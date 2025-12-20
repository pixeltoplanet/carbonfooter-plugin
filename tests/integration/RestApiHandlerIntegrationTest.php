<?php
/**
 * REST API Handler Integration Tests
 * 
 * Tests the RestApiHandler class with a real WordPress REST API environment
 * 
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin\Tests\Integration;

use CarbonfooterPlugin\RestApiHandler;
use CarbonfooterPlugin\Constants;

/**
 * Integration tests for the RestApiHandler class
 */
class RestApiHandlerIntegrationTest extends TestCase
{
    /**
     * @var RestApiHandler
     */
    protected RestApiHandler $handler;

    /**
     * Set up test fixtures
     */
    public function set_up(): void
    {
        parent::set_up();
        $this->handler = new RestApiHandler();
    }

    /**
     * Test handler instantiates
     */
    public function test_handler_instantiates(): void
    {
        $this->assertInstanceOf(RestApiHandler::class, $this->handler);
    }

    /**
     * Test register_hooks adds rest_api_init action
     */
    public function test_register_hooks_adds_action(): void
    {
        $this->handler->register_hooks();
        
        $this->assertTrue(has_action('rest_api_init') !== false);
    }

    /**
     * Test check_manage_options_permission returns true for admin
     */
    public function test_permission_check_returns_true_for_admin(): void
    {
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
        
        $result = $this->handler->check_manage_options_permission();
        
        $this->assertTrue($result);
    }

    /**
     * Test check_manage_options_permission returns false for subscriber
     */
    public function test_permission_check_returns_false_for_subscriber(): void
    {
        $subscriber_id = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);
        
        $result = $this->handler->check_manage_options_permission();
        
        $this->assertFalse($result);
    }

    /**
     * Test check_manage_options_permission returns false for logged out user
     */
    public function test_permission_check_returns_false_for_logged_out(): void
    {
        wp_set_current_user(0);
        
        $result = $this->handler->check_manage_options_permission();
        
        $this->assertFalse($result);
    }

    /**
     * Test validate_hex_color validates properly
     */
    public function test_validate_hex_color(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('validate_hex_color');
        $method->setAccessible(true);
        
        $request = new \WP_REST_Request('GET', '/test');
        
        // Valid colors
        $this->assertTrue($method->invoke($this->handler, '#000000', $request, 'color'));
        $this->assertTrue($method->invoke($this->handler, '#FFFFFF', $request, 'color'));
        $this->assertTrue($method->invoke($this->handler, '#fff', $request, 'color'));
        $this->assertTrue($method->invoke($this->handler, '', $request, 'color')); // Empty allowed
        
        // Invalid colors
        $this->assertInstanceOf(\WP_Error::class, $method->invoke($this->handler, 'invalid', $request, 'color'));
        $this->assertInstanceOf(\WP_Error::class, $method->invoke($this->handler, '000000', $request, 'color')); // Missing #
    }

    /**
     * Test validate_display_setting validates properly
     */
    public function test_validate_display_setting(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('validate_display_setting');
        $method->setAccessible(true);
        
        $request = new \WP_REST_Request('GET', '/test');
        
        // Valid settings
        $this->assertTrue($method->invoke($this->handler, 'auto', $request, 'display'));
        $this->assertTrue($method->invoke($this->handler, 'shortcode', $request, 'display'));
        $this->assertTrue($method->invoke($this->handler, '', $request, 'display')); // Empty allowed
        
        // Invalid setting
        $this->assertInstanceOf(\WP_Error::class, $method->invoke($this->handler, 'invalid', $request, 'display'));
    }

    /**
     * Test validate_widget_style validates properly
     */
    public function test_validate_widget_style(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('validate_widget_style');
        $method->setAccessible(true);
        
        $request = new \WP_REST_Request('GET', '/test');
        
        // Valid styles from constants
        foreach (Constants::WIDGET_STYLES as $style) {
            $this->assertTrue($method->invoke($this->handler, $style, $request, 'style'));
        }
        
        // Invalid style
        $this->assertInstanceOf(\WP_Error::class, $method->invoke($this->handler, 'invalid_style', $request, 'style'));
    }
}
