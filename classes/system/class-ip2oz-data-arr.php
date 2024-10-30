<?php
/**
 * Set and Get the Plugin Data
 *
 * @package                 iCopyDoc Plugins (v1.1, core 22-04-2024)
 * @subpackage              Import Products to OZON
 * @since                   0.1.0
 * 
 * @version                 0.7.3 (05-08-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     
 * 
 * @param       array       $data_arr - Optional
 *
 * @depends                 classes:	
 *                          traits:		
 *                          methods:	
 *                          functions:	
 *                          constants:	
 */
defined( 'ABSPATH' ) || exit;

class IP2OZ_Data_Arr {
	/**
	 * Plugin options array
	 * @var array
	 */
	private $data_arr = [];

	/**
	 * Set and Get the Plugin Data
	 * 
	 * @param array $data_arr - Optional
	 */
	public function __construct( $data_arr = [] ) {
		if ( empty( $data_arr ) ) {
			$this->data_arr = [ 
				[ 
					'opt_name' => 'status_sborki',
					'def_val' => '-1',
					'mark' => 'private',
					'required' => true,
					'type' => 'auto',
					'tab' => 'none'
				],
				[ // дата начала сборки
					'opt_name' => 'date_sborki',
					'def_val' =>
						'0000000001',
					'mark' => 'private',
					'required' => true,
					'type' => 'auto',
					'tab' => 'none'
				],
				[ // дата завершения сборки
					'opt_name' => 'date_sborki_end',
					'def_val' => '0000000001',
					'mark' => 'private',
					'required' => true,
					'type' => 'auto',
					'tab' => 'none'
				],
				[ // дата сохранения настроек плагина
					'opt_name' => 'date_save_set',
					'def_val' => '0000000001',
					'mark' => 'private',
					'required' => true,
					'type' => 'auto',
					'tab' => 'none'
				],
				[ // число товаров, попавших в выгрузку
					'opt_name' => 'count_products_in_feed',
					'def_val' => '-1',
					'mark' => 'private',
					'required' => true,
					'type' => 'auto',
					'tab' => 'none'
				],
				[ 
					'opt_name' => 'status_cron',
					'def_val' => 'off',
					'mark' => 'private',
					'required' => true,
					'type' => 'auto',
					'tab' => 'none'
				],
				/* ---------- API SETTINGS ---------- */
				[ 
					'opt_name' => 'client_id',
					'def_val' => '',
					'mark' => 'public',
					'required' => true,
					'type' => 'text',
					'tab' => 'api_tab',
					'data' => [ 
						'label' => __( 'Client-Id', 'import-products-to-ozon' ),
						'desc' => 'Client-Id - ' . __( 'from the site OZON', 'import-products-to-ozon' ),
						'placeholder' => '654321'
					]
				],
				[ 
					'opt_name' => 'api_key',
					'def_val' => '',
					'mark' => 'public',
					'required' => true,
					'type' => 'text',
					'tab' => 'api_tab',
					'data' => [ 
						'label' => __( 'Api-Key', 'import-products-to-ozon' ),
						'desc' => 'Api-Key - ' . __( 'from the site OZON', 'import-products-to-ozon' ),
						'placeholder' => 'c9a*******************e9d'
					]
				],
				/* ---------- MAIN SETTINGS ---------- */
				[ 
					'opt_name' => 'syncing_with_ozon',
					'def_val' => 'disabled',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Syncing with OZON', 'import-products-to-ozon' ),
						'desc' => __(
							'Using this parameter, you can stop the plugin completely',
							'import-products-to-ozon'
						),
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 'value' => 'enabled', 'text' => __( 'Enabled', 'import-products-to-ozon' ) ]
						]
					]
				],
				[ 
					'opt_name' => 'run_cron',
					'def_val' => 'disabled',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __(
							'The frequency of full synchronization of products',
							'import-products-to-ozon'
						),
						'desc' => __(
							'With the specified frequency, the plugin will transmit information about all your products to OZON',
							'import-products-to-ozon'
						),
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 'value' => 'hourly', 'text' => __( 'Hourly', 'import-products-to-ozon' ) ],
							[ 'value' => 'three_hours', 'text' => __( 'Every three hours', 'import-products-to-ozon' ) ],
							[ 'value' => 'six_hours', 'text' => __( 'Every six hours', 'import-products-to-ozon' ) ],
							[ 'value' => 'twicedaily', 'text' => __( 'Twice a day', 'import-products-to-ozon' ) ],
							[ 'value' => 'daily', 'text' => __( 'Daily', 'import-products-to-ozon' ) ],
							[ 'value' => 'week', 'text' => __( 'Once a week', 'import-products-to-ozon' ) ]
						]
					]
				],
				[ 
					'opt_name' => 'step_export',
					'def_val' => '500',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Step export', 'import-products-to-ozon' ),
						'desc' => __(
							'Determines the maximum number of products uploaded to OZON in one minute',
							'import-products-to-ozon'
						),
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => '50', 'text' => '50' ],
							[ 'value' => '100', 'text' => '100' ],
							[ 'value' => '200', 'text' => '200' ],
							[ 'value' => '300', 'text' => '300' ],
							[ 'value' => '400', 'text' => '400' ],
							[ 
								'value' => '500',
								'text' => '500 (' . __( 'The maximum value allowed by OZON', 'import-products-to-ozon' ) . ')'
							]
						]
					]
				],
				[ 
					'opt_name' => 'vat',
					'def_val' => '0',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'VAT rate', 'import-products-to-ozon' ),
						'desc' => sprintf( '%s. %s',
							__( 'By default for all your products', 'import-products-to-ozon' ),
							__(
								'If necessary, you can change the tax inside the product card',
								'import-products-to-ozon'
							)
						),
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 
								'value' => '0',
								'text' => __( 'Not taxed', 'import-products-to-ozon' )
							],
							[ 
								'value' => '0.1',
								'text' => '10%'
							],
							[ 
								'value' => '0.2',
								'text' => '20%'
							]
						],
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'prefix_shop_sku',
					'def_val' => '',
					'mark' => 'public',
					'required' => true,
					'type' => 'text',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Prefix for product SKU', 'import-products-to-ozon' ),
						'desc' => __(
							'Since you cannot change the ID of previously uploaded products on OZON, this option may be useful at the debugging stage',
							'import-products-to-ozon'
						),
						'placeholder' => 'test-',
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'sku',
					'def_val' => 'disabled',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'SKU format', 'import-products-to-ozon' ),
						'desc' => __( 'The source of the SKU of the product', 'import-products-to-ozon' ),
						'woo_attr' => false,
						'default_value' => false,
						'key_value_arr' => [ 
							[ 
								'value' => 'prefix_mode',
								'text' => sprintf( 'offer-XXX -- (*XXX - %s)',
									__( 'product ID', 'import-products-to-ozon' )
								)
							],
							[ 
								'value' => 'products_id',
								'text' => sprintf( 'XXX -- (**XXX - %s)',
									__( 'Add from product ID', 'import-products-to-ozon' )
								)
							],
							[ 
								'value' => 'sku',
								'text' => sprintf( 'XXX -- (***XXX - %s)',
									__( 'Substitute from SKU', 'import-products-to-ozon' )
								)
							]
						]
					]
				],
				[ 
					'opt_name' => 'description',
					'def_val' => 'fullexcerpt',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Description of the product', 'import-products-to-ozon' ),
						'desc' => '[description] - ' . __( 'The source of the description', 'import-products-to-ozon' ),
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 
								'value' => 'excerpt',
								'text' => __( 'Only Excerpt description', 'import-products-to-ozon' )
							],
							[ 
								'value' => 'full',
								'text' => __( 'Only Full description', 'import-products-to-ozon' )
							],
							[ 
								'value' => 'excerptfull',
								'text' => __( 'Excerpt or Full description', 'import-products-to-ozon' )
							],
							[ 
								'value' => 'fullexcerpt',
								'text' => __( 'Full or Excerpt description', 'import-products-to-ozon' )
							],
							[ 
								'value' => 'excerptplusfull',
								'text' => __( 'Excerpt plus Full description', 'import-products-to-ozon' )
							],
							[ 
								'value' => 'fullplusexcerpt',
								'text' => __( 'Full plus Excerpt description', 'import-products-to-ozon' )
							]
						],
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'var_desc_priority',
					'def_val' => 'enabled',
					'mark' => 'public',
					'required' => false,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __(
							'The varition description takes precedence over others',
							'import-products-to-ozon'
						),
						'desc' => '',
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 'value' => 'enabled', 'text' => __( 'Enabled', 'import-products-to-ozon' ) ]
						]
					]
				],
				[ 
					'opt_name' => 'params_arr',
					'def_val' => '',
					'mark' => 'public',
					'required' => true,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Include these attributes in the import', 'import-products-to-ozon' ),
						'desc' => sprintf( '%s: %s. %s',
							__( 'Hint', 'import-products-to-ozon' ),
							__(
								'To select multiple values, hold down the (ctrl) button on Windows or (cmd) on a Mac',
								'import-products-to-ozon'
							),
							__(
								'To deselect, press and hold (ctrl) or (cmd), click on the marked items',
								'import-products-to-ozon'
							)
						),
						'woo_attr' => true,
						'default_value' => false,
						'key_value_arr' => [],
						'multiple' => true,
						'size' => '8',
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'partnomer',
					'def_val' => 'enabled',
					'mark' => 'public',
					'required' => false,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Part number', 'import-products-to-ozon' ),
						'desc' => '',
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 
								'value' => 'sku',
								'text' => sprintf( '%s',
									__( 'Substitute from SKU', 'import-products-to-ozon' )
								)
							]
						]
					]
				],
				[ 
					'opt_name' => 'barcode',
					'def_val' => 'enabled',
					'mark' => 'public',
					'required' => false,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Barcode', 'import-products-to-ozon' ),
						'desc' => '',
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 
								'value' => 'sku',
								'text' => sprintf( '%s',
									__( 'Substitute from SKU', 'import-products-to-ozon' )
								)
							],
							[ 'value' => 'post_meta', 'text' => __( 'Substitute from post meta', 'import-products-to-ozon' ) ],
							[ 
								'value' => 'upc-ean-generator',
								'text' => sprintf( '%s UPC/EAN/GTIN Code Generator',
									__( 'Substitute from the plugin', 'import-products-to-ozon' )
								)
							],
							[ 
								'value' => 'ean-for-woocommerce',
								'text' => sprintf( '%s EAN for WooCommerce',
									__( 'Substitute from the plugin', 'import-products-to-ozon' )
								)
							],
							[ 
								'value' => 'germanized',
								'text' => sprintf( '%s WooCommerce Germanized',
									__( 'Substitute from the plugin', 'import-products-to-ozon' )
								)
							]
						],
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'barcode_post_meta',
					'def_val' => '',
					'mark' => 'public',
					'required' => true,
					'type' => 'text',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => '',
						'desc' => '',
						'placeholder' => __( 'Name post_meta', 'import-products-to-ozon' )
					]
				],
				[ 
					'opt_name' => 'warehouse_id',
					'def_val' => '',
					'mark' => 'public',
					'required' => true,
					'type' => 'text',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Warehouse ID', 'import-products-to-ozon' ),
						'desc' => __(
							'Warehouse ID',
							'import-products-to-ozon'
						),
						'placeholder' => '1234567898765432', 
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'old_price',
					'def_val' => 'disabled',
					'mark' => 'public',
					'required' => false,
					'type' => 'select',
					'tab' => 'main_tab',
					'data' => [ 
						'label' => __( 'Old price', 'import-products-to-ozon' ),
						'desc' => __(
							'In oldprice indicates the old price of the goods, which must necessarily be higher than the new price (price)',
							'import-products-to-ozon'
						),
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 'value' => 'enabled', 'text' => __( 'Enabled', 'import-products-to-ozon' ) ]
						],
						'tr_class' => 'ip2oz_tr'
					]
				],
				[ 
					'opt_name' => 'skip_missing_products',
					'def_val' => 'disabled',
					'mark' => 'public',
					'required' => false,
					'type' => 'select',
					'tab' => 'filtration_tab',
					'data' => [ 
						'label' => sprintf( '%1$s (%2$s)',
							__( 'Skip missing products', 'import-products-to-ozon' ),
							__( 'except for products for which a pre-order is permitted', 'import-products-to-ozon' )
						),
						'desc' => '',
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 'value' => 'enabled', 'text' => __( 'Enabled', 'import-products-to-ozon' ) ]
						]
					]
				],
				[ 
					'opt_name' => 'skip_backorders_products',
					'def_val' => 'disabled',
					'mark' => 'public',
					'required' => false,
					'type' => 'select',
					'tab' => 'filtration_tab',
					'data' => [ 
						'label' => __( 'Skip backorders products', 'import-products-to-ozon' ),
						'desc' => '',
						'woo_attr' => false,
						'key_value_arr' => [ 
							[ 'value' => 'disabled', 'text' => __( 'Disabled', 'import-products-to-ozon' ) ],
							[ 'value' => 'enabled', 'text' => __( 'Enabled', 'import-products-to-ozon' ) ]
						]
					]
				]
			];
		} else {
			$this->data_arr = $data_arr;
		}

		$this->data_arr = apply_filters( 'ip2oz_f_set_default_feed_settings_result_arr', $this->get_data_arr() );
	}

	/**
	 * Get the plugin data array
	 * 
	 * @return array
	 */
	public function get_data_arr() {
		return $this->data_arr;
	}

	/**
	 * Get data for tabs
	 * 
	 * @param string $whot
	 * 
	 * @return array	Example: array([0] => opt_key1, [1] => opt_key2, ...)
	 */
	public function get_data_for_tabs( $whot = '' ) {
		$res_arr = [];
		if ( ! empty( $this->get_data_arr() ) ) {
			// echo get_array_as_string($this->get_data_arr(), '<br/>');
			for ( $i = 0; $i < count( $this->get_data_arr() ); $i++ ) {
				switch ( $whot ) {
					case "main_tab":
					case "filtration_tab":
						if ( $this->get_data_arr()[ $i ]['tab'] === $whot ) {
							$arr = $this->get_data_arr()[ $i ]['data'];
							$arr['opt_name'] = $this->get_data_arr()[ $i ]['opt_name'];
							$arr['tab'] = $this->get_data_arr()[ $i ]['tab'];
							$arr['type'] = $this->get_data_arr()[ $i ]['type'];
							$res_arr[] = $arr;
						}
						break;
					case "api_tab":
						if ( $this->get_data_arr()[ $i ]['tab'] === 'api_tab' ) {
							$arr = $this->get_data_arr()[ $i ]['data'];
							$arr['opt_name'] = $this->get_data_arr()[ $i ]['opt_name'];
							$arr['tab'] = $this->get_data_arr()[ $i ]['tab'];
							$arr['type'] = $this->get_data_arr()[ $i ]['type'];
							$res_arr[] = $arr;
						}
						break;
					default:
						if ( $this->get_data_arr()[ $i ]['tab'] === $whot ) {
							$arr = $this->get_data_arr()[ $i ]['data'];
							$arr['opt_name'] = $this->get_data_arr()[ $i ]['opt_name'];
							$arr['tab'] = $this->get_data_arr()[ $i ]['tab'];
							$arr['type'] = $this->get_data_arr()[ $i ]['type'];
							$res_arr[] = $arr;
						}
				}
			}
			// echo get_array_as_string($res_arr, '<br/>');
			return $res_arr;
		} else {
			return $res_arr;
		}
	}

	/**
	 * Get plugin options name
	 * 
	 * @param string $whot
	 * 
	 * @return array	Example: array([0] => opt_key1, [1] => opt_key2, ...)
	 */
	public function get_opts_name( $whot = '' ) {
		$res_arr = [];
		if ( ! empty( $this->get_data_arr() ) ) {
			for ( $i = 0; $i < count( $this->get_data_arr() ); $i++ ) {
				switch ( $whot ) {
					case "public":
						if ( $this->get_data_arr()[ $i ]['mark'] === 'public' ) {
							$res_arr[] = $this->get_data_arr()[ $i ]['opt_name'];
						}
						break;
					case "private":
						if ( $this->get_data_arr()[ $i ]['mark'] === 'private' ) {
							$res_arr[] = $this->get_data_arr()[ $i ]['opt_name'];
						}
						break;
					default:
						$res_arr[] = $this->get_data_arr()[ $i ]['opt_name'];
				}
			}
			return $res_arr;
		} else {
			return $res_arr;
		}
	}

	/**
	 * Get plugin options name and default date (array)
	 * 
	 * @param string $whot
	 * 
	 * @return array	Example: array(opt_name1 => opt_val1, opt_name2 => opt_val2, ...)
	 */
	public function get_opts_name_and_def_date( $whot = 'all' ) {
		$res_arr = [];
		if ( ! empty( $this->get_data_arr() ) ) {
			for ( $i = 0; $i < count( $this->get_data_arr() ); $i++ ) {
				switch ( $whot ) {
					case "public":
						if ( $this->get_data_arr()[ $i ]['mark'] === 'public' ) {
							$res_arr[ $this->get_data_arr()[ $i ]['opt_name'] ] = $this->get_data_arr()[ $i ]['def_val'];
						}
						break;
					case "private":
						if ( $this->get_data_arr()[ $i ]['mark'] === 'private' ) {
							$res_arr[ $this->get_data_arr()[ $i ]['opt_name'] ] = $this->get_data_arr()[ $i ]['def_val'];
						}
						break;
					default:
						$res_arr[ $this->get_data_arr()[ $i ]['opt_name'] ] = $this->get_data_arr()[ $i ]['def_val'];
				}
			}
			return $res_arr;
		} else {
			return $res_arr;
		}
	}

	/**
	 * Get plugin options name and default date (stdClass object)
	 * 
	 * @param string $whot
	 * 
	 * @return array<stdClass>
	 */
	public function get_opts_name_and_def_date_obj( $whot = 'all' ) {
		$source_arr = $this->get_opts_name_and_def_date( $whot );

		$res_arr = [];
		foreach ( $source_arr as $key => $value ) {
			$obj = new stdClass();
			$obj->name = $key;
			$obj->opt_def_value = $value;
			$res_arr[] = $obj; // unit obj
			unset( $obj );
		}
		return $res_arr;
	}
}