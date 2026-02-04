<?php
/****
 * Edit Payment
 * This is all the stuff to do with editing payments
 * that includes the page to display and process the edits
 * as well as the calls to process specific updates (in Mailster and Stripe)
 ***/

/****
 * v4.5.10 Sep 2021
 * - prevents user update if in workshops users or recordings users table
 * v4.5.8 Jul 2021
 * - added extra vat fields
 * v4.5.0 Oct 2020
 * - new file added to move payment off the list
 * - and to accommodate recording pmts as well as workshop pmts
 * - and to give a tighter link between payments and Mialster
 * - and to allow refunds to be driven from here
 ***/

// The main edit payment page
function ccpa_edit_payment(){
	wp_enqueue_script('ccpa-pay-edit-scripts');
	wp_enqueue_style('ccpa-pay-edit-style');
	wp_enqueue_style('font-awesome');
	?>
	<div class="ccpa-edit-payment-panel">
		<h1>Edit Payment</h1>
		<?php
		$payment_id = 0;
		if(isset($_GET['epmt'])){
			$payment_id = absint($_GET['epmt']);
		}
		if($payment_id == 0){
			$list_link = add_query_arg( array(
			    'page' => 'ccpa_payments',
			), admin_url() );
			?>
			<div class="top-msg-wrap">
				<div class="top-msg error">Please select a payment from the <a href="<?php echo $list_link; ?>">payment list</a> to edit.</div>
			</div>
			<?php
		}else{
			$edit_values = cc_paymentdb_get_payment($payment_id);
			if($edit_values === NULL){
				?>
				<div class="top-msg-wrap">
					<div class="top-msg error">Payment ID <?php echo $payment_id; ?> .</div>
				</div>
				<?php
			}else{
				if($edit_values['pmt_method'] <> 'invoice' && $edit_values['token'] <> ''){
					$pmt_type = 'stripe';
				}else{
					$pmt_type = '';
				}
				if($edit_values['currency'] == ''){
					$edit_values['currency'] = 'GBP';
				}
				?>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Payment</strong>
					</div>
					<div class="ccpa-col col-9">
						<input type="hidden" id="payment_id" value="<?php echo $edit_values['id']; ?>">
						<input type="hidden" id="type" value="<?php echo $edit_values['type']; ?>">
						ID: 
						<?php echo $edit_values['id']; ?>
						Dated:
						<?php echo date('d/m/Y H:i:s', strtotime($edit_values['last_update'])); ?>
						for a <strong>
						<?php if($edit_values['type'] == 'recording'){
							echo 'Recording';
						}elseif( $edit_values['type'] == 'series' ){
							echo 'Series';
						}elseif( $edit_values['type'] == 'auto' ){
							echo 'System generated';
						}else{
							echo 'Workshop';
						} ?>
						</strong>
						<?php
						if(isset($edit_values['ip_address']) && $edit_values['ip_address'] <> ''){
							echo '<br>IP Address: '.$edit_values['ip_address'];
						}
						?>
					</div>
				</div>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Status</strong>
					</div>
					<div class="ccpa-col col-2">
						<select name="status" id="status" class="ccpa-edit-fld" data-field="status" data-group="status">
							<option value="">---</option>
							<option value="Payment successful: " <?php selected($edit_values['status'], 'Payment successful: '); ?>>Payment successful: </option>
							<option value="Invoice requested" <?php selected($edit_values['status'], 'Invoice requested'); ?>>Invoice requested</option>
							<option value="Invoice Sent" <?php selected($edit_values['status'], 'Invoice Sent'); ?>>Invoice Sent</option>
							<option value="Payment failed" <?php selected($edit_values['status'], 'Payment failed'); ?>>Payment failed</option>
							<option value="Cancelled" <?php selected($edit_values['status'], 'Cancelled'); ?>>Cancelled</option>
							<option value="Payment not needed" <?php selected($edit_values['status'], 'Payment not needed'); ?>>Payment not needed</option>
						</select>
					</div>
					<div class="ccpa-col col-1">
						<span id="status-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
						<span id="status-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
					</div>
				</div>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Payee</strong>
					</div>
					<?php
					$show_user_fields = true;
					if( $edit_values['type'] == 'recording' || $edit_values['type'] == 'auto' ){
						$recuser_row = cc_myacct_get_recordings_users_row($payment_id);
						if($recuser_row !== NULL){
							$show_user_fields = false;
							$user_data = get_user_by('ID', $recuser_row['user_id']);
							?>
							<div class="ccpa-col col-9">
								<?php echo $user_data->first_name.' '.$user_data->last_name.' '.$user_data->user_email.' '; ?>
								<a href="/wp-admin/user-edit.php?user_id=<?php echo $recuser_row['user_id']; ?>"><i class="fas fa-external-link-alt"></i></a>
							</div>
							<?php
						}
					}else{
						$wkshpuser_row = cc_myacct_get_workshops_users_row($payment_id, 'r');
						if($wkshpuser_row !== NULL){
							$show_user_fields = false;
							$user_data = get_user_by('ID', $wkshpuser_row['user_id']);
							?>
							<div class="ccpa-col col-9">
								<?php echo $user_data->first_name.' '.$user_data->last_name.' '.$user_data->user_email.' '; ?>
								<a href="/wp-admin/user-edit.php?user_id=<?php echo $wkshpuser_row['user_id']; ?>"><i class="fas fa-external-link-alt"></i></a>
							</div>
							<?php
						}
					}
					if($show_user_fields){ ?>
						<div class="ccpa-col col-1">
							<label for="title">Title</label><br>
							<input id="title" name="title" type="text" class="ccpa-edit-fld" data-field="title" data-group="name" value="<?php echo $edit_values['title']; ?>">
						</div>
						<div class="ccpa-col col-2">
							<label for="firstname">First name</label><br>
							<input id="firstname" name="firstname" type="text" class="ccpa-edit-fld" data-field="firstname" data-group="name" value="<?php echo $edit_values['firstname']; ?>">
						</div>
						<div class="ccpa-col col-2">
							<label for="lastname">Last name</label><br>
							<input id="lastname" name="lastname" type="text" class="ccpa-edit-fld" data-field="lastname" data-group="name" value="<?php echo $edit_values['lastname']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<br>
							<span id="name-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
							<span id="name-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
						</div>
						<div class="ccpa-col col-3 email-col">
							<label for="email">Email</label><br>
							<input id="email" name="email" type="text" class="" data-field="email" data-group="email" value="<?php echo $edit_values['email']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<br>
							<span id="email-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
							<span id="email-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
						</div>
					<?php } ?>
				</div>
				<div class="ccpa-row">
					<div class="ccpa-col col-2"></div>
					<div class="ccpa-col col-6">
						<label for="address">Address</label><br>
						<input id="address" name="address" type="text" class="ccpa-edit-fld" data-field="address" data-group="address" value="<?php echo $edit_values['address']; ?>">
					</div>
					<div class="ccpa-col col-1">
						<br>
						<span id="address-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
						<span id="address-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
					</div>
					<div class="ccpa-col col-2">
						<label for="phone">Phone</label><br>
						<input id="phone" name="phone" type="text" class="ccpa-edit-fld" data-field="phone" data-group="phone" value="<?php echo $edit_values['phone']; ?>">
					</div>
					<div class="ccpa-col col-1">
						<br>
						<span id="phone-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
						<span id="phone-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
					</div>
				</div>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Payment</strong>
					</div>
					<?php
					if($pmt_type == 'stripe'){
						?>
						<div class="ccpa-col col-4">
							Stripe payment of
							<?php
							echo '<strong>'.$edit_values['currency'].' '.cc_money_format($edit_values['payment_amount'], $edit_values['currency']);
							/*
							switch ($edit_values['currency']) {
								case 'GBP':
									$fmt = numfmt_create( 'en_GB', NumberFormatter::CURRENCY );
									echo numfmt_format_currency($fmt, $edit_values['payment_amount'], $edit_values['currency']);
									break;
								case 'AUD':
									$fmt = numfmt_create( 'en_AU', NumberFormatter::CURRENCY );
									echo numfmt_format_currency($fmt, $edit_values['payment_amount'], $edit_values['currency']);
									break;
								case 'USD':
									$fmt = numfmt_create( 'en_USD', NumberFormatter::CURRENCY );
									echo numfmt_format_currency($fmt, $edit_values['payment_amount'], $edit_values['currency']);
									break;
							}
							*/
							echo '</strong>';
							if($edit_values['vat_exempt'] == 'y'){
								echo ' VAT Exempt';
							}
							if($edit_values['vat_included'] > 0){
								// echo ' including &pound;'.sprintf("%0.2f", $edit_values["vat_included"]).' VAT';
								echo ' including '.cc_money_format($edit_values["vat_included"], $edit_values['currency']).' VAT';
							}
							if($edit_values['student'] == 'y'){
								echo ' (Student discount)';
							}
							if($edit_values['earlybird'] == 'y'){
								echo ' (Early-bird)';
							}
							if($edit_values['refund_amount'] <> 0){
								// echo ' <strong style="color:#ff0000;">&pound;'.sprintf("%0.2f", $edit_values["refund_amount"]).' refunded</strong>';
								echo ' <strong style="color:#ff0000;">'.cc_money_format($edit_values["refund_amount"], $edit_values['currency']).' refunded</strong>';
							}

							if( $edit_values['upsell_payment_amount'] > 0 ){
								echo '<br>'.cc_money_format( $edit_values["payment_amount"] - $edit_values['upsell_payment_amount'], $edit_values['currency'] ).' attributed to '.$edit_values['workshop_id'].': '.get_the_title( $edit_values['workshop_id'] );
								echo '<br>'.cc_money_format( $edit_values['upsell_payment_amount'], $edit_values['currency'] ).' attributed to '.$edit_values['upsell_workshop_id'].': '.get_the_title( $edit_values['upsell_workshop_id'] );
							}
							?>
						</div>
						<div class="ccpa-col col-6">
							Stripe Payment:
							<?php
							// we're now storing the payment_intent_id
							// $stripe_id = payment_edit_stripe_id($edit_values);
							// if($stripe_id){
							if( $edit_values['payment_intent_id'] <> '' ){
								// echo $stripe_id;
								echo $edit_values['payment_intent_id'];
								$stripe_public = get_option('ccpa_stripe_public');
								if(strpos($stripe_public, 'pk_test') === false){
									$stripe_url = 'https://dashboard.stripe.com/payments/'.$edit_values['payment_intent_id'];
								}else{
									$stripe_url = 'https://dashboard.stripe.com/test/payments/'.$edit_values['payment_intent_id'];
								} ?>
								<a href="<?php echo $stripe_url; ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
							<?php }else{
								echo 'unknown';
							} ?>
							&nbsp;&nbsp;&nbsp;Stripe Fees:
							<?php $stripe_fee = ccpa_stripe_fee($edit_values, true);
							if($stripe_fee == '?'){
								echo 'unknown';
							}else{
								$fmt = numfmt_create( 'en_GB', NumberFormatter::CURRENCY );
								echo numfmt_format_currency($fmt, $stripe_fee, 'GBP');
							}
							if( $edit_values['charge_id'] <> '' ){
								echo '<br>Stripe Charge: '.$edit_values['charge_id'];;
							}
							?>
						</div>
					</div>
					<div class="ccpa-row">
						<div class="ccpa-col col-2">&nbsp;</div>
						<div class="ccpa-col col-10">
							Receipt:
							<?php
							$receipt_parms = ccpdf_receipt_parms_encode($payment_id);
							$receipt_url = add_query_arg(array('r' => $receipt_parms), site_url('/receipt/'));
							echo $receipt_url;
							?>
							<a class="" href="<?php echo $receipt_url; ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
						</div>
						<?php
					}else{
						?>
						<div class="ccpa-col col-1">
							<label for="currency">Currency</label><br>
							<select name="currency" id="currency" class="ccpa-edit-fld" data-field="currency" data-group="payment">
								<option value="GBP">GBP</option>
								<option value="AUD" <?php selected('AUD', $edit_values['currency']); ?>>AUD</option>
								<option value="USD" <?php selected('USD', $edit_values['currency']); ?>>USD</option>
								<option value="EUR" <?php selected('EUR', $edit_values['currency']); ?>>EUR</option>
							</select>
						</div>
						<div class="ccpa-col col-1">
							<label for="payment_amount">Amount</label><br>
							<input id="payment_amount" name="payment_amount" type="text" class="ccpa-edit-fld" data-field="payment_amount" data-group="payment" value="<?php echo $edit_values['payment_amount']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<label for="vat_exempt">VAT Exempt</label><br>
							<select name="vat_exempt" id="vat_exempt" class="ccpa-edit-fld" data-field="vat_exempt" data-group="payment">
								<option value="">-</option>
								<option value="y" <?php selected('y', $edit_values['vat_exempt']); ?>>Yes</option>
							</select>
						</div>
						<div class="ccpa-col col-1">
							<label for="vat_included">VAT Included</label><br>
							<input id="vat_included" name="vat_included" type="text" class="ccpa-edit-fld" data-field="vat_included" data-group="payment" value="<?php echo $edit_values['vat_included']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<label for="pmt_method">Pmt Method</label><br>
							<select name="pmt_method" id="pmt_method" class="ccpa-edit-fld" data-field="pmt_method" data-group="payment">
								<option value="online">online</option>
								<option value="invoice" <?php selected('invoice', $edit_values['pmt_method']); ?>>invoice</option>
							</select>
						</div>
						<div class="ccpa-col col-1">
							<br>
							<span id="payment-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
							<span id="payment-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
						</div>
						<?php
					}
					?>
				</div>
				<?php
				if($pmt_type <> 'stripe'){
					?>
					<div class="ccpa-row">
						<div class="ccpa-col col-2">
							<strong>Invoicing</strong>
						</div>

						<?php if( $edit_values['inv_address'] <> '' ){ ?>
							<div class="ccpa-col col-3">
								<label for="inv_address">Invoice Addr</label><br>
								<input id="inv_address" name="inv_address" type="text" class="ccpa-edit-fld" data-field="inv_address" data-group="invoice" value="<?php echo $edit_values['inv_address']; ?>">
							</div>
						<?php }else{ ?>
							<div class="ccpa-col col-2">
								<label for="inv_org">Invoice Organisation</label><br>
								<input id="inv_org" name="inv_org" type="text" class="ccpa-edit-fld" data-field="inv_org" data-group="invoice" value="<?php echo $edit_values['inv_org']; ?>">
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_addr1">Invoice Address</label><br>
								<input id="inv_addr1" name="inv_addr1" type="text" class="ccpa-edit-fld" data-field="inv_addr1" data-group="invoice" value="<?php echo $edit_values['inv_addr1']; ?>">
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_addr2">Address 2</label><br>
								<input id="inv_addr2" name="inv_addr2" type="text" class="ccpa-edit-fld" data-field="inv_addr2" data-group="invoice" value="<?php echo $edit_values['inv_addr2']; ?>">
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_town">Town/City</label><br>
								<input id="inv_town" name="inv_town" type="text" class="ccpa-edit-fld" data-field="inv_town" data-group="invoice" value="<?php echo $edit_values['inv_town']; ?>">
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_county">County/State</label><br>
								<input id="inv_county" name="inv_county" type="text" class="ccpa-edit-fld" data-field="inv_county" data-group="invoice" value="<?php echo $edit_values['inv_county']; ?>">
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_postcode">Postcode/Zipcode</label><br>
								<input id="inv_postcode" name="inv_postcode" type="text" class="ccpa-edit-fld" data-field="inv_postcode" data-group="invoice" value="<?php echo $edit_values['inv_postcode']; ?>">
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_country">Country</label><br>
								<select name="inv_country" id="inv_country" class="ccpa-edit-fld" data-field="inv_country" data-group="invoice">
									<option value="">Please select ...</option>
									<?php echo ccpa_countries_options( $edit_values['inv_country'] ); ?>
								</select>
							</div>
							<div class="ccpa-col col-2">
								<label for="inv_name">Contact person</label><br>
								<input id="inv_name" name="inv_name" type="text" class="ccpa-edit-fld" data-field="inv_name" data-group="invoice" value="<?php echo $edit_values['inv_name']; ?>">
							</div>
						<?php } ?>
						<div class="ccpa-col col-2">
							<label for="inv_email">Invoice Email</label><br>
							<input id="inv_email" name="inv_email" type="text" class="ccpa-edit-fld" data-field="inv_email" data-group="invoice" value="<?php echo $edit_values['inv_email']; ?>">
						</div>
						<div class="ccpa-col col-2">
							<label for="inv_phone">Invoice Phone</label><br>
							<input id="inv_phone" name="inv_phone" type="text" class="ccpa-edit-fld" data-field="inv_phone" data-group="invoice" value="<?php echo $edit_values['inv_phone']; ?>">
						</div>
						<div class="ccpa-col col-2">
							<label for="inv_ref">Invoice Ref</label><br>
							<input id="inv_ref" name="inv_ref" type="text" class="ccpa-edit-fld" data-field="inv_ref" data-group="invoice" value="<?php echo $edit_values['inv_ref']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<br>
							<span id="invoice-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
							<span id="invoice-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
						</div>
					</div>
					<?php
				} ?>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>VAT</strong>
					</div>
					<div class="ccpa-col col-1">
						<label for="vat_uk">Based in UK?</label>
						<select name="vat_uk" id="vat_uk" class="ccpa-edit-fld" data-field="vat_uk" data-group="vat">
							<option value="">Not set</option>
							<option value="n" <?php selected('n', $edit_values['vat_uk']); ?>>No</option>
							<option value="y" <?php selected('y', $edit_values['vat_uk']); ?>>Yes</option>
						</select>
					</div>
					<div class="ccpa-col col-2">
						<label for="vat_employ">Employment status</label>
						<select name="vat_employ" id="vat_employ" class="ccpa-edit-fld" data-field="vat_employ" data-group="vat">
							<option value="">Not set</option>
							<option value="self" <?php selected('self', $edit_values['vat_employ']); ?>>Self-employed professional</option>
							<option value="employ" <?php selected('employ', $edit_values['vat_employ']); ?>>Undertaking this course for the benefit of your employment</option>
							<option value="not" <?php selected('not', $edit_values['vat_employ']); ?>>Neither of the above</option>
						</select>
					</div>
					<div class="ccpa-col col-2">
						<label for="vat_employer">Business or employer</label>
						<input type="text" class="ccpa-edit-fld" data-field="vat_employer" data-group="vat" name="vat_employer" id="vat_employer" value="<?php echo $edit_values['vat_employer']; ?>">
					</div>
					<div class="ccpa-col col-2">
						<label for="vat_website">Website</label>
						<input type="text" class="ccpa-edit-fld" data-field="vat_website" data-group="vat" name="vat_website" id="vat_website" value="<?php echo $edit_values['vat_website']; ?>">
					</div>
					<div class="ccpa-col col-2">
						<br>
						<?php
						if($edit_values['vat_exempt'] == 'y'){
							echo 'Exempt from UK VAT';
						}else{
							echo 'Charged UK VAT';
						}
						?>
					</div>
				</div>
				<?php
				if( $edit_values['disc_code'] <> '' || $edit_values['disc_amount'] <> 0 || $edit_values['voucher_code'] <> '' || $edit_values['voucher_amount'] <> 0 ){
					?>
					<div class="ccpa-row">
						<div class="ccpa-col col-2">
							<strong>Promotions/Vouchers</strong>
						</div>
						<div class="ccpa-col col-10">
							<?php
							if($edit_values['disc_code'] <> ''){
								echo 'Promotional code: '.$edit_values['disc_code'].' applied. ';
								echo 'Discount amount: '.$edit_values['currency'].sprintf("%0.2f", $edit_values["disc_amount"]).'. ';
							}
							if( $edit_values['disc_code'] <> '' && $edit_values['voucher_code'] <> '' ){
								echo '<br>';
							}
							if( $edit_values['voucher_code'] <> '' ){
						        if( substr( $edit_values['voucher_code'], 0, 3) == 'CC-' ){
						        	$pretty_voucher_code = $edit_values['voucher_code'];
						        }else{
						        	$pretty_voucher_code = cc_voucher_core_pretty_voucher( $edit_values['voucher_code'] );
						        }
								echo 'Voucher: '.$pretty_voucher_code.' used. ';
								echo 'voucher amount: '.$edit_values['currency'].sprintf("%0.2f", $edit_values["voucher_amount"]).'. ';
							}
								/*
								$voucher = ccpa_voucher_table_get($edit_values['disc_code']);
								if($voucher === NULL){
									echo 'Promotional code: '.$edit_values['disc_code'].' applied. ';
								}else{
									echo 'Voucher: '.cc_voucher_core_pretty_voucher($edit_values['disc_code']).' used. ';
								}

							}
							if($edit_values['disc_amount'] <> 0){
								echo 'Discount/voucher amount: '.$edit_values['currency'].sprintf("%0.2f", $edit_values["disc_amount"]).'. ';
							}
							*/
							?>
						</div>
					</div>
					<?php
				}
				?>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Other Info</strong>
					</div>
					<div class="ccpa-col col-2">
						<label for="source">Source</label><br>
						<input id="source" name="source" type="text" class="ccpa-edit-fld" data-field="source" data-group="other" value="<?php echo $edit_values['source']; ?>">
					</div>
					<div class="ccpa-col col-1">
						<label for="webcast_attend">Webcast attend</label><br>
						<select name="webcast_attend" id="webcast_attend" class="ccpa-edit-fld" data-field="webcast_attend" data-group="other">
							<option value="">No</option>
							<option value="yes" <?php selected('yes', $edit_values['webcast_attend']); ?>>Yes</option>
						</select>
					</div>
					<div class="ccpa-col col-1">
						<label for="tandcs">T&amp;Cs</label><br>
						<input id="tandcs" name="tandcs" type="text" class="ccpa-edit-fld" data-field="tandcs" data-group="other" value="<?php echo $edit_values['tandcs']; ?>">
					</div>
					<div class="ccpa-col col-1">
						<label for="mailing_list">Mailing List</label><br>
						<input id="mailing_list" name="mailing_list" type="text" class="ccpa-edit-fld" data-field="mailing_list" data-group="other" value="<?php echo $edit_values['mailing_list']; ?>">
					</div>
					<div class="ccpa-col col-4">
						<label for="notes">Notes</label><br>
						<input id="notes" name="notes" type="text" class="ccpa-edit-fld" data-field="notes" data-group="other" value="<?php echo $edit_values['notes']; ?>">
					</div>
					<div class="ccpa-col col-1">
						<br>
						<span id="other-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
						<span id="other-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
					</div>
				</div>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Training</strong>
					</div>
					<div class="ccpa-col col-4">
						<?php if( $edit_values['type'] == 'recording' || $edit_values['type'] == 'auto' ){ ?>
							<label for="">Recording</label><br>
							<select name="recording_id" id="recording_id" class="ccpa-edit-fld" data-field="workshop_id" data-group="recwkshp">
								<option value="">Please select</option>
								<?php echo ccrecw_free_rec_options($edit_values['workshop_id']); ?>
							</select>
						<?php }elseif( $edit_values['type'] == 'series' ){ ?>
							<label for="">Series</label><br>
							<input type="text" disabled value="<?php echo get_the_title($edit_values['workshop_id']); ?>">
						<?php }else{ ?>
							<label for="">Workshop</label><br>
							<select name="workshop_id" id="workshop_id" class="ccpa-edit-fld" data-field="workshop_id" data-group="recwkshp">
								<option value="">Please select</option>
								<?php
								$workshop_one = cc_upsell_all_workshop_options($edit_values['workshop_id'], $edit_values['payment_ref']);
								echo $workshop_one['options'];
								?>
							</select>
							<?php if($workshop_one['selected_id'] == 0){
								echo $edit_values['payment_ref'];
							}
						} ?>

						<br>
						<label for="">Upsell</label><br>
						<select name="upsell_workshop_id" id="upsell_workshop_id" class="ccpa-edit-fld" data-field="upsell_workshop_id" data-group="recwkshp">
							<option value="">---</option>
							<?php $workshop_two = cc_upsell_all_workshop_options( $edit_values['upsell_workshop_id'], '', array( 'workshop', 'recording' ) );
							echo $workshop_two['options'];
							?>
						</select>
					</div>
					<div class="ccpa-col col-1">
						<br>
						<span id="recwkshp-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
						<span id="recwkshp-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
					</div>
					<div class="ccpa-col col-4">
						<?php if($edit_values['type'] == ''){ ?>
							<label for="">Events</label><br>
							<div id="events-wrap">
								<?php echo payment_edit_events_checkboxes($edit_values['workshop_id'], $edit_values['event_ids']); ?>
							</div>
							<a href="javascript:void(0);" id="events-update" class="btn">Update events</a>
						<?php } ?>
					</div>
					<div class="ccpa-col col-1">
						<br>
						<span id="events-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
						<span id="events-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
					</div>
				</div>
				<div class="ccpa-row">
					<div class="ccpa-col col-2">
						<strong>Mailing Lists</strong>
					</div>
					<div id="mail-lists" class="ccpa-col col-9">
						<?php echo payment_edit_mailster_lists($edit_values['email']); ?>
					</div>
					<div class="ccpa-col col-1">
						<a href="javascript:void(0);" id="mail-list-update" class="btn">Update mailing lists</a>
					</div>
				</div>

				<p>&nbsp;</p>
				<?php
				$attendees = cc_attendees_for_payment($payment_id);
				$row_heading = 'Attendees';
				foreach ($attendees as $attendee) {
					if($attendee['registrant'] == 'r'){ ?>
						<div class="ccpa-row">
							<div class="ccpa-col col-2">
								<strong><?php echo $row_heading; ?></strong>
							</div>
							<div class="ccpa-col col-9">
								Registrant: <?php echo $user_data->first_name.' '.$user_data->last_name; ?>
							</div>
						</div>
					<?php }else{
						$user = get_user_by('ID', $attendee['user_id']);
						if($user){
							$attendee_firstname = $user->user_firstname;
							$attendee_lastname = $user->user_lastname;
							$attendee_email = $user->user_email;
						}else{
							$attendee_firstname = 'Unknown';
							$attendee_lastname = 'Unknown';
							$attendee_email = 'Unknown user ID '.$attendee['user_id'];
						}
						?>
						<div class="ccpa-row">
							<div class="ccpa-col col-2">
								<strong><?php echo $row_heading; ?></strong>
							</div>
							<div class="ccpa-col col-2">
								<label for="attendee_firstname">First name</label><br>
								<input name="attendee_firstname[]" type="text" class="ccpa-edit-fld" data-field="attendee_firstname" data-group="attendee_name" value="<?php echo $attendee_firstname; ?>" readonly>
							</div>
							<div class="ccpa-col col-2">
								<label for="attendee_lastname">Last name</label><br>
								<input name="attendee_lastname[]" type="text" class="ccpa-edit-fld" data-field="attendee_lastname" data-group="attendee_name" value="<?php echo $attendee_lastname; ?>" readonly>
							</div>
							<div class="ccpa-col col-1">
								<br>
								<span id="attendee_name-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
								<span id="attendee_name-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
							</div>
							<div class="ccpa-col col-3">
								<label for="attendee_email">Email</label><br>
								<input type="text" name="attendee_email[]" class="" data-field="attendee_email" data-group="attendee" value="<?php echo $attendee_email; ?>" readonly>
							</div>
							<div class="ccpa-col col-1">
								<br>
								<span id="attendee-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
								<span id="attendee-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
							</div>
						</div>
					<?php }
					$row_heading = '';
				}
					/*
					<div class="ccpa-row">
						<div class="ccpa-col col-2">
							<strong>Attendees</strong>
						</div>
						<div class="ccpa-col col-2">
							<label for="attendee_firstname">First name</label><br>
							<input id="attendee_firstname" name="attendee_firstname" type="text" class="ccpa-edit-fld" data-field="attendee_firstname" data-group="attendee_name" value="<?php echo $edit_values['attendee_firstname']; ?>">
						</div>
						<div class="ccpa-col col-2">
							<label for="attendee_lastname">Last name</label><br>
							<input id="attendee_lastname" name="attendee_lastname" type="text" class="ccpa-edit-fld" data-field="attendee_lastname" data-group="attendee_name" value="<?php echo $edit_values['attendee_lastname']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<br>
							<span id="attendee_name-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
							<span id="attendee_name-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
						</div>
						<div class="ccpa-col col-3">
							<label for="attendee_email">Email</label><br>
							<input type="text" name="attendee_email" id="attendee_email" class="" data-field="attendee_email" data-group="attendee" value="<?php echo $edit_values['attendee_email']; ?>">
						</div>
						<div class="ccpa-col col-1">
							<br>
							<span id="attendee-wait" class="wait-flag"><i class="fas fa-spinner fa-spin"></i></span>
							<span id="attendee-upd" class="upd-flag"><i class="far fa-check-circle"></i></span>
						</div>
					</div>
					<div class="ccpa-row">
						<div class="ccpa-col col-2"></div>
						<div class="ccpa-col col-9">
							<label for="">Attendee mailing lists</label><br>
							<div id="attendee-mail-lists" class="">
								<?php echo payment_edit_mailster_lists($edit_values['attendee_email']) ?>
							</div>
						</div>
						<div class="ccpa-col col-1">
							<br>
							<a href="javascript:void(0);" id="attendee-mail-list-update" class="btn">Update mailing lists</a>
						</div>
					</div>
				<?php 
				*/
				
				if( $edit_values['type'] <> 'auto' ){ ?>

					<p>&nbsp;</p>
					<div class="ccpa-row">
						<div class="ccpa-col col-2">
							<strong>Actions</strong>
						</div>
						<div class="ccpa-col col-10">
							<a href="javascript:void(0);" id="resend-reg-email" class="btn">Resend registration email</a>
							<a href="javascript:void(0);" id="send-invoice-pmt-conf" class="btn">Send invoice payment conf.</a>
							<?php if($pmt_type == 'stripe' && $edit_values['token'] <> ''){
								$max_refund = $edit_values['payment_amount'] - $edit_values['refund_amount'];
								?>
								<div class="refund-wrap">
									<label for="refund-request">Refund:</label>
									<span><?php echo $edit_values['currency']; ?></span>
									<input type="number" id="refund-request" class="refund-request" value="<?php echo $max_refund; ?>" min="0.01" max="<?php echo $max_refund; ?>" step="0.01" data-currency="<?php echo $edit_values['currency']; ?>">
									<a href="javascript:void(0);" id="refund-now" class="btn">Refund now</a>
								</div>
							<?php } ?>
						</div>
					</div>
					<div class="ccpa-row">
						<div class="ccpa-col col-2"></div>
						<div class="ccpa-col col-10">
							<div id="action-msg" class="action-msg"></div>
						</div>
					</div>

				<?php } ?>

				<div id="email-update-popup-wrap" class="email-update-popup-wrap"></div>
				<?php
			}
		}
		?>
	</div>
	<?php 
}

