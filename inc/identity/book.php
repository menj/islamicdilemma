<?php
/**
 * Companion-book support, from book.json in the theme.
 *
 * The site can accompany a printed book without changing its structure:
 * a Book page ([islamicdilemma_book]) describes the book, and articles that
 * go with a chapter show a short note linking the two. Nothing appears
 * until the book has a title.
 *
 * book.json:
 *   title, subtitle, status ("Forthcoming", "Out now"), description, cover
 *   (theme-relative path or URL), cover_alt, publisher_note, isbn,
 *   buy_links: [ { "label": "...", "url": "..." } ],
 *   chapters:  [ { "number": 1, "title": "...", "pages": [ "slug", ... ] } ],
 *   errata:    [ { "page": "p. 42", "reads": "...", "should": "..." } ]
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The companion book's details.
 *
 * @return array
 */
function islamicdilemma_book() {
	static $book = null;
	if ( null === $book ) {
		$path = get_stylesheet_directory() . '/book.json';
		$data = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$book = (array) apply_filters(
			'islamicdilemma_book',
			wp_parse_args(
				is_array( $data ) ? $data : array(),
				array(
					'title'          => '',
					'subtitle'       => '',
					'status'         => '',
					'description'    => '',
					'cover'          => '',
					'cover_alt'      => '',
					'publisher_note' => '',
					'isbn'           => '',
					'buy_links'      => array(),
					'chapters'       => array(),
					'errata'         => array(),
				)
			)
		);
	}
	return $book;
}

/**
 * Whether there is a book to show.
 *
 * @return bool
 */
function islamicdilemma_has_book() {
	return '' !== trim( (string) islamicdilemma_book()['title'] );
}

/**
 * The book chapter a page accompanies, if any.
 *
 * @param WP_Post $post Post.
 * @return array|null
 */
function islamicdilemma_book_chapter( $post ) {
	if ( ! islamicdilemma_has_book() || ! $post ) {
		return null;
	}
	foreach ( (array) islamicdilemma_book()['chapters'] as $chapter ) {
		if ( in_array( $post->post_name, (array) ( $chapter['pages'] ?? array() ), true ) ) {
			return $chapter;
		}
	}
	return null;
}

/**
 * URL of the Book page.
 *
 * @return string
 */
function islamicdilemma_book_url() {
	$page = get_page_by_path( 'book' );
	return $page ? get_permalink( $page ) : home_url( '/book/' );
}

/**
 * [islamicdilemma_book] The Book page: cover, description, where to buy,
 * chapters with their companion articles, and errata.
 *
 * @return string
 */
