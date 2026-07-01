<?php
namespace JourneyLoom\Database;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Database schema — tables created by Activator.
 * This class provides query helpers and migration support.
 */
class Schema {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'check_migration' ) );
    }

    public function check_migration() {
        $current = get_option( 'wptm_db_version', '0' );
        if ( version_compare( $current, WPTM_DB_VERSION, '<' ) ) {
            \JourneyLoom\Activator::activate();
        }
    }

    public static function get_table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'wptm_' . $name;
    }
}
