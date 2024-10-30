<?php
/**
 * The class return the Settings page of the plugin Import Products to OZON
 *
 * @package                 Import Products to OZON
 * @subpackage              
 * @since                   0.1.0
 * 
 * @version                 0.5.1 (17-06-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     
 * 
 * @param                   
 *
 * @depends                 classes:	IP2OZ_Data_Arr
 *                                      IP2OZ_Error_Log 
 *                          traits:     
 *                          methods:    
 *                          functions:  common_option_get
 *                                      common_option_upd
 *                          constants:  IP2OZ_PLUGIN_DIR_URL
 *                          options:    
 */
defined( 'ABSPATH' ) || exit;

class IP2OZ_Settings_Page {
	/**
	 * Allowed HTML tags for use in wp_kses()
	 */
	const ALLOWED_HTML_ARR = [ 
		'a' => [ 
			'href' => true,
			'title' => true,
			'target' => true,
			'class' => true,
			'style' => true
		],
		'br' => [ 'class' => true ],
		'i' => [ 'class' => true ],
		'small' => [ 'class' => true ],
		'strong' => [ 'class' => true, 'style' => true ],
		'p' => [ 'class' => true, 'style' => true ],
		'kbd' => [ 'class' => true ]
	];

	/**
	 * Feed ID
	 * @var string
	 */
	private $feed_id;
	/**
	 * The value of the current tab
	 * @var string
	 */
	private $cur_tab = 'main_tab';

