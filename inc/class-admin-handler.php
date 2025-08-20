<?php

/**
 * Admin Handler Class
 *
 * Handles all WordPress admin functionality including menus, scripts, columns, and widgets.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * AdminHandler
 *
 * Manages WordPress admin functionality: menus, scripts, dashboard widgets,
 * list table columns, notices, and frontend CSS variables.
 *
 * Responsibilities:
 * - Register admin menus and submenus
 * - Enqueue and localize admin assets (JS/CSS)
 * - Add emissions column to posts/pages list and render values
 * - Provide a dashboard widget with key metrics
 * - Output CSS variables for the frontend widget
 * - Provide a fallback activation redirect
 *
 * Security:
 * - Admin pages are gated by `Constants::REQUIRED_CAPABILITY`
 * - Localized data avoids sensitive information and includes nonces where needed
 */
class AdminHandler
{

  /**
   * Emissions handler instance
   *
   * @var Emissions
   */
  private Emissions $emissions_handler;

  /**
   * Constructor
   *
   * @param Emissions $emissions_handler Emissions handler instance
   */
  public function __construct(Emissions $emissions_handler)
  {
    $this->emissions_handler = $emissions_handler;
  }

  /**
   * Register admin hooks.
   *
   * Attaches menus, assets, dashboard widgets, notices, custom columns,
   * and frontend style output to appropriate WordPress hooks.
   *
   * @return void
   */
  public function register_hooks(): void
  {
    add_action('admin_menu', [$this, 'register_admin_menus']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    add_action('wp_dashboard_setup', [$this, 'register_dashboard_widgets']);
    add_action('admin_notices', [$this, 'handle_activation_redirect_fallback']);

    // Post/Page columns
    add_filter('manage_posts_columns', [$this, 'add_emissions_columns']);
    add_filter('manage_pages_columns', [$this, 'add_emissions_columns']);
    add_action('manage_posts_custom_column', [$this, 'render_emissions_column_content'], 10, 2);
    add_action('manage_pages_custom_column', [$this, 'render_emissions_column_content'], 10, 2);

    // Plugin action links
    add_filter('plugin_action_links_' . plugin_basename(CARBONFOOTER_PLUGIN_FILE), [$this, 'add_plugin_action_links']);

    // Settings registration
    add_action('admin_init', [$this, 'register_plugin_settings']);

    // Privacy policy
    add_action('admin_init', [$this, 'register_privacy_policy_content']);

    // Frontend styles
    add_action('wp_head', [$this, 'output_frontend_widget_styles']);
  }

  /**
   * Register admin menu pages.
   *
   * Adds a top-level Carbonfooter menu with Results and Settings subpages.
   *
   * @return void
   */
  public function register_admin_menus(): void
  {
    $svg_icon = $this->get_menu_icon_svg();

    add_menu_page(
      __('Carbonfooter', 'carbonfooter'),
      __('Carbonfooter', 'carbonfooter'),
      Constants::REQUIRED_CAPABILITY,
      Constants::PLUGIN_SLUG,
      [$this, 'render_results_page'],
      $svg_icon,
      Constants::MENU_POSITION
    );

    add_submenu_page(
      Constants::PLUGIN_SLUG,
      __('Results', 'carbonfooter'),
      __('Results', 'carbonfooter'),
      Constants::REQUIRED_CAPABILITY,
      Constants::PLUGIN_SLUG,
      [$this, 'render_results_page']
    );

    add_submenu_page(
      Constants::PLUGIN_SLUG,
      __('Settings', 'carbonfooter'),
      __('Settings', 'carbonfooter'),
      Constants::REQUIRED_CAPABILITY,
      Constants::PLUGIN_SLUG . '-settings',
      [$this, 'render_settings_page']
    );
  }

  /**
   * Get SVG icon for admin menu.
   *
   * Returns a data URI for a monochrome SVG so the icon inherits WP color.
   *
   * @return string Base64 encoded SVG icon
   */
  private function get_menu_icon_svg(): string
  {
    $svg_content = '<svg fill="currentColor" height="20px" width="20px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768"><path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z"/><path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73ZM703.28,512.37c-17.09,24.09-39.63,43.27-67.63,57.55-28.01,14.28-61.89,21.42-101.65,21.42s-75.76-8.4-107.96-25.2c-32.21-16.8-57.69-41.17-76.45-73.09-18.77-31.92-28.14-70.28-28.14-115.1v-10.92c0-44.8,9.37-83.03,28.14-114.68,18.76-31.64,44.24-56,76.45-73.09,32.2-17.08,68.18-25.62,107.96-25.62s73.64,7.29,101.65,21.84c28,14.57,50.54,33.89,67.63,57.97,17.08,24.09,28.14,50.7,33.18,79.81l-84.01,17.64c-2.81-18.48-8.69-35.29-17.64-50.41-8.97-15.12-21.57-27.16-37.81-36.13-16.25-8.95-36.69-13.44-61.33-13.44s-45.79,5.46-65.11,16.38c-19.32,10.92-34.59,26.61-45.79,47.05-11.21,20.45-16.8,45.24-16.8,74.35v7.56c0,29.13,5.59,54.06,16.8,74.77,11.2,20.73,26.46,36.41,45.79,47.05,19.32,10.65,41.02,15.96,65.11,15.96,36.4,0,64.12-9.37,83.17-28.14,19.03-18.76,31.08-42.7,36.12-71.83l84.01,19.32c-6.72,28.56-18.63,54.9-35.71,78.97Z"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg_content);
  }

  /**
   * Enqueue admin assets (scripts and styles).
   *
   * Loads built assets when present, falls back to standard dependencies
   * in development mode.
   *
   * @param string $hook_suffix Current admin page hook suffix
   * @return void
   */
  public function enqueue_admin_assets(string $hook_suffix): void
  {
    // Only load on our plugin's admin pages
    $plugin_pages = Constants::get_admin_page_hooks();

    if (!in_array($hook_suffix, $plugin_pages)) {
      return;
    }

    $this->enqueue_react_assets();
    $this->localize_admin_script($hook_suffix);
  }

  /**
   * Enqueue React build assets.
   *
   * Uses `build/index.asset.php` when available for dependency and version
   * metadata; otherwise enqueues with safe defaults for development.
   *
   * @return void
   */
  private function enqueue_react_assets(): void
  {
    $asset_file_path = CARBONFOOTER_PLUGIN_DIR . 'build/index.asset.php';

    if (file_exists($asset_file_path)) {
      $asset_data = include $asset_file_path;

      wp_enqueue_script(
        'carbonfooter-admin',
        CARBONFOOTER_PLUGIN_URL . 'build/index.js',
        $asset_data['dependencies'],
        $asset_data['version'],
        true
      );

      wp_enqueue_style(
        'carbonfooter-admin',
        CARBONFOOTER_PLUGIN_URL . 'build/index.css',
        ['wp-components'],
        $asset_data['version']
      );
    } else {
      // Fallback for development mode
      wp_enqueue_script(
        'carbonfooter-admin',
        CARBONFOOTER_PLUGIN_URL . 'build/index.js',
        ['wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch'],
        Plugin::VERSION,
        true
      );

      wp_enqueue_style(
        'carbonfooter-admin',
        CARBONFOOTER_PLUGIN_URL . 'build/index.css',
        ['wp-components'],
        Plugin::VERSION
      );
    }
  }

  /**
   * Localize script with admin data.
   *
   * Exposes runtime config and initial data to the admin SPA while avoiding
   * sensitive details. Includes nonces for AJAX/REST calls.
   *
   * @param string $hook_suffix Current admin page hook suffix
   * @return void
   */
  private function localize_admin_script(string $hook_suffix): void
  {
    $localization_data = [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce(Constants::NONCE_ACTION),
      'restUrl' => rest_url('wp/v2/'),
      'restNonce' => wp_create_nonce(Constants::REST_NONCE_ACTION),
      'carbonfooterRestUrl' => rest_url(Constants::API_NAMESPACE . '/'),
      'currentPage' => $hook_suffix,
      'siteUrl' => home_url('/'),
      'isLocalEnvironment' => Helpers::is_local_environment(),
      'cachingPlugins' => Helpers::check_caching_plugins(),
      'siteSettings' => [
        'show_on_front' => get_option('show_on_front', 'posts'),
        'page_on_front' => get_option('page_on_front', 0),
        'page_for_posts' => get_option('page_for_posts', 0)
      ],
      'initialData' => [
        'average' => $this->emissions_handler->get_average_emissions(),
        'total_measured' => $this->emissions_handler->get_total_measured_posts(),
        'hosting_status' => get_option(Constants::OPTION_GREEN_HOST, false),
        'total_emissions' => 0, // Will be loaded via AJAX
        'latest_test_date' => null, // Will be loaded via AJAX
        'resource_stats' => [] // Will be loaded via AJAX
      ],
      'i18n' => [
        'measuring' => __('Measuring...', 'carbonfooter'),
        'measureAgain' => __('Measure again', 'carbonfooter'),
        'error' => __('Failed to measure emissions. Please try again.', 'carbonfooter')
      ]
    ];

    wp_localize_script('carbonfooter-admin', 'carbonfooterVars', $localization_data);
    wp_set_script_translations('carbonfooter-admin', 'carbonfooter');
  }

  /**
   * Register dashboard widgets.
   *
   * Adds a "Carbon Emissions Overview" widget to the WP dashboard for admins.
   *
   * @return void
   */
  public function register_dashboard_widgets(): void
  {
    wp_add_dashboard_widget(
      'carbonfooter_dashboard_widget',
      __('Carbon Emissions Overview', 'carbonfooter'),
      [$this, 'render_dashboard_widget']
    );
  }

  /**
   * Add emissions columns to post/page lists.
   *
   * @param array $columns Existing columns
   * @return array Modified columns
   */
  public function add_emissions_columns(array $columns): array
  {
    $columns['carbon_emissions'] = __('CO2 Emissions', 'carbonfooter');
    return $columns;
  }

  /**
   * Render content for emissions columns.
   *
   * Security:
   * - All dynamic output is escaped
   *
   * @param string $column_name Column name
   * @param int    $post_id     Post ID
   * @return void
   */
  public function render_emissions_column_content(string $column_name, int $post_id): void
  {
    if ($column_name !== 'carbon_emissions') {
      return;
    }

    $emissions = $this->emissions_handler->get_post_emissions($post_id);

    if ($emissions) {
      printf(
        '<p class="carbonfooter-emissions">%s</p>',
        esc_html(number_format($emissions, 2) . 'g CO2')
      );
    } else {
      echo esc_html(__('No result yet', 'carbonfooter')) . '<br>';
      printf(
        '<a href="%s" target="_blank" rel="noopener noreferrer" style="padding: 0; border: none; background: transparent; text-decoration: underline; cursor: pointer;" class="measure-emissions">%s</a>',
        esc_url(get_permalink($post_id)),
        esc_html__('Visit page to get a result', 'carbonfooter')
      );
    }
  }

  /**
   * Add plugin action links.
   *
   * Adds quick access links (Settings, Terms) on the Plugins screen.
   *
   * @param array $links Existing action links
   * @return array Modified action links
   */
  public function add_plugin_action_links(array $links): array
  {
    $plugin_links = [
      sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=carbonfooter-settings'),
        __('Settings', 'carbonfooter')
      ),
    ];

    return array_merge($plugin_links, $links);
  }

