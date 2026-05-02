=== MSC Stealth Login ===
Donate link: https://anomalous.co.za/donate
Tags: security, login, brute-force, wp-admin, stealth
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide your WordPress login page from attackers. Protect against brute force with custom URLs, lockouts, and email alerts.

== Description ==

MSC Stealth Login provides comprehensive protection for your WordPress login page, blocking attackers while keeping your site accessible to legitimate users.

**Stealth Login URL**

Change your login page from `/wp-login.php` to a custom URL like `/secure-login/`. Attackers scanning for standard WordPress login pages will be blocked before they can even attempt a brute force attack.

**wp-admin Protection**

Block direct access to `/wp-admin/` for users who aren't logged in. They'll be redirected to your custom login page instead, preventing exposure of your admin area.

**Brute Force Protection**

After failed login attempts, MSC Stealth Login progressively increases lockout durations. First-time offenders wait 15 minutes, repeat offenders face increasingly longer delays. This stops automated attacks while minimizing disruption to real users who mistype their password.

**Email Notifications**

Stay informed about security events with configurable email alerts:

* Lockout notifications when IPs are blocked
* Admin login alerts for every administrator sign-in
* New IP alerts when users log in from previously unseen locations

**Login History & Export**

Track all login attempts with detailed logging. Filter by IP address, username, result type, or date range. Export reports to CSV for security audits.

**XML-RPC & REST API Protection**

Disable vulnerable XML-RPC endpoints commonly exploited for brute force attacks. Block REST API user enumeration that lets attackers harvest usernames.

**IP Whitelist**

Bypass protection for trusted IP addresses. Add your office, home, or server IPs to ensure uninterrupted access while maintaining maximum security for everyone else.

**Progressive Lockout System**

Unlike simple lockouts that reset immediately, MSC Stealth Login uses a multiplier system. Each successive lockout doubles the wait time (15 min → 30 min → 60 min → 120 min). The multiplier resets after 24 hours without an attempt, balancing security with usability.

**Recovery URL**

Forgot your custom login URL? No problem. The recovery system lets you regain access through a secure bypass URL that's displayed in your WordPress admin bar when logged in.

== Installation ==

1. **Upload** the plugin files to `/wp-content/plugins/msc-stealth-login/` directory
2. **Activate** the plugin through the 'Plugins' menu in WordPress
3. **Navigate** to Settings → MSC Stealth Login
4. **Configure** your custom login URL (e.g., `/secure-login/`)
5. **Enable** additional security features as needed (brute force protection, email alerts, etc.)
6. **Save** your recovery URL somewhere safe — bookmark it or store it securely

**Important:** After activation, immediately bookmark your new login URL and save your recovery URL in a secure location.

== Frequently Asked Questions ==

= How does the stealth login work? =

MSC Stealth Login uses WordPress rewrite rules to redirect requests from the standard `/wp-login.php` to your custom URL. When visitors try to access the old login page, they're blocked and redirected. The custom URL only works when you explicitly configure it.

= Will this break my site or existing plugins? =

The plugin is designed to work with standard WordPress installations and popular plugins. The custom login URL and wp-admin protection may conflict with plugins that have their own login flows. Always test on a staging site first, and keep your recovery URL bookmarked.

= What happens if I forget my custom login URL? =

Use the recovery URL system. When logged in, your WordPress admin bar shows the recovery URL. Alternatively, access your site via FTP or hosting control panel and rename the plugin folder to disable it temporarily.

= How do I recover access if I'm locked out? =

Wait for the lockout period to expire (starts at 15 minutes and increases with repeat attempts). If you need immediate access, disable the plugin via FTP by renaming the plugin folder. Your IP can also be added to the whitelist if you have database access.

= Does this work with caching plugins? =

Yes, but ensure your login pages aren't cached. Most caching plugins have options to exclude specific pages. You'll want to exclude your custom login URL and wp-admin directory from caching.

= Can I use this with Wordfence/other security plugins? =

Generally yes, but some security plugins have overlapping features. You may want to disable redundant features (like brute force protection) in one plugin to avoid conflicts. Test thoroughly before deploying to production.

= How do the email notifications work? =

Navigate to Settings → MSC Stealth Login → Email tab. Enable the notifications you want and customize the subject and body using placeholders: `{ip}`, `{attempts}`, `{time}`, `{site_name}`, `{site_url}`. Notifications are sent immediately when events occur.

= Is there a premium version? =

No, all features are included in the free version. There is no premium version or paid upgrade.

== Screenshots ==

1. Settings tab - Configure your custom login URL and security options
2. Advanced tab - Enable brute force protection and security features  
3. Email tab - Configure email notifications for lockouts and alerts
4. History tab - View login attempts with filters and CSV export

== Changelog ==

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

= 1.0.2 =
Fixes Plugin Check errors for WordPress.org submission readiness.

= 1.0.1 =
Fixes rewrite rules flush issue where custom login URL returned 404 after activation or slug change.

= 1.0.0 =
Initial release of MSC Stealth Login.
