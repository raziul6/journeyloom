<?php
/**
 * Activity taxonomy — what you do on a trip (hiking, diving, safari…).
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activity extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_activity';
        $this->key          = 'activity';
        $this->object_types = array( 'wptm_trip' );
        $this->hierarchical = false;
        $this->singular     = __( 'Activity', 'wp-travel-machine' );
        $this->plural       = __( 'Activities', 'wp-travel-machine' );
        $this->slug         = 'activity';
        $this->icon         = '🎯';
    }
}
