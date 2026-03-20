<?php

namespace Hostinger\AiPluginBuilder;

/**
 * Central configuration for the AI Plugin Builder.
 */
class Config {

	/**
	 * LLM provider: 'openai' or 'anthropic'.
	 */
	public static function llm_provider(): string {
		return get_option( 'apb_llm_provider', 'openai' );
	}

	/**
	 * API key for the configured LLM provider.
	 */
	public static function api_key(): string {
		return get_option( 'apb_api_key', '' );
	}

	/**
	 * Model identifier (e.g. 'gpt-4o', 'claude-sonnet-4-6').
	 */
	public static function model(): string {
		$value = get_option( 'apb_model', '' );
		if ( '' === $value ) {
			return 'openai' === self::llm_provider() ? 'gpt-4o' : 'claude-sonnet-4-6';
		}
		return $value;
	}

	/**
	 * Maximum number of files the planner may create.
	 */
	public static function max_files(): int {
		return 10;
	}

	/**
	 * Whether the plugin has a valid configuration (API key set).
	 */
	public static function is_configured(): bool {
		return '' !== self::api_key();
	}

	/**
	 * Max tokens for the planner step.
	 */
	public static function planner_max_tokens(): int {
		return 16384;
	}

	/**
	 * Max tokens for the coder step.
	 */
	public static function coder_max_tokens(): int {
		return 32768;
	}

	/**
	 * HTTP timeout in seconds for LLM API calls.
	 */
	public static function request_timeout(): int {
		return defined( 'WORDPRESS_AI_PLUGIN_BUILDER_TIMEOUT' )
			? (int) WORDPRESS_AI_PLUGIN_BUILDER_TIMEOUT
			: 120;
	}

	/**
	 * Required capability to generate plugins.
	 */
	public static function generate_capability(): string {
		return 'manage_options';
	}

	/**
	 * Required capability to install generated plugins.
	 */
	public static function install_capability(): string {
		return 'install_plugins';
	}

	/**
	 * Regex patterns for dangerous PHP constructs in generated code.
	 *
	 * @return string[]
	 */
	public static function dangerous_patterns(): array {
		return [
			'/\beval\s*\(/i',
			'/\bexec\s*\(/i',
			'/\bsystem\s*\(/i',
			'/\bpassthru\s*\(/i',
			'/\bshell_exec\s*\(/i',
			'/\bproc_open\s*\(/i',
			'/\bpopen\s*\(/i',
			'/\bfile_put_contents\s*\(\s*\$_(GET|POST|REQUEST)/i',
			'/\b(unlink|rmdir)\s*\(\s*\$_(GET|POST|REQUEST)/i',
			'/\bbase64_decode\s*\(\s*\$_(GET|POST|REQUEST)/i',
			'/\$_GET\b(?!.*\b(sanitize_|esc_|wp_verify_nonce|absint|intval))/i',
			'/\$_POST\b(?!.*\b(sanitize_|esc_|wp_verify_nonce|absint|intval))/i',
		];
	}
}
