<?php
/**
 * Plugin Form Activate
 *
 * @package                 iCopyDoc Plugins (v1, core 16-08-2023)
 * @subpackage              Import products to OZON
 * @since                   0.1.0
 * 
 * @version                 0.4.0 (01-06-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     [ 202, 402, 412, 418, 520 ]
 * 
 * @param     array         $pref
 * @param      array        $slug
 *
 * @depends                 classes:    ICPD_Set_Admin_Notices
 *                          traits:	    
 *                          methods:    
 *                          functions:  
 *                          constants:  
 *                          options:    
 */
defined( 'ABSPATH' ) || exit;
final class IP2OZ_Plugin_Form_Activate {
	const INSTRUCTION_URL = 'https://icopydoc.ru/kak-aktivirovat-pro-versiyu-instruktsiya/';
	private $list_plugin_names = [ 
		'ip2ozp' => [ 'name' => 'PRO', 'code' => 'renewlicense24ozon' ]
	];
	private $pref = 'ip2ozp';
	private $slug;
	private $submit_name;
	private $opt_name_order_id;
	private $opt_name_order_email;

	public function __construct( $pref = 'ip2ozp', $slug = '' ) {
		$this->pref = $pref;
		$this->slug = $slug;
		$this->submit_name = $this->get_pref() . '_submit_license_pro';
		$this->opt_name_order_id = $this->get_pref() . '_order_id';
		$this->opt_name_order_email = $this->get_pref() . '_order_email';

		$this->save_form();
		$this->init_hooks(); // подключим хуки
	}

	/**
	 * @uses add_action()
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'ip2oz_activation_forms', [ $this, 'the_form' ] );
		// TODO: Удалить в следующих версиях
		add_action( 'ip2oz_before_support_project', [ $this, 'the_form' ] );
	}

	/**
	 * Print the activation form
	 *
	 * @return void
	 */
	public function the_form() {
		if ( is_multisite() ) {
			$order_id = get_blog_option( get_current_blog_id(), $this->get_opt_name_order_id() );
			$order_email = get_blog_option( get_current_blog_id(), $this->get_opt_name_order_email() );
		} else {
			$order_id = get_option( $this->get_opt_name_order_id() );
			$order_email = get_option( $this->get_opt_name_order_email() );
		}
		?>
		<style>
			input.pw {
				-webkit-text-security: disc;
			}
		</style>
		<div class="postbox">
			<h2 class="hndle">
				<?php
				printf( '%s %s',
					__( 'License data', 'import-products-to-ozon' ),
					esc_html( $this->list_plugin_names[ $this->get_pref()]['name'] )
				); ?>
			</h2>
			<div class="inside">
				<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" method="post" enctype="multipart/form-data">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<?php _e( 'Order ID', 'import-products-to-ozon' ); ?>
								</th>
								<td class="overalldesc">
									<input class="pw" type="text"
										name="<?php echo esc_attr( $this->get_opt_name_order_id() ); ?>"
										value="<?php echo esc_attr( $order_id ); ?>" /><br />
									<span class="description">
										<a target="_blank" href="<?php
										printf( '%1$s?utm_source=%2$s&utm_medium=organic&utm_campaign=%2$s%3$s',
											esc_attr( self::INSTRUCTION_URL ),
											esc_attr( $this->slug ),
											'&utm_content=settings&utm_term=how-to-activate-order-id'
										); ?>"><?php _e( 'Read more', 'import-products-to-ozon' ); ?></a>
									</span>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php _e( 'Order Email', 'import-products-to-ozon' ); ?>
								</th>
								<td class="overalldesc">
									<input name="<?php echo esc_attr( $this->get_opt_name_order_email() ); ?>"
										value="<?php echo esc_attr( $order_email ); ?>" type="text" /><br />
									<span class="description">
										<a target="_blank" href="<?php
										printf( '%1$s?utm_source=%2$s&utm_medium=organic&utm_campaign=%2$s%3$s',
											esc_attr( self::INSTRUCTION_URL ),
											esc_attr( $this->slug ),
											'&utm_content=settings&utm_term=how-to-activate-order-email'
										); ?>"><?php _e( 'Read more', 'import-products-to-ozon' ); ?></a></span>
								</td>
							</tr>
						</tbody>
					</table>
					<input class="button-primary" name="<?php echo esc_attr( $this->get_submit_name() ); ?>"
						value="<?php _e( 'Update License Data', 'import-products-to-ozon' ); ?>" type="submit" />
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Get prefix
	 * 
	 * @return string
	 */
	private function get_pref() {
		return $this->pref;
	}

	/**
	 * Get submit button name
	 * 
	 * @return string
	 */
	private function get_submit_name() {
		return $this->submit_name;
	}

	/**
	 * Get order id field name
	 * 
	 * @return string
	 */
	private function get_opt_name_order_id() {
		return $this->opt_name_order_id;
	}

	/**
	 * Get order field name
	 * 
	 * @return string
	 */
	private function get_opt_name_order_email() {
		return $this->opt_name_order_email;
	}

	/**
	 * Saving data
	 * 
	 * @return void
	 */
	private function save_form() {
		if ( isset( $_REQUEST[ $this->get_submit_name()] ) ) {
			if ( is_multisite() ) {
				if ( isset( $_POST[ $this->get_opt_name_order_id()] ) ) {
					update_blog_option(
						get_current_blog_id(),
						$this->get_opt_name_order_id(),
						sanitize_text_field( $_POST[ $this->get_opt_name_order_id()] )
					);
				}
				if ( isset( $_POST[ $this->get_opt_name_order_email()] ) ) {
					update_blog_option(
						get_current_blog_id(),
						$this->get_opt_name_order_email(),
						sanitize_text_field( $_POST[ $this->get_opt_name_order_email()] )
					);
				}
			} else {
				if ( isset( $_POST[ $this->get_opt_name_order_id()] ) ) {
					update_option(
						$this->get_opt_name_order_id(),
						sanitize_text_field( $_POST[ $this->get_opt_name_order_id()] )
					);
				}
				if ( isset( $_POST[ $this->get_opt_name_order_email()] ) ) {
					update_option(
						$this->get_opt_name_order_email(),
						sanitize_text_field( $_POST[ $this->get_opt_name_order_email()] )
					);
				}
			}
			wp_clean_plugins_cache();
			wp_clean_update_cache();
			add_filter( 'pre_site_transient_update_plugins', '__return_null' );
			wp_update_plugins();
			remove_filter( 'pre_site_transient_update_plugins', '__return_null' );
			$message = sprintf( '%1$s <a href="javascript:location.reload(true)">%2$s</a>',
				__( 'License data has been updated', 'import-products-to-ozon' ),
				__( 'Refresh this page', 'import-products-to-ozon' )
			);
			$class = 'notice-success';
			new ICPD_Set_Admin_Notices( $message, $class );
			wp_update_plugins();
		}
	}
}