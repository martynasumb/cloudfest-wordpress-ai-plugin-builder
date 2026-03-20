<?php

namespace Hostinger\AiPluginBuilder\Ai;

use Hostinger\AiPluginBuilder\Config;
use WP_Error;

/**
 * PHP HTTP client for OpenAI and Anthropic REST APIs via wp_remote_post().
 *
 * Ported from service/agents/llm_client.py.
 */
class AiClient {

	private const MAX_CONTINUE_ROUNDS = 3;

	/** @var array[] Accumulated token usage steps. */
	private array $token_steps = [];

	/**
	 * Call the configured LLM provider.
	 *
	 * @param array $args {
	 *     @type string $system_prompt  System prompt.
	 *     @type string $user_prompt    User prompt.
	 *     @type int    $max_tokens     Max tokens for response.
	 *     @type float  $temperature    Sampling temperature.
	 *     @type bool   $json_mode      Request JSON output (OpenAI only).
	 * }
	 * @return array{content: string, input_tokens: int, output_tokens: int, model: string, provider: string, continued: int}|WP_Error
	 */
	public function call( array $args ) {
		$args = wp_parse_args( $args, [
			'system_prompt' => '',
			'user_prompt'   => '',
			'max_tokens'    => 4096,
			'temperature'   => 0.3,
			'json_mode'     => false,
		] );

		$provider = Config::llm_provider();
		$model    = Config::model();

		if ( 'anthropic' === $provider ) {
			return $this->call_anthropic( $model, $args );
		}

		return $this->call_openai( $model, $args );
	}

	/**
	 * Record a step in the token usage tracker.
	 */
	public function record( string $step_name, array $response ): void {
		$this->token_steps[] = [
			'step'          => $step_name,
			'model'         => $response['model'] ?? '',
			'provider'      => $response['provider'] ?? '',
			'input_tokens'  => $response['input_tokens'] ?? 0,
			'output_tokens' => $response['output_tokens'] ?? 0,
			'continued'     => $response['continued'] ?? 0,
		];
	}

	/**
	 * Get the token usage summary.
	 *
	 * @return array{steps: array[], total_input_tokens: int, total_output_tokens: int, total_tokens: int}
	 */
	public function get_usage_summary(): array {
		$total_input  = 0;
		$total_output = 0;

		foreach ( $this->token_steps as $step ) {
			$total_input  += $step['input_tokens'];
			$total_output += $step['output_tokens'];
		}

		return [
			'steps'              => $this->token_steps,
			'total_input_tokens' => $total_input,
			'total_output_tokens' => $total_output,
			'total_tokens'       => $total_input + $total_output,
		];
	}

