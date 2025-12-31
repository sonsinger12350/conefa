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
		add_action(self::CRON_HOOK, array($this, 'process_batch_cron'));

		// AJAX handlers
		add_action('wp_ajax_swc_start_batch', array($this, 'ajax_start_batch'));
		add_action('wp_ajax_swc_stop_batch', array($this, 'ajax_stop_batch'));
		add_action('wp_ajax_swc_get_batch_progress', array($this, 'ajax_get_batch_progress'));
		add_action('wp_ajax_swc_get_batch_stats', array($this, 'ajax_get_batch_stats'));
		add_action('wp_ajax_swc_trigger_batch', array($this, 'ajax_trigger_batch'));
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
	 * Write debug log to file
	 *
	 * @param string $message Log message
	 */
	private function write_log($message)
	{
		// Get upload directory
		$upload_dir = wp_upload_dir();
		$log_dir = $upload_dir['basedir'] . '/swc-logs';

		// Create log directory if it doesn't exist
		if (! file_exists($log_dir)) {
			wp_mkdir_p($log_dir);
			// Add .htaccess to protect log files
			file_put_contents($log_dir . '/.htaccess', 'deny from all');
		}

		// Log file path (one file per day)
		$log_file = $log_dir . '/batch-' . date('Y-m-d') . '.log';

		// Format message with timestamp (server time UTC+7)
		$timestamp = $this->format_server_time(time());
		$formatted_message = '[' . $timestamp . '] ' . $message . PHP_EOL;

		// Write to file (append mode)
		@file_put_contents($log_file, $formatted_message, FILE_APPEND | LOCK_EX);
	}

	/**
	 * Get total images to process (including thumbnails)
	 *
	 * @return int Number of image files (attachments + thumbnails)
	 */
	public function get_total_images()
	{
		// Check cache first
		$cache_key = 'swc_total_files_count';
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			return intval($cached);
		}

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/jpg', 'image/png', 'image/gif'),
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new WP_Query($args);
		$attachment_ids = $query->posts;
		$total = count($attachment_ids); // Count original images

		// Count thumbnails for each attachment (only count existing files)
		foreach ($attachment_ids as $attachment_id) {
			$metadata = wp_get_attachment_metadata($attachment_id);
			if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
				$file_path = get_attached_file($attachment_id);
				if ($file_path) {
					$base_dir = dirname($file_path);
					foreach ($metadata['sizes'] as $size_name => $size_data) {
						$thumb_path = $base_dir . '/' . $size_data['file'];
						if (file_exists($thumb_path)) {
							$total++; // Only count if file exists
						}
					}
				}
			}
		}

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
			return array();
		}

		$content = @file_get_contents($json_file);
		if ($content === false) {
			return array();
		}

		$data = json_decode($content, true);
		if (! is_array($data) || ! isset($data['ids'])) {
			return array();
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
		$upload_dir = wp_upload_dir();
		$log_dir = $upload_dir['basedir'] . '/swc-logs';

		// Create log directory if it doesn't exist
		if (! file_exists($log_dir)) {
			wp_mkdir_p($log_dir);
			// Add .htaccess to protect log files
			file_put_contents($log_dir . '/.htaccess', 'deny from all');
		}

		$json_file = $log_dir . '/processed-ids.json';

		// Get existing processed IDs
		$processed_ids = $this->get_processed_ids();

		// Add new ID if not already present
		$attachment_id = intval($attachment_id);
		if (! in_array($attachment_id, $processed_ids, true)) {
			$processed_ids[] = $attachment_id;
			sort($processed_ids); // Keep sorted for easier reading

			// Save to JSON file
			$data = array(
				'last_updated' => current_time('mysql'),
				'total_count'  => count($processed_ids),
				'ids'          => $processed_ids,
			);

			@file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
		}
	}

	/**
	 * Get images to process
	 *
	 * @param int $offset Offset
	 * @param int $limit Limit
	 * @return array Array of attachment IDs
	 */
	public function get_images_to_process($offset = 0, $limit = 10)
	{
		// Get processed IDs to exclude
		$processed_ids = $this->get_processed_ids();

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/jpg', 'image/png', 'image/gif'),
			'post_status'    => 'inherit',
			'posts_per_page' => $limit * 2, // Get more to account for exclusions
			'offset'         => $offset,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		// Exclude processed IDs if any
		if (! empty($processed_ids)) {
			$args['post__not_in'] = $processed_ids;
		}

		$query = new WP_Query($args);
		$attachment_ids = $query->posts;

		// Limit to requested amount
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
		delete_transient('swc_total_files_count');

		// Initialize progress
		$total = $this->get_total_images();
		$this->update_progress(array(
			'status'    => 'running',
			'offset'    => 0, // File offset (attachments + thumbnails)
			'attachment_offset' => 0, // Attachment offset (for querying)
			'total'     => $total,
			'processed' => $this->get_processed_count(), // Get from processed-ids.json
			'skipped'   => 0,
			'errors'    => 0,
			'thumbnails_processed' => 0,
			'total_processing_time' => 0, // Total time in seconds
			'average_time_per_image' => 0, // Average time per image in seconds
			'images_with_timing' => 0, // Count of images that actually had processing time (not cached)
			'deleted_attachments' => 0, // Count of deleted attachments (missing files)
			'started_at' => current_time('mysql'),
			'last_update' => current_time('mysql'),
		));

		// Schedule cron event (run every minute)
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
		// Clear scheduled event
		$timestamp = wp_next_scheduled(self::CRON_HOOK);
		if ($timestamp) {
			wp_unschedule_event($timestamp, self::CRON_HOOK);
		}

		// Release lock
		delete_transient(self::LOCK_TRANSIENT);

		// Update progress status
		$progress = $this->get_progress();
		if ($progress) {
			$progress['status'] = 'stopped';
			$progress['stopped_at'] = current_time('mysql');
			$this->update_progress($progress);
		}

		return true;
	}

	/**
	 * Process batch via cron
	 */
	public function process_batch_cron()
	{
		// Check lock to prevent concurrent execution
		$lock = get_transient(self::LOCK_TRANSIENT);
		if ($lock && (time() - $lock) < 300) {
			// Another batch is running, skip this execution
			return;
		}

		// Set lock (valid for 5 minutes)
		set_transient(self::LOCK_TRANSIENT, time(), 300);

		try {
			$progress = $this->get_progress();

			// Check if batch is running
			if (! $progress || ! isset($progress['status']) || $progress['status'] !== 'running') {
				// Clear scheduled event if not running
				$timestamp = wp_next_scheduled(self::CRON_HOOK);
				if ($timestamp) {
					wp_unschedule_event($timestamp, self::CRON_HOOK);
				}
				// Release lock
				delete_transient(self::LOCK_TRANSIENT);
				return;
			}

			// Use attachment_offset to query attachments, but track file_offset for progress
			$attachment_offset = isset($progress['attachment_offset']) ? intval($progress['attachment_offset']) : 0;
			$file_offset = isset($progress['offset']) ? intval($progress['offset']) : 0;
			$limit = 10; // Process 10 attachments per cron run

			// Get images to process (by attachment offset)
			$attachment_ids = $this->get_images_to_process($attachment_offset, $limit);

			if (empty($attachment_ids)) {
				// Finished
				$progress['status'] = 'completed';
				$progress['completed_at'] = current_time('mysql');
				$this->update_progress($progress);

				// Clear scheduled event
				$timestamp = wp_next_scheduled(self::CRON_HOOK);
				if ($timestamp) {
					wp_unschedule_event($timestamp, self::CRON_HOOK);
				}
				// Release lock
				delete_transient(self::LOCK_TRANSIENT);
				return;
			}

			// Increase memory limit and execution time for batch processing
			@ini_set('memory_limit', '256M');
			@set_time_limit(300); // 5 minutes per batch

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
			$current_file_offset = $file_offset; // Track files processed
			$current_attachment_offset = $attachment_offset; // Track attachments processed
			$start_time = microtime(true);
			$max_execution_time = 50; // Maximum 50 seconds per batch to leave time for cleanup

			foreach ($attachment_ids as $attachment_id) {
				// Check timeout - stop if running too long
				$elapsed = microtime(true) - $start_time;
				if ($elapsed > $max_execution_time) {
					// Calculate average time (only count images that actually had processing time)
					$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

					// Update progress before stopping
					$this->update_progress(array(
						'status'    => 'running',
						'offset'    => $current_file_offset,
						'attachment_offset' => $current_attachment_offset,
						'total'     => $total,
						'processed' => $this->get_processed_count(),
						'skipped'   => $skipped,
						'errors'    => $errors,
						'thumbnails_processed' => $thumbnails_processed,
						'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
						'average_time_per_image' => $average_time,
						'images_with_timing' => $images_with_timing,
						'deleted_attachments' => $deleted_attachments,
						'progress'  => $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0,
						'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
						'last_update' => current_time('mysql'),
					));
					break; // Stop processing, let next cron continue
				}

				// Check if attachment ID is already in processed-ids.json
				$processed_ids = $this->get_processed_ids();
				if (in_array($attachment_id, $processed_ids, true)) {
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SKIPPED (already processed)');
					$skipped++;
					$current_file_offset++; // Count original image as skipped

					// Also count thumbnails for skipped attachment
					$metadata = wp_get_attachment_metadata($attachment_id);
					if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
						$base_dir = dirname(get_attached_file($attachment_id));
						foreach ($metadata['sizes'] as $size_name => $size_data) {
							$thumb_path = $base_dir . '/' . $size_data['file'];
							if (file_exists($thumb_path)) {
								$skipped++;
								$current_file_offset++; // Count thumbnail
							}
						}
					}

					$current_attachment_offset++; // Move to next attachment

					// Calculate average time (only count images that actually had processing time)
					$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

					// Update progress
					$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
					$this->update_progress(array(
						'status'    => 'running',
						'offset'    => $current_file_offset,
						'attachment_offset' => $current_attachment_offset,
						'total'     => $total,
						'processed' => $this->get_processed_count(),
						'skipped'   => $skipped,
						'errors'    => $errors,
						'thumbnails_processed' => $thumbnails_processed,
						'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
						'average_time_per_image' => $average_time,
						'images_with_timing' => $images_with_timing,
						'deleted_attachments' => $deleted_attachments,
						'progress'  => $progress_percent,
						'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
						'last_update' => current_time('mysql'),
					));
					continue;
				}

				// Get file path
				$file_path = get_attached_file($attachment_id);
				if (! $file_path || ! file_exists($file_path)) {
					// Check if file should be in uploads directory
					$upload_dir = wp_upload_dir();
					$should_be_in_uploads = false;

					if ($file_path) {
						// Check if file path is within uploads directory
						$should_be_in_uploads = (strpos($file_path, $upload_dir['basedir']) === 0);
					}

					// If file doesn't exist and should be in uploads, delete the attachment
					if ($should_be_in_uploads || ! $file_path) {

						// Delete attachment and all its metadata
						$deleted = wp_delete_attachment($attachment_id, true); // true = force delete (skip trash)

						if ($deleted) {
							$this->write_log('[SWC Batch] ID ' . $attachment_id . ': DELETED (file missing)');
							$deleted_attachments++;
							$current_file_offset++; // Count file
							$current_attachment_offset++; // Move to next attachment

							// Also count thumbnails for deleted attachment
							$metadata = wp_get_attachment_metadata($attachment_id);
							if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
								$current_file_offset += count($metadata['sizes']);
							}

							// Save processed ID
							$this->save_processed_id($attachment_id);

							// Calculate average time (only count images that actually had processing time)
							$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

							// Update progress
							$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
							$this->update_progress(array(
								'status'    => 'running',
								'offset'    => $current_file_offset,
								'attachment_offset' => $current_attachment_offset,
								'total'     => $total,
								'processed' => $this->get_processed_count(),
								'skipped'   => $skipped,
								'errors'    => $errors,
								'thumbnails_processed' => $thumbnails_processed,
								'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
								'average_time_per_image' => $average_time,
								'images_with_timing' => $images_with_timing,
								'deleted_attachments' => $deleted_attachments,
								'progress'  => $progress_percent,
								'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
								'last_update' => current_time('mysql'),
							));
							continue;
						}
					}

					// If deletion failed or file is outside uploads, count as error
					$errors++;
					$current_file_offset++; // Count file
					$current_attachment_offset++; // Move to next attachment

					// Also count thumbnails even if original file doesn't exist
					$metadata = wp_get_attachment_metadata($attachment_id);
					if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
						$current_file_offset += count($metadata['sizes']);
					}

					// Save processed ID (even if error, to avoid reprocessing)
					$this->save_processed_id($attachment_id);

					// Calculate average time (only count images that actually had processing time)
					$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

					// Update progress
					$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
					$this->update_progress(array(
						'status'    => 'running',
						'offset'    => $current_file_offset,
						'attachment_offset' => $current_attachment_offset,
						'total'     => $total,
						'processed' => $this->get_processed_count(),
						'skipped'   => $skipped,
						'errors'    => $errors,
						'thumbnails_processed' => $thumbnails_processed,
						'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
						'average_time_per_image' => $average_time,
						'images_with_timing' => $images_with_timing,
						'deleted_attachments' => $deleted_attachments,
						'progress'  => $progress_percent,
						'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
						'last_update' => current_time('mysql'),
					));
					continue;
				}

				// Skip if file is too large (over 10MB) to avoid timeout
				$file_size = filesize($file_path);
				if ($file_size > 10 * 1024 * 1024) { // 10MB
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SKIPPED (file too large: ' . number_format($file_size / 1024 / 1024, 2) . ' MB)');
					$skipped++;
					$current_file_offset++; // Count original file

					// Also count thumbnails for large file
					$metadata = wp_get_attachment_metadata($attachment_id);
					if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
						$current_file_offset += count($metadata['sizes']);
					}

					// Save processed ID
					$this->save_processed_id($attachment_id);

					$current_attachment_offset++; // Move to next attachment

					// Calculate average time (only count images that actually had processing time)
					$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

					// Update progress
					$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
					$this->update_progress(array(
						'status'    => 'running',
						'offset'    => $current_file_offset,
						'attachment_offset' => $current_attachment_offset,
						'total'     => $total,
						'processed' => $this->get_processed_count(),
						'skipped'   => $skipped,
						'errors'    => $errors,
						'thumbnails_processed' => $thumbnails_processed,
						'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
						'average_time_per_image' => $average_time,
						'images_with_timing' => $images_with_timing,
						'deleted_attachments' => $deleted_attachments,
						'progress'  => $progress_percent,
						'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
						'last_update' => current_time('mysql'),
					));
					continue;
				}

				// Get original file size before conversion
				$original_size = filesize($file_path);

				// Convert to WebP with auto quality adjustment if WebP is larger
				$current_quality = $quality;
				$min_quality = 50; // Minimum quality to avoid too poor quality
				$result = false;
				$is_success = false;
				$processing_time = 0;
				$total_processing_time_for_image = 0;
				$quality_adjusted = false;
				$webp_path = null;
				$webp_size = 0;

				// Try conversion with decreasing quality if WebP is larger than original
				while ($current_quality >= $min_quality) {
					// Delete existing WebP if exists (from previous attempt)
					$temp_webp_path = $converter->get_webp_path($file_path);
					if ($temp_webp_path && file_exists($temp_webp_path)) {
						@unlink($temp_webp_path);
					}

					// Convert to WebP with current quality
					$result = $converter->convert_image_to_webp($file_path, $current_quality, $max_width, $max_height);

					// Check if conversion was successful
					$is_success = false;
					$is_cached = false;
					if (is_array($result) && isset($result['success']) && $result['success']) {
						$is_success = true;
						if (isset($result['processing_time'])) {
							$processing_time = floatval($result['processing_time']);
							$total_processing_time_for_image += $processing_time;
							$is_cached = isset($result['cached']) && $result['cached'] === true;
						}
					} elseif ($result === true) {
						$is_success = true;
					}

					if (! $is_success) {
						break; // Conversion failed, stop trying
					}

					// Get WebP file size after conversion
					$webp_path = $converter->get_attachment_webp_path($attachment_id);
					if ($webp_path && file_exists($webp_path)) {
						$webp_size = filesize($webp_path);
					}

					// If WebP is smaller than original, we're done
					if ($webp_size < $original_size) {
						break;
					}

					// If WebP is larger and we can reduce quality, try again
					if ($current_quality > $min_quality) {
						$quality_adjusted = true;
						$current_quality -= 10; // Reduce quality by 5 each time
						// Log quality adjustment
					} else {
						// Reached minimum quality, stop
						break;
					}
				}

				// Add processing time to total
				if ($total_processing_time_for_image > 0 && ! $is_cached) {
					$total_processing_time += $total_processing_time_for_image;
					$images_with_timing++;
				}

				// Check if WebP is still larger than original after reaching min quality
				if ($is_success && $webp_size >= $original_size && $current_quality <= $min_quality) {
					// WebP is still larger even at minimum quality, skip this file
					// Delete the WebP file we created
					if ($webp_path && file_exists($webp_path)) {
						@unlink($webp_path);
					}
					
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SKIPPED (WebP still larger at min quality ' . $min_quality . ')');
					
					// Save processed ID to skip in future
					$this->save_processed_id($attachment_id);
					
					$skipped++;
					$current_file_offset++; // Count original image as skipped

					// Also count thumbnails for skipped attachment
					$metadata = wp_get_attachment_metadata($attachment_id);
					if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
						$base_dir = dirname($file_path);
						foreach ($metadata['sizes'] as $size_name => $size_data) {
							$thumb_path = $base_dir . '/' . $size_data['file'];
							if (file_exists($thumb_path)) {
								$skipped++;
								$current_file_offset++; // Count thumbnail
							}
						}
					}

					$current_attachment_offset++; // Move to next attachment

					// Calculate average time (only count images that actually had processing time)
					$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

					// Update progress
					$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
					$this->update_progress(array(
						'status'    => 'running',
						'offset'    => $current_file_offset,
						'attachment_offset' => $current_attachment_offset,
						'total'     => $total,
						'processed' => $this->get_processed_count(),
						'skipped'   => $skipped,
						'errors'    => $errors,
						'thumbnails_processed' => $thumbnails_processed,
						'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
						'average_time_per_image' => $average_time,
						'images_with_timing' => $images_with_timing,
						'deleted_attachments' => $deleted_attachments,
						'progress'  => $progress_percent,
						'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
						'last_update' => current_time('mysql'),
					));
					continue; // Skip this attachment
				}

				if ($is_success) {

					// Update metadata
					$metadata = wp_get_attachment_metadata($attachment_id);
					if ($metadata) {
						if ($webp_path) {
							$metadata['swc_webp'] = array(
								'file' => basename($webp_path),
								'path' => $webp_path,
								'url'  => $converter->get_attachment_webp_url($attachment_id),
								'size' => $webp_size,
							);
							wp_update_attachment_metadata($attachment_id, $metadata);
						}
					}

					// Format file sizes
					$original_size_kb = number_format($original_size / 1024, 2);
					$webp_size_kb = number_format($webp_size / 1024, 2);
					
					// Calculate size reduction percentage
					$size_reduction = 0;
					if ($original_size > 0) {
						$size_reduction = round((($original_size - $webp_size) / $original_size) * 100, 1);
					}

					// Log success with processing time and file sizes
					$processing_time_str = '';
					if ($total_processing_time_for_image > 0) {
						$processing_time_str = ' (' . number_format($total_processing_time_for_image, 2) . 's)';
					}
					
					$size_info = ' | ' . $original_size_kb . ' KB → ' . $webp_size_kb . ' KB';
					if ($size_reduction > 0) {
						$size_info .= ' (-' . $size_reduction . '%)';
					} else {
						// WebP is still larger even after quality reduction
						$size_info .= ' (+' . abs($size_reduction) . '%)';
					}
					
					// Add quality info if adjusted
					$quality_info = '';
					if ($quality_adjusted) {
						$quality_info = ' [Quality: ' . $quality . ' → ' . $current_quality . ']';
					}
					
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': SUCCESS' . $processing_time_str . $size_info . $quality_info);

					// Processed count is now tracked in processed-ids.json, no need to increment
					$current_file_offset++; // Count original image

					// Convert thumbnails and count them
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
										$current_file_offset++; // Count thumbnail
									} elseif ($thumb_result === true) {
										$thumbnails_processed++;
										$current_file_offset++; // Count thumbnail
									} else {
										$errors++;
										$current_file_offset++; // Still count as processed (even if failed)
									}
								} else {
									$skipped++;
									$current_file_offset++; // Count skipped thumbnail
								}

								// Calculate average time per image (only count images that actually had processing time)
								$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

								// Update progress after each thumbnail
								$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
								$this->update_progress(array(
									'status'    => 'running',
									'offset'    => $current_file_offset,
									'attachment_offset' => $current_attachment_offset,
									'total'     => $total,
									'processed' => $this->get_processed_count(),
									'skipped'   => $skipped,
									'errors'    => $errors,
									'thumbnails_processed' => $thumbnails_processed,
									'total_processing_time' => $total_processing_time, // Keep full precision
									'average_time_per_image' => $average_time,
									'images_with_timing' => $images_with_timing,
									'deleted_attachments' => $deleted_attachments,
									'progress'  => $progress_percent,
									'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
									'last_update' => current_time('mysql'),
								));

								// Check timeout after each thumbnail
								$elapsed = microtime(true) - $start_time;
								if ($elapsed > $max_execution_time) {
									break 2; // Break out of both loops
								}
							}
						}
					}

					// Save processed ID after successful conversion
					$this->save_processed_id($attachment_id);

					$current_attachment_offset++; // Move to next attachment after processing all files

					// Update progress after original image (if thumbnails weren't processed)
					if (empty($metadata['sizes']) || ! is_array($metadata['sizes'])) {
						// Calculate average time (only count images that actually had processing time)
						$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

						$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
						$this->update_progress(array(
							'status'    => 'running',
							'offset'    => $current_file_offset,
							'attachment_offset' => $current_attachment_offset,
							'total'     => $total,
							'processed' => $this->get_processed_count(),
							'skipped'   => $skipped,
							'errors'    => $errors,
							'thumbnails_processed' => $thumbnails_processed,
							'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
							'average_time_per_image' => $average_time,
							'images_with_timing' => $images_with_timing,
							'deleted_attachments' => $deleted_attachments,
							'progress'  => $progress_percent,
							'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
							'last_update' => current_time('mysql'),
						));
					}
				} else {
					// Log conversion failure
					$error_msg = 'Unknown error';
					if (is_array($result) && isset($result['error'])) {
						$error_msg = $result['error'];
					} elseif (is_object($result) && is_wp_error($result)) {
						$error_msg = $result->get_error_message();
					}
					$this->write_log('[SWC Batch] ID ' . $attachment_id . ': ERROR - ' . $error_msg);

					$errors++;
					$current_file_offset++; // Count failed conversion

					// Also count thumbnails even if conversion failed
					$metadata = wp_get_attachment_metadata($attachment_id);
					if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
						$current_file_offset += count($metadata['sizes']);
					}

					// Save processed ID (even if error, to avoid reprocessing)
					$this->save_processed_id($attachment_id);

					$current_attachment_offset++; // Move to next attachment

					// Calculate average time (only count images that actually had processing time)
					$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;

					// Update progress
					$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
					$this->update_progress(array(
						'status'    => 'running',
						'offset'    => $current_file_offset,
						'attachment_offset' => $current_attachment_offset,
						'total'     => $total,
						'processed' => $this->get_processed_count(),
						'skipped'   => $skipped,
						'errors'    => $errors,
						'thumbnails_processed' => $thumbnails_processed,
						'total_processing_time' => $total_processing_time, // Keep full precision for accurate calculations
						'average_time_per_image' => $average_time,
						'images_with_timing' => $images_with_timing,
						'deleted_attachments' => $deleted_attachments,
						'progress'  => $progress_percent,
						'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
						'last_update' => current_time('mysql'),
					));
				}

				// Clear memory after each image
				if (function_exists('gc_collect_cycles')) {
					gc_collect_cycles();
				}
			}

			// Update progress after processing all attachments in this batch
			$average_time = $images_with_timing > 0 ? round($total_processing_time / $images_with_timing, 3) : 0;
			$progress_percent = $total > 0 ? round(($current_file_offset / $total) * 100, 2) : 0;
			
			// Get updated progress to check status
			$updated_progress = $this->get_progress();
			$is_still_running = $updated_progress && isset($updated_progress['status']) && $updated_progress['status'] === 'running';
			
			$this->update_progress(array(
				'status'    => 'running',
				'offset'    => $current_file_offset,
				'attachment_offset' => $current_attachment_offset,
				'total'     => $total,
				'processed' => $this->get_processed_count(),
				'skipped'   => $skipped,
				'errors'    => $errors,
				'thumbnails_processed' => $thumbnails_processed,
				'total_processing_time' => $total_processing_time,
				'average_time_per_image' => $average_time,
				'images_with_timing' => $images_with_timing,
				'deleted_attachments' => $deleted_attachments,
				'progress'  => $progress_percent,
				'started_at' => isset($progress['started_at']) ? $progress['started_at'] : current_time('mysql'),
				'last_update' => current_time('mysql'),
			));

			// Ensure cron event is scheduled for next run if batch is still running
			if ($is_still_running) {
				$next_scheduled = wp_next_scheduled(self::CRON_HOOK);
				$current_time = time();
				
				if (! $next_scheduled) {
					// Reschedule if not scheduled
					wp_schedule_event($current_time + 30, 'swc_batch_interval', self::CRON_HOOK);
				} else {
					// Check if scheduled time is in the past or too soon (less than 10 seconds)
					$time_until_next = $next_scheduled - $current_time;
					
					if ($time_until_next < 10) {
						// Reschedule if too soon or in the past
						wp_unschedule_event($next_scheduled, self::CRON_HOOK);
						wp_schedule_event($current_time + 30, 'swc_batch_interval', self::CRON_HOOK);
					}
				}
			}
		} finally {
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
	 * Update progress data
	 *
	 * @param array $data Progress data
	 */
	private function update_progress($data)
	{
		// Ensure timing stats are included
		if (! isset($data['total_processing_time'])) {
			$existing = $this->get_progress();
			if ($existing && isset($existing['total_processing_time'])) {
				$data['total_processing_time'] = $existing['total_processing_time'];
			} else {
				$data['total_processing_time'] = 0;
			}
		}

		// Ensure images_with_timing is included
		if (! isset($data['images_with_timing'])) {
			$existing = $this->get_progress();
			if ($existing && isset($existing['images_with_timing'])) {
				$data['images_with_timing'] = $existing['images_with_timing'];
			} else {
				$data['images_with_timing'] = 0;
			}
		}

		if (! isset($data['average_time_per_image'])) {
			$images_with_timing = isset($data['images_with_timing']) ? intval($data['images_with_timing']) : 0;
			$total_time = isset($data['total_processing_time']) ? floatval($data['total_processing_time']) : 0;
			$data['average_time_per_image'] = $images_with_timing > 0 ? round($total_time / $images_with_timing, 3) : 0;
		}

		// Store for 24 hours
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
			wp_send_json_error(array('message' => __('Permission denied', 'smart-webp-converter')));
		}

		$result = $this->start_batch();

		if ($result) {
			wp_send_json_success(array('message' => __('Batch processing started', 'smart-webp-converter')));
		} else {
			wp_send_json_error(array('message' => __('Batch processing is already running', 'smart-webp-converter')));
		}
	}

	/**
	 * AJAX handler to stop batch
	 */
	public function ajax_stop_batch()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'smart-webp-converter')));
		}

		$result = $this->stop_batch();

		if ($result) {
			wp_send_json_success(array('message' => __('Batch processing stopped', 'smart-webp-converter')));
		} else {
			wp_send_json_error(array('message' => __('Failed to stop batch processing', 'smart-webp-converter')));
		}
	}

	/**
	 * AJAX handler to get batch progress
	 */
	public function ajax_get_batch_progress()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'smart-webp-converter')));
		}

		$progress = $this->get_progress();

		if (! $progress) {
			wp_send_json_success(array(
				'status' => 'idle',
				'message' => __('No batch processing in progress', 'smart-webp-converter'),
			));
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
			wp_send_json_error(array('message' => __('Permission denied', 'smart-webp-converter')));
		}

		$total = $this->get_total_images();

		wp_send_json_success(array(
			'total' => $total,
		));
	}

	/**
	 * AJAX handler to trigger batch processing (fallback when cron doesn't run)
	 */
	public function ajax_trigger_batch()
	{
		check_ajax_referer('swc_batch_process', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied', 'smart-webp-converter')));
		}

		$progress = $this->get_progress();

		// Only trigger if batch is running
		if (! $progress || ! isset($progress['status']) || $progress['status'] !== 'running') {
			wp_send_json_success(array('message' => __('Batch is not running', 'smart-webp-converter')));
		}

		// Check lock to prevent concurrent execution
		$lock = get_transient(self::LOCK_TRANSIENT);
		if ($lock && (time() - $lock) < 300) {
			// Another batch is running, skip
			wp_send_json_success(array('message' => __('Batch is already processing', 'smart-webp-converter')));
		}

		// Ensure cron is scheduled and valid
		$current_time = time();
		$next_scheduled = wp_next_scheduled(self::CRON_HOOK);
		
		if (! $next_scheduled) {
			// Not scheduled, schedule it
			wp_schedule_event($current_time + 30, 'swc_batch_interval', self::CRON_HOOK);
		} else {
			// Check if scheduled time is in the past or too soon
			$time_until_next = $next_scheduled - $current_time;
			if ($time_until_next < 10) {
				// Reschedule if in the past or too soon
				wp_unschedule_event($next_scheduled, self::CRON_HOOK);
				wp_schedule_event($current_time + 30, 'swc_batch_interval', self::CRON_HOOK);
			}
		}

		// Trigger batch processing
		$this->process_batch_cron();

		wp_send_json_success(array('message' => __('Batch processing triggered', 'smart-webp-converter')));
	}
}
