<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Detector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin, hardened wrapper around wp_safe_remote_get for fetching storefront
 * markup. Uses wp_safe_remote_get so WordPress' own SSRF protections
 * (via wp_http_validate_url) are applied on every redirect hop too.
 */
final class HttpFetcher {

	private const MAX_BYTES = 1_500_000; // ~1.5MB is plenty for a storefront <head>+<body> scan.

	public static function get( string $url ): ?string {
		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'     => 12,
				'redirection' => 3,
				'sslverify'   => true,
				'user-agent'  => 'ShopifyThemeDetector/' . STD_VERSION . ' (+WordPress)',
				'headers'     => [
					'Accept' => 'text/html,application/xhtml+xml',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 400 ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( '' === $body ) {
			return null;
		}

		if ( strlen( $body ) > self::MAX_BYTES ) {
			$body = substr( $body, 0, self::MAX_BYTES );
		}

		return $body;
	}
}
