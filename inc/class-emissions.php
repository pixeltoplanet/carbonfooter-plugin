<?php

/**
 * Emissions functionality.
 *
 * This file handles the core emissions calculations and data management.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

// Import helper utilities
use CarbonfooterPlugin\Helpers;

/**
 * Emissions
 *
 * Core domain service for retrieving, computing, and storing emissions data
 * for posts and aggregating site-level metrics.
 *
 * Responsibilities:
 * - Process a post via the Carbonfooter API (or mock in local)
 * - Persist emissions, page size, resources, and history
 * - Maintain green host status and invalidate caches
 * - Provide cached stats and listings via Database_Optimizer
 */
class Emissions
{

  /**
   * Cache instance.
   *
   * @var Cache
   */
  private $cache;

  /**
   * Constructor.
   */
  public function __construct()
  {
    $this->cache = new Cache();
  }

  /**
   * Process a single post to get its emissions data.
   *
   * Structure:
   * - Validates data collection setting
   * - Builds and performs API call (HTTPS enforced)
   * - Stores results and updates caches/history/options
   *
   * @param int $post_id Post ID.
   * @return float|false Emissions value or false on failure.
   */
  public function process_post($post_id)
  {
    try {
      // Check if data collection is enabled
      if (!get_option(Constants::OPTION_DATA_COLLECTION_ENABLED, Constants::DEFAULT_DATA_COLLECTION_ENABLED)) {
        throw new \Exception(__('Data collection is disabled in privacy settings', 'carbonfooter'));
      }

      // Get post URL
      $url = get_permalink($post_id);
      if (!$url) {
        throw new \Exception(__('Invalid post URL', 'carbonfooter'));
      }

      // Ensure using HTTPS
      $url = set_url_scheme($url, 'https');

      // Get emissions data from API
      $data = $this->get_emissions_from_api($url, $post_id);
      if (!$data) {
        return false;
      }

      // Store the data
      $this->store_emissions_data($post_id, $data);

      return $data['emissions'];
    } catch (\Exception $e) {
      Logger::error("Error processing post $post_id: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get emissions data for a post.
   *
   * @param int $post_id Post ID.
   * @return float|false Emissions value or false if not found.
   */
  public function get_post_emissions($post_id)
  {
    $post_id = (int) $post_id;
    // 1) Try structured cache first
    $payload = $this->cache->get_post_payload($post_id);
    if (is_array($payload) && isset($payload['emissions'])) {
      $stale = $this->cache->is_stale($payload);
      Logger::info('Per-post cache hit', [
        'post_id' => $post_id,
        'stale' => $stale,
        'source' => $payload['source'] ?? 'cache'
      ]);
      // If stale, background processor will schedule refresh; still return for fast path
      return $payload['emissions'];
    }

    // 2) Fallback to DB-optimized path to get value quickly
    $value = Database_Optimizer::get_post_emissions($post_id);
    if ($value !== false && $value !== null) {
      // Build payload from meta (populate page_size and updated_at if available)
      $payload_from_meta = $this->build_payload_from_meta($post_id, (float) $value);
      $this->cache->set_post_payload($post_id, $payload_from_meta);
      Logger::info('Per-post cache miss, DB optimizer hit', ['post_id' => $post_id]);
      return (float) $value;
    }

    // 3) Fallback directly to post meta if Database_Optimizer had nothing
    $meta_val = get_post_meta($post_id, Constants::META_EMISSIONS, true);
    if ($meta_val !== '' && $meta_val !== null) {
      $payload_from_meta = $this->build_payload_from_meta($post_id, (float) $meta_val);
      $this->cache->set_post_payload($post_id, $payload_from_meta);
      Logger::info('Per-post cache miss, meta hit', ['post_id' => $post_id]);
      return (float) $meta_val;
    }

    Logger::info('Per-post cache miss, no data found', ['post_id' => $post_id]);
    return false;
  }

  /**
   * Get structured per-post emissions payload (cache-first, fallback to meta/optimizer).
   *
   * @param int $post_id
   * @return array|null Payload or null if not found.
   */
  public function get_post_payload(int $post_id): ?array
  {
    $payload = $this->cache->get_post_payload($post_id);
    if (is_array($payload)) {
      Logger::info('Payload cache hit', [
        'post_id' => $post_id,
        'stale' => $this->cache->is_stale($payload)
      ]);
      return $payload;
    }

    $value = Database_Optimizer::get_post_emissions($post_id);
    if ($value !== false && $value !== null) {
      $payload_from_meta = $this->build_payload_from_meta($post_id, (float) $value);
      $this->cache->set_post_payload($post_id, $payload_from_meta);
      Logger::info('Payload cache miss, DB optimizer hit', ['post_id' => $post_id]);
      return $payload_from_meta;
    }

    $meta_val = get_post_meta($post_id, Constants::META_EMISSIONS, true);
    if ($meta_val !== '' && $meta_val !== null) {
      $payload_from_meta = $this->build_payload_from_meta($post_id, (float) $meta_val);
      $this->cache->set_post_payload($post_id, $payload_from_meta);
      Logger::info('Payload cache miss, meta hit', ['post_id' => $post_id]);
      return $payload_from_meta;
    }

    Logger::info('Payload cache miss, no data found', ['post_id' => $post_id]);
    return null;
  }

  /**
   * Get average emissions for the site.
   *
   * @return float Average emissions value.
   */
  public function get_average_emissions()
  {
    $stats = $this->get_site_stats_cached();
    return $stats['average_emissions'];
  }

  /**
   * Get total number of posts with emissions data.
   *
   * @return int Number of posts with emissions data.
   */
  public function get_total_measured_posts()
  {
    $stats = $this->get_site_stats_cached();
    return $stats['total_measured'];
  }

  /**
   * Public accessor for site stats (cached).
   *
   * @return array Site stats from Database_Optimizer (may be cached)
   */
  public function get_site_stats(): array
  {
    return $this->get_site_stats_cached();
  }

  /**
   * Get resource data for a post.
   *
   * @param int $post_id Post ID.
   * @return array|false Resource data or false if not found.
   */
  public function get_post_resources($post_id)
  {
    $resources = get_post_meta($post_id, '_carbon_resources', true);
    return !empty($resources) ? $resources : false;
  }

  /**
   * Get aggregated resource statistics across all posts.
   *
   * @return array Aggregated resource statistics.
   */
  public function get_site_resource_stats()
  {
    return Database_Optimizer::get_site_resource_stats();
  }

  /**
   * Get the heaviest pages based on emissions.
   *
   * @param int $limit Number of pages to return (default 10).
   * @return array Array of pages with emissions data.
   */
  public function get_heaviest_pages($limit = 10)
  {
    $cache_key = Constants::CACHE_HEAVIEST_PAGES_KEY . ':' . intval($limit);
    $cached = wp_cache_get($cache_key, Constants::CACHE_GROUP);
    if ($cached !== false) {
      return $cached;
    }
    $pages = Database_Optimizer::get_heaviest_pages($limit);
    wp_cache_set($cache_key, $pages, Constants::CACHE_GROUP, Constants::CACHE_EXPIRATION_TIME);
    return $pages;
  }

  /**
   * Get untested pages grouped by post type.
   *
   * @return array Array of untested pages grouped by post type.
   */
  public function get_untested_pages()
  {
    $cache_key = Constants::CACHE_UNTESTED_PAGES_KEY;
    $cached = wp_cache_get($cache_key, Constants::CACHE_GROUP);
    if ($cached !== false) {
      return $cached;
    }
    $pages = Database_Optimizer::get_untested_pages();
    wp_cache_set($cache_key, $pages, Constants::CACHE_GROUP, Constants::CACHE_EXPIRATION_TIME);
    return $pages;
  }

  /**
   * Format bytes to human readable format.
   *
   * @param int $bytes Number of bytes.
   * @return string Formatted size.
   */
  public function format_bytes($bytes)
  {
    if ($bytes >= 1024 * 1024 * 1024) {
      return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    } elseif ($bytes >= 1024 * 1024) {
      return round($bytes / (1024 * 1024), 2) . ' MB';
    } elseif ($bytes >= 1024) {
      return round($bytes / 1024, 2) . ' KB';
    } else {
      return $bytes . ' B';
    }
  }

  /**
   * Get emissions data from the API.
   *
   * @param string $url     URL to check.
   * @param int    $post_id Post ID.
   * @return array|false API response data or false on failure.
   * @throws \Exception If API request fails.
   */
  private function get_emissions_from_api($url, $post_id)
  {
    // Check if we're in a local environment
    if ($this->is_local_environment()) {
      return $this->get_mock_emissions_data();
    }

    // Make API request
    $api_url = add_query_arg([
      'target' => urlencode($url),
      'post_id' => $post_id,
      'site_url' => get_site_url(),
      'plugin_version' => CARBONFOOTER_VERSION,
      't' => time()
    ], Constants::API_BASE_URL);

    $response = wp_remote_get($api_url, [
      'timeout' => 60,
      'user-agent' => 'WordPress/CarbonFooter-Plugin/' . CARBONFOOTER_VERSION,
      'headers' => [
        'Accept' => 'application/json',
        'X-WordPress-Site' => get_site_url()
      ]
    ]);

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error("API Error: $error_message");
      /* translators: %s: API error message returned by the remote request */
      $error_text = __('API Error: %s', 'carbonfooter');
      throw new \Exception(sprintf(esc_html($error_text), esc_html($error_message)));
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      $error_message = wp_remote_retrieve_response_message($response);
      Logger::error("API Error: Status $response_code - $error_message");
      /* translators: %s: HTTP response status code from the API */
      $error_text = __('API Error: Status %s', 'carbonfooter');
      throw new \Exception(sprintf(esc_html($error_text), esc_html($response_code)));
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
      $error_text = __('Empty API response', 'carbonfooter');
      throw new \Exception(esc_html($error_text));
    }

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $json_error = json_last_error_msg();
      Logger::error("JSON Error: $json_error");
      /* translators: %s: JSON decoding error message */
      $error_text = __('Invalid JSON response: %s', 'carbonfooter');
      throw new \Exception(sprintf(esc_html($error_text), esc_html($json_error)));
    }

    if (!isset($data['emissions']['emissionsPerVisit'])) {
      $error_text = __('No emissions data found in response', 'carbonfooter');
      throw new \Exception(esc_html($error_text));
    }

    // Log successful API response
    Logger::info('API Response:', [
      'url' => $url,
      'emissions' => $data['emissions']['emissionsPerVisit'],
      'isGreenHost' => $data['emissions']['isGreenHost'] ?? false
    ]);

    return [
      'emissions' => (float) $data['emissions']['emissionsPerVisit'],
      'page_size' => isset($data['metrics']['totalByteWeight']['numericValue'])
        ? (float) $data['metrics']['totalByteWeight']['numericValue']
        : null,
      'is_green_host' => isset($data['emissions']['isGreenHost'])
        ? (bool) $data['emissions']['isGreenHost']
        : false,
      'resources' => isset($data['resourceData']) ? $data['resourceData'] : array(),
      'timestamp' => current_time('mysql')
    ];
  }

  /**
   * Store emissions data for a post.
   *
   * Side effects:
   * - Updates post meta (emissions, page size, resources, last updated)
   * - Updates green host option
   * - Warms and invalidates caches/transients
   * - Appends to emissions history (max 12 entries)
   *
   * @param int   $post_id Post ID.
   * @param array $data    Emissions data.
   */
  private function store_emissions_data($post_id, $data)
  {
    // Log the data being stored
    Logger::info('Storing emissions data:', [
      'post_id' => $post_id,
      'data' => $data,
      'is_green_host' => $data['is_green_host']
    ]);

    // Store emissions value
    update_post_meta($post_id, Constants::META_EMISSIONS, $data['emissions']);

    // Store page size if available
    if (isset($data['page_size'])) {
      update_post_meta($post_id, Constants::META_PAGE_SIZE, $data['page_size']);
    }

    // Store resource data if available
    if (isset($data['resources']) && !empty($data['resources'])) {
      update_post_meta($post_id, Constants::META_RESOURCES, $data['resources']);
    }

    // Always store green host status and log it
    $previous_status = get_option(Constants::OPTION_GREEN_HOST);
    update_option(Constants::OPTION_GREEN_HOST, $data['is_green_host']);

    // Log green host status change
    Logger::info('Green host status:', [
      'previous' => $previous_status,
      'new' => $data['is_green_host'],
      'changed' => $previous_status !== $data['is_green_host']
    ]);

    // Store update time
    update_post_meta($post_id, Constants::META_EMISSIONS_UPDATED, current_time('mysql'));

    // Update per-post cache with structured payload
    $payload = [
      'emissions' => (float) $data['emissions'],
      'page_size' => isset($data['page_size']) ? (float) $data['page_size'] : null,
      'updated_at' => time(),
      'source' => 'api',
      'stale' => false,
    ];
    $this->cache->set_post_payload((int) $post_id, $payload);

    // Invalidate optimized cache
    Database_Optimizer::invalidate_post_cache($post_id);

    // Invalidate site-level caches (stats and lists)
    wp_cache_delete(Constants::CACHE_STATS_KEY, Constants::CACHE_GROUP);
    delete_transient(Constants::TRANSIENT_STATS_CACHE);
    // Common list caches (default keys/limits)
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':10', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_UNTESTED_PAGES_KEY, Constants::CACHE_GROUP);

    // Warm site stats object cache from DB after clearing caches
    // This keeps admin and frontend views responsive after updates
    $this->get_site_stats_cached();

    // Store in history
    $this->update_emissions_history($post_id, $data['emissions']);
  }

