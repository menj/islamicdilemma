<?php
/**
 * Custom login screen in the site's colours, type and brandmark.
 *
 * @package islamicdilemma
 */

/**
 * Load the login stylesheet.
 */
function islamicdilemma_login_assets() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'islamicdilemma-login', get_stylesheet_directory_uri() . '/assets/css/login.css', array( 'login' ), $version );
}
add_action( 'login_enqueue_scripts', 'islamicdilemma_login_assets' );

/**
 * The logo links to the site, and its text is the site title.
 *
 * @return string
 */
function islamicdilemma_login_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'islamicdilemma_login_url' );

/**
 * Logo text.
 *
 * @return string
 */
function islamicdilemma_login_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'islamicdilemma_login_text' );

/**
 * The basmala above the form, and the theme's icons in the head.
 */
function islamicdilemma_login_basmala() {
	echo '<p class="login-basmala" lang="ar" dir="rtl">بِسْمِ ٱللَّهِ ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ</p>' . "\n";
}
add_action( 'login_header', 'islamicdilemma_login_basmala' );
add_action( 'login_head', 'islamicdilemma_brand_icons' );
