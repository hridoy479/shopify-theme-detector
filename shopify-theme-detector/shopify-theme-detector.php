<?php
/**
 * Plugin Name:       Shopify Theme Detector
 * Plugin URI:        https://github.com/hridoy/shopify-theme-detector
 * Description:       Detect the Shopify theme used by any Shopify store directly from your WordPress site. No external API required.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Hridoy
 * Author URI:        https://github.com/hridoy
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shopify-theme-detector
 * Domain Path:       /languages
 */

declare( strict_types=1 );

namespace ShopifyThemeDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STD_VERSION', '1.0.0' );
define( 'STD_PLUGIN_FILE', __FILE__ );
define( 'STD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4 style autoloader. More specific sub-namespaces are mapped to
 * their own directories; anything else falls back to /includes.
 */
spl_autoload_register(
	static function ( string $class ): void {
		static $map = null;

		if ( null === $map ) {
			$map = [
				__NAMESPACE__ . '\\Detector\\' => STD_PLUGIN_DIR . 'includes/Detector/',
				__NAMESPACE__ . '\\Ajax\\'     => STD_PLUGIN_DIR . 'includes/Ajax/',
				__NAMESPACE__ . '\\Admin\\'    => STD_PLUGIN_DIR . 'admin/',
				__NAMESPACE__ . '\\Frontend\\' => STD_PLUGIN_DIR . 'public/',
				__NAMESPACE__ . '\\'           => STD_PLUGIN_DIR . 'includes/',
			];
		}

		foreach ( $map as $prefix => $dir ) {
			if ( ! str_starts_with( $class, $prefix ) ) {
				continue;
			}

			$relative = substr( $class, strlen( $prefix ) );
			$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
			$file     = $dir . $relative . '.php';

			if ( is_readable( $file ) ) {
				require $file;
				return;
			}
		}
	}
);

register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Activator::class, 'deactivate' ] );

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);
