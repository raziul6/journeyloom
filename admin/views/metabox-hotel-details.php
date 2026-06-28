<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-info-outline"></span>
    <p><?php esc_html_e( 'Core hotel information shown across listings and booking pages.', 'wp-travel-machine' ); ?></p>
</div>
<div class="wptm-meta-grid">
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Star Rating', 'wp-travel-machine' ); ?></label>
        <select name="wptm_star_rating"><?php for ( $i = 1; $i <= 5; $i++ ) : ?><option value="<?php echo esc_attr( $i ); ?>" <?php selected( $fields['star_rating'], $i ); ?>><?php echo str_repeat( '⭐', $i ); ?></option><?php endfor; ?></select>
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Check-in Time', 'wp-travel-machine' ); ?></label>
        <input type="time" name="wptm_check_in_time" value="<?php echo esc_attr( $fields['check_in_time'] ); ?>">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Check-out Time', 'wp-travel-machine' ); ?></label>
        <input type="time" name="wptm_check_out_time" value="<?php echo esc_attr( $fields['check_out_time'] ); ?>">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Email', 'wp-travel-machine' ); ?></label>
        <input type="email" name="wptm_hotel_email" value="<?php echo esc_attr( $fields['contact_email'] ); ?>" class="widefat" placeholder="reservations@hotel.com">
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Phone', 'wp-travel-machine' ); ?></label>
        <input type="text" name="wptm_hotel_phone" value="<?php echo esc_attr( $fields['contact_phone'] ); ?>" class="widefat" placeholder="+1 555 000 0000">
    </div>
    <div class="wptm-meta-field wptm-full">
        <p class="wptm-field-hint" style="margin:0;display:flex;align-items:center;gap:6px;">
            <span class="dashicons dashicons-yes-alt" style="color:#fd4621;"></span>
            <?php esc_html_e( 'Add the hotel’s facilities in the “Facilities” tab — they display with icons on the hotel page.', 'wp-travel-machine' ); ?>
        </p>
    </div>
</div>
