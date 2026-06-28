<?php
/**
 * Elementor widget definitions for WP Travel Machine.
 *
 * Included only after Elementor is loaded (see class-elementor.php), so it is
 * safe to extend \Elementor\Widget_Base here. Every widget exposes a Content
 * tab and a Style tab and renders through the shared {@see \WPTravelMachine\Blocks\Renderer}.
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Blocks\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WPTravelMachine\Blocks\Renderer;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shared base: WPTM category + Style tab + attribute mapping.
 */
abstract class Base extends Widget_Base {

	public function get_categories() {
		return array( 'wptm' );
	}

	/**
	 * Term slug => name options for a taxonomy select.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array
	 */
	protected function term_options( $taxonomy ) {
		$opts  = array( '' => __( 'All', 'wp-travel-machine' ) );
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$opts[ $t->slug ] = $t->name;
			}
		}
		return $opts;
	}

	/**
	 * Register the shared Style tab section.
	 */
	protected function add_style_section() {
		$this->start_controls_section( 'wptm_style', array(
			'label' => __( 'Style', 'wp-travel-machine' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'align', array(
			'label'   => __( 'Alignment', 'wp-travel-machine' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => array(
				'left'   => array( 'title' => __( 'Left', 'wp-travel-machine' ),   'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => __( 'Center', 'wp-travel-machine' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => __( 'Right', 'wp-travel-machine' ),  'icon' => 'eicon-text-align-right' ),
			),
		) );

		$this->add_control( 'gap', array(
			'label'      => __( 'Grid Gap', 'wp-travel-machine' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
		) );

		$this->add_control( 'cardRadius', array(
			'label'      => __( 'Card Radius', 'wp-travel-machine' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
		) );

		$this->add_control( 'accent', array(
			'label' => __( 'Accent / Price Color', 'wp-travel-machine' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'titleColor', array(
			'label' => __( 'Title Color', 'wp-travel-machine' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'textColor', array(
			'label' => __( 'Text Color', 'wp-travel-machine' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'btnBg', array(
			'label' => __( 'Button Background', 'wp-travel-machine' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'btnColor', array(
			'label' => __( 'Button Text Color', 'wp-travel-machine' ),
			'type'  => Controls_Manager::COLOR,
		) );

		$this->end_controls_section();
	}

	/**
	 * Map Elementor settings to the renderer's style attributes.
	 *
	 * @param array $s Settings.
	 * @return array
	 */
	protected function style_atts( $s ) {
		return array(
			'gap'        => isset( $s['gap']['size'] ) && '' !== $s['gap']['size'] ? $s['gap']['size'] : '',
			'cardRadius' => isset( $s['cardRadius']['size'] ) && '' !== $s['cardRadius']['size'] ? $s['cardRadius']['size'] : '',
			'accent'     => $s['accent'] ?? '',
			'titleColor' => $s['titleColor'] ?? '',
			'textColor'  => $s['textColor'] ?? '',
			'btnBg'      => $s['btnBg'] ?? '',
			'btnColor'   => $s['btnColor'] ?? '',
			'align'      => $s['align'] ?? '',
		);
	}

	/** Common count/columns/order controls for grid widgets. */
	protected function add_grid_controls() {
		$this->add_control( 'count', array(
			'label'   => __( 'Number of items', 'wp-travel-machine' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 6, 'min' => 1, 'max' => 48,
		) );
		$this->add_control( 'columns', array(
			'label'   => __( 'Columns', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '3',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
		) );
		$this->add_control( 'orderby', array(
			'label'   => __( 'Order By', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'date',
			'options' => array(
				'date'       => __( 'Newest', 'wp-travel-machine' ),
				'title'      => __( 'Title', 'wp-travel-machine' ),
				'price'      => __( 'Price', 'wp-travel-machine' ),
				'rand'       => __( 'Random', 'wp-travel-machine' ),
				'menu_order' => __( 'Menu Order', 'wp-travel-machine' ),
			),
		) );
		$this->add_control( 'order', array(
			'label'   => __( 'Order', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'DESC',
			'options' => array( 'DESC' => __( 'Descending', 'wp-travel-machine' ), 'ASC' => __( 'Ascending', 'wp-travel-machine' ) ),
		) );
	}
}

/* ───────────────────────── Trip Grid ───────────────────────── */
class Trip_Grid extends Base {
	public function get_name() { return 'wptm_trip_grid'; }
	public function get_title() { return __( 'Trip Grid', 'wp-travel-machine' ); }
	public function get_icon() { return 'eicon-posts-grid'; }
	public function get_keywords() { return array( 'trip', 'travel', 'tour', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'wp-travel-machine' ) ) );
		$this->add_grid_controls();
		$this->add_control( 'destination', array(
			'label'   => __( 'Destination', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT2,
			'options' => $this->term_options( 'wptm_destination' ),
			'default' => '',
		) );
		$this->add_control( 'activity', array(
			'label'   => __( 'Activity', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT2,
			'options' => $this->term_options( 'wptm_activity' ),
			'default' => '',
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::trips( array_merge( $this->style_atts( $s ), array(
			'count'       => $s['count'] ?? 6,
			'columns'     => $s['columns'] ?? 3,
			'orderby'     => $s['orderby'] ?? 'date',
			'order'       => $s['order'] ?? 'DESC',
			'destination' => $s['destination'] ?? '',
			'activity'    => $s['activity'] ?? '',
		) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ───────────────────────── Hotel Grid ───────────────────────── */
class Hotel_Grid extends Base {
	public function get_name() { return 'wptm_hotel_grid'; }
	public function get_title() { return __( 'Hotel Grid', 'wp-travel-machine' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_keywords() { return array( 'hotel', 'room', 'stay', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'wp-travel-machine' ) ) );
		$this->add_grid_controls();
		$this->add_control( 'destination', array(
			'label'   => __( 'Destination', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT2,
			'options' => $this->term_options( 'wptm_destination' ),
			'default' => '',
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::hotels( array_merge( $this->style_atts( $s ), array(
			'count'       => $s['count'] ?? 6,
			'columns'     => $s['columns'] ?? 3,
			'orderby'     => $s['orderby'] ?? 'date',
			'order'       => $s['order'] ?? 'DESC',
			'destination' => $s['destination'] ?? '',
		) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ───────────────────────── Search Form ───────────────────────── */
class Search_Form extends Base {
	public function get_name() { return 'wptm_search_form'; }
	public function get_title() { return __( 'Travel Search Form', 'wp-travel-machine' ); }
	public function get_icon() { return 'eicon-search'; }
	public function get_keywords() { return array( 'search', 'filter', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'wp-travel-machine' ) ) );
		$this->add_control( 'style', array(
			'label'   => __( 'Layout', 'wp-travel-machine' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'horizontal',
			'options' => array(
				'horizontal' => __( 'Horizontal', 'wp-travel-machine' ),
				'vertical'   => __( 'Vertical', 'wp-travel-machine' ),
			),
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::search( array_merge( $this->style_atts( $s ), array(
			'style' => $s['style'] ?? 'horizontal',
		) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ───────────────────────── Destinations ───────────────────────── */
class Destinations extends Base {
	public function get_name() { return 'wptm_destinations'; }
	public function get_title() { return __( 'Destinations Grid', 'wp-travel-machine' ); }
	public function get_icon() { return 'eicon-map-pin'; }
	public function get_keywords() { return array( 'destination', 'location', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'wp-travel-machine' ) ) );
		$this->add_control( 'count', array(
			'label'   => __( 'Number of destinations', 'wp-travel-machine' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 8, 'min' => 1, 'max' => 24,
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::destinations( array_merge( $this->style_atts( $s ), array(
			'count' => $s['count'] ?? 8,
		) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ───────────────────────── Booking Form ───────────────────────── */
class Booking_Form extends Base {
	public function get_name() { return 'wptm_booking_form'; }
	public function get_title() { return __( 'Booking Form', 'wp-travel-machine' ); }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_keywords() { return array( 'booking', 'reserve', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'wp-travel-machine' ) ) );
		$this->add_control( 'id', array(
			'label'       => __( 'Trip / Hotel ID', 'wp-travel-machine' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'description' => __( '0 = use the current trip/hotel being viewed.', 'wp-travel-machine' ),
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::booking( array_merge( $this->style_atts( $s ), array(
			'id' => $s['id'] ?? 0,
		) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
