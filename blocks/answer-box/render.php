<?php
/**
 * Key points box: the post's key points near the top, one list item each.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$islamicdilemma_post   = get_post( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0 );
$islamicdilemma_points = $islamicdilemma_post ? islamicdilemma_key_points( $islamicdilemma_post ) : array();
if ( ! $islamicdilemma_points ) {
	return;
}
$islamicdilemma_title = 'answer-box-title-' . $islamicdilemma_post->ID;
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'answer-box', 'aria-labelledby' => $islamicdilemma_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="answer-box-title" id="<?php echo esc_attr( $islamicdilemma_title ); ?>"><?php esc_html_e( 'Key points', 'islamicdilemma' ); ?></h2>
	<ul class="answer-box-points">
		<?php foreach ( $islamicdilemma_points as $islamicdilemma_point ) : ?>
		<li><?php echo esc_html( $islamicdilemma_point ); ?></li>
		<?php endforeach; ?>
	</ul>
</section>
