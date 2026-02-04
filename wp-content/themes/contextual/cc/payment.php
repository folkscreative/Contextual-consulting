<?php
/**
 * Payment functions
 */

// the payment panel
// NOTE: .pay-field means the field is required if that method of payment is selected
function cc_payment_panel( $total_payable, $currency, $training_type, $invoice_allowed ){
	global $rpm_theme_options;
	$html = '
		<div class="animated-card">
			<div class="reg-pay-panel reg-panel wms-background animated-card-inner pale-bg">
				<div class="reg-pay">
					<h2>Payment:</h2>
					<h4>Total payable: '.cc_money_format($total_payable, $currency).'</h4>';
	if( $invoice_allowed ){
		$html .= '	<div class="mb-3">
						<label for="switch" class="form-label">How do you wish to pay?</label>
						<div class="text-center">
							<input type="radio" class="btn-check pay-switch" name="pay-type" id="pay-card" autocomplete="off" value="card" checked>
							<label class="btn btn-secondary" for="pay-card">Credit Card</label>
							<input type="radio" class="btn-check pay-switch" name="pay-type" id="pay-inv" autocomplete="off" value="inv">
							<label class="btn btn-secondary" for="pay-inv">Invoice</label>
						</div>
					</div>';
	}
	$html .= '		<div id="reg-card-dets" class="reg-card-dets">';
	if(cc_stripe_mode() == 'test'){
		$html .= '		<p class="stripe-test-mode">Stripe test mode: Use card number 4242 4242 4242 4242 for a successful (fake) payment</p>';
	}
	$html .= '			<div id="card-element" class="stripe-card-element-wrap mb-3">
							<!-- A Stripe Element will be inserted here. -->
						</div>
						<div class="row mb-3">
							<div class="col-md-6">';
	if($training_type == 'w'){
		$html .= '				<p>I agree to the <a href="/training-terms-and-conditions/" target="_blank">training terms and conditions</a>.</p>';
	}
	$html .= '					<p>A receipt will be emailed to you and can also be downloaded from your account.</p>';
	if( ! $invoice_allowed ){
		$html .= '				<p class="small"><a href="/sitefaq/payment-methods" target="_blank">Payment FAQs</a></p>';
	}
	$html .= '				</div>
							<div class="col-md-6 text-end">
								<a href="javascript:void(0)" id="reg-card-sub" class="btn btn-primary reg-pay-sub-btn">Submit payment</a>
							</div>
						</div>
					</div>';
	if( $invoice_allowed ){
		$html .= '	<div id="reg-inv-dets" class="reg-inv-dets">
						<h5>Invoice my Employing Authority</h5>
						<h6>Invoice to be made out to:</h6>
						<div class="mb-3">
							<label for="inv-org" class="form-label">Organisation to be invoiced *</label>
							<input type="text" id="inv-org" name="inv-org" class="form-control form-control-lg pay-field">
						</div>
						<div class="mb-3">
							<label for="inv-addr1" class="form-label">Address *</label>
							<input type="text" id="inv-addr1" name="inv-addr1" class="form-control form-control-lg pay-field">
						</div>
						<div class="mb-3">
							<label for="inv-addr2" class="form-label">Address line 2</label>
							<input type="text" id="inv-addr2" name="inv-addr2" class="form-control form-control-lg">
						</div>
						<div class="mb-3">
							<label for="inv-town" class="form-label">Town/city *</label>
							<input type="text" id="inv-town" name="inv-town" class="form-control form-control-lg pay-field">
						</div>
						<div class="mb-3">
							<label for="inv-county" class="form-label">County/state</label>
							<input type="text" id="inv-county" name="inv-county" class="form-control form-control-lg">
						</div>
						<div class="mb-3">
							<label for="inv-postcode" class="form-label">Postcode/zipcode *</label>
							<input type="text" id="inv-postcode" name="inv-postcode" class="form-control form-control-lg pay-field">
						</div>
						<div class="mb-3">
							<label for="inv-country" class="form-label">Country *</label>
							<select name="inv-country" id="inv-country" class="form-select form-select-lg pay-field">
								<option value="">Please select ...</option>
								'.ccpa_countries_options( "" ).'
							</select>
						</div>
						<h6>Invoice to be sent to:</h6>
						<div class="mb-3">
							<label for="inv-name" class="form-label">Contact person *</label>
							<input type="text" id="inv-name" name="inv-name" class="form-control form-control-lg pay-field">
						</div>
						<div class="mb-3">
							<label for="inv-email" class="form-label">Contact email *</label>
							<input type="email" class="form-control form-control-lg pay-field" name="inv-email" id="inv-email">
						</div>
						<div class="mb-3">
							<label for="inv-phone" class="form-label">Contact phone *</label>
							<input type="tel" class="form-control form-control-lg pay-field" name="inv-phone" id="inv-phone">
						</div>
						<div class="mb-3">
							<label for="inv-ref" class="form-label">PO number *</label>
							<input type="text" class="form-control form-control-lg pay-field" name="inv-ref" id="inv-ref">
							<div class="form-text">Purchase order number or other reference you would like on the invoice</div>
						</div>
						<div class="row mb-3">
							<div class="col-md-6">
								<p>I agree to the <a href="/training-terms-and-conditions/" target="_blank">training terms and conditions</a></p>
							</div>
							<div class="col-md-6 text-end">
								<a href="javascript:void(0)" id="reg-inv-sub" class="btn btn-primary reg-pay-sub-btn">Submit Registration</a>
							</div>
						</div>
					</div>';
	}
	$html .= '		<div id="reg-pay-msg" class="reg-pay-msg"></div>
				</div>
			</div>
		</div>';
	return $html;
}

// Updated payment health check to use tokens
add_action('wp_ajax_payment_health_check', 'cc_payment_payment_health_check');
add_action('wp_ajax_nopriv_payment_health_check', 'cc_payment_payment_health_check');
function cc_payment_payment_health_check(){
    $response = array(
        'status' => 'ok',
        'msg' => '',
    );
    echo json_encode($response);
    die();
}



