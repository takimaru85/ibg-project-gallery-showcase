<?php
/**
 * Uninstall handler for WordPress Project Gallery.
 *
 * Removes only the plugin's own settings. Project posts, meta, and the
 * project_category taxonomy terms are left in place so users never lose
 * content simply by deleting the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpg_settings' );
delete_option( 'wpg_needs_rewrite_flush' );
delete_option( 'wpg_version' );
delete_option( 'wpg_default_terms_created' );

// Clean up on multisite installs as well.
if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'wpg_settings' );
		delete_option( 'wpg_needs_rewrite_flush' );
		delete_option( 'wpg_version' );
		delete_option( 'wpg_default_terms_created' );
		restore_current_blog();
	}
}
