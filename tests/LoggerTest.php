<?php

/**
 * Logger Class Tests
 *
 * @package CarbonFooter
 */

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Logger;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';

class LoggerTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        
        // Stub wp_json_encode for tests that need it
        when('wp_json_encode')->alias(function ($data, $options = 0, $depth = 512) {
            return json_encode($data, $options, $depth);
        });
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    /**
     * Test Logger::LEVEL constants are defined
     */
    public function test_level_constants_are_defined(): void
    {
        $this->assertEquals('info', Logger::LEVEL_INFO);
        $this->assertEquals('error', Logger::LEVEL_ERROR);
        $this->assertEquals('warning', Logger::LEVEL_WARNING);
        $this->assertEquals('debug', Logger::LEVEL_DEBUG);
    }

    /**
     * Test format_log_entry private method creates correct format
     */
    public function test_format_log_entry_creates_correct_format(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('format_log_entry');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, 'Test message', '', 'info');

        // Should contain [CarbonFooter], [INFO], and the message
        $this->assertStringContainsString('[CarbonFooter]', $formatted);
        $this->assertStringContainsString('[INFO]', $formatted);
        $this->assertStringContainsString('Test message', $formatted);
        $this->assertStringContainsString("\n", $formatted); // Should end with newline
    }

    /**
     * Test format_log_entry includes data when provided as array
     */
    public function test_format_log_entry_includes_array_data(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('format_log_entry');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, 'Test message', ['key' => 'value'], 'info');

        $this->assertStringContainsString('Data:', $formatted);
        $this->assertStringContainsString('key', $formatted);
        $this->assertStringContainsString('value', $formatted);
    }

    /**
     * Test format_log_entry includes string data
     */
    public function test_format_log_entry_includes_string_data(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('format_log_entry');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, 'Test message', 'extra context', 'warning');

        $this->assertStringContainsString('extra context', $formatted);
        $this->assertStringContainsString('[WARNING]', $formatted);
    }

    /**
     * Test format_log_entry handles all log levels
     */
    public function test_format_log_entry_handles_all_levels(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('format_log_entry');
        $method->setAccessible(true);

        $levels = ['info', 'error', 'warning', 'debug'];
        foreach ($levels as $level) {
            $formatted = $method->invoke(null, 'Test', '', $level);
            $this->assertStringContainsString('[' . strtoupper($level) . ']', $formatted);
        }
    }

    /**
     * Test should_log returns true for errors always
     */
    public function test_should_log_returns_true_for_errors(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('should_log');
        $method->setAccessible(true);

        // Errors should always be logged regardless of WP_DEBUG
        $result = $method->invoke(null, 'error');
        $this->assertTrue($result);
    }

    /**
     * Test should_log returns true for non-errors when WP_DEBUG is true
     */
    public function test_should_log_returns_true_for_other_levels_with_debug(): void
    {
        // WP_DEBUG is defined as true in bootstrap

        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('should_log');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'info');
        $this->assertTrue($result);

        $result = $method->invoke(null, 'warning');
        $this->assertTrue($result);

        $result = $method->invoke(null, 'debug');
        $this->assertTrue($result);
    }

    /**
     * Test format_log_entry timestamp format
     */
    public function test_format_log_entry_includes_timestamp(): void
    {
        $reflection = new \ReflectionClass(Logger::class);
        $method = $reflection->getMethod('format_log_entry');
        $method->setAccessible(true);

        $formatted = $method->invoke(null, 'Test message', '', 'info');

        // Should contain a timestamp pattern YYYY-MM-DD HH:MM:SS
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $formatted);
    }
}
