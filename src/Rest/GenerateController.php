<?php

namespace Hostinger\AiPluginBuilder\Rest;

use Hostinger\AiPluginBuilder\Ai\BackgroundJob;
use Hostinger\AiPluginBuilder\Ai\IntentDetector;
use Hostinger\AiPluginBuilder\Ai\Pipeline;
use Hostinger\AiPluginBuilder\Config;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /wordpress-ai-plugin-builder/v1/generate — dispatch a background pipeline job.
 */
class GenerateController {

	private const ROUTE_NAMESPACE = 'wordpress-ai-plugin-builder/v1';

	public function register(): void {
		register_rest_route( self::ROUTE_NAMESPACE, '/generate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle' ],
			'permission_callback' => function () {
				return current_user_can( Config::generate_capability() );
			},
			'args' => [
				'description' => [
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new \WP_Error( 'invalid_type', 'Description must be a string.' );
						}
						$len = mb_strlen( trim( $value ) );
						if ( $len < 10 ) {
							return new \WP_Error( 'too_short', 'Description must be at least 10 characters.' );
						}
						if ( $len > 5000 ) {
							return new \WP_Error( 'too_long', 'Description must not exceed 5000 characters.' );
						}
						return true;
					},
					'sanitize_callback' => 'sanitize_textarea_field',
				],
				'complexity' => [
					'type'              => 'string',
					'default'           => 'simple',
					'enum'              => [ 'simple', 'complex' ],
					'validate_callback' => 'rest_validate_request_arg',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'previous_plan' => [
					'type'    => 'object',
					'default' => null,
				],
				'previous_files' => [
					'type'    => 'array',
					'default' => null,
				],
			],
		] );
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		if ( ! Config::is_configured() ) {
			return new WP_REST_Response( [
				'message' => 'API key not configured. Please visit Settings > WordPress AI Plugin Builder.',
			], 400 );
		}

		$description    = $request->get_param( 'description' );
		$complexity     = $request->get_param( 'complexity' );
		$previous_plan  = $request->get_param( 'previous_plan' );
		$previous_files = $request->get_param( 'previous_files' );

		// Detect user intent before running the full pipeline.
		$detector       = new IntentDetector();
		$classification = $detector->classify( $description, $previous_plan );

		if ( is_wp_error( $classification ) ) {
			return new WP_REST_Response( [
				'message' => 'Failed to process request: ' . $classification->get_error_message(),
			], 500 );
		}

		$intent = $classification['intent'];

		// Handle non-plugin requests immediately without background job.
		if ( 'question' === $intent || 'other' === $intent ) {
			return new WP_REST_Response( [
				'type'        => $intent,
				'response'    => $classification['response'],
				'token_usage' => $classification['token_usage'],
			], 200 );
		}

		// For plugin_request or modification_request, proceed with generation.
		$job_id = wp_generate_uuid4();

		// Store initial state using direct database access to bypass object cache.
		Pipeline::set_job_state( 'apb_job_' . $job_id, [
			'job_id'         => $job_id,
			'status'         => 'queued',
			'current_step'   => 'Queued for processing...',
			'plan'           => null,
			'files'          => [],
			'review'         => null,
			'error'          => null,
			'token_usage'    => null,
			'previous_plan'  => $previous_plan,
			'previous_files' => $previous_files,
			'is_modification' => 'modification_request' === $intent,
		] );

		// Dispatch background job with context.
		BackgroundJob::dispatch( $job_id, $description, $complexity, $previous_plan, $previous_files );

		return new WP_REST_Response( [
			'job_id' => $job_id,
			'status' => 'queued',
			'type'   => $intent,
		], 202 );
	}
}
