<?php
/**
 * Reader tools: filterable glossary and reading list, verse index, contact form.
 *
 * The glossary is built from tag entries (see term-entries.php); the reading list is seed content in content/*.json.
 *
 * @package islamicdilemma
 */

/**
 * Enqueue the filter script once.
 */
function islamicdilemma_tools_assets() {
	wp_enqueue_script( 'islamicdilemma-tools', get_stylesheet_directory_uri() . '/assets/js/tools.js', array(), islamicdilemma_asset_version( 'assets/js/tools.js' ), true );
}

/**
 * Read a seed JSON file from content/.
 *
 * @param string $file File name.
 * @return array
 */
function islamicdilemma_tool_data( $file ) {
	$data = islamicdilemma_read_json( $file );
	return is_array( $data ) ? $data : array();
}

/**
 * Filter bar: a search box and category chips.
 *
 * @param string $id    Target list id.
 * @param array  $cats  Category names.
 * @param string $label Search label.
 * @return string
 */
function islamicdilemma_filter_bar( $id, $cats, $label ) {
	$html  = '<div class="tool-filter" data-target="' . esc_attr( $id ) . '">';
	$html .= '<label class="screen-reader-text" for="' . esc_attr( $id ) . '-q">' . esc_html( $label ) . '</label>';
	$html .= '<input type="search" id="' . esc_attr( $id ) . '-q" class="tool-filter-q" placeholder="' . esc_attr( $label ) . '" autocomplete="off">';
	$html .= '<div class="tool-chips" role="group" aria-label="' . esc_attr__( 'Filter by category', 'islamicdilemma' ) . '"><button type="button" class="chip is-on" data-cat="" aria-pressed="true">' . esc_html__( 'All', 'islamicdilemma' ) . '</button>';
	foreach ( $cats as $cat ) {
		$html .= '<button type="button" class="chip" data-cat="' . esc_attr( $cat ) . '" aria-pressed="false">' . esc_html( $cat ) . '</button>';
	}
	$html .= '</div><p class="tool-count" aria-live="polite"></p></div>';
	return $html;
}

/**
 * [islamicdilemma_glossary]
 *
 * @return string
 */
