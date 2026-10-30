<?php
/**
 * Search and answer-engine metadata.
 *
 * - A unique meta description per page, from its excerpt.
 * - Open Graph and Twitter card tags, and a robots noindex on search results.
 * - One JSON-LD graph per page, using only types Google Search supports:
 *   WebSite, Person (the author), WebPage / ProfilePage, Article or
 *   BlogPosting, BreadcrumbList, ImageObject with licence metadata, and
 *   speakable sections. No Organization: the site belongs to one person.
 * - /llms.txt, a plain-text map of the site for AI assistants.
 *
 * Everything here stands down if a dedicated SEO plugin is active, so the
 * page never carries two sets of tags.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the theme should print its own SEO tags.
 *
 * @return bool
 */
function islamicdilemma_seo_enabled() {
	$plugin = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
	return (bool) apply_filters( 'islamicdilemma_seo_enabled', ! $plugin );
}

/**
 * The document title as plain text, for tags and structured data.
 *
 * @return string
 */
function islamicdilemma_title_text() {
	return html_entity_decode( wp_strip_all_tags( wp_get_document_title() ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Credits and licences for the theme's images, keyed by file name.
 *
 * @return array
 */
function islamicdilemma_image_credits() {
	static $credits = null;
	if ( null === $credits ) {
		$path    = get_stylesheet_directory() . '/assets/img/credits.json';
		$credits = is_readable( $path ) ? (array) json_decode( (string) file_get_contents( $path ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	return $credits;
}

/**
 * A plain-text summary of the current view for the description tags.
 *
 * @return string
 */
function islamicdilemma_meta_description() {
	$text = '';
	if ( is_front_page() ) {
		$text = get_bloginfo( 'description' );
		$home = get_post( (int) get_option( 'page_on_front' ) );
		if ( $home && $home->post_excerpt ) {
			$text = $home->post_excerpt;
		} elseif ( $text ) {
			$text .= '. ' . __( 'The Qur’an on the Torah and the Gospel, read in Arabic and in context, with the classical commentaries and the history of the biblical text.', 'islamicdilemma' );
		}
	} elseif ( is_home() ) {
		$page = get_post( (int) get_option( 'page_for_posts' ) );
		$text = $page && $page->post_excerpt ? $page->post_excerpt : __( 'Commentary, close readings and replies on the Islamic Dilemma.', 'islamicdilemma' );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$text = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 32, '…' );
	} elseif ( is_category() || is_tag() ) {
		$text = term_description();
		if ( ! trim( wp_strip_all_tags( $text ) ) ) {
			/* translators: %s: category or tag name. */
			$text = sprintf( __( 'Articles on %s: close readings of the Qur’an and the Bible in reply to the Islamic Dilemma.', 'islamicdilemma' ), single_term_title( '', false ) );
		}
	}
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	return wp_html_excerpt( $text, 300, '…' );
}

/**
 * The best image for the current page: its featured image, else the first
 * image in its content, else the site's default share image.
 *
 * @return array { url, width, height, alt, file }
 */
function islamicdilemma_page_image() {
	$post = is_singular() ? get_queried_object() : null;
	$id   = 0;
	if ( $post ) {
		$id = (int) get_post_thumbnail_id( $post );
		if ( ! $id && preg_match( '/wp-image-(\d+)/', $post->post_content, $m ) ) {
			$id = (int) $m[1];
		}
	}
	if ( $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
				'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'file'   => (string) get_post_meta( $id, ISLAMICDILEMMA_ASSET_META, true ),
			);
		}
	}
	return array(
		'url'    => get_stylesheet_directory_uri() . '/assets/img/og-default.jpg',
		'width'  => 1200,
		'height' => 630,
		'alt'    => __( 'An illuminated page of the Qur’an', 'islamicdilemma' ),
		'file'   => 'og-default.jpg',
	);
}

/**
 * Schema.org ImageObject for an image, with credit and licence where known.
 *
 * @param array  $image From islamicdilemma_page_image().
 * @param string $id    @id for the node.
 * @return array
 */
function islamicdilemma_image_node( $image, $id ) {
	$node    = array(
		'@type'      => 'ImageObject',
		'@id'        => $id,
		'url'        => $image['url'],
		'contentUrl' => $image['url'],
		'width'      => $image['width'],
		'height'     => $image['height'],
	);
	$credits = islamicdilemma_image_credits();
	if ( $image['alt'] ) {
		$node['caption'] = $image['alt'];
	}
	if ( $image['file'] && isset( $credits[ $image['file'] ] ) ) {
		$credit = $credits[ $image['file'] ];
		if ( ! empty( $credit['creator'] ) ) {
			$node['creator'] = array( '@type' => 'Person', 'name' => $credit['creator'] );
		}
		if ( ! empty( $credit['credit'] ) ) {
			$node['creditText'] = $credit['credit'];
		}
		if ( ! empty( $credit['license'] ) ) {
			$node['license'] = $credit['license'];
		}
		if ( ! empty( $credit['source'] ) ) {
			$node['acquireLicensePage'] = $credit['source'];
		}
	}
	return $node;
}

/**
 * The author as a schema.org Person, or null if author.json has no name.
 *
 * @return array|null
 */
function islamicdilemma_person_node() {
	if ( ! islamicdilemma_has_author() ) {
		return null;
	}
	$author = islamicdilemma_author();
	$person = array(
		'@type' => 'Person',
		'@id'   => home_url( '/#author' ),
		'name'  => $author['name'],
		'url'   => islamicdilemma_author_url(),
	);
	$map    = array(
		'alternate_name'   => 'alternateName',
		'honorific_prefix' => 'honorificPrefix',
		'job_title'        => 'jobTitle',
		'bio'              => 'description',
		'nationality'      => 'nationality',
		'email'            => 'email',
	);
	foreach ( $map as $key => $prop ) {
		if ( '' !== trim( (string) $author[ $key ] ) ) {
			$person[ $prop ] = 'nationality' === $key ? array( '@type' => 'Country', 'name' => $author[ $key ] ) : wp_strip_all_tags( $author[ $key ] );
		}
	}
	if ( $author['home_location'] ) {
		$person['homeLocation'] = array( '@type' => 'Place', 'name' => $author['home_location'] );
	}
	foreach ( array( 'knows_about' => 'knowsAbout', 'knows_language' => 'knowsLanguage', 'same_as' => 'sameAs' ) as $key => $prop ) {
		$values = array_values( array_filter( array_map( 'trim', (array) $author[ $key ] ) ) );
		if ( $values ) {
			$person[ $prop ] = $values;
		}
	}
	$photo = islamicdilemma_author_photo_url();
	if ( $photo ) {
		$person['image'] = array(
			'@type'      => 'ImageObject',
			'@id'        => home_url( '/#author-image' ),
			'url'        => $photo,
			'contentUrl' => $photo,
			'caption'    => $author['name'],
		);
	}
	return $person;
}

/**
 * The JSON-LD graph for the current view.
 *
 * @return array
 */
function islamicdilemma_schema_graph() {
	$home   = home_url( '/' );
	$person = islamicdilemma_person_node();
	$owner  = $person ? array( '@id' => $person['@id'] ) : null;
	$graph  = array();

	$website = array(
		'@type'       => 'WebSite',
		'@id'         => $home . '#website',
		'url'         => $home,
		'name'        => get_bloginfo( 'name' ),
		'description' => get_bloginfo( 'description' ),
		'inLanguage'  => get_bloginfo( 'language' ),
	);
	if ( $owner ) {
		$website['publisher']       = $owner;
		$website['author']          = $owner;
		$website['copyrightHolder'] = $owner;
	}
	$graph[] = $website;
	if ( $person ) {
		$graph[] = $person;
	}

	$url = is_singular() ? get_permalink() : ( is_front_page() || is_home() ? ( is_home() && ! is_front_page() ? get_permalink( (int) get_option( 'page_for_posts' ) ) : $home ) : '' );
	if ( ! $url && ( is_category() || is_tag() ) ) {
		$url = get_term_link( get_queried_object() );
	}
	if ( ! $url || is_wp_error( $url ) || is_search() || is_404() ) {
		return $graph;
	}

	$image      = islamicdilemma_page_image();
	$image_node = islamicdilemma_image_node( $image, $url . '#primaryimage' );
	$graph[]    = $image_node;

	$about   = get_page_by_path( 'about' );
	$is_about = $about && is_page( $about->ID );
	$type    = 'WebPage';
	if ( $is_about ) {
		$type = $person ? array( 'WebPage', 'AboutPage', 'ProfilePage' ) : array( 'WebPage', 'AboutPage' );
	} elseif ( is_home() || is_category() || is_tag() ) {
		$type = array( 'WebPage', 'CollectionPage' );
	}
	$webpage = array(
		'@type'              => $type,
		'@id'                => $url . '#webpage',
		'url'                => $url,
		'name'               => islamicdilemma_title_text(),
		'description'        => islamicdilemma_meta_description(),
		'isPartOf'           => array( '@id' => $home . '#website' ),
		'inLanguage'         => get_bloginfo( 'language' ),
		'primaryImageOfPage' => array( '@id' => $image_node['@id'] ),
		'speakable'          => array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => array( 'h1', '.deck', '.is-style-lead', '.is-style-callout' ),
		),
	);
	if ( is_singular() ) {
		$webpage['datePublished'] = get_the_date( 'c' );
		$webpage['dateModified']  = get_the_modified_date( 'c' );
	}
	if ( $is_about && $person ) {
		$webpage['mainEntity'] = array( '@id' => $person['@id'] );
	}

	$trail = islamicdilemma_breadcrumb_trail();
	if ( count( $trail ) > 1 ) {
		$items = array();
		foreach ( $trail as $i => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => html_entity_decode( wp_strip_all_tags( $crumb[0] ), ENT_QUOTES, 'UTF-8' ),
			);
			// The last crumb is the page itself.
			$item['item'] = $crumb[1] ? $crumb[1] : $url;
			$items[]      = $item;
		}
		$graph[]               = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $url . '#breadcrumb',
			'itemListElement' => $items,
		);
		$webpage['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}
	$graph[] = $webpage;

	// Articles: every Enrichment post, and every content page except home, the
	// posts page and About.
	$post = is_singular() ? get_queried_object() : null;
	$is_utility = is_page( array( 'site-map', 'book' ) );
	if ( $post && ! is_front_page() && ! $is_about && ! $is_utility && in_array( $post->post_type, array( 'post', 'page' ), true ) && (int) get_option( 'page_for_posts' ) !== $post->ID ) {
		$text    = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$article = array(
			'@type'            => 'post' === $post->post_type ? 'BlogPosting' : 'Article',
			'@id'              => $url . '#article',
			'headline'         => wp_html_excerpt( html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ), 110, '…' ),
			'description'      => islamicdilemma_meta_description(),
			'url'              => $url,
			'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			'isPartOf'         => array( '@id' => $url . '#webpage' ),
			'image'            => array( '@id' => $image_node['@id'] ),
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'wordCount'        => str_word_count( $text ),
			'inLanguage'       => get_bloginfo( 'language' ),
			'isAccessibleForFree' => true,
			'about'            => array( '@type' => 'Thing', 'name' => 'Islamic Dilemma' ),
		);
		if ( $owner ) {
			$article['author']    = $owner;
			$article['publisher'] = $owner;
		}
		if ( 'post' === $post->post_type ) {
			$cats = get_the_category( $post->ID );
			if ( $cats ) {
				$article['articleSection'] = $cats[0]->name;
			}
			$tags = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
			if ( $tags ) {
				$article['keywords'] = implode( ', ', $tags );
			}
		}
		$graph[] = $article;
	}
	return $graph;
}

/**
 * Print the head tags.
 */
function islamicdilemma_seo_head() {
	if ( ! islamicdilemma_seo_enabled() ) {
		return;
	}
	if ( is_search() || is_404() ) {
		return;
	}
	$description = islamicdilemma_meta_description();
	$image       = islamicdilemma_page_image();
	$url         = is_singular() ? get_permalink() : '';
	if ( ! $url ) {
		$url = is_home() && ! is_front_page() ? get_permalink( (int) get_option( 'page_for_posts' ) ) : ( is_category() || is_tag() ? get_term_link( get_queried_object() ) : home_url( '/' ) );
	}
	$article = is_singular( 'post' ) || ( is_page() && ! is_front_page() );
	$tags    = array(
		'description'         => $description,
		'og:type'             => $article ? 'article' : 'website',
		'og:site_name'        => get_bloginfo( 'name' ),
		'og:locale'           => str_replace( '-', '_', get_bloginfo( 'language' ) ),
		'og:title'            => islamicdilemma_title_text(),
		'og:description'      => $description,
		'og:url'              => is_wp_error( $url ) ? '' : $url,
		'og:image'            => $image['url'],
		'og:image:width'      => $image['width'],
		'og:image:height'     => $image['height'],
		'og:image:alt'        => $image['alt'],
		'twitter:card'        => 'summary_large_image',
		'twitter:title'       => islamicdilemma_title_text(),
		'twitter:description' => $description,
		'twitter:image'       => $image['url'],
		'twitter:image:alt'   => $image['alt'],
	);
	if ( $article ) {
		$tags['article:published_time'] = get_the_date( 'c' );
		$tags['article:modified_time']  = get_the_modified_date( 'c' );
		if ( islamicdilemma_has_author() ) {
			$tags['author']         = islamicdilemma_author()['name'];
			$tags['article:author'] = islamicdilemma_author_url();
		}
		if ( is_singular( 'post' ) ) {
			$cats = get_the_category();
			if ( $cats ) {
				$tags['article:section'] = $cats[0]->name;
			}
		}
	}
	foreach ( $tags as $name => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}
		$attr = 0 === strpos( $name, 'og:' ) || 0 === strpos( $name, 'article:' ) ? 'property' : 'name';
		printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attr ), esc_attr( $name ), esc_attr( $content ) );
	}
	if ( is_singular( 'post' ) ) {
		foreach ( wp_get_post_tags( get_the_ID(), array( 'fields' => 'names' ) ) as $tag ) {
			printf( '<meta property="article:tag" content="%s">' . "\n", esc_attr( $tag ) );
		}
	}
	if ( islamicdilemma_has_author() ) {
		printf( '<link rel="author" href="%s">' . "\n", esc_url( islamicdilemma_author_url() ) );
	}
	if ( ! is_singular() && ! is_wp_error( $url ) ) {
		// WordPress prints canonical links for single views only.
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( is_paged() ? get_pagenum_link( get_query_var( 'paged' ) ) : $url ) );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => islamicdilemma_schema_graph() ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'islamicdilemma_seo_head', 2 );

