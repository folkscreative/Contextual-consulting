<?php
/**
 * Therapy contact form
 */

/* PHP Code for Shortcode */
add_shortcode('therapy_contact_form', 'therapy_contact_form_shortcode');
function therapy_contact_form_shortcode() {
    ob_start();
    ?>
    <form id="therapy-contact-form" class="container my-4 text-start" method="post">
        <div class="row">
            <div class="col-12 col-md-4 mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label for="contact-number" class="form-label">Contact number</label>
                <input type="tel" class="form-control" id="contact-number" name="contact_number" required>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="enquiry" class="form-label">Enquiry / what would you like support with</label>
            <textarea class="form-control" id="enquiry" name="enquiry" rows="4"></textarea>
        </div>

        <div class="row">
            <div class="col-12 col-md-4 mb-3">
                <label for="clinician">Clinician</label>
                <select name="clinician" id="clinician" class="form-select">
                    <option value="Any">Any</option>
                    <option value="Dr Sarah Lloyd">Dr Sarah Lloyd</option>
                    <option value="Dr Kristy Potter">Dr Kristy Potter</option>
                    <option value="Dr Marc Balint">Dr Marc Balint</option>
                    <option value="Dr Elisabeth Baker">Dr Elisabeth Baker</option>
                    <option value="Dr Natasha Avery">Dr Natasha Avery</option>
                    <option value="Dr Susie Rudge">Dr Susie Rudge</option>
                </select>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label for="funding">Funding - insurance</label>
                <select name="funding" id="funding" class="form-select">
                    <option value="Self-Funding">Self-Funding</option>
                    <option value="BUPA">BUPA</option>
                    <option value="BUPA Global">BUPA Global</option>
                    <option value="AXA">AXA</option>
                    <option value="Aviva">Aviva</option>
                    <option value="WPA">WPA</option>
                    <option value="Other Insurance">Other Insurance</option>
                </select>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label for="session_times">Preferred session times</label>
                <select class="form-select" name="session_times" required>
                    <option value="">Choose...</option>
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                    <option value="Evening">Evening</option>
                    <option value="Any">Any</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <label for="referral_source" class="form-label">How did you hear about Contextual Consulting?</label>
                <input type="text" class="form-control" id="referral_source" name="referral_source">
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <label for="more" class="form-label">Anything else we should know?</label>
                <textarea class="form-control" id="more" name="more" rows="4"></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <?php
    return ob_get_clean();
}

/* .... removed fields
        <fieldset class="mb-3">
            <legend>Clinician</legend>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="clinician1" value="Dr Sarah Lloyd">
                <label class="form-check-label" for="clinician1">Dr Sarah Lloyd</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="clinician2" value="Dr Kristy Potter">
                <label class="form-check-label" for="clinician2">Dr Kristy Potter</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="clinician3" value="Dr Marc Balint">
                <label class="form-check-label" for="clinician3">Dr Marc Balint</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="clinician4" value="Dr Elisabeth Baker">
                <label class="form-check-label" for="clinician4">Dr Elisabeth Baker</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="clinician5" value="Dr Natasha Avery">
                <label class="form-check-label" for="clinician5">Dr Natasha Avery</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="clinician6" value="Dr Susie Rudge">
                <label class="form-check-label" for="clinician6">Dr Susie Rudge</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="clinician[]" id="any-clinician" value="Any">
                <label class="form-check-label" for="any-clinician">Any</label>
            </div>
        </fieldset>
        <fieldset class="mb-3">
            <legend>Funding - insurance</legend>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="bupa" value="BUPA">
                <label class="form-check-label" for="bupa">BUPA</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="bupa-global" value="BUPA Global">
                <label class="form-check-label" for="bupa-global">BUPA Global</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="axa" value="AXA">
                <label class="form-check-label" for="axa">AXA</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="aviva" value="Aviva">
                <label class="form-check-label" for="aviva">Aviva</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="wpa" value="WPA">
                <label class="form-check-label" for="wpa">WPA</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="other-insurance" value="Other Insurance">
                <label class="form-check-label" for="other-insurance">Other Insurance</label>
            </div>
            <div class="form-check form-check-inline form-switch">
                <input class="form-check-input" type="checkbox" name="funding[]" id="self-funding" value="Self-Funding">
                <label class="form-check-label" for="self-funding">Self-Funding</label>
            </div>
        </fieldset>
*/

add_action('wp_ajax_therapy_contact_form_submit', 'therapy_contact_form_submit');
add_action('wp_ajax_nopriv_therapy_contact_form_submit', 'therapy_contact_form_submit');

function therapy_contact_form_submit() {
    global $rpm_theme_options;
    parse_str($_POST['form_data'], $form_data);

    // Format the form data into a readable string
    $formatted_data = "
        <strong>Name:</strong> {$form_data['name']}<br>
        <strong>Contact number:</strong> {$form_data['contact_number']}<br>
        <strong>Email:</strong> {$form_data['email']}<br>
        <strong>Enquiry / what would you like support with:</strong> " . stripslashes(nl2br($form_data['enquiry'])) . "<br>
        <strong>Clinician:</strong> {$form_data['clinician']}<br>
        <strong>Funding - insurance:</strong> {$form_data['funding']}<br>
        <strong>Preferred session times:</strong> {$form_data['session_times']}<br>
        <strong>How did you hear about Contextual Consulting:</strong> " . (!empty($form_data['referral_source']) ? stripslashes($form_data['referral_source']) : 'Not specified') . "<br>
        <strong>Anything else we should know:</strong> " . stripslashes(nl2br($form_data['more'])) . "<br>
    ";

    /* removed ........
        <strong>Clinician:</strong> " . (!empty($form_data['clinician']) ? implode(', ', $form_data['clinician']) : 'None selected') . "<br>
        <strong>Funding - Insurance:</strong> " . (!empty($form_data['funding']) ? implode(', ', $form_data['funding']) : 'None selected') . "<br>
    */    

    $to = $rpm_theme_options['therapy-to'];
    $subject = 'New Therapy Contact Form Submission';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $formatted_data, $headers);

    wp_send_json_success('Form submitted successfully!');
}