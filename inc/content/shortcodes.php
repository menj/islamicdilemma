<?php
/**
 * Shortcodes used inside templates (through the Shortcode block) for parts
 * core blocks don't cover: breadcrumbs, an "On this page" list, reading time
 * and related reading.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The breadcrumb trail for the current view, as array( label, url ) pairs.
 * The last item, the current page, has an empty URL. Also used for the
 * BreadcrumbList structured data.
 *
 * @return array
 */
function islamicdilemma_breadcrumb_trail() {
	$trail      = array( array( __( 'Home', 'islamicdilemma' ), home_url( '/' ) ) );
	$posts_page = (int) get_option( 'page_for_posts' );
	$enrichment = $posts_page ? array( get_the_title( $posts_page ), get_permalink( $posts_page ) ) : null;

	if ( is_singular( 'post' ) ) {
		if ( $enrichment ) {
			$trail[] = $enrichment;
		}
		$cats = get_the_category();
		if ( $cats ) {
			$trail[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$trail[] = array( get_the_title(), '' );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor ) {
			$trail[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
		$trail[] = array( get_the_title(), '' );
	} elseif ( is_home() && $enrichment ) {
		$trail[] = array( $enrichment[0], '' );
	} elseif ( is_category() || is_tag() ) {
		if ( $enrichment ) {
			$trail[] = $enrichment;
		}
		$trail[] = array( single_term_title( '', false ), '' );
	} elseif ( is_search() ) {
		$trail[] = array( __( 'Search', 'islamicdilemma' ), '' );
	} else {
		return array();
	}
	return $trail;
}

/**
 * [islamicdilemma_breadcrumbs] Home / Enrichment / Category / Title.
 *
 * @return string
 */
function islamicdilemma_breadcrumbs() {
	$trail = islamicdilemma_breadcrumb_trail();
	if ( ! $trail ) {
		return '';
	}

	$items = array();
	foreach ( $trail as $crumb ) {
		$items[] = $crumb[1]
			? sprintf( '<a href="%s">%s</a>', esc_url( $crumb[1] ), esc_html( $crumb[0] ) )
			: sprintf( '<span aria-current="page">%s</span>', esc_html( $crumb[0] ) );
	}
	return sprintf(
		'<nav class="crumbs" aria-label="%s">%s</nav>',
		esc_attr__( 'Breadcrumb', 'islamicdilemma' ),
		implode( '<span class="crumbs-sep" aria-hidden="true">/</span>', $items )
	);
}
add_shortcode( 'islamicdilemma_breadcrumbs', 'islamicdilemma_breadcrumbs' );

/**
 * Run shortcodes when the Shortcode block renders, so ones used inside a
 * Query Loop (such as reading time) see that loop's post, not the page's.
 *
 * @param string $content Rendered block.
 * @return string
 */
function islamicdilemma_render_shortcode_block( $content ) {
	return do_shortcode( $content );
}
add_filter( 'render_block_core/shortcode', 'islamicdilemma_render_shortcode_block' );

/**
 * Collect level-two headings from parsed blocks, depth first.
 *
 * @param array $blocks Parsed blocks.
 * @param array $found  Headings found so far.
 * @return array List of array( id, text ).
 */
function islamicdilemma_collect_headings( $blocks, $found = array() ) {
	foreach ( $blocks as $block ) {
		if ( 'core/heading' === $block['blockName'] ) {
			$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
			$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
			if ( 2 === $level && false === strpos( $class, 'notes-title' ) ) {
				$html = $block['innerHTML'];
				$text = trim( wp_strip_all_tags( $html ) );
				$id   = preg_match( '/\sid="([^"]+)"/', $html, $m ) ? $m[1] : sanitize_title( $text );
				if ( '' !== $text ) {
					// Leave out a trailing verse reference such as "Q 5:47" from the list label.
					$label   = trim( preg_replace( '/\s*Q\s[\d:–\-·\s]+$/u', '', $text ) );
					$found[] = array( $id, '' !== $label ? $label : $text );
				}
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = islamicdilemma_collect_headings( $block['innerBlocks'], $found );
		}
	}
	return $found;
}

/**
 * [islamicdilemma_toc] "On this page" links to the post's sections.
 *
 * @param array $atts Shortcode attributes: title.
 * @return string
 */
function islamicdilemma_toc( $atts ) {
	$atts = shortcode_atts( array( 'title' => __( 'On this page', 'islamicdilemma' ) ), $atts );
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	$headings = islamicdilemma_collect_headings( parse_blocks( $post->post_content ) );
	if ( count( $headings ) < 2 ) {
		return '';
	}
	$links = '';
	foreach ( $headings as $heading ) {
		$links .= sprintf( '<a href="#%s">%s</a>', esc_attr( $heading[0] ), esc_html( $heading[1] ) );
	}
	return sprintf(
		'<nav class="side-box toc" aria-label="%1$s"><h2 class="side-title">%1$s</h2>%2$s</nav>',
		esc_html( $atts['title'] ),
		$links
	);
}
add_shortcode( 'islamicdilemma_toc', 'islamicdilemma_toc' );

/**
 * [islamicdilemma_reading_time] "7 min read" for the current post.
 *
 * @return string
 */
function islamicdilemma_reading_time() {
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
	$minutes = max( 1, (int) round( $words / 200 ) );
	/* translators: %d: minutes of reading. */
	return '<span class="reading-time">' . esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'islamicdilemma' ), $minutes ) ) . '</span>';
}
add_shortcode( 'islamicdilemma_reading_time', 'islamicdilemma_reading_time' );

/**
 * [islamicdilemma_related] Up to three other posts from the same category.
 *
 * @return string
 */
function islamicdilemma_related() {
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	$cats  = wp_get_post_categories( $post->ID );
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 3,
			'post__not_in'        => array( $post->ID ),
			'category__in'        => $cats,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( ! $query->have_posts() ) {
		return '';
	}
	$links = '';
	foreach ( $query->posts as $related ) {
		$links .= sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $related ) ), esc_html( get_the_title( $related ) ) );
	}
	return sprintf(
		'<nav class="side-box" aria-label="%1$s"><h2 class="side-title">%1$s</h2>%2$s</nav>',
		esc_html__( 'Related reading', 'islamicdilemma' ),
		$links
	);
}
add_shortcode( 'islamicdilemma_related', 'islamicdilemma_related' );

