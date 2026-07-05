<?php
/**
 * Session-less shopping cart.
 *
 * The cart and any applied coupon live in a transient keyed by a random token
 * stored in an HttpOnly cookie. This avoids PHP native sessions (which break
 * under object caching / load balancers and are flagged by the WordPress Plugin
 * Check / marketplace reviews) while keeping the cart available to guests.
 *
 * Line prices are always recomputed server-side via {@see Pricing}, never taken
 * from the request, so the cart total cannot be tampered with.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Booking;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class Cart {
    /** Cookie holding the per-visitor cart token. */
    const COOKIE = 'wptm_cart_token';

    /** How long an idle cart survives. */
    const TTL = 7 * DAY_IN_SECONDS;

    public function __construct() {
        add_action( 'wp_ajax_wptm_add_to_cart', array( $this, 'add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_wptm_add_to_cart', array( $this, 'add_to_cart' ) );
        add_action( 'wp_ajax_wptm_remove_from_cart', array( $this, 'remove_from_cart' ) );
        add_action( 'wp_ajax_nopriv_wptm_remove_from_cart', array( $this, 'remove_from_cart' ) );
        add_action( 'wp_ajax_wptm_get_cart', array( $this, 'get_cart_ajax' ) );
        add_action( 'wp_ajax_nopriv_wptm_get_cart', array( $this, 'get_cart_ajax' ) );
        add_action( 'wp_ajax_wptm_apply_coupon', array( $this, 'apply_coupon' ) );
        add_action( 'wp_ajax_nopriv_wptm_apply_coupon', array( $this, 'apply_coupon' ) );
    }

    /* ---------------------------------------------------------------------
     * Token / storage
     * ------------------------------------------------------------------- */

    /**
     * Current cart token, optionally minting (and cookie-ing) a new one.
     *
     * @param bool $create Create a token if none exists yet.
     * @return string
     */
    private function token( $create = false ) {
        $token = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';

        if ( '' === $token && $create ) {
            $token = wp_generate_password( 24, false );
            if ( ! headers_sent() ) {
                setcookie( self::COOKIE, $token, array(
                    'expires'  => time() + self::TTL,
                    'path'     => COOKIEPATH ? COOKIEPATH : '/',
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ) );
            }
            // Make it available within the current request too.
            $_COOKIE[ self::COOKIE ] = $token;
        }

        return $token;
    }

    /**
     * Read the full cart record (items + coupon).
     *
     * @return array{items:array,coupon:?array}
     */
    private function read() {
        $token = $this->token();
        $empty = array( 'items' => array(), 'coupon' => null );
        if ( '' === $token ) {
            return $empty;
        }
        $data = get_transient( 'wptm_cart_' . $token );
        return is_array( $data ) ? wp_parse_args( $data, $empty ) : $empty;
    }

    /**
     * Persist the full cart record.
     *
     * @param array $data items + coupon.
     */
    private function write( $data ) {
        $token = $this->token( true );
        if ( '' === $token ) {
            return;
        }
        set_transient( 'wptm_cart_' . $token, $data, self::TTL );
    }

    /* ---------------------------------------------------------------------
     * AJAX
     * ------------------------------------------------------------------- */

    public function add_to_cart() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );

        $item_id   = absint( $_POST['item_id'] ?? 0 );
        $item_type = sanitize_text_field( wp_unslash( $_POST['item_type'] ?? 'trip' ) );
        if ( empty( $item_id ) || ! get_post( $item_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid item.', 'byteflows-travel-hotel-booking' ) ) );
        }

        $room_id   = absint( $_POST['room_id'] ?? 0 );
        $check_in  = sanitize_text_field( wp_unslash( $_POST['check_in'] ?? '' ) );
        $check_out = sanitize_text_field( wp_unslash( $_POST['check_out'] ?? '' ) );
        $date      = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
        $tier_lbl  = sanitize_text_field( wp_unslash( $_POST['pricing_tier'] ?? '' ) );

        // Authoritative unit price — the request never sets the price.
        if ( 'hotel' === $item_type ) {
            $unit_price = Pricing::hotel_nightly( $item_id, $room_id, $check_in ?: $date )
                * Pricing::nights( $check_in, $check_out );
        } else {
            $unit_price = $this->trip_unit_price( $item_id, $tier_lbl );
        }

        $item = array(
            'item_id'      => $item_id,
            'item_type'    => $item_type,
            'quantity'     => max( 1, absint( $_POST['quantity'] ?? 1 ) ),
            'date'         => $date,
            'check_in'     => $check_in,
            'check_out'    => $check_out,
            'guests'       => max( 1, absint( $_POST['guests'] ?? 1 ) ),
            'room_id'      => $room_id,
            'pricing_tier' => $tier_lbl,
            'price'        => round( (float) $unit_price, 2 ),
        );

        $cart = $this->get_cart();
        $key  = md5( wp_json_encode( array( $item['item_id'], $item['item_type'], $item['room_id'], $item['date'] ) ) );
        $cart[ $key ] = $item;
        $this->save_cart( $cart );

        wp_send_json_success( array(
            'message' => __( 'Added to cart!', 'byteflows-travel-hotel-booking' ),
            'cart'    => $this->get_cart_summary(),
        ) );
    }

    public function remove_from_cart() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $key  = sanitize_text_field( wp_unslash( $_POST['cart_key'] ?? '' ) );
        $cart = $this->get_cart();
        if ( isset( $cart[ $key ] ) ) {
            unset( $cart[ $key ] );
        }
        $this->save_cart( $cart );
        wp_send_json_success( array( 'cart' => $this->get_cart_summary() ) );
    }

    public function get_cart_ajax() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        wp_send_json_success( array( 'cart' => $this->get_cart_summary() ) );
    }

    public function apply_coupon() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );
        $code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) );

        global $wpdb;
        $coupon = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wptm_coupons WHERE code = %s AND status = 'active'", $code
        ) );

        if ( ! $coupon ) {
            wp_send_json_error( array( 'message' => __( 'Invalid coupon code.', 'byteflows-travel-hotel-booking' ) ) );
        }
        if ( $coupon->end_date && strtotime( $coupon->end_date ) < time() ) {
            wp_send_json_error( array( 'message' => __( 'Coupon expired.', 'byteflows-travel-hotel-booking' ) ) );
        }
        if ( $coupon->max_uses && $coupon->used_count >= $coupon->max_uses ) {
            wp_send_json_error( array( 'message' => __( 'Coupon usage limit reached.', 'byteflows-travel-hotel-booking' ) ) );
        }
        if ( $coupon->start_date && strtotime( $coupon->start_date ) > time() ) {
            wp_send_json_error( array( 'message' => __( 'This coupon is not active yet.', 'byteflows-travel-hotel-booking' ) ) );
        }

        // Base amount to discount against. Single trip/hotel pages post their
        // current subtotal for the preview; fall back to the session-less cart
        // total. The figure is preview-only — the authoritative discount is
        // recomputed server-side at booking/checkout time.
        $base = isset( $_POST['amount'] ) ? floatval( wp_unslash( $_POST['amount'] ) ) : 0;
        if ( $base <= 0 ) {
            $base = $this->get_cart_total();
        }
        if ( $base <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Add an amount before applying a coupon.', 'byteflows-travel-hotel-booking' ) ) );
        }
        if ( $coupon->min_amount && $base < (float) $coupon->min_amount ) {
            wp_send_json_error( array(
                'message' => sprintf(
                    /* translators: %s: minimum spend amount */
                    __( 'A minimum of %s is required for this coupon.', 'byteflows-travel-hotel-booking' ),
                    wptm_format_price( $coupon->min_amount )
                ),
            ) );
        }

        if ( 'percentage' === $coupon->type ) {
            $discount = $base * ( $coupon->amount / 100 );
        } else {
            $discount = min( $coupon->amount, $base );
        }
        $discount = round( $discount, 2 );

        $record           = $this->read();
        $record['coupon'] = array(
            'code'     => $code,
            'discount' => $discount,
            'type'     => $coupon->type,
            'amount'   => (float) $coupon->amount,
        );
        $this->write( $record );

        wp_send_json_success( array(
            /* translators: %s: formatted discount amount. */
            'message'   => sprintf( __( 'Coupon applied! Discount: %s', 'byteflows-travel-hotel-booking' ), wptm_format_price( $discount ) ),
            'discount'  => $discount,
            'type'      => $coupon->type,
            'amount'    => (float) $coupon->amount,
            'new_total' => max( 0, $base - $discount ),
        ) );
    }

    /* ---------------------------------------------------------------------
     * Cart data
     * ------------------------------------------------------------------- */

    public function get_cart() {
        $data = $this->read();
        return is_array( $data['items'] ) ? $data['items'] : array();
    }

    private function save_cart( $cart ) {
        $data          = $this->read();
        $data['items'] = $cart;
        $this->write( $data );
    }

    /**
     * Effective unit price for a trip, honouring a chosen tier label.
     *
     * @param int    $item_id    Trip ID.
     * @param string $tier_label Selected tier label (empty = lowest).
     * @return float
     */
    private function trip_unit_price( $item_id, $tier_label ) {
        $tiers = Pricing::trip_tiers( $item_id );
        if ( empty( $tiers ) ) {
            return 0.0;
        }
        if ( '' !== $tier_label ) {
            foreach ( $tiers as $t ) {
                if ( strtolower( $t['label'] ) === strtolower( $tier_label ) ) {
                    return (float) $t['price'];
                }
            }
        }
        return (float) min( wp_list_pluck( $tiers, 'price' ) );
    }

    public function get_cart_total() {
        $total = 0;
        foreach ( $this->get_cart() as $item ) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }
        return $total;
    }

    public function get_cart_summary() {
        $cart  = $this->get_cart();
        $items = array();
        foreach ( $cart as $key => $item ) {
            $post    = get_post( $item['item_id'] );
            $items[] = array(
                'key'       => $key,
                'title'     => $post ? $post->post_title : __( 'Unknown', 'byteflows-travel-hotel-booking' ),
                'thumbnail' => get_the_post_thumbnail_url( $item['item_id'], 'thumbnail' ),
                'price'     => $item['price'],
                'quantity'  => $item['quantity'],
                'subtotal'  => (float) $item['price'] * (int) $item['quantity'],
                'type'      => $item['item_type'],
                'date'      => $item['date'] ?: $item['check_in'],
            );
        }
        $record = $this->read();
        $coupon = $record['coupon'] ?? null;
        return array(
            'items'       => $items,
            'count'       => count( $items ),
            'total'       => $this->get_cart_total(),
            'coupon'      => $coupon,
            'final_total' => max( 0, $this->get_cart_total() - ( $coupon['discount'] ?? 0 ) ),
        );
    }

    public function clear_cart() {
        $token = $this->token();
        if ( '' !== $token ) {
            delete_transient( 'wptm_cart_' . $token );
        }
    }
}
