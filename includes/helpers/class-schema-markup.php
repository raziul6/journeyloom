<?php
namespace JourneyLoom\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class SchemaMarkup {
    public function __construct() {
        add_action( 'wp_head', array( $this, 'output_schema' ) );
    }

    public function output_schema() {
        if ( is_singular( 'wptm_trip' ) ) {
            $this->trip_schema();
        } elseif ( is_singular( 'wptm_hotel' ) ) {
            $this->hotel_schema();
        }
    }

    private function trip_schema() {
        global $post;
        $pricing = get_post_meta( $post->ID, '_wptm_pricing', true );
        $price = is_array( $pricing ) && ! empty( $pricing ) ? $pricing[0]['price'] : 0;
        $dest = wp_get_post_terms( $post->ID, 'wptm_destination', array( 'fields' => 'names' ) );

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => get_the_title(),
            'description' => wp_trim_words( $post->post_content, 30 ),
            'url' => get_permalink(),
            'image' => get_the_post_thumbnail_url( $post->ID, 'large' ),
            'touristType' => 'Traveler',
            'offers' => array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => get_option( 'wptm_currency', 'USD' ),
                'availability' => 'https://schema.org/InStock',
            ),
        );
        if ( ! empty( $dest ) ) $schema['itinerary'] = array( '@type' => 'Place', 'name' => $dest[0] );
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }

    private function hotel_schema() {
        global $post;
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            'name' => get_the_title(),
            'description' => wp_trim_words( $post->post_content, 30 ),
            'url' => get_permalink(),
            'image' => get_the_post_thumbnail_url( $post->ID, 'large' ),
            'address' => array( '@type' => 'PostalAddress', 'addressLocality' => get_post_meta( $post->ID, '_wptm_hotel_city', true ), 'addressCountry' => get_post_meta( $post->ID, '_wptm_hotel_country', true ) ),
            'starRating' => array( '@type' => 'Rating', 'ratingValue' => get_post_meta( $post->ID, '_wptm_star_rating', true ) ),
        );
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}
