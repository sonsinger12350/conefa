<?php
/**
 * Uninstall script for Elementor Template Porter
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It cleans up any temporary files and data created by the plugin.
 *
 * @package Elementor_Template_Porter
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up temporary export/import files
 */
function etp_cleanup_temp_files() {
	$upload_dir = wp_upload_dir();
	$base_dir   = $upload_dir['basedir'];

	if ( ! is_dir( $base_dir ) ) {
		return;
	}

	// Remove any temporary export/import directories
	$files = glob( $base_dir . '/etp-temp-*' );
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				etp_recursive_delete( $file );
			}
		}
	}

	$files = glob( $base_dir . '/etp-import-*' );
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				etp_recursive_delete( $file );
			}
		}
	}

	// Remove any leftover ZIP files
	$zip_files = glob( $base_dir . '/*-*.zip' );
	if ( $zip_files ) {
		foreach ( $zip_files as $zip_file ) {
			if ( is_file( $zip_file ) && strpos( basename( $zip_file ), 'etp' ) !== false ) {
				wp_delete_file( $zip_file );
			}
		}
	}
}

/**
 * Recursively delete a directory
 *
 * @param string $dir Directory path to delete.
 */
function etp_recursive_delete( $dir ) {
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
				etp_recursive_delete( $path );
			} else {
				wp_delete_file( $path );
			}
		}
		if ( is_dir( $dir ) ) {
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Safe fallback for empty directory removal
		}
	}
}

// Execute cleanup
etp_cleanup_temp_files();

// Note: We do NOT delete imported Elementor templates as users may want to keep them
// even after uninstalling the plugin. If needed, users can manually delete templates
// from the Elementor Library.
