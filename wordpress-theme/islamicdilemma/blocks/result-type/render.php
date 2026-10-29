<?php
/**
 * "Page", or the first category, for the post in context.
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
if ( 'page' === $islamicdilemma_post->post_type ) {
	$islamicdilemma_label = __( 'Page', 'islamicdilemma' );
} else {
	$islamicdilemma_cats  = get_the_category( $islamicdilemma_post->ID );
	$islamicdilemma_label = $islamicdilemma_cats ? $islamicdilemma_cats[0]->name : __( 'Enrichment', 'islamicdilemma' );
}
printf(
	'<span %s>%s</span>',
	get_block_wrapper_attributes( array( 'class' => 'result-type' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html( $islamicdilemma_label )
);
