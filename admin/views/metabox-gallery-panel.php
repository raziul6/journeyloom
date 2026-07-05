<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Gallery tab panel — manages images, a feature video and an audio track.
 *
 * Expected vars (set by the post-type render_data):
 * @var string $gallery       Comma-separated attachment IDs.
 * @var string $video_url      Feature video URL (YouTube / Vimeo / mp4).
 * @var string $audio_url      Audio track URL (mp3 / wav).
 * @var string $gallery_field  POST field name for the image IDs.
 * @var string $video_field    POST field name for the video URL.
 * @var string $audio_field    POST field name for the audio URL.
 * @var string $gallery_dom    Container id used by admin.js (wptm-gallery | wptm-hotel-gallery).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$gallery_dom = isset( $gallery_dom ) ? $gallery_dom : 'wptm-gallery';
$add_btn_id  = 'wptm-hotel-gallery' === $gallery_dom ? 'wptm-add-hotel-gallery' : 'wptm-add-gallery';
?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-format-gallery"></span>
    <p><?php esc_html_e( 'Images, a feature video and an audio track shown in the gallery at the top of the single page. The featured image appears first.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>

<div class="wptm-gallery-panel">

    <!-- Images -->
    <div class="wptm-gallery-block">
        <h4 class="wptm-gallery-block__title"><span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Gallery Images', 'byteflows-travel-hotel-booking' ); ?></h4>
        <div id="<?php echo esc_attr( $gallery_dom ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $gallery_field ); ?>" id="<?php echo esc_attr( $gallery_dom ); ?>_ids" value="<?php echo esc_attr( $gallery ); ?>">
            <div class="wptm-gallery-grid">
            <?php if ( $gallery ) : foreach ( explode( ',', $gallery ) as $id ) : $id = trim( $id ); if ( ! $id ) continue; $img = wp_get_attachment_image_src( $id, 'thumbnail' ); if ( $img ) : ?>
                <div class="wptm-gallery-item" data-id="<?php echo esc_attr( $id ); ?>"><img src="<?php echo esc_url( $img[0] ); ?>"><button type="button" class="wptm-remove-image" aria-label="<?php esc_attr_e( 'Remove image', 'byteflows-travel-hotel-booking' ); ?>">&times;</button></div>
            <?php endif; endforeach; endif; ?>
            </div>
            <button type="button" class="button button-primary wptm-add-item-btn" id="<?php echo esc_attr( $add_btn_id ); ?>"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Images', 'byteflows-travel-hotel-booking' ); ?></button>
            <p class="wptm-field-hint"><?php esc_html_e( 'Drag is not required — the first image follows the featured image on the front end.', 'byteflows-travel-hotel-booking' ); ?></p>
        </div>
    </div>

    <!-- Video -->
    <div class="wptm-gallery-block">
        <h4 class="wptm-gallery-block__title"><span class="dashicons dashicons-video-alt3"></span> <?php esc_html_e( 'Feature Video', 'byteflows-travel-hotel-booking' ); ?></h4>
        <div class="wptm-media-row">
            <input type="url" name="<?php echo esc_attr( $video_field ); ?>" id="<?php echo esc_attr( $gallery_dom ); ?>_video" value="<?php echo esc_attr( $video_url ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'YouTube / Vimeo link or .mp4 URL', 'byteflows-travel-hotel-booking' ); ?>">
            <button type="button" class="button wptm-media-picker" data-target="#<?php echo esc_attr( $gallery_dom ); ?>_video" data-type="video"><?php esc_html_e( 'Media Library', 'byteflows-travel-hotel-booking' ); ?></button>
        </div>
        <p class="wptm-field-hint"><?php esc_html_e( 'A “Play Video” button appears over the gallery. Leave empty to hide it.', 'byteflows-travel-hotel-booking' ); ?></p>
    </div>

    <!-- Audio -->
    <div class="wptm-gallery-block">
        <h4 class="wptm-gallery-block__title"><span class="dashicons dashicons-format-audio"></span> <?php esc_html_e( 'Audio Track', 'byteflows-travel-hotel-booking' ); ?></h4>
        <div class="wptm-media-row">
            <input type="url" name="<?php echo esc_attr( $audio_field ); ?>" id="<?php echo esc_attr( $gallery_dom ); ?>_audio" value="<?php echo esc_attr( $audio_url ); ?>" class="widefat" placeholder="<?php esc_attr_e( '.mp3 / .wav URL', 'byteflows-travel-hotel-booking' ); ?>">
            <button type="button" class="button wptm-media-picker" data-target="#<?php echo esc_attr( $gallery_dom ); ?>_audio" data-type="audio"><?php esc_html_e( 'Media Library', 'byteflows-travel-hotel-booking' ); ?></button>
        </div>
        <p class="wptm-field-hint"><?php esc_html_e( 'Optional. Shown as a compact player on the gallery.', 'byteflows-travel-hotel-booking' ); ?></p>
    </div>

</div>
