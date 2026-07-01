<?php
/**
 * Difficulty taxonomy — how demanding a trip is (easy, moderate, challenging…).
 *
 * @package JourneyLoom
 */

namespace JourneyLoom\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) exit;

class Difficulty extends AbstractTaxonomy {
    protected function configure() {
        $this->taxonomy     = 'wptm_difficulty';
        $this->key          = 'difficulty';
        $this->object_types = array( 'wptm_trip' );
        $this->hierarchical = true;
        $this->singular     = __( 'Difficulty', 'journeyloom' );
        $this->plural       = __( 'Difficulty Levels', 'journeyloom' );
        $this->slug         = 'difficulty';
        $this->icon         = '⛰️';
    }
}
