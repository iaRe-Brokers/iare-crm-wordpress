<?php

namespace IareCrm\Admin\Pages;

use IareCrm\Traits\Singleton;
use IareCrm\Admin\Settings\ApiSettings;

defined('ABSPATH') || exit;

class SettingsPage {
    use Singleton;

    private $api_settings;

    /**
     * Initialize settings page
     */
    protected function __construct() {
        $this->api_settings = ApiSettings::get_instance();
        
        add_action('admin_init', [$this, 'admin_init']);
        add_action('wp_ajax_iare_crm_test_connection', [$this, 'ajax_test_connection']);
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        $this->api_settings->register_settings();
    }

    /**
     * Render the settings page
     */
    public function render() {
        if (!current_user_can('manage_iare_crm')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'iare-crm'));
        }
        
        $this->handle_form_submission();
        $this->auto_test_connection();

        include IARE_CRM_PLUGIN_PATH . 'templates/admin/settings-page.php';
    }

    private function handle_form_submission() {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['iare_crm_settings_nonce'])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['iare_crm_settings_nonce']));
        if (!wp_verify_nonce($nonce, 'iare_crm_settings_action')) {
            add_settings_error('iare_crm_messages', 'invalid_nonce', __('Security check failed.', 'iare-crm'));
            return;
        }

        // Check if api_key is being submitted (form submission)
        if (isset($_POST['api_key'])) {
            $api_key = isset($_POST['api_key']) ? trim(sanitize_text_field(wp_unslash($_POST['api_key']))) : '';
            $this->save_settings($api_key);
        }
    }

    private function save_settings($api_key) {
        if (empty($api_key)) {
            add_settings_error('iare_crm_messages', 'empty_api_key', __('API key cannot be empty.', 'iare-crm'));
            return;
        }

        $api_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $api_key);
        
        if (empty($api_key)) {
            add_settings_error('iare_crm_messages', 'invalid_api_key', __('Invalid API key format.', 'iare-crm'));
            return;
        }

        // Clear old connection cache before saving new API key
        $old_api_key = $this->get_current_api_key();
        if (!empty($old_api_key)) {
            $old_transient_key = 'iare_crm_auto_connection_test_' . md5($old_api_key);
            delete_transient($old_transient_key);
        }

        $saved = $this->api_settings->save_api_key($api_key);
        
        if ($saved) {
            // Clear cache for new API key as well to force fresh test
            $new_transient_key = 'iare_crm_auto_connection_test_' . md5($api_key);
            delete_transient($new_transient_key);
            
            add_settings_error('iare_crm_messages', 'settings_saved', __('Settings saved successfully.', 'iare-crm'), 'success');
        } else {
            add_settings_error('iare_crm_messages', 'save_failed', __('Failed to save settings.', 'iare-crm'));
        }
    }

    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('iare_crm_ajax_nonce', 'nonce');

        if (!current_user_can('manage_iare_crm')) {
            wp_die(json_encode([
                'success' => false,
                'error' => [
                    'code' => 'unauthorized',
                    'message' => __('You do not have permission to perform this action.', 'iare-crm'),
                    'details' => null
                ]
            ]));
        }

        $api_key = isset($_POST['api_key']) ? trim(sanitize_text_field(wp_unslash($_POST['api_key']))) : '';
        
        if (empty($api_key)) {
            wp_die(json_encode([
                'success' => false,
                'error' => [
                    'code' => 'missing_api_key',
                    'message' => __('API key is required.', 'iare-crm'),
                    'details' => null
                ]
            ]));
        }

        $result = $this->api_settings->test_connection($api_key);
        
        wp_die(json_encode($result));
    }

    public function get_current_api_key() {
        return $this->api_settings->get_api_key();
    }

    public function display_settings_errors() {
        settings_errors('iare_crm_messages');
    }

    /**
     * Automatically test connection on page load with 30-minute cache
     */
    private function auto_test_connection() {
        $api_key = $this->get_current_api_key();
        
        // Don't test if no API key is set
        if (empty($api_key)) {
            return;
        }
        
        $transient_key = 'iare_crm_auto_connection_test_' . md5($api_key);
        $cached_result = get_transient($transient_key);
        
        // Return cached result if available
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Perform connection test
        $result = $this->api_settings->test_connection($api_key);
        
        // Cache the result for 30 minutes (1800 seconds)
        set_transient($transient_key, $result, 30 * MINUTE_IN_SECONDS);
        
        return $result;
    }

    /**
     * Get auto-tested connection status for display
     */
    public function get_auto_connection_status() {
        $api_key = $this->get_current_api_key();
        
        if (empty($api_key)) {
            return [
                'status' => 'no_key',
                'message' => __('No API key configured', 'iare-crm'),
                'data' => null
            ];
        }
        
        $transient_key = 'iare_crm_auto_connection_test_' . md5($api_key);
        $cached_result = get_transient($transient_key);
        
        if ($cached_result === false) {
            return [
                'status' => 'unknown',
                'message' => __('Connection status unknown', 'iare-crm'),
                'data' => null
            ];
        }
        
        return $cached_result;
    }
} 