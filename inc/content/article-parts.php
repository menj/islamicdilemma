<?php
/**
 * Article parts: the word count behind the reading time, the key points box,
 * the next step after the author box, and the FAQ block style.
 *
 * The key points box (blocks/answer-box) shows the lines typed in the "Key
 * points" box on the edit screen. The deck above it already carries the
 * excerpt, so the box shows nothing until key points are written.
 *
 * The next step (blocks/next-step) offers three ways on and nothing else:
 * keep reading (the next page on the reading path, or the next article in
 * the same category), check the sources (the article's notes, or the Sources
 * and scholarship page), and ask a question (the Contact page). It never
 * links to the page it sits on, and never to an unpublished page.
 *
 * FAQ: Details blocks take the "FAQ" style and the islamicdilemma/faq
 * pattern. The questions stay visible on the page only. No FAQPage markup is
 * printed: `.claude/SEO.md` rules the type out.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Post meta key for the key points, one point a line. */
const ISLAMICDILEMMA_KEY_POINTS = '_islamicdilemma_key_points';

/**
 * Words in a post's text. Counts letters with their marks, so "taḥrīf" and
 * "qirāʾāt" are one word each, where str_word_count() splits them.
 *
 * @param WP_Post|int|null $post Post.
 * @return int
 */
function islamicdilemma_word_count( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 0;
	}
	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$count = preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\p{M}\'’-]*/u', $text );
	// Invalid UTF-8 makes the pattern fail: fall back to the byte-wise count.
	return false === $count ? str_word_count( $text ) : (int) $count;
}

/**
 * Minutes of reading at 200 words a minute, never less than one.
 *
 * @param WP_Post|int|null $post Post.
 * @return int
 */
function islamicdilemma_reading_minutes( $post = null ) {
	return max( 1, (int) round( islamicdilemma_word_count( $post ) / 200 ) );
}

/**
 * The "7 min read" label.
 *
 * @param WP_Post|int|null $post Post.
 * @return string Plain text.
 */
function islamicdilemma_reading_label( $post = null ) {
	$minutes = islamicdilemma_reading_minutes( $post );
	/* translators: %d: minutes of reading. */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'islamicdilemma' ), $minutes );
}

/**
 * Key points of a post: one a line, blank lines dropped, at most six.
 *
 * @param WP_Post|int|null $post Post.
 * @return string[]
 */
function islamicdilemma_key_points( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$lines = preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $post->ID, ISLAMICDILEMMA_KEY_POINTS, true ) );
	$lines = array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );
	return array_slice( $lines, 0, 6 );
}

/**
 * Register the key points meta, so the REST API and the editor can read it.
 */
