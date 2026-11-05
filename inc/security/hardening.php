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
