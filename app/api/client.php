<?php

namespace IareCrm\Api;

use IareCrm\Helpers\Validator;

if (!defined('ABSPATH')) {
    exit;
}

class Client {

    private $base_url;
    private $version;
    private $timeout;

    public function __construct() {
        $this->base_url = IARE_CRM_API_BASE_URL;
        $this->version = IARE_CRM_API_VERSION;
        $this->timeout = 30;
    }

    public function test_connection($api_key) {
        if (empty($this->base_url)) {
            return [
                'success' => false,
                'message' => __('API base URL is not configured', 'iare-crm'),
                'data' => null
            ];
        }

        $response = $this->make_request('GET', '/auth/validate', [], $api_key);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                /* translators: %s: Error message from the API connection */
                'message' => sprintf(__('Connection error: %s', 'iare-crm'), $response->get_error_message()),
                'data' => null
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) === 200 && isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'message' => __('Connection successful', 'iare-crm'),
                'data' => $data['data'] ?? null
            ];
        }

                    $error_message = __('Authentication failed', 'iare-crm');
        if (isset($data['error']['message'])) {
            $error_message = $data['error']['message'];
        }

        return [
            'success' => false,
            'message' => $error_message,
            'data' => null
        ];
    }

    public function get_campaigns($api_key, $params = []) {
        $default_params = [
            'page' => 1,
            'limit' => 10,
            'status' => 'active',
            'capture_source' => 'wordpress'
        ];

        $params = array_merge($default_params, $params);
        $query_string = http_build_query($params);

        $response = $this->make_request('GET', '/campaigns?' . $query_string, [], $api_key);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'data' => null
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) === 200 && isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'message' => __('Campaigns retrieved successfully', 'iare-crm'),
                'data' => $data['data'] ?? null
            ];
        }

        return [
            'success' => false,
                            'message' => $data['error']['message'] ?? __('Failed to retrieve campaigns', 'iare-crm'),
            'data' => null
        ];
    }

    /**
     * Create a new lead
     * 
     * @param string $api_key API key
     * @param int $campaign_id Campaign ID
     * @param array $lead_data Lead data
     * @return array Response with creation result
     */
    public function create_lead($api_key, $campaign_id, $lead_data) {
        // Validate lead data
        $validation = Validator::validate_lead_data($lead_data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => __('Invalid lead data', 'iare-crm'),
                'data' => ['validation_errors' => $validation['errors']]
            ];
        }

        $endpoint = sprintf('/campaigns/%d/leads', intval($campaign_id));
        $response = $this->make_request('POST', $endpoint, $lead_data, $api_key);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'data' => null
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $data = json_decode($body, true);

        if ($response_code === 200 && isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'message' => __('Lead created successfully', 'iare-crm'),
                'data' => $data['data'] ?? null
            ];
        }

        return [
            'success' => false,
            'message' => $data['error']['message'] ?? __('Failed to create lead', 'iare-crm'),
            'data' => $data['error'] ?? null
        ];
    }

    /**
     * Make HTTP request to API
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $api_key API key
     * @return array|WP_Error Response or error
     */
    private function make_request($method, $endpoint, $data = [], $api_key = '') {
        $url = $this->base_url . IARE_CRM_API_ENDPOINT . $endpoint;

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'iaRe CRM WordPress Plugin/' . IARE_CRM_VERSION
        ];

        if (!empty($api_key)) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $args = [
            'method' => strtoupper($method),
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }

        /**
         * Filtro para argumentos de requisições da API
         * 
         * @param array $args Argumentos da requisição
         * @param string $method Método HTTP
         * @param string $endpoint Endpoint da API
         * @param array $data Dados da requisição
         * @param string $api_key Chave da API
         * @since 1.0.0
         */
        $args = apply_filters('iare_crm_api_request', $args, $method, $endpoint, $data, $api_key);

        return wp_remote_request($url, $args);
    }

    /**
     * Health check
     * 
     * @return array Response with health status
     */
    public function health_check() {
        if (empty($this->base_url)) {
            return [
                'success' => false,
                'message' => __('API base URL is not configured', 'iare-crm')
            ];
        }

        $response = $this->make_request('GET', '/health');

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return [
            'success' => wp_remote_retrieve_response_code($response) === 200,
                            'message' => $data['message'] ?? __('Health check completed', 'iare-crm'),
            'data' => $data
        ];
    }
} 