<?php

namespace IareCrm\Integrations\Elementor\FormActions;

use IareCrm\Api\Client;
use IareCrm\Helpers\Validator;
use IareCrm\Integrations\Elementor\Services\FieldMapperService;
use ElementorPro\Modules\Forms\Classes\Action_Base;

defined('ABSPATH') || exit;

/**
 * iaRe CRM Action for Elementor Forms
 * 
 * Custom Elementor form action which creates leads in iaRe CRM after form submission
 */
class IareCrmAction extends Action_Base {

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
                'label' => esc_html__('Campaign', 'iare-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_campaigns_options(),
                'description' => esc_html__('Select the campaign to send leads to.', 'iare-crm'),
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

        if (empty($settings['iare_crm_campaign'])) {
            return;
        }

        // Validate field mapping
        require_once IARE_CRM_PLUGIN_PATH . 'app/integrations/elementor/services/field-mapper-service.php';
        $validation = FieldMapperService::validate_field_mapping($settings);
        if (!$validation['valid']) {
            return;
        }

        $raw_fields = $record->get('fields');

        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = $field['value'];
        }

        // Build lead data from mapped fields
        $lead_data = $this->build_lead_data($settings, $fields);

        // Apply filter to allow UTM data injection
        $lead_data = apply_filters('iare_crm_elementor_form_data', $lead_data, $raw_fields);

        $api_key = get_option(IARE_CRM_OPTION_API_KEY, '');
        if (empty($api_key)) {
            return;
        }

        $api_client = new Client();
        $result = $api_client->create_lead($api_key, $settings['iare_crm_campaign'], $lead_data);
    }

    /**
     * Build lead data from form settings and submitted fields
     * 
     * @param array $settings Form settings
     * @param array $fields Submitted form fields
     * @return array Lead data
     */
    private function build_lead_data($settings, $fields) {
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
     * On export - clear sensitive data
     * 
     * @param array $element
     * @return array
     */
    public function on_export($element) {
        unset(
            $element['iare_crm_campaign'],
            $element['iare_crm_name_field'],
            $element['iare_crm_surname_field'],
            $element['iare_crm_phone_country_code_field'],
            $element['iare_crm_default_country_code'],
            $element['iare_crm_phone_number_field'],
            $element['iare_crm_email_field']
        );

        return $element;
    }
} 