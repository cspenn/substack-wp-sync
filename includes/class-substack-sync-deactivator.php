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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Substack_Sync_Deactivator
{
    /**
     * Deactivate the plugin.
     *
     * Clears scheduled cron events.
     */
    public static function deactivate(): void
    {
        // The hook name must match the one created in the Cron class.
        wp_clear_scheduled_hook('substack_sync_hourly_event');
    }
}