// Updated payment recording function to work with tokens - COMPLETE VERSION
function cc_payment_record_payment_core_with_token($token, $form_data) {
    // ccpa_send_post_data_to_me();
    cc_debug_log_anything([
    	'function' => 'cc_payment_record_payment_core_with_token',
    	'token' => $token,
    	'form_data' => (array) $form_data,
    ]);

    // STEP 1: Get or create the user from temp registration
    $user_id = cc_create_user_from_temp_registration($token);
    
    if (!$user_id) {
        // Handle error - couldn't create/find user
        cc_debug_log_anything([
            'function' => 'cc_payment_record_payment_core_with_token',
            'error' => 'Failed to get/create user',
            'token' => $token
        ]);
        
        return array(
            'status' => 'error',
            'msg' => 'Unable to process registration. Please try again.'
        );
    }
    
    // STEP 2: Get the user data (either just created or existing)
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return array(
            'status' => 'error',
            'msg' => 'User data could not be retrieved.'
        );
    }
    
    // STEP 3: Check if this was a new user (for later email handling)
    // $registration_data = TempRegistration::get($token);
    // $temp_form_data = json_decode($registration_data->form_data, true);
    // $is_new_user = isset($temp_form_data['new_user']) && isset($temp_form_data['new_user']['is_new_user']);
    // $more = $form_data['more'] ?? [];

	// how many attendees
	$num_attendees = 1;
	if (isset($form_data['attendees']) && is_array($form_data['attendees'])) {
	    $num_attendees = count($form_data['attendees']);
	    /*
	} elseif (isset($form_data['attendees']['attend_type'])) {
	    // Fallback for old format (during transition)
	    if ($form_data['attendees']['attend_type'] == 'group' && isset($form_data['attendees']['attend_email'])) {
	        $num_attendees = count($form_data['attendees']['attend_email']);
	    }
	    */
	}
    
    // STEP 4: Build payment record
    $payment = cc_paymentdb_empty_payment();
    
    // Extract training type and set payment type
    if( isset( $form_data['training_type'] ) ){
        switch ($form_data['training_type']) {
            case 'r':
                $payment['type'] = 'recording';
                break;
            case 's':
                $payment['type'] = 'series';
                break;
            case 'g':
                $payment['type'] = 'group';
                break;
            default:
                $payment['type'] = '';
        }
    }

    // Basic training information
    if(isset($form_data['training_id'])){
    	// for a group, this will be the ID of the series that the group is a part of
        $payment['workshop_id'] = absint($form_data['training_id']);
    }
    if(isset($form_data['event_id'])){
        $payment['event_ids'] = sanitize_text_field($form_data['event_id']);
    }
    if(isset($form_data['currency']) && in_array($form_data['currency'], cc_valid_currencies())){
        $payment['currency'] = $form_data['currency'];
    }
    if(isset($form_data['student']) && $form_data['student'] == 'yes'){
        $payment['student'] = 'y';
    } elseif(cc_workshop_price_earlybird($payment['workshop_id'])){
        $payment['earlybird'] = 'y';
    }
    if(isset($form_data['upsell_workshop_id'])){
        $payment['upsell_workshop_id'] = (int) $form_data['upsell_workshop_id'];
    }

    // STEP 5: Populate user details
    $payment['reg_userid'] = $user_id;
    $payment['email'] = $form_data['email'];
    $payment['firstname'] = $form_data['firstname'];
    $payment['lastname'] = $form_data['lastname'];
    $payment['phone'] = $form_data['phone'];
    $payment['address'] = cc_users_user_address_formdata( $form_data );

    // Group training handling - prepare main payment notes
    if($payment['type'] == 'group' && isset($form_data['group_training'])){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter($group_training);
        $group_training = array_unique($group_training);
        
        if(!empty($group_training)){
	        // Build course details for notes
	        $course_details = array();
	        foreach($group_training as $training_id){
	            $course_title = get_the_title($training_id);
	            $course_details[] = $training_id . ': ' . $course_title;
	        }
            // Add group information to the main payment notes
            $payment['notes'] = 'Group training registration (' . count($group_training) . ' courses): ' . implode(', ', $course_details);

	        // Store group discount information if present
	        if(isset($form_data['payment_details']['group_disc_amount']) && $form_data['payment_details']['group_disc_amount'] > 0){
	            $group_disc_amount = (float) $form_data['payment_details']['group_disc_amount'];
	            $group_disc_percent = 0;
	            
	            // Calculate discount percentage if we have raw price
	            if(isset($form_data['raw_price']) && $form_data['raw_price'] > 0){
	                $group_disc_percent = round(($group_disc_amount / $form_data['raw_price']) * 100, 1);
	            }
	            
	            $payment['notes'] .= ' | Group discount applied: ' . 
	                                 cc_money_format( $group_disc_amount, strtolower( $payment['currency'] ) ) . 
	                                 ' (' . $group_disc_percent . '%)';
	        }

        }
    }    

    // Payment amounts
    // ccpa_write_log('payment.php cc_payment_record_payment_core_with_token');
    // ccpa_write_log('form data:');
    // ccpa_write_log($form_data);
    
    // Set payment details from payment_details in form_data
    // doesn't exist!
    // $payment_details = $form_data['payment_details'] ?? array();

    // $payment['payment_amount'] = (float) ($payment_details['total_payable'] ?? 0);
    $payment['payment_amount'] = floatval($form_data['total_payable'] ?? 0);

    // Payment method
	$payment_method = $form_data['pay_type'] ?? '';
	if($payment_method == 'inv'){
	    $payment['pmt_method'] = 'invoice';
	    $payment['status'] = 'Invoice requested';
	} else {
	    $payment['pmt_method'] = 'online';
	    if($payment['payment_amount'] == 0){
	        $payment['status'] = 'Payment not needed';
	    } else {
	        $payment['status'] = 'Payment successful: ';
	    }
	}

	// Invoice details - now reading from form_data where they were added
	if($payment['pmt_method'] == 'invoice'){
	    if(isset($form_data['inv_org'])){
	        $payment['inv_org'] = substr($form_data['inv_org'], 0, 255);
	    }
	    if(isset($form_data['inv_addr1'])){
	        $payment['inv_addr1'] = substr($form_data['inv_addr1'], 0, 255);
	    }
	    if(isset($form_data['inv_addr2'])){
	        $payment['inv_addr2'] = substr($form_data['inv_addr2'], 0, 255);
	    }
	    if(isset($form_data['inv_town'])){
	        $payment['inv_town'] = substr($form_data['inv_town'], 0, 255);
	    }
	    if(isset($form_data['inv_county'])){
	        $payment['inv_county'] = substr($form_data['inv_county'], 0, 255);
	    }
	    if(isset($form_data['inv_postcode'])){
	        $payment['inv_postcode'] = substr($form_data['inv_postcode'], 0, 255);
	    }
	    if(isset($form_data['inv_country'])){
	        $payment['inv_country'] = substr($form_data['inv_country'], 0, 255);
	    }
	    if(isset($form_data['inv_name'])){
	        $payment['inv_name'] = substr($form_data['inv_name'], 0, 255);
	    }
	    if(isset($form_data['inv_email'])){
	        $payment['inv_email'] = substr($form_data['inv_email'], 0, 255);
	    }
	    if(isset($form_data['inv_phone'])){
	        $payment['inv_phone'] = substr($form_data['inv_phone'], 0, 30);
	    }
	    if(isset($form_data['inv_ref'])){
	        $payment['inv_ref'] = substr($form_data['inv_ref'], 0, 255);
	    }
	}

    // Discounts
	if( $form_data['training_type'] == 's' ){
		if( $form_data['series_discount'] > 0 ){
			$payment['disc_amount'] = $form_data['series_discount'] * $num_attendees;
			$payment['disc_code'] = 'series';
		}
	}elseif( ( $form_data['training_type'] == 'w' || $form_data['training_type'] == 'r' ) && isset( $form_data['disc_code'] ) && $form_data['disc_code'] <> '' ){
        if( substr( $form_data['disc_code'], 0, 3) == 'CC-' ){
            if( cc_friend_can_i_use_it( $form_data['disc_code'], $user_id ) ){
                $disc_percent = get_option('refer_friend_percent', 0);
                $disc_amount = round( $form_data['raw_price'] * $disc_percent * 0.01, 2 );
	            if( $disc_amount > 0 ){
	            	$payment['disc_code'] = substr( $form_data['disc_code'], 0, 25 );
	            	$payment['disc_amount'] = $disc_amount * $num_attendees;
	            }
            }
        }else{
	        $discount = cc_discount_lookup( $form_data['disc_code'], $form_data['training_type'], $form_data['training_id'] );
	        if ($discount) {
	            if ($discount['disc_type'] == 'a') {
	                $disc_amount = min($discount['disc_amount'], $raw_price);
	            } elseif ($discount['disc_type'] == 'p') {
	                $disc_amount = round( $form_data['raw_price'] * $discount['disc_amount'] * 0.01, 2);
	            }
	            if( $disc_amount > 0 ){
	            	$payment['disc_code'] = substr($form_data['disc_code'], 0, 25);
	            	$payment['disc_amount'] = $disc_amount * $num_attendees;
	            }
	        }
	    }
	}

	// vouchers
	if( $form_data['voucher_code'] <> '' && $form_data['voucher_amount'] > 0 ){
		$payment['voucher_amount'] = (float) $form_data['voucher_amount'];
		if( substr( $form_data['voucher_code'], 0, 3) == 'CC-' ){
			$payment['voucher_code'] = substr( $form_data['voucher_code'], 0, 12 );
		}else{
			$payment['voucher_code'] = substr( cc_voucher_code_raw( $form_data['voucher_code'] ), 0, 12 );
		}
	}
	
	/*
    if(isset($form_data['disc_amount']) && $form_data['disc_amount'] > 0){
        $payment['disc_amount'] = (float) $form_data['disc_amount'] * $num_attendees;
    }
    if(isset($form_data['disc_code']) && $payment['disc_amount'] <> ''){
        $payment['disc_code'] = substr($form_data['disc_code'], 0, 25);
    }
    */

    // VAT details
    if(isset($form_data['vat_exempt'])){
        $payment['vat_exempt'] = $form_data['vat_exempt'];
    }
    if(isset($form_data['vat_uk'])){
        $payment['vat_uk'] = $form_data['vat_uk'];
    }
    if(isset($form_data['vat_employ'])){
        $payment['vat_employ'] = substr($form_data['vat_employ'], 0, 25);
    }
    if(isset($form_data['vat_employer'])){
        $payment['vat_employer'] = substr($form_data['vat_employer'], 0, 255);
    }
    if( isset( $form_data['vat_amount'] ) ){
    	$payment['vat_included'] = (float) $form_data['vat_amount'] * $num_attendees;
    }else{
    	$payment['vat_included'] = 0;
    }

	// Stripe payment details
	if(isset($form_data['client_secret']) && $form_data['client_secret'] != ''){
	    $client_secret = $form_data['client_secret'];
	    $payment['token'] = ccpa_payint_client_secret_get_pi_id($client_secret);
	}
    if(isset($form_data['payment_intent_id']) && $form_data['payment_intent_id'] != ''){
        $payment['payment_intent_id'] = $form_data['payment_intent_id'];
    }

    // Source and mailing list
    if( isset( $form_data['source'] ) && $form_data['source'] <> '' ){
        $payment['source'] = substr($form_data['source'], 0, 255);
    }

    if( isset( $form_data['mailing_list'] ) && $form_data['mailing_list'] == 'yes' ){
        $payment['mailing_list'] = 'y';
    }elseif( cc_mailsterint_on_newsletter( $form_data['email'] ) ){
		$payment['mailing_list'] = 'p';
	}else{
		$payment['mailing_list'] = 'n';
	}

    // Manager email note
    if(isset($form_data['conf_email']) && $form_data['conf_email'] != ''){
        $existing_notes = $payment['notes'] ?? '';
        $manager_note = 'Manager email: ' . $form_data['conf_email'];
        $payment['notes'] = $existing_notes ? $existing_notes . '. ' . $manager_note : $manager_note;
    }

    // Additional tracking
    $payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $payment['tandcs'] = 'y';
    $payment['last_update'] = date('Y-m-d H:i:s');

    // upsell portion of payment amount
	if( $payment['upsell_workshop_id'] > 0 ){
		$payment['disc_code'] = 'UPSELL';
		$upsell = cc_upsells_offer( $payment['workshop_id'] );
		if( $upsell <> NULL && $payment['payment_amount'] > 0 && $upsell['discount'] > 0 ){
			// discount will be expressed as a percentage eg 10.0000
			$undiscounted_amount = $payment['payment_amount'] / ( 1 - $upsell['discount'] / 100);
			$payment['disc_amount'] = round( $undiscounted_amount - $payment['payment_amount'], 2 );
			// we need to calculate the upsell_payment_amount
			// this is the actual amount paid for the upsell after discount applied
			$payment['upsell_payment_amount'] = cc_payment_upsell_payment_amount( $payment['upsell_workshop_id'], $payment['currency'], $upsell['discount'] );
		}
	}


    // Validate and repair payment data before saving
    /*
    if( function_exists( 'cc_payment_validate_and_repair' ) ){
        $validation_result = cc_payment_validate_and_repair($payment, $token, $attendee_data);
        $payment = $validation_result['payment'];
        $attendee_data = $validation_result['attendees'];
    }
    */

    // STEP 6: Check for duplicate payments
    $duplicate_id = cc_paymentdb_dejavu_token($user_id, $payment['workshop_id'], $token);

    if(!$duplicate_id){

    	/*
    	ccpa_write_log([
        	'function' => 'cc_payment_record_payment_core_with_token',
        	'step' => 'before saving main payment',
        	'token' => $token,
        	'payment' => $payment,
    	]);
    	*/

        // Create the MAIN payment record (full amount)
        // also stores attendees and adds them to the workshop/recording users tables
        // but does not add the recording meta
        $main_payment_id = cc_paymentdb_update_payment($payment, $token);
        
        if($main_payment_id){
            $payment['id'] = $main_payment_id;
            $payment_id = $main_payment_id; // For the response

            // Process attendees for the main payment
            if(isset($form_data['attendees'])){
                cc_attendees_save_from_token($token, $main_payment_id);
            }

            // Get attendee count for stats
            $attendees = cc_attendees_for_payment($main_payment_id);
            $count_attendees = count($attendees);

            // what course are included in this registration?
            $child_training_ids = array();
            if( $payment['type'] == 'group' ){
                $group_training = array_map('absint', (array) $form_data['group_training']);
                $group_training = array_filter($group_training);
                $group_training = array_unique($group_training);
                $child_training_ids = $group_training;
                $series_discount = 0;
            }elseif( $payment['type'] == 'series' ){
                $series_id = $payment['workshop_id'];
                $series_courses = get_post_meta( $series_id, '_series_courses', true );
                $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
                $series_courses = is_array($series_courses) ? $series_courses : [];
                $child_training_ids = $series_courses;
				$series_discount = (float) get_post_meta( $form_data['training_id'], '_series_discount', true);
            }

            // create individual payment records for groups and series
            if( ! empty( $child_training_ids ) ){
			    global $wpdb;
			    $table_name = $wpdb->prefix . 'ccpa_payments';

	            // Get actual prices for each course
	            $course_prices = [];
	            $total_course_prices = 0;
	            $now = time();
		            
	            foreach($child_training_ids as $training_id){
                    // Get the individual course price
                    $pricing = get_training_prices( $training_id );

				    $currency_lc = strtolower($payment['currency']);
					$course_price = $pricing['price_'.$currency_lc];

					$total_payable = $course_price * $count_attendees;

	                // Apply student discount if applicable
	                $student_discount = 0;
	                if( $payment['student'] == 'y' && $pricing['student_discount'] > 0 ){
						$student_discount = round( $course_price * $pricing['student_discount'] / 100, 2 );
						$student_discount = $student_discount * $count_attendees;
						if( $student_discount > $total_payable ){
							$student_discount = $total_payable;
							$total_payable = 0;
						}else{
							$total_payable = $total_payable - $student_discount;
						}
	                }

	                // or early bird discount
	                $early_bird_name = '';
	                $early_bird_discount = 0;
					if( $pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] <> '' ){
						$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'] );
						if( $expiry_date ){
							if( $expiry_date->getTimestamp() > $now ){
								$early_bird_name = ( $pricing['early_bird_name'] == '' ) ? 'Early bird' : $pricing['early_bird_name'];
								$early_bird_discount = round( $course_price * $pricing['early_bird_discount'] / 100, 2 );
								$early_bird_discount = $early_bird_discount * $count_attendees;
								if( $early_bird_discount > $total_payable ){
									$early_bird_discount = $total_payable;
									$total_payable = 0;
								}else{
									$total_payable = $total_payable - $early_bird_discount;
								}
							}
						}
					}

					// if it's a series, apply the series discount
					if( $series_discount > 0 ){
						$series_disc_amt = round( $course_price * $series_discount / 100, 2 );
						$series_disc_amt = $series_disc_amt * $count_attendees;
						if( $series_disc_amt > $total_payable ){
							$series_disc_amt = $total_payable;
							$total_payable = 0;
						}else{
							$total_payable = $total_payable - $series_disc_amt;
						}
					}else{
						$series_disc_amt = 0;
					}

					// vat
					$vat = ( $payment['vat_exempt'] == 'y' ) ? 0 : ccpa_vat_amount( $total_payable );
					$total_payable = $total_payable + $vat;
	                
	                // store it
	                $course_prices[$training_id] = [
	                	'course_price' => $course_price,
	                	'total_payable' => $total_payable,
	                	'student_discount' => $student_discount,
	                	'early_bird_name' => $early_bird_name,
	                	'early_bird_discount' => $early_bird_discount,
	                	'series_discount' => $series_disc_amt,
	                	'vat_included' => $vat,
	                ];
	            }

                // Create individual course records (linked to main payment)
                $course_payment_ids = [];
                $running_total = 0;

                foreach( $child_training_ids as $index => $training_id ){
                    // Clone the payment array for each course
                    $course_payment = $payment;

                    // Set key fields for the course record
		            $course_payment['id'] = 0; // Force new record
		            $course_payment['payment_ref'] = $main_payment_id; // Link to main payment
                    
                    // Clear fields that should only be on the main payment
                    $course_payment['payment_intent_id'] = '';
                    $course_payment['charge_id'] = '';
                    $course_payment['stripe_fee'] = 0;
                    
                    // Set the specific workshop ID for this record
                    $course_payment['workshop_id'] = $training_id;
                    
		            // Set type based on training type
		            if( course_training_type( $training_id ) == 'recording' ){
		            	$course_payment['type'] = 'recording';
		            }else{
		            	$course_payment['type'] = '';
		            }

		            // financials
		            $course_payment['payment_amount'] = $course_prices[$training_id]['total_payable'];
		            if( $course_prices[$training_id]['student_discount'] > 0 ){
		            	$course_payment['disc_amount'] = $course_prices[$training_id]['student_discount'];
		            	$course_payment['disc_code'] = 'student';
		            }
		            if( $course_prices[$training_id]['early_bird_discount'] > 0 ){
		            	$course_payment['disc_amount'] = $course_prices[$training_id]['early_bird_discount'];
		            	$course_payment['disc_code'] = $course_prices[$training_id]['early_bird_name'];
		            }
		            if( $course_prices[$training_id]['series_discount'] > 0 ){
		            	$course_payment['disc_amount'] = $course_prices[$training_id]['series_discount'];
		            	$course_payment['disc_code'] = 'series';
		            }
		            $course_payment['vat_included'] = $course_prices[$training_id]['vat_included'];
                        
                    // Add course-specific note linking to main payment
					$course_payment['notes'] = sprintf(
					    'Course record for %s payment %d - Course %d of %d: %d: %s',
					    $payment['type'],
                        $main_payment_id,
					    ($index + 1),
					    count($child_training_ids),
					    $training_id,
					    get_the_title($training_id),
					);
                        
                    // Set a different status to indicate this is a linked record
                    $course_payment['status'] = 'Linked to #' . $main_payment_id;
                    
                    $course_payment['token'] = '';

		            // Ensure we have required fields
		            $course_payment['last_update'] = date('Y-m-d H:i:s');
		            $course_payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
			            
		            // DIRECT DATABASE INSERT - bypassing cc_paymentdb_update_payment
		            $result = $wpdb->insert($table_name, $course_payment);

		            if($result !== false){
		                $course_payment_id = $wpdb->insert_id;
			                
		                // Set the ID for use in subsequent functions
		                $course_payment['id'] = $course_payment_id;
		                $course_payment_ids[] = $course_payment_id;
			                
		                // Copy attendees from main payment to this course payment
		                // This is crucial for cc_myacct_insert_workshops_recordings_users to work
		                foreach($attendees as $attendee){
                            // Only pass the fields that exist in cc_attendees table
                            $attendee_data = array(
                                'payment_id' => $course_payment_id,
                                'user_id' => $attendee['user_id'] ?? 0,
                                'registrant' => $attendee['registrant'] ?? '',
                            );
		                    cc_attendee_add($attendee_data);
		                }

		                // Now explicitly call cc_myacct_insert_workshops_recordings_users
		                // This function will now find the attendees we just added and create
		                // the appropriate workshops_users or recordings_users entries
		                cc_myacct_insert_workshops_recordings_users($course_payment);
			                
		                // If it's a recording, also give recording access via user meta
		                if($course_payment['type'] == 'recording'){
		                    $access_type = $course_prices[$training_id]['total_payable'] > 0 ? 'paid' : 'free';
		                    foreach($attendees as $attendee){
		                        if( isset( $attendee['user_id'] ) && $attendee['user_id'] > 0 ){
		                        	// ccrecw_add_recording_to_user($user_id, $recording_id, $raw_access_type='free', $amount=0, $token='', $currency='GBP', $vat_included=0, $payment_id=NULL)
		                            ccrecw_add_recording_to_user(
		                                $attendee['user_id'], 
		                                $training_id, 
		                                $access_type, 
		                                $course_prices[$training_id]['total_payable'],
		                                '', 
		                                $payment['currency'], 
		                                $course_prices[$training_id]['vat_included'],
		                                $course_payment_id
		                            );
		                        }
		                    }
		                }

						// Update popularity stats for each course
		                cc_training_popularity_update($training_id, $count_attendees);
		                
		                // Record in training groups table for tracking
		                if( $payment['type'] == 'group' ){
			                cc_training_groups_record_training($main_payment_id, $training_id);
			            }
		            } else {
		                // Log error if insert failed
		                error_log('Failed to insert course payment for training ID: ' . $training_id);
		            }
		            
		            $running_total += $course_amount;
		        }
                    
                // Update the main payment record with links to course records
                if(!empty($course_payment_ids)){
                    global $wpdb;
                    $linked_ids = implode(',', $course_payment_ids);
                    $link_note = sprintf(' | Linked course records: %s', $linked_ids);
                    
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$wpdb->prefix}ccpa_payments 
                         SET notes = CONCAT(notes, %s) 
                         WHERE id = %d",
                        $link_note, $main_payment_id
                    ));
                    
                    // Store for response
                    $payment['course_payment_ids'] = $course_payment_ids;
                }
            }

            // now process a single training
            if( $payment['type'] == 'recording' || $payment['type'] == '' ){
	            // update popularity
	            cc_training_popularity_update($payment['workshop_id'], $count_attendees);
	            if($payment['upsell_workshop_id'] > 0){
	                cc_training_popularity_update($payment['upsell_workshop_id'], $count_attendees);
	            }
            }

		    // Give recording access to ALL attendees (not just registrant) (= user meta)
		    if($payment['type'] == 'recording'){
		        $access_type = ($payment['payment_amount'] > 0) ? 'paid' : 'free';
		        // Give recording access to each attendee
		        foreach ($attendees as $attendee) {
		            ccrecw_add_recording_to_user(
		                $attendee['user_id'], 
		                $payment['workshop_id'], 
		                $access_type, 
		                $payment['payment_amount'], 
		                '', 
		                $payment['currency'], 
		                cc_workshop_pricing_vat_included($payment['payment_amount']), 
		                $payment_id
		            );
		        }
		    }

            // Refer-a-friend code processing
            if( substr( $payment['disc_code'], 0, 3 ) == 'CC-' ){
                cc_friend_code_used( $payment['disc_code'], $payment['reg_userid'], $payment['currency'], $payment['disc_amount'] );
            }

            // voucher usage
            if( $payment['voucher_code'] <> '' ){
            	if( $payment['voucher_code'] == cc_friend_user_code_lookup( $user_id ) ){
		            // this user has used some of their own RAF credits
					cc_friend_redeem( $payment['voucher_code'], $payment['reg_userid'], $payment['currency'], $payment['voucher_amount'] );
				}else{
					ccpa_voucher_usage_record($payment['voucher_code'], $payment['voucher_amount'], $payment['currency'], $payment_id);
				}
            }

            // Save last registration info
            update_user_meta($user_id, 'last_registration', date('Y-m-d H:i:s'));
            update_user_meta($user_id, 'last_reg_id', $main_payment_id);

            // Add to mailing list if requested
            if($payment['mailing_list'] == 'y'){
                update_user_meta($user_id, 'mailing_list', 'y');
                cc_mailsterint_newsletter_subscribe($user);
            }
            cc_mailsterint_update_region($user);

            // Update temp registration status
            TempRegistration::update_status($token, 'completed');

            do_action('cc_registration_completed', $user_id, $main_payment_id, $payment['workshop_id']);

            // Send confirmation emails
            cc_mailsterint_send_reg_emails($payment);

			// does the payment result in a voucher being sent?
			if(!$duplicate_id){
				ccpa_vouchers_alloc_maybe($payment);
			}

            // Generate response message
            $response_message = 'Many thanks for your registration. A confirmation email is on its way to you now<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
            
            if($payment['payment_amount'] == 0){
                // Free registration message (already set above)
            } elseif($payment['pmt_method'] == 'online'){
                $response_message = 'Many thanks for your payment. A confirmation email is on its way to you now<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
            } else {
                $response_message = 'Many thanks for your interest. A confirmation email is on its way to you now.<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong><br>We will also send an invoice out to you shortly.';
            }

            // Generate confirmation URL
            $funny_number = ($payment_id + 987834) * 2;
            $conf_url = add_query_arg(array('c' => $funny_number), site_url('/reg-conf/'));

            // Generate conversion tracking data
            $conversion_data = $payment['payment_amount'].'|'.$payment['currency'].'|'.($client_secret ?? '').'|'.$user->user_email;

            return array(
                'status' => 'ok', 
                'msg' => $response_message,
                'conversion' => $conversion_data,
                'payment_id' => $payment_id,
                'is_new_user' => $new_user
            );

        } else {
            return array('status' => 'error', 'msg' => 'Failed to create payment record');
        }
    } else {
        // Duplicate payment detected
        $funny_number = ($duplicate_id + 987834) * 2;
        $conf_url = add_query_arg(array('c' => $funny_number), site_url('/reg-conf/'));
        
        return array(
            'status' => 'ok', 
            'msg' => 'Registration already exists - please <a href="/contact-us">contact us</a> for assistance.',
            'payment_id' => $duplicate_id
        );
    }
}

    /*
    // Payment details from form_data
    if(isset($form_data['payment_details'])){
        $payment_details = $form_data['payment_details'];
        
        // Payment amounts
        if(isset($payment_details['total_payable'])){
            $payment['payment_amount'] = (float) $payment_details['total_payable'];
        }
        if(isset($payment_details['vat_amount'])){
            $payment['vat_included'] = (float) $payment_details['vat_amount'];
        }
        
        // Discounts and vouchers
        if(isset($payment_details['disc_amount'])){
            $payment['disc_amount'] = (float) $payment_details['disc_amount'];
        }
        if(isset($payment_details['disc_code']) && $payment['disc_amount'] > 0){
            $payment['disc_code'] = substr($payment_details['disc_code'], 0, 25);
        }
        if(isset($payment_details['voucher_amount'])){
            $payment['voucher_amount'] = (float) $payment_details['voucher_amount'];
        }
        if(isset($payment_details['voucher_code']) && $payment['voucher_amount'] > 0){
            $payment['voucher_code'] = substr(cc_voucher_code_raw($payment_details['voucher_code']), 0, 12);
        }

        // VAT information
        if(isset($payment_details['vat_exempt'])){
            $payment['vat_exempt'] = $payment_details['vat_exempt'];
        }
        if(isset($payment_details['vat_uk'])){
            $payment['vat_uk'] = substr($payment_details['vat_uk'], 0, 25);
        }
        if(isset($payment_details['vat_employ'])){
            $payment['vat_employ'] = substr($payment_details['vat_employ'], 0, 25);
        }
        if(isset($payment_details['vat_employer'])){
            $payment['vat_employer'] = substr($payment_details['vat_employer'], 0, 255);
        }

        // Invoice details
        if(isset($payment_details['inv_email'])){
            $payment['inv_email'] = substr($payment_details['inv_email'], 0, 255);
        }
        if(isset($payment_details['inv_phone'])){
            $payment['inv_phone'] = substr($payment_details['inv_phone'], 0, 30);
        }
        if(isset($payment_details['inv_ref'])){
            $payment['inv_ref'] = substr($payment_details['inv_ref'], 0, 255);
        }
        if(isset($payment_details['inv_org'])){
            $payment['inv_org'] = substr($payment_details['inv_org'], 0, 255);
        }
        if(isset($payment_details['inv_name'])){
            $payment['inv_name'] = substr($payment_details['inv_name'], 0, 255);
        }

        // Invoice address
        if(isset($payment_details['inv_addr1'])){
            $payment['inv_addr1'] = substr($payment_details['inv_addr1'], 0, 255);
        }
        if(isset($payment_details['inv_addr2'])){
            $payment['inv_addr2'] = substr($payment_details['inv_addr2'], 0, 255);
        }
        if(isset($payment_details['inv_town'])){
            $payment['inv_town'] = substr($payment_details['inv_town'], 0, 255);
        }
        if(isset($payment_details['inv_county'])){
            $payment['inv_county'] = substr($payment_details['inv_county'], 0, 255);
        }
        if(isset($payment_details['inv_postcode'])){
            $payment['inv_postcode'] = substr($payment_details['inv_postcode'], 0, 255);
        }
        if(isset($payment_details['inv_country'])){
            $payment['inv_country'] = substr($payment_details['inv_country'], 0, 255);
        }
    }

    // Group training data
    $group_training = array();
    if($payment['type'] == 'group' && isset($form_data['group_training'])){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter($group_training); // remove zeroes
        $group_training = array_unique($group_training); // remove duplicates
        
		// Store group training data in payment notes
	    $payment['notes'] = 'Group training: ' . implode(', ', array_map('get_the_title', $group_training));
            
        // For main payment record, use first training ID as workshop_id
        if(!empty($group_training)){
            $payment['workshop_id'] = $group_training[0];
        }
    }
    *//*

    // Payment method determination
    $payment_method = $_POST['payment_method'] ?? 'card';
    if($payment_method == 'invoice'){
        $payment['pmt_method'] = 'invoice';
        $payment['status'] = 'Pending';
    } else {
        $payment['pmt_method'] = 'online';
        if($payment['payment_amount'] > 0){
            $payment['status'] = 'Payment complete';
        } else {
            $payment['status'] = 'Payment not needed';
        }
    }

    // Set status for free payments
    if($payment['payment_amount'] == 0){
        $payment['status'] = 'Payment not needed';
        $payment['pmt_method'] = 'free';
    }

    // Stripe payment data (already processed by JavaScript)
    if(isset($_POST['client-secret']) && $_POST['client-secret'] <> ''){
        $client_secret = stripslashes(sanitize_text_field($_POST['client-secret']));
        $payment['token'] = ccpa_payint_client_secret_get_pi_id($client_secret);
    }
    if(isset($_POST['PIID']) && $_POST['PIID'] <> ''){
        $payment['payment_intent_id'] = stripslashes(sanitize_text_field($_POST['PIID']));
    }

    // Manager email (for portal users)
    if(isset($_POST['conf_email']) && $_POST['conf_email'] <> ''){
        $existing_notes = $payment['notes'] ?? '';
        $manager_note = 'Manager email: '.stripslashes(sanitize_text_field($_POST['conf_email']));
        $payment['notes'] = $existing_notes ? $existing_notes . '. ' . $manager_note : $manager_note;
    }

    // Additional tracking
    $payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $payment['tandcs'] = 'y';

    // Check for duplicates using token-based approach
    $duplicate_id = cc_paymentdb_dejavu_token(get_current_user_id(), $payment['workshop_id'], $token);

    if(!$duplicate_id){
        // Set last_update if not set
        if(!isset($payment['last_update']) || $payment['last_update'] == ''){
            $payment['last_update'] = date('Y-m-d H:i:s');
        }

        $payment_id = cc_paymentdb_update_payment( $payment, $token );
        
        if($payment_id){
            $payment['id'] = $payment_id;

		    if( $payment['last_update'] == '' ){
		        $payment['last_update'] = date( 'Y-m-d H:i:s' );
		    }

            // *** REFER-A-FRIEND CODE PROCESSING ***
            if( substr( $payment['disc_code'], 0, 3 ) == 'CC-' && !$duplicate_id ){
                cc_friend_code_used( $payment['disc_code'], $payment['reg_userid'], $payment['currency'], $payment['disc_amount'] );
            }

            // *** SAVE LAST REGISTRATION INFO ***
            update_user_meta($current_user->ID, 'last_registration', date('Y-m-d H:i:s'));
            update_user_meta($current_user->ID, 'last_reg_id', $payment_id);

            // *** ADD TO MAILING LIST ***
            if($payment['mailing_list'] == 'y'){
                update_user_meta($current_user->ID, 'mailing_list', 'y');
                cc_mailsterint_newsletter_subscribe($current_user);
            }
            cc_mailsterint_update_region($current_user);

            // Get attendee count from database (most reliable)
            $attendees = cc_attendees_for_payment($payment_id);
            $count_attendees = count($attendees);
            $access_type = ($payment['payment_amount'] > 0) ? 'paid' : 'free';

			// *** HANDLE RECORDING ACCESS FOR MAIN TRAINING ***
			if($payment['type'] == 'recording'){
			    foreach ($attendees as $attendee) {
			        ccrecw_add_recording_to_user(
			            $attendee['user_id'], 
			            $payment['workshop_id'], 
			            $access_type, 
			            $payment['payment_amount'], 
			            '', 
			            $payment['currency'], 
			            cc_workshop_pricing_vat_included($payment['payment_amount']), 
			            $payment_id
			        );
			    }
			}

			// *** HANDLE UPSELL RECORDING ACCESS ***
			if( $payment['upsell_workshop_id'] > 0 && course_training_type( $payment['upsell_workshop_id'] ) == 'recording' ){
			    foreach ($attendees as $attendee) {
			        ccrecw_add_recording_to_user(
			            $attendee['user_id'], 
			            $payment['upsell_workshop_id'], 
			            $access_type, 
			            $payment['payment_amount'], 
			            '', 
			            $payment['currency'], 
			            cc_workshop_pricing_vat_included($payment['payment_amount']), 
			            $payment_id
			        );
			    }
			}

            // *** HANDLE GROUP TRAINING - CREATE INDIVIDUAL PAYMENT RECORDS ***
            if($payment['type'] == 'group' && !empty($group_training) && $payment_id){
                foreach($group_training as $training_id){
                    // Record in training groups table
                    cc_training_groups_record_training($payment_id, $training_id);
                    
                    // Create individual payment records for tracking (like series does)
                    $individual_payment = $payment;
                    $individual_payment['id'] = 0; // Force new record
                    $individual_payment['workshop_id'] = $training_id;
                    $individual_payment['payment_ref'] = $payment_id; // Link to main payment
                    $individual_payment['payment_amount'] = 0;
                    $individual_payment['vat_included'] = 0;
                    $individual_payment['vat_exempt'] = '';
                    $individual_payment['disc_amount'] = 0;
                    $individual_payment['pmt_method'] = '';
                    $individual_payment['inv_email'] = '';
                    $individual_payment['inv_phone'] = '';
                    $individual_payment['inv_ref'] = '';
                    $individual_payment['upsell_workshop_id'] = 0;
                    $individual_payment['stripe_fee'] = 0;
                    $individual_payment['vat_uk'] = '';
                    $individual_payment['vat_employ'] = '';
                    $individual_payment['vat_employer'] = '';
                    $individual_payment['student'] = '';
                    $individual_payment['earlybird'] = '';
                    $individual_payment['voucher_code'] = '';
                    $individual_payment['voucher_amount'] = 0;
                    $individual_payment['payment_intent_id'] = '';
                    $individual_payment['charge_id'] = '';
                    $individual_payment['notes'] = 'Part of group registration #' . $payment_id;
                    
                    // Set type based on training type
                    if(get_post_type($training_id) == 'workshop'){
                        $individual_payment['type'] = '';
                    } else {
                        $individual_payment['type'] = 'recording';
                    }
                    
                    $individual_payment_id = cc_paymentdb_update_payment($individual_payment);
                    
                    // Give recording access if it's a recording
                    if($individual_payment['type'] == 'recording'){
                        foreach($attendees as $attendee){
                            ccrecw_add_recording_to_user(
                                $attendee['user_id'], 
                                $training_id, 
                                $access_type, 
                                0, // Individual amount
                                '', 
                                $payment['currency'], 
                                0, // VAT for individual
                                $individual_payment_id
                            );
                        }
                    }
                    
                    // Update popularity stats for each training
                    cc_training_popularity_update($training_id, $count_attendees);
                }
            }

            // *** HANDLE SERIES TRAINING - CREATE INDIVIDUAL PAYMENT RECORDS ***
            if($payment['type'] == 'series'){
                $series_id = $payment['workshop_id'];
                $series_courses = get_post_meta( $series_id, '_series_courses', true );
                $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
                $series_courses = is_array($series_courses) ? $series_courses : [];

                foreach ( $series_courses as $training_id ) {
                    $training_payment = $payment;
                    $training_payment['id'] = 0;
                    $training_payment['payment_amount'] = 0;
                    $training_payment['vat_included'] = 0;
                    $training_payment['vat_exempt'] = '';
                    $training_payment['payment_ref'] = $series_id;
                    $training_payment['disc_amount'] = 0;
                    $training_payment['pmt_method'] = '';
                    $training_payment['inv_email'] = '';
                    $training_payment['inv_phone'] = '';
                    $training_payment['inv_ref'] = '';
                    $training_payment['workshop_id'] = $training_id;
                    $training_payment['upsell_workshop_id'] = 0;
                    if( course_training_type( $training_id ) =='workshop' ){
                        $training_payment['type'] = '';
                    }else{
                        $training_payment['type'] = 'recording';
                    }
                    $training_payment['stripe_fee'] = 0;
                    $training_payment['vat_uk'] = '';
                    $training_payment['vat_employ'] = '';
                    $training_payment['vat_employer'] = '';
                    $training_payment['student'] = '';
                    $training_payment['earlybird'] = '';
                    $training_payment['voucher_code'] = '';
                    $training_payment['voucher_amount'] = 0;
                    $training_payment['payment_intent_id'] = '';
                    $training_payment['charge_id'] = '';

					$training_payment['inv_address'] = '';
					$training_payment['vat_website'] = '';  
					$training_payment['upsell_payment_amount'] = 0;
					$training_payment['invoice_no'] = '';
					$training_payment['invoice_id'] = '';

                    $training_payment_id = cc_paymentdb_update_payment($training_payment);

                    if( $training_payment['type'] == 'recording' ){
                        foreach ($attendees as $attendee) {
                            ccrecw_add_recording_to_user( $attendee['user_id'], $training_id, $access_type, 0, '', '', 0, $training_payment_id);
                        }
                    }

                    // Update popularity stats for each training
                    cc_training_popularity_update($training_id, $count_attendees);
                }
            }

            // *** VOUCHER CODE PROCESSING ***
            if($payment['voucher_code'] <> '' && !$duplicate_id){
                if( substr($payment['voucher_code'], 0, 3) == 'CC-' ){
                    // refer a friend redemption
                    cc_friend_redeem( $payment['voucher_code'], $payment['reg_userid'], $payment['currency'], $payment['voucher_amount'] );
                }else{
                    ccpa_voucher_usage_record($payment['voucher_code'], $payment['voucher_amount'], $payment['currency'], $payment_id);
                }
            }

            // *** SEND EMAILS ***
            cc_mailsterint_send_reg_emails($payment);

            // *** VOUCHER ALLOCATION ***
            ccpa_vouchers_alloc_maybe($payment);

            // *** UPDATE POPULARITY STATS FOR MAIN TRAINING ***
            cc_training_popularity_update($payment['workshop_id'], $count_attendees);
            if($payment['upsell_workshop_id'] > 0){
                cc_training_popularity_update($payment['upsell_workshop_id'], $count_attendees);
            }

            // Clear the temp registration as it's now complete
            TempRegistration::delete($token);

			// Generate confirmation URL
			$funny_number = ($payment_id + 987834) * 2;
			$conf_url = add_query_arg(array('c' => $funny_number), site_url('/reg-conf/'));

			// Generate appropriate response message based on payment details
			$response_message = '';
			if($payment['payment_amount'] == 0){
			    $response_message = 'Many thanks for your registration. A confirmation email is on its way to you now<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
			} elseif($payment['pmt_method'] == 'online'){
			    $response_message = 'Many thanks for your payment. A confirmation email is on its way to you now<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
			} else {
			    $response_message = 'Many thanks for your interest. A confirmation email is on its way to you now.<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong><br>We will also send an invoice out to you shortly.';
			}

			// Generate conversion tracking data for Google Ads
			$conversion_data = $payment['payment_amount'].'|'.$payment['currency'].'|'.$client_secret.'|'.$current_user->user_email;

			return array(
			    'status' => 'ok', 
			    'message' => $response_message,
			    'redirect_url' => $conf_url,
			    'conversion' => $conversion_data,
			    'payment_id' => $payment_id
			);

        } else {
            return array('status' => 'error', 'message' => 'Failed to create payment record');
        }
    } else {
        // Duplicate payment
        $funny_number = ($duplicate_id + 987834) * 2;
        $conf_url = add_query_arg(array('c' => $funny_number), site_url('/reg-conf/'));
        
	    return array(
	        'status' => 'ok', 
	        'message' => 'Registration already exists - redirecting to confirmation',
	        'redirect_url' => $conf_url,
	        'payment_id' => $duplicate_id
	    );

    }
}
*/



