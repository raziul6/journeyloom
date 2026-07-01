<?php
namespace JourneyLoom\Booking;

if ( ! defined( 'ABSPATH' ) ) exit;

// This class is the bookings data layer: it reads/writes the plugin's own custom
// tables (wptm_bookings, wptm_availability, wptm_booking_meta), which have no core
// API and are unsuitable for the object cache (transactional, always-fresh reads).
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

class BookingEngine {
    public function __construct() {
        add_action( 'wp_ajax_wptm_create_booking', array( $this, 'create_booking' ) );
        add_action( 'wp_ajax_nopriv_wptm_create_booking', array( $this, 'create_booking' ) );
        add_action( 'wp_ajax_wptm_checkout', array( $this, 'process_checkout' ) );
        add_action( 'wp_ajax_nopriv_wptm_checkout', array( $this, 'process_checkout' ) );
        add_action( 'wp_ajax_wptm_check_availability', array( $this, 'check_availability' ) );
        add_action( 'wp_ajax_nopriv_wptm_check_availability', array( $this, 'check_availability' ) );
        add_action( 'wp_ajax_wptm_update_booking_status', array( $this, 'update_status' ) );
    }

    public function create_booking() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );

        $method    = sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'manual' ) );
        $item_id   = absint( $_POST['item_id'] ?? 0 );
        $item_type = sanitize_text_field( wp_unslash( $_POST['booking_type'] ?? 'trip' ) );
        $check_in  = sanitize_text_field( wp_unslash( $_POST['check_in'] ?? '' ) );
        $check_out = sanitize_text_field( wp_unslash( $_POST['check_out'] ?? '' ) );
        $travelers = absint( $_POST['travelers_count'] ?? 1 );
        $room_id   = absint( $_POST['room_id'] ?? 0 );
        // Tiers are read (label/qty only — never their price) for server pricing.
        $posted_tiers = ( isset( $_POST['tiers'] ) && is_array( $_POST['tiers'] ) ) ? wp_unslash( $_POST['tiers'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- structured array sanitized per field in Pricing.

        // Authoritative pricing: never trust the client's totals. Recompute the
        // subtotal and coupon discount from the saved trip/hotel/room data.
        $subtotal = Pricing::subtotal( $item_id, $item_type, array(
            'tiers'           => $posted_tiers,
            'travelers_count' => $travelers,
            'room_id'         => $room_id,
            'check_in'        => $check_in,
            'check_out'       => $check_out,
        ) );
        $coupon   = Pricing::coupon_discount( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ), $subtotal );

        // Pro: paid/free pickup points selected per traveler are added on top of
        // the (coupon-discounted) trip price. Prices come from the saved list.
        $posted_pickups = ( isset( $_POST['pickups'] ) && is_array( $_POST['pickups'] ) ) ? wp_unslash( $_POST['pickups'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- structured array sanitized per field in Pricing.
        $pickup         = Pricing::pickup_total( $item_id, $posted_pickups );

        $total = max( 0, round( $subtotal - $coupon['discount'] + $pickup['total'], 2 ) );

        $data = array(
            'booking_number'  => $this->generate_booking_number(),
            'user_id'         => get_current_user_id(),
            'booking_type'    => $item_type,
            'item_id'         => $item_id,
            'status'          => 'pending',
            'total_price'     => $total,
            'currency'        => get_option( 'wptm_currency', 'USD' ),
            'travelers_count' => $travelers,
            'check_in'        => $check_in,
            'check_out'       => $check_out,
            'payment_method'  => $method,
            // Bank transfer is "awaiting payment"; online methods stay unpaid
            // until their gateway confirms the charge.
            'payment_status'  => 'manual' === $method ? 'awaiting' : 'unpaid',
            'customer_name'   => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
            'customer_email'  => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
            'customer_phone'  => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
            'customer_address'=> sanitize_textarea_field( wp_unslash( $_POST['customer_address'] ?? '' ) ),
            'notes'           => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
            'coupon_code'     => $coupon['code'],
            'discount_amount' => $coupon['discount'],
            'ip_address'      => $this->get_client_ip(),
        );

        if ( empty( $data['item_id'] ) || empty( $data['customer_name'] ) || empty( $data['customer_email'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Required fields missing.', 'journeyloom' ) ) );
        }

        global $wpdb;
        $inserted = $wpdb->insert( $wpdb->prefix . 'wptm_bookings', $data );

        if ( $inserted ) {
            $booking_id = $wpdb->insert_id;

            // Save traveler details as meta.
            if ( isset( $_POST['travelers'] ) && is_array( $_POST['travelers'] ) ) {
                $travelers_in = wp_unslash( $_POST['travelers'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
                foreach ( $travelers_in as $i => $traveler ) {
                    $this->add_booking_meta( $booking_id, '_traveler_' . absint( $i ), array(
                        'name' => sanitize_text_field( $traveler['name'] ?? '' ),
                        'age'  => absint( $traveler['age'] ?? 0 ),
                        'type' => sanitize_text_field( $traveler['type'] ?? 'adult' ),
                    ) );
                }
            }

            // Save the selected pricing-tier breakdown (Adult ×2, Child ×1, …).
            // Unit prices come from the authoritative tier table, not the request.
            if ( ! empty( $posted_tiers ) ) {
                $auth_prices = array();
                foreach ( Pricing::trip_tiers( $item_id ) as $t ) {
                    $auth_prices[ strtolower( $t['label'] ) ] = $t['price'];
                }
                $breakdown = array();
                foreach ( $posted_tiers as $tier ) {
                    $qty   = absint( $tier['qty'] ?? 0 );
                    $label = sanitize_text_field( $tier['label'] ?? '' );
                    if ( $qty < 1 ) {
                        continue;
                    }
                    $breakdown[] = array(
                        'label' => $label,
                        'price' => (float) ( $auth_prices[ strtolower( $label ) ] ?? 0 ),
                        'qty'   => $qty,
                    );
                }
                if ( ! empty( $breakdown ) ) {
                    $this->add_booking_meta( $booking_id, '_pricing_tiers', $breakdown );
                }
            }

            // Save the selected pickup points (Pro).
            if ( ! empty( $pickup['items'] ) ) {
                $this->add_booking_meta( $booking_id, '_pickup_points', $pickup['items'] );
            }

            // Trigger booking created action.
            do_action( 'wptm_booking_created', $booking_id, $data );

            // Where the customer lands after this step. Online methods still need
            // their gateway to run before they are sent here; bank transfer goes
            // straight to the confirmation/order page.
            $confirm_url = wptm_get_page_url( 'confirmation' );
            if ( ! $confirm_url ) {
                $confirm_url = home_url( '/booking-confirmation/' );
            }
            $confirm_url = add_query_arg( 'booking', $booking_id, $confirm_url );

            wp_send_json_success( array(
                'message'        => __( 'Booking created successfully!', 'journeyloom' ),
                'booking_id'     => $booking_id,
                'booking_number' => $data['booking_number'],
                'payment_method' => $method,
                'redirect'       => $confirm_url,
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Booking failed. Please try again.', 'journeyloom' ) ) );
    }

    /**
     * Create bookings from the session cart (multi-item checkout).
     *
     * One booking row is created per cart line, sharing the customer details and
     * payment method. Any cart coupon discount is distributed proportionally so
     * the booking totals add up to the discounted order total. The cart is then
     * cleared and the customer is sent to their order page.
     */
    public function process_checkout() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );

        $cart_module = \JourneyLoom\Plugin::get_instance()->get_module( 'cart' );
        if ( ! $cart_module ) {
            wp_send_json_error( array( 'message' => __( 'Cart is not available.', 'journeyloom' ) ) );
        }

        $cart    = $cart_module->get_cart();
        $summary = $cart_module->get_cart_summary();
        if ( empty( $cart ) ) {
            wp_send_json_error( array( 'message' => __( 'Your cart is empty.', 'journeyloom' ) ) );
        }

        $name  = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
        if ( '' === $name || '' === $email ) {
            wp_send_json_error( array( 'message' => __( 'Please enter your name and email.', 'journeyloom' ) ) );
        }

        $method = sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'manual' ) );
        // Online card capture for multi-item checkout is not built yet — keep the
        // flow honest rather than creating unpaid "confirmed" orders.
        if ( 'manual' !== $method ) {
            wp_send_json_error( array( 'message' => __( 'Online payment for cart checkout is coming soon. Please choose Bank Transfer.', 'journeyloom' ) ) );
        }

        // Cart line prices are already server-derived; re-validate the coupon
        // discount against the real cart total rather than trusting stored values.
        $cart_total  = (float) $summary['total'];
        $coupon      = Pricing::coupon_discount( (string) ( $summary['coupon']['code'] ?? '' ), $cart_total );
        $discount_total = $coupon['discount'];
        $coupon_code    = $coupon['code'];

        global $wpdb;
        $booking_ids = array();
        $applied     = 0.0;
        $index       = 0;
        $count       = count( $cart );

        foreach ( $cart as $item ) {
            $index++;
            $subtotal = (float) $item['price'] * (int) $item['quantity'];

            // Proportional discount; the last line absorbs any rounding remainder.
            $line_discount = 0.0;
            if ( $discount_total > 0 && $cart_total > 0 ) {
                $line_discount = ( $index === $count )
                    ? round( $discount_total - $applied, 2 )
                    : round( $discount_total * ( $subtotal / $cart_total ), 2 );
                $applied += $line_discount;
            }

            $data = array(
                'booking_number'  => $this->generate_booking_number(),
                'user_id'         => get_current_user_id(),
                'booking_type'    => sanitize_text_field( $item['item_type'] ?? 'trip' ),
                'item_id'         => absint( $item['item_id'] ?? 0 ),
                'status'          => 'pending',
                'total_price'     => max( 0, round( $subtotal - $line_discount, 2 ) ),
                'currency'        => get_option( 'wptm_currency', 'USD' ),
                'travelers_count' => absint( $item['guests'] ?? $item['quantity'] ?? 1 ),
                'check_in'        => sanitize_text_field( $item['check_in'] ?: ( $item['date'] ?? '' ) ),
                'check_out'       => sanitize_text_field( $item['check_out'] ?? '' ),
                'payment_method'  => 'manual',
                'payment_status'  => 'awaiting',
                'customer_name'   => $name,
                'customer_email'  => $email,
                'customer_phone'  => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                'customer_address'=> sanitize_textarea_field( wp_unslash( $_POST['customer_address'] ?? '' ) ),
                'coupon_code'     => $coupon_code,
                'discount_amount' => $line_discount,
                'ip_address'      => $this->get_client_ip(),
            );

            if ( empty( $data['item_id'] ) ) {
                continue;
            }

            if ( $wpdb->insert( $wpdb->prefix . 'wptm_bookings', $data ) ) {
                $booking_id    = $wpdb->insert_id;
                $booking_ids[] = $booking_id;
                do_action( 'wptm_booking_created', $booking_id, $data );
            }
        }

        if ( empty( $booking_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'Could not create your order. Please try again.', 'journeyloom' ) ) );
        }

        $cart_module->clear_cart();

        // Logged-in customers land on their full order list; guests see the first
        // order's confirmation page.
        $my_bookings = is_user_logged_in() ? wptm_get_page_url( 'my_bookings' ) : '';
        if ( $my_bookings ) {
            $redirect = $my_bookings;
        } else {
            $confirm = wptm_get_page_url( 'confirmation' );
            if ( ! $confirm ) {
                $confirm = home_url( '/booking-confirmation/' );
            }
            $redirect = add_query_arg( 'booking', $booking_ids[0], $confirm );
        }

        wp_send_json_success( array(
            'message'     => __( 'Order placed successfully!', 'journeyloom' ),
            'booking_ids' => $booking_ids,
            'redirect'    => $redirect,
        ) );
    }

    public function check_availability() {
        check_ajax_referer( 'wptm_booking_nonce', 'nonce' );

        $item_id   = absint( $_POST['item_id'] ?? 0 );
        $item_type = sanitize_text_field( wp_unslash( $_POST['item_type'] ?? 'trip' ) );
        $date      = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
        $guests    = absint( $_POST['guests'] ?? 1 );

        global $wpdb;
        // Any rule covering this date is the source of truth (most recent wins),
        // regardless of its status — so an explicit "blocked" period is honoured.
        $availability = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wptm_availability
             WHERE item_id = %d AND item_type = %s AND date_start <= %s AND date_end >= %s
             ORDER BY id DESC LIMIT 1",
            $item_id, $item_type, $date, $date
        ) );

        // 1) An explicit availability record exists — it is the source of truth.
        if ( $availability ) {
            // A non-"available" status (e.g. blocked/closed) means no booking.
            if ( 'available' !== $availability->status ) {
                wp_send_json_success( array(
                    'available'   => false,
                    'spots_left'  => 0,
                    'reason'      => 'blocked',
                ) );
            }

            $spots = (int) $availability->available_spots;
            // Subtract travelers already booked for this date.
            $booked = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE( SUM( travelers_count ), 0 ) FROM {$wpdb->prefix}wptm_bookings
                 WHERE item_id = %d AND booking_type = %s AND check_in = %s AND status IN ( 'pending', 'confirmed' )",
                $item_id, $item_type, $date
            ) );
            $left = max( 0, $spots - $booked );

            wp_send_json_success( array(
                'available'      => $left >= $guests,
                'spots_left'     => $left,
                'price_override' => $availability->price_override,
            ) );
        }

        // 2) No record — derive capacity from the item itself.
        //    Trips use their max group size; everything else is treated as
        //    untracked (unlimited) unless a developer supplies a capacity.
        $capacity = null; // null === unlimited / not tracked.
        if ( 'trip' === $item_type ) {
            $group_max = (int) get_post_meta( $item_id, '_wptm_group_max', true );
            if ( $group_max > 0 ) {
                $capacity = $group_max;
            }
        }

        /**
         * Filter the bookable capacity for an item/date when no explicit
         * availability record exists. Return null for "unlimited".
         *
         * @param int|null $capacity  Total spots, or null for unlimited.
         * @param int      $item_id   Trip/hotel ID.
         * @param string   $item_type 'trip' or 'hotel'.
         * @param string   $date      Requested date (Y-m-d).
         */
        $capacity = apply_filters( 'wptm_item_capacity', $capacity, $item_id, $item_type, $date );

        if ( null === $capacity ) {
            // Capacity not tracked — available, but don't show a made-up number.
            wp_send_json_success( array( 'available' => true, 'spots_left' => null, 'unlimited' => true ) );
        }

        // Subtract travelers already booked for this date (pending/confirmed).
        $booked = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE( SUM( travelers_count ), 0 ) FROM {$wpdb->prefix}wptm_bookings
             WHERE item_id = %d AND booking_type = %s AND check_in = %s AND status IN ( 'pending', 'confirmed' )",
            $item_id, $item_type, $date
        ) );

        $spots_left = max( 0, (int) $capacity - $booked );
        wp_send_json_success( array(
            'available'  => $spots_left >= $guests,
            'spots_left' => $spots_left,
        ) );
    }

    public function update_status() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $status     = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );

        if ( ! in_array( $status, array( 'pending', 'confirmed', 'cancelled', 'completed' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid status.', 'journeyloom' ) ) );
        }

        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'wptm_bookings',
            array( 'status' => $status ),
            array( 'id' => $booking_id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( false !== $updated ) {
            do_action( 'wptm_booking_status_changed', $booking_id, $status );
            wp_send_json_success( array( 'message' => __( 'Status updated.', 'journeyloom' ) ) );
        }

        wp_send_json_error();
    }

    public static function get_booking( $booking_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wptm_bookings WHERE id = %d", $booking_id
        ) );
    }

    public static function get_bookings( $args = array() ) {
        global $wpdb;
        $defaults = array( 'status' => '', 'type' => '', 'limit' => 20, 'offset' => 0, 'orderby' => 'created_at', 'order' => 'DESC' );
        $args = wp_parse_args( $args, $defaults );

        $where = "WHERE 1=1";
        $params = array();

        if ( $args['status'] ) { $where .= " AND status = %s"; $params[] = $args['status']; }
        if ( $args['type'] )   { $where .= " AND booking_type = %s"; $params[] = $args['type']; }

        $orderby = in_array( $args['orderby'], array( 'created_at', 'total_price', 'status' ) ) ? $args['orderby'] : 'created_at';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        // $where holds only static SQL + %s placeholders (values are in $params);
        // $orderby and $order are whitelisted above; the table name comes from
        // $wpdb->prefix. Nothing here is user-controlled, so the interpolation is safe.
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        $sql = "SELECT * FROM {$wpdb->prefix}wptm_bookings {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- placeholders are prepared below; identifiers are whitelisted.
        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }

    public function add_booking_meta( $booking_id, $key, $value ) {
        global $wpdb;
        return $wpdb->insert( $wpdb->prefix . 'wptm_booking_meta', array(
            'booking_id' => $booking_id,
            'meta_key'   => $key,
            'meta_value' => maybe_serialize( $value ),
        ) );
    }

    private function generate_booking_number() {
        return 'WPTM-' . strtoupper( wp_generate_password( 8, false ) );
    }

    /**
     * Best-effort client IP.
     *
     * Only the connection's REMOTE_ADDR is trusted by default — proxy headers
     * like X-Forwarded-For are client-spoofable, so they are used only when the
     * site owner explicitly opts in (behind a trusted reverse proxy) via the
     * 'wptm_trust_proxy_ip' filter.
     */
    private function get_client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        if ( apply_filters( 'wptm_trust_proxy_ip', false ) ) {
            foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR' ) as $key ) {
                if ( ! empty( $_SERVER[ $key ] ) ) {
                    $forwarded = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                    // X-Forwarded-For may be a comma-separated list; take the first.
                    $forwarded = trim( explode( ',', $forwarded )[0] );
                    if ( filter_var( $forwarded, FILTER_VALIDATE_IP ) ) {
                        $ip = $forwarded;
                        break;
                    }
                }
            }
        }

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}
