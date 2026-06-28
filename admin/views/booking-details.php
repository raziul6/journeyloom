<?php
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

$subtotal = (float) $booking->total_price + (float) $booking->discount_amount;
$initial  = strtoupper( substr( $booking->customer_name ?: '?', 0, 1 ) );
$date_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="wptm-bd">

    <div class="wptm-bd__head">
        <div class="wptm-bd__avatar"><?php echo esc_html( $initial ); ?></div>
        <div class="wptm-bd__head-info">
            <h2><?php echo esc_html( $booking->customer_name ); ?></h2>
            <code class="wptm-bd__number"><?php echo esc_html( $booking->booking_number ); ?></code>
        </div>
        <div class="wptm-bd__badges">
            <span class="wptm-badge wptm-badge-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span>
            <span class="wptm-badge wptm-badge-<?php echo esc_attr( $booking->payment_status ); ?>"><?php echo esc_html( ucfirst( $booking->payment_status ) ); ?></span>
        </div>
    </div>

    <!-- Booking summary -->
    <div class="wptm-bd__section">
        <h3><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e( 'Booking', 'wp-travel-machine' ); ?></h3>
        <div class="wptm-bd__grid">
            <div class="wptm-bd__row"><span><?php esc_html_e( 'Item', 'wp-travel-machine' ); ?></span><strong>
                <?php if ( $item ) : ?><a href="<?php echo esc_url( get_edit_post_link( $item->ID ) ); ?>" target="_blank"><?php echo esc_html( $item->post_title ); ?></a><?php else : ?>—<?php endif; ?>
            </strong></div>
            <div class="wptm-bd__row"><span><?php esc_html_e( 'Type', 'wp-travel-machine' ); ?></span><strong><?php echo esc_html( ucfirst( $booking->booking_type ) ); ?></strong></div>
            <div class="wptm-bd__row"><span><?php esc_html_e( 'Travelers', 'wp-travel-machine' ); ?></span><strong><?php echo (int) $booking->travelers_count; ?></strong></div>
            <?php if ( $booking->check_in ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'Check-in', 'wp-travel-machine' ); ?></span><strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) ) ); ?></strong></div><?php endif; ?>
            <?php if ( $booking->check_out ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'Check-out', 'wp-travel-machine' ); ?></span><strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) ) ); ?></strong></div><?php endif; ?>
            <div class="wptm-bd__row"><span><?php esc_html_e( 'Placed', 'wp-travel-machine' ); ?></span><strong><?php echo esc_html( date_i18n( $date_fmt, strtotime( $booking->created_at ) ) ); ?></strong></div>
        </div>
    </div>

    <!-- Customer -->
    <div class="wptm-bd__section">
        <h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Customer', 'wp-travel-machine' ); ?></h3>
        <div class="wptm-bd__grid">
            <div class="wptm-bd__row"><span><?php esc_html_e( 'Email', 'wp-travel-machine' ); ?></span><strong><a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a></strong></div>
            <?php if ( $booking->customer_phone ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'Phone', 'wp-travel-machine' ); ?></span><strong><a href="tel:<?php echo esc_attr( $booking->customer_phone ); ?>"><?php echo esc_html( $booking->customer_phone ); ?></a></strong></div><?php endif; ?>
            <?php if ( $booking->customer_address ) : ?><div class="wptm-bd__row wptm-bd__row--full"><span><?php esc_html_e( 'Address', 'wp-travel-machine' ); ?></span><strong><?php echo nl2br( esc_html( $booking->customer_address ) ); ?></strong></div><?php endif; ?>
            <?php if ( $booking->ip_address ) : ?><div class="wptm-bd__row"><span><?php esc_html_e( 'IP', 'wp-travel-machine' ); ?></span><strong><?php echo esc_html( $booking->ip_address ); ?></strong></div><?php endif; ?>
        </div>
    </div>

    <?php if ( ! empty( $travelers ) ) : ?>
    <div class="wptm-bd__section">
        <h3><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Traveler Details', 'wp-travel-machine' ); ?></h3>
        <div class="wptm-bd__travelers">
            <?php foreach ( $travelers as $i => $t ) : $data = maybe_unserialize( $t->meta_value ); if ( ! is_array( $data ) ) continue; ?>
            <div class="wptm-bd__traveler">
                <strong><?php echo esc_html( $data['name'] ?: sprintf( __( 'Traveler %d', 'wp-travel-machine' ), $i + 1 ) ); ?></strong>
                <span><?php echo esc_html( ucfirst( $data['type'] ?? 'adult' ) ); ?><?php echo ! empty( $data['age'] ) ? ' · ' . esc_html( $data['age'] ) . ' ' . esc_html__( 'yrs', 'wp-travel-machine' ) : ''; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $booking->notes ) : ?>
    <div class="wptm-bd__section">
        <h3><span class="dashicons dashicons-edit-page"></span> <?php esc_html_e( 'Special Requests', 'wp-travel-machine' ); ?></h3>
        <p class="wptm-bd__notes"><?php echo nl2br( esc_html( $booking->notes ) ); ?></p>
    </div>
    <?php endif; ?>

    <!-- Payment -->
    <div class="wptm-bd__section">
        <h3><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Payment', 'wp-travel-machine' ); ?></h3>
        <div class="wptm-bd__summary">
            <?php if ( ! empty( $pricing_tiers ) && is_array( $pricing_tiers ) ) : ?>
                <?php foreach ( $pricing_tiers as $pt ) : ?>
                <div class="line muted"><span><?php echo esc_html( $pt['label'] . ' × ' . (int) $pt['qty'] ); ?></span><span><?php echo esc_html( $sym . number_format( (float) $pt['price'] * (int) $pt['qty'], 2 ) ); ?></span></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="line"><span><?php esc_html_e( 'Subtotal', 'wp-travel-machine' ); ?></span><span><?php echo esc_html( $sym . number_format( $subtotal, 2 ) ); ?></span></div>
            <?php if ( (float) $booking->discount_amount > 0 ) : ?>
            <div class="line"><span><?php esc_html_e( 'Discount', 'wp-travel-machine' ); ?><?php echo $booking->coupon_code ? ' (' . esc_html( $booking->coupon_code ) . ')' : ''; ?></span><span>-<?php echo esc_html( $sym . number_format( $booking->discount_amount, 2 ) ); ?></span></div>
            <?php endif; ?>
            <div class="line total"><span><?php esc_html_e( 'Total', 'wp-travel-machine' ); ?></span><span><?php echo esc_html( $sym . number_format( $booking->total_price, 2 ) ); ?></span></div>
            <div class="line muted"><span><?php esc_html_e( 'Method', 'wp-travel-machine' ); ?></span><span><?php echo esc_html( ucfirst( $booking->payment_method ?: '—' ) ); ?></span></div>
        </div>
    </div>

    <!-- Actions -->
    <div class="wptm-bd__actions" data-id="<?php echo esc_attr( $booking->id ); ?>">
        <?php if ( 'pending' === $booking->status ) : ?>
            <button class="button button-primary wptm-booking-action" data-action="confirm" data-id="<?php echo esc_attr( $booking->id ); ?>"><?php esc_html_e( 'Confirm', 'wp-travel-machine' ); ?></button>
        <?php endif; ?>
        <?php if ( 'confirmed' === $booking->status ) : ?>
            <button class="button button-primary wptm-booking-action" data-action="complete" data-id="<?php echo esc_attr( $booking->id ); ?>"><?php esc_html_e( 'Mark Completed', 'wp-travel-machine' ); ?></button>
        <?php endif; ?>
        <?php if ( ! in_array( $booking->status, array( 'cancelled', 'completed' ), true ) ) : ?>
            <button class="button wptm-booking-action" data-action="cancel" data-id="<?php echo esc_attr( $booking->id ); ?>"><?php esc_html_e( 'Cancel', 'wp-travel-machine' ); ?></button>
        <?php endif; ?>
        <button class="button wptm-booking-action wptm-bd__delete" data-action="delete" data-id="<?php echo esc_attr( $booking->id ); ?>"><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'wp-travel-machine' ); ?></button>
    </div>

</div>
