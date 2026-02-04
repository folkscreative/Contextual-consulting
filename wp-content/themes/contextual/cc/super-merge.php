<?php
/**
 * Admin Account Merge Tool - Updated to handle secondary account email aliases
 * Add this to your theme's functions.php or create it as a separate admin plugin
 */

// Add admin menu item
add_action('admin_menu', 'cc_add_merge_tool_menu');
function cc_add_merge_tool_menu() {
    add_management_page(
        'Account Merge Tool',
        'Account Merge Tool',
        'manage_options',
        'cc-account-merge-tool',
        'cc_account_merge_tool_page'
    );
}

// Admin page HTML - FIXED to require immediate dry run
function cc_account_merge_tool_page() {
    // Clear any existing transient if this is a fresh page load (not a form submission)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        delete_transient('cc_merge_can_proceed_' . get_current_user_id());
        $can_proceed = false;
    } else {
        // Check if we have a valid dry run stored
        $can_proceed = get_transient('cc_merge_can_proceed_' . get_current_user_id());
    }
    
    ?>
    <div class="wrap">
        <h1>Account Merge Tool</h1>
        <div class="notice notice-warning">
            <p><strong>Warning:</strong> This tool performs account merges. THERE IS ON UNDO OPTION. Use with EXTREME CAUTION!!</p>
        </div>
        
        <?php
        // Process the form BEFORE displaying it, so we can check the transient after dry run
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('cc_merge_tool_action', 'cc_merge_tool_nonce')) {
            if (isset($_POST['dry_run'])) {
                cc_merge_dry_run($_POST['primary_email'], $_POST['secondary_email']);
                // Re-check the transient after dry run
                $can_proceed = get_transient('cc_merge_can_proceed_' . get_current_user_id());
            } elseif (isset($_POST['execute_merge'])) {
                // Verify transient exists and matches before executing
                $stored_data = get_transient('cc_merge_can_proceed_' . get_current_user_id());
                if ($stored_data && 
                    $stored_data['primary_email'] === $_POST['primary_email'] && 
                    $stored_data['secondary_email'] === $_POST['secondary_email']) {
                    cc_execute_merge($_POST['primary_email'], $_POST['secondary_email']);
                } else {
                    echo '<div class="notice notice-error"><p>Please run a dry check first before executing the merge.</p></div>';
                }
                // Clear the transient after execution attempt
                delete_transient('cc_merge_can_proceed_' . get_current_user_id());
                $can_proceed = false;
            }
        }
        ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('cc_merge_tool_action', 'cc_merge_tool_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="primary_email">Primary Email (Keep this account)</label></th>
                    <td>
                        <input type="email" name="primary_email" id="primary_email" class="regular-text" 
                               value="<?php echo ($can_proceed && isset($can_proceed['primary_email'])) ? esc_attr($can_proceed['primary_email']) : (isset($_POST['primary_email']) ? esc_attr($_POST['primary_email']) : ''); ?>" 
                               required />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="secondary_email">Secondary Email (Merge from this account)</label></th>
                    <td>
                        <input type="email" name="secondary_email" id="secondary_email" class="regular-text" 
                               value="<?php echo ($can_proceed && isset($can_proceed['secondary_email'])) ? esc_attr($can_proceed['secondary_email']) : (isset($_POST['secondary_email']) ? esc_attr($_POST['secondary_email']) : ''); ?>" 
                               required />
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="dry_run" class="button button-primary" value="Run Dry Check" />
                <?php if ($can_proceed): ?>
                    <input type="submit" name="execute_merge" class="button" value="Execute Merge" 
                           style="background: #d63638; border-color: #d63638; color: white;"
                           onclick="return confirm('Are you sure you want to proceed with the merge? This cannot be undone.');" />
                    <span style="color: green; margin-left: 10px; font-weight: bold;">✓ Dry run successful - ready to merge</span>
                <?php endif; ?>
            </p>
        </form>
    </div>
    <?php
}

