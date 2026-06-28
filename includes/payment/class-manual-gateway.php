<?php
namespace WPTravelMachine\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class ManualGateway extends AbstractGateway {
    public function get_id() { return 'manual'; }
    public function get_title() { return __( 'Manual / Bank Transfer', 'wp-travel-machine' ); }
    public function is_enabled() { return (bool) get_option( 'wptm_manual_payment', true ); }
    public function get_description() { return __( 'Pay via bank transfer. Your booking will be confirmed after payment verification.', 'wp-travel-machine' ); }

    public function process( $data ) {
        $booking_id = absint( $data['booking_id'] ?? 0 );
        if ( ! $booking_id ) return array( 'success' => false, 'message' => __( 'Invalid booking.', 'wp-travel-machine' ) );

        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'wptm_bookings',
            array( 'payment_method' => 'manual', 'payment_status' => 'pending' ),
            array( 'id' => $booking_id )
        );

        do_action( 'wptm_manual_payment_pending', $booking_id );

        return array(
            'success' => true,
            'message' => __( 'Booking submitted. Please complete the bank transfer.', 'wp-travel-machine' ),
            'redirect' => add_query_arg( 'booking', $booking_id, home_url( '/booking-confirmation/' ) ),
        );
    }
}
