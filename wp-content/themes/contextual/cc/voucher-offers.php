<?php
/**
 * Voucher Offers
 * - training and registration page content
 */

// the offer banner for the training page
function cc_voucher_offer_training_banner($training_type, $training_id, $currency, $amount){
	$html = '';
	// should the offer apply here?
	$voucher_check_data = array(
		'currency' => $currency,
		'payment_amount' => $amount,
		'pmt_method' => 'unknown',
		'type' => $training_type,
		'workshop_id' => $training_id,
		'upsell_workshop_id' => 0
	);
	if(ccpa_vouchers_alloc_check($voucher_check_data)){
		$value = cc_voucher_offer_value($currency);
		$html .= '
			<div class="voucher-banner-wrap">
				<div class="voucher-banner text-center">
					<h3 class="voucher-banner-header">Receive your <span class="gift-voucher-value">'.$value.'</span> Gift Voucher</h3>
					<p class="voucher-banner-text">Registration for this training qualifies you to receive a voucher worth <span class="gift-voucher-value">'.$value.'</span>* towards your future training. <br class="d-none d-md-block"><a href="#" data-bs-toggle="modal" data-bs-target="#voucher-tandcs">T&amp;Cs apply</a></p>
				</div>
			</div>';
	}
	return $html;
}

// the text for the training panel on the registration page
function cc_voucher_offer_reg_page($training_type, $training_id, $currency, $amount){
	$html = '';
	// should the offer apply here?
	$voucher_check_data = array(
		'currency' => $currency,
		'payment_amount' => $amount,
		'pmt_method' => 'unknown',
		'type' => $training_type,
		'workshop_id' => $training_id,
		'upsell_workshop_id' => 0
	);
	if(ccpa_vouchers_alloc_check($voucher_check_data)){
		$value = cc_voucher_offer_value($currency);
		$html .= '
			<div class="voucher-reg-wrap">
				<div class="row">
					<div class="col">
						<div class="voucher-reg">
							<h6 class="voucher-reg-header mb-0">Receive your <span class="gift-voucher-value">'.$value.'</span> Gift Voucher</h6>
							<p class="voucher-reg-text">Registration for this training qualifies you to receive a voucher worth <span class="gift-voucher-value">'.$value.'</span>* towards your future training. <a href="#" data-bs-toggle="modal" data-bs-target="#voucher-tandcs">T&amp;Cs apply</a></p>
						</div>
					</div>
				</div>
			</div>';
	}
	return $html;
}

function cc_voucher_offer_value($currency){
	$offer_settings = cc_voucher_core_offer_settings();
	switch ($currency) {
		case 'GBP':		$amount = $offer_settings['offer_gbp'];		break;
		case 'AUD':		$amount = $offer_settings['offer_aud'];		break;
		case 'USD':		$amount = $offer_settings['offer_usd'];		break;
		case 'EUR':		$amount = $offer_settings['offer_eur'];		break;
	}
	return cc_money_format($amount, $currency);
}

