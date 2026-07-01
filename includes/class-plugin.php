<?php
/**
 * Main plugin orchestrator class.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Plugin
 *
 * Singleton that boots all modules.
 */
class Plugin {

    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Loaded module instances.
     *
     * @var array
     */
    private $modules = array();

    /**
     * Get singleton instance.
     *
     * @return Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — private for singleton.
     */
    private function __construct() {
        // Translations for WordPress.org-hosted plugins are loaded automatically
        // since WordPress 4.6, so no manual load_plugin_textdomain() call is needed.
        $this->init_modules();
        $this->init_hooks();

        /**
         * Fires once all JourneyLoom modules are loaded.
         *
         * Use this to safely access modules via $plugin->get_module() or to
         * register extensions, gateways, custom templates, etc.
         *
         * @param Plugin $plugin The plugin orchestrator instance.
         */
        do_action( 'wptm_loaded', $this );
    }

    /**
     * Initialize all modules.
     */
    private function init_modules() {
        // Core Post Types.
        $this->modules['trip_cpt']   = new PostTypes\Trip();
        $this->modules['hotel_cpt']  = new PostTypes\Hotel();

        // Taxonomies.
        $this->modules['destination_tax']    = new Taxonomies\Destination();
        $this->modules['activity_tax']       = new Taxonomies\Activity();
        $this->modules['trip_type_tax']      = new Taxonomies\TripType();
        $this->modules['difficulty_tax']     = new Taxonomies\Difficulty();
        $this->modules['hotel_type_tax']     = new Taxonomies\HotelType();
        $this->modules['hotel_facility_tax'] = new Taxonomies\HotelFacility();

        // Booking Engine.
        $this->modules['booking_engine'] = new Booking\BookingEngine();
        $this->modules['cart']           = new Booking\Cart();
        $this->modules['invoice']        = new Booking\Invoice();

        // Payment.
        $this->modules['payment'] = new Payment\PaymentGateway();

        // Search.
        $this->modules['search'] = new Search\SearchEngine();

        // REST API.
        $this->modules['rest'] = new REST\RestController();

        // AI Engine.
        $this->modules['ai'] = new AI\AIEngine();

        // Database.
        $this->modules['database'] = new Database\Schema();

        // Helpers.
        $this->modules['template_loader'] = new Helpers\TemplateLoader();
        $this->modules['email']           = new Helpers\Email();
        $this->modules['schema_markup']   = new Helpers\SchemaMarkup();

        // Admin.
        if ( is_admin() ) {
            $this->modules['admin']        = new Admin\Admin();
            $this->modules['setup_wizard'] = new Admin\SetupWizard();
            $this->modules['pro']          = new Pro();
        }

        // Public-facing.
        $this->modules['public']     = new Pub\PublicHandler();
        $this->modules['shortcodes'] = new Pub\Shortcodes();
        $this->modules['ajax']       = new Pub\AjaxHandler();

        // Gutenberg blocks.
        $this->modules['blocks'] = new Blocks\BlocksLoader();
    }

    /**
     * Register global hooks.
     */
    private function init_hooks() {
        // Flush rewrite rules after CPT registration on activation.
        add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 99 );

        // Run lightweight upgrade routines for already-active installs.
        add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );

        // Plugin action links.
        add_filter( 'plugin_action_links_' . WPTM_PLUGIN_BASENAME, array( $this, 'action_links' ) );
    }

    /**
     * Run idempotent upgrade steps when the stored plugin version is behind.
     *
     * Ensures installs activated before new features (e.g. the taxonomy archive
     * pages) pick them up without requiring a manual re-activation.
     */
    public function maybe_upgrade() {
        // Current "content schema" — bump when new system pages/taxonomies/meta
        // backfills are added.
        $pages_version = 5;

        if ( (int) get_option( 'wptm_pages_version', 0 ) >= $pages_version ) {
            return;
        }

        // create_pages() / seed_default_terms() are idempotent — they skip
        // anything that already exists.
        Activator::create_pages();
        Activator::seed_default_terms();
        Activator::backfill_trip_prices();

        // One-time: clear legacy duplicate wishlist rows and ensure the unique key.
        Activator::dedupe_wishlist();

        // New taxonomies were registered this request; refresh rewrite rules.
        flush_rewrite_rules();

        update_option( 'wptm_pages_version', $pages_version );
    }

    /**
     * Conditionally flush rewrite rules once after activation.
     */
    public function maybe_flush_rewrites() {
        if ( get_transient( 'wptm_flush_rewrites' ) ) {
            flush_rewrite_rules();
            delete_transient( 'wptm_flush_rewrites' );
        }
    }

    /**
     * Add plugin action links.
     *
     * @param array $links Existing links.
     * @return array
     */
    public function action_links( $links ) {
        $custom = array(
            '<a href="' . admin_url( 'admin.php?page=wptm-dashboard' ) . '">' . esc_html__( 'Dashboard', 'journeyloom' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=wptm-settings' ) . '">' . esc_html__( 'Settings', 'journeyloom' ) . '</a>',
            '<a href="' . esc_url( WPTM_PLUGIN_URL . 'Doc/doc.html' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Docs', 'journeyloom' ) . '</a>',
        );
        return array_merge( $custom, $links );
    }

    /**
     * Get a module instance.
     *
     * @param string $key Module key.
     * @return object|null
     */
    public function get_module( $key ) {
        return isset( $this->modules[ $key ] ) ? $this->modules[ $key ] : null;
    }
}