// Dry run function
function cc_merge_dry_run($primary_email, $secondary_email) {
    global $wpdb;
    
    $report = array();
    $warnings = array();
    $errors = array();
    $can_proceed = true;
    
    // Get user objects
    $primary_user = get_user_by('email', sanitize_email($primary_email));
    $secondary_user = get_user_by('email', sanitize_email($secondary_email));
    
    echo '<div class="notice notice-info"><h2>Dry Run Report</h2></div>';
    
    // 1. Check users exist
    if (!$primary_user) {
        $errors[] = "Primary user with email {$primary_email} not found.";
        $can_proceed = false;
    } else {
        $report[] = "✓ Primary user found: {$primary_user->display_name} (ID: {$primary_user->ID})";
    }
    
    if (!$secondary_user) {
        $errors[] = "Secondary user with email {$secondary_email} not found.";
        $can_proceed = false;
    } else {
        $report[] = "✓ Secondary user found: {$secondary_user->display_name} (ID: {$secondary_user->ID})";
    }
    
    if (!$can_proceed) {
        cc_display_merge_report($report, $warnings, $errors, false);
        return;
    }
    
    // 2. Check email aliases - ENHANCED SECTION
    $primary_alias_1 = get_user_meta($primary_user->ID, 'user_email_alias_1', true);
    $primary_alias_2 = get_user_meta($primary_user->ID, 'user_email_alias_2', true);
    $primary_alias_3 = get_user_meta($primary_user->ID, 'user_email_alias_3', true);
    
    $secondary_alias_1 = get_user_meta($secondary_user->ID, 'user_email_alias_1', true);
    $secondary_alias_2 = get_user_meta($secondary_user->ID, 'user_email_alias_2', true);
    $secondary_alias_3 = get_user_meta($secondary_user->ID, 'user_email_alias_3', true);
    
    // Count existing aliases and available slots
    $primary_aliases = array_filter(array($primary_alias_1, $primary_alias_2, $primary_alias_3));
    $secondary_aliases = array_filter(array($secondary_alias_1, $secondary_alias_2, $secondary_alias_3));
    
    // Add the secondary user's main email to the list of emails to migrate
    $emails_to_migrate = array($secondary_user->user_email);
    $emails_to_migrate = array_merge($emails_to_migrate, $secondary_aliases);
    
    $available_slots = 3 - count($primary_aliases);
    $needed_slots = count($emails_to_migrate);
    
    $report[] = "→ Primary account email aliases (" . count($primary_aliases) . " of 3 used):";
    if (!empty($primary_alias_1)) $report[] = "  - Alias 1: {$primary_alias_1}";
    if (!empty($primary_alias_2)) $report[] = "  - Alias 2: {$primary_alias_2}";
    if (!empty($primary_alias_3)) $report[] = "  - Alias 3: {$primary_alias_3}";
    
    $report[] = "→ Secondary account emails to migrate (" . $needed_slots . " total):";
    $report[] = "  - Main secondary email: {$secondary_user->user_email}";
    if (!empty($secondary_alias_1)) $report[] = "  - Alias 1: {$secondary_alias_1}";
    if (!empty($secondary_alias_2)) $report[] = "  - Alias 2: {$secondary_alias_2}";
    if (!empty($secondary_alias_3)) $report[] = "  - Alias 3: {$secondary_alias_3}";
    
    if ($available_slots >= $needed_slots) {
        $report[] = "✓ Sufficient alias slots available: {$available_slots} slots for {$needed_slots} emails";
    } else {
        $errors[] = "Insufficient email alias slots. Need {$needed_slots} slots but only {$available_slots} available.";
        $can_proceed = false;
    }
    
    // 3. Check for previous merges
    $merge_log = get_user_meta($primary_user->ID, 'merge_acct_log', true);
    if (!empty($merge_log)) {
        $warnings[] = "Primary account has previous merge history. Review carefully.";
        $previous_merges = substr_count($merge_log, 'Merging users:');
        $report[] = "⚠ Previous merges detected: {$previous_merges}";
    }
    
    // 4. Check Mailster subscribers
    if (function_exists('mailster')) {
        $pri_subscriber = mailster('subscribers')->get_by_mail($primary_user->user_email);
        $sec_subscriber = mailster('subscribers')->get_by_mail($secondary_user->user_email);
        
        if ($pri_subscriber) {
            $pri_lists = mailster('subscribers')->get_lists($pri_subscriber->ID, true);
            $report[] = "✓ Primary Mailster subscriber found with " . count($pri_lists) . " lists";
        } else {
            $warnings[] = "Primary user not found in Mailster subscribers";
        }
        
        if ($sec_subscriber) {
            $sec_lists = mailster('subscribers')->get_lists($sec_subscriber->ID, true);
            $report[] = "✓ Secondary Mailster subscriber found with " . count($sec_lists) . " lists";
            if (count($sec_lists) > 0) {
                $report[] = "  Lists to be merged: " . implode(', ', $sec_lists);
            }
        } else {
            $warnings[] = "Secondary user not found in Mailster subscribers";
        }
    }
    
    // 5. Check workshops
    $wkshop_users_table = $wpdb->prefix . 'wshops_users';
    $workshops_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $wkshop_users_table WHERE user_id = %d",
        $secondary_user->ID
    ));
    $report[] = "→ Workshops to migrate: {$workshops_count}";
    
    // 6. Check attendees (including recordings)
    $attendees_table = $wpdb->prefix . 'cc_attendees';
    $attendees_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $attendees_table WHERE user_id = %d",
        $secondary_user->ID
    ));
    $report[] = "→ Attendee records to migrate: {$attendees_count}";
    
    // 7. Check payments - ENHANCED to check for all payment types
    $payments_table = $wpdb->prefix . 'ccpa_payments';
    
    // Build the email list for payment checks
    $all_secondary_emails = array_merge(array($secondary_user->user_email), $secondary_aliases);
    $email_placeholders = implode(',', array_fill(0, count($all_secondary_emails), '%s'));
    
    // Workshop payments (empty type)
    $query_args = array_merge(array($secondary_user->ID), $all_secondary_emails);
    $workshop_payments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table 
        WHERE type = '' AND (reg_userid = %d OR email IN ($email_placeholders))",
        $query_args
    ));
    $report[] = "→ Workshop payments to migrate: {$workshop_payments}";
    
    // Recording payments
    $recording_payments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table 
        WHERE type = 'recording' AND (reg_userid = %d OR email IN ($email_placeholders))",
        $query_args
    ));
    $report[] = "→ Recording payments to migrate: {$recording_payments}";
    
    // Series payments
    $series_payments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table 
        WHERE type = 'series' AND (reg_userid = %d OR email IN ($email_placeholders))",
        $query_args
    ));
    $report[] = "→ Series payments to migrate: {$series_payments}";
    
    // Group payments
    $group_payments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table 
        WHERE type = 'group' AND (reg_userid = %d OR email IN ($email_placeholders))",
        $query_args
    ));
    $report[] = "→ Group payments to migrate: {$group_payments}";

    // Auto payments
    $group_payments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table 
        WHERE type = 'auto' AND (reg_userid = %d OR email IN ($email_placeholders))",
        $query_args
    ));
    $report[] = "→ Auto payments to migrate: {$group_payments}";

    // Check for potential payment conflicts
    $duplicate_check = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table p1
        JOIN $payments_table p2 ON p1.wkshop_id = p2.wkshop_id
        WHERE p1.reg_userid = %d AND p2.reg_userid = %d
        AND p1.type = p2.type",
        $primary_user->ID,
        $secondary_user->ID
    ));
    if ($duplicate_check > 0) {
        $warnings[] = "Found {$duplicate_check} potential duplicate payment records";
    }
    
    // 8. Check recordings
    $recordings_users_table = $wpdb->prefix . 'recordings_users';
    $recordings_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $recordings_users_table WHERE user_id = %d",
        $secondary_user->ID
    ));
    $report[] = "→ Recording access records to migrate: {$recordings_count}";
    
    // 9. Check usermeta for recordings
    $usermeta_table = $wpdb->prefix . 'usermeta';
    $rec_usermeta_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $usermeta_table 
        WHERE user_id = %d AND meta_key LIKE %s",
        $secondary_user->ID,
        'cc_rec_wshop_%'
    ));
    $report[] = "→ Recording usermeta entries to migrate: {$rec_usermeta_count}";

    // Check for feedbacks
    $feedback_table = $wpdb->prefix.'feedback';
    $feedbacks_count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $feedback_table
        WHERE user_id = %d",
        $secondary_user->ID
    ) );
    $report[] = "→ Feedback entries to migrate: {$feedbacks_count}";
    
    // 10. Check for active sessions
    $session_tokens = get_user_meta($secondary_user->ID, 'session_tokens', true);
    if (!empty($session_tokens)) {
        $warnings[] = "Secondary user has active sessions that will be terminated";
    }
    
    // 11. Check roles
    if (empty($secondary_user->roles)) {
        $warnings[] = "Secondary user already has no roles - may have been previously merged";
    } else {
        $report[] = "→ Secondary user roles to be removed: " . implode(', ', $secondary_user->roles);
    }
    
    // Store decision in transient for execute button
    if ($can_proceed) {
        set_transient('cc_merge_can_proceed_' . get_current_user_id(), array(
            'primary_id' => $primary_user->ID,
            'secondary_id' => $secondary_user->ID,
            'primary_email' => $primary_email,
            'secondary_email' => $secondary_email,
            'emails_to_migrate' => $emails_to_migrate
        ), 300); // 5 minutes expiry
    } else {
        // Clear any existing transient if the dry run fails
        delete_transient('cc_merge_can_proceed_' . get_current_user_id());
    }

    cc_display_merge_report($report, $warnings, $errors, $can_proceed);
}

