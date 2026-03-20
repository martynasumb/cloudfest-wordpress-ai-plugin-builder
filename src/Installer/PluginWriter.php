<?php

namespace Hostinger\AiPluginBuilder\Installer;

use WP_Error;
use WP_Filesystem_Base;

/**
 * Writes generated plugin files to wp-content/plugins using WP_Filesystem.
 */
class PluginWriter {

	/**
	 * Write all files for a generated plugin.
	 *
	 * @param string $plugin_slug  Plugin directory name (e.g. "recipe-manager").
	 * @param array  $files        Array of [ 'path' => string, 'content' => string, 'is_main' => bool ].
	 *
	 * @return array{main_file: string}|WP_Error  The relative plugin path (slug/main.php) on success.
	 */
	public function write( string $plugin_slug, array $files ): array|WP_Error {
		$filesystem = $this->init_filesystem();
		if ( is_wp_error( $filesystem ) ) {
			return $filesystem;
		}

		$plugins_dir = $filesystem->wp_plugins_dir();
		if ( ! $plugins_dir ) {
			return new WP_Error( 'no_plugins_dir', 'Could not determine plugins directory.' );
		}

		$plugin_dir = trailingslashit( $plugins_dir ) . $plugin_slug;

		// Create plugin directory.
		if ( ! $filesystem->is_dir( $plugin_dir ) ) {
			if ( ! $filesystem->mkdir( $plugin_dir, FS_CHMOD_DIR ) ) {
				return new WP_Error(
					'mkdir_failed',
					sprintf( 'Could not create directory: %s', $plugin_dir )
				);
			}
		}

		$main_file = '';

		foreach ( $files as $file ) {
			$relative_path = ltrim( $file['path'], '/' );
			$full_path     = trailingslashit( $plugin_dir ) . $relative_path;

			// Ensure subdirectory exists.
			$dir = dirname( $full_path );
			if ( ! $filesystem->is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					return new WP_Error(
						'mkdir_failed',
						sprintf( 'Could not create directory: %s', $dir )
					);
				}
			}

			// Write the file.
			if ( ! $filesystem->put_contents( $full_path, $file['content'], FS_CHMOD_FILE ) ) {
				return new WP_Error(
					'write_failed',
					sprintf( 'Could not write file: %s', $relative_path )
				);
			}

			// Track the main plugin file.
			if ( ! empty( $file['is_main'] ) ) {
				$main_file = $plugin_slug . '/' . $relative_path;
			}
		}

		// Fallback: if no file marked as main, use the first root-level PHP file.
		if ( empty( $main_file ) ) {
			foreach ( $files as $file ) {
				$path = ltrim( $file['path'], '/' );
				if ( str_ends_with( $path, '.php' ) && ! str_contains( $path, '/' ) ) {
					$main_file = $plugin_slug . '/' . $path;
					break;
				}
			}
		}

		if ( empty( $main_file ) ) {
			return new WP_Error( 'no_main_file', 'Could not determine the main plugin file.' );
		}

		return [ 'main_file' => $main_file ];
	}

	/**
	 * Initialize WP_Filesystem.
	 */
	private function init_filesystem(): WP_Filesystem_Base|WP_Error {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return new WP_Error( 'filesystem_error', 'Could not initialize WP_Filesystem.' );
		}

		return $wp_filesystem;
	}
}
