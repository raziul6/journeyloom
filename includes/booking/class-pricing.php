<?php
/**
 * Authoritative, server-side pricing.
 *
 * The browser must never be trusted for money. Every price shown in the booking
 * form is recomputed here from the saved trip/hotel/room/availability data so a
 * tampered request (e.g. total_price=0.01) cannot lower what the customer is
 * actually charged. The same logic backs the coupon discount.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Booking;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class Pricing {

    /**
     * Sale-aware effective price for a regular/sale pair.
     *
     * @param mixed $regular Regular price.
     * @param mixed $sale    Sale price (0/null = none).
     * @return float
     */
    public static function effective( $regular, $sale ) {
        $regular = (float) $regular;
        $sale    = (float) $sale;
        if ( $sale > 0 && ( $sale < $regular || $regular <= 0 ) ) {
            return $sale;
        }
        return max( 0, $regular );
    }

    /**
     * Authoritative trip pricing tiers (label => effective price), in saved order.
     *
     * Mirrors the filtering used by the booking form so the recomputed total
     * matches what the customer saw.
     *
     * @param int $item_id Trip ID.
     * @return array<int,array{label:string,price:float}>
     */
    public static function trip_tiers( $item_id ) {
        $pricing = get_post_meta( $item_id, '_wptm_pricing', true );
        $tiers   = array();
        if ( is_array( $pricing ) ) {
            foreach ( $pricing as $tier ) {
                $eff   = self::effective( $tier['price'] ?? 0, $tier['sale_price'] ?? 0 );
                $label = trim( (string) ( $tier['label'] ?? '' ) );
                if ( $eff <= 0 && '' === $label ) {
                    continue;
                }
                $tiers[] = array(
                    'label' => $label !== '' ? $label : __( 'Standard', 'byteflows-travel-hotel-booking' ),
                    'price' => $eff,
                );
            }
        }
        return $tiers;
    }

    /**
     * Authoritative pickup points for a trip: [ ['label'=>, 'price'=>], ... ].
     *
     * Pickup points are provided by the Byteflows Travel & Hotel Booking Pro
     * add-on, which returns the saved list via the 'wptm_pickup_points' filter.
     * The free plugin returns an empty list (no pickups).
     *
     * @param int $item_id Trip ID.
     * @return array<int,array{label:string,price:float}>
     */
    public static function pickup_points( $item_id ) {
        /**
         * Filter the pickup points for a trip.
         *
         * @param array $points  List of [ 'label' => string, 'price' => float ].
         * @param int   $item_id Trip ID.
         */
        $points = apply_filters( 'wptm_pickup_points', array(), $item_id );
        return is_array( $points ) ? $points : array();
    }

    /**
     * Total for the pickup points selected at checkout (one per traveler).
     *
     * Selections are indexes into the saved list; the price always comes from the
     * saved pickup, never the request. Unknown/empty indexes mean "no pickup".
     *
     * @param int   $item_id  Trip ID.
     * @param array $selected Selected pickup indexes (one per traveler).
     * @return array{total:float,items:array<int,array{label:string,price:float}>}
     */
    public static function pickup_total( $item_id, $selected ) {
        $points = self::pickup_points( $item_id );
        if ( empty( $points ) || ! is_array( $selected ) ) {
            return array( 'total' => 0.0, 'items' => array() );
        }
        $total = 0.0;
        $items = array();
        foreach ( $selected as $idx ) {
            if ( '' === $idx || ! isset( $points[ (int) $idx ] ) ) {
                continue;
            }
            $items[] = $points[ (int) $idx ];
            $total  += $points[ (int) $idx ]['price'];
        }
        return array( 'total' => round( $total, 2 ), 'items' => $items );
    }

    /**
     * Server-side trip subtotal.
     *
     * Posted tier quantities are honoured, but the unit price always comes from
     * the saved tier (matched by label), never from the request.
     *
     * @param int   $item_id       Trip ID.
     * @param array $posted_tiers  Raw tiers[] from the request (label/qty).
     * @param int   $travelers     Fallback head-count for single-tier trips.
     * @return float
     */
    public static function trip_subtotal( $item_id, $posted_tiers, $travelers ) {
        $auth = self::trip_tiers( $item_id );
        if ( empty( $auth ) ) {
            return 0.0;
        }

        $by_label = array();
        foreach ( $auth as $t ) {
            $by_label[ strtolower( $t['label'] ) ] = $t['price'];
        }

        $subtotal = 0.0;
        $matched  = false;
        if ( is_array( $posted_tiers ) ) {
            foreach ( $posted_tiers as $pt ) {
                $label = strtolower( trim( (string) ( $pt['label'] ?? '' ) ) );
                $qty   = absint( $pt['qty'] ?? 0 );
                if ( $qty < 1 || ! isset( $by_label[ $label ] ) ) {
                    continue;
                }
                $subtotal += $by_label[ $label ] * $qty;
                $matched   = true;
            }
        }

        if ( $matched ) {
            return round( $subtotal, 2 );
        }

        // Single-tier / no explicit selection: lowest tier × head-count.
        $unit = min( wp_list_pluck( $auth, 'price' ) );
        return round( $unit * max( 1, (int) $travelers ), 2 );
    }

    /**
     * Effective nightly rate for a hotel room, honouring a date-range override.
     *
     * @param int    $item_id Hotel ID.
     * @param int    $room_id Selected room (0 = cheapest available).
     * @param string $date    Stay start date (Y-m-d) for override lookup.
     * @return float
     */
    public static function hotel_nightly( $item_id, $room_id, $date = '' ) {
        global $wpdb;
        $nightly = 0.0;

        if ( $room_id ) {
            $room = $wpdb->get_row( $wpdb->prepare(
                "SELECT price_per_night, sale_price FROM {$wpdb->prefix}wptm_rooms WHERE id = %d AND hotel_id = %d AND status = 'available'",
                $room_id, $item_id
            ) );
            if ( $room ) {
                $nightly = self::effective( $room->price_per_night, $room->sale_price );
            }
        }

        if ( $nightly <= 0 ) {
            $room = $wpdb->get_row( $wpdb->prepare(
                "SELECT price_per_night, sale_price FROM {$wpdb->prefix}wptm_rooms WHERE hotel_id = %d AND status = 'available' ORDER BY price_per_night ASC LIMIT 1",
                $item_id
            ) );
            if ( $room ) {
                $nightly = self::effective( $room->price_per_night, $room->sale_price );
            }
        }

        if ( $date ) {
            $override = $wpdb->get_var( $wpdb->prepare(
                "SELECT price_override FROM {$wpdb->prefix}wptm_availability
                 WHERE item_id = %d AND item_type = 'hotel' AND date_start <= %s AND date_end >= %s
                 AND status = 'available' AND price_override IS NOT NULL
                 ORDER BY id DESC LIMIT 1",
                $item_id, $date, $date
            ) );
            if ( null !== $override && '' !== $override ) {
                $nightly = (float) $override;
            }
        }

        return max( 0, $nightly );
    }

    /**
     * Number of nights between two dates (minimum 1).
     *
     * @param string $check_in  Y-m-d.
     * @param string $check_out Y-m-d.
     * @return int
     */
    public static function nights( $check_in, $check_out ) {
        $ci = strtotime( (string) $check_in );
        $co = strtotime( (string) $check_out );
        if ( ! $ci || ! $co || $co <= $ci ) {
            return 1;
        }
        return max( 1, (int) round( ( $co - $ci ) / DAY_IN_SECONDS ) );
    }

    /**
     * Authoritative pre-discount subtotal for a single item booking.
     *
     * @param int    $item_id   Trip/hotel ID.
     * @param string $item_type 'trip' | 'hotel'.
     * @param array  $args      tiers, travelers_count, room_id, check_in, check_out.
     * @return float
     */
    public static function subtotal( $item_id, $item_type, $args ) {
        $item_id = absint( $item_id );
        if ( ! $item_id ) {
            return 0.0;
        }

        if ( 'hotel' === $item_type ) {
            $nightly = self::hotel_nightly( $item_id, absint( $args['room_id'] ?? 0 ), (string) ( $args['check_in'] ?? '' ) );
            return round( $nightly * self::nights( $args['check_in'] ?? '', $args['check_out'] ?? '' ), 2 );
        }

        return self::trip_subtotal( $item_id, $args['tiers'] ?? array(), $args['travelers_count'] ?? 1 );
    }

    /**
     * Server-validated coupon discount against a known-good subtotal.
     *
     * @param string $code     Coupon code.
     * @param float  $subtotal Authoritative subtotal to discount.
     * @return array{code:string,discount:float} Empty code when not applicable.
     */
    public static function coupon_discount( $code, $subtotal ) {
        $code     = sanitize_text_field( $code );
        $subtotal = (float) $subtotal;
        $none     = array( 'code' => '', 'discount' => 0.0 );

        if ( '' === $code || $subtotal <= 0 ) {
            return $none;
        }

        /**
         * Filter the validated coupon discount for a subtotal.
         *
         * Coupons are provided by the Byteflows Travel & Hotel Booking Pro add-on,
         * which validates the code against its coupons table and returns
         * [ 'code' => string, 'discount' => float, ... ]. The free plugin applies
         * no discount.
         *
         * @param array  $result   Default (no discount).
         * @param string $code     Sanitized coupon code.
         * @param float  $subtotal Authoritative subtotal.
         */
        $result = apply_filters( 'wptm_coupon_discount', $none, $code, $subtotal );
        return is_array( $result ) ? $result : $none;
    }
}
