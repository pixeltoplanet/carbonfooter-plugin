# Carbonfooter WordPress Plugin

A WordPress plugin that measures and displays the carbon footprint of your website.

## Description

Carbonfooter helps you understand and showcase the carbon emissions of your website. The plugin automatically measures the carbon footprint of your pages and provides easy-to-use shortcodes to display this information to your visitors.

## Features

- **Automatic Emissions Measurement**: Measures emissions per page and aggregates site-wide stats
- **Smart Cache System**: Structured per-post cache with 24-hour TTL and intelligent stale data detection
- **Automatic Cache Invalidation**: Cache automatically refreshes on post updates and status changes
- **Background Processing**: Runs API requests asynchronously to avoid impacting page performance with improved background refresh for stale data
- **Automatic Refresh**: Emissions data is refreshed automatically (weekly by default)
- **Admin Columns**: Displays CO₂ emissions for each post/page in the admin list view
- **Flexible Display**: One shortcode with multiple styles (minimal, sticker, full) configurable in Settings
- **Gutenberg Blocks (FSE)**: 9 native blocks in the "Carbonfooter" block category, compatible with Full Site Editing themes
- **Block Patterns**: ready-made footer and stats layouts in the "Carbonfooter" pattern category
- **Block Bindings**: the `carbonfooter/stats` source shows Carbonfooter values in core paragraphs, headings and buttons
- **Editor Sidebar Panel**: page stats and a "Measure now" button in the block editor document sidebar
- **Site Health**: checks for data collection, delayed WP-Cron measurements and API connectivity, plus a Site Health Info section

## Installation