function islamicdilemma_glossary() {
	$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false, 'meta_query' => array( array( 'key' => 'id_definition', 'value' => '', 'compare' => '!=' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	if ( is_wp_error( $tags ) || ! $tags ) {
		return '';
	}
	$items = array();
	foreach ( $tags as $tag ) {
		$entry   = islamicdilemma_term_entry( $tag );
		$items[] = array(
			'term'   => $tag->name,
			'url'    => get_term_link( $tag ),
			'arabic' => $entry['arabic'],
			'cat'    => $entry['category'] ? $entry['category'] : __( 'Other', 'islamicdilemma' ),
			'def'    => $entry['definition'],
		);
	}
	islamicdilemma_tools_assets();
	usort( $items, function ( $a, $b ) {
		return strcasecmp( remove_accents( $a['term'] ), remove_accents( $b['term'] ) );
	} );
	$html = islamicdilemma_filter_bar( 'glossary-list', array_values( array_unique( wp_list_pluck( $items, 'cat' ) ) ), __( 'Search the glossary', 'islamicdilemma' ) );
	$html .= '<dl class="tool-list" id="glossary-list">';
	foreach ( $items as $it ) {
		$html .= sprintf(
			'<div class="tool-item" data-cat="%1$s" data-text="%2$s"><dt><a href="%7$s">%3$s</a>%4$s</dt><dd>%5$s <span class="tool-cat">%6$s</span></dd></div>',
			esc_attr( $it['cat'] ),
			esc_attr( mb_strtolower( remove_accents( $it['term'] . ' ' . $it['def'] ) ) ),
			esc_html( $it['term'] ),
			$it['arabic'] ? ' <span lang="ar" dir="rtl" class="ar">' . esc_html( $it['arabic'] ) . '</span>' : '',
			islamicdilemma_entry_definition_html( $it['def'] ),
			esc_html( $it['cat'] ),
			esc_url( $it['url'] )
		);
	}
	return $html . '</dl>';
}
add_shortcode( 'islamicdilemma_glossary', 'islamicdilemma_glossary' );

/**
 * [islamicdilemma_reading_list]
 *
 * @return string
 */
function islamicdilemma_reading_list() {
	$items = islamicdilemma_tool_data( 'reading-list.json' );
	if ( ! $items ) {
		return '';
	}
	islamicdilemma_tools_assets();
	$html  = islamicdilemma_filter_bar( 'reading-list', array_values( array_unique( wp_list_pluck( $items, 'cat' ) ) ), __( 'Search the reading list', 'islamicdilemma' ) );
	$html .= '<ul class="tool-list reading" id="reading-list">';
	foreach ( $items as $it ) {
		$title = ! empty( $it['url'] ) ? '<a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['title'] ) . '</a>' : esc_html( $it['title'] );
		$html .= sprintf(
			'<li class="tool-item" data-cat="%1$s" data-text="%2$s"><strong>%3$s</strong> <span class="tool-meta">%4$s, %5$s · %6$s</span><br>%7$s <span class="tool-cat">%8$s</span></li>',
			esc_attr( $it['cat'] ),
			esc_attr( mb_strtolower( remove_accents( $it['title'] . ' ' . $it['author'] . ' ' . $it['note'] . ' ' . $it['kind'] ) ) ),
			$title,
			esc_html( $it['author'] ),
			esc_html( $it['year'] ),
			esc_html( $it['kind'] ),
			esc_html( $it['note'] ),
			esc_html( $it['cat'] )
		);
	}
	return $html . '</ul>';
}
add_shortcode( 'islamicdilemma_reading_list', 'islamicdilemma_reading_list' );

/**
 * Sūra names, in order.
 *
 * @return string[]
 */
function islamicdilemma_sura_names() {
	return array_map( 'trim', explode( ',', 'al-Fātiḥa,al-Baqara,Āl ʿImrān,al-Nisāʾ,al-Māʾida,al-Anʿām,al-Aʿrāf,al-Anfāl,al-Tawba,Yūnus,Hūd,Yūsuf,al-Raʿd,Ibrāhīm,al-Ḥijr,al-Naḥl,al-Isrāʾ,al-Kahf,Maryam,Ṭā Hā,al-Anbiyāʾ,al-Ḥajj,al-Muʾminūn,al-Nūr,al-Furqān,al-Shuʿarāʾ,al-Naml,al-Qaṣaṣ,al-ʿAnkabūt,al-Rūm,Luqmān,al-Sajda,al-Aḥzāb,Sabaʾ,Fāṭir,Yā Sīn,al-Ṣāffāt,Ṣād,al-Zumar,Ghāfir,Fuṣṣilat,al-Shūrā,al-Zukhruf,al-Dukhān,al-Jāthiya,al-Aḥqāf,Muḥammad,al-Fatḥ,al-Ḥujurāt,Qāf,al-Dhāriyāt,al-Ṭūr,al-Najm,al-Qamar,al-Raḥmān,al-Wāqiʿa,al-Ḥadīd,al-Mujādila,al-Ḥashr,al-Mumtaḥana,al-Ṣaff,al-Jumuʿa,al-Munāfiqūn,al-Taghābun,al-Ṭalāq,al-Taḥrīm,al-Mulk,al-Qalam,al-Ḥāqqa,al-Maʿārij,Nūḥ,al-Jinn,al-Muzzammil,al-Muddaththir,al-Qiyāma,al-Insān,al-Mursalāt,al-Nabaʾ,al-Nāziʿāt,ʿAbasa,al-Takwīr,al-Infiṭār,al-Muṭaffifīn,al-Inshiqāq,al-Burūj,al-Ṭāriq,al-Aʿlā,al-Ghāshiya,al-Fajr,al-Balad,al-Shams,al-Layl,al-Ḍuḥā,al-Sharḥ,al-Tīn,al-ʿAlaq,al-Qadr,al-Bayyina,al-Zalzala,al-ʿĀdiyāt,al-Qāriʿa,al-Takāthur,al-ʿAṣr,al-Humaza,al-Fīl,Quraysh,al-Māʿūn,al-Kawthar,al-Kāfirūn,al-Naṣr,al-Masad,al-Ikhlāṣ,al-Falaq,al-Nās' ) );
}

/**
 * Bible books: abbreviation or name => [canonical name, order].
 *
 * @return array
 */
function islamicdilemma_bible_books() {
	$books = array(
		'Gen|Genesis', 'Exod|Exodus', 'Lev|Leviticus', 'Num|Numbers', 'Deut|Deuteronomy', 'Josh|Joshua', 'Judg|Judges', '1 Sam|1 Samuel', '2 Sam|2 Samuel', '1 Kgs|1 Kings', '2 Kgs|2 Kings',
		'Job|Job', 'Ps|Psalms|Psalm', 'Prov|Proverbs', 'Isa|Isaiah', 'Jer|Jeremiah', 'Ezek|Ezekiel', 'Dan|Daniel', 'Hos|Hosea', 'Joel|Joel', 'Amos|Amos', 'Jonah|Jonah', 'Mic|Micah', 'Mal|Malachi',
		'Matt|Matthew', 'Mark|Mark', 'Luke|Luke', 'John|John', 'Acts|Acts', 'Rom|Romans', '1 Cor|1 Corinthians', '2 Cor|2 Corinthians', 'Gal|Galatians', 'Eph|Ephesians', 'Phil|Philippians', 'Col|Colossians',
		'1 Thess|1 Thessalonians', '2 Thess|2 Thessalonians', '1 Tim|1 Timothy', '2 Tim|2 Timothy', 'Titus|Titus', 'Phlm|Philemon', 'Heb|Hebrews', 'Jas|James', '1 Pet|1 Peter', '2 Pet|2 Peter',
		'1 John|1 John', '2 John|2 John', '3 John|3 John', 'Jude|Jude', 'Rev|Revelation',
	);
	$map = array();
	foreach ( $books as $i => $row ) {
		$names = explode( '|', $row );
		foreach ( $names as $n ) {
			$map[ mb_strtolower( $n ) ] = array( $names[1], $i );
		}
	}
	return $map;
}

/**
 * Turn a reference string such as "Q 5:44 · 15:9" or "Matt 23:3 · ESV" into
 * [ [group, key, verse] ... ].
 *
 * @param string $text Raw reference text.
 * @return array
 */
function islamicdilemma_parse_refs( $text ) {
	$books = islamicdilemma_bible_books();
	$out   = array();
	$ctx   = null;
	foreach ( preg_split( '/\s*[·;,]\s*/u', trim( html_entity_decode( $text ) ) ) as $tok ) {
		$tok = trim( $tok );
		if ( preg_match( '/^(?:ESV|KJV|NIV|NRSV|NASB)$/i', $tok ) || '' === $tok ) {
			continue;
		}
		if ( preg_match( '/^Q\s+(\d{1,3}):(\d{1,3})/u', $tok, $m ) ) {
			$ctx   = 'Q';
			$out[] = array( 'Q', (int) $m[1], (int) $m[2] );
		} elseif ( preg_match( '/^((?:[123]\s)?[A-Za-z]+)\.?\s+(\d{1,3}):(\d{1,3})/u', $tok, $m ) && isset( $books[ mb_strtolower( $m[1] ) ] ) ) {
			$b     = $books[ mb_strtolower( $m[1] ) ];
			$ctx   = $b;
			$out[] = array( 'B', $b, (int) $m[2] . ':' . (int) $m[3] );
		} elseif ( preg_match( '/^(\d{1,3}):(\d{1,3})/', $tok, $m ) && $ctx ) {
			$out[] = 'Q' === $ctx ? array( 'Q', (int) $m[1], (int) $m[2] ) : array( 'B', $ctx, (int) $m[1] . ':' . (int) $m[2] );
		}
	}
	return $out;
}

/**
 * Collect every reference cited on published pages and posts.
 *
 * @return array
 */
function islamicdilemma_collect_refs() {
	$cached = get_transient( 'islamicdilemma_verse_index' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$quran = array();
	$bible = array();
	$posts = get_posts( array( 'post_type' => array( 'page', 'post' ), 'numberposts' => -1, 'post_status' => 'publish' ) );
	foreach ( $posts as $p ) {
		if ( has_shortcode( $p->post_content, 'islamicdilemma_verse_index' ) ) {
			continue;
		}
		$html = do_blocks( $p->post_content );
		$raw  = array();
		if ( preg_match_all( '#<span class="ref">([^<]+)</span>#u', $html, $m ) ) {
			$raw = array_merge( $raw, $m[1] );
		}
		if ( preg_match_all( '#<p class="[^"]*is-style-verse-ref[^"]*">([^<]+)</p>#u', $html, $m ) ) {
			$raw = array_merge( $raw, $m[1] );
		}
		if ( preg_match_all( '#<cite>(?:\s*--\s*|\s*—\s*)?([^<]+)</cite>#u', $html, $m ) ) {
			$raw = array_merge( $raw, $m[1] );
		}
		foreach ( $raw as $text ) {
			foreach ( islamicdilemma_parse_refs( $text ) as $r ) {
				$link = array( 'id' => $p->ID, 'title' => get_the_title( $p ), 'url' => get_permalink( $p ) );
				if ( 'Q' === $r[0] ) {
					$quran[ $r[1] ][ $r[2] ][ $p->ID ] = $link;
				} else {
					$bible[ $r[1][1] ]['name'] = $r[1][0];
					$bible[ $r[1][1] ]['v'][ $r[2] ][ $p->ID ] = $link;
				}
			}
		}
	}
	ksort( $quran );
	ksort( $bible );
	$data = array( 'quran' => $quran, 'bible' => $bible );
	set_transient( 'islamicdilemma_verse_index', $data, DAY_IN_SECONDS );
	return $data;
}
add_action( 'save_post', function () {
	delete_transient( 'islamicdilemma_verse_index' );
} );

/**
 * One verse row with links to the pages that cite it.
 *
 * @param string $label Verse label.
 * @param array  $pages Pages.
 * @return string
 */
function islamicdilemma_verse_row( $label, $pages ) {
	$links = array();
	foreach ( $pages as $pg ) {
		$links[] = '<a href="' . esc_url( $pg['url'] ) . '">' . esc_html( $pg['title'] ) . '</a>';
	}
	return '<li class="tool-item" data-text="' . esc_attr( mb_strtolower( remove_accents( $label . ' ' . wp_strip_all_tags( implode( ' ', $links ) ) ) ) ) . '"><span class="ref">' . esc_html( $label ) . '</span> ' . implode( '<span class="sep"> · </span>', $links ) . '</li>';
}

/**
 * [islamicdilemma_verse_index]: every Qur'an and Bible verse cited on the
 * site, grouped by sūra and by book.
 *
 * @return string
 */
function islamicdilemma_verse_index() {
	$data = islamicdilemma_collect_refs();
	if ( ! $data['quran'] && ! $data['bible'] ) {
		return '';
	}
	islamicdilemma_tools_assets();
	$names = islamicdilemma_sura_names();
	$html  = islamicdilemma_filter_bar( 'verse-list', array( 'Qur\'an', 'Bible' ), __( 'Find a verse or a page', 'islamicdilemma' ) );
	$html .= '<div class="tool-list verse-index" id="verse-list"><h2 id="quran-verses" data-cat="Qur\'an">' . esc_html__( 'The Qur\'an', 'islamicdilemma' ) . '</h2>';
	foreach ( $data['quran'] as $sura => $verses ) {
		ksort( $verses );
		$html .= '<section class="tool-group" data-cat="Qur\'an"><h3>' . esc_html( sprintf( 'Sūra %d, %s', $sura, $names[ $sura - 1 ] ?? '' ) ) . '</h3><ul>';
		foreach ( $verses as $v => $pages ) {
			$html .= islamicdilemma_verse_row( "Q $sura:$v", $pages );
		}
		$html .= '</ul></section>';
	}
	$html .= '<h2 id="bible-verses" data-cat="Bible">' . esc_html__( 'The Bible', 'islamicdilemma' ) . '</h2>';
	foreach ( $data['bible'] as $book ) {
		uksort( $book['v'], function ( $a, $b ) {
			return strnatcmp( $a, $b );
		} );
		$html .= '<section class="tool-group" data-cat="Bible"><h3>' . esc_html( $book['name'] ) . '</h3><ul>';
		foreach ( $book['v'] as $ref => $pages ) {
			$html .= islamicdilemma_verse_row( $book['name'] . ' ' . $ref, $pages );
		}
		$html .= '</ul></section>';
	}
	return $html . '</div>';
}
add_shortcode( 'islamicdilemma_verse_index', 'islamicdilemma_verse_index' );

/**
 * Contact form.
 */
function islamicdilemma_contact_topics() {
	return array(
		'question'   => __( 'A question', 'islamicdilemma' ),
		'correction' => __( 'A correction', 'islamicdilemma' ),
		'source'     => __( 'A source to add', 'islamicdilemma' ),
		'other'      => __( 'Something else', 'islamicdilemma' ),
	);
}

/**
 * A signed timestamp so that forms submitted instantly (bots) are refused.
 *
 * @param int $time Unix time.
 * @return string
 */
function islamicdilemma_contact_token( $time ) {
	return $time . '.' . substr( hash_hmac( 'sha256', (string) $time, wp_salt( 'nonce' ) ), 0, 20 );
}

/**
 * [islamicdilemma_contact]
 *
 * @return string
 */
function islamicdilemma_contact_form() {
	$status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$notes  = array(
		'sent'  => array( 'ok', __( 'Thank you. Your message has been sent, and we will reply by email.', 'islamicdilemma' ) ),
		'error' => array( 'err', __( 'Your message could not be sent. Please check each field and try again.', 'islamicdilemma' ) ),
		'wait'  => array( 'err', __( 'Please wait a little before sending another message.', 'islamicdilemma' ) ),
	);
	$html = '';
	if ( isset( $notes[ $status ] ) ) {
		$html .= '<p class="contact-note is-' . esc_attr( $notes[ $status ][0] ) . '" role="status">' . esc_html( $notes[ $status ][1] ) . '</p>';
	}
	$html .= '<form class="contact-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$html .= '<input type="hidden" name="action" value="islamicdilemma_contact">';
	$html .= wp_nonce_field( 'islamicdilemma_contact', '_contact_nonce', true, false );
	$html .= '<input type="hidden" name="ts" value="' . esc_attr( islamicdilemma_contact_token( time() ) ) . '">';
	$html .= '<input type="hidden" name="back" value="' . esc_url( get_permalink() ) . '">';
	$html .= '<p class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>';
	$html .= '<p><label for="c-name">' . esc_html__( 'Your name', 'islamicdilemma' ) . '</label><input id="c-name" name="name" type="text" required maxlength="100" autocomplete="name"></p>';
	$html .= '<p><label for="c-email">' . esc_html__( 'Your email', 'islamicdilemma' ) . '</label><input id="c-email" name="email" type="email" required maxlength="150" autocomplete="email"></p>';
	$html .= '<p><label for="c-topic">' . esc_html__( 'About', 'islamicdilemma' ) . '</label><select id="c-topic" name="topic">';
	foreach ( islamicdilemma_contact_topics() as $k => $v ) {
		$html .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';
	}
	$html .= '</select></p>';
	$html .= '<p><label for="c-msg">' . esc_html__( 'Your message', 'islamicdilemma' ) . '</label><textarea id="c-msg" name="message" rows="8" required minlength="20" maxlength="5000"></textarea></p>';
	$html .= '<p class="contact-submit"><button type="submit" class="wp-element-button">' . esc_html__( 'Send message', 'islamicdilemma' ) . '</button></p>';
	$html .= '<p class="contact-privacy">' . esc_html__( 'Your name and email are used only to reply to you. Nothing is stored on this site, and nothing is shared.', 'islamicdilemma' ) . '</p>';
	return $html . '</form>';
}
add_shortcode( 'islamicdilemma_contact', 'islamicdilemma_contact_form' );

/**
 * Handle the submission: validate, rate-limit, email the site owner.
 */
function islamicdilemma_contact_submit() {
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	$go   = function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'contact', $status, $back ) . '#contact' );
		exit;
	};
	if ( ! isset( $_POST['_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_contact_nonce'] ) ), 'islamicdilemma_contact' ) ) {
		$go( 'error' );
	}
	if ( ! empty( $_POST['website'] ) ) {
		$go( 'sent' ); // A bot filled the trap field: pretend success, send nothing.
	}
	$ts = isset( $_POST['ts'] ) ? sanitize_text_field( wp_unslash( $_POST['ts'] ) ) : '';
	$t  = (int) $ts;
	if ( islamicdilemma_contact_token( $t ) !== $ts || time() - $t < 4 || time() - $t > DAY_IN_SECONDS ) {
		$go( 'error' );
	}
	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$topic   = isset( $_POST['topic'] ) ? sanitize_key( wp_unslash( $_POST['topic'] ) ) : 'other';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$topics  = islamicdilemma_contact_topics();
	if ( '' === $name || ! is_email( $email ) || ! isset( $topics[ $topic ] ) || mb_strlen( $message ) < 20 || mb_strlen( $message ) > 5000 || preg_match_all( '#https?://#i', $message ) > 2 ) {
		$go( 'error' );
	}
	$key = 'islamicdilemma_contact_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'nonce' ) );
	$n   = (int) get_transient( $key );
	if ( $n >= 3 ) {
		$go( 'wait' );
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );
	$to      = apply_filters( 'islamicdilemma_contact_recipient', get_option( 'admin_email' ) );
	$subject = sprintf( '[%s] %s: %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $topics[ $topic ], $name );
	$body    = sprintf( "From: %s <%s>\nAbout: %s\n\n%s\n", $name, $email, $topics[ $topic ], $message );
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
	$go( wp_mail( $to, $subject, $body, $headers ) ? 'sent' : 'error' );
}
add_action( 'admin_post_nopriv_islamicdilemma_contact', 'islamicdilemma_contact_submit' );
add_action( 'admin_post_islamicdilemma_contact', 'islamicdilemma_contact_submit' );
