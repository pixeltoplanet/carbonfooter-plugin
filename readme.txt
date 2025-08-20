=== Carbonfooter ===
Contributors: dannymoons
Tags: carbon footprint, sustainability, emissions, eco-friendly, sustainable website
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.16.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Measure and display your website's carbon footprint with accurate emissions data from carbonfooter.nl.

== Description ==

Carbonfooter helps you understand and showcase the carbon emissions of your website. The plugin automatically measures the carbon footprint of your pages and provides easy-to-use shortcodes to display this information to your visitors.

**Important Note:** This plugin uses the carbonfooter.nl API service to calculate emissions data. By installing and activating this plugin, you agree to share your website URLs with this service for analysis. See the Privacy section below for more details.

= Features =

* Automatic emissions measurement for all posts and pages
* Background processing to avoid impacting site performance
* Automatic refresh of emissions data (weekly by default)
* Three display options via shortcodes:
  - Minimal display [carbonfooter_minimal]
  - Sticker display [carbonfooter_sticker]
  - Full banner display [carbonfooter_full]
* Post/page columns showing emissions in admin
* Optional green hosting detection
* Multilingual support (English and Dutch)

= Privacy =

This plugin sends the following data to carbonfooter.nl:
* Page URLs for emissions calculation
* Basic site information for analysis

No personal data is collected or stored. You can opt-out of data collection at any time by disabling automatic checks in the plugin settings.

= Terms of Service =

By using this plugin, you agree that the URLs that are being tested are send to the external carbonfooter api

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

1. Minimal emissions display - Shows a simple badge with your page's carbon emissions
2. Sticker display option - A more detailed sticker showing emissions and hosting type
3. Full banner display - Comprehensive display with all emissions data and tips
4. Admin settings page - Configure your plugin settings and display options
5. Post columns showing emissions - Quick overview in your posts list

== Changelog ==

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
