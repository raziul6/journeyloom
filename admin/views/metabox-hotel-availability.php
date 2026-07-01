<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Hotel Availability manager.
 *
 * Add date ranges with a number of available rooms (or mark them blocked).
 * These rules drive the front-end date availability check. With no rules, the
 * hotel is treated as always available.
 *
 * @var array $availability  Rows from wptm_availability.
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$availability = isset( $availability ) && is_array( $availability ) ? $availability : array();
$sym          = get_option( 'wptm_currency_symbol', '$' );
?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-calendar-alt"></span>
    <p><?php esc_html_e( 'Control which dates are bookable. Add a period with the number of available rooms, or mark it as blocked (e.g. fully booked / closed). No rules = always available.', 'journeyloom' ); ?></p>
</div>

<div id="wptm-availability-builder" class="wptm-repeater">
    <input type="hidden" name="wptm_availability_present" value="1">

    <div class="wptm-availability-head">
        <span><?php esc_html_e( 'From', 'journeyloom' ); ?></span>
        <span><?php esc_html_e( 'To', 'journeyloom' ); ?></span>
        <span><?php esc_html_e( 'Rooms', 'journeyloom' ); ?></span>
        <span><?php esc_html_e( 'Status', 'journeyloom' ); ?></span>
        <span><?php /* translators: %s: currency symbol. */ printf( esc_html__( 'Price/night (%s)', 'journeyloom' ), esc_html( $sym ) ); ?></span>
        <span></span>
    </div>

    <div class="wptm-repeater-items">
        <?php foreach ( $availability as $i => $row ) : ?>
            <div class="wptm-repeater-item">
                <div class="wptm-availability-row">
                    <input type="date" name="wptm_availability[<?php echo esc_attr( $i ); ?>][date_start]" value="<?php echo esc_attr( $row['date_start'] ?? '' ); ?>">
                    <input type="date" name="wptm_availability[<?php echo esc_attr( $i ); ?>][date_end]" value="<?php echo esc_attr( $row['date_end'] ?? '' ); ?>">
                    <input type="number" min="0" name="wptm_availability[<?php echo esc_attr( $i ); ?>][spots]" value="<?php echo esc_attr( $row['available_spots'] ?? 1 ); ?>" placeholder="0">
                    <select name="wptm_availability[<?php echo esc_attr( $i ); ?>][status]">
                        <option value="available" <?php selected( $row['status'] ?? 'available', 'available' ); ?>><?php esc_html_e( 'Available', 'journeyloom' ); ?></option>
                        <option value="unavailable" <?php selected( $row['status'] ?? '', 'unavailable' ); ?>><?php esc_html_e( 'Blocked', 'journeyloom' ); ?></option>
                    </select>
                    <input type="number" min="0" step="0.01" name="wptm_availability[<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( $row['price_override'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Default', 'journeyloom' ); ?>">
                    <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove period', 'journeyloom' ); ?>"><span class="dashicons dashicons-trash"></span></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="wptm-empty-state"<?php echo ! empty( $availability ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php esc_html_e( 'No availability rules — this hotel shows as available on every date. Add a period to manage inventory.', 'journeyloom' ); ?></p>
    </div>

    <button type="button" class="button button-primary wptm-add-item" data-target="availability"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Availability Period', 'journeyloom' ); ?></button>
</div>
