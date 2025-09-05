<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-emissions.php';
require_once __DIR__ . '/../inc/class-background-processor.php';

// Define DISABLE_WP_CRON for this test file to exercise the fallback paths
if (!defined('DISABLE_WP_CRON')) {
    define('DISABLE_WP_CRON', true);
}

class BackgroundProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        // add_action is called in constructor; no-op
        when('add_action')->justReturn(true);
        // Pass-through for apply_filters(tag, value)
        when('apply_filters')->alias(function ($tag, $value) {
            return $value;
        });
        // Stub WP Object Cache functions used inside Cache class
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('wp_cache_delete')->justReturn(true);
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    public function test_cron_disabled_unauthorized_triggers_fire_and_forget_ping()
    {
        // Frontend singular view with a valid post
        when('is_singular')->justReturn(true);
        when('get_the_ID')->justReturn(123);

        // Unauthorized visitor
        when('current_user_can')->justReturn(false);

        // Not locked initially; verify lock gets set
        $locks = [];
        when('get_transient')->alias(function ($key) use (&$locks) {
            return $locks[$key] ?? false;
        });
        when('set_transient')->alias(function ($key, $val, $ttl) use (&$locks) {
            $locks[$key] = true;
            return true;
        });

        // Force should_update_emissions() to return true via legacy meta path
        when('get_post_meta')->justReturn('2001-01-01 00:00:00');

        // Expect a non-blocking cron ping
        $pinged = false;
        expect('wp_remote_get')->once()->andReturnUsing(function ($url, $args) use (&$pinged) {
            $pinged = true;
            // Quick sanity checks for args
            if (!isset($args['blocking']) || $args['blocking'] !== false) {
                throw new Exception('Expected non-blocking cron ping');
            }
            return null;
        });

        // Additional WP functions used
        when('add_query_arg')->alias(function ($key, $val, $url) {
            return $url . '?' . urlencode($key) . '=' . urlencode((string)$val);
        });
        when('site_url')->justReturn('https://example.com/wp-cron.php');

        $processor = new CarbonfooterPlugin\Background_Processor();
        $processor->maybe_schedule_processing();

        $this->assertTrue($pinged, 'Expected a fire-and-forget wp-cron.php ping when cron is disabled and user unauthorized');
        $this->assertTrue(($locks['carbonfooter_processing_123'] ?? false), 'Expected transient lock to be set');
    }
}
