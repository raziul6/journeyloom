<?php
namespace JourneyLoom\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class RazorpayGateway extends AbstractGateway {
    public function get_id() { return 'razorpay'; }
    public function get_title() { return __( 'Razorpay', 'journeyloom' ); }

    /**
     * Razorpay is a Pro feature, and only offered when fully configured.
     */
    public function is_enabled() {
        return wptm_is_pro()
            && (bool) get_option( 'wptm_razorpay_enabled', false )
            && '' !== trim( (string) get_option( 'wptm_razorpay_key_id', '' ) )
            && '' !== trim( (string) get_option( 'wptm_razorpay_key_secret', '' ) );
    }

    public function process( $data ) {
        return $this->create_order( absint( $data['booking_id'] ?? 0 ) );
    }

    private function keys() {
        return array(
            'id'     => trim( (string) get_option( 'wptm_razorpay_key_id', '' ) ),
            'secret' => trim( (string) get_option( 'wptm_razorpay_key_secret', '' ) ),
        );
    }

    private function auth_header( $keys ) {
        return 'Basic ' . base64_encode( $keys['id'] . ':' . $keys['secret'] );
    }

    /**
     * Create a Razorpay Order for a booking. The amount always comes from the
     * server-side booking total, never the request.
     */
    public function create_order( $booking_id ) {
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return array( 'success' => false, 'message' => __( 'Booking not found.', 'journeyloom' ) );
        }

        $keys = $this->keys();
        if ( '' === $keys['id'] || '' === $keys['secret'] ) {
            return array( 'success' => false, 'message' => __( 'Razorpay not configured.', 'journeyloom' ) );
        }

        // Razorpay amounts are in the currency's smallest unit (e.g. paise).
        $amount = intval( round( (float) $booking->total_price * 100 ) );
        if ( $amount <= 0 ) {
            return array( 'success' => false, 'message' => __( 'Invalid payment amount.', 'journeyloom' ) );
        }

