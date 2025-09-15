/**
 * iaRe CRM Geolocation AJAX Handler
 * 
 * Handles automatic location capture for Elementor forms
 */
(function($) {
    'use strict';
    
    // Ensure iareCrmGeolocation object exists
    if (typeof iareCrmGeolocation === 'undefined') {
        console.warn('iaRe CRM: Geolocation AJAX configuration not found');
        return;
    }
    
    /**
     * Geolocation service class
     */
    class IareCrmGeolocation {
        constructor() {
            this.config = iareCrmGeolocation;
            this.cache = new Map();
            this.isLoading = false;
            
            this.init();
        }
        
        /**
         * Initialize the service
         */
        init() {
            // Listen for Elementor form submissions
            $(document).on('submit_success', '.elementor-form', this.handleFormSuccess.bind(this));
            
            // Listen for automatic location toggle changes
            $(document).on('change', '[data-iare-auto-location]', this.handleAutoLocationToggle.bind(this));
            
            // Auto-load location data when forms with auto-location are detected
            this.autoLoadLocationData();
        }
        
        /**
         * Handle form submission success
         */
        handleFormSuccess(event, response) {
            // Clear cached location data after successful submission
            this.cache.clear();
        }
        
        /**
         * Handle automatic location toggle changes
         */
        handleAutoLocationToggle(event) {
            const $toggle = $(event.target);
            const isEnabled = $toggle.is(':checked');
            const $form = $toggle.closest('.elementor-form');
            
            if (isEnabled) {
                this.loadLocationData($form);
            } else {
                this.clearLocationFields($form);
            }
        }
        
        /**
         * Auto-load location data for forms with auto-location enabled
         */
        autoLoadLocationData() {
            $('.elementor-form').each((index, form) => {
                const $form = $(form);
                const $autoLocationToggle = $form.find('[data-iare-auto-location]');
                
                if ($autoLocationToggle.length && $autoLocationToggle.is(':checked')) {
                    this.loadLocationData($form);
                }
            });
        }
        
        /**
         * Load location data via AJAX
         */
        async loadLocationData($form) {
            if (this.isLoading) {
                return;
            }
            
            // Check cache first
            const cacheKey = 'location_data';
            if (this.cache.has(cacheKey)) {
                this.populateLocationFields($form, this.cache.get(cacheKey));
                return;
            }
            
            this.isLoading = true;
            this.showLoadingState($form);
            
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
                    const locationData = response.data.location;
                    
                    // Cache the data
                    this.cache.set(cacheKey, locationData);
                    
                    // Populate form fields
                    this.populateLocationFields($form, locationData);
                    
                    // Show success message if configured
                    this.showMessage($form, this.config.strings.success, 'success');
                } else {
                    throw new Error(response.data?.message || 'Failed to load location data');
                }
            } catch (error) {
                console.error('iaRe CRM: Location loading failed:', error);
                this.showMessage($form, this.config.strings.error, 'error');
            } finally {
                this.isLoading = false;
                this.hideLoadingState($form);
            }
        }
        
        /**
         * Populate location fields in the form
         */
        populateLocationFields($form, locationData) {
            const fieldMappings = {
                city: '[data-iare-city-field]',
                state: '[data-iare-state-field]', 
                country: '[data-iare-country-field]'
            };
            
            Object.entries(fieldMappings).forEach(([key, selector]) => {
                if (locationData[key]) {
                    const $field = $form.find(selector);
                    if ($field.length) {
                        $field.val(locationData[key]).trigger('change');
                    }
                }
            });
        }
        
        /**
         * Clear location fields in the form
         */
        clearLocationFields($form) {
            const selectors = [
                '[data-iare-city-field]',
                '[data-iare-state-field]',
                '[data-iare-country-field]'
            ];
            
            selectors.forEach(selector => {
                const $field = $form.find(selector);
                if ($field.length) {
                    $field.val('').trigger('change');
                }
            });
        }
        
        /**
         * Show loading state
         */
        showLoadingState($form) {
            const $locationFields = $form.find('[data-iare-city-field], [data-iare-state-field], [data-iare-country-field]');
            $locationFields.prop('disabled', true).addClass('iare-loading');
            
            // Show loading message
            this.showMessage($form, this.config.strings.loading, 'info');
        }
        
        /**
         * Hide loading state
         */
        hideLoadingState($form) {
            const $locationFields = $form.find('[data-iare-city-field], [data-iare-state-field], [data-iare-country-field]');
            $locationFields.prop('disabled', false).removeClass('iare-loading');
        }
        
        /**
         * Show message to user
         */
        showMessage($form, message, type = 'info') {
            // Remove existing messages
            $form.find('.iare-location-message').remove();
            
            // Create message element
            const $message = $('<div>', {
                class: `iare-location-message iare-message-${type}`,
                text: message,
                css: {
                    padding: '8px 12px',
                    margin: '10px 0',
                    borderRadius: '4px',
                    fontSize: '14px',
                    display: 'none'
                }
            });
            
            // Apply type-specific styles
            switch (type) {
                case 'success':
                    $message.css({
                        backgroundColor: '#d4edda',
                        color: '#155724',
                        border: '1px solid #c3e6cb'
                    });
                    break;
                case 'error':
                    $message.css({
                        backgroundColor: '#f8d7da',
                        color: '#721c24',
                        border: '1px solid #f5c6cb'
                    });
                    break;
                case 'info':
                default:
                    $message.css({
                        backgroundColor: '#d1ecf1',
                        color: '#0c5460',
                        border: '1px solid #bee5eb'
                    });
                    break;
            }
            
            // Insert message and show with animation
            $form.prepend($message);
            $message.slideDown(300);
            
            // Auto-hide success and info messages after 3 seconds
            if (type === 'success' || type === 'info') {
                setTimeout(() => {
                    $message.slideUp(300, () => $message.remove());
                }, 3000);
            }
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        new IareCrmGeolocation();
    });
    
})(jQuery);