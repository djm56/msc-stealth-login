# MSC Stealth Login

![Version](https://img.shields.io/badge/version-1.3.2-blue)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-blue)
![Tested up to](https://img.shields.io/badge/tested%20up%20to-7.1-blue)

Hide your WordPress login page from attackers. Protect against brute force with custom URLs, lockouts, and email alerts.

**All features are free. There is no premium version.**

## Index

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Recovery / Lockout](#recovery--lockout)
- [Multisite](#multisite)
- [Developer Reference](#developer-reference)
- [Development](#development)
- [Changelog](#changelog)
- [License](#license)

## Features

- **Custom Login URL** — Change `/wp-login.php` to any slug (e.g. `/secure-login/`)
- **wp-admin Protection** — Block direct access to `/wp-admin/` for non-logged-in users
- **Brute Force Protection** — Lock out IPs after configurable failed login attempts
- **Progressive Lockout** — Each successive lockout doubles the wait time (15 min → 30 min → 60 min → 120 min), resets after 24 hours
- **Email Notifications** — Lockout alerts, admin login alerts, new IP login alerts with customisable templates
- **Login History** — Track all login attempts with filters by IP, username, result, and date range
- **CSV Export** — Export login history for security audits (up to 10,000 rows)
- **XML-RPC Protection** — Block XML-RPC requests used for brute force and pingback attacks
- **REST API Protection** — Block user enumeration via `/wp/v2/users` and `?author=` queries
- **IP Whitelist** — Bypass brute force protection for trusted IPs (supports CIDR notation)
- **Recovery URL** — Token-based emergency access if you forget your custom login URL
- **20 Languages** — German (DE/CH), Spanish (ES/MX), French (FR/CA), Italian, Japanese, Dutch (NL/BE), Portuguese (BR/PT), Russian, Simplified Chinese, Turkish, Polish, Indonesian, Swedish, Ukrainian, Arabic

## Installation

### From WordPress Admin

1. Download the plugin zip file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Click **Activate**

### Manual Installation

1. Upload the `msc-stealth-login` folder to `/wp-content/plugins/`
2. Activate via the **Plugins** menu in WordPress

### Post-Activation

1. Go to **Settings → MSC Stealth Login**
2. Configure your custom login slug (default: `secure-login`)
3. **Bookmark your custom login URL and recovery URL immediately**

## Configuration

The plugin has 5 settings tabs:

### Settings Tab

| Option | Description | Default |
|--------|-------------|---------|
| Enable Stealth Login | Master toggle for the custom login URL | Enabled |
| Custom Login URL | Your custom login slug | `secure-login` |
| Hide wp-admin | Block direct `/wp-admin/` access for non-logged-in users | Enabled |
| wp-admin Redirect URL | Where blocked users are sent | Homepage |
| Logout Redirect URL | Where users go after logging out | Homepage |
| Emergency Recovery URL | Token-based bypass URL (bookmark this!) | Auto-generated |

### Advanced Tab

| Option | Description | Default |
|--------|-------------|---------|
| Enable Advanced Security | Master toggle for all advanced features below | Disabled |
| Disable XML-RPC | Block XML-RPC requests | Enabled (when advanced on) |
| Disable REST API User Enumeration | Block `/wp/v2/users` and `?author=` queries | Enabled (when advanced on) |
| Enable Brute Force Protection | Lock out IPs after failed logins | Enabled (when advanced on) |
| Max Login Attempts | Failed attempts before lockout (1–10) | 3 |
| Lockout Duration | Minutes per lockout (5–60) | 15 |
| Enable Login Logging | Log all login attempts to database | Enabled (when advanced on) |
| IP Whitelist | IPs that bypass brute force (comma/newline separated, CIDR supported) | Empty |
| Progressive Lockout Delays | Double lockout time on repeat offences | Disabled |
| Maximum Lockout Duration | Cap for progressive lockout (60–1440 min) | 60 |
| Share Lockouts Across Network | Multisite only — count failed attempts network-wide instead of per site | Disabled |

### Email Tab

| Option | Description | Default |
|--------|-------------|---------|
| Enable Email Notifications | Master toggle for all email features | Disabled |
| Lockout Email Notification | Send email when an IP is locked out | Enabled (when emails on) |
| Notification Email | Recipient for lockout emails | Site admin email |
| Email Subject / Body | Customisable with placeholders | Default templates |
| Admin Login Alert | Email admin on every login | Disabled |
| New IP Login Alert | Email user when login from new IP | Disabled |

**Email placeholders:** `{ip}`, `{attempts}`, `{time}`, `{site_name}`, `{site_url}`

### History Tab

View, filter, and export login attempt history. Filters: IP address, username, result type (Success/Failed/Locked Out/Whitelisted), date range. Export to CSV.

### Support Tab

Setup instructions, feature explanations, FAQ, and contact info.

## Recovery / Lockout

If you lose access to your custom login URL:

1. **Recovery URL** — Use the emergency recovery URL (bookmarked during setup): `https://yoursite.com/wp-login.php?msc_recovery=YOUR_TOKEN`
2. **FTP/SFTP** — Rename the plugin folder: `msc-stealth-login` → `msc-stealth-login-disabled`
3. **WP-CLI** — Run: `wp plugin deactivate msc-stealth-login`
4. **Database** — Set `module_enabled` to `0` in the `mscsl_options` option

## Multisite

The plugin is per-site aware and works with both subdomain and subdirectory networks.

- **Activation** — activate network-wide or per site. Because WordPress only fires the activation hook once on a network activation, each site installs its own table, options and recovery token on first load (`MSCSL\Plugin::maybe_install()`), and sites created later are provisioned via `wp_initialize_site`.
- **Settings** — each site's administrator configures the plugin under Settings → MSC Stealth Login on their own site (`manage_options`). There is no network-admin settings screen; slug, redirects, history and recovery URL are all per site.
- **Recovery URL** — each site has its own recovery token, so bookmark the recovery URL per site.
- **Lockouts** — per site by default. Enable **Share Lockouts Across Network** on the Advanced tab to count failed attempts network-wide via network transients, or force it for every site with the `mscsl_network_shared_lockout` filter:

```php
// In an mu-plugin, to share lockouts across the whole network.
add_filter( 'mscsl_network_shared_lockout', '__return_true' );
```

- **Login history** — each site logs to its own `{prefix}mscsl_login_attempts` table (e.g. `wp_2_mscsl_login_attempts`).
- **Uninstall** — removes the plugin's data from every site on the network.

## Developer Reference

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `MSCSL_PLUGIN_VERSION` | `'1.3.2'` | Current plugin version |
| `MSCSL_PLUGIN_FILE` | `__FILE__` | Absolute path to main plugin file |
| `MSCSL_PLUGIN_DIR` | Plugin directory path | Absolute path to plugin directory |
| `MSCSL_PLUGIN_URL` | Plugin directory URL | URL to plugin directory |
| `MSCSL_OPTION_KEY` | `'mscsl_options'` | Option key used in `wp_options` |

### Plugin Options

All options are stored as a single serialised array under the `mscsl_options` key. Access via:

```php
$options = get_option( 'mscsl_options' );
```

Or via the plugin API:

```php
$plugin = MSCSL\Plugin::instance();
$value  = $plugin->get_option( 'custom_login_slug', 'secure-login' );
```

| Option Key | Type | Default | Description |
|------------|------|---------|-------------|
| `module_enabled` | `int` | `1` | Enable/disable stealth login (1/0) |
| `custom_login_slug` | `string` | `'secure-login'` | Custom login URL slug |
| `hide_wp_admin` | `int` | `1` | Block direct wp-admin access (1/0) |
| `wp_admin_redirect` | `string` | `''` | Redirect URL for blocked wp-admin (empty = homepage) |
| `logout_redirect_url` | `string` | `''` | Redirect URL after logout (empty = homepage) |
| `advanced_security_enabled` | `int` | `0` | Master toggle for advanced features (1/0) |
| `disable_xmlrpc` | `int` | `1` | Block XML-RPC requests (1/0) |
| `disable_rest_api` | `int` | `1` | Block REST API user enumeration (1/0) |
| `brute_force_enabled` | `int` | `1` | Enable brute force protection (1/0) |
| `max_login_attempts` | `int` | `3` | Failed attempts before lockout (1–10) |
| `lockout_duration` | `int` | `15` | Lockout duration in minutes (5–60) |
| `login_logging_enabled` | `int` | `1` | Log login attempts to database (1/0) |
| `ip_whitelist` | `string` | `''` | Comma/newline separated IPs (CIDR supported) |
| `trust_proxy` | `int` | `0` | Trust proxy headers for IP detection (1/0) |
| `progressive_lockout_enabled` | `int` | `0` | Enable progressive lockout delays (1/0) |
| `max_lockout_duration` | `int` | `60` | Max lockout minutes with progressive (60–1440) |
| `email_notifications_enabled` | `int` | `0` | Master toggle for email notifications (1/0) |
| `lockout_email_enabled` | `int` | `1` | Send email on lockout (1/0) |
| `lockout_email_recipient` | `string` | `''` | Lockout notification recipient (empty = admin email) |
| `lockout_email_subject` | `string` | `''` | Custom lockout email subject (empty = default) |
| `lockout_email_body` | `string` | `''` | Custom lockout email body (empty = default) |
| `login_alert_admin` | `int` | `0` | Email admin on every login (1/0) |
| `login_alert_new_ip` | `int` | `0` | Email user on login from new IP (1/0) |
| `network_shared_lockout` | `int` | `0` | Multisite only — share lockout counters network-wide (1/0) |

### Recovery Token

The recovery token is stored separately:

```php
$token = get_option( 'mscsl_recovery_token' );
// 32-character random string, generated on install.
// On multisite each site has its own token, so each site has its own recovery URL.
```

### Custom Actions

| Action | Parameters | Description |
|--------|------------|-------------|
| `mscsl_tabs` | `string $current_tab` | Fires after the settings tab navigation. Use to add custom tabs. |
| `mscsl_settings_sections` | — | Fires at the end of the Settings, Advanced, and Email tab forms. Use to add custom fields. |
| `mscsl_tab_content` | `string $current_tab` | Fires on the Support tab and any custom tabs. Use to render custom tab content. |

### WordPress Hooks Used

The plugin hooks into the following WordPress actions and filters:

**Actions:**
- `init` — Register rewrite rules, block wp-admin, handle recovery URL, flush rewrite rules
- `login_init` — Block direct wp-login.php access
- `template_redirect` — Handle custom login URL requests, block author queries
- `wp_logout` — Handle logout redirect
- `wp_login` — Log successful logins, send login alerts
- `wp_login_failed` — Record failed login attempts
- `xmlrpc_call` — Block specific XML-RPC calls
- `admin_menu` — Register settings page
- `admin_enqueue_scripts` — Load admin CSS/JS
- `admin_notices` — Show conflict warnings, recovery notices

**Plugin filters:**

| Filter | Parameters | Description |
|--------|------------|-------------|
| `mscsl_log_retention_days` | `int $days` | Login-history retention in days. Default `30`. |
| `mscsl_network_shared_lockout` | `bool $shared` | Whether brute-force lockouts are shared across a multisite network. Lets a network operator force sharing on for every site from an mu-plugin. |

**WordPress filters:**
- `login_url` — Rewrite login URL to custom slug (or recovery URL)
- `query_vars` — Add `mscsl_login` and `mscsl_action` query vars
- `authenticate` — Check IP whitelist (priority 1), check login attempts (priority 30), check progressive lockout (priority 32)
- `xmlrpc_enabled` — Disable XML-RPC
- `xmlrpc_methods` — Remove dangerous XML-RPC methods
- `rest_endpoints` — Remove `/wp/v2/users` endpoints

### Database Table

The plugin creates a custom table `{prefix}mscsl_login_attempts`:

| Column | Type | Description |
|--------|------|-------------|
| `id` | `bigint(20)` | Auto-increment primary key |
| `user_login` | `varchar(60)` | Username attempted |
| `ip_address` | `varchar(45)` | Client IP address |
| `attempt_type` | `varchar(20)` | `success`, `failure`, `lockout`, or `whitelisted` |
| `user_agent` | `text` | Browser user agent (max 500 chars) |
| `created_at` | `datetime` | Timestamp of the attempt |

### Reserved Slugs

The following slugs cannot be used as custom login URLs:

`wp-admin`, `wp-login`, `wp-login.php`, `login`, `admin`, `dashboard`, `wp`

Any slug starting with `wp-` is also blocked.

### Uninstall Behaviour

On plugin deletion (not deactivation), the plugin removes:

- `mscsl_options` from `wp_options`
- `mscsl_recovery_token` from `wp_options`
- `mscsl_flush_rewrite_rules` transient
- `mscsl_db_version` from `wp_options`
- All `mscsl_login_attempts_*` transients
- `mscsl_data_notice_dismissed` and `mscsl_known_ips` user meta
- The `{prefix}mscsl_login_attempts` database table
- Scheduled `mscsl_brute_force_cleanup` cron event

On multisite this cleanup runs for every site on the network (in batches of 100), plus any network-wide lockout transients in `wp_sitemeta`. User meta is global, so it is removed once.

## Development

### Requirements

- PHP 7.4+
- Composer
- MySQL/MariaDB (for tests)
- WP-CLI (for .pot generation)
- gettext (`msgfmt`) for .mo compilation

### Setup

```bash
cd msc-stealth-login
composer install
```

### Linting

```bash
# Check coding standards (WordPress-Core, WordPress-Docs, WordPress-Extra)
composer lint

# Auto-fix fixable issues
composer lint-fix
```

### Testing

The plugin includes 78 PHPUnit tests covering core functionality, security features, settings, and module behaviour.

```bash
# One-time setup (interactive — prompts for database credentials)
bash scripts/setup-tests.sh

# Run all tests
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit

# Run with readable output
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit --testdox

# Run a specific test file
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit tests/security-test.php

# Run a specific test
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit --filter test_lockout_after_max_attempts

# Generate coverage report
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit --coverage-html coverage
```

**Test files:**

| File | Tests | Covers |
|------|-------|--------|
| `core-test.php` | 14 | Singleton, activation, deactivation, options |
| `module-test.php` | 28 | Rewrite rules, IP detection, recovery URLs, redirects |
| `security-test.php` | 21 | Brute force, lockouts, XML-RPC, REST API, capabilities |
| `settings-test.php` | 22+ | Options, validation, slug sanitisation, tab saves |

See [tests/README.md](tests/README.md) for detailed testing instructions and troubleshooting.

### Translations

The plugin ships with 20 translations. To update:

```bash
# Regenerate .pot template from source PHP files (requires WP-CLI)
composer run i18n:pot

# Regenerate all .po and .mo files from translation dictionaries
php scripts/generate-translations.php
```

Translation dictionaries are maintained in `scripts/generate-translations.php`. To add or update a translation:

1. Add/update the string in the locale's `'strings'` array
2. Run `php scripts/generate-translations.php`
3. Verify the .po and .mo files were generated

**Supported locales:** de_DE, de_CH, es_ES, es_MX, fr_FR, fr_CA, it_IT, ja, nl_NL, nl_BE, pt_BR, pt_PT, ru_RU, zh_CN, tr_TR, pl_PL, id_ID, sv_SE, uk, ar

### Composer Scripts

| Script | Command | Description |
|--------|---------|-------------|
| `lint` | `composer lint` | Run PHP_CodeSniffer |
| `lint-fix` | `composer lint-fix` | Auto-fix coding standard issues |
| `i18n:pot` | `composer run i18n:pot` | Regenerate .pot file from source |
| `test` | `composer test` | Run PHPUnit tests |
| `test:coverage` | `composer run test:coverage` | Run tests with HTML coverage report |

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL-2.0+ — see [LICENSE](LICENSE) for details.