// record a payment
add_action('wp_ajax_record_payment', 'cc_payment_record_payment');
add_action('wp_ajax_nopriv_record_payment', 'cc_payment_record_payment');
function cc_payment_record_payment(){
	cc_debug_log_anything([
		'function' => 'cc_payment_record_payment',
		'post' => $_POST,
	]);
	if( isset( $_POST['token'] ) ){
        $current_token = stripslashes( sanitize_text_field( $_POST['token'] ) );
        $registration_data = TempRegistration::get($current_token);
        $form_data = json_decode($registration_data->form_data, true);

		cc_debug_log_anything([
			'function' => 'cc_payment_record_payment',
			'form_data' => (array) $form_data,
		]);
        
		// save the new data
		$update_data = array();

        // Add payment intent id if we can
        if( isset( $_POST['PIID'] ) && $_POST['PIID'] != '' && $_POST['PIID'] != '0' ){
            $update_data['payment_intent_id'] = stripslashes( sanitize_text_field( $_POST['PIID'] ) );
        }
        
        // Add payment method and invoice details to form_data for this submission
        if( isset($_POST['pay-type']) ){
            $update_data['pay_type'] = stripslashes( sanitize_text_field( $_POST['pay-type'] ) );
        }
        
        // If invoice payment, add invoice details to form_data
        if( isset($_POST['pay-type']) && $_POST['pay-type'] == 'inv' ){
            $update_data['inv_org'] = isset($_POST['inv-org']) ? stripslashes( sanitize_text_field( $_POST['inv-org'] ) ) : '';
            $update_data['inv_addr1'] = isset($_POST['inv-addr1']) ? stripslashes( sanitize_text_field( $_POST['inv-addr1'] ) ) : '';
            $update_data['inv_addr2'] = isset($_POST['inv-addr2']) ? stripslashes( sanitize_text_field( $_POST['inv-addr2'] ) ) : '';
            $update_data['inv_town'] = isset($_POST['inv-town']) ? stripslashes( sanitize_text_field( $_POST['inv-town'] ) ) : '';
            $update_data['inv_county'] = isset($_POST['inv-county']) ? stripslashes( sanitize_text_field( $_POST['inv-county'] ) ) : '';
            $update_data['inv_postcode'] = isset($_POST['inv-postcode']) ? stripslashes( sanitize_text_field( $_POST['inv-postcode'] ) ) : '';
            $update_data['inv_country'] = isset($_POST['inv-country']) ? stripslashes( sanitize_text_field( $_POST['inv-country'] ) ) : '';
            $update_data['inv_name'] = isset($_POST['inv-name']) ? stripslashes( sanitize_text_field( $_POST['inv-name'] ) ) : '';
            $update_data['inv_email'] = isset($_POST['inv-email']) ? stripslashes( sanitize_email( $_POST['inv-email'] ) ) : '';
            $update_data['inv_phone'] = isset($_POST['inv-phone']) ? stripslashes( sanitize_text_field( $_POST['inv-phone'] ) ) : '';
            $update_data['inv_ref'] = isset($_POST['inv-ref']) ? stripslashes( sanitize_text_field( $_POST['inv-ref'] ) ) : '';
        }
        
        // If card payment, add client secret
        if( isset($_POST['client-secret']) && $_POST['client-secret'] != '' ){
            $update_data['client_secret'] = stripslashes( sanitize_text_field( $_POST['client-secret'] ) );
        }

        // add this data to the temp reg
        $success = TempRegistration::update_form_data( $current_token, '4', $update_data );

        // get a fresh complete copy
        $updated_data = TempRegistration::get_form_data( $current_token );

        // record the payment
		$response = cc_payment_record_payment_core_with_token( $current_token, $updated_data );
	}else{
		$response = array(
			'status' => 'error',
			'msg' => 'token invalid',
		);
	}
    echo json_encode($response);
    die();
}