  /**
   * Register plugin settings.
   *
   * Splits appearance/privacy groups for clarity and REST exposure.
   *
   * @return void
   */
  public function register_plugin_settings(): void
  {
    $this->register_appearance_settings();
    $this->register_privacy_settings();
  }

  /**
   * Register appearance-related settings.
   *
   * Exposes settings to the REST API with schema metadata for editor UIs.
   *
   * @return void
   */
  private function register_appearance_settings(): void
  {
    $appearance_settings = [
      Constants::OPTION_WIDGET_BACKGROUND_COLOR => [
        'type' => 'string',
        'default' => Constants::DEFAULT_BACKGROUND_COLOR,
        'sanitize_callback' => 'sanitize_hex_color'
      ],
      Constants::OPTION_WIDGET_TEXT_COLOR => [
        'type' => 'string',
        'default' => Constants::DEFAULT_TEXT_COLOR,
        'sanitize_callback' => 'sanitize_hex_color'
      ],
      Constants::OPTION_DISPLAY_SETTING => [
        'type' => 'string',
        'default' => Constants::DEFAULT_DISPLAY_SETTING,
        'sanitize_callback' => 'sanitize_text_field'
      ],
      Constants::OPTION_WIDGET_STYLE => [
        'type' => 'string',
        'default' => Constants::DEFAULT_WIDGET_STYLE,
        'sanitize_callback' => 'sanitize_text_field'
      ]
    ];

    foreach ($appearance_settings as $setting_name => $setting_config) {
      register_setting('carbonfooter_settings', $setting_name, array_merge($setting_config, [
        'show_in_rest' => [
          'name' => $setting_name,
          'schema' => [
            'type' => $setting_config['type'],
            'format' => str_contains($setting_name, 'color') ? 'hex-color' : null
          ]
        ]
      ]));
    }
  }

