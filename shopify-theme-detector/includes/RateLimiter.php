<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple per-IP sliding-hour rate limiter backed by transients.
 */
final class RateLimiter {

	private const PREFIX = 'std_rl_';

	public static function is_allowed( string $ip ): bool {
		$limit = (int) Settings::get( 'rate_limit' );

		if ( $limit <= 0 ) {
			return true;
		}

		$count = (int) get_transient( self::key( $ip ) );

		return $count < $limit;
	}

	public static function hit( string $ip ): void {
		$key   = self::key( $ip );
		$count = (int) get_transient( $key );

		if ( 0 === $count ) {
			set_transient( $key, 1, HOUR_IN_SECONDS );
			return;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	public static function remaining( string $ip ): int {
		$limit = (int) Settings::get( 'rate_limit' );
		$count = (int) get_transient( self::key( $ip ) );

		return max( 0, $limit - $count );
	}

	private static function key( string $ip ): string {
		return self::PREFIX . md5( $ip );
	}
}