  /**
   * Build structured payload from meta and an emissions value.
   *
   * @param int   $post_id
   * @param float $emissions
   * @return array
   */
  private function build_payload_from_meta(int $post_id, float $emissions): array
  {
    $page_size = get_post_meta($post_id, Constants::META_PAGE_SIZE, true);
    $updated_mysql = get_post_meta($post_id, Constants::META_EMISSIONS_UPDATED, true);
    $updated_ts = $updated_mysql ? strtotime($updated_mysql) : time();

    return [
      'emissions' => (float) $emissions,
      'page_size' => ($page_size !== '' && $page_size !== null) ? (float) $page_size : null,
      'updated_at' => $updated_ts ?: time(),
      'source' => 'meta',
      'stale' => false,
    ];
  }

  /**
   * Get cached site stats (object cache + transient fallback).
   *
   * Warms object cache from transient for faster subsequent reads.
   *
   * @return array Stats array
   */
  private function get_site_stats_cached(): array
  {
    $cached = wp_cache_get(Constants::CACHE_STATS_KEY, Constants::CACHE_GROUP);
    if ($cached !== false && is_array($cached)) {
      return $cached;
    }

    $transient = get_transient(Constants::TRANSIENT_STATS_CACHE);
    if ($transient !== false && is_array($transient)) {
      // Warm object cache for faster subsequent reads
      wp_cache_set(Constants::CACHE_STATS_KEY, $transient, Constants::CACHE_GROUP, Constants::CACHE_EXPIRATION_TIME);
      return $transient;
    }

    $stats = Database_Optimizer::get_site_stats();
    wp_cache_set(Constants::CACHE_STATS_KEY, $stats, Constants::CACHE_GROUP, Constants::CACHE_EXPIRATION_TIME);
    set_transient(Constants::TRANSIENT_STATS_CACHE, $stats, Constants::CACHE_EXPIRATION_TIME);
    return $stats;
  }

  /**
   * Update emissions history for a post.
   *
   * Keeps a rolling window of the last 12 entries.
   *
   * @param int   $post_id Post ID.
   * @param float $value   Emissions value.
   */
  private function update_emissions_history($post_id, $value)
  {
    $history = get_post_meta($post_id, Constants::META_EMISSIONS_HISTORY, true);
    if (!is_array($history)) {
      $history = [];
    }

    // Add new record
    $history[] = [
      'date' => current_time('mysql'),
      'value' => $value
    ];

    // Keep only the last 12 records
    if (count($history) > 12) {
      $history = array_slice($history, -12);
    }

    update_post_meta($post_id, Constants::META_EMISSIONS_HISTORY, $history);
  }

  /**
   * Check if we're in a local environment.
   *
   * @return bool True if local environment.
   */
  private function is_local_environment()
  {
    return Helpers::is_local_environment();
  }

  /**
   * Get mock emissions data for local testing.
   *
   * @return array Mock emissions data.
   */
  private function get_mock_emissions_data()
  {
    return Helpers::generate_mock_data('emissions');
  }
}
