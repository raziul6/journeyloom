<?php
/**
 * Search Form Partial Template.
 *
 * Renders the advanced search form from the admin builder configuration
 * (\WPTravelMachine\Admin\SearchFormBuilder::get_enabled_fields()). Fields,
 * labels, placeholders, order and which controls show are all driven by that
 * saved config so the live form always matches the builder.
 *
 * All inputs are namespaced under wptm_search[...] so they never collide with
 * query vars registered by the theme or other plugins.
 *
 * @package WPTravelMachine
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$style  = isset( $style ) ? $style : 'horizontal';
$fields = \WPTravelMachine\Admin\SearchFormBuilder::get_enabled_fields();

if ( empty( $fields ) ) {
    return;
}

// Current submitted values (namespaced).
$wptm_req = ( isset( $_GET['wptm_search'] ) && is_array( $_GET['wptm_search'] ) ) ? wp_unslash( $_GET['wptm_search'] ) : array();
$wptm_val = function ( $key ) use ( $wptm_req ) {
    return isset( $wptm_req[ $key ] ) ? sanitize_text_field( $wptm_req[ $key ] ) : '';
};

/**
 * Render a <select> of taxonomy terms for a search field.
 */
$wptm_tax_select = function ( $key, $taxonomy, $placeholder, $current ) {
    $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
    echo '<select id="wptm-search-' . esc_attr( $key ) . '" name="wptm_search[' . esc_attr( $key ) . ']">';
    echo '<option value="">' . esc_html( $placeholder ) . '</option>';
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $current, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
        }
    }
    echo '</select>';
};
?>
<div class="wptm-search-form <?php echo esc_attr( $style ); ?>">
    <form class="wptm-search-fields" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'wptm_trip' ) ); ?>">
        <input type="hidden" name="post_type" value="wptm_trip">

        <?php foreach ( $fields as $key => $field ) :
            $label    = $field['label'];
            $ph       = $field['placeholder'];
            $required = ! empty( $field['required'] ) ? 'required' : '';
            ?>
            <div class="wptm-search-field wptm-search-field--<?php echo esc_attr( $key ); ?>">
                <label for="wptm-search-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>

                <?php if ( ! empty( $field['taxonomy'] ) ) : ?>
                    <?php $wptm_tax_select( $key, $field['taxonomy'], $ph ?: $label, $wptm_val( $key ) ); ?>

                <?php elseif ( 'keyword' === $key ) : ?>
                    <input type="text" id="wptm-search-keyword" name="wptm_search[keyword]" placeholder="<?php echo esc_attr( $ph ); ?>" value="<?php echo esc_attr( $wptm_val( 'keyword' ) ); ?>" <?php echo esc_attr( $required ); ?>>

                <?php elseif ( 'duration' === $key ) :
                    $dur = $wptm_val( 'duration' );
                    $buckets = array(
                        '1-3'  => __( '1 – 3 days', 'wp-travel-machine' ),
                        '4-7'  => __( '4 – 7 days', 'wp-travel-machine' ),
                        '8-14' => __( '8 – 14 days', 'wp-travel-machine' ),
                        '15-'  => __( '15+ days', 'wp-travel-machine' ),
                    ); ?>
                    <select id="wptm-search-duration" name="wptm_search[duration]">
                        <option value=""><?php echo esc_html( $ph ?: __( 'Any Duration', 'wp-travel-machine' ) ); ?></option>
                        <?php foreach ( $buckets as $val => $text ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $dur, $val ); ?>><?php echo esc_html( $text ); ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ( 'budget' === $key ) : ?>
                    <div class="wptm-search-range">
                        <input type="number" id="wptm-search-budget" name="wptm_search[min_price]" min="0" step="1" placeholder="<?php esc_attr_e( 'Min', 'wp-travel-machine' ); ?>" value="<?php echo esc_attr( $wptm_val( 'min_price' ) ); ?>">
                        <span class="wptm-search-range__sep">—</span>
                        <input type="number" name="wptm_search[max_price]" min="0" step="1" placeholder="<?php esc_attr_e( 'Max', 'wp-travel-machine' ); ?>" value="<?php echo esc_attr( $wptm_val( 'max_price' ) ); ?>">
                    </div>

                <?php elseif ( 'guests' === $key ) : ?>
                    <input type="number" id="wptm-search-guests" name="wptm_search[guests]" min="1" step="1" placeholder="<?php echo esc_attr( $ph ); ?>" value="<?php echo esc_attr( $wptm_val( 'guests' ) ); ?>" <?php echo esc_attr( $required ); ?>>

                <?php elseif ( 'date' === $key ) : ?>
                    <input type="date" id="wptm-search-date" name="wptm_search[date]" value="<?php echo esc_attr( $wptm_val( 'date' ) ); ?>" <?php echo esc_attr( $required ); ?>>

                <?php else : ?>
                    <input type="<?php echo esc_attr( $field['type'] ?: 'text' ); ?>" id="wptm-search-<?php echo esc_attr( $key ); ?>" name="wptm_search[<?php echo esc_attr( $key ); ?>]" placeholder="<?php echo esc_attr( $ph ); ?>" value="<?php echo esc_attr( $wptm_val( $key ) ); ?>" <?php echo esc_attr( $required ); ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="wptm-search-field wptm-search-field--submit">
            <label>&nbsp;</label>
            <button type="submit" class="wptm-btn wptm-btn--primary"><?php echo wptm_icon( 'search', array( 'size' => 16 ) ); ?> <?php esc_html_e( 'Search', 'wp-travel-machine' ); ?></button>
        </div>
    </form>
</div>
