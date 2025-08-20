<?php

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Cache
 *
 * Thin wrapper around WordPress object cache for plugin-specific keys.
 * Provides helpers for common cache operations used by the emissions flow.
 *
 * Responsibilities:
 * - Read/write/delete per-post emissions cache entries
 * - Clear coarse-grained caches for stats and listings
 */
class Cache
{
  /**
   * Cache duration in seconds (1 hour).
   *
   * @var int
   */
  private $cache_duration = 3600; // 1 hour

  /**
   * Get emissions data from cache.
   *
   * @param int $post_id Post ID.
   * @return mixed|false Cached data or false if not found.
   */
  public function get($post_id)
  {
    $cache_key = 'carbonfooter_emissions_' . $post_id;
    return wp_cache_get($cache_key, Constants::CACHE_GROUP);
  }

  /**
   * Set emissions data in cache.
   *
   * @param int   $post_id Post ID.
   * @param mixed $value   Value to cache.
   * @return bool True on success, false on failure.
   */
  public function set($post_id, $value)
  {
    $cache_key = 'carbonfooter_emissions_' . $post_id;
    return wp_cache_set($cache_key, $value, Constants::CACHE_GROUP, $this->cache_duration);
  }

  /**
   * Delete emissions data from cache.
   *
   * @param int $post_id Post ID.
   * @return bool True on success, false on failure.
   */
  public function delete($post_id)
  {
    $cache_key = 'carbonfooter_emissions_' . $post_id;
    return wp_cache_delete($cache_key, Constants::CACHE_GROUP);
  }

  /**
   * Clear plugin-level caches.
   *
   * Removes common stats and listing caches so views can update promptly after
   * measurements or data changes.
   *
   * @return void
   */
  public function clear_all()
  {
    // Site stats
    wp_cache_delete(Constants::CACHE_STATS_KEY, Constants::CACHE_GROUP);

    // Common list caches
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':10', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':20', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':50', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_UNTESTED_PAGES_KEY, Constants::CACHE_GROUP);
  }
}
