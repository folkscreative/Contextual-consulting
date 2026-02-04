<?php
/***
 * functions for the recorded workshops
 */

// can a user view this recording?
function ccrecw_user_can_view($recording_id, $user_id=0){
	$response = array(
		'access' => false,
		'expiry_date' => '',
	);
	if($recording_id > 0){
		$post_type = get_post_type( $recording_id );
		$course_status = 'ignore';
		if( $post_type == 'recording' ){
			$course_status = get_post_meta($recording_id, 'recording_for_sale', true);
		}elseif( $post_type == 'course' ){
			$course_type = get_post_meta( $recording_id, '_course_type', true );
			if( $course_type == 'on-demand' ){
				$course_status = get_post_meta( $recording_id, '_course_status', true );
			}
		}
		if( $course_status == 'ignore' ){
			// not a recording, do nothing, no access allowed
		}elseif( $course_status == 'public' ){
			// everyone can view it
        	$response['access'] = true;
		}else{
			if( $user_id > 0 ){
				$portal_user = get_user_meta( $user_id, 'portal_user', true );
				if( !empty($portal_user) ){
					// Check organisation contract status
					$org_reg_status = org_register_status( $portal_user, $recording_id );
					
					// Get the organisation's contract details
					$contract = get_organisation_contract($portal_user);
					$contract_type = !empty($contract->contract_type) ? $contract->contract_type : 'unlimited';
					
					// For UNLIMITED contracts: block access if expired
					// For FIXED_NUMBER contracts: allow access to already-registered content even if expired
					if( $org_reg_status['status'] == 'expired' && $contract_type == 'unlimited' ){
						$response['access'] = false;
						return $response;
					}
					
					// For FIXED_NUMBER contracts with expired status, we continue to check
					// individual access times below - if they registered before expiry, they retain access
				}
			}
			date_default_timezone_set('Europe/London');
			if($user_id == 0){
				$user_id = get_current_user_id();
			}
			if($user_id > 0){
				$recording_meta = get_recording_meta( $user_id, $recording_id );
				$now = date('Y-m-d H:i:s');
				// access time is always set
				if(isset($recording_meta['access_time']) && $recording_meta['access_time'] < $now){
					if(!isset($recording_meta['closed_time']) || $recording_meta['closed_time'] == ''){
						// unlimited access
						$response['access'] = true;
					}else{
						$response['expiry_date'] = date('jS M Y', strtotime( $recording_meta['closed_time'] ) );
						if($recording_meta['closed_time'] > $now){
							$response['access'] = true;
						}
					}
				}
			}
		}
	}
	return $response;
}

// options for the free recordings page - recordings ... also used in other places!
// now uses course CPTs instead of recordings
function ccrecw_free_rec_options($recording_id=''){
    $args = array(
    	'post_type' => 'course',
    	'posts_per_page' => -1,
    	'meta_query' => array(
			array(
				'key'   => '_course_type',
				'value' => 'on-demand',
			)
		)
    );
    $recordings = get_posts($args);
    $html = '';
    foreach ($recordings as $recording) {
    	$html .= '<option value="'.$recording->ID.'" '.selected($recording->ID, $recording_id, true).'>'.$recording->ID.': '.$recording->post_title.'</option>';
    }
    return $html;
}

// options for the free recordings page - subscriber lists
function ccrecw_free_rec_mailster_lists($mailster_list_id=''){
    // get all mailster lists
    $lists = mailster( 'lists' )->get();
    $html = '';
    foreach ($lists as $list) {
    	$html .= '<option value="'.$list->ID.'" '.selected($list->ID, $mailster_list_id, true).'>'.$list->name.'</option>';
    }
    return $html;
}

// new subscriber email filter
add_filter( 'wp_new_user_notification_email', 'ccrecw_new_user_notification_email_callback', 10, 3 );
function ccrecw_new_user_notification_email_callback( $email, $user, $blogname ) {
	$recording_title = '';
	$recording_id = get_user_meta($user->ID, 'new_user_recording_id', true);
	if($recording_id > 0)	{
		$recording_title = get_the_title($recording_id);
	}
	$email['subject'] = str_replace('[recording]', $recording_title, get_option('free_recording_email_msg_subject'));
	$frem = get_option('free_recording_email_msg');
	$frem = str_replace('[firstname]', '{firstname}', $frem);
	$frem = str_replace('[recording]', $recording_title, $frem);
	$frem = str_replace('[recording_access_expiry]', rpm_cc_recordings_expiry_pretty_expiry_datetime($user->ID, $recording_id), $frem);
	$email['message'] = $frem."\r\n\r\n";
    $email['message'] .= 'To set your password, visit the following address:'."\r\n\r\n";
    $key = get_password_reset_key( $user );
    // $email['message'] .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . ">\r\n\r\n";
    $email['message'] .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_email ), 'login' ) . ">\r\n\r\n";
    $email['message'] .= 'Kind regards,'."\r\n".'The Contextual Consulting Team'."\r\n\r\n";
    return $email;
}

// add recording access to a user
// now (v1.0.3.36x) returns payment_id
function ccrecw_add_recording_to_user($user_id, $recording_id, $raw_access_type='free', $amount=0, $token='', $currency='GBP', $vat_included=0, $payment_id=NULL){
	// ccpa_write_log('ccrecw_add_recording_to_user');
	// ccpa_write_log('user_id: '.$user_id.' recording_id: '.$recording_id.' raw_access_type: '.$raw_access_type.' amount: '.$amount.' token: '.$token.' currency: '.$currency.' vat_included: '.$vat_included.' payment_id: '.$payment_id);
	global $wpdb;
	$recordings_users_table = $wpdb->prefix.'recordings_users';
	if($raw_access_type == 'invoice'){
		// we treat invoice payers as if they had paid
		$access_type = 'paid';
	}else{
		$access_type = $raw_access_type;
	}
	$recording_access = ccrecw_user_can_view($recording_id, $user_id);
	// ccpa_write_log('recording_access:');
	// ccpa_write_log($recording_access);
	if($recording_access['access']){
		// they already have access
		return false;
	}
	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
	$recording_meta = get_recording_meta( $user_id, $recording_id );
	// ccpa_write_log('recording_meta:');
	// ccpa_write_log($recording_meta);
	$now = date('Y-m-d H:i:s');
	$recording_expiry = rpm_cc_recordings_get_expiry($recording_id, true);
	if($recording_expiry['string_to_time'] == ''){
		$closing_time = '';
		$closing_type = '';
	}else{
		// v1.7.0 Mar 2021
		// $closing_time = date('Y-m-d H:i:s', strtotime($recording_expiry['string_to_time']));
		$closing_time = date('Y-m-d', strtotime($recording_expiry['string_to_time'])).' 23:59:59';
		$closing_type = 'auto';
	}
	if($recording_meta <> ''){
		// they had access previously
		// ccpa_write_log('recording_meta not empty');
		if(isset($recording_meta['closed_time']) && $recording_meta['closed_time'] <> '' && $recording_meta['closed_time'] > $now){
			// leave it set as it was
		}else{
			$recording_meta['closed_time'] = $closing_time;
			$recording_meta['closed_type'] = $closing_type;
		}
		if(isset($recording_meta['access_time']) && $recording_meta['access_time'] <> '' && $recording_meta['access_time'] < $now){
			// leave it as it was
		}else{
			$recording_meta['access_time'] = $now;
		}
		$recording_meta['access_type'] = $access_type;
		$recording_meta['currency'] = $currency;
		$recording_meta['amount'] = $amount;
		$recording_meta['token'] = $token;
		if($payment_id === NULL){
			$notes = 'Reinstated access to on-demand training';
			$payment_id = ccrecw_add_rec_pmt_to_pmts_table($user_id, $recording_id, $recording_meta, $vat_included, $raw_access_type, $notes);
		}
		$recording_meta['payment_id'] = $payment_id;
		// ccpa_write_log('recording_meta:');
		// ccpa_write_log($recording_meta);
		update_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta);
	    // and to really give them accesss, they need to be in the attendees table too 
	    $attendee = array(
	    	'payment_id' => $payment_id,
	    	'user_id' => $user_id,
	    	'registrant' => '',
	    );
	    cc_attendee_add( $attendee ); // does nothing if already set
	    return $payment_id;
	}else{
		// new access
		// ccpa_write_log('recording_meta empty');
		// first_viewed added May 2022
		$recording_meta = array(
			'access_time' => $now,					// when given access to the recording
			'access_type' => $access_type,			// paid or free access
			'num_views' => 0,						// how many times have they watched the recording
			'first_viewed' => '',					// date/time when they first watched it (d/m/y H:i:s)
			'last_viewed' => '',					// date/time when they last watched it (d/m/y H:i:s)
			'last_viewed_time' => 0,				// num seconds they viewed it for
			'viewed_end' => 'no',					// have they got to the end
			'viewing_time' => 0,					// total seconds viewing time
			'closed_time' => $closing_time,			// when their access was revoked/expired
			'closed_type' => $closing_type,			// 'auto' or 'manual' or '' if not closed
			'currency' => $currency,
			'amount' => $amount,					// amount paid 
			'token' => $token,						// stripe token
		);
		if($payment_id === NULL){
			$notes = 'Granted access to on-demand training';
			$payment_id = ccrecw_add_rec_pmt_to_pmts_table($user_id, $recording_id, $recording_meta, $vat_included, $raw_access_type, $notes);
		}
		$recording_meta['payment_id'] = $payment_id;
		// ccpa_write_log('recording_meta:');
		// ccpa_write_log($recording_meta);
		add_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta);
		// now add to the recordings users table
	    // is it already in the table?
	    $sql = "SELECT * FROM $recordings_users_table WHERE user_id = $user_id AND recording_id = $recording_id LIMIT 1";
	    $result = $wpdb->get_row($sql, ARRAY_A); // Returns null if no result is found
	    if($result === NULL){
	        // not found, add it ....
	        $data = array(
	            'user_id' => $user_id,
	            'recording_id' => $recording_id,
	            'payment_id' => $payment_id,
	        );
	        $wpdb->insert($recordings_users_table, $data, array('%d', '%d', '%d'));
	    }
	    // and to really give them accesss, they need to be in the attendees table too
	    $attendee = array(
	    	'payment_id' => $payment_id,
	    	'user_id' => $user_id,
	    	'registrant' => '',
	    );
	    cc_attendee_add( $attendee );
	}
	// maybe this purchase triggers a voucher to be issued?
	// ccpa_vouchers_alloc_maybe( cc_paymentdb_get_payment( $payment_id ) );
    return $payment_id;
}

// switch recording access from one recording to another
// this is the user meta field
// used by the edit payment function
function ccrecw_switch_user_recording($payment_id, $user_id, $old_recording_id, $new_recording_id){
	if( $old_recording_id == $new_recording_id ) return;
	$date_utc = new DateTime("now", new DateTimeZone("UTC"));
	$now = $date_utc->format('Y-m-d H:i:s');
	$access_type = 'free';
	$currency = 'GBP';
	$amount = 0;
	$token = '';
	// do not remove the user meta field for the old recording but set it so that access is closed from now
	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$old_recording_id, true);
	$recording_meta = get_recording_meta( $user_id, $old_recording_id );
	if($recording_meta <> ''){
		// should be!
		$access_type = $recording_meta['access_type'];
		$currency = $recording_meta['currency'];
		$amount = $recording_meta['amount'];
		$token = $recording_meta['token'];
		// if already closed then nothing to do
		if( ! isset( $recording_meta['closed_time'] ) || $recording_meta['closed_time'] == '' || $recording_meta['closed_time'] > $now ){
			// still open ... let's close it
			$recording_meta['closed_type'] = 'manual';
			$recording_meta['closed_time'] = $now;
			update_user_meta($user_id, 'cc_rec_wshop_'.$old_recording_id, $recording_meta);
		}
	}
	// does user meta data exist for the new recording?
	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$new_recording_id, true);
	$recording_meta = get_recording_meta( $user_id, $new_recording_id );
	$recording_expiry = rpm_cc_recordings_get_expiry($new_recording_id, true);
	if($recording_expiry['string_to_time'] == ''){
		$closing_time = '';
		$closing_type = '';
	}else{
		$closing_time = date('Y-m-d', strtotime($recording_expiry['string_to_time'])).' 23:59:59';
		$closing_type = 'auto';
	}
	if($recording_meta == ''){
		// no previous access .... grant it here
		$recording_meta = array(
			'access_time' => $now,					// when given access to the recording
			'access_type' => $access_type,			// paid or free access
			'num_views' => 0,						// how many times have they watched the recording
			'first_viewed' => '',					// date/time when they first watched it (d/m/y h:i:s)
			'last_viewed' => '',					// date/time when they last watched it (d/m/y h:i:s)
			'last_viewed_time' => 0,				// num seconds they viewed it for
			'viewed_end' => 'no',					// have they got to the end
			'viewing_time' => 0,					// total seconds viewing time
			'closed_time' => $closing_time,			// when their access was revoked/expired
			'closed_type' => $closing_type,			// 'auto' or 'manual' or '' if not closed
			'currency' => $currency,
			'amount' => $amount,					// amount paid 
			'token' => $token,						// stripe token
			'payment_id' => $payment_id,
		);
		add_user_meta($user_id, 'cc_rec_wshop_'.$new_recording_id, $recording_meta);
	}else{
		// previous access ... extend it if necessary
		if(isset($recording_meta['closed_time']) && $recording_meta['closed_time'] <> '' && $recording_meta['closed_time'] < $now){
			$recording_meta['closed_time'] = $closing_time;
			$recording_meta['closed_type'] = $closing_type;
		}
		if(isset($recording_meta['access_time']) && $recording_meta['access_time'] <> '' && $recording_meta['access_time'] > $now){
			$recording_meta['access_time'] = $now;
		}
		$recording_meta['access_type'] = $access_type;
		$recording_meta['currency'] = $currency;
		$recording_meta['amount'] = $amount;
		$recording_meta['token'] = $token;
		$recording_meta['payment_id'] = $payment_id;
		update_user_meta($user_id, 'cc_rec_wshop_'.$new_recording_id, $recording_meta);
	}
}

