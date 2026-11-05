<?php
/**
 * Small hardening the server logs asked for (1.4.4), as in the sister themes.
 *
 * - The REST user list (/wp/v2/users and /wp/v2/users/<id>) is hidden from
 *   visitors who are not logged in. It names every account with posts and
 *   hands out a Gravatar hash of its email. Logged-in editors keep it: the
 *   block editor's author panel reads it.
 * - XML-RPC is off: no methods, no pingback header, and xmlrpc.php answers
 *   403. The site publishes from the editor only, and password
 *   guessing through xmlrpc.php is the commonest attack in the logs.
 *   Define ISLAMICDILEMMA_ALLOW_XMLRPC as true in wp-config.php for a tool
 *   that needs it.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drops the user routes for a visitor who is not logged in.
 *
 * @param array $endpoints REST routes.
 * @return array
 */
function islamicdilemma_hide_rest_users( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	foreach ( array_keys( (array) $endpoints ) as $route ) {
		if ( preg_match( '#^/wp/v2/users(/|$)#', (string) $route ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'islamicdilemma_hide_rest_users', 99 );

/**
 * Whether XML-RPC stays off.
 *
 * @return bool
 */
function islamicdilemma_xmlrpc_off() {
	return ! ( defined( 'ISLAMICDILEMMA_ALLOW_XMLRPC' ) && ISLAMICDILEMMA_ALLOW_XMLRPC );
}

if ( islamicdilemma_xmlrpc_off() ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array', 999 );
	add_filter( 'pings_open', '__return_false', 999 );
	add_filter(
		'wp_headers',
		function ( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		}
	);
	remove_action( 'wp_head', 'rsd_link' );
	// xmlrpc.php itself answers 403 before it lists even its system methods.
	add_action(
		'after_setup_theme',
		function () {
			if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
				status_header( 403 );
				header( 'Content-Type: text/plain; charset=utf-8' );
				echo 'XML-RPC is turned off on this site.';
				exit;
			}
		},
		1
	);
}

/*
 * The web server refuses what never serves a page, before WordPress runs.
 * Two sections in the site's .htaccess: "Block xmlrpc.php", the marker the
 * sister themes share, while XML-RPC is off, and "Islamic Dilemma: block
 * direct file access" for PHP in wp-includes, wp-admin/includes and the
 * themes folder, the installer and version files, .git and .claude, and this
 * theme's tests, tools and docs folders. Define
 * ISLAMICDILEMMA_BLOCK_DIRECT_FILES as false in wp-config.php to leave the
 * second one out. Both are written on an administrator's visit, at most once
 * an hour, and removed when another theme is activated (on a single site:
 * on a network another site may still use them). Without mod_rewrite an
 * Apache 2.4 <If> on the file's own path refuses the same files.
 */

define( 'ISLAMICDILEMMA_SEC_FILES_MARKER', 'Islamic Dilemma: block direct file access' );

/**
 * Lines that deny xmlrpc.php at the web server, for Apache 2.4 and 2.2.
 *
 * @return string[]
 */
function islamicdilemma_sec_htaccess_lines() {
	return array(
		'<Files xmlrpc.php>',
		'<IfModule mod_authz_core.c>',
		'Require all denied',
		'</IfModule>',
		'<IfModule !mod_authz_core.c>',
		'Order Deny,Allow',
		'Deny from all',
		'</IfModule>',
		'</Files>',
	);
}

/**
 * A folder relative to the folder that holds .htaccess, as a mod_rewrite
 * pattern prefix ('' for the same folder, 'wp/' below it), or null when it
 * lies outside that folder.
 *
 * @param string $path Absolute path of a folder.
 * @param string $home Absolute path of the folder that holds .htaccess.
 * @return string|null
 */
function islamicdilemma_sec_rewrite_prefix( $path, $home ) {
	$path = trailingslashit( wp_normalize_path( $path ) );
	$home = trailingslashit( wp_normalize_path( $home ) );
	if ( 0 !== strpos( $path, $home ) ) {
		return null;
	}
	$rel = (string) substr( $path, strlen( $home ) );
	return '' === $rel ? '' : implode( '/', array_map( 'islamicdilemma_sec_rewrite_quote', explode( '/', rtrim( $rel, '/' ) ) ) ) . '/';
}

/**
 * One path segment quoted for a mod_rewrite pattern.
 *
 * @param string $segment Folder name.
 * @return string
 */
function islamicdilemma_sec_rewrite_quote( $segment ) {
	// '\\\\$0' is the PHP string \\$0: a literal backslash, then the match.
	return preg_replace( '/[^A-Za-z0-9_-]/', '\\\\$0', (string) $segment );
}

/**
 * mod_rewrite lines that answer 403 for files that never serve a page.
 *
 * wp-includes/js/tinymce/wp-tinymce.php (the classic editor) and
 * wp-includes/ms-files.php (uploads on old multisite networks) stay open, as
 * do the admin, admin-ajax.php, wp-cron.php, wp-login.php and the REST API.
 *
 * @param string $home Folder that holds .htaccess; get_home_path() if empty.
 * @return string[]
 */
function islamicdilemma_sec_files_htaccess_lines( $home = '' ) {
	if ( '' === $home ) {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$home = get_home_path();
	}
	$core   = islamicdilemma_sec_rewrite_prefix( ABSPATH, $home );
	$themes = islamicdilemma_sec_rewrite_prefix( get_theme_root(), $home );
	$theme  = islamicdilemma_sec_rewrite_prefix( get_stylesheet_directory(), $home );

	$rules = array( 'RewriteRule (^|/)\.(git|claude) - [F]' );
	if ( null !== $core ) {
		// The two exceptions are exact paths after the prefix; a PHP file
		// with either one appended as path info is still refused.
		$rules[] = 'RewriteRule ^' . $core . 'wp-includes/(?!(js/tinymce/wp-tinymce|ms-files)\.php$).+\.php(/|$) - [F]';
		$rules[] = 'RewriteRule ^' . $core . 'wp-admin/includes/.+\.php(/|$) - [F]';
		// WordPress also reads wp-config.php from the folder above its own.
		$parent = '' === $core ? null : islamicdilemma_sec_rewrite_prefix( dirname( untrailingslashit( wp_normalize_path( ABSPATH ) ) ), $home );
		if ( null !== $parent ) {
			$rules[] = 'RewriteRule ^' . $parent . 'wp-config\.php(/|$) - [F]';
		}
		$rules[] = 'RewriteRule ^' . $core . '(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)(/|$) - [F]';
	}
	if ( null !== $themes && '' !== $themes ) {
		$rules[] = 'RewriteRule ^' . $themes . '.+\.php(/|$) - [F]';
	}
	if ( null !== $theme && '' !== $theme ) {
		$rules[] = 'RewriteRule ^' . $theme . '(tests|tools|docs)(/|$) - [F]';
	}

	$lines = array_merge( array( '<IfModule mod_rewrite.c>', 'RewriteEngine On' ), $rules, array( '</IfModule>' ) );

	// Without mod_rewrite, Apache 2.4 tests the requested file's own path.
	$if = islamicdilemma_sec_files_if_pattern( $home );
	if ( '' !== $if ) {
		$lines = array_merge( $lines, array( '<IfModule !mod_rewrite.c>', '<IfModule mod_authz_core.c>', '<If "%{REQUEST_FILENAME} =~ m#' . $if . '#">', 'Require all denied', '</If>', '</IfModule>', '</IfModule>' ) );
	}
	return $lines;
}

/**
 * The same files as one regular expression on the requested file's path,
 * for an Apache 2.4 <If> where mod_rewrite is missing. '' when a folder
 * path holds a character the expression cannot carry.
 *
 * @param string $home Folder that holds .htaccess.
 * @return string
 */
function islamicdilemma_sec_files_if_pattern( $home ) {
	$dirs = array(
		'home'   => untrailingslashit( wp_normalize_path( $home ) ),
		'core'   => untrailingslashit( wp_normalize_path( ABSPATH ) ),
		'themes' => untrailingslashit( wp_normalize_path( get_theme_root() ) ),
		'theme'  => untrailingslashit( wp_normalize_path( get_stylesheet_directory() ) ),
	);
	$q = array();
	foreach ( $dirs as $key => $dir ) {
		if ( '' === $dir || preg_match( '/[\s"\\\\]/', $dir ) ) {
			return '';
		}
		$q[ $key ] = preg_quote( $dir, '#' );
	}
	$parts = array(
		$q['home'] . '/(.+/)?\.(git|claude)',
		$q['core'] . '/wp-includes/(?!(js/tinymce/wp-tinymce|ms-files)\.php$).+\.php$',
		$q['core'] . '/wp-admin/includes/.+\.php$',
		$q['core'] . '/(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)$',
		preg_quote( dirname( $dirs['core'] ), '#' ) . '/wp-config\.php$',
		$q['themes'] . '/.+\.php$',
		$q['theme'] . '/(tests|tools|docs)(/|$)',
	);
	return '^(' . implode( '|', $parts ) . ')';
}

/**
 * Whether the direct-access section is wanted. On unless
 * ISLAMICDILEMMA_BLOCK_DIRECT_FILES is defined false.
 *
 * @return bool
 */
function islamicdilemma_sec_files_block_on() {
	return ! ( defined( 'ISLAMICDILEMMA_BLOCK_DIRECT_FILES' ) && ! ISLAMICDILEMMA_BLOCK_DIRECT_FILES );
}

/**
 * Path of the site's .htaccess, or '' when the server is not Apache or the
 * file cannot be written.
 *
 * @return string
 */
function islamicdilemma_sec_htaccess_file() {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	if ( empty( $GLOBALS['is_apache'] ) ) {
		return '';
	}
	$file = get_home_path() . '.htaccess';
	if ( file_exists( $file ) ? ! wp_is_writable( $file ) : ! wp_is_writable( dirname( $file ) ) ) {
		return '';
	}
	return $file;
}

/**
 * Keep both sections in step with the settings: written when wanted and
 * missing or changed, removed when not wanted. At most once an hour unless
 * forced, so a lost or overwritten .htaccess is repaired on the next visit.
 *
 * @param bool $force Skip the hourly throttle.
 */
function islamicdilemma_sec_sync_htaccess( $force = false ) {
	if ( ! $force && get_transient( 'islamicdilemma_sec_htaccess_checked' ) ) {
		return;
	}
	set_transient( 'islamicdilemma_sec_htaccess_checked', 1, HOUR_IN_SECONDS );
	$file = islamicdilemma_sec_htaccess_file();
	if ( '' === $file ) {
		return;
	}
	$sections = array(
		'Block xmlrpc.php'               => islamicdilemma_xmlrpc_off() ? islamicdilemma_sec_htaccess_lines() : array(),
		ISLAMICDILEMMA_SEC_FILES_MARKER => islamicdilemma_sec_files_block_on() ? islamicdilemma_sec_files_htaccess_lines() : array(),
	);
	foreach ( $sections as $marker => $want ) {
		if ( extract_from_markers( $file, $marker ) !== $want ) {
			insert_with_markers( $file, $marker, $want );
		}
	}
}
add_action( 'admin_init', 'islamicdilemma_sec_sync_htaccess' );
add_action(
	'after_switch_theme',
	function () {
		islamicdilemma_sec_sync_htaccess( true );
	}
);

/**
 * Take both sections out when another theme is activated. A sister theme
 * writes its own xmlrpc block back on its first admin visit. On a network
 * every site shares one .htaccess and another site may still run this
 * theme, so the sections stay.
 */
function islamicdilemma_sec_remove_htaccess() {
	if ( is_multisite() ) {
		return;
	}
	$file = islamicdilemma_sec_htaccess_file();
	if ( '' !== $file ) {
		insert_with_markers( $file, 'Block xmlrpc.php', array() );
		insert_with_markers( $file, ISLAMICDILEMMA_SEC_FILES_MARKER, array() );
	}
	delete_transient( 'islamicdilemma_sec_htaccess_checked' );
}
add_action( 'switch_theme', 'islamicdilemma_sec_remove_htaccess' );
