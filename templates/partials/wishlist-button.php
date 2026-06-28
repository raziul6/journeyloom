<?php
/**
 * Wishlist Button Partial.
 *
 * @package WPTravelMachine
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$item_id   = isset( $item_id ) ? $item_id : get_the_ID();
$item_type = isset( $item_type ) ? $item_type : ( get_post_type( $item_id ) === 'wptm_hotel' ? 'hotel' : 'trip' );
?>
<button class="wptm-wishlist-btn" data-item-id="<?php echo esc_attr( $item_id ); ?>" data-item-type="<?php echo esc_attr( $item_type ); ?>">
    <?php echo wptm_icon( 'heart', array( 'size' => 17 ) ); ?>
    <span class="wptm-wishlist-btn__label" data-label-save="<?php esc_attr_e( 'Save', 'wp-travel-machine' ); ?>" data-label-saved="<?php esc_attr_e( 'Saved', 'wp-travel-machine' ); ?>"><?php esc_html_e( 'Save', 'wp-travel-machine' ); ?></span>
</button>
