<?php
/**
 * Reading progress bar, after the one in the injil.help theme.
 *
 * A thin gold bar fixed to the top of the window fills as the reader scrolls
 * an article or an inner page (never the front page). The bar is decorative
 * and carries aria-hidden: the browser's scroll bar already tells every
 * reader where they are, and a live progressbar would announce itself on
 * every scroll. The script shows the bar, so without JavaScript there is none.
 *
 * Filters: islamicdilemma_progress_enabled (bool, the view) switches it.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the bar belongs on this view.
 *
 * @return bool
 */
function islamicdilemma_progress_wanted() {
	$wanted = is_singular( array( 'post', 'page' ) ) && ! is_front_page() && ! is_page( array( 'sitemap', 'book' ) );
	return (bool) apply_filters( 'islamicdilemma_progress_enabled', $wanted );
}

/**
 * The bar's stylesheet and script.
 */
function islamicdilemma_progress_assets() {
	if ( ! islamicdilemma_progress_wanted() ) {
		return;
	}
	wp_enqueue_style( 'islamicdilemma-progress', get_stylesheet_directory_uri() . '/assets/css/progress.css', array( 'islamicdilemma-theme' ), islamicdilemma_asset_version( 'assets/css/progress.css' ) );
	wp_enqueue_script(
		'islamicdilemma-progress',
		get_stylesheet_directory_uri() . '/assets/js/progress.js',
		array(),
		islamicdilemma_asset_version( 'assets/js/progress.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'islamicdilemma_progress_assets', 21 );

/**
 * The bar itself, first in the body.
 */
function islamicdilemma_progress_bar() {
	if ( islamicdilemma_progress_wanted() ) {
		echo '<div class="id-progress" aria-hidden="true" hidden><span class="id-progress-fill"></span></div>' . "\n";
	}
}
add_action( 'wp_body_open', 'islamicdilemma_progress_bar' );
