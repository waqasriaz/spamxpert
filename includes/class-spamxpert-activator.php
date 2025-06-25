<?php
/**
 * Plugin Activator
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Activator
 *
 * Handles plugin activation
 */
class SpamXpert_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create log directory
        self::create_log_directory();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'spamxpert_logs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_type varchar(50) NOT NULL,
            form_id varchar(100) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            spam_reason varchar(100) NOT NULL,
            spam_score int(11) DEFAULT 0,
            form_data longtext,
            blocked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_type (form_type),
            KEY ip_address (ip_address),
            KEY blocked_at (blocked_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store database version
        update_option('spamxpert_db_version', '1.0.0');
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        // General settings
        add_option('spamxpert_enabled', '1');
        add_option('spamxpert_honeypot_count', '2');
        add_option('spamxpert_time_threshold', '3');
        add_option('spamxpert_log_spam', '1');
        add_option('spamxpert_log_retention_days', '30');
        
        // Form type settings
        add_option('spamxpert_protect_wp_login', '1');
        add_option('spamxpert_protect_wp_registration', '1');
        add_option('spamxpert_protect_wp_comments', '1');
        add_option('spamxpert_protect_contact_form_7', '1');
        add_option('spamxpert_protect_gravity_forms', '1');
        add_option('spamxpert_protect_wpforms', '1');
        add_option('spamxpert_protect_ninja_forms', '1');
        add_option('spamxpert_protect_elementor_forms', '1');
        add_option('spamxpert_protect_houzez_forms', '1');
        
        // Advanced settings
        add_option('spamxpert_custom_css_classes', '');
        add_option('spamxpert_excluded_forms', '');
        add_option('spamxpert_debug_mode', '0');
    }

    /**
     * Create log directory
     */
    private static function create_log_directory() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/spamxpert-logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Add .htaccess to protect log files
            $htaccess_content = "Order Deny,Allow\nDeny from all";
            file_put_contents($log_dir . '/.htaccess', $htaccess_content);
            
            // Add index.php for extra security
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        // Schedule daily log cleanup
        if (!wp_next_scheduled('spamxpert_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'spamxpert_daily_cleanup');
        }
    }
} 