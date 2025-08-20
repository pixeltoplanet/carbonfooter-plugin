<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

$average_emissions = $this->emissions_handler->get_average_emissions();
$total_measured = $this->emissions_handler->get_total_measured_posts();
$is_green_host = get_option('carbonfooter_greenhost', false);

// Get top 10 highest emission pages
global $wpdb;
$high_emission_pages = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm.meta_value as emissions
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_carbon_emissions'
    AND pm.meta_value REGEXP '^[0-9]+(.[0-9]+)?$'
    ORDER BY CAST(pm.meta_value AS DECIMAL(10,2)) DESC
    LIMIT 10
");
?>

<div class="carbonfooter-dashboard-widget">
  <div class="carbonfooter-dashboard-stats">
    <div class="carbonfooter-dashboard-stat">
      <span class="stat-label"><?php echo esc_html__('Average Emissions', 'carbonfooter'); ?></span>
      <span class="stat-value"><?php echo esc_html(number_format($average_emissions, 2)); ?>g CO2</span>
    </div>

    <div class="carbonfooter-dashboard-stat">
      <span class="stat-label"><?php echo esc_html__('Pages Measured', 'carbonfooter'); ?></span>
      <span class="stat-value"><?php echo esc_html($total_measured); ?></span>
    </div>

    <?php if ($is_green_host): ?>
      <div class="carbonfooter-dashboard-stat">
        <span class="stat-label"><?php echo esc_html__('Green Hosting', 'carbonfooter'); ?></span>
        <span class="stat-value green">✓</span>
      </div>
    <?php else: ?>
      <div class="carbonfooter-dashboard-stat">
        <span class="stat-label"><?php echo esc_html__('Non-Green Hosting', 'carbonfooter'); ?></span>
        <span class="stat-value red">✗</span>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($high_emission_pages): ?>
    <div class="carbonfooter-dashboard-pages">
      <h4><?php echo esc_html__('Top 10 Highest Emission Pages', 'carbonfooter'); ?></h4>
      <ul>
        <?php foreach ($high_emission_pages as $page): ?>
          <li>
            <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>">
              <?php echo esc_html($page->post_title); ?>
            </a>
            <span class="emissions"><?php echo esc_html(number_format($page->emissions, 2)); ?>g CO2</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <p class="carbonfooter-dashboard-links">
    <a href="<?php echo esc_url(admin_url('admin.php?page=carbonfooter-settings')); ?>" class="button button-small">
      <?php echo esc_html__('View Details', 'carbonfooter'); ?>
    </a>
  </p>
</div>

<style>
  .carbonfooter-dashboard-widget {
    margin: -12px;
    padding: 12px;
  }

  .carbonfooter-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }

  .carbonfooter-dashboard-stat {
    text-align: center;
  }

  .carbonfooter-dashboard-stat .stat-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-bottom: 5px;
  }

  .carbonfooter-dashboard-stat .stat-value {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #2271b1;
  }

  .carbonfooter-dashboard-stat .stat-value.green {
    color: #46b450;
  }

  .carbonfooter-dashboard-stat .stat-value.red {
    color: #d63638;
  }

  .carbonfooter-dashboard-pages {
    margin-top: 20px;
  }

  .carbonfooter-dashboard-pages h4 {
    margin: 0 0 10px;
    color: #1d2327;
  }

  .carbonfooter-dashboard-pages ul {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .carbonfooter-dashboard-pages li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
  }

  .carbonfooter-dashboard-pages li:last-child {
    border-bottom: none;
  }

  .carbonfooter-dashboard-pages .emissions {
    font-size: 12px;
    color: #646970;
  }

  .carbonfooter-dashboard-links {
    margin: 20px 0 0;
    text-align: right;
  }
</style>