// record the payment
// moved here so it can be called from the page as it loads for free payments
function cc_payment_record_payment_core(){
	ccpa_send_post_data_to_me();
	$payment = cc_paymentdb_empty_payment();
	$client_secret = '';
	$ccreg = '';

	if(isset($_POST['ccreg'])){
	    $ccreg = stripslashes( sanitize_text_field( $_POST['ccreg'] ) );
	}

	if( isset( $_POST['training-type'] ) ){
		switch ($_POST['training-type']) {
			case 'r':
				$payment['type'] = 'recording';
				break;
			case 's':
				$payment['type'] = 'series';
				break;
            case 'g':
                $payment['type'] = 'group';  // NEW: Set type as 'group'
                break;
		}
	}

    // Get group training data if it's a group registration
    $group_training = array();
    if($payment['type'] == 'group' && isset($_POST['training_id'])){
        $group_training = array_map('absint', (array) $_POST['training_id']);
        $group_training = array_filter($group_training);
        $group_training = array_unique($group_training);
        
        // Store group training data in payment notes or custom field
        $payment['notes'] = 'Group training: ' . implode(', ', array_map('get_the_title', $group_training));
        
        // For the main payment record, use the first training ID as workshop_id
        // We'll create individual records for each course later
        $payment['workshop_id'] = $group_training[0] ?? 0;
    } else {
        // Existing logic for other training types
        if(isset($_POST['training-id'])){
            $payment['workshop_id'] = absint($_POST['training-id']);
        }
    }

    /*
	if(isset($_POST['training-id'])){
		$payment['workshop_id'] = absint($_POST['training-id']);
	}
	*/
	if(isset($_POST['event-id'])){
		$payment['event_ids'] = stripslashes( sanitize_text_field( $_POST['event-id'] ) );
	}
	if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies()) ){
		$payment['currency'] = $_POST['currency'];
	}
	if(isset($_POST['student']) && $_POST['student'] == 'yes'){
	    $payment['student'] = 'y';
	}elseif(cc_workshop_price_earlybird($payment['workshop_id'])){
		$payment['earlybird'] = 'y';
	}
	if(isset($_POST['vat_exempt']) && ($_POST['vat_exempt'] == 'y' || $_POST['vat_exempt'] == 'n')){
	    $payment['vat_exempt'] = $_POST['vat_exempt'];
	}
	if(isset($_POST['voucher_code'])){
	    $payment['voucher_code'] = substr( cc_voucher_code_raw( stripslashes( sanitize_text_field( $_POST['voucher_code'] ) ) ), 0, 12);
	}
	if(isset($_POST['voucher_amount'])){
	    $payment['voucher_amount'] = (float) $_POST['voucher_amount'];
	}
	/*
	if(isset($_POST['tot_pay'])){
	    $payment['payment_amount'] = (float) $_POST['tot_pay'];
	}
	*/
	if(isset($_POST['total_payable'])){
	    $payment['payment_amount'] = (float) $_POST['total_payable'];
	}
	if(isset($_POST['vat-uk']) && ($_POST['vat-uk'] == 'y' || $_POST['vat-uk'] == 'n')){
	    $payment['vat_uk'] = $_POST['vat-uk'];
	}
	if(isset($_POST['vat-employ'])){
	    $payment['vat_employ'] = substr( stripslashes( sanitize_text_field( $_POST['vat-employ'] ) ), 0, 25);
	}
	if(isset($_POST['vat-employer'])){
	    $payment['vat_employer'] = substr( stripslashes( sanitize_text_field( $_POST['vat-employer'] ) ),0 , 255);
	}
	/*
	if(isset($_POST['attend_first']) && $_POST['attend_first'] <> ''){
	    $payment['attendee_firstname'] = substr( stripslashes( sanitize_text_field( $_POST['attend_first'] ) ),0 , 50);
	}
	if(isset($_POST['attend_last']) && $_POST['attend_last'] <> ''){
	    $payment['attendee_lastname'] = substr( stripslashes( sanitize_text_field( $_POST['attend_last'] ) ), 0, 50);
	}
	if(isset($_POST['attend_email']) && $_POST['attend_email'] <> ''){
	    $payment['attendee_email'] = substr( stripslashes( sanitize_email( $_POST['attend_email'] ) ), 0, 255);
	}
	if(isset($_POST['inv-address'])){
	    $payment['inv_address'] = stripslashes( sanitize_text_field( $_POST['inv-address'] ) );
	}
	*/
	if(isset($_POST['inv-email'])){
	    $payment['inv_email'] = substr( stripslashes( sanitize_email( $_POST['inv-email'] ) ), 0, 255);
	}
	if(isset($_POST['inv-phone'])){
	    $payment['inv_phone'] = substr( stripslashes( sanitize_text_field( $_POST['inv-phone'] ) ), 0, 30);
	}
	if(isset($_POST['inv-ref'])){
	    $payment['inv_ref'] = substr( stripslashes( sanitize_text_field( $_POST['inv-ref'] ) ), 0, 255);
	}
	if(isset($_POST['inv-org'])){
	    $payment['inv_org'] = substr( stripslashes( sanitize_text_field( $_POST['inv-org'] ) ), 0, 255);
	}
	if(isset($_POST['inv-addr1'])){
	    $payment['inv_addr1'] = substr( stripslashes( sanitize_text_field( $_POST['inv-addr1'] ) ), 0, 255);
	}
	if(isset($_POST['inv-addr2'])){
	    $payment['inv_addr2'] = substr( stripslashes( sanitize_text_field( $_POST['inv-addr2'] ) ), 0, 255);
	}
	if(isset($_POST['inv-town'])){
	    $payment['inv_town'] = substr( stripslashes( sanitize_text_field( $_POST['inv-town'] ) ), 0, 255);
	}
	if(isset($_POST['inv-county'])){
	    $payment['inv_county'] = substr( stripslashes( sanitize_text_field( $_POST['inv-county'] ) ), 0, 255);
	}
	if(isset($_POST['inv-postcode'])){
	    $payment['inv_postcode'] = substr( stripslashes( sanitize_text_field( $_POST['inv-postcode'] ) ), 0, 255);
	}
	if(isset($_POST['inv-country'])){
	    $payment['inv_country'] = substr( stripslashes( sanitize_text_field( $_POST['inv-country'] ) ), 0, 255);
	}
	if(isset($_POST['inv-name'])){
	    $payment['inv_name'] = substr( stripslashes( sanitize_text_field( $_POST['inv-name'] ) ), 0, 255);
	}

	if(isset($_POST['upsell_workshop_id'])){
	    $payment['upsell_workshop_id'] = (int) $_POST['upsell_workshop_id'];
	}
	if(isset($_POST['source'])){
	    $payment['source'] = substr( stripslashes( sanitize_text_field( $_POST['source'] ) ), 0, 255);
	}
	if( isset( $_POST['mailing_list'] ) && $_POST['mailing_list'] == 'yes' ){
		$payment['mailing_list'] = 'y';
	}
	if(isset($_POST['conf_email']) && $_POST['conf_email'] <> ''){
	    $payment['notes'] = 'Manager email: '.stripslashes( sanitize_text_field( $_POST['conf_email'] ) );
	}
	if(isset($_POST['client-secret']) && $_POST['client-secret'] <> ''){
		$client_secret = stripslashes( sanitize_text_field( $_POST['client-secret'] ) );
		// Jan 2025 (v2.0) not sure we need to do this anymore but I have left it in place just in case .....
		$payment['token'] = ccpa_payint_client_secret_get_pi_id( $client_secret );
	}
	$payment_intent_id = 0;
	if( isset( $_POST['PIID'] ) && $_POST['PIID'] <> '' ){
		$payment['payment_intent_id'] = stripslashes( sanitize_text_field( $_POST['PIID'] ) );
		// we'll use that to get the charge_id when we save the payment
	}

	$attendees = cc_attendees_reg_get($ccreg);
	$count_attendees = count($attendees);
	if($count_attendees < 1){
		$count_attendees = 1;
	}

	if(isset($_POST['vat_amount'])){
	    $payment['vat_included'] = round( $count_attendees * (float) $_POST['vat_amount'], 2);
	}
	if(isset($_POST['disc_amount'])){
	    $payment['disc_amount'] = round( $count_attendees * (float) $_POST['disc_amount'], 2);
	}
	if(isset($_POST['disc_code']) && $payment['disc_amount'] > 0){
	    $payment['disc_code'] = substr( stripslashes( sanitize_text_field( $_POST['disc_code'] ) ), 0, 25);
	}

	if(isset($_POST['pay-type']) && $_POST['pay-type'] == 'inv'){
		$payment['pmt_method'] = 'invoice';
	}else{
		$payment['pmt_method'] = 'online';
	}
	if($payment['payment_amount'] == 0){
		$payment['status'] = 'Payment not needed';
		$payment['pmt_method'] = 'free';
	}else{
		if($payment['pmt_method'] == 'online'){
			$payment['status'] = 'Payment successful: ';
		}else{
			$payment['status'] = 'Invoice requested';
		}
	}

	if($payment['vat_uk'] == 'y'){
		$payment['vat_employ'] = $payment['vat_employer'] = '';
	}

	if( $payment['upsell_workshop_id'] > 0 ){
		$payment['disc_code'] = 'UPSELL';
		$upsell = cc_upsells_offer( $payment['workshop_id'] );
		if( $upsell <> NULL && $payment['payment_amount'] > 0 && $upsell['discount'] > 0 ){
			// discount will be expressed as a percentage eg 10.0000
			$undiscounted_amount = $payment['payment_amount'] / ( 1 - $upsell['discount'] / 100);
			$payment['disc_amount'] = round( $undiscounted_amount - $payment['payment_amount'], 2 );
			// we need to calculate the upsell_payment_amount
			// this is the actual amount paid for the upsell after discount applied
			$payment['upsell_payment_amount'] = cc_payment_upsell_payment_amount( $payment['upsell_workshop_id'], $payment['currency'], $upsell['discount'] );
		}
	}

	$user = wp_get_current_user();
	$payment['reg_userid'] = $user->ID;
	$payment['firstname'] = $user->user_firstname;
	$payment['lastname'] = $user->user_lastname;
	$payment['email'] = $user->user_email;
	$payment['phone'] = get_user_meta($user->ID, 'phone', true);
	$payment['address'] = cc_users_user_address($user->ID, 'string');

	if( $payment['mailing_list'] == '' && cc_mailsterint_on_newsletter( $user->user_email ) ){
		$payment['mailing_list'] = 'p';
	}

	/* attendees now added to their table in a bit
	if($payment['attendee_email'] <> ''){
		if($payment['attendee_email'] == $payment['email']){
			$payment['attendee_email'] = $payment['attendee_firstname'] = $payment['attendee_lastname'] = '';
		}else{
			$attendee = cc_users_get_user($payment['attendee_email']);
			if($attendee){
				$payment['att_userid'] = $attendee->ID;
			}else{
				// we'll add the attendee to the user table
		        // user_login can only be 60 chars. Uniqid is 13 chars.
		        $user_login = substr( $payment['attendee_firstname'].' '.$payment['attendee_lastname'], 0, 46).' '.uniqid();
		        $args = array(
		            'user_login' => $user_login,
		            'user_pass' => wp_generate_password(),
		            'user_email' => $payment['attendee_email'],
		            'first_name' => $payment['attendee_firstname'],
		            'last_name' => $payment['attendee_lastname'],
		        );
		        $payment['att_userid'] = wp_insert_user($args);
		        update_user_meta($payment['att_userid'], 'source', 'attendee');
			}
		}
	}
	*/

	// is this a duplicated payment (eg because the Internet is slow and JS got bored and sent it again)?
	$duplicate_id = cc_paymentdb_dejavu($payment['reg_userid'], $payment['workshop_id'], $ccreg);
	if($duplicate_id){
		// trigger an update, not insert
		$payment['id'] = $duplicate_id;
	}

	// updates payment and attendees and also the workshop/recording user tables
	$payment_id = cc_paymentdb_update_payment($payment, $ccreg);
	$payment['id'] = $payment_id;

	if( $payment['last_update'] == '' ){
		$payment['last_update'] = date( 'Y-m-d H:i:s' );
	}

	// if a refer a friend code was used, record the fact and allocate a credit to the code giver ... also blocks the recipient from using it a second time
	if( substr( $payment['disc_code'], 0, 3 ) == 'CC-' && !$duplicate_id ){
		cc_friend_code_used( $payment['disc_code'], $payment['reg_userid'], $payment['currency'], $payment['disc_amount'] );
	}

	// save last registration info (for CNWL people really)
	update_user_meta($user->ID, 'last_registration', date('Y-m-d H:i:s'));
	update_user_meta($user->ID, 'last_reg_id', $payment_id);

	// add them to the mailing list if needed
    if($payment['mailing_list'] == 'y'){
        update_user_meta($user->ID, 'mailing_list', 'y');
        cc_mailsterint_newsletter_subscribe($user);
    }
    cc_mailsterint_update_region( $user );

	$response = array();
	$response['status'] = 'ok';
	if($payment['payment_amount'] == 0){
		$response['msg'] = 'Many thanks for your registration. A confirmation email is on its way to you now<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
	}elseif($payment['pmt_method'] == 'online'){
		$response['msg'] = 'Many thanks for your payment. A confirmation email is on its way to you now<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
	}else{
		$response['msg'] = 'Many thanks for your interest. A confirmation email is on its way to you now.<br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong><br>We will also send an invoice out to you shortly.';
	}
	
	// for Google Ads conversion tracking ...
	$response['conversion'] = $payment['payment_amount'].'|'.$payment['currency'].'|'.$client_secret.'|'.$user->user_email;

	$access_type = ( $payment['pmt_method'] == 'invoice' ) ? 'invoice' : 'paid';
    $attendees = cc_attendees_for_payment($payment_id);

    // NEW: Handle group training - create individual training records
    if($payment['type'] == 'group' && !empty($group_training)){
        foreach($group_training as $training_id){
            // Insert into training groups table
            cc_training_groups_record_training($payment_id, $training_id);
            
            // Create individual payment records for tracking
            $individual_payment = $payment;
            $individual_payment['id'] = 0; // Force new record
            $individual_payment['workshop_id'] = $training_id;
            $individual_payment['payment_ref'] = $payment_id; // Link to main payment
            $individual_payment['payment_amount'] = 0; // Individual amount tracking if needed
            $individual_payment['notes'] = 'Part of group registration #' . $payment_id;
            
            // Set type based on training type
            if(get_post_type($training_id) == 'workshop'){
                $individual_payment['type'] = '';
            } else {
                $individual_payment['type'] = 'recording';
            }
            
            $individual_payment_id = cc_paymentdb_update_payment($individual_payment);
            
            // Give recording access if it's a recording
            if($individual_payment['type'] == 'recording'){
                $attendees = cc_attendees_for_payment($payment_id);
                $access_type = $payment['payment_amount'] > 0 ? 'paid' : 'free';
                foreach($attendees as $attendee){
                    ccrecw_add_recording_to_user(
                        $attendee['user_id'], 
                        $training_id, 
                        $access_type, 
                        0, // Individual amount - could be calculated if needed
                        '', 
                        $payment['currency'], 
                        0, // VAT for individual
                        $individual_payment_id
                    );
                }
            }
            
            // Update popularity stats for each training
            cc_training_popularity_update($training_id, 1);
        }
    }elseif( $payment['type'] == 'series' ){
	    // now, if it's a series purchase, repeat most of the above for the individual trainings
	    // also give attendees recording access where appropriate
    	$series_id = $payment['workshop_id'];
	    // get all the training ids for the series
	    $series_courses = get_post_meta( $series_id, '_series_courses', true );
	    $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
	    $series_courses = is_array($series_courses) ? $series_courses : [];

	    foreach ( $series_courses as $training_id ) {
	    	$training_payment = $payment;
	    	// and change the payment data
	    	$training_payment['id'] = 0;
	    	$training_payment['payment_amount'] = 0;
	    	$training_payment['vat_included'] = 0;
	    	$training_payment['vat_exempt'] = '';
	    	$training_payment['payment_ref'] = $series_id;
	    	$training_payment['disc_amount'] = 0;
	    	$training_payment['pmt_method'] = '';
	    	$training_payment['inv_address'] = '';
	    	$training_payment['inv_email'] = '';
	    	$training_payment['inv_phone'] = '';
	    	$training_payment['inv_ref'] = '';
	    	$training_payment['workshop_id'] = $training_id;
	    	$training_payment['upsell_workshop_id'] = 0;
	    	if( course_training_type( $training_id ) =='workshop' ){
	    		$training_payment['type'] = '';
	    	}else{
	    		$training_payment['type'] = 'recording';
	    	}
	    	$training_payment['stripe_fee'] = 0;
	    	$training_payment['vat_uk'] = '';
	    	$training_payment['vat_employ'] = '';
	    	$training_payment['vat_employer'] = '';
	    	$training_payment['vat_website'] = '';
	    	$training_payment['student'] = '';
	    	$training_payment['earlybird'] = '';
	    	$training_payment['voucher_code'] = '';
	    	$training_payment['voucher_amount'] = 0;
	    	$training_payment['upsell_payment_amount'] = 0;
	    	$training_payment['payment_intent_id'] = '';
	    	$training_payment['charge_id'] = '';
	    	$training_payment['invoice_no'] = '';
	    	$training_payment['invoice_id'] = '';

			$training_payment_id = cc_paymentdb_update_payment($training_payment, $ccreg);

			$training_payment['id'] = $training_payment_id;

			if( $training_payment['type'] == 'recording' ){
			    foreach ($attendees as $attendee) {
			        ccrecw_add_recording_to_user( $attendee['user_id'], $training_id, $access_type, 0, '', '', 0, $training_payment_id);
			    }
			}

			// cc_mailster_add_attendees_to_training( $training_payment, true ); // and send attendee emails
	    }
    }else{
		// and also give recording access to attendees
		if($payment['type'] == 'recording'){
		    foreach ($attendees as $attendee) {
		        ccrecw_add_recording_to_user($attendee['user_id'], $payment['workshop_id'], $access_type, $payment['payment_amount'], '', $payment['currency'], cc_workshop_pricing_vat_included($payment['payment_amount']), $payment_id);
		    }
		}
		// upsell recording?
		if( $payment['upsell_workshop_id'] > 0 && course_training_type( $payment['upsell_workshop_id'] ) == 'recording' ){
		    foreach ($attendees as $attendee) {
		        ccrecw_add_recording_to_user($attendee['user_id'], $payment['upsell_workshop_id'], $access_type, $payment['payment_amount'], '', $payment['currency'], cc_workshop_pricing_vat_included($payment['payment_amount']), $payment_id);
		    }
		}
    }

	if($payment['voucher_code'] <> '' && !$duplicate_id){
		if( substr($payment['voucher_code'], 0, 3) == 'CC-' ){
			// refer a friend redemption
			cc_friend_redeem( $payment['voucher_code'], $payment['reg_userid'], $payment['currency'], $payment['voucher_amount'] );
		}else{
			ccpa_voucher_usage_record($payment['voucher_code'], $payment['voucher_amount'], $payment['currency'], $payment_id);
		}
	}

	// send out the emails (and adds people to the training and registration lists)
	// if it's a duplicate they may get the emails twice
	// cc_mailsterint_send_email($payment);
	cc_mailsterint_send_reg_emails( $payment );

	// does the payment result in a voucher being sent?
	if(!$duplicate_id){
		ccpa_vouchers_alloc_maybe($payment);
	}

	// also update the popularity stats
	if( !$duplicate_id ){
		cc_training_popularity_update( $payment['workshop_id'], $count_attendees );
		if( $payment['upsell_workshop_id'] > 0 ){
			cc_training_popularity_update( $payment['upsell_workshop_id'], $count_attendees );
		}
	}

	return $response;
}

