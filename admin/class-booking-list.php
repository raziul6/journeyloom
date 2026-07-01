<?php
namespace JourneyLoom\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class BookingList {
    public function __construct() {
        add_action( 'wp_ajax_wptm_admin_booking_action', array( $this, 'handle_action' ) );
        add_action( 'wp_ajax_wptm_save_coupon', array( $this, 'save_coupon' ) );
        add_action( 'wp_ajax_wptm_get_booking', array( $this, 'get_booking_details' ) );
    }

    /**
     * Return the rendered detail panel for a single booking (admin drawer).
     */
    public function get_booking_details() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $id = absint( $_POST['booking_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();

        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $id );
        if ( ! $booking ) wp_send_json_error( array( 'message' => __( 'Booking not found.', 'journeyloom' ) ) );

        ob_start();
        include WPTM_PLUGIN_DIR . 'admin/views/booking-details.php';
        wp_send_json_success( array( 'html' => ob_get_clean() ) );
    }

    public function handle_action() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $action = sanitize_text_field( wp_unslash( $_POST['booking_action'] ?? '' ) );
        $id = absint( $_POST['booking_id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();

        global $wpdb;
        $table = $wpdb->prefix . 'wptm_bookings';

        switch ( $action ) {
            case 'confirm':
                $wpdb->update( $table, array( 'status' => 'confirmed' ), array( 'id' => $id ) );
                do_action( 'wptm_booking_status_changed', $id, 'confirmed' );
                break;
            case 'cancel':
                $wpdb->update( $table, array( 'status' => 'cancelled' ), array( 'id' => $id ) );
                do_action( 'wptm_booking_status_changed', $id, 'cancelled' );
                break;
            case 'complete':
                $wpdb->update( $table, array( 'status' => 'completed' ), array( 'id' => $id ) );
                break;
            case 'delete':
                $wpdb->delete( $table, array( 'id' => $id ) );
                $wpdb->delete( $wpdb->prefix . 'wptm_booking_meta', array( 'booking_id' => $id ) );
                break;
            default:
                wp_send_json_error();
        }

        wp_send_json_success( array( 'message' => __( 'Booking updated.', 'journeyloom' ) ) );
    }

    public function save_coupon() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $code = strtoupper( sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ) );
        if ( empty( $code ) ) wp_send_json_error( array( 'message' => __( 'Coupon code is required.', 'journeyloom' ) ) );

        global $wpdb;
        $table = $wpdb->prefix . 'wptm_coupons';

        // Check for duplicates.
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE code = %s", $code ) );
        if ( $exists ) wp_send_json_error( array( 'message' => __( 'Coupon code already exists.', 'journeyloom' ) ) );

        $wpdb->insert( $table, array(
            'code'     => $code,
            'type'     => sanitize_text_field( wp_unslash( $_POST['type'] ?? 'percentage' ) ),
            'amount'   => floatval( $_POST['amount'] ?? 0 ),
            'max_uses' => absint( $_POST['max_uses'] ?? 0 ) ?: null,
            'end_date' => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ) ?: null,
            'status'   => 'active',
        ) );

        wp_send_json_success( array( 'message' => __( 'Coupon created.', 'journeyloom' ) ) );
    }

    /**
     * Booking stats, optionally scoped to a booking type.
     *
     * @param string $type '' for all, or 'trip' / 'hotel'.
     * @return array
     */
    public static function get_stats( $type = '' ) {
        global $wpdb;
        $t = $wpdb->prefix . 'wptm_bookings';

        // $type is whitelisted, so a pre-escaped literal predicate is safe here.
        $type     = in_array( $type, array( 'trip', 'hotel' ), true ) ? $type : '';
        $type_and = $type ? " AND booking_type = '" . esc_sql( $type ) . "'" : '';
        $month    = esc_sql( gmdate( 'Y-m-01' ) );

        return array(
            'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE 1=1$type_and" ),
            'pending'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE status='pending'$type_and" ),
            'confirmed'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE status='confirmed'$type_and" ),
            'cancelled'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE status='cancelled'$type_and" ),
            'revenue'    => (float) $wpdb->get_var( "SELECT COALESCE(SUM(total_price),0) FROM $t WHERE payment_status='paid'$type_and" ),
            'this_month' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE created_at >= '$month'$type_and" ),
        );
    }

    /**
     * Total booking counts per type, for the Trips / Hotels filter.
     *
     * @return array{all:int,trip:int,hotel:int}
     */
    public static function get_type_counts() {
        global $wpdb;
        $t = $wpdb->prefix . 'wptm_bookings';
        return array(
            'all'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ),
            'trip'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE booking_type='trip'" ),
            'hotel' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE booking_type='hotel'" ),
        );
    }
}
