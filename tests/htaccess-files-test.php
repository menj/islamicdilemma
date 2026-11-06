<?php
/**
 * Self-test for the "block direct file access" lines that inc/security/hardening.php
 * writes into .htaccess. Run: php tests/htaccess-files-test.php
 *
 * Takes islamicdilemma_sec_rewrite_prefix(), islamicdilemma_sec_rewrite_quote() and
 * islamicdilemma_sec_files_htaccess_lines() out of inc/security/hardening.php, builds the lines
 * for a site at the domain root and for one in a subfolder, checks their
 * shape (balanced IfModule, each RewriteCond followed by a RewriteRule,
 * every pattern a valid regular expression) and runs them over request
 * paths the way mod_rewrite does in .htaccess: the pattern is matched
 * against the path below the .htaccess folder, a RewriteCond against the
 * full REQUEST_URI.
 *
 * @since 1.4.5
 * @package islamicdilemma
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', '/srv/site/' );
function wp_normalize_path( $p ) { return str_replace( '\\', '/', (string) $p ); } // phpcs:ignore
function trailingslashit( $p ) { return rtrim( (string) $p, '/\\' ) . '/'; } // phpcs:ignore
function untrailingslashit( $p ) { return rtrim( (string) $p, '/\\' ); } // phpcs:ignore
function get_stylesheet_directory() { return $GLOBALS['id_theme_root'] . '/islamicdilemma'; } // phpcs:ignore
function get_theme_root() { return $GLOBALS['id_theme_root']; } // phpcs:ignore

$id_src = (string) file_get_contents( dirname( __DIR__ ) . '/inc/security/hardening.php' );
foreach ( array( 'islamicdilemma_sec_htaccess_lines', 'islamicdilemma_sec_rewrite_prefix', 'islamicdilemma_sec_rewrite_quote', 'islamicdilemma_sec_files_htaccess_lines', 'islamicdilemma_sec_files_if_pattern' ) as $id_fn ) {
	if ( ! preg_match( '/^function ' . $id_fn . '\(.*?^}$/ms', $id_src, $id_m ) ) {
		echo "FAIL $id_fn not found in inc/security/hardening.php\n";
		exit( 1 );
	}
	eval( $id_m[0] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test only.
}

$id_fail = 0;
function id_check( $ok, $label ) {
	global $id_fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	$id_fail += $ok ? 0 : 1;
}

/**
 * Whether the lines answer 403 for a request, as mod_rewrite would.
 *
 * @param string[] $lines Lines.
 * @param string   $uri   REQUEST_URI.
 * @param string   $base  URL path of the .htaccess folder, with slashes.
 * @return bool
 */
function id_forbidden( $lines, $uri, $base ) {
	$path  = (string) substr( $uri, strlen( $base ) );
	$conds = array();
	foreach ( $lines as $line ) {
		if ( preg_match( '/^RewriteCond %\{REQUEST_URI\} (!?)(\S+)$/', $line, $m ) ) {
			$conds[] = array( '!' === $m[1], $m[2] );
			continue;
		}
		if ( preg_match( '/^RewriteRule (\S+) - \[F\]$/', $line, $m ) ) {
			$pass = 1 === preg_match( '#' . $m[1] . '#', $path );
			foreach ( $conds as $c ) {
				$hit  = 1 === preg_match( '#' . $c[1] . '#', $uri );
				$pass = $pass && ( $c[0] ? ! $hit : $hit );
			}
			$conds = array();
			if ( $pass ) {
				return true;
			}
		}
	}
	return false;
}

$id_denied  = array( '.git/config', '.git/HEAD', '.gitignore', '.claude/CLAUDE.md', 'wp-content/themes/islamicdilemma/.git/config', 'wp-content/themes/islamicdilemma/.claude/FARUQI.md', 'wp-includes/version.php', 'wp-includes/blocks/archives.php', 'wp-includes/version.php/x', 'wp-includes/blocks/archives.php/wp-includes/js/tinymce/wp-tinymce.php', 'wp-includes/blocks/archives.php/wp-includes/ms-files.php', 'wp-settings.php/x', 'wp-admin/includes/admin.php', 'wp-content/themes/islamicdilemma/functions.php', 'wp-content/themes/twentytwentyfive/functions.php', 'wp-content/themes/islamicdilemma/tests/unlist-test.php', 'wp-content/themes/islamicdilemma/docs/README.md', 'wp-content/themes/islamicdilemma/tools/x.py', 'wp-content/themes/islamicdilemma/tools/', 'wp-content/themes/islamicdilemma/docs/readme.txt', 'wp-content/themes/islamicdilemma/docs/', 'wp-content/themes/islamicdilemma/docs', 'wp-config.php', 'wp-settings.php', 'wp-config-sample.php', 'wp-admin/install.php', 'readme.html' );
$id_allowed = array( '', 'index.php', 'wp-login.php', 'wp-cron.php', 'xmlrpc.php', 'wp-admin/', 'wp-admin/index.php', 'wp-admin/admin-ajax.php', 'wp-admin/post.php', 'wp-admin/load-scripts.php', 'wp-json/wp/v2/posts', 'wp-includes/js/tinymce/wp-tinymce.php', 'wp-includes/ms-files.php', 'wp-includes/js/dist/block-editor.min.js', 'wp-includes/css/dist/block-library/style.min.css', 'wp-content/themes/islamicdilemma/style.css', 'wp-content/themes/islamicdilemma/assets/js/theme.js', 'wp-content/themes/islamicdilemma/testsuite.css', 'wp-content/uploads/2026/11/photo.webp', 'what-is-the-qibla/', 'binary-choices/', 'documents/', 'articles/does-god-exist/', 'journeys/', 'quiz/', 'wp-content/themes/islamicdilemma/assets/downloads/islamic-dilemma-quick-reference.pdf', 'wp-content/themes/islamicdilemma/bin/x.php.txt', 'wp-content/themes/islamicdilemma/documents/readme.css', 'wp-content/themes/islamicdilemma/docs.css' );

