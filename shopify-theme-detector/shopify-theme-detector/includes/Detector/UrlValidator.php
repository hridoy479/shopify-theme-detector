<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Detector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates user-supplied store URLs and guards against SSRF abuse.
 */
final class UrlValidator {

	/**
	 * Normalizes and validates a URL. Returns the sanitized URL on success
	 * or a WP_Error-style array of [ 'error' => string ] on failure.
	 *
	 * @return string|array{error:string}
	 */
	public static function validate( string $raw_url ) {
		$raw_url = trim( $raw_url );

		if ( '' === $raw_url ) {
			return [ 'error' => __( 'Please enter a store URL.', 'shopify-theme-detector' ) ];
		}

		if ( ! preg_match( '#^https?://#i', $raw_url ) ) {
			$raw_url = 'https://' . $raw_url;
		}

		$sanitized = filter_var( $raw_url, FILTER_SANITIZE_URL );

		if ( false === $sanitized || ! filter_var( $sanitized, FILTER_VALIDATE_URL ) ) {
			return [ 'error' => __( 'That does not look like a valid URL.', 'shopify-theme-detector' ) ];
		}

		$parts = wp_parse_url( $sanitized );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return [ 'error' => __( 'That does not look like a valid URL.', 'shopify-theme-detector' ) ];
		}

		$scheme = strtolower( $parts['scheme'] );

		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return [ 'error' => __( 'Only http and https URLs are allowed.', 'shopify-theme-detector' ) ];
		}

		$host = strtolower( $parts['host'] );

		if ( self::is_blocked_host( $host ) ) {
			return [ 'error' => __( 'This host is not allowed.', 'shopify-theme-detector' ) ];
		}

		$ips = self::resolve_host( $host );

		if ( empty( $ips ) ) {
			return [ 'error' => __( 'The store host could not be resolved.', 'shopify-theme-detector' ) ];
		}

		foreach ( $ips as $ip ) {
			if ( self::is_private_or_reserved_ip( $ip ) ) {
				return [ 'error' => __( 'This host resolves to a restricted network address and cannot be scanned.', 'shopify-theme-detector' ) ];
			}
		}

		$path  = $parts['path'] ?? '/';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		return $scheme . '://' . $host . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' ) . $path . $query;
	}

	private static function is_blocked_host( string $host ): bool {
		$blocked_exact = [
			'localhost',
			'localhost.localdomain',
			'metadata.google.internal',
			'ip6-localhost',
		];

		if ( in_array( $host, $blocked_exact, true ) ) {
			return true;
		}

		if ( str_ends_with( $host, '.local' ) || str_ends_with( $host, '.internal' ) ) {
			return true;
		}

		// Bare IPs are blocked outright; we only resolve hostnames.
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return self::is_private_or_reserved_ip( $host );
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	private static function resolve_host( string $host ): array {
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return [ $host ];
		}

		$records = dns_get_record( $host, DNS_A + DNS_AAAA );
		$ips     = [];

		if ( is_array( $records ) ) {
			foreach ( $records as $record ) {
				if ( ! empty( $record['ip'] ) ) {
					$ips[] = $record['ip'];
				} elseif ( ! empty( $record['ipv6'] ) ) {
					$ips[] = $record['ipv6'];
				}
			}
		}

		if ( empty( $ips ) ) {
			$ip = gethostbyname( $host );

			if ( $ip && $ip !== $host ) {
				$ips[] = $ip;
			}
		}

		return $ips;
	}

	private static function is_private_or_reserved_ip( string $ip ): bool {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}
}
