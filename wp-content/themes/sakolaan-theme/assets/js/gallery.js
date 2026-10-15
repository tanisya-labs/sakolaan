/**
 * Sakolaan Theme: lightbox galeri (progressive enhancement).
 * Tanpa JavaScript, tautan tetap membuka foto besar. Memakai elemen <dialog> native
 * sehingga Esc, focus trap, dan pengembalian fokus ditangani browser.
 */
( function () {
	'use strict';

	if ( typeof HTMLDialogElement === 'undefined' ) {
		return;
	}

	function button( className, label, text ) {
		var el = document.createElement( 'button' );
		el.type = 'button';
		el.className = className;
		el.setAttribute( 'aria-label', label );
		el.textContent = text;
		return el;
	}

	document.querySelectorAll( '[data-sakolaan-gallery]' ).forEach( function ( gallery ) {
		var links = Array.prototype.slice.call( gallery.querySelectorAll( 'a[data-sakolaan-lightbox]' ) );

		if ( ! links.length ) {
			return;
		}

		var data = gallery.dataset;
		var index = 0;

		var dialog = document.createElement( 'dialog' );
		dialog.className = 'sakolaan-lightbox';
		dialog.setAttribute( 'aria-label', data.labelDialog || '' );

		var image = document.createElement( 'img' );
		image.className = 'sakolaan-lightbox__image';

		var close = button( 'sakolaan-lightbox__close', data.labelClose || '', '\u00d7' );
		var prev = button( 'sakolaan-lightbox__prev', data.labelPrev || '', '\u2039' );
		var next = button( 'sakolaan-lightbox__next', data.labelNext || '', '\u203a' );

		dialog.appendChild( image );
		dialog.appendChild( close );

		if ( links.length > 1 ) {
			dialog.appendChild( prev );
			dialog.appendChild( next );
		}

		function show( position ) {
			index = ( position + links.length ) % links.length;
			var thumb = links[ index ].querySelector( 'img' );
			image.src = links[ index ].href;
			image.alt = thumb ? thumb.alt : '';
		}

		links.forEach( function ( link, position ) {
			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				show( position );
				if ( ! dialog.open ) {
					dialog.showModal();
				}
			} );
		} );

		close.addEventListener( 'click', function () { dialog.close(); } );
		prev.addEventListener( 'click', function () { show( index - 1 ); } );
		next.addEventListener( 'click', function () { show( index + 1 ); } );

		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog ) {
				dialog.close();
			}
		} );

		dialog.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'ArrowRight' ) {
				show( index + 1 );
			} else if ( event.key === 'ArrowLeft' ) {
				show( index - 1 );
			}
		} );

		gallery.appendChild( dialog );
	} );
}() );
