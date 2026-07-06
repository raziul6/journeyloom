<?php
/**
 * Gutenberg + Elementor block loader.
 *
 * Registers server-rendered Gutenberg blocks (Trip Grid, Hotel Grid, Search,
 * Destinations, Booking) with full content + style attribute schemas, all
 * rendered through the shared {@see Renderer}. Also boots the Elementor
 * integration when Elementor is active.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Blocks;

if ( ! defined( 'ABSPATH' ) ) exit;

class BlocksLoader {

	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );
		add_filter( 'block_categories_all', array( $this, 'block_category' ), 10, 1 );

		// Elementor integration (only meaningful when Elementor is present).
		new Elementor();
	}

	/**
	 * Style attributes shared by every block.
	 *
	 * @return array
	 */
	public static function style_attributes() {
		return array(
			'gap'        => array( 'type' => 'number' ),
			'cardRadius' => array( 'type' => 'number' ),
			'accent'     => array( 'type' => 'string', 'default' => '' ),
			'titleColor' => array( 'type' => 'string', 'default' => '' ),
			'textColor'  => array( 'type' => 'string', 'default' => '' ),
			'btnBg'      => array( 'type' => 'string', 'default' => '' ),
			'btnColor'   => array( 'type' => 'string', 'default' => '' ),
			'align'      => array( 'type' => 'string', 'default' => '' ),
		);
	}

	/**
	 * Register all Gutenberg blocks.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$grid_content = array(
			'count'   => array( 'type' => 'number', 'default' => 6 ),
			'columns' => array( 'type' => 'number', 'default' => 3 ),
			'layout'  => array( 'type' => 'string', 'default' => 'grid' ),
			'orderby' => array( 'type' => 'string', 'default' => 'date' ),
			'order'   => array( 'type' => 'string', 'default' => 'DESC' ),
		);

		register_block_type( 'wptm/trip-grid', array(
			'render_callback' => array( $this, 'render_trip_grid' ),
			'attributes'      => array_merge( $grid_content, array(
				'destination' => array( 'type' => 'string', 'default' => '' ),
				'activity'    => array( 'type' => 'string', 'default' => '' ),
			), self::style_attributes() ),
		) );

		register_block_type( 'wptm/hotel-grid', array(
			'render_callback' => array( $this, 'render_hotel_grid' ),
			'attributes'      => array_merge( $grid_content, array(
				'destination' => array( 'type' => 'string', 'default' => '' ),
			), self::style_attributes() ),
		) );

		register_block_type( 'wptm/search-form', array(
			'render_callback' => array( $this, 'render_search_form' ),
			'attributes'      => array_merge( array(
				'style' => array( 'type' => 'string', 'default' => 'horizontal' ),
			), self::style_attributes() ),
		) );

		register_block_type( 'wptm/booking-form', array(
			'render_callback' => array( $this, 'render_booking_form' ),
			'attributes'      => array_merge( array(
				'id' => array( 'type' => 'number', 'default' => 0 ),
			), self::style_attributes() ),
		) );

		register_block_type( 'wptm/destinations', array(
			'render_callback' => array( $this, 'render_destinations' ),
			'attributes'      => array_merge( array(
				'count' => array( 'type' => 'number', 'default' => 8 ),
			), self::style_attributes() ),
		) );
	}

	/**
	 * Add a dedicated block category so the blocks are easy to find.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public function block_category( $categories ) {
		foreach ( $categories as $cat ) {
			if ( isset( $cat['slug'] ) && 'wptm' === $cat['slug'] ) {
				return $categories;
			}
		}
		array_unshift( $categories, array(
			'slug'  => 'wptm',
			'title' => __( 'Byteflows Travel', 'byteflows-travel-hotel-booking' ),
			'icon'  => 'palmtree',
		) );
		return $categories;
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function editor_assets() {
		$path = WPTM_PLUGIN_DIR . 'assets/js/admin/blocks.js';
		$ver  = file_exists( $path ) ? filemtime( $path ) : WPTM_VERSION;

		wp_enqueue_script(
			'wptm-blocks',
			WPTM_PLUGIN_URL . 'assets/js/admin/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			$ver,
			true
		);

		// The front-end stylesheet powers the editor preview (ServerSideRender).
		$css = WPTM_PLUGIN_DIR . 'assets/css/public.css';
		wp_enqueue_style(
			'wptm-blocks-editor',
			WPTM_PLUGIN_URL . 'assets/css/public.css',
			array(),
			file_exists( $css ) ? filemtime( $css ) : WPTM_VERSION
		);
	}

	/* ─── Render callbacks → shared Renderer ─── */

	public function render_trip_grid( $atts ) { return Renderer::trips( $atts ); }
	public function render_hotel_grid( $atts ) { return Renderer::hotels( $atts ); }
	public function render_search_form( $atts ) { return Renderer::search( $atts ); }
	public function render_booking_form( $atts ) { return Renderer::booking( $atts ); }
	public function render_destinations( $atts ) { return Renderer::destinations( $atts ); }
}
