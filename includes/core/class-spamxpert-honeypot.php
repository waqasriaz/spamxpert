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
        
        // Add nonce field
        $html .= wp_nonce_field('spamxpert_form_' . $form_id, 'spamxpert_nonce', true, false);
        
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
        
        // Check if honeypot fields exist in session
        if (!isset($_SESSION[$session_key])) {
            return __('Session expired. Please refresh the page and try again.', 'spamxpert');
        }
        
        $honeypot_fields = $_SESSION[$session_key];
        
        // Check each honeypot field
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