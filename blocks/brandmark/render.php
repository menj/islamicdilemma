<?php
/**
 * Brandmark: the site's mark as a small image.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$islamicdilemma_size = max( 24, min( 160, (int) ( $attributes['size'] ?? 56 ) ) );
?>
<img <?php echo get_block_wrapper_attributes( array( 'class' => 'brandmark' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( get_theme_file_uri( 'assets/brand/brandmark.svg' ) ); ?>" width="<?php echo esc_attr( $islamicdilemma_size ); ?>" height="<?php echo esc_attr( $islamicdilemma_size ); ?>" alt="" decoding="async">
