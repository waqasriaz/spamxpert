<?php
/**
 * SpamXpert Admin Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Admin
 *
 * Handles admin interface
 */
class SpamXpert_Admin {

    /**
     * Initialize the admin module
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Handle admin actions
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Clean up notices on our pages
        add_action('admin_head', array($this, 'clean_admin_notices'));
        add_action('in_admin_header', array($this, 'start_notice_capture'), 1);
        add_action('admin_notices', array($this, 'end_notice_capture'), 999);
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('SpamXpert', 'spamxpert'),
            __('SpamXpert', 'spamxpert'),
            'manage_options',
            'spamxpert',
            array($this, 'render_dashboard_page'),
            'dashicons-shield-alt',
            80
        );
        
        // Dashboard submenu
        add_submenu_page(
            'spamxpert',
            __('Dashboard', 'spamxpert'),
            __('Dashboard', 'spamxpert'),
            'manage_options',
            'spamxpert',
            array($this, 'render_dashboard_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'spamxpert',
            __('Settings', 'spamxpert'),
            __('Settings', 'spamxpert'),
            'manage_options',
            'spamxpert-settings',
            array($this, 'render_settings_page')
        );
        
        // Logs submenu
        add_submenu_page(
            'spamxpert',
            __('Spam Logs', 'spamxpert'),
            __('Spam Logs', 'spamxpert'),
            'manage_options',
            'spamxpert-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        // Get logger instance
        $logger = SpamXpert::get_instance()->get_module('logger');
        $stats = $logger ? $logger->get_dashboard_stats() : array();
        
        // Include dashboard template
        include SPAMXPERT_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get settings instance
        $settings = SpamXpert::get_instance()->get_module('settings');
        
        // Include settings template
        include SPAMXPERT_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        // Check if pro
        if (!spamxpert_is_pro()) {
            // Generate dummy data for free version
            $result = $this->get_dummy_logs_data();
            $filters = array(
                'page' => 1,
                'per_page' => 20,
                'form_type' => '',
                'ip_address' => '',
                'date_from' => '',
                'date_to' => ''
            );
        } else {
            // Get logger instance for pro version
            $logger = SpamXpert::get_instance()->get_module('logger');
            
            // Get current page
            $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            
            // Get filters
            $filters = array(
                'page' => $current_page,
                'per_page' => 20,
                'form_type' => isset($_GET['form_type']) ? sanitize_text_field($_GET['form_type']) : '',
                'ip_address' => isset($_GET['ip_address']) ? sanitize_text_field($_GET['ip_address']) : '',
                'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
                'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : ''
            );
            
            // Get logs
            $result = $logger ? $logger->get_logs($filters) : array('logs' => array(), 'total' => 0, 'pages' => 0);
        }
        
        // Include logs template
        include SPAMXPERT_PLUGIN_DIR . 'templates/admin/logs.php';
    }
    
    /**
     * Get dummy logs data for free version
     */
    private function get_dummy_logs_data() {
        $dummy_logs = array();
        $current_time = current_time('timestamp');
        
        // Generate 20 dummy log entries
        for ($i = 0; $i < 20; $i++) {
            $log = new stdClass();
            $log->id = $i + 1;
            // Random time intervals for more realistic data
            $time_offset = $i * rand(1800, 7200) + rand(0, 1800); // 30 min to 2.5 hours apart
            $log->blocked_at = date('Y-m-d H:i:s', $current_time - $time_offset);
            
            // Random form types
            $form_types = array('wp_login', 'wp_registration', 'wp_comments', 'contact_form_7', 'houzez_contact');
            $log->form_type = $form_types[array_rand($form_types)];
            
            // Random IPs (mix of realistic spam IPs)
            $ips = array(
                '185.220.' . rand(100, 103) . '.' . rand(1, 255),
                '45.155.' . rand(204, 207) . '.' . rand(1, 255),
                '104.244.' . rand(72, 79) . '.' . rand(1, 255),
                '162.247.' . rand(72, 75) . '.' . rand(1, 255),
                '199.195.' . rand(250, 255) . '.' . rand(1, 255)
            );
            $log->ip_address = $ips[array_rand($ips)];
            
            // Random reasons (mix of free and pro detection methods)
            $reasons = array(
                'honeypot_field', 
                'time_trap', 
                'ai_spam_score',
                'ip_reputation',
                'behavioral_analysis',
                'geo_blocking',
                'rate_limiting',
                'keyword_match'
            );
            $log->spam_reason = $reasons[array_rand($reasons)];
            
            // Random score
            $log->spam_score = rand(60, 100);
            
            // Random user agents
            $agents = array(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'SpamBot/1.0 (automated submission tool)',
                'Python-urllib/3.8'
            );
            $log->user_agent = $agents[array_rand($agents)];
            
            $dummy_logs[] = $log;
        }
        
        return array(
            'logs' => $dummy_logs,
            'total' => 147, // Fake total
            'pages' => 8    // Fake pages
        );
    }

