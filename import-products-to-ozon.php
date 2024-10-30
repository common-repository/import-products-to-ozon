<?php defined( 'ABSPATH' ) || exit;
/**
 * Plugin Name: Import products to OZON
 * Requires Plugins: woocommerce
 * Plugin URI: https://icopydoc.ru/category/documentation/import-products-to-ozon/
 * Description: Plugin for importing products from WooCommerce to OZON. Helps to increase sales.
 * Version: 0.7.3
 * Requires at least: 4.7
 * Requires PHP: 7.4.0
 * Author: Maxim Glazunov
 * Author URI: https://icopydoc.ru
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: import-products-to-ozon
 * Domain Path: /languages
 * Tags: ozon, import, products, export, woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 9.1.4
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * Copyright 2023-2024 (Author emails: djdiplomat@yandex.ru, support@icopydoc.ru)
 */

$nr = false;
// Check php version
if ( version_compare( phpversion(), '7.4.0', '<' ) ) { // не совпали версии
	add_action( 'admin_notices', function () {
		warning_notice( 'notice notice-error',
			sprintf(
				'<strong style="font-weight: 700;">%1$s</strong> %2$s 7.4.0 %3$s %4$s',
				'Import products to OZON',
				__( 'plugin requires a php version of at least', 'import-products-to-ozon' ),
				__( 'You have the version installed', 'import-products-to-ozon' ),
				phpversion()
			)
		);
	} );
	$nr = true;
}

// Check if WooCommerce is active
$plugin = 'woocommerce/woocommerce.php';
if ( ! in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) ) )
	&& ! ( is_multisite()
		&& array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', [] ) ) )
) {
	add_action( 'admin_notices', function () {
		warning_notice(
			'notice notice-error',
			sprintf(
				'<strong style="font-weight: 700;">Import products to OZON</strong> %1$s',
				__( 'requires WooCommerce installed and activated', 'import-products-to-ozon' )
			)
		);
	} );
	$nr = true;
} else {
	// поддержка HPOS
	add_action( 'before_woocommerce_init', function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );
}

if ( ! function_exists( 'warning_notice' ) ) {
	/**
	 * Display a notice in the admin Plugins page. Usually used in a @hook 'admin_notices'
	 * 
	 * @since 0.1.0
	 * 
	 * @param string $class - Optional
	 * @param string $message - Optional
	 * 
	 * @return void
	 */
	function warning_notice( $class = 'notice', $message = '' ) {
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}

// Define constants
define( 'IP2OZ_PLUGIN_VERSION', '0.7.3' );

$upload_dir = wp_get_upload_dir();
// https://site.ru/wp-content/uploads
define( 'IP2OZ_SITE_UPLOADS_URL', $upload_dir['baseurl'] );

// /home/site.ru/public_html/wp-content/uploads
define( 'IP2OZ_SITE_UPLOADS_DIR_PATH', $upload_dir['basedir'] );

// https://site.ru/wp-content/uploads/import-products-to-ozon
define( 'IP2OZ_PLUGIN_UPLOADS_DIR_URL', $upload_dir['baseurl'] . '/import-products-to-ozon' );

// /home/site.ru/public_html/wp-content/uploads/import-products-to-ozon
define( 'IP2OZ_PLUGIN_UPLOADS_DIR_PATH', $upload_dir['basedir'] . '/import-products-to-ozon' );
unset( $upload_dir );

// https://site.ru/wp-content/plugins/import-products-to-ozon/
define( 'IP2OZ_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

// /home/p135/www/site.ru/wp-content/plugins/import-products-to-ozon/
define( 'IP2OZ_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

// /home/p135/www/site.ru/wp-content/plugins/import-products-to-ozon/import-products-to-ozon.php
define( 'IP2OZ_PLUGIN_MAIN_FILE_PATH', __FILE__ );

// import-products-to-ozon - псевдоним плагина
define( 'IP2OZ_PLUGIN_SLUG', wp_basename( dirname( __FILE__ ) ) );

// import-products-to-ozon/import-products-to-ozon.php - полный псевдоним плагина (папка плагина + имя главного файла)
define( 'IP2OZ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// $nr = apply_filters('ip2oz_f_nr', $nr);

// load translation
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'import-products-to-ozon', false, dirname( IP2OZ_PLUGIN_BASENAME ) . '/languages/' );
} );

if ( false === $nr ) {
	unset( $nr );
	require_once IP2OZ_PLUGIN_DIR_PATH . '/packages.php';
	register_activation_hook( __FILE__, [ 'IP2OZ', 'on_activation' ] );
	register_deactivation_hook( __FILE__, [ 'IP2OZ', 'on_deactivation' ] );
	add_action( 'plugins_loaded', [ 'IP2OZ', 'init' ], 10 ); // активируем плагин
	define( 'IP2OZ_ACTIVE', true );
}