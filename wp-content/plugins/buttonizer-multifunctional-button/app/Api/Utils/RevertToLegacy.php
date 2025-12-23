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

namespace Buttonizer\Api\Utils;

use Buttonizer\Utils\PermissionCheck;
use Buttonizer\Utils\Settings;

/**
 * Delete Legacy Backup API
 * Removes backup rows made from migrating
 *
 * @endpoint /wp-json/buttonizer/revert_legacy
 * @methods POST
 */
class RevertToLegacy
{
    /**
     * Register route
     */
    public function registerRoute()
    {
        register_rest_route('buttonizer', '/revert_legacy', [
            [
                'methods'  => ['POST'],
                'args' => [
                    'nonce' => [
                        'validate_callback' => function ($value) {
                            return wp_verify_nonce($value, 'wp_rest');
                        },
                        'required' => true
                    ],
                ],
                'callback' => [$this, 'revert'],
                'permission_callback' => function () {
                    return PermissionCheck::hasPermission(true);
                }
            ]
        ]);
    }

    /**
     * Delete backup
     */
    public function revert()
    {
        self::restore();

        // Get all current settings first
        $settings = get_option('buttonizer_settings');

        // Set force_legacy
        $settings["force_legacy"] = true;
        update_option('buttonizer_settings', $settings);

        // Delete standalone
        delete_option('buttonizer_account');

        return [
            'status' => 'success'
        ];
    }

    /**
     * Restore 2.x data
     *
     */
    private static function restore(): void
    {
        // Register special backup settings
        register_setting('buttonizer', 'buttonizer_buttons');
        register_setting('buttonizer', 'buttonizer_buttons_published');
        register_setting('buttonizer', 'buttonizer_has_changes');
        register_setting('buttonizer', 'buttonizer_rules');
        register_setting('buttonizer', 'buttonizer_rules_published');
        register_setting('buttonizer', 'buttonizer_schedules');
        register_setting('buttonizer', 'buttonizer_schedules_published');
        register_setting('buttonizer', 'buttonizer_settings');

        // Save restorable data
        update_option('buttonizer_buttons', get_option('buttonizer_buttons_backup_30'));
        update_option('buttonizer_buttons_published', get_option('buttonizer_buttons_published_backup_30'));
        update_option('buttonizer_has_changes', get_option('buttonizer_has_changes_backup_30'));
        update_option('buttonizer_rules', get_option('buttonizer_rules_backup_30'));
        update_option('buttonizer_rules_published', get_option('buttonizer_rules_published_backup_30'));
        update_option('buttonizer_schedules', get_option('buttonizer_schedules_backup_30'));
        update_option('buttonizer_schedules_published', get_option('buttonizer_schedules_published_backup_30'));
        update_option('buttonizer_settings', get_option('buttonizer_settings_backup_30'));

        // Delete old options
        delete_option('buttonizer_buttons_backup_30');
        delete_option("buttonizer_buttons_published_backup_30");
        delete_option("buttonizer_has_changes_backup_30");
        delete_option("buttonizer_rules_backup_30");
        delete_option("buttonizer_rules_published_backup_30");
        delete_option("buttonizer_schedules_backup_30");
        delete_option("buttonizer_schedules_published_backup_30");
        delete_option("buttonizer_settings_backup_30");
    }
}
