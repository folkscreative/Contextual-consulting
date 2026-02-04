<?php
/**
 * My Account Details panel
 */

function cc_myacct_details(){
    wp_enqueue_script( 'myacct-details-scripts' );
	$user_info = wp_get_current_user();
    $portal_user = get_user_meta( $user_info->ID, 'portal_user', true);
	$portal_admin = get_user_meta( $user_info->ID, 'portal_admin', true);
    $user_job = get_user_meta( $user_info->ID, 'job', true);
    $bacb_num = get_user_meta( $user_info->ID, 'bacb_num', true);
	$html = '
	<h3 class="d-md-none">My profile</h3>
	<div class="myacct-panel myacct-details-panel dark-bg">
		<form id="myacct-details-form" action="" method="post" novalidate>
			<div class="row">
				<div id="firstname-wrap" class="col-lg-6 firstname-flds">
					<label class="form-label" for="firstname">First name</label>
					<input type="text" id="firstname" name="firstname" class="firstname-flds form-control form-control-lg" value="'.$user_info->first_name.'">
					<div id="firstname-msg" class="firstname-flds form-text invalid-feedback"></div>
				</div>
				<div id="lastname-wrap" class="col-lg-6 lastname-flds">
					<label class="form-label" for="lastname">Last name</label>
					<input type="text" id="lastname" name="lastname" class="lastname-flds form-control form-control-lg" value="'.$user_info->last_name.'">
					<div id="lastname-msg" class="lastname-flds form-text invalid-feedback"></div>
				</div>
			</div>
			<div class="row">
				<div id="email-wrap" class="col-lg-12 email-flds">
					<label class="form-label" for="email">Email</label>
					<input type="email" id="email" name="email" class="email-flds form-control form-control-lg" value="'.$user_info->user_email.'">
					<div id="email-msg" class="email-flds form-text invalid-feedback"></div>
				</div>
			</div>';
	$user_email_alias_1 = get_user_meta($user_info->ID, 'user_email_alias_1', true);
	$user_email_alias_2 = get_user_meta($user_info->ID, 'user_email_alias_2', true);
	$user_email_alias_3 = get_user_meta($user_info->ID, 'user_email_alias_3', true);
	if($user_email_alias_1 <> '' || $user_email_alias_2 <> '' || $user_email_alias_3 <> ''){
		$sec_emails = '';
		for ($i=1; $i < 4; $i++) { 
			if(${"user_email_alias_$i"} <> ''){
				if($sec_emails <> ''){
					$sec_emails .= ', ';
				}
				$sec_emails .= ${"user_email_alias_$i"};
			}
		}
		$html .= '
			<div class="row">
				<div id="" class="col-lg-12">
					<label class="form-label">Secondary Email (merged account):</label>
					<input type="text" class="sec-emails form-control form-control-lg" disabled value="'.$sec_emails.'">
				</div>
			</div>';
	}
	$html .= '
			<div class="row">
				<div id="phone-wrap" class="col-lg-6">
					<label class="form-label" for="phone">Phone</label>
					<input type="text" id="phone" name="phone" class="form-control form-control-lg" value="'.$user_info->phone.'">
					<div class="form-text invalid-feedback"></div>
				</div>
				<div id="job-wrap" class="col-lg-6">
					<label for="job" class="form-label">Job title</label>
					<input type="text" id="job" name="job" class="form-control form-control-lg" autocomplete="off" value="'.professions_pretty( $user_job ).'" required placeholder="Start typing your job title - select from the list if possible">
					<div id="job-msg" class="job-flds form-text invalid-feedback"></div>
					<input type="hidden" id="job_id" name="job_id" value="">
					<div id="job-message" style="display: none; font-size: 0.9rem; color: #ffff00; line-height:1.2;">
						Tip: If your job title appears in the list, please select it. If not, just keep typing.
					</div>
				</div>
			</div>';
				/*
				<div id="job-wrap" class="col-lg-6 job-flds">
					<label for="job" class="form-label">Job title</label>
					<select name="job" id="job" class="form-select form-select-lg">
						<option value="">Please select ...</option>';
	if($portal_user <> ''){
		$html .= professions_options( $portal_user, $user_job );
	}else{
		$html .= professions_options('std', $user_job);
	}
	$html .= '		</select>
					<div id="job-msg" class="job-flds form-text invalid-feedback"></div>';
	if( $portal_user == 'nlft' ){
		if( professions_other( 'nlft', $user_job ) ){
			$html .= '
					<!-- Non-hidden text field for "Other" -->
				    <div id="other-job-wrap" class="mb-3">
				        <label for="other-job" class="form-label">Please specify your job title</label>
				        <input type="text" id="other-job" name="other-job" class="form-control form-control-lg" value="'.$user_job.'">
				    </div>
					<div id="other-job-msg" class="job-flds form-text invalid-feedback"></div>';
		}else{
			$html .= '
					<!-- Hidden text field for "Other" -->
				    <div id="other-job-wrap" class="mb-3" style="display: none;">
				        <label for="other-job" class="form-label">Please specify your job title</label>
				        <input type="text" id="other-job" name="other-job" class="form-control form-control-lg">
				    </div>
					<div id="other-job-msg" class="job-flds form-text invalid-feedback"></div>';
		}
	}
	*/
	$html .= '
			<div class="row">
				<div id="bacb-wrap" class="col-lg-6">
					<label for="bacb_num" class="form-label">Participant BACB certification number</label>
					<input type="text" id="bacb_num" name="bacb_num" class="form-control form-control-lg" value="'.$bacb_num.'">
				</div>
			</div>';
	if( $portal_user == '' || $portal_admin == 'yes' ){
		$html .= '
			<div class="row">
				<div id="pswrd1-wrap" class="col-lg-6">
					<label class="form-label" for="pswrd1">New password</label>
					<input type="password" id="pswrd1" name="pswrd1" value="" class="pswrd-flds form-control form-control-lg">
					<div id="pswrd1-msg" class="pswrd-msg"></div>
				</div>
				<div id="pswrd2-wrap" class="col-lg-6">
					<label class="form-label" for="pswrd2">Retype new password</label>
					<input type="password" id="pswrd2" name="pswrd2" value="" class="pswrd-flds form-control form-control-lg">
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<p>If you change your password, you will need to choose a password that is "good" or "strong". The best passwords are long and/or completely random strings of letters (including some uppercase), numbers and symbols. Please do not re-use passwords.</p>
				</div>
			</div>';
	}
	$html .= '
			<div class="row mb-3">
				<div id="timezone-wrap" class="col-lg-12">
					<p>Training times are being shown in <span class="user-timezone">';
						$user_timezone = cc_timezone_get_user_timezone($user_info->ID);
						$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_info->ID, $user_timezone);
						$html .= $pretty_timezone.'</span> time (where it is currently <span id="current-time" class="current-time">';
						$date = new DateTime("now", new DateTimeZone($user_timezone) );
						$html .= $date->format('jS M Y H:i:s').'</span>). <a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="0">change timezone</a></p>
						<input type="hidden" id="user-timezone" value="'.$user_timezone.'">
						<input type="hidden" id="user-prettytime" value="'.$pretty_timezone.'">';
	/*					
					<label class="form-label" for="timezone">Show workshop times in this timezone:</label>
					<select id="timezone" name="timezone" class="form-select form-select-lg">';
	$user_timezone = cc_timezone_get_user_timezone($user_info->ID);
	$html .= cc_timezone_select_options($user_timezone);
    $html .= '		</select>
					<div class="form-text"></div>
	*/
	$html .= '	</div>
			</div>
			<div class="row align-items-center">
				<div id="submit-wrap" class="col-md-6 col-lg-3">
					<button type="submit" id="submit" class="btn btn-lg btn-myacct btn-sm" name="submit">Update</button>
				</div>
				<div id="myacct-details-msg-wrap" class="col-md-6 col-lg-9">
					<div id="myacct-details-msg" class="myacct-details-msg"></div>
				</div>
			</div>
		</form>
	</div>
	';
	return $html;
}


