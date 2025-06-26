<?php
/**
 * Test WordPress Login and Registration Form Protection
 * 
 * Usage: wp eval-file wp-content/plugins/spamxpert/test-login-register.php
 */

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../wp-load.php');
}

echo "\n🔐 Testing SpamXpert Login & Registration Form Protection\n";
echo "========================================================\n\n";

// Check if SpamXpert is active
if (!class_exists('SpamXpert')) {
    echo "❌ SpamXpert plugin is not active!\n";
    exit(1);
}

$spamxpert = SpamXpert::get_instance();
echo "✅ SpamXpert is active\n\n";

// Test Login Form Integration
echo "LOGIN FORM INTEGRATION\n";
echo "---------------------\n";

// Check if login integration is loaded
if (!class_exists('SpamXpert_Integration_WP_Login')) {
    echo "❌ Login integration class not loaded!\n";
} else {
    echo "✅ Login integration class loaded\n";
    
    // Check if protection is enabled
    if (get_option('spamxpert_protect_wp_login', '1') !== '1') {
        echo "⚠️  Login protection is DISABLED in settings\n";
    } else {
        echo "✅ Login protection is ENABLED\n";
    }
    
    // Check hooks
    global $wp_filter;
    $login_hooks = array(
        'login_form' => 'output_honeypot_fields',
        'authenticate' => 'validate_login',
        'login_errors' => 'custom_login_errors'
    );
    
    foreach ($login_hooks as $hook_name => $method) {
        $found = false;
        if (isset($wp_filter[$hook_name])) {
            foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        get_class($callback['function'][0]) === 'SpamXpert_Integration_WP_Login' &&
                        $callback['function'][1] === $method) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }
        echo $found ? "✅ Hook '{$hook_name}' is registered\n" : "❌ Hook '{$hook_name}' is NOT registered\n";
    }
}

echo "\nREGISTRATION FORM INTEGRATION\n";
echo "-----------------------------\n";

// Check if registration integration is loaded
if (!class_exists('SpamXpert_Integration_WP_Registration')) {
    echo "❌ Registration integration class not loaded!\n";
} else {
    echo "✅ Registration integration class loaded\n";
    
    // Check if protection is enabled
    if (get_option('spamxpert_protect_wp_registration', '1') !== '1') {
        echo "⚠️  Registration protection is DISABLED in settings\n";
    } else {
        echo "✅ Registration protection is ENABLED\n";
    }
    
    // Check if registration is open
    if (!get_option('users_can_register') && !is_multisite()) {
        echo "⚠️  User registration is DISABLED in WordPress settings\n";
    } else {
        echo "✅ User registration is enabled\n";
    }
    
    // Check hooks
    $reg_hooks = array(
        'register_form' => 'output_honeypot_fields',
        'registration_errors' => 'validate_registration'
    );
    
    if (is_multisite()) {
        $reg_hooks['signup_extra_fields'] = 'output_honeypot_fields';
        $reg_hooks['wpmu_validate_user_signup'] = 'validate_multisite_signup';
        $reg_hooks['wpmu_validate_blog_signup'] = 'validate_multisite_blog_signup';
    }
    
    foreach ($reg_hooks as $hook_name => $method) {
        $found = false;
        if (isset($wp_filter[$hook_name])) {
            foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        get_class($callback['function'][0]) === 'SpamXpert_Integration_WP_Registration' &&
                        $callback['function'][1] === $method) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }
        echo $found ? "✅ Hook '{$hook_name}' is registered\n" : "❌ Hook '{$hook_name}' is NOT registered\n";
    }
}

// Test honeypot module
echo "\nHONEYPOT MODULE TEST\n";
echo "-------------------\n";

$honeypot = $spamxpert->get_module('honeypot');
if ($honeypot) {
    echo "✅ Honeypot module loaded\n";
    
    // Generate test fields
    $test_fields = $honeypot->generate_fields('test_form');
    echo "✅ Generated " . count($test_fields) . " honeypot field(s)\n";
    
    // Show first field as example
    if (!empty($test_fields)) {
        $field = $test_fields[0];
        echo "   Example: name='{$field['name']}', type='{$field['type']}', label='{$field['label']}'\n";
    }
} else {
    echo "❌ Honeypot module not found!\n";
}

// Test time trap module
echo "\nTIME TRAP MODULE TEST\n";
echo "---------------------\n";

$time_trap = $spamxpert->get_module('time_trap');
if ($time_trap) {
    echo "✅ Time trap module loaded\n";
    $threshold = get_option('spamxpert_time_threshold', 3);
    echo "✅ Time threshold: {$threshold} seconds\n";
} else {
    echo "❌ Time trap module not found!\n";
}

echo "\nTEST INSTRUCTIONS\n";
echo "=================\n";
echo "\n📝 To test LOGIN FORM protection:\n";
echo "1. Go to: " . wp_login_url() . "\n";
echo "2. View page source and search for 'spamxpert-hp-field'\n";
echo "3. You should see hidden honeypot fields\n";
echo "4. Try submitting the form very quickly (< {$threshold} seconds) - it should be blocked\n";
echo "5. Use browser console to fill honeypot fields and submit - it should be blocked\n";

echo "\n📝 To test REGISTRATION FORM protection:\n";
if (get_option('users_can_register')) {
    echo "1. Go to: " . wp_registration_url() . "\n";
    echo "2. View page source and search for 'spamxpert-hp-field'\n";
    echo "3. You should see hidden honeypot fields\n";
    echo "4. Try submitting the form very quickly (< {$threshold} seconds) - it should be blocked\n";
    echo "5. Use browser console to fill honeypot fields and submit - it should be blocked\n";
} else {
    echo "⚠️  Registration is disabled. Enable it in Settings > General > Membership\n";
}

echo "\n💡 Console command to test honeypot fields:\n";
echo "document.querySelectorAll('.spamxpert-hp-field').forEach(d => { d.style.cssText = 'position:relative!important;border:2px solid red;padding:10px;background:#ffe0e0;margin:10px 0'; }); document.querySelectorAll('.spamxpert-hp-field input').forEach(f => { f.value = 'test'; f.style.border = '2px solid red'; });\n";

echo "\n✅ Test completed!\n\n"; 