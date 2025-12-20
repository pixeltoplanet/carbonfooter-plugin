/**
 * Tests for formatEmissions utility function
 *
 * @package CarbonFooter
 */

import { formatEmissions } from '../../lib/formatEmissions';

describe('formatEmissions', () => {
    describe('gram formatting', () => {
        it('should format small emissions in grams', () => {
            expect(formatEmissions(0)).toBe('0.00 gr');
            expect(formatEmissions(1)).toBe('1.00 gr');
            expect(formatEmissions(100)).toBe('100.00 gr');
            expect(formatEmissions(500)).toBe('500.00 gr');
            expect(formatEmissions(999)).toBe('999.00 gr');
            expect(formatEmissions(1000)).toBe('1000.00 gr');
        });

        it('should handle decimal grams correctly', () => {
            expect(formatEmissions(0.5)).toBe('0.50 gr');
            expect(formatEmissions(12.345)).toBe('12.35 gr');
            expect(formatEmissions(999.99)).toBe('999.99 gr');
        });
    });

    describe('kilogram formatting', () => {
        it('should convert to kg when over 1000g', () => {
            expect(formatEmissions(1001)).toBe('1.00 kg');
            expect(formatEmissions(1500)).toBe('1.50 kg');
            expect(formatEmissions(2000)).toBe('2.00 kg');
        });

        it('should format larger kg values correctly', () => {
            expect(formatEmissions(10000)).toBe('10.00 kg');
            expect(formatEmissions(100000)).toBe('100.00 kg');
            expect(formatEmissions(1000000)).toBe('1000.00 kg');
        });

        it('should handle decimal kg correctly', () => {
            expect(formatEmissions(1234.56)).toBe('1.23 kg');
            expect(formatEmissions(5678.9)).toBe('5.68 kg');
        });
    });

    describe('boundary cases', () => {
        it('should handle the 1000g boundary correctly', () => {
            expect(formatEmissions(1000)).toBe('1000.00 gr');
            expect(formatEmissions(1000.01)).toBe('1.00 kg');
            expect(formatEmissions(1001)).toBe('1.00 kg');
        });
    });

    describe('real-world website emission scenarios', () => {
        it('should format typical page view emissions', () => {
            // Average website ~0.5-2g CO2 per page view
            expect(formatEmissions(0.5)).toBe('0.50 gr');
            expect(formatEmissions(1.2)).toBe('1.20 gr');
            expect(formatEmissions(2.5)).toBe('2.50 gr');
        });

        it('should format monthly/yearly emissions', () => {
            // 1000 visitors/month at 1g each = 1kg
            expect(formatEmissions(1000)).toBe('1000.00 gr');
            // 10000 visitors = 10kg
            expect(formatEmissions(10000)).toBe('10.00 kg');
        });
    });
});
