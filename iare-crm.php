<?php
/**
 * Plugin Name: iaRe CRM
 * Description: Complete integration with iaRe CRM system for lead capture and management
 * Version: 1.0.0
 * Author: iaRe CRM
 * Author URI: https://crm.iare.me
 * License: GPL v2 or later
 * Text Domain: iare-crm
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IARE_CRM_VERSION', '1.0.0');
define('IARE_CRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IARE_CRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('IARE_CRM_PLUGIN_BASENAME', plugin_basename(__FILE__));

define('IARE_CRM_API_BASE_URL', 'https://0d29-187-18-141-206.ngrok-free.app');
define('IARE_CRM_API_VERSION', 'v1');
define('IARE_CRM_API_ENDPOINT', '/api/' . IARE_CRM_API_VERSION);

define('IARE_CRM_OPTION_API_KEY', 'iare_crm_api_key');
define('IARE_CRM_OPTION_SETTINGS', 'iare_crm_settings');

require_once IARE_CRM_PLUGIN_PATH . 'vendor/autoload.php';

register_activation_hook(__FILE__, ['IareCrm\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['IareCrm\Core\Deactivator', 'deactivate']);

add_action('init', function() {
    load_plugin_textdomain(
        'iare-crm',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}, 1);

add_action('plugins_loaded', ['IareCrm\Core\Initializer', 'get_instance']); 