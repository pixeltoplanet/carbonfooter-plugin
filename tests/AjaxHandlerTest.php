<?php

use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\AjaxHandler;
use CarbonfooterPlugin\Emissions;
use CarbonfooterPlugin\Cache;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-emissions.php';
require_once __DIR__ . '/../inc/class-ajax-handler.php';

class AjaxHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        // Define WP time constants used by code
        if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
        if (!defined('WEEK_IN_SECONDS')) define('WEEK_IN_SECONDS', 7 * 24 * 60 * 60);

        // WP helpers used inside handler
        when('__')->alias(function ($text) { return $text; });
        when('wp_json_encode')->alias(function ($data) { return json_encode($data); });
        // Error helpers
        when('status_header')->justReturn(null);
        when('wp_send_json_error')->justReturn(null);
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    public function test_handle_measure_request_returns_in_progress_when_locked()
    {
        // Mock nonce and capability
        when('check_ajax_referer')->justReturn(true);
        when('current_user_can')->justReturn(true);

        // Simulate lock present
        when('get_transient')->alias(function ($key) {
            return str_starts_with($key, 'carbonfooter_processing_');
        });

        // Mock wp_send_json_success to capture payload without exiting
        $captured = null;
        expect('wp_send_json_success')->once()->andReturnUsing(function ($data) use (&$captured) {
            $captured = $data;
            return null;
        });

        // Provide cached payload via Cache mock
        $fakeCache = $this->createMock(Cache::class);
        $fakeCache->method('get_post_payload')->willReturn([
            'emissions' => 12.34,
            'updated_at' => time() - 100,
            'stale' => true,
            'source' => 'cache'
        ]);

        $fakeEmissions = $this->createMock(Emissions::class);

        // Provide POST data
        $_POST['nonce'] = 'ok';
        $_POST['post_id'] = 123;

        $handler = new AjaxHandler($fakeEmissions, $fakeCache);
        $handler->register_hooks(); // not required but harmless
        $handler->handle_measure_request();

        $this->assertIsArray($captured);
        $this->assertEquals('in_progress', $captured['status']);
        $this->assertEquals(12.34, $captured['emissions']);
    }

    public function test_handle_measure_request_processes_when_not_locked()
    {
        when('check_ajax_referer')->justReturn(true);
        when('current_user_can')->justReturn(true);

        // No lock first, then expect it to be deleted after
        $locks = [];
        when('get_transient')->alias(function ($key) use (&$locks) {
            return $locks[$key] ?? false;
        });
        when('set_transient')->alias(function ($key, $val, $ttl) use (&$locks) {
            $locks[$key] = true;
            return true;
        });
        when('delete_transient')->alias(function ($key) use (&$locks) {
            unset($locks[$key]);
            return true;
        });

        // Capture success payload
        $captured = null;
        expect('wp_send_json_success')->once()->andReturnUsing(function ($data) use (&$captured) {
            $captured = $data;
            return null;
        });

        $fakeCache = $this->createMock(Cache::class);
        $fakeEmissions = $this->createMock(Emissions::class);
        $fakeEmissions->method('process_post')->with(123)->willReturn(50.0);

        $_POST['nonce'] = 'ok';
        $_POST['post_id'] = 123;

        $handler = new AjaxHandler($fakeEmissions, $fakeCache);
        $handler->handle_measure_request();

        $this->assertIsArray($captured);
        $this->assertEquals('completed', $captured['status']);
        $this->assertEquals(50.0, $captured['emissions']);
        // Ensure lock cleared
        $this->assertFalse($locks['carbonfooter_processing_123'] ?? false);
    }
}
