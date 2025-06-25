<?php
/**
 * Admin Logs Template
 *
 * @package SpamXpert
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$logs = isset($result['logs']) ? $result['logs'] : array();
$total = isset($result['total']) ? $result['total'] : 0;
$pages = isset($result['pages']) ? $result['pages'] : 0;
$current_page = isset($filters['page']) ? $filters['page'] : 1;
?>

<div class="wrap">
    <h1>
        <?php echo esc_html__('Spam Logs', 'spamxpert'); ?>
        <?php if ($total > 0): ?>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=spamxpert-logs&action=clear_all_logs'), 'spamxpert_clear_logs'); ?>" 
               class="page-title-action" 
               onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all logs?', 'spamxpert')); ?>');">
                <?php _e('Clear All Logs', 'spamxpert'); ?>
            </a>
        <?php endif; ?>
    </h1>
    
    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="spamxpert-logs" />
            
            <div class="alignleft actions">
                <select name="form_type">
                    <option value=""><?php _e('All Form Types', 'spamxpert'); ?></option>
                    <option value="wp_login" <?php selected($filters['form_type'], 'wp_login'); ?>><?php _e('Login', 'spamxpert'); ?></option>
                    <option value="wp_registration" <?php selected($filters['form_type'], 'wp_registration'); ?>><?php _e('Registration', 'spamxpert'); ?></option>
                    <option value="wp_comments" <?php selected($filters['form_type'], 'wp_comments'); ?>><?php _e('Comments', 'spamxpert'); ?></option>
                </select>
                
                <input type="text" name="ip_address" value="<?php echo esc_attr($filters['ip_address']); ?>" placeholder="<?php esc_attr_e('IP Address', 'spamxpert'); ?>" />
                
                <input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>" placeholder="<?php esc_attr_e('From Date', 'spamxpert'); ?>" />
                <input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>" placeholder="<?php esc_attr_e('To Date', 'spamxpert'); ?>" />
                
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'spamxpert'); ?>" />
                
                <?php if (!empty($filters['form_type']) || !empty($filters['ip_address']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                    <a href="<?php echo admin_url('admin.php?page=spamxpert-logs'); ?>" class="button"><?php _e('Clear Filters', 'spamxpert'); ?></a>
                <?php endif; ?>
            </div>
            
            <?php if ($total > 0): ?>
                <div class="alignright">
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=spamxpert_export_logs'), 'spamxpert_export_logs', 'nonce'); ?>" class="button">
                        <?php _e('Export CSV', 'spamxpert'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if (!empty($logs)): ?>
        <form method="post" action="">
            <?php wp_nonce_field('spamxpert_delete_logs'); ?>
            <input type="hidden" name="action" value="delete_logs" />
            
            <table id="spamxpert-logs-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" />
                        </td>
                        <th><?php _e('Date/Time', 'spamxpert'); ?></th>
                        <th><?php _e('Form Type', 'spamxpert'); ?></th>
                        <th><?php _e('IP Address', 'spamxpert'); ?></th>
                        <th><?php _e('Reason', 'spamxpert'); ?></th>
                        <th><?php _e('Score', 'spamxpert'); ?></th>
                        <th><?php _e('User Agent', 'spamxpert'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>" />
                            </th>
                            <td><?php echo esc_html($log->blocked_at); ?></td>
                            <td><?php echo esc_html(str_replace('_', ' ', ucfirst($log->form_type))); ?></td>
                            <td><?php echo esc_html($log->ip_address); ?></td>
                            <td><?php echo esc_html(str_replace('_', ' ', ucfirst($log->spam_reason))); ?></td>
                            <td><?php echo esc_html($log->spam_score); ?></td>
                            <td>
                                <span title="<?php echo esc_attr($log->user_agent); ?>">
                                    <?php echo esc_html(substr($log->user_agent, 0, 50)) . (strlen($log->user_agent) > 50 ? '...' : ''); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <input type="submit" class="button" value="<?php esc_attr_e('Delete Selected', 'spamxpert'); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete selected logs?', 'spamxpert')); ?>');" />
                </div>
                
                <?php if ($pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total, 'spamxpert'), number_format($total)); ?></span>
                        
                        <?php
                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $current_page,
                            'total' => $pages,
                            'prev_text' => __('&laquo;', 'spamxpert'),
                            'next_text' => __('&raquo;', 'spamxpert'),
                        );
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    <?php else: ?>
        <div class="spamxpert-empty-state">
            <p><?php _e('No spam attempts have been logged yet.', 'spamxpert'); ?></p>
        </div>
    <?php endif; ?>
</div> 