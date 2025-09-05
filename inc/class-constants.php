<?php

/**
 * Constants Class
 *
 * Centralizes all plugin constants and configuration values.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Constants
 *
 * Central registry of plugin configuration values, keys, and limits.
 * Documenting each group helps maintainers discover available flags and
 * prevents drift across subsystems.
 */
final class Constants
{

  /**
   * Plugin version
   */
  public const VERSION = '0.16.2';

  /**
   * Plugin text domain
   */
  public const TEXT_DOMAIN = 'carbonfooter';

  /**
   * Plugin slug
   */
  public const PLUGIN_SLUG = 'carbonfooter';

  /**
   * API endpoints
   */
  public const API_NAMESPACE = 'carbonfooter/v1';
  public const API_BASE_URL = 'https://carbonfooter.nl/api';

  /**
   * Database meta keys
   */
  public const META_EMISSIONS = '_carbon_emissions';
  public const META_PAGE_SIZE = '_carbon_page_size';
  public const META_RESOURCES = '_carbon_resources';
  public const META_EMISSIONS_UPDATED = '_carbon_emissions_updated';
  public const META_EMISSIONS_HISTORY = '_carbon_emissions_history';

  /**
   * Option keys
   */
  public const OPTION_WIDGET_BACKGROUND_COLOR = 'carbonfooter_widget_background_color';
  public const OPTION_WIDGET_TEXT_COLOR = 'carbonfooter_widget_text_color';
  public const OPTION_DISPLAY_SETTING = 'carbonfooter_display_setting';
  public const OPTION_WIDGET_STYLE = 'carbonfooter_widget_style';
  public const OPTION_GREEN_HOST = 'carbonfooter_greenhost';
  public const OPTION_DATA_COLLECTION_ENABLED = 'carbonfooter_data_collection_enabled';
  public const OPTION_SHOW_ATTRIBUTION = 'carbonfooter_show_attribution';

  /**
   * Transient keys
   */
  public const TRANSIENT_ACTIVATION_REDIRECT = 'carbonfooter_activation_redirect';
  public const TRANSIENT_STATS_CACHE = 'carbonfooter_stats_cache';

  /**
   * Cache keys
   */
  public const CACHE_GROUP = 'carbonfooter';
  public const CACHE_STATS_KEY = 'site_stats';
  public const CACHE_HEAVIEST_PAGES_KEY = 'heaviest_pages';
  public const CACHE_UNTESTED_PAGES_KEY = 'untested_pages';
  
  /**
   * Per-post emissions cache configuration
   *
   * CACHE_POST_KEY_PREFIX: Prefix for per-post emissions cache keys.
   * CACHE_PER_POST_TTL: Default TTL for per-post cache entries (24h) in seconds.
   * CACHE_STALE_AFTER: Threshold in seconds after which cached data is considered stale and should be refreshed in background.
   */
  public const CACHE_POST_KEY_PREFIX = 'carbonfooter_emissions_';
  public const CACHE_PER_POST_TTL = 86400; // 24 hours
  public const CACHE_STALE_AFTER = 86400; // 24 hours

  /**
   * AJAX actions
   */
  public const AJAX_MEASURE = 'carbonfooter_measure';
  public const AJAX_GET_STATS = 'carbonfooter_get_stats';
  public const AJAX_GET_HEAVIEST_PAGES = 'carbonfooter_get_heaviest_pages';
  public const AJAX_GET_UNTESTED_PAGES = 'carbonfooter_get_untested_pages';
  public const AJAX_SAVE_SETTINGS = 'carbonfooter_save_settings';
  public const AJAX_CLEAR_DATA = 'carbonfooter_clear_data';
  public const AJAX_EXPORT_DATA = 'carbonfooter_export_data';

  /**
   * Nonce actions
   */
  public const NONCE_ACTION = 'carbonfooter-nonce';
  public const REST_NONCE_ACTION = 'wp_rest';

  /**
   * File paths and URLs
   */
  public const LOG_FILENAME = 'carbonfooter-debug.log';
  public const ASSETS_BUILD_DIR = 'build';
  public const ASSETS_INDEX_FILE = 'index.js';
  public const ASSETS_STYLE_FILE = 'index.css';
  public const ASSETS_ASSET_FILE = 'index.asset.php';

