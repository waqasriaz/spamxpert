<?php
/**
 * Admin Dashboard Template
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get stats
$total_blocked = isset($stats['total_blocked']) ? $stats['total_blocked'] : 0;
$blocked_today = isset($stats['blocked_today']) ? $stats['blocked_today'] : 0;
$blocked_week = isset($stats['blocked_week']) ? $stats['blocked_week'] : 0;
$top_ips = isset($stats['top_ips']) ? $stats['top_ips'] : array();
$by_form = isset($stats['by_form']) ? $stats['by_form'] : array();
$by_reason = isset($stats['by_reason']) ? $stats['by_reason'] : array();
?>

<div class="wrap" id="spamxpert-dashboard">
    <h1><?php echo esc_html__('SpamXpert Dashboard', 'spamxpert'); ?></h1>
    
    <!-- Status Cards -->
    <div class="spamxpert-dashboard-cards">
        <div class="card">
            <div class="card-icon" style="color: var(--spamxpert-primary);">
                <span class="dashicons dashicons-shield-alt"></span>
            </div>
            <h3><?php _e('Total Blocked', 'spamxpert'); ?></h3>
            <div class="stat-number"><?php echo number_format($total_blocked); ?></div>
            <p><?php _e('Spam attempts blocked all time', 'spamxpert'); ?></p>
        </div>
        
        <div class="card">
            <div class="card-icon" style="color: var(--spamxpert-warning);">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <h3><?php _e('Blocked Today', 'spamxpert'); ?></h3>
            <div class="stat-number"><?php echo number_format($blocked_today); ?></div>
            <p><?php _e('Spam attempts blocked today', 'spamxpert'); ?></p>
        </div>
        
        <div class="card">
            <div class="card-icon" style="color: var(--spamxpert-info);">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <h3><?php _e('Blocked This Week', 'spamxpert'); ?></h3>
            <div class="stat-number"><?php echo number_format($blocked_week); ?></div>
            <p><?php _e('Spam attempts blocked in last 7 days', 'spamxpert'); ?></p>
        </div>
        
        <div class="card">
            <div class="card-icon" style="color: <?php echo spamxpert_is_enabled() ? 'var(--spamxpert-success)' : 'var(--spamxpert-danger)'; ?>;">
                <span class="dashicons dashicons-<?php echo spamxpert_is_enabled() ? 'yes-alt' : 'dismiss'; ?>"></span>
            </div>
            <h3><?php _e('Protection Status', 'spamxpert'); ?></h3>
            <div class="stat-status <?php echo spamxpert_is_enabled() ? 'active' : 'inactive'; ?>">
                <?php echo spamxpert_is_enabled() ? __('Active', 'spamxpert') : __('Inactive', 'spamxpert'); ?>
            </div>
            <p><?php _e('Current protection status', 'spamxpert'); ?></p>
        </div>
    </div>
    
    <div class="spamxpert-dashboard-row">
        <!-- Top Spam Sources -->
        <div class="spamxpert-dashboard-panel">
            <h2><?php _e('Top Spam Sources', 'spamxpert'); ?></h2>
            <?php if (!empty($top_ips)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('IP Address', 'spamxpert'); ?></th>
                            <th><?php _e('Attempts', 'spamxpert'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_ips as $ip): ?>
                            <tr>
                                <td><?php echo esc_html($ip->ip_address); ?></td>
                                <td><?php echo number_format($ip->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="spamxpert-empty-state">
                    <p><?php _e('No spam attempts recorded yet.', 'spamxpert'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Spam by Form Type -->
        <div class="spamxpert-dashboard-panel">
            <h2><?php _e('Spam by Form Type', 'spamxpert'); ?></h2>
            <?php if (!empty($by_form)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Form Type', 'spamxpert'); ?></th>
                            <th><?php _e('Blocked', 'spamxpert'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($by_form as $form): ?>
                            <tr>
                                <td><?php echo esc_html(str_replace('_', ' ', ucfirst($form->form_type))); ?></td>
                                <td><?php echo number_format($form->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="spamxpert-empty-state">
                    <p><?php _e('No form submissions blocked yet.', 'spamxpert'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Spam by Reason -->
        <div class="spamxpert-dashboard-panel">
            <h2><?php _e('Spam Detection Methods', 'spamxpert'); ?></h2>
            <?php if (!empty($by_reason)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Detection Method', 'spamxpert'); ?></th>
                            <th><?php _e('Count', 'spamxpert'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($by_reason as $reason): ?>
                            <tr>
                                <td><?php echo esc_html(str_replace('_', ' ', ucfirst($reason->spam_reason))); ?></td>
                                <td><?php echo number_format($reason->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="spamxpert-empty-state">
                    <p><?php _e('No detection data available yet.', 'spamxpert'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Protection Summary -->
    <?php if ($total_blocked > 0): ?>
    <div class="spamxpert-dashboard-panel" style="margin-top: 30px;">
        <h2><?php _e('Protection Effectiveness', 'spamxpert'); ?></h2>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="flex: 1;">
                <div style="background: var(--spamxpert-light); height: 30px; border-radius: 15px; overflow: hidden;">
                    <div style="background: var(--spamxpert-success); height: 100%; width: <?php echo min(100, ($blocked_week / max(1, $total_blocked)) * 100); ?>%; transition: width 0.5s ease;"></div>
                </div>
                <p style="margin-top: 10px; color: var(--spamxpert-text-light);">
                    <?php printf(__('%d%% of total spam was blocked this week', 'spamxpert'), round(($blocked_week / max(1, $total_blocked)) * 100)); ?>
                </p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: var(--spamxpert-success);">
                    <?php echo round(($blocked_week / max(1, $total_blocked)) * 100); ?>%
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="spamxpert-quick-actions">
        <h2><?php _e('Quick Actions', 'spamxpert'); ?></h2>
        <a href="<?php echo admin_url('admin.php?page=spamxpert-settings'); ?>" class="button button-primary">
            <span class="dashicons dashicons-admin-settings" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Configure Settings', 'spamxpert'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=spamxpert-logs'); ?>" class="button">
            <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('View Spam Logs', 'spamxpert'); ?>
        </a>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=spamxpert-logs&action=export'), 'spamxpert_export_logs'); ?>" class="button">
            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Export Logs', 'spamxpert'); ?>
        </a>
    </div>
</div> 