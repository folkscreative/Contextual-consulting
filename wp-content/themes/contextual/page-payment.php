<?php
/**
 * Template Name: Payment Page
 * Updated for Phase 1 - Token-based system only
 */

global $rpm_theme_options;

// ccpa_write_log( 'page-payment.php' );
// ccpa_write_log($_REQUEST);

/*
// At the top of page-payment.php
function is_final_payment_back_button() {
    if (isset($_GET['token'])) {
        $registration_data = TempRegistration::get($_GET['token']);
        
        if ($registration_data) {
            $current_step = (int)$registration_data->current_step;
            
            // We're on step 3 page
            // Only allow if current_step is exactly 3
            if ($current_step != 3) {
                // Either going backwards or skipping steps
                return true;
            }
            
            // Check if payment already processed
            if ($registration_data->status === 'payment_completed' || 
                $registration_data->status === 'completed') {
                return true;
            }
            
            // Check for payment gateway returns (back button from PayPal/Stripe)
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referrer, 'stripe.com') !== false) {
                return true;
            }
        }
    }
    
    return false;
}

// Check before any other processing
if (is_final_payment_back_button()) {
    TempRegistration::delete($_GET['token']);
    wp_redirect(home_url('/act-therapy-training'));
    exit;
}
*/



// Initialize variables using token system
$current_token = handle_registration_page_load();
$registration_data = TempRegistration::get($current_token);
$form_data = json_decode($registration_data->form_data, true);
$current_step = $registration_data->current_step;

$user_id = $registration_data->user_id;

// are we ok to process this token?
if( $registration_data->status <> 'active' ){
    // ccpa_write_log('payment page found token '.$current_token.' to be '.$registration_data->status.'. Redirecting the user to the training page ....');
    wp_redirect(home_url('/act-therapy-training'));
    exit;
}

// ccpa_write_log('current token:'.$current_token);
// ccpa_write_log('form_data:');
// ccpa_write_log($form_data);
// ccpa_write_log('current step:'.$current_step);

// error_log('VAT exempt from form_data: ' . ($form_data['vat_exempt'] ?? 'not set'));
// error_log('VAT exempt being used: ' . $vat_exempt);

// Basic validation
if(empty($form_data['training_type']) || 
   empty($form_data['training_id']) || 
   empty($form_data['currency'])) {
    wp_redirect(home_url());
    exit;
}

// Extract variables from temp registration data
$training_type = $form_data['training_type'];
$training_id = $form_data['training_id'];
$eventID = $form_data['event_id'] ?? '';
$currency = $form_data['currency'];
// $raw_price = (float) ($form_data['raw_price'] ?? 0);
$student = $form_data['student'] ?? 'no';
$vat_exempt = $form_data['vat_exempt'] ?? 'n';
// $vat_amount = (float) ($form_data['vat_amount'] ?? 0);
// $disc_code = $form_data['payment_details']['disc_code'] ?? '';
$disc_code = $form_data['disc_code'] ?? '';
// $disc_amount = (float) ($form_data['payment_details']['disc_amount'] ?? 0);
// $voucher_code = $form_data['payment_details']['voucher_code'] ?? '';
$voucher_code = $form_data['voucher_code'] ?? '';
// $voucher_amount = (float) ($form_data['payment_details']['voucher_amount'] ?? 0);
// $tot_pay = (float) ($form_data['payment_details']['tot_pay'] ?? $raw_price);
// $total_payable = (float) ($form_data['payment_details']['total_payable'] ?? $raw_price);
$user_timezone = $form_data['user_timezone'] ?? '';
$user_prettytime = $form_data['user_prettytime'] ?? '';
$vat_uk = $form_data['vat_uk'] ?? '';
$vat_employ = $form_data['vat_employ'] ?? '';
$vat_employer = $form_data['vat_employer'] ?? '';
$upsell_workshop_id = (int) ($form_data['upsell_workshop_id'] ?? 0);
$source = $form_data['source'] ?? '';
$mailing_list = $form_data['mailing_list'] ?? '';
$conf_email = $form_data['conf_email'] ?? '';

