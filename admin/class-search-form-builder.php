<?php
namespace JourneyLoom\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class SearchFormBuilder {
    public function __construct() {
        add_action( 'wp_ajax_wptm_save_search_form', array( $this, 'save' ) );
    }

    /**
     * Canonical registry of available search fields — the single source of
     * truth shared by the admin builder and the frontend form.
     *
     * Each field: label, type, icon, placeholder, taxonomy (optional), and the
     * default enabled/order used before the site owner customises anything.
     *
     * @return array
     */
    public static function registry() {
        $registry = array(
            'keyword' => array(
                'label' => __( 'Search', 'journeyloom' ), 'type' => 'text', 'icon' => '🔍',
                'placeholder' => __( 'Where do you want to go?', 'journeyloom' ),
                'enabled' => true, 'order' => 1,
            ),
            'destination' => array(
                'label' => __( 'Destination', 'journeyloom' ), 'type' => 'select', 'icon' => '📍',
                'placeholder' => __( 'All Destinations', 'journeyloom' ), 'taxonomy' => 'wptm_destination',
                'enabled' => true, 'order' => 2,
            ),
            'activity' => array(
                'label' => __( 'Activity', 'journeyloom' ), 'type' => 'select', 'icon' => '🎯',
                'placeholder' => __( 'All Activities', 'journeyloom' ), 'taxonomy' => 'wptm_activity',
                'enabled' => true, 'order' => 3,
            ),
            'trip_type' => array(
                'label' => __( 'Trip Type', 'journeyloom' ), 'type' => 'select', 'icon' => '🧭',
                'placeholder' => __( 'All Trip Types', 'journeyloom' ), 'taxonomy' => 'wptm_trip_type',
                'enabled' => false, 'order' => 4,
            ),
            'difficulty' => array(
                'label' => __( 'Difficulty', 'journeyloom' ), 'type' => 'select', 'icon' => '⛰️',
                'placeholder' => __( 'Any Difficulty', 'journeyloom' ), 'taxonomy' => 'wptm_difficulty',
                'enabled' => false, 'order' => 5,
            ),
            'duration' => array(
                'label' => __( 'Duration', 'journeyloom' ), 'type' => 'select', 'icon' => '⏱️',
                'placeholder' => __( 'Any Duration', 'journeyloom' ),
                'enabled' => true, 'order' => 6,
            ),
            'budget' => array(
                'label' => __( 'Budget', 'journeyloom' ), 'type' => 'range', 'icon' => '💰',
                'placeholder' => '', 'enabled' => true, 'order' => 7,
            ),
            'guests' => array(
                'label' => __( 'Guests', 'journeyloom' ), 'type' => 'number', 'icon' => '👥',
                'placeholder' => __( 'How many?', 'journeyloom' ),
                'enabled' => false, 'order' => 8,
            ),
            'date' => array(
                'label' => __( 'Date', 'journeyloom' ), 'type' => 'date', 'icon' => '📅',
                'placeholder' => __( 'When?', 'journeyloom' ),
                'enabled' => true, 'order' => 9,
            ),
        );

        /**
         * Filter the registry of available search-form fields.
         *
         * @param array $registry Map of field key => definition.
         */
        return apply_filters( 'wptm_search_fields_registry', $registry );
    }

    /**
     * The selectable field "type" options shown in the builder.
     *
     * @return string[]
     */
    public static function field_types() {
        return array( 'text', 'select', 'date', 'number', 'range', 'checkbox' );
    }

    public function save() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        // Values are sanitized per field below; unslash the payload up front.
        $fields = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( ! is_array( $fields ) ) wp_send_json_error();

        $registry  = self::registry();
        $allowed   = self::field_types();
        $sanitized = array();

        foreach ( $fields as $key => $field ) {
            $key = sanitize_key( $key );
            // Only persist keys we recognise from the registry.
            if ( ! isset( $registry[ $key ] ) ) {
                continue;
            }
            $type = sanitize_text_field( $field['type'] ?? 'text' );
            $sanitized[ $key ] = array(
                'enabled'     => ! empty( $field['enabled'] ),
                'label'       => sanitize_text_field( $field['label'] ?? '' ),
                'type'        => in_array( $type, $allowed, true ) ? $type : 'text',
                'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
                'required'    => ! empty( $field['required'] ),
                'order'       => absint( $field['order'] ?? 0 ),
            );
        }

        update_option( 'wptm_search_form_fields', wp_json_encode( $sanitized ) );
        wp_send_json_success( array( 'message' => __( 'Search form saved.', 'journeyloom' ) ) );
    }

    /**
     * Resolved field config: registry defaults overlaid with saved overrides,
     * sorted by order. Always includes registry-only metadata (icon, taxonomy)
     * so callers don't need the registry separately.
     *
     * @return array
     */
    public static function get_fields() {
        $registry = self::registry();
        $saved    = json_decode( (string) get_option( 'wptm_search_form_fields', '' ), true );
        $saved    = is_array( $saved ) ? $saved : array();

        $fields = array();
        foreach ( $registry as $key => $def ) {
            $override = isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ? $saved[ $key ] : array();
            $fields[ $key ] = array(
                'label'       => $override['label']       ?? $def['label'],
                'type'        => $override['type']        ?? $def['type'],
                'placeholder' => $override['placeholder'] ?? $def['placeholder'],
                'enabled'     => array_key_exists( 'enabled', $override ) ? ! empty( $override['enabled'] ) : ! empty( $def['enabled'] ),
                'required'    => ! empty( $override['required'] ),
                'order'       => isset( $override['order'] ) ? (int) $override['order'] : (int) $def['order'],
                'icon'        => $def['icon'] ?? '🔘',
                'taxonomy'    => $def['taxonomy'] ?? '',
            );
        }

        uasort( $fields, function ( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );

        return $fields;
    }

    /**
     * Only the enabled fields, in order — convenience for the frontend.
     *
     * @return array
     */
    public static function get_enabled_fields() {
        return array_filter( self::get_fields(), function ( $f ) {
            return ! empty( $f['enabled'] );
        } );
    }
}
