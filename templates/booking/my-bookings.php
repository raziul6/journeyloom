<?php
/**
 * My Bookings — list the logged-in user's orders.
 *
 * @package WPTravelMachine
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sym          = get_option( 'wptm_currency_symbol', '$' );
$confirm_page = wptm_get_page_url( 'confirmation' );
?>
<div class="wptm-my-bookings">
    <h2 class="wptm-my-bookings__title"><?php esc_html_e( 'My Bookings', 'wp-travel-machine' ); ?></h2>

    <?php if ( ! is_user_logged_in() ) : ?>
        <div class="wptm-my-bookings__empty">
            <p><?php esc_html_e( 'Please log in to view your bookings.', 'wp-travel-machine' ); ?></p>
            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'Log In', 'wp-travel-machine' ); ?></a>
        </div>
    <?php else : ?>
        <?php
        global $wpdb;
        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wptm_bookings WHERE user_id = %d ORDER BY created_at DESC",
            get_current_user_id()
        ) );
        ?>
        <?php if ( empty( $bookings ) ) : ?>
            <div class="wptm-my-bookings__empty">
                <p>🧳 <?php esc_html_e( 'You have no bookings yet.', 'wp-travel-machine' ); ?></p>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'wptm_trip' ) ); ?>" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'Browse Trips', 'wp-travel-machine' ); ?></a>
            </div>
        <?php else : ?>
            <div class="wptm-my-bookings__table-wrap">
                <table class="wptm-my-bookings__table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Booking', 'wp-travel-machine' ); ?></th>
                            <th><?php esc_html_e( 'Item', 'wp-travel-machine' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'wp-travel-machine' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'wp-travel-machine' ); ?></th>
                            <th><?php esc_html_e( 'Payment', 'wp-travel-machine' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'wp-travel-machine' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $bookings as $b ) : ?>
                            <?php
                            $title    = get_the_title( $b->item_id );
                            $view_url = $confirm_page ? add_query_arg( 'booking', $b->id, $confirm_page ) : '';
                            ?>
                            <tr>
                                <td data-label="<?php esc_attr_e( 'Booking', 'wp-travel-machine' ); ?>"><strong><?php echo esc_html( $b->booking_number ); ?></strong></td>
                                <td data-label="<?php esc_attr_e( 'Item', 'wp-travel-machine' ); ?>"><?php echo esc_html( $title ?: '—' ); ?></td>
                                <td data-label="<?php esc_attr_e( 'Date', 'wp-travel-machine' ); ?>"><?php echo esc_html( $b->check_in ?: mysql2date( get_option( 'date_format' ), $b->created_at ) ); ?></td>
                                <td data-label="<?php esc_attr_e( 'Total', 'wp-travel-machine' ); ?>"><?php echo esc_html( $sym . number_format( $b->total_price, 2 ) ); ?></td>
                                <td data-label="<?php esc_attr_e( 'Payment', 'wp-travel-machine' ); ?>"><span class="wptm-pay-badge wptm-pay-badge--<?php echo esc_attr( $b->payment_status ); ?>"><?php echo esc_html( ucfirst( $b->payment_status ) ); ?></span></td>
                                <td data-label="<?php esc_attr_e( 'Status', 'wp-travel-machine' ); ?>"><span class="wptm-badge wptm-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
                                <td>
                                    <?php if ( $view_url ) : ?>
                                        <a href="<?php echo esc_url( $view_url ); ?>" class="wptm-btn wptm-btn--sm"><?php esc_html_e( 'View', 'wp-travel-machine' ); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
