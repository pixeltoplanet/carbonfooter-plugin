/**
 * Tests for trees utility functions
 *
 * @package CarbonFooter
 */

import {
    calculateTreesNeeded,
    formatTreesNeeded,
    getTreesNeeded,
} from '../../lib/trees';

describe('trees utilities', () => {
    describe('calculateTreesNeeded', () => {
        it('should return 0 for 0 emissions', () => {
            expect(calculateTreesNeeded(0)).toBe(0);
        });

        it('should return 0 for negative emissions', () => {
            expect(calculateTreesNeeded(-100)).toBe(0);
            expect(calculateTreesNeeded(-1)).toBe(0);
        });

        it('should return 0 for non-number inputs', () => {
            expect(calculateTreesNeeded('100')).toBe(0);
            expect(calculateTreesNeeded(null)).toBe(0);
            expect(calculateTreesNeeded(undefined)).toBe(0);
            expect(calculateTreesNeeded({})).toBe(0);
        });

        it('should calculate correctly for exact multiples of 5900', () => {
            expect(calculateTreesNeeded(5900)).toBe(1);
            expect(calculateTreesNeeded(11800)).toBe(2);
            expect(calculateTreesNeeded(59000)).toBe(10);
        });

        it('should round up for partial trees', () => {
            expect(calculateTreesNeeded(1)).toBe(1);
            expect(calculateTreesNeeded(100)).toBe(1);
            expect(calculateTreesNeeded(5899)).toBe(1);
            expect(calculateTreesNeeded(5901)).toBe(2);
        });

        it('should handle large emission values', () => {
            expect(calculateTreesNeeded(590000)).toBe(100);
            expect(calculateTreesNeeded(5900000)).toBe(1000);
        });

        it('should handle decimal emissions', () => {
            expect(calculateTreesNeeded(0.5)).toBe(1);
            expect(calculateTreesNeeded(5900.5)).toBe(2);
        });
    });

    describe('formatTreesNeeded', () => {
        it('should format trees as a string', () => {
            expect(formatTreesNeeded(0)).toBe('0 trees');
            expect(formatTreesNeeded(5900)).toBe('1 trees');
            expect(formatTreesNeeded(11800)).toBe('2 trees');
        });

        it('should handle edge cases', () => {
            expect(formatTreesNeeded(-100)).toBe('0 trees');
            expect(formatTreesNeeded(100)).toBe('1 trees');
        });
    });

    describe('getTreesNeeded', () => {
        it('should return the same value as calculateTreesNeeded', () => {
            expect(getTreesNeeded(0)).toBe(calculateTreesNeeded(0));
            expect(getTreesNeeded(5900)).toBe(calculateTreesNeeded(5900));
            expect(getTreesNeeded(10000)).toBe(calculateTreesNeeded(10000));
        });
    });

    describe('real-world scenarios', () => {
        it('should calculate trees for typical website emissions', () => {
            // Website with 1000 visitors/month, 1g each, 12 months = 12kg = 12000g
            // 12000 / 5900 = ~2.03 trees
            expect(calculateTreesNeeded(12000)).toBe(3);
        });

        it('should calculate trees for high-traffic website', () => {
            // 100,000 visitors/month, 2g each, 12 months = 2,400,000g
            // 2,400,000 / 5900 = ~406.78 trees
            expect(calculateTreesNeeded(2400000)).toBe(407);
        });
    });
});
