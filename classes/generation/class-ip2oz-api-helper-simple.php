<?php
/**
 * The class will help you connect your store to OZON using OZON API
 *
 * @package                 Import Products to OZON
 * @subpackage              
 * @since                   0.1.0
 * 
 * @version                 0.7.3 (05-08-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     	
 *
 * @param     string        $post_id - Required
 *
 * @depends                 classes:    
 *                          traits:     
 *                          methods:    
 *                          functions:  common_option_get
 *                                      common_option_upd
 *                          constants:  
 */
defined( 'ABSPATH' ) || exit;

final class IP2OZ_Api_Helper_Simple {
	use IP2OZ_T_Common_Get_CatId;
	use IP2OZ_T_Common_Skips;

	/**
	 * @var WC_Product
	 */
	protected $product;
	/**
	 * Feed ID
	 * @var string
	 */
	protected $feed_id;
	/**
	 * Result array
	 * @var array
	 */
	protected $result_arr = [];
	/**
	 * Skip reasons array
	 * @var array
	 */
	protected $skip_reasons_arr = [];

	/**
	 * The class will help you connect your store to OZON using OZON API methodes
	 * 
	 * @param WC_Product $product
	 * @param string $actions - It can take values `product_add`, `product_del`
	 * @param string $feed_id - Feed ID
	 * 
	 */
	public function __construct( $product, $actions, $feed_id = '1' ) {
		$this->product = $product;
		$this->feed_id = $feed_id;
		$this->set_category_id();
		$this->get_skips();
		switch ( $actions ) {
			case 'product_add':
				$this->product_add();
				$this->result_arr = apply_filters( 'ip2oz_f_simple_product_add_result_arr',
					$this->result_arr,
					[ 
						'product' => $this->get_product()
					],
					$this->get_feed_id()
				);
				if ( ! empty( $this->get_skip_reasons_arr() ) ) {
					$this->result_arr = [];
				}
				break;
			case 'product_del':
				$this->product_del();
				$this->result_arr = apply_filters( 'ip2oz_f_simple_product_del_result_arr',
					$this->result_arr,
					[ 
						'product' => $this->get_product()
					],
					$this->get_feed_id()
				);
				break;
			case 'product_archive':
				$this->product_archive();
				break;
			case 'product_unarchive':
				$this->product_unarchive();
				break;
			case 'product_stocks':
				$this->product_stocks();
				break;
		}
	}

	/**
	 * Set skip reasons
	 * 
	 * @param string $v
	 * 
	 * @return void
	 */
	public function set_skip_reasons_arr( $v ) {
		$this->skip_reasons_arr[] = $v;
	}

	/**
	 * Get skip reasons
	 * 
	 * @return array
	 */
	public function get_skip_reasons_arr() {
		return $this->skip_reasons_arr;
	}

	/**
	 * Add skip reason
	 * 
	 * @param array $reason
	 * 
	 * @return void
	 */
	protected function add_skip_reason( $reason ) {
		$reason_string = sprintf(
			'FEED № %1$s; Товар с postId = %2$s пропущен. Причина: %3$s; Файл: %4$s; Строка: %5$s',
			$this->feed_id, $reason['post_id'], $reason['reason'], $reason['file'], $reason['line']
		);

		$this->set_skip_reasons_arr( $reason_string );
		new IP2OZ_Error_Log( $reason_string );
	}

