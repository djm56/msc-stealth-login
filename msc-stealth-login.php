<?php
/**
 * Plugin Name: MSC Stealth Login
 * Plugin URI: https://github.com/djm56/msc-stealth-login
 * Description: Hide your login page, block brute force attacks, and protect your WordPress site from unauthorized access. Complete free plugin with all features included.
 * Version: 1.2.0
 * Author: Anomalous Developers
 * Author URI: https://anomalous.co.za
 * Text Domain: msc-stealth-login
 * Domain Path: /languages
 * Requires at least: 5.9
 * Tested up to: 7.0
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
define( 'MSCSL_PLUGIN_VERSION', '1.2.0' );

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
