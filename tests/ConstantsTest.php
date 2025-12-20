<?php

/**
 * Constants Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Constants;

require_once __DIR__ . '/../inc/class-constants.php';

class ConstantsTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test plugin version is defined
     */
    public function test_version_is_defined(): void
    {
        $this->assertNotEmpty(Constants::VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Constants::VERSION);
    }

    /**
     * Test text domain is defined
     */
    public function test_text_domain_is_defined(): void
    {
        $this->assertEquals('carbonfooter', Constants::TEXT_DOMAIN);
    }

    /**
     * Test plugin slug is defined
     */
    public function test_plugin_slug_is_defined(): void
    {
        $this->assertEquals('carbonfooter', Constants::PLUGIN_SLUG);
    }

    /**
     * Test API namespace and base URL are defined
     */
    public function test_api_constants_are_defined(): void
    {
        $this->assertEquals('carbonfooter/v1', Constants::API_NAMESPACE);
        $this->assertStringStartsWith('https://', Constants::API_BASE_URL);
    }

    /**
     * Test meta keys are properly prefixed
     */
    public function test_meta_keys_are_prefixed(): void
    {
        $this->assertStringStartsWith('_carbon_', Constants::META_EMISSIONS);
        $this->assertStringStartsWith('_carbon_', Constants::META_PAGE_SIZE);
        $this->assertStringStartsWith('_carbon_', Constants::META_RESOURCES);
        $this->assertStringStartsWith('_carbon_', Constants::META_EMISSIONS_UPDATED);
        $this->assertStringStartsWith('_carbon_', Constants::META_EMISSIONS_HISTORY);
    }

    /**
     * Test option keys are properly prefixed
     */
    public function test_option_keys_are_prefixed(): void
    {
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_WIDGET_BACKGROUND_COLOR);
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_WIDGET_TEXT_COLOR);
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_DISPLAY_SETTING);
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_WIDGET_STYLE);
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_GREEN_HOST);
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_DATA_COLLECTION_ENABLED);
        $this->assertStringStartsWith('carbonfooter_', Constants::OPTION_SHOW_ATTRIBUTION);
    }

    /**
     * Test transient keys are properly prefixed
     */
    public function test_transient_keys_are_prefixed(): void
    {
        $this->assertStringStartsWith('carbonfooter_', Constants::TRANSIENT_ACTIVATION_REDIRECT);
        $this->assertStringStartsWith('carbonfooter_', Constants::TRANSIENT_STATS_CACHE);
    }

    /**
     * Test cache constants are defined
     */
    public function test_cache_constants_are_defined(): void
    {
        $this->assertEquals('carbonfooter', Constants::CACHE_GROUP);
        $this->assertNotEmpty(Constants::CACHE_STATS_KEY);
        $this->assertNotEmpty(Constants::CACHE_HEAVIEST_PAGES_KEY);
        $this->assertNotEmpty(Constants::CACHE_UNTESTED_PAGES_KEY);
        $this->assertNotEmpty(Constants::CACHE_POST_KEY_PREFIX);
    }

    /**
     * Test cache TTL values are positive integers
     */
    public function test_cache_ttl_values_are_positive(): void
    {
        $this->assertIsInt(Constants::CACHE_PER_POST_TTL);
        $this->assertGreaterThan(0, Constants::CACHE_PER_POST_TTL);

        $this->assertIsInt(Constants::CACHE_STALE_AFTER);
        $this->assertGreaterThan(0, Constants::CACHE_STALE_AFTER);

        $this->assertIsInt(Constants::CACHE_EXPIRATION_TIME);
        $this->assertGreaterThan(0, Constants::CACHE_EXPIRATION_TIME);
    }

    /**
     * Test AJAX actions are properly prefixed
     */
    public function test_ajax_actions_are_prefixed(): void
    {
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_MEASURE);
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_GET_STATS);
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_GET_HEAVIEST_PAGES);
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_GET_UNTESTED_PAGES);
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_SAVE_SETTINGS);
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_CLEAR_DATA);
        $this->assertStringStartsWith('carbonfooter_', Constants::AJAX_EXPORT_DATA);
    }

    /**
     * Test nonce actions are defined
     */
    public function test_nonce_actions_are_defined(): void
    {
        $this->assertNotEmpty(Constants::NONCE_ACTION);
        $this->assertEquals('wp_rest', Constants::REST_NONCE_ACTION);
    }

    /**
     * Test file path constants are defined
     */
    public function test_file_path_constants_are_defined(): void
    {
        $this->assertNotEmpty(Constants::LOG_FILENAME);
        $this->assertNotEmpty(Constants::ASSETS_BUILD_DIR);
        $this->assertNotEmpty(Constants::ASSETS_INDEX_FILE);
        $this->assertNotEmpty(Constants::ASSETS_STYLE_FILE);
        $this->assertNotEmpty(Constants::ASSETS_ASSET_FILE);
    }

    /**
     * Test default values are defined
     */
    public function test_default_values_are_defined(): void
    {
        $this->assertMatchesRegularExpression('/^#[A-Fa-f0-9]{6}$/', Constants::DEFAULT_BACKGROUND_COLOR);
        $this->assertMatchesRegularExpression('/^#[A-Fa-f0-9]{6}$/', Constants::DEFAULT_TEXT_COLOR);
        $this->assertContains(Constants::DEFAULT_DISPLAY_SETTING, Constants::DISPLAY_SETTINGS);
        $this->assertContains(Constants::DEFAULT_WIDGET_STYLE, Constants::WIDGET_STYLES);
        $this->assertIsBool(Constants::DEFAULT_DATA_COLLECTION_ENABLED);
        $this->assertIsBool(Constants::DEFAULT_SHOW_ATTRIBUTION);
    }

    /**
     * Test limits are reasonable
     */
    public function test_limits_are_reasonable(): void
    {
        $this->assertGreaterThan(0, Constants::MAX_HEAVIEST_PAGES_LIMIT);
        $this->assertLessThanOrEqual(100, Constants::MAX_HEAVIEST_PAGES_LIMIT);

        $this->assertGreaterThan(0, Constants::MAX_UNTESTED_PAGES_LIMIT);
        $this->assertLessThanOrEqual(200, Constants::MAX_UNTESTED_PAGES_LIMIT);

        $this->assertGreaterThan(0, Constants::DEFAULT_HEAVIEST_PAGES_LIMIT);
        $this->assertLessThanOrEqual(Constants::MAX_HEAVIEST_PAGES_LIMIT, Constants::DEFAULT_HEAVIEST_PAGES_LIMIT);

        $this->assertGreaterThan(0, Constants::DEFAULT_UNTESTED_PAGES_LIMIT);
        $this->assertLessThanOrEqual(Constants::MAX_UNTESTED_PAGES_LIMIT, Constants::DEFAULT_UNTESTED_PAGES_LIMIT);
    }

    /**
     * Test widget styles array is valid
     */
    public function test_widget_styles_array_is_valid(): void
    {
        $this->assertIsArray(Constants::WIDGET_STYLES);
        $this->assertNotEmpty(Constants::WIDGET_STYLES);
        $this->assertContains('minimal', Constants::WIDGET_STYLES);
        $this->assertContains('full', Constants::WIDGET_STYLES);
        $this->assertContains('sticker', Constants::WIDGET_STYLES);
    }

    /**
     * Test display settings array is valid
     */
    public function test_display_settings_array_is_valid(): void
    {
        $this->assertIsArray(Constants::DISPLAY_SETTINGS);
        $this->assertNotEmpty(Constants::DISPLAY_SETTINGS);
        $this->assertContains('auto', Constants::DISPLAY_SETTINGS);
        $this->assertContains('shortcode', Constants::DISPLAY_SETTINGS);
    }

    /**
     * Test admin page hooks are defined
     */
    public function test_admin_page_hooks_are_defined(): void
    {
        $this->assertStringContainsString('carbonfooter', Constants::ADMIN_PAGE_MAIN);
        $this->assertStringContainsString('carbonfooter', Constants::ADMIN_PAGE_SETTINGS);
        $this->assertStringContainsString('carbonfooter', Constants::ADMIN_PAGE_RESULTS);
    }

    /**
     * Test menu position is a number
     */
    public function test_menu_position_is_number(): void
    {
        $this->assertIsInt(Constants::MENU_POSITION);
        $this->assertGreaterThan(0, Constants::MENU_POSITION);
    }

    /**
     * Test required capability is valid
     */
    public function test_required_capability_is_valid(): void
    {
        $this->assertEquals('manage_options', Constants::REQUIRED_CAPABILITY);
    }

    /**
     * Test get_meta_keys returns all meta keys
     */
    public function test_get_meta_keys_returns_all_keys(): void
    {
        $keys = Constants::get_meta_keys();

        $this->assertIsArray($keys);
        $this->assertCount(5, $keys);
        $this->assertContains(Constants::META_EMISSIONS, $keys);
        $this->assertContains(Constants::META_PAGE_SIZE, $keys);
        $this->assertContains(Constants::META_RESOURCES, $keys);
        $this->assertContains(Constants::META_EMISSIONS_UPDATED, $keys);
        $this->assertContains(Constants::META_EMISSIONS_HISTORY, $keys);
    }

    /**
     * Test get_option_keys returns all option keys
     */
    public function test_get_option_keys_returns_all_keys(): void
    {
        $keys = Constants::get_option_keys();

        $this->assertIsArray($keys);
        $this->assertCount(7, $keys);
        $this->assertContains(Constants::OPTION_WIDGET_BACKGROUND_COLOR, $keys);
        $this->assertContains(Constants::OPTION_WIDGET_TEXT_COLOR, $keys);
        $this->assertContains(Constants::OPTION_DISPLAY_SETTING, $keys);
        $this->assertContains(Constants::OPTION_WIDGET_STYLE, $keys);
        $this->assertContains(Constants::OPTION_GREEN_HOST, $keys);
        $this->assertContains(Constants::OPTION_DATA_COLLECTION_ENABLED, $keys);
        $this->assertContains(Constants::OPTION_SHOW_ATTRIBUTION, $keys);
    }

    /**
     * Test get_ajax_actions returns all AJAX actions
     */
    public function test_get_ajax_actions_returns_all_actions(): void
    {
        $actions = Constants::get_ajax_actions();

        $this->assertIsArray($actions);
        $this->assertCount(7, $actions);
        $this->assertContains(Constants::AJAX_MEASURE, $actions);
        $this->assertContains(Constants::AJAX_GET_STATS, $actions);
        $this->assertContains(Constants::AJAX_GET_HEAVIEST_PAGES, $actions);
        $this->assertContains(Constants::AJAX_GET_UNTESTED_PAGES, $actions);
        $this->assertContains(Constants::AJAX_SAVE_SETTINGS, $actions);
        $this->assertContains(Constants::AJAX_CLEAR_DATA, $actions);
        $this->assertContains(Constants::AJAX_EXPORT_DATA, $actions);
    }

    /**
     * Test get_admin_page_hooks returns all hooks
     */
    public function test_get_admin_page_hooks_returns_all_hooks(): void
    {
        $hooks = Constants::get_admin_page_hooks();

        $this->assertIsArray($hooks);
        $this->assertCount(3, $hooks);
        $this->assertContains(Constants::ADMIN_PAGE_MAIN, $hooks);
        $this->assertContains(Constants::ADMIN_PAGE_SETTINGS, $hooks);
        $this->assertContains(Constants::ADMIN_PAGE_RESULTS, $hooks);
    }

    /**
     * Test init method runs without errors
     */
    public function test_init_runs_without_errors(): void
    {
        // Should not throw
        try {
            Constants::init();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Constants::init threw an exception: ' . $e->getMessage());
        }
    }
}
