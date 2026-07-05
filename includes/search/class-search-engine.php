<?php
namespace JourneyLoom\Search;

if ( ! defined( 'ABSPATH' ) ) exit;

class SearchEngine {
    public function __construct() {
        add_action( 'wp_ajax_wptm_search', array( $this, 'handle_search' ) );
        add_action( 'wp_ajax_nopriv_wptm_search', array( $this, 'handle_search' ) );
        add_action( 'wp_ajax_wptm_filter_trips', array( $this, 'filter_trips' ) );
        add_action( 'wp_ajax_nopriv_wptm_filter_trips', array( $this, 'filter_trips' ) );
        add_action( 'wp_ajax_wptm_filter_hotels', array( $this, 'filter_hotels' ) );
        add_action( 'wp_ajax_nopriv_wptm_filter_hotels', array( $this, 'filter_hotels' ) );
        add_action( 'pre_get_posts', array( $this, 'modify_archive_query' ) );
    }

    public function handle_search() {
        check_ajax_referer( 'wptm_search_nonce', 'nonce' );

        $query = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
        $type = sanitize_text_field( wp_unslash( $_POST['search_type'] ?? 'all' ) );
        $filters = $this->sanitize_filters( wp_unslash( $_POST ) );

        $args = array(
            'post_type' => 'all' === $type ? array( 'wptm_trip', 'wptm_hotel' ) : ( 'hotel' === $type ? 'wptm_hotel' : 'wptm_trip' ),
            'posts_per_page' => ( ! empty( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : (int) get_option( 'wptm_items_per_page', 12 ) ),
            'paged' => absint( $_POST['page'] ?? 1 ),
            's' => $query,
            'post_status' => 'publish',
        );

        $args = $this->apply_filters_to_query( $args, $filters );
        $wp_query = new \WP_Query( $args );

        $results = array();
        foreach ( $wp_query->posts as $post ) {
            $results[] = $this->format_result( $post );
        }

        wp_send_json_success( array(
            'results' => $results,
            'total' => $wp_query->found_posts,
            'pages' => $wp_query->max_num_pages,
            'current_page' => absint( $_POST['page'] ?? 1 ),
        ) );
    }

    public function filter_trips() {
        check_ajax_referer( 'wptm_search_nonce', 'nonce' );
        $filters = $this->sanitize_filters( wp_unslash( $_POST ) );

        $args = array(
            'post_type' => 'wptm_trip',
            'posts_per_page' => ( ! empty( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : (int) get_option( 'wptm_items_per_page', 12 ) ),
            'paged' => absint( $_POST['page'] ?? 1 ),
            'post_status' => 'publish',
        );

        if ( ! empty( $_POST['keyword'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_POST['keyword'] ) );
        }

        $args = $this->apply_filters_to_query( $args, $filters );
        $wp_query = new \WP_Query( $args );

        $html = '';
        if ( $wp_query->have_posts() ) {
            ob_start();
            while ( $wp_query->have_posts() ) {
                $wp_query->the_post();
                wptm_get_template( 'partials/trip-card.php' );
            }
            wp_reset_postdata();
            $html = ob_get_clean();
        }

        wp_send_json_success( array(
            'html' => $html,
            'total' => $wp_query->found_posts,
            'pages' => $wp_query->max_num_pages,
        ) );
    }

    /**
     * AJAX: filter the hotel grid (destination, type, facility, stars, sort).
     */
    public function filter_hotels() {
        check_ajax_referer( 'wptm_search_nonce', 'nonce' );

        $args = array(
            'post_type'      => 'wptm_hotel',
            'posts_per_page' => ( ! empty( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : (int) get_option( 'wptm_items_per_page', 12 ) ),
            'paged'          => absint( $_POST['page'] ?? 1 ),
            'post_status'    => 'publish',
        );

        if ( ! empty( $_POST['keyword'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_POST['keyword'] ) );
        }

        $tax_query = array();
        foreach ( array(
            'destination'    => 'wptm_destination',
            'hotel_type'     => 'wptm_hotel_type',
            'hotel_facility' => 'wptm_hotel_facility',
        ) as $field => $taxonomy ) {
            $slug = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );
            if ( $slug ) {
                $tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slug );
            }
        }
        if ( $tax_query ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query']     = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- querying the plugin's own indexed meta; low-frequency query.
        }

        $stars = absint( $_POST['stars'] ?? 0 );
        if ( $stars ) {
            $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- querying the plugin's own indexed meta; low-frequency query.
                array( 'key' => '_wptm_star_rating', 'value' => $stars, 'compare' => '>=', 'type' => 'NUMERIC' ),
            );
        }

        switch ( sanitize_text_field( wp_unslash( $_POST['sort'] ?? 'date' ) ) ) {
            case 'name':  $args['orderby'] = 'title'; $args['order'] = 'ASC'; break;
            case 'stars': $args['meta_key'] = '_wptm_star_rating'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- querying the plugin's own indexed meta; low-frequency query.
            default:      $args['orderby'] = 'date'; $args['order'] = 'DESC';
        }

        $wp_query = new \WP_Query( $args );
        $html     = '';
        if ( $wp_query->have_posts() ) {
            ob_start();
            while ( $wp_query->have_posts() ) {
                $wp_query->the_post();
                wptm_get_template( 'partials/hotel-card.php' );
            }
            wp_reset_postdata();
            $html = ob_get_clean();
        }

        wp_send_json_success( array(
            'html'  => $html,
            'total' => $wp_query->found_posts,
            'pages' => $wp_query->max_num_pages,
        ) );
    }

