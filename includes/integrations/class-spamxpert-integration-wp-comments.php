<?php
/**
 * WordPress Comments Form Integration
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integration_WP_Comments
 *
 * Handles WordPress comments form protection
 */
class SpamXpert_Integration_WP_Comments extends SpamXpert_Integration_Base {

    /**
     * Integration name
     * @var string
     */
    protected $name = 'WordPress Comments Form';
    
    /**
     * Integration slug
     * @var string
     */
    protected $slug = 'wp_comments';

    /**
     * Check if the integration is available
     *
     * @return bool
     */
    public function is_available() {
        // Comments form is available if comments are open globally
        return get_option('default_comment_status') === 'open';
    }

    /**
     * Initialize the integration
     */
    protected function init() {
        // Add honeypot fields to comment form
        add_action('comment_form', array($this, 'output_honeypot_fields'));
        
        // For themes that use comment_form_after_fields
        add_action('comment_form_after_fields', array($this, 'output_honeypot_fields_after'));
        
        // For logged in users (they don't see the fields section)
        add_action('comment_form_logged_in_after', array($this, 'output_honeypot_fields_logged_in'));
        
        // Validate comment submission
        add_filter('preprocess_comment', array($this, 'validate_comment'), 1);
        
        // Alternative validation hook
        add_action('pre_comment_on_post', array($this, 'validate_comment_early'));
    }

    /**
     * Output honeypot fields to comment form
     */
    public function output_honeypot_fields() {
        // Only add once
        static $added = false;
        if ($added) {
            return;
        }
        $added = true;
        
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if ($honeypot) {
            echo $honeypot->render_fields('wp_comments');
        }
    }

    /**
     * Output honeypot fields after form fields
     */
    public function output_honeypot_fields_after() {
        $this->output_honeypot_fields();
    }

    /**
     * Output honeypot fields for logged in users
     */
    public function output_honeypot_fields_logged_in() {
        $this->output_honeypot_fields();
    }

    /**
     * Validate comment submission early
     */
    public function validate_comment_early() {
        // Get validator
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return;
        }
        
        // Validate the form submission
        $validation_result = $validator->validate_submission($_POST, $this->slug);
        
        if ($validation_result !== true) {
            // Module already logged the spam attempt with proper details
            // No need to log again at integration level
            
            // Stop processing and show error
            wp_die(
                __('Your comment appears to be spam. Please try again.', 'spamxpert'),
                __('Comment Submission Failed', 'spamxpert'),
                array('response' => 403, 'back_link' => true)
            );
        }
    }

    /**
     * Validate comment data
     *
     * @param array $commentdata Comment data
     * @return array
     */
    public function validate_comment($commentdata) {
        // Skip for trackbacks and pingbacks
        if (!empty($commentdata['comment_type']) && $commentdata['comment_type'] !== 'comment') {
            return $commentdata;
        }
        
        // Skip for logged-in users with moderate capabilities
        if (is_user_logged_in() && current_user_can('moderate_comments')) {
            return $commentdata;
        }
        
        // This is a backup validation in case the early one didn't catch it
        // The early validation should have already blocked spam
        
        return $commentdata;
    }
} 