function islamicdilemma_register_article_meta() {
	foreach ( array( 'post', 'page' ) as $type ) {
		register_post_meta(
			$type,
			ISLAMICDILEMMA_KEY_POINTS,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => static function ( $allowed, $key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'islamicdilemma_register_article_meta' );

/**
 * The "Key points" box on the edit screen of posts and pages.
 */
function islamicdilemma_add_key_points_box() {
	add_meta_box( 'islamicdilemma-key-points', __( 'Key points', 'islamicdilemma' ), 'islamicdilemma_key_points_box', array( 'post', 'page' ), 'side', 'default' );
}
add_action( 'add_meta_boxes', 'islamicdilemma_add_key_points_box' );

/**
 * Print the key points box.
 *
 * @param WP_Post $post Post being edited.
 */
function islamicdilemma_key_points_box( $post ) {
	wp_nonce_field( 'islamicdilemma_key_points', 'islamicdilemma_key_points_nonce' );
	?>
	<p><label for="islamicdilemma-key-points-field"><?php esc_html_e( 'One point a line, at most six. They show in a box above the article. Leave it empty for no box.', 'islamicdilemma' ); ?></label></p>
	<textarea id="islamicdilemma-key-points-field" name="islamicdilemma_key_points" rows="6" class="widefat"><?php echo esc_textarea( (string) get_post_meta( $post->ID, ISLAMICDILEMMA_KEY_POINTS, true ) ); ?></textarea>
	<?php
}

/**
 * Save the key points box.
 *
 * @param int $post_id Post ID.
 */
function islamicdilemma_save_key_points( $post_id ) {
	if ( ! isset( $_POST['islamicdilemma_key_points_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['islamicdilemma_key_points_nonce'] ) ), 'islamicdilemma_key_points' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$value = sanitize_textarea_field( wp_unslash( $_POST['islamicdilemma_key_points'] ?? '' ) );
	if ( '' === trim( $value ) ) {
		delete_post_meta( $post_id, ISLAMICDILEMMA_KEY_POINTS );
		return;
	}
	update_post_meta( $post_id, ISLAMICDILEMMA_KEY_POINTS, $value );
}
add_action( 'save_post', 'islamicdilemma_save_key_points' );

/**
 * Pages read in order: the argument, the reply, its two lists of errors, then
 * the Enrichment articles. The keep-reading link on these pages leads to the
 * next one; other pages lead to the start of the reply.
 *
 * @return string[] Page slugs.
 */
function islamicdilemma_reading_path() {
	return (array) apply_filters( 'islamicdilemma_reading_path', array( 'islamic-dilemma', 'muslim-response', 'errors-in-case-for-dilemma', 'more-errors-in-case-for-dilemma', 'enrichment' ) );
}

/**
 * A published page by its path, or null.
 *
 * @param string $slug Page path.
 * @return WP_Post|null
 */
function islamicdilemma_published_page( $slug ) {
	$page = get_page_by_path( $slug );
	return $page && 'publish' === $page->post_status ? $page : null;
}

/**
 * The keep-reading target for a post or page.
 *
 * @param WP_Post $post Current post.
 * @return WP_Post|null
 */
function islamicdilemma_next_read( $post ) {
	if ( 'post' === $post->post_type ) {
		// get_adjacent_post() reads the global post: point it at this one, then put it back.
		$saved           = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$next            = null;
		// The next article in the same category, newer first, then older.
		foreach ( array( false, true ) as $previous ) {
			$found = get_adjacent_post( true, '', $previous, 'category' );
			if ( $found instanceof WP_Post ) {
				$next = $found;
				break;
			}
		}
		$GLOBALS['post'] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		return $next ? $next : islamicdilemma_published_page( 'muslim-response' );
	}
	$path = islamicdilemma_reading_path();
	$at   = array_search( $post->post_name, $path, true );
	if ( false === $at ) {
		$next = islamicdilemma_published_page( (string) reset( $path ) );
		return $next && $next->ID !== $post->ID ? $next : null;
	}
	for ( $i = $at + 1, $n = count( $path ); $i < $n; $i++ ) {
		$next = islamicdilemma_published_page( $path[ $i ] );
		if ( $next ) {
			return $next;
		}
	}
	return null;
}

/**
 * Whether a post's text carries a Notes heading.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function islamicdilemma_has_notes( $post ) {
	return false !== strpos( $post->post_content, 'notes-title' );
}

/**
 * The next-step links for a post: kind => array( label, title, url ).
 *
 * @param WP_Post $post Current post.
 * @return array<string,array{0:string,1:string,2:string}>
 */
function islamicdilemma_next_steps( $post ) {
	$steps = array();
	$next  = islamicdilemma_next_read( $post );
	if ( $next && $next->ID !== $post->ID ) {
		$steps['read'] = array( __( 'Keep reading', 'islamicdilemma' ), get_the_title( $next ), get_permalink( $next ) );
	}
	if ( islamicdilemma_has_notes( $post ) ) {
		$steps['sources'] = array( __( 'Check the sources', 'islamicdilemma' ), __( 'The notes to this article', 'islamicdilemma' ), '#notes' );
	} else {
		$resources = islamicdilemma_published_page( 'resources' );
		if ( $resources && $resources->ID !== $post->ID ) {
			$steps['sources'] = array( __( 'Check the sources', 'islamicdilemma' ), get_the_title( $resources ), get_permalink( $resources ) );
		}
	}
	$contact = islamicdilemma_published_page( 'contact' );
	if ( $contact && $contact->ID !== $post->ID ) {
		$steps['ask'] = array( __( 'Ask a question', 'islamicdilemma' ), __( 'Write to the author', 'islamicdilemma' ), get_permalink( $contact ) );
	}
	return (array) apply_filters( 'islamicdilemma_next_steps', $steps, $post );
}

/**
 * The FAQ style for Details blocks: a visible question and answer. No
 * FAQPage markup goes with it.
 */
function islamicdilemma_register_faq_style() {
	register_block_style(
		'core/details',
		array(
			'name'  => 'faq',
			'label' => __( 'FAQ', 'islamicdilemma' ),
		)
	);
}
add_action( 'init', 'islamicdilemma_register_faq_style' );

/**
 * Tables in posts and pages: a focusable region named by the caption, so a
 * table wider than a phone screen can be scrolled from the keyboard.
 *
 * @param string $content Rendered block.
 * @return string
 */
function islamicdilemma_table_region( $content ) {
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( ! $tags->next_tag( 'figure' ) ) {
		return $content;
	}
	$label = preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#s', $content, $m ) ? trim( wp_strip_all_tags( $m[1] ) ) : '';
	$tags->set_attribute( 'tabindex', '0' );
	$tags->set_attribute( 'role', 'region' );
	$tags->set_attribute( 'aria-label', '' !== $label ? $label : __( 'Table', 'islamicdilemma' ) );
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/table', 'islamicdilemma_table_region' );

/**
 * The stylesheet for these parts and the progress bar's companions.
 */
function islamicdilemma_article_parts_assets() {
	if ( ! is_singular() ) {
		return;
	}
	wp_enqueue_style(
		'islamicdilemma-article-parts',
		get_stylesheet_directory_uri() . '/assets/css/article-parts.css',
		array( 'islamicdilemma-theme' ),
		islamicdilemma_asset_version( 'assets/css/article-parts.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'islamicdilemma_article_parts_assets', 21 );
