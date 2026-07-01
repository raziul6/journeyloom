<?php
namespace JourneyLoom\PostTypes;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class Hotel {
    public function __construct() {
        add_action( 'init', array( $this, 'register' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_wptm_hotel', array( $this, 'save_meta' ), 10, 2 );
        add_filter( 'manage_wptm_hotel_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_wptm_hotel_posts_custom_column', array( $this, 'column_data' ), 10, 2 );
    }

    public function register() {
        $args = array(
            'labels' => array(
                'name' => __( 'Hotels', 'journeyloom' ),
                'singular_name' => __( 'Hotel', 'journeyloom' ),
                'add_new' => __( 'Add New Hotel', 'journeyloom' ),
                'add_new_item' => __( 'Add New Hotel', 'journeyloom' ),
                'edit_item' => __( 'Edit Hotel', 'journeyloom' ),
                'view_item' => __( 'View Hotel', 'journeyloom' ),
                'search_items' => __( 'Search Hotels', 'journeyloom' ),
                'not_found' => __( 'No hotels found', 'journeyloom' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'hotels', 'with_front' => false ),
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
            'menu_icon' => 'dashicons-building',
            'show_in_rest' => true,
            'rest_base' => 'wptm-hotels',
            'show_in_menu' => 'wptm-dashboard',
            'taxonomies' => array( 'wptm_destination', 'wptm_hotel_type', 'wptm_hotel_facility' ),
        );

        /**
         * Filter the Hotel post type registration arguments.
         *
         * @param array $args Arguments passed to register_post_type().
         */
        register_post_type( 'wptm_hotel', apply_filters( 'wptm_hotel_post_type_args', $args ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'wptm_hotel_data', __( 'Hotel Configuration', 'journeyloom' ), array( $this, 'render_data' ), 'wptm_hotel', 'normal', 'high' );
    }

    /**
     * Single tabbed metabox combining Overview, Location, Rooms & Gallery.
     */
    public function render_data( $post ) {
        wp_nonce_field( 'wptm_hotel_meta', 'wptm_hotel_nonce' );

        $tabs = array(
            'overview'   => array( 'label' => __( 'Overview', 'journeyloom' ), 'icon' => 'dashicons-info-outline', 'view' => 'metabox-hotel-details' ),
            'facilities' => array( 'label' => __( 'Facilities', 'journeyloom' ), 'icon' => 'dashicons-yes-alt', 'view' => 'metabox-hotel-facilities' ),
            'location'   => array( 'label' => __( 'Location', 'journeyloom' ), 'icon' => 'dashicons-location', 'view' => 'metabox-hotel-location' ),
            'rooms'      => array( 'label' => __( 'Rooms', 'journeyloom' ), 'icon' => 'dashicons-admin-home', 'view' => 'metabox-hotel-rooms' ),
            'availability' => array( 'label' => __( 'Availability', 'journeyloom' ), 'icon' => 'dashicons-calendar-alt', 'view' => 'metabox-hotel-availability' ),
            'gallery'    => array( 'label' => __( 'Gallery', 'journeyloom' ), 'icon' => 'dashicons-format-gallery', 'view' => 'metabox-gallery-panel' ),
        );

        // Facility groups: array of { title, items: [ name, … ] }.
        $facility_groups = get_post_meta( $post->ID, '_wptm_hotel_facilities', true );
        $facility_groups = is_array( $facility_groups ) ? $facility_groups : array();

        // Availability rules from the wptm_availability table.
        global $wpdb;
        $availability = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wptm_availability WHERE item_id = %d AND item_type = 'hotel' ORDER BY date_start ASC",
            $post->ID
        ), ARRAY_A );
        $availability = is_array( $availability ) ? $availability : array();

        $fields = array(
            'star_rating' => get_post_meta( $post->ID, '_wptm_star_rating', true ) ?: 3,
            'address' => get_post_meta( $post->ID, '_wptm_hotel_address', true ) ?: '',
            'city' => get_post_meta( $post->ID, '_wptm_hotel_city', true ) ?: '',
            'country' => get_post_meta( $post->ID, '_wptm_hotel_country', true ) ?: '',
            'latitude' => get_post_meta( $post->ID, '_wptm_hotel_lat', true ) ?: '',
            'longitude' => get_post_meta( $post->ID, '_wptm_hotel_lng', true ) ?: '',
            'map_embed' => get_post_meta( $post->ID, '_wptm_hotel_map_embed', true ) ?: '',
            'amenities' => get_post_meta( $post->ID, '_wptm_hotel_amenities', true ) ?: '',
            'check_in_time' => get_post_meta( $post->ID, '_wptm_check_in_time', true ) ?: '14:00',
            'check_out_time' => get_post_meta( $post->ID, '_wptm_check_out_time', true ) ?: '11:00',
            'contact_email' => get_post_meta( $post->ID, '_wptm_hotel_email', true ) ?: '',
            'contact_phone' => get_post_meta( $post->ID, '_wptm_hotel_phone', true ) ?: '',
        );

        global $wpdb;
        $rooms = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wptm_rooms WHERE hotel_id = %d ORDER BY sort_order ASC",
            $post->ID
        ), ARRAY_A );

        // Gallery panel data.
        $gallery   = get_post_meta( $post->ID, '_wptm_hotel_gallery', true ) ?: '';
        $video_url = get_post_meta( $post->ID, '_wptm_hotel_video_url', true ) ?: '';
        $audio_url = get_post_meta( $post->ID, '_wptm_hotel_audio_url', true ) ?: '';
        $gallery_field = 'wptm_hotel_gallery';
        $video_field   = 'wptm_hotel_video_url';
        $audio_field   = 'wptm_hotel_audio_url';
        $gallery_dom   = 'wptm-hotel-gallery';

        include WPTM_PLUGIN_DIR . 'admin/views/metabox-tabs.php';
    }

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['wptm_hotel_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wptm_hotel_nonce'] ) ), 'wptm_hotel_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array(
            'wptm_star_rating' => '_wptm_star_rating',
            'wptm_hotel_address' => '_wptm_hotel_address',
            'wptm_hotel_city' => '_wptm_hotel_city',
            'wptm_hotel_country' => '_wptm_hotel_country',
            'wptm_hotel_lat' => '_wptm_hotel_lat',
            'wptm_hotel_lng' => '_wptm_hotel_lng',
            'wptm_hotel_amenities' => '_wptm_hotel_amenities',
            'wptm_check_in_time' => '_wptm_check_in_time',
            'wptm_check_out_time' => '_wptm_check_out_time',
            'wptm_hotel_email' => '_wptm_hotel_email',
            'wptm_hotel_phone' => '_wptm_hotel_phone',
            'wptm_hotel_gallery' => '_wptm_hotel_gallery',
        );
        foreach ( $fields as $fk => $mk ) {
            if ( isset( $_POST[ $fk ] ) ) update_post_meta( $post_id, $mk, sanitize_text_field( wp_unslash( $_POST[ $fk ] ) ) );
        }

        // Featured flag (checkbox — absent when unchecked).
        update_post_meta( $post_id, '_wptm_featured', isset( $_POST['wptm_featured'] ) ? 1 : 0 );

        // Map embed (iframe) — sanitized to a safe, provider-validated iframe.
        if ( isset( $_POST['wptm_hotel_map_embed'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wptm_sanitize_map_embed() validates/sanitizes the iframe.
            update_post_meta( $post_id, '_wptm_hotel_map_embed', wptm_sanitize_map_embed( wp_unslash( $_POST['wptm_hotel_map_embed'] ), get_the_title( $post_id ) ) );
        }

        // Gallery media (video / audio URLs).
        $url_fields = array(
            'wptm_hotel_video_url' => '_wptm_hotel_video_url',
            'wptm_hotel_audio_url' => '_wptm_hotel_audio_url',
        );
        foreach ( $url_fields as $fk => $mk ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- esc_url_raw() sanitizes the URL.
            if ( isset( $_POST[ $fk ] ) ) update_post_meta( $post_id, $mk, esc_url_raw( trim( wp_unslash( $_POST[ $fk ] ) ) ) );
        }

        // Facility groups → meta + sync names to the hotel-facility taxonomy
        // (so the front-end filter keeps working).
        if ( isset( $_POST['wptm_facilities_present'] ) ) {
            $groups   = ( isset( $_POST['wptm_facilities'] ) && is_array( $_POST['wptm_facilities'] ) ) ? wp_unslash( $_POST['wptm_facilities'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
            $clean    = array();
            $all_names = array();

            foreach ( $groups as $group ) {
                $title = sanitize_text_field( $group['title'] ?? '' );
                $items = array();
                foreach ( preg_split( '/[\r\n,]+/', (string) ( $group['items'] ?? '' ) ) as $line ) {
                    $line = sanitize_text_field( $line );
                    if ( '' !== trim( $line ) ) {
                        $items[]     = $line;
                        $all_names[] = $line;
                    }
                }
                if ( '' !== trim( $title ) || ! empty( $items ) ) {
                    $clean[] = array( 'title' => $title, 'items' => $items );
                }
            }

            update_post_meta( $post_id, '_wptm_hotel_facilities', $clean );

            // Mirror facility names into the taxonomy for filtering.
            $term_ids = array();
            foreach ( array_unique( $all_names ) as $name ) {
                $term = term_exists( $name, 'wptm_hotel_facility' );
                if ( ! $term ) {
                    $term = wp_insert_term( $name, 'wptm_hotel_facility' );
                }
                if ( ! is_wp_error( $term ) ) {
                    $term_ids[] = (int) $term['term_id'];
                }
            }
            wp_set_object_terms( $post_id, $term_ids, 'wptm_hotel_facility' );
        }

        // Availability periods → wptm_availability table (replace the hotel's rows).
        if ( isset( $_POST['wptm_availability_present'] ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'wptm_availability';
            $wpdb->delete( $table, array( 'item_id' => $post_id, 'item_type' => 'hotel' ), array( '%d', '%s' ) );

            $rows = ( isset( $_POST['wptm_availability'] ) && is_array( $_POST['wptm_availability'] ) ) ? wp_unslash( $_POST['wptm_availability'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
            foreach ( $rows as $row ) {
                $start = sanitize_text_field( $row['date_start'] ?? '' );
                $end   = sanitize_text_field( $row['date_end'] ?? '' );
                if ( '' === $start || '' === $end ) {
                    continue; // skip incomplete periods.
                }
                $price = ( isset( $row['price'] ) && '' !== $row['price'] ) ? (float) $row['price'] : null;
                $wpdb->insert( $table, array(
                    'item_id'         => $post_id,
                    'item_type'       => 'hotel',
                    'room_id'         => null,
                    'date_start'      => $start,
                    'date_end'        => $end,
                    'available_spots' => absint( $row['spots'] ?? 0 ),
                    'price_override'  => $price,
                    'status'          => ( 'unavailable' === ( $row['status'] ?? 'available' ) ) ? 'unavailable' : 'available',
                ) );
            }
        }

        // Save rooms to custom table.
        if ( isset( $_POST['wptm_rooms'] ) && is_array( $_POST['wptm_rooms'] ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'wptm_rooms';
            $wpdb->delete( $table, array( 'hotel_id' => $post_id ), array( '%d' ) );
            foreach ( wp_unslash( $_POST['wptm_rooms'] ) as $i => $room ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
                $wpdb->insert( $table, array(
                    'hotel_id' => $post_id,
                    'room_type' => sanitize_text_field( $room['type'] ?? '' ),
                    'room_name' => sanitize_text_field( $room['name'] ?? '' ),
                    'description' => sanitize_textarea_field( $room['description'] ?? '' ),
                    'max_guests' => absint( $room['max_guests'] ?? 2 ),
                    'price_per_night' => floatval( $room['price'] ?? 0 ),
                    'sale_price' => floatval( $room['sale_price'] ?? 0 ) ?: null,
                    'amenities' => sanitize_text_field( $room['amenities'] ?? '' ),
                    'bed_type' => sanitize_text_field( $room['bed_type'] ?? '' ),
                    'room_size' => sanitize_text_field( $room['room_size'] ?? '' ),
                    'sort_order' => $i,
                    'status' => 'available',
                ) );
            }
        }
    }

    public function columns( $cols ) {
        $new = array();
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( 'title' === $k ) {
                $new['wptm_stars'] = __( 'Stars', 'journeyloom' );
                $new['wptm_city'] = __( 'City', 'journeyloom' );
                $new['wptm_rooms_count'] = __( 'Rooms', 'journeyloom' );
            }
        }
        return $new;
    }

    public function column_data( $col, $pid ) {
        switch ( $col ) {
            case 'wptm_stars':
                $s = get_post_meta( $pid, '_wptm_star_rating', true ) ?: 0;
                echo esc_html( str_repeat( '⭐', intval( $s ) ) );
                break;
            case 'wptm_city':
                echo esc_html( get_post_meta( $pid, '_wptm_hotel_city', true ) ?: '—' );
                break;
            case 'wptm_rooms_count':
                global $wpdb;
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wptm_rooms WHERE hotel_id = %d", $pid ) );
                echo intval( $count );
                break;
        }
    }
}
