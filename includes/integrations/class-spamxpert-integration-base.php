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
     * @return string Modified form HTML
     */
    protected function add_honeypot_fields($form_html) {
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if (!$honeypot) {
            return $form_html;
        }
        
        $fields = $honeypot->generate_fields();
        
        // Allow developers to modify honeypot fields for this integration
        $fields = apply_filters('spamxpert_' . $this->slug . '_honeypot_fields', $fields, $form_html);
        
        return $form_html . $fields;
    }
    
    /**
     * Add time trap field to form
     * 
     * @param string $form_html Form HTML
     * @return string Modified form HTML
     */
    protected function add_time_trap($form_html) {
        $time_trap = SpamXpert::get_instance()->get_module('time_trap');
        if (!$time_trap) {
            return $form_html;
        }
        
        $field = $time_trap->generate_field();
        
        // Allow developers to modify time trap field for this integration
        $field = apply_filters('spamxpert_' . $this->slug . '_time_trap_field', $field, $form_html);
        
        return $form_html . $field;
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
        
        $result = $validator->validate($this->slug, $data);
        
        // Allow developers to override validation result
        return apply_filters('spamxpert_' . $this->slug . '_validation_result', $result, $data);
    }
    
    /**
     * Log spam attempt
     * 
     * @param string $reason Spam reason
     * @param int $score Spam score
     */
    protected function log_spam($reason, $score = 100) {
        $logger = SpamXpert::get_instance()->get_module('logger');
        if ($logger && get_option('spamxpert_log_spam', '1') === '1') {
            $logger->log($this->slug, $reason, $score);
        }
    }
} 