/**
 * [islamicdilemma_result_type] Label for a search result: "Page", or the
 * post's first category.
 *
 * @return string
 */
function islamicdilemma_result_type() {
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	if ( 'page' === $post->post_type ) {
		$label = __( 'Page', 'islamicdilemma' );
	} else {
		$cats  = get_the_category( $post->ID );
		$label = $cats ? $cats[0]->name : __( 'Enrichment', 'islamicdilemma' );
	}
	return '<span class="result-type">' . esc_html( $label ) . '</span>';
}
add_shortcode( 'islamicdilemma_result_type', 'islamicdilemma_result_type' );

/**
 * [islamicdilemma_sitemap] An HTML site map: pages in reading order, then
 * Enrichment articles grouped by category.
 *
 * @return string
 */
function islamicdilemma_sitemap() {
	$skip  = array_filter( array( (int) get_option( 'page_on_front' ), (int) get_the_ID(), function_exists( 'islamicdilemma_hidden_book_id' ) ? islamicdilemma_hidden_book_id() : 0 ) );
	$html  = '<h2 id="pages">' . esc_html__( 'Pages', 'islamicdilemma' ) . '</h2><ul class="site-map">';
	$html .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/' ) ), esc_html__( 'Home', 'islamicdilemma' ) );
	foreach ( get_pages( array( 'sort_column' => 'menu_order', 'exclude' => $skip ) ) as $page ) {
		$html .= sprintf(
			'<li><a href="%s">%s</a>%s</li>',
			esc_url( get_permalink( $page ) ),
			esc_html( get_the_title( $page ) ),
			$page->post_excerpt ? ' <span class="site-map-note">' . esc_html( $page->post_excerpt ) . '</span>' : ''
		);
	}
	$html .= '</ul>';
	foreach ( get_categories( array( 'hide_empty' => true ) ) as $cat ) {
		$posts = get_posts( array( 'category' => $cat->term_id, 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		if ( ! $posts ) {
			continue;
		}
		$html .= sprintf( '<h2 id="%s">%s</h2><ul class="site-map">', esc_attr( $cat->slug ), esc_html( $cat->name ) );
		foreach ( $posts as $post ) {
			$html .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $post ) ), esc_html( get_the_title( $post ) ) );
		}
		$html .= '</ul>';
	}
	return $html;
}
add_shortcode( 'islamicdilemma_sitemap', 'islamicdilemma_sitemap' );
