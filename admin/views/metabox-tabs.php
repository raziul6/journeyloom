<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables (included within the template loader), not true globals.
/**
 * Shared tabbed metabox shell.
 *
 * Renders a horizontal tab nav + panels. Each panel includes a sub-view file.
 * The including render method must define:
 *
 * @var array $tabs  Map of key => array( 'label', 'icon', 'view' ).
 *
 * Plus whatever variables the individual panel views expect
 * ($fields, $itinerary, $pricing, $rooms, $lat, $lng, $addr, …).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( empty( $tabs ) || ! is_array( $tabs ) ) return;

$first = array_key_first( $tabs );
?>
<div class="wptm-mb" data-wptm-mbtabs>
    <nav class="wptm-mb__nav" role="tablist" aria-label="<?php esc_attr_e( 'Sections', 'journeyloom' ); ?>">
        <?php foreach ( $tabs as $key => $tab ) : ?>
            <button type="button"
                    class="wptm-mb__tab<?php echo $key === $first ? ' is-active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $key === $first ? 'true' : 'false'; ?>"
                    data-tab="<?php echo esc_attr( $key ); ?>">
                <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                <span class="wptm-mb__tab-label"><?php echo esc_html( $tab['label'] ); ?></span>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="wptm-mb__panels">
        <?php foreach ( $tabs as $key => $tab ) :
            $view = WPTM_PLUGIN_DIR . 'admin/views/' . $tab['view'] . '.php';
            ?>
            <div class="wptm-mb__panel<?php echo $key === $first ? ' is-active' : ''; ?>"
                 role="tabpanel"
                 data-panel="<?php echo esc_attr( $key ); ?>">
                <?php if ( file_exists( $view ) ) include $view; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
