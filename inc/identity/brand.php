<?php
/**
 * Brandmark, favicon and app icons.
 *
 * The theme ships its own icon set in assets/brand. A Site Icon chosen in
 * Settings → General (or the Site Editor) takes precedence over it.
 *
 * @package islamicdilemma
 */

/**
 * Print the theme's icon links when no Site Icon has been set. On the site,
 * the login screen (hooked in inc/presentation/login.php) and the admin.
 */
function islamicdilemma_brand_icons() {
	if ( has_site_icon() ) {
		return;
	}
	$base = get_stylesheet_directory_uri() . '/assets/brand/';
	printf( '<link rel="icon" href="%s" sizes="48x48">' . "\n", esc_url( $base . 'favicon.ico' ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $base . 'favicon.svg' ) );
	printf( '<link rel="icon" href="%s" type="image/png" sizes="32x32">' . "\n", esc_url( $base . 'favicon-32.png' ) );
	printf( '<link rel="icon" href="%s" type="image/png" sizes="96x96">' . "\n", esc_url( $base . 'favicon-96.png' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $base . 'apple-touch-icon.png' ) );
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( $base . 'site.webmanifest' ) );
	echo '<meta name="theme-color" content="#18283C">' . "\n";
}
add_action( 'wp_head', 'islamicdilemma_brand_icons', 2 );
add_action( 'admin_head', 'islamicdilemma_brand_icons' );

/**
 * Serve the theme's favicon.ico at /favicon.ico when no Site Icon has been set.
 * The address is fixed, so the answer carries a week's cache and an ETag, and
 * a browser holding the same file gets a 304. While the file is missing (an
 * upload still running), or with a Site Icon, WordPress answers as it would
 * without the theme.
 */
function islamicdilemma_brand_faviconico() {
	$file = get_stylesheet_directory() . '/assets/brand/favicon.ico';
	if ( has_site_icon() || ! is_readable( $file ) ) {
		return;
	}
	$etag = '"' . md5_file( $file ) . '"';
	header( 'Content-Type: image/vnd.microsoft.icon' );
	header( 'Cache-Control: public, max-age=604800' );
	header( 'ETag: ' . $etag );
	// If-None-Match may list several validators, weak ones (W/"...") or *.
	$sent = array_map(
		static function ( $v ) {
			return preg_replace( '#^W/#', '', trim( $v ) );
		},
		explode( ',', wp_unslash( (string) ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? '' ) ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	);
	if ( in_array( '*', $sent, true ) || in_array( $etag, $sent, true ) ) {
		status_header( 304 );
		exit;
	}
	status_header( 200 );
	header( 'Content-Length: ' . (string) filesize( $file ) );
	// A HEAD request gets the headers alone.
	if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	}
	exit;
}
add_action( 'do_faviconico', 'islamicdilemma_brand_faviconico', 1 );

/**
 * WordPress ends a HEAD request before do_favicon, with an empty HTML answer,
 * and skips its Site Icon redirect then. template_redirect runs first, so
 * this answers every method: the icon, or the redirect to the Site Icon.
 */
function islamicdilemma_brand_favicon_early() {
	if ( ! is_favicon() ) {
		return;
	}
	if ( has_site_icon() ) {
		wp_redirect( get_site_icon_url( 32 ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- core's own redirect; the icon may sit on a CDN.
		exit;
	}
	islamicdilemma_brand_faviconico();
}
add_action( 'template_redirect', 'islamicdilemma_brand_favicon_early', -1 );
