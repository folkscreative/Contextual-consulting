<?php
/**
 * ACT supervision form stuff
 */

// [act_supervision_form]
function act_supervision_form_shortcode() {
    ob_start();
    ?>
    <div class="py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <h4 class="mb-3" style="font-family: 'sofia-pro', sans-serif;">Register your interest in group supervision</h4>
                <p class="mb-4" style="font-family: 'europa', sans-serif;">Tell us a bit about yourself so we can match you with the right supervision group. Someone from our team will be in touch shortly.</p>

            	<form id="act-supervision-form" class="needs-validation p-4 border rounded bg-light" novalidate  style="font-family: 'europa', sans-serif;">
                    <!-- Honeypot -->
                    <div style="display:none;">
                        <input type="text" name="website" value="">
                    </div>

                    <div class="mb-3">
                        <label for="act_name" class="form-label">Name</label>
                        <input type="text" class="form-control form-control-lg" id="act_name" name="name" required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>

                    <div class="mb-3">
                        <label for="act_email" class="form-label">Email</label>
                        <input type="email" class="form-control form-control-lg" id="act_email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-3">
                        <label for="act_background" class="form-label">Professional background</label>
                        <input type="text" class="form-control form-control-lg" id="act_background" name="background" required>
                        <div class="invalid-feedback">Please tell us your professional background.</div>
                    </div>

                    <div class="mb-3">
                        <label for="act_training" class="form-label">ACT training background</label>
                        <textarea class="form-control form-control-lg" id="act_training" name="training" rows="3" required></textarea>
                        <div class="invalid-feedback">Please tell us your ACT training background.</div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="act_pathway" name="act_pathway" value="yes">
                        <label class="form-check-label" for="act_pathway">
                            I am registered on the <a href="https://contextualconsulting.co.uk/series/the-act-pathway" target="_blank">ACT Pathway Training Series</a>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label for="act_slot" class="form-label">Preferred slot</label>
                        <select class="form-select form-select-lg" id="act_slot" name="slot" required>
                            <option value="">Select a group</option>
                            <option value="Any">Any</option>
                            <option value="Group 1">Group 1</option>
                            <option value="Group 2">Group 2</option>
                            <option value="Group 3">Group 3</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please choose a slot or 'other'.</div>
                    </div>

                    <div class="mb-3 d-none" id="other_slot_container">
                        <label for="act_other_slot" class="form-label">Preferred times/days of the week</label>
                        <input type="text" class="form-control form-control-lg" id="act_other_slot" name="other_slot">
                        <div class="invalid-feedback">Please tell us more here or choose a slot above.</div>
                    </div>

                    <div class="mb-3">
                        <label for="where_hear" class="form-label">Where did you hear about group supervision?</label>
                        <input type="text" class="form-control form-control-lg" id="where_hear" name="where_hear" required>
                        <div class="invalid-feedback">Please tell us where you heard about this supervision.</div>
                    </div>

                    <?php /*
                    <div class="mb-3">
                        <label for="how_hear_about_us" class="form-label">How did you hear about Contextual Consulting?</label>
                        <input type="text" class="form-control form-control-lg" id="how_hear_about_us" name="how_hear_about_us" required>
                        <div class="invalid-feedback">Please tell us how you heard about us.</div>
                    </div>
                    */ ?>

                    <div class="mb-3">
                        <label for="act_message" class="form-label">Is there anything else you'd like us to consider when matching you with a group?</label>
                        <textarea class="form-control form-control-lg" id="act_message" name="message" rows="4"></textarea>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="consent" required>
                        <label class="form-check-label" for="consent">
                            I consent to Contextual Consulting storing and processing my data in accordance with the <a href="/privacy-policy" target="_blank">privacy policy</a>.
                        </label>
                        <div class="invalid-feedback">We need your consent to be able to contact you about this.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Submit</button>
                    </div>

                    <div id="form-response" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>

	<script>
	jQuery(document).ready(function($) {
	    const form = $('#act-supervision-form');
	    const submitBtn = form.find('button[type="submit"]');
	    const originalBtnText = submitBtn.html();

	    $('#act_slot').on('change', function() {
	        if ($(this).val() === 'Other') {
	            $('#other_slot_container').removeClass('d-none').hide().fadeIn();
	        } else {
	            $('#other_slot_container').fadeOut(300, function () {
	                $(this).addClass('d-none');
	            });
	        }
	    });

	    form.on('submit', function(e) {
	        if (!this.checkValidity()) {
	            e.preventDefault();
	            e.stopPropagation();
	            $(this).addClass('was-validated');

	            // Show error message if fields are incomplete
	            $('#form-response').html(`
	                <div class="alert alert-danger bg-danger text-white mt-3" role="alert">
	                    Please complete all required fields before submitting the form.
	                </div>
	            `);
	            return;
	        }

	        e.preventDefault();

	        // Clear any previous messages
	        $('#form-response').html('');

	        // Honeypot check
	        if ($('input[name="website"]').val().length !== 0) {
	            return;
	        }

	        // Disable button and show spinner
	        submitBtn.html('<i class="fa-solid fa-spinner fa-spin"></i>');
	        submitBtn.prop('disabled', true);

	        var formData = form.serialize();
	        $.ajax({
	            url: '<?php echo admin_url("admin-ajax.php"); ?>',
	            type: 'POST',
	            data: formData + '&action=act_supervision_form_submit',
	            success: function(response) {
	                // $('html, body').animate({ scrollTop: form.offset().top }, 500);
	                $('#form-response').html(`
	                    <div class="alert alert-success mt-3">
	                        Thanks for signing up! We'll be in touch soon to help match you with the right supervision group.
	                    </div>
	                `);
	                form[0].reset();
	                form.removeClass('was-validated');
	                $('#other_slot_container').addClass('d-none');
	            },
	            error: function() {
	                $('#form-response').html(`
	                    <div class="alert alert-danger mt-3">
	                        Oops! Something went wrong. Please try again or contact us directly.
	                    </div>
	                `);
	            },
	            complete: function() {
	                // Restore button and text
	                submitBtn.html(originalBtnText);
	                submitBtn.prop('disabled', false);
	            }
	        });
	    });
	});
	</script>

    <?php
    return ob_get_clean();
}
add_shortcode('act_supervision_form', 'act_supervision_form_shortcode');

