<?php
/**
 * Vouchers - allocation
 * the functions needed to check if a voucher should be allocated following a purchase as well as actually allocating them
 */

// check and, if appropriate allocate the voucher
function ccpa_vouchers_alloc_maybe($payment){
	if( ccpa_vouchers_alloc_check($payment) ){
		$voucher_code = ccpa_vouchers_alloc_generate();
		$voucher = ccpa_vouchers_create_new($voucher_code, $payment['currency']);
        // Check if voucher creation was successful
        if(!$voucher || !isset($voucher['id']) || $voucher['id'] == 0){
            ccpa_write_log('Function ccpa_vouchers_alloc_maybe: Failed to create voucher for payment ID: ' . $payment['id']);
            return false;
        }
		// link the issuing payment with the voucher
		$voucher_pmts = array(
			'id' => 0,
			'voucher_code' => $voucher_code,
			'payment_id' => $payment['id'],
			'type' => 'issue',
			'amount' => $voucher['amount'],
		);
		$voucher_pmts_id = ccpa_voucher_payments_table_update($voucher_pmts);
		ccpa_vouchers_new_tell_payee($payment['email'], $voucher_code);
	}
}


// Should this purchase result in a new voucher being allocated?
// returns true or false
function ccpa_vouchers_alloc_check($payment){
	$offer_settings = cc_voucher_core_offer_settings();
	if($offer_settings['active'] == 'no') return false;

	$today = date('Y-m-d');
	if($offer_settings['offer_start'] <> ''){
		$date = date_create_from_format('d/m/Y', $offer_settings['offer_start']);
		if($date){
			if($today < $date->format('Y-m-d')) return false;
		}
	}
	if($offer_settings['offer_end'] <> ''){
		$date = date_create_from_format('d/m/Y', $offer_settings['offer_end']);
		if($date){
			if($today > $date->format('Y-m-d')) return false;
		}
	}

	if($payment === NULL) return false;
	
	// protect the next comparison
	if( ! in_array( $payment['currency'], cc_valid_currencies() ) ) return false;
	if( $payment['payment_amount'] <= 0 ) return false;
	$min_sale = 0;
	if( isset( $offer_settings['min_sale'] ) ){
		$min_sale = (float) $offer_settings['min_sale'];
	}
	if( cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] ) < $min_sale ) return false;
	if($payment['pmt_method'] <> 'unknown'){
		if($offer_settings['pmt_method'] <> 'any' && $offer_settings['pmt_method'] <> $payment['pmt_method']) return false;
	}

	// for the portal people ...
	if(cc_users_is_valid_user_logged_in()){
		$portal_user = get_user_meta(get_current_user_id(), 'portal_user', true);
		if($portal_user <> ''){
			return false;
		}
	}

	$training_type = course_training_type( $payment['workshop_id'] );
	if( cc_voucher_applies_to_course( $offer_settings, $payment['workshop_id'], $training_type ) ){
		return true;
	}

	if( $payment['upsell_workshop_id'] > 0 ){
		$training_type = course_training_type( $payment['upsell_workshop_id'] );
		if( cc_voucher_applies_to_course( $offer_settings, $payment['upsell_workshop_id'], $training_type ) ){
			return true;
		}
	}

	return false;
}


/**
 * Check if a voucher applies to a specific training type
 * 
 * @param array $offer_voucher The voucher settings array
 * @param string $training_type The type to check ('workshop', 'recording', or 'series')
 * @return bool True if the voucher applies to this type
 */
function cc_voucher_applies_to_type($offer_voucher, $training_type) {
    // Handle backwards compatibility - if using old course_type field
    if (!isset($offer_voucher['course_types']) && isset($offer_voucher['course_type'])) {
        switch($offer_voucher['course_type']) {
            case 'any':
                return true;
            case 'workshop':
                return $training_type === 'workshop';
            case 'recording':
                return $training_type === 'recording';
            default:
                return true; // Default to allowing if unknown
        }
    }
    
    // Using new course_types array
    if (isset($offer_voucher['course_types'])) {
        // If array is empty or has all 3 types, it applies to any
        if (empty($offer_voucher['course_types']) || count($offer_voucher['course_types']) >= 3) {
            return true;
        }
        return in_array($training_type, $offer_voucher['course_types']);
    }
    
    // Default to true if no restrictions set
    return true;
}

