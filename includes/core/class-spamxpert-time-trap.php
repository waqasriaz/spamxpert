<?php
/**
 * SpamXpert Time Trap Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Time_Trap
 *
 * Handles time-based spam detection
 */
class SpamXpert_Time_Trap {

    /**
     * Initialize the module
     */
    public function init() {
        // Module initialization if needed
    }

    /**
     * Validate submission time
     *
     * @param string $encrypted_time Encrypted time from form
     * @param string $form_id Form identifier
     * @return bool|string True if valid, error message if not
     */
    public function validate($encrypted_time, $form_id = '') {
        // Get honeypot instance to decrypt time
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        
        if (!$honeypot) {
            return true; // Skip validation if honeypot module not available
        }
        
        $submission_time = $honeypot->decrypt_time($encrypted_time);
        
        if (!$submission_time) {
            spamxpert_log_spam(array(
                'form_type' => $form_id,
                'reason' => 'invalid_time_field',
                'score' => 100
            ));
            return __('Invalid form submission.', 'spamxpert');
        }
        
        // Calculate time difference
        $current_time = time();
        $time_diff = $current_time - $submission_time;
        
        // Get threshold from settings
        $threshold = intval(get_option('spamxpert_time_threshold', 3));
        
        // Check if form was submitted too quickly
        if ($time_diff < $threshold) {
            spamxpert_log_spam(array(
                'form_type' => $form_id,
                'reason' => 'too_fast_submission',
                'score' => 90,
                'form_data' => array(
                    'time_diff' => $time_diff,
                    'threshold' => $threshold
                )
            ));
            return __('Form submitted too quickly. Please try again.', 'spamxpert');
        }
        
        // Check if form was submitted after too long (24 hours)
        if ($time_diff > 86400) {
            spamxpert_log_spam(array(
                'form_type' => $form_id,
                'reason' => 'expired_form',
                'score' => 50,
                'form_data' => array(
                    'time_diff' => $time_diff
                )
            ));
            return __('Form has expired. Please refresh the page and try again.', 'spamxpert');
        }
        
        return true;
    }

    /**
     * Get JavaScript for client-side time tracking
     *
     * @return string
     */
    public function get_tracking_script() {
        $threshold = intval(get_option('spamxpert_time_threshold', 3));
        
        return "
        (function() {
            var forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                var startTime = Date.now();
                form.addEventListener('submit', function(e) {
                    var elapsed = (Date.now() - startTime) / 1000;
                    if (elapsed < {$threshold}) {
                        // Optional: Add visual feedback
                        console.warn('SpamXpert: Form submitted too quickly');
                    }
                });
            });
        })();
        ";
    }
} 