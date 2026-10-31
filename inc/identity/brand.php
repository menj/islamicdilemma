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
 * Print the theme's icon links when no Site Icon has been set.
 */
function islamicdilemma_brand_icons() {
	if ( has_site_icon() ) {
		return;
	}
	$base = get_stylesheet_directory_uri() . '/assets/brand/';
	printf( '<link rel="icon" href="%s" sizes="48x48">' . "\n", esc_url( $base . 'favicon.ico' ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $base . 'favicon.svg' ) );
	printf( '<link rel="icon" href="%s" type="image/png" sizes="96x96">' . "\n", esc_url( $base . 'favicon-96.png' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $base . 'apple-touch-icon.png' ) );
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( $base . 'site.webmanifest' ) );
	echo '<meta name="theme-color" content="#18283C">' . "\n";
}
add_action( 'wp_head', 'islamicdilemma_brand_icons', 2 );

/**
 * Serve the theme's favicon.ico at /favicon.ico when no Site Icon has been set.
 */
function islamicdilemma_brand_faviconico() {
	if ( has_site_icon() ) {
		return;
	}
	header( 'Content-Type: image/vnd.microsoft.icon' );
	header( 'Cache-Control: public, max-age=604800' );
	readfile( get_stylesheet_directory() . '/assets/brand/favicon.ico' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action( 'do_faviconico', 'islamicdilemma_brand_faviconico', 1 );
