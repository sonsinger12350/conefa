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
namespace Buttonizer\Legacy\Api\TimeSchedules;

use Buttonizer\Utils\PermissionCheck;
/**
 * PageRules API
 * 
 * @endpoint /wp-json/buttonizer/time_schedules
 * @methods GET POST
 */
class ApiTimeSchedules {
    /**
     * Register route
     */
    public function registerRoute() {
        register_rest_route( 'buttonizer', '/time_schedules', [[
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
        ], [
            'methods'             => ['POST'],
            'args'                => [
                'data'  => [
                    'required' => true,
                    'type'     => "object",
                ],
                'nonce' => [
                    'validate_callback' => function ( $value ) {
                        return wp_verify_nonce( $value, 'wp_rest' );
                    },
                    'required'          => true,
                ],
            ],
            'callback'            => [$this, 'post'],
            'permission_callback' => function () {
                return PermissionCheck::hasPermission();
            },
        ]] );
    }

    /**
     * Get time schedules
     */
    public function get() {
        return \Buttonizer\Legacy\Api\Api::needButtonizerPremium();
    }

    /**
     * Save time schedule data
     */
    public function post( $request ) {
        return \Buttonizer\Legacy\Api\Api::needButtonizerPremium();
    }

}
