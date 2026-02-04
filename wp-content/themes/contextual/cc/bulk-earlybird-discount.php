<?php
/**
 * Bulk On-Demand Discount Admin Page
 */

// Hook to add the admin menu
add_action('admin_menu', 'add_bulk_discount_menu');

// Hook to handle AJAX requests
add_action('wp_ajax_bulk_discount_dry_run', 'handle_bulk_discount_dry_run');
add_action('wp_ajax_bulk_discount_live_run', 'handle_bulk_discount_live_run');

/**
 * Add submenu page under Training Courses
 */
function add_bulk_discount_menu() {
    add_submenu_page(
        'edit.php?post_type=course',  // Parent slug (Training Courses)
        'Bulk on-demand discount',    // Page title
        'Bulk on-demand discount',    // Menu title
        'manage_options',             // Capability
        'bulk-discount',              // Menu slug
        'bulk_discount_page_callback' // Callback function
    );
}

/**
 * Display the admin page
 */
function bulk_discount_page_callback() {
    ?>
    <div class="wrap">
        <h1>Bulk on-demand discount</h1>

        <p>This will apply a discount to all on-demand training that is:</p>
        <ul>
            <li>Available for sale (ie published and with an Availability setting of "available" or "unlisted").</li>
            <li>And does not have a current early bird type discount running (early bird expiry date must either not be set or be in the past).</li>
        </ul>
        
        <div id="bulk-discount-form-container">
            <form id="bulk-discount-form">
                <?php wp_nonce_field('bulk_discount_action', 'bulk_discount_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="discount_percentage">Discount Percentage *</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="discount_percentage" 
                                   name="discount_percentage" 
                                   min="0.1" 
                                   max="100" 
                                   step="0.1" 
                                   required 
                                   class="regular-text" />
                            <p class="description">Enter a value between 0.1 and 100 (one decimal place allowed)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="expiry_date">Offer Expiry Date *</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="expiry_date" 
                                   name="expiry_date" 
                                   placeholder="dd/mm/yyyy" 
                                   pattern="^(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[012])/([0-9]{4})$"
                                   required 
                                   class="regular-text" />
                            <p class="description">Format: dd/mm/yyyy (must be a future date)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="discount_name">Discount Name</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="discount_name" 
                                   name="discount_name" 
                                   class="regular-text" 
                                   maxlength="255" />
                            <p class="description">Optional. If not specified, the discount will be shown as "Early bird"</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="submit-discount">
                        Preview Changes
                    </button>
                    <span id="loading-spinner" class="spinner" style="display: none; visibility: visible;"></span>
                </p>
            </form>
        </div>
        
        <div id="bulk-discount-results" style="display: none;">
            <!-- Results will be displayed here -->
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Form validation and submission
        $('#bulk-discount-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return false;
            }
            
            performDryRun();
        });
        
        function validateForm() {
            var discount = parseFloat($('#discount_percentage').val());
            var expiryDate = $('#expiry_date').val();
            var errors = [];
            
            // Validate discount percentage
            if (isNaN(discount) || discount <= 0 || discount > 100) {
                errors.push('Discount percentage must be between 0.1 and 100');
            }
            
            // Validate expiry date format and future date
            var datePattern = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/([0-9]{4})$/;
            if (!datePattern.test(expiryDate)) {
                errors.push('Expiry date must be in dd/mm/yyyy format');
            } else {
                var parts = expiryDate.split('/');
                var inputDate = new Date(parts[2], parts[1] - 1, parts[0]);
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (inputDate <= today) {
                    errors.push('Expiry date must be in the future');
                }
            }
            
            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            return true;
        }
        
        function performDryRun() {
            $('#loading-spinner').show();
            $('#submit-discount').prop('disabled', true);
            
            var formData = {
                action: 'bulk_discount_dry_run',
                bulk_discount_nonce: $('#bulk_discount_nonce').val(),
                discount_percentage: $('#discount_percentage').val(),
                expiry_date: $('#expiry_date').val(),
                discount_name: $('#discount_name').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#loading-spinner').hide();
                    $('#submit-discount').prop('disabled', false);
                    
                    if (response.success) {
                        showDryRunResults(response.data);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    $('#loading-spinner').hide();
                    $('#submit-discount').prop('disabled', false);
                    alert('An error occurred. Please try again.');
                }
            });
        }
        
        function showDryRunResults(data) {
            var html = '<div class="notice notice-info"><p><strong>Dry Run Results:</strong></p>';
            html += '<p>' + data.message + '</p>';
            
            if (data.errors && data.errors.length > 0) {
                html += '<p><strong>Errors encountered:</strong></p><ul>';
                data.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
            }
            
            if (data.courses_to_update > 0) {
                html += '<p><strong>Do you want to proceed with applying these discounts?</strong></p>';
                html += '<button type="button" class="button button-primary" id="confirm-live-run">Yes, Apply Discounts</button> ';
                html += '<button type="button" class="button" id="cancel-live-run">Cancel</button>';
            }
            
            html += '</div>';
            
            $('#bulk-discount-results').html(html).show();
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $('#bulk-discount-results').offset().top
            }, 500);
        }
        
        // Handle live run confirmation
        $(document).on('click', '#confirm-live-run', function() {
            performLiveRun();
        });
        
        $(document).on('click', '#cancel-live-run', function() {
            $('#bulk-discount-results').hide();
        });
        
        function performLiveRun() {
            $('#confirm-live-run').prop('disabled', true).text('Processing...');
            
            var formData = {
                action: 'bulk_discount_live_run',
                bulk_discount_nonce: $('#bulk_discount_nonce').val(),
                discount_percentage: $('#discount_percentage').val(),
                expiry_date: $('#expiry_date').val(),
                discount_name: $('#discount_name').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showLiveRunResults(response.data);
                    } else {
                        alert('Error: ' + response.data.message);
                        $('#confirm-live-run').prop('disabled', false).text('Yes, Apply Discounts');
                    }
                },
                error: function() {
                    alert('An error occurred during the live run. Please try again.');
                    $('#confirm-live-run').prop('disabled', false).text('Yes, Apply Discounts');
                }
            });
        }
        
        function showLiveRunResults(data) {
            var html = '<div class="notice notice-success"><p><strong>Process Complete!</strong></p>';
            html += '<p>' + data.message + '</p>';
            
            if (data.errors && data.errors.length > 0) {
                html += '<p><strong>Errors encountered:</strong></p><ul>';
                data.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
            }
            
            html += '<p><button type="button" class="button" onclick="location.reload()">Start New Bulk Discount</button></p>';
            html += '</div>';
            
            $('#bulk-discount-results').html(html);
            $('#bulk-discount-form-container').hide();
        }
    });
    </script>
    
    <style>
    #bulk-discount-results {
        margin-top: 20px;
    }
    #bulk-discount-results ul {
        margin-left: 20px;
    }
    .spinner {
        float: none;
        margin-left: 10px;
    }
    </style>
    <?php
}

