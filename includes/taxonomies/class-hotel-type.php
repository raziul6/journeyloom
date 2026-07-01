<?php
/**
 * Hotel Type taxonomy — category of accommodation (resort, boutique, hostel…).
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class HotelType extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_hotel_type';
        $this->key          = 'hotel_type';
        $this->object_types = array( 'wptm_hotel' );
        $this->hierarchical = true;
        $this->singular     = __( 'Hotel Type', 'journeyloom' );
        $this->plural       = __( 'Hotel Types', 'journeyloom' );
        $this->slug         = 'hotel-type';
        $this->icon         = '🏨';
    }
}
