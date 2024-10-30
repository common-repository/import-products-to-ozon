<?php
/**
 * Traits for variable products
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
 *                          methods:    get_product
 *                                      get_offer
 *                                      get_feed_id
 *                                      get_feed_category_id
 *                                      add_skip_reason
 *                          functions:  common_option_get
 *                          constants:  
 */
defined( 'ABSPATH' ) || exit;

trait IP2OZ_T_Common_Skips {
	/**
	 * Get skips array
	 * 
	 * @return array
	 */
	public function get_skips() {
		$skip_flag = false;

		if ( null == $this->get_product() ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'There is no product with this ID', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'trait-ip2oz-t-common-skips.php',
				'line' => __LINE__
			] );
			return [];
		}

		if ( $this->get_product()->is_type( 'grouped' ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'Product is grouped', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'trait-ip2oz-t-common-skips.php',
				'line' => __LINE__ ] );
			return [];
		}

		if ( $this->get_product()->is_type( 'external' ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'Product is External/Affiliate product', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'trait-ip2oz-t-common-skips.php',
				'line' => __LINE__
			] );
			return [];
		}

		if ( $this->get_product()->get_status() !== 'publish' ) {
			$this->add_skip_reason( [ 
				'reason' => sprintf( '%s "%s"',
					__( 'The product status/visibility is', 'import-products-to-ozon' ),
					$this->get_product()->get_status()
				),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'trait-ip2oz-t-common-skips.php',
				'line' => __LINE__
			] );
			return [];
		}

		// что выгружать
		$ip2oz_whot_export = common_option_get( 'whot_export', false, $this->get_feed_id(), 'ip2oz' );
		if ( $this->get_product()->is_type( 'variable' ) ) {
			if ( $ip2oz_whot_export === 'simple' ) {
				$this->add_skip_reason( [ 
					'reason' => __( 'Product is simple', 'import-products-to-ozon' ),
					'post_id' => $this->get_product()->get_id(),
					'file' => 'trait-ip2oz-t-common-skips.php',
					'line' => __LINE__
				] );
				return [];
			}
		}
		if ( $this->get_product()->is_type( 'simple' ) ) {
			if ( $ip2oz_whot_export === 'variable' ) {
				$this->add_skip_reason( [ 
					'reason' => __( 'Product is variable', 'import-products-to-ozon' ),
					'post_id' => $this->get_product()->get_id(),
					'file' => 'trait-ip2oz-t-common-skips.php',
					'line' => __LINE__
				] );
				return [];
			}
		}

		$skip_flag = apply_filters(
			'ip2oz_f_skip_flag',
			$skip_flag,
			[ 
				'product' => $this->get_product(),
				'catid' => $this->get_feed_category_id()
			],
			$this->get_feed_id()
		);
		if ( false !== $skip_flag ) {
			$this->add_skip_reason( [ 
				'reason' => $skip_flag,
				'post_id' => $this->get_product()->get_id(),
				'file' => 'trait-ip2oz-t-common-skips.php',
				'line' => __LINE__
			] );
			return [];
		}

		if ( $this->get_product()->is_type( 'simple' ) ) {
			// пропуск товаров, которых нет в наличии
			$ip2oz_skip_missing_products = common_option_get( 'skip_missing_products', false, $this->get_feed_id(), 'ip2oz' );
			if ( $ip2oz_skip_missing_products == 'enabled' ) {
				if ( false == $this->get_product()->is_in_stock() ) {
					$this->add_skip_reason( [ 
						'reason' => __( 'Skip missing products', 'import-products-to-ozon' ),
						'post_id' => $this->get_product()->get_id(),
						'file' => 'trait-ip2oz-t-common-skips.php',
						'line' => __LINE__
					] );
					return [];
				}
			}

			// пропускаем товары на предзаказ
			$skip_backorders_products = common_option_get( 'skip_backorders_products', false, $this->get_feed_id(), 'ip2oz' );
			if ( $skip_backorders_products == 'enabled' ) {
				if ( true == $this->get_product()->get_manage_stock() ) { // включено управление запасом  
					if ( ( $this->get_product()->get_stock_quantity() < 1 )
						&& ( $this->get_product()->get_backorders() !== 'no' ) ) {
						$this->add_skip_reason( [ 
							'reason' => __( 'Skip backorders products', 'import-products-to-ozon' ),
							'post_id' => $this->get_product()->get_id(),
							'file' => 'trait-ip2oz-t-common-skips.php',
							'line' => __LINE__
						] );
						return [];
					}
				} else {
					if ( $this->get_product()->get_stock_status() !== 'instock' ) {
						$this->add_skip_reason( [ 
							'reason' => __( 'Skip backorders products', 'import-products-to-ozon' ),
							'post_id' => $this->get_product()->get_id(),
							'file' => 'trait-ip2oz-t-common-skips.php',
							'line' => __LINE__
						] );
						return [];
					}
				}
			}
		}

		if ( $this->get_product()->is_type( 'variable' ) ) {
			// пропуск вариаций, которых нет в наличии
			$ip2oz_skip_missing_products = common_option_get( 'skip_missing_products', false, $this->get_feed_id(), 'ip2oz' );
			if ( $ip2oz_skip_missing_products == 'enabled' ) {
				if ( $this->get_offer()->is_in_stock() == false ) {
					$this->add_skip_reason( [ 
						'offer_id' => $this->get_offer()->get_id(),
						'reason' => __( 'Skip missing products', 'import-products-to-ozon' ),
						'post_id' => $this->get_product()->get_id(),
						'file' => 'traits-ip2oz-variable.php',
						'line' => __LINE__
					] );
					return [];
				}
			}

			// пропускаем вариации на предзаказ
			$skip_backorders_products = common_option_get( 'skip_backorders_products', false, $this->get_feed_id(), 'ip2oz' );
			if ( $skip_backorders_products == 'enabled' ) {
				if ( $this->get_offer()->get_manage_stock() == true ) { // включено управление запасом			  
					if ( ( $this->get_offer()->get_stock_quantity() < 1 ) && ( $this->get_offer()->get_backorders() !== 'no' ) ) {
						$this->add_skip_reason( [ 
							'offer_id' => $this->get_offer()->get_id(),
							'reason' => __( 'Skip backorders products', 'import-products-to-ozon' ),
							'post_id' => $this->get_product()->get_id(),
							'file' => 'traits-ip2oz-variable.php',
							'line' => __LINE__
						] );
						return [];
					}
				}
			}

			$skip_flag = apply_filters(
				'ip2oz_f_skip_flag_variable',
				$skip_flag,
				[ 
					'product' => $this->get_product(),
					'offer' => $this->get_offer(),
					'catid' => $this->get_feed_category_id()
				],
				$this->get_feed_id()
			);
			if ( false !== $skip_flag ) {
				$this->add_skip_reason( [ 
					'offer_id' => $this->get_offer()->get_id(),
					'reason' => $skip_flag,
					'post_id' => $this->get_product()->get_id(),
					'file' => 'trait-ip2oz-t-common-skips.php',
					'line' => __LINE__
				] );
				return [];
			}
		}
		return [];
	}
}