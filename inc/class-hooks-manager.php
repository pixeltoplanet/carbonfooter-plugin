<?php

/**
 * Hooks Manager Class
 *
 * Centralizes hook registration for all plugin components.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * HooksManager
 *
 * Centralizes all WordPress hook registration across plugin components.
 *
 * Responsibilities:
 * - Register hooks exposed by individual handlers (admin, ajax, rest)
 * - Register general-purpose plugin hooks not tied to a single handler
 *
 * Notes:
 * - Keeps plugin initialization deterministic and testable
 */
class HooksManager
{

  /**
   * Handler instances
   *
   * @var array
   */
  private array $handlers;

  /**
   * Constructor
   *
   * @param array $handlers Array of handler instances
   */
  public function __construct(array $handlers)
  {
    $this->handlers = $handlers;
  }

  /**
   * Register all hooks for all handlers.
   *
   * Structure:
   * - First, register per-handler hooks
   * - Then, register general hooks common to the plugin
   *
   * @return void
   */
  public function register_all_hooks(): void
  {
    $this->register_handler_hooks();
    $this->register_general_hooks();
  }

  /**
   * Register hooks for all handler instances.
   *
   * Iterates over provided handler objects and invokes their `register_hooks()`
   * method if present, allowing each handler to install its own hooks.
   *
   * @return void
   */
  private function register_handler_hooks(): void
  {
    foreach ($this->handlers as $handler_name => $handler) {
      if (method_exists($handler, 'register_hooks')) {
        $handler->register_hooks();
        Logger::log("Registered hooks for {$handler_name} handler");
      }
    }
  }

  /**
   * Register general plugin hooks that don't belong to specific handlers.
   *
   * Currently installs the URL check handler used to trigger manual
   * measurements from the admin interface.
   *
   * @return void
   */
  private function register_general_hooks(): void
  {
    // URL check hook for manual measurements
    add_action('admin_init', [$this, 'handle_url_check']);

    Logger::log('Registered general plugin hooks');
  }

  /**
   * Handle URL check for manual measurements.
   *
   * Why:
   * - Enables manual measurement triggers via admin URL parameters
   *   (e.g., `?cf-action=measure&post=123`).
   *
   * Security:
   * - Relies on admin context (hooked into `admin_init`) and current user
   *   permissions enforced downstream in Emissions processing when required.
   *
   * @return void
   */
  public function handle_url_check(): void
  {
    if (!isset($_REQUEST['cf-action'])) {
      return;
    }

    if ($_REQUEST['cf-action'] === 'measure' && isset($_REQUEST['post'])) {
      $post_id = intval($_REQUEST['post']);

      // Security: require nonce and capability, and only allow from admin
      if (!is_admin()) {
        return;
      }

      // Verify nonce from URL `_wpnonce` tied to action + post
      $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field((string) $_REQUEST['_wpnonce']) : '';
      if (!$nonce || !wp_verify_nonce($nonce, 'carbonfooter-measure-' . $post_id)) {
        Logger::warning('Blocked manual measure: invalid nonce', ['post_id' => $post_id]);
        return;
      }

      // Check capability against the specific post
      if (!current_user_can('edit_post', $post_id)) {
        Logger::warning('Blocked manual measure: insufficient capability', ['post_id' => $post_id]);
        return;
      }

      if ($post_id > 0) {
        $plugin = Plugin::get_instance();
        $emissions_handler = $plugin->get_emissions_handler();
        $emissions_handler->process_post($post_id);

        Logger::log("Manual measurement triggered for post ID: {$post_id}");

        wp_safe_redirect(wp_get_referer());
        exit;
      }
    }
  }
}
