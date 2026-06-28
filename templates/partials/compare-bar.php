<?php
/**
 * Compare Bar Partial.
 *
 * @package WPTravelMachine
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wptm-compare-bar">
    <div class="wptm-compare-bar__items"></div>
    <button class="wptm-btn wptm-btn--primary wptm-btn--sm wptm-compare-bar__go"><?php esc_html_e( 'Compare', 'wp-travel-machine' ); ?></button>
    <button class="wptm-btn wptm-btn--sm wptm-compare-bar__clear"><?php esc_html_e( 'Clear', 'wp-travel-machine' ); ?></button>
</div>
