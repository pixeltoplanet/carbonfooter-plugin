<?php
/**
 * Test bootstrap for CarbonFooter plugin
 *
 * @package CarbonFooter
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress ABSPATH so guarded plugin files don't exit during tests
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Define common WordPress time constants
if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);
if (!defined('HOUR_IN_SECONDS')) define('HOUR_IN_SECONDS', 3600);
if (!defined('DAY_IN_SECONDS')) define('DAY_IN_SECONDS', 86400);
if (!defined('WEEK_IN_SECONDS')) define('WEEK_IN_SECONDS', 604800);

// Define plugin-specific constants
if (!defined('CARBONFOOTER_VERSION')) define('CARBONFOOTER_VERSION', '0.19.0');
if (!defined('CARBONFOOTER_PLUGIN_DIR')) define('CARBONFOOTER_PLUGIN_DIR', dirname(__DIR__) . '/');
if (!defined('CARBONFOOTER_PLUGIN_URL')) define('CARBONFOOTER_PLUGIN_URL', 'https://example.com/wp-content/plugins/carbonfooter-plugin/');
if (!defined('CARBONFOOTER_PLUGIN_FILE')) define('CARBONFOOTER_PLUGIN_FILE', dirname(__DIR__) . '/carbonfooter.php');

// Define WP_DEBUG for test context
if (!defined('WP_DEBUG')) define('WP_DEBUG', true);

// Define OBJECT constant for wpdb
if (!defined('OBJECT')) define('OBJECT', 'OBJECT');
if (!defined('ARRAY_A')) define('ARRAY_A', 'ARRAY_A');
if (!defined('ARRAY_N')) define('ARRAY_N', 'ARRAY_N');

// Explicitly include Brain Monkey classes (belt-and-suspenders for some CI environments)
if (!class_exists('Brain\\Monkey\\Functions')) {
    $bmFunctions = __DIR__ . '/../vendor/brain/monkey/src/Functions.php';
    if (file_exists($bmFunctions)) {
        require_once $bmFunctions;
    }
}
if (!class_exists('Brain\\Monkey')) {
    $bmMonkey = __DIR__ . '/../vendor/brain/monkey/src/Monkey.php';
    if (file_exists($bmMonkey)) {
        require_once $bmMonkey;
    }
}

/**
 * Mock WP_Error class for testing
 */
if (!class_exists('WP_Error')) {
    class WP_Error
    {
        protected array $errors = [];
        protected array $error_data = [];
        protected string $code = '';
        protected string $message = '';

        public function __construct($code = '', $message = '', $data = '')
        {
            if (!empty($code)) {
                $this->add($code, $message, $data);
            }
            $this->code = $code;
            $this->message = $message;
        }

        public function add($code, $message, $data = '')
        {
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_codes(): array
        {
            return array_keys($this->errors);
        }

        public function get_error_code()
        {
            $codes = $this->get_error_codes();
            return $codes[0] ?? '';
        }

        public function get_error_messages($code = ''): array
        {
            if (empty($code)) {
                $all_messages = [];
                foreach ($this->errors as $messages) {
                    $all_messages = array_merge($all_messages, $messages);
                }
                return $all_messages;
            }
            return $this->errors[$code] ?? [];
        }

        public function get_error_message($code = ''): string
        {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            $messages = $this->get_error_messages($code);
            return $messages[0] ?? '';
        }

        public function get_error_data($code = '')
        {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return $this->error_data[$code] ?? null;
        }

        public function has_errors(): bool
        {
            return !empty($this->errors);
        }
    }
}

/**
 * Mock WP_REST_Response class for testing
 */
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        protected $data;
        protected int $status;
        protected array $headers = [];

        public function __construct($data = null, $status = 200, $headers = [])
        {
            $this->data = $data;
            $this->status = $status;
            $this->headers = $headers;
        }

        public function get_data()
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }

        public function get_headers(): array
        {
            return $this->headers;
        }

        public function set_data($data): void
        {
            $this->data = $data;
        }

        public function set_status(int $status): void
        {
            $this->status = $status;
        }

        public function header(string $key, string $value): void
        {
            $this->headers[$key] = $value;
        }
    }
}

/**
 * Mock WP_REST_Request class for testing
 */
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        protected array $params = [];
        protected string $method = 'GET';
        protected string $route = '';

        public function __construct($method = 'GET', $route = '', $attributes = [])
        {
            $this->method = $method;
            $this->route = $route;
        }

        public function get_param($key)
        {
            return $this->params[$key] ?? null;
        }

        public function set_param($key, $value): void
        {
            $this->params[$key] = $value;
        }

        public function get_params(): array
        {
            return $this->params;
        }

        public function get_method(): string
        {
            return $this->method;
        }

        public function get_route(): string
        {
            return $this->route;
        }
    }
}

/**
 * Helper function to check if value is WP_Error
 */
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

/**
 * Stub for trailingslashit
 */
if (!function_exists('trailingslashit')) {
    function trailingslashit(string $path): string
    {
        return rtrim($path, '/') . '/';
    }
}

/**
 * Stub for wp_get_upload_dir
 */
if (!function_exists('wp_get_upload_dir')) {
    function wp_get_upload_dir(): array
    {
        return [
            'basedir' => '/tmp/uploads',
            'baseurl' => 'https://example.com/wp-content/uploads',
            'path' => '/tmp/uploads/' . date('Y/m'),
            'url' => 'https://example.com/wp-content/uploads/' . date('Y/m'),
            'subdir' => '/' . date('Y/m'),
            'error' => false,
        ];
    }
}

/**
 * Stub for wp_mkdir_p
 */
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $target): bool
    {
        return true;
    }
}

/**
 * Stub for file_exists in namespace if needed
 */
if (!function_exists('CarbonfooterPlugin\\file_exists')) {
    // We don't override this as it should use native PHP file_exists
}
