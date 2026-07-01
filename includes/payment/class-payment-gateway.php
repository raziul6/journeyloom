<?php
namespace JourneyLoom\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class PaymentGateway {
    private $gateways = array();

    public function __construct() {
        add_action( 'init', array( $this, 'register_gateways' ) );
        add_action( 'wp_ajax_wptm_process_payment', array( $this, 'process_payment' ) );
        add_action( 'wp_ajax_nopriv_wptm_process_payment', array( $this, 'process_payment' ) );

        // Stripe uses a create-intent → confirm-card (SCA/3DS) → verify flow.
        add_action( 'wp_ajax_wptm_stripe_create_intent', array( $this, 'stripe_create_intent' ) );
        add_action( 'wp_ajax_nopriv_wptm_stripe_create_intent', array( $this, 'stripe_create_intent' ) );
        add_action( 'wp_ajax_wptm_stripe_confirm', array( $this, 'stripe_confirm' ) );
        add_action( 'wp_ajax_nopriv_wptm_stripe_confirm', array( $this, 'stripe_confirm' ) );

        // PayPal uses a two-step create/approve/capture flow driven by its JS SDK.
        add_action( 'wp_ajax_wptm_paypal_create_order', array( $this, 'paypal_create_order' ) );
        add_action( 'wp_ajax_nopriv_wptm_paypal_create_order', array( $this, 'paypal_create_order' ) );
        add_action( 'wp_ajax_wptm_paypal_capture_order', array( $this, 'paypal_capture_order' ) );
        add_action( 'wp_ajax_nopriv_wptm_paypal_capture_order', array( $this, 'paypal_capture_order' ) );

        // Razorpay — create order (server) → checkout modal → verify signature.
        add_action( 'wp_ajax_wptm_razorpay_create_order', array( $this, 'razorpay_create_order' ) );
        add_action( 'wp_ajax_nopriv_wptm_razorpay_create_order', array( $this, 'razorpay_create_order' ) );
        add_action( 'wp_ajax_wptm_razorpay_verify', array( $this, 'razorpay_verify' ) );
        add_action( 'wp_ajax_nopriv_wptm_razorpay_verify', array( $this, 'razorpay_verify' ) );

        // Stripe webhook — the authoritative payment-confirmation channel.
        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
    }

