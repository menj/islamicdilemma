<?php
/**
 * Reading time for the post in context.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$islamicdilemma_post = get_post( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0 );
if ( ! $islamicdilemma_post ) {
	return;
}
$islamicdilemma_words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $islamicdilemma_post->post_content ) ) );
$islamicdilemma_minutes = max( 1, (int) round( $islamicdilemma_words / 200 ) );
printf(
	'<span %s>%s</span>',
	get_block_wrapper_attributes( array( 'class' => 'reading-time' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	/* translators: %d: minutes of reading. */
	esc_html( sprintf( _n( '%d min read', '%d min read', $islamicdilemma_minutes, 'islamicdilemma' ), $islamicdilemma_minutes ) )
);
