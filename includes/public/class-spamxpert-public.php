<?php
/**
 * SpamXpert Public Module
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Public
 *
 * Handles public-facing functionality
 */
class SpamXpert_Public {

    /**
     * Initialize the public module
     */
    public function init() {
        // Add inline JavaScript
        add_action('wp_footer', array($this, 'add_inline_scripts'), 999);
        
        // Set JavaScript detection cookie
        add_action('init', array($this, 'handle_js_detection'));
        
        // Add debug mode class to body if enabled
        if (get_option('spamxpert_debug_mode', '0') === '1') {
            add_filter('body_class', array($this, 'add_debug_body_class'));
        }
    }
    
    /**
     * Add debug mode class to body
     *
     * @param array $classes Body classes
     * @return array
     */
    public function add_debug_body_class($classes) {
        $classes[] = 'spamxpert-debug';
        return $classes;
    }

    /**
     * Add inline scripts for spam protection
     */
    public function add_inline_scripts() {
        // Only add if plugin is enabled
        if (!spamxpert_is_enabled()) {
            return;
        }
        
        // Only add on pages that might have forms
        if (!$this->should_load_assets()) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function() {
            // Set JavaScript detection cookie
            document.cookie = "spamxpert_js=1; path=/; max-age=86400";
            
            // Time tracking for forms
            var spamxpert_time_threshold = <?php echo intval(get_option('spamxpert_time_threshold', 3)); ?>;
            var forms = document.querySelectorAll('form');
            
            forms.forEach(function(form) {
                var startTime = Date.now();
                
                // Add submit handler
                form.addEventListener('submit', function(e) {
                    var elapsed = (Date.now() - startTime) / 1000;
                    
                    // Add elapsed time as hidden field
                    var timeField = document.createElement('input');
                    timeField.type = 'hidden';
                    timeField.name = 'spamxpert_elapsed_time';
                    timeField.value = elapsed;
                    form.appendChild(timeField);
                    
                    // Debug mode logging
                    <?php if (get_option('spamxpert_debug_mode', '0') === '1'): ?>
                    console.log('SpamXpert: Form submission time:', elapsed, 'seconds');
                    <?php endif; ?>
                });
                
                // Make honeypot fields harder to detect
                var honeypotFields = form.querySelectorAll('.spamxpert-hp-field input');
                honeypotFields.forEach(function(field) {
                    // Ensure field stays empty
                    field.addEventListener('change', function() {
                        if (this.value !== '') {
                            <?php if (get_option('spamxpert_debug_mode', '0') === '1'): ?>
                            console.warn('SpamXpert: Honeypot field filled!');
                            <?php endif; ?>
                        }
                    });
                });
            });
            
            // Additional bot detection
            var mouseMovements = 0;
            var keyPresses = 0;
            
            document.addEventListener('mousemove', function() {
                mouseMovements++;
            });
            
            document.addEventListener('keypress', function() {
                keyPresses++;
            });
            
            // Store behavior data
            window.spamxpert_behavior = {
                mouse: function() { return mouseMovements; },
                keys: function() { return keyPresses; }
            };
        })();
        </script>
        <?php
    }

    /**
     * Handle JavaScript detection
     */
    public function handle_js_detection() {
        // Check if JS detection parameter is present
        if (isset($_GET['spamxpert_js_check'])) {
            // Set cookie and redirect
            setcookie('spamxpert_js', '1', time() + 86400, '/');
            
            // Remove parameter and redirect
            $redirect_url = remove_query_arg('spamxpert_js_check');
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Check if the current page should have protection
     *
     * @return bool
     */
    private function should_load_assets() {
        // Check if we're on a page with forms
        if (is_singular() || is_page() || is_single()) {
            return true;
        }
        
        // Check for specific pages
        $current_url = $_SERVER['REQUEST_URI'];
        $form_pages = array('wp-login.php', 'wp-signup.php');
        
        foreach ($form_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                return true;
            }
        }
        
        // Allow themes and plugins to filter
        return apply_filters('spamxpert_should_load_assets', false);
    }
} 