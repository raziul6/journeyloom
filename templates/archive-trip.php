<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Archive Template — Trips.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<div class="wptm-archive-wrap">
    <div class="wptm-archive-hero wptm-archive-hero--trip">
        <h1 class="wptm-archive-hero__title"><?php post_type_archive_title(); ?></h1>
        <p class="wptm-archive-hero__sub"><?php esc_html_e( 'Discover amazing travel experiences around the world.', 'journeyloom' ); ?></p>
    </div>

    <div class="wptm-archive-body">
        <?php $ptype = 'trip'; include WPTM_PLUGIN_DIR . 'templates/partials/filter-bar.php'; ?>

        <?php if ( have_posts() ) : ?>
            <div class="wptm-grid wptm-grid-3 wptm-search-results">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php include WPTM_PLUGIN_DIR . 'templates/partials/trip-card.php'; ?>
                <?php endwhile; ?>
            </div>
            <div class="wptm-pagination-wrap wptm-archive-pagination" data-type="trip" data-page="1" data-max="<?php echo (int) $wp_query->max_num_pages; ?>" data-total="<?php echo (int) $wp_query->found_posts; ?>">
                <?php the_posts_pagination( array( 'mid_size' => 2, 'prev_text' => '← ' . __( 'Previous', 'journeyloom' ), 'next_text' => __( 'Next', 'journeyloom' ) . ' →' ) ); ?>
            </div>
        <?php else : ?>
            <div class="wptm-grid wptm-grid-3 wptm-search-results">
                <p class="wptm-no-results"><?php esc_html_e( 'No trips found. Try adjusting your filters.', 'journeyloom' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