    /**
     * Register the public Stripe webhook endpoint. Stripe authenticates itself
     * with a signed payload, so this route is open but the handler verifies the
     * signature before trusting anything.
     */
    public function register_webhook_route() {
        register_rest_route( 'wptm/v1', '/stripe-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_stripe_webhook' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( 'wptm/v1', '/razorpay-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_razorpay_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * Delegate an incoming Razorpay webhook to the Razorpay gateway.
     *
     * @param \WP_REST_Request $request The webhook request.
     * @return \WP_REST_Response
     */
    public function handle_razorpay_webhook( $request ) {
        $gw = $this->get_gateway( 'razorpay' );
        if ( ! $gw ) {
            return new \WP_REST_Response( array( 'error' => 'Razorpay gateway unavailable.' ), 503 );
        }
        return $gw->handle_webhook( $request );
    }

    /**
     * Delegate an incoming Stripe webhook to the Stripe gateway.
     *
     * @param \WP_REST_Request $request The webhook request.
     * @return \WP_REST_Response
     */
    public function handle_stripe_webhook( $request ) {
        $gw = $this->get_gateway( 'stripe' );
        if ( ! $gw ) {
            return new \WP_REST_Response( array( 'error' => 'Stripe gateway unavailable.' ), 503 );
        }
        return $gw->handle_webhook( $request );
    }

    public function register_gateways() {
        $this->gateways = apply_filters( 'wptm_payment_gateways', array(
            'manual'   => new ManualGateway(),
            'stripe'   => new StripeGateway(),
            'paypal'   => new PaypalGateway(),
            'razorpay' => new RazorpayGateway(),
        ) );
    }

    public function get_active_gateways() {
        $active = array();
        foreach ( $this->gateways as $id => $gw ) {
            if ( $gw->is_enabled() ) $active[ $id ] = $gw;
        }
        return $active;
    }

    public function process_payment() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $method = sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'manual' ) );

        if ( ! isset( $this->gateways[ $method ] ) || ! $this->gateways[ $method ]->is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'Payment method not available.', 'journeyloom' ) ) );
        }

        // Individual gateways sanitize the fields they consume.
        $result = $this->gateways[ $method ]->process( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    public function get_gateway( $id ) {
        return $this->gateways[ $id ] ?? null;
    }

    /**
     * Return the active Stripe gateway, or send a JSON error and stop.
     */
    private function require_stripe() {
        $gw = $this->gateways['stripe'] ?? null;
        if ( ! $gw || ! $gw->is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'Stripe is not available.', 'journeyloom' ) ) );
        }
        return $gw;
    }

    /**
     * AJAX: create a Stripe PaymentIntent for a booking and return its client
     * secret (consumed by stripe.confirmCardPayment on the client).
     */
    public function stripe_create_intent() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $gw         = $this->require_stripe();
        $booking_id = absint( $_POST['booking_id'] ?? 0 );

        $result = $gw->create_payment_intent( $booking_id );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    /**
     * AJAX: verify a confirmed PaymentIntent server-side and mark the booking
     * paid. Returns the confirmation redirect.
     */
    public function stripe_confirm() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $gw         = $this->require_stripe();
        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $intent_id  = sanitize_text_field( wp_unslash( $_POST['payment_intent_id'] ?? '' ) );

        if ( ! $intent_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing payment reference.', 'journeyloom' ) ) );
        }

        $result = $gw->confirm_payment( $intent_id, $booking_id );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    /**
     * Return the active PayPal gateway, or send a JSON error and stop.
     */
    private function require_paypal() {
        $gw = $this->gateways['paypal'] ?? null;
        if ( ! $gw || ! $gw->is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'PayPal is not available.', 'journeyloom' ) ) );
        }
        return $gw;
    }

    /**
     * AJAX: create a PayPal order for a booking (called by the PayPal Buttons
     * createOrder callback). Returns the PayPal order id.
     */
    public function paypal_create_order() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $gw         = $this->require_paypal();
        $booking_id = absint( $_POST['booking_id'] ?? 0 );

        $result = $gw->create_order( $booking_id );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    /**
     * AJAX: capture an approved PayPal order and mark the booking paid (called by
     * the PayPal Buttons onApprove callback). Returns the confirmation redirect.
     */
    public function paypal_capture_order() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $gw         = $this->require_paypal();
        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $order_id   = sanitize_text_field( wp_unslash( $_POST['order_id'] ?? '' ) );

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing PayPal order.', 'journeyloom' ) ) );
        }

        $result = $gw->capture_order( $order_id, $booking_id );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    /**
     * Return the active Razorpay gateway, or send a JSON error and stop.
     */
    private function require_razorpay() {
        $gw = $this->gateways['razorpay'] ?? null;
        if ( ! $gw || ! $gw->is_enabled() ) {
            wp_send_json_error( array( 'message' => __( 'Razorpay is not available.', 'journeyloom' ) ) );
        }
        return $gw;
    }

    /**
     * AJAX: create a Razorpay order for a booking (called before opening the
     * Razorpay checkout modal). Returns the order id + checkout options.
     */
    public function razorpay_create_order() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $gw         = $this->require_razorpay();
        $booking_id = absint( $_POST['booking_id'] ?? 0 );

        $result = $gw->create_order( $booking_id );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    /**
     * AJAX: verify a completed Razorpay payment (signature + server-side fetch)
     * and mark the booking paid. Returns the confirmation redirect.
     */
    public function razorpay_verify() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $gw         = $this->require_razorpay();
        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $payment_id = sanitize_text_field( wp_unslash( $_POST['razorpay_payment_id'] ?? '' ) );
        $order_id   = sanitize_text_field( wp_unslash( $_POST['razorpay_order_id'] ?? '' ) );
        $signature  = sanitize_text_field( wp_unslash( $_POST['razorpay_signature'] ?? '' ) );

        if ( ! $payment_id || ! $order_id || ! $signature ) {
            wp_send_json_error( array( 'message' => __( 'Missing payment reference.', 'journeyloom' ) ) );
        }

        $result = $gw->verify_payment( $payment_id, $order_id, $signature, $booking_id );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }
}
