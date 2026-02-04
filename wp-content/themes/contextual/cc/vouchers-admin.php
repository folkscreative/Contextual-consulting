<?php
/**
 * Vouchers admin - Updated with Series support
 */

// The submenu is added in the main discounts file
// this is its html ...
function ccpa_vouchers_admin_page(){
	if(!current_user_can('edit_posts')) wp_die('Go away!');
	global $wpdb;
	$errors = array();
	$info = array();

	// updating the currency exchange rates
	if(isset($_POST['currency_submit'])){
		$exchange_rates = array(
			'AUD' => $_POST['conv_rate_aud'] + 0,
			'USD' => $_POST['conv_rate_usd'] + 0,
			'EUR' => $_POST['conv_rate_eur'] + 0,
		);
		update_option('voucher_exchange_rates', $exchange_rates);
		$info[] = 'Exchange rates updated';
	}

	// updating the voucher offer
	if(isset($_POST['offer_submit'])){
		// var_dump($_POST);
		$offer_voucher = cc_voucher_core_offer_settings();
		if(isset($_POST['offer_active'])){
			$offer_voucher['active'] = 'active';
		}else{
			$offer_voucher['active'] = 'no';
		}
		if(isset($_POST['offer_voucher_gbp'])){
			$offer_voucher['offer_gbp'] = (float) $_POST['offer_voucher_gbp'];
		}else{
			$offer_voucher['offer_gbp'] = 0;
		}
		if(isset($_POST['offer_voucher_aud'])){
			$offer_voucher['offer_aud'] = (float) $_POST['offer_voucher_aud'];
		}else{
			$offer_voucher['offer_aud'] = 0;
		}
		if(isset($_POST['offer_voucher_usd'])){
			$offer_voucher['offer_usd'] = (float) $_POST['offer_voucher_usd'];
		}else{
			$offer_voucher['offer_usd'] = 0;
		}
		if(isset($_POST['offer_voucher_eur'])){
			$offer_voucher['offer_eur'] = (float) $_POST['offer_voucher_eur'];
		}else{
			$offer_voucher['offer_eur'] = 0;
		}
		if(isset($_POST['min_sale'])){
			$offer_voucher['min_sale'] = (float) $_POST['min_sale'];
		}else{
			$offer_voucher['min_sale'] = 0;
		}
		if(isset($_POST['pmt_method'])){
			$offer_voucher['pmt_method'] = $_POST['pmt_method'];
		}else{
			$offer_voucher['pmt_method'] = 'any';
		}
		if(isset($_POST['offer_start']) && $_POST['offer_start'] <> ''){
            $date = date_create_from_format('d/m/Y',$_POST['offer_start']);
            if($date){
            	// echo 'date good';
            	// echo $date->format('d/m/Y');
                $offer_voucher['offer_start'] = $date->format('d/m/Y');
            }else{
            	// echo 'date bad';
            	$offer_voucher['offer_start'] = '';
            }
		}else{
			// echo 'date missing';
			$offer_voucher['offer_start'] = '';
		}
		if(isset($_POST['offer_end']) && $_POST['offer_end'] <> ''){
            $date = date_create_from_format('d/m/Y',$_POST['offer_end']);
            if($date){
                $offer_voucher['offer_end'] = $date->format('d/m/Y');
            }else{
            	$offer_voucher['offer_end'] = '';
            }
		}else{
			$offer_voucher['offer_end'] = '';
		}
		
		// NEW: Handle course types as checkboxes
		// Store selected types in an array
		$selected_types = array();
		if(isset($_POST['course_type_workshop'])){
			$selected_types[] = 'workshop';
		}
		if(isset($_POST['course_type_recording'])){
			$selected_types[] = 'recording';
		}
		if(isset($_POST['course_type_series'])){
			$selected_types[] = 'series';
		}
		
		// If no types selected, default to 'any' (all types)
		if(empty($selected_types)){
			$offer_voucher['course_types'] = array('workshop', 'recording', 'series');
		}else{
			$offer_voucher['course_types'] = $selected_types;
		}
		
		// Keep the old course_type field for backwards compatibility
		// This helps if other code still expects the old format
		if(count($selected_types) == 3 || empty($selected_types)){
			$offer_voucher['course_type'] = 'any';
		}elseif(count($selected_types) == 1){
			$offer_voucher['course_type'] = $selected_types[0];
		}else{
			// Multiple but not all - store as 'custom' or 'any'
			$offer_voucher['course_type'] = 'any';
		}
		
		if(isset($_POST['offer_workshop'])){
			$offer_voucher['workshops'] = $_POST['offer_workshop'];
		}else{
			$offer_voucher['workshops'] = array();
		}
		if(isset($_POST['offer_recording'])){
			$offer_voucher['recordings'] = $_POST['offer_recording'];
		}else{
			$offer_voucher['recordings'] = array();
		}
		// NEW: Handle series selection
		if(isset($_POST['offer_series'])){
			$offer_voucher['series'] = $_POST['offer_series'];
		}else{
			$offer_voucher['series'] = array();
		}
		if(isset($_POST['expiry_mths'])){
			$offer_voucher['expiry_mths'] = (int) $_POST['expiry_mths'];
		}else{
			$offer_voucher['expiry_mths'] = 12;
		}
		update_option('voucher_offer_settings', $offer_voucher);
		$info[] = 'Voucher Offer Saved';
	}

	?>
	<div class="wrap">
		<h2>Vouchers</h2>
		<?php if(!empty($errors)){ ?>
			<p class="rpm-voucher-msg wp-ui-notification">
				<?php foreach ($errors as $error) {
					echo $error.'<br>';
				} ?>
				Input data ignored
			</p>
		<?php } ?>
		<?php if(!empty($info)){ ?>
			<p class="rpm-voucher-msg wp-ui-highlight">
				<?php foreach ($info as $info_msg) {
					echo $info_msg.'<br>';
				} ?>
			</p>
		<?php } ?>
		<div id="poststuff">
			<div id="post-body">
				<form action="" method="post">
					<?php wp_nonce_field('vouchers update');
					$exchange_rates = cc_voucher_core_exchange_rates();
					?>
					<div class="postbox">
						<h3 class="hndle">
							<label for="">Currencies</label>
						</h3>
						<div class="inside">
							<p></p>
							<table class="form-table">
								<tr>
									<th><label for="conv_rate_aud">Conversion Rate (GBP to AUD)</label></th>
									<td>
										<input type="text" id="conv_rate_aud" name="conv_rate_aud" class="regular-text" value="<?php echo $exchange_rates['AUD']; ?>">
										<p class="description">GBP £1.00 = AUD $<?php echo number_format($exchange_rates['AUD'],2); ?></p>
									</td>
								</tr>
								<tr>
									<th><label for="conv_rate_usd">Conversion Rate (GBP to USD)</label></th>
									<td>
										<input type="text" id="conv_rate_usd" name="conv_rate_usd" class="regular-text" value="<?php echo $exchange_rates['USD']; ?>">
										<p class="description">GBP £1.00 = USD $<?php echo number_format($exchange_rates['USD'],2); ?></p>
									</td>
								</tr>
								<tr>
									<th><label for="conv_rate_eur">Conversion Rate (GBP to EUR)</label></th>
									<td>
										<input type="text" id="conv_rate_eur" name="conv_rate_eur" class="regular-text" value="<?php echo $exchange_rates['EUR']; ?>">
										<p class="description">GBP £1.00 = EUR €<?php echo number_format($exchange_rates['EUR'],2); ?></p>
									</td>
								</tr>
								<tr>
									<th></th>
									<td><input type="submit" id="" name="currency_submit" value="Update Exchange Rates" class="button-primary" /></td>
								</tr>
							</table>
						</div>
					</div>

					<?php $offer_voucher = cc_voucher_core_offer_settings();
					// echo '<br>';
					// var_dump($offer_voucher);
					
					// Handle backwards compatibility - convert old course_type to new course_types array
					if(!isset($offer_voucher['course_types'])){
						if(isset($offer_voucher['course_type'])){
							switch($offer_voucher['course_type']){
								case 'any':
									$offer_voucher['course_types'] = array('workshop', 'recording', 'series');
									break;
								case 'workshop':
									$offer_voucher['course_types'] = array('workshop');
									break;
								case 'recording':
									$offer_voucher['course_types'] = array('recording');
									break;
								default:
									$offer_voucher['course_types'] = array('workshop', 'recording', 'series');
							}
						}else{
							$offer_voucher['course_types'] = array('workshop', 'recording', 'series');
						}
					}
					
					// Initialize series array if not set
					if(!isset($offer_voucher['series'])){
						$offer_voucher['series'] = array();
					}
					
					// get all workshops
					$args = array(
						'post_type' => 'workshop',
						'posts_per_page' => -1,
					);
					$workshops = get_posts($args);
					// get all recordings (now stored as 'course' post type with _course_type = 'on-demand')
					$args = array(
						'post_type' => 'course',
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
								'key' => '_course_type',
								'value' => 'on-demand',
								'compare' => '='
							)
						)
					);
					$recordings = get_posts($args);
					// get all series
					$args = array(
						'post_type' => 'series',
						'posts_per_page' => -1,
					);
					$series = get_posts($args);
					?>
					<div class="postbox">
						<h3 class="hndle">
							<label for="">Voucher Offer</label>
						</h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><label for="">Voucher Offer Active</label></th>
									<td>
										<div>
											<label for="offer_active" class="toggle-switch">
												<input type="checkbox" id="offer_active" name="offer_active" value="active" <?php checked($offer_voucher['active'], 'active'); ?>>
												<span class="toggle-slider round"></span>
											</label>
										</div>
									</td>
								</tr>
								<tr>
									<th><label for="">Amount</label></th>
									<td>
										<p>
											<label for="offer_voucher_gbp">GBP</label>
											<input type="text" id="offer_voucher_gbp" name="offer_voucher_gbp" class="regular-text" value="<?php echo $offer_voucher['offer_gbp']; ?>">
										</p>
										<p>
											<label for="offer_voucher_aud">AUD</label>
											<input type="text" id="offer_voucher_aud" name="offer_voucher_aud" class="regular-text" value="<?php echo $offer_voucher['offer_aud']; ?>">
										</p>
										<p>
											<label for="offer_voucher_usd">USD</label>
											<input type="text" id="offer_voucher_usd" name="offer_voucher_usd" class="regular-text" value="<?php echo $offer_voucher['offer_usd']; ?>">
										</p>
										<p>
											<label for="offer_voucher_eur">EUR</label>
											<input type="text" id="offer_voucher_eur" name="offer_voucher_eur" class="regular-text" value="<?php echo $offer_voucher['offer_eur']; ?>">
										</p>
									</td>
								</tr>
								<tr>
									<th><label for="min_sale">Minimum sale value</label></th>
									<td>
										<input type="text" id="min_sale" name="min_sale" class="regular-text" value="<?php echo $offer_voucher['min_sale']; ?>">
										<p class="description">Total purchase price (in GBP) including VAT where applicable</p>
									</td>
								</tr>
								<tr>
									<th><label for="pmt_method">Payment method</label></th>
									<td>
										<select name="pmt_method" id="pmt_method" class="regular-text">
											<option value="any" <?php selected($offer_voucher['pmt_method'], 'any'); ?>>Any</option>
											<option value="online" <?php selected($offer_voucher['pmt_method'], 'online'); ?>>Online only</option>
											<option value="invoice" <?php selected($offer_voucher['pmt_method'], 'invoice'); ?>>Invoice only</option>
										</select>
									</td>
								</tr>
								<tr>
									<th><label for="offer_start">Start Date</label></th>
									<td>
										<input type="text" id="offer_start" name="offer_start" class="regular-text" placeholder="dd/mm/yyyy" value="<?php echo $offer_voucher['offer_start']; ?>">
										<p class="description">Leave blank for immediate start. Includes this date.</p>
									</td>
								</tr>
								<tr>
									<th><label for="offer_end">End Date</label></th>
									<td>
										<input type="text" id="offer_end" name="offer_end" class="regular-text" placeholder="dd/mm/yyyy" value="<?php echo $offer_voucher['offer_end']; ?>">
										<p class="description">Leave blank for no end date. Includes this date.</p>
									</td>
								</tr>
								<tr>
									<th><label for="">Training Types Allowed</label></th>
									<td>
										<p>
											<label>
												<input type="checkbox" name="course_type_workshop" value="workshop" 
													<?php checked(in_array('workshop', $offer_voucher['course_types'])); ?>> 
												Live
											</label>
										</p>
										<p>
											<label>
												<input type="checkbox" name="course_type_recording" value="recording" 
													<?php checked(in_array('recording', $offer_voucher['course_types'])); ?>> 
												On-demand
											</label>
										</p>
										<p>
											<label>
												<input type="checkbox" name="course_type_series" value="series" 
													<?php checked(in_array('series', $offer_voucher['course_types'])); ?>> 
												Series
											</label>
										</p>
										<p class="description">Select which training types this voucher can be applied to. Leave all unchecked to allow any type.</p>
									</td>
								</tr>
								<tr>
									<th><label for="">Live training (leave blank for any)</label></th>
									<td>
										<?php foreach ($workshops as $workshop) {
											if(in_array($workshop->ID, $offer_voucher['workshops'])){
												$checked = 'checked';
											}else{
												$checked = '';
											}
											?>
											<input type="checkbox" name="offer_workshop[]" value="<?php echo $workshop->ID; ?>" <?php echo $checked; ?>> <?php echo $workshop->ID.': '.$workshop->post_title; ?><br>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<th><label for="">On-demand training (leave blank for any)</label></th>
									<td>
										<?php foreach ($recordings as $recording) {
											if(in_array($recording->ID, $offer_voucher['recordings'])){
												$checked = 'checked';
											}else{
												$checked = '';
											}
											?>
											<input type="checkbox" name="offer_recording[]" value="<?php echo $recording->ID; ?>" <?php echo $checked; ?>> <?php echo $recording->ID.': '.$recording->post_title; ?><br>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<th><label for="">Series (leave blank for any)</label></th>
									<td>
										<?php 
										if(!empty($series)){
											foreach ($series as $serie) {
												if(in_array($serie->ID, $offer_voucher['series'])){
													$checked = 'checked';
												}else{
													$checked = '';
												}
												?>
												<input type="checkbox" name="offer_series[]" value="<?php echo $serie->ID; ?>" <?php echo $checked; ?>> <?php echo $serie->ID.': '.$serie->post_title; ?><br>
											<?php }
										}else{
											echo '<p class="description">No series found</p>';
										}
										?>
									</td>
								</tr>
								<tr>
									<th><label for="">Expiry Months</label></th>
									<td>
										<input type="number" id="expiry_mths" name="expiry_mths" class="regular-text" value="<?php echo $offer_voucher['expiry_mths']; ?>">
										<p class="description">The expiry date will be calculated when the voucher is issued.</p>
									</td>
								</tr>
								<tr>
									<th></th>
									<td><input type="submit" id="" name="offer_submit" value="Update Voucher Offer" class="button-primary" /></td>
								</tr>
							</table>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php
}