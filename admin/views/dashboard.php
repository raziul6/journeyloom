<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$stats = \WPTravelMachine\Admin\BookingList::get_stats();
$trips = wp_count_posts( 'wptm_trip' );
$hotels = wp_count_posts( 'wptm_hotel' );
$demo_counts = \WPTravelMachine\Admin\DemoImporter::demo_counts();
$has_demo    = ( $demo_counts['trip'] + $demo_counts['hotel'] ) > 0;
?>
<div class="wrap wptm-admin-wrap">
    <div class="wptm-admin-header">
        <h1><span class="dashicons dashicons-airplane"></span> <?php esc_html_e( 'WP Travel Machine', 'wp-travel-machine' ); ?></h1>
        <span class="wptm-version">v<?php echo esc_html( WPTM_VERSION ); ?></span>
    </div>

    <div class="wptm-dashboard-grid">
        <div class="wptm-stat-card wptm-stat-primary">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-chart-bar"></span></div>
            <div class="wptm-stat-content">
                <span class="wptm-stat-number"><?php echo esc_html( $stats['total'] ); ?></span>
                <span class="wptm-stat-label"><?php esc_html_e( 'Total Bookings', 'wp-travel-machine' ); ?></span>
            </div>
        </div>
        <div class="wptm-stat-card wptm-stat-success">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="wptm-stat-content">
                <span class="wptm-stat-number"><?php echo esc_html( $stats['confirmed'] ); ?></span>
                <span class="wptm-stat-label"><?php esc_html_e( 'Confirmed', 'wp-travel-machine' ); ?></span>
            </div>
        </div>
        <div class="wptm-stat-card wptm-stat-warning">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="wptm-stat-content">
                <span class="wptm-stat-number"><?php echo esc_html( $stats['pending'] ); ?></span>
                <span class="wptm-stat-label"><?php esc_html_e( 'Pending', 'wp-travel-machine' ); ?></span>
            </div>
        </div>
        <div class="wptm-stat-card wptm-stat-info">
            <div class="wptm-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="wptm-stat-content">
                <span class="wptm-stat-number"><?php echo esc_html( get_option( 'wptm_currency_symbol', '$' ) . number_format( $stats['revenue'], 2 ) ); ?></span>
                <span class="wptm-stat-label"><?php esc_html_e( 'Total Revenue', 'wp-travel-machine' ); ?></span>
            </div>
        </div>
    </div>

    <div class="wptm-dashboard-row">
        <div class="wptm-dashboard-col">
            <div class="wptm-card">
                <h3><?php esc_html_e( 'Quick Stats', 'wp-travel-machine' ); ?></h3>
                <table class="wptm-quick-stats">
                    <tr><td><?php esc_html_e( 'Published Trips', 'wp-travel-machine' ); ?></td><td><strong><?php echo esc_html( $trips->publish ?? 0 ); ?></strong></td></tr>
                    <tr><td><?php esc_html_e( 'Published Hotels', 'wp-travel-machine' ); ?></td><td><strong><?php echo esc_html( $hotels->publish ?? 0 ); ?></strong></td></tr>
                    <tr><td><?php esc_html_e( 'Bookings This Month', 'wp-travel-machine' ); ?></td><td><strong><?php echo esc_html( $stats['this_month'] ); ?></strong></td></tr>
                    <tr><td><?php esc_html_e( 'Cancelled', 'wp-travel-machine' ); ?></td><td><strong><?php echo esc_html( $stats['cancelled'] ); ?></strong></td></tr>
                </table>
            </div>
        </div>
        <div class="wptm-dashboard-col">
            <div class="wptm-card">
                <h3><?php esc_html_e( 'Quick Links', 'wp-travel-machine' ); ?></h3>
                <div class="wptm-quick-links">
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wptm_trip' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Trip', 'wp-travel-machine' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wptm_hotel' ) ); ?>" class="button"><?php esc_html_e( 'Add New Hotel', 'wp-travel-machine' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-bookings' ) ); ?>" class="button"><?php esc_html_e( 'View Bookings', 'wp-travel-machine' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wptm-settings' ) ); ?>" class="button"><?php esc_html_e( 'Settings', 'wp-travel-machine' ); ?></a>
                    <a href="<?php echo esc_url( WPTM_PLUGIN_URL . 'Doc/doc.html' ); ?>" class="button" target="_blank" rel="noopener"><span class="dashicons dashicons-book" style="vertical-align:text-bottom;"></span> <?php esc_html_e( 'Documentation', 'wp-travel-machine' ); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="wptm-card wptm-demo-importer"
        data-import-nonce="<?php echo esc_attr( wp_create_nonce( 'wptm_admin_nonce' ) ); ?>">
        <h3><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Demo Content', 'wp-travel-machine' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Populate your site with a set of sample trips and hotels — complete with pricing, itineraries and rooms — so you can preview the plugin instantly. One click, no manual CSV needed.', 'wp-travel-machine' ); ?>
        </p>

        <div class="wptm-demo-choices">
            <label><input type="checkbox" name="wptm_demo_type" value="trip" checked> <?php esc_html_e( 'Trips', 'wp-travel-machine' ); ?> <span class="description">(12)</span></label>
            <label><input type="checkbox" name="wptm_demo_type" value="hotel" checked> <?php esc_html_e( 'Hotels', 'wp-travel-machine' ); ?> <span class="description">(12)</span></label>
            <label><input type="checkbox" name="wptm_demo_images" id="wptm-demo-images" checked> <?php esc_html_e( 'Import featured + gallery images from Unsplash', 'wp-travel-machine' ); ?></label>
        </div>

        <div class="wptm-demo-unsplash" id="wptm-demo-unsplash">
            <label for="wptm-unsplash-key"><?php esc_html_e( 'Unsplash Access Key', 'wp-travel-machine' ); ?> <span class="description"><?php esc_html_e( '(optional)', 'wp-travel-machine' ); ?></span></label>
            <input type="text" class="regular-text" id="wptm-unsplash-key" name="wptm_unsplash_key"
                value="<?php echo esc_attr( get_option( 'wptm_unsplash_key', '' ) ); ?>"
                placeholder="<?php esc_attr_e( 'Paste your free Unsplash Access Key', 'wp-travel-machine' ); ?>" autocomplete="off">
            <p class="description">
                <?php
                printf(
                    /* translators: %s: link to the Unsplash developers site. */
                    esc_html__( 'Add a free key from %s for real, topic-matched photos. Leave blank to use keyless placeholder images.', 'wp-travel-machine' ),
                    '<a href="https://unsplash.com/developers" target="_blank" rel="noopener">unsplash.com/developers</a>'
                );
                ?>
            </p>
        </div>

        <p class="wptm-demo-actions">
            <button type="button" class="button button-primary" id="wptm-import-demo">
                <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Import Demo Content', 'wp-travel-machine' ); ?>
            </button>
            <button type="button" class="button wptm-demo-remove" id="wptm-remove-demo" <?php echo $has_demo ? '' : 'style="display:none;"'; ?>>
                <?php esc_html_e( 'Remove Demo Content', 'wp-travel-machine' ); ?>
            </button>
            <span class="spinner" style="float:none;"></span>
        </p>

        <p class="wptm-demo-status" id="wptm-demo-status" aria-live="polite">
            <?php
            if ( $has_demo ) {
                printf(
                    /* translators: 1: trip count, 2: hotel count. */
                    esc_html__( 'Currently installed: %1$d demo trips, %2$d demo hotels.', 'wp-travel-machine' ),
                    (int) $demo_counts['trip'],
                    (int) $demo_counts['hotel']
                );
            }
            ?>
        </p>
    </div>
</div>
