<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Related items section.
 *
 * Shows other trips/hotels related to the current item by shared taxonomy
 * (destination / activity / trip type, or hotel type), falling back to recent
 * items of the same type. Controlled by the "Related Items" display setting.
 *
 * Expects (via wptm_get_template):
 *   - int    $item_id    Current trip/hotel ID.
 *   - string $item_type  'trip' or 'hotel'.
 *   - int    $count      Optional. How many to show.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! get_option( 'wptm_enable_related', 1 ) ) {
    return;
}

$rel_item_id   = isset( $item_id ) ? (int) $item_id : get_the_ID();
$rel_item_type = ( isset( $item_type ) && 'hotel' === $item_type ) ? 'hotel' : 'trip';
$rel_count     = isset( $count ) ? max( 1, (int) $count ) : max( 1, (int) get_option( 'wptm_related_count', 3 ) );
$rel_post_type = 'wptm_' . $rel_item_type;
$rel_card      = 'hotel' === $rel_item_type ? 'templates/partials/hotel-card.php' : 'templates/partials/trip-card.php';
$rel_taxes     = 'hotel' === $rel_item_type
    ? array( 'wptm_destination', 'wptm_hotel_type' )
    : array( 'wptm_destination', 'wptm_activity', 'wptm_trip_type' );

// Build an OR tax query from whatever terms the current item has.
$rel_tax_query = array( 'relation' => 'OR' );
foreach ( $rel_taxes as $rel_tx ) {
    $rel_terms = get_the_terms( $rel_item_id, $rel_tx );
    if ( $rel_terms && ! is_wp_error( $rel_terms ) ) {
        $rel_tax_query[] = array(
            'taxonomy' => $rel_tx,
            'field'    => 'term_id',
            'terms'    => wp_list_pluck( $rel_terms, 'term_id' ),
        );
    }
}

$rel_args = array(
    'post_type'           => $rel_post_type,
    'posts_per_page'      => $rel_count,
    'post__not_in'        => array( $rel_item_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- single related item excluded; tiny query.
    'post_status'         => 'publish',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
    'orderby'             => 'rand',
);
if ( count( $rel_tax_query ) > 1 ) {
    $rel_args['tax_query'] = $rel_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
}

$rel_q = new WP_Query( $rel_args );

// Fallback: nothing shares a taxonomy — show recent items of the same type.
if ( ! $rel_q->have_posts() ) {
    unset( $rel_args['tax_query'] );
    $rel_args['orderby'] = 'date';
    $rel_q = new WP_Query( $rel_args );
}

if ( $rel_q->have_posts() ) :
    /**
     * Filter the "Related items" heading.
     *
     * @param string $title     Section heading.
     * @param string $item_type 'trip' or 'hotel'.
     */
    $rel_title = apply_filters( 'wptm_related_title', __( 'You may also like', 'byteflows-travel-hotel-booking' ), $rel_item_type );
    ?>
    <section class="wptm-related">
        <div class="wptm-related__inner">
            <h2 class="wptm-section__title wptm-related__title"><?php echo esc_html( $rel_title ); ?></h2>
            <div class="wptm-grid wptm-grid-3">
                <?php
                while ( $rel_q->have_posts() ) :
                    $rel_q->the_post();
                    include WPTM_PLUGIN_DIR . $rel_card;
                endwhile;
                ?>
            </div>
        </div>
    </section>
    <?php
endif;
wp_reset_postdata();
