<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Trip Card Partial Template.
 *
 * @package JourneyLoom
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$sym      = get_option( 'wptm_currency_symbol', '$' );
$pricing  = get_post_meta( get_the_ID(), '_wptm_pricing', true );
$price    = is_array( $pricing ) && ! empty( $pricing ) ? $pricing[0]['price'] : 0;
$sale     = is_array( $pricing ) && ! empty( $pricing ) ? ( $pricing[0]['sale_price'] ?? 0 ) : 0;
$duration = get_post_meta( get_the_ID(), '_wptm_duration', true );
$unit     = get_post_meta( get_the_ID(), '_wptm_duration_unit', true ) ?: 'days';
$diff     = get_post_meta( get_the_ID(), '_wptm_difficulty', true );
$dests    = get_the_terms( get_the_ID(), 'wptm_destination' );
$dest     = ! is_wp_error( $dests ) && ! empty( $dests ) ? $dests[0]->name : '';
$thumb    = get_the_post_thumbnail_url( get_the_ID(), 'large' );
?>
<div class="wptm-trip-card" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
    <div class="wptm-trip-card__image">
        <a href="<?php the_permalink(); ?>">
            <?php if ( $thumb ) : ?>
                <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
            <?php else : ?>
                <div class="wptm-card-fallback wptm-card-fallback--trip"><?php echo wp_kses( wptm_icon( 'plane', array( 'size' => 44 ) ), wptm_svg_allowed() ); ?></div>
            <?php endif; ?>
        </a>
        <?php if ( $sale && $sale < $price ) : ?>
            <span class="wptm-trip-card__badge"><?php echo esc_html( round( ( ( $price - $sale ) / $price ) * 100 ) . '% OFF' ); ?></span>
        <?php endif; ?>
        <?php if ( get_post_meta( get_the_ID(), '_wptm_featured', true ) ) : ?>
            <span class="wptm-card-ribbon--featured"><?php echo wp_kses( wptm_icon( 'star', array( 'size' => 12, 'fill' => true, 'stroke' => 0 ) ), wptm_svg_allowed() ); ?> <?php esc_html_e( 'Featured', 'byteflows-travel-hotel-booking' ); ?></span>
        <?php endif; ?>
        <button class="wptm-trip-card__wishlist wptm-wishlist-btn" data-item-id="<?php echo esc_attr( get_the_ID() ); ?>" data-item-type="trip" aria-label="<?php esc_attr_e( 'Add to wishlist', 'byteflows-travel-hotel-booking' ); ?>"><?php echo wp_kses( wptm_icon( 'heart', array( 'size' => 18 ) ), wptm_svg_allowed() ); ?></button>
    </div>
    <div class="wptm-trip-card__body">
        <h3 class="wptm-trip-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="wptm-trip-card__meta">
            <?php if ( $dest ) : ?><span><?php echo wp_kses( wptm_icon( 'map-pin', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( $dest ); ?></span><?php endif; ?>
            <?php if ( $duration ) : ?><span><?php echo wp_kses( wptm_icon( 'clock', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( $duration . ' ' . $unit ); ?></span><?php endif; ?>
            <?php if ( $diff ) : ?><span><?php echo wp_kses( wptm_icon( 'mountain', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( ucfirst( $diff ) ); ?></span><?php endif; ?>
        </div>
        <div class="wptm-trip-card__footer">
            <div class="wptm-trip-card__price">
                <?php if ( $sale && $sale < $price ) : ?>
                    <span class="old-price"><?php echo esc_html( $sym . number_format( $price, 0 ) ); ?></span>
                    <span class="amount"><?php echo esc_html( $sym . number_format( $sale, 0 ) ); ?></span>
                <?php else : ?>
                    <span class="amount"><?php echo esc_html( $sym . number_format( $price, 0 ) ); ?></span>
                <?php endif; ?>
                <span class="per">/<?php esc_html_e( 'person', 'byteflows-travel-hotel-booking' ); ?></span>
            </div>
            <a href="<?php the_permalink(); ?>" class="wptm-btn wptm-btn--primary wptm-btn--sm"><?php esc_html_e( 'View Details', 'byteflows-travel-hotel-booking' ); ?></a>
        </div>
    </div>
</div>