// Handle group training data
$group_training = array();
$series_discount = 0;

if( $training_type == 'g' ){
    $group_training = $form_data['group_training'] ?? [];
    $group_training = array_map('absint', (array) $group_training);
    $group_training = array_filter($group_training);
    $group_training = array_unique($group_training);
} elseif( $training_type == 's' ) {
    $series_discount = (float) ($form_data['series_discount'] ?? 0);
}

// how many attendees - updated for standardized format
$num_attendees = 1; // default fallback

if (isset($form_data['attendees']) && is_array($form_data['attendees'])) {
    // New standardized format - attendees is an array of attendee objects
    $num_attendees = count($form_data['attendees']);
    // ccpa_write_log("Attendee count from standardized format: " . $num_attendees);
} elseif (isset($form_data['attendees']['attend_type'])) {
    // Fallback for old format (during transition)
    if ($form_data['attendees']['attend_type'] == 'group' && isset($form_data['attendees']['attend_email'])) {
        $num_attendees = count($form_data['attendees']['attend_email']);
        // ccpa_write_log("Attendee count from legacy format: " . $num_attendees);
    }
}

// ccpa_write_log("Final num_attendees: " . $num_attendees);

// ===== RECALCULATE PRICING ON LOAD =====
// This ensures we always have the correct, current pricing

$raw_price = 0;
$disc_amount = 0;
$voucher_amount = 0;
$vat_amount = 0;
$tot_pay = 0;
$total_payable = 0;

