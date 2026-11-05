/**
 * Reading progress bar (inc/presentation/progress.php): sets --id-read (0 to 1)
 * on the bar from the scroll position, at most once an animation frame. The
 * bar is decorative (aria-hidden); reduced motion only drops the transition.
 */
( function () {
	'use strict';
	var bar = document.querySelector( '.id-progress' );
	if ( ! bar ) {
		return;
	}
	var root = document.documentElement;
	var queued = false;

	function update() {
		queued = false;
		var max = root.scrollHeight - window.innerHeight;
		var read = max > 0 ? Math.min( 1, Math.max( 0, ( window.scrollY || root.scrollTop ) / max ) ) : 1;
		bar.style.setProperty( '--id-read', read.toFixed( 4 ) );
	}

	function queue() {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( update );
		}
	}

	bar.hidden = false;
	update();
	window.addEventListener( 'scroll', queue, { passive: true } );
	window.addEventListener( 'resize', queue, { passive: true } );
	window.addEventListener( 'load', queue );
} )();
