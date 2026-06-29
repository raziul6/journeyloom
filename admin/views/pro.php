<?php
/**
 * The single "Upgrade to Pro" page — Free vs Pro comparison + buy button.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$rows = \WPTravelMachine\Pro::comparison();
$url  = wptm_pro_upgrade_url();
?>
<div class="wrap wptm-admin-wrap wptm-pro-page">

    <div class="wptm-pro-hero">
        <div class="wptm-pro-hero__main">
            <span class="wptm-pro-hero__eyebrow">✦ <?php esc_html_e( 'WP Travel Machine', 'wp-travel-machine' ); ?> <strong>PRO</strong></span>
            <h1><?php esc_html_e( 'Unlock the full power of your travel store.', 'wp-travel-machine' ); ?></h1>
            <p><?php esc_html_e( 'AI that writes your trips and replies to customers, Stripe & PayPal checkout, printable invoices, and coupons — all in one upgrade.', 'wp-travel-machine' ); ?></p>
            <div class="wptm-pro-hero__cta">
                <a class="button button-primary button-hero" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy Pro', 'wp-travel-machine' ); ?> →</a>
                <a class="button button-hero wptm-pro-ghost" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See live demo', 'wp-travel-machine' ); ?></a>
            </div>
        </div>
        <div class="wptm-pro-hero__badges">
            <span><span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'AI Suite', 'wp-travel-machine' ); ?></span>
            <span><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Stripe & PayPal', 'wp-travel-machine' ); ?></span>
            <span><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'Invoices', 'wp-travel-machine' ); ?></span>
            <span><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Coupons', 'wp-travel-machine' ); ?></span>
        </div>
    </div>

    <div class="wptm-pro-compare">
        <table class="wptm-pro-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Feature', 'wp-travel-machine' ); ?></th>
                    <th class="wptm-pro-col"><?php esc_html_e( 'Free', 'wp-travel-machine' ); ?></th>
                    <th class="wptm-pro-col wptm-pro-col--pro"><?php esc_html_e( 'Pro', 'wp-travel-machine' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) :
                    list( $label, $free, $pro ) = $row;
                    $tick = '<span class="wptm-yes dashicons dashicons-yes"></span>';
                    $no   = '<span class="wptm-no dashicons dashicons-minus"></span>';
                    ?>
                    <tr>
                        <td><?php echo esc_html( $label ); ?></td>
                        <td class="wptm-pro-col"><?php echo $free ? $tick : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                        <td class="wptm-pro-col wptm-pro-col--pro"><?php echo $pro ? $tick : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td class="wptm-pro-col"><span class="wptm-pro-current"><?php esc_html_e( 'Current', 'wp-travel-machine' ); ?></span></td>
                    <td class="wptm-pro-col wptm-pro-col--pro">
                        <a class="button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy Pro', 'wp-travel-machine' ); ?></a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <p class="wptm-pro-foot">
            <?php esc_html_e( 'Already purchased? Install and activate the “WP Travel Machine Pro” plugin to unlock everything automatically — no settings to migrate.', 'wp-travel-machine' ); ?>
        </p>
    </div>
</div>