if ($training_type == 'g' && !empty($group_training)) {
    // Group training pricing - EXACT match to registration.php
    $currency_lc = strtolower($currency);
    $now = time();
    $training_price = 0;
    $discounts = array();
    $total_discount = 0;
    
    foreach ($group_training as $grp_train_id) {
        $pricing = get_training_prices($grp_train_id);
        $training_price += $pricing['price_'.$currency_lc];
        
        // Early bird discounts
        if ($pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] != '') {
            $expiry_date = DateTime::createFromFormat('Y-m-d H:i:s', $pricing['early_bird_expiry']);
            if ($expiry_date && $expiry_date->getTimestamp() > $now) {
                $early_bird_name = ($pricing['early_bird_name'] == '') ? 'Early bird' : $pricing['early_bird_name'];
                $discount_amount = round($pricing['price_'.$currency_lc] * $pricing['early_bird_discount'] / 100, 2);
                $total_discount += $discount_amount;
                if (isset($discounts[$early_bird_name])) {
                    $discounts[$early_bird_name] += $discount_amount;
                } else {
                    $discounts[$early_bird_name] = $discount_amount;
                }
            }
        }
    }
    
    // CRITICAL: Match registration.php exactly
    $raw_price = $training_price;
    $disc_amount = $total_discount;
    
    // This MUST match the registration.php calculation:
    // $discounted_price = $training_price - $total_discount;
    // $vat = ( $vat_exempt == 'y' ) ? 0 : ccpa_vat_amount( $discounted_price );
    
    /*
    $discounted_price = $training_price - $total_discount;
    if ($vat_exempt != 'y') {
        $vat_amount = ccpa_vat_amount($discounted_price); // VAT on discounted price per attendee
    } else {
        $vat_amount = 0;
    }
    
    // Per attendee total
    $tot_pay = $discounted_price + $vat_amount;
    
    // Total for all attendees
    $total_payable = $tot_pay * $num_attendees;
    */
    
    // Debug the exact calculation
    // ccpa_write_log('Group training calculation debug:');
    // ccpa_write_log("Raw price: €{$raw_price}");
    // ccpa_write_log("Discount: €{$disc_amount}");
    // ccpa_write_log("Discounted price: €{$discounted_price}");
    // ccpa_write_log("VAT (20% of discounted): €{$vat_amount}");
    // ccpa_write_log("Per attendee total: €{$tot_pay}");
    // ccpa_write_log("Number of attendees: {$num_attendees}");
    // ccpa_write_log("Total payable: €{$total_payable}");
    // ccpa_write_log("Stripe charge amount (cents): " . round($total_payable * 100, 0));
        
} else {
    // Single workshop, recording, or series pricing (existing logic remains the same)
    if ($training_type == 'w') {
        $student_flag = ($student == 'yes') ? 'yes' : '';
        $result = cc_workshop_price_exact($training_id, $currency, $eventID, $student_flag);
    } elseif ($training_type == 'r') {
        $result = cc_recording_price($training_id, $currency);
    } elseif ($training_type == 's') {
        $result = cc_series_pricing($training_id, $currency);
        $series_discount = $result['saving'] ?? 0;
    }
    
    $raw_price = $result['raw_price'] ?? 0;
    $currency = $result['curr_found'] ?? $currency;

    if( $training_type <> 'w' && $student == 'yes' && isset( $result['student_price'] ) && $result['student_price'] > 0 ){
        $raw_price = $result['student_price'];
    }

    /*
    ccpa_write_log([
        'step' => 'after get pricing',
        'result' => $result,
        'raw_price' => $raw_price,
        'currency' => $currency,
    ]);
    */

    // IMPORTANT: Handle upsell pricing if selected
    if ($upsell_workshop_id > 0 && ($training_type == 'w' || $training_type == 'r')) {
        $upsell = cc_upsells_get_upsell($training_id);
        if ($upsell) {
            // Get pricing for the upsell workshop/recording
            if (course_training_type($upsell_workshop_id) == 'workshop') {
                $upsell_pricing = cc_workshop_price_exact($upsell_workshop_id, $currency, '', $student);
            } else {
                $upsell_pricing = cc_recording_price($upsell_workshop_id, $currency);
            }
            
            if ($upsell_pricing && isset($upsell_pricing['raw_price'])) {
                // Calculate combined price with upsell discount
                $combined_price = $raw_price + $upsell_pricing['raw_price'];
                $raw_price = round($combined_price - ($combined_price * $upsell['discount'] / 100), 2);
            }
        }
    }

    /*
    ccpa_write_log([
        'step' => 'after upsell',
        'upsell_workshop_id' => $upsell_workshop_id,
        'raw_price' => $raw_price,
    ]);
    */

    // Apply discount codes (if any)
    if (!empty($disc_code) && $training_type != 's') { // Series has its own discount
        if( substr( $disc_code, 0, 3) == 'CC-' ){
            if( cc_friend_can_i_use_it( $disc_code, $user_id ) ){
                $disc_percent = get_option('refer_friend_percent', 0);
                $disc_amount = round( $raw_price * $disc_percent * 0.01, 2 );
            }
        }else{
            $discount = cc_discount_lookup($disc_code, $training_type, $training_id);
            if ($discount) {
                if ($discount['disc_type'] == 'a') {
                    $disc_amount = min($discount['disc_amount'], $raw_price);
                } elseif ($discount['disc_type'] == 'p') {
                    $disc_amount = round($raw_price * $discount['disc_amount'] * 0.01, 2);
                }
            }
        }
    }

    /*
    ccpa_write_log([
        'step' => 'after discount',
        'discount' => $discount,
        'disc_amount' => $disc_amount,
    ]);
    */

    if( $training_type == 's' ){
        $disc_amount = $series_discount;
    }
}

// Calculate VAT on discounted price
$discounted_price = $raw_price - $disc_amount;
if ($vat_exempt != 'y') {
    $vat_amount = ccpa_vat_amount($discounted_price);
} else {
    $vat_amount = 0;
}

/*
ccpa_write_log([
    'step' => 'after vat',
    'discounted_price' => $discounted_price,
    'raw_price' => $raw_price,
    'disc_amount' => $disc_amount,
    'vat_amount' => $vat_amount,
]);
*/

// Per attendee total
$tot_pay = $discounted_price + $vat_amount;

