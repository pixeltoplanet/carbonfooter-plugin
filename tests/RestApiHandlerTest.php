<?php

/**
 * Rest_Api_Handler Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\RestApiHandler;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-rest-api-handler.php';

class RestApiHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        
        // Mock WordPress functions
        when('__')->alias(function ($text) { return $text; });
        when('_x')->alias(function ($text) { return $text; });
        when('esc_html__')->alias(function ($text) { return $text; });
        when('add_action')->justReturn(true);
        when('current_user_can')->justReturn(true);
        when('get_option')->justReturn(false);
        when('update_option')->justReturn(true);
        when('get_current_user_id')->justReturn(1);
        when('register_rest_route')->justReturn(true);
        when('rest_ensure_response')->alias(function ($data) {
            return new WP_REST_Response($data);
        });
        when('sanitize_hex_color')->alias(function ($color) {
            if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                return $color;
            }
            return '';
        });
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test RestApiHandler instantiates
     */
    public function test_instantiates(): void
    {
        $handler = new RestApiHandler();
        $this->assertInstanceOf(RestApiHandler::class, $handler);
    }

    /**
     * Test register_hooks can be called without error
     */
    public function test_register_hooks_runs_without_error(): void
    {
        $handler = new RestApiHandler();
        $handler->register_hooks();
        
        // If we get here without error, the test passes
        $this->assertTrue(true);
    }

    /**
     * Test check_manage_options_permission returns boolean
     */
    public function test_check_manage_options_permission_returns_true_for_admin(): void
    {
        when('current_user_can')->justReturn(true);
        
        $handler = new RestApiHandler();
        $result = $handler->check_manage_options_permission();
        
        $this->assertTrue($result);
    }

    /**
     * Test check_manage_options_permission returns false for non-admin
     */
    public function test_check_manage_options_permission_returns_false_for_non_admin(): void
    {
        when('current_user_can')->justReturn(false);
        
        $handler = new RestApiHandler();
        $result = $handler->check_manage_options_permission();
        
        $this->assertFalse($result);
    }

    /**
     * Test validate_hex_color with valid colors
     */
    public function test_validate_hex_color_accepts_valid_colors(): void
    {
        $handler = new RestApiHandler();
        $request = new WP_REST_Request();
        
        // Valid 6-digit hex
        $result = $handler->validate_hex_color('#000000', $request, 'color');
        $this->assertTrue($result);
        
        // Valid 3-digit hex
        $result = $handler->validate_hex_color('#fff', $request, 'color');
        $this->assertTrue($result);
        
        // Empty value (allowed for partial updates)
        $result = $handler->validate_hex_color('', $request, 'color');
        $this->assertTrue($result);
    }

    /**
     * Test validate_hex_color rejects invalid colors
     */
    public function test_validate_hex_color_rejects_invalid_colors(): void
    {
        $handler = new RestApiHandler();
        $request = new WP_REST_Request();
        
        $result = $handler->validate_hex_color('invalid', $request, 'color');
        $this->assertInstanceOf(WP_Error::class, $result);
        
        $result = $handler->validate_hex_color('red', $request, 'color');
        $this->assertInstanceOf(WP_Error::class, $result);
        
        $result = $handler->validate_hex_color('#gggggg', $request, 'color');
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test validate_display_setting with valid values
     */
    public function test_validate_display_setting_accepts_valid_values(): void
    {
        $handler = new RestApiHandler();
        $request = new WP_REST_Request();
        
        $result = $handler->validate_display_setting('auto', $request, 'display_setting');
        $this->assertTrue($result);
        
        $result = $handler->validate_display_setting('shortcode', $request, 'display_setting');
        $this->assertTrue($result);
        
        // Empty value allowed for partial updates
        $result = $handler->validate_display_setting('', $request, 'display_setting');
        $this->assertTrue($result);
    }

    /**
     * Test validate_display_setting rejects invalid values
     */
    public function test_validate_display_setting_rejects_invalid_values(): void
    {
        $handler = new RestApiHandler();
        $request = new WP_REST_Request();
        
        $result = $handler->validate_display_setting('invalid', $request, 'display_setting');
        $this->assertInstanceOf(WP_Error::class, $result);
        
        $result = $handler->validate_display_setting('manual', $request, 'display_setting');
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test validate_widget_style with valid values
     */
    public function test_validate_widget_style_accepts_valid_values(): void
    {
        $handler = new RestApiHandler();
        $request = new WP_REST_Request();
        
        $result = $handler->validate_widget_style('minimal', $request, 'widget_style');
        $this->assertTrue($result);
        
        $result = $handler->validate_widget_style('full', $request, 'widget_style');
        $this->assertTrue($result);
        
        $result = $handler->validate_widget_style('sticker', $request, 'widget_style');
        $this->assertTrue($result);
        
        // Empty value allowed for partial updates
        $result = $handler->validate_widget_style('', $request, 'widget_style');
        $this->assertTrue($result);
    }

    /**
     * Test validate_widget_style rejects invalid values
     */
    public function test_validate_widget_style_rejects_invalid_values(): void
    {
        $handler = new RestApiHandler();
        $request = new WP_REST_Request();
        
        $result = $handler->validate_widget_style('invalid', $request, 'widget_style');
        $this->assertInstanceOf(WP_Error::class, $result);
        
        $result = $handler->validate_widget_style('large', $request, 'widget_style');
        $this->assertInstanceOf(WP_Error::class, $result);
    }
}

