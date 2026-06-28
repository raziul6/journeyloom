<?php if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb; $t = $wpdb->prefix . 'wptm_bookings';
$monthly = $wpdb->get_results("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count, SUM(total_price) as revenue FROM $t WHERE payment_status='paid' GROUP BY month ORDER BY month DESC LIMIT 12");
?>
<div class="wrap wptm-admin-wrap">
    <h1><?php esc_html_e( 'Reports', 'wp-travel-machine' ); ?></h1>
    <div class="wptm-card">
        <h3><?php esc_html_e( 'Monthly Revenue', 'wp-travel-machine' ); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th><?php esc_html_e('Month','wp-travel-machine');?></th><th><?php esc_html_e('Bookings','wp-travel-machine');?></th><th><?php esc_html_e('Revenue','wp-travel-machine');?></th></tr></thead>
            <tbody>
            <?php if(empty($monthly)):?><tr><td colspan="3"><?php esc_html_e('No data.','wp-travel-machine');?></td></tr>
            <?php else: $sym=get_option('wptm_currency_symbol','$'); foreach($monthly as $r):?>
                <tr><td><?php echo esc_html($r->month);?></td><td><?php echo esc_html($r->count);?></td><td><?php echo esc_html($sym.number_format($r->revenue,2));?></td></tr>
            <?php endforeach; endif;?>
            </tbody>
        </table>
    </div>
</div>
