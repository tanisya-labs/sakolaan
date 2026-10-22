( function () {
	'use strict';

	var motionPreference = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var root = document.documentElement;
	var revealSelector = [
		'.sakolaan-page-hero__inner',
		'.sakolaan-page-hero > .alignwide:not(.sakolaan-page-hero__inner)',
		'.sakolaan-hero:not(.sakolaan-hero-slider) .sakolaan-hero__text',
		'.sakolaan-hero-slider__controls',
		'.sakolaan-section-header',
		'.sakolaan-introduction > *',
		'.sakolaan-principal > *',
		'.sakolaan-stat',
		'.sakolaan-quick-link',
		'.sakolaan-news-card',
		'.sakolaan-card',
		'.sakolaan-post-card',
		'.sakolaan-listing-card',
		'.sakolaan-program-card',
		'.sakolaan-testimonial-card',
		'.sakolaan-catalog-card',
		'.sakolaan-archive-controls',
		'.sakolaan-archive-toolbar',
		'.wp-block-query-pagination',
		'.sakolaan-empty',
		'.sakolaan-detail__media',
		'.sakolaan-detail__facts',
		'.sakolaan-detail > .sakolaan-prose',
		'.sakolaan-profile',
		'.sakolaan-detail__taxonomy',
		'.sakolaan-post-nav',
		'.sakolaan-cta__copy',
		'.sakolaan-cta__actions',
		'.sakolaan-gallery__list > *',
		'.sakolaan-footer__main > *'
	].join( ',' );
	var groupSelector = [
		'.wp-block-post-template',
		'.sakolaan-news-grid',
		'.sakolaan-grid',
		'.sakolaan-stats',
		'.sakolaan-quick-links__grid',
		'.sakolaan-gallery__list',
		'.sakolaan-footer__main'
	].join( ',' );

	if ( motionPreference.matches || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var candidates = Array.prototype.slice.call( document.querySelectorAll( revealSelector ) );
	var revealItems = [];

	/* Avoid stacking two reveal transforms on nested components. */
	candidates.forEach( function ( element ) {
		if ( element.parentElement && element.parentElement.closest( '.sakolaan-reveal' ) ) {
			return;
		}

		element.classList.add( 'sakolaan-reveal' );
		revealItems.push( element );
	} );

	if ( ! revealItems.length ) {
		return;
	}

	document.querySelectorAll( groupSelector ).forEach( function ( group ) {
		var index = 0;

		group.querySelectorAll( '.sakolaan-reveal' ).forEach( function ( element ) {
			if ( element.closest( groupSelector ) !== group ) {
				return;
			}

			element.style.setProperty( '--sakolaan-reveal-delay', Math.min( index * 55, 220 ) + 'ms' );
			index += 1;
		} );
	} );

	root.classList.add( 'sakolaan-motion-enabled' );

	var observer = new window.IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			var element = entry.target;
			var isLargeElement = entry.boundingClientRect.height > window.innerHeight * 0.8;

			if ( entry.isIntersecting && ( entry.intersectionRatio >= 0.14 || isLargeElement ) ) {
				element.classList.add( 'is-in-view' );
				return;
			}

			/* Reset only after the element is fully outside the viewport. */
			if ( ! entry.isIntersecting && ! element.contains( document.activeElement ) ) {
				element.classList.remove( 'is-in-view' );
			}
		} );
	}, {
		rootMargin: '0px 0px -6% 0px',
		threshold: [ 0, 0.14 ]
	} );

	revealItems.forEach( function ( element ) {
		observer.observe( element );
	} );

	document.addEventListener( 'focusin', function ( event ) {
		var element = event.target.closest && event.target.closest( '.sakolaan-reveal' );

		if ( element ) {
			element.classList.add( 'is-in-view' );
		}
	} );

	function disableMotion( event ) {
		if ( ! event.matches ) {
			return;
		}

		observer.disconnect();
		root.classList.remove( 'sakolaan-motion-enabled' );
		revealItems.forEach( function ( element ) {
			element.classList.remove( 'sakolaan-reveal', 'is-in-view' );
			element.style.removeProperty( '--sakolaan-reveal-delay' );
		} );
	}

	if ( motionPreference.addEventListener ) {
		motionPreference.addEventListener( 'change', disableMotion );
	} else {
		motionPreference.addListener( disableMotion );
	}
}() );
