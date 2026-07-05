<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Wishlist Button Partial.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$item_id   = isset( $item_id ) ? $item_id : get_the_ID();
$item_type = isset( $item_type ) ? $item_type : ( get_post_type( $item_id ) === 'wptm_hotel' ? 'hotel' : 'trip' );
?>
<button class="wptm-wishlist-btn" data-item-id="<?php echo esc_attr( $item_id ); ?>" data-item-type="<?php echo esc_attr( $item_type ); ?>">
    <?php echo wp_kses( wptm_icon( 'heart', array( 'size' => 17 ) ), wptm_svg_allowed() ); ?>
    <span class="wptm-wishlist-btn__label" data-label-save="<?php esc_attr_e( 'Save', 'byteflows-travel-hotel-booking' ); ?>" data-label-saved="<?php esc_attr_e( 'Saved', 'byteflows-travel-hotel-booking' ); ?>"><?php esc_html_e( 'Save', 'byteflows-travel-hotel-booking' ); ?></span>
</button>
