<?php

namespace IareCrm\Core;

defined('ABSPATH') || exit;

class Activator {
    
    public static function activate() {
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html(__('iaRe CRM requires WordPress version 6.0 or higher.', 'iare-crm')));
        }

        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html(__('iaRe CRM requires PHP version 7.4 or higher.', 'iare-crm')));
        }

        self::set_default_options();
        self::create_capabilities();
        flush_rewrite_rules();
    }

    private static function set_default_options() {
        if (!get_option(IARE_CRM_OPTION_API_KEY)) {
            add_option(IARE_CRM_OPTION_API_KEY, '');
        }

        $default_settings = [
            'default_campaign_id' => '',
            'enable_logging' => true,
            'cache_duration' => 3600,
        ];

        if (!get_option(IARE_CRM_OPTION_SETTINGS)) {
            add_option(IARE_CRM_OPTION_SETTINGS, $default_settings);
        }
    }

    private static function create_capabilities() {
        $role = get_role('administrator');
        
        if ($role) {
            $role->add_cap('manage_iare_crm');
            $role->add_cap('view_iare_crm_logs');
            $role->add_cap('manage_iare_crm_debug');
        }
    }
} 