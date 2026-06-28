<?php
/**
 * Payment method selector — styled card list of the active gateways.
 *
 * @package WPTravelMachine
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$wptm_methods = wptm_payment_methods();
?>
<div class="wptm-form-group wptm-payment">
    <label class="wptm-payment__label"><?php esc_html_e( 'Payment Method', 'wp-travel-machine' ); ?></label>
    <div class="wptm-payment-methods" role="radiogroup" aria-label="<?php esc_attr_e( 'Payment Method', 'wp-travel-machine' ); ?>">
        <?php foreach ( $wptm_methods as $i => $wptm_m ) : ?>
            <label class="wptm-payment-method">
                <input type="radio" name="payment_method" value="<?php echo esc_attr( $wptm_m['id'] ); ?>" <?php checked( 0, $i ); ?>>
                <span class="wptm-payment-method__icon"><?php echo wptm_payment_icon( $wptm_m['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?></span>
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

    <?php // Per-gateway detail areas. JS reveals the one matching the selected method. ?>
    <div class="wptm-payment-detail wptm-payment-detail--stripe" style="display:none;">
        <div class="wptm-stripe-card" aria-label="<?php esc_attr_e( 'Card details', 'wp-travel-machine' ); ?>"></div>
        <div class="wptm-stripe-error" role="alert"></div>
    </div>
    <div class="wptm-payment-detail wptm-payment-detail--paypal" style="display:none;">
        <div class="wptm-paypal-buttons"></div>
    </div>
</div>