// Total for all attendees
$total_payable = $tot_pay * $num_attendees;

/*
ccpa_write_log([
    'step' => 'after totals',
    'tot_pay' => $tot_pay,
    'num_attendees' => $num_attendees,
    'total_payable' => $total_payable,
]);
*/

// Apply vouchers
if (!empty($voucher_code)) {
    $voucher_bal = 0;
    
    // Check if it's a refer-a-friend code
    if (substr($voucher_code, 0, 3) == 'CC-') {
        if ($voucher_code == cc_friend_user_code(get_current_user_id())) {
            $usage = cc_friend_get_usage('raf_code', $voucher_code);
            if ($usage['balance'] > 0) {
                $voucher_bal = cc_voucher_core_curr_convert($usage['currency'], $currency, $usage['balance']);
                $voucher_bal = (float) str_replace(',', '', $voucher_bal);
            }
        }
    } else {
        // Regular voucher
        $raw_voucher_code = cc_voucher_code_raw( $voucher_code );
        $voucher_dets = ccpa_voucher_table_get( $raw_voucher_code );
        if ($voucher_dets) {
            $voucher_bal = ccpa_voucher_usage_balance($voucher_dets, $currency);
            $voucher_bal = (float) str_replace(',', '', $voucher_bal);
        }
    }
    
    if ($voucher_bal > 0) {
        $voucher_amount = min($voucher_bal, $total_payable);
        $total_payable -= $voucher_amount;
    }
}

/*
ccpa_write_log([
    'step' => 'after vouchers',
    'voucher_code' => $voucher_code,
    'total_payable' => $total_payable,
]);
*/

/*
// Update the form data with recalculated pricing
$updated_payment_details = array_merge(
    $form_data['payment_details'] ?? $form_data,
    array(
        'raw_price' => $raw_price,
        'disc_amount' => $disc_amount,
        'voucher_amount' => $voucher_amount,
        'vat_amount' => $vat_amount,
        'tot_pay' => $tot_pay,
        'total_payable' => $total_payable,
        'currency' => $currency,
    )
);

$form_data['payment_details'] = $updated_payment_details;

// Save the updated pricing back to the temp registration
TempRegistration::update_form_data($current_token, $current_step, array(
    'payment_details' => $updated_payment_details
));
*/

// ccpa_write_log('Recalculated pricing:');
// ccpa_write_log($updated_payment_details);

// Extract final values for use in the template
$raw_price = (float) $raw_price;
$vat_amount = (float) $vat_amount;
$disc_amount = (float) $disc_amount;
$voucher_amount = (float) $voucher_amount;
$tot_pay = (float) $tot_pay;
$total_payable = (float) $total_payable;

/*
ccpa_write_log([
    'step' => 'after floats',
    'raw_price' => $raw_price,
    'vat_amount' => $vat_amount,
    'disc_amount' => $disc_amount,
    'voucher_amount' => $voucher_amount,
    'tot_pay' => $tot_pay,
    'total_payable' => $total_payable,
]);
*/

// Complete fix for page-payment.php attendee data extraction around line 266
// Get attendee information from form data
$attend_first = '';
$attend_last = '';
$attend_email = '';