// the events checkboxes
function payment_edit_events_checkboxes($workshop_id, $selected_ids){
	$html = '';
	$num_events = 0;
	if($workshop_id > 0){
		if($selected_ids <> ''){
			$event_ids = explode(',', $selected_ids);
		}else{
			$event_ids = array();
		}
		for ($i=1; $i < 16; $i++) { 
			$event_name = get_post_meta($workshop_id, 'event_'.$i.'_name', true);
			if($event_name <> ''){
				if(in_array($i, $event_ids)){
					$checked = 'checked="checked"';
				}else{
					$checked = '';
				}
				$html .= '<input type="checkbox" class="event-chkbx" id="event-id-'.$i.'" value="'.$i.'" '.$checked.'> '.$event_name.'<br>';
			}
		}
	}
	return $html;
}

// the mailster lists
function payment_edit_mailster_lists($email){
	$html = '';
	if($email == ''){
		$subscriber = false;
		return '<p><strong>Email address missing and must be set before assigning mailing lists.</strong></p>';
	}
	$subscriber = mailster('subscribers')->get_by_mail($email);
	if($subscriber){
		$subs_list_ids = mailster('subscribers')->get_lists($subscriber->ID, true); // get ids only
	}else{
		$html .= '<p><strong>Email address '.$email.' not found in the list of subscribers.</strong></p>';
		$subs_list_ids = array();
	}
    // get all mailster lists
    $lists = mailster( 'lists' )->get();
    // lists is an array of arrays:  { ["ID"]=> string(1) "1" ["parent_id"]=> string(1) "0" ["name"]=> string(12) "Default List" ["slug"]=> string(12) "default-list" ["description"]=> string(0) "" ["added"]=> string(10) "1567784674" ["updated"]=> string(10) "1567784674" ["_sort"]=> string(2) "10" }
	$html .= '<div class="ccpa-scroller">';
	foreach ($lists as $list) {
		if(in_array($list->ID, $subs_list_ids)){
			$checked = 'checked="checked"';
		}else{
			$checked = '';
		}
		$html .= '<input type="checkbox" class="mail-list-chkbx" id="list-id-'.$list->ID.'" value="'.$list->ID.'" '.$checked.'> '.$list->name.'<br>';
	}
	$html .= '</div>';
	return $html;
}

