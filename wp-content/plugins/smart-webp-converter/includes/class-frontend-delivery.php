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
		$options = get_option('swc_options', array());

		if (! empty($options['serve_webp'])) {
			// Hook into image URL generation
			add_filter('wp_get_attachment_image_src', array($this, 'replace_image_src_with_webp'), 10, 3);
			add_filter('the_content', array($this, 'replace_content_images_with_webp'), 99);
			add_filter('post_thumbnail_html', array($this, 'replace_image_html_with_webp'), 10, 5);
		}
	}

	/**
	 * Check if browser supports WebP
	 *
	 * @return bool True if browser supports WebP
	 */
	private function browser_supports_webp()
	{
		if (! isset($_SERVER['HTTP_ACCEPT'])) {
			return false;
		}

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
		if (! $image || ! $this->browser_supports_webp()) {
			return $image;
		}

		$converter = SWC_WebP_Converter::get_instance();
		$webp_url = $converter->get_attachment_webp_url($attachment_id);

		if ($webp_url) {
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
							$webp_url = $upload_dir['baseurl'] . $webp_relative;
						} else {
							// WebP file doesn't exist, keep original image
							return $image;
						}
					}
				}
			} else {
				// For full size, check if WebP file exists
				$file_path = get_attached_file($attachment_id);
				if ($file_path) {
					$webp_path = $converter->get_webp_path($file_path);
					if (! file_exists($webp_path)) {
						// WebP file doesn't exist, keep original image
						return $image;
					}
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
		if (! $this->browser_supports_webp()) {
			return $content;
		}

		// Match img tags
		preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

		if (empty($matches[1])) {
			return $content;
		}

		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];

		foreach ($matches[1] as $index => $image_url) {
			// Check if image is from uploads directory
			if (strpos($image_url, $base_url) === false) {
				continue;
			}

			// Get attachment ID from URL
			$attachment_id = attachment_url_to_postid($image_url);
			if (! $attachment_id) {
				continue;
			}

			// Get WebP URL and check if file exists
			$converter = SWC_WebP_Converter::get_instance();
			$webp_url = $converter->get_attachment_webp_url($attachment_id);

			if ($webp_url) {
				// Check if WebP file actually exists on server
				$file_path = get_attached_file($attachment_id);
				if ($file_path) {
					$webp_path = $converter->get_webp_path($file_path);
					if (file_exists($webp_path)) {
						// Replace in content only if WebP file exists
						$content = str_replace($image_url, $webp_url, $content);
					}
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
		if (! $html || ! $this->browser_supports_webp()) {
			return $html;
		}

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
