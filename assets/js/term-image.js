/* Media picker for the glossary entry image on the tag screens. */
( function ( $ ) {
	var frame;
	$( document ).on( 'click', '.id-term-image-choose', function ( e ) {
		e.preventDefault();
		var field = $( this ).closest( '.id-term-image' );
		frame = wp.media( {
			title: window.islamicdilemmaTermImage.title,
			button: { text: window.islamicdilemmaTermImage.button },
			library: { type: 'image' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var img = frame.state().get( 'selection' ).first().toJSON();
			var src = img.sizes && img.sizes.thumbnail ? img.sizes.thumbnail.url : img.url;
			field.find( '#id_image' ).val( img.id );
			field.find( '.id-term-image-preview' ).empty().append( $( '<img>', { src: src, alt: img.alt || '' } ) );
			field.find( '.id-term-image-remove' ).prop( 'hidden', false );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '.id-term-image-remove', function ( e ) {
		e.preventDefault();
		var field = $( this ).closest( '.id-term-image' );
		field.find( '#id_image' ).val( '' );
		field.find( '.id-term-image-preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );
	/* The Add Tag form posts by AJAX and clears its fields; clear the picker with them. */
	$( document ).ajaxComplete( function ( event, xhr, settings ) {
		if ( settings.data && typeof settings.data === 'string' && settings.data.indexOf( 'action=add-tag' ) !== -1 ) {
			$( '#addtag .id-term-image-remove' ).trigger( 'click' );
		}
	} );
}( jQuery ) );
