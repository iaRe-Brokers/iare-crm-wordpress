<?php
/**
 * Uninstall Script
 * 
 * Fired when the plugin is uninstalled.
 * 
 * @package IareCrm
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!defined('IARE_CRM_OPTION_API_KEY')) {
    define('IARE_CRM_OPTION_API_KEY', 'iare_crm_api_key');
}

if (!defined('IARE_CRM_OPTION_SETTINGS')) {
    define('IARE_CRM_OPTION_SETTINGS', 'iare_crm_settings');
}

/**
 * Clean up known plugin transients
 */
function iare_crm_clean_plugin_transients() {
    $known_transients = [
        'iare_crm_auto_connection_test_attempt_1',
        'iare_crm_auto_connection_test_attempt_2',
        'iare_crm_auto_connection_test_attempt_3',
        'iare_crm_auto_connection_test_attempt_4',
        'iare_crm_auto_connection_test_attempt_5',
        'iare_crm_connection_cache',
        'iare_crm_api_rate_limit',
        'iare_crm_campaigns_cache',
        'iare_crm_sync_status'
    ];
    
    foreach ($known_transients as $transient_key) {
        delete_transient($transient_key);
    }
    
    foreach ($known_transients as $transient_key) {
        delete_site_transient($transient_key);
    }
    
    wp_cache_flush();
}

/**
 * Clean up plugin data
 */
function iare_crm_uninstall_cleanup() {
    delete_option(IARE_CRM_OPTION_API_KEY);
    delete_option(IARE_CRM_OPTION_SETTINGS);
    
    delete_transient('iare_crm_campaigns');
    delete_transient('iare_crm_connection_status');
    
    iare_crm_clean_plugin_transients();
    
    wp_clear_scheduled_hook('iare_crm_sync_campaigns');
    wp_clear_scheduled_hook('iare_crm_cleanup_logs');
    
    $roles = ['administrator'];
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        
        if ($role) {
            $role->remove_cap('manage_iare_crm');
            $role->remove_cap('view_iare_crm_logs');
        }
    }
    
    flush_rewrite_rules();

    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/iare-crm-logs';

    if (is_dir($log_dir)) {
        $files = glob($log_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
        
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        WP_Filesystem();
        global $wp_filesystem;
        
        if ($wp_filesystem && $wp_filesystem->is_dir($log_dir)) {
            $wp_filesystem->rmdir($log_dir);
        }
    }
}

iare_crm_uninstall_cleanup(); 