// email lookup on my account details page
add_action('wp_ajax_myacct_email_lookup', 'myacct_email_lookup');
add_action('wp_ajax_nopriv_myacct_email_lookup', 'myacct_email_lookup');
function myacct_email_lookup(){
	$response = array(
		'status' => 'unregistered',
	);
	$current_email = '';
	if(cc_users_is_valid_user_logged_in()){
		$current_user = wp_get_current_user();
		$current_email = $current_user->user_email;
	}
	if(isset($_POST['email']) && $_POST['email'] <> ''){
		$email = sanitize_email($_POST['email']);
		if($email == $current_email){
			$response['status'] = 'unchanged';
		}else{
			$user = get_user_by('email', $email);
			if($user){
				$response['status'] = 'registered';
			}else{
				// WP users are good. How about Mailster?
				$subscriber = mailster('subscribers')->get_by_mail($email);
				if($subscriber){
					$response['status'] = 'subscribed';
				}else{
					// all good, email not used
				}
			}
		}
	}else{
		$response['status'] = 'error';
	}
   	echo json_encode($response);
	die();
}

// details update
add_action('wp_ajax_myacct_details_update', 'myacct_details_update');
add_action('wp_ajax_nopriv_myacct_details_update', 'myacct_details_update');
function myacct_details_update(){
	// ccpa_write_log('myacct_details_update');
	$response = array(
		'status' => 'error',
	);
	if(!cc_users_is_valid_user_logged_in()){
		$response['msg'] = 'Your session has expired. Please login again to continue.';
	}else{
		$user_info = wp_get_current_user();
		$subscriber = mailster('subscribers')->get_by_mail($user_info->user_email);
		if($subscriber){
			$subscriber_id = $subscriber->ID;
		}else{
			// add subscriber
			$userdata = array(
	            'email' => $user_info->user_email,
	        );
	        $overwrite = true;
	        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
		}
		$flds_updated = array();

		$mailster_update = array(
			'ID' => $subscriber_id,
			'email' => $user_info->user_email,
			'firstname' => $user_info->first_name,
			'lastname' => $user_info->last_name,
		);
		$payment_data_update = array(
			'email' => $user_info->user_email,
			'firstname' => $user_info->first_name,
			'lastname' => $user_info->last_name,
			'phone' => $user_info->phone,
		);

		if(isset($_POST['firstname']) && $_POST['firstname'] <> ''){
			$new_value = stripslashes(sanitize_text_field($_POST['firstname']));
			$current_value = $user_info->first_name;
			if($new_value <> $current_value){
				$args = array(
					'ID' => $user_info->ID,
					'first_name' => $new_value,
				);
				wp_update_user($args);
				$flds_updated[] = 'firstname';
				$mailster_update['firstname'] = $new_value;
				$payment_data_update['firstname'] = $new_value;
			}
		}

		if(isset($_POST['lastname']) && $_POST['lastname'] <> ''){
			$new_value = stripslashes(sanitize_text_field($_POST['lastname']));
			$current_value = $user_info->last_name;
			if($new_value <> $current_value){
				$args = array(
					'ID' => $user_info->ID,
					'last_name' => $new_value,
				);
				wp_update_user($args);
				$flds_updated[] = 'lastname';
				$mailster_update['lastname'] = $new_value;
				$payment_data_update['lastname'] = $new_value;
			}
		}

		if(isset($_POST['email']) && $_POST['email'] <> ''){
			$new_value = strtolower( stripslashes(sanitize_email($_POST['email'])) );
			$current_value = $user_info->user_email;
			if($new_value <> $current_value && $new_value <> ''){
				$args = array(
					'ID' => $user_info->ID,
					'user_email' => $new_value,
				);
				wp_update_user($args);
				$flds_updated[] = 'email';
				$mailster_update['email'] = $new_value;
				$payment_data_update['email'] = $new_value;
			}
		}

		if(isset($_POST['phone']) && $_POST['phone'] <> ''){
			$new_value = stripslashes(sanitize_text_field($_POST['phone']));
			$current_value = get_user_meta($user_info->ID, 'phone', true);
			if($new_value <> $current_value){
				update_user_meta($user_info->ID, 'phone', $new_value);
				$flds_updated[] = 'phone';
				$payment_data_update['phone'] = $new_value;
			}
		}

		if(isset($_POST['job']) && $_POST['job'] <> ''){
			$new_value = stripslashes(sanitize_text_field($_POST['job']));
			if( isset( $_POST['jobId'] ) && $_POST['jobId'] <> '' ){
				$new_value = stripslashes(sanitize_text_field($_POST['jobId']));
			}
			$current_value = get_user_meta($user_info->ID, 'job', true);
			if($new_value <> $current_value){
				update_user_meta($user_info->ID, 'job', $new_value);
				$flds_updated[] = 'job';
				$payment_data_update['job'] = $new_value;
			}
		}

		if( isset( $_POST['bacb_num'] ) && $_POST['bacb_num'] <> '' ){
			$new_value = stripslashes( sanitize_text_field( $_POST['bacb_num'] ) );
			$current_value = get_user_meta( $user_info->ID, 'bacb_num', true );
			if( $new_value <> $current_value ){
				update_user_meta( $user_info->ID, 'bacb_num', $new_value );
				$flds_updated[] = 'bacb_num';
			}
		}

		if(isset($_POST['pswrd']) && $_POST['pswrd'] <> ''){
			$new_value = $_POST['pswrd']; // do not sanitise this as it may remove required chars
			$args = array(
				'ID' => $user_info->ID,
				'user_pass' => $new_value,
			);
			wp_update_user($args);
			$flds_updated[] = 'pswrd';
		}

		// ccpa_write_log('myacct_details_update user data updated');

		/*
		if(isset($_POST['timezone']) && $_POST['timezone'] <> ''){
			// $new_value = $_POST['timezone'] + 0;
			$new_value = stripslashes(sanitize_text_field($_POST['timezone']));
			// if(cc_timezone_name($new_value) !== NULL){
			if(in_array($new_value, timezone_identifiers_list())){
				// found in the list
				$current_value = get_user_meta($user_info->ID, 'workshop_timezone', true);
				if($new_value <> $current_value){
					update_user_meta($user_info->ID, 'workshop_timezone', $new_value);
					$flds_updated[] = 'timezone';
				}
			}
		}
		*/
		if(!empty($flds_updated)){
			cc_debug_log_anything('myacct_details_update');
			cc_debug_log_anything($flds_updated);
			cc_debug_log_anything($user_info);
			cc_debug_log_anything($_POST);
		}

		// do we also need to update Mailster?
		if(count(array_intersect(array('firstname', 'lastname', 'email'), $flds_updated)) > 0){
			// at least one relevant field has changed
			$result = mailster('subscribers')->update( $mailster_update );
		}

		// ccpa_write_log('myacct_details_update mailster updated');

		// we'll also update all the payment records with the new details
		if(count(array_intersect(array('firstname', 'lastname', 'email', 'phone'), $flds_updated)) > 0){
			// first, workshops ...
		    $wkshop_users = cc_myacct_get_workshops($user_info->ID);
		    foreach ($wkshop_users as $wkshop_user) {
		    	if($wkshop_user['payment_id'] > 0){
			    	$payment_data = cc_paymentdb_get_payment($wkshop_user['payment_id']);
			    	if($payment_data !== NULL){
				    	if($wkshop_user['reg_attend'] == 'r'){
				    		if($payment_data['attendee_email'] == $payment_data['email']){
				    			$payment_data['attendee_email'] = '';
				    			$payment_data['attendee_firstname'] = '';
				    			$payment_data['attendee_lastname'] = '';
				    		}
				    		$payment_data['firstname'] = $payment_data_update['firstname'];
				    		$payment_data['lastname'] = $payment_data_update['lastname'];
				    		$payment_data['email'] = $payment_data_update['email'];
				    		$payment_data['phone'] = $payment_data_update['phone'];
				    	}else{
				    		$payment_data['attendee_firstname'] = $payment_data_update['firstname'];
				    		$payment_data['attendee_lastname'] = $payment_data_update['lastname'];
				    		$payment_data['attendee_email'] = $payment_data_update['email'];
				    	}
				    	$result = cc_paymentdb_update_payment($payment_data);
				    }
			    }
		    }
		    // ccpa_write_log('myacct_details_update workshop payment updated');
		    // now recordings
		    $user_meta = get_user_meta($user_info->ID);
		    foreach ($user_meta as $key => $meta) {
		    	// if(strpos($key, 'cc_rec_wshop_') === 0){
		    	if( preg_match( '/^cc_rec_wshop_\d+$/', $key ) ){
		    		// it's for a recording (not a module)
		    		$recording_meta = maybe_unserialize($meta[0]);
		    		$recording_id = substr($key, 13);
		    		$recording_meta = sanitise_recording_meta( $recording_meta, $user_info->ID, $recording_id );
		    		// echo '#### payment_id='.$recording_meta['payment_id'].'####';
		    		$payment_data = cc_paymentdb_get_payment($recording_meta['payment_id']);
		    		if($payment_data !== NULL){
			    		$payment_data['firstname'] = $payment_data_update['firstname'];
			    		$payment_data['lastname'] = $payment_data_update['lastname'];
			    		$payment_data['email'] = $payment_data_update['email'];
			    		$payment_data['phone'] = $payment_data_update['phone'];
			    		$result = cc_paymentdb_update_payment($payment_data);
			    	}
		    	}
		    }
		    // ccpa_write_log('myacct_details_update recording payments updated');
		}

		$response['fields'] = implode(' ', $flds_updated);

		if(count($flds_updated) > 0){
			$response['msg'] = 'Updated.';
			$response['status'] = 'ok';
		}else{
			$response['msg'] = 'Nothing updated';
			$response['status'] = 'ok';
		}
	}
	// ccpa_write_log('myacct_details_update done');
	// ccpa_write_log($response);
    echo json_encode($response);
    die();
}

