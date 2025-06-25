<?php
/**
 * WordPress Registration Form Integration
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integration_WP_Registration
 *
 * Handles WordPress registration form protection
 */
class SpamXpert_Integration_WP_Registration {

    /**
     * Initialize the integration
     */
    public function init() {
        // Add honeypot fields to registration form
        add_action('register_form', array($this, 'add_honeypot_fields'));
        
        // Also support multisite signup form
        add_action('signup_extra_fields', array($this, 'add_honeypot_fields'));
        
        // Validate registration
        add_filter('registration_errors', array($this, 'validate_registration'), 10, 3);
        
        // Validate multisite signup
        add_filter('wpmu_validate_user_signup', array($this, 'validate_multisite_signup'));
        add_filter('wpmu_validate_blog_signup', array($this, 'validate_multisite_blog_signup'));
    }

    /**
     * Add honeypot fields to registration form
     */
    public function add_honeypot_fields() {
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if ($honeypot) {
            echo $honeypot->render_fields('wp_registration');
        }
    }

    /**
     * Validate registration form submission
     *
     * @param WP_Error $errors A WP_Error object containing any errors encountered during registration.
     * @param string $sanitized_user_login User's username after it has been sanitized.
     * @param string $user_email User's email.
     * @return WP_Error
     */
    public function validate_registration($errors, $sanitized_user_login, $user_email) {
        // Skip if plugin is disabled
        if (!spamxpert_is_enabled()) {
            return $errors;
        }
        
        // Get validator
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return $errors;
        }
        
        // Validate the form submission
        $validation_result = $validator->validate_submission($_POST, 'wp_registration');
        
        if ($validation_result !== true) {
            // Log the spam attempt
            spamxpert_log_spam(array(
                'form_type' => 'wp_registration',
                'reason' => 'validation_failed',
                'form_data' => array(
                    'username' => $sanitized_user_login,
                    'email' => $user_email,
                    'ip' => spamxpert_get_user_ip()
                )
            ));
            
            // Add error
            $errors->add(
                'spamxpert_blocked',
                __('<strong>ERROR</strong>: Your registration appears to be spam. Please try again.', 'spamxpert')
            );
        }
        
        return $errors;
    }

    /**
     * Validate multisite user signup
     *
     * @param array $result Signup validation results
     * @return array
     */
    public function validate_multisite_signup($result) {
        // Skip if plugin is disabled
        if (!spamxpert_is_enabled()) {
            return $result;
        }
        
        // Get validator
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return $result;
        }
        
        // Validate the form submission
        $validation_result = $validator->validate_submission($_POST, 'wp_registration');
        
        if ($validation_result !== true) {
            // Log the spam attempt
            spamxpert_log_spam(array(
                'form_type' => 'wp_registration_multisite',
                'reason' => 'validation_failed',
                'form_data' => array(
                    'username' => isset($result['user_name']) ? $result['user_name'] : '',
                    'email' => isset($result['user_email']) ? $result['user_email'] : '',
                    'ip' => spamxpert_get_user_ip()
                )
            ));
            
            // Add error
            $result['errors']->add(
                'spamxpert_blocked',
                __('Your registration appears to be spam. Please try again.', 'spamxpert')
            );
        }
        
        return $result;
    }

    /**
     * Validate multisite blog signup
     *
     * @param array $result Blog signup validation results
     * @return array
     */
    public function validate_multisite_blog_signup($result) {
        // Use the same validation as user signup
        return $this->validate_multisite_signup($result);
    }
} 