<?php
/**
 * Build the static error pages in errors/ for the web server's ErrorDocument lines.
 *
 * They use the same markup and inline stylesheet as the drop-ins. The web server
 * serves them at the address of the page that failed, so every address in them is
 * absolute: fonts from /wp-content/themes/islamicdilemma/assets/fonts/, home at /.
 *
 * Run: php tools/build/error_pages.php (add --check to fail when a page is stale).
 *
 * @package islamicdilemma
 */

$id_root = dirname( __DIR__, 2 );
require $id_root . '/inc/presentation/error-page-html.php';

$id_check = in_array( '--check', $argv, true );
$id_site  = array(
	'site_name' => 'Islamic Dilemma Is No Dilemma',
	'home_url'  => '/',
	'css'       => islamicdilemma_error_page_css( $id_root, '/wp-content/themes/islamicdilemma/assets/fonts/' ),
);
if ( '' === $id_site['css'] ) {
	fwrite( STDERR, "cannot read assets/css/error-page.css\n" );
	exit( 1 );
}
if ( ! $id_check && ! is_dir( $id_root . '/errors' ) ) {
	mkdir( $id_root . '/errors' );
}
$id_stale = 0;
foreach ( array( '403', '500', '502', '503', '504' ) as $id_code ) {
	$id_html = islamicdilemma_error_page_html( $id_code, $id_site );
	$id_path = $id_root . '/errors/' . $id_code . '.html';
	if ( $id_check ) {
		if ( ! is_readable( $id_path ) || file_get_contents( $id_path ) !== $id_html ) {
			fwrite( STDERR, "stale: errors/$id_code.html\n" );
			++$id_stale;
		}
		continue;
	}
	if ( false === file_put_contents( $id_path, $id_html ) ) {
		fwrite( STDERR, "cannot write errors/$id_code.html\n" );
		exit( 1 );
	}
	echo "wrote errors/$id_code.html\n";
}
exit( $id_stale ? 1 : 0 );
