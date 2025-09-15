<?php

namespace IareCrm\Api;

use IareCrm\App\Services\GeolocationService;

/**
 * Geolocation AJAX Endpoints
 * 
 * Handles AJAX requests for automatic location capture functionality
 */
class GeolocationEndpoints {
    
    /**
     * Initialize AJAX endpoints
     */
    public static function init() {
        // AJAX endpoint for logged-in users
        add_action('wp_ajax_iare_crm_get_location', [self::class, 'get_location']);
        
        // AJAX endpoint for non-logged-in users (public forms)
        add_action('wp_ajax_nopriv_iare_crm_get_location', [self::class, 'get_location']);
        
        // AJAX endpoint for testing API connectivity
        add_action('wp_ajax_iare_crm_test_location_api', [self::class, 'test_location_api']);
        add_action('wp_ajax_nopriv_iare_crm_test_location_api', [self::class, 'test_location_api']);
    }
    
    /**
     * Get location data via AJAX
     * 
     * @return void
     */
    public static function get_location() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'iare_crm_location_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed'
            ], 403);
            return;
        }
        
        try {
            $geolocation_service = new GeolocationService();
            $location_data = $geolocation_service->getLocationByIp();
            
            if ($location_data && $location_data['status'] === 'success') {
                $formatted_location = $geolocation_service->formatLocationData($location_data);
                
                wp_send_json_success([
                    'location' => $formatted_location,
                    'raw_data' => $location_data,
                    'cached' => $geolocation_service->isDataCached(),
                    'message' => 'Location data retrieved successfully'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Failed to retrieve location data',
                    'error' => $location_data['message'] ?? 'Unknown error'
                ], 500);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'An error occurred while retrieving location data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test location API connectivity via AJAX
     * 
     * @return void
     */
    public static function test_location_api() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'iare_crm_location_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed'
            ], 403);
            return;
        }
        
        // Only allow administrators to test API connectivity
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'Insufficient permissions'
            ], 403);
            return;
        }
        
        try {
            $geolocation_service = new GeolocationService();
            $test_result = $geolocation_service->testApiConnectivity();
            
            if ($test_result['success']) {
                wp_send_json_success([
                    'message' => 'API connectivity test successful',
                    'response_time' => $test_result['response_time'],
                    'api_status' => $test_result['api_status']
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'API connectivity test failed',
                    'error' => $test_result['error']
                ], 500);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'An error occurred during API connectivity test',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Enqueue AJAX scripts and localize data
     * 
     * @return void
     */
    public static function enqueue_ajax_scripts() {
        // Only enqueue on pages with Elementor forms
        if (!is_admin() && (is_page() || is_single())) {
            wp_enqueue_script(
                'iare-crm-geolocation-ajax',
                plugin_dir_url(__FILE__) . '../assets/js/geolocation-ajax.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            wp_localize_script('iare-crm-geolocation-ajax', 'iareCrmGeolocation', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('iare_crm_location_nonce'),
                'strings' => [
                    'loading' => __('Loading location...', 'iare-crm'),
                    'error' => __('Failed to load location data', 'iare-crm'),
                    'success' => __('Location loaded successfully', 'iare-crm')
                ]
            ]);
        }
    }
    
    /**
     * Add admin AJAX scripts for testing
     * 
     * @return void
     */
    public static function enqueue_admin_ajax_scripts() {
        $screen = get_current_screen();
        
        // Only enqueue on relevant admin pages
        if ($screen && strpos($screen->id, 'iare-crm') !== false) {
            wp_enqueue_script(
                'iare-crm-admin-geolocation',
                plugin_dir_url(__FILE__) . '../assets/js/admin-geolocation.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            wp_localize_script('iare-crm-admin-geolocation', 'iareCrmAdminGeolocation', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('iare_crm_location_nonce'),
                'strings' => [
                    'testing' => __('Testing API connectivity...', 'iare-crm'),
                    'success' => __('API test successful', 'iare-crm'),
                    'error' => __('API test failed', 'iare-crm')
                ]
            ]);
        }
    }
}