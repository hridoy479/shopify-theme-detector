<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector\Detector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core detection engine. Runs eight independent heuristics against a
 * storefront's public HTML and aggregates them into a single confidence
 * scored result. No third-party API is used: only a single GET request
 * to the store URL supplied by the visitor.
 */
final class ThemeDetector {

	private array $methods_run = [];

	public function detect( string $url ): array {
		$html = HttpFetcher::get( $url );

		if ( null === $html ) {
			return [
				'success' => false,
				'error'   => __( 'Could not reach that URL. Please check it and try again.', 'shopify-theme-detector' ),
			];
		}

		$is_shopify = $this->is_shopify_store( $html );

		if ( ! $is_shopify ) {
			return [
				'success'   => true,
				'url'       => $url,
				'is_shopify' => false,
				'message'   => __( 'This does not appear to be a Shopify store.', 'shopify-theme-detector' ),
				'timestamp' => $this->timestamp(),
			];
		}

		$metadata = $this->method_metadata( $html );

		$candidates = [];
		$this->collect( $candidates, $this->method_asset_urls( $html ) );
		$this->collect( $candidates, $this->method_theme_scripts( $html ) );
		$this->collect( $candidates, $this->method_fingerprint_strings( $html ) );
		$this->collect( $candidates, $this->method_css_assets( $html ) );
		$this->collect( $candidates, $this->method_js_assets( $html ) );
		$this->collect( $candidates, $this->method_structural_patterns( $html ) );
		$structure = $this->method_html_structure( $html );

		$result = $this->resolve( $metadata, $candidates, $structure );

		return array_merge(
			[
				'success'    => true,
				'url'        => $url,
				'is_shopify' => true,
				'screenshot' => $this->method_screenshot_preview( $html ),
				'timestamp'  => $this->timestamp(),
			],
			$result
		);
	}

