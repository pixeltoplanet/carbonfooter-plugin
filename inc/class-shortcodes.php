<?php

/**
 * Footer emissions functionality.
 *
 * This file handles the display of carbon emissions through shortcodes.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Shortcodes
 *
 * Handles emissions widget rendering via shortcodes and optional automatic
 * insertion into the footer. Provides three styles: minimal, sticker, full.
 *
 * Responsibilities:
 * - Render UI fragments with proper escaping and limited inline styles
 * - Fetch emissions for the current page or fall back to average
 * - Support localized messages and accessible markup
 */
class Shortcodes
{



	/**
	 * Emissions instance.
	 *
	 * @var Emissions
	 */
	private $emissions;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		// Initialize dependencies
		$this->emissions = new Emissions();

		// Add main shortcode (uses style from settings)
		add_shortcode('carbonfooter', array($this, 'render_carbonfooter'));

		// Allow SVG in WordPress
		add_filter('wp_kses_allowed_html', array($this, 'allow_svg_in_kses'), 10, 2);

		// Add footer hook if enabled in settings
		add_action('wp_footer', array($this, 'maybe_add_to_footer'), 50);
	}

	/**
	 * Allow SVG elements in wp_kses.
	 *
	 * Extends the allowed tags for the 'post' context to support inline icons
	 * within the widget markup.
	 *
	 * @param array  $allowed_html Allowed tags
	 * @param string $context      Kses context
	 * @return array Modified allowed tags
	 */
	public function allow_svg_in_kses($allowed_html, $context)
	{
		if ($context === 'post') {
			$allowed_html['svg']  = array(
				'class'   => array(),
				'fill'    => array(),
				'xmlns'   => array(),
				'viewbox' => array(),
				'width'   => array(),
				'height'  => array(),
			);
			$allowed_html['path'] = array(
				'd' => array(),
			);
		}
		return $allowed_html;
	}

	/**
	 * Get common CSS variables.
	 *
	 * Returns a string to be used in a `style` attr to pass CSS custom props
	 * (background/text colors) to the inline widget styles.
	 *
	 * @return string CSS variables string
	 */
	private function get_common_css_vars()
	{
		return '
      --cf-container-width: 1200px;
      --cf-color-background: ' . esc_attr(get_option('carbonfooter_widget_background_color', '#000000')) . ';
      --cf-color-foreground: ' . esc_attr(get_option('carbonfooter_widget_text_color', '#FFFFFF')) . ';
    ';
	}

	/**
	 * Enqueue widget styles for a specific style type.
	 *
	 * Registers and enqueues styles for the widget shortcode using wp_add_inline_style().
	 *
	 * @param string $style_type The style type (minimal, sticker, full)
	 * @return void
	 */
	private function enqueue_widget_styles(string $style_type): void
	{
		$handle = 'carbonfooter-widget-' . $style_type;

		// Register style handle with empty source (inline-only styles)
		wp_register_style($handle, '', array(), CARBONFOOTER_VERSION);
		wp_enqueue_style($handle);

		// Get the appropriate styles based on type
		$styles = '';
		switch ($style_type) {
			case 'minimal':
				$styles = $this->get_minimal_styles_content();
				break;
			case 'sticker':
				$styles = $this->get_sticker_styles_content();
				break;
			case 'full':
				$styles = $this->get_full_styles_content();
				break;
			case 'label':
				$styles = $this->get_label_styles_content();
				break;
		}

		// Add inline styles
		wp_add_inline_style($handle, $styles);
	}

	/**
	 * Get label widget styles content (without style tags).
	 *
	 * @return string CSS content
	 */
	private function get_label_styles_content()
	{
		return '
      .cf-label {
        background-color: var(--cf-color-background);
        color: var(--cf-color-foreground);
				border-radius: 99px;
        padding: 4px 8px;
        font-size: 12px;
        display: inline-block;
      }
      .cf-label__value {
        font-weight: bold;
      }
      .cf-label__link {
        color: inherit;
        text-decoration: underline;
      }
      .cf-label__link:hover {
        color: inherit;
      }
    ';
	}

	/**
	 * Get minimal widget styles content (without style tags).
	 *
	 * @return string CSS content
	 */
	private function get_minimal_styles_content()
	{
		return '
      .cf-minimal {
        background-color: var(--cf-color-background);
        color: var(--cf-color-foreground);
        padding: 8px 0 16px;
        font-size: 14px;
      }
      .cf-minimal__content {
        display: flex;
        justify-content: center;
        text-align: center;
        width: 90%;
        max-width: 900px;
        margin: 0 auto;
      }
      .cf-minimal__text {
        font-size: 14px;
      }
      .cf-minimal__text a,
      .cf-minimal__link {
        color: var(--cf-color-foreground);
        text-decoration: none;
      }
      .cf-minimal__link:hover {
        text-decoration: underline;
      }
      .cf-minimal__value {
        font-weight: bold;
      }
      .cf-minimal__link {
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
      }
      .cf-minimal__link-icon {
        --icon-size: 24px;
        width: var(--icon-size);
        height: var(--icon-size);
      }
    ';
	}

	/**
	 * Get sticker widget styles content (without style tags).
	 *
	 * @return string CSS content
	 */
	private function get_sticker_styles_content()
	{
		return '
      .cf-sticker {
        aspect-ratio: 1/1;
        width: 100%;
        max-width: 300px;
        color: var(--cf-color-foreground);
        display: grid;
        place-items: center;
        justify-content: center;
        font-size: 16px;
        position: relative;
        line-height: 1.2;
        margin-left: auto;
      }
      .cf-sticker__cloud {
        --icon-size: 100%;
        width: var(--icon-size);
        height: var(--icon-size);
        position: absolute;
        top: 0;
        left: 0;
      }
      .cf-sticker__text {
        text-wrap: balance;
        position: relative;
        z-index: 1;
        text-align: center;
        padding: 80px;
        margin-left: 20px;
        line-height: 1.2;
      }
      .cf-sticker__value {
        font-weight: bold;
      }
    ';
	}

	/**
	 * Get full widget styles content (without style tags).
	 *
	 * @return string CSS content
	 */
	private function get_full_styles_content()
	{
		return '
      
    ';
	}

	/**
	 * Conditionally add emissions widget to the footer.
	 *
	 * Only injects on the frontend when `carbonfooter_display_setting` is set to
	 * `auto`. Respects admin/AJAX contexts.
	 *
	 * @return void
	 */
	public function maybe_add_to_footer()
	{
		// Don't show in admin or during AJAX requests
		if (\is_admin() || \wp_doing_ajax()) {
			return;
		}

		// Get display setting - check if auto display is enabled
		$display_setting = get_option('carbonfooter_display_setting', 'shortcode');

		// If not set to auto, don't show anything
		if ($display_setting !== 'auto') {
			return;
		}

		// Wrap in a div with clear styling to avoid theme conflicts
		echo '<div id="carbonfooter" class="carbonfooter">';
		echo do_shortcode('[carbonfooter]');
		echo '</div>';
	}

	/**
	 * Render the carbonfooter shortcode based on widget style setting or shortcode attribute.
	 *
	 * Supports [carbonfooter style="minimal|full|sticker|label"]. The label style is
	 * shortcode-only and not available for auto injection.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The shortcode output.
	 */
	public function render_carbonfooter($atts = array())
	{
		$atts         = shortcode_atts(array('style' => ''), $atts, 'carbonfooter');
		$valid_styles = array('minimal', 'full', 'sticker', 'label');
		$widget_style = ! empty($atts['style']) && in_array($atts['style'], $valid_styles, true)
			? $atts['style']
			: get_option('carbonfooter_widget_style', 'minimal');

		// Label is shortcode-only: when called from auto injection (no atts), never use label.
		if (empty($atts['style']) && 'label' === $widget_style) {
			$widget_style = 'minimal';
		}

		switch ($widget_style) {
			case 'label':
				return $this->render_label();
			case 'sticker':
				return $this->render_sticker();
			case 'full':
				return $this->render_full();
			case 'minimal':
			default:
				return $this->render_minimal();
		}
	}

	/**
	 * Render minimal emissions display.
	 *
	 * @return string The shortcode output.
	 */
	public function render_minimal()
	{
		// Enqueue styles for this widget type
		$this->enqueue_widget_styles('minimal');

		$emissions = $this->get_current_page_emissions();
		$link      = 'https://carbonfooter.nl/';
		$icon      = '<svg class="cf-minimal__link-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768">
    <path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z" />
    <path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z" />
  </svg>';

		ob_start();
		$css_vars = $this->get_common_css_vars();
?>
		<div id="carbonfooter" style="<?php echo esc_attr($css_vars); ?>">
			<div class="cf-minimal">
				<div class="cf-minimal__content">
					<p class="cf-minimal__text">
						<?php
						$emissions_value = '<span class="cf-minimal__value">' . esc_html($emissions['emissions']) . ' g CO<sub>2</sub></span>';
						$link_text       = '<a class="cf-minimal__link" href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer"><span>' . esc_html__('Carbonfooter.nl', 'carbonfooter') . '</span>' . wp_kses_post($icon) . '</a>';

						echo wp_kses_post(
							sprintf(
								/* translators: %1$s is the CO2 emissions HTML span, %2$s is the Carbonfooter.nl link */
								esc_html__('This page produced %1$s per page view. Measure more? %2$s', 'carbonfooter'),
								$emissions_value,
								$link_text
							)
						);
						?>
					</p>
				</div>
			</div>
		</div>
	<?php
		return ob_get_clean();
	}

	/**
	 * Render label emissions display (shortcode only, subtle badge).
	 *
	 * @return string The shortcode output.
	 */
	public function render_label()
	{
		$this->enqueue_widget_styles('label');

		$emissions = $this->get_current_page_emissions();
		$link      = 'https://carbonfooter.nl/';
		$icon = '<svg class="cf-label__link-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768">
    <path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z" />
    <path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z" />
  </svg>';

		ob_start();
		$css_vars = $this->get_common_css_vars();
	?>
		<div id="carbonfooter" style="<?php echo esc_attr($css_vars); ?>">
			<span class="cf-label">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %1$s is the CO2 emissions value, %2$s is Carbonfooter.nl link */
						esc_html__('%1$s g CO2 / visit %2$s', 'carbonfooter'),
						'<span class="cf-label__value">' . esc_html($emissions['emissions']) . '</span>',
						'<a class="cf-label__link" href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer">' . $icon . '</a>'
					)
				);
				?>
			</span>
		</div>
	<?php
		return ob_get_clean();
	}

	/**
	 * Render sticker emissions display.
	 *
	 * @return string The shortcode output.
	 */
	public function render_sticker()
	{
		// Enqueue styles for this widget type
		$this->enqueue_widget_styles('sticker');

		$emissions = $this->get_current_page_emissions();
		$link      = 'https://carbonfooter.nl/';
		$icon      = '<svg class="cf-sticker__cloud" xmlns="http://www.w3.org/2000/svg" fill="var(--cf-color-background)" viewBox="0 0 995 768"><path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z"/><path class="cls-1" d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z"/><path class="cls-1" d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z"/></svg>';

		ob_start();
		$css_vars = $this->get_common_css_vars();
	?>
		<div id="carbonfooter" style="<?php echo esc_attr($css_vars); ?>">
			<div class="cf-sticker">
				<?php echo wp_kses_post($icon); ?>
				<p class="cf-sticker__text">
					<?php
					$emissions_value = '<span class="cf-sticker__value">' . esc_html($emissions['emissions']) . ' g CO<sub>2</sub></span>';

					echo wp_kses_post(
						sprintf(
							/* translators: %s is the CO2 emissions HTML span */
							esc_html__('This page produced %s per page view.', 'carbonfooter'),
							$emissions_value
						)
					);
					?>
				</p>
			</div>
		</div>
	<?php
		return ob_get_clean();
	}

	/**
	 * Render full emissions display.
	 *
	 * @return string The shortcode output.
	 */
	public function render_full()
	{
		// Enqueue styles for this widget type
		$this->enqueue_widget_styles('full');

		$emissions = $this->get_current_page_emissions();
		$average   = get_option('carbonfooter_average_emissions', 0);
		$link      = 'https://carbonfooter.nl/';

		$emissions_value     = esc_html($emissions['emissions']);
		$page_size_value     = esc_html($emissions['page_size']);
		$formatted_page_size = $page_size_value . ' bytes';

		if ($page_size_value >= 1024 && $page_size_value < 1024 * 1024) {
			$formatted_page_size = round($page_size_value / 1024, 0) . ' KB';
		}

		if ($page_size_value >= 1024 * 1024 && $page_size_value < 1024 * 1024 * 1024) {
			$formatted_page_size = round($page_size_value / (1024 * 1024), 2) . ' MB';
		}

		// Get green host status
		$is_green_host = (bool) get_option('carbonfooter_greenhost', false);
		// $icon = $this->get_cta_icon();
		$icon = '<svg class="cf-full__cta-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768">
    <path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z" />
    <path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z" />
  </svg>';

		ob_start();
	?>

		<div id="carbonfooter" class="cf-full">
			<div class="cf-full__minimal-fallback">
				<?php
				$emissions_value_html = '<span class="cf-minimal__value">' . esc_html($emissions['emissions']) . ' g CO<sub>2</sub></span>';
				$icon_minimal         = '<svg class="cf-minimal__link-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768" style="width:20px;height:20px;vertical-align:middle"><path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z" /><path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z" /></svg>';
				$link_text            = '<a class="cf-minimal__link" href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer"><span>' . esc_html__('Carbonfooter.nl', 'carbonfooter') . '</span>' . wp_kses_post($icon_minimal) . '</a>';
				echo wp_kses_post(
					sprintf(
						/* translators: %1$s is the CO2 emissions HTML span, %2$s is the Carbonfooter.nl link text */
						esc_html__('This page produced %1$s per page view. Measure more? %2$s', 'carbonfooter'),
						$emissions_value_html,
						$link_text
					)
				);
				?>
			</div>

			<div class="cf-full__header">
				<p class="cf-full__intro"><?php echo esc_html__('How green is this webpage?', 'carbonfooter'); ?></p>
				<div class="cf-full__cta">
					<a class="cf-full__cta-link" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo wp_kses_post(__('Results are measured with <span class="cf-full__value">carbonfooter</span>', 'carbonfooter')); ?><?php echo wp_kses_post($icon); ?></a>
				</div>
			</div>

			<div class="cf-full__row">
				<div class="cf-full__col green-host">
					<svg class="cf-full__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 245.94 250">
						<path class="cls-1" d="M221.2,121.59l-18.68,8.2.67,1.55,18.68-8.2-.67-1.55ZM202.89,79.92l-18.7,8.25.66,1.55,18.7-8.25-.67-1.55ZM88.48,142.29l.09.2c18.64-8.39,28.23-24.38,34.17-34.26,2.92-4.96,5.07-8.55,7.56-9.62l28.59-12.55c1.3-.6,2.84.01,3.38,1.29l2.53,5.68,16.31-7.14.67,1.55,19.44-8.57c1.94-.84,4.22,0,5.06,1.96.84,1.95-.01,4.23-1.96,5.07l-19.44,8.57.67,1.55-16.31,7.14,13.82,31.55,16.38-7.17.67,1.55,19.44-8.57c1.95-.84,4.21.06,5.04,2.01.86,1.9,0,4.18-1.96,5.07l-19.42,8.52.67,1.55-16.33,7.19,2.27,5.19c.58,1.34-.01,2.83-1.31,3.43l-28.59,12.55c-2.5,1.12-6.68.29-12.44-.89-11.27-2.31-29.35-5.95-48.07,2.07l1.09,2.44-16.73,7.37-4.32-9.85c-17.72,6.64-26.26,15.93-32.67,22.95-11.61,12.68-17.92,19.58-44.3,3.23-2.4-1.51-3.21-4.67-1.68-7.12,1.47-2.42,4.65-3.16,7.08-1.69,19.15,11.85,23.44,7.22,31.26-1.36,7.16-7.78,16.56-18.11,36.12-25.46l-4.55-10.38,16.73-7.37,1.06,2.37Z" />
						<path class="cls-1" d="M180.9,26.39c.36.75.4,1.62.1,2.4l-10.14,27.85h0c-.6,1.63-2.4,2.47-4.03,1.88-1.63-.59-2.47-2.39-1.88-4.03l10.14-27.85h0c.43-1.2,1.55-2.01,2.81-2.06,1.26-.06,2.45.66,2.98,1.82Z" />
						<path class="cls-1" d="M112.48,30.65l12.53,26.87c.74,1.57.06,3.44-1.51,4.17-1.58.73-3.44.05-4.17-1.52l-12.53-26.87c-.73-1.57-.05-3.44,1.52-4.17,1.58-.73,3.44-.05,4.17,1.52Z" />
						<path class="cls-1" d="M96.79,90.37c.54,1.15.33,2.52-.53,3.44-.86.95-2.2,1.27-3.4.84l-27.84-10.14c-1.64-.59-2.47-2.39-1.88-4.03.59-1.64,2.39-2.47,4.02-1.87l27.85,10.14h0c.79.29,1.43.86,1.78,1.62Z" />
						<path class="cls-1" d="M127.04,223.61c-.36-.75-.4-1.62-.1-2.4l10.14-27.85h0c.6-1.63,2.4-2.47,4.03-1.88,1.63.59,2.47,2.39,1.88,4.03l-10.14,27.85h0c-.43,1.2-1.55,2.01-2.81,2.06-1.26.06-2.45-.66-2.98-1.82Z" />
						<path class="cls-1" d="M195.46,219.35l-12.53-26.87c-.74-1.57-.06-3.44,1.52-4.17,1.57-.73,3.44-.05,4.17,1.52l12.53,26.87c.73,1.57.05,3.44-1.52,4.17-1.58.73-3.44.05-4.17-1.52Z" />
						<path class="cls-1" d="M211.15,159.63c-.54-1.15-.33-2.52.53-3.44.86-.95,2.2-1.27,3.4-.84l27.84,10.14c1.64.59,2.47,2.39,1.88,4.03-.59,1.64-2.39,2.47-4.02,1.87l-27.85-10.14h0c-.79-.29-1.43-.86-1.78-1.62Z" />
					</svg>

					<div class="cf-full__text">
						<p><?php
								echo wp_kses_post(
									sprintf(
										/* translators: %1$s: Yes! or No!, %2$s: green energy or does not run on green energy */
										__('<span class="cf-full__value">%1$s</span> %2$s', 'carbonfooter'),
										$is_green_host ? esc_html__('YES', 'carbonfooter') : esc_html__('NO', 'carbonfooter'),
										$is_green_host ? esc_html__('It runs on green energy', 'carbonfooter') : esc_html__('It does not run on green energy', 'carbonfooter')
									)
								);
								?></p>
					</div>
				</div>
				<div class="cf-full__col page-size">

					<svg class="cf-full__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156.76 154.49">
						<path d="M138.56,52.41l-8.38,50.39c-.16.96-1.18,1.24-1.83.86l-65.11-37.59,8.69-52.25c.18-1.11,1.13-1.91,2.25-1.91.4,0,.78.1,1.13.31l60.85,35.13c1.77,1.02,2.73,3.06,2.4,5.07h0ZM129.29,111.03l-41.17,23.77c-2.01,1.16-4.5,1.16-6.51,0l-62.87-36.3c-.31-.18-.53-.47-.6-.82,0-.03-.02-.05-.03-.07v-2.37s.08.07.12.1l26.09,15.06.17.35c.33.69.85,1.26,1.52,1.64l5.64,3.26c.84.49,1.8.77,2.77.81l.44.02,27.22,15.71c.86.5,1.83.75,2.79.75s1.93-.25,2.79-.75l42.1-24.31v2.32c0,.34-.17.64-.47.81h0ZM54.15,115.42c-.7-.08-1.38-.29-1.99-.64l-5.64-3.25c-.4-.23-.71-.57-.95-.97h.28c.47,0,.97.12,1.4.37l5.82,3.36c.46.27.84.66,1.08,1.14h0ZM18.75,92.27l42.83-24.72c.68-.39,1.53-.39,2.2,0t0,0l63.64,36.74h0l1.87,1.08h0c.29.17.47.48.47.81s-.18.64-.47.81l-42.14,24.33c-1.41.81-3.16.81-4.57,0l-27.19-15.7-.35-.67c-.33-.65-.84-1.19-1.48-1.55l-5.82-3.36c-.58-.34-1.26-.54-1.92-.51h-.98s-26.1-15.07-26.1-15.07c-.4-.23-.63-.64-.63-1.09,0-.46.24-.87.63-1.1h0ZM136.68,46.45L75.84,11.32c-.5-.29-1.07-.44-1.64-.44-1.62,0-2.99,1.16-3.26,2.76l-8.75,52.6s0,.04,0,.06c-.39.06-.77.17-1.12.37l-42.82,24.73c-.71.41-1.14,1.15-1.14,1.97,0,0,0,0,0,0v4.43c0,.13.05.24.13.33.17.53.52.98,1.02,1.26l62.86,36.3c1.16.67,2.46,1.01,3.76,1.01s2.6-.33,3.76-1.01l41.17-23.77c.61-.35.98-.99.98-1.69v-4.04h0c0-.65-.31-1.22-.83-1.58.64-.31,1.11-.91,1.24-1.65l8.38-50.39c.4-2.43-.76-4.89-2.89-6.12h0Z" />
						<path d="M126.57,98.39l-59.21-34.18,7.79-45.98,59.21,34.19-7.79,45.98ZM135.39,51.95s-.01-.03-.03-.05c-.04-.08-.1-.15-.18-.2l-60.16-34.73c-.07-.04-.14-.06-.22-.06-.02,0-.04,0-.06,0-.06,0-.11.01-.16.03-.01,0-.02,0-.03,0-.01,0-.02.02-.03.03-.04.02-.08.06-.12.09-.02.02-.04.04-.05.06,0,.01-.02.02-.02.03-.02.04-.03.08-.04.13,0,.01-.02.03-.02.04l-7.98,47.05s0,0,0,.01c0,.04,0,.08,0,.12,0,.03,0,.05,0,.08,0,.03.03.06.04.09.01.03.02.06.04.09.01.02.04.03.05.05.03.03.06.06.09.08,0,0,0,0,0,0l60.13,34.72h0s.02.01.02.01c0,0,.02,0,.03,0,.04.02.09.04.14.05.03,0,.06,0,.08,0h0c.08,0,.15-.02.22-.06.02,0,.03-.02.05-.03.07-.04.13-.1.17-.17,0,0,0,0,0,0,.03-.05.05-.1.06-.16l7.98-47.05s0,0,0,0c.02-.09,0-.18-.03-.26h0Z" />
						<path d="M57.54,96.88c.25-.15.58-.15.83,0l18.86,10.89-11.58,6.68c-.25.14-.58.14-.83,0l-18.86-10.89,11.58-6.68ZM45.31,104.35l19.01,10.98c.28.16.6.25.92.25s.64-.09.92-.25l11.73-6.77c.28-.17.46-.46.46-.79s-.17-.63-.46-.79l-19.01-10.98c-.56-.32-1.28-.32-1.85,0l-11.73,6.77c-.29.16-.46.46-.46.79s.17.63.46.79Z" />
						<path d="M51.25,84.11l2.8,1.62-2.77,1.6-2.8-1.62,2.77-1.6ZM55.04,81.92l4.72,2.73-2.77,1.6-4.73-2.73,2.77-1.6ZM58.83,79.73l5.1,2.95h0s1.51.88,1.51.88l-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.27-.15l-4.85-2.8,2.77-1.6ZM62.61,77.55l3.57,2.06s0,0,0,0l.77.44-2.77,1.6-4.34-2.51,2.77-1.6ZM108.47,107.22l-5.08-2.94,2.77-1.6,5.08,2.93-2.77,1.6ZM104.68,109.41l-6.62-3.82,2.77-1.6,1.28.74s0,0,0,0l5.33,3.08-2.77,1.6ZM100.89,111.59l-2.8-1.62,2.77-1.6,2.8,1.62-2.77,1.6ZM69.27,85.76l-2.77,1.6-1.5-.86c-.07-.08-.16-.13-.27-.15l-1.04-.6,2.77-1.6,2.8,1.62ZM94.24,103.38l2.77-1.6,1.28.74s0,0,0,0l1.51.87-2.77,1.6-2.8-1.62ZM83.69,98.46l1.64.95h0l1.16.67-2.77,1.6-2.8-1.62,2.77-1.6ZM79.88,96.26l1.64.95s0,0,0,0l1.16.67-2.77,1.6-2.8-1.62,2.77-1.6ZM76.06,94.06l1.64.95,1.16.67-2.77,1.6-2.8-1.62,2.77-1.6ZM72.24,91.85l1.64.95s0,0,0,0l1.16.67-2.77,1.6-2.8-1.62,2.77-1.6ZM94.48,100.32s0,0,0,0l1.51.88-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.26-.15l-1.04-.6,2.77-1.6,1.28.74ZM90.66,98.11s0,0,0,0l1.51.87-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.26-.15l-1.04-.6,2.77-1.6,1.28.74ZM86.84,95.91s0,0,0,0l1.51.88-2.77,1.6-1.5-.86c-.07-.08-.16-.13-.26-.15l-1.04-.6,2.77-1.6,1.29.74ZM83.02,93.7s0,0,0,0l1.51.87-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.26-.15l-1.04-.6,2.77-1.6,1.28.74ZM79.2,91.5s0,0,0,0l1.51.87-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.26-.15l-1.04-.6,2.77-1.6,1.28.74ZM75.39,89.29h0s1.51.88,1.51.88l-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.26-.15l-1.04-.6,2.77-1.6,1.28.74ZM71.57,87.09s0,0,0,0l1.51.87-2.77,1.6-1.5-.87c-.07-.08-.16-.13-.27-.15l-1.04-.6,2.77-1.6,1.28.74ZM61.83,89.04l2.77-1.6,1.64.95,1.16.67-2.77,1.6-2.8-1.62ZM65.65,91.25l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM84.74,102.27l2.77-1.6,1.64.95,1.16.67-2.77,1.6-2.8-1.62ZM88.56,104.47l2.77-1.6,1.64.95s0,0,0,0l3.05,1.76-2.77,1.6-4.7-2.71ZM94.27,107.77l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM104.37,101.65h0s.77.45.77.45l-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM100.55,99.45s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM96.73,97.24h0s.77.45.77.45l-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM92.91,95.04s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM89.09,92.83l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM85.27,90.63s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM81.45,88.42s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM77.64,86.22s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM73.82,84.02s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM70,81.81s0,0,0,0l.77.44-2.77,1.6-2.8-1.62,2.77-1.6,2.03,1.17ZM63.59,86.85l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM52.3,87.91l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM56.12,90.12l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM59.94,92.32l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM63.76,94.53l2.77-1.6,18.07,10.44-2.77,1.6-18.08-10.44ZM82.85,105.55l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM86.67,107.75l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM90.49,109.96l2.77-1.6,2.8,1.62-2.77,1.6-2.8-1.62ZM97.1,113.78l-2.8-1.62,2.77-1.6,2.8,1.62-2.77,1.6ZM115.03,103.43l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM111.21,101.23l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM107.39,99.02l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM103.57,96.82l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM99.75,94.61l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM95.93,92.41l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM92.12,90.21l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM88.3,88l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM84.48,85.8l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM80.66,83.59l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM76.84,81.39l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM73.02,79.18l-2.77,1.6-2.8-1.62,2.77-1.6,2.8,1.62ZM66.4,75.36l2.8,1.62-2.77,1.6-2.8-1.62,2.77-1.6ZM46.97,85.8s.02.07.03.1c0,.02,0,.04.02.06,0,0,.01,0,.02.02.04.07.09.13.17h0s0,0,0,0l7.64,4.41s0,0,0,0l3.82,2.2,22.91,13.23s0,0,0,0l3.81,2.2s0,0,0,0l3.82,2.2h0l3.81,2.2s0,0,0,0l3.81,2.2h0c.07.04.14.05.21.06.01,0,.03,0,.04,0,.01,0,.02,0,.04,0,.08,0,.15-.02.22-.06h0l3.78-2.18s0,0,0,0h0s3.78-2.18,3.78-2.18c0,0,0,0,0,0h0s3.78-2.19,3.78-2.19c0,0,0,0,0,0h0s3.78-2.19,3.78-2.19c0,0,0,0,0,0h0s3.78-2.19,3.78-2.19c0,0,0,0,0,0h0c.07-.04.12-.1.17-.17,0,0,.01,0,.02-.02.01-.02.01-.04.02-.06.01-.03.03-.06.03-.1,0-.03,0-.06,0-.1s0-.06,0-.1c0-.03-.02-.07-.03-.1,0-.02-.01-.04-.02-.06,0,0-.02-.01-.02-.02-.02-.03-.04-.05-.07-.07-.03-.03-.05-.05-.08-.07,0,0-.01-.01-.02-.02l-49.63-28.66s-.04-.01-.06-.02c-.03-.01-.06-.03-.1-.03-.03,0-.06,0-.1,0s-.06,0-.1,0c-.04,0-.07.02-.1.03-.02,0-.04,0-.06.02l-18.94,10.93s-.01.01-.02.02c-.03.02-.05.04-.08.07-.02.02-.05.05-.07.08,0,0-.01.01-.02.02-.01.02-.01.04-.02.06-.01.03-.03.06-.03.1,0,.03,0,.06,0,.1s0,.06,0,.1h0Z" />
					</svg>

					<div class="cf-full__text">
						<p><?php
								echo wp_kses_post(
									sprintf(
										/* translators: %1$s: page size value (e.g. 105 kB), %2$s: page size label */
										__('<span class="cf-full__value">%1$s</span><span class="cf-full__block">= %2$s</span>', 'carbonfooter'),
										esc_html__('page size', 'carbonfooter'),
										esc_html($formatted_page_size)
									)
								);
								?></p>
					</div>
				</div>
				<div class="cf-full__col emissions">

					<svg class="cf-full__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 242.6 250">
						<path fill="currentColor" d="M46.99,208.56s14.22.99,14.46-10.7c.18-10.36-12.49-9.12-12.49-9.12,0,0-6.81-10.36-14.56-5.9-9.09,5.36-3.29,16.03-3.29,16.03,0,0-8.44,9.35,1.16,15.05,10.73,6.4,14.72-5.36,14.72-5.36Z" />
						<path fill="currentColor" d="M145.25,112.02c-1.83-.87-5.11-1.65-6.63,0-1.93,2.43-.93,8.87.81,11.11,2.8,3.67,5.57.31,7-2.65,1.43-2.96,2.21-6.85-1.18-8.47" />
						<path fill="currentColor" d="M177.18,141.22h-11.14c-1.56-.02-3-.86-3.78-2.21-.78-1.36-.79-3.02-.01-4.38,1.31-2.12,3.11-3.83,4.48-5.82l.19-.34c-1.49.25-3.02-.24-4.09-1.3-1.08-1.07-1.58-2.59-1.35-4.08,1.09-6.23,10.05-7.22,13.6-2.55,1.87,2.75,2.24,6.25,1,9.34-.34.91-.79,1.78-1.34,2.59h2.46c1.57,0,3.01.84,3.8,2.19.78,1.36.78,3.03,0,4.39-.79,1.36-2.23,2.19-3.8,2.19h0ZM152.81,127.03c-4.86,7.22-14.01,9.06-19.98,1.93-2.5-3.31-3.88-7.32-3.96-11.46-.08-4.14,1.15-8.21,3.53-11.6,4.6-5.14,13.48-4.11,18.67-.56,7.44,4.95,6.1,15.16,1.74,21.69ZM126.86,143.77c-8.96,2.87-20.76,1.09-25.15-8.21-1.92-4.67-2.03-9.88-.31-14.62,1.73-4.74,5.17-8.67,9.64-11,4.82-2.55,9.09,4.73,4.27,7.28-4.82,2.55-8.84,9.68-5.63,15.22,3.21,5.54,10.21,4.7,15,3.11,4.79-1.59,7.38,6.56,2.18,8.21ZM206.72,101.76c4.61-6.14,6.93-13.7,6.54-21.38-.39-7.67-3.45-14.96-8.66-20.61-18.43-21.78-45-9.06-45-9.06-3.78-4.38-8.65-7.69-14.11-9.6-5.46-1.91-11.33-2.36-17.02-1.29-5.99.83-11.66,3.18-16.47,6.83-4.81,3.65-8.6,8.48-11.01,14.02,0,0-15.72-15.41-36.57,6.23-20.85,21.63-3.92,40.18-3.92,40.18,0,0-25.68,10.86-16,37.75,2.65,7.58,7.59,14.15,14.14,18.8,6.54,4.66,14.37,7.16,22.4,7.19,0,0-1.21,35.91,26.89,40.46,7.64,1.5,15.56.49,22.58-2.87,7.02-3.37,12.77-8.91,16.39-15.81,0,0,12.45,28.01,41.67,11.11,29.23-16.9,18.43-32.77,18.43-32.77,0,0,29.6-3.33,32.03-30.53,2.43-27.2-32.31-38.65-32.31-38.65Z" />
					</svg>
					<div class="cf-full__text">
						<p><?php
								echo wp_kses_post(
									sprintf(
										/* translators: %1$s: emissions value in grams CO2, %2$s: per visit */
										__('emits <span class="cf-full__value">%1$s g CO<sub>2</sub></span> %2$s', 'carbonfooter'),
										number_format((float) $emissions['emissions'], 2),
										esc_html__('per visit', 'carbonfooter')
									)
								);
								?></p>
					</div>
				</div>
				<div class="cf-full__col driving-distance">
					<svg class="cf-full__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 438.9 250">
						<path class="cls-1" d="M167.29,144.85c-.88,0-1.6-.73-1.58-1.61,0-.34.1-.63.28-.89,1.48-2.16,2.83-4.29,4.19-6.45,4.48-7.09,9.04-14.29,17.34-21.26.29-.25.66-.38,1.02-.38h19.16c.85,0,1.55.66,1.59,1.49l2.4,27.7c.07.88-.57,1.66-1.46,1.74-.1.02-.19.02-.29,0l-42.66-.35ZM217.61,115.87c0-.89.72-1.61,1.61-1.61h42.83c.87,0,1.58.69,1.61,1.55l2.91,28.07c.09.88-.56,1.67-1.43,1.76-.09.02-.18.02-.26,0l-43.13-.35c-.88.07-1.64-.59-1.73-1.46l-2.42-27.79c0-.06-.01-.12-.01-.18h.01ZM224.93,157.49c0-.9.72-1.61,1.61-1.61h10.4c.89,0,1.61.72,1.61,1.61s-.72,1.61-1.61,1.61h-10.4c-.9,0-1.61-.72-1.61-1.61M406.55,161.48c0-.9.72-1.61,1.61-1.61h20.32c2.75,1.87,3.98,3.88,3.98,6.35v9.3l-20.14-4.53c-.44-.09-.82-.37-1.07-.78l-4.44-7.85c-.18-.26-.28-.57-.28-.9v.02ZM421.35,187.39c0-.9.72-1.61,1.61-1.61h9.51v10.55c0,.98-.18,1.87-.48,2.72h-9.02c-.89,0-1.61-.72-1.61-1.61v-10.07.02ZM282.93,104.74c-13.29-.54-26.26-.83-39-.89v-5.27h39v6.17ZM199.86,98.59h39v5.24c-13.23.01-26.19.31-39,.86v-6.11ZM271.97,115.88c0-.89.72-1.61,1.61-1.61h27.58c.37,0,.69.12.95.32,7.9,5.11,14.65,10.08,20.2,14.91,5.62,4.89,10.05,9.64,13.2,14.24.5.73.31,1.73-.42,2.23-.31.2-.66.29-1.01.28l-57.51-.48c-.83.03-1.57-.59-1.66-1.43l-2.91-28.16c-.02-.09-.03-.19-.03-.28v-.02ZM293.08,160.48h-11.09c-.89,0-1.61-.72-1.61-1.61s.72-1.61,1.61-1.61h11.09c.89,0,1.61.72,1.61,1.61s-.72,1.61-1.61,1.61M241.53,192.89c0-.9.72-1.61,1.61-1.61h95.42c.89,0,1.61.72,1.61,1.61s-.72,1.61-1.61,1.61h-95.42c-.9,0-1.61-.72-1.61-1.61M145.66,175.66v-21.83h11.01c.85,0,1.55.66,1.6,1.51l.4,7.18c.03.45-.15.91-.5,1.24l-12.53,11.9h.02ZM145.66,195.55v-11.21h7.28c.9,0,1.61.72,1.61,1.61v10.06c0,.9-.72,1.61-1.61,1.61h-6.97c-.18-.66-.31-1.35-.31-2.07h0ZM145.4,140.95c-1.93,2.15-2.91,4.68-2.91,7.56v47.05c0,6.29,5.23,11.41,11.66,11.43l15.05.04c.88,0,1.58-.7,1.58-1.58,0-.15-.04-.28-.09-.41.54-7.61,3.82-14.45,8.89-19.53,5.56-5.55,13.24-8.99,21.73-8.99s16.16,3.44,21.73,8.99c5.02,5.03,8.29,11.81,8.87,19.34-.13.23-.23.48-.23.78,0,.88.7,1.6,1.58,1.6l111.7.32c.88,0,1.58-.7,1.58-1.58,0-.29-.1-.56-.23-.79.64-6.87,3.57-13.58,8.83-18.85,5.99-5.99,13.86-8.99,21.73-8.99s15.72,3,21.73,8.99c5.32,5.32,8.25,12.1,8.85,19.06-.09.2-.16.43-.16.66,0,.88.7,1.6,1.57,1.6l15.06.13h.05c3.21,0,5.97-1.11,8.25-3.33,2.28-2.23,3.45-4.95,3.45-8.11v-30.13c0-5.96-4.91-9.07-9.18-11.12-24.1-11.51-65.25-13.43-70.38-13.64l-48.9-33.76c-1.88-1.29-3.93-1.93-6.65-2.1-4.22-.23-8.38-.44-12.52-.63v-6.39h10.63c.63,0,1.24-.23,1.71-.66l5.48-4.98c1.04-.95,1.11-2.55.16-3.59-.95-1.04-2.55-1.11-3.59-.16l-4.74,4.32h-112.58l-4.97-4.35c-1.05-.92-2.66-.82-3.59.23-.93,1.05-.82,2.66.23,3.59l5.68,4.98c.47.41,1.05.63,1.67.63h10.64v6.34c-4.12.2-8.23.42-12.32.67-3.69.23-6.53,1.49-8.67,3.86l-28.37,31.48v.02ZM215.61,200.56c.98,2.11,1.48,4.39,1.48,6.68s-.5,4.57-1.48,6.7l-6.68-6.68,6.68-6.68h0ZM205.98,202.57c-.53-.53-1.13-.95-1.8-1.28l6.64-6.63c.57.44,1.13.91,1.64,1.43.53.53,1,1.07,1.43,1.64l-6.64,6.64c-.32-.67-.76-1.28-1.27-1.8M201.31,203.86c.94,0,1.79.38,2.4,1s1,1.46,1,2.4-.38,1.79-1,2.4-1.46,1-2.4,1-1.79-.38-2.4-1-1-1.46-1-2.4.38-1.79,1-2.4,1.46-1,2.4-1M194.63,192.95c2.11-.98,4.39-1.48,6.69-1.48s4.57.5,6.68,1.48l-6.68,6.68-6.68-6.68h-.01ZM196.64,202.57c-.51.51-.95,1.13-1.28,1.79l-6.64-6.64c.44-.57.91-1.11,1.43-1.64.53-.51,1.07-1,1.64-1.42l6.63,6.63c-.67.32-1.27.76-1.79,1.28h-.01ZM205.98,211.92c.51-.51.95-1.13,1.27-1.79l6.64,6.64c-.44.57-.91,1.11-1.43,1.64-.53.53-1.07,1-1.64,1.42l-6.64-6.63c.67-.32,1.27-.76,1.79-1.28h.02ZM182.32,207.24c0-4.86,1.86-9.71,5.57-13.43,3.71-3.71,8.57-5.57,13.43-5.57s9.71,1.86,13.43,5.57c3.71,3.7,5.57,8.57,5.57,13.43s-1.86,9.71-5.57,13.43c-3.7,3.71-8.57,5.57-13.41,5.57s-9.72-1.86-13.44-5.57c-3.71-3.7-5.56-8.57-5.56-13.43h-.02ZM183.22,189.15c-4.63,4.63-7.5,11.03-7.5,18.09s2.86,13.46,7.5,18.09c4.63,4.63,11.03,7.5,18.09,7.5s13.46-2.86,18.09-7.5c4.63-4.63,7.5-11.03,7.5-18.09s-2.86-13.46-7.5-18.09c-4.63-4.63-11.03-7.49-18.09-7.49s-13.46,2.86-18.09,7.49M187.01,213.92c-.98-2.11-1.48-4.39-1.48-6.68s.5-4.57,1.48-6.69l6.68,6.68-6.68,6.68h0ZM208,221.54c-2.11.98-4.39,1.48-6.69,1.48s-4.57-.5-6.68-1.48l6.68-6.68,6.68,6.68h.01ZM196.64,211.92c.53.53,1.13.95,1.8,1.28l-6.64,6.64c-.57-.44-1.13-.91-1.64-1.44-.53-.53-1-1.07-1.43-1.64l6.63-6.64c.32.67.76,1.28,1.28,1.8M372.18,203.38c-.51.51-.95,1.13-1.27,1.79l-6.64-6.64c.44-.57.91-1.11,1.43-1.64.51-.53,1.07-1,1.64-1.43l6.64,6.64c-.67.32-1.28.76-1.79,1.27v.02ZM370.18,193.75c2.11-.98,4.39-1.48,6.7-1.48s4.57.5,6.68,1.48l-6.68,6.68-6.68-6.68h-.01ZM357.87,208.03c0-4.86,1.86-9.71,5.56-13.42,3.71-3.7,8.57-5.56,13.44-5.56s9.71,1.86,13.43,5.56c3.71,3.71,5.56,8.57,5.56,13.42s-1.86,9.72-5.56,13.44c-3.71,3.71-8.57,5.56-13.42,5.56s-9.71-1.86-13.43-5.56c-3.71-3.71-5.57-8.57-5.57-13.44h-.01ZM358.76,189.96c-5,4.99-7.49,11.54-7.5,18.09,0,6.55,2.49,13.08,7.5,18.09,5,5,11.54,7.49,18.09,7.49s13.1-2.49,18.09-7.49c5-5,7.5-11.54,7.5-18.09s-2.51-13.1-7.5-18.09c-4.99-5-11.54-7.5-18.09-7.5s-13.09,2.51-18.09,7.5M376.85,204.65c.94,0,1.79.38,2.4,1,.61.61,1,1.46,1,2.4s-.38,1.79-1,2.4c-.62.61-1.46,1-2.4,1s-1.79-.38-2.4-1c-.62-.61-1-1.46-1-2.4s.38-1.79,1-2.4c.61-.61,1.46-1,2.4-1M391.15,201.37c.98,2.11,1.48,4.39,1.48,6.68s-.5,4.57-1.48,6.69l-6.68-6.68,6.68-6.68h0ZM381.53,203.38c-.53-.53-1.13-.95-1.8-1.28l6.63-6.64c.57.44,1.13.91,1.64,1.44.53.53,1,1.07,1.43,1.64l-6.64,6.64c-.32-.67-.76-1.28-1.28-1.8M372.18,212.71c.53.53,1.13.95,1.8,1.27l-6.64,6.64c-.57-.44-1.13-.91-1.64-1.43-.53-.53-1-1.07-1.43-1.64l6.64-6.64c.32.67.76,1.27,1.27,1.8M362.56,214.72c-.98-2.11-1.48-4.39-1.48-6.68s.5-4.57,1.48-6.7l6.68,6.68-6.68,6.68v.02ZM124.09,130.19c.8-.81,1.93-1.32,3.15-1.32h9.49c1.23,0,2.34.5,3.15,1.32.81.81,1.32,1.93,1.32,3.15v44.15c0,1.23-.5,2.34-1.32,3.15-.8.81-1.93,1.32-3.15,1.32h-9.49c-1.23,0-2.34-.5-3.15-1.32-.81-.81-1.32-1.93-1.32-3.15v-44.15c0-1.23.5-2.34,1.32-3.15M383.56,222.33c-2.11.98-4.39,1.48-6.7,1.48s-4.57-.5-6.68-1.48l6.68-6.68,6.68,6.68h.02ZM379.75,213.98l6.64,6.64c.57-.44,1.11-.91,1.64-1.43.51-.53,1-1.07,1.42-1.64l-6.64-6.64c-.32.67-.76,1.27-1.28,1.79-.51.51-1.13.95-1.79,1.28h0Z" />
						<path class="cls-1" d="M97.23,153.07s-10.01.7-10.17-7.66c-.13-7.42,8.79-6.53,8.79-6.53,0,0,4.79-7.42,10.25-4.23,6.4,3.84,2.31,11.48,2.31,11.48,0,0,5.94,6.7-.82,10.78-7.55,4.58-10.36-3.84-10.36-3.84Z" />
						<path class="cls-1" d="M31.26,55.97c-2.7-3.66-4.06-8.17-3.83-12.74.23-4.57,2.02-8.92,5.07-12.29,10.8-12.99,26.36-5.4,26.36-5.4,2.22-2.61,5.07-4.58,8.26-5.72,3.2-1.14,6.64-1.41,9.97-.77,3.51.49,6.83,1.9,9.65,4.07,2.82,2.17,5.04,5.05,6.45,8.36,0,0,9.21-9.19,21.42,3.71,12.21,12.89,2.3,23.95,2.3,23.95,0,0,15.04,6.47,9.37,22.51-1.55,4.52-4.45,8.43-8.28,11.21-3.83,2.78-8.42,4.27-13.12,4.28,0,0,.71,21.41-15.75,24.12-4.47.9-9.11.29-13.23-1.71-4.11-2-7.48-5.31-9.6-9.42,0,0-7.29,16.7-24.41,6.62-17.12-10.07-10.79-19.54-10.79-19.54,0,0-17.34-1.99-18.76-18.2-1.42-16.22,18.92-23.04,18.92-23.04Z" />
					</svg>
					<div class="cf-full__text">
						<p><?php
								// Calculate annual driving distance based on 12k visitors
								// 1g CO2 = 5 meters = 0.005 km
								$annual_visitors  = 12000;
								$driving_distance = number_format(($emissions['emissions'] * 0.005) * $annual_visitors, 2);

								echo wp_kses_post(
									sprintf(
										/* translators: %s: km value for car drive equivalence */
										__('= same as a <span class="cf-full__value">%s km</span> drive', 'carbonfooter'),
										esc_html($driving_distance)
									)
								);
								?></p>
					</div>
				</div>
				<div class="cf-full__col trees-offset">
					<svg class="cf-full__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3237.31 3268.2">
						<path d="M3158.27,1355.44c-42.29-68.95-104.03-133.12-183.49-190.72-106.4-77.14-214.61-121.17-258.05-137.12,67.7-94.9,101.51-209.54,95.5-325.12-6.36-122.3-56.97-239.21-142.53-329.22-68.96-78.96-150.77-135.71-243.13-168.65-73.87-26.35-154.52-37.56-239.69-33.33-120.74,6-214.77,40.93-245.06,53.47-61.13-65.83-138.7-115.93-224.98-145.2-89.62-30.4-186.31-37.55-279.63-20.68-97.97,13.19-191.41,50.8-270.25,108.79-73.64,54.16-133.08,125.02-172.84,205.81-25.56-19.46-81.23-55.14-159.72-68.5-63.3-10.78-128.29-4.68-193.14,18.11-80.18,28.18-160.26,82.12-238.04,160.32-77.96,78.38-129.92,160.98-154.45,245.49-19.78,68.13-21.84,137.5-6.15,206.17,19.83,86.78,62.57,149.53,84.32,177.21-34.04,16.82-108.87,59.03-173.32,131.92-50.13,56.68-83.34,120.11-98.72,188.52-19.17,85.26-10.45,178.15,25.92,276.1,21.68,60,52.65,116.18,92.05,166.99,39.4,50.81,86.47,95.24,139.89,132.07,53.4,36.82,112.11,65.31,174.5,84.71,57.59,17.9,117.27,27.72,177.63,29.24.12,15.37.92,41.51,4.18,74.87,8.75,89.68,28.38,159.79,43.3,202.81,30.58,88.17,74.46,161.58,130.43,218.21,70.22,71.04,159.47,115.5,265.25,132.12,37.7,7.17,75.85,10.76,114.06,10.76,24.96,0,49.95-1.53,74.86-4.6,63.11-7.78,124.2-25.23,181.58-51.88,26.42-12.28,42.14-27.02,58.78-42.63,5.28-4.95,10.67-10,16.55-15.09l26.2,165.24c3.21,71.08-118.67,280.71-160.68,442.85l-3.24,12.49,530.46.25,2.78-5.51c4.14-8.22,8.86-112.48-108.95-528.24-13.16-58.53-23.14-110.46-29.81-155.14,44.83,47.11,110.29,98.42,197.12,123.17,35.9,10.23,73.02,15.34,111.28,15.34,90.8,0,187.99-28.77,290.24-86.09,102.63-57.53,182.93-119.08,238.66-182.97,46.08-52.81,75.82-107.61,88.41-162.88,17.43-76.54-1.94-135.12-15.75-164.26,40.48-6.93,130.98-26.83,225.29-77.06,73.49-39.15,133.81-88.66,179.26-147.16,56.97-73.32,90.49-160.8,99.62-260,9.16-99.55-13.21-194.08-66.51-280.96ZM3194.91,1633.65c-8.58,93.22-39.91,175.28-93.11,243.92-42.71,55.11-99.63,101.89-169.17,139.07-119.44,63.84-236.09,77.27-237.25,77.4l-25.26,2.71,14.58,20.78c.11.16,11.33,16.57,19.61,44.64,11.02,37.39,11.9,76.72,2.61,116.9-11.63,50.28-39.24,100.59-82.04,149.51-53.46,61.1-130.92,120.31-230.26,175.98-138.1,77.41-265.49,100.32-378.62,68.07-96.57-27.53-165.12-90.9-206.33-140.56-.3-2.74-.57-5.34-.82-7.87l-.53-5.32-2.87-2.88c-1.06-2.93-3.84-15.49-.09-61.72,2.99-36.92,9.62-88.72,19.71-153.99,14.85-33.93,30.45-66.75,46.39-97.6,32.72-63.35,56.62-108.08,79.92-149.58,24.96-44.45,50.69-87.68,76.49-128.5,27.24-43.1,52.9-80.84,74.2-109.13,20.28-26.93,39.17-47.8,61.24-67.66,20.26-18.23,41.63-34.47,63.52-48.25,11.16-7.03,22.72-13.73,32.92-19.64,10.71-6.21,19.97-11.57,26.37-15.92l76.6-52.08-89.12,25.24c-.25.07-.47.13-.68.19-5.23-1.73-11.74-1.99-25.9.31-16.64,1.23-45.24,10.31-73.28,23.3-27.71,12.84-54.76,29.33-80.39,49-28.02,21.52-53.73,46.55-83.35,81.17-32.19,37.62-63.57,77.33-93.28,118.03-9.67,13.24-19.43,26.96-29.24,41.08,22.99-108.42,47.5-197.78,73.06-266.32,44.02-101.12,86.73-200.13,126.98-294.39,3.85-1.44,7.6-2.85,11.27-4.22,23.44-8.78,43.69-16.36,59.54-23.34,8.5-3.74,16-7.01,22.33-9.78,7.89-3.44,14.13-6.16,18.29-8.05,8.17-3.71,16.68-7.27,26.02-10.86,8.57-3.3,18.17-6.87,29.29-11.01l8.08-3.01c9.6-3.58,20.26-7.44,28.04-10.26,3.83-1.39,7.09-2.57,9.39-3.42,8.3-3.05,17.11-6.17,26.16-9.29l9.63-3.31c8.22-2.83,17.32-5.95,27.28-9.39,12-4.14,26-7.15,28.01-7.58,1.7-.28,3.02-.5,3.94-.66.56-.1.98-.17,1.27-.22,9.56-1.75,13.57-10.12,12.5-17.12-1.08-7.02-7.52-13.81-17.32-12.53-.42.05-1.05.14-1.92.26-1.6.22-4.05.57-7.43,1.05-9.39,1.33-21.36,1.62-32.94,1.9-2.28.06-4.54.11-6.76.17-13.3.38-25.59,1-36.52,1.86-10.34.81-19.24,1.84-31.73,3.68-8.96,1.32-26.4,3.97-42.58,6.88-15.01,2.7-28.15,5.65-37.91,7.92-10.7,2.49-19.73,4.99-31.73,8.48-1.04.3-2.15.62-3.33.96-6.81,1.95-16.3,4.66-27.47,8.86,78.46-185.28,148.09-354.12,207.18-502.36l-26.85-13.18-195.18,330.65c-126.02,166.83-197.11,264.5-217.33,298.59l-.76,1.28-.49,1.4c-30.12,85.74-57.67,152.59-81.87,198.69-24.25,46.19-41.2,64.04-51.29,70.85-14.9-10.54-36.22-34.66-61.67-89.02l-162.7-441.75c1.58-14.5,3.72-29.35,6.37-44.19,5.04-28.14,12.01-57.76,20.73-88.04,6.58-22.84,14.33-46.26,22.53-71.05,3.22-9.74,6.55-19.8,9.81-29.86,11.01-33.94,24.39-69.18,39.76-104.73,13.69-31.67,30.03-65.78,49.94-104.27,18.44-35.65,33.37-61.87,48.42-85.02,15.78-24.27,32.44-46.43,49.5-65.87,8.91-10.15,18-19.74,26.03-28.21,7.64-8.06,14.25-15.03,18.61-20.31l58.39-70.69-77.88,48.4s-.04.03-.06.04c-5.31-.22-11.57,1.31-23.62,7.03-15,5.65-38.81,21.64-62.45,41.96-23.21,19.96-45.64,43.63-66.66,70.35-22.73,28.89-44.12,61.17-63.84,91.75-.68,1.06-1.37,2.12-2.05,3.2-2.25-9.64-4.95-19.24-7.48-27.87-4.2-14.34-8.11-26.65-12.7-39.91-4.92-14.23-10.61-29.91-16.92-46.58-6.48-17.11-13.96-35.87-20.02-50.19l-3-7.08c-4.1-9.68-6.57-15.53-8.73-20.52-2.8-6.46-4.67-10.82-5.89-13.73-.48-1.16-.88-2.12-1.2-2.89-.92-2.23-1.27-3.07-1.9-4.15l-38.53-66.53,9.46,67.35c-2.17,3.32-2.57,6.75-2.62,8.43h0c-.03,1.4.05,3.43.56,10.12.46,6.04,1,12.12,1.63,18.57.82,8.4,2.03,18.1,5.22,38.18,3.52,22.13,6.87,41.39,9.39,55.7,2.73,15.54,5.31,30.34,7.65,43.99,2.29,13.36,3.99,23.85,5.87,37.92l.34,2.54c1.86,13.96,3.62,27.15,6.48,38.8,2.53,10.34,4.55,20.06,6.02,28.92,1.37,8.29,2.15,14.82,2.78,23.3.37,4.91.55,10.18.58,16.37-18.99,38.16-33.63,73.17-44.74,106.92-5.02,15.25-9.62,31.24-13.71,47.52l-4-10.85-.18-.46c-29.28-72.09-81.01-171.75-153.75-296.22l-26.9,13.08c48.27,122.18,84.93,230.76,108.96,322.73l.22.85.32.82c4.71,12.07,22.3,86.25,79.34,371.16l.14.7,4.64,15.42c-12.83-14.69-24.26-26.59-32.54-34.2-16.6-15.26-34.41-30.37-54.43-46.21-21.05-16.64-40.44-30.7-59.3-42.98-22.44-14.61-47.84-28.28-117.67-58.63-68.82-29.91-128.53-56.05-177.5-77.7-3.75-1.65-7.4-3.27-10.99-4.87-5.95-11.51-14.03-25.83-23.21-42.09-4.35-7.7-8.85-15.67-13.36-23.77-11.69-20.99-24.7-41.94-38.48-64.13-7.19-11.57-14.62-23.54-22.02-35.78-19.31-31.93-37.94-67.5-40.84-73.08-1.62-4.29-2.9-7.64-3.8-9.95-.56-1.44-1.01-2.53-1.32-3.25-4.05-9.3-12.69-11.25-19.01-9.13-.54.18-13.24,4.63-9.48,19.46.29,1.15.73,2.76,1.39,5.07,1.2,4.23,3.07,10.59,5.66,19.39,1.72,5.86,3.51,12.06,5.36,18.51,8.43,29.28,17.99,62.47,30.08,94.14,7.82,20.5,17.28,39.97,28.29,58.25-13.66-6.81-23.29-12.19-33.41-17.85-4.34-2.43-8.83-4.94-13.67-7.58-16.77-9.13-32.23-18.74-47.29-29.36-15.1-10.66-29.64-22.19-43.22-34.28-12.03-10.72-22.44-23.53-24.09-25.61-1.13-1.61-2.01-2.88-2.63-3.75-.4-.56-.7-.98-.91-1.27-5.7-7.76-14.93-7.81-20.73-3.92-5.83,3.92-9.23,12.59-4.04,20.95.27.43.68,1.08,1.23,1.95,1.02,1.6,2.59,4.03,4.76,7.39,2.95,4.58,6.25,10.1,9.74,15.94,5.51,9.23,11.75,19.7,18.69,29.89,11.75,17.25,25.21,33.44,40.02,48.11,16.4,16.25,33.64,30.52,45.22,39.62,16.91,13.31,38.17,27.97,65,44.82l.18.12c30.47,19.14,71.67,43.17,125.94,73.48,16.97,9.48,34.9,19.37,53.09,29.33-2.42,0-4.93,0-7.53,0-12.91.04-28.31.46-44.64,3.27-18.2,3.13-31.45,5.45-36.94,6.6-5.65,1.19-11.27,2.45-17.19,3.86-5.7,1.36-11.47,2.81-17.16,4.31-6.64,1.75-13.34,3.63-33.7,10.33-22.33,7.34-38.74,12.78-51.52,17.16-12.18,4.17-22.11,7.47-27.97,9.28-2.72.84-4.56,1.33-7.34,2.07-2.41.64-5.41,1.44-9.97,2.74-8.87,2.52-18.6,5.13-29.73,8-10.53,2.71-21.35,5.32-32.16,7.77-9.82,2.22-20.39,2.76-20.47,2.76l.23,4.99s-.04,0-.05,0l.96,19.98h0l.23,5,2.95-.14c6.99-.33,16.91.47,26.49,1.25l3.57.29c11.67.92,23.15,1.47,34.12,1.63,12.11.18,23.45-.04,27.76-.14,6.68-.16,12.69-.62,22.18-1.72,7.41-.85,20.21-2.46,39.1-5.26,12.49-1.85,26.78-4.19,39.38-6.26,4.25-.7,8.24-1.35,11.8-1.92,13.93-2.25,19.15-2.91,24.09-3.5,5.22-.62,10.48-1.18,15.65-1.67,5.05-.47,10.19-.9,20.22-1.55,3.5-.23,7.56-.45,11.94-.7,8.37-.46,17.85-.99,26.94-1.72,9.53-.76,18.31-1.81,26.8-2.82,3.94-.47,7.77-.93,11.49-1.33,12.22-1.33,22.46-2.19,33.2-2.81,11.64-.66,24.66-.96,36.68-.83,13.22.14,24.45.58,34.34,1.37,11.78.94,21.53,2.15,31.64,3.93,2.85.5,5.96,1.07,9.64,1.77,10.26,6.11,18.34,11.12,26.17,15.96,3.25,2.01,6.39,3.96,9.52,5.88,17.98,11.03,35.63,22.7,53.96,35.68,17.77,12.59,34.18,25.09,56.56,45.2,25.1,22.57,55.39,55.95,81,89.31,27.45,35.74,53.26,75.61,78.91,121.9,1.04,1.87,2.07,3.75,3.1,5.63l5.41,18c44.13,239.35,37.34,325.75,23.87,356.1l-.79,1.79-.31,1.93c-2.74,17.14-10.12,22.29-15.44,24.67-16.18,7.25-62.1,7.27-188-73.12-9.17-14-19.23-28.64-28.97-42.83-2.48-3.61-4.93-7.17-7.32-10.67-22.21-32.44-39.15-59.27-50.36-79.76-10.99-20.09-18.92-35.02-35.35-66.54-15.42-29.59-29.55-60.81-42-92.77-12.33-31.66-24.01-65.1-35.7-102.24-10.14-32.19-18.43-66.47-19.76-72.08-.48-3.99-.86-7.11-1.15-9.27-.18-1.37-.33-2.41-.45-3.11-1.67-9.96-9.6-13.9-16.26-13.33-.57.05-13.99,1.35-13.82,16.5.01,1.02.06,2.57.14,4.67.15,3.88.43,9.72.81,17.81.33,6.95.61,14.47.91,22.44.97,25.76,2.07,54.96,5.24,84.07,3.98,36.59,11.24,74.17,21.55,111.67,12.01,43.66,25.9,79.3,33.11,96.65,1.24,2.99,2.54,5.95,3.88,8.89-74.6-63.24-153.82-144.26-236.44-241.87l-24.26,17.5c40.04,65.68,78.27,125.17,114.09,177.54-35.14-15.05-74.49-26.22-117.45-33.3-49.78-8.21-100.58-11.75-108.23-12.25-5.6-1.04-10.05-1.86-13.14-2.39-1.87-.32-3.2-.54-4.2-.67-11.71-1.55-16.81,6.83-17.86,11.49-.32,1.39-2.68,13.76,11.96,18.35,1.37.43,3.32,1,6.15,1.79,5.21,1.46,13.09,3.59,24,6.54,35.96,9.73,82.87,27.39,132.1,49.73,40.81,18.52,82.62,38.49,131.58,62.86,19.74,9.82,39.63,20.61,55.96,29.5,31.86,35.73,74.09,80.16,125.94,132.53-25.38-6.06-51.79-9.33-78.98-9.76-41.1-.65-80.88,3.54-87.38,4.26-4.46.01-7.94.02-10.35.06-1.5.02-2.65.05-3.42.09-10.11.51-14.97,7.92-15.21,14.58-.02.57-.32,14.02,14.83,15.7,1.14.13,2.74.27,5.06.46,4.22.34,10.58.81,19.38,1.46,29.34,2.16,68.72,11.12,108.04,24.6,29.45,10.09,58.13,22.65,88.49,35.95,9.09,3.98,18.49,8.1,27.94,12.16,15.21,6.53,30.9,13.91,44.61,20.48l99.71,131.63,43.56,291c-9.31,7.56-17.11,14.87-24.7,21.99-15.48,14.52-28.84,27.06-50.9,37.3-54.53,25.33-112.61,41.92-172.61,49.31-60,7.39-120.53,5.41-179.88-5.9l-.48-.08c-99.25-15.56-182.85-57.03-248.48-123.28-52.78-53.28-94.32-122.75-123.47-206.48-50.1-143.91-46.04-281.55-46-282.92l.54-15.49-15.5-.04c-62.5-.14-124.36-9.59-183.87-28.09-59.49-18.49-115.47-45.66-166.38-80.76-50.88-35.08-95.7-77.39-133.21-125.75-37.49-48.34-66.96-101.79-87.57-158.86l-.05-.13c-34.24-92.2-42.63-179.18-24.93-258.52,14.13-63.34,44.87-122.23,91.37-175.05,80.07-90.94,180.36-132.91,181.35-133.32l20.12-8.27-14.88-15.88c-.66-.7-65.88-71.24-90.42-180.26-14.3-63.51-12.2-127.67,6.24-190.69,23.21-79.34,72.56-157.35,146.67-231.87,74.22-74.63,150.16-126.04,225.7-152.81,41.22-14.6,82.41-21.93,123.05-21.93,18.34,0,36.57,1.49,54.63,4.48,99.4,16.46,160.68,73.38,161.26,73.94l15.35,14.59,8.67-19.32c37.43-83.45,96.76-156.64,171.58-211.67,74.89-55.08,163.69-90.79,256.81-103.27l.69-.11c88.43-16.04,180.05-9.29,264.97,19.51,83.04,28.17,157.51,76.72,215.73,140.57v17.71s21.31-9.86,21.31-9.86c1.05-.48,106.23-48.5,245.17-55.19,81.06-3.9,157.66,6.82,227.67,31.88,87.33,31.26,164.79,85.14,230.23,160.15l.44.48c80.76,84.86,128.54,195.07,134.53,310.34,5.99,115.23-30.09,229.52-101.62,321.8l-13.12,16.94,20.41,6.54c1.37.44,138.79,45.05,269.47,139.87,76.18,55.27,135.18,116.53,175.36,182.08,49.77,81.2,70.67,169.48,62.12,262.39Z" />
					</svg>

					<div class="cf-full__text">
						<p><?php
								// Calculate number of trees needed to offset annual emissions
								// 1 tree absorbs 5900g CO2 per year
								$annual_visitors  = 10000;
								$annual_emissions = $emissions['emissions'] * $annual_visitors;
								$trees_needed     = number_format($annual_emissions / 5900, 1);

								echo wp_kses_post(
									sprintf(
										/* translators: %1$s: number of trees, %2$s: to offset per year */
										__('Takes <span class="cf-full__value">%1$s trees</span> %2$s', 'carbonfooter'),
										esc_html($trees_needed),
										esc_html__('to offset per year', 'carbonfooter')
									)
								);
								?></p>
					</div>
				</div>
			</div>



		</div>
<?php
		$output = ob_get_clean();
		// Collapse double newlines to prevent wpautop from creating empty <p></p> when
		// shortcode output is wrapped in <p> (e.g. Shortcode block, Text widget).
		$output = preg_replace("/\n\n+/", "\n", $output);
		return $output;
	}

	/**
	 * Get emissions for the current page.
	 *
	 * Attempts, in order:
	 * - Current queried object ID
	 * - Global post ID
	 * - `p` query param
	 * Falls back to site average when no page data is available.
	 *
	 * @return array{emissions:string,page_size:mixed} Emissions string and raw page size
	 */
	private function get_current_page_emissions()
	{
		// Get the current queried object ID
		$post_id = get_queried_object_id();

		// If we don't have a post ID yet, try to get it from the global post
		if (! $post_id) {
			$post_id = get_the_ID();
		}

		// If we still don't have a post ID, try to get it from the URL
		if (! $post_id && isset($_GET['p'])) {
			$post_id = absint($_GET['p']);
		}

		// Optional debug info guarded by filter to avoid production logging
		if (apply_filters('carbonfooter_enable_debug_logging', false)) {
			Logger::log('CarbonFooter Debug - Post ID: ' . $post_id);
			Logger::log('CarbonFooter Debug - Is Single: ' . (\is_singular() ? 'yes' : 'no'));
			Logger::log('CarbonFooter Debug - Query Object: ' . wp_json_encode(get_queried_object()));
		}

		// If we have a valid post ID, try to get its emissions (cache-first)
		if ($post_id) {
			$payload = $this->emissions->get_post_payload((int) $post_id);
			if (is_array($payload) && isset($payload['emissions'])) {
				$page_size = $payload['page_size'] ?? null;
				return array(
					'emissions' => number_format((float) $payload['emissions'], 2),
					'page_size' => $page_size,
				);
			}
		}

		// Otherwise return the site average
		$average           = $this->emissions->get_average_emissions();
		$average_formatted = number_format($average, 2);
		return array(
			'emissions' => $average_formatted,
			'page_size' => '0 KB', // Default page size when using average
		);
	}

	/**
	 * Get the CTA icon SVG with proper escaping.
	 *
	 * Returns an <img> tag with a data URI so it passes through wp_kses and CSP.
	 *
	 * @return string Safe <img> markup for the CTA icon
	 */
	private function get_cta_icon()
	{
		// Use a data URI to avoid CSP issues while keeping inline SVG
		$svg_content = '<svg class="cf-full__cta-icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768" width="20" height="20">
    <path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z" />
    <path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z" />
  </svg>';

		// Embed SVG icon as base64 data URL for widget CTA icon.
		return '<img class="cf-full__cta-icon" src="data:image/svg+xml;base64,' . base64_encode($svg_content) . '" alt="Carbonfooter icon" width="20" height="20">';
	}
}
