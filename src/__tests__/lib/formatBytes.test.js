/**
 * Tests for formatBytes utility function
 *
 * @package CarbonFooter
 */

import { formatBytes } from '../../lib/formatBytes';

describe('formatBytes', () => {
    describe('basic conversions', () => {
        it('should return "0 B" for 0 bytes', () => {
            expect(formatBytes(0)).toBe('0 B');
        });

        it('should format bytes correctly', () => {
            expect(formatBytes(500)).toBe('500 B');
            expect(formatBytes(1)).toBe('1 B');
            expect(formatBytes(999)).toBe('999 B');
        });

        it('should format kilobytes correctly', () => {
            expect(formatBytes(1024)).toBe('1 KB');
            expect(formatBytes(1536)).toBe('1.5 KB');
            expect(formatBytes(2048)).toBe('2 KB');
        });

        it('should format megabytes correctly', () => {
            expect(formatBytes(1048576)).toBe('1 MB');
            expect(formatBytes(1572864)).toBe('1.5 MB');
            expect(formatBytes(5242880)).toBe('5 MB');
        });

        it('should format gigabytes correctly', () => {
            expect(formatBytes(1073741824)).toBe('1 GB');
            expect(formatBytes(2147483648)).toBe('2 GB');
        });
    });

    describe('edge cases', () => {
        it('should handle decimal values that round nicely', () => {
            expect(formatBytes(1500)).toBe('1.46 KB');
        });

        it('should handle large numbers', () => {
            // 10 GB
            expect(formatBytes(10737418240)).toBe('10 GB');
        });

        it('should handle small decimal differences', () => {
            expect(formatBytes(1025)).toBe('1 KB');
        });
    });

    describe('real-world scenarios', () => {
        it('should format typical web page sizes', () => {
            // Average web page is ~2-3 MB
            expect(formatBytes(2500000)).toBe('2.38 MB');
            expect(formatBytes(3500000)).toBe('3.34 MB');
        });

        it('should format typical image sizes', () => {
            // JPEG ~100-500KB
            expect(formatBytes(150000)).toBe('146.48 KB');
            // PNG ~500KB-2MB
            expect(formatBytes(800000)).toBe('781.25 KB');
        });

        it('should format typical JavaScript bundle sizes', () => {
            // Average JS bundle 200-500KB
            expect(formatBytes(350000)).toBe('341.8 KB');
        });
    });
});