// NEW: Function to record training groups
function cc_training_groups_record_training($payment_id, $course_id){
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cc_training_groups';
    
    return $wpdb->insert(
        $table_name,
        array(
            'payment_id' => $payment_id,
            'course_id' => $course_id,
        ),
        array('%d', '%d')
    );
}

// NEW: Function to get group training courses for a payment
function cc_training_groups_get_courses($payment_id){
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cc_training_groups';
    
    return $wpdb->get_col($wpdb->prepare(
        "SELECT course_id FROM $table_name WHERE payment_id = %d",
        $payment_id
    ));
}

// no payment needed
function cc_payment_free_panel(){
	global $rpm_theme_options;
	$html = '
		<div class="animated-card">
			<div class="reg-pay-panel reg-panel wms-background animated-card-inner pale-bg">
				<div class="reg-pay">
					<h2>Thanks!</h2>
					<h4>Many thanks for your registration.</h4>
					<p>A confirmation email is on its way to you now<br>
						<strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>
					</p>
				</div>
			</div>
		</div>';
	return wms_tidy_html( $html );
}

// get the non-discounted registration amount for a given payment (excl VAT)
function cc_payment_non_disc_amount( $payment, $wanted_curr='' ){
	$response = array(
		'currency' => 'GBP',
		'amount' => 0.0,
	);
	$currency = $wanted_curr == '' ? $payment['currency'] : $wanted_curr;
	if( $payment['type'] == 'recording' ){
		$result = cc_recording_price( $payment['workshop_id'], $currency, $payment['last_update'] );
	}else{
		$student = $payment['student'] == 'y' ? 'yes' : '';
		$result = cc_workshop_price_exact( $payment['workshop_id'], $currency, $payment['event_ids'], $student, $payment['last_update']);
	}
	$response['currency'] = $result['curr_found'];
	$response['amount'] = (float)$result['raw_price'];
	return $response;
}

