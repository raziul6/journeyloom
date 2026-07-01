<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
 if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-list-view"></span>
    <p><?php esc_html_e( 'Build the day-by-day plan. Drag items to reorder.', 'journeyloom' ); ?></p>
</div>

<?php if ( wptm_is_pro() && get_option( 'wptm_enable_ai' ) ) :
    // Prefill the AI inputs from the trip's existing data where possible.
    $ai_dest  = '';
    $dest_terms = isset( $post ) ? get_the_terms( $post->ID, 'wptm_destination' ) : false;
    if ( $dest_terms && ! is_wp_error( $dest_terms ) ) {
        $ai_dest = $dest_terms[0]->name;
    }
    if ( '' === $ai_dest && isset( $post ) ) {
        $ai_dest = get_the_title( $post );
    }
    $ai_days = isset( $fields['duration'] ) ? (int) $fields['duration'] : 0;
    ?>
    <div class="wptm-ai-itinerary">
        <div class="wptm-ai-itinerary__head">
            <span class="dashicons dashicons-superhero-alt"></span>
            <strong><?php esc_html_e( 'Generate with AI', 'journeyloom' ); ?></strong>
        </div>
        <div class="wptm-ai-itinerary__row">
            <input type="text" class="wptm-ai-dest" value="<?php echo esc_attr( $ai_dest ); ?>" placeholder="<?php esc_attr_e( 'Destination', 'journeyloom' ); ?>">
            <input type="number" class="wptm-ai-days" value="<?php echo esc_attr( $ai_days ?: 3 ); ?>" min="1" max="30" placeholder="<?php esc_attr_e( 'Days', 'journeyloom' ); ?>">
            <button type="button" class="button button-secondary wptm-ai-generate-itinerary">
                <span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'Generate', 'journeyloom' ); ?>
            </button>
        </div>
        <p class="wptm-ai-itinerary__note description"><?php esc_html_e( 'Generated days are appended below — review and edit before saving.', 'journeyloom' ); ?></p>
        <p class="wptm-ai-itinerary__status" style="display:none;"></p>
    </div>
<?php endif; ?>
<div id="wptm-itinerary-builder" class="wptm-repeater">
    <div class="wptm-repeater-items">
    <?php if ( ! empty( $itinerary ) ) : foreach ( $itinerary as $i => $day ) : ?>
        <div class="wptm-repeater-item" data-index="<?php echo esc_attr( $i ); ?>">
            <div class="wptm-repeater-header">
                <span class="dashicons dashicons-menu wptm-drag"></span>
                <span class="wptm-repeater-badge"><?php echo esc_html( $i + 1 ); ?></span>
                <strong><?php echo esc_html( $day['title'] ?? '' ) ?: sprintf( /* translators: %d: itinerary day number. */ esc_html__( 'Day %d', 'journeyloom' ), (int) ( $i + 1 ) ); ?></strong>
                <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove day', 'journeyloom' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="wptm-repeater-body">
                <input type="text" name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $day['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Day Title', 'journeyloom' ); ?>" class="widefat wptm-mb-spacer">
                <textarea name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][description]" rows="2" placeholder="<?php esc_attr_e( 'Description', 'journeyloom' ); ?>" class="widefat wptm-mb-spacer"><?php echo esc_textarea( $day['description'] ?? '' ); ?></textarea>
                <div class="wptm-inline">
                    <input type="text" name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][meals]" value="<?php echo esc_attr( $day['meals'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Meals', 'journeyloom' ); ?>" class="widefat">
                    <input type="text" name="wptm_itinerary[<?php echo esc_attr( $i ); ?>][accommodation]" value="<?php echo esc_attr( $day['accommodation'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Accommodation', 'journeyloom' ); ?>" class="widefat">
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
    <div class="wptm-empty-state"<?php echo ! empty( $itinerary ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php esc_html_e( 'No days added yet. Start building your itinerary.', 'journeyloom' ); ?></p>
    </div>
    <button type="button" class="button button-primary wptm-add-item" data-target="itinerary"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Day', 'journeyloom' ); ?></button>
</div>
