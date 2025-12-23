<?php

/*
 * SOFTWARE LICENSE INFORMATION
 *
 * Copyright (c) 2017 Buttonizer, all rights reserved.
 *
 * This file is part of Buttonizer
 *
 * For detailed information regarding to the licensing of
 * this software, please review the license.txt or visit:
 * https://buttonizer.pro/license/
 */
namespace Buttonizer\Legacy\Admin;

use Buttonizer\Admin\Admin as NewAdmin;
use Buttonizer\Utils\Editor;
use Buttonizer\Utils\PermissionCheck;
use Buttonizer\Utils\Settings;
# No script kiddies
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
class Admin {
    private static $showMigrationPage = false;

    /**
     * Admin constructor.
     */
    public function __construct() {
        // Lets do some admin stuff for Buttonizer
        add_action( 'admin_init', [$this, 'adminInit'] );
        // Add admin assets for migration
        add_action( 'admin_enqueue_scripts', [$this, 'adminAssets'] );
        // Add admin menu
        add_action( 'admin_menu', [$this, 'pluginAdminMenu'] );
    }

    /**
     * Create Admin menu
     */
    public function pluginAdminMenu() {
        if ( !PermissionCheck::hasPermission() ) {
            return;
        }
        // Admin menu
        add_menu_page(
            'Buttonizer Buttons',
            'Buttonizer',
            'read',
            'Buttonizer',
            [$this, 'migrationAdminPage'],
            plugins_url( '/assets/images/wp-icon.png', BUTTONIZER_PLUGIN_DIR ),
            81
        );
        // Add submenu
        add_submenu_page(
            'Buttonizer',
            'Migrate to 3.0',
            __( 'Migrate to 3.0', 'buttonizer-multifunctional-button' ),
            ( is_multisite() ? 'manage_options' : 'activate_plugins' ),
            'admin.php?page=Buttonizer&buttonizer_migrate=true'
        );
        // Add community link
        add_submenu_page(
            'Buttonizer',
            __( 'Community', 'buttonizer-multifunctional-button' ),
            __( 'Community', 'buttonizer-multifunctional-button' ),
            'read',
            'https://community.buttonizer.pro/?referral=buttonizer-plugin-menu'
        );
        // Add knowledge base link
        add_submenu_page(
            'Buttonizer',
            __( 'Knowledge base', 'buttonizer-multifunctional-button' ),
            __( 'Knowledge base', 'buttonizer-multifunctional-button' ),
            'read',
            'https://community.buttonizer.pro/knowledgebase?referral=buttonizer-plugin-menu'
        );
        // Add support link
        add_submenu_page(
            'Buttonizer',
            __( 'I need support', 'buttonizer-multifunctional-button' ),
            __( 'I need support', 'buttonizer-multifunctional-button' ),
            'read',
            'https://community.buttonizer.pro/t/support?referral=buttonizer-plugin-menu'
        );
        // Plugin information, add links
        add_filter( "plugin_action_links_" . plugin_basename( BUTTONIZER_PLUGIN_DIR ), function ( $aLinks ) {
            $aButtonizerLinks = [
                '<a href="https://community.buttonizer.pro/" target="_blank">' . __( 'Community forums', 'buttonizer-multifunctional-button' ) . '</a>',
                '<a href="https://community.buttonizer.pro/knowledgebase" target="_blank">' . __( 'Knowledge base', 'buttonizer-multifunctional-button' ) . '</a>',
                '<a href="' . admin_url( 'admin.php?page=Buttonizer' ) . '">' . __( 'Manage buttons', 'buttonizer-multifunctional-button' ) . '</a>',
                '<a href="' . admin_url( 'admin.php?page=Buttonizer#/settings' ) . '">' . __( 'Settings', 'buttonizer-multifunctional-button' ) . '</a>'
            ];
            return array_merge( $aLinks, $aButtonizerLinks );
        } );
    }

    /**
     * Show new Buttonizer admin dashboard
     */
    public function adminAssets() {
        // Make sure only admins see this page
        if ( !self::$showMigrationPage ) {
            return;
        }
        ( new NewAdmin() )->adminAssets();
    }

