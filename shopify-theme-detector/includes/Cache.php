<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps WordPress transients for caching detection results.
 */
final class Cache {

	private const PREFIX = 'std_res_';

	public static function get( string $url ): ?array {
		if ( ! Settings::get( 'cache_enabled' ) ) {
			return null;
		}

		$value = get_transient( self::key( $url ) );

		return is_array( $value ) ? $value : null;
	}

	public static function set( string $url, array $data ): void {
		if ( ! Settings::get( 'cache_enabled' ) ) {
			return;
		}

		$hours = max( 1, (int) Settings::get( 'cache_duration' ) );

		set_transient( self::key( $url ), $data, $hours * HOUR_IN_SECONDS );
	}

	public static function forget( string $url ): void {
		delete_transient( self::key( $url ) );
	}

	private static function key( string $url ): string {
		return self::PREFIX . md5( strtolower( trim( $url ) ) );
	}
}
