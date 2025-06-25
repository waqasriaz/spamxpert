<?php
/**
 * Plugin Name: SpamXpert
 * Plugin URI: https://spamxpert.com
 * Description: Multi-layered anti-spam solution with honeypot traps, time-based checks, and deep integration with popular form builders and Houzez theme.
 * Version: 1.0.0
 * Author: SpamXpert Team
 * Author URI: https://spamxpert.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: spamxpert
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPAMXPERT_VERSION', '1.0.0');
define('SPAMXPERT_PLUGIN_FILE', __FILE__);
define('SPAMXPERT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPAMXPERT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPAMXPERT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once SPAMXPERT_PLUGIN_DIR . 'includes/class-spamxpert-autoloader.php';
SpamXpert_Autoloader::register();

// Initialize the plugin
function spamxpert_init() {
    // Load text domain
    load_plugin_textdomain('spamxpert', false, dirname(SPAMXPERT_PLUGIN_BASENAME) . '/languages');
    
    // Initialize main plugin class
    $plugin = SpamXpert::get_instance();
    $plugin->init();
}
add_action('plugins_loaded', 'spamxpert_init');

// Activation hook
register_activation_hook(__FILE__, array('SpamXpert_Activator', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('SpamXpert_Deactivator', 'deactivate'));

// Uninstall hook
register_uninstall_hook(__FILE__, array('SpamXpert_Uninstaller', 'uninstall')); 