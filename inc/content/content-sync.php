<?php
/**
 * Theme content sync.
 *
 * The site's pages and posts ship with the theme as JSON files in /content.
 *
 * - On first activation, the theme imports all of its content, moves every
 *   other post and page to the Trash (recoverable for 30 days), and sets the
 *   front page, the posts page (Enrichment) and pretty permalinks. Removing
 *   other content is on by default; it can be switched off under
 *   Appearance → Theme content, or with the islamicdilemma_remove_other_content filter.
 * - Images in the content are added to the Media Library, so WordPress
 *   serves them at responsive sizes.
 * - When a new theme version ships changed content, it syncs automatically:
 *   new items are added, unchanged theme items are updated, and theme items
 *   removed from the theme are moved to the Trash.
 * - A theme item that has been edited by hand (its text, or settings such as
 *   status, slug, date, order, parent, template, comments, categories, tags
 *   or featured image) is never overwritten, trashed
 *   or otherwise touched, and one the owner deleted is never recreated,
 *   unless the owner explicitly restores it (Appearance → Theme content, or
 *   `wp islamicdilemma content restore <slug>`).
 * - Content the owner creates after the first activation is never removed.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ISLAMICDILEMMA_STATE    = 'islamicdilemma_content_state';
const ISLAMICDILEMMA_REGISTRY = 'islamicdilemma_content_registry';
const ISLAMICDILEMMA_KEY_META = '_islamicdilemma_key';
const ISLAMICDILEMMA_SUM_META = '_islamicdilemma_checksum';
const ISLAMICDILEMMA_SRC_META = '_islamicdilemma_source';
const ISLAMICDILEMMA_ASSET_META = '_islamicdilemma_asset';
const ISLAMICDILEMMA_REMOVE_OPTION = 'islamicdilemma_remove_other_content';
const ISLAMICDILEMMA_REMOVE_TAGS_OPTION = 'islamicdilemma_remove_other_tags';

/**
 * Whether each sync deletes tags that are not part of the theme.
 * On unless the owner switches it off.
 *
 * @return bool
 */
function islamicdilemma_removes_other_tags() {
	return (bool) apply_filters( 'islamicdilemma_remove_other_tags', '0' !== (string) get_option( ISLAMICDILEMMA_REMOVE_TAGS_OPTION, '1' ) );
}

/**
 * Delete tags whose slug is not in the theme's terms.json. Posts keep every other tag.
 *
 * @param array $report Sync report, updated in place.
 */
function islamicdilemma_remove_other_tags( &$report ) {
	$terms = islamicdilemma_read_json( 'terms.json' );
	$keep  = array_column( (array) ( $terms['tags'] ?? array() ), 'slug' );
	$names = array_map( 'mb_strtolower', array_column( (array) ( $terms['tags'] ?? array() ), 'name' ) );
	if ( ! $keep ) {
		return;
	}
	$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
	if ( is_wp_error( $tags ) ) {
		return;
	}
	foreach ( $tags as $tag ) {
		if ( in_array( $tag->slug, $keep, true ) || in_array( mb_strtolower( $tag->name ), $names, true ) ) {
			continue; // A theme tag, matched by slug or by name.
		}
		if ( wp_delete_term( $tag->term_id, 'post_tag' ) ) {
			$report['removed_tags'][] = $tag->name;
		}
	}
}

/**
 * Whether first activation moves non-theme posts and pages to the Trash.
 * On unless the owner switches it off.
 *
 * @return bool
 */
function islamicdilemma_removes_other_content() {
	return (bool) apply_filters( 'islamicdilemma_remove_other_content', '0' !== (string) get_option( ISLAMICDILEMMA_REMOVE_OPTION, '1' ) );
}

/**
 * Content digest, including the manifest. File dates and lengths can remain
 * unchanged during deployment, so they cannot reliably detect an update.
 *
 * @return string
 */
function islamicdilemma_content_stamp() {
	return islamicdilemma_content_fingerprint();
}

/**
 * Path to the theme's content folder.
 *
 * @return string
 */
function islamicdilemma_content_dir() {
	return get_stylesheet_directory() . '/content';
}

/**
 * Read and decode a JSON file from the content folder.
 *
 * @param string $relative Path inside /content.
 * @return array|null
 */
