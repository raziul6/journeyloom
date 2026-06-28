<?php
/**
 * AI Chat Widget Partial.
 *
 * @package WPTravelMachine
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wptm-ai-chat">
    <button class="wptm-ai-chat__toggle" aria-label="<?php esc_attr_e( 'Open AI Chat', 'wp-travel-machine' ); ?>">💬</button>
    <div class="wptm-ai-chat__window">
        <div class="wptm-ai-chat__header">
            <h4>🤖 <?php esc_html_e( 'Travel Assistant', 'wp-travel-machine' ); ?></h4>
            <button class="wptm-ai-chat__close" aria-label="<?php esc_attr_e( 'Close', 'wp-travel-machine' ); ?>">&times;</button>
        </div>
        <div class="wptm-ai-chat__messages"></div>
        <div class="wptm-ai-chat__input">
            <input type="text" placeholder="<?php esc_attr_e( 'Ask me about trips, destinations...', 'wp-travel-machine' ); ?>">
            <button type="button"><?php esc_html_e( 'Send', 'wp-travel-machine' ); ?></button>
        </div>
    </div>
</div>
