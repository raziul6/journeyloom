<?php
/**
 * Hotel Card Partial Template.
 *
 * @package WPTravelMachine
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
                <div class="wptm-card-fallback wptm-card-fallback--hotel"><?php echo wptm_icon( 'building', array( 'size' => 44 ) ); ?></div>
            <?php endif; ?>
        </a>
        <span class="wptm-hotel-card__stars"><?php echo wptm_stars( $stars, 14 ); ?></span>
        <button class="wptm-hotel-card__wishlist wptm-wishlist-btn" data-item-id="<?php echo esc_attr( get_the_ID() ); ?>" data-item-type="hotel" aria-label="<?php esc_attr_e( 'Add to wishlist', 'wp-travel-machine' ); ?>"><?php echo wptm_icon( 'heart', array( 'size' => 18 ) ); ?></button>
    </div>
    <div class="wptm-hotel-card__body">
        <h3 class="wptm-hotel-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="wptm-hotel-card__location"><?php echo wptm_icon( 'map-pin', array( 'size' => 15 ) ); ?> <?php echo esc_html( trim( $city . ', ' . $country, ', ' ) ); ?></div>
        <?php if ( has_excerpt() ) : ?>
            <p style="font-size:13px;color:#94a3b8;margin:0 0 12px;line-height:1.5;"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?></p>
        <?php endif; ?>
        <div class="wptm-hotel-card__footer">
            <div class="wptm-hotel-card__price">
                <?php if ( $min_price ) : ?>
                    <span class="amount"><?php echo esc_html( $sym . number_format( $min_price, 0 ) ); ?></span>
                    <span class="per">/<?php esc_html_e( 'night', 'wp-travel-machine' ); ?></span>
                <?php else : ?>
                    <span class="amount"><?php esc_html_e( 'Contact', 'wp-travel-machine' ); ?></span>
                <?php endif; ?>
            </div>
            <a href="<?php the_permalink(); ?>" class="wptm-btn wptm-btn--primary wptm-btn--sm"><?php esc_html_e( 'View Details', 'wp-travel-machine' ); ?></a>
        </div>
    </div>
</div>
