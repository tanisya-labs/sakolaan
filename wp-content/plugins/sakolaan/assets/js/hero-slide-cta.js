( function () {
	'use strict';

	document.querySelectorAll( '.sakolaan-slide-cta__item' ).forEach( function ( item ) {
		var label = item.querySelector( '[data-sakolaan-cta-label]' );
		var url = item.querySelector( '[data-sakolaan-cta-url]' );
		var warning = item.querySelector( '[data-sakolaan-cta-warning]' );
		if ( ! label || ! url || ! warning ) {
			return;
		}
		function update() {
			warning.textContent = Boolean( label.value.trim() ) !== Boolean( url.value.trim() ) ? warning.dataset.warning : '';
		}
		label.addEventListener( 'input', update );
		url.addEventListener( 'input', update );
		update();
	} );
}() );
