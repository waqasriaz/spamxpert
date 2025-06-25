<?php
/**
 * SpamXpert Validator Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Validator
 *
 * Main validation coordinator
 */
class SpamXpert_Validator {

    /**
     * Initialize the module
     */
    public function init() {
        // Module initialization if needed
    }

    /**
     * Validate form submission
     *
     * @param array $form_data Form data
     * @param string $form_type Form type identifier
     * @return bool|string True if valid, error message if spam detected
     */
    public function validate_submission($form_data, $form_type) {
        // Check if protection is enabled for this form type
        if (!spamxpert_is_form_protected($form_type)) {
            return true;
        }
        
        // Get modules
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        $time_trap = SpamXpert::get_instance()->get_module('time_trap');
        
        // Validate nonce
        $nonce_field = 'spamxpert_nonce';
        
        // Skip nonce validation for forms that have their own nonce protection
        $skip_nonce_forms = apply_filters('spamxpert_skip_nonce_validation', array(
            'houzez_agent_contact',
            'houzez_schedule_tour', 
            'houzez_inquiry'
        ));
        
        if (!in_array($form_type, $skip_nonce_forms)) {
            if (!isset($form_data[$nonce_field]) || !wp_verify_nonce($form_data[$nonce_field], 'spamxpert_form_' . $form_type)) {
                spamxpert_log_spam(array(
                    'form_type' => $form_type,
                    'reason' => 'invalid_nonce',
                    'score' => 100
                ));
                return __('Security check failed. Please refresh the page and try again.', 'spamxpert');
            }
        }
        
        // Validate honeypot fields
        if ($honeypot) {
            $honeypot_result = $honeypot->validate($form_data, $form_type);
            if ($honeypot_result !== true) {
                return $honeypot_result;
            }
        }
        
        // Validate time trap
        if ($time_trap && isset($form_data['spamxpert_time'])) {
            $time_result = $time_trap->validate($form_data['spamxpert_time'], $form_type);
            if ($time_result !== true) {
                return $time_result;
            }
        }
        
        // Apply custom filters for additional validation
        $custom_result = apply_filters('spamxpert_custom_validation', true, $form_data, $form_type);
        if ($custom_result !== true) {
            return $custom_result;
        }
        
        // All validations passed
        do_action('spamxpert_form_validated', $form_type, $form_data);
        
        return true;
    }

    /**
     * Clean form data by removing SpamXpert fields
     *
     * @param array $form_data Original form data
     * @return array Cleaned form data
     */
    public function clean_form_data($form_data) {
        $spamxpert_fields = array('spamxpert_nonce', 'spamxpert_time');
        
        // Remove SpamXpert fields
        foreach ($form_data as $key => $value) {
            if (in_array($key, $spamxpert_fields) || strpos($key, '_hp') !== false || strpos($key, '_check') !== false || strpos($key, '_verify') !== false) {
                unset($form_data[$key]);
            }
        }
        
        return $form_data;
    }

    /**
     * Check if the current request might be spam based on various factors
     *
     * @return int Spam score (0-100)
     */
    public function calculate_spam_score() {
        $score = 0;
        
        // Check user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if (empty($user_agent)) {
            $score += 20;
        }
        
        // Check for common spam patterns in user agent
        $spam_agents = array('bot', 'crawler', 'spider', 'scraper', 'curl', 'wget');
        foreach ($spam_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $score += 10;
                break;
            }
        }
        
        // Check referrer
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (empty($referrer)) {
            $score += 10;
        }
        
        // Check if JavaScript is enabled (will be set by JS)
        if (!isset($_COOKIE['spamxpert_js']) || $_COOKIE['spamxpert_js'] !== '1') {
            $score += 30;
        }
        
        return min($score, 100);
    }
} 