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
	if ( ! in_array( $_POST['islamicdilemma_action'], array( 'settings', 'sync', 'restore' ), true ) ) {
		return;
	}
	check_admin_referer( 'islamicdilemma_content' );
	if ( 'settings' === $_POST['islamicdilemma_action'] ) {
		update_option( ISLAMICDILEMMA_REMOVE_OPTION, empty( $_POST['remove_other'] ) ? '0' : '1', false );
		wp_safe_redirect( admin_url( 'themes.php?page=islamicdilemma-content&tab=settings&saved=1' ) );
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
 * The admin screen.
 */
function islamicdilemma_content_screen() {
	$state = get_option( ISLAMICDILEMMA_STATE, array() );
	$rows  = islamicdilemma_content_status();
	$tab   = ( isset( $_GET['tab'] ) && 'settings' === $_GET['tab'] ) ? 'settings' : 'content'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap islamicdilemma-admin">
		<h1><?php esc_html_e( 'Theme content', 'islamicdilemma' ); ?></h1>
		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Theme content sections', 'islamicdilemma' ); ?>">
			<a href="<?php echo esc_url( admin_url( 'themes.php?page=islamicdilemma-content&tab=content' ) ); ?>" class="nav-tab<?php echo 'content' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Content', 'islamicdilemma' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'themes.php?page=islamicdilemma-content&tab=settings' ) ); ?>" class="nav-tab<?php echo 'settings' === $tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'islamicdilemma' ); ?></a>
		</nav>
		<?php if ( 'content' === $tab ) : ?>
		<p><?php esc_html_e( 'The pages and Enrichment articles below ship with the theme. They are added on activation and kept in step with each new theme version. Anything you edit or delete yourself is left alone, unless you choose to restore the theme version here.', 'islamicdilemma' ); ?></p>
		<?php if ( ! empty( $state['report'] ) ) : ?>
			<div class="notice notice-info inline">
				<p><strong><?php echo esc_html( sprintf( /* translators: %s: date and time */ __( 'Last sync: %s', 'islamicdilemma' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $state['last_sync'] ) ) ); ?></strong></p>
				<?php echo wp_kses_post( islamicdilemma_report_html( $state['report'] ) ); ?>
			</div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'islamicdilemma_content' ); ?>
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
						<th class="check-column">
							<?php if ( in_array( $row['status'], array( 'edited', 'deleted' ), true ) ) : ?>
								<input type="checkbox" name="restore[]" value="<?php echo esc_attr( $row['key'] ); ?>" id="restore-<?php echo esc_attr( sanitize_title( $row['key'] ) ); ?>">
							<?php endif; ?>
						</th>
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
			<p class="submit">
				<button type="submit" class="button button-primary" name="islamicdilemma_action" value="sync"><?php esc_html_e( 'Sync now', 'islamicdilemma' ); ?></button>
				<button type="submit" class="button" name="islamicdilemma_action" value="restore"><?php esc_html_e( 'Restore the theme version of the selected items', 'islamicdilemma' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Restoring replaces your edits to the selected items with the theme’s version. Use it only for items you want to reset.', 'islamicdilemma' ); ?></p>
		</form>
		<?php else : ?>
		<h2><?php esc_html_e( 'First activation', 'islamicdilemma' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'islamicdilemma_content' ); ?>
			<p>
				<label>
					<input type="checkbox" name="remove_other" value="1" <?php checked( islamicdilemma_removes_other_content() ); ?>>
					<?php esc_html_e( 'On first activation, move posts and pages that are not theme content to the Trash', 'islamicdilemma' ); ?>
				</label>
			</p>
			<p class="description">
				<?php
				echo esc_html(
					empty( $state['initial'] )
						? __( 'The first import has not run yet.', 'islamicdilemma' )
						: __( 'The first import has already run on this site, so this setting applies only if the theme content is reset. Posts and pages you create are never removed by later syncs.', 'islamicdilemma' )
				);
				?>
			</p>
			<p><button type="submit" class="button" name="islamicdilemma_action" value="settings"><?php esc_html_e( 'Save setting', 'islamicdilemma' ); ?></button></p>
		</form>
		<?php endif; ?>
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
