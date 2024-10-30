<?php
/**
 * The class will help you connect your store to OZON using API
 *
 * @package                 Import Products to OZON
 * @subpackage              
 * @since                   0.1.0
 * 
 * @version                 0.7.3 (05-08-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     https://docs.ozon.ru/api/seller/
 *
 * @param       array       $args_arr - Optional
 *
 * @depends                 classes:    IP2OZ_Api_Helper
 *                                      IP2OZ_Error_Log
 *                          traits:     
 *                          methods:    
 *                          functions:  common_option_get
 *                          constants:  
 */
defined( 'ABSPATH' ) || exit;

final class IP2OZ_Api {
	/**
	 * Client ID from OZON personal account
	 * @var string
	 */
	protected $client_id;
	/**
	 * API key from OZON personal account
	 * @var string
	 */
	protected $api_key;
	/**
	 * debug - string - позволяет добавить к url запроса GET-параметр для дебага
	 * @var string
	 */
	protected $debug;
	/**
	 * Feed ID
	 * @var string
	 */
	protected $feed_id = '1';

	/**
	 * Summary of __construct
	 * 
	 * @param array $args_arr
	 */
	public function __construct( $args_arr = [] ) {
		if ( isset( $args_arr['client_id'] ) ) {
			$this->client_id = $args_arr['client_id'];
		} else {
			$this->client_id = common_option_get( 'client_id', false, '1', 'ip2oz' );
		}
		if ( isset( $args_arr['api_key'] ) ) {
			$this->api_key = $args_arr['api_key'];
		} else {
			$this->api_key = common_option_get( 'api_key', false, '1', 'ip2oz' );
		}
		if ( isset( $args_arr['debug'] ) ) {
			$this->debug = $args_arr['debug'];
		}
		if ( isset( $args_arr['feed_id'] ) ) {
			$this->feed_id = $args_arr['feed_id'];
		}

		$this->init_hooks();
	}

	/**
	 * Initialization hooks
	 * 
	 * @return void
	 */
	public function init_hooks() {
		// хук срабатывает только во фронтенде
		add_action( 'parse_request', [ $this, 'listen_request' ] );
		// хук срабатывает только в админке
		add_action( 'admin_init', [ $this, 'listen_submits' ], 9 );
	}

	/**
	 * Listening to the submit button
	 * 
	 * @return void
	 */
	public function listen_submits() {
		return;
	}

	/**
	 * Listen request
	 * 
	 * @return void
	 */
	public function listen_request() {
		return;
	}

