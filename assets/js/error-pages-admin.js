/* Error pages tab: copy the .htaccess lines to the clipboard. */
( function () {
	document.querySelectorAll( '.islamicdilemma-copy' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var field = document.getElementById( button.getAttribute( 'data-target' ) );
			var status = button.parentNode.querySelector( '.islamicdilemma-copy-status' );
			if ( ! field ) {
				return;
			}
			var done = function () {
				if ( status ) {
					status.textContent = button.getAttribute( 'data-done' );
				}
			};
			// The older copy command, used when the clipboard API is missing or refused.
			var fallback = function () {
				field.focus();
				field.select();
				try {
					if ( document.execCommand( 'copy' ) ) {
						done();
					}
				} catch ( e ) {
					// The text stays selected, ready to copy by hand.
				}
			};
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( field.value ).then( done, fallback );
			} else {
				fallback();
			}
		} );
	} );
}() );
