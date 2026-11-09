<?php
/**
 * Admin screen (Appearance → Theme content), sync notices and WP-CLI
 * commands for the theme's content.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Status of every theme item, for the admin screen and WP-CLI.
 *
 * @return array List of array( key, title, type, status, post_id ).
 */
function islamicdilemma_content_status() {
	$registry = get_option( ISLAMICDILEMMA_REGISTRY, array() );
	$rows     = array();
	foreach ( islamicdilemma_theme_items() as $key => $item ) {
		$post_id = isset( $registry[ $key ] ) ? (int) $registry[ $key ] : 0;
		$post    = $post_id ? get_post( $post_id ) : null;
		if ( ! $post_id ) {
			$status = 'not-imported';
		} elseif ( ! $post || 'trash' === $post->post_status ) {
			$status = 'deleted';
		} elseif ( islamicdilemma_is_edited( $post ) ) {
			$status = 'edited';
		} elseif ( islamicdilemma_item_source_hash( $item ) !== get_post_meta( $post->ID, ISLAMICDILEMMA_SRC_META, true ) ) {
			$status = 'update-pending';
		} else {
			$status = 'synced';
		}
		$rows[] = array(
			'key'     => $key,
			'title'   => $item['title'],
			'type'    => $item['type'],
			'status'  => $status,
			'post_id' => $post ? $post->ID : 0,
		);
	}
	return $rows;
}

/**
 * Human-readable label for a status.
 *
 * @param string $status Status slug.
 * @return string
 */
function islamicdilemma_status_label( $status ) {
	$labels = array(
		'synced'         => __( 'Matches the theme', 'islamicdilemma' ),
		'edited'         => __( 'Edited by you — kept as is', 'islamicdilemma' ),
		'deleted'        => __( 'Deleted by you — not recreated', 'islamicdilemma' ),
		'update-pending' => __( 'Theme has a newer version', 'islamicdilemma' ),
		'not-imported'   => __( 'Not imported yet', 'islamicdilemma' ),
	);
	return $labels[ $status ] ?? $status;
}

/**
 * Register the admin screen.
 */
function islamicdilemma_content_menu() {
	add_theme_page(
		__( 'Theme content', 'islamicdilemma' ),
		__( 'Theme content', 'islamicdilemma' ),
		'edit_theme_options',
		'islamicdilemma-content',
		'islamicdilemma_content_screen'
	);
}
add_action( 'admin_menu', 'islamicdilemma_content_menu' );

/**
 * Handle the screen's form: sync now, or restore selected items.
 */
function islamicdilemma_content_actions() {
	if ( empty( $_POST['islamicdilemma_action'] ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	if ( ! in_array( $_POST['islamicdilemma_action'], array( 'settings', 'sync', 'restore', 'reset_dates' ), true ) ) {
		return;
	}
	check_admin_referer( 'islamicdilemma_content' );
	if ( 'settings' === $_POST['islamicdilemma_action'] ) {
		update_option( ISLAMICDILEMMA_REMOVE_OPTION, empty( $_POST['remove_other'] ) ? '0' : '1', false );
		update_option( ISLAMICDILEMMA_REMOVE_TAGS_OPTION, empty( $_POST['remove_tags'] ) ? '0' : '1', false );
		wp_safe_redirect( admin_url( 'themes.php?page=islamicdilemma-content&tab=settings&saved=1' ) );
		exit;
	}
	if ( 'reset_dates' === $_POST['islamicdilemma_action'] ) {
		$done = islamicdilemma_reset_image_batch_dates();
		wp_safe_redirect( admin_url( 'themes.php?page=islamicdilemma-content&' . ( null === $done ? 'dates=busy' : 'dates=1' ) ) );
		exit;
	}
	$restore = array();
	if ( 'restore' === $_POST['islamicdilemma_action'] && ! empty( $_POST['restore'] ) ) {
		$restore = array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['restore'] ) );
	}
	islamicdilemma_run_sync( array( 'restore' => $restore ) );
	wp_safe_redirect( admin_url( 'themes.php?page=islamicdilemma-content&synced=1' ) );
	exit;
}
add_action( 'admin_init', 'islamicdilemma_content_actions' );

