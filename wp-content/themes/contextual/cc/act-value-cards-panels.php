<?php
/**
 * The ACT Value Cards Panels
 */

// add the panels to a page
add_shortcode( 'act_value_cards_purchase', 'cc_avcp_panel_shortcode' );
function cc_avcp_panel_shortcode(){
	global $rpm_theme_options;
	$html = '<div id="act-value-cards-panel" class="act-value-cards-panel">';
	$panel = 1;
	$order_id = 0;
	$order = cc_avc_empty_order();
	$order['user_id'] = get_current_user_id();
	$order['currency'] = cc_currency_get_user_currency( $order['user_id'] );
	$order['pack_price'] = $rpm_theme_options['avc_price_'.$order['currency']];
	$html .= cc_avcp_panel_content( $panel, $order );
	$html .= '</div>';
	return $html;
}

// returns the core html for a panel
function cc_avcp_panel_content( $panel, $order ){
	global $rpm_theme_options;

	$html = '<form id="avc-order-form"><input type="hidden" name="action" value="avc_order_form_submit" needs-validation><input type="hidden" name="order_id" value="'.$order['id'].'"><input type="hidden" name="user_id" value="'.$order['user_id'].'"><input type="hidden" name="panel" value="'.$panel.'"><input id="avc_action" type="hidden" name="avc_action" value="next"><input type="hidden" id="stripe-public" value="'.cc_get_stripe_key('public').'">';
                            

	switch ( $panel ) {
		case 1:
			// currency and number of packs
			$html .= '<input type="hidden" id="avc-currency" name="currency" value="'.$order['currency'].'"><input type="hidden" id="avc-locale" value="'.cc_currency_locale( $order['currency'] ).'"><input type="hidden" id="avc-pack-price" value="'.$order['pack_price'].'">';

			$html .= '<div class="row"><div class="col-md-6"><h3>Order ACT Values Cards</h3></div><div class="col-md-6 text-end">'.cc_avc_currency_switcher( $order['currency'] ).'</div></div>';

			$html .= '<div class="row mt-4"><div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';

			$html .= '<div class="row mb-3"><div class="col-8"><h5 class="mb-0">Price per pack:</h5><div class="small">(excl. VAT & PnP)</div></div><div class="col-4 text-end"><p id="avc-pack-price-display" class="h5">'.cc_money_format($order['pack_price'], $order['currency']).'</p></div></div>';

			$html .= '<div class="row mb-3"><div class="col-8"><h5 class="mb-0">Number of packs:</h5></div><div class="col-4 text-end"><input id="avc-packs" name="packs" class="form-control form-control-lg" type="number" min="1" step="1" value="'.$order['packs'].'"></div></div>';

			$html .= '<div class="row mb-3"><div class="col-8"><h5 class="mb-0">Total price:</h5><div class="small">(excl. VAT & PnP)</div></div><div class="col-4 text-end"><p id="avc-pack-total" class="h5">'.cc_money_format($order['pack_price'], $order['currency']).'</p></div></div>';

			$html .= '<div class="row mb-3"><div class="col-6"></div><div class="col-6 text-end"><button type="submit" data-avc_action="next" class="avc-submit-btn btn btn-primary btn-lg">Next</button></div></div>';

			$html .= '<h5 class="mb-1">VAT:</h5><p>'.$rpm_theme_options['avc-vat-msg'].'</p>';
			$html .= '<h5 class="mb-1">Postage and packaging:</h5><p>'.$rpm_theme_options['avc-pnp-msg'].'</p>';

			$html .= '</div></div>';
			break;

		case 2:
			// who is this?
			$html .= '<h3>Order ACT Values Cards</h3>';

			$html .= '<div class="row mt-4"><div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';

			$html .= '<h5>Your details:</h5>';

			$html .= '<div class="row mb-3"><div class="col-sm-6"><label class="form-label" for="firstname">First name *</label><input type="text" class="form-control form-control-lg" id="firstname" name="firstname" value="'.$order['firstname'].'" required></div><div class="col-sm-6"><label class="form-label" for="lastname">Last name *</label><input type="text" class="form-control form-control-lg" id="lastname" name="lastname" value="'.$order['lastname'].'" required></div></div>';

			$html .= '<div class="mb-3"><label class="form-label" for="email">Email *</label><input type="email" class="form-control form-control-lg" id="email" name="email" value="'.$order['email'].'" required></div>';

			$html .= '<div class="mb-3"><label class="form-label" for="phone">Phone *</label><input type="text" class="form-control form-control-lg" id="phone" name="phone" value="'.$order['phone'].'" required></div>';

			$html .= '<div class="row mb-3"><div class="col-6"><button type="submit" data-avc_action="back" class="avc-submit-btn btn btn-primary btn-lg">Back</button></div><div class="col-6 text-end"><button type="submit" data-avc_action="next" class="avc-submit-btn btn btn-primary btn-lg">Next</button></div></div>';

			$html .= '</div>';
		
			break;

		case 3:
			// Shipping address
			$html .= '<h3>Order ACT Values Cards</h3>';

			$html .= '<div class="row mt-4"><div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';

			$html .= '<h5>Shipping address:</h5>';

			$html .= '<div class="mb-3"><label class="form-label" for="ship_name">Name *</label><input type="text" class="form-control form-control-lg" id="ship_name" name="ship_name" value="'.$order['ship_name'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="ship_addr_1">Address line 1 *</label><input type="text" class="form-control form-control-lg" id="ship_addr_1" name="ship_addr_1" value="'.$order['ship_addr_1'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="ship_addr_2">Address line 2</label><input type="text" class="form-control form-control-lg" id="ship_addr_2" name="ship_addr_2" value="'.$order['ship_addr_2'].'"></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="ship_town">Town/City *</label><input type="text" class="form-control form-control-lg" id="ship_town" name="ship_town" value="'.$order['ship_town'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="ship_county">County/Region/State</label><input type="text" class="form-control form-control-lg" id="ship_county" name="ship_county" value="'.$order['ship_county'].'"></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="ship_postcode">Post code/Zip code *</label><input type="text" class="form-control form-control-lg" id="ship_postcode" name="ship_postcode" value="'.$order['ship_postcode'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="ship_country">Country *</label><select id="ship_country" name="ship_country" class="form-select form-select-lg" required><option value="">Please select ...</option>'.ccpa_countries_options($order['ship_country']).'</select></div>';

			$html .= '<div class="row mb-3"><div class="col-6"><button type="submit" data-avc_action="back" class="avc-submit-btn btn btn-primary btn-lg">Back</button></div><div class="col-6 text-end"><button type="submit" data-avc_action="next" class="avc-submit-btn btn btn-primary btn-lg">Next</button></div></div>';

			$html .= '</div>';

			break;

		case 4:
			// Billing address
			$html .= '<h3>Order ACT Values Cards</h3>';

			$html .= '<div class="row mt-4"><div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';

			$html .= '<h5>Billing address:</h5>';

			$html .= '<div class="mb-3"><label class="form-label" for="bill_name">Name *</label><input type="text" class="form-control form-control-lg" id="bill_name" name="bill_name" value="'.$order['bill_name'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="bill_addr_1">Address line 1 *</label><input type="text" class="form-control form-control-lg" id="bill_addr_1" name="bill_addr_1" value="'.$order['bill_addr_1'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="bill_addr_2">Address line 2</label><input type="text" class="form-control form-control-lg" id="bill_addr_2" name="bill_addr_2" value="'.$order['bill_addr_2'].'"></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="bill_town">Town/City *</label><input type="text" class="form-control form-control-lg" id="bill_town" name="bill_town" value="'.$order['bill_town'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="bill_county">County/Region/State</label><input type="text" class="form-control form-control-lg" id="bill_county" name="bill_county" value="'.$order['bill_county'].'"></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="bill_postcode">Post code/Zip code *</label><input type="text" class="form-control form-control-lg" id="bill_postcode" name="bill_postcode" value="'.$order['bill_postcode'].'" required></div>';
			$html .= '<div class="mb-3"><label class="form-label" for="bill_country">Country *</label><select id="bill_country" name="bill_country" class="form-select form-select-lg" required><option value="">Please select ...</option>'.ccpa_countries_options($order['bill_country']).'</select></div>';

			$html .= '<div class="row mb-3"><div class="col-6"><button type="submit" data-avc_action="back" class="avc-submit-btn btn btn-primary btn-lg">Back</button></div><div class="col-6 text-end"><button type="submit" data-avc_action="next" class="avc-submit-btn btn btn-primary btn-lg">Next</button></div></div>';

			$html .= '</div>';

			break;

		case 5:
			// summary
			$html .= '<h3>Order ACT Values Cards</h3>';

			$html .= '<div class="row mt-4"><div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';

			$html .= '<h5>Summary:</h5>';

			$html .= '<div class="row mb-3"><div class="col-sm-6"><h6>Shipping address:</h6><p>'.cc_avc_format_address( $order, 'ship').'</p></div><div class="col-sm-6"><h6>Billing address:</h6><p>'.cc_avc_format_address( $order, 'bill').'</p></div></div>';

			$html .= '<div class="row mb-0"><div class="col-8"><h5 class="mb-0">Price per pack:</h5></div><div class="col-4 text-end"><p class="h5">'.cc_money_format($order['pack_price'], $order['currency']).'</p></div></div>';
			$html .= '<div class="row mb-0"><div class="col-8"><h5 class="mb-0">Number of packs:</h5></div><div class="col-4 text-end"><p class="h5">'.$order['packs'].'</p></div></div>';
			$html .= '<div class="row mb-3"><div class="col-8"><h5 class="mb-0">Pack total:</h5></div><div class="col-4 text-end"><p class="h5">'.cc_money_format($order['pack_total'], $order['currency']).'</p></div></div>';

			if( $order['vat'] == 0 ){
				$pnp_class = 'mb-3';
			}else{
				$pnp_class = '';
			}
			$html .= '<div class="row '.$pnp_class.'"><div class="col-8"><h5 class="mb-0">Postage and packaging:</h5></div><div class="col-4 text-end"><p class="h5">'.cc_money_format($order['pnp'], $order['currency']).'</p></div></div>';

			if( $order['vat'] > 0 ){
				$html .= '<div class="row mb-3"><div class="col-8"><h5 class="mb-0">VAT:</h5></div><div class="col-4 text-end"><p class="h5">'.cc_money_format($order['vat'], $order['currency']).'</p></div></div>';
			}

			$html .= '<div class="row mb-3"><div class="col-8"><h5 class="mb-0">Total payable:</h5></div><div class="col-4 text-end"><p class="h5">'.cc_money_format($order['total'], $order['currency']).'</p></div></div>';

			$html .= '<div class="row mb-3"><div class="col-6"><button type="submit" data-avc_action="back" class="avc-submit-btn btn btn-primary btn-lg">Back</button></div><div class="col-6 text-end"><button type="submit" data-avc_action="next" class="avc-submit-btn btn btn-primary btn-lg">Next</button></div></div>';

			$html .= '</div>';

			break;

		case 6:
			// payment/invoice
			$html .= '<input type="hidden" id="pay_method" name="pay_method" value="'.$order['pay_method'].'">
					<input type="hidden" id="stripe-public" value="'.cc_get_stripe_key('public').'">
					<input type="hidden" id="client-secret" name="client-secret" value="'.$order['client_secret'].'">
					<input type="hidden" id="full_name" value="'.$order['firstname'].' '.$order['lastname'].'">
					<input type="hidden" id="email" value="'.$order['email'].'">';

			$html .= '<h3>Order ACT Values Cards</h3>';

			$html .= '<div class="row mt-4"><div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';

			$html .= '<h5>Payment:</h5>';

			$raw_html = '
			<div id="avc-payment-wrap" class="avc-payment-wrap avc-online">
				<h6 class="avc-pay-method-chooser" data-method="online">';
					/*
					<i class="fa-regular fa-xl fa-square-check show-online"></i>
					<i class="fa-regular fa-xl fa-square show-invoice"></i>
					*/
			$raw_html .= '
					Pay by card
				</h6>
				<div id="avc-payment-panel-online" class="avc-payment-panel avc-payment-panel-online">
					<div id="reg-card-dets" class="reg-card-dets">';
						if(cc_stripe_mode() == 'test'){
							$raw_html .= '<p class="stripe-test-mode">Stripe test mode: Use card number 4242 4242 4242 4242 for a successful (fake) payment</p>';
						}
						$raw_html .= '
						<div id="card-element" class="stripe-card-element-wrap mb-3">
							<!-- A Stripe Element will be inserted here. -->
						</div>
					</div>
				</div>';
				/*
				<h6 class="avc-pay-method-chooser" data-method="invoice">
					<i class="fa-regular fa-xl fa-square-check show-invoice"></i>
					<i class="fa-regular fa-xl fa-square show-online"></i>
					Pay by invoice
				</h6>
				<div id="avc-payment-panel-invoice" class="avc-payment-panel avc-payment-panel-invoice">
					<p>We will send an invoice to the email address you choose below and will ship the cards as soon as payment is received.</p>
					<div class="mb-3">
						<label for="inv_address" class="form-label">Invoice to be made out to *</label>
						<textarea name="inv_address" id="inv_address" rows="4" class="pay-field form-control form-control-lg" required>'.$order['inv_address'].'</textarea>
						<div class="form-text">Full name and address including contact name if required</div>
					</div>
					<div class="mb-3">
						<label for="inv_email">Invoice email *</label>
						<input type="email" id="inv_email" name="inv_email" class="pay-field form-control form-control-lg" value="'.$order['inv_email'].'" required>
					</div>
					<div class="mb-3">
						<label for="inv_phone">Invoice phone *</label>
						<input type="text" id="inv_phone" name="inv_phone" class="pay-field form-control form-control-lg" value="'.$order['inv_phone'].'" required>
					</div>
					<div class="mb-3">
						<label for="inv_ref">Invoice reference *</label>
						<input type="email" id="inv_ref" name="inv_ref" class="pay-field form-control form-control-lg" value="'.$order['inv_ref'].'" required>
						<div class="form-text">Purchase order number or other reference you would like on the invoice</div>
					</div>
				</div>
				*/
			$raw_html .= '
			</div>';

			$html .= wms_tidy_html( $raw_html );

			$html .= '<div class="row mb-3"><div class="col-6"><button type="submit" data-avc_action="back" class="avc-submit-btn btn btn-primary btn-lg">Back</button></div><div class="col-6 text-end"><button type="submit" data-avc_action="next" class="avc-payment-btn btn btn-primary btn-lg">Submit</button></div></div>';

			$html .= '</div>';

			break;
	}

	$html .= '</form>';

	$html .= '<div class="col col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">';
	$html .= '<div id="avc-panel-msg" class="avc-panel-msg"></div>';
	$html .= '</div>';

	return $html;
}