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
 * The cron-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for managing cron events.
 */
class Substack_Sync_Cron
{
    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        // The custom hook that will run our sync logic
        add_action('substack_sync_hourly_event', [$this, 'run_hourly_sync']);

        // Schedule the event if not already scheduled
        if (! wp_next_scheduled('substack_sync_hourly_event')) {
            wp_schedule_event(time(), 'hourly', 'substack_sync_hourly_event');
        }
    }

    /**
     * Run the hourly sync process.
     *
     * This is the callback function for the cron job.
     */
    public function run_hourly_sync(): void
    {
        // Ensure the processor class is available
        require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
        $processor = new Substack_Sync_Processor();
        $processor->run_sync();
    }
}
