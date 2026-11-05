<?php
/**
 * Islamic Dilemma child theme for Twenty Twenty-Five.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ISLAMICDILEMMA_VERSION', wp_get_theme( 'islamicdilemma' )->get( 'Version' ) );

/** The study on which the site is based. */
define( 'ISLAMICDILEMMA_PAPER_URL', 'https://www.academia.edu/166160379/Reconsidering_The_Islamic_Dilemma' );

// Every file the theme loads. The owner uploads over FTP one file at a time,
// so for a while one can be missing, or there but half written. Every file
// is checked before any loads, and they load inside a try: either case gives
// visitors a 503 "Back shortly" page instead of a fatal error
// (islamicdilemma_incomplete_theme()). This file itself is parsed before any
// of it runs, so it cannot guard itself.
$islamicdilemma_files = array(
	'/inc/presentation/light-only.php',
	'/inc/editor/block-styles.php',
	'/inc/presentation/render-filters.php',
	'/inc/content/shortcodes.php',
	'/inc/content/content-sync.php',
	'/inc/content/content-admin.php',
	'/inc/identity/author.php',
	'/inc/seo/seo.php',
	'/inc/identity/book.php',
	'/inc/identity/brand.php',
	'/inc/presentation/login.php',
	'/inc/presentation/error-pages.php',
	'/inc/content/term-entries.php',
	'/inc/content/term-images.php',
	'/inc/content/tools.php',
	'/inc/security/hardening.php',
);

/**
 * The theme caught half uploaded: a 503 with Retry-After instead of a fatal
 * error, and one line in the error log per problem and ten minutes, not one
 * per request. The login screen, AJAX, cron and the command line go on, and
 * the admin gets the notice only when a file broke part-way through loading.
 *
 * @param string $what What is wrong, for the log.
 * @param bool   $all  Send the notice to the admin screens too.
 */
function islamicdilemma_incomplete_theme( $what, $all = false ) {
	$key = 'islamicdilemma_incomplete_' . md5( $what );
	if ( ! get_transient( $key ) ) {
		set_transient( $key, 1, 10 * MINUTE_IN_SECONDS );
		error_log( 'Islamic Dilemma: the theme is incomplete or being uploaded (' . $what . '); visitors see the maintenance notice until it is whole.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
	// The login screen by its path, not a query that happens to hold the word.
	$path  = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$login = 'wp-login.php' === ( $GLOBALS['pagenow'] ?? '' ) || 'wp-login.php' === basename( $path );
	if ( wp_doing_cron() && get_option( 'islamicdilemma_attach_waiting' ) && ! wp_next_scheduled( 'islamicdilemma_attach_batch' ) ) {
		// The image batch could not run while the theme was incomplete: try
		// again once the upload has had time to finish.
		wp_schedule_single_event( time() + 300, 'islamicdilemma_attach_batch' );
	}
	if ( wp_doing_ajax() || wp_doing_cron() || 'cli' === PHP_SAPI || $login || ( is_admin() && ! $all ) ) {
		return;
	}
	if ( ! headers_sent() ) {
		header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Retry-After: 300' );
		header( 'Cache-Control: no-store, max-age=0' );
		header( 'X-Robots-Tag: noindex' );
	}
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Back shortly</title></head><body><h1>Back shortly</h1><p>The site is being updated. Please try again in a few minutes.</p></body></html>';
	exit;
}

foreach ( $islamicdilemma_files as $islamicdilemma_file ) {
	if ( ! is_readable( get_stylesheet_directory() . $islamicdilemma_file ) ) {
		islamicdilemma_incomplete_theme( ltrim( $islamicdilemma_file, '/' ) . ' is missing' );
		return;
	}
}
try {
	foreach ( $islamicdilemma_files as $islamicdilemma_file ) {
		require_once get_stylesheet_directory() . $islamicdilemma_file;
	}
} catch ( Throwable $islamicdilemma_error ) {
	islamicdilemma_incomplete_theme( $islamicdilemma_error->getMessage() . ' in ' . basename( $islamicdilemma_error->getFile() ), true );
	return;
}
unset( $islamicdilemma_files, $islamicdilemma_file );

/**
 * Theme setup.
 */
function islamicdilemma_setup() {
	load_child_theme_textdomain( 'islamicdilemma', get_stylesheet_directory() . '/languages' );

	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/theme.css', 'assets/css/editor.css' ) );

	// Pages get a short deck under the title, taken from the excerpt.
	add_post_type_support( 'page', 'excerpt' );

	// The parent registers every post format; this site uses standard posts only.
	remove_theme_support( 'post-formats' );

	// Core's bundled patterns don't fit this design.
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'islamicdilemma_setup', 11 );

/**
 * Asset version: the file's modification time, so edits bust caches.
 *
 * @param string $relative Path inside the theme.
 * @return string
 */
function islamicdilemma_asset_version( $relative ) {
	$path = get_stylesheet_directory() . '/' . $relative;
	return file_exists( $path ) ? (string) filemtime( $path ) : ISLAMICDILEMMA_VERSION;
}

/**
 * Front-end styles and scripts.
 */
function islamicdilemma_enqueue_assets() {
	wp_enqueue_style(
		'islamicdilemma-theme',
		get_stylesheet_directory_uri() . '/assets/css/theme.css',
		array(),
		islamicdilemma_asset_version( 'assets/css/theme.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'islamicdilemma_enqueue_assets', 20 );

/**
 * Editor-only script: the Qur'an reference format and the search toggle block preview.
 */
function islamicdilemma_editor_assets() {
	wp_enqueue_script(
		'islamicdilemma-editor',
		get_stylesheet_directory_uri() . '/assets/js/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-rich-text', 'wp-block-editor', 'wp-i18n' ),
		islamicdilemma_asset_version( 'assets/js/editor.js' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'islamicdilemma_editor_assets' );

/**
 * Register the theme's blocks: search overlay, reading time, result type, author byline and author box.
 */
function islamicdilemma_register_blocks() {
	foreach ( array( 'search-toggle', 'reading-time', 'result-type', 'author-byline', 'author-box', 'shahada', 'term-entry', 'brandmark' ) as $block ) {
		register_block_type( get_stylesheet_directory() . '/blocks/' . $block );
	}
}
add_action( 'init', 'islamicdilemma_register_blocks' );

/**
 * Pattern categories, and removal of the parent theme's patterns so editors
 * only see patterns built for this design.
 */
function islamicdilemma_patterns() {
	register_block_pattern_category(
		'islamicdilemma',
		array(
			'label'       => __( 'Islamic Dilemma', 'islamicdilemma' ),
			'description' => __( 'Sections built for islamicdilemma.info.', 'islamicdilemma' ),
		)
	);

	$registry = WP_Block_Patterns_Registry::get_instance();
	foreach ( $registry->get_all_registered() as $pattern ) {
		if ( 0 === strpos( $pattern['name'], 'twentytwentyfive/' ) ) {
			unregister_block_pattern( $pattern['name'] );
		}
	}
}
add_action( 'init', 'islamicdilemma_patterns', 20 );

// Core's remote pattern directory suggestions don't fit this design either.
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Shorter, cleaner excerpts for cards and search results.
 *
 * @return int
 */
function islamicdilemma_excerpt_length() {
	return 32;
}
add_filter( 'excerpt_length', 'islamicdilemma_excerpt_length' );

/**
 * Replace the default "[…]" excerpt ending with a plain ellipsis.
 *
 * @return string
 */
function islamicdilemma_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'islamicdilemma_excerpt_more' );
