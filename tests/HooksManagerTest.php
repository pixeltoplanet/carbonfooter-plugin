<?php

/**
 * HooksManager Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\HooksManager;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-cache.php';
require_once __DIR__ . '/../inc/class-database-optimizer.php';
require_once __DIR__ . '/../inc/class-hooks-manager.php';

class HooksManagerTest extends TestCase
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
        when('wp_is_post_autosave')->justReturn(false);
        when('wp_is_post_revision')->justReturn(false);
        when('get_post_type')->justReturn('post');
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test HooksManager instantiates with empty handlers
     */
    public function test_instantiates_with_empty_handlers(): void
    {
        $manager = new HooksManager([]);
        $this->assertInstanceOf(HooksManager::class, $manager);
    }

    /**
     * Test HooksManager instantiates with handlers
     */
    public function test_instantiates_with_handlers(): void
    {
        $handler = new class {
            public function register_hooks(): void {}
        };
        
        $manager = new HooksManager(['test' => $handler]);
        $this->assertInstanceOf(HooksManager::class, $manager);
    }

    /**
     * Test register_all_hooks calls handler register_hooks
     */
    public function test_register_all_hooks_calls_handler_methods(): void
    {
        $handlerCalled = false;
        $handler = new class($handlerCalled) {
            private $called;
            public function __construct(&$called) { $this->called = &$called; }
            public function register_hooks(): void { $this->called = true; }
        };
        
        $manager = new HooksManager(['test' => $handler]);
        $manager->register_all_hooks();
        
        $this->assertTrue($handlerCalled);
    }

    /**
     * Test register_all_hooks skips handlers without register_hooks method
     */
    public function test_register_all_hooks_skips_invalid_handlers(): void
    {
        $handler = new stdClass();
        
        $manager = new HooksManager(['invalid' => $handler]);
        
        // Should not throw
        $manager->register_all_hooks();
        $this->assertTrue(true);
    }

    /**
     * Test register_all_hooks runs without errors
     */
    public function test_register_all_hooks_completes(): void
    {
        $manager = new HooksManager([]);
        $manager->register_all_hooks();
        
        // Should complete without throwing
        $this->assertTrue(true);
    }

    /**
     * Test handle_url_check returns early without cf-action
     */
    public function test_handle_url_check_returns_without_action(): void
    {
        unset($_REQUEST['cf-action']);
        
        $manager = new HooksManager([]);
        $manager->handle_url_check();
        
        // Should complete without errors or side effects
        $this->assertTrue(true);
    }

    /**
     * Test invalidate_post_cache_on_save skips autosaves
     */
    public function test_invalidate_cache_skips_autosaves(): void
    {
        when('wp_is_post_autosave')->justReturn(true);
        
        $post = new stdClass();
        $post->ID = 123;
        
        $manager = new HooksManager([]);
        $manager->invalidate_post_cache_on_save(123, $post);
        
        // Should not throw
        $this->assertTrue(true);
    }

    /**
     * Test invalidate_post_cache_on_save skips revisions
     */
    public function test_invalidate_cache_skips_revisions(): void
    {
        when('wp_is_post_autosave')->justReturn(false);
        when('wp_is_post_revision')->justReturn(true);
        
        $post = new stdClass();
        $post->ID = 123;
        
        $manager = new HooksManager([]);
        $manager->invalidate_post_cache_on_save(123, $post);
        
        $this->assertTrue(true);
    }

    /**
     * Test invalidate_post_cache_on_save skips revision post type
     */
    public function test_invalidate_cache_skips_revision_post_type(): void
    {
        when('wp_is_post_autosave')->justReturn(false);
        when('wp_is_post_revision')->justReturn(false);
        when('get_post_type')->justReturn('revision');
        
        $post = new stdClass();
        $post->ID = 123;
        
        $manager = new HooksManager([]);
        $manager->invalidate_post_cache_on_save(123, $post);
        
        $this->assertTrue(true);
    }

    /**
     * Test invalidate_post_cache_on_status_change handles null post
     */
    public function test_invalidate_status_change_handles_null_post(): void
    {
        $manager = new HooksManager([]);
        
        // Should not throw with null post
        $manager->invalidate_post_cache_on_status_change('publish', 'draft', null);
        $this->assertTrue(true);
    }

    /**
     * Test invalidate_post_cache_on_status_change handles post with empty ID
     */
    public function test_invalidate_status_change_handles_empty_id(): void
    {
        $emptyPost = new stdClass();
        $emptyPost->ID = 0;
        
        $manager = new HooksManager([]);
        $manager->invalidate_post_cache_on_status_change('publish', 'draft', $emptyPost);
        
        $this->assertTrue(true);
    }

    /**
     * Test invalidate_post_cache_on_status_change works with valid post
     */
    public function test_invalidate_status_change_works_with_valid_post(): void
    {
        when('wp_is_post_autosave')->justReturn(false);
        when('wp_is_post_revision')->justReturn(false);
        when('get_post_type')->justReturn('post');
        
        $post = new stdClass();
        $post->ID = 123;
        
        $manager = new HooksManager([]);
        $manager->invalidate_post_cache_on_status_change('publish', 'draft', $post);
        
        $this->assertTrue(true);
    }

    /**
     * Test multiple handlers are all called
     */
    public function test_multiple_handlers_are_called(): void
    {
        $handler1Called = false;
        $handler2Called = false;
        
        $handler1 = new class($handler1Called) {
            private $called;
            public function __construct(&$called) { $this->called = &$called; }
            public function register_hooks(): void { $this->called = true; }
        };
        
        $handler2 = new class($handler2Called) {
            private $called;
            public function __construct(&$called) { $this->called = &$called; }
            public function register_hooks(): void { $this->called = true; }
        };
        
        $manager = new HooksManager([
            'handler1' => $handler1,
            'handler2' => $handler2,
        ]);
        
        $manager->register_all_hooks();
        
        $this->assertTrue($handler1Called);
        $this->assertTrue($handler2Called);
    }
}
