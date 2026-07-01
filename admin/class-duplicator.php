<?php
/**
 * One-click duplicate for Trips and Hotels.
 *
 * Adds a "Duplicate" row action to the Trip/Hotel list tables that clones the
 * post along with its meta, taxonomy terms and — for hotels — the rooms stored
 * in the custom rooms table. The copy is created as a draft.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom-table access: reads/writes the plugin's own tables (no core API, uncacheable transactional data).


class Duplicator {

    /** @var string[] Post types that support duplication. */
    private $post_types = array( 'wptm_trip', 'wptm_hotel' );

    public function __construct() {
        add_filter( 'post_row_actions', array( $this, 'row_action' ), 10, 2 );
        add_action( 'admin_action_wptm_duplicate', array( $this, 'handle' ) );
        add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
    }

    /**
     * Add a "Duplicate" link to the row actions of a trip/hotel.
     *
     * @param array    $actions Existing row actions.
     * @param \WP_Post $post    The post being listed.
     * @return array
     */
    public function row_action( $actions, $post ) {
        if ( ! in_array( $post->post_type, $this->post_types, true ) ) {
            return $actions;
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url( 'admin.php?action=wptm_duplicate&post=' . $post->ID ),
            'wptm_duplicate_' . $post->ID
        );

        $label = ( 'wptm_hotel' === $post->post_type )
            ? __( 'Duplicate Hotel', 'journeyloom' )
            : __( 'Duplicate Trip', 'journeyloom' );

        $actions['wptm_duplicate'] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            esc_url( $url ),
            esc_attr( $label ),
            esc_html__( 'Duplicate', 'journeyloom' )
        );

        return $actions;
    }

    /**
     * Handle the duplicate request and redirect to the new draft.
     */
    public function handle() {
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

        if ( ! $post_id ) {
            wp_die( esc_html__( 'No item to duplicate.', 'journeyloom' ) );
        }
        check_admin_referer( 'wptm_duplicate_' . $post_id );

        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, $this->post_types, true ) ) {
            wp_die( esc_html__( 'This item cannot be duplicated.', 'journeyloom' ) );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( esc_html__( 'You are not allowed to duplicate this item.', 'journeyloom' ) );
        }

        $new_id = $this->duplicate_post( $post );

        if ( ! $new_id || is_wp_error( $new_id ) ) {
            wp_die( esc_html__( 'Could not duplicate this item.', 'journeyloom' ) );
        }

        wp_safe_redirect( add_query_arg( 'wptm_duplicated', 1, admin_url( 'post.php?action=edit&post=' . $new_id ) ) );
        exit;
    }

    /**
     * Create the duplicate post and copy meta, terms and rooms.
     *
     * @param \WP_Post $post Source post.
     * @return int|\WP_Error New post ID or error.
     */
    private function duplicate_post( $post ) {
        $new_id = wp_insert_post( array(
            'post_type'      => $post->post_type,
            'post_title'     => $post->post_title . ' ' . __( '(Copy)', 'journeyloom' ),
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_status'    => 'draft',
            'post_author'    => get_current_user_id(),
            'post_parent'    => $post->post_parent,
            'menu_order'     => $post->menu_order,
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
        ), true );

        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        $this->copy_meta( $post->ID, $new_id );
        $this->copy_terms( $post, $new_id );

        if ( 'wptm_hotel' === $post->post_type ) {
            $this->copy_rooms( $post->ID, $new_id );
        }

        /**
         * Fires after a trip/hotel has been duplicated.
         *
         * @param int      $new_id New post ID.
         * @param \WP_Post $post   Source post.
         */
        do_action( 'wptm_duplicated_post', $new_id, $post );

        return $new_id;
    }

    /**
     * Copy all post meta except internal edit locks.
     *
     * @param int $from Source post ID.
     * @param int $to   Destination post ID.
     */
    private function copy_meta( $from, $to ) {
        $skip = array( '_edit_lock', '_edit_last', '_wp_old_slug' );
        $all  = get_post_meta( $from );
        if ( ! is_array( $all ) ) {
            return;
        }
        foreach ( array_keys( $all ) as $key ) {
            if ( in_array( $key, $skip, true ) ) {
                continue;
            }
            // Per-key form returns already-unserialized values; wp_slash so
            // add_post_meta's internal wp_unslash round-trips arrays intact.
            foreach ( get_post_meta( $from, $key ) as $value ) {
                add_post_meta( $to, $key, wp_slash( $value ) );
            }
        }
    }

    /**
     * Copy every taxonomy term assignment from the source post.
     *
     * @param \WP_Post $post Source post.
     * @param int      $to   Destination post ID.
     */
    private function copy_terms( $post, $to ) {
        $taxonomies = get_object_taxonomies( $post->post_type );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $to, $terms, $taxonomy );
            }
        }
    }

    /**
     * Copy hotel rooms (custom table) to the duplicated hotel.
     *
     * @param int $from Source hotel ID.
     * @param int $to   Destination hotel ID.
     */
    private function copy_rooms( $from, $to ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wptm_rooms';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rooms = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE hotel_id = %d", $from ), ARRAY_A );
        if ( empty( $rooms ) ) {
            return;
        }
        foreach ( $rooms as $room ) {
            unset( $room['id'] );
            $room['hotel_id'] = $to;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert( $table, $room );
        }
    }

    /**
     * Show a success notice on the duplicated draft's edit screen.
     */
    public function maybe_notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only presence check to show an admin notice.
        if ( empty( $_GET['wptm_duplicated'] ) ) {
            return;
        }
        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html__( 'Item duplicated. This is the draft copy — review and publish when ready.', 'journeyloom' )
            . '</p></div>';
    }
}
