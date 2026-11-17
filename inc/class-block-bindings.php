<?php

/**
 * Block Bindings Class
 *
 * Registers the `carbonfooter/stats` block bindings source so any bindable
 * core block attribute (paragraph/heading content, button text, ...) can
 * display a Carbonfooter value for the current page.
 *
 * Example markup:
 * <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"carbonfooter/stats","args":{"key":"emissions"}}}}} -->
 * <p></p>
 * <!-- /wp:paragraph -->
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockBindings
 */
class BlockBindings {

	/**
	 * Bindings source name.
	 */
	public const SOURCE = 'carbonfooter/stats';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_source' ) );
	}

	/**
	 * Register the bindings source (WordPress 6.5+).
	 *
	 * @return void
	 */
	public function register_source(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Carbonfooter', 'carbonfooter' ),
				'get_value_callback' => array( $this, 'get_value' ),
				'uses_context'       => array( 'postId' ),
			)
		);
	}

	/**
	 * Resolve a bound value.
	 *
	 * @param array     $source_args    Binding args, expects `key`.
	 * @param \WP_Block $block_instance Block being rendered.
	 * @return string|null
	 */
	public function get_value( array $source_args, $block_instance ): ?string {
		$key = isset( $source_args['key'] ) ? sanitize_key( $source_args['key'] ) : '';
		if ( ! in_array( $key, Stats::BINDABLE_KEYS, true ) ) {
			return null;
		}

		$post_id = 0;
		if ( $block_instance instanceof \WP_Block && ! empty( $block_instance->context['postId'] ) ) {
			$post_id = (int) $block_instance->context['postId'];
		}
		if ( ! $post_id ) {
			$post_id = Stats::resolve_current_post_id();
		}

		// Never expose data for posts the visitor can't see.
		if ( $post_id && ! is_post_publicly_viewable( $post_id ) && ! current_user_can( 'read_post', $post_id ) ) {
			$post_id = 0;
		}

		return esc_html( Stats::format_value( Stats::for_post( $post_id ), $key ) );
	}
}