	/**
	 * The class return the Settings page of the plugin Import Products to OZON
	 */
	public function __construct() {
		if ( isset( $_GET['feed_id'] ) ) {
			$this->feed_id = sanitize_text_field( $_GET['feed_id'] );
		} else {
			// * тут у нас отличиается от других плагинов тк фид один
			$this->feed_id = '1';
		}
		if ( isset( $_GET['tab'] ) ) {
			$this->cur_tab = sanitize_text_field( $_GET['tab'] );
		}

		$this->init_classes();
		$this->init_hooks();
		$this->listen_submit();

		$this->print_view_html_form();
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
	 * Initialization hooks
	 * 
	 * @return void
	 */
	public function init_hooks() {
		// наш класс, вероятно, вызывается во время срабатывания хука admin_menu.
		// admin_init - следующий в очереди срабатывания, хуки раньше admin_menu нет смысла вешать
		// add_action('admin_init', [ $this, 'my_func' ], 10, 1);
		return;
	}

	/**
	 * Summary of print_view_html_form
	 * 
	 * @return void
	 */
	public function print_view_html_form() {
		$view_arr = [ 
			'feed_id' => $this->get_feed_id(),
			'tab_name' => $this->get_tab_name(),
			'tabs_arr' => $this->get_tabs_arr(),
			'prefix_feed' => $this->get_prefix_feed(),
			'current_blog_id' => $this->get_current_blog_id()
		];
		include_once __DIR__ . '/views/html-admin-settings-page.php';
	}

	/**
	 * Get tabs arr
	 * 
	 * @param string $current
	 * @return array
	 */
	public function get_tabs_arr( $current = 'main_tab' ) {
		$tabs_arr = [ 
			'main_tab' => sprintf( '%s',
				__( 'Main settings', 'import-products-to-ozon' )
			),
			'filtration_tab' => sprintf( '%s',
				__( 'Filtration', 'import-products-to-ozon' )
			),
			'api_tab' => sprintf( '%s',
				__( 'API Settings', 'import-products-to-ozon' )
			),
			'instruction_tab' => sprintf( '%s',
				__( 'Instruction', 'import-products-to-ozon' )
			)
		];
		$tabs_arr = apply_filters( 'ip2oz_f_tabs_arr', $tabs_arr, [ 'feed_id' => $this->get_feed_id() ] );
		return $tabs_arr;
	}

	/**
	 * Summary of print_view_html_fields
	 * 
	 * @param string $tab
	 * 
	 * @return void
	 */
	public static function print_view_html_fields( $tab, $feed_id = '1' ) {
		$ip2oz_data_arr_obj = new IP2OZ_Data_Arr();
		$data_for_tab_arr = $ip2oz_data_arr_obj->get_data_for_tabs( $tab ); // список дефолтных настроек

		for ( $i = 0; $i < count( $data_for_tab_arr ); $i++ ) {
			switch ( $data_for_tab_arr[ $i ]['type'] ) {
				case 'text':
					self::get_view_html_field_input( $data_for_tab_arr[ $i ] );
					break;
				case 'number':
					self::get_view_html_field_number( $data_for_tab_arr[ $i ] );
					break;
				case 'select':
					self::get_view_html_field_select( $data_for_tab_arr[ $i ] );
					break;
				case 'textarea':
					self::get_view_html_field_textarea( $data_for_tab_arr[ $i ] );
					break;
				default:
					do_action( 'ip2oz_f_print_view_html_fields', $data_for_tab_arr[ $i ], $feed_id );
			}
		}
		do_action( 'ip2oz_f_after_print_view_html_fields', $tab, $feed_id );
	}

	/**
	 * Summary of get_view_html_field_input
	 * 
	 * @param array $data_arr
	 * 
	 * @return void
	 */
	public static function get_view_html_field_input( $data_arr, $feed_id = '1' ) {
		if ( isset( $data_arr['tr_class'] ) ) {
			$tr_class = $data_arr['tr_class'];
		} else {
			$tr_class = '';
		}
		printf( '<tr class="%1$s">
					<th scope="row"><label for="%2$s">%3$s</label></th>
					<td class="overalldesc">
						<input 
							type="text" 
							name="%2$s" 
							id="%2$s" 
							value="%4$s"
							placeholder="%5$s"
							class="ip2oz_input"
							style="%6$s" /><br />
						<span class="description"><small>%7$s</small></span>
					</td>
				</tr>',
			esc_attr( $tr_class ),
			esc_attr( $data_arr['opt_name'] ),
			wp_kses( $data_arr['label'], self::ALLOWED_HTML_ARR ),
			esc_attr( common_option_get( $data_arr['opt_name'], false, $feed_id, 'ip2oz' ) ),
			esc_html( $data_arr['placeholder'] ),
			'width: 100%;',
			wp_kses( $data_arr['desc'], self::ALLOWED_HTML_ARR )
		);
	}

	/**
	 * Summary of get_view_html_field_number
	 * 
	 * @param array $data_arr
	 * 
	 * @return void
	 */
	public static function get_view_html_field_number( $data_arr, $feed_id = '1' ) {
		if ( isset( $data_arr['tr_class'] ) ) {
			$tr_class = $data_arr['tr_class'];
		} else {
			$tr_class = '';
		}
		if ( isset( $data_arr['min'] ) ) {
			$min = $data_arr['min'];
		} else {
			$min = '';
		}
		if ( isset( $data_arr['max'] ) ) {
			$max = $data_arr['max'];
		} else {
			$max = '';
		}
		if ( isset( $data_arr['step'] ) ) {
			$step = $data_arr['step'];
		} else {
			$step = '';
		}

		printf( '<tr class="%1$s">
					<th scope="row"><label for="%2$s">%3$s</label></th>
					<td class="overalldesc">
						<input 
							type="number" 
							name="%2$s" 
							id="%2$s" 
							value="%4$s"
							placeholder="%5$s" 
							min="%6$s"
							max="%7$s"
							step="%8$s"
							class="ip2oz_input"
							/><br />
						<span class="description"><small>%9$s</small></span>
					</td>
				</tr>',
			esc_attr( $tr_class ),
			esc_attr( $data_arr['opt_name'] ),
			wp_kses( $data_arr['label'], self::ALLOWED_HTML_ARR ),
			esc_attr( common_option_get( $data_arr['opt_name'], false, $feed_id, 'ip2oz' ) ),
			esc_html( $data_arr['placeholder'] ),
			esc_attr( $min ),
			esc_attr( $max ),
			esc_attr( $step ),
			wp_kses( $data_arr['desc'], self::ALLOWED_HTML_ARR )
		);
	}

	/**
	 * Summary of get_view_html_field_select
	 * 
	 * @param array $data_arr
	 * 
	 * @return void
	 */
	public static function get_view_html_field_select( $data_arr, $feed_id = '1' ) {
		if ( isset( $data_arr['key_value_arr'] ) ) {
			$key_value_arr = $data_arr['key_value_arr'];
		} else {
			$key_value_arr = [];
		}
		if ( isset( $data_arr['categories_list'] ) ) {
			$categories_list = $data_arr['categories_list'];
		} else {
			$categories_list = false;
		}
		if ( isset( $data_arr['tags_list'] ) ) {
			$tags_list = $data_arr['tags_list'];
		} else {
			$tags_list = false;
		}
		if ( isset( $data_arr['tr_class'] ) ) {
			$tr_class = $data_arr['tr_class'];
		} else {
			$tr_class = '';
		}
		if ( isset( $data_arr['size'] ) ) {
			$size = $data_arr['size'];
		} else {
			$size = '1';
		}
		// массивы храним отдельно от других параметров
		if ( isset( $data_arr['multiple'] ) && true === $data_arr['multiple'] ) {
			$multiple = true;
			$multiple_val = '[]" multiple';
			// $value = []; // ? временно поставил тут массив, но может тут строка
			$value = maybe_unserialize( univ_option_get( $data_arr['opt_name'] . $feed_id ) );
		} else {
			$multiple = false;
			$multiple_val = '"';
			$value = common_option_get(
				$data_arr['opt_name'],
				false,
				$feed_id,
				'ip2oz' );
		}

		printf( '<tr class="%1$s">
				<th scope="row"><label for="%2$s">%3$s</label></th>
				<td class="overalldesc">
					<select name="%2$s%5$s id="%2$s" size="%4$s"/>%6$s</select><br />
					<span class="description"><small>%7$s</small></span>
				</td>
			</tr>',
			esc_attr( $tr_class ),
			esc_attr( $data_arr['opt_name'] ),
			wp_kses( $data_arr['label'], self::ALLOWED_HTML_ARR ),
			esc_attr( $size ),
			$multiple_val,
			self::print_view_html_option_for_select(
				$value,
				$data_arr['opt_name'],
				[ 
					'woo_attr' => $data_arr['woo_attr'],
					'key_value_arr' => $key_value_arr,
					'categories_list' => $categories_list,
					'tags_list' => $tags_list,
					'multiple' => $multiple
				]
			),
			wp_kses( $data_arr['desc'], self::ALLOWED_HTML_ARR )
		);
	}

	/**
	 * Summary of print_view_html_option_for_select
	 * 
	 * @param mixed $opt_value
	 * @param string $opt_name
	 * @param array $params_arr
	 * @param mixed $res
	 * 
	 * @return mixed
	 */
	public static function print_view_html_option_for_select( $opt_value, string $opt_name, $params_arr = [], $res = '' ) {
		if ( true === $params_arr['multiple'] ) {
			$woo_attributes_arr = get_woo_attributes();
			foreach ( $woo_attributes_arr as $attribute ) {
				if ( ! empty( $opt_value ) ) {
					foreach ( $opt_value as $value ) {
						if ( (string) $attribute['id'] == (string) $value ) {
							$selected = ' selected="select" ';
							break;
						} else {
							$selected = '';
						}
					}
				} else {
					$selected = '';
				}
				$res .= sprintf( '<option value="%1$s" %2$s>%3$s</option>' . PHP_EOL,
					esc_attr( $attribute['id'] ),
					$selected,
					esc_attr( $attribute['name'] )
				);
			}
			unset( $woo_attributes_arr );
		} else {
			if ( ! empty( $params_arr['key_value_arr'] ) ) {
				for ( $i = 0; $i < count( $params_arr['key_value_arr'] ); $i++ ) {
					$res .= sprintf( '<option value="%1$s" %2$s>%3$s</option>' . PHP_EOL,
						esc_attr( $params_arr['key_value_arr'][ $i ]['value'] ),
						esc_attr( selected( $opt_value, $params_arr['key_value_arr'][ $i ]['value'], false ) ),
						esc_attr( $params_arr['key_value_arr'][ $i ]['text'] )
					);
				}
			}

			if ( ! empty( $params_arr['woo_attr'] ) ) {
				$woo_attributes_arr = get_woo_attributes();
				for ( $i = 0; $i < count( $woo_attributes_arr ); $i++ ) {
					$res .= sprintf( '<option value="%1$s" %2$s>%3$s</option>' . PHP_EOL,
						esc_attr( $woo_attributes_arr[ $i ]['id'] ),
						esc_attr( selected( $opt_value, $woo_attributes_arr[ $i ]['id'], false ) ),
						esc_attr( $woo_attributes_arr[ $i ]['name'] )
					);
				}
				unset( $woo_attributes_arr );
			}
		}

		// TODO: For PRO Если активен плагин WooCommerce Multi Inventory & Warehouses
		if ( $opt_name === 'ip2ozp_inventories' ) {
			foreach ( get_terms( [ 'taxonomy' => [ 'inventories' ], 'hide_empty' => 0, 'parent' => 0 ] ) as $term ) {
				$res .= sprintf( '<option value="%1$s" %2$s>%3$s</option>' . PHP_EOL,
					esc_attr( $term->term_id ),
					esc_attr( selected( $opt_value, $term->term_id, false ) ),
					esc_attr( $term->name )
				);
			}
		}

		return $res;
	}

	/**
	 * Summary of get_view_html_field_textarea
	 * 
	 * @param array $data_arr
	 * 
	 * @return void
	 */
	public static function get_view_html_field_textarea( $data_arr, $feed_id = '1' ) {
		if ( isset( $data_arr['tr_class'] ) ) {
			$tr_class = $data_arr['tr_class'];
		} else {
			$tr_class = '';
		}
		if ( isset( $data_arr['rows'] ) ) {
			$rows = $data_arr['rows'];
		} else {
			$rows = '6';
		}
		if ( isset( $data_arr['cols'] ) ) {
			$cols = $data_arr['cols'];
		} else {
			$cols = '32';
		}
		printf( '<tr class="%1$s">
					<th scope="row"><label for="%2$s">%3$s</label></th>
					<td class="overalldesc">
						<textarea 							 
							name="%2$s" 
							id="%2$s" 
							rows="%4$s"
							cols="%5$s"
							class="ip2oz_textarea"
							placeholder="%6$s">%7$s</textarea><br />
						<span class="description"><small>%8$s</small></span>
					</td>
				</tr>',
			esc_attr( $tr_class ),
			esc_attr( $data_arr['opt_name'] ),
			wp_kses( $data_arr['label'], self::ALLOWED_HTML_ARR ),
			esc_attr( $rows ),
			esc_attr( $cols ),
			esc_html( $data_arr['placeholder'] ),
			esc_attr( common_option_get( $data_arr['opt_name'], false, $feed_id, 'ip2oz' ) ),
			wp_kses( $data_arr['desc'], self::ALLOWED_HTML_ARR )
		);
	}

	/**
	 * Get feed ID
	 * 
	 * @return string
	 */
	private function get_feed_id() {
		return $this->feed_id;
	}

	/**
	 * Get current tab
	 * 
	 * @return string
	 */
	private function get_tab_name() {
		return $this->cur_tab;
	}

	/**
	 * Save plugin settings
	 * 
	 * @param string $opt_name
	 * @param string $feed_id
	 * @param string $save_if_empty
	 * 
	 * @return void
	 */
	private function save_plugin_set( $opt_name, $feed_id = '1', $save_if_empty = 'no' ) {
		if ( isset( $_POST[ $opt_name ] ) ) {
			if ( is_array( $_POST[ $opt_name ] ) ) {
				// массивы храним отдельно от других параметров
				univ_option_upd( $opt_name . $feed_id, maybe_serialize( $_POST[ $opt_name ] ) );
			} else {
				$value = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $_POST[ $opt_name ] );
				common_option_upd( $opt_name, $value, 'no', $feed_id, 'ip2oz' );
			}
		} else {
			if ( 'empty_str' === $save_if_empty ) {
				common_option_upd( $opt_name, '', 'no', $feed_id, 'ip2oz' );
			}
			if ( 'empty_arr' === $save_if_empty ) {
				// массивы храним отдельно от других параметров
				univ_option_upd( $opt_name . $feed_id, maybe_serialize( [] ) );
			}
		}
		return;
	}