// Display report function
function cc_display_merge_report($report, $warnings, $errors, $can_proceed) {
    echo '<div class="merge-report" style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4;">';
    
    // Errors
    if (!empty($errors)) {
        echo '<div class="notice notice-error" style="margin: 0 0 20px 0;">';
        echo '<h3 style="margin-top: 10px;">❌ Errors (Merge Cannot Proceed)</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    // Warnings
    if (!empty($warnings)) {
        echo '<div class="notice notice-warning" style="margin: 0 0 20px 0;">';
        echo '<h3 style="margin-top: 10px;">⚠️ Warnings (Review Carefully)</h3>';
        echo '<ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . esc_html($warning) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    // Report
    echo '<div class="notice notice-info" style="margin: 0;">';
    echo '<h3 style="margin-top: 10px;">📊 Merge Analysis</h3>';
    echo '<ul>';
    foreach ($report as $item) {
        echo '<li>' . esc_html($item) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    
    // Summary
    if ($can_proceed) {
        echo '<div class="notice notice-success" style="margin: 20px 0 0 0;">';
        echo '<p><strong>✅ Dry run complete.</strong> The merge appears safe to proceed. Click "Execute Merge" to perform the actual merge.</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-error" style="margin: 20px 0 0 0;">';
        echo '<p><strong>Cannot proceed with merge.</strong> Please resolve the errors above.</p>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Execute merge function
function cc_execute_merge($primary_email, $secondary_email) {
    global $wpdb;

    // Get stored IDs from transient
    $stored_data = get_transient('cc_merge_can_proceed_' . get_current_user_id());
    if (!$stored_data) {
        echo '<div class="notice notice-error"><p>No valid dry run found. Please run dry check first.</p></div>';
        return;
    }
    
    // Validate that the emails match what was checked in dry run
    if ($stored_data['primary_email'] !== $primary_email || $stored_data['secondary_email'] !== $secondary_email) {
        echo '<div class="notice notice-error"><p>Email mismatch. The emails don\'t match the dry run. Please run dry check again.</p></div>';
        delete_transient('cc_merge_can_proceed_' . get_current_user_id());
        return;
    }
    
    $primary_user = get_user_by('id', $stored_data['primary_id']);
    $secondary_user = get_user_by('id', $stored_data['secondary_id']);
    $emails_to_migrate = $stored_data['emails_to_migrate'];
    
    // Start merge process
    $log_message = "\n" . date('d/m/Y H:i:s') . ' Admin merge by ' . wp_get_current_user()->user_login;
    $log_message .= "\nMerging {$secondary_user->user_email} (ID: {$secondary_user->ID}) into {$primary_user->user_email} (ID: {$primary_user->ID})";
    
    try {
        // 1. Mailster lists
        if (function_exists('mailster')) {
            $sec_subscriber = mailster('subscribers')->get_by_mail($secondary_user->user_email);
            if ($sec_subscriber) {
                $sec_subs_list_ids = mailster('subscribers')->get_lists($sec_subscriber->ID, true);
                $log_message .= "\nSecondary subscriber lists: " . implode(', ', $sec_subs_list_ids);
                
                $pri_subscriber = mailster('subscribers')->get_by_mail($primary_user->user_email);
                if ($pri_subscriber) {
                    $pri_subs_list_ids = mailster('subscribers')->get_lists($pri_subscriber->ID, true);
                    $log_message .= "\nPrimary subscriber lists: " . implode(', ', $pri_subs_list_ids);
                    mailster('subscribers')->assign_lists($pri_subscriber->ID, $sec_subs_list_ids);
                    mailster('subscribers')->unassign_lists($sec_subscriber->ID);
                }
            }
        }
        
        // 2. Add email aliases - ENHANCED to handle multiple emails
        $primary_alias_1 = get_user_meta($primary_user->ID, 'user_email_alias_1', true);
        $primary_alias_2 = get_user_meta($primary_user->ID, 'user_email_alias_2', true);
        $primary_alias_3 = get_user_meta($primary_user->ID, 'user_email_alias_3', true);
        
        foreach ($emails_to_migrate as $email_to_add) {
            if (empty($primary_alias_1)) {
                update_user_meta($primary_user->ID, 'user_email_alias_1', $email_to_add);
                $primary_alias_1 = $email_to_add; // Mark as used
                $log_message .= "\nEmail alias 1 set to: {$email_to_add}";
            } elseif (empty($primary_alias_2)) {
                update_user_meta($primary_user->ID, 'user_email_alias_2', $email_to_add);
                $primary_alias_2 = $email_to_add; // Mark as used
                $log_message .= "\nEmail alias 2 set to: {$email_to_add}";
            } elseif (empty($primary_alias_3)) {
                update_user_meta($primary_user->ID, 'user_email_alias_3', $email_to_add);
                $primary_alias_3 = $email_to_add; // Mark as used
                $log_message .= "\nEmail alias 3 set to: {$email_to_add}";
            }
        }
        
        // 3. Move workshops
        $wkshop_users_table = $wpdb->prefix . 'wshops_users';
        $result = $wpdb->update(
            $wkshop_users_table,
            array('user_id' => $primary_user->ID),
            array('user_id' => $secondary_user->ID)
        );
        $log_message .= "\n{$result} workshops_users rows moved";
        
        // 4. Move attendees
        $attendees_table = $wpdb->prefix . 'cc_attendees';
        $result = $wpdb->update(
            $attendees_table,
            array('user_id' => $primary_user->ID),
            array('user_id' => $secondary_user->ID)
        );
        $log_message .= "\n{$result} attendees rows moved";

        // 5. Move payments - ENHANCED to handle all payment types
        $payments_table = $wpdb->prefix . 'ccpa_payments';
        
        // Get secondary user's aliases for payment migration
        $secondary_alias_1 = get_user_meta($secondary_user->ID, 'user_email_alias_1', true);
        $secondary_alias_2 = get_user_meta($secondary_user->ID, 'user_email_alias_2', true);
        $secondary_alias_3 = get_user_meta($secondary_user->ID, 'user_email_alias_3', true);
        $secondary_aliases = array_filter(array($secondary_alias_1, $secondary_alias_2, $secondary_alias_3));
        
        // Build list of all secondary emails to check
        $all_secondary_emails = array_merge(array($secondary_user->user_email), $secondary_aliases);
        
        // Define payment types to process
        $payment_types = array(
            '' => 'workshop',
            'recording' => 'recording',
            'series' => 'series',
            'group' => 'group',
            'auto' => 'auto'
        );
        
        // Process each payment type
        foreach ($payment_types as $type => $label) {
            $count_payments = 0;
            foreach ($all_secondary_emails as $check_email) {
                $sql = $wpdb->prepare(
                    "SELECT * FROM $payments_table WHERE type = %s AND (reg_userid = %d OR email = %s)",
                    $type,
                    $secondary_user->ID,
                    $check_email
                );
                $payments = $wpdb->get_results($sql, ARRAY_A);
                foreach ($payments as $payment) {
                    $wpdb->update(
                        $payments_table,
                        array('email' => $primary_user->user_email, 'reg_userid' => $primary_user->ID),
                        array('id' => $payment['id'])
                    );
                    $count_payments++;
                }
            }
            $log_message .= "\n{$count_payments} {$label} payments moved";
        }

        // 6. Move recordings
        $recordings_users_table = $wpdb->prefix . 'recordings_users';
        $result = $wpdb->update(
            $recordings_users_table,
            array('user_id' => $primary_user->ID),
            array('user_id' => $secondary_user->ID)
        );
        $log_message .= "\n{$result} recordings_users rows moved";
        
        // 7. Move usermeta - FIXED the LIKE query
        $usermeta_table = $wpdb->prefix . 'usermeta';
        $sql = $wpdb->prepare(
            "SELECT * FROM $usermeta_table WHERE user_id = %d AND meta_key LIKE %s",
            $secondary_user->ID,
            'cc_rec_wshop_%'
        );
        $usermetas = $wpdb->get_results($sql, ARRAY_A);
        $count_usermetas = 0;
        foreach ($usermetas as $usermeta) {
            $wpdb->update(
                $usermeta_table,
                array('user_id' => $primary_user->ID),
                array('umeta_id' => $usermeta['umeta_id'])
            );
            $count_usermetas++;
        }
        $log_message .= "\n{$count_usermetas} usermetas moved";

        // Move feedbacks
        $data = array(
            'user_id' => $primary_user->ID
        );
        $where = array(
            'user_id' => $secondary_user->ID
        );
        $result = $wpdb->update( $feedback_table, $data, $where );
        if( $result ){
            $log_message .= "\n{$result} feedbacks moved";
        }
        
        // 8. Clear the secondary user's email aliases since we've migrated them
        delete_user_meta($secondary_user->ID, 'user_email_alias_1');
        delete_user_meta($secondary_user->ID, 'user_email_alias_2');
        delete_user_meta($secondary_user->ID, 'user_email_alias_3');
        $log_message .= "\nSecondary user email aliases cleared";
        
        // 9. Remove roles and logout
        $secondary_user->set_role('');
        update_user_meta($secondary_user->ID, 'session_tokens', '');
        $log_message .= "\nUser {$secondary_user->ID} access removed";
        
        // Save log
        if (function_exists('wms_write_log')) {
            wms_write_log($log_message);
        }
        $curr_log = get_user_meta($primary_user->ID, 'merge_acct_log', true);
        $log_message_html = str_replace("\n", "<br>", $log_message); // Convert for HTML display
        update_user_meta($primary_user->ID, 'merge_acct_log', $curr_log . $log_message_html);
        
        // Clear transient
        delete_transient('cc_merge_can_proceed_' . get_current_user_id());
        
        echo '<div class="notice notice-success"><h3>✅ Merge Completed Successfully</h3>';
        echo '<p>All data from <strong>' . esc_html($secondary_email) . '</strong> has been merged into <strong>' . esc_html($primary_email) . '</strong></p>';
        echo '<h4>Summary:</h4>';
        echo '<pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">' . esc_html($log_message) . '</pre>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="notice notice-error"><p>Error during merge: ' . esc_html($e->getMessage()) . '</p></div>';
        if (function_exists('wms_write_log')) {
            wms_write_log('Merge error: ' . $e->getMessage());
        }
    }
}