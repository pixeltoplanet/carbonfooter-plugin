<?php

/**
 * Block Patterns Class
 *
 * Registers ready-made Carbonfooter layouts in the block inserter
 * (Patterns tab → "Carbonfooter"). Footer patterns are also offered when
 * editing a footer template part in the site editor.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockPatterns
 */
class BlockPatterns {

	/**
	 * Pattern category slug.
	 */
	public const CATEGORY = 'carbonfooter';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	/**
	 * Register the pattern category and all patterns.
	 *
	 * @return void
	 */
	public function register_patterns(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Carbonfooter', 'carbonfooter' ),
				'description' => __( 'Show the carbon footprint of your pages.', 'carbonfooter' ),
			)
		);

		foreach ( $this->get_patterns() as $name => $pattern ) {
			register_block_pattern( 'carbonfooter/' . $name, $pattern );
		}
	}

	/**
	 * Pattern definitions.
	 *
	 * @return array<string, array>
	 */
	private function get_patterns(): array {
		$footer_types = array( 'core/template-part/footer' );

		return array(
			'footer-bar'     => array(
				'title'       => __( 'Carbonfooter footer bar', 'carbonfooter' ),
				'description' => __( 'A slim full-width bar with a short note and the page emissions label.', 'carbonfooter' ),
				'categories'  => array( self::CATEGORY, 'footer' ),
				'blockTypes'  => $footer_types,
				'keywords'    => array( 'carbon', 'emissions', 'footer', 'co2' ),
				'content'     => $this->footer_bar(),
			),
			'footer-stats'   => array(
				'title'       => __( 'Carbonfooter stats overview', 'carbonfooter' ),
				'description' => __( 'All page stats in one row: hosting, page weight, emissions, driving and trees.', 'carbonfooter' ),
				'categories'  => array( self::CATEGORY, 'footer' ),
				'blockTypes'  => $footer_types,
				'keywords'    => array( 'carbon', 'emissions', 'stats', 'trees' ),
				'content'     => $this->footer_stats(),
			),
			'footer-sticker' => array(
				'title'       => __( 'Carbonfooter sticker, aligned right', 'carbonfooter' ),
				'description' => __( 'The cloud sticker aligned to the right of the footer.', 'carbonfooter' ),
				'categories'  => array( self::CATEGORY, 'footer' ),
				'blockTypes'  => $footer_types,
				'keywords'    => array( 'carbon', 'sticker', 'badge' ),
				'content'     => $this->footer_sticker(),
			),
			'bound-stats'    => array(
				'title'       => __( 'Carbonfooter stats columns', 'carbonfooter' ),
				'description' => __( 'Three columns using regular headings bound to Carbonfooter data, so they follow your theme typography.', 'carbonfooter' ),
				'categories'  => array( self::CATEGORY ),
				'keywords'    => array( 'carbon', 'emissions', 'bindings', 'columns' ),
				'content'     => $this->bound_stats(),
			),
		);
	}

	/**
	 * Footer bar markup.
	 *
	 * @return string
	 */
	private function footer_bar(): string {
		return '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">' . esc_html__( 'We measure the footprint of every page on this site.', 'carbonfooter' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:carbonfooter/label /--></div>
<!-- /wp:group -->';
	}

	/**
	 * Full stats overview markup.
	 *
	 * @return string
	 */
	private function footer_stats(): string {
		return '<!-- wp:carbonfooter/full -->
<!-- wp:carbonfooter/green-hosting /-->

<!-- wp:carbonfooter/pageweight /-->

<!-- wp:carbonfooter/emissions /-->

<!-- wp:carbonfooter/driving /-->

<!-- wp:carbonfooter/trees /-->
<!-- /wp:carbonfooter/full -->';
	}

	/**
	 * Sticker footer markup.
	 *
	 * @return string
	 */
	private function footer_sticker(): string {
		return '<!-- wp:group {"layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-group"><!-- wp:carbonfooter/sticker /--></div>
<!-- /wp:group -->';
	}

	/**
	 * Columns with headings bound to the carbonfooter/stats source.
	 *
	 * @return string
	 */
	private function bound_stats(): string {
		$columns = array(
			'emissions'     => __( 'per page visit', 'carbonfooter' ),
			'page_size'     => __( 'page weight', 'carbonfooter' ),
			'green_hosting' => __( 'hosting', 'carbonfooter' ),
		);

		$markup = '';
		foreach ( $columns as $key => $caption ) {
			$binding = wp_json_encode(
				array(
					'metadata' => array(
						'bindings' => array(
							'content' => array(
								'source' => BlockBindings::SOURCE,
								'args'   => array( 'key' => $key ),
							),
						),
					),
					'level'    => 3,
				)
			);

			$markup .= '<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading ' . $binding . ' -->
<h3 class="wp-block-heading"></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">' . esc_html( $caption ) . '</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

';
		}

		return '<!-- wp:columns -->
<div class="wp-block-columns">' . trim( $markup ) . '</div>
<!-- /wp:columns -->';
	}
}
