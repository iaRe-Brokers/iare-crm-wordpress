<?php

namespace IareCrm\Helpers;

defined('ABSPATH') || exit;

/**
 * Validation utilities
 */
class Validator {

    /**
     * Validate API key format
     * 
     * @param string $api_key API key to validate
     * @return bool True if valid
     */
    public static function validate_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }

        // API key should be alphanumeric and at least 16 characters
        if (strlen($api_key) < 16) {
            return false;
        }

        // Should contain only alphanumeric characters, hyphens, and underscores
        return preg_match('/^[a-zA-Z0-9_-]+$/', $api_key);
    }

    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @return bool True if valid
     */
    public static function validate_email($email) {
        return !empty($email) && is_email($email);
    }

    /**
     * Validate required field
     * 
     * @param mixed $value Value to validate
     * @return bool True if not empty
     */
    public static function validate_required($value) {
        if (is_string($value)) {
            return !empty(trim($value));
        }
        
        return !empty($value);
    }

    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @return bool True if valid
     */
    public static function validate_url($url) {
        return !empty($url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     * 
     * @param mixed $value Value to validate
     * @param int $min Minimum value (optional)
     * @param int $max Maximum value (optional)
     * @return bool True if valid
     */
    public static function validate_int($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            return false;
        }

        $int_value = intval($value);

        if ($min !== null && $int_value < $min) {
            return false;
        }

        if ($max !== null && $int_value > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate array of lead data
     * 
     * @param array $data Lead data to validate
     * @return array Validation result with errors
     */
    public static function validate_lead_data($data) {
        $errors = [];

        // Validate required fields
        if (!self::validate_required($data['name'] ?? '')) {
            $errors['name'] = __('Name is required', 'iare-crm');
        }

        if (!self::validate_required($data['phone_number'] ?? '')) {
            $errors['phone_number'] = __('Phone number is required', 'iare-crm');
        }

        // Validate email if provided
        if (!empty($data['email']) && !self::validate_email($data['email'])) {
            $errors['email'] = __('Invalid email format', 'iare-crm');
        }

        // Validate capture_source length
        if (!empty($data['capture_source']) && strlen($data['capture_source']) > 100) {
            $errors['capture_source'] = __('Capture source must be at most 100 characters', 'iare-crm');
        }

        // Validate enterprise length (API limit: 50 characters)
        if (!empty($data['enterprise']) && strlen($data['enterprise']) > 50) {
            $errors['enterprise'] = __('Enterprise must be at most 50 characters', 'iare-crm');
        }

        // Validate additional_info structure and limits
        if (!empty($data['additional_info'])) {
            $additional_info_errors = self::validate_additional_info($data['additional_info']);
            if (!empty($additional_info_errors)) {
                $errors['additional_info'] = $additional_info_errors;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate additional_info array structure
     * 
     * @param array $additional_info Additional info array to validate
     * @return array Validation errors
     */
    public static function validate_additional_info($additional_info) {
        $errors = [];

        if (!is_array($additional_info)) {
            return [__('Additional info must be an array', 'iare-crm')];
        }

        // Check maximum number of items (API limit: 15)
        if (count($additional_info) > 15) {
            $errors[] = __('Additional info can contain at most 15 items', 'iare-crm');
        }

        $titles_seen = [];

        foreach ($additional_info as $index => $item) {
            if (!is_array($item)) {
                // translators: %d is the item number
                $errors[] = sprintf(__('Additional info item %d must be an array', 'iare-crm'), $index + 1);
                continue;
            }

            // Validate required fields
            if (empty($item['title'])) {
                // translators: %d is the item number
                $errors[] = sprintf(__('Additional info item %d: title is required', 'iare-crm'), $index + 1);
            } elseif (strlen($item['title']) > 50) {
                // translators: %d is the item number
                $errors[] = sprintf(__('Additional info item %d: title must be at most 50 characters', 'iare-crm'), $index + 1);
            } elseif (in_array($item['title'], $titles_seen)) {
                // translators: %1$d is the item number, %2$s is the title value
                $errors[] = sprintf(__('Additional info item %1$d: duplicate title "%2$s"', 'iare-crm'), $index + 1, $item['title']);
            } else {
                $titles_seen[] = $item['title'];
            }

            if (empty($item['value'])) {
                // translators: %d is the item number
                $errors[] = sprintf(__('Additional info item %d: value is required', 'iare-crm'), $index + 1);
            } elseif (strlen($item['value']) > 255) {
                // translators: %d is the item number
                $errors[] = sprintf(__('Additional info item %d: value must be at most 255 characters', 'iare-crm'), $index + 1);
            }
        }

        return $errors;
    }

    /**
     * Validate campaigns array
     * 
     * @param mixed $campaigns Campaigns to validate (string or array)
     * @return array Validation result with valid campaigns and errors
     */
    public static function validate_campaigns($campaigns) {
        $errors = [];
        $valid_campaigns = [];

        // Converter string para array se necessário
        if (is_string($campaigns) && !empty($campaigns)) {
            $campaigns = [$campaigns];
        }

        // Verificar se é um array
        if (!is_array($campaigns)) {
            return [
                'valid' => false,
                'campaigns' => [],
                'errors' => [__('Campaigns must be an array or string', 'iare-crm')]
            ];
        }

        // Verificar se não está vazio
        if (empty($campaigns)) {
            return [
                'valid' => false,
                'campaigns' => [],
                'errors' => [__('At least one campaign must be selected', 'iare-crm')]
            ];
        }

        // Verificar limite máximo de campanhas (10)
        if (count($campaigns) > 10) {
            $errors[] = __('Maximum of 10 campaigns allowed', 'iare-crm');
        }

        // Validar cada ID de campanha
        $seen_ids = [];
        foreach ($campaigns as $index => $campaign_id) {
            // Converter para string se necessário
            $campaign_id = (string) $campaign_id;
            
            // Verificar se não está vazio
            if (empty($campaign_id)) {
                $errors[] = sprintf(__('Campaign at position %d is empty', 'iare-crm'), $index + 1);
                continue;
            }

            // Validar se é um ID válido (numérico ou string alfanumérica)
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $campaign_id)) {
                $errors[] = sprintf(__('Campaign ID "%s" contains invalid characters', 'iare-crm'), $campaign_id);
                continue;
            }

            // Verificar duplicatas
            if (in_array($campaign_id, $seen_ids)) {
                $errors[] = sprintf(__('Duplicate campaign ID "%s"', 'iare-crm'), $campaign_id);
                continue;
            }

            $seen_ids[] = $campaign_id;
            $valid_campaigns[] = $campaign_id;
        }

        // Limitar a 10 campanhas válidas
        if (count($valid_campaigns) > 10) {
            $valid_campaigns = array_slice($valid_campaigns, 0, 10);
            $errors[] = __('Only the first 10 campaigns will be used', 'iare-crm');
        }

        return [
            'valid' => empty($errors),
            'campaigns' => $valid_campaigns,
            'errors' => $errors
        ];
    }
}