<?php
/**
 * Error pages in the theme's design.
 *
 * WordPress shows three files from wp-content when it cannot run the theme:
 * maintenance.php during an update (503), db-error.php when the database is
 * down (503) and php-error.php after a fatal PHP error (500). The theme
 * writes all three on activation, and again on an admin page load when one
 * is missing or out of date. Each is a standalone page: status headers, then
 * HTML with its stylesheet inline, so it works when WordPress does not.
 *
 * A file the theme did not write is left alone. A file written by one of the
 * sister themes, marked "Written by the ... theme; safe to delete", is
 * replaced. Switching to another theme removes the theme's own files.
 *
 * The static pages in errors/ cover the errors the web server answers before
 * WordPress starts. tools/build/error_pages.php builds them.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/error-page-html.php';

/**
 * The drop-ins, with the status each sends and its Retry-After in seconds.
 *
 * @return array<string,array{status:int,retry:int}>
 */
function islamicdilemma_dropin_specs() {
	return array(
		'php-error.php'   => array( 'status' => 500, 'retry' => 0 ),
		'db-error.php'    => array( 'status' => 503, 'retry' => 600 ),
		'maintenance.php' => array( 'status' => 503, 'retry' => 300 ),
	);
}

/**
 * The values each page is built from: the site name, the home address and the
 * stylesheet with its fonts in the theme folder.
 *
 * @return array{site_name:string,home_url:string,css:string}
 */
function islamicdilemma_dropin_site() {
	$theme_path = untrailingslashit( (string) wp_parse_url( get_stylesheet_directory_uri(), PHP_URL_PATH ) );
	$home_path  = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	return array(
		'site_name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'home_url'  => '' !== $home_path ? $home_path : '/',
		'css'       => islamicdilemma_error_page_css( get_stylesheet_directory(), $theme_path . '/assets/fonts/' ),
	);
}

/**
 * The PHP that opens a drop-in: the marker line and the status headers.
 *
 * @param int $status HTTP status.
 * @param int $retry  Retry-After in seconds, 0 for none.
 * @return string
 */
function islamicdilemma_dropin_headers( $status, $retry ) {
	$reason = 503 === $status ? 'Service Unavailable' : 'Internal Server Error';
	return "if ( ! headers_sent() ) {\n"
		. "\theader( 'HTTP/1.1 {$status} {$reason}', true, {$status} );\n"
		. ( $retry ? "\theader( 'Retry-After: {$retry}' );\n" : '' )
		. "\theader( 'Content-Type: text/html; charset=utf-8' );\n"
		. "\theader( 'X-Robots-Tag: noindex' );\n"
		. "\theader( 'Cache-Control: no-store, max-age=0' );\n"
		. "}\n";
}

/**
 * The full text of one drop-in.
 *
 * @param string $file A key of islamicdilemma_dropin_specs().
 * @return string
 */
