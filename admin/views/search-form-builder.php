<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
if ( ! defined( 'ABSPATH' ) ) exit;
$fields = \JourneyLoom\Admin\SearchFormBuilder::get_fields();
$types  = \JourneyLoom\Admin\SearchFormBuilder::field_types();
?>
<div class="wrap wptm-admin-wrap">
    <div class="wptm-admin-header">
        <span class="dashicons dashicons-search"></span>
        <div>
            <h1><?php esc_html_e( 'Search Form Builder', 'byteflows-travel-hotel-booking' ); ?></h1>
            <p style="margin:4px 0 0;opacity:.8;font-size:13px;"><?php esc_html_e( 'Toggle fields on or off, rename them, and drag to reorder. Changes apply to the live search form.', 'byteflows-travel-hotel-booking' ); ?></p>
        </div>
        <span class="wptm-version"><?php echo esc_html( count( array_filter( $fields, function ( $f ) { return ! empty( $f['enabled'] ); } ) ) ); ?> <?php esc_html_e( 'active', 'byteflows-travel-hotel-booking' ); ?></span>
    </div>

    <form id="wptm-search-form-builder">
        <?php wp_nonce_field( 'wptm_admin_nonce', 'nonce' ); ?>

        <div class="wptm-sfb-grid" id="wptm-sortable-fields">
            <?php foreach ( $fields as $key => $field ) : ?>
            <div class="wptm-sfb-card<?php echo empty( $field['enabled'] ) ? ' is-off' : ''; ?>" data-field="<?php echo esc_attr( $key ); ?>" draggable="false">
                <div class="wptm-sfb-card__head">
                    <span class="wptm-sfb-drag dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'byteflows-travel-hotel-booking' ); ?>"></span>
                    <span class="wptm-sfb-icon" aria-hidden="true"><?php echo esc_html( $field['icon'] ); ?></span>
                    <span class="wptm-sfb-name">
                        <strong class="wptm-sfb-title"><?php echo esc_html( $field['label'] ); ?></strong>
                        <code><?php echo esc_html( $key ); ?></code>
                    </span>
                    <label class="wptm-switch" title="<?php esc_attr_e( 'Enable field', 'byteflows-travel-hotel-booking' ); ?>">
                        <input type="checkbox" class="wptm-sfb-enabled" name="fields[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $field['enabled'] ) ); ?>>
                        <span class="wptm-switch__track"><span class="wptm-switch__thumb"></span></span>
                    </label>
                </div>

                <div class="wptm-sfb-card__body">
                    <label class="wptm-sfb-control">
                        <span><?php esc_html_e( 'Label', 'byteflows-travel-hotel-booking' ); ?></span>
                        <input type="text" class="wptm-sfb-label" name="fields[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>">
                    </label>

                    <div class="wptm-sfb-row">
                        <label class="wptm-sfb-control">
                            <span><?php esc_html_e( 'Type', 'byteflows-travel-hotel-booking' ); ?></span>
                            <select name="fields[<?php echo esc_attr( $key ); ?>][type]"<?php echo $field['taxonomy'] ? ' disabled' : ''; ?>>
                                <?php foreach ( $types as $t ) : ?>
                                    <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $field['type'], $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ( $field['taxonomy'] ) : ?>
                                <input type="hidden" name="fields[<?php echo esc_attr( $key ); ?>][type]" value="<?php echo esc_attr( $field['type'] ); ?>">
                            <?php endif; ?>
                        </label>

                        <label class="wptm-sfb-control wptm-sfb-required">
                            <span><?php esc_html_e( 'Required', 'byteflows-travel-hotel-booking' ); ?></span>
                            <input type="checkbox" name="fields[<?php echo esc_attr( $key ); ?>][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?>>
                        </label>
                    </div>

                    <label class="wptm-sfb-control">
                        <span><?php esc_html_e( 'Placeholder', 'byteflows-travel-hotel-booking' ); ?></span>
                        <input type="text" class="wptm-sfb-ph" name="fields[<?php echo esc_attr( $key ); ?>][placeholder]" value="<?php echo esc_attr( $field['placeholder'] ); ?>">
                    </label>
                </div>

                <input type="hidden" class="wptm-sfb-order" name="fields[<?php echo esc_attr( $key ); ?>][order]" value="<?php echo esc_attr( $field['order'] ); ?>">
            </div>
            <?php endforeach; ?>
        </div>

        <div class="wptm-sfb-footer">
            <p class="wptm-sfb-shortcode">
                <span class="dashicons dashicons-shortcode"></span>
                <?php esc_html_e( 'Embed anywhere:', 'byteflows-travel-hotel-booking' ); ?> <code>[wptm_search_form]</code>
            </p>
            <button type="submit" class="button button-primary button-hero wptm-sfb-save"><?php esc_html_e( 'Save Search Form', 'byteflows-travel-hotel-booking' ); ?></button>
        </div>
    </form>
</div>
