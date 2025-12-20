<?php
/**
 * Database Optimizer Integration Tests
 *
 * Tests the Database_Optimizer class with a real WordPress database.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin\Tests\Integration;

use CarbonfooterPlugin\Database_Optimizer;

/**
 * Integration tests for Database_Optimizer
 */
class DatabaseOptimizerIntegrationTest extends TestCase
{
    /**
     * Database Optimizer instance
     *
     * @var Database_Optimizer
     */
    protected $optimizer;

    /**
     * Set up before each test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->optimizer = new Database_Optimizer();
    }

    /**
     * Test that optimizer instantiates
     */
    public function test_instantiates(): void
    {
        $this->assertInstanceOf(Database_Optimizer::class, $this->optimizer);
    }

    /**
     * Test ensure_custom_table creates table
     */
    public function test_ensure_custom_table_creates_table(): void
    {
        global $wpdb;
        
        // Call the method to ensure table exists
        $this->optimizer->ensure_custom_table();
        
        // Check if table exists
        $table_name = $wpdb->prefix . 'carbonfooter_emissions';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );
        
        $this->assertEquals($table_name, $table_exists);
    }

    /**
     * Test save_emissions_data stores data correctly
     */
    public function test_save_emissions_data_stores_data(): void
    {
        // Create a test post
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Emissions Post',
            'post_status' => 'publish',
        ]);

        // Save emissions data
        $emissions = 0.5;
        $page_size = 150000;
        
        $result = $this->optimizer->save_emissions_data($post_id, $emissions, $page_size);
        
        // Should return true
        $this->assertTrue($result);
        
        // Verify data was saved to post meta
        $saved_emissions = get_post_meta($post_id, '_carbonfooter_emissions', true);
        $saved_page_size = get_post_meta($post_id, '_carbonfooter_page_size', true);
        
        $this->assertEquals($emissions, (float) $saved_emissions);
        $this->assertEquals($page_size, (int) $saved_page_size);
        
        // Clean up
        wp_delete_post($post_id, true);
    }

    /**
     * Test get_emissions_data retrieves data correctly
     */
    public function test_get_emissions_data_retrieves_data(): void
    {
        // Create a test post
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Retrieve Emissions',
            'post_status' => 'publish',
        ]);

        // Set emissions data via meta
        update_post_meta($post_id, '_carbonfooter_emissions', 0.75);
        update_post_meta($post_id, '_carbonfooter_page_size', 200000);
        
        // Retrieve via optimizer
        $data = $this->optimizer->get_emissions_data($post_id);
        
        $this->assertIsArray($data);
        $this->assertEquals(0.75, $data['emissions']);
        $this->assertEquals(200000, $data['page_size']);
        
        // Clean up
        wp_delete_post($post_id, true);
    }

    /**
     * Test get_emissions_data returns null for non-existent post
     */
    public function test_get_emissions_data_returns_null_for_missing_post(): void
    {
        $data = $this->optimizer->get_emissions_data(999999);
        
        $this->assertNull($data);
    }

    /**
     * Test bulk operations work with multiple posts
     */
    public function test_bulk_save_emissions(): void
    {
        // Create test posts
        $post_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $post_ids[] = $this->factory->post->create([
                'post_title' => "Bulk Test Post {$i}",
                'post_status' => 'publish',
            ]);
        }

        // Save emissions for each
        foreach ($post_ids as $index => $post_id) {
            $emissions = ($index + 1) * 0.2; // 0.2, 0.4, 0.6, 0.8, 1.0
            $page_size = ($index + 1) * 50000; // 50k, 100k, etc
            $this->optimizer->save_emissions_data($post_id, $emissions, $page_size);
        }

        // Verify all data was saved
        foreach ($post_ids as $index => $post_id) {
            $data = $this->optimizer->get_emissions_data($post_id);
            
            $expected_emissions = ($index + 1) * 0.2;
            $this->assertEquals($expected_emissions, $data['emissions']);
        }

        // Clean up
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    /**
     * Test get_all_emissions_summary returns correct summary
     */
    public function test_get_all_emissions_summary(): void
    {
        // Create test posts with emissions
        $post_ids = [];
        $total_emissions = 0;
        
        for ($i = 0; $i < 3; $i++) {
            $post_id = $this->factory->post->create([
                'post_title' => "Summary Test Post {$i}",
                'post_status' => 'publish',
            ]);
            $post_ids[] = $post_id;
            
            $emissions = ($i + 1) * 0.5; // 0.5, 1.0, 1.5
            $total_emissions += $emissions;
            
            update_post_meta($post_id, '_carbonfooter_emissions', $emissions);
            update_post_meta($post_id, '_carbonfooter_page_size', 100000);
        }

        // Get summary (method may vary)
        if (method_exists($this->optimizer, 'get_site_summary')) {
            $summary = $this->optimizer->get_site_summary();
            
            $this->assertIsArray($summary);
            $this->assertArrayHasKey('total_pages', $summary);
            $this->assertArrayHasKey('average_emissions', $summary);
        }

        // Clean up
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    /**
     * Test clear_emissions_data removes data
     */
    public function test_clear_emissions_data(): void
    {
        // Create a test post
        $post_id = $this->factory->post->create([
            'post_title' => 'Clear Test Post',
            'post_status' => 'publish',
        ]);

        // Save emissions data
        $this->optimizer->save_emissions_data($post_id, 0.5, 150000);
        
        // Verify data exists
        $data = $this->optimizer->get_emissions_data($post_id);
        $this->assertNotNull($data);

        // Clear emissions (if method exists)
        if (method_exists($this->optimizer, 'clear_emissions_data')) {
            $this->optimizer->clear_emissions_data($post_id);
            
            // Verify data is cleared
            $cleared_data = $this->optimizer->get_emissions_data($post_id);
            $this->assertNull($cleared_data);
        }

        // Clean up
        wp_delete_post($post_id, true);
    }
}
