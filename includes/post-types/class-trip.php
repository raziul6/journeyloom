<?php
namespace WPTravelMachine\PostTypes;

if ( ! defined( 'ABSPATH' ) ) exit;

class Trip {
    public function __construct() {
        add_action( 'init', array( $this, 'register' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_wptm_trip', array( $this, 'save_meta' ), 10, 2 );
        add_filter( 'manage_wptm_trip_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_wptm_trip_posts_custom_column', array( $this, 'column_data' ), 10, 2 );
    }

    public function register() {
        $args = array(
            'labels' => array(
                'name' => __( 'Trips', 'wp-travel-machine' ),
                'singular_name' => __( 'Trip', 'wp-travel-machine' ),
                'add_new' => __( 'Add New Trip', 'wp-travel-machine' ),
                'add_new_item' => __( 'Add New Trip', 'wp-travel-machine' ),
                'edit_item' => __( 'Edit Trip', 'wp-travel-machine' ),
                'view_item' => __( 'View Trip', 'wp-travel-machine' ),
                'search_items' => __( 'Search Trips', 'wp-travel-machine' ),
                'not_found' => __( 'No trips found', 'wp-travel-machine' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'trips', 'with_front' => false ),
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
            'menu_icon' => 'dashicons-palmtree',
            'show_in_rest' => true,
            'rest_base' => 'wptm-trips',
            'show_in_menu' => 'wptm-dashboard',
            'taxonomies' => array( 'wptm_destination', 'wptm_activity', 'wptm_trip_type', 'wptm_difficulty' ),
        );

        /**
         * Filter the Trip post type registration arguments.
         *
         * @param array $args Arguments passed to register_post_type().
         */
        register_post_type( 'wptm_trip', apply_filters( 'wptm_trip_post_type_args', $args ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'wptm_trip_data', __( 'Trip Configuration', 'wp-travel-machine' ), array( $this, 'render_data' ), 'wptm_trip', 'normal', 'high' );
    }

    /**
     * Single tabbed metabox combining Overview, Itinerary, Pricing, Location & Gallery.
     */
    public function render_data( $post ) {
        wp_nonce_field( 'wptm_trip_meta', 'wptm_trip_nonce' );

        $tabs = array(
            'overview'  => array( 'label' => __( 'Overview', 'wp-travel-machine' ), 'icon' => 'dashicons-info-outline', 'view' => 'metabox-trip-details' ),
            'itinerary' => array( 'label' => __( 'Itinerary', 'wp-travel-machine' ), 'icon' => 'dashicons-list-view', 'view' => 'metabox-trip-itinerary' ),
            'pricing'   => array( 'label' => __( 'Pricing', 'wp-travel-machine' ), 'icon' => 'dashicons-money-alt', 'view' => 'metabox-trip-pricing' ),
            'location'  => array( 'label' => __( 'Location', 'wp-travel-machine' ), 'icon' => 'dashicons-location', 'view' => 'metabox-trip-map' ),
            'gallery'   => array( 'label' => __( 'Gallery', 'wp-travel-machine' ), 'icon' => 'dashicons-format-gallery', 'view' => 'metabox-gallery-panel' ),
            'faq'       => array( 'label' => __( 'FAQ', 'wp-travel-machine' ), 'icon' => 'dashicons-editor-help', 'view' => 'metabox-trip-faq' ),
        );

        // Pickup Points are a Pro feature.
        if ( wptm_is_pro() ) {
            $tabs['pickup'] = array( 'label' => __( 'Pickup Points', 'wp-travel-machine' ), 'icon' => 'dashicons-location-alt', 'view' => 'metabox-trip-pickup' );
        }

        // Data each panel view expects.
        $fields = array(
            'duration' => get_post_meta( $post->ID, '_wptm_duration', true ),
            'duration_unit' => get_post_meta( $post->ID, '_wptm_duration_unit', true ) ?: 'days',
            'group_min' => get_post_meta( $post->ID, '_wptm_group_min', true ) ?: 1,
            'group_max' => get_post_meta( $post->ID, '_wptm_group_max', true ) ?: 20,
            'difficulty' => get_post_meta( $post->ID, '_wptm_difficulty', true ) ?: 'moderate',
            'min_age' => get_post_meta( $post->ID, '_wptm_min_age', true ) ?: 0,
            'highlights' => get_post_meta( $post->ID, '_wptm_highlights', true ) ?: '',
            'includes' => get_post_meta( $post->ID, '_wptm_includes', true ) ?: '',
            'excludes' => get_post_meta( $post->ID, '_wptm_excludes', true ) ?: '',
        );
        $itinerary = get_post_meta( $post->ID, '_wptm_itinerary', true );
        $itinerary = is_array( $itinerary ) ? $itinerary : array();
        $faq = get_post_meta( $post->ID, '_wptm_faq', true );
        $faq = is_array( $faq ) ? $faq : array();
        $pickups = get_post_meta( $post->ID, '_wptm_pickup_points', true );
        $pickups = is_array( $pickups ) ? $pickups : array();
        $pricing = get_post_meta( $post->ID, '_wptm_pricing', true );
        $pricing = is_array( $pricing ) && ! empty( $pricing ) ? $pricing : array( array( 'label' => 'Adult', 'price' => '', 'sale_price' => '' ) );
        $lat = get_post_meta( $post->ID, '_wptm_latitude', true ) ?: '';
        $lng = get_post_meta( $post->ID, '_wptm_longitude', true ) ?: '';
        $addr = get_post_meta( $post->ID, '_wptm_address', true ) ?: '';
        $map_embed = get_post_meta( $post->ID, '_wptm_map_embed', true ) ?: '';

        // Gallery panel data.
        $gallery   = get_post_meta( $post->ID, '_wptm_gallery', true ) ?: '';
        $video_url = get_post_meta( $post->ID, '_wptm_video_url', true ) ?: '';
        $audio_url = get_post_meta( $post->ID, '_wptm_audio_url', true ) ?: '';
        $gallery_field = 'wptm_gallery';
        $video_field   = 'wptm_video_url';
        $audio_field   = 'wptm_audio_url';
        $gallery_dom   = 'wptm-gallery';

        include WPTM_PLUGIN_DIR . 'admin/views/metabox-tabs.php';
    }

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['wptm_trip_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wptm_trip_nonce'] ) ), 'wptm_trip_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $text_fields = array(
            'wptm_duration' => '_wptm_duration', 'wptm_duration_unit' => '_wptm_duration_unit',
            'wptm_group_min' => '_wptm_group_min', 'wptm_group_max' => '_wptm_group_max',
            'wptm_difficulty' => '_wptm_difficulty', 'wptm_min_age' => '_wptm_min_age',
            'wptm_gallery' => '_wptm_gallery',
            'wptm_latitude' => '_wptm_latitude', 'wptm_longitude' => '_wptm_longitude',
            'wptm_address' => '_wptm_address',
        );
        foreach ( $text_fields as $fk => $mk ) {
            if ( isset( $_POST[ $fk ] ) ) update_post_meta( $post_id, $mk, sanitize_text_field( wp_unslash( $_POST[ $fk ] ) ) );
        }

        // Featured flag (checkbox — absent when unchecked).
        update_post_meta( $post_id, '_wptm_featured', isset( $_POST['wptm_featured'] ) ? 1 : 0 );

        // List repeaters → arrays of non-empty strings.
        $list_fields = array(
            'wptm_highlights' => '_wptm_highlights',
            'wptm_includes'   => '_wptm_includes',
            'wptm_excludes'   => '_wptm_excludes',
        );
        foreach ( $list_fields as $fk => $mk ) {
            if ( ! isset( $_POST[ $fk ] ) ) continue;
            $list = array();
            foreach ( (array) wp_unslash( $_POST[ $fk ] ) as $v ) {
                $v = sanitize_text_field( $v );
                if ( '' !== trim( $v ) ) $list[] = $v;
            }
            update_post_meta( $post_id, $mk, $list );
        }

        // FAQ repeater → array of { question, answer }.
        if ( isset( $_POST['wptm_faq_present'] ) ) {
            $faq  = array();
            $rows = ( isset( $_POST['wptm_faq'] ) && is_array( $_POST['wptm_faq'] ) ) ? wp_unslash( $_POST['wptm_faq'] ) : array();
            foreach ( $rows as $row ) {
                $q = sanitize_text_field( $row['question'] ?? '' );
                $a = sanitize_textarea_field( $row['answer'] ?? '' );
                if ( '' !== trim( $q ) || '' !== trim( $a ) ) {
                    $faq[] = array( 'question' => $q, 'answer' => $a );
                }
            }
            update_post_meta( $post_id, '_wptm_faq', $faq );
        }

        // Pickup points (Pro) → array of { label, price }. The presence flag lets
        // removing every row clear the saved list.
        if ( wptm_is_pro() && isset( $_POST['wptm_pickups_present'] ) ) {
            $pickups = array();
            $rows    = ( isset( $_POST['wptm_pickups'] ) && is_array( $_POST['wptm_pickups'] ) ) ? wp_unslash( $_POST['wptm_pickups'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized — sanitized per field below.
            foreach ( $rows as $row ) {
                $label = sanitize_text_field( $row['label'] ?? '' );
                if ( '' === trim( $label ) ) {
                    continue;
                }
                $pickups[] = array( 'label' => $label, 'price' => round( (float) ( $row['price'] ?? 0 ), 2 ) );
            }
            update_post_meta( $post_id, '_wptm_pickup_points', $pickups );
        }

        // Map embed (iframe) — sanitized to a safe, provider-validated iframe.
        if ( isset( $_POST['wptm_map_embed'] ) ) {
            update_post_meta( $post_id, '_wptm_map_embed', wptm_sanitize_map_embed( wp_unslash( $_POST['wptm_map_embed'] ), get_the_title( $post_id ) ) );
        }

        // Gallery media (video / audio URLs).
        $url_fields = array(
            'wptm_video_url' => '_wptm_video_url',
            'wptm_audio_url' => '_wptm_audio_url',
        );
        foreach ( $url_fields as $fk => $mk ) {
            if ( isset( $_POST[ $fk ] ) ) update_post_meta( $post_id, $mk, esc_url_raw( trim( wp_unslash( $_POST[ $fk ] ) ) ) );
        }

        if ( isset( $_POST['wptm_itinerary'] ) && is_array( $_POST['wptm_itinerary'] ) ) {
            $it = array();
            foreach ( wp_unslash( $_POST['wptm_itinerary'] ) as $day ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized — sanitized per field below.
                $it[] = array(
                    'title' => sanitize_text_field( $day['title'] ?? '' ),
                    'description' => sanitize_textarea_field( $day['description'] ?? '' ),
                    'meals' => sanitize_text_field( $day['meals'] ?? '' ),
                    'accommodation' => sanitize_text_field( $day['accommodation'] ?? '' ),
                );
            }
            update_post_meta( $post_id, '_wptm_itinerary', $it );
        }

        if ( isset( $_POST['wptm_pricing'] ) && is_array( $_POST['wptm_pricing'] ) ) {
            $pr = array();
            foreach ( wp_unslash( $_POST['wptm_pricing'] ) as $tier ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized — sanitized per field below.
                $pr[] = array(
                    'label' => sanitize_text_field( $tier['label'] ?? '' ),
                    'price' => floatval( $tier['price'] ?? 0 ),
                    'sale_price' => floatval( $tier['sale_price'] ?? 0 ),
                );
            }
            update_post_meta( $post_id, '_wptm_pricing', $pr );

            // Mirror the lowest effective price into a flat numeric meta so the
            // search engine can filter/sort by price (the pricing array can't).
            update_post_meta( $post_id, '_wptm_price', self::lowest_price( $pr ) );
        }
    }

    /**
     * Lowest effective (sale-aware) price across pricing tiers.
     *
     * @param array $pricing Pricing tiers.
     * @return float
     */
    public static function lowest_price( $pricing ) {
        $prices = array();
        foreach ( (array) $pricing as $tier ) {
            $sale = floatval( $tier['sale_price'] ?? 0 );
            $base = floatval( $tier['price'] ?? 0 );
            $eff  = $sale > 0 ? $sale : $base;
            if ( $eff > 0 ) {
                $prices[] = $eff;
            }
        }
        return empty( $prices ) ? 0.0 : min( $prices );
    }

    public function columns( $cols ) {
        $new = array();
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( 'title' === $k ) {
                $new['wptm_price'] = __( 'Price', 'wp-travel-machine' );
                $new['wptm_duration'] = __( 'Duration', 'wp-travel-machine' );
                $new['wptm_difficulty'] = __( 'Difficulty', 'wp-travel-machine' );
            }
        }
        return $new;
    }

    public function column_data( $col, $pid ) {
        $sym = get_option( 'wptm_currency_symbol', '$' );
        switch ( $col ) {
            case 'wptm_price':
                $p = get_post_meta( $pid, '_wptm_pricing', true );
                echo is_array( $p ) && ! empty( $p ) ? esc_html( $sym . number_format( $p[0]['price'], 2 ) ) : '—';
                break;
            case 'wptm_duration':
                $d = get_post_meta( $pid, '_wptm_duration', true );
                $u = get_post_meta( $pid, '_wptm_duration_unit', true ) ?: 'days';
                echo $d ? esc_html( "$d $u" ) : '—';
                break;
            case 'wptm_difficulty':
                $df = get_post_meta( $pid, '_wptm_difficulty', true );
                echo $df ? esc_html( ucfirst( $df ) ) : '—';
                break;
        }
    }
}
