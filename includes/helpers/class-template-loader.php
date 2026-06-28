<?php
namespace WPTravelMachine\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class TemplateLoader {
    public function __construct() {
        add_filter( 'template_include', array( $this, 'load_template' ) );
    }

    public function load_template( $template ) {
        if ( is_post_type_archive( 'wptm_trip' ) ) {
            $custom = $this->locate( 'archive-trip.php' );
            if ( $custom ) return $custom;
        }
        if ( is_singular( 'wptm_trip' ) ) {
            $custom = $this->locate( 'single-trip.php' );
            if ( $custom ) return $custom;
        }
        if ( is_post_type_archive( 'wptm_hotel' ) ) {
            $custom = $this->locate( 'archive-hotel.php' );
            if ( $custom ) return $custom;
        }
        if ( is_singular( 'wptm_hotel' ) ) {
            $custom = $this->locate( 'single-hotel.php' );
            if ( $custom ) return $custom;
        }
        // Handle all WPTM taxonomy archives with a shared, decorated template.
        $wptm_taxonomies = array(
            'wptm_destination', 'wptm_activity', 'wptm_trip_type',
            'wptm_difficulty', 'wptm_hotel_type', 'wptm_hotel_facility',
        );
        if ( is_tax( $wptm_taxonomies ) ) {
            $custom = $this->locate( 'taxonomy.php' );
            if ( $custom ) return $custom;
        }
        return $template;
    }

    public function locate( $template_name ) {
        $theme = locate_template( 'wp-travel-machine/' . $template_name );
        $file  = $theme ? $theme : WPTM_PLUGIN_DIR . 'templates/' . $template_name;
        if ( ! file_exists( $file ) ) {
            return false;
        }

        /**
         * Filter the located full-page template (single/archive).
         *
         * @param string $file          Absolute path to the template.
         * @param string $template_name Template filename.
         */
        return apply_filters( 'wptm_locate_template', $file, $template_name );
    }
}