/**
 * Render a sync report as a short list.
 *
 * @param array $report Report from islamicdilemma_sync_content().
 * @return string
 */
function islamicdilemma_report_html( $report ) {
	$parts = array(
		'created'      => __( 'Added', 'islamicdilemma' ),
		'updated'      => __( 'Updated to the theme version', 'islamicdilemma' ),
		'kept_edited'  => __( 'Kept because you edited them', 'islamicdilemma' ),
		'kept_deleted' => __( 'Not recreated because you deleted them', 'islamicdilemma' ),
		'trashed'      => __( 'Moved to the Trash', 'islamicdilemma' ),
		'removed_tags' => __( 'Tags removed because they are not part of the theme', 'islamicdilemma' ),
		'errors'       => __( 'Errors', 'islamicdilemma' ),
	);
	$html = '';
	foreach ( $parts as $key => $label ) {
		if ( empty( $report[ $key ] ) ) {
			continue;
		}
		$html .= '<p><strong>' . esc_html( $label ) . ' (' . count( $report[ $key ] ) . '):</strong> ' . esc_html( implode( ', ', $report[ $key ] ) ) . '</p>';
	}
	return $html ? $html : '<p>' . esc_html__( 'Everything already matched the theme.', 'islamicdilemma' ) . '</p>';
}

/**
 * The one-off reset of "Updated" dates: its result when it has run, or its button.
 *
 * @param array $state Content state.
 */
function islamicdilemma_render_dates_reset( $state ) {
	$done = isset( $state['dates_reset'] ) && is_array( $state['dates_reset'] ) ? $state['dates_reset'] : null;
	if ( isset( $_GET['dates'] ) && 'busy' === $_GET['dates'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'A sync is running or the content files are incomplete. Try the reset again in a few minutes.', 'islamicdilemma' ) . '</p></div>';
	}
	?>
	<h2><?php esc_html_e( 'Updated dates', 'islamicdilemma' ); ?></h2>
	<?php if ( $done ) : ?>
		<div class="notice notice-info inline">
			<p><strong><?php echo esc_html( sprintf( /* translators: %s: date and time */ __( 'Updated dates reset: %s', 'islamicdilemma' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) ( $done['time'] ?? 0 ) ) ) ); ?></strong></p>
			<?php if ( ! empty( $done['reset'] ) ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: 1: number, 2: titles */ __( 'Put back to the published date (%1$d): %2$s', 'islamicdilemma' ), count( $done['reset'] ), implode( ', ', (array) $done['reset'] ) ) ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'No item needed a reset.', 'islamicdilemma' ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $done['kept_edited'] ) ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: 1: number, 2: titles */ __( 'Left alone because you edited them (%1$d): %2$s', 'islamicdilemma' ), count( $done['kept_edited'] ), implode( ', ', (array) $done['kept_edited'] ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $done['kept_other'] ) ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: %d: number */ __( 'Left alone because their text changed in a later theme version (%d).', 'islamicdilemma' ), (int) $done['kept_other'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $done['errors'] ) ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: %s: titles */ __( 'A database error stopped these. Run the reset again: %s', 'islamicdilemma' ), implode( ', ', (array) $done['errors'] ) ) ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! $done || ! empty( $done['errors'] ) ) : ?>
		<form method="post">
			<?php wp_nonce_field( 'islamicdilemma_content' ); ?>
			<p class="description"><?php esc_html_e( 'Some articles and pages show an Updated date from an image batch although nobody changed them. This puts the published date back on every theme item that you have not edited and whose text matches the theme. Edited items keep their dates. It lists what it changed, and it is meant to run once.', 'islamicdilemma' ); ?></p>
			<p><button type="submit" class="button" name="islamicdilemma_action" value="reset_dates"><?php esc_html_e( 'Reset Updated dates set by image batches', 'islamicdilemma' ); ?></button></p>
		</form>
	<?php endif; ?>
	<?php
}

/**
 * The admin screen.
 */
