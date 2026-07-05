<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Location Map Partial (Leaflet + OpenStreetMap — no API key required).
 *
 * Expected vars (via wptm_get_template):
 *   $lat, $lng  — coordinates (strings/floats)
 *   $address    — human-readable address (optional)
 *   $label      — marker popup label (optional)
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$lat     = isset( $lat ) ? trim( (string) $lat ) : '';
$lng     = isset( $lng ) ? trim( (string) $lng ) : '';
$label   = isset( $label ) ? (string) $label : '';
$address = isset( $address ) ? trim( (string) $address ) : '';
$embed   = isset( $embed ) ? trim( (string) $embed ) : ''; // Pre-sanitized iframe HTML.

// Valid, non-zero coordinates within range.
$has_coords = is_numeric( $lat ) && is_numeric( $lng )
    && abs( (float) $lat ) <= 90 && abs( (float) $lng ) <= 180
    && ! ( 0.0 === (float) $lat && 0.0 === (float) $lng );

// Nothing to show.
if ( '' === $embed && ! $has_coords && '' === $address ) {
    return;
}

$maps_query = $has_coords ? ( $lat . ',' . $lng ) : $address;
$maps_url   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $maps_query );
?>
<div class="wptm-section wptm-location">
    <h2 class="wptm-section__title"><?php esc_html_e( 'Location', 'byteflows-travel-hotel-booking' ); ?></h2>

    <?php if ( '' !== $embed ) :
        // Stored embed is built by wptm_sanitize_map_embed() — a provider-validated
        // iframe. Re-run through wp_kses on output with an iframe allowlist.
        $wptm_iframe_allowed = array(
            'iframe' => array(
                'src'             => true, 'title' => true, 'width' => true, 'height' => true,
                'style'           => true, 'loading' => true, 'referrerpolicy' => true,
                'allowfullscreen' => true, 'frameborder' => true,
            ),
        );
    ?>
    <div class="wptm-map-embed"><?php echo wp_kses( $embed, $wptm_iframe_allowed ); ?></div>
    <?php elseif ( $has_coords ) : ?>
    <div class="wptm-map"
        data-lat="<?php echo esc_attr( $lat ); ?>"
        data-lng="<?php echo esc_attr( $lng ); ?>"
        data-label="<?php echo esc_attr( $label !== '' ? $label : $address ); ?>"></div>
    <?php endif; ?>

    <div class="wptm-location__bar">
        <?php if ( '' !== $address ) : ?>
            <span class="wptm-location__addr">📍 <?php echo esc_html( $address ); ?></span>
        <?php endif; ?>
        <a class="wptm-location__directions" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Get directions', 'byteflows-travel-hotel-booking' ); ?> →
        </a>
    </div>
</div>
