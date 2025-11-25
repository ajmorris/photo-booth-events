<?php
/**
 * Custom Post Type registration.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * Custom Post Type class.
 */
class CPT {
	/**
	 * Instance of this class.
	 *
	 * @var CPT|null
	 */
	private static ?CPT $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return CPT
	 */
	public static function get_instance(): CPT {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register the photo_booth_event post type.
	 */
	public function register_post_type(): void {
		// Check if already registered.
		if ( post_type_exists( 'photo_booth_event' ) ) {
			return;
		}

		$labels = array(
			'name'                  => _x( 'Photo Booth Events', 'Post Type General Name', 'virtual-photo-booth' ),
			'singular_name'         => _x( 'Photo Booth Event', 'Post Type Singular Name', 'virtual-photo-booth' ),
			'menu_name'             => __( 'Photo Booth Events', 'virtual-photo-booth' ),
			'name_admin_bar'        => __( 'Photo Booth Event', 'virtual-photo-booth' ),
			'archives'              => __( 'Event Archives', 'virtual-photo-booth' ),
			'attributes'            => __( 'Event Attributes', 'virtual-photo-booth' ),
			'parent_item_colon'     => __( 'Parent Event:', 'virtual-photo-booth' ),
			'all_items'             => __( 'All Events', 'virtual-photo-booth' ),
			'add_new_item'          => __( 'Add New Event', 'virtual-photo-booth' ),
			'add_new'               => __( 'Add New', 'virtual-photo-booth' ),
			'new_item'              => __( 'New Event', 'virtual-photo-booth' ),
			'edit_item'             => __( 'Edit Event', 'virtual-photo-booth' ),
			'update_item'           => __( 'Update Event', 'virtual-photo-booth' ),
			'view_item'             => __( 'View Event', 'virtual-photo-booth' ),
			'view_items'            => __( 'View Events', 'virtual-photo-booth' ),
			'search_items'          => __( 'Search Event', 'virtual-photo-booth' ),
			'not_found'             => __( 'Not found', 'virtual-photo-booth' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'virtual-photo-booth' ),
			'featured_image'        => __( 'Featured Image', 'virtual-photo-booth' ),
			'set_featured_image'    => __( 'Set featured image', 'virtual-photo-booth' ),
			'remove_featured_image' => __( 'Remove featured image', 'virtual-photo-booth' ),
			'use_featured_image'    => __( 'Use as featured image', 'virtual-photo-booth' ),
		);

		$args = array(
			'label'                 => __( 'Photo Booth Event', 'virtual-photo-booth' ),
			'description'           => __( 'Photo booth events that group captured photos', 'virtual-photo-booth' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'thumbnail' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-camera',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'show_in_rest'          => true,
		);

		register_post_type( 'photo_booth_event', $args );
	}

	/**
	 * Add meta boxes for event settings.
	 *
	 * @param string $post_type Post type.
	 */
	public function add_meta_boxes( string $post_type ): void {
		if ( 'photo_booth_event' !== $post_type ) {
			return;
		}

		add_meta_box(
			'pbe_event_settings',
			__( 'Event Settings', 'virtual-photo-booth' ),
			array( $this, 'render_event_settings_meta_box' ),
			'photo_booth_event',
			'normal',
			'high'
		);
	}

	/**
	 * Render event settings meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_event_settings_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'pbe_save_event_settings', 'pbe_event_settings_nonce' );

		$frame_id         = get_post_meta( $post->ID, 'pbe_frame_id', true );
		$auto_approve     = get_post_meta( $post->ID, 'pbe_auto_approve', true );
		$gallery_enabled  = get_post_meta( $post->ID, 'pbe_gallery_enabled', true );
		$start_datetime   = get_post_meta( $post->ID, 'pbe_start_datetime', true );
		$end_datetime     = get_post_meta( $post->ID, 'pbe_end_datetime', true );

		$auto_approve     = $auto_approve ? '1' : '0';
		$gallery_enabled  = $gallery_enabled ? '1' : '0';

		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pbe_frame_id"><?php esc_html_e( 'Frame Image', 'virtual-photo-booth' ); ?></label>
				</th>
				<td>
					<div class="pbe-media-picker">
						<input type="hidden" id="pbe_frame_id" name="pbe_frame_id" value="<?php echo esc_attr( $frame_id ); ?>" />
						<div class="pbe-frame-preview">
							<?php if ( $frame_id ) : ?>
								<?php echo wp_get_attachment_image( (int) $frame_id, 'medium', false, array( 'style' => 'max-width: 200px; height: auto;' ) ); ?>
							<?php endif; ?>
						</div>
						<button type="button" class="button pbe-select-frame" data-frame-id="<?php echo esc_attr( $frame_id ); ?>">
							<?php esc_html_e( 'Select Frame', 'virtual-photo-booth' ); ?>
						</button>
						<button type="button" class="button pbe-remove-frame" style="<?php echo $frame_id ? '' : 'display:none;'; ?>">
							<?php esc_html_e( 'Remove Frame', 'virtual-photo-booth' ); ?>
						</button>
					</div>
					<p class="description"><?php esc_html_e( 'Optional frame image to overlay on captured photos.', 'virtual-photo-booth' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pbe_auto_approve"><?php esc_html_e( 'Auto-approve Photos', 'virtual-photo-booth' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" id="pbe_auto_approve" name="pbe_auto_approve" value="1" <?php checked( $auto_approve, '1' ); ?> />
						<?php esc_html_e( 'Automatically approve uploaded photos for this event', 'virtual-photo-booth' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pbe_gallery_enabled"><?php esc_html_e( 'Enable Gallery', 'virtual-photo-booth' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" id="pbe_gallery_enabled" name="pbe_gallery_enabled" value="1" <?php checked( $gallery_enabled, '1' ); ?> />
						<?php esc_html_e( 'Show photos from this event in gallery blocks', 'virtual-photo-booth' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pbe_start_datetime"><?php esc_html_e( 'Start Date/Time', 'virtual-photo-booth' ); ?></label>
				</th>
				<td>
					<input type="datetime-local" id="pbe_start_datetime" name="pbe_start_datetime" value="<?php echo esc_attr( $start_datetime ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional start date and time for this event.', 'virtual-photo-booth' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pbe_end_datetime"><?php esc_html_e( 'End Date/Time', 'virtual-photo-booth' ); ?></label>
				</th>
				<td>
					<input type="datetime-local" id="pbe_end_datetime" name="pbe_end_datetime" value="<?php echo esc_attr( $end_datetime ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional end date and time for this event.', 'virtual-photo-booth' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta_boxes( int $post_id, \WP_Post $post ): void {
		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check post type.
		if ( 'photo_booth_event' !== $post->post_type ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['pbe_event_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pbe_event_settings_nonce'] ) ), 'pbe_save_event_settings' ) ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save frame ID.
		if ( isset( $_POST['pbe_frame_id'] ) ) {
			$frame_id = absint( $_POST['pbe_frame_id'] );
			update_post_meta( $post_id, 'pbe_frame_id', $frame_id );
		}

		// Save auto-approve.
		$auto_approve = isset( $_POST['pbe_auto_approve'] ) && '1' === $_POST['pbe_auto_approve'] ? '1' : '0';
		update_post_meta( $post_id, 'pbe_auto_approve', $auto_approve );

		// Save gallery enabled.
		$gallery_enabled = isset( $_POST['pbe_gallery_enabled'] ) && '1' === $_POST['pbe_gallery_enabled'] ? '1' : '0';
		update_post_meta( $post_id, 'pbe_gallery_enabled', $gallery_enabled );

		// Save start datetime.
		if ( isset( $_POST['pbe_start_datetime'] ) ) {
			$start_datetime = sanitize_text_field( wp_unslash( $_POST['pbe_start_datetime'] ) );
			update_post_meta( $post_id, 'pbe_start_datetime', $start_datetime );
		}

		// Save end datetime.
		if ( isset( $_POST['pbe_end_datetime'] ) ) {
			$end_datetime = sanitize_text_field( wp_unslash( $_POST['pbe_end_datetime'] ) );
			update_post_meta( $post_id, 'pbe_end_datetime', $end_datetime );
		}
	}
}

