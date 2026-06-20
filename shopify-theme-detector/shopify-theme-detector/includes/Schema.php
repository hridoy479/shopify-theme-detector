<?php
declare( strict_types=1 );

namespace ShopifyThemeDetector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the Schema.org FAQPage JSON-LD block for the shortcode's
 * optional FAQ section.
 */
final class Schema {

	public static function faq_json_ld(): string {
		$entities = array_map(
			static function ( array $faq ): array {
				return [
					'@type'          => 'Question',
					'name'           => $faq['q'],
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $faq['a'],
					],
				];
			},
			self::faqs()
		);

		$schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];

		return (string) wp_json_encode( $schema, JSON_UNESCAPED_SLASHES );
	}

	public static function faqs(): array {
		return [
			[
				'q' => __( 'What Shopify theme is this?', 'shopify-theme-detector' ),
				'a' => __( 'Enter any Shopify store URL above and the detector will analyze its public storefront markup to identify the theme name, vendor, and other available details.', 'shopify-theme-detector' ),
			],
			[
				'q' => __( 'How does theme detection work?', 'shopify-theme-detector' ),
				'a' => __( 'The tool fetches the store\'s public homepage and inspects asset URLs, scripts, stylesheets, theme metadata, and structural patterns, then matches them against a database of known Shopify theme fingerprints.', 'shopify-theme-detector' ),
			],
			[
				'q' => __( 'Are results always accurate?', 'shopify-theme-detector' ),
				'a' => __( 'Detection relies on publicly visible signals and a confidence score is shown with every result. Heavily customized themes or stores that strip metadata may produce lower-confidence or inconclusive results.', 'shopify-theme-detector' ),
			],
		];
	}
}
