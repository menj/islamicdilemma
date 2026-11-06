<?php
/** Standalone checks for the error-page drop-ins. Run: php tests/error-pages-test.php */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
error_reporting( E_ALL );
define( 'ABSPATH', __DIR__ . '/' );
define( 'ISLAMICDILEMMA_VERSION', 'test' );
define( 'HOUR_IN_SECONDS', 3600 );
$dir = sys_get_temp_dir() . '/id-error-pages-' . getmypid();
@mkdir( $dir . '/islamicdilemma-errors', 0777, true );
define( 'WP_CONTENT_DIR', $dir );
$GLOBALS['multisite'] = false;
$GLOBALS['options']   = array();
function add_action() {}
function is_multisite() { return $GLOBALS['multisite']; }
function is_main_site() { return true; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); }
function delete_transient( $key ) {}
require dirname( __DIR__ ) . '/inc/presentation/error-pages.php';

$failed = 0;
function check( $ok, $message ) {
	global $failed;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $message . "\n";
	$failed += $ok ? 0 : 1;
}
function put( $name, $text ) {
	file_put_contents( WP_CONTENT_DIR . '/' . $name, $text );
	return WP_CONTENT_DIR . '/' . $name;
}

// The sister-theme mark counts only on a header comment line.
$p = put( 'maintenance.php', "<?php\n// Paulus error page drop-in 2.77.3. Written by the Paulus theme; safe to delete.\necho 'x';\n" );
check( islamicdilemma_dropin_is_replaceable( $p ), 'A sister drop-in with the mark on its // line is replaceable' );
$p = put( 'maintenance.php', "<?php\necho 'Written by the Paulus theme; safe to delete.';\n" );
check( ! islamicdilemma_dropin_is_replaceable( $p ), 'The mark inside code is not a header comment' );
$p = put( 'maintenance.php', "<?php\n/* note */ \$x = 1; // Written by the Paulus theme; safe to delete.\n" );
check( ! islamicdilemma_dropin_is_replaceable( $p ), 'The mark in a trailing comment does not count' );
$p = put( 'islamicdilemma-errors/503.html', "<!-- Murtadd error page 1.0. Written by the Murtadd theme; safe to delete. -->\n<!doctype html>\n" );
check( islamicdilemma_dropin_is_replaceable( $p ), 'A sister static page with the mark in its opening HTML comment is replaceable' );
$p = put( 'islamicdilemma-errors/503.html', "<!doctype html><p>Written by the Murtadd theme; safe to delete.</p>\n" );
check( ! islamicdilemma_dropin_is_replaceable( $p ), 'The mark in page text does not count' );
$p = put( 'islamicdilemma-errors/503.html', "<!-- A foreign page --> Written by the Murtadd theme; safe to delete.\n" );
check( ! islamicdilemma_dropin_is_replaceable( $p ), 'The mark after the comment closes does not count' );

// Removal on a theme switch: the theme's own files go, except on a network.
$ours = "<?php\n// Islamic Dilemma error page drop-in test. " . ISLAMICDILEMMA_ERROR_MARKER . "\n";
put( 'php-error.php', $ours );
put( 'islamicdilemma-errors/503.html', '<!-- Islamic Dilemma error page test. ' . ISLAMICDILEMMA_ERROR_MARKER . " -->\n" );
$GLOBALS['multisite'] = true;
islamicdilemma_remove_dropins();
check( file_exists( $dir . '/php-error.php' ) && file_exists( $dir . '/islamicdilemma-errors/503.html' ), 'On a network a switch leaves the shared files in place' );
$GLOBALS['multisite'] = false;
islamicdilemma_remove_dropins();
check( ! file_exists( $dir . '/php-error.php' ) && ! file_exists( $dir . '/islamicdilemma-errors/503.html' ), 'On a single site a switch removes the theme\'s own files' );
check( file_exists( $dir . '/maintenance.php' ), 'A file the theme did not write is kept' );

foreach ( array( '403', '500', '502', '503', '504' ) as $code ) {
	check( false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . "/errors/$code.html" ), '<link rel="icon" href="data:image/png;base64,' ), "errors/$code.html carries the tab icon as a data address" );
}

array_map( 'unlink', array_merge( glob( $dir . '/*.*' ), glob( $dir . '/islamicdilemma-errors/*' ) ) );
@rmdir( $dir . '/islamicdilemma-errors' );
@rmdir( $dir );
echo $failed ? "$failed failed\n" : "all passed\n";
exit( $failed ? 1 : 0 );
