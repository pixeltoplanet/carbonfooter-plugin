<?php

/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests from the admin interface.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * AjaxHandler
 *
 * Handles secured admin-ajax.php endpoints for measurement, stats retrieval,
 * settings changes, and data management (clear/export).
 *
 * Responsibilities:
 * - Verify nonces and capabilities for each action
 * - Sanitize and validate incoming POST params
 * - Delegate domain work to services and format JSON responses
 *
 * Security:
 * - Each endpoint calls `verify_nonce_and_permissions()` with the minimal
 *   required capability for the action
 * - All outputs are JSON and nonces are required on requests
 */
class AjaxHandler
{

  /**
   * Emissions handler instance
   *
   * @var Emissions
   */
  private Emissions $emissions_handler;

  /**
   * Cache manager instance
   *
   * @var Cache
   */
  private Cache $cache_manager;

  /**
   * AJAX nonce action
   *
   * @var string
   */
  private const NONCE_ACTION = Constants::NONCE_ACTION;

  /**
   * Constructor
   *
   * @param Emissions $emissions_handler Emissions handler instance
   * @param Cache $cache_manager Cache manager instance
   */
  public function __construct(Emissions $emissions_handler, Cache $cache_manager)
  {
    $this->emissions_handler = $emissions_handler;
    $this->cache_manager = $cache_manager;
  }

  /**
   * Register AJAX hooks.
   *
   * Attaches wp_ajax_* actions for measurement, stats, and settings/data ops.
   *
   * @return void
   */
  public function register_hooks(): void
  {
    // Measurement actions
    add_action('wp_ajax_' . Constants::AJAX_MEASURE, [$this, 'handle_measure_request']);

    // Statistics actions
    add_action('wp_ajax_' . Constants::AJAX_GET_STATS, [$this, 'handle_get_stats_request']);
    add_action('wp_ajax_' . Constants::AJAX_GET_HEAVIEST_PAGES, [$this, 'handle_get_heaviest_pages_request']);
    add_action('wp_ajax_' . Constants::AJAX_GET_UNTESTED_PAGES, [$this, 'handle_get_untested_pages_request']);

    // Settings actions
    add_action('wp_ajax_' . Constants::AJAX_SAVE_SETTINGS, [$this, 'handle_save_settings_request']);

    // Data management actions
    add_action('wp_ajax_' . Constants::AJAX_CLEAR_DATA, [$this, 'handle_clear_data_request']);
    add_action('wp_ajax_' . Constants::AJAX_EXPORT_DATA, [$this, 'handle_export_data_request']);
  }

  /**
   * Handle emissions measurement request.
   *
   * Structure:
   * - Capability: `edit_posts`
   * - Input: POST `post_id` (int)
   * - Delegates to Emissions->process_post()
   * - Returns: `{ emissions: float, formatted: string }` on success
   *
   * @return void
   */
  public function handle_measure_request(): void
  {
    Logger::info('AJAX measure request started');

    $this->verify_nonce_and_permissions('edit_posts');
    Logger::info('Nonce and permissions verified');

    $post_id = $this->get_sanitized_post_parameter('post_id', 'int');
    Logger::info('Post ID received: ' . $post_id);

    if (!$post_id) {
      Logger::error('Invalid post ID provided');
      $this->send_error_response(__('Invalid post ID', 'carbonfooter'));
      return;
    }

    Logger::info('Starting emissions processing for post: ' . $post_id);
    $result = $this->emissions_handler->process_post($post_id);
    Logger::info('Emissions processing result: ' . ($result ? $result : 'false'));

    if ($result) {
      $response_data = [
        'emissions' => $result,
        'formatted' => number_format($result, 2) . 'g CO2'
      ];
      Logger::info('Sending success response: ' . wp_json_encode($response_data));
      $this->send_success_response($response_data);
    } else {
      Logger::error('Emissions processing failed for post: ' . $post_id);
      $this->send_error_response(__('Failed to measure emissions', 'carbonfooter'));
    }
  }

  /**
   * Handle statistics request.
   *
   * Returns site-wide stats from Emissions->get_site_stats().
   *
   * @return void
   */
  public function handle_get_stats_request(): void
  {
    $this->verify_nonce_and_permissions();

    try {
      $stats = $this->emissions_handler->get_site_stats();
      $this->send_success_response($stats);
    } catch (\Exception $e) {
      Logger::log('Error getting stats: ' . $e->getMessage(), 'error');
      $this->send_error_response(__('Failed to retrieve statistics', 'carbonfooter'));
    }
  }

