# WordPress Plugin Security Best Practices

This document outlines security best practices for the Carbonfooter WordPress plugin.

## Data Validation and Sanitization

### Input Validation

Always validate user input before processing it:

```php
// Validate that a value is a positive integer
if ( ! is_numeric( $_POST['count'] ) || intval( $_POST['count'] ) <= 0 ) {
    wp_die( 'Invalid count value' );
}

// Validate that a value is in an allowed list
$allowed_types = array( 'daily', 'weekly', 'monthly' );
if ( ! in_array( $_POST['report_type'], $allowed_types, true ) ) {
    wp_die( 'Invalid report type' );
}
```

### Input Sanitization

Always sanitize user input before using it:

```php
// Sanitize text input
$title = sanitize_text_field( $_POST['title'] );

// Sanitize email
$email = sanitize_email( $_POST['email'] );

// Sanitize URL
$website = esc_url_raw( $_POST['website'] );

// Sanitize integer
$count = absint( $_POST['count'] );

// Sanitize textarea
$description = sanitize_textarea_field( $_POST['description'] );
```

## Output Escaping

Always escape data before outputting it to prevent XSS attacks:

```php
// Escape HTML content
echo esc_html( $title );

// Escape attributes
echo '<div class="' . esc_attr( $class_name ) . '">';

// Escape URLs
echo '<a href="' . esc_url( $url ) . '">';

// Escape JavaScript
echo '<script>var data = ' . wp_json_encode( $data ) . ';</script>';

// Escape translated strings
echo esc_html__( 'Message', 'carbonfooter' );
```

## Nonce Verification

Use nonces to protect against CSRF attacks:

```php
// Add a nonce to a form
wp_nonce_field( 'carbonfooter_action', 'carbonfooter_nonce' );

// Add a nonce to a URL
$url = wp_nonce_url( $url, 'carbonfooter_action' );

// Verify a nonce from a form
if ( ! isset( $_POST['carbonfooter_nonce'] ) || ! wp_verify_nonce( $_POST['carbonfooter_nonce'], 'carbonfooter_action' ) ) {
    wp_die( 'Security check failed' );
}

// Verify a nonce from a URL
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'carbonfooter_action' ) ) {
    wp_die( 'Security check failed' );
}
```

## Capability Checks

Always check user capabilities before performing privileged actions:

```php
// Check if user can manage options
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have sufficient permissions to access this page.' );
}

// Check if user can edit posts
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    wp_die( 'You do not have sufficient permissions to edit this post.' );
}
```

## Database Queries

### Prepare SQL Queries

Always use `$wpdb->prepare()` for SQL queries with variables:

```php
global $wpdb;

// Bad (vulnerable to SQL injection)
$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}carbonfooter_data WHERE user_id = $user_id" );

// Good (safe from SQL injection)
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}carbonfooter_data WHERE user_id = %d",
    $user_id
) );
```

### Use WordPress API Functions

Whenever possible, use WordPress API functions instead of direct SQL queries:

```php
// Instead of direct SQL to get post data
$post = get_post( $post_id );

// Instead of direct SQL to update post data
wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );

// Instead of direct SQL to get options
$option = get_option( 'carbonfooter_settings' );

// Instead of direct SQL to update options
update_option( 'carbonfooter_settings', $settings );
```

## File Operations

### Validate File Uploads

Always validate file uploads:

```php
// Check file type
$file_type = wp_check_filetype( $file['name'], array( 'jpg' => 'image/jpeg', 'png' => 'image/png' ) );
if ( ! $file_type['ext'] ) {
    wp_die( 'Invalid file type' );
}

// Check file size
if ( $file['size'] > 1048576 ) { // 1MB
    wp_die( 'File too large' );
}
```

### Secure File Operations

Use WordPress file system API for file operations:

```php
// Get WordPress filesystem
global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
    require_once ABSPATH . '/wp-admin/includes/file.php';
    WP_Filesystem();
}

// Read a file
$content = $wp_filesystem->get_contents( $file_path );

// Write to a file
$wp_filesystem->put_contents( $file_path, $content, FS_CHMOD_FILE );
```

## API Security

### Secure REST API Endpoints

Secure your REST API endpoints:

```php
// Register a REST API endpoint with permission callback
register_rest_route( 'carbonfooter/v1', '/data', array(
    'methods'             => 'GET',
    'callback'            => 'carbonfooter_api_get_data',
    'permission_callback' => function() {
        return current_user_can( 'read' );
    }
) );
```

### Validate and Sanitize API Data

Validate and sanitize data in API callbacks:

```php
function carbonfooter_api_get_data( $request ) {
    // Validate parameters
    $param = $request->get_param( 'id' );
    if ( empty( $param ) || ! is_numeric( $param ) ) {
        return new WP_Error( 'invalid_parameter', 'Invalid ID parameter', array( 'status' => 400 ) );
    }

    // Sanitize parameters
    $id = absint( $param );

    // Process request
    $data = get_data_by_id( $id );

    // Return response
    return rest_ensure_response( $data );
}
```

## Plugin Options

### Register Settings Properly

Register settings using the Settings API:

```php
// Register settings
function carbonfooter_register_settings() {
    register_setting(
        'carbonfooter_options',
        'carbonfooter_settings',
        array(
            'sanitize_callback' => 'carbonfooter_sanitize_settings',
            'default'           => array(
                'api_key'       => '',
                'enable_debug'  => false,
            ),
        )
    );
}
add_action( 'admin_init', 'carbonfooter_register_settings' );

// Sanitize settings
function carbonfooter_sanitize_settings( $input ) {
    $output = array();
    $output['api_key'] = sanitize_text_field( $input['api_key'] );
    $output['enable_debug'] = isset( $input['enable_debug'] ) ? (bool) $input['enable_debug'] : false;
    return $output;
}
```

## Error Handling

### Secure Error Handling

Handle errors securely without exposing sensitive information:

```php
// Log errors instead of displaying them
if ( ! $result ) {
    error_log( 'Carbonfooter plugin error: ' . $error_message );
    return false;
}

// Display user-friendly error messages
if ( ! $result ) {
    return new WP_Error( 'processing_error', __( 'There was an error processing your request. Please try again.', 'carbonfooter' ) );
}
```

## External Requests

### Validate External Data

Always validate data from external sources:

```php
// Make an external request
$response = wp_remote_get( $api_url );

// Check for errors
if ( is_wp_error( $response ) ) {
    return $response;
}

// Check response code
if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
    return new WP_Error( 'api_error', 'API returned an error' );
}

// Get and validate response body
$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );

if ( ! is_array( $data ) || ! isset( $data['required_field'] ) ) {
    return new WP_Error( 'invalid_response', 'Invalid API response' );
}
```

## Security Checklist

Before releasing your plugin, check the following:

1. ✅ All user input is validated and sanitized
2. ✅ All output is properly escaped
3. ✅ Nonces are used for all forms and actions
4. ✅ Capability checks are performed for all privileged actions
5. ✅ SQL queries are properly prepared
6. ✅ File operations are secure
7. ✅ API endpoints are properly secured
8. ✅ Settings are properly registered and sanitized
9. ✅ Errors are handled securely
10. ✅ External data is validated

## Resources

- [WordPress Plugin Security](https://developer.wordpress.org/plugins/security/)
- [WordPress Data Validation](https://developer.wordpress.org/themes/theme-security/data-validation/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [OWASP Top Ten](https://owasp.org/www-project-top-ten/)
