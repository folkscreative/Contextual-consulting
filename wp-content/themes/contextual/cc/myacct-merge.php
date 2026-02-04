<?php
/**
 * My Account Merge Functions and Page
 */

// returns the main page html
function cc_myacct_merge(){
	wp_enqueue_script( 'myacct-merge-scripts' );
	$user_info = wp_get_current_user();
	$html = '<h3 class="d-md-none">Merge accounts</h3><div class="myacct-panel myacct-merge-panel dark-bg"><p>If you have access to training through more than one email address (eg a work email and a home email), you can merge both of these accounts together so that you can see all your training in one place.</p><p><i class="fa-solid fa-triangle-exclamation"></i> This action is irreversible! Do not merge accounts unless you are sure.</p>
			<div id="myacct-merge-form-wrap" class="myacct-merge-form-wrap">
				<h4>Other account</h4>
				<p>What is the email address and password for the other account that you wish to merge with your '.$user_info->user_email.' account?</p>
				<form id="myacct-merge-form" data-type="email">
					<input type="hidden" name="action" value="merge_email">
					<div class="row">
						<div id="email-wrap" class="col-md-8">
							<label class="form-label" for="email">Email</label>
							<input type="email" id="email" name="email" class="form-control form-control-lg" value="">
						</div>
						<div id="password-wrap" class="col-md-4">
							<label class="form-label" for="password">Password</label>
							<input type="password" id="password" name="password" class="form-control form-control-lg" value="">
						</div>
					</div>
				</form>
			</div>
			<div id="myacct-merge-submit-row" class="row">
				<div class="col-4">
					<a href="javascript:void(0);" id="myacct-merge-submit" class="btn btn-myacct btn-sm">Next</a>
				</div>
				<div class="col-8">
					<p id="myacct-merge-message" class="myacct-merge-message"></p>
				</div>
			</div>
		</div>
	';
	return $html;
}

// submission of email and password to merge an account
add_action('wp_ajax_merge_email', 'cc_myacct_merge_email');
add_action('wp_ajax_nopriv_merge_email', 'cc_myacct_merge_email');
function cc_myacct_merge_email(){
	$response = array(
		'status' => 'error',
		'msg' => '',
		'html' => '',
	);
	$user_info = wp_get_current_user();
    $email = '';
    if(isset($_POST['email']) && $_POST['email'] <> ''){
        $email = sanitize_email($_POST['email']);
    }
    $pass = '';
    if(isset($_POST['password']) &&  $_POST['password'] <> ''){
        $pass = sanitize_text_field($_POST['password']);
    }
    if($email == '' || $pass == ''){
    	$response['msg'] = 'Email or password invalid, please try again.';
    }else{
	    // try to find the user
	    $merge_user = cc_users_get_user($email, false);
		if($merge_user === false){
			$response['msg'] = 'Sorry we cannot find that account, please try again.';
		}elseif($merge_user->ID == $user_info->ID){
			$response['msg'] = 'That email address is already in this account. Please select an email from a different account to merge.';
		}else{
			// ok, but does the acct have an alias?
			if(get_user_meta($merge_user->ID, 'user_email_alias_1', true) <> '' || get_user_meta($merge_user->ID, 'user_email_alias_2', true) <> '' || get_user_meta($merge_user->ID, 'user_email_alias_3', true) <> ''){
				$response['msg'] = 'That account is already merged. Please contact us for assistance.';
			}else{
				// email is good, what about the password ...
				if ( ! wp_check_password( $pass, $merge_user->user_pass, $merge_user->ID ) ){
					$response['msg'] = 'Sorry that email or password is wrong, please try again.';
				}else{
					// password is good too ...
					// if the current user is not merged, then we can offer a choice of primary emails
					if(get_user_meta($user_info->ID, 'user_email_alias_1', true) == '' && get_user_meta($user_info->ID, 'user_email_alias_2', true) == '' && get_user_meta($user_info->ID, 'user_email_alias_3', true) == ''){
						$info = $user_info->ID.':'.$merge_user->ID;
						$response['html'] = cc_merge_primary_html($user_info->user_email, $merge_user->user_email, $info);
						$response['status'] = 'ok';
					}else{
						// have we hit the limit on the max number of email aliases?
						if(get_user_meta($user_info->ID, 'user_email_alias_3', true) <> ''){
							$response['msg'] = 'Sorry you have merged the maximum number of accounts. Contact us for assistance.';
						}else{
							$info = $user_info->ID.':'.$merge_user->ID.':'.$user_info->ID;
							$response['html'] = cc_merge_conf_html($user_info->user_email, $merge_user->user_email, $user_info->user_email, $info);
							$response['status'] = 'ok';
						}
					}
				}
			}
		}
	}
   	echo json_encode($response);
	die();
}

