<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="iare-crm-admin">
    <div class="iare-crm-header">
        <div class="iare-crm-logo">
            <h1><?php esc_html_e('iaRe CRM Settings', 'iare-crm'); ?></h1>
        </div>
        <p class="iare-crm-subtitle">
            <?php esc_html_e('Configure your connection to the iaRe CRM system.', 'iare-crm'); ?>
        </p>
    </div>

    <?php
    $current_api_key = $this->get_current_api_key();
    $this->display_settings_errors();
    ?>

    <div class="iare-crm-content">
        <div class="iare-crm-main">
            <div class="iare-crm-card">
                <div class="iare-crm-card-header">
                    <h2><?php esc_html_e('API Configuration', 'iare-crm'); ?></h2>
                </div>
                <div class="iare-crm-card-body">
                    <form method="post" action="" id="iare-crm-settings-form">
                        <?php wp_nonce_field('iare_crm_settings_action', 'iare_crm_settings_nonce'); ?>
                        
                        <div class="iare-crm-field-group">
                            <label for="api_key" class="iare-crm-label">
                                <?php esc_html_e('API Key', 'iare-crm'); ?>
                            </label>
                            <div class="iare-crm-input-group">
                                <input 
                                    type="password" 
                                    name="api_key" 
                                    id="api_key" 
                                    value="<?php echo esc_attr($current_api_key); ?>" 
                                    placeholder="<?php esc_attr_e('Enter your iaRe CRM API key to connect your site.', 'iare-crm'); ?>"
                                    autocomplete="off"
                                />
                                <button type="button" id="toggle-password" class="iare-crm-btn" aria-label="<?php esc_attr_e('Toggle API key visibility', 'iare-crm'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                            <p class="iare-crm-field-description">
                                <?php esc_html_e('Enter your iaRe CRM API key to connect your site.', 'iare-crm'); ?>
                            </p>
                        </div>

                        <div class="iare-crm-actions">
                            <button type="submit" class="iare-crm-btn iare-crm-btn-primary">
                                <?php esc_html_e('Save Settings', 'iare-crm'); ?>
                            </button>
                            <button type="button" id="test-connection" class="iare-crm-btn iare-crm-btn-secondary">
                                <?php esc_html_e('Test Connection', 'iare-crm'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="iare-crm-sidebar">
            <div class="iare-crm-card">
                <div class="iare-crm-card-header">
                    <h3><?php esc_html_e('Connection Status', 'iare-crm'); ?></h3>
                </div>
                <div class="iare-crm-card-body">
                    <div class="connection-status">
                        <?php 
                        $connection_status = $this->get_auto_connection_status();
                        $status_class = 'status-unknown';
                        $icon_class = 'dashicons-warning';
                        $status_title = esc_html__('Not Connected', 'iare-crm');
                        
                        if (isset($connection_status['success'])) {
                            if ($connection_status['success']) {
                                $status_class = 'status-connected';
                                $icon_class = 'dashicons-yes-alt';
                                $status_title = esc_html__('Connected', 'iare-crm');
                            } else {
                                $status_class = 'status-failed';
                                $icon_class = 'dashicons-warning';
                                $status_title = esc_html__('Connection Failed', 'iare-crm');
                            }
                        } elseif ($connection_status['status'] === 'no_key') {
                            $status_title = esc_html__('No API Key', 'iare-crm');
                        }
                        ?>
                        <div class="iare-crm-status-indicator <?php echo esc_attr($status_class); ?>">
                            <div class="status-icon">
                                <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                            </div>
                            <div class="status-text">
                                <div class="status-title"><?php echo esc_html($status_title); ?></div>
                                <div class="status-message">
                                    <?php 
                                    if (isset($connection_status['message'])) {
                                        echo esc_html($connection_status['message']);
                                    } elseif (isset($connection_status['error']['message'])) {
                                        echo esc_html($connection_status['error']['message']);
                                    } else {
                                        esc_html_e('Enter your API key and test the connection.', 'iare-crm');
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($connection_status['data']) && $connection_status['data'] && isset($connection_status['data']['partner_name'])): ?>
                        <div class="iare-crm-partner-info">
                            <h4><?php esc_html_e('Partner Information', 'iare-crm'); ?></h4>
                            <div class="partner-details">
                                <p><strong><?php esc_html_e('Name:', 'iare-crm'); ?></strong> <?php echo esc_html($connection_status['data']['partner_name']); ?></p>
                                <?php if (isset($connection_status['data']['plan'])): ?>
                                    <p><strong><?php esc_html_e('Plan:', 'iare-crm'); ?></strong> <?php echo esc_html($connection_status['data']['plan']); ?></p>
                                <?php endif; ?>
                                <?php if (isset($connection_status['data']['integration_limit'])): ?>
                                    <p><strong><?php esc_html_e('Integration Limit:', 'iare-crm'); ?></strong> <?php echo esc_html($connection_status['data']['integration_limit']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="iare-crm-card">
                <div class="iare-crm-card-header">
                    <h3><?php esc_html_e('Getting Started', 'iare-crm'); ?></h3>
                </div>
                <div class="iare-crm-card-body">
                    <ol class="getting-started-list">
                        <li><?php esc_html_e('1. Enter your API key above', 'iare-crm'); ?></li>
                        <li><?php esc_html_e('2. Test the connection', 'iare-crm'); ?></li>
                        <li><?php esc_html_e('3. Save your settings', 'iare-crm'); ?></li>
                        <li><?php esc_html_e('4. Configure form integrations', 'iare-crm'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="iare-crm-modal" class="iare-crm-modal" style="display: none;">
    <div class="iare-crm-modal-content">
        <button type="button" class="iare-crm-modal-close" aria-label="<?php esc_attr_e('Close modal', 'iare-crm'); ?>">
            <span class="dashicons dashicons-no"></span>
        </button>
    </div>
</div> 