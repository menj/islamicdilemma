<?php
/**
 * Glossary entry images: the id_image tag field, its media picker, and the fallback chain.
 *
 * Order: the image chosen on the tag, then the featured image of the newest post with the tag,
 * then a photograph shipped in assets/img, picked from a pool that suits the entry.
 *
 * @package islamicdilemma
 */

/**
 * Register id_image as tag meta holding an attachment ID.
 */
function islamicdilemma_register_entry_image_meta() {
	register_term_meta(
		'post_tag',
		'id_image',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'islamicdilemma_sanitize_entry_image',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);
}
add_action( 'init', 'islamicdilemma_register_entry_image_meta' );

/**
 * Keep id_image to the ID of an existing image attachment, or 0.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function islamicdilemma_sanitize_entry_image( $value ) {
	$id = absint( $value );
	return $id && wp_attachment_is_image( $id ) ? $id : 0;
}

/**
 * Bundled photographs, by pool. Each file is in assets/img and credited in credits.json.
 *
 * @return array Pool => list of array( file, width, height ).
 */
function islamicdilemma_entry_image_pools() {
	return array(
		'quran'    => array(
			array( 'birmingham-quran.webp', 960, 625 ),
			array( 'folio-of-an-early-quran-manuscript-in.webp', 1400, 1071 ),
			array( 'folio-of-the-blue-quran-kairouan.webp', 1100, 834 ),
			array( 'illuminated-page-of-a-quran-manuscript.webp', 1280, 808 ),
			array( 'page-of-a-quran-manuscript.webp', 1000, 773 ),
			array( 'ornate-page-of-a-quran.webp', 940, 627 ),
			array( 'old-quran-manuscript.webp', 940, 627 ),
			array( 'page-of-the-quran-copied-by-ibn.webp', 1280, 826 ),
			array( 'page-of-an-old-arabic-quran-manuscript.webp', 940, 627 ),
			array( 'open-quran.webp', 940, 627 ),
		),
		'bible'    => array(
			array( 'codex-sinaiticus-matthew-6.webp', 760, 746 ),
			array( 'leaf-of-codex-sinaiticus-a-fourth-century.webp', 1280, 960 ),
			array( 'page-of-codex-alexandrinus-a-fifth-century.webp', 1400, 842 ),
			array( 'page-of-codex-bezae-a-greek-and.webp', 1280, 755 ),
			array( 'page-of-the-aleppo-codex-a-tenth.webp', 1000, 811 ),
			array( 'great-isaiah-scroll-from-the-dead-sea.webp', 1100, 789 ),
			array( 'hebrew-text-in-a-torah-scroll.webp', 940, 627 ),
			array( 'old-bible.webp', 1100, 676 ),
			array( 'open-bible.webp', 940, 627 ),
			array( 'papyrus-46-an-early-manuscript-of-the.webp', 1100, 775 ),
		),
		'hadith'   => array(
			array( 'handwritten-arabic-pages-in-an-old-book.webp', 940, 627 ),
			array( 'old-arabic-manuscript-open-on-a-table.webp', 940, 627 ),
			array( 'islamic-calligraphy-in-a-book.webp', 940, 627 ),
			array( 'pages-of-an-old-arabic-manuscript.webp', 940, 627 ),
			array( 'arabic-calligraphy-in-ink.webp', 940, 627 ),
		),
		'theology' => array(
			array( 'geometric-ceiling-of-a-mosque.webp', 940, 627 ),
			array( 'geometric-ceiling.webp', 1280, 853 ),
			array( 'dome-of-a-mosque-seen-from-inside.webp', 940, 627 ),
			array( 'set-of-balance-scales.webp', 940, 627 ),
			array( 'scholars-desk-with-manuscripts.webp', 940, 627 ),
		),
		'books'    => array(
			array( 'old-books-in-a-library.webp', 940, 528 ),
			array( 'bookshelves-in-an-old-library.webp', 1280, 853 ),
			array( 'old-books-stacked-on-a-shelf.webp', 1100, 825 ),
			array( 'shelves-of-old-books-in-a-library.webp', 940, 627 ),
			array( 'ancient-scrolls.webp', 940, 627 ),
			array( 'rolled-parchment-scroll.webp', 940, 642 ),
			array( 'title-page-of-an-old-printed-book.webp', 1280, 853 ),
		),
	);
}

