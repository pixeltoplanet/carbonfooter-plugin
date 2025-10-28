<?php

/**
 * Background Processor for emissions calculations.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background_Processor
 *
 * Handles asynchronous processing of emissions calculations using WP Cron
 * and lightweight transients for de-duplication.
 *
 * Responsibilities:
 * - Detect when a singular page is viewed and schedule a background job
 * - Prevent duplicate concurrent jobs per post with a short-lived transient
 * - Execute emissions processing off the main request
 *
 * Performance:
 * - Defers API calls to a cron job to keep page views responsive
 * - Uses minimal coordination mechanisms (transient + next_scheduled check)
 */
class Background_Processor {

	/**
	 * Emissions instance.
	 *
	 * @var Emissions
	 */
	private $emissions;

	/**
	 * Cache instance.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Constructor.
	 *
	 * Hooks:
	 * - `wp` to consider scheduling on page view
	 * - `carbonfooter_process_emissions` to process queued jobs
	 */
	public function __construct() {
		$this->emissions = new Emissions();
		$this->cache     = new Cache();

		// Hook into post view to schedule processing
		add_action( 'wp', array( $this, 'maybe_schedule_processing' ) );

		// Hook for processing
		add_action( 'carbonfooter_process_emissions', array( $this, 'process_emissions' ), 10, 1 );
	}

	/**
	 * Schedule processing if needed when page is viewed.
	 *
	 * Structure:
	 * - Only on frontend singular views
	 * - If due, set a short transient lock and enqueue a single event
	 *
	 * @return void
	 */
	public function maybe_schedule_processing() {
		// Only process on singular posts/pages
		if ( ! \is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		// Check if we need to update
		if ( $this->should_update_emissions( $post_id ) ) {
			$lock_key  = 'carbonfooter_processing_' . $post_id;
			$is_locked = (bool) get_transient( $lock_key );

			// If WP-Cron is disabled, provide a safe inline fallback for authorized users
			if ( ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) && current_user_can( 'edit_post', $post_id ) ) {
				if ( ! $is_locked ) {
					set_transient( $lock_key, true, 5 * MINUTE_IN_SECONDS );
					Logger::log( 'WP-Cron disabled: running inline refresh for authorized user', array( 'post_id' => $post_id ) );
					// Inline processing (guarded by transient lock)
					$this->process_emissions( $post_id );
				}
				return; // Do not attempt to schedule when cron is disabled
			}

			// If WP-Cron is disabled and user is NOT authorized, trigger a fire-and-forget cron ping
			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				if ( ! $is_locked ) {
					set_transient( $lock_key, true, 5 * MINUTE_IN_SECONDS );
					$cron_url = add_query_arg(
						'doing_wp_cron',
						urlencode( microtime( true ) ),
						site_url( 'wp-cron.php' )
					);
					// Non-blocking ping with tiny timeout to avoid impacting visitor
					$args = array(
						'timeout'   => 0.01,
						'blocking'  => false,
						'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
					);
					wp_remote_get( $cron_url, $args );
					Logger::log( 'WP-Cron disabled: triggered fire-and-forget cron ping', array( 'post_id' => $post_id ) );
				}
				return;
			}

			// Normal path: schedule via wp-cron if not already scheduled/locked
			if ( ! wp_next_scheduled( 'carbonfooter_process_emissions', array( $post_id ) ) && ! $is_locked ) {
				// Set a transient to prevent duplicate processing
				set_transient( $lock_key, true, 5 * MINUTE_IN_SECONDS );

				// Schedule immediate processing using wp-cron
				wp_schedule_single_event( time(), 'carbonfooter_process_emissions', array( $post_id ) );
				Logger::log( 'Scheduled background emissions refresh', array( 'post_id' => $post_id ) );
			}
		}
	}

	/**
	 * Check if emissions need updating.
	 *
	 * Policy:
	 * - Process if never calculated or if older than one week.
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether emissions should be updated.
	 */
	private function should_update_emissions( $post_id ) {
		$post_id = (int) $post_id;

		// 1) Prefer cache-aware policy
		$payload = $this->cache->get_post_payload( $post_id );
		if ( $this->cache->is_stale( $payload ) ) {
			return true;
		}

		// 2) Weekly fallback policy when payload exists but not considered stale
		if ( is_array( $payload ) && isset( $payload['updated_at'] ) ) {
			if ( ( time() - (int) $payload['updated_at'] ) > WEEK_IN_SECONDS ) {
				return true;
			}
		}

		// 3) Legacy fallback to meta timestamp if no payload
		if ( ! $payload ) {
			$last_update = get_post_meta( $post_id, '_carbon_emissions_updated', true );
			if ( empty( $last_update ) || ( strtotime( $last_update ) < ( time() - WEEK_IN_SECONDS ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process emissions calculation for a post.
	 *
	 * Side effects:
	 * - Updates `carbonfooter_last_processed` option on success
	 * - Clears transient lock for the given post
	 *
	 * @param int $post_id Post ID to process.
	 * @return void
	 */
	public function process_emissions( $post_id ) {
		// Process the calculation
		$result = $this->emissions->process_post( $post_id );

		if ( $result ) {
			update_option( 'carbonfooter_last_processed', time() );
		}

		// Clear the processing transient
		delete_transient( 'carbonfooter_processing_' . $post_id );
	}
}
