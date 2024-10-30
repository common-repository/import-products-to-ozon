<?php
/**
 * The main class of the plugin Import Products to OZON
 *
 * @package                 Import Products to OZON
 * @subpackage              
 * @since                   0.1.0
 * 
 * @version                 0.7.2 (23-07-2024)
 * @author                  Maxim Glazunov
 * @link                    https://icopydoc.ru/
 * @see                     
 * 
 * @param        
 *
 * @depends                 classes:    IP2OZ_Data_Arr
 *                                      IP2OZ_Settings_Page
 *                                      IP2OZ_Debug_Page
 *                                      IP2OZ_Extensions_Page
 *                                      IP2OZ_Error_Log
 *                                      IP2OZ_Generation_XML
 *                                      IP2OZ_Api_Helper
 *                          traits:     
 *                          methods:    
 *                          functions:  common_option_get
 *                                      common_option_upd
 *                          constants:  IP2OZ_PLUGIN_VERSION
 *                                      IP2OZ_PLUGIN_BASENAME
 *                                      IP2OZ_PLUGIN_DIR_URL
 */
defined( 'ABSPATH' ) || exit;

final class IP2OZ {
	/**
	 * Plugin version
	 * @var string
	 */
	private $plugin_version = IP2OZ_PLUGIN_VERSION; // 0.1.0

