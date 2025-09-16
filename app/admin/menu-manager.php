<?php

namespace IareCrm\Admin;

use IareCrm\Traits\Singleton;
use IareCrm\Admin\Pages\SettingsPage;
use IareCrm\Admin\Pages\DebugPage;

defined('ABSPATH') || exit;

class MenuManager {
    use Singleton;

    private $settings_page;
    private $debug_page;

    protected function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'hide_wordpress_notices'], 1);
        add_action('admin_head', [$this, 'hide_wordpress_notices_late'], 999);
        
        $this->settings_page = SettingsPage::get_instance();
        $this->debug_page = DebugPage::get_instance();
    }

    public function add_admin_menu() {
        add_menu_page(
            __('iaRe CRM', 'iare-crm'),
            __('iaRe CRM', 'iare-crm'),
            'manage_iare_crm',
            'iare-crm',
            [$this->settings_page, 'render'],
            $this->get_menu_icon(),
            80
        );

        add_submenu_page(
            'iare-crm',
            __('Settings', 'iare-crm'),
            __('Settings', 'iare-crm'),
            'manage_iare_crm',
            'iare-crm',
            [$this->settings_page, 'render']
        );
        
        // Add debug submenu
        add_submenu_page(
            'iare-crm',
            __('Debug Settings', 'iare-crm'),
            __('Debug', 'iare-crm'),
            'manage_iare_crm_debug',
            'iare-crm-debug',
            [$this->debug_page, 'render']
        );
    }

    private function get_menu_icon() {
        $svg_content = '
            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60" preserveAspectRatio="xMidYMid meet">
                <g transform="translate(0,60) scale(0.1,-0.1)" fill="#9EA3B1" stroke="none">
                    <path d="M220 587 c-191 -54 -278 -286 -169 -451 130 -196 424 -172 523 43 29
64 29 178 -1 242 -27 59 -92 125 -148 150 -56 25 -148 32 -205 16z m182 -199
c36 -33 69 -66 72 -75 12 -30 5 -63 -16 -83 -29 -27 -57 -25 -98 5 -43 32 -71
32 -110 -2 -40 -33 -86 -35 -112 -4 -32 41 -23 67 50 143 102 106 111 107 214
16z"/>
                </g>
            </svg>
        ';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg_content);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'iare-crm') === false) {
            return;
        }

        wp_enqueue_style(
            'iare-crm-admin',
            IARE_CRM_PLUGIN_URL . 'assets/css/admin/settings.css',
            [],
            IARE_CRM_VERSION
        );

        // Enqueue additional CSS for debug page
        if (strpos($hook, 'iare-crm-debug') !== false) {
            wp_enqueue_style(
                'iare-crm-debug',
                IARE_CRM_PLUGIN_URL . 'assets/css/admin/debug.css',
                ['iare-crm-admin'],
                IARE_CRM_VERSION
            );
        }

        wp_enqueue_script(
            'iare-crm-admin',
            IARE_CRM_PLUGIN_URL . 'assets/js/admin/settings.js',
            ['jquery'],
            IARE_CRM_VERSION,
            true
        );

        wp_localize_script('iare-crm-admin', 'iareCrmAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('iare_crm_ajax_nonce'),
            'messages' => [
                'testing_connection' => __('Testing connection...', 'iare-crm'),
                'connection_success' => __('Connection successful!', 'iare-crm'),
                'connection_failed' => __('Connection failed. Please check your API key.', 'iare-crm'),
            ]
        ]);
    }

    /**
     * Hide WordPress notices on iaRe CRM pages (early hook)
     */
    public function hide_wordpress_notices() {
        $screen = get_current_screen();
        
        if (!$screen || strpos($screen->id, 'iare-crm') === false) {
            return;
        }

        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');
        
        add_action('admin_notices', [$this, 'remove_all_notices'], 0);
        add_action('all_admin_notices', [$this, 'remove_all_notices'], 0);
        add_action('user_admin_notices', [$this, 'remove_all_notices'], 0);
        add_action('network_admin_notices', [$this, 'remove_all_notices'], 0);
    }

    /**
     * Hide WordPress notices on iaRe CRM pages (late hook with CSS)
     */
    public function hide_wordpress_notices_late() {
        $screen = get_current_screen();
        
        if (!$screen || strpos($screen->id, 'iare-crm') === false) {
            return;
        }

        add_filter('admin_footer_text', '__return_empty_string', 11);
        add_filter('update_footer', '__return_empty_string', 11);
        
        // Add CSS as backup to hide any remaining notices
        $hide_notices_css = '
        .iare-crm-admin .notice,
        .iare-crm-admin .updated,
        .iare-crm-admin .error,
        .iare-crm-admin .notice-warning,
        .iare-crm-admin .notice-success,
        .iare-crm-admin .notice-error,
        .iare-crm-admin .notice-info,
        .iare-crm-admin .notice-alt,
        body.toplevel_page_iare-crm .notice,
        body.toplevel_page_iare-crm .updated,
        body.toplevel_page_iare-crm .error,
        body.toplevel_page_iare-crm .notice-warning,
        body.toplevel_page_iare-crm .notice-success,
        body.toplevel_page_iare-crm .notice-error,
        body.toplevel_page_iare-crm .notice-info,
        body.toplevel_page_iare-crm .notice-alt {
            display: none !important;
        }';
        
        wp_add_inline_style('iare-crm-admin', $hide_notices_css);
    }

    /**
     * Remove all notices callback
     */
    public function remove_all_notices() {
        global $wp_filter;
        
        $notice_hooks = [
            'admin_notices',
            'all_admin_notices', 
            'user_admin_notices',
            'network_admin_notices'
        ];
        
        foreach ($notice_hooks as $hook) {
            if (isset($wp_filter[$hook])) {
                unset($wp_filter[$hook]);
            }
        }
    }
} 