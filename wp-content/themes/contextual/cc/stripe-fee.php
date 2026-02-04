<?php
/**
 * Gets the stripe fee for a transaction
 */

/**
 * So when you create a PaymentIntent there will be an associated Balance Transaction object (txn_xxx) to this.
 * 
 * To find the underlying Stripe fee, you would need to retrieve the Balance Transaction itself to show this: https://stripe.com/docs/api/balance_transactions/retrieve Or, find it from the PaymentIntent using the Expand  * feature to dig down to this object within it: https://stripe.com/docs/api/payment_intents/retrieve https://stripe.com/docs/api/expanding_objects
 * 
 * https://stackoverflow.com/questions/36907341/stripe-fee-calculation:
 * $charge = \Stripe\Charge::create(array(
 *               "amount" => $totalAmount,
 *               "currency" => $currency_code,
 *               "source" => $stripeToken,
 *               "transfer_group" => $orderId,
 *               "expand" => array("balance_transaction")
 *             ));
 *
 * NOTE: A payment intent must be captured have a succeeded status for the Stripe fees to be available. ie "partially refunded" will NOT give us a fee!
 **/

// returns either an updated payment_data array ($fee_only = false)
// or just the fee ($fee_only = true)
function ccpa_stripe_fee($payment_data, $fee_only=false){
	$stripe_id = '';
	if($payment_data['stripe_fee'] == 0){
		if($payment_data['token'] <> ''){
			if(substr($payment_data['token'], 0, 4) == 'tok_'){
				// that was the old (and useless) way of stroing stripe data but we may find a charge id elsewhere...
				if(strpos($payment_data['notes'], 'ch_')){
					$stripe_id = substr($payment_data['notes'], strpos($payment_data['notes'], 'ch_'), 27);
					// put the charge id inot the token field as it is slightly more useful than the token id
					$payment_data['token'] = $stripe_id;
				}
			}else{
				$stripe_id = $payment_data['token'];
			}
		}
		if($stripe_id <> ''){
			if(substr($stripe_id, 0, 3) == 'pi_'){
				// payment intent
				// https://stripe.com/docs/expand/use-cases#stripe-fee-for-payment
				$args = array(
					'id' => $stripe_id,
					'expand' => array('charges.data.balance_transaction'),
				);
				$intent = cc_stripe_pmt_intent_retrieve($args);
				// var_dump($intent);
				// echo 'Stripe Request ID:'.$intent->getLastResponse()->headers["Request-Id"]; // doesn't work!
				if($intent && isset($intent->charges->data[0]->balance_transaction->fee_details)){
					$fee_details = $intent->charges->data[0]->balance_transaction->fee_details;
					foreach ($fee_details as $fee_detail) {
						if($fee_detail->description == 'Stripe processing fees'){
							$payment_data['stripe_fee'] = $fee_detail->amount / 100;
							// probably only one entry but, just in case ...
							break;
						}
					}
				}
			}else{
				// a charge ... maybe
				// echo 'Stripe ID:',$stripe_id.'###';
				$args = array(
					'id' => $stripe_id,
					'expand' => array('balance_transaction'),
				);
				$charge = cc_stripe_pmt_intent_retrieve_charge($args);
				if($charge && isset($charge->balance_transaction->fee_details)){
					// var_dump($charge);
					$fee_details = $charge->balance_transaction->fee_details;
					foreach ($fee_details as $fee_detail) {
						if($fee_detail->description == 'Stripe processing fees'){
							$payment_data['stripe_fee'] = $fee_detail->amount / 100;
							// probably only one entry but, just in case ...
							break;
						}
					}
				}
			}
		}
	}
	if($fee_only){
		if($payment_data['stripe_fee'] == 0){
			if($stripe_id == ''){
				return 0;
			}else{
				return '?';
			}
		}else{
			return $payment_data['stripe_fee'];
		}
	}
	return $payment_data;
}

// stripe fee for ACT Value Card orders
// returns a fee or 0 on failure
function cc_stripe_fee_avc_order( $stripe_id ){
	$args = array(
		'id' => $stripe_id,
		'expand' => array('charges.data.balance_transaction'),
	);
	$intent = cc_stripe_pmt_intent_retrieve($args);
	if($intent && isset($intent->charges->data[0]->balance_transaction->fee_details)){
		$fee_details = $intent->charges->data[0]->balance_transaction->fee_details;
		foreach ($fee_details as $fee_detail) {
			if($fee_detail->description == 'Stripe processing fees'){
				return $fee_detail->amount / 100;
			}
		}
	}
	return 0;
}