	/** ---------------------------------------------------------------
	 * Best-effort screenshot preview: reuses the og:image meta tag
	 * already present in the homepage markup we fetched. No external
	 * screenshot service is called.
	 * ------------------------------------------------------------- */
	private function method_screenshot_preview( string $html ): ?string {
		if ( preg_match( '#<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m )
			|| preg_match( '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']#i', $html, $m ) ) {
			$candidate = esc_url_raw( $m[1] );

			return '' !== $candidate ? $candidate : null;
		}

		return null;
	}

	/** ---------------------------------------------------------------
	 * Shopify presence check
	 * ------------------------------------------------------------- */
	private function is_shopify_store( string $html ): bool {
		$signals = [
			'cdn.shopify.com',
			'Shopify.theme',
			'shopify-features',
			'/cdn/shop/t/',
			'Shopify.shop',
			'window.Shopify',
			'shopify-checkout-api-token',
		];

		foreach ( $signals as $signal ) {
			if ( false !== stripos( $html, $signal ) ) {
				return true;
			}
		}

		return false;
	}

	/** ---------------------------------------------------------------
	 * Method 3: Shopify theme metadata object (Shopify.theme = {...})
	 * ------------------------------------------------------------- */
	private function method_metadata( string $html ): ?array {
		$this->methods_run[] = 'Theme Metadata Inspection';

		if ( ! preg_match( '/Shopify\.theme\s*=\s*(\{.*?\})\s*;/s', $html, $matches ) ) {
			return null;
		}

		$decoded = json_decode( $matches[1], true );

		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return [
			'name'           => $decoded['schema_name'] ?? $decoded['name'] ?? null,
			'theme_id'       => $decoded['id'] ?? null,
			'theme_store_id' => $decoded['theme_store_id'] ?? null,
			'version'        => $decoded['schema_version'] ?? null,
			'role'           => $decoded['role'] ?? null,
			'handle'         => $decoded['handle'] ?? null,
		];
	}

	/** ---------------------------------------------------------------
	 * Method 1: Shopify asset (CDN) URL analysis
	 * ------------------------------------------------------------- */
	private function method_asset_urls( string $html ): array {
		$this->methods_run[] = 'Asset URL Fingerprint Match';

		$matches = [];

		if ( preg_match_all( '#cdn\.shopify\.com/s/files/[^"\'\s]*?/t/(\d+)/assets/([a-zA-Z0-9_\-.]+)#i', $html, $found, PREG_SET_ORDER ) ) {
			foreach ( $found as $m ) {
				$matches[] = strtolower( $m[2] );
			}
		}

		return $this->score_against_fingerprints( $matches, 'Asset URL Fingerprint Match' );
	}

	/** ---------------------------------------------------------------
	 * Method 2: Theme-related <script> inspection
	 * ------------------------------------------------------------- */
	private function method_theme_scripts( string $html ): array {
		$this->methods_run[] = 'Theme Script Inspection';

		$matches = [];

		if ( preg_match_all( '#<script[^>]+src=["\']([^"\']+)["\']#i', $html, $found ) ) {
			foreach ( $found[1] as $src ) {
				if ( false !== stripos( $src, 'theme' ) || false !== stripos( $src, '/t/' ) ) {
					$matches[] = strtolower( basename( wp_parse_url( $src, PHP_URL_PATH ) ?? $src ) );
				}
			}
		}

		return $this->score_against_fingerprints( $matches, 'Theme Script Inspection' );
	}

	/** ---------------------------------------------------------------
	 * Method 4: Known theme fingerprint string search
	 * ------------------------------------------------------------- */
	private function method_fingerprint_strings( string $html ): array {
		$this->methods_run[] = 'Fingerprint String Search';

		$results = [];

		foreach ( FingerprintDatabase::themes() as $theme ) {
			foreach ( (array) ( $theme['fingerprint_strings'] ?? [] ) as $needle ) {
				if ( '' !== $needle && false !== stripos( $html, $needle ) ) {
					$results[] = [
						'slug'       => $theme['slug'],
						'confidence' => 80,
						'method'     => 'Fingerprint String Search',
					];
					break;
				}
			}
		}

		return $results;
	}

	/** ---------------------------------------------------------------
	 * Method 5: CSS asset analysis
	 * ------------------------------------------------------------- */
	private function method_css_assets( string $html ): array {
		$this->methods_run[] = 'CSS Asset Analysis';

		$matches = [];

		if ( preg_match_all( '#<link[^>]+rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\']#i', $html, $found ) ) {
			foreach ( $found[1] as $href ) {
				$matches[] = strtolower( basename( wp_parse_url( $href, PHP_URL_PATH ) ?? $href ) );
			}
		}

		return $this->score_against_fingerprints( $matches, 'CSS Asset Analysis' );
	}

	/** ---------------------------------------------------------------
	 * Method 6: JS asset analysis (broad scan of all enqueued scripts)
	 * ------------------------------------------------------------- */
	private function method_js_assets( string $html ): array {
		$this->methods_run[] = 'JS Asset Analysis';

		$matches = [];

		if ( preg_match_all( '#<script[^>]+src=["\']([^"\']+\.js[^"\']*)["\']#i', $html, $found ) ) {
			foreach ( $found[1] as $src ) {
				$matches[] = strtolower( basename( wp_parse_url( $src, PHP_URL_PATH ) ?? $src ) );
			}
		}

		return $this->score_against_fingerprints( $matches, 'JS Asset Analysis' );
	}

	/** ---------------------------------------------------------------
	 * Method 7: Section / template CSS class pattern analysis
	 * ------------------------------------------------------------- */
	private function method_structural_patterns( string $html ): array {
		$this->methods_run[] = 'Section & Template Pattern Analysis';

		$results = [];

		foreach ( FingerprintDatabase::themes() as $theme ) {
			$hits = 0;

			foreach ( (array) ( $theme['css_classes'] ?? [] ) as $class ) {
				if ( '' !== $class && false !== stripos( $html, $class ) ) {
					++$hits;
				}
			}

			if ( $hits > 0 ) {
				$results[] = [
					'slug'       => $theme['slug'],
					'confidence' => min( 90, 40 + ( $hits * 20 ) ),
					'method'     => 'Section & Template Pattern Analysis',
				];
			}
		}

		return $results;
	}

	/** ---------------------------------------------------------------
	 * Method 8: General storefront HTML structure analysis
	 * (Online Store 2.0 sections, structural confirmation only)
	 * ------------------------------------------------------------- */
	private function method_html_structure( string $html ): array {
		$this->methods_run[] = 'Storefront HTML Structure Analysis';

		$section_based = false !== stripos( $html, 'shopify-section-' );
		$has_app_blocks = false !== stripos( $html, 'shopify-app-block' );

		return [
			'section_based' => $section_based,
			'has_app_blocks' => $has_app_blocks,
		];
	}

	/** ---------------------------------------------------------------
	 * Scoring helpers
	 * ------------------------------------------------------------- */
	private function score_against_fingerprints( array $found_filenames, string $method ): array {
		if ( empty( $found_filenames ) ) {
			return [];
		}

		$results = [];

		foreach ( FingerprintDatabase::themes() as $theme ) {
			$hits = 0;

			foreach ( (array) ( $theme['asset_patterns'] ?? [] ) as $pattern ) {
				foreach ( $found_filenames as $filename ) {
					if ( '' !== $pattern && @preg_match( '#' . $pattern . '#i', $filename ) ) {
						++$hits;
					}
				}
			}

			if ( $hits > 0 ) {
				$results[] = [
					'slug'       => $theme['slug'],
					'confidence' => min( 92, 45 + ( $hits * 15 ) ),
					'method'     => $method,
				];
			}
		}

		return $results;
	}

	private function collect( array &$candidates, array $new_results ): void {
		foreach ( $new_results as $result ) {
			$candidates[] = $result;
		}
	}

	/** ---------------------------------------------------------------
	 * Final aggregation: metadata wins outright, otherwise the highest
	 * scoring fingerprint candidate (boosted by corroborating methods).
	 * ------------------------------------------------------------- */
	private function resolve( ?array $metadata, array $candidates, array $structure ): array {
		if ( ! empty( $metadata['name'] ) ) {
			$db_theme = FingerprintDatabase::find_by_name( (string) $metadata['name'] );

			return [
				'theme_name'      => $metadata['name'],
				'theme_id'        => $metadata['theme_id'],
				'theme_version'   => $metadata['version'] ?? __( 'Not available', 'shopify-theme-detector' ),
				'theme_vendor'    => $db_theme['vendor'] ?? __( 'Unknown / Custom', 'shopify-theme-detector' ),
				'theme_type'      => $db_theme['type'] ?? __( 'Unknown', 'shopify-theme-detector' ),
				'published'       => 'main' === ( $metadata['role'] ?? '' ),
				'confidence'      => 99,
				'detection_method' => __( 'Theme Metadata Object (Shopify.theme)', 'shopify-theme-detector' ),
				'methods_checked' => $this->methods_run,
			];
		}

		if ( empty( $candidates ) ) {
			return [
				'theme_name'       => __( 'Unknown', 'shopify-theme-detector' ),
				'theme_id'         => null,
				'theme_version'    => __( 'Not available', 'shopify-theme-detector' ),
				'theme_vendor'     => __( 'Unknown', 'shopify-theme-detector' ),
				'theme_type'       => __( 'Unknown', 'shopify-theme-detector' ),
				'published'        => null,
				'confidence'       => $structure['section_based'] ? 25 : 10,
				'detection_method' => __( 'Storefront Structure Heuristic', 'shopify-theme-detector' ),
				'methods_checked'  => $this->methods_run,
			];
		}

		$tally = [];

		foreach ( $candidates as $candidate ) {
			$slug = $candidate['slug'];

			if ( ! isset( $tally[ $slug ] ) ) {
				$tally[ $slug ] = [
					'slug'       => $slug,
					'max'        => 0,
					'count'      => 0,
					'methods'    => [],
				];
			}

			$tally[ $slug ]['max']    = max( $tally[ $slug ]['max'], $candidate['confidence'] );
			++$tally[ $slug ]['count'];
			$tally[ $slug ]['methods'][ $candidate['method'] ] = true;
		}

		uasort(
			$tally,
			static fn( $a, $b ) => ( $b['max'] + $b['count'] ) <=> ( $a['max'] + $a['count'] )
		);

		$winner    = reset( $tally );
		$db_theme  = FingerprintDatabase::find_by_slug( $winner['slug'] );
		$confidence = min( 96, $winner['max'] + ( ( $winner['count'] - 1 ) * 5 ) );

		return [
			'theme_name'       => $db_theme['name'] ?? ucfirst( $winner['slug'] ),
			'theme_id'         => null,
			'theme_version'    => __( 'Not available', 'shopify-theme-detector' ),
			'theme_vendor'     => $db_theme['vendor'] ?? __( 'Unknown', 'shopify-theme-detector' ),
			'theme_type'       => $db_theme['type'] ?? __( 'Unknown', 'shopify-theme-detector' ),
			'published'        => null,
			'confidence'       => $confidence,
			'detection_method' => implode( ', ', array_keys( $winner['methods'] ) ),
			'methods_checked'  => $this->methods_run,
		];
	}

	private function timestamp(): string {
		return current_time( 'Y-m-d H:i:s' );
	}
}
