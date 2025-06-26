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

        // Property Agent Contact Form (on property pages)
        add_action('houzez_property_agent_contact_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_property_agent_contact_validation', array($this, 'validate_property_agent_form'), 10, 2);
        
        // Schedule Tour Form
        add_action('houzez_schedule_tour_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_schedule_tour_validation', array($this, 'validate_schedule_tour'), 10, 2);
        
        // Inquiry Form (Elementor Widget)
        add_action('houzez_inquiry_form_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_ele_inquiry_form_validation', array($this, 'validate_inquiry_form'), 10, 2);

        // Contact Form (Elementor Widget - property pages)
        add_action('houzez_contact_form_fields', array($this, 'output_honeypot_fields'), 10);
        add_filter('houzez_ele_contact_form_validation', array($this, 'validate_contact_form'), 10, 2);
        
        // Login/Register Forms
        add_action('houzez_login_form_fields', array($this, 'output_honeypot_fields'), 10);
        add_action('houzez_before_login', array($this, 'validate_login_form'), 10);
        
        add_action('houzez_register_form_fields', array($this, 'output_honeypot_fields'), 10);
        add_action('houzez_before_register', array($this, 'validate_register_form'), 10);
        
        // Agent/Agency Profile Contact Forms (on agent/agency profile pages)
        add_action( 'houzez_realtor_contact_form_fields', array( $this, 'output_honeypot_fields' ), 10 );
        add_action( 'houzez_before_realtor_form_submission', array( $this, 'validate_realtor_contact_form' ), 10 );
        
        // Review form
        add_action( 'houzez_review_form_fields', array( $this, 'output_honeypot_fields' ) );
        add_filter( 'houzez_before_review_submission', array( $this, 'filter_review_submission' ), 10, 2 );
        
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
                'houzez_contact_form_fields' => 'houzez_contact_form',
                'houzez_login_form_fields' => 'houzez_login',
                'houzez_register_form_fields' => 'houzez_register',
                'houzez_realtor_contact_form_fields' => 'houzez_agent_contact',
                'houzez_review_form_fields' => 'houzez_review_form'
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
    public function validate_property_agent_form($errors, $data) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($data, 'houzez_property_agent_contact');
            
            if ($result !== true) {
                // Module already logged the spam attempt with proper details
                // No need to log again at integration level
                $errors[] = esc_html__('Your submission was blocked. Please try again.', 'spamxpert');
            }
        }
        
        return $errors;
    }

    /**
     * Validate realtor contact form
     */
    public function validate_realtor_contact_form() {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $realtor_type = isset($_POST['agent_type']) ? $_POST['agent_type'] : '';
            if($realtor_type == 'agency_info') {
                $form_type = 'houzez_agency_contact';   
            } else {
                $form_type = 'houzez_agent_contact';
            }
            $result = $validator->validate_submission($_POST, $form_type);
            
            if ($result !== true) {
                wp_send_json(array(
                    'success' => false,
                    'msg' => esc_html__('Your submission was blocked. Please try again.', 'spamxpert')
                ));
                wp_die();
            }
        }
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
     * Validate login form
     */
    public function validate_login_form() {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($_POST, 'houzez_login');
            
            if ($result !== true) {
                wp_send_json(array(
                    'success' => false,
                    'msg' => esc_html__('Your submission was blocked. Please try again.', 'spamxpert')
                ));
                wp_die();
            }
        }
    }

    /**
     * Validate register form
     */
    public function validate_register_form() {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ($validator) {
            $result = $validator->validate_submission($_POST, 'houzez_register');
            
            if ($result !== true) {
                wp_send_json(array(
                    'success' => false,
                    'msg' => esc_html__('Your submission was blocked. Please try again.', 'spamxpert')
                ));
                wp_die();
            }
        }
    }

    /**
     * Filter review submission
     *
     * @param bool $result Validation result
     * @param array $data Form data
     * @return bool
     */
    public function filter_review_submission($result, $data) {
        $validator = SpamXpert::get_instance()->get_module('validator');
        
        if ( ! $validator ) {
            return $result;
        }
        
        // Validate submission
        $validated = $validator->validate_submission( $data, 'houzez_review_form' );
        
        if ( $validated !== true ) {
            // For AJAX requests, return JSON error
            if ( wp_doing_ajax() ) {
                wp_send_json_error( array(
                    'message' => esc_html__( 'Your submission has been blocked. Please try again.', 'spamxpert' )
                ) );
                wp_die();
            }
            
            // For non-AJAX, return WP_Error
            return new WP_Error( 
                'spam_detected', 
                esc_html__( 'Your submission has been blocked. Please try again.', 'spamxpert' ) 
            );
        }
        
        return $result;
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
            
            // Handle review form
            $('#submit-review').on('click', function(e) {
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