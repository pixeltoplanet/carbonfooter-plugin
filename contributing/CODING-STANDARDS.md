# Carbonfooter Plugin Coding Standards

This document outlines the coding standards and best practices for the Carbonfooter WordPress plugin.

## File Organization

1. **Class Files**:

   - Class files should be prefixed with `class-` (e.g., `class-footer-emissions.php`)
   - One class per file
   - File name should match the class name (lowercase with hyphens)

2. **Function Files**:

   - Function files should use descriptive names (e.g., `template-functions.php`)

3. **Directory Structure**:
   - `/inc/` - Core functionality classes and functions
   - `/dashboard/` - Admin dashboard functionality
   - `/assets/` - CSS, JS, and image files

## Naming Conventions

1. **Functions and Methods**:

   - Use lowercase letters
   - Words separated by underscores
   - Prefix all functions with `carbonfooter_` (e.g., `carbonfooter_get_emissions()`)
   - Method names don't need the prefix (e.g., `get_emissions()`)

2. **Classes**:

   - Use capitalized words
   - No underscores between words (CamelCase)
   - Prefix with a unique identifier (e.g., `CarbonFooter_Emissions`)

3. **Variables**:

   - Use lowercase letters
   - Words separated by underscores
   - Descriptive names

4. **Constants**:
   - Use uppercase letters
   - Words separated by underscores
   - Prefix with `CARBONFOOTER_` (e.g., `CARBONFOOTER_VERSION`)

## Documentation

1. **File Headers**:

   ```php
   <?php
   /**
    * Short description of the file.
    *
    * Longer description of the file if needed.
    *
    * @package CarbonFooter
    * @since 0.2.0
    */
   ```

2. **Class Documentation**:

   ```php
   /**
    * Class name and short description.
    *
    * Longer description if needed.
    *
    * @package CarbonFooter
    * @since 0.2.0
    */
   class Class_Name {
   ```

3. **Method Documentation**:

   ```php
   /**
    * Short description.
    *
    * Longer description if needed.
    *
    * @since 0.2.0
    * @param string $param Description of the parameter.
    * @return string Description of the return value.
    */
   public function method_name( $param ) {
   ```

4. **Property Documentation**:
   ```php
   /**
    * Short description.
    *
    * @var string
    * @since 0.2.0
    */
   private $property_name;
   ```

## Formatting

1. **Indentation**:

   - Use tabs for indentation
   - Configure your editor to display tabs as 4 spaces width

2. **Braces**:

   - Opening brace on the same line as the declaration
   - Closing brace on its own line
   - Always use braces, even for single-line statements

3. **Spaces**:

   - Add a space after commas
   - Add a space around operators (=, +, -, \*, /, etc.)
   - Add a space after control structure keywords (if, for, while, etc.)
   - Add spaces inside parentheses for control structures
   - No spaces inside function call parentheses

4. **Line Length**:
   - Keep lines under 100 characters when possible

## Security

1. **Input Validation**:

   - Always validate and sanitize user input
   - Use WordPress functions like `sanitize_text_field()`, `absint()`, etc.

2. **Output Escaping**:

   - Always escape output before displaying it
   - Use WordPress functions like `esc_html()`, `esc_attr()`, `esc_url()`, etc.

3. **Nonce Verification**:
   - Use nonces for all form submissions
   - Verify nonces before processing form data

## Example

Here's an example of a well-formatted class file following WordPress standards:

```php
<?php
/**
 * Footer emissions functionality.
 *
 * This file handles the display of average carbon emissions in the footer.
 *
 * @package CarbonFooter
 * @since 0.2.0
 */

namespace CarbonFooter;

/**
 * Class FooterEmissions
 *
 * Handles the display of average carbon emissions in the footer.
 *
 * @package CarbonFooter
 * @since 0.2.0
 */
class FooterEmissions {

	/**
	 * The emissions instance.
	 *
	 * @var Emissions
	 * @since 0.2.0
	 */
	private $emissions;

	/**
	 * Constructor.
	 *
	 * Initializes the class and sets up the shortcode.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_shortcode( 'carbonfooter_average', array( $this, 'display_average_emissions' ) );
		$this->emissions = new Emissions();
	}

	/**
	 * Display average emissions.
	 *
	 * Outputs the average carbon emissions for tested pages.
	 *
	 * @since 0.2.0
	 * @return string The HTML output for the average emissions.
	 */
	public function display_average_emissions() {
		$average_emissions = $this->emissions->get_average_emissions();

		if ( $average_emissions > 0 ) {
			return sprintf(
				'<div class="carbonfooter-average">Gemiddelde CO2 uitstoot per geteste pagina: <span class="carbon-footer-value">%s</span> gr.</div>',
				esc_html( $average_emissions )
			);
		}
		return '';
	}
}
```

## Automated Checking

Use the following commands to check and fix your code:

1. Check coding standards:

   ```
   composer lint
   ```

2. Automatically fix issues:

   ```
   composer fix
   ```

3. Fix a specific file:
   ```
   composer fix-file path/to/file.php
   ```

## Resources

- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/)
- [WordPress Security Best Practices](https://developer.wordpress.org/plugins/security/)
