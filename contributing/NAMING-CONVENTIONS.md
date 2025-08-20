# WordPress Naming Conventions for Carbonfooter Plugin

This document provides detailed guidance on naming conventions for files, classes, functions, and variables in the Carbonfooter plugin, following WordPress best practices.

## File Naming

### Class Files

Class files should be prefixed with `class-` and use lowercase letters with hyphens separating words:

```
class-footer-emissions.php
class-api-client.php
class-dashboard-widget.php
```

### Function Files

Function files should use descriptive names with lowercase letters and hyphens:

```
template-functions.php
admin-functions.php
api-functions.php
```

### Template Files

Template files should use descriptive names with lowercase letters and hyphens, and end with `-template.php`:

```
dashboard-template.php
settings-template.php
```

## Class Naming

Classes should use CamelCase (capitalized words with no underscores):

```php
class FooterEmissions { }
class ApiClient { }
class DashboardWidget { }
```

For better organization and to avoid conflicts, consider using namespaces:

```php
namespace CarbonFooter;

class FooterEmissions { }
```

## Function Naming

### Plugin Functions

All public functions should be prefixed with the plugin name to avoid conflicts:

```php
function get_emissions() { }
function display_dashboard() { }
function register_settings() { }
```

Functions should use lowercase letters with underscores separating words.

### Class Methods

Methods within classes don't need the plugin prefix since they're already namespaced by the class:

```php
class FooterEmissions {
    public function get_emissions() { }
    public function display_average() { }
}
```

## Hook Naming

### Action Hooks

Custom action hooks should be prefixed with the plugin name:

```php
do_action( 'carbonfooter_after_calculation' );
do_action( 'carbonfooter_before_display' );
```

### Filter Hooks

Custom filter hooks should also be prefixed with the plugin name:

```php
apply_filters( 'carbonfooter_emission_value', $value );
apply_filters( 'carbonfooter_display_options', $options );
```

## Variable Naming

Variables should use lowercase letters with underscores separating words:

```php
$emission_value = 0;
$average_calculation = $total / $count;
$display_options = array();
```

## Constant Naming

Constants should use uppercase letters with underscores separating words and be prefixed with the plugin name:

```php
define( 'CARBONFOOTER_VERSION', '0.2.0' );
define( 'CARBONFOOTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARBONFOOTER_API_URL', 'https://carbonfooter.nl' );
```

## Database Table and Option Naming

### Database Tables

Custom database tables should be prefixed with the WordPress table prefix and the plugin name:

```php
global $wpdb;
$table_name = $wpdb->prefix . 'carbonfooter_emissions';
```

### Options

Options stored in the WordPress options table should be prefixed with the plugin name:

```php
update_option( 'carbonfooter_settings', $settings );
$settings = get_option( 'carbonfooter_api_key' );
```

## Transient Naming

Transients should be prefixed with the plugin name:

```php
set_transient( 'carbonfooter_api_response', $data, HOUR_IN_SECONDS );
$cached_data = get_transient( 'carbonfooter_calculation_results' );
```

## Meta Keys

Custom post meta or user meta keys should be prefixed with the plugin name:

```php
update_post_meta( $post_id, 'carbonfooter_page_emissions', $value );
update_user_meta( $user_id, 'carbonfooter_dashboard_settings', $settings );
```

## Examples

### Good Examples

```php
// Good file name
// class-footer-emissions.php

namespace CarbonFooter;

/**
 * Class FooterEmissions
 */
class FooterEmissions {
    private $emission_value;

    public function get_emissions() {
        return apply_filters( 'carbonfooter_emission_value', $this->emission_value );
    }
}

// Good function name
function carbonfooter_calculate_average( $values ) {
    // Function code
}

// Good constant name
define( 'CARBONFOOTER_API_TIMEOUT', 30 );
```

### Bad Examples

```php
// Bad file name
// footerEmissions.php or FooterEmissions.php

// Bad class name
class footer_emissions { }

// Bad function name (no prefix)
function calculate_average( $values ) { }

// Bad constant name (no prefix)
define( 'API_TIMEOUT', 30 );
```

## Conclusion

Following these naming conventions will ensure that your plugin:

1. Integrates well with WordPress
2. Avoids conflicts with other plugins
3. Is easier to maintain and understand
4. Follows WordPress coding standards

Remember that consistency is key. Choose a naming pattern and stick with it throughout your plugin.