function islamicdilemma_dropin_text( $file ) {
	$site = islamicdilemma_dropin_site();
	$head = "<?php\n// Islamic Dilemma error page drop-in " . ISLAMICDILEMMA_VERSION . '. ' . ISLAMICDILEMMA_ERROR_MARKER . "\n";
	if ( 'php-error.php' === $file ) {
		// A file missing or half written in the theme or in WordPress itself
		// is an update in progress: that visitor gets a 503 that asks them
		// to come back in a minute. The file is tested, and so is the first line of the
		// message with its " in /path:line" removed, because a core update
		// stopped halfway fails in wp-settings.php and names the missing
		// wp-includes/ file only in the message. The stack trace below that
		// line is ignored: it names wp-includes/ for any fatal raised through
		// a hook.
		return $head
			. "\$id_error    = isset( \$error ) && is_array( \$error ) ? \$error : error_get_last();\n"
			. "\$id_message  = is_array( \$id_error ) && isset( \$id_error['message'] ) ? (string) \$id_error['message'] : '';\n"
			. "\$id_file     = is_array( \$id_error ) && isset( \$id_error['file'] ) ? str_replace( '\\\\', '/', (string) \$id_error['file'] ) : '';\n"
			. "\$id_first    = preg_replace( '~ in /\\S+:\\d+\$~', '', (string) strtok( \$id_message, \"\\n\" ) );\n"
			. "\$id_updating = '' !== \$id_message\n"
			. "\t&& preg_match( '~/(wp-content/themes/|wp-includes/|wp-admin/)~', \$id_file . ' ' . \$id_first )\n"
			. "\t&& preg_match( '~Failed opening required|No such file or directory|syntax error|Unclosed|Unterminated|unexpected end of file|Call to undefined (function|method)|Class \"[^\"]+\" not found~', \$id_first );\n"
			. "if ( ! headers_sent() ) {\n"
			. "\tif ( \$id_updating ) {\n"
			. "\t\theader( 'HTTP/1.1 503 Service Unavailable', true, 503 );\n"
			. "\t\theader( 'Retry-After: 120' );\n"
			. "\t} else {\n"
			. "\t\theader( 'HTTP/1.1 500 Internal Server Error', true, 500 );\n"
			. "\t}\n"
			. "\theader( 'Content-Type: text/html; charset=utf-8' );\n"
			. "\theader( 'X-Robots-Tag: noindex' );\n"
			. "\theader( 'Cache-Control: no-store, max-age=0' );\n"
			. "}\n"
			. "if ( \$id_updating ) :\n?>\n"
			. islamicdilemma_error_page_html( 'updating', $site )
			. "<?php else : ?>\n"
			. islamicdilemma_error_page_html( '500', $site )
			. "<?php endif;\n";
	}
	$spec = islamicdilemma_dropin_specs()[ $file ];
	$key  = 'db-error.php' === $file ? 'db-error' : 'maintenance';
	return $head . islamicdilemma_dropin_headers( $spec['status'], $spec['retry'] ) . "?>\n"
		. islamicdilemma_error_page_html( $key, $site );
}

/**
 * The first bytes of a file, or '' when it cannot be read.
 *
 * @param string $path File path.
 * @return string
 */
