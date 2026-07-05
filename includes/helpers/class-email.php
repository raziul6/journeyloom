<?php
namespace JourneyLoom\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Transactional email for bookings.
 *
 * Sends a branded HTML confirmation to the customer and a notification to the
 * store admin when a booking is created, and a status-update email when a
 * booking's status changes. All copy (subjects, from name, footer) is
 * configurable under Settings → Emails, and every email is rendered through one
 * shared, responsive layout (see {@see wrap()}).
 */
class Email {

    public function __construct() {
        add_action( 'wptm_booking_created', array( $this, 'send_booking_confirmation' ), 10, 2 );
        add_action( 'wptm_booking_status_changed', array( $this, 'send_status_update' ), 10, 2 );
        add_action( 'wptm_payment_completed', array( $this, 'send_payment_received' ), 10, 2 );
        add_action( 'wp_ajax_wptm_send_test_email', array( $this, 'send_test_email' ) );
        add_action( 'wp_ajax_wptm_send_reply', array( $this, 'send_reply' ) );
    }

    /**
     * Email the customer a payment receipt once their payment completes.
     *
     * Fires on wptm_payment_completed, so it covers every paid path — the Stripe
     * webhook, the browser-side Stripe confirm, and PayPal capture. Sends at most
     * once per booking, guarded by a booking-meta flag.
     *
     * @param int    $booking_id Booking row id.
     * @param string $gateway_id The gateway that completed the payment.
     */
    public function send_payment_received( $booking_id, $gateway_id = '' ) {
        if ( ! $this->enabled( 'customer' ) ) {
            return;
        }
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking || empty( $booking->customer_email ) ) {
            return;
        }

