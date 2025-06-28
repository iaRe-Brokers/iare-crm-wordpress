<?php

namespace IareCrm\Integrations\Elementor\Services;

use IareCrm\Traits\Singleton;

defined('ABSPATH') || exit;

/**
 * UTM Capture Service
 * 
 * Captures and processes UTM parameters from URL for form submissions
 */
class UtmCaptureService {
    use Singleton;

    const UTM_PARAMETERS = [
        'utm_source',
        'utm_medium', 
        'utm_campaign',
        'utm_content'
    ];

    const UTM_COOKIE_PREFIX = 'iare_crm_';

    /**
     * Initialize the service
     */
    protected function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_utm_capture_script']);
        add_filter('iare_crm_elementor_form_data', [$this, 'add_utm_to_form_data'], 10, 2);
    }

    /**
     * Enqueue UTM capture JavaScript
     */
    public function enqueue_utm_capture_script() {
        wp_enqueue_script(
            'iare-crm-utm-capture',
            IARE_CRM_PLUGIN_URL . 'assets/js/frontend/utm-capture.js',
            [],
            IARE_CRM_VERSION,
            true
        );
    }

    /**
     * Safely get GET parameter
     * 
     * @param string $key Parameter key
     * @return string|null Sanitized parameter value or null
     */
    private function get_safe_get_parameter($key) {
        $get_data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
        
        if (isset($get_data[$key]) && !empty($get_data[$key])) {
            return sanitize_text_field($get_data[$key]);
        }
        
        return null;
    }

    /**
     * Safely get all GET parameters
     * 
     * @return array Sanitized GET parameters
     */
    private function get_safe_get_parameters() {
        $get_data = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
        
        if (!is_array($get_data)) {
            return [];
        }
        
        $sanitized_data = [];
        foreach ($get_data as $key => $value) {
            $sanitized_data[sanitize_key($key)] = sanitize_text_field($value);
        }
        
        return $sanitized_data;
    }

    /**
     * Safely get UTM data from cookies
     * 
     * @return array Sanitized UTM data
     */
    private function get_safe_cookie_utm_data() {
        $utm_data = [];
        
        foreach (self::UTM_PARAMETERS as $param) {
            $cookie_name = self::UTM_COOKIE_PREFIX . $param;
            
            if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name])) {
                $utm_data[$param] = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
            }
        }
        
        return $utm_data;
    }



    /**
     * Get stored UTM parameters
     * 
     * @return array UTM parameters
     */
    public function get_utm_parameters() {
        return $this->get_safe_cookie_utm_data();
    }

    /**
     * Add UTM data to Elementor form data
     */
    public function add_utm_to_form_data($form_data, $raw_fields) {
        $utm_data = $this->get_safe_cookie_utm_data();

        if (!empty($utm_data)) {
            foreach ($utm_data as $utm_key => $utm_value) {
                $form_data[$utm_key] = $utm_value;
            }
        }

        return $form_data;
    }

    /**
     * Clear UTM data from cookies
     */
    public function clear_utm_data() {
        foreach (self::UTM_PARAMETERS as $param) {
            $cookie_name = self::UTM_COOKIE_PREFIX . $param;
            if (isset($_COOKIE[$cookie_name])) {
                setcookie($cookie_name, '', time() - 3600, '/');
                unset($_COOKIE[$cookie_name]);
            }
        }
        
        // Clear timestamp cookie
        $timestamp_cookie = self::UTM_COOKIE_PREFIX . 'timestamp';
        if (isset($_COOKIE[$timestamp_cookie])) {
            setcookie($timestamp_cookie, '', time() - 3600, '/');
            unset($_COOKIE[$timestamp_cookie]);
        }
    }

    /**
     * Get specific UTM parameter
     * 
     * @param string $parameter UTM parameter name
     * @return string|null UTM parameter value or null if not found
     */
    public function get_utm_parameter($parameter) {
        $utm_data = $this->get_utm_parameters();
        $sanitized_parameter = sanitize_key($parameter);
        return $utm_data[$sanitized_parameter] ?? null;
    }

    /**
     * Check if UTM data is available
     * 
     * @return bool True if UTM data exists
     */
    public function has_utm_data() {
        return !empty($this->get_utm_parameters());
    }

    /**
     * Debug method to check UTM capture status
     * 
     * @return array Debug information
     */
    public function debug_utm_status() {
        $request_uri = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        }

        // Safely get cookie data
        $utm_cookies = [];
        foreach (self::UTM_PARAMETERS as $param) {
            $cookie_name = self::UTM_COOKIE_PREFIX . $param;
            if (isset($_COOKIE[$cookie_name])) {
                $utm_cookies[$cookie_name] = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
            }
        }

        $debug_info = [
            'utm_cookies' => $utm_cookies,
            'utm_data' => $this->get_utm_parameters(),
            'get_params' => $this->get_safe_get_parameters(),
            'request_uri' => $request_uri,
            'has_utm' => $this->has_utm_data(),
            'javascript_enabled' => 'Check console for: IareCrmUtmCapture.debug()'
        ];

        return $debug_info;
    }
} 