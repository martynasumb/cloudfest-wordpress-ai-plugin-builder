<?php

namespace Hostinger\AiPluginBuilder\Ai;

use WP_Error;

/**
 * Detects user intent from their input.
 *
 * Classifies whether the user wants to:
 * - Create a new plugin (plugin_request)
 * - Ask a question about WordPress (question)
 * - Modify an existing generation (modification_request)
 * - Something else (other)
 */
class IntentDetector {

	private AiClient $client;

	public function __construct() {
		$this->client = new AiClient();
	}

	/**
	 * Classify user intent.
	 *
	 * @param string     $description    User's input.
	 * @param array|null $previous_plan  Previous plan if continuing a session.
	 * @return array{intent: string, confidence: float, response: string|null}|WP_Error
	 */
	public function classify( string $description, ?array $previous_plan = null ) {
		$system_prompt = $this->get_system_prompt();
		$user_prompt   = $this->get_user_prompt( $description, $previous_plan );

		$result = $this->client->call( [
			'system_prompt' => $system_prompt,
			'user_prompt'   => $user_prompt,
			'max_tokens'    => 500,
			'temperature'   => 0.1,
			'json_mode'     => true,
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->client->record( 'intent_detection', $result );

		$data = AiClient::parse_json_response( $result['content'] );

		if ( is_wp_error( $data ) ) {
			// Log the error for debugging but fall back to plugin_request to avoid blocking users.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'APB IntentDetector: JSON parse failed - ' . $data->get_error_message() );

			return [
				'intent'      => 'plugin_request',
				'confidence'  => 0.5,
				'response'    => null,
				'token_usage' => $this->client->get_usage_summary(),
			];
		}

		return [
			'intent'      => $data['intent'] ?? 'plugin_request',
			'confidence'  => (float) ( $data['confidence'] ?? 0.5 ),
			'response'    => $data['response'] ?? null,
			'token_usage' => $this->client->get_usage_summary(),
		];
	}

	/**
	 * Get the system prompt for intent classification.
	 */
	private function get_system_prompt(): string {
		return <<<'PROMPT'
You are an intent classifier for a WordPress AI Plugin Builder. Your job is to determine what the user wants.

Classify the user's intent into one of these categories:
- plugin_request: User wants to create a new WordPress plugin (most common)
- modification_request: User wants to modify or improve a previously generated plugin
- question: User is asking a question about WordPress, plugins, or how to do something
- other: Request is unclear, off-topic, or inappropriate

Return ONLY valid JSON in this exact format:
{
    "intent": "plugin_request|modification_request|question|other",
    "confidence": 0.0-1.0,
    "response": "If intent is 'question' or 'other', provide a helpful response here. Otherwise null."
}

Examples:
- "Create a contact form plugin" → plugin_request
- "Build me a plugin that adds a maintenance mode" → plugin_request
- "A dashboard widget showing posts" → plugin_request
- "Rename the plugin to XYZ" → modification_request (if previous context exists)
- "Add a settings page" → modification_request (if previous context exists)
- "Can I modify existing plugins?" → question
- "How do WordPress hooks work?" → question
- "What is the best way to add custom fields?" → question
- "Hello" → other
- "Make me money" → other
PROMPT;
	}

	/**
	 * Get the user prompt for classification.
	 */
	private function get_user_prompt( string $description, ?array $previous_plan ): string {
		$context = '';
		if ( $previous_plan ) {
			$context = sprintf(
				"\n\nPREVIOUS CONTEXT: The user previously generated a plugin named \"%s\" (slug: %s). " .
				"If they're asking to modify, rename, or improve it, classify as modification_request.",
				$previous_plan['plugin_name'] ?? 'Unknown',
				$previous_plan['plugin_slug'] ?? 'unknown'
			);
		}

		return "User input: \"{$description}\"{$context}";
	}
}
