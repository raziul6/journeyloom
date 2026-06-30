<?php
namespace WPTravelMachine\Pub;

if ( ! defined( 'ABSPATH' ) ) exit;

class Shortcodes {
    public function __construct() {
        add_shortcode( 'wptm_trips', array( $this, 'trips_grid' ) );
        add_shortcode( 'wptm_hotels', array( $this, 'hotels_grid' ) );
        add_shortcode( 'wptm_search_form', array( $this, 'search_form' ) );
        add_shortcode( 'wptm_booking_form', array( $this, 'booking_form' ) );
        add_shortcode( 'wptm_destinations', array( $this, 'destinations_grid' ) );
        add_shortcode( 'wptm_terms', array( $this, 'terms_grid' ) );
        add_shortcode( 'wptm_ai_chat', array( $this, 'ai_chat' ) );
        add_shortcode( 'wptm_ai_recommend', array( $this, 'ai_recommend' ) );
        add_shortcode( 'wptm_checkout', array( $this, 'checkout_page' ) );
        add_shortcode( 'wptm_booking_confirmation', array( $this, 'confirmation_page' ) );
        add_shortcode( 'wptm_wishlist', array( $this, 'wishlist_page' ) );
        add_shortcode( 'wptm_cart', array( $this, 'cart_page' ) );
        add_shortcode( 'wptm_my_bookings', array( $this, 'my_bookings_page' ) );
    }

    public function trips_grid( $atts ) {
        // Listing shortcodes paginate with the global "items per page" count.
        // filters="yes" adds the full filter bar (off by default so it doesn't
        // duplicate a [wptm_search_form] placed on the same page).
        $defaults = array_merge( \WPTravelMachine\Blocks\Renderer::defaults(), array( 'count' => 12, 'paginate' => 'yes', 'filters' => 'no' ) );
        $atts     = shortcode_atts( $defaults, $atts );
        return \WPTravelMachine\Blocks\Renderer::trips( $atts );
    }

    public function hotels_grid( $atts ) {
        $defaults = array_merge( \WPTravelMachine\Blocks\Renderer::defaults(), array( 'count' => 12, 'paginate' => 'yes', 'filters' => 'no' ) );
        $atts     = shortcode_atts( $defaults, $atts );
        return \WPTravelMachine\Blocks\Renderer::hotels( $atts );
    }

    public function search_form( $atts ) {
        $atts = shortcode_atts( \WPTravelMachine\Blocks\Renderer::defaults(), $atts );
        return \WPTravelMachine\Blocks\Renderer::search( $atts );
    }

    public function booking_form( $atts ) {
        $atts = shortcode_atts( \WPTravelMachine\Blocks\Renderer::defaults(), $atts );
        return \WPTravelMachine\Blocks\Renderer::booking( $atts );
    }

    public function destinations_grid( $atts ) {
        $atts = shortcode_atts( array( 'count' => 8 ), $atts );
        return $this->render_terms_grid( 'wptm_destination', array(
            'count' => $atts['count'],
            'empty' => __( 'No destinations found. Add destinations in the admin area.', 'wp-travel-machine' ),
        ) );
    }

    /**
     * Generic taxonomy term grid — [wptm_terms taxonomy="wptm_activity" columns="4"].
     *
     * Renders any WPTM taxonomy as a grid of decorated image cards linking to the
     * term archive. Powers the auto-created Activities / Trip Types / Difficulty /
     * Hotel Types / Hotel Facilities pages.
     */
    public function terms_grid( $atts ) {
        $atts = shortcode_atts( array(
            'taxonomy' => 'wptm_destination',
            'count'    => 0,
            'columns'  => 4,
            'orderby'  => 'name',
            'order'    => 'ASC',
        ), $atts, 'wptm_terms' );

        if ( ! taxonomy_exists( $atts['taxonomy'] ) ) {
            return '';
        }

        return $this->render_terms_grid( $atts['taxonomy'], array(
            'count'   => $atts['count'],
            'columns' => $atts['columns'],
            'orderby' => $atts['orderby'],
            'order'   => $atts['order'],
        ) );
    }

    /**
     * Shared term-grid renderer used by [wptm_destinations] and [wptm_terms].
     *
     * @param string $taxonomy Taxonomy name.
     * @param array  $args     count, columns, placeholder, empty, orderby, order.
     * @return string
     */
    private function render_terms_grid( $taxonomy, $args = array() ) {
        $args = wp_parse_args( $args, array(
            'count'       => 0,
            'columns'     => 4,
            'placeholder' => '',
            'empty'       => __( 'Nothing found yet. Add terms in the admin area.', 'wp-travel-machine' ),
            'orderby'     => 'name',
            'order'       => 'ASC',
        ) );

        $tax_obj = get_taxonomy( $taxonomy );
        $noun    = ( $tax_obj && in_array( 'wptm_hotel', (array) $tax_obj->object_type, true ) && ! in_array( 'wptm_trip', (array) $tax_obj->object_type, true ) )
            ? __( 'hotels', 'wp-travel-machine' )
            : __( 'trips', 'wp-travel-machine' );

        if ( '' === $args['placeholder'] ) {
            // Fall back to a premium SVG icon per taxonomy.
            $icons = array(
                'wptm_destination'    => 'globe',
                'wptm_activity'       => 'target',
                'wptm_trip_type'      => 'compass',
                'wptm_difficulty'     => 'mountain',
                'wptm_hotel_type'     => 'building',
                'wptm_hotel_facility' => 'bell',
            );
            $args['placeholder'] = isset( $icons[ $taxonomy ] ) ? $icons[ $taxonomy ] : 'tag';
        }

        $query_args = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => $args['orderby'],
            'order'      => $args['order'],
        );
        if ( (int) $args['count'] > 0 ) {
            $query_args['number'] = (int) $args['count'];
        }

