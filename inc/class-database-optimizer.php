<?php

/**
 * Simple Database Optimizer for CarbonFooter
 *
 * Handles optimized database queries with basic caching and indexing.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database_Optimizer
 *
 * Encapsulates optimized queries and light caching for site-wide stats and
 * listings. Reduces round-trips and avoids N+1 patterns.
 *
 * Responsibilities:
 * - Aggregate site stats in a single query
 * - Compute sorted lists (heaviest, untested) with minimal overhead
 * - Provide coarse-grained caches and invalidation helpers
 */
class Database_Optimizer {


	/**
	 * Get comprehensive site statistics with caching.
	 *
	 * Returns keys: total_measured, average_emissions, total_emissions,
	 * latest_test_date, hosting_status, homepage_emissions, resource_stats.
	 *
	 * @return array Site statistics
	 */
	public static function get_site_stats() {
		// Try cache first
		$cached = wp_cache_get( Constants::CACHE_STATS_KEY, Constants::CACHE_GROUP );
		if ( $cached !== false ) {
			return $cached;
		}

		global $wpdb;

		// Single optimized query to get all stats at once
		$stats = $wpdb->get_row(
			"
            SELECT
                COUNT(*) as total_measured,
                AVG(CAST(meta_value AS DECIMAL(10,2))) as average_emissions,
                SUM(CAST(meta_value AS DECIMAL(10,2))) as total_emissions
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_carbon_emissions'
            AND pm.meta_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
            AND p.post_status = 'publish'
        ",
			ARRAY_A
		);

		// Get latest update timestamp
		$latest_update = $wpdb->get_var(
			"
            SELECT MAX(meta_value)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_carbon_emissions_updated'
        "
		);

		// Get homepage emissions if homepage is set
		$homepage_emissions = null;
		$show_on_front      = get_option( 'show_on_front', 'posts' );
		$page_on_front      = get_option( 'page_on_front', 0 );

		if ( $show_on_front === 'page' && $page_on_front ) {
			$homepage_emissions = get_post_meta( $page_on_front, '_carbon_emissions', true );
			if ( $homepage_emissions && is_numeric( $homepage_emissions ) ) {
				$homepage_emissions = (float) $homepage_emissions;
			} else {
				$homepage_emissions = null;
			}
		}

		$result = array(
			'total_measured'     => (int) ( $stats['total_measured'] ?? 0 ),
			'average_emissions'  => (float) ( $stats['average_emissions'] ?? 0 ),
			'average'            => (float) ( $stats['average_emissions'] ?? 0 ), // Backward compatibility
			'total_emissions'    => (float) ( $stats['total_emissions'] ?? 0 ),
			'latest_test_date'   => $latest_update ?: null,
			'hosting_status'     => get_option( 'carbonfooter_greenhost', false ),
			'homepage_emissions' => $homepage_emissions,
			'resource_stats'     => self::get_site_resource_stats(),
		);

		// Cache for 5 minutes
		wp_cache_set( Constants::CACHE_STATS_KEY, $result, Constants::CACHE_GROUP, 300 );

		return $result;
	}

