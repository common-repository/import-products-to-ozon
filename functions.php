<?php defined( 'ABSPATH' ) || exit;
/**
 * Получает ID первого фида. Используется на случай если get-параметр feed_id не указан
 * 
 * @since 0.2.0
 *
 * @return string feed ID or (string)''
 */
function ip2oz_get_first_feed_id() {
	$ip2oz_settings_arr = univ_option_get( 'ip2oz_settings_arr' );
	if ( ! empty( $ip2oz_settings_arr ) ) {
		return (string) array_key_first( $ip2oz_settings_arr );
	} else {
		return '';
	}
}

/**
 * Получает ID последнего фида
 * 
 * @since 0.2.0
 *
 * @return string feed ID or (string)''
 */
function ip2oz_get_last_feed_id() {
	$ip2oz_settings_arr = univ_option_get( 'ip2oz_settings_arr' );
	if ( ! empty( $ip2oz_settings_arr ) ) {
		return (string) array_key_last( $ip2oz_settings_arr );
	} else {
		return ip2oz_get_first_feed_id();
	}
}

/**
 * Wrapper for the `strip_tags` function
 * 
 * @since 0.7.3
 *
 * @param mixed $tag_value - Required
 * @param array $enable_tags - Optional
 *
 * @return string
 */
function ip2oz_strip_tags( $tag_value, $enable_tags = [ 'p', 'a', 'br', 'ul', 'li' ] ) {
	if ( null === $tag_value ) {
		return (string) $tag_value;
	}
	$tag_value = strip_tags( $tag_value, $enable_tags );
	return $tag_value;
}