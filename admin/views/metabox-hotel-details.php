<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
 if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-info-outline"></span>
    <p><?php esc_html_e( 'Core hotel information shown across listings and booking pages.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>
<div class="wptm-meta-field" style="margin-bottom:16px;">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
        <input type="checkbox" name="wptm_featured" value="1" <?php checked( get_post_meta( $post->ID, '_wptm_featured', true ), 1 ); ?>>
        <strong><?php esc_html_e( 'Featured hotel', 'byteflows-travel-hotel-booking' ); ?></strong>
    </label>
    <p class="description"><?php esc_html_e( 'Show a “Featured” ribbon on this hotel’s card across listings.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>
<div class="wptm-meta-grid">
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Star Rating', 'byteflows-travel-hotel-booking' ); ?></label>
        <select name="wptm_star_rating"><?php for ( $i = 1; $i <= 5; $i++ ) : ?><option value="<?php echo esc_attr( $i ); ?>" <?php selected( $fields['star_rating'], $i ); ?>><?php echo esc_html( str_repeat( '⭐', $i ) ); ?></option><?php endfor; ?></select>
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Check-in Time', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="time" name="wptm_check_in_time" value="<?php echo esc_attr( $fields['check_in_time'] ); ?>">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Check-out Time', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="time" name="wptm_check_out_time" value="<?php echo esc_attr( $fields['check_out_time'] ); ?>">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Email', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="email" name="wptm_hotel_email" value="<?php echo esc_attr( $fields['contact_email'] ); ?>" class="widefat" placeholder="reservations@hotel.com">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Phone', 'byteflows-travel-hotel-booking' ); ?></label>
        <input type="text" name="wptm_hotel_phone" value="<?php echo esc_attr( $fields['contact_phone'] ); ?>" class="widefat" placeholder="+1 555 000 0000">
    </div>
    <div class="wptm-meta-field wptm-full">
        <p class="wptm-field-hint" style="margin:0;display:flex;align-items:center;gap:6px;">
            <span class="dashicons dashicons-yes-alt" style="color:#fd4621;"></span>
            <?php esc_html_e( 'Add the hotel’s facilities in the “Facilities” tab — they display with icons on the hotel page.', 'byteflows-travel-hotel-booking' ); ?>
        </p>
    </div>
</div>
