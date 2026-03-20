<?php

namespace Hostinger\AiPluginBuilder\Rest;

use Hostinger\AiPluginBuilder\Config;
use Hostinger\AiPluginBuilder\Installer\PluginActivator;
use Hostinger\AiPluginBuilder\Installer\PluginWriter;
use Hostinger\AiPluginBuilder\Installer\SlugValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /wordpress-ai-plugin-builder/v1/install — write generated files and activate.
 */
class InstallController {

	private const ROUTE_NAMESPACE = 'wordpress-ai-plugin-builder/v1';

	public function register(): void {
		register_rest_route( self::ROUTE_NAMESPACE, '/install', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle' ],
			'permission_callback' => function () {
				return current_user_can( Config::install_capability() );
			},
			'args' => [
				'plugin_slug' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_file_name',
				],
				'files' => [
					'required' => true,
					'type'     => 'array',
				],
				'force' => [
					'required' => false,
					'type'     => 'boolean',
					'default'  => false,
				],
			],
		] );
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$plugin_slug = $request->get_param( 'plugin_slug' );
		$files       = $request->get_param( 'files' );
		$force       = (bool) $request->get_param( 'force' );

		if ( empty( $files ) || ! is_array( $files ) ) {
			return new WP_Error(
				'invalid_files',
				'No files provided.',
				[ 'status' => 400 ]
			);
		}

		// Validate each file has path + content, and check for path traversal.
		foreach ( $files as $file ) {
			if ( ! is_array( $file ) || empty( $file['path'] ) || ! isset( $file['content'] ) ) {
				return new WP_Error(
					'invalid_file',
					'Each file must have "path" and "content".',
					[ 'status' => 400 ]
				);
			}

			// Prevent directory traversal attacks.
			$path = $file['path'];
			if ( str_contains( $path, '..' ) || str_starts_with( $path, '/' ) || str_starts_with( $path, '\\' ) ) {
				return new WP_Error(
					'invalid_path',
					'File paths cannot contain directory traversal sequences or be absolute.',
					[ 'status' => 400 ]
				);
			}
		}

		// Validate plugin slug against WordPress.org and local plugins.
		$validator  = new SlugValidator();
		$validation = $validator->validate( $plugin_slug );

		// Hard errors block installation.
		if ( ! $validation['valid'] ) {
			return new WP_Error(
				'slug_conflict',
				$validation['error'],
				[ 'status' => 409 ]
			);
		}

		// Warnings require force=true to proceed.
		if ( ! empty( $validation['warnings'] ) && ! $force ) {
			return new WP_REST_Response( [
				'needs_confirmation' => true,
				'warnings'           => $validation['warnings'],
				'message'            => 'Plugin slug has potential conflicts. Set force=true to proceed anyway.',
			], 200 );
		}

		// Step 1: Write files via WP_Filesystem.
		$writer = new PluginWriter();
		$result = $writer->write( $plugin_slug, $files );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$main_file = $result['main_file'];

		// Step 2: Activate the plugin.
		$activator = new PluginActivator();
		$activation = $activator->activate( $main_file );

		if ( is_wp_error( $activation ) ) {
			return new WP_REST_Response( [
				'installed' => true,
				'activated' => false,
				'error'     => $activation->get_error_message(),
				'plugin'    => $main_file,
			], 200 );
		}

		return new WP_REST_Response( [
			'installed' => true,
			'activated' => true,
			'plugin'    => $main_file,
		], 200 );
	}
}
