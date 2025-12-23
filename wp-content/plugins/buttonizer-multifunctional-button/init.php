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

use Buttonizer\Admin\Admin;
use Buttonizer\Utils\Settings;
use Buttonizer\Api\Api;
use Buttonizer\Utils\PermissionCheck;

/**
 * Get current languages
 */
function getCurrentLanguage()
{
    // Polylang
    if (function_exists("pll_current_language")) {
        return pll_current_language("slug");
    }

    // Weglot
    if (function_exists("weglot_get_current_language")) {
        return weglot_get_current_language();
    }

    // WMPL
    $currentLanguage = apply_filters('wpml_current_language', NULL);

    // Try to fall back on current language
    if (!$currentLanguage) return substr(get_bloginfo('language'), 0, 2);

    return $currentLanguage;
}

/**
 * Custom language
 *
 * Automatically redirects to the page in current language
 */
function buttonizer_redirect_to_page()
{
    // Validate params
    if (!isset($_GET['page_id']) || !is_numeric($_GET['page_id']) || !isset($_GET['is_buttonizer_redirect'])) {
        return;
    }

    $id = $_GET['page_id'];
    $page = null;

    // Polylang
    if (function_exists("pll_get_post")) {
        $page = pll_get_post($id);
    }

    // Check WPML translated page
    if (!$page && $wpmlObject = apply_filters('wpml_object_id', $id)) {
        $page = $wpmlObject;
    }

    // Redirect if post or page was found
    if ($pageUrl = get_the_permalink($page ?? $id)) {
        // Check if the page was redirected
        if (!wp_redirect($pageUrl, 302, 'Buttonizer')) {
            // Make sure to receive a safe redirect URL
            $redirectUrl = wp_validate_redirect(wp_sanitize_redirect($pageUrl), false);

            // Only redirect if it's a safe and allowed host
            if ($redirectUrl) {
                header("Location: " . $redirectUrl, true, 302);
            }

            exit("A redirect was cancelled.");
        }
        exit;
    }
}

// Check Buttonizer Legacy enabled
if (
    // Check if legacy was forced
    Settings::getSetting("force_legacy", false) === true ||

    // Check for older installations which did not yet decide
    (Settings::getSetting("finished_setup", false) === false && get_option("buttonizer_buttons", false) !== false)
) {
    // Init legacy
    require_once BUTTONIZER_DIR . "/legacy.php";
} else {
    /*
    * Buttonizer Admin Dashboard
    */
    if (is_admin()) {
        // Load Admin page
        new Admin();
    }

    /**
     * Redirect to page in correct language
     */
    add_action('template_redirect', 'buttonizer_redirect_to_page', 0);

    /**
     * Add Buttonizer scripts
     */
    add_action('wp_enqueue_scripts', function () {
        // Add Google Analytics (old setting from Buttonizer 2.x)
        if (Settings::isset("google_analytics")) {
            wp_register_script('google_analytics', 'https://www.googletagmanager.com/gtag/js?id=' . Settings::getSetting("google_analytics"), array(), false, true);

            wp_enqueue_script('google_analytics');

            wp_add_inline_script('google_analytics', "
 window.dataLayer = window.dataLayer || [];
 function gtag(){dataLayer.push(arguments);}
 gtag('js', new Date());

 gtag('config', '" . Settings::getSetting("google_analytics") . "');");
        }
    });

    // Add page data
    add_action('wp_head', function () {
        if (Settings::getSetting("site_id")) {
            // Get current page language
            $pageData = [
                "language" => getCurrentLanguage()
            ];

            // Add Buttonize page data
            if (Settings::getSetting("include_page_data", false)) {
                // Get page categories
                $pageCategories = array_map(function ($category) {
                    return $category->cat_ID;
                }, get_the_category());

                // Collect page data
                $pageData = array_merge([
                    "page_id" => get_the_ID(),
                    "categories" => $pageCategories,
                    "is_frontpage" => is_front_page(),
                    "is_404" => is_404(),
                    "user_roles" => PermissionCheck::getUserRoles()
                ], $pageData);
            }

            // Define page data
            $buttonizerData = "if(!window._buttonizer) { window._buttonizer = {}; };var _buttonizer_page_data = " . json_encode($pageData) . ";window._buttonizer.data = { ..._buttonizer_page_data, ...window._buttonizer.data };";

            echo '<script type="text/javascript">' . $buttonizerData . '</script>';
        }
    }, 10);

    // Add integration script
    add_action('wp_footer', function () {
        if (Settings::getSetting("site_id")) {
            // Buttonizer integration script
            $buttonizerSnippet = "(function(n,t,c,d){if(t.getElementById(d)){return}var o=t.createElement('script');o.id=d;(o.async=!0),(o.src='https://cdn.buttonizer.io/embed.js'),(o.onload=function(){window.Buttonizer?window.Buttonizer.init(c):window.addEventListener('buttonizer_script_loaded',()=>window.Buttonizer.init(c))}),t.head.appendChild(o)})(window,document,'" . Settings::getSetting("site_id") . "','buttonizer_script')";

            // GDPR Compliance check
            if (Settings::getSetting("wait_until_consent", false)) {
                $buttonizerSnippet = "// Buttonizer snippet container
function enableButtonizer() {" . $buttonizerSnippet . "};

// Buttonizer consent given, load content
if(window.buttonizer_consent_given){ enableButtonizer(); }";
            }

            echo '<script type="text/javascript">' . $buttonizerSnippet . '</script>';
        }
    }, 11);

    // Validator only available after WP 4.9
    function isValidUUID($uuid)
    {
        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

        return (bool) preg_match($regex, $uuid);
    }

    // Buttonizer widget shortcode
    function widgetShortcode($atts)
    {
        // Get attributes
        $atts = shortcode_atts(
            array(
                'id' => '',
            ),
            $atts
        );

        // Make sure the ID exists and is a valid UUID
        if (!isset($atts['id']) || !is_string($atts['id']) || !isValidUUID($atts['id'])) return "";

        return '<div class="buttonizer-inline-widget" data-buttonizer-widget-id="' . $atts['id'] . '"></div>';
    };

    function initFunction()
    {
        add_shortcode('buttonizer', 'widgetShortcode');
    }

    add_action('init', 'initFunction');

    // Add admin menu
    add_action('admin_bar_menu', function ($bar) {
        Admin::wordpressAdminBar($bar);
    }, 100);

    /**
     * Initialize Buttonizer API endpoints
     */
    add_action('rest_api_init', function () {
        new Api();
    });
}
