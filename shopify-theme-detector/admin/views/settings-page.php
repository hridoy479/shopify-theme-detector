<?php
/**
 * Settings -> Shopify Theme Detector admin view.
 * Variables available: $settings (array), $themes (array).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Shopify Theme Detector', 'shopify-theme-detector' ); ?></h1>

	<?php if ( isset( $_GET['cache_cleared'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Cache cleared successfully.', 'shopify-theme-detector' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'std_settings_group' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Cache', 'shopify-theme-detector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="std_settings[cache_enabled]" value="1" <?php checked( ! empty( $settings['cache_enabled'] ) ); ?> />
						<?php esc_html_e( 'Cache detection results using WordPress transients.', 'shopify-theme-detector' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="std-cache-duration"><?php esc_html_e( 'Cache Duration (hours)', 'shopify-theme-detector' ); ?></label>
				</th>
				<td>
					<input type="number" min="1" max="168" id="std-cache-duration" name="std_settings[cache_duration]" value="<?php echo esc_attr( (string) $settings['cache_duration'] ); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="std-rate-limit"><?php esc_html_e( 'Rate Limit (requests per IP / hour)', 'shopify-theme-detector' ); ?></label>
				</th>
				<td>
					<input type="number" min="1" max="1000" id="std-rate-limit" name="std_settings[rate_limit]" value="<?php echo esc_attr( (string) $settings['rate_limit'] ); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Debug Mode', 'shopify-theme-detector' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="std_settings[debug_mode]" value="1" <?php checked( ! empty( $settings['debug_mode'] ) ); ?> />
						<?php esc_html_e( 'Include extra diagnostic data in AJAX responses (visible to all users; enable temporarily only).', 'shopify-theme-detector' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Cache Management', 'shopify-theme-detector' ); ?></h2>
	<p><?php esc_html_e( 'Clear all cached detection results immediately.', 'shopify-theme-detector' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'std_clear_cache' ); ?>
		<input type="hidden" name="action" value="std_clear_cache" />
		<?php submit_button( __( 'Clear Cache', 'shopify-theme-detector' ), 'secondary', 'submit', false ); ?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Theme Fingerprint Database', 'shopify-theme-detector' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: %d: number of themes in the fingerprint database */
			esc_html__( '%d known themes loaded from data/fingerprints.json.', 'shopify-theme-detector' ),
			count( $themes )
		);
		?>
	</p>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Theme', 'shopify-theme-detector' ); ?></th>
				<th><?php esc_html_e( 'Vendor', 'shopify-theme-detector' ); ?></th>
				<th><?php esc_html_e( 'Type', 'shopify-theme-detector' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $themes as $theme ) : ?>
				<tr>
					<td><?php echo esc_html( $theme['name'] ?? '' ); ?></td>
					<td><?php echo esc_html( $theme['vendor'] ?? '' ); ?></td>
					<td><?php echo esc_html( $theme['type'] ?? '' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
