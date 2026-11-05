<?php
/**
 * Contextual links: in the running text of an article or inner page, the
 * first mention of a known phrase links to the page on this site that treats
 * it at length. Ported from the lailatulqadar theme's engine and fitted to
 * this site: the phrases live in inc/data/contextual-links.json, each with the
 * path of an Enrichment article or a page, and every link stays on this site.
 *
 * Links are added as the page renders, so the stored text is untouched. A
 * phrase is matched whole, capitals aside, in the running text only: never
 * inside a link, a heading, a quotation, a figure or caption, a table, a
 * Details block, a verse card, Arabic, the notes or the key points. Each
 * address is linked once a page, never the page itself and never an address
 * the text already links to; at most one new link goes into a paragraph or
 * list item, and at most six into a page.
 *
 * Filters:
 * - islamicdilemma_contextual_links_map (array): the decoded map.
 * - islamicdilemma_contextual_links_enabled (bool, WP_Post): one page.
 * - islamicdilemma_contextual_links_max (int): links a page, 0 to 15.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A phrase or matched text in the form used for lookups: entities decoded,
 * curly apostrophes made straight, spaces collapsed, lower case.
 *
 * @param string $text Text.
 * @return string
 */
function islamicdilemma_ctx_norm( $text ) {
	$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( array( "\u{2019}", "\u{2018}", "\u{02BC}", "\u{00A0}" ), array( "'", "'", "'", ' ' ), $text );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) ) ?? trim( $text );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

/**
 * The phrase map, decoded once a request.
 *
 * @return array<int,array{path:string,phrases:string[]}>
 */
function islamicdilemma_ctx_map() {
	static $map = null;
	if ( null === $map ) {
		$file = get_stylesheet_directory() . '/inc/data/contextual-links.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$map  = isset( $data['links'] ) && is_array( $data['links'] ) ? $data['links'] : array();
		$map  = (array) apply_filters( 'islamicdilemma_contextual_links_map', $map );
	}
	return $map;
}

/**
 * Address of a published post or page by its path, or '' while it is a draft
 * or missing, so no link ever leads to a page readers cannot open.
 *
 * @param string $path Path such as /injil-where-is-it/.
 * @return string
 */
function islamicdilemma_ctx_url( $path ) {
	$id = url_to_postid( home_url( '/' . ltrim( (string) $path, '/' ) ) );
	return $id && 'publish' === get_post_status( $id ) ? (string) get_permalink( $id ) : '';
}

/**
 * The phrase alternatives as compiled patterns: one pattern while it fits
 * PCRE's size limit, else halves until each half compiles. A phrase that
 * cannot compile on its own is left out, never the rest.
 *
 * @param string[] $parts Quoted phrase alternatives, longest first.
 * @return string[]
 */
function islamicdilemma_ctx_patterns( $parts ) {
	if ( ! $parts ) {
		return array();
	}
	// An apostrophe in a phrase matches the straight one, the curly one and
	// the entities wptexturize writes; a space matches any run of spaces.
	$define  = "(?(DEFINE)(?<q>'|\u{2019}|\u{2018}|\u{02BC}|&\#8217;|&\#8216;|&\#039;|&\#39;|&rsquo;|&apos;)(?<s>(?:\s|&nbsp;|&\#160;)+))";
	$pattern = '/' . $define . '(?<![\p{L}\p{N}])(?:' . implode( '|', $parts ) . ')(?![\p{L}\p{N}])/iu';
	if ( false !== @preg_match( $pattern, '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return array( $pattern );
	}
	if ( 1 === count( $parts ) ) {
		return array();
	}
	$half = (int) ceil( count( $parts ) / 2 );
	return array_merge( islamicdilemma_ctx_patterns( array_slice( $parts, 0, $half ) ), islamicdilemma_ctx_patterns( array_slice( $parts, $half ) ) );
}

/**
 * Compiled targets, once a request. The first entry for a phrase wins.
 *
 * @return array{patterns:string[],by:array<string,string>}
 */
function islamicdilemma_ctx_targets() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$by      = array();
	$phrases = array();
	foreach ( islamicdilemma_ctx_map() as $link ) {
		$path = isset( $link['path'] ) ? (string) $link['path'] : '';
		$url  = '' === $path ? '' : islamicdilemma_ctx_url( $path );
		if ( '' === $url || empty( $link['phrases'] ) ) {
			continue;
		}
		foreach ( (array) $link['phrases'] as $phrase ) {
			$key = islamicdilemma_ctx_norm( $phrase );
			if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $key ) : strlen( $key ) ) < 3 || isset( $by[ $key ] ) ) {
				continue;
			}
			$by[ $key ] = $url;
			$phrases[]  = trim( (string) $phrase );
		}
	}
	usort(
		$phrases,
		static function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);
	$parts = array();
	foreach ( $phrases as $phrase ) {
		$quoted = preg_quote( $phrase, '/' );
		$quoted = str_replace( array( "'", "\u{2019}", "\u{2018}", "\u{02BC}" ), '(?&q)', $quoted );
		$quoted = preg_replace( '/(?:\\\\ |\s)+/u', '(?&s)', $quoted );
		if ( null !== $quoted ) {
			$parts[] = $quoted;
		}
	}
	$cache = array(
		'patterns' => islamicdilemma_ctx_patterns( $parts ),
		'by'       => $by,
	);
	return $cache;
}

