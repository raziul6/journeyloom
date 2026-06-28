<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-info-outline"></span>
    <p><?php esc_html_e( 'Key facts about this trip — shown in the summary box and search filters.', 'wp-travel-machine' ); ?></p>
</div>
<div class="wptm-meta-field" style="margin-bottom:16px;">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
        <input type="checkbox" name="wptm_featured" value="1" <?php checked( get_post_meta( $post->ID, '_wptm_featured', true ), 1 ); ?>>
        <strong><?php esc_html_e( 'Featured trip', 'wp-travel-machine' ); ?></strong>
    </label>
    <p class="description"><?php esc_html_e( 'Show a “Featured” ribbon on this trip’s card across listings.', 'wp-travel-machine' ); ?></p>
</div>
<div class="wptm-meta-grid">
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Duration', 'wp-travel-machine' ); ?></label>
        <div class="wptm-inline">
            <input type="number" name="wptm_duration" value="<?php echo esc_attr( $fields['duration'] ); ?>" min="1" placeholder="0">
            <select name="wptm_duration_unit">
                <?php foreach ( array( 'days', 'hours', 'nights' ) as $u ) : ?>
                <option value="<?php echo esc_attr( $u ); ?>" <?php selected( $fields['duration_unit'], $u ); ?>><?php echo esc_html( ucfirst( $u ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Group Size', 'wp-travel-machine' ); ?></label>
        <div class="wptm-inline">
            <input type="number" name="wptm_group_min" value="<?php echo esc_attr( $fields['group_min'] ); ?>" min="1" placeholder="<?php esc_attr_e( 'Min', 'wp-travel-machine' ); ?>">
            <span class="wptm-inline__sep">—</span>
            <input type="number" name="wptm_group_max" value="<?php echo esc_attr( $fields['group_max'] ); ?>" min="1" placeholder="<?php esc_attr_e( 'Max', 'wp-travel-machine' ); ?>">
        </div>
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Difficulty', 'wp-travel-machine' ); ?></label>
        <select name="wptm_difficulty">
            <?php foreach ( array( 'easy', 'moderate', 'challenging', 'difficult', 'extreme' ) as $l ) : ?>
            <option value="<?php echo esc_attr( $l ); ?>" <?php selected( $fields['difficulty'], $l ); ?>><?php echo esc_html( ucfirst( $l ) ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="wptm-meta-field">
        <label><?php esc_html_e( 'Minimum Age', 'wp-travel-machine' ); ?></label>
        <input type="number" name="wptm_min_age" value="<?php echo esc_attr( $fields['min_age'] ); ?>" min="0" placeholder="0">
    </div>
    <?php
    /**
     * Render a simple add/remove list repeater.
     *
     * @param string $field   POST field / repeater target (e.g. 'wptm_highlights').
     * @param array  $items   Existing string values.
     * @param string $ph      Input placeholder.
     * @param string $add     "Add" button label.
     */
    $wptm_list_repeater = function( $field, $items, $ph, $add ) {
        $target = str_replace( 'wptm_', '', $field ); // highlight / include / exclude
        ?>
        <div class="wptm-repeater wptm-list-repeater">
            <?php // Ensures the key is always submitted so removing all items clears it. ?>
            <input type="hidden" name="<?php echo esc_attr( $field ); ?>[]" value="">
            <div class="wptm-repeater-items">
                <?php foreach ( $items as $val ) : ?>
                <div class="wptm-repeater-item"><div class="wptm-list-row">
                    <input type="text" name="<?php echo esc_attr( $field ); ?>[]" value="<?php echo esc_attr( $val ); ?>" class="widefat" placeholder="<?php echo esc_attr( $ph ); ?>">
                    <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove', 'wp-travel-machine' ); ?>"><span class="dashicons dashicons-trash"></span></button>
                </div></div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button wptm-add-item" data-target="<?php echo esc_attr( $target ); ?>"><span class="dashicons dashicons-plus-alt2"></span> <?php echo esc_html( $add ); ?></button>
        </div>
        <?php
    };
    ?>
    <div class="wptm-meta-field wptm-full">
        <label><?php esc_html_e( 'Highlights', 'wp-travel-machine' ); ?></label>
        <?php $wptm_list_repeater( 'wptm_highlights', wptm_to_list( $fields['highlights'] ), __( 'e.g. Sunset over the valley', 'wp-travel-machine' ), __( 'Add Highlight', 'wp-travel-machine' ) ); ?>
    </div>
    <div class="wptm-meta-field wptm-full">
        <label><?php esc_html_e( "What's Included", 'wp-travel-machine' ); ?></label>
        <?php $wptm_list_repeater( 'wptm_includes', wptm_to_list( $fields['includes'] ), __( 'e.g. Airport transfers', 'wp-travel-machine' ), __( 'Add Item', 'wp-travel-machine' ) ); ?>
    </div>
    <div class="wptm-meta-field wptm-full">
        <label><?php esc_html_e( "What's Excluded", 'wp-travel-machine' ); ?></label>
        <?php $wptm_list_repeater( 'wptm_excludes', wptm_to_list( $fields['excludes'] ), __( 'e.g. International flights', 'wp-travel-machine' ), __( 'Add Item', 'wp-travel-machine' ) ); ?>
    </div>
</div>
