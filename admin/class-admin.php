<?php
namespace WPTravelMachine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Keep WPTM screens clean of third-party admin notices.
        add_action( 'in_admin_header', array( $this, 'hide_admin_notices' ), 1000 );

        // Keep the Travel Machine menu open/highlighted on taxonomy term screens.
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
            __( 'Travel Machine', 'wp-travel-machine' ),
            __( 'Travel Machine', 'wp-travel-machine' ) . $bubble,
            'manage_options',
            'wptm-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-airplane',
            25
        );
        add_submenu_page( 'wptm-dashboard', __( 'Dashboard', 'wp-travel-machine' ), __( 'Dashboard', 'wp-travel-machine' ), 'manage_options', 'wptm-dashboard', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'wptm-dashboard', __( 'Bookings', 'wp-travel-machine' ), __( 'Bookings', 'wp-travel-machine' ) . $bubble, 'manage_options', 'wptm-bookings', array( $this, 'render_bookings' ) );
        add_submenu_page( 'wptm-dashboard', __( 'Search Form', 'wp-travel-machine' ), __( 'Search Form', 'wp-travel-machine' ), 'manage_options', 'wptm-search-form', array( $this, 'render_search_form' ) );
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

        // Coupons are a Pro feature — only expose the menu when Pro is active.
        if ( wptm_is_pro() ) {
            add_submenu_page( 'wptm-dashboard', __( 'Coupons', 'wp-travel-machine' ), __( 'Coupons', 'wp-travel-machine' ), 'manage_options', 'wptm-coupons', array( $this, 'render_coupons' ) );
        }
        add_submenu_page( 'wptm-dashboard', __( 'Reports', 'wp-travel-machine' ), __( 'Reports', 'wp-travel-machine' ), 'manage_options', 'wptm-reports', array( $this, 'render_reports' ) );
        add_submenu_page( 'wptm-dashboard', __( 'Settings', 'wp-travel-machine' ), __( 'Settings', 'wp-travel-machine' ), 'manage_options', 'wptm-settings', array( $this, 'render_settings' ) );
    }

    /**
     * Taxonomy → admin submenu config: tax name => array( label, post_type ).
     *
     * @return array
     */
    private function get_taxonomy_submenus() {
        return array(
            'wptm_destination'    => array( 'label' => __( 'Destinations', 'wp-travel-machine' ),       'post_type' => 'wptm_trip' ),
            'wptm_activity'       => array( 'label' => __( 'Activities', 'wp-travel-machine' ),          'post_type' => 'wptm_trip' ),
            'wptm_trip_type'      => array( 'label' => __( 'Trip Types', 'wp-travel-machine' ),          'post_type' => 'wptm_trip' ),
            'wptm_difficulty'     => array( 'label' => __( 'Difficulty Levels', 'wp-travel-machine' ),   'post_type' => 'wptm_trip' ),
            'wptm_hotel_type'     => array( 'label' => __( 'Hotel Types', 'wp-travel-machine' ),         'post_type' => 'wptm_hotel' ),
            'wptm_hotel_facility' => array( 'label' => __( 'Hotel Facilities', 'wp-travel-machine' ),    'post_type' => 'wptm_hotel' ),
        );
    }

    /**
     * Force the Travel Machine top-level menu to stay highlighted on our
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

        wp_enqueue_style( 'wptm-admin', WPTM_PLUGIN_URL . 'assets/css/admin.css', array( 'dashicons' ), $css_ver );
        wp_enqueue_script( 'wptm-admin', WPTM_PLUGIN_URL . 'assets/js/admin/admin.js', array(), $js_ver, true );
        wp_localize_script( 'wptm-admin', 'wptmAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wptm_admin_nonce' ),
            'aiNonce' => wp_create_nonce( 'wptm_ai_nonce' ),
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
    public function render_coupons() { include WPTM_PLUGIN_DIR . 'admin/views/coupons.php'; }
    public function render_reports() { include WPTM_PLUGIN_DIR . 'admin/views/reports.php'; }
    public function render_settings() { include WPTM_PLUGIN_DIR . 'admin/views/settings.php'; }
}