	/**
	 * The function listens for the send buttons
	 * 
	 * @return void
	 */
	private function listen_submit() {
		if ( isset( $_REQUEST['ip2oz_check_action'] ) ) {
			$obj = new IP2OZ_Api();
			$result = $obj->get_ozon_categories();
			if ( true == $result['status'] ) {
				$class = 'notice-success';
				$message = sprintf( '<strong style="font-weight: 700;">%1$s</strong>. %2$s',
					__( 'API connection was successful', 'import-products-to-ozon' ),
					__( 'Now you can go to step 4 of the instructions', 'import-products-to-ozon' )
				);
			} else {
				$class = 'notice-error';
				$message = sprintf(
					'<strong style="%1$s">%2$s!</strong><br/>
					<strong style="%1$s">error_code:</strong> %3$s. <strong style="%1$s">error_msg:</strong> %4$s',
					'font-weight: 700;',
					__( 'API connection error', 'import-products-to-ozon' ),
					esc_html( $result['errors']->error_code ),
					esc_html( $result['errors']->error_msg )
				);
				// $message .= get_array_as_string( $result, '<br/>' );
				new IP2OZ_Error_Log( $result );
			}
			printf( '<div class="notice %1$s"><p>%2$s</p></div>', $class, $message );
		}

		if ( isset( $_REQUEST['ip2oz_submit_action'] ) ) {
			if ( ! empty( $_POST ) && check_admin_referer( 'ip2oz_nonce_action', 'ip2oz_nonce_field' ) ) {
				do_action( 'ip2oz_prepend_submit_action', $this->get_feed_id() );
				$feed_id = sanitize_text_field( $_POST['ip2oz_feed_id_for_save'] );

				common_option_upd( 'date_save_set', current_time( 'timestamp', 1 ), 'no', $feed_id, 'ip2oz' );

				if ( isset( $_POST['run_cron'] ) ) {
					$run_cron = sanitize_text_field( $_POST['run_cron'] );
					common_option_upd( 'date_save_set', $run_cron, 'no', $feed_id, 'ip2oz' );

					if ( $run_cron === 'disabled' ) {
						// отключаем крон
						wp_clear_scheduled_hook( 'ip2oz_cron_period', [ $feed_id ] );
						common_option_upd( 'status_cron', 'disabled', 'no', $feed_id, 'ip2oz' );

						wp_clear_scheduled_hook( 'cron_sborki', [ $feed_id ] );
						common_option_upd( 'status_sborki', '-1', 'no', $feed_id, 'ip2oz' );
					} else {
						wp_clear_scheduled_hook( 'ip2oz_cron_period', [ $feed_id ] );
						wp_schedule_event( time(), $run_cron, 'ip2oz_cron_period', [ $feed_id ] );
						new IP2OZ_Error_Log( sprintf( 'FEED № %1$s; %2$s. Файл: %3$s; Строка: %4$s',
							'ip2oz_cron_period внесен в список заданий',
							$this->get_feed_id(),
							'class-ip2oz-settings-page.php',
							__LINE__
						) );
					}
				}

				$def_plugin_date_arr = new IP2OZ_Data_Arr();
				$opts_name_and_def_date_arr = $def_plugin_date_arr->get_opts_name_and_def_date( 'public' );
				foreach ( $opts_name_and_def_date_arr as $opt_name => $value ) {
					$save_if_empty = 'no';
					$save_if_empty = apply_filters( 'ip2oz_f_save_if_empty', $save_if_empty, [ 'opt_name' => $opt_name ] );
					$this->save_plugin_set( $opt_name, $feed_id, $save_if_empty );
				}
				do_action( 'ip2oz_settings_page_listen_submit', $feed_id );
				$this->feed_id = $feed_id;
			}
		}

		return;
	}

	/**
	 * Возвращает префикс фида
	 * 
	 * @return string
	 */
	private function get_prefix_feed() {
		if ( $this->get_feed_id() == '1' ) {
			$prefix_feed = '';
		} else {
			$prefix_feed = $this->get_feed_id();
		}
		return (string) $prefix_feed;
	}

	/**
	 * Возвращает id текущего блога
	 * 
	 * @return string
	 */
	private function get_current_blog_id() {
		if ( is_multisite() ) {
			$cur_blog_id = get_current_blog_id();
		} else {
			$cur_blog_id = '0';
		}
		return (string) $cur_blog_id;
	}
}