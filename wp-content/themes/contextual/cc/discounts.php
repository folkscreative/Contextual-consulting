<?php
/**
 * Discounts
 */

// add or update the discounts tables
add_action('init', 'cc_discounts_upd_tables');
function cc_discounts_upd_tables(){
	global $wpdb;
	// discount codes
	// v2 added discount type: 'p' = percent, 'a' = amount
	$ccpa_discounts_db_ver = 2;
	$installed_table_ver = get_option('ccpa_discounts_db_ver');
	if($installed_table_ver <> $ccpa_discounts_db_ver){
		$discounts_table = $wpdb->prefix.'ccpa_discounts';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $discounts_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			disc_code varchar(25) NOT NULL,
			disc_start date NOT NULL,
			disc_end date NOT NULL,
			disc_type char(1) NOT NULL,
			disc_amount decimal(9,2) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccpa_discounts_db_ver', $ccpa_discounts_db_ver);
	}
	// discounts to workshops
	$ccpa_disc_workshops_db_ver = 1;
	$installed_table_ver = get_option('ccpa_disc_workshops_db_ver');
	if($installed_table_ver <> $ccpa_disc_workshops_db_ver){
		$disc_workshops_table = $wpdb->prefix.'disc_workshops';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $disc_workshops_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			disc_id mediumint(9) NOT NULL,
			workshop_id mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccpa_disc_workshops_db_ver', $ccpa_disc_workshops_db_ver);
	}
	// discounts to recordings
	$ccpa_disc_recordings_db_ver = 1;
	$installed_table_ver = get_option('ccpa_disc_recordings_db_ver');
	if($installed_table_ver <> $ccpa_disc_recordings_db_ver){
		$disc_recordings_table = $wpdb->prefix.'disc_recordings';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $disc_recordings_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			disc_id mediumint(9) NOT NULL,
			recording_id mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccpa_disc_recordings_db_ver', $ccpa_disc_recordings_db_ver);
	}
}

// checks to see if any discounts are possible right now (for a workshop)
// used to decide whether or not to show the voucher field on the form
function cc_discounts_possible_discount($training_type, $training_id){
	global $wpdb;
	$discounts_table = $wpdb->prefix.'ccpa_discounts';
	$disc_workshops_table = $wpdb->prefix.'disc_workshops';
	$disc_recordings_table = $wpdb->prefix.'disc_recordings';
	$now = date('Y-m-d');
	$sql = "SELECT * FROM $discounts_table WHERE disc_start < '$now' AND (disc_end = '0000-00-00' OR disc_end > '$now')";
	$discounts = $wpdb->get_results($sql, ARRAY_A);
	if($discounts === false){
		return false;
	}
	// restricted to specific workshops or recordings?
	foreach ($discounts as $discount) {
		$discount_id = $discount['id'];
		$sql = "SELECT workshop_id FROM $disc_workshops_table WHERE disc_id = $discount_id";
		$workshops = $wpdb->get_col($sql);
		$sql = "SELECT recording_id FROM $disc_recordings_table WHERE disc_id = $discount_id";
		$recordings = $wpdb->get_col($sql);
		if(empty($workshops) && empty($recordings)){
			// we've found an acceptable discount code
			return true;
		}
		if( ( $training_type == 'w' && in_array($training_id, $workshops ) ) || ( $training_type == 'r' && in_array($training_id, $recordings ) ) ){
			// we've found an acceptable discount code
			return true;
		}
	}
	return false;
}