  /**
   * Default values
   */
  public const DEFAULT_BACKGROUND_COLOR = '#000000';
  public const DEFAULT_TEXT_COLOR = '#FFFFFF';
  public const DEFAULT_DISPLAY_SETTING = 'auto';
  public const DEFAULT_WIDGET_STYLE = 'minimal';
  public const DEFAULT_DATA_COLLECTION_ENABLED = true;
  public const DEFAULT_SHOW_ATTRIBUTION = false;

  /**
   * Limits and constraints
   */
  public const MAX_HEAVIEST_PAGES_LIMIT = 50;
  public const MAX_UNTESTED_PAGES_LIMIT = 100;
  public const DEFAULT_HEAVIEST_PAGES_LIMIT = 10;
  public const DEFAULT_UNTESTED_PAGES_LIMIT = 20;
  public const CACHE_EXPIRATION_TIME = 3600; // 1 hour

  /**
   * Widget styles
   */
  public const WIDGET_STYLES = ['minimal', 'full', 'sticker'];

  /**
   * Display settings
   */
  public const DISPLAY_SETTINGS = ['auto', 'shortcode'];

  /**
   * Admin page hooks
   */
  public const ADMIN_PAGE_MAIN = 'toplevel_page_carbonfooter';
  public const ADMIN_PAGE_SETTINGS = 'carbonfooter_page_carbonfooter-settings';
  public const ADMIN_PAGE_RESULTS = 'carbonfooter_page_carbonfooter-results';

  /**
   * Menu positions
   */
  public const MENU_POSITION = 999;

  /**
   * Capabilities
   */
  public const REQUIRED_CAPABILITY = 'manage_options';

  /**
   * Initialize constants that depend on WordPress functions
   *
   * @return void
   */
  public static function init(): void
  {
    // Define plugin file constant if not already defined
    if (!defined('CARBONFOOTER_PLUGIN_FILE')) {
      define('CARBONFOOTER_PLUGIN_FILE', dirname(__DIR__) . '/carbonfooter.php');
    }

    // Basic constants (VERSION, PLUGIN_DIR, PLUGIN_URL) should already be defined in main file
    // This method can be used for any additional initialization if needed
  }

  /**
   * Get all meta keys as array
   *
   * @return array Meta keys
   */
  public static function get_meta_keys(): array
  {
    return [
      self::META_EMISSIONS,
      self::META_PAGE_SIZE,
      self::META_RESOURCES,
      self::META_EMISSIONS_UPDATED,
      self::META_EMISSIONS_HISTORY
    ];
  }

  /**
   * Get all option keys as array
   *
   * @return array Option keys
   */
  public static function get_option_keys(): array
  {
    return [
      self::OPTION_WIDGET_BACKGROUND_COLOR,
      self::OPTION_WIDGET_TEXT_COLOR,
      self::OPTION_DISPLAY_SETTING,
      self::OPTION_WIDGET_STYLE,
      self::OPTION_GREEN_HOST,
      self::OPTION_DATA_COLLECTION_ENABLED,
      self::OPTION_SHOW_ATTRIBUTION
    ];
  }

  /**
   * Get all AJAX actions as array
   *
   * @return array AJAX actions
   */
  public static function get_ajax_actions(): array
  {
    return [
      self::AJAX_MEASURE,
      self::AJAX_GET_STATS,
      self::AJAX_GET_HEAVIEST_PAGES,
      self::AJAX_GET_UNTESTED_PAGES,
      self::AJAX_SAVE_SETTINGS,
      self::AJAX_CLEAR_DATA,
      self::AJAX_EXPORT_DATA
    ];
  }

  /**
   * Get admin page hooks as array
   *
   * @return array Admin page hooks
   */
  public static function get_admin_page_hooks(): array
  {
    return [
      self::ADMIN_PAGE_MAIN,
      self::ADMIN_PAGE_SETTINGS,
      self::ADMIN_PAGE_RESULTS
    ];
  }
}
