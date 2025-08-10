<?php

declare(strict_types=1);

/**
 * Plugin Name:       Substack Sync
 * Plugin URI:        https://www.christopherspenn.com/2025/08/substack-sync-for-wordpress/
 * Description:       Syncs a Substack RSS feed to your WordPress site. NO SUPPORT PROVIDED. Use at your own risk. If it lights your computer on fire, it's not the author's fault.
 * Version:           1.0.2
 * Author:            Christopher S. Penn
 * Author URI:        https://www.christopherspenn.com/
 * License:           Apache-2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 * Text Domain:       substack-sync
 * Network:           false
 * Requires at least: 6.0
 * Tested up to:      6.6
 * Requires PHP:      8.0
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Define Plugin Constants
define('SUBSTACK_SYNC_VERSION', '1.0.2');
define('SUBSTACK_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_substack_sync(): void
{
    require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-activator.php';
    Substack_Sync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_substack_sync(): void
{
    require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-deactivator.php';
    Substack_Sync_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_substack_sync');
register_deactivation_hook(__FILE__, 'deactivate_substack_sync');

// Include All Other Files
require_once SUBSTACK_SYNC_PLUGIN_DIR . 'admin/class-substack-sync-admin.php';
require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-cron.php';
require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';

// Initialize the classes
new Substack_Sync_Admin();
new Substack_Sync_Cron();
