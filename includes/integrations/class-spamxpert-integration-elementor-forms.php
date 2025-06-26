<?php
/**
 * SpamXpert Elementor Forms Integration
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integration_Elementor_Forms
 *
 * Handles integration with Elementor Pro Forms
 */
class SpamXpert_Integration_Elementor_Forms extends SpamXpert_Integration_Base {

    /**
     * Integration name
     * @var string
     */
    protected $name = 'Elementor Forms';
    
    /**
     * Integration slug
     * @var string
     */
    protected $slug = 'elementor_forms';

    /**
     * Check if the integration is available
     *
     * @return bool
     */
    public function is_available() {
        // Check if Elementor Pro is active using class check (more reliable)
        return class_exists('\ElementorPro\Plugin') && 
               class_exists('\ElementorPro\Modules\Forms\Module');
    }

    /**
     * Initialize the integration
     */
    protected function init() {
        // Validate form submission
        add_action('elementor_pro/forms/validation', array($this, 'validate_form'), 10, 2);
        
        // Add JavaScript to inject honeypot fields and handle form submissions
        add_action('wp_footer', array($this, 'add_form_scripts'), 20);
    }

    /**
     * Get honeypot HTML
     *
     * @return string
     */
    private function get_honeypot_html() {
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if ($honeypot) {
            return $honeypot->render_fields('elementor_forms');
        }
        return '';
    }

    /**
     * Output honeypot fields
     */
    public function output_honeypot_fields() {
        echo $this->get_honeypot_html();
    }

    /**
     * Validate form submission
     *
     * @param object $record Form record
     * @param object $ajax_handler AJAX handler
     */
    public function validate_form($record, $ajax_handler) {
        // Get form data
        $raw_fields = $record->get('fields');
        $form_data = array();
        
        // Convert Elementor fields format to our format
        foreach ($raw_fields as $id => $field) {
            $form_data[$id] = $field['value'];
        }
        
        // Add POST data for honeypot fields
        foreach ($_POST as $key => $value) {
            if (!isset($form_data[$key])) {
                $form_data[$key] = $value;
            }
        }
        
        // Get validator module directly
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($form_data, 'elementor_forms');
            
            if ($result !== true) {
                // Modules already logged the spam attempt
                $ajax_handler->add_error('form', __('Your submission was flagged as spam.', 'spamxpert'));
            }
        }
    }
    
    /**
     * Add JavaScript for Elementor forms
     */
    public function add_form_scripts() {
        // Get honeypot HTML to inject
        $honeypot_html = $this->get_honeypot_html();
        
        // Escape for JavaScript
        $honeypot_html_escaped = json_encode($honeypot_html);
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Honeypot HTML to inject
            var honeypotHtml = <?php echo $honeypot_html_escaped; ?>;
            
            // Function to inject honeypot fields
            function injectHoneypotFields(form) {
                // Check if honeypot already injected
                if (form.find('.spamxpert-hp-field').length > 0) {
                    return;
                }
                
                // Find the submit button group
                var submitGroup = form.find('.elementor-field-type-submit');
                
                if (submitGroup.length > 0) {
                    // Insert honeypot fields before submit button
                    submitGroup.before(honeypotHtml);
                }
            }
            
            // Inject honeypot fields on page load
            $('.elementor-form').each(function() {
                injectHoneypotFields($(this));
            });
            
            // Watch for dynamically loaded forms
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    $(mutation.addedNodes).find('.elementor-form').each(function() {
                        injectHoneypotFields($(this));
                    });
                });
            });
            
            // Start observing document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            // Handle Elementor form submissions
            $(document).on('submit_success', function(event, response) {
                // Clear honeypot fields after successful submission
                var form = $(event.target).closest('form');
                var honeypotFields = form.find('.spamxpert-hp-field input');
                
                honeypotFields.each(function() {
                    if ($(this).is(':checkbox')) {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                });
                
                // Re-inject fresh honeypot fields
                form.find('.spamxpert-hp-field').remove();
                injectHoneypotFields(form);
            });
            
            // Ensure honeypot fields are empty before submission
            $(document).on('elementor/forms/before_submit', function(event, form) {
                var honeypotFields = form.find('.spamxpert-hp-field input');
                
                honeypotFields.each(function() {
                    if ($(this).is(':checkbox')) {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                });
            });
            
            // Also handle Elementor popup forms
            $(document).on('elementor/popup/show', function(event, id, instance) {
                setTimeout(function() {
                    $('.elementor-popup-modal .elementor-form').each(function() {
                        injectHoneypotFields($(this));
                    });
                }, 100);
            });
        });
        </script>
        <?php
    }
} 