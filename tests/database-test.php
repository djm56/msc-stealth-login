<?php
/**
 * Test the login-history database layer.
 *
 * @package MSCSL
 */

/**
 * Test login attempt storage, filtering and counting.
 *
 * @covers MSCSL\Database
 */
class Test_Database extends WP_UnitTestCase {

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();

		MSCSL\Database::create_table();
		MSCSL\Database::clear_attempts();
		wp_cache_flush();
	}

	/**
	 * Tear down after test.
	 */
	public function tear_down() {
		MSCSL\Database::clear_attempts();

		parent::tear_down();
	}

	/**
	 * Seed a known set of attempts.
	 *
	 * @return void
	 */
	private function seed_attempts() {
		MSCSL\Database::log_attempt( '10.0.0.1', 'alice', 'failure', 'UA-1' );
		MSCSL\Database::log_attempt( '10.0.0.1', 'alice', 'lockout', 'UA-1' );
		MSCSL\Database::log_attempt( '10.0.0.2', 'bob', 'success', 'UA-2' );
		MSCSL\Database::log_attempt( '10.0.0.3', 'carol', 'failure', 'UA-3' );

		// log_attempt() bumps the cache marker, but be explicit.
		wp_cache_flush();
	}

	/**
	 * Test an attempt is logged and returned.
	 */
	public function test_log_attempt_and_read_back() {
		$this->assertTrue( MSCSL\Database::log_attempt( '10.0.0.9', 'dave', 'failure', 'UA' ) );

		$attempts = MSCSL\Database::get_attempts();

		$this->assertCount( 1, $attempts );
		$this->assertSame( '10.0.0.9', $attempts[0]['ip_address'] );
		$this->assertSame( 'dave', $attempts[0]['user_login'] );
		$this->assertSame( 'failure', $attempts[0]['attempt_type'] );
	}

	/**
	 * Test unfiltered queries return every row.
	 */
	public function test_get_attempts_unfiltered() {
		$this->seed_attempts();

		$this->assertCount( 4, MSCSL\Database::get_attempts() );
		$this->assertSame( 4, MSCSL\Database::get_attempt_count() );
	}

	/**
	 * Test the IP filter actually restricts the result set.
	 *
	 * Regression test for 1.3.0: the WHERE clause used to be passed to
	 * $wpdb->prepare() as a %s value, which MySQL evaluated as a truthy string
	 * literal, so every filter was silently ignored.
	 */
	public function test_get_attempts_filters_by_ip() {
		$this->seed_attempts();

		$attempts = MSCSL\Database::get_attempts( array( 'ip' => '10.0.0.1' ) );

		$this->assertCount( 2, $attempts );

		foreach ( $attempts as $attempt ) {
			$this->assertSame( '10.0.0.1', $attempt['ip_address'] );
		}

		$this->assertSame( 2, MSCSL\Database::get_attempt_count( array( 'ip' => '10.0.0.1' ) ) );
	}

	/**
	 * Test the username filter restricts the result set.
	 */
	public function test_get_attempts_filters_by_username() {
		$this->seed_attempts();

		$attempts = MSCSL\Database::get_attempts( array( 'username' => 'bob' ) );

		$this->assertCount( 1, $attempts );
		$this->assertSame( 'bob', $attempts[0]['user_login'] );
		$this->assertSame( 1, MSCSL\Database::get_attempt_count( array( 'username' => 'bob' ) ) );
	}

	/**
	 * Test the result-type filter restricts the result set.
	 */
	public function test_get_attempts_filters_by_type() {
		$this->seed_attempts();

		$attempts = MSCSL\Database::get_attempts( array( 'type' => 'failure' ) );

		$this->assertCount( 2, $attempts );

		foreach ( $attempts as $attempt ) {
			$this->assertSame( 'failure', $attempt['attempt_type'] );
		}

		$this->assertSame( 2, MSCSL\Database::get_attempt_count( array( 'type' => 'failure' ) ) );
	}

	/**
	 * Test combined filters are ANDed together.
	 */
	public function test_get_attempts_combines_filters() {
		$this->seed_attempts();

		$attempts = MSCSL\Database::get_attempts(
			array(
				'ip'   => '10.0.0.1',
				'type' => 'lockout',
			)
		);

		$this->assertCount( 1, $attempts );
		$this->assertSame( 'lockout', $attempts[0]['attempt_type'] );
	}

	/**
	 * Test an unknown result type is discarded rather than applied.
	 */
	public function test_invalid_type_filter_is_ignored() {
		$this->seed_attempts();

		$this->assertCount( 4, MSCSL\Database::get_attempts( array( 'type' => 'bogus' ) ) );
	}

	/**
	 * Test a malformed IP filter is discarded rather than applied.
	 */
	public function test_invalid_ip_filter_is_ignored() {
		$this->seed_attempts();

		$this->assertCount( 4, MSCSL\Database::get_attempts( array( 'ip' => 'not-an-ip' ) ) );
	}

	/**
	 * Test date bounds filter on created_at.
	 */
	public function test_get_attempts_filters_by_date() {
		$this->seed_attempts();

		$today    = current_time( 'Y-m-d' );
		$future   = gmdate( 'Y-m-d', time() + ( 2 * DAY_IN_SECONDS ) );

		$this->assertCount(
			4,
			MSCSL\Database::get_attempts( array( 'date_from' => $today ) )
		);

		$this->assertCount(
			0,
			MSCSL\Database::get_attempts( array( 'date_from' => $future ) )
		);
	}

	/**
	 * Test pagination via limit and offset.
	 */
	public function test_limit_and_offset() {
		$this->seed_attempts();

		$page_one = MSCSL\Database::get_attempts(
			array(
				'limit'  => 2,
				'offset' => 0,
			)
		);
		$page_two = MSCSL\Database::get_attempts(
			array(
				'limit'  => 2,
				'offset' => 2,
			)
		);

		$this->assertCount( 2, $page_one );
		$this->assertCount( 2, $page_two );
		$this->assertNotSame( $page_one[0]['id'], $page_two[0]['id'] );
	}

	/**
	 * Test the CSV export honours filters and includes a header row.
	 */
	public function test_export_to_csv_respects_filters() {
		$this->seed_attempts();

		$csv = MSCSL\Database::export_to_csv( array( 'ip' => '10.0.0.2' ) );

		$this->assertStringContainsString( '10.0.0.2', $csv );
		$this->assertStringNotContainsString( '10.0.0.1', $csv );
	}

	/**
	 * Test old entries are pruned and recent ones kept.
	 */
	public function test_delete_old_attempts() {
		global $wpdb;

		$this->seed_attempts();

		$table = $wpdb->prefix . MSCSL\Database::TABLE_NAME;
		$old   = gmdate( 'Y-m-d H:i:s', time() - ( 60 * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- test fixture
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET created_at = %s WHERE ip_address = %s", $old, '10.0.0.3' ) );
		wp_cache_flush();

		MSCSL\Database::delete_old_attempts( 30 );
		wp_cache_flush();

		$this->assertSame( 3, MSCSL\Database::get_attempt_count() );
	}
}
