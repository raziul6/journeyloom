<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php if ( wptm_is_pro() && get_option( 'wptm_enable_ai' ) ) :
    $ai_dest = '';
    $dest_terms = isset( $post ) ? get_the_terms( $post->ID, 'wptm_destination' ) : false;
    if ( $dest_terms && ! is_wp_error( $dest_terms ) ) {
        $ai_dest = $dest_terms[0]->name;
    }
    if ( '' === $ai_dest && isset( $post ) ) {
        $ai_dest = get_the_title( $post );
    }
    $ai_days = isset( $fields['duration'] ) && (int) $fields['duration'] > 0 ? (int) $fields['duration'] : 5;
    ?>
    <div class="wptm-ai-builder" id="wptm-ai-builder">
        <div class="wptm-ai-builder__head">
            <span class="wptm-ai-builder__spark dashicons dashicons-superhero-alt"></span>
            <div>
                <strong><?php esc_html_e( 'AI Trip Builder', 'wp-travel-machine' ); ?></strong>
                <span><?php esc_html_e( 'Generate the full trip — description, highlights, itinerary, inclusions & FAQ — in one click.', 'wp-travel-machine' ); ?></span>
            </div>
        </div>
        <div class="wptm-ai-builder__grid">
            <label class="wptm-ai-field wptm-ai-field--wide">
                <span><?php esc_html_e( 'Destination', 'wp-travel-machine' ); ?></span>
                <input type="text" class="wptm-ai-dest" value="<?php echo esc_attr( $ai_dest ); ?>" placeholder="<?php esc_attr_e( 'e.g. Bali, Indonesia', 'wp-travel-machine' ); ?>">
            </label>
            <label class="wptm-ai-field">
                <span><?php esc_html_e( 'Days', 'wp-travel-machine' ); ?></span>
                <input type="number" class="wptm-ai-days" value="<?php echo esc_attr( $ai_days ); ?>" min="1" max="30">
            </label>
            <label class="wptm-ai-field">
                <span><?php esc_html_e( 'Style', 'wp-travel-machine' ); ?></span>
                <select class="wptm-ai-style">
                    <?php foreach ( array( 'adventure' => 'Adventure', 'cultural' => 'Cultural', 'luxury' => 'Luxury', 'budget' => 'Budget', 'family' => 'Family', 'beach' => 'Beach & Relax', 'honeymoon' => 'Honeymoon', 'wildlife' => 'Wildlife & Safari', 'trekking' => 'Trekking', 'roadtrip' => 'Road Trip' ) as $k => $l ) : ?>
                    <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="wptm-ai-field">
                <span><?php esc_html_e( 'Budget', 'wp-travel-machine' ); ?></span>
                <select class="wptm-ai-budget">
                    <option value="budget"><?php esc_html_e( 'Budget', 'wp-travel-machine' ); ?></option>
                    <option value="mid-range" selected><?php esc_html_e( 'Mid-range', 'wp-travel-machine' ); ?></option>
                    <option value="premium"><?php esc_html_e( 'Premium', 'wp-travel-machine' ); ?></option>
                    <option value="luxury"><?php esc_html_e( 'Luxury', 'wp-travel-machine' ); ?></option>
                </select>
            </label>
        </div>
        <div class="wptm-ai-builder__opts">
            <?php
            $ai_parts = array(
                'description' => __( 'Description', 'wp-travel-machine' ),
                'highlights'  => __( 'Highlights', 'wp-travel-machine' ),
                'itinerary'   => __( 'Itinerary', 'wp-travel-machine' ),
                'inclusions'  => __( 'Includes / Excludes', 'wp-travel-machine' ),
                'faq'         => __( 'FAQ', 'wp-travel-machine' ),
                'facts'       => __( 'Trip facts', 'wp-travel-machine' ),
            );
            foreach ( $ai_parts as $k => $label ) : ?>
                <label class="wptm-ai-chip"><input type="checkbox" class="wptm-ai-part" value="<?php echo esc_attr( $k ); ?>" checked> <?php echo esc_html( $label ); ?></label>
            <?php endforeach; ?>
            <label class="wptm-ai-chip wptm-ai-chip--danger"><input type="checkbox" class="wptm-ai-replace" checked> <?php esc_html_e( 'Replace existing', 'wp-travel-machine' ); ?></label>
        </div>
        <div class="wptm-ai-builder__foot">
            <button type="button" class="button button-primary wptm-ai-generate-trip">
                <span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Generate Full Trip', 'wp-travel-machine' ); ?>
            </button>
            <span class="wptm-ai-builder__status" aria-live="polite"></span>
        </div>
    </div>
<?php endif; ?>
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
