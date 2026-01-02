<?php
/**
 * Admin Settings Class
 * 
 * Handles admin settings page and options
 *
 * @package Smart_WebP_Converter
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

class SWC_Admin_Settings {
	
	/**
	 * Instance of this class
	 *
	 * @var SWC_Admin_Settings
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return SWC_Admin_Settings
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
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		add_options_page(
			__('Smart WebP Converter', 'smart-webp-converter'),
			__('WebP Converter', 'smart-webp-converter'),
			'manage_options',
			'smart-webp-converter',
			[$this, 'render_settings_page']
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings()
	{
		register_setting('swc_settings', 'swc_options', [$this, 'sanitize_options']);

		// General settings section
		add_settings_section(
			'swc_general_section',
			__('General Settings', 'smart-webp-converter'),
			[$this, 'render_general_section'],
			'smart-webp-converter'
		);

		// Conversion settings
		add_settings_field(
			'auto_convert',
			__('Auto Convert on Upload', 'smart-webp-converter'),
			[$this, 'render_auto_convert_field'],
			'smart-webp-converter',
			'swc_general_section'
		);

		add_settings_field(
			'webp_quality',
			__('WebP Quality', 'smart-webp-converter'),
			[$this, 'render_quality_field'],
			'smart-webp-converter',
			'swc_general_section'
		);

		add_settings_field(
			'max_dimensions',
			__('Maximum Dimensions', 'smart-webp-converter'),
			[$this, 'render_max_dimensions_field'],
			'smart-webp-converter',
			'swc_general_section'
		);

		add_settings_field(
			'serve_webp',
			__('Serve WebP to Browsers', 'smart-webp-converter'),
			[$this, 'render_serve_webp_field'],
			'smart-webp-converter',
			'swc_general_section'
		);

		// add_settings_field(
		// 	'delete_original',
		// 	__('Delete Original After Conversion', 'smart-webp-converter'),
		// 	[$this, 'render_delete_original_field'],
		// 	'smart-webp-converter',
		// 	'swc_general_section'
		// );
	}

	/**
	 * Sanitize options
	 *
	 * @param array $input Input options
	 * @return array Sanitized options
	 */
	public function sanitize_options($input)
	{
		$sanitized = [];

		$sanitized['auto_convert'] = isset($input['auto_convert']) ? (bool) $input['auto_convert'] : false;
		$sanitized['webp_quality'] = isset($input['webp_quality']) ? absint($input['webp_quality']) : 82;
		$sanitized['webp_quality'] = min(100, max(1, $sanitized['webp_quality']));
		$sanitized['max_width'] = isset($input['max_width']) ? absint($input['max_width']) : 2560;
		$sanitized['max_height'] = isset($input['max_height']) ? absint($input['max_height']) : 2560;
		$sanitized['serve_webp'] = isset($input['serve_webp']) ? (bool) $input['serve_webp'] : false;
		$sanitized['delete_original'] = isset($input['delete_original']) ? (bool) $input['delete_original'] : false;

		return $sanitized;
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page()
	{
		if (! current_user_can('manage_options')) {
			return;
		}
		
		// Check WebP support
		$converter = SWC_WebP_Converter::get_instance();
		$webp_supported = $converter->is_webp_supported();
		?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

			<?php if (! $webp_supported) : ?>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e('WebP conversion is not supported on your server.', 'smart-webp-converter'); ?></strong></p>
					<p><?php esc_html_e('Please install PHP GD extension with WebP support or ImageMagick with WebP support.', 'smart-webp-converter'); ?></p>
				</div>
			<?php else : ?>
				<div class="notice notice-success">
					<p><?php esc_html_e('WebP conversion is supported on your server.', 'smart-webp-converter'); ?></p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields('swc_settings');
				do_settings_sections('smart-webp-converter');
				submit_button(__('Save Settings', 'smart-webp-converter'));
				?>
			</form>
			
			<?php if ($webp_supported) : ?>
				<?php
				$batch_processor = SWC_Batch_Processor::get_instance();
				$total_images = $batch_processor->get_total_images();
				$progress = $batch_processor->get_progress();
				$is_running = $progress && isset($progress['status']) && $progress['status'] === 'running';
				?>
				<div class="swc-batch-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
					<h2><?php esc_html_e('Batch Processing', 'smart-webp-converter'); ?></h2>
					<p><?php esc_html_e('Convert existing images to WebP format using WordPress Cron. Processing runs in the background every minute, processing 10 images per batch.', 'smart-webp-converter'); ?></p>

					<p><strong><?php esc_html_e('Total images to process:', 'smart-webp-converter'); ?></strong> <span id="swc-total-images"><?php echo esc_html($total_images); ?></span>
						<button type="button" id="swc-clear-cache" class="button button-link" style="margin-left: 10px;">
							<?php esc_html_e('Clear Cache', 'smart-webp-converter'); ?>
						</button>
					</p>

					<p>
						<button type="button" id="swc-start-batch" class="button button-primary" <?php echo $is_running ? 'disabled' : ''; ?>>
							<?php esc_html_e('Start Batch Conversion', 'smart-webp-converter'); ?>
						</button>
						<button type="button" id="swc-stop-batch" class="button button-secondary" style="<?php echo $is_running ? '' : 'display: none;'; ?>">
							<?php esc_html_e('Stop Batch Processing', 'smart-webp-converter'); ?>
						</button>
					</p>

					<div id="swc-batch-progress" style="margin-top: 15px; <?php echo $is_running ? '' : 'display: none;'; ?>">
						<div style="background: #fff; border: 1px solid #ccc; border-radius: 3px; padding: 2px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
							<div id="swc-progress-bar" style="background: linear-gradient(to bottom, #2271b1, #135e96); height: 24px; width: <?php echo $is_running && isset($progress['progress']) ? esc_attr($progress['progress']) : 0; ?>%; transition: width 0.5s ease; border-radius: 2px; text-align: center; line-height: 24px; color: #fff; font-weight: bold; font-size: 12px;">
								<?php if ($is_running && isset($progress['progress'])) : ?>
									<?php echo esc_html(round($progress['progress'], 1)); ?>%
								<?php endif; ?>
							</div>
						</div>
						<div id="swc-progress-text" style="margin-top: 15px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px; font-size: 13px; line-height: 1.6;">
							<?php if ($is_running) : ?>
								<?php
								$offset = isset($progress['offset']) ? intval($progress['offset']) : 0;
								$total = isset($progress['total']) ? intval($progress['total']) : $total_images;
								// Get processed count from JSON file instead of progress
								$processed = $batch_processor->get_processed_count();
								$skipped = isset($progress['skipped']) ? intval($progress['skipped']) : 0;
								$errors = isset($progress['errors']) ? intval($progress['errors']) : 0;
								$progress_percent = isset($progress['progress']) ? round($progress['progress'], 1) : 0;

								$message = sprintf(
									__('Processing: %d / %d (%s%%)', 'smart-webp-converter'),
									$offset,
									$total,
									$progress_percent
								);
								$message .= '<br>' . sprintf(
									__('Processed: %d | Skipped: %d | Errors: %d', 'smart-webp-converter'),
									$processed,
									$skipped,
									$errors
								);

								if (isset($progress['started_at'])) {
									$started_time = strtotime($progress['started_at']);
									$now = current_time('timestamp');
									$elapsed = $now - $started_time;
									$minutes = floor($elapsed / 60);
									$seconds = $elapsed % 60;
									$message .= '<br>' . sprintf(
										__('Elapsed time: %dm %ds', 'smart-webp-converter'),
										$minutes,
										$seconds
									);
								}

								echo wp_kses_post($message);
								?>
							<?php endif; ?>
						</div>
					</div>

					<?php if ($is_running) : ?>
						<div style="margin-top: 15px; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 3px; font-size: 12px; color: #0c5460;">
							<strong><?php esc_html_e('Batch processing is running in the background.', 'smart-webp-converter'); ?></strong>
							<?php esc_html_e('You can close this page and the processing will continue. Check back later to see the progress.', 'smart-webp-converter'); ?>
						</div>
					<?php else : ?>
						<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 3px; font-size: 12px;">
							<strong><?php esc_html_e('Note:', 'smart-webp-converter'); ?></strong>
							<?php esc_html_e('Batch processing uses WordPress Cron and runs in the background. You can close this page and the processing will continue. Check back later to see the progress.', 'smart-webp-converter'); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
	
	/**
	 * Render general section
	 */
	public function render_general_section()
	{
		echo '<p>' . esc_html__('Configure WebP conversion settings.', 'smart-webp-converter') . '</p>';
	}

	/**
	 * Render auto convert field
	 */
	public function render_auto_convert_field()
	{
		$options = get_option('swc_options', []);
		$value = isset($options['auto_convert']) ? $options['auto_convert'] : true;
		?>
		<label>
			<input type="checkbox" name="swc_options[auto_convert]" value="1" <?php checked($value, true); ?>>
			<?php esc_html_e('Automatically convert images to WebP when uploaded', 'smart-webp-converter'); ?>
		</label>
		<?php
	}

	/**
	 * Render quality field
	 */
	public function render_quality_field()
	{
		$options = get_option('swc_options', []);
		$value = isset($options['webp_quality']) ? $options['webp_quality'] : 82;
		?>
		<input type="number" name="swc_options[webp_quality]" value="<?php echo esc_attr($value); ?>" min="1" max="100" step="1">
		<p class="description"><?php esc_html_e('WebP quality (1-100). Recommended: 80-85 for good balance between quality and file size.', 'smart-webp-converter'); ?></p>
		<?php
	}

	/**
	 * Render max dimensions field
	 */
	public function render_max_dimensions_field()
	{
		$options = get_option('swc_options', []);
		$max_width = isset($options['max_width']) ? $options['max_width'] : 2560;
		$max_height = isset($options['max_height']) ? $options['max_height'] : 2560;
		?>
		<label>
			<?php esc_html_e('Max Width:', 'smart-webp-converter'); ?>
			<input type="number" name="swc_options[max_width]" value="<?php echo esc_attr($max_width); ?>" min="0" step="1" style="width: 100px;">
			px
		</label>
		&nbsp;&nbsp;
		<label>
			<?php esc_html_e('Max Height:', 'smart-webp-converter'); ?>
			<input type="number" name="swc_options[max_height]" value="<?php echo esc_attr($max_height); ?>" min="0" step="1" style="width: 100px;">
			px
		</label>
		<p class="description"><?php esc_html_e('Resize images larger than these dimensions. Set to 0 to disable resizing.', 'smart-webp-converter'); ?></p>
		<?php
	}

	/**
	 * Render serve WebP field
	 */
	public function render_serve_webp_field()
	{
		$options = get_option('swc_options', []);
		$value = isset($options['serve_webp']) ? $options['serve_webp'] : true;
		?>
		<label>
			<input type="checkbox" name="swc_options[serve_webp]" value="1" <?php checked($value, true); ?>>
			<?php esc_html_e('Automatically serve WebP images to browsers that support it', 'smart-webp-converter'); ?>
		</label>
		<?php
	}

	/**
	 * Render delete original field
	 */
	public function render_delete_original_field()
	{
		$options = get_option('swc_options', []);
		$value = isset($options['delete_original']) ? $options['delete_original'] : false;
		?>
		<label>
			<input type="checkbox" name="swc_options[delete_original]" value="1" <?php checked($value, true); ?>>
			<?php esc_html_e('Delete original image files after WebP conversion (not recommended)', 'smart-webp-converter'); ?>
		</label>
		<p class="description"><?php esc_html_e('Warning: This will permanently delete original images. Use with caution!', 'smart-webp-converter'); ?></p>
		<?php
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_scripts($hook)
	{
		if ('settings_page_smart-webp-converter' !== $hook) {
			return;
		}

		wp_enqueue_script('swc-admin', SWC_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], SWC_VERSION, true);
		wp_localize_script('swc-admin', 'swcAdmin', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('swc_batch_process'),
		]);
	}
}

