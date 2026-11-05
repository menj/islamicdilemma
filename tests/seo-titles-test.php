<?php
/**
 * Self-test for the title tags. Run: php tests/seo-titles-test.php
 *
 * Every title tag reads "<page part> | Islamic Dilemma Is No Dilemma" and
 * stays under 60 characters. The check reads the site name from
 * content/settings.json and every page, post and term the theme ships, and
 * fails when one would run over without a short page part in
 * inc/data/seo-titles.json, or when a short part itself is too long or
 * names an item the theme does not ship.
 *
 * @since 1.4.8
 * @package islamicdilemma
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$id_root  = dirname( __DIR__ );
$id_fail  = 0;
$id_short = json_decode( (string) file_get_contents( $id_root . '/inc/data/seo-titles.json' ), true );
$id_short = isset( $id_short['titles'] ) ? $id_short['titles'] : array();
$id_set   = json_decode( (string) file_get_contents( $id_root . '/content/settings.json' ), true );
$id_site  = (string) ( $id_set['blogname'] ?? $id_set['options']['blogname'] ?? '' );
$id_tag   = (string) ( $id_set['blogdescription'] ?? $id_set['options']['blogdescription'] ?? '' );
/** Length in UTF-8 characters, without needing mbstring. */
function id_len( $text ) {
	return (int) preg_match_all( '/./us', (string) $text );
}
$id_room  = 59 - id_len( ' | ' . $id_site );

function id_check( $ok, $label ) {
	global $id_fail;
	if ( ! $ok ) {
		echo "FAIL $label\n";
		++$id_fail;
	}
}

id_check( '' !== $id_site, 'content/settings.json names the site' );
$id_items = array( 'front_page' => '' !== $id_tag ? $id_tag : 'Home' );
foreach ( array( 'pages' => 'page', 'posts' => 'post' ) as $id_dir => $id_type ) {
	foreach ( glob( $id_root . '/content/' . $id_dir . '/*.json' ) as $id_file ) {
		$id_item = json_decode( (string) file_get_contents( $id_file ), true );
		$id_items[ $id_type . ':' . $id_item['slug'] ] = html_entity_decode( $id_item['title'], ENT_QUOTES, 'UTF-8' );
	}
}
$id_terms = json_decode( (string) file_get_contents( $id_root . '/content/terms.json' ), true );
foreach ( array( 'categories' => 'category', 'tags' => 'tag' ) as $id_group => $id_type ) {
	foreach ( (array) ( $id_terms[ $id_group ] ?? array() ) as $id_term ) {
		$id_items[ $id_type . ':' . $id_term['slug'] ] = $id_term['name'];
	}
}

foreach ( $id_short as $id_key => $id_part ) {
	id_check( isset( $id_items[ $id_key ] ), "seo-titles.json names $id_key, which the theme does not ship" );
	id_check( id_len( $id_part ) <= $id_room, "short title for $id_key runs to " . id_len( $id_part ) . " characters, over $id_room" );
}
foreach ( $id_items as $id_key => $id_title ) {
	$id_part = isset( $id_short[ $id_key ] ) ? $id_short[ $id_key ] : $id_title;
	id_check( id_len( $id_part ) <= $id_room, "title of $id_key needs a short page part (" . ( id_len( $id_title ) + 59 - $id_room ) . ' characters)' );
}

if ( $id_fail ) {
	echo "$id_fail failed\n";
	exit( 1 );
}
echo count( $id_items ) . " titles checked: all under 60 characters\n";
