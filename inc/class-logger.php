<?php

/**
 * Logger Class
 *
 * Handles logging functionality for the CarbonFooter plugin.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger
 *
 * Lightweight file-based logging with leveled messages. Intended primarily
 * for development and operational diagnostics when WP_DEBUG is enabled,
 * but always logs errors.
 *
 * Responsibilities:
 * - Format log entries consistently
 * - Write to a plugin-scoped log file
 * - Provide convenience methods per level
 */
class Logger {



	/**
	 * Log levels
	 */
	public const LEVEL_INFO    = 'info';
	public const LEVEL_ERROR   = 'error';
	public const LEVEL_WARNING = 'warning';
	public const LEVEL_DEBUG   = 'debug';

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private static string $log_file_path;

	/**
	 * Initialize logger.
	 *
	 * Ensures the log directory exists and resolves the file path.
	 *
	 * @return void
	 */
	public static function init(): void {
		$uploads = wp_get_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'carbonfooter-logs/';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		self::$log_file_path = $dir . 'carbonfooter-debug.log';
	}

	/**
	 * Log a message.
	 *
	 * Behavior:
	 * - Always logs errors; other levels honor WP_DEBUG.
	 * - Serializes arrays/objects as JSON for readability.
	 *
	 * @param string       $message Log message
	 * @param string|array $data    Additional context to log
	 * @param string       $level   Log level (info|error|warning|debug)
	 * @return void
	 */
	public static function log( string $message, $data = '', string $level = self::LEVEL_INFO ): void {
		// Only log if WP_DEBUG is enabled, unless it's an error
		if ( ! self::should_log( $level ) ) {
			return;
		}

		$formatted_message = self::format_log_entry( $message, $data, $level );
		self::write_to_log_file( $formatted_message );
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Error message
	 * @param array  $context Additional context
	 * @return void
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( $message, $context, self::LEVEL_ERROR );
	}

	/**
	 * Log a warning message
	 *
	 * @param string $message Warning message
	 * @param array  $context Additional context
	 * @return void
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( $message, $context, self::LEVEL_WARNING );
	}

	/**
	 * Log a debug message
	 *
	 * @param string $message Debug message
	 * @param array  $context Additional context
	 * @return void
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( $message, $context, self::LEVEL_DEBUG );
	}

	/**
	 * Log an info message
	 *
	 * @param string $message Info message
	 * @param array  $context Additional context
	 * @return void
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( $message, $context, self::LEVEL_INFO );
	}

	/**
	 * Determine if we should log based on level and settings.
	 *
	 * @param string $level Log level
	 * @return bool True if should log
	 */
	private static function should_log( string $level ): bool {
		// Always log errors
		if ( $level === self::LEVEL_ERROR ) {
			return true;
		}

		// For other levels, check if WP_DEBUG is enabled
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Format log entry.
	 *
	 * @param string $message Log message
	 * @param mixed  $data    Additional data
	 * @param string $level   Log level
	 * @return string Formatted log entry
	 */
	private static function format_log_entry( string $message, $data, string $level ): string {
		$timestamp       = gmdate( 'Y-m-d H:i:s' );
		$formatted_level = strtoupper( $level );

		$log_entry = "[CarbonFooter][{$formatted_level}][{$timestamp}] {$message}";

		// Add data if provided
		if ( ! empty( $data ) ) {
			if ( is_array( $data ) || is_object( $data ) ) {
				$log_entry .= ' | Data: ' . wp_json_encode( $data );
			} else {
				$log_entry .= ' | ' . $data;
			}
		}

		return $log_entry . "\n";
	}

	/**
	 * Write to log file.
	 *
	 * @param string $formatted_message Formatted log message
	 * @return void
	 */
	private static function write_to_log_file( string $formatted_message ): void {
		if ( ! isset( self::$log_file_path ) ) {
			self::init();
		}

		// Only write non-error logs when WP_DEBUG is enabled; errors always log
		if ( function_exists( 'wp_debug_log' ) ) {
			wp_debug_log( $formatted_message );
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Local file logging when wp_debug_log is unavailable
		error_log( $formatted_message, 3, self::$log_file_path );
	}

	/**
	 * Ensure log directory exists.
	 *
	 * Creates the logs directory if it does not exist.
	 *
	 * @return void
	 */
	private static function ensure_log_directory_exists(): void {}

	/**
	 * Clear log file.
	 *
	 * @return bool True if cleared successfully
	 */
	public static function clear_log_file(): bool {
		if ( ! isset( self::$log_file_path ) ) {
			self::init();
		}

		if ( file_exists( self::$log_file_path ) ) {
			return wp_delete_file( self::$log_file_path );
		}

		return true;
	}

	/**
	 * Get log file contents.
	 *
	 * @param int $lines Number of lines to retrieve (0 for all)
	 * @return string Log file contents
	 */
	public static function get_log_contents( int $lines = 0 ): string {
		if ( ! isset( self::$log_file_path ) ) {
			self::init();
		}

		if ( ! file_exists( self::$log_file_path ) ) {
			return '';
		}

		if ( $lines === 0 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local plugin log file
			$contents = file_get_contents( self::$log_file_path );
			return is_string( $contents ) ? $contents : '';
		}

		// Get last N lines
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_file -- Reading local plugin log file lines
		$file_lines  = file( self::$log_file_path );
		$total_lines = count( $file_lines );
		$start_line  = max( 0, $total_lines - $lines );

		return implode( '', array_slice( $file_lines, $start_line ) );
	}

	/**
	 * Get log file size.
	 *
	 * @return int File size in bytes
	 */
	public static function get_log_file_size(): int {
		if ( ! isset( self::$log_file_path ) ) {
			self::init();
		}

		if ( ! file_exists( self::$log_file_path ) ) {
			return 0;
		}

		return filesize( self::$log_file_path );
	}
}
