<?php
/**
 * Workshop pricing
 */

// get the workshop price (exclusive of VAT)
// used on the workshop page and when the currency is changed
// price_text returns either '' or a formatted price or a "from" price if there are multiple events or "Free"
// the returned currency may be GBP if the price was not found in the requested currency
// student discount price also returned if it applies (single-event only)
// earlybird pricing (single event only) will be used if it applies and will return an earlybird msg (eg "Early-bird rate valid until 30th Sep 2022 and then the normal price of £123.45 will apply")
// $when (Y-m-d H:i:s) is used to lookup a past price
function cc_workshop_price($workshop_id, $currency, $when=''){
	$response = array(
		'price_text' => '',
		'curr_found' => '',
		'raw_price' => 0,
		'student_price' => null,
		'student_price_formatted' => '',
		'earlybird_msg' => '',
		'non_early_price' => null,
	);
	// if the workshop has passed, we don't show prices
	if( $when == '' && ! workshops_show_this_workshop( $workshop_id ) ){
		return $response;
	}
	if(!in_array($currency, cc_valid_currencies())){
		$currency = 'GBP';
	}
	// check the events first ... we're looking for a "from" price
	$num_events = 0;
	$now = time();
	$curr_price = 999999.99;
	$gbp_price = 999999.99;
	for($i = 1; $i<16; $i++){
		$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
		if($event_name <> ''){
			if(workshop_event_in_future( $workshop_id, $i, $when )){
				$num_events ++;
				$event_free = get_post_meta( get_the_ID(), 'event_'.$i.'_free', true );
				if($event_free <> 'yes'){
					$event_price = workshops_event_price($workshop_id, $i, $currency);
					if($event_price > 0){
						if($event_price < $curr_price){
							$curr_price = $event_price;
						}
					}else{
						if($currency <> 'GBP'){
							$event_gbp_price = workshops_event_price($workshop_id, $i, 'GBP');
							if($event_gbp_price > 0 && $event_gbp_price < $gbp_price){
								$gbp_price = $event_gbp_price;
							}
						}
					}
				}
			}
		}
	}
	if($num_events > 1){
		if($curr_price < 999999.99 && $curr_price > 0){
			$response['raw_price'] = $curr_price;
			$response['price_text'] = 'From '.workshops_pretty_price($curr_price, $currency);
			$response['curr_found'] = $currency;
			return $response;
		}elseif($gbp_price < 999999.99 && $gbp_price > 0){
			$response['raw_price'] = $gbp_price;
			$response['price_text'] = 'From '.workshops_pretty_price($gbp_price, 'GBP');
			$response['curr_found'] = 'GBP';
			return $response;
		}
	}
	// use workshop pricing
	switch ($currency) {
		case 'GBP':
			$curr_price = get_post_meta($workshop_id, 'online_pmt_amt', true );
			break;
		case 'AUD':
			$curr_price = get_post_meta($workshop_id, 'online_pmt_aud', true );
			if($curr_price == '' || $curr_price == 0){
				$curr_price = get_post_meta($workshop_id, 'online_pmt_amt', true );
				$currency = 'GBP';
			}
			break;
		case 'USD':
			$curr_price = get_post_meta($workshop_id, 'online_pmt_usd', true );
			if($curr_price == '' || $curr_price == 0){
				$curr_price = get_post_meta($workshop_id, 'online_pmt_amt', true );
				$currency = 'GBP';
			}
			break;
		case 'EUR':
			$curr_price = get_post_meta($workshop_id, 'online_pmt_eur', true );
			if($curr_price == '' || $curr_price == 0){
				$curr_price = get_post_meta($workshop_id, 'online_pmt_amt', true );
				$currency = 'GBP';
			}
			break;
	}
	if($num_events <= 1 && $curr_price > 0){
		// is there a student discount?
		$student_discount = get_post_meta($workshop_id, 'student_discount', true);
		if($student_discount <> '' && $student_discount > 0){
			$discount_price = $curr_price - ($curr_price * $student_discount / 100);
			// however, we want this rounded up to the next multiple of 5
			$response['student_price'] = ceil($discount_price / 5) * 5;
			$response['student_price_formatted'] = workshops_price_prefix($currency).number_format($response['student_price'],2);
		}
		// earlybird
		if( $when == '' ){
			$timestamp = time();
		}else{
			$wanted_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $when );
			$timestamp = $wanted_date->getTimestamp();
		}
		$earlybird_discount = get_post_meta($workshop_id, 'earlybird_discount', true);
		$earlybird_expiry = get_post_meta($workshop_id, 'earlybird_expiry_date', true);
		if($earlybird_discount <> '' && $earlybird_discount > 0 && $earlybird_expiry <> ''){
			$expiry_date = DateTime::createFromFormat('d/m/Y H:i:s', $earlybird_expiry.' 23:59:59');
			if($expiry_date->getTimestamp() > $timestamp){
				$discount_price = $curr_price - ($curr_price * $earlybird_discount / 100);
				// however, we want this rounded up to the next multiple of 5
				$normal_price = $curr_price;
				// $curr_price = ceil($discount_price / 5) * 5;
				$curr_price = $discount_price;
				$response['non_early_price'] = workshops_price_prefix($currency).number_format($normal_price,2);
				$earlybird_name = get_post_meta($workshop_id, 'earlybird_name', true);
				if($earlybird_name == ''){
					$earlybird_name = 'Early-bird';
				}
				$response['earlybird_msg'] = $earlybird_name.' rate valid until '.$expiry_date->format('jS M Y').' and then the normal price of <span class="non-early-price">'.workshops_price_prefix($currency).number_format($normal_price,2).'</span> + VAT will apply.';
			}
		}
	}
	if($curr_price < 999999.99 && $curr_price > 0){
		$response['raw_price'] = $curr_price;
		$response['curr_found'] = $currency;
		$response['price_text'] = workshops_pretty_price($curr_price, $currency);
	}elseif( $curr_price == 0 ){
		$response['raw_price'] = $curr_price;
		$response['curr_found'] = $currency;
		$response['price_text'] = 'Free';
	}
	return $response;
}

