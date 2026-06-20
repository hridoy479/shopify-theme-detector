<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

use ShopifyThemeDetector\Frontend\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the [shopify_theme_detector] shortcode.
 */
final class Shortcode {

	public function __construct() {
		add_shortcode( 'shopify_theme_detector', [ $this, 'render' ] );
	}

	public function render( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'faq' => 'yes',
			],
			$atts,
			'shopify_theme_detector'
		);

		Frontend::mark_shortcode_present();

		ob_start();
		include STD_PLUGIN_DIR . 'templates/detector-form.php';

		if ( 'yes' === strtolower( (string) $atts['faq'] ) ) {
			include STD_PLUGIN_DIR . 'templates/faq.php';
		}

		return (string) ob_get_clean();
	}
}