/**
 * Search results stay out of the index; their links are still followed.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function islamicdilemma_wp_robots( $robots ) {
	if ( is_search() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'islamicdilemma_wp_robots' );

/**
 * Keep search result pages from being crawled.
 *
 * @param string $output  robots.txt content.
 * @param bool   $public  Whether the site is public.
 * @return string
 */
function islamicdilemma_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	// Rules must sit inside the user-agent group, before the Sitemap line.
	$rules  = "Disallow: /?s=\nDisallow: /search/\n";
	$output = preg_match( '/^Sitemap:/m', $output ) ? preg_replace( '/^(Sitemap:)/m', $rules . "\n$1", $output, 1 ) : $output . $rules;
	$output .= "\n# A plain-text guide to this site for AI assistants: " . home_url( '/llms.txt' ) . "\n";
	return $output;
}
add_filter( 'robots_txt', 'islamicdilemma_robots_txt', 10, 2 );

/**
 * The text of /llms.txt: what the site is, who writes it, and its pages.
 *
 * @return string
 */
function islamicdilemma_llms_txt() {
	$lines   = array( '# ' . get_bloginfo( 'name' ), '' );
	$lines[] = '> A Muslim reply to the "Islamic Dilemma" argument: the Qur\'an\'s statements about the Torah and the Gospel, read in Arabic and in context, with the classical commentaries (al-Tabari, Ibn Kathir) and the history of the biblical text.';
	$lines[] = '';
	if ( islamicdilemma_has_author() ) {
		$author  = islamicdilemma_author();
		$lines[] = 'Written by ' . $author['name'] . ( $author['job_title'] ? ', ' . $author['job_title'] : '' ) . '. About the author: ' . islamicdilemma_author_url();
		$lines[] = 'Based on the study: ' . $author['name'] . ', Reconsidering the “Islamic Dilemma” (2026), ' . ISLAMICDILEMMA_PAPER_URL;
		$lines[] = '';
	}
	$lines[] = 'Conventions: Qur\'an references are given as Q surah:verse; Arabic is quoted before translation; hadith carry their collection numbers (Sahih al-Bukhari, Sahih Muslim); Bible quotations name their translation. Corrections are recorded on the About page.';
	$lines[] = '';
	$lines[] = '## Pages';
	$lines[] = '';
	$pages = get_pages( array( 'sort_column' => 'menu_order', 'post_status' => 'publish' ) );
	foreach ( $pages as $page ) {
		if ( (int) get_option( 'page_for_posts' ) === $page->ID || islamicdilemma_hidden_book_id() === $page->ID ) {
			continue;
		}
		$summary = (int) get_option( 'page_on_front' ) === $page->ID ? get_bloginfo( 'description' ) : $page->post_excerpt;
		$lines[] = sprintf( '- [%s](%s): %s', get_the_title( $page ), get_permalink( $page ), wp_strip_all_tags( $summary ) );
	}
	$lines[] = '';
	$lines[] = '## Enrichment articles';
	$lines[] = '';
	foreach ( get_posts( array( 'numberposts' => 200, 'post_status' => 'publish' ) ) as $post ) {
		$lines[] = sprintf( '- [%s](%s): %s', get_the_title( $post ), get_permalink( $post ), wp_strip_all_tags( $post->post_excerpt ) );
	}
	return html_entity_decode( implode( "\n", $lines ), ENT_QUOTES, 'UTF-8' ) . "\n";
}

/**
 * Serve /llms.txt.
 */
function islamicdilemma_serve_llms_txt() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( rtrim( (string) $home, '/' ) . '/llms.txt' !== $path || ! (bool) apply_filters( 'islamicdilemma_llms_txt_enabled', true ) ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo islamicdilemma_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'init', 'islamicdilemma_serve_llms_txt', 99 );
