<?php
/**
 * Block registration and rendering.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * Blocks class.
 */
class Blocks {
	/**
	 * Instance of this class.
	 *
	 * @var Blocks|null
	 */
	private static ?Blocks $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Blocks
	 */
	public static function get_instance(): Blocks {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks(): void {
		// Determine block directory (use build if available, otherwise source).
		$photo_booth_path = PBE_PLUGIN_DIR . 'build/blocks/photo-booth';
		$gallery_path     = PBE_PLUGIN_DIR . 'build/blocks/gallery';

		// Fallback to source if build doesn't exist.
		if ( ! file_exists( $photo_booth_path . '/block.json' ) ) {
			$photo_booth_path = PBE_PLUGIN_DIR . 'src/blocks/photo-booth';
		}
		if ( ! file_exists( $gallery_path . '/block.json' ) ) {
			$gallery_path = PBE_PLUGIN_DIR . 'src/blocks/gallery';
		}

		// Register Photo Booth block.
		register_block_type(
			$photo_booth_path,
			array(
				'render_callback' => array( $this, 'render_photo_booth_block' ),
			)
		);

		// Register Gallery block.
		register_block_type(
			$gallery_path,
			array(
				'render_callback' => array( $this, 'render_gallery_block' ),
			)
		);
	}

	/**
	 * Enqueue block assets.
	 */
	public function enqueue_block_assets(): void {
		// Only enqueue on frontend.
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'pbe-photo-booth',
			PBE_PLUGIN_URL . 'assets/js/photo-booth.js',
			array(),
			PBE_VERSION,
			true
		);

		wp_localize_script(
			'pbe-photo-booth',
			'pbeData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pbe_upload_photo' ),
			)
		);

		wp_enqueue_style(
			'pbe-blocks',
			PBE_PLUGIN_URL . 'assets/css/blocks.css',
			array(),
			PBE_VERSION
		);
	}

	/**
	 * Render Photo Booth block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string
	 */
	public function render_photo_booth_block( array $attributes, string $content ): string {
		$event_id      = $attributes['eventId'] ?? 0;
		$frame_id      = $attributes['frameId'] ?? 0;
		$container_class = $attributes['containerClass'] ?? '';

		// Determine active event and frame with fallback logic.
		if ( ! $event_id ) {
			$settings = Settings::get_instance()->get_settings();
			$event_id = $settings['pbe_default_event_id'] ?? 0;
		}

		if ( ! $frame_id && $event_id ) {
			$frame_id = get_post_meta( $event_id, 'pbe_frame_id', true );
		}

		if ( ! $frame_id ) {
			$settings = Settings::get_instance()->get_settings();
			$frame_id = $settings['pbe_default_frame_id'] ?? 0;
		}

		$frame_image_url = $frame_id ? wp_get_attachment_image_url( (int) $frame_id, 'full' ) : '';

		// Get unique ID for this block instance.
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			$block_id = 'pbe-' . wp_generate_uuid4();
		} else {
			$block_id = 'pbe-' . uniqid( '', true );
		}

		// Extract variables for template.
		extract(
			array(
				'attributes'      => $attributes,
				'content'         => $content,
				'block_id'        => $block_id,
				'event_id'        => $event_id,
				'frame_id'        => $frame_id,
				'frame_image_url' => $frame_image_url,
				'container_class' => $container_class,
			),
			EXTR_SKIP
		);

		ob_start();
		include PBE_PLUGIN_DIR . 'src/blocks/photo-booth/render.php';
		return ob_get_clean();
	}

	/**
	 * Render Gallery block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string
	 */
	public function render_gallery_block( array $attributes, string $content ): string {
		$event_id      = $attributes['eventId'] ?? 0;
		$columns       = $attributes['columns'] ?? 3;
		$limit         = $attributes['limit'] ?? 20;
		$order         = $attributes['order'] ?? 'DESC';
		$container_class = $attributes['containerClass'] ?? '';

		// Determine active event with fallback.
		if ( ! $event_id ) {
			$settings = Settings::get_instance()->get_settings();
			$event_id = $settings['pbe_default_event_id'] ?? 0;
		}

		// Query approved photos.
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit', // Attachments use 'inherit' status.
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => $order,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => 'pbe_source',
					'value' => 'photo_booth',
					'compare' => '=',
				),
				array(
					'key'   => 'pbe_status',
					'value' => 'approved',
					'compare' => '=',
				),
			),
		);

		if ( $event_id > 0 ) {
			$args['meta_query'][] = array(
				'key'   => 'pbe_event_id',
				'value' => $event_id,
				'compare' => '=',
				'type' => 'NUMERIC',
			);
		}

		$photos_query = new \WP_Query( $args );

		// Extract variables for template.
		extract(
			array(
				'attributes'      => $attributes,
				'content'         => $content,
				'photos_query'    => $photos_query,
				'event_id'        => $event_id,
				'columns'         => $columns,
				'container_class' => $container_class,
			),
			EXTR_SKIP
		);

		ob_start();
		include PBE_PLUGIN_DIR . 'src/blocks/gallery/render.php';
		return ob_get_clean();
	}
}

