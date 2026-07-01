<?php
namespace JourneyLoom\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class PaypalGateway extends AbstractGateway {
    public function get_id() { return 'paypal'; }
    public function get_title() { return __( 'PayPal', 'journeyloom' ); }

    /**
     * Only offer PayPal when it is both enabled and configured with credentials.
     */
    public function is_enabled() {
        return wptm_is_pro() // Online PayPal payments are a Pro feature.
            && (bool) get_option( 'wptm_paypal_enabled', false )
            && '' !== trim( (string) get_option( 'wptm_paypal_client_id', '' ) )
            && '' !== trim( (string) get_option( 'wptm_paypal_secret', '' ) );
    }

    /**
     * REST API base — sandbox or live depending on the configured mode.
     */
    private function api_base() {
        $mode = get_option( 'wptm_paypal_mode', 'sandbox' );
        return 'sandbox' === $mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    /**
     * Fetch an OAuth2 access token via client-credentials.
     *
     * @return string|\WP_Error
     */
    private function get_access_token() {
        $client_id = get_option( 'wptm_paypal_client_id', '' );
        $secret    = get_option( 'wptm_paypal_secret', '' );
        if ( '' === $client_id || '' === $secret ) {
            return new \WP_Error( 'wptm_paypal', __( 'PayPal is not configured.', 'journeyloom' ) );
        }

        $response = wp_remote_post( $this->api_base() . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => array( 'grant_type' => 'client_credentials' ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['access_token'] ) ) {
            return new \WP_Error( 'wptm_paypal', __( 'Could not authenticate with PayPal.', 'journeyloom' ) );
        }
        return $body['access_token'];
    }

    /**
     * Entry point from wptm_process_payment — for PayPal this just creates the
     * order; the capture happens after the buyer approves it (see capture_order).
     */
    public function process( $data ) {
        return $this->create_order( absint( $data['booking_id'] ?? 0 ) );
    }

    /**
     * Create a PayPal order for a booking. The amount always comes from the
     * server-side booking total, never from the request.
     */
    public function create_order( $booking_id ) {
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return array( 'success' => false, 'message' => __( 'Booking not found.', 'journeyloom' ) );
        }

        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return array( 'success' => false, 'message' => $token->get_error_message() );
        }

        $response = wp_remote_post( $this->api_base() . '/v2/checkout/orders', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 30,
            'body'    => wp_json_encode( array(
                'intent'         => 'CAPTURE',
                'purchase_units' => array(
                    array(
                        'reference_id' => (string) $booking_id,
                        'description'  => sprintf( 'Booking #%s', $booking->booking_number ),
                        'amount'       => array(
                            'currency_code' => strtoupper( $booking->currency ),
                            'value'         => number_format( (float) $booking->total_price, 2, '.', '' ),
                        ),
                    ),
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['id'] ) ) {
            return array( 'success' => false, 'message' => $body['message'] ?? __( 'Could not create PayPal order.', 'journeyloom' ) );
        }

        return array( 'success' => true, 'order_id' => $body['id'] );
    }

    /**
     * Capture an approved PayPal order and mark the booking paid. The captured
     * amount is verified against the booking total to reject tampering.
     */
    public function capture_order( $order_id, $booking_id ) {
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return array( 'success' => false, 'message' => __( 'Booking not found.', 'journeyloom' ) );
        }

        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return array( 'success' => false, 'message' => $token->get_error_message() );
        }

        $response = wp_remote_post( $this->api_base() . '/v2/checkout/orders/' . rawurlencode( $order_id ) . '/capture', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 30,
            'body'    => '{}',
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['status'] ) || 'COMPLETED' !== $body['status'] ) {
            return array( 'success' => false, 'message' => $body['message'] ?? __( 'PayPal payment could not be completed.', 'journeyloom' ) );
        }

        // Verify the captured amount matches the booking total before crediting.
        $capture  = $body['purchase_units'][0]['payments']['captures'][0] ?? array();
        $paid     = isset( $capture['amount']['value'] ) ? (float) $capture['amount']['value'] : 0;
        $expected = round( (float) $booking->total_price, 2 );
        if ( abs( $paid - $expected ) > 0.01 ) {
            return array( 'success' => false, 'message' => __( 'Payment amount mismatch.', 'journeyloom' ) );
        }

        $this->complete_payment( $booking_id, $capture['id'] ?? $order_id );

        return array(
            'success'  => true,
            'message'  => __( 'Payment successful!', 'journeyloom' ),
            'redirect' => $this->confirm_url( $booking_id ),
        );
    }
}