	/**
	 * Sets the data for add or updating the product
	 * 
	 * @return void
	 */
	public function product_add() {
		$picture_info_arr = $this->get_picture();
		if ( empty( $picture_info_arr ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'the product does not have a photo', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
			return;
		}

		$description = $this->get_description();
		if ( empty( $description ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'description', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
			return;
		}

		$dimensions_arr = $this->get_dimensions();
		if ( empty( $dimensions_arr ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'dimensions', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
			return;
		}

		if ( empty( get_term_meta( $this->get_feed_category_id(), 'ip2oz_ozon_category_id', true ) ) ) {
			$this->add_skip_reason( [ 
				'reason' => sprintf( '%s id = "%s" %s',
					__( 'For the', 'import-products-to-ozon' ),
					$this->get_feed_category_id(),
					__( 'category, the category ID is not specified on the OZON', 'import-products-to-ozon' )
				),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
			return;
		} else {
			$ozon_category_id = get_term_meta( $this->get_feed_category_id(), 'ip2oz_ozon_category_id', true );
			$ozon_category_id_arr = explode( "--", $ozon_category_id );
			$description_category_id = $ozon_category_id_arr[0];
			$type_id = $ozon_category_id_arr[1];
		}

		if ( empty( $this->get_sku() ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'SKU is empty', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
			return;
		} else {
			$sku = $this->get_sku();
		}

		$this->result_arr = [ 
			'attributes' => $this->get_attributes_arr( $type_id, $description ),
			'name' => $this->get_name(),
			'description_category_id' => (int) $description_category_id, // ! теперь тут категория НЕ верхнего уровня $ozon_category_id,
			'price' => (string) $this->get_price(),
			'offer_id' => $sku,
			// 'oldprice' => (string) $this->get_oldprice(),
			'primary_image' => $picture_info_arr['url'], // string
			'currency_code' => $this->get_currency(), // string
			'depth' => (float) $dimensions_arr['depth'],
			'height' => (float) $dimensions_arr['height'],
			'width' => (float) $dimensions_arr['width'],
			'dimension_unit' => 'mm',
			'weight' => (float) $this->get_weight(), // вес
			'weight_unit' => 'g',
			'vat' => $this->get_vat()
		];

		$barcode = $this->get_barcode();
		if ( ! empty( $barcode ) ) {
			$this->result_arr['barcode'] = $barcode;
		}

		$this->result_arr = apply_filters(
			'ip2oz_f_simple_helper_result_arr',
			$this->result_arr,
			[ 
				'product' => $this->get_product()
			],
			$this->get_feed_id()
		);
	}

	/**
	 * Get barcode
	 * 
	 * @return string
	 */
	public function get_barcode() {
		$tag_value = '';
		$barcode = common_option_get( 'barcode', false, $this->get_feed_id(), 'ip2oz' );
		switch ( $barcode ) {
			// disabled, sku, post_meta, germanized, upc-ean-generator, ean-for-woocommerce, id
			case "disabled": // выгружать штрихкод нет нужды		
				break;
			case "sku": // выгружать из артикула
				$tag_value = $this->get_product()->get_sku();
				break;
			case "post_meta":
				$barcode_post_meta_id = common_option_get( 'barcode_post_meta', false, $this->get_feed_id(), 'ip2oz' );
				$barcode_post_meta_id = trim( $barcode_post_meta_id );
				if ( get_post_meta( $this->get_product()->get_id(), $barcode_post_meta_id, true ) !== '' ) {
					$tag_value = get_post_meta( $this->get_product()->get_id(), $barcode_post_meta_id, true );
				}
				break;
			case "germanized":
				if ( class_exists( 'WooCommerce_Germanized' ) ) {
					if ( get_post_meta( $this->get_product()->get_id(), '_ts_gtin', true ) !== '' ) {
						$tag_value = get_post_meta( $this->get_product()->get_id(), '_ts_gtin', true );
					}
				}
				break;
			case "upc-ean-generator":
				if ( get_post_meta( $this->get_product()->get_id(), 'usbs_barcode_field', true ) !== '' ) {
					$tag_value = get_post_meta( $this->get_product()->get_id(), 'usbs_barcode_field', true );
				}
				break;
			case "ean-for-woocommerce":
				if ( class_exists( 'Alg_WC_EAN' ) ) {
					if ( get_post_meta( $this->get_product()->get_id(), '_alg_ean', true ) !== '' ) {
						$tag_value = get_post_meta( $this->get_product()->get_id(), '_alg_ean', true );
					}
				}
				break;
			default:
				$tag_value = apply_filters(
					'ip2oz_f_simple_tag_value_switch_barcode',
					$tag_value,
					[ 
						'product' => $this->get_product(),
						'switch_value' => $barcode
					],
					$this->get_feed_id()
				);
				if ( $tag_value == '' ) {
					$barcode = (int) $barcode;
					$tag_value = $this->get_product()->get_attribute( wc_attribute_taxonomy_name_by_id( $barcode ) );
				}
		}
		return $tag_value;
	}

	/**
	 * Get attributes array
	 * 
	 * @param string $type_id
	 * @param string $description
	 * 
	 * @return array
	 */
	public function get_attributes_arr( $type_id, $description ) {
		$result_arr = [];

		// Аннотация
		$add_arr = [ 
			'complex_id' => 0,
			'id' => 4191,
			'values' => [ 
				[ 
					'dictionary_value_id' => 0,
					'value' => $description
				]
			]
		];
		array_push( $result_arr, $add_arr );

		// ТН ВЭД
		if ( ! empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_tn_ved_code', true ) ) ) {
			$tn_ved_code = (float) get_post_meta( $this->get_product()->get_id(), '_ip2oz_tn_ved_code', true );
			$add_arr = [ 
				'complex_id' => 0,
				'id' => 22232,
				'values' => [ 
					[ 
						'dictionary_value_id' => 0,
						'value' => $tn_ved_code
					]
				]
			];
			array_push( $result_arr, $add_arr );
		}

		// Партномер
		$partnomer = common_option_get( 'partnomer', false, $this->get_feed_id(), 'ip2oz' );
		if ( $partnomer === 'sku' ) {
			$sku = $this->get_product()->get_sku();
			if ( ! empty( $sku ) ) {
				$add_arr = [ 
					'complex_id' => 0,
					'id' => 7236,
					'values' => [ 
						[ 
							'dictionary_value_id' => 0,
							'value' => $sku
						]
					]
				];
				array_push( $result_arr, $add_arr );
			}
		}

		// ТН ВЭД
		if ( ! empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_tn_ved_code', true ) ) ) {
			$tn_ved_code = (float) get_post_meta( $this->get_product()->get_id(), '_ip2oz_tn_ved_code', true );
			$add_arr = [ 
				'complex_id' => 0,
				'id' => 22232,
				'values' => [ 
					[ 
						'dictionary_value_id' => 0,
						'value' => $tn_ved_code
					]
				]
			];
			array_push( $result_arr, $add_arr );
		}

		// подкатегория
		$add_arr = [ 
			'complex_id' => 0,
			'id' => 8229,
			'values' => [ 
				[ 
					'dictionary_value_id' => (int) $type_id // ! в доках OZON лажа. dictionary_value_id - верно
				]
			]
		];
		array_push( $result_arr, $add_arr );

		// объединение в одну карточку 
		$add_arr = [ 
			'complex_id' => 0,
			'id' => 9048,
			'values' => [ 
				[ 
					'value' => $this->get_name()
				]
			]
		];
		array_push( $result_arr, $add_arr );

		$add_arr = $this->get_other_attributes();
		if ( ! empty( $add_arr ) ) {
			for ( $i = 0; $i < count( $add_arr ); $i++ ) {
				array_push( $result_arr, $add_arr[ $i ] );
			}
		}

		return $result_arr;
	}

	/**
	 * Getting other attributes
	 * 
	 * @return array
	 */
	public function get_other_attributes() {
		$res_arr = [];
		$site_params_arr = $this->get_params();
		// array(1) { [0]=> array(4) { ["id"]=> int(1) ["variation"]=> bool(false) ["name"]=> string(8) "Цвет" ["value"]=> string(36) "Зелёный, Фиолетовый" } }

		$brand_flag = false;
		if ( ! empty( $site_params_arr ) ) {

			for ( $i = 0; $i < count( $site_params_arr ); $i++ ) {
				$attribute_id = $site_params_arr[ $i ]['id'];
				$attribute_slug = wc_attribute_taxonomy_name_by_id( (int) $attribute_id ); // pa_color
				$all_product_attributes_arr = $this->get_product()->get_attributes();
				$term_id = null;
				foreach ( $all_product_attributes_arr as $param ) {
					if ( $param->get_name() == $attribute_slug ) {
						$values_arr = [];
						$term = $param->get_terms();
						foreach ( $term as $t ) {
							$term_id = $t->term_id; // получен ID атрибута
							if ( ! empty( $term_id ) ) {
								$values_arr = $this->get_attribute_values_arr( $term_id, $values_arr );
							}
						}
						if ( isset( $term_id ) && ! empty( $term_id ) ) {
							// имя атрибута
							$ozon_attribute_name_id = esc_attr( get_term_meta( $term_id, 'ip2oz_ozon_attribute_name_id', true ) );
							if ( $ozon_attribute_name_id !== '' && $ozon_attribute_name_id !== 'disabled' ) {
								$ozon_attribute_name_id_arr = explode( "--", $ozon_attribute_name_id );
								$ozon_attribute_name_id = (int) $ozon_attribute_name_id_arr[0];
								$ozon_attribute_name_dictionary_id = (int) $ozon_attribute_name_id_arr[1];
							}
							if ( $ozon_attribute_name_id == 85 ) {
								$brand_flag = true;
							}
						}
						if ( ! empty( $ozon_attribute_name_id ) && ! empty( $values_arr ) ) {
							$res_arr[] = [ 
								'complex_id' => 0,
								'id' => $ozon_attribute_name_id,
								'values' => $values_arr
							];
						}
					}
				}
			}

		}

		if ( false === $brand_flag ) {
			$res_arr[] = [ 
				'complex_id' => 0,
				'id' => 85,
				'values' => [ 
					[ 
						'description_category_id' => 126745801,
						'value' => 'Нет бренда'
					]
				]
			];
		}

		return $res_arr;
	}

	/**
	 * Getting other attributes values array
	 * 
	 * @param int $term_id
	 * @param array $values_arr
	 * 
	 * @return array
	 */

	private function get_attribute_values_arr( $term_id, $values_arr = [] ) {		
		// значение атрибута
		$ozon_attribute_value_id = esc_attr( get_term_meta( $term_id, 'ip2oz_ozon_attribute_value_id', true ) );
		if ( $ozon_attribute_value_id !== '' && $ozon_attribute_value_id !== 'disabled' && $ozon_attribute_value_id !== '' ) {
			if ( strpos( $ozon_attribute_value_id, "--" ) !== false ) {
				$ozon_attribute_value_id_arr = explode( "--", $ozon_attribute_value_id );
				$ozon_attribute_value_id = (int) $ozon_attribute_value_id_arr[0];
				$ozon_attribute_value_value = (string) $ozon_attribute_value_id_arr[1];
			} else {
				$ozon_attribute_value_value = $ozon_attribute_value_id;
				$ozon_attribute_value_id = -1;
			}
		}

		if ( ! empty( $ozon_attribute_value_value ) ) {
			if ( $ozon_attribute_value_id > 0 ) {
				array_push(
					$values_arr,
					[ 
						'description_category_id' => $ozon_attribute_value_id,
						'value' => $ozon_attribute_value_value
					]
				);
			} else {
				array_push( $values_arr, [ 'value' => $ozon_attribute_value_value ] );
			}
		}

		return $values_arr;
	}

	/**
	 * Get params array
	 * 
	 * @return array - structure: [ [ `id` => int, `variation` => bool, `name` => string, `value` => string ], ... ];
	 * `name` and `value` - it can be an empty string
	 */
	public function get_params() {
		$result_arr = [];
		$params_arr = maybe_unserialize( univ_option_get( 'params_arr' . $this->get_feed_id() ) );

		if ( ! empty( $params_arr ) ) {
			$attributes = $this->get_product()->get_attributes();
			foreach ( $attributes as $param ) {
				$param_name = wc_attribute_label( wc_attribute_taxonomy_name_by_id( $param->get_id() ) );

				// проверка на вариативность атрибута не нужна
				$param_val = $this->get_product()->get_attribute( wc_attribute_taxonomy_name_by_id( $param->get_id() ) );

				// если этот параметр не нужно выгружать - пропускаем
				$variation_id_string = (string) $param->get_id(); // важно, т.к. в настройках id как строки
				if ( ! in_array( $variation_id_string, $params_arr, true ) ) {
					continue;
				}

				array_push( $result_arr, [ 
					'id' => $param->get_id(),
					'variation' => false, // это атрибут вариации да/нет
					'name' => $param_name, // * Этот элемент массива оставил для логов
					'value' => htmlspecialchars( $param_val ) // * Этот элемент массива оставил для логов
				] );
			}
		}

		return $result_arr;
	}

	/**
	 * Get shop SKU
	 * 
	 * @return string
	 */
	public function get_sku() {
		$sku_format = common_option_get( 'sku', 'prefix_mode', $this->get_feed_id(), 'ip2oz' );
		switch ( $sku_format ) {
			case "products_id":
				$shop_sku = (string) $this->get_product()->get_id();
				break;
			case "sku":
				$shop_sku = $this->get_product()->get_sku();
				break;
			default:
				$shop_sku = 'offer-' . $this->get_product()->get_id();
		}

		$prefix_shop_sku = common_option_get( 'prefix_shop_sku', false, $this->get_feed_id(), 'ip2oz' );
		if ( ! empty( $prefix_shop_sku ) ) {
			$shop_sku = $prefix_shop_sku . $shop_sku;
		}

		$shop_sku = apply_filters( 'ip2oz_f_simple_shop_sku',
			$shop_sku,
			[ 
				'product' => $this->get_product()
			],
			$this->get_feed_id()
		);

		return $shop_sku;
	}

	/**
	 * Get weight
	 * 
	 * @return float
	 */
	public function get_weight() {
		$tag_value = 0;
		if ( empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_weight', true ) ) ) {
			$weight = $this->get_product()->get_weight();
			if ( ! empty( $weight ) ) {
				$tag_value = round( wc_get_weight( $weight, 'g' ), 3 );
			}
		} else {
			$tag_value = (float) get_post_meta( $this->get_product()->get_id(), '_ip2oz_weight', true );
		}

		return $tag_value;
	}

	/**
	 * Get dimensions array
	 * 
	 * @param array $offer_arr
	 * 
	 * @return array
	 */
	public function get_dimensions() {
		$res_arr = [];
		// $dimensions = wc_format_dimensions( $this->get_product()->get_dimensions( false ) );
		if ( ! empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_length', true ) ) ) {
			$length = (float) get_post_meta( $this->get_product()->get_id(), '_ip2oz_length', true );
		} else {
			$length = (float) 0;
		}
		if ( ! empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_width', true ) ) ) {
			$width = (float) get_post_meta( $this->get_product()->get_id(), '_ip2oz_width', true );
		} else {
			$width = (float) 0;
		}
		if ( ! empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_height', true ) ) ) {
			$height = (float) get_post_meta( $this->get_product()->get_id(), '_ip2oz_height', true );
		} else {
			$height = (float) 0;
		}

		if ( $this->get_product()->has_dimensions() ) {
			if ( empty( $length ) ) {
				$length = $this->get_product()->get_length();
				if ( ! empty( $length ) ) {
					$length = round( wc_get_dimension( $length, 'mm' ), 3 );
				}
			}

			if ( empty( $width ) ) {
				$width = $this->get_product()->get_width();
				if ( ! empty( $width ) ) {
					$width = round( wc_get_dimension( $width, 'mm' ), 3 );
				}
			}

			if ( empty( $height ) ) {
				$height = $this->get_product()->get_height();
				if ( ! empty( $height ) ) {
					$height = round( wc_get_dimension( $height, 'mm' ), 3 );
				}
			}
		}

		if ( ( $length > 0 ) && ( $width > 0 ) && ( $height > 0 ) ) {
			return [ 
				'depth' => $length,
				'width' => $width,
				'height' => $height
			];
		} else {
			return $res_arr;
		}
	}

	/**
	 * Get VAT
	 * 
	 * @return string
	 */
	public function get_vat() {
		if ( empty( get_post_meta( $this->get_product()->get_id(), '_ip2oz_vat', true ) )
			|| get_post_meta( $this->get_product()->get_id(), '_ip2oz_vat', true ) === 'default'
		) {
			$vat = common_option_get( 'vat', '0', $this->get_feed_id(), 'ip2oz' );
		} else {
			$vat = get_post_meta( $this->get_product()->get_id(), '_ip2oz_vat', true );
		}
		return $vat;
	}

	/**
	 * Sets the data for updating the product
	 * 
	 * @return void
	 */
	public function product_upd() {
		return;
	}

	/**
	 * Sets the data for deleting the product
	 * 
	 * @return void
	 */
	public function product_del() {
		return;
	}

	/**
	 * Sets the data for transferring the product to the archive
	 * 
	 * @return void
	 */
	public function product_archive() {
		$product_id_on_ozon = $this->get_product_id_on_ozon();
		if ( null !== $product_id_on_ozon ) {
			array_push( $this->result_arr, $product_id_on_ozon );
		}
	}

	/**
	 * Sets the data for transferring the product from the archive
	 * 
	 * @return void
	 */
	public function product_unarchive() {
		$product_id_on_ozon = $this->get_product_id_on_ozon();
		if ( null !== $product_id_on_ozon ) {
			array_push( $this->result_arr, $product_id_on_ozon );
		}
	}

	/**
	 * Sets the data for updating stocks
	 * 
	 * @return void
	 */
	public function product_stocks() {
		$product_id_on_ozon = $this->get_product_id_on_ozon();
		if ( null == $product_id_on_ozon ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'The product has not synced with OZON yet', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
		}
		if ( empty( $this->get_sku() ) ) {
			$this->add_skip_reason( [ 
				'reason' => __( 'SKU is empty', 'import-products-to-ozon' ),
				'post_id' => $this->get_product()->get_id(),
				'file' => 'class-ip2oz-api-helper-simple.php',
				'line' => __LINE__
			] );
			return;
		} else {
			$sku = $this->get_sku();
		}
		if ( true == $this->get_product()->get_manage_stock() ) { // включено управление запасом  
			$stock = $this->get_product()->get_stock_quantity();
		} else {
			$stock = 0;
		}

		$warehouse_id = common_option_get( 'warehouse_id', false, $this->get_feed_id(), 'ip2oz' );
		$stock_arr = [ 
			'offer_id' => (string) $sku,
			'product_id' => (int) $product_id_on_ozon,
			'stockd' => $stock,
			'warehouse_id' => $warehouse_id
		];

		array_push( $this->result_arr, $stock_arr );
	}

	/**
	 * Get currency
	 * 
	 * @return string
	 */
	public function get_currency() {
		$currency_id_maybe = [ 'RUB', 'USD', 'CNY', 'EUR', 'BYN' ];
		$currency_id_ozon = get_woocommerce_currency();
		if ( ! in_array( $currency_id_ozon, $currency_id_maybe ) ) {
			$currency_id_ozon = 'RUB';
		}
		return $currency_id_ozon;
	}

	/**
	 * Get products prices
	 * 
	 * @return float
	 */
	public function get_price() {
		/**
		 * $product->get_price() - актуальная цена (равна sale_price или regular_price если sale_price пуст)
		 * $product->get_regular_price() - обычная цена
		 * $product->get_sale_price() - цена скидки
		 */
		$price = $this->get_product()->get_price();
		$regular_price = $this->get_product()->get_regular_price();
		$sale_price = $this->get_product()->get_sale_price();

		$sale_price = apply_filters(
			'ip2oz_f_change_sale_price_simple',
			$sale_price,
			[ 'product' => $this->get_product() ],
			$this->get_feed_id()
		);
		$regular_price = apply_filters(
			'ip2oz_f_change_regular_price_simple',
			$regular_price,
			[ 'product' => $this->get_product() ],
			$this->get_feed_id()
		);

		if ( $price > 0 && $price == $sale_price ) { // скидка есть
			$old_price = common_option_get( 'old_price', false, $this->get_feed_id(), 'ip2oz' );
			if ( $old_price === 'enabled' ) {
				return $sale_price;
			} else {
				return $regular_price;
			}
		} else { // скидки нет
			return $regular_price;
		}
	}

	/**
	 * Get product name
	 * 
	 * @return string
	 */
	public function get_name() {
		return $this->get_product()->get_title();
	}

	/**
	 * Get product URL
	 * 
	 * @return string
	 */
	public function get_url() {
		return htmlspecialchars( get_permalink( $this->get_product()->get_id() ) );
	}

	/**
	 * Get the Picture info 
	 * 
	 * @return array
	 */
	public function get_picture() {
		$res_arr = [];
		$thumb_id = get_post_thumbnail_id( $this->get_product()->get_id() );
		if ( ! empty( $thumb_id ) ) { // есть картинка у товара
			$thumb_url = wp_get_attachment_image_src( $thumb_id, 'full', true );
			$res_arr['url'] = $thumb_url[0]; /* урл оригинал миниатюры товара */
			$res_arr['id'] = $thumb_id; /* id миниатюры товара */
		}
		return $res_arr;
	}

	/**
	 * Get product description
	 * 
	 * @return string
	 */
	public function get_description() {
		$description_source = common_option_get( 'description', false, $this->get_feed_id(), 'ip2oz' );
		$desc_val = '';

		switch ( $description_source ) {
			case "full":
				$desc_val = $this->get_product()->get_description();
				break;
			case "excerpt":
				$desc_val = $this->get_product()->get_short_description();
				break;
			case "fullexcerpt":
				$desc_val = $this->get_product()->get_description();
				if ( empty( $desc_val ) ) {
					$desc_val = $this->get_product()->get_short_description();
				}
				break;
			case "excerptfull":
				$desc_val = $this->get_product()->get_short_description();
				if ( empty( $desc_val ) ) {
					$desc_val = $this->get_product()->get_description();
				}
				break;
			case "fullplusexcerpt":
				$desc_val = sprintf( '%1$s<br/>%2$s',
					$this->get_product()->get_description(),
					$this->get_product()->get_short_description()
				);
				break;
			case "excerptplusfull":
				$desc_val = sprintf( '%2$s<br/>%1$s',
					$this->get_product()->get_description(),
					$this->get_product()->get_short_description()
				);
				break;
			default:
				$desc_val = $this->get_product()->get_description();
		}

		// Заменим переносы строк, чтоб не вываливалась ошибка аттача
		$desc_val = str_replace( [ "\r\n", "\r", "\n", PHP_EOL ], '<br/>', $desc_val );
		$desc_val = ip2oz_strip_tags( $desc_val );
		// ? $desc_val = htmlspecialchars( $desc_val );
		return $desc_val;
	}

	/**
	 * Get product ID on OZON
	 * 
	 * @return string
	 */
	public function get_product_id_on_ozon() {
		if ( get_post_meta( $this->get_product()->get_id(), '_ip2oz_prod_id_on_ozon', true ) !== '' ) {
			return (string) get_post_meta( $this->get_product()->get_id(), '_ip2oz_prod_id_on_ozon', true );
		} else {
			return null;
		}
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
	 * @return string
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
		return $this->result_arr;
	}
}