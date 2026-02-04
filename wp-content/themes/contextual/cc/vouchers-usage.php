<?php
/**
 * Vouchers - usage
 * paying for training with vouchers etc
 */

// returns the balance left on a voucher, in the currency of your choice
function ccpa_voucher_usage_balance($voucher, $currency){
	if(!is_array($voucher)) return 0;
	if($voucher['expiry_time'] < date('Y-m-d H:i:s')) return 0;
	if($voucher['balance'] <= 0) return 0;
	if($voucher['currency'] == $currency) return $voucher['balance'];
	return cc_voucher_core_curr_convert($voucher['currency'], $currency, $voucher['balance']);
}

// records usage of a voucher (payment for training)
function ccpa_voucher_usage_record($voucher_code, $amount, $currency, $payment_id){
	$voucher = ccpa_voucher_table_get($voucher_code);
	$voucher_amount = cc_voucher_core_curr_convert($currency, $voucher['currency'], $amount);
	return ccpa_voucher_trans_record($voucher, 'pay', $voucher_amount, $payment_id);
}

// records a payment or expiry transaction
// amount must be in voucher currency
function ccpa_voucher_trans_record($voucher, $type, $amount, $payment_id=0){
	$redeemed = $voucher['redeemed'] + $amount;
	$balance = $voucher['balance'] - $amount;
	$voucher_pmt = array(
		'id' => 0,
		'voucher_code' => $voucher['voucher_code'],
		'payment_id' => $payment_id,
		'type' => $type,
		'amount' => $amount * -1,
	);
	$voucher_pmt_id = ccpa_voucher_payments_table_update($voucher_pmt);
	$updated_voucher = $voucher;
	$updated_voucher['redeemed'] = $redeemed;
	$updated_voucher['balance'] = $balance;
	return ccpa_voucher_table_update($updated_voucher);
}

// returns a suitable voucher applied message
function ccpa_voucher_usage_applied_msg($raw_voucher_code, $purch_curr, $voucher_curr, $applied_amt_purch, $applied_amt_voucher, $voucher_balance){
	$msg = 'Voucher '
		.cc_voucher_core_pretty_voucher($raw_voucher_code)
		.' applied. '
		.cc_money_format($applied_amt_purch, $purch_curr);
	if($purch_curr <> $voucher_curr){
		$msg .= ' ('.cc_money_format($applied_amt_voucher, $voucher_curr).')';
	}
	$msg .= ' has been used from your voucher. The remaining balance is '
		.cc_money_format($voucher_balance, $voucher_curr)
		.'.';
	/*
	return 'Voucher '
		.cc_voucher_core_pretty_voucher($raw_voucher_code)
		.' applied. '
		.cc_money_format($exempt_disc, $currency)
		.' ('
		.cc_money_format($disc_amt_voucher_curr, $voucher['currency'])
		.') has been applied from your voucher. A balance of '
		.cc_money_format($rem_bal_voucher_curr, $voucher['currency'])
		.' remains to be used.';
	*/
	return $msg;
}


// ajax lookup of a voucher code
// returns msg ... js will trigger price update
add_action('wp_ajax_reg_voucher_lookup', 'cc_discounts_reg_voucher_lookup');
add_action('wp_ajax_nopriv_reg_voucher_lookup', 'cc_discounts_reg_voucher_lookup');
function cc_discounts_reg_voucher_lookup(){
	$response = array(
		'status' => 'error',
		'msg' => 'Voucher not found',
		'code' => '',
	);
	$trainingType = '';
	if(isset($_POST['trainingType']) && ( $_POST['trainingType'] == 'w' || $_POST['trainingType'] == 'r' )){
		$trainingType = $_POST['trainingType'];
	}
	$trainingID = '';
	if(isset($_POST['trainingID'])){
		$trainingID = (int) $_POST['trainingID'];
	}
	$eventID = '';
	if(isset($_POST['eventID'])){
		$eventID = stripslashes( sanitize_text_field( $_POST['eventID'] ) );
	}
	$currency = '';
	if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies())){
		$currency = $_POST['currency'];
	}
	$vatExempt = '';
	if(isset($_POST['vatExempt']) && ( $_POST['vatExempt'] == 'y' || $_POST['vatExempt'] == 'n' )){
		$vatExempt = $_POST['vatExempt'];
	}
	$voucher = '';
	if(isset($_POST['voucher'])){
		$voucher = stripslashes( sanitize_text_field( $_POST['voucher'] ) );
	}
	// bug fix ......... :-(
	// if($trainingType <> '' && $trainingID > 0 && $eventID <> '' && $currency <> '' && $vatExempt <> '' && $voucher <> ''){
	if($trainingType <> '' && $trainingID > 0 && $currency <> '' && $vatExempt <> '' && $voucher <> ''){

		// is it somebody trying to spend their refer a friend credits?
		if( substr( $voucher, 0, 3 ) == 'CC-' ){
			// is it their code
			if( $voucher == cc_friend_user_code( get_current_user_id() ) ){
				$usage = cc_friend_get_usage( 'raf_code', $voucher );
				// any balance on it?
				if( $usage['balance'] <= 0 ){
					$response['msg'] = 'No balance available on your Refer a Friend code';
				}else{
					$response['status'] = 'ok';
					$response['msg'] = 'Balance of '.cc_money_format($usage['balance'], $usage['currency']).' available on your Refer a Friend code. We will apply this towards your training.';
					$response['code'] = $voucher;
				}

			}
		}else{
			// voucher
			$raw_voucher_code = cc_voucher_code_raw($voucher);
			$voucher = ccpa_voucher_table_get( $raw_voucher_code );
			if($voucher !== NULL){
				// great, it's a voucher
				// still valid?
				if( $voucher['expiry_time'] < date('Y-m-d H:i:s') ){
					$response['msg'] = 'That voucher code has expired';
				}else{
					// any balance left on it?
					if($voucher['balance'] <= 0){
						$response['msg'] = 'No available balance remaining on this voucher';
					}else{
						$response['status'] = 'ok';
						$response['msg'] = 'Balance of '.cc_money_format($voucher['balance'], $voucher['currency']).' available on this voucher. We will apply this towards your training.';
						$response['code'] = $raw_voucher_code;
					}
				}
			}
		}
	}
	echo json_encode($response);
	die();
}
