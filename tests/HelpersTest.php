<?php

/**
 * Helpers Class Tests
 *
 * @package CarbonFooter
 */

use PHPUnit\Framework\TestCase;
use CarbonfooterPlugin\Helpers;
use CarbonfooterPlugin\Constants;

require_once __DIR__ . '/../inc/class-constants.php';
require_once __DIR__ . '/../inc/class-logger.php';
require_once __DIR__ . '/../inc/class-helpers.php';

class HelpersTest extends TestCase
{
    /**
     * Test format_file_size formats bytes correctly
     */
    public function test_format_file_size_formats_bytes(): void
    {
        $this->assertEquals('0 B', Helpers::format_file_size(0));
        $this->assertEquals('500 B', Helpers::format_file_size(500));
        $this->assertEquals('1023 B', Helpers::format_file_size(1023));
    }

    /**
     * Test format_file_size formats kilobytes correctly
     * Note: Implementation uses $bytes > 1024, so exactly 1024 stays as B
     */
    public function test_format_file_size_formats_kilobytes(): void
    {
        // 1025 bytes should trigger KB
        $this->assertEquals('1 KB', Helpers::format_file_size(1025));
        // 1536 stays as B since we divide only when > 1024
        $this->assertEquals('1.5 KB', Helpers::format_file_size(1537));
        // 10.5 KB is about 10752 bytes
        $this->assertEquals('10.5 KB', Helpers::format_file_size(10753));
    }

    /**
     * Test format_file_size formats megabytes correctly
     * Note: Implementation uses $bytes > 1024, so 1048576 (exactly 1MB worth) stays as KB
     */
    public function test_format_file_size_formats_megabytes(): void
    {
        // 1048577 bytes (just over 1MB) should be formatted as MB
        $this->assertEquals('1 MB', Helpers::format_file_size(1048577));
        // 2.5 MB worth is 2621440 bytes, but needs to be slightly more to trigger MB
        $this->assertEquals('2.5 MB', Helpers::format_file_size(2621441));
    }

    /**
     * Test format_file_size respects precision parameter
     */
    public function test_format_file_size_respects_precision(): void
    {
        $this->assertEquals('1.667 KB', Helpers::format_file_size(1707, 3));
        $this->assertEquals('1.7 KB', Helpers::format_file_size(1707, 1));
    }

    /**
     * Test is_valid_hex_color validates 6-character hex
     */
    public function test_is_valid_hex_color_validates_6char_hex(): void
    {
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#000000'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#FFFFFF'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#ff5733'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#ABC123'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#abcdef'));
    }

    /**
     * Test is_valid_hex_color validates 3-character hex
     */
    public function test_is_valid_hex_color_validates_3char_hex(): void
    {
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#000'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#FFF'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#abc'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#ABC'));
    }

    /**
     * Test is_valid_hex_color rejects invalid colors
     */
    public function test_is_valid_hex_color_rejects_invalid(): void
    {
        // Missing hash
        $this->assertFalse((bool) Helpers::is_valid_hex_color('000000'));
        
        // Invalid characters
        $this->assertFalse((bool) Helpers::is_valid_hex_color('#GGGGGG'));
        $this->assertFalse((bool) Helpers::is_valid_hex_color('#ZZZZZZ'));
        
        // Wrong length
        $this->assertFalse((bool) Helpers::is_valid_hex_color('#12345'));
        $this->assertFalse((bool) Helpers::is_valid_hex_color('#1234567'));
        $this->assertFalse((bool) Helpers::is_valid_hex_color('#12'));
        
        // Color name instead of hex
        $this->assertFalse((bool) Helpers::is_valid_hex_color('red'));
        $this->assertFalse((bool) Helpers::is_valid_hex_color('blue'));
        
        // Empty string
        $this->assertFalse((bool) Helpers::is_valid_hex_color(''));
    }

    /**
     * Test is_valid_hex_color edge cases
     */
    public function test_is_valid_hex_color_edge_cases(): void
    {
        // Just hash
        $this->assertFalse((bool) Helpers::is_valid_hex_color('#'));
        
        // Mixed case (valid)
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#AbCdEf'));
        $this->assertTrue((bool) Helpers::is_valid_hex_color('#AbC'));
    }
}
