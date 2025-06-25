<?php
/**
 * SpamXpert Contact Form 7 Integration
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integration_CF7
 *
 * Handles Contact Form 7 integration
 */
class SpamXpert_Integration_CF7 extends SpamXpert_Integration_Base {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'Contact Form 7';
        $this->slug = 'cf7';
        
        parent::__construct();
    }
    
    /**
     * Check if Contact Form 7 is available
     */
    public function is_available() {
        return class_exists('WPCF7');
    }
    
    /**
     * Initialize the integration
     */
    protected function init() {
        // Add honeypot and time trap to forms
        add_filter('wpcf7_form_elements', array($this, 'add_spam_fields'));
        
        // Validate submissions
        add_filter('wpcf7_validate', array($this, 'validate_form'), 10, 2);
        
        // Developer hook for CF7 integration init
        do_action('spamxpert_cf7_integration_init', $this);
    }
    
    /**
     * Add spam protection fields to CF7 forms
     *
     * @param string $form Form HTML
     * @return string Modified form HTML
     */
    public function add_spam_fields($form) {
        // Allow developers to skip protection for specific forms
        if (apply_filters('spamxpert_cf7_skip_protection', false, $form)) {
            return $form;
        }
        
        // Add honeypot fields
        $form = $this->add_honeypot_fields($form);
        
        // Add time trap
        $form = $this->add_time_trap($form);
        
        return $form;
    }
    
    /**
     * Validate CF7 form submission
     *
     * @param object $result Validation result object
     * @param array $tags Form tags
     * @return object Modified validation result
     */
    public function validate_form($result, $tags) {
        // Skip if already invalid
        if (!$result->is_valid()) {
            return $result;
        }
        
        // Get submission data
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return $result;
        }
        
        $posted_data = $submission->get_posted_data();
        
        // Validate submission
        $validation = $this->validate_submission($posted_data);
        
        if (is_wp_error($validation)) {
            $result->invalidate('', $validation->get_error_message());
            
            // Module already logged the spam attempt with proper details
            // No need to log again at integration level
            
            // Developer hook for spam detection
            do_action('spamxpert_cf7_spam_detected', $posted_data, $validation);
        }
        
        return $result;
    }
} 