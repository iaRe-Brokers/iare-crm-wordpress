<?php

namespace IareCrm\Core;

defined('ABSPATH') || exit;

class Deactivator {
    
    public static function deactivate() {
        self::clear_scheduled_events();
        flush_rewrite_rules();
        self::clear_transients();
    }

    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('iare_crm_sync_campaigns');
        wp_clear_scheduled_hook('iare_crm_cleanup_logs');
    }

    private static function clear_transients() {
        delete_transient('iare_crm_campaigns');
        delete_transient('iare_crm_connection_status');
    }
} 