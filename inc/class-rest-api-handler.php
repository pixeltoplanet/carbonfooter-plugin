<?php

/**
 * REST API Handler Class
 *
 * Handles all REST API endpoints for the CarbonFooter plugin.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * RestApiHandler
 *
 * Manages the plugin's REST API surface. Registers routes, validates input,
 * enforces permissions, and returns structured responses for consumers.
 *
 * Responsibilities:
 * - Register and maintain REST routes under the `carbonfooter/v1` namespace
 * - Provide a typed, validated interface for reading/updating plugin settings
 * - Normalize responses and error payloads for consistent API behavior
 *
 * Security:
 * - All settings read/write endpoints require `manage_options` capability
 * - Input is sanitized and validated via `sanitize_callback`/validators
 * - Error payloads avoid leaking sensitive system details
 *
 * Performance:
 * - Delegates caching concerns to underlying option getters/setters
 * - Keeps route layer thin; heavy work is performed by domain services
 */
class RestApiHandler
{

  /**
   * API namespace
   *
   * @var string
   */
  private const API_NAMESPACE = Constants::API_NAMESPACE;

  /**
   * Register REST API hooks.
   *
   * Hooks into `rest_api_init` to install all plugin routes at request time.
   *
   * @return void
   */
  public function register_hooks(): void
  {
    add_action('rest_api_init', [$this, 'register_rest_routes']);
  }

  /**
   * Register all REST API routes.
   *
   * Keeps route registration split into feature-focused methods for clarity
   * and future expansion.
   *
   * @return void
   */
  public function register_rest_routes(): void
  {
    $this->register_settings_routes();
  }

  /**
   * Register settings-related REST API routes.
   *
   * Routes:
   * - GET  `carbonfooter/v1/settings`  Read current settings
   * - POST `carbonfooter/v1/settings`  Update one or more settings
   *
   * Security:
   * - Both routes require `manage_options` via `check_manage_options_permission()`
   *
   * @return void
   */
  private function register_settings_routes(): void
  {
    // GET /wp-json/carbonfooter/v1/settings
    register_rest_route(self::API_NAMESPACE, '/settings', [
      'methods' => 'GET',
      'callback' => [$this, 'handle_get_settings_request'],
      'permission_callback' => [$this, 'check_manage_options_permission']
    ]);

    // POST /wp-json/carbonfooter/v1/settings
    register_rest_route(self::API_NAMESPACE, '/settings', [
      'methods' => 'POST',
      'callback' => [$this, 'handle_save_settings_request'],
      'permission_callback' => [$this, 'check_manage_options_permission'],
      'args' => $this->get_settings_endpoint_args()
    ]);
  }

  /**
   * Describe accepted POST parameters for the settings endpoint.
   *
   * Structure (all optional):
   * - background_color: hex color, validated by `validate_hex_color()`
   * - text_color:       hex color, validated by `validate_hex_color()`
   * - display_setting:  'auto' | 'shortcode', validated by `validate_display_setting()`
   * - widget_style:     'minimal' | 'full' | 'sticker', validated by `validate_widget_style()`
   *
   * @return array Endpoint arguments definition compatible with register_rest_route
   */
  private function get_settings_endpoint_args(): array
  {
    return [
      'background_color' => [
        'required' => false,
        'sanitize_callback' => 'sanitize_hex_color',
        'validate_callback' => [$this, 'validate_hex_color'],
        'description' => __('Widget background color in hex format', 'carbonfooter')
      ],
      'text_color' => [
        'required' => false,
        'sanitize_callback' => 'sanitize_hex_color',
        'validate_callback' => [$this, 'validate_hex_color'],
        'description' => __('Widget text color in hex format', 'carbonfooter')
      ],
      'display_setting' => [
        'required' => false,
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => [$this, 'validate_display_setting'],
        'description' => __('Widget display setting (auto, manual, disabled)', 'carbonfooter')
      ],
      'widget_style' => [
        'required' => false,
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => [$this, 'validate_widget_style'],
        'description' => __('Widget style (minimal, detailed, compact)', 'carbonfooter')
      ]
    ];
  }

  /**
   * Handle GET settings request.
   *
   * Why:
   * - Exposes current plugin settings to admin clients consuming the API.
   *
   * Structure:
   * - Reads current settings via `get_current_settings()`
   * - Logs an audit entry (user id, settings count)
   * - Returns a normalized REST response
   *
   * @param \WP_REST_Request $request Request object (unused)
   * @return \WP_REST_Response|\WP_Error Associative array of settings on success or WP_Error on failure
   */
  public function handle_get_settings_request(\WP_REST_Request $request)
  {
    try {
      $settings = $this->get_current_settings();

      Logger::log('Settings retrieved via REST API', [
        'user_id' => get_current_user_id(),
        'settings_count' => count($settings)
      ]);

      return rest_ensure_response($settings);
    } catch (\Exception $e) {
      Logger::log('Error retrieving settings via REST API: ' . $e->getMessage(), 'error');
      return new \WP_Error(
        'carbonfooter_settings_error',
        __('Failed to retrieve settings', 'carbonfooter'),
        ['status' => 500]
      );
    }
  }

