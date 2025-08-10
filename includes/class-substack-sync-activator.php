<?php

declare(strict_types=1);

/**
 * Substack Sync - WordPress Plugin
 *
 * Copyright (c) 2025 Christopher S. Penn
 * Licensed under Apache License Version 2.0
 *
 * NO SUPPORT PROVIDED. USE AT YOUR OWN RISK.
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Substack_Sync_Activator
{
    /**
     * Activate the plugin.
     *
     * Creates the database table for tracking synchronized posts.
     */
    public static function activate(): void
    {
        self::create_sync_table();
    }

    /**
     * Create the sync table.
     *
     * Creates a custom database table to track imported posts.
     */
    private static function create_sync_table(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'substack_sync_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            substack_guid varchar(255) NOT NULL,
            substack_title varchar(500) DEFAULT '' NOT NULL,
            sync_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            last_modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) DEFAULT '' NOT NULL,
            retry_count int DEFAULT 0 NOT NULL,
            error_message text DEFAULT '' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY substack_guid (substack_guid),
            KEY status_idx (status),
            KEY sync_date_idx (sync_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
