# 🇮🇹 Recesso Facile - WordPress Plugin

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)
[![Made in Italy](https://img.shields.io/badge/Made%20in-Italy%20🇮🇹-green.svg)](https://irn3.com)

> **The definitive solution for managing consumer withdrawal rights in Italy** - Built to support compliance with Art. 54-bis of the Italian Consumer Code (D.Lgs. 206/2005, introduced by D.Lgs. 209/2025) implementing EU Directive 2023/2673.

## 🎯 Why Recesso Facile?

If you run an **e-commerce in Italy**, from **19 June 2026** you **must** provide consumers with an online "withdrawal function" to exercise their right of withdrawal. This obligation comes from **Art. 54-bis** of the Consumer Code (introduced by D.Lgs. 209/2025, transposing **EU Directive 2023/2673**, which amends Directive 2011/83/EU).

> ⚠️ **Disclaimer:** This plugin is a technical tool to help you implement the withdrawal function. It does not constitute legal advice, and compliance responsibility remains with the store owner. Verify your setup with a legal professional.

**❌ It's not enough anymore to:**
- Send a PDF module via email
- Put a link in the terms and conditions
- Tell customers to "contact us"

**✅ You need:**
- A visible button/link labeled "Request Withdrawal Here"
- A guided form collecting: name, order number, email
- Double confirmation step
- Automatic receipt via email with timestamp
- Mobile accessibility
- Active for 14 days with automatic eligibility check

**Recesso Facile does all of this out of the box.** 🚀

---

## ✨ Key Features

### 📋 **3-Step Guided Form**
- **Step 1:** Order verification (name, order ID, email)
- **Step 2:** Withdrawal reason and refund method selection
- **Step 3:** Summary and double confirmation

### 🔐 **Legal Compliance**
- ✅ Supports the Art. 54-bis withdrawal-function requirement (D.Lgs. 209/2025)
- ✅ Implements EU Directive 2023/2673 (amending Dir. 2011/83/EU)
- ✅ Aligned with Art. 52-59 of the Italian Consumer Code (D.Lgs. 206/2005)
- ✅ GDPR-conscious personal data management
- ✅ SHA-256 hash for receipt integrity
- ✅ Complete audit trail

### 🎨 **Professional UI/UX**
- Beautiful progress indicator
- Real-time validation
- AJAX submission (no page reload)
- Fully responsive (mobile-first)
- Sticky withdrawal button option
- Clear error messages

### 🛒 **WooCommerce Integration**
- Withdrawal button on order details page
- Info box on Thank You page
- Direct link in order confirmation emails
- Admin metabox showing withdrawal status
- HPOS (High-Performance Order Storage) compatible

### 📧 **Email System**
- Customer confirmation email (with PDF)
- Admin notification email
- Status update emails
- Customizable templates
- HTML and plain text versions

### 📄 **PDF Receipt Generation**
- Professional PDF with company branding
- SHA-256 hash for authenticity
- Complete order details
- Legal references
- Digital signature

### ⚙️ **Admin Panel**
- Dashboard with statistics
- Request management (approve/reject/complete)
- Product/category exceptions (Art. 59)
- Complete activity log
- 5-tab settings page

### 🔌 **REST API**
- Full REST API for integrations
- GET/POST/PUT endpoints
- Secure authentication
- Developer-friendly

### 🎁 **Product Exceptions (Art. 59)**
Easily exclude products/categories that cannot be withdrawn:
- Personalized/custom products
- Perishable goods
- Sealed hygiene products
- Digital content (if opened)
- And 12 more exception types

---

## 🚀 Quick Start

### Installation

1. **Download** the latest release
2. **Upload** to `/wp-content/plugins/recesso-facile/`
3. **Activate** from WordPress admin panel
4. **Configure** in WooCommerce → Recesso Facile

### Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+
- MySQL 5.6+

### Basic Usage

**Add the form to any page:**
```
[recesso_facile_form]
```

**Add a simple button:**
```
[recesso_facile_button]
```

**Show user's withdrawal requests:**
```
[recesso_facile_status]
```

---

## 📚 Documentation

### Full Documentation
- [WooCommerce Integration](WOOCOMMERCE-INTEGRATION.md)
- [Changelog v1.0.1](CHANGELOG-1.0.1.md)
- [Security Policy](SECURITY.md)
- [Contributing](CONTRIBUTING.md)

### Configuration

**General Settings:**
- Withdrawal period (default: 14 days)
- Sticky button position
- Button text customization
- Terms & conditions page

**Email Settings:**
- Enable/disable customer emails
- Enable/disable admin notifications
- Custom from name and address
- Email templates

**Form Settings:**
- Require withdrawal reason
- Enable additional notes
- Double confirmation
- Guest withdrawal

**Refund Methods:**
- Original payment method
- Bank transfer (with IBAN validation)
- Store credit

**PDF Settings:**
- Company name and address
- VAT number
- Logo upload

---

## 🛠️ Advanced Usage

### Hooks & Filters

**Customize eligibility check:**
```php
add_filter('recesso_facile_check_eligibility', function($eligible, $order_id, $email) {
    // Your custom logic
    return $eligible;
}, 10, 3);
```

**Modify form validation:**
```php
add_filter('recesso_facile_validation_errors', function($errors, $data) {
    // Add custom validation
    return $errors;
}, 10, 2);
```

**After withdrawal creation:**
```php
add_action('recesso_facile_withdrawal_created', function($withdrawal_id, $data) {
    // Your custom action
}, 10, 2);
```

### REST API

**Get all withdrawals:**
```bash
GET /wp-json/recesso-facile/v1/withdrawals
Authorization: Basic [your-credentials]
```

**Create withdrawal:**
```bash
POST /wp-json/recesso-facile/v1/withdrawals
{
  "order_id": 12345,
  "customer_name": "Mario Rossi",
  "email": "mario@example.com",
  "reason": "Changed my mind"
}
```

**Update status:**
```bash
PUT /wp-json/recesso-facile/v1/withdrawals/1/status
{
  "status": "approved",
  "admin_notes": "Approved for refund"
}
```

---

## 🔒 Security

This plugin follows WordPress security best practices:
- ✅ Nonce verification for all AJAX/form submissions
- ✅ Capability checks (`manage_woocommerce`)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (proper escaping)
- ✅ Input validation and sanitization
- ✅ Direct file access prevention

See [SECURITY.md](SECURITY.md) for our security policy.

**Found a security issue?** Please report it privately to: security@irn3.com

---

## 🤝 Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

### Development Setup

```bash
# Clone repository
git clone https://github.com/1r0n3d3v3l0per/recesso-facile.git
cd recesso-facile

# Install dependencies (if any)
composer install

# Run tests (if available)
phpunit
```

### Reporting Bugs

Found a bug? [Open an issue](https://github.com/1r0n3d3v3l0per/recesso-facile/issues/new) with:
- WordPress version
- WooCommerce version
- PHP version
- Steps to reproduce
- Expected vs actual behavior

---

## 💡 Why Open Source?

This plugin was created to help **Italian merchants comply with the law** without paying expensive monthly subscriptions for simple withdrawal management.

**We believe:**
- 🆓 Legal compliance should be accessible to everyone
- 🤝 Open source creates better software through community collaboration
- 🇮🇹 Italian e-commerce deserves world-class tools

If this plugin saves you time/money, consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💻 Contributing code
- 📢 Spreading the word

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0** - see [LICENSE](LICENSE) file.

**What this means:**
- ✅ Free to use commercially
- ✅ Free to modify
- ✅ Free to distribute
- ⚠️ Must open-source modifications
- ⚠️ Must use same license

---

## 👨‍💻 Author

**Andrea Ferro**
- 🌐 Website: [irn3.com](https://irn3.com)
- 📧 Email: support@irn3.com
- 💼 GitHub: [@1r0n3d3v3l0per](https://github.com/1r0n3d3v3l0per)

---

## 🙏 Acknowledgments

- Italian Consumer Code (Codice del Consumo) for legal framework
- WooCommerce team for excellent documentation
- WordPress community for best practices
- All contributors and testers

---

## 📞 Support

- 📖 [Documentation](https://irn3.com/docs/recesso-facile)
- 🐛 [Issue Tracker](https://github.com/1r0n3d3v3l0per/recesso-facile/issues)
- 💬 [Discussions](https://github.com/1r0n3d3v3l0per/recesso-facile/discussions)
- 📧 Email: support@irn3.com

---

<div align="center">

**Made with ❤️ in Italy 🇮🇹**

If this plugin helped you, give it a ⭐!

[⬆ Back to Top](#-recesso-facile---wordpress-plugin)

</div>
