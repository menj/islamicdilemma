<?php
/**
 * Checks for the fixes from the live logs (1.4.4). Run: php tests/live-logs-test.php
 *
 * - islamicdilemma_moved_address(): the verse tags without separators and
 *   the dated addresses go to their new homes, and nothing else moves.
 * - islamicdilemma_hide_rest_users(): visitors get no /wp/v2/users routes,
 *   logged-in users keep them.
 * - The theme folder's .htaccess refuses the development files and leaves
 *   the assets open.
 *
 * @package islamicdilemma
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
$GLOBALS['logged_in'] = false;
$GLOBALS['posts']     = array( 'is-god-of-islam-distant' => 'publish', 'spiritual-blindness-appeal' => 'publish', 'a-draft' => 'draft' );
function home_url( $p = '/' ) { return 'https://islamicdilemma.info' . $p; }
function get_page_by_path( $slug ) { return isset( $GLOBALS['posts'][ $slug ] ) ? (object) array( 'post_name' => $slug, 'post_status' => $GLOBALS['posts'][ $slug ] ) : null; }
function get_permalink( $post ) { return home_url( '/' . $post->post_name . '/' ); }
function is_user_logged_in() { return $GLOBALS['logged_in']; }
function add_filter() {}
function add_action() {}
function remove_action() {}

$src = (string) file_get_contents( dirname( __DIR__ ) . '/inc/content/tools.php' );
if ( ! preg_match( '/^function islamicdilemma_moved_address\(.*?^}$/ms', $src, $m ) ) {
	echo "FAIL islamicdilemma_moved_address not found\n";
	exit( 1 );
}
eval( $m[0] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test only.
require dirname( __DIR__ ) . '/inc/security/hardening.php';

$fail = 0;
function check( $ok, $label ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	$fail += $ok ? 0 : 1;
}

$moves = array(
	'/tag/q-568/'                                => 'https://islamicdilemma.info/tag/q-5-68/',
	'tag/q-546'                                  => 'https://islamicdilemma.info/tag/q-5-46/',
	'/tag/q-548/'                                => 'https://islamicdilemma.info/tag/q-5-48/',
	'/tag/q-2946/'                               => 'https://islamicdilemma.info/tag/q-29-46/',
	'/tag/Q-5727/'                               => 'https://islamicdilemma.info/tag/q-57-27/',
	'/2026/10/27/is-the-god-of-islam-distant/'   => 'https://islamicdilemma.info/is-god-of-islam-distant/',
	'/2026/10/28/the-spiritual-blindness-appeal/' => 'https://islamicdilemma.info/spiritual-blindness-appeal/',
	'/2026/10/28/spiritual-blindness-appeal/'    => 'https://islamicdilemma.info/spiritual-blindness-appeal/',
	'/site-map/'                                 => 'https://islamicdilemma.info/sitemap/',
	'/2026/10/28/a-draft/'                       => '',
	'/2026/10/28/no-such-post/'                  => '',
	'/2026/10/is-the-god-of-islam-distant/'      => '',
	'/tag/q-5-68/'                               => '',
	'/is-god-of-islam-distant/'                  => '',
);
foreach ( $moves as $from => $to ) {
	check( islamicdilemma_moved_address( $from ) === $to, "$from -> " . ( '' === $to ? 'stays' : $to ) );
}

$routes = array( '/' => 1, '/wp/v2/posts' => 1, '/wp/v2/users' => 1, '/wp/v2/users/(?P<id>[\d]+)' => 1, '/wp/v2/users/me' => 1, '/wp/v2/usersettings' => 1 );
$anon   = islamicdilemma_hide_rest_users( $routes );
check( ! isset( $anon['/wp/v2/users'], $anon['/wp/v2/users/(?P<id>[\d]+)'], $anon['/wp/v2/users/me'] ) && isset( $anon['/wp/v2/posts'], $anon['/wp/v2/usersettings'] ), 'a visitor gets no user routes, and the others stay' );
$GLOBALS['logged_in'] = true;
check( islamicdilemma_hide_rest_users( $routes ) === $routes, 'a logged-in user keeps every route' );

$own = array();
foreach ( file( dirname( __DIR__ ) . '/.htaccess', FILE_IGNORE_NEW_LINES ) as $line ) {
	if ( preg_match( '/^RedirectMatch 403 (\S+)$/', $line, $mm ) || preg_match( '/^RewriteRule (\S+) - \[F\]$/', $line, $mm ) ) {
		$own[] = $mm[1];
	}
}
$hit = function ( $rel ) use ( $own ) {
	foreach ( $own as $p ) {
		if ( preg_match( '#' . $p . '#', '/wp-content/themes/islamicdilemma/' . $rel ) || preg_match( '#' . $p . '#', $rel ) ) {
			return true;
		}
	}
	return false;
};
foreach ( array( '.git/config', '.github/workflows/x.yml', '.claude/CLAUDE.md', '.gitignore', 'tests/content-sync.php', 'tools/style/lint.py', 'functions.php', 'inc/seo/seo.php' ) as $rel ) {
	check( $hit( $rel ), "theme .htaccess refuses $rel" );
}
foreach ( array( 'style.css', 'assets/css/theme.css', 'assets/img/birmingham-quran.webp', 'screenshot.png', 'assets/fonts/x.woff2' ) as $rel ) {
	check( ! $hit( $rel ), "theme .htaccess leaves $rel open" );
}
foreach ( array( 'tests', 'tools' ) as $d ) {
	$ht = (string) file_get_contents( dirname( __DIR__ ) . "/$d/.htaccess" );
	check( false !== strpos( $ht, 'Require all denied' ) && false !== strpos( $ht, 'Deny from all' ), "$d/.htaccess refuses everything" );
}

// Without mod_alias and mod_rewrite: the <If> on the request path refuses dot folders.
$ld_if = preg_match( '/<If "%\\{REQUEST_URI\\} =~ m#(.+)#">/', (string) file_get_contents( dirname( __DIR__ ) . '/.htaccess' ), $ld_mm ) ? $ld_mm[1] : '';
check( '' !== $ld_if && 1 === preg_match( '#' . $ld_if . '#', '/wp-content/themes/x/.git/config' ) && 0 === preg_match( '#' . $ld_if . '#', '/wp-content/themes/x/.well-known/a' ) && 0 === preg_match( '#' . $ld_if . '#', '/wp-content/themes/x/assets/css/a.min.css' ), 'without mod_alias and mod_rewrite, the <If> refuses .git/config and leaves .well-known and assets open' );

echo $fail ? "$fail failed\n" : "all passed\n";
exit( $fail ? 1 : 0 );
