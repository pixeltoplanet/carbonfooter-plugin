=== Carbonfooter ===
Contributors: pixeltoplanet, dannymoons, dumithrathnayaka
Tags: carbon footprint, sustainability, emissions, eco-friendly, sustainable website
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.19.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Measure and display your website's carbon footprint with accurate emissions data from carbonfooter.nl.

== Description ==

Carbonfooter helps you understand and showcase the carbon emissions of your website. The plugin automatically measures the carbon footprint of your pages and provides easy-to-use shortcodes to display this information to your visitors.

**Important Note:** This plugin uses the carbonfooter.nl API service to calculate emissions data. By installing and activating this plugin, you agree to share your website URLs with this service for analysis. See the Privacy and External services sections below for more details.

= Features =

* Automatic emissions measurement for all posts and pages
* Background processing to avoid impacting site performance
* Automatic refresh of emissions data (weekly by default)
* One shortcode for display: `[carbonfooter]` (choose the visual style in Settings → Appearance → Widget style: minimal, sticker, or full)
* Post/page columns showing emissions in admin
* Optional green hosting detection
* Multilingual support (English and Dutch)

= Usage =

Use the single shortcode to render the widget. The visual style is selected in Settings.

```
[carbonfooter]
```

- Style selection: Choose between "minimal", "sticker", or "full" in Carbonfooter → Settings → Appearance → Widget style. The shortcode does not take attributes; a site-wide style is applied.
- Automatic insertion: In Settings, set Display to "Auto" to automatically inject the widget into the site footer on the frontend. Set to "Shortcode" to only render where the shortcode is used.

== Privacy ==

This plugin connects to the Carbonfooter API (operated by the same developer/owner as this plugin) to calculate page-level emissions. The following data may be sent to `carbonfooter.nl`:

* Page URL being analyzed
* Site URL
* Post ID
* Plugin version
* A timestamp

When is data sent:
* When you trigger a measurement in the WordPress admin
* When the plugin refreshes stale data in the background (via WP-Cron)
* When a single page view schedules a background refresh

No personal data is collected or stored by this plugin.

Opt-out controls:
* You can disable data collection at any time in Carbonfooter → Settings → Privacy (toggle "Data collection").

Provider:
* Carbonfooter by Pixel to Planet
* Privacy Policy: https://carbonfooter.nl/privacy

Terms:
* Terms of Service for the Carbonfooter API are currently under preparation and will be linked here when available.

== External services ==

This plugin connects to the Carbonfooter API (operated by the same developer/owner as this plugin) to calculate page-level emissions.

What is sent and when:
- Page URL being analyzed
- Site URL, post ID, plugin version, timestamp
- Sent when you trigger a measurement in admin, when the plugin refreshes stale data in the background, or when a single page view schedules a background refresh.

Provider: Carbonfooter (Pixel to Planet)
- Privacy Policy: https://carbonfooter.nl/privacy
- Terms of Service: currently under preparation

== Terms of Service ==

The Carbonfooter API used by this plugin is operated by the same developer/owner as this plugin (Pixel to Planet / carbonfooter.nl). By using this plugin, you agree that the URLs being tested are sent to the external Carbonfooter API for analysis. Terms of Service are currently under preparation. Privacy Policy: https://carbonfooter.nl/privacy

== Installation ==

1. Upload the `carbonfooter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Carbonfooter > Settings to configure options
4. Use shortcodes to display emissions data on your pages

== Frequently Asked Questions ==

= How accurate are the emissions calculations? =

Calculations are based on multiple factors including page size, server location, and hosting type. The methodology follows industry standards for digital carbon footprint calculation.

= How often is the data updated? =

By default, emissions data is refreshed automatically. You can manually trigger updates or adjust display behavior in settings.

= Can I use this without sharing data with carbonfooter.nl? =

No, the emissions calculations require analysis through the carbonfooter.nl service. If you prefer not to share URLs, this plugin may not be suitable for your needs.

= Is the plugin GDPR compliant? =

Yes, the plugin is GDPR compliant. It does not collect or store any personal data. All data collection can be disabled in the settings.

== Screenshots ==

1-Overview. screenshot-1.png
2-Footer. screenshot-2.png
3-Settings page. screenshot-3.png

== Changelog ==

= 0.19.0 =
* Add “External services” section and expand Privacy with data sent and opt‑out controls
* Consolidate shortcode docs to a single [carbonfooter]; style chosen in Settings
* Packaging: release script now also outputs carbonfooter.zip (includes src/) alongside versioned zip
* Update README.md with a short External services note and privacy link
* Update readme files and contributors


= 0.18.0 =
* Convert all inline CSS to wp_add_inline_style() for WordPress compliance
* Convert all inline JavaScript to wp_add_inline_script() for WordPress compliance
* Fix sanitization callbacks in register_setting() calls for proper data validation
* Update frontend widget styles to use proper WordPress enqueue methods
* Convert shortcode styles (minimal, sticker, full) to wp_add_inline_style()
* Move dashboard widget CSS from inline styles to wp_add_inline_style()
* Fix activation redirect script to use wp_add_inline_script()
* Resolve PHPUnit test configuration path issues
* Achieve full WordPress repository compliance standards
* Document external service usage (External services + expanded Privacy)
* Packaging: release script produces carbonfooter.zip (non-versioned) for WP upload


= 0.17.0 =
* Release plugin version 0.17.0 with minior improvements.


= 0.16.3 =
* Fix get_avarage_emissions() is null on dashboard widget


= 0.16.2 =
* Update readme files

= 0.16.1 =
* Fix shortcode output


= 0.16.0 =
* Include version script


= 0.15.0 =
* Major refactor: unified namespace `CarbonfooterPlugin` and improved autoloader
* Added REST Settings API with validation and translators comments
* Hardened security (escaping, capability checks) and improved i18n
* Enhanced docs and inline PHPDoc across codebase
* Improved dashboard widget and admin UX
