<?php
/**
 * Elementor integration.
 *
 * Registers a "JourneyLoom" widget category and the Trip Grid, Hotel Grid,
 * Search, Destinations and Booking widgets. The widget classes extend
 * \Elementor\Widget_Base, so they are declared in a separate file that is only
 * included once Elementor is loaded. Every widget renders through the shared
 * {@see Renderer}, so Elementor output matches the Gutenberg blocks/shortcodes.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Blocks;

if ( ! defined( 'ABSPATH' ) ) exit;

class Elementor {

	public function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'editor_assets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );
	}

	/**
	 * Whether the AI Style generator should load in the Elementor editor
	 * (Pro + AI enabled + an API key) — mirrors the Gutenberg gate.
	 *
	 * @return bool
	 */
	private function ai_enabled() {
		return function_exists( 'wptm_is_pro' ) && wptm_is_pro()
			&& (bool) get_option( 'wptm_enable_ai', false )
			&& ! empty( get_option( 'wptm_ai_api_key', '' ) );
	}

	/**
	 * Enqueue the AI Style generator script for the Elementor editor (Pro only).
	 */
	public function editor_assets() {
		if ( ! $this->ai_enabled() ) {
			return;
		}
		$path = WPTM_PLUGIN_DIR . 'assets/js/admin/elementor-ai.js';
		$ver  = file_exists( $path ) ? filemtime( $path ) : WPTM_VERSION;
		wp_enqueue_script( 'wptm-elementor-ai', WPTM_PLUGIN_URL . 'assets/js/admin/elementor-ai.js', array( 'jquery' ), $ver, true );
		wp_localize_script( 'wptm-elementor-ai', 'wptmElAI', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wptm_ai_nonce' ),
		) );
	}

	/**
	 * Enqueue the AI Style panel styles for the Elementor editor (Pro only).
	 */
	public function editor_styles() {
		if ( ! $this->ai_enabled() ) {
			return;
		}
		$path = WPTM_PLUGIN_DIR . 'assets/css/admin-elementor-ai.css';
		$ver  = file_exists( $path ) ? filemtime( $path ) : WPTM_VERSION;
		wp_enqueue_style( 'wptm-elementor-ai', WPTM_PLUGIN_URL . 'assets/css/admin-elementor-ai.css', array(), $ver );
	}

	/**
	 * Add the WPTM widget category.
	 *
	 * @param \Elementor\Elements_Manager $manager Categories manager.
	 */
	public function register_category( $manager ) {
		$manager->add_category( 'wptm', array(
			'title' => __( 'JourneyLoom', 'journeyloom' ),
			'icon'  => 'eicon-tour',
		) );
	}

	/**
	 * Register the widgets.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public function register_widgets( $manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once WPTM_PLUGIN_DIR . 'includes/blocks/elementor-widgets.php';

		$manager->register( new Widgets\Trip_Grid() );
		$manager->register( new Widgets\Hotel_Grid() );
		$manager->register( new Widgets\Search_Form() );
		$manager->register( new Widgets\Destinations() );
		$manager->register( new Widgets\Booking_Form() );
	}
}
