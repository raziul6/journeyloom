<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Booking Confirmation Template.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

// Redirects use ?booking=ID; keep ?booking_id= as a fallback for older links.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only booking id from a confirmation/redirect link.
$booking_id = absint( $_GET['booking'] ?? ( $_GET['booking_id'] ?? 0 ) );
$booking    = $booking_id ? \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id ) : null;
$sym        = get_option( 'wptm_currency_symbol', '$' );

$is_manual   = $booking && 'manual' === $booking->payment_method;
$is_unpaid   = $booking && 'paid' !== $booking->payment_status;
$my_bookings = wptm_get_page_url( 'my_bookings' );
?>
<div style="max-width:700px;margin:60px auto;padding:0 20px;text-align:center;">
    <?php if ( $booking ) : ?>
        <div style="font-size:64px;margin-bottom:16px;"><?php echo ( $is_unpaid && $is_manual ) ? '📝' : '✅'; ?></div>
        <h1 style="font-family:var(--wptm-font-display);font-size:32px;font-weight:700;margin-bottom:12px;">
            <?php echo ( $is_unpaid && $is_manual ) ? esc_html__( 'Order Received!', 'byteflows-travel-hotel-booking' ) : esc_html__( 'Booking Confirmed!', 'byteflows-travel-hotel-booking' ); ?>
        </h1>
        <p style="font-size:16px;color:#64748b;margin-bottom:32px;"><?php esc_html_e( 'Thank you for your booking. A confirmation email is on its way.', 'byteflows-travel-hotel-booking' ); ?></p>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:32px;text-align:left;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div><strong><?php esc_html_e( 'Booking Number', 'byteflows-travel-hotel-booking' ); ?></strong><br><span style="color:#fd4621;font-size:18px;font-weight:700;"><?php echo esc_html( $booking->booking_number ); ?></span></div>
                <div><strong><?php esc_html_e( 'Status', 'byteflows-travel-hotel-booking' ); ?></strong><br><span class="wptm-badge wptm-badge--<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span></div>
                <div><strong><?php esc_html_e( 'Name', 'byteflows-travel-hotel-booking' ); ?></strong><br><?php echo esc_html( $booking->customer_name ); ?></div>
                <div><strong><?php esc_html_e( 'Email', 'byteflows-travel-hotel-booking' ); ?></strong><br><?php echo esc_html( $booking->customer_email ); ?></div>
                <div><strong><?php esc_html_e( 'Check-in', 'byteflows-travel-hotel-booking' ); ?></strong><br><?php echo esc_html( $booking->check_in ?: '—' ); ?></div>
                <div><strong><?php esc_html_e( 'Check-out', 'byteflows-travel-hotel-booking' ); ?></strong><br><?php echo esc_html( $booking->check_out ?: '—' ); ?></div>
                <div><strong><?php esc_html_e( 'Travelers', 'byteflows-travel-hotel-booking' ); ?></strong><br><?php echo intval( $booking->travelers_count ); ?></div>
                <div><strong><?php esc_html_e( 'Total', 'byteflows-travel-hotel-booking' ); ?></strong><br><span style="font-size:20px;font-weight:700;color:#fd4621;"><?php echo esc_html( $sym . number_format( $booking->total_price, 2 ) ); ?></span></div>
            </div>
        </div>

        <?php if ( $is_manual && $is_unpaid ) : ?>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:16px;padding:24px;text-align:left;margin-top:20px;">
                <h3 style="margin:0 0 8px;font-size:16px;"><?php esc_html_e( 'Complete Your Payment', 'byteflows-travel-hotel-booking' ); ?></h3>
                <?php
                $instructions = get_option( 'wptm_bank_instructions', '' );
                if ( '' === trim( (string) $instructions ) ) {
                    $instructions = __( 'Please transfer the total amount using your booking number as the payment reference. Your booking will be confirmed once we receive and verify your payment.', 'byteflows-travel-hotel-booking' );
                }
                /**
                 * Filter the bank-transfer instructions shown on the order page.
                 *
                 * @param string $instructions Instruction text.
                 * @param object $booking      Booking row.
                 */
                $instructions = apply_filters( 'wptm_bank_transfer_instructions', $instructions, $booking );
                ?>
                <div style="margin:0;color:#7c2d12;font-size:14px;line-height:1.6;"><?php echo wp_kses_post( wpautop( $instructions ) ); ?></div>
            </div>
        <?php endif; ?>

        <div style="margin-top:32px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <?php if ( is_user_logged_in() && $my_bookings ) : ?>
                <a href="<?php echo esc_url( $my_bookings ); ?>" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'View My Bookings', 'byteflows-travel-hotel-booking' ); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wptm-btn"><?php esc_html_e( 'Back to Home', 'byteflows-travel-hotel-booking' ); ?></a>
        </div>
    <?php else : ?>
        <div style="font-size:64px;margin-bottom:16px;">🔍</div>
        <p style="font-size:18px;color:#94a3b8;"><?php esc_html_e( 'Booking not found.', 'byteflows-travel-hotel-booking' ); ?></p>
        <div style="margin-top:24px;"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'Back to Home', 'byteflows-travel-hotel-booking' ); ?></a></div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
