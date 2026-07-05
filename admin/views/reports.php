<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).
 if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb; $t = $wpdb->prefix . 'wptm_bookings';
$monthly = $wpdb->get_results("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count, SUM(total_price) as revenue FROM $t WHERE payment_status='paid' GROUP BY month ORDER BY month DESC LIMIT 12");
?>
<div class="wrap wptm-admin-wrap">
    <h1><?php esc_html_e( 'Reports', 'byteflows-travel-hotel-booking' ); ?></h1>
    <div class="wptm-card">
        <h3><?php esc_html_e( 'Monthly Revenue', 'byteflows-travel-hotel-booking' ); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th><?php esc_html_e('Month','byteflows-travel-hotel-booking');?></th><th><?php esc_html_e('Bookings','byteflows-travel-hotel-booking');?></th><th><?php esc_html_e('Revenue','byteflows-travel-hotel-booking');?></th></tr></thead>
            <tbody>
            <?php if(empty($monthly)):?><tr><td colspan="3"><?php esc_html_e('No data.','byteflows-travel-hotel-booking');?></td></tr>
            <?php else: $sym=get_option('wptm_currency_symbol','$'); foreach($monthly as $r):?>
                <tr><td><?php echo esc_html($r->month);?></td><td><?php echo esc_html($r->count);?></td><td><?php echo esc_html($sym.number_format($r->revenue,2));?></td></tr>
            <?php endforeach; endif;?>
            </tbody>
        </table>
    </div>
</div>
