<?php
/**
 * The class will help you connect your store to OZON using API
 *
 * @package                 Import Products to OZON
 * @subpackage              
 * @since                   0.1.0
 * 
 * @version                 0.7.1 (13-07-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     
 *
 * @param     string        $post_id - Required
 * @param     string        $actions - Required
 *
 * @depends                 classes:    IP2OZ_Api_Helper_Simple
 *                                      IP2OZ_Api_Helper_Variable
 *                          traits:     
 *                          methods:    
 *                          functions:  
 *                          constants:  
 */
defined( 'ABSPATH' ) || exit;

final class IP2OZ_Api_Helper {
	/**
	 * Feed ID
	 * @var string
	 */
	protected $feed_id;
	/**
	 * @var WC_Product
	 */
	protected $product;
	/**
	 * Product data array
	 * @var array
	 */
	protected $product_data_arr = [];
	/**
	 * Product SKU list array
	 * @var array
	 */
	protected $product_sku_list_arr = [];
	/**
	 * Category ID on OZON
	 * @var string
	 */
	protected $category_id_on_ozon = '';
	/**
	 * Skip reasons array
	 * @var array
	 */
	protected $skip_reasons_arr = [];

	/**
	 * The class will help you connect your store to OZON using API
	 */
	public function __construct() {
		$this->feed_id = '1';
	}

