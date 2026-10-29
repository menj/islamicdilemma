/**
 * Editor additions, written without a build step:
 * - a "Qur'an reference" text format that wraps text in <span class="ref">
 * - editor previews for the theme's server-rendered blocks
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	wp.richText.registerFormatType( 'islamicdilemma/ref', {
		title: __( "Qur'an reference", 'islamicdilemma' ),
		tagName: 'span',
		className: 'ref',
		edit: function ( props ) {
			return el( wp.blockEditor.RichTextToolbarButton, {
				icon: 'book-alt',
				title: __( "Qur'an reference", 'islamicdilemma' ),
				isActive: props.isActive,
				onClick: function () {
					props.onChange( wp.richText.toggleFormat( props.value, { type: 'islamicdilemma/ref' } ) );
				},
			} );
		},
	} );

	wp.blocks.registerBlockType( 'islamicdilemma/search-toggle', {
		edit: function () {
			var blockProps = wp.blockEditor.useBlockProps( { className: 'search-toggle-wrap' } );
			return el(
				'div',
				blockProps,
				el(
					'span',
					{ className: 'search-toggle', title: __( 'Opens the search overlay on the site', 'islamicdilemma' ) },
					el(
						'svg',
						{ className: 'search-icon', width: 20, height: 20, viewBox: '0 0 24 24', 'aria-hidden': true },
						el( 'circle', { cx: 10.5, cy: 10.5, r: 6.5, fill: 'none', stroke: 'currentColor', strokeWidth: 2 } ),
						el( 'path', { d: 'M15.5 15.5 21 21', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' } )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// Server-rendered blocks: show a readable placeholder in the editor.
	[
		[ 'islamicdilemma/reading-time', __( '7 min read', 'islamicdilemma' ) ],
		[ 'islamicdilemma/result-type', __( 'Category', 'islamicdilemma' ) ],
	].forEach( function ( pair ) {
		wp.blocks.registerBlockType( pair[ 0 ], {
			edit: function () {
				return el( 'span', wp.blockEditor.useBlockProps(), pair[ 1 ] );
			},
			save: function () {
				return null;
			},
		} );
	} );
} )( window.wp );