// does earlybird apply now (recording or workshop!)
// returns bool
function cc_workshop_price_earlybird($training_id){
	$earlybird_discount = get_post_meta($training_id, 'earlybird_discount', true);
	if($earlybird_discount == '') $earlybird_discount = 0;
	$earlybird_expiry = get_post_meta($training_id, 'earlybird_expiry_date', true);
	if($earlybird_discount > 0 && $earlybird_expiry <> ''){
		$expiry_date = DateTime::createFromFormat('d/m/Y H:i:s', $earlybird_expiry.' 23:59:59');
		if($expiry_date->getTimestamp() > time()){
			return true;
		}
	}
	return false;
}

// gets the correct price for a workshop/event(s) for the payment page
// this is before VAT or discounts are applied
// this is also for one attendee
// $when (Y-m-d H:i:s) is used to lookup a past price
function cc_workshop_price_exact($workshop_id, $currency, $eventID, $student, $when=''){
	// ccpa_write_log('function cc_workshop_price_exact');
	// ccpa_write_log( array(
	// 	'workshop_id' => $workshop_id,
	// 	'currency' => $currency,
	// 	'eventID' => $eventID,
	// 	'student' => $student,
	// 	'when' => $when,
	// ) );
	$response = array(
		'raw_price' => 0,
		'curr_found' => $currency,
	);
	// if the workshop has passed, we don't show prices
	if( $when == '' && ! workshops_show_this_workshop( $workshop_id ) ){
		return false;
	}
	if(!in_array($response['curr_found'], cc_valid_currencies())){
		$response['curr_found'] = 'GBP';
	}
	$num_events = 0;
	if(workshop_is_multi_event($workshop_id)){
		$selected_events = explode(',', $eventID);
		$count_selected = 0;
		$now = time();
		for($i = 1; $i<16; $i++){
			$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
			if($event_name <> ''){
				$num_events ++;
				if(in_array($i, $selected_events)){
					$count_selected ++;
					$event_free = get_post_meta( $workshop_id, 'event_'.$i.'_free', true );
					if($event_free <> 'yes'){
						$event_price = workshops_event_price($workshop_id, $i, $response['curr_found']);
						if($event_price > 0){
							$response['raw_price'] = $response['raw_price'] + $event_price;
						}else{
							if($response['curr_found'] <> 'GBP'){
								$event_gbp_price = workshops_event_price($workshop_id, $i, 'GBP');
								if($event_gbp_price > 0){
									// switch to GBP
									$response['curr_found'] = 'GBP';
									$response['raw_price'] = $response['raw_price'] + $event_gbp_price;
								}
							}
						}
					}
				}
			}
		}
		if($num_events == $count_selected){
			// all events selected
			$all_events_discount = (float) get_post_meta($workshop_id, 'all_events_discount', true);
			if($all_events_discount > 0){
				$response['raw_price'] = round( $response['raw_price'] * ((100 - $all_events_discount) / 100), 2 );
			}
		}
	}else{
		if($response['curr_found'] == 'GBP'){
			$response['raw_price'] = get_post_meta($workshop_id, 'online_pmt_amt', true );
		}else{
			$response['raw_price'] = get_post_meta($workshop_id, 'online_pmt_'.strtolower($response['curr_found']), true );
			if($response['raw_price'] == '' || $response['raw_price'] == 0){
				$gbp_price = get_post_meta($workshop_id, 'online_pmt_amt', true );
				if($gbp_price > 0){
					$response['raw_price'] = $gbp_price;
					$response['curr_found'] = 'GBP';
				}
			}
		}
	}
	// ccpa_write_log($response);
	if($num_events <= 1 && $response['raw_price'] > 0){
		// should we apply a student discount?
		if($student == 'yes'){
			$student_discount = (float) get_post_meta($workshop_id, 'student_discount', true);
			if($student_discount > 0){
				$discount_price = $response['raw_price'] - ($response['raw_price'] * $student_discount / 100);
				// however, we want this rounded up to the next multiple of 5
				$response['raw_price'] = ceil($discount_price / 5) * 5;
			}
		}else{
			// earlybird
			$earlybird_discount = (float) get_post_meta($workshop_id, 'earlybird_discount', true);
			// ccpa_write_log('earlybird_discount: '.$earlybird_discount);
			$earlybird_expiry = get_post_meta($workshop_id, 'earlybird_expiry_date', true);
			// ccpa_write_log('earlybird_expiry: '.$earlybird_expiry);
			if($earlybird_discount > 0 && $earlybird_expiry <> ''){
				$expiry_date = DateTime::createFromFormat('d/m/Y H:i:s', $earlybird_expiry.' 23:59:59');
				if( $when == '' ){
					$timestamp = time();
				}else{
					$wanted_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $when );
					$timestamp = $wanted_date->getTimestamp();
				}
				// ccpa_write_log('timestamp: '.$timestamp);
				// ccpa_write_log('expiry timestamp: '.$expiry_date->getTimestamp());
				if( $expiry_date->getTimestamp() > $timestamp ){
					// $discount_price = $response['raw_price'] - ($response['raw_price'] * $earlybird_discount / 100);
					// round to whole pence/cents not that we're not going to multiples of 5
					$discount_pence = round( $response['raw_price'] * $earlybird_discount ); // eg 12.34 * 5 = 62, not 61.7
					$discount_price = $response['raw_price'] - ( $discount_pence / 100 );
					// however, we want this rounded up to the next multiple of 5
					// $normal_price = $response['raw_price'];
					// $response['raw_price'] = ceil($discount_price / 5) * 5;
					$response['raw_price'] = $discount_price;
				}
			}
		}
	}
	/*
	$response['price_text'] = workshops_price_prefix($response['curr_found']).number_format($curr_price,2);
	*/
	// ccpa_write_log($response);
	return $response;
}