/**
 * The pool that suits an entry: people and works get books and scrolls, then the glossary group decides.
 *
 * @param string $type     Entry type.
 * @param string $category Glossary group.
 * @return string Pool key.
 */
function islamicdilemma_entry_image_pool( $type, $category ) {
	if ( in_array( $type, array( 'person', 'work' ), true ) ) {
		return 'books';
	}
	$category = strtolower( remove_accents( $category ) );
	if ( false !== strpos( $category, 'qur' ) ) {
		return 'quran';
	}
	if ( false !== strpos( $category, 'bible' ) || false !== strpos( $category, 'gospel' ) || false !== strpos( $category, 'torah' ) ) {
		return 'bible';
	}
	if ( false !== strpos( $category, 'hadith' ) ) {
		return 'hadith';
	}
	if ( false !== strpos( $category, 'theolog' ) ) {
		return 'theology';
	}
	return 'verse' === $type ? 'quran' : 'books';
}

/**
 * The image of a glossary entry. Every entry gets one.
 *
 * @param array $entry From islamicdilemma_term_entry().
 * @return array source (tag, post or bundled), id, file, url, width, height, alt.
 */
function islamicdilemma_entry_image( $entry ) {
	static $cache = array();
	$term = $entry['term'];
	if ( isset( $cache[ $term->term_id ] ) ) {
		return $cache[ $term->term_id ];
	}
	$source = 'tag';
	$id     = islamicdilemma_sanitize_entry_image( get_term_meta( $term->term_id, 'id_image', true ) );
	if ( ! $id ) {
		$source = 'post';
		$posts  = get_posts(
			array(
				'post_type'        => 'post',
				'posts_per_page'   => 1,
				'tag_id'           => $term->term_id,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_query'       => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);
		$id     = $posts ? (int) get_post_thumbnail_id( $posts[0] ) : 0;
	}
	if ( $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( $src ) {
			$cache[ $term->term_id ] = array(
				'source' => $source,
				'id'     => $id,
				'file'   => defined( 'ISLAMICDILEMMA_ASSET_META' ) ? (string) get_post_meta( $id, ISLAMICDILEMMA_ASSET_META, true ) : '',
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
				'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
			return $cache[ $term->term_id ];
		}
	}
	$pool = islamicdilemma_entry_image_pools()[ islamicdilemma_entry_image_pool( $entry['entry_type'], $entry['category'] ) ];
	$pick = $pool[ (int) sprintf( '%u', crc32( $term->slug ) ) % count( $pool ) ];

	$cache[ $term->term_id ] = array(
		'source' => 'bundled',
		'id'     => 0,
		'file'   => $pick[0],
		'url'    => get_stylesheet_directory_uri() . '/assets/img/' . $pick[0],
		'width'  => $pick[1],
		'height' => $pick[2],
		'alt'    => '', // Decorative: the entry's name sits beside it.
	);
	return $cache[ $term->term_id ];
}

/**
 * An <img> for an entry image. Attachments go through WordPress, so they carry srcset and sizes.
 *
 * @param array  $image From islamicdilemma_entry_image().
 * @param string $size  Attachment size.
 * @param array  $attr  Extra attributes (class, sizes, loading, width, height).
 * @return string
 */
function islamicdilemma_entry_image_html( $image, $size, $attr = array() ) {
	if ( $image['id'] ) {
		return wp_get_attachment_image( $image['id'], $size, false, $attr );
	}
	$attr = array_merge(
		array(
			'width'    => $image['width'],
			'height'   => $image['height'],
			'decoding' => 'async',
		),
		$attr
	);
	$html = '<img src="' . esc_url( $image['url'] ) . '" alt=""';
	foreach ( $attr as $name => $value ) {
		if ( false !== $value && 'sizes' !== $name ) {
			$html .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}
	}
	return $html . '>';
}

/**
 * The picker on the Add Tag screen.
 */
function islamicdilemma_entry_image_add_field() {
	?>
	<div class="form-field id-term-image">
		<label for="id_image"><?php esc_html_e( 'Entry image', 'islamicdilemma' ); ?></label>
		<?php islamicdilemma_entry_image_picker( 0 ); ?>
	</div>
	<?php
}
add_action( 'post_tag_add_form_fields', 'islamicdilemma_entry_image_add_field', 20 );

/**
 * The picker on the Edit Tag screen.
 *
 * @param WP_Term $term Tag being edited.
 */
function islamicdilemma_entry_image_edit_field( $term ) {
	?>
	<tr class="form-field id-term-image">
		<th scope="row"><label for="id_image"><?php esc_html_e( 'Entry image', 'islamicdilemma' ); ?></label></th>
		<td><?php islamicdilemma_entry_image_picker( islamicdilemma_sanitize_entry_image( get_term_meta( $term->term_id, 'id_image', true ) ) ); ?></td>
	</tr>
	<?php
}
add_action( 'post_tag_edit_form_fields', 'islamicdilemma_entry_image_edit_field', 20 );

/**
 * Hidden ID field, preview and buttons. term-image.js opens the media library.
 *
 * @param int $id Current attachment ID.
 */
function islamicdilemma_entry_image_picker( $id ) {
	?>
	<input type="hidden" name="id_image" id="id_image" value="<?php echo esc_attr( $id ? $id : '' ); ?>">
	<div class="id-term-image-preview"><?php echo $id ? wp_get_attachment_image( $id, 'thumbnail' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<p>
		<button type="button" class="button id-term-image-choose"><?php esc_html_e( 'Choose image', 'islamicdilemma' ); ?></button>
		<button type="button" class="button-link id-term-image-remove"<?php echo $id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove image', 'islamicdilemma' ); ?></button>
	</p>
	<p class="description"><?php esc_html_e( 'Optional. Without one, the entry uses the featured image of the newest article with this tag, or a photograph shipped with the theme.', 'islamicdilemma' ); ?></p>
	<?php
}

/**
 * Save id_image. The nonce comes from the entry fields in term-entries.php.
 *
 * @param int $term_id Tag ID.
 */
function islamicdilemma_entry_image_save( $term_id ) {
	if ( ! isset( $_POST['islamicdilemma_entry_nonce'], $_POST['id_image'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['islamicdilemma_entry_nonce'] ) ), 'islamicdilemma_entry' )
		|| ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$id = islamicdilemma_sanitize_entry_image( wp_unslash( $_POST['id_image'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( $id ) {
		update_term_meta( $term_id, 'id_image', $id );
	} else {
		delete_term_meta( $term_id, 'id_image' );
	}
}
add_action( 'created_post_tag', 'islamicdilemma_entry_image_save' );
add_action( 'edited_post_tag', 'islamicdilemma_entry_image_save' );

/**
 * Media library, script and styles on the tag screens only.
 *
 * @param string $hook Current admin page hook.
 */
function islamicdilemma_entry_image_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) || 'post_tag' !== get_current_screen()->taxonomy ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'islamicdilemma-term-image', get_theme_file_uri( 'assets/css/term-image.css' ), array(), islamicdilemma_asset_version( 'assets/css/term-image.css' ) );
	wp_enqueue_script( 'islamicdilemma-term-image', get_theme_file_uri( 'assets/js/term-image.js' ), array( 'jquery' ), islamicdilemma_asset_version( 'assets/js/term-image.js' ), true );
	wp_localize_script(
		'islamicdilemma-term-image',
		'islamicdilemmaTermImage',
		array(
			'title'  => __( 'Entry image', 'islamicdilemma' ),
			'button' => __( 'Use this image', 'islamicdilemma' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'islamicdilemma_entry_image_admin_assets' );
