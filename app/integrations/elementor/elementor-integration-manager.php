<?php

namespace IareCrm\Integrations\Elementor;

use IareCrm\Traits\Singleton;
use IareCrm\Helpers\Logger;

defined('ABSPATH') || exit;

/**
 * Elementor Integration Manager
 * 
 * Manages Elementor integration, compatibility checks and form action registration
 */
class ElementorIntegrationManager {
    use Singleton;

    private $initialized = false;
    private $elementor_initialized = false;
    private static $global_initialized = false;
    private $logger = null;
    const MIN_ELEMENTOR_VERSION = '3.0.0';
    const MIN_ELEMENTOR_PRO_VERSION = '3.0.0';

    /**
     * Initialize integration
     */
    protected function __construct() {
        $this->logger = new Logger();
        add_action('plugins_loaded', [$this, 'init'], 30);
    }

    /**
     * Initialize Elementor integration
     */
    public function init() {
        if (self::$global_initialized) {
            $this->logger->info('Elementor integration already initialized globally.');
            return;
        }

        if (!$this->is_compatible()) {
            $this->logger->warning('Elementor integration not compatible with current versions.');
            add_action('admin_notices', [$this, 'admin_notice_minimum_version']);
            return;
        }

        if (!$this->is_elementor_pro_loaded()) {
            $this->logger->warning('Elementor Pro is not loaded.');
            add_action('admin_notices', [$this, 'admin_notice_elementor_pro_required']);
            return;
        }

        add_action('elementor/init', [$this, 'elementor_init']);

        self::$global_initialized = true;
        $this->logger->info('Elementor integration initialized.');
    }

    /**
     * Initialize when Elementor is ready
     */
    public function elementor_init() {
        if ($this->elementor_initialized) {
            $this->logger->info('Elementor integration already initialized.');
            return;
        }

        if (!$this->is_elementor_pro_loaded()) {
            $this->logger->warning('Elementor Pro is not loaded during elementor_init.');
            add_action('admin_notices', [$this, 'admin_notice_elementor_pro_required']);
            $this->elementor_initialized = true;
            return;
        }

        if (did_action('elementor_pro/forms/actions/register')) {
            $this->logger->info('Elementor forms actions hook already fired, using direct registration.');
            $this->try_direct_registration();
        } else {
            $this->logger->info('Adding action for elementor_pro/forms/actions/register hook.');
            add_action('elementor_pro/forms/actions/register', [$this, 'register_form_actions']);
        }

        $this->init_utm_capture();
        $this->clear_elementor_cache();
        $this->elementor_initialized = true;
        
        $this->logger->info('Elementor initialization completed.');
    }

    /**
     * Check if Elementor is loaded
     */
    private function is_elementor_loaded() {
        return did_action('elementor/loaded') || class_exists('Elementor\Plugin');
    }

    /**
     * Check if Elementor Pro is loaded
     */
    private function is_elementor_pro_loaded() {
        return class_exists('ElementorPro\Plugin');
    }

