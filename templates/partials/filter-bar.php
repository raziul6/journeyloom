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

// Allowed tags when echoing the generated <option> markup.
$wptm_opt_allowed = array( 'option' => array( 'value' => true, 'selected' => true ) );
?>
<form class="wptm-filter-bar" data-filter-type="<?php echo esc_attr( $ptype ); ?>" role="search">
	<div class="wptm-filter-bar__head">
		<span class="wptm-filter-bar__title"><?php echo wp_kses( wptm_icon( 'search', array( 'size' => 17 ) ), wptm_svg_allowed() ); ?> <?php esc_html_e( 'Filter results', 'byteflows-travel-hotel-booking' ); ?></span>
		<span class="wptm-filter-count" aria-live="polite"></span>
	</div>

	<div class="wptm-filter-bar__grid">
		<div class="wptm-filter-field wptm-filter-field--search">
			<label><?php esc_html_e( 'Keyword', 'byteflows-travel-hotel-booking' ); ?></label>
			<div class="wptm-filter-search">
				<?php echo wp_kses( wptm_icon( 'search', array( 'size' => 16 ) ), wptm_svg_allowed() ); ?>
				<input type="search" name="keyword" placeholder="<?php echo esc_attr( $is_hotel ? __( 'Search hotels…', 'byteflows-travel-hotel-booking' ) : __( 'Search trips…', 'byteflows-travel-hotel-booking' ) ); ?>">
			</div>
		</div>

		<div class="wptm-filter-field">
			<label><?php esc_html_e( 'Destination', 'byteflows-travel-hotel-booking' ); ?></label>
			<select name="destination"><?php echo wp_kses( $wptm_term_options( 'wptm_destination', __( 'All destinations', 'byteflows-travel-hotel-booking' ) ), $wptm_opt_allowed ) ?></select>
		</div>

		<?php if ( $is_hotel ) : ?>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Hotel Type', 'byteflows-travel-hotel-booking' ); ?></label>
				<select name="hotel_type"><?php echo wp_kses( $wptm_term_options( 'wptm_hotel_type', __( 'Any type', 'byteflows-travel-hotel-booking' ) ), $wptm_opt_allowed ) ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Facility', 'byteflows-travel-hotel-booking' ); ?></label>
				<select name="hotel_facility"><?php echo wp_kses( $wptm_term_options( 'wptm_hotel_facility', __( 'Any facility', 'byteflows-travel-hotel-booking' ) ), $wptm_opt_allowed ) ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Rating', 'byteflows-travel-hotel-booking' ); ?></label>
				<select name="stars">
					<option value=""><?php esc_html_e( 'Any rating', 'byteflows-travel-hotel-booking' ); ?></option>
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>"><?php /* translators: %d: minimum star rating. */ printf( esc_html__( '%d+ stars', 'byteflows-travel-hotel-booking' ), (int) $i ); ?></option>
					<?php endfor; ?>
				</select>
			</div>
		<?php else : ?>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Activity', 'byteflows-travel-hotel-booking' ); ?></label>
				<select name="activity"><?php echo wp_kses( $wptm_term_options( 'wptm_activity', __( 'Any activity', 'byteflows-travel-hotel-booking' ) ), $wptm_opt_allowed ) ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Trip Type', 'byteflows-travel-hotel-booking' ); ?></label>
				<select name="trip_type"><?php echo wp_kses( $wptm_term_options( 'wptm_trip_type', __( 'Any type', 'byteflows-travel-hotel-booking' ) ), $wptm_opt_allowed ) ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Difficulty', 'byteflows-travel-hotel-booking' ); ?></label>
				<select name="difficulty"><?php echo wp_kses( $wptm_term_options( 'wptm_difficulty', __( 'Any level', 'byteflows-travel-hotel-booking' ) ), $wptm_opt_allowed ) ?></select>
			</div>
			<div class="wptm-filter-field wptm-filter-field--price">
				<label><?php esc_html_e( 'Price range', 'byteflows-travel-hotel-booking' ); ?></label>
				<div class="wptm-filter-price">
					<input type="number" name="min_price" min="0" placeholder="<?php esc_attr_e( 'Min', 'byteflows-travel-hotel-booking' ); ?>">
					<span>–</span>
					<input type="number" name="max_price" min="0" placeholder="<?php esc_attr_e( 'Max', 'byteflows-travel-hotel-booking' ); ?>">
				</div>
			</div>
		<?php endif; ?>

		<div class="wptm-filter-field">
			<label><?php esc_html_e( 'Sort by', 'byteflows-travel-hotel-booking' ); ?></label>
			<select name="sort">
				<option value="date"><?php esc_html_e( 'Newest', 'byteflows-travel-hotel-booking' ); ?></option>
				<option value="name"><?php esc_html_e( 'Name (A–Z)', 'byteflows-travel-hotel-booking' ); ?></option>
				<?php if ( $is_hotel ) : ?>
					<option value="stars"><?php esc_html_e( 'Top rated', 'byteflows-travel-hotel-booking' ); ?></option>
				<?php else : ?>
					<option value="price_low"><?php esc_html_e( 'Price: Low to High', 'byteflows-travel-hotel-booking' ); ?></option>
					<option value="price_high"><?php esc_html_e( 'Price: High to Low', 'byteflows-travel-hotel-booking' ); ?></option>
				<?php endif; ?>
			</select>
		</div>
	</div>

	<div class="wptm-filter-bar__actions">
		<button type="reset" class="wptm-filter-reset"><?php esc_html_e( 'Reset filters', 'byteflows-travel-hotel-booking' ); ?></button>
	</div>
</form>
