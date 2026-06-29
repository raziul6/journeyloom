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
        return wptm_is_pro() // Online card payments are a Pro feature.
            && (bool) get_option( 'wptm_stripe_enabled', false )
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

    /**
     * The public URL to register in the Stripe Dashboard as a webhook endpoint.
     */
    public static function webhook_url() {
        return rest_url( 'wptm/v1/stripe-webhook' );
    }

    /**
     * Handle an incoming Stripe webhook event.
     *
     * This is the authoritative confirmation path: even if the customer closes
     * the tab before the browser-side confirm runs, Stripe still notifies us and
     * the booking gets marked paid. The payload is verified against the endpoint
     * signing secret before anything is trusted.
     *
     * @param \WP_REST_Request $request The webhook request.
     * @return \WP_REST_Response
     */
    public function handle_webhook( $request ) {
        $secret = trim( (string) get_option( 'wptm_stripe_webhook_secret', '' ) );
        if ( '' === $secret ) {
            // Without a signing secret we cannot trust the payload — refuse to act.
            return new \WP_REST_Response( array( 'error' => 'Webhook signing secret not configured.' ), 400 );
        }

        $payload = $request->get_body();
        $sig     = (string) $request->get_header( 'stripe-signature' );

        if ( ! $this->verify_webhook_signature( $payload, $sig, $secret ) ) {
            return new \WP_REST_Response( array( 'error' => 'Invalid signature.' ), 400 );
        }

        $event = json_decode( $payload, true );
        if ( ! is_array( $event ) || empty( $event['type'] ) ) {
            return new \WP_REST_Response( array( 'error' => 'Malformed event.' ), 400 );
        }

        // We only act on a successful PaymentIntent. Acknowledge everything else
        // with 200 so Stripe doesn't keep retrying events we don't use.
        if ( 'payment_intent.succeeded' !== $event['type'] ) {
            return new \WP_REST_Response( array( 'received' => true, 'ignored' => $event['type'] ), 200 );
        }

        $intent     = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();
        $booking_id = (int) ( $intent['metadata']['booking_id'] ?? 0 );

        // From here we return 200 on business-rule rejections too: the event is
        // valid and authenticated, it just isn't actionable — retrying won't help.
        if ( ! $booking_id ) {
            return new \WP_REST_Response( array( 'received' => true, 'note' => 'No booking_id in metadata.' ), 200 );
        }

        $booking = \WPTravelMachine\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            return new \WP_REST_Response( array( 'received' => true, 'note' => 'Booking not found.' ), 200 );
        }

        // Idempotent: if it's already paid (e.g. the browser-side confirm won the
        // race), acknowledge and stop so we never double-fire confirmation email.
        if ( 'paid' === ( $booking->payment_status ?? '' ) ) {
            return new \WP_REST_Response( array( 'received' => true, 'already_paid' => true ), 200 );
        }

        // The amount must cover the server-side booking total.
        $expected = intval( round( (float) $booking->total_price * 100 ) );
        $received = (int) ( $intent['amount_received'] ?? $intent['amount'] ?? 0 );
        if ( $received < $expected ) {
            return new \WP_REST_Response( array( 'received' => true, 'note' => 'Amount mismatch.' ), 200 );
        }

        $txn = is_string( $intent['latest_charge'] ?? null ) ? $intent['latest_charge'] : ( $intent['id'] ?? '' );
        $this->complete_payment( $booking_id, $txn );

        return new \WP_REST_Response( array( 'received' => true, 'booking_id' => $booking_id ), 200 );
    }

    /**
     * Verify a Stripe-Signature header without the Stripe SDK.
     *
     * Stripe signs "{timestamp}.{payload}" with HMAC-SHA256 using the endpoint
     * secret. The header looks like: "t=12345,v1=abc...[,v1=def...]".
     *
     * @param string $payload    Raw request body.
     * @param string $sig_header The Stripe-Signature header value.
     * @param string $secret     The endpoint signing secret (whsec_…).
     * @param int    $tolerance  Max age, in seconds, to limit replay (0 = skip).
     * @return bool
     */
    private function verify_webhook_signature( $payload, $sig_header, $secret, $tolerance = 300 ) {
        if ( '' === $sig_header ) {
            return false;
        }

        $timestamp  = '';
        $signatures = array();
        foreach ( explode( ',', $sig_header ) as $part ) {
            $kv = explode( '=', trim( $part ), 2 );
            if ( 2 !== count( $kv ) ) {
                continue;
            }
            if ( 't' === $kv[0] ) {
                $timestamp = $kv[1];
            } elseif ( 'v1' === $kv[0] ) {
                $signatures[] = $kv[1];
            }
        }

        if ( '' === $timestamp || empty( $signatures ) ) {
            return false;
        }

        // Reject stale events to limit replay attacks.
        if ( $tolerance > 0 && abs( time() - (int) $timestamp ) > $tolerance ) {
            return false;
        }

        $expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
        foreach ( $signatures as $candidate ) {
            if ( hash_equals( $expected, $candidate ) ) {
                return true;
            }
        }
        return false;
    }
}
