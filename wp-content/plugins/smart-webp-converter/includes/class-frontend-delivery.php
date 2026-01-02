<?php

/**
 * Frontend Delivery Class
 * 
 * Handles serving WebP images to browsers that support it
 *
 * @package Smart_WebP_Converter
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

class SWC_Frontend_Delivery
{

	/**
	 * Instance of this class
	 *
	 * @var SWC_Frontend_Delivery
	 */
	private static $instance = null;

	/**
	 * Get instance of this class
	 *
	 * @return SWC_Frontend_Delivery
	 */
	public static function get_instance()
	{
		if (null === self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$options = get_option('swc_options', []);

		if (! empty($options['serve_webp'])) {
			// Hook into image URL generation
			add_filter('wp_get_attachment_image_src', [$this, 'replace_image_src_with_webp'], 10, 3);
			add_filter('wp_get_attachment_image_url', [$this, 'replace_attachment_image_url_with_webp'], 10, 2);
			add_filter('wp_get_attachment_url', [$this, 'replace_attachment_url_with_webp'], 10, 2);
			add_filter('post_thumbnail_url', [$this, 'replace_post_thumbnail_url_with_webp'], 10, 3);
			add_filter('the_content', [$this, 'replace_content_images_with_webp'], 99);
			add_filter('post_thumbnail_html', [$this, 'replace_image_html_with_webp'], 10, 5);
			
			// Use output buffering to catch all HTML output
			add_action('template_redirect', [$this, 'start_output_buffer'], 1);
		}
	}

	/**
	 * Check if browser supports WebP
	 *
	 * @return bool True if browser supports WebP
	 */
	private function browser_supports_webp()
	{
		if (! isset($_SERVER['HTTP_ACCEPT'])) return false;

		$accept = $_SERVER['HTTP_ACCEPT'];
		return strpos($accept, 'image/webp') !== false;
	}

	/**
	 * Replace image src with WebP version
	 *
	 * @param array|false  $image Array with image data or false
	 * @param int          $attachment_id Attachment ID
	 * @param string|array $size Image size
	 * @return array|false Modified image array or false
	 */
	public function replace_image_src_with_webp($image, $attachment_id, $size)
	{
		if (! $image || ! $this->browser_supports_webp()) return $image;

		// Check if original image is already WebP
		$file_path = get_attached_file($attachment_id);
		if ($file_path && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $image;

		$converter = SWC_WebP_Converter::get_instance();
		$webp_url = $converter->get_attachment_webp_url($attachment_id);

		if ($webp_url) {
			// For specific named sizes (string), check if WebP version exists
			// Only process string sizes, not arrays (which represent custom dimensions)
			if (is_string($size) && $size !== 'full') {
				$metadata = wp_get_attachment_metadata($attachment_id);

				if ($metadata && isset($metadata['sizes'][$size]) && ! empty($metadata['sizes'][$size])) {
					$file_path = get_attached_file($attachment_id);

					if ($file_path) {
						$base_dir = dirname($file_path);
						$size_file = $base_dir . '/' . $metadata['sizes'][$size]['file'];
						$size_webp_path = $converter->get_webp_path($size_file);

						if (file_exists($size_webp_path)) {
							$upload_dir = wp_upload_dir();
							$webp_relative = str_replace($upload_dir['basedir'], '', $size_webp_path);
							$webp_url = $upload_dir['baseurl'] . $webp_relative;
						}
						else {
							// WebP file doesn't exist, keep original image
							return $image;
						}
					}
				}
			}
			else {
				// For full size or array sizes (custom dimensions), check if WebP file exists
				$file_path = get_attached_file($attachment_id);

				if ($file_path) {
					$webp_path = $converter->get_webp_path($file_path);
					if (! file_exists($webp_path)) return $image;
				}
			}

			$image[0] = $webp_url;
		}

		return $image;
	}

	/**
	 * Replace images in content with WebP versions
	 *
	 * @param string $content Post content
	 * @return string Modified content
	 */
	public function replace_content_images_with_webp($content)
	{
		if (! $this->browser_supports_webp()) return $content;

		// Match img tags
		preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

		if (empty($matches[1])) return $content;

		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];

		foreach ($matches[1] as $index => $image_url) {
			// Check if image is from uploads directory
			if (strpos($image_url, $base_url) === false) continue;

			// Get attachment ID from URL
			$attachment_id = attachment_url_to_postid($image_url);
			if (! $attachment_id) continue;

			// Check if original image is already WebP
			$file_path = get_attached_file($attachment_id);
			if ($file_path && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') continue;

			// Get WebP URL and check if file exists
			$converter = SWC_WebP_Converter::get_instance();
			$webp_url = $converter->get_attachment_webp_url($attachment_id);

			if ($webp_url) {
				// Check if WebP file actually exists on server
				$file_path = get_attached_file($attachment_id);

				if ($file_path) {
					$webp_path = $converter->get_webp_path($file_path);
					if (file_exists($webp_path)) $content = str_replace($image_url, $webp_url, $content);
				}
			}
		}

		return $content;
	}

	/**
	 * Replace image HTML with WebP version
	 *
	 * @param string       $html Image HTML
	 * @param int          $post_id Post ID
	 * @param int          $post_thumbnail_id Thumbnail ID
	 * @param string|array $size Image size
	 * @param string       $attr Image attributes
	 * @return string Modified HTML
	 */
	public function replace_image_html_with_webp($html, $post_id, $post_thumbnail_id, $size, $attr)
	{
		if (! $html || ! $this->browser_supports_webp()) return $html;

		// Check if original image is already WebP
		$file_path = get_attached_file($post_thumbnail_id);
		if ($file_path && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $html;

		$converter = SWC_WebP_Converter::get_instance();
		$webp_url = $converter->get_attachment_webp_url($post_thumbnail_id);

		if ($webp_url) {
			// Check if WebP file actually exists on server
			$file_path = get_attached_file($post_thumbnail_id);

			if ($file_path) {
				$webp_path = $converter->get_webp_path($file_path);

				if (file_exists($webp_path)) {
					// Replace src attribute only if WebP file exists
					$html = preg_replace('/src=["\']([^"\']+)["\']/i', 'src="' . esc_url($webp_url) . '"', $html);

					// Replace srcset if exists
					if (preg_match('/srcset=["\']([^"\']+)["\']/i', $html, $srcset_match)) {
						// For now, just replace the main src. Full srcset replacement would require more complex logic
					}
				}
			}
		}

		return $html;
	}

	/**
	 * Replace attachment image URL with WebP version
	 * This filter catches wp_get_attachment_image_url()
	 *
	 * @param string|false $url Image URL or false
	 * @param int          $attachment_id Attachment ID
	 * @return string|false Modified URL or false
	 */
	public function replace_attachment_image_url_with_webp($url, $attachment_id)
	{
		if (! $url || ! $this->browser_supports_webp()) return $url;

		// Check if original image is already WebP
		$file_path = get_attached_file($attachment_id);
		if ($file_path && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $url;

		$converter = SWC_WebP_Converter::get_instance();
		$webp_url = $converter->get_attachment_webp_url($attachment_id);

		if ($webp_url) {
			// Check if WebP file actually exists on server
			$file_path = get_attached_file($attachment_id);

			if ($file_path) {
				$webp_path = $converter->get_webp_path($file_path);
				if (file_exists($webp_path)) return $webp_url;
			}
		}

		return $url;
	}

	/**
	 * Replace attachment URL with WebP version
	 * This filter catches wp_get_attachment_url()
	 *
	 * @param string|false $url Attachment URL or false
	 * @param int          $attachment_id Attachment ID
	 * @return string|false Modified URL or false
	 */
	public function replace_attachment_url_with_webp($url, $attachment_id)
	{
		if (! $url || ! $this->browser_supports_webp()) return $url;

		// Only process image attachments
		$post = get_post($attachment_id);
		if (! $post || $post->post_type !== 'attachment') return $url;

		$mime_type = get_post_mime_type($attachment_id);
		if (! $mime_type || strpos($mime_type, 'image/') !== 0) return $url;

		// Check if original image is already WebP
		$file_path = get_attached_file($attachment_id);
		if ($file_path && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $url;

		$converter = SWC_WebP_Converter::get_instance();
		$webp_url = $converter->get_attachment_webp_url($attachment_id);

		if ($webp_url) {
			// Check if WebP file actually exists on server
			$file_path = get_attached_file($attachment_id);

			if ($file_path) {
				$webp_path = $converter->get_webp_path($file_path);
				if (file_exists($webp_path)) return $webp_url;
			}
		}

		return $url;
	}

	/**
	 * Replace post thumbnail URL with WebP version
	 * This filter catches get_the_post_thumbnail_url()
	 *
	 * @param string|false $thumbnail_url Post thumbnail URL or false
	 * @param int|WP_Post|null $post Post ID or WP_Post object
	 * @param string|int[] $size Image size
	 * @return string|false Modified URL or false
	 */
	public function replace_post_thumbnail_url_with_webp($thumbnail_url, $post, $size)
	{
		if (! $thumbnail_url || ! $this->browser_supports_webp()) return $thumbnail_url;

		// Get post ID
		$post_id = is_object($post) ? $post->ID : (int) $post;
		if (! $post_id) return $thumbnail_url;

		// Get thumbnail attachment ID
		$attachment_id = get_post_thumbnail_id($post_id);
		if (! $attachment_id) return $thumbnail_url;

		// Check if original image is already WebP
		$file_path = get_attached_file($attachment_id);
		if ($file_path && strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $thumbnail_url;

		$converter = SWC_WebP_Converter::get_instance();
		
		// For specific sizes, check if WebP version exists
		if (is_array($size) || (is_string($size) && $size !== 'full')) {
			$metadata = wp_get_attachment_metadata($attachment_id);

			if ($metadata && ! empty($metadata['sizes'][$size])) {
				$file_path = get_attached_file($attachment_id);

				if ($file_path) {
					$base_dir = dirname($file_path);
					$size_file = $base_dir . '/' . $metadata['sizes'][$size]['file'];
					$size_webp_path = $converter->get_webp_path($size_file);

					if (file_exists($size_webp_path)) {
						$upload_dir = wp_upload_dir();
						$webp_relative = str_replace($upload_dir['basedir'], '', $size_webp_path);
						$webp_relative = str_replace(DIRECTORY_SEPARATOR, '/', $webp_relative);
						return $upload_dir['baseurl'] . $webp_relative;
					}
				}
			}
		}

		// For full size or if size-specific WebP doesn't exist, use full size WebP
		$webp_url = $converter->get_attachment_webp_url($attachment_id);

		if ($webp_url) {
			// Check if WebP file actually exists on server
			$file_path = get_attached_file($attachment_id);

			if ($file_path) {
				$webp_path = $converter->get_webp_path($file_path);
				if (file_exists($webp_path)) return $webp_url;
			}
		}

		return $thumbnail_url;
	}

	/**
	 * Start output buffering to catch all HTML
	 */
	public function start_output_buffer()
	{
		// Only buffer HTML pages, not AJAX, REST API, or admin
		if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;

		ob_start([$this, 'replace_html_images_with_webp']);
	}

	/**
	 * Replace all image URLs in HTML output with WebP versions
	 *
	 * @param string $buffer HTML buffer
	 * @return string Modified HTML
	 */
	public function replace_html_images_with_webp($buffer)
	{
		if (! $this->browser_supports_webp()) return $buffer;

		// Only process HTML content
		if (stripos($buffer, '<html') === false && stripos($buffer, '<!DOCTYPE') === false) return $buffer;

		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_dir = $upload_dir['basedir'];

		// Match all img tags with src attributes (including data-src, data-lazy-src, etc.)
		$buffer = preg_replace_callback(
			'/<img([^>]+)(src|data-src|data-lazy-src|data-original)=["\']([^"\']+)["\']([^>]*)>/i',
			function($matches) use ($base_url, $base_dir) {
				$before_attr = $matches[1];
				$attr_name = $matches[2];
				$image_url = $matches[3];
				$after_attr = $matches[4];

				// Check if image is from uploads directory
				if (strpos($image_url, $base_url) === false) return $matches[0];

				// Convert URL to file path
				$relative_path = str_replace($base_url, '', $image_url);
				// Remove query string if exists
				$relative_path = preg_replace('/\?.*$/', '', $relative_path);
				$file_path = $base_dir . $relative_path;

				// Normalize path separators
				$file_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);

				// Check if file exists
				if (! file_exists($file_path)) return $matches[0];
				// Check if original image is already WebP
				if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $matches[0];

				// Get WebP path
				$converter = SWC_WebP_Converter::get_instance();
				$webp_path = $converter->get_webp_path($file_path);

				if (file_exists($webp_path)) {
					$webp_relative = str_replace($base_dir, '', $webp_path);
					$webp_relative = str_replace(DIRECTORY_SEPARATOR, '/', $webp_relative);
					$webp_url = $base_url . $webp_relative;
					return '<img' . $before_attr . $attr_name . '="' . esc_url($webp_url) . '"' . $after_attr . '>';
				}

				return $matches[0];
			},
			$buffer
		);

		// Also replace URLs in style attributes (background-image, etc.)
		$buffer = preg_replace_callback(
			'/url\s*\(\s*(["\']?)(.*?)\1\s*\)/i',
			function($matches) use ($base_url, $base_dir) {
				$quote = $matches[1]; // Giữ nguyên dấu nháy ban đầu (nếu có)
				$image_url = trim($matches[2]);

				// Skip data URIs
				if (strpos($image_url, 'data:') === 0) return $matches[0];

				// Decode HTML entities (e.g., &quot;)
				$image_url = html_entity_decode($image_url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$image_url = trim($image_url, ' "\'');

				// Skip empty URLs
				if (empty($image_url)) return $matches[0];

				// Check if image is from uploads directory
				if (strpos($image_url, $base_url) === false) return $matches[0];

				// Convert URL to file path
				$relative_path = str_replace($base_url, '', $image_url);
				// Remove query string if exists
				$relative_path = preg_replace('/\?.*$/', '', $relative_path);
				$file_path = $base_dir . $relative_path;

				// Normalize path separators
				$file_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file_path);

				// Check if file exists
				if (! file_exists($file_path)) return $matches[0];
				// Check if original image is already WebP
				if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'webp') return $matches[0];

				// Get WebP path
				$converter = SWC_WebP_Converter::get_instance();
				$webp_path = $converter->get_webp_path($file_path);

				if (file_exists($webp_path)) {
					$webp_relative = str_replace($base_dir, '', $webp_path);
					$webp_relative = str_replace(DIRECTORY_SEPARATOR, '/', $webp_relative);
					$webp_url = $base_url . $webp_relative;
					
					// Giữ nguyên format ban đầu: không có dấu nháy, dấu nháy đơn, hoặc dấu nháy kép
					if (empty($quote)) return 'url(' . esc_url($webp_url) . ')';
					else return 'url(' . $quote . esc_url($webp_url) . $quote . ')';
				}

				return $matches[0];
			},
			$buffer
		);

		return $buffer;
	}

	/**
	 * Get WebP path helper (expose method from converter)
	 *
	 * @param string $file_path Original file path
	 * @return string WebP file path
	 */
	private function get_webp_path($file_path)
	{
		$converter = SWC_WebP_Converter::get_instance();
		return $converter->get_webp_path($file_path);
	}
}
