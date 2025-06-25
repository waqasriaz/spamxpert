<?php
/**
 * SpamXpert Logger Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Logger
 *
 * Handles spam logging and reporting
 */
class SpamXpert_Logger {

    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Initialize the module
     */
    public function init() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'spamxpert_logs';
        
        // Add AJAX handlers for log export
        add_action('wp_ajax_spamxpert_export_logs', array($this, 'ajax_export_logs'));
    }

    /**
     * Log a spam attempt
     *
     * @param string $form_type Form type
     * @param string $reason Spam reason
     * @param int $score Spam score
     * @param array $form_data Optional form data
     * @return bool
     */
    public function log($form_type, $reason, $score = 100, $form_data = array()) {
        if (get_option('spamxpert_log_spam', '1') !== '1') {
            return false;
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'form_type' => sanitize_text_field($form_type),
                'form_id' => isset($form_data['form_id']) ? sanitize_text_field($form_data['form_id']) : null,
                'ip_address' => spamxpert_get_user_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'spam_reason' => sanitize_text_field($reason),
                'spam_score' => intval($score),
                'form_data' => !empty($form_data) ? wp_json_encode($form_data) : null,
                'blocked_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        return $result !== false;
    }

    /**
     * Get logs with pagination
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'blocked_at',
            'order' => 'DESC',
            'form_type' => '',
            'ip_address' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = array('1=1');
        $prepare_args = array();
        
        if (!empty($args['form_type'])) {
            $where[] = 'form_type = %s';
            $prepare_args[] = $args['form_type'];
        }
        
        if (!empty($args['ip_address'])) {
            $where[] = 'ip_address = %s';
            $prepare_args[] = $args['ip_address'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'blocked_at >= %s';
            $prepare_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'blocked_at <= %s';
            $prepare_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($prepare_args)) {
            $count_query = $wpdb->prepare($count_query, $prepare_args);
        }
        $total_items = $wpdb->get_var($count_query);
        
        // Calculate pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Get logs
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $query_args = array_merge($prepare_args, array($args['per_page'], $offset));
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        return array(
            'logs' => $logs,
            'total' => $total_items,
            'pages' => ceil($total_items / $args['per_page'])
        );
    }

    /**
     * Get log by ID
     *
     * @param int $id Log ID
     * @return object|null
     */
    public function get_log($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Delete logs
     *
     * @param array $ids Log IDs to delete
     * @return int Number of deleted logs
     */
    public function delete_logs($ids) {
        global $wpdb;
        
        if (empty($ids)) {
            return 0;
        }
        
        $ids = array_map('intval', (array) $ids);
        $ids_string = implode(',', $ids);
        
        return $wpdb->query(
            "DELETE FROM {$this->table_name} WHERE id IN ({$ids_string})"
        );
    }

    /**
     * Clear all logs
     *
     * @return int Number of deleted logs
     */
    public function clear_all_logs() {
        global $wpdb;
        
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }

    /**
     * Export logs to CSV
     *
     * @param array $args Export arguments
     */
    public function export_logs($args = array()) {
        // Get all logs for export
        $args['per_page'] = -1; // Get all
        $result = $this->get_logs($args);
        $logs = $result['logs'];
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=spamxpert-logs-' . date('Y-m-d-H-i-s') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array(
            'ID',
            'Date/Time',
            'Form Type',
            'Form ID',
            'IP Address',
            'User Agent',
            'Spam Reason',
            'Spam Score'
        ));
        
        // Add data
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->blocked_at,
                $log->form_type,
                $log->form_id,
                $log->ip_address,
                $log->user_agent,
                $log->spam_reason,
                $log->spam_score
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * AJAX handler for log export
     */
    public function ajax_export_logs() {
        // Check nonce
        if (!check_ajax_referer('spamxpert_export_logs', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Export logs
        $this->export_logs($_POST);
    }

    /**
     * Get statistics for dashboard
     *
     * @return array
     */
    public function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total blocked
        $stats['total_blocked'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );
        
        // Blocked today
        $stats['blocked_today'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE DATE(blocked_at) = CURDATE()"
        );
        
        // Blocked this week
        $stats['blocked_week'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE blocked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Top spam sources (IPs)
        $stats['top_ips'] = $wpdb->get_results(
            "SELECT ip_address, COUNT(*) as count 
             FROM {$this->table_name} 
             GROUP BY ip_address 
             ORDER BY count DESC 
             LIMIT 5"
        );
        
        // Spam by form type
        $stats['by_form'] = $wpdb->get_results(
            "SELECT form_type, COUNT(*) as count 
             FROM {$this->table_name} 
             GROUP BY form_type 
             ORDER BY count DESC"
        );
        
        // Spam by reason
        $stats['by_reason'] = $wpdb->get_results(
            "SELECT spam_reason, COUNT(*) as count 
             FROM {$this->table_name} 
             GROUP BY spam_reason 
             ORDER BY count DESC"
        );
        
        return $stats;
    }
} 