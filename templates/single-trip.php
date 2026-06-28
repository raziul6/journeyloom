<?php
/**
 * Single Trip Template.
 *
 * Theme override: copy to `your-theme/wp-travel-machine/single-trip.php`.
 * Extend without copying via the wptm_* action hooks fired below.
 *
 * @package WPTravelMachine
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$sym       = get_option( 'wptm_currency_symbol', '$' );
$pid       = get_the_ID();
$pricing   = get_post_meta( $pid, '_wptm_pricing', true );
$price     = is_array( $pricing ) && ! empty( $pricing ) ? $pricing[0]['price'] : 0;
$sale      = is_array( $pricing ) && ! empty( $pricing ) ? ( $pricing[0]['sale_price'] ?? 0 ) : 0;
$duration  = get_post_meta( $pid, '_wptm_duration', true );
$unit      = get_post_meta( $pid, '_wptm_duration_unit', true ) ?: 'days';
$diff      = get_post_meta( $pid, '_wptm_difficulty', true );
$min_age   = get_post_meta( $pid, '_wptm_min_age', true );
$group_max = get_post_meta( $pid, '_wptm_group_max', true );
$highlights= get_post_meta( $pid, '_wptm_highlights', true );
$includes  = get_post_meta( $pid, '_wptm_includes', true );
$excludes  = get_post_meta( $pid, '_wptm_excludes', true );
$lat       = get_post_meta( $pid, '_wptm_latitude', true );
$lng       = get_post_meta( $pid, '_wptm_longitude', true );
$addr      = get_post_meta( $pid, '_wptm_address', true );
$itinerary = get_post_meta( $pid, '_wptm_itinerary', true );
$faq       = get_post_meta( $pid, '_wptm_faq', true );
$gallery   = get_post_meta( $pid, '_wptm_gallery', true );
$video_url = get_post_meta( $pid, '_wptm_video_url', true );
$audio_url = get_post_meta( $pid, '_wptm_audio_url', true );
$dests     = get_the_terms( $pid, 'wptm_destination' );
$dest      = ! is_wp_error( $dests ) && ! empty( $dests ) ? $dests[0]->name : '';
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

/**
 * Fires at the very top of the single trip template (after the header).
 *
 * @param int $pid Trip post ID.
 */
