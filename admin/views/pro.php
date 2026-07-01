<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * The single "Upgrade to Pro" page — Free vs Pro comparison + buy button.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$rows = \JourneyLoom\Pro::comparison();
$url  = wptm_pro_upgrade_url();
?>
<div class="wrap wptm-admin-wrap wptm-pro-page">

    <div class="wptm-pro-hero">
        <div class="wptm-pro-hero__main">
            <span class="wptm-pro-hero__eyebrow">✦ <?php esc_html_e( 'JourneyLoom', 'journeyloom' ); ?> <strong>PRO</strong></span>
            <h1><?php esc_html_e( 'Unlock the full power of your travel store.', 'journeyloom' ); ?></h1>
            <p><?php esc_html_e( 'AI that writes your trips and replies to customers, Stripe / PayPal / Razorpay checkout, printable invoices, coupons, pickup points and the AI Style generator — all in one upgrade.', 'journeyloom' ); ?></p>
            <div class="wptm-pro-hero__cta">
                <a class="button button-primary button-hero" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy Pro', 'journeyloom' ); ?> →</a>
                <a class="button button-hero wptm-pro-ghost" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See live demo', 'journeyloom' ); ?></a>
            </div>
        </div>
        <div class="wptm-pro-hero__badges">
            <span><span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'AI Suite', 'journeyloom' ); ?></span>
            <span><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Stripe · PayPal · Razorpay', 'journeyloom' ); ?></span>
            <span><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'Invoices', 'journeyloom' ); ?></span>
            <span><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Coupons', 'journeyloom' ); ?></span>
        </div>
    </div>

    <div class="wptm-pro-compare">
        <table class="wptm-pro-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Feature', 'journeyloom' ); ?></th>
                    <th class="wptm-pro-col"><?php esc_html_e( 'Free', 'journeyloom' ); ?></th>
                    <th class="wptm-pro-col wptm-pro-col--pro"><?php esc_html_e( 'Pro', 'journeyloom' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) :
                    list( $label, $free, $pro ) = $row;
                    if ( null === $free && null === $pro ) : ?>
                        <tr class="wptm-pro-cat"><td colspan="3"><?php echo esc_html( $label ); ?></td></tr>
                    <?php else :
                        $tick = '<span class="wptm-yes dashicons dashicons-yes"></span>';
                        $no   = '<span class="wptm-no dashicons dashicons-minus"></span>';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $label ); ?></td>
                            <td class="wptm-pro-col"><?php echo $free ? $tick : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            <td class="wptm-pro-col wptm-pro-col--pro"><?php echo $pro ? $tick : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td class="wptm-pro-col"><span class="wptm-pro-current"><?php esc_html_e( 'Current', 'journeyloom' ); ?></span></td>
                    <td class="wptm-pro-col wptm-pro-col--pro">
                        <a class="button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy Pro', 'journeyloom' ); ?></a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <p class="wptm-pro-foot">
            <?php esc_html_e( 'Already purchased? Install and activate the “JourneyLoom Pro” plugin to unlock everything automatically — no settings to migrate.', 'journeyloom' ); ?>
        </p>
    </div>
</div>
