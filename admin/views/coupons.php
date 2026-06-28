<?php if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$coupons = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wptm_coupons ORDER BY created_at DESC" );
?>
<div class="wrap wptm-admin-wrap">
    <h1><?php esc_html_e( 'Coupons', 'wp-travel-machine' ); ?> <button class="button button-primary" id="wptm-add-coupon"><?php esc_html_e( 'Add New', 'wp-travel-machine' ); ?></button></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr>
            <th><?php esc_html_e( 'Code', 'wp-travel-machine' ); ?></th>
            <th><?php esc_html_e( 'Type', 'wp-travel-machine' ); ?></th>
            <th><?php esc_html_e( 'Amount', 'wp-travel-machine' ); ?></th>
            <th><?php esc_html_e( 'Usage', 'wp-travel-machine' ); ?></th>
            <th><?php esc_html_e( 'Expiry', 'wp-travel-machine' ); ?></th>
            <th><?php esc_html_e( 'Status', 'wp-travel-machine' ); ?></th>
        </tr></thead>
        <tbody>
        <?php if ( empty( $coupons ) ) : ?>
            <tr><td colspan="6"><?php esc_html_e( 'No coupons yet.', 'wp-travel-machine' ); ?></td></tr>
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