/**
 * Check if a voucher applies to a specific course ID
 * 
 * @param array $offer_voucher The voucher settings array
 * @param int $course_id The course/post ID to check
 * @param string $course_type The type of course ('workshop', 'recording', or 'series')
 * @return bool True if the voucher applies to this specific course
 */
function cc_voucher_applies_to_course($offer_voucher, $course_id, $course_type) {
    // First check if the voucher applies to this type at all
    if (!cc_voucher_applies_to_type($offer_voucher, $course_type)) {
        return false;
    }
    
    // Then check if there are specific course restrictions
    switch($course_type) {
        case 'workshop':
            if (isset($offer_voucher['workshops']) && !empty($offer_voucher['workshops'])) {
                return in_array($course_id, $offer_voucher['workshops']);
            }
            break;
            
        case 'recording':
            if (isset($offer_voucher['recordings']) && !empty($offer_voucher['recordings'])) {
                return in_array($course_id, $offer_voucher['recordings']);
            }
            break;
            
        case 'series':
            if (isset($offer_voucher['series']) && !empty($offer_voucher['series'])) {
                return in_array($course_id, $offer_voucher['series']);
            }
            break;
    }
    
    // If no specific courses are selected for this type, voucher applies to all courses of this type
    return true;
}







/*
// Should this purchase result in a new voucher being allocated?
// returns true or false
function ccpa_vouchers_alloc_check($payment){
	// ccpa_write_log('ccpa_vouchers_alloc_check');
	// ccpa_write_log($payment);
	// ccpa_write_log('1');
	$offer_settings = cc_voucher_core_offer_settings();
	if($offer_settings['active'] == 'no') return false;
	// ccpa_write_log('2');
	$today = date('Y-m-d');
	if($offer_settings['offer_start'] <> ''){
		$date = date_create_from_format('d/m/Y', $offer_settings['offer_start']);
		if($date){
			// ccpa_write_log('3');
			if($today < $date->format('Y-m-d')) return false;
		}
	}
	if($offer_settings['offer_end'] <> ''){
		$date = date_create_from_format('d/m/Y', $offer_settings['offer_end']);
		if($date){
			// ccpa_write_log('4');
			if($today > $date->format('Y-m-d')) return false;
		}
	}
	// ccpa_write_log('5');
	if($payment === NULL) return false;
	// ccpa_write_log('6');

	//  sometimes a payment is not actually a payment ... fake ones can have odd settings for type ...
	if( $payment['type'] == 'recording' || $payment['type'] == 'r' ){
		$payment_type = 'recording';
	}else{
		$payment_type = 'workshop';
	}

	// protect the next comparison
	if( ! in_array( $payment['currency'], cc_valid_currencies() ) ) return false;
	if( $payment['payment_amount'] <= 0 ) return false;
	$min_sale = 0;
	if( isset( $offer_settings['min_sale'] ) ){
		$min_sale = (float) $offer_settings['min_sale'];
	}
	if( cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] ) < $min_sale ) return false;

	// ccpa_write_log('7');
	if($payment['pmt_method'] <> 'unknown'){
		if($offer_settings['pmt_method'] <> 'any' && $offer_settings['pmt_method'] <> $payment['pmt_method']) return false;
	}
	// ccpa_write_log('8');
	if($offer_settings['course_type'] <> 'any' && ($offer_settings['course_type'] == 'workshop' && $payment_type == 'recording' || $offer_settings['course_type'] == 'recording' && $payment_type == 'workshop')) return false;
	if($payment_type == 'recording'){
		if(! empty( $offer_settings['recordings'] ) ){
			// ccpa_write_log('9');
			if( ! in_array($payment['workshop_id'], $offer_settings['recordings']) ) return false;
		}
	}else{
		if(! empty( $offer_settings['workshops'] ) ){
			// ccpa_write_log('10');
			if( in_array( $payment['workshop_id'], $offer_settings['workshops']) ) return true;
			if( $payment['upsell_workshop_id'] > 0 ){
				// ccpa_write_log('11');
				if( in_array( $payment['upsell_workshop_id'], $offer_settings['workshops']) ) return true;
			}
			// ccpa_write_log('12');
			return false;
		}
	}
	// for the CNWL people ...
	if(cc_users_is_valid_user_logged_in()){
		$portal_user = get_user_meta(get_current_user_id(), 'portal_user', true);
		if($portal_user <> ''){
			return false;
		}
	}
	// ccpa_write_log('13');
	return true;
}
*/

