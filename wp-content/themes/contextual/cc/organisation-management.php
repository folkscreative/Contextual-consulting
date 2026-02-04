<?php
/**
 * Organisation Management System
 */

// Register the admin menu
add_action('admin_menu', 'org_management_admin_menu');
function org_management_admin_menu() {
    add_menu_page(
        'Organisation Management',
        'Organisations',
        'manage_options',
        'organisation-management',
        'org_management_page',
        'dashicons-groups',
        30
    );
}

// Enqueue admin styles for better layout control
add_action('admin_enqueue_scripts', 'org_management_admin_styles');
function org_management_admin_styles($hook) {
    // Only load on our admin page
    if ($hook !== 'toplevel_page_organisation-management') {
        return;
    }
    
    // Add inline styles for the table layout fix
    wp_add_inline_style('wp-admin', '
        .org-contracts-table input[type="date"],
        .org-contracts-table select {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        .org-contracts-table td {
            padding: 8px !important;
        }
        .org-contract-type-select {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
    ');
}

// Process form submissions before any output (prevents header errors)
add_action('admin_init', 'org_management_process_forms');
function org_management_process_forms() {
    // Only process on our admin page
    if (!isset($_GET['page']) || $_GET['page'] !== 'organisation-management') {
        return;
    }
    
    // Check if form was submitted
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_contracts' && check_admin_referer('org_contract_update')) {
            org_management_update_contracts();
        } elseif ($_POST['action'] == 'export_feedback' && check_admin_referer('org_feedback_export')) {
            org_management_export_feedback();
            exit; // Stop execution after file download
        }
    }
}

// Define database version
define('ORG_MANAGEMENT_DB_VERSION', '1.2');

// Hook into init to check and create/update database
add_action('init', 'org_management_check_database');
function org_management_check_database() {
    $installed_version = get_option('org_management_db_version');
    
    if ($installed_version != ORG_MANAGEMENT_DB_VERSION) {
        org_management_create_or_update_table();
        org_management_setup_capabilities();
    }
}

// Create or update database table
function org_management_create_or_update_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Get the installed version before we update it
    $installed_version = get_option('org_management_db_version');
    
    // Table structure - dbDelta will handle both CREATE and UPDATE operations
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        org_code varchar(20) NOT NULL,
        org_name varchar(100) NOT NULL,
        contract_start date DEFAULT NULL,
        contract_end date DEFAULT NULL,
        contract_type varchar(20) DEFAULT 'unlimited',
        status varchar(20) DEFAULT 'active',
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY org_code (org_code),
        KEY status_index (status),
        KEY contract_dates (contract_start, contract_end)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Update the version in options
    update_option('org_management_db_version', ORG_MANAGEMENT_DB_VERSION);
    
    // Run any version-specific updates
    org_management_run_updates($installed_version);
    
    // Insert default organisations if they don't exist
    org_management_insert_defaults();
}

// Setup capabilities (only runs during version updates)
function org_management_setup_capabilities() {
    $role = get_role('administrator');
    if ($role && !$role->has_cap('manage_organisation_contracts')) {
        $role->add_cap('manage_organisation_contracts');
        error_log('Organisation Management: Added manage_organisation_contracts capability');
    }
    
    // Optionally add capabilities to other roles
    // $editor = get_role('editor');
    // if ($editor && !$editor->has_cap('view_organisation_contracts')) {
    //     $editor->add_cap('view_organisation_contracts');
    // }
}

// Run version-specific updates
function org_management_run_updates($previous_version) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    
    if (!$previous_version) {
        // First installation
        error_log('Organisation Management: Initial installation completed');
        return;
    }
    
    // Version 1.0 to 1.1 updates
    if (version_compare($previous_version, '1.1', '<')) {
        // Example: Add any specific migration code here
        // For instance, if you added the 'notes' field in version 1.1:
        error_log('Organisation Management: Updated from version ' . $previous_version . ' to 1.1');
    }
    
    // Version 1.1 to 1.2 updates - Add contract_type field
    if (version_compare($previous_version, '1.2', '<')) {
        // Set default contract_type for existing records
        $wpdb->query("UPDATE $table_name SET contract_type = 'unlimited' WHERE contract_type IS NULL OR contract_type = ''");
        error_log('Organisation Management: Updated from version ' . $previous_version . ' to 1.2 - Added contract_type field');
    }
    
    // Future version updates can be added here
    // if (version_compare($previous_version, '1.2', '<')) {
    //     // Version 1.2 specific updates
    // }
}

