<?php
/**
 * Plugin deactivation logic.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Deactivator
 */
class Deactivator {

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate() {
        // Flush rewrite rules to remove custom post type permalinks.
        flush_rewrite_rules();

        // Clear any scheduled cron events.
        wp_clear_scheduled_hook( 'wptm_daily_cleanup' );
        wp_clear_scheduled_hook( 'wptm_check_booking_expiry' );

        // Clear transients.
        delete_transient( 'wptm_flush_rewrites' );
    }
}