// returns a payment amount with VAT added if necessary
function ccpa_payment_amount($amount){
	if($amount > 0){
		$ccpa_vat_option = esc_attr(get_option('ccpa_vat_option'));
		$ccpa_vat_rate = (float) get_option('ccpa_vat_rate');
		if($ccpa_vat_rate > 0){
			switch ($ccpa_vat_option) {
				case '':
					// no vat
					break;
				case 'excshow':
					// amount is vat exclusive
					$amount = round($amount + $amount * $ccpa_vat_rate / 100, 2);
					break;
				case 'incshow':
					// amount is VAT inclusive
					break;
				case 'inchide':
					// amount is VAT inclusive
					break;
				default:
					// if in doubt, do nothing
					break;
			}
		}
	}
	return $amount;
}

// returns a VAT amount
function ccpa_vat_amount($amount){
	$vat = 0;
	if($amount > 0){
		$ccpa_vat_option = esc_attr(get_option('ccpa_vat_option'));
		$ccpa_vat_rate = (float) get_option('ccpa_vat_rate');
		if($ccpa_vat_rate > 0){
			switch ($ccpa_vat_option) {
				case '':
					// no vat
					break;
				case 'excshow':
					// amount is vat exclusive
					$vat = round($amount * $ccpa_vat_rate * .01, 2);
					break;
				case 'incshow':
					// amount is VAT inclusive, to be shown
					$vat = round($amount - ($amount / (1 + ($ccpa_vat_rate * .01))), 2);
					break;
				case 'inchide':
					// amount is VAT inclusive, to be hidden
					$vat = round($amount - ($amount / (1 + ($ccpa_vat_rate * .01))), 2);
					break;
				default:
					// if in doubt, do nothing
					break;
			}
		}
	}
	return $vat;
}

// returns the amount of vat included in a payment
function cc_workshop_pricing_vat_included($amount){
	$vat_included = 0;
	if($amount > 0){
		$ccpa_vat_rate = (float) get_option('ccpa_vat_rate');
		$vat_included = round($amount - ($amount / (1 + ($ccpa_vat_rate * .01))), 2);
	}
	return $vat_included;
}