/**
 * Replace phrases in a text run, left to right: the earliest match wins, and
 * of two at the same place the longer. A match the callback passes over (it
 * returns null) resumes the search one character on, while a phrase wholly
 * inside a passed-over match stays part of it.
 *
 * @param string[] $patterns Compiled patterns.
 * @param string   $text     Text run, no tags.
 * @param callable $link     Receives the matched text, returns markup or null.
 * @return string|null Null when the text cannot be searched (invalid UTF-8).
 */
function islamicdilemma_ctx_replace( $patterns, $text, $link ) {
	$out    = '';
	$pos    = 0;
	$at     = 0;
	$shadow = 0;
	$len    = strlen( $text );
	while ( $pos < $len ) {
		$best = null;
		foreach ( $patterns as $pattern ) {
			$hit = preg_match( $pattern, $text, $m, PREG_OFFSET_CAPTURE, $pos );
			if ( false === $hit ) {
				return null;
			}
			if ( $hit && ( null === $best || $m[0][1] < $best[1] || ( $m[0][1] === $best[1] && strlen( $m[0][0] ) > strlen( $best[0] ) ) ) ) {
				$best = $m[0];
			}
		}
		if ( null === $best || '' === $best[0] ) {
			break;
		}
		$end    = $best[1] + strlen( $best[0] );
		$markup = $end <= $shadow ? null : $link( $best[0] );
		if ( null === $markup ) {
			$shadow = max( $shadow, $end );
			$byte   = ord( $text[ $best[1] ] );
			$pos    = $best[1] + ( $byte >= 0xF0 ? 4 : ( $byte >= 0xE0 ? 3 : ( $byte >= 0xC0 ? 2 : 1 ) ) );
			continue;
		}
		$out .= substr( $text, $at, $best[1] - $at ) . $markup;
		$at   = $end;
		$pos  = $end;
	}
	return $out . substr( $text, $at );
}

/**
 * An address reduced for comparison: host and path, lower case, without the
 * trailing slash, query or fragment. A relative address takes this site's host.
 *
 * @param string $url Address.
 * @return string
 */
function islamicdilemma_ctx_url_key( $url ) {
	$url  = html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( '' === $host && '/' === substr( $url, 0, 1 ) ) {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	}
	$path = strtolower( rtrim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) );
	return $host . $path;
}

/**
 * Add the links to rendered HTML. Pure: everything it needs is passed in.
 *
 * @param string $content Rendered content.
 * @param array  $targets From islamicdilemma_ctx_targets().
 * @param string $here    Address of the page being rendered.
 * @param int    $max     Most links to add.
 * @return string
 */
