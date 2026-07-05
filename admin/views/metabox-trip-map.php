<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-location"></span>
    <p><?php esc_html_e( 'Where this trip takes place. Paste a map embed code for the best result, or just enter coordinates.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>
<div class="wptm-meta-grid">
    <div class="wptm-meta-field wptm-full">
        <label><?php esc_html_e( 'Map Embed Code (recommended)', 'byteflows-travel-hotel-booking' ); ?></label>
        <textarea name="wptm_map_embed" rows="3" class="widefat" placeholder="<?php esc_attr_e( '<iframe src=&quot;https://www.google.com/maps/embed?pb=...&quot; ...></iframe>', 'byteflows-travel-hotel-booking' ); ?>"><?php echo esc_textarea( $map_embed ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Google Maps → Share → "Embed a map" → copy HTML. Or OpenStreetMap → Share → HTML. Paste it here. (Coordinates below are used only if this is empty.)', 'byteflows-travel-hotel-booking' ); ?></p>
    </div>
    <div class="wptm-meta-field wptm-full">
        <label><?php esc_html_e( 'Address', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="text" name="wptm_address" value="<?php echo esc_attr( $addr ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Meeting point or main location', 'byteflows-travel-hotel-booking' ); ?>">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Latitude', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="text" name="wptm_latitude" value="<?php echo esc_attr( $lat ); ?>" class="widefat" placeholder="e.g. 27.7172">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Longitude', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="text" name="wptm_longitude" value="<?php echo esc_attr( $lng ); ?>" class="widefat" placeholder="e.g. 85.3240">
    </div>
</div>