        // Send only once, even if the completion hook fires more than once.
        // These read/write the plugin's own wptm_booking_meta table (no core API,
        // uncacheable transactional flag); the table name comes from $wpdb->prefix.
        global $wpdb;
        $meta = $wpdb->prefix . 'wptm_booking_meta';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sent = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_id FROM {$meta} WHERE booking_id = %d AND meta_key = %s LIMIT 1",
            $booking_id,
            '_payment_email_sent'
        ) );
        if ( $sent ) {
            return;
        }
        $wpdb->insert( $meta, array(
            'booking_id' => $booking_id,
            'meta_key'   => '_payment_email_sent', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- querying the plugin's own indexed meta; low-frequency query.
            'meta_value' => current_time( 'mysql' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- querying the plugin's own indexed meta; low-frequency query.
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        $subject = $this->parse( __( 'Payment received for booking {booking_number}', 'byteflows-travel-hotel-booking' ), $booking );
        $this->mail( $booking->customer_email, $subject, $this->build_payment_email( $booking ) );
    }

    /**
     * AJAX: send a custom admin-composed reply to the customer, wrapped in the
     * shared branded layout. Used by the booking drawer's reply box.
     */
    public function send_reply() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'byteflows-travel-hotel-booking' ) ) );
        }

        $id      = absint( $_POST['booking_id'] ?? 0 );
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $id );
        if ( ! $booking || empty( $booking->customer_email ) ) {
            wp_send_json_error( array( 'message' => __( 'This booking has no customer email.', 'byteflows-travel-hotel-booking' ) ) );
        }

        $subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
        $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
        if ( '' === trim( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Write a message before sending.', 'byteflows-travel-hotel-booking' ) ) );
        }
        if ( '' === trim( $subject ) ) {
            /* translators: %s: booking reference number. */
            $subject = sprintf( __( 'Regarding your booking %s', 'byteflows-travel-hotel-booking' ), $booking->booking_number );
        }

        $body = $this->text_to_paragraphs( $message );
        $html = $this->wrap( array(
            'title'     => __( 'A message about your booking', 'byteflows-travel-hotel-booking' ),
            'preheader' => $subject,
            'badge'     => $this->status_badge( $booking ),
            'body'      => $body,
        ) );

        if ( $this->mail( $booking->customer_email, $subject, $html ) ) {
            /* translators: %s: customer email address the reply was sent to. */
            wp_send_json_success( array( 'message' => sprintf( __( 'Reply sent to %s.', 'byteflows-travel-hotel-booking' ), $booking->customer_email ) ) );
        }
        wp_send_json_error( array( 'message' => __( 'wp_mail() returned false — mail is not configured on this server.', 'byteflows-travel-hotel-booking' ) ) );
    }

    /**
     * Convert a plain-text message into escaped, blank-line-separated paragraphs.
     */
    private function text_to_paragraphs( $text ) {
        $html = '';
        foreach ( preg_split( '/\n{2,}/', trim( (string) $text ) ) as $para ) {
            $para = trim( $para );
            if ( '' !== $para ) {
                $html .= '<p style="margin:0 0 14px;">' . nl2br( esc_html( $para ) ) . '</p>';
            }
        }
        return $html;
    }

    /* ----------------------------------------------------------- public sends */

    public function send_booking_confirmation( $booking_id, $data ) {
        // Always work from the stored row so templates get a consistent object
        // (the $data passed by the hook is an associative array).
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking ) {
            $booking = (object) $data;
        }

        // Customer confirmation.
        if ( $this->enabled( 'customer' ) && ! empty( $booking->customer_email ) ) {
            $subject = $this->parse(
                get_option( 'wptm_email_customer_subject', __( 'Thanks for your booking, {customer_name}! ({booking_number})', 'byteflows-travel-hotel-booking' ) ),
                $booking
            );
            $this->mail( $booking->customer_email, $subject, $this->build_customer_email( $booking ) );
        }

        // Admin notification.
        if ( $this->enabled( 'admin' ) ) {
            $admin_email = get_option( 'wptm_booking_email', get_option( 'admin_email' ) );
            $subject     = $this->parse( __( 'New booking received — {booking_number}', 'byteflows-travel-hotel-booking' ), $booking );
            $this->mail( $admin_email, $subject, $this->build_admin_email( $booking ) );
        }
    }

    public function send_status_update( $booking_id, $status ) {
        if ( ! $this->enabled( 'customer' ) ) {
            return;
        }
        $booking = \JourneyLoom\Booking\BookingEngine::get_booking( $booking_id );
        if ( ! $booking || empty( $booking->customer_email ) ) {
            return;
        }
        $subject = $this->parse(
            /* translators: 1: booking number placeholder, 2: new booking status (e.g. Confirmed). */
            sprintf( __( 'Your booking %1$s is now %2$s', 'byteflows-travel-hotel-booking' ), '{booking_number}', ucfirst( $status ) ),
            $booking
        );
        $this->mail( $booking->customer_email, $subject, $this->build_status_email( $booking, $status ) );
    }

    /**
     * AJAX: send a test email so the admin can confirm delivery works.
     */
    public function send_test_email() {
        check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'byteflows-travel-hotel-booking' ) ) );
        }
        $to = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! $to ) {
            $to = get_option( 'wptm_booking_email', get_option( 'admin_email' ) );
        }
        $body = $this->wrap( array(
            'title'     => __( 'Test email', 'byteflows-travel-hotel-booking' ),
            'preheader' => __( 'Your email settings are working.', 'byteflows-travel-hotel-booking' ),
            'badge'     => array( 'label' => __( 'Success', 'byteflows-travel-hotel-booking' ), 'color' => '#10b981' ),
            'body'      => '<p style="margin:0 0 14px;">' . esc_html__( 'If you can read this, Byteflows Travel can send emails from your site. 🎉', 'byteflows-travel-hotel-booking' ) . '</p>',
        ) );
        $sent = $this->mail( $to, __( 'Byteflows Travel — test email', 'byteflows-travel-hotel-booking' ), $body );

        if ( $sent ) {
            /* translators: %s: recipient email address. */
            wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent to %s.', 'byteflows-travel-hotel-booking' ), $to ) ) );
        }
        wp_send_json_error( array( 'message' => __( 'wp_mail() returned false — your server/SMTP is not configured to send mail.', 'byteflows-travel-hotel-booking' ) ) );
    }

    /* -------------------------------------------------------------- composers */

    private function build_customer_email( $booking ) {
        $badge = $this->status_badge( $booking );
        /* translators: %s: customer first/full name. */
        $body  = '<p style="margin:0 0 8px;font-size:16px;">' . sprintf( esc_html__( 'Hi %s,', 'byteflows-travel-hotel-booking' ), esc_html( $booking->customer_name ) ) . '</p>';
        $body .= '<p style="margin:0 0 18px;color:#5b5048;">' . esc_html__( 'Thank you for your booking. Here are your details:', 'byteflows-travel-hotel-booking' ) . '</p>';
        $body .= $this->details_table( $booking );

        // Bank transfer instructions when payment is still due.
        $instructions = trim( (string) get_option( 'wptm_bank_instructions', '' ) );
        if ( 'manual' === ( $booking->payment_method ?? '' ) && 'paid' !== ( $booking->payment_status ?? '' ) && '' !== $instructions ) {
            $body .= '<div style="margin:18px 0 0;padding:16px 18px;background:#fff7f4;border:1px solid #ffd9cc;border-radius:12px;">';
            $body .= '<strong style="display:block;margin-bottom:6px;color:#c2410c;">' . esc_html__( 'Payment instructions', 'byteflows-travel-hotel-booking' ) . '</strong>';
            $body .= '<div style="color:#7c4a37;font-size:14px;line-height:1.6;">' . nl2br( esc_html( $instructions ) ) . '</div></div>';
        }

        $url = $this->booking_link( $booking );
        return $this->wrap( array(
            'title'     => __( 'Booking received', 'byteflows-travel-hotel-booking' ),
            'preheader' => $this->parse( __( 'Your booking {booking_number} has been received.', 'byteflows-travel-hotel-booking' ), $booking ),
            'badge'     => $badge,
            'body'      => $body,
            'button'    => $url ? array( 'url' => $url, 'label' => __( 'View your booking', 'byteflows-travel-hotel-booking' ) ) : null,
        ) );
    }

    private function build_admin_email( $booking ) {
        $body  = '<p style="margin:0 0 18px;font-size:16px;">' . esc_html__( 'A new booking has just come in.', 'byteflows-travel-hotel-booking' ) . '</p>';
        $body .= $this->details_table( $booking, true );
        $url   = admin_url( 'admin.php?page=wptm-bookings' );
        return $this->wrap( array(
            'title'     => __( 'New booking', 'byteflows-travel-hotel-booking' ),
            'preheader' => $this->parse( __( 'New booking {booking_number} from {customer_name}.', 'byteflows-travel-hotel-booking' ), $booking ),
            'badge'     => $this->status_badge( $booking ),
            'body'      => $body,
            'button'    => array( 'url' => $url, 'label' => __( 'Manage booking', 'byteflows-travel-hotel-booking' ) ),
        ) );
    }

    private function build_status_email( $booking, $status ) {
        $colors = array( 'confirmed' => '#10b981', 'completed' => '#0ea5e9', 'cancelled' => '#ef4444', 'pending' => '#f59e0b' );
        /* translators: %s: customer first/full name. */
        $body   = '<p style="margin:0 0 8px;font-size:16px;">' . sprintf( esc_html__( 'Hi %s,', 'byteflows-travel-hotel-booking' ), esc_html( $booking->customer_name ) ) . '</p>';
        /* translators: %s: new booking status (e.g. Confirmed), wrapped in bold. */
        $body  .= '<p style="margin:0 0 18px;color:#5b5048;">' . sprintf( esc_html__( 'The status of your booking has been updated to %s.', 'byteflows-travel-hotel-booking' ), '<strong>' . esc_html( ucfirst( $status ) ) . '</strong>' ) . '</p>';
        $body  .= $this->details_table( $booking );
        $url    = $this->booking_link( $booking );
        return $this->wrap( array(
            'title'     => __( 'Booking update', 'byteflows-travel-hotel-booking' ),
            /* translators: %s: new booking status (e.g. Confirmed). */
            'preheader' => $this->parse( sprintf( __( 'Booking {booking_number} is now %s.', 'byteflows-travel-hotel-booking' ), ucfirst( $status ) ), $booking ),
            'badge'     => array( 'label' => ucfirst( $status ), 'color' => $colors[ $status ] ?? '#6b7280' ),
            'body'      => $body,
            'button'    => $url ? array( 'url' => $url, 'label' => __( 'View your booking', 'byteflows-travel-hotel-booking' ) ) : null,
        ) );
    }

    private function build_payment_email( $booking ) {
        $sym  = get_option( 'wptm_currency_symbol', '$' );
        /* translators: %s: customer first/full name. */
        $body = '<p style="margin:0 0 8px;font-size:16px;">' . sprintf( esc_html__( 'Hi %s,', 'byteflows-travel-hotel-booking' ), esc_html( $booking->customer_name ) ) . '</p>';
        $body .= '<p style="margin:0 0 18px;color:#5b5048;">' . sprintf(
            /* translators: %s: amount paid, formatted with the currency symbol. */
            esc_html__( 'We’ve received your payment of %s — thank you! Your booking is now confirmed.', 'byteflows-travel-hotel-booking' ),
            '<strong>' . esc_html( $sym . number_format( (float) $booking->total_price, 2 ) ) . '</strong>'
        ) . '</p>';
        $body .= $this->details_table( $booking );

        if ( ! empty( $booking->transaction_id ) ) {
            $body .= '<p style="margin:14px 0 0;color:#9a8f86;font-size:12.5px;">'
                /* translators: %s: payment gateway transaction reference. */
                . sprintf( esc_html__( 'Transaction reference: %s', 'byteflows-travel-hotel-booking' ), esc_html( $booking->transaction_id ) ) . '</p>';
        }

        $url = $this->booking_link( $booking );
        return $this->wrap( array(
            'title'     => __( 'Payment received', 'byteflows-travel-hotel-booking' ),
            'preheader' => $this->parse( __( 'Your payment for {booking_number} was received.', 'byteflows-travel-hotel-booking' ), $booking ),
            'badge'     => array( 'label' => __( 'Paid', 'byteflows-travel-hotel-booking' ), 'color' => '#10b981' ),
            'body'      => $body,
            'button'    => $url ? array( 'url' => $url, 'label' => __( 'View your booking', 'byteflows-travel-hotel-booking' ) ) : null,
        ) );
    }

    /* ----------------------------------------------------------- view helpers */

    private function details_table( $booking, $include_customer = false ) {
        $sym  = get_option( 'wptm_currency_symbol', '$' );
        $rows = array();

        $rows[] = array( __( 'Booking number', 'byteflows-travel-hotel-booking' ), esc_html( $booking->booking_number ?? '' ) );
        $rows[] = array( __( 'Item', 'byteflows-travel-hotel-booking' ), esc_html( get_the_title( $booking->item_id ?? 0 ) ) );

        $dates = trim( (string) ( $booking->check_in ?? '' ) );
        if ( ! empty( $booking->check_out ) ) {
            $dates .= ' → ' . $booking->check_out;
        }
        if ( '' !== $dates ) {
            $rows[] = array( __( 'Date', 'byteflows-travel-hotel-booking' ), esc_html( $dates ) );
        }
        $rows[] = array( __( 'Travelers', 'byteflows-travel-hotel-booking' ), intval( $booking->travelers_count ?? 1 ) );

        if ( ! empty( $booking->payment_method ) ) {
            $rows[] = array( __( 'Payment method', 'byteflows-travel-hotel-booking' ), esc_html( ucwords( str_replace( '_', ' ', $booking->payment_method ) ) ) );
        }
        if ( ! empty( $booking->payment_status ) ) {
            $rows[] = array( __( 'Payment status', 'byteflows-travel-hotel-booking' ), esc_html( ucfirst( $booking->payment_status ) ) );
        }

        if ( $include_customer ) {
            $rows[] = array( __( 'Customer', 'byteflows-travel-hotel-booking' ), esc_html( $booking->customer_name ?? '' ) );
            $rows[] = array( __( 'Email', 'byteflows-travel-hotel-booking' ), esc_html( $booking->customer_email ?? '' ) );
            if ( ! empty( $booking->customer_phone ) ) {
                $rows[] = array( __( 'Phone', 'byteflows-travel-hotel-booking' ), esc_html( $booking->customer_phone ) );
            }
        }

        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:6px 0;border:1px solid #efe9e5;border-radius:12px;overflow:hidden;">';
        $i = 0;
        foreach ( $rows as $row ) {
            $bg = ( $i % 2 ) ? '#ffffff' : '#faf7f5';
            $html .= '<tr style="background:' . $bg . ';">';
            $html .= '<td style="padding:11px 16px;font-size:13px;color:#8a7d73;width:42%;">' . esc_html( $row[0] ) . '</td>';
            $html .= '<td style="padding:11px 16px;font-size:14px;color:#2b2017;font-weight:600;">' . $row[1] . '</td></tr>';
            $i++;
        }
        // Total row, emphasised.
        $html .= '<tr style="background:#1b1512;"><td style="padding:14px 16px;font-size:14px;color:#fff;">' . esc_html__( 'Total', 'byteflows-travel-hotel-booking' ) . '</td>';
        $html .= '<td style="padding:14px 16px;font-size:18px;color:#ff8a3d;font-weight:800;">' . esc_html( $sym . number_format( (float) ( $booking->total_price ?? 0 ), 2 ) ) . '</td></tr>';
        $html .= '</table>';
        return $html;
    }

    private function status_badge( $booking ) {
        $paid = 'paid' === ( $booking->payment_status ?? '' );
        if ( $paid ) {
            return array( 'label' => __( 'Paid', 'byteflows-travel-hotel-booking' ), 'color' => '#10b981' );
        }
        if ( 'manual' === ( $booking->payment_method ?? '' ) ) {
            return array( 'label' => __( 'Awaiting payment', 'byteflows-travel-hotel-booking' ), 'color' => '#f59e0b' );
        }
        return array( 'label' => __( 'Pending', 'byteflows-travel-hotel-booking' ), 'color' => '#f59e0b' );
    }

    private function booking_link( $booking ) {
        $url = function_exists( 'wptm_get_page_url' ) ? wptm_get_page_url( 'confirmation' ) : '';
        if ( ! $url ) {
            $url = home_url( '/booking-confirmation/' );
        }
        return add_query_arg( 'booking', (int) ( $booking->id ?? 0 ), $url );
    }

    /**
     * The one shared, responsive email layout. Every email passes its content
     * through here so they all look consistent and on-brand.
     *
     * @param array $a title, preheader, badge[label,color], body (html), button[url,label]
     */
    private function wrap( $a ) {
        $site    = get_bloginfo( 'name' );
        $from    = get_option( 'wptm_email_from_name', $site );
        $accent1 = '#fd4621';
        $accent2 = '#ff8a3d';
        $footer  = trim( (string) get_option( 'wptm_email_footer_text', '' ) );
        $title   = $a['title'] ?? $site;
        $pre     = $a['preheader'] ?? '';
        $badge   = $a['badge'] ?? null;
        $button  = $a['button'] ?? null;

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
<title><?php echo esc_html( $title ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f1ece8;-webkit-font-smoothing:antialiased;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<span style="display:none!important;visibility:hidden;opacity:0;height:0;width:0;overflow:hidden;mso-hide:all;"><?php echo esc_html( $pre ); ?></span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1ece8;padding:32px 14px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 24px 48px -28px rgba(70,30,10,.45);">
    <!-- Header -->
    <tr><td style="background:linear-gradient(135deg,<?php echo esc_attr( $accent1 ); ?>,<?php echo esc_attr( $accent2 ); ?>);padding:34px 36px;">
        <table role="presentation" width="100%"><tr>
            <td style="color:#fff;font-size:18px;font-weight:800;letter-spacing:.2px;">✈&nbsp; <?php echo esc_html( $site ); ?></td>
            <?php if ( $badge ) : ?>
            <td align="right"><span style="display:inline-block;background:rgba(255,255,255,.22);color:#fff;font-size:12px;font-weight:700;padding:6px 12px;border-radius:999px;"><?php echo esc_html( $badge['label'] ); ?></span></td>
            <?php endif; ?>
        </tr></table>
        <div style="margin-top:18px;color:#fff;font-size:26px;font-weight:800;line-height:1.2;"><?php echo esc_html( $title ); ?></div>
    </td></tr>
    <!-- Body -->
    <tr><td style="padding:32px 36px;color:#2b2017;font-size:15px;line-height:1.6;">
        <?php echo wp_kses_post( $a['body'] ); ?>
        <?php if ( $button ) : ?>
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 4px;"><tr><td style="border-radius:12px;background:linear-gradient(135deg,<?php echo esc_attr( $accent1 ); ?>,<?php echo esc_attr( $accent2 ); ?>);">
            <a href="<?php echo esc_url( $button['url'] ); ?>" style="display:inline-block;padding:13px 28px;color:#fff;font-weight:700;font-size:15px;text-decoration:none;border-radius:12px;"><?php echo esc_html( $button['label'] ); ?> &rarr;</a>
        </td></tr></table>
        <?php endif; ?>
    </td></tr>
    <!-- Footer -->
    <tr><td style="background:#faf7f5;border-top:1px solid #efe9e5;padding:24px 36px;color:#9a8f86;font-size:12.5px;line-height:1.6;">
        <?php if ( '' !== $footer ) : ?>
            <div style="margin-bottom:8px;color:#7c7068;"><?php echo nl2br( esc_html( $footer ) ); ?></div>
        <?php endif; ?>
        <div><strong style="color:#5b5048;"><?php echo esc_html( $from ); ?></strong></div>
        <?php /* translators: 1: current year, 2: site name. */ ?>
        <div><?php echo esc_html( sprintf( __( '© %1$s %2$s. All rights reserved.', 'byteflows-travel-hotel-booking' ), gmdate( 'Y' ), $site ) ); ?></div>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
        <?php
        return apply_filters( 'wptm_email_html', ob_get_clean(), $a );
    }

    /* --------------------------------------------------------------- plumbing */

    /**
     * Send an HTML email with the configured From identity.
     */
    private function mail( $to, $subject, $html ) {
        if ( ! $to ) {
            return false;
        }
        $from_name = get_option( 'wptm_email_from_name', get_bloginfo( 'name' ) );
        $from_addr = get_option( 'wptm_email_from_address', get_option( 'admin_email' ) );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if ( $from_addr && is_email( $from_addr ) ) {
            $headers[] = sprintf( 'From: %s <%s>', $from_name, $from_addr );
        }
        return wp_mail( $to, $subject, $html, $headers );
    }

    /**
     * Whether a given email channel ('customer'|'admin') is enabled. Defaults on.
     */
    private function enabled( $who ) {
        $key = 'customer' === $who ? 'wptm_email_customer_enabled' : 'wptm_email_admin_enabled';
        return (bool) get_option( $key, 1 );
    }

    /**
     * Replace {placeholders} in subjects/preheaders from a booking.
     */
    private function parse( $text, $booking ) {
        $sym = get_option( 'wptm_currency_symbol', '$' );
        return strtr( $text, array(
            '{booking_number}' => $booking->booking_number ?? '',
            '{customer_name}'  => $booking->customer_name ?? '',
            '{site_name}'      => get_bloginfo( 'name' ),
            '{item}'           => get_the_title( $booking->item_id ?? 0 ),
            '{total}'          => $sym . number_format( (float) ( $booking->total_price ?? 0 ), 2 ),
        ) );
    }
}
