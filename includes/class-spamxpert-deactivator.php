<?php
/**
 * Plugin Deactivator
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Deactivator
 *
 * Handles plugin deactivation
 */
class SpamXpert_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Unschedule cron jobs
        self::unschedule_cron_jobs();
        
        // Clear any cached data
        self::clear_cache();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Unschedule cron jobs
     */
    private static function unschedule_cron_jobs() {
        $timestamp = wp_next_scheduled('spamxpert_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'spamxpert_daily_cleanup');
        }
    }

    /**
     * Clear cached data
     */
    private static function clear_cache() {
        // Clear any transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spamxpert_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_spamxpert_%'");
    }
} 