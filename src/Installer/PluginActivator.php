<?php

namespace Hostinger\AiPluginBuilder\Installer;

use WP_Error;

/**
 * Safely activates a generated plugin with fatal error detection.
 *
 * Uses register_shutdown_function to catch fatal errors during activation
 * (learned from WP-Autoplugin).
 */
class PluginActivator {

	private const FATAL_OPTION_KEY = 'ai_plugin_builder_fatal_error';

	/**
	 * Activate a plugin by its relative path (e.g. "my-plugin/my-plugin.php").
	 *
	 * @return true|WP_Error
	 */
	public function activate( string $plugin_path ): true|WP_Error {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Clear any previous fatal error record.
		delete_option( self::FATAL_OPTION_KEY );

		// Suppress display errors during activation.
		$old_display = ini_get( 'display_errors' );
		ini_set( 'display_errors', '0' ); // phpcs:ignore WordPress.PHP.IniSet

		// Register shutdown handler to catch fatal errors.
		register_shutdown_function( function () use ( $plugin_path ) {
			$error = error_get_last();
			if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
				update_option( self::FATAL_OPTION_KEY, [
					'plugin'  => $plugin_path,
					'message' => $error['message'],
					'file'    => $error['file'],
					'line'    => $error['line'],
				] );
			}
		} );

		$result = activate_plugin( $plugin_path );

		// Restore display errors.
		ini_set( 'display_errors', $old_display ); // phpcs:ignore WordPress.PHP.IniSet

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Check if a fatal error was recorded during the last activation attempt.
	 *
	 * @return array|null  Fatal error details or null if none.
	 */
	public static function get_last_fatal(): ?array {
		$error = get_option( self::FATAL_OPTION_KEY );
		return is_array( $error ) ? $error : null;
	}

	/**
	 * Clear the stored fatal error.
	 */
	public static function clear_fatal(): void {
		delete_option( self::FATAL_OPTION_KEY );
	}
}