// Add a recording payment to the payment table
// Mar 2025 now also generating payments where no payment taken!
function ccrecw_add_rec_pmt_to_pmts_table($user_id, $recording_id, $recording_meta, $vat_included, $raw_access_type, $notes=''){
	// ccpa_write_log('function ccrecw_add_rec_pmt_to_pmts_table');
	// ccpa_write_log('user_ud: '.$user_id.' recording_id: '.$recording_id.' vat_included: '.$vat_included.' raw_access_type: '.$raw_access_type.' recording_meta:');
	// ccpa_write_log($recording_meta);
	$payment_data = cc_paymentdb_empty_payment();
	$user = get_user_by( 'ID', $user_id );

	$payment_data['reg_userid'] = $user_id;
	$payment_data['firstname'] = $user->first_name;
	$payment_data['lastname'] = $user->last_name;
	$payment_data['email'] = $user->user_email;
	$payment_data['phone'] = get_user_meta($user_id, 'phone', true);
	$payment_data['workshop_id'] = $recording_id;
	if( $notes == 'Granted access to on-demand training' || $notes == 'Reinstated access to on-demand training' ){
		$payment_data['type'] = 'auto';
	}else{
		$payment_data['type'] = 'recording';
	}
	$payment_data['notes'] = $notes;

	if($recording_meta['access_type'] == 'paid'){
		if($vat_included == 0){
			$vat_exempt = 'y';
		}else{
			$vat_exempt = 'n';
		}
		$stripe_id = get_user_meta($user_id, 'clientSecret', true);
		if(strpos($stripe_id, '_secret')){
			$stripe_id = substr($stripe_id, 0, strpos($stripe_id, '_secret'));
		}
		if($raw_access_type == 'invoice'){
			$status = 'Invoice requested';
		}else{
			$status = 'Payment successful: ';
		}
		$payment_data['token'] = $stripe_id;
		$payment_data['status'] = $status;
		$payment_data['payment_amount'] = $recording_meta['amount'];
		$payment_data['vat_included'] = $vat_included;
		$payment_data['vat_exempt'] = $vat_exempt;
		$payment_data['mailing_list'] = get_user_meta($user_id, 'maillist', true);
		$payment_data['disc_code'] = get_user_meta($user_id, 'voucher', true);
		$payment_data['disc_amount'] = get_user_meta($user_id, 'discAmount', true);
		$payment_data['pmt_method'] = get_user_meta($user_id, 'pmtmethod', true);
		$payment_data['inv_address'] = get_user_meta($user_id, 'invaddr', true);
		$payment_data['inv_email'] = get_user_meta($user_id, 'invemail', true);
		$payment_data['inv_phone'] = get_user_meta($user_id, 'invphone', true);
		$payment_data['inv_ref'] = get_user_meta($user_id, 'invref', true);
		$payment_data['currency'] = $recording_meta['currency'];
		$payment_data['vat_uk'] = get_user_meta($user_id, 'vat_uk', true);
		$payment_data['vat_employ'] = get_user_meta($user_id, 'vat_employ', true);
		$payment_data['vat_employer'] = get_user_meta($user_id, 'vat_employer', true);
		$payment_data['vat_website'] = get_user_meta($user_id, 'vat_website', true);
	}else{
		$payment_data['status'] = 'Payment not needed';
		$payment_data['currency'] = 'GBP';
		$payment_data['pmt_method'] = 'free';
	}
	return cc_paymentdb_update_payment($payment_data);
	// return 0; // payment not put into table
}

// return a pretty field from the recording meta
function ccrecw_pretty_meta($recording_meta, $field){
	if(!isset($recording_meta[$field])) return '';
	switch ($field) {
		case 'access_time':
		case 'first_viewed':
		case 'last_viewed':
		case 'closed_time':
			if($recording_meta[$field] == ''){
				if($field == 'closed_time' && isset($recording_meta['closed_type']) && $recording_meta['closed_type'] == 'manual'){
					return 'Unlimited (M)';
				}else{
					return '';
				}
			}else{
				if( $field == 'access_time' || $field == 'closed_time' ){
					$text = date('d/m/Y H:i:s', strtotime($recording_meta[$field]));
				}else{
					$text = $recording_meta[$field];
				}
				if($field == 'closed_time' && isset($recording_meta['closed_type']) && $recording_meta['closed_type'] == 'manual'){
					$text .= ' (M)';
				}
				return $text;
			}
			break;
		case 'access_type':
			if($recording_meta[$field] == 'paid'){
				$curr_code = '&pound;';
				if(isset($recording_meta['currency'])){
					if($recording_meta['currency'] == 'AUD'){
						$curr_code = 'AU$';
					}elseif($recording_meta['currency'] == 'USD'){
						$curr_code = 'US$';
					}
				}
				return ucfirst($recording_meta[$field]).': '.$curr_code.number_format($recording_meta['amount'], 2, '.', '');
			}else{
				return ucfirst($recording_meta[$field]);
			}
			break;
		case 'viewed_end':
			return ucfirst($recording_meta[$field]);
			break;
		case 'last_viewed_time':
		case 'viewing_time':
		case 'last_playhead':
			// $hours = (int) floor($recording_meta[$field] / 3600);
			// $minutes = (int) floor(($recording_meta[$field] / 60) % 60);
			// to stop the deprecation warnings ....
			$total_seconds = $recording_meta[$field];
			$hours = intdiv( $total_seconds, 3600 );
			$minutes = intdiv( $total_seconds % 3600, 60 );
			$seconds = $total_seconds % 60;
			return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
			break;
		case 'stripe':
			$stripe = $recording_meta['token'];
			$charge_id = $recording_meta['charge_id'];
			if($stripe <> '' && $charge_id <> '') $stripe .= '<br>';
			$stripe .= $charge_id;
			return $stripe;
			break;			
		default:
			return $recording_meta[$field];
			break;
	}
}

// saves updates to the video stats
// this is an improved version of the function that follows (ccrecw_save_video_stats)
add_action('wp_ajax_save_video_stats_update', 'ccrecw_save_video_stats_update');
add_action('wp_ajax_nopriv_save_video_stats_update', 'ccrecw_save_video_stats_update');
function ccrecw_save_video_stats_update(){
	$response = array(
		'status' => '',
	);
	// $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$video_id = isset( $_POST['video_id'] ) ? sanitize_text_field( $_POST['video_id'] ) : '';
	list( $recording_id, $module_num ) = explode( "-", $video_id, 2 ); // $module_num becomes null if no hyphen
	$user_id = get_current_user_id();

	// module num 9999 (or NULL) for the main recording
	if( $module_num == 9999 ){
		$module_num = NULL;
	}

	$recording_meta = get_recording_meta( $user_id, $recording_id, $module_num);
	$recording_meta_main = get_recording_meta( $user_id, $recording_id);

	$now = date( 'd/m/Y H:i:s' );

	if( $recording_meta == '' && $module_num !== NULL ){
		// we'll set the basics up from the main recording module if we can
		if( $recording_meta_main <> '' ){
			$recording_meta = array(
				'access_time' => $recording_meta_main['access_time'], // d/m/Y H:i:s
				'access_type' => $recording_meta_main['access_type'], // free
				'num_views' => 0,
				'first_viewed' => $now, // d/m/Y H:i:s
				'last_viewed' => $now, // d/m/Y H:i:s
				'last_viewed_time' => 0,
				'viewed_end' => 'no', // no
				'viewing_time' => 0,
				'closed_time' => $recording_meta_main['closed_time'], // d/m/Y H:i:s
				'closed_type' => $recording_meta_main['closed_type'], // auto
				'currency' => $recording_meta_main['currency'], // GBP
				'amount' => $recording_meta_main['amount'],
				'token' => $recording_meta_main['token'],
				'payment_id' => $recording_meta_main['payment_id'],
			);
		}
	}

	// now apply the update
	$field = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : 'error';
	$value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : 'error';

	if( $recording_meta <> '' && $field <> 'error' && $value <> 'error' ){
		switch ($field) {
			case 'numviews':
				$value = absint( $value );
				if( $value > 0 ){
					$recording_meta['num_views'] = $value;
					$recording_meta['last_viewed'] = $now;
					if( ! isset( $recording_meta['first_viewed'] ) || $recording_meta['first_viewed'] == '' ){
						$recording_meta['first_viewed'] = $now; // d/m/y H:i:s
					}
				}
				break;

			case 'viewedend':
				if( $value == 'yes' ){
					$recording_meta['viewed_end'] = 'yes';
				}
				break;

			case 'lastviewedtime':
				$value = absint( $value );
				if( $value > 0 ){
					$recording_meta['last_viewed_time'] = $value;
					if( ! isset( $recording_meta['viewing_time'] ) || $recording_meta['viewing_time'] < $value ){
						$recording_meta['viewing_time'] = $value;
					}
				}
				break;
		}

		// also update the main recording meta if necessary
		if( $module_num !== NULL && is_array( $recording_meta_main ) ){
			$recording_meta_main['last_viewed'] = $now;
			if( ! isset( $recording_meta_main['first_viewed'] ) || $recording_meta_main['first_viewed'] == '' ){
				$recording_meta_main['first_viewed'] = $now;
			}
			update_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta_main );
			update_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id.'_'.$module_num, $recording_meta );
		}else{
			update_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta );
		}

		/*
		if( $module_num == 9999 ){
			update_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta );
		}else{
			update_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id.'_'.$module_num, $recording_meta );
		}
		*/
		$response['status'] = 'ok';
	}else{
		$response['status'] = 'error';
	}

	// potentially add the user to a newsletter list
	if( $field <> 'error' && $value <> 'error' ){
		courses_maybe_newsletter_sub( $user_id, $recording_id, $module_num );
	}

   	echo json_encode($response);
	die();
}

// get the recording meta
// previously we did not attempt to store data by section, now we do
function get_recording_meta( $user_id, $recording_id, $section_num=9999 ){
	if( $section_num == 9999 || $section_num === NULL ){
		$meta_key = 'cc_rec_wshop_'.$recording_id;
	}else{
		$meta_key = 'cc_rec_wshop_'.$recording_id.'_'.$section_num;
	}
	$recording_meta = get_user_meta( $user_id, $meta_key, true);
	if( is_array( $recording_meta ) ){
		// repair first_viewed and last_viewed if needed
		if( $section_num == 9999 || $section_num === NULL ){
			// reformat and populate the datetimes
			return sanitise_recording_meta( $recording_meta, $user_id, $recording_id );
		}else{
			// reformat only
			return sanitise_recording_meta( $recording_meta );
		}
	}
	// not found
	return '';
}

