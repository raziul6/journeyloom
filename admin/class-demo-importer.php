<?php
/**
 * One-click demo content importer for Trips and Hotels.
 *
 * Unlike WooCommerce's manual CSV importer, this ships a curated set of demo
 * trips and hotels (with meta, taxonomy terms, pricing, itineraries and hotel
 * rooms) that an admin can install with a single click. The admin chooses
 * whether to import Trips, Hotels or both. Every created item is tagged with
 * the {@see self::DEMO_META} meta so the whole set can be removed again later.
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Admin;

use WPTravelMachine\PostTypes\Trip;

if ( ! defined( 'ABSPATH' ) ) exit;

class DemoImporter {

	/** Meta flag stamped on every imported post so demo content can be tracked/removed. */
	const DEMO_META = '_wptm_demo';

	/** Meta holding the image search query used to fetch a featured image. */
	const QUERY_META = '_wptm_demo_img_query';

	public function __construct() {
		add_action( 'wp_ajax_wptm_import_demo', array( $this, 'ajax_import' ) );
		add_action( 'wp_ajax_wptm_import_demo_image', array( $this, 'ajax_import_image' ) );
		add_action( 'wp_ajax_wptm_remove_demo', array( $this, 'ajax_remove' ) );
	}

	/**
	 * Count of currently-installed demo items, keyed by post type.
	 *
	 * @return array{trip:int,hotel:int}
	 */
	public static function demo_counts() {
		$counts = array( 'trip' => 0, 'hotel' => 0 );
		foreach ( array( 'trip' => 'wptm_trip', 'hotel' => 'wptm_hotel' ) as $key => $pt ) {
			$ids = get_posts( array(
				'post_type'      => $pt,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => self::DEMO_META,
				'meta_value'     => '1',
			) );
			$counts[ $key ] = count( $ids );
		}
		return $counts;
	}

	/* ---------------------------------------------------------------------
	 * AJAX: import
	 * ------------------------------------------------------------------- */

	public function ajax_import() {
		check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-travel-machine' ) ) );
		}

		$types = isset( $_POST['types'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['types'] ) ) : array();
		$types = array_intersect( $types, array( 'trip', 'hotel' ) );
		if ( empty( $types ) ) {
			wp_send_json_error( array( 'message' => __( 'Choose at least one content type to import.', 'wp-travel-machine' ) ) );
		}

		$with_images = ! empty( $_POST['images'] );

		// Persist the Unsplash Access Key if one was supplied (blank clears it).
		if ( isset( $_POST['unsplash_key'] ) ) {
			$key = sanitize_text_field( wp_unslash( $_POST['unsplash_key'] ) );
			update_option( 'wptm_unsplash_key', $key );
		}

		$created = array( 'trip' => 0, 'hotel' => 0 );
		$queue   = array();

		if ( in_array( 'trip', $types, true ) ) {
			foreach ( $this->trips_data() as $trip ) {
				$id = $this->create_trip( $trip );
				if ( $id ) {
					$created['trip']++;
					$queue[] = $id;
				}
			}
		}

		if ( in_array( 'hotel', $types, true ) ) {
			foreach ( $this->hotels_data() as $hotel ) {
				$id = $this->create_hotel( $hotel );
				if ( $id ) {
					$created['hotel']++;
					$queue[] = $id;
				}
			}
		}

		$parts = array();
		if ( $created['trip'] ) {
			/* translators: %d: number of trips. */
			$parts[] = sprintf( _n( '%d trip', '%d trips', $created['trip'], 'wp-travel-machine' ), $created['trip'] );
		}
		if ( $created['hotel'] ) {
			/* translators: %d: number of hotels. */
			$parts[] = sprintf( _n( '%d hotel', '%d hotels', $created['hotel'], 'wp-travel-machine' ), $created['hotel'] );
		}

		wp_send_json_success( array(
			'created'     => $created,
			'counts'      => self::demo_counts(),
			'image_queue' => $with_images ? $queue : array(),
			'message'     => $parts
				? sprintf( __( 'Imported %s.', 'wp-travel-machine' ), implode( ' ' . __( 'and', 'wp-travel-machine' ) . ' ', $parts ) )
				: __( 'Nothing was imported.', 'wp-travel-machine' ),
		) );
	}

	/* ---------------------------------------------------------------------
	 * AJAX: import a single featured image (one at a time, JS-driven)
	 * ------------------------------------------------------------------- */

	public function ajax_import_image() {
		check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-travel-machine' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || ! in_array( $post->post_type, array( 'wptm_trip', 'wptm_hotel' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item.', 'wp-travel-machine' ) ) );
		}
		// Only touch demo content; never overwrite a real post or an existing image.
		if ( '1' !== get_post_meta( $post_id, self::DEMO_META, true ) || has_post_thumbnail( $post_id ) ) {
			wp_send_json_success( array( 'skipped' => true ) );
		}

		// Older demo posts (imported before image support) have no query meta —
		// fall back to the post title so the Unsplash query is still relevant.
		$query = get_post_meta( $post_id, self::QUERY_META, true );
		if ( '' === trim( (string) $query ) ) {
			$query = $post->post_title;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Featured image.
		$featured_id = $this->sideload_image( $this->image_source_url( $query, $post_id, 0 ), $post_id, $post->post_title );
		if ( is_wp_error( $featured_id ) ) {
			wp_send_json_error( array( 'message' => $featured_id->get_error_message() ) );
		}
		set_post_thumbnail( $post_id, $featured_id );

		// Gallery images → comma-separated list of attachment IDs in the gallery
		// meta the metabox/front end already read from.
		$gallery_key = ( 'wptm_hotel' === $post->post_type ) ? '_wptm_hotel_gallery' : '_wptm_gallery';
		$gallery_ids = array();
		if ( '' === trim( (string) get_post_meta( $post_id, $gallery_key, true ) ) ) {
			for ( $i = 1; $i <= 3; $i++ ) {
				$gid = $this->sideload_image( $this->image_source_url( $query, $post_id, $i ), $post_id, $post->post_title );
				if ( ! is_wp_error( $gid ) ) {
					$gallery_ids[] = $gid;
				}
			}
			if ( $gallery_ids ) {
				update_post_meta( $post_id, $gallery_key, implode( ',', $gallery_ids ) );
			}
		}

		wp_send_json_success( array(
			'post_id'   => $post_id,
			'thumb_url' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
			'gallery'   => count( $gallery_ids ),
		) );
	}

	/**
	 * Download a remote image and attach it to a post.
	 *
	 * Unsplash/Picsum URLs have no file extension (which media_sideload_image()
	 * rejects), so we download the file ourselves and force a .jpg filename.
	 *
	 * @param string $url     Remote image URL.
	 * @param int    $post_id Parent post ID.
	 * @param string $desc    Attachment description/title.
	 * @return int|\WP_Error Attachment ID or error.
	 */
	private function sideload_image( $url, $post_id, $desc ) {
		if ( ! $url ) {
			return new \WP_Error( 'wptm_no_url', __( 'No image source available.', 'wp-travel-machine' ) );
		}

		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = array(
			'name'     => 'wptm-demo-' . $post_id . '-' . wp_generate_password( 6, false ) . '.jpg',
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id, $desc );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return $attachment_id;
		}

		update_post_meta( $attachment_id, self::DEMO_META, '1' );

		return $attachment_id;
	}

	/**
	 * Resolve a featured-image URL for a query.
	 *
	 * Uses the official Unsplash API when a free Access Key is configured (real,
	 * topic-relevant photos); otherwise falls back to keyless Lorem Picsum so the
	 * importer still works out of the box.
	 *
	 * @param string $query   Search query.
	 * @param int    $post_id Post ID (used for a stable fallback seed).
	 * @param int    $variant Distinguishes multiple images for the same post (gallery).
	 * @return string Image URL, or empty string on failure.
	 */
	private function image_source_url( $query, $post_id, $variant = 0 ) {
		$query = trim( (string) $query );
		$key   = trim( (string) get_option( 'wptm_unsplash_key', '' ) );

		if ( $key && $query ) {
			$endpoint = add_query_arg( array(
				'query'          => rawurlencode( $query ),
				'orientation'    => 'landscape',
				'content_filter' => 'high',
				'client_id'      => $key,
			), 'https://api.unsplash.com/photos/random' );

			$response = wp_remote_get( $endpoint, array( 'timeout' => 20 ) );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! empty( $body['urls']['regular'] ) ) {
					return $body['urls']['regular'];
				}
			}
			// Fall through to the keyless source on any API error/limit.
		}

		// Keyless fallback — deterministic seed keeps each item's image stable
		// while the variant gives distinct gallery images.
		return 'https://picsum.photos/seed/wptm' . absint( $post_id ) . 'v' . absint( $variant ) . '/1200/800';
	}

	/* ---------------------------------------------------------------------
	 * AJAX: remove
	 * ------------------------------------------------------------------- */

	public function ajax_remove() {
		check_ajax_referer( 'wptm_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-travel-machine' ) ) );
		}

		global $wpdb;
		$removed = 0;

		// Imported featured images (tagged with the demo meta).
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_key'       => self::DEMO_META,
			'meta_value'     => '1',
		) );
		foreach ( $attachments as $att_id ) {
			wp_delete_attachment( $att_id, true );
		}

		foreach ( array( 'wptm_trip', 'wptm_hotel' ) as $pt ) {
			$ids = get_posts( array(
				'post_type'      => $pt,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => self::DEMO_META,
				'meta_value'     => '1',
			) );
			foreach ( $ids as $id ) {
				if ( 'wptm_hotel' === $pt ) {
					$wpdb->delete( $wpdb->prefix . 'wptm_rooms', array( 'hotel_id' => $id ), array( '%d' ) );
				}
				if ( wp_delete_post( $id, true ) ) {
					$removed++;
				}
			}
		}

		wp_send_json_success( array(
			'removed' => $removed,
			'counts'  => self::demo_counts(),
			/* translators: %d: number of removed demo items. */
			'message' => sprintf( _n( 'Removed %d demo item.', 'Removed %d demo items.', $removed, 'wp-travel-machine' ), $removed ),
		) );
	}

	/* ---------------------------------------------------------------------
	 * Creators
	 * ------------------------------------------------------------------- */

	/**
	 * Create a single demo trip with meta and taxonomy terms.
	 *
	 * @param array $t Trip definition.
	 * @return int|false New post ID or false on failure.
	 */
	private function create_trip( $t ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'wptm_trip',
			'post_title'   => $t['title'],
			'post_content' => $t['content'],
			'post_excerpt' => $t['excerpt'],
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		), true );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		update_post_meta( $post_id, self::DEMO_META, '1' );

		$meta = array(
			'_wptm_duration'      => $t['duration'],
			'_wptm_duration_unit' => 'days',
			'_wptm_group_min'     => $t['group_min'],
			'_wptm_group_max'     => $t['group_max'],
			'_wptm_difficulty'    => $t['difficulty'],
			'_wptm_min_age'       => $t['min_age'],
			'_wptm_highlights'    => $t['highlights'],
			'_wptm_includes'      => $t['includes'],
			'_wptm_excludes'      => $t['excludes'],
			'_wptm_itinerary'     => $t['itinerary'],
			'_wptm_faq'           => $t['faq'],
			'_wptm_pricing'       => $t['pricing'],
			'_wptm_price'         => Trip::lowest_price( $t['pricing'] ),
			'_wptm_address'       => $t['address'],
			'_wptm_latitude'      => $t['lat'],
			'_wptm_longitude'     => $t['lng'],
			self::QUERY_META      => $t['img_query'],
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		$this->set_terms( $post_id, 'wptm_destination', $t['destination'] );
		$this->set_terms( $post_id, 'wptm_activity', $t['activity'] );
		$this->set_terms( $post_id, 'wptm_trip_type', $t['trip_type'] );
		$this->set_terms( $post_id, 'wptm_difficulty', $t['difficulty_label'] );

		return $post_id;
	}

	/**
	 * Create a single demo hotel with meta, taxonomy terms and rooms.
	 *
	 * @param array $h Hotel definition.
	 * @return int|false New post ID or false on failure.
	 */
	private function create_hotel( $h ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'wptm_hotel',
			'post_title'   => $h['title'],
			'post_content' => $h['content'],
			'post_excerpt' => $h['excerpt'],
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		), true );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		update_post_meta( $post_id, self::DEMO_META, '1' );

		$meta = array(
			'_wptm_star_rating'    => $h['star'],
			'_wptm_hotel_address'  => $h['address'],
			'_wptm_hotel_city'     => $h['city'],
			'_wptm_hotel_country'  => $h['country'],
			'_wptm_hotel_lat'      => $h['lat'],
			'_wptm_hotel_lng'      => $h['lng'],
			'_wptm_hotel_amenities'=> $h['amenities'],
			'_wptm_check_in_time'  => '14:00',
			'_wptm_check_out_time' => '11:00',
			'_wptm_hotel_email'    => $h['email'],
			'_wptm_hotel_phone'    => $h['phone'],
			self::QUERY_META       => $h['img_query'],
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		$this->set_terms( $post_id, 'wptm_destination', $h['city'] );
		$this->set_terms( $post_id, 'wptm_hotel_type', $h['hotel_type'] );
		$this->set_terms( $post_id, 'wptm_hotel_facility', $h['facilities'] );

		// Rooms → custom table.
		global $wpdb;
		$table = $wpdb->prefix . 'wptm_rooms';
		foreach ( $h['rooms'] as $i => $room ) {
			$wpdb->insert( $table, array(
				'hotel_id'        => $post_id,
				'room_type'       => $room['type'],
				'room_name'       => $room['name'],
				'description'     => $room['description'],
				'max_guests'      => $room['max_guests'],
				'price_per_night' => $room['price'],
				'sale_price'      => $room['sale'] ?: null,
				'amenities'       => $room['amenities'],
				'bed_type'        => $room['bed_type'],
				'room_size'       => $room['room_size'],
				'sort_order'      => $i,
				'status'          => 'available',
			) );
		}

		return $post_id;
	}

	/**
	 * Resolve a list of term names to IDs (creating any that are missing) and
	 * assign them to a post.
	 *
	 * @param int          $post_id  Post ID.
	 * @param string       $taxonomy Taxonomy slug.
	 * @param string|array $names    Term name(s).
	 */
	private function set_terms( $post_id, $taxonomy, $names ) {
		$ids = array();
		foreach ( (array) $names as $name ) {
			$name = trim( (string) $name );
			if ( '' === $name ) {
				continue;
			}
			$existing = term_exists( $name, $taxonomy );
			if ( ! $existing ) {
				$existing = wp_insert_term( $name, $taxonomy );
			}
			if ( ! is_wp_error( $existing ) ) {
				$ids[] = (int) $existing['term_id'];
			}
		}
		if ( $ids ) {
			wp_set_object_terms( $post_id, $ids, $taxonomy );
		}
	}

	/* ---------------------------------------------------------------------
	 * Demo data
	 * ------------------------------------------------------------------- */

	/**
	 * Curated demo trips.
	 *
	 * @return array
	 */
	private function trips_data() {
		// title, destination, activity, trip_type, difficulty slug + label,
		// duration (days), base price, sale price, group min/max, min_age,
		// lat, lng, address.
		$pool = array(
			array( 'Everest Base Camp Trek', 'Nepal', 'Trekking', 'Adventure', 'challenging', 'Challenging', 14, 1499, 1299, 2, 14, 16, 27.9881, 86.9250, 'Khumbu, Solukhumbu District, Nepal' ),
			array( 'Annapurna Circuit Adventure', 'Nepal', 'Trekking', 'Adventure', 'challenging', 'Challenging', 12, 1199, 0, 2, 14, 16, 28.5970, 83.8203, 'Annapurna Conservation Area, Nepal' ),
			array( 'Bali Beaches & Temples', 'Indonesia', 'Sightseeing', 'Leisure', 'easy', 'Easy', 7, 899, 749, 1, 20, 8, -8.4095, 115.1889, 'Bali, Indonesia' ),
			array( 'Safari in the Serengeti', 'Tanzania', 'Wildlife Safari', 'Wildlife', 'moderate', 'Moderate', 8, 2499, 2199, 2, 12, 10, -2.3333, 34.8333, 'Serengeti National Park, Tanzania' ),
			array( 'Sahara Desert Expedition', 'Morocco', 'Desert Tour', 'Adventure', 'moderate', 'Moderate', 6, 1099, 0, 2, 16, 12, 31.0801, -4.0133, 'Merzouga, Sahara Desert, Morocco' ),
			array( 'Iceland Ring Road Explorer', 'Iceland', 'Road Trip', 'Adventure', 'moderate', 'Moderate', 9, 1799, 1599, 1, 18, 6, 64.9631, -19.0208, 'Ring Road, Iceland' ),
			array( 'Machu Picchu & Inca Trail', 'Peru', 'Trekking', 'Adventure', 'challenging', 'Challenging', 7, 1399, 1199, 2, 16, 14, -13.1631, -72.5450, 'Cusco Region, Peru' ),
			array( 'Greek Islands Hopping', 'Greece', 'Cruise', 'Leisure', 'easy', 'Easy', 10, 1599, 0, 1, 24, 8, 37.4467, 25.3289, 'Cyclades, Greece' ),
			array( 'Patagonia Wilderness Hike', 'Chile', 'Trekking', 'Adventure', 'challenging', 'Challenging', 11, 2199, 1999, 2, 12, 16, -50.9423, -73.4068, 'Torres del Paine, Patagonia, Chile' ),
			array( 'Kyoto Cultural Journey', 'Japan', 'Cultural', 'Cultural', 'easy', 'Easy', 6, 1299, 0, 1, 16, 8, 35.0116, 135.7681, 'Kyoto, Japan' ),
			array( 'Swiss Alps Panorama Tour', 'Switzerland', 'Sightseeing', 'Leisure', 'moderate', 'Moderate', 8, 1899, 1699, 1, 20, 6, 46.5197, 7.6066, 'Interlaken, Switzerland' ),
			array( 'Vietnam North to South', 'Vietnam', 'Cultural', 'Cultural', 'easy', 'Easy', 12, 1099, 949, 1, 18, 7, 16.0471, 108.2068, 'Da Nang, Vietnam' ),
		);

		$trips = array();
		foreach ( $pool as $p ) {
			list( $title, $dest, $activity, $type, $diff, $diffLabel, $days, $price, $sale, $gmin, $gmax, $min_age, $lat, $lng, $address ) = $p;

			$pricing = array(
				array( 'label' => 'Adult', 'price' => (float) $price, 'sale_price' => (float) $sale ),
				array( 'label' => 'Child', 'price' => round( $price * 0.7, 2 ), 'sale_price' => $sale ? round( $sale * 0.7, 2 ) : 0.0 ),
			);

			$trips[] = array(
				'title'            => $title,
				'destination'      => $dest,
				'activity'         => $activity,
				'trip_type'        => $type,
				'difficulty'       => $diff,
				'difficulty_label' => $diffLabel,
				'duration'         => $days,
				'group_min'        => $gmin,
				'group_max'        => $gmax,
				'min_age'          => $min_age,
				'lat'              => $lat,
				'lng'              => $lng,
				'address'          => $address,
				'img_query'        => sprintf( '%s %s landscape', $dest, $activity ),
				'pricing'          => $pricing,
				'excerpt'          => sprintf( '%d-day %s experience in %s — an unforgettable %s adventure.', $days, strtolower( $activity ), $dest, strtolower( $type ) ),
				'content'          => $this->trip_content( $title, $dest, $days, $activity ),
				'highlights'       => $this->trip_highlights( $dest, $activity ),
				'includes'         => array( 'Accommodation throughout the trip', 'Experienced local guide', 'All ground transportation', 'Daily breakfast', 'Permits and entry fees' ),
				'excludes'         => array( 'International flights', 'Travel insurance', 'Personal expenses', 'Tips and gratuities', 'Visa fees' ),
				'itinerary'        => $this->trip_itinerary( $days, $dest ),
				'faq'              => $this->trip_faq( $dest ),
			);
		}

		return $trips;
	}

	private function trip_content( $title, $dest, $days, $activity ) {
		return sprintf(
			"<p>Embark on the %s, a carefully crafted %d-day journey through %s. This trip blends %s with comfortable accommodation, expert local guides and plenty of free time to soak in the surroundings.</p>\n<p>Whether you are a seasoned traveller or setting out on your first big adventure, our small-group format ensures a personal experience and a smooth, well-organised itinerary from start to finish.</p>",
			$title,
			$days,
			$dest,
			strtolower( $activity )
		);
	}

	private function trip_highlights( $dest, $activity ) {
		return array(
			sprintf( 'Discover the highlights of %s', $dest ),
			sprintf( 'Guided %s with local experts', strtolower( $activity ) ),
			'Authentic local cuisine and culture',
			'Small group sizes for a personal experience',
			'Breathtaking scenery and photo opportunities',
		);
	}

	private function trip_itinerary( $days, $dest ) {
		$itinerary = array();
		for ( $d = 1; $d <= $days; $d++ ) {
			if ( 1 === $d ) {
				$title = sprintf( 'Arrival in %s', $dest );
				$desc  = sprintf( 'Arrive in %s where you will be met and transferred to your accommodation. Enjoy a welcome briefing and free time to settle in.', $dest );
			} elseif ( $d === $days ) {
				$title = 'Departure';
				$desc  = 'After breakfast, enjoy some free time before your transfer to the airport for your onward journey.';
			} else {
				$title = sprintf( 'Day %d — Exploration', $d );
				$desc  = sprintf( 'A full day exploring the sights and trails around %s with your guide, with stops for lunch and photography.', $dest );
			}
			$itinerary[] = array(
				'title'         => $title,
				'description'   => $desc,
				'meals'         => 1 === $d ? 'Dinner' : ( $d === $days ? 'Breakfast' : 'Breakfast, Lunch' ),
				'accommodation' => $d === $days ? '—' : 'Hotel / Lodge',
			);
		}
		return $itinerary;
	}

	private function trip_faq( $dest ) {
		return array(
			array(
				'question' => 'What is the best time to visit?',
				'answer'   => sprintf( 'The shoulder seasons of spring and autumn generally offer the most pleasant weather in %s, but the trip runs year-round.', $dest ),
			),
			array(
				'question' => 'How fit do I need to be?',
				'answer'   => 'A reasonable level of fitness is recommended. Please review the difficulty rating and contact us if you have any concerns.',
			),
			array(
				'question' => 'Are flights included?',
				'answer'   => 'International flights are not included so you can choose your preferred departure point. We are happy to advise on the best routes.',
			),
		);
	}

	/**
	 * Curated demo hotels.
	 *
	 * @return array
	 */
	private function hotels_data() {
		// title, city, country, star, lat, lng, hotel_type, facilities[], base price.
		$pool = array(
			array( 'The Grand Riverside Hotel', 'Bangkok', 'Thailand', 5, 13.7220, 100.5145, 'Luxury', array( 'Pool', 'Spa', 'Free WiFi', 'Restaurant', 'Gym' ), 180 ),
			array( 'Azure Bay Resort', 'Bali', 'Indonesia', 5, -8.6705, 115.2126, 'Resort', array( 'Beachfront', 'Pool', 'Spa', 'Free WiFi', 'Bar' ), 220 ),
			array( 'Alpine Lodge Retreat', 'Interlaken', 'Switzerland', 4, 46.6863, 7.8632, 'Lodge', array( 'Mountain View', 'Free WiFi', 'Restaurant', 'Parking', 'Sauna' ), 160 ),
			array( 'Sahara Desert Camp', 'Merzouga', 'Morocco', 3, 31.0998, -4.0119, 'Camp', array( 'Free WiFi', 'Restaurant', 'Guided Tours', 'Breakfast' ), 90 ),
			array( 'Santorini Cliff Suites', 'Santorini', 'Greece', 5, 36.4618, 25.3753, 'Boutique', array( 'Sea View', 'Pool', 'Free WiFi', 'Bar', 'Breakfast' ), 280 ),
			array( 'Kyoto Garden Ryokan', 'Kyoto', 'Japan', 4, 35.0036, 135.7780, 'Boutique', array( 'Free WiFi', 'Onsen', 'Restaurant', 'Garden' ), 150 ),
			array( 'Serengeti Safari Lodge', 'Serengeti', 'Tanzania', 4, -2.3333, 34.8333, 'Lodge', array( 'Game Drives', 'Free WiFi', 'Restaurant', 'Bar', 'Pool' ), 320 ),
			array( 'Reykjavik City Hotel', 'Reykjavik', 'Iceland', 4, 64.1466, -21.9426, 'City Hotel', array( 'Free WiFi', 'Restaurant', 'Bar', 'Parking', 'Gym' ), 170 ),
			array( 'Cusco Heritage Inn', 'Cusco', 'Peru', 3, -13.5183, -71.9781, 'Boutique', array( 'Free WiFi', 'Restaurant', 'Breakfast', 'Tour Desk' ), 95 ),
			array( 'Patagonia Wilderness Lodge', 'Torres del Paine', 'Chile', 4, -50.9423, -73.4068, 'Lodge', array( 'Mountain View', 'Restaurant', 'Bar', 'Guided Tours' ), 240 ),
			array( 'Da Nang Beach Hotel', 'Da Nang', 'Vietnam', 4, 16.0544, 108.2470, 'Resort', array( 'Beachfront', 'Pool', 'Free WiFi', 'Spa', 'Restaurant' ), 110 ),
			array( 'Kathmandu Boutique Hotel', 'Kathmandu', 'Nepal', 3, 27.7172, 85.3240, 'City Hotel', array( 'Free WiFi', 'Restaurant', 'Rooftop Terrace', 'Breakfast' ), 70 ),
		);

		$hotels = array();
		foreach ( $pool as $p ) {
			list( $title, $city, $country, $star, $lat, $lng, $type, $facilities, $base ) = $p;

			$hotels[] = array(
				'title'      => $title,
				'city'       => $city,
				'country'    => $country,
				'star'       => $star,
				'lat'        => $lat,
				'lng'        => $lng,
				'hotel_type' => $type,
				'facilities' => $facilities,
				'img_query'  => sprintf( '%s hotel %s', strtolower( $type ), $city ),
				'amenities'  => implode( ', ', $facilities ),
				'address'    => sprintf( '%s, %s', $city, $country ),
				'email'      => 'reservations@' . sanitize_title( $title ) . '.example.com',
				'phone'      => '+1 555 0' . wp_rand( 100, 999 ),
				'excerpt'    => sprintf( '%d-star %s in the heart of %s, %s.', $star, strtolower( $type ), $city, $country ),
				'content'    => sprintf(
					"<p>Welcome to %s, a %d-star %s ideally located in %s, %s. Enjoy %s and warm, attentive service throughout your stay.</p>\n<p>Our comfortable rooms, on-site dining and easy access to local attractions make this the perfect base for exploring the region.</p>",
					$title,
					$star,
					strtolower( $type ),
					$city,
					$country,
					strtolower( implode( ', ', $facilities ) )
				),
				'rooms'      => $this->hotel_rooms( $base ),
			);
		}

		return $hotels;
	}

	private function hotel_rooms( $base ) {
		return array(
			array(
				'type'        => 'Standard',
				'name'        => 'Standard Double Room',
				'description' => 'A comfortable room with all the essentials for a relaxing stay.',
				'max_guests'  => 2,
				'price'       => (float) $base,
				'sale'        => 0.0,
				'amenities'   => 'Free WiFi, Air Conditioning, TV, Private Bathroom',
				'bed_type'    => 'Double',
				'room_size'   => '24 m²',
			),
			array(
				'type'        => 'Deluxe',
				'name'        => 'Deluxe Room with View',
				'description' => 'A spacious room with premium furnishings and a stunning view.',
				'max_guests'  => 3,
				'price'       => round( $base * 1.5, 2 ),
				'sale'        => round( $base * 1.3, 2 ),
				'amenities'   => 'Free WiFi, Air Conditioning, Minibar, Balcony, TV, Private Bathroom',
				'bed_type'    => 'King',
				'room_size'   => '36 m²',
			),
			array(
				'type'        => 'Suite',
				'name'        => 'Executive Suite',
				'description' => 'Our finest accommodation with a separate living area and luxury amenities.',
				'max_guests'  => 4,
				'price'       => round( $base * 2.4, 2 ),
				'sale'        => 0.0,
				'amenities'   => 'Free WiFi, Air Conditioning, Minibar, Lounge Area, Bathtub, Balcony, TV',
				'bed_type'    => 'King + Sofa Bed',
				'room_size'   => '58 m²',
			),
		);
	}
}
