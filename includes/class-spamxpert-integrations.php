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
        
        // Note: Integrations that extend SpamXpert_Integration_Base are automatically
        // initialized by their constructor, so we don't need to manually call init()
    }

    /**
     * Load WordPress core integrations
     */
    private function load_core_integrations() {
        // Login form integration
        require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-wp-login.php';
        $this->integrations['wp_login'] = new SpamXpert_Integration_WP_Login();
        
        // Registration form integration
        require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-wp-registration.php';
        $this->integrations['wp_registration'] = new SpamXpert_Integration_WP_Registration();
        
        // Comments form integration
        require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-wp-comments.php';
        $this->integrations['wp_comments'] = new SpamXpert_Integration_WP_Comments();
    }

    /**
     * Load third-party integrations
     */
    private function load_third_party_integrations() {
        // Contact Form 7 integration
        if (class_exists('WPCF7')) {
            require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-cf7.php';
            $this->integrations['cf7'] = new SpamXpert_Integration_CF7();
        }
        
        // Houzez theme integration
        // The integration class will check if Houzez is active
        require_once SPAMXPERT_PLUGIN_DIR . 'includes/integrations/class-spamxpert-integration-houzez.php';
        $this->integrations['houzez'] = new SpamXpert_Integration_Houzez();
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