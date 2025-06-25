<?php
/**
 * SpamXpert Integrations Manager
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integrations
 *
 * Manages all form integrations
 */
class SpamXpert_Integrations {

    /**
     * Active integrations
     *
     * @var array
     */
    private $integrations = array();

    /**
     * Initialize integrations
     */
    public function init() {
        // Load WordPress core integrations
        $this->load_core_integrations();
        
        // Load third-party integrations
        $this->load_third_party_integrations();
        
        // Initialize all active integrations
        foreach ($this->integrations as $integration) {
            if (method_exists($integration, 'init')) {
                $integration->init();
            }
        }
    }

    /**
     * Load WordPress core integrations
     */
    private function load_core_integrations() {
        // Login form integration
        if (spamxpert_is_form_protected('wp_login')) {
            require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-wp-login.php';
            $this->integrations['wp_login'] = new SpamXpert_Integration_WP_Login();
        }
        
        // Registration form integration
        if (spamxpert_is_form_protected('wp_registration')) {
            require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-wp-registration.php';
            $this->integrations['wp_registration'] = new SpamXpert_Integration_WP_Registration();
        }
        
        // Comments form integration
        if (spamxpert_is_form_protected('wp_comments')) {
            require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-wp-comments.php';
            $this->integrations['wp_comments'] = new SpamXpert_Integration_WP_Comments();
        }
    }

    /**
     * Load third-party integrations
     */
    private function load_third_party_integrations() {
        // This will be expanded later for Contact Form 7, Gravity Forms, etc.
        // For now, we're focusing on WordPress core forms
    }

    /**
     * Get an integration instance
     *
     * @param string $integration_id Integration ID
     * @return object|null
     */
    public function get_integration($integration_id) {
        return isset($this->integrations[$integration_id]) ? $this->integrations[$integration_id] : null;
    }

    /**
     * Check if an integration is active
     *
     * @param string $integration_id Integration ID
     * @return bool
     */
    public function is_integration_active($integration_id) {
        return isset($this->integrations[$integration_id]);
    }
} 