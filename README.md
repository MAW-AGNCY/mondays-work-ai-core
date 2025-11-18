# Monday's Work AI Core 🚀

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)
[![Build Status](https://github.com/yourusername/mondays-work-ai-core/workflows/PHP%20Lint%20and%20Code%20Quality/badge.svg)](https://github.com/yourusername/mondays-work-ai-core/actions)

Advanced AI integration core plugin for WordPress with enterprise-grade security features, encryption, and rate limiting.

## 🎯 Features

### Core Functionality
- **🔐 AES-256-CBC Encryption** - Secure encryption for API keys and sensitive data
- **⏱️ Rate Limiting** - Built-in rate limiting using WordPress transients
- **🔒 AJAX Security** - Automatic nonce validation and capability checks
- **📊 PHPUnit Testing** - Comprehensive unit test suite with 20+ tests
- **🎨 WordPress Coding Standards** - 100% WPCS compliant code
- **🚀 PSR-4 Autoloading** - Modern PHP class autoloading

### Security Features
- HMAC authentication for encrypted data
- IP-based and user-based rate limiting
- Automatic request validation
- Secure data storage in WordPress options
- Protection against timing attacks with `hash_equals()`

### Developer Tools
- GitHub Actions CI/CD pipeline
- PHP CodeSniffer configuration
- PHPUnit bootstrap and test suite
- Automated syntax checking for PHP 7.4-8.3

## 📋 Requirements

- **PHP:** 7.4 or higher
- **WordPress:** 5.8 or higher
- **PHP Extensions:** 
  - OpenSSL (required for encryption)
  - mbstring
  - mysqli

## 🔧 Installation

### Manual Installation

1. **Download the plugin:**
   ```bash
   git clone https://github.com/yourusername/mondays-work-ai-core.git
   ```

2. **Upload to WordPress:**
   ```bash
   cp -r mondays-work-ai-core /path/to/wordpress/wp-content/plugins/
   ```

3. **Install dependencies (optional for development):**
   ```bash
   cd wp-content/plugins/mondays-work-ai-core
   composer install
   ```

4. **Activate the plugin:**
   - Go to WordPress Admin → Plugins
   - Find "Monday's Work AI Core"
   - Click "Activate"

### Via WordPress Admin

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

## ⚙️ Configuration

### Setting Up Encryption Key

For maximum security, add a custom encryption key to your `wp-config.php`:

```php
define( 'MONDAYS_WORK_AI_ENCRYPTION_KEY', 'your-32-byte-encryption-key-here' );
```

Generate a secure key:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Basic Usage

#### Encrypting Data

```php
use MondaysWork\AI\Core\Security\Encryption;

$encryption = new Encryption();

// Encrypt an API key
$api_key = 'sk-abc123xyz789';
$encrypted = $encryption->encrypt( $api_key );

// Store encrypted data
$encryption->store_encrypted( 'my_api_key', $api_key );
```

#### Rate Limiting

```php
use MondaysWork\AI\Core\Security\RateLimiter;

$rate_limiter = new RateLimiter();

// Check if request is allowed (60 requests per minute)
$identifier = RateLimiter::get_request_identifier();
if ( $rate_limiter->is_allowed( $identifier, 'api_call', 60, 60 ) ) {
    // Process request
} else {
    // Rate limit exceeded
    $time_until_reset = $rate_limiter->get_time_until_reset( $identifier, 'api_call' );
    wp_send_json_error( "Rate limit exceeded. Try again in {$time_until_reset} seconds." );
}
```

#### AJAX Requests

```javascript
// Frontend JavaScript
jQuery.ajax({
    url: mondaysWorkAI.ajaxUrl,
    type: 'POST',
    data: {
        action: 'mondays_work_ai_process',
        nonce: mondaysWorkAI.nonce,
        input: 'Your data here'
    },
    success: function(response) {
        console.log(response.data);
    }
});
```

## 📁 Project Structure

```
mondays-work-ai-core/
├── includes/
│   ├── Core/
│   │   └── Security/
│   │       ├── Encryption.php      # AES-256-CBC encryption class
│   │       └── RateLimiter.php     # Rate limiting with transients
│   └── Ajax/
│       └── AjaxHandler.php         # AJAX request handler
├── tests/
│   ├── bootstrap.php               # PHPUnit bootstrap
│   └── Unit/
│       └── EncryptionTest.php      # Encryption unit tests
├── .github/
│   └── workflows/
│       └── php-lint.yml            # CI/CD pipeline
├── languages/                      # Translation files
├── mondays-work-ai-core.php       # Main plugin file
├── phpcs.xml                       # PHP CodeSniffer config
├── composer.json                   # Composer dependencies
├── README.md                       # This file
├── CHANGELOG.md                    # Version history
└── LICENSE                         # GPL v2 license
```

## 🧪 Testing

### Running PHPUnit Tests

```bash
# Install dependencies
composer install

# Install WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root root localhost latest

# Run tests
composer test

# Run with coverage
composer test -- --coverage-html coverage/
```

### Running Code Standards Check

```bash
# Check coding standards
composer phpcs

# Auto-fix issues
composer phpcbf
```

## 🔌 Available Hooks

### Actions

```php
// Fires when plugin is fully loaded
do_action( 'mondays_work_ai_loaded' );

// Fires when plugin is deactivated
do_action( 'mondays_work_ai_deactivated' );

// Fires when rate limit is exceeded
do_action( 'mondays_work_ai_rate_limit_exceeded', $identifier, $action, $attempts );
```

### Filters

```php
// Filter encryption key
$key = apply_filters( 'mondays_work_ai_encryption_key', $key );

// Filter rate limit settings
$max_attempts = apply_filters( 'mondays_work_ai_rate_limit_max', 60, $action );
$time_window = apply_filters( 'mondays_work_ai_rate_limit_window', 60, $action );
```

## 🛠️ Development

### Setting Up Development Environment

```bash
# Clone repository
git clone https://github.com/yourusername/mondays-work-ai-core.git
cd mondays-work-ai-core

# Install dependencies
composer install

# Set up pre-commit hooks
composer run-script setup-hooks
```

### Code Standards

This project follows WordPress Coding Standards. All code must pass:

- PHP CodeSniffer (PHPCS)
- PHPUnit tests
- PHP compatibility check (7.4+)

## 🐛 Troubleshooting

### Plugin Won't Activate

**Error:** "Parse error" or syntax error

**Solution:** Ensure you're running PHP 7.4 or higher:
```bash
php -v
```

### OpenSSL Not Available

**Error:** "OpenSSL extension required"

**Solution:** Install/enable OpenSSL:
```bash
# Ubuntu/Debian
sudo apt-get install php-openssl

# CentOS/RHEL
sudo yum install php-openssl
```

### Rate Limit Issues

**Problem:** Transients not clearing

**Solution:** Clean up transients:
```php
$rate_limiter = new RateLimiter();
$rate_limiter->clear_all( 'identifier' );
```

### Encryption Fails

**Problem:** Data cannot be encrypted/decrypted

**Solutions:**
1. Verify OpenSSL is installed
2. Check encryption key is defined
3. Verify filesystem permissions for wp-content

## 📚 Documentation

Full documentation is available in the [Wiki](https://github.com/yourusername/mondays-work-ai-core/wiki):

- [Installation Guide](https://github.com/yourusername/mondays-work-ai-core/wiki/Installation-Guide)
- [Security Configuration](https://github.com/yourusername/mondays-work-ai-core/wiki/Security-Configuration)
- [API Reference](https://github.com/yourusername/mondays-work-ai-core/wiki/API-Reference)
- [Testing Guide](https://github.com/yourusername/mondays-work-ai-core/wiki/Testing-Guide)

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details on:

- Code of Conduct
- Development process
- Pull request process
- Coding standards

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **Your Name** - *Initial work* - [GitHub](https://github.com/yourusername)

## 🙏 Acknowledgments

- WordPress Core Team
- Anthropic for AI capabilities
- All contributors

## 📞 Support

- **Issues:** [GitHub Issues](https://github.com/yourusername/mondays-work-ai-core/issues)
- **Discussions:** [GitHub Discussions](https://github.com/yourusername/mondays-work-ai-core/discussions)
- **Email:** support@layers.tv

## 🔗 Links

- [Plugin Homepage](https://layers.tv)
- [Documentation](https://github.com/yourusername/mondays-work-ai-core/wiki)
- [Report Bug](https://github.com/yourusername/mondays-work-ai-core/issues/new)
- [Request Feature](https://github.com/yourusername/mondays-work-ai-core/issues/new?labels=enhancement)

---

Made with ❤️ for the WordPress community
