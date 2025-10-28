<?php

/**
 * Plugin Name: Carbonfooter
 * Description: Measure the carbon emissions of your website right inside WordPress
 * Version: 0.19.0
 * Requires PHP: 8.0
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Author: Pixel to Planet
 * Author URI: https://carbonfooter.nl
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: carbonfooter
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define basic plugin constants first (needed for autoloader)
define( 'CARBONFOOTER_VERSION', '0.19.0' );
define( 'CARBONFOOTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARBONFOOTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Register autoloader
require_once CARBONFOOTER_PLUGIN_DIR . 'inc/class-autoloader.php';
\CarbonfooterPlugin\Autoloader::register();

// Initialize plugin constants class
\CarbonfooterPlugin\Constants::init();

// Initialize logger
\CarbonfooterPlugin\Logger::init();

/**
 * Initialize the plugin
 */
function carbonfooter_init(): void {
	\CarbonfooterPlugin\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'carbonfooter_init' );

/**
 * Plugin activation hook
 */
register_activation_hook(
	__FILE__,
	function (): void {
		// Set activation redirect transient
		set_transient( \CarbonfooterPlugin\Constants::TRANSIENT_ACTIVATION_REDIRECT, true, 30 );

		\CarbonfooterPlugin\Logger::info( 'Plugin activated, setting redirect transient' );
	}
);
