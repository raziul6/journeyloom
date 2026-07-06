<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).
/**
 * Booking detail panel (loaded into the admin drawer via AJAX).
 *
 * @var object $booking Row from wptm_bookings.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$sym  = get_option( 'wptm_currency_symbol', '$' );
$item = get_post( $booking->item_id );

$travelers = $wpdb->get_results( $wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->prefix}wptm_booking_meta WHERE booking_id = %d AND meta_key LIKE %s ORDER BY meta_id ASC",
    $booking->id,
    '_traveler_%'
) );

$tiers_meta = $wpdb->get_var( $wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->prefix}wptm_booking_meta WHERE booking_id = %d AND meta_key = %s LIMIT 1",
    $booking->id,
    '_pricing_tiers'
) );
$pricing_tiers = $tiers_meta ? maybe_unserialize( $tiers_meta ) : array();

$pickup_meta = $wpdb->get_var( $wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->prefix}wptm_booking_meta WHERE booking_id = %d AND meta_key = %s LIMIT 1",
    $booking->id,
    '_pickup_points'
) );
$pickups = $pickup_meta ? maybe_unserialize( $pickup_meta ) : array();

$subtotal = (float) $booking->total_price + (float) $booking->discount_amount;
$initial  = strtoupper( substr( $booking->customer_name ?: '?', 0, 1 ) );
$date_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

// Map a status string to a visual tone for the header pills.
$wptm_tone = function ( $s ) {
    $s = strtolower( (string) $s );
    if ( in_array( $s, array( 'confirmed', 'completed', 'paid', 'active' ), true ) )            return 'good';
    if ( in_array( $s, array( 'cancelled', 'canceled', 'failed', 'expired', 'refunded' ), true ) ) return 'bad';
    return 'warn'; // pending, awaiting, processing, unpaid, etc.
};

// Guard against empty / zero-dates (avoids "November 30, -0001").
$wptm_valid_date = function ( $d ) {
    return $d && '0000-00-00' !== substr( (string) $d, 0, 10 ) && strtotime( (string) $d ) > 0;
};
?>
<div class="wptm-bd">

    <div class="wptm-bd__head">
        <div class="wptm-bd__head-top">
            <div class="wptm-bd__avatar"><?php echo esc_html( $initial ); ?></div>
            <div class="wptm-bd__head-info">
                <h2><?php echo esc_html( $booking->customer_name ); ?></h2>
                <code class="wptm-bd__number"><span class="dashicons dashicons-tag"></span><?php echo esc_html( $booking->booking_number ); ?></code>
            </div>
        </div>
        <div class="wptm-bd__statusbar">
            <span class="wptm-bd__pill is-<?php echo esc_attr( $wptm_tone( $booking->status ) ); ?>">
                <i class="dot"></i><small><?php esc_html_e( 'Booking', 'byteflows-travel-hotel-booking' ); ?></small><b><?php echo esc_html( ucfirst( $booking->status ) ); ?></b>
            </span>
            <span class="wptm-bd__pill is-<?php echo esc_attr( $wptm_tone( $booking->payment_status ) ); ?>">
                <i class="dot"></i><small><?php esc_html_e( 'Payment', 'byteflows-travel-hotel-booking' ); ?></small><b><?php echo esc_html( ucfirst( $booking->payment_status ) ); ?></b>
            </span>
        </div>
    </div>

    <div class="wptm-bd__body">

        <!-- Booking summary -->
        <div class="wptm-bd__section">
            <h3><span class="ico"><span class="dashicons dashicons-tickets-alt"></span></span> <?php esc_html_e( 'Booking', 'byteflows-travel-hotel-booking' ); ?></h3>
            <div class="wptm-bd__grid">
                <div class="wptm-bd__row"><span><?php esc_html_e( 'Item', 'byteflows-travel-hotel-booking' ); ?></span><strong>
                    <?php if ( $item ) : ?><a href="<?php echo esc_url( get_edit_post_link( $item->ID ) ); ?>" target="_blank"><?php echo esc_html( $item->post_title ); ?></a><?php else : ?>—<?php endif; ?>
                </strong></div>
                <div class="wptm-bd__row"><span><?php esc_html_e( 'Type', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo esc_html( ucfirst( $booking->booking_type ) ); ?></strong></div>
                <div class="wptm-bd__row"><span><?php esc_html_e( 'Travelers', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo (int) $booking->travelers_count; ?></strong></div>
                <?php if ( $wptm_valid_date( $booking->check_in ) ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'Check-in', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) ); ?></strong></div><?php endif; ?>
                <?php if ( $wptm_valid_date( $booking->check_out ) ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'Check-out', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?></strong></div><?php endif; ?>
                <div class="wptm-bd__row"><span><?php esc_html_e( 'Placed', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo esc_html( date_i18n( $date_fmt, strtotime( $booking->created_at ) ) ); ?></strong></div>
            </div>
        </div>

        <!-- Customer -->
        <div class="wptm-bd__section">
            <h3><span class="ico"><span class="dashicons dashicons-admin-users"></span></span> <?php esc_html_e( 'Customer', 'byteflows-travel-hotel-booking' ); ?></h3>
            <div class="wptm-bd__grid">
                <div class="wptm-bd__row wptm-bd__row--full"><span><?php esc_html_e( 'Email', 'byteflows-travel-hotel-booking' ); ?></span><strong><a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a></strong></div>
                <?php if ( $booking->customer_phone ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'Phone', 'byteflows-travel-hotel-booking' ); ?></span><strong><a href="tel:<?php echo esc_attr( $booking->customer_phone ); ?>"><?php echo esc_html( $booking->customer_phone ); ?></a></strong></div><?php endif; ?>
                <?php if ( $booking->ip_address ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'IP', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo esc_html( $booking->ip_address ); ?></strong></div><?php endif; ?>
                <?php if ( $booking->customer_address ) : ?><div class="wptm-bd__row wptm-bd__row--full"><span><?php esc_html_e( 'Address', 'byteflows-travel-hotel-booking' ); ?></span><strong><?php echo nl2br( esc_html( $booking->customer_address ) ); ?></strong></div><?php endif; ?>
            </div>
        </div>

        <?php if ( ! empty( $travelers ) ) : ?>
        <div class="wptm-bd__section">
            <h3><span class="ico"><span class="dashicons dashicons-groups"></span></span> <?php esc_html_e( 'Traveler Details', 'byteflows-travel-hotel-booking' ); ?></h3>
            <div class="wptm-bd__travelers">
                <?php foreach ( $travelers as $i => $t ) : $data = maybe_unserialize( $t->meta_value ); if ( ! is_array( $data ) ) continue; ?>
                <div class="wptm-bd__traveler">
                    <span class="wptm-bd__traveler-avatar"><?php echo esc_html( strtoupper( substr( $data['name'] ?: 'T', 0, 1 ) ) ); ?></span>
                    <div class="wptm-bd__traveler-meta">
                        <strong><?php echo esc_html( $data['name'] ?: sprintf( /* translators: %d: traveler number. */ __( 'Traveler %d', 'byteflows-travel-hotel-booking' ), (int) ( $i + 1 ) ) ); ?></strong>
                        <span><?php echo esc_html( ucfirst( $data['type'] ?? 'adult' ) ); ?><?php echo ! empty( $data['age'] ) ? ' · ' . esc_html( $data['age'] ) . ' ' . esc_html__( 'yrs', 'byteflows-travel-hotel-booking' ) : ''; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $pickups ) && is_array( $pickups ) ) : ?>
        <div class="wptm-bd__section">
            <h3><span class="ico"><span class="dashicons dashicons-location-alt"></span></span> <?php esc_html_e( 'Pickup Points', 'byteflows-travel-hotel-booking' ); ?></h3>
            <div class="wptm-bd__travelers">
                <?php foreach ( $pickups as $pp ) : if ( ! is_array( $pp ) ) continue; $price = (float) ( $pp['price'] ?? 0 ); ?>
                <div class="wptm-bd__traveler">
                    <span class="wptm-bd__traveler-avatar"><span class="dashicons dashicons-location" style="font-size:15px;width:15px;height:15px;"></span></span>
                    <div class="wptm-bd__traveler-meta">
                        <strong><?php echo esc_html( $pp['label'] ?? '' ); ?></strong>
                        <span><?php echo $price > 0 ? esc_html( $sym . number_format( $price, 2 ) ) : esc_html__( 'Free', 'byteflows-travel-hotel-booking' ); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $booking->notes ) : ?>
        <div class="wptm-bd__section">
            <h3><span class="ico"><span class="dashicons dashicons-edit-page"></span></span> <?php esc_html_e( 'Special Requests', 'byteflows-travel-hotel-booking' ); ?></h3>
            <p class="wptm-bd__notes"><?php echo nl2br( esc_html( $booking->notes ) ); ?></p>
        </div>
        <?php endif; ?>

        <!-- Payment -->
        <div class="wptm-bd__section">
            <h3><span class="ico"><span class="dashicons dashicons-money-alt"></span></span> <?php esc_html_e( 'Payment', 'byteflows-travel-hotel-booking' ); ?></h3>
            <div class="wptm-bd__summary">
                <div class="wptm-bd__summary-lines">
                    <?php if ( ! empty( $pricing_tiers ) && is_array( $pricing_tiers ) ) : ?>
                        <?php foreach ( $pricing_tiers as $pt ) : ?>
                        <div class="line muted"><span><?php echo esc_html( $pt['label'] . ' × ' . (int) $pt['qty'] ); ?></span><span><?php echo esc_html( $sym . number_format( (float) $pt['price'] * (int) $pt['qty'], 2 ) ); ?></span></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="line"><span><?php esc_html_e( 'Subtotal', 'byteflows-travel-hotel-booking' ); ?></span><span><?php echo esc_html( $sym . number_format( $subtotal, 2 ) ); ?></span></div>
                    <?php if ( (float) $booking->discount_amount > 0 ) : ?>
                    <div class="line discount"><span><?php esc_html_e( 'Discount', 'byteflows-travel-hotel-booking' ); ?><?php echo $booking->coupon_code ? ' (' . esc_html( $booking->coupon_code ) . ')' : ''; ?></span><span>-<?php echo esc_html( $sym . number_format( $booking->discount_amount, 2 ) ); ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="line total"><span><?php esc_html_e( 'Total', 'byteflows-travel-hotel-booking' ); ?></span><span><?php echo esc_html( $sym . number_format( $booking->total_price, 2 ) ); ?></span></div>
                <div class="line method"><span><span class="dashicons dashicons-bank"></span> <?php esc_html_e( 'Method', 'byteflows-travel-hotel-booking' ); ?></span><span><?php echo esc_html( ucfirst( $booking->payment_method ?: '—' ) ); ?></span></div>
            </div>
        </div>

        <?php if ( $booking->customer_email ) : ?>
        <!-- Reply to customer -->
        <div class="wptm-bd__section wptm-bd__reply" data-id="<?php echo esc_attr( $booking->id ); ?>">
            <h3><span class="ico"><span class="dashicons dashicons-email"></span></span> <?php esc_html_e( 'Reply to Customer', 'byteflows-travel-hotel-booking' ); ?></h3>
            <?php
            /**
             * The Pro add-on injects the "Draft with AI" reply tools here.
             *
             * @param object $booking Booking row.
             */
            do_action( 'wptm_booking_reply_ai_tools', $booking );
            ?>
            <?php /* translators: %s: booking reference number. */ ?>
            <input type="text" class="wptm-reply-subject" value="<?php echo esc_attr( sprintf( __( 'Regarding your booking %s', 'byteflows-travel-hotel-booking' ), $booking->booking_number ) ); ?>">
            <?php /* translators: %s: customer name. */ ?>
            <textarea class="wptm-reply-message" rows="6" placeholder="<?php echo esc_attr( sprintf( __( 'Write your reply to %s…', 'byteflows-travel-hotel-booking' ), $booking->customer_name ) ); ?>"></textarea>
            <div class="wptm-reply__foot">
                <button type="button" class="button button-primary wptm-reply-send"><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Send', 'byteflows-travel-hotel-booking' ); ?></button>
                <button type="button" class="button wptm-reply-copy"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'byteflows-travel-hotel-booking' ); ?></button>
                <span class="wptm-reply__status" aria-live="polite"></span>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.wptm-bd__body -->

    <!-- Actions -->
    <div class="wptm-bd__actions" data-id="<?php echo esc_attr( $booking->id ); ?>">
        <?php
        /**
         * Fires at the start of the booking-detail action row. Add-ons hook
         * here to add actions (e.g. the Pro add-on's invoice link).
         *
         * @param object $booking Booking row.
         */
        do_action( 'wptm_booking_details_actions', $booking );
        ?>
        <?php if ( 'pending' === $booking->status ) : ?>
            <button class="button button-primary wptm-booking-action" data-action="confirm" data-id="<?php echo esc_attr( $booking->id ); ?>"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Confirm', 'byteflows-travel-hotel-booking' ); ?></button>
        <?php endif; ?>
        <?php if ( 'confirmed' === $booking->status ) : ?>
            <button class="button button-primary wptm-booking-action" data-action="complete" data-id="<?php echo esc_attr( $booking->id ); ?>"><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Mark Completed', 'byteflows-travel-hotel-booking' ); ?></button>
        <?php endif; ?>
        <?php if ( ! in_array( $booking->status, array( 'cancelled', 'completed' ), true ) ) : ?>
            <button class="button wptm-booking-action" data-action="cancel" data-id="<?php echo esc_attr( $booking->id ); ?>"><?php esc_html_e( 'Cancel', 'byteflows-travel-hotel-booking' ); ?></button>
        <?php endif; ?>
        <button class="button wptm-booking-action wptm-bd__delete" data-action="delete" data-id="<?php echo esc_attr( $booking->id ); ?>"><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'byteflows-travel-hotel-booking' ); ?></button>
    </div>

</div>
