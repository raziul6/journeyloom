/**
 * Setup wizard — currency select fills in the symbol field.
 */
( function () {
	var sel = document.getElementById( 'wptm-cur' );
	if ( ! sel ) {
		return;
	}
	sel.addEventListener( 'change', function () {
		var o = this.options[ this.selectedIndex ];
		var sym = document.getElementById( 'wptm-cur-sym' );
		if ( o && o.dataset.symbol && sym ) {
			sym.value = o.dataset.symbol;
		}
	} );
} )();
