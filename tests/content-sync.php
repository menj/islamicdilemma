<?php
/** Standalone regression checks for importer failure paths. Run: php tests/content-sync.php */
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
function get_stylesheet_directory() { return $GLOBALS['fixture']; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['locks'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['locks'][ $key ] = $value; }
function delete_transient( $key ) { unset( $GLOBALS['locks'][ $key ] ); }
function apply_filters( $key, $value ) { return $value; }
function get_posts( $args ) {
	if ( empty( $args['meta_query'] ) ) { return array(); }
	return array_keys( array_filter( $GLOBALS['posts'], function ( $p ) { return empty( $GLOBALS['meta'][ $p->ID ][ ISLAMICDILEMMA_KEY_META ] ) && 'trash' !== $p->post_status; } ) );
}
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function get_the_title( $id ) { return get_post( $id )->post_title; }
function get_post_type( $id ) { return get_post( $id )->post_type; }
function wp_trash_post( $id ) {
	$GLOBALS['trash_calls']++;
	if ( ! empty( $GLOBALS['fail_trash'] ) ) { return false; }
	$GLOBALS['posts'][ $id ]->post_status = 'trash';
	return get_post( $id );
}
function wp_untrash_post( $id ) { $GLOBALS['posts'][ $id ]->post_status = 'publish'; }
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
	$id = $data['ID'] ?? count( $GLOBALS['posts'] ) + 1;
	$defaults = array( 'ID' => $id, 'post_date' => '2026-10-30', 'post_password' => '', 'menu_order' => 0, 'post_parent' => 0, 'comment_status' => 'closed', 'ping_status' => 'closed' );
	$GLOBALS['posts'][ $id ] = (object) array_merge( $defaults, $data );
	return $id;
}
function wp_update_post( $data, $error = false ) { return wp_insert_post( $data, $error ); }
require dirname( __DIR__ ) . '/inc/content/content-sync.php';
$checks = 0;
function check( $ok, $message ) {
	$GLOBALS['checks']++;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
function put_json( $file, $value ) { file_put_contents( $GLOBALS['fixture'] . '/content/' . $file, json_encode( $value ) ); }
function manifest() {
	$m = array( 'pages' => array( 'pages/home.json', 'pages/enrichment.json' ), 'posts' => array( 'posts/article.json' ), 'terms' => 'terms.json', 'settings' => 'settings.json', 'checksums' => array() );
	foreach ( array_merge( $m['pages'], $m['posts'], array( $m['terms'], $m['settings'] ) ) as $file ) { $m['checksums'][ $file ] = md5_file( $GLOBALS['fixture'] . '/content/' . $file ); }
	put_json( 'manifest.json', $m );
}
function reset_fixture() {
	$GLOBALS['options'] = array(); $GLOBALS['locks'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['meta'] = array();
	$GLOBALS['trash_calls'] = 0; $GLOBALS['fail_write'] = false; $GLOBALS['throw_write'] = false; $GLOBALS['fail_trash'] = false; $GLOBALS['kses'] = true;
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
		$fail_trash = false; $r = islamicdilemma_run_sync();
		check( empty( $r['errors'] ) && 1 === count( $r['trashed'] ), 'Removal retries successfully.' );
		reset_fixture(); islamicdilemma_run_sync();
		put_json( 'posts/article.json', array( 'slug' => 'article', 'title' => 'Revised', 'content' => 'Revised' ) ); manifest();
		set_transient( 'islamicdilemma_sync_lock', 1, 300 ); $saved = $options[ ISLAMICDILEMMA_STATE ];
		islamicdilemma_maybe_sync(); check( $saved === $options[ ISLAMICDILEMMA_STATE ], 'Lock contention must not swallow a pending update.' );
		delete_transient( 'islamicdilemma_sync_lock' ); islamicdilemma_maybe_sync();
		check( in_array( 'Revised', $options[ ISLAMICDILEMMA_STATE ]['report']['updated'], true ), 'Pending update must run after lock release.' );
	}
	echo $checks . " importer regression checks passed.\n";
} finally {
	$fixture = $fixture_dir;
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $fixture, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $iterator as $entry ) { $entry->isDir() ? rmdir( $entry->getPathname() ) : unlink( $entry->getPathname() ); }
	rmdir( $fixture );
}