        /** Filter the get_terms() args for a [wptm_terms] / [wptm_destinations] grid. */
        $query_args = apply_filters( 'wptm_terms_grid_args', $query_args, $taxonomy );

        $terms = get_terms( $query_args );

        ob_start();
        echo '<div class="wptm-destinations-grid" data-taxonomy="' . esc_attr( $taxonomy ) . '">';
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $img = get_term_meta( $term->term_id, '_wptm_image', true );
                echo '<a href="' . esc_url( get_term_link( $term ) ) . '" class="wptm-destination-card">';
                if ( $img ) {
                    echo '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy">';
                } else {
                    // Render a library icon when the placeholder is an icon name, else plain text.
                    $ph_icon = function_exists( 'wptm_icon' ) ? wptm_icon( $args['placeholder'], array( 'size' => 38 ) ) : '';
                    echo '<div class="wptm-destination-card__placeholder">' . ( $ph_icon ?: esc_html( $args['placeholder'] ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
                }
                echo '<div class="wptm-destination-overlay"><h3>' . esc_html( $term->name ) . '</h3><span>' . esc_html( number_format_i18n( $term->count ) ) . ' ' . esc_html( $noun ) . '</span></div></a>';
            }
        } else {
            echo '<p style="text-align:center;color:#94a3b8;padding:40px 0;">' . esc_html( $args['empty'] ) . '</p>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function ai_chat( $atts ) {
        if ( ! get_option( 'wptm_enable_ai' ) ) return '';
        // Shared renderer guards against a double render when the widget is also
        // auto-injected in the footer.
        return PublicHandler::ai_chat_markup();
    }

    public function ai_recommend( $atts ) {
        // Smart recommendations (bookable cards) are a Pro feature; the chat
        // assistant covers the free tier.
        if ( ! get_option( 'wptm_enable_ai' ) || ! wptm_is_pro() ) return '';
        $atts = shortcode_atts( array(
            'title' => __( 'Find your perfect trip', 'wp-travel-machine' ),
        ), $atts, 'wptm_ai_recommend' );
        ob_start();
        include WPTM_PLUGIN_DIR . 'templates/partials/ai-recommend.php';
        return ob_get_clean();
    }

