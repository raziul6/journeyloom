<?php
namespace JourneyLoom\Pub;

if ( ! defined( 'ABSPATH' ) ) exit;

class PublicHandler {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        // The AI assistant widget is provided by the Pro add-on.
    }

    public function enqueue_assets() {
        $css_path = WPTM_PLUGIN_DIR . 'assets/css/public.css';
        $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : WPTM_VERSION;
        wp_enqueue_style( 'wptm-public', WPTM_PLUGIN_URL . 'assets/css/public.css', array(), $css_ver );

        // Admin-configurable colours (Settings → Appearance) as :root overrides.
        $custom_colors = $this->custom_colors_css();
        if ( '' !== $custom_colors ) {
            wp_add_inline_style( 'wptm-public', $custom_colors );
        }

        // Font — Inter (self-hosted inside the plugin, no external/CDN request).
        // Filter to disable, or pass your own stylesheet URL via 'wptm_fonts_url'.
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
            'userId'   => get_current_user_id(),
            'enableWishlist' => (bool) get_option( 'wptm_enable_wishlist', true ),
            'enableCompare'  => (bool) get_option( 'wptm_enable_compare', true ),
            'paginationType' => get_option( 'wptm_pagination_type', 'pagination' ),
            'itemsPerPage'   => (int) get_option( 'wptm_items_per_page', 12 ),
            'pluginUrl' => WPTM_PLUGIN_URL,
            'i18n' => array(
                'addedToWishlist' => __( 'Added to wishlist!', 'byteflows-travel-hotel-booking' ),
                'removedFromWishlist' => __( 'Removed from wishlist.', 'byteflows-travel-hotel-booking' ),
                'addedToCart' => __( 'Added to cart!', 'byteflows-travel-hotel-booking' ),
                'bookNow' => __( 'Book Now', 'byteflows-travel-hotel-booking' ),
                'loading' => __( 'Loading...', 'byteflows-travel-hotel-booking' ),
                'noResults' => __( 'No results found. Try adjusting your filters.', 'byteflows-travel-hotel-booking' ),
                'loadMore' => __( 'Load More', 'byteflows-travel-hotel-booking' ),
                'prev' => __( 'Previous', 'byteflows-travel-hotel-booking' ),
                'next' => __( 'Next', 'byteflows-travel-hotel-booking' ),
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
        $wl_path = WPTM_PLUGIN_DIR . 'assets/js/public/wishlist.js';
        $wl_ver  = file_exists( $wl_path ) ? filemtime( $wl_path ) : WPTM_VERSION;
        wp_enqueue_script( 'wptm-wishlist', WPTM_PLUGIN_URL . 'assets/js/public/wishlist.js', array( 'wptm-public' ), $wl_ver, true );

        // Single trip/hotel pages.
        if ( is_singular( array( 'wptm_trip', 'wptm_hotel' ) ) ) {
            $booking_path = WPTM_PLUGIN_DIR . 'assets/js/public/booking-engine.js';
            $booking_ver  = file_exists( $booking_path ) ? filemtime( $booking_path ) : WPTM_VERSION;

            // Online gateway client SDKs — loaded only when the gateway is fully
            // configured, and made dependencies of the booking script so their
            // globals (Stripe / paypal) exist before it runs. The online gateways
            // themselves live in the Pro add-on, so their SDKs load only with Pro.
            $pro           = function_exists( 'wptm_is_pro' ) && wptm_is_pro();
            $currency      = get_option( 'wptm_currency', 'USD' );
            $stripe_pk     = (string) get_option( 'wptm_stripe_publishable_key', '' );
            $stripe_on     = $pro && get_option( 'wptm_stripe_enabled', false ) && '' !== trim( $stripe_pk ) && '' !== trim( (string) get_option( 'wptm_stripe_secret_key', '' ) );
            $paypal_cid    = (string) get_option( 'wptm_paypal_client_id', '' );
            $paypal_on     = $pro && get_option( 'wptm_paypal_enabled', false ) && '' !== trim( $paypal_cid ) && '' !== trim( (string) get_option( 'wptm_paypal_secret', '' ) );
            $razorpay_kid  = (string) get_option( 'wptm_razorpay_key_id', '' );
            $razorpay_on   = $pro && get_option( 'wptm_razorpay_enabled', false ) && '' !== trim( $razorpay_kid ) && '' !== trim( (string) get_option( 'wptm_razorpay_key_secret', '' ) );

            $booking_deps = array( 'wptm-public' );
            if ( $stripe_on ) {
                wp_enqueue_script( 'wptm-stripe-js', 'https://js.stripe.com/v3/', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party payment SDK; versioned by its own URL, no ?ver needed.
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
                wp_enqueue_script( 'wptm-paypal-sdk', $paypal_sdk, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party payment SDK; versioned by its own URL, no ?ver needed.
                $booking_deps[] = 'wptm-paypal-sdk';
            }
            if ( $razorpay_on ) {
                wp_enqueue_script( 'wptm-razorpay-js', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party payment SDK; versioned by its own URL, no ?ver needed.
                $booking_deps[] = 'wptm-razorpay-js';
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
                'razorpay' => array(
                    'enabled' => (bool) $razorpay_on,
                    'keyId'   => $razorpay_kid,
                ),
                'i18n' => array(
                    'cardError'    => __( 'Please enter your card details.', 'byteflows-travel-hotel-booking' ),
                    'payFailed'    => __( 'Online payment could not be completed.', 'byteflows-travel-hotel-booking' ),
                    'processing'   => __( 'Processing...', 'byteflows-travel-hotel-booking' ),
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

        // The AI chat script is enqueued by the Pro add-on when active.
    }

    /**
     * Build the :root CSS overrides for the admin-chosen colours. Returns an
     * empty string when nothing has been customised (so the stylesheet defaults
     * apply). Changing the primary colour also derives matching hover/light/soft
     * shades and the gradient so the brand stays cohesive.
     */
    private function custom_colors_css() {
        $primary  = sanitize_hex_color( (string) get_option( 'wptm_color_primary', '' ) );
        $discount = sanitize_hex_color( (string) get_option( 'wptm_color_discount_ribbon', '' ) );
        $featured = sanitize_hex_color( (string) get_option( 'wptm_color_featured_ribbon', '' ) );
        $icon     = sanitize_hex_color( (string) get_option( 'wptm_color_icon', '' ) );

        $vars = array();
        if ( $primary ) {
            $light = $this->shade( $primary, 0.18 );
            $vars['--wptm-primary']       = $primary;
            $vars['--wptm-primary-hover'] = $this->shade( $primary, -0.12 );
            $vars['--wptm-primary-light'] = $light;
            $vars['--wptm-secondary']     = $this->shade( $primary, 0.24 );
            $vars['--wptm-primary-soft']  = $this->rgba( $primary, 0.08 );
            $vars['--wptm-primary-ring']  = $this->rgba( $primary, 0.18 );
            $vars['--wptm-gradient']      = sprintf( 'linear-gradient(135deg, %s, %s)', $primary, $light );
        }
        if ( $discount ) {
            $vars['--wptm-ribbon-discount'] = $discount;
        }
        if ( $featured ) {
            $vars['--wptm-ribbon-featured'] = $featured;
        }
        if ( $icon ) {
            $vars['--wptm-icon-color'] = $icon;
        }

        if ( empty( $vars ) ) {
            return '';
        }
        $css = ':root{';
        foreach ( $vars as $key => $value ) {
            $css .= $key . ':' . $value . ';';
        }
        return $css . '}';
    }

    /**
     * Lighten (positive $amount) or darken (negative) a hex colour.
     *
     * @param string $hex    #rrggbb
     * @param float  $amount -1..1
     * @return string #rrggbb
     */
    private function shade( $hex, $amount ) {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $out = '#';
        for ( $i = 0; $i < 3; $i++ ) {
            $c = hexdec( substr( $hex, $i * 2, 2 ) );
            if ( $amount >= 0 ) {
                $c = $c + ( 255 - $c ) * $amount;
            } else {
                $c = $c * ( 1 + $amount );
            }
            $out .= str_pad( dechex( max( 0, min( 255, (int) round( $c ) ) ) ), 2, '0', STR_PAD_LEFT );
        }
        return $out;
    }

    /**
     * Convert a hex colour to an rgba() string.
     */
    private function rgba( $hex, $alpha ) {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return sprintf(
            'rgba(%d,%d,%d,%s)',
            hexdec( substr( $hex, 0, 2 ) ),
            hexdec( substr( $hex, 2, 2 ) ),
            hexdec( substr( $hex, 4, 2 ) ),
            rtrim( rtrim( number_format( (float) $alpha, 2 ), '0' ), '.' )
        );
    }

}
