# Contributing to CarbonFooter

This directory contains documentation for developers who want to contribute to the CarbonFooter plugin.

## Contents

- **[CODING-STANDARDS.md](CODING-STANDARDS.md)** - Detailed guide on the coding standards used in this plugin, including file organization, naming conventions, documentation requirements, and formatting rules.

- **[NAMING-CONVENTIONS.md](NAMING-CONVENTIONS.md)** - Specific guidance on naming conventions for files, classes, functions, and variables, following WordPress best practices.

- **[SECURITY-BEST-PRACTICES.md](SECURITY-BEST-PRACTICES.md)** - Security guidelines for plugin development, including data validation, sanitization, SQL query preparation, and more.

- **[LINTING.md](LINTING.md)** - Instructions for setting up and using the linting tools to maintain code quality.

## Development Setup

### Prerequisites

- WordPress 5.6 or higher
- PHP 8.0 or higher
- Composer (for development)

### Installation for Development

1. Clone this repository into your WordPress plugins directory:

   ```
   cd wp-content/plugins/
   git clone https://github.com/dannymoons/carbonfooter.git
   ```

2. Install development dependencies:

   ```
   cd carbonfooter
   composer install
   ```

3. Activate the plugin in the WordPress admin panel.

### Code Quality Tools

This plugin uses PHP_CodeSniffer to ensure code quality and adherence to WordPress Coding Standards.

#### Check Coding Standards

```
composer lint
```

#### Automatically Fix Coding Standards Issues

```
composer fix
```

#### Fix a Specific File

```
composer fix-file path/to/file.php
```

## Contributing Process

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-new-feature`
3. Read the coding standards documentation
4. Make your changes and ensure they pass linting: `composer lint`
5. Commit your changes: `git commit -am 'Add some feature'`
6. Push to the branch: `git push origin feature/my-new-feature`
7. Submit a pull request 