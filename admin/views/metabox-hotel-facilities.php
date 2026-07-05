<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Hotel Facilities group repeater.
 *
 * Each group has a title (e.g. "General", "Wellness") and a list of facilities,
 * one per line. Stored as _wptm_hotel_facilities = [ { title, items[] }, … ].
 *
 * @var array $facility_groups
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$facility_groups = isset( $facility_groups ) && is_array( $facility_groups ) ? $facility_groups : array();
$suggestions     = implode( ', ', array_keys( wptm_hotel_facilities() ) );
?>
<div class="wptm-panel-intro">
    <span class="dashicons dashicons-yes-alt"></span>
    <p><?php esc_html_e( 'Group the hotel’s facilities (e.g. General, Wellness, Food & Drink). Each facility shows with a matching icon on the hotel page.', 'byteflows-travel-hotel-booking' ); ?></p>
</div>

<div id="wptm-facilities-builder" class="wptm-repeater">
    <input type="hidden" name="wptm_facilities_present" value="1">
    <div class="wptm-repeater-items">
        <?php foreach ( $facility_groups as $i => $group ) :
            $title = $group['title'] ?? '';
            $items = isset( $group['items'] ) && is_array( $group['items'] ) ? $group['items'] : array();
            ?>
            <div class="wptm-repeater-item" data-index="<?php echo esc_attr( $i ); ?>">
                <div class="wptm-repeater-header">
                    <span class="dashicons dashicons-menu wptm-drag"></span>
                    <span class="wptm-repeater-badge"><?php echo esc_html( $i + 1 ); ?></span>
                    <strong><?php echo esc_html( $title ) ?: sprintf( /* translators: %d: facility group number. */ esc_html__( 'Group %d', 'byteflows-travel-hotel-booking' ), (int) ( $i + 1 ) ); ?></strong>
                    <button type="button" class="wptm-remove-item button-link" aria-label="<?php esc_attr_e( 'Remove group', 'byteflows-travel-hotel-booking' ); ?>"><span class="dashicons dashicons-trash"></span></button>
                </div>
                <div class="wptm-repeater-body">
                    <input type="text" name="wptm_facilities[<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Group title (e.g. General)', 'byteflows-travel-hotel-booking' ); ?>" class="widefat wptm-mb-spacer">
                    <textarea name="wptm_facilities[<?php echo esc_attr( $i ); ?>][items]" rows="4" placeholder="<?php esc_attr_e( 'One facility per line…', 'byteflows-travel-hotel-booking' ); ?>" class="widefat"><?php echo esc_textarea( implode( "\n", array_map( 'strval', $items ) ) ); ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="wptm-empty-state"<?php echo ! empty( $facility_groups ) ? ' style="display:none"' : ''; ?>>
        <span class="dashicons dashicons-yes-alt"></span>
        <p><?php esc_html_e( 'No facility groups yet. Add a group like “General” and list its facilities.', 'byteflows-travel-hotel-booking' ); ?></p>
    </div>

    <button type="button" class="button button-primary wptm-add-item" data-target="facilities"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Facility Group', 'byteflows-travel-hotel-booking' ); ?></button>

    <p class="wptm-field-hint" style="margin-top:12px;">
        <?php
        printf(
            /* translators: %s: comma-separated facility names. */
            esc_html__( 'Facilities matching these names get a dedicated icon: %s. Any other name still gets a sensible icon automatically.', 'byteflows-travel-hotel-booking' ),
            esc_html( $suggestions )
        );
        ?>
    </p>
</div>
