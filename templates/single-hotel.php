<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).
/**
 * Single Hotel Template.
 *
 * Theme override: copy to `your-theme/journeyloom/single-hotel.php`.
 * Extend without copying via the wptm_* action hooks fired below.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$pid       = get_the_ID();
$sym       = get_option( 'wptm_currency_symbol', '$' );
$stars     = get_post_meta( $pid, '_wptm_star_rating', true ) ?: 0;
$address   = get_post_meta( $pid, '_wptm_hotel_address', true );
$city      = get_post_meta( $pid, '_wptm_hotel_city', true );
$country   = get_post_meta( $pid, '_wptm_hotel_country', true );
$hlat      = get_post_meta( $pid, '_wptm_hotel_lat', true );
$hlng      = get_post_meta( $pid, '_wptm_hotel_lng', true );
$amenities = get_post_meta( $pid, '_wptm_hotel_amenities', true );
$facility_groups = get_post_meta( $pid, '_wptm_hotel_facilities', true );
$facility_groups = is_array( $facility_groups ) ? $facility_groups : array();
$check_in  = get_post_meta( $pid, '_wptm_check_in_time', true ) ?: '14:00';
$check_out = get_post_meta( $pid, '_wptm_check_out_time', true ) ?: '11:00';
$email     = get_post_meta( $pid, '_wptm_hotel_email', true );
$phone     = get_post_meta( $pid, '_wptm_hotel_phone', true );
$gallery   = get_post_meta( $pid, '_wptm_hotel_gallery', true );
$video_url = get_post_meta( $pid, '_wptm_hotel_video_url', true );
$audio_url = get_post_meta( $pid, '_wptm_hotel_audio_url', true );
$thumb     = get_the_post_thumbnail_url( $pid, 'full' );

// Combined hero gallery: featured image first, then gallery images (de-duplicated).
$hero_image_ids = array();
if ( has_post_thumbnail( $pid ) ) {
    $hero_image_ids[] = get_post_thumbnail_id( $pid );
}
if ( $gallery ) {
    foreach ( array_filter( array_map( 'trim', explode( ',', $gallery ) ) ) as $gid ) {
        if ( ! in_array( $gid, $hero_image_ids ) ) {
            $hero_image_ids[] = $gid;
        }
    }
}
$hero_style = get_option( 'wptm_gallery_style', 'grid' ); // Settings → Display → Gallery Style.

global $wpdb;
$rooms = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}wptm_rooms WHERE hotel_id = %d AND status = 'available' ORDER BY price_per_night ASC",
    $pid
) );

/**
 * Fires at the very top of the single hotel template (after the header).
 *
 * @param int $pid Hotel post ID.
 */
do_action( 'wptm_before_single_hotel', $pid );
?>