// convert incorrect date formats and populate missing dates from module data
function sanitise_recording_meta($recording_meta, $user_id = null, $training_id = null) {
    // Fields to process
    $dateFields = ['first_viewed', 'last_viewed'];
    
    // First, handle date format conversion (existing functionality)
    foreach ($dateFields as $field) {
        // Skip if field doesn't exist or is empty
        if (!isset($recording_meta[$field]) || empty($recording_meta[$field])) {
            continue;
        }
        
        $dateValue = trim($recording_meta[$field]);
        
        // Skip if still empty after trimming
        if (empty($dateValue)) {
            continue;
        }
        
        // Check if it's in the incorrect format (yyyy-mm-dd hh:mm:ss)
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateValue)) {
            // Convert from yyyy-mm-dd hh:mm:ss to dd/mm/yyyy hh:mm:ss
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateValue);
            
            if ($dateTime !== false) {
                $recording_meta[$field] = $dateTime->format('d/m/Y H:i:s');
            }
        }
        // If it's already in the correct format (dd/mm/yyyy hh:mm:ss), leave it as is
        // The regex check ensures we only convert valid formats
        elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', $dateValue)) {
            // If it's neither format, you might want to handle this case
            // For now, we'll leave it unchanged, but you could add error handling
            ccpa_write_log("Warning: function sanitise_recording_meta found unrecognized date format for {$field}: {$dateValue}");
        }
    }

    if ($user_id && $training_id) {
        $needs_update = false;
        $played = false;

	    if( $recording_meta['num_views'] == 0 && $recording_meta['first_viewed'] <> '' ){
	    	$recording_meta['num_views'] = 1;
	    }
        
        $first_viewed_missing = !isset($recording_meta['first_viewed']) || empty(trim($recording_meta['first_viewed']));
        $last_viewed_missing = !isset($recording_meta['last_viewed']) || empty(trim($recording_meta['last_viewed']));
        
        if ( $first_viewed_missing || $last_viewed_missing || $recording_meta['num_views'] == 0 ) {
            global $wpdb;
            
            $meta_key_pattern = 'cc_rec_wshop_' . $training_id . '_%';
            
            $module_metas = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM {$wpdb->usermeta} 
                     WHERE user_id = %d AND meta_key LIKE %s",
                    $user_id,
                    $meta_key_pattern
                )
            );
            
            $first_viewed_dates = array();
            $last_viewed_dates = array();
            
            // Process each module meta
            foreach ($module_metas as $meta) {
                // Skip if this is the main training meta (without _yyyy suffix)
                if ($meta->meta_key === 'cc_rec_wshop_' . $training_id) {
                    continue;
                }
                
                // Unserialize the meta value
                $module_data = maybe_unserialize($meta->meta_value);
                
                if (is_array($module_data)) {
                    // Check for first_viewed
                    if (isset($module_data['first_viewed']) && !empty(trim($module_data['first_viewed']))) {
                        $first_viewed_dates[] = trim($module_data['first_viewed']);
                    }
                    
                    // Check for last_viewed
                    if (isset($module_data['last_viewed']) && !empty(trim($module_data['last_viewed']))) {
                        $last_viewed_dates[] = trim($module_data['last_viewed']);
                    }

                    if( isset( $module_data['last_playhead'] ) && $module_data['last_playhead'] <> '' ){
                    	$played = true;
                    }
                }
            }
            
            $earliest_first_viewed = null;
            $latest_last_viewed = null;
            
            if (!empty($first_viewed_dates)) {
                $earliest_first_viewed = $first_viewed_dates[0];
                foreach ($first_viewed_dates as $date) {
                    if (strtotime($date) < strtotime($earliest_first_viewed)) {
                        $earliest_first_viewed = $date;
                    }
                }
            }
            
            if (!empty($last_viewed_dates)) {
                $latest_last_viewed = $last_viewed_dates[0];
                foreach ($last_viewed_dates as $date) {
                    if (strtotime($date) > strtotime($latest_last_viewed)) {
                        $latest_last_viewed = $date;
                    }
                }
            }
            
            // Handle the case where only one date type is found
            if ($earliest_first_viewed && !$latest_last_viewed) {
                $latest_last_viewed = $earliest_first_viewed;
            } elseif ($latest_last_viewed && !$earliest_first_viewed) {
                $earliest_first_viewed = $latest_last_viewed;
            }
            
            // Update the recording_meta array and set update flag
            if ($first_viewed_missing && $earliest_first_viewed) {
                $recording_meta['first_viewed'] = $earliest_first_viewed;
                $played = true;
                $needs_update = true;
            }
            
            if ($last_viewed_missing && $latest_last_viewed) {
                $recording_meta['last_viewed'] = $latest_last_viewed;
                $played = true;
                $needs_update = true;
            }
            
            if( $recording_meta['num_views'] == 0 && $played ){
            	$recording_meta['num_views'] = 1;
                $needs_update = true;
            }

            // Update the database if changes were made
            if ($needs_update) {
                $main_meta_key = 'cc_rec_wshop_' . $training_id;
                update_user_meta($user_id, $main_meta_key, $recording_meta);
            }
        }
    }
    
    return $recording_meta;
}


/*
// convert incorrect date formats (old version)
function sanitise_recording_meta( $recording_meta ){
    // Fields to process
    $dateFields = ['first_viewed', 'last_viewed'];
    
    foreach ($dateFields as $field) {
        // Skip if field doesn't exist or is empty
        if (!isset($recording_meta[$field]) || empty($recording_meta[$field])) {
            continue;
        }
        
        $dateValue = trim($recording_meta[$field]);
        
        // Skip if still empty after trimming
        if (empty($dateValue)) {
            continue;
        }
        
        // Check if it's in the incorrect format (yyyy-mm-dd hh:mm:ss)
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateValue)) {
            // Convert from yyyy-mm-dd hh:mm:ss to dd/mm/yyyy hh:mm:ss
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $dateValue);
            
            if ($dateTime !== false) {
                $recording_meta[$field] = $dateTime->format('d/m/Y H:i:s');
            }
        }
        // If it's already in the correct format (dd/mm/yyyy hh:mm:ss), leave it as is
        // The regex check ensures we only convert valid formats
        elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', $dateValue)) {
            // If it's neither format, you might want to handle this case
            // For now, we'll leave it unchanged, but you could add error handling
            ccpa_write_log("Warning: function sanitise_recording_meta found unrecognized date format for {$field}: {$dateValue}");
        }
    }
    
    return $recording_meta;
}
*/

// save the stats from the watch recording page
add_action('wp_ajax_save_video_stats', 'ccrecw_save_video_stats');
add_action('wp_ajax_nopriv_save_video_stats', 'ccrecw_save_video_stats');
function ccrecw_save_video_stats(){
	$response = array(
		'status' => '',
	);
	if(cc_users_is_valid_user_logged_in()){
		$current_user = wp_get_current_user();
		$recording_id = 0;
		if(isset($_POST['recid'])){
			$recording_id = absint($_POST['recid']);
			if($recording_id > 0){
				// $recording_meta = get_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, true);
				$recording_meta = get_recording_meta( $current_user->ID, $recording_id );
				if($recording_meta <> ''){
					$data_ok = true;
					if(!isset($_POST['numviews']) || !is_array($_POST['numviews'])){
						$response['status'] .= 'numviews invalid';
						$data_ok = false;
					}else{
					}
					if(!isset($_POST['lastviewed']) || !is_array($_POST['lastviewed'])){
						$response['status'] .= 'lastviewed invalid';
						$data_ok = false;
					}else{
					}
					if(!isset($_POST['lastviewedtime']) || !is_array($_POST['lastviewedtime'])){
						$response['status'] .= 'lastviewedtime invalid';
						$data_ok = false;
					}else{
					}
					if(!isset($_POST['viewedend']) || !is_array($_POST['viewedend']) ){
						$response['status'] .= 'viewedend invalid';
						$data_ok = false;
					}else{
					}
					if(!isset($_POST['viewingtime']) || !is_array($_POST['viewingtime'])){
						$response['status'] .= 'viewingtime invalid';
						$data_ok = false;
					}else{
					}
					if($data_ok){
						// main video
						// $recording_meta['num_views'] = absint($_POST['numviews'][0]);
						$last_viewed = stripslashes( sanitize_text_field( $_POST['lastviewed'][0] ) );
						if($last_viewed <> ''){
							if($recording_meta['last_viewed'] <> $last_viewed){
								$recording_meta['num_views'] ++;
								$recording_meta['last_viewed'] = $last_viewed;
								if(!isset($recording_meta['first_viewed']) || $recording_meta['first_viewed'] == '' ){
									$recording_meta['first_viewed'] = $last_viewed;
								}
							}
						}
						$last_viewed_time = absint($_POST['lastviewedtime'][0]);
						if($last_viewed_time > 0){
							$recording_meta['last_viewed_time'] = $last_viewed_time;
						}
						$viewed_end = stripslashes( sanitize_text_field( $_POST['viewedend'][0] ) );
						if($viewed_end <> ''){
							$recording_meta['viewed_end'] = $viewed_end;
						}
						$recording_meta['viewing_time'] = absint($_POST['viewingtime'][0]);
						// modules
						for ($i=0; $i < 10; $i++){
							$mod_num = $i + 1;
							/*
							if(isset($_POST['numviews'][$mod_num])){
								$recording_meta['modules'][$i]['num_views'] = absint($_POST['numviews'][$mod_num]);
							}
							*/
							if(isset($_POST['lastviewed'][$mod_num])){
								$last_viewed = stripslashes( sanitize_text_field( $_POST['lastviewed'][$mod_num] ) );
								if($last_viewed <> '' && $last_viewed <> $recording_meta['modules'][$i]['last_viewed']){
									$recording_meta['modules'][$i]['num_views'] ++;
									$recording_meta['modules'][$i]['last_viewed'] = $last_viewed;
									if(!isset($recording_meta['modules'][$i]['first_viewed']) || $recording_meta['modules'][$i]['first_viewed'] == '' ){
										$recording_meta['modules'][$i]['first_viewed'] = $last_viewed;
									}
								}
							}
							if(isset($_POST['lastviewedtime'][$mod_num])){
								$last_viewed_time = absint($_POST['lastviewedtime'][$mod_num]);
								if($last_viewed_time > 0){
									$recording_meta['modules'][$i]['last_viewed_time'] = $last_viewed_time;
								}
							}
							if(isset($_POST['viewedend'][$mod_num])){
								$viewed_end = stripslashes( sanitize_text_field( $_POST['viewedend'][$mod_num] ) );
								if($viewed_end <> ''){
									$recording_meta['modules'][$i]['viewed_end'] = $viewed_end;
								}
							}
							if(isset($_POST['viewingtime'][$mod_num])){
								$recording_meta['modules'][$i]['viewing_time'] = absint($_POST['viewingtime'][$mod_num]);
							}
						}
						update_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, $recording_meta);
						$response['status'] = 'saved';
					}else{
						$response['status'] .= 'not saved';
					}
				}else{
					$response['status'] = 'meta not found';
				}
			}else{
				$response['status'] = 'recording id invalid';
			}
		}else{
			$response['status'] = 'recording id missing';
		}
	}else{
		$response['status'] = 'user not logged in';
	}
   	echo json_encode($response);
	die();
}

// v1.0.3.40x.0 record the fact that somebody has clicked a link to one of Oli's old videos
add_action('wp_ajax_save_rec_click', 'ccrecw_save_rec_click');
add_action('wp_ajax_nopriv_save_rec_click', 'ccrecw_save_rec_click');
function ccrecw_save_rec_click(){
	$response = array(
		'status' => '',
	);
	if(cc_users_is_valid_user_logged_in()){
		$current_user = wp_get_current_user();
		$recording_id = 0;
		if(isset($_POST['recid'])){
			$recording_id = absint($_POST['recid']);
			if($recording_id > 0){
				// $recording_meta = get_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, true);
				$recording_meta = get_recording_meta( $current_user->ID, $recording_id );
				if($recording_meta <> ''){
					$recording_meta['num_views'] ++;
					if($recording_meta['first_viewed'] == ''){
						if($recording_meta['last_viewed'] == ''){
							$recording_meta['first_viewed'] = date('d/m/Y H:i:s');
						}else{
							$recording_meta['first_viewed'] = $recording_meta['last_viewed'];
						}
					}
					$recording_meta['last_viewed'] = date('d/m/Y H:i:s');
					update_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, $recording_meta);
					$response['status'] = 'saved';
				}else{
					$response['status'] = 'meta not found';
				}
			}else{
				$response['status'] = 'recording id invalid';
			}
		}else{
			$response['status'] = 'recording id missing';
		}
	}else{
		$response['status'] = 'user not logged in';
	}
   	echo json_encode($response);
	die();
}

// withdraw acces to a recording
function ccrecw_withdraw_access($user_id, $recording_id){
	$recording_access = ccrecw_user_can_view($recording_id, $user_id);
	if($recording_access['access']){
		// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
		$recording_meta = get_recording_meta( $user_id, $recording_id );
		$recording_meta['closed_time'] = date('Y-m-d H:i:s');
		$recording_meta['closed_type'] = 'manual';
		return update_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta);
	}
	return false;
}

// send extra recording email to user
function ccrecw_send_extra_rec_email($user_id, $recording_id){
	$user = get_user_by('ID', $user_id);
	if($user){
		$to = $user->user_email;
		$recording_title = get_the_title($recording_id);
		$subject = str_replace('[recording]', $recording_title, get_option('free_recording_reg_email_msg_subject'));
		$message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>'.$subject.'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0 " />
</head>
<body style="font-family: tahoma; font-size: 14px; color: #333333;">';
	$frrem = esc_attr(get_option('free_recording_reg_email_msg'));
	$frrem = str_replace('[recording]', $recording_title, $frrem);
	$frrem = str_replace('[recording_access_expiry]', rpm_cc_recordings_expiry_pretty_expiry_datetime($user_id, $recording_id), $frrem);
	$message .= nl2br($frrem);
	$message .= '</body>
</html>';
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Contextual Consulting <admin@contextualconsulting.co.uk>',
		);
		return wp_mail( $to, $subject, $message, $headers );
	}
	return false;
}

// the mailster version of the extra recording email
/*
function ccrecw_send_rec_pmt_email_mailster($user_id){
}
*/

