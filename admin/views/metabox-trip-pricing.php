<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
 if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-money-alt"></span>
    <p><?php esc_html_e( 'Define one or more price tiers (e.g. Adult, Child). Add a sale price to show a discount.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>
<div id="wptm-pricing-tiers" class="wptm-repeater wptm-pricing">
    <div class="wptm-pricing-head">
        <span><?php esc_html_e( 'Label', 'byteflows-travel-hotel-booking' ); ?></span>
        <span><?php esc_html_e( 'Price', 'byteflows-travel-hotel-booking' ); ?></span>
        <span><?php esc_html_e( 'Sale Price', 'byteflows-travel-hotel-booking' ); ?></span>
        <span></span>
    </div>
    <div class="wptm-repeater-items">
    <?php foreach ( $pricing as $i => $tier ) : ?>
        <div class="wptm-repeater-item"><div class="wptm-pricing-row">
            <input type="text" name="wptm_pricing[<?php echo esc_attr( $i ); ?>][label]" value="<?php echo esc_attr( $tier['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Label', 'byteflows-travel-hotel-booking' ); ?>">
            <input type="number" name="wptm_pricing[<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( $tier['price'] ?? '' ); ?>" placeholder="0.00" step="0.01" min="0">
            <input type="number" name="wptm_pricing[<?php echo esc_attr( $i ); ?>][sale_price]" value="<?php echo esc_attr( $tier['sale_price'] ?? '' ); ?>" placeholder="0.00" step="0.01" min="0">
            <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove tier', 'byteflows-travel-hotel-booking' ); ?>"><span class="dashicons dashicons-trash"></span></button>
        </div></div>
    <?php endforeach; ?>
    </div>
    <button type="button" class="button button-primary wptm-add-item" data-target="pricing"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Tier', 'byteflows-travel-hotel-booking' ); ?></button>
</div>
