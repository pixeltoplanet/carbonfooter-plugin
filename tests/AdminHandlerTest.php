<?php

/**
 * Admin_Handler Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\AdminHandler;
use CarbonfooterPlugin\Emissions;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-helpers.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-database-optimizer.php';
require_once __DIR__ . '/../inc/class-emissions.php';
require_once __DIR__ . '/../inc/class-admin-handler.php';

class AdminHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        
        // Mock WordPress functions
        when('__')->alias(function ($text) { return $text; });
        when('_x')->alias(function ($text) { return $text; });
        when('esc_html__')->alias(function ($text) { return $text; });
        when('esc_html')->alias(function ($text) { return $text; });
        when('esc_attr')->alias(function ($text) { return $text; });
        when('esc_url')->alias(function ($text) { return $text; });
        when('add_action')->justReturn(true);
        when('add_filter')->justReturn(true);
        when('current_user_can')->justReturn(true);
        when('get_option')->justReturn(false);
        when('update_option')->justReturn(true);
        when('is_admin')->justReturn(true);
        when('get_post_meta')->justReturn(null);
        when('get_the_ID')->justReturn(1);
        when('admin_url')->justReturn('http://example.com/wp-admin/');
        when('plugin_dir_url')->justReturn('http://example.com/wp-content/plugins/carbonfooter-plugin/');
        when('plugin_dir_path')->justReturn(__DIR__ . '/../');
        when('plugins_url')->justReturn('http://example.com/wp-content/plugins/');
        when('plugin_basename')->justReturn('carbonfooter-plugin/carbonfooter.php');
        when('wp_create_nonce')->justReturn('test-nonce');
        when('get_current_user_id')->justReturn(1);
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);
        when('wp_json_encode')->alias(function ($data) { return json_encode($data); });
        when('get_permalink')->justReturn('http://example.com/test-post/');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Helper to create a mocked Emissions instance
     */
    private function create_mock_emissions(): Emissions
    {
        return new Emissions();
    }

    /**
     * Test AdminHandler instantiates
     */
    public function test_instantiates(): void
    {
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        $this->assertInstanceOf(AdminHandler::class, $handler);
    }

    /**
     * Test register_hooks can be called without error
     */
    public function test_register_hooks_runs_without_error(): void
    {
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        
        $handler->register_hooks();
        
        // If we get here without error, the test passes
        $this->assertTrue(true);
    }

    /**
     * Test add_emissions_columns adds expected column
     */
    public function test_add_emissions_columns_adds_columns(): void
    {
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        
        $columns = [
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'date' => 'Date',
        ];
        
        $modified = $handler->add_emissions_columns($columns);
        
        // The actual implementation adds 'carbon_emissions' column
        $this->assertArrayHasKey('carbon_emissions', $modified);
        $this->assertEquals('CO2 Emissions', $modified['carbon_emissions']);
    }

    /**
     * Test add_emissions_columns preserves existing columns
     */
    public function test_add_emissions_columns_preserves_existing(): void
    {
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        
        $columns = [
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'author' => 'Author',
            'date' => 'Date',
        ];
        
        $modified = $handler->add_emissions_columns($columns);
        
        $this->assertArrayHasKey('cb', $modified);
        $this->assertArrayHasKey('title', $modified);
        $this->assertArrayHasKey('author', $modified);
        $this->assertArrayHasKey('date', $modified);
    }

    /**
     * Test add_plugin_action_links adds settings link
     */
    public function test_add_plugin_action_links_adds_settings(): void
    {
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        
        $links = [];
        $modified = $handler->add_plugin_action_links($links);
        
        $this->assertNotEmpty($modified);
        $this->assertIsArray($modified);
    }

    /**
     * Test render_emissions_column_content ignores non-emissions columns
     */
    public function test_render_emissions_column_content_ignores_other_columns(): void
    {
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        
        ob_start();
        $handler->render_emissions_column_content('other_column', 1);
        $output = ob_get_clean();
        
        // Should output nothing for non-carbon_emissions columns
        $this->assertEmpty($output);
    }

    /**
     * Test render_emissions_column_content outputs for carbon_emissions column when no emissions exist
     */
    public function test_render_emissions_column_content_shows_no_result(): void
    {
        // Mock the emissions handler to return null (no emissions)
        $emissions = $this->getMockBuilder(Emissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emissions->method('get_post_emissions')->willReturn(null);
        
        $handler = new AdminHandler($emissions);
        
        ob_start();
        $handler->render_emissions_column_content('carbon_emissions', 1);
        $output = ob_get_clean();
        
        // Should show "No result yet" message
        $this->assertStringContainsString('No result yet', $output);
    }

    /**
     * Test render_emissions_column_content outputs emissions when they exist
     */
    public function test_render_emissions_column_content_shows_emissions(): void
    {
        // Mock the emissions handler to return a value
        $emissions = $this->getMockBuilder(Emissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emissions->method('get_post_emissions')->willReturn(0.5);
        
        $handler = new AdminHandler($emissions);
        
        ob_start();
        $handler->render_emissions_column_content('carbon_emissions', 1);
        $output = ob_get_clean();
        
        // Should show emissions value
        $this->assertStringContainsString('CO2', $output);
        $this->assertStringContainsString('0.50', $output);
    }

    /**
     * Test enqueue_admin_assets can be called without error
     */
    public function test_enqueue_admin_assets_runs_without_error(): void
    {
        when('wp_enqueue_script')->justReturn(true);
        when('wp_enqueue_style')->justReturn(true);
        when('wp_localize_script')->justReturn(true);
        when('wp_set_script_translations')->justReturn(true);
        
        $emissions = $this->create_mock_emissions();
        $handler = new AdminHandler($emissions);
        
        // Should not throw an error when called with non-plugin hook
        $handler->enqueue_admin_assets('edit.php');
        
        $this->assertTrue(true); // If we get here, no errors occurred
    }
}
