<?php

/**
 * Background Processor for emissions calculations.
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
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
class Background_Processor
{
  /**
   * Emissions instance.
   *
   * @var Emissions
   */
  private $emissions;

  /**
   * Constructor.
   *
   * Hooks:
   * - `wp` to consider scheduling on page view
   * - `carbonfooter_process_emissions` to process queued jobs
   */
  public function __construct()
  {
    $this->emissions = new Emissions();

    // Hook into post view to schedule processing
    add_action('wp', array($this, 'maybe_schedule_processing'));

    // Hook for processing
    add_action('carbonfooter_process_emissions', array($this, 'process_emissions'), 10, 1);
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
  public function maybe_schedule_processing()
  {
    // Only process on singular posts/pages
    if (!\is_singular()) {
      return;
    }

    $post_id = get_the_ID();

    // Check if we need to update
    if ($this->should_update_emissions($post_id)) {
      // Check if already scheduled or processing
      if (!wp_next_scheduled('carbonfooter_process_emissions', array($post_id)) && !get_transient('carbonfooter_processing_' . $post_id)) {
        // Set a transient to prevent duplicate processing
        set_transient('carbonfooter_processing_' . $post_id, true, 5 * MINUTE_IN_SECONDS);

        // Schedule immediate processing using wp-cron
        wp_schedule_single_event(time(), 'carbonfooter_process_emissions', array($post_id));
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
  private function should_update_emissions($post_id)
  {
    // Get last update time
    $last_update = get_post_meta($post_id, '_carbon_emissions_updated', true);

    // If never calculated or older than 1 week
    if (empty($last_update) || (strtotime($last_update) < (time() - WEEK_IN_SECONDS))) {
      return true;
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
  public function process_emissions($post_id)
  {
    // Process the calculation
    $result = $this->emissions->process_post($post_id);

    if ($result) {
      update_option('carbonfooter_last_processed', time());
    }

    // Clear the processing transient
    delete_transient('carbonfooter_processing_' . $post_id);
  }
}