// the ajax function to update one of the above fields
add_action('wp_ajax_payment_edit_field_update', 'payment_edit_field_update');
function payment_edit_field_update(){
	$response = array(
		'status' => 'error',
		'msg' => '',
		'html' => '',
		'update' => '',
	);
	$payment_id = 0;
	$field = '';
	$value = '';
	if(isset($_POST['paymentID'])){
		$payment_id = absint($_POST['paymentID']);
	}
	if(isset($_POST['field'])){
		$field = sanitize_text_field($_POST['field']);
	}
	if(isset($_POST['value'])){
		$money_fields = array('payment_amount', 'vat_included');
		$id_fields = array('workshop_id', 'upsell_workshop_id');
		if(in_array($field, $money_fields)){
			$value = (float) $_POST['value'];
		}elseif(in_array($field, $id_fields)){
			$value = absint($_POST['value']);
		}else{
			$value = sanitize_text_field($_POST['value']);
		}
	}
	if($payment_id == 0){
		$response['msg'] = 'Payment ID invalid or missing - '.$field.' not updated';
	}else{
		$payment_data = cc_paymentdb_get_payment($payment_id);
		if($payment_data === NULL){
			$response['msg'] = 'Payment '.$payment_id.' not found - '.$field.' not updated';
		}else{
			if($field <> 'mail_list_ids' && $field <> 'attendee_mail_list_ids' && !isset($payment_data[$field])){
				$response['msg'] = 'Payment data does not contain '.$field;
			}else{
				// the update .....
				if($field == 'mail_list_ids' || $field == 'attendee_mail_list_ids'){
					// this is a mailster update not a payment data update
					$list_ids = explode(',', $value);
					if($field == 'mail_list_ids'){
						$email = $payment_data['email'];
					}else{
						$email = $payment_data['attendee_email'];
					}
					$subscriber = mailster('subscribers')->get_by_mail($email);
					if($subscriber){
						$subscriber_id = $subscriber->ID;
					}else{
						// add subscriber
				        $overwrite = true;
				        if($field == 'mail_list_ids'){
							$userdata = array(
					            'email' => $payment_data['email'],
					            'firstname' => $payment_data["firstname"],
					            'lastname' => $payment_data['lastname'],
					            'last_payment_id' => $payment_data['id'],
						        'region' => cc_country_region( get_user_meta( $payment_data['reg_userid'], 'address_country', true ) ),
					        );
						}else{
							$userdata = array(
					            'email' => $payment_data['attendee_email'],
					            'firstname' => $payment_data["attendee_firstname"],
					            'lastname' => $payment_data['attendee_lastname'],
						        'region' => cc_country_region( get_user_meta( $payment_data['reg_userid'], 'address_country', true ) ),
					        );
						}
				        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
					}
					if(mailster('subscribers')->assign_lists($subscriber_id, $list_ids, true)){ // true = remove old = remove any lists not in list_ids
						$response['msg'] = $field.' updated to '.$value;
						$response['status'] = 'ok';
					}else{
						$response['msg'] = 'Failed to update '.$field;
					}
				}else{
					if($field == 'email' && $payment_data['email'] <> $value){
						$response['update'] = 'mail-lists';
					}
					if($field == 'attendee_email' && $payment_data['attendee_email'] <> $value){
						$response['update'] = 'attendee-mail-lists';
					}
					if($field == 'workshop_id' && $payment_data['type'] <> 'recording' && $payment_data['workshop_id'] <> $value){
						$payment_data['event_ids'] = '';
					}
					if( in_array($field, $id_fields) ){
						$prev_id = $payment_data[$field];
					}
					$payment_data[$field] = $value;
					if(cc_paymentdb_update_payment($payment_data) === false){
						$response['msg'] = 'Failed to update '.$field;
					}else{
						$response['msg'] = $field.' updated to '.$value;
						$response['status'] = 'ok';
						// maybe cancel the registration
						if( ( $value == '' || $value == 0 ) && in_array($field, $id_fields) && $prev_id > 0 ){
							if($payment_data['type'] == 'recording'){
								$recuser_row = cc_myacct_get_recordings_users_row($payment_id);
								if($recuser_row !== NULL){
									if(cc_myacct_recording_offer_cancellation($prev_id, $recuser_row['user_id'])){
										cc_cancellation_cancel_recording($prev_id, $recuser_row['user_id']);
										$response['msg'] .= ' - registration cancelled';
									}
								}
							}else{
								$wkshpuser_row = cc_myacct_get_workshops_users_row($payment_id, 'r');
								if($wkshpuser_row !== NULL){
									if(cc_myacct_workshop_offer_cancellation($prev_id, $wkshpuser_row['user_id'])){
										cc_cancellation_cancel_workshop($prev_id, $wkshpuser_row['user_id']);
										$response['msg'] .= ' - registration cancelled';
									}
								}
							}
						}

						if($field == 'workshop_id' && $payment_data['type'] <> 'recording'){
							$response['update'] = 'events-wrap';
							$response['html'] = payment_edit_events_checkboxes($value, $payment_data['event_ids']);
						}
						if($response['update'] == 'mail-lists' || $response['update'] == 'attendee-mail-lists'){
							$response['html'] = payment_edit_mailster_lists($value);
						}
					}
				}
			}
		}
	}
    echo json_encode($response);
    die();
}

