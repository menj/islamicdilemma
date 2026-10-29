<?php
/**
 * Search toggle and overlay.
 *
 * The toggle is a link to the search page, so search still works without
 * JavaScript. view.js turns it into a button that opens the overlay.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$islamicdilemma_topics = array(
	__( 'Injil', 'islamicdilemma' ),
	__( 'Q 5:47', 'islamicdilemma' ),
	__( 'Taḥrīf', 'islamicdilemma' ),
	__( 'Preservation', 'islamicdilemma' ),
	__( 'Q 10:94', 'islamicdilemma' ),
);
$islamicdilemma_icon = '<svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.5 15.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'search-toggle-wrap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<a class="search-toggle" href="<?php echo esc_url( add_query_arg( 's', '', home_url( '/' ) ) ); ?>" aria-controls="islamicdilemma-search" aria-haspopup="dialog">
		<?php echo $islamicdilemma_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'islamicdilemma' ); ?></span>
	</a>
	<dialog id="islamicdilemma-search" class="search-overlay" aria-labelledby="islamicdilemma-search-title">
		<div class="search-panel">
			<div class="search-head">
				<p class="search-eyebrow"><?php esc_html_e( 'Search the site', 'islamicdilemma' ); ?></p>
				<h2 id="islamicdilemma-search-title" class="search-title"><?php esc_html_e( 'What are you looking for?', 'islamicdilemma' ); ?></h2>
				<button type="button" class="search-close">
					<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<span class="screen-reader-text"><?php esc_html_e( 'Close search', 'islamicdilemma' ); ?></span>
				</button>
			</div>
			<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="islamicdilemma-search-input"><?php esc_html_e( 'Search for', 'islamicdilemma' ); ?></label>
				<input type="search" id="islamicdilemma-search-input" class="search-input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search articles, arguments, and resources…', 'islamicdilemma' ); ?>" autocomplete="off" enterkeyhint="search">
				<button type="submit" class="search-submit">
					<?php echo $islamicdilemma_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Search', 'islamicdilemma' ); ?></span>
				</button>
			</form>
			<div class="search-topics">
				<p class="search-topics-label" id="islamicdilemma-search-topics"><?php esc_html_e( 'Common topics', 'islamicdilemma' ); ?></p>
				<ul aria-labelledby="islamicdilemma-search-topics">
					<?php foreach ( $islamicdilemma_topics as $islamicdilemma_topic ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $islamicdilemma_topic ), home_url( '/' ) ) ); ?>"><?php echo esc_html( $islamicdilemma_topic ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</dialog>
</div>