// the html to choose the primary account
function cc_merge_primary_html($email_1, $email_2, $info){
	$html = '	<h4>Primary email</h4>
				<p>Ok, we\'ll merge your <strong>'.$email_2.'</strong> account with your <strong>'.$email_1.'</strong> account.</p>
				<p>When you merge these accounts, all future emails will be sent to only one, primary, email address. Which email would you like these sent to?</p>
				<form id="myacct-merge-form" data-type="primary">
					<input type="hidden" name="action" value="merge_primary">
					<input type="hidden" name="info" value="'.$info.'">
					<div class="row">
						<div id="email-wrap" class="col-12">
							<label class="form-label" for="email">Primary email</label>
							<select name="primary" class="form-select form-select-lg">
								<option value="'.$email_1.'">'.$email_1.'</option>
								<option value="'.$email_2.'">'.$email_2.'</option>
							</select>
						</div>
					</div>
				</form>
	';
	return $html;
}

// submission of the chosen primary email
add_action('wp_ajax_merge_primary', 'cc_myacct_merge_primary');
add_action('wp_ajax_nopriv_merge_primary', 'cc_myacct_merge_primary');
function cc_myacct_merge_primary(){
	$response = array(
		'status' => 'error',
		'msg' => '',
		'html' => '',
	);
	$user_info = wp_get_current_user();
    $user_1_id = $user_2_id = 0;
    $primary_email = '';
    if(isset($_POST['info']) && $_POST['info'] <> ''){
    	list($user_1_id, $user_2_id) = explode(":", sanitize_text_field($_POST['info']), 2);
    }
    if(isset($_POST['primary']) && $_POST['primary'] <> ''){
    	$primary_email = sanitize_email($_POST['primary']);
    }
    if($user_1_id == 0 || $user_2_id == 0 || $primary_email == ''){
    	$response['msg'] = 'Information invalid, please try again.';
    }else{
    	if($user_info->user_email == $primary_email){
    		$primary_id = $user_info->ID;
    	}else{
    		$primary_id = $user_2_id;
    	}
		$info = $user_1_id.':'.$user_2_id.':'.$primary_id;
		$merge_user = get_user_by('ID', $user_2_id);
		$response['html'] = cc_merge_conf_html($user_info->user_email, $merge_user->user_email, $primary_email, $info);
		$response['status'] = 'ok';
    }
   	echo json_encode($response);
	die();
}

// the confirmation html
function cc_merge_conf_html($email_1, $email_2, $primary, $info){
	$html = '	<h4>Confirmation</h4>
				<p>We\'ll merge your <strong>'.$email_2.'</strong> account with your <strong>'.$email_1.'</strong> account. We\'ll set <strong>'.$primary.'</strong> as the primary email address. All future emails will be sent to this email address.</p>
				<p>If you\'re happy to proceed, click the "Next" button below and the accounts will be merged.</p>
				<form id="myacct-merge-form" data-type="conf">
					<input type="hidden" name="action" value="merge_conf">
					<input type="hidden" name="info" value="'.$info.'">
				</form>
	';
	return $html;
}

// the "merged it" html
function cc_merge_comp_html($email){
	$html = '	<h4>Success!</h4>
				<p>We\'ve merged your accounts. You can now see all your training by logging in with your <strong>'.$email.'</strong> email. All future emails will be sent to this email address.</p>
	';
	return $html;
}

