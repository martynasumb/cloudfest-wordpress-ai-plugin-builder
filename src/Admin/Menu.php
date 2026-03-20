<?php

namespace Hostinger\AiPluginBuilder\Admin;

use Hostinger\AiPluginBuilder\Config;

/**
 * Registers the "WordPress AI Plugin Builder" admin page under Tools.
 */
class Menu {

	public const SLUG = 'wordpress-ai-plugin-builder';

	public function register(): void {
		add_management_page(
			__( 'WordPress AI Plugin Builder', 'wordpress-ai-plugin-builder' ),
			__( 'WordPress AI Plugin Builder', 'wordpress-ai-plugin-builder' ),
			Config::generate_capability(),
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Render the admin page — just a mount point for the Vue app.
	 */
	public function render(): void {
		echo '<div class="wrap"><div id="wordpress-ai-plugin-builder-app"></div></div>';
	}
}
