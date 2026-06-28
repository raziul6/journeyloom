<?php
/**
 * Premium filter bar for trip & hotel archives.
 *
 * @var string $ptype 'trip' | 'hotel'
 * @package WPTravelMachine
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
		<span class="wptm-filter-bar__title"><?php echo wptm_icon( 'search', array( 'size' => 17 ) ); ?> <?php esc_html_e( 'Filter results', 'wp-travel-machine' ); ?></span>
		<span class="wptm-filter-count" aria-live="polite"></span>
	</div>

	<div class="wptm-filter-bar__grid">
		<div class="wptm-filter-field wptm-filter-field--search">
			<label><?php esc_html_e( 'Keyword', 'wp-travel-machine' ); ?></label>
			<div class="wptm-filter-search">
				<?php echo wptm_icon( 'search', array( 'size' => 16 ) ); ?>
				<input type="search" name="keyword" placeholder="<?php echo esc_attr( $is_hotel ? __( 'Search hotels…', 'wp-travel-machine' ) : __( 'Search trips…', 'wp-travel-machine' ) ); ?>">
			</div>
		</div>

		<div class="wptm-filter-field">
			<label><?php esc_html_e( 'Destination', 'wp-travel-machine' ); ?></label>
			<select name="destination"><?php echo $wptm_term_options( 'wptm_destination', __( 'All destinations', 'wp-travel-machine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
		</div>

		<?php if ( $is_hotel ) : ?>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Hotel Type', 'wp-travel-machine' ); ?></label>
				<select name="hotel_type"><?php echo $wptm_term_options( 'wptm_hotel_type', __( 'Any type', 'wp-travel-machine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Facility', 'wp-travel-machine' ); ?></label>
				<select name="hotel_facility"><?php echo $wptm_term_options( 'wptm_hotel_facility', __( 'Any facility', 'wp-travel-machine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Rating', 'wp-travel-machine' ); ?></label>
				<select name="stars">
					<option value=""><?php esc_html_e( 'Any rating', 'wp-travel-machine' ); ?></option>
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>"><?php printf( esc_html__( '%d+ stars', 'wp-travel-machine' ), $i ); ?></option>
					<?php endfor; ?>
				</select>
			</div>
		<?php else : ?>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Activity', 'wp-travel-machine' ); ?></label>
				<select name="activity"><?php echo $wptm_term_options( 'wptm_activity', __( 'Any activity', 'wp-travel-machine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Trip Type', 'wp-travel-machine' ); ?></label>
				<select name="trip_type"><?php echo $wptm_term_options( 'wptm_trip_type', __( 'Any type', 'wp-travel-machine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field">
				<label><?php esc_html_e( 'Difficulty', 'wp-travel-machine' ); ?></label>
				<select name="difficulty"><?php echo $wptm_term_options( 'wptm_difficulty', __( 'Any level', 'wp-travel-machine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></select>
			</div>
			<div class="wptm-filter-field wptm-filter-field--price">
				<label><?php esc_html_e( 'Price range', 'wp-travel-machine' ); ?></label>
				<div class="wptm-filter-price">
					<input type="number" name="min_price" min="0" placeholder="<?php esc_attr_e( 'Min', 'wp-travel-machine' ); ?>">
					<span>–</span>
					<input type="number" name="max_price" min="0" placeholder="<?php esc_attr_e( 'Max', 'wp-travel-machine' ); ?>">
				</div>
			</div>
		<?php endif; ?>

		<div class="wptm-filter-field">
			<label><?php esc_html_e( 'Sort by', 'wp-travel-machine' ); ?></label>
			<select name="sort">
				<option value="date"><?php esc_html_e( 'Newest', 'wp-travel-machine' ); ?></option>
				<option value="name"><?php esc_html_e( 'Name (A–Z)', 'wp-travel-machine' ); ?></option>
				<?php if ( $is_hotel ) : ?>
					<option value="stars"><?php esc_html_e( 'Top rated', 'wp-travel-machine' ); ?></option>
				<?php else : ?>
					<option value="price_low"><?php esc_html_e( 'Price: Low to High', 'wp-travel-machine' ); ?></option>
					<option value="price_high"><?php esc_html_e( 'Price: High to Low', 'wp-travel-machine' ); ?></option>
				<?php endif; ?>
			</select>
		</div>
	</div>

	<div class="wptm-filter-bar__actions">
		<button type="reset" class="wptm-filter-reset"><?php esc_html_e( 'Reset filters', 'wp-travel-machine' ); ?></button>
	</div>
</form>
