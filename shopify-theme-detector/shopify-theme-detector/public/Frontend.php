<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues frontend assets, but only on pages where the shortcode
 * actually renders (set via mark_shortcode_present()).
 */
final class Frontend {

	private static bool $shortcode_present = false;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_footer', [ $this, 'maybe_enqueue' ], 1 );
	}

	public static function mark_shortcode_present(): void {
		self::$shortcode_present = true;
	}

	public function register_assets(): void {
		wp_register_style(
			'std-frontend',
			STD_PLUGIN_URL . 'assets/css/style.css',
			[],
			STD_VERSION
		);

		wp_register_script(
			'std-frontend',
			STD_PLUGIN_URL . 'assets/js/script.js',
			[],
			STD_VERSION,
			true
		);

		wp_localize_script(
			'std-frontend',
			'STD_Data',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'std_detect_nonce' ),
				'i18n'    => [
					'loading'      => __( 'Scanning storefront…', 'shopify-theme-detector' ),
					'detectLabel'  => __( 'Detect Theme', 'shopify-theme-detector' ),
					'genericErr'   => __( 'Something went wrong. Please try again.', 'shopify-theme-detector' ),
					'emptyUrl'     => __( 'Please enter a store URL.', 'shopify-theme-detector' ),
					'confidence'   => __( 'confidence', 'shopify-theme-detector' ),
					'themeId'      => __( 'Theme ID', 'shopify-theme-detector' ),
					'themeVendor'  => __( 'Theme Vendor', 'shopify-theme-detector' ),
					'themeType'    => __( 'Theme Type', 'shopify-theme-detector' ),
					'themeVersion' => __( 'Theme Version', 'shopify-theme-detector' ),
					'published'    => __( 'Published', 'shopify-theme-detector' ),
					'unpublished'  => __( 'Not Published', 'shopify-theme-detector' ),
					'method'       => __( 'Detection Method', 'shopify-theme-detector' ),
					'timestamp'    => __( 'Detected At', 'shopify-theme-detector' ),
					'notAvailable' => __( 'Not available', 'shopify-theme-detector' ),
				],
			]
		);

		// In the (rare) case the shortcode runs before wp_enqueue_scripts
		// has fired (e.g. some page builders), enqueue immediately too.
		if ( self::$shortcode_present ) {
			$this->enqueue();
		}
	}

	public function maybe_enqueue(): void {
		if ( self::$shortcode_present ) {
			$this->enqueue();
		}
	}

	private function enqueue(): void {
		wp_enqueue_style( 'std-frontend' );
		wp_enqueue_script( 'std-frontend' );
	}
}
