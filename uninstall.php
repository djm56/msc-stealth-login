<?php
/**
 * Uninstall MSC Stealth Login.
 *
 * On multisite every site keeps its own options, transients and login-attempt
 * table, so the cleanup runs once per site.
 *
 * @return void
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! function_exists( 'mscsl_uninstall_site' ) ) {
	/**
	 * Remove all plugin data belonging to the current site.
	 *
	 * @return void
	 */
	function mscsl_uninstall_site() {
		global $wpdb;

		delete_option( 'mscsl_options' );
		delete_option( 'mscsl_recovery_token' );
		delete_option( 'mscsl_db_version' );
		delete_option( 'mscsl_activated_time' );
		delete_option( 'mscsl_review_dismissed' );
		delete_transient( 'mscsl_flush_rewrite_rules' );

		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'mscsl_brute_force_cleanup' );

		// Clear login attempt and lockout multiplier transients, including
		// their timeout rows.
		$transient_prefixes = array(
			'_transient_mscsl_login_attempts_',
			'_transient_timeout_mscsl_login_attempts_',
			'_transient_mscsl_lockout_multiplier_',
			'_transient_timeout_mscsl_lockout_multiplier_',
		);

		foreach ( $transient_prefixes as $prefix ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall, caching not applicable
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$wpdb->esc_like( $prefix ) . '%'
				)
			);
		}

		// Delete login attempts table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- uninstall: removing custom table, table name from $wpdb prefix
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mscsl_login_attempts" );
	}
}

if ( is_multisite() ) {
	global $wpdb;

	// Network-wide (site) transients used when lockouts are shared.
	$mscsl_network_transient_prefixes = array(
		'_site_transient_mscsl_login_attempts_',
		'_site_transient_timeout_mscsl_login_attempts_',
		'_site_transient_mscsl_lockout_multiplier_',
		'_site_transient_timeout_mscsl_lockout_multiplier_',
	);

	foreach ( $mscsl_network_transient_prefixes as $mscsl_network_prefix ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup during uninstall, caching not applicable
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
				$wpdb->esc_like( $mscsl_network_prefix ) . '%'
			)
		);
	}

	// Walk the network in batches so large networks do not exhaust memory.
	$mscsl_batch_size = 100;
	$mscsl_offset     = 0;

	do {
		$mscsl_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $mscsl_batch_size,
				'offset' => $mscsl_offset,
			)
		);

		$mscsl_found = count( $mscsl_site_ids );

		foreach ( $mscsl_site_ids as $mscsl_site_id ) {
			switch_to_blog( (int) $mscsl_site_id );
			mscsl_uninstall_site();
			restore_current_blog();
		}

		$mscsl_offset += $mscsl_batch_size;
	} while ( $mscsl_found === $mscsl_batch_size );
} else {
	mscsl_uninstall_site();
}

// User meta is global on multisite, so this only needs doing once.
delete_metadata( 'user', 0, 'mscsl_data_notice_dismissed', null, true );
delete_metadata( 'user', 0, 'mscsl_known_ips', null, true );
