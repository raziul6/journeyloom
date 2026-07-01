<?php
namespace JourneyLoom\Pub;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class AjaxHandler {
    public function __construct() {
        add_action( 'wp_ajax_wptm_toggle_wishlist', array( $this, 'toggle_wishlist' ) );
        add_action( 'wp_ajax_wptm_get_wishlist', array( $this, 'get_wishlist' ) );
        add_action( 'wp_ajax_wptm_enquiry', array( $this, 'enquiry' ) );
        add_action( 'wp_ajax_nopriv_wptm_enquiry', array( $this, 'enquiry' ) );
        add_action( 'wp_ajax_wptm_get_recently_viewed', array( $this, 'get_recently_viewed' ) );
        add_action( 'wp_ajax_nopriv_wptm_get_recently_viewed', array( $this, 'get_recently_viewed' ) );
    }

    public function toggle_wishlist() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => __( 'Please log in.', 'journeyloom' ) ) );

        global $wpdb;
        $user_id = get_current_user_id();
        $item_id = absint( $_POST['item_id'] ?? 0 );
        $item_type = sanitize_text_field( wp_unslash( $_POST['item_type'] ?? 'trip' ) );
        $table = $wpdb->prefix . 'wptm_wishlist';

        if ( ! $item_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid item.', 'journeyloom' ) ) );
        }

        $where  = array( 'user_id' => $user_id, 'item_id' => $item_id, 'item_type' => $item_type );
        $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id=%d AND item_id=%d AND item_type=%s", $user_id, $item_id, $item_type ) );

        if ( $exists ) {
            // Delete every matching row so any legacy duplicates are cleared too.
            $wpdb->delete( $table, $where );
            wp_send_json_success( array( 'action' => 'removed', 'message' => __( 'Removed from wishlist.', 'journeyloom' ) ) );
        } else {
            // The table's unique key (user_id, item_id, item_type) keeps this
            // idempotent even if two requests race.
            $wpdb->insert( $table, $where );
            wp_send_json_success( array( 'action' => 'added', 'message' => __( 'Added to wishlist!', 'journeyloom' ) ) );
        }
    }

    public function get_wishlist() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        global $wpdb;
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT item_id, item_type FROM {$wpdb->prefix}wptm_wishlist WHERE user_id=%d GROUP BY item_id, item_type", get_current_user_id() ) );
        $ids = wp_list_pluck( $items, 'item_id' );
        wp_send_json_success( array( 'items' => $ids ) );
    }

    /**
     * Handle an enquiry-form submission. Fields are defined by the admin in
     * Settings → Enquiry; the stored definitions are the source of truth.
     */
    public function enquiry() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );
        $fields  = wptm_enquiry_fields();
        $vals    = ( isset( $_POST['enquiry'] ) && is_array( $_POST['enquiry'] ) ) ? wp_unslash( $_POST['enquiry'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field in the loop below.

        $lines    = array();
        $errors   = array();
        $reply_to = '';

        foreach ( $fields as $i => $f ) {
            $label = $f['label'];
            $type  = $f['type'];
            $req   = ! empty( $f['required'] );
            $raw   = $vals[ $i ] ?? '';
            $val   = ( 'textarea' === $type ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );

            if ( $req && '' === trim( $val ) ) {
                $errors[] = $label;
                continue;
            }
            if ( 'email' === $type && '' !== $val ) {
                if ( ! is_email( $val ) ) {
                    $errors[] = $label;
                    continue;
                }
                if ( '' === $reply_to ) $reply_to = $val;
            }
            if ( '' !== trim( $val ) ) {
                $lines[] = array( 'label' => $label, 'value' => $val );
            }
        }

        if ( ! empty( $errors ) ) {
            /* translators: %s: comma-separated list of field labels. */
            wp_send_json_error( array( 'message' => sprintf( __( 'Please check these fields: %s', 'journeyloom' ), implode( ', ', $errors ) ) ) );
        }
        if ( empty( $lines ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in the form before sending.', 'journeyloom' ) ) );
        }

        $to    = get_option( 'wptm_enquiry_email' );
        $to    = is_email( $to ) ? $to : get_option( 'admin_email' );
        $title = $post_id ? get_the_title( $post_id ) : '';
        $url   = $post_id ? get_permalink( $post_id ) : '';

        /* translators: %s: trip/hotel title the enquiry is about. */
        $subject = $title ? sprintf( __( 'New enquiry — %s', 'journeyloom' ), $title ) : __( 'New enquiry', 'journeyloom' );

        $body  = '<h2>' . esc_html__( 'New Enquiry', 'journeyloom' ) . '</h2>';
        if ( $title ) {
            $body .= '<p><strong>' . esc_html__( 'Regarding', 'journeyloom' ) . ':</strong> ' . esc_html( $title );
            if ( $url ) $body .= ' — <a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a>';
            $body .= '</p>';
        }
        $body .= '<table cellpadding="6" style="border-collapse:collapse;">';
        foreach ( $lines as $l ) {
            $body .= '<tr><td style="border:1px solid #eee;"><strong>' . esc_html( $l['label'] ) . '</strong></td><td style="border:1px solid #eee;">' . nl2br( esc_html( $l['value'] ) ) . '</td></tr>';
        }
        $body .= '</table>';

        $headers = array();
        if ( $reply_to ) $headers[] = 'Reply-To: ' . $reply_to;

        wp_mail( $to, $subject, $body, $headers );

        wp_send_json_success( array( 'message' => __( 'Thank you! Your enquiry has been sent.', 'journeyloom' ) ) );
    }

    public function get_recently_viewed() {
        $ids = isset( $_COOKIE['wptm_recently_viewed'] ) ? array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['wptm_recently_viewed'] ) ) ) ) : array();
        if ( empty( $ids ) ) wp_send_json_success( array( 'items' => array() ) );

        $posts = get_posts( array( 'post__in' => $ids, 'post_type' => array( 'wptm_trip', 'wptm_hotel' ), 'posts_per_page' => 6, 'orderby' => 'post__in' ) );
        $items = array();
        foreach ( $posts as $p ) {
            $items[] = array( 'id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink( $p->ID ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID, 'medium' ), 'type' => $p->post_type );
        }
        wp_send_json_success( array( 'items' => $items ) );
    }
}