	protected static $instance;
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Срабатывает при активации плагина (вызывается единожды)
	 * 
	 * @return void
	 */
	public static function on_activation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( is_multisite() ) {
			add_blog_option( get_current_blog_id(), 'ip2oz_keeplogs', '' );
			add_blog_option( get_current_blog_id(), 'ip2oz_disable_notices', '' );
			add_blog_option( get_current_blog_id(), 'ip2oz_group_content', '' );

			add_blog_option( get_current_blog_id(), 'ip2oz_settings_arr', [] );
			// add_blog_option(get_current_blog_id(), 'ip2oz_registered_groups_arr', [ ]);
		} else {
			add_option( 'ip2oz_keeplogs', '' );
			add_option( 'ip2oz_disable_notices', '' );
			add_option( 'ip2oz_group_content', '' );

			add_option( 'ip2oz_settings_arr', [] );
			// add_option('ip2oz_registered_groups_arr', [ ]);
		}
	}

	/**
	 * Срабатывает при отключении плагина (вызывается единожды)
	 * 
	 * @return void
	 */
	public static function on_deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		wp_clear_scheduled_hook( 'ip2oz_cron_period', [ '1' ] );
		wp_clear_scheduled_hook( 'ip2oz_cron_sborki', [ '1' ] );
	}

	/**
	 * The main class of the plugin Import Products to OZON
	 */
	public function __construct() {
		$this->check_options_upd(); // проверим, нужны ли обновления опций плагина
		$this->init_classes();
		$this->init_hooks(); // подключим хуки
	}

	/**
	 * Checking whether the plugin options need to be updated
	 * 
	 * @return void
	 */
	public function check_options_upd() {
		if ( false == common_option_get( 'ip2oz_version' ) ) { // это первая установка
			$ip2oz_data_arr_obj = new IP2OZ_Data_Arr();
			$opts_arr = $ip2oz_data_arr_obj->get_opts_name_and_def_date( 'all' ); // массив дефолтных настроек
			common_option_upd( 'ip2oz_settings_arr', $opts_arr, 'no', '1' ); // пишем все настройки
			if ( is_multisite() ) {
				update_blog_option( get_current_blog_id(), 'ip2oz_version', $this->plugin_version );
			} else {
				update_option( 'ip2oz_version', $this->plugin_version );
			}
		} else {
			$this->set_new_options();
		}
	}

	/**
	 * Initialization classes
	 * 
	 * @return void
	 */
	public function init_classes() {
		new IP2OZ_Interface_Hoocked();
		new ICPD_Feedback( [ 
			'plugin_name' => 'Import products to OZON',
			'plugin_version' => $this->get_plugin_version(),
			'logs_url' => IP2OZ_PLUGIN_UPLOADS_DIR_URL . '/plugin.log',
			'pref' => 'ip2oz'
		] );
		new IP2OZ_Api();
		new ICPD_Promo( 'ip2oz' );
		return;
	}

	/**
	 * Initialization hooks
	 * 
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_init', [ $this, 'listen_submits' ], 9 ); // ещё можно слушать чуть раньше на wp_loaded
		add_action( 'admin_init', function () {
			wp_register_style( 'ip2oz-admin-css', IP2OZ_PLUGIN_DIR_URL . 'assets/css/ip2oz-style.css' );
		}, 9999 ); // Регаем стили только для страницы настроек плагина
		add_action( 'admin_enqueue_scripts', [ &$this, 'reg_script' ] ); // правильно регаем скрипты в админку
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 10, 1 );

		add_action( 'ip2oz_cron_sborki', [ $this, 'do_this_seventy_sec' ], 10, 1 );
		add_action( 'ip2oz_cron_period', [ $this, 'do_this_event' ], 10, 1 );

		add_filter( 'cron_schedules', [ $this, 'add_cron_intervals' ], 10, 1 );
		add_filter( 'plugin_action_links', [ $this, 'add_plugin_action_links' ], 10, 2 );
	}

	/**
	 * Function for `admin_init` action-hook.
	 * 
	 * @return void
	 */
	public function listen_submits() {
		do_action( 'ip2oz_listen_submits' );

		if ( isset( $_REQUEST['ip2oz_submit_action'] ) ) {
			$message = __( 'Updated', 'import-products-to-ozon' );
			$class = 'notice-success';

			add_action( 'admin_notices', function () use ($message, $class) {
				$this->admin_notices_func( $message, $class );
			}, 10, 2 );
		}

		$status_sborki = (int) common_option_get( 'status_sborki', false, '1', 'ip2oz' );
		$step_export = (int) common_option_get( 'step_export', false, '1', 'ip2oz' );

		if ( $status_sborki == 1 ) {
			$message = sprintf( 'IP2OZ: %1$s. %2$s: 1. %3$s',
				__( 'Import products is running', 'import-products-to-ozon' ),
				__( 'Step', 'import-products-to-ozon' ),
				__( 'Importing a list of categories', 'import-products-to-ozon' )
			);
		} else if ( $status_sborki > 1 ) {
			$message = sprintf( 'IP2OZ: %1$s. %2$s: 2. %3$s %4$s',
				__( 'Import products is running', 'import-products-to-ozon' ),
				__( 'Step', 'import-products-to-ozon' ),
				__( 'Processed products', 'import-products-to-ozon' ),
				$status_sborki * $step_export
			);
		} else {
			$message = '';
		}

		if ( ! empty( $message ) ) {
			$class = 'notice-success';
			add_action( 'admin_notices', function () use ($message, $class) {
				$this->admin_notices_func( $message, $class );
			}, 10, 2 );
		}
	}

	/**
	 * Function for `admin_enqueue_scripts` action-hook.
	 * 
	 * @param string $hook_suffix The current admin page.
	 *
	 * @return void
	 */
	public function reg_script() {
		// https://daext.com/blog/how-to-add-select2-in-wordpress/
		wp_enqueue_script( 'select2-js', IP2OZ_PLUGIN_DIR_URL . 'assets/js/select2.min.js', [ 'jquery' ] );
		wp_enqueue_script( 'ip2oz-select2-init', IP2OZ_PLUGIN_DIR_URL . 'assets/js/select2-init.js', [ 'jquery' ] );
		// wp_enqueue_style( 'ip2oz-select2-css', IP2OZ_PLUGIN_DIR_URL . 'assets/css/select2.min.css', [] );
	}

	/**
	 * Add items to admin menu. Function for `admin_menu` action-hook.
	 * 
	 * @param string $context Empty context
	 * 
	 * @return void
	 */
	public function add_admin_menu() {
		$page_suffix = add_menu_page(
			null,
			'Import Products to OZON',
			'manage_woocommerce',
			'ip2oz-import',
			[ $this, 'get_plugin_settings_page' ],
			'dashicons-redo',
			51
		);
		// создаём хук, чтобы стили выводились только на странице настроек
		add_action( 'admin_print_styles-' . $page_suffix, [ $this, 'admin_enqueue_style_css' ] );

		$page_suffix = add_submenu_page(
			'ip2oz-import',
			__( 'Debug', 'import-products-to-ozon' ),
			__( 'Debug page', 'import-products-to-ozon' ),
			'manage_woocommerce',
			'ip2oz-debug',
			[ $this, 'get_debug_page' ]
		);
		add_action( 'admin_print_styles-' . $page_suffix, [ $this, 'admin_enqueue_style_css' ] );

		$page_suffix = add_submenu_page(
			'ip2oz-import',
			__( 'Add Extensions', 'import-products-to-ozon' ),
			__( 'Add Extensions', 'import-products-to-ozon' ),
			'manage_woocommerce',
			'ip2oz-extensions',
			[ $this, 'get_extensions_page' ]
		);
		add_action( 'admin_print_styles-' . $page_suffix, [ $this, 'admin_enqueue_style_css' ] );
	}

	/**
	 * Вывод страницы настроек плагина
	 * 
	 * @return void
	 */
	public function get_plugin_settings_page() {
		new IP2OZ_Settings_Page();
		return;
	}

	/**
	 * Вывод страницы отладки плагина
	 * 
	 * @return void
	 */
	public function get_debug_page() {
		new IP2OZ_Debug_Page( 'ip2oz' );
		return;
	}

	/**
	 * Вывод страницы расширений плагина
	 * 
	 * @return void
	 */
	public function get_extensions_page() {
		new IP2OZ_Extensions_Page();
		return;
	}

	/**
	 * Get plugin version
	 * 
	 * @return string
	 */
	public function get_plugin_version() {
		if ( is_multisite() ) {
			$v = get_blog_option( get_current_blog_id(), 'ip2oz_version' );
		} else {
			$v = get_option( 'ip2oz_version' );
		}
		return $v;
	}

	/**
	 * Register the style sheet on separate pages of our plugin.
	 * Function for `admin_print_styles-[page_suffix]` action-hook.
	 * 
	 * @return void
	 */
	public function admin_enqueue_style_css() {
		wp_enqueue_style( 'ip2oz-admin-css' ); // Ставим css-файл в очередь на вывод
	}

	/**
	 * of do_this_seventy_sec
	 * 
	 * @param string $feed_id
	 * 
	 * @return void
	 */
	public function do_this_seventy_sec( $feed_id ) {
		// условие исправляет возможные ошибки и повторное создание удаленного фида
		if ( $feed_id === (int) 1 || $feed_id === (float) 1 ) {
			$feed_id = (string) $feed_id;
		}
		if ( $feed_id == '' ) {
			common_option_upd( 'status_sborki', '-1', 'no', $feed_id, 'ip2oz' );
			wp_clear_scheduled_hook( 'ip2oz_cron_sborki', [ $feed_id ] );
			wp_clear_scheduled_hook( 'ip2oz_cron_period', [ $feed_id ] );
			return;
		}

		new IP2OZ_Error_Log( 'Cтартовала крон-задача do_this_seventy_sec' );
		$generation = new IP2OZ_Generation_XML( $feed_id ); // делаем что-либо каждые 70 сек
		$generation->run();
	}

	/**
	 * of do_this_seventy_sec
	 * 
	 * @param string $feed_id
	 * 
	 * @return void
	 */
	public function do_this_event( $feed_id ) {
		// условие исправляет возможные ошибки и повторное создание удаленного фида
		if ( $feed_id === (int) 1 || $feed_id === (float) 1 ) {
			$feed_id = (string) $feed_id;
		}
		if ( $feed_id == '' ) {
			common_option_upd( 'status_sborki', '-1', 'no', $feed_id, 'ip2oz' );
			wp_clear_scheduled_hook( 'ip2oz_cron_sborki', [ $feed_id ] );
			wp_clear_scheduled_hook( 'ip2oz_cron_period', [ $feed_id ] );
			return;
		}

		new IP2OZ_Error_Log(
			sprintf( 'FEED № %1$s; Крон-функция do_this_event включена согласно интервала; Файл: %2$s; Строка: %3$s',
				$feed_id,
				'class-ip2oz.php',
				__LINE__
			)
		);
		$step_export = (int) common_option_get( 'step_export', false, $feed_id, 'ip2oz' );
		common_option_upd( 'status_sborki', '1', 'no', $feed_id, 'ip2oz' );
		wp_clear_scheduled_hook( 'ip2oz_cron_sborki', [ $feed_id ] );

		// Возвращает nul/false. null когда планирование завершено. false в случае неудачи.
		$res = wp_schedule_event( time(), 'seventy_sec', 'ip2oz_cron_sborki', [ $feed_id ] );
		if ( false === $res ) {
			new IP2OZ_Error_Log(
				sprintf( 'FEED № %1$s; ERROR: Не удалось запланировань CRON seventy_sec; Файл: %2$s; Строка: %3$s',
					$feed_id,
					'class-ip2oz.php',
					__LINE__
				)
			);
		} else {
			new IP2OZ_Error_Log(
				sprintf( 'FEED № %1$s; CRON seventy_sec успешно запланирован; Файл: %2$s; Строка: %3$s',
					$feed_id,
					'class-ip2oz.php',
					__LINE__
				)
			);
		}
	}

	/**
	 * Add cron intervals to WordPress. Function for `cron_schedules` filter-hook.
	 * 
	 * @param array $new_schedules An array of non-default cron schedules keyed by the schedule name.
	 * 
	 * @return array
	 */
	public function add_cron_intervals( $schedules ) {
		$schedules['seventy_sec'] = [ 
			'interval' => 70,
			'display' => __( '70 seconds', 'import-products-to-ozon' )
		];
		$schedules['three_hours'] = [ 
			'interval' => 10800,
			'display' => __( '3 hours', 'import-products-to-ozon' )
		];
		$schedules['six_hours'] = [ 
			'interval' => 21600,
			'display' => __( '6 hours', 'import-products-to-ozon' )
		];
		$schedules['every_two_days'] = [ 
			'interval' => 172800,
			'display' => __( 'Every two days', 'import-products-to-ozon' )
		];
		$schedules['week'] = [ 
			'interval' => 604800,
			'display' => __( '1 week', 'import-products-to-ozon' )
		];
		return $schedules;
	}

	/**
	 * Function for `plugin_action_links` action-hook.
	 * 
	 * @param string[] $actions An array of plugin action links. By default this can include 'activate', 'deactivate', and 'delete'
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory
	 * 
	 * @return string[]
	 */
	public function add_plugin_action_links( $actions, $plugin_file ) {
		if ( false === strpos( $plugin_file, IP2OZ_PLUGIN_BASENAME ) ) { // проверка, что у нас текущий плагин
			return $actions;
		}

		$settings_link = sprintf( '<a style="%s" href="/wp-admin/admin.php?page=%s">%s</a>',
			'color: green; font-weight: 700;',
			'ip2oz-extensions',
			__( 'More features', 'import-products-to-ozon' )
		);
		array_unshift( $actions, $settings_link );

		$settings_link = sprintf( '<a href="/wp-admin/admin.php?page=%s">%s</a>',
			'ip2oz-import',
			__( 'Settings', 'import-products-to-ozon' )
		);
		array_unshift( $actions, $settings_link );

		return $actions;
	}

	/**
	 * Set new plugin options
	 * 
	 * @return void
	 */
	private function set_new_options() {
		// Если предыдущая версия плагина меньше текущей
		if ( version_compare( $this->get_plugin_version(), $this->plugin_version, '<' ) ) {
			new IP2OZ_Error_Log( sprintf( '%1$s (%2$s < %3$s). %4$s; Файл: %5$s; Строка: %6$s',
				'Предыдущая версия плагина меньше текущей',
				(string) $this->get_plugin_version(),
				(string) $this->plugin_version,
				'Обновляем опции плагина',
				'class-ip2oz.php',
				__LINE__
			) );
		} else { // обновления не требуются
			return;
		}

		// получаем список дефолтных настроек
		$ip2oz_data_arr_obj = new IP2OZ_Data_Arr();
		$default_settings_obj = $ip2oz_data_arr_obj->get_opts_name_and_def_date_obj( 'all' );
		// проверим, заданы ли дефолтные настройки
		$settings_arr = univ_option_get( 'ip2oz_settings_arr' );
		$feed_id = '1'; // * если будет несколько фидов, то надо будет ещё один цикл
		for ( $i = 0; $i < count( $default_settings_obj ); $i++ ) {
			$name = $default_settings_obj[ $i ]->name;
			$value = $default_settings_obj[ $i ]->opt_def_value;
			if ( ! isset( $settings_arr[ $feed_id ][ $name ] ) ) {
				// если какой-то опции нет - добавим в БД
				common_option_upd( $name, $value, 'no', $feed_id, 'ip2oz' ); // $settings_arr[ $name ] = $value;
			}
		}

		if ( is_multisite() ) {
			update_blog_option( get_current_blog_id(), 'ip2oz_version', $this->plugin_version );
		} else {
			update_option( 'ip2oz_version', $this->plugin_version );
		}
	}

	/**
	 * Print admin notice
	 * 
	 * @param string $message
	 * @param string $class
	 * 
	 * @return void
	 */
	private function admin_notices_func( $message, $class ) {
		$ip2oz_disable_notices = common_option_get( 'ip2oz_disable_notices' );
		if ( $ip2oz_disable_notices === 'on' ) {
			return;
		} else {
			printf( '<div class="notice %1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			return;
		}
	}
} /* end class IP2OZ */