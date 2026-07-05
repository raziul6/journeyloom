<?php
namespace JourneyLoom\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register' ) );
        add_action( 'wp_ajax_wptm_save_settings', array( $this, 'save_ajax' ) );
    }

    public function register() {
        foreach ( $this->field_sanitizers() as $field => $callback ) {
            register_setting( 'wptm_settings', $field, array( 'sanitize_callback' => $callback ) );
        }
    }

    /**
     * Map each registered setting to a type-appropriate sanitize callback so the
     * Settings API (options.php) save path stores clean data. The AJAX saver in
     * save_ajax() applies the same rules on its own path.
     *
     * @return array<string,callable> Option name => sanitize callback.
     */
    private function field_sanitizers() {
        $email    = 'sanitize_email';
        $url      = 'esc_url_raw';
        $hex      = 'sanitize_hex_color';
        $textarea = 'sanitize_textarea_field';
        $int      = 'absint';
        $text     = 'sanitize_text_field';
        $decimal  = array( $this, 'sanitize_decimal' );
        $bool     = array( $this, 'sanitize_checkbox' );

        return array(
            'wptm_currency' => $text, 'wptm_currency_symbol' => $text, 'wptm_currency_position' => $text,
            'wptm_tax_enabled' => $bool, 'wptm_tax_rate' => $decimal, 'wptm_items_per_page' => $int, 'wptm_pagination_type' => $text,
            'wptm_gallery_style' => $text,
            'wptm_enable_wishlist' => $bool, 'wptm_enable_compare' => $bool, 'wptm_enable_reviews' => $bool,
            'wptm_enable_related' => $bool, 'wptm_related_count' => $int,
            'wptm_color_primary' => $hex, 'wptm_color_discount_ribbon' => $hex, 'wptm_color_featured_ribbon' => $hex, 'wptm_color_icon' => $hex,
            'wptm_enable_ai' => $bool, 'wptm_ai_provider' => $text, 'wptm_ai_api_key' => $text, 'wptm_ai_base_url' => $url, 'wptm_ai_model' => $text,
            'wptm_enquiry_enabled' => $bool, 'wptm_enquiry_title' => $text, 'wptm_enquiry_email' => $email, 'wptm_enquiry_fields' => array( $this, 'sanitize_enquiry_fields' ),
            'wptm_stripe_enabled' => $bool, 'wptm_stripe_publishable_key' => $text, 'wptm_stripe_secret_key' => $text, 'wptm_stripe_webhook_secret' => $text,
            'wptm_paypal_enabled' => $bool, 'wptm_paypal_client_id' => $text, 'wptm_paypal_secret' => $text, 'wptm_paypal_mode' => $text,
            'wptm_razorpay_enabled' => $bool, 'wptm_razorpay_key_id' => $text, 'wptm_razorpay_key_secret' => $text, 'wptm_razorpay_webhook_secret' => $text,
            'wptm_manual_payment' => $bool, 'wptm_bank_instructions' => $textarea, 'wptm_booking_email' => $email, 'wptm_terms_page' => $int,
            // Email notifications.
            'wptm_email_from_name' => $text, 'wptm_email_from_address' => $email, 'wptm_email_customer_enabled' => $bool,
            'wptm_email_admin_enabled' => $bool, 'wptm_email_customer_subject' => $text, 'wptm_email_footer_text' => $textarea,
            // Invoice / company details.
            'wptm_invoice_company' => $text, 'wptm_invoice_address' => $textarea, 'wptm_invoice_email' => $email, 'wptm_invoice_phone' => $text,
            'wptm_invoice_tax_number' => $text, 'wptm_invoice_logo' => $url, 'wptm_invoice_prefix' => $text, 'wptm_invoice_notes' => $textarea,
            // Page settings.
            'wptm_page_search' => $int, 'wptm_page_destinations' => $int, 'wptm_page_trips' => $int,
            'wptm_page_hotels' => $int, 'wptm_page_checkout' => $int, 'wptm_page_confirmation' => $int,
            'wptm_page_wishlist' => $int, 'wptm_page_cart' => $int, 'wptm_page_my_bookings' => $int,
        );
    }

    /**
     * Sanitize a decimal (e.g. tax rate). Non-numeric becomes an empty string.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_decimal( $value ) {
        return is_numeric( $value ) ? (string) round( (float) $value, 4 ) : '';
    }

    /**
     * Sanitize a checkbox to '1' (on) or '' (off).
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_checkbox( $value ) {
        return $value ? '1' : '';
    }

    /**
     * Sanitize the enquiry-form field definitions (array of field defs).
     *
     * @param mixed $value Raw value.
     * @return array
     */
    public function sanitize_enquiry_fields( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $allowed = array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'country' );
        $clean   = array();
        foreach ( $value as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }
            $label = sanitize_text_field( $field['label'] ?? '' );
            if ( '' === trim( $label ) ) {
                continue;
            }
            $type    = in_array( $field['type'] ?? 'text', $allowed, true ) ? $field['type'] : 'text';
            $clean[] = array(
                'label'    => $label,
                'type'     => $type,
                'required' => ! empty( $field['required'] ) ? 1 : 0,
                'options'  => sanitize_text_field( $field['options'] ?? '' ),
            );
        }
        return $clean;
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
            'wptm_enable_related',
            'wptm_enable_ai', 'wptm_tax_enabled', 'wptm_enquiry_enabled',
            'wptm_stripe_enabled', 'wptm_paypal_enabled', 'wptm_razorpay_enabled', 'wptm_manual_payment',
            'wptm_email_customer_enabled', 'wptm_email_admin_enabled',
        );

        // Set unchecked checkboxes to empty string.
        foreach ( $checkbox_keys as $cb_key ) {
            if ( ! isset( $fields[ $cb_key ] ) ) {
                $fields[ $cb_key ] = '';
            }
        }

        // Colour fields — validate as hex; empty/invalid clears to the default.
        $color_keys = array( 'wptm_color_primary', 'wptm_color_discount_ribbon', 'wptm_color_featured_ribbon', 'wptm_color_icon' );
        foreach ( $color_keys as $color_key ) {
            if ( isset( $fields[ $color_key ] ) && ! is_array( $fields[ $color_key ] ) ) {
                $hex = sanitize_hex_color( wp_unslash( $fields[ $color_key ] ) );
                update_option( $color_key, $hex ? $hex : '' );
                unset( $fields[ $color_key ] );
            }
        }

        // Multi-line fields keep their newlines (sanitize_text_field would strip them).
        $textarea_keys = array( 'wptm_bank_instructions', 'wptm_email_footer_text', 'wptm_invoice_address', 'wptm_invoice_notes' );
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
        wp_send_json_success( array( 'message' => __( 'Settings saved.', 'byteflows-travel-hotel-booking' ) ) );
    }
}
