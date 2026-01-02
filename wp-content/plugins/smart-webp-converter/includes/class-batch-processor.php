<?php

/**
 * Batch Processor Class
 * 
 * Handles batch conversion of existing images using WordPress Cron
 *
 * @package Smart_WebP_Converter
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

class SWC_Batch_Processor
{

	/**
	 * Cron hook name
	 */
	const CRON_HOOK = 'swc_batch_process_cron';

	/**
	 * Progress transient name
	 */
	const PROGRESS_TRANSIENT = 'swc_batch_progress';

	/**
	 * Lock transient name (to prevent concurrent execution)
	 */
	const LOCK_TRANSIENT = 'swc_batch_lock';

	/**
	 * Instance of this class
	 *
	 * @var SWC_Batch_Processor
	 */
	private static $instance = null;

	/**
	 * Get instance of this class
	 *
	 * @return SWC_Batch_Processor
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
		// Register cron hook
		add_action(self::CRON_HOOK, [$this, 'process_batch_cron']);

		// AJAX handlers
		add_action('wp_ajax_swc_start_batch', [$this, 'ajax_start_batch']);
		add_action('wp_ajax_swc_stop_batch', [$this, 'ajax_stop_batch']);
		add_action('wp_ajax_swc_get_batch_progress', [$this, 'ajax_get_batch_progress']);
		add_action('wp_ajax_swc_get_batch_stats', [$this, 'ajax_get_batch_stats']);
		add_action('wp_ajax_swc_trigger_batch', [$this, 'ajax_trigger_batch']);
		add_action('wp_ajax_swc_clear_cache', [$this, 'ajax_clear_cache']);
	}

	/**
	 * Format timestamp to server timezone (UTC+7)
	 *
	 * @param int $timestamp Unix timestamp (UTC)
	 * @param string $format Date format (default: 'Y-m-d H:i:s')
	 * @return string Formatted date string in UTC+7
	 */
	private function format_server_time($timestamp, $format = 'Y-m-d H:i:s')
	{
		// Create DateTime object from UTC timestamp
		$utc_time = new DateTime('@' . $timestamp, new DateTimeZone('UTC'));
		
		// Convert to UTC+7 (Vietnam timezone)
		$server_timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
		$utc_time->setTimezone($server_timezone);
		
		return $utc_time->format($format);
	}

	/**
	 * Convert absolute file path to relative path from WordPress root
	 *
	 * @param string $file_path Absolute file path
	 * @return string Relative path (e.g., "wp-content/uploads/2024/10/3-4.jpg") or empty string if conversion fails
	 */
	private function get_relative_path($file_path)
	{
		if (empty($file_path)) {
			return '';
		}

		// Get WordPress root directory
		$wp_root = ABSPATH;
		
		// Normalize paths (remove trailing slashes)
		$wp_root = rtrim($wp_root, '/\\');
		$file_path = rtrim($file_path, '/\\');
		
		// Check if file path is within WordPress root
		if (strpos($file_path, $wp_root) !== 0) {
			return '';
		}
		
		// Get relative path
		$relative_path = substr($file_path, strlen($wp_root));
		$relative_path = ltrim($relative_path, '/\\');
		
		return $relative_path;
	}

	/**
	 * Ensure log directory exists and is protected
	 *
	 * @return string Log directory path
	 */
	private function ensure_log_directory()
	{
		$upload_dir = wp_upload_dir();
		$log_dir = $upload_dir['basedir'] . '/swc-logs';

		if (! file_exists($log_dir)) {
			wp_mkdir_p($log_dir);
			file_put_contents($log_dir . '/.htaccess', 'deny from all');
		}

		return $log_dir;
	}

	/**
	 * Write debug log to file
	 *
	 * @param string $message Log message
	 */
	private function write_log($message)
	{
		$log_dir = $this->ensure_log_directory();
		$log_file = $log_dir . '/batch-' . date('Y-m-d') . '.log';
		$timestamp = $this->format_server_time(time());
		$formatted_message = '[' . $timestamp . '] ' . $message . PHP_EOL;
		@file_put_contents($log_file, $formatted_message, FILE_APPEND | LOCK_EX);
	}

	/**
	 * Clear cache for total images count
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache()
	{
		return delete_transient('swc_total_files_count');
	}

	/**
	 * Get total images to process (attachments only)
	 *
	 * @return int Number of image attachments
	 */
	public function get_total_images()
	{
		// Check cache first
		$cache_key = 'swc_total_files_count';
		$cached = get_transient($cache_key);
		if ($cached !== false) return intval($cached);

		$args = [
			'post_type'      => 'attachment',
			'post_mime_type' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$query = new WP_Query($args);
		$attachment_ids = $query->posts;
		$total = count($attachment_ids); // Count attachments only

		// Cache for 1 hour
		set_transient($cache_key, $total, HOUR_IN_SECONDS);

		return $total;
	}

	/**
	 * Get processed attachment IDs from JSON file
	 *
	 * @return array Array of processed attachment IDs
	 */
	private function get_processed_ids()
	{
		$upload_dir = wp_upload_dir();
		$json_file = $upload_dir['basedir'] . '/swc-logs/processed-ids.json';

		if (! file_exists($json_file)) {
			return [];
		}

		$content = @file_get_contents($json_file);
		if ($content === false) {
			return [];
		}

		$data = json_decode($content, true);
		if (! is_array($data) || ! isset($data['ids'])) {
			return [];
		}

		return array_map('intval', $data['ids']);
	}

	/**
	 * Get processed count from JSON file
	 *
	 * @return int Number of processed attachments
	 */
	public function get_processed_count()
	{
		$processed_ids = $this->get_processed_ids();
		return count($processed_ids);
	}

	/**
	 * Save processed attachment ID to JSON file
	 *
	 * @param int $attachment_id Attachment ID to save
	 */
	private function save_processed_id($attachment_id)
	{
		$log_dir = $this->ensure_log_directory();
		$json_file = $log_dir . '/processed-ids.json';
		$processed_ids = $this->get_processed_ids();
		$attachment_id = intval($attachment_id);

		if (! in_array($attachment_id, $processed_ids, true)) {
			$processed_ids[] = $attachment_id;
			sort($processed_ids);

			$data = [
				'last_updated' => current_time('mysql'),
				'total_count'  => count($processed_ids),
				'ids'          => $processed_ids,
			];

			@file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
		}
	}

	/**
	 * Get images to process
	 *
	 * @param int $limit Limit
	 * @return array Array of attachment IDs
	 */
	public function get_images_to_process($limit = 10)
	{
		// Get processed IDs to exclude
		$processed_ids = $this->get_processed_ids();

		$args = [
			'post_type'      => 'attachment',
			'post_mime_type' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
			'post_status'    => 'inherit',
			'posts_per_page' => -1, // Get all unprocessed images first
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		];

		// Exclude processed IDs if any
		if (! empty($processed_ids)) {
			$args['post__not_in'] = $processed_ids;
		}

		$query = new WP_Query($args);
		$attachment_ids = $query->posts;

		// Get only the requested limit from unprocessed images
		// Processed IDs are already excluded, so we just take the first $limit items
		return array_slice($attachment_ids, 0, $limit);
	}

	/**
	 * Start batch processing
	 */
	public function start_batch()
	{
		// Check if already running
		$progress = $this->get_progress();
		if ($progress && isset($progress['status']) && $progress['status'] === 'running') {
			return false;
		}

		// Clear cache to get fresh count
		$this->clear_cache();

		// Initialize progress
		$total = $this->get_total_images();
		$already_processed = $this->get_processed_count(); // Get already processed count from previous runs
		$this->update_progress([
			'status'    => 'running',
			'offset'    => $already_processed, // Start from already processed count (includes previous runs)
			'attachment_offset' => 0, // Attachment offset (for querying)
			'total'     => $total,
			'processed' => $already_processed, // Get from processed-ids.json
			'skipped'   => 0,
			'errors'    => 0,
			'thumbnails_processed' => 0,
			'total_processing_time' => 0, // Total time in seconds
			'average_time_per_image' => 0, // Average time per image in seconds
			'images_with_timing' => 0, // Count of images that actually had processing time (not cached)
			'deleted_attachments' => 0, // Count of deleted attachments (missing files)
			'started_at' => current_time('mysql'),
			'last_update' => current_time('mysql'),
		]);

		// Schedule cron event with recurring interval
		// WordPress will automatically create subsequent cron events based on the interval
		if (! wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_event(time(), 'swc_batch_interval', self::CRON_HOOK);
		}

		// Process first batch immediately
		$this->process_batch_cron();

		return true;
	}

	/**
	 * Stop batch processing
	 */
	public function stop_batch()
	{
		$this->clear_cron_and_lock();


		// Update progress status - force update to 'stopped'
		$progress = $this->get_progress();
		if ($progress) {
			$progress['status'] = 'stopped';
			$progress['stopped_at'] = current_time('mysql');
			$this->update_progress($progress);
		}
		else {
			// Even if no progress exists, create a stopped status to prevent auto-resume
			$this->update_progress([
				'status' => 'stopped',
				'stopped_at' => current_time('mysql'),
			]);
		}

		return true;
	}

	/**
	 * Process batch via cron
	 */
	public function process_batch_cron()
	{
		if (! $this->should_continue_processing()) {
			$this->clear_cron_and_lock();
			return;
		}

		// Check lock to prevent concurrent execution
		$lock = get_transient(self::LOCK_TRANSIENT);
		if ($lock && (time() - $lock) < 300) {
			return;
		}

		set_transient(self::LOCK_TRANSIENT, time(), 300);

		try {
			if (! $this->should_continue_processing()) {
				$this->clear_cron_and_lock();
				return;
			}

			$progress = $this->get_progress();
			if (! $progress) {
				$this->clear_cron_and_lock();
				return;
			}

			// Track file_offset for progress
			// Ensure offset includes already processed count from previous runs
			$already_processed = $this->get_processed_count();
			$file_offset = isset($progress['offset']) ? intval($progress['offset']) : $already_processed;
			// If stored offset is less than already processed, use already processed count
			if ($file_offset < $already_processed) $file_offset = $already_processed;
			$limit = 20; // Process 20 attachments per cron run

			// Get images to process (processed IDs are automatically excluded)
			$attachment_ids = $this->get_images_to_process($limit);

			if (empty($attachment_ids)) {
				$progress['status'] = 'completed';
				$progress['completed_at'] = current_time('mysql');
				$this->update_progress($progress);
				$this->clear_cron_and_lock();
				return;
			}

			// Increase memory limit and execution time for batch processing (including large files)
			@ini_set('memory_limit', '512M'); // Increased for large files
			@set_time_limit(600); // 10 minutes per batch for large files

			$converter = SWC_WebP_Converter::get_instance();
			$options = get_option('swc_options', array());
			$quality = isset($options['webp_quality']) ? intval($options['webp_quality']) : 82;
			$max_width = isset($options['max_width']) ? intval($options['max_width']) : 2560;
			$max_height = isset($options['max_height']) ? intval($options['max_height']) : 2560;

			// Get processed count from JSON file instead of tracking in progress
			$skipped = isset($progress['skipped']) ? intval($progress['skipped']) : 0;
			$errors = isset($progress['errors']) ? intval($progress['errors']) : 0;
			$thumbnails_processed = isset($progress['thumbnails_processed']) ? intval($progress['thumbnails_processed']) : 0;
			$total_processing_time = isset($progress['total_processing_time']) ? floatval($progress['total_processing_time']) : 0;
			$images_with_timing = isset($progress['images_with_timing']) ? intval($progress['images_with_timing']) : 0; // Count of images that actually had processing time
			$deleted_attachments = isset($progress['deleted_attachments']) ? intval($progress['deleted_attachments']) : 0; // Count of deleted attachments

			$total = isset($progress['total']) ? intval($progress['total']) : $this->get_total_images();
			$current_file_offset = $file_offset; // Track files processed (includes already processed from previous runs)
			$current_attachment_offset = isset($progress['attachment_offset']) ? intval($progress['attachment_offset']) : 0; // Track attachments processed
			$start_time = microtime(true);
			$max_execution_time = 550; // Maximum 550 seconds per batch (9+ minutes) to leave time for cleanup, increased for large files

			foreach ($attachment_ids as $attachment_id) {
				if ((microtime(true) - $start_time) > $max_execution_time) {
					$this->update_progress($this->build_progress_data(
						$progress, $current_file_offset, $current_attachment_offset,
						$total, $skipped, $errors, $thumbnails_processed,
						$total_processing_time, $images_with_timing, $deleted_attachments
					));
					break;
				}

				// Check if attachment ID is already in processed-ids.json
				$processed_ids = $this->get_processed_ids();
				if (in_array($attachment_id, $processed_ids, true)) {
					$file_path_for_log = get_attached_file($attachment_id);
					$relative_path = $this->get_relative_path($file_path_for_log);
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SKIPPED (already processed)' . $relative_path);
					
					$this->save_processed_id($attachment_id);
					$skipped++;
					$current_file_offset++;
					$current_attachment_offset++;

					$this->update_progress($this->build_progress_data(
						$progress, $current_file_offset, $current_attachment_offset,
						$total, $skipped, $errors, $thumbnails_processed,
						$total_processing_time, $images_with_timing, $deleted_attachments
					));
					continue;
				}

				// Get file path
				$file_path = get_attached_file($attachment_id);
				if (! $file_path || ! file_exists($file_path)) {
					$upload_dir = wp_upload_dir();
					$should_be_in_uploads = $file_path && (strpos($file_path, $upload_dir['basedir']) === 0);

					if ($should_be_in_uploads || ! $file_path) {
						$deleted = wp_delete_attachment($attachment_id, true);

						if ($deleted) {
							$relative_path = $file_path ? $this->get_relative_path($file_path) : '';
							$this->write_log('[SWC Batch] ID ' . $attachment_id . ': DELETED (file missing)' . $relative_path);
							$deleted_attachments++;
							$current_file_offset++;
							$current_attachment_offset++;
							$this->save_processed_id($attachment_id);

							$this->update_progress($this->build_progress_data(
								$progress, $current_file_offset, $current_attachment_offset,
								$total, $skipped, $errors, $thumbnails_processed,
								$total_processing_time, $images_with_timing, $deleted_attachments
							));
							continue;
						}
					}

					$errors++;
					$current_file_offset++;
					$current_attachment_offset++;
					$this->save_processed_id($attachment_id);

					$this->update_progress($this->build_progress_data(
						$progress, $current_file_offset, $current_attachment_offset,
						$total, $skipped, $errors, $thumbnails_processed,
						$total_processing_time, $images_with_timing, $deleted_attachments
					));
					continue;
				}

				// Convert to WebP with auto quality adjustment if WebP is larger
				$original_size = filesize($file_path);
				$conversion_result = $this->convert_with_quality_adjustment(
					$converter, $file_path, $attachment_id, $quality, $max_width, $max_height, $original_size
				);

				$is_success = $conversion_result['success'];
				$total_processing_time_for_image = $conversion_result['processing_time'];
				$is_cached = $conversion_result['cached'];
				$quality_adjusted = $conversion_result['quality_adjusted'];
				$current_quality = $conversion_result['final_quality'];
				$webp_path = $conversion_result['webp_path'];
				$webp_size = $conversion_result['webp_size'];

				// Add processing time to total
				if ($total_processing_time_for_image > 0 && ! $is_cached) {
					$total_processing_time += $total_processing_time_for_image;
					$images_with_timing++;
				}

				// Check if WebP is still larger than original after reaching min quality
				$min_quality = 50;
				if ($is_success && $webp_size >= $original_size && $current_quality <= $min_quality) {
					if ($webp_path && file_exists($webp_path)) {
						@unlink($webp_path);
					}
					
					$relative_path = ' | ' . $this->get_relative_path($file_path);
					$processing_time_str = $this->format_processing_time($total_processing_time_for_image);
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SKIPPED (WebP still larger at min quality ' . $min_quality . ')' . $processing_time_str . $relative_path);
					
					$this->save_processed_id($attachment_id);
					$skipped++;
					$current_file_offset++;
					$current_attachment_offset++;

					$this->update_progress($this->build_progress_data(
						$progress, $current_file_offset, $current_attachment_offset,
						$total, $skipped, $errors, $thumbnails_processed,
						$total_processing_time, $images_with_timing, $deleted_attachments
					));
					continue;
				}

				if ($is_success) {
					// Update metadata
					$metadata = wp_get_attachment_metadata($attachment_id);

					if ($metadata) {
						if ($webp_path) {
							$metadata['swc_webp'] = [
								'file' => basename($webp_path),
								'path' => $webp_path,
								'url'  => $converter->get_attachment_webp_url($attachment_id),
								'size' => $webp_size,
							];
							wp_update_attachment_metadata($attachment_id, $metadata);
						}
					}

					// Log success with processing time and file sizes
					$original_size_kb = number_format($original_size / 1024, 2);
					$webp_size_kb = number_format($webp_size / 1024, 2);
					$size_reduction = $original_size > 0 ? round((($original_size - $webp_size) / $original_size) * 100, 1) : 0;
					
					$size_info = ' | ' . $original_size_kb . ' KB → ' . $webp_size_kb . ' KB';
					$size_info .= ($size_reduction > 0) ? ' (-' . $size_reduction . '%)' : ' (+' . abs($size_reduction) . '%)';
					$quality_info = $quality_adjusted ? ' [Quality: ' . $quality . ' → ' . $current_quality . ']' : '';
					$relative_path = ' | ' . $this->get_relative_path($file_path);
					$processing_time_str = $this->format_processing_time($total_processing_time_for_image);
					
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SUCCESS' . $processing_time_str . $size_info . $quality_info . $relative_path);

					// Processed count is now tracked in processed-ids.json, no need to increment
					$current_file_offset++; // Count attachment (only count attachments, not thumbnails)

					// Convert thumbnails and count them (for statistics only, not for offset)
					if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
						$base_dir = dirname($file_path);

						foreach ($metadata['sizes'] as $size_name => $size_data) {
							$thumb_path = $base_dir . '/' . $size_data['file'];

							if (file_exists($thumb_path)) {
								// Check if WebP already exists for thumbnail
								$thumb_webp_path = $converter->get_webp_path($thumb_path);

								if (! file_exists($thumb_webp_path)) {
									$thumb_result = $converter->convert_image_to_webp($thumb_path, $quality, null, null);

									// Extract processing time for thumbnail
									if (is_array($thumb_result) && isset($thumb_result['success']) && $thumb_result['success']) {
										if (isset($thumb_result['processing_time'])) {
											$thumb_processing_time = floatval($thumb_result['processing_time']);
											// Only add processing time if not cached (cached images have very small time)
											$thumb_is_cached = isset($thumb_result['cached']) && $thumb_result['cached'] === true;

											if (! $thumb_is_cached || $thumb_processing_time > 0.001) {
												$total_processing_time += $thumb_processing_time;
												$images_with_timing++; // Count this thumbnail for average calculation
											}
										}
										$thumbnails_processed++;
										// Don't increment offset for thumbnails - only count attachments
									}
									elseif ($thumb_result === true) {
										$thumbnails_processed++;
										// Don't increment offset for thumbnails - only count attachments
									}
									else {
										$errors++;
										// Don't increment offset for thumbnails - only count attachments
									}
								}
								else {
									// Thumbnail already exists - don't count in skipped (only count attachments)
									// Thumbnails are tracked separately in thumbnails_processed
								}

								$this->update_progress($this->build_progress_data(
									$progress, $current_file_offset, $current_attachment_offset,
									$total, $skipped, $errors, $thumbnails_processed,
									$total_processing_time, $images_with_timing, $deleted_attachments
								));

								if ((microtime(true) - $start_time) > $max_execution_time) {
									break 2;
								}
							}
						}
					}

					// Save processed ID after successful conversion
					$this->save_processed_id($attachment_id);

					$current_attachment_offset++; // Move to next attachment after processing all files

					// Update progress after original image (if thumbnails weren't processed)
					if (empty($metadata['sizes']) || ! is_array($metadata['sizes'])) {
						$this->update_progress($this->build_progress_data(
							$progress, $current_file_offset, $current_attachment_offset,
							$total, $skipped, $errors, $thumbnails_processed,
							$total_processing_time, $images_with_timing, $deleted_attachments
						));
					}
				}
				else {
					$error_msg = 'Unknown error';
					if (is_array($conversion_result['result']) && isset($conversion_result['result']['error'])) {
						$error_msg = $conversion_result['result']['error'];
					} elseif (is_object($conversion_result['result']) && is_wp_error($conversion_result['result'])) {
						$error_msg = $conversion_result['result']->get_error_message();
					}
					
					$relative_path = ' | ' . $this->get_relative_path($file_path);
					$processing_time_str = $this->format_processing_time($total_processing_time_for_image);
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': ERROR - ' . $error_msg . $processing_time_str . $relative_path);

					$errors++;
					$current_file_offset++;
					$current_attachment_offset++;
					$this->save_processed_id($attachment_id);

					$this->update_progress($this->build_progress_data(
						$progress, $current_file_offset, $current_attachment_offset,
						$total, $skipped, $errors, $thumbnails_processed,
						$total_processing_time, $images_with_timing, $deleted_attachments
					));
				}

				// Clear memory after each image
				if (function_exists('gc_collect_cycles')) gc_collect_cycles();
			}

			// Update progress after processing all attachments in this batch
			$updated_progress = $this->get_progress();

			if ($updated_progress && isset($updated_progress['status']) && $updated_progress['status'] === 'stopped') {
				$this->clear_cron_and_lock();
				return;
			}

			if ($this->should_continue_processing()) {
				$this->update_progress($this->build_progress_data(
					$progress, $current_file_offset, $current_attachment_offset,
					$total, $skipped, $errors, $thumbnails_processed,
					$total_processing_time, $images_with_timing, $deleted_attachments
				));
			} else {
				$this->clear_cron_and_lock();
				return;
			}
		}
		finally {
			// Always release lock, even if error occurs
			delete_transient(self::LOCK_TRANSIENT);
		}
	}

	/**
	 * Get progress data
	 *
	 * @return array|false Progress data or false
	 */
	public function get_progress()
	{
		return get_transient(self::PROGRESS_TRANSIENT);
	}

	/**
	 * Convert image with quality adjustment if WebP is larger than original
	 *
	 * @param SWC_WebP_Converter $converter Converter instance
	 * @param string             $file_path Original file path
	 * @param int                $attachment_id Attachment ID
	 * @param int                $quality Initial quality
	 * @param int                $max_width Max width
	 * @param int                $max_height Max height
	 * @param int                $original_size Original file size
	 * @return array Conversion result with success, processing_time, cached, quality_adjusted, final_quality, webp_path, webp_size, result
	 */
	private function convert_with_quality_adjustment($converter, $file_path, $attachment_id, $quality, $max_width, $max_height, $original_size)
	{
		$current_quality = $quality;
		$min_quality = 50;
		$result = false;
		$is_success = false;
		$total_processing_time = 0;
		$quality_adjusted = false;
		$webp_path = null;
		$webp_size = 0;
		$is_cached = false;

		while ($current_quality >= $min_quality) {
			$temp_webp_path = $converter->get_webp_path($file_path);
			if ($temp_webp_path && file_exists($temp_webp_path)) {
				@unlink($temp_webp_path);
			}

			$result = $converter->convert_image_to_webp($file_path, $current_quality, $max_width, $max_height);

			$is_success = false;
			if (is_array($result) && isset($result['success']) && $result['success']) {
				$is_success = true;
				if (isset($result['processing_time'])) {
					$total_processing_time += floatval($result['processing_time']);
					$is_cached = isset($result['cached']) && $result['cached'] === true;
				}
			} elseif ($result === true) {
				$is_success = true;
			}

			if (! $is_success) {
				break;
			}

			$webp_path = $converter->get_attachment_webp_path($attachment_id);
			if ($webp_path && file_exists($webp_path)) {
				$webp_size = filesize($webp_path);
			}

			if ($webp_size < $original_size) {
				break;
			}

			if ($current_quality > $min_quality) {
				$quality_adjusted = true;
				$current_quality -= 10;
			} else {
				break;
			}
		}

		return [
			'success'           => $is_success,
			'processing_time'   => $total_processing_time,
			'cached'            => $is_cached,
			'quality_adjusted'  => $quality_adjusted,
			'final_quality'     => $current_quality,
			'webp_path'         => $webp_path,
			'webp_size'         => $webp_size,
			'result'            => $result,
		];
	}

	/**
	 * Calculate average processing time per image
	 *
	 * @param float $total_time Total processing time
	 * @param int   $images_count Number of images with timing data
	 * @return float Average time per image
	 */
	private function calculate_average_time($total_time, $images_count)
	{
		return $images_count > 0 ? round($total_time / $images_count, 3) : 0;
	}

	/**
	 * Calculate progress percentage
	 *
	 * @param int $current Current offset
	 * @param int $total Total count
	 * @return float Progress percentage
	 */
	private function calculate_progress_percent($current, $total)
	{
		return $total > 0 ? round(($current / $total) * 100, 2) : 0;
	}

	/**
	 * Format processing time string for logging
	 *
	 * @param float $processing_time Processing time in seconds
	 * @return string Formatted time string
	 */
	private function format_processing_time($processing_time)
	{
		return $processing_time > 0 ? ' (' . number_format($processing_time, 2) . 's)' : '';
	}

	/**
	 * Build progress data array
	 *
	 * @param array $progress Existing progress data
	 * @param int   $current_file_offset Current file offset
	 * @param int   $current_attachment_offset Current attachment offset
	 * @param int   $total Total count
	 * @param int   $skipped Skipped count
	 * @param int   $errors Error count
	 * @param int   $thumbnails_processed Thumbnails processed count
	 * @param float $total_processing_time Total processing time
	 * @param int   $images_with_timing Images with timing data
	 * @param int   $deleted_attachments Deleted attachments count
	 * @return array Progress data array
	 */
	private function build_progress_data($progress, $current_file_offset, $current_attachment_offset, $total, $skipped, $errors, $thumbnails_processed, $total_processing_time, $images_with_timing, $deleted_attachments)
	{
		$average_time = $this->calculate_average_time($total_processing_time, $images_with_timing);
		$progress_percent = $this->calculate_progress_percent($current_file_offset, $total);

		return [
			'status'                => 'running',
			'offset'                => $current_file_offset,
			'attachment_offset'     => $current_attachment_offset,
			'total'                 => $total,
			'processed'             => $this->get_processed_count(),
			'skipped'               => $skipped,
			'errors'                => $errors,
			'thumbnails_processed'  => $thumbnails_processed,
			'total_processing_time' => $total_processing_time,
			'average_time_per_image' => $average_time,
			'images_with_timing'    => $images_with_timing,
			'deleted_attachments'   => $deleted_attachments,
			'progress'              => $progress_percent,
			'started_at'            => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
			'last_update'           => current_time('mysql'),
		];
	}

	/**
	 * Check if batch should continue processing
	 *
	 * @return bool True if should continue, false otherwise
	 */
	private function should_continue_processing()
	{
		$progress = $this->get_progress();
		return $progress && isset($progress['status']) && $progress['status'] === 'running';
	}

	/**
	 * Clear cron events and release lock
	 */
	private function clear_cron_and_lock()
	{
		wp_clear_scheduled_hook(self::CRON_HOOK);
		delete_transient(self::LOCK_TRANSIENT);
	}

	/**
	 * Update progress data
	 *
	 * @param array $data Progress data
	 */
	private function update_progress($data)
	{
		$existing = $this->get_progress();

		// Ensure timing stats are included
		if (! isset($data['total_processing_time'])) {
			$data['total_processing_time'] = $existing['total_processing_time'] ?? 0;
		}

		if (! isset($data['images_with_timing'])) {
			$data['images_with_timing'] = $existing['images_with_timing'] ?? 0;
		}

		if (! isset($data['average_time_per_image'])) {
			$data['average_time_per_image'] = $this->calculate_average_time(
				$data['total_processing_time'],
				$data['images_with_timing']
			);
		}

		set_transient(self::PROGRESS_TRANSIENT, $data, DAY_IN_SECONDS);
	}

	/**
	 * Clear progress data
	 */
	public function clear_progress()
	{
		delete_transient(self::PROGRESS_TRANSIENT);
	}

	/**
	 * AJAX handler to start batch
	 */
	public function ajax_start_batch()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied', 'smart-webp-converter')]);
		}

		$result = $this->start_batch();

		if ($result) {
			wp_send_json_success(['message' => __('Batch processing started', 'smart-webp-converter')]);
		}
		else {
			wp_send_json_error(['message' => __('Batch processing is already running', 'smart-webp-converter')]);
		}
	}

	/**
	 * AJAX handler to stop batch
	 */
	public function ajax_stop_batch()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied', 'smart-webp-converter')]);
		}

		$result = $this->stop_batch();

		if ($result) {
			wp_send_json_success(['message' => __('Batch processing stopped', 'smart-webp-converter')]);
		}
		else {
			wp_send_json_error(['message' => __('Failed to stop batch processing', 'smart-webp-converter')]);
		}
	}

	/**
	 * AJAX handler to get batch progress
	 */
	public function ajax_get_batch_progress()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied', 'smart-webp-converter')]);
		}

		$progress = $this->get_progress();

		if (! $progress) {
			wp_send_json_success([
				'status' => 'idle',
				'message' => __('No batch processing in progress', 'smart-webp-converter'),
			]);
		}

		if (isset($progress['status']) && $progress['status'] === 'stopped') {
			$this->clear_cron_and_lock();
		}

		// If status is 'running', verify that cron is actually scheduled
		// If not, it might have been stopped externally, so update status
		if (isset($progress['status']) && $progress['status'] === 'running') {
			$next_scheduled = wp_next_scheduled(self::CRON_HOOK);
			if (! $next_scheduled) {
				// Status says running but no cron scheduled - might have been stopped
				// Don't auto-resume, just return the status as-is
				// The frontend will handle it appropriately
			}
		}

		wp_send_json_success($progress);
	}

	/**
	 * AJAX handler for getting batch stats
	 */
	public function ajax_get_batch_stats()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied', 'smart-webp-converter')]);
		}

		$total = $this->get_total_images();

		wp_send_json_success([
			'total' => $total,
		]);
	}

	/**
	 * AJAX handler to trigger batch processing (fallback when cron doesn't run)
	 */
	public function ajax_trigger_batch()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied', 'smart-webp-converter')]);
		}

		$progress = $this->get_progress();

		// Only trigger if batch is running
		if (! $progress || ! isset($progress['status']) || $progress['status'] !== 'running') {
			wp_send_json_success(['message' => __('Batch is not running', 'smart-webp-converter')]);
		}

		// Check lock to prevent concurrent execution
		$lock = get_transient(self::LOCK_TRANSIENT);
		if ($lock && (time() - $lock) < 300) {
			// Another batch is running, skip
			wp_send_json_success(['message' => __('Batch is already processing', 'smart-webp-converter')]);
		}

		if (! $this->should_continue_processing()) {
			$this->clear_cron_and_lock();
			wp_send_json_success(['message' => __('Batch is not running', 'smart-webp-converter')]);
		}

		// Ensure cron is scheduled (WordPress will handle recurring events automatically)
		// Only schedule if not already scheduled
		if (! wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_event(time(), 'swc_batch_interval', self::CRON_HOOK);
		}

		// Trigger batch processing
		$this->process_batch_cron();

		wp_send_json_success(['message' => __('Batch processing triggered', 'smart-webp-converter')]);
	}

	/**
	 * AJAX handler to clear cache
	 */
	public function ajax_clear_cache()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied', 'smart-webp-converter')]);
		}

		$result = $this->clear_cache();

		if ($result) {
			wp_send_json_success(['message' => __('Cache cleared successfully', 'smart-webp-converter')]);
		}
		else {
			wp_send_json_success(['message' => __('Cache was already empty or cleared', 'smart-webp-converter')]);
		}
	}
}
