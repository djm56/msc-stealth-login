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
	 *
	 * On a network activation WordPress only fires this hook once, in the
	 * context of the site the network admin happens to be on. Rather than
	 * looping every site on the network (which does not scale), each site
	 * installs itself on first load via install(), and new sites are handled
	 * by the wp_initialize_site hook.
	 *
	 * @since 1.3.0 Delegates the per-site work to install().
	 */
	public static function activate() {
		self::install();
	}

	/**
	 * Install the plugin's data for the current site.
	 *
	 * Safe to call repeatedly: every step is guarded, so this doubles as the
	 * self-heal path for sites that never saw the activation hook (network
	 * activation, sites created later, or an update without re-activation).
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public static function install() {
		$options = get_option( self::OPTION_KEY );
		if ( ! is_array( $options ) ) {
			update_option( self::OPTION_KEY, self::default_options() );
		}

		// Every site needs its own recovery token, otherwise the emergency
		// recovery URL is unusable on that site.
		if ( ! get_option( 'mscsl_recovery_token' ) ) {
			update_option( 'mscsl_recovery_token', wp_generate_password( 32, false ) );
		}

		// Create login attempts table.
		Database::create_table();
		update_option( 'mscsl_db_version', Database::DB_VERSION );

		// Schedule the daily login-log cleanup.
		if ( ! wp_next_scheduled( 'mscsl_brute_force_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'mscsl_brute_force_cleanup' );
		}

		// Schedule rewrite rules flush for the next page load.
		// During activation, the 'init' hook hasn't fired yet so our custom
		// rewrite rules aren't registered. Using a transient ensures the flush
		// happens after init fires and register_rewrite_rules() has run.
		set_transient( 'mscsl_flush_rewrite_rules', true, 60 );
	}

	/**
	 * Install the current site if it has never been installed.
	 *
	 * Reads a single autoloaded option in the common case, so this is cheap
	 * enough to run on every request.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public static function maybe_install() {
		if ( get_option( 'mscsl_db_version' ) && is_array( get_option( self::OPTION_KEY ) ) && get_option( 'mscsl_recovery_token' ) ) {
			// Fully installed — only check for pending schema migrations.
			Database::maybe_upgrade();
			return;
		}

		self::install();
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
		// Install this site if it has never been installed (covers network
		// activation, where the activation hook only runs once), and run any
		// pending schema migrations. Runs before the modules register hooks so
		// they read real options rather than falling back to defaults.
		self::maybe_install();

		$this->settings = new Settings( $this );
		$this->module   = new Module( $this );

		// Daily login-log cleanup (self-heals if the event is missing, e.g.
		// after an update without re-activation).
		add_action( 'mscsl_brute_force_cleanup', array( $this, 'cleanup_old_login_logs' ) );
		if ( ! wp_next_scheduled( 'mscsl_brute_force_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'mscsl_brute_force_cleanup' );
		}

		if ( ! get_option( 'mscsl_activated_time' ) ) {
			update_option( 'mscsl_activated_time', time() );
		}

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'maybe_render_review_notice' ) );
			add_action( 'admin_init', array( $this, 'maybe_handle_review_dismiss' ) );
		}
	}

	/**
	 * Shows a one-time, dismissible review request on the plugin's settings page.
	 *
	 * @return void
	 */
	public function maybe_render_review_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_msc-stealth-login' !== $screen->id ) {
			return;
		}

		if ( get_option( 'mscsl_review_dismissed' ) ) {
			return;
		}

		$since = (int) get_option( 'mscsl_activated_time', 0 );
		if ( $since <= 0 ) {
			update_option( 'mscsl_activated_time', time() );
			return;
		}

		if ( ( time() - $since ) < ( 7 * DAY_IN_SECONDS ) ) {
			return;
		}

		$review_url  = 'https://wordpress.org/support/plugin/msc-stealth-login/reviews/#new-post';
		$dismiss_url = wp_nonce_url( add_query_arg( 'mscsl_dismiss_review', '1' ), 'mscsl_dismiss_review' );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php esc_html_e( 'Enjoying MSC Stealth Login? A quick review would really help other WordPress users find it.', 'msc-stealth-login' ); ?>
				<a href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a review', 'msc-stealth-login' ); ?></a>
				&nbsp;·&nbsp;
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'No thanks', 'msc-stealth-login' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Permanently dismisses the review request.
	 *
	 * @return void
	 */
	public function maybe_handle_review_dismiss() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['mscsl_dismiss_review'] ) ) {
			return;
		}

		check_admin_referer( 'mscsl_dismiss_review' );
		update_option( 'mscsl_review_dismissed', 1 );
		wp_safe_redirect( remove_query_arg( array( 'mscsl_dismiss_review', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Cron callback: prunes login-history entries older than the retention period.
	 *
	 * @return void
	 */
	public function cleanup_old_login_logs() {
		/**
		 * Filters the login-history retention period in days.
		 *
		 * @param int $days Retention in days. Default 30.
		 */
		$days = (int) apply_filters( 'mscsl_log_retention_days', 30 );
		Database::delete_old_attempts( max( 1, $days ) );
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

			// Multisite: share brute-force lockouts across the network.
			'network_shared_lockout'      => 0,
		);
	}

	/**
	 * Whether brute-force lockouts are shared across a multisite network.
	 *
	 * When enabled, failed-attempt counters and progressive lockout
	 * multipliers are stored as site (network) transients, so an attacker gets
	 * one allowance for the whole network instead of one per site.
	 *
	 * @since 1.3.0
	 *
	 * @return bool
	 */
	public function uses_network_lockout() {
		if ( ! is_multisite() ) {
			return false;
		}

		/**
		 * Filters whether brute-force lockouts are shared network-wide.
		 *
		 * Useful for network operators who want to force sharing on for every
		 * site from an mu-plugin rather than per site.
		 *
		 * @since 1.3.0
		 *
		 * @param bool $shared Whether lockouts are shared across the network.
		 */
		return (bool) apply_filters( 'mscsl_network_shared_lockout', (bool) $this->get_option( 'network_shared_lockout', 0 ) );
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
