<?php
/**
 * Superseded content, renames and the sync log.
 *
 * The content manifest may carry a top-level `supersedes` map, old slug => new slug, and an item may carry
 * `former`, a list of the slugs it had before. Both ship empty. While the new item is published, an old item:
 *
 *   - answers with a 301 to the new one (template_redirect, priority 1);
 *   - is left out of lists, search, feeds, the sitemap, llms.txt and the verse index, and asks search engines
 *     not to index it;
 *   - is moved to the Trash by the sync, never deleted, when nobody has edited it.
 *
 * A superseded item that somebody edited is left alone: it is not redirected, hidden or trashed. Nothing the
 * owner created is touched, because only items the theme itself recorded are considered.
 *
 * A renamed item (`former`) keeps its post and its ID: the sync renames the post in place, and the old address
 * answers with a 301.
 *
 * Guards. Removals stop when the content files fail their checksums (the sync then does nothing at all), when
 * the item count dropped by more than 10 percent since the last good sync, or when
 * ISLAMICDILEMMA_DISABLE_SUPERSEDE is true in wp-config.php (which also switches off the redirect and the hiding).
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ISLAMICDILEMMA_LOG_OPTION   = 'islamicdilemma_sync_log';
const ISLAMICDILEMMA_COUNT_OPTION = 'islamicdilemma_manifest_count';

/**
 * The kill switch.
 *
 * @return bool
 */
function islamicdilemma_supersede_disabled() {
	return defined( 'ISLAMICDILEMMA_DISABLE_SUPERSEDE' ) && ISLAMICDILEMMA_DISABLE_SUPERSEDE;
}

/**
 * The supersedes map of the manifest, cleaned: slug => slug, never a slug that replaces itself.
 *
 * @return array<string,string>
 */
function islamicdilemma_supersedes_map() {
	$manifest = islamicdilemma_read_json( 'manifest.json' );
	$map      = array();
	$raw      = is_array( $manifest ) && isset( $manifest['supersedes'] ) ? $manifest['supersedes'] : array();
	foreach ( is_array( $raw ) ? $raw : array() as $old => $new ) {
		$old = sanitize_title( (string) $old );
		$new = sanitize_title( (string) $new );
		if ( '' !== $old && '' !== $new && $old !== $new ) {
			$map[ $old ] = $new;
		}
	}
	return $map;
}

/**
 * Whether an item key names an item the manifest says a newer one replaces.
 *
 * @param string $key Item key, "type:slug".
 * @return bool
 */
function islamicdilemma_is_retired_key( $key ) {
	$slug = (string) substr( (string) $key, (int) strpos( (string) $key, ':' ) + 1 );
	return isset( islamicdilemma_supersedes_map()[ $slug ] );
}

/**
 * Old slug => new slug for the items that were renamed (their `former` lists).
 *
 * @return array<string,string>
 */
function islamicdilemma_former_map() {
	$map = array();
	foreach ( islamicdilemma_theme_items() as $item ) {
		foreach ( (array) ( $item['former'] ?? array() ) as $old ) {
			$old = sanitize_title( (string) $old );
			if ( '' !== $old && $old !== $item['slug'] ) {
				$map[ $old ] = $item['slug'];
			}
		}
	}
	return $map;
}

/**
 * Whether removals may run. Pure.
 *
 * @param bool $disabled    The kill switch.
 * @param int  $count_now   Items in the manifest now.
 * @param int  $count_prior Items at the last good sync, 0 when none.
 * @return string '' when allowed, otherwise the reason.
 */
function islamicdilemma_removal_guard( $disabled, $count_now, $count_prior ) {
	if ( $disabled ) {
		return 'the kill switch ISLAMICDILEMMA_DISABLE_SUPERSEDE is on';
	}
	if ( $count_prior > 0 && $count_now < $count_prior * 0.9 ) {
		return 'the content files list more than 10 percent fewer items than at the last good sync';
	}
	return '';
}

