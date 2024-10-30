<?php
/**
 * Traits for different classes
 *
 * @package                 Import Products to OZON
 * @subpackage              
 * @since                   0.1.0
 * 
 * @version                 0.6.1 (19-06-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     
 *
 * @depends                 classes:    
 *                          traits:     
 *                          methods:    
 *                          functions:  
 *                          constants:  
 */
defined( 'ABSPATH' ) || exit;

trait IP2OZ_T_Get_Product {
	/**
	 * WooCommerce product object
	 * @var WC_Product
	 */
	protected $product;

	/**
	 * Get WooCommerce product object
	 * 
	 * @return WC_Product
	 */
	protected function get_product() {
		return $this->product;
	}
}

trait IP2OZ_T_Get_Feed_Id {
	/**
	 * Feed ID
	 * @var string
	 */
	protected $feed_id;

	/**
	 * Get feed ID
	 * 
	 * @return string
	 */
	protected function get_feed_id() {
		return $this->feed_id;
	}
}

trait IP2OZ_T_Get_Post_Id {
	/**
	 * Post ID
	 * @var int|string
	 */
	protected $post_id;

	/**
	 * Get post ID
	 * 
	 * @return int|string
	 */
	protected function get_post_id() {
		return $this->post_id;
	}
}

trait IP2OZ_T_Get_Skip_Reasons_Arr {
	/**
	 * Skip reasons array
	 * @var array
	 */
	protected $skip_reasons_arr = [];

	/**
	 * Set(add) skip reasons
	 *
	 * @param string $v
	 * 
	 * @return void
	 */
	public function set_skip_reasons_arr( $v ) {
		$this->skip_reasons_arr[] = $v;
	}

	/**
	 * Get skip reasons array
	 * 
	 * @return array
	 */
	public function get_skip_reasons_arr() {
		return $this->skip_reasons_arr;
	}
}