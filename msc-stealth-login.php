<?php
/**
 * Plugin Name: MSC Stealth Login
 * Plugin URI: https://github.com/djm56/msc-stealth-login
 * Description: Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.
 * Version: 1.3.1
 * Author: Anomalous Developers
 * Author URI: https://anomalous.co.za
 * Text Domain: msc-stealth-login
 * Domain Path: /languages
 * Requires at least: 5.9
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 */
define( 'MSCSL_PLUGIN_VERSION', '1.3.1' );

/**
 * Absolute path to the main plugin file.
 */
define( 'MSCSL_PLUGIN_FILE', __FILE__ );

/**
 * Absolute path to the plugin directory.
 */
define( 'MSCSL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory.
 */
define( 'MSCSL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Option key for storing plugin settings.
 */
define( 'MSCSL_OPTION_KEY', 'mscsl_options' );

require_once MSCSL_PLUGIN_DIR . 'includes/class-msc-stealth-login-database.php';
require_once MSCSL_PLUGIN_DIR . 'includes/class-msc-stealth-login-module.php';
require_once MSCSL_PLUGIN_DIR . 'includes/class-msc-stealth-login-settings.php';
require_once MSCSL_PLUGIN_DIR . 'includes/class-msc-stealth-login.php';

register_activation_hook(
	__FILE__,
	array( 'MSCSL\\Plugin', 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( 'MSCSL\\Plugin', 'deactivate' )
);

// Provision newly created sites on a multisite network.
add_action(
	'wp_initialize_site',
	'mscsl_stealth_login_initialize_new_site',
	100
);

add_action(
	'plugins_loaded',
	'mscsl_stealth_login_init'
);

/**
 * Initialize MSC Stealth Login plugin.
 *
 * Bootstraps the plugin singleton instance. Text domain is loaded by
 * WordPress core for WordPress.org-hosted plugins (since WP 4.6).
 *
 * @since 1.0.7
 * @since 1.0.8
 */
function mscsl_stealth_login_init() {
	MSCSL\Plugin::instance();
}

/**
 * Install the plugin's per-site data on a newly created multisite site.
 *
 * Network activation only fires the activation hook once, so new sites
 * created afterwards need their own table, options and recovery token.
 *
 * @since 1.3.0
 *
 * @param \WP_Site $new_site New site object.
 * @return void
 */
function mscsl_stealth_login_initialize_new_site( $new_site ) {
	if ( ! is_multisite() ) {
		return;
	}

	// wp_initialize_site can fire outside the admin (e.g. user registration).
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active_for_network( plugin_basename( MSCSL_PLUGIN_FILE ) ) ) {
		return;
	}

	switch_to_blog( (int) $new_site->blog_id );
	MSCSL\Plugin::install();
	restore_current_blog();
}
