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
printf(
	'<span %s>%s</span>',
	get_block_wrapper_attributes( array( 'class' => 'reading-time' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html( islamicdilemma_reading_label( $islamicdilemma_post ) )
);
