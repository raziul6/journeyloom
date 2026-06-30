<?php
/**
 * Settings page — WP Travel Machine.
 *
 * Sidebar navigation + content panels layout.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$all_pages = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title' ) );

/**
 * Render a page-select dropdown row.
 */
$wptm_page_field = function ( $option_key ) use ( $all_pages ) {
    $current = (int) get_option( $option_key, 0 );
    ob_start();
    ?>
    <select name="settings[<?php echo esc_attr( $option_key ); ?>]" class="wptm-field__select">
        <option value="0"><?php esc_html_e( '— Select a page —', 'wp-travel-machine' ); ?></option>
        <?php foreach ( $all_pages as $p ) : ?>
            <option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $current, $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
    if ( $current ) {
        printf(
            ' <a href="%s" target="_blank" class="wptm-field__link">%s</a> <a href="%s" target="_blank" class="wptm-field__link">%s</a>',
            esc_url( get_edit_post_link( $current ) ), esc_html__( 'Edit', 'wp-travel-machine' ),
            esc_url( get_permalink( $current ) ), esc_html__( 'View', 'wp-travel-machine' )
        );
    }
    return ob_get_clean();
};
?>
<div class="wrap wptm-admin-wrap wptm-settings-wrap">

    <div class="wptm-settings">

        <!-- ─── Sidebar ─── -->
        <aside class="wptm-settings__sidebar">
            <div class="wptm-settings__brand">
                <span class="wptm-settings__brand-icon dashicons dashicons-airplane"></span>
                <span class="wptm-settings__brand-text">WP Travel <strong>Machine</strong></span>
            </div>

            <div class="wptm-settings__search">
                <span class="dashicons dashicons-search"></span>
                <input type="search" id="wptm-settings-search" placeholder="<?php esc_attr_e( 'Search settings…', 'wp-travel-machine' ); ?>">
            </div>

            <nav class="wptm-settings__nav">
                <div class="wptm-nav-group is-open">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-info-outline"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'General', 'wp-travel-machine' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item is-active" data-panel="pages"><?php esc_html_e( 'Pages', 'wp-travel-machine' ); ?></a>
                        <a class="wptm-nav-item" data-panel="display"><?php esc_html_e( 'Display', 'wp-travel-machine' ); ?></a>
                        <a class="wptm-nav-item" data-panel="appearance"><?php esc_html_e( 'Appearance', 'wp-travel-machine' ); ?></a>
                        <a class="wptm-nav-item" data-panel="currency"><?php esc_html_e( 'Currency', 'wp-travel-machine' ); ?></a>
                    </div>
                </div>

                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-cart"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'Payments', 'wp-travel-machine' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <?php if ( wptm_is_pro() ) : ?>
                        <a class="wptm-nav-item" data-panel="stripe"><?php esc_html_e( 'Stripe', 'wp-travel-machine' ); ?></a>
                        <a class="wptm-nav-item" data-panel="paypal"><?php esc_html_e( 'PayPal', 'wp-travel-machine' ); ?></a>
                        <a class="wptm-nav-item" data-panel="razorpay"><?php esc_html_e( 'Razorpay', 'wp-travel-machine' ); ?></a>
                        <?php endif; ?>
                        <a class="wptm-nav-item" data-panel="manual"><?php esc_html_e( 'Manual Payment', 'wp-travel-machine' ); ?></a>
                    </div>
                </div>

                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-superhero"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'AI Features', 'wp-travel-machine' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item" data-panel="ai"><?php esc_html_e( 'AI Configuration', 'wp-travel-machine' ); ?></a>
                    </div>
                </div>

                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-email-alt"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'Emails', 'wp-travel-machine' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item" data-panel="email"><?php esc_html_e( 'Notifications', 'wp-travel-machine' ); ?></a>
                        <a class="wptm-nav-item" data-panel="enquiry"><?php esc_html_e( 'Enquiry Form', 'wp-travel-machine' ); ?></a>
                    </div>
                </div>

                <?php if ( wptm_is_pro() ) : ?>
                <div class="wptm-nav-group">
                    <button type="button" class="wptm-nav-group__head">
                        <span class="dashicons dashicons-media-document"></span>
                        <span class="wptm-nav-group__title"><?php esc_html_e( 'Invoice', 'wp-travel-machine' ); ?></span>
                        <span class="wptm-nav-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="wptm-nav-group__items">
                        <a class="wptm-nav-item" data-panel="invoice"><?php esc_html_e( 'Company & Invoice', 'wp-travel-machine' ); ?></a>
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
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Pages', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Checkout Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_checkout' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'This is the checkout page where buyers will complete their order.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Terms & Conditions Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_terms_page' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The terms and conditions page that trip bookers will see during booking.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Booking Confirmation Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_confirmation' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The confirmation page where trip bookers fill in traveller details.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Cart Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_cart' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page that displays the items a buyer has added to their cart.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Trip Search Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_search' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page that hosts the trip search form and results.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'All Trips Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_trips' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The archive page that lists all available trips.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Destinations Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_destinations' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page that lists all travel destinations.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'All Hotels Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_hotels' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The archive page that lists all available hotels.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Wishlist Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php echo $wptm_page_field( 'wptm_page_wishlist' ); ?>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The page where users view trips they have saved to their wishlist.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Display -->
                <section class="wptm-settings-panel" data-panel="display">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Display', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Items Per Page', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="number" name="settings[wptm_items_per_page]" value="<?php echo esc_attr( get_option( 'wptm_items_per_page', 12 ) ); ?>" min="1" max="100" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Number of trips/hotels shown per page on archive pages.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Pagination Type', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <?php $wptm_pag = get_option( 'wptm_pagination_type', 'pagination' ); ?>
                            <select name="settings[wptm_pagination_type]" class="wptm-field__input">
                                <option value="pagination" <?php selected( $wptm_pag, 'pagination' ); ?>><?php esc_html_e( 'Numbered Pagination', 'wp-travel-machine' ); ?></option>
                                <option value="load_more" <?php selected( $wptm_pag, 'load_more' ); ?>><?php esc_html_e( 'AJAX “Load More” button', 'wp-travel-machine' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'How additional trips/hotels are loaded on archive pages.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <?php
                    $wptm_gallery_styles = array(
                        'grid'     => array( __( 'Grid', 'wp-travel-machine' ),     __( 'Equal-sized tiles in a neat grid.', 'wp-travel-machine' ) ),
                        'masonry'  => array( __( 'Masonry', 'wp-travel-machine' ),  __( 'Pinterest-style columns, varied heights.', 'wp-travel-machine' ) ),
                        'carousel' => array( __( 'Carousel', 'wp-travel-machine' ), __( 'Horizontal sliding strip of images.', 'wp-travel-machine' ) ),
                        'mosaic'   => array( __( 'Mosaic', 'wp-travel-machine' ),   __( 'One large feature image with a thumbnail grid.', 'wp-travel-machine' ) ),
                    );
                    $current_gstyle = get_option( 'wptm_gallery_style', 'grid' );
                    ?>
                    <div class="wptm-field wptm-field--stacked">
                        <div class="wptm-field__label">
                            <label><?php esc_html_e( 'Gallery Style', 'wp-travel-machine' ); ?></label>
                        </div>
                        <div class="wptm-field__control wptm-field__control--full">
                            <p class="wptm-field__desc" style="margin:0 0 12px;"><?php esc_html_e( 'Choose how the image gallery looks on every single trip page.', 'wp-travel-machine' ); ?></p>
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
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Wishlist', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_wishlist]" value="1" <?php checked( get_option( 'wptm_enable_wishlist', true ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Allow users to save trips to a wishlist.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Compare', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_compare]" value="1" <?php checked( get_option( 'wptm_enable_compare', true ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Allow visitors to compare multiple trips side by side.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Related Items', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_related]" value="1" <?php checked( get_option( 'wptm_enable_related', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Show a “You may also like” section of related trips/hotels on single tour and booking pages.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Related Items Count', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="number" min="1" max="12" name="settings[wptm_related_count]" value="<?php echo esc_attr( get_option( 'wptm_related_count', 3 ) ); ?>" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'How many related items to display.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                </section>

                <!-- Panel: Appearance -->
                <section class="wptm-settings-panel" data-panel="appearance">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Color Settings', 'wp-travel-machine' ); ?></h2>
                    <p class="wptm-panel-desc"><?php esc_html_e( 'Customize the brand colors used across the front end. Leave a field empty to use the default.', 'wp-travel-machine' ); ?></p>
                    <?php
                    $wptm_color_fields = array(
                        'wptm_color_primary'         => array( __( 'Primary Color', 'wp-travel-machine' ), '#fd4621', __( 'Buttons, links, prices and brand accents across the site.', 'wp-travel-machine' ) ),
                        'wptm_color_discount_ribbon' => array( __( 'Discount Ribbon Color', 'wp-travel-machine' ), '#fd4621', __( 'The “% OFF” ribbon shown on discounted trip cards.', 'wp-travel-machine' ) ),
                        'wptm_color_featured_ribbon' => array( __( 'Featured Ribbon Color', 'wp-travel-machine' ), '#f59e0b', __( 'The “Featured” ribbon shown on featured trip & hotel cards.', 'wp-travel-machine' ) ),
                        'wptm_color_icon'            => array( __( 'Icon Color', 'wp-travel-machine' ), '#fd4621', __( 'Line icons used in facts, meta, amenities and lists.', 'wp-travel-machine' ) ),
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
                                    <button type="button" class="button wptm-color-field__reset"><?php esc_html_e( 'Reset', 'wp-travel-machine' ); ?></button>
                                </div>
                                <p class="wptm-field__desc"><?php echo esc_html( $wptm_cf[2] ); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- Panel: Currency -->
                <section class="wptm-settings-panel" data-panel="currency">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Currency', 'wp-travel-machine' ); ?></h2>

                    <?php $wptm_current_currency = get_option( 'wptm_currency', 'USD' ); ?>
                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Currency', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_currency]" id="wptm-currency-select" class="wptm-field__select">
                                <?php foreach ( wptm_get_currencies() as $code => $cur ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" data-symbol="<?php echo esc_attr( $cur[1] ); ?>" <?php selected( $wptm_current_currency, $code ); ?>>
                                        <?php echo esc_html( $cur[0] . ' (' . $code . ' ' . $cur[1] . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Select your store currency. The symbol below updates automatically (you can still override it).', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Currency Symbol', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_currency_symbol]" id="wptm-currency-symbol" value="<?php echo esc_attr( get_option( 'wptm_currency_symbol', '$' ) ); ?>" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The symbol displayed alongside prices.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Symbol Position', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_currency_position]" class="wptm-field__select wptm-field__select--sm">
                                <option value="before" <?php selected( get_option( 'wptm_currency_position' ), 'before' ); ?>><?php esc_html_e( 'Before — $99', 'wp-travel-machine' ); ?></option>
                                <option value="after" <?php selected( get_option( 'wptm_currency_position' ), 'after' ); ?>><?php esc_html_e( 'After — 99$', 'wp-travel-machine' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Where the currency symbol appears relative to the amount.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Tax', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_tax_enabled]" value="1" <?php checked( get_option( 'wptm_tax_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Apply tax to bookings at the rate set below.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Tax Rate (%)', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="number" name="settings[wptm_tax_rate]" value="<?php echo esc_attr( get_option( 'wptm_tax_rate', 0 ) ); ?>" step="0.01" min="0" max="100" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The percentage tax rate applied to bookings.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <?php if ( wptm_is_pro() ) : ?>
                <!-- Panel: Stripe -->
                <section class="wptm-settings-panel" data-panel="stripe">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Stripe', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Stripe', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_stripe_enabled]" value="1" <?php checked( get_option( 'wptm_stripe_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Accept card payments through Stripe at checkout.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Publishable Key', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_stripe_publishable_key]" value="<?php echo esc_attr( get_option( 'wptm_stripe_publishable_key' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Stripe publishable (public) API key.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Secret Key', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_stripe_secret_key]" value="<?php echo esc_attr( get_option( 'wptm_stripe_secret_key' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Stripe secret API key. Keep this private.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Endpoint', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div class="wptm-copy-row">
                                <input type="text" class="wptm-field__input" id="wptm-stripe-webhook-url" value="<?php echo esc_attr( \WPTravelMachine\Payment\StripeGateway::webhook_url() ); ?>" readonly onfocus="this.select()">
                                <button type="button" class="button wptm-copy-btn" data-copy-target="#wptm-stripe-webhook-url"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'wp-travel-machine' ); ?></button>
                            </div>
                            <p class="wptm-field__desc">
                                <?php esc_html_e( 'In the Stripe Dashboard → Developers → Webhooks, add an endpoint with this URL and subscribe to the', 'wp-travel-machine' ); ?>
                                <code>payment_intent.succeeded</code> <?php esc_html_e( 'event. This guarantees bookings are marked paid even if the customer closes the tab.', 'wp-travel-machine' ); ?>
                            </p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Signing Secret', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_stripe_webhook_secret]" value="<?php echo esc_attr( get_option( 'wptm_stripe_webhook_secret' ) ); ?>" class="wptm-field__input" placeholder="whsec_…">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The “Signing secret” shown after you create the webhook endpoint in Stripe. Required to verify incoming events.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: PayPal -->
                <section class="wptm-settings-panel" data-panel="paypal">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'PayPal', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable PayPal', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_paypal_enabled]" value="1" <?php checked( get_option( 'wptm_paypal_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Accept payments through PayPal at checkout.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Client ID', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_paypal_client_id]" value="<?php echo esc_attr( get_option( 'wptm_paypal_client_id' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your PayPal REST application Client ID.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Secret', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_paypal_secret]" value="<?php echo esc_attr( get_option( 'wptm_paypal_secret' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your PayPal REST application Secret.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Mode', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_paypal_mode]" class="wptm-field__select wptm-field__select--sm">
                                <option value="sandbox" <?php selected( get_option( 'wptm_paypal_mode' ), 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (testing)', 'wp-travel-machine' ); ?></option>
                                <option value="live" <?php selected( get_option( 'wptm_paypal_mode' ), 'live' ); ?>><?php esc_html_e( 'Live', 'wp-travel-machine' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Use Sandbox for testing and Live for real payments.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Razorpay -->
                <section class="wptm-settings-panel" data-panel="razorpay">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Razorpay', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Razorpay', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_razorpay_enabled]" value="1" <?php checked( get_option( 'wptm_razorpay_enabled' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Accept cards, UPI, netbanking and wallets via Razorpay at checkout.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Key ID', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_razorpay_key_id]" value="<?php echo esc_attr( get_option( 'wptm_razorpay_key_id' ) ); ?>" class="wptm-field__input" placeholder="rzp_live_… / rzp_test_…">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Razorpay Key ID (Dashboard → Settings → API Keys).', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Key Secret', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_razorpay_key_secret]" value="<?php echo esc_attr( get_option( 'wptm_razorpay_key_secret' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your Razorpay Key Secret. Keep this private.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Endpoint', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div class="wptm-copy-row">
                                <input type="text" class="wptm-field__input" id="wptm-razorpay-webhook-url" value="<?php echo esc_attr( \WPTravelMachine\Payment\RazorpayGateway::webhook_url() ); ?>" readonly onfocus="this.select()">
                                <button type="button" class="button wptm-copy-btn" data-copy-target="#wptm-razorpay-webhook-url"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'wp-travel-machine' ); ?></button>
                            </div>
                            <p class="wptm-field__desc">
                                <?php esc_html_e( 'In the Razorpay Dashboard → Settings → Webhooks, add a webhook with this URL and subscribe to the', 'wp-travel-machine' ); ?>
                                <code>order.paid</code> <?php esc_html_e( 'event. This confirms bookings even if the customer closes the payment window.', 'wp-travel-machine' ); ?>
                            </p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Webhook Secret', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_razorpay_webhook_secret]" value="<?php echo esc_attr( get_option( 'wptm_razorpay_webhook_secret' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The secret you set when creating the webhook in Razorpay. Required to verify incoming events.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Panel: Manual Payment -->
                <section class="wptm-settings-panel" data-panel="manual">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Manual Payment', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Manual Payment', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_manual_payment]" value="1" <?php checked( get_option( 'wptm_manual_payment', true ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Allow bookings to be paid offline (bank transfer, cash, etc.).', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label for="wptm_bank_instructions"><?php esc_html_e( 'Bank / Payment Instructions', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea id="wptm_bank_instructions" name="settings[wptm_bank_instructions]" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Transfer the total to Bank XYZ, Account 0000-0000, using your booking number as the reference.', 'wp-travel-machine' ); ?>"><?php echo esc_textarea( get_option( 'wptm_bank_instructions', '' ) ); ?></textarea>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Shown on the order confirmation page when a customer pays by bank transfer. Leave blank to use the default message.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: AI -->
                <section class="wptm-settings-panel" data-panel="ai">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'AI Configuration', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Available features', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <p class="wptm-field__desc" style="margin-top:2px">
                                <strong><?php esc_html_e( 'Free:', 'wp-travel-machine' ); ?></strong>
                                <?php esc_html_e( 'Natural-language search · Chat assistant (text replies).', 'wp-travel-machine' ); ?><br>
                                <strong><?php esc_html_e( 'Pro:', 'wp-travel-machine' ); ?></strong>
                                <?php esc_html_e( 'AI Trip Builder · Smart recommendations & in-chat bookable cards · Itinerary generator · AI customer replies.', 'wp-travel-machine' ); ?>
                                <?php if ( ! wptm_is_pro() ) : ?><br><em><?php esc_html_e( 'Uses your own provider API key. Upgrade to Pro to unlock the rest.', 'wp-travel-machine' ); ?></em><?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable AI', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enable_ai]" value="1" <?php checked( get_option( 'wptm_enable_ai' ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Enable AI-powered search and the trip assistant.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Provider', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <select name="settings[wptm_ai_provider]" class="wptm-field__select wptm-field__select--sm">
                                <option value="anthropic" <?php selected( get_option( 'wptm_ai_provider' ), 'anthropic' ); ?>><?php esc_html_e( 'Anthropic (Claude)', 'wp-travel-machine' ); ?></option>
                                <option value="openai" <?php selected( get_option( 'wptm_ai_provider' ), 'openai' ); ?>><?php esc_html_e( 'OpenAI (GPT)', 'wp-travel-machine' ); ?></option>
                                <option value="custom" <?php selected( get_option( 'wptm_ai_provider' ), 'custom' ); ?>><?php esc_html_e( 'Custom — OpenAI-compatible (Groq, Gemini, OpenRouter, Ollama…)', 'wp-travel-machine' ); ?></option>
                            </select>
                            <p class="wptm-field__desc"><?php esc_html_e( 'The AI service used to generate recommendations. Choose "Custom" to use a free-tier or self-hosted provider.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'API Key', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="password" name="settings[wptm_ai_api_key]" value="<?php echo esc_attr( get_option( 'wptm_ai_api_key' ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Your API key for the selected provider. Keep this private.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Base URL', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_ai_base_url]" value="<?php echo esc_attr( get_option( 'wptm_ai_base_url' ) ); ?>" class="wptm-field__input" placeholder="https://api.groq.com/openai/v1">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Only for the "Custom" provider. The OpenAI-compatible base URL (e.g. Groq: https://api.groq.com/openai/v1).', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Model', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_ai_model]" value="<?php echo esc_attr( get_option( 'wptm_ai_model' ) ); ?>" class="wptm-field__input" placeholder="llama-3.3-70b-versatile">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Required for "Custom"; optional for OpenAI/Anthropic (leave blank for the default). Groq example: llama-3.3-70b-versatile.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Email -->
                <section class="wptm-settings-panel" data-panel="email">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Email Notifications', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'From Name', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_email_from_name]" value="<?php echo esc_attr( get_option( 'wptm_email_from_name', get_bloginfo( 'name' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The sender name shown on all booking emails.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'From Email', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_email_from_address]" value="<?php echo esc_attr( get_option( 'wptm_email_from_address', get_option( 'admin_email' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'The sender address. Use an address on your own domain for best deliverability.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Customer Confirmation', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_email_customer_enabled]" value="1" <?php checked( get_option( 'wptm_email_customer_enabled', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Email the customer a confirmation when they book and when their booking status changes.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Confirmation Subject', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_email_customer_subject]" value="<?php echo esc_attr( get_option( 'wptm_email_customer_subject', __( 'Thanks for your booking, {customer_name}! ({booking_number})', 'wp-travel-machine' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Placeholders: {customer_name}, {booking_number}, {item}, {total}, {site_name}.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Admin Notification', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_email_admin_enabled]" value="1" <?php checked( get_option( 'wptm_email_admin_enabled', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Notify your team whenever a new booking is placed.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Notification Email', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_booking_email]" value="<?php echo esc_attr( get_option( 'wptm_booking_email', get_option( 'admin_email' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'New booking notifications are sent to this address.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Email Footer Text', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea name="settings[wptm_email_footer_text]" rows="2" class="wptm-field__input"><?php echo esc_textarea( get_option( 'wptm_email_footer_text', '' ) ); ?></textarea>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Optional footer line shown on every email (e.g. business address, support contact).', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Test Delivery', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <input type="email" id="wptm-test-email" class="wptm-field__input" style="max-width:280px;" placeholder="<?php echo esc_attr( get_option( 'wptm_booking_email', get_option( 'admin_email' ) ) ); ?>">
                                <button type="button" class="button" id="wptm-send-test-email"><?php esc_html_e( 'Send test email', 'wp-travel-machine' ); ?></button>
                                <span id="wptm-test-email-result" style="font-size:13px;"></span>
                            </div>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Send a sample email to confirm your server can deliver mail. If it fails, install an SMTP plugin.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <!-- Panel: Enquiry Form -->
                <section class="wptm-settings-panel" data-panel="enquiry">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Enquiry Form', 'wp-travel-machine' ); ?></h2>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Enable Enquiry Form', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <label class="wptm-switch">
                                <input type="checkbox" name="settings[wptm_enquiry_enabled]" value="1" <?php checked( get_option( 'wptm_enquiry_enabled', 1 ) ); ?>>
                                <span class="wptm-switch__slider"></span>
                            </label>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Show the enquiry form on single trip and hotel pages.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Form Title', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_enquiry_title]" value="<?php echo esc_attr( get_option( 'wptm_enquiry_title', __( 'Have a question? Send an enquiry', 'wp-travel-machine' ) ) ); ?>" class="wptm-field__input">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Send Enquiries To', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_enquiry_email]" value="<?php echo esc_attr( get_option( 'wptm_enquiry_email', get_option( 'admin_email' ) ) ); ?>" class="wptm-field__input">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Submitted enquiries are emailed to this address.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Form Fields', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div class="wptm-repeater wptm-enquiry-builder">
                                <input type="hidden" name="settings[wptm_enquiry_present]" value="1">
                                <div class="wptm-enquiry-field-head">
                                    <span><?php esc_html_e( 'Label', 'wp-travel-machine' ); ?></span>
                                    <span><?php esc_html_e( 'Type', 'wp-travel-machine' ); ?></span>
                                    <span><?php esc_html_e( 'Options (dropdown)', 'wp-travel-machine' ); ?></span>
                                    <span><?php esc_html_e( 'Req.', 'wp-travel-machine' ); ?></span>
                                    <span></span>
                                </div>
                                <div class="wptm-repeater-items">
                                    <?php
                                    $wptm_types = array( 'text' => __( 'Text', 'wp-travel-machine' ), 'email' => __( 'Email', 'wp-travel-machine' ), 'tel' => __( 'Phone', 'wp-travel-machine' ), 'number' => __( 'Number', 'wp-travel-machine' ), 'textarea' => __( 'Textarea', 'wp-travel-machine' ), 'select' => __( 'Dropdown', 'wp-travel-machine' ), 'country' => __( 'Country list', 'wp-travel-machine' ) );
                                    foreach ( wptm_enquiry_fields() as $i => $f ) : ?>
                                    <div class="wptm-repeater-item"><div class="wptm-enquiry-field-row">
                                        <input type="text" name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $f['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Field label', 'wp-travel-machine' ); ?>" class="widefat">
                                        <select name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][type]">
                                            <?php foreach ( $wptm_types as $tk => $tl ) : ?>
                                            <option value="<?php echo esc_attr( $tk ); ?>" <?php selected( $f['type'] ?? 'text', $tk ); ?>><?php echo esc_html( $tl ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][options]" value="<?php echo esc_attr( $f['options'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'A, B, C', 'wp-travel-machine' ); ?>" class="widefat">
                                        <label class="wptm-enquiry-req"><input type="checkbox" name="settings[wptm_enquiry_fields][<?php echo (int) $i; ?>][required]" value="1" <?php checked( ! empty( $f['required'] ) ); ?>></label>
                                        <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove field', 'wp-travel-machine' ); ?>"><span class="dashicons dashicons-trash"></span></button>
                                    </div></div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="button wptm-add-item" data-target="enquiry"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Field', 'wp-travel-machine' ); ?></button>
                            </div>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Add or remove fields shown on the enquiry form. For "Dropdown", list options separated by commas.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>

                <?php if ( wptm_is_pro() ) : ?>
                <!-- Panel: Invoice -->
                <section class="wptm-settings-panel" data-panel="invoice">
                    <h2 class="wptm-panel-title"><?php esc_html_e( 'Company & Invoice', 'wp-travel-machine' ); ?></h2>
                    <div class="wptm-panel-intro">
                        <span class="dashicons dashicons-media-document"></span>
                        <p><?php esc_html_e( 'These details appear on the printable invoice you can open from any booking.', 'wp-travel-machine' ); ?></p>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Company Name', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_company]" value="<?php echo esc_attr( get_option( 'wptm_invoice_company', '' ) ); ?>" class="wptm-field__input" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                            <p class="wptm-field__desc"><?php esc_html_e( 'Defaults to your site name if left blank.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Logo', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="text" id="wptm-invoice-logo" name="settings[wptm_invoice_logo]" value="<?php echo esc_attr( get_option( 'wptm_invoice_logo', '' ) ); ?>" class="wptm-field__input" placeholder="https://…/logo.png">
                                <button type="button" class="button wptm-media-picker" data-target="#wptm-invoice-logo" data-type="image"><?php esc_html_e( 'Choose', 'wp-travel-machine' ); ?></button>
                            </div>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Shown at the top of the invoice. Leave blank to use a lettermark.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Business Address', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea name="settings[wptm_invoice_address]" rows="3" class="wptm-field__input" placeholder="<?php esc_attr_e( '123 Travel St, Suite 4&#10;City, Country 0000', 'wp-travel-machine' ); ?>"><?php echo esc_textarea( get_option( 'wptm_invoice_address', '' ) ); ?></textarea>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Contact Email', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="email" name="settings[wptm_invoice_email]" value="<?php echo esc_attr( get_option( 'wptm_invoice_email', '' ) ); ?>" class="wptm-field__input" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Phone', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_phone]" value="<?php echo esc_attr( get_option( 'wptm_invoice_phone', '' ) ); ?>" class="wptm-field__input">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Tax / VAT Number', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_tax_number]" value="<?php echo esc_attr( get_option( 'wptm_invoice_tax_number', '' ) ); ?>" class="wptm-field__input">
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Invoice Number Prefix', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <input type="text" name="settings[wptm_invoice_prefix]" value="<?php echo esc_attr( get_option( 'wptm_invoice_prefix', 'INV-' ) ); ?>" class="wptm-field__input wptm-field__input--sm">
                            <p class="wptm-field__desc"><?php esc_html_e( 'e.g. "INV-" produces invoice numbers like INV-00042.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>

                    <div class="wptm-field">
                        <div class="wptm-field__label"><label><?php esc_html_e( 'Notes & Terms', 'wp-travel-machine' ); ?></label></div>
                        <div class="wptm-field__control">
                            <textarea name="settings[wptm_invoice_notes]" rows="3" class="wptm-field__input" placeholder="<?php esc_attr_e( 'Payment terms, cancellation policy, thank-you note…', 'wp-travel-machine' ); ?>"><?php echo esc_textarea( get_option( 'wptm_invoice_notes', '' ) ); ?></textarea>
                            <p class="wptm-field__desc"><?php esc_html_e( 'Printed in the footer of every invoice.', 'wp-travel-machine' ); ?></p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <div class="wptm-settings__footer">
                    <button type="submit" class="button button-primary wptm-save-btn" id="wptm-save-settings"><?php esc_html_e( 'Save Settings', 'wp-travel-machine' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