// checks to see if discount code exists and is valid for this workshop/recording
// Returns false or discount row
function cc_discount_lookup($disc_code, $training_type, $training_id){
	global $wpdb;
	$discounts_table = $wpdb->prefix.'ccpa_discounts';
	$disc_workshops_table = $wpdb->prefix.'disc_workshops';
	$disc_recordings_table = $wpdb->prefix.'disc_recordings';
	$sql = "SELECT * FROM $discounts_table WHERE disc_code = '$disc_code' LIMIT 1";
	$discount = $wpdb->get_row($sql, ARRAY_A);
	if($discount === null){
		return false;
	}
	$now = date('Y-m-d');
	if($now < $discount['disc_start'] || $discount['disc_end'] <> '0000-00-00' && $now > $discount['disc_end'] ){
		return false;
	}
	$discount_id = $discount['id'];
	if($training_type == 'w'){
		$sql = "SELECT workshop_id FROM $disc_workshops_table WHERE disc_id = $discount_id";
		$workshops = $wpdb->get_col($sql);
		if(empty($workshops)){
			// code valid on all workshops
			return $discount;
		}
		if(in_array($training_id, $workshops)){
			// valid on this workshop
			return $discount;
		}
		return false;
	}
	// recording
	$sql = "SELECT recording_id FROM $disc_recordings_table WHERE disc_id = $discount_id";
	$recordings = $wpdb->get_col($sql);
	if(empty($recordings)){
		// code valid on all recordings
		return $discount;
	}
	if(in_array($training_id, $recordings)){
		// valid on this workshop
		return $discount;
	}
	return false;
}


