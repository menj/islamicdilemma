<?php
/**
 * Term entry: the glossary entry of the tag being viewed.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) || ! is_tag() ) {
	return;
}
$islamicdilemma_entry = islamicdilemma_term_entry( get_queried_object() );
if ( empty( $islamicdilemma_entry ) || ( '' === $islamicdilemma_entry['definition'] && '' === $islamicdilemma_entry['arabic'] ) ) {
	return;
}
$islamicdilemma_types = islamicdilemma_entry_types();
$islamicdilemma_type  = $islamicdilemma_types[ $islamicdilemma_entry['entry_type'] ] ?? $islamicdilemma_types['topic'];
?>
<aside <?php echo get_block_wrapper_attributes( array( 'class' => 'term-entry', 'aria-label' => __( 'Glossary entry', 'islamicdilemma' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<p class="term-entry-meta">
		<span><?php echo esc_html( $islamicdilemma_type ); ?></span>
		<?php if ( $islamicdilemma_entry['category'] ) : ?>
			<span><?php echo esc_html( $islamicdilemma_entry['category'] ); ?></span>
		<?php endif; ?>
	</p>
	<?php if ( $islamicdilemma_entry['arabic'] ) : ?>
		<p class="term-entry-arabic ar" lang="ar" dir="rtl"><?php echo esc_html( $islamicdilemma_entry['arabic'] ); ?></p>
	<?php endif; ?>
	<?php if ( $islamicdilemma_entry['definition'] ) : ?>
		<p class="term-entry-definition"><?php echo islamicdilemma_entry_definition_html( $islamicdilemma_entry['definition'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php endif; ?>
	<?php if ( $islamicdilemma_entry['related'] ) : ?>
		<p class="term-entry-related">
			<span><?php esc_html_e( 'See also', 'islamicdilemma' ); ?></span>
			<?php foreach ( $islamicdilemma_entry['related'] as $islamicdilemma_related ) : ?>
				<a href="<?php echo esc_url( get_term_link( $islamicdilemma_related ) ); ?>"><?php echo esc_html( $islamicdilemma_related->name ); ?></a>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
	<p class="term-entry-link"><a href="<?php echo esc_url( home_url( '/glossary/' ) ); ?>"><?php esc_html_e( 'Full glossary', 'islamicdilemma' ); ?></a></p>
</aside>