function islamicdilemma_book_shortcode() {
	if ( ! islamicdilemma_has_book() ) {
		return '';
	}
	$book  = islamicdilemma_book();
	$cover = (string) $book['cover'];
	if ( $cover && ! preg_match( '#^https?://#', $cover ) ) {
		$cover = get_stylesheet_directory_uri() . '/' . ltrim( $cover, '/' );
	}
	$html = '<div class="book-card">';
	if ( $cover ) {
		$html .= sprintf( '<img class="book-cover" src="%s" alt="%s" loading="lazy" decoding="async">', esc_url( $cover ), esc_attr( $book['cover_alt'] ? $book['cover_alt'] : $book['title'] ) );
	}
	$html .= '<div class="book-text">';
	if ( $book['status'] ) {
		$html .= '<p class="book-status">' . esc_html( $book['status'] ) . '</p>';
	}
	$html .= '<h2 class="book-title">' . esc_html( $book['title'] ) . '</h2>';
	if ( $book['subtitle'] ) {
		$html .= '<p class="book-subtitle">' . esc_html( $book['subtitle'] ) . '</p>';
	}
	if ( islamicdilemma_has_author() ) {
		$html .= '<p class="book-author">' . esc_html__( 'By', 'islamicdilemma' ) . ' <a href="' . esc_url( islamicdilemma_author_url() ) . '">' . esc_html( islamicdilemma_author()['name'] ) . '</a></p>';
	}
	if ( $book['description'] ) {
		$html .= wp_kses_post( wpautop( $book['description'] ) );
	}
	$links = array();
	foreach ( (array) $book['buy_links'] as $link ) {
		if ( ! empty( $link['url'] ) && ! empty( $link['label'] ) ) {
			$links[] = sprintf( '<a class="wp-element-button" href="%s" rel="noopener">%s</a>', esc_url( $link['url'] ), esc_html( $link['label'] ) );
		}
	}
	if ( $links ) {
		$html .= '<p class="book-buy">' . implode( ' ', $links ) . '</p>';
	}
	if ( $book['isbn'] || $book['publisher_note'] ) {
		$html .= '<p class="book-meta">' . esc_html( trim( $book['publisher_note'] . ( $book['isbn'] ? ' · ISBN ' . $book['isbn'] : '' ), ' ·' ) ) . '</p>';
	}
	$html .= '</div></div>';

	if ( $book['chapters'] ) {
		$html .= '<h2 id="chapters">' . esc_html__( 'Chapter by chapter', 'islamicdilemma' ) . '</h2><ol class="book-chapters">';
		foreach ( (array) $book['chapters'] as $chapter ) {
			$pages = array();
			foreach ( (array) ( $chapter['pages'] ?? array() ) as $slug ) {
				$post = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );
				if ( $post && 'publish' === $post->post_status ) {
					$pages[] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post ) ), esc_html( get_the_title( $post ) ) );
				}
			}
			$html .= sprintf(
				'<li value="%d"><strong>%s</strong>%s</li>',
				(int) ( $chapter['number'] ?? 0 ),
				esc_html( $chapter['title'] ?? '' ),
				$pages ? '<span class="book-chapter-pages">' . esc_html__( 'On this site:', 'islamicdilemma' ) . ' ' . implode( ', ', $pages ) . '</span>' : ''
			);
		}
		$html .= '</ol>';
	}

	if ( $book['errata'] ) {
		$html .= '<h2 id="errata">' . esc_html__( 'Corrections to the book', 'islamicdilemma' ) . '</h2><ul class="book-errata">';
		foreach ( (array) $book['errata'] as $item ) {
			$html .= sprintf(
				'<li><strong>%s</strong>: %s <em>%s</em> %s <em>%s</em></li>',
				esc_html( $item['page'] ?? '' ),
				esc_html__( 'for', 'islamicdilemma' ),
				esc_html( $item['reads'] ?? '' ),
				esc_html__( 'read', 'islamicdilemma' ),
				esc_html( $item['should'] ?? '' )
			);
		}
		$html .= '</ul>';
	}
	return $html;
}
add_shortcode( 'islamicdilemma_book', 'islamicdilemma_book_shortcode' );

/**
 * A short note on articles that accompany a chapter of the book.
 *
 * @param string $content Post content.
 * @return string
 */
function islamicdilemma_book_note( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$chapter = islamicdilemma_book_chapter( get_post() );
	if ( ! $chapter ) {
		return $content;
	}
	$note = sprintf(
		'<aside class="book-note"><p>%s <a href="%s"><em>%s</em></a>%s.</p></aside>',
		esc_html__( 'This article accompanies', 'islamicdilemma' ),
		esc_url( islamicdilemma_book_url() . '#chapters' ),
		esc_html( islamicdilemma_book()['title'] ),
		esc_html(
			sprintf(
				/* translators: 1: chapter number, 2: chapter title. */
				__( ', chapter %1$d: %2$s', 'islamicdilemma' ),
				(int) ( $chapter['number'] ?? 0 ),
				$chapter['title'] ?? ''
			)
		)
	);
	return $content . $note;
}
add_filter( 'the_content', 'islamicdilemma_book_note', 20 );

/**
 * Until the book has a title, its page answers 404 and stays out of the
 * sitemaps, the Sitemap page and llms.txt.
 */
function islamicdilemma_hide_book_page() {
	if ( ! islamicdilemma_has_book() && is_page( 'book' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'islamicdilemma_hide_book_page', 1 );

/**
 * ID of the Book page while it is hidden, or 0.
 *
 * @return int
 */
function islamicdilemma_hidden_book_id() {
	if ( islamicdilemma_has_book() ) {
		return 0;
	}
	$page = get_page_by_path( 'book' );
	return $page ? (int) $page->ID : 0;
}

/**
 * Keep the hidden Book page out of the XML sitemap.
 *
 * @param array  $args      Query arguments.
 * @param string $post_type Post type.
 * @return array
 */
function islamicdilemma_sitemap_skip_book( $args, $post_type ) {
	$id = 'page' === $post_type ? islamicdilemma_hidden_book_id() : 0;
	if ( $id ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), array( $id ) );
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'islamicdilemma_sitemap_skip_book', 10, 2 );
