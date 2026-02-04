<?php
/**
 * Add to Account Registration Functions
 * 
 * Handles instant registration for qualified users (CNWL portal, unlimited subscribers,
 * and users with linked training access) without going through payment process.
 */

/**
 * Create an "Add to Account" registration for a user
 * 
 * This function creates a $0 payment record and grants training access instantly
 * for users who qualify (portal users, unlimited subscribers, or linked access)
 * 
 * @param int $user_id User ID
 * @param int $training_id Training post ID (workshop or recording)
 * @param array $access_details Optional details about how access was granted
 * @return array Success/error response with payment_id if successful
 */
function cc_training_create_add_to_account_registration($user_id, $training_id, $access_details = array()) {
    global $wpdb;
    
    // Validate inputs
    if (!$user_id || !$training_id) {
        return array(
            'success' => false,
            'message' => 'Invalid user or training ID'
        );
    }
    
    // Get user object
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return array(
            'success' => false,
            'message' => 'User not found'
        );
    }
    
    // Verify user is still eligible
    if (!cc_training_can_add_to_account($user_id, $training_id)) {
        return array(
            'success' => false,
            'message' => 'You are not eligible to add this training to your account'
        );
    }
    
    // Check if user is already registered
    if (cc_training_access_user_already_registered($user_id, $training_id)) {
        return array(
            'success' => false,
            'message' => 'You are already registered for this training'
        );
    }
    
    // Determine training type
    $training_type = course_training_type($training_id);
    if (!in_array($training_type, array('workshop', 'recording'))) {
        return array(
            'success' => false,
            'message' => 'Invalid training type for add to account'
        );
    }
    
    // Build the payment record
    $payment = cc_paymentdb_empty_payment();
    
    // Basic training information
    $payment['workshop_id'] = $training_id;
    $payment['type'] = ($training_type === 'recording') ? 'recording' : '';
    
    // User details
    $payment['reg_userid'] = $user_id;
    $payment['att_userid'] = $user_id; // User is also the attendee
    $payment['email'] = $user->user_email;
    $payment['firstname'] = $user->first_name;
    $payment['lastname'] = $user->last_name;
    $payment['phone'] = get_user_meta($user_id, 'phone', true);
    
    // Build address from user meta using existing function
    $payment['address'] = cc_users_user_address($user_id, 'string');
    
    // Payment details - all zero for add to account
    $payment['payment_amount'] = 0;
    $payment['vat_included'] = 0;
    $payment['disc_amount'] = 0;
    $payment['voucher_amount'] = 0;
    $payment['stripe_fee'] = 0;
    
    // Get user's currency
    $currency = cc_currency_get_user_currency();
    $payment['currency'] = $currency;
    
    // Payment status and method
    $payment['status'] = 'Payment not needed';
    $payment['pmt_method'] = 'add_to_account';
    
    // Source tracking
    $payment['source'] = 'Add to account offer';
    
    // Build notes with access details
    $training_title = get_the_title($training_id);
    if (!empty($access_details['via_training_id'])) {
        $via_training_id = $access_details['via_training_id'];
        $via_training_title = get_the_title($via_training_id);
        $payment['notes'] = sprintf(
            'Linked access via %s (ID: %d)',
            $via_training_title,
            $via_training_id
        );
    } else {
        // For portal users or unlimited subscribers
        $portal_user = get_user_meta($user_id, 'portal_user', true);
        if ($portal_user === 'cnwl') {
            $payment['notes'] = 'CNWL portal user - instant access';
        /*
        } else if (function_exists('contextual_get_user_subscription')) {
            $subscription = contextual_get_user_subscription($user_id);
            if ($subscription && $subscription['type'] === 'unlimited') {
                $payment['notes'] = 'Unlimited subscriber - instant access';
            }
        */
        }
        
        // Fallback if neither condition matched
        if (empty($payment['notes'])) {
            $payment['notes'] = 'Add to account registration';
        }
    }
    
    // Mailing list - check if user is already on it
    if (cc_mailsterint_on_newsletter($user->user_email)) {
        $payment['mailing_list'] = 'p'; // Previously subscribed
    } else {
        $payment['mailing_list'] = 'n';
    }
    
    // VAT exempt for zero-value transactions
    $payment['vat_exempt'] = 'y';
    
    // Terms accepted (implied for instant access)
    $payment['tandcs'] = 'y';
    
    // Tracking
    $payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $payment['last_update'] = date('Y-m-d H:i:s');
    
    // No token needed for add to account
    $payment['token'] = '';
    
    // Insert the payment record
    $payment_id = cc_paymentdb_update_payment($payment, '');
    
    if (!$payment_id) {
        return array(
            'success' => false,
            'message' => 'Failed to create registration record'
        );
    }
    
    // Add the payment ID to the payment array for subsequent operations
    $payment['id'] = $payment_id;
    
    // Create attendee record
    $attendee_data = array(
        'payment_id' => $payment_id,
        'user_id' => $user_id,
        'registrant' => 'y', // User is the registrant
    );
    cc_attendee_add($attendee_data);
    
    // Add to workshops_users or recordings_users table
    cc_myacct_insert_workshops_recordings_users($payment);
    
    // For recordings, also grant access via user meta
    if ($training_type === 'recording') {
        ccrecw_add_recording_to_user(
            $user_id,
            $training_id,
            'free', // Access type (free since it's $0)
            0,      // Amount
            '',     // Token (none)
            $currency,
            0,      // VAT included
            $payment_id
        );
    }
    
    // Update user's last registration info
    update_user_meta($user_id, 'last_registration', date('Y-m-d H:i:s'));
    update_user_meta($user_id, 'last_reg_id', $payment_id);
    
    // Update training popularity stats
    cc_training_popularity_update($training_id, 1);
    
    // Clear user's eligible trainings cache
    cc_training_access_clear_user_cache($user_id);
    
    // Send confirmation email
    cc_mailsterint_send_reg_emails($payment);
    
    // Trigger registration completed hook for other systems to respond
    do_action('cc_registration_completed', $user_id, $payment_id, $training_id);
    
    // Log the successful registration
    cc_debug_log_anything(array(
        'function' => 'cc_training_create_add_to_account_registration',
        'success' => true,
        'user_id' => $user_id,
        'training_id' => $training_id,
        'payment_id' => $payment_id,
        'training_type' => $training_type,
    ));
    
    return array(
        'success' => true,
        'message' => 'Training successfully added to your account',
        'payment_id' => $payment_id,
        'training_title' => $training_title
    );
}


