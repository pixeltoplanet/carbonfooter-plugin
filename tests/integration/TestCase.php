<?php
/**
 * Base class for WordPress integration tests
 * 
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin\Tests\Integration;

use WP_UnitTestCase;

/**
 * Base test case for integration tests
 */
abstract class TestCase extends WP_UnitTestCase
{
    /**
     * Set up test fixtures
     */
    public function set_up(): void
    {
        parent::set_up();
        
        // Activate the plugin for each test
        activate_plugin('carbonfooter-plugin/carbonfooter.php');
    }

    /**
     * Tear down test fixtures
     */
    public function tear_down(): void
    {
        parent::tear_down();
    }

    /**
     * Create a test post with emissions data
     *
     * @param float $emissions Emissions value
     * @param array $args Additional post arguments
     * @return int Post ID
     */
    protected function create_post_with_emissions(float $emissions, array $args = []): int
    {
        $defaults = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
        ];

        $post_id = $this->factory->post->create(array_merge($defaults, $args));
        
        // Use correct meta key from Constants
        update_post_meta($post_id, \CarbonfooterPlugin\Constants::META_EMISSIONS, $emissions);
        update_post_meta($post_id, \CarbonfooterPlugin\Constants::META_EMISSIONS_UPDATED, current_time('mysql'));
        
        return $post_id;
    }

    /**
     * Helper to get the Emissions handler
     *
     * @return \CarbonfooterPlugin\Emissions
     */
    protected function get_emissions_handler(): \CarbonfooterPlugin\Emissions
    {
        return new \CarbonfooterPlugin\Emissions();
    }

    /**
     * Helper to clean up all plugin data
     */
    protected function cleanup_plugin_data(): void
    {
        global $wpdb;
        
        // Delete all plugin post meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_carbonfooter_%'");
        
        // Delete all plugin options
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'carbonfooter_%'");
        
        // Delete all plugin transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_carbonfooter_%'");
        
        // Clear object cache
        wp_cache_flush();
    }
}
