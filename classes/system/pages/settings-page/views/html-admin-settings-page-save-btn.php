<?php
/**
 * Print the Save button
 * 
 * @version 0.3.1 (25-05-2024)
 * @see     
 * @package 
 * 
 * @param $view_arr['tab_name']
 * @param $view_arr['feed_id']
 */
defined( 'ABSPATH' ) || exit;

if ( $view_arr['tab_name'] === 'no_submit_tab' ) {
	return;
}
?>
<div class="postbox">
	<div class="inside">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="button-primary"></label></th>
					<td class="overalldesc">
						<?php wp_nonce_field( 'ip2oz_nonce_action', 'ip2oz_nonce_field' ); ?>
						<input id="button-primary" class="button-primary" name="ip2oz_submit_action" type="submit"
							value="<?php
							if ( $view_arr['tab_name'] === 'main_tab' ) {
								printf( '%s & %s (ID: %s)',
									esc_html__( 'Save', 'import-products-to-ozon' ),
									esc_html__( 'Run Import', 'import-products-to-ozon' ),
									$view_arr['feed_id']
								);
							} else {
								printf( '%s (ID: %s)',
									esc_html__( 'Save', 'import-products-to-ozon' ),
									$view_arr['feed_id']
								);
							}
							?>" /><br />
						<span class="description">
							<small>
								<?php esc_html_e( 'Click to save the settings', 'import-products-to-ozon' ); ?>
							</small>
						</span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>