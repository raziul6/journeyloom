<?php
/**
 * Uninstall — runs when the plugin is deleted.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Standalone uninstall script: variables are file-scoped (the file runs once and
// exits), and the queries drop the plugin's own custom tables / clean its meta —
// operations with no core API and no object cache to keep.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

global $wpdb;

// Remove all plugin options.
$options = array(
    'wptm_version', 'wptm_db_version',
    'wptm_currency', 'wptm_currency_symbol', 'wptm_currency_position',
    'wptm_date_format', 'wptm_tax_enabled', 'wptm_tax_rate',
    'wptm_items_per_page', 'wptm_pagination_type', 'wptm_enable_wishlist', 'wptm_enable_compare',
    'wptm_enable_reviews',
    'wptm_manual_payment', 'wptm_booking_email', 'wptm_terms_page',
    'wptm_search_form_fields',
    'wptm_gallery_style', 'wptm_pages_version',
    'wptm_enquiry_enabled', 'wptm_enquiry_title', 'wptm_enquiry_email', 'wptm_enquiry_fields',
    // Page options.
    'wptm_page_search', 'wptm_page_destinations', 'wptm_page_trips',
    'wptm_page_hotels', 'wptm_page_checkout', 'wptm_page_confirmation',
    'wptm_page_wishlist', 'wptm_page_cart', 'wptm_page_my_bookings',
    'wptm_page_activities', 'wptm_page_trip_types', 'wptm_page_difficulties',
    'wptm_page_hotel_types', 'wptm_page_hotel_facilities',
    'wptm_bank_instructions',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Drop custom tables.
$tables = array(
    'wptm_bookings', 'wptm_booking_meta', 'wptm_rooms',
    'wptm_availability', 'wptm_reviews', 'wptm_wishlist',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Remove all post meta with our prefix.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wptm_%'" );

// Remove term meta.
$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE '_wptm_%'" );

// Clean transients (named + our wildcard-keyed ones: carts, rate limits).
delete_transient( 'wptm_flush_rewrites' );
delete_transient( 'wptm_activation_redirect' );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_wptm\_%' OR option_name LIKE '\_transient\_timeout\_wptm\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Clear cron.
wp_clear_scheduled_hook( 'wptm_daily_cleanup' );
wp_clear_scheduled_hook( 'wptm_check_booking_expiry' );
