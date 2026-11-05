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
 *   serves them at responsive sizes. Cron adds them in short batches after
 *   a sync (islamicdilemma_attach_batch()); the sync itself never sideloads.
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
const ISLAMICDILEMMA_HASH_META = '_islamicdilemma_content_hash';
const ISLAMICDILEMMA_DATES_OPTION = 'islamicdilemma_dates_repaired';
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
 * Fingerprint of what a reader takes as the item's text: its title, excerpt
 * and content as shipped. Only a change here counts as an update, so only a
 * change here moves the "Updated" date.
 *
 * @param array $item Item from the content files.
 * @return string
 */
function islamicdilemma_item_content_hash( $item ) {
	return 'v1:' . md5( wp_json_encode( array( (string) $item['title'], (string) ( $item['excerpt'] ?? '' ), (string) $item['content'] ) ) );
}

/**
 * Whether a post already holds the item's title, excerpt and content. The
 * content may carry Media Library copies of the theme images in place of
 * their theme addresses, so both forms count as a match.
 *
 * @param WP_Post $post Post.
 * @param array   $item Item from the content files.
 * @return bool
 */
function islamicdilemma_post_has_item_text( $post, $item ) {
	if ( $post->post_title !== $item['title'] || $post->post_excerpt !== (string) ( $item['excerpt'] ?? '' ) ) {
		return false;
	}
	$filled = islamicdilemma_fill_placeholders( $item['content'] );
	if ( $post->post_content === $filled ) {
		return true;
	}
	$pending = 0;
	return $post->post_content === islamicdilemma_attach_images( $filled, false, $pending );
}

/**
 * Whether writing the item would leave the post's text as it is. An item the
 * theme wrote and nobody edited since is judged by its stored text hash; any
 * other post by its fields.
 *
 * @param WP_Post $post Post.
 * @param array   $item Item from the content files.
 * @return bool
 */
function islamicdilemma_item_text_unchanged( $post, $item ) {
	$stored = (string) get_post_meta( $post->ID, ISLAMICDILEMMA_HASH_META, true );
	if ( '' !== $stored && ! islamicdilemma_is_edited( $post ) ) {
		return islamicdilemma_item_content_hash( $item ) === $stored;
	}
	return islamicdilemma_post_has_item_text( $post, $item );
}

/**
 * Whether a post already matches everything a write of the item would set:
 * the text, status, slug, author, order, date, category and tags. Such a post needs
 * no write at all.
 *
 * @param WP_Post $post Post.
 * @param array   $item Item from the content files.
 * @param array   $cats Category slug => term ID.
 * @return bool
 */
function islamicdilemma_post_matches_item( $post, $item, $cats ) {
	if ( $post->post_type !== $item['type'] || $post->post_name !== $item['slug']
		|| $post->post_status !== ( $item['status'] ?? 'publish' ) || (int) $post->post_author !== (int) islamicdilemma_content_author()
		|| ! islamicdilemma_post_has_item_text( $post, $item ) ) {
		return false;
	}
	if ( 'page' === $item['type'] ) {
		return (int) $post->menu_order === (int) ( $item['menu_order'] ?? 0 );
	}
	if ( ! empty( $item['date'] ) && $post->post_date !== $item['date'] ) {
		return false;
	}
	if ( ! empty( $item['category'] ) && isset( $cats[ $item['category'] ] ) ) {
		$ids = wp_get_object_terms( $post->ID, 'category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $ids ) || array( (int) $cats[ $item['category'] ] ) !== array_map( 'intval', $ids ) ) {
			return false;
		}
	}
	$names = wp_get_object_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) );
	if ( is_wp_error( $names ) ) {
		return false;
	}
	$want = array_map( 'strval', (array) ( $item['tags'] ?? array() ) );
	$have = array_map( 'strval', $names );
	sort( $want );
	sort( $have );
	return $want === $have;
}