// if a WP user is updated, we need to copy that through the payments records
// hook fires immediately after an existing user is updated.
// sometimes nothing will have changed!
// You should be able to get the new user data from the $_POST array
// for some reason the new user data returned from get_userdata is incomplete (eg no first_name, last_name). I wonder whether this is because the hook is early in the process and these things have not been assembled yet. We can get this data from $_POST data or from get_user_meta.
add_action('profile_update', 'cc_myacct_profile_update', 10, 2);
function cc_myacct_profile_update($user_id, $old_user_data){
    global $wpdb;
    $payments_table = $wpdb->prefix.'ccpa_payments';
	// ccpa_write_log('cc_myacct_profile_update $old_user_data=');
	// ccpa_write_log($old_user_data);
	/* typically ...
		WP_User Object (
		    [data] => stdClass Object (
		            [ID] => 132
		            [user_login] => test@roderickpughmarketing.com
		            [user_pass] => $P$BScdMW.5nZyhmMd73I8bPaLXLiDylS1
		            [user_nicename] => testroderickpughmarketing-com
		            [user_email] => test4444@roderickpughmarketing.com
		            [user_url] => 
		            [user_registered] => 2021-07-13 11:33:27
		            [user_activation_key] => 
		            [user_status] => 0
		            [display_name] => test@roderickpughmarketing.com
		        )
		    [ID] => 132
		    [caps] => Array (
		            [subscriber] => 1
		        )
		    [cap_key] => wp_capabilities
		    [roles] => Array (
		            [0] => subscriber
		        )
		    [allcaps] => Array (
		            [read] => 1
		            [level_0] => 1
		            [subscriber] => 1
		        )
		    [filter] => 
		    [site_id:WP_User:private] => 1
		)
	*/
	// ccpa_write_log('post data ...');
	// ccpa_write_log($_POST);
	/* typically .......
		but sometimes it's firstname and lastname!!!
		Array (
		    [_wpnonce] => 9e9dd2d308
		    [_wp_http_referer] => /wp-admin/user-edit.php?user_id=132&wp_http_referer=%2Fwp-admin%2Fusers.php
		    [wp_http_referer] => /wp-admin/users.php
		    [from] => profile
		    [checkuser_id] => 1
		    [color-nonce] => 6e3599ea04
		    [admin_color] => fresh
		    [admin_bar_front] => 1
		    [locale] => site-default
		    [role] => subscriber
		    [first_name] => Billiam
		    [last_name] => Smythers
		    [nickname] => test@roderickpughmarketing.com
		    [display_name] => test@roderickpughmarketing.com
		    [email] => test4444@roderickpughmarketing.com
		    [url] => 
		    [description] => 
		    [pass1] => 
		    [pass2] => 
		    [title] => 
		    [phone] => 01554 775 737
		    [vat] => y
		    [maillist] => n
		    [pmtmethod] => online
		    [invaddr] => 
		    [invemail] => 
		    [invphone] => 
		    [invref] => 
		    [currency] => AUD
		    [amount] => 50
		    [clientSecret] => pi_1JCjqtCbNJKjn0bnQL1VQ1Vf_secret_KrlyfcfMMv0CUxeeCDOyXFNFJ
		    [voucher] => 
		    [discAmount] => 0
		    [action] => update
		    [user_id] => 132
		    [submit] => Update User
		)
	*/
	$old_user_email = $old_user_data->data->user_email;
	$new_user_email = $_POST['email'];
	// ccpa_write_log('old email:'.$old_user_email.' new email:'.$new_user_email);
	ccpa_write_log('cc_myacct_profile_update userid:'.$user_id.' old email:'.$old_user_email.' new email:'.$new_user_email);
	if(!isset($_POST['email']) || $new_user_email == '' || $new_user_email == $old_user_email){
		ccpa_write_log('aborting payment data and subscriber update');
		return;
	}

	// we cannot see the old first or last name so have to assume that it has changed
	// first let's update payment records
	// we look for all payment records that use the old email address
	// emails can include apostrophes, quotes, etc so switched to prepare the query first
    // $sql = "SELECT * FROM $payments_table WHERE email = '$old_user_email' OR attendee_email = '$old_user_email'";
    $sql = $wpdb->prepare(
    	"SELECT * FROM `$payments_table` WHERE `email` = %s OR `attendee_email` = %s",
    	array(
    		$old_user_email,
    		$old_user_email
    	)
    );
    $payments = $wpdb->get_results($sql, ARRAY_A);
	if(isset($_POST['first_name'])){
		$first_name = $_POST['first_name'];
	}elseif(isset($_POST['firstname'])){
		$first_name = $_POST['firstname'];
	}else{
		$first_name = false;
	}
	if(isset($_POST['last_name'])){
		$last_name = $_POST['last_name'];
	}elseif(isset($_POST['lastname'])){
		$last_name = $_POST['lastname'];
	}else{
		$last_name = false;
	}
    foreach ($payments as $payment) {
	    $updates = array();
	    if($payment['email'] == $old_user_email){
	    	$updates['email'] = $new_user_email;
	    	if($first_name){
	    		$updates['firstname'] = $first_name;
	    	}
	    	if($last_name){
	    		$updates['lastname'] = $last_name;
	    	}
	    	if(isset($_POST['phone'])){
	    		$updates['phone'] = $_POST['phone'];
	    	}
	    }
	    if($payment['attendee_email'] == $old_user_email){
	    	$updates['attendee_email'] = $new_user_email;
	    	if($first_name){
	    		$updates['attendee_firstname'] = $first_name;
	    	}
	    	if($last_name){
	    		$updates['attendee_lastname'] = $last_name;
	    	}
	    }
        $where = array(
        	'id' => $payment['id']
        );
		// ccpa_write_log('updates ...');
		// ccpa_write_log($updates);
        if(!empty($updates)){
	        $wpdb->update( $payments_table, $updates, $where );
	    }
    }
	// then we'll update mailster subscribers
	$subscriber = mailster('subscribers')->get_by_mail($old_user_email);
	if($subscriber){
		$userdata = array(
			'ID' => $subscriber->ID,
	        'email' => $new_user_email,
	    );
    	if($first_name){
    		$userdata['firstname'] = $first_name;
    	}
    	if($last_name){
    		$userdata['lastname'] = $last_name;
    	}
		$result = mailster('subscribers')->update( $userdata );
	}
}

