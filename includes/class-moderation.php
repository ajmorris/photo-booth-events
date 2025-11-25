<?php
/**
 * Photo Booth event moderation submenu placeholder.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * Registers the Moderation submenu and renders a placeholder page.
 */
class Moderation {
	private const PAGE_SLUG = 'pbe-moderation';
	/**
	 * Instance holder.
	 *
	 * @var Moderation|null
	 */
	private static ?Moderation $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Moderation
	 */
	public static function get_instance(): Moderation {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook into WordPress.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_bulk_actions' ) );
		add_action( 'admin_init', array( $this, 'handle_single_action' ) );
		add_filter( 'parent_file', array( $this, 'set_parent_file' ) );
	}

	/**
	 * Register the "Moderation" submenu under the Photo Booth Events CPT.
	 */
	public function register_submenu(): void {
		if ( ! post_type_exists( 'photo_booth_event' ) ) {
			return;
		}

		add_submenu_page(
			'edit.php?post_type=photo_booth_event',
			__( 'Moderation', 'virtual-photo-booth' ),
			__( 'Moderation', 'virtual-photo-booth' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Highlight the correct parent menu when loading the moderation page.
	 *
	 * @param string $parent_file Current parent file.
	 * @return string
	 */
	public function set_parent_file( string $parent_file ): string {
		if ( isset( $_GET['page'] ) && self::PAGE_SLUG === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$parent_file = 'edit.php?post_type=photo_booth_event';
		}

		return $parent_file;
	}

	/**
	 * Handle bulk moderation actions.
	 */
	public function handle_bulk_actions(): void {
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		if ( empty( $_POST['pbe_bulk_action'] ) || empty( $_POST['pbe_photo_ids'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'virtual-photo-booth' ) );
		}

		check_admin_referer( 'pbe_bulk_action', 'pbe_bulk_nonce' );

		$action    = sanitize_text_field( wp_unslash( $_POST['pbe_bulk_action'] ) );
		$photo_ids = array_map( 'absint', (array) $_POST['pbe_photo_ids'] );
		$updated   = 0;

		foreach ( $photo_ids as $photo_id ) {
			if ( ! $photo_id ) {
				continue;
			}

			switch ( $action ) {
				case 'approve':
					update_post_meta( $photo_id, 'pbe_status', 'approved' );
					$updated++;
					break;
				case 'unapprove':
					update_post_meta( $photo_id, 'pbe_status', 'pending' );
					$updated++;
					break;
				case 'delete':
					if ( wp_delete_attachment( $photo_id, true ) ) {
						$updated++;
					}
					break;
			}
		}

		$args = array(
			'page'         => self::PAGE_SLUG,
			'pbe_notice'   => $action,
			'pbe_updated'  => $updated,
		);

		if ( isset( $_POST['event_filter'] ) ) {
			$args['event_filter'] = absint( $_POST['event_filter'] );
		}

		if ( isset( $_POST['status_filter'] ) ) {
			$args['status_filter'] = sanitize_text_field( wp_unslash( $_POST['status_filter'] ) );
		}

		wp_safe_redirect(
			add_query_arg(
				$args,
				admin_url( 'edit.php?post_type=photo_booth_event' )
			)
		);
		exit;
	}

	/**
	 * Handle single moderation action links.
	 */
	public function handle_single_action(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== $page || ! isset( $_GET['pbe_action'], $_GET['photo_id'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'virtual-photo-booth' ) );
		}

		check_admin_referer( 'pbe_single_action' );

		$action   = sanitize_text_field( wp_unslash( $_GET['pbe_action'] ) );
		$photo_id = absint( $_GET['photo_id'] );

		switch ( $action ) {
			case 'approve':
				update_post_meta( $photo_id, 'pbe_status', 'approved' );
				break;
			case 'unapprove':
				update_post_meta( $photo_id, 'pbe_status', 'pending' );
				break;
			case 'delete':
				wp_delete_attachment( $photo_id, true );
				break;
		}

		$redirect_args = array(
			'page'        => self::PAGE_SLUG,
			'pbe_notice'  => $action,
			'pbe_updated' => 1,
		);

		if ( isset( $_GET['event_filter'] ) ) {
			$redirect_args['event_filter'] = absint( $_GET['event_filter'] );
		}

		if ( isset( $_GET['status_filter'] ) ) {
			$redirect_args['status_filter'] = sanitize_text_field( wp_unslash( $_GET['status_filter'] ) );
		}

		wp_safe_redirect(
			add_query_arg(
				$redirect_args,
				admin_url( 'edit.php?post_type=photo_booth_event' )
			)
		);
		exit;
	}

	/**
	 * Render a placeholder moderation page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'virtual-photo-booth' ) );
		}

		$event_filter  = isset( $_GET['event_filter'] ) ? absint( $_GET['event_filter'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status_filter = isset( $_GET['status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['status_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged         = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 20,
			'paged'          => $paged,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'pbe_source',
					'value'   => 'photo_booth',
					'compare' => '=',
				),
			),
		);

		if ( $event_filter > 0 ) {
			$query_args['meta_query'][] = array(
				'key'     => 'pbe_event_id',
				'value'   => $event_filter,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		if ( in_array( $status_filter, array( 'pending', 'approved' ), true ) ) {
			$query_args['meta_query'][] = array(
				'key'     => 'pbe_status',
				'value'   => $status_filter,
				'compare' => '=',
			);
		}

		$photos_query = new \WP_Query( $query_args );
		$events       = get_posts(
			array(
				'post_type'      => 'photo_booth_event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$notice_action = isset( $_GET['pbe_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['pbe_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice_count  = isset( $_GET['pbe_updated'] ) ? absint( $_GET['pbe_updated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Photo Moderation', 'virtual-photo-booth' ); ?></h1>

			<?php if ( $notice_action && $notice_count ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						switch ( $notice_action ) {
							case 'approve':
								printf(
									/* translators: %d number of photos */
									esc_html( _n( '%d photo approved.', '%d photos approved.', $notice_count, 'virtual-photo-booth' ) ),
									$notice_count
								);
								break;
							case 'unapprove':
								printf(
									esc_html( _n( '%d photo moved to pending.', '%d photos moved to pending.', $notice_count, 'virtual-photo-booth' ) ),
									$notice_count
								);
								break;
							case 'delete':
								printf(
									esc_html( _n( '%d photo deleted.', '%d photos deleted.', $notice_count, 'virtual-photo-booth' ) ),
									$notice_count
								);
								break;
						}
						?>
					</p>
				</div>
			<?php endif; ?>

