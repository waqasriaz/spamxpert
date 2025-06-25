<?php
/**
 * SpamXpert Autoloader
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Autoloader
 *
 * Handles automatic loading of plugin classes
 */
class SpamXpert_Autoloader {

    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload SpamXpert classes
     *
     * @param string $class_name The class name to load
     */
    public static function autoload($class_name) {
        // Check if it's a SpamXpert class
        if (strpos($class_name, 'SpamXpert') !== 0) {
            return;
        }

        // Convert class name to file name
        $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        
        // Define directories to search
        $directories = array(
            SPAMXPERT_PLUGIN_DIR . 'includes/',
            SPAMXPERT_PLUGIN_DIR . 'includes/core/',
            SPAMXPERT_PLUGIN_DIR . 'includes/admin/',
            SPAMXPERT_PLUGIN_DIR . 'includes/public/',
            SPAMXPERT_PLUGIN_DIR . 'includes/integrations/',
            SPAMXPERT_PLUGIN_DIR . 'includes/validators/',
        );

        // Search for the file
        foreach ($directories as $directory) {
            $file_path = $directory . $file_name;
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
} 