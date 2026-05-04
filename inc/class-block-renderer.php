<?php

/**
 * Block Renderer Class
 *
 * Contains render callbacks for all Carbonfooter Gutenberg blocks.
 * Each method produces the front-end HTML for a dynamic block,
 * reusing the existing emissions data layer.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockRenderer
 *
 * Static render methods for each Carbonfooter block.
 * All methods accept $attributes and $content (for InnerBlocks)
 * and return an HTML string.
 */
class BlockRenderer {

	/**
	 * Get emissions data for the current page context.
	 *
	 * Reuses the same logic as Shortcodes::get_current_page_emissions().
	 *
	 * @return array{emissions:string,page_size:mixed}
	 */
	private static function get_emissions_data(): array {
		$emissions_handler = new Emissions();

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id && isset( $_GET['p'] ) ) {
			$post_id = absint( $_GET['p'] );
		}

		if ( $post_id ) {
			$payload = $emissions_handler->get_post_payload( (int) $post_id );
			if ( is_array( $payload ) && isset( $payload['emissions'] ) ) {
				return array(
					'emissions' => number_format( (float) $payload['emissions'], 2 ),
					'page_size' => $payload['page_size'] ?? null,
				);
			}
		}

		$average = $emissions_handler->get_average_emissions();
		return array(
			'emissions' => number_format( $average, 2 ),
			'page_size' => '0 KB',
		);
	}

	/**
	 * Format page size from raw bytes.
	 *
	 * @param mixed $page_size Raw page size value.
	 * @return string Formatted page size string.
	 */
	private static function format_page_size( $page_size ): string {
		$size = (float) $page_size;
		if ( $size >= 1024 * 1024 ) {
			return round( $size / ( 1024 * 1024 ), 2 ) . ' MB';
		}
		if ( $size >= 1024 ) {
			return round( $size / 1024, 0 ) . ' KB';
		}
		return $size . ' bytes';
	}

	/**
	 * Get the CTA cloud icon SVG markup.
	 *
	 * @param string $class CSS class for the SVG.
	 * @return string SVG markup.
	 */
	private static function get_cloud_icon( string $class = 'cf-block-icon' ): string {
		return '<svg class="' . esc_attr( $class ) . '" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 995 768">
			<path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z" />
			<path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z" />
		</svg>';
	}

	/**
	 * Render: carbonfooter/minimal
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_minimal( array $attributes, string $content ): string {
		$data = self::get_emissions_data();
		$link = 'https://carbonfooter.nl/';
		$icon = self::get_cloud_icon( 'cf-minimal__link-icon' );

		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-minimal' ) );

		$emissions_value = '<span class="cf-block-minimal__value">' . esc_html( $data['emissions'] ) . ' g CO<sub>2</sub></span>';
		$link_html       = sprintf(
			'<a class="cf-block-minimal__link" href="%s" target="_blank" rel="noopener noreferrer"><span>%s</span>%s</a>',
			esc_url( $link ),
			esc_html__( 'Carbonfooter.nl', 'carbonfooter' ),
			$icon
		);

		$text = wp_kses_post(
			sprintf(
				/* translators: %1$s is the CO2 emissions HTML span, %2$s is the link HTML */
				esc_html__( 'This page produced %1$s per page view. Want to learn more? %2$s', 'carbonfooter' ),
				$emissions_value,
				$link_html
			)
		);

		return sprintf(
			'<div %s><p class="cf-block-minimal__text">%s</p></div>',
			$wrapper_attrs,
			$text
		);
	}

	/**
	 * Render: carbonfooter/emissions
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_emissions( array $attributes, string $content ): string {
		$data          = self::get_emissions_data();
		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-stat cf-block-stat--emissions' ) );

		$icon = '<svg class="cf-block-stat__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156.76 154.49"><path d="M28.52,123.15s9.68.67,9.84-7.28c.12-7.05-8.5-6.21-8.5-6.21,0,0-4.64-7.05-9.91-4.02-6.19,3.65-2.24,10.91-2.24,10.91,0,0-5.75,6.36.79,10.24,7.3,4.35,10.01-3.65,10.01-3.65h0Z" /><path d="M84.08,65.82c-1.04-.49-2.89-.93-3.75,0-1.09,1.38-.53,5.02.46,6.29,1.59,2.08,3.16.18,3.97-1.5.81-1.67,1.25-3.88-.67-4.79h0Z" /><path d="M118.9,60.01c2.61-3.48,3.92-7.76,3.71-12.11-.22-4.34-1.95-8.48-4.9-11.67-10.44-12.34-25.49-5.13-25.49-5.13h0c-2.14-2.48-4.9-4.36-7.99-5.44-3.09-1.08-6.42-1.34-9.64-.73-3.39.47-6.6,1.8-9.33,3.87-2.73,2.07-4.87,4.8-6.24,7.94,0,0-8.9-8.73-20.71,3.53-11.81,12.25-2.22,22.76-2.22,22.76,0,0-14.54,6.15-9.06,21.38,1.5,4.29,4.3,8.01,8.01,10.65,3.71,2.64,8.14,4.06,12.69,4.07,0,0-.69,20.34,15.23,22.92,4.33.85,8.81.28,12.79-1.63,3.98-1.91,7.23-5.05,9.28-8.95,0,0,7.05,15.87,23.6,6.29,16.55-9.57,10.44-18.56,10.44-18.56,0,0,16.76-1.89,18.14-17.29,1.37-15.41-18.3-21.89-18.3-21.89h0Z" /></svg>';

		return sprintf(
			'<div %s>%s<p class="cf-block-stat__title">%s</p><p class="cf-block-stat__text">%s</p></div>',
			$wrapper_attrs,
			$icon,
			wp_kses_post( sprintf( __( '%s gram CO<sub>2</sub>', 'carbonfooter' ), number_format( (float) $data['emissions'], 2 ) ) ),
			esc_html__( 'per visit', 'carbonfooter' )
		);
	}

	/**
	 * Render: carbonfooter/trees
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_trees( array $attributes, string $content ): string {
		$data             = self::get_emissions_data();
		$annual_visitors  = 10000;
		$annual_emissions = (float) $data['emissions'] * $annual_visitors;
		$trees_needed     = number_format( $annual_emissions / 5900, 1 );

		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-stat cf-block-stat--trees' ) );

		$icon = '<svg class="cf-block-stat__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C9.5 2 7.5 4 7.5 6.5c0 .5.1 1 .2 1.5C5.6 8.5 4 10.3 4 12.5 4 15 6 17 8.5 17H9v4h2v-4h2v4h2v-4h.5c2.5 0 4.5-2 4.5-4.5 0-2.2-1.6-4-3.7-4.5.1-.5.2-1 .2-1.5C16.5 4 14.5 2 12 2z" /></svg>';

		return sprintf(
			'<div %s>%s<p class="cf-block-stat__text">%s</p></div>',
			$wrapper_attrs,
			$icon,
			wp_kses_post(
				sprintf(
					/* translators: %s: trees needed to offset emissions per year */
					__( 'and <strong>%s trees</strong><br>offset per year.', 'carbonfooter' ),
					esc_html( $trees_needed )
				)
			)
		);
	}

	/**
	 * Render: carbonfooter/driving
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_driving( array $attributes, string $content ): string {
		$data             = self::get_emissions_data();
		$annual_visitors  = 12000;
		$driving_distance = number_format( ( (float) $data['emissions'] * 0.005 ) * $annual_visitors, 2 );

		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-stat cf-block-stat--driving' ) );

		$icon = '<svg class="cf-block-stat__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 215.7 154.49"><path d="M76.83,87.93c-.39,0-.71-.33-.71-.72,0-.15.05-.28.12-.4.66-.96,1.26-1.91,1.87-2.87,2-3.16,4.03-6.37,7.73-9.47.13-.11.29-.17.46-.17h8.54c.38,0,.69.29.71.67l1.07,12.35c.03.39-.26.74-.65.78-.05,0-.08,0-.13,0l-19.01-.16Z" /><path d="M16.19,48.31c-1.2-1.63-1.81-3.64-1.71-5.68.1-2.04.9-3.98,2.26-5.48,4.81-5.79,11.75-2.41,11.75-2.41.99-1.16,2.26-2.04,3.68-2.55,1.43-.51,2.96-.63,4.44-.34,1.56.22,3.04.85,4.3,1.82,1.26.97,2.25,2.25,2.88,3.73,0,0,4.1-4.09,9.55,1.65,5.44,5.75,1.02,10.68,1.02,10.68,0,0,6.7,2.89,4.18,10.03-.69,2.01-1.98,3.76-3.69,5s-3.75,1.9-5.85,1.91c0,0,.32,9.55-7.02,10.75-1.99.4-4.06.13-5.9-.76-1.83-.89-3.33-2.37-4.28-4.2,0,0-3.25,7.44-10.88,2.95-7.63-4.49-4.81-8.71-4.81-8.71,0,0-7.73-.89-8.36-8.11-.63-7.23,8.44-10.27,8.44-10.27Z" /></svg>';

		return sprintf(
			'<div %s>%s<p class="cf-block-stat__text">%s</p></div>',
			$wrapper_attrs,
			$icon,
			wp_kses_post(
				sprintf(
					/* translators: %s: amount in km car drive per year based on carbon emissions */
					__( '= <strong>%s km.</strong><br>drive per year.', 'carbonfooter' ),
					esc_html( $driving_distance )
				)
			)
		);
	}

	/**
	 * Render: carbonfooter/pageweight
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_pageweight( array $attributes, string $content ): string {
		$data            = self::get_emissions_data();
		$formatted_size  = self::format_page_size( $data['page_size'] );
		$wrapper_attrs   = get_block_wrapper_attributes( array( 'class' => 'cf-block-stat cf-block-stat--pageweight' ) );

		$icon = '<svg class="cf-block-stat__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156.76 154.49"><path d="M138.56,52.41l-8.38,50.39c-.16.96-1.18,1.24-1.83.86l-65.11-37.59,8.69-52.25c.18-1.11,1.13-1.91,2.25-1.91.4,0,.78.1,1.13.31l60.85,35.13c1.77,1.02,2.73,3.06,2.4,5.07h0Z" /></svg>';

		return sprintf(
			'<div %s>%s<p class="cf-block-stat__title">%s</p><p class="cf-block-stat__text">%s</p></div>',
			$wrapper_attrs,
			$icon,
			esc_html( $formatted_size ),
			esc_html__( 'pagesize', 'carbonfooter' )
		);
	}

	/**
	 * Render: carbonfooter/green-hosting
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_green_hosting( array $attributes, string $content ): string {
		$is_green_host = (bool) get_option( 'carbonfooter_greenhost', false );
		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-stat cf-block-stat--green-hosting' ) );

		$icon = '<svg class="cf-block-stat__icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156.76 154.49"><path d="M109.56,19.5c.2.41.22.9.06,1.33l-5.63,15.47h0c-.33.9-1.33,1.37-2.24,1.04-.9-.33-1.37-1.33-1.04-2.24l5.63-15.47h0c.24-.67.86-1.11,1.56-1.15.7-.03,1.36.37,1.66,1.01Z" /><path d="M71.56,21.87l6.96,14.92c.41.87.03,1.91-.84,2.32-.88.41-1.91.03-2.32-.84l-6.96-14.92c-.41-.88-.03-1.91.84-2.32.87-.41,1.91-.03,2.32.84Z" /></svg>';

		return sprintf(
			'<div %s>%s<p class="cf-block-stat__title">%s</p><p class="cf-block-stat__text">%s</p></div>',
			$wrapper_attrs,
			$icon,
			$is_green_host ? esc_html__( 'Yes!', 'carbonfooter' ) : esc_html__( 'No!', 'carbonfooter' ),
			$is_green_host ? esc_html__( 'green energy', 'carbonfooter' ) : esc_html__( 'not green energy', 'carbonfooter' )
		);
	}

	/**
	 * Render: carbonfooter/full
	 *
	 * Wraps InnerBlocks content in the full-style container.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Rendered inner blocks HTML.
	 * @return string Block HTML.
	 */
	public static function render_full( array $attributes, string $content ): string {
		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-full' ) );
		$link          = 'https://carbonfooter.nl/';
		$icon          = self::get_cloud_icon( 'cf-block-full__cta-icon' );

		$cta = sprintf(
			'<div class="cf-block-full__cta"><a class="cf-block-full__cta-link" href="%s" target="_blank" rel="noopener noreferrer">%s <span class="cf-block-full__cta-link-text"><strong>Carbonfooter.nl</strong>%s</span></a></div>',
			esc_url( $link ),
			esc_html__( 'Want to learn more?', 'carbonfooter' ),
			$icon
		);

		return sprintf(
			'<div %s><div class="cf-block-full__row">%s</div>%s</div>',
			$wrapper_attrs,
			$content,
			$cta
		);
	}

	/**
	 * Render: carbonfooter/sticker
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_sticker( array $attributes, string $content ): string {
		$data          = self::get_emissions_data();
		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-sticker' ) );

		$cloud_icon = '<svg class="cf-block-sticker__cloud" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 995 768"><path d="M841.11,279.51c20.9-26.96,31.38-60.17,29.63-93.85-1.75-33.68-15.63-65.7-39.22-90.49C748.06-.49,627.65,55.4,627.65,55.4h0c-17.13-19.23-39.17-33.76-63.9-42.15-24.74-8.39-51.31-10.35-77.08-5.68-27.11,3.63-52.79,13.96-74.6,30-21.8,16.03-38.98,37.23-49.89,61.56,0,0-71.19-67.65-165.66,27.33-94.46,94.98-17.76,176.42-17.76,176.42,0,0-116.31,47.7-72.46,165.76,12.02,33.28,34.39,62.13,64.04,82.56,29.64,20.44,65.1,31.46,101.48,31.54,0,0-5.5,157.7,121.81,177.65,34.61,6.59,70.49,2.17,102.29-12.6,31.81-14.78,57.84-39.11,74.22-69.39,0,0,56.39,122.99,188.77,48.79,132.38-74.2,83.46-143.9,83.46-143.9,0,0,134.07-14.62,145.07-134.06,10.99-119.43-146.32-169.72-146.34-169.73Z"/><path d="M102.26,600.22s-31.43-47.8-67.16-27.24c-41.94,24.74-15.18,73.97-15.18,73.97,0,0-38.96,43.14,5.38,69.43,49.47,29.52,67.88-24.74,67.88-24.74h0s65.6,4.55,66.68-49.35c.84-47.8-57.6-42.07-57.6-42.07Z"/></svg>';

		$emissions_value = '<span class="cf-block-sticker__value">' . esc_html( $data['emissions'] ) . ' g CO<sub>2</sub></span>';

		$text = wp_kses_post(
			sprintf(
				/* translators: %1$s is the CO2 emissions HTML span */
				esc_html__( 'This page produced %1$s per page view.', 'carbonfooter' ),
				$emissions_value
			)
		);

		return sprintf(
			'<div %s>%s<p class="cf-block-sticker__text">%s</p></div>',
			$wrapper_attrs,
			$cloud_icon,
			$text
		);
	}

	/**
	 * Render: carbonfooter/label
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_label( array $attributes, string $content ): string {
		$data          = self::get_emissions_data();
		$wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'cf-block-label' ) );

		return sprintf(
			'<div %s><span class="cf-block-label__text">%s</span></div>',
			$wrapper_attrs,
			wp_kses_post(
				sprintf(
					/* translators: %s is the CO2 emissions value in grams */
					__( '%s g CO<sub>2</sub> per visit', 'carbonfooter' ),
					esc_html( $data['emissions'] )
				)
			)
		);
	}
}
