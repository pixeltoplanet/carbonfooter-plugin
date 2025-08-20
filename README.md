# Carbonfooter WordPress Plugin

A WordPress plugin that measures and displays the carbon footprint of your website.

## Description

Carbonfooter helps you understand and showcase the carbon emissions of your website. The plugin automatically measures the carbon footprint of your pages and provides easy-to-use shortcodes to display this information to your visitors.

## Features

- **Automatic Emissions Measurement**: Measures emissions per page and aggregates site-wide stats
- **Background Processing**: Runs API requests asynchronously to avoid impacting page performance
- **Automatic Refresh**: Emissions data is refreshed automatically (weekly by default)
- **Admin Columns**: Displays CO₂ emissions for each post/page in the admin list view
- **Multiple Display Options**: Choose from minimal, sticker, or full display options via shortcodes

## Installation

1. Upload the `carbonfooter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Carbonfooter > Settings to configure the plugin

## Requirements

- WordPress 5.6 or higher
- PHP 8.0 or higher

## Configuration

You can configure the plugin by visiting the Settings page in the WordPress admin area. From there, you can:

- Choose a display style (minimal, sticker, full)
- Enable automatic footer insertion or use shortcodes manually
- Adjust widget colors
- Toggle data collection

## Usage

### Shortcodes

The plugin provides three shortcodes to display carbon emissions data:

#### 1. Minimal Display

```
[carbonfooter_minimal]
```

Displays a simple text showing the current page emissions (falls back to site average).

#### 2. Sticker Display

```
[carbonfooter_sticker]
```

Displays a compact sticker with emissions data.

#### 3. Full Display

```
[carbonfooter_full]
```

Displays a detailed emissions banner with additional information and comparisons.

### Usage in Themes

You can add the shortcodes to your theme templates using WordPress's `do_shortcode()` function:

```php
<?php echo do_shortcode('[carbonfooter_minimal]'); ?>
```

## Developer Documentation

The plugin follows WordPress coding standards. For developers who want to contribute or extend the plugin, please check the documentation in the `contributing` folder:

- **Coding Standards**: Documentation on code style, naming conventions, and best practices
- **Security Guidelines**: Security considerations for plugin development
- **Linting Configuration**: How to set up and use linting tools
- **REST API**: Settings endpoints under `carbonfooter/v1` (see `inc/class-rest-api-handler.php`)

## License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.

## Credits

CarbonFooter is developed and maintained by [Carbonfooter](https://carbonfooter.nl).
