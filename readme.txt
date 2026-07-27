=== MSC Stealth Login ===
Contributors: djm56
Donate link: https://anomalous.co.za/donate
Tags: hide login, custom login url, block wp-admin, disable xml-rpc, brute force
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide wp-login.php behind a custom login URL and stop brute-force attacks — lockouts, IP allowlist, login history, email alerts. No tracking.

== Description ==

**Move your WordPress login page to a secret URL of your choosing and make wp-login.php disappear.**

Bots hammering wp-login.php and wp-admin are silently redirected away, while you log in at your own custom address. Enable Advanced Security and you also get brute-force lockouts with progressive delays, an IP allowlist with CIDR support, XML-RPC hardening, user-enumeration blocking, a full login history with CSV export, and email alerts — all free, with zero external services.

**Stealth Login URL**

Change your login page from `/wp-login.php` to a custom URL like `/secure-login/`. Attackers scanning for standard WordPress login pages are redirected away before they can even attempt a brute force attack.

**wp-admin Protection**

Block direct access to `/wp-admin/` for users who aren't logged in — they're silently redirected to a URL you choose. Logged-in users and AJAX requests are unaffected.

**Brute Force Protection** *(enable Advanced Security to arm)*

After a configurable number of failed login attempts (default 3), the IP is locked out for a configurable duration (default 15 minutes). With progressive lockouts enabled, each successive lockout doubles the wait time, up to your configured maximum. This stops automated attacks while minimizing disruption to real users who mistype their password.

**Email Notifications**

Stay informed about security events with configurable email alerts:

* Lockout notifications when IPs are blocked
* Admin login alerts for every administrator sign-in
* New IP alerts when users log in from previously unseen locations

**Login History & Export**

Track login attempts with detailed logging: IP, username, result and user agent. Filter by IP address, username, result type, or date range. Export reports to CSV for security audits. Entries older than 30 days are pruned automatically.

**XML-RPC & REST API Protection**

Disable vulnerable XML-RPC endpoints commonly exploited for brute force attacks. Block REST API user enumeration that lets attackers harvest usernames.

**IP Allowlist**

Bypass protection for trusted IP addresses — exact IPv4/IPv6 or CIDR ranges (e.g. `10.0.0.0/8`). Add your office, home, or server IPs to ensure uninterrupted access while maintaining maximum security for everyone else. A proxy-header trust toggle supports Cloudflare and reverse-proxy setups.

**Emergency Recovery URL**

Forgot your custom login URL? The Settings tab shows a secure recovery URL that always reaches wp-login.php — copy it, bookmark it, or email it to yourself from the Support tab. You can regenerate it at any time.

**Private by design**

No external services, no CDN assets, no tracking. Login data stays in your database, is clearable from the History tab, auto-pruned after 30 days, and fully removed on uninstall.

== Installation ==

1. **Upload** the plugin files to `/wp-content/plugins/msc-stealth-login/` directory
2. **Activate** the plugin through the 'Plugins' menu in WordPress
3. **Navigate** to Settings → MSC Stealth Login
4. **Configure** your custom login URL (e.g., `/secure-login/`)
5. **Enable** additional security features as needed (brute force protection, email alerts, etc.)
6. **Save** your recovery URL somewhere safe — bookmark it or store it securely

**Important:** After activation, immediately bookmark your new login URL and save your recovery URL in a secure location.

== Privacy ==

MSC Stealth Login collects the following data to provide its security features:

* **IP Addresses**: Logged for every login attempt (successful, failed, and locked out) to enable brute force protection and login history.
* **Usernames**: Logged with each login attempt to help administrators identify targeted accounts.
* **User Agents**: Logged with each login attempt for security auditing.
* **Login History**: All login attempts are stored in the database and can be viewed in the History tab or exported as CSV.

Data collection only occurs when the plugin is active. All collected data is stored in your WordPress database and is not sent to any external services. Administrators can clear login history at any time from the History tab.

This plugin does not use cookies or third-party tracking.

== Frequently Asked Questions ==

= How do I hide my WordPress login page? =

Install and activate the plugin, go to Settings → MSC Stealth Login, and set a custom login slug (e.g. `my-secret-door`). Save — your login page now lives at `yoursite.com/my-secret-door` and wp-login.php no longer works for visitors. Bookmark the new URL and the Emergency Recovery URL immediately.

= What happens when someone visits wp-login.php or wp-admin? =

They are silently redirected (HTTP 302) to a URL you choose — the homepage by default. There's no error page revealing that a protection plugin is running. Logged-in users, logout/password-reset flows, and AJAX requests keep working normally.

= How do I block access to /wp-admin? =

Enable "Hide wp-admin" on the Settings tab. Logged-out visitors who try to open any /wp-admin URL are silently redirected to a URL you choose (your homepage by default), while logged-in users and AJAX requests are unaffected. Combined with the custom login URL, this hides both your login page and your admin area from bots and vulnerability scanners.

= What happens if I forget my custom login URL? =

