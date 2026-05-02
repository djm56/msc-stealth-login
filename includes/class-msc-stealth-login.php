<?php
/**
 * Main bootstrap class for MSC Stealth Login.
 *
 * @package MSCSL
 */

namespace MSCSL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	const OPTION_KEY = 'mscsl_options';

	/**
	 * Database instance.
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Module instance.
	 *
	 * @var Module|null
	 */
	private $module = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Activate plugin.
	 */
	public static function activate() {
		$options = get_option( self::OPTION_KEY );
		if ( ! is_array( $options ) ) {
			update_option( self::OPTION_KEY, self::default_options() );
		}

		// Generate recovery token if not exists.
		if ( ! get_option( 'msc_recovery_token' ) ) {
			update_option( 'msc_recovery_token', wp_generate_password( 32, false ) );
		}

		// Create login attempts table.
		Database::create_table();

		// Schedule rewrite rules flush for the next page load.
		// During activation, the 'init' hook hasn't fired yet so our custom
		// rewrite rules aren't registered. Using a transient ensures the flush
		// happens after init fires and register_rewrite_rules() has run.
		set_transient( 'mscsl_flush_rewrite_rules', true, 60 );
	}

	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {
		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'mscsl_brute_force_cleanup' );

		// Flush rewrite rules on deactivation.
		flush_rewrite_rules();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = new Settings( $this );
		$this->module   = new Module( $this );
	}

	/**
	 * Default options.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_options() {
		return array(
			'module_enabled'            => 1,
			'custom_login_slug'        => 'secure-login',
			'hide_wp_admin'            => 1,
			'wp_admin_redirect'        => home_url(),
			'advanced_security_enabled' => 0,
			'disable_xmlrpc'           => 1,
			'disable_rest_api'          => 1,
			'brute_force_enabled'      => 1,
			'max_login_attempts'       => 3,
			'lockout_duration'         => 15,
			'logout_redirect_url'      => home_url(),

			// Login logging & notifications.
			'login_logging_enabled'      => 1,
			'email_notifications_enabled' => 0,
			'lockout_email_enabled'     => 0,
			'lockout_email_recipient'   => '',
			'ip_whitelist'             => '',
			'progressive_lockout_enabled' => 0,
			'max_lockout_duration'     => 60,

			// Email customization.
			'lockout_email_subject'     => '',
			'lockout_email_body'       => '',

			// Login alerts.
			'login_alert_admin'        => 0,
			'login_alert_new_ip'       => 0,
		);
	}

	/**
	 * Option getter.
	 *
	 * @param string $key     Key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_option( $key, $default = null ) {
		$options = wp_parse_args( get_option( self::OPTION_KEY, array() ), self::default_options() );
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Get all options.
	 *
	 * @return array<string,mixed>
	 */
	public function get_options() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::default_options() );
	}

	/**
	 * Save merged options.
	 *
	 * @param array<string,mixed> $new_options New values.
	 * @return bool
	 */
	public function update_options( $new_options ) {
		$current = wp_parse_args( get_option( self::OPTION_KEY, array() ), self::default_options() );
		$merged  = array_merge( $current, $new_options );
		return (bool) update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Check if an IP is whitelisted.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	public function is_ip_whitelisted( $ip ) {
		$whitelist = $this->get_option( 'ip_whitelist', '' );

		if ( empty( $whitelist ) ) {
			return false;
		}

		// Support both comma and newline separated IPs.
		$whitelist_ips = array_filter(
			array_map( 'trim', preg_split( '/[,\n\r]/', $whitelist ) ),
			function( $entry ) {
				// Validate each entry as either a valid IP or CIDR range.
				if ( empty( $entry ) ) {
					return false;
				}
				// Check for CIDR notation.
				if ( strpos( $entry, '/' ) !== false ) {
					$parts = explode( '/', $entry );
					return filter_var( $parts[0], FILTER_VALIDATE_IP ) && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 128;
				}
				return (bool) filter_var( $entry, FILTER_VALIDATE_IP );
			}
		);

		return in_array( $ip, $whitelist_ips, true );
	}

	/**
	 * Get database instance.
	 *
	 * @return Database
	 */
	public function get_database() {
		if ( null === $this->database ) {
			$this->database = new Database();
		}
		return $this->database;
	}
}
