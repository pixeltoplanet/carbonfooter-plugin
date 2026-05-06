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
if ( ! defined( 'ABSPATH' ) ) {
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
class Shortcodes {



	/**
	 * Emissions instance.
	 *
	 * @var Emissions
	 */
	private $emissions;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize dependencies
		$this->emissions = new Emissions();

		// Add main shortcode (uses style from settings)
		add_shortcode( 'carbonfooter', array( $this, 'render_carbonfooter' ) );

		// Allow SVG in WordPress
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_svg_in_kses' ), 10, 2 );

		// Add footer hook if enabled in settings
		add_action( 'wp_footer', array( $this, 'maybe_add_to_footer' ), 50 );
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
	public function allow_svg_in_kses( $allowed_html, $context ) {
		if ( $context === 'post' ) {
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
	 * Conditionally add emissions widget to the footer.
	 *
	 * Only injects on the frontend when `carbonfooter_display_setting` is set to
	 * `auto`. Respects admin/AJAX contexts.
	 *
	 * @return void
	 */
	public function maybe_add_to_footer() {
		// Don't show in admin or during AJAX requests
		if ( \is_admin() || \wp_doing_ajax() ) {
			return;
		}

		// Get display setting - check if auto display is enabled
		$display_setting = get_option( 'carbonfooter_display_setting', 'shortcode' );

		// If not set to auto, don't show anything
		if ( $display_setting !== 'auto' ) {
			return;
		}

		// Wrap in a div with clear styling to avoid theme conflicts
		echo '<div id="carbonfooter" class="carbonfooter">';
		echo do_shortcode( '[carbonfooter]' );
		echo '</div>';
	}

	/**
	 * Render the carbonfooter shortcode based on widget style setting.
	 *
	 * Reads `carbonfooter_widget_style` and dispatches to the corresponding
	 * renderer. Defaults to minimal.
	 *
	 * @return string The shortcode output.
	 */
	public function render_carbonfooter() {
		// Get the widget style from settings
		$widget_style = get_option( 'carbonfooter_widget_style', 'minimal' );

		// Render the appropriate style
		switch ( $widget_style ) {
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
	public function render_minimal() {
		$emissions = $this->get_current_page_emissions();
		$link      = 'https://carbonfooter.nl/';
		$icon      = SvgIcons::get( 'cloud', 'cf-minimal__link-icon' );

		ob_start();
		?>
		<div id="carbonfooter">
			<div class="cf-minimal">
				<div class="cf-minimal__content">
					<p class="cf-minimal__text">
						<?php
						$emissions_value = '<span class="cf-minimal__value">' . esc_html( $emissions['emissions'] ) . ' g CO<sub>2</sub></span>';
						$link_html       = sprintf(
							'<a class="cf-minimal__link" href="%s" target="_blank" rel="noopener noreferrer"><span>%s</span>%s</a>',
							esc_url( $link ),
							esc_html__( 'Carbonfooter.nl', 'carbonfooter' ),
							wp_kses_post( $icon )
						);

						echo wp_kses_post(
							sprintf(
								/* translators: %1$s is the CO2 emissions HTML span, %2$s is the link HTML */
								esc_html__( 'This page produced %1$s per page view. Want to learn more? %2$s', 'carbonfooter' ),
								$emissions_value,
								$link_html
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
	 * Render sticker emissions display.
	 *
	 * @return string The shortcode output.
	 */
	public function render_sticker() {
		$emissions = $this->get_current_page_emissions();
		$link      = 'https://carbonfooter.nl/';
		$icon      = SvgIcons::get( 'cloud_sticker', 'cf-sticker__cloud', 'var(--cf-color-background)' );

		ob_start();
		?>
		<div id="carbonfooter">
			<div class="cf-sticker">
				<?php echo wp_kses_post( $icon ); ?>
				<p class="cf-sticker__text">
					<?php
					$emissions_value = '<span class="cf-sticker__value">' . esc_html( $emissions['emissions'] ) . ' g CO<sub>2</sub></span>';
					$link_html       = sprintf(
						'<a class="cf-sticker__link" href="%s" target="_blank" rel="noopener noreferrer"><span>%s</span></a>',
						esc_url( $link ),
						esc_html__( 'Carbonfooter.nl', 'carbonfooter' )
					);

					echo wp_kses_post(
						sprintf(
							/* translators: %1$s is the CO2 emissions HTML span, %2$s is the link HTML */
							esc_html__( 'This page produced %1$s per page view.', 'carbonfooter' ),
							$emissions_value,
							$link_html
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
	public function render_full() {
		$emissions = $this->get_current_page_emissions();
		$average   = get_option( 'carbonfooter_average_emissions', 0 );
		$link      = 'https://carbonfooter.nl/';

		$emissions_value     = esc_html( $emissions['emissions'] );
		$page_size_value     = esc_html( $emissions['page_size'] );
		$formatted_page_size = $page_size_value . ' bytes';

		if ( $page_size_value >= 1024 && $page_size_value < 1024 * 1024 ) {
			$formatted_page_size = round( $page_size_value / 1024, 0 ) . ' KB';
		}

		if ( $page_size_value >= 1024 * 1024 && $page_size_value < 1024 * 1024 * 1024 ) {
			$formatted_page_size = round( $page_size_value / ( 1024 * 1024 ), 2 ) . ' MB';
		}

		// Get green host status
		$is_green_host = (bool) get_option( 'carbonfooter_greenhost', false );
		$icon = SvgIcons::get( 'cloud', 'cf-full__cta-icon' );

		ob_start();
		?>

		<div id="carbonfooter" class="cf-full">
			<div class="cf-full__row">
				<div class="cf-full__col green-host">
					<?php echo SvgIcons::get( 'green_hosting', 'cf-full__icon' ); ?>

					<p class="cf-full__title">
						<?php echo $is_green_host ? esc_html__( 'Yes!', 'carbonfooter' ) : esc_html__( 'No!', 'carbonfooter' ); ?>
					</p>
					<p class="cf-full__text">
						<?php echo $is_green_host ? esc_html__( 'green energy', 'carbonfooter' ) : esc_html__( 'not green energy', 'carbonfooter' ); ?>
					</p>
				</div>
				<div class="cf-full__col page-size">

					<?php echo SvgIcons::get( 'pageweight', 'cf-full__icon' ); ?>

					<p class="cf-full__title">
						<?php echo esc_html( $formatted_page_size ); ?>
					</p>
					<p class="cf-full__text">
						<?php echo esc_html__( 'pagesize', 'carbonfooter' ); ?>
					</p>
				</div>
				<div class="cf-full__col emissions">
					<?php echo SvgIcons::get( 'emissions', 'cf-full__icon' ); ?>
					<p class="cf-full__title">
						<?php
						/* translators: %s: Emissions value in grams of CO2 */
						echo wp_kses_post( sprintf( __( '%s gram CO<sub>2</sub>', 'carbonfooter' ), number_format( $emissions_value, 2 ) ) );
						?>
					</p>
					<p class="cf-full__text">
						<?php echo esc_html__( 'per visit', 'carbonfooter' ); ?>
					</p>
				</div>
				<div class="cf-full__col driving-distance">
					<?php echo SvgIcons::get( 'driving', 'cf-full__icon' ); ?>
					<p class="cf-full__text">
						<?php
						// Calculate annual driving distance based on 10k visitors
						// 1g CO<sub>2</sub> = 5 meters = 0.005 km
						$annual_visitors  = 12000;
						$driving_distance = number_format( ( $emissions['emissions'] * 0.005 ) * $annual_visitors, 2 );

						echo wp_kses_post(
							sprintf(
								/* translators: %s: amount in km car drive per year based on carbon emissions */
								__( '= <strong>%s km.</strong><br>drive per year.', 'carbonfooter' ),
								esc_html( $driving_distance )
							)
						);
						?>
					</p>
				</div>
				<div class="cf-full__col trees-offset">
					<?php echo SvgIcons::get( 'trees', 'cf-full__icon' ); ?>

					<p class="cf-full__text">
						<?php

						// Calculate number of trees needed to offset annual emissions
						// 1 tree absorbs 5900g CO<sub>2</sub> per year
						$annual_visitors  = 10000;
						$annual_emissions = $emissions['emissions'] * $annual_visitors;
						$trees_needed     = number_format( $annual_emissions / 5900, 1 );
						echo wp_kses_post(
							sprintf(
								/* translators: %s: trees needed to offset emissions per year */
								__( 'and <strong>%s trees</strong><br>offset per year.', 'carbonfooter' ),
								esc_html( $trees_needed )
							)
						);
						?>
					</p>
				</div>
			</div>


			<div class="cf-full__cta">
				<?php
				/* translators: %s: url to carbonfooter.nl */
				printf(
					'<a class="cf-full__cta-link" href="%s" target="_blank" rel="noopener noreferrer">%s <span class="cf-full__cta-link-text"><strong>Carbonfooter.nl</strong>%s</span></a>',
					esc_url( $link ),
					esc_html__( 'Want to learn more?', 'carbonfooter' ),
					wp_kses_post( $icon )
				);

				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
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
	private function get_current_page_emissions() {
		// Get the current queried object ID
		$post_id = get_queried_object_id();

		// If we don't have a post ID yet, try to get it from the global post
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// If we still don't have a post ID, try to get it from the URL
		if ( ! $post_id && isset( $_GET['p'] ) ) {
			$post_id = absint( $_GET['p'] );
		}

		// Optional debug info guarded by filter to avoid production logging
		if ( apply_filters( 'carbonfooter_enable_debug_logging', false ) ) {
			Logger::log( 'CarbonFooter Debug - Post ID: ' . $post_id );
			Logger::log( 'CarbonFooter Debug - Is Single: ' . ( \is_singular() ? 'yes' : 'no' ) );
			Logger::log( 'CarbonFooter Debug - Query Object: ' . wp_json_encode( get_queried_object() ) );
		}

		// If we have a valid post ID, try to get its emissions (cache-first)
		if ( $post_id ) {
			$payload = $this->emissions->get_post_payload( (int) $post_id );
			if ( is_array( $payload ) && isset( $payload['emissions'] ) ) {
				$page_size = $payload['page_size'] ?? null;
				return array(
					'emissions' => number_format( (float) $payload['emissions'], 2 ),
					'page_size' => $page_size,
				);
			}
		}

		// Otherwise return the site average
		$average           = $this->emissions->get_average_emissions();
		$average_formatted = number_format( $average, 2 );
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
	private function get_cta_icon() {
		// Use a data URI to avoid CSP issues while keeping inline SVG
		$svg_content = SvgIcons::get( 'cloud', 'cf-full__cta-icon' );

		// Embed SVG icon as base64 data URL for widget CTA icon.
		return '<img class="cf-full__cta-icon" src="data:image/svg+xml;base64,' . base64_encode( $svg_content ) . '" alt="Carbonfooter icon" width="20" height="20">';
	}
}