1. Upload the `carbonfooter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Carbonfooter > Settings to configure the plugin

## Requirements

- WordPress 6.6 or higher (tested up to 7.1)
- PHP 8.0 or higher

## Configuration

You can configure the plugin by visiting the Settings page in the WordPress admin area. From there, you can:

- Choose a display style (minimal, sticker, full)
- Enable automatic footer insertion or use shortcodes manually
- Adjust widget colors
- Toggle data collection

## Usage

### Shortcode

Use the single shortcode to render the widget. The visual style is selected in Settings.

```
[carbonfooter]
```

- **Style selection**: Choose between "minimal", "sticker", or "full" in Carbonfooter → Settings → Appearance → Widget style. The shortcode does not take attributes; site-wide style is applied.
- **Automatic insertion**: In Settings, set Display to "Auto" to automatically inject the widget into the site footer on the frontend. Set to "Shortcode" to only render where the shortcode is used.
- **Colors**: Background and text colors are controlled via Settings (exposed as CSS variables `--cf-color-background` and `--cf-color-foreground`).
- **Data shown**: If the current page has measured data, its emissions are shown; otherwise the site average is displayed.

### Usage in Themes

You can add the shortcodes to your theme templates using WordPress's `do_shortcode()` function:

```php
<?php echo do_shortcode('[carbonfooter]'); ?>
```

### Gutenberg Blocks

The plugin registers 9 native Gutenberg blocks under the **"Carbonfooter"** block category, compatible with Full Site Editing (FSE) themes. All blocks use server-side rendering so emissions data is always up to date.

Find them by searching "Carbonfooter" in the block inserter, or browse the dedicated category.

| Block | Description |
|---|---|
| `carbonfooter/minimal` | A lightweight single-line carbon emissions widget |
| `carbonfooter/sticker` | A compact badge-style carbon emissions sticker |
| `carbonfooter/full` | A composable container for building custom layouts using the stat blocks below |
| `carbonfooter/emissions` | Displays CO₂ emissions per visit for the current page |
| `carbonfooter/trees` | Displays trees needed to offset annual emissions for the current page |
| `carbonfooter/driving` | Displays equivalent driving distance from annual emissions for the current page |
| `carbonfooter/pageweight` | Displays the page weight (transfer size) for the current page |
| `carbonfooter/green-hosting` | Displays whether the site uses green energy hosting |
| `carbonfooter/label` | A minimal inline carbon emissions label |

All blocks support color, spacing, border-radius, and typography controls via the block editor sidebar.

### Block Patterns

Patterns are registered in the "Carbonfooter" pattern category (`inc/class-block-patterns.php`):

| Pattern | Description |
|---|---|
| `carbonfooter/footer-bar` | Full-width bar with a short note and the emissions label |
| `carbonfooter/footer-stats` | The `full` block with all five stat blocks |
| `carbonfooter/footer-sticker` | Sticker aligned to the right |
| `carbonfooter/bound-stats` | Three columns of core headings bound to Carbonfooter data |

Footer patterns declare `blockTypes: core/template-part/footer`, so the Site Editor suggests them when editing a footer.

### Block Bindings

The `carbonfooter/stats` bindings source (WordPress 6.5+) resolves a value for the current post (via `postId` block context, or the queried object):

```html
<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"carbonfooter/stats","args":{"key":"emissions"}}}}} -->
<h2 class="wp-block-heading"></h2>
<!-- /wp:heading -->
```

Keys: `emissions`, `page_size`, `trees`, `driving`, `green_hosting`, `last_measured`. In the editor, the fields are also listed in the block's Attributes panel.

### Editor Sidebar Panel and REST API

The document sidebar panel (`src/editor/`) uses two REST endpoints, both requiring `edit_post` for the given post:

- `GET /wp-json/carbonfooter/v1/posts/<id>/stats`
- `POST /wp-json/carbonfooter/v1/posts/<id>/measure` (published, public posts only)

### Site Health

`inc/class-site-health.php` adds three tests (data collection, overdue `carbonfooter_process_emissions` cron events, API reachability) and a "Carbonfooter" section on the Site Health Info tab.

## Performance & Caching

The plugin implements a sophisticated caching system to optimize performance and reduce API calls:

### Smart Cache System
- **Structured Payload**: Each post's cache contains emissions data, page size, timestamp, source, and staleness flag
- **24-Hour TTL**: Cache entries expire after 24 hours but can be marked stale earlier
- **Stale Detection**: Automatic detection of outdated data based on configurable thresholds
- **Memory Efficiency**: Reduced database queries and improved response times

### Automatic Cache Invalidation
- **Post Updates**: Cache automatically invalidates when posts are saved or updated
- **Status Changes**: Cache refreshes when post status changes (draft, published, etc.)
- **Background Refresh**: Stale data is refreshed in the background without blocking user requests
- **Site-wide Clearing**: Statistics and listing caches are cleared when data changes

### Cache Structure
```php
[
  'emissions'   => 10.5,        // grams CO2e
  'page_size'   => 12345,       // bytes
  'updated_at'  => 1694789123,  // unix timestamp
  'source'      => 'api',       // 'api' | 'meta' | 'manual' | 'background'
  'stale'       => false        // staleness flag
]
```

## How it works

- The `[carbonfooter]` shortcode dispatches to one of three renderers (minimal, sticker, full) based on the configured widget style.
- When Display is set to Auto, the plugin adds the widget to the frontend footer via `wp_footer`.
- Emissions are fetched per page with cache-first approach (or fall back to the site average) and formatted for display. The full style includes additional comparisons like estimated annual driving distance and trees needed for offsetting.
- **Cache Integration**: All data fetching prioritizes cached values, with automatic background refresh for stale data
- Admin pages (Results, Settings) are available under Carbonfooter in the WP admin menu.

## Testing

The plugin includes comprehensive test coverage to ensure reliability:

- **9 Tests, 31 Assertions**: Complete test suite covering cache operations, AJAX handlers, and background processing
- **PHPUnit Integration**: Uses PHPUnit with Brain Monkey for WordPress function mocking
- **Cache Testing**: Validates cache roundtrip operations, TTL behavior, and staleness detection
- **Background Processing Tests**: Ensures proper scheduling and execution of background tasks
- **AJAX Handler Tests**: Verifies API endpoint functionality and error handling

### Running Tests
```bash
# Install dependencies
composer install

# Run test suite
vendor/bin/phpunit
```

## Developer Documentation

The plugin follows WordPress coding standards. For developers who want to contribute or extend the plugin, please check the documentation in the `contributing` folder:

- **Coding Standards**: Documentation on code style, naming conventions, and best practices
- **Security Guidelines**: Security considerations for plugin development
- **Linting Configuration**: How to set up and use linting tools
- **REST API**: Settings endpoints under `carbonfooter/v1` (see `inc/class-rest-api-handler.php`)
- **Cache System**: Advanced caching implementation in `inc/class-cache.php`
- **Hook Management**: Centralized hook registration in `inc/class-hooks-manager.php`

## License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.

## Credits

CarbonFooter is developed and maintained by [Carbonfooter](https://carbonfooter.nl).

## External services

This plugin connects to the Carbonfooter API (operated by the same developer/owner as this plugin) to calculate page-level emissions. Data sent may include the page URL, site URL, post ID, plugin version, and a timestamp, when you trigger a measurement, when background refresh runs, or when a page view schedules a refresh.

- See the WordPress readme (readme.txt) “External services” section for full details
- Privacy Policy: https://carbonfooter.nl/privacy