  /**
   * Register privacy-related settings.
   *
   * Settings include data collection and attribution flags; exposed to REST.
   *
   * @return void
   */
  private function register_privacy_settings(): void
  {
    $privacy_settings = [
      Constants::OPTION_DATA_COLLECTION_ENABLED => [
        'type' => 'boolean',
        'default' => Constants::DEFAULT_DATA_COLLECTION_ENABLED,
        'sanitize_callback' => 'rest_sanitize_boolean'
      ],
      Constants::OPTION_SHOW_ATTRIBUTION => [
        'type' => 'boolean',
        'default' => Constants::DEFAULT_SHOW_ATTRIBUTION,
        'sanitize_callback' => 'rest_sanitize_boolean'
      ]
    ];

    foreach ($privacy_settings as $setting_name => $setting_config) {
      register_setting('carbonfooter_settings', $setting_name, array_merge($setting_config, [
        'show_in_rest' => [
          'name' => $setting_name,
          'schema' => [
            'type' => $setting_config['type']
          ]
        ]
      ]));
    }
  }

  /**
   * Register privacy policy content.
   *
   * Adds a concise privacy section describing external API data flow.
   *
   * @return void
   */
  public function register_privacy_policy_content(): void
  {
    if (!function_exists('wp_add_privacy_policy_content')) {
      return;
    }

    $content = sprintf(
      '<p>%s</p><h3>%s</h3><p>%s</p><ul><li>%s</li><li>%s</li></ul>',
      __('The CarbonFooter plugin uses the carbonfooter.nl API service to calculate carbon emissions for your website pages.', 'carbonfooter'),
      __('Data Shared with External Service', 'carbonfooter'),
      __('The following data is sent to carbonfooter.nl:', 'carbonfooter'),
      __('Page URLs for emissions calculation', 'carbonfooter'),
      __('Basic site information for analysis', 'carbonfooter')
    );

    wp_add_privacy_policy_content(
      'CarbonFooter',
      wp_kses_post(wpautop($content, false))
    );
  }

