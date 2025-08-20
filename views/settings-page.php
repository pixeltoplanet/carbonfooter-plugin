<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

// Check for caching plugins
$caching_status = \CarbonfooterPlugin\Helpers::check_caching_plugins();
$is_local = \CarbonfooterPlugin\Helpers::is_local_environment();

// Show notice if caching plugins are active OR if we're in a local environment
$show_notice = $caching_status['active'] || $is_local;
?>

<?php if ($show_notice): ?>
  <div class="notice notice-warning" style="max-width: 900px;margin: 20px 0; padding: 15px; border-left: 4px solid #ffb900; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
    <div style="display: flex; align-items: flex-start;">
      <div style="margin-right: 10px; margin-top: 2px;">
        <span class="dashicons dashicons-warning" style="color: #ffb900; font-size: 20px;"></span>
      </div>
      <div>
        <h3 style="margin: 0 0 10px 0; color: #23282d; font-size: 16px;">
          <?php echo esc_html__('Caching Plugin Detected', 'carbonfooter'); ?>
        </h3>
        <p style="margin: 0 0 10px 0; color: #666; line-height: 1.5;">
          <?php
          if ($caching_status['active']) {
            $plugin_list = implode(', ', $caching_status['plugins']);
            printf(
              /* translators: %s is a comma-separated list of detected caching plugins. */
              wp_kses_post(__('We detected the following caching plugin(s): <strong>%s</strong>.', 'carbonfooter')),
              esc_html($plugin_list)
            );
          } else {
            echo esc_html__('Caching plugin detection is active for testing purposes in local environment.', 'carbonfooter');
          }
          ?>
        </p>
        <p style="margin: 0; color: #666; line-height: 1.5;">
          <?php echo esc_html__('Caching plugins may interfere with accurate carbon emissions measurements. Please make sure to clear your cache if your settings are not working or you are not seeing any emissions results. Note: if you are using LiteSpeed Cache, you need to clear the cache manually after every measurement for now.', 'carbonfooter'); ?>
        </p>
      </div>
    </div>
  </div>
<?php endif; ?>

<div id="carbonfooter-settings-root"></div>