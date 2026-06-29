<?php
/**
 * The single "Upgrade to Pro" page.
 *
 * Pro features are hidden throughout the UI (see wptm_is_pro() gates); the only
 * place the upgrade is advertised is this one dedicated page — the standard,
 * wp.org-friendly freemium pattern. The menu disappears once Pro is active.
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine;

if ( ! defined( 'ABSPATH' ) ) exit;

class Pro {

    const SLUG = 'wptm-pro';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ), 99 );
    }

    /**
     * Feature comparison rows for the upgrade table: [ label, in_free, in_pro ].
     *
     * @return array
     */
    public static function comparison() {
        return array(
            array( __( 'Trips & Hotels — itineraries, pricing tiers, gallery, FAQ', 'wp-travel-machine' ), true, true ),
            array( __( 'Search, filters & pagination', 'wp-travel-machine' ), true, true ),
            array( __( 'Reviews, Wishlist & Compare', 'wp-travel-machine' ), true, true ),
            array( __( 'Gutenberg blocks & Elementor widgets', 'wp-travel-machine' ), true, true ),
            array( __( 'Manual / Bank Transfer payments', 'wp-travel-machine' ), true, true ),
            array( __( 'Booking management, drawer & emails', 'wp-travel-machine' ), true, true ),
            array( __( 'AI Trip Builder — write whole trips in one click', 'wp-travel-machine' ), false, true ),
            array( __( 'AI customer-reply drafting in the booking screen', 'wp-travel-machine' ), false, true ),
            array( __( 'AI concierge chat, recommendations & natural-language search', 'wp-travel-machine' ), false, true ),
            array( __( 'Stripe & PayPal online checkout', 'wp-travel-machine' ), false, true ),
            array( __( 'Printable, branded invoices', 'wp-travel-machine' ), false, true ),
            array( __( 'Coupons & discounts', 'wp-travel-machine' ), false, true ),
            array( __( 'Pickup points — free or paid, per traveler', 'wp-travel-machine' ), false, true ),
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
            __( 'Upgrade to Pro', 'wp-travel-machine' ),
            '<span class="wptm-pro-menu">' . __( 'Upgrade', 'wp-travel-machine' ) . ' ✦</span>',
            'manage_options',
            self::SLUG,
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        include WPTM_PLUGIN_DIR . 'admin/views/pro.php';
    }
}
