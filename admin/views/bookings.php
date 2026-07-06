<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
if ( ! defined( 'ABSPATH' ) ) exit;
// Read-only list filters from the admin screen; no state change, so no nonce.
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$status_filter = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) );
$type_filter   = sanitize_text_field( wp_unslash( $_GET['type'] ?? '' ) );
$type_filter   = in_array( $type_filter, array( 'trip', 'hotel' ), true ) ? $type_filter : '';
$paged    = absint( $_GET['paged'] ?? 1 );
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$per_page = 20;
$bookings = \JourneyLoom\Booking\BookingEngine::get_bookings( array(
    'status' => $status_filter,
    'type'   => $type_filter,
    'limit'  => $per_page,
    'offset' => ( $paged - 1 ) * $per_page,
) );
$sym         = get_option( 'wptm_currency_symbol', '$' );
$stats       = \JourneyLoom\Admin\BookingList::get_stats( $type_filter );
$type_counts = \JourneyLoom\Admin\BookingList::get_type_counts();

$base_url = admin_url( 'admin.php?page=wptm-bookings' );

// Type segmented filter (preserves the active status).
$status_carry = $status_filter ? add_query_arg( 'status', $status_filter, $base_url ) : $base_url;
$type_tabs = array(
    ''      => array( 'label' => __( 'All', 'byteflows-travel-hotel-booking' ),    'icon' => 'dashicons-screenoptions', 'count' => $type_counts['all'],   'url' => remove_query_arg( 'type', $status_carry ) ),
    'trip'  => array( 'label' => __( 'Trips', 'byteflows-travel-hotel-booking' ),  'icon' => 'dashicons-palmtree',      'count' => $type_counts['trip'],  'url' => add_query_arg( 'type', 'trip', $status_carry ) ),
    'hotel' => array( 'label' => __( 'Hotels', 'byteflows-travel-hotel-booking' ), 'icon' => 'dashicons-building',      'count' => $type_counts['hotel'], 'url' => add_query_arg( 'type', 'hotel', $status_carry ) ),
);

