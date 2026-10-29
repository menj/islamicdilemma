<?php
/**
 * Small render-time adjustments to core blocks.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paragraphs with the "Arabic verse" style get lang and dir attributes, so
 * screen readers pronounce them as Arabic and the text runs right to left.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function islamicdilemma_arabic_paragraph( $content, $block ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( false === strpos( $class, 'is-style-arabic' ) ) {
		return $content;
	}
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( 'p' ) ) {
		$tags->set_attribute( 'lang', 'ar' );
		$tags->set_attribute( 'dir', 'rtl' );
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/paragraph', 'islamicdilemma_arabic_paragraph', 10, 2 );

/**
 * Show a trailing ".info" in the site title in gold.
 *
 * @param string $content Rendered block.
 * @return string
 */
function islamicdilemma_site_title_suffix( $content ) {
	return preg_replace( '#(>[^<]*?)(\.info)(</a>|</p>|</h1>)#', '$1<span class="site-title-suffix">$2</span>$3', $content, 1 );
}
add_filter( 'render_block_core/site-title', 'islamicdilemma_site_title_suffix' );

/**
 * Path of the current request, without the home path or trailing slash.
 *
 * @return string
 */
function islamicdilemma_current_section() {
	if ( is_front_page() ) {
		return '';
	}
	// Posts, their categories and tags all belong to Enrichment, the posts page.
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		return $posts_page ? untrailingslashit( wp_make_link_relative( get_permalink( $posts_page ) ) ) : '';
	}
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	return untrailingslashit( (string) $path );
}

/**
 * Mark the menu link for the current section with aria-current="page".
 * Links in the header are plain URLs, so core can't tell which is current.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function islamicdilemma_navigation_current( $content, $block ) {
	if ( empty( $block['attrs']['url'] ) ) {
		return $content;
	}
	$link    = untrailingslashit( (string) wp_parse_url( $block['attrs']['url'], PHP_URL_PATH ) );
	$current = islamicdilemma_current_section();
	$is_home = ( '' === $link || '/' === $link );

	if ( ( $is_home && is_front_page() ) || ( ! $is_home && '' !== $current && 0 === strpos( $current . '/', $link . '/' ) ) ) {
		$tags = new WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( 'li' ) ) {
			$tags->add_class( 'current-menu-item' );
		}
		if ( $tags->next_tag( 'a' ) ) {
			$tags->set_attribute( 'aria-current', 'page' );
		}
		return $tags->get_updated_html();
	}
	return $content;
}
add_filter( 'render_block_core/navigation-link', 'islamicdilemma_navigation_current', 10, 2 );

/**
 * Give every second-level heading in post content an id, so the
 * "On this page" list can link to it. Headings with an anchor keep it.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function islamicdilemma_heading_anchor( $content, $block ) {
	$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
	if ( 2 !== $level ) {
		return $content;
	}
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( 'h2' ) && ! $tags->get_attribute( 'id' ) ) {
		$slug = sanitize_title( wp_strip_all_tags( $content ) );
		if ( '' !== $slug ) {
			$tags->set_attribute( 'id', $slug );
			return $tags->get_updated_html();
		}
	}
	return $content;
}
add_filter( 'render_block_core/heading', 'islamicdilemma_heading_anchor', 10, 2 );
