<?php
/**
 * The site's author, from author.json in the theme.
 *
 * One person writes the whole site, so the byline, the author box and the
 * structured data all read from this one file rather than from WordPress
 * user profiles. Nothing author-related is shown until a name is set.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The author's details, with defaults filled in.
 *
 * @return array
 */
function islamicdilemma_author() {
	static $author = null;
	if ( null !== $author ) {
		return $author;
	}
	$path = get_stylesheet_directory() . '/author.json';
	$data = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$data = is_array( $data ) ? $data : array();
	unset( $data['_note'] );
	$author = (array) apply_filters(
		'islamicdilemma_author',
		wp_parse_args(
			$data,
			array(
				'name'             => '',
				'alternate_name'   => '',
				'honorific_prefix' => '',
				'job_title'        => '',
				'bio'              => '',
				'bio_long'         => '',
				'photo'            => '',
				'photo_alt'        => '',
				'knows_about'      => array(),
				'knows_language'   => array(),
				'nationality'      => '',
				'home_location'    => '',
				'same_as'          => array(),
				'email'            => '',
			)
		)
	);
	return $author;
}

/**
 * Whether the author has been set up.
 *
 * @return bool
 */
function islamicdilemma_has_author() {
	return '' !== trim( (string) islamicdilemma_author()['name'] );
}

/**
 * URL of the author's photo, or '' if there is none.
 *
 * @return string
 */
function islamicdilemma_author_photo_url() {
	$photo = (string) islamicdilemma_author()['photo'];
	if ( '' === $photo ) {
		return '';
	}
	if ( preg_match( '#^https?://#', $photo ) ) {
		return $photo;
	}
	return is_readable( get_stylesheet_directory() . '/' . $photo ) ? get_stylesheet_directory_uri() . '/' . ltrim( $photo, '/' ) : '';
}

/**
 * The page that describes the author: the About page, at #author.
 *
 * @return string
 */
function islamicdilemma_author_url() {
	$about = get_page_by_path( 'about' );
	return ( $about ? get_permalink( $about ) : home_url( '/about/' ) ) . '#author';
}

/**
 * Author archives would show a WordPress username; send them to the About page instead.
 */
function islamicdilemma_redirect_author_archives() {
	if ( is_author() ) {
		wp_safe_redirect( islamicdilemma_author_url(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'islamicdilemma_redirect_author_archives' );

/**
 * Keep usernames out of the XML sitemap.
 *
 * @param WP_Sitemaps_Provider $provider Provider.
 * @param string               $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function islamicdilemma_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'islamicdilemma_sitemap_providers', 10, 2 );

/**
 * The feed author is the site's author, not a username.
 *
 * @param string $name Display name.
 * @return string
 */
function islamicdilemma_feed_author( $name ) {
	return is_feed() && islamicdilemma_has_author() ? islamicdilemma_author()['name'] : $name;
}
add_filter( 'the_author', 'islamicdilemma_feed_author' );

/**
 * Remind administrators to fill in author.json.
 */
function islamicdilemma_author_notice() {
	if ( islamicdilemma_has_author() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && ! in_array( $screen->id, array( 'dashboard', 'themes', 'appearance_page_islamicdilemma-content' ), true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Islamic Dilemma theme: add the author’s name and bio to author.json in the theme folder. Bylines, the author box and author structured data appear once a name is set.', 'islamicdilemma' )
	);
}
add_action( 'admin_notices', 'islamicdilemma_author_notice' );
