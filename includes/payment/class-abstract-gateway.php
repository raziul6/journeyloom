<?php
namespace JourneyLoom\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


abstract class AbstractGateway {
    abstract public function get_id();
    abstract public function get_title();
    abstract public function is_enabled();
    abstract public function process( $data );

    public function get_icon() { return ''; }
    public function get_description() { return ''; }

    /**
     * Booking confirmation/order page URL for a completed payment, including
     * the booking's access key so the customer can view their details.
     */
    protected function confirm_url( $booking_id ) {
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( absint( $booking_id ) );
        return wptm_booking_confirmation_url( $booking );
    }

    protected function complete_payment( $booking_id, $transaction_id = '' ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'wptm_bookings',
            array( 'payment_status' => 'paid', 'transaction_id' => $transaction_id, 'status' => 'confirmed' ),
            array( 'id' => $booking_id ), array( '%s', '%s', '%s' ), array( '%d' )
        );
        do_action( 'wptm_payment_completed', $booking_id, $this->get_id() );
    }
}
