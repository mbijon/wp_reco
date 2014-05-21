<?php
/**
 *
 * @package Reco, plugin for WordPress
 * @since     0.1
 */

if ( ! defined( 'DB_NAME' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}
?>

<?php if ( is_admin() ) : ?>

	<div class="wrap">
        <form method="post">
			<?php if ( function_exists('screen_icon') ) { screen_icon(); } ?>
			<h2><?php _e( 'Reco Settings', 'wp-reco' ); ?></h2>
			
			<table class="form-table">
				<tr>
					<td colspan="2">
						<div class="widgets-holder-wrap" style="padding: 0 10px 10px;">
							<?php $sm_url = admin_url( 'options-media.php' ); ?>
							<h3>Plugin Functionality:</h3>
							<p>Filtering of content types will be added to a future version. Currently returns Posts, Pages &amp; public Custom Post Types</p>
						</div>
					</td>
				</tr>
				<tr>
					<th>
						<strong><?php _e( 'Indexed Content Types', 'wp-reco' ); ?></strong>
					</th>
					<td>
						<p>-- Not implemented yet. --</p>
					</td>
				</tr>
			</table>
		<p class="submit">
			<input type="submit" name="reco-submit" id="reco-submit" class="button button-primary" value="Save Changes">
		</p>
		
		<?php
			// WordPress nonce for security
			wp_nonce_field( 'reco_update_admin_options', 'reco_admin_nonce' );
		?>
        </form>
	</div>

<?php endif;