function islamicdilemma_dropin_head( $path ) {
	return is_readable( $path ) ? (string) file_get_contents( $path, false, null, 0, 400 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Whether a file was written by this theme.
 *
 * @param string $path File path.
 * @return bool
 */
function islamicdilemma_dropin_is_ours( $path ) {
	return false !== strpos( islamicdilemma_dropin_head( $path ), ISLAMICDILEMMA_ERROR_MARKER );
}

/**
 * Whether a file may be written over: this theme's own, or a sister theme's,
 * which marks its files "Written by the ... theme; safe to delete".
 *
 * @param string $path File path.
 * @return bool
 */
function islamicdilemma_dropin_is_replaceable( $path ) {
	return (bool) preg_match( "/Written by the [A-Za-z0-9 .'-]{1,40} theme; safe to delete/", islamicdilemma_dropin_head( $path ) );
}

/**
 * The state of one drop-in: missing, installed (ours and current), outdated
 * (ours, from another version or with another site name), replaceable (a
 * sister theme's) or foreign (no marker, left alone).
 *
 * @param string $file File name.
 * @return string
 */
function islamicdilemma_dropin_state( $file ) {
	$path = WP_CONTENT_DIR . '/' . $file;
	if ( ! file_exists( $path ) ) {
		return 'missing';
	}
	if ( islamicdilemma_dropin_is_ours( $path ) ) {
		return (string) file_get_contents( $path ) === islamicdilemma_dropin_text( $file ) ? 'installed' : 'outdated'; // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return islamicdilemma_dropin_is_replaceable( $path ) ? 'replaceable' : 'foreign';
}

/**
 * Write the drop-ins into wp-content. A file with no sister-theme marker is
 * kept. Each file goes to a temporary name first and is then moved into
 * place, so WordPress never reads half a page.
 *
 * On a network every site shares wp-content, so only the main site writes.
 *
 * @return array<string,string>|null File name => written, current, kept or failed; null on a subsite.
 */
function islamicdilemma_install_dropins() {
	if ( is_multisite() && ! is_main_site() ) {
		return null;
	}
	$result = array();
	foreach ( array_keys( islamicdilemma_dropin_specs() ) as $file ) {
		$state = islamicdilemma_dropin_state( $file );
		if ( 'installed' === $state || 'foreign' === $state ) {
			$result[ $file ] = 'installed' === $state ? 'current' : 'kept';
			continue;
		}
		$target = WP_CONTENT_DIR . '/' . $file;
		$text   = islamicdilemma_dropin_text( $file );
		$tmp    = $target . '.' . uniqid( 'islamicdilemma-', true ) . '.tmp';
		if ( strlen( $text ) !== @file_put_contents( $tmp, $text ) || ! @rename( $tmp, $target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
			if ( file_exists( $tmp ) ) {
				@unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
			}
			$result[ $file ] = 'failed';
			continue;
		}
		$result[ $file ] = 'written';
	}
	update_option( 'islamicdilemma_dropins_status', $result, false );
	if ( in_array( 'failed', $result, true ) ) {
		set_transient( 'islamicdilemma_dropins_retry', 1, HOUR_IN_SECONDS );
	} else {
		delete_transient( 'islamicdilemma_dropins_retry' );
	}
	return $result;
}
add_action( 'after_switch_theme', 'islamicdilemma_install_dropins' );

/**
 * On an admin page, for someone who may change the theme, write the drop-ins
 * again when one is missing, out of date or a sister theme's. A failed write
 * waits an hour before the next try.
 */
function islamicdilemma_maybe_install_dropins() {
	if ( wp_doing_ajax() || ! current_user_can( 'edit_theme_options' ) || ( is_multisite() && ! is_main_site() ) || get_transient( 'islamicdilemma_dropins_retry' ) ) {
		return;
	}
	foreach ( array_keys( islamicdilemma_dropin_specs() ) as $file ) {
		if ( in_array( islamicdilemma_dropin_state( $file ), array( 'missing', 'outdated', 'replaceable' ), true ) ) {
			islamicdilemma_install_dropins();
			return;
		}
	}
}
add_action( 'admin_init', 'islamicdilemma_maybe_install_dropins' );

/**
 * Remove the theme's own drop-ins when another theme is activated.
 */
function islamicdilemma_remove_dropins() {
	if ( ! is_multisite() || is_main_site() ) {
		foreach ( array_keys( islamicdilemma_dropin_specs() ) as $file ) {
			$path = WP_CONTENT_DIR . '/' . $file;
			if ( file_exists( $path ) && islamicdilemma_dropin_is_ours( $path ) ) {
				@unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
			}
		}
	}
	delete_option( 'islamicdilemma_dropins_status' );
	delete_transient( 'islamicdilemma_dropins_retry' );
}
add_action( 'switch_theme', 'islamicdilemma_remove_dropins' );

/**
 * The "Install error pages" button.
 */
function islamicdilemma_handle_install_dropins() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'islamicdilemma' ), 403 );
	}
	check_admin_referer( 'islamicdilemma_install_error_pages' );
	delete_transient( 'islamicdilemma_dropins_retry' );
	$result = islamicdilemma_install_dropins();
	$flag   = null === $result ? '' : ( in_array( 'failed', (array) $result, true ) ? 'failed' : 'done' );
	wp_safe_redirect( admin_url( 'themes.php?page=islamicdilemma-content&tab=errors' . ( $flag ? '&dropins=' . $flag : '' ) ) );
	exit;
}
add_action( 'admin_post_islamicdilemma_install_error_pages', 'islamicdilemma_handle_install_dropins' );

/**
 * The .htaccess lines that point the web server at the static pages.
 *
 * @return string
 */
