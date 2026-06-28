<?php
/**
 * Base class for all WP Travel Machine taxonomies.
 *
 * Sub-classes only declare a small config (slug, labels, objects, icon) and get
 * a fully-decorated taxonomy for free: complete label set, REST + admin column
 * support, a featured term image (used on listing cards & archives) and a
 * thumbnail column on the term table.
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class AbstractTaxonomy {

    /** @var string Registered taxonomy name, e.g. 'wptm_destination'. */
    protected $taxonomy = '';

    /** @var string Short key used for filter names & page options, e.g. 'destination'. */
    protected $key = '';

    /** @var array Post types this taxonomy is attached to. */
    protected $object_types = array( 'wptm_trip' );

    /** @var bool Whether the taxonomy is hierarchical (category-like) or flat (tag-like). */
    protected $hierarchical = true;

    /** @var string Singular label, e.g. 'Destination'. */
    protected $singular = '';

    /** @var string Plural label, e.g. 'Destinations'. */
    protected $plural = '';

    /** @var string Rewrite slug, e.g. 'destination'. */
    protected $slug = '';

    /** @var string Emoji placeholder shown on cards when a term has no image. */
    protected $icon = '🌍';

    public function __construct() {
        // Defer everything to init: configure() calls __(), and translations must
        // not load before the init hook (WP 6.7+ notices otherwise).
        add_action( 'init', array( $this, 'boot' ), 9 );
    }

    /**
     * Configure, register the taxonomy and wire its admin hooks. Runs on init.
     */
    public function boot() {
        $this->configure();
        $this->register();

        // Featured term image (admin add/edit + save).
        add_action( $this->taxonomy . '_add_form_fields', array( $this, 'render_add_image_field' ) );
        add_action( $this->taxonomy . '_edit_form_fields', array( $this, 'render_edit_image_field' ), 10, 2 );
        add_action( 'created_' . $this->taxonomy, array( $this, 'save_image' ) );
        add_action( 'edited_' . $this->taxonomy, array( $this, 'save_image' ) );

        // Image thumbnail column on the term list table.
        add_filter( 'manage_edit-' . $this->taxonomy . '_columns', array( $this, 'image_column_header' ) );
        add_filter( 'manage_' . $this->taxonomy . '_custom_column', array( $this, 'image_column_content' ), 10, 3 );
    }

    /**
     * Sub-classes set $taxonomy, $key, $object_types, $singular, $plural, $slug,
     * $hierarchical and $icon here.
     */
    abstract protected function configure();

    /**
     * Register the taxonomy with a complete, translatable label set.
     */
    public function register() {
        $singular = $this->singular;
        $plural   = $this->plural;
        $lower    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $plural ) : strtolower( $plural );

        $labels = array(
            'name'                       => $plural,
            'singular_name'              => $singular,
            'menu_name'                  => $plural,
            'all_items'                  => sprintf( /* translators: %s: plural taxonomy label. */ __( 'All %s', 'wp-travel-machine' ), $plural ),
            'edit_item'                  => sprintf( /* translators: %s: singular taxonomy label. */ __( 'Edit %s', 'wp-travel-machine' ), $singular ),
            'view_item'                  => sprintf( /* translators: %s: singular taxonomy label. */ __( 'View %s', 'wp-travel-machine' ), $singular ),
            'update_item'                => sprintf( /* translators: %s: singular taxonomy label. */ __( 'Update %s', 'wp-travel-machine' ), $singular ),
            'add_new_item'               => sprintf( /* translators: %s: singular taxonomy label. */ __( 'Add New %s', 'wp-travel-machine' ), $singular ),
            'new_item_name'              => sprintf( /* translators: %s: singular taxonomy label. */ __( 'New %s Name', 'wp-travel-machine' ), $singular ),
            'parent_item'                => sprintf( /* translators: %s: singular taxonomy label. */ __( 'Parent %s', 'wp-travel-machine' ), $singular ),
            'parent_item_colon'          => sprintf( /* translators: %s: singular taxonomy label. */ __( 'Parent %s:', 'wp-travel-machine' ), $singular ),
            'search_items'               => sprintf( /* translators: %s: plural taxonomy label. */ __( 'Search %s', 'wp-travel-machine' ), $plural ),
            'popular_items'              => sprintf( /* translators: %s: plural taxonomy label. */ __( 'Popular %s', 'wp-travel-machine' ), $plural ),
            'not_found'                  => sprintf( /* translators: %s: lowercase plural taxonomy label. */ __( 'No %s found', 'wp-travel-machine' ), $lower ),
            'no_terms'                   => sprintf( /* translators: %s: lowercase plural taxonomy label. */ __( 'No %s', 'wp-travel-machine' ), $lower ),
            'items_list_navigation'      => sprintf( /* translators: %s: plural taxonomy label. */ __( '%s list navigation', 'wp-travel-machine' ), $plural ),
            'items_list'                 => sprintf( /* translators: %s: plural taxonomy label. */ __( '%s list', 'wp-travel-machine' ), $plural ),
            'separate_items_with_commas' => sprintf( /* translators: %s: lowercase plural taxonomy label. */ __( 'Separate %s with commas', 'wp-travel-machine' ), $lower ),
            'add_or_remove_items'        => sprintf( /* translators: %s: lowercase plural taxonomy label. */ __( 'Add or remove %s', 'wp-travel-machine' ), $lower ),
            'choose_from_most_used'      => sprintf( /* translators: %s: lowercase plural taxonomy label. */ __( 'Choose from the most used %s', 'wp-travel-machine' ), $lower ),
            'back_to_items'              => sprintf( /* translators: %s: plural taxonomy label. */ __( '← Back to %s', 'wp-travel-machine' ), $plural ),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => $this->hierarchical,
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'show_tagcloud'     => ! $this->hierarchical,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => $this->slug, 'with_front' => false ),
        );

        $object_types = apply_filters( "wptm_{$this->key}_taxonomy_objects", $this->object_types );

        /**
         * Filter the taxonomy registration arguments.
         *
         * @param array $args Arguments passed to register_taxonomy().
         */
        register_taxonomy(
            $this->taxonomy,
            $object_types,
            apply_filters( "wptm_{$this->key}_taxonomy_args", $args )
        );
    }

    /* ---------------------------------------------------------------------
     * Featured term image
     * ------------------------------------------------------------------- */

    /**
     * Image picker on the "Add new term" screen.
     */
    public function render_add_image_field() {
        ?>
        <div class="form-field term-group wptm-term-image-field">
            <label><?php esc_html_e( 'Featured Image', 'wp-travel-machine' ); ?></label>
            <input type="hidden" name="wptm_term_image" class="wptm-term-image-input" value="">
            <div class="wptm-term-image-preview" style="margin:8px 0;"></div>
            <button type="button" class="button wptm-term-image-upload"><?php esc_html_e( 'Upload / Select Image', 'wp-travel-machine' ); ?></button>
            <button type="button" class="button wptm-term-image-remove" style="display:none;"><?php esc_html_e( 'Remove', 'wp-travel-machine' ); ?></button>
            <p class="description"><?php esc_html_e( 'Shown on listing cards and the term archive header.', 'wp-travel-machine' ); ?></p>
        </div>
        <?php
        wp_nonce_field( 'wptm_term_image', 'wptm_term_image_nonce' );
    }

    /**
     * Image picker on the "Edit term" screen.
     *
     * @param \WP_Term $term Current term.
     */
    public function render_edit_image_field( $term ) {
        $image = get_term_meta( $term->term_id, '_wptm_image', true );
        ?>
        <tr class="form-field term-group-wrap wptm-term-image-field">
            <th scope="row"><label><?php esc_html_e( 'Featured Image', 'wp-travel-machine' ); ?></label></th>
            <td>
                <input type="hidden" name="wptm_term_image" class="wptm-term-image-input" value="<?php echo esc_attr( $image ); ?>">
                <div class="wptm-term-image-preview" style="margin:0 0 8px;">
                    <?php if ( $image ) : ?>
                        <img src="<?php echo esc_url( $image ); ?>" alt="" style="max-width:180px;height:auto;border-radius:8px;display:block;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button wptm-term-image-upload"><?php esc_html_e( 'Upload / Select Image', 'wp-travel-machine' ); ?></button>
                <button type="button" class="button wptm-term-image-remove"<?php echo $image ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'wp-travel-machine' ); ?></button>
                <p class="description"><?php esc_html_e( 'Shown on listing cards and the term archive header.', 'wp-travel-machine' ); ?></p>
            </td>
        </tr>
        <?php
        wp_nonce_field( 'wptm_term_image', 'wptm_term_image_nonce' );
    }

    /**
     * Persist the featured image URL on term create/update.
     *
     * @param int $term_id Term ID.
     */
    public function save_image( $term_id ) {
        if ( ! isset( $_POST['wptm_term_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wptm_term_image_nonce'] ) ), 'wptm_term_image' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }
        if ( ! isset( $_POST['wptm_term_image'] ) ) {
            return;
        }
        $url = esc_url_raw( trim( wp_unslash( $_POST['wptm_term_image'] ) ) );
        if ( $url ) {
            update_term_meta( $term_id, '_wptm_image', $url );
        } else {
            delete_term_meta( $term_id, '_wptm_image' );
        }
    }

    /* ---------------------------------------------------------------------
     * Admin term-table image column
     * ------------------------------------------------------------------- */

    /**
     * Insert an "Image" column before the term name.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function image_column_header( $columns ) {
        $new = array();
        foreach ( $columns as $k => $v ) {
            if ( 'name' === $k ) {
                $new['wptm_term_image'] = __( 'Image', 'wp-travel-machine' );
            }
            $new[ $k ] = $v;
        }
        return $new;
    }

    /**
     * Render the image column cell (thumbnail or emoji fallback).
     *
     * @param string $content Column content.
     * @param string $column  Column name.
     * @param int    $term_id Term ID.
     * @return string
     */
    public function image_column_content( $content, $column, $term_id ) {
        if ( 'wptm_term_image' !== $column ) {
            return $content;
        }
        $image = get_term_meta( $term_id, '_wptm_image', true );
        if ( $image ) {
            return '<img src="' . esc_url( $image ) . '" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">';
        }
        return '<span style="font-size:22px;line-height:44px;" aria-hidden="true">' . esc_html( $this->icon ) . '</span>';
    }

    /**
     * Public accessor for the taxonomy name.
     *
     * @return string
     */
    public function get_taxonomy() {
        return $this->taxonomy;
    }
}