if (isset($form_data['attendees'])) {
    // NEW STANDARDIZED FORMAT - Check if attendees is an array of attendee objects
    if (is_array($form_data['attendees']) && !empty($form_data['attendees'])) {
        // Find the primary attendee (registrant first, or first valid attendee)
        $primary_attendee = null;
        
        // Look for registrant first
        foreach ($form_data['attendees'] as $attendee) {
            if (isset($attendee['registrant']) && $attendee['registrant'] === 'r' && !empty($attendee['email'])) {
                $primary_attendee = $attendee;
                break;
            }
        }
        
        // If no registrant found or registrant has no email, use the first attendee with valid data
        if (!$primary_attendee) {
            foreach ($form_data['attendees'] as $attendee) {
                if (!empty($attendee['email']) && !empty($attendee['firstname'])) {
                    $primary_attendee = $attendee;
                    break;
                }
            }
        }
        
        // Extract the data from the primary attendee
        if ($primary_attendee) {
            $attend_first = $primary_attendee['firstname'] ?? '';
            $attend_last = $primary_attendee['lastname'] ?? '';
            $attend_email = $primary_attendee['email'] ?? '';
        }
        
        // ccpa_write_log('Using standardized attendee format - Primary attendee: ' . $attend_email);
        
    } 
    // LEGACY FORMAT - Check for old attend_type structure
    elseif (isset($form_data['attendees']['attend_type'])) {
        if ($form_data['attendees']['attend_type'] == 'single') {
            $attend_first = $form_data['attendees']['attend_first_reg'] ?? '';
            $attend_last = $form_data['attendees']['attend_last_reg'] ?? '';
            $attend_email = $form_data['attendees']['attend_email_reg'] ?? '';
        } else {
            // For group attendees, use the first attendee for main details
            $attend_first = $form_data['attendees']['attend_first'][0] ?? '';
            $attend_last = $form_data['attendees']['attend_last'][0] ?? '';
            $attend_email = $form_data['attendees']['attend_email'][0] ?? '';
        }
        
        // ccpa_write_log('Using legacy attendee format - Type: ' . $form_data['attendees']['attend_type']);
    }
    else {
        // ccpa_write_log('WARNING: Attendees data found but in unexpected format');
        // ccpa_write_log('Attendees structure: ' . json_encode($form_data['attendees']));
    }
} 

// FALLBACK - Check for attend_type at root level for backward compatibility
elseif (isset($form_data['attend_type'])) {
    // ccpa_write_log('Found attend_type at root level: ' . $form_data['attend_type']);
    // This handles cases where attend_type was saved separately
}

else {
    // ccpa_write_log('WARNING: No attendees data found in form_data at all');
}

// If we still don't have attendee data, try to get it from current user
if (empty($attend_email) || empty($attend_first)) {
    // ccpa_write_log('Attempting to get attendee data from current user as fallback');
    $current_user = wp_get_current_user();
    if ($current_user->ID > 0) {
        if (empty($attend_first)) {
            $attend_first = get_user_meta($current_user->ID, 'first_name', true);
        }
        if (empty($attend_last)) {
            $attend_last = get_user_meta($current_user->ID, 'last_name', true);
        }
        if (empty($attend_email)) {
            $attend_email = $current_user->user_email;
        }
        // ccpa_write_log('Used current user data as fallback: ' . $attend_email);
    }
}

// ccpa_write_log('Final extracted attendee info - Name: ' . $attend_first . ' ' . $attend_last . ', Email: ' . $attend_email);




/*
if (isset($form_data['attendees'])) {
    if ($form_data['attendees']['attend_type'] == 'single') {
        $attend_first = $form_data['attendees']['attend_first_reg'] ?? '';
        $attend_last = $form_data['attendees']['attend_last_reg'] ?? '';
        $attend_email = $form_data['attendees']['attend_email_reg'] ?? '';
    } else {
        // For group attendees, we'll use the first attendee for main details
        $attend_first = $form_data['attendees']['attend_first'][0] ?? '';
        $attend_last = $form_data['attendees']['attend_last'][0] ?? '';
        $attend_email = $form_data['attendees']['attend_email'][0] ?? '';
    }
}
*/

// Check for duplicate registration using token
$attendee_id = 0;
if($attend_email <> ''){
    $attendee = cc_users_get_user($attend_email);
    if($attendee){
        $attendee_id = $attendee->ID;
    }
}

/* now checking the token status on page load
if( cc_paymentdb_dejavu_token( get_current_user_id(), $training_id, $current_token) ){
    wp_redirect( home_url() );
    exit;
}
*/

