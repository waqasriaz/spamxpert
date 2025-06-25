<?php
/**
 * Admin Settings Template
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('SpamXpert Settings', 'spamxpert'); ?></h1>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=spamxpert-settings')); ?>" class="spamxpert-settings-form">
        <?php wp_nonce_field('spamxpert_settings'); ?>
        
        <div class="spamxpert-settings-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'spamxpert'); ?></a>
                <a href="#forms" class="nav-tab"><?php _e('Forms', 'spamxpert'); ?></a>
                <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'spamxpert'); ?></a>
            </h2>
            
            <!-- General Settings -->
            <div id="general" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable SpamXpert', 'spamxpert'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="spamxpert_enabled" value="1" <?php checked(get_option('spamxpert_enabled', '1'), '1'); ?> />
                                <?php _e('Enable spam protection', 'spamxpert'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Honeypot Fields', 'spamxpert'); ?></th>
                        <td>
                            <input type="number" name="spamxpert_honeypot_count" value="<?php echo esc_attr(get_option('spamxpert_honeypot_count', '2')); ?>" min="1" max="5" />
                            <p class="description"><?php _e('Number of honeypot fields to add to forms (1-5)', 'spamxpert'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Time Threshold', 'spamxpert'); ?></th>
                        <td>
                            <input type="number" name="spamxpert_time_threshold" value="<?php echo esc_attr(get_option('spamxpert_time_threshold', '3')); ?>" min="1" max="60" />
                            <span><?php _e('seconds', 'spamxpert'); ?></span>
                            <p class="description"><?php _e('Minimum time before form can be submitted (1-60 seconds)', 'spamxpert'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Logging', 'spamxpert'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="spamxpert_log_spam" value="1" <?php checked(get_option('spamxpert_log_spam', '1'), '1'); ?> />
                                <?php _e('Log blocked spam attempts', 'spamxpert'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Log Retention', 'spamxpert'); ?></th>
                        <td>
                            <input type="number" name="spamxpert_log_retention_days" value="<?php echo esc_attr(get_option('spamxpert_log_retention_days', '30')); ?>" min="0" max="365" />
                            <span><?php _e('days', 'spamxpert'); ?></span>
                            <p class="description"><?php _e('Automatically delete logs older than this many days (0 = keep forever)', 'spamxpert'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Form Settings -->
            <div id="forms" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('WordPress Core Forms', 'spamxpert'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_wp_login" value="1" <?php checked(get_option('spamxpert_protect_wp_login', '1'), '1'); ?> />
                                    <?php _e('Login Form', 'spamxpert'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_wp_registration" value="1" <?php checked(get_option('spamxpert_protect_wp_registration', '1'), '1'); ?> />
                                    <?php _e('Registration Form', 'spamxpert'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_wp_comments" value="1" <?php checked(get_option('spamxpert_protect_wp_comments', '1'), '1'); ?> />
                                    <?php _e('Comment Forms', 'spamxpert'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Third-Party Forms', 'spamxpert'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_cf7" value="1" <?php checked(get_option('spamxpert_protect_cf7', '1'), '1'); ?> />
                                    <?php _e('Contact Form 7', 'spamxpert'); ?>
                                    <?php if (!class_exists('WPCF7')) : ?>
                                        <span class="description">(<?php _e('Not installed', 'spamxpert'); ?>)</span>
                                    <?php endif; ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_gravity_forms" value="1" <?php checked(get_option('spamxpert_protect_gravity_forms', '1'), '1'); ?> />
                                    <?php _e('Gravity Forms', 'spamxpert'); ?>
                                    <?php if (!class_exists('GFForms')) : ?>
                                        <span class="description">(<?php _e('Not installed', 'spamxpert'); ?>)</span>
                                    <?php endif; ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_wpforms" value="1" <?php checked(get_option('spamxpert_protect_wpforms', '1'), '1'); ?> />
                                    <?php _e('WPForms', 'spamxpert'); ?>
                                    <?php if (!function_exists('wpforms')) : ?>
                                        <span class="description">(<?php _e('Not installed', 'spamxpert'); ?>)</span>
                                    <?php endif; ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_ninja_forms" value="1" <?php checked(get_option('spamxpert_protect_ninja_forms', '1'), '1'); ?> />
                                    <?php _e('Ninja Forms', 'spamxpert'); ?>
                                    <?php if (!class_exists('Ninja_Forms')) : ?>
                                        <span class="description">(<?php _e('Not installed', 'spamxpert'); ?>)</span>
                                    <?php endif; ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_elementor_forms" value="1" <?php checked(get_option('spamxpert_protect_elementor_forms', '1'), '1'); ?> />
                                    <?php _e('Elementor Forms', 'spamxpert'); ?>
                                    <?php if (!did_action('elementor/loaded')) : ?>
                                        <span class="description">(<?php _e('Not installed', 'spamxpert'); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Houzez Theme Forms', 'spamxpert'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="spamxpert_protect_houzez" value="1" <?php checked(get_option('spamxpert_protect_houzez', '1'), '1'); ?> />
                                    <?php _e('Enable protection for all Houzez forms', 'spamxpert'); ?>
                                    <?php 
                                    $theme = wp_get_theme();
                                    if ($theme->get('Name') !== 'Houzez' && $theme->get('Template') !== 'houzez') : 
                                    ?>
                                        <span class="description">(<?php _e('Houzez theme not active', 'spamxpert'); ?>)</span>
                                    <?php endif; ?>
                                </label>
                                <p class="description"><?php _e('Protects property submission, contact agent, inquiry, and review forms', 'spamxpert'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Advanced Settings -->
            <div id="advanced" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'spamxpert'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="spamxpert_debug_mode" value="1" <?php checked(get_option('spamxpert_debug_mode', '0'), '1'); ?> />
                                <?php _e('Enable debug mode (shows honeypot fields and logs to console)', 'spamxpert'); ?>
                            </label>
                            <p class="description"><?php _e('Warning: Only enable for testing. This will make honeypot fields visible!', 'spamxpert'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Data Removal', 'spamxpert'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="spamxpert_remove_data_on_uninstall" value="1" <?php checked(get_option('spamxpert_remove_data_on_uninstall', '0'), '1'); ?> />
                                <?php _e('Remove all data when plugin is uninstalled', 'spamxpert'); ?>
                            </label>
                            <p class="description"><?php _e('If checked, all SpamXpert data including logs will be permanently deleted when the plugin is uninstalled.', 'spamxpert'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="spamxpert_save_settings" class="button-primary" value="<?php esc_attr_e('Save Settings', 'spamxpert'); ?>" />
        </p>
    </form>
</div> 