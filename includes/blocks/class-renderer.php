<?php
/**
 * Shared, style-aware renderer for WPTM blocks & Elementor widgets.
 *
 * One source of truth for the Trip Grid, Hotel Grid, Search, Destinations and
 * Booking outputs. Each method wraps the content in a `.wptm-blk` container that
 * carries inline CSS custom properties driven by the style controls, so the
 * Gutenberg block, the Elementor widget and the shortcode all render identically.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Blocks;

if ( ! defined( 'ABSPATH' ) ) exit;

class Renderer {

	/**
	 * Default attributes shared by the grid widgets/blocks.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Content.
			'count'       => 6,
			'columns'     => 3,
			'layout'      => 'grid',       // grid | list.
			'destination' => '',
			'activity'    => '',
			'orderby'     => 'date',
			'order'       => 'DESC',
			'style'       => 'horizontal', // search layout.
			'id'          => 0,            // booking item id.
			'paginate'    => 'no',         // paginated listing (with per-page) vs fixed grid.
			'filters'     => 'no',         // show the filter bar above the grid.
			// Style.
			'gap'         => '',
			'cardRadius'  => '',
			'accent'      => '',
			'titleColor'  => '',
			'textColor'   => '',
			'btnBg'       => '',
			'btnColor'    => '',
			'align'       => '',
		);
	}

	/**
	 * Coerce a raw attribute array against the defaults.
	 *
	 * @param array $a Raw attributes.
	 * @return array
	 */
	public static function normalize( $a ) {
		$a = wp_parse_args( is_array( $a ) ? $a : array(), self::defaults() );

		$a['count']       = max( 1, min( 48, (int) $a['count'] ) );
		$a['columns']     = max( 1, min( 4, (int) $a['columns'] ) );
		$a['layout']      = in_array( $a['layout'], array( 'grid', 'list' ), true ) ? $a['layout'] : 'grid';
		$a['destination'] = sanitize_title( $a['destination'] );
		$a['activity']    = sanitize_title( $a['activity'] );
		$a['orderby']     = in_array( $a['orderby'], array( 'date', 'title', 'price', 'rand', 'menu_order' ), true ) ? $a['orderby'] : 'date';
		$a['order']       = strtoupper( $a['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$a['style']       = in_array( $a['style'], array( 'horizontal', 'vertical' ), true ) ? $a['style'] : 'horizontal';
		$a['id']          = (int) $a['id'];
		$a['paginate']    = self::truthy( $a['paginate'] );
		$a['filters']     = self::truthy( $a['filters'] );
		$a['align']       = in_array( $a['align'], array( 'left', 'center', 'right' ), true ) ? $a['align'] : '';

		foreach ( array( 'accent', 'titleColor', 'textColor', 'btnBg', 'btnColor' ) as $c ) {
			$a[ $c ] = self::safe_color( $a[ $c ] );
		}
		return $a;
	}

	/**
	 * Allow only hex / rgb(a) color strings.
	 *
	 * @param string $c Color.
	 * @return string Safe color or empty.
	 */
	/**
	 * Loose boolean coercion for shortcode/block attributes.
	 *
	 * @param mixed $v Value.
	 * @return bool
	 */
	private static function truthy( $v ) {
		return in_array( strtolower( (string) $v ), array( '1', 'yes', 'true', 'on' ), true ) || true === $v;
	}

	private static function safe_color( $c ) {
		$c = trim( (string) $c );
		if ( '' === $c ) {
			return '';
		}
		return preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $c ) || preg_match( '/^rgba?\(\s*[\d.,%\s]+\)$/i', $c )
			? $c
			: '';
	}

	/**
	 * Build the opening wrapper with style custom properties.
	 *
	 * @param string $type Block type slug (trips|hotels|search|destinations|booking).
	 * @param array  $a    Normalized attributes.
	 * @return string
	 */
	public static function wrapper_open( $type, $a ) {
		$vars = array();
		if ( '' !== $a['gap'] && null !== $a['gap'] ) {
			$vars[] = '--wptm-blk-gap:' . (int) $a['gap'] . 'px';
		}
		if ( '' !== $a['cardRadius'] && null !== $a['cardRadius'] ) {
			$vars[] = '--wptm-blk-radius:' . (int) $a['cardRadius'] . 'px';
		}
		$map = array(
			'accent'     => '--wptm-blk-accent',
			'titleColor' => '--wptm-blk-title',
			'textColor'  => '--wptm-blk-text',
			'btnBg'      => '--wptm-blk-btn-bg',
			'btnColor'   => '--wptm-blk-btn-color',
		);
		foreach ( $map as $key => $var ) {
			if ( '' !== $a[ $key ] ) {
				$vars[] = $var . ':' . $a[ $key ];
			}
		}

		$class = 'wptm-blk wptm-blk--' . sanitize_html_class( $type );
		if ( $a['align'] ) {
			$class .= ' wptm-blk--align-' . $a['align'];
		}
		$style = $vars ? ' style="' . esc_attr( implode( ';', $vars ) ) . '"' : '';

		return '<div class="' . esc_attr( $class ) . '"' . $style . '>';
	}

	public static function wrapper_close() {
		return '</div>';
	}

	/* ─────────────────────────────────────────────
	 * Renderers
	 * ───────────────────────────────────────────── */

	/**
	 * Trip grid.
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	public static function trips( $a ) {
		$a    = self::normalize( $a );
		$args = array(
			'post_type'      => 'wptm_trip',
			'posts_per_page' => $a['paginate'] ? (int) get_option( 'wptm_items_per_page', 12 ) : $a['count'],
			'paged'          => $a['paginate'] ? self::current_page() : 1,
			'post_status'    => 'publish',
			'orderby'        => 'price' === $a['orderby'] ? 'meta_value_num' : $a['orderby'],
			'order'          => $a['order'],
		);
		if ( 'price' === $a['orderby'] ) {
			$args['meta_key'] = '_wptm_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- querying the plugin's own indexed meta; low-frequency query.
		}
		if ( $a['destination'] ) {
			$args['tax_query'][] = array( 'taxonomy' => 'wptm_destination', 'field' => 'slug', 'terms' => $a['destination'] );
		}
		if ( $a['activity'] ) {
			$args['tax_query'][] = array( 'taxonomy' => 'wptm_activity', 'field' => 'slug', 'terms' => $a['activity'] );
		}

		/** Filter the trip grid query args. */
		$args = apply_filters( 'wptm_trips_query_args', $args, $a );

		return self::grid( 'trips', $a, $args, 'partials/trip-card.php', __( 'No trips found.', 'byteflows-travel-hotel-booking' ) );
	}

	/**
	 * Hotel grid.
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	public static function hotels( $a ) {
		$a    = self::normalize( $a );
		$args = array(
			'post_type'      => 'wptm_hotel',
			'posts_per_page' => $a['paginate'] ? (int) get_option( 'wptm_items_per_page', 12 ) : $a['count'],
			'paged'          => $a['paginate'] ? self::current_page() : 1,
			'post_status'    => 'publish',
			'orderby'        => 'price' === $a['orderby'] ? 'date' : $a['orderby'],
			'order'          => $a['order'],
		);
		if ( $a['destination'] ) {
			$args['tax_query'][] = array( 'taxonomy' => 'wptm_destination', 'field' => 'slug', 'terms' => $a['destination'] );
		}

		/** Filter the hotel grid query args. */
		$args = apply_filters( 'wptm_hotels_query_args', $args, $a );

		return self::grid( 'hotels', $a, $args, 'partials/hotel-card.php', __( 'No hotels found.', 'byteflows-travel-hotel-booking' ) );
	}

	/**
	 * Shared grid runner.
	 *
	 * @param string $type    Block type.
	 * @param array  $a       Normalized attributes.
	 * @param array  $args    WP_Query args.
	 * @param string $partial Card partial path.
	 * @param string $empty   Empty-state message.
	 * @return string
	 */
	private static function grid( $type, $a, $args, $partial, $empty ) {
		$q          = new \WP_Query( $args );
		$grid_class = 'list' === $a['layout']
			? 'wptm-grid wptm-grid--list'
			: 'wptm-grid wptm-grid-' . (int) $a['columns'];
		if ( $a['paginate'] ) {
			$grid_class .= ' wptm-search-results';
		}

		ob_start();
		echo self::wrapper_open( $type, $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- plugin-built <div>; class + inline style are esc_attr() escaped in wrapper_open().

		// Optional filter bar (same UI as the archives; powered by filter-bar.js).
		if ( $a['paginate'] && $a['filters'] ) {
			$ptype = 'hotels' === $type ? 'hotel' : 'trip';
			wptm_get_template( 'partials/filter-bar.php', array( 'ptype' => $ptype ) );
		}

		if ( $q->have_posts() ) {
			echo '<div class="' . esc_attr( $grid_class ) . '">';
			while ( $q->have_posts() ) {
				$q->the_post();
				wptm_get_template( $partial );
			}
			echo '</div>';
			wp_reset_postdata();

			// Pagination wrapper — filter-bar.js turns this into numbered AJAX
			// pagination or a "Load More" button per the admin setting.
			if ( $a['paginate'] ) {
				printf(
					'<div class="wptm-pagination-wrap" data-type="%s" data-page="1" data-max="%d" data-total="%d"></div>',
					'hotels' === $type ? 'hotel' : 'trip',
					(int) $q->max_num_pages,
					(int) $q->found_posts
				);
			}
		} else {
			if ( $a['paginate'] ) {
				echo '<div class="' . esc_attr( $grid_class ) . '"><p class="wptm-no-results">' . esc_html( $empty ) . '</p></div>';
			} else {
				echo '<p class="wptm-blk-empty">' . esc_html( $empty ) . '</p>';
			}
		}

		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Current page number for a paginated listing on a normal page/post.
	 *
	 * @return int
	 */
	private static function current_page() {
		$paged = max(
			(int) get_query_var( 'paged' ),
			(int) get_query_var( 'page' )
		);
		return $paged > 0 ? $paged : 1;
	}

	/**
	 * Search form.
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	public static function search( $a ) {
		$a = self::normalize( $a );
		ob_start();
		echo self::wrapper_open( 'search', $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- plugin-built <div>; class + inline style are esc_attr() escaped in wrapper_open().
		wptm_get_template( 'partials/search-form.php', array( 'style' => $a['style'] ) );
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Booking form.
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	public static function booking( $a ) {
		$a       = self::normalize( $a );
		$item_id = $a['id'] ?: get_the_ID();
		ob_start();
		echo self::wrapper_open( 'booking', $a ); // phpcs:ignore WordPress.Security.EscapeOutput -- plugin-built <div>; class + inline style are esc_attr() escaped in wrapper_open().
		wptm_get_template( 'partials/booking-form.php', array( 'item_id' => $item_id ) );
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Destinations grid (wraps the shortcode's terms grid).
	 *
	 * @param array $a Attributes.
	 * @return string
	 */
	public static function destinations( $a ) {
		$a  = self::normalize( $a );
		$sc = \JourneyLoom\Plugin::get_instance()->get_module( 'shortcodes' );
		$inner = $sc ? $sc->destinations_grid( array( 'count' => $a['count'] ) ) : '';
		return self::wrapper_open( 'destinations', $a ) . $inner . self::wrapper_close();
	}
}
