<?php
/**
 * Archive Template — JourneyLoom taxonomy terms.
 *
 * Shared by every WPTM taxonomy (destination, activity, trip type, difficulty,
 * hotel type, hotel facility). Shows the term name, description and optional
 * featured image, then lists matching trips/hotels with the right card partial.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$wptm_term  = get_queried_object();
$wptm_image = ( $wptm_term && isset( $wptm_term->term_id ) ) ? get_term_meta( $wptm_term->term_id, '_wptm_image', true ) : '';
$wptm_hero  = $wptm_image
    ? 'background:linear-gradient(135deg,rgba(30,27,75,.78),rgba(49,46,129,.78)),url(' . esc_url( $wptm_image ) . ') center/cover no-repeat;'
    : 'background:linear-gradient(135deg,#1e1b4b,#312e81);';
?>
<div class="wptm-archive-wrap">
    <div class="wptm-archive-hero" style="<?php echo esc_attr( $wptm_hero ); ?>padding:64px 20px;text-align:center;color:#fff;">
        <h1 style="font-family:var(--wptm-font-display);font-size:42px;font-weight:700;margin:0 0 12px;"><?php single_term_title(); ?></h1>
        <?php if ( $wptm_term && ! empty( $wptm_term->description ) ) : ?>
            <p style="font-size:18px;opacity:.85;max-width:680px;margin:0 auto;"><?php echo esc_html( wp_strip_all_tags( $wptm_term->description ) ); ?></p>
        <?php endif; ?>
    </div>

    <div style="max-width:1200px;margin:40px auto;padding:0 20px;">
        <div class="wptm-archive-toolbar" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <p style="color:#94a3b8;margin:0;"><?php /* translators: %d: number of results shown. */ printf( esc_html__( 'Showing %d results', 'byteflows-travel-hotel-booking' ), (int) $wp_query->found_posts ); ?></p>
            <select class="wptm-sort-select" onchange="location.href=this.value">
                <option><?php esc_html_e( 'Sort by', 'byteflows-travel-hotel-booking' ); ?></option>
                <option value="<?php echo esc_url( add_query_arg( 'orderby', 'date' ) ); ?>"><?php esc_html_e( 'Newest', 'byteflows-travel-hotel-booking' ); ?></option>
                <option value="<?php echo esc_url( add_query_arg( 'orderby', 'title' ) ); ?>"><?php esc_html_e( 'Name', 'byteflows-travel-hotel-booking' ); ?></option>
            </select>
        </div>

        <?php if ( have_posts() ) : ?>
            <div class="wptm-grid wptm-grid-3 wptm-search-results">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $wptm_card = ( 'wptm_hotel' === get_post_type() ) ? 'partials/hotel-card.php' : 'partials/trip-card.php';
                    include WPTM_PLUGIN_DIR . 'templates/' . $wptm_card;
                    ?>
                <?php endwhile; ?>
            </div>
            <div style="margin-top:40px;text-align:center;">
                <?php the_posts_pagination( array( 'mid_size' => 2, 'prev_text' => '← ' . __( 'Previous', 'byteflows-travel-hotel-booking' ), 'next_text' => __( 'Next', 'byteflows-travel-hotel-booking' ) . ' →' ) ); ?>
            </div>
        <?php else : ?>
            <p style="text-align:center;padding:60px 0;color:#94a3b8;font-size:18px;"><?php esc_html_e( 'Nothing found here yet. Please check back soon.', 'byteflows-travel-hotel-booking' ); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
