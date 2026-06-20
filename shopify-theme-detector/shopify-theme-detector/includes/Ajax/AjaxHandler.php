<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Ajax;

use ShopifyThemeDetector\Cache;
use ShopifyThemeDetector\RateLimiter;
use ShopifyThemeDetector\Settings;
use ShopifyThemeDetector\Detector\ThemeDetector;
use ShopifyThemeDetector\Detector\UrlValidator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the std_detect_theme AJAX action for both logged-in and
 * anonymous visitors.
 */
final class AjaxHandler {

	private const ACTION = 'std_detect_theme';
	private const NONCE   = 'std_detect_nonce';

	public function __construct() {
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ $this, 'handle' ] );
	}

	public function handle(): void {
		check_ajax_referer( self::NONCE, 'nonce' );

		$ip = $this->client_ip();

		if ( ! RateLimiter::is_allowed( $ip ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Rate limit exceeded. Please try again later.', 'shopify-theme-detector' ),
				],
				429
			);
		}

		$raw_url = isset( $_POST['store_url'] ) ? sanitize_text_field( wp_unslash( $_POST['store_url'] ) ) : '';

		$validated = UrlValidator::validate( $raw_url );

		if ( is_array( $validated ) && isset( $validated['error'] ) ) {
			wp_send_json_error( [ 'message' => $validated['error'] ], 400 );
		}

		$url = (string) $validated;

		RateLimiter::hit( $ip );

		$cached = Cache::get( $url );

		if ( null !== $cached ) {
			$cached['cached'] = true;
			wp_send_json_success( $cached );
		}

		$detector = new ThemeDetector();
		$result   = $detector->detect( $url );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error(
				[ 'message' => $result['error'] ?? __( 'Detection failed.', 'shopify-theme-detector' ) ],
				502
			);
		}

		$result['cached'] = false;

		Cache::set( $url, $result );

		if ( Settings::get( 'debug_mode' ) ) {
			$result['debug'] = [
				'ip'             => $ip,
				'rate_remaining' => RateLimiter::remaining( $ip ),
			];
		}

		wp_send_json_success( $result );
	}

	private function client_ip(): string {
		$candidates = [ 'REMOTE_ADDR' ];

		foreach ( $candidates as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