/**
 * The reason removals are stopped now, or ''.
 *
 * @return string
 */
function islamicdilemma_removal_blocked() {
	return islamicdilemma_removal_guard( islamicdilemma_supersede_disabled(), count( islamicdilemma_theme_items() ), (int) get_option( ISLAMICDILEMMA_COUNT_OPTION, 0 ) );
}

/**
 * Record the item count as the baseline for the 10 percent guard. Called after a sync that finished.
 */
function islamicdilemma_record_count() {
	$count = count( islamicdilemma_theme_items() );
	if ( $count > 0 ) {
		update_option( ISLAMICDILEMMA_COUNT_OPTION, $count, false );
	}
}

/**
 * Old post ID => new post ID, for every pair where both are published theme items and the old one has not been
 * edited. Static for one request.
 *
 * @param bool $reset Forget the cached pairs and read them again.
 * @return array<int,int>
 */
function islamicdilemma_superseded_pairs( $reset = false ) {
	static $cache = null;
	if ( $reset ) {
		$cache = null;
	}
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( islamicdilemma_supersede_disabled() ) {
		return $cache;
	}
	$map = islamicdilemma_supersedes_map();
	if ( ! $map ) {
		return $cache;
	}
	$registry = (array) get_option( ISLAMICDILEMMA_REGISTRY, array() );
	foreach ( $map as $old => $new ) {
		foreach ( array( 'post', 'page' ) as $type ) {
			$old_id = isset( $registry[ $type . ':' . $old ] ) ? (int) $registry[ $type . ':' . $old ] : 0;
			$new_id = isset( $registry[ $type . ':' . $new ] ) ? (int) $registry[ $type . ':' . $new ] : 0;
			$old_p  = $old_id ? get_post( $old_id ) : null;
			$new_p  = $new_id ? get_post( $new_id ) : null;
			if ( $old_p && $new_p && 'publish' === $old_p->post_status && 'publish' === $new_p->post_status && $old_id !== $new_id && islamicdilemma_is_unedited( $old_p ) ) {
				$cache[ $old_id ] = $new_id;
			}
		}
	}
	return $cache;
}

/**
 * IDs of the superseded posts.
 *
 * @return int[]
 */
function islamicdilemma_superseded_ids() {
	return array_keys( islamicdilemma_superseded_pairs() );
}

/**
 * Whether a post still holds the text the theme wrote. Fails closed: with no recorded checksum the post counts
 * as edited, so it is never redirected, hidden, renamed or trashed.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function islamicdilemma_is_unedited( $post ) {
	return '' !== (string) get_post_meta( $post->ID, ISLAMICDILEMMA_SUM_META, true ) && ! islamicdilemma_is_edited( $post );
}

/**
 * The 301 from a superseded item to its replacement.
 */
