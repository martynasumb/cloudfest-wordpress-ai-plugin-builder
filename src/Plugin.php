<?php

namespace Hostinger\AiPluginBuilder;

use Hostinger\AiPluginBuilder\Admin\Assets;
use Hostinger\AiPluginBuilder\Admin\Menu;
use Hostinger\AiPluginBuilder\Admin\SettingsPage;
use Hostinger\AiPluginBuilder\Ai\BackgroundJob;
use Hostinger\AiPluginBuilder\Rest\GenerateController;
use Hostinger\AiPluginBuilder\Rest\InstallController;
use Hostinger\AiPluginBuilder\Rest\StatusController;

/**
 * Main plugin bootstrap — registers hooks and wires dependencies.
 */
class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->register_hooks();
	}

	private function register_hooks(): void {
		// Admin menu.
		$menu = new Menu();
		add_action( 'admin_menu', [ $menu, 'register' ] );

		// Settings page.
		$settings = new SettingsPage();
		add_action( 'admin_menu', [ $settings, 'register_menu' ] );
		add_action( 'admin_init', [ $settings, 'register_settings' ] );

		// Enqueue assets only on our admin page.
		$assets = new Assets();
		add_action( 'admin_enqueue_scripts', [ $assets, 'enqueue' ] );

		// REST API routes.
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );

		// Background job AJAX handler.
		BackgroundJob::register();
	}

	public function register_routes(): void {
		$generate = new GenerateController();
		$generate->register();

		$status = new StatusController();
		$status->register();

		$install = new InstallController();
		$install->register();
	}
}
