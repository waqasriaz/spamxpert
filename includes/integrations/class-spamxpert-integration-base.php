<?php
/**
 * SpamXpert Integration Base Class
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for form integrations
 */
abstract class SpamXpert_Integration_Base {
    
    /**
     * Integration name
     * @var string
     */
    protected $name = '';
    
    /**
     * Integration slug
     * @var string
     */
    protected $slug = '';
    
    /**
     * Whether integration is enabled
     * @var bool
     */
    protected $enabled = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->enabled = $this->is_enabled();
        
        if ($this->enabled && $this->is_available()) {
            $this->init();
        }
    }
    
    /**
     * Initialize the integration
     */
    abstract protected function init();
    
    /**
     * Check if the integration is available (plugin active, etc)
     */
    abstract public function is_available();
    
    /**
     * Check if the integration is enabled in settings
     */
    protected function is_enabled() {
        $option_name = 'spamxpert_protect_' . $this->slug;
        return get_option($option_name, '1') === '1';
    }
    
    /**
     * Add honeypot fields to form
     * 
     * @param string $form_html Form HTML
     * @param string $form_id Optional form ID
     * @return string Modified form HTML
     */
    protected function add_honeypot_fields($form_html, $form_id = '') {
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if (!$honeypot) {
            return $form_html;
        }
        
        // Use the form type slug as form_id if not provided
        if (empty($form_id)) {
            $form_id = $this->slug;
        }
        
        // render_fields already includes honeypot fields AND time field
        $fields = $honeypot->render_fields($form_id);
        
        // Allow developers to modify honeypot fields for this integration
        $fields = apply_filters('spamxpert_' . $this->slug . '_honeypot_fields', $fields, $form_html);
        
        return $form_html . $fields;
    }
    
    /**
     * Validate form submission
     * 
     * @param array $data Form data
     * @return bool|WP_Error True if valid, WP_Error if spam detected
     */
    protected function validate_submission($data = array()) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return true;
        }
        
        // Allow developers to modify validation data
        $data = apply_filters('spamxpert_' . $this->slug . '_validation_data', $data);
        
        $result = $validator->validate_submission($data, $this->slug);
        
        // Convert string error to WP_Error for consistency
        if (is_string($result)) {
            return new WP_Error('spam_detected', $result);
        }
        
        // Allow developers to override validation result
        return apply_filters('spamxpert_' . $this->slug . '_validation_result', $result, $data);
    }
    
    /**
     * Log spam attempt
     * 
     * @deprecated Integrations should not log spam attempts. Core modules handle all logging.
     * 
     * @param string $reason Spam reason or error message
     * @param int $score Spam score
     * @param array $form_data Optional form data
     */
    protected function log_spam($reason, $score = 100, $form_data = array()) {
        if (get_option('spamxpert_log_spam', '1') === '1') {
            spamxpert_log_spam(array(
                'form_type' => $this->slug,
                'reason' => $reason,
                'score' => $score,
                'form_data' => $form_data
            ));
        }
    }
} 