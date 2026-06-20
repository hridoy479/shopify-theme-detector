<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation/deactivation bootstrapping.
 */
final class Activator {

	public static function activate(): void {
		$defaults = [
			'cache_enabled'   => 1,
			'cache_duration'  => 24, // hours.
			'rate_limit'      => 10, // requests per IP per hour.
			'debug_mode'      => 0,
		];

		if ( false === get_option( 'std_settings' ) ) {
			add_option( 'std_settings', $defaults );
		}
	}

	public static function deactivate(): void {
		global $wpdb;

		// Clean up rate-limit and result transients created by this plugin.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_std\\_%' OR option_name LIKE '\\_transient\\_timeout\\_std\\_%'"
		);
	}
}
