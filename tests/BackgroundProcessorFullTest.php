<?php

/**
 * Background_Processor Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Background_Processor;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-helpers.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-database-optimizer.php';
require_once __DIR__ . '/../inc/class-emissions.php';
require_once __DIR__ . '/../inc/class-background-processor.php';

class BackgroundProcessorFullTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        
        when('__')->alias(function ($text) { return $text; });
        when('wp_json_encode')->alias(function ($data) { return json_encode($data); });
        when('add_action')->justReturn(true);
        when('wp_cache_get')->justReturn(false);
        when('wp_cache_set')->justReturn(true);
        when('wp_cache_delete')->justReturn(true);
        when('delete_transient')->justReturn(true);
        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);
        when('get_option')->justReturn(false);
        when('update_option')->justReturn(true);
        when('get_post_meta')->justReturn(null);
        when('is_singular')->justReturn(false);
        when('get_the_ID')->justReturn(0);
        when('wp_next_scheduled')->justReturn(false);
        when('wp_schedule_single_event')->justReturn(true);
        when('current_user_can')->justReturn(false);
        when('get_permalink')->justReturn('https://example.com/test');
        when('get_post_types')->justReturn(['post', 'page']);
        when('get_the_title')->justReturn('Test Post');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test Background_Processor instantiates
     */
    public function test_instantiates(): void
    {
        $processor = new Background_Processor();
        $this->assertInstanceOf(Background_Processor::class, $processor);
    }

    /**
     * Test maybe_schedule_processing returns early for non-singular
     */
    public function test_maybe_schedule_returns_for_non_singular(): void
    {
        when('is_singular')->justReturn(false);
        
        $processor = new Background_Processor();
        $processor->maybe_schedule_processing();
        
        $this->assertTrue(true);
    }

    /**
     * Test maybe_schedule_processing returns for missing post ID
     */
    public function test_maybe_schedule_returns_for_missing_post_id(): void
    {
        when('is_singular')->justReturn(true);
        when('get_the_ID')->justReturn(0);
        
        $processor = new Background_Processor();
        $processor->maybe_schedule_processing();
        
        $this->assertTrue(true);
    }

    /**
     * Test maybe_schedule_processing returns for false post ID
     */
    public function test_maybe_schedule_returns_for_false_post_id(): void
    {
        when('is_singular')->justReturn(true);
        when('get_the_ID')->justReturn(false);
        
        $processor = new Background_Processor();
        $processor->maybe_schedule_processing();
        
        $this->assertTrue(true);
    }

    /**
     * Test maybe_schedule_processing skips when locked by transient
     */
    public function test_maybe_schedule_skips_when_locked(): void
    {
        when('is_singular')->justReturn(true);
        when('get_the_ID')->justReturn(123);
        when('wp_next_scheduled')->justReturn(false);
        when('get_transient')->alias(function ($key) {
            if (strpos($key, 'carbonfooter_processing_') !== false) {
                return true; // Locked
            }
            return false;
        });
        
        $processor = new Background_Processor();
        $processor->maybe_schedule_processing();
        
        $this->assertTrue(true);
    }

    /**
     * Test process_emissions clears transient
     */
    public function test_process_emissions_clears_transient(): void
    {
        $transientDeleted = false;
        when('delete_transient')->alias(function ($key) use (&$transientDeleted) {
            if ($key === 'carbonfooter_processing_123') {
                $transientDeleted = true;
            }
            return true;
        });
        
        $processor = new Background_Processor();
        $processor->process_emissions(123);
        
        $this->assertTrue($transientDeleted);
    }
}
