/**
 * Sakolaan: pemilih gambar di halaman Pengaturan Sakolaan.
 */
( function ( $ ) {
	'use strict';

	var strings = window.sakolaanSettings || {};
	var quickLinkPanelCounter = 0;

	$( '[data-sakolaan-section-order]' ).each( function () {
		var $list = $( this );
		var $status = $list.nextAll( '.sakolaan-section-order__status' ).first();

		function updateControls() {
			$list.children( '.sakolaan-section-order__item' ).each( function ( index ) {
				var $item = $( this );
				var isFirst = index === 0;
				var isLast = index === $list.children().length - 1;
				$item.find( '[data-move="up"]' ).prop( 'disabled', isFirst ).attr( 'aria-disabled', String( isFirst ) );
				$item.find( '[data-move="down"]' ).prop( 'disabled', isLast ).attr( 'aria-disabled', String( isLast ) );
			} );
		}

		function announce( message ) {
			var labels = $list.children().map( function () {
				return $.trim( $( this ).find( '.sakolaan-section-order__label' ).text() );
			} ).get();
			$status.text( message || labels.join( ', ' ) );
			updateControls();
		}

		if ( typeof $.fn.sortable === 'function' ) {
			$list.sortable( {
				handle: '.sakolaan-section-order__handle',
				axis: 'y',
				update: function () { announce(); }
			} );
		}

		$list.on( 'click', '[data-move]', function () {
			var $button = $( this );
			var $item = $button.closest( '.sakolaan-section-order__item' );
			var direction = $button.data( 'move' );
			var $target = direction === 'up' ? $item.prev() : $item.next();

			if ( ! $target.length ) {
				return;
			}

			if ( direction === 'up' ) {
				$item.insertBefore( $target );
			} else {
				$item.insertAfter( $target );
			}

			announce();
			if ( $button.prop( 'disabled' ) ) {
				$item.find( '[data-move="' + ( direction === 'up' ? 'down' : 'up' ) + '"]' ).trigger( 'focus' );
			} else {
				$button.trigger( 'focus' );
			}
		} );

		updateControls();
	} );

	$( '[data-quick-links]' ).each( function () {
		var $wrap = $( this );
		var $rows = $wrap.find( '.sakolaan-quick-links__rows' );
		var template = $wrap.find( 'template' )[ 0 ];

		function updateHeading( $row ) {
			var $heading = $row.find( '.sakolaan-quick-link__heading' );
			var value = $row.find( 'input[name$="[label]"]' ).val();
			var label = ( typeof value === 'string' ? value.trim() : '' ) || $heading.attr( 'data-empty-label' );
			var $toggle = $row.find( '[data-quick-link-toggle]' );
			var action = $toggle.attr( 'aria-expanded' ) === 'true' ? 'data-collapse-label' : 'data-expand-label';
			$heading.text( label );
			$toggle.attr( { 'aria-label': $toggle.attr( action ) + ': ' + label, title: $toggle.attr( action ) } );
		}

		function updateMoveControls() {
			$rows.children( '[data-quick-link-row]' ).each( function ( index ) {
				var $row = $( this );
				var isFirst = index === 0;
				var isLast = index === $rows.children().length - 1;
				$row.find( '[data-move="up"]' ).prop( 'disabled', isFirst ).attr( 'aria-disabled', String( isFirst ) );
				$row.find( '[data-move="down"]' ).prop( 'disabled', isLast ).attr( 'aria-disabled', String( isLast ) );
			} );
		}

		function initializeCard( $row ) {
			var panelId = 'sakolaan-quick-link-panel-' + ( ++quickLinkPanelCounter );
			$row.find( '.sakolaan-quick-link__fields' ).attr( 'id', panelId );
			$row.find( '[data-quick-link-toggle]' ).attr( 'aria-controls', panelId ).prop( 'hidden', false );
			updateHeading( $row );
		}

		$rows.children( '[data-quick-link-row]' ).each( function () { initializeCard( $( this ) ); } );
		$wrap.on( 'click', '[data-quick-link-toggle]', function () {
			var $toggle = $( this );
			var $row = $toggle.closest( '[data-quick-link-row]' );
			var expanded = $toggle.attr( 'aria-expanded' ) === 'true';
			$toggle.attr( 'aria-expanded', String( ! expanded ) );
			$row.find( '.sakolaan-quick-link__fields' ).prop( 'hidden', expanded );
			$toggle.find( '.dashicons' ).toggleClass( 'dashicons-arrow-up-alt2', ! expanded ).toggleClass( 'dashicons-arrow-down-alt2', expanded );
			updateHeading( $row );
		} );
		$wrap.on( 'input', 'input[name$="[label]"]', function () { updateHeading( $( this ).closest( '[data-quick-link-row]' ) ); } );

		function reindex() {
			$rows.children( '[data-quick-link-row]' ).each( function ( index ) {
				$( this ).find( '[name]' ).each( function () {
					this.name = this.name.replace( /\[\d+\]/, '[' + index + ']' );
				} );
			} );
			updateMoveControls();
		}

		if ( typeof $.fn.sortable === 'function' ) {
			$rows.sortable( { handle: '.dashicons-move', axis: 'y', update: reindex } );
		}
		$wrap.on( 'click', '[data-quick-link-add]', function () {
			var html = template.innerHTML.replace( /__INDEX__/g, $rows.children().length );
			$rows.append( html );
			initializeCard( $rows.children().last() );
			$rows.children().last().find( 'input' ).first().trigger( 'focus' );
		} );
		$wrap.on( 'click', '[data-quick-link-remove]', function () {
			var $row = $( this ).closest( '[data-quick-link-row]' );
			var $focusTarget = $row.next().find( '[data-quick-link-toggle]' ).first();
			if ( ! $focusTarget.length ) {
				$focusTarget = $row.prev().find( '[data-quick-link-toggle]' ).first();
			}
			if ( ! $focusTarget.length ) {
				$focusTarget = $wrap.find( '[data-quick-link-add]' );
			}
			$row.remove();
			reindex();
			$focusTarget.trigger( 'focus' );
		} );
		$wrap.on( 'click', '[data-move]', function () {
			var $button = $( this );
			var $row = $button.closest( '[data-quick-link-row]' );
			var direction = $button.data( 'move' );
			var $target = direction === 'up' ? $row.prev() : $row.next();
			if ( !$target.length ) { return; }
			if ( direction === 'up' ) { $row.insertBefore( $target ); } else { $row.insertAfter( $target ); }
			reindex();
			if ( $button.prop( 'disabled' ) ) {
				$row.find( '[data-move="' + ( direction === 'up' ? 'down' : 'up' ) + '"]' ).trigger( 'focus' );
			} else {
				$button.trigger( 'focus' );
			}
		} );
		$wrap.on( 'change', 'select[name$="[icon]"]', function () {
			if ( ! $( this ).val() ) { return; }
			var $media = $( this ).closest( '[data-quick-link-row]' ).find( '.sakolaan-quick-link__media .sakolaan-media' );
			$media.find( 'input[type="hidden"]' ).val( '' );
			$media.find( '.sakolaan-media__preview' ).empty();
		} );
		updateMoveControls();
	} );

	$( document ).on( 'click', '.sakolaan-media__select', function ( event ) {
		event.preventDefault();

		var $trigger = $( this );
		var $wrap = $trigger.closest( '.sakolaan-media' );
		if ( ! window.wp || ! wp.media ) {
			$wrap.find( '.sakolaan-media__status' ).text( strings.mediaUnavailable || '' );
			return;
		}
		var frame = wp.media( {
			title: strings.title,
			button: { text: strings.button },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var url = ( attachment.sizes && attachment.sizes.thumbnail ) ? attachment.sizes.thumbnail.url : attachment.url;

			$wrap.find( 'input[type="hidden"]' ).val( attachment.id );
			$wrap.find( '.sakolaan-media__preview' ).empty().append( $( '<img>', { src: url, alt: '' } ) );
			$wrap.find( '.sakolaan-media__status' ).text( strings.imageSelected || '' );
			$wrap.closest( '[data-quick-link-row]' ).find( 'select[name$="[icon]"]' ).val( '' );
		} );
		frame.on( 'close', function () {
			$trigger.trigger( 'focus' );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.sakolaan-media__remove', function ( event ) {
		event.preventDefault();

		var $wrap = $( this ).closest( '.sakolaan-media' );

		$wrap.find( 'input[type="hidden"]' ).val( '' );
		$wrap.find( '.sakolaan-media__preview' ).empty();
		$wrap.find( '.sakolaan-media__status' ).text( strings.imageRemoved || '' );
	} );

	function resetSubmittingState( form ) {
		var $form = $( form );
		$form.removeData( 'sakolaan-submitting' ).removeAttr( 'aria-busy' );
		$form.find( ':submit' ).prop( 'disabled', false );
		$form.find( '.sakolaan-submit-spinner' ).remove();
	}

	$( '.sakolaan-settings-form, .sakolaan-demo-form' ).on( 'submit', function () {
		var $form = $( this );
		var $submit = $form.find( ':submit' );
		var message = $form.hasClass( 'sakolaan-demo-form' ) ? strings.processing : strings.saving;

		if ( $form.data( 'sakolaan-submitting' ) ) {
			return;
		}

		$form.data( 'sakolaan-submitting', true ).attr( 'aria-busy', 'true' );
		$submit.prop( 'disabled', true );
		$( '<span>', {
			class: 'spinner is-active sakolaan-submit-spinner',
			'aria-hidden': 'true'
		} ).insertAfter( $submit.last() );
		$form.find( '.sakolaan-submit-status' ).text( message || '' );
	} );

	window.addEventListener( 'pageshow', function () {
		$( '.sakolaan-settings-form, .sakolaan-demo-form' ).each( function () {
			resetSubmittingState( this );
		} );
	} );
}( jQuery ) );