add_action('wp_ajax_payment_edit_resend_reg', 'payment_edit_resend_reg');
function payment_edit_resend_reg(){
	$response = array(
		'class' => 'error',
		'msg' => '',
	);
	$payment_id = 0;
	if(isset($_POST['paymentID'])){
		$payment_id = absint($_POST['paymentID']);
	}
	if($payment_id == 0){
		$response['msg'] = 'Payment ID invalid or missing - registration email not queued';
	}else{
		if(cc_mailsterint_send_reg_emails( $payment_id, true ) ){
			$response['msg'] = 'Registration email successfully queued.';
			$response['class'] = 'success';
		}else{
			$response['msg'] = 'Registration email send FAILED!';
		}
	}
    echo json_encode($response);
    die();
}

add_action('wp_ajax_payment_edit_send_inv_conf', 'payment_edit_send_inv_conf');
function payment_edit_send_inv_conf(){
	$response = array(
		'class' => 'error',
		'msg' => '',
	);
	$payment_id = 0;
	if(isset($_POST['paymentID'])){
		$payment_id = absint($_POST['paymentID']);
	}
	if($payment_id == 0){
		$response['msg'] = 'Payment ID invalid or missing - invoice payment confirmation email not queued';
	}else{
		if(ccpa_send_inv_conf_email($payment_id)){
			$response['msg'] = 'Invoice payment confirmation email successfully queued.';
			$response['class'] = 'success';
		}else{
			$response['msg'] = 'Invoice payment confirmation email send FAILED!';
		}
	}
    echo json_encode($response);
    die();
}

