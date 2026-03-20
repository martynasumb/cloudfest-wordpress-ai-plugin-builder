<?php

namespace Hostinger\AiPluginBuilder\Ai;

use Hostinger\AiPluginBuilder\Config;

/**
 * Basic dangerous-pattern regex scanner for generated PHP code.
 *
 * Ported from service/config.py DANGEROUS_PATTERNS.
 */
class SecurityScanner {

	/**
	 * Scan generated files for dangerous patterns.
	 *
	 * @param array $files Array of generated file arrays [{path, type, content}, ...].
	 * @return array{passed: bool, issues: array} Issues found, if any.
	 */
	public static function scan( array $files ): array {
		$issues   = [];
		$patterns = Config::dangerous_patterns();

		foreach ( $files as $file ) {
			// Only scan PHP files.
			if ( 'php' !== ( $file['type'] ?? '' ) ) {
				continue;
			}

			$lines = explode( "\n", $file['content'] ?? '' );

			foreach ( $lines as $line_num => $line ) {
				foreach ( $patterns as $pattern ) {
					if ( preg_match( $pattern, $line ) ) {
						$issues[] = [
							'file_path'   => $file['path'],
							'line'        => $line_num + 1,
							'pattern'     => $pattern,
							'line_content' => trim( $line ),
						];
					}
				}
			}
		}

		return [
			'passed' => empty( $issues ),
			'issues' => array_slice( $issues, 0, 10 ), // Cap at 10 issues.
		];
	}
}
