<?php
/**
 * Trip → Pickup Points panel (Pro).
 *
 * @var array $pickups Saved pickup points: [ ['label'=>, 'price'=>], ... ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sym = get_option( 'wptm_currency_symbol', '$' );
?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-location-alt"></span>
    <p><?php esc_html_e( 'Offer pickup locations for this trip. Leave the price at 0 for a free pickup, or set an amount to charge for a premium pickup. Customers choose a pickup per traveler at checkout.', 'wp-travel-machine' ); ?></p>
</div>

<div id="wptm-pickup-builder" class="wptm-repeater">
    <?php // Presence flag so removing every row clears the saved list. ?>
    <input type="hidden" name="wptm_pickups_present" value="1">
    <div class="wptm-repeater-items">
    <?php foreach ( $pickups as $i => $row ) : ?>
        <div class="wptm-repeater-item">
            <div class="wptm-pickup-row">
                <input type="text" name="wptm_pickups[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Pickup location / label (e.g. City Centre Hotel)', 'wp-travel-machine' ); ?>">
                <div class="wptm-pickup-price">
                    <span class="wptm-pickup-sym"><?php echo esc_html( $sym ); ?></span>
                    <input type="number" name="wptm_pickups[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( $row['price'] ?? '' ); ?>" step="0.01" min="0" placeholder="0.00">
                </div>
                <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove pickup', 'wp-travel-machine' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="wptm-empty-state"<?php echo ! empty( $pickups ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-location-alt"></span>
        <p><?php esc_html_e( 'No pickup points yet. Add a free or paid pickup location.', 'wp-travel-machine' ); ?></p>
    </div>
    <button type="button" class="button button-primary wptm-add-item" data-target="pickup"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Pickup Point', 'wp-travel-machine' ); ?></button>
</div>
