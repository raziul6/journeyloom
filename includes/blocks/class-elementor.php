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
		// The AI Style generator for the Elementor editor is provided by the Pro add-on.
	}

	/**
	 * Add the WPTM widget category.
	 *
	 * @param \Elementor\Elements_Manager $manager Categories manager.
	 */
	public function register_category( $manager ) {
		$manager->add_category( 'wptm', array(
			'title' => __( 'Byteflows Travel', 'byteflows-travel-hotel-booking' ),
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