// get the user_id of a payment
function cc_payment_user_id( $payment ){
	if( $payment['reg_userid'] > 0 ){
		return $payment['reg_userid'];
	}
	$user_id = cc_myacct_get_user( $payment, 'r' );
	return $user_id;
}

// calculates the discounted payment amount for the training added in an upsell
// $when (Y-m-d H:i:s) is used to lookup a past price
function cc_payment_upsell_payment_amount( $training_id, $currency, $discount, $when='' ){
	if( course_training_type( $training_id ) == 'workshop' ){
		$training_pricing = cc_workshop_price( $training_id, $currency, $when );
	}else{
		$training_pricing = cc_recording_price( $training_id, $currency, $when );
	}
	if( ! isset( $training_pricing['raw_price'] ) || $training_pricing['raw_price'] == 0 || $discount >= 100 ){
		return 0;
	}
	if( $discount <= 0 ){
		return $training_pricing['raw_price'];
	}
	return round( $training_pricing['raw_price'] - $training_pricing['raw_price'] * $discount / 100, 2);
}

// returns an invoice address from a payment record
// inv_address used to be used for free format text, now the address fields have to be completed
// $format can be 'array', 'string' 
function cc_payment_invoice_address( $payment, $format ){
	$invoice_address = array(
		'inv_name' => '',
		'inv_org' => '',
		'inv_addr1' => '',
		'inv_addr2' => '',
		'inv_town' => '',
		'inv_county' => '',
		'inv_postcode' => '',
		'inv_country' => '', // a 2 char code
		'inv_country_name' => '',
	);
	if( $payment['inv_address'] <> '' ){
		if( $format == 'string' ){
			return $payment['inv_address'];
		}
		if( $format == 'array' ){
			$invoice_address['inv_addr1'] = $payment['inv_address'];
			return $invoice_address;
		}
	}else{
		foreach ( $invoice_address as $key => $value ) {
		    if ( array_key_exists( $key, $payment ) ) {
		        $invoice_address[$key] = $payment[$key];
		    }
		}
		$invoice_address['inv_country_name'] = ccpa_countries_name( $payment['inv_country'] );

		if( $format == 'string' ){
			unset( $invoice_address['inv_country'] );
			// array_filter() removes any elements that are considered "empty" (i.e. '', null, false, 0, etc.).
			return implode( ', ', array_filter( $invoice_address ) );
		}
		if( $format == 'array' ){
			return $invoice_address;
		}
	}
	return false;
}

