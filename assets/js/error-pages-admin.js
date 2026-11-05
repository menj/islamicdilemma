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
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( field.value ).then( done, function () {
					field.select();
				} );
			} else {
				field.select();
				if ( document.execCommand( 'copy' ) ) {
					done();
				}
			}
		} );
	} );
}() );
