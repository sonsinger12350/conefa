<?php

/**
 * WebP Converter Class
 * 
 * Handles image conversion to WebP format
 *
 * @package Smart_WebP_Converter
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

class SWC_WebP_Converter
{

	/**
	 * Instance of this class
	 *
	 * @var SWC_WebP_Converter
	 */
	private static $instance = null;

	/**
	 * Get instance of this class
	 *
	 * @return SWC_WebP_Converter
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
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks()
	{
		$options = get_option('swc_options', array());

		if (! empty($options['auto_convert'])) {
			// Hook into image upload process
			add_filter('wp_generate_attachment_metadata', array($this, 'convert_attachment_to_webp'), 10, 2);
		}
	}

	/**
	 * Check if WebP conversion is supported
	 *
	 * @return bool|string True if supported, error message if not
	 */
	public function is_webp_supported()
	{
		// Check GD library with WebP support
		if (function_exists('imagewebp')) {
			return true;
		}

		// Check ImageMagick with WebP support
		if (extension_loaded('imagick') && class_exists('Imagick')) {
			$imagick = new Imagick();
			$formats = $imagick->queryFormats();
			if (in_array('WEBP', $formats)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert attachment to WebP format
	 *
	 * @param array $metadata Attachment metadata
	 * @param int   $attachment_id Attachment ID
	 * @return array Modified metadata
	 */
	public function convert_attachment_to_webp($metadata, $attachment_id)
	{
		// Skip if already processing
		if (get_post_meta($attachment_id, '_swc_processing', true)) {
			return $metadata;
		}

		// Check if WebP is supported
		if (! $this->is_webp_supported()) {
			return $metadata;
		}

		// Get attachment file path
		$file_path = get_attached_file($attachment_id);
		if (! $file_path || ! file_exists($file_path)) {
			return $metadata;
		}

		// Check if file is an image
		$mime_type = get_post_mime_type($attachment_id);
		if (! $this->is_supported_image_type($mime_type)) {
			return $metadata;
		}

		// Mark as processing
		update_post_meta($attachment_id, '_swc_processing', true);

		// Get options
		$options = get_option('swc_options', array());
		$quality = isset($options['webp_quality']) ? intval($options['webp_quality']) : 82;
		$max_width = isset($options['max_width']) ? intval($options['max_width']) : 2560;
		$max_height = isset($options['max_height']) ? intval($options['max_height']) : 2560;
		$delete_original = isset($options['delete_original']) ? (bool) $options['delete_original'] : false;

		// Convert original image
		$result = $this->convert_image_to_webp($file_path, $quality, $max_width, $max_height);

		// Handle both array and bool return values
		$is_success = false;
		if (is_array($result) && isset($result['success']) && $result['success']) {
			$is_success = true;
		} elseif ($result === true) {
			$is_success = true;
		}

		if ($is_success) {
			// Store WebP file info in metadata
			$webp_path = $this->get_webp_path($file_path);
			if (file_exists($webp_path)) {
				$metadata['swc_webp'] = array(
					'file' => basename($webp_path),
					'path' => $webp_path,
					'url'  => $this->get_webp_url($file_path),
					'size' => filesize($webp_path),
				);

				// Delete original if option is enabled and WebP is smaller
				if ($delete_original) {
					$original_size = filesize($file_path);
					$webp_size = filesize($webp_path);
					if ($webp_size < $original_size) {
						@unlink($file_path);
						// Update attachment file path to WebP
						update_attached_file($attachment_id, $webp_path);
					}
				}
			}

			// Convert all thumbnail sizes
			if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
				$base_dir = dirname($file_path);

				foreach ($metadata['sizes'] as $size_name => $size_data) {
					$thumb_path = $base_dir . '/' . $size_data['file'];
					if (file_exists($thumb_path)) {
						$this->convert_image_to_webp($thumb_path, $quality, null, null);
					}
				}
			}
		}

		// Remove processing flag
		delete_post_meta($attachment_id, '_swc_processing');

		return $metadata;
	}

	/**
	 * Convert single image file to WebP
	 *
	 * @param string $file_path Path to image file
	 * @param int    $quality WebP quality (0-100)
	 * @param int    $max_width Maximum width (null to keep original)
	 * @param int    $max_height Maximum height (null to keep original)
	 * @return bool|array True on success, false on failure. If array, contains 'success' and 'processing_time' keys
	 */
	public function convert_image_to_webp($file_path, $quality = 82, $max_width = null, $max_height = null)
	{
		$start_time = microtime(true);

		if (! file_exists($file_path)) {
			return false;
		}

		// Check if WebP already exists
		$webp_path = $this->get_webp_path($file_path);
		if (file_exists($webp_path)) {
			$processing_time = microtime(true) - $start_time;
			return array('success' => true, 'processing_time' => $processing_time, 'cached' => true);
		}

		// Skip if file is too large
		$file_size = filesize($file_path);
		if ($file_size > 10 * 1024 * 1024) { // 10MB
			return false;
		}

		// Get image editor
		$editor = wp_get_image_editor($file_path);
		if (is_wp_error($editor)) {
			return false;
		}

		// Set memory limit for large images
		@ini_set('memory_limit', '256M');

		// Resize if needed
		if ($max_width || $max_height) {
			$size = $editor->get_size();
			$width = $size['width'];
			$height = $size['height'];

			$needs_resize = false;
			$new_width = $width;
			$new_height = $height;

			if ($max_width && $width > $max_width) {
				$needs_resize = true;
				$ratio = $max_width / $width;
				$new_width = $max_width;
				$new_height = round($height * $ratio);
			}

			if ($max_height && $new_height > $max_height) {
				$needs_resize = true;
				$ratio = $max_height / $new_height;
				$new_width = round($new_width * $ratio);
				$new_height = $max_height;
			}

			if ($needs_resize) {
				$editor->resize($new_width, $new_height, false);
			}
		}

		// Set quality
		$editor->set_quality($quality);

		// Save as WebP
		$saved = $editor->save($webp_path, 'image/webp');

		$processing_time = microtime(true) - $start_time;

		if (is_wp_error($saved)) {
			return false;
		}

		return array('success' => true, 'processing_time' => $processing_time, 'file_size' => $file_size);
	}

	/**
	 * Get WebP file path from original file path
	 *
	 * @param string $file_path Original file path
	 * @return string WebP file path
	 */
	public function get_webp_path($file_path)
	{
		$path_info = pathinfo($file_path);
		return $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
	}

	/**
	 * Get WebP file URL from original file path
	 *
	 * @param string $file_path Original file path
	 * @return string WebP file URL
	 */
	private function get_webp_url($file_path)
	{
		$upload_dir = wp_upload_dir();
		$relative_path = str_replace($upload_dir['basedir'], '', $file_path);
		$webp_path = $this->get_webp_path($file_path);
		$webp_relative = str_replace($upload_dir['basedir'], '', $webp_path);
		return $upload_dir['baseurl'] . $webp_relative;
	}

	/**
	 * Check if image type is supported for conversion
	 *
	 * @param string $mime_type MIME type
	 * @return bool True if supported
	 */
	private function is_supported_image_type($mime_type)
	{
		$supported_types = array(
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
		);

		return in_array($mime_type, $supported_types, true);
	}

	/**
	 * Get WebP file path for an attachment
	 *
	 * @param int $attachment_id Attachment ID
	 * @return string|false WebP file path or false if not found
	 */
	public function get_attachment_webp_path($attachment_id)
	{
		$file_path = get_attached_file($attachment_id);
		if (! $file_path) {
			return false;
		}

		$webp_path = $this->get_webp_path($file_path);
		return file_exists($webp_path) ? $webp_path : false;
	}

	/**
	 * Get WebP file URL for an attachment
	 *
	 * @param int $attachment_id Attachment ID
	 * @return string|false WebP file URL or false if not found
	 */
	public function get_attachment_webp_url($attachment_id)
	{
		$file_path = get_attached_file($attachment_id);
		if (! $file_path) {
			return false;
		}

		return $this->get_webp_url($file_path);
	}
}
