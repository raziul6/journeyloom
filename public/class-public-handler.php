<?php
namespace WPTravelMachine\Pub;

if ( ! defined( 'ABSPATH' ) ) exit;

class PublicHandler {
    /** Guards against rendering the AI chat widget twice (shortcode + auto-inject). */
    public static $ai_chat_rendered = false;

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        // Float the AI assistant on every front-end page when enabled.
        add_action( 'wp_footer', array( $this, 'render_ai_chat' ) );
    }

    /**
     * Output the AI chat widget in the footer (site-wide) when AI is enabled.
     */
    public function render_ai_chat() {
        if ( ! get_option( 'wptm_enable_ai', false ) ) {
            return;
        }
        echo self::ai_chat_markup(); // phpcs:ignore WordPress.Security.EscapeOutput — partial escapes its own output.
    }

    /**
     * Render the AI chat widget partial once per request.
     *
     * Shared by the footer auto-inject and the [wptm_ai_chat] shortcode so the
     * widget never renders twice on the same page.
     *
     * @return string
     */
    public static function ai_chat_markup() {
        if ( self::$ai_chat_rendered ) {
            return '';
        }
        self::$ai_chat_rendered = true;
        ob_start();
        include WPTM_PLUGIN_DIR . 'templates/partials/ai-chat.php';
        return ob_get_clean();
    }

    public function enqueue_assets() {
        $css_path = WPTM_PLUGIN_DIR . 'assets/css/public.css';
        $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : WPTM_VERSION;
        wp_enqueue_style( 'wptm-public', WPTM_PLUGIN_URL . 'assets/css/public.css', array(), $css_ver );

        // Fonts — Sora (display) + Plus Jakarta Sans (body), self-hosted inside
        // the plugin (no external/CDN request). Filter to disable, or pass your
        // own stylesheet URL via 'wptm_fonts_url'.
        if ( apply_filters( 'wptm_enqueue_fonts', true ) ) {
            $fonts_path = WPTM_PLUGIN_DIR . 'assets/vendor/fonts/fonts.css';
            $fonts_url  = apply_filters( 'wptm_fonts_url', WPTM_PLUGIN_URL . 'assets/vendor/fonts/fonts.css' );
            if ( $fonts_url ) {
                $fonts_ver = file_exists( $fonts_path ) ? filemtime( $fonts_path ) : WPTM_VERSION;
                wp_enqueue_style( 'wptm-fonts', $fonts_url, array(), $fonts_ver );
            }
        }

        // Main app JS (utilities: toast, ajax helper, format price, lightbox).
        $app_path = WPTM_PLUGIN_DIR . 'assets/js/public/app.js';
        $app_ver  = file_exists( $app_path ) ? filemtime( $app_path ) : WPTM_VERSION;
        wp_enqueue_script( 'wptm-public', WPTM_PLUGIN_URL . 'assets/js/public/app.js', array(), $app_ver, true );
        wp_localize_script( 'wptm-public', 'wptmData', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'restUrl'  => rest_url( 'wptm/v1/' ),
            'nonce'    => wp_create_nonce( 'wptm_booking_nonce' ),
            'searchNonce' => wp_create_nonce( 'wptm_search_nonce' ),
            'aiNonce'  => wp_create_nonce( 'wptm_ai_nonce' ),
            'currency' => get_option( 'wptm_currency_symbol', '$' ),
            'currencyPos' => get_option( 'wptm_currency_position', 'before' ),
            'enableWishlist' => get_option( 'wptm_enable_wishlist', true ),
            'enableCompare'  => get_option( 'wptm_enable_compare', true ),
            'enableAI' => get_option( 'wptm_enable_ai', false ),
            'paginationType' => get_option( 'wptm_pagination_type', 'pagination' ),
            'itemsPerPage'   => (int) get_option( 'wptm_items_per_page', 12 ),
            'pluginUrl' => WPTM_PLUGIN_URL,
            'i18n' => array(
                'addedToWishlist' => __( 'Added to wishlist!', 'wp-travel-machine' ),
                'removedFromWishlist' => __( 'Removed from wishlist.', 'wp-travel-machine' ),
                'addedToCart' => __( 'Added to cart!', 'wp-travel-machine' ),
                'bookNow' => __( 'Book Now', 'wp-travel-machine' ),
                'loading' => __( 'Loading...', 'wp-travel-machine' ),
                'noResults' => __( 'No results found. Try adjusting your filters.', 'wp-travel-machine' ),
                'loadMore' => __( 'Load More', 'wp-travel-machine' ),
                'prev' => __( 'Previous', 'wp-travel-machine' ),
                'next' => __( 'Next', 'wp-travel-machine' ),
            ),
        ) );

        // Search & Filter — on archive pages and pages with search shortcode.
        $sf_path = WPTM_PLUGIN_DIR . 'assets/js/public/search-filter.js';
        $sf_ver  = file_exists( $sf_path ) ? filemtime( $sf_path ) : WPTM_VERSION;
        wp_enqueue_script( 'wptm-search-filter', WPTM_PLUGIN_URL . 'assets/js/public/search-filter.js', array( 'wptm-public' ), $sf_ver, true );

        // Archive filter bar (trips & hotels).
        $fb_path = WPTM_PLUGIN_DIR . 'assets/js/public/filter-bar.js';
        $fb_ver  = file_exists( $fb_path ) ? filemtime( $fb_path ) : WPTM_VERSION;
        wp_enqueue_script( 'wptm-filter-bar', WPTM_PLUGIN_URL . 'assets/js/public/filter-bar.js', array( 'wptm-public' ), $fb_ver, true );

        // Wishlist.
        wp_enqueue_script( 'wptm-wishlist', WPTM_PLUGIN_URL . 'assets/js/public/wishlist.js', array( 'wptm-public' ), WPTM_VERSION, true );

        // Single trip/hotel pages.
        if ( is_singular( array( 'wptm_trip', 'wptm_hotel' ) ) ) {
            $booking_path = WPTM_PLUGIN_DIR . 'assets/js/public/booking-engine.js';
            $booking_ver  = file_exists( $booking_path ) ? filemtime( $booking_path ) : WPTM_VERSION;

            // Online gateway client SDKs — loaded only when the gateway is fully
            // configured, and made dependencies of the booking script so their
            // globals (Stripe / paypal) exist before it runs.
            $currency      = get_option( 'wptm_currency', 'USD' );
            $stripe_pk     = (string) get_option( 'wptm_stripe_publishable_key', '' );
            $stripe_on     = get_option( 'wptm_stripe_enabled', false ) && '' !== trim( $stripe_pk ) && '' !== trim( (string) get_option( 'wptm_stripe_secret_key', '' ) );
            $paypal_cid    = (string) get_option( 'wptm_paypal_client_id', '' );
            $paypal_on     = get_option( 'wptm_paypal_enabled', false ) && '' !== trim( $paypal_cid ) && '' !== trim( (string) get_option( 'wptm_paypal_secret', '' ) );

            $booking_deps = array( 'wptm-public' );
            if ( $stripe_on ) {
                wp_enqueue_script( 'wptm-stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
                $booking_deps[] = 'wptm-stripe-js';
            }
            if ( $paypal_on ) {
                $paypal_sdk = add_query_arg(
                    array(
                        'client-id' => rawurlencode( $paypal_cid ),
                        'currency'  => rawurlencode( strtoupper( $currency ) ),
                        'intent'    => 'capture',
                    ),
                    'https://www.paypal.com/sdk/js'
                );
                wp_enqueue_script( 'wptm-paypal-sdk', $paypal_sdk, array(), null, true );
                $booking_deps[] = 'wptm-paypal-sdk';
            }

            wp_enqueue_script( 'wptm-booking', WPTM_PLUGIN_URL . 'assets/js/public/booking-engine.js', $booking_deps, $booking_ver, true );
            wp_localize_script( 'wptm-booking', 'wptmPay', array(
                'stripe' => array(
                    'enabled' => (bool) $stripe_on,
                    'pk'      => $stripe_pk,
                ),
                'paypal' => array(
                    'enabled'  => (bool) $paypal_on,
                    'clientId' => $paypal_cid,
                    'currency' => strtoupper( $currency ),
                ),
                'i18n' => array(
                    'cardError'    => __( 'Please enter your card details.', 'wp-travel-machine' ),
                    'payFailed'    => __( 'Online payment could not be completed.', 'wp-travel-machine' ),
                    'processing'   => __( 'Processing...', 'wp-travel-machine' ),
                ),
            ) );
            $cal_path = WPTM_PLUGIN_DIR . 'assets/js/public/calendar.js';
            $cal_ver  = file_exists( $cal_path ) ? filemtime( $cal_path ) : WPTM_VERSION;
            wp_enqueue_script( 'wptm-calendar', WPTM_PLUGIN_URL . 'assets/js/public/calendar.js', array( 'wptm-public' ), $cal_ver, true );

            // Leaflet location map (bundled locally; map tiles load from
            // OpenStreetMap at runtime — see the readme's External Services note).
            wp_enqueue_style( 'wptm-leaflet', WPTM_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
            wp_enqueue_script( 'wptm-leaflet', WPTM_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
            $map_path = WPTM_PLUGIN_DIR . 'assets/js/public/map.js';
            $map_ver  = file_exists( $map_path ) ? filemtime( $map_path ) : WPTM_VERSION;
            wp_enqueue_script( 'wptm-map', WPTM_PLUGIN_URL . 'assets/js/public/map.js', array( 'wptm-leaflet' ), $map_ver, true );
        }

        // Compare.
        if ( get_option( 'wptm_enable_compare', true ) ) {
            wp_enqueue_script( 'wptm-compare', WPTM_PLUGIN_URL . 'assets/js/public/compare.js', array( 'wptm-public' ), WPTM_VERSION, true );
        }

        // AI Chat.
        if ( get_option( 'wptm_enable_ai', false ) ) {
            $ai_path = WPTM_PLUGIN_DIR . 'assets/js/public/ai-chat.js';
            $ai_ver  = file_exists( $ai_path ) ? filemtime( $ai_path ) : WPTM_VERSION;
            wp_enqueue_script( 'wptm-ai-chat', WPTM_PLUGIN_URL . 'assets/js/public/ai-chat.js', array( 'wptm-public' ), $ai_ver, true );
        }
    }

}