  /**
   * Handle heaviest pages request.
   *
   * Input: POST `limit` (int, default 10). Returns an array of pages ordered by
   * emissions descending.
   *
   * @return void
   */
  public function handle_get_heaviest_pages_request(): void
  {
    $this->verify_nonce_and_permissions();

    $limit = $this->get_sanitized_post_parameter('limit', 'int', 10);

    try {
      $pages = Database_Optimizer::get_heaviest_pages($limit);
      $this->send_success_response($pages);
    } catch (\Exception $e) {
      Logger::log('Error getting heaviest pages: ' . $e->getMessage(), 'error');
      $this->send_error_response(__('Failed to retrieve heaviest pages', 'carbonfooter'));
    }
  }

  /**
   * Handle untested pages request.
   *
   * Input: POST `limit` (int, default 20). Returns pages without measurements,
   * grouped by post type.
   *
   * @return void
   */
  public function handle_get_untested_pages_request(): void
  {
    $this->verify_nonce_and_permissions();

    $limit = $this->get_sanitized_post_parameter('limit', 'int', 20);

    try {
      $pages = Database_Optimizer::get_untested_pages($limit);
      $this->send_success_response($pages);
    } catch (\Exception $e) {
      Logger::log('Error getting untested pages: ' . $e->getMessage(), 'error');
      $this->send_error_response(__('Failed to retrieve untested pages', 'carbonfooter'));
    }
  }

  /**
   * Handle save settings request.
   *
   * Capability: `manage_options`.
   * Accepts color and appearance options; updates only provided keys and returns
   * the set of updated values.
   *
   * @return void
   */
  public function handle_save_settings_request(): void
  {
    $this->verify_nonce_and_permissions('manage_options');

    $background_color = $this->get_sanitized_post_parameter('background_color', 'hex_color');
    $text_color = $this->get_sanitized_post_parameter('text_color', 'hex_color');

    $updated_settings = [];

    if ($background_color) {
      update_option(Constants::OPTION_WIDGET_BACKGROUND_COLOR, $background_color);
      $updated_settings['background_color'] = $background_color;
    }

    if ($text_color) {
      update_option(Constants::OPTION_WIDGET_TEXT_COLOR, $text_color);
      $updated_settings['text_color'] = $text_color;
    }

    if (empty($updated_settings)) {
      $this->send_error_response(__('No valid settings provided', 'carbonfooter'));
    }

    Logger::log('Settings updated via AJAX', $updated_settings);
    $this->send_success_response($updated_settings);
  }

  /**
   * Handle clear data request.
   *
   * Capability: `manage_options`.
   * Clears plugin post meta, transients, cache, and green host option.
   * Returns counts for transparency.
   *
   * @return void
   */
  public function handle_clear_data_request(): void
  {
    $this->verify_nonce_and_permissions('manage_options');

    global $wpdb;

    try {
      // Clear all CarbonFooter-related post meta
      $meta_keys_to_delete = Constants::get_meta_keys();

      $deleted_count = 0;
      foreach ($meta_keys_to_delete as $meta_key) {
        $result = $wpdb->delete(
          $wpdb->postmeta,
          ['meta_key' => $meta_key],
          ['%s']
        );
        if ($result !== false) {
          $deleted_count += $result;
        }
      }

      // Clear cache
      $this->cache_manager->clear_all();

      // Clear WordPress transients
      $cache_keys_cleared = $this->clear_carbonfooter_transients();

      // Clear green host status
      delete_option(Constants::OPTION_GREEN_HOST);

      Logger::log('All CarbonFooter data cleared by user', [
        'deleted_meta_count' => $deleted_count,
        'cache_keys_cleared' => $cache_keys_cleared,
        'user_id' => get_current_user_id()
      ]);

      $this->send_success_response([
        'message' => sprintf(
          /* translators: %d is the number of deleted data entries. */
          __('Successfully cleared %d data entries and cache. All emissions data has been removed.', 'carbonfooter'),
          $deleted_count
        ),
        'deleted_count' => $deleted_count,
        'cache_cleared' => $cache_keys_cleared
      ]);
    } catch (\Exception $e) {
      Logger::log('Error clearing data: ' . $e->getMessage(), 'error');
      $this->send_error_response(__('Failed to clear data', 'carbonfooter'));
    }
  }

