<?php
/**
 * AdminHandler Integration Tests
 * 
 * Tests the AdminHandler class with a real WordPress environment
 * 
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin\Tests\Integration;

use CarbonfooterPlugin\AdminHandler;
use CarbonfooterPlugin\Emissions;
use CarbonfooterPlugin\Constants;

/**
 * Integration tests for the AdminHandler class
 */
class AdminHandlerIntegrationTest extends TestCase
{
    /**
     * @var AdminHandler
     */
    protected AdminHandler $handler;

    /**
     * Set up test fixtures
     */
    public function set_up(): void
    {
        parent::set_up();
        
        // Create handler with emissions dependency
        $emissions = new Emissions();
        $this->handler = new AdminHandler($emissions);
    }

    /**
     * Test register_hooks registers admin_menu action
     */
    public function test_register_hooks_adds_admin_menu_action(): void
    {
        $this->handler->register_hooks();
        
        $this->assertTrue(has_action('admin_menu') !== false);
    }

    /**
     * Test add_emissions_columns adds column to existing columns
     */
    public function test_add_emissions_columns_adds_column(): void
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'author' => 'Author',
            'date' => 'Date',
        ];
        
        $result = $this->handler->add_emissions_columns($columns);
        
        $this->assertArrayHasKey('carbon_emissions', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('author', $result);
    }

    /**
     * Test add_plugin_action_links adds settings link
     */
    public function test_add_plugin_action_links_adds_settings(): void
    {
        $links = [];
        
        $result = $this->handler->add_plugin_action_links($links);
        
        $this->assertNotEmpty($result);
        
        // Should contain a link to settings
        $linksString = implode(' ', $result);
        $this->assertStringContainsString('href', $linksString);
    }

    /**
     * Test render_emissions_column_content outputs for measured post
     */
    public function test_render_emissions_column_content_for_measured_post(): void
    {
        $post_id = $this->create_post_with_emissions(2.5);
        
        // Clear cache to ensure meta is read directly
        wp_cache_flush();
        
        ob_start();
        $this->handler->render_emissions_column_content('carbon_emissions', $post_id);
        $output = ob_get_clean();
        
        // Should contain emissions value
        $this->assertStringContainsString('2.5', $output);
    }

    /**
     * Test render_emissions_column_content ignores other columns
     */
    public function test_render_emissions_column_content_ignores_other_columns(): void
    {
        $post_id = $this->factory->post->create(['post_status' => 'publish']);
        
        ob_start();
        $this->handler->render_emissions_column_content('title', $post_id);
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }

    /**
     * Test register_plugin_settings runs without error
     */
    public function test_register_plugin_settings_runs(): void
    {
        $this->handler->register_plugin_settings();
        
        // Should run without throwing
        $this->assertTrue(true);
    }

    /**
     * Test get_menu_icon_svg returns valid SVG data
     */
    public function test_get_menu_icon_svg_returns_valid(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('get_menu_icon_svg');
        $method->setAccessible(true);
        
        $icon = $method->invoke($this->handler);
        
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $icon);
    }
}
