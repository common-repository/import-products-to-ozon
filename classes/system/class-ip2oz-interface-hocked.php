<?php
/**
 * This class is responsible for the plugin interface Import Products to OZON
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
 * @param         
 *
 * @depends                 classes:    IP2OZ_Error_Log
 *                                      IP2OZ_Api
 *                          traits:     
 *                          methods:    
 *                          functions:  common_option_get
 *                          constants:  
 *                          options:    
 */
defined( 'ABSPATH' ) || exit;

final class IP2OZ_Interface_Hoocked {
	/**
	 * This class is responsible for the plugin interface Import Products to OZON
	 */
	public function __construct() {
		$this->init_hooks();
		$this->init_classes();
	}

	/**
	 * Initialization hooks
	 * 
	 * @return void
	 */
	public function init_hooks() {
		// Мета-поля для категорий товаров
		add_action( "product_cat_edit_form_fields", [ $this, 'add_meta_product_cat' ], 10, 1 );
		add_action( 'edited_product_cat', [ $this, 'save_meta_product_cat' ], 10, 1 );
		add_action( 'create_product_cat', [ $this, 'save_meta_product_cat' ], 10, 1 );
		add_action( 'admin_init', function () {
			$attributes_names = get_object_taxonomies( [ 'product' ], $output = 'names' );
			foreach ( $attributes_names as $key => $value ) {
				if ( strpos( $value, 'pa_' ) === 0 ) {
					$hook_name = $value . '_edit_form_fields';
					add_action( $hook_name, [ $this, 'add_meta_pa_cat' ], 10, 1 );

					$hook_name = 'edited_' . $value;
					add_action( $hook_name, [ $this, 'save_meta_product_cat' ], 10, 1 );

					$hook_name = 'create_' . $value;
					add_action( $hook_name, [ $this, 'save_meta_product_cat' ], 10, 1 );
				}
			}
		}, 9999 );

		add_action( 'edit_form_after_title', [ $this, 'output_url_imported_product' ], 10, 1 );
		add_action( 'save_post', [ $this, 'save_post_product' ], 50, 3 );
		add_action( 'woocommerce_product_duplicate', [ $this, 'product_duplicate' ], 50, 3 );

		// https://wpruse.ru/woocommerce/custom-fields-in-products/
		// https://wpruse.ru/woocommerce/custom-fields-in-variations/
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'added_wc_tabs' ], 10, 1 );
		add_action( 'woocommerce_product_data_panels', [ $this, 'added_tabs_panel_view' ], 10, 1 );

		add_filter( 'ip2oz_f_save_if_empty', [ $this, 'flag_save_if_empty' ], 10, 2 );

		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_to_product_sync_info' ], 99, 1 );
		add_action( 'woocommerce_variation_options', [ $this, 'add_to_product_variation_sync_info' ], 99, 3 );
	}

	/**
	 * Initialization classes
	 * 
	 * @return void
	 */
	public function init_classes() {
		return;
	}

	/**
	 * Позволяет добавить дополнительные поля на страницу редактирования элементов таксономии (термина).
	 * Function for `(taxonomy)_edit_form_fields` action-hook.
	 * 
	 * @param WP_Term $tag Current taxonomy term object.
	 * @param string $taxonomy Current taxonomy slug.
	 *
	 * @return void
	 */
	public function add_meta_product_cat( $term ) {
		$this->print_tr_tags_ozon_category( $term );
	}

	/**
	 * Сохранение данных в БД. Function for `create_(taxonomy)` and `edited_(taxonomy)` action-hooks.
	 * 
	 * @param int $term_id
	 * 
	 * @return void
	 */
	public function save_meta_product_cat( $term_id ) {
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}
		if (
			! wp_verify_nonce( $_POST['_wpnonce_add-tag'], "add-tag" ) // wp_nonce_field('add-tag', '_wpnonce_add-tag');
			&& ! wp_verify_nonce( $_POST['_wpnonce'], "update-tag_$term_id" ) // wp_nonce_field('update-tag_' . $tag_ID );
		) {
			return;
		}

		$ip2oz_cat_meta = array_map( 'sanitize_text_field', $_POST['ip2oz_cat_meta'] );
		foreach ( $ip2oz_cat_meta as $key => $value ) {
			if ( empty( $value ) ) {
				delete_term_meta( $term_id, $key );
				continue;
			}
			update_term_meta( $term_id, $key, sanitize_text_field( $value ) );
		}
		return;
	}

	/**
	 * 
	 * @param WP_Term $tag Current taxonomy term object.
	 *
	 * @return void
	 */
	public function add_meta_pa_cat( $term ) {
		// категория
		$this->print_tr_tags_ozon_category( $term );

		// имя атрибута (имя характеристики) на OZON
		if ( ! empty( get_term_meta( $term->term_id, 'ip2oz_ozon_category_id', true ) ) ) {
			$ozon_category_id = get_term_meta( $term->term_id, 'ip2oz_ozon_category_id', true );
			$ozon_category_id_arr = explode( "--", $ozon_category_id );
			$description_category_id = $ozon_category_id_arr[0];
			$type_id = $ozon_category_id_arr[1];

			$this->print_tr_tags_ozon_attribute_name( $term, $description_category_id, $type_id );
		}

		// значение атрибута (значение характеристики) на OZON
		$ip2oz_ozon_attribute_name_id = get_term_meta( $term->term_id, 'ip2oz_ozon_attribute_name_id', true );
		if ( ! empty( $ip2oz_ozon_attribute_name_id ) ) {
			$ozon_attribute_name_id_arr = explode( "--", $ip2oz_ozon_attribute_name_id );
			$ozon_id = $ozon_attribute_name_id_arr[0];
			$dictionary_id = $ozon_attribute_name_id_arr[1];
			if ( ! empty( $ozon_id ) ) {
				$this->print_tr_tags_ozon_attribute_value( $term, $ozon_id, $dictionary_id, $description_category_id, $type_id );
			}
		}
	}

	/**
	 * Print HTML `<tr>...</tr>` tags of OZON category
	 * 
	 * @param WP_Term $tag Current taxonomy term object.
	 *
	 * @return void
	 */
	public function print_tr_tags_ozon_category( $term ) {
		$obj = new IP2OZ_Api();
		$result = $obj->get_ozon_categories();

		if ( true == $result['status'] ) {
			$ozon_category_id = esc_attr( get_term_meta( $term->term_id, 'ip2oz_ozon_category_id', true ) );
			printf( '<tr class="form-field term-parent-wrap"><th scope="row" valign="top"><label>%1$s</label></th>
			<td>
				<select name="ip2oz_cat_meta[ip2oz_ozon_category_id]" id="ip2oz_ozon_category_id">
					<option %2$s disabled="disabled" value="">%3$s!</option>%4$s
				</select>
				<p class="description">%5$s <strong>type_id</strong>, <strong>description_category_id</strong></p>
			</td></tr>',
				esc_html__( 'Category OZON', 'import-products-to-ozon' ),
				esc_attr( selected( $ozon_category_id, '', false ) ),
				esc_html__( 'You must select', 'import-products-to-ozon' ),
				$this->get_option_tags_ozon_categories( $result['categories_arr'], $ozon_category_id ),
				esc_html__( 'Required element', 'import-products-to-ozon' )
			);
		}
	}

	/**
	 * Get HTML `<option>...</option>` tags of OZON categories
	 * (processing of json data obtained using the method `https://api-seller.ozon.ru/v1/description-category/tree`)
	 * @see https://docs.ozon.ru/api/seller/
	 * 
	 * @param array $ozon_cat_arr
	 * @param string $ozon_category_id
	 * @param string $sep - Optional
	 * @param int $description_category_id - Optional
	 * @param string $parent_name - Optional
	 * @param string $result_html - Optional
	 * 
	 * @return string
	 */
	public function get_option_tags_ozon_categories( $ozon_cat_arr, $ozon_category_id, $sep = '', $description_category_id = -1, $parent_name = '', $result_html = '' ) {
		for ( $i = 0; $i < count( $ozon_cat_arr ); $i++ ) {
			if ( empty( $ozon_cat_arr[ $i ]->children ) ) {
				$result_html .= sprintf(
					'<option value="%1$s--%2$s" %3$s>%4$s (type_id = %5$s; description_category_id = %6$s)</option>' . PHP_EOL,
					esc_attr( $description_category_id ),
					esc_attr( $ozon_cat_arr[ $i ]->type_id ),
					esc_attr(
						selected( $ozon_category_id, $description_category_id . '--' . $ozon_cat_arr[ $i ]->type_id, false )
					),
					esc_html( $sep . $parent_name . '-' . $ozon_cat_arr[ $i ]->type_name ),
					esc_html( $ozon_cat_arr[ $i ]->type_id ),
					esc_html( $description_category_id )
				);
			} else {
				$result_html .= sprintf( '<option disabled value="%1$s"><strong>%2$s</strong></option>%3$s' . PHP_EOL,
					esc_attr( $ozon_cat_arr[ $i ]->description_category_id ),
					esc_html( $sep . $ozon_cat_arr[ $i ]->category_name ),
					$this->get_option_tags_ozon_categories( $ozon_cat_arr[ $i ]->children, $ozon_category_id, $sep . '-', $ozon_cat_arr[ $i ]->description_category_id, $ozon_cat_arr[ $i ]->category_name )
				);
			}
		}
		return $result_html;
	}

	/**
	 * Print HTML `<tr>...</tr>` tags of OZON ozon attribute (сharacteristic) name
	 * 
	 * @param WP_Term $tag Current taxonomy term object.
	 *
	 * @return void
	 */
	public function print_tr_tags_ozon_attribute_name( $term, $description_category_id, $type_id ) {
		$obj = new IP2OZ_Api();
		$result = $obj->get_ozon_attribute_name_list_by_category( $description_category_id, $type_id );
		if ( true == $result['status'] ) {
			$ip2oz_ozon_attribute_name_id = esc_attr( get_term_meta( $term->term_id, 'ip2oz_ozon_attribute_name_id', true ) );
			printf( '<tr class="form-field term-parent-wrap"><th scope="row" valign="top"><label>%1$s</label></th>
				<td>
					<select name="ip2oz_cat_meta[ip2oz_ozon_attribute_name_id]" id="ip2oz_ozon_attribute_name_id">
						<option %2$s disabled="disabled" value="">%3$s!</option>%4$s
					</select>
					<p class="description">%5$s <strong>id</strong>, <strong>dictionary_id</strong></p>
				</td></tr>',
				esc_html__( 'The name of the attribute on OZON', 'import-products-to-ozon' ),
				esc_attr( selected( $ip2oz_ozon_attribute_name_id, '', false ) ),
				esc_html__( 'You must select', 'import-products-to-ozon' ),
				$this->get_option_tags_ozon_attribute_name( $result['attribute_arr'], $ip2oz_ozon_attribute_name_id ),
				esc_html__( 'Required element', 'import-products-to-ozon' )
			);
		}
	}

	/**
	 * Get HTML `<option>...</option>` tags of OZON attribute (сharacteristic) names 
	 * (processing of json data obtained using the method `https://api-seller.ozon.ru/v1/description-category/attribute`)
	 * @see https://docs.ozon.ru/api/seller/
	 * 
	 * @param array $ozon_cat_arr
	 * @param string $ip2oz_ozon_attribute_name_id
	 * @param string $sep - Optional
	 * @param string $result_html - Optional
	 * 
	 * @return string
	 */
	public function get_option_tags_ozon_attribute_name( $ozon_cat_arr, $ozon_characteristic, $sep = '', $result_html = '' ) {
		for ( $i = 0; $i < count( $ozon_cat_arr ); $i++ ) {
			if ( 8292 === $ozon_cat_arr[ $i ]->id ) {
				// пропускаем объединение в одной карточке
				continue;
			}
			if ( true === $ozon_cat_arr[ $i ]->is_required ) {
				$required_mark = '*';
			} else {
				$required_mark = '';
			}
			$x = $ozon_cat_arr[ $i ]->id . '--' . $ozon_cat_arr[ $i ]->dictionary_id;
			$result_html .= sprintf( '<option value="%1$s--%2$s"%3$s>%4$s (id = %1$s)%5$s</option>' . PHP_EOL,
				esc_attr( $ozon_cat_arr[ $i ]->id ),
				esc_attr( $ozon_cat_arr[ $i ]->dictionary_id ),
				esc_attr( selected( $ozon_characteristic, $x, false ) ),
				esc_html( $ozon_cat_arr[ $i ]->name ),
				esc_html( $required_mark )
			);
		}
		return $result_html;
	}

	/**
	 * Print HTML `<tr>...</tr>` tags of OZON ozon attribute (сharacteristic) value
	 * 
	 * @param WP_Term $tag Current taxonomy term object.
	 * @param string $ozon_id
	 * @param string $dictionary_id
	 * @param string $description_category_id
	 * @param string $type_id
	 *
	 * @return void
	 */
	public function print_tr_tags_ozon_attribute_value( $term, $ozon_id, $dictionary_id, $description_category_id, $type_id ) {
		if ( '0' == $dictionary_id ) {
			$ozon_attribute_value_id = esc_attr( get_term_meta( $term->term_id, 'ip2oz_ozon_attribute_value_id', true ) );

			printf( '<tr class="form-field term-parent-wrap"><th scope="row" valign="top"><label>%1$s</label></th><td>
				<input type="text" name="ip2oz_cat_meta[ip2oz_ozon_attribute_value_id]" value="%2$s" placeholder="%4$s">
				<p class="description">%3$s. %4$s</p></td></tr>',
				esc_html__( 'The value of the attribute on OZON', 'import-products-to-ozon' ),
				esc_attr( $ozon_attribute_value_id ),
				esc_html__( 'Required element', 'import-products-to-ozon' ),
				esc_html__( 'Enter a value in this field', 'import-products-to-ozon' )
			);
		} else {
			$attribute_arr = $this->ozon_attribute_value_list_helper( $ozon_id, $description_category_id, $type_id );
			if ( ! empty( $attribute_arr ) ) {
				$ozon_attribute_value_id = esc_attr( get_term_meta( $term->term_id, 'ip2oz_ozon_attribute_value_id', true ) );

				if ( '85' == $ozon_id ) {
					$no_brand = sprintf( '<option value="%1$s--%2$s" %3$s>%2$s (ozon_id = %1$s)</option>',
						'126745801',
						'Нет бренда',
						esc_attr( selected( $ozon_attribute_value_id, '126745801--Нет бренда', false ) )
					);
				} else {
					$no_brand = '';
				}

				printf( '<tr class="form-field term-parent-wrap"><th scope="row" valign="top"><label>%1$s</label></th><td>
					<select name="ip2oz_cat_meta[ip2oz_ozon_attribute_value_id]" id="ip2oz_ozon_attribute_value_id">
						<option value="" %2$s>%3$s!</option> %4$s %5$s
					</select><p class="description">%6$s <strong>id</strong>, <strong>value</strong></p></td></tr>',
					esc_html__( 'The value of the attribute on OZON', 'import-products-to-ozon' ),
					esc_attr( selected( $ozon_attribute_value_id, '', false ) ),
					esc_html__( 'You must select', 'import-products-to-ozon' ),
					$no_brand,
					$this->get_option_tags_ozon_attribute_value( $attribute_arr, $ozon_attribute_value_id ),
					esc_html__( 'Required element', 'import-products-to-ozon' ),
				);
			}
		}
	}

	/**
	 * Restarts the function `get_ozon_attribute_value_list` if `has_next` === `true`
	 * 
	 * @param string $ozon_id
	 * @param string $description_category_id
	 * @param string $type_id
	 * @param int $last_value_id
	 * @param array $attribute_arr
	 * 
	 * @return array
	 */
	private function ozon_attribute_value_list_helper( $ozon_id, $description_category_id, $type_id, $last_value_id = 0, $attribute_arr = [] ) {
		$obj = new IP2OZ_Api();
		$result = $obj->get_ozon_attribute_value_list( $ozon_id, $description_category_id, $type_id, $last_value_id );
		if ( true == $result['status'] ) {
			if ( empty( $attribute_arr ) ) {
				$attribute_arr = $result['attribute_arr'];
			} else {
				$attribute_arr = array_merge( $attribute_arr, $result['attribute_arr'] );
			}
		} else {
			return $attribute_arr;
		}
		if ( true === $result['has_next'] ) {
			$attribute_arr = $this->ozon_attribute_value_list_helper(
				$ozon_id,
				$description_category_id,
				$type_id,
				$result['attribute_arr'][1999]->id,
				$attribute_arr
			);
		}
		return $attribute_arr;
	}

	/**
	 * Get HTML `<option>...</option>` tags of OZON attribute (сharacteristic) values
	 * (processing of json data obtained using the method `https://api-seller.ozon.ru/v1/description-category/attribute`)
	 * @see https://docs.ozon.ru/api/seller/
	 * 
	 * @param array $ozon_cat_arr
	 * @param string $ozon_attribute_value_id
	 * @param string $sep - Optional
	 * @param string $result_html - Optional
	 * 
	 * @return string
	 */
	public function get_option_tags_ozon_attribute_value( $ozon_cat_arr, $ozon_attribute_value_id, $sep = '', $result_html = '' ) {
		for ( $i = 0; $i < count( $ozon_cat_arr ); $i++ ) {
			if ( empty( $ozon_cat_arr[ $i ]->children ) ) {
				$result_html .= sprintf( '<option value="%1$s--%2$s" %3$s>%4$s (ozon_id = %5$s)</option>' . PHP_EOL,
					esc_attr( $ozon_cat_arr[ $i ]->id ),
					esc_attr( $ozon_cat_arr[ $i ]->value ),
					esc_attr( selected( $ozon_attribute_value_id, $ozon_cat_arr[ $i ]->id . '--' . $ozon_cat_arr[ $i ]->value, false ) ),
					esc_html( $sep . $ozon_cat_arr[ $i ]->value ),
					esc_html( $ozon_cat_arr[ $i ]->id )
				);
			} else {
				$result_html .= sprintf( '<option disabled value="%1$s"><strong>%2$s</strong></option>%3$s' . PHP_EOL,
					esc_attr( $ozon_cat_arr[ $i ]->id ),
					esc_html( $sep . $ozon_cat_arr[ $i ]->value ),
					$this->get_option_tags_ozon_attribute_value( $ozon_cat_arr[ $i ]->children, $ozon_attribute_value_id, $sep . '--' )
				);
			}
		}
		return $result_html;
	}

	/**
	 * Сохраняем данные блока, когда пост сохраняется
	 * 
	 * @param int $post_id
	 * @param WP_Post $post Post object
	 * @param bool $update (true — это обновление записи; false — это добавление новой записи)
	 * 
	 * @return void
	 */
	public function save_post_product( $post_id, $post, $update, $feed_id = '1' ) {
		if ( $post->post_type !== 'product' ) {
			return; // если это не товар вукомерц
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return; // если это ревизия
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return; // если это автосохранение ничего не делаем
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return; // проверяем права юзера
		}

		$post_meta_arr = [ 
			'_ip2oz_length',
			'_ip2oz_width',
			'_ip2oz_height',
			'_ip2oz_weight',
			'_ip2oz_tn_ved_code',
			'_ip2oz_vat'
		];
		$post_meta_arr = apply_filters( 'ip2oz_f_post_meta_arr', $post_meta_arr );
		if ( ! empty( $post_meta_arr ) ) {
			$this->save_post_meta( $post_meta_arr, $post_id );
		}

		// если экспорт глобально запрещён
		// * пофиксить '1' если будет несколько фидов
		$syncing_with_ozon = common_option_get( 'syncing_with_ozon', false, '1', 'ip2oz' );
		if ( $syncing_with_ozon === 'disabled' ) {
			new IP2OZ_Error_Log(
				sprintf( 'NOTICE: Включён глобальный запрет на импорт; Файл: %1$s; Строка: %2$s',
					'class-ip2oz.php',
					__LINE__
				)
			);
			return;
		}

		$api = new IP2OZ_Api();
		$answer_arr = $api->product_sync( $post_id );
		if ( $answer_arr['status'] == true ) {
			new IP2OZ_Error_Log(
				sprintf( 'FEED № %1$s; Тоовара с $post_id = %2$s успешно импортирован; Файл: %3$s; Строка: %4$s',
					$feed_id,
					$post_id,
					'class-ip2oz.php',
					__LINE__
				)
			);
		} else {
			new IP2OZ_Error_Log(
				sprintf( 'FEED № %1$s; ошибка добавления товара с $post_id = %2$s; Файл: %3$s; Строка: %4$s',
					$feed_id,
					$post_id,
					'class-ip2oz.php',
					__LINE__
				)
			);
			new IP2OZ_Error_Log( $answer_arr );
		}
	}

	/**
	 * Выводит в админке ссылку на импортированный и опубликованный в OZON товар.
	 * Function for `edit_form_after_title` action-hook.
	 * 
	 * @param WP_Post $post Post object
	 * 
	 * @return void
	 */
	public static function output_url_imported_product( $post ) {
		if ( $post->post_type !== 'product' ) {
			return; // пропускаем, если это не товар вукомерц	
		}
		$product = wc_get_product( $post->ID );
		if ( empty( get_post_meta( $post->ID, '_ip2oz_prod_id_on_ozon', true ) ) ) {
			$product_id_on_ozon = get_post_meta( $post->ID, '_ip2oz_prod_id_on_ozon', true );
			if ( $product_id_on_ozon == '' ) {
				$sku_format = common_option_get( 'sku', 'prefix_mode', '1', 'ip2oz' );
				switch ( $sku_format ) {
					case "products_id":
						$sku = (string) $product->get_id();
						break;
					case "sku":
						$sku = $product->get_sku();
						break;
					default:
						$sku = 'offer-' . $product->get_id();
				}

				$prefix_shop_sku = common_option_get( 'prefix_shop_sku', false, '1', 'ip2oz' );
				if ( ! empty( $prefix_shop_sku ) ) {
					$sku = $prefix_shop_sku . $sku;
				}

				$api = new IP2OZ_Api();
				$res_arr = $api->product_info( $sku );
				if ( isset( $res_arr['product_id'] ) ) {
					$product_id_on_ozon = $res_arr['product_id'];
					update_post_meta( $post->ID, '_ip2oz_prod_id_on_ozon', $product_id_on_ozon );
				}
			}
		} else {
			$product_id_on_ozon = get_post_meta( $post->ID, '_ip2oz_prod_id_on_ozon', true );
		}

		if ( ! empty( $product_id_on_ozon ) ) {
			printf( '<div id="ip2oz_url"><strong>%1$s</strong>: <a 
				href="https://seller.ozon.ru/app/products/%2$s/edit/general-info" 
				target="_blank">https://seller.ozon.ru/app/products/%2$s/edit/general-info</a></div>',
				esc_html__( 'Edit this product on OZON', 'import-products-to-ozon' ),
				$product_id_on_ozon
			);
			print '<style>#ip2oz_url{line-height:24px;min-height:25px;margin:0px;padding:0 10px;color:#666;}</style>';
		}
	}

	/**
	 * Added WooCommerce tabs. Function for `woocommerce_product_data_tabs` filter-hook.
	 * 
	 * @param array $tabs
	 * 
	 * @return array
	 */
	public function added_wc_tabs( $tabs ) {
		$tabs['ip2oz_special_panel'] = [ 
			'label' => __( 'Import Products to OZON', 'import-products-to-ozon' ), // название вкладки
			'target' => 'ip2oz_added_wc_tabs', // идентификатор вкладки
			'class' => [ 'hide_if_grouped' ], // классы управления видимостью вкладки в зависимости от типа товара
			'priority' => 70 // приоритет вывода
		];
		return $tabs;
	}

	/**
	 * Added WooCommerce tabs panel. Function for `woocommerce_product_data_panels` action-hook.
	 * 
	 * @return void
	 */
	public function added_tabs_panel_view() {
		global $post; ?>
		<div id="ip2oz_added_wc_tabs" class="panel woocommerce_options_panel">
			<?php do_action( 'ip2oz_before_options_group', $post ); ?>
			<div class="options_group">
				<h2>
					<strong>
						<?php _e( 'Individual product settings for export to OZON', 'import-products-to-ozon' ); ?>
					</strong>
				</h2>
				<?php
				woocommerce_wp_text_input( [ 
					'id' => '_ip2oz_length',
					'label' => __( 'Packing depth', 'import-products-to-ozon' ) . ' (мм)',
					'description' => '',
					'type' => 'number',
					'custom_attributes' => [ 
						'step' => 'any',
						'min' => '0'
					]
				] );

				woocommerce_wp_text_input( [ 
					'id' => '_ip2oz_width',
					'label' => __( 'Packing width', 'import-products-to-ozon' ) . ' (мм)',
					'description' => '',
					'type' => 'number',
					'custom_attributes' => [ 
						'step' => 'any',
						'min' => '0'
					]
				] );

				woocommerce_wp_text_input( [ 
					'id' => '_ip2oz_height',
					'label' => __( 'Packing height', 'import-products-to-ozon' ) . ' (мм)',
					'description' => '',
					'type' => 'number',
					'custom_attributes' => [ 
						'step' => 'any',
						'min' => '0'
					]
				] );

				woocommerce_wp_text_input( [ 
					'id' => '_ip2oz_weight',
					'label' => __( 'Packing weight', 'import-products-to-ozon' ) . ' (г)',
					'description' => '',
					'type' => 'number',
					'custom_attributes' => [ 
						'step' => 'any',
						'min' => '0'
					]
				] );

				woocommerce_wp_text_input( [ 
					'id' => '_ip2oz_tn_ved_code',
					'label' => 'ТН ВЭД',
					'description' => '',
					'type' => 'text'
				] );

				woocommerce_wp_select( [ 
					'id' => '_ip2oz_vat',
					'label' => __( 'VAT', 'import-products-to-ozon' ),
					'options' => [ 
						'default' => __( 'Default', 'import-products-to-ozon' ),
						'0' => __( 'Not taxed', 'import-products-to-ozon' ),
						'0.1' => '10%',
						'0.2' => '20%'
					]
				] );

				do_action( 'ip2oz_prepend_options_group', $post );
				do_action( 'ip2oz_append_options_group', $post );
				?>
			</div>
			<?php do_action( 'ip2oz_after_options_group', $post ); ?>
		</div>
		<?php
	}

	/**
	 * Удаляем метаполе о синхронизации с OZON, если мы в админке дублируем товар
	 * Function for `woocommerce_product_duplicate` action-hook.
	 * 
	 * @param WC_Product $duplicate
	 * @param WC_Product $product
	 *
	 * @return void
	 */
	public function product_duplicate( $duplicate, $product ) {
		// ? возможно вариации как-то ещё дополнительно надо обрабатывать...
		if ( get_post_meta( $duplicate->get_id(), '_ip2oz_prod_id_on_ozon', true ) !== '' ) {
			delete_post_meta( $duplicate->get_id(), '_ip2oz_prod_id_on_ozon' );
		}
	}

	/**
	 * Save post_meta
	 * 
	 * @param array $post_meta_arr
	 * @param int $post_id
	 * 
	 * @return void
	 */
	private function save_post_meta( $post_meta_arr, $post_id ) {
		for ( $i = 0; $i < count( $post_meta_arr ); $i++ ) {
			$meta_name = $post_meta_arr[ $i ];
			if ( isset( $_POST[ $meta_name ] ) ) {
				if ( empty( $_POST[ $meta_name ] ) ) {
					delete_post_meta( $post_id, $meta_name );
				} else {
					update_post_meta( $post_id, $meta_name, sanitize_text_field( $_POST[ $meta_name ] ) );
				}
			}
		}
	}

	/**
	 * Флаг для того, чтобы работало сохранение настроек если мультиселект пуст
	 * 
	 * @param string $save_if_empty
	 * @param array $args_arr
	 * 
	 * @return string
	 */
	public function flag_save_if_empty( $save_if_empty, $args_arr ) {
		if ( ! empty( $_GET ) && isset( $_GET['tab'] ) && $_GET['tab'] === 'main_tab' ) {
			if ( $args_arr['opt_name'] === 'params_arr' ) {
				$save_if_empty = 'empty_arr';
			}
		}
		return $save_if_empty;
	}

	/**
	 * Function for `woocommerce_product_options_general_product_data` action-hook.
	 * 
	 * @return void
	 */
	public function add_to_product_sync_info() {
		global $product, $post;

		// ? if ( get_post_meta( $post->ID, '_ip2oz_sku_on_ozon', true ) !== '' ) {
		// ?	$sku_on_ozon = get_post_meta( $post->ID, '_ip2oz_sku_on_ozon', true );
		// ? } else {
		// ?	$sku_on_ozon = '';
		// ? }

		if ( get_post_meta( $post->ID, '_ip2oz_prod_id_on_ozon', true ) == '' ) {
			$product_id_on_ozon = '';
		} else {
			$product_id_on_ozon = get_post_meta( $post->ID, '_ip2oz_prod_id_on_ozon', true );
		}

		if ( get_post_meta( $post->ID, '_ip2oz_prod_archive_status', true ) === 'archived' ) {
			$prod_archive_status = sprintf( ' %s', __( 'The product in the OZON archive', 'import-products-to-ozon' ) );
		} else {
			$prod_archive_status = '';
		}

		/**
		 * Выводит в админке ссылку на импортированный и опубликованный в OZON товар.
		 */
		if ( ! empty( $product_id_on_ozon ) ) {
			printf( '</p><p class="form-row form-row-full">%1$s. %2$s: "%3$s"%4$s<br/>
				<strong>%5$s</strong>: <a href="%6$s/%3$s/%7$s" target="_blank">%6$s/%3$s/%7$s</a>', // ! не закрывать <p>
				esc_html__( 'The product was imported to the OZON', 'import-products-to-ozon' ),
				esc_html__( 'His ID on OZON', 'import-products-to-ozon' ),
				esc_html__( $product_id_on_ozon ),
				$prod_archive_status,
				esc_html__( 'Edit product on OZON', 'import-products-to-ozon' ),
				'https://seller.ozon.ru/app/products',
				'edit/general-info'
			);
		}
	}

	/**
	 * Function for `woocommerce_variation_options` action-hook.
	 * 
	 * @param int $i Position in the loop
	 * @param array $variation_data Variation data
	 * @param WP_Post $variation Post data
	 *
	 * @return void
	 */
	function add_to_product_variation_sync_info( $i, $variation_data, $variation ) {
		if ( get_post_meta( $variation->ID, '_ip2oz_prod_id_on_ozon', true ) == '' ) {
			$product_id_on_ozon = '';
		} else {
			$product_id_on_ozon = get_post_meta( $variation->ID, '_ip2oz_prod_id_on_ozon', true );
		}

		if ( get_post_meta( $variation->ID, '_ip2oz_prod_archive_status', true ) === 'archived' ) {
			$prod_archive_status = sprintf( ' %s', __( 'The product in the OZON archive', 'import-products-to-ozon' ) );
		} else {
			$prod_archive_status = '';
		}

		/**
		 * Выводит в админке ссылку на импортированный и опубликованный в OZON товар.
		 */
		if ( ! empty( $product_id_on_ozon ) ) {
			printf( '</p><p class="form-row form-row-full">%1$s. %2$s: "%3$s"%4$s<br/>
				<strong>%5$s</strong>: <a href="%6$s/%3$s/%7$s" target="_blank">%6$s/%3$s/%7$s</a>', // ! не закрывать <p>
				esc_html__( 'The product variation was imported to the OZON', 'import-products-to-ozon' ),
				esc_html__( 'His ID on OZON', 'import-products-to-ozon' ),
				esc_html__( $product_id_on_ozon ),
				$prod_archive_status,
				esc_html__( 'Edit variation on OZON', 'import-products-to-ozon' ),
				'https://seller.ozon.ru/app/products',
				'edit/general-info'
			);
		}
	}
} // end class IP2OZ_Interface_Hoocked