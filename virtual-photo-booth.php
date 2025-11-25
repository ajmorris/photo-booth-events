<?php
/**
 * Plugin Name: Virtual Photo Booth
 * Plugin URI: https://example.com/virtual-photo-booth
 * Description: A block-based WordPress plugin for virtual photo booth functionality with camera capture, frame overlays, and gallery display.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: virtual-photo-booth
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package VirtualPhotoBooth
 */

declare(strict_types=1);

namespace VirtualPhotoBooth;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'PBE_VERSION', '1.0.0' );
define( 'PBE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PBE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PBE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * Instance of this class.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 */
	private function init(): void {
		// Load dependencies.
		$this->load_dependencies();

		// Initialize components immediately (CPT needs to register on init hook).
		$this->init_components();

		// Register AJAX handlers for both admin and frontend contexts.
		AJAX::get_instance();
		
		// Initialize admin components immediately in admin context.
		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies(): void {
		require_once PBE_PLUGIN_DIR . 'includes/class-cpt.php';
		require_once PBE_PLUGIN_DIR . 'includes/class-settings.php';
		require_once PBE_PLUGIN_DIR . 'includes/class-admin.php';
		require_once PBE_PLUGIN_DIR . 'includes/class-moderation.php';
		require_once PBE_PLUGIN_DIR . 'includes/class-ajax.php';
		require_once PBE_PLUGIN_DIR . 'includes/class-blocks.php';
		require_once PBE_PLUGIN_DIR . 'includes/class-rest-api.php';
	}

	/**
	 * Initialize components on init hook.
	 */
	public function init_components(): void {
		CPT::get_instance();
		Settings::get_instance();
		Blocks::get_instance();
		REST_API::get_instance();
	}

	/**
	 * Initialize admin components.
	 */
	public function init_admin(): void {
		Admin::get_instance();
		Moderation::get_instance();
	}
}

/**
 * Initialize the plugin.
 */
function init_plugin(): void {
	// Check if required WordPress functions exist.
	if ( ! function_exists( 'register_block_type' ) ) {
		add_action(
			'admin_notices',
			function() {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Virtual Photo Booth requires WordPress 5.0 or higher.', 'virtual-photo-booth' ); ?></p>
				</div>
				<?php
			}
		);
		return;
	}

	Plugin::get_instance();
}

// Initialize plugin.
init_plugin();

