# WordPress Coding Standards for Carbonfooter Plugin

This document provides instructions on how to set up and use the linting tools for the Carbonfooter WordPress plugin.

## Setup

1. Install Composer dependencies:

```bash
composer install
```

This will install PHP_CodeSniffer and the WordPress Coding Standards.

## Available Commands

### Check Code Quality

To check your code against WordPress coding standards:

```bash
composer lint
```

This will show you a list of coding standards violations in your code.

### Fix Code Automatically

To automatically fix some of the coding standards violations:

```bash
composer fix
```

Note that not all issues can be fixed automatically. You'll need to manually address some of them.

## Understanding the Configuration

### `.phpcs.xml.dist`

This file contains the PHP_CodeSniffer configuration:

- It's set to check all PHP files in the plugin
- It excludes vendor, node_modules, and other non-plugin code
- It enforces WordPress coding standards
- It checks for PHP 8.0+ compatibility
- It ensures proper prefixing of functions and classes with `carbonfooter` or `CarbonFooter`
- It verifies proper text domain usage for translations

### `.editorconfig`

This file ensures consistent coding style across different editors and IDEs. It sets:

- UTF-8 encoding
- LF line endings
- Tab indentation (4 spaces width) for PHP files
- Space indentation (2 spaces) for JSON, YAML, and Markdown files

## WordPress Coding Standards Resources

- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [WordPress Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/)

## IDE Integration

### Visual Studio Code

1. Install the following extensions:

   - [PHP Sniffer & Beautifier](https://marketplace.visualstudio.com/items?itemName=ValeryanM.vscode-phpsab)
   - [EditorConfig for VS Code](https://marketplace.visualstudio.com/items?itemName=EditorConfig.EditorConfig)
   - [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client)

2. The repository includes a `.vscode/settings.json` file that configures:

   - Tabs (not spaces) for PHP files with a width of 4
   - Spaces for JSON, YAML, and Markdown files with a width of 2
   - Integration with PHP_CodeSniffer using the `.phpcs.xml.dist` file
   - Format on save
   - Other helpful settings for WordPress development

3. After installing the extensions and dependencies, VSCode will automatically:
   - Show coding standard violations as you type
   - Allow you to fix issues with the "Format Document" command
   - Ensure consistent indentation with tabs for PHP files

### PhpStorm

1. Go to Settings > PHP > Quality Tools > PHP_CodeSniffer
2. Set the PHP_CodeSniffer path to your vendor/bin/phpcs
3. Go to Settings > Editor > Inspections > PHP > Quality Tools
4. Enable PHP_CodeSniffer and set the coding standard to Custom, pointing to your `.phpcs.xml.dist` file