// email lookup on recorded workshops payment page
add_action('wp_ajax_ccrecw_email_lookup', 'ccrecw_email_lookup');
add_action('wp_ajax_nopriv_ccrecw_email_lookup', 'ccrecw_email_lookup');
function ccrecw_email_lookup(){
	$response = array(
		'status' => 'unregistered',
	);
	$current_email = '';
	$user_id = 0;
	if(cc_users_is_valid_user_logged_in()){
		$current_user = wp_get_current_user();
		$current_email = $current_user->user_email;
	}
	if(isset($_POST['email']) && $_POST['email'] <> ''){
		$email = sanitize_email($_POST['email']);
		if($email == $current_email){
			$response['status'] = 'unchanged';
			$user_id = $current_user->ID;
		}else{
			$user = get_user_by('email', $email);
			if($user){
				$response['status'] = 'registered';
				$user_id = $user->ID;
			}
		}
	}
	if($user_id > 0){
		// user already set up
		// do they have access to this recording already?
		if(isset($_POST['recording']) && $_POST['recording'] <> ''){
			$recording_id = absint($_POST['recording']);
			$recording_access = ccrecw_user_can_view($recording_id, $user_id);
			if($recording_id > 0 && $recording_access['access']){
				$response['status'] = 'viewer';
			}
		}
	}
   	echo json_encode($response);
	die();
}

// handle a payment
add_action('wp_ajax_ccrecw_payment', 'ccrecw_payment');
add_action('wp_ajax_nopriv_ccrecw_payment', 'ccrecw_payment');
function ccrecw_payment(){
	ccpa_send_post_data_to_me();
	$ok_to_save = false;
	$clientSecret = '';
	if(isset($_POST['clientSecret']) && $_POST['clientSecret'] <> ''){
		$client_secret_set = true;
		$clientSecret = sanitize_text_field($_POST['clientSecret']);
		$ok_to_save = ccpa_payint_client_secret_status_update($clientSecret, 'new', 'locked'); // sets status to 'locked'
	}else{
		$client_secret_set = false;
		$ok_to_save = true;
	}
	$response = array(
		'user' => '',
		'msg' => '',
		'status' => 'error',
		'conversion' => '',
	);
	$user_id = 0;
	if(isset($_POST['userid']) && $_POST['userid'] <> ''){
		$user_id = (int) $_POST['userid'];
	}
	$recording_id = 0;
	if(isset($_POST['recording']) && $_POST['recording'] <> ''){
		$recording_id = (int) $_POST['recording'];
	}
	$email = '';
	if(isset($_POST['email']) && $_POST['email'] <> ''){
		$email = stripslashes( sanitize_email($_POST['email']) );
	}
	$title = '';
	if(isset($_POST['title']) && $_POST['title'] <> ''){
		$title = stripslashes( sanitize_text_field($_POST['title']) );
	}
	$firstname = '';
	if(isset($_POST['firstname']) && $_POST['firstname'] <> ''){
		$firstname = stripslashes( sanitize_text_field($_POST['firstname']) );
	}
	$lastname = '';
	if(isset($_POST['lastname']) && $_POST['lastname'] <> ''){
		$lastname = stripslashes( sanitize_text_field($_POST['lastname']) );
	}
	$phone = '';
	if(isset($_POST['phone']) && $_POST['phone'] <> ''){
		$phone = stripslashes( sanitize_text_field($_POST['phone']) );
	}
	$vat = '';
	if(isset($_POST['vat']) && $_POST['vat'] <> ''){
		$vat = stripslashes( sanitize_text_field($_POST['vat']) ); // 'y' = vat exempt
	}
	$vat_uk = '';
	if(isset($_POST['vatUK']) && $_POST['vatUK'] <> ''){
		$vat_uk = stripslashes( sanitize_text_field($_POST['vatUK']) );
	}
	$vat_employ = '';
	if(isset($_POST['vatEmploy']) && $_POST['vatEmploy'] <> ''){
		$vat_employ = stripslashes( sanitize_text_field($_POST['vatEmploy']) );
	}
	$vat_employer = '';
	if(isset($_POST['vatEmployer']) && $_POST['vatEmployer'] <> ''){
		$vat_employer = stripslashes( sanitize_text_field($_POST['vatEmployer']) );
	}
	$vat_website = '';
	if(isset($_POST['vatWebsite']) && $_POST['vatWebsite'] <> ''){
		$vat_website = stripslashes( filter_var($_POST['vatWebsite'], FILTER_VALIDATE_URL) );
	}
	$voucher = '';
	$discount_voucher = '';
	if(isset($_POST['voucher']) && $_POST['voucher'] <> ''){
		$voucher = stripslashes( sanitize_text_field($_POST['voucher']) );
		if($voucher <> ''){
			$discount = ccpa_discount_lookup($voucher);
			if($discount === NULL){
				$raw_voucher_code = ccpa_voucher_code_raw($voucher);
				$full_voucher = ccpa_voucher_table_get( $raw_voucher_code );
				if($full_voucher !== NULL){
					$discount_voucher = 'voucher';
					$voucher = $raw_voucher_code;
				}
			}else{
				$discount_voucher = 'discount';
			}
		}
	}else{
		// for the portal people ...
		if(cc_users_is_valid_user_logged_in()){
			$portal_user = get_user_meta(get_current_user_id(), 'portal_user', true);
			if($portal_user <> ''){
				$voucher = strtoupper( $portal_user );
			}
		}
	}
	$discAmount = 0;
	if(isset($_POST['discAmount']) && $_POST['discAmount'] <> ''){
		$discAmount = (float) $_POST['discAmount'];
	}
	$maillist = '';
	if(isset($_POST['maillist']) && $_POST['maillist'] <> ''){
		$maillist = stripslashes( sanitize_text_field($_POST['maillist']) );
	}
	$pmtmethod = '';
	if(isset($_POST['pmtmethod']) && $_POST['pmtmethod'] <> ''){
		$pmtmethod = stripslashes( sanitize_text_field($_POST['pmtmethod']) );
	}
	$invaddr = '';
	if(isset($_POST['invaddr']) && $_POST['invaddr'] <> ''){
		$invaddr = stripslashes( sanitize_text_field($_POST['invaddr']) );
	}
	$invemail = '';
	if(isset($_POST['invemail']) && $_POST['invemail'] <> ''){
		$invemail = stripslashes( sanitize_email($_POST['invemail']) );
	}
	$invphone = '';
	if(isset($_POST['invphone']) && $_POST['invphone'] <> ''){
		$invphone = stripslashes( sanitize_text_field($_POST['invphone']) );
	}
	$invref = '';
	if(isset($_POST['invref']) && $_POST['invref'] <> ''){
		$invref = stripslashes( sanitize_text_field($_POST['invref']) );
	}
	$currency = 'GBP';
	$curr_code = '&pound;';
	if(isset($_POST['currency']) && ($_POST['currency'] == 'AUD' || $_POST['currency'] == 'USD')){
		$currency = $_POST['currency'];
		if($currency == 'AUD'){
			$curr_code = '$AU';
		}else{
			$curr_code = '$US';
		}
	}
	$stripe_currency = strtolower($currency);
	$amount = 0;
	if(isset($_POST['amount']) && $_POST['amount'] <> ''){
		$amount = (float) $_POST['amount'];
	}
	$token = '';
	if(isset($_POST['token']) && $_POST['token'] <> ''){
		$token = stripslashes( sanitize_text_field($_POST['token']) );
	}
	$pmt_status = '';
	if($pmtmethod == 'online' || $pmtmethod == 'free'){
		$pmt_status = 'complete';
	}else{
		$pmt_status = 'invoice';
	}

	if($user_id > 0){
		$user = get_user_by('id', $user_id);
	}else{
		$user = get_user_by('email', $email);
	}
	if($user){
		$user_id = $user->ID;
		$new_user = false;
	}else{
		$username = $firstname.' '.$lastname.' '.uniqid();
		$user_id = wp_create_user( $username, wp_generate_password(), $email );
		$new_user = true;
		update_user_meta($user_id, 'last_login', 'never');
	}
	if($firstname <> '' || $lastname <> ''){
		$args = array(
			'ID' => $user_id,
			'first_name' => $firstname,
			'last_name' => $lastname,
		);
		wp_update_user($args);
	}

	$metas = array('title', 'phone', 'vat', 'vat_uk', 'vat_employ', 'vat_employer', 'vat_website', 'maillist', 'pmtmethod', 'invaddr', 'invemail', 'invphone', 'invref', 'amount', 'clientSecret', 'currency', 'voucher', 'discAmount');
	foreach ($metas as $meta) {
		update_user_meta($user_id, $meta, $$meta);
	}
	if($maillist == 'y'){
		ccrecw_subscribe_news($user_id);
	}
	$mailster_amount = $curr_code.sprintf("%0.2f", $amount);
	$vat_included = 0;
	// if($currency == 'GBP'){
		if($vat == 'y'){
			$mailster_amount .= ' (no VAT)';
		}else{
			$ccpa_vat_rate = (float) get_option('ccpa_vat_rate');
			$vat_included = round($amount - ($amount / (1 + ($ccpa_vat_rate * .01))), 2);
			$mailster_amount .= ' (incl. '.$curr_code.sprintf("%0.2f", $vat_included).' VAT)';
		}
	// }
	if($ok_to_save){
		if($client_secret_set){
			$still_ok_to_save = ccpa_payint_client_secret_status_update($clientSecret, 'locked', 'saving'); // sets status to saving
		}else{
			$still_ok_to_save = true;
		}
		if($still_ok_to_save){
			if($pmt_status == 'complete'){
				// online (or free) pmt
				if(!function_exists('mailster')){
					if($new_user){
						update_user_meta($user_id, 'new_user_recording_id', $recording_id);
						wp_new_user_notification( $user_id, null, 'user' );
						$response['user'] = 'added';
						$response['msg'] = 'Thanks! An email is on its way to you now so that you can set your password and login to access the recording<br><br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
						$response['status'] = 'ok';
					}else{
						ccrecw_send_extra_rec_email($user_id, $recording_id);
						$response['user'] = 'updated';
						$url = add_query_arg( 'id', $recording_id, '/watch-recording' );
						$response['msg'] = 'Thanks! You can access your recording now by <a href="'.$url.'">clicking here</a>';
						$response['status'] = 'ok';
					}
				}else{
					if($new_user){
						$user = get_user_by('id', $user_id);
						// #### NOTE #### DO NOT USE QUOTES IN THE MESSAGE OR YOUR MESSAGE WILL NOT BE SENT!!!!!!!!!
						// $set_password_msg = "<br><br>You can now login to the website to access the recording. You will need a username (your email address) and a password. To set a password, please click the link below:<br><br>";
						// $key = get_password_reset_key( $user );
						// $set_password_msg .= '<a href="'.network_site_url("wp-login.php?action=rp&key=$key&login=".rawurlencode( $user->user_email ), 'login' ).'">Set password</a>';
						// following did not works as $payment_data not set yet
						// $set_password_msg = cc_login_account_password_msg($payment_data);
						$response['user'] = 'added';
						$response['msg'] = 'Thanks! An email is on its way to you now so that you can set your password and login to access the recording<br><br><strong><i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> Please check your junk mail folder too.</strong>';
						$response['status'] = 'ok';
					}else{
						$response['user'] = 'updated';
						$url = add_query_arg( 'id', $recording_id, '/watch-recording' );
						$response['msg'] = 'Thanks! You can access your recording now by <a href="'.$url.'">clicking here</a>';
						$response['status'] = 'ok';
					}
					$set_password_msg = cc_login_account_password_msg_new($user->user_email, 'recording');
				}
				$mailster_hook = 'send_reg_recording_paid';
			}else{
				// invoice
				if($new_user){
					$user = get_user_by('id', $user_id);
					// #### NOTE #### DO NOT USE QUOTES IN THE MESSAGE OR YOUR MESSAGE WILL NOT BE SENT!!!!!!!!!
					// $set_password_msg = "<br><br>You can now login to the website. You will need a username (your email address) and a password. To set a password, please click the link below:<br><br>";
					// $key = get_password_reset_key( $user );
					// $set_password_msg .= '<a href="'.network_site_url("wp-login.php?action=rp&key=$key&login=".rawurlencode( $user->user_email ), 'login' ).'">Set password</a>';
				}else{
					//
				}
				$set_password_msg = cc_login_account_password_msg_new($user->user_email, 'recording');
				$mailster_hook = 'send_reg_recording_inv';
				$response['user'] = 'pending';
				$response['msg'] = 'Thanks! An email is on its way to you now so that you can set your password. We\'ll also send you an invoice so that you can access the recording.';
				$response['status'] = 'ok';
			}
			// for Google ads conversion tracking ...
			$response['conversion'] = $amount.'|'.$currency.'|'.$client_secret;

			// now find the list for this recording
			$lists = mailster( 'lists' )->get();
			$list_title = 'Recording: '.get_the_title($recording_id);
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

		    $subs_on_list = false;
			// maybe add the user to mailster
			$userdata = array(
	            'email' => $email,
	            'firstname' => $firstname,
	            'lastname' => $lastname,
	        );
			$subscriber = mailster('subscribers')->get_by_mail($email);
			if($subscriber){
				$subscriber_id = $subscriber->ID;
				if($maillist <> 'y'){
					// we need to update the subscriber with any name changes (unless we just did that for the newsletter)
					// if no change in data then result will be 0
					$result = mailster('subscribers')->update( $userdata );
				}
				// already on the list?
				$subs_list_ids = mailster('subscribers')->get_lists($subscriber_id, true); // get ids only
				if(in_array($list_id, $subs_list_ids)){
					$subs_on_list = true;
				}
			}else{
				// add subscriber
		        $overwrite = true;
		        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
			}

			// this also creates the payment_data row ...
			if($pmt_status == 'invoice'){
				$access_type = 'invoice';
			}else{
				$access_type = 'paid';
			}
			$payment_id = ccrecw_add_recording_to_user($user_id, $recording_id, $access_type, $amount, $token, $currency, $vat_included);
			if($discount_voucher == 'voucher'){
				ccpa_voucher_usage_record($voucher, $discAmount, $currency, $payment_id);
			}
	        if($subscriber_id > 0){
	        	if(!$subs_on_list){
			        // now add our user to that list
					mailster('subscribers')->assign_lists($subscriber_id, $list_id);
	        	}
				// now we can send out the thanks email
				$mailster_tags = array(
					'firstname' => $firstname,
					'recording' => get_the_title($recording_id),
					'amount' => $mailster_amount,
					'promo_msg' => ccpa_mailster_tag_promo_msg_direct(ccpa_get_payment($payment_id)),
					'set_password' => $set_password_msg,
					'date' => date('jS M Y'),
					'customer_address' => '', // we don't ask for this
					'registration_message' => nl2br( get_post_meta($recording_id, 'registration_message', true) ),
					'recording_access_expiry' => rpm_cc_recordings_expiry_pretty_expiry_datetime($user_id, $recording_id),
				);
	    		$add_special_action = false;
	    		$campaign_id = ccpa_get_campaign_id_from_hook($mailster_hook);
	    		if($campaign_id){
	    			$add_special_action = ccpa_subscriber_has_prev_action($subscriber_id, $campaign_id);
	    		}
	    		sysctrl_mailster_ar_hook($mailster_hook, $subscriber_id, $mailster_tags);
	    		if($add_special_action){
	    			ccpa_add_registration_resent_activity($subscriber_id, $campaign_id);
	        	}
			}else{
				$response['user'] = 'error';
				$response['msg'] = 'Sorry but we had a system error recording your registration. Please email us now so that we can sort it out for you.';
				$response['status'] = 'error';
			}
			$flagged_ok = ccpa_payint_client_secret_status_update($clientSecret, 'saving', 'used');
			ccrecw_tell_joe($user_id, $pmtmethod, $recording_id);
		}
	}
   	echo json_encode($response);
	die();
}

