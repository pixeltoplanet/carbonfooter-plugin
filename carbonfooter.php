<?php

/**
 * Plugin Name: Carbonfooter
 * Description: Measure the carbon emissions of your website right inside WordPress
 * Version: 0.21.0
 * Requires PHP: 8.0
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Author: Pixel to Planet
 * Author URI: https://carbonfooter.nl
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: carbonfooter
 * Domain Path: /languages
 *
 * @package CarbonFooter
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

// Define basic plugin constants first (needed for autoloader)
define('CARBONFOOTER_VERSION', '0.21.0');
define('CARBONFOOTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CARBONFOOTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register autoloader
require_once CARBONFOOTER_PLUGIN_DIR . 'inc/class-autoloader.php';
\CarbonfooterPlugin\Autoloader::register();

// Initialize plugin constants class
\CarbonfooterPlugin\Constants::init();

// Initialize logger
\CarbonfooterPlugin\Logger::init();

/**
 * Sync bundled translations to the site-level override directory.
 *
 * WordPress checks wp-content/languages/plugins/ before the plugin's own
 * languages/ folder. When a plugin is not (yet) on WordPress.org, any
 * auto-downloaded pack there is incomplete and causes mixed-language output.
 *
 * Solution: on every new plugin version, copy our complete bundled .mo files
 * into that directory, overwriting whatever WordPress placed there. We also
 * remove any companion .l10n.php (a PHP-optimised cache that WordPress 6.5+
 * prefers over .mo) because we cannot regenerate that format ourselves —
 * without it WordPress falls back cleanly to our freshly copied .mo.
 *
 * The sync runs inline (not via a hook) so it completes before WordPress's
 * just-in-time translation loader touches the textdomain for this request.
 * A wp_option prevents redundant file I/O on subsequent requests.
 */
function carbonfooter_sync_translations(): void
{
	if (! defined('WP_CONTENT_DIR') || ! defined('CARBONFOOTER_PLUGIN_DIR') || ! function_exists('get_option')) {
		return;
	}

	// Only run once per plugin version.
	if (get_option('carbonfooter_translations_synced_version', '') === CARBONFOOTER_VERSION) {
		return;
	}

	$plugin_lang_dir = CARBONFOOTER_PLUGIN_DIR . 'languages/';
	$override_dir    = WP_CONTENT_DIR . '/languages/plugins/';

	if (! is_dir($override_dir)) {
		wp_mkdir_p($override_dir);
	}

	$mo_files = glob($plugin_lang_dir . 'carbonfooter-*.mo');
	$synced   = array();

	foreach ($mo_files as $source_mo) {
		$filename = basename($source_mo);
		$locale   = preg_replace('/^carbonfooter-(.+)\.mo$/', '$1', $filename);
		$dest_mo  = $override_dir . $filename;
		$dest_l10n = $override_dir . 'carbonfooter-' . $locale . '.l10n.php';

		// Overwrite the (possibly incomplete) WordPress-downloaded .mo with ours.
		if (@copy($source_mo, $dest_mo)) {
			$synced[] = $locale;
		}

		// Remove the .l10n.php cache so WordPress uses our fresh .mo above.
		if (file_exists($dest_l10n)) {
			@unlink($dest_l10n);
		}
	}

	if (! empty($synced)) {
		// Record the synced version so this only runs once per release.
		update_option('carbonfooter_translations_synced_version', CARBONFOOTER_VERSION, false);
		// Signal the admin notice.
		set_transient('carbonfooter_translations_synced', $synced, HOUR_IN_SECONDS);
	}
}
carbonfooter_sync_translations();

/**
 * Initialize the plugin
 */
function carbonfooter_init(): void
{
	\CarbonfooterPlugin\Plugin::get_instance();
}
add_action('plugins_loaded', 'carbonfooter_init');

/**
 * Plugin activation hook
 */
register_activation_hook(
	__FILE__,
	function (): void {
		// Set activation redirect transient
		set_transient(\CarbonfooterPlugin\Constants::TRANSIENT_ACTIVATION_REDIRECT, true, 30);

		\CarbonfooterPlugin\Logger::info('Plugin activated, setting redirect transient');
	}
);
