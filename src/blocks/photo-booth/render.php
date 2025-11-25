<?php
/**
 * Photo Booth block render template.
 *
 * @package VirtualPhotoBooth
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Block content.
 * @var string $block_id  Unique block ID.
 * @var int    $event_id   Active event ID.
 * @var int    $frame_id   Active frame ID.
 * @var string $frame_image_url Frame image URL.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pbe-photo-booth-container <?php echo esc_attr( $container_class ); ?>" data-block-id="<?php echo esc_attr( $block_id ); ?>" data-event-id="<?php echo esc_attr( $event_id ); ?>">
	<div class="pbe-camera-wrapper">
		<video id="<?php echo esc_attr( $block_id ); ?>-video" class="pbe-video" autoplay playsinline></video>
		<canvas id="<?php echo esc_attr( $block_id ); ?>-canvas" class="pbe-canvas" style="display: none;"></canvas>
		<?php if ( $frame_image_url ) : ?>
			<img src="<?php echo esc_url( $frame_image_url ); ?>" class="pbe-frame-overlay" alt="<?php esc_attr_e( 'Frame', 'virtual-photo-booth' ); ?>" />
		<?php endif; ?>
	</div>
	<div class="pbe-controls">
		<button type="button" class="pbe-btn pbe-btn-capture" id="<?php echo esc_attr( $block_id ); ?>-capture">
			<?php esc_html_e( 'Capture', 'virtual-photo-booth' ); ?>
		</button>
		<button type="button" class="pbe-btn pbe-btn-retake" id="<?php echo esc_attr( $block_id ); ?>-retake" style="display: none;">
			<?php esc_html_e( 'Retake', 'virtual-photo-booth' ); ?>
		</button>
		<button type="button" class="pbe-btn pbe-btn-upload" id="<?php echo esc_attr( $block_id ); ?>-upload" style="display: none;">
			<?php esc_html_e( 'Upload', 'virtual-photo-booth' ); ?>
		</button>
	</div>
	<div class="pbe-status" id="<?php echo esc_attr( $block_id ); ?>-status"></div>
	<?php if ( $attributes['showGalleryLink'] ?? true ) : ?>
		<div class="pbe-gallery-link">
			<a href="#pbe-gallery"><?php esc_html_e( 'View Gallery', 'virtual-photo-booth' ); ?></a>
		</div>
	<?php endif; ?>
</div>

<script type="application/json" id="<?php echo esc_attr( $block_id ); ?>-config">
{
	"blockId": "<?php echo esc_js( $block_id ); ?>",
	"eventId": <?php echo absint( $event_id ); ?>,
	"frameImageUrl": "<?php echo esc_js( $frame_image_url ); ?>"
}
</script>