	/**
	 * Возвращает массив из объектов категорий товаров OZON
	 * 
	 * @version             0.1.0
	 * @see                 https://docs.ozon.ru/api/seller/
	 * 
	 * @param   string      $language - Optional
	 * 
	 * @return  array:
	 *                      ['status'] - `true` | `false` (всегда);
	 *                      ['categories_arr'] - `array` - массив из объектов категорий OZON в json
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function get_ozon_categories( $language = 'DEFAULT' ) {
		$result = [ 
			'status' => false
		];

		$data_arr = [ 
			'language' => $language
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v1/description-category/tree',
			$data_arr,
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка получения списка категорий от OZON',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}
		// object(stdClass)#18950(1){ "result":[{"description_category_id": 200001506,
		// "category_name": "Товары для курения и аксессуары","disabled": false,
		//"children": [{"description_category_id": 200001034,"category_name": "Товары для курения","disabled": false,
		//"children": [{"type_name": "Пепельница","type_id": 91975,"disabled": false, "children": []}]}]}]}

		$result = [ 
			'status' => true,
			'categories_arr' => $answer_arr['body_answer']->result
		];

		return $result;
	}

	/**
	 * Возвращает список характеристик категории OZON
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/DescriptionCategoryAPI_GetAttributes
	 * 
	 * @param   int|string	$description_category_id - Required
	 * @param   int|string	$type_id - Required
	 * 
	 * @return  array:
	 *					['status'] - true / false (всегда)
	 *					['categories_arr'] - array - массив из объектов категорий OZON в json
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function get_ozon_attribute_name_list_by_category( $description_category_id, $type_id ) {
		$result = [ 
			'status' => false
		];

		$data_arr = [ 
			"description_category_id" => (int) $description_category_id,
			'language' => "DEFAULT",
			'type_id' => (int) $type_id
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v1/description-category/attribute',
			$data_arr,
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка получения списка характеристик категории от OZON',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}
		// object(stdClass)#18950(1){ "result":[{"category_id":17034410,"title":"Развивающие игрушки","children":[]}] }

		$result = [ 
			'status' => true,
			'attribute_arr' => $answer_arr['body_answer']->result
		];

		return $result;
	}

	/**
	 * Возвращает массив из объектов категорий товаров OZON
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/DescriptionCategoryAPI_GetAttributeValues
	 * 
	 * @param	int		$attribute_id - Required
	 * @param	int		$description_category_id - Optional
	 * @param	int		$type_id - Optional
	 * @param	int		$last_value_id - Optional
	 * 
	 * @return	array:
	 *					- ['status'] - true / false (всегда)
	 *					- ['attribute_arr'] - array - массив из объектов категорий OZON в json
	 *                  - ['has_next']
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function get_ozon_attribute_value_list( $attribute_id, $description_category_id = 17027900, $type_id = 92579, $last_value_id = 0 ) {
		$result = [ 
			'status' => false
		];

		$data_arr = [ 
			"description_category_id" => $description_category_id, // 17027900,
			'attribute_id' => $attribute_id,
			'type_id' => $type_id, // 92579,
			'language' => "DEFAULT",
			'last_value_id' => $last_value_id,
			'limit' => 5000
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v1/description-category/attribute/values',
			$data_arr,
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка получения списка категорий от OZON',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}
		// object(stdClass)#18950(1){ "result":[{"category_id":17034410,"title":"Развивающие игрушки","children":[]}] }

		$has_next = false;
		if ( isset( $answer_arr['body_answer']->has_next ) ) {
			$has_next = $answer_arr['body_answer']->has_next;
		}

		$result = [ 
			'status' => true,
			'attribute_arr' => $answer_arr['body_answer']->result,
			'has_next' => $has_next
		];

		return $result;
	}

	/**
	 * Синхронизация товаров
	 * 
	 * @version			0.1.0
	 * @see				
	 * 
	 * @param	int		$product_id - Required
	 * 
	 * @return	array:
	 *					['status'] - true / false (всегда)
	 *					['product_id'] - string - id удалённого товара
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function product_sync( $product_id ) {
		$answer_arr = [ 
			'status' => false
		];

		$helper = new IP2OZ_Api_Helper();
		$helper->set_product_data( $product_id, 'product_add' );
		if ( ! empty( $helper->get_skip_reasons_arr() ) ) {
			$answer_arr['skip_reasons'] = $helper->get_skip_reasons_arr();
		}
		// if ( ! empty( $helper->get_product_data() ) ) {
			$product_sku_list_arr = $helper->get_product_sku_list_arr();
			for ( $i = 0; $i < count( $product_sku_list_arr ); $i++ ) {
				$sku_on_ozon = $product_sku_list_arr[ $i ]['sku_on_ozon'];
				$post_id_on_wp = $product_sku_list_arr[ $i ]['post_id_on_wp'];
				$have_get_result = $product_sku_list_arr[ $i ]['have_get_result'];
				$prod_id_on_ozon = get_post_meta( $post_id_on_wp, '_ip2oz_prod_id_on_ozon', true );

				if ( false === $have_get_result && ! empty( $sku_on_ozon ) ) {
					if ( $prod_id_on_ozon !== '' ) {
						// этот товар надо перенести в архив
						$res_d = $this->product_archive( [ $prod_id_on_ozon ] );
						if ( true == $res_d['status'] ) {
							$helper->set_product_exists( $post_id_on_wp, [ 'product_archive_status' => 'archived' ] );
						}
					}
				}

				if ( true === $have_get_result ) {
					// этот товар надо создать / обновить
					if ( get_post_meta( $post_id_on_wp, '_ip2oz_prod_archive_status', true ) === 'archived' ) {
						// но прежде этот товар надо разархивировать
						$res_d = $this->product_unarchive( [ $prod_id_on_ozon ] );
						if ( true == $res_d['status'] ) {
							$helper->set_product_exists( $post_id_on_wp, [ 'product_archive_status' => '' ] );
						}
					}
					$answer_arr = $this->product_add(
						$helper->get_product_data(),
						[ 
							'product_id' => $post_id_on_wp,
							'category_id_ozon' => ''
						]
					);

					if ( true === $answer_arr['status'] ) {
						$res_arr = $this->product_info( $sku_on_ozon );

						if ( isset( $res_arr['product_id'] ) ) {
							$product_id_on_ozon = $res_arr['product_id'];
							update_post_meta( $post_id_on_wp, '_ip2oz_prod_id_on_ozon', $product_id_on_ozon );
							$helper->set_product_exists(
								$post_id_on_wp,
								[ 
									'product_id_on_ozon' => $res_arr['product_id'],
									'product_archive_status' => ''
								]
							);
							$answer_arr['product_id'] = $product_id_on_ozon;
						}
					}
				}
			}
		// }

		return $answer_arr;
	}

	/**
	 * Добавление товара
	 * 
	 * @version			0.1.0
	 * @see				https://dev.ozon.ru/start/261-Izmenenie-dereva-kategorii-tovarov-v-Seller-API
	 * 
	 * @param	array	$product_data - Required
	 * @param	array	$data_arr - Required
	 * 
	 * @return	array:
	 *					['status'] - true / false (всегда)
	 *					['product_id'] - string - id удалённого товара
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function product_add( $product_data, $data_arr ) {
		$result = [ 
			'status' => false
		];

		$params_arr = [];
		$params_arr = array_merge( $params_arr, $product_data );

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v3/product/import',
			[ 
				'items' => $product_data
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка добавления товара',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		// object(stdClass)#18501 (1) { ["result"]=> object(stdClass)#18486 (1) { ["task_id"]=> int(718664361) } }
		$task_id = $answer_arr['body_answer']->result->task_id;
		new IP2OZ_Error_Log( sprintf(
			'FEED № %1$s; NOTICE: %2$s task_id = %3$s! Файл: %4$s; Строка: %5$s',
			$this->get_feed_id(),
			'Получили',
			$task_id,
			'class-ip2oz-api.php',
			__LINE__
		) );
		// ? тут мы можем записать в метаполе номер таска и поставить задачу на его проверку
		update_post_meta( $data_arr['product_id'], '_ip2oz_task_id', sanitize_text_field( $task_id ) );
		$r = $this->check_task_id( $task_id );
		if ( true == $r['status'] ) {
			$result = [ 
				'status' => true,
				'product_id' => $r['product_id'],
				'offer_id' => $r['offer_id'] // артикул товара на нашем сайте
			];
		} else {
			$result['errors'] = $r['errors'];
		}

		return $result;
	}

	/**
	 * Узнать статус добавления товара
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/ProductAPI_GetImportProductsInfo
	 * 
	 * @param	int		$task_id - Required
	 * 
	 * @return	array:
	 *					['status'] - true / false (всегда)
	 *					['offer_id] - string - артикул загружаемого товара на нашем сайте
	 *					['product_id'] - string - id загружаемого товара (может быть 0)
	 *					['product_status']- string - "pending" или "imported",
	 *					['product_errors_arr'] - array - массив ошибко товара
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function check_task_id( $task_id ) {
		$result = [ 
			'status' => false
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v1/product/import/info',
			[ 
				'task_id' => $task_id
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		// object(stdClass)#18503 (1) { 
		//		["result"]=> object(stdClass)#18515 (2) { 
		//			["items"]=> array(1) { 
		//				[0]=> object(stdClass)#18513 (4) { 
		//						["offer_id"]=> string(9) "prod_1091" 
		//						["product_id"]=> int(0) // ? когда таск в работе тут будет 0
		//						["status"]=> string(7) "pending" or "imported"
		//						["errors"]=> array(0) { } 
		//					} 
		//  		} 
		//			["total"]=> int(1) 
		//		} 
		// }
		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				$this->get_feed_id(),
				'Ошибка обработки задачи на добавления товара',
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		if ( ! empty( $answer_arr['body_answer']->result->items ) ) {
			$offer_id = $answer_arr['body_answer']->result->items[0]->offer_id;
			$product_id = $answer_arr['body_answer']->result->items[0]->product_id;
			$product_status = $answer_arr['body_answer']->result->items[0]->status;
			$product_errors_arr = $answer_arr['body_answer']->result->items[0]->errors;

			$result = [ 
				'status' => true,
				'offer_id' => $offer_id,
				'product_id' => $product_id,
				'product_status' => $product_status,
				'product_errors_arr' => $product_errors_arr
			];
		} else {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => 'items пуст!',
				'request_params' => null
			];
		}

		return $result;
	}

	/**
	 * Список товаров
	 * 
	 * @version             0.1.0
	 * @see                 https://docs.ozon.ru/api/seller/#operation/ProductAPI_GetProductInfoV2
	 * 
	 * @param  string|int   $product_sku - Required - Идентификатор товара в системе продавца — артикул
	 * 
	 * @return array:
	 *						['status'] - true / false (всегда)
	 *						['product_id'] - string - id товара
	 *						['offer_id'] - string - артикул товара
	 *				или:
	 * 						['errors'] - array 
	 * 							- ["error_code"] => int(101)
	 *							- ["error_msg"] => string(37)
	 *							- ["request_params"] => NULL
	 */
	public function product_info( $product_sku ) {
		$result = [ 
			'status' => false
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v2/product/info',
			[ 
				"offer_id" => $product_sku, // ! тут offer_id - это артикул
				'product_id' => 0, // ! product_id - это артикул id товара на сайте ОЗОН
				'sku' => 0
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				$this->get_feed_id(),
				'Ошибка обработки product_info',
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		//  object(stdClass)#23121 (43) { 
		//		["id"]=> int(623642719) 
		//		["name"]=> string(80) "Хрома пиколинат, БАД для похудения, 60 капсул" 
		//		["offer_id"]=> string(9) "offer-357"
		//		["barcode"]=> string(0) "" 
		//		["buybox_price"]=> string(0) "" 
		//		...
		// }
		if ( ! empty( $answer_arr['body_answer']->result->id ) ) {
			$result = [ 
				'status' => true,
				'product_id' => $answer_arr['body_answer']->result->id,
				'offer_id' => $answer_arr['body_answer']->result->offer_id
			];
		}

		return $result;
	}

	/**
	 * Удалить товар без SKU из архива
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/ProductAPI_ProductUnarchive
	 * 
	 * @param	int	$product_id_ozon - Required
	 * 
	 * @return	array:
	 *					['status'] - true / false (всегда)
	 *					['product_id'] - string - id товара в системе ozon
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function product_del( $product_id_ozon ) {
		$result = [ 
			'status' => false
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v2/products/delete',
			[ 
				'products' => [ 
					'offer_id' => $product_id_ozon
				]
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				$this->get_feed_id(),
				'Ошибка удаления товара',
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		$answer_arr['body_answer']->result;

		$result = [ 
			'status' => $answer_arr['body_answer']->result, //true,
			'product_id' => $product_id_ozon
		];

		return $result;
	}

	/**
	 * Архивация товара
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/ProductAPI_ProductArchive
	 * 
	 * @param	array   $offer_ids_arr = [] - Required
	 * 
	 * @return	array:
	 *					['status'] - true / false (всегда)
	 *					['product_id'] - string - id товара в системе ozon
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function product_archive( $offer_ids_arr = [] ) {
		$result = [ 
			'status' => false
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v1/product/archive',
			[ 
				"product_id" => $offer_ids_arr
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка переноса товара в архив',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		$result = [ 
			'status' => true
		];

		return $result;
	}

	/**
	 * Восстановление товаров из архива
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/ProductAPI_ProductUnarchive
	 * 
	 * @param	array	 $offer_ids_arr = [] - Required
	 * 
	 * @return	array:
	 *					['status'] - true / false (всегда)
	 *					['product_id'] - string - id товара в системе ozon
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function product_unarchive( $offer_ids_arr = [] ) {
		$result = [ 
			'status' => false
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v1/product/unarchive',
			[ 
				"product_id" => $offer_ids_arr
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка восстановления товаров из архива',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		$result = [ 
			'status' => true
		];

		return $result;
	}

	/**
	 * Позволяет изменить информацию о количестве товара в наличии
	 * 
	 * @version			0.1.0
	 * @see				https://docs.ozon.ru/api/seller/#operation/ProductAPI_ProductsStocksV2
	 * 
	 * @param   array   $stocks_arr = [] - Required
	 * 
	 * @return  array:
	 *					['status'] - true / false (всегда)
	 *					['product_id'] - string - id товара в системе ozon
	 *			или:
	 * 					['errors'] - array 
	 * 						- ["error_code"] => int(101)
	 *						- ["error_msg"] => string(37)
	 *						- ["request_params"] => NULL
	 */
	public function product_stocks( $stocks_arr = [] ) {
		$result = [ 
			'status' => false
		];

		$answer_arr = $this->response_to_ozon(
			'https://api-seller.ozon.ru/v2/products/stocks',
			[ 
				'stocks' => $stocks_arr
			],
			$this->get_headers_arr(),
			'POST',
			[],
			'json_encode'
		);

		if ( isset( $answer_arr['body_answer']->error ) ) {
			new IP2OZ_Error_Log( sprintf(
				'FEED № %1$s; ERROR: %2$s body_answer = %3$s! Файл: %4$s; Строка: %5$s',
				'Ошибка передачи остатков',
				$this->get_feed_id(),
				$answer_arr['body_answer']->error->error_msg,
				'class-ip2oz-api.php',
				__LINE__
			) );
			$result['errors'] = $answer_arr['body_answer']->error;
			return $result;
		}
		if ( $answer_arr['http_code'] !== 200 ) {
			$result['errors'] = [ 
				'error_code' => $answer_arr['http_code'],
				'error_msg' => $answer_arr['body_answer'],
				'request_params' => null
			];
			return $result;
		}

		$result = [ 
			'status' => true
		];

		return $result;
	}

	/**
	 * Отправка запросов через wp_remote_request
	 * 
	 * @version			0.1.0
	 * @see				https://snipp.ru/php/curl
	 * 
	 * @param	string	$request_url - Required
	 * @param	array	$postfields_arr - Optional
	 * @param	array	$headers_arr - Optional
	 * @param	string	$request_type - Optional
	 * @param	array	$pwd_arr - Optional (require keys: 'login', 'pwd')
	 * @param	string	$encode_type - Optional
	 * @param	int		$timeout - Optional
	 * @param	string	$proxy - Optional // example: '165.22.115.179:8080'
	 * @param	bool	$debug - Optional
	 * @param	string	$sep - Optional
	 * @param	string	$useragent - Optional
	 * 
	 * @return 	array	keys: errors, status, http_code, body, header_request, header_answer
	 * 
	 */
	public function response_to_ozon(
		$request_url,
		$postfields_arr = [],
		$headers_arr = [],
		$request_type = 'POST',
		$pwd_arr = [],
		$encode_type = 'json_encode',
		$timeout = 45,
		$proxy = '',
		$debug = false,
		$sep = PHP_EOL,
		$useragent = 'default' ) {

		if ( ! empty( $this->get_debug() ) ) {
			$request_url = $request_url . '?dbg=' . $this->get_debug();
		}
		if ( $useragent === 'default' ) {
			$useragent = 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' );
		}

		/** 
		 * if (!empty($pwd_arr)) {
		 *	if (isset($pwd_arr['login']) && isset($pwd_arr['pwd'])) {
		 *		$userpwd = $pwd_arr['login'].':'.$pwd_arr['pwd']; // 'логин:пароль'
		 *		curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
		 *	}
		 * }
		 **/

		$answer_arr = [];
		$answer_arr['body_request'] = null;
		if ( $request_type !== 'GET' ) {
			switch ( $encode_type ) {
				case 'json_encode':
					$answer_arr['body_request'] = wp_json_encode( $postfields_arr, JSON_UNESCAPED_UNICODE );
					break;
				case 'http_build_query':
					$answer_arr['body_request'] = http_build_query( $postfields_arr );
					break;
				case 'dont_encode':
					$answer_arr['body_request'] = $postfields_arr;
					break;
				default:
					$answer_arr['body_request'] = wp_json_encode( $postfields_arr );
			}
		}

		new IP2OZ_Error_Log( sprintf( 'FEED № %1$s; %2$s %3$s; Файл: %4$s; Строка: %5$s',
			$this->get_feed_id(),
			'Отправляем запрос к',
			$request_url,
			'class-ip2oz-api.php',
			__LINE__
		) );
		new IP2OZ_Error_Log( $headers_arr );
		new IP2OZ_Error_Log( $answer_arr['body_request'] );

		$args = [ 
			'body' => $answer_arr['body_request'],
			'method' => $request_type,
			'timeout' => $timeout,
			// 'redirection' => '5',
			'user-agent' => $useragent,
			// 'httpversion'=> '1.0',
			// 'blocking'	=> true,
			'headers' => $headers_arr,
			'cookies' => []
		];
		usleep( 300000 ); // притормозим на 0,3 секунды
		$result = wp_remote_request( $request_url, $args );

		if ( is_wp_error( $result ) ) {
			$answer_arr['errors'] = $result->get_error_message(); // $result->get_error_code();
			$answer_arr['body_answer'] = null;
		} else {
			$answer_arr['status'] = true; // true - получили ответ
			// Разделение полученных HTTP-заголовков и тела ответа
			$response_body = $result['body'];
			$http_code = $result['response']['code'];
			$answer_arr['http_code'] = $http_code;

			if ( $http_code == 200 ) {
				// Если HTTP-код ответа равен 200, то возвращаем отформатированное тело ответа в формате JSON
				$decoded_body = json_decode( $response_body );
				$answer_arr['body_answer'] = $decoded_body;
			} else {
				// Если тело ответа не пустое, то производится попытка декодирования JSON-кода
				if ( ! empty( $response_body ) ) {
					$decoded_body = json_decode( $response_body );
					if ( $decoded_body != null ) {
						// Если ответ содержит тело в формате JSON,
						// то возвращаем отформатированное тело в формате JSON
						$answer_arr['body_answer'] = $decoded_body;
					} else {
						// Если не удалось декодировать JSON либо тело имеет другой формат,
						// то возвращаем преобразованное тело ответа
						$answer_arr['body_answer'] = htmlspecialchars( $response_body );
					}
				} else {
					$answer_arr['body_answer'] = null;
				}
			}
			$answer_arr['header_answer'] = $result['headers']; // Заголовки ответа
		}
		return $answer_arr;
	}

	/* Getters */

	/**
	 * Get request headers to send to the OZON server
	 * 
	 * @return array
	 */
	public function get_headers_arr() {
		return [ 
			'Client-Id' => $this->get_client_id(),
			'Api-Key' => $this->get_api_key(),
			'Content-Type' => 'application/json'
		];
	}

	/**
	 * Get Client ID from OZON personal account
	 * 
	 * @return string
	 */
	private function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Get API key from OZON personal account
	 * 
	 * @return string
	 */
	private function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Get debug string. Позволяет добавить к url запроса GET-параметр для дебага
	 * 
	 * @return string
	 */
	private function get_debug() {
		return $this->debug;
	}

	/**
	 * Get feed ID
	 * 
	 * @return string
	 */
	private function get_feed_id() {
		return $this->feed_id;
	}
}