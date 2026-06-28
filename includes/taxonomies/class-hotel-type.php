<?php
/**
 * Hotel Type taxonomy — category of accommodation (resort, boutique, hostel…).
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class HotelType extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_hotel_type';
        $this->key          = 'hotel_type';
        $this->object_types = array( 'wptm_hotel' );
        $this->hierarchical = true;
        $this->singular     = __( 'Hotel Type', 'wp-travel-machine' );
        $this->plural       = __( 'Hotel Types', 'wp-travel-machine' );
        $this->slug         = 'hotel-type';
        $this->icon         = '🏨';
    }
}
