<?php
namespace JourneyLoom\REST;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class RestController {
    const NS = 'wptm/v1';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NS, '/trips', array( 'methods' => 'GET', 'callback' => array( $this, 'get_trips' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/trips/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $this, 'get_trip' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/hotels', array( 'methods' => 'GET', 'callback' => array( $this, 'get_hotels' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/bookings', array( 'methods' => 'GET', 'callback' => array( $this, 'get_bookings' ), 'permission_callback' => function() { return current_user_can( 'manage_options' ); } ) );
        register_rest_route( self::NS, '/bookings', array( 'methods' => 'POST', 'callback' => array( $this, 'create_booking' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/search', array( 'methods' => 'GET', 'callback' => array( $this, 'search' ), 'permission_callback' => '__return_true' ) );
        register_rest_route( self::NS, '/availability/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $this, 'get_availability' ), 'permission_callback' => '__return_true' ) );
    }

    public function get_trips( $req ) {
        $q = new \WP_Query( array( 'post_type' => 'wptm_trip', 'posts_per_page' => $req->get_param('per_page') ?: 12, 'paged' => $req->get_param('page') ?: 1, 'post_status' => 'publish' ) );
        return rest_ensure_response( array( 'trips' => array_map( array( $this, 'fmt_trip' ), $q->posts ), 'total' => $q->found_posts ) );
    }

    public function get_trip( $req ) {
        $p = get_post( $req['id'] );
        if ( ! $p || 'wptm_trip' !== $p->post_type ) return new \WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
        return rest_ensure_response( $this->fmt_trip( $p ) );
    }

    public function get_hotels( $req ) {
        $q = new \WP_Query( array( 'post_type' => 'wptm_hotel', 'posts_per_page' => 12, 'post_status' => 'publish' ) );
        return rest_ensure_response( array( 'hotels' => array_map( array( $this, 'fmt_hotel' ), $q->posts ), 'total' => $q->found_posts ) );
    }

    public function get_bookings( $req ) {
        return rest_ensure_response( \JourneyLoom\Booking\BookingEngine::get_bookings( array( 'limit' => 20, 'status' => $req->get_param('status') ?: '' ) ) );
    }

    public function create_booking( $req ) {
        // Lightweight per-IP throttle to curb unauthenticated booking spam.
        $ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        $key = 'wptm_rest_book_' . md5( $ip );
        $hits = (int) get_transient( $key );
        if ( $hits >= 10 ) {
            return new \WP_Error( 'too_many_requests', __( 'Too many requests. Please try again later.', 'journeyloom' ), array( 'status' => 429 ) );
        }
        set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );

        $d = $req->get_json_params();
        $item_id = absint( $d['item_id'] ?? 0 );
        $email   = sanitize_email( $d['customer_email'] ?? '' );
        if ( ! $item_id || ! get_post( $item_id ) || ! is_email( $email ) ) {
            return new \WP_Error( 'invalid', __( 'Missing or invalid fields.', 'journeyloom' ), array( 'status' => 400 ) );
        }

        $item_type = sanitize_text_field( $d['booking_type'] ?? 'trip' );

        // Authoritative price — the client's total_price is ignored.
        $subtotal = \JourneyLoom\Booking\Pricing::subtotal( $item_id, $item_type, array(
            'tiers'           => is_array( $d['tiers'] ?? null ) ? $d['tiers'] : array(),
            'travelers_count' => absint( $d['travelers_count'] ?? 1 ),
            'room_id'         => absint( $d['room_id'] ?? 0 ),
            'check_in'        => sanitize_text_field( $d['check_in'] ?? '' ),
            'check_out'       => sanitize_text_field( $d['check_out'] ?? '' ),
        ) );
        $coupon = \JourneyLoom\Booking\Pricing::coupon_discount( $d['coupon_code'] ?? '', $subtotal );

        global $wpdb;
        $b = array(
            'booking_number' => 'WPTM-' . strtoupper( wp_generate_password( 8, false ) ),
            'item_id'        => $item_id,
            'booking_type'   => $item_type,
            'status'         => 'pending',
            'total_price'    => max( 0, round( $subtotal - $coupon['discount'], 2 ) ),
            'discount_amount'=> $coupon['discount'],
            'coupon_code'    => $coupon['code'],
            'currency'       => get_option( 'wptm_currency', 'USD' ),
            'travelers_count'=> absint( $d['travelers_count'] ?? 1 ),
            'customer_name'  => sanitize_text_field( $d['customer_name'] ?? '' ),
            'customer_email' => $email,
            'payment_status' => 'unpaid',
        );
        $wpdb->insert( $wpdb->prefix . 'wptm_bookings', $b );
        $b['id'] = $wpdb->insert_id;
        do_action( 'wptm_booking_created', $b['id'], $b );
        return rest_ensure_response( $b );
    }

    public function search( $req ) {
        $q = new \WP_Query( array( 'post_type' => array( 'wptm_trip', 'wptm_hotel' ), 'posts_per_page' => 12, 's' => sanitize_text_field( $req->get_param('q') ?: '' ), 'post_status' => 'publish' ) );
        $r = array();
        foreach ( $q->posts as $p ) $r[] = array( 'id' => $p->ID, 'title' => $p->post_title, 'type' => $p->post_type, 'url' => get_permalink( $p->ID ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID, 'medium' ) );
        return rest_ensure_response( array( 'results' => $r, 'total' => $q->found_posts ) );
    }

    public function get_availability( $req ) {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wptm_availability WHERE item_id = %d AND status = 'available' ORDER BY date_start", $req['id'] ) ) );
    }

    private function fmt_trip( $p ) {
        $pr = get_post_meta( $p->ID, '_wptm_pricing', true );
        return array( 'id' => $p->ID, 'title' => $p->post_title, 'excerpt' => wp_trim_words( $p->post_excerpt ?: $p->post_content, 20 ), 'url' => get_permalink( $p->ID ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID, 'large' ), 'price' => is_array( $pr ) && ! empty( $pr ) ? $pr[0]['price'] : 0, 'duration' => get_post_meta( $p->ID, '_wptm_duration', true ), 'difficulty' => get_post_meta( $p->ID, '_wptm_difficulty', true ), 'destinations' => wp_get_post_terms( $p->ID, 'wptm_destination', array( 'fields' => 'names' ) ) );
    }

    private function fmt_hotel( $p ) {
        return array( 'id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink( $p->ID ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID, 'large' ), 'stars' => get_post_meta( $p->ID, '_wptm_star_rating', true ), 'city' => get_post_meta( $p->ID, '_wptm_hotel_city', true ) );
    }
}
