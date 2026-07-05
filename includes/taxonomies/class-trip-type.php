<?php
/**
 * Trip Type taxonomy — the style of trip (adventure, family, luxury…).
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class TripType extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_trip_type';
        $this->key          = 'trip_type';
        $this->object_types = array( 'wptm_trip' );
        $this->hierarchical = true;
        $this->singular     = __( 'Trip Type', 'byteflows-travel-hotel-booking' );
        $this->plural       = __( 'Trip Types', 'byteflows-travel-hotel-booking' );
        $this->slug         = 'trip-type';
        $this->icon         = '🧭';
    }
}
