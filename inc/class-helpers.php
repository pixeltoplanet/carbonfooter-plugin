<?php

/**
 * Helper utilities for the CarbonFooter plugin
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper utilities class
 *
 * Contains reusable utility methods for the CarbonFooter plugin, including
 * environment detection, formatting helpers, URL sanitization, logging shims,
 * mock data generation, DB table creation, and caching plugin detection.
 */
class Helpers {


	/**
	 * Check if we're in a local development environment.
	 *
	 * Uses WordPress's `wp_get_environment_type()` and augments with common
	 * host/URL patterns and dev ports, to enable safe local behavior.
	 *
	 * @return bool True when a local/dev environment is detected
	 */
	public static function is_local_environment() {
		// First check WordPress's built-in environment detection
		$wp_env = wp_get_environment_type();
		if ( in_array( $wp_env, array( 'local', 'development' ), true ) ) {
			return true;
		}

		// Additional checks for common local development indicators
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

		$local_patterns = array(
			'localhost',
			'127.0.0.1',
			'::1',
			'.local',
			'.test',
			'.dev',
			'staging.',
			'dev.',
			'.ngrok.io',
			'.wpsandbox.pro',
			'.staging.wpengine.com',
			'.dev.wpengine.com',
		);

		foreach ( $local_patterns as $pattern ) {
			if ( strpos( $host, $pattern ) !== false ) {
				return true;
			}
		}

		// Check for common local development ports
		if ( preg_match( '/:(8000|8080|3000|4000|5000|9000)$/', $host ) ) {
			return true;
		}

		// Check site URL patterns
		$site_url = get_site_url();
		foreach ( $local_patterns as $pattern ) {
			if ( strpos( $site_url, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize and validate URL.
	 *
	 * @param string $url           URL to validate
	 * @param bool   $require_https Whether to require HTTPS scheme
	 * @return string|false Sanitized URL or false if invalid
	 */
	public static function sanitize_url( $url, $require_https = false ) {
		$url = esc_url_raw( $url );

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		if ( $require_https && strpos( $url, 'https://' ) !== 0 ) {
			$url = str_replace( 'http://', 'https://', $url );
		}

		return $url;
	}

	/**
	 * Format file size in human readable format.
	 *
	 * @param int $bytes     File size in bytes
	 * @param int $precision Decimal precision
	 * @return string Human-readable size (e.g., "1.23 MB")
	 */
	public static function format_file_size( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$units_count = count( $units );
		for ( $i = 0; $bytes > 1024 && $i < $units_count - 1; $i++ ) {
			$bytes /= 1024;
		}

		return round( $bytes, $precision ) . ' ' . $units[ $i ];
	}

	/**
	 * Get current timestamp in WordPress timezone.
	 *
	 * @param string $format Date format
	 * @return string Formatted timestamp
	 */
	public static function get_wp_timestamp( $format = 'Y-m-d H:i:s' ) {
		return current_time( $format );
	}

	/**
	 * Log message using plugin's logging function if available.
	 *
	 * @param string $message Log message
	 * @param string $level   Log level (error, warning, info, debug)
	 */
	public static function log( $message, $level = 'info' ) {
		Logger::log( $message, '', $level );
	}

	/**
	 * Generate mock data for local development.
	 *
	 * @param string $type Type of mock data (emissions, visitor_count, page_load_time)
	 * @return mixed Mock payload for the requested type
	 */
	public static function generate_mock_data( $type ) {
		switch ( $type ) {
			case 'emissions':
				return array(
					'emissions'     => round( wp_rand( 1, 350 ) / 100, 2 ),
					'page_size'     => wp_rand( 100 * 1024, 2 * 1024 * 1024 ),
					'is_green_host' => (bool) wp_rand( 0, 1 ),
				);

			case 'visitor_count':
				return wp_rand( 1, 50 );

			case 'page_load_time':
				return round( wp_rand( 500, 3000 ) / 1000, 2 ); // 0.5-3 seconds

			default:
				return null;
		}
	}

	/**
	 * Check if a value is a valid hex color.
	 *
	 * @param string $color Color value to check (e.g., #000, #ffffff)
	 * @return bool True if the color is valid hex
	 */
	public static function is_valid_hex_color( $color ) {
		return preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color );
	}

	/**
	 * Create database table with proper error handling.
	 *
	 * @param string $table_name Full table name
	 * @param string $sql        CREATE TABLE SQL
	 * @return bool Success status
	 */
	public static function create_table( $table_name, $sql ) {
		global $wpdb;

		// Check if table already exists
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		if ( $table_exists === $table_name ) {
			return true; // Table already exists
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$result = dbDelta( $sql );

		// Check if table was created successfully
		$table_created = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		) === $table_name;

		if ( $table_created ) {
			self::log( "Database table created: $table_name" );
		} else {
			self::log( "Failed to create database table: $table_name", 'error' );
		}

		return $table_created;
	}

	/**
	 * Check if a caching plugin is active.
	 *
	 * Detects common WordPress caching plugins including:
	 * - WP Rocket
	 * - W3 Total Cache
	 * - WP Super Cache
	 * - LiteSpeed Cache
	 * - Autoptimize
	 * - WP Fastest Cache
	 * - Cache Enabler
	 * - Comet Cache
	 * - SG Optimizer
	 * - WP-Optimize
	 * - Hummingbird
	 * - Perfmatters
	 * - FlyingPress
	 * - Swift Performance
	 * - WP Optimize
	 * - Breeze
	 * - WP Engine's caching
	 * - Kinsta's caching
	 *
	 * @return array{active:bool,plugins:array<int,string>} Detection result
	 */
	public static function check_caching_plugins() {
		$caching_plugins = array(
			'wp-rocket/wp-rocket.php'                    => 'WP Rocket',
			'w3-total-cache/w3-total-cache.php'          => 'W3 Total Cache',
			'wp-super-cache/wp-cache.php'                => 'WP Super Cache',
			'litespeed-cache/litespeed-cache.php'        => 'LiteSpeed Cache',
			'autoptimize/autoptimize.php'                => 'Autoptimize',
			'wp-fastest-cache/wpFastestCache.php'        => 'WP Fastest Cache',
			'cache-enabler/cache-enabler.php'            => 'Cache Enabler',
			'comet-cache/comet-cache.php'                => 'Comet Cache',
			'sg-cachepress/sg-cachepress.php'            => 'SG Optimizer',
			'wp-optimize/wp-optimize.php'                => 'WP-Optimize',
			'hummingbird-performance/wp-hummingbird.php' => 'Hummingbird',
			'perfmatters/perfmatters.php'                => 'Perfmatters',
			'flying-press/flying-press.php'              => 'FlyingPress',
			'swift-performance-lite/performance.php'     => 'Swift Performance Lite',
			'swift-performance/performance.php'          => 'Swift Performance',
			'breeze/breeze.php'                          => 'Breeze',
			'kinsta-mu/kinsta-mu.php'                    => 'Kinsta Cache',
			'wpengine-common/wpengine-common.php'        => 'WP Engine Cache',
		);

		$active_plugins         = get_option( 'active_plugins', array() );
		$network_active_plugins = array();

		// Check for network-activated plugins if multisite
		if ( is_multisite() ) {
			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		}

		$detected_plugins = array();

		// Check regular plugins
		foreach ( $caching_plugins as $plugin_file => $plugin_name ) {
			if ( in_array( $plugin_file, $active_plugins, true ) ) {
				$detected_plugins[] = $plugin_name;
			}
		}

		// Check network-activated plugins
		foreach ( $caching_plugins as $plugin_file => $plugin_name ) {
			if ( array_key_exists( $plugin_file, $network_active_plugins ) ) {
				$detected_plugins[] = $plugin_name;
			}
		}

		// Check for hosting-specific caching
		$hosting_cache_indicators = array(
			'WP_ROCKET_IS_ON'            => 'WP Rocket',
			'W3TC_DYNAMIC_SECURITY'      => 'W3 Total Cache',
			'LSCWP_V'                    => 'LiteSpeed Cache',
			'AUTOPTIMIZE_PLUGIN_VERSION' => 'Autoptimize',
			'WPFC_WP_PLUGIN_DIR'         => 'WP Fastest Cache',
			'CE_VERSION'                 => 'Cache Enabler',
			'COMET_CACHE_PLUGIN_VERSION' => 'Comet Cache',
			'SG_CACHEPRESS_VERSION'      => 'SG Optimizer',
			'WP_OPTIMIZE_VERSION'        => 'WP-Optimize',
			'WPHB_VERSION'               => 'Hummingbird',
			'PERFMATTERS_VERSION'        => 'Perfmatters',
			'FLYINGPRESS_VERSION'        => 'FlyingPress',
			'SWIFT_PERFORMANCE_VERSION'  => 'Swift Performance',
			'BREEZE_VERSION'             => 'Breeze',
		);

		foreach ( $hosting_cache_indicators as $constant => $plugin_name ) {
			if ( defined( $constant ) ) {
				$detected_plugins[] = $plugin_name;
			}
		}

		// Check for common hosting providers with built-in caching
		$hosting_providers = array(
			'WP_ENGINE'                    => 'WP Engine',
			'KINSTA_CACHE_DIR'             => 'Kinsta',
			'PANTHEON_CACHE_DIR'           => 'Pantheon',
			'FLYWHEEL_PLUGIN_DIR'          => 'Flywheel',
			'SITEGROUND_OPTIMIZER_VERSION' => 'SiteGround',
		);

		foreach ( $hosting_providers as $constant => $provider_name ) {
			if ( defined( $constant ) ) {
				$detected_plugins[] = $provider_name . ' Cache';
			}
		}

		// Remove duplicates
		$detected_plugins = array_unique( $detected_plugins );

		return array(
			'active'  => ! empty( $detected_plugins ),
			'plugins' => $detected_plugins,
		);
	}
}