add_action('wp_ajax_payment_edit_refund_request', 'payment_edit_refund_request');
function payment_edit_refund_request(){
	// ccpa_write_log('payment_edit_refund_request');
	// ccpa_write_log($_REQUEST);
	$response = array(
		'class' => 'error',
		'msg' => '',
	);
	$payment_id = 0;
	if(isset($_POST['paymentID'])){
		$payment_id = absint($_POST['paymentID']);
	}
	$refund = 0;
	if(isset($_POST['refund'])){
		$refund = (float) $_POST['refund'];
	}
	if($payment_id == 0 || $refund == 0){
		$response['msg'] = 'Refund request invalid - no action taken';
	}else{
		$payment_data = cc_paymentdb_get_payment($payment_id);
		// ccpa_write_log($payment_data);
		if($payment_data === NULL){
			$response['msg'] = 'DB lookup error - no action taken';
		}else{
			$refund_pence = $refund * 100;
			$stripe_id = payment_edit_stripe_id($payment_data);
			if(substr($stripe_id, 0, 3) == 'pi_' || substr($stripe_id, 0, 3) == 'ch_'){
				if(substr($stripe_id, 0, 3) == 'pi_'){
					$args = array(
						'payment_intent' => $stripe_id,
						'amount' => $refund_pence,
					);
				}else{
					$args = array(
						'charge' => $stripe_id,
						'amount' => $refund_pence,
					);
				}
				$result = cc_stripe_pmt_intent_refund($args);
				// ccpa_write_log($result);
				if($result){
					$response['msg'] = 'Refund of '.$payment_data['currency'].' '.$refund.' successfully processed';
					$payment_data['refund_amount'] = $payment_data['refund_amount'] + $refund;
					if(cc_paymentdb_update_payment($payment_data) === false){
						$response['msg'] .= ' but DB update failed!';
					}else{
						$response['class'] = 'success';
					}
				}else{
					$response['msg'] = 'Refund request FAILED!';
				}
			}else{
				$response['msg'] = 'Stripe ID invalid or missing';
			}
		}
	}
	// ccpa_write_log($response);
    echo json_encode($response);
    // ccpa_write_log('end payment_edit_refund_request');
    die();
}

