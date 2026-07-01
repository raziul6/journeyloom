<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Premium filter bar for trip & hotel archives.
 *
 * @var string $ptype 'trip' | 'hotel'
 * @package JourneyLoom
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$ptype    = isset( $ptype ) && 'hotel' === $ptype ? 'hotel' : 'trip';
$is_hotel = 'hotel' === $ptype;

/**
 * Build <option> markup for a taxonomy's terms.
 */
$wptm_term_options = static function ( $taxonomy, $placeholder ) {
	$out   = '<option value="">' . esc_html( $placeholder ) . '</option>';
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$out .= '<option value="' . esc_attr( $t->slug ) . '">' . esc_html( $t->name ) . '</option>';
		}
	}
	return $out;
};
?>
<form class="wptm-filter-bar" data-filter-type="<?php echo esc_attr( $ptype ); ?>" role="search">
	<div class="wptm-filter-bar__head">
		<span class="wptm-filter-bar__title"><?php echo wp_kses( wptm_icon( 'search', array( 'size' => 17 ) ), wptm_svg_allowed() ); ?> <?php esc_html_e( 'Filter results', 'journeyloom' ); ?></span>
		<span class="wptm-filter-count" aria-live="polite"></span>
	</div>

	<div class="wptm-filter-bar__grid">
		<div class="wptm-filter-field wptm-filter-field--search">
			<label><?php esc_html_e( 'Keyword', 'journeyloom' ); ?></label>
			<div class="wptm-filter-search">
				<?php echo wp_kses( wptm_icon( 'search', array( 'size' => 16 ) ), wptm_svg_allowed() ); ?>
				<input type="search" name="keyword" placeholder="<?php echo esc_attr( $is_hotel ? __( 'Search hotels…', 'journeyloom' ) : __( 'Search trips…', 'journeyloom' ) ); ?>">
			</div>
		</div>

		<div class="wptm-filter-field">
			<label><?php esc_html_e( 'Destination', 'journeyloom' ); ?></label>
			<select name="destination"><?php echo $wptm_term_options( 'wptm_destination', __( 'All destinations', 'journeyloom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
		</div>

		<?php if ( $is_hotel ) : ?>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Hotel Type', 'journeyloom' ); ?></label>
				<select name="hotel_type"><?php echo $wptm_term_options( 'wptm_hotel_type', __( 'Any type', 'journeyloom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Facility', 'journeyloom' ); ?></label>
				<select name="hotel_facility"><?php echo $wptm_term_options( 'wptm_hotel_facility', __( 'Any facility', 'journeyloom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Rating', 'journeyloom' ); ?></label>
				<select name="stars">
					<option value=""><?php esc_html_e( 'Any rating', 'journeyloom' ); ?></option>
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>"><?php /* translators: %d: minimum star rating. */ printf( esc_html__( '%d+ stars', 'journeyloom' ), (int) $i ); ?></option>
					<?php endfor; ?>
				</select>
			</div>
		<?php else : ?>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Activity', 'journeyloom' ); ?></label>
				<select name="activity"><?php echo $wptm_term_options( 'wptm_activity', __( 'Any activity', 'journeyloom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Trip Type', 'journeyloom' ); ?></label>
				<select name="trip_type"><?php echo $wptm_term_options( 'wptm_trip_type', __( 'Any type', 'journeyloom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Difficulty', 'journeyloom' ); ?></label>
				<select name="difficulty"><?php echo $wptm_term_options( 'wptm_difficulty', __( 'Any level', 'journeyloom' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field wptm-filter-field--price">
				<label><?php esc_html_e( 'Price range', 'journeyloom' ); ?></label>
				<div class="wptm-filter-price">
					<input type="number" name="min_price" min="0" placeholder="<?php esc_attr_e( 'Min', 'journeyloom' ); ?>">
					<span>–</span>
					<input type="number" name="max_price" min="0" placeholder="<?php esc_attr_e( 'Max', 'journeyloom' ); ?>">
				</div>
			</div>
		<?php endif; ?>

		<div class="wptm-filter-field">
			<label><?php esc_html_e( 'Sort by', 'journeyloom' ); ?></label>
			<select name="sort">
				<option value="date"><?php esc_html_e( 'Newest', 'journeyloom' ); ?></option>
				<option value="name"><?php esc_html_e( 'Name (A–Z)', 'journeyloom' ); ?></option>
				<?php if ( $is_hotel ) : ?>
					<option value="stars"><?php esc_html_e( 'Top rated', 'journeyloom' ); ?></option>
				<?php else : ?>
					<option value="price_low"><?php esc_html_e( 'Price: Low to High', 'journeyloom' ); ?></option>
					<option value="price_high"><?php esc_html_e( 'Price: High to Low', 'journeyloom' ); ?></option>
				<?php endif; ?>
			</select>
		</div>
	</div>

	<div class="wptm-filter-bar__actions">
		<button type="reset" class="wptm-filter-reset"><?php esc_html_e( 'Reset filters', 'journeyloom' ); ?></button>
	</div>
</form>