Use the Emergency Recovery URL shown on the **Settings tab** — copy or bookmark it when you set up the plugin (you can also email yourself the login URL from the Support tab). The recovery URL always reaches wp-login.php. If you lose both, rename the plugin folder via FTP/SFTP or run `wp plugin deactivate msc-stealth-login` — deactivating instantly restores the standard login page.

= How do I recover access if I'm locked out? =

Wait for the lockout period to expire, or use the Emergency Recovery URL. For immediate access, disable the plugin via FTP by renaming the plugin folder. Your IP can also be added to the allowlist if you have database access.

= How does brute-force protection work? =

Enable **Advanced Security Features** on the Advanced tab (it ships disabled so nothing surprises you). Then, after the configured number of failed attempts (default 3) from one IP, that IP is locked out for the configured duration (default 15 minutes). Optional progressive lockouts double the wait after each repeat offence, capped at your configured maximum. Successful logins reset the counter.

= Can I allowlist my own IP so I'm never locked out? =

Yes. Add exact IPs or CIDR ranges (IPv4 and IPv6) to the whitelist on the Advanced tab. Behind Cloudflare or a reverse proxy? Enable "Trust Proxy Headers" so the plugin sees real visitor IPs instead of the proxy's.

= Does it block XML-RPC attacks and user enumeration? =

Yes, both are on by default once Advanced Security is enabled. XML-RPC pingback and user-listing methods are disabled, and the REST API user endpoints plus `?author=N` queries are blocked. Note: disabling XML-RPC affects the WordPress mobile app and some Jetpack features — leave it off if you use those.

= Does this work with caching plugins? =

Yes, but ensure your login pages aren't cached — exclude your custom login URL from caching. The plugin detects six major cache/security plugins (W3 Total Cache, WP Super Cache, WP Rocket, Wordfence, iThemes Security, Sucuri) and shows a heads-up notice when one is active.

= Can I run it alongside Wordfence or other security plugins? =

Generally yes, but avoid overlapping features — if another plugin also limits login attempts or hides the login page, disable that feature in one of the two. Test on staging before production.

= Does it work with WooCommerce login forms? =

WooCommerce's My Account login page is separate and keeps working. The plugin protects wp-login.php and wp-admin; test your specific checkout/membership flows on staging.

= How do the email notifications work? =

Navigate to Settings → MSC Stealth Login → Email tab. Enable the notifications you want and customize the subject and body using placeholders: `{ip}`, `{attempts}`, `{time}`, `{site_name}`, `{site_url}`. Notifications are sent immediately when events occur.

= What data does it store? Is it GDPR-friendly? =

Login attempts (IP, username, result, user agent, timestamp) are stored in your own database only — nothing is sent externally and there are no cookies or third-party requests. History is clearable from the History tab, auto-pruned after 30 days, and the table is removed completely on uninstall.

= Is anything locked or paid? =

No. Every feature is included in the plugin you download — there is nothing to unlock and no separate paid add-on.

== Screenshots ==

1. Stealth login settings — set a custom login URL, block direct access to the wp-admin directory, and configure the redirect and emergency recovery URLs.
2. Advanced security — disable XML-RPC, stop REST API user enumeration, and limit login attempts with brute-force lockouts.
3. Email alerts — get notified on lockouts, admin logins and new-IP logins, with customisable subject and body placeholders.
4. Login history — view and filter every login attempt (IP, username, result, date) and export to CSV.

== Changelog ==

= 1.2.0 =
* Changed: Support now links to the plugin's WordPress.org support forum instead of the old contact button.

= 1.1.0 =
* Added: Automatic login-history pruning — entries older than 30 days are now cleaned up daily (filterable via `mscsl_log_retention_days`).
* Added: One-time, dismissible review request on the settings page (shown 7+ days after activation).
* Added: "Email Me My Login URL" form on the Support tab so you can keep the custom login URL on record.
* Fixed: Documentation incorrectly said the Emergency Recovery URL appears in the admin bar — it is shown on the Settings tab.
* Improved: WordPress.org listing rewritten — clearer title, searchable tags, expanded FAQ (incl. blocking /wp-admin), refreshed screenshot captions, and accurate description of which protections require the Advanced Security toggle.

= 1.0.9 =
* Tested with WordPress 7.0.2. No functional changes.

= 1.0.8 =
* **Fixed**: Updated plugin metadata to WordPress 7.0 compatibility (`Tested up to: 7.0`).
* **Fixed**: Renamed global init callback to prefixed function name for Plugin Check naming compliance.
* **Fixed**: Removed discouraged `load_plugin_textdomain()` call for WordPress.org translation loading compliance.
* **Fixed**: Refactored login history SQL query assembly to avoid interpolated dynamic WHERE fragments and ensure placeholder/replacement parity in `$wpdb->prepare()`.
* **Fixed**: Replaced direct `usermeta` cleanup queries in uninstall with `delete_metadata()` API.
* **Updated**: Release version bumped to `1.0.8`.

