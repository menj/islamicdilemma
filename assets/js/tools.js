/* Filter for the glossary, reading list and verse index. Without JavaScript every entry stays visible. */
( function () {
	document.querySelectorAll( '.tool-filter' ).forEach( function ( bar ) {
		// The sticky filter box hides what sits under it. Keep scroll-padding-top equal to its real height,
		// so a focused link scrolled into view stays clear of it (WCAG 2.4.11).
		function reserve() {
			var sticky = 'sticky' === window.getComputedStyle( bar ).position;
			document.documentElement.style.scrollPaddingTop = sticky ? Math.ceil( bar.offsetHeight + 24 ) + 'px' : '';
		}
		reserve();
		window.addEventListener( 'resize', reserve );
		if ( window.ResizeObserver ) { new ResizeObserver( reserve ).observe( bar ); }
		var list = document.getElementById( bar.getAttribute( 'data-target' ) );
		if ( ! list ) { return; }
		var q = bar.querySelector( '.tool-filter-q' );
		var chips = bar.querySelectorAll( '.chip' );
		var count = bar.querySelector( '.tool-count' );
		var cat = '';
		var fold = function ( s ) {
			return s.normalize( 'NFD' ).replace( /[̀-ͯʻʾʿʼ]/g, '' ).toLowerCase();
		};
		function apply() {
			var term = fold( q.value.trim() );
			var shown = 0;
			list.querySelectorAll( '.tool-item' ).forEach( function ( it ) {
				var group = it.closest( '.tool-group' );
				var itemCat = it.getAttribute( 'data-cat' ) || ( group && group.getAttribute( 'data-cat' ) ) || '';
				var ok = ( ! cat || itemCat === cat ) && ( ! term || fold( it.getAttribute( 'data-text' ) || it.textContent ).indexOf( term ) !== -1 );
				it.hidden = ! ok;
				if ( ok ) { shown++; }
			} );
			list.querySelectorAll( '.tool-group' ).forEach( function ( g ) {
				g.hidden = ! g.querySelector( '.tool-item:not([hidden])' );
			} );
			list.querySelectorAll( 'h2[data-cat]' ).forEach( function ( h ) {
				h.hidden = cat && h.getAttribute( 'data-cat' ) !== cat;
			} );
			count.textContent = shown + ( shown === 1 ? ' entry' : ' entries' );
		}
		q.addEventListener( 'input', apply );
		chips.forEach( function ( c ) {
			c.addEventListener( 'click', function () {
				cat = c.getAttribute( 'data-cat' );
				chips.forEach( function ( o ) {
					var on = o === c;
					o.classList.toggle( 'is-on', on );
					o.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				} );
				apply();
			} );
		} );
		apply();
	} );
}() );
