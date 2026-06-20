<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Detector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads and exposes the JSON theme fingerprint database.
 */
final class FingerprintDatabase {

	private static ?array $themes = null;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function themes(): array {
		if ( null !== self::$themes ) {
			return self::$themes;
		}

		$path = STD_PLUGIN_DIR . 'data/fingerprints.json';

		if ( ! is_readable( $path ) ) {
			self::$themes = [];
			return self::$themes;
		}

		$json = file_get_contents( $path );
		$data = json_decode( (string) $json, true );

		self::$themes = is_array( $data ) && ! empty( $data['themes'] ) ? $data['themes'] : [];

		return self::$themes;
	}

	public static function find_by_slug( string $slug ): ?array {
		foreach ( self::themes() as $theme ) {
			if ( ( $theme['slug'] ?? '' ) === $slug ) {
				return $theme;
			}
		}

		return null;
	}

	public static function find_by_name( string $name ): ?array {
		$name = strtolower( trim( $name ) );

		foreach ( self::themes() as $theme ) {
			if ( strtolower( (string) ( $theme['name'] ?? '' ) ) === $name ) {
				return $theme;
			}
		}

		return null;
	}
}
