<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

use ShopifyThemeDetector\Admin\AdminSettings;
use ShopifyThemeDetector\Frontend\Frontend;
use ShopifyThemeDetector\Ajax\AjaxHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin bootstrapper (singleton).
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private bool $booted = false;

	private function __construct() {}

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		load_plugin_textdomain( 'shopify-theme-detector', false, dirname( STD_PLUGIN_BASENAME ) . '/languages' );

		new Shortcode();
		new AjaxHandler();
		new Frontend();

		if ( is_admin() ) {
			new AdminSettings();
		}
	}
}