function islamicdilemma_content_screen() {
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	$rows  = islamicdilemma_content_status();
	$tab   = ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], array( 'settings', 'errors' ), true ) ) ? sanitize_key( $_GET['tab'] ) : 'content'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap islamicdilemma-admin">
		<div class="idadm-header">
			<span class="idadm-header__icon dashicons dashicons-admin-page" aria-hidden="true"></span>
			<div class="idadm-header__text">
				<h1><?php esc_html_e( 'Theme content', 'islamicdilemma' ); ?></h1>
				<p><?php esc_html_e( 'The pages and articles the theme carries, what happens on activation and on update, and the error pages.', 'islamicdilemma' ); ?></p>
			</div>
			<span class="idadm-badge"><?php echo esc_html( sprintf( /* translators: %s: theme version */ __( 'Version %s', 'islamicdilemma' ), ISLAMICDILEMMA_VERSION ) ); ?></span>
		</div>
		<?php
		$islamicdilemma_tabs = array(
			'content'  => array( __( 'Content', 'islamicdilemma' ), 'dashicons-admin-page' ),
			'settings' => array( __( 'Settings', 'islamicdilemma' ), 'dashicons-admin-generic' ),
			'errors'   => array( __( 'Error pages', 'islamicdilemma' ), 'dashicons-warning' ),
		);
		?>
		<nav class="nav-tab-wrapper idadm-tabs" aria-label="<?php esc_attr_e( 'Theme content sections', 'islamicdilemma' ); ?>">
			<?php foreach ( $islamicdilemma_tabs as $islamicdilemma_slug => $islamicdilemma_tab ) : ?>
				<a href="<?php echo esc_url( admin_url( 'themes.php?page=islamicdilemma-content&tab=' . $islamicdilemma_slug ) ); ?>" class="nav-tab<?php echo $islamicdilemma_slug === $tab ? ' nav-tab-active' : ''; ?>"<?php echo $islamicdilemma_slug === $tab ? ' aria-current="page"' : ''; ?>><span class="dashicons <?php echo esc_attr( $islamicdilemma_tab[1] ); ?>" aria-hidden="true"></span><?php echo esc_html( $islamicdilemma_tab[0] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="idadm-panel">
		<?php if ( 'errors' === $tab ) : ?>
			<?php islamicdilemma_render_error_pages_tab(); ?>
		<?php endif; ?>
		<?php if ( 'content' === $tab ) : ?>
		<p><?php esc_html_e( 'The pages and Enrichment articles below ship with the theme. They are added on activation and kept in step with each new theme version. Anything you edit or delete yourself is left alone, unless you choose to restore the theme version here.', 'islamicdilemma' ); ?></p>
		<?php if ( ! empty( $state['report'] ) ) : ?>
			<div class="notice notice-info inline">
				<p><strong><?php echo esc_html( sprintf( /* translators: %s: date and time */ __( 'Last sync: %s', 'islamicdilemma' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $state['last_sync'] ) ) ); ?></strong></p>
				<?php echo wp_kses_post( islamicdilemma_report_html( $state['report'] ) ); ?>
				<?php if ( ! empty( $state['dates_repaired'] ) ) : ?>
					<p><?php echo esc_html( sprintf( /* translators: %s: list of titles */ __( 'Updated dates put back to the published date: %s', 'islamicdilemma' ), implode( ', ', (array) $state['dates_repaired'] ) ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php islamicdilemma_render_dates_reset( $state ); ?>
		<form method="post">
			<?php wp_nonce_field( 'islamicdilemma_content' ); ?>
			<div class="idadm-scroll" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Theme content items', 'islamicdilemma' ); ?>">
			<table class="widefat striped">
				<thead><tr>
					<td class="check-column"></td>
					<th><?php esc_html_e( 'Title', 'islamicdilemma' ); ?></th>
					<th><?php esc_html_e( 'Type', 'islamicdilemma' ); ?></th>
					<th><?php esc_html_e( 'Status', 'islamicdilemma' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td class="check-column">
							<?php if ( in_array( $row['status'], array( 'edited', 'deleted' ), true ) ) : ?>
								<input type="checkbox" name="restore[]" value="<?php echo esc_attr( $row['key'] ); ?>" id="restore-<?php echo esc_attr( sanitize_title( $row['key'] ) ); ?>">
							<?php endif; ?>
						</td>
						<td>
							<label for="restore-<?php echo esc_attr( sanitize_title( $row['key'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></label>
							<?php if ( $row['post_id'] ) : ?>
								— <a href="<?php echo esc_url( get_edit_post_link( $row['post_id'] ) ); ?>"><?php esc_html_e( 'Edit', 'islamicdilemma' ); ?></a>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( 'page' === $row['type'] ? __( 'Page', 'islamicdilemma' ) : __( 'Post', 'islamicdilemma' ) ); ?></td>
						<td><?php echo esc_html( islamicdilemma_status_label( $row['status'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<p class="submit">
				<button type="submit" class="button button-primary" name="islamicdilemma_action" value="sync"><?php esc_html_e( 'Sync now', 'islamicdilemma' ); ?></button>
				<button type="submit" class="button" name="islamicdilemma_action" value="restore"><?php esc_html_e( 'Restore the theme version of the selected items', 'islamicdilemma' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Restoring replaces your edits to the selected items with the theme’s version. Use it only for items you want to reset.', 'islamicdilemma' ); ?></p>
		</form>
		<?php elseif ( 'settings' === $tab ) : ?>
		<form method="post" class="idadm-form">
			<?php wp_nonce_field( 'islamicdilemma_content' ); ?>
			<section class="idadm-card" aria-labelledby="idadm-h-first">
				<h2 id="idadm-h-first"><?php esc_html_e( 'First activation', 'islamicdilemma' ); ?></h2>
				<div class="idadm-field">
					<label class="idadm-field__label" for="idadm-remove-other"><?php esc_html_e( 'On first activation, move posts and pages that are not theme content to the Trash', 'islamicdilemma' ); ?></label>
					<span class="idadm-switch"><input type="checkbox" role="switch" id="idadm-remove-other" name="remove_other" value="1" aria-describedby="idadm-d-other" <?php checked( islamicdilemma_removes_other_content() ); ?>><span class="idadm-switch__track" aria-hidden="true"></span></span>
					<p class="description idadm-field__help" id="idadm-d-other">
						<?php
						echo esc_html(
							empty( $state['initial'] )
								? __( 'The first import has not run yet.', 'islamicdilemma' )
								: __( 'The first import has already run on this site, so this setting applies only if the theme content is reset. Posts and pages you create are never removed by later syncs.', 'islamicdilemma' )
						);
						?>
					</p>
				</div>
			</section>
			<section class="idadm-card" aria-labelledby="idadm-h-tags">
				<h2 id="idadm-h-tags"><?php esc_html_e( 'Tags', 'islamicdilemma' ); ?></h2>
				<div class="idadm-field">
					<label class="idadm-field__label" for="idadm-remove-tags"><?php esc_html_e( 'On every sync, delete tags that are not part of the theme', 'islamicdilemma' ); ?></label>
					<span class="idadm-switch"><input type="checkbox" role="switch" id="idadm-remove-tags" name="remove_tags" value="1" aria-describedby="idadm-d-tags" <?php checked( islamicdilemma_removes_other_tags() ); ?>><span class="idadm-switch__track" aria-hidden="true"></span></span>
					<p class="description idadm-field__help" id="idadm-d-tags"><?php esc_html_e( 'Tags are glossary entries. Tags you create yourself are removed at the next sync unless you switch this off. Posts keep their other tags.', 'islamicdilemma' ); ?></p>
				</div>
			</section>
			<p><button type="submit" class="button button-primary" name="islamicdilemma_action" value="settings"><?php esc_html_e( 'Save settings', 'islamicdilemma' ); ?></button></p>
		</form>
		<?php endif; ?>
		</div>
		<div class="idadm-footer">
			<?php if ( current_user_can( 'switch_themes' ) || current_user_can( 'edit_theme_options' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'themes.php' ) ); ?>"><?php esc_html_e( 'Themes', 'islamicdilemma' ); ?></a>
			<?php endif; ?>
			<?php if ( current_user_can( 'view_site_health_checks' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'site-health.php' ) ); ?>"><?php esc_html_e( 'Site Health', 'islamicdilemma' ); ?></a>
			<?php endif; ?>
			<span><?php echo esc_html( 'Islamic Dilemma ' . ISLAMICDILEMMA_VERSION ); ?></span>
		</div>
	</div>
	<?php
}

/**
 * Load the admin screen's stylesheet.
 *
 * @param string $hook Current admin page hook.
 */
function islamicdilemma_content_admin_assets( $hook ) {
	if ( 'appearance_page_islamicdilemma-content' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'islamicdilemma-admin', get_theme_file_uri( 'assets/css/admin.css' ), array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_style( 'islamicdilemma-error-pages-admin', get_theme_file_uri( 'assets/css/error-pages-admin.css' ), array( 'islamicdilemma-admin' ), islamicdilemma_asset_version( 'assets/css/error-pages-admin.css' ) );
	wp_enqueue_script( 'islamicdilemma-error-pages-admin', get_theme_file_uri( 'assets/js/error-pages-admin.js' ), array(), islamicdilemma_asset_version( 'assets/js/error-pages-admin.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'islamicdilemma_content_admin_assets' );

/**
 * After a sync, tell administrators what happened, once.
 */
function islamicdilemma_sync_notice() {
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	if ( empty( $state['notice'] ) || empty( $state['report'] ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'appearance_page_islamicdilemma-content' === $screen->id ) {
		return;
	}
	$state['notice'] = false;
	update_option( ISLAMICDILEMMA_STATE, $state, false );
	printf(
		'<div class="notice %s is-dismissible"><p><strong>%s</strong></p>%s<p><a href="%s">%s</a></p></div>',
		empty( $state['report']['errors'] ) ? 'notice-success' : 'notice-error',
		empty( $state['report']['errors'] )
			? esc_html__( 'Islamic Dilemma theme: site content synced.', 'islamicdilemma' )
			: esc_html__( 'Islamic Dilemma theme: content sync needs attention.', 'islamicdilemma' ),
		wp_kses_post( islamicdilemma_report_html( $state['report'] ) ),
		esc_url( admin_url( 'themes.php?page=islamicdilemma-content' ) ),
		esc_html__( 'Review theme content', 'islamicdilemma' )
	);
}
add_action( 'admin_notices', 'islamicdilemma_sync_notice' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Manage the content that ships with the Islamic Dilemma theme.
	 */
	class Islamicdilemma_Content_Command {

		/**
		 * Show each theme item and whether it matches the theme, was edited or was deleted.
		 *
		 * @subcommand status
		 */
		public function status() {
			$rows = array_map(
				function ( $row ) {
					return array( 'key' => $row['key'], 'title' => $row['title'], 'status' => $row['status'], 'post_id' => $row['post_id'] );
				},
				islamicdilemma_content_status()
			);
			WP_CLI\Utils\format_items( 'table', $rows, array( 'key', 'title', 'status', 'post_id' ) );
		}

		/**
		 * Sync now. Edited and deleted items are left alone.
		 *
		 * @subcommand sync
		 */
		public function sync() {
			$report = islamicdilemma_run_sync();
			$this->print_report( $report );
		}

		/**
		 * Overwrite items with the theme version, even if edited or deleted.
		 *
		 * ## OPTIONS
		 *
		 * <key>...
		 * : Item keys, such as page:about or post:injil-where-is-it.
		 *
		 * @subcommand restore
		 *
		 * @param array $args Keys.
		 */
		public function restore( $args ) {
			$report = islamicdilemma_run_sync( array( 'restore' => $args ) );
			$this->print_report( $report );
		}

		/**
		 * Print a report.
		 *
		 * @param array|null $report Report.
		 */
		private function print_report( $report ) {
			if ( null === $report ) {
				WP_CLI::warning( 'Another sync is running. Try again in a few minutes.' );
				return;
			}
			foreach ( $report as $key => $titles ) {
				if ( $titles ) {
					WP_CLI::log( str_pad( $key, 14 ) . count( $titles ) . ': ' . implode( ', ', $titles ) );
				}
			}
			if ( ! empty( $report['errors'] ) ) {
				WP_CLI::error( 'Sync failed. Fix the reported errors and run it again.' );
			}
			WP_CLI::success( 'Sync complete.' );
		}
	}
	WP_CLI::add_command( 'islamicdilemma content', 'Islamicdilemma_Content_Command' );
}
