<?php
namespace WPTravelMachine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register' ) );
        add_action( 'wp_ajax_wptm_save_settings', array( $this, 'save_ajax' ) );
    }

    public function register() {
        $fields = array(
            'wptm_currency', 'wptm_currency_symbol', 'wptm_currency_position',
            'wptm_tax_enabled', 'wptm_tax_rate', 'wptm_items_per_page', 'wptm_pagination_type',
            'wptm_gallery_style',
            'wptm_enable_wishlist', 'wptm_enable_compare', 'wptm_enable_reviews',
            'wptm_enable_ai', 'wptm_ai_provider', 'wptm_ai_api_key', 'wptm_ai_base_url', 'wptm_ai_model',
            'wptm_enquiry_enabled', 'wptm_enquiry_title', 'wptm_enquiry_email', 'wptm_enquiry_fields',
            'wptm_stripe_enabled', 'wptm_stripe_publishable_key', 'wptm_stripe_secret_key',
            'wptm_paypal_enabled', 'wptm_paypal_client_id', 'wptm_paypal_secret', 'wptm_paypal_mode',
            'wptm_manual_payment', 'wptm_bank_instructions', 'wptm_booking_email', 'wptm_terms_page',
            // Email notifications.
            'wptm_email_from_name', 'wptm_email_from_address', 'wptm_email_customer_enabled',
            'wptm_email_admin_enabled', 'wptm_email_customer_subject', 'wptm_email_footer_text',
            // Page settings.
            'wptm_page_search', 'wptm_page_destinations', 'wptm_page_trips',
            'wptm_page_hotels', 'wptm_page_checkout', 'wptm_page_confirmation',
            'wptm_page_wishlist', 'wptm_page_cart', 'wptm_page_my_bookings',
        );
        foreach ( $fields as $f ) register_setting( 'wptm_settings', $f );
    }

    public function save_ajax() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        // Raw payload; each branch below unslashes + sanitizes the keys it uses.
        $fields = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? $_POST['settings'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        if ( ! is_array( $fields ) ) wp_send_json_error();

        // Enquiry form fields (array of field defs) — handled before the flat loop.
        if ( isset( $fields['wptm_enquiry_present'] ) ) {
            $raw   = ( isset( $fields['wptm_enquiry_fields'] ) && is_array( $fields['wptm_enquiry_fields'] ) ) ? $fields['wptm_enquiry_fields'] : array();
            $clean = array();
            foreach ( $raw as $f ) {
                $label = sanitize_text_field( wp_unslash( $f['label'] ?? '' ) );
                if ( '' === trim( $label ) ) continue;
                $type = $f['type'] ?? 'text';
                if ( ! in_array( $type, array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'country' ), true ) ) {
                    $type = 'text';
                }
                $clean[] = array(
                    'label'    => $label,
                    'type'     => $type,
                    'required' => ! empty( $f['required'] ) ? 1 : 0,
                    'options'  => sanitize_text_field( wp_unslash( $f['options'] ?? '' ) ),
                );
            }
            update_option( 'wptm_enquiry_fields', $clean );
            unset( $fields['wptm_enquiry_fields'], $fields['wptm_enquiry_present'] );
        }

        // Collect all known checkbox keys so we can set unchecked ones to empty.
        $checkbox_keys = array(
            'wptm_enable_wishlist', 'wptm_enable_compare', 'wptm_enable_reviews',
            'wptm_enable_ai', 'wptm_tax_enabled', 'wptm_enquiry_enabled',
            'wptm_stripe_enabled', 'wptm_paypal_enabled', 'wptm_manual_payment',
            'wptm_email_customer_enabled', 'wptm_email_admin_enabled',
        );

        // Set unchecked checkboxes to empty string.
        foreach ( $checkbox_keys as $cb_key ) {
            if ( ! isset( $fields[ $cb_key ] ) ) {
                $fields[ $cb_key ] = '';
            }
        }

        // Multi-line fields keep their newlines (sanitize_text_field would strip them).
        $textarea_keys = array( 'wptm_bank_instructions', 'wptm_email_footer_text' );
        foreach ( $textarea_keys as $ta_key ) {
            if ( isset( $fields[ $ta_key ] ) && ! is_array( $fields[ $ta_key ] ) ) {
                update_option( $ta_key, sanitize_textarea_field( wp_unslash( $fields[ $ta_key ] ) ) );
                unset( $fields[ $ta_key ] );
            }
        }

        foreach ( $fields as $key => $value ) {
            if ( is_array( $value ) ) continue; // Arrays are handled explicitly above.
            if ( strpos( $key, 'wptm_' ) === 0 ) {
                update_option( sanitize_key( $key ), sanitize_text_field( wp_unslash( $value ) ) );
            }
        }
        wp_send_json_success( array( 'message' => __( 'Settings saved.', 'wp-travel-machine' ) ) );
    }
}
