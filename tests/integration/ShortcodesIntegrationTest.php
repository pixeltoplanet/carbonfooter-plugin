<?php
/**
 * Shortcodes Integration Tests
 * 
 * Tests the Shortcodes class with a real WordPress environment
 * 
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin\Tests\Integration;

use CarbonfooterPlugin\Shortcodes;
use CarbonfooterPlugin\Constants;

/**
 * Integration tests for the Shortcodes class
 */
class ShortcodesIntegrationTest extends TestCase
{
    /**
     * @var Shortcodes
     */
    protected Shortcodes $shortcodes;

    /**
     * Set up test fixtures
     */
    public function set_up(): void
    {
        parent::set_up();
        $this->shortcodes = new Shortcodes();
    }

    /**
     * Test shortcode is registered
     */
    public function test_shortcode_is_registered(): void
    {
        $this->assertTrue(shortcode_exists('carbonfooter'));
    }

    /**
     * Test render_carbonfooter returns HTML
     */
    public function test_render_carbonfooter_returns_html(): void
    {
        $result = $this->shortcodes->render_carbonfooter([]);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('carbonfooter', $result);
    }

    /**
     * Test render_carbonfooter returns different output for each style
     */
    public function test_render_different_styles(): void
    {
        $minimal = $this->shortcodes->render_carbonfooter(['style' => 'minimal']);
        $full = $this->shortcodes->render_carbonfooter(['style' => 'full']);
        
        $this->assertIsString($minimal);
        $this->assertIsString($full);
        $this->assertNotEmpty($minimal);
        $this->assertNotEmpty($full);
    }

    /**
     * Test do_shortcode processes the shortcode
     */
    public function test_do_shortcode_processes(): void
    {
        $result = do_shortcode('[carbonfooter]');
        
        $this->assertIsString($result);
        $this->assertStringContainsString('carbonfooter', $result);
    }

    /**
     * Test allow_svg_in_kses adds SVG tags
     */
    public function test_allow_svg_in_kses_adds_tags(): void
    {
        $tags = [];
        $result = $this->shortcodes->allow_svg_in_kses($tags, 'post');
        
        $this->assertArrayHasKey('svg', $result);
        $this->assertArrayHasKey('path', $result);
    }

    /**
     * Test allow_svg_in_kses preserves existing tags
     */
    public function test_allow_svg_in_kses_preserves_existing(): void
    {
        $tags = [
            'div' => ['class' => true],
            'span' => ['id' => true],
        ];
        
        $result = $this->shortcodes->allow_svg_in_kses($tags, 'post');
        
        $this->assertArrayHasKey('div', $result);
        $this->assertArrayHasKey('span', $result);
        $this->assertArrayHasKey('svg', $result);
    }

    /**
     * Test all widget styles produce output
     */
    public function test_all_widget_styles_produce_output(): void
    {
        foreach (Constants::WIDGET_STYLES as $style) {
            $result = $this->shortcodes->render_carbonfooter(['style' => $style]);
            
            $this->assertIsString($result, "Style '$style' should return string");
            $this->assertNotEmpty($result, "Style '$style' should not be empty");
        }
    }

    /**
     * Test maybe_add_to_footer respects shortcode setting
     */
    public function test_maybe_add_to_footer_respects_shortcode_setting(): void
    {
        update_option(Constants::OPTION_DISPLAY_SETTING, 'shortcode');
        
        ob_start();
        $this->shortcodes->maybe_add_to_footer();
        $output = ob_get_clean();
        
        $this->assertEmpty($output);
    }

    /**
     * Test XSS prevention in style attribute
     */
    public function test_xss_prevention_in_style_attribute(): void
    {
        $result = $this->shortcodes->render_carbonfooter([
            'style' => '<script>alert("xss")</script>',
        ]);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert(', $result);
    }
}