/*
ccpa_write_log([
    'page' => 'page-payment.php',
    'tot_pay' => $tot_pay,
    'voucher_code' => $voucher_code,
    'voucher_amount' => $voucher_amount,
    'total_payable' => $total_payable,
    'discounted_price' => $discounted_price,
    'vat_amount' => $vat_amount,
    'raw_price' => $raw_price,
    'disc_amount' => $disc_amount,
]);
*/

// ccpa_write_log($form_data);

// update the temp reg with the final amounts
$payment_updates = array(
    'total_payable' => $total_payable,
    'vat_amount' => $vat_amount, // per attendee
);
$success = TempRegistration::update_form_data( $current_token, '3', $payment_updates);

// Check if this is free and should go straight to confirmation
if( $total_payable == 0 ){
    // Process free registration immediately
    cc_registration_free_confirmation_with_token($current_token);
    exit;
}


// Determine if invoice is allowed
$invoice_allowed = ccpa_invoice_payment_possible_for( 'id', get_current_user_id() );

// let's create a Stripe payment intent
$charge_amount = round(($total_payable * 100), 0);

/*
ccpa_write_log([
    'step' => 'after attendees',
    'charge_amount' => $charge_amount,
    'training_type' => $training_type,
]);
*/

if($training_type == 'g' && !empty($group_training)){
    $course_titles = array();
    foreach($group_training as $course_id){
        $course_titles[] = get_the_title($course_id);
    }
    
    $args = array(
        'amount' => $charge_amount,
        'currency' => strtolower($currency),
        'metadata' => array(
            'training_type' => 'group',
            'courses' => implode(', ', $course_titles),
            'course_count' => count($group_training),
            'course_ids' => implode(',', $group_training),
            'series_id' => $training_id // The series that contains these courses
        ),
    );
} else {
    // Existing logic for other training types
    if($training_type == 'w'){
        $key = 'workshop';
    } elseif($training_type == 's'){
        $key = 'series';
    } else {
        $key = 'recording';
    }
    
    $args = array(
        'amount' => $charge_amount,
        'currency' => strtolower($currency),
        'metadata' => array(
            $key => get_the_title($training_id),
        ),
    );
}

$intent = cc_stripe_pmt_intent_create_pi( $args );

if( $intent['status'] == 'error' ){
    // redirect back to the training page
    $training_url = get_permalink( $training_id );
    wp_redirect( $training_url );
    exit;
}

// and save info about it
$client_secret = array(
    'client_secret' => $intent->client_secret,
    'payment_intent_id' => $intent->id,
    'status' => 'new',
    'type' => $training_type == 'g' ? 'group' : ($training_type == 'w' ? 'workshop' : ($training_type == 's' ? 'series' : 'recording')),
    'type_id' => 0,
    'counter' => 0,
);
ccpa_payint_client_secret_add( $client_secret );

/*
$intent_response = cc_stripe_payment_intent( $total_payable, $currency );
$client_secret = $intent_response['client_secret'];
$stripe_amount = $intent_response['amount'];
*/

get_header();
while ( have_posts() ) : the_post(); ?>

<div class="wms-sect-page-head">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>
            </div>
        </div>
    </div>
</div>

