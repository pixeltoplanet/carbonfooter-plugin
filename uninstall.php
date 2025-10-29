<?php

/**
 * Uninstall CarbonFooter
 *
 * @package CarbonFooter
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all post meta
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		'_carbon_%'
	)
);

// Delete all options
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		'carbonfooter_%'
	)
);

// Clear any cached data
wp_cache_flush();