function act_supervision_form_submit() {
    // Honeypot check
    if (!empty($_POST['website'])) {
        wp_die(); // Bot submission, silently fail
    }

    $name        = stripslashes( sanitize_text_field($_POST['name']) );
    $email       = sanitize_email($_POST['email']);
    $background  = clean_textarea( stripslashes( sanitize_text_field($_POST['background']) ) );
    $training    = clean_textarea( stripslashes( sanitize_textarea_field($_POST['training']) ) );
    $act_pathway = isset($_POST['act_pathway']) && $_POST['act_pathway'] === 'yes' ? 'Yes' : 'No';
    $slot        = sanitize_text_field($_POST['slot']);
    $other_slot  = isset($_POST['other_slot']) ? clean_textarea( stripslashes( sanitize_text_field($_POST['other_slot']) ) ) : '';
    $message     = clean_textarea( stripslashes( sanitize_textarea_field($_POST['message']) ) );
    $where_hear  = sanitize_text_field($_POST['where_hear']);
    // $how_hear_about_us = sanitize_text_field($_POST['how_hear_about_us']);

    if( LIVE_SITE ){
    	$admin_email = 'kristy.potter@contextualconsulting.co.uk';
    }else{
    	$admin_email = get_option('admin_email');
    }

    /*
    $body_admin = "New ACT Group Supervision Registration:\n\n";
    $body_admin .= "Name: $name\nEmail: $email\nProfessional background: $background\nACT training: $training\nPreferred Slot: $slot\n";
    if ($slot === 'Other') {
        $body_admin .= "Preferred Time/Days: $other_slot\n";
    }
    $body_admin .= "Additional Info:\n$message";

    wp_mail($admin_email, 'New ACT Supervision Registration', $body_admin, ['Content-Type: text/plain; charset=UTF-8']);
    */

	// Add timestamp
	$timestamp = date('Y-m-d H:i:s');

	/*
	// Headers
	$headers_admin = ['Content-Type: text/plain; charset=UTF-8'];

	// Header and row for easy Excel pasting
	$header_row = "Timestamp\tName\tEmail\tProfessional Background\tACT Training Background\tPreferred Slot\tOther Slot\tAdditional Notes";
	$data_row = implode("\t", [
	    $timestamp,
	    $name,
	    $email,
	    $background,
	    $training,
	    $slot,
	    $other_slot,
	    $message,
	]);

	$message_admin = $header_row . "\n" . $data_row;

	wp_mail($admin_email, 'New ACT Supervision Registration', $message_admin, $headers_admin);
	*/

	// Format for filename
	$filename_date = current_time('Y-m-d-H-i');
	$filename = "act-submission-{$filename_date}.csv";

	// Create CSV content in memory
	$csv_headers = [
	    'Timestamp',
	    'Name',
	    'Email',
	    'Professional Background',
	    'ACT Training Background',
	    'Registered on ACT Pathway',
	    'Preferred Slot',
	    'Other Slot (if selected)',
	    'Where Heard About Supervision',
	    // 'How Heard About Contextual Consulting',
	    'Additional Notes'
	];

	$csv_data = [
	    $timestamp,
	    $name,
	    $email,
	    $background,
	    $training,
	    $act_pathway,
	    $slot,
	    $other_slot,
	    $where_hear,
	    // $how_hear_about_us,
	    $message
	];

	// Create CSV in uploads directory
	$upload_dir = wp_upload_dir();
	$csv_path = $upload_dir['basedir'] . '/' . $filename;

	$csv_file = fopen($csv_path, 'w');
	fputcsv($csv_file, $csv_headers);
	fputcsv($csv_file, $csv_data);
	fclose($csv_file);

	// Send admin email with CSV attachment
	$admin_subject = 'New ACT Supervision Form Submission';
	$admin_message = "A new ACT supervision form has been submitted.\n\nDetails are attached as a CSV.";
	$headers = ['Content-Type: text/plain; charset=UTF-8'];

	wp_mail($admin_email, $admin_subject, $admin_message, $headers, [$csv_path]);

	// Delete file after sending
	unlink($csv_path);

	// Send confirmation to user
	$user_subject = 'Thanks for your ACT supervision form submission';
    $user_msg = "Hi $name,\n\nThanks for signing up for our ACT supervision group. We've received your details and will be in touch soon to help match you with the most suitable group.\n\nIf you have any urgent questions, you can always reach us at admin@contextualconsulting.co.uk.\n\nRegards,\n\nThe Contextual Consulting Team";
    wp_mail($email, 'Thanks for signing up – Contextual Consulting', $user_msg, ['Content-Type: text/plain; charset=UTF-8']);

    wp_send_json_success();
}
add_action('wp_ajax_act_supervision_form_submit', 'act_supervision_form_submit');
add_action('wp_ajax_nopriv_act_supervision_form_submit', 'act_supervision_form_submit');

// Helper to strip newlines
function clean_textarea($text) {
    return trim(preg_replace('/\r\n|\r|\n/', ' ', sanitize_textarea_field($text)));
}

