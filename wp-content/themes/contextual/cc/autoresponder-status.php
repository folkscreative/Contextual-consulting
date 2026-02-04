<?php
/**
 * Mailster Autoresponder Status Monitor
 */

function mailster_check_autoresponder_status() {
    if (!function_exists('mailster')) {
        return;
    }
    
    $args = array(
        'post_type' => 'newsletter',
        'posts_per_page' => -1,
        'post_status' => 'autoresponder'
    );
    
    $autoresponders = get_posts($args);
    
    if (empty($autoresponders)) {
        return;
    }
    
    $stored_status = get_option('mailster_autoresponder_status_tracker', array());
    $deactivated = array();
    $current_status = array();
    
    foreach ($autoresponders as $autoresponder) {
        $id = $autoresponder->ID;
        
        $is_active = get_post_meta($id, '_mailster_active', true);
        $is_active = (bool) $is_active;
        
        $current_status[$id] = $is_active;
        
        $previous_status = isset($stored_status[$id]) ? $stored_status[$id] : 'not set';
        
        if (isset($stored_status[$id]) && $stored_status[$id] === true && $is_active === false) {
            $autoresponder_data = get_post_meta($id, '_mailster_autoresponder', true);
            
            if (is_string($autoresponder_data)) {
                $autoresponder_data = maybe_unserialize($autoresponder_data);
            }
            
            $deactivated[] = array(
                'id' => $id,
                'name' => $autoresponder->post_title,
                'time' => current_time('mysql'),
                'action' => isset($autoresponder_data['action']) ? $autoresponder_data['action'] : 'unknown'
            );
        }
    }
    
    update_option('mailster_autoresponder_status_tracker', $current_status);
    
    if (!empty($deactivated)) {
        $result = mailster_send_deactivation_alert($deactivated);
        return $deactivated;
    }
    
    return array();
}

function mailster_send_deactivation_alert($deactivated) {
    $alert_email = get_option('admin_email');
    
    $subject = '[Alert] Newsletter Autoresponder(s) Deactivated';
    
    $message = "The following autoresponder(s) have been deactivated:\n\n";
    
    foreach ($deactivated as $item) {
        $edit_link = admin_url('post.php?post=' . $item['id'] . '&action=edit');
        $message .= "- " . $item['name'] . "\n";
        $message .= "  Trigger: " . $item['action'] . "\n";
        $message .= "  Time: " . $item['time'] . "\n";
        $message .= "  Edit: " . $edit_link . "\n\n";
    }
    
    $message .= "Please check these autoresponders and reactivate if necessary.\n";
    $message .= "Site: " . get_site_url();
    
    $mail_result = wp_mail($alert_email, $subject, $message);
    
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'deactivated' => $deactivated
    );
    
    $log = get_option('mailster_deactivation_log', array());
    $log[] = $log_entry;
    
    if (count($log) > 50) {
        $log = array_slice($log, -50);
    }
    
    update_option('mailster_deactivation_log', $log);
    
    return $mail_result;
}

function mailster_schedule_status_check() {
    if (!wp_next_scheduled('mailster_autoresponder_status_check')) {
        wp_schedule_event(time(), 'hourly', 'mailster_autoresponder_status_check');
    }
}
add_action('wp', 'mailster_schedule_status_check');

add_action('mailster_autoresponder_status_check', 'mailster_check_autoresponder_status');

function mailster_clear_status_check_schedule() {
    wp_clear_scheduled_hook('mailster_autoresponder_status_check');
}
register_deactivation_hook(__FILE__, 'mailster_clear_status_check_schedule');

function mailster_add_status_monitor_page() {
    add_submenu_page(
        'edit.php?post_type=newsletter',
        'Autoresponder Status Log',
        'Status Monitor',
        'manage_options',
        'mailster-status-monitor',
        'mailster_status_monitor_page'
    );
}
add_action('admin_menu', 'mailster_add_status_monitor_page');

