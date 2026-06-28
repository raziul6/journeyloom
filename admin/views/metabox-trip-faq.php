<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-editor-help"></span>
    <p><?php esc_html_e( 'Frequently asked questions, shown as an accordion on the trip page.', 'wp-travel-machine' ); ?></p>
</div>
<div id="wptm-faq-builder" class="wptm-repeater">
    <?php // Presence flag so removing every FAQ clears the saved list. ?>
    <input type="hidden" name="wptm_faq_present" value="1">
    <div class="wptm-repeater-items">
    <?php foreach ( $faq as $i => $row ) : ?>
        <div class="wptm-repeater-item" data-index="<?php echo esc_attr( $i ); ?>">
            <div class="wptm-repeater-header">
                <span class="dashicons dashicons-menu wptm-drag"></span>
                <span class="wptm-repeater-badge"><?php echo esc_html( $i + 1 ); ?></span>
                <strong><?php echo esc_html( $row['question'] ?? '' ) ?: sprintf( esc_html__( 'FAQ %d', 'wp-travel-machine' ), $i + 1 ); ?></strong>
                <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove FAQ', 'wp-travel-machine' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="wptm-repeater-body">
                <input type="text" name="wptm_faq[<?php echo esc_attr( $i ); ?>][question]" value="<?php echo esc_attr( $row['question'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Question', 'wp-travel-machine' ); ?>" class="widefat wptm-mb-spacer">
                <textarea name="wptm_faq[<?php echo esc_attr( $i ); ?>][answer]" rows="2" placeholder="<?php esc_attr_e( 'Answer', 'wp-travel-machine' ); ?>" class="widefat"><?php echo esc_textarea( $row['answer'] ?? '' ); ?></textarea>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="wptm-empty-state"<?php echo ! empty( $faq ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-editor-help"></span>
        <p><?php esc_html_e( 'No FAQs yet. Add common questions travellers ask.', 'wp-travel-machine' ); ?></p>
    </div>
    <button type="button" class="button button-primary wptm-add-item" data-target="faq"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add FAQ', 'wp-travel-machine' ); ?></button>
</div>
