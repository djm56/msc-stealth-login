<?php
/**
 * Test multisite behaviour.
 *
 * Run with WP_TESTS_MULTISITE=1 to exercise the network paths; the multisite
 * cases skip themselves on a single-site install.
 *
 * @package MSCSL
 */

/**
 * Test per-site installation and network-shared lockouts.
 *
 * @covers MSCSL\Plugin
 */
class Test_Multisite extends WP_UnitTestCase {

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
		delete_option( 'mscsl_recovery_token' );
		delete_option( 'mscsl_db_version' );

		parent::tear_down();
	}

	/**
	 * Skip the current test unless the suite is running as multisite.
	 *
	 * @return void
	 */
	private function require_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires WP_TESTS_MULTISITE=1.' );
		}
	}

	/**
	 * Test install() seeds options, a recovery token and the schema version.
	 */
	public function test_install_seeds_site_data() {
		delete_option( 'mscsl_options' );
		delete_option( 'mscsl_recovery_token' );
		delete_option( 'mscsl_db_version' );

		MSCSL\Plugin::install();

		$this->assertIsArray( get_option( 'mscsl_options' ) );
		$this->assertNotEmpty( get_option( 'mscsl_recovery_token' ) );
		$this->assertSame( MSCSL\Database::DB_VERSION, get_option( 'mscsl_db_version' ) );
	}

	/**
	 * Test install() does not overwrite an existing recovery token.
	 */
	public function test_install_preserves_existing_recovery_token() {
		update_option( 'mscsl_recovery_token', 'existing-token-value' );

		MSCSL\Plugin::install();

		$this->assertSame( 'existing-token-value', get_option( 'mscsl_recovery_token' ) );
	}

	/**
	 * Test maybe_install() repairs a site that never ran the activation hook.
	 *
	 * This is the network-activation case: WordPress fires the activation hook
	 * once, so every other site has to install itself on first load.
	 */
	public function test_maybe_install_repairs_missing_recovery_token() {
		delete_option( 'mscsl_recovery_token' );

		MSCSL\Plugin::maybe_install();

		$this->assertNotEmpty( get_option( 'mscsl_recovery_token' ) );
	}

	/**
	 * Test lockouts are per-site by default.
	 */
	public function test_network_lockout_disabled_by_default() {
		$defaults = MSCSL\Plugin::default_options();

		$this->assertArrayHasKey( 'network_shared_lockout', $defaults );
		$this->assertSame( 0, $defaults['network_shared_lockout'] );
		$this->assertFalse( $this->plugin->uses_network_lockout() );
	}

	/**
	 * Test the network lockout flag is always false on single-site installs.
	 */
	public function test_network_lockout_is_ignored_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site only.' );
		}

		$this->plugin->update_options( array( 'network_shared_lockout' => 1 ) );

		$this->assertFalse( $this->plugin->uses_network_lockout() );
	}

	/**
	 * Test the network lockout flag is honoured on multisite.
	 */
	public function test_network_lockout_enabled_on_multisite() {
		$this->require_multisite();

		$this->plugin->update_options( array( 'network_shared_lockout' => 1 ) );

		$this->assertTrue( $this->plugin->uses_network_lockout() );

		$this->plugin->update_options( array( 'network_shared_lockout' => 0 ) );

		$this->assertFalse( $this->plugin->uses_network_lockout() );
	}

	/**
	 * Test the filter can force network-shared lockouts on.
	 */
	public function test_network_lockout_filter_can_force_sharing() {
		$this->require_multisite();

		$this->plugin->update_options( array( 'network_shared_lockout' => 0 ) );

		add_filter( 'mscsl_network_shared_lockout', '__return_true' );
		$shared = $this->plugin->uses_network_lockout();
		remove_filter( 'mscsl_network_shared_lockout', '__return_true' );

		$this->assertTrue( $shared );
	}

	/**
	 * Test each site on a network gets its own recovery token and options.
	 */
	public function test_each_site_installs_its_own_data() {
		$this->require_multisite();

		$main_token = get_option( 'mscsl_recovery_token' );
		$site_id    = self::factory()->blog->create();

		switch_to_blog( $site_id );

		delete_option( 'mscsl_recovery_token' );
		delete_option( 'mscsl_options' );
		delete_option( 'mscsl_db_version' );

		MSCSL\Plugin::maybe_install();

		$sub_token   = get_option( 'mscsl_recovery_token' );
		$sub_options = get_option( 'mscsl_options' );

		restore_current_blog();

		$this->assertNotEmpty( $sub_token );
		$this->assertNotSame( $main_token, $sub_token );
		$this->assertIsArray( $sub_options );
	}

	/**
	 * Test a network-shared lockout counter is visible from another site.
	 */
	public function test_shared_lockout_counter_crosses_sites() {
		$this->require_multisite();

		$key = 'mscsl_login_attempts_' . md5( '198.51.100.7' );
		set_site_transient( $key, array( 'count' => 3 ), HOUR_IN_SECONDS );

		$site_id = self::factory()->blog->create();
		switch_to_blog( $site_id );
		$seen_from_other_site = get_site_transient( $key );
		restore_current_blog();

		delete_site_transient( $key );

		$this->assertIsArray( $seen_from_other_site );
		$this->assertSame( 3, $seen_from_other_site['count'] );
	}
}
