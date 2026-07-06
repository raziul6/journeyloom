<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Payment method selector — styled card list of the active gateways.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$wptm_methods = wptm_payment_methods();
?>
<div class="wptm-form-group wptm-payment">
    <label class="wptm-payment__label"><?php esc_html_e( 'Payment Method', 'byteflows-travel-hotel-booking' ); ?></label>
    <div class="wptm-payment-methods" role="radiogroup" aria-label="<?php esc_attr_e( 'Payment Method', 'byteflows-travel-hotel-booking' ); ?>">
        <?php foreach ( $wptm_methods as $i => $wptm_m ) : ?>
            <label class="wptm-payment-method">
                <input type="radio" name="payment_method" value="<?php echo esc_attr( $wptm_m['id'] ); ?>" <?php checked( 0, $i ); ?>>
                <span class="wptm-payment-method__icon"><?php echo wp_kses( wptm_payment_icon( $wptm_m['icon'] ), wptm_svg_allowed() ); ?></span>
                <span class="wptm-payment-method__body">
                    <span class="wptm-payment-method__title"><?php echo esc_html( $wptm_m['title'] ); ?></span>
                    <?php if ( ! empty( $wptm_m['desc'] ) ) : ?>
                        <span class="wptm-payment-method__desc"><?php echo esc_html( $wptm_m['desc'] ); ?></span>
                    <?php endif; ?>
                </span>
                <span class="wptm-payment-method__check" aria-hidden="true"></span>
            </label>
        <?php endforeach; ?>
    </div>

    <?php
    /**
     * Fires after the payment-method list. Gateway add-ons render their
     * per-gateway detail areas here (e.g. a card element); JS reveals the
     * .wptm-payment-detail--{id} block matching the selected method.
     *
     * @param array $wptm_methods The listed payment methods.
     */
    do_action( 'wptm_payment_method_details', $wptm_methods );
    ?>
</div>
