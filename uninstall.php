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
delete_option( 'mscsl_recovery_token' );
delete_option( 'msc_recovery_token' );
delete_transient( 'mscsl_flush_rewrite_rules' );
delete_option( 'mscsl_db_version' );

// Clear any scheduled events.
wp_clear_scheduled_hook( 'mscsl_brute_force_cleanup' );

// Delete user meta and clean up transients/table.
global $wpdb;
delete_metadata( 'user', 0, 'mscsl_data_notice_dismissed', null, true );
delete_metadata( 'user', 0, 'mscsl_known_ips', null, true );

// Clear login attempt transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall, caching not applicable
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_mscsl_login_attempts_' ) . '%'
	)
);

// Clear lockout multiplier transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall, caching not applicable
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_mscsl_lockout_multiplier_' ) . '%'
	)
);

// Clear transient timeout rows for login attempts.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall, caching not applicable
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_mscsl_login_attempts_' ) . '%'
	)
);

// Clear transient timeout rows for lockout multipliers.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_mscsl_lockout_multiplier_' ) . '%'
	)
);

// Delete login attempts table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- uninstall: removing custom table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mscsl_login_attempts" );
