<?php
/**
 * Elementor widget definitions for JourneyLoom.
 *
 * Included only after Elementor is loaded (see class-elementor.php), so it is
 * safe to extend \Elementor\Widget_Base here. Every widget exposes a Content
 * tab and a Style tab and renders through the shared {@see \JourneyLoom\Blocks\Renderer}.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Blocks\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use JourneyLoom\Blocks\Renderer;

if ( ! defined( 'ABSPATH' ) ) exit;

// Every render() below only echoes markup produced by the shared Renderer, which
// escapes its own output (it renders the same trusted grid HTML as the plugin's
// shortcodes). The widget settings are Elementor-controlled, not raw request data.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

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
		$opts  = array( '' => __( 'All', 'journeyloom' ) );
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
	/**
	 * Whether the AI Style generator is available (Pro + AI configured).
	 *
	 * @return bool
	 */
	public static function ai_style_enabled() {
		return function_exists( 'wptm_is_pro' ) && wptm_is_pro()
			&& (bool) get_option( 'wptm_enable_ai', false )
			&& ! empty( get_option( 'wptm_ai_api_key', '' ) );
	}

	/**
	 * Markup for the in-panel AI Style generator (driven by elementor-ai.js).
	 *
	 * @return string
	 */
	protected function ai_style_markup() {
		ob_start();
		?>
		<div class="wptm-el-ai">
			<div class="wptm-el-ai__title">✨ <?php esc_html_e( 'AI Style', 'journeyloom' ); ?></div>
			<input type="text" class="wptm-el-ai__vibe" placeholder="<?php esc_attr_e( 'e.g. luxury beach, minimal, vibrant tropical', 'journeyloom' ); ?>">
			<button type="button" class="wptm-el-ai__gen"><?php esc_html_e( 'Generate styles', 'journeyloom' ); ?></button>
			<div class="wptm-el-ai__msg" style="display:none"></div>
			<div class="wptm-el-ai__presets"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	protected function add_style_section() {
		$this->start_controls_section( 'wptm_style', array(
			'label' => __( 'Style', 'journeyloom' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		// AI Style generator (Pro): fills the colour/radius/gap controls below.
		if ( self::ai_style_enabled() ) {
			$this->add_control( 'wptm_ai_style', array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => $this->ai_style_markup(),
				'content_classes' => 'wptm-el-ai-control',
			) );
		}

		$this->add_control( 'align', array(
			'label'   => __( 'Alignment', 'journeyloom' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => array(
				'left'   => array( 'title' => __( 'Left', 'journeyloom' ),   'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => __( 'Center', 'journeyloom' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => __( 'Right', 'journeyloom' ),  'icon' => 'eicon-text-align-right' ),
			),
		) );

		$this->add_control( 'gap', array(
			'label'      => __( 'Grid Gap', 'journeyloom' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
		) );

		$this->add_control( 'cardRadius', array(
			'label'      => __( 'Card Radius', 'journeyloom' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
		) );

		$this->add_control( 'accent', array(
			'label' => __( 'Accent / Price Color', 'journeyloom' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'titleColor', array(
			'label' => __( 'Title Color', 'journeyloom' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'textColor', array(
			'label' => __( 'Text Color', 'journeyloom' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'btnBg', array(
			'label' => __( 'Button Background', 'journeyloom' ),
			'type'  => Controls_Manager::COLOR,
		) );
		$this->add_control( 'btnColor', array(
			'label' => __( 'Button Text Color', 'journeyloom' ),
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
			'label'   => __( 'Number of items', 'journeyloom' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 6, 'min' => 1, 'max' => 48,
		) );
		$this->add_control( 'layout', array(
			'label'   => __( 'Layout', 'journeyloom' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => array( 'grid' => __( 'Grid', 'journeyloom' ), 'list' => __( 'List', 'journeyloom' ) ),
		) );
		$this->add_control( 'columns', array(
			'label'     => __( 'Columns', 'journeyloom' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '3',
			'options'   => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'condition' => array( 'layout' => 'grid' ),
		) );
		$this->add_control( 'orderby', array(
			'label'   => __( 'Order By', 'journeyloom' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'date',
			'options' => array(
				'date'       => __( 'Newest', 'journeyloom' ),
				'title'      => __( 'Title', 'journeyloom' ),
				'price'      => __( 'Price', 'journeyloom' ),
				'rand'       => __( 'Random', 'journeyloom' ),
				'menu_order' => __( 'Menu Order', 'journeyloom' ),
			),
		) );
		$this->add_control( 'order', array(
			'label'   => __( 'Order', 'journeyloom' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'DESC',
			'options' => array( 'DESC' => __( 'Descending', 'journeyloom' ), 'ASC' => __( 'Ascending', 'journeyloom' ) ),
		) );
	}
}

/* ───────────────────────── Trip Grid ───────────────────────── */
class Trip_Grid extends Base {
	public function get_name() { return 'wptm_trip_grid'; }
	public function get_title() { return __( 'Trip Grid', 'journeyloom' ); }
	public function get_icon() { return 'eicon-posts-grid'; }
	public function get_keywords() { return array( 'trip', 'travel', 'tour', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'journeyloom' ) ) );
		$this->add_grid_controls();
		$this->add_control( 'destination', array(
			'label'   => __( 'Destination', 'journeyloom' ),
			'type'    => Controls_Manager::SELECT2,
			'options' => $this->term_options( 'wptm_destination' ),
			'default' => '',
		) );
		$this->add_control( 'activity', array(
			'label'   => __( 'Activity', 'journeyloom' ),
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
				'layout'      => $s['layout'] ?? 'grid',
			'orderby'     => $s['orderby'] ?? 'date',
			'order'       => $s['order'] ?? 'DESC',
			'destination' => $s['destination'] ?? '',
			'activity'    => $s['activity'] ?? '',
		) ) );	}
}

/* ───────────────────────── Hotel Grid ───────────────────────── */
class Hotel_Grid extends Base {
	public function get_name() { return 'wptm_hotel_grid'; }
	public function get_title() { return __( 'Hotel Grid', 'journeyloom' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_keywords() { return array( 'hotel', 'room', 'stay', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'journeyloom' ) ) );
		$this->add_grid_controls();
		$this->add_control( 'destination', array(
			'label'   => __( 'Destination', 'journeyloom' ),
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
				'layout'      => $s['layout'] ?? 'grid',
			'orderby'     => $s['orderby'] ?? 'date',
			'order'       => $s['order'] ?? 'DESC',
			'destination' => $s['destination'] ?? '',
		) ) );	}
}

/* ───────────────────────── Search Form ───────────────────────── */
class Search_Form extends Base {
	public function get_name() { return 'wptm_search_form'; }
	public function get_title() { return __( 'Travel Search Form', 'journeyloom' ); }
	public function get_icon() { return 'eicon-search'; }
	public function get_keywords() { return array( 'search', 'filter', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'journeyloom' ) ) );
		$this->add_control( 'style', array(
			'label'   => __( 'Layout', 'journeyloom' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'horizontal',
			'options' => array(
				'horizontal' => __( 'Horizontal', 'journeyloom' ),
				'vertical'   => __( 'Vertical', 'journeyloom' ),
			),
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::search( array_merge( $this->style_atts( $s ), array(
			'style' => $s['style'] ?? 'horizontal',
		) ) );	}
}

/* ───────────────────────── Destinations ───────────────────────── */
class Destinations extends Base {
	public function get_name() { return 'wptm_destinations'; }
	public function get_title() { return __( 'Destinations Grid', 'journeyloom' ); }
	public function get_icon() { return 'eicon-map-pin'; }
	public function get_keywords() { return array( 'destination', 'location', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'journeyloom' ) ) );
		$this->add_control( 'count', array(
			'label'   => __( 'Number of destinations', 'journeyloom' ),
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
		) ) );	}
}

/* ───────────────────────── Booking Form ───────────────────────── */
class Booking_Form extends Base {
	public function get_name() { return 'wptm_booking_form'; }
	public function get_title() { return __( 'Booking Form', 'journeyloom' ); }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_keywords() { return array( 'booking', 'reserve', 'wptm' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'journeyloom' ) ) );
		$this->add_control( 'id', array(
			'label'       => __( 'Trip / Hotel ID', 'journeyloom' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'description' => __( '0 = use the current trip/hotel being viewed.', 'journeyloom' ),
		) );
		$this->end_controls_section();
		$this->add_style_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo Renderer::booking( array_merge( $this->style_atts( $s ), array(
			'id' => $s['id'] ?? 0,
		) ) );	}
}
