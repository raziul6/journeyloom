<?php
/**
 * Admin view: Free vs Pro comparison / upgrade page.
 *
 * @package JourneyLoom
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables.
if ( ! defined( 'ABSPATH' ) ) exit;

$buy_url = wptm_pro_url( 'pro-page' );
$is_pro  = wptm_is_pro_active();

/*
 * Comparison rows, grouped by section.
 * Each row: label, description, in free (bool), in pro (bool).
 */
$sections = array(
    __( 'Core Platform', 'byteflows-travel-hotel-booking' ) => array(
        array( __( 'Trip Management', 'byteflows-travel-hotel-booking' ), __( 'Trip packages with day-by-day itineraries, pricing tiers, galleries, maps and FAQs.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Hotel Management', 'byteflows-travel-hotel-booking' ), __( 'Hotels with room types, amenities, star ratings and availability.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Smart Booking Engine', 'byteflows-travel-hotel-booking' ), __( 'Step-by-step AJAX booking with availability checking and server-side price validation.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Advanced Search & Form Builder', 'byteflows-travel-hotel-booking' ), __( 'AJAX search with filters plus a drag-and-drop search-form builder.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Wishlist & Compare', 'byteflows-travel-hotel-booking' ), __( 'Visitors can save favorites and compare trips/hotels side by side.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Reviews & Ratings', 'byteflows-travel-hotel-booking' ), __( 'Built-in review system with moderation.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Email Notifications', 'byteflows-travel-hotel-booking' ), __( 'Automated booking confirmation and status-update emails.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Booking Manager & Reports', 'byteflows-travel-hotel-booking' ), __( 'Dedicated dashboard with a booking detail drawer and revenue reports.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Gutenberg Blocks & Elementor Widgets', 'byteflows-travel-hotel-booking' ), __( 'Native blocks/widgets for trip grids, hotel grids, search forms and more.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'REST API & Schema Markup', 'byteflows-travel-hotel-booking' ), __( 'Headless/mobile integrations and automatic SEO structured data.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Demo Content Importer', 'byteflows-travel-hotel-booking' ), __( 'One-click sample trips and hotels to preview the plugin instantly.', 'byteflows-travel-hotel-booking' ), true, true ),
    ),
    __( 'Payments & Checkout', 'byteflows-travel-hotel-booking' ) => array(
        array( __( 'Manual / Bank Transfer Payments', 'byteflows-travel-hotel-booking' ), __( 'Take bookings with configurable bank-transfer instructions.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Stripe Card Checkout', 'byteflows-travel-hotel-booking' ), __( 'Cards with SCA / 3-D Secure and server-side verification.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'PayPal Checkout', 'byteflows-travel-hotel-booking' ), __( 'PayPal orders created and captured via the REST API.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'Razorpay Checkout', 'byteflows-travel-hotel-booking' ), __( 'Cards, UPI, netbanking and wallets with signature verification.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'Coupons & Discounts', 'byteflows-travel-hotel-booking' ), __( 'Percentage or fixed-amount coupon codes with usage limits and expiry.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'Pickup Points', 'byteflows-travel-hotel-booking' ), __( 'Free or priced pickup locations chosen per traveler at checkout.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'Printable Invoices', 'byteflows-travel-hotel-booking' ), __( 'Branded, print-ready invoices for any booking.', 'byteflows-travel-hotel-booking' ), false, true ),
    ),
    __( 'AI Assistant (Bring Your Own Key)', 'byteflows-travel-hotel-booking' ) => array(
        array( __( 'Natural-Language Search', 'byteflows-travel-hotel-booking' ), __( '"Beach trip under $500 in July" — AI turns plain language into filtered results.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'AI Concierge Chat', 'byteflows-travel-hotel-booking' ), __( 'Front-end chat widget that answers questions and shows bookable trip/hotel cards.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'Smart Recommendations', 'byteflows-travel-hotel-booking' ), __( 'AI-picked related trips and hotels for every visitor.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'AI Trip Builder & Itinerary Generator', 'byteflows-travel-hotel-booking' ), __( 'Draft complete trips and day-by-day itineraries from a short prompt.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'AI-Drafted Customer Replies', 'byteflows-travel-hotel-booking' ), __( 'One-click reply drafts for booking enquiries in the admin.', 'byteflows-travel-hotel-booking' ), false, true ),
        array( __( 'OpenAI, Anthropic & Compatible Endpoints', 'byteflows-travel-hotel-booking' ), __( 'Use your own API key with OpenAI, Anthropic (Claude), Groq, Ollama and more.', 'byteflows-travel-hotel-booking' ), false, true ),
    ),
    __( 'Support', 'byteflows-travel-hotel-booking' ) => array(
        array( __( 'Community Support', 'byteflows-travel-hotel-booking' ), __( 'WordPress.org support forum.', 'byteflows-travel-hotel-booking' ), true, true ),
        array( __( 'Priority Email Support', 'byteflows-travel-hotel-booking' ), __( 'Direct help from the Byteflows team.', 'byteflows-travel-hotel-booking' ), false, true ),
    ),
);
?>
<div class="wrap wptm-admin-wrap wptm-pro-page">

    <?php if ( $is_pro ) : ?>

        <div class="wptm-card">
            <h3><span class="dashicons dashicons-yes-alt" style="color:var(--wptm-success);"></span> <?php esc_html_e( 'Pro is active — thank you!', 'byteflows-travel-hotel-booking' ); ?></h3>
            <p><?php esc_html_e( 'All Pro features are unlocked. Online payments, coupons, invoices, pickup points and the AI assistant are available in Settings.', 'byteflows-travel-hotel-booking' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Settings', 'byteflows-travel-hotel-booking' ); ?></a>
        </div>

    <?php else : ?>

        <div class="wptm-pro-hero">
            <div class="wptm-pro-hero__main">
                <span class="wptm-pro-hero__eyebrow"><?php esc_html_e( 'Byteflows Travel & Hotel Booking', 'byteflows-travel-hotel-booking' ); ?> <strong><?php esc_html_e( 'PRO', 'byteflows-travel-hotel-booking' ); ?></strong></span>
                <h1><?php esc_html_e( 'Unlock online payments, coupons, invoices & the AI assistant', 'byteflows-travel-hotel-booking' ); ?></h1>
                <p><?php esc_html_e( 'Everything in the free plugin stays free and fully functional. The Pro add-on installs alongside it — the extra options appear automatically, with no settings to migrate.', 'byteflows-travel-hotel-booking' ); ?></p>
                <div class="wptm-pro-hero__cta">
                    <a href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener" class="button button-primary button-hero">
                        <?php esc_html_e( 'Buy Pro Now', 'byteflows-travel-hotel-booking' ); ?>
                    </a>
                    <a href="<?php echo esc_url( wptm_pro_url( 'pro-page-features' ) ); ?>" target="_blank" rel="noopener" class="button button-hero wptm-pro-ghost">
                        <?php esc_html_e( 'See Full Details', 'byteflows-travel-hotel-booking' ); ?>
                    </a>
                </div>
                <div class="wptm-pro-hero__badges">
                    <span><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Stripe · PayPal · Razorpay', 'byteflows-travel-hotel-booking' ); ?></span>
                    <span><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Coupons & Invoices', 'byteflows-travel-hotel-booking' ); ?></span>
                    <span><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'AI Search, Chat & Trip Builder', 'byteflows-travel-hotel-booking' ); ?></span>
                    <span><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Priority Support', 'byteflows-travel-hotel-booking' ); ?></span>
                </div>
            </div>
        </div>

        <table class="wptm-pro-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Feature', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th class="wptm-pro-col"><?php esc_html_e( 'Free', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th class="wptm-pro-col wptm-pro-col--pro"><?php esc_html_e( 'Pro', 'byteflows-travel-hotel-booking' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $sections as $section_label => $rows ) : ?>
                    <tr class="wptm-pro-cat">
                        <td colspan="3"><?php echo esc_html( $section_label ); ?></td>
                    </tr>
                    <?php foreach ( $rows as $row ) : list( $label, $desc, $in_free, $in_pro ) = $row; ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $label ); ?></strong>
                                <span class="wptm-pro-feature-desc"><?php echo esc_html( $desc ); ?></span>
                            </td>
                            <td class="wptm-pro-col">
                                <?php if ( $in_free ) : ?>
                                    <span class="dashicons dashicons-yes-alt wptm-yes" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Included', 'byteflows-travel-hotel-booking' ); ?></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus wptm-no" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Not included', 'byteflows-travel-hotel-booking' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="wptm-pro-col wptm-pro-col--pro">
                                <?php if ( $in_pro ) : ?>
                                    <span class="dashicons dashicons-yes-alt wptm-yes" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Included', 'byteflows-travel-hotel-booking' ); ?></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus wptm-no" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Not included', 'byteflows-travel-hotel-booking' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td class="wptm-pro-col"><span class="wptm-pro-current"><?php esc_html_e( 'Free forever', 'byteflows-travel-hotel-booking' ); ?></span></td>
                    <td class="wptm-pro-col wptm-pro-col--pro">
                        <a href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="noopener" class="button button-primary">
                            <?php esc_html_e( 'Buy Now', 'byteflows-travel-hotel-booking' ); ?>
                        </a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <p class="wptm-pro-foot">
            <?php esc_html_e( 'Pro is a separate add-on plugin hosted at byteflows.net — install it alongside this free plugin and the extra options appear automatically. Your trips, hotels, bookings and settings are untouched. The AI assistant is bring-your-own-key (OpenAI, Anthropic or any OpenAI-compatible endpoint); nothing is sent to any provider until you enable it.', 'byteflows-travel-hotel-booking' ); ?>
        </p>

    <?php endif; ?>
</div>
