<?php
/**
 * Plugin Name: Template Porter for Elementor
 * Description: Export and import Elementor templates WITH images bundled. Exports template JSON + images into a ZIP. On import, sideloads images and updates template JSON with new attachment IDs so images work immediately in Elementor editor without reselection.
 * Version: 1.0.0
 * Author: MrMoazR
 * Author URI: https://github.com/MrMoazR
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: template-porter-for-elementor
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Elementor is installed and active
function temppofo_check_elementor_dependency() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'temppofo_elementor_missing_notice' );
		return false;
	}
	return true;
}

// Display admin notice if Elementor is not active
function temppofo_elementor_missing_notice() {
	$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
		esc_html__( '%1$s requires %2$s to be installed and activated.', 'template-porter-for-elementor' ),
		'<strong>' . esc_html__( 'Template Porter for Elementor', 'template-porter-for-elementor' ) . '</strong>',
		'<strong>' . esc_html__( 'Elementor', 'template-porter-for-elementor' ) . '</strong>'
	);

	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

define( 'TEMPPOFO_VERSION', '1.0.0' );
define( 'TEMPPOFO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TEMPPOFO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class TEMPPOFO_Template_Porter {

	public function __construct() {
		// Admin menu
		add_action( 'admin_menu', [ $this, 'add_menu' ] );

		// AJAX handlers
		add_action( 'wp_ajax_temppofo_export_template', [ $this, 'ajax_export_template' ] );
		add_action( 'wp_ajax_temppofo_import_template', [ $this, 'ajax_import_template' ] );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function add_menu() {
		add_menu_page(
			__( 'Template Porter', 'template-porter-for-elementor' ),
			__( 'Template Porter', 'template-porter-for-elementor' ),
			'manage_options',
			'template-porter-for-elementor',
			[ $this, 'admin_page' ],
			'dashicons-download',
			58
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'toplevel_page_template-porter-for-elementor' ) {
			return;
		}
		wp_enqueue_style( 'temppofo-admin', TEMPPOFO_PLUGIN_URL . 'assets/admin.css', [], TEMPPOFO_VERSION );
		wp_enqueue_script( 'temppofo-admin', TEMPPOFO_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], TEMPPOFO_VERSION, true );
		wp_localize_script( 'temppofo-admin', 'temppofoData', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'temppofo_nonce' ),
		] );
	}

	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'template-porter-for-elementor' ) );
		}

		// Get all Elementor templates
		$templates = get_posts( [
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		include TEMPPOFO_PLUGIN_DIR . 'views/admin-page.php';
	}

	// ========== EXPORT ==========
	public function ajax_export_template() {
		check_ajax_referer( 'temppofo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'template-porter-for-elementor' ) ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => __( 'No template selected', 'template-porter-for-elementor' ) ] );
		}

		// Verify the template exists and is an Elementor template
		$post = get_post( $template_id );
		if ( ! $post || 'elementor_library' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Invalid template ID', 'template-porter-for-elementor' ) ] );
		}

		$result = $this->export_template_with_images( $template_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'download_url' => $result ] );
	}

	protected function export_template_with_images( $template_id ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$post = get_post( $template_id );
		if ( ! $post || $post->post_type !== 'elementor_library' ) {
			return new WP_Error( 'invalid_template', 'Invalid template ID' );
		}

		// Get ALL Elementor meta data
		$elementor_data = get_post_meta( $template_id, '_elementor_data', true );
		$template_type = get_post_meta( $template_id, '_elementor_template_type', true );
		$page_settings = get_post_meta( $template_id, '_elementor_page_settings', true );
		$edit_mode = get_post_meta( $template_id, '_elementor_edit_mode', true );
		$version = get_post_meta( $template_id, '_elementor_version', true );
		
		if ( empty( $elementor_data ) ) {
			return new WP_Error( 'no_data', 'Template has no Elementor data' );
		}

		// Parse data
		if ( is_string( $elementor_data ) ) {
			$data = json_decode( $elementor_data, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_Error( 'invalid_json', 'Template data is not valid JSON' );
			}
		} else {
			$data = $elementor_data; // Already an array
		}

		// Extract image attachment IDs from data
		$image_ids = $this->extract_image_ids( $data );

		// Prepare export package
		$export_data = [
			'version'          => TEMPPOFO_VERSION,
			'elementor_version' => $version ?: ELEMENTOR_VERSION,
			'template_title'   => $post->post_title,
			'template_type'    => $template_type,
			'template_content' => $data, // Store as array, not JSON string
			'page_settings'    => $page_settings,
			'edit_mode'        => $edit_mode,
			'images'           => [],
		];

		// Build image map: attachment_id => filename
		$image_files = [];
		foreach ( $image_ids as $att_id ) {
			$file = get_attached_file( $att_id );
			if ( $file && file_exists( $file ) ) {
				$filename = basename( $file );
				$image_files[ $att_id ] = [
					'file'     => $file,
					'filename' => $filename,
					'url'      => wp_get_attachment_url( $att_id ),
				];
				$export_data['images'][ $att_id ] = $filename;
			}
		}

		// Create temporary directory for export
		$upload_dir = wp_upload_dir();
		$temp_dir   = trailingslashit( $upload_dir['basedir'] ) . 'temppofo-temp-' . time();
		wp_mkdir_p( $temp_dir );

		// Write template.json
		file_put_contents( $temp_dir . '/template.json', wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

		// Copy images into temp/images/
		$images_dir = $temp_dir . '/images';
		wp_mkdir_p( $images_dir );
		foreach ( $image_files as $att_id => $info ) {
			copy( $info['file'], $images_dir . '/' . $info['filename'] );
		}

		// Create ZIP
		$zip_filename = sanitize_file_name( $post->post_title ) . '-' . time() . '.zip';
		$zip_path     = trailingslashit( $upload_dir['basedir'] ) . $zip_filename;

		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'no_zip', 'ZipArchive PHP extension not available' );
		}

		$zip = new ZipArchive();
		if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'zip_error', 'Could not create ZIP file' );
		}

		// Add files to ZIP
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $temp_dir ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $file ) {
			if ( ! $file->isDir() ) {
				$file_path     = $file->getRealPath();
				$relative_path = substr( $file_path, strlen( $temp_dir ) + 1 );
				$zip->addFile( $file_path, $relative_path );
			}
		}

		$zip->close();

		// Cleanup temp directory
		$this->recursive_delete( $temp_dir );

		// Return download URL
		return trailingslashit( $upload_dir['baseurl'] ) . $zip_filename;
	}

	protected function extract_image_ids( $data, &$ids = [] ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				// Look for common Elementor image keys
				if ( $key === 'id' && is_numeric( $value ) && $value > 0 ) {
					// Check if sibling key suggests it's an image
					$parent_keys = array_keys( $data );
					$is_image = in_array( 'url', $parent_keys, true ) || 
					            in_array( 'image', $parent_keys, true ) ||
					            ( isset( $data['url'] ) && is_string( $data['url'] ) && strpos( $data['url'], 'wp-content/uploads/' ) !== false );
					if ( $is_image ) {
						$ids[] = intval( $value );
					}
				}
				$this->extract_image_ids( $value, $ids );
			}
		}
		return array_unique( $ids );
	}

	// ========== IMPORT ==========
	public function ajax_import_template() {
		check_ajax_referer( 'temppofo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'template-porter-for-elementor' ) ] );
		}

		if ( empty( $_FILES['import_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded', 'template-porter-for-elementor' ) ] );
		}

		// Validate file type
		$file_name = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( $_FILES['import_file']['name'] ) : '';
		$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

		if ( 'zip' !== $file_ext ) {
			wp_send_json_error( [ 'message' => __( 'Invalid file type. Please upload a ZIP file.', 'template-porter-for-elementor' ) ] );
		}

		// Check file size (limit to 50MB)
		$max_size = 50 * 1024 * 1024; // 50MB in bytes
		if ( isset( $_FILES['import_file']['size'] ) && absint( $_FILES['import_file']['size'] ) > $max_size ) {
			wp_send_json_error( [ 'message' => __( 'File size exceeds 50MB limit.', 'template-porter-for-elementor' ) ] );
		}

		// Sanitize file upload array before processing
		if ( ! isset( $_FILES['import_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded', 'template-porter-for-elementor' ) ] );
		}

		// Sanitize each element of the $_FILES array
		$sanitized_file = [
			'name'     => isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( $_FILES['import_file']['name'] ) : '',
			'type'     => isset( $_FILES['import_file']['type'] ) ? sanitize_text_field( $_FILES['import_file']['type'] ) : '',
			'tmp_name' => isset( $_FILES['import_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['import_file']['tmp_name'] ) : '',
			'error'    => isset( $_FILES['import_file']['error'] ) ? absint( $_FILES['import_file']['error'] ) : 0,
			'size'     => isset( $_FILES['import_file']['size'] ) ? absint( $_FILES['import_file']['size'] ) : 0,
		];

		$result = $this->import_template_with_images( $sanitized_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message(), 'log' => $result->get_error_data() ] );
		}

		wp_send_json_success( [
			'message'     => 'Template imported successfully!',
			'template_id' => $result['template_id'],
			'title'       => $result['title'],
			'edit_url'    => admin_url( 'post.php?post=' . $result['template_id'] . '&action=elementor' ),
			'log'         => $result['log'],
		] );
	}

	protected function import_template_with_images( $file ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$import_log = [];

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', __( 'File upload error', 'template-porter-for-elementor' ), $import_log );
		}

		$upload_dir = wp_upload_dir();
		$temp_dir   = trailingslashit( $upload_dir['basedir'] ) . 'temppofo-import-' . wp_generate_password( 12, false );
		wp_mkdir_p( $temp_dir );

		$import_log[] = "Created temp directory: $temp_dir";

		// Extract ZIP with security checks
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'no_zip', __( 'ZipArchive PHP extension not available', 'template-porter-for-elementor' ), $import_log );
		}

		$zip = new ZipArchive();
		if ( $zip->open( $file['tmp_name'] ) !== true ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'zip_error', __( 'Could not open ZIP file', 'template-porter-for-elementor' ), $import_log );
		}

		// Security: Check for path traversal attacks
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$filename = $zip->getNameIndex( $i );
			if ( strpos( $filename, '..' ) !== false || strpos( $filename, '/' ) === 0 ) {
				$zip->close();
				$this->recursive_delete( $temp_dir );
				return new WP_Error( 'security_error', __( 'Invalid file paths detected in ZIP', 'template-porter-for-elementor' ), $import_log );
			}
		}

		$zip->extractTo( $temp_dir );
		$zip->close();

		$import_log[] = "Extracted ZIP to temp directory";

		// Read template.json
		$json_file = $temp_dir . '/template.json';
		if ( ! file_exists( $json_file ) ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'invalid_package', 'Invalid template package (missing template.json)', $import_log );
		}

		$json_content = file_get_contents( $json_file );
		$export_data = json_decode( $json_content, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'invalid_json', 'Invalid JSON in template.json: ' . json_last_error_msg(), $import_log );
		}

		$import_log[] = "Parsed template.json successfully";
		$import_log[] = "Template title: " . ( $export_data['template_title'] ?? 'N/A' );
		$import_log[] = "Template type: " . ( $export_data['template_type'] ?? 'N/A' );

		// Import images into Media Library and build ID map
		$image_map = []; // old_id => new_id
		if ( ! empty( $export_data['images'] ) ) {
			$images_dir = $temp_dir . '/images';
			$import_log[] = "Found " . count( $export_data['images'] ) . " images to import";
			
			foreach ( $export_data['images'] as $old_id => $filename ) {
				$file_path = $images_dir . '/' . $filename;
				if ( file_exists( $file_path ) ) {
					$new_id = $this->sideload_image_from_file( $file_path, $filename );
					if ( ! is_wp_error( $new_id ) ) {
						$image_map[ $old_id ] = $new_id;
						$import_log[] = "Imported image: $filename (old ID: $old_id -> new ID: $new_id)";
					} else {
						$import_log[] = "Failed to import image: $filename - " . $new_id->get_error_message();
					}
				} else {
					$import_log[] = "Image file not found: $file_path";
				}
			}
		} else {
			$import_log[] = "No images to import";
		}

		// Get template data
		if ( ! isset( $export_data['template_content'] ) ) {
			$this->recursive_delete( $temp_dir );
			return new WP_Error( 'no_content', 'Template package missing template_content', $import_log );
		}

		$template_data = $export_data['template_content'];
		
		// If it's a string, decode it
		if ( is_string( $template_data ) ) {
			$template_data = json_decode( $template_data, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$this->recursive_delete( $temp_dir );
				return new WP_Error( 'invalid_content', 'Invalid template_content JSON', $import_log );
			}
		}

		$import_log[] = "Template data is an array: " . ( is_array( $template_data ) ? 'YES' : 'NO' );
		$import_log[] = "Template data element count: " . ( is_array( $template_data ) ? count( $template_data ) : 0 );

		// Replace image IDs with new ones
		if ( ! empty( $image_map ) ) {
			$template_data = $this->replace_image_ids_in_data( $template_data, $image_map );
			$import_log[] = "Replaced image IDs in template data";
		}

		// Create new Elementor template post
		$post_title = isset( $export_data['template_title'] ) ? sanitize_text_field( $export_data['template_title'] ) : __( 'Imported Template', 'template-porter-for-elementor' );
		$new_post_id = wp_insert_post( [
			'post_title'  => $post_title . ' ' . __( '(Imported)', 'template-porter-for-elementor' ),
			'post_status' => 'publish',
			'post_type'   => 'elementor_library',
		], true );

		if ( is_wp_error( $new_post_id ) ) {
			$this->recursive_delete( $temp_dir );
			return $new_post_id;
		}

		$import_log[] = "Created new post ID: $new_post_id";

		// Save Elementor meta - CRITICAL: must be JSON string for _elementor_data
		$json_to_save = wp_json_encode( $template_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		update_post_meta( $new_post_id, '_elementor_data', wp_slash( $json_to_save ) );
		$import_log[] = "Saved _elementor_data (length: " . strlen( $json_to_save ) . " bytes)";
		
		// Save other meta
		update_post_meta( $new_post_id, '_elementor_template_type', $export_data['template_type'] ?? 'page' );
		$import_log[] = "Saved _elementor_template_type: " . ( $export_data['template_type'] ?? 'page' );
		
		if ( ! empty( $export_data['page_settings'] ) ) {
			update_post_meta( $new_post_id, '_elementor_page_settings', $export_data['page_settings'] );
			$import_log[] = "Saved _elementor_page_settings";
		}
		
		update_post_meta( $new_post_id, '_elementor_edit_mode', $export_data['edit_mode'] ?? 'builder' );
		update_post_meta( $new_post_id, '_elementor_version', $export_data['elementor_version'] ?? ELEMENTOR_VERSION );
		
		// This tells Elementor the template needs CSS regeneration
		update_post_meta( $new_post_id, '_elementor_css', '' );
		
		$import_log[] = "All meta saved successfully";

		// Cleanup
		$this->recursive_delete( $temp_dir );
		$import_log[] = "Cleaned up temp directory";

		return [
			'template_id' => $new_post_id,
			'title'       => get_the_title( $new_post_id ),
			'log'         => $import_log,
		];
	}

	protected function sideload_image_from_file( $file_path, $filename ) {
		// Validate file path
		$file_path = realpath( $file_path );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'Image file not found', 'template-porter-for-elementor' ) );
		}

		// Validate file type - only allow images
		$allowed_mime_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml' ];
		$filetype = wp_check_filetype( $filename );
		
		if ( ! in_array( $filetype['type'], $allowed_mime_types, true ) ) {
			return new WP_Error( 'invalid_image_type', __( 'Invalid image file type', 'template-porter-for-elementor' ) );
		}

		$upload_dir = wp_upload_dir();
		$safe_filename = sanitize_file_name( $filename );
		$new_file   = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $safe_filename );

		if ( ! copy( $file_path, $new_file ) ) {
			/* translators: %s: filename */
			return new WP_Error( 'copy_failed', sprintf( __( 'Could not copy %s', 'template-porter-for-elementor' ), $safe_filename ) );
		}

		$filetype = wp_check_filetype( $new_file );
		$attachment = [
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attach_id = wp_insert_attachment( $attachment, $new_file );
		if ( is_wp_error( $attach_id ) ) {
			wp_delete_file( $new_file );
			return $attach_id;
		}

		$attach_data = wp_generate_attachment_metadata( $attach_id, $new_file );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	protected function replace_image_ids_in_data( $data, $image_map ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				// Replace id field if it's in the map
				if ( $key === 'id' && isset( $image_map[ $value ] ) ) {
					$data[ $key ] = $image_map[ $value ];
					// Also update url if present
					if ( isset( $data['url'] ) ) {
						$new_url = wp_get_attachment_url( $image_map[ $value ] );
						if ( $new_url ) {
							$data['url'] = $new_url;
						}
					}
				} else {
					$data[ $key ] = $this->replace_image_ids_in_data( $value, $image_map );
				}
			}
		}
		return $data;
	}

	// Utility: recursive directory delete
	protected function recursive_delete( $dir ) {
		global $wp_filesystem;

		// Initialize WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
			WP_Filesystem();
		}

		if ( ! is_dir( $dir ) ) {
			return;
		}

		// Use WP_Filesystem to remove directory recursively
		if ( $wp_filesystem ) {
			$wp_filesystem->rmdir( $dir, true );
		} else {
			// Fallback to manual deletion if WP_Filesystem is not available
			$files = array_diff( scandir( $dir ), [ '.', '..' ] );
			foreach ( $files as $file ) {
				$path = $dir . '/' . $file;
				if ( is_dir( $path ) ) {
					$this->recursive_delete( $path );
				} else {
					wp_delete_file( $path );
				}
			}
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Safe fallback for empty directory removal
			}
		}
	}
}

// Initialize plugin only if Elementor is active
function temppofo_init() {
	if ( temppofo_check_elementor_dependency() ) {
		new TEMPPOFO_Template_Porter();
	}
}
add_action( 'plugins_loaded', 'temppofo_init' );