add_action('wp_ajax_payment_edit_email_update', 'payment_edit_email_update');
function payment_edit_email_update(){
	$response = array(
		'status' => 'error',
		'msg' => '',
		'html' => '',
		'update' => '',
	);
	$payment_id = 0;
	$field = '';
	$value = '';
	if(isset($_POST['paymentID'])){
		$payment_id = absint($_POST['paymentID']);
	}
	if(isset($_POST['field'])){
		$field = sanitize_text_field($_POST['field']);
	}
	if(isset($_POST['value'])){
		$value = sanitize_email($_POST['value']);
	}
	if($payment_id == 0 || ($field <> 'email' && $field <> 'attendee_email') || $value == ''){
		$response['msg'] = 'Update data invalid or missing - '.$field.' not updated';
	}else{
		$payment_data = cc_paymentdb_get_payment($payment_id);
		if($payment_data === NULL){
			$response['msg'] = 'Payment '.$payment_id.' not found - '.$field.' not updated';
		}else{
			if($value == $payment_data[$field]){
				$response['msg'] = $field.' unchanged - no update needed';
			}else{
				$old_subscriber_id = 0;
				$old_subscriber = mailster('subscribers')->get_by_mail($payment_data[$field]);
				if($old_subscriber){
					$old_subscriber_id = $old_subscriber->ID;
					if($old_subscriber->firstname == '' && $old_subscriber->lastname == ''){
						$old_subs_name = 'Name unknown';
					}else{
						$old_subs_name = $old_subscriber->firstname.' '.$old_subscriber->lastname;
					}
				}
				$new_subscriber_id = 0;
				$new_subscriber = mailster('subscribers')->get_by_mail($value);
				if($new_subscriber){
					$new_subscriber_id = $new_subscriber->ID;
					if($new_subscriber->firstname == '' && $new_subscriber->lastname == ''){
						$new_subs_name = 'Name unknown';
					}else{
						$new_subs_name = $new_subscriber->firstname.' '.$new_subscriber->lastname;
					}
				}
				if($payment_data['type'] == 'recording'){
					$old_user = get_user_by('email', $payment_data[$field]);
					$new_user = get_user_by('email', $value);
				}

				if($old_subscriber_id == 0 && ($payment_data['type'] <> 'recording' || $old_user === false && $new_user <> false)){

					// no questions to ask!
					if($field == 'email'){
						$response['update'] = 'mail-lists';
					}
					if($field == 'attendee_email'){
						$response['update'] = 'attendee-mail-lists';
					}
					$payment_data[$field] = $value;
					if(cc_paymentdb_update_payment($payment_data) === false){
						$response['msg'] = 'Failed to update '.$field;
					}else{
						$response['msg'] = $field.' updated to '.$value;
						$response['status'] = 'ok';
						if($response['update'] == 'mail-lists' || $response['update'] == 'attendee-mail-lists'){
							$response['html'] = payment_edit_mailster_lists($value);
						}
					}

				}else{

					$response['status'] = 'popup';
					$response['html'] = '<div id="email-update-popup" class="email-update-popup">';
					$response['html'] .= '<h3>Email update</h3>';

					$response['html'] .= '<p>The original email address: '.$payment_data[$field];
					if($old_subscriber_id == 0){
						$response['html'] .= ' is not associated with a subscriber.';
					}else{
						$response['html'] .= ' is associated with subscriber '.$old_subscriber_id.': '.$old_subs_name.'.';
					}
					if($payment_data['type'] == 'recording'){
						if($old_user === false){
							$response['html'] .= ' This email address does not have login details to view recordings.';
						}else{
							$response['html'] .= ' This email address also has login details to view recordings.';
						}
					}
					$response['html'] .= '</p>';

					$response['html'] .= '<p>The new email address: '.$value;
					if($new_subscriber_id == 0){
						$response['html'] .= ' is not associated with a subscriber.';
					}else{
						$response['html'] .= ' is associated with subscriber '.$new_subscriber_id.': '.$new_subs_name.'.';
					}
					if($payment_data['type'] == 'recording'){
						if($new_user === false){
							$response['html'] .= ' This email address does not have login details to view recordings.';
						}else{
							$response['html'] .= ' This email address also has login details to view recordings.';
						}
					}
					$response['html'] .= '</p>';
					$response['html'] .= '<p>What would you like to happen?</p>';

					$response['html'] .= '<h4>Mailing lists</h4>';
					if($old_subscriber_id == 0){
						$response['html'] .= '<p>No mailing lists to move/copy</p>';
					}else{
						if($new_subscriber_id == 0){
							$response['html'] .= '<div id="data-row-1" class="ccpa-row data-row" data-chosen="" data-choices="mlist">';
							$response['html'] .= '<div class="ccpa-col col-4">';
							$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="chg">Change subscriber email address</a>';
							$response['html'] .= '</div>';
							$response['html'] .= '<div class="ccpa-col col-4">';
							$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="cpy">Copy these mailing lists to a new subscriber</a>';
							$response['html'] .= '</div>';
							$response['html'] .= '<div class="ccpa-col col-4">';
							$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="new">Create new subscriber with mailing list for this w/shop/rec. only</a>';
							$response['html'] .= '</div>';
							$response['html'] .= '</div>';
						}else{
							$response['html'] .= '<div id="data-row-1" class="ccpa-row data-row" data-chosen="" data-choices="mlist">';
							$response['html'] .= '<div class="ccpa-col col-4">';
							$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="swi">Leave mailing lists unchanged</a>';
							$response['html'] .= '</div>';
							$response['html'] .= '<div class="ccpa-col col-4">';
							$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="mge">New subscriber to <strong>also</strong> have mailing lists of old subscriber (merge)</a>';
							$response['html'] .= '</div>';
							$response['html'] .= '<div class="ccpa-col col-4">';
							$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="mov">New subscriber to <strong>only</strong> have mailing lists of old subscriber (move)</a>';
							$response['html'] .= '</div>';
							$response['html'] .= '</div>';						
						}
					}

					$response['html'] .= '<h4>Old subscriber record</h4>';
					if($old_subscriber_id == 0){
						$response['html'] .= '<p>No subscriber record</p>';
					}else{
						$response['html'] .= '<div id="data-row-2" class="ccpa-row data-row" data-chosen="" data-choices="exsub">';
						$response['html'] .= '<div class="ccpa-col col-4">';
						$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="kee">Keep the old subscriber</a>';
						$response['html'] .= '</div>';
						$response['html'] .= '<div class="ccpa-col col-4">';
						$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="del">Delete the old subscriber</a>';
						$response['html'] .= '</div>';
						$response['html'] .= '</div>';
					}

					if($payment_data['type'] == 'recording'){
						$response['html'] .= '<h4>User login choices</h4>';
						if($old_user === false){
							// this means the old email does not have access to a recording they have paid for ... should not normally happen!
							if($new_user === false){
								// no new user either!
								$response['html'] .= '<div id="data-row-3" class="ccpa-row data-row" data-chosen="" data-choices="user">';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="cre">Create new user login and send <em>create password</em> email</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="zip">Do nothing with users</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '</div>';
							}else{
								// new but not old user
								$response['html'] .= '<p>We\'ll add this recording to the ones that the new email address already has access to.</p>';
							}
						}else{
							// old user found
							if($new_user === false){
								// but not new user
								$response['html'] .= '<div id="data-row-3" class="ccpa-row data-row" data-chosen="" data-choices="user">';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="cre">Create new user login and send <em>create password</em> email</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="crd">Create new user, send email and also remove old user</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="zip">Do nothing with users</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '</div>';
							}else{
								// old and new user
								$response['html'] .= '<p>We\'ll add this recording to the ones that the new email address already has access to. What else would you like to happen?</p>';
								$response['html'] .= '<div id="data-row-3" class="ccpa-row data-row" data-chosen="" data-choices="user">';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="del">Delete the old user</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '<div class="ccpa-col col-4">';
								$response['html'] .= '<a href="javascript:void(0);" class="email-option btn" data-option="zip">Do nothing with the old user</a>';
								$response['html'] .= '</div>';
								$response['html'] .= '</div>';
							}
						}
					}

					$response['html'] .= '<div class="ccpa-row">';
					$response['html'] .= '<div class="ccpa-col col-6"><a href="javascript:void(0);" id="email-cancel-popup" class="btn btn-default btn-cancel" data-field="'.$field.'" data-old-email="'.$payment_data[$field].'">Cancel</a></div>';
					$response['html'] .= '<div class="ccpa-col col-6 text-right"><a href="javascript:void(0);" id="email-submit-popup" class="btn btn-primary disabled" data-field="'.$field.'" data-sub1="'.$old_subscriber_id.'" data-sub2="'.$new_subscriber_id.'" data-new-email="'.$value.'">Submit</a></div>';
					$response['html'] .= '</div>';

				}
			}
		}
	}
    echo json_encode($response);
    die();
}