$id_sites = array(
	'at the domain root' => array( '/srv/site/', '/' ),
	'in a subfolder'     => array( '/srv/site/', '/blog/' ),
);
foreach ( $id_sites as $id_label => $id_site ) {
	$GLOBALS['id_theme_root'] = '/srv/site/wp-content/themes';
	$id_all   = islamicdilemma_sec_files_htaccess_lines( $id_site[0] );
	$id_cut   = array_search( '<IfModule !mod_rewrite.c>', $id_all, true );
	$id_lines = false === $id_cut ? $id_all : array_slice( $id_all, 0, $id_cut );
	$id_rest  = false === $id_cut ? array() : array_slice( $id_all, $id_cut );
	id_check( 7 === count( $id_rest ) && '<IfModule mod_authz_core.c>' === $id_rest[1] && 'Require all denied' === $id_rest[3] && '</If>' === $id_rest[4] && '</IfModule>' === end( $id_rest ), "$id_label: a balanced Apache 2.4 <If> for a server without mod_rewrite" );

	id_check( '<IfModule mod_rewrite.c>' === $id_lines[0] && '</IfModule>' === end( $id_lines ) && 2 === count( preg_grep( '/IfModule/', $id_lines ) ), "$id_label: one balanced IfModule guard" );
	$id_shape = true;
	foreach ( $id_lines as $id_i => $id_line ) {
		if ( 0 === strpos( $id_line, 'RewriteCond' ) && 0 !== strpos( $id_lines[ $id_i + 1 ] ?? '', 'RewriteRule' ) ) {
			$id_shape = false;
		}
		if ( preg_match( '/^Rewrite(?:Rule|Cond %\{REQUEST_URI\} !?)\s*(\S+)/', $id_line, $id_m ) && false === @preg_match( '#' . $id_m[1] . '#', '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$id_shape = false;
		}
		if ( ! preg_match( '/^(<IfModule mod_rewrite\.c>|<\/IfModule>|RewriteEngine On|RewriteCond %\{REQUEST_URI\} \S+|RewriteRule \S+ - \[F\])$/', $id_line ) ) {
			$id_shape = false;
		}
	}
	id_check( $id_shape, "$id_label: every line is a known directive with a valid pattern" );

	foreach ( $id_denied as $id_p ) {
		id_check( id_forbidden( $id_lines, $id_site[1] . $id_p, $id_site[1] ), "$id_label: 403 for /$id_p" );
	}
	foreach ( $id_allowed as $id_p ) {
		id_check( ! id_forbidden( $id_lines, $id_site[1] . $id_p, $id_site[1] ), "$id_label: open /$id_p" );
	}
}

// A folder name with a regex metacharacter is quoted, so its own paths match.
$id_pfx = islamicdilemma_sec_rewrite_prefix( '/srv/wp.6/', '/srv/' );
id_check( 'wp\\.6/' === $id_pfx && 1 === preg_match( '#^' . $id_pfx . 'wp-includes/.+\\.php$#', 'wp.6/wp-includes/version.php' ) && 0 === preg_match( '#^' . $id_pfx . 'wp-includes/.+\\.php$#', 'wpx6/wp-includes/version.php' ), 'a dot in a folder name is escaped, and the prefix matches that folder only' );

// WordPress in its own folder below the .htaccess (wp/), themes beside it.
$GLOBALS['id_theme_root'] = '/srv/site/wp-content/themes';
$id_split = islamicdilemma_sec_files_htaccess_lines( '/srv/' );
id_check( (bool) preg_grep( '#\^site/wp-includes/#', $id_split ) && (bool) preg_grep( '#\^site/wp-content/themes/islamicdilemma/#', $id_split ), 'core and the theme below the .htaccess folder get their prefix' );
id_check( id_forbidden( $id_split, '/wp-config.php', '/' ) && id_forbidden( $id_split, '/site/wp-config.php', '/' ), 'wp-config.php is refused in the core folder and in the folder above it' );
id_check( id_forbidden( $id_split, '/site/wp-includes/version.php', '/' ) && ! id_forbidden( $id_split, '/site/wp-includes/js/tinymce/wp-tinymce.php', '/' ), 'the prefixed rules refuse and allow as at the root' );

// Without mod_rewrite: the <If> on the requested file's path, at the root,
// with WordPress in its own folder, and with a dot in a folder name.
$GLOBALS['id_theme_root'] = '/srv/site/wp-content/themes';
foreach ( array( '/srv/site/' => '', '/srv/' => '' ) as $id_home => $id_unused ) {
	$id_all = islamicdilemma_sec_files_htaccess_lines( $id_home );
	$id_if  = preg_match( '/^<If "%\{REQUEST_FILENAME\} =~ m#(.+)#">$/', (string) ( array_values( preg_grep( '/^<If /', $id_all ) )[0] ?? '' ), $id_m ) ? $id_m[1] : '';
	id_check( '' !== $id_if && false !== @preg_match( '#' . $id_if . '#', '' ), "without mod_rewrite (.htaccess in $id_home): the <If> holds a valid pattern" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	foreach ( array( 'wp-includes/version.php', 'wp-includes/blocks/archives.php', 'wp-admin/includes/admin.php', 'wp-settings.php', 'wp-config-sample.php', 'wp-config.php', 'readme.html', 'wp-admin/install.php', '.git/config', 'wp-content/themes/islamicdilemma/.claude/CLAUDE.md', 'wp-content/themes/islamicdilemma/functions.php', 'wp-content/themes/twentytwentyfive/functions.php', 'wp-content/themes/islamicdilemma/docs/README.md', 'wp-content/themes/islamicdilemma/tools', 'wp-content/themes/islamicdilemma/tests/content-sync.php' ) as $id_p ) {
		id_check( '' !== $id_if && 1 === preg_match( '#' . $id_if . '#', '/srv/site/' . $id_p ), "without mod_rewrite (.htaccess in $id_home): 403 for /srv/site/$id_p" );
	}
	foreach ( array( 'index.php', 'wp-login.php', 'xmlrpc.php', 'wp-cron.php', 'wp-admin/index.php', 'wp-admin/admin-ajax.php', 'wp-admin/load-styles.php', 'wp-includes/js/tinymce/wp-tinymce.php', 'wp-includes/ms-files.php', 'wp-includes/css/dashicons.min.css', 'wp-content/themes/islamicdilemma/style.css', 'wp-content/themes/islamicdilemma/assets/downloads/islamic-dilemma-quick-reference.pdf', 'wp-content/themes/islamicdilemma/docs.css', '.well-known/security.txt', 'wp-content/uploads/2026/11/photo.webp' ) as $id_p ) {
		id_check( '' !== $id_if && 0 === preg_match( '#' . $id_if . '#', '/srv/site/' . $id_p ), "without mod_rewrite (.htaccess in $id_home): open /srv/site/$id_p" );
	}
	if ( '/srv/' === $id_home ) {
		id_check( '' !== $id_if && 1 === preg_match( '#' . $id_if . '#', '/srv/wp-config.php' ), "without mod_rewrite (.htaccess in $id_home): wp-config.php one folder up is refused" );
	} else {
		id_check( '' !== $id_if && 0 === preg_match( '#' . $id_if . '#', '/srv/wp-config.php' ), "without mod_rewrite (.htaccess in $id_home): no pattern claims wp-config.php above the .htaccess folder" );
	}
}

// The .htaccess in the core folder itself (home path equals ABSPATH) with
// wp-config.php one folder up. No rule in that file can reach a request for
// the folder above, so none is written for it; docs/README.md says the web
// server must refuse it there. wp-config.php in the core folder stays refused.
$id_same = islamicdilemma_sec_files_htaccess_lines( ABSPATH );
id_check( 1 === count( preg_grep( '/^RewriteRule .*wp-config/', $id_same ) ), 'home path equal to ABSPATH: one wp-config.php rule, the core folder\'s own' );
id_check( id_forbidden( $id_same, '/wp-config.php', '/' ) && ! preg_grep( '/\.\.\//', $id_same ), 'home path equal to ABSPATH: wp-config.php in the core folder is refused, and no rule reaches above the folder' );
$id_if = islamicdilemma_sec_files_if_pattern( '/srv/site/' );
id_check( false !== strpos( $id_if, '/srv/site/wp\-content' ) || false !== strpos( $id_if, '/srv/site/wp-content' ), 'the <If> carries the real folder paths' );
$GLOBALS['id_theme_root'] = '/srv/my site/wp-content/themes';
id_check( '' === islamicdilemma_sec_files_if_pattern( '/srv/my site/' ) && ! preg_grep( '/^<If /', islamicdilemma_sec_files_htaccess_lines( '/srv/my site/' ) ), 'a folder path with a space gets no <If>, and the mod_rewrite rules stay' );
$GLOBALS['id_theme_root'] = '/srv/site/wp-content/themes';

// The theme folder's own .htaccess, which Apache reads before WordPress runs.
$id_ht   = (string) file_get_contents( dirname( __DIR__ ) . '/.htaccess' );
$id_own  = preg_match_all( '/^RedirectMatch 403 (\S+)$/m', $id_ht, $id_m ) ? $id_m[1] : array();
$id_rw   = preg_match_all( '/^RewriteRule (\S+) - \[F\]$/m', $id_ht, $id_m ) ? $id_m[1] : array();
$id_hits = function ( $patterns, $subject ) {
	foreach ( $patterns as $p ) {
		if ( preg_match( '#' . $p . '#', $subject ) ) {
			return true;
		}
	}
	return false;
};
id_check( 2 === count( $id_own ), "the theme's .htaccess has its 2 RedirectMatch lines" );
id_check( 3 === count( $id_rw ), "the theme's .htaccess has its 3 RewriteRule lines" );
foreach ( array( '.git/config', '.github/workflows/checks.yml', '.claude/CLAUDE.md', 'assets/.env', 'functions.php', 'inc/seo/seo.php/x' ) as $id_p ) {
	id_check( $id_hits( $id_own, '/wp-content/themes/islamicdilemma/' . $id_p ), "theme .htaccess: 403 for $id_p" );
}
foreach ( array( 'tests/content-sync.php', 'tools/style/lint.py', 'tools', 'docs/README.md', 'docs/', 'docs', '.git/config', '.claude/CLAUDE.md', 'functions.php' ) as $id_p ) {
	id_check( $id_hits( $id_rw, $id_p ), "theme .htaccess rewrite: 403 for $id_p" );
}
foreach ( array( 'style.css', 'assets/js/tools.js', 'assets/downloads/islamic-dilemma-quick-reference.pdf', 'docs.css', 'assets/docs/x.css', '.well-known/x', 'assets/brand/favicon.ico', 'assets/brand/favicon.svg', 'assets/brand/favicon-32.png', 'assets/brand/site.webmanifest' ) as $id_p ) {
	id_check( ! $id_hits( $id_own, '/wp-content/themes/islamicdilemma/' . $id_p ) && ! $id_hits( $id_rw, $id_p ), "theme .htaccess: open $id_p" );
}

// Markdown files by their own name, with no module beyond the core.
$id_md = preg_match( '/<FilesMatch "([^"]*md[^"]*)">\s*<IfModule mod_authz_core\.c>\s*Require all denied/', $id_ht, $id_m ) ? $id_m[1] : '';
id_check( '' !== $id_md, 'the theme .htaccess refuses Markdown files with a FilesMatch' );
foreach ( array( 'README.md', 'CREDITS.md', 'notes.MD' ) as $id_p ) {
	id_check( '' !== $id_md && 1 === preg_match( '#' . $id_md . '#', $id_p ), "theme .htaccess FilesMatch: 403 for $id_p" );
}
foreach ( array( 'style.css', 'readme.txt', 'main.md.css' ) as $id_p ) {
	id_check( '' === $id_md || 0 === preg_match( '#' . $id_md . '#', $id_p ), "theme .htaccess FilesMatch: open $id_p" );
}

// tests/, tools/ and docs/ refuse every request into them, whatever the theme's address.
foreach ( array( 'tests', 'tools', 'docs' ) as $id_d ) {
	$id_dir = (string) @file_get_contents( dirname( __DIR__ ) . "/$id_d/.htaccess" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	id_check( false !== strpos( $id_dir, 'Require all denied' ) && false !== strpos( $id_dir, 'Deny from all' ), "$id_d/.htaccess refuses everything, on Apache 2.4 and 2.2" );
}

// The xmlrpc.php block, for Apache 2.4 and 2.2.
$id_x = islamicdilemma_sec_htaccess_lines();
id_check( '<Files xmlrpc.php>' === $id_x[0] && '</Files>' === end( $id_x ) && in_array( 'Require all denied', $id_x, true ) && in_array( 'Deny from all', $id_x, true ), 'the xmlrpc.php block denies on Apache 2.4 and 2.2' );

echo $id_fail ? "$id_fail failed\n" : "all passed\n";
exit( $id_fail ? 1 : 0 );
