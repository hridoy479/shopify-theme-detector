<?php
/**
 * Frontend shortcode markup: input form + results container.
 * Variables available: none required (self-contained).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$instance_id = 'std-' . wp_unique_id();
?>
<div class="std-widget" id="<?php echo esc_attr( $instance_id ); ?>" data-std-widget>
	<form class="std-form" data-std-form autocomplete="off">
		<label class="std-form__label" for="<?php echo esc_attr( $instance_id . '-url' ); ?>">
			<?php esc_html_e( 'Shopify Store URL', 'shopify-theme-detector' ); ?>
		</label>

		<div class="std-form__row">
			<input
				type="text"
				inputmode="url"
				id="<?php echo esc_attr( $instance_id . '-url' ); ?>"
				class="std-form__input"
				name="std_store_url"
				placeholder="<?php esc_attr_e( 'https://gymshark.com', 'shopify-theme-detector' ); ?>"
				data-std-url-input
				required
			/>
			<button type="submit" class="std-form__submit" data-std-submit>
				<span class="std-form__submit-label"><?php esc_html_e( 'Detect Theme', 'shopify-theme-detector' ); ?></span>
				<span class="std-spinner" data-std-spinner hidden aria-hidden="true"></span>
			</button>
		</div>

		<p class="std-form__hint"><?php esc_html_e( 'Example: https://gymshark.com', 'shopify-theme-detector' ); ?></p>
	</form>

	<div class="std-message" data-std-message role="alert" hidden></div>

	<div class="std-results" data-std-results hidden></div>
</div>
