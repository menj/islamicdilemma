<?php
/**
 * Self-test for the contextual links engine and the word count.
 * Run: php tests/article-parts-test.php
 *
 * Loads inc/content/contextual-links.php and inc/content/article-parts.php
 * with small stand-ins for the WordPress functions they call, then checks
 * the rules: whole phrases, first mention, one link a paragraph, never in
 * headings, links, verse cards, notes or Details blocks, never the page
 * itself or an address the text already links to, the per-page cap, and
 * a word count that keeps transliterated words whole.
 *
 * @since 1.4.7
 * @package islamicdilemma
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', '/srv/site/' );
// phpcs:disable
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function apply_filters( $hook, $value ) { return $value; }
function home_url( $path = '' ) { return 'https://islamicdilemma.info' . $path; }
function wp_parse_url( $url, $part = -1 ) { return parse_url( $url, $part ); }
function esc_url( $url ) { return $url; }
function get_stylesheet_directory() { return dirname( __DIR__ ); }
function url_to_postid( $url ) { return strlen( $url ); }
function get_post_status() { return 'publish'; }
function get_permalink( $id ) { return $GLOBALS['id_urls'][ $id ] ?? ''; }
function wp_strip_all_tags( $text ) { return trim( strip_tags( $text ) ); }
function strip_shortcodes( $text ) { return $text; }
function get_post( $post = null ) { return $post; }
// phpcs:enable

$GLOBALS['id_urls'] = array();
foreach ( array( '/injil-where-is-it/', '/islamic-dilemma/', '/what-classical-muslim-scholars-said-about-tahrif/', '/which-bible-one-gospel-many-canons/' ) as $id_path ) {
	$GLOBALS['id_urls'][ strlen( home_url( $id_path ) ) ] = home_url( $id_path );
}
require dirname( __DIR__ ) . '/inc/content/contextual-links.php';
require dirname( __DIR__ ) . '/inc/content/article-parts.php';

$id_fail = 0;
function id_check( $ok, $label ) {
	global $id_fail;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	$id_fail += $ok ? 0 : 1;
}

$id_targets = islamicdilemma_ctx_targets();
id_check( ! empty( $id_targets['patterns'] ) && isset( $id_targets['by']['injil'] ), 'The map compiles' );

$id_here = 'https://islamicdilemma.info/some-article/';
$id_html = '<h2>The Injil</h2><p>The Injil was given to Jesus. The Injil again.</p><p>Taḥrīf and the Islamic Dilemma.</p>';
$id_out  = islamicdilemma_ctx_apply( $id_html, $id_targets, $id_here, 6 );
id_check( 1 === substr_count( $id_out, 'injil-where-is-it' ), 'First mention only, never in a heading' );
id_check( false !== strpos( $id_out, '<h2>The Injil</h2>' ), 'The heading is untouched' );
id_check( 1 === substr_count( islamicdilemma_ctx_apply( '<p>Taḥrīf and the Islamic Dilemma.</p>', $id_targets, $id_here, 6 ), 'href' ), 'One new link a paragraph' );

$id_out = islamicdilemma_ctx_apply( '<p>The Injil.</p>', $id_targets, 'https://islamicdilemma.info/injil-where-is-it/', 6 );
id_check( false === strpos( $id_out, 'href' ), 'A page never links to itself' );

$id_out = islamicdilemma_ctx_apply( '<p>See <a href="/injil-where-is-it/">this</a>.</p><p>The Injil.</p>', $id_targets, $id_here, 6 );
id_check( 1 === substr_count( $id_out, 'injil-where-is-it' ), 'An address the text already links to is not linked again' );

$id_skip = '<div class="wp-block-group is-style-ayah"><p class="ayah-translation">The Injil</p></div><ol class="is-style-notes"><li>Injil</li></ol><details class="is-style-faq"><summary>Injil?</summary><p>The Injil.</p></details><p lang="ar">Injil</p>';
id_check( false === strpos( islamicdilemma_ctx_apply( $id_skip, $id_targets, $id_here, 6 ), 'href' ), 'Verse cards, notes, Details blocks and Arabic take no links' );

id_check( false === strpos( islamicdilemma_ctx_apply( '<p>Injilistic</p>', $id_targets, $id_here, 6 ), 'href' ), 'Whole words only' );
id_check( 1 === substr_count( islamicdilemma_ctx_apply( '<p>The Injil.</p><p>Taḥrīf.</p>', $id_targets, $id_here, 1 ), 'href' ), 'The per-page cap holds' );

$id_post = (object) array( 'post_content' => '<p>Taḥrīf, qirāʾāt and the Qur’an’s word.</p>' );
id_check( 6 === islamicdilemma_word_count( $id_post ), 'Transliterated words count once each' );
id_check( 1 === islamicdilemma_reading_minutes( $id_post ), 'Reading time is at least one minute' );

if ( $id_fail ) {
	echo "$id_fail failed\n";
	exit( 1 );
}
echo "All checks passed\n";