			<form method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
				<input type="hidden" name="post_type" value="photo_booth_event">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">

				<label for="event_filter"><?php esc_html_e( 'Event:', 'virtual-photo-booth' ); ?></label>
				<select id="event_filter" name="event_filter">
					<option value="0"><?php esc_html_e( 'All events', 'virtual-photo-booth' ); ?></option>
					<?php foreach ( $events as $event ) : ?>
						<option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event_filter, $event->ID ); ?>>
							<?php echo esc_html( $event->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="status_filter" style="margin-left: 15px;"><?php esc_html_e( 'Status:', 'virtual-photo-booth' ); ?></label>
				<select id="status_filter" name="status_filter">
					<option value=""><?php esc_html_e( 'All statuses', 'virtual-photo-booth' ); ?></option>
					<option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending', 'virtual-photo-booth' ); ?></option>
					<option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'Approved', 'virtual-photo-booth' ); ?></option>
				</select>

				<?php submit_button( __( 'Filter', 'virtual-photo-booth' ), 'secondary', '', false ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
				<?php wp_nonce_field( 'pbe_bulk_action', 'pbe_bulk_nonce' ); ?>
				<input type="hidden" name="post_type" value="photo_booth_event">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<input type="hidden" name="event_filter" value="<?php echo esc_attr( $event_filter ); ?>">
				<input type="hidden" name="status_filter" value="<?php echo esc_attr( $status_filter ); ?>">

				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="pbe_bulk_action">
							<option value=""><?php esc_html_e( 'Bulk actions', 'virtual-photo-booth' ); ?></option>
							<option value="approve"><?php esc_html_e( 'Approve', 'virtual-photo-booth' ); ?></option>
							<option value="unapprove"><?php esc_html_e( 'Move to pending', 'virtual-photo-booth' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'virtual-photo-booth' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'virtual-photo-booth' ), 'action', '', false ); ?>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="cb-select-all">
							</td>
							<th scope="col"><?php esc_html_e( 'Photo', 'virtual-photo-booth' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Event', 'virtual-photo-booth' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'virtual-photo-booth' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Uploaded', 'virtual-photo-booth' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'virtual-photo-booth' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $photos_query->have_posts() ) : ?>
							<?php while ( $photos_query->have_posts() ) : ?>
								<?php
								$photos_query->the_post();
								$photo_id  = get_the_ID();
								$event_id  = (int) get_post_meta( $photo_id, 'pbe_event_id', true );
								$event     = $event_id ? get_post( $event_id ) : null;
								$status    = get_post_meta( $photo_id, 'pbe_status', true );
								$status    = $status ? $status : 'pending';
								$actions   = $this->get_single_actions( $photo_id, $status, $event_filter, $status_filter );
								?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="pbe_photo_ids[]" value="<?php echo esc_attr( $photo_id ); ?>">
									</th>
									<td><?php echo wp_get_attachment_image( $photo_id, 'thumbnail' ); ?></td>
									<td><?php echo $event ? esc_html( $event->post_title ) : esc_html__( 'Unassigned', 'virtual-photo-booth' ); ?></td>
									<td>
										<span class="pbe-status pbe-status-<?php echo esc_attr( $status ); ?>">
											<?php echo 'approved' === $status ? esc_html__( 'Approved', 'virtual-photo-booth' ) : esc_html__( 'Pending', 'virtual-photo-booth' ); ?>
										</span>
									</td>
									<td><?php echo esc_html( get_the_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
									<td class="column-actions"><?php echo wp_kses_post( implode( ' | ', $actions ) ); ?></td>
								</tr>
							<?php endwhile; ?>
							<?php wp_reset_postdata(); ?>
						<?php else : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No photos found.', 'virtual-photo-booth' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>

			<?php
			$total_pages = (int) $photos_query->max_num_pages;
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav bottom">';
				echo paginate_links(
					array(
						'base'      => add_query_arg(
							array(
								'paged'         => '%#%',
								'event_filter'  => $event_filter,
								'status_filter' => $status_filter,
								'post_type'     => 'photo_booth_event',
								'page'          => self::PAGE_SLUG,
							)
						),
						'format'    => '',
						'current'   => $paged,
						'total'     => $total_pages,
						'prev_text' => __( '&laquo;', 'virtual-photo-booth' ),
						'next_text' => __( '&raquo;', 'virtual-photo-booth' ),
					)
				);
				echo '</div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Build action links for a given photo.
	 *
	 * @param int    $photo_id Photo ID.
	 * @param int    $event_filter Current event filter.
	 * @param string $status_filter Current status filter.
	 * @return array
	 */
	private function get_single_actions( int $photo_id, string $current_status, int $event_filter, string $status_filter ): array {
		$query_args = array(
			'post_type'     => 'photo_booth_event',
			'page'          => self::PAGE_SLUG,
			'photo_id'      => $photo_id,
			'event_filter'  => $event_filter,
			'status_filter' => $status_filter,
		);

		$actions = array();

		if ( 'approved' !== $current_status ) {
			$actions[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array_merge(
								$query_args,
								array(
									'pbe_action' => 'approve',
								)
							),
							admin_url( 'edit.php' )
						),
						'pbe_single_action'
					)
				),
				esc_html__( 'Approve', 'virtual-photo-booth' )
			);
		} else {
			$actions[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array_merge(
								$query_args,
								array(
									'pbe_action' => 'unapprove',
								)
							),
							admin_url( 'edit.php' )
						),
						'pbe_single_action'
					)
				),
				esc_html__( 'Move to pending', 'virtual-photo-booth' )
			);
		}

		$actions[] = sprintf(
			'<a href="%1$s" onclick="return confirm(\'%3$s\');">%2$s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						array_merge(
							$query_args,
							array(
								'pbe_action' => 'delete',
							)
						),
						admin_url( 'edit.php' )
					),
					'pbe_single_action'
				)
			),
			esc_html__( 'Delete', 'virtual-photo-booth' ),
			esc_js( __( 'Are you sure you want to delete this photo?', 'virtual-photo-booth' ) )
		);

		return $actions;
	}
}