	/**
	 * Call OpenAI Chat Completions API.
	 *
	 * @return array|WP_Error
	 */
	private function call_openai( string $model, array $args ) {
		$api_key = Config::api_key();

		$messages = [
			[ 'role' => 'system', 'content' => $args['system_prompt'] ],
			[ 'role' => 'user', 'content' => $args['user_prompt'] ],
		];

		// GPT-5 series and o-series use max_completion_tokens instead of max_tokens.
		$uses_new_param = (bool) preg_match( '/^(gpt-5|o1|o3|o4)/', $model );

		$full_content = '';
		$total_input  = 0;
		$total_output = 0;
		$continued    = 0;

		for ( $round = 0; $round <= self::MAX_CONTINUE_ROUNDS; $round++ ) {
			$body = [
				'model'    => $model,
				'messages' => $messages,
			];

			if ( $uses_new_param ) {
				$body['max_completion_tokens'] = $args['max_tokens'];
			} else {
				$body['max_tokens']  = $args['max_tokens'];
				$body['temperature'] = $args['temperature'];
			}

			if ( $args['json_mode'] && ! $uses_new_param ) {
				$body['response_format'] = [ 'type' => 'json_object' ];
			}

			$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
				'timeout' => Config::request_timeout(),
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body' => wp_json_encode( $body ),
			] );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code >= 400 ) {
				$msg = $data['error']['message'] ?? 'Unknown OpenAI error';
				return new WP_Error( 'openai_error', $msg, [ 'status' => $code ] );
			}

			$chunk = $data['choices'][0]['message']['content'] ?? '';
			$full_content .= $chunk;

			$total_input  += $data['usage']['prompt_tokens'] ?? 0;
			$total_output += $data['usage']['completion_tokens'] ?? 0;

			$finish_reason = $data['choices'][0]['finish_reason'] ?? 'stop';

			if ( 'length' === $finish_reason && ! $args['json_mode'] ) {
				$continued++;
				$messages[] = [ 'role' => 'assistant', 'content' => $chunk ];
				$messages[] = [ 'role' => 'user', 'content' => 'Continue exactly from where you left off. Do not repeat any code already generated.' ];
			} else {
				break;
			}
		}

		return [
			'content'       => $full_content,
			'input_tokens'  => $total_input,
			'output_tokens' => $total_output,
			'model'         => $model,
			'provider'      => 'openai',
			'continued'     => $continued,
		];
	}

	/**
	 * Call Anthropic Messages API.
	 *
	 * @return array|WP_Error
	 */
	private function call_anthropic( string $model, array $args ) {
		$api_key = Config::api_key();

		$messages = [
			[ 'role' => 'user', 'content' => $args['user_prompt'] ],
		];

		$full_content = '';
		$total_input  = 0;
		$total_output = 0;
		$continued    = 0;

		for ( $round = 0; $round <= self::MAX_CONTINUE_ROUNDS; $round++ ) {
			$body = [
				'model'       => $model,
				'max_tokens'  => $args['max_tokens'],
				'temperature' => $args['temperature'],
				'system'      => $args['system_prompt'],
				'messages'    => $messages,
			];

			$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
				'timeout' => Config::request_timeout(),
				'headers' => [
					'Content-Type'      => 'application/json',
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
				],
				'body' => wp_json_encode( $body ),
			] );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$data = json_decode( $response_body, true );

			if ( $code >= 400 ) {
				$msg = $data['error']['message'] ?? $data['message'] ?? 'Unknown Anthropic error';
				return new WP_Error( 'anthropic_error', $msg, [ 'status' => $code ] );
			}

			$chunk = $data['content'][0]['text'] ?? '';
			$full_content .= $chunk;

			$total_input  += $data['usage']['input_tokens'] ?? 0;
			$total_output += $data['usage']['output_tokens'] ?? 0;

			$stop_reason = $data['stop_reason'] ?? 'end_turn';

			if ( 'max_tokens' === $stop_reason ) {
				$continued++;
				$messages[] = [ 'role' => 'assistant', 'content' => $chunk ];
				$messages[] = [ 'role' => 'user', 'content' => 'Continue exactly from where you left off. Do not repeat any code already generated.' ];
			} else {
				break;
			}
		}

		return [
			'content'       => $full_content,
			'input_tokens'  => $total_input,
			'output_tokens' => $total_output,
			'model'         => $model,
			'provider'      => 'anthropic',
			'continued'     => $continued,
		];
	}

	/**
	 * Parse JSON from an LLM response, stripping markdown code fences if present.
	 *
	 * @param string $text Raw LLM response.
	 * @return array|WP_Error Parsed JSON array.
	 */
	public static function parse_json_response( string $text ) {
		$text = trim( $text );

		// Strip markdown code fences.
		if ( 0 === strpos( $text, '```' ) ) {
			$lines = explode( "\n", $text );
			// Remove first line (```json) and find closing ```.
			$start = 1;
			$end   = count( $lines );
			for ( $i = count( $lines ) - 1; $i > 0; $i-- ) {
				if ( '```' === trim( $lines[ $i ] ) ) {
					$end = $i;
					break;
				}
			}
			$text = implode( "\n", array_slice( $lines, $start, $end - $start ) );
		}

		$data = json_decode( $text, true );

		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'json_parse_error', 'Failed to parse LLM response as JSON: ' . json_last_error_msg() );
		}

		return $data;
	}

	/**
	 * Strip markdown code fences from generated code output.
	 */
	public static function strip_code_fences( string $text ): string {
		$text = trim( $text );

		if ( 0 === strpos( $text, '```' ) ) {
			$lines = explode( "\n", $text );
			$start = 1;
			$end   = count( $lines );
			for ( $i = count( $lines ) - 1; $i > 0; $i-- ) {
				if ( '```' === trim( $lines[ $i ] ) ) {
					$end = $i;
					break;
				}
			}
			$text = implode( "\n", array_slice( $lines, $start, $end - $start ) );
		}

		return $text;
	}
}