	/**
	 * Get heaviest pages with optimized query.
	 *
	 * @param int $limit Number of pages to return (capped to 100)
	 * @return array Array of pages with emissions data
	 */
	public static function get_heaviest_pages( $limit = 10 ) {
		$cache_key = Constants::CACHE_HEAVIEST_PAGES_KEY . ":{$limit}";

		$cached = wp_cache_get( $cache_key, Constants::CACHE_GROUP );
		if ( $cached !== false ) {
			return $cached;
		}

		global $wpdb;

		$limit = min( absint( $limit ), 100 );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT
                p.ID,
                p.post_title,
                p.post_type,
                p.post_name,
                pm.meta_value as emissions
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_carbon_emissions'
            AND pm.meta_value REGEXP '^[0-9]+(\\.[0-9]+)?$'
            AND p.post_status = 'publish'
            ORDER BY CAST(pm.meta_value AS DECIMAL(10,2)) DESC
            LIMIT %d
        ",
				$limit
			)
		);

		$pages = array();
		foreach ( $results as $result ) {
			$pages[] = array(
				'id'        => (int) $result->ID,
				'title'     => $result->post_title,
				'type'      => $result->post_type,
				'emissions' => (float) $result->emissions,
				'url'       => get_permalink( $result->ID ),
				'edit_url'  => get_edit_post_link( $result->ID, 'raw' ),
			);
		}

		// Cache for 10 minutes
		wp_cache_set( $cache_key, $pages, Constants::CACHE_GROUP, 600 );

		return $pages;
	}

	/**
	 * Get untested pages with optimized query.
	 *
	 * @param int $limit Number of pages to return (capped to 100)
	 * @return array Array of untested pages grouped by post type
	 */
	public static function get_untested_pages( $limit = 20 ) {
		$cache_key = Constants::CACHE_UNTESTED_PAGES_KEY . ":{$limit}";

		$cached = wp_cache_get( $cache_key, Constants::CACHE_GROUP );
		if ( $cached !== false ) {
			return $cached;
		}

		global $wpdb;

		$limit = min( absint( $limit ), 100 );

		// Get all public post types
		$post_types = get_post_types( array( 'public' => true ) );

		// Remove blacklisted post types
		$post_types = array_filter(
			$post_types,
			function ( $post_type ) {
				return ! in_array( $post_type, array( 'attachment' ), true );
			}
		);

		// Create placeholders for the IN clause
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// Prepare the query with dynamic post types
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
      SELECT
        p.ID,
        p.post_title,
        p.post_type,
        p.post_name,
        p.post_date
      FROM {$wpdb->posts} p
      LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_carbon_emissions'
      WHERE p.post_status = 'publish'
      AND p.post_type IN ($placeholders) -- phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders built via array_fill and used with $wpdb->prepare
      AND pm.meta_value IS NULL
      ORDER BY p.post_type ASC, p.post_date DESC
      LIMIT %d
    ",
				array_merge( $post_types, array( $limit ) )
			)
		);

		// Group by post type
		$grouped_pages = array();
		foreach ( $results as $result ) {
			$post_type       = $result->post_type;
			$post_type_label = get_post_type_object( $post_type )->labels->name ?? ucfirst( $post_type );

			if ( ! isset( $grouped_pages[ $post_type ] ) ) {
				$grouped_pages[ $post_type ] = array(
					'label' => $post_type_label,
					'pages' => array(),
				);
			}

			$grouped_pages[ $post_type ]['pages'][] = array(
				'id'       => (int) $result->ID,
				'title'    => $result->post_title,
				'type'     => $result->post_type,
				'date'     => $result->post_date,
				'url'      => get_permalink( $result->ID ),
				'edit_url' => get_edit_post_link( $result->ID, 'raw' ),
			);
		}

		// Cache for 10 minutes
		wp_cache_set( $cache_key, $grouped_pages, Constants::CACHE_GROUP, 600 );

		return $grouped_pages;
	}

	/**
	 * Get site resource statistics.
	 *
	 * Aggregates transfer sizes and request counts per resource type, returning
	 * totals and averages across analyzed pages.
	 *
	 * @return array Aggregated resource statistics
	 */
	public static function get_site_resource_stats() {
		$cached = wp_cache_get( 'carbonfooter_resource_stats', Constants::CACHE_GROUP );
		if ( $cached !== false ) {
			return $cached;
		}

		global $wpdb;

		$resources_data = $wpdb->get_results(
			"
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_carbon_resources'
            AND meta_value != ''
        "
		);

		if ( empty( $resources_data ) ) {
			$result = array();
		} else {
			$total_stats = array(
				'total'      => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
				'images'     => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
				'script'     => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
				'css'        => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
				'font'       => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
				'media'      => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
				'thirdParty' => array(
					'transferSize' => 0,
					'requestCount' => 0,
				),
			);

			$count = 0;
			foreach ( $resources_data as $row ) {
				$resources = maybe_unserialize( $row->meta_value );
				if ( is_array( $resources ) ) {
					++$count;
					foreach ( $total_stats as $type => $stats ) {
						if ( isset( $resources[ $type ] ) ) {
							$total_stats[ $type ]['transferSize'] += isset( $resources[ $type ]['transferSize'] ) ? $resources[ $type ]['transferSize'] : 0;
							$total_stats[ $type ]['requestCount'] += isset( $resources[ $type ]['requestCount'] ) ? $resources[ $type ]['requestCount'] : 0;
						}
					}
				}
			}

			// Calculate averages
			if ( $count > 0 ) {
				foreach ( $total_stats as $type => $stats ) {
					$total_stats[ $type ]['avgTransferSize'] = round( $stats['transferSize'] / $count );
					$total_stats[ $type ]['avgRequestCount'] = round( $stats['requestCount'] / $count, 1 );
				}
			}

			$total_stats['pages_analyzed'] = $count;
			$result                        = $total_stats;
		}

		// Cache for 30 minutes
		wp_cache_set( 'carbonfooter_resource_stats', $result, Constants::CACHE_GROUP, 1800 );

		return $result;
	}

	/**
	 * Add database indices for performance.
	 *
	 * Creates helpful indices for frequent meta queries when supported by the DB.
	 *
	 * @return bool True on success, false on failure
	 */
	public static function add_performance_indices() {
		// Intentionally left as no-op for maximum host portability.
		// Creating custom indexes on core tables is discouraged for WordPress.org distribution,
		// and portable partial indexes are not supported across common MySQL/MariaDB versions.
		return false;
	}

	/**
	 * Get emissions for a specific post with caching.
	 *
	 * @param int $post_id Post ID
	 * @return float|false Emissions value or false if not found
	 */
	public static function get_post_emissions( $post_id ) {
		$cache_key = "carbonfooter_emissions_{$post_id}";

		$cached = wp_cache_get( $cache_key, Constants::CACHE_GROUP );
		if ( $cached !== false ) {
			return $cached;
		}

		$emissions = get_post_meta( $post_id, '_carbon_emissions', true );

		if ( $emissions && is_numeric( $emissions ) ) {
			$emissions = (float) $emissions;
			// Cache for 1 hour
			wp_cache_set( $cache_key, $emissions, Constants::CACHE_GROUP, 3600 );
			return $emissions;
		}

		return false;
	}

	/**
	 * Invalidate cache for a specific post.
	 *
	 * @param int $post_id Post ID
	 */
	public static function invalidate_post_cache( $post_id ) {
		// Remove specific post cache
		wp_cache_delete( "carbonfooter_emissions_{$post_id}", Constants::CACHE_GROUP );

		// Invalidate site stats cache
		wp_cache_delete( Constants::CACHE_STATS_KEY, Constants::CACHE_GROUP );

		// Invalidate page caches
		wp_cache_delete( Constants::CACHE_HEAVIEST_PAGES_KEY . ':10', Constants::CACHE_GROUP );
		wp_cache_delete( Constants::CACHE_HEAVIEST_PAGES_KEY . ':20', Constants::CACHE_GROUP );
		wp_cache_delete( Constants::CACHE_UNTESTED_PAGES_KEY . ':20', Constants::CACHE_GROUP );
		wp_cache_delete( Constants::CACHE_UNTESTED_PAGES_KEY . ':50', Constants::CACHE_GROUP );
	}
}
