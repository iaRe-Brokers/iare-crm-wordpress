<?php

namespace IareCrm\App\Services;

/**
 * GeolocationService class
 * 
 * Handles automatic location capture using IP-API.com service
 * with WordPress transient caching for 72 hours
 */
class GeolocationService {

    /**
     * Cache duration in seconds (72 hours)
     */
    const CACHE_DURATION = 72 * HOUR_IN_SECONDS;

    /**
     * IP-API.com endpoint URL
     */
    const API_ENDPOINT = 'http://ip-api.com/json/';

    /**
     * Required fields from IP-API response
     */
    const API_FIELDS = 'status,message,country,countryCode,region,regionName,city';

    /**
     * Get location data by IP address
     * 
     * @param string|null $ip_address IP address to lookup (null for current IP)
     * @return array|false Location data or false on failure
     */
    public function getLocationByIp($ip_address = null) {
        // Use current IP if none provided
        if (empty($ip_address)) {
            $ip_address = $this->getCurrentIpAddress();
        }

        // Check cache first
        $cache_key = $this->getCacheKey($ip_address);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Fetch from API
        $location_data = $this->fetchLocationFromApi($ip_address);
        
        if ($location_data && $location_data['status'] === 'success') {
            // Cache the successful response
            set_transient($cache_key, $location_data, self::CACHE_DURATION);
            return $location_data;
        }

        return false;
    }

    /**
     * Get current user's IP address
     * 
     * @return string IP address
     */
    private function getCurrentIpAddress() {
        // Check for various headers that might contain the real IP
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (take the first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to REMOTE_ADDR even if it's private/reserved
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Fetch location data from IP-API.com
     * 
     * @param string $ip_address IP address to lookup
     * @return array|false API response or false on failure
     */
    private function fetchLocationFromApi($ip_address) {
        $url = self::API_ENDPOINT . $ip_address . '?fields=' . self::API_FIELDS;
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'user-agent' => 'iaRe CRM WordPress Plugin'
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $data;
    }

    /**
     * Generate cache key for IP address
     * 
     * @param string $ip_address IP address
     * @return string Cache key
     */
    private function getCacheKey($ip_address) {
        return 'iare_crm_geolocation_' . md5($ip_address);
    }

    /**
     * Clear cache for specific IP address
     * 
     * @param string $ip_address IP address
     * @return bool True on success, false on failure
     */
    public function clearCache($ip_address) {
        $cache_key = $this->getCacheKey($ip_address);
        return delete_transient($cache_key);
    }

    /**
     * Clear all geolocation cache
     * 
     * @return void
     */
    public function clearAllCache() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_iare_crm_geolocation_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_iare_crm_geolocation_%'
            )
        );
    }

    /**
     * Format location data for form fields
     * 
     * @param array $location_data Raw API response
     * @return array Formatted location data
     */
    public function formatLocationData($location_data) {
        if (!$location_data || $location_data['status'] !== 'success') {
            return [
                'city' => '',
                'state' => '',
                'country' => ''
            ];
        }

        return [
            'city' => $location_data['city'] ?? '',
            'state' => $location_data['regionName'] ?? '',
            'country' => $location_data['country'] ?? ''
        ];
    }

    /**
     * Test API connectivity
     * 
     * @return array Test result with status and message
     */
    public function testApiConnectivity() {
        $test_ip = '8.8.8.8'; // Google DNS for testing
        $location_data = $this->fetchLocationFromApi($test_ip);
        
        if ($location_data && $location_data['status'] === 'success') {
            return [
                'success' => true,
                'message' => 'IP-API.com connectivity test successful',
                'data' => $location_data
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to connect to IP-API.com service',
            'data' => $location_data
        ];
    }
}