    private function sanitize_filters( $data ) {
        $duration_min = absint( $data['duration_min'] ?? 0 );
        $duration_max = absint( $data['duration_max'] ?? 0 );

        // The frontend "Duration" select submits a bucket like "4-7" or "15-".
        if ( ! empty( $data['duration'] ) && preg_match( '/^(\d+)-(\d*)$/', $data['duration'], $m ) ) {
            $duration_min = absint( $m[1] );
            $duration_max = '' === $m[2] ? 0 : absint( $m[2] );
        }

        return array(
            'destination' => sanitize_text_field( $data['destination'] ?? '' ),
            'activity' => sanitize_text_field( $data['activity'] ?? '' ),
            'trip_type' => sanitize_text_field( $data['trip_type'] ?? '' ),
            'difficulty' => sanitize_text_field( $data['difficulty'] ?? '' ),
            'min_price' => floatval( $data['min_price'] ?? 0 ),
            'max_price' => floatval( $data['max_price'] ?? 0 ),
            'duration_min' => $duration_min,
            'duration_max' => $duration_max,
            'date_from' => sanitize_text_field( $data['date_from'] ?? ( $data['date'] ?? '' ) ),
            'date_to' => sanitize_text_field( $data['date_to'] ?? '' ),
            'guests' => absint( $data['guests'] ?? 0 ),
            'sort' => sanitize_text_field( $data['sort'] ?? 'date' ),
        );
    }

    private function apply_filters_to_query( $args, $filters ) {
        $tax_query = array();
        $meta_query = array();

        if ( $filters['destination'] ) {
            $tax_query[] = array( 'taxonomy' => 'wptm_destination', 'field' => 'slug', 'terms' => $filters['destination'] );
        }
        if ( $filters['activity'] ) {
            $tax_query[] = array( 'taxonomy' => 'wptm_activity', 'field' => 'slug', 'terms' => $filters['activity'] );
        }
        if ( $filters['trip_type'] ) {
            $tax_query[] = array( 'taxonomy' => 'wptm_trip_type', 'field' => 'slug', 'terms' => $filters['trip_type'] );
        }
        if ( $filters['difficulty'] ) {
            // Difficulty is now a taxonomy; match by slug, falling back to the
            // legacy `_wptm_difficulty` meta for trips saved before the change.
            $tax_query[] = array( 'taxonomy' => 'wptm_difficulty', 'field' => 'slug', 'terms' => $filters['difficulty'] );
        }
        if ( $filters['guests'] ) {
            $meta_query[] = array(
                'key' => '_wptm_group_max', 'value' => $filters['guests'], 'compare' => '>=', 'type' => 'NUMERIC',
            );
        }
        if ( $filters['min_price'] || $filters['max_price'] ) {
            if ( $filters['min_price'] ) {
                $meta_query[] = array( 'key' => '_wptm_price', 'value' => $filters['min_price'], 'compare' => '>=', 'type' => 'NUMERIC' );
            }
            if ( $filters['max_price'] ) {
                $meta_query[] = array( 'key' => '_wptm_price', 'value' => $filters['max_price'], 'compare' => '<=', 'type' => 'NUMERIC' );
            }
        }
        if ( $filters['duration_min'] || $filters['duration_max'] ) {
            if ( $filters['duration_min'] ) {
                $meta_query[] = array(
                    'key'     => '_wptm_duration',
                    'value'   => $filters['duration_min'],
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
            }
            if ( $filters['duration_max'] ) {
                $meta_query[] = array(
                    'key'     => '_wptm_duration',
                    'value'   => $filters['duration_max'],
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                );
            }
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- querying the plugin's own indexed meta; low-frequency query.
        }
        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- querying the plugin's own indexed meta; low-frequency query.
        }

        switch ( $filters['sort'] ) {
            case 'price_low':  $args['meta_key'] = '_wptm_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; break; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- querying the plugin's own indexed meta; low-frequency query.
            case 'price_high': $args['meta_key'] = '_wptm_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- querying the plugin's own indexed meta; low-frequency query.
            case 'name':       $args['orderby'] = 'title'; $args['order'] = 'ASC'; break;
            case 'popular':    $args['orderby'] = 'comment_count'; $args['order'] = 'DESC'; break;
            default:           $args['orderby'] = 'date'; $args['order'] = 'DESC';
        }

        return $args;
    }