// Status pills (preserve the active type).
$type_carry  = $type_filter ? add_query_arg( 'type', $type_filter, $base_url ) : $base_url;
$status_tabs = array(
    ''          => array( __( 'All', 'byteflows-travel-hotel-booking' ),       $stats['total'] ),
    'pending'   => array( __( 'Pending', 'byteflows-travel-hotel-booking' ),   $stats['pending'] ),
    'confirmed' => array( __( 'Confirmed', 'byteflows-travel-hotel-booking' ), $stats['confirmed'] ),
    'completed' => array( __( 'Completed', 'byteflows-travel-hotel-booking' ), null ),
    'cancelled' => array( __( 'Cancelled', 'byteflows-travel-hotel-booking' ), $stats['cancelled'] ),
);
?>
<div class="wrap wptm-admin-wrap wptm-bookings-wrap">

    <div class="wptm-admin-header">
        <span class="dashicons dashicons-tickets-alt"></span>
        <h1><?php esc_html_e( 'Bookings', 'byteflows-travel-hotel-booking' ); ?></h1>
        <span class="wptm-version"><?php /* translators: %d: total number of bookings. */ printf( esc_html__( '%d total', 'byteflows-travel-hotel-booking' ), (int) $stats['total'] ); ?></span>
    </div>

    <!-- Stat cards -->
    <div class="wptm-dashboard-grid">
        <div class="wptm-stat-card wptm-stat-primary">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-tickets-alt"></span></div>
            <div class="wptm-stat-content"><span class="wptm-stat-number"><?php echo (int) $stats['total']; ?></span><span class="wptm-stat-label"><?php esc_html_e( 'Total Bookings', 'byteflows-travel-hotel-booking' ); ?></span></div>
        </div>
        <div class="wptm-stat-card wptm-stat-warning">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="wptm-stat-content"><span class="wptm-stat-number"><?php echo (int) $stats['pending']; ?></span><span class="wptm-stat-label"><?php esc_html_e( 'Pending', 'byteflows-travel-hotel-booking' ); ?></span></div>
        </div>
        <div class="wptm-stat-card wptm-stat-success">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="wptm-stat-content"><span class="wptm-stat-number"><?php echo (int) $stats['confirmed']; ?></span><span class="wptm-stat-label"><?php esc_html_e( 'Confirmed', 'byteflows-travel-hotel-booking' ); ?></span></div>
        </div>
        <div class="wptm-stat-card wptm-stat-info">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-chart-bar"></span></div>
            <div class="wptm-stat-content"><span class="wptm-stat-number"><?php echo esc_html( $sym . number_format( $stats['revenue'], 0 ) ); ?></span><span class="wptm-stat-label"><?php esc_html_e( 'Paid Revenue', 'byteflows-travel-hotel-booking' ); ?></span></div>
        </div>
    </div>

    <div class="wptm-card wptm-bookings-card">

        <!-- Toolbar: type segmented filter + search -->
        <div class="wptm-bookings-toolbar">
            <div class="wptm-seg" role="tablist" aria-label="<?php esc_attr_e( 'Filter by booking type', 'byteflows-travel-hotel-booking' ); ?>">
                <?php foreach ( $type_tabs as $key => $tab ) :
                    $active = $type_filter === $key ? ' is-active' : '';
                    ?>
                    <a href="<?php echo esc_url( $tab['url'] ); ?>" class="wptm-seg__btn wptm-seg__btn--<?php echo esc_attr( $key ?: 'all' ); ?><?php echo esc_attr( $active ); ?>" role="tab" aria-selected="<?php echo $type_filter === $key ? 'true' : 'false'; ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                        <em class="wptm-seg__count"><?php echo (int) $tab['count']; ?></em>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="wptm-bookings-search">
                <span class="dashicons dashicons-search"></span>
                <input type="search" id="wptm-bookings-search" placeholder="<?php esc_attr_e( 'Search booking, name, email…', 'byteflows-travel-hotel-booking' ); ?>">
            </div>
        </div>

        <!-- Sub-toolbar: status pills -->
        <div class="wptm-bookings-toolbar wptm-bookings-toolbar--sub">
            <div class="wptm-pills">
                <?php foreach ( $status_tabs as $key => $tab ) :
                    $url = $key ? add_query_arg( 'status', $key, $type_carry ) : $type_carry;
                    $active = $status_filter === $key ? ' is-active' : '';
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="wptm-pill<?php echo esc_attr( $active ); ?>">
                        <?php echo esc_html( $tab[0] ); ?>
                        <?php if ( null !== $tab[1] ) : ?><span class="wptm-pill__count"><?php echo (int) $tab[1]; ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <table class="wptm-booking-table wptm-table-modern">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Booking', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Customer', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Item', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Payment', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'byteflows-travel-hotel-booking' ); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $bookings ) ) : ?>
                    <tr class="wptm-no-rows"><td colspan="9">
                        <div class="wptm-empty-state">
                            <span class="dashicons dashicons-tickets-alt"></span>
                            <p><?php esc_html_e( 'No bookings found.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </td></tr>
                <?php else : foreach ( $bookings as $b ) :
                    $item     = get_post( $b->item_id );
                    $initial  = strtoupper( substr( $b->customer_name ?: '?', 0, 1 ) );
                    $is_hotel = 'hotel' === $b->booking_type;
                    $type_lbl = $is_hotel ? __( 'Hotel', 'byteflows-travel-hotel-booking' ) : __( 'Trip', 'byteflows-travel-hotel-booking' );
                    $type_ico = $is_hotel ? 'dashicons-building' : 'dashicons-palmtree';
                    $search   = strtolower( $b->booking_number . ' ' . $b->customer_name . ' ' . $b->customer_email . ' ' . $type_lbl . ' ' . ( $item ? $item->post_title : '' ) );
                    ?>
                    <tr data-id="<?php echo esc_attr( $b->id ); ?>" data-search="<?php echo esc_attr( $search ); ?>" class="wptm-booking-row">
                        <td><code class="wptm-bk-number"><?php echo esc_html( $b->booking_number ); ?></code></td>
                        <td>
                            <div class="wptm-bk-customer">
                                <span class="wptm-bk-avatar"><?php echo esc_html( $initial ); ?></span>
                                <span class="wptm-bk-customer__info">
                                    <strong><?php echo esc_html( $b->customer_name ); ?></strong>
                                    <small><?php echo esc_html( $b->customer_email ); ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="wptm-type-chip wptm-type-chip--<?php echo $is_hotel ? 'hotel' : 'trip'; ?>">
                                <span class="dashicons <?php echo esc_attr( $type_ico ); ?>"></span><?php echo esc_html( $type_lbl ); ?>
                            </span>
                        </td>
                        <td><?php echo $item ? esc_html( $item->post_title ) : '—'; ?></td>
                        <td><strong><?php echo esc_html( $sym . number_format( $b->total_price, 2 ) ); ?></strong></td>
                        <td><span class="wptm-badge wptm-badge-<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
                        <td><span class="wptm-badge wptm-badge-<?php echo esc_attr( $b->payment_status ); ?>"><?php echo esc_html( ucfirst( $b->payment_status ) ); ?></span></td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $b->created_at ) ) ); ?></td>
                        <td class="wptm-bk-actions">
                            <button class="button button-small wptm-view-booking" data-id="<?php echo esc_attr( $b->id ); ?>"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'byteflows-travel-hotel-booking' ); ?></button>
                            <?php
                            /**
                             * Fires in the actions cell of each bookings-list row.
                             * Add-ons hook here to add row actions (e.g. a
                             * print-invoice button).
                             *
                             * @param object $b Booking row.
                             */
                            do_action( 'wptm_bookings_list_row_actions', $b );
                            ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detail drawer -->
<div class="wptm-drawer" id="wptm-booking-drawer" aria-hidden="true">
    <div class="wptm-drawer__overlay"></div>
    <aside class="wptm-drawer__panel" role="dialog" aria-modal="true">
        <button type="button" class="wptm-drawer__close" aria-label="<?php esc_attr_e( 'Close', 'byteflows-travel-hotel-booking' ); ?>">&times;</button>
        <div class="wptm-drawer__body">
            <div class="wptm-drawer__loading"><span class="spinner is-active"></span></div>
        </div>
    </aside>
</div>
