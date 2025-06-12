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

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
} 