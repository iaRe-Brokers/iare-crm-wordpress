<?php

namespace IareCrm\Admin\Settings;

use IareCrm\Traits\Singleton;
use IareCrm\Helpers\Validator;
use IareCrm\Api\Client;

defined('ABSPATH') || exit;

class ApiSettings {
    use Singleton;

    private $settings_group = 'iare_crm_settings';
    private $api_client;

    /**
     * Initialize API settings
     */
    protected function __construct() {
        $this->api_client = new Client();
    }

    /**
     * Get current API key
     * 
     * @return string The API key or empty string if not set
     */
    public function get_api_key() {
        return get_option(IARE_CRM_OPTION_API_KEY, '');
    }

    /**
     * Save API key
     * 
     * @param string $api_key The API key to save
     * @return bool True on success, false on failure
     */
    public function save_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }

        $api_key = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $api_key));
        
        if (empty($api_key)) {
            return false;
        }

        $saved = update_option(IARE_CRM_OPTION_API_KEY, $api_key);

        if ($saved) {
            delete_transient('iare_crm_connection_status');
            // Clear all auto connection test cache when API key changes
            $transient_key = 'iare_crm_auto_connection_test_' . md5($api_key);
            delete_transient($transient_key);
        }
        
        return $saved;
    }

    /**
     * Get other settings (excluding API key)
     * 
     * @return array Other settings
     */
    public function get_settings() {
        return get_option(IARE_CRM_OPTION_SETTINGS, $this->get_default_settings());
    }

    /**
     * Save other settings (excluding API key)
     * 
     * @param array $settings Settings to save
     * @return bool True on success, false on failure
     */
    public function save_settings($settings) {
        $sanitized_settings = $this->sanitize_settings($settings);
        return update_option(IARE_CRM_OPTION_SETTINGS, $sanitized_settings);
    }

    /**
     * Test API connection
     * 
     * @param string $api_key The API key to test
     * @return array Response with test result
     */
    public function test_connection($api_key) {
        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'missing_api_key',
                    'message' => __('API key is required for testing connection.', 'iare-crm'),
                    'details' => null
                ]
            ];
        }

        $api_key = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $api_key));
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'invalid_api_key',
                    'message' => __('Invalid API key format.', 'iare-crm'),
                    'details' => null
                ]
            ];
        }

        $result = $this->api_client->test_connection($api_key);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ];
        } else {
            return [
                'success' => false,
                'error' => [
                    'code' => 'connection_failed',
                    'message' => $result['message'],
                    'details' => $result['data']
                ]
            ];
        }
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register API key setting
        register_setting(
            $this->settings_group,
            IARE_CRM_OPTION_API_KEY,
            [
                'sanitize_callback' => [$this, 'sanitize_api_key'],
                'default' => ''
            ]
        );

        // Register other settings
        register_setting(
            $this->settings_group,
            IARE_CRM_OPTION_SETTINGS,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings()
            ]
        );

        // Add settings sections
        add_settings_section(
            'iare_crm_api_section',
            __('API Configuration', 'iare-crm'),
            [$this, 'api_section_callback'],
            'iare-crm-settings'
        );

        // Add API key field
        add_settings_field(
            'api_key',
            __('API Key', 'iare-crm'),
            [$this, 'api_key_field_callback'],
            'iare-crm-settings',
            'iare_crm_api_section'
        );
    }

    /**
     * Get default settings (excluding API key)
     */
    private function get_default_settings() {
        return [
            'default_campaign_id' => '',
            'enable_logging' => true,
            'cache_duration' => 3600,
        ];
    }

    /**
     * Sanitize API key
     */
    public function sanitize_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }
        return trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $api_key));
    }

    /**
     * Sanitize other settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        if (isset($input['default_campaign_id'])) {
            $sanitized['default_campaign_id'] = absint($input['default_campaign_id']);
        }

        if (isset($input['enable_logging'])) {
            $sanitized['enable_logging'] = (bool) $input['enable_logging'];
        }

        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
        }

        return array_merge($this->get_default_settings(), $sanitized);
    }

    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . esc_html(__('Configure your iaRe CRM API connection settings.', 'iare-crm')) . '</p>';
    }

    /**
     * API key field callback
     */
    public function api_key_field_callback() {
        $api_key = $this->get_api_key();
        
        echo '<input type="password" id="api_key" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html(__('Enter your iaRe CRM API key.', 'iare-crm')) . '</p>';
    }
} 