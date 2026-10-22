( function () {
	'use strict';

	var header = document.querySelector( '.sakolaan-header.is-style-sakolaan-header-smart' );
	if ( !header || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { return; }

	var shell = header.closest( '.wp-block-template-part' ) || header;
	var lastY = window.scrollY;
	var ticking = false;

	function menuIsOpen() {
		return !!header.querySelector( '.is-menu-open, [aria-expanded="true"]' );
	}

	function update() {
		var y = window.scrollY;
		var movingDown = y > lastY;
		var keepVisible = header.contains( document.activeElement ) || menuIsOpen();

		if ( y < 80 || keepVisible || !movingDown ) {
			shell.classList.remove( 'sakolaan-header-scroll-hidden' );
		} else {
			shell.classList.add( 'sakolaan-header-scroll-hidden' );
		}

		lastY = y;
		ticking = false;
	}

	window.addEventListener( 'scroll', function () {
		if ( !ticking ) {
			window.requestAnimationFrame( update );
			ticking = true;
		}
	}, { passive: true } );
	window.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' ) { shell.classList.remove( 'sakolaan-header-scroll-hidden' ); }
	} );
	window.addEventListener( 'focusin', function ( event ) {
		if ( header.contains( event.target ) ) { shell.classList.remove( 'sakolaan-header-scroll-hidden' ); }
	} );
}() );
