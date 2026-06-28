<?php
/**
 * Difficulty taxonomy — how demanding a trip is (easy, moderate, challenging…).
 *
 * @package WPTravelMachine
 */

namespace WPTravelMachine\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class Difficulty extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_difficulty';
        $this->key          = 'difficulty';
        $this->object_types = array( 'wptm_trip' );
        $this->hierarchical = true;
        $this->singular     = __( 'Difficulty', 'wp-travel-machine' );
        $this->plural       = __( 'Difficulty Levels', 'wp-travel-machine' );
        $this->slug         = 'difficulty';
        $this->icon         = '⛰️';
    }
}
