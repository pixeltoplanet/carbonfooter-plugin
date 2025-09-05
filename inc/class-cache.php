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
   * Structured per-post cache payload format
   *
   * [
   *   'emissions'   => float|null,   // grams CO2e
   *   'page_size'   => int|null,     // bytes
   *   'updated_at'  => int,          // unix timestamp when measured
   *   'source'      => string,       // 'api' | 'meta' | 'manual' | 'background'
   *   'stale'       => bool          // true if should be refreshed
   * ]
   */
  
  /**
   * Build the cache key for a post_id.
   *
   * @param int $post_id
   * @return string
   */
  private function key_for(int $post_id): string
  {
    return Constants::CACHE_POST_KEY_PREFIX . $post_id;
  }

  /**
   * Get emissions data from cache.
   *
   * @param int $post_id Post ID.
   * @return mixed|false Cached data or false if not found.
   */
  public function get($post_id)
  {
    // Back-compat: return whatever is stored (structured array or legacy scalar)
    return wp_cache_get($this->key_for((int) $post_id), Constants::CACHE_GROUP);
  }

  /**
   * Get structured payload for a post.
   *
   * @param int $post_id
   * @return array|null Returns payload array or null if not found.
   */
  public function get_post_payload(int $post_id): ?array
  {
    $value = wp_cache_get($this->key_for($post_id), Constants::CACHE_GROUP);
    if ($value === false) {
      return null;
    }
    return is_array($value) ? $value : null;
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
    // Back-compat setter
    return wp_cache_set(
      $this->key_for((int) $post_id),
      $value,
      Constants::CACHE_GROUP,
      Constants::CACHE_PER_POST_TTL
    );
  }

  /**
   * Set structured payload for a post.
   *
   * @param int   $post_id
   * @param array $payload See class docblock for format.
   * @return bool
   */
  public function set_post_payload(int $post_id, array $payload): bool
  {
    return wp_cache_set(
      $this->key_for($post_id),
      $payload,
      Constants::CACHE_GROUP,
      Constants::CACHE_PER_POST_TTL
    );
  }

  /**
   * Delete emissions data from cache.
   *
   * @param int $post_id Post ID.
   * @return bool True on success, false on failure.
   */
  public function delete($post_id)
  {
    return wp_cache_delete($this->key_for((int) $post_id), Constants::CACHE_GROUP);
  }

  /**
   * Determine if a cached payload is stale based on timestamp and explicit flag.
   *
   * @param array|null $payload
   * @return bool True if payload is considered stale or missing.
   */
  public function is_stale(?array $payload): bool
  {
    if (!$payload) {
      return true;
    }
    if (!empty($payload['stale'])) {
      return true;
    }
    $updated = isset($payload['updated_at']) ? (int) $payload['updated_at'] : 0;
    if ($updated <= 0) {
      return true;
    }
    return (time() - $updated) >= Constants::CACHE_STALE_AFTER;
  }

  /**
   * Mark an existing payload as stale without deleting it.
   *
   * @param int $post_id
   * @return void
   */
  public function mark_stale(int $post_id): void
  {
    $payload = $this->get_post_payload($post_id);
    if (!$payload) {
      return;
    }
    $payload['stale'] = true;
    // Preserve original updated_at; only mark as stale
    $this->set_post_payload($post_id, $payload);
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
    // Also clear transient mirror for stats
    delete_transient(Constants::TRANSIENT_STATS_CACHE);

    // Common list caches
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':10', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':20', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_HEAVIEST_PAGES_KEY . ':50', Constants::CACHE_GROUP);
    wp_cache_delete(Constants::CACHE_UNTESTED_PAGES_KEY, Constants::CACHE_GROUP);
  }
}
