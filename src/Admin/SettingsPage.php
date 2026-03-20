<?php

namespace Hostinger\AiPluginBuilder\Admin;

use Hostinger\AiPluginBuilder\Config;

/**
 * Settings > WordPress AI Plugin Builder — LLM provider configuration.
 */
class SettingsPage {

	private const SLUG    = 'wordpress-ai-plugin-builder-settings';
	private const GROUP   = 'apb_settings';
	private const SECTION = 'apb_llm_section';

	public function register_menu(): void {
		add_options_page(
			'WordPress AI Plugin Builder',
			'WordPress AI Plugin Builder',
			Config::generate_capability(),
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function register_settings(): void {
		// Provider.
		register_setting( self::GROUP, 'apb_llm_provider', [
			'type'              => 'string',
			'default'           => 'openai',
			'sanitize_callback' => function ( $value ) {
				return in_array( $value, [ 'openai', 'anthropic' ], true ) ? $value : 'openai';
			},
		] );

		// API key.
		register_setting( self::GROUP, 'apb_api_key', [
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		// Model.
		register_setting( self::GROUP, 'apb_model', [
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		add_settings_section(
			self::SECTION,
			'LLM Configuration',
			function () {
				echo '<p>Configure the AI provider used to generate plugins.</p>';
			},
			self::SLUG
		);

		add_settings_field( 'apb_llm_provider', 'Provider', [ $this, 'field_provider' ], self::SLUG, self::SECTION );
		add_settings_field( 'apb_api_key', 'API Key', [ $this, 'field_api_key' ], self::SLUG, self::SECTION );
		add_settings_field( 'apb_model', 'Model', [ $this, 'field_model' ], self::SLUG, self::SECTION );
	}

	public function field_provider(): void {
		$value = Config::llm_provider();
		?>
		<select name="apb_llm_provider" id="apb_llm_provider">
			<option value="openai" <?php selected( $value, 'openai' ); ?>>OpenAI</option>
			<option value="anthropic" <?php selected( $value, 'anthropic' ); ?>>Anthropic</option>
		</select>
		<?php
	}

	public function field_api_key(): void {
		$value = Config::api_key();
		printf(
			'<input type="password" name="apb_api_key" id="apb_api_key" value="%s" class="regular-text" autocomplete="off" />',
			esc_attr( $value )
		);
	}

	public function field_model(): void {
		$value = get_option( 'apb_model', '' );
		printf(
			'<input type="text" name="apb_model" id="apb_model" value="%s" class="regular-text" placeholder="Leave blank for default" />',
			esc_attr( $value )
		);
		echo '<p class="description">OpenAI default: <code>gpt-4o</code> &mdash; Anthropic default: <code>claude-sonnet-4-5-20250514</code></p>';
	}

	public function render(): void {
		if ( ! current_user_can( Config::generate_capability() ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>WordPress AI Plugin Builder Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * URL to this settings page.
	 */
	public static function url(): string {
		return admin_url( 'options-general.php?page=' . self::SLUG );
	}
}
