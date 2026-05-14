<?php
/**
 * Access denied error page template.
 *
 * Displays the blocked message when someone tries to
 * access wp-login.php directly.
 *
 * @package MSCSL
 * @since 1.0.3
 * @since 1.0.4 Inlined CSS styles to eliminate external stylesheet dependency.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width" />
	<title><?php esc_html_e( 'Access Denied', 'msc-stealth-login' ); ?></title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; background: #f7f7f7; color: #444; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
	<div style="background: #fff; padding: 40px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); text-align: center; max-width: 400px;">
		<h1 style="margin: 0 0 20px; font-size: 24px;"><?php esc_html_e( 'Access Denied', 'msc-stealth-login' ); ?></h1>
		<p style="margin: 0 0 20px; line-height: 1.6;"><?php esc_html_e( 'The login page you are trying to access has been hidden for security reasons. Please use the correct login URL to access this site.', 'msc-stealth-login' ); ?></p>
	</div>
</body>
</html>