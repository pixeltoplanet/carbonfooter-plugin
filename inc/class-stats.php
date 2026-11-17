<?php

/**
 * Stats Class
 *
 * Single source of truth for the per-page numbers shown to visitors and
 * editors (emissions, page size, trees, driving distance, green hosting).
 * Used by block renderers, block bindings, the editor sidebar REST endpoint
 * and Site Health so every surface shows the same values.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stats
 */
class Stats {

	/**
	 * Assumed annual visitors used for the trees comparison.
	 */
	public const TREES_ANNUAL_VISITORS = 10000;

	/**
	 * Grams of CO2 one tree absorbs per year.
	 */
	public const GRAMS_PER_TREE_PER_YEAR = 5900;

	/**
	 * Assumed annual visitors used for the driving comparison.
	 */
	public const DRIVING_ANNUAL_VISITORS = 12000;

	/**
	 * Kilometres driven per gram of CO2.
	 */
	public const KM_PER_GRAM = 0.005;

	/**
	 * Keys that can be requested through block bindings.
	 */
	public const BINDABLE_KEYS = array( 'emissions', 'page_size', 'trees', 'driving', 'green_hosting', 'last_measured' );

	/**
	 * Resolve the post ID for the current front-end request.
	 *
	 * @return int Post ID or 0 when there is no singular context.
	 */
	public static function resolve_current_post_id(): int {
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only lookup for plain permalinks.
		if ( ! $post_id && isset( $_GET['p'] ) ) {
			$post_id = absint( $_GET['p'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		return (int) $post_id;
	}

	/**
	 * Trees needed per year to offset a page's emissions.
	 *
	 * @param float $emissions Grams CO2 per visit.
	 * @return float
	 */
	public static function trees_needed( float $emissions ): float {
		return ( $emissions * self::TREES_ANNUAL_VISITORS ) / self::GRAMS_PER_TREE_PER_YEAR;
	}

	/**
	 * Equivalent kilometres driven per year.
	 *
	 * @param float $emissions Grams CO2 per visit.
	 * @return float
	 */
	public static function driving_km( float $emissions ): float {
		return ( $emissions * self::KM_PER_GRAM ) * self::DRIVING_ANNUAL_VISITORS;
	}

	/**
	 * Format bytes as a short human readable size.
	 *
	 * @param mixed $bytes Raw byte count.
	 * @return string
	 */
	public static function format_page_size( $bytes ): string {
		$size = (float) $bytes;
		if ( $size >= 1024 * 1024 ) {
			return round( $size / ( 1024 * 1024 ), 2 ) . ' MB';
		}
		if ( $size >= 1024 ) {
			return round( $size / 1024, 0 ) . ' KB';
		}
		return $size . ' bytes';
	}

	/**
	 * Collect all stats for a post.
	 *
	 * When the post has not been measured yet, the site average is used for
	 * emissions and `measured` is false.
	 *
	 * @param int $post_id Post ID (0 for "no post context").
	 * @return array{
	 *     post_id:int,
	 *     measured:bool,
	 *     emissions:float,
	 *     page_size:float|null,
	 *     trees:float,
	 *     driving_km:float,
	 *     green_hosting:bool,
	 *     updated_at:int|null
	 * }
	 */
	public static function for_post( int $post_id ): array {
		$emissions_handler = new Emissions();
		$payload           = $post_id ? $emissions_handler->get_post_payload( $post_id ) : null;
		$measured          = is_array( $payload ) && isset( $payload['emissions'] );

		$emissions  = $measured ? (float) $payload['emissions'] : (float) $emissions_handler->get_average_emissions();
		$page_size  = ( $measured && isset( $payload['page_size'] ) ) ? (float) $payload['page_size'] : null;
		$updated_at = ( $measured && ! empty( $payload['updated_at'] ) ) ? (int) $payload['updated_at'] : null;

		return array(
			'post_id'       => $post_id,
			'measured'      => $measured,
			'emissions'     => $emissions,
			'page_size'     => $page_size,
			'trees'         => self::trees_needed( $emissions ),
			'driving_km'    => self::driving_km( $emissions ),
			'green_hosting' => (bool) get_option( Constants::OPTION_GREEN_HOST, false ),
			'updated_at'    => $updated_at,
		);
	}

	/**
	 * Human readable, self-contained value for a single stat key.
	 *
	 * Used by block bindings where the value replaces a block's whole content.
	 *
	 * @param array  $stats Result of for_post().
	 * @param string $key   One of BINDABLE_KEYS.
	 * @return string|null Null for unknown keys.
	 */
	public static function format_value( array $stats, string $key ): ?string {
		switch ( $key ) {
			case 'emissions':
				/* translators: %s: grams of CO2 per page visit */
				return sprintf( __( '%s g CO₂', 'carbonfooter' ), number_format_i18n( $stats['emissions'], 2 ) );
			case 'page_size':
				return null === $stats['page_size'] ? __( 'Not measured yet', 'carbonfooter' ) : self::format_page_size( $stats['page_size'] );
			case 'trees':
				/* translators: %s: number of trees */
				return sprintf( __( '%s trees', 'carbonfooter' ), number_format_i18n( $stats['trees'], 1 ) );
			case 'driving':
				/* translators: %s: kilometres */
				return sprintf( __( '%s km', 'carbonfooter' ), number_format_i18n( $stats['driving_km'], 2 ) );
			case 'green_hosting':
				return $stats['green_hosting'] ? __( 'Green energy', 'carbonfooter' ) : __( 'Not green energy', 'carbonfooter' );
			case 'last_measured':
				return $stats['updated_at']
					? wp_date( get_option( 'date_format' ), $stats['updated_at'] )
					: __( 'Not measured yet', 'carbonfooter' );
		}
		return null;
	}
}