// Insert default organisations
function org_management_insert_defaults() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    
    $defaults = array(
        array('org_code' => 'cnwl', 'org_name' => 'Central and North West London NHS'),
        array('org_code' => 'nlft', 'org_name' => 'North London Foundation Trust')
    );
    
    foreach ($defaults as $org) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE org_code = %s",
            $org['org_code']
        ));
        
        if (!$exists) {
            $wpdb->insert($table_name, $org);
        }
    }
}

// Main admin page
function org_management_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    
    // Get all organisations
    $organisations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY org_name");
    
    // Check if table is empty and organisations is false/null
    if (!$organisations) {
        $organisations = array();
        // Try to insert defaults again if table is empty
        org_management_insert_defaults();
        $organisations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY org_name");
    }
    ?>
    
    <div class="wrap">
        <h1>Organisation Management</h1>
        
        <?php 
        // Display success message
        if (isset($_GET['message']) && $_GET['message'] == 'updated'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Contract dates updated successfully!</p>
            </div>
        <?php endif; 
        
        // Display any errors
        $errors = get_transient('org_management_errors');
        if ($errors && is_array($errors)): 
            delete_transient('org_management_errors');
        ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Some updates failed:</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Contract Management Section -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>Contract Dates Management</h2>
            <form method="post" action="">
                <?php wp_nonce_field('org_contract_update'); ?>
                <input type="hidden" name="action" value="update_contracts">
                
                <div style="overflow-x: auto;">
                <table class="wp-list-table widefat fixed striped org-contracts-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Organisation</th>
                            <th style="width: 8%;">Code</th>
                            <th style="width: 15%;">Contract Start Date</th>
                            <th style="width: 15%;">Contract End Date</th>
                            <th style="width: 17%;">Contract Type</th>
                            <th style="width: 20%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($organisations as $org): ?>
                            <tr>
                                <td><strong><?php echo esc_html($org->org_name); ?></strong></td>
                                <td><?php echo esc_html(strtoupper($org->org_code)); ?></td>
                                <td>
                                    <input type="date" 
                                           name="contracts[<?php echo $org->id; ?>][start_date]" 
                                           value="<?php echo esc_attr($org->contract_start); ?>"
                                           class="org-date-input">
                                </td>
                                <td>
                                    <input type="date" 
                                           name="contracts[<?php echo $org->id; ?>][end_date]" 
                                           value="<?php echo esc_attr($org->contract_end); ?>"
                                           class="org-date-input">
                                </td>
                                <td>
                                    <select name="contracts[<?php echo $org->id; ?>][contract_type]" class="org-contract-type-select">
                                        <option value="unlimited" <?php selected($org->contract_type, 'unlimited'); ?>>Unlimited</option>
                                        <option value="fixed_number" <?php selected($org->contract_type, 'fixed_number'); ?>>Fixed Number</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="contracts[<?php echo $org->id; ?>][status]" class="org-status-select">
                                        <option value="active" <?php selected($org->status, 'active'); ?>>Active</option>
                                        <option value="inactive" <?php selected($org->status, 'inactive'); ?>>Inactive</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Update Contract Dates">
                </p>
            </form>
        </div>
        
        <!-- Feedback Export Section -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>Export Training Feedback</h2>
            <form method="post" action="" id="feedback-export-form">
                <?php wp_nonce_field('org_feedback_export'); ?>
                <input type="hidden" name="action" value="export_feedback">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="export_org">Organisation</label></th>
                        <td>
                            <select name="export_org" id="export_org" required>
                                <option value="">Select Organisation</option>
                                <?php foreach ($organisations as $org): ?>
                                    <option value="<?php echo esc_attr($org->org_code); ?>">
                                        <?php echo esc_html($org->org_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="export_start_date">Start Date</label></th>
                        <td>
                            <input type="date" name="export_start_date" id="export_start_date" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="export_end_date">End Date</label></th>
                        <td>
                            <input type="date" name="export_end_date" id="export_end_date" required>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Export Feedback to CSV">
                </p>
            </form>
        </div>
        
        <!-- Quick Stats Section -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>Quick Statistics</h2>
            <?php org_management_display_stats(); ?>
        </div>
    </div>
    
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
        }
        .card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        /* Fix for input fields extending beyond table cells */
        .org-contracts-table {
            table-layout: fixed;
        }
        .org-contracts-table td {
            overflow: hidden;
        }
        .org-date-input {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .org-status-select {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .org-contract-type-select {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        /* Responsive adjustments */
        @media screen and (max-width: 1200px) {
            .org-date-input,
            .org-status-select {
                font-size: 12px;
            }
        }
        /* Fix for WordPress admin table compatibility */
        .wp-admin .org-contracts-table input[type="date"] {
            line-height: normal;
            height: 30px;
            padding: 3px 5px;
        }
        .wp-admin .org-contracts-table select {
            line-height: normal;
            height: 30px;
        }
    </style>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Ensure date inputs are properly sized on load
        $('.org-date-input').each(function() {
            $(this).attr('size', '10');
        });
        
        // Add date validation
        $('#feedback-export-form').on('submit', function() {
            var startDate = $('#export_start_date').val();
            var endDate = $('#export_end_date').val();
            
            if (startDate && endDate && startDate > endDate) {
                alert('End date must be after start date');
                return false;
            }
        });
        
        // Auto-adjust select width if needed
        $('.org-status-select, .org-contract-type-select').each(function() {
            var $this = $(this);
            var width = $this.parent().width();
            if (width > 0) {
                $this.css('max-width', width + 'px');
            }
        });
    });
    </script>
    <?php
}

// Update contract dates
function org_management_update_contracts() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $errors = array();
    $updated = 0;
    
    if (isset($_POST['contracts']) && is_array($_POST['contracts'])) {
        foreach ($_POST['contracts'] as $id => $data) {
            // Validate and sanitize input
            $id = intval($id);
            $start_date = !empty($data['start_date']) ? sanitize_text_field($data['start_date']) : null;
            $end_date = !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null;
            $contract_type = !empty($data['contract_type']) ? sanitize_text_field($data['contract_type']) : 'unlimited';
            $status = sanitize_text_field($data['status']);
            
            // Validate contract_type - ensure it's one of the allowed values
            if (!in_array($contract_type, array('unlimited', 'fixed_number'))) {
                $contract_type = 'unlimited';
            }
            
            // Validate dates if both are provided
            if ($start_date && $end_date && $start_date > $end_date) {
                $errors[] = "Invalid date range for organisation ID $id";
                continue;
            }
            
            $result = $wpdb->update(
                $table_name,
                array(
                    'contract_start' => $start_date,
                    'contract_end' => $end_date,
                    'contract_type' => $contract_type,
                    'status' => $status
                ),
                array('id' => $id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                $updated++;
            } else {
                $errors[] = "Failed to update organisation ID $id";
                error_log("Organisation Management: Database update failed for ID $id - " . $wpdb->last_error);
            }
        }
    }
    
    // Store any errors in a transient for display
    if (!empty($errors)) {
        set_transient('org_management_errors', $errors, 30);
    }
    
    // Perform redirect
    $redirect_url = admin_url('admin.php?page=organisation-management&message=updated');
    wp_safe_redirect($redirect_url);
    exit;
}

// Helper function to ensure proper UTF-8 encoding
function ensure_utf8($text) {
    // First, make sure we're working with UTF-8
    if (!mb_check_encoding($text, 'UTF-8')) {
        // Try to detect the actual encoding and convert to UTF-8
        $encoding = mb_detect_encoding($text, array('UTF-8', 'ISO-8859-1', 'Windows-1252'), true);
        if ($encoding && $encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }
    }
    
    // Decode any HTML entities that might be present
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Remove any control characters except tab, newline, and carriage return
    // These can cause issues in CSV files
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    
    // Normalize the text (decompose and recompose Unicode characters)
    if (class_exists('Normalizer')) {
        $text = Normalizer::normalize($text, Normalizer::FORM_C);
    }
    
    // Trim whitespace
    $text = trim($text);
    
    return $text;
}

// Export feedback function
function org_management_export_feedback() {
    global $wpdb;
    
    // Get parameters
    $org_code = sanitize_text_field($_POST['export_org']);
    $start_date = sanitize_text_field($_POST['export_start_date']) . ' 00:00:00';
    $end_date = sanitize_text_field($_POST['export_end_date']) . ' 23:59:59';
    
    // Get organisation details
    $org_table = $wpdb->prefix . 'organisation_contracts';
    $org = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $org_table WHERE org_code = %s",
        $org_code
    ));
    
    if (!$org) {
        wp_die('Invalid organisation selected.');
    }
    
    // Query feedback data
    $table_name = $wpdb->prefix . 'feedback';
    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE updated >= %s 
         AND updated <= %s 
         ORDER BY training_id ASC, updated ASC",
        $start_date,
        $end_date
    );
    
    $feedbacks = $wpdb->get_results($sql, ARRAY_A);
    
    // Generate filename
    $filename = $org_code . '-feedback-' . date('Y-m-d-H-i-s') . '.csv';
    
    // Set proper headers for UTF-8 CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Prevent caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Add BOM (Byte Order Mark) for UTF-8 
    // This is crucial for Excel to recognize the file as UTF-8
    echo "\xEF\xBB\xBF";

    // Open output stream
    $output = fopen('php://output', 'w');

    // Set UTF-8 encoding for the stream if possible
    if (function_exists('stream_encoding')) {
        stream_encoding($output, 'UTF-8');
    }

    // Add header row
    fputcsv($output, array(
        'Organisation: ' . $org->org_name,
        'Export Date: ' . date('Y-m-d H:i:s'),
        'Period: ' . $_POST['export_start_date'] . ' to ' . $_POST['export_end_date']
    ));
    fputcsv($output, array()); // Empty row
    
    $last_training_id = 0;
    $count_feedbacks = 0;
    $questions = array();
    
    foreach ($feedbacks as $feedback) {
        $portal_user = get_user_meta($feedback['user_id'], 'portal_user', true);
        
        if ($portal_user == $org_code) {
            if ($feedback['training_id'] != $last_training_id) {
                // New training section
                if ($last_training_id > 0) {
                    fputcsv($output, array()); // Empty row between trainings
                }
                
                // Training header
                fputcsv($output, array(
                    'Training ID: ' . $feedback['training_id'],
                    'Title: ' . ensure_utf8( get_the_title($feedback['training_id']) )
                ));
                
                // Get and parse questions
                $feedback_questions = get_post_meta($feedback['training_id'], '_feedback_questions', true);
                $shortcodes = explode(']', $feedback_questions);
                
                $questions = array();
                foreach ($shortcodes as $shortcode) {
                    $atts = shortcode_parse_atts(trim($shortcode) . ']');
                    if (!isset($atts['name'])) continue;

                    // Ensure proper UTF-8 encoding for the question text
                    $question_text = ($atts['question'] == '') ? $atts['name'] : $atts['question'];
                    $question_text = ensure_utf8($question_text);

                    $question = array(
                        'field' => $atts['field'],
                        'name' => strtolower($atts['name']),
                        'question' => $question_text,
                        'required' => $atts['required'],
                        'options' => '',
                        'count' => 0,
                    );
                    
                    if ($atts['field'] == 'radio') {
                        $question['options'] = array_map('trim', explode(',', $atts['options']));
                        $question['count'] = array();
                    }
                    
                    $questions[] = $question;
                }
                
                // Question headers
                $header_row = array('User ID', 'Date Submitted');
                foreach ($questions as $question) {
                    $header_row[] = $question['question'];
                }
                fputcsv($output, $header_row);
            }
            
            // Output feedback answers
            $answers = maybe_unserialize($feedback['feedback']);
            $data_row = array(
                $feedback['user_id'],
                $feedback['updated']
            );
            
            foreach ($questions as $question) {
                $question_name = strtolower($question['name']);
                $answer_text = isset($answers[$question_name]) ? $answers[$question_name] : '';
                // Ensure proper UTF-8 encoding for answer text
                $data_row[] = ensure_utf8($answer_text);
            }
            
            fputcsv($output, $data_row);
            $count_feedbacks++;
            $last_training_id = $feedback['training_id'];
        }
    }
    
    // Summary footer
    fputcsv($output, array()); // Empty row
    fputcsv($output, array('Total Feedback Entries: ' . $count_feedbacks));
    
    fclose($output);
    exit;
}

