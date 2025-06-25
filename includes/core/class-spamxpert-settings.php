<?php
/**
 * SpamXpert Settings Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Settings
 *
 * Handles plugin settings
 */
class SpamXpert_Settings {

    /**
     * Settings option name
     *
     * @var string
     */
    private $option_name = 'spamxpert_settings';

    /**
     * Default settings
     *
     * @var array
     */
    private $defaults = array();

    /**
     * Initialize the module
     */
    public function init() {
        // Set default values
        $this->set_defaults();
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Set default settings values
     */
    private function set_defaults() {
        $this->defaults = array(
            'general' => array(
                'enabled' => '1',
                'honeypot_count' => '2',
                'time_threshold' => '3',
                'log_spam' => '1',
                'log_retention_days' => '30',
                'remove_data_on_uninstall' => '0',
                'debug_mode' => '0'
            ),
            'forms' => array(
                'wp_login' => '1',
                'wp_registration' => '1',
                'wp_comments' => '1',
                'contact_form_7' => '1',
                'gravity_forms' => '1',
                'wpforms' => '1',
                'ninja_forms' => '1',
                'elementor_forms' => '1',
                'houzez_forms' => '1'
            ),
            'advanced' => array(
                'custom_css_classes' => '',
                'excluded_forms' => '',
                'whitelisted_ips' => '',
                'blacklisted_ips' => ''
            )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'spamxpert_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Get a setting value
     *
     * @param string $key Setting key (can use dot notation)
     * @param mixed $default Default value
     * @return mixed
     */
    public function get($key, $default = null) {
        $settings = get_option($this->option_name, $this->defaults);
        
        // Handle dot notation
        $keys = explode('.', $key);
        $value = $settings;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default !== null ? $default : $this->get_default($key);
            }
        }
        
        return $value;
    }

    /**
     * Get default value for a setting
     *
     * @param string $key Setting key
     * @return mixed
     */
    private function get_default($key) {
        $keys = explode('.', $key);
        $value = $this->defaults;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    /**
     * Update a setting value
     *
     * @param string $key Setting key
     * @param mixed $value New value
     */
    public function update($key, $value) {
        $settings = get_option($this->option_name, $this->defaults);
        
        // Handle dot notation
        $keys = explode('.', $key);
        $current = &$settings;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k])) {
                    $current[$k] = array();
                }
                $current = &$current[$k];
            }
        }
        
        update_option($this->option_name, $settings);
    }

    /**
     * Get all settings
     *
     * @return array
     */
    public function get_all() {
        return get_option($this->option_name, $this->defaults);
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $settings Settings to sanitize
     * @return array
     */
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        // Sanitize general settings
        if (isset($settings['general'])) {
            $sanitized['general'] = array(
                'enabled' => isset($settings['general']['enabled']) ? '1' : '0',
                'honeypot_count' => absint($settings['general']['honeypot_count']),
                'time_threshold' => absint($settings['general']['time_threshold']),
                'log_spam' => isset($settings['general']['log_spam']) ? '1' : '0',
                'log_retention_days' => absint($settings['general']['log_retention_days']),
                'remove_data_on_uninstall' => isset($settings['general']['remove_data_on_uninstall']) ? '1' : '0',
                'debug_mode' => isset($settings['general']['debug_mode']) ? '1' : '0'
            );
        }
        
        // Sanitize form settings
        if (isset($settings['forms'])) {
            foreach ($settings['forms'] as $form_type => $enabled) {
                $sanitized['forms'][$form_type] = $enabled ? '1' : '0';
            }
        }
        
        // Sanitize advanced settings
        if (isset($settings['advanced'])) {
            $sanitized['advanced'] = array(
                'custom_css_classes' => sanitize_text_field($settings['advanced']['custom_css_classes']),
                'excluded_forms' => sanitize_textarea_field($settings['advanced']['excluded_forms']),
                'whitelisted_ips' => sanitize_textarea_field($settings['advanced']['whitelisted_ips']),
                'blacklisted_ips' => sanitize_textarea_field($settings['advanced']['blacklisted_ips'])
            );
        }
        
        return $sanitized;
    }

    /**
     * Reset settings to defaults
     */
    public function reset_to_defaults() {
        update_option($this->option_name, $this->defaults);
    }
} 