<?php
namespace WPTravelMachine\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class StripeGateway extends AbstractGateway {
    public function get_id() { return 'stripe'; }
    public function get_title() { return __( 'Credit Card (Stripe)', 'wp-travel-machine' ); }

    /**
     * Only offer Stripe when it is both enabled and fully configured — otherwise
     * the customer would pick a method that can only fail.
     */
    public function is_enabled() {
        return (bool) get_option( 'wptm_stripe_enabled', false )
            && '' !== trim( (string) get_option( 'wptm_stripe_secret_key', '' ) )
            && '' !== trim( (string) get_option( 'wptm_stripe_publishable_key', '' ) );
    }

    /**
     * Entry point from wptm_process_payment — for the SCA flow this just creates
     * a PaymentIntent. The card is confirmed client-side (3-D Secure handled by
     * Stripe.js) and finalised server-side via confirm_payment().
     */
    public function process( $data ) {
        return $this->create_payment_intent( absint( $data['booking_id'] ?? 0 ) );
    }

    /**
     * Create a PaymentIntent for a booking and return its client secret. The
     * amount always comes from the server-side booking total, never the request.
     */
    public function create_payment_intent( $booking_id ) {
        $booking = \WPTravelMachine\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return array( 'success' => false, 'message' => __( 'Booking not found.', 'wp-travel-machine' ) );
        }

        $secret_key = get_option( 'wptm_stripe_secret_key', '' );
        if ( empty( $secret_key ) ) {
            return array( 'success' => false, 'message' => __( 'Stripe not configured.', 'wp-travel-machine' ) );
        }

        $amount = intval( round( (float) $booking->total_price * 100 ) );
        if ( $amount <= 0 ) {
            return array( 'success' => false, 'message' => __( 'Invalid payment amount.', 'wp-travel-machine' ) );
        }

        $response = wp_remote_post( 'https://api.stripe.com/v1/payment_intents', array(
            'headers' => array( 'Authorization' => 'Bearer ' . $secret_key ),
            'timeout' => 30,
            'body'    => array(
                'amount'               => $amount,
                'currency'             => strtolower( $booking->currency ),
                'description'          => sprintf( 'Booking #%s', $booking->booking_number ),
                'payment_method_types' => array( 'card' ),
                'metadata'             => array( 'booking_id' => $booking_id ),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['client_secret'] ) || empty( $body['id'] ) ) {
            return array( 'success' => false, 'message' => $body['error']['message'] ?? __( 'Could not start the payment.', 'wp-travel-machine' ) );
        }

        return array(
            'success'       => true,
            'client_secret' => $body['client_secret'],
            'intent_id'     => $body['id'],
        );
    }

    /**
     * Verify a PaymentIntent server-side after the client confirms the card and
     * mark the booking paid. The intent is checked for succeeded status, a
     * matching booking id, and a sufficient captured amount — never trusting the
     * client's claim that payment succeeded.
     */
    public function confirm_payment( $intent_id, $booking_id ) {
        $booking = \WPTravelMachine\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return array( 'success' => false, 'message' => __( 'Booking not found.', 'wp-travel-machine' ) );
        }

        $secret_key = get_option( 'wptm_stripe_secret_key', '' );
        if ( empty( $secret_key ) ) {
            return array( 'success' => false, 'message' => __( 'Stripe not configured.', 'wp-travel-machine' ) );
        }

        $response = wp_remote_get( 'https://api.stripe.com/v1/payment_intents/' . rawurlencode( $intent_id ), array(
            'headers' => array( 'Authorization' => 'Bearer ' . $secret_key ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['status'] ) || 'succeeded' !== $body['status'] ) {
            return array( 'success' => false, 'message' => $body['error']['message'] ?? __( 'Payment was not completed.', 'wp-travel-machine' ) );
        }

        // Bind the intent to this booking and confirm the amount, to reject a
        // reused or under-funded intent.
        if ( (int) ( $body['metadata']['booking_id'] ?? 0 ) !== (int) $booking_id ) {
            return array( 'success' => false, 'message' => __( 'Payment does not match this booking.', 'wp-travel-machine' ) );
        }
        $expected = intval( round( (float) $booking->total_price * 100 ) );
        $received = (int) ( $body['amount_received'] ?? $body['amount'] ?? 0 );
        if ( $received < $expected ) {
            return array( 'success' => false, 'message' => __( 'Payment amount mismatch.', 'wp-travel-machine' ) );
        }

        $txn = is_string( $body['latest_charge'] ?? null ) ? $body['latest_charge'] : $intent_id;
        $this->complete_payment( $booking_id, $txn );

        return array(
            'success'  => true,
            'message'  => __( 'Payment successful!', 'wp-travel-machine' ),
            'redirect' => $this->confirm_url( $booking_id ),
        );
    }
}
