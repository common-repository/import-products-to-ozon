<?php defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
if ( is_multisite() ) {
	delete_blog_option( get_current_blog_id(), 'ip2oz_version' );
	delete_blog_option( get_current_blog_id(), 'ip2oz_keeplogs' );
	delete_blog_option( get_current_blog_id(), 'ip2oz_disable_notices' );
	delete_blog_option( get_current_blog_id(), 'ip2oz_settings_arr' );
	// delete_blog_option(get_current_blog_id(), 'ip2oz_registered_groups_arr');
} else {
	delete_option( 'ip2oz_version' );
	delete_option( 'ip2oz_keeplogs' );
	delete_option( 'ip2oz_disable_notices' );
	delete_option( 'ip2oz_settings_arr' );
	// delete_option('ip2oz_registered_groups_arr');
}