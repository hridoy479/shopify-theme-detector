<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Admin;

use ShopifyThemeDetector\Settings;
use ShopifyThemeDetector\Detector\FingerprintDatabase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Settings -> Shopify Theme Detector admin page.
 */
final class AdminSettings {

	private const PAGE_SLUG = 'shopify-theme-detector';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_post_std_clear_cache', [ $this, 'handle_clear_cache' ] );
	}

	public function register_menu(): void {
		add_options_page(
			__( 'Shopify Theme Detector', 'shopify-theme-detector' ),
			__( 'Shopify Theme Detector', 'shopify-theme-detector' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			'std_settings_group',
			Settings::option_key(),
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
			]
		);
	}

	public function sanitize( $value ): array {
		return [
			'cache_enabled'  => empty( $value['cache_enabled'] ) ? 0 : 1,
			'cache_duration' => max( 1, (int) ( $value['cache_duration'] ?? 24 ) ),
			'rate_limit'     => max( 1, (int) ( $value['rate_limit'] ?? 10 ) ),
			'debug_mode'     => empty( $value['debug_mode'] ) ? 0 : 1,
		];
	}

	public function handle_clear_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shopify-theme-detector' ) );
		}

		check_admin_referer( 'std_clear_cache' );

		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_std\\_%' OR option_name LIKE '\\_transient\\_timeout\\_std\\_%'"
		);

		wp_safe_redirect( add_query_arg( [ 'page' => self::PAGE_SLUG, 'cache_cleared' => 1 ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::all();
		$themes   = FingerprintDatabase::themes();

		include STD_PLUGIN_DIR . 'admin/views/settings-page.php';
	}
}
