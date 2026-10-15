( function () {
	'use strict';

	var table = document.querySelector( '.wp-list-table' );
	var form = document.querySelector( '#sakolaan-order-form' );
	if ( ! table || ! form ) {
		return;
	}

	var body = table.querySelector( 'tbody' );
	var dragged = null;
	var rows = function () { return Array.prototype.slice.call( body.querySelectorAll( 'tr[id^="post-"]' ) ); };

	function refreshPositions() {
		rows().forEach( function ( row, index ) {
			var badge = row.querySelector( '.sakolaan-order-badge' );
			row.setAttribute( 'data-sakolaan-order-position', String( index + 1 ) );
			if ( badge ) {
				badge.textContent = String( ( index + 1 ) * 10 );
			}
		} );
	}

	function moveRow( row, direction ) {
		var sibling = 'up' === direction ? row.previousElementSibling : row.nextElementSibling;
		if ( ! sibling || ! sibling.matches( 'tr[id^="post-"]' ) ) {
			return;
		}
		if ( 'up' === direction ) {
			body.insertBefore( row, sibling );
		} else {
			body.insertBefore( sibling, row );
		}
		refreshPositions();
		row.querySelector( '[data-order-handle]' ).focus();
	}

	rows().forEach( function ( row ) {
		var handle = row.querySelector( '[data-order-handle]' );
		if ( ! handle ) {
			return;
		}
		handle.setAttribute( 'tabindex', '0' );
		handle.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowUp' === event.key || 'ArrowDown' === event.key ) {
				event.preventDefault();
				moveRow( row, 'ArrowUp' === event.key ? 'up' : 'down' );
			}
		} );
		handle.addEventListener( 'dragstart', function ( event ) {
			dragged = row;
			row.classList.add( 'is-sakolaan-order-dragging' );
			if ( event.dataTransfer ) {
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setData( 'text/plain', row.id );
			}
		} );
		handle.addEventListener( 'dragend', function () {
			dragged = null;
			row.classList.remove( 'is-sakolaan-order-dragging' );
		} );
		row.addEventListener( 'dragover', function ( event ) {
			if ( dragged && dragged !== row ) {
				event.preventDefault();
				if ( event.dataTransfer ) {
					event.dataTransfer.dropEffect = 'move';
				}
			}
		} );
		row.addEventListener( 'drop', function ( event ) {
			if ( ! dragged || dragged === row ) {
				return;
			}
			event.preventDefault();
			var rowsNow = rows();
			var before = rowsNow.indexOf( dragged ) < rowsNow.indexOf( row );
			body.insertBefore( dragged, before ? row.nextElementSibling : row );
			refreshPositions();
			dragged.querySelector( '[data-order-handle]' ).focus();
		} );
		row.querySelectorAll( '[data-move]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { moveRow( row, button.dataset.move ); } );
		} );
	} );
	refreshPositions();

	form.addEventListener( 'submit', function () {
		var inputs = form.querySelector( '[data-sakolaan-order-inputs]' );
		if ( ! inputs ) {
			return;
		}
		inputs.innerHTML = '';
		rows().forEach( function ( row ) {
			var input = document.createElement( 'input' );
			input.type = 'hidden';
			input.name = 'ordered_ids[]';
			input.value = row.id.replace( 'post-', '' );
			inputs.appendChild( input );
		} );
	} );
}() );
