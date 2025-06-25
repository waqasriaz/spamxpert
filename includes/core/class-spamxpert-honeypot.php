<?php
/**
 * SpamXpert Honeypot Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Honeypot
 *
 * Handles honeypot field generation and validation
 */
class SpamXpert_Honeypot {

    /**
     * Honeypot fields storage
     *
     * @var array
     */
    private $honeypot_fields = array();

    /**
     * Initialize the module
     */
    public function init() {
        // Hook into init to start session properly
        add_action('init', array($this, 'start_session'), 1);
        
        // Also start session for AJAX requests
        add_action('admin_init', array($this, 'start_session'), 1);
        add_action('wp_ajax_houzez_property_agent_contact', array($this, 'start_session'), 0);
        add_action('wp_ajax_nopriv_houzez_property_agent_contact', array($this, 'start_session'), 0);
        add_action('wp_ajax_houzez_schedule_send_message', array($this, 'start_session'), 0);
        add_action('wp_ajax_nopriv_houzez_schedule_send_message', array($this, 'start_session'), 0);
        add_action('wp_ajax_houzez_ele_inquiry_form', array($this, 'start_session'), 0);
        add_action('wp_ajax_nopriv_houzez_ele_inquiry_form', array($this, 'start_session'), 0);
    }

    /**
     * Start session if needed
     */
    public function start_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Generate honeypot fields
     *
     * @param string $form_id Form identifier
     * @return array
     */
    public function generate_fields($form_id = '') {
        $count = intval(get_option('spamxpert_honeypot_count', 2));
        $fields = array();
        
        for ($i = 0; $i < $count; $i++) {
            $field_name = spamxpert_generate_honeypot_name();
            $field_label = spamxpert_generate_honeypot_label();
            
            $fields[] = array(
                'name' => $field_name,
                'label' => $field_label,
                'type' => $this->get_random_field_type(),
                'id' => 'spamxpert_' . wp_generate_password(8, false, false)
            );
        }
        
        // Store fields in session for validation
        $session_key = $this->get_session_key($form_id);
        $_SESSION[$session_key] = $fields;
        
        // Allow debugging
        $fields = apply_filters('spamxpert_honeypot_fields_generated', $fields, $form_id);
        
        return $fields;
    }

    /**
     * Get random field type
     *
     * @return string
     */
    private function get_random_field_type() {
        $types = array('text', 'email', 'tel', 'url');
        return $types[array_rand($types)];
    }

    /**
     * Render honeypot fields HTML
     *
     * @param string $form_id Form identifier
     * @return string
     */
    public function render_fields($form_id = '') {
        $fields = $this->generate_fields($form_id);
        $html = '';
        
        foreach ($fields as $field) {
            $html .= sprintf(
                '<div class="spamxpert-hp-field" style="position:absolute;left:-9999px;top:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">
                    <label for="%s">%s</label>
                    <input type="%s" name="%s" id="%s" value="" tabindex="-1" autocomplete="off" />
                </div>',
                esc_attr($field['id']),
                esc_html($field['label']),
                esc_attr($field['type']),
                esc_attr($field['name']),
                esc_attr($field['id'])
            );
        }
        
        // Add time field
        $html .= $this->render_time_field($form_id);
        
        // Skip nonce field for forms that have their own nonce protection
        $skip_nonce_forms = apply_filters('spamxpert_skip_nonce_generation', array(
            'houzez_agent_contact',
            'houzez_schedule_tour',
            'houzez_inquiry'
        ));
        
        if (!in_array($form_id, $skip_nonce_forms)) {
            // Add nonce field
            $html .= wp_nonce_field('spamxpert_form_' . $form_id, 'spamxpert_nonce', true, false);
        }
        
        return $html;
    }

    /**
     * Render time field
     *
     * @param string $form_id Form identifier
     * @return string
     */
    private function render_time_field($form_id) {
        $time = time();
        $encrypted_time = $this->encrypt_time($time);
        
        return sprintf(
            '<input type="hidden" name="spamxpert_time" value="%s" />',
            esc_attr($encrypted_time)
        );
    }

    /**
     * Validate honeypot fields
     *
     * @param array $form_data Submitted form data
     * @param string $form_id Form identifier
     * @return bool|string True if valid, error message if not
     */
    public function validate($form_data, $form_id = '') {
        $session_key = $this->get_session_key($form_id);
        $honeypot_fields = array();
        
        // Check if honeypot fields exist in session
        if (isset($_SESSION[$session_key])) {
            $honeypot_fields = $_SESSION[$session_key];
            
            // Check each honeypot field from session
            foreach ($honeypot_fields as $field) {
                if (isset($form_data[$field['name']]) && !empty($form_data[$field['name']])) {
                    // Honeypot field was filled - it's spam!
                    spamxpert_log_spam(array(
                        'form_type' => $form_id,
                        'reason' => 'honeypot_filled',
                        'form_data' => $form_data
                    ));
                    return __('Spam detected: Invalid form submission.', 'spamxpert');
                }
            }
            
            // Clear session data
            unset($_SESSION[$session_key]);
        }
        
        // Also check for any honeypot-pattern fields in the submitted data
        // This handles cases where session might be lost (cached forms, CDN, etc.)
        $honeypot_pattern = apply_filters(
            'spamxpert_honeypot_field_pattern',
            '/^(email|name|phone|website|url|company|address|message)_\w{4}_(hp|check|verify|confirm|validate|field)$/',
            $form_id
        );
        $suspicious_fields = array();
        
        foreach ($form_data as $key => $value) {
            if (preg_match($honeypot_pattern, $key) && !empty($value)) {
                $suspicious_fields[$key] = $value;
            }
        }
        
        if (!empty($suspicious_fields)) {
            // Found filled honeypot fields - it's spam!
            spamxpert_log_spam(array(
                'form_type' => $form_id,
                'reason' => 'honeypot_filled',
                'form_data' => $form_data,
                'filled_honeypots' => $suspicious_fields
            ));
            return __('Spam detected: Invalid form submission.', 'spamxpert');
        }
        
        return true;
    }

    /**
     * Encrypt time value
     *
     * @param int $time Time value
     * @return string
     */
    private function encrypt_time($time) {
        $key = wp_salt('auth');
        return base64_encode($time . '|' . wp_hash($time . $key));
    }

    /**
     * Decrypt time value
     *
     * @param string $encrypted Encrypted time
     * @return int|false
     */
    public function decrypt_time($encrypted) {
        $key = wp_salt('auth');
        $decoded = base64_decode($encrypted);
        
        if (!$decoded || !strpos($decoded, '|')) {
            return false;
        }
        
        list($time, $hash) = explode('|', $decoded, 2);
        
        if (wp_hash($time . $key) !== $hash) {
            return false;
        }
        
        return intval($time);
    }

    /**
     * Get session key
     *
     * @param string $form_id Form identifier
     * @return string
     */
    private function get_session_key($form_id) {
        return 'spamxpert_hp_' . md5($form_id . wp_salt());
    }
} 