<?php

/**
 * Autoloader functionality.
 *
 * This file handles autoloading of plugin classes.
 *
 * @package CarbonfooterPlugin
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader
 *
 * Handles autoloading of plugin classes within the `CarbonfooterPlugin` namespace.
 * Converts class names to kebab-case file names under `inc/` following the
 * `class-*.php` convention.
 */
class Autoloader {

	/**
	 * Register autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class_name Full class name.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		// Only handle our namespace
		if ( strpos( $class_name, 'CarbonfooterPlugin\\' ) !== 0 ) {
			return;
		}

		// Remove namespace from class name
		$class_name = str_replace( 'CarbonfooterPlugin\\', '', $class_name );

		// Convert camelCase and underscores to kebab-case
		$file_name = 'class-' . strtolower( str_replace( '_', '-', preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name ) ) ) . '.php';

		// Get plugin directory
		$plugin_dir = plugin_dir_path( __DIR__ );

		// Build file path
		$file_path = $plugin_dir . 'inc/' . $file_name;

		// Load file if it exists
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
}
