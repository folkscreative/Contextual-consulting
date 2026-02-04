<?php
/**
 * Recrding pricing
 */

// gets the recording price (exclusive of vat and without formatting)
// will return the discount (earlybird) price if it is set and applies now
// $when (Y-m-d H:i:s) is used to lookup a past price
// hacked to use course instead of recording
function cc_recording_price( $course_id, $currency, $when='' ){
	$response = array(
		'raw_price' => 0,
		'curr_found' => '',
		'student_price' => null,
		'student_price_formatted' => '',
		'earlybird_msg' => '',
		'non_early_price' => null,
	);
	if(!in_array($currency, cc_valid_currencies())){
		$currency = 'GBP';
	}
	$response['curr_found'] = $currency;
	$currency_lc = strtolower( $currency );

	$course_pricing = course_pricing_get( $course_id );

	$curr_price = 0;
	if($response['curr_found'] == 'GBP'){
		$curr_price = $course_pricing['price_gbp'];
		// $curr_price = get_post_meta( $recording_id, 'recording_price', true);
		if($curr_price == '' || !is_numeric($curr_price)){
			$curr_price = 0;
		}
	}else{
		$curr_price = $course_pricing['price_'.$currency_lc];
		// $curr_price = get_post_meta( $recording_id, 'recording_price_'.strtolower($response['curr_found']), true);
		if($curr_price == '' || !is_numeric($curr_price)){
			$curr_price = 0;
		}
		if($curr_price == 0){
			$curr_price = $course_pricing['price_gbp'];
			// $curr_price = get_post_meta( $recording_id, 'recording_price', true);
			if($curr_price == '' || !is_numeric($curr_price)){
				$curr_price = 0;
			}
			if($curr_price > 0){
				$response['curr_found'] = 'GBP';
			}
		}
	}
	if($curr_price > 0){
		// is there a student discount?
		// $student_discount = get_post_meta($recording_id, 'student_discount', true);
		if($course_pricing['student_discount'] <> '' && $course_pricing['student_discount'] > 0){
			$discount_price = $curr_price - ($curr_price * $course_pricing['student_discount'] / 100);
			// however, we want this rounded up to the next multiple of 5
			$response['student_price'] = ceil($discount_price / 5) * 5;
			$response['student_price_formatted'] = workshops_price_prefix($currency).number_format($response['student_price'],2);
		}
		// earlybird
		// $earlybird_discount = get_post_meta($recording_id, 'earlybird_discount', true);
		if($course_pricing['early_bird_discount'] == '') $course_pricing['early_bird_discount'] = 0;
		// $earlybird_expiry = get_post_meta($recording_id, 'earlybird_expiry_date', true);
		if($course_pricing['early_bird_discount'] > 0 && $course_pricing['early_bird_expiry'] <> NULL){
			// $expiry_date = DateTime::createFromFormat('d/m/Y H:i:s', $course_pricing['early_bird_expiry'].' 23:59:59');
			$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $course_pricing['early_bird_expiry'] );
			if( $expiry_date ){
				if( $when == '' ){
					$timestamp = time();
				}else{
					$wanted_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $when );
					$timestamp = $wanted_date->getTimestamp();
				}
				// ccpa_write_log('Function cc_recording_price timestamp = '.$timestamp.', expiry_date timestamp = '.$expiry_date->getTimestamp().', recording_id ='.$recording_id.', currency = '.$currency.', when = '.$when.', eb discount = '.$earlybird_discount.', eb expiry = '.$earlybird_expiry);
				if( $expiry_date->getTimestamp() > $timestamp ){
					$discount_pence = round( $curr_price * $course_pricing['early_bird_discount'] );
					$discount_price = $curr_price - ( $discount_pence / 100 );
					// however, we want this rounded up to the next multiple of 5
					$normal_price = $curr_price;
					
					// temp change to not round up ...
					// $curr_price = ceil($discount_price / 5) * 5;
					$curr_price = $discount_price;
					
					$response['non_early_price'] = workshops_price_prefix($currency).number_format($normal_price,2);
					// $earlybird_name = get_post_meta($recording_id, 'earlybird_name', true);
					if($course_pricing['early_bird_name'] == ''){
						$course_pricing['early_bird_name'] = 'Early-bird';
					}
					$response['earlybird_msg'] = $course_pricing['early_bird_name'].' rate valid until '.$expiry_date->format('jS M Y').' and then the normal price of <span class="non-early-price">'.workshops_price_prefix($currency).number_format($normal_price,2).'</span> + VAT will apply.';
				}
			}
		}
	}
	$response['raw_price'] = $curr_price;
	return $response;
}