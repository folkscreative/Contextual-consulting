<?php
/**
 * VIDEO TRACKING MONITORING SYSTEM
 * 
 * Add this to your functions.php or as a separate plugin
 * This tracks success/failure rates so you can quantify the improvement
 */

// Create/update monitoring table on init with version control
add_action('init', 'cc_video_tracking_monitor_table_update');
function cc_video_tracking_monitor_table_update() {
    global $wpdb;
    // v1
    $cc_video_tracking_monitor_db_ver = 1;
    $installed_table_ver = get_option('cc_video_tracking_monitor_db_ver');
    
    if ($installed_table_ver != $cc_video_tracking_monitor_db_ver) {
        $table_name = $wpdb->prefix . 'video_tracking_monitor';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date DATE NOT NULL,
            hour TINYINT NOT NULL,
            total_requests INT DEFAULT 0,
            successful_requests INT DEFAULT 0,
            failed_requests INT DEFAULT 0,
            activities TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_hour (date, hour),
            KEY date_idx (date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $response = dbDelta($sql);
        update_option('cc_video_tracking_monitor_db_ver', $cc_video_tracking_monitor_db_ver);
    }
}

function cc_video_tracking_log_success($activity, $request_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'video_tracking_monitor';
    
    $date = current_time('Y-m-d');
    $hour = intval(current_time('H'));
    
    // Get current record or initialize
    $current = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE date = %s AND hour = %d",
        $date,
        $hour
    ), ARRAY_A);
    
    if ($current) {
        // Update existing record
        $activities = json_decode($current['activities'], true) ?: array();
        $activities[$activity] = ($activities[$activity] ?? 0) + 1;
        
        $wpdb->update(
            $table_name,
            array(
                'total_requests' => $current['total_requests'] + 1,
                'successful_requests' => $current['successful_requests'] + 1,
                'activities' => json_encode($activities)
            ),
            array('id' => $current['id'])
        );
    } else {
        // Insert new record
        $activities = array($activity => 1);
        
        $wpdb->insert(
            $table_name,
            array(
                'date' => $date,
                'hour' => $hour,
                'total_requests' => 1,
                'successful_requests' => 1,
                'failed_requests' => 0,
                'activities' => json_encode($activities)
            )
        );
    }
}

function cc_video_tracking_log_failure($error_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'video_tracking_monitor';
    
    $date = current_time('Y-m-d');
    $hour = intval(current_time('H'));
    
    $current = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE date = %s AND hour = %d",
        $date,
        $hour
    ), ARRAY_A);
    
    if ($current) {
        $wpdb->update(
            $table_name,
            array(
                'total_requests' => $current['total_requests'] + 1,
                'failed_requests' => $current['failed_requests'] + 1
            ),
            array('id' => $current['id'])
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'date' => $date,
                'hour' => $hour,
                'total_requests' => 1,
                'successful_requests' => 0,
                'failed_requests' => 1,
                'activities' => json_encode(array())
            )
        );
    }
    
    error_log("Video tracking failure: $error_type");
}

/**
 * ADMIN DASHBOARD WIDGET
 * Shows real-time success rate
 */
add_action('wp_dashboard_setup', 'cc_video_tracking_add_dashboard_widget');
function cc_video_tracking_add_dashboard_widget() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'cc_video_tracking_stats',
            'Video Tracking Statistics',
            'cc_video_tracking_dashboard_display'
        );
    }
}

