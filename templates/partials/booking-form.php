<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).
/**
 * Booking Form Partial Template.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$item_id   = isset( $item_id ) ? $item_id : get_the_ID();
$post_type = get_post_type( $item_id );
$is_hotel  = $post_type === 'wptm_hotel';
$sym       = get_option( 'wptm_currency_symbol', '$' );

if ( $is_hotel ) {
    global $wpdb;
    $rooms = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wptm_rooms WHERE hotel_id = %d AND status = 'available' ORDER BY price_per_night ASC",
        $item_id
    ) );

    // Effective (sale-aware) nightly price for a room.
    $wptm_room_price = static function ( $room ) {
        return ( ! empty( $room->sale_price ) && $room->sale_price < $room->price_per_night )
            ? (float) $room->sale_price
            : (float) $room->price_per_night;
    };
    $base_price = ! empty( $rooms ) ? $wptm_room_price( $rooms[0] ) : 0;
} else {
    $pricing = get_post_meta( $item_id, '_wptm_pricing', true );

    // Build the list of bookable pricing tiers (Adult, Child, …).
    $tiers = array();
    if ( is_array( $pricing ) ) {
        foreach ( $pricing as $tier ) {
            $regular = (float) ( $tier['price'] ?? 0 );
            $sale    = (float) ( $tier['sale_price'] ?? 0 );
            $eff     = $sale > 0 ? $sale : $regular;
            $label   = trim( $tier['label'] ?? '' );
            if ( $eff <= 0 && '' === $label ) {
                continue;
            }
            $tiers[] = array(
                'label'   => $label ?: __( 'Standard', 'journeyloom' ),
                'price'   => $eff,
                'regular' => $regular,
                'sale'    => $sale,
            );
        }
    }

    $multi_tier = count( $tiers ) > 1;
    // Header shows the lowest tier as a "from" price; single tier shows that price.
    $tier_prices = wp_list_pluck( $tiers, 'price' );
    $base_price  = ! empty( $tier_prices ) ? min( $tier_prices ) : 0;

    // A trip is a fixed-length package: the price is per person for the whole trip
    // and does NOT change with the chosen dates. So the visitor only picks a
    // departure date and we derive the end date from the trip's set duration.
    $trip_duration = (int) get_post_meta( $item_id, '_wptm_duration', true );
    $trip_unit     = get_post_meta( $item_id, '_wptm_duration_unit', true ) ?: 'days';
    $trip_fixed    = $trip_duration > 0;
    // Calendar days to add to the departure date to reach the end date.
    // 5 "days" trip = depart day 1 … return day 5 (+4); "nights" map 1:1; "hours" = same day.
    $end_offset = 0;
    if ( $trip_fixed ) {
        $end_offset = 'nights' === $trip_unit ? $trip_duration : ( 'hours' === $trip_unit ? 0 : max( 0, $trip_duration - 1 ) );
    }
    $duration_label = $trip_fixed
        ? sprintf( '%d %s', $trip_duration, $trip_duration === 1 ? rtrim( $trip_unit, 's' ) : $trip_unit )
        : '';
}

// Date picker mode + blocked (unavailable) periods for the calendar.
$cal_mode = $is_hotel ? 'range' : ( ! empty( $trip_fixed ) ? 'single' : 'range' );
global $wpdb;
$blocked_rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT date_start, date_end FROM {$wpdb->prefix}wptm_availability WHERE item_id = %d AND item_type = %s AND status = 'unavailable'",
    $item_id,
    $is_hotel ? 'hotel' : 'trip'
) );
$unavailable_ranges = array();
foreach ( (array) $blocked_rows as $r ) {
    $unavailable_ranges[] = array( 'start' => $r->date_start, 'end' => $r->date_end );
}

// Sold-out dates (available periods that are fully booked) + price-override periods.
$soldout_dates = array();
$price_periods = array();
$avail_rows    = $wpdb->get_results( $wpdb->prepare(
    "SELECT date_start, date_end, available_spots, price_override FROM {$wpdb->prefix}wptm_availability WHERE item_id = %d AND item_type = %s AND status = 'available'",
    $item_id,
    $is_hotel ? 'hotel' : 'trip'
) );
if ( $avail_rows ) {
    $today = gmdate( 'Y-m-d' );

    // Per-date occupancy from active bookings (hotels occupy each night in range).
    $active = $wpdb->get_results( $wpdb->prepare(
        "SELECT check_in, check_out FROM {$wpdb->prefix}wptm_bookings WHERE item_id = %d AND booking_type = %s AND status IN ( 'pending', 'confirmed' )",
        $item_id,
        $is_hotel ? 'hotel' : 'trip'
    ) );
    $occ = array();
    foreach ( (array) $active as $b ) {
        $ci = $b->check_in;
        if ( empty( $ci ) || '0000-00-00' === $ci ) {
            continue;
        }
        if ( $is_hotel ) {
            $co    = $b->check_out ?: $ci;
            $night = $ci;
            $guard = 0;
            while ( $night < $co && $guard < 730 ) {
                $occ[ $night ] = ( $occ[ $night ] ?? 0 ) + 1;
                $night = gmdate( 'Y-m-d', strtotime( $night . ' +1 day' ) );
                $guard++;
            }
            if ( $co <= $ci ) {
                $occ[ $ci ] = ( $occ[ $ci ] ?? 0 ) + 1;
            }
        } else {
            $occ[ $ci ] = ( $occ[ $ci ] ?? 0 ) + 1;
        }
    }

    foreach ( $avail_rows as $row ) {
        $spots = (int) $row->available_spots;
        if ( null !== $row->price_override && '' !== $row->price_override ) {
            $price_periods[] = array( 'start' => $row->date_start, 'end' => $row->date_end, 'price' => (float) $row->price_override );
        }
        if ( $spots <= 0 ) {
            $soldout_dates[] = array( 'start' => $row->date_start, 'end' => $row->date_end );
            continue;
        }
        $d     = max( $row->date_start, $today );
        $guard = 0;
        while ( $d <= $row->date_end && $guard < 730 ) {
            if ( ( $occ[ $d ] ?? 0 ) >= $spots ) {
                $soldout_dates[] = array( 'start' => $d, 'end' => $d );
            }
            $d = gmdate( 'Y-m-d', strtotime( $d . ' +1 day' ) );
            $guard++;
        }
    }
}

?>
<div class="wptm-booking-form" id="wptm-booking-form" data-item-id="<?php echo esc_attr( $item_id ); ?>" data-item-type="<?php echo esc_attr( $is_hotel ? 'hotel' : 'trip' ); ?>" data-base-price="<?php echo esc_attr( $base_price ); ?>"<?php if ( ! $is_hotel && ! empty( $trip_fixed ) ) : ?> data-end-offset="<?php echo esc_attr( $end_offset ); ?>"<?php endif; ?>>
    <div class="wptm-booking-form__header">
        <h3><?php echo $is_hotel ? esc_html__( 'Book This Hotel', 'journeyloom' ) : esc_html__( 'Book This Trip', 'journeyloom' ); ?></h3>
        <div class="wptm-trip-card__price" style="margin-top:8px;">
            <?php if ( ! empty( $multi_tier ) ) : ?><span class="wptm-from"><?php esc_html_e( 'from', 'journeyloom' ); ?></span> <?php endif; ?>
            <span class="amount"><?php echo esc_html( $sym . number_format( $base_price, 0 ) ); ?></span>
            <span class="per">/<?php echo $is_hotel ? esc_html__( 'night', 'journeyloom' ) : esc_html__( 'person', 'journeyloom' ); ?></span>
        </div>
    </div>

    <div class="wptm-availability-status"></div>
    <div class="wptm-booking-confirmation" style="display:none;"></div>

    <form class="wptm-booking-fields">
        <?php wp_nonce_field( 'wptm_booking_nonce', 'nonce' ); ?>

        <div class="wptm-form-group wptm-datepicker">
            <label><?php
                echo $is_hotel
                    ? esc_html__( 'Select Your Stay', 'journeyloom' )
                    : ( ! empty( $trip_fixed ) ? esc_html__( 'Departure Date', 'journeyloom' ) : esc_html__( 'Trip Dates', 'journeyloom' ) );
            ?></label>

            <div class="wptm-dp-summary">
                <div class="wptm-dp-field">
                    <span class="wptm-dp-field__label"><?php echo $is_hotel ? esc_html__( 'Check-in', 'journeyloom' ) : esc_html__( 'Start', 'journeyloom' ); ?></span>
                    <span class="wptm-dp-field__value wptm-dp-in"><?php esc_html_e( 'Select date', 'journeyloom' ); ?></span>
                </div>
                <?php if ( 'range' === $cal_mode ) : ?>
                    <span class="wptm-dp-arrow" aria-hidden="true">→</span>
                    <div class="wptm-dp-field">
                        <span class="wptm-dp-field__label"><?php echo $is_hotel ? esc_html__( 'Check-out', 'journeyloom' ) : esc_html__( 'End', 'journeyloom' ); ?></span>
                        <span class="wptm-dp-field__value wptm-dp-out"><?php esc_html_e( 'Select date', 'journeyloom' ); ?></span>
                    </div>
                <?php else : ?>
                    <span class="wptm-dp-arrow" aria-hidden="true">·</span>
                    <div class="wptm-dp-field">
                        <span class="wptm-dp-field__label"><?php esc_html_e( 'Trip Length', 'journeyloom' ); ?></span>
                        <span class="wptm-dp-field__value"><?php echo esc_html( $duration_label ); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="wptm-calendar"
                data-mode="<?php echo esc_attr( $cal_mode ); ?>"
                data-min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
                data-unavailable="<?php echo esc_attr( wp_json_encode( $unavailable_ranges ) ); ?>"
                data-soldout="<?php echo esc_attr( wp_json_encode( $soldout_dates ) ); ?>"
                data-prices="<?php echo esc_attr( wp_json_encode( $price_periods ) ); ?>"
                data-currency="<?php echo esc_attr( $sym ); ?>"
                <?php if ( ! $is_hotel && ! empty( $trip_fixed ) ) : ?>data-end-offset="<?php echo esc_attr( $end_offset ); ?>"<?php endif; ?>></div>

            <input type="hidden" id="wptm-checkin" name="check_in" value="">
            <input type="hidden" id="wptm-checkout" name="check_out" value="">

            <?php if ( ! $is_hotel ) : ?>
            <p class="wptm-date-hint">
                <span class="dashicons dashicons-info-outline"></span>
                <span>
                    <?php echo ! empty( $trip_fixed )
                        ? esc_html__( 'Fixed-length package — the price is per person for the whole trip and does not change with the dates.', 'journeyloom' )
                        : esc_html__( 'The price is per person for the whole trip and does not change with the dates.', 'journeyloom' ); ?>
                    <span class="wptm-return-hint"></span>
                </span>
            </p>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $multi_tier ) ) : ?>
        <div class="wptm-form-group">
            <label><?php esc_html_e( 'Tickets', 'journeyloom' ); ?></label>
            <div class="wptm-tiers">
                <?php foreach ( $tiers as $i => $tier ) : ?>
                <div class="wptm-tier-row">
                    <div class="wptm-tier-info">
                        <span class="wptm-tier-label"><?php echo esc_html( $tier['label'] ); ?></span>
                        <span class="wptm-tier-price">
                            <?php if ( $tier['sale'] > 0 && $tier['sale'] < $tier['regular'] ) : ?>
                                <del><?php echo esc_html( wptm_format_price( $tier['regular'] ) ); ?></del>
                            <?php endif; ?>
                            <?php echo esc_html( wptm_format_price( $tier['price'] ) ); ?> <small><?php esc_html_e( '/ person', 'journeyloom' ); ?></small>
                        </span>
                    </div>
                    <div class="wptm-tier-qty">
                        <button type="button" class="wptm-btn wptm-btn--outline wptm-btn--sm wptm-tier-minus" aria-label="<?php esc_attr_e( 'Decrease', 'journeyloom' ); ?>">−</button>
                        <input type="number" class="wptm-tier-input" name="tiers[<?php echo (int) $i; ?>][qty]" value="<?php echo 0 === $i ? 1 : 0; ?>" min="0" data-price="<?php echo esc_attr( $tier['price'] ); ?>" data-label="<?php echo esc_attr( $tier['label'] ); ?>">
                        <button type="button" class="wptm-btn wptm-btn--outline wptm-btn--sm wptm-tier-plus" aria-label="<?php esc_attr_e( 'Increase', 'journeyloom' ); ?>">+</button>
                    </div>
                    <input type="hidden" name="tiers[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $tier['label'] ); ?>">
                    <input type="hidden" name="tiers[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( $tier['price'] ); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="travelers_count" class="wptm-tier-total-count" value="1">
        </div>
        <?php else : ?>
        <div class="wptm-form-group">
            <label><?php echo $is_hotel ? esc_html__( 'Guests', 'journeyloom' ) : esc_html__( 'Travelers', 'journeyloom' ); ?></label>
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="button" class="wptm-btn wptm-btn--outline wptm-btn--sm wptm-travelers-minus">−</button>
                <input type="number" name="travelers_count" value="1" min="1" style="width:60px;text-align:center;">
                <button type="button" class="wptm-btn wptm-btn--outline wptm-btn--sm wptm-travelers-plus">+</button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $is_hotel && ! empty( $rooms ) ) : ?>
        <div class="wptm-form-group">
            <label for="wptm-room"><?php esc_html_e( 'Room Type', 'journeyloom' ); ?></label>
            <select id="wptm-room" name="room_id">
                <?php foreach ( $rooms as $room ) : $rp = $wptm_room_price( $room ); ?>
                    <option value="<?php echo esc_attr( $room->id ); ?>" data-price="<?php echo esc_attr( $rp ); ?>">
                        <?php echo esc_html( $room->room_name . ' — ' . $sym . number_format( $rp, 0 ) . '/night' ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php
        // Pro: pickup points for this trip (one selection per traveler at checkout).
        $wptm_pickups = ( ! $is_hotel && function_exists( 'wptm_is_pro' ) && wptm_is_pro() )
            ? \JourneyLoom\Booking\Pricing::pickup_points( $item_id )
            : array();
        if ( ! empty( $wptm_pickups ) ) : ?>
        <div class="wptm-form-group wptm-pickup-block" data-pickups="<?php echo esc_attr( wp_json_encode( $wptm_pickups ) ); ?>" data-currency="<?php echo esc_attr( $sym ); ?>" data-free-label="<?php esc_attr_e( 'No pickup needed', 'journeyloom' ); ?>">
            <label><?php esc_html_e( 'Pickup Point', 'journeyloom' ); ?></label>
            <p class="wptm-pickup-hint"><?php esc_html_e( 'Choose a pickup location for each traveler.', 'journeyloom' ); ?></p>
            <div class="wptm-pickups"></div>
        </div>
        <?php endif; ?>

        <hr class="wptm-divider">
        <h4 style="margin:0 0 12px;"><?php esc_html_e( 'Your Details', 'journeyloom' ); ?></h4>
        <div class="wptm-form-row">
            <div class="wptm-form-group">
                <label for="wptm-name"><?php esc_html_e( 'Full Name', 'journeyloom' ); ?></label>
                <input type="text" id="wptm-name" name="customer_name" required value="<?php echo esc_attr( wp_get_current_user()->display_name ?? '' ); ?>">
            </div>
            <div class="wptm-form-group">
                <label for="wptm-email"><?php esc_html_e( 'Email', 'journeyloom' ); ?></label>
                <input type="email" id="wptm-email" name="customer_email" required value="<?php echo esc_attr( wp_get_current_user()->user_email ?? '' ); ?>">
            </div>
        </div>
        <div class="wptm-form-group">
            <label for="wptm-phone"><?php esc_html_e( 'Phone', 'journeyloom' ); ?></label>
            <input type="tel" id="wptm-phone" name="customer_phone">
        </div>
        <div class="wptm-form-group">
            <label for="wptm-notes"><?php esc_html_e( 'Special Requests', 'journeyloom' ); ?></label>
            <textarea id="wptm-notes" name="notes" rows="3" placeholder="<?php esc_attr_e( 'Any special requests or notes...', 'journeyloom' ); ?>"></textarea>
        </div>

        <!-- Coupon -->
        <div class="wptm-form-group">
            <label><?php esc_html_e( 'Coupon Code', 'journeyloom' ); ?></label>
            <div style="display:flex;gap:8px;">
                <input type="text" name="coupon_code" placeholder="<?php esc_attr_e( 'Enter code', 'journeyloom' ); ?>">
                <button type="button" class="wptm-btn wptm-btn--outline wptm-btn--sm wptm-apply-coupon"><?php esc_html_e( 'Apply', 'journeyloom' ); ?></button>
            </div>
        </div>

        <!-- Summary -->
        <div class="wptm-booking-form__summary">
            <div class="line"><span><?php esc_html_e( 'Subtotal', 'journeyloom' ); ?></span><span class="wptm-summary-subtotal"><?php echo esc_html( $sym . number_format( $base_price, 2 ) ); ?></span></div>
            <div class="line"><span><?php esc_html_e( 'Discount', 'journeyloom' ); ?></span><span class="wptm-summary-discount">-<?php echo esc_html( $sym . '0.00' ); ?></span></div>
            <div class="line wptm-summary-pickup-line" style="display:none;"><span><?php esc_html_e( 'Pickup', 'journeyloom' ); ?></span><span class="wptm-summary-pickup"><?php echo esc_html( $sym . '0.00' ); ?></span></div>
            <div class="line total"><span><?php esc_html_e( 'Total', 'journeyloom' ); ?></span><span class="wptm-summary-total"><?php echo esc_html( $sym . number_format( $base_price, 2 ) ); ?></span></div>
        </div>

        <!-- Payment -->
        <?php wptm_get_template_part( 'partials/payment-methods.php' ); ?>

        <button type="submit" class="wptm-btn wptm-btn--primary wptm-btn--lg" style="width:100%;justify-content:center;">
            <?php esc_html_e( 'Complete Booking', 'journeyloom' ); ?> →
        </button>
    </form>
</div>