// return fields for the payment form
function ccrecw_form_value($field){
	if(!cc_users_is_valid_user_logged_in()) return '';
	$current_user = wp_get_current_user();
	switch ($field) {
		case 'email':
			return $current_user->user_email;
			break;
		case 'title':
			return get_user_meta($current_user->ID, 'title', true);
			break;
		case 'firstname':
			return $current_user->user_firstname;
			break;
		case 'lastname':
			return $current_user->user_lastname;
			break;
		case 'phone':
			return get_user_meta($current_user->ID, 'phone', true);
			break;
	}
	return '';
}

// add the extra meta fields to the user's profile page
add_action( 'show_user_profile', 'ccrecw_extra_user_profile_fields' );
add_action( 'edit_user_profile', 'ccrecw_extra_user_profile_fields' );
function ccrecw_extra_user_profile_fields( $user ) { ?>
	<h3>Portal User?</h3>
	<table class="form-table">
		<tr>
			<th><label for="portal_user">Portal User?</label></th>
			<td>
				<select name="portal_user" id="portal_user">
					<?php $portal_user = get_user_meta($user->ID, 'portal_user', true); ?>
					<option value="">No</option>
					<option value="cnwl" <?php selected('cnwl', $portal_user); ?>>CNWL</option>
					<option value="nlft" <?php selected('nlft', $portal_user); ?>>NLFT</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="portal_admin">Portal Admin?</label></th>
			<td>
				<select name="portal_admin" id="portal_admin">
					<?php $portal_admin = get_user_meta($user->ID, 'portal_admin', true); ?>
					<option value="">No</option>
					<option value="yes" <?php selected('yes', $portal_admin); ?>>Yes</option>
				</select>
				<p class="description">Ignored unless this is a portal user</p>
			</td>
		</tr>
	</table>
	<h3>Merged accounts</h3>
	<table class="form-table">
		<tr>
			<th><label for="">Email aliases:</label></th>
			<td>
				<?php
				$alias_found = false;
				for ($i=1; $i < 4; $i++) { 
					$email_alias = get_user_meta($user->ID, 'user_email_alias_'.$i, true);
					if($email_alias <> ''){
						echo $email_alias.'<br>';
						$alias_found = true;
					}
				}
				if(!$alias_found){
					echo 'None';
				}
				?>
			</td>
		</tr>
		<tr>
			<th><label for="">Merged account log</label></th>
			<td><?php echo get_user_meta($user->ID, 'merge_acct_log', true); ?></td>
		</tr>
	</table>
	<h3>Last Login</h3>
	<table class="form-table">
		<tr>
			<th><label for="">Last login</label></th>
			<td>
				<?php
				$last_login = get_user_meta($user->ID, 'last_login', true);
				if($last_login == '' || $last_login == 'never'){
					echo 'Never';
				}else{
					echo date('d/m/Y H:i:s', $last_login); ?>
					<div class="reset-last-login-wrap">
						<button type="button" class="button" id="reset-last-login" data-form-type="action">Reset last login</button>
					</div>
					<?php
				}
				?>
			</td>
		</tr>
	</table>
    <h3>Purchase data</h3>
    <table class="form-table">
    	<?php
		$metas = array('title', 'phone', 'vat', 'maillist', 'pmtmethod', 'invaddr', 'invemail', 'invphone', 'invref', 'currency', 'amount', 'clientSecret', 'voucher', 'discAmount');
		foreach ($metas as $meta) {
			?>
		    <tr>
		        <th><label for="<?php echo $meta; ?>"><?php echo ucfirst($meta); ?></label></th>
		        <td>
		        	<?php if($meta == 'invaddr'){ ?>
						<textarea name="<?php echo $meta; ?>" id="<?php echo $meta; ?>" cols="30" rows="10"><?php echo esc_attr( get_user_meta($user->ID, $meta, true) ); ?></textarea>
		        	<?php }else{ ?>
			            <input type="text" name="<?php echo $meta; ?>" id="<?php echo $meta; ?>" value="<?php echo esc_attr( get_user_meta($user->ID, $meta, true) ); ?>" class="regular-text">
			        <?php } ?>
		        </td>
		    </tr>
		<?php } ?>
    </table>
<?php }
// and save the fields
add_action( 'personal_options_update', 'ccrecw_save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'ccrecw_save_extra_user_profile_fields' );
function ccrecw_save_extra_user_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) { 
        return false; 
    }
	$metas = array('title', 'phone', 'vat', 'maillist', 'pmtmethod', 'invaddr', 'invemail', 'invphone', 'invref', 'amount', 'clientSecret', 'voucher', 'discAmount', 'portal_user', 'portal_admin');
	foreach ($metas as $meta) {
		update_user_meta($user_id, $meta, $_POST[$meta]);
	}
}

// reset user last login to never
add_action('wp_ajax_reset_last_login', 'ccrecw_reset_last_login');
function ccrecw_reset_last_login(){
	$response = array(
		'status' => 'error',
		'msg' => '',
	);
	$user = get_userdata( (int) $_POST['user_id'] );
	if ( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			$user = false;
		} elseif ( ! wp_verify_nonce( $_POST['nonce'], 'update-user_' . $user->ID ) ) {
			$user = false;
		}
	}

	if ( ! $user ) {
		$response['msg'] = 'Could not reset last login. Please try again.';
		/*
		wp_send_json_error(
			array(
				'message' => __( 'Could not reset last login. Please try again.' ),
			)
		);
		*/
	}else{
		update_user_meta($user->ID, 'last_login', 'never');
		$response['msg'] = 'Last login reset to "never". This user can now set a new password by going to '.site_url("my-account/?login=".rawurlencode( $user->user_email ) );
		$response['status'] = 'ok';

		// $message = 'Last login reset to "never". This user can now set a new password by going to '.site_url("my-account/?login=".rawurlencode( $user->user_email ) );
		// wp_send_json_success( array( 'message' => $message ) );	
	}
   	echo json_encode($response);
	die();
}

// if they want the newsletter than add them as a subscriber and put them on the list
function ccrecw_subscribe_news($user_id){
	if(function_exists('mailster')){
        // get all mailster lists
        $lists = mailster( 'lists' )->get();
        // we want the list ID for the newsletter list
        $news_list_id = 0;
        foreach ($lists as $list) {
        	if($list->name == 'Contextual Consulting Events'){
        		$news_list_id = $list->ID;
        		break;
        	}
        }
        // did we find the news list? If not, create it
        if($news_list_id == 0){
        	$news_list_id = mailster('lists')->add('Contextual Consulting Events');
        }
        // add/update subscriber
        $user = get_user_by('ID', $user_id);
        if($user){
			$userdata = array(
	            'email' => $user->user_email,
	            'firstname' => $user->user_firstname,
	            'lastname' => $user->user_lastname,
	        );
			$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
			if($subscriber){
				$subscriber_id = $subscriber->ID;
			}else{
				// add subscriber
		        $overwrite = true;
		        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
			}
			// add to the newsletter list
			mailster('subscribers')->assign_lists($subscriber_id, $news_list_id);
        }
	}
}

// tell Joe about the new registration
function ccrecw_tell_joe($user_id, $pmtmethod, $recording_id){
	$ccpa_client_email_record_pay_inv = esc_attr(get_option('ccpa_client_email_record_pay_inv'));
	$ccpa_client_email_record_pay_online = esc_attr(get_option('ccpa_client_email_record_pay_online'));
	if($pmtmethod == 'invoice' && $ccpa_client_email_record_pay_inv == 1 || $pmtmethod <> 'invoice' && $ccpa_client_email_record_pay_online == 1){
		$to = esc_attr(get_option('ccpa_client_email_addr'));
		$subject = '';
		if($pmtmethod == 'invoice'){
			$subject .= 'INVOICE NEEDED: ';
		}
		$subject .= 'Contextual Consulting: Recorded Workshop Purchase ('.$pmtmethod.')';
		$message = 'New Purchase:<br>';
		$message .= 'Recording: '.get_the_title($recording_id).'<br>';
		$user = get_user_by('ID', $user_id);
		$message .= 'email: '.$user->user_email.'<br>';
		$message .= 'firstname: '.$user->user_firstname.'<br>';
		$message .= 'lastname: '.$user->user_lastname.'<br>';
		$metas = array('title', 'phone', 'vat', 'vat_uk', 'vat_employ', 'vat_employer', 'vat_website', 'maillist', 'pmtmethod', 'invaddr', 'invemail', 'invphone', 'invref', 'currency', 'amount', 'clientSecret', 'voucher', 'discAmount');
		foreach ($metas as $meta) {
			$message .= $meta.': '.get_user_meta($user_id, $meta, true).'<br>';
		}
		if($pmtmethod == 'invoice'){
			$message .= '<br><strong>Please send invoice and, once paid, give access</strong>';
		}
		$headers = array(
			'From: '.esc_attr(get_option('ccpa_email_from_name')).' <'.esc_attr(get_option('ccpa_email_from_addr')).'>',
			'Content-Type: text/html; charset=UTF-8',
		);
		$result = wp_mail($to, $subject, $message, $headers);
		// wp mail debugging
		if (!$result) {
		    global $ts_mail_errors;
		    global $phpmailer;
		    if (!isset($ts_mail_errors)) $ts_mail_errors = array();
		    if (isset($phpmailer)) {
		        $ts_mail_errors[] = $phpmailer->ErrorInfo;
		    }
		    error_log(print_r($ts_mail_errors, true));
		}
	}
	return;
}

/*
add_shortcode('send_apology_email', 'ccrecw_send_apology_email');
function ccrecw_send_apology_email(){
	$subscribers = array();
	$count = 0;
	foreach ($subscribers as $subscriber_id) {
		$result = do_action('send_apology_email_now', $subscriber_id);
		$count ++;
		// if($count > 2) break;
	}
	return $count;
}

add_shortcode('send_correction_email', 'ccrecw_send_correction_email');
function ccrecw_send_correction_email(){
	$subscribers = array();
	$count = 0;
	foreach ($subscribers as $subscriber_id) {
		$result = do_action('send_correction_email_now', $subscriber_id);
		$count ++;
		// if($count > 2) break;
	}
	return $count;
}
*/

