<?php

namespace IareCrm\Admin\Pages;

use IareCrm\Traits\Singleton;
use IareCrm\Helpers\Logger;

defined('ABSPATH') || exit;

class DebugPage {
    use Singleton;

    private $logger;

    /**
     * Initialize debug page
     */
    protected function __construct() {
        $this->logger = new Logger();
        
        add_action('admin_init', [$this, 'handle_form_submission']);
        add_action('admin_post_iare_crm_clear_debug_logs', [$this, 'handle_clear_logs']);
    }

    

    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        if (!isset($_POST['iare_crm_debug_nonce']) || !wp_verify_nonce($_POST['iare_crm_debug_nonce'], 'iare_crm_debug_action')) {
            return;
        }

        if (!current_user_can('manage_iare_crm_debug')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'iare-crm'));
        }

        // Save debug setting
        if (isset($_POST['enable_debug'])) {
            $settings = get_option(IARE_CRM_OPTION_SETTINGS, []);
            $settings['enable_debug'] = true;
            update_option(IARE_CRM_OPTION_SETTINGS, $settings);
            add_settings_error('iare_crm_debug_messages', 'debug_enabled', __('Debug mode enabled.', 'iare-crm'), 'success');
        } else {
            $settings = get_option(IARE_CRM_OPTION_SETTINGS, []);
            $settings['enable_debug'] = false;
            update_option(IARE_CRM_OPTION_SETTINGS, $settings);
            add_settings_error('iare_crm_debug_messages', 'debug_disabled', __('Debug mode disabled.', 'iare-crm'), 'success');
        }
    }

    /**
     * Handle clear logs request
     */
    public function handle_clear_logs() {
        if (!current_user_can('manage_iare_crm_debug')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'iare-crm'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'iare_crm_clear_logs')) {
            wp_die(esc_html__('Security check failed.', 'iare-crm'));
        }

        $this->logger->clear_logs();
        
        wp_redirect(add_query_arg(['page' => 'iare-crm-debug', 'updated' => 'true'], admin_url('admin.php')));
        exit;
    }

    /**
     * Render the debug page
     */
    public function render() {
        if (!current_user_can('manage_iare_crm_debug')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'iare-crm'));
        }

        $settings = get_option(IARE_CRM_OPTION_SETTINGS, []);
        $debug_enabled = isset($settings['enable_debug']) ? (bool) $settings['enable_debug'] : false;
        $error_logs = $this->logger->get_error_logs();

        include IARE_CRM_PLUGIN_PATH . 'templates/admin/debug-page.php';
    }
}