    /**
     * Save settings
     *
     * @param array $post_data POST data
     */
    private function save_settings($post_data) {
        // Update individual options for compatibility
        if (isset($post_data['spamxpert_enabled'])) {
            update_option('spamxpert_enabled', '1');
        } else {
            update_option('spamxpert_enabled', '0');
        }
        
        if (isset($post_data['spamxpert_honeypot_count'])) {
            update_option('spamxpert_honeypot_count', absint($post_data['spamxpert_honeypot_count']));
        }
        
        if (isset($post_data['spamxpert_time_threshold'])) {
            update_option('spamxpert_time_threshold', absint($post_data['spamxpert_time_threshold']));
        }
        
        if (isset($post_data['spamxpert_log_spam'])) {
            update_option('spamxpert_log_spam', '1');
        } else {
            update_option('spamxpert_log_spam', '0');
        }
        
        if (isset($post_data['spamxpert_log_retention_days'])) {
            update_option('spamxpert_log_retention_days', absint($post_data['spamxpert_log_retention_days']));
        }
        
        // Update form protection settings
        $form_types = array(
            'wp_login', 
            'wp_registration', 
            'wp_comments',
            'cf7',
            'gravity_forms',
            'wpforms',
            'ninja_forms',
            'elementor_forms',
            'houzez'
        );
        foreach ($form_types as $form_type) {
            $option_name = 'spamxpert_protect_' . $form_type;
            if (isset($post_data[$option_name])) {
                update_option($option_name, '1');
            } else {
                update_option($option_name, '0');
            }
        }
        
        // Update debug mode
        if (isset($post_data['spamxpert_debug_mode'])) {
            update_option('spamxpert_debug_mode', '1');
        } else {
            update_option('spamxpert_debug_mode', '0');
        }
        
        // Update remove data on uninstall
        if (isset($post_data['spamxpert_remove_data_on_uninstall'])) {
            update_option('spamxpert_remove_data_on_uninstall', '1');
        } else {
            update_option('spamxpert_remove_data_on_uninstall', '0');
        }
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        // Handle settings save
        if (isset($_POST['spamxpert_save_settings'])) {
            // Check if we're on the settings page
            if (!isset($_GET['page']) || $_GET['page'] !== 'spamxpert-settings') {
                return;
            }
            
            check_admin_referer('spamxpert_settings');
            
            if (current_user_can('manage_options')) {
                // Process and save settings
                $this->save_settings($_POST);
                
                // Build proper redirect URL
                $redirect_url = admin_url('admin.php?page=spamxpert-settings&settings-updated=true');
                
                // Redirect to avoid resubmission
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
        
        // Handle log deletion
        if (isset($_POST['action']) && $_POST['action'] === 'delete_logs') {
            check_admin_referer('spamxpert_delete_logs');
            
            if (current_user_can('manage_options')) {
                $logger = SpamXpert::get_instance()->get_module('logger');
                if ($logger && isset($_POST['log_ids'])) {
                    $logger->delete_logs($_POST['log_ids']);
                }
            }
            
            wp_redirect(admin_url('admin.php?page=spamxpert-logs&deleted=true'));
            exit;
        }
        
        // Handle clear all logs
        if (isset($_GET['action']) && $_GET['action'] === 'clear_all_logs') {
            check_admin_referer('spamxpert_clear_logs');
            
            if (current_user_can('manage_options')) {
                $logger = SpamXpert::get_instance()->get_module('logger');
                if ($logger) {
                    $logger->clear_all_logs();
                }
            }
            
            wp_redirect(admin_url('admin.php?page=spamxpert-logs&cleared=true'));
            exit;
        }
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Settings updated notice
        if (isset($_GET['settings-updated'])) {
            ?>
            <div class="notice notice-success is-dismissible spamxpert-notice">
                <p><?php _e('Settings saved successfully.', 'spamxpert'); ?></p>
            </div>
            <?php
        }
        
        // Logs deleted notice
        if (isset($_GET['deleted'])) {
            ?>
            <div class="notice notice-success is-dismissible spamxpert-notice">
                <p><?php _e('Selected logs have been deleted.', 'spamxpert'); ?></p>
            </div>
            <?php
        }
        
        // Logs cleared notice
        if (isset($_GET['cleared'])) {
            ?>
            <div class="notice notice-success is-dismissible spamxpert-notice">
                <p><?php _e('All logs have been cleared.', 'spamxpert'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Clean admin notices on SpamXpert pages
     */
    public function clean_admin_notices() {
        $screen = get_current_screen();
        
        // Check if we're on a SpamXpert page
        if (!$screen || strpos($screen->id, 'spamxpert') === false) {
            return;
        }
        
        // Remove all admin notices from other plugins
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        
        // Re-add only our admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Add custom CSS to ensure clean layout
        ?>
        <style>
            /* Hide any notices that might slip through */
            .wrap > .notice:not(.spamxpert-notice),
            .wrap > .error:not(.spamxpert-notice),
            .wrap > .updated:not(.spamxpert-notice),
            .wrap > .update-nag:not(.spamxpert-notice),
            #wpbody-content > .notice:not(.spamxpert-notice),
            #wpbody-content > .error:not(.spamxpert-notice),
            #wpbody-content > .updated:not(.spamxpert-notice),
            #wpbody-content > .update-nag:not(.spamxpert-notice) {
                display: none !important;
            }
            
            /* Also hide common notification areas from other plugins */
            .woocommerce-layout__notice-list,
            .woocommerce-admin-notice,
            .redux-messageredux-notice,
            .vc_license-activation-notice,
            .elementor-message,
            .notice-houzez,
            .houzez-admin-notice {
                display: none !important;
            }
            
            /* Clean up any extra spacing */
            .wrap {
                margin-top: 20px;
            }
            
            /* Ensure our notices look good */
            .spamxpert-notice {
                margin: 5px 0 15px !important;
            }
        </style>
        <?php
    }
    
    /**
     * Start capturing output to filter notices
     */
    public function start_notice_capture() {
        $screen = get_current_screen();
        
        // Only on SpamXpert pages
        if (!$screen || strpos($screen->id, 'spamxpert') === false) {
            return;
        }
        
        // Start output buffering
        ob_start();
    }
    
    /**
     * End notice capture and filter output
     */
    public function end_notice_capture() {
        $screen = get_current_screen();
        
        // Only on SpamXpert pages
        if (!$screen || strpos($screen->id, 'spamxpert') === false) {
            return;
        }
        
        // Get the buffered content
        $content = ob_get_clean();
        
        // Only output SpamXpert notices
        if (strpos($content, 'spamxpert-notice') !== false) {
            echo $content;
        }
    }
} 