<?php
/**
 * The single "Upgrade to Pro" page.
 *
 * Pro features are hidden throughout the UI (see wptm_is_pro() gates); the only
 * place the upgrade is advertised is this one dedicated page — the standard,
 * wp.org-friendly freemium pattern. The menu disappears once Pro is active.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom;

if ( ! defined( 'ABSPATH' ) ) exit;

class Pro {

    const SLUG = 'wptm-pro';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ), 99 );
    }

    /**
     * Feature comparison rows for the upgrade table: [ label, in_free, in_pro ].
     *
     * A row with both flags set to null is a category header (rendered as a
     * full-width band by admin/views/pro.php).
     *
     * @return array
     */
    public static function comparison() {
        $cat  = function ( $label ) { return array( $label, null, null ); };
        $free = function ( $label ) { return array( $label, true, true ); };
        $pro  = function ( $label ) { return array( $label, false, true ); };

        return array(
            $cat( __( 'Content', 'journeyloom' ) ),
            $free( __( 'Trips & Hotels — itinerary, pricing tiers, gallery, FAQ, map', 'journeyloom' ) ),
            $free( __( 'Unlimited trips, hotels & bookings', 'journeyloom' ) ),
            $free( __( 'Taxonomies — destinations, activities, types, facilities', 'journeyloom' ) ),
            $free( __( 'Reviews & star ratings', 'journeyloom' ) ),
            $free( __( 'Single pages — gallery + lightbox, map, sticky booking bar', 'journeyloom' ) ),
            $free( __( 'Related trips / hotels', 'journeyloom' ) ),

            $cat( __( 'Booking engine', 'journeyloom' ) ),
            $free( __( 'Availability calendar — date-range & single date', 'journeyloom' ) ),
            $free( __( 'Pricing tiers (Adult/Child/…) & taxes', 'journeyloom' ) ),
            $free( __( 'Session-less cart & server-side price validation', 'journeyloom' ) ),
            $pro( __( 'Coupons / discount codes', 'journeyloom' ) ),
            $pro( __( 'Pickup points — priced add-on at checkout', 'journeyloom' ) ),

            $cat( __( 'Payments', 'journeyloom' ) ),
            $free( __( 'Manual / bank transfer', 'journeyloom' ) ),
            $pro( __( 'Stripe (cards, SCA / 3-D Secure)', 'journeyloom' ) ),
            $pro( __( 'PayPal', 'journeyloom' ) ),
            $pro( __( 'Razorpay', 'journeyloom' ) ),
            $pro( __( 'Printable invoices + company details', 'journeyloom' ) ),

            $cat( __( 'Display & page building', 'journeyloom' ) ),
            $free( __( 'Shortcodes, Gutenberg blocks & Elementor widgets', 'journeyloom' ) ),
            $free( __( 'Grid / List layout + style controls', 'journeyloom' ) ),
            $free( __( 'Search form, AJAX filters & pagination', 'journeyloom' ) ),
            $free( __( 'Wishlist, Compare & enquiry form', 'journeyloom' ) ),

            $cat( __( 'System & admin', 'journeyloom' ) ),
            $free( __( 'Dashboard, bookings management & reports', 'journeyloom' ) ),
            $free( __( 'Branded emails, demo importer & setup wizard', 'journeyloom' ) ),
            $free( __( 'SEO schema, REST API & developer hooks', 'journeyloom' ) ),

            $cat( __( 'AI — runs on your own provider API key', 'journeyloom' ) ),
            $free( __( 'Natural-language search', 'journeyloom' ) ),
            $free( __( 'Chat assistant — conversational text replies', 'journeyloom' ) ),
            $pro( __( 'Chat — inline bookable trip/hotel cards', 'journeyloom' ) ),
            $pro( __( 'Smart recommendations — bookable cards + score', 'journeyloom' ) ),
            $pro( __( 'AI Trip Builder — write a whole trip in one click', 'journeyloom' ) ),
            $pro( __( 'AI itinerary generator', 'journeyloom' ) ),
            $pro( __( 'AI customer-reply drafting', 'journeyloom' ) ),
            $pro( __( 'AI Style generator (blocks & Elementor)', 'journeyloom' ) ),
        );
    }

    /**
     * Add the "Upgrade" submenu — only while Pro is inactive.
     */
    public function register_menu() {
        if ( wptm_is_pro() ) {
            return; // Nothing to upgrade once Pro is active.
        }
        add_submenu_page(
            'wptm-dashboard',
            __( 'Upgrade to Pro', 'journeyloom' ),
            '<span class="wptm-pro-menu">' . __( 'Upgrade', 'journeyloom' ) . ' ✦</span>',
            'manage_options',
            self::SLUG,
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        include WPTM_PLUGIN_DIR . 'admin/views/pro.php';
    }
}
