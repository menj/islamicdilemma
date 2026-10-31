<?php
/** Run with wp eval-file on a disposable WordPress installation only. */
function id_check( $condition, $message ) {
	if ( ! $condition ) { WP_CLI::error( $message ); }
	WP_CLI::log( 'PASS: ' . $message );
}
$state = get_option( ISLAMICDILEMMA_STATE );
id_check( ! empty( $state['initial'] ) && empty( $state['report']['errors'] ), 'Activation completes without import errors' );
$items = islamicdilemma_theme_items();
$registry = get_option( ISLAMICDILEMMA_REGISTRY );
id_check( count( $items ) === count( $registry ), 'All shipped items imported' );
foreach ( $registry as $key => $id ) {
	$post = get_post( $id );
	id_check( $post && ! islamicdilemma_is_edited( $post ), 'Imported checksum matches: ' . $key );
}
id_check( 'trash' === get_post_status( 1 ), 'First activation moves default post to Trash' );
$r = islamicdilemma_run_sync();
id_check( empty( $r['created'] ) && empty( $r['updated'] ) && empty( $r['errors'] ), 'Second sync is idempotent' );
$id = $registry['page:about'];
wp_update_post( array( 'ID' => $id, 'post_content' => 'Owner revision' ) );
$owner = wp_insert_post( array( 'post_title' => 'Owner post', 'post_status' => 'publish' ) );
$r = islamicdilemma_run_sync();
id_check( 'Owner revision' === get_post( $id )->post_content, 'Owner edits survive sync' );
id_check( 'publish' === get_post_status( $owner ), 'Owner-created posts survive later sync' );
wp_trash_post( $id );
islamicdilemma_run_sync();
id_check( 'trash' === get_post_status( $id ), 'Deleted theme content stays deleted' );
$r = islamicdilemma_run_sync( array( 'restore' => array( 'page:about' ) ) );
id_check( empty( $r['errors'] ) && 'publish' === get_post_status( $id ) && ! islamicdilemma_is_edited( get_post( $id ) ), 'Explicit restore reinstates theme content' );
id_check( (int) get_option( 'page_on_front' ) === (int) $registry['page:home'], 'Front page configured' );
id_check( (int) get_option( 'page_for_posts' ) === (int) $registry['page:enrichment'], 'Posts page configured' );
WP_CLI::success( 'WordPress integration checks passed.' );
$taharif = get_term_by( 'slug', 'tahrif', 'post_tag' );
$entry   = $taharif ? islamicdilemma_term_entry( $taharif ) : array();
id_check( ! empty( $entry['definition'] ) && 'term' === $entry['entry_type'], 'Tags carry glossary entry data' );
$empty = get_term_by( 'slug', 'al-zabur', 'post_tag' );
id_check( $empty && 0 === (int) $empty->count && '' !== get_term_meta( $empty->term_id, 'id_definition', true ), 'Glossary-only tags exist without articles' );
id_check( false !== strpos( do_shortcode( '[islamicdilemma_glossary]' ), 'al-Zabūr' ), 'Glossary lists tags that no article uses yet' );
wp_insert_term( 'Owner tag', 'post_tag', array( 'slug' => 'owner-tag' ) );
$r = islamicdilemma_run_sync();
id_check( ! get_term_by( 'slug', 'owner-tag', 'post_tag' ) && in_array( 'Owner tag', $r['removed_tags'], true ), 'Tags outside the theme are removed on sync' );
id_check( (bool) get_term_by( 'slug', 'tahrif', 'post_tag' ), 'Theme tags survive the tag cleanup' );
update_option( ISLAMICDILEMMA_REMOVE_TAGS_OPTION, '0' );
wp_insert_term( 'Kept tag', 'post_tag', array( 'slug' => 'kept-tag' ) );
islamicdilemma_run_sync();
id_check( (bool) get_term_by( 'slug', 'kept-tag', 'post_tag' ), 'Tag cleanup can be switched off' );
delete_option( ISLAMICDILEMMA_REMOVE_TAGS_OPTION );
wp_delete_term( get_term_by( 'slug', 'kept-tag', 'post_tag' )->term_id, 'post_tag' );
$pages = array( 'glossary', 'reading-list' );
foreach ( $pages as $slug ) {
	id_check( (bool) get_page_by_path( $slug ), 'Page exists: ' . $slug );
}
$site = get_bloginfo( 'name' );
/** Point the main query at a URL, as the front end would. Plain query strings work with any permalink setting. */
function id_go_to( $query ) {
	$_GET = array();
	parse_str( $query, $_GET );
	$_SERVER['REQUEST_URI'] = '/?' . $query;
	foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'page', 'pages', 'multipage', 'more', 'numpages' ) as $var ) {
		unset( $GLOBALS[ $var ] );
	}
	$GLOBALS['wp']->init();
	$GLOBALS['wp']->parse_request();
	$GLOBALS['wp']->query_posts();
	$GLOBALS['wp']->register_globals();
}
foreach ( array( 'pagename=glossary', '', 'tag=tahrif', 's=tahrif', 'p=99999999' ) as $query ) {
	id_go_to( $query );
	$title = html_entity_decode( wp_get_document_title(), ENT_QUOTES, 'UTF-8' );
	id_check( 1 === preg_match( '/^.+ \\| ' . preg_quote( $site, '/' ) . '$/u', $title ), 'Title follows "<page> | <site>" for ?' . $query . ': ' . $title );
}
