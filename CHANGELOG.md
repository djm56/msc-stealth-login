# Changelog

All notable changes to MSC Stealth Login are documented in this file.

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
