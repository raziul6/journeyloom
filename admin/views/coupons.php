<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).
 if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$coupons = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wptm_coupons ORDER BY created_at DESC" );
?>
<div class="wrap wptm-admin-wrap">
    <h1><?php esc_html_e( 'Coupons', 'byteflows-travel-hotel-booking' ); ?> <button class="button button-primary" id="wptm-add-coupon"><?php esc_html_e( 'Add New', 'byteflows-travel-hotel-booking' ); ?></button></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr>
            <th><?php esc_html_e( 'Code', 'byteflows-travel-hotel-booking' ); ?></th>
            <th><?php esc_html_e( 'Type', 'byteflows-travel-hotel-booking' ); ?></th>
            <th><?php esc_html_e( 'Amount', 'byteflows-travel-hotel-booking' ); ?></th>
            <th><?php esc_html_e( 'Usage', 'byteflows-travel-hotel-booking' ); ?></th>
            <th><?php esc_html_e( 'Expiry', 'byteflows-travel-hotel-booking' ); ?></th>
            <th><?php esc_html_e( 'Status', 'byteflows-travel-hotel-booking' ); ?></th>
        </tr></thead>
        <tbody>
        <?php if ( empty( $coupons ) ) : ?>
            <tr><td colspan="6"><?php esc_html_e( 'No coupons yet.', 'byteflows-travel-hotel-booking' ); ?></td></tr>
        <?php else : foreach ( $coupons as $c ) : ?>
            <tr>
                <td><strong><?php echo esc_html( $c->code ); ?></strong></td>
                <td><?php echo esc_html( ucfirst( $c->type ) ); ?></td>
                <td><?php echo 'percentage' === $c->type ? esc_html( $c->amount . '%' ) : esc_html( get_option('wptm_currency_symbol','$') . $c->amount ); ?></td>
                <td><?php echo esc_html( $c->used_count . ( $c->max_uses ? '/' . $c->max_uses : '' ) ); ?></td>
                <td><?php echo $c->end_date ? esc_html( date_i18n( get_option('date_format'), strtotime( $c->end_date ) ) ) : '—'; ?></td>
                <td><span class="wptm-badge wptm-badge-<?php echo esc_attr( $c->status ); ?>"><?php echo esc_html( ucfirst( $c->status ) ); ?></span></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
