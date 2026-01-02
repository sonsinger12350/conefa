<?php
/**
 * Plugin Name: Smart WebP Converter
 * Plugin URI: https://github.com/your-repo/smart-webp-converter
 * Description: Tự động chuyển đổi ảnh sang định dạng WebP và giảm dung lượng ảnh một cách hợp lý. Hỗ trợ tự động chuyển đổi khi upload và batch processing cho ảnh cũ.
 * Version: 1.0.0
 * Author: Lucas
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-webp-converter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('SWC_VERSION', '1.0.0');
define('SWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SWC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Smart_WebP_Converter {
	
	/**
	 * Instance of this class
	 *
	 * @var Smart_WebP_Converter
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return Smart_WebP_Converter
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init()
	{
		// Load plugin files
		$this->load_dependencies();

		// Initialize components
		$this->init_components();

		// Load text domain
		add_action('plugins_loaded', [$this, 'load_textdomain']);

		// Activation/Deactivation hooks
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies()
	{
		require_once SWC_PLUGIN_DIR . 'includes/class-webp-converter.php';
		require_once SWC_PLUGIN_DIR . 'includes/class-admin-settings.php';
		require_once SWC_PLUGIN_DIR . 'includes/class-batch-processor.php';
		require_once SWC_PLUGIN_DIR . 'includes/class-frontend-delivery.php';
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components()
	{
		// Add custom cron interval
		add_filter('cron_schedules', [$this, 'add_cron_interval']);

		// Initialize WebP converter
		SWC_WebP_Converter::get_instance();

		// Initialize admin settings
		if (is_admin()) {
			SWC_Admin_Settings::get_instance();
			SWC_Batch_Processor::get_instance();
		}

		// Initialize frontend delivery
		SWC_Frontend_Delivery::get_instance();
	}

	/**
	 * Add custom cron interval for batch processing
	 *
	 * @param array $schedules Existing schedules
	 * @return array Modified schedules
	 */
	public function add_cron_interval($schedules)
	{
		$schedules['swc_batch_interval'] = [
			'interval' => 60, // 1 minute
			'display'  => __('Every 1 minute (WebP Batch)', 'smart-webp-converter'),
		];

		return $schedules;
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain('smart-webp-converter', false, dirname(SWC_PLUGIN_BASENAME) . '/languages');
	}

	/**
	 * Plugin activation
	 */
	public function activate()
	{
		// Set default options
		$default_options = [
			'auto_convert'      => true,
			'webp_quality'      => 82,
			'max_width'         => 2560,
			'max_height'        => 2560,
			'convert_old_images' => false,
			'delete_original'   => false,
			'serve_webp'        => true,
		];

		add_option('swc_options', $default_options);

		// Flush rewrite rules if needed
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate()
	{
		// Clear scheduled cron events
		$timestamp = wp_next_scheduled('swc_batch_process_cron');

		if ($timestamp) {
			wp_unschedule_event($timestamp, 'swc_batch_process_cron');
		}

		// Clean up if needed
		flush_rewrite_rules();
	}
}

/**
 * Initialize the plugin
 */
function swc_init()
{
	return Smart_WebP_Converter::get_instance();
}

// Start the plugin
swc_init();