// Display statistics
function org_management_display_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    $feedback_table = $wpdb->prefix . 'feedback';
    
    $organisations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY org_name");
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Organisation</th><th>Total Feedback Count</th><th>Contract Status</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($organisations as $org) {
        // Count feedback for this organisation
        $sql = "SELECT COUNT(DISTINCT f.id) as count 
                FROM $feedback_table f 
                INNER JOIN {$wpdb->usermeta} um ON f.user_id = um.user_id 
                WHERE um.meta_key = 'portal_user' 
                AND um.meta_value = %s";
        
        $count = $wpdb->get_var($wpdb->prepare($sql, $org->org_code));
        
        // Determine contract status
        $status_text = 'No contract dates set';
        $status_class = '';
        
        if ($org->contract_start && $org->contract_end) {
            $now = current_time('Y-m-d');
            if ($now < $org->contract_start) {
                $status_text = 'Contract pending (starts ' . date('M j, Y', strtotime($org->contract_start)) . ')';
                $status_class = 'notice-info';
            } elseif ($now > $org->contract_end) {
                $status_text = 'Contract expired (ended ' . date('M j, Y', strtotime($org->contract_end)) . ')';
                $status_class = 'notice-error';
            } else {
                $days_remaining = floor((strtotime($org->contract_end) - strtotime($now)) / 86400);
                $status_text = 'Active (expires in ' . $days_remaining . ' days)';
                $status_class = 'notice-success';
            }
        }
        
        echo '<tr>';
        echo '<td><strong>' . esc_html($org->org_name) . '</strong></td>';
        echo '<td>' . intval($count) . '</td>';
        echo '<td><span class="' . $status_class . '" style="padding: 2px 8px; border-radius: 3px;">' . $status_text . '</span></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
}

