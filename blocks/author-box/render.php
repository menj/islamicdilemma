<?php
/**
 * Author box: a short card after articles, or the full profile on the About page.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! islamicdilemma_has_author() || ( is_page( array( 'about', 'sitemap', 'book' ) ) && 'full' !== ( $attributes['variant'] ?? 'card' ) ) ) {
	return;
}
$islamicdilemma_author = islamicdilemma_author();
$islamicdilemma_full   = 'full' === ( $attributes['variant'] ?? 'card' );
$islamicdilemma_photo  = islamicdilemma_author_photo_url();
$islamicdilemma_name   = trim( $islamicdilemma_author['honorific_prefix'] . ' ' . $islamicdilemma_author['name'] );
$islamicdilemma_bio    = $islamicdilemma_full && $islamicdilemma_author['bio_long'] ? $islamicdilemma_author['bio_long'] : $islamicdilemma_author['bio'];
$islamicdilemma_links  = array();
if ( $islamicdilemma_full ) {
	foreach ( (array) $islamicdilemma_author['same_as'] as $islamicdilemma_url ) {
		$islamicdilemma_host = wp_parse_url( $islamicdilemma_url, PHP_URL_HOST );
		if ( $islamicdilemma_host ) {
			$islamicdilemma_links[] = sprintf( '<a href="%s" rel="me noopener">%s</a>', esc_url( $islamicdilemma_url ), esc_html( preg_replace( '/^www\./', '', $islamicdilemma_host ) ) );
		}
	}
}
?>
<?php
$islamicdilemma_wrapper = array(
	'class'      => 'author-box' . ( $islamicdilemma_full ? ' is-full' : '' ),
	'aria-label' => __( 'About the author', 'islamicdilemma' ),
);
if ( $islamicdilemma_full ) {
	$islamicdilemma_wrapper['id'] = 'author';
}
?>
<aside <?php echo get_block_wrapper_attributes( $islamicdilemma_wrapper ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $islamicdilemma_photo ) : ?>
		<img class="author-photo" src="<?php echo esc_url( $islamicdilemma_photo ); ?>" alt="<?php echo esc_attr( $islamicdilemma_author['photo_alt'] ? $islamicdilemma_author['photo_alt'] : $islamicdilemma_author['name'] ); ?>" width="<?php echo $islamicdilemma_full ? 160 : 88; ?>" height="<?php echo $islamicdilemma_full ? 160 : 88; ?>" loading="lazy" decoding="async">
	<?php endif; ?>
	<div class="author-text">
		<p class="author-eyebrow"><?php echo esc_html( $islamicdilemma_full ? __( 'About the author', 'islamicdilemma' ) : __( 'Written by', 'islamicdilemma' ) ); ?></p>
		<p class="author-name"><?php echo esc_html( $islamicdilemma_name ); ?></p>
		<?php if ( $islamicdilemma_author['job_title'] ) : ?>
			<p class="author-role"><?php echo esc_html( $islamicdilemma_author['job_title'] ); ?></p>
		<?php endif; ?>
		<?php if ( $islamicdilemma_bio ) : ?>
			<?php echo wp_kses_post( wpautop( $islamicdilemma_bio ) ); ?>
		<?php endif; ?>
		<?php if ( $islamicdilemma_links ) : ?>
			<p class="author-links"><?php echo implode( ' · ', $islamicdilemma_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php elseif ( ! $islamicdilemma_full ) : ?>
			<p class="author-links"><a href="<?php echo esc_url( islamicdilemma_author_url() ); ?>"><?php esc_html_e( 'More about the author and how this site is written', 'islamicdilemma' ); ?></a></p>
		<?php endif; ?>
	</div>
</aside>