/*
// find subscribers who missed the 20% all event discount
add_shortcode('discount_missed', 'ccrecw_discount_missed');
function ccrecw_discount_missed(){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	$html = 'Start';
	foreach ($pmts as $pmt) {
		if($pmt['last_update'] > '2020-04-21 21:00:00'){
			// if($pmt['status'] == 'Payment successful: '){
			if($pmt['status'] == 'Invoice requested'){
				if($pmt['event_ids'] <> ''){
					if(substr_count($pmt['event_ids'], ',') > 9){
						$html .= '<br>'.$pmt['id'].' '.$pmt['firstname'].' '.$pmt['lastname'].' '.$pmt['email'];
					}
				}
			}
		}
	}
	$html .= '<br>End';
	return $html;
}
*/

/*
// find people who were sent the free registration email in error
add_shortcode('erroneous_free_registrations', 'ccrecw_erroneous_free_registrations');
function ccrecw_erroneous_free_registrations(){
	global $wpdb;
	$actions_table = $wpdb->prefix.'mailster_actions';
	$subscriber_fields_table = $wpdb->prefix.'mailster_subscriber_fields';
	$sql = "SELECT * FROM $actions_table WHERE campaign_id = 1673 AND type = 1";
	$actions = $wpdb->get_results($sql, ARRAY_A);
	$html = 'Start';
	$bad_ids = array();
	foreach ($actions as $action) {
		$sql = "SELECT * FROM $subscriber_fields_table WHERE subscriber_id = ".$action['subscriber_id']." AND meta_key = 'last_payment_id'";
		$meta_rows = $wpdb->get_results($sql, ARRAY_A);
		$last_payment_id = 0;
		foreach ($meta_rows as $meta_row) {
			if($last_payment_id == 0){
				$last_payment_id = $meta_row['meta_value'];
			}else{
				$html .= '<br>Subscriber '.$action['subscriber_id'].' has multiple last_payment_id rows - ignored';
				$last_payment_id = -1;
				break;
			}
		}
		if($last_payment_id == 0){
			$html .= '<br>Subscriber '.$action['subscriber_id'].' last_payment_id not found';
			$bad_ids[] = $action['subscriber_id'];
		}elseif($last_payment_id > 0){
			$payment_data = ccpa_get_payment($last_payment_id);
			if($payment_data === NULL){
				$html .= '<br>Subscriber '.$action['subscriber_id'].' payment data '.$last_payment_id.' not found';
				$bad_ids[] = $action['subscriber_id'];
			}else{
				if($payment_data['status'] == 'Payment not needed'){
					// then they probably should have been sent a free reg email
					if($payment_data['event_ids'] <> '4,'){
						$html .= '<br>Subscriber '.$action['subscriber_id'].' INcorrectly sent free recording email - wrong events';
						$bad_ids[] = $action['subscriber_id'];
					}
				}else{
					// then they should not have got the email
					$html .= '<br>Subscriber '.$action['subscriber_id'].' INcorrectly sent free recording email';
					$bad_ids[] = $action['subscriber_id'];
				}
			}
		}
	}
	$html .= '<br>End<br>';
	foreach ($bad_ids as $bad_id) {
		$html .= "'$bad_id', ";
	}
	return $html;
}
*/
/*
add_shortcode('send_reg_apology_email', 'ccrecw_send_reg_apology_email');
function ccrecw_send_reg_apology_email(){
	$subscribers = array('5976', '6172', '7205', '7350', '7616', '7836', '5966', '6953', '7380', '6797', '6950', '7262', '7492', '6267', '7159', '6990', '6203', '6188', '5866', '7001', '7282', '7315', '7332', '7590', '7626', '7806', '6090', '6336', '6978', '7139', '5911', '7008', '7527', '5974', '6121', '6840', '7592', '7009', '1462', '6120', '6206', '67', '5403', '5850', '6211', '6275', '6795', '7028', '7054', '7209', '7227', '7252', '7276', '7279', '7290', '7304', '7348', '7650', '7713', '7720', '7729', '7808', '7479', '265', '5583', '6921', '7052', '8', '5627', '6029', '490', '5977', '6218', '6930', '6939', '7230', '7243', '7269', '7283', '7302', '7408', '7537', '7550', '7565', '7568', '7576', '7584', '7598', '7630', '3669', '6187', '2012', '5973', '6076', '5411', '6135', '6143', '6270', '7022', '7112', '33', '268', '5052', '5923', '6292', '7011', '404', '3185', '4206', '4467', '4822', '6781', '6887', '7077', '7090', '7194', '7580', '485', '6080', '6159', '6180', '7169', '7', '2817', '5733', '6179', '3810', '1440', '7025', '2975', '7223', '7285', '7286', '7296', '7596', '2520', '6220', '7015', '5887', '6026', '6107', '7046', '3628', '4202', '4760', '7058', '7094', '23', '475', '2613', '3635', '4087', '5053', '5600', '5741', '5936', '6103', '6126', '6129', '6273', '6927', '6973', '7006', '7073', '7165', '7226', '7228', '7229', '7232', '7234', '7236', '7239', '7242', '7244', '7246', '7247', '7249', '7250', '7254', '7256', '7261', '7263', '7266', '7273', '7274', '7277', '7278', '7280', '7281', '7293', '7294', '7295', '7297', '7298', '7299', '7301', '7443', '7471', '7543', '7547', '7553', '7554', '7556', '7559', '7560', '7561', '7571', '7574', '7577', '7578', '7579', '7581', '7583', '7585', '7587', '7589', '7594', '7595', '7599', '7600', '7601', '7604', '7606', '7610', '7615', '7617', '7618', '7619', '7621', '7623', '7624', '7625', '7629', '7631', '7632', '7633', '7634', '7636', '7639', '7647', '7649', '7651', '7652', '7656', '7659', '7665', '7667', '7668', '7669', '7670', '7673', '7678', '7682', '7683', '7690', '7694', '7696', '7702', '7707', '7708', '7711', '7717', '7724', '7726', '7727', '7728', '7730', '7733', '7734', '7736', '7737', '7738', '7740', '7741', '7742', '7743', '7745', '7746', '7748', '7752', '7756', '7764', '7766', '7768', '7771', '7774', '7775', '7776', '7779', '7783', '7784', '7787', '7788', '7789', '7791', '7795', '7798', '7800', '7803', '7805', '7811', '7820', '7825', '7828', '7830', '7831', '7832', '7833', '7834', '7835', '7837', '7838', '7839', '7840', '7841', '7842', '7844', '7845', '7846', '7848', '7849', '7850', '7852', '7854', '7855', '7856', '7857', '7858', '7861', '7863', '7865', '7868', '7878', '7880', '7882', '7883', '7884', '7885', '7887', '7889', '7891', '7892', '7894', '7896', '7897', '7898', '7899', '7900', '7902', '7904', '7905', '7907', '7908', '7909', '7910', '7911', '7915', '7918', '7920', '7925', '5133', '6764', '804', '6737', '7012', '1038', '1692', '5857', '5882', '6885', '6904', '31', '149', '1434', '1709', '2599', '5739', '6155', '6441', '6905', '7023', '108', '753', '1024', '1626', '3257', '3904', '3922', '6066', '6119', '6134', '6173', '6189', '6944', '6967', '7062', '7102', '201', '308', '5872', '6830', '6992', '4325', '40', '483', '782', '1608', '2418', '5548', '6136', '7036', '5009', '6', '69', '227', '1092', '1193', '3109', '5797', '7109', '5908', '6151', '6208', '6369', '1149', '7193', '2', '1404', '1769', '2016', '2261', '2861', '3936', '4312', '5780', '5854', '5935', '7115', '71', '249', '533', '1543', '3954', '5711', '5983', '6993', '7080', '7241', '7260', '7291', '7569', '7660', '4478', '6070', '4', '1354', '2231', '4038', '262', '3044', '4361', '6989', '847', '624', '4311', '5545', '6078', '6991', '2946', '4143', '1312', '1264', '5798', '5894', '6094', '6214', '6257', '7128', '7231', '7270', '7289', '7545', '7663', '7664', '7686', '7689', '7695', '7709', '7735', '7747', '7754', '7758', '7761', '7765', '7772', '7777', '7781', '96', '1144', '3374', '3487', '3981', '4346', '5169', '6281', '6772', '17', '169', '4105', '6947', '4888', '101', '2026', '4389', '6199', '6212', '4805', '28', '2109', '5931', '48', '777', '1154', '1225', '1567', '3433', '4867', '4925', '5297', '6068', '6091', '1023', '3627', '1', '29', '49', '55', '92', '238', '244', '275', '340', '365', '583', '662', '959', '1026', '1191', '1233', '1334', '1582', '2073', '2715', '2805', '2826', '3006', '3101', '3699', '3867', '3873', '3959', '4229', '4292', '4313', '4543', '4689', '4773', '5254', '5523', '5616', '5676', '5710', '5731', '5748', '5805', '5810', '5831', '5881', '5912', '5939', '5964', '5965', '5975', '6024', '6074', '6075', '6092', '6095', '6115', '6139', '6161', '6163', '6164', '6165', '6167', '6168', '6169', '6184', '6210', '6217', '6277', '6348', '6583', '6782', '6783', '6889', '6920', '6962', '6970', '6977', '6979', '6981', '7057', '7084', '7206', '7219', '7222', '7224', '7225', '7233', '7235', '7245', '7258', '7259', '7264', '7265', '7267', '7271', '7272', '7275', '7292', '7305', '7542', '7544', '7548', '7552', '7555', '7558', '7562', '7564', '7573', '7582', '7588', '7593', '7597', '7605', '7607', '7608', '7611', '7613', '7622', '7627', '7628', '7637', '7638', '7640', '7641', '7643', '7644', '7648', '7654', '7655', '7661', '7671', '7672', '7680', '7681', '7684', '7687', '7688', '7691', '7692', '7693', '7697', '7698', '7700', '7701', '7703', '7705', '7710', '7712', '7715', '7718', '7719', '7725', '7753', '7755', '7760', '7770', '7773', '7778', '7782', '7796', '7799', '7801', '7802', '7807', '7815', '7821', '7826', '7827', '7843', '7853', '7866', '7867', '7870', '7871', '7874', '7877', '7886', '7888', '7890', '7901', '7917', '7922', '7928', '7957', '8005', '8383', '8172', '8719', '6722', '8029', '8042', '8051', '8057', '8070', '8093', '8111', '8125', '8132', '8159', '8166', '8168', '8178', '8185', '8239', '8241', '8189', '8272', '8293', '8368', '8591', '8310', '8322', '2625', '8361', '8369', '8379', '8459', '8464', '8473', '8477', '8499', '8510', '8524', '8535', '8537', '8552', '8657', '8663', '8667', '8676', '8710', '8702', '8704', '8721', '8751', '8767', '8781', '8807', '8804', '8821', '8823', '5885', '2780', '90', '2857', '11', '429', '4184', '5012', '5742');
	$count = 0;
	foreach ($subscribers as $subscriber_id) {
		$result = do_action('send_reg_apology_email_now', $subscriber_id);
		$count ++;
		// if($count > 2) break;
	}
	return $count; // 783
}
*/

