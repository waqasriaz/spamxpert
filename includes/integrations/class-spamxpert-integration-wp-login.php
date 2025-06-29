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
        
        // Skip validation for Houzez custom login form (handled by Houzez integration)
        if (isset($_POST['action']) && $_POST['action'] === 'houzez_login') {
            return $user;
        }
        
        // Skip validation for other AJAX login actions that have their own integrations
        $skip_actions = apply_filters('spamxpert_skip_wp_login_validation_actions', array(
            'houzez_login',
            'houzez_register'
        ));
        
        if (isset($_POST['action']) && in_array($_POST['action'], $skip_actions)) {
            return $user;
        }
        
        // Only validate if this is a standard WordPress login form submission
        // Check if we're on wp-login.php or if honeypot fields exist
        $is_wp_login_page = (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], 'wp-login.php') !== false);
        $has_honeypot_fields = false;
        
        // Check if any honeypot fields from our wp_login form exist
        foreach ($_POST as $key => $value) {
            if (strpos($key, '_hp') !== false || strpos($key, 'spamxpert_') === 0) {
                $has_honeypot_fields = true;
                break;
            }
        }
        
        // Skip validation if not on wp-login.php and no honeypot fields present and not doing the wp_login action
        if (!$is_wp_login_page && !$has_honeypot_fields && !doing_action('wp_login')) {
            return $user;
        }
        
        // Get validator
        $validator = SpamXpert::get_instance()->get_module('validator');
        if (!$validator) {
            return $user;
        }
        
        // Check if this form has SpamXpert fields but was generated by another integration
        // This happens when Elementor (or other) forms submit to wp-login.php
        if ($has_honeypot_fields && isset($_POST['spamxpert_nonce'])) {
            // Check if the nonce is valid for wp_login
            $nonce_valid_for_wp_login = wp_verify_nonce($_POST['spamxpert_nonce'], 'spamxpert_form_wp_login');
            
            if (!$nonce_valid_for_wp_login) {
                // This form has SpamXpert protection from another integration
                // We should validate honeypot and time trap but skip nonce validation
                $honeypot = SpamXpert::get_instance()->get_module('honeypot');
                $time_trap = SpamXpert::get_instance()->get_module('time_trap');
                
                // Validate honeypot
                if ($honeypot) {
                    $honeypot_result = $honeypot->validate($_POST, 'other_login');
                    if ($honeypot_result !== true) {
                        return new WP_Error(
                            'spamxpert_blocked',
                            __('<strong>ERROR</strong>: Invalid login attempt detected.', 'spamxpert')
                        );
                    }
                }
                
                // Validate time trap
                if ($time_trap && isset($_POST['spamxpert_time'])) {
                    $time_result = $time_trap->validate($_POST['spamxpert_time'], 'wp_login');
                    if ($time_result !== true) {
                        return new WP_Error(
                            'spamxpert_blocked',
                            __('<strong>ERROR</strong>: Invalid login attempt detected.', 'spamxpert')
                        );
                    }
                }
                
                // If honeypot and time trap passed, allow login
                // We skip nonce validation because it was generated for a different form type
                return $user;
            }
            // If nonce is valid for wp_login, continue with standard validation
        }
        
        // For standard WordPress login forms, do full validation
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