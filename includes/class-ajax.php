<?php
/**
 * AJAX handlers.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * AJAX class.
 */
class AJAX {
	/**
	 * Instance of this class.
	 *
	 * @var AJAX|null
	 */
	private static ?AJAX $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AJAX
	 */
	public static function get_instance(): AJAX {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_ajax_pbe_upload_photo', array( $this, 'handle_upload_photo' ) );
		add_action( 'wp_ajax_nopriv_pbe_upload_photo', array( $this, 'handle_upload_photo' ) );
	}

	/**
	 * Handle photo upload via AJAX.
	 */
	public function handle_upload_photo(): void {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pbe_upload_photo' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'virtual-photo-booth' ) ) );
		}

		// Get event ID.
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		if ( ! $event_id ) {
			wp_send_json_error( array( 'message' => __( 'Event ID is required.', 'virtual-photo-booth' ) ) );
		}

		// Verify event exists.
		$event = get_post( $event_id );
		if ( ! $event || 'photo_booth_event' !== $event->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Invalid event.', 'virtual-photo-booth' ) ) );
		}

		// Check file upload.
		if ( ! isset( $_FILES['photo'] ) || ! is_uploaded_file( $_FILES['photo']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'virtual-photo-booth' ) ) );
		}

		$file = $_FILES['photo'];

		// Get settings.
		$settings = Settings::get_instance()->get_settings();

		// Validate file size.
		$max_size = ( $settings['pbe_max_image_size_mb'] ?? 5 ) * 1024 * 1024; // Convert MB to bytes.
		if ( $file['size'] > $max_size ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: maximum file size in MB */
						__( 'File size exceeds maximum allowed size of %d MB.', 'virtual-photo-booth' ),
						$settings['pbe_max_image_size_mb'] ?? 5
					),
				)
			);
		}

		// Validate MIME type.
		$allowed_types = $settings['pbe_allowed_mime_types'] ?? array( 'image/jpeg', 'image/png', 'image/webp' );
		$file_type     = wp_check_filetype( $file['name'] );
		$mime_type     = $file['type'];

		if ( ! in_array( $mime_type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'File type not allowed.', 'virtual-photo-booth' ) ) );
		}

		// Use WordPress upload handler.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'webp'         => 'image/webp',
				),
			)
		);

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload['error'] ) );
		}

		// Create attachment.
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $upload['type'],
				'post_title'     => sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		// Generate attachment metadata.
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		// Add custom meta.
		update_post_meta( $attachment_id, 'pbe_event_id', $event_id );
		update_post_meta( $attachment_id, 'pbe_source', 'photo_booth' );
		update_post_meta( $attachment_id, 'pbe_created_at', current_time( 'mysql' ) );

		// Determine status.
		$auto_approve = get_post_meta( $event_id, 'pbe_auto_approve', true );
		// Handle both boolean and string values ('1'/'0').
		$auto_approve = ( '1' === $auto_approve || true === $auto_approve || 'true' === $auto_approve );
		$status       = $auto_approve ? 'approved' : 'pending';
		update_post_meta( $attachment_id, 'pbe_status', $status );

		// Prepare response.
		$response = array(
			'success'   => true,
			'status'    => $status,
			'photo_id'  => $attachment_id,
		);

		if ( 'approved' === $status ) {
			$response['image_url'] = wp_get_attachment_image_url( $attachment_id, 'full' );
		}

		wp_send_json_success( $response );
	}
}

