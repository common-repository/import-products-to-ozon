<?php
/**
 * The API Settings tab
 * 
 * @version 0.3.1 (25-05-2024)
 * @see     
 * @package 
 * 
 * @param $view_arr['feed_id']
 * @param $view_arr['tab_name']
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="postbox">
	<h2 class="hndle">
		<?php esc_html_e( 'API Settings', 'import-products-to-ozon' ); ?>
	</h2>
	<div class="inside">
		<?php
		$client_id = common_option_get( 'client_id', false, $view_arr['feed_id'], 'ip2oz' );
		$api_key = common_option_get( 'api_key', false, $view_arr['feed_id'], 'ip2oz' );
		if ( empty( $api_key ) || empty( $client_id ) ) {
			printf( '<p>%1$s <a href="%2$s" target="_blank">%3$s</a>. %4$s</p>',
				esc_html__( 'You need to get a Api-Key and Client ID', 'import-products-to-ozon' ),
				'https://seller.ozon.ru/app/settings/api-keys',
				esc_html__( 'on this page', 'import-products-to-ozon' ),
				esc_html__(
					'After configuring the API, edit your categories on the site by selecting a similar category for each of them in OZON',
					'import-products-to-ozon'
				)
			);
		}
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<?php IP2OZ_Settings_Page::print_view_html_fields( $view_arr['tab_name'], $view_arr['feed_id'] ); ?>
				<?php if ( ! empty( $api_key ) && ! empty( $client_id ) ) : ?>
					<tr class="ip2oz_tr">
						<th scope="row">
							<label for="redirect_uri">
								<?php esc_html_e( 'Check API', 'import-products-to-ozon' ); ?>
							</label>
						</th>
						<td class="overalldesc">
							<input id="button-check-api" class="button" value="<?php
							esc_html_e( 'Check API', 'import-products-to-ozon' );
							?>" type="submit" name="ip2oz_check_action" /><br />
							<span class="description">
								<?php
								printf( '<small>%s. %s</small>',
									esc_html__( 'The OZON API is configured', 'import-products-to-ozon' ),
									esc_html__(
										'Now you can check its operation by clicking on this button',
										'import-products-to-ozon'
									)
								);
								?>
							</span>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>