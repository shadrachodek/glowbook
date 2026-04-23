<?php
/**
 * Uninstall script for GlowBook plugin.
 *
 * This file runs when the plugin is deleted from WordPress admin.
 * It removes all plugin data including database tables, options, and posts.
 *
 * @package GlowBook
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Check if we should preserve data (optional setting).
$preserve_data = get_option( 'sodek_gb_preserve_data_on_uninstall', false );

if ( $preserve_data ) {
	return;
}

// Delete custom post types and their meta.
$post_types = array( 'sodek_gb_service', 'sodek_gb_booking', 'sodek_gb_addon' );

foreach ( $post_types as $post_type ) {
	$posts = get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

// Delete taxonomy terms.
$terms = get_terms( array(
	'taxonomy'   => 'sodek_gb_service_cat',
	'hide_empty' => false,
	'fields'     => 'ids',
) );

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'sodek_gb_service_cat' );
	}
}

// Delete custom product type term.
$product_type_term = get_term_by( 'slug', 'bookable_service', 'product_type' );
if ( $product_type_term ) {
	wp_delete_term( $product_type_term->term_id, 'product_type' );
}

// Delete all plugin options.
$options_to_delete = array(
	'sodek_gb_version',
	'sodek_gb_db_version',
	'sodek_gb_primary_color',
	'sodek_gb_button_style',
	'sodek_gb_border_radius',
	'sodek_gb_inherit_theme_colors',
	'sodek_gb_button_text_type',
	'sodek_gb_button_text_custom',
	'sodek_gb_email_from_name',
	'sodek_gb_email_from_address',
	'sodek_gb_admin_email',
	'sodek_gb_enable_reminders',
	'sodek_gb_reminder_hours',
	'sodek_gb_enable_whatsapp',
	'sodek_gb_whatsapp_number',
	'sodek_gb_google_calendar_enabled',
	'sodek_gb_google_calendar_id',
	'sodek_gb_cancellation_window',
	'sodek_gb_late_cancellation_fee',
	'sodek_gb_refund_method',
	'sodek_gb_preserve_data_on_uninstall',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Delete options with wildcard pattern.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'sodek_gb_%'" );

// Delete transients.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sodek_gb_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sodek_gb_%'" );

// Delete post meta associated with WooCommerce products.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_sodek_gb_%'" );

// Delete user meta.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'sodek_gb_%'" );

// Drop custom database tables.
$tables = array(
	$wpdb->prefix . 'sodek_gb_availability',
	$wpdb->prefix . 'sodek_gb_availability_overrides',
	$wpdb->prefix . 'sodek_gb_booked_slots',
	$wpdb->prefix . 'sodek_gb_sent_reminders',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Clear scheduled cron events.
$cron_hooks = array(
	'sodek_gb_send_reminders',
	'sodek_gb_cleanup_expired_slots',
	'sodek_gb_daily_maintenance',
);

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
	// Clear all instances.
	wp_unschedule_hook( $hook );
}

// Clear any cached data.
wp_cache_flush();