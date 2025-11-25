<?php
/**
 * REST API endpoints.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * REST API class.
 */
class REST_API {
	/**
	 * Instance of this class.
	 *
	 * @var REST_API|null
	 */
	private static ?REST_API $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return REST_API
	 */
	public static function get_instance(): REST_API {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			'virtual-photo-booth/v1',
			'/events',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_events' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get events endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_events( \WP_REST_Request $request ): \WP_REST_Response {
		$events = get_posts(
			array(
				'post_type'      => 'photo_booth_event',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$formatted_events = array();
		foreach ( $events as $event ) {
			$formatted_events[] = array(
				'id'    => $event->ID,
				'title' => $event->post_title,
			);
		}

		return new \WP_REST_Response( $formatted_events, 200 );
	}
}