function islamicdilemma_read_json( $relative ) {
	$path = islamicdilemma_content_dir() . '/' . $relative;
	if ( ! is_readable( $path ) ) {
		return null;
	}
	$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return is_array( $data ) ? $data : null;
}

/**
 * Fingerprint of the theme's shipped content, so changes are detected
 * whenever the theme files change, with or without a version bump.
 *
 * @return string
 */
function islamicdilemma_content_fingerprint() {
	$manifest = islamicdilemma_read_json( 'manifest.json' );
	if ( ! $manifest || is_wp_error( islamicdilemma_validate_content() ) ) {
		return '';
	}
	$hash = hash_init( 'sha256' );
	hash_update( $hash, wp_json_encode( $manifest ) );
	foreach ( array_merge( (array) $manifest['pages'], (array) $manifest['posts'], array( $manifest['terms'], $manifest['settings'] ) ) as $file ) {
		$path = islamicdilemma_content_dir() . '/' . $file;
		if ( is_readable( $path ) ) {
			hash_update( $hash, $file . (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}
	}
	return hash_final( $hash );
}

/**
 * Validate the complete package before making any database changes. A partial
 * upload must never look like an intentional removal of shipped articles.
 *
 * @return true|WP_Error
 */
function islamicdilemma_validate_content() {
	$manifest = islamicdilemma_read_json( 'manifest.json' );
	if ( ! $manifest || ! isset( $manifest['pages'], $manifest['posts'], $manifest['terms'], $manifest['settings'] )
		|| ! is_array( $manifest['pages'] ) || ! is_array( $manifest['posts'] ) ) {
		return new WP_Error( 'invalid_manifest', 'The theme content manifest is missing or invalid.' );
	}
	if ( ! isset( $manifest['checksums'] ) || ! is_array( $manifest['checksums'] ) ) {
		return new WP_Error( 'invalid_checksums', 'Theme content checksums are missing.' );
	}
	$files = array_merge( $manifest['pages'], $manifest['posts'], array( 'terms.json', 'settings.json' ) );
	$keys = array();
	foreach ( array( 'page' => 'pages', 'post' => 'posts' ) as $type => $list ) {
		foreach ( $manifest[ $list ] as $file ) {
			if ( ! is_string( $file ) || ! preg_match( '#^' . $list . '/[a-z0-9_-]+\.json$#D', $file ) ) {
				return new WP_Error( 'invalid_path', 'Invalid content path in the theme manifest.' );
			}
			$item = islamicdilemma_read_json( $file );
			if ( ! $item || ! isset( $item['slug'], $item['title'], $item['content'] )
				|| ! is_string( $item['slug'] ) || '' === $item['slug']
				|| ! is_string( $item['title'] ) || ! is_string( $item['content'] ) ) {
				return new WP_Error( 'invalid_item', 'Missing or invalid theme content: ' . $file );
			}
			$key = $type . ':' . $item['slug'];
			if ( isset( $keys[ $key ] ) ) {
				return new WP_Error( 'duplicate_item', 'Duplicate theme content key: ' . $key );
			}
			$keys[ $key ] = true;
		}
	}
	if ( 'terms.json' !== $manifest['terms'] || 'settings.json' !== $manifest['settings'] ) {
		return new WP_Error( 'invalid_manifest', 'Invalid terms or settings path in the theme manifest.' );
	}
	$terms    = islamicdilemma_read_json( 'terms.json' );
	$settings = islamicdilemma_read_json( 'settings.json' );
	if ( ! $terms || ! isset( $terms['categories'], $terms['tags'] )
		|| ! is_array( $terms['categories'] ) || ! is_array( $terms['tags'] )
		|| ! $settings || ! isset( $settings['front_page'], $settings['posts_page'], $settings['posts_per_page'] )
		|| ! is_string( $settings['front_page'] ) || ! is_string( $settings['posts_page'] )
		|| ! is_numeric( $settings['posts_per_page'] ) || (int) $settings['posts_per_page'] < 1
		|| ! isset( $keys[ 'page:' . $settings['front_page'] ], $keys[ 'page:' . $settings['posts_page'] ] ) ) {
		return new WP_Error( 'invalid_settings', 'Theme terms or front-page settings are missing or invalid.' );
	}
	foreach ( array( 'categories', 'tags' ) as $taxonomy ) {
		foreach ( $terms[ $taxonomy ] as $term ) {
			if ( ! is_array( $term ) || ! isset( $term['slug'], $term['name'] )
				|| ! is_string( $term['slug'] ) || ! is_string( $term['name'] ) ) {
				return new WP_Error( 'invalid_terms', 'Invalid term in the theme content package.' );
			}
		}
	}
	foreach ( $files as $file ) {
		$path = islamicdilemma_content_dir() . '/' . $file;
		if ( ! isset( $manifest['checksums'][ $file ] ) || ! is_readable( $path )
			|| md5_file( $path ) !== $manifest['checksums'][ $file ] ) {
			return new WP_Error( 'checksum_mismatch', 'Incomplete theme content package: ' . $file );
		}
	}
	return true;
}

/**
 * All items the theme ships, keyed by "page:slug" or "post:slug".
 *
 * @return array
 */
function islamicdilemma_theme_items() {
	$manifest = islamicdilemma_read_json( 'manifest.json' );
	$items    = array();
	if ( ! $manifest || is_wp_error( islamicdilemma_validate_content() ) ) {
		return $items;
	}
	foreach ( array( 'page' => 'pages', 'post' => 'posts' ) as $type => $list ) {
		foreach ( (array) $manifest[ $list ] as $file ) {
			$item = islamicdilemma_read_json( $file );
			if ( $item && ! empty( $item['slug'] ) ) {
				$item['type']                     = $type;
				$items[ $type . ':' . $item['slug'] ] = $item;
			}
		}
	}
	return $items;
}

/**
 * Fill in the site-specific URLs the content files leave as placeholders.
 *
 * @param string $html Block markup.
 * @return string
 */
function islamicdilemma_fill_placeholders( $html ) {
	return strtr(
		$html,
		array(
			'{{theme_uri}}' => untrailingslashit( get_stylesheet_directory_uri() ),
			'{{home_url}}'  => untrailingslashit( home_url() ),
		)
	);
}

/**
 * Checksum of what a person can edit: the text, and the settings an editor
 * can change in the sidebar or Quick Edit.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function islamicdilemma_post_checksum( $post ) {
	$terms = array();
	foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
		$ids = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
		$ids = is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
		sort( $ids );
		$terms[ $taxonomy ] = $ids;
	}
	$fields = array(
		$post->post_title,
		$post->post_excerpt,
		$post->post_content,
		$post->post_status,
		$post->post_name,
		$post->post_date,
		$post->post_password,
		(int) $post->menu_order,
		(int) $post->post_parent,
		$post->comment_status,
		$post->ping_status,
		(string) get_post_meta( $post->ID, '_wp_page_template', true ),
		(int) get_post_thumbnail_id( $post ),
		is_sticky( $post->ID ),
		$terms,
	);
	return 'v2:' . md5( wp_json_encode( $fields ) );
}

/**
 * The 1.0.0 checksum, which covered only the text. Items written by that
 * version are compared with it until the theme next writes them.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function islamicdilemma_post_checksum_v1( $post ) {
	return md5( $post->post_title . "\n" . $post->post_excerpt . "\n" . $post->post_content );
}

/**
 * Fingerprint of a theme item as shipped, to tell whether a new theme
 * version changed it.
 *
 * @param array $item Item from the content files.
 * @return string
 */
function islamicdilemma_item_source_hash( $item ) {
	unset( $item['type'] );
	return md5( wp_json_encode( $item ) );
}

/**
 * Has a theme item been changed by hand since the theme last wrote it?
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function islamicdilemma_is_edited( $post ) {
	$stored = (string) get_post_meta( $post->ID, ISLAMICDILEMMA_SUM_META, true );
	if ( '' === $stored ) {
		return false;
	}
	$current = 0 === strpos( $stored, 'v2:' ) ? islamicdilemma_post_checksum( $post ) : islamicdilemma_post_checksum_v1( $post );
	return $current !== $stored;
}

/**
 * Names of theme tags created during this sync (the site did not have them), for re-attaching to posts.
 *
 * @param string|bool|null $name Tag name to record, true to reset, or null to read the list.
 * @return array
 */
function islamicdilemma_new_tags( $name = null ) {
	static $names = array();
	if ( true === $name ) {
		$names = array(); // A new sync starts with an empty list.
	} elseif ( null !== $name ) {
		$names[] = $name;
	}
	return $names;
}

/**
 * Give a tag that had to be recreated back to the theme posts that use it. Only adds; never removes a tag.
 *
 * @param array $items    Theme items.
 * @param array $registry Key => post ID.
 */
function islamicdilemma_restore_new_tags( $items, $registry ) {
	$new = islamicdilemma_new_tags();
	if ( ! $new ) {
		return;
	}
	foreach ( $items as $key => $item ) {
		$post_id = $registry[ $key ] ?? 0;
		$names   = array_values( array_intersect( (array) ( $item['tags'] ?? array() ), $new ) );
		if ( $post_id && $names && 'trash' !== get_post_status( $post_id ) ) {
			wp_set_object_terms( $post_id, $names, 'post_tag', true );
		}
	}
}

/**
 * Create or update the categories the content uses.
 *
 * @return array Slug => term ID.
 */
function islamicdilemma_sync_terms() {
	$terms = islamicdilemma_read_json( 'terms.json' );
	$ids   = array();
	foreach ( (array) ( $terms['categories'] ?? array() ) as $cat ) {
		$existing = get_term_by( 'slug', $cat['slug'], 'category' );
		if ( $existing ) {
			$ids[ $cat['slug'] ] = (int) $existing->term_id;
			continue;
		}
		$made = wp_insert_term( $cat['name'], 'category', array( 'slug' => $cat['slug'], 'description' => $cat['description'] ) );
		if ( ! is_wp_error( $made ) ) {
			$ids[ $cat['slug'] ] = (int) $made['term_id'];
		}
	}
	// Tags are created up front so their slugs stay short; posts then attach them by name.
	// Each tag is also a glossary entry, so its entry data is applied here.
	foreach ( (array) ( $terms['tags'] ?? array() ) as $tag ) {
		$existing = get_term_by( 'slug', $tag['slug'], 'post_tag' );
		if ( ! $existing ) {
			$existing = get_term_by( 'name', $tag['name'], 'post_tag' );
		}
		if ( $existing ) {
			$term_id = (int) $existing->term_id;
			// A tag the site made on its own (for example "q-1094" for "Q 10:94") takes the theme's slug,
			// so related entries and the cleanup below find it.
			if ( $existing->slug !== $tag['slug'] && ! get_term_by( 'slug', $tag['slug'], 'post_tag' ) ) {
				wp_update_term( $term_id, 'post_tag', array( 'slug' => $tag['slug'] ) );
			}
		} else {
			$made = wp_insert_term( $tag['name'], 'post_tag', array( 'slug' => $tag['slug'] ) );
			if ( is_wp_error( $made ) ) {
				continue;
			}
			$term_id = (int) $made['term_id'];
			islamicdilemma_new_tags( $tag['name'] );
		}
		islamicdilemma_apply_entry_data( $term_id, $tag );
	}
	return $ids;
}

/**
 * Write one theme item into the database (insert or update).
 *
 * @param array    $item    Item from the content files.
 * @param int|null $post_id Existing post to update, or null to insert.
 * @param array    $cats    Category slug => term ID.
 * @return int|WP_Error Post ID.
 */
function islamicdilemma_write_item( $item, $post_id, $cats ) {
	$data = array(
		'post_type'    => $item['type'],
		'post_name'    => $item['slug'],
		'post_title'   => $item['title'],
		'post_excerpt' => $item['excerpt'] ?? '',
		'post_content' => islamicdilemma_attach_images( islamicdilemma_fill_placeholders( $item['content'] ) ),
		'post_status'  => $item['status'] ?? 'publish',
		'post_author'  => islamicdilemma_content_author(),
	);
	if ( 'page' === $item['type'] ) {
		$data['menu_order'] = (int) ( $item['menu_order'] ?? 0 );
	} else {
		if ( ! empty( $item['date'] ) ) {
			$data['post_date']     = $item['date'];
			$data['post_date_gmt'] = get_gmt_from_date( $item['date'] );
		}
		if ( ! empty( $item['category'] ) && isset( $cats[ $item['category'] ] ) ) {
			$data['post_category'] = array( $cats[ $item['category'] ] );
		}
		$data['tags_input'] = $item['tags'] ?? array();
	}
	if ( $post_id ) {
		$data['ID'] = $post_id;
	}

	// Content is trusted theme markup; keep kses from stripping footnote ids and spans.
	kses_remove_filters();
	try {
		$result = $post_id ? wp_update_post( wp_slash( $data ), true ) : wp_insert_post( wp_slash( $data ), true );
	} finally {
		kses_init_filters();
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$saved = get_post( $result );
	update_post_meta( $result, ISLAMICDILEMMA_KEY_META, $item['type'] . ':' . $item['slug'] );
	update_post_meta( $result, ISLAMICDILEMMA_SUM_META, islamicdilemma_post_checksum( $saved ) );
	update_post_meta( $result, ISLAMICDILEMMA_SRC_META, islamicdilemma_item_source_hash( $item ) );
	return (int) $result;
}

/**
 * The Media Library copy of a theme image, added on first use.
 *
 * @param string $file File name in assets/img.
 * @param string $alt  Alt text for a new attachment.
 * @return int Attachment ID, or 0 if it could not be added.
 */
function islamicdilemma_theme_attachment( $file, $alt ) {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => ISLAMICDILEMMA_ASSET_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( $found ) {
		return (int) $found[0];
	}
	$source = get_stylesheet_directory() . '/assets/img/' . $file;
	if ( ! is_readable( $source ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $file );
	if ( ! $tmp || ! copy( $source, $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0, $alt );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, ISLAMICDILEMMA_ASSET_META, $file );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

/**
 * Point the content's image blocks at Media Library copies of the theme
 * images, so WordPress adds srcset and sizes when it renders them.
 *
 * @param string $html Block markup with theme image URLs.
 * @return string
 */
function islamicdilemma_attach_images( $html ) {
	if ( false === strpos( $html, '<!-- wp:image' ) ) {
		return $html;
	}
	$blocks = islamicdilemma_attach_image_blocks( parse_blocks( $html ) );
	return serialize_blocks( $blocks );
}

/**
 * Recursive helper for islamicdilemma_attach_images().
 *
 * @param array $blocks Parsed blocks.
 * @return array
 */
function islamicdilemma_attach_image_blocks( $blocks ) {
	$base = untrailingslashit( get_stylesheet_directory_uri() ) . '/assets/img/';
	foreach ( $blocks as &$block ) {
		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = islamicdilemma_attach_image_blocks( $block['innerBlocks'] );
		}
		if ( 'core/image' !== $block['blockName'] || ! empty( $block['attrs']['id'] ) ) {
			continue;
		}
		$markup = $block['innerHTML'];
		if ( ! preg_match( '/<img src="' . preg_quote( $base, '/' ) . '([a-z0-9._-]+)" alt="([^"]*)"/', $markup, $m ) ) {
			continue;
		}
		$id  = islamicdilemma_theme_attachment( $m[1], html_entity_decode( $m[2], ENT_QUOTES ) );
		$url = $id ? wp_get_attachment_url( $id ) : '';
		if ( ! $url ) {
			continue;
		}
		$img = '<img src="' . esc_url( $url ) . '" alt="' . $m[2] . '" class="wp-image-' . $id . '"';
		$new = str_replace( $m[0], $img, $markup );
		if ( false !== strpos( $new, 'class="wp-image-' . $id . '" class=' ) ) {
			continue; // An img that already had a class; leave it as shipped.
		}
		$block['attrs'] = array( 'id' => $id ) + $block['attrs'];
		$block['innerHTML'] = $new;
		foreach ( $block['innerContent'] as &$chunk ) {
			if ( is_string( $chunk ) ) {
				$chunk = str_replace( $m[0], $img, $chunk );
			}
		}
		unset( $chunk );
	}
	unset( $block );
	return $blocks;
}

/**
 * The user new content is attributed to: the current user if they can
 * publish, otherwise the first administrator.
 *
 * @return int
 */
function islamicdilemma_content_author() {
	$current = get_current_user_id();
	if ( $current && user_can( $current, 'publish_pages' ) ) {
		return $current;
	}
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID' ) );
	return $admins ? (int) $admins[0] : 1;
}

/**
 * Never permanently delete content when WordPress Trash is disabled.
 *
 * @param int $post_id Post ID.
 * @return WP_Post|false|null
 */
function islamicdilemma_trash_post( $post_id ) {
	if ( ! defined( 'EMPTY_TRASH_DAYS' ) || ! EMPTY_TRASH_DAYS ) {
		return false;
	}
	return wp_trash_post( $post_id );
}

/**
 * Bring the database in line with the theme's content.
 *
 * @param array $args {
 *     @type bool  $initial Move all non-theme posts and pages to the Trash first (first activation only).
 *     @type array $restore Keys ("page:slug") to overwrite even if edited or deleted by the owner.
 * }
 * @return array Report of what happened.
 */
function islamicdilemma_sync_content( $args = array() ) {
	$args     = wp_parse_args( $args, array( 'initial' => false, 'restore' => array() ) );
	$valid    = islamicdilemma_validate_content();
	$items    = is_wp_error( $valid ) ? array() : islamicdilemma_theme_items();
	$registry = get_option( ISLAMICDILEMMA_REGISTRY, array() );
	$report   = array( 'created' => array(), 'updated' => array(), 'unchanged' => array(), 'kept_edited' => array(), 'kept_deleted' => array(), 'trashed' => array(), 'removed_tags' => array(), 'errors' => array() );

	if ( ! $items ) {
		$report['errors'][] = is_wp_error( $valid ) ? $valid->get_error_message() : 'No content files found in the theme.';
		return $report;
	}

	islamicdilemma_new_tags( true );
	$cats = islamicdilemma_sync_terms();

	// First activation: everything that isn't theme content goes to the Trash (unless switched off).
	if ( $args['initial'] && ! get_option( 'islamicdilemma_initial_cleanup_done' ) && islamicdilemma_removes_other_content() ) {
		$others = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => ISLAMICDILEMMA_KEY_META, 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);
		foreach ( $others as $other_id ) {
			$title = get_the_title( $other_id );
			if ( islamicdilemma_trash_post( $other_id ) ) {
				$report['trashed'][] = $title . ' (' . get_post_type( $other_id ) . ', not theme content)';
			} else {
				$report['errors'][] = $title . ': could not move to the Trash.';
			}
		}
	}

	if ( $args['initial'] && empty( $report['errors'] ) ) {
		update_option( 'islamicdilemma_initial_cleanup_done', true, false );
	}

	foreach ( $items as $key => $item ) {
		$post_id = isset( $registry[ $key ] ) ? (int) $registry[ $key ] : 0;
		$post    = $post_id ? get_post( $post_id ) : null;
		$restore = in_array( $key, (array) $args['restore'], true );

		// The owner deleted or trashed this item: leave it gone unless asked to restore it.
		if ( $post_id && ( ! $post || 'trash' === $post->post_status ) && ! $restore ) {
			$report['kept_deleted'][] = $item['title'];
			continue;
		}
		if ( $post && 'trash' === $post->post_status && $restore ) {
			wp_untrash_post( $post->ID );
			$post = get_post( $post->ID );
		}
		if ( ! $post ) {
			$post_id = 0;
		}

		if ( $post && islamicdilemma_is_edited( $post ) && ! $restore ) {
			$report['kept_edited'][] = $item['title'];
			continue;
		}

		if ( $post ) {
			if ( islamicdilemma_item_source_hash( $item ) === get_post_meta( $post->ID, ISLAMICDILEMMA_SRC_META, true ) && ! $restore ) {
				$report['unchanged'][] = $item['title'];
				continue;
			}
		}

		$result = islamicdilemma_write_item( $item, $post_id ? $post_id : null, $cats );
		if ( is_wp_error( $result ) ) {
			$report['errors'][] = $item['title'] . ': ' . $result->get_error_message();
			continue;
		}
		$registry[ $key ]                         = $result;
		update_option( ISLAMICDILEMMA_REGISTRY, $registry, false );
		$report[ $post_id ? 'updated' : 'created' ][] = $item['title'];
	}

	// Theme items dropped from this version of the theme go to the Trash, unless edited by hand.
	foreach ( $registry as $key => $post_id ) {
		if ( isset( $items[ $key ] ) ) {
			continue;
		}
		$post = get_post( $post_id );
		if ( $post && 'trash' !== $post->post_status ) {
			if ( islamicdilemma_is_edited( $post ) ) {
				$report['kept_edited'][] = $post->post_title . ' (no longer in the theme)';
				continue;
			}
			if ( ! islamicdilemma_trash_post( $post_id ) ) {
				$report['errors'][] = $post->post_title . ': could not move to the Trash.';
				continue;
			}
			$report['trashed'][] = $post->post_title . ' (removed from the theme)';
		}
		unset( $registry[ $key ] );
	}

	update_option( ISLAMICDILEMMA_REGISTRY, $registry, false );
	islamicdilemma_apply_settings( $registry, $args['initial'] );
	islamicdilemma_restore_new_tags( $items, $registry );
	if ( islamicdilemma_removes_other_tags() && empty( $report['errors'] ) ) {
		islamicdilemma_remove_other_tags( $report );
	}
	return $report;
}

/**
 * Front page, posts page, permalinks and posts per page.
 * On later syncs, only fills in settings that are missing.
 *
 * @param array $registry Key => post ID.
 * @param bool  $initial  First activation.
 */
function islamicdilemma_apply_settings( $registry, $initial ) {
	$settings = islamicdilemma_read_json( 'settings.json' );
	if ( ! $settings ) {
		return;
	}
	$front = $registry[ 'page:' . $settings['front_page'] ] ?? 0;
	$posts = $registry[ 'page:' . $settings['posts_page'] ] ?? 0;

	if ( $front && ( $initial || ! get_option( 'page_on_front' ) ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );
	}
	if ( $posts && ( $initial || ! get_option( 'page_for_posts' ) ) ) {
		update_option( 'page_for_posts', $posts );
	}
	if ( $initial ) {
		update_option( 'posts_per_page', (int) $settings['posts_per_page'] );
		foreach ( array( 'blogname', 'blogdescription' ) as $option ) {
			if ( ! empty( $settings[ $option ] ) ) {
				update_option( $option, $settings[ $option ] );
			}
		}
	}
	if ( '' === (string) get_option( 'permalink_structure' ) && ! empty( $settings['permalink_structure'] ) ) {
		update_option( 'permalink_structure', $settings['permalink_structure'] );
	}
	flush_rewrite_rules( false );
}

/**
 * Run a sync and remember the result. Uses a short lock so two requests
 * never sync at once.
 *
 * @param array $args See islamicdilemma_sync_content().
 * @return array|null Report, or null if another sync is running.
 */
function islamicdilemma_run_sync( $args = array() ) {
	if ( get_transient( 'islamicdilemma_sync_lock' ) ) {
		return null;
	}
	set_transient( 'islamicdilemma_sync_lock', 1, 5 * MINUTE_IN_SECONDS );
	try {
		$state  = get_option( ISLAMICDILEMMA_STATE, array() );
		// A failed first import must retain its initial settings on retry.
		$args['initial'] = empty( $state['initial'] );
		$report = islamicdilemma_sync_content( $args );
		if ( empty( $report['errors'] ) ) {
			$state['fingerprint'] = islamicdilemma_content_fingerprint();
			$state['stamp']       = islamicdilemma_content_stamp();
			$state['initial']     = true;
			$state['version']     = ISLAMICDILEMMA_VERSION;
		}
		$state['last_sync'] = time();
		$state['report']    = $report;
		$state['notice']    = true;
		update_option( ISLAMICDILEMMA_STATE, $state, false );
	} finally {
		delete_transient( 'islamicdilemma_sync_lock' );
	}
	return $report;
}

/**
 * First activation: import everything and clear out non-theme content.
 * Reactivating later syncs without clearing anything.
 */
function islamicdilemma_on_activation() {
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	islamicdilemma_run_sync( array( 'initial' => empty( $state['initial'] ) ) );
}
add_action( 'after_switch_theme', 'islamicdilemma_on_activation' );

/**
 * After a theme update, the shipped content changes without an activation
 * event. Compare fingerprints and sync when they differ.
 */
function islamicdilemma_maybe_sync() {
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	if ( empty( $state['initial'] ) ) {
		return; // Activation handles the first import.
	}
	// Hash the bytes: deployments may preserve both file dates and file sizes.
	$stamp = islamicdilemma_content_stamp();
	if ( '' !== $stamp && ( $state['stamp'] ?? '' ) === $stamp ) {
		return;
	}
	if ( '' === $stamp || ( $state['fingerprint'] ?? '' ) !== $stamp ) {
		$report = islamicdilemma_run_sync();
		if ( null === $report || ! empty( $report['errors'] ) ) {
			return; // Retry this package after lock contention or a failed import.
		}
		$state = get_option( ISLAMICDILEMMA_STATE, array() );
	}
	$state['stamp'] = $stamp;
	update_option( ISLAMICDILEMMA_STATE, $state, false );
}
add_action( 'init', 'islamicdilemma_maybe_sync', 30 );