function cc_video_tracking_dashboard_display() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'video_tracking_monitor';
    
    // Today's stats
    $today = current_time('Y-m-d');
    $today_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(total_requests) as total,
            SUM(successful_requests) as success,
            SUM(failed_requests) as failed
        FROM $table_name 
        WHERE date = %s",
        $today
    ), ARRAY_A);
    
    // Last 7 days
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
    $week_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(total_requests) as total,
            SUM(successful_requests) as success,
            SUM(failed_requests) as failed
        FROM $table_name 
        WHERE date >= %s",
        $seven_days_ago
    ), ARRAY_A);
    
    // Last 30 days
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $month_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(total_requests) as total,
            SUM(successful_requests) as success,
            SUM(failed_requests) as failed
        FROM $table_name 
        WHERE date >= %s",
        $thirty_days_ago
    ), ARRAY_A);
    
    // Calculate success rates
    $today_rate = $today_stats['total'] > 0 
        ? round(($today_stats['success'] / $today_stats['total']) * 100, 1) 
        : 0;
    $week_rate = $week_stats['total'] > 0 
        ? round(($week_stats['success'] / $week_stats['total']) * 100, 1) 
        : 0;
    $month_rate = $month_stats['total'] > 0 
        ? round(($month_stats['success'] / $month_stats['total']) * 100, 1) 
        : 0;
    
    // Activity breakdown for today
    $activities_today = $wpdb->get_results($wpdb->prepare(
        "SELECT activities FROM $table_name WHERE date = %s",
        $today
    ));
    
    $activity_counts = array();
    foreach ($activities_today as $row) {
        $acts = json_decode($row->activities, true);
        if ($acts) {
            foreach ($acts as $activity => $count) {
                $activity_counts[$activity] = ($activity_counts[$activity] ?? 0) + $count;
            }
        }
    }
    
    ?>
    <div class="cc-video-tracking-dashboard">
        <style>
            .cc-stat-box {
                padding: 15px;
                margin: 10px 0;
                background: #f9f9f9;
                border-left: 4px solid #2271b1;
                border-radius: 3px;
            }
            .cc-stat-box.excellent { border-left-color: #00a32a; }
            .cc-stat-box.good { border-left-color: #72aee6; }
            .cc-stat-box.warning { border-left-color: #dba617; }
            .cc-stat-box.poor { border-left-color: #d63638; }
            .cc-stat-title { font-weight: 600; margin-bottom: 5px; }
            .cc-stat-number { font-size: 24px; font-weight: bold; }
            .cc-stat-detail { color: #666; font-size: 12px; margin-top: 5px; }
            .cc-activity-list { margin-top: 10px; }
            .cc-activity-item { 
                display: flex; 
                justify-content: space-between; 
                padding: 5px 0;
                border-bottom: 1px solid #eee;
            }
        </style>
        
        <h3>Video Tracking Success Rate</h3>
        
        <div class="cc-stat-box <?php echo $today_rate >= 95 ? 'excellent' : ($today_rate >= 85 ? 'good' : ($today_rate >= 70 ? 'warning' : 'poor')); ?>">
            <div class="cc-stat-title">Today</div>
            <div class="cc-stat-number"><?php echo $today_rate; ?>%</div>
            <div class="cc-stat-detail">
                <?php echo number_format($today_stats['success']); ?> successful / 
                <?php echo number_format($today_stats['total']); ?> total requests
            </div>
        </div>
        
        <div class="cc-stat-box <?php echo $week_rate >= 95 ? 'excellent' : ($week_rate >= 85 ? 'good' : ($week_rate >= 70 ? 'warning' : 'poor')); ?>">
            <div class="cc-stat-title">Last 7 Days</div>
            <div class="cc-stat-number"><?php echo $week_rate; ?>%</div>
            <div class="cc-stat-detail">
                <?php echo number_format($week_stats['success']); ?> successful / 
                <?php echo number_format($week_stats['total']); ?> total requests
            </div>
        </div>
        
        <div class="cc-stat-box <?php echo $month_rate >= 95 ? 'excellent' : ($month_rate >= 85 ? 'good' : ($month_rate >= 70 ? 'warning' : 'poor')); ?>">
            <div class="cc-stat-title">Last 30 Days</div>
            <div class="cc-stat-number"><?php echo $month_rate; ?>%</div>
            <div class="cc-stat-detail">
                <?php echo number_format($month_stats['success']); ?> successful / 
                <?php echo number_format($month_stats['total']); ?> total requests
            </div>
        </div>
        
        <?php if (!empty($activity_counts)): ?>
        <div class="cc-stat-box">
            <div class="cc-stat-title">Today's Activity Breakdown</div>
            <div class="cc-activity-list">
                <?php 
                arsort($activity_counts);
                foreach ($activity_counts as $activity => $count): 
                ?>
                <div class="cc-activity-item">
                    <span><?php echo esc_html($activity); ?></span>
                    <span><strong><?php echo number_format($count); ?></strong></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <strong>Target:</strong> 95%+ success rate<br>
            <strong>Current Status:</strong> 
            <?php if ($month_rate >= 95): ?>
                <span style="color: #00a32a;">✓ Excellent</span>
            <?php elseif ($month_rate >= 85): ?>
                <span style="color: #72aee6;">✓ Good</span>
            <?php elseif ($month_rate >= 70): ?>
                <span style="color: #dba617;">⚠ Needs Improvement</span>
            <?php else: ?>
                <span style="color: #d63638;">✗ Poor - Action Required</span>
            <?php endif; ?>
        </p>
    </div>
    <?php
}

/**
 * DETAILED STATS PAGE (Optional)
 * Add to admin menu for more detailed analytics
 */
add_action('admin_menu', 'cc_video_tracking_add_menu');
function cc_video_tracking_add_menu() {
    add_submenu_page(
        'tools.php',
        'Video Tracking Stats',
        'Video Tracking',
        'manage_options',
        'cc-video-tracking-stats',
        'cc_video_tracking_stats_page'
    );
}

function cc_video_tracking_stats_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'video_tracking_monitor';
    
    // Get daily stats for last 30 days
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $daily_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            date,
            SUM(total_requests) as total,
            SUM(successful_requests) as success,
            SUM(failed_requests) as failed,
            ROUND((SUM(successful_requests) / SUM(total_requests)) * 100, 1) as success_rate
        FROM $table_name 
        WHERE date >= %s
        GROUP BY date
        ORDER BY date DESC",
        $thirty_days_ago
    ), ARRAY_A);
    
    ?>
    <div class="wrap">
        <h1>Video Tracking Statistics</h1>
        
        <div class="card" style="max-width: none;">
            <h2>Daily Success Rates (Last 30 Days)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Requests</th>
                        <th>Successful</th>
                        <th>Failed</th>
                        <th>Success Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily_stats as $stat): ?>
                    <tr>
                        <td><?php echo date('D, j M Y', strtotime($stat['date'])); ?></td>
                        <td><?php echo number_format($stat['total']); ?></td>
                        <td><?php echo number_format($stat['success']); ?></td>
                        <td><?php echo number_format($stat['failed']); ?></td>
                        <td><strong><?php echo $stat['success_rate']; ?>%</strong></td>
                        <td>
                            <?php if ($stat['success_rate'] >= 95): ?>
                                <span style="color: #00a32a;">✓ Excellent</span>
                            <?php elseif ($stat['success_rate'] >= 85): ?>
                                <span style="color: #72aee6;">✓ Good</span>
                            <?php elseif ($stat['success_rate'] >= 70): ?>
                                <span style="color: #dba617;">⚠ Fair</span>
                            <?php else: ?>
                                <span style="color: #d63638;">✗ Poor</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card" style="max-width: none; margin-top: 20px;">
            <h2>Improvement Tips</h2>
            <ul>
                <li><strong>95%+ Success Rate:</strong> Excellent - system is working well</li>
                <li><strong>85-95% Success Rate:</strong> Good - minor issues may exist</li>
                <li><strong>70-85% Success Rate:</strong> Fair - improvements recommended</li>
                <li><strong>&lt;70% Success Rate:</strong> Poor - immediate action required</li>
            </ul>
            <p>If you see declining success rates, check:</p>
            <ol>
                <li>WordPress debug log for errors</li>
                <li>Browser console for JavaScript errors</li>
                <li>Network tab in DevTools for failed requests</li>
                <li>Server error logs for PHP errors</li>
            </ol>
        </div>
    </div>
    <?php
}
