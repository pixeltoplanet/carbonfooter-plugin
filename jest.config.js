/**
 * Jest Configuration for CarbonFooter Plugin
 *
 * Uses @wordpress/scripts defaults with custom overrides for WordPress integration.
 *
 * @package CarbonFooter
 */

const defaultConfig = require('@wordpress/scripts/config/jest-unit.config');

module.exports = {
    ...defaultConfig,

    // Setup file runs before each test
    setupFilesAfterEnv: [
        '<rootDir>/src/setupTests.js',
    ],

    // Test environment
    testEnvironment: 'jsdom',

    // Where to find tests
    testMatch: [
        '<rootDir>/src/__tests__/**/*.test.js',
        '<rootDir>/src/**/*.test.js',
    ],

    // Coverage configuration
    collectCoverageFrom: [
        'src/**/*.js',
        '!src/index.js',
        '!src/setupTests.js',
        '!src/__tests__/**',
        '!**/node_modules/**',
    ],

    coverageDirectory: '<rootDir>/coverage',

    coverageReporters: ['text', 'lcov', 'html'],

    // Coverage thresholds (commented out until more component tests are added)
    // The lib/ directory has 100% coverage
    // coverageThreshold: {
    //     global: {
    //         branches: 5,
    //         functions: 5,
    //         lines: 5,
    //         statements: 5,
    //     },
    // },

    // Module name mapping for WordPress packages
    moduleNameMapper: {
        ...defaultConfig.moduleNameMapper,
        '^@wordpress/(.*)$': '<rootDir>/node_modules/@wordpress/$1',
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
    },

    // Transform configuration
    transform: {
        '^.+\\.[jt]sx?$': [
            'babel-jest',
            {
                presets: ['@wordpress/babel-preset-default'],
            },
        ],
    },

    // Ignore patterns
    testPathIgnorePatterns: [
        '/node_modules/',
        '/build/',
        '/vendor/',
    ],

    // Verbose output
    verbose: true,

    // Clear mocks between tests
    clearMocks: true,

    // Automatically restore mocks
    restoreMocks: true,
};
