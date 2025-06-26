<?php
/**
 * SpamXpert Houzez Integration
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SpamXpert_Integration_Houzez
 *
 * Handles integration with Houzez theme forms
 */
class SpamXpert_Integration_Houzez extends SpamXpert_Integration_Base {

    /**
     * Integration name
     * @var string
     */
    protected $name = 'Houzez Theme';
    
    /**
     * Integration slug
     * @var string
     */
    protected $slug = 'houzez';

    /**
     * Initialize the integration
     */
    protected function init() {

        // Agent Contact Form
        add_action('houzez_property_agent_contact_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_property_agent_contact_validation', array($this, 'validate_agent_form'), 10, 2);
        
        // Schedule Tour Form
        add_action('houzez_schedule_tour_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_schedule_tour_validation', array($this, 'validate_schedule_tour'), 10, 2);
        
        // Inquiry Form (Elementor Widget)
        add_action('houzez_inquiry_form_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_ele_inquiry_form_validation', array($this, 'validate_inquiry_form'), 10, 2);

        // Contact Form (Elementor Widget)
        add_action('houzez_contact_form_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_ele_contact_form_validation', array($this, 'validate_contact_form'), 10, 2);
        
        // Add JavaScript for forms
        add_action('wp_footer', array($this, 'add_form_scripts'), 20);
    }

    /**
     * Check if the integration is available
     *
     * @return bool
     */
    public function is_available() {
        $theme = wp_get_theme();
        $theme_name = $theme->get('Name');
        $parent_theme = $theme->parent() ? $theme->parent()->get('Name') : '';
        
        return ($theme_name === 'Houzez' || $parent_theme === 'Houzez');
    }

    /**
     * Output honeypot fields to forms (action hook callback)
     */
    public function output_honeypot_fields() {
        $honeypot = SpamXpert::get_instance()->get_module('honeypot');
        if ($honeypot) {
            // Map hook names to form IDs to ensure consistency with validation
            $form_id_map = array(
                'houzez_property_agent_contact_fields' => 'houzez_agent_contact',
                'houzez_schedule_tour_fields' => 'houzez_schedule_tour',
                'houzez_inquiry_form_fields' => 'houzez_inquiry',
                'houzez_contact_form_fields' => 'houzez_contact_form'
            );
            
            $current_hook = current_filter();
            $form_id = isset($form_id_map[$current_hook]) ? $form_id_map[$current_hook] : $current_hook;
            
            echo $honeypot->render_fields($form_id);
        }
    }

    /**
     * Validate agent contact form
     *
     * @param array $errors Current errors
     * @param array $data Form data
     * @return array
     */
    public function validate_agent_form($errors, $data) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($data, 'houzez_agent_contact');
            
            if ($result !== true) {
                // Module already logged the spam attempt with proper details
                // No need to log again at integration level
                $errors[] = esc_html__('Your submission was blocked. Please try again.', 'spamxpert');
            }
        }
        
        return $errors;
    }

    /**
     * Validate schedule tour form
     *
     * @param array $errors Current errors
     * @param array $data Form data
     * @return array
     */
    public function validate_schedule_tour($errors, $data) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($data, 'houzez_schedule_tour');
            
            if ($result !== true) {
                // Module already logged the spam attempt with proper details
                // No need to log again at integration level
                $errors[] = esc_html__('Your submission was blocked. Please try again.', 'spamxpert');
            }
        }
        
        return $errors;
    }

    /**
     * Validate property inquiry form
     *
     * @param array $errors Current errors
     * @param array $data Form data
     * @return array
     */
    public function validate_inquiry_form($errors, $data) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($data, 'houzez_inquiry');
            
            if ($result !== true) {
                // Module already logged the spam attempt with proper details
                // No need to log again at integration level
                $errors[] = esc_html__('Your submission was blocked. Please try again.', 'spamxpert');
            }
        }
        
        return $errors;
    }

    /**
     * Validate contact form (Elementor widget)
     *
     * @param array $errors Current errors
     * @param array $data Form data
     * @return array
     */
    public function validate_contact_form($errors, $data) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($data, 'houzez_contact_form');
            
            if ($result !== true) {
                // Module already logged the spam attempt with proper details
                // No need to log again at integration level
                $errors[] = esc_html__('Your submission was blocked. Please try again.', 'spamxpert');
            }
        }
        
        return $errors;
    }

    /**
     * Add JavaScript for Houzez forms
     */
    public function add_form_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle agent contact form
            $('.houzez_agent_property_form').on('click', function(e) {
                var form = $(this).closest('form');
                var honeypotFields = form.find('.spamxpert-hp-field input');
                
                // Clear honeypot fields before submission
                honeypotFields.each(function() {
                    if ($(this).is(':checkbox')) {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                });
            });
            
            // Handle schedule tour form
            $('.schedule_tour_form').on('click', function(e) {
                var form = $(this).closest('form');
                var honeypotFields = form.find('.spamxpert-hp-field input');
                
                honeypotFields.each(function() {
                    if ($(this).is(':checkbox')) {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                });
            });
            
            // Handle Elementor contact forms
            $('.houzez-contact-form-js').on('click', function(e) {
                var form = $(this).closest('form');
                var honeypotFields = form.find('.spamxpert-hp-field input');
                
                honeypotFields.each(function() {
                    if ($(this).is(':checkbox')) {
                        $(this).prop('checked', false);
                    } else {
                        $(this).val('');
                    }
                });
            });
        });
        </script>
        <?php
    }
} 