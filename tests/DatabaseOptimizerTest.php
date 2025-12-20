<?php

/**
 * Database_Optimizer Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Database_Optimizer;
use CarbonfooterPlugin\Constants;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-database-optimizer.php';

class DatabaseOptimizerTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        
        when('__')->alias(function ($text) { return $text; });
        when('wp_json_encode')->alias(function ($data) { return json_encode($data); });
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('wp_cache_delete')->justReturn(true);
        when('delete_transient')->justReturn(true);
        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);
        when('get_option')->justReturn(false);
        when('get_post_meta')->justReturn(null);
        when('get_permalink')->justReturn('https://example.com/test');
        when('get_the_title')->justReturn('Test Post');
        when('get_post_types')->justReturn(['post', 'page']);
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test get_site_stats returns cached data when available
     */
    public function test_get_site_stats_returns_cached(): void
    {
        $cachedStats = [
            'total_measured' => 50,
            'average_emissions' => 1.5,
            'total_emissions' => 75.0,
        ];
        
        when('wp_cache_get')->alias(function ($key, $group) use ($cachedStats) {
            if ($key === 'site_stats') {
                return $cachedStats;
            }
            return false;
        });
        
        $result = Database_Optimizer::get_site_stats();
        
        $this->assertEquals($cachedStats, $result);
    }

    /**
     * Test get_heaviest_pages returns cached data
     */
    public function test_get_heaviest_pages_returns_cached(): void
    {
        $cachedPages = [
            ['post_id' => 1, 'emissions' => 5.0, 'title' => 'Heavy Page'],
            ['post_id' => 2, 'emissions' => 4.0, 'title' => 'Second'],
        ];
        
        when('wp_cache_get')->alias(function ($key, $group) use ($cachedPages) {
            if (strpos($key, 'heaviest_pages') !== false) {
                return $cachedPages;
            }
            return false;
        });
        
        $result = Database_Optimizer::get_heaviest_pages(10);
        
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
        
        $result = Database_Optimizer::get_post_emissions(123);
        
        $this->assertEquals(3.5, $result);
    }

    /**
     * Test get_post_emissions returns from meta when not cached
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
        
        $result = Database_Optimizer::get_post_emissions(123);
        
        $this->assertEquals(2.5, $result);
    }

    /**
     * Test get_post_emissions returns false when not found
     */
    public function test_get_post_emissions_returns_false_when_not_found(): void
    {
        when('wp_cache_get')->justReturn(false);
        when('get_post_meta')->justReturn(null);
        
        $result = Database_Optimizer::get_post_emissions(999);
        
        $this->assertFalse($result);
    }

    /**
     * Test invalidate_post_cache runs without error
     */
    public function test_invalidate_post_cache_completes(): void
    {
        Database_Optimizer::invalidate_post_cache(123);
        
        // Should complete without throwing
        $this->assertTrue(true);
    }

    /**
     * Test cached post emissions avoids meta lookup
     */
    public function test_cached_emissions_avoids_meta_lookup(): void
    {
        $metaCalled = false;
        
        when('wp_cache_get')->alias(function ($key, $group) {
            if (strpos($key, 'carbonfooter_emissions_') !== false) {
                return 5.0;
            }
            return false;
        });
        
        when('get_post_meta')->alias(function ($post_id, $key, $single) use (&$metaCalled) {
            $metaCalled = true;
            return null;
        });
        
        $result = Database_Optimizer::get_post_emissions(456);
        
        $this->assertEquals(5.0, $result);
        $this->assertFalse($metaCalled);
    }
}
