<?php
/**
 * Main SpamXpert Plugin Class
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert
 *
 * Main plugin class that handles initialization
 */
class SpamXpert {

    /**
     * Plugin instance
     *
     * @var SpamXpert
     */
    private static $instance = null;

    /**
     * Plugin modules
     *
     * @var array
     */
    private $modules = array();

    /**
     * Get plugin instance
     *
     * @return SpamXpert
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize modules
        $this->init_modules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Initialize modules
        foreach ($this->modules as $module) {
            if (method_exists($module, 'init')) {
                $module->init();
            }
        }
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load helper functions
        require_once SPAMXPERT_PLUGIN_DIR . 'includes/spamxpert-functions.php';
    }

    /**
     * Initialize modules
     */
    private function init_modules() {
        // Core modules
        $this->modules['settings'] = new SpamXpert_Settings();
        $this->modules['logger'] = new SpamXpert_Logger();
        $this->modules['honeypot'] = new SpamXpert_Honeypot();
        $this->modules['time_trap'] = new SpamXpert_Time_Trap();
        $this->modules['validator'] = new SpamXpert_Validator();
        
        // Admin modules
        if (is_admin()) {
            $this->modules['admin'] = new SpamXpert_Admin();
        }
        
        // Public modules
        if (!is_admin()) {
            $this->modules['public'] = new SpamXpert_Public();
        }
        
        // Integration modules
        $this->modules['integrations'] = new SpamXpert_Integrations();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add plugin action links
        add_filter('plugin_action_links_' . SPAMXPERT_PLUGIN_BASENAME, array($this, 'add_action_links'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=spamxpert-settings') . '">' . __('Settings', 'spamxpert') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_public_assets() {
        // Only enqueue if needed
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Enqueue CSS (minimal, < 5KB)
        wp_enqueue_style(
            'spamxpert-public',
            SPAMXPERT_PLUGIN_URL . 'assets/css/spamxpert-public.css',
            array(),
            SPAMXPERT_VERSION
        );
        
        // Enqueue JS (minimal, < 5KB)
        wp_enqueue_script(
            'spamxpert-public',
            SPAMXPERT_PLUGIN_URL . 'assets/js/spamxpert-public.js',
            array('jquery'),
            SPAMXPERT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('spamxpert-public', 'spamxpert_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spamxpert_nonce'),
            'time_threshold' => get_option('spamxpert_time_threshold', 3)
        ));
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        $allowed_hooks = array(
            'toplevel_page_spamxpert',          // Dashboard
            'spamxpert_page_spamxpert-settings', // Settings
            'spamxpert_page_spamxpert-logs'      // Logs
        );
        
        if (!in_array($hook, $allowed_hooks)) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'spamxpert-admin',
            SPAMXPERT_PLUGIN_URL . 'assets/css/spamxpert-admin.css',
            array(),
            SPAMXPERT_VERSION
        );
        
        // Enqueue admin JS
        wp_enqueue_script(
            'spamxpert-admin',
            SPAMXPERT_PLUGIN_URL . 'assets/js/spamxpert-admin.js',
            array('jquery'),
            SPAMXPERT_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('spamxpert-admin', 'spamxpert_admin_l10n', array(
            'debug_warning' => __('Warning: Debug mode will make honeypot fields visible and may reduce spam protection effectiveness. Continue?', 'spamxpert')
        ));
    }

    /**
     * Check if assets should be loaded
     *
     * @return bool
     */
    public function should_load_assets() {
        // Check if we're on a page with forms
        if (is_singular() || is_page() || is_single()) {
            return true;
        }
        
        // Check for specific pages (login, registration, etc.)
        $current_url = $_SERVER['REQUEST_URI'];
        $form_pages = array('wp-login.php', 'wp-signup.php');
        
        foreach ($form_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                return true;
            }
        }
        
        return apply_filters('spamxpert_should_load_assets', false);
    }

    /**
     * Get a module instance
     *
     * @param string $module Module name
     * @return object|null
     */
    public function get_module($module) {
        return isset($this->modules[$module]) ? $this->modules[$module] : null;
    }
} 