<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Settings page — JourneyLoom.
 *
 * Sidebar navigation + content panels layout.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$all_pages = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title' ) );

/**
 * Render a page-select dropdown row.
 */
$wptm_page_field = function ( $option_key ) use ( $all_pages ) {
    // Prints a page-picker <select> (with Edit/View links). Every dynamic value
    // is escaped inline, so callers can invoke this directly without echoing.
    $current = (int) get_option( $option_key, 0 );
    ?>
    <select name="settings[<?php echo esc_attr( $option_key ); ?>]" class="wptm-field__select">
        <option value="0"><?php esc_html_e( '— Select a page —', 'byteflows-travel-hotel-booking' ); ?></option>
        <?php foreach ( $all_pages as $p ) : ?>
            <option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $current, $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
    if ( $current ) {
        printf(
            ' <a href="%s" target="_blank" class="wptm-field__link">%s</a> <a href="%s" target="_blank" class="wptm-field__link">%s</a>',
            esc_url( get_edit_post_link( $current ) ), esc_html__( 'Edit', 'byteflows-travel-hotel-booking' ),
            esc_url( get_permalink( $current ) ), esc_html__( 'View', 'byteflows-travel-hotel-booking' )
        );
    }
};
?>
<div class="wrap wptm-admin-wrap wptm-settings-wrap">

    <div class="wptm-settings">

        <!-- ─── Sidebar ─── -->
        <aside class="wptm-settings__sidebar">
            <div class="wptm-settings__brand">
                <span class="wptm-settings__brand-icon dashicons dashicons-airplane"></span>
                <span class="wptm-settings__brand-text">Journey<strong>Loom</strong></span>
            </div>

            <div class="wptm-settings__search">
                <span class="dashicons dashicons-search"></span>
                <input type="search" id="wptm-settings-search" placeholder="<?php esc_attr_e( 'Search settings…', 'byteflows-travel-hotel-booking' ); ?>">
            </div>

            <nav class="wptm-settings__nav">
                <div class="wptm-nav-group is-open">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-info-outline"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'General', 'byteflows-travel-hotel-booking' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item is-active" data-panel="pages"><?php esc_html_e( 'Pages', 'byteflows-travel-hotel-booking' ); ?></a>
                        <a class="wptm-nav-item" data-panel="display"><?php esc_html_e( 'Display', 'byteflows-travel-hotel-booking' ); ?></a>
                        <a class="wptm-nav-item" data-panel="appearance"><?php esc_html_e( 'Appearance', 'byteflows-travel-hotel-booking' ); ?></a>
                        <a class="wptm-nav-item" data-panel="currency"><?php esc_html_e( 'Currency', 'byteflows-travel-hotel-booking' ); ?></a>
                    </div>
                </div>

                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-cart"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'Payments', 'byteflows-travel-hotel-booking' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <?php if ( wptm_is_pro() ) : ?>
                        <a class="wptm-nav-item" data-panel="stripe"><?php esc_html_e( 'Stripe', 'byteflows-travel-hotel-booking' ); ?></a>
                        <a class="wptm-nav-item" data-panel="paypal"><?php esc_html_e( 'PayPal', 'byteflows-travel-hotel-booking' ); ?></a>
                        <a class="wptm-nav-item" data-panel="razorpay"><?php esc_html_e( 'Razorpay', 'byteflows-travel-hotel-booking' ); ?></a>
                        <?php endif; ?>
                        <a class="wptm-nav-item" data-panel="manual"><?php esc_html_e( 'Manual Payment', 'byteflows-travel-hotel-booking' ); ?></a>
                    </div>
                </div>

                <?php
                /** Add-ons (e.g. Pro) inject extra settings nav groups here. */
                do_action( 'wptm_settings_nav_items' );
                ?>

                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-email-alt"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'Emails', 'byteflows-travel-hotel-booking' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item" data-panel="email"><?php esc_html_e( 'Notifications', 'byteflows-travel-hotel-booking' ); ?></a>
                        <a class="wptm-nav-item" data-panel="enquiry"><?php esc_html_e( 'Enquiry Form', 'byteflows-travel-hotel-booking' ); ?></a>
                    </div>
                </div>

                <?php if ( wptm_is_pro() ) : ?>
                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-media-document"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'Invoice', 'byteflows-travel-hotel-booking' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item" data-panel="invoice"><?php esc_html_e( 'Company & Invoice', 'byteflows-travel-hotel-booking' ); ?></a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- ─── Body ─── -->
        <div class="wptm-settings__body">
            <form id="wptm-settings-form" class="wptm-settings-form">
                <?php wp_nonce_field( 'wptm_admin_nonce', 'wptm_settings_nonce' ); ?>

                <!-- Panel: Pages -->
                <section class="wptm-settings-panel is-active" data-panel="pages">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Pages', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Checkout Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_checkout' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'This is the checkout page where buyers will complete their order.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Terms & Conditions Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_terms_page' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The terms and conditions page that trip bookers will see during booking.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Booking Confirmation Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_confirmation' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The confirmation page where trip bookers fill in traveller details.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Cart Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_cart' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page that displays the items a buyer has added to their cart.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Trip Search Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_search' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page that hosts the trip search form and results.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'All Trips Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_trips' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The archive page that lists all available trips.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Destinations Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_destinations' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page that lists all travel destinations.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'All Hotels Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_hotels' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The archive page that lists all available hotels.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Wishlist Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_page_field( 'wptm_page_wishlist' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page where users view trips they have saved to their wishlist.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Display -->
                <section class="wptm-settings-panel" data-panel="display">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Display', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Items Per Page', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="number" name="settings[wptm_items_per_page]" value="<?php echo esc_attr( get_option( 'wptm_items_per_page', 12 ) ); ?>" min="1" max="100" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Number of trips/hotels shown per page on archive pages.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Pagination Type', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_pag = get_option( 'wptm_pagination_type', 'pagination' ); ?>
                            <select name="settings[wptm_pagination_type]" class="wptm-field__input">
                                <option value="pagination" <?php selected( $wptm_pag, 'pagination' ); ?>><?php esc_html_e( 'Numbered Pagination', 'byteflows-travel-hotel-booking' ); ?></option>
                                <option value="load_more" <?php selected( $wptm_pag, 'load_more' ); ?>><?php esc_html_e( 'AJAX “Load More” button', 'byteflows-travel-hotel-booking' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'How additional trips/hotels are loaded on archive pages.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <?php
                    $wptm_gallery_styles = array(
                        'grid'     => array( __( 'Grid', 'byteflows-travel-hotel-booking' ),     __( 'Equal-sized tiles in a neat grid.', 'byteflows-travel-hotel-booking' ) ),
                        'masonry'  => array( __( 'Masonry', 'byteflows-travel-hotel-booking' ),  __( 'Pinterest-style columns, varied heights.', 'byteflows-travel-hotel-booking' ) ),
                        'carousel' => array( __( 'Carousel', 'byteflows-travel-hotel-booking' ), __( 'Horizontal sliding strip of images.', 'byteflows-travel-hotel-booking' ) ),
                        'mosaic'   => array( __( 'Mosaic', 'byteflows-travel-hotel-booking' ),   __( 'One large feature image with a thumbnail grid.', 'byteflows-travel-hotel-booking' ) ),
                    );
                    $current_gstyle = get_option( 'wptm_gallery_style', 'grid' );
                    ?>
                    <div class="wptm-field wptm-field--stacked">
                        <div class="wptm-field__label">
                            <label><?php esc_html_e( 'Gallery Style', 'byteflows-travel-hotel-booking' ); ?></label>
                        </div>
                        <div class="wptm-field__control wptm-field__control--full">
                            <p class="wptm-field__desc" style="margin:0 0 12px;"><?php esc_html_e( 'Choose how the image gallery looks on every single trip page.', 'byteflows-travel-hotel-booking' ); ?></p>
                            <div class="wptm-gallery-style">
                            <?php foreach ( $wptm_gallery_styles as $key => $style ) : ?>
                                <label class="wptm-gallery-style__option<?php echo $current_gstyle === $key ? ' is-selected' : ''; ?>" data-style="<?php echo esc_attr( $key ); ?>">
                                    <input type="radio" name="settings[wptm_gallery_style]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current_gstyle, $key ); ?>>
                                    <span class="wptm-gallery-style__preview wptm-gsp--<?php echo esc_attr( $key ); ?>" aria-hidden="true">
                                        <?php for ( $i = 0; $i < 5; $i++ ) : ?><span class="wptm-gsp__tile"></span><?php endfor; ?>
                                    </span>
                                    <span class="wptm-gallery-style__name"><?php echo esc_html( $style[0] ); ?></span>
                                    <span class="wptm-gallery-style__desc"><?php echo esc_html( $style[1] ); ?></span>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Wishlist', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_wishlist]" value="1" <?php checked( get_option( 'wptm_enable_wishlist', true ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Allow users to save trips to a wishlist.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Compare', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_compare]" value="1" <?php checked( get_option( 'wptm_enable_compare', true ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Allow visitors to compare multiple trips side by side.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Related Items', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_related]" value="1" <?php checked( get_option( 'wptm_enable_related', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Show a “You may also like” section of related trips/hotels on single tour and booking pages.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Related Items Count', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="number" min="1" max="12" name="settings[wptm_related_count]" value="<?php echo esc_attr( get_option( 'wptm_related_count', 3 ) ); ?>" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'How many related items to display.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                </section>

                <!-- Panel: Appearance -->
                <section class="wptm-settings-panel" data-panel="appearance">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Color Settings', 'byteflows-travel-hotel-booking' ); ?></h2>
                    <p class="wptm-panel-desc"><?php esc_html_e( 'Customize the brand colors used across the front end. Leave a field empty to use the default.', 'byteflows-travel-hotel-booking' ); ?></p>
                    <?php
                    $wptm_color_fields = array(
                        'wptm_color_primary'         => array( __( 'Primary Color', 'byteflows-travel-hotel-booking' ), '#fd4621', __( 'Buttons, links, prices and brand accents across the site.', 'byteflows-travel-hotel-booking' ) ),
                        'wptm_color_discount_ribbon' => array( __( 'Discount Ribbon Color', 'byteflows-travel-hotel-booking' ), '#fd4621', __( 'The “% OFF” ribbon shown on discounted trip cards.', 'byteflows-travel-hotel-booking' ) ),
                        'wptm_color_featured_ribbon' => array( __( 'Featured Ribbon Color', 'byteflows-travel-hotel-booking' ), '#f59e0b', __( 'The “Featured” ribbon shown on featured trip & hotel cards.', 'byteflows-travel-hotel-booking' ) ),
                        'wptm_color_icon'            => array( __( 'Icon Color', 'byteflows-travel-hotel-booking' ), '#fd4621', __( 'Line icons used in facts, meta, amenities and lists.', 'byteflows-travel-hotel-booking' ) ),
                    );
                    foreach ( $wptm_color_fields as $wptm_ck => $wptm_cf ) :
                        $wptm_cval = (string) get_option( $wptm_ck, '' );
                        $wptm_cnow = '' !== $wptm_cval ? $wptm_cval : $wptm_cf[1];
                        ?>
                        <div class="wptm-field">
                            <div class="wptm-field__label"><label><?php echo esc_html( $wptm_cf[0] ); ?></label></div>
                            <div class="wptm-field__control">
                                <div class="wptm-color-field" data-default="<?php echo esc_attr( $wptm_cf[1] ); ?>">
                                    <input type="color" class="wptm-color-field__swatch" value="<?php echo esc_attr( $wptm_cnow ); ?>" aria-label="<?php echo esc_attr( $wptm_cf[0] ); ?>">
                                    <input type="text" name="settings[<?php echo esc_attr( $wptm_ck ); ?>]" class="wptm-color-field__hex" value="<?php echo esc_attr( $wptm_cval ); ?>" placeholder="<?php echo esc_attr( $wptm_cf[1] ); ?>" maxlength="7">
                                    <button type="button" class="button wptm-color-field__reset"><?php esc_html_e( 'Reset', 'byteflows-travel-hotel-booking' ); ?></button>
                                </div>
                                <p class="wptm-field__desc"><?php echo esc_html( $wptm_cf[2] ); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- Panel: Currency -->
                <section class="wptm-settings-panel" data-panel="currency">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Currency', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <?php $wptm_current_currency = get_option( 'wptm_currency', 'USD' ); ?>
                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Currency', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_currency]" id="wptm-currency-select" class="wptm-field__select">
                                <?php foreach ( wptm_get_currencies() as $code => $cur ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" data-symbol="<?php echo esc_attr( $cur[1] ); ?>" <?php selected( $wptm_current_currency, $code ); ?>>
                                        <?php echo esc_html( $cur[0] . ' (' . $code . ' ' . $cur[1] . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Select your store currency. The symbol below updates automatically (you can still override it).', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Currency Symbol', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_currency_symbol]" id="wptm-currency-symbol" value="<?php echo esc_attr( get_option( 'wptm_currency_symbol', '$' ) ); ?>" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The symbol displayed alongside prices.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Symbol Position', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_currency_position]" class="wptm-field__select wptm-field__select--sm">
                                <option value="before" <?php selected( get_option( 'wptm_currency_position' ), 'before' ); ?>><?php esc_html_e( 'Before — $99', 'byteflows-travel-hotel-booking' ); ?></option>
                                <option value="after" <?php selected( get_option( 'wptm_currency_position' ), 'after' ); ?>><?php esc_html_e( 'After — 99$', 'byteflows-travel-hotel-booking' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Where the currency symbol appears relative to the amount.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Tax', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_tax_enabled]" value="1" <?php checked( get_option( 'wptm_tax_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Apply tax to bookings at the rate set below.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Tax Rate (%)', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="number" name="settings[wptm_tax_rate]" value="<?php echo esc_attr( get_option( 'wptm_tax_rate', 0 ) ); ?>" step="0.01" min="0" max="100" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The percentage tax rate applied to bookings.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <?php if ( wptm_is_pro() ) : // Online gateways ship in the Pro add-on. ?>
                <!-- Panel: Stripe -->
                <section class="wptm-settings-panel" data-panel="stripe">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Stripe', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Stripe', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_stripe_enabled]" value="1" <?php checked( get_option( 'wptm_stripe_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Accept card payments through Stripe at checkout.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Publishable Key', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_stripe_publishable_key]" value="<?php echo esc_attr( get_option( 'wptm_stripe_publishable_key' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Stripe publishable (public) API key.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Secret Key', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_stripe_secret_key]" value="<?php echo esc_attr( get_option( 'wptm_stripe_secret_key' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Stripe secret API key. Keep this private.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Endpoint', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div class="wptm-copy-row">
                                <input type="text" class="wptm-field__input" id="wptm-stripe-webhook-url" value="<?php echo esc_attr( \JourneyLoom\Payment\StripeGateway::webhook_url() ); ?>" readonly onfocus="this.select()">
                                <button type="button" class="button wptm-copy-btn" data-copy-target="#wptm-stripe-webhook-url"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'byteflows-travel-hotel-booking' ); ?></button>
                            </div>
                            <p class="wptm-field__desc">
                                <?php esc_html_e( 'In the Stripe Dashboard → Developers → Webhooks, add an endpoint with this URL and subscribe to the', 'byteflows-travel-hotel-booking' ); ?>
                                <code>payment_intent.succeeded</code> <?php esc_html_e( 'event. This guarantees bookings are marked paid even if the customer closes the tab.', 'byteflows-travel-hotel-booking' ); ?>
                            </p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Signing Secret', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_stripe_webhook_secret]" value="<?php echo esc_attr( get_option( 'wptm_stripe_webhook_secret' ) ); ?>" class="wptm-field__input" placeholder="whsec_…">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The “Signing secret” shown after you create the webhook endpoint in Stripe. Required to verify incoming events.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: PayPal -->
                <section class="wptm-settings-panel" data-panel="paypal">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'PayPal', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable PayPal', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_paypal_enabled]" value="1" <?php checked( get_option( 'wptm_paypal_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Accept payments through PayPal at checkout.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Client ID', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_paypal_client_id]" value="<?php echo esc_attr( get_option( 'wptm_paypal_client_id' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your PayPal REST application Client ID.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Secret', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_paypal_secret]" value="<?php echo esc_attr( get_option( 'wptm_paypal_secret' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your PayPal REST application Secret.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Mode', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_paypal_mode]" class="wptm-field__select wptm-field__select--sm">
                                <option value="sandbox" <?php selected( get_option( 'wptm_paypal_mode' ), 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (testing)', 'byteflows-travel-hotel-booking' ); ?></option>
                                <option value="live" <?php selected( get_option( 'wptm_paypal_mode' ), 'live' ); ?>><?php esc_html_e( 'Live', 'byteflows-travel-hotel-booking' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Use Sandbox for testing and Live for real payments.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Razorpay -->
                <section class="wptm-settings-panel" data-panel="razorpay">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Razorpay', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Razorpay', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_razorpay_enabled]" value="1" <?php checked( get_option( 'wptm_razorpay_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Accept cards, UPI, netbanking and wallets via Razorpay at checkout.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Key ID', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_razorpay_key_id]" value="<?php echo esc_attr( get_option( 'wptm_razorpay_key_id' ) ); ?>" class="wptm-field__input" placeholder="rzp_live_… / rzp_test_…">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Razorpay Key ID (Dashboard → Settings → API Keys).', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Key Secret', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_razorpay_key_secret]" value="<?php echo esc_attr( get_option( 'wptm_razorpay_key_secret' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Razorpay Key Secret. Keep this private.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Endpoint', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div class="wptm-copy-row">
                                <input type="text" class="wptm-field__input" id="wptm-razorpay-webhook-url" value="<?php echo esc_attr( \JourneyLoom\Payment\RazorpayGateway::webhook_url() ); ?>" readonly onfocus="this.select()">
                                <button type="button" class="button wptm-copy-btn" data-copy-target="#wptm-razorpay-webhook-url"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'byteflows-travel-hotel-booking' ); ?></button>
                            </div>
                            <p class="wptm-field__desc">
                                <?php esc_html_e( 'In the Razorpay Dashboard → Settings → Webhooks, add a webhook with this URL and subscribe to the', 'byteflows-travel-hotel-booking' ); ?>
                                <code>order.paid</code> <?php esc_html_e( 'event. This confirms bookings even if the customer closes the payment window.', 'byteflows-travel-hotel-booking' ); ?>
                            </p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Secret', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_razorpay_webhook_secret]" value="<?php echo esc_attr( get_option( 'wptm_razorpay_webhook_secret' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The secret you set when creating the webhook in Razorpay. Required to verify incoming events.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Panel: Manual Payment -->
                <section class="wptm-settings-panel" data-panel="manual">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Manual Payment', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Manual Payment', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_manual_payment]" value="1" <?php checked( get_option( 'wptm_manual_payment', true ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Allow bookings to be paid offline (bank transfer, cash, etc.).', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label for="wptm_bank_instructions"><?php esc_html_e( 'Bank / Payment Instructions', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea id="wptm_bank_instructions" name="settings[wptm_bank_instructions]" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Transfer the total to Bank XYZ, Account 0000-0000, using your booking number as the reference.', 'byteflows-travel-hotel-booking' ); ?>"><?php echo esc_textarea( get_option( 'wptm_bank_instructions', '' ) ); ?></textarea>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Shown on the order confirmation page when a customer pays by bank transfer. Leave blank to use the default message.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <?php
                /** Add-ons (e.g. Pro) inject extra settings panels here. */
                do_action( 'wptm_settings_panels' );
                ?>

                <!-- Panel: Email -->
                <section class="wptm-settings-panel" data-panel="email">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Email Notifications', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'From Name', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_email_from_name]" value="<?php echo esc_attr( get_option( 'wptm_email_from_name', get_bloginfo( 'name' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The sender name shown on all booking emails.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'From Email', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_email_from_address]" value="<?php echo esc_attr( get_option( 'wptm_email_from_address', get_option( 'admin_email' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The sender address. Use an address on your own domain for best deliverability.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Customer Confirmation', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_email_customer_enabled]" value="1" <?php checked( get_option( 'wptm_email_customer_enabled', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Email the customer a confirmation when they book and when their booking status changes.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Confirmation Subject', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_email_customer_subject]" value="<?php echo esc_attr( get_option( 'wptm_email_customer_subject', __( 'Thanks for your booking, {customer_name}! ({booking_number})', 'byteflows-travel-hotel-booking' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Placeholders: {customer_name}, {booking_number}, {item}, {total}, {site_name}.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Admin Notification', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_email_admin_enabled]" value="1" <?php checked( get_option( 'wptm_email_admin_enabled', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Notify your team whenever a new booking is placed.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Notification Email', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_booking_email]" value="<?php echo esc_attr( get_option( 'wptm_booking_email', get_option( 'admin_email' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'New booking notifications are sent to this address.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Email Footer Text', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea name="settings[wptm_email_footer_text]" rows="2" class="wptm-field__input"><?php echo esc_textarea( get_option( 'wptm_email_footer_text', '' ) ); ?></textarea>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Optional footer line shown on every email (e.g. business address, support contact).', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Test Delivery', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <input type="email" id="wptm-test-email" class="wptm-field__input" style="max-width:280px;" placeholder="<?php echo esc_attr( get_option( 'wptm_booking_email', get_option( 'admin_email' ) ) ); ?>">
                                <button type="button" class="button" id="wptm-send-test-email"><?php esc_html_e( 'Send test email', 'byteflows-travel-hotel-booking' ); ?></button>
                                <span id="wptm-test-email-result" style="font-size:13px;"></span>
                            </div>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Send a sample email to confirm your server can deliver mail. If it fails, install an SMTP plugin.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Enquiry Form -->
                <section class="wptm-settings-panel" data-panel="enquiry">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Enquiry Form', 'byteflows-travel-hotel-booking' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Enquiry Form', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enquiry_enabled]" value="1" <?php checked( get_option( 'wptm_enquiry_enabled', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Show the enquiry form on single trip and hotel pages.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Form Title', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_enquiry_title]" value="<?php echo esc_attr( get_option( 'wptm_enquiry_title', __( 'Have a question? Send an enquiry', 'byteflows-travel-hotel-booking' ) ) ); ?>" class="wptm-field__input">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Send Enquiries To', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_enquiry_email]" value="<?php echo esc_attr( get_option( 'wptm_enquiry_email', get_option( 'admin_email' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Submitted enquiries are emailed to this address.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Form Fields', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div class="wptm-repeater wptm-enquiry-builder">
                                <input type="hidden" name="settings[wptm_enquiry_present]" value="1">
                                <div class="wptm-enquiry-field-head">
                                    <span><?php esc_html_e( 'Label', 'byteflows-travel-hotel-booking' ); ?></span>
                                    <span><?php esc_html_e( 'Type', 'byteflows-travel-hotel-booking' ); ?></span>
                                    <span><?php esc_html_e( 'Options (dropdown)', 'byteflows-travel-hotel-booking' ); ?></span>
                                    <span><?php esc_html_e( 'Req.', 'byteflows-travel-hotel-booking' ); ?></span>
                                    <span></span>
                                </div>
                                <div class="wptm-repeater-items">
                                    <?php
                                    $wptm_types = array( 'text' => __( 'Text', 'byteflows-travel-hotel-booking' ), 'email' => __( 'Email', 'byteflows-travel-hotel-booking' ), 'tel' => __( 'Phone', 'byteflows-travel-hotel-booking' ), 'number' => __( 'Number', 'byteflows-travel-hotel-booking' ), 'textarea' => __( 'Textarea', 'byteflows-travel-hotel-booking' ), 'select' => __( 'Dropdown', 'byteflows-travel-hotel-booking' ), 'country' => __( 'Country list', 'byteflows-travel-hotel-booking' ) );
                                    foreach ( wptm_enquiry_fields() as $i => $f ) : ?>
                                    <div class="wptm-repeater-item"><div class="wptm-enquiry-field-row">
                                        <input type="text" name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $f['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Field label', 'byteflows-travel-hotel-booking' ); ?>" class="widefat">
                                        <select name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][type]">
                                            <?php foreach ( $wptm_types as $tk => $tl ) : ?>
                                            <option value="<?php echo esc_attr( $tk ); ?>" <?php selected( $f['type'] ?? 'text', $tk ); ?>><?php echo esc_html( $tl ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][options]" value="<?php echo esc_attr( $f['options'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'A, B, C', 'byteflows-travel-hotel-booking' ); ?>" class="widefat">
                                        <label class="wptm-enquiry-req"><input type="checkbox" name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][required]" value="1" <?php checked( ! empty( $f['required'] ) ); ?>></label>
                                        <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove field', 'byteflows-travel-hotel-booking' ); ?>"><span class="dashicons dashicons-trash"></span></button>
                                    </div></div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="button wptm-add-item" data-target="enquiry"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Field', 'byteflows-travel-hotel-booking' ); ?></button>
                            </div>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Add or remove fields shown on the enquiry form. For "Dropdown", list options separated by commas.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>

                <?php if ( wptm_is_pro() ) : // Invoices ship in the Pro add-on. ?>
                <!-- Panel: Invoice -->
                <section class="wptm-settings-panel" data-panel="invoice">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Company & Invoice', 'byteflows-travel-hotel-booking' ); ?></h2>
                    <div class="wptm-panel-intro">
                        <span class="dashicons dashicons-media-document"></span>
                        <p><?php esc_html_e( 'These details appear on the printable invoice you can open from any booking.', 'byteflows-travel-hotel-booking' ); ?></p>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Company Name', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_company]" value="<?php echo esc_attr( get_option( 'wptm_invoice_company', '' ) ); ?>" class="wptm-field__input" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Defaults to your site name if left blank.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Logo', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="text" id="wptm-invoice-logo" name="settings[wptm_invoice_logo]" value="<?php echo esc_attr( get_option( 'wptm_invoice_logo', '' ) ); ?>" class="wptm-field__input" placeholder="https://…/logo.png">
                                <button type="button" class="button wptm-media-picker" data-target="#wptm-invoice-logo" data-type="image"><?php esc_html_e( 'Choose', 'byteflows-travel-hotel-booking' ); ?></button>
                            </div>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Shown at the top of the invoice. Leave blank to use a lettermark.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Business Address', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea name="settings[wptm_invoice_address]" rows="3" class="wptm-field__input" placeholder="<?php esc_attr_e( '123 Travel St, Suite 4&#10;City, Country 0000', 'byteflows-travel-hotel-booking' ); ?>"><?php echo esc_textarea( get_option( 'wptm_invoice_address', '' ) ); ?></textarea>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Contact Email', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_invoice_email]" value="<?php echo esc_attr( get_option( 'wptm_invoice_email', '' ) ); ?>" class="wptm-field__input" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Phone', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_phone]" value="<?php echo esc_attr( get_option( 'wptm_invoice_phone', '' ) ); ?>" class="wptm-field__input">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Tax / VAT Number', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_tax_number]" value="<?php echo esc_attr( get_option( 'wptm_invoice_tax_number', '' ) ); ?>" class="wptm-field__input">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Invoice Number Prefix', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_prefix]" value="<?php echo esc_attr( get_option( 'wptm_invoice_prefix', 'INV-' ) ); ?>" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'e.g. "INV-" produces invoice numbers like INV-00042.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Notes & Terms', 'byteflows-travel-hotel-booking' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea name="settings[wptm_invoice_notes]" rows="3" class="wptm-field__input" placeholder="<?php esc_attr_e( 'Payment terms, cancellation policy, thank-you note…', 'byteflows-travel-hotel-booking' ); ?>"><?php echo esc_textarea( get_option( 'wptm_invoice_notes', '' ) ); ?></textarea>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Printed in the footer of every invoice.', 'byteflows-travel-hotel-booking' ); ?></p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <div class="wptm-settings__footer">
                    <button type="submit" class="button button-primary wptm-save-btn" id="wptm-save-settings"><?php esc_html_e( 'Save Settings', 'byteflows-travel-hotel-booking' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
