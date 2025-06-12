<?php

namespace IareCrm\Integrations\Elementor\Services;

defined('ABSPATH') || exit;

/**
 * Field Mapper Service
 * 
 * Helps with field mapping and validation for Elementor forms
 */
class FieldMapperService {

    /**
     * API field definitions
     */
    const API_FIELDS = [
        'name' => [
            'required' => true,
            'type' => 'string',
            'sanitize' => 'sanitize_text_field'
        ],
        'surname' => [
            'required' => false,
            'type' => 'string',
            'sanitize' => 'sanitize_text_field'
        ],
        'phone_country_code' => [
            'required' => true,
            'type' => 'string',
            'sanitize' => 'sanitize_text_field',
            'default' => '55'
        ],
        'phone_number' => [
            'required' => true,
            'type' => 'string',
            'sanitize' => 'sanitize_text_field'
        ],
        'email' => [
            'required' => false,
            'type' => 'email',
            'sanitize' => 'sanitize_email'
        ]
    ];

    /**
     * Validate field mapping configuration
     * 
     * @param array $settings Form settings
     * @return array Validation result
     */
    public static function validate_field_mapping($settings) {
        $errors = [];

        foreach (self::API_FIELDS as $field_name => $config) {
            if ($config['required']) {
                $setting_key = 'iare_crm_' . $field_name . '_field';
                
                // Special case for phone_country_code - can use default
                if ($field_name === 'phone_country_code') {
                    if (empty($settings[$setting_key]) && empty($settings['iare_crm_default_country_code'])) {
                        /* translators: %s: Field name that requires mapping */
                        $errors[] = sprintf(__('Field mapping required: %s', 'iare-crm'), $field_name);
                    }
                } else {
                    if (empty($settings[$setting_key])) {
                        /* translators: %s: Field name that requires mapping */
                        $errors[] = sprintf(__('Field mapping required: %s', 'iare-crm'), $field_name);
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get human-readable field names
     * 
     * @return array Field names for display
     */
    public static function get_field_labels() {
        return [
            'name' => __('Name', 'iare-crm'),
            'surname' => __('Surname', 'iare-crm'),
            'phone_country_code' => __('Phone Country Code', 'iare-crm'),
            'phone_number' => __('Phone Number', 'iare-crm'),
            'email' => __('Email', 'iare-crm')
        ];
    }

    /**
     * Sanitize field value based on type
     * 
     * @param string $field_name Field name
     * @param mixed $value Field value
     * @return mixed Sanitized value
     */
    public static function sanitize_field_value($field_name, $value) {
        if (!isset(self::API_FIELDS[$field_name])) {
            return sanitize_text_field($value);
        }

        $config = self::API_FIELDS[$field_name];
        $sanitize_function = $config['sanitize'];

        if (function_exists($sanitize_function)) {
            return call_user_func($sanitize_function, $value);
        }

        return sanitize_text_field($value);
    }

    /**
     * Check if field is required
     * 
     * @param string $field_name Field name
     * @return bool True if required
     */
    public static function is_field_required($field_name) {
        return self::API_FIELDS[$field_name]['required'] ?? false;
    }
} 