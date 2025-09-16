<?php

namespace IareCrm\Api;

use IareCrm\Helpers\Validator;
use IareCrm\Helpers\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class Client {

    private $base_url;
    private $version;
    private $timeout;
    private $logger;

    public function __construct() {
        $this->base_url = IARE_CRM_API_BASE_URL;
        $this->version = IARE_CRM_API_VERSION;
        $this->timeout = 30;
        $this->logger = new Logger();
    }

    public function test_connection($api_key) {
        $this->logger->info('Iniciando teste de conexão com a API', ['api_key' => substr($api_key, 0, 5) . '...']);
        
        if (empty($this->base_url)) {
            $this->logger->error('URL base da API não configurada');
            return [
                'success' => false,
                'message' => __('API base URL is not configured', 'iare-crm'),
                'data' => null
            ];
        }

        $response = $this->make_request('GET', '/auth/validate', [], $api_key);

        if (is_wp_error($response)) {
            $this->logger->error('Erro na requisição de teste de conexão', [
                'error' => $response->get_error_message()
            ]);
            return [
                'success' => false,
                /* translators: %s: Error message from the API connection */
                'message' => sprintf(__('Connection error: %s', 'iare-crm'), $response->get_error_message()),
                'data' => null
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response_code = wp_remote_retrieve_response_code($response);

        $this->logger->info('Resposta da API recebida', [
            'response_code' => $response_code,
            'has_success' => isset($data['success']),
            'success_value' => $data['success'] ?? null
        ]);

        if ($response_code === 200 && isset($data['success']) && $data['success']) {
            $this->logger->info('Teste de conexão bem-sucedido');
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

        $this->logger->error('Falha na autenticação', [
            'response_code' => $response_code,
            'error_message' => $error_message
        ]);

        return [
            'success' => false,
            'message' => $error_message,
            'data' => null
        ];
    }

    public function get_campaigns($api_key, $params = []) {
        $this->logger->info('Obtendo campanhas da API', ['params' => $params]);
        
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
            $this->logger->error('Erro ao obter campanhas', [
                'error' => $response->get_error_message()
            ]);
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'data' => null
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response_code = wp_remote_retrieve_response_code($response);

        $this->logger->info('Resposta da API de campanhas recebida', [
            'response_code' => $response_code,
            'has_success' => isset($data['success']),
            'success_value' => $data['success'] ?? null
        ]);

        if ($response_code === 200 && isset($data['success']) && $data['success']) {
            $this->logger->info('Campanhas obtidas com sucesso', [
                'campaigns_count' => isset($data['data']['campaigns']) ? count($data['data']['campaigns']) : 0
            ]);
            return [
                'success' => true,
                'message' => __('Campaigns retrieved successfully', 'iare-crm'),
                'data' => $data['data'] ?? null
            ];
        }

        $error_message = $data['error']['message'] ?? __('Failed to retrieve campaigns', 'iare-crm');
        $this->logger->error('Falha ao obter campanhas', [
            'response_code' => $response_code,
            'error_message' => $error_message
        ]);

        return [
            'success' => false,
            'message' => $error_message,
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
        $this->logger->info('Criando lead na API', [
            'campaign_id' => $campaign_id,
            'lead_data' => $lead_data
        ]);
        
        // Validate lead data
        $validation = Validator::validate_lead_data($lead_data);
        if (!$validation['valid']) {
            $this->logger->error('Dados de lead inválidos', [
                'validation_errors' => $validation['errors']
            ]);
            return [
                'success' => false,
                'message' => __('Invalid lead data', 'iare-crm'),
                'data' => ['validation_errors' => $validation['errors']]
            ];
        }

        $endpoint = sprintf('/campaigns/%d/leads', intval($campaign_id));
        $response = $this->make_request('POST', $endpoint, $lead_data, $api_key);

        if (is_wp_error($response)) {
            $this->logger->error('Erro ao criar lead', [
                'error' => $response->get_error_message()
            ]);
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'data' => null
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $data = json_decode($body, true);

        $this->logger->info('Resposta da API ao criar lead', [
            'response_code' => $response_code,
            'has_success' => isset($data['success']),
            'success_value' => $data['success'] ?? null,
            'response_body' => $data
        ]);

        // Check for successful response codes (200 for updates, 201 for creations)
        if (($response_code === 200 || $response_code === 201) && isset($data['success']) && $data['success']) {
            $this->logger->info('Lead criado com sucesso', [
                'lead_id' => $data['data']['id'] ?? null
            ]);
            return [
                'success' => true,
                'message' => __('Lead created successfully', 'iare-crm'),
                'data' => $data['data'] ?? null
            ];
        }

        $error_message = $data['error']['message'] ?? __('Failed to create lead', 'iare-crm');
        $error_data = $data['error'] ?? null;
        
        $this->logger->error('Falha ao criar lead', [
            'response_code' => $response_code,
            'error_message' => $error_message,
            'error_data' => $error_data
        ]);

        return [
            'success' => false,
            'message' => $error_message,
            'data' => $error_data
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
        $this->logger->info('Realizando requisição HTTP para a API', [
            'method' => $method,
            'endpoint' => $endpoint,
            'data_keys' => array_keys($data),
            'has_api_key' => !empty($api_key)
        ]);
        
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
            // Log the full data being sent, but not sensitive information
            $this->logger->info('Dados sendo enviados para a API', [
                'sanitized_data' => [
                    'name' => $data['name'] ?? '',
                    'phone_number' => $data['phone_number'] ?? '',
                    'email' => $data['email'] ?? '',
                    'has_additional_info' => !empty($data['additional_info']),
                    'capture_source' => $data['capture_source'] ?? ''
                ]
            ]);
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

        $this->logger->info('Argumentos da requisição preparados', [
            'url' => $url,
            'method' => $args['method'],
            'timeout' => $args['timeout'],
            'has_body' => !empty($args['body'])
        ]);

        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('Erro na requisição HTTP', [
                'error_code' => $response->get_error_code(),
                'error_message' => $response->get_error_message()
            ]);
        } else {
            $this->logger->info('Requisição HTTP concluída com sucesso', [
                'response_code' => wp_remote_retrieve_response_code($response)
            ]);
        }

        return $response;
    }

    /**
     * Health check
     * 
     * @return array Response with health status
     */
    public function health_check() {
        $this->logger->info('Executando health check da API');
        
        if (empty($this->base_url)) {
            $this->logger->error('URL base da API não configurada no health check');
            return [
                'success' => false,
                'message' => __('API base URL is not configured', 'iare-crm')
            ];
        }

        $response = $this->make_request('GET', '/health');

        if (is_wp_error($response)) {
            $this->logger->error('Erro no health check', [
                'error' => $response->get_error_message()
            ]);
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response_code = wp_remote_retrieve_response_code($response);

        $this->logger->info('Health check concluído', [
            'response_code' => $response_code,
            'success' => $response_code === 200
        ]);

        return [
            'success' => $response_code === 200,
            'message' => $data['message'] ?? __('Health check completed', 'iare-crm'),
            'data' => $data
        ];
    }
} 