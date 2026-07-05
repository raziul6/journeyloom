<?php
/**
 * Plugin activation logic.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Activator
 */
class Activator {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        // Check minimum requirements.
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( WPTM_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'Byteflows Travel & Hotel Booking requires PHP 7.4 or higher.', 'byteflows-travel-hotel-booking' ),
                'Plugin Activation Error',
                array( 'back_link' => true )
            );
        }

        // Create database tables.
        self::create_tables();

        // Set default options.
        self::set_defaults();

        // Auto-create system pages.
        self::create_pages();

        // Seed sensible default taxonomy terms.
        self::seed_default_terms();

        // Schedule flush rewrite rules.
        set_transient( 'wptm_flush_rewrites', true );

        // Store version.
        update_option( 'wptm_version', WPTM_VERSION );
        update_option( 'wptm_db_version', WPTM_DB_VERSION );

        // Set activation redirect flag.
        set_transient( 'wptm_activation_redirect', true, 30 );
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Bookings table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_bookings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_number VARCHAR(32) NOT NULL,
            user_id BIGINT(20) UNSIGNED DEFAULT 0,
            booking_type VARCHAR(20) NOT NULL DEFAULT 'trip',
            item_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(3) NOT NULL DEFAULT 'USD',
            travelers_count INT(11) NOT NULL DEFAULT 1,
            check_in DATE DEFAULT NULL,
            check_out DATE DEFAULT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            transaction_id VARCHAR(255) DEFAULT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50) DEFAULT NULL,
            customer_address TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            coupon_code VARCHAR(50) DEFAULT NULL,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_number (booking_number),
            KEY user_id (user_id),
            KEY item_id (item_id),
            KEY status (status),
            KEY booking_type (booking_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Booking meta table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_booking_meta (
            meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (meta_id),
            KEY booking_id (booking_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";

        // Rooms table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_rooms (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            hotel_id BIGINT(20) UNSIGNED NOT NULL,
            room_type VARCHAR(100) NOT NULL,
            room_name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            max_guests INT(11) NOT NULL DEFAULT 2,
            price_per_night DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            sale_price DECIMAL(12,2) DEFAULT NULL,
            amenities TEXT DEFAULT NULL,
            gallery TEXT DEFAULT NULL,
            bed_type VARCHAR(100) DEFAULT NULL,
            room_size VARCHAR(50) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'available',
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hotel_id (hotel_id),
            KEY status (status)
        ) $charset_collate;";

        // Availability table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_availability (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            item_type VARCHAR(20) NOT NULL DEFAULT 'trip',
            room_id BIGINT(20) UNSIGNED DEFAULT NULL,
            date_start DATE NOT NULL,
            date_end DATE NOT NULL,
            available_spots INT(11) NOT NULL DEFAULT 0,
            price_override DECIMAL(12,2) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'available',
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY item_type (item_type),
            KEY room_id (room_id),
            KEY date_range (date_start, date_end)
        ) $charset_collate;";

        // Reviews table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_reviews (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            item_type VARCHAR(20) NOT NULL DEFAULT 'trip',
            user_id BIGINT(20) UNSIGNED NOT NULL,
            rating TINYINT(1) NOT NULL DEFAULT 5,
            title VARCHAR(255) DEFAULT NULL,
            content TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Wishlist table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_wishlist (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            item_type VARCHAR(20) NOT NULL DEFAULT 'trip',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_item (user_id, item_id, item_type),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Coupons table.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wptm_coupons (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(50) NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            min_amount DECIMAL(12,2) DEFAULT NULL,
            max_uses INT(11) DEFAULT NULL,
            used_count INT(11) NOT NULL DEFAULT 0,
            applicable_items TEXT DEFAULT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) {
            dbDelta( $query );
        }
    }

    /**
     * Set default plugin options.
     */
    private static function set_defaults() {
        $defaults = array(
            'wptm_currency'          => 'USD',
            'wptm_currency_symbol'   => '$',
            'wptm_currency_position' => 'before',
            'wptm_date_format'       => 'Y-m-d',
            'wptm_tax_enabled'       => false,
            'wptm_tax_rate'          => 0,
            'wptm_items_per_page'    => 12,
            'wptm_enable_wishlist'   => true,
            'wptm_enable_compare'    => true,
            'wptm_enable_reviews'    => true,
            'wptm_enable_ai'         => false,
            'wptm_ai_provider'       => 'openai',
            'wptm_ai_api_key'        => '',
            'wptm_stripe_enabled'    => false,
            'wptm_paypal_enabled'    => false,
            'wptm_manual_payment'    => true,
            'wptm_booking_email'     => get_option( 'admin_email' ),
            'wptm_terms_page'        => 0,
            'wptm_search_form_fields' => wp_json_encode( array(
                'destination' => array( 'enabled' => true, 'label' => 'Destination', 'type' => 'select' ),
                'date'        => array( 'enabled' => true, 'label' => 'Date', 'type' => 'date' ),
                'guests'      => array( 'enabled' => true, 'label' => 'Guests', 'type' => 'number' ),
                'budget'      => array( 'enabled' => true, 'label' => 'Budget', 'type' => 'range' ),
                'duration'    => array( 'enabled' => true, 'label' => 'Duration', 'type' => 'select' ),
                'activity'    => array( 'enabled' => true, 'label' => 'Activity', 'type' => 'select' ),
                'difficulty'  => array( 'enabled' => false, 'label' => 'Difficulty', 'type' => 'select' ),
                'trip_type'   => array( 'enabled' => false, 'label' => 'Trip Type', 'type' => 'select' ),
            ) ),
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                update_option( $key, $value );
            }
        }
    }

    /**
     * Auto-create required system pages on activation.
     *
     * Pages are only created if they don't already exist (checks stored option).
     * Each page gets a shortcode embedded that provides its functionality.
     *
     * Idempotent: safe to call on activation and on upgrade.
     */
    public static function create_pages() {
        $pages = array(
            'search' => array(
                'title'   => __( 'Trip Search', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_search_form]<!-- /wp:shortcode -->' . "\n\n" . '<!-- wp:shortcode -->[wptm_trips count="12" columns="3"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_search',
            ),
            'destinations' => array(
                'title'   => __( 'Destinations', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_destinations count="12"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_destinations',
            ),
            'activities' => array(
                'title'   => __( 'Activities', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_terms taxonomy="wptm_activity" columns="4"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_activities',
            ),
            'trip_types' => array(
                'title'   => __( 'Trip Types', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_terms taxonomy="wptm_trip_type" columns="4"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_trip_types',
            ),
            'difficulties' => array(
                'title'   => __( 'Difficulty Levels', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_terms taxonomy="wptm_difficulty" columns="4"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_difficulties',
            ),
            'hotel_types' => array(
                'title'   => __( 'Hotel Types', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_terms taxonomy="wptm_hotel_type" columns="4"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_hotel_types',
            ),
            'hotel_facilities' => array(
                'title'   => __( 'Hotel Facilities', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_terms taxonomy="wptm_hotel_facility" columns="4"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_hotel_facilities',
            ),
            'trips' => array(
                'title'   => __( 'All Trips', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_search_form]<!-- /wp:shortcode -->' . "\n\n" . '<!-- wp:shortcode -->[wptm_trips count="12" columns="3"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_trips',
            ),
            'hotels' => array(
                'title'   => __( 'All Hotels', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_hotels count="12" columns="3"]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_hotels',
            ),
            'checkout' => array(
                'title'   => __( 'Checkout', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_checkout]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_checkout',
            ),
            'confirmation' => array(
                'title'   => __( 'Booking Confirmation', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_booking_confirmation]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_confirmation',
            ),
            'wishlist' => array(
                'title'   => __( 'My Wishlist', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_wishlist]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_wishlist',
            ),
            'my_bookings' => array(
                'title'   => __( 'My Bookings', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_my_bookings]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_my_bookings',
            ),
            'cart' => array(
                'title'   => __( 'Cart', 'byteflows-travel-hotel-booking' ),
                'content' => '<!-- wp:shortcode -->[wptm_cart]<!-- /wp:shortcode -->',
                'option'  => 'wptm_page_cart',
            ),
        );

        foreach ( $pages as $key => $page ) {
            $existing_id = get_option( $page['option'], 0 );

            // Check if the page already exists and is not trashed.
            if ( $existing_id && get_post_status( $existing_id ) !== false && get_post_status( $existing_id ) !== 'trash' ) {
                continue;
            }

            // Check if a page with the same title already exists (get_page_by_title
            // was deprecated in WP 6.2 in favour of WP_Query).
            $existing_query = new \WP_Query( array(
                'post_type'              => 'page',
                'title'                  => $page['title'],
                'post_status'            => array( 'publish', 'pending', 'draft', 'future', 'private' ),
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ) );
            $existing_page = ! empty( $existing_query->post ) ? $existing_query->post : null;
            if ( $existing_page && 'trash' !== $existing_page->post_status ) {
                update_option( $page['option'], $existing_page->ID );
                continue;
            }

            // Create the page.
            $page_id = wp_insert_post( array(
                'post_title'     => $page['title'],
                'post_content'   => $page['content'],
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
                'post_author'    => get_current_user_id() ?: 1,
            ) );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( $page['option'], $page_id );
            }
        }
    }

    /**
     * Seed default terms for taxonomies that have a natural fixed set.
     *
     * Only runs when the taxonomy exists and is currently empty, so it never
     * fights with terms the site owner has already created. Difficulty mirrors
     * the legacy `_wptm_difficulty` trip meta values.
     */
    public static function seed_default_terms() {
        $defaults = array(
            'wptm_difficulty' => array(
                __( 'Easy', 'byteflows-travel-hotel-booking' ),
                __( 'Moderate', 'byteflows-travel-hotel-booking' ),
                __( 'Challenging', 'byteflows-travel-hotel-booking' ),
                __( 'Extreme', 'byteflows-travel-hotel-booking' ),
            ),
        );

        foreach ( $defaults as $taxonomy => $terms ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }
            $existing = wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
            if ( is_wp_error( $existing ) || (int) $existing > 0 ) {
                continue;
            }
            foreach ( $terms as $term ) {
                if ( ! term_exists( $term, $taxonomy ) ) {
                    wp_insert_term( $term, $taxonomy );
                }
            }
        }
    }

    /**
     * Backfill the flat `_wptm_price` meta from each trip's pricing tiers so the
     * advanced search can filter/sort by price on content created before the
     * flat-price mirror existed. Idempotent.
     */
    public static function backfill_trip_prices() {
        $trips = get_posts( array(
            'post_type'      => 'wptm_trip',
            'post_status'    => 'any',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- querying the plugin's own indexed meta; low-frequency query.
                array( 'key' => '_wptm_price', 'compare' => 'NOT EXISTS' ),
            ),
        ) );

        foreach ( $trips as $trip_id ) {
            $pricing = get_post_meta( $trip_id, '_wptm_pricing', true );
            update_post_meta( $trip_id, '_wptm_price', \JourneyLoom\PostTypes\Trip::lowest_price( $pricing ) );
        }
    }

    /**
     * Remove duplicate wishlist rows left by installs that predate the unique
     * key, then (re)add that key so duplicates can't recur. Idempotent.
     */
    public static function dedupe_wishlist() {
        global $wpdb;
        // One-time activation maintenance on the plugin's own wptm_wishlist table.
        // The table name is derived from $wpdb->prefix (never user input) and these
        // are schema/maintenance statements that can't use the object cache.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $table = $wpdb->prefix . 'wptm_wishlist';

        // Bail if the table doesn't exist yet.
        if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
            return;
        }

        // Keep the lowest id per (user_id, item_id, item_type); drop the rest.
        $wpdb->query(
            "DELETE w1 FROM {$table} w1
             INNER JOIN {$table} w2
                 ON w1.user_id = w2.user_id
                AND w1.item_id = w2.item_id
                AND w1.item_type = w2.item_type
                AND w1.id > w2.id"
        );

        // Ensure the unique key is present (older tables may lack it).
        $has_key = $wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", 'user_item' ) );
        if ( ! $has_key ) {
            $wpdb->query( "ALTER TABLE {$table} ADD UNIQUE KEY user_item (user_id, item_id, item_type)" );
        }
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    }
}
