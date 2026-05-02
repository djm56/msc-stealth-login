<?php
/**
 * Admin settings class for MSC Stealth Login.
 *
 * @package MSCSL
 */

namespace MSCSL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * Main plugin instance.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Current tab.
	 *
	 * @var string
	 */
	private $current_tab = 'settings';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_mscsl_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_init', array( $this, 'handle_tab_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_msc-stealth-login' !== $hook_suffix ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'msc-stealth-login-admin',
			MSCSL_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MSCSL_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'msc-stealth-login-admin',
			MSCSL_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MSCSL_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'msc-stealth-login-admin',
			'mscslAdmin',
			array(
				'reservedSlug' => esc_html__( 'This slug is reserved. Please choose a different one.', 'msc-stealth-login' ),
			)
		);
	}

	/**
	 * Register admin page.
	 */
	public function register_menu() {
		add_options_page(
			'MSC Stealth Login',
			'MSC Stealth Login',
			'manage_options',
			'msc-stealth-login',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle tab redirect on initial load.
	 */
	public function handle_tab_redirect() {
		// Handle CSV export.
		$this->handle_csv_export();

		if ( isset( $_GET['page'] ) && 'msc-stealth-login' === $_GET['page'] && isset( $_GET['tab'] ) ) {
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mscsl_tab_switch' ) ) {
				$this->current_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			}
		}
	}

	/**
	 * Handle CSV export.
	 */
	public function handle_csv_export() {
		if ( ! isset( $_GET['action'] ) || 'export_csv' !== $_GET['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'msc-stealth-login' ) );
		}

		check_admin_referer( 'mscsl_export_csv' );

		$filters = array();

		// Apply same filters as history view.
		if ( ! empty( $_GET['filter_ip'] ) ) {
			$filters['ip'] = sanitize_text_field( wp_unslash( $_GET['filter_ip'] ) );
		}
		if ( ! empty( $_GET['filter_type'] ) ) {
			$filters['type'] = sanitize_text_field( wp_unslash( $_GET['filter_type'] ) );
		}
		if ( ! empty( $_GET['filter_username'] ) ) {
			$filters['username'] = sanitize_text_field( wp_unslash( $_GET['filter_username'] ) );
		}
		if ( ! empty( $_GET['filter_date_from'] ) ) {
			$filters['date_from'] = sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) );
		}
		if ( ! empty( $_GET['filter_date_to'] ) ) {
			$filters['date_to'] = sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) );
		}

		// No limit for export.
		$filters['limit'] = 10000;

		$logs = Database::get_attempts( $filters );

		// Generate CSV.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=msc-stealth-login-logs-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );

		// CSV Header.
		fputcsv( $output, array(
			__( 'IP Address', 'msc-stealth-login' ),
			__( 'Username', 'msc-stealth-login' ),
			__( 'Result', 'msc-stealth-login' ),
			__( 'User Agent', 'msc-stealth-login' ),
			__( 'Date/Time', 'msc-stealth-login' ),
		) );

		// CSV Data.
		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log['ip_address'],
				$log['user_login'],
				$log['attempt_type'],
				$log['user_agent'] ?? '',
				$log['created_at'],
			) );
		}

		// fclose() is used for php://output stream in CSV export — not a filesystem write operation.
		// WP_Filesystem does not apply to stream resources for output.
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Handle settings save.
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'msc-stealth-login' ) );
		}

		check_admin_referer( 'mscsl_save_settings' );

		$tab = isset( $_POST['current_tab'] ) ? sanitize_key( wp_unslash( $_POST['current_tab'] ) ) : 'settings';
		$options = array();

		// Build options array based on current tab to avoid resetting other tabs.
		if ( 'settings' === $tab ) {
			// Settings tab - only update settings fields.
			$options['module_enabled']     = isset( $_POST['module_enabled'] ) ? 1 : 0;
			$options['custom_login_slug']   = sanitize_text_field( wp_unslash( $_POST['custom_login_slug'] ?? '' ) );
			$options['hide_wp_admin']       = isset( $_POST['hide_wp_admin'] ) ? 1 : 0;
			$options['wp_admin_redirect']   = esc_url_raw( wp_unslash( $_POST['wp_admin_redirect'] ?? '' ) );
			$options['logout_redirect_url'] = esc_url_raw( wp_unslash( $_POST['logout_redirect_url'] ?? '' ) );

			// Validate login slug.
			$slug = $options['custom_login_slug'];
			$slug = trim( $slug );
			$slug = ltrim( $slug, '/' );

			if ( empty( $slug ) ) {
				$slug = 'secure-login';
			}

			// Prevent conflicting with existing WordPress routes.
			$reserved = array( 'wp-admin', 'wp-login', 'wp-login.php', 'login', 'admin' );
			if ( in_array( $slug, $reserved, true ) || preg_match( '/^wp-/i', $slug ) ) {
				$slug = 'secure-login';
			}

			$options['custom_login_slug'] = $slug;
		} elseif ( 'advanced' === $tab ) {
			// Advanced tab - only update advanced security fields.
			$options['advanced_security_enabled'] = isset( $_POST['advanced_security_enabled'] ) ? 1 : 0;
			$options['disable_xmlrpc']             = isset( $_POST['disable_xmlrpc'] ) ? 1 : 0;
			$options['disable_rest_api']           = isset( $_POST['disable_rest_api'] ) ? 1 : 0;
			$options['brute_force_enabled']        = isset( $_POST['brute_force_enabled'] ) ? 1 : 0;
			$options['max_login_attempts']         = absint( $_POST['max_login_attempts'] ?? 3 );
			$options['lockout_duration']           = absint( $_POST['lockout_duration'] ?? 15 );
			$options['login_logging_enabled']      = isset( $_POST['login_logging_enabled'] ) ? 1 : 0;
			$options['ip_whitelist']               = sanitize_textarea_field( wp_unslash( $_POST['ip_whitelist'] ?? '' ) );
			$options['progressive_lockout_enabled'] = isset( $_POST['progressive_lockout_enabled'] ) ? 1 : 0;
			$options['max_lockout_duration']       = absint( $_POST['max_lockout_duration'] ?? 60 );

			// Validate numeric options.
			$options['max_login_attempts']  = max( 1, min( 10, $options['max_login_attempts'] ) );
			$options['lockout_duration']  = max( 5, min( 60, $options['lockout_duration'] ) );
			$options['max_lockout_duration'] = max( 60, min( 1440, $options['max_lockout_duration'] ) );
		} elseif ( 'email' === $tab ) {
			// Email tab - only update email notification fields.
			$options['email_notifications_enabled'] = isset( $_POST['email_notifications_enabled'] ) ? 1 : 0;
			$options['lockout_email_enabled']        = isset( $_POST['lockout_email_enabled'] ) ? 1 : 0;
			$options['lockout_email_recipient']       = sanitize_email( wp_unslash( $_POST['lockout_email_recipient'] ?? '' ) );
			$options['lockout_email_subject']         = sanitize_text_field( wp_unslash( $_POST['lockout_email_subject'] ?? '' ) );
			$options['lockout_email_body']           = sanitize_textarea_field( wp_unslash( $_POST['lockout_email_body'] ?? '' ) );
			$options['login_alert_admin']             = isset( $_POST['login_alert_admin'] ) ? 1 : 0;
			$options['login_alert_new_ip']            = isset( $_POST['login_alert_new_ip'] ) ? 1 : 0;

			// Validate email recipient.
			if ( ! empty( $_POST['lockout_email_recipient'] ) ) {
				$options['lockout_email_recipient'] = sanitize_email( wp_unslash( $_POST['lockout_email_recipient'] ) );
				if ( ! is_email( $options['lockout_email_recipient'] ) ) {
					$options['lockout_email_recipient'] = get_option( 'admin_email' );
				}
			} else {
				$options['lockout_email_recipient'] = '';
			}
		}

		// Only update if we have options to save for this tab.
		if ( ! empty( $options ) ) {
			$this->plugin->update_options( $options );
		}

		// Schedule rewrite rules flush if settings tab was saved.
		// Using a transient ensures the flush happens on the next page load
		// when our rewrite rules (registered on init at priority 1) are
		// available with the updated slug value. A direct flush_rewrite_rules()
		// call here would use the old slug because init hooks already ran
		// with the previous option value.
		if ( 'settings' === $tab ) {
			set_transient( 'mscsl_flush_rewrite_rules', true, 60 );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'msc-stealth-login',
					'tab'     => $tab,
					'updated' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->current_tab = 'settings';
		if ( isset( $_GET['page'] ) && 'msc-stealth-login' === $_GET['page'] && isset( $_GET['tab'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mscsl_tab_switch' ) ) {
			$this->current_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
		}

		$options = $this->plugin->get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MSC Stealth Login', 'msc-stealth-login' ); ?></h1>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce already verified in handle_save() which sets this param via redirect. ?>
			<?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings updated successfully.', 'msc-stealth-login' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'settings', '_wpnonce' => wp_create_nonce( 'mscsl_tab_switch' ) ), admin_url( 'options-general.php?page=msc-stealth-login' ) ) ); ?>" class="nav-tab <?php echo 'settings' === $this->current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'msc-stealth-login' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'advanced', '_wpnonce' => wp_create_nonce( 'mscsl_tab_switch' ) ), admin_url( 'options-general.php?page=msc-stealth-login' ) ) ); ?>" class="nav-tab <?php echo 'advanced' === $this->current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Advanced', 'msc-stealth-login' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'email', '_wpnonce' => wp_create_nonce( 'mscsl_tab_switch' ) ), admin_url( 'options-general.php?page=msc-stealth-login' ) ) ); ?>" class="nav-tab <?php echo 'email' === $this->current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Email', 'msc-stealth-login' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'history', '_wpnonce' => wp_create_nonce( 'mscsl_tab_switch' ) ), admin_url( 'options-general.php?page=msc-stealth-login' ) ) ); ?>" class="nav-tab <?php echo 'history' === $this->current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'History', 'msc-stealth-login' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'support', '_wpnonce' => wp_create_nonce( 'mscsl_tab_switch' ) ), admin_url( 'options-general.php?page=msc-stealth-login' ) ) ); ?>" class="nav-tab <?php echo 'support' === $this->current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Support', 'msc-stealth-login' ); ?>
				</a>
				<?php do_action( 'mscsl_tabs', $this->current_tab ); ?>
			</nav>

			<?php if ( 'settings' === $this->current_tab ) : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mscsl_save_settings" />
					<input type="hidden" name="current_tab" value="settings" />
					<?php wp_nonce_field( 'mscsl_save_settings' ); ?>

					<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable Stealth Login', 'msc-stealth-login' ); ?></th>
								<td>
									<label for="module_enabled">
										<input id="module_enabled" type="checkbox" name="module_enabled" value="1" <?php checked( 1, $options['module_enabled'] ); ?> />
										<?php esc_html_e( 'Enable stealth login protection', 'msc-stealth-login' ); ?>
									</label>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="custom_login_slug"><?php esc_html_e( 'Custom Login URL', 'msc-stealth-login' ); ?></label>
								</th>
								<td>
									<input type="text" id="custom_login_slug" name="custom_login_slug" value="<?php echo esc_attr( $options['custom_login_slug'] ); ?>" class="regular-text code" />
									<p class="description">
										<?php
										printf(
											/* translators: %s is the home URL */
											esc_html__( 'Your login page will be at: %s', 'msc-stealth-login' ),
											'<code>' . esc_url( trailingslashit( home_url() ) . $options['custom_login_slug'] ) . '</code>'
										);
										?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php esc_html_e( 'Hide wp-admin', 'msc-stealth-login' ); ?></th>
								<td>
									<label for="hide_wp_admin">
										<input id="hide_wp_admin" type="checkbox" name="hide_wp_admin" value="1" <?php checked( 1, $options['hide_wp_admin'] ); ?> />
										<?php esc_html_e( 'Block direct access to wp-admin directory', 'msc-stealth-login' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Users will be redirected to your custom login URL or the specified URL when trying to access wp-admin directly.', 'msc-stealth-login' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="wp_admin_redirect"><?php esc_html_e( 'wp-admin Redirect URL', 'msc-stealth-login' ); ?></label>
								</th>
								<td>
									<input type="url" id="wp_admin_redirect" name="wp_admin_redirect" value="<?php echo esc_url( $options['wp_admin_redirect'] ); ?>" class="regular-text" />
									<p class="description">
										<?php esc_html_e( 'Where to redirect users when they try to access wp-admin directly. Defaults to homepage.', 'msc-stealth-login' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="logout_redirect_url"><?php esc_html_e( 'Logout Redirect URL', 'msc-stealth-login' ); ?></label>
								</th>
								<td>
									<input type="url" id="logout_redirect_url" name="logout_redirect_url" value="<?php echo esc_url( $options['logout_redirect_url'] ); ?>" class="regular-text" />
									<p class="description">
										<?php esc_html_e( 'Where to redirect users after logging out. Defaults to homepage.', 'msc-stealth-login' ); ?>
									</p>
								</td>
						</tr>

						<!-- Login URL Preview -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Login URL Preview', 'msc-stealth-login' ); ?></th>
							<td>
								<code id="mscsl-url-preview" style="padding: 10px; background: #f0f0f1; display: block; font-size: 14px;"></code>
								<p class="description"><?php esc_html_e( 'This is what your custom login URL will look like.', 'msc-stealth-login' ); ?></p>
							</td>
						</tr>

						<!-- Emergency Recovery URL -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Emergency Recovery URL', 'msc-stealth-login' ); ?></th>
							<td>
								<?php
								$recovery_token = get_option( 'msc_recovery_token', '' );
								$recovery_url   = home_url( '/wp-login.php?msc_recovery=' . $recovery_token );
								?>
								<p><strong><?php esc_html_e( 'Current Recovery URL:', 'msc-stealth-login' ); ?></strong></p>
								<code id="mscsl-recovery-url" style="padding: 10px; background: #f0f0f1; display: block; font-size: 14px; margin-bottom: 10px;"><?php echo esc_url( $recovery_url ); ?></code>
								<p>
									<button type="button" class="button" id="mscsl-copy-recovery-url">
										<?php esc_html_e( 'Copy URL', 'msc-stealth-login' ); ?>
									</button>
									<span id="mscsl-copy-feedback" data-copied="<?php esc_attr_e( 'Copied!', 'msc-stealth-login' ); ?>" style="margin-left: 10px; color: #2271b1; display: none;"></span>
								</p>
								<p class="description">
									<?php esc_html_e( 'Bookmark this URL! If you lose access to your custom login URL, use this recovery URL to access wp-login.php and then navigate to settings to find your custom URL.', 'msc-stealth-login' ); ?>
								</p>
								<p style="margin-top: 15px;">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mscsl_regenerate_recovery_token' ), 'mscsl_regenerate_recovery_token' ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure? This will invalidate the current recovery URL.', 'msc-stealth-login' ); ?>');">
										<?php esc_html_e( 'Regenerate Recovery URL', 'msc-stealth-login' ); ?>
									</a>
								</p>
								<?php if ( isset( $_GET['recovery'] ) && 'regenerated' === sanitize_key( wp_unslash( $_GET['recovery'] ) ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mscsl_recovery_notice' ) ) : ?>
									<div class="notice notice-success is-dismissible" style="margin-top: 10px;">
										<p><?php esc_html_e( 'Recovery URL regenerated successfully!', 'msc-stealth-login' ); ?></p>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					</table>

					<?php do_action( 'mscsl_settings_sections' ); ?>

					<?php submit_button( __( 'Save Settings', 'msc-stealth-login' ) ); ?>

					</form>

			<?php elseif ( 'advanced' === $this->current_tab ) : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mscsl_save_settings" />
					<input type="hidden" name="current_tab" value="advanced" />
					<?php wp_nonce_field( 'mscsl_save_settings' ); ?>

					<p><?php esc_html_e( 'Advanced security features provide additional protection against brute force attacks, XML-RPC exploitation, and user enumeration via the REST API. These features are optional and may affect compatibility with some plugins or services that require XML-RPC or REST API access.', 'msc-stealth-login' ); ?></p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Advanced Security Features', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="advanced_security_enabled">
									<input id="advanced_security_enabled" type="checkbox" name="advanced_security_enabled" value="1" <?php checked( 1, $options['advanced_security_enabled'] ); ?> />
									<?php esc_html_e( 'Enable additional security features including brute force protection, XML-RPC blocking, and REST API protection.', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'When disabled, only the basic stealth login features will be active. Enable this to protect against automated attacks.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'XML-RPC & REST API Protection', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-advanced-option" style="<?php echo $options['advanced_security_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Disable XML-RPC', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="disable_xmlrpc">
									<input id="disable_xmlrpc" type="checkbox" name="disable_xmlrpc" value="1" <?php checked( 1, $options['disable_xmlrpc'] ); ?> />
									<?php esc_html_e( 'Block XML-RPC requests', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents XML-RPC attacks and pingbacks. This also blocks the XML-RPC authentication method. Only available when Advanced Security is enabled.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>

						<tr class="mscsl-advanced-option" style="<?php echo $options['advanced_security_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Disable REST API User Enumeration', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="disable_rest_api">
									<input id="disable_rest_api" type="checkbox" name="disable_rest_api" value="1" <?php checked( 1, $options['disable_rest_api'] ); ?> />
									<?php esc_html_e( 'Block REST API user enumeration', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevents attackers from discovering usernames via the REST API by querying ?author=1. Only available when Advanced Security is enabled.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'Brute Force Protection', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-advanced-option" style="<?php echo $options['advanced_security_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Enable Brute Force Protection', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="brute_force_enabled">
									<input id="brute_force_enabled" type="checkbox" name="brute_force_enabled" value="1" <?php checked( 1, $options['brute_force_enabled'] ); ?> />
									<?php esc_html_e( 'Enable login attempt limiting', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Limits login attempts to prevent brute force attacks. Only available when Advanced Security is enabled.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>

						<tr class="mscsl-advanced-option mscsl-bruteforce-option" style="<?php echo ( $options['advanced_security_enabled'] && $options['brute_force_enabled'] ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="max_login_attempts"><?php esc_html_e( 'Max Login Attempts', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<input type="number" id="max_login_attempts" name="max_login_attempts" value="<?php echo esc_attr( $options['max_login_attempts'] ); ?>" min="1" max="10" class="small-text" />
								<p class="description">
									<?php esc_html_e( 'Number of failed attempts before a temporary lockout (1-10).', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>

						<tr class="mscsl-advanced-option mscsl-bruteforce-option" style="<?php echo ( $options['advanced_security_enabled'] && $options['brute_force_enabled'] ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="lockout_duration"><?php esc_html_e( 'Lockout Duration', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<input type="number" id="lockout_duration" name="lockout_duration" value="<?php echo esc_attr( $options['lockout_duration'] ); ?>" min="5" max="60" class="small-text" />
								<?php esc_html_e( 'minutes', 'msc-stealth-login' ); ?>
								<p class="description">
									<?php esc_html_e( 'How long the lockout lasts in minutes (5-60).', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'Login Logging', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-advanced-option" style="<?php echo $options['advanced_security_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Enable Login Logging', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="login_logging_enabled">
									<input id="login_logging_enabled" type="checkbox" name="login_logging_enabled" value="1" <?php checked( 1, $options['login_logging_enabled'] ); ?> />
									<?php esc_html_e( 'Log all login attempts to database', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'When enabled, all login attempts (success, failure, lockout) will be recorded in the database for review in the History tab.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'IP Whitelist', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-advanced-option" style="<?php echo $options['advanced_security_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="ip_whitelist"><?php esc_html_e( 'Whitelisted IP Addresses', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<textarea id="ip_whitelist" name="ip_whitelist" rows="5" class="large-text code"><?php echo esc_textarea( $options['ip_whitelist'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Enter IP addresses that should bypass brute force protection. Enter one IP per line or separate with commas. Examples:', 'msc-stealth-login' ); ?>
									<br><code>192.168.1.1</code><br><code>10.0.0.0/8</code>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'Progressive Lockout', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-advanced-option" style="<?php echo $options['advanced_security_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Progressive Lockout Delays', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="progressive_lockout_enabled">
									<input id="progressive_lockout_enabled" type="checkbox" name="progressive_lockout_enabled" value="1" <?php checked( 1, $options['progressive_lockout_enabled'] ); ?> />
									<?php esc_html_e( 'Enable progressive lockout delays', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Each subsequent lockout doubles the wait time. Helps stop persistent attackers. Resets after 24 hours of no attempts.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>

						<tr class="mscsl-advanced-option mscsl-progressive-option" style="<?php echo ( $options['advanced_security_enabled'] && $options['progressive_lockout_enabled'] ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="max_lockout_duration"><?php esc_html_e( 'Maximum Lockout Duration', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<input type="number" id="max_lockout_duration" name="max_lockout_duration" value="<?php echo esc_attr( $options['max_lockout_duration'] ); ?>" min="60" max="1440" class="small-text" />
								<?php esc_html_e( 'minutes', 'msc-stealth-login' ); ?>
								<p class="description">
									<?php esc_html_e( 'Maximum lockout duration even with progressive delays (60-1440 minutes).', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php do_action( 'mscsl_settings_sections' ); ?>

					<?php submit_button( __( 'Save Settings', 'msc-stealth-login' ) ); ?>

				</form>

			<?php elseif ( 'email' === $this->current_tab ) : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mscsl_save_settings" />
					<input type="hidden" name="current_tab" value="email" />
					<?php wp_nonce_field( 'mscsl_save_settings' ); ?>

					<p><?php esc_html_e( 'Configure email notifications for login lockouts and alerts. These settings are independent of the Advanced Security features and can be enabled separately.', 'msc-stealth-login' ); ?></p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Email Notifications', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="email_notifications_enabled">
									<input id="email_notifications_enabled" type="checkbox" name="email_notifications_enabled" value="1" <?php checked( 1, $options['email_notifications_enabled'] ); ?> />
									<?php esc_html_e( 'Enable email notification features', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Master toggle for all email notifications. When enabled, you can configure individual notification types below.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'Lockout Notifications', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-email-option" style="<?php echo $options['email_notifications_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Lockout Email Notification', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="lockout_email_enabled">
									<input id="lockout_email_enabled" type="checkbox" name="lockout_email_enabled" value="1" <?php checked( 1, $options['lockout_email_enabled'] ); ?> />
									<?php esc_html_e( 'Send email when a lockout occurs', 'msc-stealth-login' ); ?>
								</label>
							</td>
						</tr>

						<tr class="mscsl-email-option mscsl-email-detail" style="<?php echo ( $options['email_notifications_enabled'] && $options['lockout_email_enabled'] ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="lockout_email_recipient"><?php esc_html_e( 'Notification Email', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<input type="email" id="lockout_email_recipient" name="lockout_email_recipient" value="<?php echo esc_attr( $options['lockout_email_recipient'] ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Email address to receive lockout notifications. Defaults to site admin email.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>

						<tr class="mscsl-email-option mscsl-email-detail" style="<?php echo ( $options['email_notifications_enabled'] && $options['lockout_email_enabled'] ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="lockout_email_subject"><?php esc_html_e( 'Email Subject', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<input type="text" id="lockout_email_subject" name="lockout_email_subject" value="<?php echo esc_attr( $options['lockout_email_subject'] ); ?>" class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Custom email subject. Leave blank for default.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>

						<tr class="mscsl-email-option mscsl-email-detail" style="<?php echo ( $options['email_notifications_enabled'] && $options['lockout_email_enabled'] ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="lockout_email_body"><?php esc_html_e( 'Email Body', 'msc-stealth-login' ); ?></label>
							</th>
							<td>
								<textarea id="lockout_email_body" name="lockout_email_body" rows="5" class="large-text"><?php echo esc_textarea( $options['lockout_email_body'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Custom email body. Leave blank for default. Available placeholders:', 'msc-stealth-login' ); ?><br>
									<code>{ip}</code> - <?php esc_html_e( 'IP Address', 'msc-stealth-login' ); ?><br>
									<code>{attempts}</code> - <?php esc_html_e( 'Number of failed attempts', 'msc-stealth-login' ); ?><br>
									<code>{time}</code> - <?php esc_html_e( 'Current time', 'msc-stealth-login' ); ?><br>
									<code>{site_name}</code> - <?php esc_html_e( 'Site name', 'msc-stealth-login' ); ?><br>
									<code>{site_url}</code> - <?php esc_html_e( 'Site URL', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2 class="title"><?php esc_html_e( 'Login Alerts', 'msc-stealth-login' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr class="mscsl-email-option" style="<?php echo $options['email_notifications_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Admin Login Alert', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="login_alert_admin">
									<input id="login_alert_admin" type="checkbox" name="login_alert_admin" value="1" <?php checked( 1, $options['login_alert_admin'] ); ?> />
									<?php esc_html_e( 'Send email to admin when any user logs in', 'msc-stealth-login' ); ?>
								</label>
							</td>
						</tr>

						<tr class="mscsl-email-option" style="<?php echo $options['email_notifications_enabled'] ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'New IP Login Alert', 'msc-stealth-login' ); ?></th>
							<td>
								<label for="login_alert_new_ip">
									<input id="login_alert_new_ip" type="checkbox" name="login_alert_new_ip" value="1" <?php checked( 1, $options['login_alert_new_ip'] ); ?> />
									<?php esc_html_e( 'Email user when they login from a new IP address', 'msc-stealth-login' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Users will be notified when their account is accessed from an IP address they have not used before.', 'msc-stealth-login' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php do_action( 'mscsl_settings_sections' ); ?>

					<?php submit_button( __( 'Save Settings', 'msc-stealth-login' ) ); ?>

				</form>

			<?php elseif ( 'history' === $this->current_tab ) : ?>

				<?php $this->render_history_tab(); ?>

			<?php elseif ( 'support' === $this->current_tab ) : ?>

				<div style="max-width:800px;margin-top:1.5em;">

					<h2><?php esc_html_e( 'How to Use MSC Stealth Login', 'msc-stealth-login' ); ?></h2>
					<p><?php esc_html_e( 'MSC Stealth Login helps secure your WordPress site by hiding the default login page and adding brute force protection.', 'msc-stealth-login' ); ?></p>

					<h3><?php esc_html_e( 'Setting Up Your Custom Login URL', 'msc-stealth-login' ); ?></h3>
					<ol style="margin-left:20px;">
						<li><?php esc_html_e( 'Go to the Settings tab above.', 'msc-stealth-login' ); ?></li>
						<li><?php esc_html_e( 'Enter a custom login slug (e.g., "my-secret-login" or "admin-access").', 'msc-stealth-login' ); ?></li>
						<li><?php esc_html_e( 'Click Save Settings.', 'msc-stealth-login' ); ?></li>
						<li><?php esc_html_e( 'Your new login URL will be:', 'msc-stealth-login' ); ?> <code><?php echo esc_url( trailingslashit( home_url() ) . esc_html( $options['custom_login_slug'] ) ); ?></code></li>
						<li><?php esc_html_e( 'Bookmark this URL immediately!', 'msc-stealth-login' ); ?> <strong><?php esc_html_e( 'Important:', 'msc-stealth-login' ); ?></strong> <?php esc_html_e( 'You will no longer be able to access wp-login.php directly.', 'msc-stealth-login' ); ?></li>
					</ol>

					<h3><?php esc_html_e( 'Security Features Explained', 'msc-stealth-login' ); ?></h3>

					<h4><?php esc_html_e( 'Custom Login URL', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Changes your login page from /wp-login.php to a custom URL of your choice. This prevents automated bots from finding your login page.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Hide wp-admin', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Blocks direct access to the /wp-admin/ directory and redirects users to your custom login URL. Prevents unauthorized access attempts.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Disable XML-RPC', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Blocks XML-RPC requests which are commonly used for brute force attacks and pingback spam. If you use mobile apps or external services that require XML-RPC, leave this disabled.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Disable REST API User Enumeration', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Prevents attackers from discovering usernames by querying the REST API with ?author=1, ?author=2, etc.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Brute Force Protection', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Limits login attempts to prevent brute force attacks. After the configured number of failed attempts, the IP address is temporarily blocked.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Emergency Recovery URL', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'A special URL that allows you to access wp-login.php even when the plugin is active. This is your safety net if you forget your custom login URL. Bookmark this URL and keep it safe!', 'msc-stealth-login' ); ?></p>

					<h3><?php esc_html_e( 'Frequently Asked Questions', 'msc-stealth-login' ); ?></h3>

					<h4><?php esc_html_e( 'What happens if I forget my custom login URL?', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Use the Emergency Recovery URL shown on the Settings tab. Bookmark this URL when you first set up the plugin. If you lose both URLs, you will need to disable the plugin via FTP or WP-CLI to regain access.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Will this break my site?', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'If configured correctly, no. However, we strongly recommend testing on a staging site first. Always bookmark your custom login URL and the recovery URL before logging out.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Can I still use wp-admin after logging in?', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Yes! Once you are logged in, wp-admin works normally. The "Hide wp-admin" feature only blocks direct access for non-logged-in users.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Will this work with my security plugin?', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Generally yes, but avoid using multiple plugins that modify login URLs or block wp-admin simultaneously as they may conflict.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'Does this work with WooCommerce?', 'msc-stealth-login' ); ?></h4>
					<p><?php esc_html_e( 'Yes, but note that WooCommerce has its own login/my-account pages. You may need to test compatibility with your specific setup.', 'msc-stealth-login' ); ?></p>

					<h4><?php esc_html_e( 'How do I disable the plugin if I get locked out?', 'msc-stealth-login' ); ?></h4>
					<ol style="margin-left:20px;">
						<li><?php esc_html_e( 'Use the Emergency Recovery URL (if you have it bookmarked)', 'msc-stealth-login' ); ?></li>
						<li><?php esc_html_e( 'Via FTP/SFTP: Rename the plugin folder from /wp-content/plugins/msc-stealth-login/ to /wp-content/plugins/msc-stealth-login-disabled/', 'msc-stealth-login' ); ?></li>
						<li><?php esc_html_e( 'Via WP-CLI: Run: wp plugin deactivate msc-stealth-login', 'msc-stealth-login' ); ?></li>
						<li><?php esc_html_e( 'Via Database: Set the mscsl_options option value to have module_enabled = 0', 'msc-stealth-login' ); ?></li>
					</ol>

					<hr style="margin:2em 0;" />

					<h2><?php esc_html_e( 'Need Help?', 'msc-stealth-login' ); ?></h2>
					<p><?php esc_html_e( 'If you have questions, encounter bugs, or need setup assistance, we\'re here to help.', 'msc-stealth-login' ); ?></p>
					<p>
						<a class="button" href="https://anomalous.co.za" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Get Support', 'msc-stealth-login' ); ?>
						</a>
					</p>
				</div>

			<?php else : ?>

				<?php do_action( 'mscsl_tab_content', $this->current_tab ); ?>

			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Render the history tab content.
	 */
	public function render_history_tab() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle clear logs action.
		if ( isset( $_GET['action'] ) && 'clear_logs' === $_GET['action'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'mscsl_clear_logs' ) ) {
			Database::clear_attempts();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'All login logs have been cleared.', 'msc-stealth-login' ) . '</p></div>';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- view-only pagination and filters on manage_options-protected admin page.
		// Pagination.
		$per_page = 20;
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset   = ( $page - 1 ) * $per_page;

		// Build filters.
		$filters = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);

		// IP filter.
		if ( ! empty( $_GET['filter_ip'] ) ) {
			$filters['ip'] = sanitize_text_field( wp_unslash( $_GET['filter_ip'] ) );
		}

		// Type filter.
		if ( ! empty( $_GET['filter_type'] ) ) {
			$filters['type'] = sanitize_text_field( wp_unslash( $_GET['filter_type'] ) );
		}

		// Username filter.
		if ( ! empty( $_GET['filter_username'] ) ) {
			$filters['username'] = sanitize_text_field( wp_unslash( $_GET['filter_username'] ) );
		}

		// Date filters.
		if ( ! empty( $_GET['filter_date_from'] ) ) {
			$date = sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$filters['date_from'] = $date;
			}
		}
		if ( ! empty( $_GET['filter_date_to'] ) ) {
			$date = sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$filters['date_to'] = $date;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$logs       = Database::get_attempts( $filters );
		$total      = Database::get_attempt_count( $filters );
		$total_pages = ceil( $total / $per_page );
		?>
		<div style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Login Attempt History', 'msc-stealth-login' ); ?></h2>

			<!-- Filters -->
			<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
				<input type="hidden" name="page" value="msc-stealth-login">
				<input type="hidden" name="tab" value="history">

				<table class="form-table" style="margin-bottom: 20px;">
					<tr>
						<th scope="row">
							<label for="filter_ip"><?php esc_html_e( 'Filter by IP', 'msc-stealth-login' ); ?></label>
						</th>
						<td>
							<input type="text" id="filter_ip" name="filter_ip" value="<?php echo esc_attr( $filters['ip'] ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'IP Address', 'msc-stealth-login' ); ?>">
						</td>
						<th scope="row">
							<label for="filter_username"><?php esc_html_e( 'Filter by Username', 'msc-stealth-login' ); ?></label>
						</th>
						<td>
							<input type="text" id="filter_username" name="filter_username" value="<?php echo esc_attr( $filters['username'] ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Username', 'msc-stealth-login' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="filter_type"><?php esc_html_e( 'Filter by Result', 'msc-stealth-login' ); ?></label>
						</th>
						<td>
							<select id="filter_type" name="filter_type">
								<option value=""><?php esc_html_e( 'All Results', 'msc-stealth-login' ); ?></option>
								<option value="success" <?php selected( ( $filters['type'] ?? '' ), 'success' ); ?>><?php esc_html_e( 'Success', 'msc-stealth-login' ); ?></option>
								<option value="failure" <?php selected( ( $filters['type'] ?? '' ), 'failure' ); ?>><?php esc_html_e( 'Failed', 'msc-stealth-login' ); ?></option>
								<option value="lockout" <?php selected( ( $filters['type'] ?? '' ), 'lockout' ); ?>><?php esc_html_e( 'Locked Out', 'msc-stealth-login' ); ?></option>
								<option value="whitelisted" <?php selected( ( $filters['type'] ?? '' ), 'whitelisted' ); ?>><?php esc_html_e( 'Whitelisted', 'msc-stealth-login' ); ?></option>
							</select>
						</td>
						<th scope="row">
							<label for="filter_date_from"><?php esc_html_e( 'Date From', 'msc-stealth-login' ); ?></label>
						</th>
						<td>
							<input type="date" id="filter_date_from" name="filter_date_from" value="<?php echo esc_attr( $filters['date_from'] ?? '' ); ?>">
						</td>
						<th scope="row">
							<label for="filter_date_to"><?php esc_html_e( 'Date To', 'msc-stealth-login' ); ?></label>
						</th>
						<td>
							<input type="date" id="filter_date_to" name="filter_date_to" value="<?php echo esc_attr( $filters['date_to'] ?? '' ); ?>">
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'msc-stealth-login' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=msc-stealth-login&tab=history' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Clear Filters', 'msc-stealth-login' ); ?></a>
				</p>
			</form>

			<?php if ( empty( $logs ) ) : ?>
				<p><?php esc_html_e( 'No login attempts recorded yet.', 'msc-stealth-login' ); ?></p>
			<?php else : ?>
				<p><?php
				/* translators: %1$d: start number, %2$d: end number, %3$d: total count */
				printf( esc_html__( 'Showing %1$d to %2$d of %3$d entries', 'msc-stealth-login' ), intval( $offset + 1 ), intval( min( $offset + count( $logs ), $total ) ), intval( $total ) );
				?></p>

				<table class="widefat" style="margin-top: 16px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'IP Address', 'msc-stealth-login' ); ?></th>
							<th><?php esc_html_e( 'Username', 'msc-stealth-login' ); ?></th>
							<th><?php esc_html_e( 'Result', 'msc-stealth-login' ); ?></th>
							<th><?php esc_html_e( 'Date/Time', 'msc-stealth-login' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><code><?php echo esc_html( $log['ip_address'] ); ?></code></td>
								<td><?php echo esc_html( $log['user_login'] ); ?></td>
								<td>
									<?php
									switch ( $log['attempt_type'] ) {
										case 'success':
											echo '<span style="color: #00a32a;">' . esc_html__( 'Success', 'msc-stealth-login' ) . '</span>';
											break;
										case 'failure':
											echo '<span style="color: #d63638;">' . esc_html__( 'Failed', 'msc-stealth-login' ) . '</span>';
											break;
										case 'lockout':
											echo '<span style="color: #d63638; font-weight: bold;">' . esc_html__( 'Locked Out', 'msc-stealth-login' ) . '</span>';
											break;
										case 'whitelisted':
											echo '<span style="color: #2271b1;">' . esc_html__( 'Whitelisted', 'msc-stealth-login' ) . '</span>';
											break;
										default:
											echo esc_html( $log['attempt_type'] );
									}
									?>
								</td>
								<td><?php echo esc_html( $log['created_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav" style="margin-top: 20px;">
						<div class="tablenav-pages">
							<?php
							$page_links = paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => __( '&laquo; Previous', 'msc-stealth-login' ),
									'next_text' => __( 'Next &raquo;', 'msc-stealth-login' ),
									'total'     => $total_pages,
									'current'   => $page,
									'add_args'  => array(
										'page' => 'msc-stealth-login',
										'tab'  => 'history',
									),
								)
							);
							if ( $page_links ) {
								/* translators: %d: total number of items */
								echo '<span class="displaying-num">' . sprintf( esc_html__( '%d items', 'msc-stealth-login' ), intval( $total ) ) . '</span>';
								echo wp_kses_post( $page_links );
							}
							?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Actions -->
				<p style="margin-top: 20px;">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=msc-stealth-login&tab=history&action=clear_logs' ), 'mscsl_clear_logs' ) ); ?>" class="button" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all login logs?', 'msc-stealth-login' ); ?>');">
						<?php esc_html_e( 'Clear All Logs', 'msc-stealth-login' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'msc-stealth-login', 'tab' => 'history', 'action' => 'export_csv' ), admin_url( 'options-general.php' ) ), 'mscsl_export_csv' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Export to CSV', 'msc-stealth-login' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
