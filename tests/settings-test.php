<?php
/**
 * Test settings functionality for MSC Stealth Login.
 *
 * @package MSCSL
 */

/**
 * Test settings/options functionality.
 *
 * @covers MSCSL\Settings
 */
class Test_Settings extends WP_UnitTestCase {

	/**
	 * Plugin instance.
	 *
	 * @var MSCSL\Plugin
	 */
	protected $plugin;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();
		$this->plugin = MSCSL\Plugin::instance();
	}

	/**
	 * Tear down after test.
	 */
	public function tear_down() {
		// Clean up options.
		delete_option( 'mscsl_options' );
		delete_option( 'mscsl_recovery_token' );

		parent::tear_down();
	}

	/**
	 * Test default options are returned correctly.
	 */
	public function test_default_options() {
		$defaults = MSCSL\Plugin::default_options();

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'module_enabled', $defaults );
		$this->assertArrayHasKey( 'custom_login_slug', $defaults );
		$this->assertArrayHasKey( 'hide_wp_admin', $defaults );
		$this->assertArrayHasKey( 'wp_admin_redirect', $defaults );
		$this->assertArrayHasKey( 'advanced_security_enabled', $defaults );
		$this->assertArrayHasKey( 'disable_xmlrpc', $defaults );
		$this->assertArrayHasKey( 'disable_rest_api', $defaults );
		$this->assertArrayHasKey( 'brute_force_enabled', $defaults );
		$this->assertArrayHasKey( 'max_login_attempts', $defaults );
		$this->assertArrayHasKey( 'lockout_duration', $defaults );
		$this->assertArrayHasKey( 'logout_redirect_url', $defaults );
	}

	/**
	 * Test default values are correct.
	 */
	public function test_default_values() {
		$defaults = MSCSL\Plugin::default_options();

		$this->assertEquals( 1, $defaults['module_enabled'] );
		$this->assertEquals( 'secure-login', $defaults['custom_login_slug'] );
		$this->assertEquals( 1, $defaults['hide_wp_admin'] );
		$this->assertEquals( home_url(), $defaults['wp_admin_redirect'] );
		$this->assertEquals( 0, $defaults['advanced_security_enabled'] );
		$this->assertEquals( 1, $defaults['disable_xmlrpc'] );
		$this->assertEquals( 1, $defaults['disable_rest_api'] );
		$this->assertEquals( 1, $defaults['brute_force_enabled'] );
		$this->assertEquals( 3, $defaults['max_login_attempts'] );
		$this->assertEquals( 15, $defaults['lockout_duration'] );
		$this->assertEquals( home_url(), $defaults['logout_redirect_url'] );
	}

	/**
	 * Test option retrieval with no saved options.
	 */
	public function test_get_option_no_saved_options() {
		delete_option( 'mscsl_options' );

		$slug = $this->plugin->get_option( 'custom_login_slug' );
		$this->assertEquals( 'secure-login', $slug );

		$enabled = $this->plugin->get_option( 'module_enabled' );
		$this->assertEquals( 1, $enabled );
	}

	/**
	 * Test option retrieval with saved options.
	 */
	public function test_get_option_with_saved_options() {
		$this->plugin->update_options( array( 'custom_login_slug' => 'my-secret-login' ) );

		$slug = $this->plugin->get_option( 'custom_login_slug' );
		$this->assertEquals( 'my-secret-login', $slug );
	}

	/**
	 * Test getting non-existent option returns default.
	 */
	public function test_get_nonexistent_option_returns_default() {
		$value = $this->plugin->get_option( 'nonexistent_key', 'default_value' );
		$this->assertEquals( 'default_value', $value );
	}

	/**
	 * Test get_options returns all options with defaults.
	 */
	public function test_get_options_returns_merged_defaults() {
		$options = $this->plugin->get_options();

		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'module_enabled', $options );
		$this->assertArrayHasKey( 'custom_login_slug', $options );
		$this->assertEquals( 'secure-login', $options['custom_login_slug'] );
	}

	/**
	 * Test update_options saves correctly.
	 */
	public function test_update_options_saves_correctly() {
		$new_options = array(
			'module_enabled'       => 0,
			'custom_login_slug'    => 'custom-slug',
			'max_login_attempts'   => 5,
		);

		$result = $this->plugin->update_options( $new_options );

		$this->assertTrue( $result );
		$this->assertEquals( 0, $this->plugin->get_option( 'module_enabled' ) );
		$this->assertEquals( 'custom-slug', $this->plugin->get_option( 'custom_login_slug' ) );
		$this->assertEquals( 5, $this->plugin->get_option( 'max_login_attempts' ) );
	}

	/**
	 * Test update_options merges with existing options.
	 */
	public function test_update_options_merges_with_existing() {
		// Set initial options.
		$this->plugin->update_options(
			array(
				'custom_login_slug' => 'first-slug',
				'module_enabled'   => 1,
			)
		);

		// Update only one option.
		$this->plugin->update_options( array( 'custom_login_slug' => 'second-slug' ) );

		// Check both values.
		$this->assertEquals( 'second-slug', $this->plugin->get_option( 'custom_login_slug' ) );
		$this->assertEquals( 1, $this->plugin->get_option( 'module_enabled' ) );
	}

	/**
	 * Test login slug sanitization - strips leading slash.
	 */
	public function test_login_slug_sanitization_strips_leading_slash() {
		$this->plugin->update_options( array( 'custom_login_slug' => '/my-login' ) );

		$options = $this->plugin->get_options();
		$this->assertEquals( 'my-login', $options['custom_login_slug'] );
	}

	/**
	 * Test login slug sanitization - reserved words.
	 */
	public function test_login_slug_sanitization_reserved_words() {
		$reserved_slugs = array( 'wp-admin', 'wp-login', 'wp-login.php', 'login', 'admin' );

		foreach ( $reserved_slugs as $slug ) {
			$this->plugin->update_options( array( 'custom_login_slug' => $slug ) );
			$options = $this->plugin->get_options();
			$this->assertEquals(
				'secure-login',
				$options['custom_login_slug'],
				"Slug '$slug' should be rejected and replaced with default"
			);
		}
	}

	/**
	 * Test login slug sanitization - wp- prefix.
	 */
	public function test_login_slug_sanitization_wp_prefix() {
		$this->plugin->update_options( array( 'custom_login_slug' => 'wp-admin-redirect' ) );

		$options = $this->plugin->get_options();
		$this->assertEquals( 'secure-login', $options['custom_login_slug'] );
	}

	/**
	 * Test empty login slug defaults to secure-login.
	 */
	public function test_empty_login_slug_defaults_to_secure_login() {
		$this->plugin->update_options( array( 'custom_login_slug' => '' ) );

		$options = $this->plugin->get_options();
		$this->assertEquals( 'secure-login', $options['custom_login_slug'] );
	}

	/**
	 * Test max_login_attempts is clamped to valid range.
	 */
	public function test_max_login_attempts_clamped_to_range() {
		// Test too low.
		$this->plugin->update_options( array( 'max_login_attempts' => 0 ) );
		$options = $this->plugin->get_options();
		$this->assertEquals( 1, $options['max_login_attempts'] );

		// Test too high.
		$this->plugin->update_options( array( 'max_login_attempts' => 100 ) );
		$options = $this->plugin->get_options();
		$this->assertEquals( 10, $options['max_login_attempts'] );

		// Test valid.
		$this->plugin->update_options( array( 'max_login_attempts' => 5 ) );
		$options = $this->plugin->get_options();
		$this->assertEquals( 5, $options['max_login_attempts'] );
	}

	/**
	 * Test lockout_duration is clamped to valid range.
	 */
	public function test_lockout_duration_clamped_to_range() {
		// Test too low.
		$this->plugin->update_options( array( 'lockout_duration' => 1 ) );
		$options = $this->plugin->get_options();
		$this->assertEquals( 5, $options['lockout_duration'] );

		// Test too high.
		$this->plugin->update_options( array( 'lockout_duration' => 100 ) );
		$options = $this->plugin->get_options();
		$this->assertEquals( 60, $options['lockout_duration'] );

		// Test valid.
		$this->plugin->update_options( array( 'lockout_duration' => 30 ) );
		$options = $this->plugin->get_options();
		$this->assertEquals( 30, $options['lockout_duration'] );
	}

	/**
	 * Test recovery token is generated on activation.
	 */
	public function test_recovery_token_generated_on_activation() {
		delete_option( 'mscsl_recovery_token' );

		MSCSL\Plugin::activate();

		$token = get_option( 'mscsl_recovery_token' );
		$this->assertNotEmpty( $token );
		$this->assertEquals( 32, strlen( $token ) );
	}

	/**
	 * Helper to get options array.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_options() {
		return $this->plugin->get_options();
	}

	/**
	 * Test email_notifications_enabled is in default options.
	 */
	public function test_email_notifications_enabled_in_defaults() {
		$defaults = MSCSL\Plugin::default_options();
		$this->assertArrayHasKey( 'email_notifications_enabled', $defaults );
		$this->assertEquals( 0, $defaults['email_notifications_enabled'] );
	}

	/**
	 * Test saving Settings tab does not affect Advanced settings.
	 */
	public function test_saving_settings_tab_does_not_affect_advanced_settings() {
		// Set initial advanced_security_enabled to 1.
		$options = $this->get_options();
		$options['advanced_security_enabled'] = 1;
		$options['disable_xmlrpc'] = 1;
		$options['brute_force_enabled'] = 1;
		update_option( 'mscsl_options', $options );

		// Simulate POST from Settings tab (no advanced_security_enabled field).
		$_POST = array(
			'module_enabled' => '1',
			'custom_login_slug' => 'my-login',
			'hide_wp_admin' => '1',
			'wp_admin_redirect' => home_url(),
			'logout_redirect_url' => home_url(),
			'current_tab' => 'settings',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		// Build request mock.
		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		// Verify advanced_security_enabled is still 1.
		$new_options = $this->get_options();
		$this->assertEquals( 1, $new_options['advanced_security_enabled'] );
		$this->assertEquals( 1, $new_options['disable_xmlrpc'] );
		$this->assertEquals( 1, $new_options['brute_force_enabled'] );
	}

	/**
	 * Test saving Advanced tab does not affect Settings.
	 */
	public function test_saving_advanced_tab_does_not_affect_settings() {
		// Set initial settings values.
		$options = $this->get_options();
		$options['module_enabled'] = 1;
		$options['custom_login_slug'] = 'original-slug';
		$options['hide_wp_admin'] = 1;
		update_option( 'mscsl_options', $options );

		// Simulate POST from Advanced tab (no module_enabled or custom_login_slug field).
		$_POST = array(
			'advanced_security_enabled' => '1',
			'disable_xmlrpc' => '1',
			'disable_rest_api' => '1',
			'brute_force_enabled' => '1',
			'max_login_attempts' => '5',
			'lockout_duration' => '30',
			'login_logging_enabled' => '1',
			'ip_whitelist' => "192.168.1.1\n10.0.0.0/8",
			'progressive_lockout_enabled' => '1',
			'max_lockout_duration' => '120',
			'current_tab' => 'advanced',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		// Verify settings values are unchanged.
		$new_options = $this->get_options();
		$this->assertEquals( 1, $new_options['module_enabled'] );
		$this->assertEquals( 'original-slug', $new_options['custom_login_slug'] );
		$this->assertEquals( 1, $new_options['hide_wp_admin'] );
	}

	/**
	 * Test saving Email tab does not affect Settings or Advanced.
	 */
	public function test_saving_email_tab_does_not_affect_other_tabs() {
		// Set initial values across all tabs.
		$options = $this->get_options();
		$options['module_enabled'] = 0;
		$options['custom_login_slug'] = 'test-slug';
		$options['advanced_security_enabled'] = 1;
		$options['brute_force_enabled'] = 1;
		update_option( 'mscsl_options', $options );

		// Simulate POST from Email tab.
		$_POST = array(
			'email_notifications_enabled' => '1',
			'lockout_email_enabled' => '1',
			'lockout_email_recipient' => 'test@example.com',
			'lockout_email_subject' => 'Test Subject',
			'lockout_email_body' => 'Test body content',
			'login_alert_admin' => '1',
			'login_alert_new_ip' => '1',
			'current_tab' => 'email',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		// Verify other tab settings are unchanged.
		$new_options = $this->get_options();
		$this->assertEquals( 0, $new_options['module_enabled'] );
		$this->assertEquals( 'test-slug', $new_options['custom_login_slug'] );
		$this->assertEquals( 1, $new_options['advanced_security_enabled'] );
		$this->assertEquals( 1, $new_options['brute_force_enabled'] );

		// Verify email settings were saved.
		$this->assertEquals( 1, $new_options['email_notifications_enabled'] );
		$this->assertEquals( 1, $new_options['lockout_email_enabled'] );
		$this->assertEquals( 'test@example.com', $new_options['lockout_email_recipient'] );
		$this->assertEquals( 'Test Subject', $new_options['lockout_email_subject'] );
		$this->assertEquals( 'Test body content', $new_options['lockout_email_body'] );
		$this->assertEquals( 1, $new_options['login_alert_admin'] );
		$this->assertEquals( 1, $new_options['login_alert_new_ip'] );
	}

	/**
	 * Test checkbox can be disabled independently.
	 *
	 * Note: Unchecked checkboxes are NOT sent in POST at all (they are omitted).
	 * This test properly simulates real browser behavior by not including
	 * the unchecked checkbox field in POST data.
	 */
	public function test_checkbox_can_be_disabled_independently() {
		// Set initial value.
		$this->plugin->update_options( array( 'module_enabled' => 1 ) );
		$this->assertEquals( 1, $this->get_options()['module_enabled'] );

		// Simulate saving with checkbox OFF (checkbox omitted from POST).
		$_POST = array(
			'custom_login_slug' => 'secure-login',
			'hide_wp_admin' => '1',
			'wp_admin_redirect' => home_url(),
			'logout_redirect_url' => home_url(),
			'current_tab' => 'settings',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$this->assertEquals( 0, $this->get_options()['module_enabled'] );
	}

	/**
	 * Test text field saves correctly.
	 */
	public function test_text_field_saves_correctly() {
		$_POST = array(
			'module_enabled' => '1',
			'custom_login_slug' => 'my-custom-slug',
			'hide_wp_admin' => '1',
			'wp_admin_redirect' => home_url(),
			'logout_redirect_url' => home_url(),
			'current_tab' => 'settings',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$this->assertEquals( 'my-custom-slug', $this->get_options()['custom_login_slug'] );
	}

	/**
	 * Test number field saves correctly.
	 */
	public function test_number_field_saves_correctly() {
		$_POST = array(
			'advanced_security_enabled' => '1',
			'disable_xmlrpc' => '1',
			'disable_rest_api' => '1',
			'brute_force_enabled' => '1',
			'max_login_attempts' => '7',
			'lockout_duration' => '45',
			'login_logging_enabled' => '1',
			'ip_whitelist' => '',
			'progressive_lockout_enabled' => '1',
			'max_lockout_duration' => '300',
			'current_tab' => 'advanced',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$new_options = $this->get_options();
		$this->assertEquals( '7', $new_options['max_login_attempts'] );
		$this->assertEquals( '45', $new_options['lockout_duration'] );
		$this->assertEquals( '300', $new_options['max_lockout_duration'] );
	}

	/**
	 * Test textarea field saves correctly.
	 */
	public function test_textarea_field_saves_correctly() {
		$whitelist_content = "192.168.1.1\n10.0.0.0/8\n172.16.0.0/16";

		$_POST = array(
			'advanced_security_enabled' => '1',
			'disable_xmlrpc' => '1',
			'disable_rest_api' => '1',
			'brute_force_enabled' => '1',
			'max_login_attempts' => '3',
			'lockout_duration' => '15',
			'login_logging_enabled' => '1',
			'ip_whitelist' => $whitelist_content,
			'progressive_lockout_enabled' => '1',
			'max_lockout_duration' => '60',
			'current_tab' => 'advanced',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$this->assertEquals( $whitelist_content, $this->get_options()['ip_whitelist'] );
	}

	/**
	 * Test advanced settings clamp values to valid ranges.
	 */
	public function test_advanced_settings_clamp_to_valid_ranges() {
		$_POST = array(
			'advanced_security_enabled' => '1',
			'disable_xmlrpc' => '1',
			'disable_rest_api' => '1',
			'brute_force_enabled' => '1',
			'max_login_attempts' => '100', // Invalid - should clamp to 10.
			'lockout_duration' => '200', // Invalid - should clamp to 60.
			'login_logging_enabled' => '1',
			'ip_whitelist' => '',
			'progressive_lockout_enabled' => '1',
			'max_lockout_duration' => '5000', // Invalid - should clamp to 1440.
			'current_tab' => 'advanced',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$new_options = $this->get_options();
		$this->assertEquals( 10, $new_options['max_login_attempts'] );
		$this->assertEquals( 60, $new_options['lockout_duration'] );
		$this->assertEquals( 1440, $new_options['max_lockout_duration'] );
	}

	/**
	 * Test email settings saves correctly on Email tab.
	 */
	public function test_email_tab_saves_correctly() {
		$_POST = array(
			'email_notifications_enabled' => '1',
			'lockout_email_enabled' => '1',
			'lockout_email_recipient' => 'admin@example.com',
			'lockout_email_subject' => 'Security Alert',
			'lockout_email_body' => "Your site {site_name} has been locked out.\nIP: {ip}",
			'login_alert_admin' => '1',
			'login_alert_new_ip' => '1',
			'current_tab' => 'email',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$new_options = $this->get_options();
		$this->assertEquals( 1, $new_options['email_notifications_enabled'] );
		$this->assertEquals( 1, $new_options['lockout_email_enabled'] );
		$this->assertEquals( 'admin@example.com', $new_options['lockout_email_recipient'] );
		$this->assertEquals( 'Security Alert', $new_options['lockout_email_subject'] );
		$this->assertEquals( "Your site {site_name} has been locked out.\nIP: {ip}", $new_options['lockout_email_body'] );
		$this->assertEquals( 1, $new_options['login_alert_admin'] );
		$this->assertEquals( 1, $new_options['login_alert_new_ip'] );
	}

	/**
	 * Test saving email tab can disable email notifications.
	 */
	public function test_email_tab_can_disable_notifications() {
		// Set initial values.
		$this->plugin->update_options(
			array(
				'email_notifications_enabled' => 1,
				'lockout_email_enabled' => 1,
				'login_alert_admin' => 1,
			)
		);

		// Simulate POST with all email notifications disabled.
		$_POST = array(
			'email_notifications_enabled' => '0',
			'lockout_email_enabled' => '0',
			'lockout_email_recipient' => '',
			'lockout_email_subject' => '',
			'lockout_email_body' => '',
			'login_alert_admin' => '0',
			'login_alert_new_ip' => '0',
			'current_tab' => 'email',
			'_wpnonce' => wp_create_nonce( 'mscsl_save_settings' ),
		);

		$_REQUEST = $_POST;
		set_current_screen( 'settings_page_msc-stealth-login' );

		$settings = new MSCSL\Settings( $this->plugin );
		$settings->handle_save();

		$new_options = $this->get_options();
		$this->assertEquals( 0, $new_options['email_notifications_enabled'] );
		$this->assertEquals( 0, $new_options['lockout_email_enabled'] );
		$this->assertEquals( 0, $new_options['login_alert_admin'] );
		$this->assertEquals( 0, $new_options['login_alert_new_ip'] );
	}
}