// generate a valid, unique voucher code
function ccpa_vouchers_alloc_generate(){
	$characters = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
	do {
		$voucher_code = '';
		for ($i=0; $i < 12; $i++) { 
			$voucher_code .= $characters[mt_rand(0, 61)];
		}
	} while (ccpa_voucher_table_get($voucher_code) !== NULL);
	return $voucher_code;
}

// create a new voucher row
function ccpa_vouchers_create_new($voucher_code, $currency, $amount=0){
	$offer_settings = cc_voucher_core_offer_settings();
	if($amount == 0){
		switch ($currency) {
			case 'GBP':		$amount = $offer_settings['offer_gbp'];		break;
			case 'AUD':		$amount = $offer_settings['offer_aud'];		break;
			case 'USD':		$amount = $offer_settings['offer_usd'];		break;
			case 'EUR':		$amount = $offer_settings['offer_eur'];		break;
		}
	}
    // Validate amount is greater than 0
    if($amount <= 0){
        ccpa_write_log('function ccpa_vouchers_create_new: Voucher creation failed: Invalid amount for currency ' . $currency);
        return false;
    }
	$voucher = array(
		'id' => 0,
		'voucher_code' => $voucher_code,
		'issue_time' => gmdate('Y-m-d H:i:s'),
		'expiry_time' => gmdate('Y-m-d', strtotime('+ '.$offer_settings['expiry_mths'].' months')) . ' 23:59:59',
		'currency' => $currency,
		'amount' => $amount,
		'redeemed' => 0,
		'balance' => $amount,
	);
	$voucher_id = ccpa_voucher_table_update($voucher);
	$voucher['id'] = $voucher_id;
	return $voucher;
}

// tell the payee all about their new voucher
function ccpa_vouchers_new_tell_payee($email, $voucher_code){
	$subscriber = mailster('subscribers')->get_by_mail($email);
	if($subscriber){
		$subscriber_id = $subscriber->ID;
	}else{
		// add subscriber
		$userdata = array(
            'email' => $email,
        );
        $overwrite = true;
        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
	}
	$voucher = ccpa_voucher_table_get($voucher_code);
	switch ($voucher['currency']) {
		case 'GBP':		$voucher_value = '&pound;'.number_format($voucher['amount'], 2);		break;
		case 'AUD':		$voucher_value = 'AU$'.number_format($voucher['amount'], 2);			break;
		case 'USD':		$voucher_value = 'US$'.number_format($voucher['amount'], 2);			break;
		case 'EUR':		$voucher_value = '€'.number_format($voucher['amount'], 2);				break;
	}
	$voucher_expiry = date('jS M Y', strtotime($voucher['expiry_time']));
    $mailster_tags = array(
        'voucher_code' => cc_voucher_core_pretty_voucher($voucher_code),
        'voucher_value' => $voucher_value,
        'voucher_expiry' => $voucher_expiry,
    );
    $mailster_hook = 'send_password_reset_email';
    sysctrl_mailster_ar_hook('voucher_issue_email', $subscriber_id, $mailster_tags);
}