= 1.0.7 =
* **Security**: Fixed IP spoofing vulnerability — now defaults to REMOTE_ADDR; proxy headers only trusted when explicitly enabled via new `trust_proxy` option.
* **Security**: Removed broad `redirect_to` exception that allowed bypassing login block.
* **Security**: Added CSV formula injection prevention for data exports.
* **Fixed**: Added `load_plugin_textdomain()` so translation files are loaded correctly.
* **Fixed**: Converting closures to named methods for removability.
* **Fixed**: Added `settings_errors()` output on settings page.
* **Fixed**: Refactored SQL sentinel pattern to dynamic WHERE clauses for index utilisation.
* **Fixed**: URL-safe validation for custom login slug.
* **Fixed**: Synchronized reserved slug list between PHP and JavaScript.
* **Fixed**: Double-escaping in login URL display.
* **Fixed**: `esc_attr_e()` in JS onclick handlers replaced with `esc_js()`.
* **Fixed**: `esc_html__()` in plain text email bodies replaced with `__()`.
* **Fixed**: `esc_html__()` in `wp_localize_script()` replaced with `__()`.
* **Fixed**: `esc_url()` in input value attributes replaced with `esc_attr()`.
* **Fixed**: Timezone-sensitive date calculation using `gmdate()` + `DAY_IN_SECONDS`.
* **Fixed**: Incomplete translator comment for lockout email.
* **Fixed**: Orphan user meta cleanup on uninstall.
* **Fixed**: `delete_transient()` instead of `delete_option()` for transients.

= 1.0.6 =
* Fixed: Removed inline `<script>` from data tracking notice and moved dismiss logic to admin.js with localized nonce (WordPress.org review compliance).
* Fixed: Replaced hardcoded `/wp-login.php` URL paths with `wp_login_url()` + `add_query_arg()` for subdirectory WordPress compatibility.
* Fixed: Added missing translators comment for data tracking notice string (Plugin Check compliance).
* Fixed: Added phpcs:ignore comments for custom table direct database queries (Plugin Check compliance).

= 1.0.5 =
* Fixed: CIDR IP whitelist matching now works correctly for subnet ranges.
* Fixed: Recovery token comparison now uses timing-safe comparison (hash_equals).
* Fixed: Lockout message output now properly escaped.
* Fixed: Recovery token option key renamed from msc_recovery_token to mscsl_recovery_token for namespace consistency, with automatic migration.
* Fixed: Plugin header tab character removed for parser compatibility.
* Added: Privacy admin notice informing administrators about data collection (IP addresses, usernames, user agents, login history).
* Added: Database schema version tracking for future upgrade path.
* Added: Privacy Policy section to plugin documentation.

= 1.0.4 =
* Changed: Inlined CSS styles on error page elements for simpler standalone page rendering.
* Removed: External CSS file for error pages (no longer needed).
* Removed: Frontend style registration hooks (no longer needed).

= 1.0.3 =
* Fixed: Extracted inline CSS to external stylesheet file per WordPress.org review requirements.
* Fixed: Created template files for lockout and blocked error pages.
* Added: X-Frame-Options and X-Content-Type-Options security headers to error pages.

= 1.0.2 =
* Fixed: Plugin Check errors for unescaped database parameters in query methods.
* Fixed: Plugin Check error for fclose() on php://output stream — added phpcs:ignore.
* Fixed: DROP TABLE query now uses direct query instead of prepare() (table names cannot be prepared).
* Fixed: Added phpcs:ignore comments for nonce verification warnings in frontend security filters.
* Fixed: Added cleanup of flush rewrite rules transient in uninstall.

= 1.0.1 =
* Fixed: Custom login URL now works immediately after plugin activation without manual permalink flush.
* Fixed: Custom login URL now works immediately after changing the slug in settings.

= 1.0.0 =
* Initial release
* Custom login URL with rewrite rules
* wp-admin blocking and redirect
* Brute force protection with configurable lockouts
* Email notifications (lockout, admin alert, new IP)
* Login history with filtering and CSV export
* XML-RPC endpoint disable option
* REST API user enumeration blocking
* IP whitelist for bypassing protection
* Progressive lockout delay multiplier
* Recovery URL system for forgotten login URLs

== Upgrade Notice ==

= 1.1.0 =
Adds automatic 30-day login-history pruning and a login-URL email form on the Support tab. Improved listing and docs. Safe update.

= 1.0.9 =
Tested with WordPress 7.0.2. No functional changes — safe update.

= 1.0.4 =
Simplified error page rendering with inline CSS — no external stylesheet dependency.

= 1.0.3 =
Fixes WordPress.org review feedback — inline styles are now properly enqueued.

= 1.0.2 =
Fixes Plugin Check errors for WordPress.org submission readiness.

= 1.0.1 =
Fixes rewrite rules flush issue where custom login URL returned 404 after activation or slug change.

= 1.0.0 =
Initial release of MSC Stealth Login.