/**
 * Record that the theme's copy of an item is in the post: its key, the
 * checksum that tells later syncs whether someone edited it, and the hashes
 * of the shipped item and of its text.
 *
 * @param int   $post_id Post ID.
 * @param array $item    Item from the content files.
 */
function islamicdilemma_mark_item( $post_id, $item ) {
	update_post_meta( $post_id, ISLAMICDILEMMA_KEY_META, $item['type'] . ':' . $item['slug'] );
	update_post_meta( $post_id, ISLAMICDILEMMA_SUM_META, islamicdilemma_post_checksum( get_post( $post_id ) ) );
	update_post_meta( $post_id, ISLAMICDILEMMA_SRC_META, islamicdilemma_item_source_hash( $item ) );
	update_post_meta( $post_id, ISLAMICDILEMMA_HASH_META, islamicdilemma_item_content_hash( $item ) );
}

/**
 * Set a post's modified dates without a write through WordPress, which
 * would give it the current time. The row changes only while its
 * post_modified_gmt is still $expect, so a save meanwhile wins.
 *
 * @param int    $post_id Post ID.
 * @param string $local   New post_modified.
 * @param string $gmt     New post_modified_gmt.
 * @param string $expect  The post_modified_gmt the caller read.
 * @return int|false Rows changed (0 when the post changed meanwhile), or false on a database error.
 */
