<?php
/**
 * Payment DB things
 */

// create/update the table
add_action('init', 'cc_paymentdb_update_pmts_table');
function cc_paymentdb_update_pmts_table(){
	global $wpdb;
	$ccpa_pmts_table_ver = 25;
	// v25 (Jan 2026) added performance indexes for training access system
	// v24 (jun 2025 v2.9) added extra contact and address fields for invoice payments
	// no change to structure but payment_ref now used for the payment id of the series payment record
	// v23 (Feb 2025 v2.1) added invoice_id and invoice_no (Xero data)
	// v22 (Jan 2025 v2.0) expanded to add in upsell_payment_amount, Payment_intent_id, charge_id
	// v21 moved to theme (v1.0) added reg_userid, att_userid, student, earlybird, voucher_code, voucher_amount
	// v20 bump
	// v19 (v4.5.8) added ip_Address, attendee_firstname and attendee_lastname
	// v18 (v4.5.8) added extra vat fields (vat_uk, vat_employ, vat_employer, vat_website)
	// v17 (v4.5.0) added stripe_fee
	// v16 (v4.5.0) added refund_amount for stripe refunds
	// v15 (v4.5.0) added type so that we can add in recording payments too
	// v14 (v4.3.10.0) changed source from varchar 50 to 255
	// v13 (v4.3.9) added multi-currency and multi-events
	// v12 (v4.3.8) added webcast_attend
	// v11 (v4.3.7) added upsell_workshop_id
	// v10 added workshop_id
	// v9 added attendee_email - an optional field
	// v8 added invoice fields
	// v7 added voucher (discount) fields
	// v6 added payment ref
	// v5 bug - vat exempt and mailing list not null
	// v4 added vat, address, eu/uk resident and mailing list
	// v3 added source & TnCs
	// v2 added apptscode
	$installed_table_ver = get_option('ccpa_pmts_table_ver');
	if($installed_table_ver <> $ccpa_pmts_table_ver){
		$table_name = $wpdb->prefix.'ccpa_payments';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			last_update timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			token varchar(35) NOT NULL,
			status varchar(25) NOT NULL,
			title varchar(10) NOT NULL,
			firstname varchar(50) NOT NULL,
			lastname varchar(50) NOT NULL,
			email varchar(255) NOT NULL,
			phone varchar(30) NOT NULL,
			source varchar(255) NOT NULL,
			tandcs char(1) NOT NULL,
			payment_amount decimal(7,2) NOT NULL,
			notes text NOT NULL,
			apptscode varchar(100) NOT NULL,
			vat_included decimal(7,2) NOT NULL,
			address text NOT NULL,
			vat_exempt char(1) NOT NULL,
			mailing_list char(1) NOT NULL,
			payment_ref text NOT NULL,
			disc_code varchar(25) NOT NULL,
			disc_amount decimal(9,2) NOT NULL,
			pmt_method varchar(25) NOT NULL,
			inv_address text NOT NULL,
			inv_email varchar(255) NOT NULL,
			inv_phone varchar(30) NOT NULL,
			inv_ref varchar(255) NOT NULL,
			attendee_email varchar(255) NOT NULL,
			workshop_id mediumint(9) NOT NULL,
			upsell_workshop_id mediumint(9) NOT NULL,
			webcast_attend varchar(3) NOT NULL,
			currency varchar(3) NOT NULL,
			event_ids varchar(255) NOT NULL,
			type varchar(15) NOT NULL,
			refund_amount decimal(7,2) NOT NULL,
			stripe_fee decimal(7,2) NOT NULL,
			vat_uk char(1) NOT NULL,
			vat_employ varchar(25) NOT NULL,
			vat_employer varchar(255) NOT NULL,
			vat_website varchar(255) NOT NULL,
			ip_address varchar(45) NOT NULL,
			attendee_firstname varchar(50) NOT NULL,
			attendee_lastname varchar(50) NOT NULL,
			reg_userid mediumint(9) NOT NULL,
			att_userid mediumint(9) NOT NULL,
			student char(1) NOT NULL,
			earlybird char(1) NOT NULL,
			voucher_code varchar(12) NOT NULL,
			voucher_amount decimal(7,2) NOT NULL,
			upsell_payment_amount decimal(7,2) NOT NULL,
			payment_intent_id varchar(255) NOT NULL,
			charge_id varchar(255) NOT NULL,
			invoice_no varchar(25) NOT NULL,
			invoice_id varchar(36) NOT NULL,
			inv_org varchar(255) NOT NULL,
			inv_addr1 varchar(255) NOT NULL,
			inv_addr2 varchar(255) NOT NULL,
			inv_town varchar(255) NOT NULL,
			inv_county varchar(255) NOT NULL,
			inv_postcode varchar(255) NOT NULL,
			inv_country varchar(10) NOT NULL,
			inv_name varchar(255) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccpa_pmts_table_ver', $ccpa_pmts_table_ver);

		if( $ccpa_pmts_table_ver == 25 ){
			cc_paymentdb_add_performance_indexes($table_name);
		}
	}
}

