<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Cache;
use CarbonfooterPlugin\Constants;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-cache.php';

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);

        // Provide default stubs for cache functions (tests override per-case when needed)
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('wp_cache_delete')->justReturn(true);
        when('delete_transient')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    public function test_set_and_get_post_payload_roundtrip_and_ttl()
    {
        $store = [];
        $lastSet = ['ttl' => null, 'key' => null, 'group' => null];

        when('wp_cache_set')->alias(function ($key, $value, $group, $ttl) use (&$store, &$lastSet) {
            $store[$group . ':' . $key] = $value;
            $lastSet = compact('ttl', 'key', 'group');
            return true;
        });
        when('wp_cache_get')->alias(function ($key, $group) use (&$store) {
            $composite = $group . ':' . $key;
            return $store[$composite] ?? false;
        });

        $cache = new Cache();
        $payload = [
            'emissions' => 10.5,
            'page_size' => 12345,
            'updated_at' => time(),
            'source' => 'api',
            'stale' => false,
        ];
        $postId = 55;

        $ok = $cache->set_post_payload($postId, $payload);
        $this->assertTrue($ok);

        $fetched = $cache->get_post_payload($postId);
        $this->assertIsArray($fetched);
        $this->assertSame($payload, $fetched);

        // Confirm TTL and group were used
        $this->assertSame(Constants::CACHE_PER_POST_TTL, $lastSet['ttl']);
        $this->assertSame(Constants::CACHE_GROUP, $lastSet['group']);
    }

    public function test_is_stale_behavior_variants()
    {
        $cache = new Cache();
        $this->assertTrue($cache->is_stale(null), 'Null payload should be stale');
        $this->assertTrue($cache->is_stale(['stale' => true]), 'Explicit stale flag should be stale');
        $this->assertTrue($cache->is_stale(['stale' => false, 'updated_at' => 0]), 'Zero timestamp should be stale');
        $old = time() - (Constants::CACHE_STALE_AFTER + 10);
        $this->assertTrue($cache->is_stale(['stale' => false, 'updated_at' => $old]), 'Older than threshold should be stale');
        $fresh = time() - max(0, Constants::CACHE_STALE_AFTER - 10);
        $this->assertFalse($cache->is_stale(['stale' => false, 'updated_at' => $fresh]), 'Newer than threshold should not be stale');
    }

    public function test_mark_stale_preserves_timestamp_and_sets_flag()
    {
        $postId = 42;
        $key = Constants::CACHE_POST_KEY_PREFIX . $postId;
        $group = Constants::CACHE_GROUP;
        $store = [];

        // Seed existing payload
        $existing = [
            'emissions' => 33.3,
            'updated_at' => time() - 100,
            'stale' => false,
            'source' => 'api',
        ];
        $store[$group . ':' . $key] = $existing;

        when('wp_cache_get')->alias(function ($k, $g) use (&$store) {
            return $store[$g . ':' . $k] ?? false;
        });
        when('wp_cache_set')->alias(function ($k, $v, $g, $ttl) use (&$store) {
            $store[$g . ':' . $k] = $v;
            return true;
        });

        $cache = new Cache();
        $cache->mark_stale($postId);

        $after = $store[$group . ':' . $key];
        $this->assertTrue($after['stale']);
        $this->assertSame($existing['updated_at'], $after['updated_at']);
    }

    public function test_delete_removes_key()
    {
        $postId = 77;
        $key = Constants::CACHE_POST_KEY_PREFIX . $postId;
        $deleted = [];

        when('wp_cache_delete')->alias(function ($k, $g) use (&$deleted) {
            $deleted[] = [$g, $k];
            return true;
        });

        $cache = new Cache();
        $ok = $cache->delete($postId);
        $this->assertTrue($ok);
        $this->assertSame([[Constants::CACHE_GROUP, $key]], $deleted);
    }

    public function test_clear_all_deletes_stats_and_lists_and_transient()
    {
        $calls = [
            'delete' => [],
            'transient' => [],
        ];
        when('wp_cache_delete')->alias(function ($k, $g) use (&$calls) {
            $calls['delete'][] = [$g, $k];
            return true;
            ;
        });
        when('delete_transient')->alias(function ($t) use (&$calls) {
            $calls['transient'][] = $t;
            return true;
        });

        $cache = new Cache();
        $cache->clear_all();

        $this->assertContains([Constants::CACHE_GROUP, Constants::CACHE_STATS_KEY], $calls['delete']);
        $this->assertContains(Constants::TRANSIENT_STATS_CACHE, $calls['transient']);
        $this->assertContains([Constants::CACHE_GROUP, Constants::CACHE_HEAVIEST_PAGES_KEY . ':10'], $calls['delete']);
        $this->assertContains([Constants::CACHE_GROUP, Constants::CACHE_HEAVIEST_PAGES_KEY . ':20'], $calls['delete']);
        $this->assertContains([Constants::CACHE_GROUP, Constants::CACHE_HEAVIEST_PAGES_KEY . ':50'], $calls['delete']);
        $this->assertContains([Constants::CACHE_GROUP, Constants::CACHE_UNTESTED_PAGES_KEY], $calls['delete']);
    }

    public function test_back_compat_scalar_get_and_set()
    {
        $store = [];
        when('wp_cache_set')->alias(function ($k, $v, $g, $ttl) use (&$store) {
            $store[$g . ':' . $k] = $v;
            return true;
        });
        when('wp_cache_get')->alias(function ($k, $g) use (&$store) {
            return $store[$g . ':' . $k] ?? false;
        });

        $cache = new Cache();
        $postId = 88;
        $this->assertTrue($cache->set($postId, 12.34));
        $this->assertSame(12.34, $cache->get($postId));
    }
}