<div class="wptm-single-hero<?php echo empty( $hero_image_ids ) ? ' wptm-single-hero--noimg' : ''; ?>">
    <?php
    wptm_get_template( 'partials/gallery-hero.php', array(
        'hero_image_ids' => $hero_image_ids,
        'hero_video'     => $video_url,
        'hero_audio'     => $audio_url,
        'hero_style'     => $hero_style,
    ) );
    ?>
    <div class="wptm-single-hero__overlay">
        <div class="wptm-single-hero__inner">
            <?php if ( $stars ) : ?><span class="wptm-hero-badge wptm-hero-badge--stars"><?php echo wp_kses( wptm_stars( $stars, 16 ), wptm_svg_allowed() ); ?></span><?php endif; ?>
            <h1 class="wptm-single-hero__title"><?php the_title(); ?></h1>
            <?php $loc = trim( $address . ', ' . $city . ', ' . $country, ', ' ); if ( $loc ) : ?>
            <div class="wptm-hero-meta"><span class="wptm-hero-chip"><?php echo wp_kses( wptm_icon( 'map-pin', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( $loc ); ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="wptm-single-content">
    <div class="wptm-single-main">
        <?php
        /**
         * Fires inside the main column, before the hotel content sections.
         *
         * @param int $pid Hotel post ID.
         */
        do_action( 'wptm_single_hotel_before_content', $pid );
        ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'About This Hotel', 'byteflows-travel-hotel-booking' ); ?></h2>
            <div class="wptm-prose"><?php the_content(); ?></div>
        </div>

        <!-- Quick Info -->
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Hotel Info', 'byteflows-travel-hotel-booking' ); ?></h2>
            <div class="wptm-facts">
                <div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wp_kses( wptm_icon( 'login', array( 'size' => 24 ) ), wptm_svg_allowed() ); ?></span><span class="wptm-fact__value"><?php echo esc_html( $check_in ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Check-in', 'byteflows-travel-hotel-booking' ); ?></span></div>
                <div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wp_kses( wptm_icon( 'logout', array( 'size' => 24 ) ), wptm_svg_allowed() ); ?></span><span class="wptm-fact__value"><?php echo esc_html( $check_out ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Check-out', 'byteflows-travel-hotel-booking' ); ?></span></div>
                <div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wp_kses( wptm_icon( 'star', array( 'size' => 24, 'fill' => true, 'stroke' => 0, 'class' => 'wptm-star' ) ), wptm_svg_allowed() ); ?></span><span class="wptm-fact__value"><?php echo intval( $stars ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Rating', 'byteflows-travel-hotel-booking' ); ?></span></div>
                <div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wp_kses( wptm_icon( 'bed', array( 'size' => 24 ) ), wptm_svg_allowed() ); ?></span><span class="wptm-fact__value"><?php echo count( $rooms ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Room Types', 'byteflows-travel-hotel-booking' ); ?></span></div>
            </div>
        </div>

        <!-- Facilities (grouped, with icons) -->
        <?php if ( ! empty( $facility_groups ) ) : ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Facilities', 'byteflows-travel-hotel-booking' ); ?></h2>
            <div class="wptm-facilities">
                <?php foreach ( $facility_groups as $group ) :
                    $g_items = isset( $group['items'] ) && is_array( $group['items'] ) ? $group['items'] : array();
                    if ( empty( $g_items ) ) continue; ?>
                    <div class="wptm-facility-group">
                        <?php if ( ! empty( $group['title'] ) ) : ?>
                            <h3 class="wptm-facility-group__title"><?php echo esc_html( $group['title'] ); ?></h3>
                        <?php endif; ?>
                        <div class="wptm-facility-group__items">
                            <?php foreach ( $g_items as $f_item ) : ?>
                                <span class="wptm-facility"><?php echo wp_kses( wptm_facility_icon( $f_item, 18 ), wptm_svg_allowed() ); ?><span><?php echo esc_html( $f_item ); ?></span></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ( $amenities ) : ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Amenities', 'byteflows-travel-hotel-booking' ); ?></h2>
            <div class="wptm-amenities">
                <?php foreach ( explode( ',', $amenities ) as $a ) : $a = trim( $a ); if ( $a ) : ?>
                    <span class="wptm-tag"><?php echo wp_kses( wptm_facility_icon( $a, 15 ), wptm_svg_allowed() ); ?> <?php echo esc_html( $a ); ?></span>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rooms -->
        <?php if ( ! empty( $rooms ) ) : ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Available Rooms', 'byteflows-travel-hotel-booking' ); ?></h2>
            <?php foreach ( $rooms as $room ) : ?>
            <div class="wptm-room-card">
                <div class="wptm-room-card__info">
                    <h4 class="wptm-room-card__name"><?php echo esc_html( $room->room_name ); ?></h4>
                    <?php if ( $room->description ) : ?><p class="wptm-room-card__desc"><?php echo esc_html( $room->description ); ?></p><?php endif; ?>
                    <div class="wptm-room-card__meta">
                        <?php if ( $room->bed_type ) : ?><span><?php echo wp_kses( wptm_icon( 'bed', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( $room->bed_type ); ?></span><?php endif; ?>
                        <span><?php echo wp_kses( wptm_icon( 'users', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php /* translators: %d: maximum number of guests the room sleeps. */ printf( esc_html__( 'Max %d guests', 'byteflows-travel-hotel-booking' ), (int) $room->max_guests ); ?></span>
                        <?php if ( $room->room_size ) : ?><span><?php echo wp_kses( wptm_icon( 'ruler', array( 'size' => 15 ) ), wptm_svg_allowed() ); ?> <?php echo esc_html( $room->room_size ); ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="wptm-room-card__price">
                    <?php if ( ! empty( $room->sale_price ) && $room->sale_price < $room->price_per_night ) : ?>
                        <span class="wptm-room-card__old"><?php echo esc_html( $sym . number_format( $room->price_per_night, 0 ) ); ?></span>
                        <span class="amount"><?php echo esc_html( $sym . number_format( $room->sale_price, 0 ) ); ?></span>
                    <?php else : ?>
                        <span class="amount"><?php echo esc_html( $sym . number_format( $room->price_per_night, 0 ) ); ?></span>
                    <?php endif; ?>
                    <span class="per"><?php esc_html_e( '/night', 'byteflows-travel-hotel-booking' ); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        /**
         * Fires inside the main column, after the rooms (before contact/reviews).
         *
         * @param int $pid Hotel post ID.
         */
        do_action( 'wptm_single_hotel_after_content', $pid );
        ?>

        <!-- Location -->
        <?php
        wptm_get_template( 'partials/location-map.php', array(
            'lat'     => $hlat,
            'lng'     => $hlng,
            'address' => trim( $address . ', ' . $city . ', ' . $country, ', ' ),
            'embed'   => get_post_meta( $pid, '_wptm_hotel_map_embed', true ),
            'label'   => get_the_title( $pid ),
        ) );
        ?>

        <!-- Contact -->
        <?php if ( $email || $phone ) : ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Contact', 'byteflows-travel-hotel-booking' ); ?></h2>
            <?php if ( $email ) : ?><p class="wptm-contact-line"><?php echo wp_kses( wptm_icon( 'mail', array( 'size' => 17 ) ), wptm_svg_allowed() ); ?> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p><?php endif; ?>
            <?php if ( $phone ) : ?><p class="wptm-contact-line"><?php echo wp_kses( wptm_icon( 'phone', array( 'size' => 17 ) ), wptm_svg_allowed() ); ?> <a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php wptm_get_template( 'partials/enquiry-form.php', array( 'post_id' => $pid ) ); ?>
    </div>

    <div class="wptm-single-sidebar">
        <?php
        /**
         * Fires at the top of the hotel sidebar (before the booking form).
         *
         * @param int $pid Hotel post ID.
         */
        do_action( 'wptm_single_hotel_before_sidebar', $pid );

        wptm_get_template( 'partials/booking-form.php', array( 'item_id' => $pid ) );

        /**
         * Fires at the bottom of the hotel sidebar (after the booking form).
         *
         * @param int $pid Hotel post ID.
         */
        do_action( 'wptm_single_hotel_after_sidebar', $pid );
        ?>
    </div>
</div>

<!-- Related Hotels -->
<?php wptm_get_template( 'partials/related-items.php', array( 'item_id' => $pid, 'item_type' => 'hotel' ) ); ?>

<?php
/**
 * Fires at the very bottom of the single hotel template (before the footer).
 *
 * @param int $pid Hotel post ID.
 */
do_action( 'wptm_after_single_hotel', $pid );

get_footer();

