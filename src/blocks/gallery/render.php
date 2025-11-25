<?php
/**
 * Gallery block render template.
 *
 * @package VirtualPhotoBooth
 *
 * @var array      $attributes Block attributes.
 * @var string     $content    Block content.
 * @var \WP_Query  $photos_query Query object.
 * @var int        $event_id   Active event ID.
 * @var int        $columns    Number of columns.
 * @var string     $container_class Container CSS class.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pbe-gallery-container <?php echo esc_attr( $container_class ); ?>" style="--pbe-columns: <?php echo esc_attr( $columns ); ?>;">
	<?php if ( $photos_query->have_posts() ) : ?>
		<div class="pbe-gallery-grid">
			<?php while ( $photos_query->have_posts() ) : ?>
				<?php $photos_query->the_post(); ?>
				<?php
				$photo_id = get_the_ID();
				$image_url = wp_get_attachment_image_url( $photo_id, 'medium' );
				$full_url  = wp_get_attachment_image_url( $photo_id, 'full' );
				?>
				<div class="pbe-gallery-item">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
				</div>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	<?php else : ?>
		<div class="pbe-gallery-empty">
			<p><?php esc_html_e( 'No photos available yet.', 'virtual-photo-booth' ); ?></p>
		</div>
	<?php endif; ?>
</div>


