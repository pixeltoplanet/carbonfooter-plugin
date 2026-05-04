<?php

/**
 * Blocks Registration Class
 *
 * Registers all Carbonfooter Gutenberg blocks and the custom block category.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks
 *
 * Handles registration of all Carbonfooter Gutenberg blocks.
 * Each block is registered via block.json with a PHP render_callback.
 */
class Blocks {

	/**
	 * Block definitions: directory name => render callback method.
	 *
	 * @var array<string, string>
	 */
	private const BLOCK_MAP = array(
		'minimal'       => 'render_minimal',
		'emissions'     => 'render_emissions',
		'trees'         => 'render_trees',
		'driving'       => 'render_driving',
		'pageweight'    => 'render_pageweight',
		'green-hosting' => 'render_green_hosting',
		'full'          => 'render_full',
		'sticker'       => 'render_sticker',
		'label'         => 'render_label',
	);

	/**
	 * Register hooks for block registration.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Register all Carbonfooter blocks.
	 *
	 * Each block is registered from its built block.json with a
	 * render_callback pointing to the BlockRenderer class.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		// Register the shared front-end stylesheet for all blocks.
		$style_path = CARBONFOOTER_PLUGIN_DIR . 'assets/css/carbonfooter-blocks.css';
		wp_register_style(
			'carbonfooter-blocks',
			CARBONFOOTER_PLUGIN_URL . 'assets/css/carbonfooter-blocks.css',
			array(),
			file_exists( $style_path ) ? filemtime( $style_path ) : CARBONFOOTER_VERSION
		);

		foreach ( self::BLOCK_MAP as $block_dir => $render_method ) {
			$block_path = CARBONFOOTER_PLUGIN_DIR . 'build/blocks/' . $block_dir;

			// Only register if the built block.json exists
			if ( ! file_exists( $block_path . '/block.json' ) ) {
				Logger::warning(
					'Block build not found, skipping registration',
					array( 'block' => $block_dir, 'path' => $block_path )
				);
				continue;
			}

			register_block_type(
				$block_path,
				array(
					'render_callback' => array( BlockRenderer::class, $render_method ),
					'style'           => 'carbonfooter-blocks',
				)
			);

			Logger::log( "Registered block: carbonfooter/{$block_dir}" );
		}
	}

	/**
	 * Register the Carbonfooter block category.
	 *
	 * Adds a "Carbonfooter" category to the block inserter so all
	 * plugin blocks are grouped together.
	 *
	 * @param array                    $categories Existing block categories.
	 * @param \WP_Block_Editor_Context $context    Block editor context.
	 * @return array Modified categories.
	 */
	public function register_block_category( array $categories, $context ): array {
		return array_merge(
			array(
				array(
					'slug'  => 'carbonfooter',
					'title' => __( 'Carbonfooter', 'carbonfooter' ),
					'icon'  => 'cloud',
				),
			),
			$categories
		);
	}
}
