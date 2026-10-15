(function () {
	'use strict';

	var wrapper = document.querySelector('.sakolaan-dashboard__navigation');
	var toggle = document.querySelector('.sakolaan-dashboard__nav-toggle');
	var navigation = document.getElementById('sakolaan-primary-navigation');

	if (!wrapper || !toggle || !navigation) {
		return;
	}

	var mediaQuery = window.matchMedia('(max-width: 782px)');
	var hasUserChoice = false;

	function setExpanded(expanded) {
		toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
		navigation.hidden = !expanded;
		wrapper.classList.toggle('is-open', expanded);
	}

	function syncLayout(event) {
		if (event && event.matches) {
			hasUserChoice = false;
		}

		if (mediaQuery.matches) {
			wrapper.classList.add('is-collapsible');
			if (!hasUserChoice) {
				setExpanded(false);
			}
			return;
		}

		wrapper.classList.remove('is-collapsible');
		setExpanded(true);
	}

	toggle.addEventListener('click', function () {
		hasUserChoice = true;
		setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
	});

	navigation.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape' || !mediaQuery.matches) {
			return;
		}

		hasUserChoice = true;
		setExpanded(false);
		toggle.focus();
	});

	document.addEventListener('click', function (event) {
		if (!mediaQuery.matches || toggle.getAttribute('aria-expanded') !== 'true' || wrapper.contains(event.target)) {
			return;
		}

		hasUserChoice = true;
		setExpanded(false);
	});

	if (typeof mediaQuery.addEventListener === 'function') {
		mediaQuery.addEventListener('change', syncLayout);
	} else {
		mediaQuery.addListener(syncLayout);
	}

	syncLayout();
}());
