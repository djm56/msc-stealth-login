<?php
/**
 * Test security functionality for MSC Stealth Login.
 *
 * @package MSCSL
 */

/**
 * Test security features.
 *
 * @covers MSCSL\Module
 */
class Test_Security extends WP_UnitTestCase {

	/**
	 * Plugin instance.
	 *
	 * @var MSCSL\Plugin
	 */
	protected $plugin;

	/**
	 * Module instance.
	 *
	 * @var MSCSL\Module
	 */
	protected $module;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();
		$this->plugin = MSCSL\Plugin::instance();
		$this->module = $this->plugin->module;

		// Enable advanced security for these tests.
		$this->plugin->update_options(
			array(
				'advanced_security_enabled' => 1,
				'brute_force_enabled'       => 1,
				'max_login_attempts'        => 3,
				'lockout_duration'          => 15,
			)
		);
	}

	/**
	 * Tear down after test.
	 */
	public function tear_down() {
		delete_option( 'mscsl_options' );
		delete_option( 'mscsl_recovery_token' );

		// Clear all transients.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_mscsl_login_attempts_' ) . '%'
			)
		);

		parent::tear_down();
	}

	/**
	 * Test module is enabled by default.
	 */
	public function test_module_enabled_by_default() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options( array( 'module_enabled' => 1 ) );

		$this->assertTrue( $this->module->is_enabled() );
	}

	/**
	 * Test module can be disabled.
	 */
	public function test_module_can_be_disabled() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options( array( 'module_enabled' => 0 ) );

		$this->assertFalse( $this->module->is_enabled() );
	}

	/**
	 * Test failed login attempt is recorded.
	 */
	public function test_failed_attempt_recorded() {
		$ip = '192.168.1.100';

		// Record a failed attempt.
		$this->module->record_failed_attempt( 'test_user' );

		// Verify transient was set.
		$attempts = $this->get_attempts_for_ip( $ip );
		$this->assertNotFalse( $attempts );
		$this->assertEquals( 1, $attempts['count'] );
	}

	/**
	 * Test multiple failed attempts increment counter.
	 */
	public function test_multiple_failed_attempts_increment_counter() {
		// Record multiple failed attempts.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->module->record_failed_attempt( 'test_user' );
		}

		// Get attempts for the IP.
		$ip       = $this->get_client_ip();
		$attempts = $this->get_attempts_for_ip( $ip );

		$this->assertNotFalse( $attempts );
		$this->assertEquals( 3, $attempts['count'] );
	}

	/**
	 * Test brute force lockout after max attempts.
	 */
	public function test_lockout_after_max_attempts() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options(
			array(
				'advanced_security_enabled' => 1,
				'brute_force_enabled'       => 1,
				'max_login_attempts'         => 3,
			)
		);

		// Record max failed attempts.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->module->record_failed_attempt( 'test_user' );
		}

		// Check login attempts should now block.
		$user = $this->module->check_login_attempts( null );

		$this->assertInstanceOf( \WP_Error::class, $user );
		$this->assertEquals( 'locked_out', $user->get_error_code() );
	}

	/**
	 * Test lockout message contains duration.
	 */
	public function test_lockout_message_contains_duration() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options(
			array(
				'advanced_security_enabled' => 1,
				'brute_force_enabled'       => 1,
				'max_login_attempts'        => 3,
				'lockout_duration'          => 30,
			)
		);

		// Record max failed attempts.
		for ( $i = 0; $i < 3; $i++ ) {
			$this->module->record_failed_attempt( 'test_user' );
		}

		$user = $this->module->check_login_attempts( null );

		$this->assertStringContainsString( '30', $user->get_error_message() );
	}

	/**
	 * Test valid user passes check when not locked out.
	 */
	public function test_valid_user_passes_when_not_locked_out() {
		$mock_user = new \WP_User( 1 );

		$result = $this->module->check_login_attempts( $mock_user );

		$this->assertSame( $mock_user, $result );
	}

	/**
	 * Test WP_Error passes through without modification.
	 */
	public function test_wp_error_passes_through() {
		$error    = new \WP_Error( 'test_error', 'Test message' );
		$expected = $this->module->check_login_attempts( $error );

		$this->assertSame( $error, $expected );
	}

	/**
	 * Test capability check for admin access.
	 */
	public function test_admin_has_manage_options_capability() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$this->assertTrue( current_user_can( 'manage_options' ) );
	}

	/**
	 * Test capability check blocks subscriber.
	 */
	public function test_subscriber_cannot_manage_options() {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$this->assertFalse( current_user_can( 'manage_options' ) );
	}

	/**
	 * Test xmlrpc methods are blocked when enabled.
	 */
	public function test_xmlrpc_pingback_methods_blocked() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options(
			array(
				'advanced_security_enabled' => 1,
				'disable_xmlrpc'             => 1,
			)
		);

		$methods = array(
			'pingback.ping'                      => true,
			'pingback.extensions.getPingbacks'   => true,
			'wp.getUsers'                        => true,
			'wp.getUser'                         => true,
			'wp.getUsersBlogs'                   => true,
		);

		$blocked = $this->module->block_xmlrpc_methods( $methods );

		$this->assertArrayNotHasKey( 'pingback.ping', $blocked );
		$this->assertArrayNotHasKey( 'pingback.extensions.getPingbacks', $blocked );
		$this->assertArrayNotHasKey( 'wp.getUsers', $blocked );
		$this->assertArrayNotHasKey( 'wp.getUser', $blocked );
		$this->assertArrayNotHasKey( 'wp.getUsersBlogs', $blocked );
	}

	/**
	 * Test rest api user enumeration is blocked.
	 */
	public function test_rest_api_user_endpoints_blocked() {
		$endpoints = array(
			'/wp/v2/users'                 => array( 'methods' => array( 'GET' ) ),
			'/wp/v2/users/(?P<id>[\d]+)'    => array( 'methods' => array( 'GET' ) ),
			'/wp/v2/posts'                 => array( 'methods' => array( 'GET' ) ),
		);

		$blocked = $this->module->block_user_enumeration( $endpoints );

		$this->assertArrayNotHasKey( '/wp/v2/users', $blocked );
		$this->assertArrayNotHasKey( '/wp/v2/users/(?P<id>[\d]+)', $blocked );
		$this->assertArrayHasKey( '/wp/v2/posts', $blocked );
	}

	/**
	 * Test author query redirect when author param is set.
	 */
	public function test_author_query_redirect() {
		$_GET['author'] = '1';

		// Capture the redirect.
		add_action( 'wp_redirect', array( $this, 'capture_redirect' ), 1, 3 );

		$this->module->maybe_block_author_query();

		remove_action( 'wp_redirect', array( $this, 'capture_redirect' ), 1 );

		unset( $_GET['author'] );

		// The redirect should have been called with home_url.
		$this->assertEquals( home_url( '/' ), $this->captured_redirect );
	}

	/**
	 * Helper to capture redirect URL.
	 *
	 * @param string $location The redirect location.
	 * @param int    $status   The redirect status code.
	 * @return string
	 */
	public function capture_redirect( $location, $status ) {
		$this->captured_redirect = $location;
		return $location;
	}

	/**
	 * Test hide_wp_admin option controls blocking behavior.
	 */
	public function test_hide_wp_admin_option_controls_blocking() {
		$plugin = MSCSL\Plugin::instance();

		// Enable hiding.
		$plugin->update_options( array( 'hide_wp_admin' => 1 ) );

		// Should return early when user is admin.
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		// No redirect should happen for admin.
		$this->module->block_wp_admin();
	}

	/**
	 * Helper to get client IP.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		return '192.168.1.100';
	}

	/**
	 * Helper to get attempts for IP.
	 *
	 * @param string $ip IP address.
	 * @return array|false
	 */
	private function get_attempts_for_ip( $ip ) {
		$transient_key = 'mscsl_login_attempts_' . md5( $ip );
		return get_transient( $transient_key );
	}
}
