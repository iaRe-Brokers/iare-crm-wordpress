jQuery(document).ready(function($) {
    const form = $('#iare-crm-settings-form');
    const testButton = $('#test-connection');
    const apiKeyField = $('#api_key');
    const togglePasswordButton = $('#toggle-password');
    const statusContainer = $('.connection-status');
    const modal = $('#iare-crm-modal');
    const modalContent = modal.find('.iare-crm-modal-content');
    const closeModalButton = modal.find('.iare-crm-modal-close');

    togglePasswordButton.on('click', function() {
        const type = apiKeyField.attr('type') === 'password' ? 'text' : 'password';
        apiKeyField.attr('type', type);
        $(this).find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');
    });

    testButton.on('click', function(e) {
        e.preventDefault();
        
        const apiKey = apiKeyField.val().trim();
        if (!apiKey) {
            showModal('error', 'Error', 'Please enter your API key.');
            return;
        }

        const originalText = testButton.text();
        testButton.prop('disabled', true);
        testButton.html('<span class="loading"></span> ' + iareCrmAjax.messages.testing_connection);

        $.ajax({
            url: iareCrmAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'iare_crm_test_connection',
                api_key: apiKey,
                nonce: iareCrmAjax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateConnectionStatus('connected', response.message, response.data);
                    showModal('success', 'Success', response.message, response.data);
                } else {
                    updateConnectionStatus('failed', response.error ? response.error.message : 'Connection failed');
                    showModal('error', 'Error', response.error ? response.error.message : 'Connection failed');
                }
            },
            error: function(xhr, status, error) {
                const message = 'Server communication error: ' + error;
                updateConnectionStatus('failed', message);
                showModal('error', 'Error', message);
            },
            complete: function() {
                testButton.prop('disabled', false);
                testButton.text(originalText);
            }
        });
    });

    function updateConnectionStatus(status, message, data = null) {
        const statusIndicator = statusContainer.find('.iare-crm-status-indicator');
        statusIndicator.removeClass('status-connected status-failed status-unknown');
        statusIndicator.addClass('status-' + status);
        
        let statusText = '';
        let iconClass = '';
        
        switch(status) {
            case 'connected':
                statusText = 'Connected';
                iconClass = 'dashicons-yes-alt';
                break;
            case 'failed':
                statusText = 'Connection Failed';
                iconClass = 'dashicons-warning';
                break;
            default:
                statusText = 'Not Connected';
                iconClass = 'dashicons-warning';
        }
        
        const statusHtml = `
            <div class="iare-crm-status-indicator status-${status}">
                <div class="status-icon">
                    <span class="dashicons ${iconClass}"></span>
                </div>
                <div class="status-text">
                    <div class="status-title">${statusText}</div>
                    <div class="status-message">${message}</div>
                </div>
            </div>
        `;
        
        statusContainer.html(statusHtml);
        
        if (data && data.partner_name) {
            statusContainer.append(`
                <div class="iare-crm-partner-info">
                    <h4>Partner Information</h4>
                    <div class="partner-details">
                        <p><strong>Name:</strong> ${data.partner_name}</p>
                        ${data.plan ? `<p><strong>Plan:</strong> ${data.plan}</p>` : ''}
                        ${data.integration_limit ? `<p><strong>Integration Limit:</strong> ${data.integration_limit}</p>` : ''}
                    </div>
                </div>
            `);
        }
    }

    function showModal(type, title, message, data = null) {
        let icon = '';
        let modalClass = '';
        
        switch(type) {
            case 'success':
                icon = '<span class="dashicons dashicons-yes-alt"></span>';
                modalClass = 'modal-success';
                break;
            case 'error':
                icon = '<span class="dashicons dashicons-warning"></span>';
                modalClass = 'modal-error';
                break;
            default:
                icon = '<span class="dashicons dashicons-info"></span>';
                modalClass = 'modal-info';
        }
        
        let content = `
            <div class="iare-crm-modal-header ${modalClass}">
                <h3>${icon} ${title}</h3>
                <button type="button" class="iare-crm-modal-close" aria-label="Close modal">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="iare-crm-modal-body">
                <p>${message}</p>
        `;
        
        if (data && data.partner_name) {
            content += `
                <div class="iare-crm-partner-info">
                    <h4>Partner Information:</h4>
                    <div class="partner-details">
                        <p><strong>Name:</strong> ${data.partner_name}</p>
                        ${data.plan ? `<p><strong>Plan:</strong> ${data.plan}</p>` : ''}
                        ${data.integration_limit ? `<p><strong>Integration Limit:</strong> ${data.integration_limit}</p>` : ''}
                    </div>
                </div>
            `;
        }
        
        content += '</div>';
        
        modalContent.html(content);
        
        // Re-bind close button since it was recreated
        modalContent.find('.iare-crm-modal-close').on('click', function() {
            modal.hide();
        });
        
        modal.show();
    }

    closeModalButton.on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(e) {
        if (e.target === modal[0]) {
            modal.hide();
        }
    });

    form.on('submit', function(e) {
        const apiKey = apiKeyField.val().trim();
        if (!apiKey) {
            e.preventDefault();
            showModal('error', 'Error', 'Please enter your API key.');
        }
    });
}); 