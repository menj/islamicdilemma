<?php
/** Standalone regression checks for importer failure paths. Run: php tests/content-sync.php */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
} );
define( 'ABSPATH', __DIR__ . '/' );
define( 'ISLAMICDILEMMA_VERSION', 'test' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'EMPTY_TRASH_DAYS', in_array( '--no-trash', $argv, true ) ? 0 : 30 );
class WP_Error {
	private $message;
	public function __construct( $code, $message ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function add_action() {}
function add_filter( $hook, $callback ) { $GLOBALS['filters'][ $hook ][] = $callback; }
function remove_filter( $hook, $callback ) { $GLOBALS['filters'][ $hook ] = array_values( array_filter( $GLOBALS['filters'][ $hook ] ?? array(), function ( $c ) use ( $callback ) { return $c !== $callback; } ) ); }
function clean_post_cache() {}
/** The database table, as far as the date repair touches it. */
class Fake_WPDB {
	public $posts = 'wp_posts';
	public function update( $table, $data, $where ) {
		if ( ! empty( $GLOBALS['fail_db'] ) && ( true === $GLOBALS['fail_db'] || $GLOBALS['fail_db'] === $where['ID'] ) ) { return false; }
		foreach ( $where as $field => $value ) { if ( (string) $GLOBALS['posts'][ $where['ID'] ]->$field !== (string) $value ) { return 0; } }
		foreach ( $data as $field => $value ) { $GLOBALS['posts'][ $where['ID'] ]->$field = $value; }
		return 1;
	}
}
$wpdb = new Fake_WPDB();
function get_stylesheet_directory() { return $GLOBALS['fixture']; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['locks'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['locks'][ $key ] = $value; }
function delete_transient( $key ) { unset( $GLOBALS['locks'][ $key ] ); }
function apply_filters( $key, $value ) { return $value; }
function get_posts( $args ) {
	if ( empty( $args['meta_query'] ) ) { return array(); }
	return array_keys( array_filter( $GLOBALS['posts'], function ( $p ) { return empty( $GLOBALS['meta'][ $p->ID ][ ISLAMICDILEMMA_KEY_META ] ) && 'trash' !== $p->post_status; } ) );
}
function get_post_field( $field, $id ) { return get_post( $id )->$field ?? ''; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function get_the_title( $id ) { return get_post( $id )->post_title; }
function get_post_type( $id ) { return get_post( $id )->post_type; }
function wp_trash_post( $id ) {
	$GLOBALS['trash_calls']++;
	if ( ! empty( $GLOBALS['fail_trash'] ) ) { return false; }
	$GLOBALS['posts'][ $id ]->post_status = 'trash';
	return get_post( $id );
}
function wp_untrash_post( $id ) { $GLOBALS['posts'][ $id ]->post_status = 'publish'; $GLOBALS['posts'][ $id ]->post_modified = $GLOBALS['posts'][ $id ]->post_modified_gmt = $GLOBALS['now']; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['meta'][ $id ][ $key ] = $value; }
function wp_get_object_terms() { return array(); }
function get_post_thumbnail_id() { return 0; }
function is_sticky() { return false; }
function get_current_user_id() { return 1; }
function user_can() { return true; }
function get_stylesheet_directory_uri() { return 'https://example.test/theme'; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function home_url() { return 'https://example.test'; }
function wp_slash( $value ) { return $value; }
function kses_remove_filters() { $GLOBALS['kses'] = false; }
function kses_init_filters() { $GLOBALS['kses'] = true; }
function get_gmt_from_date( $date ) { return $date; }
function flush_rewrite_rules() {}
function wp_insert_post( $data, $error = false ) {
	if ( ! empty( $GLOBALS['throw_write'] ) ) { throw new RuntimeException( 'write exception' ); }
	if ( ! empty( $GLOBALS['fail_write'] ) ) { return new WP_Error( 'write_failed', 'write failed' ); }
	$GLOBALS['writes']++;
	$update = isset( $data['ID'] );
	$id = $data['ID'] ?? count( $GLOBALS['posts'] ) + 1;
	$defaults = array( 'ID' => $id, 'post_date' => '2026-10-30 08:00:00', 'post_date_gmt' => '2026-10-30 08:00:00', 'post_excerpt' => '', 'post_password' => '', 'menu_order' => 0, 'post_parent' => 0, 'comment_status' => 'closed', 'ping_status' => 'closed' );
	$base = $update ? (array) $GLOBALS['posts'][ $id ] : $defaults;
	$row = array_merge( $base, $data );
	// As WordPress does: an update takes the current time, an insert its publication date.
	$row['post_modified'] = $update ? $GLOBALS['now'] : $row['post_date'];
	$row['post_modified_gmt'] = $update ? $GLOBALS['now'] : $row['post_date_gmt'];
	foreach ( $GLOBALS['filters']['wp_insert_post_data'] ?? array() as $filter ) { $row = $filter( $row, $data ); }
	$GLOBALS['posts'][ $id ] = (object) $row;
	return $id;
}
function wp_update_post( $data, $error = false ) { return wp_insert_post( $data, $error ); }
function sanitize_title( $t ) { return strtolower( preg_replace( '/[^A-Za-z0-9_-]+/', '-', trim( (string) $t ) ) ); }
require dirname( __DIR__ ) . '/inc/content/content-sync.php';
require dirname( __DIR__ ) . '/inc/content/supersede.php';
$checks = 0;
function check( $ok, $message ) {
	$GLOBALS['checks']++;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
function islamicdilemma_log_last() { $l = (array) ( $GLOBALS['options'][ ISLAMICDILEMMA_LOG_OPTION ] ?? array() ); return $l ? end( $l ) : array(); }
function put_json( $file, $value ) { file_put_contents( $GLOBALS['fixture'] . '/content/' . $file, json_encode( $value ) ); }
function manifest() {
	$m = array( 'pages' => array( 'pages/home.json', 'pages/enrichment.json' ), 'posts' => array( 'posts/article.json' ), 'terms' => 'terms.json', 'settings' => 'settings.json', 'checksums' => array() );
	foreach ( array_merge( $m['pages'], $m['posts'], array( $m['terms'], $m['settings'] ) ) as $file ) { $m['checksums'][ $file ] = md5_file( $GLOBALS['fixture'] . '/content/' . $file ); }
	put_json( 'manifest.json', $m );
}
function reset_fixture() {
	$GLOBALS['options'] = array(); $GLOBALS['locks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array();
	$GLOBALS['trash_calls'] = 0; $GLOBALS['writes'] = 0; $GLOBALS['fail_db'] = false; $GLOBALS['filters'] = array(); $GLOBALS['now'] = '2026-11-04 09:00:00'; $GLOBALS['fail_write'] = false; $GLOBALS['throw_write'] = false; $GLOBALS['fail_trash'] = false; $GLOBALS['kses'] = true;
	foreach ( array( 'home', 'enrichment' ) as $slug ) { put_json( 'pages/' . $slug . '.json', array( 'slug' => $slug, 'title' => $slug, 'content' => 'Original' ) ); }
	put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Article', 'content' => 'Original' ) );
	put_json( 'terms.json', array( 'categories' => array(), 'tags' => array() ) );
	put_json( 'settings.json', array( 'front_page' => 'home', 'posts_page' => 'enrichment', 'posts_per_page' => 10 ) );
	manifest();
}
$fixture = sys_get_temp_dir() . '/islamicdilemma-sync-' . uniqid();
$fixture_dir = $fixture;
mkdir( $fixture . '/content/pages', 0777, true ); mkdir( $fixture . '/content/posts' );
try {
	// Also validate every real shipped file before using synthetic failure fixtures.
	$fixture = dirname( __DIR__ );
	check( true === islamicdilemma_validate_content(), 'Repository content package must validate.' );
	$fixture = $fixture_dir;
	reset_fixture();
	if ( ! EMPTY_TRASH_DAYS ) {
		check( false === islamicdilemma_trash_post( 1 ), 'Disabled Trash must not call permanent deletion.' );
		check( 0 === $trash_calls, 'Core trash fallback must not be called.' );
	} else {
		check( true === islamicdilemma_validate_content(), 'Valid fixture.' );
		$before = islamicdilemma_content_stamp();
		$file = $fixture . '/content/posts/article.json'; $mtime = filemtime( $file ); $size = filesize( $file );
		file_put_contents( $file, str_replace( 'Original', 'Modified', file_get_contents( $file ) ) ); touch( $file, $mtime ); clearstatcache();
		check( filesize( $file ) === $size && filemtime( $file ) === $mtime, 'Same-size, same-time deployment fixture.' );
		manifest();
		check( $before !== islamicdilemma_content_stamp(), 'Byte changes must be detected even with preserved timestamps.' );
		$before = islamicdilemma_content_fingerprint();
		$m = islamicdilemma_read_json( 'manifest.json' ); $m['version'] = 2; put_json( 'manifest.json', $m );
		check( $before !== islamicdilemma_content_fingerprint(), 'Manifest-only changes must be detected.' );
		reset_fixture();
		unlink( $fixture . '/content/posts/article.json' );
		$r = islamicdilemma_run_sync( array( 'initial' => true ) );
		check( ! empty( $r['errors'] ) && ! $posts && 0 === $trash_calls, 'Missing file must abort before writes or cleanup.' );
		check( empty( $options[ ISLAMICDILEMMA_STATE ]['initial'] ) && empty( $locks ), 'Failed first import stays pending and releases lock.' );
		reset_fixture(); file_put_contents( $fixture . '/content/posts/article.json', '{broken' );
		check( is_wp_error( islamicdilemma_validate_content() ), 'Invalid JSON must reject the package.' );
		reset_fixture(); put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Changed', 'content' => 'Changed' ) );
		check( is_wp_error( islamicdilemma_validate_content() ), 'Valid JSON from an incomplete deployment must fail its checksum.' );
		reset_fixture(); $fail_write = true;
		$r = islamicdilemma_run_sync();
		check( count( $r['errors'] ) === 3 && empty( $options[ ISLAMICDILEMMA_STATE ]['initial'] ), 'Database errors must not mark package complete.' );
		$fail_write = false; $r = islamicdilemma_run_sync();
		check( empty( $r['errors'] ) && 3 === count( $r['created'] ) && $options['page_on_front'], 'Failed first import must be retryable with initial settings.' );
		$id = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'];
		$posts[ $id ]->post_content = 'Owner edit';
		$r = islamicdilemma_run_sync(); check( in_array( 'Article', $r['kept_edited'], true ), 'Owner edits survive a sync.' );
		$posts[ $id ]->post_status = 'trash'; $r = islamicdilemma_run_sync();
		check( in_array( 'Article', $r['kept_deleted'], true ), 'Owner deletions survive a sync.' );
		reset_fixture(); $throw_write = true;
		try { islamicdilemma_run_sync(); } catch ( RuntimeException $e ) { check( 'write exception' === $e->getMessage(), 'Expected injected write exception.' ); }
		check( $kses && empty( $locks ), 'Exception must restore KSES and release sync lock.' );
		reset_fixture(); islamicdilemma_run_sync(); $saved = $options[ ISLAMICDILEMMA_STATE ];
		$m = islamicdilemma_read_json( 'manifest.json' ); $m['posts'] = array(); put_json( 'manifest.json', $m ); $fail_trash = true;
		$r = islamicdilemma_run_sync();
		check( ! empty( $r['errors'] ) && isset( $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'] ), 'Failed trash must retain registry entry for retry.' );
		check( $saved['fingerprint'] === $options[ ISLAMICDILEMMA_STATE ]['fingerprint'], 'Failure must not advance successful fingerprint.' );
		$fail_trash = false; unset( $options[ ISLAMICDILEMMA_COUNT_OPTION ] ); $r = islamicdilemma_run_sync();
		check( empty( $r['errors'] ) && 1 === count( $r['trashed'] ), 'Removal retries successfully.' );
		reset_fixture(); islamicdilemma_run_sync();
		put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Revised', 'content' => 'Revised' ) ); manifest();
		set_transient( 'islamicdilemma_sync_lock', 1, 300 ); $saved = $options[ ISLAMICDILEMMA_STATE ];
		islamicdilemma_maybe_sync(); check( $saved === $options[ ISLAMICDILEMMA_STATE ], 'Lock contention must not swallow a pending update.' );
		delete_transient( 'islamicdilemma_sync_lock' ); islamicdilemma_maybe_sync();
		check( in_array( 'Revised', $options[ ISLAMICDILEMMA_STATE ]['report']['updated'], true ), 'Pending update must run after lock release.' );

		// The "Updated" date: only a change to the text a reader sees moves it.
		reset_fixture(); islamicdilemma_run_sync();
		$id = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article']; $home = $options[ ISLAMICDILEMMA_REGISTRY ]['page:home'];
		check( '2026-10-30 08:00:00' === $posts[ $id ]->post_modified, 'A new item starts with its publication date as its modified date.' );
		check( islamicdilemma_item_content_hash( islamicdilemma_theme_items()['post:article'] ) === $meta[ $id ][ ISLAMICDILEMMA_HASH_META ], 'The sync stores the text hash.' );
		put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Article', 'content' => 'Original', 'status' => 'publish' ) ); manifest();
		$now = '2026-11-05 09:00:00'; $writes = 0; $r = islamicdilemma_run_sync();
		check( 0 === $writes && in_array( 'Article', $r['unchanged'], true ), 'A package change that leaves the post as it is writes nothing.' );
		$posts[ $id ]->post_author = 7; put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Article', 'content' => 'Original' ) ); manifest();
		islamicdilemma_run_sync();
		check( 1 === $writes && 1 === $posts[ $id ]->post_author && '2026-10-30 08:00:00' === $posts[ $id ]->post_modified, 'A different author is written, as before, and the date stays.' );
		$writes = 0;
		check( '2026-10-30 08:00:00' === $posts[ $id ]->post_modified, 'An item the sync did not write keeps its modified date.' );
		put_json( 'pages/home.json', array( 'slug' => 'home', 'title' => 'home', 'content' => 'Original', 'menu_order' => 4 ) ); manifest();
		$r = islamicdilemma_run_sync();
		check( 1 === $writes && 4 === $posts[ $home ]->menu_order && in_array( 'home', $r['updated'], true ), 'A change outside the text is written.' );
		check( '2026-10-30 08:00:00' === $posts[ $home ]->post_modified && '2026-10-30 08:00:00' === $posts[ $home ]->post_modified_gmt, 'A write that leaves the text keeps both modified dates.' );
		check( empty( $filters['wp_insert_post_data'] ), 'The date filter is removed after the write, so editor saves move the date.' );
		put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Article', 'content' => 'Revised text' ) ); manifest();
		islamicdilemma_run_sync();
		check( '2026-11-05 09:00:00' === $posts[ $id ]->post_modified, 'A change to the text moves the modified date.' );
		unset( $meta[ $home ][ ISLAMICDILEMMA_HASH_META ] );
		put_json( 'pages/home.json', array( 'slug' => 'home', 'title' => 'home', 'content' => 'Original', 'menu_order' => 5 ) ); manifest();
		$now = '2026-11-06 09:00:00'; islamicdilemma_run_sync();
		check( '2026-10-30 08:00:00' === $posts[ $home ]->post_modified, 'Without a stored hash the text is compared field by field.' );

		// The one-time repair after the upgrade.
		reset_fixture(); islamicdilemma_run_sync();
		$reg = $options[ ISLAMICDILEMMA_REGISTRY ];
		$synced = strtotime( '2026-11-04 09:00:00 UTC' ); $options[ ISLAMICDILEMMA_STATE ]['last_sync'] = $synced; unset( $options[ ISLAMICDILEMMA_STATE ]['dates_kept'] ); // As an earlier version left it.
		foreach ( $reg as $post_id ) { unset( $meta[ $post_id ][ ISLAMICDILEMMA_HASH_META ] ); $posts[ $post_id ]->post_modified = $posts[ $post_id ]->post_modified_gmt = '2026-11-04 08:59:30'; }
		$posts[ $reg['page:enrichment'] ]->post_modified = $posts[ $reg['page:enrichment'] ]->post_modified_gmt = '2026-11-01 10:00:00';
		$posts[ $reg['page:home'] ]->post_content = 'Owner edit';
		$fail_db = $reg['post:article']; $posts[ $reg['page:enrichment'] ]->post_modified_gmt = '2026-11-04 08:59:30'; islamicdilemma_repair_modified_dates();
		check( ! isset( $options[ ISLAMICDILEMMA_DATES_OPTION ] ) && array( 'enrichment' ) === $options[ ISLAMICDILEMMA_STATE ]['dates_repaired'], 'A failed date write leaves the repair to run again and keeps what it repaired.' );
		$fail_db = false; $posts[ $reg['page:enrichment'] ]->post_modified = $posts[ $reg['page:enrichment'] ]->post_modified_gmt = '2026-11-01 10:00:00';
		set_transient( 'islamicdilemma_sync_lock', 1, 300 ); islamicdilemma_repair_modified_dates();
		check( ! isset( $options[ ISLAMICDILEMMA_DATES_OPTION ] ), 'The repair waits while a sync runs.' );
		delete_transient( 'islamicdilemma_sync_lock' ); islamicdilemma_repair_modified_dates();
		check( '2026-10-30 08:00:00' === $posts[ $reg['post:article'] ]->post_modified && '2026-10-30 08:00:00' === $posts[ $reg['post:article'] ]->post_modified_gmt, 'The repair puts a synced item back to its published date.' );
		check( '2026-11-01 10:00:00' === $posts[ $reg['page:enrichment'] ]->post_modified, 'The repair leaves a date from before the last sync.' );
		check( '2026-11-04 08:59:30' === $posts[ $reg['page:home'] ]->post_modified, 'The repair leaves an edited item.' );
		check( array( 'enrichment', 'Article' ) === $options[ ISLAMICDILEMMA_STATE ]['dates_repaired'] && 'test' === $options[ ISLAMICDILEMMA_DATES_OPTION ], 'The repair reports what it did and records its version.' );
		check( ! empty( $meta[ $reg['page:enrichment'] ][ ISLAMICDILEMMA_HASH_META ] ), 'The repair stores the text hash of every unedited item.' );
		$posts[ $reg['post:article'] ]->post_modified = '2026-11-04 09:00:00'; islamicdilemma_repair_modified_dates();
		check( '2026-11-04 09:00:00' === $posts[ $reg['post:article'] ]->post_modified, 'The repair runs only once.' );
		reset_fixture(); islamicdilemma_repair_modified_dates();
		check( isset( $options[ ISLAMICDILEMMA_DATES_OPTION ] ) && ! isset( $options[ ISLAMICDILEMMA_STATE ] ), 'A new site only records the flag.' );
		// A sync by this version before the repair: its real text change keeps its new date.
		reset_fixture(); islamicdilemma_run_sync(); $id = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'];
		$old = $options[ ISLAMICDILEMMA_STATE ]['last_sync'] = strtotime( '2026-11-01 00:00:00 UTC' ); unset( $options[ ISLAMICDILEMMA_STATE ]['dates_kept'] );
		put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Article', 'content' => 'New text' ) ); manifest();
		$now = gmdate( 'Y-m-d H:i:s' ); islamicdilemma_run_sync(); islamicdilemma_repair_modified_dates();
		check( $old === $options[ ISLAMICDILEMMA_STATE ]['legacy_sync'] && $now === $posts[ $id ]->post_modified, 'A text change synced by this version keeps its new date, even when the repair runs after it.' );
		// The button on Theme content: image-batch dates go, edits and later changes stay.
		reset_fixture(); islamicdilemma_run_sync(); $reg = $options[ ISLAMICDILEMMA_REGISTRY ];
		$options[ ISLAMICDILEMMA_STATE ]['dates_kept'] = strtotime( '2026-11-04 12:00:00 UTC' );
		foreach ( $reg as $post_id ) { $posts[ $post_id ]->post_modified = $posts[ $post_id ]->post_modified_gmt = '2026-11-03 10:00:00'; }
		$posts[ $reg['page:home'] ]->post_content = 'Owner edit';
		$posts[ $reg['page:enrichment'] ]->post_modified = $posts[ $reg['page:enrichment'] ]->post_modified_gmt = '2026-11-05 10:00:00';
		set_transient( 'islamicdilemma_sync_lock', 1, 300 );
		check( null === islamicdilemma_reset_image_batch_dates() && '2026-11-03 10:00:00' === $posts[ $reg['post:article'] ]->post_modified, 'The reset waits while a sync runs.' );
		delete_transient( 'islamicdilemma_sync_lock' );
		$fail_db = $reg['post:article']; $r = islamicdilemma_reset_image_batch_dates();
		check( array( 'Article' ) === $r['errors'] && array() === $r['reset'], 'A failed date write is reported and nothing else changes for that item.' );
		$fail_db = false; $r = islamicdilemma_reset_image_batch_dates();
		check( array( 'Article' ) === $r['reset'] && '2026-10-30 08:00:00' === $posts[ $reg['post:article'] ]->post_modified && '2026-10-30 08:00:00' === $posts[ $reg['post:article'] ]->post_modified_gmt, 'The reset puts an unedited item back to its published date.' );
		check( array( 'home' ) === $r['kept_edited'] && '2026-11-03 10:00:00' === $posts[ $reg['page:home'] ]->post_modified, 'The reset never touches an edited item.' );
		check( 1 === $r['kept_other'] && '2026-11-05 10:00:00' === $posts[ $reg['page:enrichment'] ]->post_modified, 'The reset keeps a date set by a sync that keeps dates.' );
		check( $options[ ISLAMICDILEMMA_STATE ]['dates_reset']['reset'] === array( 'Article' ), 'The reset stores what it changed for the screen.' );
		$r = islamicdilemma_reset_image_batch_dates();
		check( array() === $r['reset'], 'A second run changes nothing.' );
		// A restore from the Trash of an item that matches keeps its dates.
		reset_fixture(); islamicdilemma_run_sync(); $id = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'];
		$posts[ $id ]->post_status = 'trash'; $now = '2026-11-07 09:00:00'; $writes = 0;
		$r = islamicdilemma_run_sync( array( 'restore' => array( 'post:article' ) ) );
		check( 0 === $writes && 'publish' === $posts[ $id ]->post_status && '2026-10-30 08:00:00' === $posts[ $id ]->post_modified_gmt, 'A restore from the Trash that matches keeps the dates it had.' );
		// An editor save between the read and the write wins.
		reset_fixture(); islamicdilemma_run_sync(); $id = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'];
		unset( $options[ ISLAMICDILEMMA_STATE ]['dates_kept'] ); $options[ ISLAMICDILEMMA_STATE ]['last_sync'] = strtotime( '2026-11-04 09:00:00 UTC' );
		$posts[ $id ]->post_modified = $posts[ $id ]->post_modified_gmt = '2026-11-04 09:00:00';
		check( 0 === $wpdb->update( 'wp_posts', array( 'post_modified' => 'x' ), array( 'ID' => $id, 'post_modified_gmt' => '2026-11-04 08:00:00' ) ), 'The date write matches the date it read.' );
		$p = (object) array( 'post_title' => 'T', 'post_excerpt' => '', 'post_content' => 'Hello {{home_url}}' );
		check( ! islamicdilemma_post_has_item_text( $p, array( 'title' => 'T', 'content' => 'Hello {{home_url}}' ) ) && islamicdilemma_post_has_item_text( (object) array( 'post_title' => 'T', 'post_excerpt' => '', 'post_content' => 'Hello https://example.test' ), array( 'title' => 'T', 'content' => 'Hello {{home_url}}' ) ), 'Text is compared with its placeholders filled.' );
	}

		// Superseded items, renames, guards and the log.
		$two = function () {
			put_json( 'posts/old.json', array( 'slug' => 'old', 'title' => 'Old', 'content' => 'Old text' ) );
			put_json( 'posts/new.json', array( 'slug' => 'new', 'title' => 'New', 'content' => 'New text' ) );
			$m = array( 'pages' => array( 'pages/home.json', 'pages/enrichment.json' ), 'posts' => array( 'posts/article.json', 'posts/old.json', 'posts/new.json' ), 'terms' => 'terms.json', 'settings' => 'settings.json', 'checksums' => array(), 'supersedes' => array( 'old' => 'new' ) );
			foreach ( array_merge( $m['pages'], $m['posts'], array( $m['terms'], $m['settings'] ) ) as $file ) { $m['checksums'][ $file ] = md5_file( $GLOBALS['fixture'] . '/content/' . $file ); }
			put_json( 'manifest.json', $m );
		};
		reset_fixture(); $two();
		$r = islamicdilemma_run_sync();
		$oid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:old']; $nid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:new'];
		check( 'trash' === $posts[ $oid ]->post_status && 'publish' === $posts[ $nid ]->post_status, 'A superseded item goes to the Trash once its replacement is live.' );
		check( 1 === count( $r['trashed'] ) && false !== strpos( $r['trashed'][0], 'replaced by new' ), 'The report names the replacement.' );
		check( 'moved to the Trash, replaced by new' === islamicdilemma_log_last()['action'], 'The log records the retirement.' );
		check( empty( $r['kept_deleted'] ), 'A retired item is not reported as deleted by the owner.' );
		$r = islamicdilemma_run_sync();
		check( empty( $r['kept_deleted'] ) && empty( $r['trashed'] ), 'A later sync stays quiet about a retired item.' );
		check( array( 'old' => 'new' ) === islamicdilemma_supersedes_map(), 'The map is read and cleaned.' );
		// An edited old item is left alone.
		reset_fixture(); $two();
		$m = islamicdilemma_read_json( 'manifest.json' ); $map = $m['supersedes']; unset( $m['supersedes'] ); put_json( 'manifest.json', $m );
		islamicdilemma_run_sync(); $oid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:old'];
		$posts[ $oid ]->post_content = 'Owner edit'; $m['supersedes'] = $map; put_json( 'manifest.json', $m );
		$r = islamicdilemma_run_sync();
		check( 'publish' === $posts[ $oid ]->post_status && in_array( 'Old', $r['kept_edited'], true ), 'An edited superseded item is not trashed.' );
		check( ! in_array( $oid, islamicdilemma_superseded_pairs( true ) ? array_keys( islamicdilemma_superseded_pairs( true ) ) : array(), true ), 'An edited superseded item is not redirected or hidden.' );
		unset( $meta[ $oid ][ ISLAMICDILEMMA_SUM_META ] );
		check( ! islamicdilemma_is_unedited( $posts[ $oid ] ) && islamicdilemma_is_edited( $posts[ $oid ] ), 'A post with no recorded checksum counts as edited.' );
		check( array( 0 ) === islamicdilemma_supersede_cut_includes( array( 5 ), array( 5 ) ) && array( 7 ) === islamicdilemma_supersede_cut_includes( array( 5, 7 ), array( 5 ) ) && null === islamicdilemma_supersede_cut_includes( array(), array( 5 ) ), 'A post__in list is cut, not extended with post__not_in.' );
		// Rename in place.
		reset_fixture(); islamicdilemma_run_sync(); $aid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'];
		put_json( 'posts/article.json', array( 'slug' => 'essay', 'title' => 'Article', 'content' => 'Original', 'former' => array( 'article' ) ) );
		$m = islamicdilemma_read_json( 'manifest.json' ); $m['checksums']['posts/article.json'] = md5_file( $fixture . '/content/posts/article.json' ); put_json( 'manifest.json', $m );
		$r = islamicdilemma_run_sync();
		check( $aid === $options[ ISLAMICDILEMMA_REGISTRY ]['post:essay'] && ! isset( $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'] ) && 'essay' === $posts[ $aid ]->post_name, 'An unedited item is renamed in place and keeps its post.' );
		check( 1 === count( $r['renamed'] ) && empty( $r['trashed'] ), 'A rename trashes nothing.' );
		check( 'essay' === islamicdilemma_former_map()['article'], 'The old address maps to the new one.' );
		// An edited item is not renamed.
		reset_fixture(); islamicdilemma_run_sync(); $aid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article']; $posts[ $aid ]->post_content = 'Owner edit';
		put_json( 'posts/article.json', array( 'slug' => 'essay', 'title' => 'Article', 'content' => 'Original', 'former' => array( 'article' ) ) );
		$m = islamicdilemma_read_json( 'manifest.json' ); $m['checksums']['posts/article.json'] = md5_file( $fixture . '/content/posts/article.json' ); put_json( 'manifest.json', $m );
		$r = islamicdilemma_run_sync();
		check( 'article' === $posts[ $aid ]->post_name && 'Owner edit' === $posts[ $aid ]->post_content && empty( $r['renamed'] ), 'An edited item keeps its address and its text.' );
		// Guards.
		check( '' === islamicdilemma_removal_guard( false, 90, 100 ) && '' === islamicdilemma_removal_guard( false, 91, 100 ), 'A drop of up to 10 percent passes the guard.' );
		check( '' !== islamicdilemma_removal_guard( false, 89, 100 ) && '' !== islamicdilemma_removal_guard( true, 100, 100 ) && '' === islamicdilemma_removal_guard( false, 5, 0 ), 'A bigger drop, or the kill switch, stops removals; no baseline allows them.' );
		reset_fixture(); islamicdilemma_run_sync(); $aid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article']; $options[ ISLAMICDILEMMA_COUNT_OPTION ] = 100;
		$m = islamicdilemma_read_json( 'manifest.json' ); $m['posts'] = array(); put_json( 'manifest.json', $m );
		$r = islamicdilemma_run_sync();
		check( 'publish' === $posts[ $aid ]->post_status && isset( $options[ ISLAMICDILEMMA_REGISTRY ]['post:article'] ) && ! empty( $r['errors'] ), 'A big drop in the item count stops the removal of dropped items.' );
		// The log keeps the last 100 lines.
		for ( $i = 0; $i < 130; $i++ ) { islamicdilemma_log( 'x' . $i, 'test' ); }
		check( 100 === count( $options[ ISLAMICDILEMMA_LOG_OPTION ] ) && 'x129' === islamicdilemma_log_last()['item'], 'The log keeps the last 100 lines.' );
		// The dry run says what a sync would do and changes nothing.
		reset_fixture(); islamicdilemma_run_sync(); $aid = $options[ ISLAMICDILEMMA_REGISTRY ]['post:article']; $posts[ $aid ]->post_content = 'Owner edit';
		$writes = 0; $by = array_column( islamicdilemma_sync_preview(), 'action', 'key' );
		check( 'kept' === $by['post:article'] && 'current' === $by['page:home'] && 0 === $writes, 'The dry run marks an edited item as kept and writes nothing.' );
		// The kill switch.
		define( 'ISLAMICDILEMMA_DISABLE_SUPERSEDE', true );
		check( islamicdilemma_supersede_disabled() && array() === islamicdilemma_superseded_pairs( true ) && ! islamicdilemma_supersede_hides(), 'The kill switch ends the redirect and the hiding.' );
	echo $checks . " importer regression checks passed.\n";
} finally {
	$fixture = $fixture_dir;
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $fixture, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $iterator as $entry ) { $entry->isDir() ? rmdir( $entry->getPathname() ) : unlink( $entry->getPathname() ); }
	rmdir( $fixture );
}