// Fires immediately after a user is deleted from the database.
// removes a user from the workshops users and recordings users tables
add_action('deleted_user', 'cc_myacct_deleted_user');
function cc_myacct_deleted_user($user_id){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    $where = array(
    	'user_id' => $user_id
    );
    $wpdb->delete( $wkshop_users_table, $where );
    $wpdb->delete( $recordings_users_table, $where );
}

// returns default fields for payment forms
function cc_myacct_default_payment_fields(){
	$defaults = array(
		'user_id' => '',
		'firstname' => '',
		'lastname' => '',
		'email' => '',
		'phone' => '',
	);
	$user = wp_get_current_user();
	if($user->exists()){
		$defaults['user_id'] = $user->ID;
		$defaults['firstname'] = $user->first_name;
		$defaults['lastname'] = $user->last_name;
		$defaults['email'] = $user->user_email;
		$defaults['phone'] = get_user_meta( $user->ID, 'phone', true ); 
	}
	return $defaults;
}

// returns the my-account url
// includes the email address where possible
function cc_myacct_login_url($email=''){
	if($email == ''){
		$url = site_url().'/member-login';
	}else{
		$url = add_query_arg('login', $email, site_url().'/member-login');
	}
	return esc_url( $url );
}

// returns the login link to set a password
function cc_myacct_login_url_set_password($payment_data, $reg_attend='r'){
	$user_id = cc_myacct_get_user($payment_data, $reg_attend);
	$user = get_user_by('id', $user_id);
	$key = get_password_reset_key( $user );
	return '<a href="'.network_site_url("wp-login.php?action=rp&key=$key&login=".rawurlencode( $user->user_email ), 'login' ).'">Set password</a>';
}

