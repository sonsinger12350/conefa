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

use Buttonizer\Utils\PermissionCheck;
/**
 * WordPress Buttonizer API
 * 
 * @endpoint /wp-json/buttonizer/pagerules/blogs
 * @methods GET
 */
class ApiBlogs {
    /**
     * Register route
     */
    public function registerRoute() {
        register_rest_route( 'buttonizer', '/page_rules/blogs', [[
            'methods'             => ['GET'],
            'args'                => [
                'nonce' => [
                    'validate_callback' => function ( $value ) {
                        return wp_verify_nonce( $value, 'wp_rest' );
                    },
                    'required'          => true,
                ],
            ],
            'callback'            => [$this, 'get'],
            'permission_callback' => function () {
                return PermissionCheck::hasPermission();
            },
        ]] );
    }

    /**
     * Get page rules blogs
     */
    public function get() {
        return \Buttonizer\Legacy\Api\Api::needButtonizerPremium();
    }

}