  /**
   * Output frontend widget styles.
   *
   * Emits CSS variables for background/text colors so frontend widgets can
   * consume site-configured appearance without extra network requests.
   *
   * @return void
   */
  public function output_frontend_widget_styles(): void
  {
    $background_color = get_option(Constants::OPTION_WIDGET_BACKGROUND_COLOR, Constants::DEFAULT_BACKGROUND_COLOR);
    $text_color = get_option(Constants::OPTION_WIDGET_TEXT_COLOR, Constants::DEFAULT_TEXT_COLOR);

    printf(
      '<style>:root{--cf-color-background:%s;--cf-color-foreground:%s;}</style>',
      esc_attr($background_color),
      esc_attr($text_color)
    );
  }

  /**
   * Fallback activation redirect using admin_notices.
   *
   * Why:
   * - Provides a redirect mechanism even when early admin_init hooks
   *   are bypassed; uses inline JS to navigate to the Settings page.
   *
   * @return void
   */
  public function handle_activation_redirect_fallback(): void
  {
    $current_page = $_GET['page'] ?? '';

    if (in_array($current_page, ['carbonfooter', 'carbonfooter-settings'])) {
      return;
    }

    $redirect_transient = get_transient('carbonfooter_activation_redirect');

    if (!$redirect_transient) {
      return;
    }

    delete_transient('carbonfooter_activation_redirect');
    Logger::log('Fallback redirect to settings page');

    printf(
      '<script>window.location.href = "%s";</script>',
      esc_url(admin_url('admin.php?page=carbonfooter-settings'))
    );
  }

  /**
   * Render admin pages
   */

  public function render_results_page(): void
  {
    include CARBONFOOTER_PLUGIN_DIR . 'views/results-page.php';
  }

  public function render_settings_page(): void
  {
    include CARBONFOOTER_PLUGIN_DIR . 'views/settings-page.php';
  }

  public function render_dashboard_widget(): void
  {
    include CARBONFOOTER_PLUGIN_DIR . 'views/dashboard-widget.php';
  }
}
