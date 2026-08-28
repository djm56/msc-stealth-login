# Changelog

All notable changes to MSC Stealth Login are documented in this file.

## [1.3.2] - 2026-08-27

### Changed

- Updated translations: added 8 new languages (Russian, Simplified Chinese, Turkish, Polish, Indonesian, Swedish, Ukrainian, Arabic) and refreshed all 20 bundled locales to 100% string coverage.

## [1.3.1] - 2026-08-27

### Changed

- Confirmed compatibility with WordPress 7.1 — "Tested up to" header updated to 7.1. No functional changes.

## [1.3.0] - 2026-08-03

### Added

- Multisite support. Each site on a network now installs its own login-attempts table, options and emergency recovery token, whether the plugin is activated per site or network-wide, and sites created after a network activation are provisioned automatically via `wp_initialize_site`.
- Optional "Share Lockouts Across Network" setting (Advanced tab, multisite only, default off). When enabled, failed-attempt counters and progressive lockout multipliers are stored as network transients, so an attacker gets one allowance for the whole network instead of one per site. Also filterable network-wide via `mscsl_network_shared_lockout`.
- Regression tests for login-history filtering (`tests/database-test.php`) and per-site installation / network lockouts (`tests/multisite-test.php`).

### Fixed

- **Login History filters did nothing.** `Database::get_attempts()` and `Database::get_attempt_count()` passed the entire WHERE clause to `$wpdb->prepare()` as a `%s` value, so MySQL received `WHERE '1=1 AND ip_address = %s'` — a quoted string literal it evaluates as true. Every IP, username, result and date filter was silently ignored in the table, the entry count and the CSV export. Introduced by the 1.0.8 PHPCS refactor.
- **Fatal error on WordPress 5.9–6.2.** The History tab called `wp_cache_get_last_changed()`, which only exists in WordPress 6.3+, while the plugin declares support for 5.9+. It now falls back to reading the cache marker directly.
- **The Filter button dropped you back to the Settings tab.** Tab selection required a nonce that the history filter form, pagination links and "Clear Filters" link never carried. Tab switching is a read-only view change and is no longer nonce-protected; saving, clearing logs, exporting and regenerating the recovery token keep their nonces.
- "Export to CSV" now exports what is on screen — the active filters are carried into the export link — and no longer silently truncates at 1,000 rows when 10,000 were requested.
- Uninstalling on a multisite network now removes options, transients and the login-attempts table for every site, plus any network-wide lockout transients. Previously only the current site was cleaned up.
- `is_plugin_active()` is no longer called before `wp-admin/includes/plugin.php` is guaranteed to be loaded during the conflict-detection check.
- The Settings tab mints a recovery token if one is missing, so it can never display a recovery URL that would be rejected.

## [1.2.0] - 2026-07-25

### Changed

- Support now links to the plugin's WordPress.org support forum instead of the old contact button.

## [1.1.0] - 2026-07-24

### Added

- Automatic login-history pruning: a daily cron (`mscsl_brute_force_cleanup`) now prunes entries older than 30 days via `Database::delete_old_attempts()` — the event was previously cleared on deactivation but never scheduled, so logs grew forever despite documentation claiming auto-pruning. Retention filterable via `mscsl_log_retention_days`.
- "Email Me My Login URL" form on the Support tab, wired to the existing `mscsl_send_recovery_email` handler (which previously had no UI).
- One-time, dismissible in-plugin review request on the settings page (7+ days after activation); new options cleaned up on uninstall.

### Fixed

- Documentation corrections: Emergency Recovery URL is on the Settings tab (not the admin bar); blocked wp-login.php requests are silently 302-redirected (not a 403 page); recovery parameter is `mscsl_recovery` (not `msc_recovery`); Login History, IP allowlist, email alerts and progressive lockout are free features (docs wrongly labelled them "Pro" — no Pro version exists); Max Login Attempts range is 1–10; History tab paginates at 20 per page; progressive lockout cap follows the Maximum Lockout Duration setting.

### Changed

- WordPress.org listing rewritten: searchable title and tags, keyword-rich description, expanded 13-question FAQ, and honest framing that brute-force/XML-RPC/REST protections require the Advanced Security toggle (off by default).

## [1.0.9] - 2026-07-24

### Changed

- Confirmed compatibility with WordPress 7.0.2 — updated "Tested up to" header to 7.0.2. No functional changes.

## [1.0.8] - 2026-05-21

### Fixed
* Updated plugin compatibility metadata to `Tested up to: 7.0` for current WordPress directory requirements.
* Renamed global plugin init callback to a prefixed function for naming convention compliance.
* Removed discouraged `load_plugin_textdomain()` bootstrap call for WordPress.org-hosted translation handling.
* Refactored login history query construction to remove interpolated dynamic WHERE fragments and ensure valid `$wpdb->prepare()` placeholder replacement counts.
* Replaced direct uninstall usermeta cleanup queries with `delete_metadata()` API calls.

### Updated
* Version bump to 1.0.8.

## [1.0.7] - 2026-05-21

