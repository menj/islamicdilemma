<?php
/**
 * The markup of the theme's error pages, as plain PHP with no WordPress calls.
 *
 * error-pages.php uses it to write the three drop-ins into wp-content, and
 * tools/build/error_pages.php uses it to build the static pages in errors/.
 * Each page carries its stylesheet inline (assets/css/error-page.css), so it
 * needs no other file. The fonts come from the theme folder on the same
 * server; while that folder is missing (half way through an update) the
 * browser falls back to Georgia and system fonts. Nothing is fetched from
 * another host.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) && 'cli' !== PHP_SAPI ) {
	exit;
}

/** The marker every file the theme writes into wp-content starts with. */
const ISLAMICDILEMMA_ERROR_MARKER = 'Written by the Islamic Dilemma theme; safe to delete.';

/**
 * The text of each error page, keyed by name.
 *
 * Each entry: the label above the heading, the heading (also the first part
 * of the title), the lines of text and the label of the main link. A main
 * link with an empty target reloads the page.
 *
 * @return array<string,array{label:string,title:string,lines:string[],action:string,target:string}>
 */
function islamicdilemma_error_page_texts() {
	$again = 'Please try again in a few minutes.';
	return array(
		'403'         => array(
			'label'  => 'Error 403',
			'title'  => 'Access denied',
			'lines'  => array( 'This address is closed to visitors.', 'Every page of the site can be reached from the home page.' ),
			'action' => 'Go to the home page',
			'target' => 'home',
		),
		'500'         => array(
			'label'  => 'Error 500',
			'title'  => 'Something went wrong',
			'lines'  => array( 'A fault on the server stopped this page. Nothing you did caused it.', $again ),
			'action' => 'Try again',
			'target' => '',
		),
		'502'         => array(
			'label'  => 'Error 502',
			'title'  => 'Bad gateway',
			'lines'  => array( 'A server between you and the site sent back a broken answer.', $again ),
			'action' => 'Try again',
			'target' => '',
		),
		'503'         => array(
			'label'  => 'Error 503',
			'title'  => 'Back shortly',
			'lines'  => array( 'The site is busy or offline for a short while.', $again ),
			'action' => 'Try again',
			'target' => '',
		),
		'504'         => array(
			'label'  => 'Error 504',
			'title'  => 'The server took too long',
			'lines'  => array( 'A server between you and the site waited too long for an answer.', $again ),
			'action' => 'Try again',
			'target' => '',
		),
		'maintenance' => array(
			'label'  => 'Updating',
			'title'  => 'Back shortly',
			'lines'  => array( 'The site is being updated. That takes a minute or two.', 'Please reload this page in a minute or two.' ),
			'action' => 'Try again',
			'target' => '',
		),
		'updating'    => array(
			'label'  => 'Updating',
			'title'  => 'Back shortly',
			'lines'  => array( 'This page caught the site half way through an update.', 'Please reload it in a minute or two.' ),
			'action' => 'Try again',
			'target' => '',
		),
		'db-error'    => array(
			'label'  => 'Error 503',
			'title'  => 'Database unavailable',
			'lines'  => array( 'Every page of the site is stored in its database, and the server cannot reach it right now.', $again ),
			'action' => 'Try again',
			'target' => '',
		),
	);
}

/**
 * The stylesheet, with its font addresses pointed at the theme's fonts folder.
 *
 * @param string $theme_dir  The theme folder on disk.
 * @param string $fonts_path The fonts folder as a URL path, with a trailing slash.
 * @return string
 */
function islamicdilemma_error_page_css( $theme_dir, $fonts_path ) {
	$css = (string) @file_get_contents( rtrim( $theme_dir, '/' ) . '/assets/css/error-page.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
	$css = (string) preg_replace( '~/\*.*?\*/\s*~s', '', $css );
	return trim( str_replace( 'url("../fonts/', 'url("' . $fonts_path, $css ) );
}

/**
 * The 32px icon as a link with a data address. An error page answers at any
 * path, in a subfolder install too, and while WordPress or PHP is down, so it
 * carries the icon inside the page and asks the server for nothing.
 *
 * @return string The link tag and a newline, or '' when the file is missing.
 */
function islamicdilemma_error_page_icon() {
	$png = @file_get_contents( dirname( __DIR__, 2 ) . '/assets/brand/favicon-32.png' ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
	return $png ? '<link rel="icon" href="data:image/png;base64,' . base64_encode( $png ) . "\" sizes=\"32x32\" type=\"image/png\">\n" : ''; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/**
 * One error page as a complete HTML document.
 *
 * @param string $key  A key of islamicdilemma_error_page_texts().
 * @param array  $site site_name, home_url and css.
 * @return string
 */
function islamicdilemma_error_page_html( $key, array $site ) {
	$texts = islamicdilemma_error_page_texts();
	$page  = $texts[ $key ];
	$esc   = static function ( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	};
	$home  = $esc( $site['home_url'] );
	$name  = $esc( $site['site_name'] );
	$lines = '';
	foreach ( $page['lines'] as $line ) {
		$lines .= '<p>' . $esc( $line ) . "</p>\n";
	}
	$main_href = 'home' === $page['target'] ? $home : '';
	$second    = 'home' === $page['target'] ? '' : ' <a class="link" href="' . $home . '">Go to the home page</a>';
	return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"utf-8\">\n"
		. "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
		. "<meta name=\"robots\" content=\"noindex\">\n"
		. '<title>' . $esc( $page['title'] ) . ' | ' . $name . "</title>\n"
		. islamicdilemma_error_page_icon()
		. '<style>' . $site['css'] . "</style>\n</head>\n<body>\n"
		. "<header class=\"band\"><a class=\"site\" href=\"" . $home . '">' . $name . "</a></header>\n"
		. "<main class=\"card\">\n"
		. '<p class="label">' . $esc( $page['label'] ) . "</p>\n"
		. '<h1>' . $esc( $page['title'] ) . "</h1>\n"
		. $lines
		. '<p class="actions"><a class="button" href="' . $main_href . '">' . $esc( $page['action'] ) . '</a>' . $second . "</p>\n"
		. "</main>\n</body>\n</html>\n";
}
