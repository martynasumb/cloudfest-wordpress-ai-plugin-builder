<?php
/**
 * Plugin Name: WordPress AI Plugin Builder
 * Plugin URI:  https://hostinger.com
 * Description: Chat-based AI plugin generator — describe what you need, review the plan, and install with one click.
 * Version:     0.1.0
 * Author:      Hostinger
 * Author URI:  https://hostinger.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wordpress-ai-plugin-builder
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WORDPRESS_AI_PLUGIN_BUILDER_VERSION', '0.1.0' );
define( 'WORDPRESS_AI_PLUGIN_BUILDER_FILE', __FILE__ );
define( 'WORDPRESS_AI_PLUGIN_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORDPRESS_AI_PLUGIN_BUILDER_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 autoloader via Composer.
$autoload = WORDPRESS_AI_PLUGIN_BUILDER_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

// Bootstrap.
add_action( 'plugins_loaded', function () {
	\Hostinger\AiPluginBuilder\Plugin::instance();
} );
