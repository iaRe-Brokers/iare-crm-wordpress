(function() {
    'use strict';

    const UTM_PARAMETERS = [
        'utm_source',
        'utm_medium', 
        'utm_campaign',
        'utm_content'
    ];

    const COOKIE_CONFIG = {
        prefix: 'iare_crm_',
        duration: 30, // days
        path: '/',
        sameSite: 'Lax'
    };

    /**
     * Utility functions for cookie management
     */
    const CookieUtils = {
        /**
         * Set cookie with proper configuration
         * @param {string} name Cookie name
         * @param {string} value Cookie value
         * @param {number} days Days to expire
         */
        set: function(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            
            const cookieString = name + '=' + encodeURIComponent(value) + 
                '; expires=' + expires.toUTCString() + 
                '; path=' + COOKIE_CONFIG.path +
                '; SameSite=' + COOKIE_CONFIG.sameSite;
            
            document.cookie = cookieString;
        },

        /**
         * Get cookie value
         * @param {string} name Cookie name
         * @return {string|null} Cookie value or null
         */
        get: function(name) {
            const nameEQ = name + '=';
            const cookies = document.cookie.split(';');
            
            for (let i = 0; i < cookies.length; i++) {
                let cookie = cookies[i];
                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1, cookie.length);
                }
                if (cookie.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(cookie.substring(nameEQ.length, cookie.length));
                }
            }
            return null;
        },

        /**
         * Delete cookie
         * @param {string} name Cookie name
         */
        delete: function(name) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=' + COOKIE_CONFIG.path + ';';
        }
    };

    /**
     * UTM Capture Service
     */
    const UtmCaptureService = {
        /**
         * Parse UTM parameters from current URL
         * @return {Object} UTM parameters object
         */
        parseUtmFromUrl: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const utmData = {};
            let hasUtm = false;

            UTM_PARAMETERS.forEach(function(param) {
                const value = urlParams.get(param);
                if (value && value.trim() !== '') {
                    utmData[param] = value.trim();
                    hasUtm = true;
                }
            });

            return hasUtm ? utmData : null;
        },

        /**
         * Store UTM parameters in cookies
         * @param {Object} utmData UTM parameters object
         */
        storeUtmInCookies: function(utmData) {
            if (!utmData || typeof utmData !== 'object') {
                return false;
            }

            let stored = false;
            
            UTM_PARAMETERS.forEach(function(param) {
                if (utmData[param]) {
                    const cookieName = COOKIE_CONFIG.prefix + param;
                    CookieUtils.set(cookieName, utmData[param], COOKIE_CONFIG.duration);
                    stored = true;
                }
            });

            if (stored) {
                // Store timestamp for tracking purposes
                CookieUtils.set(COOKIE_CONFIG.prefix + 'timestamp', Date.now().toString(), COOKIE_CONFIG.duration);
            }

            return stored;
        },

        /**
         * Get stored UTM parameters from cookies
         * @return {Object} UTM parameters object
         */
        getStoredUtmData: function() {
            const utmData = {};
            let hasData = false;

            UTM_PARAMETERS.forEach(function(param) {
                const cookieName = COOKIE_CONFIG.prefix + param;
                const value = CookieUtils.get(cookieName);
                if (value) {
                    utmData[param] = value;
                    hasData = true;
                }
            });

            return hasData ? utmData : null;
        },

        /**
         * Clear all UTM cookies
         */
        clearUtmData: function() {
            UTM_PARAMETERS.forEach(function(param) {
                const cookieName = COOKIE_CONFIG.prefix + param;
                CookieUtils.delete(cookieName);
            });
            
            CookieUtils.delete(COOKIE_CONFIG.prefix + 'timestamp');
        },

        /**
         * Initialize UTM capture
         */
        init: function() {
            const currentUtmData = this.parseUtmFromUrl();
            
            if (currentUtmData) {
                this.storeUtmInCookies(currentUtmData);
                
                if (window.console && window.console.log) {
                    console.log('iaRe CRM: UTM parameters captured', currentUtmData);
                }
            }

            this.populateFormFields();
        },

        /**
         * Populate form fields with UTM data
         * Supports various form builders including Elementor
         */
        populateFormFields: function() {
            const utmData = this.getStoredUtmData();
            
            if (!utmData) {
                return;
            }

            // Populate fields by various selectors
            UTM_PARAMETERS.forEach(function(param) {
                if (utmData[param]) {
                    const selectors = [
                        'input[name="' + param + '"]',
                        'input[id="' + param + '"]',
                        'input[class*="' + param + '"]',
                        'input[data-utm="' + param + '"]'
                    ];

                    selectors.forEach(function(selector) {
                        const fields = document.querySelectorAll(selector);
                        fields.forEach(function(field) {
                            if (field.type === 'hidden' || field.value === '') {
                                field.value = utmData[param];
                            }
                        });
                    });
                }
            });
        },

        /**
         * Debug function to check UTM status
         * @return {Object} Debug information
         */
        debug: function() {
            return {
                currentUrl: window.location.href,
                urlParams: this.parseUtmFromUrl(),
                storedData: this.getStoredUtmData(),
                timestamp: CookieUtils.get(COOKIE_CONFIG.prefix + 'timestamp'),
                cookies: document.cookie
            };
        }
    };

    /**
     * Initialize when DOM is ready
     */
    function initWhenReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                UtmCaptureService.init();
            });
        } else {
            UtmCaptureService.init();
        }
    }

    /**
     * Global object for external access
     */
    window.IareCrmUtmCapture = {
        service: UtmCaptureService,
        utils: CookieUtils,
        debug: function() {
            return UtmCaptureService.debug();
        },
        clear: function() {
            UtmCaptureService.clearUtmData();
        }
    };

    // Initialize
    initWhenReady();

    // Re-populate fields when new forms are loaded dynamically (Elementor, AJAX)
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            let shouldPopulate = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if (node.nodeType === 1) { // Element node
                            if (node.tagName === 'FORM' || node.querySelector('form')) {
                                shouldPopulate = true;
                                break;
                            }
                        }
                    }
                }
            });

            if (shouldPopulate) {
                setTimeout(function() {
                    UtmCaptureService.populateFormFields();
                }, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();

