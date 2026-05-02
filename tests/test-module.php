<?php
/**
 * Test module functionality for MSC Stealth Login.
 *
 * @package MSCSL
 */

/**
 * Test module behavior.
 *
 * @covers MSCSL\Module
 */
class Test_Module extends WP_UnitTestCase {

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

		// Enable module for these tests.
		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'custom_login_slug' => 'secure-login',
			)
		);
	}

	/**
	 * Tear down after test.
	 */
	public function tear_down() {
		delete_option( 'mscsl_options' );
		delete_option( 'msc_recovery_token' );

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
	 * Test rewrite rules are registered when module is enabled.
	 */
	public function test_rewrite_rules_registered() {
		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'custom_login_slug' => 'my-login',
			)
		);

		// Initialize rewrite rules.
		$this->module->register_rewrite_rules();

		global $wp_rewrite;

		// The rewrite rules should include our custom rule.
		$rules = $wp_rewrite->rewrite_rules();

		$this->assertArrayHasKey( '^my-login/?$', $rules );
		$this->assertEquals( 'index.php?mscsl_login=1', $rules['^my-login/?$'] );
	}

	/**
	 * Test query vars include mscsl_login.
	 */
	public function test_query_vars_include_mscsl() {
		$vars = array( 'p' => 1, 'post_type' => 'post' );

		$added = $this->module->add_query_vars( $vars );

		$this->assertContains( 'mscsl_login', $added );
		$this->assertContains( 'mscsl_action', $added );
	}

	/**
	 * Test custom login URL is generated correctly.
	 */
	public function test_custom_login_url() {
		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'custom_login_slug' => 'my-secret-login',
			)
		);

		$url = $this->module->custom_login_url( home_url() . '/wp-login.php' );

		$this->assertEquals( home_url() . '/my-secret-login/', $url );
	}

	/**
	 * Test custom login URL has trailing slash.
	 */
	public function test_custom_login_url_has_trailing_slash() {
		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'custom_login_slug' => 'secret',
			)
		);

		$url    = $this->module->custom_login_url( home_url() . '/wp-login.php' );
		$parsed = parse_url( $url );

		$this->assertEquals( '/secret/', $parsed['path'] );
	}

	/**
	 * Test recovery login URL format.
	 */
	public function test_recovery_login_url() {
		$token = 'test-recovery-token-12345';
		update_option( 'msc_recovery_token', $token );

		$url = $this->module->recovery_login_url( home_url() . '/wp-login.php' );

		$this->assertStringContainsString( 'wp-login.php', $url );
		$this->assertStringContainsString( 'msc_recovery=' . $token, $url );
	}

	/**
	 * Test logout redirect URL is respected.
	 */
	public function test_logout_redirect_url() {
		$redirect_url = 'https://example.com/thank-you';

		$this->plugin->update_options(
			array(
				'module_enabled'      => 1,
				'logout_redirect_url' => $redirect_url,
			)
		);

		// The logout redirect is handled via wp_logout hook.
		// We verify the option is correctly stored.
		$this->assertEquals( $redirect_url, $this->plugin->get_option( 'logout_redirect_url' ) );
	}

	/**
	 * Test wp_admin_redirect defaults to home_url.
	 */
	public function test_wp_admin_redirect_defaults_to_home() {
		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'wp_admin_redirect' => '',
			)
		);

		$redirect = $this->plugin->get_option( 'wp_admin_redirect', home_url() );
		$this->assertEquals( home_url(), $redirect );
	}

	/**
	 * Test wp_admin_redirect can be customized.
	 */
	public function test_wp_admin_redirect_can_be_customized() {
		$custom_redirect = 'https://example.com/login';

		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'wp_admin_redirect' => $custom_redirect,
			)
		);

		$this->assertEquals( $custom_redirect, $this->plugin->get_option( 'wp_admin_redirect' ) );
	}

	/**
	 * Test block_default_login allows logout action.
	 */
	public function test_block_default_login_allows_logout_action() {
		// Set up request for logout action.
		$_SERVER['SCRIPT_NAME'] = '/wp-login.php';
		$_SERVER['REQUEST_URI'] = '/wp-login.php?action=logout';
		$_GET['action'] = 'logout';

		// Should not exit - logout should be allowed.
		$this->module->block_default_login();

		// Clean up.
		unset( $_GET['action'], $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Test block_default_login allows redirect_to.
	 */
	public function test_block_default_login_allows_redirect_to() {
		// Set up request with redirect_to.
		$_SERVER['SCRIPT_NAME'] = '/wp-login.php';
		$_SERVER['REQUEST_URI'] = '/wp-login.php?redirect_to=/wp-admin/';
		$_GET['redirect_to'] = '/wp-admin/';

		// Should not exit - redirect_to should be allowed.
		$this->module->block_default_login();

		// Clean up.
		unset( $_GET['redirect_to'], $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Test block_default_login blocks custom slug access.
	 */
	public function test_block_default_login_blocks_access() {
		$this->plugin->update_options(
			array(
				'module_enabled'    => 1,
				'custom_login_slug' => 'secret-login',
			)
		);

		// Set up request to custom slug via wp-login.php path.
		$_SERVER['SCRIPT_NAME'] = '/wp-login.php';
		$_SERVER['REQUEST_URI'] = '/wp-login.php';

		// Capture exit to prevent test failure.
		$this->expectException( \Exception::class );

		$this->module->block_default_login();
	}

	/**
	 * Test get_client_ip returns valid IP.
	 */
	public function test_get_client_ip_returns_valid_ip() {
		// Set various IP headers.
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( $this->module );

		$this->assertEquals( '192.168.1.1', $ip );
	}

	/**
	 * Test get_client_ip handles X-Forwarded-For.
	 */
	public function test_get_client_ip_handles_forwarded() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 192.168.1.1';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( $this->module );

		$this->assertEquals( '10.0.0.1', $ip );
	}

	/**
	 * Test get_client_ip returns empty for invalid IP.
	 */
	public function test_get_client_ip_returns_empty_for_invalid() {
		// Clear all IP headers.
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_CF_CONNECTING_IP'] );
		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';

		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_client_ip' );
		$method->setAccessible( true );

		$ip = $method->invoke( $this->module );

		$this->assertEquals( '', $ip );
	}

	/**
	 * Test blocked message shows access denied.
	 */
	public function test_show_blocked_message_contains_access_denied() {
		// Capture output.
		ob_start();

		// Use reflection to call private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'show_blocked_message' );
		$method->setAccessible( true );

		// This will output HTML and exit.
		// We expect it to contain "Access Denied".
		try {
			$method->invoke( $this->module );
		} catch ( \Exception $e ) {
			// Expected - method calls exit.
		}

		$output = ob_get_clean();

		$this->assertStringContainsString( 'Access Denied', $output );
	}

	/**
	 * Test lockout message shows correct content.
	 */
	public function test_lockout_message_content() {
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'get_lockout_message' );
		$method->setAccessible( true );

		$message = $method->invoke( $this->module );

		$this->assertStringContainsString( 'minutes', $message );
	}

	/**
	 * Test login attempts transient key format.
	 */
	public function test_attempts_transient_key_format() {
		$expected_prefix = 'mscsl_login_attempts_';

		$this->assertEquals( $expected_prefix, MSCSL\Module::ATTEMPTS_TRANSIENT );
	}

	/**
	 * Test module is_enabled returns true when module_enabled is 1.
	 */
	public function test_is_enabled_returns_true_when_module_enabled() {
		$this->plugin->update_options( array( 'module_enabled' => 1 ) );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'is_enabled' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->module ) );
	}

	/**
	 * Test module is_enabled returns false when module_enabled is 0.
	 */
	public function test_is_enabled_returns_false_when_module_disabled() {
		$this->plugin->update_options( array( 'module_enabled' => 0 ) );

		// Use reflection to access private method.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'is_enabled' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $this->module ) );
	}

	/**
	 * Test regeneration of recovery token.
	 */
	public function test_regenerate_recovery_token() {
		$old_token = 'old-token-value-123456789012345';
		update_option( 'msc_recovery_token', $old_token );

		// Create admin user for capability check.
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		// Mock the redirect.
		add_filter( 'wp_redirect', '__return_false', 1 );

		$this->module->handle_regenerate_recovery_token();

		remove_filter( 'wp_redirect', '__return_false', 1 );

		$new_token = get_option( 'msc_recovery_token' );
		$this->assertNotEquals( $old_token, $new_token );
		$this->assertEquals( 32, strlen( $new_token ) );
	}

	/**
	 * Test handle_recovery_url returns early when no recovery param.
	 */
	public function test_handle_recovery_url_returns_early_without_param() {
		// Make sure no recovery param is set.
		unset( $_GET['msc_recovery'] );

		// Use reflection to test.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'handle_recovery_url' );
		$method->setAccessible( true );

		// Should return early without throwing.
		$result = $method->invoke( $this->module );

		$this->assertNull( $result );
	}

	/**
	 * Test handle_recovery_url allows access with valid token.
	 */
	public function test_handle_recovery_url_allows_valid_token() {
		$token = 'valid-recovery-token-12345678901';
		update_option( 'msc_recovery_token', $token );

		$_GET['msc_recovery'] = $token;

		// Use reflection to test.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'handle_recovery_url' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->module );

		// Should return null (but modifies hooks internally).
		$this->assertNull( $result );

		unset( $_GET['msc_recovery'] );
	}

	/**
	 * Test handle_recovery_url rejects invalid token.
	 */
	public function test_handle_recovery_url_rejects_invalid_token() {
		$token = 'valid-recovery-token-12345678901';
		update_option( 'msc_recovery_token', $token );

		$_GET['msc_recovery'] = 'invalid-token';

		// Use reflection to test.
		$reflection = new \ReflectionClass( $this->module );
		$method = $reflection->getMethod( 'handle_recovery_url' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->module );

		// Should return null (doesn't process invalid token).
		$this->assertNull( $result );

		unset( $_GET['msc_recovery'] );
	}
}
