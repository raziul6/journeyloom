<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
 if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-list-view"></span>
    <p><?php esc_html_e( 'Build the day-by-day plan. Drag items to reorder.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>

<?php // The "Generate with AI" itinerary tool is added by the Pro add-on. ?>
<div id="wptm-itinerary-builder" class="wptm-repeater">
    <div class="wptm-repeater-items">
    <?php if ( ! empty( $itinerary ) ) : foreach ( $itinerary as $i => $day ) : ?>
        <div class="wptm-repeater-item" data-index="<?php echo esc_attr( $i ); ?>">
            <div class="wptm-repeater-header">
                <span class="dashicons dashicons-menu wptm-drag"></span>
                <span class="wptm-repeater-badge"><?php echo esc_html( $i + 1 ); ?></span>
                <strong><?php echo esc_html( $day['title'] ?? '' ) ?: sprintf( /* translators: %d: itinerary day number. */ esc_html__( 'Day %d', 'byteflows-travel-hotel-booking' ), (int) ( $i + 1 ) ); ?></strong>
                <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove day', 'byteflows-travel-hotel-booking' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="wptm-repeater-body">
                <input type="text" name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $day['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Day Title', 'byteflows-travel-hotel-booking' ); ?>" class="widefat wptm-mb-spacer">
                <textarea name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][description]" rows="2" placeholder="<?php esc_attr_e( 'Description', 'byteflows-travel-hotel-booking' ); ?>" class="widefat wptm-mb-spacer"><?php echo esc_textarea( $day['description'] ?? '' ); ?></textarea>
                <div class="wptm-inline">
                    <input type="text" name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][meals]" value="<?php echo esc_attr( $day['meals'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Meals', 'byteflows-travel-hotel-booking' ); ?>" class="widefat">
                    <input type="text" name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][accommodation]" value="<?php echo esc_attr( $day['accommodation'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Accommodation', 'byteflows-travel-hotel-booking' ); ?>" class="widefat">
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
    <div class="wptm-empty-state"<?php echo ! empty( $itinerary ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php esc_html_e( 'No days added yet. Start building your itinerary.', 'byteflows-travel-hotel-booking' ); ?></p>
    </div>
    <button type="button" class="button button-primary wptm-add-item" data-target="itinerary"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Day', 'byteflows-travel-hotel-booking' ); ?></button>
</div>
