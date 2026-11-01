<?php
/**
 * Extended schema.org structured data.
 *
 * islamicdilemma_schema_graph() in seo.php builds the core graph (WebSite, Person, WebPage, Article,
 * BreadcrumbList, ImageObject). This module adds a node for every kind of content the theme prints:
 * quotations, verse cards, hadith, footnote citations, tables, lists, images, sections, the FAQ, the glossary,
 * the reading list, the verse index, the timeline, the testimonies, the contact page, the header, footer and
 * navigation, search results and archives. Every type and property is checked against the schema.org
 * vocabulary by tools/verify/schema_check.py.
 *
 * @package islamicdilemma
 */

/** Bump to rebuild cached page nodes after a change to this file. */
const ISLAMICDILEMMA_SCHEMA_VERSION = 4;

/**
 * Collapse whitespace in a DOM node's text.
 *
 * @param DOMNode|null $node Node.
 * @return string
 */
function islamicdilemma_schema_text( $node ) {
	return $node ? trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ) : '';
}

/**
 * Load rendered block HTML into an XPath object.
 *
 * @param string $html HTML.
 * @return DOMXPath
 */
function islamicdilemma_schema_xpath( $html ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?><body>' . $html . '</body>' );
	libxml_clear_errors();
	return new DOMXPath( $dom );
}

/**
 * XPath expression for an element that has a CSS class.
 *
 * @param string $tag   Tag name or *.
 * @param string $class Class.
 * @return string
 */