<div class="wms-section">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-4 order-md-2">
                <div class="animated-card">
                    <div id="reg-train-panel" class="reg-train-panel reg-panel wms-background animated-card-inner closed dark-bg">
                        <div class="row">
                            <div class="col-11">
                                <h3>Your training:</h3>
                            </div>
                            <div id="reg-train-closer" class="col-1 text-end d-md-none reg-train-closer">
                                <span class="closed"><i class="fa-solid fa-angle-right"></i></span>
                                <span class="open"><i class="fa-solid fa-angle-down"></i></span>
                            </div>
                        </div>
                        <div class="reg-train-dets">
                            <div class="row">
                                <div class="col-12">
                                    <?php
                                    // Calculate discount and voucher amounts

                                    /*
                                    ccpa_write_log([
                                        'step' => 'before replacement',
                                        'disc_amount' => $disc_amount,
                                        'voucher_amount' => $voucher_amount,
                                    ]);
                                    */

                                    // $disc_amount = (float) ($form_data['payment_details']['disc_amount'] ?? 0);
                                    // $voucher_amount = (float) ($form_data['payment_details']['voucher_amount'] ?? 0);
                                    $earlybird = cc_workshop_price_earlybird($form_data['training_id']);

                                    /*
                                    ccpa_write_log([
                                        'step' => 'after replacement',
                                        'disc_amount' => $disc_amount,
                                        'voucher_amount' => $voucher_amount,
                                        'earlybird' => $earlybird,
                                        'form_data_training_type' => $form_data['training_type'],
                                        'form_data_training_id' => $form_data['training_id'],
                                        'form_data_event_id' => $form_data['event_id'],
                                        'form_data_user_timezone' => $form_data['user_timezone'],
                                        'raw_price' => $raw_price,
                                        'series_discount' => $series_discount,
                                        'form_data_currency' => $form_data['currency'],
                                        'form_data_student' => $form_data['student'],
                                        'cc_workshop_price_earlybird' => cc_workshop_price_earlybird( $training_id ),
                                        'vat_exempt' => $vat_exempt,
                                        'form_data_upsell_workshop_id' => $form_data['upsell_workshop_id'],
                                        'num_attendees' => $num_attendees,
                                        'group_training' => $group_training,
                                    ]);
                                    */

                                    if( $form_data['student'] == 'yes' ){
                                        $price_to_use = $form_data['student_price'];
                                    }else{
                                        $price_to_use = $raw_price - $series_discount;
                                    }

                                    
                                    echo cc_registration_training_panel( 
                                        $form_data['training_type'], 
                                        $form_data['training_id'], 
                                        $form_data['event_id'] ?? '', 
                                        $form_data['user_timezone'] ?? '', 
                                        $price_to_use, 
                                        $form_data['currency'], 
                                        $form_data['student'] ?? 'no', 
                                        cc_workshop_price_earlybird( $training_id ), 
                                        $disc_amount, 
                                        $voucher_amount, 
                                        $vat_exempt, 
                                        $form_data['upsell_workshop_id'] ?? 0, 
                                        'payment', 
                                        $num_attendees, 
                                        $group_training
                                    );
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div  id="reg-pmt-det-col" class="col-12 col-md-8 order-md-1">

                <?php // for the timezone changer ... ?>
                <input type="hidden" id="user-timezone" value="<?php echo $user_timezone; ?>">
                <input type="hidden" id="user-prettytime" value="<?php echo $user_prettytime; ?>">

                <?php if($total_payable > 0) { ?>

                        <form action="" method="post" id="reg-pay-dets-form" class="" novalidate>
                            <input type="hidden" name="action" value="record_payment">
                            <input type="hidden" id="token" name="token" value="<?php echo $current_token; ?>">
                            <?php /*
                            <input type="hidden" id="ccreg" name="ccreg" value="<?php echo $ccreg; ?>">
                            */ ?>
                            <input type="hidden" id="stripe-public" value="<?php echo cc_get_stripe_key('public'); ?>">
                            <input type="hidden" id="client-secret" name="client-secret" value="<?php echo $intent->client_secret; ?>">

                            <input type="hidden" id="training-type" name="training-type" value="<?php echo $training_type; ?>">
                            <input type="hidden" id="training-id" name="training-id" value="<?php echo $training_id; ?>">
                            <?php /*
                            <input type="hidden" id="event-id" name="event-id" value="<?php echo $eventID; ?>">
                            <input type="hidden" id="currency" name="currency" value="<?php echo $currency; ?>">
                            <input type="hidden" id="raw-price" name="raw-price" value="<?php echo $raw_price; ?>">
                            <input type="hidden" id="tot_pay" name="tot_pay" value="<?php echo $tot_pay; ?>">
                            <input type="hidden" id="total_payable" name="total_payable" value="<?php echo $total_payable; ?>">
                            <input type="hidden" id="user-timezone" name="user-timezone" value="<?php echo $timezone; ?>">
                            <input type="hidden" name="student" value="<?php echo $student; ?>">
                            <input type="hidden" name="vat_exempt" value="<?php echo $vat_exempt; ?>">
                            <input type="hidden" name="vat_amount" value="<?php echo $vat_amount; ?>">
                            <input type="hidden" name="disc_code" value="<?php echo $disc_code; ?>">
                            <input type="hidden" name="disc_amount" value="<?php echo $disc_amount; ?>">
                            <input type="hidden" name="voucher_code" value="<?php echo $voucher_code; ?>">
                            <input type="hidden" name="voucher_amount" value="<?php echo $voucher_amount; ?>">
                            <input type="hidden" name="vat-uk" value="<?php echo $vat_uk; ?>">
                            <input type="hidden" name="vat-employ" value="<?php echo $vat_employ; ?>">
                            <input type="hidden" name="vat-employer" value="<?php echo $vat_employer; ?>">
                            <?php /*
                            <input type="hidden" name="attend_first" value="<?php echo $attend_first; ?>">
                            <input type="hidden" name="attend_last" value="<?php echo $attend_last; ?>">
                            <input type="hidden" name="attend_email" value="<?php echo $attend_email; ?>">
                            *//* ?>
                            <input type="hidden" name="upsell_workshop_id" value="<?php echo $upsell_workshop_id; ?>">
                            <input type="hidden" id="full_name" value="<?php echo $full_name; ?>">
                            <input type="hidden" id="email" value="<?php echo $email; ?>">
                            <input type="hidden" name="source" value="<?php echo $source; ?>">
                            <input type="hidden" name="mailing_list" value="<?php echo $mailing_list; ?>">
                            <input type="hidden" name="conf_email" value="<?php echo $conf_email; ?>">
                            <?php foreach ($group_training as $training_id): ?>
                                <input type="hidden" name="training_id[]" value="<?php echo htmlspecialchars($training_id); ?>">
                            <?php endforeach; ?>
                            <?php */

                            echo cc_payment_panel($total_payable, $currency, $training_type, $invoice_allowed);

                            ?>
                        </form>

                    <div class="mb-5">
                        <button type="button" id="reg-payment-cancel" class="btn btn-secondary btn-sm reg-payment-cancel" onclick="history.back()">Return to Payment Details</button>
                        <?php /*
                        // this sort of works ... but it's a lot of aggro to set up the payment details page with the new data so .... history.back will do for now :-(
                        <button type="button" id="reg-payment-cancel" class="btn btn-secondary btn-sm reg-payment-cancel" onclick="backToPmtDets()">Return to Payment Details</button>
                        */ ?>
                    </div>

                <?php }else{

                    // Free registration
                    echo cc_payment_free_panel();
                    ?>
                    <!-- FB conversion tracking -->
                    <script>
                        window.addEventListener('load', (event) => {
                            if (typeof fbq === "function"){
                                fbq('track', 'Purchase', {
                                    value: 0,
                                    currency: 'GBP',
                                    contents: [
                                        {
                                            id: '0', <?php // $payment_id not known ?>
                                            quantity: <?php echo $num_attendees; ?>
                                        }
                                    ],
                                });
                            }
                        });
                    </script>

                <?php } ?>

                <div class="reg-final-wrap">
                    <?php echo cc_custom_training_buttons(); ?>
                </div>

            </div>
        </div>
    </div>
</div>

<div id="payment-terms" class="modal tandcs-modal cc-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms &amp; Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo cc_phrases_tandcs($training_type, $training_id); ?>
            </div>
        </div>
    </div>
</div>

<?php // voucher offer t&cs modal ?>
<div id="voucher-tandcs" class="modal cc-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gift Voucher Terms &amp; Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo cc_phrases_gift_voucher_terms(); ?>
            </div>
        </div>
    </div>
</div>

<?php // the workshop times modal ?>
<div id="workshop-times-modal" class="modal session-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
        </div>
    </div>
</div>

<?php
endwhile;
get_footer();
