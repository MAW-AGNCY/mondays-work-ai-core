# Changelog

All notable changes to the Monday's Work AI Core plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2025-01-27

### 🐛 Fixed

- **CRITICAL**: Fixed Parse Error in `OpenAIClient.php` line 813
  - Removed doubled single quote in regex pattern (`$''` → `$'`)
  - Plugin now activates correctly without syntax errors
  - Error message: "Parse error: syntax error, unexpected single-quoted string on line 815"

### ✨ Added

- Comprehensive troubleshooting section in README.md
  - Solutions for Parse errors during plugin activation
  - Guide for API key compatibility issues
  - Fixes for white screen and activation problems
  - Autoloader troubleshooting steps

- Security and best practices documentation
  - API key protection mechanisms
  - Security recommendations (HTTPS, rate limits, monitoring)
  - Input validation and sanitization details
  - SQL injection protection information

- Detailed technical requirements section
  - PHP 7.4+ compatibility specifications
  - Required PHP extensions (json, curl, mbstring)
  - WordPress features utilized (Options, Settings, Transients API)
  - Compatibility matrix (shared hosting, multisite, WooCommerce)

- Recent changelog section in README
  - Documented critical bug fix
  - Listed PHP compatibility improvements
  - Documented API key format support

- Contributing guidelines
  - Bug reporting template
  - Feature suggestion process
  - Code of conduct for collaborators

### 🔒 Security

- Confirmed API key validation supports both formats:
  - Legacy format: `sk-xxxxxxxxxxxxxxxxxxxxxxxx`
  - New project-based format: `sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx`
- Verified input sanitization using WordPress functions
- Confirmed prepared statements for SQL protection

### 📚 Documentation

- Updated README.md with 150+ new lines of documentation
- All PHP code verified to have comprehensive PHPDoc comments
- All JavaScript code verified to have JSDoc comments
- Bilingual documentation (Spanish/English) maintained

### ✅ Verified

- PHP 7.4+ compatibility confirmed
  - No use of PHP 8.0+ only functions
  - Uses compatible alternatives (e.g., `strpos()` instead of `str_starts_with()`)
- JavaScript regex patterns confirmed to support new API key format
- WordPress Coding Standards compliance maintained
- PSR-4 autoloading working correctly

## [1.0.0] - 2025-01-20

### ✨ Added

- Initial release of Monday's Work AI Core plugin
- Multi-AI provider support (OpenAI, Google Gemini, local models)
- Factory pattern architecture for easy extensibility
- Comprehensive admin interface with Mondays at Work branding
- Rate limiting and error handling
- Caching system for improved performance
- WordPress and WooCommerce integration
- PSR-4 autoloading (no Composer required in production)
- Bilingual code documentation (Spanish/English)
- Complete API client implementation for OpenAI
- Form validation and AJAX functionality
- Accessibility features (ARIA attributes, keyboard navigation)
- Security features (input validation, sanitization, nonce verification)

---

## Tipos de Cambios / Change Types

- `Added` / `Añadido`: New features / Nuevas funcionalidades
- `Changed` / `Cambiado`: Changes in existing functionality / Cambios en funcionalidad existente
- `Deprecated` / `Obsoleto`: Soon-to-be removed features / Funcionalidades que pronto se eliminarán
- `Removed` / `Eliminado`: Removed features / Funcionalidades eliminadas
- `Fixed` / `Corregido`: Bug fixes / Correcciones de errores
- `Security` / `Seguridad`: Security improvements / Mejoras de seguridad