### Security
* Fixed IP spoofing vulnerability in `get_client_ip()` — now defaults to REMOTE_ADDR; proxy headers only trusted when `trust_proxy` option is enabled.
* Removed broad `redirect_to` exception that allowed bypassing login block on any request with a `redirect_to` parameter.
* Added CSV formula injection prevention for CSV exports.
* Fixed double-escaping in login URL display (`esc_html()` inside `esc_url()`).
* Fixed `esc_attr_e()` in JavaScript onclick handlers — replaced with `esc_js()` to prevent broken quote encoding.
* Fixed `esc_html__()` in plain text email bodies — replaced with `__()` to prevent HTML entities in plain text emails.
* Fixed `esc_html__()` in `wp_localize_script()` data — replaced with `__()` to prevent double-encoding.

### Fixed
* Added `load_plugin_textdomain()` call so translation files are actually loaded.
* Converted unnamed closures to named methods/functions for removability.
* Added `settings_errors()` output to settings page.
* Refactored SQL sentinel pattern `( %s = '' OR column = %s )` to dynamic WHERE clauses for index utilisation and PluginCheck compliance.
* Added URL-safe validation for custom login slug (strips non-URL-safe characters).
* Synchronized reserved slug list between PHP and JavaScript.
* Fixed `esc_url()` used in input value attributes — replaced with `esc_attr()`.
* Fixed timezone-sensitive date calculation in `delete_old_attempts()` — replaced `strtotime()` with `gmdate()` + `DAY_IN_SECONDS`.
* Fixed incomplete translator comment in lockout email (now documents all 4 placeholders).
* Changed `delete_option()` to `delete_transient()` for `mscsl_flush_rewrite_rules`.
* Added orphan user meta cleanup (`mscsl_data_notice_dismissed`, `mscsl_known_ips`) to uninstall.
* Added `trust_proxy` admin setting for proxy header trust control.
* Added `.distignore` to exclude vendor/, tests/, and dev files from WordPress.org builds.

## [1.0.6] - 2026-05-20

### Fixed
* Removed inline `<script>` from data tracking notice and moved dismiss logic to admin.js with localized nonce (WordPress.org review compliance).
* Fixed hardcoded `/wp-login.php` paths to use `wp_login_url()` with `add_query_arg()` for subdirectory WordPress compatibility.
* Added missing translators comment for data tracking notice string (Plugin Check compliance).
* Added phpcs:ignore comments for custom table direct database queries (Plugin Check compliance).

### Updated
* Version bump to 1.0.6.

## [1.0.5] - 2026-05-19

### Fixed
* CIDR IP whitelist matching now works correctly for subnet ranges.
* Recovery token comparison now uses timing-safe comparison (hash_equals).
* Lockout message output now properly escaped.
* Recovery token option key renamed from msc_recovery_token to mscsl_recovery_token for namespace consistency, with automatic migration.
* Plugin header tab character removed for parser compatibility.

### Added
* Privacy admin notice informing administrators about data collection (IP addresses, usernames, user agents, login history).
* Database schema version tracking for future upgrade path.
* Privacy Policy section to plugin documentation.

## [1.0.4] - 2026-05-13

### Changed
- Inlined CSS styles directly on HTML elements for error pages, eliminating external stylesheet dependency.
- Removed `register_frontend_styles()` method and `wp_enqueue_scripts`/`login_enqueue_scripts` hooks (no longer needed).
- Deleted `assets/css/mscsl-frontend.css` (styles now inline in templates).

## [1.0.3] - 2026-05-13

### Fixed
- Extracted inline CSS from lockout and blocked error pages to external stylesheet per WordPress.org review requirements.
- Created template files for error pages to separate presentation from logic.
- Added X-Frame-Options and X-Content-Type-Options security headers to error pages.

## [1.0.2] - 2026-05-01

### Fixed
- Plugin Check errors for unescaped database parameters in query methods.
- Plugin Check error for fclose() on php://output stream — added phpcs:ignore.
- DROP TABLE query now uses direct query instead of prepare() (table names cannot be prepared).
- Added phpcs:ignore comments for nonce verification warnings in frontend security filters.
- Added cleanup of flush rewrite rules transient in uninstall.

## [1.0.1] - 2026-04-20

### Fixed
- Custom login URL now works immediately after plugin activation without manual permalink flush.
- Custom login URL now works immediately after changing the slug in settings.

## [1.0.0] - 2026-04-15

### Added
- **Initial WordPress.org Release**
- Custom login URL - Change from `/wp-login.php` to custom slug (e.g., `/secure-login/`)
- wp-admin Protection - Block direct access to `/wp-admin/` for non-logged-in users
- Brute Force Protection - Progressive lockout delays after failed attempts
- Email Notifications:
  - Lockout notification emails with customizable subject/body
  - Admin login alert emails
  - New IP login alert emails for users
- Login History - Track all login attempts with filters and pagination
- CSV Export - Export login history to CSV
- XML-RPC Protection - Disable XML-RPC endpoint
- REST API Protection - Block user enumeration via REST API
- IP Whitelist - Bypass brute force protection for trusted IPs
- Recovery URL System - Token-based recovery for forgotten login URLs
- Progressive Lockout - Increasing delays (15min → 30min → 60min, etc.)
- Comprehensive Settings - 5 admin tabs (Settings, Advanced, Email, History, Support)
- 78 PHPUnit tests for automated testing
