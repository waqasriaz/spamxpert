<?php
/**
 * SpamXpert Helper Functions
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user IP address
 *
 * @return string
 */
function spamxpert_get_user_ip() {
    $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Generate random honeypot field name
 *
 * @return string
 */
function spamxpert_generate_honeypot_name() {
    $prefixes = array('email', 'name', 'phone', 'website', 'url', 'company', 'address', 'message');
    $suffixes = array('_hp', '_check', '_verify', '_confirm', '_validate', '_field');
    
    $prefix = $prefixes[array_rand($prefixes)];
    $suffix = $suffixes[array_rand($suffixes)];
    $random = wp_generate_password(4, false, false);
    
    return $prefix . '_' . $random . $suffix;
}

/**
 * Generate random honeypot field label
 *
 * @return string
 */
function spamxpert_generate_honeypot_label() {
    $labels = array(
        __('Leave this field empty', 'spamxpert'),
        __('Do not fill this field', 'spamxpert'),
        __('Skip this field', 'spamxpert'),
        __('This field is for validation purposes', 'spamxpert'),
        __('Keep this field blank', 'spamxpert')
    );
    
    return $labels[array_rand($labels)];
}

/**
 * Check if SpamXpert is enabled
 *
 * @return bool
 */
function spamxpert_is_enabled() {
    return get_option('spamxpert_enabled', '1') === '1';
}

/**
 * Check if form type protection is enabled
 *
 * @param string $form_type Form type
 * @return bool
 */
function spamxpert_is_form_protected($form_type) {
    if (!spamxpert_is_enabled()) {
        return false;
    }
    
    $option_name = 'spamxpert_protect_' . $form_type;
    return get_option($option_name, '1') === '1';
}

/**
 * Log spam attempt
 *
 * @param array $data Log data
 */
function spamxpert_log_spam($data) {
    if (get_option('spamxpert_log_spam', '1') !== '1') {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'spamxpert_logs';
    
    $wpdb->insert(
        $table_name,
        array(
            'form_type' => sanitize_text_field($data['form_type']),
            'form_id' => isset($data['form_id']) ? sanitize_text_field($data['form_id']) : null,
            'ip_address' => spamxpert_get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'spam_reason' => sanitize_text_field($data['reason']),
            'spam_score' => isset($data['score']) ? intval($data['score']) : 0,
            'form_data' => isset($data['form_data']) ? wp_json_encode($data['form_data']) : null,
            'blocked_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
    );
}

/**
 * Clean old logs
 */
function spamxpert_clean_old_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'spamxpert_logs';
    $retention_days = intval(get_option('spamxpert_log_retention_days', 30));
    
    if ($retention_days > 0) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE blocked_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
    }
}

/**
 * Get spam statistics
 *
 * @param string $period Period (today, week, month, all)
 * @return array
 */
function spamxpert_get_stats($period = 'all') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'spamxpert_logs';
    
    $where = '';
    switch ($period) {
        case 'today':
            $where = "WHERE DATE(blocked_at) = CURDATE()";
            break;
        case 'week':
            $where = "WHERE blocked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where = "WHERE blocked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
    
    $stats = array(
        'total_blocked' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where"),
        'by_type' => $wpdb->get_results("SELECT form_type, COUNT(*) as count FROM $table_name $where GROUP BY form_type", ARRAY_A),
        'by_reason' => $wpdb->get_results("SELECT spam_reason, COUNT(*) as count FROM $table_name $where GROUP BY spam_reason", ARRAY_A)
    );
    
    return $stats;
}

// Hook for cleaning old logs
add_action('spamxpert_daily_cleanup', 'spamxpert_clean_old_logs'); 