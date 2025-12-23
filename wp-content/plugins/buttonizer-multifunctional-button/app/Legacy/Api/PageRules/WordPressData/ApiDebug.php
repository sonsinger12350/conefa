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
namespace Buttonizer\Legacy\Api\PageRules\WordPressData;

use Buttonizer\Legacy\Frontend\Buttonizer;
use Buttonizer\Legacy\Frontend\PageRules\Rule\Rule;
use Buttonizer\Utils\PermissionCheck;
/**
 * WordPress Buttonizer API
 * 
 * @endpoint /wp-json/buttonizer/pagerules/debug
 * @methods GET
 */
class ApiDebug {
    /**
     * Register route
     */
    public function registerRoute() {
        register_rest_route( 'buttonizer', '/page_rules/debug', [[
            'methods'             => ['GET'],
            'args'                => [
                'rule'      => [
                    'required' => true,
                    "type"     => "string",
                ],
                'user_role' => [
                    'required' => true,
                    "type"     => "string",
                ],
                'url'       => [
                    'required' => true,
                    "type"     => "string",
                ],
            ],
            'callback'            => [$this, 'debug'],
            'permission_callback' => function () {
                return PermissionCheck::hasPermission();
            },
        ]] );
    }

    /**
     * Get page rules roles
     */
    public function debug() {
        return \Buttonizer\Legacy\Api\Api::needButtonizerPremium();
    }

}
