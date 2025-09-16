<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="iare-crm-admin">
    <div class="iare-crm-header">
        <div class="iare-crm-logo">
            <h1><?php esc_html_e('iaRe CRM Debug', 'iare-crm'); ?></h1>
        </div>
        <p class="iare-crm-subtitle">
            <?php esc_html_e('Debug settings and logs', 'iare-crm'); ?>
        </p>
    </div>

    <?php settings_errors('iare_crm_debug_messages'); ?>

    <div class="iare-crm-content">
        <div class="iare-crm-main">
            <div class="iare-crm-card">
                <div class="iare-crm-card-header">
                    <h2><?php esc_html_e('Debug Settings', 'iare-crm'); ?></h2>
                </div>
                <div class="iare-crm-card-body">
                    <form method="post" action="">
                        <?php wp_nonce_field('iare_crm_debug_action', 'iare_crm_debug_nonce'); ?>
                        
                        <div class="iare-crm-field-group">
                            <label for="enable_debug" class="iare-crm-label">
                                <input type="checkbox" name="enable_debug" id="enable_debug" value="1" <?php checked($debug_enabled, true); ?> />
                                <?php esc_html_e('Enable Debug Mode', 'iare-crm'); ?>
                            </label>
                            <p class="iare-crm-field-description">
                                <?php esc_html_e('Enable this option to start logging debug information.', 'iare-crm'); ?>
                            </p>
                        </div>

                        <div class="iare-crm-notice iare-crm-notice-warning">
                            <p><strong><?php esc_html_e('Performance Warning:', 'iare-crm'); ?></strong> <?php esc_html_e('This option should only be enabled for debugging purposes as it may impact site performance.', 'iare-crm'); ?></p>
                        </div>

                        <div class="iare-crm-actions">
                            <button type="submit" class="iare-crm-btn iare-crm-btn-primary">
                                <?php esc_html_e('Save Settings', 'iare-crm'); ?>
                            </button>
                            
                            <?php if ($debug_enabled): ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=iare_crm_clear_debug_logs'), 'iare_crm_clear_logs')); ?>" class="iare-crm-btn iare-crm-btn-secondary">
                                    <?php esc_html_e('Clear Logs', 'iare-crm'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($debug_enabled): ?>
                <div class="iare-crm-card">
                    <div class="iare-crm-card-header">
                        <h2><?php esc_html_e('Error Logs', 'iare-crm'); ?></h2>
                    </div>
                    <div class="iare-crm-card-body">
                        <?php if (!empty($error_logs)): ?>
                            <div class="iare-crm-debug-logs">
                                <?php foreach ($error_logs as $log): ?>
                                    <div class="iare-crm-debug-log-entry">
                                        <?php echo esc_html($log); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p><?php esc_html_e('No error logs found.', 'iare-crm'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>