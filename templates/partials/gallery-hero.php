<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Hero Gallery partial — combined featured image + gallery, rendered in the
 * style chosen under Settings → Display → Gallery Style. Sits inside
 * .wptm-single-hero (behind the title overlay), with optional video/audio.
 *
 * @var int[]  $hero_image_ids Attachment IDs (featured image first).
 * @var string $hero_video      Feature video URL (optional).
 * @var string $hero_audio      Audio track URL (optional).
 * @var string $hero_style      grid | masonry | carousel | mosaic.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$hero_image_ids = isset( $hero_image_ids ) && is_array( $hero_image_ids ) ? array_values( array_filter( $hero_image_ids ) ) : array();
$hero_video     = isset( $hero_video ) ? trim( $hero_video ) : '';
$hero_audio     = isset( $hero_audio ) ? trim( $hero_audio ) : '';
$hero_style     = isset( $hero_style ) && in_array( $hero_style, array( 'grid', 'masonry', 'carousel', 'mosaic' ), true ) ? $hero_style : 'carousel';
$slide_count    = count( $hero_image_ids );
$is_slider      = ( 'carousel' === $hero_style );

// How many tiles a collage layout shows before the rest are hidden — used to
// place the "+N more" overlay on the last visible tile.
$collage_visible = array( 'grid' => 6, 'mosaic' => 5 );
$visible_limit   = $collage_visible[ $hero_style ] ?? $slide_count;
$more_index      = max( 0, $visible_limit - 1 );
$hidden_count    = max( 0, $slide_count - $visible_limit );
?>
<div class="wptm-hero-gallery__media wptm-hero-gallery--<?php echo esc_attr( $hero_style ); ?>" data-hero-gallery>
    <?php if ( $slide_count ) : ?>

        <?php if ( $is_slider ) : ?>
        <div class="wptm-hero-gallery__track">
            <?php foreach ( $hero_image_ids as $i => $img_id ) :
                $src = wp_get_attachment_image_url( $img_id, 'full' );
                if ( ! $src ) continue;
                ?>
            <figure class="wptm-hero-gallery__slide<?php echo 0 === $i ? ' is-active' : ''; ?>">
                <img class="wptm-lightbox-trigger" src="<?php echo esc_url( $src ); ?>" data-full="<?php echo esc_url( $src ); ?>" alt="" loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>">
            </figure>
            <?php endforeach; ?>
        </div>

            <?php if ( $slide_count > 1 ) : ?>
            <button type="button" class="wptm-hero-gallery__nav wptm-hero-gallery__nav--prev" aria-label="<?php esc_attr_e( 'Previous image', 'journeyloom' ); ?>">&#8249;</button>
            <button type="button" class="wptm-hero-gallery__nav wptm-hero-gallery__nav--next" aria-label="<?php esc_attr_e( 'Next image', 'journeyloom' ); ?>">&#8250;</button>
            <div class="wptm-hero-gallery__count"><span class="wptm-hg-current">1</span> / <?php echo (int) $slide_count; ?></div>
            <div class="wptm-hero-gallery__dots">
                <?php for ( $d = 0; $d < $slide_count; $d++ ) : ?>
                <button type="button" class="wptm-hg-dot<?php echo 0 === $d ? ' is-active' : ''; ?>" data-index="<?php echo (int) $d; ?>" aria-label="<?php /* translators: %d: image number in the gallery. */ printf( esc_attr__( 'Go to image %d', 'journeyloom' ), (int) ( $d + 1 ) ); ?>"></button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        <?php else : /* grid / masonry / mosaic collage */ ?>
        <div class="wptm-hero-gallery__collage">
            <?php foreach ( $hero_image_ids as $i => $img_id ) :
                $src  = wp_get_attachment_image_url( $img_id, 0 === $i ? 'full' : 'large' );
                $full = wp_get_attachment_image_url( $img_id, 'full' );
                if ( ! $src ) continue;
                $is_more_tile = ( $hidden_count > 0 && $i === $more_index );
                ?>
            <figure class="wptm-hero-gallery__cell<?php echo $is_more_tile ? ' wptm-hero-gallery__cell--more' : ''; ?>">
                <img class="wptm-lightbox-trigger" src="<?php echo esc_url( $src ); ?>" data-full="<?php echo esc_url( $full ); ?>" alt="" loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>">
                <?php if ( $is_more_tile ) : ?>
                <button type="button" class="wptm-hero-gallery__more" data-gallery-open aria-label="<?php esc_attr_e( 'View all photos', 'journeyloom' ); ?>">+<?php echo (int) $hidden_count; ?></button>
                <?php endif; ?>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <?php
    // Video, audio and "Show all photos" share one control bar in the
    // bottom-right corner so they line up with a single, consistent style.
    $wptm_show_viewall = ( $slide_count > 1 );
    if ( $hero_video || $hero_audio || $wptm_show_viewall ) : ?>
    <div class="wptm-hero-gallery__bar">
        <?php if ( $hero_video ) : ?>
        <button type="button" class="wptm-hero-media-btn wptm-hero-video-btn" data-video="<?php echo esc_url( $hero_video ); ?>">
            <span class="wptm-hero-media-btn__icon">&#9654;</span> <?php esc_html_e( 'Play Video', 'journeyloom' ); ?>
        </button>
        <?php endif; ?>
        <?php if ( $hero_audio ) : ?>
        <button type="button" class="wptm-hero-media-btn wptm-hero-audio-btn" aria-pressed="false">
            <span class="wptm-hero-media-btn__icon">&#9834;</span> <span class="wptm-hero-audio-label"><?php esc_html_e( 'Play Audio', 'journeyloom' ); ?></span>
        </button>
        <audio class="wptm-hero-audio" src="<?php echo esc_url( $hero_audio ); ?>" preload="none" loop></audio>
        <?php endif; ?>
        <?php if ( $wptm_show_viewall ) : ?>
        <button type="button" class="wptm-hero-media-btn wptm-hero-gallery__viewall" data-gallery-open>
            <span class="wptm-hero-media-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            </span>
            <?php
            /* translators: %d: total number of photos */
            printf( esc_html__( 'Show all %d photos', 'journeyloom' ), (int) $slide_count );
            ?>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
