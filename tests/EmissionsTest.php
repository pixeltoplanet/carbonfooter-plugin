<?php

/**
 * Emissions Service Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Emissions;
use CarbonfooterPlugin\Constants;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-helpers.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-database-optimizer.php';
require_once __DIR__ . '/../inc/class-emissions.php';

class EmissionsTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        when('__')->alias(function ($text) { return $text; });
        when('wp_json_encode')->alias(function ($data) { return json_encode($data); });
        when('get_option')->justReturn(false);
        when('get_post_meta')->justReturn(null);
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);
        when('delete_transient')->justReturn(true);
        when('wp_cache_delete')->justReturn(true);
        when('get_permalink')->justReturn('https://example.com/post');
        when('get_post_types')->justReturn(['post', 'page']);
        when('get_the_title')->justReturn('Test Post');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test Emissions class instantiates
     */
    public function test_emissions_instantiates(): void
    {
        $emissions = new Emissions();
        $this->assertInstanceOf(Emissions::class, $emissions);
    }

    /**
     * Test get_site_stats returns array from cache
     */
    public function test_get_site_stats_returns_cached(): void
    {
        $cachedStats = [
            'total_measured' => 10,
            'average_emissions' => 1.5,
            'total_emissions' => 15.0,
        ];
        
        when('wp_cache_get')->alias(function ($key, $group) use ($cachedStats) {
            if ($key === 'site_stats') {
                return $cachedStats;
            }
            return false;
        });
        
        $emissions = new Emissions();
        $result = $emissions->get_site_stats();
        
        $this->assertIsArray($result);
        $this->assertEquals($cachedStats, $result);
    }

    /**
     * Test get_total_measured_posts returns count
     */
    public function test_get_total_measured_posts_returns_count(): void
    {
        when('wp_cache_get')->alias(function ($key, $group) {
            if ($key === 'site_stats') {
                return [
                    'total_measured' => 25,
                    'average_emissions' => 1.5,
                    'total_emissions' => 37.5,
                ];
            }
            return false;
        });
        
        $emissions = new Emissions();
        $result = $emissions->get_total_measured_posts();
        
        $this->assertEquals(25, $result);
    }

    /**
     * Test format_bytes formats correctly
     */
    public function test_format_bytes_formats_correctly(): void
    {
        $emissions = new Emissions();
        
        $this->assertEquals('500 B', $emissions->format_bytes(500));
        $this->assertStringContainsString('KB', $emissions->format_bytes(1025));
        $this->assertStringContainsString('MB', $emissions->format_bytes(1048577));
    }

    /**
     * Test format_bytes handles zero
     */
    public function test_format_bytes_handles_zero(): void
    {
        $emissions = new Emissions();
        $this->assertEquals('0 B', $emissions->format_bytes(0));
    }

    /**
     * Test get_heaviest_pages returns cached data
     */
    public function test_get_heaviest_pages_returns_cached(): void
    {
        $cachedPages = [
            ['post_id' => 1, 'emissions' => 5.0, 'title' => 'Heavy Page'],
        ];
        
        when('wp_cache_get')->alias(function ($key, $group) use ($cachedPages) {
            if (strpos($key, 'heaviest_pages') !== false) {
                return $cachedPages;
            }
            return false;
        });
        
        $emissions = new Emissions();
        $result = $emissions->get_heaviest_pages(10);
        
        $this->assertIsArray($result);
        $this->assertEquals($cachedPages, $result);
    }

    /**
     * Test get_untested_pages returns cached data
     */
    public function test_get_untested_pages_returns_cached(): void
    {
        $cachedPages = [
            'post' => [['id' => 1, 'title' => 'Untested Post']],
        ];
        
        when('wp_cache_get')->alias(function ($key, $group) use ($cachedPages) {
            if ($key === Constants::CACHE_UNTESTED_PAGES_KEY) {
                return $cachedPages;
            }
            return false;
        });
        
        $emissions = new Emissions();
        $result = $emissions->get_untested_pages();
        
        $this->assertIsArray($result);
        $this->assertEquals($cachedPages, $result);
    }

    /**
     * Test get_post_emissions returns cached value
     */
    public function test_get_post_emissions_returns_cached(): void
    {
        when('wp_cache_get')->alias(function ($key, $group) {
            if (strpos($key, 'carbonfooter_emissions_') !== false) {
                return 3.5;
            }
            return false;
        });
        
        $emissions = new Emissions();
        $result = $emissions->get_post_emissions(123);
        
        $this->assertEquals(3.5, $result);
    }

    /**
     * Test get_post_emissions returns from meta when cache empty
     */
    public function test_get_post_emissions_returns_from_meta(): void
    {
        when('wp_cache_get')->justReturn(false);
        when('get_post_meta')->alias(function ($post_id, $key, $single) {
            if ($key === Constants::META_EMISSIONS) {
                return '2.5';
            }
            return null;
        });
        
        $emissions = new Emissions();
        $result = $emissions->get_post_emissions(123);
        
        $this->assertEquals(2.5, $result);
    }

    /**
     * Test get_post_emissions returns false when not found
     */
    public function test_get_post_emissions_returns_false_when_not_found(): void
    {
        when('wp_cache_get')->justReturn(false);
        when('get_post_meta')->justReturn(null);
        
        $emissions = new Emissions();
        $result = $emissions->get_post_emissions(999);
        
        $this->assertFalse($result);
    }
}