// Add this function to handle user creation at payment time
// This could go in payment.php or a utilities file

/**
 * Create or retrieve user from temp registration data
 * Called when payment is being processed
 * 
 * @param string $token The registration token
 * @return int|false User ID on success, false on failure
 */
function cc_create_user_from_temp_registration($token) {
    // Get the registration data
    $registration = TempRegistration::get($token);
    if (!$registration) {
        cc_debug_log_anything([
            'function' => 'cc_create_user_from_temp_registration',
            'error' => 'No registration found for token',
            'token' => $token
        ]);
        return false;
    }
    
    $form_data = json_decode($registration->form_data, true);

    $user = cc_users_get_user( $form_data['email'] );

    if( $user ){
    	$user_id = $user->ID;
    	$new_user = false;
    }else{
        // Create the new user
        $username = substr($form_data['firstname'] . ' ' . $form_data['lastname'], 0, 46) . ' ' . uniqid();
        $password = wp_generate_password();
        
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $form_data['email'],
            'first_name' => $form_data['firstname'],
            'last_name' => $form_data['lastname'],
            'role' => 'subscriber'
        ]);
        
        if (is_wp_error($user_id)) {
            cc_debug_log_anything([
                'function' => 'cc_create_user_from_temp_registration',
                'error' => 'Failed to create user',
                'wp_error' => $user_id->get_error_message(),
                'email' => $form_data['email']
            ]);
            return false;
        }
        $new_user = true;
    }

    // Add all the user meta
    update_user_meta($user_id, 'address_line_1', $form_data['address_line_1']);
    update_user_meta($user_id, 'address_line_2', $form_data['address_line_2']);
    update_user_meta($user_id, 'address_town', $form_data['address_town']);
    update_user_meta($user_id, 'address_county', $form_data['address_county']);
    update_user_meta($user_id, 'address_postcode', $form_data['address_postcode']);
    update_user_meta($user_id, 'address_country', $form_data['address_country']);
    update_user_meta($user_id, 'phone', $form_data['phone']);
    update_user_meta($user_id, 'job', $form_data['job']);

    if( $new_user ){
	    update_user_meta($user_id, 'source', $form_data['source']);
	    update_user_meta($user_id, 'mailing_list', $form_data['mailing_list']);
	}
    
    // NLFT specific fields if present
    if (isset($form_data['org_name'])) {
        update_user_meta($user_id, 'org_name', $form_data['org_name']);
    }
    if (isset($form_data['nlft_service_type'])) {
        update_user_meta($user_id, 'nlft_service_type', $form_data['nlft_service_type']);
    }
    if (isset($form_data['nlft_borough'])) {
        update_user_meta($user_id, 'nlft_borough', $form_data['nlft_borough']);
    }
    if (isset($form_data['nlft_team'])) {
        update_user_meta($user_id, 'nlft_team', $form_data['nlft_team']);
    }
        
	if( $new_user ){
        // Set new user flags
        update_user_meta($user_id, 'last_login', 'never');
        update_user_meta($user_id, 'new_password', '');
        update_user_meta($user_id, 'force_new_password', 'yes');
        // Update the temp registration with the new user ID
        TempRegistration::update_user_info($token, $user_id, $form_data['email']);
    }
        
    cc_debug_log_anything([
        'function' => 'cc_create_user_from_temp_registration',
        'success' => 'User created',
        'user_id' => $user_id,
        'email' => $form_data['email']
    ]);
    
    return $user_id;
}
    

