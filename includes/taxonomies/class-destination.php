<?php
/**
 * Destination taxonomy — where a trip or hotel is located.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class Destination extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_destination';
        $this->key          = 'destination';
        $this->object_types = array( 'wptm_trip', 'wptm_hotel' );
        $this->hierarchical = true;
        $this->singular     = __( 'Destination', 'journeyloom' );
        $this->plural       = __( 'Destinations', 'journeyloom' );
        $this->slug         = 'destination';
        $this->icon         = '🌍';
    }
}
