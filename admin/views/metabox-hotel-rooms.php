<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
 if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-admin-home"></span>
    <p><?php esc_html_e( 'Add the room types guests can book. Drag to reorder.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>
<div id="wptm-rooms-manager" class="wptm-repeater">
    <div class="wptm-repeater-items">
    <?php if ( ! empty( $rooms ) ) : foreach ( $rooms as $i => $r ) : ?>
        <div class="wptm-repeater-item">
            <div class="wptm-repeater-header">
                <span class="dashicons dashicons-menu wptm-drag"></span>
                <span class="wptm-repeater-badge"><?php echo esc_html( $i + 1 ); ?></span>
                <strong><?php echo esc_html( $r['room_name'] ?: sprintf( /* translators: %d: room number. */ __( 'Room %d', 'byteflows-travel-hotel-booking' ), $i + 1 ) ); ?></strong>
                <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove room', 'byteflows-travel-hotel-booking' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="wptm-repeater-body wptm-meta-grid">
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Name', 'byteflows-travel-hotel-booking' ); ?></label><input type="text" name="wptm_rooms[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( $r['room_name'] ); ?>" class="widefat"></div>
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Type', 'byteflows-travel-hotel-booking' ); ?></label><select name="wptm_rooms[<?php echo esc_attr( $i ); ?>][type]"><?php foreach ( array( 'standard', 'deluxe', 'suite', 'family', 'presidential' ) as $t ) : ?><option value="<?php echo esc_attr( $t ); ?>" <?php selected( $r['room_type'], $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option><?php endforeach; ?></select></div>
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Price / Night', 'byteflows-travel-hotel-booking' ); ?></label><input type="number" name="wptm_rooms[<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( $r['price_per_night'] ); ?>" step="0.01" min="0" placeholder="0.00"></div>
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Sale Price', 'byteflows-travel-hotel-booking' ); ?></label><input type="number" name="wptm_rooms[<?php echo esc_attr( $i ); ?>][sale_price]" value="<?php echo esc_attr( $r['sale_price'] ); ?>" step="0.01" min="0" placeholder="0.00"></div>
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Max Guests', 'byteflows-travel-hotel-booking' ); ?></label><input type="number" name="wptm_rooms[<?php echo esc_attr( $i ); ?>][max_guests]" value="<?php echo esc_attr( $r['max_guests'] ); ?>" min="1"></div>
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Bed Type', 'byteflows-travel-hotel-booking' ); ?></label><input type="text" name="wptm_rooms[<?php echo esc_attr( $i ); ?>][bed_type]" value="<?php echo esc_attr( $r['bed_type'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. King', 'byteflows-travel-hotel-booking' ); ?>"></div>
                <div class="wptm-meta-field"><label><?php esc_html_e( 'Size', 'byteflows-travel-hotel-booking' ); ?></label><input type="text" name="wptm_rooms[<?php echo esc_attr( $i ); ?>][room_size]" value="<?php echo esc_attr( $r['room_size'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. 35 sqm', 'byteflows-travel-hotel-booking' ); ?>"></div>
                <div class="wptm-meta-field wptm-full"><label><?php esc_html_e( 'Description', 'byteflows-travel-hotel-booking' ); ?></label><textarea name="wptm_rooms[<?php echo esc_attr( $i ); ?>][description]" rows="2" class="widefat"><?php echo esc_textarea( $r['description'] ); ?></textarea></div>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
    <div class="wptm-empty-state"<?php echo ! empty( $rooms ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-admin-home"></span>
        <p><?php esc_html_e( 'No rooms added yet. Add your first room type.', 'byteflows-travel-hotel-booking' ); ?></p>
    </div>
    <button type="button" class="button button-primary wptm-add-item" data-target="room"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Room', 'byteflows-travel-hotel-booking' ); ?></button>
</div>