    public function checkout_page( $atts ) {
        ob_start();
        $file = WPTM_PLUGIN_DIR . 'templates/booking/checkout.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="wptm-checkout-wrap"><p>' . esc_html__( 'Checkout page template not found.', 'wp-travel-machine' ) . '</p></div>';
        }
        return ob_get_clean();
    }

    public function confirmation_page( $atts ) {
        ob_start();
        $file = WPTM_PLUGIN_DIR . 'templates/booking/confirmation.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            $booking_id = absint( $_GET['booking'] ?? 0 );
            if ( $booking_id ) {
                $booking = \WPTravelMachine\Booking\BookingEngine::get_booking( $booking_id );
                if ( $booking ) {
                    $sym = get_option( 'wptm_currency_symbol', '$' );
                    echo '<div class="wptm-confirmation">';
                    echo '<div class="wptm-confirmation__icon">✅</div>';
                    echo '<h2>' . esc_html__( 'Booking Confirmed!', 'wp-travel-machine' ) . '</h2>';
                    echo '<p>' . sprintf( esc_html__( 'Your booking #%s has been received.', 'wp-travel-machine' ), esc_html( $booking->booking_number ) ) . '</p>';
                    echo '<p>' . sprintf( esc_html__( 'Total: %s', 'wp-travel-machine' ), esc_html( $sym . number_format( $booking->total_price, 2 ) ) ) . '</p>';
                    echo '<p>' . sprintf( esc_html__( 'Status: %s', 'wp-travel-machine' ), esc_html( ucfirst( $booking->status ) ) ) . '</p>';
                    echo '</div>';
                } else {
                    echo '<p>' . esc_html__( 'Booking not found.', 'wp-travel-machine' ) . '</p>';
                }
            } else {
                echo '<p>' . esc_html__( 'No booking specified.', 'wp-travel-machine' ) . '</p>';
            }
        }
        return ob_get_clean();
    }

    public function wishlist_page( $atts ) {
        ob_start();
        if ( ! is_user_logged_in() ) {
            echo '<div class="wptm-wishlist-login"><p>' . esc_html__( 'Please log in to view your wishlist.', 'wp-travel-machine' ) . '</p>';
            echo '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="wptm-btn wptm-btn--primary">' . esc_html__( 'Log In', 'wp-travel-machine' ) . '</a></div>';
        } else {
            global $wpdb;
            $items = $wpdb->get_results( $wpdb->prepare(
                "SELECT item_id, item_type FROM {$wpdb->prefix}wptm_wishlist WHERE user_id = %d ORDER BY created_at DESC",
                get_current_user_id()
            ) );
            echo '<div class="wptm-wishlist-page">';

            // Empty state — shown now if empty, or revealed by JS once the last
            // item is removed on this page.
            $browse = esc_url( get_post_type_archive_link( 'wptm_trip' ) );
            printf(
                '<div class="wptm-wishlist-empty"%s><p>💝 %s</p><a href="%s" class="wptm-btn wptm-btn--primary">%s</a></div>',
                empty( $items ) ? '' : ' style="display:none;"',
                esc_html__( 'Your wishlist is empty.', 'wp-travel-machine' ),
                $browse,
                esc_html__( 'Browse Trips', 'wp-travel-machine' )
            );

            if ( ! empty( $items ) ) {
                echo '<div class="wptm-grid wptm-grid-3 wptm-wishlist-grid">';
                foreach ( $items as $item ) {
                    $post = get_post( $item->item_id );
                    if ( ! $post || 'publish' !== $post->post_status ) continue;
                    global $post;
                    $post = get_post( $item->item_id );
                    setup_postdata( $post );
                    if ( 'wptm_hotel' === $item->item_type ) {
                        include WPTM_PLUGIN_DIR . 'templates/partials/hotel-card.php';
                    } else {
                        include WPTM_PLUGIN_DIR . 'templates/partials/trip-card.php';
                    }
                }
                wp_reset_postdata();
                echo '</div>';
            }

            echo '</div>';
        }
        return ob_get_clean();
    }

    public function my_bookings_page( $atts ) {
        ob_start();
        $file = WPTM_PLUGIN_DIR . 'templates/booking/my-bookings.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<p>' . esc_html__( 'My Bookings template not found.', 'wp-travel-machine' ) . '</p>';
        }
        return ob_get_clean();
    }

    public function cart_page( $atts ) {
        ob_start();
        $cart_module = \WPTravelMachine\Plugin::get_instance()->get_module( 'cart' );
        if ( ! $cart_module ) {
            echo '<p>' . esc_html__( 'Cart not available.', 'wp-travel-machine' ) . '</p>';
            return ob_get_clean();
        }
        $summary = $cart_module->get_cart_summary();
        $sym = get_option( 'wptm_currency_symbol', '$' );
        if ( empty( $summary['items'] ) ) {
            echo '<div style="text-align:center;padding:60px 0;"><p style="color:#94a3b8;font-size:18px;">🛒 ' . esc_html__( 'Your cart is empty.', 'wp-travel-machine' ) . '</p>';
            echo '<a href="' . esc_url( get_post_type_archive_link( 'wptm_trip' ) ) . '" class="wptm-btn wptm-btn--primary">' . esc_html__( 'Browse Trips', 'wp-travel-machine' ) . '</a></div>';
        } else {
            echo '<div class="wptm-cart">';
            echo '<table class="wptm-cart-table"><thead><tr>';
            echo '<th>' . esc_html__( 'Item', 'wp-travel-machine' ) . '</th>';
            echo '<th>' . esc_html__( 'Price', 'wp-travel-machine' ) . '</th>';
            echo '<th>' . esc_html__( 'Qty', 'wp-travel-machine' ) . '</th>';
            echo '<th>' . esc_html__( 'Subtotal', 'wp-travel-machine' ) . '</th>';
            echo '<th></th></tr></thead><tbody>';
            foreach ( $summary['items'] as $item ) {
                echo '<tr data-key="' . esc_attr( $item['key'] ) . '">';
                echo '<td><strong>' . esc_html( $item['title'] ) . '</strong></td>';
                echo '<td>' . esc_html( $sym . number_format( $item['price'], 2 ) ) . '</td>';
                echo '<td>' . esc_html( $item['quantity'] ) . '</td>';
                echo '<td>' . esc_html( $sym . number_format( $item['subtotal'], 2 ) ) . '</td>';
                echo '<td><button class="wptm-cart-remove" data-key="' . esc_attr( $item['key'] ) . '">✕</button></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '<div class="wptm-cart-totals">';
            echo '<div class="line"><span>' . esc_html__( 'Total', 'wp-travel-machine' ) . '</span><strong>' . esc_html( $sym . number_format( $summary['final_total'], 2 ) ) . '</strong></div>';
            $checkout_url = wptm_get_page_url( 'checkout' ) ?: '#';
            echo '<a href="' . esc_url( $checkout_url ) . '" class="wptm-btn wptm-btn--primary wptm-btn--lg" style="width:100%;text-align:center;margin-top:16px;">' . esc_html__( 'Proceed to Checkout', 'wp-travel-machine' ) . '</a>';
            echo '</div></div>';
        }
        return ob_get_clean();
    }
}
