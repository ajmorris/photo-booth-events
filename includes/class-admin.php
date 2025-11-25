<?php
/**
 * Admin functionality.
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

/**
 * Admin class.
 */
class Admin {
	/**
	 * Instance of this class.
	 *
	 * @var Admin|null
	 */
	private static ?Admin $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Admin
	 */
	public static function get_instance(): Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		// Only load on relevant pages.
		$relevant_pages = array(
			'post.php',
			'post-new.php',
			'settings_page_virtual-photo-booth',
		);

		if ( ! in_array( $hook, $relevant_pages, true ) ) {
			return;
		}

		// Enqueue media picker script.
		wp_enqueue_media();
		wp_enqueue_script(
			'pbe-admin',
			PBE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			PBE_VERSION,
			true
		);

		wp_enqueue_style(
			'pbe-admin',
			PBE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			PBE_VERSION
		);
	}
}


