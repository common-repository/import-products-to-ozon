<?php defined( 'ABSPATH' ) || exit;
/**
 * Sandbox function
 * 
 * @since	0.1.0
 * @version 0.4.0 (01-06-2024)
 *
 * @return	void
 */
function ip2oz_run_sandbox() {
	$x = false; // установите true, чтобы использовать песочницу
	if ( true === $x ) {
		printf( '%s<br/>',
			esc_html__( __( 'The sandbox is working. The result will appear below', 'import-products-to-ozon' ) )
		);
		$time_start = microtime( true );
		/* вставьте ваш код ниже */
		// Example:
		// $product = wc_get_product(8303);
		// echo $product->get_price();
		// $api = new IP2OZ_Api();
		// $api->product_info('offer-357');
		// var_dump($api->check_task_id('815284616'));
				// Example:
		// $product = wc_get_product(8303);
		// echo $product->get_price();
		// $response = wp_remote_request( 'https://api.partner.market.yandex.ru/businesses/12345678/offers/stock', [ 
		//	'method' => 'PUT',
		//	'headers' => [ 
		//		'Content-Type' => 'application/json',
		//		'Cache-Control' => 'no-cache',
		//		'Authorization' => 'Bearer y0_A*******************************bg'
		//	],
		//	'body'=> '{"skus":[{"sku":"test-214","items":[{"count":2}]}]}'
		// ] );

		/* дальше не редактируем */
		$time_end = microtime( true );
		$time = $time_end - $time_start;
		printf( '<br/>%s<br/>%s %d %s',
			esc_html__( __( 'The sandbox is working correctly', 'import-products-to-ozon' ) ),
			esc_html__( __( 'The execution time of the test script was', 'import-products-to-ozon' ) ),
			esc_html( $time ),
			esc_html__( __( 'seconds', 'import-products-to-ozon' ) )
		);
	} else {
		printf( '%s sanbox.php',
			esc_html__( __( 'The sandbox is not active. To activate, edit the file', 'import-products-to-ozon' ) )
		);
	}
}