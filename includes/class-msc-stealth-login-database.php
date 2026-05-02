<?php
/**
 * Database class for MSC Stealth Login.
 *
 * Handles all database operations for login attempt logging.
 *
 * @package MSCSL
 */

namespace MSCSL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {

	/**
	 * Table name constant.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'mscsl_login_attempts';

	/**
	 * Create the login attempts table.
	 *
	 * @return bool True on success.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_login varchar(60) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			attempt_type varchar(20) NOT NULL DEFAULT '',
			user_agent text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY ip_address (ip_address),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		// Check for errors in dbDelta result.
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Drop the login attempts table.
	 *
	 * @return bool True on success.
	 */
	public static function drop_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from constant prefix, schema change during uninstall
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		return true;
	}

	/**
	 * Log a login attempt.
	 *
	 * @param string $ip_address  IP address.
	 * @param string $username    Username (can be 'N/A' for whitelisted).
	 * @param string $attempt_type Type: 'success', 'failure', 'lockout', 'whitelisted'.
	 * @param string $user_agent  Optional user agent string.
	 * @return bool True on success.
	 */
	public static function log_attempt( $ip_address, $username, $attempt_type, $user_agent = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table insert
		$result = $wpdb->insert(
			$table_name,
			array(
				'ip_address'   => $ip_address,
				'user_login'   => $username,
				'attempt_type' => $attempt_type,
				'user_agent'   => $user_agent,
				'created_at'   => current_time( 'mysql' ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return false !== $result;
	}

	/**
	 * Get login attempts with optional filters.
	 *
	 * @param array $args {
	 *     @type int    $limit     Number of records to fetch.
	 *     @type int    $offset    Offset for pagination.
	 *     @type string $ip         Filter by IP address.
	 *     @type string $username  Filter by username.
	 *     @type string $type      Filter by attempt type.
	 *     @type string $date_from Start date (Y-m-d).
	 *     @type string $date_to   End date (Y-m-d).
	 * }
	 * @return array Array of log entries.
	 */
	public static function get_attempts( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'     => 50,
			'offset'    => 0,
			'ip'        => '',
			'username'  => '',
			'type'      => '',
			'date_from' => '',
			'date_to'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build where clause with placeholders and corresponding values.
		$where_parts = array();
		$values      = array();

		if ( ! empty( $args['ip'] ) ) {
			$where_parts[] = 'ip_address = %s';
			$values[]      = $args['ip'];
		}

		if ( ! empty( $args['username'] ) ) {
			$where_parts[] = 'user_login = %s';
			$values[]      = $args['username'];
		}

		if ( ! empty( $args['type'] ) ) {
			$where_parts[] = 'attempt_type = %s';
			$values[]      = $args['type'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where_parts[] = 'created_at >= %s';
			$values[]      = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where_parts[] = 'created_at <= %s';
			$values[]      = $args['date_to'] . ' 23:59:59';
		}

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		// Append limit/offset values for the query.
		$values[] = $limit;
		$values[] = $offset;

		if ( ! empty( $where_parts ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table with dynamic filters, caching not applicable
			return $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table prefix from $wpdb, where built from safe %s/%d placeholders
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mscsl_login_attempts WHERE " . implode( ' AND ', $where_parts ) . ' ORDER BY created_at DESC LIMIT %d OFFSET %d', ...$values ),
				ARRAY_A
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table query, caching not applicable
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mscsl_login_attempts ORDER BY created_at DESC LIMIT %d OFFSET %d", ...$values ),
			ARRAY_A
		);
	}

	/**
	 * Get total count of attempts with filters.
	 *
	 * @param array $args Same filters as get_attempts().
	 * @return int Total count.
	 */
	public static function get_attempt_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'ip'        => '',
			'username'  => '',
			'type'      => '',
			'date_from' => '',
			'date_to'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build where clause with placeholders and corresponding values.
		$where_parts = array();
		$values      = array();

		if ( ! empty( $args['ip'] ) ) {
			$where_parts[] = 'ip_address = %s';
			$values[]      = $args['ip'];
		}

		if ( ! empty( $args['username'] ) ) {
			$where_parts[] = 'user_login = %s';
			$values[]      = $args['username'];
		}

		if ( ! empty( $args['type'] ) ) {
			$where_parts[] = 'attempt_type = %s';
			$values[]      = $args['type'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where_parts[] = 'created_at >= %s';
			$values[]      = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where_parts[] = 'created_at <= %s';
			$values[]      = $args['date_to'] . ' 23:59:59';
		}

		if ( ! empty( $where_parts ) && ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table count with dynamic filters
			return (int) $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table prefix from $wpdb, where built from safe %s placeholders
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}mscsl_login_attempts WHERE " . implode( ' AND ', $where_parts ), ...$values )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- custom table count, static query with no user input
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mscsl_login_attempts" );
	}

	/**
	 * Clear all login attempts.
	 *
	 * @return int Number of rows deleted.
	 */
	public static function clear_attempts() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- admin-only truncation of custom table
		$deleted = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}mscsl_login_attempts" );

		return false !== $deleted ? $deleted : 0;
	}

	/**
	 * Delete attempts older than specified days.
	 *
	 * @param int $days Number of days.
	 * @return int Number of rows deleted.
	 */
	public static function delete_old_attempts( $days = 30 ) {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table delete with prepare
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}mscsl_login_attempts WHERE created_at < %s",
				$cutoff
			)
		);

		return false !== $deleted ? $deleted : 0;
	}

	/**
	 * Get a single attempt by ID.
	 *
	 * @param int $id Attempt ID.
	 * @return array|null Attempt data or null.
	 */
	public static function get_attempt_by_id( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table query by primary key
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mscsl_login_attempts WHERE id = %d",
				absint( $id )
			),
			ARRAY_A
		);

		return $result;
	}

	/**
	 * Export attempts to CSV format.
	 *
	 * @param array $args Same filters as get_attempts().
	 * @return string CSV data.
	 */
	public static function export_to_csv( $args = array() ) {
		$attempts = self::get_attempts( $args );

		if ( empty( $attempts ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- php://temp stream for CSV generation, not filesystem.
		$output = fopen( 'php://temp', 'r+' );

		// CSV header row.
		fputcsv(
			$output,
			array(
				__( 'ID', 'msc-stealth-login' ),
				__( 'IP Address', 'msc-stealth-login' ),
				__( 'Username', 'msc-stealth-login' ),
				__( 'Result', 'msc-stealth-login' ),
				__( 'User Agent', 'msc-stealth-login' ),
				__( 'Date/Time', 'msc-stealth-login' ),
			)
		);

		foreach ( $attempts as $attempt ) {
			fputcsv(
				$output,
				array(
					absint( $attempt['id'] ),
					sanitize_text_field( $attempt['ip_address'] ),
					sanitize_text_field( $attempt['user_login'] ),
					sanitize_key( $attempt['attempt_type'] ),
					sanitize_textarea_field( $attempt['user_agent'] ?? '' ),
					sanitize_text_field( $attempt['created_at'] ),
				)
			);
		}

		rewind( $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_stream_get_contents -- php://temp stream.
		$csv = stream_get_contents( $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://temp stream, not filesystem.
		fclose( $output );

		return $csv;
	}
}