function mailster_status_monitor_page() {
    $check_result = null;
    
    if (isset($_POST['mailster_manual_check']) && check_admin_referer('mailster_manual_check')) {
        $check_result = mailster_check_autoresponder_status();
        
        if (is_array($check_result) && !empty($check_result)) {
            echo '<div class="notice notice-warning"><p><strong>Alert sent!</strong> ' . count($check_result) . ' autoresponder(s) were found to be deactivated since the last check. An email has been sent to ' . esc_html(get_option('admin_email')) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Status check completed! No autoresponders have been deactivated since the last check.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Newsletter Autoresponder Status Monitor</h1>
        
        <h2>Current Autoresponder Status</h2>
        <?php
        $args = array(
            'post_type' => 'newsletter',
            'posts_per_page' => -1,
            'post_status' => 'autoresponder'
        );
        
        $autoresponders = get_posts($args);
        
        if (!empty($autoresponders)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Name</th><th>Status</th><th>Trigger</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($autoresponders as $ar) {
                $is_active = get_post_meta($ar->ID, '_mailster_active', true);
                $ar_data = get_post_meta($ar->ID, '_mailster_autoresponder', true);
                
                if (is_string($ar_data)) {
                    $ar_data = maybe_unserialize($ar_data);
                }
                
                $action = isset($ar_data['action']) ? $ar_data['action'] : 'unknown';
                
                $action_display = str_replace('mailster_', '', $action);
                $action_display = str_replace('_', ' ', $action_display);
                $action_display = ucwords($action_display);
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($ar->post_title) . '</strong></td>';
                echo '<td><span style="color: ' . ($is_active ? 'green' : 'red') . '; font-size: 16px;">●</span> ' . ($is_active ? '<strong>Active</strong>' : '<strong style="color:red;">Inactive</strong>') . '</td>';
                echo '<td>' . esc_html($action_display) . '</td>';
                echo '<td><a href="' . admin_url('post.php?post=' . $ar->ID . '&action=edit') . '">Edit</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '<p><strong>Total autoresponders: ' . count($autoresponders) . '</strong></p>';
        } else {
            echo '<p>No autoresponders found.</p>';
        }
        ?>
        
        <h2>Deactivation History</h2>
        <?php
        $log = get_option('mailster_deactivation_log', array());
        
        if (empty($log)) {
            echo '<p>No deactivation events recorded yet.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Date/Time</th><th>Autoresponder</th><th>Trigger</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            
            $log = array_reverse($log);
            
            foreach ($log as $entry) {
                foreach ($entry['deactivated'] as $item) {
                    $action_display = str_replace('mailster_', '', $item['action']);
                    $action_display = str_replace('_', ' ', $action_display);
                    $action_display = ucwords($action_display);
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($entry['timestamp']) . '</td>';
                    echo '<td>' . esc_html($item['name']) . '</td>';
                    echo '<td>' . esc_html($action_display) . '</td>';
                    echo '<td><a href="' . admin_url('post.php?post=' . $item['id'] . '&action=edit') . '">View</a></td>';
                    echo '</tr>';
                }
            }
            
            echo '</tbody></table>';
        }
                
        echo '<p><form method="post">';
        echo '<input type="hidden" name="mailster_manual_check" value="1">';
        wp_nonce_field('mailster_manual_check');
        echo '<button type="submit" class="button button-primary">Run Check Now</button>';
        echo ' <small>This will check all autoresponders and send alerts if any have been deactivated since the last check.</small>';
        echo '</form></p>';

        do_action('mailster_status_monitor_page_after_history');
        ?>

        <h2>Active Status Change Tracking</h2>
        <?php
        $tracking_log = get_option('mailster_active_status_tracking', array());

        if (empty($tracking_log)) {
            echo '<p>No status changes tracked yet. (Tracking started when this code was added)</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Date/Time</th><th>Autoresponder</th><th>Change</th><th>Triggered By</th><th>User</th></tr></thead>';
            echo '<tbody>';
            
            // Show most recent first
            $tracking_log = array_reverse($tracking_log);
            
            foreach ($tracking_log as $entry) {
                $change = ($entry['previous_value'] ? 'Active' : 'Inactive') . ' → ' . ($entry['new_value'] ? 'Active' : 'Inactive');
                $change_color = !$entry['new_value'] ? 'color: red; font-weight: bold;' : 'color: green;';
                
                echo '<tr>';
                echo '<td>' . esc_html($entry['timestamp']) . '</td>';
                echo '<td><strong>' . esc_html($entry['campaign_name']) . '</strong></td>';
                echo '<td style="' . $change_color . '">' . esc_html($change) . '</td>';
                echo '<td><small>' . esc_html(implode(' → ', array_slice($entry['call_stack'], 0, 3))) . '</small></td>';
                echo '<td>' . esc_html($entry['user_login']) . ' (' . $entry['user_id'] . ')</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '<p><small>This shows all changes to autoresponder active/inactive status and what triggered them.</small></p>';
        }
        ?>
        
    </div>
    <?php
}

add_action('mailster_campaign_active', 'mailster_log_campaign_status_change', 10, 2);
function mailster_log_campaign_status_change($campaign_id, $is_active) {
    $campaign = get_post($campaign_id);
    
    if ($campaign && $campaign->post_status === 'autoresponder') {
        $status = $is_active ? 'activated' : 'deactivated';
        
        $stats = array(
            'bounces' => mailster('campaigns')->get_sent($campaign_id, 'bounced'),
            'unsubscribes' => mailster('campaigns')->get_sent($campaign_id, 'unsubscribed'),
            'errors' => mailster('campaigns')->get_sent($campaign_id, 'error'),
            'total_sent' => mailster('campaigns')->get_sent($campaign_id),
        );
        
        if ($stats['total_sent'] > 0) {
            $stats['bounce_rate'] = round(($stats['bounces'] / $stats['total_sent']) * 100, 2);
            $stats['error_rate'] = round(($stats['errors'] / $stats['total_sent']) * 100, 2);
        }
        
        $log_message = sprintf(
            'Autoresponder "%s" (ID: %d) %s. Stats: Bounces=%d (%.2f%%), Errors=%d (%.2f%%), Unsubscribes=%d, Total Sent=%d',
            $campaign->post_title,
            $campaign_id,
            $status,
            $stats['bounces'],
            isset($stats['bounce_rate']) ? $stats['bounce_rate'] : 0,
            $stats['errors'],
            isset($stats['error_rate']) ? $stats['error_rate'] : 0,
            $stats['unsubscribes'],
            $stats['total_sent']
        );
        
        error_log($log_message);
        
        $custom_log = get_option('mailster_deactivation_detailed_log', array());
        $custom_log[] = array(
            'timestamp' => current_time('mysql'),
            'campaign_id' => $campaign_id,
            'campaign_name' => $campaign->post_title,
            'status' => $status,
            'stats' => $stats
        );
        
        if (count($custom_log) > 100) {
            $custom_log = array_slice($custom_log, -100);
        }
        
        update_option('mailster_deactivation_detailed_log', $custom_log);
    }
}

add_action('mailster_status_monitor_page_after_history', 'mailster_show_detailed_deactivation_log');
function mailster_show_detailed_deactivation_log() {
    $detailed_log = get_option('mailster_deactivation_detailed_log', array());
    
    if (empty($detailed_log)) {
        return;
    }
    
    echo '<h2>Detailed Deactivation Analysis</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Date/Time</th><th>Autoresponder</th><th>Bounce Rate</th><th>Error Rate</th><th>Details</th></tr></thead>';
    echo '<tbody>';
    
    $detailed_log = array_reverse($detailed_log);
    
    foreach ($detailed_log as $entry) {
        if ($entry['status'] === 'deactivated') {
            $stats = $entry['stats'];
            $bounce_rate = isset($stats['bounce_rate']) ? $stats['bounce_rate'] : 0;
            $error_rate = isset($stats['error_rate']) ? $stats['error_rate'] : 0;
            
            $bounce_color = $bounce_rate > 5 ? 'red' : ($bounce_rate > 2 ? 'orange' : 'green');
            $error_color = $error_rate > 1 ? 'red' : 'green';
            
            echo '<tr>';
            echo '<td>' . esc_html($entry['timestamp']) . '</td>';
            echo '<td><strong>' . esc_html($entry['campaign_name']) . '</strong></td>';
            echo '<td style="color:' . $bounce_color . '"><strong>' . number_format($bounce_rate, 2) . '%</strong> (' . $stats['bounces'] . '/' . $stats['total_sent'] . ')</td>';
            echo '<td style="color:' . $error_color . '"><strong>' . number_format($error_rate, 2) . '%</strong> (' . $stats['errors'] . '/' . $stats['total_sent'] . ')</td>';
            echo '<td>Unsubscribes: ' . $stats['unsubscribes'] . '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody></table>';
    echo '<p><small><strong>Note:</strong> Bounce rate >5% or high error rates typically trigger automatic deactivation.</small></p>';
}

add_action('updated_post_meta', 'mailster_track_active_status_changes', 10, 4);
function mailster_track_active_status_changes($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_key !== '_mailster_active') {
        return;
    }
    
    $post = get_post($object_id);
    if (!$post || $post->post_status !== 'autoresponder') {
        return;
    }
    
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    
    $call_stack = array();
    foreach ($backtrace as $trace) {
        if (isset($trace['function'])) {
            $function = $trace['function'];
            if (isset($trace['class'])) {
                $function = $trace['class'] . '::' . $function;
            }
            $call_stack[] = $function;
        }
    }
    
    $previous_value = get_post_meta($object_id, '_mailster_active', true);
    
    $log_entry = sprintf(
        '[%s] Autoresponder "%s" (ID: %d) _mailster_active changed from "%s" to "%s". Triggered by: %s. User: %s (ID: %s)',
        current_time('mysql'),
        $post->post_title,
        $object_id,
        $previous_value,
        $meta_value,
        implode(' -> ', array_slice($call_stack, 0, 5)),
        wp_get_current_user()->user_login ?: 'system',
        get_current_user_id() ?: 'N/A'
    );
    
    error_log($log_entry);
    
    $tracking_log = get_option('mailster_active_status_tracking', array());
    $tracking_log[] = array(
        'timestamp' => current_time('mysql'),
        'campaign_id' => $object_id,
        'campaign_name' => $post->post_title,
        'previous_value' => $previous_value,
        'new_value' => $meta_value,
        'call_stack' => array_slice($call_stack, 0, 5),
        'user_id' => get_current_user_id(),
        'user_login' => wp_get_current_user()->user_login ?: 'system'
    );
    
    if (count($tracking_log) > 50) {
        $tracking_log = array_slice($tracking_log, -50);
    }
    
    update_option('mailster_active_status_tracking', $tracking_log);
}