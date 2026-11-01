<?php
/**
 * Byline: the author's photo and name, linked to the About page.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! islamicdilemma_has_author() || is_page( array( 'about', 'sitemap', 'book' ) ) ) {
	return;
}
$islamicdilemma_author = islamicdilemma_author();
$islamicdilemma_photo  = islamicdilemma_author_photo_url();
printf(
	'<span %1$s>%2$s<span>%3$s <a href="%4$s" rel="author">%5$s</a></span></span>',
	get_block_wrapper_attributes( array( 'class' => 'byline' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$islamicdilemma_photo ? sprintf( '<img src="%s" alt="" width="32" height="32" class="byline-photo" loading="lazy" decoding="async">', esc_url( $islamicdilemma_photo ) ) : '',
	esc_html__( 'By', 'islamicdilemma' ),
	esc_url( islamicdilemma_author_url() ),
	esc_html( $islamicdilemma_author['name'] )
);
