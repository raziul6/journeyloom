<?php
/**
 * Activity taxonomy — what you do on a trip (hiking, diving, safari…).
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activity extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_activity';
        $this->key          = 'activity';
        $this->object_types = array( 'wptm_trip' );
        $this->hierarchical = false;
        $this->singular     = __( 'Activity', 'journeyloom' );
        $this->plural       = __( 'Activities', 'journeyloom' );
        $this->slug         = 'activity';
        $this->icon         = '🎯';
    }
}
