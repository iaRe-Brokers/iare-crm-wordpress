<?php

namespace IareCrm\Integrations\Elementor\FormActions;

use IareCrm\Api\Client;
use IareCrm\Helpers\Validator;
use IareCrm\Integrations\Elementor\Services\FieldMapperService;
use IareCrm\App\Services\GeolocationService;
use ElementorPro\Modules\Forms\Classes\Action_Base;

defined('ABSPATH') || exit;

/**
 * iaRe CRM Action for Elementor Forms
 * 
 * Custom Elementor form action which creates leads in iaRe CRM after form submission
 */
class IareCrmAction extends Action_Base {

    const MAX_ADDITIONAL_INFO = 15;

    /**
     * Get action name
     * 
     * @return string
     */
    public function get_name() {
        return 'iare_crm';
    }

    /**
     * Get action label
     * 
     * @return string
     */
    public function get_label() {
        return esc_html__('iaRe CRM', 'iare-crm');
    }

    /**
     * Register action controls
     * 
     * @param \Elementor\Widget_Base $widget
     */
    public function register_settings_section($widget) {
        if (!$widget || !method_exists($widget, 'start_controls_section')) {
            return;
        }

        $widget->start_controls_section(
            'section_iare_crm',
            [
                'label' => esc_html__('iaRe CRM', 'iare-crm'),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        // Campaign selection
        $widget->add_control(
            'iare_crm_campaign',
            [
                'label' => esc_html__('Campaigns', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_campaigns_options(),
                'multiple' => true,
                'description' => esc_html__('Select one or more campaigns to distribute leads to.', 'iare-crm'),
                'label_block' => true,
            ]
        );

        // Capture source field
        $widget->add_control(
            'iare_crm_capture_source',
            [
                'label' => esc_html__('Capture Source', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'WordPress',
                'placeholder' => 'WordPress',
                'description' => esc_html__('Source of lead capture (leave blank to use "WordPress" as default).', 'iare-crm'),
            ]
        );

        // Field mapping section
        $widget->add_control(
            'iare_crm_field_mapping_heading',
            [
                'label' => esc_html__('Field Mapping', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Name field mapping (required)
        $widget->add_control(
            'iare_crm_name_field',
            [
                'label' => esc_html__('Name Field', 'iare-crm') . ' *',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget),
                'description' => esc_html__('Select which form field contains the lead name.', 'iare-crm'),
            ]
        );

        // Surname field mapping (optional)
        $widget->add_control(
            'iare_crm_surname_field',
            [
                'label' => esc_html__('Surname Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the lead surname (optional).', 'iare-crm'),
            ]
        );

        // Phone country code field
        $widget->add_control(
            'iare_crm_phone_country_code_field',
            [
                'label' => esc_html__('Phone Country Code Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the phone country code (optional).', 'iare-crm'),
            ]
        );

        // Default country code if no field is mapped
        $widget->add_control(
            'iare_crm_default_country_code',
            [
                'label' => esc_html__('Default Country Code', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '55',
                'placeholder' => '55',
                'description' => esc_html__('Default country code to use if no field is mapped (e.g., 55 for Brazil).', 'iare-crm'),
                'condition' => [
                    'iare_crm_phone_country_code_field' => '',
                ],
            ]
        );

        // Phone number field mapping (required)
        $widget->add_control(
            'iare_crm_phone_number_field',
            [
                'label' => esc_html__('Phone Number Field', 'iare-crm') . ' *',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget),
                'description' => esc_html__('Select which form field contains the lead phone number.', 'iare-crm'),
            ]
        );

        // Email field mapping (optional)
        $widget->add_control(
            'iare_crm_email_field',
            [
                'label' => esc_html__('Email Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the lead email (optional).', 'iare-crm'),
            ]
        );

        // Enterprise field mapping (optional)
        $widget->add_control(
            'iare_crm_enterprise_field',
            [
                'label' => esc_html__('Enterprise Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the enterprise name (optional).', 'iare-crm'),
            ]
        );

        // Position field mapping (optional)
        $widget->add_control(
            'iare_crm_position_field',
            [
                'label' => esc_html__('Position Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the position (optional).', 'iare-crm'),
            ]
        );

        // Profession field mapping (optional)
        $widget->add_control(
            'iare_crm_profession_field',
            [
                'label' => esc_html__('Profession Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the profession (optional).', 'iare-crm'),
            ]
        );

        // Gender field mapping (optional)
        $widget->add_control(
            'iare_crm_gender_field',
            [
                'label' => esc_html__('Gender Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the gender (optional).', 'iare-crm'),
            ]
        );

        // Location capture section
        $widget->add_control(
            'iare_crm_location_heading',
            [
                'label' => esc_html__('Location Settings', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // Automatic location capture switch
        $widget->add_control(
            'iare_crm_auto_location',
            [
                'label' => esc_html__('Automatic Location Capture', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'iare-crm'),
                'label_off' => esc_html__('No', 'iare-crm'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Enable automatic location detection based on visitor IP address using IP-API.com service.', 'iare-crm'),
            ]
        );

        // Manual location mapping heading
        $widget->add_control(
            'iare_crm_manual_location_heading',
            [
                'label' => esc_html__('Manual Location Mapping', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'iare_crm_auto_location!' => 'yes',
                ],
            ]
        );

        // City field mapping (optional)
        $widget->add_control(
            'iare_crm_city_field',
            [
                'label' => esc_html__('City Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the city (optional).', 'iare-crm'),
                'condition' => [
                    'iare_crm_auto_location!' => 'yes',
                ],
            ]
        );

        // State field mapping (optional)
        $widget->add_control(
            'iare_crm_state_field',
            [
                'label' => esc_html__('State Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the state (optional).', 'iare-crm'),
                'condition' => [
                    'iare_crm_auto_location!' => 'yes',
                ],
            ]
        );

        // Country field mapping (optional)
        $widget->add_control(
            'iare_crm_country_field',
            [
                'label' => esc_html__('Country Field', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_form_fields_options_safe($widget, true),
                'description' => esc_html__('Select which form field contains the country (optional).', 'iare-crm'),
                'condition' => [
                    'iare_crm_auto_location!' => 'yes',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * Run action after form submission
     * 
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function run($record, $ajax_handler) {
        $settings = $record->get('form_settings');

        // Validar campanhas usando o Validator
        $campaigns_input = $settings['iare_crm_campaign'] ?? [];
        $validation_result = Validator::validate_campaigns($campaigns_input);
        
        if (!$validation_result['valid']) {
            return;
        }
        
        $campaigns = $validation_result['campaigns'];

        // Validate field mapping
        require_once IARE_CRM_PLUGIN_PATH . 'app/integrations/elementor/services/field-mapper-service.php';
        $validation = FieldMapperService::validate_field_mapping($settings);
        if (!$validation['valid']) {
            return;
        }

        // Obter identificador único do formulário
        $post_id = get_the_ID();
        $widget_id = $record->get('form_settings')['id'] ?? 'unknown';
        $form_identifier = $this->generate_elementor_identifier($post_id, $widget_id);
        
        // Selecionar próxima campanha na rotação
        $selected_campaign = $this->get_next_campaign($campaigns, $form_identifier);
        
        if (!$selected_campaign) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('iaRe CRM: Failed to select campaign for form ' . $form_identifier);
            }
            return;
        }

        $raw_fields = $record->get('fields');

        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = $field['value'];
        }

        // Build lead data from mapped fields
        $lead_data = $this->build_lead_data($settings, $fields, $raw_fields);

        // Apply filter to allow UTM data injection
        $lead_data = apply_filters('iare_crm_elementor_form_data', $lead_data, $raw_fields);

        $api_key = get_option(IARE_CRM_OPTION_API_KEY, '');
        if (empty($api_key)) {
            return;
        }

        $api_client = new Client();
        $result = $api_client->create_lead($api_key, $selected_campaign, $lead_data);
    }

    /**
     * Build lead data from form settings and submitted fields
     * 
     * @param array $settings Form settings
     * @param array $fields Submitted form fields
     * @param array $raw_fields Raw form fields with labels
     * @return array Lead data
     */
    private function build_lead_data($settings, $fields, $raw_fields = []) {
        $lead_data = [];

        // Name (required)
        if (!empty($settings['iare_crm_name_field']) && !empty($fields[$settings['iare_crm_name_field']])) {
            $lead_data['name'] = sanitize_text_field($fields[$settings['iare_crm_name_field']]);
        }

        // Surname (optional)
        if (!empty($settings['iare_crm_surname_field']) && !empty($fields[$settings['iare_crm_surname_field']])) {
            $lead_data['surname'] = sanitize_text_field($fields[$settings['iare_crm_surname_field']]);
        }

        // Phone country code
        if (!empty($settings['iare_crm_phone_country_code_field']) && !empty($fields[$settings['iare_crm_phone_country_code_field']])) {
            $lead_data['phone_country_code'] = sanitize_text_field($fields[$settings['iare_crm_phone_country_code_field']]);
        } else {
            $lead_data['phone_country_code'] = sanitize_text_field($settings['iare_crm_default_country_code'] ?? '55');
        }

        // Phone number (required)
        if (!empty($settings['iare_crm_phone_number_field']) && !empty($fields[$settings['iare_crm_phone_number_field']])) {
            $lead_data['phone_number'] = sanitize_text_field($fields[$settings['iare_crm_phone_number_field']]);
        }

        // Email (optional)
        if (!empty($settings['iare_crm_email_field']) && !empty($fields[$settings['iare_crm_email_field']])) {
            $lead_data['email'] = sanitize_email($fields[$settings['iare_crm_email_field']]);
        }

        // Capture source
        $capture_source = !empty($settings['iare_crm_capture_source']) ? 
            sanitize_text_field($settings['iare_crm_capture_source']) : 'WordPress';
        $lead_data['capture_source'] = $capture_source;

        // Enterprise (optional)
        if (!empty($settings['iare_crm_enterprise_field']) && !empty($fields[$settings['iare_crm_enterprise_field']])) {
            $lead_data['enterprise'] = sanitize_text_field($fields[$settings['iare_crm_enterprise_field']]);
        }

        // Position (optional)
        if (!empty($settings['iare_crm_position_field']) && !empty($fields[$settings['iare_crm_position_field']])) {
            $lead_data['position'] = sanitize_text_field($fields[$settings['iare_crm_position_field']]);
        }

        // Profession (optional)
        if (!empty($settings['iare_crm_profession_field']) && !empty($fields[$settings['iare_crm_profession_field']])) {
            $lead_data['profession'] = sanitize_text_field($fields[$settings['iare_crm_profession_field']]);
        }

        // Gender (optional)
        if (!empty($settings['iare_crm_gender_field']) && !empty($fields[$settings['iare_crm_gender_field']])) {
            $lead_data['gender'] = sanitize_text_field($fields[$settings['iare_crm_gender_field']]);
        }

        // Location data (automatic or manual)
        $this->process_location_data($settings, $fields, $lead_data);

        // Additional info from unmapped fields
        $additional_info = $this->build_additional_info($settings, $fields, $raw_fields);
        if (!empty($additional_info)) {
            $lead_data['additional_info'] = $additional_info;
        }

        /**
         * Filtro para dados do lead antes do envio
         * 
         * @param array $lead_data Dados do lead
         * @param array $settings Configurações do formulário
         * @param array $fields Campos enviados
         * @since 1.0.0
         */
        return apply_filters('iare_crm_lead_data', $lead_data, $settings, $fields);
    }

    /**
     * Process location data (automatic or manual)
     * 
     * @param array $settings Form settings
     * @param array $fields Submitted form fields
     * @param array &$lead_data Lead data array (passed by reference)
     * @return void
     */
    private function process_location_data($settings, $fields, &$lead_data) {
        $auto_location_enabled = !empty($settings['iare_crm_auto_location']) && $settings['iare_crm_auto_location'] === 'yes';
        
        if ($auto_location_enabled) {
            // Use automatic location capture
            $geolocation_service = new GeolocationService();
            $location_data = $geolocation_service->getLocationByIp();
            
            if ($location_data && $location_data['status'] === 'success') {
                $formatted_location = $geolocation_service->formatLocationData($location_data);
                
                if (!empty($formatted_location['city'])) {
                    $lead_data['city'] = $formatted_location['city'];
                }
                
                if (!empty($formatted_location['state'])) {
                    $lead_data['state'] = $formatted_location['state'];
                }
                
                if (!empty($formatted_location['country'])) {
                    $lead_data['country'] = $formatted_location['country'];
                }
            } else {
                // Log failed automatic location capture
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('iaRe CRM: Failed to capture automatic location data');
                }
            }
        } else {
            // Use manual field mapping
            if (!empty($settings['iare_crm_city_field']) && !empty($fields[$settings['iare_crm_city_field']])) {
                $lead_data['city'] = sanitize_text_field($fields[$settings['iare_crm_city_field']]);
            }
            
            if (!empty($settings['iare_crm_state_field']) && !empty($fields[$settings['iare_crm_state_field']])) {
                $lead_data['state'] = sanitize_text_field($fields[$settings['iare_crm_state_field']]);
            }
            
            if (!empty($settings['iare_crm_country_field']) && !empty($fields[$settings['iare_crm_country_field']])) {
                $lead_data['country'] = sanitize_text_field($fields[$settings['iare_crm_country_field']]);
            }
        }
    }

    /**
     * Build additional info from unmapped form fields
     * 
     * @param array $settings Form settings
     * @param array $fields Submitted form fields
     * @param array $raw_fields Raw form fields with labels
     * @return array Additional info array
     */
    private function build_additional_info($settings, $fields, $raw_fields = []) {
        $mapped_fields = array_filter([
            $settings['iare_crm_name_field'] ?? '',
            $settings['iare_crm_surname_field'] ?? '',
            $settings['iare_crm_phone_country_code_field'] ?? '',
            $settings['iare_crm_phone_number_field'] ?? '',
            $settings['iare_crm_email_field'] ?? '',
            $settings['iare_crm_enterprise_field'] ?? '',
            $settings['iare_crm_position_field'] ?? '',
            $settings['iare_crm_profession_field'] ?? '',
            $settings['iare_crm_gender_field'] ?? '',
            $settings['iare_crm_city_field'] ?? '',
            $settings['iare_crm_state_field'] ?? '',
            $settings['iare_crm_country_field'] ?? ''
        ]);

        $additional_info = [];
        $count = 0;

        // Always add the page URL as the first additional info
        $current_url = '';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $current_url = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $current_url = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
        } else {
            $current_url = get_permalink();
        }
        
        if (!empty($current_url) && $count < self::MAX_ADDITIONAL_INFO) {
            $clean_url = strtok($current_url, '?');
            
            $additional_info[] = [
                'title' => __('URL Cadastro', 'iare-crm'),
                'value' => $clean_url
            ];
            $count++;
        }

        foreach ($fields as $field_id => $field_value) {
            // Skip if field is already mapped or if we've reached the limit
            if (in_array($field_id, $mapped_fields) || $count >= self::MAX_ADDITIONAL_INFO) {
                continue;
            }

            $value = sanitize_text_field($field_value);

            if (empty($value)) {
                continue;
            }

            $title = $field_id;
            
            if (!empty($raw_fields[$field_id]) && isset($raw_fields[$field_id]['title'])) {
                $title = $raw_fields[$field_id]['title'];
            } elseif (!empty($raw_fields[$field_id]) && isset($raw_fields[$field_id]['field_label'])) {
                $title = $raw_fields[$field_id]['field_label'];
            }

            $title = sanitize_text_field($title);

            // Ensure title is not longer than 50 characters (API limit)
            if (strlen($title) > 50) {
                $title = substr($title, 0, 47) . '...';
            }

            // Ensure value is not longer than 255 characters (API limit)
            if (strlen($value) > 255) {
                $value = substr($value, 0, 252) . '...';
            }

            $additional_info[] = [
                'title' => $title,
                'value' => $value
            ];

            $count++;
        }

        return $additional_info;
    }

    /**
     * Get campaigns options for select control
     * 
     * @return array Campaigns options
     */
    private function get_campaigns_options() {
        $options = ['' => esc_html__('Select Campaign', 'iare-crm')];

        $campaigns = $this->get_cached_campaigns();

        if (!empty($campaigns)) {
            foreach ($campaigns as $campaign) {
                $options[$campaign['id']] = esc_html($campaign['name']);
            }
        } else {
            $options[''] = esc_html__('No campaigns available (check API key)', 'iare-crm');
        }

        return $options;
    }

    /**
     * Get cached campaigns from API
     * 
     * @return array Campaigns list
     */
    private function get_cached_campaigns() {
        $cache_key = 'iare_crm_campaigns';
        $campaigns = get_transient($cache_key);

        if ($campaigns === false) {
            $api_key = get_option(IARE_CRM_OPTION_API_KEY, '');
            
            if (!empty($api_key)) {
                $api_client = new Client();
                $result = $api_client->get_campaigns($api_key);

                if ($result['success'] && !empty($result['data']['campaigns'])) {
                    $campaigns = $result['data']['campaigns'];
                    set_transient($cache_key, $campaigns, 5 * MINUTE_IN_SECONDS);
                } else {
                    $campaigns = [];
                    set_transient($cache_key, $campaigns, 1 * MINUTE_IN_SECONDS);
                }
            } else {
                $campaigns = [];
            }
        }

        return $campaigns;
    }

    /**
     * Get form fields options for select controls
     * 
     * @param \Elementor\Widget_Base $widget
     * @param bool $include_empty Whether to include empty option
     * @return array Form fields options
     */
    private function get_form_fields_options($widget, $include_empty = false) {
        $options = [];

        if ($include_empty) {
            $options[''] = esc_html__('-- Not mapped --', 'iare-crm');
        }

        if (!$widget || !method_exists($widget, 'get_settings')) {
            return $options;
        }

        // Safely get form fields using direct access to avoid triggering full widget initialization
        try {
            // First try to get just the form_fields setting directly
            $form_fields = $widget->get_settings('form_fields');
            
            if (!empty($form_fields) && is_array($form_fields)) {
                foreach ($form_fields as $field) {
                    if (is_array($field) && !empty($field['custom_id'])) {
                        $field_label = !empty($field['field_label']) ? $field['field_label'] : $field['custom_id'];
                        $options[$field['custom_id']] = esc_html($field_label);
                    }
                }
            }
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
                error_log('iaRe CRM: Could not get form fields safely: ' . $e->getMessage());
            }
        }

        return $options;
    }

    /**
     * Get form fields options safely (with additional error handling)
     * 
     * @param \Elementor\Widget_Base $widget
     * @param bool $include_empty Whether to include empty option
     * @return array Form fields options
     */
    private function get_form_fields_options_safe($widget, $include_empty = false) {
        $options = [];

        if ($include_empty) {
            $options[''] = esc_html__('-- Not mapped --', 'iare-crm');
        }

        // Try to get form fields options, but fail gracefully
        try {
            $field_options = $this->get_form_fields_options($widget, false);
            // Merge only the field options (excluding empty option to avoid duplication)
            $options = array_merge($options, $field_options);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
                error_log('iaRe CRM: Error in get_form_fields_options_safe: ' . $e->getMessage());
            }
        }

        // Ensure we always have at least one option
        if (empty($options) || (count($options) === 1 && isset($options['']))) {
            if (!isset($options[''])) {
                $options[''] = esc_html__('-- Not mapped --', 'iare-crm');
            }
        }

        return $options;
    }

    /**
     * Check if logging is enabled
     * 
     * @return bool
     */
    private function is_logging_enabled() {
        $settings = get_option(IARE_CRM_OPTION_SETTINGS, []);
        return $settings['enable_logging'] ?? true;
    }

    /**
     * Gerar identificador único para formulário Elementor
     * 
     * @param int $post_id ID do post
     * @param string $widget_id ID do widget
     * @return string Identificador único
     */
    private function generate_elementor_identifier($post_id, $widget_id) {
        return 'elementor_' . $post_id . '_' . $widget_id;
    }

    /**
     * Obter próxima campanha na rotação
     * 
     * @param array $campaign_ids Array de IDs das campanhas
     * @param string $form_identifier Identificador único do formulário
     * @return string|null ID da próxima campanha ou null se erro
     */
    private function get_next_campaign($campaign_ids, $form_identifier) {
        // Se apenas uma campanha, retornar diretamente
        if (count($campaign_ids) === 1) {
            return $campaign_ids[0];
        }

        // Obter dados de rotação
        $rotation_data = $this->get_rotation_data($form_identifier);
        
        // Verificar se as campanhas mudaram
        if ($rotation_data['campaigns'] !== $campaign_ids) {
            // Resetar rotação com novas campanhas
            $rotation_data = [
                'campaigns' => $campaign_ids,
                'last_used_index' => -1,
                'last_used_id' => '',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'total_leads' => 0
            ];
        }
        
        // Calcular próximo índice
        $next_index = ($rotation_data['last_used_index'] + 1) % count($campaign_ids);
        $selected_campaign = $campaign_ids[$next_index];
        
        // Atualizar dados de rotação
        $rotation_data['last_used_index'] = $next_index;
        $rotation_data['last_used_id'] = $selected_campaign;
        $rotation_data['updated_at'] = current_time('mysql');
        $rotation_data['total_leads']++;
        
        $this->update_rotation_data($form_identifier, $rotation_data);
        
        return $selected_campaign;
    }

    /**
     * Obter dados de rotação para um formulário
     * 
     * @param string $form_identifier Identificador único do formulário
     * @return array Dados de rotação
     */
    private function get_rotation_data($form_identifier) {
        $option_key = 'iare_crm_rotation_' . $form_identifier;
        $default_data = [
            'campaigns' => [],
            'last_used_index' => -1,
            'last_used_id' => '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'total_leads' => 0
        ];
        
        return get_option($option_key, $default_data);
    }

    /**
     * Atualizar dados de rotação
     * 
     * @param string $form_identifier Identificador único do formulário
     * @param array $rotation_data Dados de rotação
     */
    private function update_rotation_data($form_identifier, $rotation_data) {
        $option_key = 'iare_crm_rotation_' . $form_identifier;
        update_option($option_key, $rotation_data);
    }

    /**
     * On export - clear sensitive data
     * 
     * @param array $element
     * @return array
     */
    public function on_export($element) {
        unset(
            $element['iare_crm_campaign'],
            $element['iare_crm_capture_source'],
            $element['iare_crm_name_field'],
            $element['iare_crm_surname_field'],
            $element['iare_crm_phone_country_code_field'],
            $element['iare_crm_default_country_code'],
            $element['iare_crm_phone_number_field'],
            $element['iare_crm_email_field'],
            $element['iare_crm_enterprise_field']
        );

        return $element;
    }
}