<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Checkout Template (session cart).
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$cart_module = \JourneyLoom\Plugin::get_instance()->get_module( 'cart' );
$summary     = $cart_module ? $cart_module->get_cart_summary() : array( 'items' => array(), 'total' => 0, 'final_total' => 0, 'coupon' => null );
$items       = $summary['items'];
$sym         = get_option( 'wptm_currency_symbol', '$' );
?>
<div class="wptm-checkout">
    <h1 style="font-family:var(--wptm-font-display);font-size:32px;font-weight:700;margin-bottom:32px;"><?php esc_html_e( 'Checkout', 'byteflows-travel-hotel-booking' ); ?></h1>
    <?php if ( empty( $items ) ) : ?>
        <div style="text-align:center;padding:60px 0;">
            <p style="font-size:48px;">🛒</p>
            <p style="font-size:18px;color:#94a3b8;"><?php esc_html_e( 'Your cart is empty.', 'byteflows-travel-hotel-booking' ); ?></p>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'wptm_trip' ) ); ?>" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'Browse Trips', 'byteflows-travel-hotel-booking' ); ?></a>
        </div>
    <?php else : ?>
        <div class="wptm-checkout__grid">
            <div>
                <h3 style="margin-bottom:16px;"><?php esc_html_e( 'Your Details', 'byteflows-travel-hotel-booking' ); ?></h3>
                <form id="wptm-checkout-form" class="wptm-checkout-form">
                    <?php wp_nonce_field( 'wptm_booking_nonce', 'nonce' ); ?>
                    <div class="wptm-form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="wptm-form-group"><label><?php esc_html_e( 'Full Name', 'byteflows-travel-hotel-booking' ); ?></label><input type="text" name="customer_name" required></div>
                        <div class="wptm-form-group"><label><?php esc_html_e( 'Email', 'byteflows-travel-hotel-booking' ); ?></label><input type="email" name="customer_email" required></div>
                    </div>
                    <div class="wptm-form-group"><label><?php esc_html_e( 'Phone', 'byteflows-travel-hotel-booking' ); ?></label><input type="tel" name="customer_phone"></div>
                    <div class="wptm-form-group"><label><?php esc_html_e( 'Address', 'byteflows-travel-hotel-booking' ); ?></label><textarea name="customer_address" rows="2"></textarea></div>
                    <?php wptm_get_template_part( 'partials/payment-methods.php' ); ?>
                    <button type="submit" class="wptm-btn wptm-btn--primary wptm-btn--lg" style="width:100%;justify-content:center;"><?php esc_html_e( 'Place Order', 'byteflows-travel-hotel-booking' ); ?> →</button>
                </form>
            </div>
            <div>
                <h3 style="margin-bottom:16px;"><?php esc_html_e( 'Order Summary', 'byteflows-travel-hotel-booking' ); ?></h3>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
                    <?php foreach ( $items as $item ) : ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;">
                            <span><?php echo esc_html( $item['title'] ); ?> × <?php echo intval( $item['quantity'] ); ?></span>
                            <strong><?php echo esc_html( $sym . number_format( $item['subtotal'], 2 ) ); ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <?php if ( ! empty( $summary['coupon'] ) ) : ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;color:#16a34a;">
                            <span><?php /* translators: %s: applied coupon code. */ echo esc_html( sprintf( __( 'Discount (%s)', 'byteflows-travel-hotel-booking' ), $summary['coupon']['code'] ) ); ?></span>
                            <strong>-<?php echo esc_html( $sym . number_format( $summary['coupon']['discount'], 2 ) ); ?></strong>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:20px;font-weight:700;color:#fd4621;">
                        <span><?php esc_html_e( 'Total', 'byteflows-travel-hotel-booking' ); ?></span>
                        <span><?php echo esc_html( $sym . number_format( max( 0, $summary['final_total'] ), 2 ) ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