function islamicdilemma_put_modified( $post_id, $local, $gmt, $expect ) {
	global $wpdb;
	$rows = $wpdb->update( $wpdb->posts, array( 'post_modified' => $local, 'post_modified_gmt' => $gmt ), array( 'ID' => $post_id, 'post_modified_gmt' => $expect ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	clean_post_cache( $post_id );
	return $rows;
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
			if ( $existing->slug === $tag['slug'] && $existing->name !== $tag['name'] && ! get_term_by( 'name', $tag['name'], 'post_tag' ) ) {
				wp_update_term( $term_id, 'post_tag', array( 'name' => $tag['name'] ) ); // The theme owns tag names; the slug in the URL stays.
			}
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
 * WordPress gives every update the current time as its modified date. A
 * write that leaves the text as it was passes the post's existing modified
 * dates in $modified, and a wp_insert_post_data filter, added for this one
 * write, puts them back. Saves from the editor never pass through here, so
 * they move the date as usual.
 *
 * @param array      $item     Item from the content files.
 * @param int|null   $post_id  Existing post to update, or null to insert.
 * @param array      $cats     Category slug => term ID.
 * @param array|null $modified The post_modified and post_modified_gmt to keep, or null to let WordPress set them.
 * @return int|WP_Error Post ID.
 */
function islamicdilemma_write_item( $item, $post_id, $cats, $modified = null ) {
	$pending = 0;
	$data    = array(
		'post_type'    => $item['type'],
		'post_name'    => $item['slug'],
		'post_title'   => $item['title'],
		'post_excerpt' => $item['excerpt'] ?? '',
		'post_content' => islamicdilemma_attach_images( islamicdilemma_fill_placeholders( $item['content'] ), false, $pending ),
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

	$keep = null;
	if ( $post_id && is_array( $modified ) && 2 === count( $modified ) ) {
		$keep = function ( $fields, $postarr ) use ( $post_id, $modified ) {
			if ( isset( $postarr['ID'] ) && (int) $postarr['ID'] === (int) $post_id ) {
				$fields['post_modified']     = $modified[0];
				$fields['post_modified_gmt'] = $modified[1];
			}
			return $fields;
		};
		add_filter( 'wp_insert_post_data', $keep, PHP_INT_MAX, 2 );
	}

	// Content is trusted theme markup; keep kses from stripping footnote ids and spans.
	kses_remove_filters();
	try {
		$result = $post_id ? wp_update_post( wp_slash( $data ), true ) : wp_insert_post( wp_slash( $data ), true );
	} finally {
		kses_init_filters();
		if ( $keep ) {
			remove_filter( 'wp_insert_post_data', $keep, PHP_INT_MAX );
		}
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	islamicdilemma_mark_item( (int) $result, $item );
	if ( $pending ) {
		islamicdilemma_attach_queue( (int) $result );
	}
	return (int) $result;
}

/**
 * Posts whose theme images still wait for their Media Library copies. The
 * sync runs on init, inside a visitor's request, so it never sideloads: an
 * image is a copy, a resize into every registered size and several database
 * writes, and twenty of them outlast a host's time limit. Cron adds them in
 * short batches instead (islamicdilemma_attach_batch()).
 *
 * Each waiting post carries the _islamicdilemma_attach_pending meta, so a
 * sync that queues a post and a batch that finishes another never write the
 * same row. The islamicdilemma_attach_waiting option only tells the update
 * guard in functions.php that a batch may be owed.
 *
 * @param int $post_id Post to add to the queue; 0 only reads it.
 * @return int[]
 */
function islamicdilemma_attach_queue( $post_id = 0 ) {
	if ( $post_id ) {
		// A new value each time: a batch clears the marker only if it still
		// holds the value the batch started from.
		update_post_meta( $post_id, '_islamicdilemma_attach_pending', wp_generate_password( 12, false ) );
		update_option( 'islamicdilemma_attach_waiting', 1, false );
	}
	$queue = array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				// A different 50 each time, so a post that cannot finish never
				// keeps the others waiting.
				'orderby'        => 'rand',
				'meta_key'       => '_islamicdilemma_attach_pending', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'no_found_rows'  => true,
			)
		)
	);
	if ( $post_id && ! wp_next_scheduled( 'islamicdilemma_attach_batch' ) ) {
		wp_schedule_single_event( time() + 30, 'islamicdilemma_attach_batch' );
	}
	return $queue;
}

/**
 * Whether this batch may add one more image: at most 3 new copies, each
 * started within the batch's first 8 seconds.
 *
 * @param bool $reset Start a new batch.
 * @return bool
 */
function islamicdilemma_attach_budget( $reset = false ) {
	static $started = 0.0, $added = 0;
	if ( $reset ) {
		$started = microtime( true );
		$added   = 0;
		return true;
	}
	// A new copy may start only in the first 8 seconds, and at most 3 run.
	// A copy already running cannot be stopped part-way (WordPress resizes
	// it in one call), so this is a window for starting, not a deadline; one
	// copy rarely takes more than two seconds, which keeps a batch well
	// inside a 30-second limit, and set_time_limit() above asks for more.
	if ( ! $started || $added >= 3 || microtime( true ) - $started > 8 ) {
		return false;
	}
	++$added;
	return true;
}

/**
 * One cron batch: give the queued posts their Media Library images, within
 * the batch budget, then schedule the next batch while any are left. A post
 * edited by hand since the theme wrote it is left as it is and leaves the
 * queue. The post's checksum is renewed, so the theme still knows it as
 * its own. The content is written only if it is still what the batch read,
 * and a post whose write failed stays in the queue.
 */
function islamicdilemma_attach_batch() {
	global $wpdb;
	$progress = false;
	// Room for the last copy to finish where the host allows it.
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	islamicdilemma_attach_budget( true );
	$done  = array();
	$marks = array();
	foreach ( islamicdilemma_attach_queue() as $post_id ) {
		// The marker stays until the post is finished, so a batch cut short
		// leaves it queued; the value read here is the one cleared then, so
		// a sync that queues the post again meanwhile keeps its own.
		$marks[ $post_id ] = (string) get_post_meta( $post_id, '_islamicdilemma_attach_pending', true );
		$post              = get_post( $post_id );
		if ( ! $post || 'trash' === $post->post_status || islamicdilemma_is_edited( $post ) ) {
			$done[] = $post_id;
			continue;
		}
		$pending = 0;
		$html    = islamicdilemma_attach_images( $post->post_content, true, $pending );
		if ( $html !== $post->post_content ) {
			// The sideloads took time: look again, past the cache, and leave
			// a post an editor changed meanwhile, in any field the checksum
			// covers, to the editor.
			clean_post_cache( $post_id );
			$fresh = get_post( $post_id );
			if ( ! $fresh || islamicdilemma_is_edited( $fresh ) ) {
				$done[] = $post_id;
				continue;
			}
			// Written only if the content is still, byte for byte, what this
			// batch read and worked on; the checksum then covers the new
			// content and nothing else. If a sync rewrote the post meanwhile,
			// nothing is written and the post stays queued: the next batch
			// starts again from the newer content. The modified date stays: a
			// Media Library copy of an image changes no word a reader sees.
			$rows = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = %s WHERE ID = %d AND MD5(post_content) = %s", $html, $post_id, md5( $post->post_content ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $post_id );
			if ( 1 !== (int) $rows ) {
				continue; // Changed meanwhile, or a database error: stays queued.
			}
			$before              = clone $fresh;
			$fresh->post_content = $html;
			update_post_meta( $post_id, ISLAMICDILEMMA_SUM_META, islamicdilemma_post_checksum( $fresh ) );
			$progress = true;
			// What wp_update_post() would announce, so page caches, search
			// indexes and the verse index hear of the change.
			$saved = get_post( $post_id );
			if ( ! $saved ) {
				continue;
			}
			do_action( 'edit_post', $post_id, $saved );
			do_action( 'post_updated', $post_id, $saved, $before );
			do_action( "save_post_{$saved->post_type}", $post_id, $saved, true );
			do_action( 'save_post', $post_id, $saved, true );
			do_action( 'wp_insert_post', $post_id, $saved, true );
			do_action( 'wp_after_insert_post', $saved, true, $before );
		}
		if ( ! $pending ) {
			$done[] = $post_id;
		}
	}
	// A finished post leaves the queue, unless a sync queued it again
	// meanwhile (its marker then holds a new value, and stays).
	foreach ( $done as $post_id ) {
		delete_post_meta( $post_id, '_islamicdilemma_attach_pending', $marks[ $post_id ] ?? '' );
	}
	if ( islamicdilemma_attach_queue() ) {
		// A batch that got nothing done (every image waiting out its retry
		// delay) does not come back every minute: an hour later is enough.
		if ( ! wp_next_scheduled( 'islamicdilemma_attach_batch' ) ) {
			wp_schedule_single_event( time() + ( $progress || $done ? 60 : HOUR_IN_SECONDS ), 'islamicdilemma_attach_batch' );
		}
	} else {
		delete_option( 'islamicdilemma_attach_waiting' );
	}
}
add_action( 'islamicdilemma_attach_batch', 'islamicdilemma_attach_batch' );

/**
 * Once per theme version, from cron: queue the theme's own posts that still
 * hold an image at its theme address. The importer before 1.4.4 could leave
 * one there when a sideload failed, and a post the sync does not rewrite
 * would never reach the queue otherwise.
 */
function islamicdilemma_attach_scan() {
	$base = untrailingslashit( get_stylesheet_directory_uri() ) . '/assets/img/';
	foreach ( array_map( 'intval', (array) get_option( ISLAMICDILEMMA_REGISTRY, array() ) ) as $post_id ) {
		$post = $post_id ? get_post( $post_id ) : null;
		if ( $post && 'trash' !== $post->post_status && false !== strpos( $post->post_content, '<img src="' . $base ) && ! islamicdilemma_is_edited( $post ) ) {
			islamicdilemma_attach_queue( $post_id );
		}
	}
	update_option( 'islamicdilemma_attach_scanned', ISLAMICDILEMMA_VERSION, true ); // Autoloaded: read on every request.
}
add_action( 'islamicdilemma_attach_scan', 'islamicdilemma_attach_scan' );

/**
 * Schedules islamicdilemma_attach_scan() after a version change.
 */
function islamicdilemma_maybe_attach_scan() {
	if ( get_option( 'islamicdilemma_attach_scanned' ) !== ISLAMICDILEMMA_VERSION && ! wp_next_scheduled( 'islamicdilemma_attach_scan' ) ) {
		wp_schedule_single_event( time() + 60, 'islamicdilemma_attach_scan' );
	}
}
add_action( 'init', 'islamicdilemma_maybe_attach_scan', 40 );

/**
 * The Media Library copy of a theme image, added on first use.
 *
 * @param string $file   File name in assets/img.
 * @param string $alt    Alt text for a new attachment.
 * @param bool   $create Add the copy when there is none. False only looks one up:
 *                       the sync on init never sideloads (islamicdilemma_attach_batch() does).
 * @return int Attachment ID, or 0 if there is none (yet) or it could not be added.
 */
function islamicdilemma_theme_attachment( $file, $alt, $create = true ) {
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
	if ( ! $create ) {
		return 0;
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
 * @param string   $html    Block markup with theme image URLs.
 * @param bool     $create  Add missing Media Library copies (cron batches only), or only use existing ones.
 * @param int|null $pending Set to the number of images left at their theme URL.
 * @return string
 */
function islamicdilemma_attach_images( $html, $create = false, &$pending = null ) {
	$pending = 0;
	if ( false === strpos( $html, '<!-- wp:image' ) ) {
		return $html;
	}
	$blocks = islamicdilemma_attach_image_blocks( parse_blocks( $html ), $create, $pending );
	return serialize_blocks( $blocks );
}

/**
 * Recursive helper for islamicdilemma_attach_images().
 *
 * @param array $blocks  Parsed blocks.
 * @param bool  $create  Add missing copies.
 * @param int   $pending Count of images left at their theme URL.
 * @return array
 */
function islamicdilemma_attach_image_blocks( $blocks, $create = false, &$pending = 0 ) {
	$base = untrailingslashit( get_stylesheet_directory_uri() ) . '/assets/img/';
	foreach ( $blocks as &$block ) {
		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = islamicdilemma_attach_image_blocks( $block['innerBlocks'], $create, $pending );
		}
		if ( 'core/image' !== $block['blockName'] || ! empty( $block['attrs']['id'] ) ) {
			continue;
		}
		$markup = $block['innerHTML'];
		if ( ! preg_match( '/<img src="' . preg_quote( $base, '/' ) . '([a-z0-9._-]+)" alt="([^"]*)"/', $markup, $m ) ) {
			continue;
		}
		// An existing copy is free; a batch adds at most
		// islamicdilemma_attach_budget() new ones.
		$alt = html_entity_decode( $m[2], ENT_QUOTES );
		$id  = islamicdilemma_theme_attachment( $m[1], $alt, false );
		// A file whose copy failed lately is tried again a day later, so it
		// cannot spend every batch's budget on itself.
		$failed = 'islamicdilemma_attach_failed_' . md5( $m[1] );
		if ( ! $id && $create && ! get_transient( $failed ) && islamicdilemma_attach_budget() ) {
			$id = islamicdilemma_theme_attachment( $m[1], $alt, true );
			if ( ! $id ) {
				set_transient( $failed, 1, DAY_IN_SECONDS );
			}
		}
		$url = $id ? wp_get_attachment_url( $id ) : '';
		if ( ! $url ) {
			++$pending;
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
		// The dates as they stood before this sync, since a restore from the Trash writes the post first.
		$dates   = $post ? array( $post->post_modified, $post->post_modified_gmt ) : null;

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
			// Everything the write would set is already there: record the
			// theme's copy and leave the post, and its dates, alone.
			if ( islamicdilemma_post_matches_item( $post, $item, $cats ) ) {
				// A restore from the Trash wrote the post already: put its dates back.
				if ( $dates && $post->post_modified_gmt !== $dates[1] ) {
					islamicdilemma_put_modified( $post->ID, $dates[0], $dates[1], $post->post_modified_gmt );
				}
				islamicdilemma_mark_item( $post->ID, $item );
				$registry[ $key ] = $post->ID;
				$report['unchanged'][] = $item['title'];
				continue;
			}
		}

		// The modified date moves only when the text a reader sees changes.
		$keep   = $post && islamicdilemma_item_text_unchanged( $post, $item ) ? $dates : null;
		$result = islamicdilemma_write_item( $item, $post_id ? $post_id : null, $cats, $keep );
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
		$started = time();
		$report  = islamicdilemma_sync_content( $args );
		// For the one-time date repair: the last sync by a version that let
		// writes move the modified date, and when syncs began to keep it.
		if ( empty( $state['dates_kept'] ) ) {
			if ( ! empty( $state['last_sync'] ) ) {
				$state['legacy_sync'] = $state['last_sync'];
			}
			$state['dates_kept'] = $started;
		}
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

/**
 * Once, on the first request after the upgrade to 1.4.9: put back the
 * modified date of items whose only change was a theme write. Before 1.4.9
 * every write by the sync, and every image batch, gave an item the current
 * time, so it showed "Updated" with the day of the sync.
 *
 * An item is repaired when nobody has edited it, its title, excerpt and
 * content match the shipped files, and its modified date falls in or after
 * the last sync run by an earlier version (five minutes' grace, the length
 * of the sync lock). It runs at init 25, before islamicdilemma_maybe_sync(),
 * and a sync by this version records the earlier sync in legacy_sync and
 * its own start in dates_kept. A date from dates_kept on was set by a sync
 * that keeps dates, so a text change this version syncs keeps its new date. The modified date
 * becomes the date in the content file, or the published date when the file
 * has none, and is written only while it still holds the value read here.
 * Every other item keeps its date. The flag records the version that ran
 * the repair, and is set only when every write succeeded.
 */
function islamicdilemma_repair_modified_dates() {
	if ( false !== get_option( ISLAMICDILEMMA_DATES_OPTION, false ) || get_transient( 'islamicdilemma_sync_lock' ) ) {
		return;
	}
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	$fixed  = (array) ( $state['dates_repaired'] ?? array() ); // Kept from a run cut short by a database error.
	$failed = false;
	$synced = empty( $state['dates_kept'] ) ? ( $state['last_sync'] ?? 0 ) : ( $state['legacy_sync'] ?? 0 );
	$until  = empty( $state['dates_kept'] ) ? PHP_INT_MAX : (int) $state['dates_kept'];
	if ( ! empty( $state['initial'] ) && $synced ) {
		$items = islamicdilemma_theme_items();
		if ( ! $items ) {
			return; // A package that does not validate (a partial upload): try again on a later request.
		}
		$since    = (int) $synced - 5 * MINUTE_IN_SECONDS;
		$registry = (array) get_option( ISLAMICDILEMMA_REGISTRY, array() );
		foreach ( $items as $key => $item ) {
			$post = empty( $registry[ $key ] ) ? null : get_post( (int) $registry[ $key ] );
			if ( ! $post || 'trash' === $post->post_status || islamicdilemma_is_edited( $post ) || ! islamicdilemma_post_has_item_text( $post, $item ) ) {
				continue;
			}
			update_post_meta( $post->ID, ISLAMICDILEMMA_HASH_META, islamicdilemma_item_content_hash( $item ) );
			$local = empty( $item['date'] ) ? $post->post_date : $item['date'];
			$gmt   = empty( $item['date'] ) ? $post->post_date_gmt : get_gmt_from_date( $item['date'] );
			$when  = strtotime( $post->post_modified_gmt . ' UTC' );
			if ( false === $when || $when < $since || $when >= $until || $post->post_modified_gmt <= $gmt ) {
				continue;
			}
			// Only while the date is still the one read: an editor's save meanwhile wins.
			$rows = islamicdilemma_put_modified( $post->ID, $local, $gmt, $post->post_modified_gmt );
			if ( false === $rows ) {
				$failed = true; // A database error: the repair runs again on a later request.
			} elseif ( $rows ) {
				$fixed[] = $item['title'];
			}
		}
	}
	if ( ! empty( $state['initial'] ) ) {
		$state['dates_repaired'] = array_values( array_unique( $fixed ) ); // Shown on Appearance, Theme content.
		update_option( ISLAMICDILEMMA_STATE, $state, false );
	}
	if ( $failed ) {
		return; // The flag stays unset; the titles repaired so far are kept for the report.
	}
	update_option( ISLAMICDILEMMA_DATES_OPTION, ISLAMICDILEMMA_VERSION, true ); // Autoloaded: read on every request.
}
add_action( 'init', 'islamicdilemma_repair_modified_dates', 25 );

/**
 * The button on Appearance, Theme content: put back the "Updated" date that
 * an image batch gave to items, for good. Before 1.4.9 every image batch wrote
 * the current time into post_modified, and the one-time repair only reaches
 * dates from the sync it measured against.
 *
 * An item is reset only when nobody has edited it (its stored checksum still
 * matches), its title, excerpt and content match the shipped files, and its
 * modified date falls after its published date and before the first sync that
 * keeps dates (state dates_kept), the time from which a changed date is a real
 * text change. An edited item, an item whose text differs and an item with a
 * later date keep their dates. The date becomes the item's date in the content
 * file, or its published date, and is written only while it still holds the
 * value read here. The result is kept in the content state (dates_reset) for
 * the screen to show.
 *
 * @return array|null The report (reset, kept_edited, kept_other, errors, time), or null while a sync runs or when the package does not validate.
 */
function islamicdilemma_reset_image_batch_dates() {
	if ( get_transient( 'islamicdilemma_sync_lock' ) ) {
		return null;
	}
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	$items = islamicdilemma_theme_items();
	if ( empty( $state['initial'] ) || ! $items ) {
		return null;
	}
	$until    = empty( $state['dates_kept'] ) ? PHP_INT_MAX : (int) $state['dates_kept'];
	$registry = (array) get_option( ISLAMICDILEMMA_REGISTRY, array() );
	$report   = array( 'time' => time(), 'reset' => array(), 'kept_edited' => array(), 'kept_other' => 0, 'errors' => array() );
	foreach ( $items as $key => $item ) {
		$post = empty( $registry[ $key ] ) ? null : get_post( (int) $registry[ $key ] );
		if ( ! $post || 'trash' === $post->post_status ) {
			continue;
		}
		$local = empty( $item['date'] ) ? $post->post_date : $item['date'];
		$gmt   = empty( $item['date'] ) ? $post->post_date_gmt : get_gmt_from_date( $item['date'] );
		$when  = strtotime( $post->post_modified_gmt . ' UTC' );
		if ( false === $when || $post->post_modified_gmt <= $gmt ) {
			continue; // Nothing to put back: the date is the published date.
		}
		if ( islamicdilemma_is_edited( $post ) || ! islamicdilemma_post_has_item_text( $post, $item ) ) {
			$report['kept_edited'][] = $item['title'];
			continue;
		}
		if ( $when >= $until ) {
			++$report['kept_other']; // Set by a sync that keeps dates: a real change of text.
			continue;
		}
		// Only while the date is still the one read: an editor's save meanwhile wins.
		$rows = islamicdilemma_put_modified( $post->ID, $local, $gmt, $post->post_modified_gmt );
		if ( false === $rows ) {
			$report['errors'][] = $item['title'];
		} elseif ( $rows ) {
			$report['reset'][] = $item['title'];
		} else {
			$report['kept_edited'][] = $item['title']; // Saved by an editor while the reset ran.
		}
	}
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	$state['dates_reset'] = $report;
	update_option( ISLAMICDILEMMA_STATE, $state, false );
	return $report;
}
