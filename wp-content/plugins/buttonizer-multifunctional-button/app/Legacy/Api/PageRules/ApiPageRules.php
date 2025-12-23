<?php

namespace Buttonizer\Legacy\Api\PageRules;

use Buttonizer\Utils\PermissionCheck;
/**
 * PageRules API
 * 
 * @endpoint /wp-json/buttonizer/page_rules
 * @methods GET POST
 */
class ApiPageRules {
    /**
     * Register route
     */
    public function registerRoute() {
        register_rest_route( 'buttonizer', '/page_rules', [[
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
        // Register subpages
        ( new WordPressData\ApiPages() )->registerRoute();
        ( new WordPressData\ApiBlogs() )->registerRoute();
        ( new WordPressData\ApiCategories() )->registerRoute();
        ( new WordPressData\ApiRoles() )->registerRoute();
        ( new WordPressData\ApiDebug() )->registerRoute();
    }

    /**
     * Get page rules
     */
    public function get() {
        return \Buttonizer\Legacy\Api\Api::needButtonizerPremium();
    }

    /**
     * Save page rules
     */
    public function post( $request ) {
        return \Buttonizer\Legacy\Api\Api::needButtonizerPremium();
    }

}