function islamicdilemma_error_htaccess_snippet() {
	$base  = untrailingslashit( (string) wp_parse_url( get_stylesheet_directory_uri(), PHP_URL_PATH ) ) . '/errors/';
	$lines = array( '# Islamic Dilemma: pages for errors the web server answers before WordPress runs' );
	foreach ( array( 403, 500, 502, 503, 504 ) as $code ) {
		$lines[] = 'ErrorDocument ' . $code . ' ' . $base . $code . '.html';
	}
	return implode( "\n", $lines );
}

/**
 * The Error pages tab of Appearance → Theme content.
 */
function islamicdilemma_render_error_pages_tab() {
	$labels  = array(
		'missing'     => __( 'Not installed', 'islamicdilemma' ),
		'installed'   => __( 'Installed and current', 'islamicdilemma' ),
		'outdated'    => __( 'Installed by this theme, out of date', 'islamicdilemma' ),
		'replaceable' => __( 'Written by a sister theme; replaced on install', 'islamicdilemma' ),
		'foreign'     => __( 'Another file is there; left alone', 'islamicdilemma' ),
		'failed'      => __( 'Could not be written: wp-content is not writable', 'islamicdilemma' ),
	);
	$saved   = (array) get_option( 'islamicdilemma_dropins_status', array() );
	$subsite = is_multisite() && ! is_main_site();
	$flag    = isset( $_GET['dropins'] ) ? sanitize_key( wp_unslash( $_GET['dropins'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="islamicdilemma-error-pages">
		<h2><?php esc_html_e( 'Error pages', 'islamicdilemma' ); ?></h2>
		<?php if ( 'failed' === $flag ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'Some error pages could not be written. The table shows which.', 'islamicdilemma' ); ?></p></div>
		<?php elseif ( 'done' === $flag ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'The error pages are installed.', 'islamicdilemma' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'WordPress shows these files from wp-content when it cannot run the theme: maintenance.php during an update, db-error.php when the database is down and php-error.php after a fatal PHP error. The theme writes them on activation and after each update. A file the theme did not write is left alone.', 'islamicdilemma' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'File', 'islamicdilemma' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'islamicdilemma' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( array_keys( islamicdilemma_dropin_specs() ) as $file ) : ?>
				<?php
				$state = islamicdilemma_dropin_state( $file );
				if ( 'installed' !== $state && 'failed' === ( $saved[ $file ] ?? '' ) ) {
					$state = 'failed';
				}
				?>
				<tr><td><code>wp-content/<?php echo esc_html( $file ); ?></code></td><td class="is-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $labels[ $state ] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $subsite ) : ?>
			<p class="description"><?php esc_html_e( 'Every site on the network shares wp-content, so the error pages are installed from the main site.', 'islamicdilemma' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="islamicdilemma_install_error_pages">
				<?php wp_nonce_field( 'islamicdilemma_install_error_pages' ); ?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Install error pages', 'islamicdilemma' ); ?></button></p>
			</form>
		<?php endif; ?>
		<h2><?php esc_html_e( 'Web server errors', 'islamicdilemma' ); ?></h2>
		<p><?php esc_html_e( 'Some errors come from the web server before WordPress starts. Static pages for them are in the theme’s errors folder. Add these lines to the .htaccess file in the site root to use them:', 'islamicdilemma' ); ?></p>
		<textarea id="islamicdilemma-htaccess" class="large-text code" rows="7" readonly aria-label="<?php esc_attr_e( 'Lines for .htaccess', 'islamicdilemma' ); ?>"><?php echo esc_textarea( islamicdilemma_error_htaccess_snippet() ); ?></textarea>
		<p><button type="button" class="button islamicdilemma-copy" data-target="islamicdilemma-htaccess" data-done="<?php esc_attr_e( 'Copied', 'islamicdilemma' ); ?>"><?php esc_html_e( 'Copy the lines', 'islamicdilemma' ); ?></button> <span class="islamicdilemma-copy-status" aria-live="polite"></span></p>
	</div>
	<?php
}
