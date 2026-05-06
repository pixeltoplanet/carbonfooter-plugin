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
	 * Render: carbonfooter/minimal
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content (unused).
	 * @return string Block HTML.
	 */
	public static function render_minimal( array $attributes, string $content ): string {
		$data = self::get_emissions_data();
		$link = 'https://carbonfooter.nl/';
		$icon = SvgIcons::get( 'cloud', 'cf-minimal__link-icon' );

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

		$icon = SvgIcons::get( 'emissions', 'cf-full__icon' );

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

		$icon = SvgIcons::get( 'trees', 'cf-full__icon' );

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

		$icon = SvgIcons::get( 'driving', 'cf-full__icon' );

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

		$icon = SvgIcons::get( 'pageweight', 'cf-full__icon' );

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

		$icon = SvgIcons::get( 'green_hosting', 'cf-full__icon' );

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
		$icon          = SvgIcons::get( 'cloud', 'cf-full__cta-icon' );

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

		// Extract background color to use for the cloud SVG fill
		$bg_color = 'var(--cf-color-background)';
		if ( ! empty( $attributes['backgroundColor'] ) ) {
			$bg_color = sprintf( 'var(--wp--preset--color--%s)', $attributes['backgroundColor'] );
		} elseif ( ! empty( $attributes['style']['color']['background'] ) ) {
			$bg_color = $attributes['style']['color']['background'];
		}

		// Remove background color classes/styles from wrapper so it's not a square
		$wrapper_attrs = preg_replace( '/\bhas-[\w-]+-background-color\b/', '', $wrapper_attrs );
		$wrapper_attrs = preg_replace( '/\bhas-background\b/', '', $wrapper_attrs );
		$wrapper_attrs = preg_replace( '/background-color:\s*[^;"]+;?/', '', $wrapper_attrs );

		$cloud_icon = SvgIcons::get( 'cloud_sticker', 'cf-sticker__cloud', $bg_color );

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
