/**
 * Jest Setup for CarbonFooter Plugin
 *
 * This file is run before each test file.
 *
 * @package CarbonFooter
 */

import '@testing-library/jest-dom';

// Mock WordPress global objects
global.wp = {
	apiFetch: jest.fn(() => Promise.resolve({})),
	i18n: {
		__: (text) => text,
		_x: (text) => text,
		_n: (single, plural, number) => (number === 1 ? single : plural),
		sprintf: (format, ...args) => {
			let i = 0;
			return format.replace(/%s/g, () => args[i++]);
		},
	},
	element: {
		createElement: jest.fn(),
		Fragment: jest.fn(),
		useState: jest.fn(),
		useEffect: jest.fn(),
		useCallback: jest.fn(),
		useMemo: jest.fn(),
		useRef: jest.fn(),
	},
};

// Mock carbonfooterData that's normally passed from PHP
global.carbonfooterData = {
	ajaxUrl: 'http://localhost/wp-admin/admin-ajax.php',
	nonce: 'test-nonce-12345',
	restUrl: 'http://localhost/wp-json/carbonfooter/v1/',
	restNonce: 'test-rest-nonce-12345',
	pluginUrl: 'http://localhost/wp-content/plugins/carbonfooter-plugin/',
	isAdmin: true,
	version: '0.19.0',
	settings: {
		enabled: true,
		showWidget: true,
		widgetPosition: 'bottom-right',
	},
};

// Mock fetch API
global.fetch = jest.fn(() =>
	Promise.resolve({
		ok: true,
		json: () => Promise.resolve({}),
	})
);

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
	writable: true,
	value: jest.fn().mockImplementation((query) => ({
		matches: false,
		media: query,
		onchange: null,
		addListener: jest.fn(),
		removeListener: jest.fn(),
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		dispatchEvent: jest.fn(),
	})),
});

// Mock window.ResizeObserver
global.ResizeObserver = jest.fn().mockImplementation(() => ({
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn(),
}));

// Mock IntersectionObserver
global.IntersectionObserver = jest.fn().mockImplementation(() => ({
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn(),
}));

// Console error/warning suppression for cleaner test output
const originalError = console.error;
const originalWarn = console.warn;

beforeAll(() => {
	// Suppress specific React warnings during tests
	console.error = (...args) => {
		if (
			typeof args[0] === 'string' &&
			(args[0].includes('Warning: ReactDOM.render') ||
				args[0].includes('Warning: An update to') ||
				args[0].includes('act(...)'))
		) {
			return;
		}
		originalError.call(console, ...args);
	};

	console.warn = (...args) => {
		if (
			typeof args[0] === 'string' &&
			args[0].includes('componentWillReceiveProps')
		) {
			return;
		}
		originalWarn.call(console, ...args);
	};
});

afterAll(() => {
	console.error = originalError;
	console.warn = originalWarn;
});

// Reset mocks after each test
afterEach(() => {
	jest.clearAllMocks();
});
