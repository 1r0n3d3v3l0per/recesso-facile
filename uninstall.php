<?php
/**
 * Uninstall Script
 * Fires when the plugin is uninstalled via WordPress admin
 *
 * @package RecessoFacile
 */

// Exit if accessed directly or if not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load plugin file to access constants and classes
require_once plugin_dir_path(__FILE__) . 'recesso-facile.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-rf-install.php';

// Check user capabilities
if (!current_user_can('activate_plugins')) {
    return;
}

// Delete plugin data based on settings
$delete_data = get_option('rf_delete_data_on_uninstall', 'no');

if ($delete_data === 'yes') {
    // Drop database tables
    RF_Install::drop_tables();

    // Delete all options
    RF_Install::delete_options();

    // Delete withdrawal page
    $page_id = get_option('rf_withdrawal_page');
    if ($page_id) {
        wp_delete_post($page_id, true);
    }

    // Delete uploaded files (PDFs)
    $upload_dir = wp_upload_dir();
    $rf_dir = $upload_dir['basedir'] . '/recesso-facile';

    if (file_exists($rf_dir)) {
        deleteDirectory($rf_dir);
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('recesso_facile_daily_cleanup');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Recursively delete directory
 *
 * @param string $dir Directory path
 * @return bool Success
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}
