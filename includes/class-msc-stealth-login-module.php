<?php
/**
 * Module class for MSC Stealth Login.
 *
 * Handles the core stealth login functionality including URL rewriting,
 * login blocking, XML-RPC protection, REST API protection, and brute force protection.
 *
 * @package MSCSL
 */

namespace MSCSL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Module {

	/**
	 * Active plugin conflicts for the admin notice.
	 *
	 * @var array<string>
	 */
	private $active_conflicts = array();

	/**
	 * Main plugin instance.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Transient key for tracking login attempts.
	 *
	 * @var string
	 */
	const ATTEMPTS_TRANSIENT = 'mscsl_login_attempts_';

	/**
	 * Progressive lockout multiplier key.
	 *
	 * @var string
	 */
	const LOCKOUT_MULTIPLIER_KEY = 'mscsl_lockout_multiplier_';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		// Initialize hooks.
		$this->init_hooks();

		// Check for conflicting plugins.
		$this->check_conflicts();
	}

	/**
	 * Check for conflicting plugins and show notices.
	 */
	private function check_conflicts() {
		// is_plugin_active() is only available in admin context.
		if ( ! is_admin() ) {
			return;
		}

		// This runs on plugins_loaded, before wp-admin/includes/admin.php has
		// pulled in plugin.php, so load it ourselves.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$conflicting = array(
			'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
			'wp-super-cache/wp-cache.php'       => 'WP Super Cache',
			'wp-rocket/wp-rocket.php'           => 'WP Rocket',
			'wordfence/wordfence.php'           => 'Wordfence Security',
			'ithemes-security/ithemes-security.php' => 'iThemes Security',
			'sucuri-scanner/sucuri.php'         => 'Sucuri Security',
		);

		$active_conflicts = array();

		foreach ( $conflicting as $plugin => $name ) {
			if ( is_plugin_active( $plugin ) ) {
				$active_conflicts[] = $name;
			}
		}

		if ( ! empty( $active_conflicts ) ) {
			$this->active_conflicts = $active_conflicts;
			add_action( 'admin_notices', array( $this, 'show_conflict_notice' ) );
		}
	}

	/**
	 * Display admin notice about conflicting plugins.
	 *
	 * @since 1.0.7
	 */
	public function show_conflict_notice() {
		if ( empty( $this->active_conflicts ) ) {
			return;
		}
		$list = implode( ', ', $this->active_conflicts );
		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo '<strong>MSC Stealth Login:</strong> ';
		printf(
			/* translators: %s: list of detected plugins */
			esc_html__( 'The following plugins were detected: %s. You may need to configure cache or security exclusions for your custom login URL.', 'msc-stealth-login' ),
			esc_html( $list )
		);
		echo '</p></div>';
	}

	/**
	 * Initialize all hooks.
	 */
	private function init_hooks() {
		// Handle recovery URL (before rewrite rules).
		add_action( 'init', array( $this, 'handle_recovery_url' ), 0 );

		// Only register rewrite rules if module is enabled.
		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'register_rewrite_rules' ), 1 );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'handle_login_request' ), 1 );
		}

		// Block default login.
		add_action( 'login_init', array( $this, 'block_default_login' ) );
		add_action( 'init', array( $this, 'block_wp_admin' ), 1 );

		// Handle logout redirect.
		add_action( 'wp_logout', array( $this, 'handle_logout_redirect' ) );

		// Log successful logins if logging is enabled.
		if ( $this->plugin->get_option( 'login_logging_enabled', 1 ) ) {
			add_action( 'wp_login', array( $this, 'log_successful_login' ), 10, 2 );
		}

		// Only apply advanced security features if advanced_security_enabled is ON.
		if ( $this->plugin->get_option( 'advanced_security_enabled', 0 ) ) {
			// XML-RPC protection.
			if ( $this->plugin->get_option( 'disable_xmlrpc', 1 ) ) {
				add_filter( 'xmlrpc_enabled', '__return_false' );
				add_filter( 'xmlrpc_methods', array( $this, 'block_xmlrpc_methods' ) );
				add_action( 'xmlrpc_call', array( $this, 'block_xmlrpc_call' ) );
			}

			// REST API user enumeration protection.
			if ( $this->plugin->get_option( 'disable_rest_api', 1 ) ) {
				add_filter( 'rest_endpoints', array( $this, 'block_user_enumeration' ) );
				add_action( 'init', array( $this, 'block_rest_api_author_param' ), 1 );
			}

			// Brute force protection.
			if ( $this->plugin->get_option( 'brute_force_enabled', 1 ) ) {
				// IP whitelist check (runs first, priority 1).
				add_filter( 'authenticate', array( $this, 'check_ip_whitelist' ), 1 );

				add_filter( 'authenticate', array( $this, 'check_login_attempts' ), 30 );
				add_action( 'wp_login_failed', array( $this, 'record_failed_attempt' ) );

				// Progressive lockout enhancement.
				if ( $this->plugin->get_option( 'progressive_lockout_enabled', 0 ) ) {
					add_filter( 'authenticate', array( $this, 'check_progressive_lockout' ), 32 );
				}
			}
		}

		// Add recovery option.
		add_action( 'admin_post_mscsl_send_recovery_email', array( $this, 'handle_recovery_email' ) );
		add_action( 'admin_notices', array( $this, 'show_recovery_notice' ) );

		// Handle regenerate recovery token.
		add_action( 'admin_post_mscsl_regenerate_recovery_token', array( $this, 'handle_regenerate_recovery_token' ) );

		// Data tracking notice.
		add_action( 'admin_notices', array( $this, 'show_data_tracking_notice' ) );
		add_action( 'wp_ajax_mscsl_dismiss_data_notice', array( $this, 'dismiss_data_tracking_notice' ) );

		// Flush rewrite rules if scheduled by activation or settings save.
		// Runs at priority 99, after register_rewrite_rules() at priority 1.
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 99 );
	}

	/**
	 * Handle recovery URL access.
	 * Allows access to wp-login.php via recovery URL when stealth login is enabled.
	 */
	public function handle_recovery_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- recovery URL uses token-based auth, not form submission
		if ( ! isset( $_GET['mscsl_recovery'] ) ) {
			return;
		}

		$recovery_token = get_option( 'mscsl_recovery_token', '' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- token-based auth, not a form submission
		if ( empty( $recovery_token ) || ! hash_equals( $recovery_token, sanitize_text_field( wp_unslash( $_GET['mscsl_recovery'] ) ) ) ) {
			return;
		}

		// Recovery URL is valid - allow access to wp-login.php.
		// The stealth login will redirect this to the custom URL.
		remove_action( 'login_init', array( $this, 'block_default_login' ) );

		// Override the login URL filter to point to wp-login.php for recovery.
		add_filter( 'login_url', array( $this, 'recovery_login_url' ) );
	}

	/**
	 * Recovery login URL filter.
	 *
	 * @param string $login_url Default login URL.
	 * @return string
	 */
	public function recovery_login_url( $login_url ) {
		return add_query_arg( 'mscsl_recovery', get_option( 'mscsl_recovery_token', '' ), wp_login_url() );
	}

	/**
	 * Handle regenerate recovery token.
	 */
	public function handle_regenerate_recovery_token() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'msc-stealth-login' ) );
		}

		check_admin_referer( 'mscsl_regenerate_recovery_token' );

		$new_token = wp_generate_password( 32, false );
		update_option( 'mscsl_recovery_token', $new_token );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'msc-stealth-login',
					'tab'        => 'settings',
					'recovery'   => 'regenerated',
					'_wpnonce'   => wp_create_nonce( 'mscsl_recovery_notice' ),
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Register custom rewrite rules for stealth login.
	 */
	public function register_rewrite_rules() {
		$slug = $this->plugin->get_option( 'custom_login_slug', 'secure-login' );

		if ( empty( $slug ) ) {
			$slug = 'secure-login';
		}

		add_rewrite_rule(
			'^' . preg_quote( $slug, '/' ) . '/?$',
			'index.php?mscsl_login=1',
			'top'
		);

		add_rewrite_rule(
			'^' . preg_quote( $slug, '/' ) . '/?([^/]*)/?$',
			'index.php?mscsl_login=1&mscsl_action=$matches[1]',
			'top'
		);
	}

	/**
	 * Add custom query vars.
	 *
	 * @param array<string> $vars Query vars.
	 * @return array<string>
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'mscsl_login';
		$vars[] = 'mscsl_action';
		return $vars;
	}

	/**
	 * Handle login requests to custom URL.
	 */
	public function handle_login_request() {
		$login_var = get_query_var( 'mscsl_login' );

		if ( empty( $login_var ) ) {
			return;
		}

		// Check if locked out first.
		if ( $this->is_locked_out() ) {
			$this->show_lockout_message();
		}

		// If this is a POST request (form submission), let wp-login.php handle it.
		// Override the login URL first.
		add_filter( 'login_url', array( $this, 'custom_login_url' ) );

		// Don't clear query vars - let wp-login.php see them.
		// WordPress needs these for proper redirect handling.

		// Handle the login process.
		nocache_headers();
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	/**
	 * Custom login URL filter.
	 *
	 * @param string $login_url Default login URL.
	 * @return string
	 */
	public function custom_login_url( $login_url ) {
		$slug = $this->plugin->get_option( 'custom_login_slug', 'secure-login' );
		return trailingslashit( home_url() ) . $slug;
	}

	/**
	 * Block access to default wp-login.php.
	 */
	public function block_default_login() {
		// This method runs on every page load as a frontend security filter.
		// Nonce verification is not applicable — $_SERVER and $_GET are read for
		// request routing, not form processing.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		// Get the requested file.
		$requested_file = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';

		if ( false === strpos( $requested_file, 'wp-login.php' ) ) {
			return;
		}

		// Check if this is the custom login URL.
		$slug         = $this->plugin->get_option( 'custom_login_slug', 'secure-login' );
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request_path = wp_parse_url( $request_uri, PHP_URL_PATH ) ?? '';
		if ( empty( $request_path ) ) {
			$request_path = '/';
		}

		// Use precise regex matching for custom slug.
		$slug_pattern = '/' . preg_quote( $slug, '/' ) . '/?$';
		if ( preg_match( $slug_pattern, $request_path ) ) {
			return;
		}

		// CRITICAL FIX: Allow POST requests (form submissions).
		// WordPress processes login forms via POST to wp-login.php.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// CRITICAL FIX: Allow if user is already logged in.
		// This handles successful login redirects to wp-admin.
		if ( is_user_logged_in() ) {
			return;
		}

		// CRITICAL FIX: Allow if coming from custom login URL (referer check).
		// This handles failed login redirects back to wp-login.php.
		$referer = wp_get_referer();
		if ( $referer && false !== strpos( $referer, $slug ) ) {
			return;
		}

		// Allow WordPress login error redirects (when login fails, WP redirects back with error params).
		$wp_actions = array( 'logout', 'lostpassword', 'rp', 'resetpass', 'login' );
		$action     = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		if ( in_array( $action, $wp_actions, true ) ) {
			return;
		}

		// Allow if there's a WordPress error message (failed login).
		// WordPress adds various params on login redirects.
		if ( isset( $_GET['login'] ) || isset( $_GET['wp-error'] ) ) {
			return;
		}

		// Allow legitimate logout.
		if ( isset( $_GET['loggedout'] ) ) {
			return;
		}

		// CRITICAL FIX: Allow WordPress reauthentication and interim login params.
		if ( isset( $_GET['reauth'] ) || isset( $_GET['interim-login'] ) || isset( $_GET['customize-login'] ) ) {
			return;
		}

		// Block access - show 404 or redirect.
		$this->show_blocked_message();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Block direct access to wp-admin.
	 */
	public function block_wp_admin() {
		// Only block if hide_wp_admin is enabled.
		if ( ! $this->plugin->get_option( 'hide_wp_admin', 1 ) ) {
			return;
		}

		// Allow AJAX requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Get current path.
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request_path = wp_parse_url( $request_uri, PHP_URL_PATH ) ?? '';

		// Check if accessing wp-admin.
		if ( empty( $request_path ) || false === strpos( $request_path, '/wp-admin' ) ) {
			return;
		}

		// Allow admin-ajax.php.
		if ( false !== strpos( $request_path, 'admin-ajax.php' ) ) {
			return;
		}

		// If user is already logged in, let them through.
		if ( is_user_logged_in() ) {
			return;
		}

		// Not logged in - redirect them.
		$redirect_url = $this->plugin->get_option( 'wp_admin_redirect', home_url() );
		if ( empty( $redirect_url ) ) {
			$redirect_url = home_url();
		}
		// Validate redirect URL is local to prevent open redirect attacks.
		$redirect_url = wp_validate_redirect( $redirect_url, home_url() );

		wp_safe_redirect( $redirect_url, 302 );
		exit;
	}

	/**
	 * Handle logout redirect.
	 */
	public function handle_logout_redirect() {
		$redirect_url = $this->plugin->get_option( 'logout_redirect_url', home_url() );

		if ( ! empty( $redirect_url ) ) {
			wp_safe_redirect( $redirect_url, 302 );
			exit;
		}
	}

	/**
	 * Block XML-RPC methods.
	 *
	 * @param array<string> $methods XML-RPC methods.
	 * @return array<string>
	 */
	public function block_xmlrpc_methods( $methods ) {
		unset( $methods['pingback.ping'] );
		unset( $methods['pingback.extensions.getPingbacks'] );
		unset( $methods['wp.getUsers'] );
		unset( $methods['wp.getUser'] );
		unset( $methods['wp.getUsersBlogs'] );
		return $methods;
	}

	/**
	 * Block XML-RPC calls.
	 *
	 * @param string $method XML-RPC method.
	 */
	public function block_xmlrpc_call( $method ) {
		if ( 'pingback.ping' === $method ) {
			wp_die( esc_html__( 'Pingback functionality is disabled.', 'msc-stealth-login' ) );
		}
	}

	/**
	 * Block REST API user enumeration.
	 *
	 * @param array $endpoints REST endpoints.
	 * @return array
	 */
	public function block_user_enumeration( $endpoints ) {
		if ( isset( $endpoints['/wp/v2/users'] ) ) {
			unset( $endpoints['/wp/v2/users'] );
		}

		if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
			unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}

		return $endpoints;
	}

	/**
	 * Block REST API author parameter.
	 */
	public function block_rest_api_author_param() {
		// Redirect author queries to prevent enumeration.
		add_action( 'template_redirect', array( $this, 'maybe_block_author_query' ), 1 );
	}

	/**
	 * Check if IP is whitelisted - bypass all brute force protection.
	 *
	 * @param WP_User|WP_Error|null $user User or error.
	 * @return WP_User|WP_Error|null
	 */
	public function check_ip_whitelist( $user ) {
		// Only check for unauthenticated users.
		if ( is_wp_error( $user ) || is_a( $user, 'WP_User' ) ) {
			return $user;
		}

		$ip = $this->get_client_ip();

		if ( empty( $ip ) ) {
			return $user;
		}

		if ( $this->plugin->is_ip_whitelisted( $ip ) ) {
			// Log the whitelisted access if logging is enabled.
			if ( $this->plugin->get_option( 'login_logging_enabled', 1 ) ) {
				Database::log_attempt(
					$ip,
					'N/A',
					'whitelisted',
					$this->get_user_agent() // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- authentication filter, not form processing
				);
			}
			return $user;
		}

		return $user;
	}

	/**
	 * Maybe block author query.
	 */
	public function maybe_block_author_query() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- frontend security filter, not form processing
		if ( ! isset( $_GET['author'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading query param in security filter
		$author = absint( $_GET['author'] );

		if ( $author > 0 ) {
			// Redirect to homepage instead of showing user.
			wp_safe_redirect( trailingslashit( home_url() ), 301 );
			exit;
		}
	}

	/**
	 * Check login attempts for brute force protection.
	 *
	 * @param WP_User|WP_Error|null $user     User or error.
	 * @return WP_User|WP_Error|null
	 */
	public function check_login_attempts( $user ) {
		// Don't interfere if already an error or user.
		if ( is_wp_error( $user ) || is_a( $user, 'WP_User' ) ) {
			return $user;
		}

		$ip = $this->get_client_ip();

		// Skip whitelisted IPs.
		if ( $this->plugin->is_ip_whitelisted( $ip ) ) {
			return $user;
		}

		// Check if locked out.
		if ( $this->is_locked_out() ) {
			return new \WP_Error(
				'locked_out',
				$this->get_lockout_message()
			);
		}

		return $user;
	}

	/**
	 * Record a failed login attempt.
	 *
	 * @param string $username Username that failed.
	 */
	public function record_failed_attempt( $username ) {
		$ip = $this->get_client_ip();

		if ( empty( $ip ) ) {
			return;
		}

		// Skip whitelisted IPs.
		if ( $this->plugin->is_ip_whitelisted( $ip ) ) {
			return;
		}

		$transient_key = self::ATTEMPTS_TRANSIENT . md5( $ip );
		$attempts      = $this->get_lockout_transient( $transient_key );

		if ( false === $attempts ) {
			$attempts = array(
				'count'     => 1,
				'first_attempt' => time(),
				'last_attempt'  => time(),
			);
		} else {
			$attempts['count']++;
			$attempts['last_attempt'] = time();
		}

		$max_attempts = $this->plugin->get_option( 'max_login_attempts', 3 );

		// Calculate lockout duration.
		if ( $this->plugin->get_option( 'progressive_lockout_enabled', 0 ) ) {
			$multiplier = $this->get_lockout_multiplier( $ip );
			$base_duration = $this->plugin->get_option( 'lockout_duration', 15 ) * 60;
			$lockout_duration = $base_duration * $multiplier;

			// Increment multiplier if locked out.
			if ( $attempts['count'] >= $max_attempts ) {
				$new_multiplier = min( 8, $multiplier * 2 );
				$this->set_lockout_multiplier( $ip, $new_multiplier );
				$lockout_duration = $base_duration * $new_multiplier;
			}

			// Enforce max duration.
			$max_duration = $this->plugin->get_option( 'max_lockout_duration', 60 ) * 60;
			$lockout_duration = min( $lockout_duration, $max_duration );
		} else {
			$lockout_duration = $this->plugin->get_option( 'lockout_duration', 15 ) * 60;
		}

		$this->set_lockout_transient( $transient_key, $attempts, $lockout_duration );

		// Log to database if logging is enabled.
		if ( $this->plugin->get_option( 'login_logging_enabled', 1 ) ) {
			$result = ( $attempts['count'] >= $max_attempts ) ? 'lockout' : 'failure';
			Database::log_attempt(
				$ip,
				$username,
				$result,
				$this->get_user_agent() // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- authentication filter, not form processing
			);
		}

		// Send email notification on lockout if enabled.
		if ( $attempts['count'] >= $max_attempts && 
		     $this->plugin->get_option( 'email_notifications_enabled', 0 ) &&
		     $this->plugin->get_option( 'lockout_email_enabled', 0 ) ) {
			$this->send_lockout_notification( $ip, $attempts );
		}
	}

	/**
	 * Check if current IP is locked out.
	 *
	 * @return bool
	 */
	private function is_locked_out() {
		$ip       = $this->get_client_ip();
		$attempts = $this->get_attempts_for_ip( $ip );

		if ( false === $attempts ) {
			return false;
		}

		$max_attempts = $this->plugin->get_option( 'max_login_attempts', 3 );

		if ( $attempts['count'] >= $max_attempts ) {
			return true;
		}

		return false;
	}

	/**
	 * Get attempts for an IP.
	 *
	 * @param string $ip IP address.
	 * @return array|false
	 */
	private function get_attempts_for_ip( $ip ) {
		if ( empty( $ip ) ) {
			return false;
		}

		$transient_key = self::ATTEMPTS_TRANSIENT . md5( $ip );
		return $this->get_lockout_transient( $transient_key );
	}

	/**
	 * Get lockout message.
	 *
	 * @return string
	 */
	private function get_lockout_message() {
		$lockout_duration = $this->plugin->get_option( 'lockout_duration', 15 );

		return sprintf(
			/* translators: %d is the number of minutes */
			esc_html__( 'Too many failed login attempts. Please try again in %d minutes.', 'msc-stealth-login' ),
			absint( $lockout_duration )
		);
	}

	/**
	 * Check progressive lockout - increases lockout duration with repeated failures.
	 *
	 * @param WP_User|WP_Error|null $user User or error.
	 * @return WP_User|WP_Error|null
	 */
	public function check_progressive_lockout( $user ) {
		// Don't interfere if already an error or user.
		if ( is_wp_error( $user ) || is_a( $user, 'WP_User' ) ) {
			return $user;
		}

		$ip       = $this->get_client_ip();

		if ( empty( $ip ) ) {
			return $user;
		}

		// Skip whitelisted IPs.
		if ( $this->plugin->is_ip_whitelisted( $ip ) ) {
			return $user;
		}

		$attempts = $this->get_attempts_for_ip( $ip );

		if ( false === $attempts ) {
			return $user;
		}

		$max_attempts = $this->plugin->get_option( 'max_login_attempts', 3 );

		if ( $attempts['count'] >= $max_attempts ) {
			$multiplier = $this->get_lockout_multiplier( $ip );
			return new \WP_Error(
				'locked_out',
				$this->get_progressive_lockout_message( $multiplier )
			);
		}

		return $user;
	}

	/**
	 * Get lockout multiplier for an IP.
	 *
	 * @param string $ip IP address.
	 * @return int
	 */
	private function get_lockout_multiplier( $ip ) {
		if ( empty( $ip ) ) {
			return 1;
		}

		$key = self::LOCKOUT_MULTIPLIER_KEY . md5( $ip );
		$val = $this->get_lockout_transient( $key );

		if ( false === $val ) {
			return 1;
		}

		return absint( $val );
	}

	/**
	 * Set lockout multiplier for an IP.
	 *
	 * @param string $ip   IP address.
	 * @param int    $mult Multiplier.
	 */
	private function set_lockout_multiplier( $ip, $mult ) {
		if ( empty( $ip ) ) {
			return;
		}

		$key = self::LOCKOUT_MULTIPLIER_KEY . md5( $ip );
		$this->set_lockout_transient( $key, absint( $mult ), 24 * HOUR_IN_SECONDS );
	}

	/**
	 * Get progressive lockout message.
	 *
	 * @param int $multiplier Current multiplier.
	 * @return string
	 */
	private function get_progressive_lockout_message( $multiplier ) {
		$base_duration = $this->plugin->get_option( 'lockout_duration', 15 );
		$total_minutes = $base_duration * $multiplier;

		// Cap at reasonable display value (24 hours).
		if ( $total_minutes > 1440 ) {
			$total_minutes = 1440;
		}

		if ( $total_minutes >= 60 ) {
			$hours   = floor( $total_minutes / 60 );
			$minutes = $total_minutes % 60;

			if ( $hours > 24 ) {
				$days = floor( $hours / 24 );
				$hours = $hours % 24;

				return sprintf(
					/* translators: %1$d days and %2$d hours */
					esc_html__( 'Too many failed login attempts. You are temporarily blocked for %1$d days and %2$d hours.', 'msc-stealth-login' ),
					absint( $days ),
					absint( $hours )
				);
			}

			return sprintf(
				/* translators: %1$d hours and %2$d minutes */
				esc_html__( 'Too many failed login attempts. You are temporarily blocked for %1$d hours and %2$d minutes.', 'msc-stealth-login' ),
				absint( $hours ),
				absint( $minutes )
			);
		}

		return sprintf(
			/* translators: %d is the number of minutes */
			esc_html__( 'Too many failed login attempts. Please try again in %d minutes.', 'msc-stealth-login' ),
			absint( $total_minutes )
		);
	}

	/**
	 * Send lockout notification email.
	 *
	 * @param string $ip       IP address.
	 * @param array  $attempts Attempts data.
	 */
	private function send_lockout_notification( $ip, $attempts ) {
		// Get notification email.
		$recipient = $this->plugin->get_option( 'lockout_email_recipient', '' );

		if ( empty( $recipient ) ) {
			$recipient = get_option( 'admin_email' );
		}

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return;
		}

		// Check for custom subject.
		$custom_subject = $this->plugin->get_option( 'lockout_email_subject', '' );
		if ( ! empty( $custom_subject ) ) {
			$subject = $custom_subject;
		} else {
			$subject = sprintf(
				/* translators: %s is the site name */
				__( '[%s] Brute Force Lockout Alert', 'msc-stealth-login' ),
				get_bloginfo( 'name' )
			);
		}

		// Check for custom body with placeholders.
		$custom_body = $this->plugin->get_option( 'lockout_email_body', '' );
		if ( ! empty( $custom_body ) ) {
			// Replace placeholders.
			$message = $custom_body;
			$message = str_replace( '{ip}', $ip, $message );
			$message = str_replace( '{attempts}', absint( $attempts['count'] ), $message );
			$message = str_replace( '{time}', current_time( 'mysql' ), $message );
			$message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
			$message = str_replace( '{site_url}', home_url(), $message );
		} else {
			$message = sprintf(
				/* translators: 1: site name, 2: IP address, 3: number of failed attempts, 4: time */
				__( 'A brute force lockout has occurred on %1$s.

IP Address: %2$s
Failed Attempts: %3$d
Time: %4$s

This IP has been temporarily blocked from making login attempts.

If this is not expected behavior, you may want to investigate this IP address.', 'msc-stealth-login' ),
				get_bloginfo( 'name' ),
				$ip,
				absint( $attempts['count'] ),
				current_time( 'mysql' )
			);
		}

		// Debug logging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// error_log( 'MSC Stealth Login: Sending lockout notification to ' . $recipient . ' for IP ' . $ip );
		}

		wp_mail( $recipient, $subject, $message );
	}

	/**
	 * Redirect locked-out users to homepage.
	 *
	 * Instead of displaying a custom lockout page that hijacks the admin
	 * experience (violating WordPress.org Guideline #11), we silently
	 * redirect to the homepage. The lockout message is still communicated
	 * via WP_Error through the authenticate filter when users attempt
	 * to log in via the custom URL.
	 *
	 * @since 1.0.0
	 * @since 1.0.3 Moved HTML to template, extracted CSS to external file.
	 * @since 1.0.4 Inlined CSS styles directly on elements, removed external CSS dependency.
	 * @since 1.0.5 Changed from template rendering to silent redirect via wp_safe_redirect().
	 */
	private function show_lockout_message() {
		nocache_headers();
		wp_safe_redirect( home_url(), 302 );
		exit;
	}

	/**
	 * Redirect blocked wp-login.php access to homepage.
	 *
	 * Instead of displaying a custom error page that hijacks the admin
	 * experience (violating WordPress.org Guideline #11), we silently
	 * redirect to the configured homepage URL.
	 *
	 * @since 1.0.0
	 * @since 1.0.3 Moved HTML to template, extracted CSS to external file.
	 * @since 1.0.4 Inlined CSS styles directly on elements, removed external CSS dependency.
	 * @since 1.0.5 Changed from template rendering to silent redirect via wp_safe_redirect().
	 */
	private function show_blocked_message() {
		nocache_headers();
		$redirect_url = $this->plugin->get_option( 'wp_admin_redirect', home_url() );
		if ( empty( $redirect_url ) ) {
			$redirect_url = home_url();
		}
		// Validate redirect URL is local to prevent open redirect attacks.
		$redirect_url = wp_validate_redirect( $redirect_url, home_url() );
		wp_safe_redirect( $redirect_url, 302 );
		exit;
	}

	/**
	 * Get client IP address.
	 *
	 * Only trusts proxy headers when the trust_proxy option is explicitly enabled,
	 * preventing IP spoofing attacks on brute force protection.
	 *
	 * @since 1.0.7 Refactored to default to REMOTE_ADDR for security.
	 *
	 * @return string Validated IP address or empty string.
	 */
	private function get_client_ip() {
		$ip      = '';
		$options = $this->plugin->get_options();

		if ( ! empty( $options['trust_proxy'] ) ) {
			// Only trust proxy headers when explicitly enabled by admin.
			$headers = array(
				'HTTP_CF_CONNECTING_IP', // Cloudflare.
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'HTTP_CLIENT_IP',
			);
			foreach ( $headers as $header ) {
				if ( ! empty( $_SERVER[ $header ] ) ) {
					$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
					$ip      = trim( $ip_list[0] );
					break;
				}
			}
		}

		// Always fall back to REMOTE_ADDR.
		if ( empty( $ip ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '';
	}

	/**
	 * Handle recovery email request.
	 */
	public function handle_recovery_email() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'msc-stealth-login' ) );
		}

		check_admin_referer( 'mscsl_send_recovery_email' );

		$email = isset( $_POST['recovery_email'] ) ? sanitize_email( wp_unslash( $_POST['recovery_email'] ) ) : '';

		if ( empty( $email ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'     => 'msc-stealth-login',
						'tab'      => 'support',
						'recovery' => 'error',
						'_wpnonce' => wp_create_nonce( 'mscsl_recovery_notice' ),
					),
					admin_url( 'options-general.php' )
				)
			);
			exit;
		}

		$custom_slug = $this->plugin->get_option( 'custom_login_slug', 'secure-login' );
		$login_url   = trailingslashit( home_url() ) . $custom_slug;

		$subject = sprintf(
			/* translators: %s is the site name */
			__( '[%s] Your Login URL Recovery', 'msc-stealth-login' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			/* translators: 1: site name, 2: login URL */
			__( 'You requested a login URL recovery for %1$s. Your stealth login URL is: %2$s', 'msc-stealth-login' ),
			get_bloginfo( 'name' ),
			$login_url
		);

		$sent = wp_mail( $email, $subject, $message );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'msc-stealth-login',
					'tab'      => 'support',
					'recovery' => $sent ? 'sent' : 'failed',
					'_wpnonce' => wp_create_nonce( 'mscsl_recovery_notice' ),
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Show recovery notice.
	 */
	public function show_recovery_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['recovery'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mscsl_recovery_notice' ) ) {
			return;
		}

		$type = sanitize_key( wp_unslash( $_GET['recovery'] ) );

		if ( 'sent' === $type ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Recovery email sent successfully.', 'msc-stealth-login' ) . '</p></div>';
		} elseif ( 'error' === $type ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to send recovery email. Please check the email address.', 'msc-stealth-login' ) . '</p></div>';
		} elseif ( 'failed' === $type ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to send recovery email.', 'msc-stealth-login' ) . '</p></div>';
		}
	}

	/**
	 * Show data tracking admin notice.
	 *
	 * Informs administrators about data collection by the plugin,
	 * as required by WordPress.org Guideline 7.
	 *
	 * @since 1.0.5
	 */
	public function show_data_tracking_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_msc-stealth-login' !== $screen->id ) {
			return;
		}

		// Show once — dismissible via AJAX.
		if ( get_user_meta( get_current_user_id(), 'mscsl_data_notice_dismissed', true ) ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible mscsl-data-tracking-notice">
			<p>
				<strong><?php esc_html_e( 'MSC Stealth Login:', 'msc-stealth-login' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to plugin settings page */
					esc_html__( 'This plugin collects IP addresses, usernames, user agents, and login attempt history for security features (brute force protection, login alerts). This data is stored in your WordPress database and is not sent externally. You can manage settings on the %s page.', 'msc-stealth-login' ),
					'<a href="' . esc_url( admin_url( 'options-general.php?page=msc-stealth-login' ) ) . '">' . esc_html__( 'plugin settings', 'msc-stealth-login' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Dismiss data tracking notice via AJAX.
	 *
	 * Stores a user meta flag indicating the notice has been dismissed.
	 *
	 * @since 1.0.5
	 */
	public function dismiss_data_tracking_notice() {
		check_ajax_referer( 'mscsl_dismiss_data_notice', '_wpnonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'msc-stealth-login' ) );
		}
		update_user_meta( get_current_user_id(), 'mscsl_data_notice_dismissed', true );
		wp_die();
	}

	/**
	 * Log a successful login.
	 *
	 * @param string  $username Username.
	 * @param WP_User $user     User object.
	 */
	public function log_successful_login( $username, $user ) {
		if ( ! $this->plugin->get_option( 'login_logging_enabled', 1 ) ) {
			return;
		}

		$ip = $this->get_client_ip();

		if ( ! empty( $ip ) ) {
			Database::log_attempt(
				$ip,
				$username,
				'success',
				$this->get_user_agent() // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- authentication hook, not form processing
			);

			// Clear attempts on successful login.
			$this->delete_lockout_transient( self::ATTEMPTS_TRANSIENT . md5( $ip ) );
		}

		// Send login alerts if enabled.
		$this->send_admin_login_alert( $user );
		$this->send_new_ip_alert( $user, $ip );
	}

	/**
	 * Send admin login alert email.
	 *
	 * @param WP_User $user User object.
	 */
	private function send_admin_login_alert( $user ) {
		// Check if email notifications are enabled.
		if ( ! $this->plugin->get_option( 'email_notifications_enabled', 0 ) ) {
			return;
		}

		// Check if admin login alert is enabled.
		if ( ! $this->plugin->get_option( 'login_alert_admin', 0 ) ) {
			return;
		}

		// Get recipient (admin email or custom).
		$recipient = $this->plugin->get_option( 'lockout_email_recipient', '' );
		if ( empty( $recipient ) ) {
			$recipient = get_option( 'admin_email' );
		}

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return;
		}

		$ip = $this->get_client_ip();

		$subject = sprintf(
			/* translators: %s is the site name */
			__( '[%s] User Login Alert', 'msc-stealth-login' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			/* translators: 1: username, 2: IP address, 3: time */
			__( 'User: %1$s
IP Address: %2$s
Time: %3$s

This is an automated alert from your WordPress security plugin.', 'msc-stealth-login' ),
			$user->user_login,
			$ip,
			current_time( 'mysql' )
		);

		// Debug logging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// error_log( 'MSC Stealth Login: Sending admin login alert to ' . $recipient );
		}

		wp_mail( $recipient, $subject, $message );
	}

	/**
	 * Send new IP login alert email.
	 *
	 * @param WP_User $user User object.
	 * @param string  $ip   IP address.
	 */
	private function send_new_ip_alert( $user, $ip ) {
		// Check if email notifications are enabled.
		if ( ! $this->plugin->get_option( 'email_notifications_enabled', 0 ) ) {
			return;
		}

		// Check if new IP login alert is enabled.
		if ( ! $this->plugin->get_option( 'login_alert_new_ip', 0 ) ) {
			return;
		}

		// Get stored IPs from user meta.
		$known_ips = get_user_meta( $user->ID, 'mscsl_known_ips', true );
		if ( ! is_array( $known_ips ) ) {
			$known_ips = array();
		}

		// If IP not in known list, send alert.
		if ( ! in_array( $ip, $known_ips, true ) ) {
			$recipient = $user->user_email;

			$subject = sprintf(
				/* translators: %s is the site name */
				__( '[%s] New Login Location Detected', 'msc-stealth-login' ),
				get_bloginfo( 'name' )
			);

			$message = sprintf(
				/* translators: 1: display name, 2: IP address, 3: time */
				__( 'Hello %1$s,

We detected a login to your account from a new IP address:

IP Address: %2$s
Time: %3$s

If this was not you, please contact your site administrator immediately.', 'msc-stealth-login' ),
				$user->display_name,
				$ip,
				current_time( 'mysql' )
			);

			// Debug logging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// error_log( 'MSC Stealth Login: Sending new IP alert to ' . $recipient . ' for IP ' . $ip );
			}

			wp_mail( $recipient, $subject, $message );

			// Add IP to known list.
			$known_ips[] = $ip;
			update_user_meta( $user->ID, 'mscsl_known_ips', $known_ips );
		}
	}

	/**
	 * Flush rewrite rules if scheduled by plugin activation or settings save.
	 *
	 * This runs at priority 99 on init, AFTER register_rewrite_rules() at
	 * priority 1, ensuring our custom rewrite rules are registered before
	 * the flush occurs. This is necessary because:
	 *
	 * 1. During activation, init hooks haven't fired yet.
	 * 2. During settings save, the rewrite rules in memory may use the old slug.
	 *
	 * Using a transient flag ensures the flush happens at the right time
	 * without requiring manual permalink refresh.
	 */
	public function maybe_flush_rewrite_rules() {
		if ( get_transient( 'mscsl_flush_rewrite_rules' ) ) {
			delete_transient( 'mscsl_flush_rewrite_rules' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Whether module is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return (bool) $this->plugin->get_option( 'module_enabled', 1 );
	}

	/**
	 * Read a lockout transient.
	 *
	 * On a multisite network with shared lockouts enabled the value lives in a
	 * site (network) transient, so one attacker gets a single allowance for the
	 * whole network instead of one per site.
	 *
	 * @since 1.3.0
	 *
	 * @param string $key Transient key.
	 * @return mixed Transient value, or false when not set.
	 */
	private function get_lockout_transient( $key ) {
		if ( $this->plugin->uses_network_lockout() ) {
			return get_site_transient( $key );
		}

		return get_transient( $key );
	}

	/**
	 * Write a lockout transient.
	 *
	 * @since 1.3.0
	 *
	 * @param string $key        Transient key.
	 * @param mixed  $value      Value to store.
	 * @param int    $expiration Expiration in seconds.
	 * @return void
	 */
	private function set_lockout_transient( $key, $value, $expiration ) {
		if ( $this->plugin->uses_network_lockout() ) {
			set_site_transient( $key, $value, $expiration );
			return;
		}

		set_transient( $key, $value, $expiration );
	}

	/**
	 * Delete a lockout transient.
	 *
	 * Clears both storages so switching the network-shared setting on or off
	 * cannot leave a stale counter behind.
	 *
	 * @since 1.3.0
	 *
	 * @param string $key Transient key.
	 * @return void
	 */
	private function delete_lockout_transient( $key ) {
		delete_transient( $key );

		if ( is_multisite() ) {
			delete_site_transient( $key );
		}
	}

	/**
	 * Get sanitized user agent string with length limit.
	 *
	 * @return string
	 */
	private function get_user_agent() {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}
		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		// Limit user agent to 500 characters for database storage.
		if ( strlen( $ua ) > 500 ) {
			$ua = substr( $ua, 0, 500 );
		}
		return $ua;
	}
}
