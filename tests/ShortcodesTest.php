<?php

/**
 * Shortcodes Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Shortcodes;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-helpers.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-database-optimizer.php';
require_once __DIR__ . '/../inc/class-emissions.php';
require_once __DIR__ . '/../inc/class-shortcodes.php';

class ShortcodesTest extends TestCase
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
        when('wp_kses')->alias(function ($text) { return $text; });
        when('wp_kses_post')->alias(function ($text) { return $text; });
        when('add_shortcode')->justReturn(true);
        when('add_action')->justReturn(true);
        when('add_filter')->justReturn(true);
        when('get_option')->justReturn(false);
        when('is_admin')->justReturn(false);
        when('wp_doing_ajax')->justReturn(false);
        when('is_singular')->justReturn(true);
        when('get_queried_object_id')->justReturn(1);
        when('get_the_ID')->justReturn(1);
        when('get_post_meta')->justReturn(null);
        when('wp_enqueue_style')->justReturn(true);
        when('wp_add_inline_style')->justReturn(true);
        when('wp_style_is')->justReturn(false);
        when('wp_register_style')->justReturn(true);
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);
        when('wp_json_encode')->alias(function ($data) { return json_encode($data); });
        when('sanitize_hex_color')->alias(function ($color) { return $color; });
        when('doing_action')->justReturn(false);
        when('do_shortcode')->alias(function ($content) { return $content; });
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test Shortcodes instantiates
     */
    public function test_instantiates(): void
    {
        $shortcodes = new Shortcodes();
        $this->assertInstanceOf(Shortcodes::class, $shortcodes);
    }

    /**
     * Test render_carbonfooter returns string output
     */
    public function test_render_carbonfooter_returns_string(): void
    {
        when('get_option')->alias(function ($option, $default = '') {
            if ($option === 'carbonfooter_widget_style') {
                return 'minimal';
            }
            return $default;
        });
        when('get_post_meta')->justReturn(0.5); // emissions value
        
        $shortcodes = new Shortcodes();
        $output = $shortcodes->render_carbonfooter();
        
        $this->assertIsString($output);
    }

    /**
     * Test allow_svg_in_kses adds SVG elements for post context
     */
    public function test_allow_svg_in_kses_adds_svg_elements(): void
    {
        $shortcodes = new Shortcodes();
        
        $allowed = [
            'div' => ['class' => true],
        ];
        
        $modified = $shortcodes->allow_svg_in_kses($allowed, 'post');
        
        $this->assertArrayHasKey('svg', $modified);
        $this->assertArrayHasKey('path', $modified);
    }

    /**
     * Test allow_svg_in_kses ignores non-post context
     */
    public function test_allow_svg_in_kses_ignores_non_post_context(): void
    {
        $shortcodes = new Shortcodes();
        
        $allowed = [
            'div' => ['class' => true],
        ];
        
        $modified = $shortcodes->allow_svg_in_kses($allowed, 'data');
        
        // Should not add SVG in data context
        $this->assertArrayNotHasKey('svg', $modified);
    }

    /**
     * Test allow_svg_in_kses adds all required SVG attributes
     */
    public function test_allow_svg_in_kses_includes_required_attributes(): void
    {
        $shortcodes = new Shortcodes();
        
        $allowed = [];
        $modified = $shortcodes->allow_svg_in_kses($allowed, 'post');
        
        // Check SVG has required attributes
        $this->assertArrayHasKey('svg', $modified);
        $svg_attrs = $modified['svg'];
        
        $this->assertArrayHasKey('class', $svg_attrs);
        $this->assertArrayHasKey('xmlns', $svg_attrs);
        $this->assertArrayHasKey('width', $svg_attrs);
        $this->assertArrayHasKey('height', $svg_attrs);
        $this->assertArrayHasKey('viewbox', $svg_attrs);
    }

    /**
     * Test allow_svg_in_kses adds path element with required attributes
     */
    public function test_allow_svg_in_kses_includes_path_attributes(): void
    {
        $shortcodes = new Shortcodes();
        
        $allowed = [];
        $modified = $shortcodes->allow_svg_in_kses($allowed, 'post');
        
        // Check path has required attributes
        $this->assertArrayHasKey('path', $modified);
        $path_attrs = $modified['path'];
        
        // Path only requires 'd' attribute for the path data
        $this->assertArrayHasKey('d', $path_attrs);
    }
}
