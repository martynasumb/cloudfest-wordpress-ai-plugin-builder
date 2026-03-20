<?php

namespace Hostinger\AiPluginBuilder\Admin;

use Hostinger\AiPluginBuilder\Config;

/**
 * Enqueues the Vue frontend assets on the AI Plugin Builder admin page.
 */
class Assets {

	public function enqueue( string $hook_suffix ): void {
		// Only load on our page: tools_page_wordpress-ai-plugin-builder.
		if ( 'tools_page_' . Menu::SLUG !== $hook_suffix ) {
			return;
		}

		$dist_dir = WORDPRESS_AI_PLUGIN_BUILDER_DIR . 'assets/dist/';
		$dist_url = WORDPRESS_AI_PLUGIN_BUILDER_URL . 'assets/dist/';

		// Vite outputs a manifest.json in production builds.
		$manifest_path = $dist_dir . '.vite/manifest.json';
		if ( ! file_exists( $manifest_path ) ) {
			// Dev fallback — the Vue dev server handles assets.
			return;
		}

		$manifest = json_decode( file_get_contents( $manifest_path ), true );
		if ( empty( $manifest ) ) {
			return;
		}

		// Find the entrypoint (src/main.ts).
		$entry = $manifest['src/main.ts'] ?? null;
		if ( ! $entry ) {
			return;
		}

		// Enqueue JS.
		if ( ! empty( $entry['file'] ) ) {
			wp_enqueue_script(
				'wordpress-ai-plugin-builder',
				$dist_url . $entry['file'],
				[],
				WORDPRESS_AI_PLUGIN_BUILDER_VERSION,
				true
			);

			// Pass config to the frontend.
			wp_localize_script( 'wordpress-ai-plugin-builder', 'aiPluginBuilder', [
				'restUrl'      => esc_url_raw( rest_url( 'wordpress-ai-plugin-builder/v1/' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'adminUrl'     => admin_url(),
				'isConfigured' => Config::is_configured(),
				'settingsUrl'  => esc_url( SettingsPage::url() ),
			] );
		}

		// Enqueue CSS.
		if ( ! empty( $entry['css'] ) ) {
			foreach ( $entry['css'] as $index => $css_file ) {
				wp_enqueue_style(
					'wordpress-ai-plugin-builder-' . $index,
					$dist_url . $css_file,
					[],
					WORDPRESS_AI_PLUGIN_BUILDER_VERSION
				);
			}
		}
	}
}
