<?php
namespace JourneyLoom\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_head', array( $this, 'menu_upgrade_css' ) );

        // Keep WPTM screens clean of third-party admin notices.
        add_action( 'in_admin_header', array( $this, 'hide_admin_notices' ), 1000 );

        // Keep the JourneyLoom menu open/highlighted on taxonomy term screens.
        add_filter( 'parent_file', array( $this, 'taxonomy_menu_parent' ) );
        add_filter( 'submenu_file', array( $this, 'taxonomy_menu_submenu' ) );

        // Sub-modules.
        new Settings();
        new BookingList();
        new SearchFormBuilder();
        new Duplicator();
        new DemoImporter();
    }

    public function register_menus() {
        // Count of new (pending) bookings — shown as a red bubble on the menu.
        $pending = $this->get_pending_count();
        $bubble  = $pending > 0
            ? sprintf( ' <span class="update-plugins count-%1$d wptm-menu-bubble"><span class="pending-count">%1$d</span></span>', $pending )
            : '';

        add_menu_page(
            __( 'Byteflows Travel', 'byteflows-travel-hotel-booking' ),
            __( 'Byteflows Travel', 'byteflows-travel-hotel-booking' ) . $bubble,
            'manage_options',
            'wptm-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-airplane',
            58
        );
        add_submenu_page( 'wptm-dashboard', __( 'Dashboard', 'byteflows-travel-hotel-booking' ), __( 'Dashboard', 'byteflows-travel-hotel-booking' ), 'manage_options', 'wptm-dashboard', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'wptm-dashboard', __( 'Bookings', 'byteflows-travel-hotel-booking' ), __( 'Bookings', 'byteflows-travel-hotel-booking' ) . $bubble, 'manage_options', 'wptm-bookings', array( $this, 'render_bookings' ) );
        add_submenu_page( 'wptm-dashboard', __( 'Search Form', 'byteflows-travel-hotel-booking' ), __( 'Search Form', 'byteflows-travel-hotel-booking' ), 'manage_options', 'wptm-search-form', array( $this, 'render_search_form' ) );
        // Taxonomy term screens. The Trip/Hotel CPTs are relocated under this
        // custom menu ('show_in_menu' => 'wptm-dashboard'), so WordPress does NOT
        // auto-add their taxonomy submenus — register them explicitly here.
        foreach ( $this->get_taxonomy_submenus() as $tax => $info ) {
            add_submenu_page(
                'wptm-dashboard',
                $info['label'],
                $info['label'],
                'manage_categories',
                'edit-tags.php?taxonomy=' . $tax . '&post_type=' . $info['post_type']
            );
        }

        add_submenu_page( 'wptm-dashboard', __( 'Reports', 'byteflows-travel-hotel-booking' ), __( 'Reports', 'byteflows-travel-hotel-booking' ), 'manage_options', 'wptm-reports', array( $this, 'render_reports' ) );
        add_submenu_page( 'wptm-dashboard', __( 'Settings', 'byteflows-travel-hotel-booking' ), __( 'Settings', 'byteflows-travel-hotel-booking' ), 'manage_options', 'wptm-settings', array( $this, 'render_settings' ) );

        // Upgrade page — only while the Pro add-on is not active.
        if ( ! wptm_is_pro_active() ) {
            add_submenu_page(
                'wptm-dashboard',
                __( 'Upgrade to Pro', 'byteflows-travel-hotel-booking' ),
                '<span class="wptm-pro-menu">' . __( 'Upgrade to Pro', 'byteflows-travel-hotel-booking' ) . ' ★</span>',
                'manage_options',
                'wptm-pro',
                array( $this, 'render_pro' )
            );
        }
    }

    /**
     * Highlight the "Upgrade to Pro" sidebar item on every admin screen.
     * (admin.css only loads on WPTM screens, so this tiny rule is inlined.)
     */
    public function menu_upgrade_css() {
        if ( wptm_is_pro_active() ) return;
        echo '<style>#adminmenu .wptm-pro-menu { color: #ffd9a0; font-weight: 500; }</style>';
    }

    /**
     * Taxonomy → admin submenu config: tax name => array( label, post_type ).
     *
     * @return array
     */
    private function get_taxonomy_submenus() {
        return array(
            'wptm_destination'    => array( 'label' => __( 'Destinations', 'byteflows-travel-hotel-booking' ),       'post_type' => 'wptm_trip' ),
            'wptm_activity'       => array( 'label' => __( 'Activities', 'byteflows-travel-hotel-booking' ),          'post_type' => 'wptm_trip' ),
            'wptm_trip_type'      => array( 'label' => __( 'Trip Types', 'byteflows-travel-hotel-booking' ),          'post_type' => 'wptm_trip' ),
            'wptm_difficulty'     => array( 'label' => __( 'Difficulty Levels', 'byteflows-travel-hotel-booking' ),   'post_type' => 'wptm_trip' ),
            'wptm_hotel_type'     => array( 'label' => __( 'Hotel Types', 'byteflows-travel-hotel-booking' ),         'post_type' => 'wptm_hotel' ),
            'wptm_hotel_facility' => array( 'label' => __( 'Hotel Facilities', 'byteflows-travel-hotel-booking' ),    'post_type' => 'wptm_hotel' ),
        );
    }

    /**
     * Force the JourneyLoom top-level menu to stay highlighted on our
     * taxonomy term screens.
     *
     * @param string $parent_file Current parent menu slug.
     * @return string
     */
    public function taxonomy_menu_parent( $parent_file ) {
        $screen = get_current_screen();
        if ( $screen && 'edit-tags' === $screen->base && isset( $this->get_taxonomy_submenus()[ $screen->taxonomy ] ) ) {
            return 'wptm-dashboard';
        }
        return $parent_file;
    }

    /**
     * Highlight the matching taxonomy submenu item on its term screen.
     *
     * @param string $submenu_file Current submenu slug.
     * @return string
     */
    public function taxonomy_menu_submenu( $submenu_file ) {
        $screen = get_current_screen();
        $menus  = $this->get_taxonomy_submenus();
        if ( $screen && 'edit-tags' === $screen->base && isset( $menus[ $screen->taxonomy ] ) ) {
            return 'edit-tags.php?taxonomy=' . $screen->taxonomy . '&post_type=' . $menus[ $screen->taxonomy ]['post_type'];
        }
        return $submenu_file;
    }

    /**
     * Number of pending (new) bookings — drives the menu count bubble.
     */
    public function get_pending_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'wptm_bookings';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            return 0;
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" );
    }

    /**
     * Is the current admin screen one of ours?
     */
    public function is_wptm_screen() {
        $screen = get_current_screen();
        if ( ! $screen ) return false;
        return strpos( $screen->id, 'wptm' ) !== false
            || in_array( $screen->post_type, array( 'wptm_trip', 'wptm_hotel' ), true );
    }

    /**
     * Strip third-party admin notices from WPTM screens so the UI stays clean.
     */
    public function hide_admin_notices() {
        if ( ! $this->is_wptm_screen() ) return;
        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'network_admin_notices' );
    }

    public function enqueue_assets( $hook ) {
        if ( ! $this->is_wptm_screen() ) return;

        $css_path = WPTM_PLUGIN_DIR . 'assets/css/admin.css';
        $js_path  = WPTM_PLUGIN_DIR . 'assets/js/admin/admin.js';
        $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : WPTM_VERSION;
        $js_ver   = file_exists( $js_path ) ? filemtime( $js_path ) : WPTM_VERSION;

        // Self-hosted Inter font (no external/CDN request), shared with the front end.
        if ( apply_filters( 'wptm_enqueue_fonts', true ) ) {
            $fonts_path = WPTM_PLUGIN_DIR . 'assets/vendor/fonts/fonts.css';
            $fonts_url  = apply_filters( 'wptm_fonts_url', WPTM_PLUGIN_URL . 'assets/vendor/fonts/fonts.css' );
            if ( $fonts_url ) {
                $fonts_ver = file_exists( $fonts_path ) ? filemtime( $fonts_path ) : WPTM_VERSION;
                wp_enqueue_style( 'wptm-fonts', $fonts_url, array(), $fonts_ver );
            }
        }

        wp_enqueue_style( 'wptm-admin', WPTM_PLUGIN_URL . 'assets/css/admin.css', array( 'dashicons' ), $css_ver );
        wp_enqueue_script( 'wptm-admin', WPTM_PLUGIN_URL . 'assets/js/admin/admin.js', array(), $js_ver, true );
        wp_localize_script( 'wptm-admin', 'wptmAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wptm_admin_nonce' ),
            'pluginUrl' => WPTM_PLUGIN_URL,
            'currencySymbol' => get_option( 'wptm_currency_symbol', '$' ),
        ) );

        // Media uploader for galleries.
        wp_enqueue_media();
    }

    public function register_settings() {
        // Settings registered in Settings class.
    }

    public function render_dashboard() { include WPTM_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    public function render_bookings() { include WPTM_PLUGIN_DIR . 'admin/views/bookings.php'; }
    public function render_search_form() { include WPTM_PLUGIN_DIR . 'admin/views/search-form-builder.php'; }
    public function render_reports() { include WPTM_PLUGIN_DIR . 'admin/views/reports.php'; }
    public function render_settings() { include WPTM_PLUGIN_DIR . 'admin/views/settings.php'; }
    public function render_pro() { include WPTM_PLUGIN_DIR . 'admin/views/pro.php'; }
}
