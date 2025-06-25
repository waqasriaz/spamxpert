<?php
/**
 * WordPress Login Form Integration
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integration_WP_Login
 *
 * Handles WordPress login form protection
 */
class SpamXpert_Integration_WP_Login extends SpamXpert_Integration_Base {

    /**
     * Integration name
     * @var string
     */
    protected $name = 'WordPress Login Form';
    
    /**
     * Integration slug
     * @var string
     */
    protected $slug = 'wp_login';

    /**
     * Check if the integration is available
     *
     * @return bool
     */
    public function is_available() {
        // WordPress login form is always available
        return true;
    }

    /**
     * Initialize the integration
     */
    protected function init() {
        // Add honeypot fields to login form
        add_action('login_form', array($this, 'output_honeypot_fields'), 99);
        
        // Validate login form submission
        add_filter('authenticate', array($this, 'validate_login'), 30, 3);
        
        // Add custom login error handling
        add_filter('login_errors', array($this, 'custom_login_errors'), 10, 1);
    }

    /**
     * Output honeypot fields to login form
     */
    public function output_honeypot_fields() {
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if ($honeypot) {
            echo $honeypot->render_fields('wp_login');
        }
    }

    /**
     * Validate login form submission
     *
     * @param WP_User|WP_Error|null $user WP_User if the user is authenticated. WP_Error or null otherwise.
     * @param string $username Username or email address.
     * @param string $password User password.
     * @return WP_User|WP_Error
     */
    public function validate_login($user, $username, $password) {
        // Skip if already an error or if no credentials provided
        if (is_wp_error($user) || empty($username) || empty($password)) {
            return $user;
        }
        
        // Get validator
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return $user;
        }
        
        // Validate the form submission
        $validation_result = $validator->validate_submission($_POST, $this->slug);
        
        if ($validation_result !== true) {
            // Module already logged the spam attempt with proper details
            // No need to log again at integration level
            
            // Return generic error to avoid giving away information
            return new WP_Error(
                'spamxpert_blocked',
                __('<strong>ERROR</strong>: Invalid login attempt detected.', 'spamxpert')
            );
        }
        
        return $user;
    }

    /**
     * Customize login error messages
     *
     * @param string $error Error message
     * @return string
     */
    public function custom_login_errors($error) {
        // Check if this is our error
        if (strpos($error, 'spamxpert_blocked') !== false) {
            // You can customize the error message here
            return $error;
        }
        
        return $error;
    }
} 