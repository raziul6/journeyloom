<?php
/**
 * AI Chat Widget Partial.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wptm-ai-chat">
    <button class="wptm-ai-chat__toggle" aria-label="<?php esc_attr_e( 'Open AI Chat', 'journeyloom' ); ?>">💬</button>
    <div class="wptm-ai-chat__window">
        <div class="wptm-ai-chat__header">
            <h4>🤖 <?php esc_html_e( 'Travel Assistant', 'journeyloom' ); ?></h4>
            <button class="wptm-ai-chat__close" aria-label="<?php esc_attr_e( 'Close', 'journeyloom' ); ?>">&times;</button>
        </div>
        <div class="wptm-ai-chat__messages" role="log" aria-live="polite" aria-atomic="false"></div>
        <div class="wptm-ai-chat__input">
            <textarea rows="1" placeholder="<?php esc_attr_e( 'Ask me about trips, destinations...', 'journeyloom' ); ?>"></textarea>
            <button type="button"><?php esc_html_e( 'Send', 'journeyloom' ); ?></button>
        </div>
    </div>
</div>