// ajax lookup of a promo code
// returns msg  - js will then trigger price update
add_action('wp_ajax_reg_promo_lookup', 'cc_discounts_reg_promo_lookup');
add_action('wp_ajax_nopriv_reg_promo_lookup', 'cc_discounts_reg_promo_lookup');
function cc_discounts_reg_promo_lookup(){
	$response = array(
		'status' => 'error',
		'msg' => 'Code not valid',
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
	$promo = '';
	if(isset($_POST['promo'])){
		$promo = stripslashes( sanitize_text_field( $_POST['promo'] ) );
	}
    $token = '';
    if(isset($_POST['token'])){
        $token = sanitize_text_field( $_POST['token'] );
    }
	if($trainingType <> '' && $trainingID > 0 && $currency <> '' && $vatExempt <> '' && $promo <> ''){
		if( substr( $promo, 0, 3) == 'CC-' ){
			if( cc_friend_can_i_use_it( $promo, get_current_user_id() ) ){
				$response['status'] = 'ok';
				$response['msg'] = 'Promotional code applied';
				$response['code'] = $promo;
			}
		}else{
			$discount = cc_discount_lookup($promo, $trainingType, $trainingID);
			if($discount){
                if($discount['disc_type'] == 'p' && $discount['disc_amount'] == 100){
                    if(!cc_validate_single_registrant($token)){
                        $response['msg'] = 'This promotional code can only be used when registering for yourself only';
                        echo json_encode($response);
                        die();
                    }
                }
				$response['status'] = 'ok';
				$response['msg'] = 'Promotional code applied';
				$response['code'] = $promo;
			}
		}
	}
	echo json_encode($response);
	die();
}

/**
 * Validate that the registration is for a single registrant only
 * Returns true if there's exactly one attendee who is the registrant
 * Returns false otherwise (multiple attendees, no attendees, or attendee is not registrant)
 */
function cc_validate_single_registrant($token) {
    if (empty($token)) {
        return false;
    }
    
    $registration_data = TempRegistration::get($token);
    if (!$registration_data) {
        return false;
    }
    
    $form_data = json_decode($registration_data->form_data, true);
    if (!$form_data || !isset($form_data['attendees'])) {
        return false;
    }
    
    $attendees = $form_data['attendees'];
    
    // Check if attendees is an array
    if (!is_array($attendees)) {
        return false;
    }
    
    // Must have exactly one attendee
    if (count($attendees) !== 1) {
        return false;
    }
    
    // That attendee must be the registrant
    $attendee = $attendees[0];
    if (($attendee['registrant'] ?? '') !== 'r') {
        return false;
    }
    
    return true;
}

// gets all discounts and all their connections
// returns an array of arrays
function cc_discounts_get_discounts(){
	global $wpdb;
	$discounts_table = $wpdb->prefix.'ccpa_discounts';
	$disc_workshops_table = $wpdb->prefix.'disc_workshops';
	$disc_recordings_table = $wpdb->prefix.'disc_recordings';
	$sql = "SELECT * FROM $discounts_table ORDER BY id DESC";
	$discounts = $wpdb->get_results($sql, ARRAY_A);
	if(!$discounts) return array();
	foreach ($discounts as $discount) {
		$discount_id = $discount['id'];
		$sql = "SELECT workshop_id FROM $disc_workshops_table WHERE disc_id = $discount_id";
		$workshops = $wpdb->get_col($sql);
		$sql = "SELECT recording_id FROM $disc_recordings_table WHERE disc_id = $discount_id";
		$recordings = $wpdb->get_col($sql);
		$result[] = array(
			'id' => $discount['id'],
			'disc_code' => $discount['disc_code'],
			'disc_start' => $discount['disc_start'],
			'disc_end' => $discount['disc_end'],
			'disc_type' => $discount['disc_type'],
			'disc_amount' => $discount['disc_amount'],
			'workshops' => $workshops,
			'recordings' => $recordings,
		);
	}
	return $result;
}

// add or update a discount record (inc practitioners and treatments)
function cc_discounts_discount_update($disc_id, $disc_code, $disc_type, $disc_amount, $disc_start, $disc_end, $workshops, $recordings){
	global $wpdb;
	$discounts_table = $wpdb->prefix.'ccpa_discounts';
	$disc_workshops_table = $wpdb->prefix.'disc_workshops';
	$disc_recordings_table = $wpdb->prefix.'disc_recordings';
	if($disc_start <> ''){
		$date_arr = explode('/', $disc_start);
		$disc_start = $date_arr[2].'-'.$date_arr[1].'-'.$date_arr[0];
	}
	if($disc_end <> ''){
		$date_arr = explode('/', $disc_end);
		$disc_end = $date_arr[2].'-'.$date_arr[1].'-'.$date_arr[0];
	}
	$data = array(
		'disc_code' => $disc_code,
		'disc_type' => $disc_type,
		'disc_amount' => $disc_amount,
		'disc_start' => $disc_start,
		'disc_end' => $disc_end,
	);
	$format = array('%s', '%s', '%f', '%s', '%s');
	if($disc_id == 0){
		$wpdb->insert($discounts_table, $data, $format);
		$disc_id = $wpdb->insert_id;
	}else{
		$where = array(
			'id' => $disc_id
		);
		$wpdb->update($discounts_table, $data, $where, $format);
		$where = array(
			'disc_id' => $disc_id
		);
		$wpdb->delete($disc_workshops_table, $where);
		$wpdb->delete($disc_recordings_table, $where);
	}
	$format = array('%d', '%d');
	foreach ($workshops as $workshop) {
		$data = array(
			'disc_id' => $disc_id,
			'workshop_id' => $workshop,
		);
		$wpdb->insert($disc_workshops_table, $data, $format);
	}
	foreach ($recordings as $recording) {
		$data = array(
			'disc_id' => $disc_id,
			'recording_id' => $recording,
		);
		$wpdb->insert($disc_recordings_table, $data, $format);
	}
	return $disc_id;
}

// Discounts page
add_action( 'admin_menu', 'cc_discounts_add_discounts_page');
function cc_discounts_add_discounts_page(){
	add_menu_page('Promotional Codes', 'Promo Codes', 'edit_posts', 'discounts', 'cc_discounts_discounts_summary_page', 'dashicons-carrot', 24.6 );
	add_submenu_page( 'discounts', 'Vouchers', 'Vouchers', 'edit_posts', 'vouchers', 'ccpa_vouchers_admin_page' ); // in the vouchers-admin file
	add_submenu_page( 'discounts', 'Voucher List', 'Voucher List', 'edit_posts', 'voucher-list', 'ccpa_vouchers_list_page' ); // in the vouchers-list file
	add_submenu_page( 'discounts', 'Refer a Friend', 'Refer Friend', 'edit_posts', 'friend-list', 'cc_friends_list_page' ); // in the friend-list file
}
// display the discounts summary page
function cc_discounts_discounts_summary_page(){
	if(!current_user_can('edit_posts')) wp_die('Go away!');
	global $wpdb;
	$discounts_table = $wpdb->prefix.'ccpa_discounts';
	$errors = array();
	$info = array();
	if(isset($_POST["discount_update"])){
		$disc_id = 0;
		if(isset($_POST['disc_id'])){
			$disc_id = absint($_POST['disc_id']);
		}
		if($disc_id == 0){
			$action = 'Add';
			$nonce = 'new discount code';
		}else{
			$action = 'Update';
			$nonce = 'discount '.$disc_id;
		}
		if(check_admin_referer($nonce)){
			$disc_code = '';
			if(!isset($_POST["disc_code"])){
				$errors[] = 'Promo Code missing';
			}else{
				$disc_code = sanitize_text_field($_POST["disc_code"]);
				if(strlen($disc_code) < 4){
					$errors[] = 'Promo Code '.$disc_code.' too short';
				}else{
					if(strlen($disc_code) > 25){
						$errors[] = 'Promo Code '.$disc_code.' too long';
					}else{
						$sql = "SELECT id FROM $discounts_table WHERE disc_code = '$disc_code' AND id <> $disc_id LIMIT 1";
						$prev_id = $wpdb->get_var($sql);
						if($prev_id){
							$errors[] = 'Promo Code '.$disc_code.' already used';
						}
					}
				}
			}
			$valid_disc_types = array('a', 'p'); // amount or percentage
			$disc_type = '';
			if(!isset($_POST['disc_type'])){
				$errors[] = 'Promo type missing';
			}else{
				if(in_array($_POST['disc_type'], $valid_disc_types)){
					$disc_type = $_POST['disc_type'];
				}else{
					$errors[] = 'Promo type invalid';
				}
			}
			$disc_amount = 0;
			if(!isset($_POST['disc_amount'])){
				$errors[] = 'Promo amount missing';
			}else{
				$disc_amount = (float) $_POST['disc_amount'];
				if($disc_amount <= 0){
					$errors[] = 'Promo amount '.$disc_amount.' invalid';
				}else{

				}
			}
			$disc_start = '';
			$disc_end = '';
			$date_fields = array('disc_start', 'disc_end');
			foreach ($date_fields as $date_field) {
				if(isset($_POST[$date_field]) && $_POST[$date_field] <> ''){
					$poss_date_str = sanitize_text_field($_POST[$date_field]);
					$poss_date = explode('/', $poss_date_str);
					if(!checkdate($poss_date[1], $poss_date[0], $poss_date[2]) || $poss_date[2] < 2000){
						if($date_field == 'disc_start'){
							$errors[] = 'Start date '.$poss_date_str.' invalid';
						}else{
							$errors[] = 'End date '.$poss_date_str.' invalid';
						}
					}else{
						$new_date = $poss_date[0].'/'.$poss_date[1].'/'.$poss_date[2];
						if($date_field == 'disc_start'){
							$disc_start = $new_date;
							$disc_start_calc = sprintf('%04d', $poss_date[2]).sprintf('%02d', $poss_date[1]).sprintf('%02d', $poss_date[0]);
						}else{
							$disc_end_calc = sprintf('%04d', $poss_date[2]).sprintf('%02d', $poss_date[1]).sprintf('%02d', $poss_date[0]);
							if($disc_start <> '' && $disc_end_calc <= $disc_start_calc){
								$errors[] = 'End date '.$new_date.' must be greater than Start date '.$disc_start;
							}else{
								$disc_end = $new_date;
							}
						}
					}
				}
			}
			$workshops = array();
			if(isset($_POST['disc_workshop'])){
				foreach ($_POST['disc_workshop'] as $workshop) {
					if(absint($workshop) > 0){
						$workshops[] = absint($workshop);
					}
				}
			}
			$recordings = array();
			if(isset($_POST['disc_recording'])){
				foreach ($_POST['disc_recording'] as $recording) {
					if(absint($recording) > 0){
						$recordings[] = absint($recording);
					}
				}
			}
			if(empty($errors)){
				$disc_id = cc_discounts_discount_update($disc_id, $disc_code, $disc_type, $disc_amount, $disc_start, $disc_end, $workshops, $recordings);
				if($action == 'Add'){
					$info[] = 'Promo code '.$disc_code.' successfully added';
				}else{
					$info[] = 'Promo code '.$disc_code.' successfully updated';
				}
				$disc_id = 0;
				$disc_code = '';
				$disc_type = '';
				$disc_amount = 0;
				$disc_start = '';
				$disc_end = '';
				$workshops = array();
				$recordings = array();
			}
		}else{
			// won't get here as check_admin_referer dies on failure
			$errors[] = 'Nonce error';
		}
	}
	// get all workshops
	$args = array(
		'post_type' => 'workshop',
		'posts_per_page' => -1,
	);
	$workshops = get_posts($args);
	// get all recordings
	$args = array(
		'post_type' => 'recording',
		'posts_per_page' => -1,
	);
	$recordings = get_posts($args);
	$now = time();
	$discounts = cc_discounts_get_discounts();
	?>
	<div class="wrap">
		<h2>Promotional codes</h2>
		<?php if(!empty($errors)){ ?>
			<p class="rpm-discount-msg wp-ui-notification">
				<?php foreach ($errors as $error) {
					echo $error.'<br>';
				} ?>
				Input data ignored
			</p>
		<?php } ?>
		<?php if(!empty($info)){ ?>
			<p class="rpm-discount-msg wp-ui-highlight">
				<?php foreach ($info as $info_msg) {
					echo $info_msg.'<br>';
				} ?>
			</p>
		<?php } ?>
		<div id="poststuff">
			<div id="post-body">
				<form action="" method="post">
					<?php wp_nonce_field('new discount code'); ?>
					<input type="hidden" name="disc_id" value="0">
					<div class="postbox">
						<h3 class="hndle">
							<label for="title">New Promo Code</label>
						</h3>
						<div class="inside">
							<p></p>
							<table class="form-table">
								<tr>
									<th><label for="disc_code_0">Promo code</label></th>
									<td>
										<input type="text" id="disc_code_0" name="disc_code" class="regular-text" value="">
										<p class="description">This should be a unique code of 4 to 25 characters</p>
									</td>
								</tr>
								<tr>
									<th><label for="disc_type_0">Type</label></th>
									<td>
										<select name="disc_type" id="disc_type_0">
											<option value="a">Amount</option>
											<option value="p">Percent</option>
										</select>
									</td>
								</tr>
								<tr>
									<th><label for="disc_amount_0">Amount/Percentage</label></th>
									<td>
										<input type="text" id="disc_amount_0" name="disc_amount" class="regular-text" value="" placeholder="0.00">
										<p class="description">If an amount is specified, this will apply to the VAT inclusive or exclusive amount depending on the VAT payment settings</p>
									</td>
								</tr>
								<tr>
									<th><label for="disc_start_0">Promo Start Date</label></th>
									<td>
										<input type="text" id="disc_start_0" name="disc_start" class="regular-text" value="" placeholder="dd/mm/yyyy">
										<p class="description">Leave blank for immediate start</p>
									</td>
								</tr>
								<tr>
									<th><label for="disc_end_0">Promo End Date</label></th>
									<td>
										<input type="text" id="disc_end_0" name="disc_end" class="regular-text" value="" placeholder="dd/mm/yyyy">
										<p class="description">Leave blank for no end date</p>
									</td>
								</tr>
								<tr>
									<th><label for="">Workshops (leave blank for any)</label></th>
									<td>
										<?php foreach ($workshops as $workshop) { ?>
											<input type="checkbox" name="disc_workshop[]" value="<?php echo $workshop->ID; ?>"> <?php echo $workshop->post_title; ?><br>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<th><label for="">Recordings (leave blank for any)</label></th>
									<td>
										<?php foreach ($recordings as $recording) { ?>
											<input type="checkbox" name="disc_recording[]" value="<?php echo $recording->ID; ?>"> <?php echo $recording->post_title; ?><br>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<th></th>
									<td><input type="submit" id="" name="discount_update" value="Add Promo Code" class="button-primary" /></td>
								</tr>
							</table>
						</div>
					</div>
				</form>
				<?php foreach ($discounts as $discount) { ?>
					<form action="" method="post">
						<?php wp_nonce_field('discount '.$discount['id']); ?>
						<input type="hidden" name="disc_id" value="<?php echo $discount['id']; ?>">
						<div class="postbox">
							<h3 class="hndle">
								<label for="title">Promo Code <?php echo $discount['disc_code']; ?></label>
							</h3>
							<div class="inside">
								<p></p>
								<table class="form-table">
									<tr>
										<th><label for="disc_code_<?php echo $discount['id']; ?>">Promo Code</label></th>
										<td>
											<input type="text" id="disc_code_<?php echo $discount['id']; ?>" name="disc_code" class="regular-text" value="<?php echo $discount['disc_code']; ?>">
											<p class="description">This should be a unique code of 4 to 25 characters</p>
										</td>
									</tr>
									<tr>
										<th><label for="disc_type_<?php echo $discount['id']; ?>">Type</label></th>
										<td>
											<select name="disc_type" id="disc_type_<?php echo $discount['id']; ?>">
												<option value="a" <?php selected('a', $discount['disc_type']); ?>>Amount</option>
												<option value="p" <?php selected('p', $discount['disc_type']); ?>>Percent</option>
											</select>
										</td>
									</tr>
									<tr>
										<th><label for="disc_amount_<?php echo $discount['id']; ?>">Amount/Percentage</label></th>
										<td>
											<input type="text" id="disc_amount_<?php echo $discount['id']; ?>" name="disc_amount" class="regular-text" value="<?php echo $discount['disc_amount']; ?>" placeholder="0.00">
											<p class="description">If an amount is specified, this will apply to the VAT inclusive or exclusive amount depending on the VAT payment settings</p>
										</td>
									</tr>
									<tr>
										<th><label for="disc_start_<?php echo $discount['id']; ?>">Promo Start Date</label></th>
										<td>
											<input type="text" id="disc_start_<?php echo $discount['id']; ?>" name="disc_start" class="regular-text" value="<?php echo ($discount['disc_start'] == '0000-00-00') ? '' : date('d/m/Y', strtotime($discount['disc_start'])); ?>" placeholder="dd/mm/yyyy">
											<p class="description">Leave blank for immediate start</p>
										</td>
									</tr>
									<tr>
										<th><label for="disc_end_<?php echo $discount['id']; ?>">Promo End Date</label></th>
										<td>
											<input type="text" id="disc_end_<?php echo $discount['id']; ?>" name="disc_end" class="regular-text" value="<?php echo ($discount['disc_end'] == '0000-00-00') ? '' : date('d/m/Y', strtotime($discount['disc_end'])); ?>" placeholder="dd/mm/yyyy">
											<p class="description">Leave blank for no end date</p>
										</td>
									</tr>
									<tr>
										<th><label for="">Workshops (leave blank for any)</label></th>
										<td>
											<?php foreach ($workshops as $workshop) {
												if(in_array($workshop->ID, $discount['workshops'])){
													$checked = ' checked="checked"';
												}else{
													$checked = '';
												} ?>
												<input type="checkbox" name="disc_workshop[]" value="<?php echo $workshop->ID; ?>"<?php echo $checked; ?>> <?php echo $workshop->post_title; ?><br>
											<?php } ?>
										</td>
									</tr>
									<tr>
										<th><label for="">Recordings (leave blank for any)</label></th>
										<td>
											<?php foreach ($recordings as $recording) {
												if(in_array($recording->ID, $discount['recordings'])){
													$checked = ' checked="checked"';
												}else{
													$checked = '';
												} ?>
												<input type="checkbox" name="disc_recording[]" value="<?php echo $recording->ID; ?>"<?php echo $checked; ?>> <?php echo $recording->post_title; ?><br>
											<?php } ?>
										</td>
									</tr>
									<tr>
										<th></th>
										<td><input type="submit" id="" name="discount_update" value="Update Promo Code" class="button-primary" /></td>
									</tr>
								</table>
							</div>
						</div>
					</form>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php
}