    /**
     * Check compatibility with required versions
     */
    private function is_compatible() {
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '<')) {
            return false;
        }

        if (defined('ELEMENTOR_PRO_VERSION') && version_compare(ELEMENTOR_PRO_VERSION, self::MIN_ELEMENTOR_PRO_VERSION, '<')) {
            return false;
        }

        return true;
    }

    /**
     * Register form actions
     */
    public function register_form_actions($form_actions_registrar) {
        $this->logger->info('Registering iaRe CRM form action.');
        
        require_once IARE_CRM_PLUGIN_PATH . 'app/integrations/elementor/form-actions/iare-crm-action.php';
        
        $action = new \IareCrm\Integrations\Elementor\FormActions\IareCrmAction();
        $form_actions_registrar->register($action);
        
        $this->logger->info('iaRe CRM form action registered successfully.');
    }

    /**
     * Try direct registration if hook already fired
     */
    private function try_direct_registration() {
        $this->logger->info('Attempting direct registration of form actions.');
        
        // Approach 1: Through ElementorPro Plugin
        if (class_exists('ElementorPro\Plugin')) {
            $elementor_pro = \ElementorPro\Plugin::instance();
            if ($elementor_pro && isset($elementor_pro->modules_manager)) {
                $forms_module = $elementor_pro->modules_manager->get_modules('forms');
                if ($forms_module && method_exists($forms_module, 'get_form_actions_registrar')) {
                    $registrar = $forms_module->get_form_actions_registrar();
                    if ($registrar) {
                        $this->register_form_actions($registrar);
                        $this->logger->info('Form actions registered via ElementorPro Plugin approach.');
                        return;
                    }
                }
            }
        }

        // Approach 2: Through Forms Module singleton
        if (class_exists('ElementorPro\Modules\Forms\Module')) {
            $forms_module = \ElementorPro\Modules\Forms\Module::instance();
            if ($forms_module) {
                // Try different method names
                $methods_to_try = ['get_form_actions_registrar', 'get_actions_registrar'];
                foreach ($methods_to_try as $method) {
                    if (method_exists($forms_module, $method)) {
                        $registrar = $forms_module->$method();
                        if ($registrar) {
                            $this->register_form_actions($registrar);
                            $this->logger->info('Form actions registered via Forms Module singleton approach.');
                            return;
                        }
                    }
                }
                
                // Try accessing private properties via reflection
                try {
                    $reflection = new \ReflectionClass($forms_module);
                    $properties = ['form_actions_registrar', 'actions_registrar', 'registrar'];
                    
                    foreach ($properties as $property_name) {
                        if ($reflection->hasProperty($property_name)) {
                            $property = $reflection->getProperty($property_name);
                            $property->setAccessible(true);
                            $registrar = $property->getValue($forms_module);
                            
                            if ($registrar && method_exists($registrar, 'register')) {
                                $this->register_form_actions($registrar);
                                $this->logger->info('Form actions registered via reflection approach.');
                                return;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Reflection approach failed: ' . $e->getMessage());
                }
            }
        }
        
        $this->logger->warning('Failed to register form actions via any approach.');
    }

    /**
     * Initialize UTM capture service
     */
    private function init_utm_capture() {
        require_once IARE_CRM_PLUGIN_PATH . 'app/integrations/elementor/services/utm-capture-service.php';
        
        \IareCrm\Integrations\Elementor\Services\UtmCaptureService::get_instance();
    }

    /**
     * Admin notice for minimum version requirement
     */
    public function admin_notice_minimum_version() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameter check for admin notice display only
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'iare-crm'),
            '<strong>' . esc_html__('iaRe CRM - Elementor Integration', 'iare-crm') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'iare-crm') . '</strong>',
            self::MIN_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post($message));
    }

    /**
     * Admin notice for Elementor Pro requirement
     */
    public function admin_notice_elementor_pro_required() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameter check for admin notice display only
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor Pro */
            esc_html__('"%1$s" requires "%2$s" to be installed and activated for form integration.', 'iare-crm'),
            '<strong>' . esc_html__('iaRe CRM - Elementor Integration', 'iare-crm') . '</strong>',
            '<strong>' . esc_html__('Elementor Pro', 'iare-crm') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post($message));
    }

    /**
     * Clear Elementor cache
     */
    private function clear_elementor_cache() {
        if (class_exists('Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }

        if (class_exists('ElementorPro\Plugin')) {
            $elementor_pro = \ElementorPro\Plugin::instance();
            if (method_exists($elementor_pro, 'flush_css_cache')) {
                $elementor_pro->flush_css_cache();
            }
        }
    }

    /**
     * Reset initialization state (for debugging/testing purposes)
     */
    public static function reset_initialization_state() {
        self::$global_initialized = false;
    }

    /**
     * Get initialization status
     * 
     * @return array Status information
     */
    public function get_initialization_status() {
        return [
            'global_initialized' => self::$global_initialized,
            'instance_initialized' => $this->initialized,
            'elementor_initialized' => $this->elementor_initialized,
            'elementor_loaded' => $this->is_elementor_loaded(),
            'elementor_pro_loaded' => $this->is_elementor_pro_loaded(),
            'compatible' => $this->is_compatible(),
        ];
    }
} 