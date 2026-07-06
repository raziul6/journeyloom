<?php
namespace JourneyLoom\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class PaymentGateway {
    private $gateways = array();

    public function __construct() {
        add_action( 'init', array( $this, 'register_gateways' ) );
        add_action( 'wp_ajax_wptm_process_payment', array( $this, 'process_payment' ) );
        add_action( 'wp_ajax_nopriv_wptm_process_payment', array( $this, 'process_payment' ) );
    }

    public function register_gateways() {
        /**
         * Filter the registered payment gateways.
         *
         * The free plugin ships the Manual / Bank Transfer gateway. Add-ons
         * register additional gateways (id => AbstractGateway instance) here;
         * they are responsible for their own AJAX/REST endpoints.
         *
         * @param array $gateways Gateway id => gateway instance.
         */
        $this->gateways = apply_filters( 'wptm_payment_gateways', array(
            'manual' => new ManualGateway(),
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
            wp_send_json_error( array( 'message' => __( 'Payment method not available.', 'byteflows-travel-hotel-booking' ) ) );
        }

        // Sanitize every posted field before handing it to the gateway (built-in
        // gateways only read booking_id, but third-party gateways registered via
        // the wptm_payment_gateways filter may read others).
        $data               = map_deep( wp_unslash( $_POST ), 'sanitize_text_field' );
        $data['booking_id'] = absint( $_POST['booking_id'] ?? 0 );

        $result = $this->gateways[ $method ]->process( $data );
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    public function get_gateway( $id ) {
        return $this->gateways[ $id ] ?? null;
    }
}
