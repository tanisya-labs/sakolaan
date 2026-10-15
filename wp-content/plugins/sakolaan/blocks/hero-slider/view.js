( function () {
	'use strict';

	document.querySelectorAll( '[data-sakolaan-hero-slider]' ).forEach( function ( slider ) {
		var slides = Array.prototype.slice.call( slider.querySelectorAll( '.sakolaan-hero-slider__slide' ) );
		var controls = slider.querySelector( '.sakolaan-hero-slider__controls' );
		var dots = Array.prototype.slice.call( slider.querySelectorAll( '[data-slider-dot]' ) );
		var toggle = slider.querySelector( '[data-slider-toggle]' );
		var currentCount = slider.querySelector( '[data-slider-current]' );
		var status = slider.querySelector( '[data-slider-status]' );
		var current = 0;
		var timer = null;
		var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var autoplay = slider.dataset.autoplay === 'true' && ! reducedMotion;
		var userPaused = ! autoplay;
		var temporaryPaused = false;
		var interval = Math.max( 3000, parseInt( slider.dataset.interval, 10 ) || 6000 );
		var touchStart = 0;

		if ( slides.length < 2 || ! controls ) {
			return;
		}

		controls.hidden = false;
		slider.classList.add( 'is-enhanced' );

		function announce() {
			if ( status ) {
				status.textContent = slider.dataset.statusSlide + ' ' + ( current + 1 ) + ' ' + slider.dataset.statusOf + ' ' + slides.length + ': ' + ( slides[ current ].querySelector( 'h1, h2' ).textContent || '' );
			}
		}

		function show( index, shouldAnnounce ) {
			current = ( index + slides.length ) % slides.length;
			if ( currentCount ) {
				currentCount.textContent = String( current + 1 ).padStart( 2, '0' );
			}
			slides.forEach( function ( slide, slideIndex ) {
				var active = slideIndex === current;
				slide.hidden = ! active;
				slide.classList.toggle( 'is-active', active );
			} );
			dots.forEach( function ( dot, dotIndex ) {
				if ( dotIndex === current ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
			if ( shouldAnnounce ) {
				announce();
			}
		}

		function stopTimer() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function startTimer() {
			stopTimer();
			if ( autoplay && ! userPaused && ! temporaryPaused && ! document.hidden ) {
				timer = window.setInterval( function () { show( current + 1, false ); }, interval );
			}
		}

		function updateToggle() {
			var paused = userPaused || ! autoplay;
			toggle.setAttribute( 'aria-label', paused ? toggle.dataset.playLabel : toggle.dataset.pauseLabel );
			toggle.firstElementChild.textContent = paused ? '\u25b6' : '\u275a\u275a';
		}

		slider.querySelector( '[data-slider-previous]' ).addEventListener( 'click', function () { show( current - 1, true ); startTimer(); } );
		slider.querySelector( '[data-slider-next]' ).addEventListener( 'click', function () { show( current + 1, true ); startTimer(); } );
		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () { show( parseInt( dot.dataset.sliderDot, 10 ), true ); startTimer(); } );
		} );
		toggle.addEventListener( 'click', function () {
			userPaused = ! userPaused;
			if ( userPaused ) { stopTimer(); } else { autoplay = true; startTimer(); }
			updateToggle();
		} );
		slider.addEventListener( 'mouseenter', function () { temporaryPaused = true; stopTimer(); } );
		slider.addEventListener( 'mouseleave', function () { temporaryPaused = false; startTimer(); } );
		slider.addEventListener( 'focusin', function () { temporaryPaused = true; stopTimer(); } );
		slider.addEventListener( 'focusout', function ( event ) {
			if ( ! slider.contains( event.relatedTarget ) ) { temporaryPaused = false; startTimer(); }
		} );
		slider.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'ArrowLeft' ) { show( current - 1, true ); }
			if ( event.key === 'ArrowRight' ) { show( current + 1, true ); }
		} );
		slider.addEventListener( 'touchstart', function ( event ) { touchStart = event.changedTouches[ 0 ].clientX; }, { passive: true } );
		slider.addEventListener( 'touchend', function ( event ) {
			var distance = event.changedTouches[ 0 ].clientX - touchStart;
			if ( Math.abs( distance ) > 50 ) { show( current + ( distance < 0 ? 1 : -1 ), true ); startTimer(); }
		}, { passive: true } );
		document.addEventListener( 'visibilitychange', startTimer );
		updateToggle();
		startTimer();
	} );
}() );
