<?php
/**
 * Keep the site light-only.
 *
 * Twenty Twenty-Five ships dark style variations (Evening, Midnight and
 * others), and a child theme inherits them in the Site Editor. The owner has
 * ruled out dark mode, so the variations list is emptied and the site always
 * renders the palette defined in this theme's theme.json.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return no style variations, colour palettes or typography presets from the
 * parent theme to the Site Editor.
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send.
 * @param array                                             $handler  Route handler.
 * @param WP_REST_Request                                   $request  Request.
 * @return mixed
 */
function islamicdilemma_no_style_variations( $response, $handler, $request ) {
	if ( preg_match( '#^/wp/v2/global-styles/themes/[^/]+/variations$#', $request->get_route() ) ) {
		return rest_ensure_response( array() );
	}
	return $response;
}
add_filter( 'rest_request_after_callbacks', 'islamicdilemma_no_style_variations', 10, 3 );

/**
 * Tell browsers the page is light so form controls and scrollbars never
 * switch to dark rendering.
 */
function islamicdilemma_color_scheme_meta() {
	echo '<meta name="color-scheme" content="light">' . "\n";
}
add_action( 'wp_head', 'islamicdilemma_color_scheme_meta', 1 );
