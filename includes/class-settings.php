<?php
/**
 * Settings API implementation.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * Settings class.
 */
class Settings {
	/**
	 * Instance of this class.
	 *
	 * @var Settings|null
	 */
	private static ?Settings $instance = null;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'pbe_settings';

	/**
	 * Get singleton instance.
	 *
	 * @return Settings
	 */
	public static function get_instance(): Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to menu.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Virtual Photo Booth Settings', 'virtual-photo-booth' ),
			__( 'Virtual Photo Booth', 'virtual-photo-booth' ),
			'manage_options',
			'virtual-photo-booth',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		register_setting(
			'pbe_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	private function get_default_settings(): array {
		return array(
			'pbe_default_event_id'      => 0,
			'pbe_default_frame_id'       => 0,
			'pbe_default_auto_approve'   => false,
			'pbe_max_image_size_mb'      => 5,
			'pbe_allowed_mime_types'     => array( 'image/jpeg', 'image/png', 'image/webp' ),
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		if ( isset( $input['pbe_default_event_id'] ) ) {
			$sanitized['pbe_default_event_id'] = absint( $input['pbe_default_event_id'] );
		}

		if ( isset( $input['pbe_default_frame_id'] ) ) {
			$sanitized['pbe_default_frame_id'] = absint( $input['pbe_default_frame_id'] );
		}

		if ( isset( $input['pbe_default_auto_approve'] ) ) {
			$sanitized['pbe_default_auto_approve'] = '1' === $input['pbe_default_auto_approve'];
		}

		if ( isset( $input['pbe_max_image_size_mb'] ) ) {
			$sanitized['pbe_max_image_size_mb'] = absint( $input['pbe_max_image_size_mb'] );
		}

		if ( isset( $input['pbe_allowed_mime_types'] ) && is_array( $input['pbe_allowed_mime_types'] ) ) {
			$sanitized['pbe_allowed_mime_types'] = array_map( 'sanitize_text_field', $input['pbe_allowed_mime_types'] );
		}

		return $sanitized;
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		$default_frame_id = $settings['pbe_default_frame_id'] ?? 0;
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'pbe_settings_group' );
				do_settings_sections( 'pbe_settings_group' );
				?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="pbe_default_event_id"><?php esc_html_e( 'Default Event', 'virtual-photo-booth' ); ?></label>
						</th>
						<td>
							<?php
							$events = get_posts(
								array(
									'post_type'      => 'photo_booth_event',
									'posts_per_page' => -1,
									'post_status'    => 'publish',
								)
							);
							?>
							<select id="pbe_default_event_id" name="pbe_settings[pbe_default_event_id]">
								<option value="0"><?php esc_html_e( '— None —', 'virtual-photo-booth' ); ?></option>
								<?php foreach ( $events as $event ) : ?>
									<option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $settings['pbe_default_event_id'] ?? 0, $event->ID ); ?>>
										<?php echo esc_html( $event->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Default event to use when no event is specified in blocks.', 'virtual-photo-booth' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pbe_default_frame_id"><?php esc_html_e( 'Default Frame', 'virtual-photo-booth' ); ?></label>
						</th>
						<td>
							<div class="pbe-media-picker">
								<input type="hidden" id="pbe_default_frame_id" name="pbe_settings[pbe_default_frame_id]" value="<?php echo esc_attr( $default_frame_id ); ?>" />
								<div class="pbe-frame-preview">
									<?php if ( $default_frame_id ) : ?>
										<?php echo wp_get_attachment_image( (int) $default_frame_id, 'medium', false, array( 'style' => 'max-width: 200px; height: auto;' ) ); ?>
									<?php endif; ?>
								</div>
								<button type="button" class="button pbe-select-frame" data-frame-id="<?php echo esc_attr( $default_frame_id ); ?>">
									<?php esc_html_e( 'Select Frame', 'virtual-photo-booth' ); ?>
								</button>
								<button type="button" class="button pbe-remove-frame" style="<?php echo $default_frame_id ? '' : 'display:none;'; ?>">
									<?php esc_html_e( 'Remove Frame', 'virtual-photo-booth' ); ?>
								</button>
							</div>
							<p class="description"><?php esc_html_e( 'Default frame image to use when no frame is specified.', 'virtual-photo-booth' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pbe_default_auto_approve"><?php esc_html_e( 'Default Auto-approve', 'virtual-photo-booth' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="pbe_default_auto_approve" name="pbe_settings[pbe_default_auto_approve]" value="1" <?php checked( $settings['pbe_default_auto_approve'] ?? false, true ); ?> />
								<?php esc_html_e( 'Automatically approve uploaded photos by default', 'virtual-photo-booth' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pbe_max_image_size_mb"><?php esc_html_e( 'Max Image Size (MB)', 'virtual-photo-booth' ); ?></label>
						</th>
						<td>
							<input type="number" id="pbe_max_image_size_mb" name="pbe_settings[pbe_max_image_size_mb]" value="<?php echo esc_attr( $settings['pbe_max_image_size_mb'] ?? 5 ); ?>" min="1" max="50" step="1" />
							<p class="description"><?php esc_html_e( 'Maximum file size for uploaded images in megabytes.', 'virtual-photo-booth' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Allowed MIME Types', 'virtual-photo-booth' ); ?></label>
						</th>
						<td>
							<?php
							$allowed_types = $settings['pbe_allowed_mime_types'] ?? array( 'image/jpeg', 'image/png', 'image/webp' );
							$common_types  = array(
								'image/jpeg' => 'JPEG',
								'image/png'  => 'PNG',
								'image/webp' => 'WebP',
								'image/gif'  => 'GIF',
							);
							foreach ( $common_types as $mime => $label ) :
								?>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="pbe_settings[pbe_allowed_mime_types][]" value="<?php echo esc_attr( $mime ); ?>" <?php checked( in_array( $mime, $allowed_types, true ) ); ?> />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Select which image types are allowed for uploads.', 'virtual-photo-booth' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$defaults = $this->get_default_settings();
		$settings = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_setting( string $key, $default = null ) {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}
}