// list merging
add_shortcode('ccrecw_list_merging', 'ccrecw_list_merging');
function ccrecw_list_merging(){
	global $wpdb;
	$html = 'Start';
	// we need to find all the subscribers for the old lists
	$old_lists = "'20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '33', '34', '35'";
	$list_events_v1 = array(
		'21' => '10',
		'22' => '6',
		'23' => '3',
		'24' => '1',
		'25' => '9',
		'26' => '8',
		'27' => '7',
		'28' => '5',
		'29' => '4',
		'30' => '2',
	);
	// list changed 20/04/2020 21:32
	$list_events_v2 = array(
		'21' => '11',
		'22' => '7',
		'23' => '3',
		'24' => '1',
		'25' => '10',
		'26' => '9',
		'27' => '8',
		'28' => '6',
		'29' => '5',
		'30' => '2',
		'33' => '4',
	);
	$list_events_v3 = array(
		'35' => '4',
	);
	$list_map_1 = array(
		'20' => '36',
		'21' => '41',
		'22' => '45',
		'23' => '43',
		'24' => '37',
		'25' => '47',
		'26' => '44',
		'27' => '46',
		'28' => '40',
		'29' => '39',
		'30' => '42',
	);
	$list_map_2 = array(
		'20' => '36',
		'21' => '41',
		'22' => '45',
		'23' => '43',
		'24' => '37',
		'25' => '47',
		'26' => '44',
		'27' => '46',
		'28' => '40',
		'29' => '39',
		'30' => '42',
		'33' => '38',
	);
	$list_map_3 = array(
		'34' => '36',
		'35' => '38',
	);
	$events_map = array(
		'1' => '1',
		'2' => '2',
		'3' => '3',
		'4' => '5',
		'5' => '6',
		'6' => '7',
		'7' => '8',
		'8' => '9',
		'9' => '10',
		'10' => '11',
	);
	$sql = "SELECT DISTINCT subscriber_id FROM {$wpdb->prefix}mailster_lists_subscribers WHERE list_id IN($old_lists)";
	$subs_ids = $wpdb->get_col($sql);
	$html .= '<br>'.count($subs_ids).' subscribers found';
	$subs_count = 0;
	foreach ($subs_ids as $subs_id) {
		$subscriber = mailster('subscribers')->get($subs_id);
		if($subscriber){
			$html .= '<br>'.$subs_id.' '.$subscriber->email.' ';
			// let's find out what lists this subscriber is in
			$sub_list_ids = mailster('subscribers')->get_lists($subs_id, true);
			$added = 'n/a';
			if(in_array(35, $sub_list_ids)){
				$list_events = $list_events_v3;
				$list_map = $list_map_3;
				$map_events = false;
				$html .= '<strong>List 3</strong> ';
			}else{
				if(in_array(20, $sub_list_ids)){
					$sql = "SELECT added from {$wpdb->prefix}mailster_lists_subscribers WHERE list_id = 20 AND subscriber_id = $subs_id";
					$added = $wpdb->get_var( $sql );
					if($added === NULL){
						$html .= '<strong>added not found</strong> ';
					}else{
						if(in_array(33, $sub_list_ids) || ($added > 1587418340 && $added < 1587655580)){
							$list_events = $list_events_v2;
							$list_map = $list_map_2;
							$map_events = false;
							$html .= '<strong>List 2</strong> ';
						}else{
							$list_events = $list_events_v1;
							$list_map = $list_map_1;
							$map_events = true;
							$html .= 'List 1 ';
						}
					}
				}else{
					$html .= '<strong>not on main workshop list</strong> ';
					$added = NULL;
				}
			}
			if($added !== NULL){
				// we also want to know what event ids have been set in the payment record
				$sql = "SELECT * FROM {$wpdb->prefix}ccpa_payments WHERE email = \"".$subscriber->email."\" AND workshop_id = '1601' AND last_update < '2020-04-21 09:58:00' AND status IN ('Payment successful: ', 'Payment not needed', 'Invoice requested', 'Invoice Sent')";
				$pay_rows = $wpdb->get_results($sql, ARRAY_A);
				if(count($pay_rows) <> 1){
					$html .= count($pay_rows).' <strong>payments found</strong> ';
				}
				if(count($pay_rows) == 0){
					$sql = "SELECT * FROM {$wpdb->prefix}ccpa_payments WHERE attendee_email = \"".$subscriber->email."\" AND workshop_id = '1601' AND last_update < '2020-04-21 09:58:00' AND status IN ('Payment successful: ', 'Payment not needed', 'Invoice requested', 'Invoice Sent')";
					$pay_rows = $wpdb->get_results($sql, ARRAY_A);
					if(count($pay_rows) <> 1){
						$html .= count($pay_rows).' <strong>payments found for attendee email</strong> ';
					}else{
						$html .= '<strong>using attendee email</strong> ';
					}
				}
				
				if(count($pay_rows) == 1){
					$attempt_update = true;
				}else{
					$attempt_update = false;
				}
				foreach ($pay_rows as $pay_row) {
					$html .= 'Last update: <strong>'.$pay_row['last_update'].'</strong> ';
					$event_ids = explode(',', $pay_row['event_ids']);
					$tidy_event_ids = array();
					foreach ($event_ids as $event_id) {
						if($event_id > 0){
							$tidy_event_ids[] = $event_id;
						}
					}
					sort($tidy_event_ids);
					$expected_events = array();
					foreach ($list_events as $key => $value){
						if(in_array($key, $sub_list_ids)){
							$expected_events[] = $value;
						}
					}
					sort($expected_events);
					if($tidy_event_ids == $expected_events){
						$html .= 'event IDs match ';
						$unassign_lists = array();
						$assign_lists = array();
						foreach ($list_map as $old => $new) {
							if(in_array($old, $sub_list_ids)){
								$unassign_lists[] = $old;
								$assign_lists[] = $new;
							}
						}
						$html .= 'removing lists: ';
						$html .= print_r($unassign_lists, true);
						$html .= 'adding lists: ';
						$html .= print_r($assign_lists, true);
						if($map_events){
							$new_event_ids = '';
							foreach ($events_map as $old => $new) {
								if(in_array($old, $tidy_event_ids)){
									$new_event_ids .= $new.',';
								}
							}
							$html .= 'old events: ';
							$html .= print_r($tidy_event_ids, true);
							$html .= 'new events: ';
							$html .= print_r($new_event_ids, true);
							if($pay_row['event_ids'] == $new_event_ids){
								$html .= 'event update not needed ';
								$map_events = false;
							}
						}else{
							$html .= 'events unchanging: ';
							$html .= print_r($tidy_event_ids, true);
						}
						if($attempt_update){
							$result = mailster('subscribers')->unassign_lists($subs_id, $unassign_lists);
							if(!$result){
								$html .= 'unassign_lists failed!';
								break;
							}
							$result = mailster('subscribers')->assign_lists($subs_id, $assign_lists);
							if(!$result){
								$html .= 'assign_lists failed!';
								break;
							}
							if($map_events){
								$data = array(
									'event_ids' => $new_event_ids
								);
								$where = array(
									'id' => $pay_row['id']
								);
								$result = $wpdb->update( $wpdb->prefix.'ccpa_payments', $data, $where );
								if($result === false){
									$html .= 'event ids update failed!';
									break;
								}
							}
							$html .= '<strong>updated</strong> ';
						}else{
							$html .= 'not attempting update ';
						}
					}else{
						$html .= 'event IDs <strong>mismatch:</strong> Expected (Mailster): ';
						$html .= print_r($expected_events, true);
						$html .= ' Found (CCPA): ';
						$html .= print_r($tidy_event_ids, true);
						$html .= ' ';
					}
				}
			}
		}else{
			$html .= '<br>Subscriber ID '.$subs_id.' <strong>not found</strong>';
		}
		$subs_count ++;
		if($subs_count > 100) break;
	}
	$html .= '<br>'.$subs_count.' processed';
	$html .= '<br>End';
	return $html;
}

/*
// correct the correction for subscribers who missed the events lists and were added to them but not to the associated main workshop list
add_shortcode('ccrecw_add_missing_main_list', 'ccrecw_add_missing_main_list');
function ccrecw_add_missing_main_list(){
	global $wpdb;
	$html = 'Start';
	// we need to find all the subscribers for the old lists
	$old_lists = "'21', '22', '23', '24', '25', '26', '27', '28', '29', '30'";
	$sql = "SELECT DISTINCT subscriber_id FROM {$wpdb->prefix}mailster_lists_subscribers WHERE list_id IN($old_lists)";
	$subs_ids = $wpdb->get_col($sql);
	$html .= '<br>'.count($subs_ids).' subscribers found';
	$subs_count = 0;
	foreach ($subs_ids as $subs_id) {
		$subscriber = mailster('subscribers')->get($subs_id);
		if($subscriber){
			$html .= '<br>'.$subs_id.' '.$subscriber->email.' ';
			// let's see if the subscriber is also in list 20 - the main workshop list
			$sql = "SELECT added from {$wpdb->prefix}mailster_lists_subscribers WHERE list_id = 20 AND subscriber_id = $subs_id";
			$added = $wpdb->get_var( $sql );
			if($added === NULL){
				$html .= '<strong>not on main list ... adding</strong> ';
				$result = mailster('subscribers')->assign_lists($subs_id, '20');
				if($result){
					$html .= 'done ';
				}else{
					$html .= '<strong>something went wrong!</strong> ';
				}
			}else{
				$html .= 'on main list ';
			}
		}else{
			$html .= '<br>Subscriber ID '.$subs_id.' <strong>not found</strong>';
		}
		$subs_count ++;
		// if($subs_count > 20) break;
	}
	$html .= '<br>'.$subs_count.' processed';
	$html .= '<br>End';
	return $html;
}
*/
/*
// correct lost events when invoice sent
add_shortcode('ccrecw_correct_inv_lost_events', 'ccrecw_correct_inv_lost_events');
function ccrecw_correct_inv_lost_events(){
	global $wpdb;
	$html = 'Start';
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name WHERE pmt_method = 'invoice' AND workshop_id = '1601'";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	foreach ($pmts as $pmt) {
		if($pmt['status'] <> 'Invoice requested' && $pmt['event_ids'] == ''){
			$html .= '<br>'.$pmt['id'].' '.$pmt['email'];
		}
	}
	$html .= '<br>End';
	return $html;
}
*/

add_shortcode('ccrecw_attendee_tidyup', 'ccrecw_attendee_tidyup');
function ccrecw_attendee_tidyup(){
	global $wpdb;
	$html = 'Start';
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name WHERE attendee_email <> '' AND attendee_email <> email AND workshop_id = '1601'";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	foreach ($pmts as $pmt) {
		$html .= '<br>'.$pmt['id'].' email:'.$pmt['email'].' attendee:'.$pmt['attendee_email'];
		$manual = false;
		$subscriber = mailster('subscribers')->get_by_mail($pmt['email']);
		if($subscriber){
			$html .= ' subs_id:'.$subscriber->ID;
		}
		$other_pays = get_pay_recs($pmt['email'], $pmt['id']);
		if(empty($other_pays)){
			$html .= ' no other pay recs';
		}else{
			$html .= ' '.count($other_pays).' other pay recs:';
			$html .= print_r($other_pays, true);
			$manual = true;
		}
		$attendee = mailster('subscribers')->get_by_mail($pmt['attendee_email']);
		if($attendee){
			$html .= ' attendee subs_id:'.$attendee->ID;
		}
		$other_pays = get_pay_recs($pmt['attendee_email'], $pmt['id']);
		if(empty($other_pays)){
			$html .= ' no other pay recs';
		}else{
			$html .= ' '.count($other_pays).' other pay recs:';
			$html .= print_r($other_pays, true);
			$manual = true;
		}
		if($manual){
			$html .= ' do it manually';
		}else{
			$html .= ' automagic?';
		}
	}
	$html .= '<br>End';
	return $html;
}

function get_pay_recs($email, $not_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT id FROM $table_name WHERE (email = '$email' OR attendee_email = '$email') AND id <> '$not_id'";
	return $wpdb->get_col( $sql );
}

// look for subscribers who are "not confirmed" and confirm them
add_shortcode('ccrecw_correctly_confirm_subscribers', 'ccrecw_correctly_confirm_subscribers');
function ccrecw_correctly_confirm_subscribers(){
	global $wpdb;
	$html = 'Start';
	$table_name = $wpdb->prefix.'mailster_lists_subscribers';
	$sql = "SELECT * FROM $table_name WHERE added = 0";
	$lists_subs = $wpdb->get_results($sql, ARRAY_A);
	foreach ($lists_subs as $list_sub) {
		$html .= '<br>List: '.$list_sub['list_id'].' sub: '.$list_sub['subscriber_id'];
	}
	$html .= '<br>End';
	return $html;
}

// look for payments not on the correct mailster lists
add_shortcode('ccrecw_payments_not_on_lists', 'ccrecw_payments_not_on_lists');
function ccrecw_payments_not_on_lists(){
	global $wpdb;
	$html = 'Start';
	$table_name = $wpdb->prefix.'ccpa_payments';
	$sql = "SELECT * FROM $table_name WHERE workshop_id <> '' AND event_ids <> ''";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	$mailster_lists = mailster( 'lists' )->get();
	$list_xref = array();
	foreach ($pmts as $pmt){
		if($pmt['status'] == 'Invoice Sent' || $pmt['status'] == 'Payment failed'){
			continue;
		}
		$show_pmt = false;
		$pmt_html = '<br>'.$pmt['id'].' ';
		$workshop_id = $pmt['workshop_id'];
		$wanted_list_ids = array();
		if($pmt['attendee_email'] == ''){
			$subs_email = $pmt['email'];
		}else{
			$subs_email = $pmt['attendee_email'];
		}
		$pmt_html .= $subs_email.' ';
		$subscriber = mailster('subscribers')->get_by_mail($subs_email);
		if($subscriber){
			$subscriber_id = $subscriber->ID;
			if(isset($list_xref[$workshop_id]['0']) && $list_xref[$workshop_id]['0'] <> 0){
				$wanted_list_ids[] = $list_xref[$workshop_id]['0'];
			}else{
		        $workshop_title = ccpa_workshop_title($pmt);
		        $sanitised_workshop_title = sanitize_title( $workshop_title );
		        foreach ($mailster_lists as $mailster_list){
		        	if($mailster_list->slug == $sanitised_workshop_title){
		        		$list_xref[$workshop_id]['0'] = $mailster_list->ID;
		        		$wanted_list_ids[] = $mailster_list->ID;
		        		break;
		        	}
		        }
			}
			$event_ids = explode(',', $pmt['event_ids']);
			foreach ($event_ids as $event_id) {
				if($event_id > 0){
					if(isset($list_xref[$workshop_id][$event_id]) && $list_xref[$workshop_id][$event_id] <> 0){
						$wanted_list_ids[] = $list_xref[$workshop_id][$event_id];
					}else{
						$event_name = get_post_meta($pmt['workshop_id'], 'event_'.$event_id.'_name', true);
						$list_name = $workshop_title.': '.$event_name;
						$sanitized_list_name = sanitize_title($list_name);
				        foreach ($mailster_lists as $mailster_list){
				        	if($mailster_list->slug == $sanitized_list_name){
				        		$list_xref[$workshop_id][$event_id] = $mailster_list->ID;
				        		$wanted_list_ids[] = $mailster_list->ID;
				        		break;
				        	}
				        }
					}
				}
			}
			$subs_lists = mailster('subscribers')->get_lists($subscriber->ID, true); // true == ids only
			$lists_to_add = array();
			foreach ($wanted_list_ids as $wanted_list_id) {
				if(!in_array($wanted_list_id, $subs_lists)){
					$lists_to_add[] = $wanted_list_id;
				}
			}
			if(!empty($lists_to_add)){
				$pmt_html .= 'not in lists: ';
				foreach ($lists_to_add as $list_to_add) {
					$pmt_html .= $list_to_add.' ';
					$show_pmt = true;
				}
			}


		}else{
			$pmt_html .= 'subscriber not found.';
			$show_pmt = true;
		}

		if($show_pmt){
			$html .= $pmt_html;
		}
	}
	$html .= '<br>End';
	return $html;
}