function islamicdilemma_supersede_redirect() {
	if ( islamicdilemma_supersede_disabled() || is_admin() || is_preview() || is_feed() || ! is_singular() ) {
		return;
	}
	$pairs = islamicdilemma_superseded_pairs();
	$id    = (int) get_queried_object_id();
	if ( isset( $pairs[ $id ] ) ) {
		wp_safe_redirect( get_permalink( $pairs[ $id ] ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'islamicdilemma_supersede_redirect', 1 );

/**
 * An old address that is a 404 now (the item is in the Trash, or was renamed) still reaches the new one.
 */
function islamicdilemma_supersede_redirect_gone() {
	if ( islamicdilemma_supersede_disabled() || ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security
	$slug = sanitize_title( basename( $path ) );
	$map  = array_merge( islamicdilemma_former_map(), islamicdilemma_supersedes_map() );
	if ( '' === $slug || ! isset( $map[ $slug ] ) ) {
		return;
	}
	$registry = (array) get_option( ISLAMICDILEMMA_REGISTRY, array() );
	foreach ( array( 'post', 'page' ) as $type ) {
		$id = isset( $registry[ $type . ':' . $map[ $slug ] ] ) ? (int) $registry[ $type . ':' . $map[ $slug ] ] : 0;
		if ( $id && 'publish' === get_post_status( $id ) ) {
			wp_safe_redirect( get_permalink( $id ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'islamicdilemma_supersede_redirect_gone', 2 );

/**
 * Whether superseded items are hidden on this request: the public site, never an editor at work.
 *
 * @return bool
 */
function islamicdilemma_supersede_hides() {
	if ( islamicdilemma_supersede_disabled() ) {
		return false;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) {
		return false;
	}
	if ( wp_doing_ajax() && current_user_can( 'edit_posts' ) ) {
		$referer = (string) wp_get_referer();
		if ( '' !== $referer && 0 === strpos( $referer, admin_url() ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Take the superseded IDs out of a query's include list, or ask for a post that cannot exist when that empties
 * it. WordPress ignores post__not_in whenever post__in is set, so the list is cut itself.
 *
 * @param int[] $in  The include list.
 * @param int[] $ids Superseded IDs.
 * @return int[]|null The new list, or null when the query has no include list.
 */
function islamicdilemma_supersede_cut_includes( $in, $ids ) {
	$in = array_filter( array_map( 'intval', (array) $in ) );
	if ( ! $in ) {
		return null;
	}
	$in = array_values( array_diff( $in, $ids ) );
	return $in ? $in : array( 0 );
}

/**
 * Hide superseded items from lists, search and feeds.
 *
 * @param WP_Query $q Query.
 */
function islamicdilemma_supersede_pre_get_posts( $q ) {
	if ( ! islamicdilemma_supersede_hides() || $q->is_singular() || $q->get( 'p' ) || $q->get( 'page_id' ) || $q->get( 'name' ) || $q->get( 'pagename' ) ) {
		return;
	}
	$ids = islamicdilemma_superseded_ids();
	if ( ! $ids ) {
		return;
	}
	$in = islamicdilemma_supersede_cut_includes( $q->get( 'post__in' ), $ids );
	if ( null !== $in ) {
		$q->set( 'post__in', $in );
		return;
	}
	$q->set( 'post__not_in', array_values( array_unique( array_merge( (array) $q->get( 'post__not_in' ), $ids ) ) ) );
}
add_action( 'pre_get_posts', 'islamicdilemma_supersede_pre_get_posts' );

/**
 * The WordPress sitemap leaves them out.
 *
 * @param array $args Query arguments.
 * @return array
 */
function islamicdilemma_supersede_sitemap( $args ) {
	$ids = islamicdilemma_superseded_ids();
	if ( $ids ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $ids );
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'islamicdilemma_supersede_sitemap' );

/**
 * The page of a superseded item, when it can still be reached (the redirect is switched off or it is a
 * preview), asks not to be indexed.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function islamicdilemma_supersede_robots( $robots ) {
	if ( is_singular() && in_array( (int) get_queried_object_id(), islamicdilemma_superseded_ids(), true ) ) {
		return wp_robots_no_robots( $robots );
	}
	return $robots;
}
add_filter( 'wp_robots', 'islamicdilemma_supersede_robots' );

/**
 * Rank Math, when it is active, leaves them out of its sitemap and marks the page noindex.
 */
add_filter(
	'rank_math/sitemap/posts_to_exclude',
	static function ( $ids ) {
		$ids = is_array( $ids ) ? $ids : preg_split( '/[\s,]+/', (string) $ids, -1, PREG_SPLIT_NO_EMPTY );
		return array_values( array_unique( array_merge( array_filter( array_map( 'absint', $ids ) ), islamicdilemma_superseded_ids() ) ) );
	}
);
add_filter(
	'rank_math/frontend/robots',
	static function ( $robots ) {
		if ( is_singular() && in_array( (int) get_queried_object_id(), islamicdilemma_superseded_ids(), true ) ) {
			$robots['index'] = 'noindex';
		}
		return $robots;
	}
);

/**
 * Append a line to the sync log (the last 100 lines).
 *
 * @param string $title  Item.
 * @param string $action What happened.
 */
function islamicdilemma_log( $title, $action ) {
	$log   = (array) get_option( ISLAMICDILEMMA_LOG_OPTION, array() );
	$log[] = array( 't' => time(), 'item' => (string) $title, 'action' => (string) $action );
	update_option( ISLAMICDILEMMA_LOG_OPTION, array_slice( $log, -100 ), false );
}

/**
 * Rename theme items in place. When an item lists a former slug and the theme recorded a post under it that
 * nobody edited, the post takes the new key and the sync then writes the new slug into it. An edited post is
 * left under its old address and the item is created anew.
 *
 * @param array $items    Items, key => item.
 * @param array $registry Key => post ID.
 * @return array{registry:array,renamed:string[]}
 */
function islamicdilemma_rename_in_place( $items, $registry ) {
	$renamed = array();
	foreach ( $items as $key => $item ) {
		if ( isset( $registry[ $key ] ) ) {
			continue;
		}
		foreach ( (array) ( $item['former'] ?? array() ) as $old ) {
			$old_key = $item['type'] . ':' . sanitize_title( (string) $old );
			if ( ! isset( $registry[ $old_key ] ) || isset( $items[ $old_key ] ) ) {
				continue;
			}
			$post = get_post( (int) $registry[ $old_key ] );
			if ( $post && 'trash' !== $post->post_status && islamicdilemma_is_unedited( $post ) ) {
				$registry[ $key ] = (int) $registry[ $old_key ];
				unset( $registry[ $old_key ] );
				$renamed[] = $post->post_title . ' (' . $old . ' to ' . $item['slug'] . ')';
				break;
			}
		}
	}
	return array( 'registry' => $registry, 'renamed' => $renamed );
}

/**
 * The sync step: move the superseded items whose replacement is published to the Trash, unless a guard stops it.
 *
 * @return array{trashed:string[],failed:string[],blocked:string}
 */
function islamicdilemma_supersede_retire() {
	$out    = array( 'trashed' => array(), 'failed' => array(), 'blocked' => '' );
	$reason = islamicdilemma_removal_blocked();
	if ( '' !== $reason ) {
		$out['blocked'] = $reason;
		update_option( 'islamicdilemma_supersede_blocked', $reason, false );
		return $out;
	}
	delete_option( 'islamicdilemma_supersede_blocked' );
	foreach ( islamicdilemma_superseded_pairs( true ) as $old_id => $new_id ) {
		$post = get_post( $old_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			continue;
		}
		if ( islamicdilemma_trash_post( $old_id ) ) {
			$out['trashed'][] = $post->post_title . ' (replaced by ' . get_post_field( 'post_name', $new_id ) . ')';
			islamicdilemma_log( $post->post_title, 'moved to the Trash, replaced by ' . get_post_field( 'post_name', $new_id ) );
		} else {
			$out['failed'][] = $post->post_title . ': could not move to the Trash.';
		}
	}
	islamicdilemma_superseded_pairs( true );
	return $out;
}

/**
 * A line-by-line diff of an edited post against the text the theme ships, for the Restore flow.
 *
 * @param WP_Post $post Post.
 * @param array   $item Theme item.
 * @return string HTML table, or ''.
 */
function islamicdilemma_text_diff( $post, $item ) {
	$mine  = $post->post_title . "\n\n" . $post->post_content;
	$theme = $item['title'] . "\n\n" . islamicdilemma_fill_placeholders( $item['content'] );
	// One block per line, so the diff reads by block rather than as one long string.
	$mine  = preg_replace( '#(<!-- /wp:[^>]*-->)#', "$1\n", $mine );
	$theme = preg_replace( '#(<!-- /wp:[^>]*-->)#', "$1\n", $theme );
	return (string) wp_text_diff( $mine, $theme, array( 'title_left' => __( 'Your copy', 'islamicdilemma' ), 'title_right' => __( 'The theme\'s copy', 'islamicdilemma' ) ) );
}

/**
 * What the next sync would do with each item, without changing anything.
 *
 * create         not on the site yet
 * current        matches the theme
 * refresh        you have not edited it and the theme changed it
 * kept           you edited it: it stays, and you choose
 * deleted        you deleted it: it stays gone
 * rename         renamed in place, the old address redirects
 * supersede      redirected, hidden and moved to the Trash: its replacement is live
 * supersede-kept replaced, but you edited it, so it stays as it is
 * dropped        no longer in the theme and unedited: moved to the Trash
 * dropped-kept   no longer in the theme but you edited it: it stays
 *
 * @return array<int,array{title:string,key:string,action:string,post_id:int}>
 */
function islamicdilemma_sync_preview() {
	$items    = islamicdilemma_theme_items();
	$registry = (array) get_option( ISLAMICDILEMMA_REGISTRY, array() );
	$rows     = array();
	$ren      = islamicdilemma_rename_in_place( $items, $registry );
	$renamed  = array_diff_key( $ren['registry'], $registry );
	foreach ( $items as $key => $item ) {
		$id     = isset( $registry[ $key ] ) ? (int) $registry[ $key ] : 0;
		$post   = $id ? get_post( $id ) : null;
		$action = 'current';
		if ( isset( $renamed[ $key ] ) ) {
			$action = 'rename';
			$post   = get_post( (int) $renamed[ $key ] );
		} elseif ( ! $id ) {
			$action = 'create';
		} elseif ( ! $post || 'trash' === $post->post_status ) {
			$action = 'deleted';
		} elseif ( ! islamicdilemma_is_unedited( $post ) ) {
			$action = 'kept';
		} elseif ( islamicdilemma_item_source_hash( $item ) !== get_post_meta( $post->ID, ISLAMICDILEMMA_SRC_META, true ) ) {
			$action = 'refresh';
		}
		$rows[] = array( 'title' => (string) $item['title'], 'key' => $key, 'action' => $action, 'post_id' => $post ? (int) $post->ID : 0 );
	}
	$pairs = islamicdilemma_superseded_pairs();
	foreach ( islamicdilemma_supersedes_map() as $old => $new ) {
		foreach ( array( 'post', 'page' ) as $type ) {
			$id   = isset( $registry[ $type . ':' . $old ] ) ? (int) $registry[ $type . ':' . $old ] : 0;
			$post = $id ? get_post( $id ) : null;
			if ( $post && 'trash' !== $post->post_status ) {
				$rows[] = array( 'title' => $post->post_title, 'key' => $type . ':' . $old, 'action' => isset( $pairs[ $id ] ) ? 'supersede' : 'supersede-kept', 'post_id' => $id );
			}
		}
	}
	foreach ( $registry as $key => $id ) {
		if ( isset( $items[ $key ] ) || isset( $renamed[ $key ] ) || in_array( (int) $id, array_map( 'intval', $renamed ), true ) ) {
			continue;
		}
		$post = get_post( (int) $id );
		if ( $post && 'trash' !== $post->post_status && ! isset( $pairs[ (int) $id ] ) && ! in_array( $key, array_column( $rows, 'key' ), true ) ) {
			$rows[] = array( 'title' => $post->post_title, 'key' => $key, 'action' => islamicdilemma_is_unedited( $post ) ? 'dropped' : 'dropped-kept', 'post_id' => (int) $id );
		}
	}
	return $rows;
}

/**
 * The labels of the dry-run table.
 *
 * @return array<string,string>
 */
function islamicdilemma_preview_labels() {
	return array(
		'create'         => __( 'Would be added', 'islamicdilemma' ),
		'current'        => __( 'Matches the theme', 'islamicdilemma' ),
		'refresh'        => __( 'Would take the theme\'s new text, because you have not edited it', 'islamicdilemma' ),
		'kept'           => __( 'Stays as you edited it; compare and choose below', 'islamicdilemma' ),
		'deleted'        => __( 'Stays deleted, because you deleted it', 'islamicdilemma' ),
		'rename'         => __( 'Would be renamed in place; the old address redirects', 'islamicdilemma' ),
		'supersede'      => __( 'Would be redirected, hidden and moved to the Trash, because its replacement is live', 'islamicdilemma' ),
		'supersede-kept' => __( 'Replaced, but you edited it, so it stays as it is', 'islamicdilemma' ),
		'dropped'        => __( 'No longer in the theme; would be moved to the Trash', 'islamicdilemma' ),
		'dropped-kept'   => __( 'No longer in the theme, but you edited it, so it stays', 'islamicdilemma' ),
	);
}

/**
 * The dry-run table, the diff of one edited item and the log, for the Content tab.
 */
function islamicdilemma_render_dry_run() {
	$labels = islamicdilemma_preview_labels();
	$rows   = array_filter( islamicdilemma_sync_preview(), static function ( $r ) {
		return 'current' !== $r['action'];
	} );
	echo '<h2>' . esc_html__( 'Next sync, as a dry run', 'islamicdilemma' ) . '</h2>';
	$blocked = islamicdilemma_removal_blocked();
	if ( '' !== $blocked ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html( sprintf( /* translators: %s: reason */ __( 'Removals are stopped: %s.', 'islamicdilemma' ), $blocked ) ) . '</p></div>';
	}
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'Nothing would change.', 'islamicdilemma' ) . '</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Item', 'islamicdilemma' ) . '</th><th scope="col">' . esc_html__( 'What the sync would do', 'islamicdilemma' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$cell = esc_html( $row['title'] );
			if ( in_array( $row['action'], array( 'kept' ), true ) ) {
				$cell .= ' <a href="' . esc_url( admin_url( 'themes.php?page=islamicdilemma-content&compare=' . rawurlencode( $row['key'] ) ) ) . '">' . esc_html__( 'Compare', 'islamicdilemma' ) . '</a>';
			}
			echo '<tr><td>' . $cell . '</td><td>' . esc_html( $labels[ $row['action'] ] ?? $row['action'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $cell is escaped above.
		}
		echo '</tbody></table>';
	}
	$compare = isset( $_GET['compare'] ) ? sanitize_text_field( wp_unslash( $_GET['compare'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a read-only view.
	$items   = islamicdilemma_theme_items();
	$registry = (array) get_option( ISLAMICDILEMMA_REGISTRY, array() );
	if ( '' !== $compare && isset( $items[ $compare ], $registry[ $compare ] ) ) {
		$post = get_post( (int) $registry[ $compare ] );
		if ( $post ) {
			echo '<h3>' . esc_html( sprintf( /* translators: %s: title */ __( 'Your copy and the theme\'s copy: %s', 'islamicdilemma' ), $items[ $compare ]['title'] ) ) . '</h3>';
			$diff = islamicdilemma_text_diff( $post, $items[ $compare ] );
			echo '' !== $diff ? $diff : '<p>' . esc_html__( 'The two copies hold the same text.', 'islamicdilemma' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_text_diff() returns escaped HTML.
			echo '<p class="description">' . esc_html__( 'To take the theme\'s copy, tick the item in the list above and press "Restore the theme version of the selected items". To keep yours, do nothing.', 'islamicdilemma' ) . '</p>';
		}
	}
	$log = array_reverse( (array) get_option( ISLAMICDILEMMA_LOG_OPTION, array() ) );
	if ( $log ) {
		echo '<h3>' . esc_html__( 'What changed', 'islamicdilemma' ) . '</h3><table class="widefat striped"><tbody>';
		foreach ( array_slice( $log, 0, 20 ) as $line ) {
			echo '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $line['t'] ) ) . '</td><td>' . esc_html( (string) $line['item'] ) . '</td><td>' . esc_html( (string) $line['action'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
}
