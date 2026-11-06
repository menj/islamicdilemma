<?php
/**
 * Next step after the author box: keep reading, check the sources, ask a question.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$islamicdilemma_post = get_post( isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0 );
if ( ! $islamicdilemma_post || is_front_page() || is_page( array( 'sitemap', 'book' ) ) ) {
	return;
}
$islamicdilemma_steps = islamicdilemma_next_steps( $islamicdilemma_post );
if ( ! $islamicdilemma_steps ) {
	return;
}
$islamicdilemma_title = wp_unique_id( 'next-step-title-' . $islamicdilemma_post->ID . '-' );
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'next-step', 'aria-labelledby' => $islamicdilemma_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="next-step-title" id="<?php echo esc_attr( $islamicdilemma_title ); ?>"><?php esc_html_e( 'Next step', 'islamicdilemma' ); ?></h2>
	<ul class="next-step-list">
		<?php foreach ( $islamicdilemma_steps as $islamicdilemma_kind => $islamicdilemma_step ) : ?>
		<li class="next-step-<?php echo esc_attr( $islamicdilemma_kind ); ?>"><a href="<?php echo esc_url( $islamicdilemma_step[2] ); ?>"><span class="next-step-kind"><?php echo esc_html( $islamicdilemma_step[0] ); ?></span> <span class="next-step-target"><?php echo esc_html( $islamicdilemma_step[1] ); ?></span></a></li>
		<?php endforeach; ?>
	</ul>
</nav>