// create and/or update the free recordings email queue table
add_action('after_setup_theme', 'ccrecw_queue_db_update');
function ccrecw_queue_db_update(){
	global $wpdb;
	$ccrecw_queue_db_ver = 3;
	// v3 Aug 2022 (v1.0.3.41x) added email_switch
	// v2 Oct 2020 (v1.0.3.19x) changed list_id from mediumint(9) to int(9)
	$installed_table_ver = get_option('ccrecw_queue_db_ver');
	if($installed_table_ver <> $ccrecw_queue_db_ver){
		$table_name = $wpdb->prefix.'freerec_queue';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			type varchar(5) NOT NULL,
			email varchar(255) NOT NULL,
			list_id int(11) NOT NULL,
			recording_id mediumint(9) NOT NULL,
			status varchar(25) NOT NULL,
			email_switch varchar(15) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccrecw_queue_db_ver', $ccrecw_queue_db_ver);
	}
}

// add emails to queue - when entered from form field
function ccrecw_add_to_queue_form($emails, $recording_id, $email_switch){
	global $wpdb;
	ccpa_write_log('ccrecw_add_to_queue_form');
	ccpa_write_log('emails:'.$emails.' recording:'.$recording_id);
	$table_name = $wpdb->prefix.'freerec_queue';
	$emails = explode("\n", $emails);
	$email_count = 0;
	$data = array(
		'type' => 'email',
		'email' => '',
		'list_id' => 0,
		'recording_id' => $recording_id,
		'status' => 'queued',
		'email_switch' => $email_switch,
	);
	$format = array('%s', '%s', '%d', '%d', '%s', '%s');
	foreach ($emails as $email) {
		$email = sanitize_email($email);
		if($email <> ''){
			$data['email'] = $email;
			$wpdb->insert( $table_name, $data, $format);
			$email_count ++;
		}
	}
	ccpa_write_log('ccrecw_add_to_queue_form done');
	return $email_count;
}

// add to the queue from a mailsetr list
function ccrecw_add_to_queue_mailster($mailster_list, $recording_id, $email_switch){
	global $wpdb;
	ccpa_write_log('ccrecw_add_to_queue_mailster');
	ccpa_write_log('list:'.$mailster_list.' recording:'.$recording_id);
	$table_name = $wpdb->prefix.'freerec_queue';
	$data = array(
		'type' => 'list',
		'email' => '',
		'list_id' => $mailster_list,
		'recording_id' => $recording_id,
		'status' => 'queued',
		'email_switch' => $email_switch,
	);
	$format = array('%s', '%s', '%d', '%d', '%s', '%s');
	$wpdb->insert( $table_name, $data, $format);
	ccpa_write_log('ccrecw_add_to_queue_mailster done');
	return mailster( 'lists' )->get_member_count( $mailster_list, 1 );
}

// add a time interval to the cron intervals
add_filter( 'cron_schedules', 'ccrecw_add_cron_interval' );
function ccrecw_add_cron_interval( $schedules ) { 
    $schedules['ccrecw_interval'] = array(
        'interval' => 60, // one minute
        'display'  => esc_html__( 'Every Minute' ), );
    return $schedules;
}

// start processing the queue
function ccrecw_start_queue(){
	// ccpa_write_log('ccrecw_start_queue');
	// ccpa_write_log( 'next scheduled: '. wp_next_scheduled( 'ccrecw_queue_cron_hook' ) );
	if ( ! wp_next_scheduled( 'ccrecw_queue_cron_hook' ) ) {
		// ccpa_write_log('scheduling it');
	    $result = wp_schedule_event( time(), 'ccrecw_interval', 'ccrecw_queue_cron_hook' );
	    if(is_wp_error($result)){
	    	// ccpa_write_log('Error: '.$result->get_error_message());
	    }else{
	    	// ccpa_write_log('scheduled without error');
	    }
	}else{
		// ccpa_write_log('not scheduling it');
	}
	// ccpa_write_log( 'now scheduled: '. wp_next_scheduled( 'ccrecw_queue_cron_hook' ) );
}

// stop processing the queue
function ccrecw_queue_stop(){
	if ( wp_next_scheduled( 'ccrecw_queue_cron_hook' ) ) {
		$timestamp = wp_next_scheduled( 'ccrecw_queue_cron_hook' );
	    wp_unschedule_event( $timestamp, 'ccrecw_queue_cron_hook' );
	}
}

// queue processing
add_action('ccrecw_queue_cron_hook', 'ccrecw_queue_processing');
function ccrecw_queue_processing(){
	global $wpdb;
	ccpa_write_log('ccrecw_queue_processing');
	$q_processing = get_option('ccrecw_q_processing', '');
	ccpa_write_log($q_processing);
	if($q_processing <> 'locked'){
		update_option('ccrecw_q_processing', 'locked');
		update_option('ccrecw_q_started', time());
		$table_name = $wpdb->prefix.'freerec_queue';
		$sql = "SELECT * FROM $table_name WHERE status = 'queued' ORDER BY id LIMIT 100";
		$queued_items = $wpdb->get_results($sql, ARRAY_A);
		if(count($queued_items) == 0){
			ccpa_write_log('going idle');
			update_option('ccrecw_q_processing', 'idle');
			ccrecw_queue_stop();
		}else{
			foreach ($queued_items as $q_item) {
				ccpa_write_log($q_item);
				if($q_item['type'] == 'email'){
					ccrecw_process_email($q_item['email'], $q_item['recording_id'], $q_item['email_switch']);
					$where = array(
						'id' => $q_item['id'],
					);
					$wpdb->delete( $table_name, $where );
				}else{
					$lists_subs_table = $wpdb->prefix.'mailster_lists_subscribers';
					$last_subs_id = get_option('ccrecw_q_last_subs', 0);
					$sql = "SELECT subscriber_id FROM $lists_subs_table WHERE list_id = ".$q_item['list_id']." AND subscriber_id > $last_subs_id ORDER BY subscriber_id LIMIT 100";
					ccpa_write_log($sql);
					$subs_ids = $wpdb->get_col( $sql );
					ccpa_write_log($subs_ids);
					if(count($subs_ids) == 0){
						ccpa_write_log('list done');
						$where = array(
							'id' => $q_item['id'],
						);
						$wpdb->delete( $table_name, $where );
						update_option( 'ccrecw_q_last_subs', 0 );
					}else{
						foreach ($subs_ids as $subs_id) {
							$subscriber = mailster('subscribers')->get($subs_id);
							ccpa_write_log($subscriber);
							ccrecw_process_email($subscriber->email, $q_item['recording_id'], $q_item['email_switch']);
							if($subs_id > $last_subs_id){
								update_option( 'ccrecw_q_last_subs', $subs_id );
							}
						}
					}
				}
			}
			ccpa_write_log('pausing');
			update_option('ccrecw_q_processing', 'idle');
		}
	}else{
		ccpa_write_log('waiting for unlock');
		$last_started = get_option('ccrecw_q_started', 0);
		if((time() - $last_started) > 300){
			// started over 5 mins ago
			$message = "Time now: ".date('d/m/Y H:i:s')."\n";
			$message .= "Last started: ".date('d/m/Y H:i:s', $last_started)."\n";
			wp_mail( get_bloginfo('admin_email'), 'Free recording Q maybe frozen', $message );
		}
	}
}

// register the email address and send the emails as necessary
function ccrecw_process_email($email, $recording_id, $email_switch){
	// ccpa_write_log('ccrecw_process_email');
	$user = get_user_by('email', $email);
	if($user){
		$user_id = $user->ID;
		$recording_access = ccrecw_user_can_view($recording_id, $user_id);
		if($recording_access['access']){
			// can already access it, don't bother to tell them
		}else{
			// if CNWL user, they are not given access ... they have to register ... no longer true - they now (jun 23) get access
			/*
			$portal_user = get_user_meta($user_id, 'portal_user', true);
			if($portal_user == 'cnwl'){
				// do nothing
			}else{
				*/
				ccrecw_add_recording_to_user($user_id, $recording_id);
				if($email_switch == 'yes'){
					ccrecw_send_extra_rec_email($user_id, $recording_id);
				}
			// }
		}
	}else{
		$username = $email.' '.uniqid();
		$user_id = wp_create_user( $username, wp_generate_password(), $email );
		update_user_meta($user_id, 'new_user_recording_id', $recording_id);
		ccrecw_add_recording_to_user($user_id, $recording_id);
		if($email_switch == 'yes'){
			wp_new_user_notification( $user_id, null, 'user' ); // need to do this after adding rec to user as that sets up the recording meta that the new user email needs
		}
	}
	// we don't immediately need this user to be set up in Mailster but it would be simpler if they were ...
	// ccpa_write_log('email:'.$email);
	$userdata = array(
        'email' => $email,
    );
	$subscriber = mailster('subscribers')->get_by_mail($email);
	if($subscriber){
		// ccpa_write_log('subscriber id:'.$subscriber->ID);
		// do nothing
	}else{
		// ccpa_write_log('subscriber ebing added');
		// add subscriber
        $overwrite = true;
        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
        if ( is_wp_error( $subscriber_id ) ) {
		    $error_string = $subscriber_id->get_error_message();
		    ccpa_write_log('ccrecw_process_email error when attempting to add subscriber: '.$user_id.' '.$recording_id.' '.$email.' '.$error_string);
		}else{
			// ccpa_write_log('subscriber added:'.$subscriber_id);
		}
	}
}

// change a user/recording closing date/time
add_action('wp_ajax_ccrecw_change_closing_datetime', 'ccrecw_change_closing_datetime');
function ccrecw_change_closing_datetime(){
	$response = array(
		'status' => 'error',
		'date_class' => '',
		'time_class' => '',
		'closed_time' => '',
	);
	$user_id = 0;
	if(isset($_POST['user'])){
		$user_id = $_POST['user'];
	}
	$recording_id = 0;
	if(isset($_POST['recording'])){
		$recording_id = $_POST['recording'];
	}
	$closing_date = '';
	if(isset($_POST['closingDate'])){
		if($_POST['closingDate'] == ''){
			$response['date_class'] = 'success';
		}else{
			$date = date_create_from_format('d/m/Y', $_POST['closingDate']);
			if($date){
				$closing_date = date_format($date, 'Y-m-d');
				$response['date_class'] = 'success';
			}else{
				$response['date_class'] = 'error';
			}
		}
	}
	$closing_time = '';
	if(isset($_POST['closingTime'])){
		if($_POST['closingTime'] == ''){
			$response['time_class'] = 'success';
		}else{
			if($closing_date == ''){
				$time_date = date('Y-m-d');
			}else{
				$time_date = $closing_date;
			}
			$date = date_create_from_format('Y-m-d H:i:s', $time_date.' '.$_POST['closingTime']);
			if($date){
				$closing_time = date_format($date, 'H:i:s');
				$response['time_class'] = 'success';
			}else{
				$response['time_class'] = 'error';
			}
		}
	}
	if($closing_date == '' && $closing_time <> '' || $closing_date <> '' && $closing_time == ''){
		$response['date_class'] = 'error';
		$response['time_class'] = 'error';
	}
	if($response['date_class'] <> 'error' && $response['time_class'] <> 'error'){
		// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
		$recording_meta = get_recording_meta( $user_id, $recording_id );
		if($closing_date == ''){
			$recording_meta['closed_time'] = '';
		}else{
			$recording_meta['closed_time'] = $closing_date.' '.$closing_time;
		}
		$recording_meta['closed_type'] = 'manual';
		update_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta);
		$response['closed_time'] = ccrecw_pretty_meta($recording_meta, 'closed_time');
		$response['status'] = 'ok';
	}
   	echo json_encode($response);
	die();
}

// returns the matching workshop id for a recording
// or NULL if there is none
function recording_get_matching_workshop_id($recording_id){
	global $wpdb;
	$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'workshop_recording' AND meta_value = '$recording_id' LIMIT 1";
	return $wpdb->get_var($sql);
}

// has this user watched this recording
// only need to have started watching it
// returns bool
function recording_watched( $user_id, $recording_id ){
	// $recording_meta = get_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id, true );
	$recording_meta = get_recording_meta( $user_id, $recording_id );
	if( $recording_meta['num_views'] > 0 ) return true;
	return false;
}