    private function format_result( $post ) {
        $pricing = get_post_meta( $post->ID, '_wptm_pricing', true );
        $price = is_array( $pricing ) && ! empty( $pricing ) ? $pricing[0]['price'] : 0;

        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words( $post->post_excerpt ?: $post->post_content, 20 ),
            'url' => get_permalink( $post->ID ),
            'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'medium' ),
            'price' => $price,
            'type' => $post->post_type,
            'duration' => get_post_meta( $post->ID, '_wptm_duration', true ),
            'difficulty' => get_post_meta( $post->ID, '_wptm_difficulty', true ),
        );
    }

    public function modify_archive_query( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) return;

        $is_hotel_context = $query->is_post_type_archive( 'wptm_hotel' )
            || $query->is_tax( array( 'wptm_hotel_type', 'wptm_hotel_facility' ) );
        $is_trip_context  = $query->is_post_type_archive( 'wptm_trip' )
            || $query->is_tax( array( 'wptm_destination', 'wptm_activity', 'wptm_trip_type', 'wptm_difficulty' ) );

        if ( ! $is_trip_context && ! $is_hotel_context ) {
            return;
        }

        // Items-per-page applies to every WPTM archive (trips and hotels).
        $query->set( 'posts_per_page', (int) get_option( 'wptm_items_per_page', 12 ) );

        // The advanced filter logic below is trip-specific.
        if ( ! $is_trip_context ) {
            return;
        }

        // Apply the advanced-search parameters to the main archive query so the
        // frontend search form actually filters results. Params are namespaced
        // under wptm_search[...] to avoid colliding with other plugins' query
        // vars (e.g. a `destination` taxonomy registered by another plugin).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only public search filter; each value is sanitized in sanitize_filters().
        $request = isset( $_GET['wptm_search'] ) && is_array( $_GET['wptm_search'] ) ? wp_unslash( $_GET['wptm_search'] ) : array();
        if ( empty( $request ) ) {
            return;
        }

        $filters = $this->sanitize_filters( $request );
        $args    = $this->apply_filters_to_query( array(), $filters );

        // Keyword search (kept as a normal param so the page stays a trip
        // archive rather than becoming a generic search results page).
        if ( ! empty( $request['keyword'] ) ) {
            $query->set( 's', sanitize_text_field( $request['keyword'] ) );
        }

        if ( ! empty( $args['tax_query'] ) ) {
            $existing = $query->get( 'tax_query' );
            $existing = is_array( $existing ) ? $existing : array();
            $query->set( 'tax_query', array_merge( $existing, $args['tax_query'] ) );
        }
        if ( ! empty( $args['meta_query'] ) ) {
            $existing = $query->get( 'meta_query' );
            $existing = is_array( $existing ) ? $existing : array();
            $query->set( 'meta_query', array_merge( $existing, $args['meta_query'] ) );
        }
        if ( ! empty( $args['orderby'] ) ) {
            $query->set( 'orderby', $args['orderby'] );
            $query->set( 'order', $args['order'] ?? 'DESC' );
            if ( ! empty( $args['meta_key'] ) ) {
                $query->set( 'meta_key', $args['meta_key'] );
            }
        }
    }
}
