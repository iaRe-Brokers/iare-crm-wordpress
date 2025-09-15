/**
 * iaRe CRM Admin Geolocation Handler
 * 
 * Handles geolocation API testing and configuration in admin area
 */
(function($) {
    'use strict';
    
    // Ensure iareCrmAdminGeolocation object exists
    if (typeof iareCrmAdminGeolocation === 'undefined') {
        console.warn('iaRe CRM: Admin geolocation configuration not found');
        return;
    }
    
    /**
     * Admin Geolocation service class
     */
    class IareCrmAdminGeolocation {
        constructor() {
            this.config = iareCrmAdminGeolocation;
            this.isTestingApi = false;
            
            this.init();
        }
        
        /**
         * Initialize the service
         */
        init() {
            // Add test API button if it doesn't exist
            this.addTestApiButton();
            
            // Bind events
            $(document).on('click', '.iare-test-location-api', this.testLocationApi.bind(this));
            $(document).on('click', '.iare-get-current-location', this.getCurrentLocation.bind(this));
        }
        
        /**
         * Add test API button to admin interface
         */
        addTestApiButton() {
            // Look for settings sections where we can add the test button
            const $settingsTable = $('.form-table');
            
            if ($settingsTable.length) {
                const $testRow = $('<tr>');
                const $testLabel = $('<th scope="row">').text('Location API Test');
                const $testCell = $('<td>');
                
                const $testButton = $('<button>', {
                    type: 'button',
                    class: 'button button-secondary iare-test-location-api',
                    text: 'Test IP-API.com Connectivity'
                });
                
                const $getCurrentButton = $('<button>', {
                    type: 'button',
                    class: 'button button-secondary iare-get-current-location',
                    text: 'Get Current Location',
                    css: { marginLeft: '10px' }
                });
                
                const $testResult = $('<div>', {
                    class: 'iare-test-result',
                    css: {
                        marginTop: '10px',
                        padding: '10px',
                        borderRadius: '4px',
                        display: 'none'
                    }
                });
                
                $testCell.append($testButton, $getCurrentButton, $testResult);
                $testRow.append($testLabel, $testCell);
                $settingsTable.append($testRow);
            }
        }
        
        /**
         * Test location API connectivity
         */
        async testLocationApi(event) {
            event.preventDefault();
            
            if (this.isTestingApi) {
                return;
            }
            
            const $button = $(event.target);
            const $result = $button.siblings('.iare-test-result');
            
            this.isTestingApi = true;
            $button.prop('disabled', true).text(this.config.strings.testing);
            
            try {
                const response = await $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'iare_crm_test_location_api',
                        nonce: this.config.nonce
                    },
                    timeout: 15000 // 15 second timeout
                });
                
                if (response.success) {
                    this.showTestResult($result, {
                        success: true,
                        message: response.data.message,
                        details: {
                            'Response Time': response.data.response_time + 'ms',
                            'API Status': response.data.api_status
                        }
                    });
                } else {
                    throw new Error(response.data?.message || 'API test failed');
                }
            } catch (error) {
                console.error('iaRe CRM: API test failed:', error);
                this.showTestResult($result, {
                    success: false,
                    message: this.config.strings.error,
                    error: error.message
                });
            } finally {
                this.isTestingApi = false;
                $button.prop('disabled', false).text('Test IP-API.com Connectivity');
            }
        }
        
        /**
         * Get current location for testing
         */
        async getCurrentLocation(event) {
            event.preventDefault();
            
            const $button = $(event.target);
            const $result = $button.siblings('.iare-test-result');
            
            $button.prop('disabled', true).text('Getting Location...');
            
            try {
                const response = await $.ajax({
                    url: this.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'iare_crm_get_location',
                        nonce: this.config.nonce
                    },
                    timeout: 10000 // 10 second timeout
                });
                
                if (response.success && response.data.location) {
                    const location = response.data.location;
                    this.showTestResult($result, {
                        success: true,
                        message: 'Location retrieved successfully',
                        details: {
                            'City': location.city || 'N/A',
                            'State': location.state || 'N/A',
                            'Country': location.country || 'N/A',
                            'Cached': response.data.cached ? 'Yes' : 'No'
                        }
                    });
                } else {
                    throw new Error(response.data?.message || 'Failed to get location');
                }
            } catch (error) {
                console.error('iaRe CRM: Get location failed:', error);
                this.showTestResult($result, {
                    success: false,
                    message: 'Failed to get location',
                    error: error.message
                });
            } finally {
                $button.prop('disabled', false).text('Get Current Location');
            }
        }
        
        /**
         * Show test result
         */
        showTestResult($container, result) {
            $container.empty();
            
            // Create result content
            const $content = $('<div>');
            
            // Add status message
            const $message = $('<p>', {
                css: {
                    margin: '0 0 10px 0',
                    fontWeight: 'bold',
                    color: result.success ? '#155724' : '#721c24'
                },
                text: result.message
            });
            $content.append($message);
            
            // Add details if available
            if (result.details) {
                const $detailsList = $('<ul>', {
                    css: {
                        margin: '0',
                        paddingLeft: '20px'
                    }
                });
                
                Object.entries(result.details).forEach(([key, value]) => {
                    const $item = $('<li>').html(`<strong>${key}:</strong> ${value}`);
                    $detailsList.append($item);
                });
                
                $content.append($detailsList);
            }
            
            // Add error details if available
            if (result.error) {
                const $error = $('<p>', {
                    css: {
                        margin: '10px 0 0 0',
                        fontSize: '12px',
                        color: '#721c24',
                        fontStyle: 'italic'
                    },
                    text: 'Error: ' + result.error
                });
                $content.append($error);
            }
            
            // Apply container styles
            $container.css({
                backgroundColor: result.success ? '#d4edda' : '#f8d7da',
                border: result.success ? '1px solid #c3e6cb' : '1px solid #f5c6cb',
                color: result.success ? '#155724' : '#721c24'
            });
            
            $container.append($content).slideDown(300);
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        new IareCrmAdminGeolocation();
    });
    
})(jQuery);