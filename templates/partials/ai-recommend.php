<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * AI Trip Recommender Partial.
 *
 * Rendered by the [wptm_ai_recommend] shortcode.
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sym = get_option( 'wptm_currency_symbol', '$' );
?>
<div class="wptm-ai-recommend">
    <div class="wptm-ai-recommend__head">
        <span class="wptm-ai-recommend__icon">✨</span>
        <h3><?php echo esc_html( $atts['title'] ); ?></h3>
        <p><?php esc_html_e( 'Tell us what you want and our AI will suggest trips for you.', 'journeyloom' ); ?></p>
    </div>
    <form class="wptm-ai-recommend__form">
        <div class="wptm-form-group">
            <label for="wptm-rec-prefs"><?php esc_html_e( 'What are you looking for?', 'journeyloom' ); ?></label>
            <textarea id="wptm-rec-prefs" name="preferences" rows="3" required placeholder="<?php esc_attr_e( 'e.g. a relaxing 5-day beach holiday with great food and some hiking', 'journeyloom' ); ?>"></textarea>
        </div>
        <div class="wptm-form-group">
            <label for="wptm-rec-budget"><?php esc_html_e( 'Budget (optional)', 'journeyloom' ); ?></label>
            <input type="text" id="wptm-rec-budget" name="budget" placeholder="<?php echo esc_attr( $sym . '1500' ); ?>">
        </div>
        <button type="submit" class="wptm-btn wptm-btn--primary">
            <?php esc_html_e( 'Recommend trips', 'journeyloom' ); ?> ✨
        </button>
    </form>
    <div class="wptm-ai-recommend__status" style="display:none;"></div>
    <div class="wptm-ai-recommend__results"></div>
</div>
