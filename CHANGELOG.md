# Changelog

All notable changes to MSC Stealth Login are documented in this file.

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
