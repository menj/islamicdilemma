/**
 * Search overlay: opens a modal <dialog>, focuses the field, locks page
 * scrolling, closes on the close button, Escape or a click outside the panel,
 * and returns focus to the search icon.
 */
( function () {
	var toggle = document.querySelector( '.search-toggle' );
	var dialog = document.getElementById( 'islamicdilemma-search' );
	if ( ! toggle || ! dialog || typeof dialog.showModal !== 'function' ) {
		return; // The icon stays a plain link to the search page.
	}

	var input = dialog.querySelector( '.search-input' );
	var closeButton = dialog.querySelector( '.search-close' );
	var root = document.documentElement;
	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	toggle.setAttribute( 'role', 'button' );
	toggle.setAttribute( 'aria-expanded', 'false' );

	var panel = dialog.querySelector( '.search-panel' );
	var closeTimer = 0;

	// Stop a pending close, so a quick reopen or a second close can't finish it early.
	function cancelPendingClose() {
		if ( closeTimer ) {
			window.clearTimeout( closeTimer );
			closeTimer = 0;
		}
		dialog.classList.remove( 'is-closing' );
	}

	function open( event ) {
		event.preventDefault();
		if ( dialog.classList.contains( 'is-closing' ) ) {
			cancelPendingClose(); // Reopened mid-animation: stay open.
			input.focus();
			return;
		}
		if ( dialog.open ) {
			return;
		}
		dialog.showModal();
		root.classList.add( 'search-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		input.focus();
		input.select();
	}

	function finishClose() {
		cancelPendingClose();
		if ( dialog.open ) {
			dialog.close();
		}
	}

	function close() {
		if ( ! dialog.open || dialog.classList.contains( 'is-closing' ) ) {
			return; // Already closed or closing.
		}
		if ( reduceMotion.matches ) {
			finishClose();
			return;
		}
		dialog.classList.add( 'is-closing' );
		// Fallback in case the exit animation never reports its end.
		closeTimer = window.setTimeout( finishClose, 320 );
	}

	// Only the panel's own exit animation finishes a close.
	panel.addEventListener( 'animationend', function ( event ) {
		if ( event.target === panel && event.animationName === 'id-sink' && dialog.classList.contains( 'is-closing' ) ) {
			finishClose();
		}
	} );

	dialog.addEventListener( 'close', function () {
		// Native close events are queued. A same-tick reopen belongs to a new session.
		if ( dialog.open ) {
			return;
		}
		cancelPendingClose();
		root.classList.remove( 'search-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.focus();
	} );

	// Escape: animate out instead of closing instantly.
	dialog.addEventListener( 'cancel', function ( event ) {
		event.preventDefault();
		close();
	} );

	// A click on the backdrop (the dialog itself, outside the panel) closes it.
	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target === dialog ) {
			close();
		}
	} );

	// Keep Tab and Shift+Tab cycling inside the panel while it is open.
	dialog.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Tab' ) {
			return;
		}
		var focusable = Array.prototype.filter.call(
			dialog.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled])' ),
			function ( el ) {
				return el.getClientRects().length > 0;
			}
		);
		if ( ! focusable.length ) {
			return;
		}
		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	toggle.addEventListener( 'click', open );
	closeButton.addEventListener( 'click', close );
	toggle.addEventListener( 'keydown', function ( event ) {
		if ( event.key === ' ' ) {
			open( event ); // Links don't respond to Space; buttons do.
		}
	} );
} )();