/**
 * AJAX Handler for Add to Account Button
 * 
 * Processes the add-to-account request from the frontend button
 */
add_action('wp_ajax_cc_training_add_to_account', 'cc_training_add_to_account_ajax');
function cc_training_add_to_account_ajax() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'You must be logged in to add training to your account'
        ));
    }
    
    $user_id = get_current_user_id();
    
    // Verify nonce
    $training_id = isset($_POST['training_id']) ? absint($_POST['training_id']) : 0;
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    
    if (!$training_id) {
        wp_send_json_error(array(
            'message' => 'Invalid training ID'
        ));
    }
    
    if (!wp_verify_nonce($nonce, 'add_to_account_' . $training_id)) {
        wp_send_json_error(array(
            'message' => 'Security verification failed. Please refresh the page and try again.'
        ));
    }
    
    // Verify the training exists
    $training = get_post($training_id);
    if (!$training || $training->post_status !== 'publish') {
        wp_send_json_error(array(
            'message' => 'Training not found or not available'
        ));
    }
    
    // Check user eligibility
    if (!cc_training_can_add_to_account($user_id, $training_id)) {
        wp_send_json_error(array(
            'message' => 'You are not eligible to add this training to your account'
        ));
    }
    
    // Check if already registered
    if (cc_training_access_user_already_registered($user_id, $training_id)) {
        wp_send_json_error(array(
            'message' => 'You are already registered for this training'
        ));
    }
    
    // Get access details if applicable (for linked access)
    $access_details = cc_training_access_user_has_access($user_id, $training_id);
    if (!$access_details) {
        $access_details = array();
    }
    
    // Create the registration
    $result = cc_training_create_add_to_account_registration($user_id, $training_id, $access_details);
    
    if ($result['success']) {

        // Determine redirect URL based on training type
        $training_type = course_training_type($training_id);
        if ($training_type === 'recording') {
            $redirect_url = site_url('/my-account/?my=recordings');
        } else {
            $redirect_url = site_url('/my-account/?my=workshops');
        }

        wp_send_json_success(array(
            'message' => $result['message'],
            'payment_id' => $result['payment_id'],
            'training_title' => $result['training_title'],
            'redirect_url' => $redirect_url
        ));
    } else {
        wp_send_json_error(array(
            'message' => $result['message']
        ));
    }
}


/**
 * Enqueue the add-to-account JavaScript on training pages
 */
add_action('wp_enqueue_scripts', 'cc_training_enqueue_add_to_account_script');
function cc_training_enqueue_add_to_account_script() {
    // Only enqueue on single training pages
    if (!is_singular(array('course', 'workshop', 'series'))) {
        return;
    }
    
    // Check if current user can potentially use add-to-account
    // (no point loading the script if they can't)
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Quick check - do they have any of the qualifying attributes?
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    $has_subscription = false;
    
    /*
    if (function_exists('contextual_get_user_subscription')) {
        $subscription = contextual_get_user_subscription($user_id);
        $has_subscription = ($subscription && isset($subscription['has_subscription']) && $subscription['has_subscription']);
    }
    */
    
    // If they're not a portal user and don't have a subscription, they might still have linked access
    // So we'll load the script for all logged-in users (it's small)
    
    wp_enqueue_script(
        'cc-training-add-to-account',
        get_template_directory_uri() . '/js/training-add-to-account.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Localize script with ajaxurl
    wp_localize_script('cc-training-add-to-account', 'ccAddToAccount', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cc_add_to_account_action')
    ));
}