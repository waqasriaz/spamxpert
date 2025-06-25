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
class SpamXpert_Integration_WP_Comments {

    /**
     * Initialize the integration
     */
    public function init() {
        // Add honeypot fields to comment form
        add_action('comment_form', array($this, 'add_honeypot_fields'));
        
        // For themes that use comment_form_after_fields
        add_action('comment_form_after_fields', array($this, 'add_honeypot_fields_after'));
        
        // For logged in users (they don't see the fields section)
        add_action('comment_form_logged_in_after', array($this, 'add_honeypot_fields_logged_in'));
        
        // Validate comment submission
        add_filter('preprocess_comment', array($this, 'validate_comment'), 1);
        
        // Alternative validation hook
        add_action('pre_comment_on_post', array($this, 'validate_comment_early'));
    }

    /**
     * Add honeypot fields to comment form
     */
    public function add_honeypot_fields() {
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
     * Add honeypot fields after form fields
     */
    public function add_honeypot_fields_after() {
        $this->add_honeypot_fields();
    }

    /**
     * Add honeypot fields for logged in users
     */
    public function add_honeypot_fields_logged_in() {
        $this->add_honeypot_fields();
    }

    /**
     * Validate comment submission early
     */
    public function validate_comment_early() {
        // Skip if plugin is disabled
        if (!spamxpert_is_enabled()) {
            return;
        }
        
        // Get validator
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return;
        }
        
        // Validate the form submission
        $validation_result = $validator->validate_submission($_POST, 'wp_comments');
        
        if ($validation_result !== true) {
            // Log the spam attempt
            spamxpert_log_spam(array(
                'form_type' => 'wp_comments',
                'reason' => 'validation_failed',
                'form_data' => array(
                    'author' => isset($_POST['author']) ? $_POST['author'] : '',
                    'email' => isset($_POST['email']) ? $_POST['email'] : '',
                    'comment' => isset($_POST['comment']) ? substr($_POST['comment'], 0, 100) : '',
                    'post_id' => isset($_POST['comment_post_ID']) ? $_POST['comment_post_ID'] : '',
                    'ip' => spamxpert_get_user_ip()
                )
            ));
            
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
        
        // Skip if plugin is disabled
        if (!spamxpert_is_enabled()) {
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