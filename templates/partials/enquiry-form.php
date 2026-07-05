<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Enquiry Form Partial (admin-configurable fields).
 *
 * Fields are defined in Settings → Enquiry Form. Submits via AJAX (wptm_enquiry).
 *
 * Expected var: $post_id
 *
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! get_option( 'wptm_enquiry_enabled', 1 ) ) {
    return;
}

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();
$fields  = wptm_enquiry_fields();
if ( empty( $fields ) ) {
    return;
}

$title = get_option( 'wptm_enquiry_title', __( 'Have a question? Send an enquiry', 'byteflows-travel-hotel-booking' ) );
?>
<div class="wptm-section wptm-enquiry">
    <h2 class="wptm-section__title"><?php echo esc_html( $title ); ?></h2>
    <form class="wptm-enquiry-form" data-post-id="<?php echo (int) $post_id; ?>">
        <?php foreach ( $fields as $i => $f ) :
            $label = $f['label'] ?? '';
            $type  = $f['type'] ?? 'text';
            $req   = ! empty( $f['required'] );
            $id    = 'wptm-enq-' . (int) $i;
            $name  = 'enquiry[' . (int) $i . ']';
            ?>
        <div class="wptm-form-group">
            <label for="<?php echo esc_attr( $id ); ?>">
                <?php echo esc_html( $label ); ?><?php if ( $req ) : ?> <span class="wptm-req">*</span><?php endif; ?>
            </label>
            <?php if ( 'textarea' === $type ) : ?>
                <textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="4"<?php echo $req ? ' required' : ''; ?>></textarea>
            <?php elseif ( 'select' === $type || 'country' === $type ) :
                $opts = ( 'country' === $type )
                    ? wptm_countries()
                    : array_filter( array_map( 'trim', explode( ',', (string) ( $f['options'] ?? '' ) ) ) ); ?>
                <select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"<?php echo $req ? ' required' : ''; ?>>
                    <option value=""><?php echo 'country' === $type ? esc_html__( 'Select a country…', 'byteflows-travel-hotel-booking' ) : esc_html__( 'Select…', 'byteflows-travel-hotel-booking' ); ?></option>
                    <?php foreach ( $opts as $opt ) : ?>
                        <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input type="<?php echo esc_attr( in_array( $type, array( 'email', 'tel', 'number' ), true ) ? $type : 'text' ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"<?php echo $req ? ' required' : ''; ?>>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="wptm-btn wptm-btn--primary"><?php esc_html_e( 'Send Enquiry', 'byteflows-travel-hotel-booking' ); ?></button>
        <div class="wptm-enquiry__status" role="status" aria-live="polite"></div>
    </form>
</div>
