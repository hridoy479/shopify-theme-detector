<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin accessor around the plugin's single options-table entry.
 */
final class Settings {

	private const OPTION_KEY = 'std_settings';

	private static array $defaults = [
		'cache_enabled'  => 1,
		'cache_duration' => 24,
		'rate_limit'     => 10,
		'debug_mode'     => 0,
	];

	public static function all(): array {
		$stored = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		return array_merge( self::$defaults, $stored );
	}

	public static function get( string $key ) {
		$all = self::all();

		return $all[ $key ] ?? null;
	}

	public static function update( array $values ): bool {
		$sanitized = [
			'cache_enabled'  => empty( $values['cache_enabled'] ) ? 0 : 1,
			'cache_duration' => max( 1, (int) ( $values['cache_duration'] ?? 24 ) ),
			'rate_limit'     => max( 1, (int) ( $values['rate_limit'] ?? 10 ) ),
			'debug_mode'     => empty( $values['debug_mode'] ) ? 0 : 1,
		];

		return update_option( self::OPTION_KEY, $sanitized );
	}

	public static function option_key(): string {
		return self::OPTION_KEY;
	}
}
