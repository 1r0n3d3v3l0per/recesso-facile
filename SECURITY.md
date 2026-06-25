# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take security seriously. If you discover a security vulnerability within Recesso Facile, please send an email to **security@irn3.com**.

**Please do NOT:**
- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly until it has been patched
- Exploit the vulnerability for any purpose

**Please DO:**
- Provide detailed information about the vulnerability
- Include steps to reproduce
- Suggest a possible fix (if you have one)
- Allow reasonable time for us to patch before disclosure

## Response Timeline

- **Initial response:** Within 48 hours
- **Patch release:** Within 7 days for critical issues
- **Public disclosure:** After patch is released and users have time to update

## Security Best Practices

This plugin implements:

### Input Validation
- All user input is sanitized using WordPress functions
- Email validation: `is_email()`
- Text fields: `sanitize_text_field()`
- Textareas: `sanitize_textarea_field()`
- Numbers: `absint()` or `intval()`

### SQL Injection Prevention
- All database queries use `$wpdb->prepare()`
- Whitelisted sorting/ordering parameters
- No direct SQL concatenation

### XSS Protection
- All output is escaped: `esc_html()`, `esc_attr()`, `esc_url()`
- No usage of `eval()` or similar functions
- Email templates use safe variable access

### CSRF Protection
- All forms use WordPress nonces
- AJAX requests verify nonces: `check_ajax_referer()`
- Admin actions verify nonces: `check_admin_referer()`

### Authorization
- Capability checks: `current_user_can('manage_woocommerce')`
- Order verification matches customer email
- No privilege escalation vulnerabilities

### File Security
- Direct access prevention: `if (!defined('ABSPATH')) exit;`
- PDF uploads stored outside web root (when possible)
- File type validation

### Password & Authentication
- No passwords stored in database
- Uses WordPress authentication system
- Session management via WordPress

## Known Security Measures

### Implemented
✅ Nonce verification on all forms/AJAX
✅ Prepared SQL statements
✅ Input sanitization
✅ Output escaping
✅ Capability checks
✅ IBAN validation with checksum
✅ Email validation
✅ Rate limiting considerations
✅ GDPR compliance

### Planned
⏳ Two-factor authentication for admin actions
⏳ Activity log IP validation
⏳ Automatic security updates notification
⏳ Integration with WP security plugins

## Vulnerability Disclosure

We believe in responsible disclosure. If you report a security issue:

1. We will acknowledge receipt within 48 hours
2. We will work with you to understand the issue
3. We will develop and test a patch
4. We will release the patch as soon as possible
5. We will credit you in the changelog (if desired)
6. After release, we may publish a security advisory

## Security Hall of Fame

Contributors who responsibly disclosed vulnerabilities:

*No vulnerabilities reported yet*

---

Thank you for helping keep Recesso Facile secure! 🔒