    /**
     * Populate admin HTML content
     */
    public function migrationAdminPage() {
        // Make sure only admins see this page
        if ( !self::$showMigrationPage ) {
            return;
        }
        ( new NewAdmin() )->page();
    }

    /**
     * Remove stylesheets when on Buttonizer page
     */
    public function adminInit() {
        // Only show migration page to admins
        if ( defined( "\\BUTTONIZER_LEGACY_REQUESTED_MIGRATION" ) && \BUTTONIZER_LEGACY_REQUESTED_MIGRATION === false && PermissionCheck::hasPermission( true ) ) {
            self::$showMigrationPage = true;
        }
        // Register Buttonizer admin template
        if ( !self::$showMigrationPage && isset( $_GET['page'] ) && $_GET['page'] === 'Buttonizer' && !ButtonizerLicense()->is_activation_mode() ) {
            $this->getPluginAdminPage();
            exit;
        }
    }

    /**
     * Get media and add scripts/styles that Buttonizer uses
     */
    public function getPluginAdminPage() {
        // Add WordPress admin-header thing
        header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
        add_filter( 'show_admin_bar', '__return_false' );
        remove_all_actions( 'wp_head' );
        remove_all_actions( 'wp_print_styles' );
        remove_all_actions( 'wp_print_head_scripts' );
        remove_all_actions( 'wp_footer' );
        remove_all_actions( 'wp_enqueue_scripts' );
        wp_deregister_script( ['admin-bar'] );
        wp_deregister_style( ['admin-bar'] );
        // Require media manager
        wp_enqueue_media();
        $styles = '/assets/legacy/dashboard.css';
        $path = '/assets/legacy/dashboard.min.js';
        wp_register_script(
            'buttonizer_admin_js',
            plugins_url( $path . '?v=' . md5( BUTTONIZER_VERSION ), BUTTONIZER_PLUGIN_DIR ),
            [],
            false,
            true
        );
        // Require wordpress style
        wp_enqueue_style( 'buttonizer-css', plugins_url( $styles . '?v=' . md5( BUTTONIZER_VERSION ), BUTTONIZER_PLUGIN_DIR ) );
        wp_localize_script( 'buttonizer_admin_js', 'buttonizer_admin', [
            'ajax'                          => admin_url( 'admin-ajax.php' ),
            'admin'                         => admin_url( 'admin.php' ),
            'base_url'                      => get_site_url( '/' ),
            "admin_base"                    => substr( admin_url(), 0, -1 ),
            'can_send_errors'               => Settings::getSetting( "can_send_errors", false ),
            'api'                           => get_rest_url() . 'buttonizer',
            'nonce'                         => wp_create_nonce( 'wp_rest' ),
            'version'                       => BUTTONIZER_VERSION,
            'dir'                           => plugins_url( '', BUTTONIZER_PLUGIN_DIR ),
            'localePath'                    => plugins_url( '/assets/legacy/locale', BUTTONIZER_PLUGIN_DIR ),
            'locale'                        => Editor::getEditorLanguage(),
            'assets'                        => plugins_url( '/assets/legacy', BUTTONIZER_PLUGIN_DIR ),
            'templatesImgDir'               => plugins_url( '/assets/legacy/images/templates', BUTTONIZER_PLUGIN_DIR ),
            'security'                      => wp_create_nonce( "save_buttonizer" ),
            'fontawesome_current_version'   => FONTAWESOME_CURRENT_VERSION,
            'fontawesome_current_integrity' => FONTAWESOME_CURRENT_INTEGRITY,
            'permalink_structure'           => get_option( 'permalink_structure' ),
            'php_version_supported'         => version_compare( PHP_VERSION, '7.0.0', '>' ),
            'changelog_dialog_updated'      => BUTTONIZER_LAST_CHANGELOG_UPDATE,
            'php_version'                   => PHP_VERSION,
            'migration_version'             => BUTTONIZER_LAST_MIGRATION,
            'legacy_version'                => true,
        ] );
        wp_enqueue_script( 'buttonizer_admin_js' );
        // Import our own template
        require_once BUTTONIZER_DIR . '/app/Legacy/Admin/Template.php';
        exit;
    }

}