do_action( 'wptm_before_single_trip', $pid );
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
            <?php if ( $dest ) : ?><span class="wptm-hero-badge"><?php echo wptm_icon( 'map-pin', array( 'size' => 15 ) ); ?> <?php echo esc_html( $dest ); ?></span><?php endif; ?>
            <h1 class="wptm-single-hero__title"><?php the_title(); ?></h1>
            <div class="wptm-hero-meta">
                <?php if ( $duration ) : ?><span class="wptm-hero-chip"><?php echo wptm_icon( 'clock', array( 'size' => 15 ) ); ?> <?php echo esc_html( $duration . ' ' . $unit ); ?></span><?php endif; ?>
                <?php if ( $diff ) : ?><span class="wptm-hero-chip"><?php echo wptm_icon( 'mountain', array( 'size' => 15 ) ); ?> <?php echo esc_html( ucfirst( $diff ) ); ?></span><?php endif; ?>
                <?php if ( $group_max ) : ?><span class="wptm-hero-chip"><?php echo wptm_icon( 'users', array( 'size' => 15 ) ); ?> <?php printf( esc_html__( 'Up to %d', 'wp-travel-machine' ), (int) $group_max ); ?></span><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="wptm-single-content">
    <div class="wptm-single-main">
        <?php
        /**
         * Fires inside the main column, before the trip content sections.
         *
         * @param int $pid Trip post ID.
         */
        do_action( 'wptm_single_trip_before_content', $pid );
        ?>
        <!-- Overview -->
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Overview', 'wp-travel-machine' ); ?></h2>
            <div class="wptm-prose"><?php the_content(); ?></div>
        </div>

        <!-- Quick Facts -->
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Quick Facts', 'wp-travel-machine' ); ?></h2>
            <div class="wptm-facts">
                <?php if ( $duration ) : ?><div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wptm_icon( 'clock', array( 'size' => 24 ) ); ?></span><span class="wptm-fact__value"><?php echo esc_html( $duration . ' ' . $unit ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Duration', 'wp-travel-machine' ); ?></span></div><?php endif; ?>
                <?php if ( $group_max ) : ?><div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wptm_icon( 'users', array( 'size' => 24 ) ); ?></span><span class="wptm-fact__value"><?php echo esc_html( $group_max ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Max Group', 'wp-travel-machine' ); ?></span></div><?php endif; ?>
                <?php if ( $diff ) : ?><div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wptm_icon( 'mountain', array( 'size' => 24 ) ); ?></span><span class="wptm-fact__value"><?php echo esc_html( ucfirst( $diff ) ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Difficulty', 'wp-travel-machine' ); ?></span></div><?php endif; ?>
                <?php if ( $min_age ) : ?><div class="wptm-fact"><span class="wptm-fact__icon"><?php echo wptm_icon( 'cake', array( 'size' => 24 ) ); ?></span><span class="wptm-fact__value"><?php echo esc_html( $min_age . '+' ); ?></span><span class="wptm-fact__label"><?php esc_html_e( 'Min Age', 'wp-travel-machine' ); ?></span></div><?php endif; ?>
            </div>
        </div>

        <!-- Highlights -->
        <?php $hl_lines = wptm_to_list( $highlights ); if ( ! empty( $hl_lines ) ) : ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Highlights', 'wp-travel-machine' ); ?></h2>
            <ul class="wptm-checklist wptm-checklist--highlight">
                <?php foreach ( $hl_lines as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php
        /**
         * Fires after the Highlights section, before the itinerary.
         *
         * @param int $pid Trip post ID.
         */
        do_action( 'wptm_single_trip_after_overview', $pid );
        ?>

        <!-- Itinerary -->
        <?php if ( is_array( $itinerary ) && ! empty( $itinerary ) ) : ?>
        <div class="wptm-section">
            <div class="wptm-itinerary__bar">
                <h2 class="wptm-section__title"><?php esc_html_e( 'Day-by-Day Itinerary', 'wp-travel-machine' ); ?></h2>
                <?php if ( count( $itinerary ) > 1 ) : ?>
                <button type="button" class="wptm-itinerary__toggle-all" aria-expanded="false"
                    data-expand="<?php esc_attr_e( 'Expand all', 'wp-travel-machine' ); ?>"
                    data-collapse="<?php esc_attr_e( 'Collapse all', 'wp-travel-machine' ); ?>">
                    <span class="wptm-itinerary__toggle-icon" aria-hidden="true"></span>
                    <span class="wptm-itinerary__toggle-label"><?php esc_html_e( 'Expand all', 'wp-travel-machine' ); ?></span>
                </button>
                <?php endif; ?>
            </div>
            <div class="wptm-itinerary">
                <?php foreach ( $itinerary as $i => $day ) :
                    $d_title = trim( (string) ( $day['title'] ?? '' ) );
                    $d_desc  = trim( (string) ( $day['description'] ?? '' ) );
                    $d_meals = trim( (string) ( $day['meals'] ?? '' ) );
                    $d_accom = trim( (string) ( $day['accommodation'] ?? '' ) );
                    ?>
                <details class="wptm-itinerary__day"<?php echo 0 === $i ? ' open' : ''; ?>>
                    <summary class="wptm-itinerary__head">
                        <span class="wptm-itinerary__daynum"><?php printf( esc_html__( 'Day %d', 'wp-travel-machine' ), $i + 1 ); ?></span>
                        <span class="wptm-itinerary__title"><?php echo esc_html( $d_title ?: sprintf( __( 'Day %d', 'wp-travel-machine' ), $i + 1 ) ); ?></span>
                        <span class="wptm-itinerary__chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="wptm-itinerary__body">
                        <?php if ( '' !== $d_desc ) : ?><p class="wptm-itinerary__desc"><?php echo esc_html( $d_desc ); ?></p><?php endif; ?>
                        <?php if ( '' !== $d_meals || '' !== $d_accom ) : ?>
                        <div class="wptm-itinerary__facts">
                            <?php if ( '' !== $d_meals ) : ?>
                            <div class="wptm-itinerary__fact">
                                <span class="wptm-itinerary__fact-icon"><?php echo wptm_icon( 'utensils', array( 'size' => 18 ) ); ?></span>
                                <span class="wptm-itinerary__fact-text">
                                    <span class="wptm-itinerary__fact-label"><?php esc_html_e( 'Meals', 'wp-travel-machine' ); ?></span>
                                    <span class="wptm-itinerary__fact-value"><?php echo esc_html( $d_meals ); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ( '' !== $d_accom ) : ?>
                            <div class="wptm-itinerary__fact">
                                <span class="wptm-itinerary__fact-icon"><?php echo wptm_icon( 'bed', array( 'size' => 18 ) ); ?></span>
                                <span class="wptm-itinerary__fact-text">
                                    <span class="wptm-itinerary__fact-label"><?php esc_html_e( 'Accommodation', 'wp-travel-machine' ); ?></span>
                                    <span class="wptm-itinerary__fact-value"><?php echo esc_html( $d_accom ); ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Includes/Excludes -->
        <?php
        $inc_lines = wptm_to_list( $includes );
        $exc_lines = wptm_to_list( $excludes );
        if ( ! empty( $inc_lines ) || ! empty( $exc_lines ) ) :
            ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( "What's Included", 'wp-travel-machine' ); ?></h2>
            <div class="wptm-inc-exc">
                <?php if ( ! empty( $inc_lines ) ) : ?>
                <div class="wptm-inc-exc__col">
                    <h4 class="wptm-inc-exc__head wptm-inc-exc__head--yes"><?php esc_html_e( 'Included', 'wp-travel-machine' ); ?></h4>
                    <ul class="wptm-checklist wptm-checklist--yes">
                        <?php foreach ( $inc_lines as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $exc_lines ) ) : ?>
                <div class="wptm-inc-exc__col">
                    <h4 class="wptm-inc-exc__head wptm-inc-exc__head--no"><?php esc_html_e( 'Not Included', 'wp-travel-machine' ); ?></h4>
                    <ul class="wptm-checklist wptm-checklist--no">
                        <?php foreach ( $exc_lines as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Location -->
        <?php
        wptm_get_template( 'partials/location-map.php', array(
            'lat'     => $lat,
            'lng'     => $lng,
            'address' => $addr,
            'embed'   => get_post_meta( $pid, '_wptm_map_embed', true ),
            'label'   => get_the_title( $pid ),
        ) );
        ?>

        <!-- FAQ -->
        <?php if ( is_array( $faq ) && ! empty( $faq ) ) : ?>
        <div class="wptm-section">
            <h2 class="wptm-section__title"><?php esc_html_e( 'Frequently Asked Questions', 'wp-travel-machine' ); ?></h2>
            <div class="wptm-faq">
                <?php foreach ( $faq as $row ) :
                    $q = trim( (string) ( $row['question'] ?? '' ) );
                    $a = trim( (string) ( $row['answer'] ?? '' ) );
                    if ( '' === $q && '' === $a ) continue;
                    ?>
                <details class="wptm-faq__item">
                    <summary class="wptm-faq__q">
                        <span><?php echo esc_html( $q ); ?></span>
                        <span class="wptm-faq__chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="wptm-faq__a"><?php echo wpautop( esc_html( $a ) ); ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        /**
         * Fires inside the main column, after the trip content sections (before reviews).
         *
         * @param int $pid Trip post ID.
         */
        do_action( 'wptm_single_trip_after_content', $pid );
        ?>

        <!-- Enquiry -->
        <?php wptm_get_template( 'partials/enquiry-form.php', array( 'post_id' => $pid ) ); ?>
    </div>

    <div class="wptm-single-sidebar">
        <?php
        /**
         * Fires at the top of the trip sidebar (before the booking form).
         *
         * @param int $pid Trip post ID.
         */
        do_action( 'wptm_single_trip_before_sidebar', $pid );

        wptm_get_template( 'partials/booking-form.php', array( 'item_id' => $pid ) );

        /**
         * Fires at the bottom of the trip sidebar (after the booking form).
         *
         * @param int $pid Trip post ID.
         */
        do_action( 'wptm_single_trip_after_sidebar', $pid );
        ?>
    </div>
</div>

<!-- Related Trips -->
<?php wptm_get_template( 'partials/related-items.php', array( 'item_id' => $pid, 'item_type' => 'trip' ) ); ?>

<!-- Sticky Booking Bar -->
<div class="wptm-sticky-bar">
    <div class="wptm-sticky-bar__title"><?php the_title(); ?></div>
    <div class="wptm-sticky-bar__price">
        <span class="amount"><?php echo esc_html( $sym . number_format( $sale ?: $price, 0 ) ); ?></span>
        <span class="per">/<?php esc_html_e( 'person', 'wp-travel-machine' ); ?></span>
    </div>
    <a href="#wptm-booking-form" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'Book Now', 'wp-travel-machine' ); ?></a>
</div>

<?php
/**
 * Fires at the very bottom of the single trip template (before the footer).
 *
 * @param int $pid Trip post ID.
 */
do_action( 'wptm_after_single_trip', $pid );

get_footer();
