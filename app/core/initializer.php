<?php

namespace IareCrm\Core;

use IareCrm\Admin\MenuManager;
use IareCrm\Traits\Singleton;

defined('ABSPATH') || exit;

class Initializer {
    use Singleton;

    private $menu_manager;

    protected function __construct() {
        $this->set_locale();
        $this->define_admin_hooks();
        
        add_action('init', [$this, 'run'], 999);
    }

    private function set_locale() {
        $this->load_plugin_textdomain();
    }

    public function load_plugin_textdomain() {
        $loaded = load_plugin_textdomain(
            'iare-crm',
            false,
            dirname(IARE_CRM_PLUGIN_BASENAME) . '/languages/'
        );
    }

    private function define_admin_hooks() {
        if (is_admin()) {
            $this->menu_manager = MenuManager::get_instance();
        }

        $this->init_integrations();
    }

    /**
     * Initialize plugin integrations
     */
    private function init_integrations() {
        add_action('plugins_loaded', [$this, 'init_elementor_integration'], 15);
    }

    /**
     * Initialize Elementor integration
     */
    public function init_elementor_integration() {
        if (did_action('elementor/loaded') || class_exists('ElementorPro\Plugin')) {
            require_once IARE_CRM_PLUGIN_PATH . 'app/integrations/elementor/elementor-integration-manager.php';
            \IareCrm\Integrations\Elementor\ElementorIntegrationManager::get_instance();
        }
    }

    public function run() {
        /**
         * Hook executado quando o plugin iaRe CRM é totalmente carregado
         * 
         * @since 1.0.0
         */
        do_action('iare_crm_loaded');
    }
} 