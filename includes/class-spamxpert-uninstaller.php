<?php
/**
 * Plugin Uninstaller
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Uninstaller
 *
 * Handles plugin uninstallation
 */
class SpamXpert_Uninstaller {

    /**
     * Uninstall the plugin
     */
    public static function uninstall() {
        // Check if we should remove data
        $remove_data = get_option('spamxpert_remove_data_on_uninstall', '0');
        
        if ('1' === $remove_data) {
            // Remove database tables
            self::remove_tables();
            
            // Remove options
            self::remove_options();
            
            // Remove log files
            self::remove_log_files();
        }
    }

    /**
     * Remove database tables
     */
    private static function remove_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spamxpert_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    /**
     * Remove all plugin options
     */
    private static function remove_options() {
        global $wpdb;
        
        // Remove all options starting with 'spamxpert_'
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'spamxpert_%'");
        
        // Remove transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spamxpert_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_spamxpert_%'");
    }

    /**
     * Remove log files
     */
    private static function remove_log_files() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/spamxpert-logs';
        
        if (file_exists($log_dir)) {
            // Remove all files in the directory
            $files = glob($log_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            // Remove the directory
            rmdir($log_dir);
        }
    }
} 