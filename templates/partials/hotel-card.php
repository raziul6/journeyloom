<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).
/**
 * Hotel Card Partial Template.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$sym    = get_option( 'wptm_currency_symbol', '$' );
$stars  = get_post_meta( get_the_ID(), '_wptm_star_rating', true ) ?: 0;
$city   = get_post_meta( get_the_ID(), '_wptm_hotel_city', true );
$country = get_post_meta( get_the_ID(), '_wptm_hotel_country', true );
$thumb  = get_the_post_thumbnail_url( get_the_ID(), 'large' );

global $wpdb;
$min_price = $wpdb->get_var( $wpdb->prepare(
    "SELECT MIN(price_per_night) FROM {$wpdb->prefix}wptm_rooms WHERE hotel_id = %d AND status = 'available'",
    get_the_ID()
) );
?>
<div class="wptm-hotel-card" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
    <div class="wptm-hotel-card__image">
        <a href="<?php the_permalink(); ?>">
            <?php if ( $thumb ) : ?>
                <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
            <?php else : ?>
                <div class="wptm-card-fallback wptm-card-fallback--hotel"><?php echo wp_kses( wptm_icon( 'building', array( 'size' => 44 ) ), wptm_svg_allowed() ); ?></div>
            <?php endif; ?>
        </a>
        <span class="wptm-hotel-card__stars"><?php echo wp_kses( wptm_stars( $stars, 14 ), wptm_svg_allowed() ); ?></span>
        <?php if ( get_post_meta( get_the_ID(), '_wptm_featured', true ) ) : ?>
            <span class="wptm-card-ribbon--featured"><?php echo wp_kses( wptm_icon( 'star', array( 'size' => 12, 'fill' => true, 'stroke' => 0 ) ), wptm_svg_allowed() ); ?> <?php esc_html_e( 'Featured', 'byteflows-travel-hotel-booking' ); ?></span>
        <?php endif; ?>
        <button class="wptm-hotel-card__wishlist wptm-wishlist-btn" data-item-id="<?php echo esc_attr( get_the_ID() ); ?>" data-item-type="hotel" aria-label="<?php esc_attr_e( 'Add to wishlist', 'byteflows-travel-hotel-booking' ); ?>"><?php echo wp_kses( wptm_icon( 'heart', array( 'size' => 18 ) ), wptm_svg_allowed() ); ?></button>
    </div>
    <div class="wptm-hotel-card__body">
        <h3 class="wptm-hotel-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="wptm-hotel-card__location"><?php echo wp_kses( wptm_icon( 'map-pin', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( trim( $city . ', ' . $country, ', ' ) ); ?></div>
        <?php if ( has_excerpt() ) : ?>
            <p style="font-size:13px;color:#94a3b8;margin:0 0 12px;line-height:1.5;"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?></p>
        <?php endif; ?>
        <div class="wptm-hotel-card__footer">
            <div class="wptm-hotel-card__price">
                <?php if ( $min_price ) : ?>
                    <span class="amount"><?php echo esc_html( $sym . number_format( $min_price, 0 ) ); ?></span>
                    <span class="per">/<?php esc_html_e( 'night', 'byteflows-travel-hotel-booking' ); ?></span>
                <?php else : ?>
                    <span class="amount"><?php esc_html_e( 'Contact', 'byteflows-travel-hotel-booking' ); ?></span>
                <?php endif; ?>
            </div>
            <a href="<?php the_permalink(); ?>" class="wptm-btn wptm-btn--primary wptm-btn--sm"><?php esc_html_e( 'View Details', 'byteflows-travel-hotel-booking' ); ?></a>
        </div>
    </div>
</div>
