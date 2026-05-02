<?php
/**
 * Uninstall MSC Stealth Login.
 *
 * @return void
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mscsl_options' );
delete_option( 'msc_recovery_token' );
delete_option( 'mscsl_flush_rewrite_rules' );

// Clear any scheduled events.
wp_clear_scheduled_hook( 'mscsl_brute_force_cleanup' );

// Clear login attempt transients.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall, caching not applicable
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_mscsl_login_attempts_' ) . '%'
	)
);

// Delete login attempts table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- uninstall: removing custom table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mscsl_login_attempts" );
