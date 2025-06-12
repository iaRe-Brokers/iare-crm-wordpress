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
        'utm_content',
        'utm_term'
    ];

    const UTM_SESSION_KEY = 'iare_crm_utm_data';

    /**
     * Initialize the service
     */
    protected function __construct() {
        add_action('plugins_loaded', [$this, 'capture_utm_parameters'], 5);
        add_action('wp_loaded', [$this, 'capture_utm_parameters'], 5);
        add_action('template_redirect', [$this, 'capture_utm_parameters'], 5);
        add_filter('iare_crm_elementor_form_data', [$this, 'add_utm_to_form_data'], 10, 2);
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
     * Safely get UTM data from session
     * 
     * @return array Sanitized UTM data
     */
    private function get_safe_session_utm_data() {
        if (!session_id()) {
            session_start();
        }

        // Check if session data exists
        $session_data = filter_var_array($_SESSION, FILTER_SANITIZE_STRING);
        
        if (!isset($session_data[self::UTM_SESSION_KEY])) {
            return [];
        }

        $utm_data = $session_data[self::UTM_SESSION_KEY];
        
        if (!is_array($utm_data)) {
            return [];
        }

        // Additional sanitization for safety
        $sanitized_data = [];
        foreach ($utm_data as $key => $value) {
            $sanitized_data[sanitize_key($key)] = sanitize_text_field($value);
        }
        
        return $sanitized_data;
    }

    /**
     * Capture UTM parameters from URL and store in session
     */
    public function capture_utm_parameters() {
        if (!session_id()) {
            session_start();
        }

        $utm_data = [];
        $has_utm = false;

        // Capture UTM parameters from current request
        foreach (self::UTM_PARAMETERS as $param) {
            $param_value = $this->get_safe_get_parameter($param);
            if (!empty($param_value)) {
                $utm_data[$param] = $param_value;
                $has_utm = true;
            }
        }

        if ($has_utm) {
            $_SESSION[self::UTM_SESSION_KEY] = $utm_data;
        }
    }

    /**
     * Get stored UTM parameters
     * 
     * @return array UTM parameters
     */
    public function get_utm_parameters() {
        return $this->get_safe_session_utm_data();
    }

    /**
     * Add UTM data to Elementor form data
     */
    public function add_utm_to_form_data($form_data, $raw_fields) {
        $utm_data = $this->get_safe_session_utm_data();

        if (!empty($utm_data)) {
            foreach ($utm_data as $utm_key => $utm_value) {
                $form_data[$utm_key] = $utm_value;
            }
        }

        return $form_data;
    }

    /**
     * Clear UTM data from session
     */
    public function clear_utm_data() {
        if (!session_id()) {
            session_start();
        }

        if (isset($_SESSION[self::UTM_SESSION_KEY])) {
            unset($_SESSION[self::UTM_SESSION_KEY]);
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
        if (!session_id()) {
            session_start();
        }

        $request_uri = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        }

        // Safely get session data
        $safe_session_data = filter_var_array($_SESSION, FILTER_SANITIZE_STRING);

        $debug_info = [
            'session_id' => session_id(),
            'session_data' => $safe_session_data,
            'utm_data' => $this->get_utm_parameters(),
            'get_params' => $this->get_safe_get_parameters(),
            'request_uri' => $request_uri,
            'has_utm' => $this->has_utm_data()
        ];

        return $debug_info;
    }
} 