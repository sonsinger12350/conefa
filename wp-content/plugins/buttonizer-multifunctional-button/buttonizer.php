<?php
/*
* Plugin Name: Buttonizer - Smart Floating Action Button
* Plugin URI:  https://buttonizer.io
* Description: The Buttonizer is a new way to give a boost to your number of interactions, actions and conversions from your website visitor by adding one or multiple Customizable Smart Floating Button in the corner of your website.
* Version:     3.4.9
* Author:      Buttonizer
* Author URI:  https://buttonizer.io
* License:     GPLv3
* License URI: https://buttonizer.io/license/
* Text Domain: buttonizer-multifunctional-button
* Domain Path: /languages
*
*
* SOFTWARE LICENSE INFORMATION
*
* Copyright (c) 2017 Buttonizer, all rights reserved.
*
* This file is part of Buttonizer
*
* For detailed information regarding to the licensing of
* this software, please review the license.txt or visit:
* https://buttonizer.io/license/
*/

// Define current Buttonizer version
define('BUTTONIZER_VERSION', '3.4.9');
define('BUTTONIZER_PLUGIN_FILE', __FILE__);

// Get environment vars
require_once __DIR__ . "/EnvVars.php";


# No script kiddies
defined('ABSPATH') or die('No script kiddies please!');

/* ================================================
 *     WELCOME TO THE BUTTONIZER SOURCE CODE!
 *
 *      We like to see that you are courious
 *        how the code is written. If you
 *       are here to try to resolve problems
 *        you must be careful, anything
 *          can get broken you know...
 *
 *            -- KNOWLEDGE BASE --
 *        Did you know you can use our
 *              knowledge base?
 *               That's free!
 *
 *				     VISIT:
 *  https://r.buttonizer.io/support/knowledgebase
 *
 *             -- BUGS FOUND? --
 *	    Are you here to look for a bug?
 *		 Cool! If you found something
 *         you can report it to us!
 *
 *       Maybe you get a FREE license
 *            for 1 website ;)
 *
 * ================================================
 */

// Autoloader
require_once BUTTONIZER_APP_DIR . "/autoloader.php";

// Initialize
require_once BUTTONIZER_DIR . "/init.php";

// Uninstall
register_uninstall_hook(__FILE__, 'pluginUninstallEvent');

function pluginUninstallEvent()
{
    // Skip if the is no API token being used
    if (\Buttonizer\Utils\ApiRequest::getApiToken() === false) {
        return;
    }

    try {
        // Invalidate access token for security reasons on uninstall
        (new \Buttonizer\Api\Connection\Disconnect)->disconnect();
    } catch (\Error $err) {
        // Errored out, nevermind then
    }
}