function islamicdilemma_schema_has_class( $tag, $class ) {
	return sprintf( '//%s[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $tag, $class );
}

/**
 * The two scriptures cited across the site, as Book nodes.
 *
 * @param string $kind quran|bible.
 * @return array
 */
function islamicdilemma_schema_scripture( $kind ) {
	if ( 'quran' === $kind ) {
		return array(
			'@type'      => 'Book',
			'@id'        => home_url( '/#quran' ),
			'name'       => 'The Qur’an',
			'inLanguage' => 'ar',
			'sameAs'     => 'https://en.wikipedia.org/wiki/Quran',
		);
	}
	return array(
		'@type'      => 'Book',
		'@id'        => home_url( '/#bible' ),
		'name'       => 'The Bible',
		'inLanguage' => array( 'en', 'he', 'grc' ),
		'sameAs'     => 'https://en.wikipedia.org/wiki/Bible',
	);
}

/**
 * Turn the references found in a text into CreativeWork nodes that point at the right scripture.
 *
 * @param string $text   Reference text such as "Q 5:48 · 15:9" or "Matt 23:3".
 * @param array  $scrips Scripture nodes already needed, by kind (passed by reference).
 * @return array List of nodes.
 */
function islamicdilemma_schema_ref_nodes( $text, &$scrips ) {
	$nodes = array();
	foreach ( islamicdilemma_parse_refs( $text ) as $ref ) {
		if ( 'Q' === $ref[0] ) {
			$scrips['quran'] = true;
			$label           = sprintf( 'Qur’an %d:%d', $ref[1], $ref[2] );
			$nodes[ $label ] = array( '@type' => 'CreativeWork', 'name' => $label, 'isPartOf' => array( '@id' => home_url( '/#quran' ) ), 'position' => $ref[1] . ':' . $ref[2], 'inLanguage' => 'ar' );
		} else {
			$scrips['bible'] = true;
			$label           = $ref[1][0] . ' ' . $ref[2];
			$nodes[ $label ] = array( '@type' => 'CreativeWork', 'name' => $label, 'isPartOf' => array( '@id' => home_url( '/#bible' ) ), 'position' => $ref[2] );
		}
	}
	return array_values( $nodes );
}

/**
 * Nodes derived from a page's rendered blocks.
 *
 * @param WP_Post $post Post.
 * @param string  $url  Permalink.
 * @return array { nodes, parts, mentions, citations, main_entity }
 */
function islamicdilemma_schema_content( $post, $url ) {
	$out = array( 'nodes' => array(), 'parts' => array(), 'mentions' => array(), 'citations' => array(), 'scrips' => array(), 'faq' => array(), 'cards' => array(), 'timeline' => array(), 'portraits' => array() );
	if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
		return $out; // Without the DOM extension the page keeps the core graph and loses only these extra nodes.
	}
	$html  = do_blocks( $post->post_content );
	$xp    = islamicdilemma_schema_xpath( $html );
	$home  = home_url( '/' );
	$web   = $url . '#webpage';
	$push  = function ( $node, $part = true ) use ( &$out ) {
		$out['nodes'][] = $node;
		if ( $part && ! empty( $node['@id'] ) ) {
			$out['parts'][] = array( '@id' => $node['@id'] );
		}
	};

	// Sections: every heading that has an anchor.
	foreach ( $xp->query( '//h2[@id]|//h3[@id]' ) as $i => $h ) {
		$push( array(
			'@type'       => 'WebPageElement',
			'@id'         => $url . '#section-' . $h->getAttribute( 'id' ),
			'name'        => islamicdilemma_schema_text( $h ),
			'cssSelector' => '#' . $h->getAttribute( 'id' ),
			'isPartOf'    => array( '@id' => $web ),
		) );
	}

	// Block quotations.
	foreach ( $xp->query( '//blockquote' ) as $i => $q ) {
		$cite = islamicdilemma_schema_text( $xp->query( './/cite', $q )->item( 0 ) );
		$body = '';
		foreach ( $xp->query( './/p', $q ) as $p ) {
			$body .= ( $body ? ' ' : '' ) . islamicdilemma_schema_text( $p );
		}
		if ( '' === $body ) {
			continue;
		}
		$node = array( '@type' => 'Quotation', '@id' => $url . '#quote-' . ( $i + 1 ), 'text' => $body, 'inLanguage' => 'en' );
		if ( $cite ) {
			$node['citation'] = $cite;
			$refs             = islamicdilemma_schema_ref_nodes( $cite, $out['scrips'] );
			if ( $refs ) {
				$node['isPartOf'] = $refs[0]['isPartOf'];
				$node['name']     = $refs[0]['name'];
			}
		}
		$push( $node );
	}

	// Qur'an verse cards: Arabic text with Yusuf Ali's translation.
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'div', 'is-style-ayah' ) ) as $i => $card ) {
		$arabic = islamicdilemma_schema_text( $xp->query( './/p[contains(@class,"is-style-arabic")]', $card )->item( 0 ) );
		$tr     = trim( islamicdilemma_schema_text( $xp->query( './/p[contains(@class,"ayah-translation")]', $card )->item( 0 ) ), "“” " );
		$ref    = islamicdilemma_schema_text( $xp->query( './/p[contains(@class,"is-style-verse-ref")]', $card )->item( 0 ) );
		if ( ! $arabic ) {
			continue;
		}
		$node = array(
			'@type'      => 'Quotation',
			'@id'        => $url . '#verse-' . ( $i + 1 ),
			'text'       => $arabic,
			'inLanguage' => 'ar',
			'isPartOf'   => array( '@id' => $home . '#quran' ),
		);
		$out['scrips']['quran'] = true;
		if ( $ref ) {
			$node['name']     = $ref;
			$node['position'] = preg_replace( '/^Q\s*/', '', $ref );
		}
		if ( $tr ) {
			$node['workTranslation'] = array( '@type' => 'Quotation', 'text' => $tr, 'inLanguage' => 'en', 'translator' => array( '@type' => 'Person', 'name' => 'A. Yusuf Ali' ) );
		}
		$push( $node );
	}

	// Hadith quotations.
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'p', 'is-style-hadith' ) ) as $i => $h ) {
		$push( array( '@type' => 'Quotation', '@id' => $url . '#hadith-' . ( $i + 1 ), 'text' => islamicdilemma_schema_text( $h ), 'inLanguage' => 'ar' ) );
	}

	// Inline references become mentions of a verse of scripture.
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'span', 'ref' ) ) as $r ) {
		foreach ( islamicdilemma_schema_ref_nodes( islamicdilemma_schema_text( $r ), $out['scrips'] ) as $m ) {
			$out['mentions'][ $m['name'] ] = $m;
		}
	}

	// Footnotes and outside links are citations.
	foreach ( $xp->query( '//*[contains(@class,"wp-block-footnotes")]//li' ) as $li ) {
		$text = islamicdilemma_schema_text( $li );
		$text = trim( preg_replace( '/\s*↩︎?\s*$/u', '', $text ) );
		if ( $text ) {
			$out['citations'][ $text ] = $text;
		}
	}
	foreach ( $xp->query( '//a[starts-with(@href,"http")]' ) as $a ) {
		$href = $a->getAttribute( 'href' );
		if ( 0 === strpos( $href, $home ) ) {
			continue;
		}
		$out['citations'][ $href ] = array( '@type' => 'CreativeWork', 'name' => islamicdilemma_schema_text( $a ), 'url' => $href );
	}

	// Tables.
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'figure', 'wp-block-table' ) ) as $i => $t ) {
		$head = array();
		foreach ( $xp->query( './/th', $t ) as $th ) {
			$head[] = islamicdilemma_schema_text( $th );
		}
		$prev = $xp->query( 'preceding::h2[1]', $t )->item( 0 );
		$push( array(
			'@type'       => 'Table',
			'@id'         => $url . '#table-' . ( $i + 1 ),
			'name'        => $prev ? islamicdilemma_schema_text( $prev ) : get_the_title( $post ),
			'description' => $head ? 'Columns: ' . implode( '; ', $head ) . '.' : '',
			'xpath'       => sprintf( '(//figure[contains(concat(" ", normalize-space(@class), " "), " wp-block-table ")])[%d]', $i + 1 ),
			'isPartOf'    => array( '@id' => $web ),
		) );
	}

	// Plain lists of three or more items.
	foreach ( $xp->query( '//ul[not(contains(@class,"timeline"))]|//ol' ) as $i => $list ) {
		$items = array();
		foreach ( $xp->query( './li', $list ) as $n => $li ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $n + 1, 'name' => wp_html_excerpt( islamicdilemma_schema_text( $li ), 160, '…' ) );
		}
		if ( count( $items ) < 3 || count( $items ) > 30 ) {
			continue;
		}
		// An ItemList is not a CreativeWork, so the page mentions it instead of listing it as a part.
		$list_id = $url . '#list-' . ( $i + 1 );
		$push( array( '@type' => 'ItemList', '@id' => $list_id, 'itemListOrder' => 'ol' === $list->nodeName ? 'https://schema.org/ItemListOrderAscending' : 'https://schema.org/ItemListUnordered', 'numberOfItems' => count( $items ), 'itemListElement' => $items ), false );
		$out['mentions'][ $list_id ] = array( '@id' => $list_id );
	}

	// Images.
	$credits = islamicdilemma_image_credits();
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'figure', 'wp-block-image' ) ) as $i => $fig ) {
		$img = $xp->query( './/img', $fig )->item( 0 );
		if ( ! $img ) {
			continue;
		}
		$src  = $img->getAttribute( 'src' );
		$file = basename( wp_parse_url( $src, PHP_URL_PATH ) );
		$file = preg_replace( '/-\d+x\d+(\.\w+)$/', '$1', $file );
		$size = array( 0, 0 );
		$aid  = attachment_url_to_postid( $src );
		if ( $aid ) {
			$meta = wp_get_attachment_metadata( $aid );
			$size = array( (int) ( $meta['width'] ?? 0 ), (int) ( $meta['height'] ?? 0 ) );
		}
		$node = islamicdilemma_image_node( array( 'url' => $src, 'width' => $size[0], 'height' => $size[1], 'alt' => $img->getAttribute( 'alt' ), 'file' => $file ), $url . '#image-' . ( $i + 1 ) );
		if ( ! $size[0] ) {
			unset( $node['width'], $node['height'] );
		}
		$node['name']           = $img->getAttribute( 'alt' );
		$node['encodingFormat'] = 'image/' . ( preg_match( '/\.avif$/', $file ) ? 'avif' : 'webp' );
		$cap                    = islamicdilemma_schema_text( $xp->query( './/figcaption', $fig )->item( 0 ) );
		if ( $cap ) {
			$node['caption'] = $cap;
		}
		if ( isset( $credits[ $file ]['credit'] ) ) {
			$node['copyrightNotice'] = $credits[ $file ]['credit'];
		}
		$node['isPartOf'] = array( '@id' => $web );
		$push( $node );
	}

	// FAQ items and question cards (kept for the page-specific nodes below).
	$out['faq'] = array();
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'details', 'faq-item' ) ) as $d ) {
		$q = islamicdilemma_schema_text( $xp->query( './/summary', $d )->item( 0 ) );
		$a = islamicdilemma_schema_text( $xp->query( './/p', $d )->item( 0 ) );
		if ( $q && $a ) {
			$out['faq'][] = array( $q, $a );
		}
	}
	$out['cards'] = array();
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'div', 'question-card' ) ) as $c ) {
		$out['cards'][] = array( preg_replace( '/^\d+\.\s*/', '', islamicdilemma_schema_text( $xp->query( './/h3', $c )->item( 0 ) ) ), islamicdilemma_schema_text( $xp->query( './/p', $c )->item( 0 ) ) );
	}
	$out['timeline'] = array();
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'ul', 'timeline' ) . '/li' ) as $li ) {
		$out['timeline'][] = array( islamicdilemma_schema_text( $xp->query( './/strong', $li )->item( 0 ) ), islamicdilemma_schema_text( $xp->query( './/span[contains(@class,"tl-title")]', $li )->item( 0 ) ), islamicdilemma_schema_text( $li ) );
	}
	$out['portraits'] = array();
	foreach ( $xp->query( islamicdilemma_schema_has_class( 'figure', 'portrait' ) ) as $fig ) {
		$img     = $xp->query( './/img', $fig )->item( 0 );
		$section = '';
		for ( $sib = $fig->nextSibling; $sib && 'h2' !== $sib->nodeName; $sib = $sib->nextSibling ) {
			$section .= ' ' . $sib->textContent;
		}
		$out['portraits'][] = array( $img ? $img->getAttribute( 'alt' ) : '', islamicdilemma_schema_text( $xp->query( './/figcaption', $fig )->item( 0 ) ), $img ? $img->getAttribute( 'src' ) : '', trim( $section ) );
	}
	return $out;
}