/**
 * Add performance indexes to payments table
 * Called during table version upgrade to v25
 */
function cc_paymentdb_add_performance_indexes($table_name) {
	global $wpdb;
	
	// Get existing indexes
	$existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table_name", ARRAY_A);
	$index_names = array();
	foreach($existing_indexes as $index) {
		$index_names[] = $index['Key_name'];
	}
	
	// Add indexes if they don't exist
	
	// Index for registrant user ID with status (most common query)
	if(!in_array('idx_reg_userid_status', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_reg_userid_status (reg_userid, status)");
	}
	
	// Index for attendee user ID with status
	if(!in_array('idx_att_userid_status', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_att_userid_status (att_userid, status)");
	}
	
	// Index for workshop_id lookups
	if(!in_array('idx_workshop_id', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_workshop_id (workshop_id)");
	}
	
	// Index for upsell_workshop_id lookups
	if(!in_array('idx_upsell_workshop_id', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_upsell_workshop_id (upsell_workshop_id)");
	}
	
	// Combined index for type-based queries
	if(!in_array('idx_type_status', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_type_status (type, status)");
	}
	
	error_log('Training Access: Payments table performance indexes added');
}

// empty payment
function cc_paymentdb_empty_payment(){
	return array(
		'id' => 0,
		'last_update' => '',
		'token' => '',
		'status' => '',
		'title' => '',
		'firstname' => '',
		'lastname' => '',
		'email' => '',
		'phone' => '',
		'source' => '',
		'tandcs' => '',
		'payment_amount' => 0,
		'notes' => '',
		'apptscode' => '',
		'vat_included' => 0,
		'address' => '',
		'vat_exempt' => '',
		'mailing_list' => '',
		'payment_ref' => '',
		'disc_code' => '',
		'disc_amount' => 0,
		'pmt_method' => '',
		'inv_address' => '',
		'inv_email' => '',
		'inv_phone' => '',
		'inv_ref' => '',
		'attendee_email' => '',
		'workshop_id' => 0,
		'upsell_workshop_id' => 0,
		'webcast_attend' => '',
		'currency' => '',
		'event_ids' => '',
		'type' => '',
		'refund_amount' => 0,
		'stripe_fee' => 0,
		'vat_uk' => '',
		'vat_employ' => '',
		'vat_employer' => '',
		'vat_website' => '',
		'ip_address' => '',
		'attendee_firstname' => '',
		'attendee_lastname' => '',
		'reg_userid' => 0,
		'att_userid' => 0,
		'student' => '',
		'earlybird' => '',
		'voucher_code' => '',
		'voucher_amount' => 0,
		'upsell_payment_amount' => 0,
		'payment_intent_id' => '',
		'charge_id' => '',
		'invoice_no' => '',
		'invoice_id' => '',
		'inv_org' => '',
		'inv_addr1' => '',
		'inv_addr2' => '',
		'inv_town' => '',
		'inv_county' => '',
		'inv_postcode' => '',
		'inv_country' => '', // 2 char code
		'inv_name' => '',
	);
}

// gets a payment
// Returns null if no result is found
function cc_paymentdb_get_payment($id){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name WHERE id = $id LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}


// adds or updates a payment
// Sep 2025 changed to use tokens
// ccreg is needed for new registrations as that is how the attendees are found
// NOTE, this function does not update attendees but it will insert attendees on registration
// function cc_paymentdb_update_payment($payment_data, $ccreg=''){
function cc_paymentdb_update_payment($payment_data, $token=''){
	global $wpdb;
	cc_debug_log_anything([
		'function' => 'cc_paymentdb_update_payment',
		'token' => $token,
		'payment' => $payment_data,
	]);
	if(!is_array($payment_data)) return false;

	// why are we losing email addresses?
	/*
	if($payment_data['email'] == ''){
		$subject = 'CC ERROR - Email blank';
		$message = 'Payment data:<br>';
		$message .= print_r($payment_data, true);
		$message .= '<br><br>Request:<br>';
		$message .= print_r($_REQUEST, true);
		$message .= '<br><br>Backtrace:<br>';
		$message .= print_r(debug_backtrace(), true);
		$message .= '<br><br>IP: '.$_SERVER['REMOTE_ADDR'].'<br>';
		$message .= '<br><br>ccreg: '.$ccreg.'<br>';
		$to = get_bloginfo('admin_email');
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);
		wp_mail($to, $subject, $message, $headers);
	}
	*/

	$table_name = $wpdb->prefix.'ccpa_payments';
	if($payment_data['stripe_fee'] == 0){
		$stripe_fee = ccpa_stripe_fee($payment_data, true);
		if($stripe_fee <> '?' && $stripe_fee > 0){
			$payment_data['stripe_fee'] = $stripe_fee;
		}
	}
	// Mar 2025 found that sometimes, for some unkown reason, it's trying to lookup a payment intent id of 0 (eg when editing a payment in the backend). Quick fix added for now to stop it trying to loofk those ones up
	if( $payment_data['charge_id'] == '' && $payment_data['payment_intent_id'] <> '' && $payment_data['payment_intent_id'] <> '0' ){
		$payment_intent = cc_stripe_pmt_intent_retrieve( $payment_data['payment_intent_id'] );
		$latest_charge_id = $payment_intent->latest_charge;
		if( $latest_charge_id ){
			$payment_data['charge_id'] = $latest_charge_id;
		}
	}
	if(!isset($payment_data['ip_address']) || $payment_data['ip_address'] == ''){
		if(isset($_SERVER['REMOTE_ADDR'])){
			$payment_data['ip_address'] = $_SERVER['REMOTE_ADDR'];
		}else{
			$payment_data['ip_address'] = '';
		}
	}
	if(!isset($payment_data['last_update']) || $payment_data['last_update'] == ''){
		$payment_data['last_update'] = date('Y-m-d H:i:s');
	}
	if(isset($payment_data['id']) && $payment_data['id'] > 0){
		// update needed
		$orig_pay_data = cc_paymentdb_get_payment($payment_data['id']);
		if($orig_pay_data === NULL){
			$lookup_email = $payment_data['email'];
			$orig_attendee = '';
		}else{
			$lookup_email = $orig_pay_data['email'];
			$orig_attendee = $orig_pay_data['attendee_email'];
		}
		$where = array(
			'id' => $payment_data['id'],
		);
		$result = $wpdb->update($table_name, $payment_data, $where);
		if($result !== false){
			// update the workshop users or recording users tables

			// first delete any rows in the workshops_users or recordings_users tables for this payment record
			cc_myacct_workshops_users_delete_payment($payment_data['id']);
			cc_myacct_recordings_users_delete_payment($payment_data['id']);

			// now insert fresh data
			if( $payment_data['status'] <> 'Cancelled' ){
				cc_myacct_insert_workshops_recordings_users($payment_data);

				// and, if we're working with recordings and the recording_id has changed, switch the user meta data
				if( $orig_pay_data['workshop_id'] <> $payment_data['workshop_id'] 
						&& course_training_type( $orig_pay_data['workshop_id'] ) == 'recording' 
						&& course_training_type( $payment_data['workshop_id'] ) == 'recording' ){
				    $attendees = cc_attendees_for_payment( $payment_data['id'] );
				    foreach ( $attendees as $attendee ) {
				    	ccrecw_switch_user_recording( $payment_data['id'], $attendee['user_id'], $orig_pay_data['workshop_id'], $payment_data['workshop_id']);
			    	}
			    }

			    // and do the same for upsells too
				if( $orig_pay_data['upsell_workshop_id'] <> $payment_data['upsell_workshop_id'] 
						&& $orig_pay_data['upsell_workshop_id'] > 0
						&& $payment_data['upsell_workshop_id'] > 0
						&& course_training_type( $orig_pay_data['upsell_workshop_id'] ) == 'recording' 
						&& course_training_type( $payment_data['upsell_workshop_id'] ) == 'recording' ){
				    $attendees = cc_attendees_for_payment( $payment_data['id'] );
				    foreach ( $attendees as $attendee ) {
				    	ccrecw_switch_user_recording( $payment_data['id'], $attendee['user_id'], $orig_pay_data['upsell_workshop_id'], $payment_data['upsell_workshop_id']);
			    	}
			    }
			}

			return $payment_data['id'];

		}else{
			ccpa_send_failure($payment_data['id'], $payment_data);
			return false;
		}
	}else{
		// insert
		$result = $wpdb->insert($table_name, $payment_data);
		if($result === false){
			ccpa_send_failure(0, $payment_data);
			return false;
		}else{
			$payment_data['id'] = $wpdb->insert_id;
			// Use new token-based attendee saving instead of ccreg
			if($token <> ''){
				// save the attendees from a new registration
				// cc_attendees_save_from_reg($ccreg, $payment_data['id']);
                // save the attendees from the temporary registration system
                cc_attendees_save_from_token($token, $payment_data['id']);
			}
			// if it's not the main group payment record
			if( $payment_data['type'] <> 'group' ){
				// insert into the workshops users or recordings users tables
				cc_myacct_insert_workshops_recordings_users($payment_data);
			}
			return $payment_data['id'];
		}
	}
}

// returns the userid for a payment
// returns false if not found
function cc_paymentdb_get_userid($payment_data, $attendee=false){
	global $wpdb;
	$email = '';
	if($attendee){
		if(isset($payment_data['att_userid']) && $payment_data['att_userid'] > 0){
			return $payment_data['att_userid'];
		}
		if(isset($payment_data['attendee_email']) && $payment_data['attendee_email'] <> ''){
			$email = $payment_data['attendee_email'];
		}
	}else{
		if(isset($payment_data['reg_userid']) && $payment_data['reg_userid'] > 0){
			return $payment_data['reg_userid'];
		}
		if(isset($payment_data['email']) && $payment_data['email'] <> ''){
			$email = $payment_data['email'];
		}
	}
	if($email <> ''){
		$user = get_user_by('email', $email);
		if($user){
			return $user->ID;
		}
		$sql = "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'user_email_alias_%' AND meta_value = '$email'";
		$user_id = $wpdb->get_var($sql);
		if($user_id === NULL){
			return false;
		}
		return $user_id;
	}
	return false;
}

// get the latest payment for an email address
// ignores cancelled payments
// ignores a specific payment id (so we can get the previous one)
// returns NULL if not found
function cc_paymentdb_get_previous_payment($email, $ignore_id=0){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name WHERE id != $ignore_id AND email = '$email' AND status != 'Cancelled' ORDER BY id DESC LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// gets the latest payment for a reg_userid
// returns NULL if not found
function cc_paymentdb_get_previous_payment_reg_userid( $reg_userid){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name WHERE reg_userid = $reg_userid AND status != 'Cancelled' ORDER BY id DESC LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}


// Helper function to update duplicate checking to use tokens
function cc_paymentdb_dejavu_token( $user_id, $training_id, $token ){
    global $wpdb;
    
    // Check for existing payments with same user, training, and within reasonable time frame
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ccpa_payments 
         WHERE reg_userid = %d 
         AND workshop_id = %d 
         AND last_update > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
        $user_id, $training_id
    ));
    
    return $existing;
}


// are we about to process a duplicate payment?
// a duplicate is a payment in the last 5 mins with the same user, training id, attendees and IP
// returns the ID of the duplicate if found
function cc_paymentdb_dejavu($user_id, $training_id, $ccreg){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$five_mins_ago = date('Y-m-d H:i:s', strtotime('-5 mins'));
	$ip = $_SERVER['REMOTE_ADDR'];
	$sql = "SELECT id FROM $table_name WHERE reg_userid = $user_id AND workshop_id = $training_id AND ip_address = '$ip' AND last_update > '$five_mins_ago' LIMIT 1";
	$duplicate_id = $wpdb->get_var($sql);
	if($duplicate_id !== NULL){
		// now check attendees
		$prev_attendees = cc_attendees_for_payment($duplicate_id);
		$curr_attendees = cc_attendees_reg_get($ccreg);
		if(count($prev_attendees) <> count($curr_attendees)){
			return false;
		}
		$prev_emails = array();
		foreach ($prev_attendees as $prev_attendee) {
			$user = get_user_by('ID', $prev_attendee['user_id']);
			$prev_emails[] = $user->user_email;
		}
		$count_found = 0;
		foreach ($curr_attendees as $curr_attendee) {
			if($curr_attendee['registrant'] == 'r'){
				$user = get_user_by('ID', $curr_attendee['user_id']);
				if( in_array( $user->user_email, $prev_emails ) ){
					$count_found ++;
				}
			}else{
				if( in_array( $curr_attendee['email'], $prev_emails ) ){
					$count_found ++;
				}
			}
		}
		if($count_found == count($prev_attendees)){
			cc_emails_duplicate_notification($duplicate_id);
			return $duplicate_id;
		}
	}
	return false;
}

// gets all payment records in a date range
// date formats 'Y-m-d H:i:s'
// only gets "real" payments (workshops, recordings or series parents)
// Only include rows where type is 'recording', 'series', or ''.
// Exclude rows where disc_code = 'series' unless type = 'series'.
function cc_paymentdb_get_date_range($start, $end){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = $wpdb->prepare(
	    "SELECT * FROM $table_name 
	     WHERE last_update >= %s 
	       AND last_update <= %s 
	       AND type IN ('recording', 'series', '') 
	       AND (disc_code != 'series' OR type = 'series')",
	    $start,
	    $end
	);
	return $wpdb->get_results($sql, ARRAY_A);
}