// Helper function to get organisation contract details
function get_organisation_contract($org_code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'organisation_contracts';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE org_code = %s",
        $org_code
    ));
}

// Helper function to check if organisation contract is active
function is_organisation_contract_active($org_code) {
    $contract = get_organisation_contract($org_code);
    
    if (!$contract || $contract->status !== 'active') {
        return false;
    }
    
    if ($contract->contract_start && $contract->contract_end) {
        $now = current_time('Y-m-d');
        return ($now >= $contract->contract_start && $now <= $contract->contract_end);
    }
    
    return true;
}

// Manual database reset function (for development/debugging)
function org_management_reset_database() {
    delete_option('org_management_db_version');
    org_management_check_database();
}

// Function to completely uninstall the organisation management system
function org_management_uninstall() {
    global $wpdb;
    
    // Only run if explicitly confirmed (safety measure)
    if (!defined('ORG_MANAGEMENT_UNINSTALL_CONFIRMED')) {
        return false;
    }
    
    // Remove database table
    $table_name = $wpdb->prefix . 'organisation_contracts';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Remove options
    delete_option('org_management_db_version');
    delete_option('org_management_completed_migrations');
    
    // Remove capabilities from all roles that might have them
    $roles_to_check = array('administrator', 'editor', 'author');
    $capabilities_to_remove = array(
        'manage_organisation_contracts',
        'view_organisation_contracts',
        'export_organisation_feedback'
    );
    
    foreach ($roles_to_check as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities_to_remove as $cap) {
                if ($role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    // Clear scheduled events
    wp_clear_scheduled_hook('check_organisation_contracts');
    
    error_log('Organisation Management: System uninstalled successfully');
    
    return true;
}

// Version check and upgrade notice
add_action('admin_notices', 'org_management_upgrade_notice');
function org_management_upgrade_notice() {
    $installed_version = get_option('org_management_db_version');
    
    if ($installed_version && version_compare($installed_version, ORG_MANAGEMENT_DB_VERSION, '<')) {
        $class = 'notice notice-info';
        $message = 'Organisation Management database will be updated to version ' . ORG_MANAGEMENT_DB_VERSION . ' on next page load.';
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
}

// AJAX endpoint for getting organisation details (useful for other parts of the site)
add_action('wp_ajax_get_organisation_details', 'ajax_get_organisation_details');
function ajax_get_organisation_details() {
    check_ajax_referer('org_management_nonce', 'nonce');
    
    $org_code = sanitize_text_field($_POST['org_code']);
    $contract = get_organisation_contract($org_code);
    
    if ($contract) {
        wp_send_json_success($contract);
    } else {
        wp_send_json_error('Organisation not found');
    }
}

// Shortcode to display contract status on frontend if needed
add_shortcode('org_contract_status', 'shortcode_org_contract_status');
function shortcode_org_contract_status($atts) {
    $atts = shortcode_atts(array(
        'org' => '',
        'format' => 'text'
    ), $atts);
    
    if (empty($atts['org'])) {
        return '';
    }
    
    $contract = get_organisation_contract($atts['org']);
    
    if (!$contract) {
        return 'Organisation not found';
    }
    
    if ($atts['format'] == 'json') {
        return json_encode($contract);
    }
    
    $output = '';
    if (is_organisation_contract_active($atts['org'])) {
        $output = 'Active (expires ' . date('M j, Y', strtotime($contract->contract_end)) . ')';
    } else {
        $output = 'Inactive';
    }
    
    return $output;
}

function org_register_status( $org, $training_id ){
    global $wpdb;
    $response = array(
        'status' => 'ok',
        'message' => '',
    );
    $table_name = $wpdb->prefix . 'organisation_contracts';
    if( $org == '' ){
        return $response;
    }
    $org_block = get_post_meta( $training_id, 'block_'.$org, true );
    if( $org_block == 'yes' ){
        $response['status'] = 'block';
        $response['message'] = 'Registration closed for your organisation';
        return $response;
    }

    $org_end_date = $wpdb->get_var( "SELECT contract_end FROM $table_name WHERE org_code = '$org'");
    if( empty( $org_end_date ) ){
        return $response;
    }
    $org_end_timestamp = strtotime($org_end_date);
    $today = strtotime('today');
    $one_month_future = strtotime('+1 month', $today);
    if ($org_end_timestamp < $today) {
        $response['status'] = 'expired';
        $response['message'] = 'Your organisation\'s contract has expired';
        return $response;
    } elseif ($org_end_timestamp < $one_month_future) {
        $response['status'] = 'close';
    } else {
        $response['status'] = 'future';
    }
    
    switch (course_training_type($training_id)) {
        case 'workshop':
            $end_timestamp = workshop_calculate_end_timestamp( $training_id );
            if( $end_timestamp > $org_end_timestamp ){
                $response['message'] = 'This training completes after your organisation\'s contract expires';
            }
            break;
        case 'recording':
            if( $response['status'] == 'close' ){
                $response['message'] = 'You will only have access to this training until your organisation\'s contract expires on '.date( 'jS M Y', $org_end_timestamp );
            }
            break;
        case 'series':
            $selected_courses = get_post_meta( $training_id, '_series_courses', true );
            $selected_courses = is_string($selected_courses) ? json_decode($selected_courses, true) : [];
            $selected_courses = is_array($selected_courses) ? $selected_courses : [];
            $inc_wshop = $inc_rec = false;
            foreach ($selected_courses as $course_id) {
                $course_training_type = course_training_type( $course_id );
                if( $course_training_type == 'workshop' ){
                    $inc_wshop = true;
                    $end_timestamp = workshop_calculate_end_timestamp( $course_id );
                    if( $end_timestamp > $org_end_timestamp ){
                        $response['message'] = 'This training completes after your organisation\'s contract expires';
                    }
                }elseif( $course_training_type == 'recording' ){
                    $inc_rec = true;
                }
            }
            if( $inc_rec && $response['message'] == '' && $response['status'] == 'close' ){
                $response['message'] = 'You will only have access to this training until your organisation\'s contract expires on '.date( 'jS M Y', $org_end_timestamp );
            }
    }
    return $response;
}