        $response = wp_remote_post( 'https://api.razorpay.com/v1/orders', array(
            'headers' => array(
                'Authorization' => $this->auth_header( $keys ),
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 30,
            'body'    => wp_json_encode( array(
                'amount'   => $amount,
                'currency' => strtoupper( $booking->currency ),
                'receipt'  => $booking->booking_number,
                'notes'    => array( 'booking_id' => (string) $booking_id ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['id'] ) ) {
            $msg = $body['error']['description'] ?? __( 'Could not start the payment.', 'journeyloom' );
            return array( 'success' => false, 'message' => $msg );
        }

        return array(
            'success'     => true,
            'order_id'    => $body['id'],
            'amount'      => $amount,
            'currency'    => strtoupper( $booking->currency ),
            'key_id'      => $keys['id'],
            'name'        => get_bloginfo( 'name' ),
            /* translators: %s: booking reference number. */
            'description' => sprintf( __( 'Booking %s', 'journeyloom' ), $booking->booking_number ),
            'prefill'     => array(
                'name'    => $booking->customer_name,
                'email'   => $booking->customer_email,
                'contact' => $booking->customer_phone,
            ),
        );
    }

    /**
     * Verify a completed Razorpay payment and mark the booking paid.
     *
     * Defence in depth: (1) the checkout signature is verified with the key
     * secret, then (2) the payment is fetched server-side to confirm it belongs
     * to this order and covers the booking amount — never trusting the client.
     */
    public function verify_payment( $payment_id, $order_id, $signature, $booking_id ) {
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return array( 'success' => false, 'message' => __( 'Booking not found.', 'journeyloom' ) );
        }

        $keys = $this->keys();
        if ( '' === $keys['secret'] ) {
            return array( 'success' => false, 'message' => __( 'Razorpay not configured.', 'journeyloom' ) );
        }

        // 1) Signature integrity: HMAC-SHA256( "{order_id}|{payment_id}", secret ).
        $expected = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $keys['secret'] );
        if ( ! hash_equals( $expected, (string) $signature ) ) {
            return array( 'success' => false, 'message' => __( 'Payment verification failed.', 'journeyloom' ) );
        }

        // 2) Fetch the payment to confirm order binding, amount and status.
        $response = wp_remote_get( 'https://api.razorpay.com/v1/payments/' . rawurlencode( $payment_id ), array(
            'headers' => array( 'Authorization' => $this->auth_header( $keys ) ),
            'timeout' => 30,
        ) );
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }
        $p = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $p['status'] ) ) {
            return array( 'success' => false, 'message' => __( 'Payment not found.', 'journeyloom' ) );
        }
        if ( ! empty( $p['order_id'] ) && $p['order_id'] !== $order_id ) {
            return array( 'success' => false, 'message' => __( 'Payment does not match this booking.', 'journeyloom' ) );
        }
        $expected_amount = intval( round( (float) $booking->total_price * 100 ) );
        if ( (int) ( $p['amount'] ?? 0 ) < $expected_amount ) {
            return array( 'success' => false, 'message' => __( 'Payment amount mismatch.', 'journeyloom' ) );
        }

        // Auto-captured orders return 'captured'. If only 'authorized', capture now.
        if ( 'authorized' === $p['status'] ) {
            $cap = wp_remote_post( 'https://api.razorpay.com/v1/payments/' . rawurlencode( $payment_id ) . '/capture', array(
                'headers' => array(
                    'Authorization' => $this->auth_header( $keys ),
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 30,
                'body'    => wp_json_encode( array(
                    'amount'   => (int) $p['amount'],
                    'currency' => $p['currency'] ?? strtoupper( $booking->currency ),
                ) ),
            ) );
            if ( is_wp_error( $cap ) ) {
                return array( 'success' => false, 'message' => $cap->get_error_message() );
            }
            $capb = json_decode( wp_remote_retrieve_body( $cap ), true );
            if ( ( $capb['status'] ?? '' ) !== 'captured' ) {
                return array( 'success' => false, 'message' => __( 'Payment could not be captured.', 'journeyloom' ) );
            }
        } elseif ( 'captured' !== $p['status'] ) {
            return array( 'success' => false, 'message' => __( 'Payment was not completed.', 'journeyloom' ) );
        }

        $this->complete_payment( $booking_id, $payment_id );

        return array(
            'success'  => true,
            'message'  => __( 'Payment successful!', 'journeyloom' ),
            'redirect' => $this->confirm_url( $booking_id ),
        );
    }

    /**
     * The public URL to register in the Razorpay Dashboard as a webhook endpoint.
     */
    public static function webhook_url() {
        return rest_url( 'wptm/v1/razorpay-webhook' );
    }

    /**
     * Handle an incoming Razorpay webhook event.
     *
     * The authoritative confirmation path: even if the customer closes the
     * checkout modal after paying, Razorpay still notifies us and the booking
     * gets marked paid. The body is verified against the webhook secret (HMAC of
     * the raw payload) before anything is trusted.
     *
     * @param \WP_REST_Request $request The webhook request.
     * @return \WP_REST_Response
     */
    public function handle_webhook( $request ) {
        $secret = trim( (string) get_option( 'wptm_razorpay_webhook_secret', '' ) );
        if ( '' === $secret ) {
            return new \WP_REST_Response( array( 'error' => 'Webhook secret not configured.' ), 400 );
        }

        $payload = $request->get_body();
        $sig     = (string) $request->get_header( 'x-razorpay-signature' );

        // Razorpay signs the raw body with the webhook secret (hex HMAC-SHA256).
        if ( '' === $sig || ! hash_equals( hash_hmac( 'sha256', $payload, $secret ), $sig ) ) {
            return new \WP_REST_Response( array( 'error' => 'Invalid signature.' ), 400 );
        }

        $event = json_decode( $payload, true );
        if ( ! is_array( $event ) || empty( $event['event'] ) ) {
            return new \WP_REST_Response( array( 'error' => 'Malformed event.' ), 400 );
        }

        // We only act on a successful payment. Acknowledge the rest with 200.
        if ( ! in_array( $event['event'], array( 'order.paid', 'payment.captured' ), true ) ) {
            return new \WP_REST_Response( array( 'received' => true, 'ignored' => $event['event'] ), 200 );
        }

        $order   = isset( $event['payload']['order']['entity'] ) && is_array( $event['payload']['order']['entity'] ) ? $event['payload']['order']['entity'] : array();
        $payment = isset( $event['payload']['payment']['entity'] ) && is_array( $event['payload']['payment']['entity'] ) ? $event['payload']['payment']['entity'] : array();

        // booking_id comes from the order notes we set at create time (preferred),
        // falling back to payment notes.
        $booking_id = (int) ( $order['notes']['booking_id'] ?? $payment['notes']['booking_id'] ?? 0 );
        if ( ! $booking_id ) {
            return new \WP_REST_Response( array( 'received' => true, 'note' => 'No booking_id in notes.' ), 200 );
        }

        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return new \WP_REST_Response( array( 'received' => true, 'note' => 'Booking not found.' ), 200 );
        }

        // Idempotent: if already paid (e.g. the checkout handler won the race),
        // acknowledge and stop so we never double-fire the confirmation email.
        if ( 'paid' === ( $booking->payment_status ?? '' ) ) {
            return new \WP_REST_Response( array( 'received' => true, 'already_paid' => true ), 200 );
        }

        $expected = intval( round( (float) $booking->total_price * 100 ) );
        $paid     = (int) ( $order['amount_paid'] ?? $payment['amount'] ?? 0 );
        if ( $paid < $expected ) {
            return new \WP_REST_Response( array( 'received' => true, 'note' => 'Amount mismatch.' ), 200 );
        }

        $txn = is_string( $payment['id'] ?? null ) ? $payment['id'] : '';
        $this->complete_payment( $booking_id, $txn );

        return new \WP_REST_Response( array( 'received' => true, 'booking_id' => $booking_id ), 200 );
    }
}
