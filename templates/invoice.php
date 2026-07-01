<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Standalone printable invoice document.
 *
 * Rendered by \JourneyLoom\Booking\Invoice::render(). This outputs a full
 * HTML document (its own <html>), so it must not be wrapped by the theme.
 *
 * @var object $booking  Row from wptm_bookings.
 * @var WP_Post|null $item  The booked trip/hotel post.
 * @var array  $business  Company details (see Invoice::business()).
 * @var array  $tiers     Line items: [ ['label','qty','price'], ... ].
 * @var float  $subtotal  Pre-discount subtotal.
 * @var bool   $auto      Whether to auto-open the print dialog.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use JourneyLoom\Booking\Invoice;

$inv_no   = $business['prefix'] . str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT );
$date_fmt = get_option( 'date_format' );

$valid_date = function ( $d ) {
    return $d && '0000-00-00' !== substr( (string) $d, 0, 10 ) && strtotime( (string) $d ) > 0;
};
$tone = function ( $s ) {
    $s = strtolower( (string) $s );
    if ( in_array( $s, array( 'confirmed', 'completed', 'paid', 'active' ), true ) )      return 'good';
    if ( in_array( $s, array( 'cancelled', 'failed', 'expired', 'refunded' ), true ) )    return 'bad';
    return 'warn';
};
$is_paid = 'paid' === strtolower( (string) $booking->payment_status );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php /* translators: %s: invoice number. */ printf( esc_html__( 'Invoice %s', 'journeyloom' ), esc_html( $inv_no ) ); ?></title>
<style>
    :root{
        --c:#fd4621; --c-soft:#fff1ec; --ink:#1c1917; --muted:#6b6058; --light:#9a8f86;
        --bg:#faf7f5; --border:#ece7e3; --good:#10b981; --warn:#f59e0b; --bad:#ef4444;
    }
    *{ box-sizing:border-box; }
    html,body{ margin:0; padding:0; }
    body{
        background:#eceae8; color:var(--ink);
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
        -webkit-font-smoothing:antialiased; font-size:14px; line-height:1.5;
    }
    a{ color:inherit; text-decoration:none; }

    /* Floating toolbar (screen only) */
    .inv-bar{
        position:sticky; top:0; z-index:10;
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:12px 20px; background:rgba(28,25,23,.92); color:#fff; backdrop-filter:blur(8px);
    }
    .inv-bar h1{ font-size:14px; font-weight:600; margin:0; letter-spacing:.02em; }
    .inv-bar h1 b{ color:#ff8a3d; }
    .inv-bar .actions{ display:flex; gap:10px; }
    .inv-btn{
        display:inline-flex; align-items:center; gap:7px; cursor:pointer;
        border:1px solid rgba(255,255,255,.22); background:rgba(255,255,255,.1); color:#fff;
        padding:9px 16px; border-radius:9px; font-size:13px; font-weight:600; transition:.15s;
    }
    .inv-btn:hover{ background:rgba(255,255,255,.2); }
    .inv-btn--primary{ background:linear-gradient(135deg,#fd4621,#ff8a3d); border:none; box-shadow:0 6px 16px -6px rgba(253,70,33,.8); }
    .inv-btn--primary:hover{ filter:brightness(1.05); background:linear-gradient(135deg,#fd4621,#ff8a3d); }
    .inv-btn svg{ width:15px; height:15px; }

    /* Sheet */
    .inv-page{ padding:32px 16px 60px; }
    .inv-sheet{
        max-width:820px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden;
        box-shadow:0 18px 50px -12px rgba(28,25,23,.22);
    }

    /* Header */
    .inv-head{
        position:relative; overflow:hidden; color:#fff; padding:40px 44px 34px;
        background:radial-gradient(120% 140% at 100% 0%,#3a281e,#1b1512 60%);
    }
    .inv-head::before{ content:""; position:absolute; top:-80px; right:-40px; width:260px; height:260px; background:radial-gradient(circle,rgba(253,70,33,.5),transparent 68%); }
    .inv-head__row{ position:relative; display:flex; justify-content:space-between; align-items:flex-start; gap:24px; }
    .inv-brand{ display:flex; align-items:center; gap:14px; }
    .inv-brand img{ max-height:54px; max-width:200px; display:block; }
    .inv-brand__badge{
        width:54px; height:54px; border-radius:14px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#fd4621,#ff8a3d); font-size:26px; font-weight:800;
        box-shadow:0 8px 20px -6px rgba(253,70,33,.7);
    }
    .inv-brand h2{ margin:0; font-size:20px; font-weight:800; letter-spacing:-.01em; }
    .inv-brand p{ margin:3px 0 0; font-size:12.5px; color:rgba(255,255,255,.6); }
    .inv-title{ text-align:right; }
    .inv-title .label{ font-size:12px; letter-spacing:.22em; text-transform:uppercase; color:rgba(255,255,255,.55); font-weight:700; }
    .inv-title .no{ font-size:24px; font-weight:800; margin-top:4px; font-family:ui-monospace,Menlo,monospace; }
    .inv-status{
        display:inline-flex; align-items:center; gap:7px; margin-top:12px;
        padding:6px 13px; border-radius:999px; font-size:12px; font-weight:700;
        background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.18);
    }
    .inv-status .dot{ width:7px; height:7px; border-radius:50%; background:currentColor; box-shadow:0 0 0 3px rgba(255,255,255,.14); }
    .inv-status.is-good{ color:#34d399; } .inv-status.is-warn{ color:#fbbf24; } .inv-status.is-bad{ color:#f87171; }
    .inv-status b{ color:#fff; }

    /* Meta strip */
    .inv-meta{
        display:grid; grid-template-columns:repeat(3,1fr); gap:1px;
        background:var(--border); border-bottom:1px solid var(--border);
    }
    .inv-meta > div{ background:#fff; padding:18px 22px; }
    .inv-meta .k{ font-size:10.5px; text-transform:uppercase; letter-spacing:.07em; color:var(--light); font-weight:800; margin-bottom:5px; }
    .inv-meta .v{ font-size:13.5px; font-weight:600; color:var(--ink); }
    .inv-meta .v span{ display:block; font-weight:500; color:var(--muted); font-size:12.5px; line-height:1.5; }

    /* Body */
    .inv-body{ padding:30px 44px 40px; }
    .inv-parties{ display:grid; grid-template-columns:1fr 1fr; gap:28px; margin-bottom:28px; }
    .inv-party .h{ font-size:10.5px; text-transform:uppercase; letter-spacing:.08em; color:var(--c); font-weight:800; margin-bottom:8px; }
    .inv-party .name{ font-size:15px; font-weight:700; }
    .inv-party .line{ color:var(--muted); font-size:13px; margin-top:2px; }

    /* Line-items table */
    table.inv-items{ width:100%; border-collapse:collapse; margin-top:6px; }
    .inv-items thead th{
        text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:.06em;
        color:var(--muted); font-weight:800; padding:11px 12px; background:var(--bg);
        border-bottom:2px solid var(--border);
    }
    .inv-items thead th.r{ text-align:right; }
    .inv-items tbody td{ padding:14px 12px; border-bottom:1px solid var(--border); font-size:13.5px; vertical-align:top; }
    .inv-items tbody td.r{ text-align:right; font-variant-numeric:tabular-nums; }
    .inv-items tbody td .desc{ font-weight:700; }
    .inv-items tbody td .sub{ color:var(--light); font-size:12px; margin-top:2px; }
    .inv-items tbody tr:last-child td{ border-bottom:none; }

    /* Totals */
    .inv-foot{ display:flex; justify-content:flex-end; margin-top:18px; }
    .inv-totals{ width:300px; }
    .inv-totals .row{ display:flex; justify-content:space-between; padding:8px 0; font-size:13.5px; color:var(--muted); }
    .inv-totals .row span:last-child{ font-variant-numeric:tabular-nums; color:var(--ink); font-weight:600; }
    .inv-totals .row.disc span:last-child{ color:var(--good); }
    .inv-totals .grand{
        display:flex; justify-content:space-between; align-items:center; margin-top:8px;
        padding:14px 16px; border-radius:12px; background:linear-gradient(135deg,var(--c-soft),#fff);
        border:1px solid rgba(253,70,33,.18);
    }
    .inv-totals .grand .lbl{ font-size:13px; text-transform:uppercase; letter-spacing:.05em; font-weight:800; color:var(--c); }
    .inv-totals .grand .amt{ font-size:23px; font-weight:800; color:var(--c); font-variant-numeric:tabular-nums; }

    .inv-paidstamp{
        margin-top:22px; display:inline-flex; align-items:center; gap:8px;
        border:2px solid var(--good); color:var(--good); border-radius:10px;
        padding:8px 16px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; font-size:13px;
        transform:rotate(-3deg);
    }

    .inv-notes{ margin-top:30px; padding:16px 18px; background:var(--bg); border-radius:12px; border-left:3px solid var(--c); }
    .inv-notes .h{ font-size:11px; text-transform:uppercase; letter-spacing:.06em; font-weight:800; color:var(--muted); margin-bottom:6px; }
    .inv-notes p{ margin:0; font-size:13px; color:var(--ink); line-height:1.6; white-space:pre-line; }

    .inv-thanks{ margin-top:34px; padding-top:22px; border-top:1px solid var(--border); text-align:center; color:var(--light); font-size:12.5px; }
    .inv-thanks strong{ color:var(--ink); }

    /* Print */
    @media print{
        @page{ margin:14mm; }
        body{ background:#fff; }
        .inv-bar{ display:none !important; }
        .inv-page{ padding:0; }
        .inv-sheet{ box-shadow:none; border-radius:0; max-width:none; }
        .inv-head{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .inv-head, .inv-brand__badge, .inv-totals .grand, .inv-meta, .inv-items thead th, .inv-notes, .inv-paidstamp{
            -webkit-print-color-adjust:exact; print-color-adjust:exact;
        }
    }
    @media (max-width:680px){
        .inv-head{ padding:28px 24px; } .inv-body{ padding:24px; }
        .inv-head__row{ flex-direction:column; } .inv-title{ text-align:left; }
        .inv-meta{ grid-template-columns:1fr; } .inv-parties{ grid-template-columns:1fr; }
        .inv-totals{ width:100%; }
    }
</style>
</head>
<body>

    <div class="inv-bar">
        <h1>WP Travel <b>Machine</b> · <?php esc_html_e( 'Invoice', 'journeyloom' ); ?></h1>
        <div class="actions">
            <a class="inv-btn" href="javascript:window.close()"><?php esc_html_e( 'Close', 'journeyloom' ); ?></a>
            <a class="inv-btn inv-btn--primary" href="javascript:window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <?php esc_html_e( 'Print / Save as PDF', 'journeyloom' ); ?>
            </a>
        </div>
    </div>

    <div class="inv-page">
        <div class="inv-sheet">

            <!-- Header -->
            <div class="inv-head">
                <div class="inv-head__row">
                    <div class="inv-brand">
                        <?php if ( ! empty( $business['logo'] ) ) : ?>
                            <img src="<?php echo esc_url( $business['logo'] ); ?>" alt="<?php echo esc_attr( $business['name'] ); ?>">
                        <?php else : ?>
                            <div class="inv-brand__badge"><?php echo esc_html( strtoupper( substr( $business['name'], 0, 1 ) ) ); ?></div>
                            <div>
                                <h2><?php echo esc_html( $business['name'] ); ?></h2>
                                <?php if ( $business['email'] ) : ?><p><?php echo esc_html( $business['email'] ); ?></p><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="inv-title">
                        <div class="label"><?php esc_html_e( 'Invoice', 'journeyloom' ); ?></div>
                        <div class="no"><?php echo esc_html( $inv_no ); ?></div>
                        <span class="inv-status is-<?php echo esc_attr( $tone( $booking->payment_status ) ); ?>">
                            <i class="dot"></i><b><?php echo esc_html( ucfirst( $booking->payment_status ) ); ?></b>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Meta strip -->
            <div class="inv-meta">
                <div>
                    <div class="k"><?php esc_html_e( 'Invoice Date', 'journeyloom' ); ?></div>
                    <div class="v"><?php echo esc_html( date_i18n( $date_fmt, strtotime( $booking->created_at ) ) ); ?></div>
                </div>
                <div>
                    <div class="k"><?php esc_html_e( 'Booking Ref.', 'journeyloom' ); ?></div>
                    <div class="v"><?php echo esc_html( $booking->booking_number ); ?></div>
                </div>
                <div>
                    <div class="k"><?php esc_html_e( 'Payment Method', 'journeyloom' ); ?></div>
                    <div class="v"><?php echo esc_html( ucfirst( $booking->payment_method ?: '—' ) ); ?></div>
                </div>
            </div>

            <!-- Body -->
            <div class="inv-body">

                <div class="inv-parties">
                    <div class="inv-party">
                        <div class="h"><?php esc_html_e( 'From', 'journeyloom' ); ?></div>
                        <div class="name"><?php echo esc_html( $business['name'] ); ?></div>
                        <?php if ( $business['address'] ) : ?><div class="line"><?php echo nl2br( esc_html( $business['address'] ) ); ?></div><?php endif; ?>
                        <?php if ( $business['phone'] ) : ?><div class="line"><?php echo esc_html( $business['phone'] ); ?></div><?php endif; ?>
                        <?php if ( $business['email'] ) : ?><div class="line"><?php echo esc_html( $business['email'] ); ?></div><?php endif; ?>
                        <?php if ( $business['tax'] ) : ?><div class="line"><?php /* translators: %s: tax/VAT number. */ printf( esc_html__( 'Tax/VAT: %s', 'journeyloom' ), esc_html( $business['tax'] ) ); ?></div><?php endif; ?>
                    </div>
                    <div class="inv-party">
                        <div class="h"><?php esc_html_e( 'Bill To', 'journeyloom' ); ?></div>
                        <div class="name"><?php echo esc_html( $booking->customer_name ?: '—' ); ?></div>
                        <?php if ( $booking->customer_email ) : ?><div class="line"><?php echo esc_html( $booking->customer_email ); ?></div><?php endif; ?>
                        <?php if ( $booking->customer_phone ) : ?><div class="line"><?php echo esc_html( $booking->customer_phone ); ?></div><?php endif; ?>
                        <?php if ( $booking->customer_address ) : ?><div class="line"><?php echo nl2br( esc_html( $booking->customer_address ) ); ?></div><?php endif; ?>
                    </div>
                </div>

                <!-- Line items -->
                <table class="inv-items">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Description', 'journeyloom' ); ?></th>
                            <th class="r"><?php esc_html_e( 'Qty', 'journeyloom' ); ?></th>
                            <th class="r"><?php esc_html_e( 'Unit Price', 'journeyloom' ); ?></th>
                            <th class="r"><?php esc_html_e( 'Amount', 'journeyloom' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $item_title = $item ? $item->post_title : __( 'Booking', 'journeyloom' );
                        $trip_sub   = array();
                        if ( $valid_date( $booking->check_in ) )  $trip_sub[] = esc_html__( 'Check-in', 'journeyloom' ) . ': ' . date_i18n( $date_fmt, strtotime( $booking->check_in ) );
                        if ( $valid_date( $booking->check_out ) ) $trip_sub[] = esc_html__( 'Check-out', 'journeyloom' ) . ': ' . date_i18n( $date_fmt, strtotime( $booking->check_out ) );
                        $first = true;
                        foreach ( $tiers as $t ) :
                            $qty   = (int) ( $t['qty'] ?? 1 );
                            $price = (float) ( $t['price'] ?? 0 );
                            ?>
                            <tr>
                                <td>
                                    <div class="desc"><?php echo esc_html( ( $first ? $item_title . ' — ' : '' ) . ( $t['label'] ?? __( 'Item', 'journeyloom' ) ) ); ?></div>
                                    <?php if ( $first && $trip_sub ) : ?><div class="sub"><?php echo esc_html( implode( '  ·  ', $trip_sub ) ); ?></div><?php endif; ?>
                                </td>
                                <td class="r"><?php echo (int) $qty; ?></td>
                                <td class="r"><?php echo esc_html( Invoice::money( $price ) ); ?></td>
                                <td class="r"><?php echo esc_html( Invoice::money( $price * $qty ) ); ?></td>
                            </tr>
                            <?php $first = false; endforeach; ?>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="inv-foot">
                    <div class="inv-totals">
                        <div class="row"><span><?php esc_html_e( 'Subtotal', 'journeyloom' ); ?></span><span><?php echo esc_html( Invoice::money( $subtotal ) ); ?></span></div>
                        <?php if ( (float) $booking->discount_amount > 0 ) : ?>
                        <div class="row disc">
                            <span><?php esc_html_e( 'Discount', 'journeyloom' ); ?><?php echo $booking->coupon_code ? ' (' . esc_html( $booking->coupon_code ) . ')' : ''; ?></span>
                            <span>-<?php echo esc_html( Invoice::money( $booking->discount_amount ) ); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="grand">
                            <span class="lbl"><?php esc_html_e( 'Total', 'journeyloom' ); ?></span>
                            <span class="amt"><?php echo esc_html( Invoice::money( $booking->total_price ) ); ?></span>
                        </div>
                    </div>
                </div>

                <?php if ( $is_paid ) : ?>
                <div class="inv-paidstamp">✓ <?php esc_html_e( 'Paid', 'journeyloom' ); ?></div>
                <?php endif; ?>

                <?php if ( ! empty( $business['notes'] ) ) : ?>
                <div class="inv-notes">
                    <div class="h"><?php esc_html_e( 'Notes & Terms', 'journeyloom' ); ?></div>
                    <p><?php echo esc_html( $business['notes'] ); ?></p>
                </div>
                <?php endif; ?>

                <div class="inv-thanks">
                    <strong><?php esc_html_e( 'Thank you for your booking!', 'journeyloom' ); ?></strong><br>
                    <?php /* translators: %s: date/time the invoice was generated. */ printf( esc_html__( 'This invoice was generated on %s.', 'journeyloom' ), esc_html( date_i18n( $date_fmt . ' ' . get_option( 'time_format' ) ) ) ); ?>
                </div>

            </div><!-- /.inv-body -->
        </div><!-- /.inv-sheet -->
    </div>

    <?php if ( $auto ) : ?>
    <script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 350); });</script>
    <?php endif; ?>
</body>
</html>
