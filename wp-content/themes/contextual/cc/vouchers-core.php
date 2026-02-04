<?php
/**
 * Vouchers core functions
 */

// retrieve the automatic voucher settings
function cc_voucher_core_offer_settings(){
	$offer_settings = get_option('voucher_offer_settings');
	if(!$offer_settings){
		$offer_settings = array(
			'active' => 'no',
			'offer_gbp' => 0,
			'offer_aud' => 0,
			'offer_usd' => 0,
			'offer_eur' => 0,
			'min_sale' => 0, // gbp - total sale price
			'pmt_method' => 'any',
			'offer_start' => '', // dd/mm/yyyy
			'offer_end' => '', // dd/mm/yyyy
			'course_type' => 'any',
			'course_types' => array('workshop', 'recording', 'series'),
			'workshops' => array(),
			'recordings' => array(),
			'expiry_mths' => 12,
		);
	}
	return $offer_settings;
}

// retrieve the voucher exchange rates
function cc_voucher_core_exchange_rates(){
	$exchange_rates = get_option('voucher_exchange_rates');
	if(!$exchange_rates){
		$exchange_rates = array(
			'AUD' => 1,
			'USD' => 1,
			'EUR' => 1,
		);
	}
	return $exchange_rates;
}

// converts voucher amounts from one currency to another
// and formats it!!!!!
function cc_voucher_core_curr_convert($from, $to, $amount){
	if($amount == 0) return 0;
	if($from == $to) return $amount;
	$exchange_rates = cc_voucher_core_exchange_rates();
	if($from == 'GBP'){
		return number_format( $amount * $exchange_rates[$to], 2 );
	}elseif($to == 'GBP'){
		return number_format( $amount / $exchange_rates[$from], 2 );
	}else{
		$gbp = $amount / $exchange_rates[$from];
		return number_format( $gbp * $exchange_rates[$to], 2 );
	}
}

// returns a pretty voucher code
function cc_voucher_core_pretty_voucher($voucher_code){
	return
		substr($voucher_code, 0, 4) . '-' .
		substr($voucher_code, 4, 4) . '-' .
		substr($voucher_code, 8, 4);
}

// removes unnecessary characters from a voucher code (hyphens, spaces, etc)
function cc_voucher_code_raw($voucher_code){
	$voucher_code = trim($voucher_code);
	$voucher_code = str_replace('-', '', $voucher_code);
	return $voucher_code;
}