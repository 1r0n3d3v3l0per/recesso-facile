# Contributing to Recesso Facile

First off, thank you for considering contributing to Recesso Facile! 🎉

It's people like you that make this plugin better for the entire Italian e-commerce community.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Commit Messages](#commit-messages)

---

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code.

**In short:**
- Be respectful and inclusive
- Accept constructive criticism
- Focus on what's best for the community
- Show empathy towards others

---

## How Can I Contribute?

### 🐛 Reporting Bugs

Before creating bug reports, please check the [issue tracker](https://github.com/1r0n3d3v3l0per/recesso-facile/issues) as you might find that you don't need to create one.

When you create a bug report, include as many details as possible:

**Bug Report Template:**
```markdown
**Describe the bug**
A clear description of what the bug is.

**To Reproduce**
Steps to reproduce:
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected behavior**
What you expected to happen.

**Screenshots**
If applicable, add screenshots.

**Environment:**
- WordPress version: [e.g. 6.4]
- WooCommerce version: [e.g. 8.5]
- PHP version: [e.g. 8.1]
- Plugin version: [e.g. 1.0.1]
- Browser: [e.g. Chrome 120]

**Additional context**
Any other relevant information.
```

### 💡 Suggesting Features

Feature requests are welcome! Before suggesting, check if it aligns with the plugin's goals.

**Feature Request Template:**
```markdown
**Is your feature request related to a problem?**
A clear description of the problem.

**Describe the solution you'd like**
What you want to happen.

**Describe alternatives you've considered**
Other solutions you thought about.

**Use case**
How would this feature be used?

**Additional context**
Any other relevant information.
```

### 📝 Improving Documentation

Documentation improvements are always appreciated:
- Fix typos
- Add examples
- Clarify confusing sections
- Translate to other languages

### 💻 Contributing Code

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (see commit guidelines)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

---

## Development Setup

### Prerequisites

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+
- MySQL 5.6+
- Git
- Code editor (VS Code recommended)

### Local Environment Setup

```bash
# 1. Clone your fork
git clone https://github.com/YOUR-USERNAME/recesso-facile.git
cd recesso-facile

# 2. Install WordPress locally (using LocalWP, MAMP, etc.)

# 3. Create symlink to plugin directory
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/recesso-facile

# 4. Activate plugin in WordPress admin

# 5. Install WooCommerce and create test orders
```

### Database Structure

The plugin creates 3 tables:
- `wp_rf_withdrawals` - Withdrawal requests
- `wp_rf_product_exceptions` - Product/category exceptions
- `wp_rf_activity_log` - Activity tracking

---

## Pull Request Process

1. **Update documentation** if you changed functionality
2. **Add tests** if applicable (when test suite exists)
3. **Update CHANGELOG.md** with your changes
4. **Follow coding standards** (see below)
5. **One feature per PR** - keep PRs focused
6. **Describe your changes** clearly in the PR description

### PR Review Checklist

Before submitting, ensure:
- [ ] Code follows WordPress coding standards
- [ ] No PHP warnings/errors
- [ ] No JavaScript console errors
- [ ] Works on mobile devices
- [ ] Compatible with latest WordPress/WooCommerce
- [ ] All strings are translatable
- [ ] Security best practices followed
- [ ] Backwards compatible (or migration provided)

---

## Coding Standards

### PHP

Follow **WordPress PHP Coding Standards**:

```php
// Good
function rf_my_function( $param1, $param2 ) {
    if ( ! empty( $param1 ) ) {
        return sanitize_text_field( $param1 );
    }
    return $param2;
}

// Bad
function rfMyFunction($param1,$param2){
  if(!empty($param1)){
    return $param1;
  }
  return $param2;
}
```

**Key rules:**
- Use tabs for indentation
- Space after control structures: `if (`, `while (`
- Yoda conditions: `if ( 'value' === $variable )`
- Single quotes for strings (unless interpolation needed)
- Always sanitize input, escape output
- Use nonces for forms

### JavaScript

Follow **WordPress JavaScript Coding Standards**:

```javascript
// Good
function verifyOrder( orderId, email ) {
    if ( ! orderId || ! email ) {
        return false;
    }
    return true;
}

// Bad
function verifyOrder(orderId, email) {
  if(!orderId || !email) return false
  return true
}
```

### CSS

```css
/* Good */
.rf-button {
    padding: 10px 20px;
    background: #2271b1;
    color: #ffffff;
}

/* Bad */
.rf-button{padding:10px 20px;background:#2271b1;color:#ffffff}
```

### SQL

**ALWAYS use prepared statements:**

```php
// Good
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rf_withdrawals WHERE order_id = %d",
        $order_id
    )
);

// Bad - SQL INJECTION VULNERABILITY
$wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}rf_withdrawals WHERE order_id = $order_id"
);
```

---

## Commit Messages

Use **conventional commits** format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting (no code change)
- `refactor`: Code restructuring
- `perf`: Performance improvement
- `test`: Adding tests
- `chore`: Maintenance

### Examples

```
feat(form): add IBAN validation for bank transfers

- Implemented mod-97 checksum validation
- Added Italian IBAN format check (27 characters)
- Display clear error message on invalid IBAN

Closes #42
```

```
fix(admin): prevent SQL injection in order sorting

- Added whitelist for ORDER BY columns
- Sanitized sort direction parameter
- Updated security documentation

BREAKING CHANGE: Custom sort parameters no longer work
```

---

## Testing

### Manual Testing Checklist

Before submitting PR, test:

1. **Form submission:**
   - [ ] Step 1: Order verification works
   - [ ] Step 2: Validation works (reason, IBAN)
   - [ ] Step 3: Summary displays correctly
   - [ ] Email sent successfully
   - [ ] PDF generated correctly

2. **Admin panel:**
   - [ ] Dashboard displays stats
   - [ ] Request list loads
   - [ ] Status updates work
   - [ ] Settings save properly

3. **WooCommerce integration:**
   - [ ] Button shows on order page
   - [ ] Thank you page info displays
   - [ ] Email contains link
   - [ ] Admin metabox appears

4. **Edge cases:**
   - [ ] Order > 14 days
   - [ ] Duplicate requests
   - [ ] Product exceptions
   - [ ] Missing data handling

### Automated Tests

(Coming soon - PHPUnit test suite)

---

## Translation

The plugin is translation-ready. To add a translation:

1. Use **Poedit** or similar tool
2. Load `/languages/recesso-facile.pot`
3. Translate strings
4. Save as `recesso-facile-{locale}.po` and `.mo`
5. Submit PR with translation files

**Currently supported:**
- 🇮🇹 Italian (default)

**Wanted:**
- 🇬🇧 English
- 🇫🇷 French
- 🇩🇪 German
- 🇪🇸 Spanish

---

## License

By contributing, you agree that your contributions will be licensed under the **GNU GPL v2.0** license.

---

## Questions?

- 💬 Open a [discussion](https://github.com/1r0n3d3v3l0per/recesso-facile/discussions)
- 📧 Email: support@irn3.com

---

**Thank you for contributing! 🚀**
