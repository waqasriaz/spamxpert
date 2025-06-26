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
        
        // Get honeypot module
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if (!$honeypot) {
            return $form;
        }
        
        // Get the form ID if available
        $form_id = 'cf7';
        if (preg_match('/id="(wpcf7-f\d+-[^"]+)"/', $form, $matches)) {
            $form_id = $matches[1];
        }
        
        // Add honeypot fields (includes time trap field)
        $fields = $honeypot->render_fields($form_id);
        
        // Allow developers to modify fields
        $fields = apply_filters('spamxpert_cf7_honeypot_fields', $fields, $form);
        
        return $form . $fields;
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
        
        // Get validator module
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return $result;
        }
        
        // Allow developers to modify validation data
        $posted_data = apply_filters('spamxpert_cf7_validation_data', $posted_data);
        
        // Validate submission
        $validation = $validator->validate_submission($posted_data, 'cf7');
        
        if ($validation !== true) {
            // String error returned - spam detected
            $error_message = is_string($validation) ? $validation : __('Spam detected.', 'spamxpert');
            $result->invalidate('', $error_message);
            
            // Module already logged the spam attempt with proper details
            // No need to log again at integration level
            
            // Developer hook for spam detection
            do_action('spamxpert_cf7_spam_detected', $posted_data, $validation);
        }
        
        // Allow developers to override validation result
        return apply_filters('spamxpert_cf7_validation_result', $result, $posted_data);
    }
} 