function islamicdilemma_ctx_apply( $content, $targets, $here, $max ) {
	if ( empty( $targets['patterns'] ) || false === strpos( $content, '<' ) || $max < 1 ) {
		return $content;
	}
	$pieces = preg_split( '/(<[^>]+>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $pieces ) {
		return $content;
	}

	$skip_tags  = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'q', 'figure', 'figcaption', 'cite', 'code', 'pre', 'sup', 'sub', 'nav', 'table', 'button', 'label', 'select', 'textarea', 'summary', 'details', 'script', 'style', 'aside', 'svg', 'math' );
	$skip_class = '/\bclass\s*=\s*["\'][^"\']*(?<![\w-])(?:is-style-ayah|is-style-arabic|is-style-quran|is-style-hadith|is-style-bible|is-style-source|is-style-verse-ref|is-style-notes|is-style-callout|is-style-ledger|notes-title|ayah-translation|answer-box|ref|wp-block-footnotes)(?![\w-])/i';
	$skip_lang  = '/\blang\s*=\s*["\']?(?:ar|he|el|grc|syr)(?:-[a-z]+)?\b/i';
	$void       = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' );

	$here_key = islamicdilemma_ctx_url_key( $here );
	$done     = array();
	// Addresses the text already links to are never linked again.
	if ( preg_match_all( '/\shref\s*=\s*["\']([^"\']+)["\']/i', $content, $hrefs ) ) {
		foreach ( $hrefs[1] as $href ) {
			$done[ islamicdilemma_ctx_url_key( $href ) ] = true;
		}
	}
	$made   = 0;
	$used   = false; // Whether the current paragraph or list item already has a new link.
	$blocks = 0;     // Open p, li and dd outside skip regions.
	$skips  = 0;     // Open elements that forbid links.
	$open   = array();
	$out    = '';

	$link = static function ( $text ) use ( $targets, $here_key, $max, &$done, &$made, &$used ) {
		$key = islamicdilemma_ctx_norm( $text );
		// Without mbstring only ASCII letters are lowered, so a match in
		// non-ASCII capitals is looked up key by key.
		if ( ! isset( $targets['by'][ $key ] ) && ! function_exists( 'mb_strtolower' ) ) {
			foreach ( array_keys( $targets['by'] ) as $candidate ) {
				if ( preg_match( '/^' . preg_quote( (string) $candidate, '/' ) . '$/iu', $key ) ) {
					$key = $candidate;
					break;
				}
			}
		}
		if ( $used || $made >= $max || ! isset( $targets['by'][ $key ] ) ) {
			return null;
		}
		$url = $targets['by'][ $key ];
		$uk  = islamicdilemma_ctx_url_key( $url );
		if ( isset( $done[ $uk ] ) || $uk === $here_key ) {
			return null;
		}
		$done[ $uk ] = true;
		++$made;
		$used = true;
		return '<a class="ctx-link" href="' . esc_url( $url ) . '">' . $text . '</a>';
	};

	foreach ( $pieces as $piece ) {
		if ( '' === $piece ) {
			continue;
		}
		if ( '<' === $piece[0] ) {
			if ( preg_match( '#^<(/?)([a-zA-Z][a-zA-Z0-9-]*)#', $piece, $m ) ) {
				$tag      = strtolower( $m[2] );
				$is_block = 'p' === $tag || 'li' === $tag || 'dd' === $tag;
				if ( '/' === $m[1] ) {
					for ( $i = count( $open ) - 1; $i >= 0; $i-- ) {
						if ( $open[ $i ][0] === $tag ) {
							foreach ( array_splice( $open, $i ) as $closed ) {
								$skips  -= $closed[1] ? 1 : 0;
								$blocks -= $closed[2] ? 1 : 0;
							}
							break;
						}
					}
				} elseif ( ! in_array( $tag, $void, true ) && '/>' !== substr( $piece, -2 ) ) {
					$forbid = $skips > 0 || in_array( $tag, $skip_tags, true ) || preg_match( $skip_class, $piece ) || preg_match( $skip_lang, $piece );
					$block  = ! $forbid && $is_block;
					if ( $block ) {
						if ( 0 === $blocks ) {
							$used = false;
						}
						++$blocks;
					}
					$skips  += $forbid ? 1 : 0;
					$open[]  = array( $tag, (bool) $forbid, $block );
				}
			}
			$out .= $piece;
			continue;
		}
		// Text outside any paragraph or list item (a stray run) takes no link.
		if ( $skips > 0 || 0 === $blocks || '' === trim( $piece ) || $made >= $max ) {
			$out .= $piece;
			continue;
		}
		$out .= islamicdilemma_ctx_replace( $targets['patterns'], $piece, $link ) ?? $piece;
	}
	return $out;
}

/**
 * Whether a page or post takes contextual links. Lists and forms do not.
 *
 * @param WP_Post $post Page or post.
 * @return bool
 */
function islamicdilemma_ctx_enabled_for( $post ) {
	$lists   = array( 'sitemap', 'glossary', 'verse-index', 'reading-list', 'contact', 'book' );
	$enabled = in_array( $post->post_type, array( 'post', 'page' ), true ) && ! ( 'page' === $post->post_type && in_array( $post->post_name, $lists, true ) );
	return (bool) apply_filters( 'islamicdilemma_contextual_links_enabled', $enabled, $post );
}

/**
 * the_content filter. Block templates render Post Content outside the classic
 * loop, so the guard checks the main query and the queried post. Priority 25:
 * after blocks, wptexturize, wpautop, shortcodes and the book note (20).
 *
 * @param string $content Rendered content.
 * @return string
 */
function islamicdilemma_contextual_links_filter( $content ) {
	if ( is_admin() || is_feed() || ! is_singular() || is_front_page() || ! is_main_query() || doing_filter( 'get_the_excerpt' ) ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post || (int) $post->ID !== (int) get_queried_object_id() || ! islamicdilemma_ctx_enabled_for( $post ) ) {
		return $content;
	}
	$max = max( 0, min( 15, (int) apply_filters( 'islamicdilemma_contextual_links_max', 6 ) ) );
	return islamicdilemma_ctx_apply( $content, islamicdilemma_ctx_targets(), (string) get_permalink( $post ), $max );
}
add_filter( 'the_content', 'islamicdilemma_contextual_links_filter', 25 );
