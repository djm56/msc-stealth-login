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
	 * Allowed attempt types for filtering.
	 *
	 * @var array
	 */
	const ALLOWED_TYPES = array( 'success', 'failure', 'lockout', 'whitelisted' );

	/**
	 * Maximum number of rows a single query may return.
	 *
	 * Sized for the CSV export, which asks for 10,000 rows.
	 *
	 * @since 1.3.0
	 *
	 * @var int
	 */
	const MAX_LIMIT = 10000;

	/**
	 * Current database schema version.
	 *
	 * Increment this when the table schema changes.
	 * The maybe_upgrade() method will run any pending migrations.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.5';

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
	 * Check if database needs upgrading and run migrations.
	 *
	 * @since 1.0.5
	 *
	 * Compares the stored database version against the current
	 * DB_VERSION constant. Runs any pending migrations if needed.
	 *
	 * @return bool True if no upgrades needed or upgrades succeeded.
	 */
	public static function maybe_upgrade() {
		$current_version = get_option( 'mscsl_db_version', '' );

		// No upgrade needed if versions match.
		if ( $current_version === self::DB_VERSION ) {
			return true;
		}

		// First install — just create table and set version.
		if ( empty( $current_version ) ) {
			self::create_table();
			update_option( 'mscsl_db_version', self::DB_VERSION );
			return true;
		}

		// Future migrations go here.
		// Example: if ( version_compare( $current_version, '1.1.0', '<' ) ) { ... }

		// Update the stored version.
		update_option( 'mscsl_db_version', self::DB_VERSION );
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

		if ( false !== $result ) {
			wp_cache_set( 'last_changed', microtime(), 'mscsl' );
		}

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

		// Scalar type validation – default non-strings to empty string.
		$ip        = is_string( $args['ip'] ) ? $args['ip'] : '';
		$username  = is_string( $args['username'] ) ? $args['username'] : '';
		$type      = is_string( $args['type'] ) ? $args['type'] : '';
		$date_from = is_string( $args['date_from'] ) ? $args['date_from'] : '';
		$date_to   = is_string( $args['date_to'] ) ? $args['date_to'] : '';

		// Input validation.
		if ( '' !== $ip && false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}
		if ( '' !== $type && ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
			$type = '';
		}
		if ( '' !== $date_from && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$date_from = '';
		}
		if ( '' !== $date_to && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$date_to = '';
		}
		$username  = sanitize_text_field( $username );

		// Apply time boundaries to validated date strings.
		$date_from = ! empty( $date_from ) ? $date_from . ' 00:00:00' : '';
		$date_to   = ! empty( $date_to ) ? $date_to . ' 23:59:59' : '';

		// Cap limit to prevent excessive result sets.
		$limit  = min( absint( $args['limit'] ), self::MAX_LIMIT );
		$offset = absint( $args['offset'] );

		// Cache lookup — use last_changed key to auto-invalidate when data changes.
		$last_changed = self::get_last_changed();
		$cache_key    = "get_attempts:{$last_changed}:" . md5( wp_json_encode( array( $ip, $username, $type, $date_from, $date_to, $limit, $offset ) ) );
		$cached       = wp_cache_get( $cache_key, 'mscsl' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Build WHERE clause dynamically to avoid MySQL strict-mode DATETIME errors.
		list( $where_sql, $values ) = self::build_where(
			compact( 'ip', 'username', 'type', 'date_from', 'date_to' )
		);

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table; caching handled above.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mscsl_login_attempts WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				...$values
			),
			ARRAY_A
		);

		wp_cache_set( $cache_key, $results, 'mscsl' );

		return $results;
	}

	/**
	 * Build the WHERE clause and bound values for the log filters.
	 *
	 * The returned SQL contains only hard-coded column comparisons plus
	 * placeholders — never a caller-supplied string — so it is safe to
	 * interpolate into the query before handing it to $wpdb->prepare().
	 *
	 * @since 1.3.0
	 *
	 * @param array<string,string> $filters Validated filter values.
	 * @return array{0:string,1:array<int,string>} WHERE SQL and bound values.
	 */
	private static function build_where( $filters ) {
		$columns = array(
			'ip'        => 'ip_address = %s',
			'username'  => 'user_login = %s',
			'type'      => 'attempt_type = %s',
			'date_from' => 'created_at >= %s',
			'date_to'   => 'created_at <= %s',
		);

		$where  = array( '1=1' );
		$values = array();

		foreach ( $columns as $key => $fragment ) {
			if ( isset( $filters[ $key ] ) && '' !== $filters[ $key ] ) {
				$where[]  = $fragment;
				$values[] = $filters[ $key ];
			}
		}

		return array( implode( ' AND ', $where ), $values );
	}

	/**
	 * Get the cache-busting "last changed" marker for the mscsl cache group.
	 *
	 * Core's wp_cache_get_last_changed() only exists in WordPress 6.3 and
	 * later, and this plugin supports 5.9+, so fall back to reading the marker
	 * directly on older versions.
	 *
	 * @since 1.3.0
	 *
	 * @return string Last changed marker.
	 */
	private static function get_last_changed() {
		if ( function_exists( 'wp_cache_get_last_changed' ) ) {
			return (string) wp_cache_get_last_changed( 'mscsl' );
		}

		$last_changed = wp_cache_get( 'last_changed', 'mscsl' );

		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'mscsl' );
		}

		return (string) $last_changed;
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

		// Scalar type validation – default non-strings to empty string.
		$ip        = is_string( $args['ip'] ) ? $args['ip'] : '';
		$username  = is_string( $args['username'] ) ? $args['username'] : '';
		$type      = is_string( $args['type'] ) ? $args['type'] : '';
		$date_from = is_string( $args['date_from'] ) ? $args['date_from'] : '';
		$date_to   = is_string( $args['date_to'] ) ? $args['date_to'] : '';

		// Input validation.
		if ( '' !== $ip && false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}
		if ( '' !== $type && ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
			$type = '';
		}
		if ( '' !== $date_from && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$date_from = '';
		}
		if ( '' !== $date_to && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$date_to = '';
		}
		$username  = sanitize_text_field( $username );

		// Apply time boundaries to validated date strings.
		$date_from = ! empty( $date_from ) ? $date_from . ' 00:00:00' : '';
		$date_to   = ! empty( $date_to ) ? $date_to . ' 23:59:59' : '';

		// Cache lookup — use last_changed key to auto-invalidate when data changes.
		$last_changed = self::get_last_changed();
		$cache_key    = "get_attempt_count:{$last_changed}:" . md5( wp_json_encode( array( $ip, $username, $type, $date_from, $date_to ) ) );
		$cached       = wp_cache_get( $cache_key, 'mscsl' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Build WHERE clause dynamically to avoid MySQL strict-mode DATETIME errors.
		list( $where_sql, $values ) = self::build_where(
			compact( 'ip', 'username', 'type', 'date_from', 'date_to' )
		);

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table; caching handled above.
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mscsl_login_attempts WHERE {$where_sql}",
					...$values
				)
			);
		} else {
			// No filters — no placeholders needed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- safe: no user input in query
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mscsl_login_attempts" );
		}

		wp_cache_set( $cache_key, $count, 'mscsl' );

		return $count;
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

		wp_cache_set( 'last_changed', microtime(), 'mscsl' );

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

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table delete with prepare
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}mscsl_login_attempts WHERE created_at < %s",
				$cutoff
			)
		);

		wp_cache_set( 'last_changed', microtime(), 'mscsl' );

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
					self::sanitize_csv_cell( $attempt['ip_address'] ),
					self::sanitize_csv_cell( $attempt['user_login'] ),
					sanitize_key( $attempt['attempt_type'] ),
					self::sanitize_csv_cell( $attempt['user_agent'] ?? '' ),
					self::sanitize_csv_cell( $attempt['created_at'] ),
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

	/**
	 * Sanitize a CSV cell value to prevent formula injection.
	 *
	 * Prefixes cells that start with formula characters (=, +, -, @, tab)
	 * with a tab character to prevent CSV injection attacks.
	 *
	 * @since 1.0.7
	 *
	 * @param string $value Cell value to sanitize.
	 * @return string Sanitized cell value.
	 */
	private static function sanitize_csv_cell( $value ) {
		$first_char = substr( (string) $value, 0, 1 );
		if ( in_array( $first_char, array( '=', '+', '-', '@', "\t" ), true ) ) {
			return "\t" . $value;
		}
		return $value;
	}
}
