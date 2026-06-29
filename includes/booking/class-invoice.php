<?php
/**
 * Invoice generator + printable invoice renderer.
 *
 * Outputs a self-contained, print-ready HTML invoice for a single booking via
 * admin-post.php. Saving to PDF is handled by the browser's native print
 * dialog (no third-party PDF dependency required).
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Booking;

if ( ! defined( 'ABSPATH' ) ) exit;

class Invoice {

    /**
     * admin-post action slug.
     */
    const ACTION = 'wptm_invoice';

    public function __construct() {
        add_action( 'admin_post_' . self::ACTION, array( $this, 'render' ) );
    }

    /**
     * Build the print-invoice URL for a booking (nonce-protected).
     *
     * @param int  $booking_id Booking row ID.
     * @param bool $auto       Trigger the print dialog automatically on load.
     * @return string
     */
    public static function url( $booking_id, $auto = false ) {
        $args = array(
            'action'     => self::ACTION,
            'booking_id' => (int) $booking_id,
            '_wpnonce'   => wp_create_nonce( self::ACTION . '_' . (int) $booking_id ),
        );
        if ( $auto ) {
            $args['auto'] = 1;
        }
        return add_query_arg( $args, admin_url( 'admin-post.php' ) );
    }

    /**
     * Company / business details used on the invoice header, with sensible
     * fallbacks to the site identity and email settings.
     *
     * @return array
     */
    public static function business() {
        return array(
            'name'    => get_option( 'wptm_invoice_company', '' ) ?: ( get_option( 'wptm_email_from_name', '' ) ?: get_bloginfo( 'name' ) ),
            'address' => get_option( 'wptm_invoice_address', '' ),
            'email'   => get_option( 'wptm_invoice_email', '' ) ?: get_option( 'wptm_booking_email', get_option( 'admin_email' ) ),
            'phone'   => get_option( 'wptm_invoice_phone', '' ),
            'tax'     => get_option( 'wptm_invoice_tax_number', '' ),
            'logo'    => get_option( 'wptm_invoice_logo', '' ),
            'prefix'  => get_option( 'wptm_invoice_prefix', 'INV-' ),
            'notes'   => get_option( 'wptm_invoice_notes', '' ),
        );
    }

    /**
     * Format a monetary amount honouring the currency symbol + position.
     *
     * @param float $amount Amount.
     * @return string
     */
    public static function money( $amount ) {
        $sym = get_option( 'wptm_currency_symbol', '$' );
        $pos = get_option( 'wptm_currency_position', 'before' );
        $num = number_format( (float) $amount, 2 );
        return 'after' === $pos ? $num . $sym : $sym . $num;
    }

    /**
     * Render the standalone printable invoice and exit.
     */
    public function render() {
        if ( ! wptm_is_pro() ) {
            wp_die( esc_html__( 'Invoices are a Pro feature. Please upgrade to WP Travel Machine Pro.', 'wp-travel-machine' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to view invoices.', 'wp-travel-machine' ) );
        }

        $id = absint( $_GET['booking_id'] ?? 0 );
        if ( ! $id || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ?? '' ), self::ACTION . '_' . $id ) ) {
            wp_die( esc_html__( 'Invalid or expired invoice link.', 'wp-travel-machine' ) );
        }

        $booking = BookingEngine::get_booking( $id );
        if ( ! $booking ) {
            wp_die( esc_html__( 'Booking not found.', 'wp-travel-machine' ) );
        }

        global $wpdb;

        // Pricing line items (fall back to a single line if no tiers stored).
        $tiers_meta = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}wptm_booking_meta WHERE booking_id = %d AND meta_key = %s LIMIT 1",
            $id,
            '_pricing_tiers'
        ) );
        $tiers = $tiers_meta ? maybe_unserialize( $tiers_meta ) : array();
        if ( ! is_array( $tiers ) || empty( $tiers ) ) {
            $tiers = array( array(
                'label' => get_the_title( $booking->item_id ) ?: __( 'Booking', 'wp-travel-machine' ),
                'qty'   => max( 1, (int) $booking->travelers_count ),
                'price' => (float) ( $booking->total_price + $booking->discount_amount ) / max( 1, (int) $booking->travelers_count ),
            ) );
        }

        $business = self::business();
        $item     = get_post( $booking->item_id );
        $subtotal = (float) $booking->total_price + (float) $booking->discount_amount;
        $auto     = ! empty( $_GET['auto'] );

        // Expose to the template, then render and stop — this is a full document.
        require WPTM_PLUGIN_DIR . 'templates/invoice.php';
        exit;
    }
}
