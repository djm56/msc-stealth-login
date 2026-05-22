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

		// Migrate from old option key if it exists.
		$old_token = get_option( 'msc_recovery_token' );
		if ( $old_token ) {
			update_option( 'mscsl_recovery_token', $old_token );
			delete_option( 'msc_recovery_token' );
		} elseif ( ! get_option( 'mscsl_recovery_token' ) ) {
			update_option( 'mscsl_recovery_token', wp_generate_password( 32, false ) );
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

		// Run database upgrades if needed.
		Database::maybe_upgrade();
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

			// Proxy trust.
			'trust_proxy'              => 0,
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
	 * Supports exact IP matches and CIDR range notation (e.g., 192.168.1.0/24).
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if IP is whitelisted.
	 */
	public function is_ip_whitelisted( $ip ) {
		$whitelist = $this->get_option( 'ip_whitelist', '' );

		if ( empty( $whitelist ) ) {
			return false;
		}

		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		// Support both comma and newline separated IPs.
		$entries = array_filter( array_map( 'trim', preg_split( '/[,\n\r]/', $whitelist ) ) );

		foreach ( $entries as $entry ) {
			if ( empty( $entry ) ) {
				continue;
			}

			// CIDR range notation.
			if ( strpos( $entry, '/' ) !== false ) {
				if ( $this->ip_in_cidr( $ip, $entry ) ) {
					return true;
				}
			} else {
				// Exact IP match.
				if ( $ip === $entry && filter_var( $entry, FILTER_VALIDATE_IP ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if an IP address falls within a CIDR range.
	 *
	 * @since 1.0.5
	 *
	 * @param string $ip   IP address to check.
	 * @param string $cidr CIDR range (e.g., '192.168.1.0/24' or '2001:db8::/32').
	 * @return bool True if IP is within the CIDR range.
	 */
	private function ip_in_cidr( $ip, $cidr ) {
		// Validate IP address format.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		$parts = explode( '/', $cidr );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$subnet   = $parts[0];
		$prefix   = is_numeric( $parts[1] ) ? (int) $parts[1] : -1;
		$ip_is_v4 = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		$sub_is_v4 = filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );

		// IP and subnet must be the same type (both IPv4 or both IPv6).
		if ( $ip_is_v4 !== $sub_is_v4 ) {
			return false;
		}

		if ( $ip_is_v4 ) {
			// IPv4 CIDR matching.
			if ( $prefix < 0 || $prefix > 32 ) {
				return false;
			}

			$ip_long     = ip2long( $ip );
			$subnet_long = ip2long( $subnet );

			if ( false === $ip_long || false === $subnet_long ) {
				return false;
			}

			$mask = $prefix > 0 ? ( 0xFFFFFFFF << ( 32 - $prefix ) ) & 0xFFFFFFFF : 0;

			return ( $ip_long & $mask ) === ( $subnet_long & $mask );
		} else {
			// IPv6 CIDR matching.
			if ( $prefix < 0 || $prefix > 128 ) {
				return false;
			}

			$ip_bin   = inet_pton( $ip );
			$subnet_bin = inet_pton( $subnet );

			if ( false === $ip_bin || false === $subnet_bin ) {
				return false;
			}

			// Compare byte by byte using bitmask.
			$full_bytes = (int) floor( $prefix / 8 );
			$remaining_bits = $prefix % 8;

			for ( $i = 0; $i < 16; $i++ ) {
				if ( $i < $full_bytes ) {
					// Full byte — must match exactly.
					if ( $ip_bin[ $i ] !== $subnet_bin[ $i ] ) {
						return false;
					}
				} elseif ( $i === $full_bytes && $remaining_bits > 0 ) {
					// Partial byte — apply bitmask.
					$bitmask = chr( ( 0xFF << ( 8 - $remaining_bits ) ) & 0xFF );
					if ( ( $ip_bin[ $i ] & $bitmask ) !== ( $subnet_bin[ $i ] & $bitmask ) ) {
						return false;
					}
				} else {
					// Beyond prefix length — no need to compare.
					break;
				}
			}

			return true;
		}
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