  /**
   * Handle POST settings request.
   *
   * Why:
   * - Allows updating one or more settings atomically from the admin UI.
   *
   * Structure:
   * - Iterates a whitelist map parameter=>option
   * - Applies sanitization and validation per `get_settings_endpoint_args()`
   * - Updates options only for provided params
   * - Returns merged success payload including the latest settings snapshot
   *
   * Parameters (optional): background_color, text_color, display_setting, widget_style
   * Returns: `{ success: true, ...settings }` on success or WP_Error on failure
   *
   * @param \WP_REST_Request $request Request object
   * @return \WP_REST_Response|\WP_Error Response object
   */
  public function handle_save_settings_request(\WP_REST_Request $request)
  {
    try {
      $updated_settings = [];
      $settings_to_update = [
        'background_color' => Constants::OPTION_WIDGET_BACKGROUND_COLOR,
        'text_color' => Constants::OPTION_WIDGET_TEXT_COLOR,
        'display_setting' => Constants::OPTION_DISPLAY_SETTING,
        'widget_style' => Constants::OPTION_WIDGET_STYLE
      ];

      foreach ($settings_to_update as $param_key => $option_key) {
        $value = $request->get_param($param_key);

        if ($value !== null) {
          update_option($option_key, $value);
          $updated_settings[$param_key] = $value;
        }
      }

      if (empty($updated_settings)) {
        return new \WP_Error(
          'carbonfooter_no_settings',
          __('No valid settings provided', 'carbonfooter'),
          ['status' => 400]
        );
      }

      Logger::log('Settings updated via REST API', [
        'user_id' => get_current_user_id(),
        'updated_settings' => $updated_settings
      ]);

      $response_data = array_merge(
        ['success' => true],
        $this->get_current_settings()
      );

      return rest_ensure_response($response_data);
    } catch (\Exception $e) {
      Logger::log('Error saving settings via REST API: ' . $e->getMessage(), 'error');
      return new \WP_Error(
        'carbonfooter_save_error',
        __('Failed to save settings', 'carbonfooter'),
        ['status' => 500]
      );
    }
  }

  /**
   * Get current plugin settings.
   *
   * Returns a normalized map of setting keys to values, applying defaults
   * from `Constants` when options are not set.
   *
   * @return array{background_color:string,text_color:string,display_setting:string,widget_style:string}
   */
  private function get_current_settings(): array
  {
    return [
      'background_color' => get_option(Constants::OPTION_WIDGET_BACKGROUND_COLOR, Constants::DEFAULT_BACKGROUND_COLOR),
      'text_color' => get_option(Constants::OPTION_WIDGET_TEXT_COLOR, Constants::DEFAULT_TEXT_COLOR),
      'display_setting' => get_option(Constants::OPTION_DISPLAY_SETTING, Constants::DEFAULT_DISPLAY_SETTING),
      'widget_style' => get_option(Constants::OPTION_WIDGET_STYLE, Constants::DEFAULT_WIDGET_STYLE)
    ];
  }

  /**
   * Permission callback for settings routes.
   *
   * Security:
   * - Restricts settings read/write to administrators (or roles with `manage_options`).
   *
   * @return bool True if current user can manage options
   */
  public function check_manage_options_permission(): bool
  {
    return current_user_can('manage_options');
  }

  /**
   * Validate a hex color value for REST params.
   *
   * Accepts empty values to allow partial updates where a parameter is omitted.
   *
   * @param string            $value   Color value to validate (e.g. #000 or #000000)
   * @param \WP_REST_Request $request Request object (unused)
   * @param string            $param   Parameter name, used in error message
   * @return bool|\WP_Error True if valid or empty, WP_Error with message if invalid
   */
  public function validate_hex_color($value, \WP_REST_Request $request, string $param)
  {
    if (empty($value)) {
      return true; // Allow empty values
    }

    if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
      return new \WP_Error(
        'carbonfooter_invalid_color',
        sprintf(
          /* translators: %s is the parameter name that has an invalid hex color. */
          __('Invalid hex color format for %s', 'carbonfooter'),
          $param
        ),
        ['status' => 400]
      );
    }

    return true;
  }

  /**
   * Validate the display setting REST parameter.
   *
   * Allowed values: 'auto', 'shortcode'. Empty is allowed to support partial updates.
   *
   * @param string            $value   Display setting value to validate
   * @param \WP_REST_Request $request Request object (unused)
   * @param string            $param   Parameter name
   * @return bool|\WP_Error True if valid or empty, WP_Error with allowed list if invalid
   */
  public function validate_display_setting($value, \WP_REST_Request $request, string $param)
  {
    if (empty($value)) {
      return true; // Allow empty values
    }

    $valid_settings = ['auto', 'shortcode'];

    if (!in_array($value, $valid_settings, true)) {
      return new \WP_Error(
        'carbonfooter_invalid_display_setting',
        sprintf(
          /* translators: %s is a comma-separated list of allowed display settings. */
          __('Invalid display setting. Must be one of: %s', 'carbonfooter'),
          implode(', ', $valid_settings)
        ),
        ['status' => 400]
      );
    }

    return true;
  }

  /**
   * Validate the widget style REST parameter.
   *
   * Allowed values: 'minimal', 'full', 'sticker'. Empty is allowed to support partial updates.
   *
   * @param string            $value   Widget style value to validate
   * @param \WP_REST_Request $request Request object (unused)
   * @param string            $param   Parameter name
   * @return bool|\WP_Error True if valid or empty, WP_Error with allowed list if invalid
   */
  public function validate_widget_style($value, \WP_REST_Request $request, string $param)
  {
    if (empty($value)) {
      return true; // Allow empty values
    }

    $valid_styles = ['minimal', 'full', 'sticker'];

    if (!in_array($value, $valid_styles, true)) {
      return new \WP_Error(
        'carbonfooter_invalid_widget_style',
        sprintf(
          /* translators: %s is a comma-separated list of allowed widget styles. */
          __('Invalid widget style. Must be one of: %s', 'carbonfooter'),
          implode(', ', $valid_styles)
        ),
        ['status' => 400]
      );
    }

    return true;
  }
}