// change all the mailster {profile} shortlinks to be {my_account} shortlinks
// and remove {webversion} while we're at it
add_shortcode('change_profile_to_my_account', 'rpm_change_profile_to_my_account');
function rpm_change_profile_to_my_account(){
	$html = 'change_profile_to_my_account ';
	// not post_status of finished
	$args = array(
		'post_type' => 'newsletter',
		'posts_per_page' => -1,
		'post_status' => array('autoresponder', 'auto-draft', 'draft', 'paused'),
	);
	$newsletters = get_posts($args);
	$upd_count = 0;
	foreach ($newsletters as $nltr) {
		$html .= '<br>'.$nltr->ID.' status:'.$nltr->post_status;
		$post_content = str_replace('{profile}', '{my_account}', $nltr->post_content);
		$post_content = str_replace('{webversion} | ', '', $post_content);
		$post_excerpt = str_replace('{profile}', '{my_account}', $nltr->post_excerpt);
		$post_excerpt = str_replace('{webversion} | ', '', $post_excerpt);
		$updates = array(
			'ID' => $nltr->ID,
			'post_content' => $post_content,
			'post_excerpt' => $post_excerpt,
		);
		wp_update_post( $updates );
		$upd_count ++;
		/*
		if($upd_count > 1){
			break;
		}
		*/
	}
	return $html;
}