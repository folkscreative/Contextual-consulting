<?php
/**
 * Mailster Subscription Form with Bootstrap 5.3 and AJAX
 * Add this code to your theme's functions.php file
 */


// Enqueue necessary scripts and styles only if shortcode is present
add_action('wp_enqueue_scripts', 'mailster_form_scripts');
function mailster_form_scripts() {
    global $post;
    
    // Check if we have a post and if it contains the shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'newsletter_subscribe_form')) {
        // Enqueue custom script for form handling
        $script_path = get_template_directory() . '/js/mailster-form.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : '1.0.0';
        wp_enqueue_script(
            'mailster-form-script',
            get_template_directory_uri() . '/js/mailster-form.js',
            array('jquery'),
            $script_version,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('mailster-form-script', 'mailster_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mailster_form_nonce')
        ));
    }
}

// Register shortcode
// [newsletter_subscribe_form list="" button_text="" success_message="" form_id=""]
add_shortcode('newsletter_subscribe_form', 'mailster_subscribe_form_shortcode');
function mailster_subscribe_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'list' => 'Programme registered interest',
        'button_text' => 'Subscribe',
        'success_message' => 'Thank you, We\'ll be in touch once registration opens.',
        'form_id' => 'mailster-form-' . rand(1000, 9999)
    ), $atts);
    
    ob_start();
    ?>
    <div class="mailster-form-container">
        <form id="<?php echo esc_attr($atts['form_id']); ?>" class="mailster-subscribe-form needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="firstname_<?php echo esc_attr($atts['form_id']); ?>" 
                               name="firstname" 
                               placeholder="First Name" 
                               required>
                        <label for="firstname_<?php echo esc_attr($atts['form_id']); ?>">First Name *</label>
                        <div class="invalid-feedback">
                            Please provide your first name.
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="lastname_<?php echo esc_attr($atts['form_id']); ?>" 
                               name="lastname" 
                               placeholder="Last Name" 
                               required>
                        <label for="lastname_<?php echo esc_attr($atts['form_id']); ?>">Last Name *</label>
                        <div class="invalid-feedback">
                            Please provide your last name.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="email" 
                               class="form-control form-control-lg" 
                               id="email_<?php echo esc_attr($atts['form_id']); ?>" 
                               name="email" 
                               placeholder="your.email@example.com" 
                               required>
                        <label for="email_<?php echo esc_attr($atts['form_id']); ?>">Email Address *</label>
                        <div class="invalid-feedback">
                            Please provide a valid email address.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" 
                            class="btn btn-primary btn-lg w-100">
                        <span class="submit-text"><?php echo esc_html($atts['button_text']); ?></span>
                        <span class="loading-spinner d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
            
            <!-- Hidden fields -->
            <input type="hidden" name="action" value="newsletter_subscribe">
            <input type="hidden" name="mailster_list" value="<?php echo esc_attr($atts['list']); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mailster_form_nonce'); ?>">
            
            <!-- Messages container -->
            <div class="form-messages mt-3"></div>
        </form>
    </div>
    
    <style>
    .mailster-form-container {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-messages .alert {
        margin-bottom: 0;
    }
    
    .loading-spinner {
        display: none;
    }
    
    .form-submitting .submit-text {
        display: none;
    }
    
    .form-submitting .loading-spinner {
        display: inline-block !important;
    }
    
    .form-submitting button {
        pointer-events: none;
    }
    
    /* Bootstrap validation styling enhancements */
    .was-validated .form-control:invalid,
    .form-control.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .was-validated .form-control:valid,
    .form-control.is-valid {
        border-color: #198754;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 1.96 1.96 2.84-2.84.94.94L4.23 9.6z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    @media (max-width: 767px) {
        .mailster-form-container .col-md-6 {
            margin-bottom: 1rem;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

// Handle AJAX submission
add_action('wp_ajax_newsletter_subscribe', 'handle_newsletter_subscribe');
add_action('wp_ajax_nopriv_newsletter_subscribe', 'handle_newsletter_subscribe');
function handle_newsletter_subscribe() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mailster_form_nonce')) {
        wp_die(json_encode(array(
            'success' => false,
            'message' => 'Security verification failed.'
        )));
    }
    
    // Sanitize input data
    $firstname = sanitize_text_field($_POST['firstname']);
    $lastname = sanitize_text_field($_POST['lastname']);
    $email = sanitize_email($_POST['email']);
    $list_name = sanitize_text_field($_POST['mailster_list']);
    
    // Validate required fields
    if (empty($firstname) || empty($lastname) || empty($email)) {
        wp_send_json_error(array(
            'message' => 'Please fill in all required fields.'
        ));
    }
    
    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(array(
            'message' => 'Please enter a valid email address.'
        ));
    }
    
    // Check if Mailster is active
    if (!function_exists('mailster')) {
        wp_send_json_error(array(
            'message' => 'Mailster plugin is not active.'
        ));
    }
    
    try {
        // Prepare subscriber data (using your preferred format)
        $userdata = array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
        );
        
        // Get list ID using your helper function
        $list_id = cc_mailster_list_id_by_name($list_name);
        
        if (!$list_id) {
            wp_send_json_error(array(
                'message' => 'Unable to find or create the mailing list.'
            ));
        }
        
        // Check if subscriber already exists (using your approach)
        $subscriber = mailster('subscribers')->get_by_mail($email);
        
        if ($subscriber) {
            $subscriber_id = $subscriber->ID;
            // Update existing subscriber with new data
            $userdata['ID'] = $subscriber_id;
            mailster('subscribers')->update($userdata, true);
            $message = 'Thank you, We\'ll be in touch once registration opens.';
        } else {
            // Add new subscriber (using your approach)
            $overwrite = true;
            $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
            $message = 'Thank you, We\'ll be in touch once registration opens.';
        }
        
        // Assign the subscriber to the lists (using your approach)
        $wanted_lists = array($list_id);
        if ($subscriber_id > 0) {
            $assign_result = mailster('subscribers')->assign_lists($subscriber_id, $wanted_lists);
            
            if ($assign_result) {
                wp_send_json_success(array(
                    'message' => $message
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Subscriber added but failed to assign to list. Please contact support.'
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => 'There was an error creating your subscription. Please try again.'
            ));
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'An error occurred: ' . $e->getMessage()
        ));
    }
}