/**
 * Handle dry run AJAX request
 */
function handle_bulk_discount_dry_run() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['bulk_discount_nonce'], 'bulk_discount_action')) {
        wp_die('Security check failed');
    }
    
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $discount_percentage = floatval($_POST['discount_percentage']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    $discount_name = sanitize_text_field($_POST['discount_name']);
    
    $result = process_bulk_discount($discount_percentage, $expiry_date, $discount_name, true);
    
    wp_send_json_success($result);
}

/**
 * Handle live run AJAX request
 */
function handle_bulk_discount_live_run() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['bulk_discount_nonce'], 'bulk_discount_action')) {
        wp_die('Security check failed');
    }
    
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $discount_percentage = floatval($_POST['discount_percentage']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    $discount_name = sanitize_text_field($_POST['discount_name']);
    
    $result = process_bulk_discount($discount_percentage, $expiry_date, $discount_name, false);
    
    wp_send_json_success($result);
}

/**
 * Process bulk discount (dry run or live)
 */
function process_bulk_discount($discount_percentage, $expiry_date, $discount_name, $dry_run = true) {
    global $wpdb;
    
    $errors = array();
    $courses_to_update = 0;
    $courses_updated = 0;
    
    // Convert expiry date to MySQL datetime format (23:59:59 on the specified date)
    $date_parts = explode('/', $expiry_date);
    $mysql_expiry = $date_parts[2] . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date_parts[0], 2, '0', STR_PAD_LEFT) . ' 23:59:59';
    
    // Find all eligible courses
    $courses = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'course'
        AND p.post_status = 'publish'
        AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm1 
            WHERE pm1.post_id = p.ID 
            AND pm1.meta_key = '_course_type' 
            AND pm1.meta_value = 'on-demand'
        )
        AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm2 
            WHERE pm2.post_id = p.ID 
            AND pm2.meta_key = '_course_status' 
            AND (pm2.meta_value = '' OR pm2.meta_value = 'unlisted')
        )
    "));
    
    foreach ($courses as $course) {
        // Check if course has pricing data
        $pricing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}course_pricing 
            WHERE course_id = %d
        ", $course->ID));
        
        if (!$pricing) {
            $errors[] = "Course ID {$course->ID}: {$course->post_title} missing pricing data: not updated";
            continue;
        }
        
        // Check if early bird discount is already active (expiry in future)
        if ($pricing->early_bird_expiry && strtotime($pricing->early_bird_expiry) > time()) {
            continue; // Skip this course
        }
        
        $courses_to_update++;
        
        if (!$dry_run) {
            // Update the pricing record
            $update_data = array(
                'early_bird_discount' => $discount_percentage,
                'early_bird_expiry' => $mysql_expiry
            );
            
            // Only update discount name if provided (even if empty string)
            if (isset($discount_name)) {
                $update_data['early_bird_name'] = $discount_name;
            }
            
            $updated = $wpdb->update(
                $wpdb->prefix . 'course_pricing',
                $update_data,
                array('course_id' => $course->ID),
                array('%f', '%s', '%s'),
                array('%d')
            );
            
            if ($updated !== false) {
                $courses_updated++;
            } else {
                $errors[] = "Course ID {$course->ID}: {$course->post_title} failed to update";
            }
        }
    }
    
    if ($dry_run) {
        $message = "Found {$courses_to_update} course(s) that will be updated with the {$discount_percentage}% discount.";
    } else {
        $message = "Successfully updated {$courses_updated} course(s) with the {$discount_percentage}% discount.";
    }
    
    return array(
        'message' => $message,
        'courses_to_update' => $courses_to_update,
        'courses_updated' => $courses_updated,
        'errors' => $errors
    );
}