/**
 * Header, footer and navigation as WPHeader, WPFooter and SiteNavigationElement nodes.
 *
 * @param string $url Page URL.
 * @return array { header, footer, nav }
 */
function islamicdilemma_schema_chrome( $url ) {
	$home   = home_url( '/' );
	$links  = array();
	$header = (string) @file_get_contents( get_theme_file_path( 'parts/header.html' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( preg_match_all( '/"label":"([^"]+)","url":"([^"]+)"/', $header, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			$links[] = array( '@type' => 'SiteNavigationElement', 'name' => html_entity_decode( $x[1], ENT_QUOTES, 'UTF-8' ), 'url' => home_url( $x[2] ) );
		}
	}
	$footer = (string) @file_get_contents( get_theme_file_path( 'parts/footer.html' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$flinks = array();
	if ( preg_match_all( '#<a href="(/[^"]*)">([^<]+)</a>#', $footer, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			$flinks[ $x[1] ] = array( '@type' => 'SiteNavigationElement', 'name' => html_entity_decode( $x[2], ENT_QUOTES, 'UTF-8' ), 'url' => home_url( $x[1] ) );
		}
	}
	return array(
		array(
			'@type'       => 'WPHeader',
			'@id'         => $url . '#header',
			'cssSelector' => 'header.wp-block-template-part',
			'isPartOf'    => array( '@id' => $url . '#webpage' ),
			'hasPart'     => array(
				array( '@type' => 'WebPageElement', '@id' => $url . '#brand', 'name' => get_bloginfo( 'name' ), 'cssSelector' => '.brand', 'url' => $home ),
				array( '@type' => 'SiteNavigationElement', '@id' => $url . '#main-nav', 'name' => 'Main navigation', 'cssSelector' => '.main-nav', 'hasPart' => $links ),
			),
		),
		array(
			'@type'       => 'WPFooter',
			'@id'         => $url . '#footer',
			'cssSelector' => 'footer.wp-block-template-part',
			'isPartOf'    => array( '@id' => $url . '#webpage' ),
			'hasPart'     => array_values( $flinks ),
		),
	);
}

/**
 * Works by the author that the site is built on.
 *
 * @return array Nodes.
 */
function islamicdilemma_schema_works() {
	if ( ! islamicdilemma_has_author() ) {
		return array();
	}
	$owner = array( '@id' => home_url( '/#author' ) );
	return array(
		array(
			'@type'         => 'ScholarlyArticle',
			'@id'           => home_url( '/#study' ),
			'name'          => 'Reconsidering the “Islamic Dilemma”',
			'url'           => ISLAMICDILEMMA_PAPER_URL,
			'author'        => $owner,
			'datePublished' => '2026-05-01',
			'dateModified'  => '2026-10-04',
			'inLanguage'    => 'en',
			'description'   => 'A 68-page study of the Islamic Dilemma through Qur’anic vocabulary, classical tafsīr, late antique scriptural history and biblical textual criticism. This site is built on it.',
			'about'         => array( '@type' => 'Thing', 'name' => 'Taḥrīf' ),
			'isAccessibleForFree' => true,
		),
		array(
			'@type'         => 'Book',
			'@id'           => home_url( '/#paulus' ),
			'name'          => 'Paulus: Perosak Risalah al-Masīḥ',
			'alternativeHeadline' => 'Sejarah Bagaimana Ajaran Kristian Dicipta Sepenuhnya',
			'author'        => $owner,
			'datePublished' => '2025',
			'inLanguage'    => 'ms',
			'publisher'     => array( '@type' => 'Organization', 'name' => 'Langgam Fikir' ),
		),
	);
}

/**
 * Person nodes for the testimonies page, from the portraits and their captions.
 *
 * @param array  $portraits List of [alt, caption, src].
 * @param string $url       Page URL.
 * @return array Nodes.
 */
function islamicdilemma_schema_people( $portraits, $url ) {
	$people = array();
	foreach ( $portraits as $i => $p ) {
		// Captions read "Name. Public domain.", "Name (d. 2016). Public domain." or "Name. Photo: ...".
		$name = preg_match( '/^(.+?)(?:\s*\(d\.\s*\d{4}\))?\.\s*(?:Public domain|Photo:)/u', $p[1], $m ) ? trim( $m[1] ) : trim( $p[1] );
		$node    = array(
			'@type'       => 'Person',
			'@id'         => $url . '#person-' . ( $i + 1 ),
			'name'        => $name,
			'description' => $p[0],
			'image'       => array( '@type' => 'ImageObject', 'url' => $p[2], 'contentUrl' => $p[2], 'caption' => $p[0] ),
			'subjectOf'   => array( '@id' => $url . '#webpage' ),
		);
		// The death year comes from the caption, "(d. 2016)", or from the account's own words, "He died in 2020".
		if ( preg_match( '/\(d\. (\d{4})\)/', $p[1], $d ) || preg_match( '/\bdied (?:in )?(?:\w+ )?(\d{4})/', $p[3] ?? '', $d ) ) {
			$node['deathDate'] = $d[1];
		}
		$people[] = $node;
	}
	return $people;
}

/**
 * Glossary: a DefinedTermSet of every tag that has a definition.
 *
 * @param string $url Page URL.
 * @return array Nodes.
 */
function islamicdilemma_schema_glossary( $url ) {
	$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false, 'meta_query' => array( array( 'key' => 'id_definition', 'value' => '', 'compare' => '!=' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	if ( is_wp_error( $tags ) || ! $tags ) {
		return array();
	}
	$set   = array( '@type' => 'DefinedTermSet', '@id' => $url . '#glossary', 'name' => 'Glossary', 'url' => $url, 'inLanguage' => 'en', 'hasDefinedTerm' => array() );
	$nodes = array();
	foreach ( $tags as $tag ) {
		$entry = islamicdilemma_term_entry( $tag );
		$node  = islamicdilemma_schema_term_node( $entry, $tag );
		$node['inDefinedTermSet'] = array( '@id' => $set['@id'] );
		$set['hasDefinedTerm'][]  = array( '@id' => $node['@id'] );
		$nodes[]                  = $node;
	}
	array_unshift( $nodes, $set );
	return $nodes;
}

/**
 * One tag as a DefinedTerm.
 *
 * @param array   $entry Entry data.
 * @param WP_Term $tag   Tag.
 * @return array
 */
function islamicdilemma_schema_term_node( $entry, $tag ) {
	$link = get_term_link( $tag );
	$node = array(
		'@type'       => 'DefinedTerm',
		'@id'         => $link . '#term',
		'name'        => $tag->name,
		'url'         => $link,
		'description' => wp_strip_all_tags( preg_replace( '/\{([^}]+)\}/', '$1', $entry['definition'] ) ),
		'termCode'    => $tag->slug,
	);
	if ( ! empty( $entry['arabic'] ) ) {
		$node['alternateName'] = $entry['arabic'];
	}
	if ( ! empty( $entry['category'] ) ) {
		$node['disambiguatingDescription'] = $entry['category'];
	}
	return $node;
}

/**
 * Reading list: books and articles with their authors.
 *
 * @param string $url Page URL.
 * @return array Nodes.
 */
function islamicdilemma_schema_reading_list( $url ) {
	$items = islamicdilemma_tool_data( 'reading-list.json' );
	if ( ! $items ) {
		return array();
	}
	$list  = array( '@type' => 'ItemList', '@id' => $url . '#reading-list', 'name' => 'Reading list', 'numberOfItems' => count( $items ), 'itemListElement' => array() );
	$nodes = array();
	foreach ( $items as $i => $it ) {
		$type = 'Article' === $it['kind'] ? 'ScholarlyArticle' : ( 'Dissertation' === $it['kind'] ? 'Thesis' : 'Book' );
		$work = array(
			'@type'         => $type,
			'@id'           => $url . '#work-' . ( $i + 1 ),
			'name'          => $it['title'],
			'author'        => array( '@type' => 'Person', 'name' => $it['author'] ),
			'datePublished' => $it['year'],
			'description'   => $it['note'],
			'genre'         => $it['cat'],
		);
		if ( ! empty( $it['url'] ) ) {
			$work['url'] = $it['url'];
		}
		$nodes[]                  = $work;
		$list['itemListElement'][] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => array( '@id' => $work['@id'] ) );
	}
	array_unshift( $nodes, $list );
	return $nodes;
}

/**
 * Verse index: the chapters and books the site cites.
 *
 * @param string $url Page URL.
 * @return array Nodes.
 */
function islamicdilemma_schema_verse_index( $url ) {
	$data  = islamicdilemma_collect_refs();
	$names = islamicdilemma_sura_names();
	$items = array();
	foreach ( array_keys( $data['quran'] ) as $sura ) {
		$items[] = sprintf( 'Qur’an, sūra %d, %s', $sura, $names[ $sura - 1 ] ?? '' );
	}
	foreach ( $data['bible'] as $book ) {
		$items[] = 'Bible, ' . $book['name'];
	}
	if ( ! $items ) {
		return array();
	}
	$elements = array();
	foreach ( $items as $i => $name ) {
		$elements[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $name );
	}
	return array( array( '@type' => 'ItemList', '@id' => $url . '#cited', 'name' => 'Scripture cited on this site', 'numberOfItems' => count( $elements ), 'itemListElement' => $elements ) );
}

/**
 * Posts in the current loop as an ItemList.
 *
 * @param string $url Page URL.
 * @return array Nodes.
 */
function islamicdilemma_schema_loop( $url ) {
	global $wp_query;
	$elements = array();
	foreach ( (array) $wp_query->posts as $i => $p ) {
		$elements[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $p ), 'name' => get_the_title( $p ) );
	}
	if ( ! $elements ) {
		return array();
	}
	return array( array( '@type' => 'ItemList', '@id' => $url . '#items', 'numberOfItems' => count( $elements ), 'itemListElement' => $elements ) );
}

/**
 * Add the extended nodes to the core graph.
 *
 * @param array $graph Core graph.
 * @return array
 */
function islamicdilemma_schema_extend( $graph ) {
	$home  = home_url( '/' );
	$owner = islamicdilemma_has_author() ? array( '@id' => $home . '#author' ) : null;

	// Site-wide additions.
	foreach ( $graph as &$node ) {
		if ( isset( $node['@type'] ) && 'WebSite' === $node['@type'] ) {
			$node['potentialAction']    = array( '@type' => 'SearchAction', 'target' => array( '@type' => 'EntryPoint', 'urlTemplate' => $home . '?s={search_term_string}' ), 'query-input' => 'required name=search_term_string' );
			$node['isAccessibleForFree'] = true;
			$node['audience']           = array( '@type' => 'Audience', 'audienceType' => 'Muslims and Christians reading about the Islamic Dilemma' );
			$node['keywords']           = 'Islamic Dilemma, taḥrīf, Injil, Qur’an, Bible, muhaymin';
			$node['accessMode']         = array( 'textual', 'visual' );
			$node['accessibilityFeature'] = array( 'alternativeText', 'structuralNavigation', 'readingOrder' );
			$node['copyrightYear']      = (int) gmdate( 'Y' );
			if ( islamicdilemma_has_author() ) {
				$node['isBasedOn'] = array( '@id' => $home . '#study' );
			}
		}
		if ( isset( $node['@type'] ) && 'Person' === $node['@type'] && ( $node['@id'] ?? '' ) === $home . '#author' ) {
			$node['contactPoint'] = array( '@type' => 'ContactPoint', 'contactType' => 'editorial', 'url' => home_url( '/contact/' ), 'availableLanguage' => array( 'English', 'Malay' ) );
		}
	}
	unset( $node );
	foreach ( islamicdilemma_schema_works() as $work ) {
		$graph[] = $work;
	}

	// Search results.
	if ( is_search() ) {
		$url   = get_search_link();
		$page  = array(
			'@type'    => array( 'WebPage', 'SearchResultsPage' ),
			'@id'      => $url . '#webpage',
			'url'      => $url,
			'name'     => sprintf( 'Search results for “%s”', get_search_query() ),
			'isPartOf' => array( '@id' => $home . '#website' ),
		);
		$extra = array_merge( islamicdilemma_schema_loop( $url ), islamicdilemma_schema_chrome( $url ) );
		foreach ( islamicdilemma_schema_chrome( $url ) as $n ) {
			$page['hasPart'][] = array( '@id' => $n['@id'] );
		}
		return array_merge( $graph, array( $page ), $extra );
	}

	// Find the page node the core graph built.
	$index = null;
	foreach ( $graph as $i => $node ) {
		if ( isset( $node['@id'] ) && '#webpage' === substr( $node['@id'], -8 ) ) {
			$index = $i;
		}
	}
	if ( null === $index ) {
		return $graph;
	}
	$url  = substr( $graph[ $index ]['@id'], 0, -8 );
	$page = $graph[ $index ];
	$types = (array) $page['@type'];
	$post  = is_singular() ? get_queried_object() : null;
	$slug  = $post ? $post->post_name : '';
	$extra = array();

	// Site chrome on every page.
	foreach ( islamicdilemma_schema_chrome( $url ) as $n ) {
		$extra[]            = $n;
		$page['hasPart'][] = array( '@id' => $n['@id'] );
	}

	// Archives and the posts page list their posts.
	if ( ! $post ) {
		foreach ( islamicdilemma_schema_loop( $url ) as $n ) {
			$extra[]            = $n;
			$page['mainEntity'] = array( '@id' => $n['@id'] );
		}
		if ( is_tag() ) {
			$tag   = get_queried_object();
			$entry = islamicdilemma_term_entry( $tag );
			if ( ! empty( $entry['definition'] ) ) {
				$term                 = islamicdilemma_schema_term_node( $entry, $tag );
				$term['inDefinedTermSet'] = array( '@id' => home_url( '/glossary/' ) . '#glossary' );
				$extra[]              = array( '@type' => 'DefinedTermSet', '@id' => home_url( '/glossary/' ) . '#glossary', 'name' => 'Glossary', 'url' => home_url( '/glossary/' ) );
				$extra[]              = $term;
				$page['about']        = array( '@id' => $term['@id'] );
			}
		}
	}

	// Page-specific nodes.
	if ( $post ) {
		$key  = 'idschema_' . $post->ID . '_' . strtotime( $post->post_modified_gmt ) . '_' . ISLAMICDILEMMA_SCHEMA_VERSION;
		$data = get_transient( $key );
		if ( ! is_array( $data ) ) {
			$data = islamicdilemma_schema_content( $post, $url );
			set_transient( $key, $data, DAY_IN_SECONDS );
		}
		foreach ( $data['nodes'] as $n ) {
			$extra[] = $n;
		}
		foreach ( $data['parts'] as $part ) {
			$page['hasPart'][] = $part;
		}
		foreach ( array_keys( $data['scrips'] ) as $kind ) {
			$extra[] = islamicdilemma_schema_scripture( $kind );
		}

		$target = array( 'mentions' => array_values( $data['mentions'] ), 'citation' => array_values( $data['citations'] ) );
		foreach ( $target as $prop => $values ) {
			if ( $values ) {
				$page[ $prop ] = array_slice( $values, 0, 60 );
			}
		}

		switch ( $slug ) {
			case 'faq':
				$types[] = 'FAQPage';
				$qs      = array();
				foreach ( $data['faq'] as $i => $qa ) {
					$qs[] = array( '@type' => 'Question', '@id' => $url . '#q-' . ( $i + 1 ), 'name' => $qa[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $qa[1] ) );
				}
				$page['mainEntity'] = $qs;
				break;
			case 'honest-questions-for-christians':
				$types[] = 'CollectionPage';
				$qs      = array();
				foreach ( $data['cards'] as $i => $qa ) {
					$qs[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => array( '@type' => 'Question', 'name' => $qa[0], 'text' => $qa[1], 'about' => array( '@type' => 'Thing', 'name' => 'The Islamic Dilemma' ) ) );
				}
				$extra[]            = array( '@type' => 'ItemList', '@id' => $url . '#questions', 'name' => 'Ten honest questions for Christians', 'numberOfItems' => count( $qs ), 'itemListElement' => $qs );
				$page['mainEntity'] = array( '@id' => $url . '#questions' );
				break;
			case 'quran-manuscript-timeline':
				$types[]  = 'CollectionPage';
				$elements = array();
				foreach ( $data['timeline'] as $i => $t ) {
					$elements[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $t[0] . ': ' . $t[1], 'description' => $t[2] );
				}
				$extra[]                 = array( '@type' => 'ItemList', '@id' => $url . '#timeline', 'name' => 'The Qur’an from revelation to print', 'numberOfItems' => count( $elements ), 'itemListOrder' => 'https://schema.org/ItemListOrderAscending', 'itemListElement' => $elements );
				$page['mainEntity']      = array( '@id' => $url . '#timeline' );
				$page['temporalCoverage'] = '0610/1924';
				break;
			case 'christians-who-became-muslim':
				$types[] = 'CollectionPage';
				$people  = islamicdilemma_schema_people( $data['portraits'], $url );
				$elements = array();
				foreach ( $people as $i => $p ) {
					$extra[]    = $p;
					$elements[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => array( '@id' => $p['@id'] ) );
				}
				$extra[]            = array( '@type' => 'ItemList', '@id' => $url . '#people', 'name' => 'Christians who became Muslim', 'numberOfItems' => count( $elements ), 'itemListElement' => $elements );
				$page['mainEntity'] = array( '@id' => $url . '#people' );
				break;
			case 'contact':
				$types[]           = 'ContactPage';
				$page['about']     = $owner ? $owner : null;
				$page['potentialAction'] = array( '@type' => 'CommunicateAction', 'name' => 'Write to the author', 'target' => $url . '#form', 'inLanguage' => 'en' );
				break;
			case 'glossary':
				$types[] = 'CollectionPage';
				$nodes   = islamicdilemma_schema_glossary( $url );
				foreach ( $nodes as $n ) {
					$extra[] = $n;
				}
				if ( $nodes ) {
					$page['mainEntity'] = array( '@id' => $url . '#glossary' );
				}
				break;
			case 'reading-list':
				$types[] = 'CollectionPage';
				$nodes   = islamicdilemma_schema_reading_list( $url );
				foreach ( $nodes as $n ) {
					$extra[] = $n;
				}
				if ( $nodes ) {
					$page['mainEntity'] = array( '@id' => $url . '#reading-list' );
				}
				break;
			case 'verse-index':
				$types[] = 'CollectionPage';
				$nodes   = islamicdilemma_schema_verse_index( $url );
				foreach ( $nodes as $n ) {
					$extra[] = $n;
				}
				if ( $nodes ) {
					$page['mainEntity'] = array( '@id' => $url . '#cited' );
				}
				break;
			case 'sitemap':
			case 'enrichment':
				$types[] = 'CollectionPage';
				break;
			case 'about':
				$page['mentions'] = array( array( '@id' => $home . '#study' ), array( '@id' => $home . '#paulus' ) );
				break;
		}
		if ( empty( $page['about'] ) ) {
			unset( $page['about'] );
		}
	}
	$page['@type'] = array_values( array_unique( $types ) );
	if ( empty( $page['hasPart'] ) ) {
		unset( $page['hasPart'] );
	}
	$graph[ $index ] = $page;

	// The Article carries the same citations and a reading time.
	foreach ( $graph as $i => $node ) {
		if ( isset( $node['@id'] ) && '#article' === substr( $node['@id'], -8 ) ) {
			$node['timeRequired']      = 'PT' . max( 1, (int) round( ( $node['wordCount'] ?? 0 ) / 220 ) ) . 'M';
			$node['copyrightYear']     = (int) get_the_date( 'Y' );
			$node['educationalUse']    = 'reference';
			if ( $owner ) {
				$node['copyrightHolder'] = $owner;
			}
			foreach ( array( 'mentions', 'citation' ) as $prop ) {
				if ( ! empty( $page[ $prop ] ) ) {
					$node[ $prop ] = $page[ $prop ];
				}
			}
			$node['isPartOf'] = array( '@id' => $url . '#webpage' );
			$graph[ $i ]      = $node;
		}
	}
	foreach ( $extra as $n ) {
		$graph[] = $n;
	}
	return $graph;
}
add_filter( 'islamicdilemma_schema_graph', 'islamicdilemma_schema_extend' );
