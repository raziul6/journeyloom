<?php
/**
 * Hotel Facility taxonomy — bookable amenities (pool, spa, free wifi, parking…).
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class HotelFacility extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_hotel_facility';
        $this->key          = 'hotel_facility';
        $this->object_types = array( 'wptm_hotel' );
        $this->hierarchical = false;
        $this->singular     = __( 'Hotel Facility', 'wp-travel-machine' );
        $this->plural       = __( 'Hotel Facilities', 'wp-travel-machine' );
        $this->slug         = 'hotel-facility';
        $this->icon         = '🛎️';
    }
}
