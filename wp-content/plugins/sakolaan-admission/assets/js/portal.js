( function () {
	'use strict';
	var config = window.sakolaanAdmission || {};
	var messages = config.messages || {};

	function api( path, options ) {
		options = options || {};
		options.headers = Object.assign( { 'X-WP-Nonce': config.nonce || '' }, options.headers || {} );
		return fetch( config.root + path, options ).then( function ( response ) {
			return response.json().catch( function () { return {}; } ).then( function ( body ) {
				if ( ! response.ok ) {
					throw new Error( body.message || messages.requestFailed );
				}
				return body;
			} );
		} );
	}

	function answers( form ) {
		var output = {};
		new FormData( form ).forEach( function ( value, key ) {
			if ( value instanceof File ) {
				return;
			}
			key = key.replace( /\[\]$/, '' );
			output[ key ] = Object.prototype.hasOwnProperty.call( output, key ) ? ( Array.isArray( output[ key ] ) ? output[ key ].concat( value ) : [ output[ key ], value ] ) : value;
		} );
		return output;
	}

	function updateVisibility( form ) {
		form.querySelectorAll( '[data-visible-field]' ).forEach( function ( row ) {
			var source = form.elements[ row.dataset.visibleField ];
			var actual = '';
			if ( source ) {
				actual = source.length && ! source.tagName ? Array.from( source ).filter( function ( item ) { return item.checked; } ).map( function ( item ) { return item.value; } ) : source.value;
			}
			var expected = row.dataset.visibleValue;
			var show = row.dataset.visibleOperator === 'not_equals' ? String( actual ) !== expected : row.dataset.visibleOperator === 'contains' ? ( Array.isArray( actual ) ? actual.indexOf( expected ) !== -1 : String( actual ).indexOf( expected ) !== -1 ) : String( actual ) === expected;
			row.hidden = ! show;
			row.querySelectorAll( 'input,select,textarea' ).forEach( function ( input ) { input.disabled = ! show; } );
		} );
	}

	function showStep( form, step ) {
		if ( ! step ) {
			return;
		}
		var target = parseInt( step.dataset.step, 10 );
		form.querySelectorAll( '[data-step]' ).forEach( function ( item ) { item.hidden = item !== step; } );
		form.querySelectorAll( '[data-progress-step]' ).forEach( function ( item ) {
			var index = parseInt( item.dataset.progressStep, 10 );
			item.classList.toggle( 'is-complete', index < target );
			if ( index === target ) {
				item.setAttribute( 'aria-current', 'step' );
			} else {
				item.removeAttribute( 'aria-current' );
			}
		} );
		if ( step.classList.contains( 'sakolaan-admission-review' ) ) {
			buildReview( form );
		}
		var focusTarget = step.querySelector( 'input:not([type=hidden]),select,textarea,button' );
		if ( focusTarget ) {
			focusTarget.focus( { preventScroll: true } );
		}
		form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	function clearErrors( form ) {
		var summary = form.querySelector( '.sakolaan-admission-errors' );
		if ( summary ) {
			summary.hidden = true;
			summary.replaceChildren();
		}
		form.querySelectorAll( '.sakolaan-field-error' ).forEach( function ( item ) { item.remove(); } );
		form.querySelectorAll( '[aria-invalid=true]' ).forEach( function ( item ) { item.removeAttribute( 'aria-invalid' ); } );
	}

	function validateStep( form, step ) {
		clearErrors( form );
		var invalid = Array.from( step.querySelectorAll( 'input,select,textarea' ) ).filter( function ( field ) { return ! field.disabled && ! field.checkValidity(); } );
		if ( ! invalid.length ) {
			return true;
		}
		var summary = form.querySelector( '.sakolaan-admission-errors' );
		var heading = document.createElement( 'strong' );
		var list = document.createElement( 'ul' );
		heading.textContent = messages.completeRequired;
		invalid.forEach( function ( field, index ) {
			if ( ! field.id ) {
				field.id = 'sakolaan-invalid-' + index;
			}
			field.setAttribute( 'aria-invalid', 'true' );
			var label = step.querySelector( 'label[for="' + field.id + '"]' );
			var text = label ? label.textContent.replace( '*', '' ).trim() : field.name;
			var item = document.createElement( 'li' );
			var link = document.createElement( 'a' );
			link.href = '#' + field.id;
			link.textContent = text;
			link.addEventListener( 'click', function ( event ) { event.preventDefault(); field.focus(); } );
			item.appendChild( link );
			list.appendChild( item );
			var row = field.closest( '.sakolaan-admission-field' );
			if ( row && ! row.querySelector( '.sakolaan-field-error' ) ) {
				var inline = document.createElement( 'small' );
				inline.className = 'sakolaan-field-error';
				inline.textContent = field.validationMessage || messages.completeRequired;
				row.appendChild( inline );
			}
		} );
		summary.append( heading, list );
		summary.hidden = false;
		invalid[ 0 ].focus();
		return false;
	}

	function setBusy( form, busy ) {
		form.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
		form.querySelectorAll( 'button' ).forEach( function ( button ) { button.disabled = busy; } );
	}

	function createApplication( form ) {
		if ( form.dataset.application ) {
			return Promise.resolve( { id: parseInt( form.dataset.application, 10 ) } );
		}
		return api( 'applications', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { program_id: parseInt( form.dataset.program, 10 ), period_id: parseInt( form.dataset.period, 10 ) } )
		} ).then( function ( app ) {
			form.dataset.application = app.id;
			return app;
		} );
	}

	function uploadFiles( form, appId, payload ) {
		var uploads = [];
		form.querySelectorAll( 'input[type=file]' ).forEach( function ( input ) {
			if ( ! input.files[ 0 ] ) {
				return;
			}
			var data = new FormData();
			data.append( 'file', input.files[ 0 ] );
			if ( input.dataset.requirement ) {
				data.append( 'requirement_id', input.dataset.requirement );
			}
			uploads.push( api( 'applications/' + appId + '/documents', { method: 'POST', body: data } ).then( function ( documentRecord ) {
				payload[ input.name ] = String( documentRecord.id );
				input.dataset.uploaded = '1';
				input.required = false;
				input.value = '';
				var row = input.closest( '.sakolaan-admission-document' );
				if ( row ) {
					var status = row.querySelector( '.sakolaan-admission-document__status' );
					status.textContent = messages.uploadComplete;
					status.classList.add( 'is-uploaded' );
				}
			} ) );
		} );
		return Promise.all( uploads );
	}

	function saveDraft( form ) {
		var status = form.querySelector( '.sakolaan-admission-status' );
		var payload = answers( form );
		clearErrors( form );
		setBusy( form, true );
		status.textContent = messages.saving;
		return createApplication( form ).then( function ( app ) {
			return uploadFiles( form, app.id, payload ).then( function () {
				return api( 'applications/' + app.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( { answers: payload } ) } );
			} );
		} ).then( function ( result ) {
			var time = new Intl.DateTimeFormat( 'id-ID', { hour: '2-digit', minute: '2-digit' } ).format( new Date() );
			status.textContent = ( messages.savedAt || messages.draftSaved ).replace( '%s', time );
			return result;
		} ).catch( function ( failure ) {
			var error = form.querySelector( '.sakolaan-admission-errors' );
			error.textContent = failure.message;
			error.hidden = false;
			status.textContent = '';
			throw failure;
		} ).finally( function () { setBusy( form, false ); } );
	}

	function readableValue( row ) {
		var values = Array.from( row.querySelectorAll( 'input:not([type=file]):not([type=hidden]),select,textarea' ) ).filter( function ( control ) {
			return ! ( ( 'checkbox' === control.type || 'radio' === control.type ) && ! control.checked );
		} ).map( function ( control ) {
			if ( 'SELECT' === control.tagName ) {
				return control.selectedOptions[ 0 ] ? control.selectedOptions[ 0 ].textContent : '';
			}
			if ( 'checkbox' === control.type || 'radio' === control.type ) {
				return control.closest( 'label' ) ? control.closest( 'label' ).textContent.trim() : control.value;
			}
			return control.value;
		} ).filter( Boolean );
		return values.length ? values.join( ', ' ) : messages.notAnswered;
	}

	function buildReview( form ) {
		var target = form.querySelector( '[data-review-content]' );
		if ( ! target ) {
			return;
		}
		target.replaceChildren();
		var answerSection = document.createElement( 'section' );
		var answerTitle = document.createElement( 'h3' );
		var answerList = document.createElement( 'dl' );
		answerTitle.textContent = messages.reviewAnswers;
		form.querySelectorAll( '[data-review-key]' ).forEach( function ( row ) {
			if ( row.hidden ) {
				return;
			}
			var wrapper = document.createElement( 'div' );
			var term = document.createElement( 'dt' );
			var description = document.createElement( 'dd' );
			term.textContent = row.dataset.reviewLabel;
			description.textContent = readableValue( row );
			wrapper.append( term, description );
			answerList.appendChild( wrapper );
		} );
		answerSection.append( answerTitle, answerList );
		var documentSection = document.createElement( 'section' );
		var documentTitle = document.createElement( 'h3' );
		var documentList = document.createElement( 'ul' );
		documentTitle.textContent = messages.reviewDocuments;
		form.querySelectorAll( '.sakolaan-admission-document' ).forEach( function ( row ) {
			var item = document.createElement( 'li' );
			item.textContent = row.dataset.documentLabel + ' — ' + row.querySelector( '.sakolaan-admission-document__status' ).textContent;
			documentList.appendChild( item );
		} );
		documentSection.append( documentTitle, documentList );
		target.append( answerSection, documentSection );
	}

	function successScreen( form, application ) {
		var section = document.createElement( 'section' );
		var icon = document.createElement( 'span' );
		var heading = document.createElement( 'h2' );
		var number = document.createElement( 'p' );
		var instruction = document.createElement( 'p' );
		var link = document.createElement( 'a' );
		section.className = 'sakolaan-admission-success';
		section.tabIndex = -1;
		icon.className = 'sakolaan-admission-success__icon';
		icon.setAttribute( 'aria-hidden', 'true' );
		icon.textContent = '✓';
		heading.textContent = messages.submissionSuccess;
		number.textContent = messages.applicationNumber + ': ' + ( application.application_number || '—' );
		instruction.textContent = messages.nextInstruction;
		link.className = 'wp-element-button';
		link.href = window.location.pathname;
		link.textContent = messages.openDashboard;
		section.append( icon, heading, number, instruction, link );
		form.replaceWith( section );
		section.focus();
		section.scrollIntoView( { behavior: 'smooth', block: 'center' } );
	}

	document.querySelectorAll( '.sakolaan-admission-form' ).forEach( function ( form ) {
		form.addEventListener( 'change', function () { updateVisibility( form ); } );
		updateVisibility( form );
		showStep( form, form.querySelector( '[data-step="' + ( form.dataset.initialStep || '0' ) + '"]' ) || form.querySelector( '[data-step]' ) );
		form.querySelectorAll( '[data-save]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { saveDraft( form ).catch( function () {} ); } );
		} );
		form.querySelectorAll( '[data-next]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var step = button.closest( '[data-step]' );
				if ( validateStep( form, step ) ) {
					saveDraft( form ).then( function () { showStep( form, step.nextElementSibling ); } ).catch( function () {} );
				}
			} );
		} );
		form.querySelectorAll( '[data-prev]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () { showStep( form, button.closest( '[data-step]' ).previousElementSibling ); } );
		} );
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var confirmation = form.querySelector( '[data-confirm-submit]' );
			if ( ! confirmation.checked ) {
				confirmation.setCustomValidity( messages.confirmSubmission );
				confirmation.reportValidity();
				return;
			}
			confirmation.setCustomValidity( '' );
			saveDraft( form ).then( function () {
				setBusy( form, true );
				return api( 'applications/' + form.dataset.application + '/submit', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' } );
			} ).then( function ( application ) {
				successScreen( form, application );
			} ).catch( function ( failure ) {
				var error = form.querySelector( '.sakolaan-admission-errors' );
				error.textContent = failure.message;
				error.hidden = false;
			} ).finally( function () { setBusy( form, false ); } );
		} );
	} );

	document.querySelectorAll( '.sakolaan-admission-register' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var status = form.querySelector( '[role=status]' );
			status.textContent = messages.creatingAccount;
			api( 'register', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( Object.fromEntries( new FormData( form ).entries() ) ) } ).then( function ( result ) {
				status.textContent = result.message || messages.checkEmail;
				form.querySelectorAll( 'input:not([type=hidden]),button' ).forEach( function ( control ) { control.disabled = true; } );
			} ).catch( function ( error ) { status.textContent = error.message; } );
		} );
	} );

	document.querySelectorAll( '.sakolaan-verification-resend' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var status = form.querySelector( '[role=status]' );
			status.textContent = messages.resending;
			api( 'verification/resend', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( Object.fromEntries( new FormData( form ).entries() ) ) } ).then( function () {
				status.textContent = messages.resent;
			} ).catch( function () { status.textContent = messages.resent; } );
		} );
	} );

	document.querySelectorAll( '.sakolaan-payment-evidence' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var status = form.querySelector( '[role=status]' );
			status.textContent = messages.uploading;
			api( 'payments/' + form.dataset.payment + '/evidence', { method: 'POST', body: new FormData( form ) } ).then( function () {
				status.textContent = messages.evidenceReceived;
				form.querySelector( 'button' ).disabled = true;
			} ).catch( function ( error ) { status.textContent = error.message; } );
		} );
	} );

	document.querySelectorAll( '.sakolaan-document-replacement' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var status = form.querySelector( '[role=status]' );
			var data = new FormData( form );
			data.append( 'requirement_id', form.dataset.requirement );
			data.append( 'replace_document_id', form.dataset.document );
			status.textContent = messages.uploading;
			form.querySelector( 'button' ).disabled = true;
			api( 'applications/' + form.dataset.application + '/documents', { method: 'POST', body: data } ).then( function () {
				status.textContent = messages.documentReplaced;
				form.querySelector( 'input' ).disabled = true;
			} ).catch( function ( error ) {
				status.textContent = error.message;
				form.querySelector( 'button' ).disabled = false;
			} );
		} );
	} );

	var query = new URLSearchParams( window.location.search );
	if ( query.has( 'sakolaan_verify' ) && query.has( 'token' ) ) {
		api( 'verify-email', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( { applicant_id: parseInt( query.get( 'sakolaan_verify' ), 10 ), token: query.get( 'token' ) } ) } ).then( function () {
			window.location.href = config.loginUrl;
		} ).catch( function ( error ) { window.alert( error.message ); } );
	}
}() );