add_action('wp_ajax_payment_edit_popup_submit', 'payment_edit_popup_submit');
function payment_edit_popup_submit(){
	$response = array(
		'status' => 'error',
		'msg' => '',
		'html' => '',
		'update' => '',
	);
	$payment_id = 0;
	if(isset($_POST['paymentID'])){
		$payment_id = absint($_POST['paymentID']);
	}
	$text_fields = array('field', 'sub1', 'sub2', 'mlist', 'exsub', 'user', 'newEmail');
	// field = email or attendee_email
	// sub1 = subscriber 1 (the "from" subscriber)
	// sub2 = subscriber 2 (the "to" subscriber)
	// mlist = if non-blank, what to do with mailing lists
	// exsub = if non-blank the choice for the old sub
	// user = if non-blank the choice for the old user
	// newEmail = the email address wanted
	foreach ($text_fields as $text_field) {
		$$text_field = '';
		if(isset($_POST[$text_field])){
			$$text_field = sanitize_text_field($_POST[$text_field]);
		}
	}
	if($field == 'email'){
		$response['update'] = 'mail-lists';
	}else{
		$response['update'] = 'attendee-mail-lists';
	}
	if($payment_id == 0){
		$response['msg'] = 'Data invalid - ignored.';
	}else{
		$payment_data = cc_paymentdb_get_payment($payment_id);
		if($payment_data === NULL){
			$response['msg'] = 'Payment '.$payment_id.' not found - nothing updated';
		}else{
			$ml_msg = $sub_msg = '';
			if($mlist <> ''){
				if($mlist == 'chg'){
					// Change subscriber email address
					$subscriber = mailster('subscribers')->get($sub1);
					if(!$subscriber){
						$response['msg'] = 'Subscriber '.$sub1.' not found - nothing updated';
					}else{
						$subscriber->email = $newEmail;
						$subscriber_id = mailster('subscribers')->update($subscriber);
					}
				}elseif($mlist == 'cpy' || $mlist == 'new'){
					// create a new subscriber
			        $overwrite = true;
			        if($field == 'email'){
						$userdata = array(
				            'email' => $newEmail,
				            'firstname' => $payment_data["firstname"],
				            'lastname' => $payment_data['lastname'],
				            'last_payment_id' => $payment_data['id'],
				        );
			        }else{
						$userdata = array(
				            'email' => $newEmail,
				            'firstname' => $payment_data["attendee_firstname"],
				            'lastname' => $payment_data['attendee_lastname'],
				            'last_payment_id' => $payment_data['id'],
				        );
			        }
			        $sub2 = mailster('subscribers')->add($userdata, $overwrite);
			        if($mlist == 'cpy'){
						// copy the mailing lists of the old subscriber
				        $sub1_list_ids = mailster('subscribers')->get_lists($sub1, true); // true = get ids only
						$result = mailster('subscribers')->assign_lists($sub2, $sub1_list_ids);
						$ml_msg = 'Mailing lists copied. ';
			        }else{
			        	// 'new'
			        	// Assign mailing lists for this w/shop/rec. only
			        	// get all mailster lists
						$lists = mailster( 'lists' )->get();
						$new_list_ids = array();
			        	if($payment_data['type'] == 'recording'){
							// now find the list for this recording
							$list_title = 'Recording: '.get_the_title($payment_data['workshop_id']); // really the recording_id
							$sanitized_list_title = sanitize_title($list_title);
							$list_id = 0;
							foreach ($lists as $list){
								if($list->slug == $sanitized_list_title){
									$list_id = $list->ID;
									break;
								}
							}
							// if not found, create it
					        if($list_id == 0){
					        	$list_id = mailster('lists')->add($list_title);
					        }
					        $new_list_ids[] = $list_id;
					        // do they want to be on the mailing list?
					        if($payment_data['mailing_list'] == 'y'){
								$list_id = 0;
								foreach ($lists as $list){
									if($list->name == 'Contextual Consulting Events'){
										$list_id = $list->ID;
										break;
									}
								}
								// if not found, create it
						        if($list_id == 0){
						        	$list_id = mailster('lists')->add('Contextual Consulting Events');
						        }
						        $new_list_ids[] = $list_id;
					        }
					        mailster('subscribers')->assign_lists($sub2, $new_list_ids);
					    }else{
					    	// workshop
					        // we want the list ID for the Registration list, the newsletter list and also for this workshop (plus possibly the upsell workshop)
					        $reg_list_id = $wkshop_list_id = $news_list_id = $upsell_list_id = 0;
					        $workshop_title = ccpa_workshop_title($payment_data);
					        $sanitised_workshop_title = sanitize_title( $workshop_title );
					        $upsell_list_slug = '';
					        if($payment_data['upsell_workshop_id'] > 0){
					        	$upsell_title = get_the_title($payment_data['upsell_workshop_id']);
					        	$upsell_list_slug = sanitize_title($upsell_title);
					        }
							// let's work out the list names for each of the events
							$event_lists = array();
							if($payment_data['event_ids'] <> ''){
								$event_ids = explode(',', $payment_data['event_ids']);
								foreach ($event_ids as $event_id) {
									if($event_id <> ''){
										$event_name = get_post_meta($payment_data['workshop_id'], 'event_'.$event_id.'_name', true);
										$list_name = $workshop_title.': '.$event_name;
										$sanitized_list_name = sanitize_title($list_name);
										if($event_name <> ''){
											$event_lists[] = array(
												'slug' => $sanitized_list_name,
												'title' => $list_name,
												'id' => 0,
											);
										}
									}
								}
							}
					        foreach ($lists as $list) {
					        	if($list->name == 'Registration'){
					        		$reg_list_id = $list->ID;
					        	}
					        	if($list->name == 'Contextual Consulting Events'){
					        		$news_list_id = $list->ID;
					        	}
					        	if($list->slug == $sanitised_workshop_title){
					        		$wkshop_list_id = $list->ID;
					        	}
					        	if($upsell_list_slug <> '' && $list->slug == $upsell_list_slug){
					        		$upsell_list_id = $list->ID;
					        	}
					        	foreach ($event_lists as $key => $event) {
					        		if($list->slug == $event['slug']){
					        			$event_lists[$key]['id'] = $list->ID;
					        			break;
					        		}
					        	}
					        }
					        // did we find the registration list? If not, create it
					        if($reg_list_id == 0){
					        	$reg_list_id = mailster('lists')->add('Registration');
					        }
					        // did we find the news list? If not, create it
					        if($news_list_id == 0){
					        	$news_list_id = mailster('lists')->add('Contextual Consulting Events');
					        }
					        // did we find the workshop list? If not, create it
					        if($wkshop_list_id == 0){
					        	$wkshop_list_id = mailster('lists')->add($workshop_title);
					        }
					        // did we need and find the upsell workshop list? If not, create it
					        if($upsell_list_slug <> '' && $upsell_list_id == 0){
					        	$upsell_list_id = mailster('lists')->add($upsell_title);
					        }
					        // events lists to add?
					        foreach ($event_lists as $key => $event){
					        	if($event['id'] == 0){
					        		$event_list_id = mailster('lists')->add($event['title']);
					        		$event_lists[$key]['id'] = $event_list_id;
					        	}
					        }
					        // assign the subscriber to the lists
					        if($field == 'email'){
					        	$wanted_lists = array($reg_list_id);
					        }else{
					        	$wanted_lists = array();
					        }
					        if($payment_data['mailing_list'] == 'y'){
					        	$wanted_lists[] = $news_list_id;
					        }
					        // if the attendee_email is not set then add the subscriber to the workshop lists
					        if(($field == 'email' && $payment_data['attendee_email'] == '') || $field == 'attendee_email' ){
					        	$wanted_lists[] = $wkshop_list_id;
					        	if($upsell_list_id > 0){
					        		$wanted_lists[] = $upsell_list_id;
					        	}
						        foreach ($event_lists as $key => $event){
						        	$wanted_lists[] = $event['id'];
						        }
					        }
					        mailster('subscribers')->assign_lists($sub2, $wanted_lists);
					    }
				        $ml_msg = 'Mailing lists set up. ';
		        	}
		        }elseif($mlist == 'swi'){
		        	// Leave mailing lists unchanged
		    	}elseif($mlist == 'mge'){
		    		// New subscriber to <strong>also</strong> have mailing lists of old subscriber (merge)
		    		$sub1_list_ids = mailster('subscribers')->get_lists($sub1, true);
		    		$result = mailster('subscribers')->assign_lists($sub2, $sub1_list_ids);
			        $ml_msg = 'Mailing lists merged. ';
		    	}elseif($mlist == 'mov'){
		    		// New subscriber to <strong>only</strong> have mailing lists of old subscriber (move)
		    		$sub1_list_ids = mailster('subscribers')->get_lists($sub1, true);
		    		$result = mailster('subscribers')->assign_lists($sub2, $sub1_list_ids, true);
			        $ml_msg = 'Mailing lists moved. ';
		    	}
		    }
		    if($exsub <> ''){
		    	if($exsub == 'kee'){
		    		// Keep the old subscriber
		    	}elseif($exsub == 'del'){
		    		// Delete the old subscriber
		    		$result = mailster('subscribers')->remove($sub1);
		    		$sub_msg = 'Old subscriber deleted.';
		    	}
		    }
		    $user_msg = ' ';
		    $new_user_id = NULL;
		    if($user <> '' || $payment_data['type'] == 'recording'){
		    	// must be a recording
		    	// if $user == 'zip', we do nothing with users ... apart from adding the recording access
		    	if($user == 'cre' || $user == 'crd'){
		    		// Create new user login and send <em>create password</em> email
		    		$new_user = get_user_by('email', $newEmail);
		    		if($new_user){
		    			$response['msg'] = 'User '.$newEmail.' already exists - new user not created';
		    		}else{
		    			$new_username = $payment_data['firstname'].' '.$payment_data['lastname'].' '.uniqid();
		    			$new_user_id = wp_create_user( $new_username, wp_generate_password(), $newEmail );
						if($field == 'email' && ($payment_data['firstname'] <> '' || $payment_data['lastname'] <> '')){
							$args = array(
								'ID' => $new_user_id,
								'first_name' => $payment_data['firstname'],
								'last_name' => $payment_data['lastname'],
							);
							wp_update_user($args);
						}
						$metas = array('title', 'phone', 'vat', 'maillist', 'pmtmethod', 'invaddr', 'invemail', 'invphone', 'invref', 'amount', 'clientSecret', 'currency', 'voucher', 'discAmount');
						foreach ($metas as $meta) {
							update_user_meta($new_user_id, $metakey, $payment_data[$meta]);
						}
						update_user_meta($new_user_id, 'new_user_recording_id', $payment_data['workshop_id']);
						wp_new_user_notification( $new_user_id, null, 'user' );
						$user_msg = 'New user created and email triggered. ';
		    		}		    		
		    	}
		    	if($user == 'del' || $user == 'crd'){
					// and also remove old user
					$old_user = get_user_by('email', $payment_data[$field]);
					if($old_user){
						$result = wp_delete_user( $old_user->ID, $new_user_id );
						$user_msg .= 'Old user deleted. ';
					}
		    	}
		    	// grant access to new user
		    	if($new_user_id === NULL){
		    		$new_user = get_user_by('email', $newEmail);
		    		if($new_user){
		    			$new_user_id = $new_user->ID;
		    		}
		    	}
		    	if($new_user_id !== NULL){
		    		ccrecw_add_recording_to_user($new_user_id, $payment_data['workshop_id'], 'paid', $payment_data['payment_amount'], $payment_data['token'], $payment_data['currency'], $payment_data['vat_included'], $payment_id);
		    		$user_msg .= 'Recording access granted to new email';
		    	}
		    }
		    // now, finally, update the email address ...
			$payment_data[$field] = $newEmail;
			if(cc_paymentdb_update_payment($payment_data) === false){
				$response['msg'] = 'Failed to update '.$field;
			}else{
				$response['msg'] = $field.' updated to '.$newEmail.'. '.$ml_msg.$sub_msg.$user_msg;
				$response['status'] = 'ok';
				$response['html'] = payment_edit_mailster_lists($newEmail);
			}
		}
	}
    echo json_encode($response);
    die();
}

// returns a stripe id or false
// a stripe id can start with pi_ (= payment intent) or ch_ (= charge)
function payment_edit_stripe_id($payment_data){
	$stripe_id = false;
	if($payment_data['token'] <> ''){
		if(substr($payment_data['token'], 0, 4) == 'tok_'){
			// that was the old (and useless) way of stroing stripe data but we may find a charge id elsewhere...
			if(strpos($payment_data['notes'], 'ch_')){
				$stripe_id = substr($payment_data['notes'], strpos($payment_data['notes'], 'ch_'), 27);
			}
		}else{
			$stripe_id = $payment_data['token'];
		}
	}
	return $stripe_id;
}