// the merge!
// Nov 2025 added feedback
add_action('wp_ajax_merge_conf', 'cc_myacct_merge_conf');
add_action('wp_ajax_nopriv_merge_conf', 'cc_myacct_merge_conf');
function cc_myacct_merge_conf(){
	global $wpdb;
	$wkshop_users_table = $wpdb->prefix.'wshops_users';
	$recordings_users_table = $wpdb->prefix.'recordings_users';
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$payments_table = $wpdb->prefix.'ccpa_payments';
	$usermeta_table = $wpdb->prefix.'usermeta';
	$feedback_table = $wpdb->prefix.'feedback';
	$response = array(
		'status' => 'error',
		'msg' => '',
		'html' => '',
	);
    $user_1_id = $user_2_id = $primary_id = 0;
    if(isset($_POST['info']) && $_POST['info'] <> ''){
    	list($user_1_id, $user_2_id, $primary_id) = explode(":", sanitize_text_field($_POST['info']), 3);
    }
    if($user_1_id == 0 || $user_2_id == 0 || $primary_id == 0 || ($primary_id <> $user_1_id && $primary_id <> $user_2_id)){
    	$response['msg'] = 'Information invalid, please try again.';
    }else{
    	$log_message = '<br>'.date('d/m/Y H:i:s').' Merging users: '.$_POST['info'];
    	$user_info = wp_get_current_user();
    	if($primary_id == $user_1_id){
    		$secondary_id = $user_2_id;
    	}else{
    		$secondary_id = $user_1_id;
    	}
    	$primary_user = get_user_by('id', $primary_id);
    	$secondary_user = get_user_by('id', $secondary_id);

    	// copy all the Mailster lists from secondary to primary
    	$sec_subscriber = mailster('subscribers')->get_by_mail($secondary_user->user_email);
    	$sec_subs_list_ids = mailster('subscribers')->get_lists($sec_subscriber->ID, true); // get ids only
    	$log_message .= '<br>Secondary subscriber lists: '.implode(', ', $sec_subs_list_ids);
    	$pri_subscriber = mailster('subscribers')->get_by_mail($primary_user->user_email);
    	$pri_subs_list_ids = mailster('subscribers')->get_lists($pri_subscriber->ID, true); // get ids only
    	$log_message .= '<br>Primary subscriber lists: '.implode(', ', $pri_subs_list_ids);
    	mailster('subscribers')->assign_lists($pri_subscriber->ID, $sec_subs_list_ids);
    	// and remove the lists from the secondary subscriber
    	mailster('subscribers')->unassign_lists( $sec_subscriber->ID );

    	// let's add the email alias to the primary user
    	if(get_user_meta($primary_user->ID, 'user_email_alias_1', true) == ''){
    		update_user_meta( $primary_user->ID, 'user_email_alias_1', $secondary_user->user_email );
    		$log_message .= '<br>Email alias 1 set up on user '.$primary_user->ID;
    	}elseif(get_user_meta($primary_user->ID, 'user_email_alias_2', true) == ''){
    		update_user_meta( $primary_user->ID, 'user_email_alias_2', $secondary_user->user_email );
    		$log_message .= '<br>Email alias 2 set up on user '.$primary_user->ID;
    	}else{
    		update_user_meta( $primary_user->ID, 'user_email_alias_3', $secondary_user->user_email );
    		$log_message .= '<br>Email alias 3 set up on user '.$primary_user->ID;
    	}

    	// now let's switch all workshops across
    	$log_message .= '<br>Moving workshops:';
    	$data = array(
    		'user_id' => $primary_id
    	);
    	$where = array(
    		'user_id' => $secondary_id
    	);
    	$result = $wpdb->update( $wkshop_users_table, $data, $where );
    	$log_message .= '<br>'.$result.' workshops_users rows moved';
    	$result = $wpdb->update( $attendees_table, $data, $where );
    	$log_message .= '<br>'.$result.' attendees rows moved (inc recordings)';
    	// payments are a little messier
    	$sql = "SELECT * FROM $payments_table WHERE type = '' AND ( reg_userid = $secondary_id OR email = '$secondary_user->user_email' )";
    	$payments = $wpdb->get_results($sql, ARRAY_A);
		$data = array(
			'email' => $primary_user->user_email,
			'reg_userid' => $primary_id,
		);
		$count_payments = 0;
    	foreach ($payments as $payment) {
    		$where = array(
    			'id' => $payment['id'],
    		);
    		$wpdb->update( $payments_table, $data, $where );
    		$count_payments ++;
    	}
    	$log_message .= '<br>'.$count_payments.' payments moved';

    	// now let's switch recordings
    	$log_message .= '<br>Moving recordings:';
    	$data = array(
    		'user_id' => $primary_id
    	);
    	$where = array(
    		'user_id' => $secondary_id
    	);
    	$result = $wpdb->update( $recordings_users_table, $data, $where );
    	$log_message .= '<br>'.$result.' recordings_users rows moved';

    	$sql = "SELECT * FROM $payments_table WHERE type = 'recording' AND ( reg_userid = $secondary_id OR email = '$secondary_user->user_email' )";
    	$payments = $wpdb->get_results($sql, ARRAY_A);
		$data = array(
			'email' => $primary_user->user_email,
			'reg_userid' => $primary_id,
		);
		$count_payments = 0;
    	foreach ($payments as $payment) {
    		$where = array(
    			'id' => $payment['id'],
    		);
    		$wpdb->update( $payments_table, $data, $where );
    		$count_payments ++;
    	}
    	$log_message .= '<br>'.$count_payments.' payments moved';

    	$sql = "SELECT * FROM $usermeta_table WHERE user_id = $secondary_id AND meta_key LIKE 'cc_rec_wshop_%'";
    	$usermetas = $wpdb->get_results($sql, ARRAY_A);
    	$count_usermetas = 0;
    	$data = array(
    		'user_id' => $primary_id
    	);
    	foreach ($usermetas as $usermeta) {
    		$where = array(
    			'umeta_id' => $usermeta['umeta_id'],
    		);
    		$wpdb->update( $usermeta_table, $data, $where );
    		$count_usermetas ++;
    	}
    	$log_message .= '<br>'.$count_usermetas.' usermetas moved';

    	// series, group and auto payments
    	$pmt_types = array('series', 'group', 'auto');
    	foreach ($pmt_types as $pmt_type) {
	    	$sql = "SELECT * FROM $payments_table WHERE type = '$pmt_type' AND ( reg_userid = $secondary_id OR email = '$secondary_user->user_email' )";
	    	$payments = $wpdb->get_results($sql, ARRAY_A);
			$data = array(
				'email' => $primary_user->user_email,
				'reg_userid' => $primary_id,
			);
			$count_payments = 0;
	    	foreach ($payments as $payment) {
	    		$where = array(
	    			'id' => $payment['id'],
	    		);
	    		$wpdb->update( $payments_table, $data, $where );
	    		$count_payments ++;
	    	}
	    	$log_message .= '<br>'.$count_payments.' '.$pmt_type.' payments moved';
	    }

	    // Now switch feedbacks
	    $data = array(
	    	'user_id' => $primary_id
	    );
	    $where = array(
	    	'user_id' => $secondary_id
	    );
	    $result = $wpdb->update( $feedback_table, $data, $where );
	    if( $result ){
	    	$log_message .= '<br>'.$result.' feedbacks moved';
	    }

    	// now we need to remove all roles from the secondary user so they cannot login
    	$secondary_user->set_role('');
    	$log_message .= '<br>User '.$secondary_id.' no longer has access to the site';
    	if($primary_id == $user_2_id){
	    	// if they are switching to the user they are not currently logged in as, then we need to log them out too
	    	wp_destroy_current_session();
		    wp_clear_auth_cookie();
		    do_action( 'wp_logout' );
		    $log_message .= '<br>User '.$primary_id.' logged out';
		}else{
			// if they are NOT switching users, then we need to log the other user out ... they could be logged in using another computer
			// update wp_usermeta set meta_value = '' where user_id = {YOUR_USER_ID} and meta_key = 'session_tokens' limit 1;
			update_user_meta($secondary_id, 'session_tokens', '');
		}
		
		$response['status'] = 'done'; // removes next button
		$response['msg'] = 'Accounts successfully merged';
		$response['html'] = cc_merge_comp_html($primary_user->user_email);
		wms_write_log($log_message);
		$curr_log = get_user_meta($primary_id, 'merge_acct_log', true);
		update_user_meta($primary_id, 'merge_acct_log', $curr_log.$log_message);
    }
   	echo json_encode($response);
	die();
}

// complete the logout above
add_action('init', 'cc_myacct_merge_logout');
function cc_myacct_merge_logout(){
	if(isset($_POST['merge_conf'])){
		wms_write_log('logging the user out');
        wp_logout();
        wp_safe_redirect( home_url() ); // custom URL to redirect after logout
        exit();
    }
}

