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

require_once get_stylesheet_directory() . '/inc/presentation/light-only.php';
require_once get_stylesheet_directory() . '/inc/editor/block-styles.php';
require_once get_stylesheet_directory() . '/inc/presentation/render-filters.php';
require_once get_stylesheet_directory() . '/inc/content/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/content/content-sync.php';
require_once get_stylesheet_directory() . '/inc/content/content-admin.php';
require_once get_stylesheet_directory() . '/inc/identity/author.php';
require_once get_stylesheet_directory() . '/inc/seo/seo.php';
require_once get_stylesheet_directory() . '/inc/identity/book.php';
require_once get_stylesheet_directory() . '/inc/identity/brand.php';
require_once get_stylesheet_directory() . '/inc/presentation/login.php';
require_once get_stylesheet_directory() . '/inc/content/term-entries.php';
require_once get_stylesheet_directory() . '/inc/content/tools.php';

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
