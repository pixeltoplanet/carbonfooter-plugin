/**
 * Tests for drivingDistance utility functions
 *
 * @package CarbonFooter
 */

import {
    calculateDrivingDistance,
    formatDrivingDistance,
    getDrivingDistance,
} from '../../lib/drivingDistance';

describe('drivingDistance utilities', () => {
    describe('calculateDrivingDistance', () => {
        it('should return 0 for 0 emissions', () => {
            expect(calculateDrivingDistance(0)).toBe(0);
        });

        it('should return 0 for negative emissions', () => {
            expect(calculateDrivingDistance(-100)).toBe(0);
            expect(calculateDrivingDistance(-1)).toBe(0);
        });

        it('should return 0 for non-number inputs', () => {
            expect(calculateDrivingDistance('100')).toBe(0);
            expect(calculateDrivingDistance(null)).toBe(0);
            expect(calculateDrivingDistance(undefined)).toBe(0);
            expect(calculateDrivingDistance({})).toBe(0);
        });

        it('should calculate distance correctly (1g = 0.005km)', () => {
            expect(calculateDrivingDistance(1)).toBe(0.01); // Rounded to 2 decimals
            expect(calculateDrivingDistance(200)).toBe(1); // 200 * 0.005 = 1
            expect(calculateDrivingDistance(1000)).toBe(5); // 1000 * 0.005 = 5
        });

        it('should respect decimal places parameter', () => {
            expect(calculateDrivingDistance(333, 0)).toBe(2);
            expect(calculateDrivingDistance(333, 1)).toBe(1.7);
            expect(calculateDrivingDistance(333, 2)).toBe(1.67);
            expect(calculateDrivingDistance(333, 3)).toBe(1.665);
        });

        it('should use default of 2 decimal places', () => {
            expect(calculateDrivingDistance(333)).toBe(1.67);
        });

        it('should handle large values', () => {
            expect(calculateDrivingDistance(100000)).toBe(500);
            expect(calculateDrivingDistance(1000000)).toBe(5000);
        });
    });

    describe('formatDrivingDistance', () => {
        it('should format distance with km suffix', () => {
            expect(formatDrivingDistance(0)).toBe('0km');
            expect(formatDrivingDistance(200)).toBe('1km');
            expect(formatDrivingDistance(1000)).toBe('5km');
        });

        it('should respect decimal places parameter', () => {
            expect(formatDrivingDistance(333, 0)).toBe('2km');
            expect(formatDrivingDistance(333, 1)).toBe('1.7km');
            expect(formatDrivingDistance(333, 2)).toBe('1.67km');
        });

        it('should handle edge cases', () => {
            expect(formatDrivingDistance(-100)).toBe('0km');
            expect(formatDrivingDistance('invalid')).toBe('0km');
        });
    });

    describe('getDrivingDistance', () => {
        it('should return formatted distance string', () => {
            expect(getDrivingDistance(0)).toBe('0km');
            expect(getDrivingDistance(200)).toBe('1km');
            expect(getDrivingDistance(1000)).toBe('5km');
        });

        it('should respect decimal places parameter', () => {
            expect(getDrivingDistance(333, 1)).toBe('1.7km');
        });
    });

    describe('real-world scenarios', () => {
        it('should calculate driving distance for typical page view', () => {
            // 1g CO2 per page view = 0.005km = 5 meters
            expect(calculateDrivingDistance(1)).toBe(0.01);
        });

        it('should calculate driving distance for website yearly emissions', () => {
            // 100kg = 100000g emissions per year
            // 100000 * 0.005 = 500km
            expect(calculateDrivingDistance(100000)).toBe(500);
            expect(formatDrivingDistance(100000)).toBe('500km');
        });

        it('should work for carbon offset comparisons', () => {
            // "Your website emissions equal driving X km"
            // 50kg yearly = 250km
            expect(formatDrivingDistance(50000)).toBe('250km');
        });
    });
});