	/**
	 * Set product data
	 * 
	 * @param int $product_id
	 * @param string $actions
	 * 
	 * @return void
	 */
	public function set_product_data( $product_id, $actions ) {
		$this->product = wc_get_product( $product_id );
		if ( null == $this->get_product() ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'There is no product with this ID', 'import-products-to-ozon' ),
				'post_id' => $product_id,
				'file' => 'class-ip2oz-ok-ru-api-helper.php',
				'line' => __LINE__
			] );
			return;
		}

		$terms_post = get_the_terms( $product_id, 'product_cat' );
		if ( empty( $terms_post ) ) {
			$this->category_id_on_ozon = '';
		}

		if ( $this->get_product()->is_type( 'simple' ) ) {
			$obj = new IP2OZ_Api_Helper_Simple( $this->get_product(), $actions, $this->get_feed_id() );
			$this->set_helper_result( $obj, $product_id );
			unset( $obj );
		} else if ( $this->get_product()->is_type( 'variable' ) ) {
			$variations_arr = $this->get_product()->get_available_variations();
			$variation_count = count( $variations_arr );
			for ( $i = 0; $i < $variation_count; $i++ ) {
				$offer_id = $variations_arr[ $i ]['variation_id'];
				$offer = new WC_Product_Variation( $offer_id ); // получим вариацию

				$obj = new IP2OZ_Api_Helper_Variable( $this->get_product(), $actions, $offer, $variation_count, $this->get_feed_id() );
				$this->set_helper_result( $obj, $offer_id );
				unset( $obj );
			}
			// echo get_array_as_string($this->get_result(), '<br/>');
		} else {
			$this->add_skip_reason( [ 
				'reason' => __( 'The product is not simple or variable', 'import-products-to-ozon' ),
				'post_id' => $product_id,
				'file' => 'class-ip2oz-ok-ru-api-helper.php',
				'line' => __LINE__
			] );
		}
		return;
	}

	/**
	 * Set helper result
	 * 
	 * @return void
	 */
	public function set_helper_result( $obj, $post_id_on_wp ) {
		if ( ! empty( $obj->get_skip_reasons_arr() ) ) {
			foreach ( $obj->get_skip_reasons_arr() as $value ) {
				array_push( $this->skip_reasons_arr, $value );
			}
		}
		if ( ! empty( $obj->get_result() ) ) {
			array_push( $this->product_data_arr, $obj->get_result() );
			$flag = true;
		} else {
			$flag = false;
		}

		array_push( $this->product_sku_list_arr,
			[ 
				'sku_on_ozon' => $obj->get_sku(),
				'post_id_on_wp' => $post_id_on_wp,
				'have_get_result' => $flag
			]
		);
	}

	/**
	 * Get product data array
	 * 
	 * @return array
	 */
	public function get_product_data() {
		return $this->product_data_arr;
	}

	/**
	 * Get category ID on OZON
	 * 
	 * @return string
	 */
	public function get_category_id_on_ozon() {
		return $this->category_id_on_ozon;
	}

	/**
	 * Checks whether the product has been imported to the OZON
	 * 
	 * @param int $product_id - Product ID on your site
	 * 
	 * @return false|string `false` - import was not; `string` - product ID on the OZON
	 */
	public function is_product_exists( $product_id ) {
		if ( get_post_meta( $product_id, '_ip2oz_prod_id_on_ozon', true ) == '' ) {
			return false;
		} else {
			return get_post_meta( $product_id, '_ip2oz_prod_id_on_ozon', true );
		}
	}

	/**
	 * Sets information about the synchronization of the product with the OZON
	 * 
	 * @param int $product_id
	 * @param array $data_arr
	 * 
	 * @return void
	 */
	public function set_product_exists( $product_id, $data_arr ) {
		// ? if ( isset( $data_arr['sku_on_ozon'] ) ) {
		// ?	update_post_meta( $product_id, '_ip2oz_sku_on_ozon', $data_arr['sku_on_ozon'] );
		// ? }
		if ( isset( $data_arr['product_id_on_ozon'] ) ) {
			update_post_meta( $product_id, '_ip2oz_prod_id_on_ozon', $data_arr['product_id_on_ozon'] );
		}
		if ( isset( $data_arr['product_archive_status'] ) ) {
			if ( empty( $data_arr['product_archive_status'] ) ) {
				delete_post_meta( $product_id, '_ip2oz_prod_archive_status' );
			} else {
				update_post_meta( $product_id, '_ip2oz_prod_archive_status', $data_arr['product_archive_status'] );
			}
		}
		return;
	}

	/**
	 * Sets an array of reasons for skiping an product
	 * 
	 * @param mixed $v
	 * 
	 * @return void
	 */
	public function set_skip_reasons_arr( $v ) {
		$this->skip_reasons_arr[] = $v; // ? может лучше так: array_push( $this->skip_reasons_arr, $v );
	}

	/**
	 * Get skip reasons array
	 * 
	 * @return array
	 */
	public function get_skip_reasons_arr() {
		return $this->skip_reasons_arr;
	}

	/**
	 * Adds the reason for skipping the product (or variation) to the array
	 * 
	 * @param array $reason
	 * 
	 * @return void
	 */
	protected function add_skip_reason( $reason ) {
		if ( isset( $reason['offer_id'] ) ) {
			$reason_string = sprintf(
				'FEED № %1$s; Вариация товара (postId = %2$s, offer_id = %3$s) пропущена. Причина: %4$s; Файл: %5$s; Строка: %6$s',
				$this->feed_id, $reason['post_id'], $reason['offer_id'], $reason['reason'], $reason['file'], $reason['line']
			);
		} else {
			$reason_string = sprintf(
				'FEED № %1$s; Товар с postId = %2$s пропущен. Причина: %3$s; Файл: %4$s; Строка: %5$s',
				$this->feed_id, $reason['post_id'], $reason['reason'], $reason['file'], $reason['line']
			);
		}

		$this->set_skip_reasons_arr( $reason_string );
		new IP2OZ_Error_Log( $reason_string );
	}

	/* Getters */

	/**
	 * Get product
	 * 
	 * @return WC_Product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * Get feed ID
	 * 
	 * @return int|string
	 */
	public function get_feed_id() {
		return $this->feed_id;
	}

	/**
	 * Get result
	 * 
	 * @return array
	 */
	public function get_result() {
		return $this->product_data_arr;
	}

	/**
	 * Get product SKU list array
	 * 
	 * @return array
	 */
	public function get_product_sku_list_arr() {
		return $this->product_sku_list_arr;
	}
}