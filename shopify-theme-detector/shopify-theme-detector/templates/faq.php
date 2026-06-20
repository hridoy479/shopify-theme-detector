<?php
/**
 * Optional FAQ section rendered below the detector widget, with
 * matching Schema.org FAQPage JSON-LD for SEO.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ShopifyThemeDetector\Schema;
?>
<div class="std-faq">
	<h2 class="std-faq__title"><?php esc_html_e( 'Frequently Asked Questions', 'shopify-theme-detector' ); ?></h2>

	<?php foreach ( Schema::faqs() as $faq ) : ?>
		<details class="std-faq__item">
			<summary class="std-faq__question"><?php echo esc_html( $faq['q'] ); ?></summary>
			<div class="std-faq__answer"><?php echo esc_html( $faq['a'] ); ?></div>
		</details>
	<?php endforeach; ?>
</div>

<script type="application/ld+json"><?php echo Schema::faq_json_ld(); // phpcs:ignore WordPress.Security.EscapeOutput -- pre-encoded JSON-LD. ?></script>