  /**
   * Handle export data request.
   *
   * Capability: `manage_options`.
   * Gathers historical emissions and current values for posts with history,
   * returning JSON data and a suggested filename.
   *
   * @return void
   */
  public function handle_export_data_request(): void
  {
    $this->verify_nonce_and_permissions('manage_options');

    global $wpdb;

    try {
      // Get all posts with emissions history data and current emissions
      $results = $wpdb->get_results($wpdb->prepare("
				SELECT
					p.ID,
					p.post_title,
					pm_history.meta_value as history,
					pm_current.meta_value as current_emissions
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm_history ON p.ID = pm_history.post_id
				LEFT JOIN {$wpdb->postmeta} pm_current ON p.ID = pm_current.post_id AND pm_current.meta_key = %s
				WHERE pm_history.meta_key = %s
				AND pm_history.meta_value != ''
				ORDER BY p.post_title ASC
			", Constants::META_EMISSIONS, Constants::META_EMISSIONS_HISTORY));

      if (empty($results)) {
        $this->send_error_response(__('No historical emissions data found to export.', 'carbonfooter'));
      }

      $export_data = $this->prepare_export_data($results);
      $filename = $this->generate_export_filename();

      Logger::log('Emissions data exported by user', [
        'exported_posts' => count($export_data),
        'user_id' => get_current_user_id(),
        'filename' => $filename
      ]);

      $this->send_success_response([
        'data' => $export_data,
        'filename' => $filename,
        'message' => sprintf(
          /* translators: %d is the number of exported posts. */
          __('Successfully exported %d posts with historical emissions data.', 'carbonfooter'),
          count($export_data)
        )
      ]);
    } catch (\Exception $e) {
      Logger::log('Error exporting data: ' . $e->getMessage(), 'error');
      $this->send_error_response(__('Failed to export data', 'carbonfooter'));
    }
  }

  /**
   * Verify nonce and user permissions.
   *
   * Security:
   * - Verifies AJAX nonce in `$_POST['nonce']`
   * - Ensures current user has required capability
   *
   * @param string $capability Required capability (default 'manage_options')
   * @return void
   */
  private function verify_nonce_and_permissions(string $capability = 'manage_options'): void
  {
    if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
      $this->send_error_response(__('Invalid security token', 'carbonfooter'), 403);
    }

    if (!current_user_can($capability)) {
      $this->send_error_response(__('Insufficient permissions', 'carbonfooter'), 403);
    }
  }

  /**
   * Get sanitized POST parameter.
   *
   * Supported types: 'int', 'string', 'hex_color'. Returns default when
   * key is missing or fails validation.
   *
   * @param string $key     Parameter key
   * @param string $type    Parameter type (int|string|hex_color)
   * @param mixed  $default Default value
   * @return mixed Sanitized value or default
   */
  private function get_sanitized_post_parameter(string $key, string $type = 'string', $default = null)
  {
    if (!isset($_POST[$key])) {
      return $default;
    }

    $value = $_POST[$key];

    switch ($type) {
      case 'int':
        return intval($value);

      case 'hex_color':
        return sanitize_hex_color($value) ?: $default;

      case 'string':
      default:
        return sanitize_text_field($value);
    }
  }

  /**
   * Clear CarbonFooter-related transients.
   *
   * Scans options table for keys like `_transient_carbonfooter_%` and deletes
   * the corresponding transients.
   *
   * @return int Number of transients cleared
   */
  private function clear_carbonfooter_transients(): int
  {
    global $wpdb;

    $cache_keys = $wpdb->get_col($wpdb->prepare("
			SELECT option_name
			FROM {$wpdb->options}
			WHERE option_name LIKE %s
		", '_transient_carbonfooter_%'));

    $cleared_count = 0;
    foreach ($cache_keys as $cache_key) {
      $key = str_replace('_transient_', '', $cache_key);
      if (delete_transient($key)) {
        $cleared_count++;
      }
    }

    return $cleared_count;
  }

  /**
   * Prepare export data from database results.
   *
   * Normalizes history to an array of `{ date, value }` entries and includes
   * current emissions when available.
   *
   * @param array $results Database results
   * @return array Formatted export data
   */
  private function prepare_export_data(array $results): array
  {
    $export_data = [];

    foreach ($results as $result) {
      $history = maybe_unserialize($result->history);

      if (!is_array($history)) {
        continue;
      }

      $formatted_history = [];
      foreach ($history as $entry) {
        if (isset($entry['date']) && isset($entry['value'])) {
          $formatted_history[] = [
            'date' => $entry['date'],
            'value' => (float) $entry['value']
          ];
        }
      }

      $export_data[] = [
        'ID' => $result->ID,
        'post_title' => $result->post_title,
        'emissions' => $result->current_emissions ? (float) $result->current_emissions : null,
        'history' => $formatted_history
      ];
    }

    return $export_data;
  }

  /**
   * Generate export filename.
   *
   * Uses current date and site name to produce a stable, shareable filename.
   *
   * @return string Generated filename
   */
  private function generate_export_filename(): string
  {
    $site_name = sanitize_title(get_bloginfo('name'));
    return date('Y-m-d') . '-carbon-emissions-' . $site_name . '.json';
  }

  /**
   * Send success response.
   *
   * Wraps `wp_send_json_success` for consistent API surface.
   *
   * @param mixed $data Response data
   * @return void
   */
  private function send_success_response($data): void
  {
    wp_send_json_success($data);
  }

  /**
   * Send error response.
   *
   * Sets status header and returns a standardized error payload.
   *
   * @param string $message     Error message
   * @param int    $status_code HTTP status code
   * @return void
   */
  private function send_error_response(string $message, int $status_code = 400): void
  {
    status_header($status_code);
    wp_send_json_error($message);
  }
}
