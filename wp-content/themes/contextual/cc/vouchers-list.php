<?php
/**
 * Voucher List
 */

// The submenu is added in the discounts file
// this is its html ...
function ccpa_vouchers_list_page(){
	if(!current_user_can('edit_posts')) wp_die('Go away!');
	wp_enqueue_style('font-awesome');

	// new voucher to be generated?
	$msg = '';
	if(isset($_POST['new-voucher-submit'])){
		$currency = '';
		$amount = 0;
		if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies()) ){
			$currency = $_POST['currency'];
		}
		if(isset($_POST['amount'])){
			$amount = absint($_POST['amount']);
		}
		if($currency == '' || $amount < 1){
			$msg = $currency.' '.$amount.' appears invalid - no voucher generated!';
			$msg_type = 'error';
		}else{
			$voucher_code = ccpa_vouchers_alloc_generate();
			$voucher = ccpa_vouchers_create_new($voucher_code, $currency, $amount);
			$msg = 'Voucher '.cc_voucher_core_pretty_voucher($voucher_code).' generated for '.$currency.' '.$amount;
			$msg_type = 'success';
		}
	}

	?>

	<div class="wrap">
		<h2>All Vouchers</h2>
		<div id="poststuff">
			<div id="post-body">

				<div class="postbox">
					<div class="inside">
						<h3>Generate a New Voucher</h3>
						<?php if($msg <> ''){ ?>
							<p class="voucher-gen-msg <?php echo $msg_type; ?>"><?php echo $msg; ?></p>
						<?php } ?>
						<form action="" method="post">
							<table class="form-table">
								<tbody>
									<tr>
										<th>
											<label>Voucher Amount</label>
										</th>
										<td>
											<select name="currency">
												<?php foreach (cc_valid_currencies() as $currency) { ?>
													<option value="<?php echo $currency; ?>"><?php echo $currency; ?></option>
												<?php } ?>
											</select>
											<input type="number" min="1" step="1" name="amount">
										</td>
									</tr>
									<tr>
										<th></th>
										<td><input type="submit" name="new-voucher-submit" value="Generate" class="button-primary" /></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
				</div>


				<div class="postbox">
					<div class="inside">
						<h3>Allocated Vouchers</h3>
						<table class="striped">
							<thead>
								<tr>
									<th style="text-align:left;">ID</th>
									<th style="text-align:left;">Voucher Code</th>
									<th style="text-align:left;">Issued</th>
									<th style="text-align:left;">Expiry</th>
									<th style="text-align:left;">Curr.</th>
									<th style="text-align:right;">Amount</th>
									<th style="text-align:right;">Redeemed</th>
									<th style="text-align:right;">Balance</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$vouchers = ccpa_voucher_table_get_all();
								foreach ($vouchers as $voucher) {
									?>
									<tr valign="top">
										<td><?php echo $voucher['id']; ?></td>
										<td><?php echo cc_voucher_core_pretty_voucher($voucher['voucher_code']); ?></td>
										<td>
											<?php echo date('d/m/Y', strtotime($voucher['issue_time']) );
											echo ccpa_vouchers_list_pretty_payment(ccpa_voucher_issue_pmt_id($voucher['voucher_code']), $voucher['amount']);
											?>
										</td>
										<td><?php echo date('d/m/Y', strtotime($voucher['expiry_time']) ); ?></td>
										<td><?php echo $voucher['currency']; ?></td>
										<td style="text-align:right;"><?php echo number_format( $voucher['amount'], 2); ?></td>
										<td style="text-align:right;">
											<?php echo number_format( $voucher['redeemed'], 2);
											$voucher_pmts = ccpa_voucher_pay_rows($voucher['voucher_code']);
											foreach ($voucher_pmts as $voucher_pmt) {
												echo ccpa_vouchers_list_pretty_payment($voucher_pmt['payment_id'], $voucher_pmt['amount']);
											}
											$voucher_expiries = ccpa_voucher_expiry($voucher['voucher_code']);
											foreach ($voucher_expiries as $voucher_expiry) {
												echo 'Balance expired: '.number_format( $voucher_expiry['amount'], 2);
											}
											?>
										</td>
										<td style="text-align:right;"><?php echo number_format( $voucher['balance'], 2); ?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<?php
}

// gets pretty payment data
function ccpa_vouchers_list_pretty_payment($payment_id, $amount){
	$html = '';
	if($payment_id !== NULL){
		$payment = cc_paymentdb_get_payment($payment_id);
		if($payment){
			$html .= '<div>';
			$html .= $payment_id.': ';
			$html .= date('d/m/Y', strtotime($payment['last_update'])).' ';
			$html .= $payment['email'].' ';
			$html .= number_format( $amount, 2).' ';
			$payment_link = admin_url('admin.php?page=edit_payment&epmt='.$payment_id);
			$html .= '<a href="'.$payment_link.'" target="_blank"><i class="fas fa-external-link-alt"></i></a>';
			$html .= '</div>';
		}
	}
	return $html;
}

