<?php
/**
 * Test core plugin functionality for MSC Stealth Login.
 *
 * @package MSCSL
 */

/**
 * Test core plugin functionality.
 *
 * @covers MSCSL\Plugin
 */
class Test_Core extends WP_UnitTestCase {

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
		delete_option( 'mscsl_options' );
		delete_option( 'msc_recovery_token' );

		parent::tear_down();
	}

	/**
	 * Test singleton instance returns same instance.
	 */
	public function test_singleton_instance() {
		$instance1 = MSCSL\Plugin::instance();
		$instance2 = MSCSL\Plugin::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test plugin activation sets default options.
	 */
	public function test_plugin_activation_sets_default_options() {
		delete_option( 'mscsl_options' );

		MSCSL\Plugin::activate();

		$options = get_option( 'mscsl_options' );
		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'module_enabled', $options );
		$this->assertEquals( 1, $options['module_enabled'] );
	}

	/**
	 * Test plugin activation does not overwrite existing options.
	 */
	public function test_plugin_activation_does_not_overwrite_existing() {
		// Set existing options.
		update_option(
			'mscsl_options',
			array(
				'module_enabled'    => 0,
				'custom_login_slug' => 'my-custom-slug',
			)
		);

		MSCSL\Plugin::activate();

		$options = get_option( 'mscsl_options' );
		$this->assertEquals( 0, $options['module_enabled'] );
		$this->assertEquals( 'my-custom-slug', $options['custom_login_slug'] );
	}

	/**
	 * Test plugin activation generates recovery token if not exists.
	 */
	public function test_plugin_activation_generates_recovery_token() {
		delete_option( 'msc_recovery_token' );

		MSCSL\Plugin::activate();

		$token = get_option( 'msc_recovery_token' );
		$this->assertNotEmpty( $token );
		$this->assertEquals( 32, strlen( $token ) );
	}

	/**
	 * Test plugin activation does not regenerate existing recovery token.
	 */
	public function test_plugin_activation_does_not_regenerate_existing_token() {
		$existing_token = 'existing-token-value-123456789012';
		update_option( 'msc_recovery_token', $existing_token );

		MSCSL\Plugin::activate();

		$token = get_option( 'msc_recovery_token' );
		$this->assertEquals( $existing_token, $token );
	}

	/**
	 * Test plugin deactivation clears scheduled events.
	 */
	public function test_plugin_deactivation_clears_scheduled_events() {
		// Schedule an event.
		wp_schedule_event( time(), 'hourly', 'mscsl_brute_force_cleanup' );

		// Verify it's scheduled.
		$this->assertNotFalse( wp_next_scheduled( 'mscsl_brute_force_cleanup' ) );

		// Deactivate.
		MSCSL\Plugin::deactivate();

		// Verify it's cleared.
		$this->assertFalse( wp_next_scheduled( 'mscsl_brute_force_cleanup' ) );
	}

	/**
	 * Test plugin instance has settings property.
	 */
	public function test_plugin_has_settings() {
		$plugin = MSCSL\Plugin::instance();

		$this->assertTrue( property_exists( $plugin, 'settings' ) );
		$this->assertInstanceOf( MSCSL\Settings::class, $plugin->settings );
	}

	/**
	 * Test plugin instance has module property.
	 */
	public function test_plugin_has_module() {
		$plugin = MSCSL\Plugin::instance();

		$this->assertTrue( property_exists( $plugin, 'module' ) );
		$this->assertInstanceOf( MSCSL\Module::class, $plugin->module );
	}

	/**
	 * Test OPTION_KEY constant is defined.
	 */
	public function test_option_key_constant() {
		$this->assertEquals( 'mscsl_options', MSCSL\Plugin::OPTION_KEY );
		$this->assertEquals( 'mscsl_options', MSCSL_OPTION_KEY );
	}

	/**
	 * Test custom login URL is generated correctly.
	 */
	public function test_custom_login_url_format() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options( array( 'custom_login_slug' => 'my-secret-login' ) );

		$module = $plugin->module;
		$url    = $module->custom_login_url( home_url() . '/wp-login.php' );

		$this->assertEquals( home_url() . '/my-secret-login/', $url );
	}

	/**
	 * Test custom login URL with trailing slash.
	 */
	public function test_custom_login_url_always_has_trailing_slash() {
		$plugin = MSCSL\Plugin::instance();
		$plugin->update_options( array( 'custom_login_slug' => 'secret' ) );

		$module  = $plugin->module;
		$url     = $module->custom_login_url( home_url() . '/wp-login.php' );
		$parsed  = parse_url( $url );

		$this->assertEquals( '/secret/', $parsed['path'] );
	}
}
