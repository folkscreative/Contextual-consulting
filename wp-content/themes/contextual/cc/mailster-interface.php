<?php
/**
 * Mailster Interface things
 * - also see sys-control for core functions (cron etc)
 */

// get the newsletter list id
function ccmac_news_list_id(){
	static $news_list_id = 0;
	if($news_list_id > 0){
		return $news_list_id;
	}
	$lists = mailster( 'lists' )->get();
	foreach ($lists as $list){
    	if($list->name == 'Contextual Consulting Events'){
    		$news_list_id = $list->ID;
    		return $news_list_id;
    	}
	}
    if($news_list_id == 0){
    	$news_list_id = mailster('lists')->add('Contextual Consulting Events');
    }
    return $news_list_id;
}


// is the user on the newsletter list
// returns true if on the list
function cc_mailsterint_on_newsletter($email){
	global $wpdb;
	if($email == '') return false;
	$subscriber = mailster('subscribers')->get_by_mail($email);
	if(!$subscriber) return false;
    $subs_lists = mailster('subscribers')->get_lists( $subscriber->ID, true ); // true = ids only
    $news_list_id = ccmac_news_list_id();
    if(in_array($news_list_id, $subs_lists)){
    	return true;
    }
    return false;
}

// is the subscriber on a specific list
// returns true if on the list
function cc_mailsterint_subs_on_list( $subscriber_id, $list_id ){
    $subs_lists = mailster('subscribers')->get_lists( $subscriber_id, true ); // true = ids only
    if( in_array( $list_id, $subs_lists ) ){
    	return true;
    }
    return false;
}

// add a user to the newsletter
function cc_mailsterint_newsletter_subscribe($user){
	// ccpa_write_log('function cc_mailsterint_newsletter_subscribe');
	// ccpa_write_log('user ...');
	// ccpa_write_log($user);
	// is the user set as a subscriber?
	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	// ccpa_write_log('subscriber ...');
	// ccpa_write_log($subscriber);
	if($subscriber){
		// ccpa_write_log('found');
		$subscriber_id = $subscriber->ID;
	}else{
		// ccpa_write_log('not found');
		// add to subscribers
		$userdata = array(
            'email' => $user->user_email,
            'firstname' => $user->user_firstname,
            'lastname' => $user->user_lastname,
        );
        $subscriber_id = mailster('subscribers')->add($userdata, true);
	}
	// ccpa_write_log('subscriber_id ...');
	// ccpa_write_log($subscriber_id);
	mailster('subscribers')->assign_lists($subscriber_id, ccmac_news_list_id());
	// ccpa_write_log('cc_mailsterint_newsletter_subscribe done');
}

// update subscriber name (if subscriber exists)
function cc_mailsterint_update_subs_name($user){
	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	if($subscriber){
		$subscriber->firstname = $user->user_firstname;
		$subscriber->lastname = $user->user_lastname;
		mailster('subscribers')->update($subscriber);
	}
}

// returns a list id for the reg or news lists
// type must be 'reg' or 'news'
function cc_mailster_regnews_list_id($type='reg'){
	$list_id = get_option('cc_mailster_list_id_'.$type);
	if(!$list_id){
		if($type == 'reg'){
			$list_id = cc_mailster_list_id_by_name('Registration');
		}else{
			$list_id = cc_mailster_list_id_by_name('Contextual Consulting Events');
		}
		update_option('cc_mailster_list_id_'.$type, $list_id);
	}
	return $list_id;
}

// returns a list id for a workshop/recording (inc event if non zero)
// bug fixed Feb 2023 to add 'Recording: ' on to the front of recording lists
function cc_mailster_training_list_id($training_id, $event_id=0){
	if($event_id > 0){
		$meta_key = 'mailster_event_list_'.$event_id;
	}else{
		$meta_key = 'mailster_list';
	}
	$list_id = get_post_meta($training_id, $meta_key, true);
	if($list_id == ''){
		$list_name = $training_id.': '.get_the_title($training_id);
		if( course_training_type($training_id) == 'recording' ){
			$list_name = 'Recording: '.$list_name;
		}
		if($event_id > 0){
			$event_name = get_post_meta( $training_id, 'event_'.$event_id.'_name', true );
			if($event_name == ''){
				$event_name = 'Event '.$event_id;
			}
			$list_name .= ': '.$event_name;
		}
		$list_id = cc_mailster_list_id_by_name($list_name);
		update_post_meta($training_id, $meta_key, $list_id);
	}
	return $list_id;
}

// returns a list id based on its name
// creates it if not found
function cc_mailster_list_id_by_name($list_name){
	$sanitised_list_name = sanitize_title($list_name);
	$lists = mailster( 'lists' )->get();
	$list_id = 0;
	foreach ($lists as $list) {
    	if($list->slug == $sanitised_list_name){
    		$list_id = $list->ID;
    		break;
    	}
	}
	if($list_id == 0){
		$list_id = mailster('lists')->add($list_name);
	}
	return $list_id;
}

// new (Nov 2024) registration email
// adds all people to the lists and sends the registration and attendee emails
function cc_mailsterint_send_reg_emails( $payment, $resend=false ){
	// is payment just an ID or a proper payment record?
	if( ! is_array( $payment ) ){
		$payment = cc_paymentdb_get_payment( $payment );
	}
	// first, the registrant (who may also be an attendee)
	if( $resend ){
		$update_subs = false;
	}else{
		$update_subs = true;
	}

	// ccpa_write_log('function cc_mailsterint_send_reg_emails');
	// ccpa_write_log($payment);

	// add registrant to reg list and send the registration email
	$subscriber_id = cc_mailster_get_payment_subscriber_id( $payment, $update_subs );
	if( ! $resend ){
		$reg_list_id = cc_mailster_regnews_list_id( 'reg' );
        mailster('subscribers')->assign_lists($subscriber_id, $reg_list_id);
	}
	// ccpa_write_log('subs_id: '.$subscriber_id);
	$mailster_tags = array(
		'firstname' => $payment["firstname"],
		'registration_details' => ccpa_mailster_tag_registration_details( $payment ),
	);
	// ccpa_write_log($mailster_tags);
	sysctrl_mailster_ar_hook( 'registration_email', $subscriber_id, $mailster_tags );

	// now attendees (if not a resend)
	if( ! $resend ){
		cc_mailster_add_attendees_to_training( $payment ); // and send attendee emails
		/*
		$attendees = cc_attendees_for_payment( $payment['id'] );
		foreach ( $attendees as $attendee ) {
			// put all attendees (inc registrant if also an attendee) onto the mailster list(s) for the training
			$user = get_user_by( 'ID', $attendee['user_id'] );
			$subscriber_id = cc_mailster_get_subscriber_id( $user->user_email, $user->user_firstname, $user->user_lastname, true );
			// we will no longer put attendees on the registration list (registrants have just been added above)
			// we're also not putting them on the news list (we never had ... but we always could)
			$wanted_lists = array();
	    	$wanted_lists[] = cc_mailster_training_list_id( $payment['workshop_id'] );
	    	if( $payment['type'] == '' && workshop_is_multi_event( $payment['workshop_id'] ) && $payment['event_ids'] <> '' ){
				$event_ids = explode( ',', $payment['event_ids'] );
				foreach ( $event_ids as $event_id ) {
					if( $event_id <> '' ){
						$wanted_lists[] = cc_mailster_training_list_id( $payment['workshop_id'], $event_id );
					}
				}
	    	}
	    	if( $payment['upsell_workshop_id'] > 0 ){
	    		$wanted_lists[] = cc_mailster_training_list_id( $payment['upsell_workshop_id'] );
	    	}
			mailster( 'subscribers' )->assign_lists( $subscriber_id, $wanted_lists );
			
			// now we send the attendee email (but not to the registrant as they have the details in the reg email)
			if( $attendee['registrant'] <> 'r' ){
				$mailster_tags = array(
					'firstname' => $user->user_firstname,
					'registration_details' => ccpa_mailster_tag_attendee_reg_dets( $payment, $attendee['user_id'] ),
				);
				sysctrl_mailster_ar_hook( 'registration_attendee_email', $subscriber_id, $mailster_tags );
			}
		}
		*/
	}
	return true;
}

// adds attendees to the training lists and sends the attendee emails
function cc_mailster_add_attendees_to_training( $payment, $send_attendee_emails=true ){
	$attendees = cc_attendees_for_payment( $payment['id'] );
	foreach ( $attendees as $attendee ) {
		// put all attendees (inc registrant if also an attendee) onto the mailster list(s) for the training
		$user = get_user_by( 'ID', $attendee['user_id'] );
		if( $user ){
			$subscriber_id = cc_mailster_get_subscriber_id( $user->user_email, $user->user_firstname, $user->user_lastname, true );
			if( ! is_wp_error( $subscriber_id ) ){

				// we will no longer put attendees on the registration list (registrants have just been added above)
				// we're also not putting them on the news list (we never had ... but we always could)
				$wanted_lists = array();

		    	// Handle group registrations
		    	// note that group reg people do not get added to the series list
		    	if( $payment['type'] == 'group' ){
		    		// Get all courses in the group
		    		$group_courses = cc_training_groups_get_courses( $payment['id'] );
		    		foreach ( $group_courses as $course_id ) {
		    			$wanted_lists[] = cc_mailster_training_list_id( $course_id );
		    		}
		    	} else {
		    		// the workshop/recording/series
			    	$wanted_lists[] = cc_mailster_training_list_id( $payment['workshop_id'] );
			    	
			    	// add events?
			    	if( $payment['type'] == '' && workshop_is_multi_event( $payment['workshop_id'] ) && $payment['event_ids'] <> '' ){
						$event_ids = explode( ',', $payment['event_ids'] );
						foreach ( $event_ids as $event_id ) {
							if( $event_id <> '' ){
								$wanted_lists[] = cc_mailster_training_list_id( $payment['workshop_id'], $event_id );
							}
						}
			    	}
			    	
			    	// add upsell?
			    	if( $payment['upsell_workshop_id'] > 0 ){
			    		$wanted_lists[] = cc_mailster_training_list_id( $payment['upsell_workshop_id'] );
			    	}
			    	
			    	// and series courses
			    	if( $payment['type'] == 'series' ){
					    // get all the training ids for the series
					    $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true);
					    $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
					    $series_courses = is_array($series_courses) ? $series_courses : [];
					    foreach ($series_courses as $course_id) {
					    	$wanted_lists[] = cc_mailster_training_list_id( $course_id );
					    }
			    	}
		    	}

		    	// ccpa_write_log('function cc_mailster_add_attendees_to_training');
		    	// ccpa_write_log('payment:'.$payment['id']);
		    	// ccpa_write_log('user:'.$attendee['user_id']);
		    	// ccpa_write_log('subscriber:'.$subscriber_id);

				mailster( 'subscribers' )->assign_lists( $subscriber_id, $wanted_lists );

				// send attendee emails, only send to attendees that are not also registrants
				if( $send_attendee_emails && $attendee['registrant'] <> 'r' ){
					$mailster_tags = array(
						'firstname' => $user->user_firstname,
						'registration_details' => ccpa_mailster_tag_attendee_reg_dets( $payment, $attendee['user_id'] ),
					);
					sysctrl_mailster_ar_hook( 'registration_attendee_email', $subscriber_id, $mailster_tags );
				}
			}else{
				ccpa_write_log( 'function cc_mailster_add_attendees_to_training could not find subscriber ID for user '.$attendee['user_id'].' for payment '.$payment['id'] );
			}
		}else{
			ccpa_write_log( 'function cc_mailster_add_attendees_to_training could not find user ID '.$attendee['user_id'].' for payment '.$payment['id'] );
		}
	}
}

// sends the email confirmations (for online, invoice or free registration)
// and subscribes people as needed
function cc_mailsterint_send_email($payment_data){
	global $rpm_theme_options;
	// add subscriber to mailster and add to the registration list and to the workshop list
	$userdata = array(
        'email' => $payment_data['email'],
        'firstname' => $payment_data["firstname"],
        'lastname' => $payment_data['lastname'],
        'last_payment_id' => $payment_data['id'],
        'region' => cc_country_region( get_user_meta( $payment_data['reg_userid'], 'address_country', true ) ),
    );
    // $subs_regd = false;
    $reg_list_id = cc_mailster_regnews_list_id('reg');
	$subscriber = mailster('subscribers')->get_by_mail($payment_data['email']);
	if($subscriber){
		$subscriber_id = $subscriber->ID;
		// we need to update the subscriber with the latest payment id
		$result = mailster('subscribers')->update( $userdata );
		// is the subscriber already on the registration list?
		/*
		$subs_list_ids = mailster('subscribers')->get_lists($subscriber_id, true); // get ids only
		if(in_array($reg_list_id, $subs_list_ids)){
			$subs_regd = true;
		}
		*/
	}else{
		// add subscriber
        $overwrite = true;
        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
        $subs_list_ids = array();
	}
    // assign the subscriber to the lists
    $wanted_lists = array($reg_list_id);
    /* handle all attendees (including the registrant) in a mo
    // if the attendee_email is not set then add the subscriber to the workshop lists
    if($payment_data['attendee_email'] == ''){
    	$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id']);
    	if($payment_data['upsell_workshop_id'] > 0){
    		$wanted_lists[] = cc_mailster_training_list_id($payment_data['upsell_workshop_id']);
    	}
    	if($payment_data['type'] == '' && workshop_is_multi_event($payment_data['workshop_id']) && $payment_data['event_ids'] <> ''){
			$event_ids = explode(',', $payment_data['event_ids']);
			foreach ($event_ids as $event_id) {
				if($event_id <> ''){
					$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id'], $event_id);
				}
			}
    	}
    }
    */
    if($subscriber_id > 0){
        mailster('subscribers')->assign_lists($subscriber_id, $wanted_lists);
        $attendees = cc_attendees_for_payment($payment_data['id']);

        // now we'll try to send the registration email immediataly
        if($payment_data['type'] == ''){
        	// workshop
			// cancel_fee
			$type = get_post_meta( $payment_data['workshop_id'], 'type', true );
			if($type == 'webinar'){
				$cancel_fee = '10% of the training fee';
			}else{
				$cancel_fee = '10% of the training fee';
			}
			// workshop_webinar_msg
			$workshop_webinar_msg = cc_login_account_password_msg($payment_data);

			// thanks msg
			$training_title = ccpa_mailster_tag_workshop_direct($payment_data); // includes date and/or upsell
			if( count($attendees) == 1 && $attendees[0]['registrant'] == 'r' ){
				$thanks_msg = 'Thank you for registering to attend:'.$training_title;
			}else{
				$thanks_msg = 'Thank you for registering for:'.$training_title;
				if( count($attendees) == 1 ){
					$thanks_msg .= '<br><br>The following person is registered to attend the training:<ul>';
					$user = get_user_by('ID', $attendees[0]['user_id']);
					$thanks_msg .= '<li>'.$user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')</li>';
				}else{
					$thanks_msg .= '<br><br>The following people are registered to attend the training:<ul>';
					foreach ($attendees as $attendee) {
						$thanks_msg .= '<li>';
						if($attendee['registrant'] == 'r'){
							$thanks_msg .= 'Yourself';
						}else{
							$user = get_user_by('ID', $attendee['user_id']);
							$thanks_msg .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';							
						}
						$thanks_msg .= '</li>';
					}
				}
				$thanks_msg .= '</ul>Information about the training has been sent to them.';
			}

	        if($payment_data['status'] == 'Payment not needed' && ( $payment_data['disc_code'] == 'CNWL' || $payment_data['disc_code'] == 'NLFT' || $payment_data['disc_amount'] == 0 ) ){
	        	$mailster_tags = array(
	        		'workshop' => $training_title,
	        		'workshop_webinar_msg' => $workshop_webinar_msg,
					'registration_message' => nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) ),
					'thanks_msg' => $thanks_msg,
	        	);
	        	$mailster_hook = 'send_reg_workshop_free';
	        }else{
	        	// for online payment or invoice
	        	$mailster_tags = array(
					'workshop' => $training_title,
					'amount' => ccpa_payment_data_amount($payment_data),
					'promo_msg' => ccpa_mailster_tag_promo_msg_direct($payment_data),
					'date' => date('jS M Y'), // online pmt only
					'customer_address' => ccpa_mailster_tag_customer_address_direct($payment_data), // online pmt only
					'workshop_webinar_msg' => $workshop_webinar_msg,
					'cancel_fee' => $cancel_fee,
					'registration_message' => nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) ),
					'thanks_msg' => $thanks_msg,
	        	);
	        	if($payment_data['pmt_method'] == 'invoice'){
	        		$mailster_hook = 'send_reg_workshop_inv';
	        	}else{
	        		$mailster_hook = 'send_reg_workshop_paid';
	        	}
	        }

	    }else{
	    	// recording

			// thanks msg
			$training_title = ccpa_mailster_tag_workshop_direct($payment_data); // includes date and/or upsell
			if( count($attendees) == 1 && $attendees[0]['registrant'] == 'r' ){
				$thanks_msg = 'Thank you for registering to watch '.$training_title.'.';
			}else{
				$thanks_msg = 'Thank you for registering for '.$training_title.'.';
				if( count($attendees) == 1 ){
					$thanks_msg .= '<br><br>The following person is registered to watch the training:<ul>';
					$user = get_user_by('ID', $attendees[0]['user_id']);
					$thanks_msg .= '<li>'.$user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')</li>';
				}else{
					$thanks_msg .= '<br><br>The following people are registered to watch the training:<ul>';
					foreach ($attendees as $attendee) {
						$thanks_msg .= '<li>';
						if($attendee['registrant'] == 'r'){
							$thanks_msg .= 'Yourself';
						}else{
							$user = get_user_by('ID', $attendee['user_id']);
							$thanks_msg .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';							
						}
						$thanks_msg .= '</li>';
					}
				}
				$thanks_msg .= '</ul>Information about the training has been sent to them.';
			}

			// recording access expiry
			// We need an attendee (any attendee but not a registrant only) for this
			$recording_access_expiry = rpm_cc_recordings_expiry_pretty_expiry_datetime( $attendees[0]['user_id'], $payment_data['workshop_id'] );


			$mailster_tags = array(
				'firstname' => $payment_data["firstname"],
				'recording' => $training_title,
				'amount' => ccpa_payment_data_amount($payment_data),
				'promo_msg' => ccpa_mailster_tag_promo_msg_direct($payment_data),
				'set_password' => cc_login_account_password_msg($payment_data),
				// 'date' => date('jS M Y', strtotime($payment_data['last_update'])),
				'date' => date('jS M Y'),
				'customer_address' => ccpa_mailster_tag_customer_address_direct($payment_data),
				'registration_message' => nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) ),
				'recording_access_expiry' => $recording_access_expiry,
				'thanks_msg' => $thanks_msg,
			);
	        if($payment_data['status'] == 'Payment not needed' && ( $payment_data['disc_code'] == 'CNWL' || $payment_data['disc_code'] == 'NLFT' || $payment_data['disc_amount'] == 0 ) ){
				$mailster_hook = 'send_reg_recording_free';
			}elseif($payment_data['pmt_method'] == 'online'){
				$mailster_hook = 'send_reg_recording_paid';
			}else{
				$mailster_hook = 'send_reg_recording_inv';
			}

	    }

        /*
		$add_special_action = false;
		$campaign_id = ccpa_get_campaign_id_from_hook($mailster_hook);
		if($campaign_id){
			$add_special_action = ccpa_subscriber_has_prev_action($subscriber_id, $campaign_id);
		}
		*/
		sysctrl_mailster_ar_hook($mailster_hook, $subscriber_id, $mailster_tags);
		/*
		if($add_special_action){
			ccpa_add_registration_resent_activity($subscriber_id, $campaign_id);
    	}
    	*/
	        
	    /* replaced by new attendee emails below
        // if the attendee_email is set then we need to do most of this again for that email address
        if($payment_data['attendee_email'] <> ''){
        	$subscriber = mailster('subscribers')->get_by_mail($payment_data['attendee_email']);
        	if($subscriber){
        		// we'll not bother to remove and re-add cos there are no autoresponders for the workshop lists
        		$subscriber_id = $subscriber->ID;
        	}else{
				// add subscriber
				$userdata = array(
		            'email' => $payment_data['attendee_email'],
		            'firstname' => $payment_data["attendee_firstname"],
		            'lastname' => $payment_data['attendee_lastname'],
		        );
		        $overwrite = true;
		        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
        	}

        	$wanted_lists = array($reg_list_id);
	    	$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id']);
	    	if($upsell_list_id > 0){
	    		$wanted_lists[] = cc_mailster_training_list_id($payment_data['upsell_workshop_id']);
	    	}
	    	if($payment_data['type'] == '' && workshop_is_multi_event($payment_data['workshop_id']) && $payment_data['event_ids'] <> ''){
				$event_ids = explode(',', $payment_data['event_ids']);
				foreach ($event_ids as $event_id) {
					if($event_id <> ''){
						$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id'], $event_id);
					}
				}
	    	}
	        if($subscriber_id > 0){
	        	mailster('subscribers')->assign_lists($subscriber_id, $wanted_lists);
		        // now we'll try to send the registration email
				$mailster_tags['workshop_webinar_msg'] = cc_login_account_password_msg($payment_data, 'a');
				$mailster_tags['thanks_msg'] = $payment_data["firstname"].' '.$payment_data["lastname"].' (we have sent them a copy of this email) has registered for you to attend:';
	    		sysctrl_mailster_ar_hook($mailster_hook, $subscriber_id, $mailster_tags);
	        }
	    }
	    */

	    $attendees_for_joe = '';
	    // attendees (including the registrant)
	    foreach ($attendees as $attendee) {
			$user = get_user_by('ID', $attendee['user_id']);
			if($user){
	        	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	        	if($subscriber){
	        		$subscriber_id = $subscriber->ID;
	        	}else{
					// add subscriber
					$userdata = array(
			            'email' => $user->user_email,
			            'firstname' => $user->user_firstname,
			            'lastname' => $user->user_lastname,
			        );
			        $overwrite = true;
			        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
	        	}

	        	$wanted_lists = array($reg_list_id);
		    	$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id']);
		    	if($payment_data['upsell_workshop_id'] > 0){
		    		$wanted_lists[] = cc_mailster_training_list_id($payment_data['upsell_workshop_id']);
		    	}
		    	if($payment_data['type'] == '' && workshop_is_multi_event($payment_data['workshop_id']) && $payment_data['event_ids'] <> ''){
					$event_ids = explode(',', $payment_data['event_ids']);
					foreach ($event_ids as $event_id) {
						if($event_id <> ''){
							$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id'], $event_id);
						}
					}
		    	}
		        if($subscriber_id > 0){
		        	mailster('subscribers')->assign_lists($subscriber_id, $wanted_lists);
			        
			        // now we'll try to send the attendee email
			        if($payment_data['type'] == ''){
			        	$attendee_msg = 'You are now registered to attend:'.ccpa_mailster_tag_workshop_direct($payment_data);
			        }else{
			        	$attendee_msg = 'You are now registered to view:'.ccpa_mailster_tag_workshop_direct($payment_data);
			        }
			        if($attendee['registrant'] <> 'r'){
			        	$attendee_msg .= '<br><br>You have been registered for this training by '.$payment_data["firstname"].' '.$payment_data['lastname'].' ('.$payment_data['email'].').';
			        }
			        if($payment_data['type'] == ''){
			        	$attendee_msg .= '<br><br>You can view all details about the training (including the joining information) here: <a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>. You will have access to the workshop live on the day and a recording of the training afterwards. All these details will also be emailed to you shortly before the workshop.';
			        }else{
			        	$attendee_msg .= '<br><br>You can access the training here: <a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>. Access to this training is available until '.rpm_cc_recordings_expiry_pretty_expiry_datetime($user->ID, $payment_data['workshop_id']).'.';
			        }
			        $registration_message = nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) );
			        if($registration_message <> ''){
			        	$attendee_msg .= '<br><br>'.$registration_message;
			        }

		        	$mailster_tags = array(
						'training_title' => get_the_title($payment_data['workshop_id']),
						'attendee_msg' => $attendee_msg,
		        	);
		    		sysctrl_mailster_ar_hook('training_attendee_email', $subscriber_id, $mailster_tags);
		        }

		        // collect stuff for the msg to Joe
		        $attendees_for_joe .= $user->user_firstname.' '.$user->user_lastname.' '.$user->user_email.'<br>';
			}
	    }
	}

	// now tell Joe
	$reg_email_conf_to = $rpm_theme_options['reg-to'];
	if($reg_email_conf_to <> ''){
		$subject = 'Contextual Consulting: New Registration ('.$payment_data['status'].')';
		$message = 'New registration:<br>';
		foreach ($payment_data as $key => $value) {
			$message .= $key.': '.$value.'<br>';
		}
		$message .= '<br>Attendees:<br>'.$attendees_for_joe;
		$headers = array(
			'From: '.$rpm_theme_options['email-from-name'].' <'.$rpm_theme_options['email-from'].'>',
			'Content-Type: text/html; charset=UTF-8',
		);
		$result = wp_mail($reg_email_conf_to, $subject, $message, $headers);
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

// gets or creates the subscriber_id for a payment
// also, optionally, updates basic subscriber data
function cc_mailster_get_payment_subscriber_id( $payment, $update_subs=true ){
	// ccpa_write_log('function cc_mailster_get_payment_subscriber_id');
	// ccpa_write_log($payment);
	$userdata = array(
        'email' => $payment['email'],
        'firstname' => $payment["firstname"],
        'lastname' => $payment['lastname'],
        'last_payment_id' => $payment['id'],
        'region' => cc_country_region( get_user_meta( $payment['reg_userid'], 'address_country', true ) ),
    );
	$subscriber = mailster( 'subscribers' )->get_by_mail( $payment['email'] );
	if( $subscriber ){
		$subscriber_id = $subscriber->ID;
		// we need to update the subscriber with the latest payment id
		if( $update_subs ){
			$result = mailster( 'subscribers' )->update( $userdata );
		}
	}else{
		// add subscriber
        $overwrite = true;
        $subscriber_id = mailster( 'subscribers' )->add( $userdata, $overwrite );
	}
	return $subscriber_id;
}

/**
 * The registration_details tag for the REGISTRANT email
 * This function assembles the email content for the person who made the registration
 * It needs to show:
 * - All the training/courses they registered for
 * - All the attendees (including themselves if attending)
 * - Payment details
 * - Access information
 */
function ccpa_mailster_tag_registration_details( $payment ){
    $reg_dets = '';
    $user_id = cc_payment_user_id( $payment );
    $user = get_user_by( 'id', $user_id );
    $attendees = cc_attendees_for_payment( $payment['id'] );
    $inc_workshop = $inc_recording = false;

    // Determine if registrant is also attending
    $registrant_attending = false;
    foreach( $attendees as $attendee ){
        if( $attendee['registrant'] == 'r' ){
            $registrant_attending = true;
            break;
        }
    }

    // Opening message
    $reg_dets .= 'Thank you for registering ';
    if( count($attendees) == 1 && $registrant_attending ){
        $reg_dets .= 'to attend ';
    } else {
        $reg_dets .= 'for ';
    }
    $reg_dets .= 'the following training:<br><br>';

    // TRAINING DETAILS - Handle all training types
    if( $payment['type'] == 'group' ){
        // NEW: Group training - show all selected courses
        $group_courses = cc_training_groups_get_courses( $payment['id'] );
        
        if( !empty($group_courses) ){
            $reg_dets .= '<strong>Group Training Registration:</strong><br>';
            foreach( $group_courses as $course_id ){
                $response = ccpa_mailster_training_details( $course_id, '', 0, $user_id );
                $reg_dets .= '• ' . $response['reg_dets'] . '<br>';
                
                // Track what type of training is included for access instructions
                if( get_post_type($course_id) == 'workshop' ){
                    $inc_workshop = true;
                } else {
                    $inc_recording = true;
                }
            }
        } else {
            // Fallback if group courses not found
            $reg_dets .= '<strong>Group Training:</strong> Multiple courses<br>';
        }
        
    } elseif( $payment['type'] == 'series' ){
        // Series training - show all courses in series
        $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        
        $reg_dets .= '<strong>' . get_the_title($payment['workshop_id']) . ' (Complete Series):</strong><br>';
        foreach( $series_courses as $training_id ){
            $response = ccpa_mailster_training_details( $training_id, '', 0, $user_id );
            $reg_dets .= '• ' . $response['reg_dets'] . '<br>';
            
            if( get_post_type($training_id) == 'workshop' ){
                $inc_workshop = true;
            } else {
                $inc_recording = true;
            }
        }
        
    } else {
        // Individual workshop or recording
        $response = ccpa_mailster_training_details( $payment['workshop_id'], $payment['event_ids'], $payment['upsell_workshop_id'], $user_id );
        $reg_dets .= $response['reg_dets'];
        $inc_workshop = $response['inc_workshop'];
        $inc_recording = $response['inc_recording'];
    }

    // ATTENDEE INFORMATION
    if( count($attendees) > 1 || !$registrant_attending ){
        if( count($attendees) == 1 ){
            // Single attendee who is not the registrant
            $reg_dets .= '<br>The following person is registered to attend:<br>';
            $user_attendee = get_user_by( 'ID', $attendees[0]['user_id'] );
            $reg_dets .= '• ' . $user_attendee->user_firstname . ' ' . $user_attendee->user_lastname . ' (' . $user_attendee->user_email . ')<br>';
            
        } else {
            // Multiple attendees
            $reg_dets .= '<br>The following people are registered to attend:<br>';
            foreach( $attendees as $attendee ){
                if( $attendee['registrant'] == 'r' ){
                    $reg_dets .= '• Yourself<br>';
                } else {
                    $user_attendee = get_user_by( 'ID', $attendee['user_id'] );
                    $reg_dets .= '• ' . $user_attendee->user_firstname . ' ' . $user_attendee->user_lastname . ' (' . $user_attendee->user_email . ')<br>';
                }
            }
        }
        $reg_dets .= '<br>All attendees will receive information about accessing the training.';
    }

    // PAYMENT INFORMATION
    if( $payment['pmt_method'] == 'online' || $payment['pmt_method'] == 'invoice' ){
        if( $payment['pmt_method'] == 'online' ){
            $reg_dets .= '<br><br>Thank you for your payment of ';
        } else {
            $reg_dets .= '<br><br>As requested, we will send an invoice for ';
        }
        $reg_dets .= ccpa_payment_data_amount( $payment ) . '. ';
        
        // Add promo/discount message if applicable
        $promo_msg = ccpa_mailster_tag_promo_msg_direct( $payment );
        if( $promo_msg ){
            $reg_dets .= $promo_msg;
        }
        
        if( $payment['pmt_method'] == 'online' ){
            $reg_dets .= '<br>Invoice date: ' . date( 'jS M Y', strtotime( $payment['last_update'] ) );
        }
    }

    // MY ACCOUNT ACCESS INSTRUCTIONS
    if( $user ){
        $my_acct_link = '<a href="' . esc_url( site_url("my-account/?login=" . rawurlencode( $user->user_email ) ) ) . '">My Account</a>';
    } else {
        $my_acct_link = '<a href="' . esc_url( site_url("my-account/" ) ) . '">My Account</a>';
    }
    
    if( $registrant_attending ){
        $reg_dets .= '<br><br>You can view all details about your training ';
        if( $inc_workshop && $inc_recording ){
            $reg_dets .= 'including login details for live training and access to on-demand content here: ' . $my_acct_link . '. For live training, you will have access on the day and to recordings afterwards. All details will be emailed to you before the training.';
        } elseif( $inc_workshop ){
            $reg_dets .= 'including login details here: ' . $my_acct_link . '. You will have access to the training live on the day and to recordings afterwards. All details will be emailed to you before the training.';
        } elseif( $inc_recording ){
            $reg_dets .= 'and watch your on-demand training here: ' . $my_acct_link;
        }
    } else {
        $reg_dets .= '<br><br>You can view all details about the training here: ' . $my_acct_link;
    }

    // REGISTRATION MESSAGES (course-specific messages)
    if( $registrant_attending ){
        $registration_messages = array();
        
        if( $payment['type'] == 'group' ){
            // Group training - get messages from all courses
            $group_courses = cc_training_groups_get_courses( $payment['id'] );
            foreach( $group_courses as $course_id ){
                $reg_msg = nl2br( get_post_meta( $course_id, 'registration_message', true ) );
                if( $reg_msg ){
                    $registration_messages[get_the_title($course_id)] = $reg_msg;
                }
            }
        } elseif( $payment['type'] == 'series' ){
            // Series - get messages from all courses in series
            $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true );
            $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
            $series_courses = is_array($series_courses) ? $series_courses : [];
            foreach( $series_courses as $course_id ){
                $reg_msg = nl2br( get_post_meta( $course_id, 'registration_message', true ) );
                if( $reg_msg ){
                    $registration_messages[get_the_title($course_id)] = $reg_msg;
                }
            }
        } else {
            // Individual training
            $reg_msg_1 = nl2br( get_post_meta( $payment['workshop_id'], 'registration_message', true ) );
            if( $reg_msg_1 ){
                if( $payment['upsell_workshop_id'] > 0 ){
                    $registration_messages[get_the_title( $payment['workshop_id'] )] = $reg_msg_1;
                } else {
                    $registration_messages[''] = $reg_msg_1; // No title needed for single course
                }
            }
            
            if( $payment['upsell_workshop_id'] > 0 ){
                $reg_msg_2 = nl2br( get_post_meta( $payment['upsell_workshop_id'], 'registration_message', true ) );
                if( $reg_msg_2 ){
                    $registration_messages[get_the_title( $payment['upsell_workshop_id'] )] = $reg_msg_2;
                }
            }
        }
        
        // Output registration messages
        if( !empty($registration_messages) ){
            $reg_dets .= '<br>';
            foreach( $registration_messages as $title => $message ){
                if( $title && count($registration_messages) > 1 ){
                    $reg_dets .= '<br><strong>' . $title . ':</strong>';
                }
                $reg_dets .= '<br>' . $message;
            }
        }
    }

    return $reg_dets;
}

/* was ...................
// the registration_details tag
// for the new, simplified registration email (Nov 2024)
// this accommodates all trainiing types and upsells and free, invoice or paid ... woohoo!
// note that all attendees (excl the registrant) will receive an attendee email
function ccpa_mailster_tag_registration_details( $payment ){
	$reg_dets = '';
	$user_id = cc_payment_user_id( $payment );
    $user = get_user_by( 'id', $user_id );
    $attendees = cc_attendees_for_payment( $payment['id'] );
    $inc_workshop = $inc_recording = false;

	// this will sit within a paragraph, so just use br for a new line
	// don't use quotes!

	// thanks
	$reg_dets .= 'Thank you for registering ';
	if( count($attendees) == 1 && $attendees[0]['registrant'] == 'r' ){
		$reg_dets .= 'to attend ';
	}else{
		$reg_dets .= 'for ';
	}
	$reg_dets .= 'our training. The training you have registered for is:<br>';

	// primary training title
	if( $payment['type'] == 'recording' ){
		$reg_dets .= '<strong>On-demand training:</strong>';
	}elseif( $payment['type'] == 'series' ){
		$reg_dets .= '<strong>Training series:</strong>';
	}else{
		$reg_dets .= '<strong>Live training:</strong>';
	}
	$reg_dets .= '<br><strong><a href="'.get_permalink( $payment['workshop_id'] ).'">'.get_the_title( $payment['workshop_id'] ).'</a></strong> ';

	if( $payment['type'] == 'series' ){
		$reg_dets .= 'including:';
	}

	// date for workshops, availability for recordings
	if( $payment['type'] == '' ){
		$reg_dets .= ccpa_mailster_tag_workshop_datetime( $payment['workshop_id'], $user_id, $payment['event_ids'] );
		$inc_workshop = true;
	}elseif( $payment['type'] == 'recording' ){
		$reg_dets .= ccpa_mailster_tag_recording_availability( $payment['workshop_id'], $user_id );
		$inc_recording = true;
	}

	// series
	if( $payment['type'] == 'series' ){
	    // get all the training ids for the series
	    $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true);
	    $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
	    $series_courses = is_array($series_courses) ? $series_courses : [];
	    $reg_dets .= '<ul>';
	    foreach ($series_courses as $course_id) {
	    	$reg_dets .= '<li><a href="'.get_permalink( $course_id ).'" target="_blank">'.get_the_title( $course_id ).'</a>';
	    	if ( course_training_type( $course_id ) == 'workshop' ){
	    		$reg_dets .= ccpa_mailster_tag_workshop_datetime( $course_id, $user_id, '' );
				$inc_workshop = true;
	    	}else{
	    		$reg_dets .= ccpa_mailster_tag_recording_availability( $course_id, $user_id );
				$inc_recording = true;
	    	}
	    	$reg_dets .= '</li>';
	    }
	    $reg_dets .= '</ul';
	}

	// upsell
	if( $payment['upsell_workshop_id'] > 0 ){
		$reg_dets .= '<br><strong><em>Plus:</em></strong><br>';
		if( course_training_type( $payment['upsell_workshop_id'] ) == 'recording' ){
			$reg_dets .= '<strong>On-demand training:</strong>';
			$inc_recording = true;
		}else{
			$reg_dets .= '<strong>Live training:</strong>';
			$inc_workshop = true;
		}
		$reg_dets .= '<br><strong><a href="'.get_permalink( $payment['upsell_workshop_id'] ).'">'.get_the_title( $payment['upsell_workshop_id'] ).'</a></strong> ';

		// date for workshops, availability for recordings
		if( course_training_type( $payment['upsell_workshop_id'] ) == 'workshop' ){
			$reg_dets .= ccpa_mailster_tag_workshop_datetime( $payment['upsell_workshop_id'], $user_id );
		}else{
			$reg_dets .= ccpa_mailster_tag_recording_availability( $payment['upsell_workshop_id'], $user_id );
		}
	}

	// attendees
	$also_attendee = false;
	if( count($attendees) > 1 || $attendees[0]['registrant'] <> 'r' ){
		if( count($attendees) == 1 ){
			$reg_dets .= '<br><br>The following person is registered to attend the training:<ul>';
			$user = get_user_by( 'ID', $attendees[0]['user_id'] );
			$reg_dets .= '<li>'.$user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')</li>';
		}else{
			$reg_dets .= '<br><br>The following people are registered to attend the training:<ul>';
			foreach ( $attendees as $attendee ) {
				$reg_dets .= '<li>';
				if( $attendee['registrant'] == 'r' ){
					$reg_dets .= 'Yourself';
					$also_attendee = true;
				}else{
					$user = get_user_by( 'ID', $attendee['user_id'] );
					$reg_dets .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';							
				}
				$reg_dets .= '</li>';
			}
		}
		$reg_dets .= '</ul>Information about the training has been sent to them.';
	}else{
		$also_attendee = true;
	}

	// payment stuff
	if( $payment['pmt_method'] == 'online' || $payment['pmt_method'] == 'invoice' ){
		if( $payment['pmt_method'] == 'online' ){
			$reg_dets .= '<br><br>Thank you for your payment of ';
		}else{
			$reg_dets .= '<br><br>As you requested, we will send an invoice for the amount of ';
		}
		$reg_dets .= ccpa_payment_data_amount( $payment ).'. ';
		$reg_dets .= ccpa_mailster_tag_promo_msg_direct( $payment );
		if( $payment['pmt_method'] == 'online' ){
			$reg_dets .= '<br>Invoice date: '.date( 'jS M Y', strtotime( $payment['last_update'] ) );
			if( $payment['address'] <> '' ){
				$reg_dets .= '<br><br>Your address:<br>'.$payment['address'];
			}
		}		
	}

	// get them to login
    if($user){
        $my_acct_link = '<a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>';
    }else{
        $my_acct_link = '<a href="'.esc_url( site_url("my-account/" ) ).'">My Account</a>';
    }
    if( $also_attendee ){
	    $reg_dets .= '<br><br>You can view all details about your training ';
	    if( $inc_workshop && $inc_recording ){
	    	$reg_dets .= 'including the login details for your live training and to watch your on-demand training and recording here: '.$my_acct_link.'. For the live training, you will have access on the day and to a recording of the event afterwards. All these details will also be emailed to you shortly before the training.';
	    }elseif( $inc_workshop ){
	    	$reg_dets .= 'including the login here: '.$my_acct_link.'. You will have access to the training live on the day and recording of the event afterwards. All these details will also be emailed to you shortly before the training.';
	    }elseif( $inc_recording ){
	    	$reg_dets .= 'and watch your on-demand training here: '.$my_acct_link;
	    }
	}else{
	    $reg_dets .= '<br><br>You can view all details about the training here: '.$my_acct_link;
	}

    // registration_message(s)
    if( $also_attendee ){
	    $reg_msg_1 = nl2br( get_post_meta( $payment['workshop_id'], 'registration_message', true ) );
	    $reg_msg_2 = '';
		if( $payment['upsell_workshop_id'] > 0 ){
		    $reg_msg_2 = nl2br( get_post_meta( $payment['upsell_workshop_id'], 'registration_message', true ) );
		}
		if( $reg_msg_1 <> '' || $reg_msg_2 <> '' ){
			$reg_dets .= '<br>';
		}
		if( $reg_msg_1 <> '' ){
			if( $payment['upsell_workshop_id'] > 0 ){
				$reg_dets .= '<br><strong>'.get_the_title( $payment['workshop_id'] ).':</strong>';
			}
			$reg_dets .= '<br>'.$reg_msg_1;
		}
		if( $reg_msg_2 <> '' ){
			$reg_dets .= '<br><strong>'.get_the_title( $payment['upsell_workshop_id'] ).':</strong>';
			$reg_dets .= '<br>'.$reg_msg_2;
		}
	}

	return $reg_dets;
}
*/

/**
 * The registration_details tag for ATTENDEE emails
 * This function assembles the email content for people attending the training
 * (excluding the registrant who gets the main registration email)
 */
function ccpa_mailster_tag_attendee_reg_dets( $payment, $attendee_id ){
    $reg_dets = '';
    $user = get_user_by( 'id', $attendee_id );
    $inc_workshop = $inc_recording = false;

    // Opening message
    $reg_dets .= 'You have been registered to attend the following training:<br><br>';

    // TRAINING DETAILS - Handle all training types  
    if( $payment['type'] == 'group' ){
        // NEW: Group training - show all selected courses
        $group_courses = cc_training_groups_get_courses( $payment['id'] );
        
        if( !empty($group_courses) ){
            $reg_dets .= '<strong>Group Training:</strong><br>';
            foreach( $group_courses as $course_id ){
                $response = ccpa_mailster_training_details( $course_id, '', 0, $attendee_id );
                $reg_dets .= '• ' . $response['reg_dets'] . '<br>';
                
                // Track what type of training is included for access instructions
                if( get_post_type($course_id) == 'workshop' ){
                    $inc_workshop = true;
                } else {
                    $inc_recording = true;
                }
            }
        }
        
    } elseif( $payment['type'] == 'series' ){
        // Series training - show all courses in series
        $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        
        $reg_dets .= '<strong>' . get_the_title($payment['workshop_id']) . ' (Complete Series):</strong><br>';
        foreach( $series_courses as $training_id ){
            $response = ccpa_mailster_training_details( $training_id, '', 0, $attendee_id );
            $reg_dets .= '• ' . $response['reg_dets'] . '<br>';
            
            if( get_post_type($training_id) == 'workshop' ){
                $inc_workshop = true;
            } else {
                $inc_recording = true;
            }
        }
        
    } else {
        // Individual workshop or recording (existing logic)
        $response = ccpa_mailster_training_details( $payment['workshop_id'], $payment['event_ids'], $payment['upsell_workshop_id'], $attendee_id );
        $reg_dets .= $response['reg_dets'];
        $inc_workshop = $response['inc_workshop'];
        $inc_recording = $response['inc_recording'];
    }

    // WHO REGISTERED THEM (if not self-registration)
    $registrant_user_id = cc_payment_user_id( $payment );
    if( $registrant_user_id != $attendee_id ){
        $registrant_user = get_user_by( 'id', $registrant_user_id );
        if( $registrant_user ){
            $reg_dets .= '<br>You have been registered for this training by ' . 
                        $registrant_user->user_firstname . ' ' . $registrant_user->user_lastname . 
                        ' (' . $registrant_user->user_email . ').';
        }
    }

    // ACCESS INSTRUCTIONS
    $my_acct_link = '<a href="' . esc_url( site_url("my-account/?login=" . rawurlencode( $user->user_email ) ) ) . '">My Account</a>';
    
    $reg_dets .= '<br><br>You can access your training here: ' . $my_acct_link;
    
    if( $inc_workshop && $inc_recording ){
        $reg_dets .= '. This includes login details for live training and access to on-demand content. For live training, you will have access on the day and to recordings afterwards.';
    } elseif( $inc_workshop ){
        $reg_dets .= '. This includes login details for your live training. You will have access on the day and to recordings afterwards.';
    } elseif( $inc_recording ){
        $reg_dets .= ' to watch your on-demand training.';
    }
    
    if( $inc_workshop ){
        $reg_dets .= ' All details will be emailed to you before the training.';
    }

    // REGISTRATION MESSAGES (course-specific messages for attendees)
    $registration_messages = array();
    
    if( $payment['type'] == 'group' ){
        // Group training - get messages from all courses
        $group_courses = cc_training_groups_get_courses( $payment['id'] );
        foreach( $group_courses as $course_id ){
            $reg_msg = nl2br( get_post_meta( $course_id, 'registration_message', true ) );
            if( $reg_msg ){
                $registration_messages[get_the_title($course_id)] = $reg_msg;
            }
        }
    } elseif( $payment['type'] == 'series' ){
        // Series - get messages from all courses in series  
        $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        foreach( $series_courses as $course_id ){
            $reg_msg = nl2br( get_post_meta( $course_id, 'registration_message', true ) );
            if( $reg_msg ){
                $registration_messages[get_the_title($course_id)] = $reg_msg;
            }
        }
    } else {
        // Individual training (existing logic)
        $reg_msg_1 = nl2br( get_post_meta( $payment['workshop_id'], 'registration_message', true ) );
        if( $reg_msg_1 ){
            if( $payment['upsell_workshop_id'] > 0 ){
                $registration_messages[get_the_title( $payment['workshop_id'] )] = $reg_msg_1;
            } else {
                $registration_messages[''] = $reg_msg_1;
            }
        }
        
        if( $payment['upsell_workshop_id'] > 0 ){
            $reg_msg_2 = nl2br( get_post_meta( $payment['upsell_workshop_id'], 'registration_message', true ) );
            if( $reg_msg_2 ){
                $registration_messages[get_the_title( $payment['upsell_workshop_id'] )] = $reg_msg_2;
            }
        }
    }
    
    // Output registration messages
    if( !empty($registration_messages) ){
        $reg_dets .= '<br>';
        foreach( $registration_messages as $title => $message ){
            if( $title && count($registration_messages) > 1 ){
                $reg_dets .= '<br><strong>' . $title . ':</strong>';
            }
            $reg_dets .= '<br>' . $message;
        }
    }

    return $reg_dets;
}


/* was ............
// the registration_details tag for the attendee email
// for the new, simplified registration email (Nov 2024)
// this accommodates all trainiing types and upsells and free, invoice or paid ... woohoo!
// will be sent to all attendees (excl the registrant if appropriate)
function ccpa_mailster_tag_attendee_reg_dets( $payment, $attendee_id ){
	$reg_dets = '';
    $user = get_user_by( 'id', $attendee_id );
    $inc_workshop = $inc_recording = false;

	// this will sit within a paragraph, so just use br for a new line
	// don't EVER use quotes!

	// thanks
	$reg_dets .= 'You have been registered to attend the following training:<br>';

	if( $payment['type'] == 'series' ){
	    // get all the training ids for the series
	    $series_courses = get_post_meta( $payment['workshop_id'], '_series_courses', true );
	    $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
	    $series_courses = is_array($series_courses) ? $series_courses : [];
	    foreach ($series_courses as $training_id) {
	    	$response = ccpa_mailster_training_details( $training_id, '', 0, $attendee_id );
	    	$reg_dets .= $response['reg_dets'].'<br>';
	    	if( $response['inc_workshop'] ) $inc_workshop = true;
	    	if( $response['inc_recording'] ) $inc_recording = true;
	    }
	}else{
    	$response = ccpa_mailster_training_details( $payment['workshop_id'], $payment['event_ids'], $payment['upsell_workshop_id'], $attendee_id );
    	$reg_dets .= $response['reg_dets'];
    	if( $response['inc_workshop'] ) $inc_workshop = true;
    	if( $response['inc_recording'] ) $inc_recording = true;
	}

	/*
	// primary training title
	if( $payment['type'] == 'recording' ){
		$reg_dets .= '<strong>On-demand training:</strong><br>';
	}else{
		$reg_dets .= '<strong>Live training:</strong><br>';
	}
	$reg_dets .= '<strong><a href="'.get_permalink( $payment['workshop_id'] ).'">'.get_the_title( $payment['workshop_id'] ).'</a></strong> ';

	// date for workshops, availability for recordings
	if( $payment['type'] == '' ){
		$reg_dets .= ccpa_mailster_tag_workshop_datetime( $payment['workshop_id'], $attendee_id, $payment['event_ids'] );
		$inc_workshop = true;
	}else{
		$reg_dets .= ccpa_mailster_tag_recording_availability( $payment['workshop_id'], $attendee_id );
		$inc_recording = true;
	}

	// upsell
	if( $payment['upsell_workshop_id'] > 0 ){
		$reg_dets .= '<br><strong><em>Plus:</em></strong><br>';
		if( get_post_type( $payment['upsell_workshop_id'] ) == 'recording' ){
			$reg_dets .= 'On-demand training: ';
			$inc_recording = true;
		}else{
			$reg_dets .= 'Live training: ';
			$inc_workshop = true;
		}
		$reg_dets .= '<strong><a href="'.get_permalink( $payment['upsell_workshop_id'] ).'">'.get_the_title( $payment['upsell_workshop_id'] ).'</a></strong> ';

		// date for workshops, availability for recordings
		if( get_post_type( $payment['upsell_workshop_id'] ) == 'workshop' ){
			$reg_dets .= ccpa_mailster_tag_workshop_datetime( $payment['upsell_workshop_id'], $attendee_id );
		}else{
			$reg_dets .= ccpa_mailster_tag_recording_availability( $payment['upsell_workshop_id'], $attendee_id );
		}
	}
	*//*

	// registrant
	$reg_dets .= '<br><br>You have been registered for this training by '.$payment["firstname"].' '.$payment['lastname'].' ('.$payment['email'].').';

	// get them to login
    if($user){
        $my_acct_link = '<a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>';
    }else{
        $my_acct_link = '<a href="'.esc_url( site_url("my-account/" ) ).'">My Account</a>';
    }
    $reg_dets .= '<br><br>You can view all details about your training ';
    if( $inc_workshop && $inc_recording ){
    	$reg_dets .= 'including the login details for your live training and to watch your on-demand training and recording here: '.$my_acct_link.'. For the live training, you will have access on the day and to a recording of the event afterwards. All these details will also be emailed to you shortly before the training.';
    }elseif( $inc_workshop ){
    	$reg_dets .= 'including the login here: '.$my_acct_link.'. You will have access to the training live on the day and recording of the event afterwards. All these details will also be emailed to you shortly before the training.';
    }elseif( $inc_recording ){
    	$reg_dets .= 'and watch your on-demand training here: '.$my_acct_link;
    }
	$reg_dets .= '<br>';

    // registration_message(s)
    $reg_msg_1 = nl2br( get_post_meta( $payment['workshop_id'], 'registration_message', true ) );
    $reg_msg_2 = '';
	if( $payment['upsell_workshop_id'] > 0 ){
	    $reg_msg_2 = nl2br( get_post_meta( $payment['upsell_workshop_id'], 'registration_message', true ) );
	}
	if( $reg_msg_1 <> '' ){
		if( $payment['upsell_workshop_id'] > 0 ){
			$reg_dets .= '<br><strong>'.get_the_title( $payment['workshop_id'] ).':</strong>';
		}
		$reg_dets .= '<br>'.$reg_msg_1;
	}
	if( $reg_msg_2 <> '' ){
		$reg_dets .= '<br><strong>'.get_the_title( $payment['upsell_workshop_id'] ).':</strong>';
		$reg_dets .= '<br>'.$reg_msg_2;
	}
	if( $reg_msg_1 <> '' || $reg_msg_2 <> '' ){
		$reg_dets .= '<br>';
	}

	return $reg_dets;
}
*/

// training details
function ccpa_mailster_training_details( $training_id, $event_ids, $upsell_training_id, $attendee_id ){
	$response = array(
		'reg_dets' => '',
		'inc_workshop' => false,
		'inc_recording' => false,
	);
	if( course_training_type( $training_id ) == 'workshop' ){
		$response['reg_dets'] .= '<strong>Live training:</strong><br>';
	}else{
		$response['reg_dets'] .= '<strong>On-demand training:</strong><br>';
	}
	$response['reg_dets'] .= '<strong><a href="'.get_permalink( $training_id ).'">'.get_the_title( $training_id ).'</a></strong> ';

	// date for workshops, availability for recordings
	if( get_post_type( $training_id ) == 'workshop' ){
		$response['reg_dets'] .= ccpa_mailster_tag_workshop_datetime( $training_id, $attendee_id, $event_ids );
		$response['inc_workshop'] = true;
	}else{
		$response['reg_dets'] .= ccpa_mailster_tag_recording_availability( $training_id, $attendee_id );
		$response['inc_recording'] = true;
	}

	// upsell
	if( $upsell_training_id > 0 ){
		$response['reg_dets'] .= '<br><strong><em>Plus:</em></strong><br>';
		if( course_training_type( $upsell_training_id ) == 'recording' ){
			$response['reg_dets'] .= 'On-demand training: ';
			$response['inc_recording'] = true;
		}else{
			$response['reg_dets'] .= 'Live training: ';
			$response['inc_workshop'] = true;
		}
		$response['reg_dets'] .= '<strong><a href="'.get_permalink( $upsell_training_id ).'">'.get_the_title( $upsell_training_id ).'</a></strong> ';

		// date for workshops, availability for recordings
		if( course_training_type( $upsell_training_id ) == 'workshop' ){
			$response['reg_dets'] .= ccpa_mailster_tag_workshop_datetime( $upsell_training_id, $attendee_id );
		}else{
			$response['reg_dets'] .= ccpa_mailster_tag_recording_availability( $upsell_training_id, $attendee_id );
		}
	}
	return $response;
}

// the workshop start date time
function ccpa_mailster_tag_workshop_datetime( $workshop_id, $user_id, $event_ids='' ){
	$html = '';
	// we'll use the start time if we can
	$workshop_start_ampm = workshop_start_datetime_ampm( $workshop_id, $user_id );
	if( $workshop_start_ampm <> '' ){
		$html .= '<br>'.$workshop_start_ampm;
	}else{
		$dates = get_post_meta( $workshop_id, 'prettydates', true );
		if($dates <> ''){
			$html .= '<br>'.$dates;
		}
	}

	if(workshop_is_multi_event($workshop_id) && $event_ids <> ''){
		$event_ids = explode(',', $event_ids);
		sort($event_ids);
		foreach ($event_ids as $event_id) {
			if($event_id <> ''){
				$event_name = get_post_meta($workshop_id, 'event_'.$event_id.'_name', true);
				if($event_name <> ''){
					$html .= '<br> - <strong>'.$event_name.'</strong>';
					if($event_id == 1){
						$event_date = get_post_meta($workshop_id, 'meta_a', true);
					}else{
						$event_date = get_post_meta($workshop_id, 'event_'.$event_id.'_date', true);
					}						
					if($event_date <> ''){
						$html .= ': '.$event_date;
					}
				}
			}
		}
	}
	return $html;
}

// recording availability
function ccpa_mailster_tag_recording_availability( $recording_id, $user_id ){
	$recording_access_expiry = rpm_cc_recordings_expiry_pretty_expiry_datetime( $user_id, $recording_id );
	return '<br>Access to this training is available until '.$recording_access_expiry;
}

// the mailster workshop tag
// now accommodates recordings and workshops as well as upsells
function ccpa_mailster_tag_workshop_direct($payment_data){
	$html = '<br><strong><a href="'.get_permalink( $payment_data['workshop_id'] ).'">'.get_the_title( $payment_data['workshop_id'] ).'</a></strong>';
	
	if( $payment_data['type'] == '' ){
		// we'll add in the start time too if we can
		$workshop_start_ampm = workshop_start_datetime_ampm( $payment_data['workshop_id'], $payment_data['reg_userid'] );
		if( $workshop_start_ampm <> '' ){
			$html .= ': '.$workshop_start_ampm;
		}else{
			$dates = get_post_meta( $payment_data['workshop_id'], 'prettydates', true );
			if($dates <> ''){
				$html .= ': '.$dates;
			}
		}

		if(workshop_is_multi_event($payment_data['workshop_id']) && $payment_data['event_ids'] <> ''){
			$event_ids = explode(',', $payment_data['event_ids']);
			sort($event_ids);
			foreach ($event_ids as $event_id) {
				if($event_id <> ''){
					$event_name = get_post_meta($payment_data['workshop_id'], 'event_'.$event_id.'_name', true);
					if($event_name <> ''){
						$html .= '<br> - <strong>'.$event_name.'</strong>';
						if($event_id == 1){
							$event_date = get_post_meta($payment_data['workshop_id'], 'meta_a', true);
						}else{
							$event_date = get_post_meta($payment_data['workshop_id'], 'event_'.$event_id.'_date', true);
						}						
						if($event_date <> ''){
							$html .= ': '.$event_date;
						}
					}
				}
			}
		}
	}

	if($payment_data['upsell_workshop_id'] > 0){
		$html .= '<br><em>Plus:</em><br><strong><a href="'.get_permalink( $payment_data['upsell_workshop_id'] ).'">'.get_the_title( $payment_data['upsell_workshop_id'] ).'</a></strong>';

		if( course_training_type( $payment_data['upsell_workshop_id'] ) == 'workshop' ){
			$dates = get_post_meta( $payment_data['upsell_workshop_id'], 'prettydates', true );
			if($dates <> ''){
				$html .= ': '.$dates;
			}
		}
	}
	return $html;
}

function ccpa_mailster_tag_promo_msg_direct($payment_data){
	global $rpm_theme_options;
	if($payment_data["disc_code"] == ''){
		return '';
	}
	if($payment_data["disc_code"] == 'UPSELL'){
		return 'Your registration has been made using our bonus training offer.';
	}
	$voucher = ccpa_voucher_table_get($payment_data["disc_code"]);
	if($voucher === NULL){
		// treat it as a discount
		$ccpa_mailster_promo_msg = $rpm_theme_options['mailster-promo-msg'];
		if(strpos($ccpa_mailster_promo_msg, '{promo_code}')){
			$ccpa_mailster_promo_msg = str_replace('{promo_code}', $payment_data["disc_code"], $ccpa_mailster_promo_msg);
		}
	}else{
		$ccpa_mailster_promo_msg = 
			cc_money_format($payment_data['disc_amount'], $payment_data['currency'])
			.' was received from your voucher: '
			.cc_voucher_core_pretty_voucher($payment_data['disc_code'])
			.'. The balance on your voucher is now '
			.cc_money_format( (float) str_replace( ',', '', ccpa_voucher_usage_balance( $voucher, $voucher['currency'] ) ), $voucher['currency'] );
	}
	return $ccpa_mailster_promo_msg;
}

// returns the custiomer address for mailster if it can
function ccpa_mailster_tag_customer_address_direct($payment_data){
	if($payment_data['address'] == '') return '';
	return '<br><br>Your address:<br>'.$payment_data['address'];
}

// get the campaign_id from the autoresponder hook
function ccpa_get_campaign_id_from_hook($hook){
	global $wpdb;
	$table_name = $wpdb->prefix.'postmeta';
	$sql = "SELECT * FROM $table_name WHERE meta_key = '_mailster_autoresponder'";
	$metas = $wpdb->get_results($sql, ARRAY_A);
	foreach ($metas as $meta) {
		$meta_value = maybe_unserialize($meta['meta_value']);
		/*
		var_dump(maybe_unserialize($meta['meta_value']));
		array(17) { 
			["amount"]=> int(0) 
			["unit"]=> string(6) "minute" 
			["before_after"]=> string(1) "1" 
			["action"]=> string(27) "mailster_autoresponder_hook" 
			["post_type"]=> string(4) "post" 
			["post_count"]=> string(1) "0" 
			["interval"]=> string(1) "1" 
			["time_frame"]=> string(3) "day" 
			["weekdays"]=> array(7) { [0]=> string(1) "1" [1]=> string(1) "2" [2]=> string(1) "3" [3]=> string(1) "4" [4]=> string(1) "5" [5]=> string(1) "6" [6]=> string(1) "0" } 
			["time_post_count"]=> string(1) "1" 
			["time_post_type"]=> string(4) "post" 
			["issue"]=> string(1) "1" 
			["post_count_status"]=> string(1) "0" 
			["followup_action"]=> string(1) "1" 
			["hook"]=> string(23) "send_registration_email" 
			["priority"]=> string(2) "10" 
			["once"]=> bool(false) }
		*/
		if(isset($meta_value['action']) && $meta_value['action'] == 'mailster_autoresponder_hook' && isset($meta_value['hook']) && $meta_value['hook'] == $hook){
			return $meta['post_id'];
		}
	}
	return false;
}

// triggers a subscriber for the registration (or reg free) email ... from the backend payments page or the my accts page
// adds the subscriber to Mailster and to the registration list if needed
// forces the mailster tags to be the right data from the specific payment data
// no longer also sends info to attendees
function ccpa_trigger_reg_email($payment_id){
	if(!$payment_id > 0) return false;
	if(!function_exists('mailster')) return false;
	$payment_data = cc_paymentdb_get_payment($payment_id);

	// is this payment for a workshop or a recording?
	if($payment_data['type'] == 'recording'){
		return ccpa_trigger_reg_email_recording($payment_data);
	}

	// we need to know the list id for the registration email list
    $lists = mailster( 'lists' )->get();
    $reg_list_id = 0;
    foreach ($lists as $list) {
    	if($list->name == 'Registration'){
    		$reg_list_id = $list->ID;
    		break;
    	}
	}
    // did we find the list? If not, create it
    if($reg_list_id == 0){
    	$reg_list_id = mailster('lists')->add('Registration');
    }
	$userdata = array(
        'email' => $payment_data['email'],
        'firstname' => $payment_data["firstname"],
        'lastname' => $payment_data['lastname'],
        'last_payment_id' => $payment_id,
    );
    $subs_regd = false;
	$subscriber = mailster('subscribers')->get_by_mail($payment_data['email']);
	if($subscriber){
		$subscriber_id = $subscriber->ID;
		// maybe some subscriber details have changed ...
		// result will be false if nothing has changed
		$result = mailster('subscribers')->update( $userdata );
		// is the subscriber already on the registration list?
		$subs_list_ids = mailster('subscribers')->get_lists($subscriber_id, true); // get ids only
		if(in_array($reg_list_id, $subs_list_ids)){
			$subs_regd = true;
		}
	}else{
		// add subscriber
	    $overwrite = true;
	    $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
	}
	if($subscriber_id > 0){
		if(!$subs_regd){
			// add to the list
			mailster('subscribers')->assign_lists($subscriber_id, $reg_list_id);
		}
        $attendees = cc_attendees_for_payment($payment_id);

		// now setup the email tags

		// date
		$last_update = date('jS M Y', strtotime($payment_data['last_update']));

		$type = get_post_meta( $payment_data['workshop_id'], 'type', true );
		if($type == 'webinar'){
			// $workshop_webinar_msg = esc_attr(get_option('ccpa_mailster_webinar_msg'));
			$cancel_fee = '10% of the training fee';
		}else{
			// $workshop_webinar_msg = esc_attr(get_option('ccpa_mailster_workshop_msg'));
			$cancel_fee = '10% of the training fee';
		}
		// $workshop_webinar_msg = str_replace('{my_account_link}', cc_myacct_login_url_set_password($payment_data, 'r'), $workshop_webinar_msg);
		$workshop_webinar_msg = cc_login_account_password_msg($payment_data);

		// thanks msg
		$training_title = ccpa_mailster_tag_workshop_direct($payment_data);
		if( count($attendees) == 1 && $attendees[0]['registrant'] == 'r' ){
			$thanks_msg = 'Thank you for registering to attend:'.$training_title;
		}else{
			$thanks_msg = 'Thank you for registering for:'.$training_title;
			if( count($attendees) == 1 ){
				$thanks_msg .= '<br><br>The following person is registered to attend the training:<ul>';
				$user = get_user_by('ID', $attendees[0]['user_id']);
				$thanks_msg .= '<li>'.$user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')</li>';
			}else{
				$thanks_msg .= '<br><br>The following people are registered to attend the training:<ul>';
				foreach ($attendees as $attendee) {
					$thanks_msg .= '<li>';
					if($attendee['registrant'] == 'r'){
						$thanks_msg .= 'Yourself';
					}else{
						$user = get_user_by('ID', $attendee['user_id']);
						$thanks_msg .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';							
					}
					$thanks_msg .= '</li>';
				}
			}
			$thanks_msg .= '</ul>Information about the training has been sent to them.';
		}

        if($payment_data['status'] == 'Payment not needed' && ( $payment_data['disc_code'] == 'CNWL' || $payment_data['disc_code'] == 'NLFT' || $payment_data['disc_amount'] == 0 ) ){
        	// no payment was necessary because the price was zero
			// firstname will be picked up from the subscriber record
			$mailster_tags = array(
				'workshop' => $training_title,
				'workshop_webinar_msg' => $workshop_webinar_msg,
				'registration_message' => nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) ),
				'thanks_msg' => $thanks_msg,
			);
			$mailster_hook = 'send_reg_workshop_free';
		}else{
			// firstname will be picked up from the subscriber record
			$mailster_tags = array(
				'workshop' => $training_title,
				'amount' => ccpa_payment_data_amount($payment_data),
				'promo_msg' => ccpa_mailster_tag_promo_msg_direct($payment_data),
				'date' => $last_update, // online pmt only
				'customer_address' => ccpa_mailster_tag_customer_address_direct($payment_data), // online pmt only
				'workshop_webinar_msg' => $workshop_webinar_msg,
				'cancel_fee' => $cancel_fee,
				'registration_message' => nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) ),
				'thanks_msg' => $thanks_msg,
			);
			// do_action('send_registration_email', $subscriber_id);
        	// sysctrl_mailster_ar_hook('send_registration_email', $subscriber_id);
        	if($payment_data['pmt_method'] == 'invoice'){
        		$mailster_hook = 'send_reg_workshop_inv';
        	}else{
        		$mailster_hook = 'send_reg_workshop_paid';
        	}
		}

		sysctrl_mailster_ar_hook($mailster_hook, $subscriber_id, $mailster_tags);

		/*
        // if the attendee_email is set then we need to do most of this again for that email address
        if($payment_data['attendee_email'] <> ''){
        	$subscriber = mailster('subscribers')->get_by_mail($payment_data['attendee_email']);
        	if($subscriber){
        		$subscriber_id = $subscriber->ID;
        	}else{
				$userdata = array(
		            'email' => $payment_data['attendee_email'],
			        'firstname' => $payment_data["attendee_firstname"],
			        'lastname' => $payment_data['attendee_lastname'],
		        );
		        $overwrite = true;
		        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
        	}
	        if($subscriber_id > 0){
	        	mailster('subscribers')->assign_lists($subscriber_id, $reg_list_id);
				$mailster_tags['workshop_webinar_msg'] = cc_login_account_password_msg($payment_data, 'a');
				$mailster_tags['thanks_msg'] = $payment_data["firstname"].' '.$payment_data["lastname"].' (we have sent them a copy of this email) has registered for you to attend:';
	    		sysctrl_mailster_ar_hook($mailster_hook, $subscriber_id, $mailster_tags);
	        }
        }
        */

        /* not re-sending emails to attendees
	    foreach ($attendees as $attendee) {
			$user = get_user_by('ID', $attendee['user_id']);
			if($user){
	        	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	        	if($subscriber){
	        		$subscriber_id = $subscriber->ID;
	        	}else{
					// add subscriber
					$userdata = array(
			            'email' => $user->user_email,
			            'firstname' => $user->user_firstname,
			            'lastname' => $user->user_lastname,
			        );
			        $overwrite = true;
			        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
	        	}

	        	$wanted_lists = array($reg_list_id);
		    	$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id']);
		    	if($upsell_list_id > 0){
		    		$wanted_lists[] = cc_mailster_training_list_id($payment_data['upsell_workshop_id']);
		    	}
		    	if($payment_data['type'] == '' && workshop_is_multi_event($payment_data['workshop_id']) && $payment_data['event_ids'] <> ''){
					$event_ids = explode(',', $payment_data['event_ids']);
					foreach ($event_ids as $event_id) {
						if($event_id <> ''){
							$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id'], $event_id);
						}
					}
		    	}
		        if($subscriber_id > 0){
		        	mailster('subscribers')->assign_lists($subscriber_id, $wanted_lists);
			        
			        // now we'll try to send the attendee email
		        	$attendee_msg = 'You are now registered to attend:'.ccpa_mailster_tag_workshop_direct($payment_data);
			        if($attendee['registrant'] <> 'r'){
			        	$attendee_msg .= '<br><br>You have been registered for this training by '.$payment_data["firstname"].' '.$payment_data['lastname'].' ('.$payment_data['email'].').';
			        }
		        	$attendee_msg .= '<br><br>You can view all details about the training (including the joining information) here: <a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>. You will have access to the workshop Live on the day and a recording of the training afterwards. All these details will also be emailed to you shortly before the workshop.';
			        $registration_message = nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) );
			        if($registration_message <> ''){
			        	$attendee_msg .= '<br><br>'.$registration_message;
			        }

		        	$mailster_tags = array(
						'training_title' => get_the_title($payment_data['workshop_id']),
						'attendee_msg' => $attendee_msg,
		        	);
		    		sysctrl_mailster_ar_hook('training_attendee_email', $subscriber_id, $mailster_tags);
		        }
			}
	    }
	    */
	    return true;
	}else{
		return false;
	}
}

// resend registration email for recordings
function ccpa_trigger_reg_email_recording($payment_data){
	// ccpa_write_log('ccpa_trigger_reg_email_recording');
	// ccpa_write_log($payment_data);
	if( $payment_data['status'] == 'Payment not needed' && ( $payment_data['disc_code'] == 'CNWL' || $payment_data['disc_code'] == 'NLFT' || $payment_data['disc_amount'] == 0 ) ){
		$mailster_hook = 'send_reg_recording_free';
	}elseif($payment_data['pmt_method'] == 'online'){
		$mailster_hook = 'send_reg_recording_paid';
	}else{
		$mailster_hook = 'send_reg_recording_inv';
	}
	if($payment_data['reg_userid'] > 0){
		// works for registrations since about Jan 2023
		$user_id = $payment_data['reg_userid'];
	}else{
		$user_id = cc_myacct_get_user($payment_data, 'r');
	}
	$attendees = cc_attendees_for_payment($payment_data['id']);

	// thanks msg
	$training_title = '<strong>'.get_the_title($payment_data['workshop_id']).'</strong>';
	if( count($attendees) == 1 && $attendees[0]['registrant'] == 'r' ){
		$thanks_msg = 'Thank you for registering to watch '.$training_title.'.';
	}else{
		$thanks_msg = 'Thank you for registering for '.$training_title.'.';
		if( count($attendees) == 1 ){
			$thanks_msg .= '<br><br>The following person is registered to watch the training:<ul>';
			$user = get_user_by('ID', $attendees[0]['user_id']);
			$thanks_msg .= '<li>'.$user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')</li>';
		}else{
			$thanks_msg .= '<br><br>The following people are registered to watch the training:<ul>';
			foreach ($attendees as $attendee) {
				$thanks_msg .= '<li>';
				if($attendee['registrant'] == 'r'){
					$thanks_msg .= 'Yourself';
				}else{
					$user = get_user_by('ID', $attendee['user_id']);
					$thanks_msg .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';							
				}
				$thanks_msg .= '</li>';
			}
		}
		$thanks_msg .= '</ul>Information about the training has been sent to them.';
	}

	// recording access expiry
	// We need an attendee (any attendee but not a registrant only) for this
	// ccpa_write_log($attendees);
	$recording_access_expiry = rpm_cc_recordings_expiry_pretty_expiry_datetime( $attendees[0]['user_id'], $payment_data['workshop_id'] );

	$mailster_tags = array(
		'firstname' => $payment_data["firstname"],
		'recording' => $training_title,
		'amount' => ccpa_payment_data_amount($payment_data),
		'promo_msg' => ccpa_mailster_tag_promo_msg_direct($payment_data),
		'set_password' => '', // might have originally had some words here but we'll leave it blank on a resend
		'date' => date('jS M Y', strtotime($payment_data['last_update'])),
		'customer_address' => '', // we don't ask for this
		'registration_message' => nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) ),
		'recording_access_expiry' => $recording_access_expiry,
		'thanks_msg' => $thanks_msg,
	);

	// we need the subscriber id
	$subscriber = mailster('subscribers')->get_by_mail($payment_data['email']);
	if($subscriber){
		$subscriber_id = $subscriber->ID;
	}else{
		// add subscriber
		$userdata = array(
	        'email' => $payment_data['email'],
	        'firstname' => $payment_data['firstname'],
	        'lastname' => $payment_data['lastname'],
	    );
        $overwrite = true;
        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
	}

	sysctrl_mailster_ar_hook($mailster_hook, $subscriber_id, $mailster_tags);

	/* We don't need to resend the email to the attendees as the info is in their My Acct section
    // attendees (including the registrant)
    foreach ($attendees as $attendee) {
		$user = get_user_by('ID', $attendee['user_id']);
		if($user){
        	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
        	if($subscriber){
        		$subscriber_id = $subscriber->ID;
        	}else{
				// add subscriber
				$userdata = array(
		            'email' => $user->user_email,
		            'firstname' => $user->user_firstname,
		            'lastname' => $user->user_lastname,
		        );
		        $overwrite = true;
		        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
        	}

        	$wanted_lists = array();
	    	$wanted_lists[] = cc_mailster_training_list_id($payment_data['workshop_id']);
	        if($subscriber_id > 0){
	        	mailster('subscribers')->assign_lists($subscriber_id, $wanted_lists);
		        
		        // now we'll try to send the attendee email
	        	$attendee_msg = 'You are now registered to view:<br><strong>'.get_the_title($payment_data['workshop_id']).'</strong>';
		        if($attendee['registrant'] <> 'r'){
		        	$attendee_msg .= '<br><br>You have been registered for this training by '.$payment_data["firstname"].' '.$payment_data['lastname'].' ('.$payment_data['email'].').';
		        }
	        	$attendee_msg .= '<br><br>You can access the training here: <a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>. Access to this training is available until '.rpm_cc_recordings_expiry_pretty_expiry_datetime($user->ID, $payment_data['workshop_id']).'.';
		        $registration_message = nl2br( get_post_meta($payment_data['workshop_id'], 'registration_message', true) );
		        if($registration_message <> ''){
		        	$attendee_msg .= '<br><br>'.$registration_message;
		        }

	        	$mailster_tags = array(
					'training_title' => get_the_title($payment_data['workshop_id']),
					'attendee_msg' => $attendee_msg,
	        	);
	    		sysctrl_mailster_ar_hook('training_attendee_email', $subscriber_id, $mailster_tags);
	        }
		}
    }
    */

	return true;
}

// add upcoming training to mailster by way of the {upcoming_training} shortcode
// was a max of 8 cards but now changed to a max of 999 (in case we ever want to change it back)
add_action( 'mailster_add_tag', function(){
	mailster_add_tag( 'upcoming_training', 'mailster_upcoming_training' );
});
function mailster_upcoming_training( $option, $fallback, $campaignID = NULL, $subscriberID = NULL ){
	$html = '';

	// $option can be a comma separated list of workshop IDs
	if($option == ''){
		// look to see if there are any featured events in the future
		$args = array(
			'post_type' => 'workshop',
			'numberposts' => 999,
			'orderby'   => 'meta_value_num',
			'order' => 'ASC',
			'meta_key'  => 'workshop_start_timestamp',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'workshop_start_timestamp',
					'value' => time(),
					'compare' => '>',
				),
				array(
					'key' => 'workshop_featured',
					'value' => 'yes',
					'compare' => '=',
				),
			),
		);
		$featured_workshops = get_posts($args);
	}else{
		$featured_workshops = array();
		$include_ids = array_map('trim', explode(',', $option));
	}
	// do we also need some upcoming events (we always show 8 events if we can)?
	if(count($featured_workshops) < 999){
		$exclude_ids = array();
		foreach ($featured_workshops as $workshop) {
			$exclude_ids[] = $workshop->ID;
		}
		$numberposts = 999 - count($featured_workshops);
		$args = array(
			'post_type' => 'workshop',
			'numberposts' => $numberposts,
			'orderby'   => 'meta_value_num',
			'order' => 'ASC',
			'meta_key'  => 'workshop_start_timestamp',
			'meta_query' => array(
				array(
					'key' => 'workshop_start_timestamp',
					'value' => time(),
					'compare' => '>',
				),
			),
		);
		if(!empty($exclude_ids)){
			$args['post__not_in'] = $exclude_ids;
		}
		if(!empty($include_ids)){
			$args['post__in'] = $include_ids;
		}
		$upcoming_workshops = get_posts($args);
	}else{
		$upcoming_workshops = array();
	}
	$workshops = array_merge($featured_workshops, $upcoming_workshops);

	$num_workshops = count($workshops);
	/*
	if($num_workshops == 3 || $num_workshops == 5 || $num_workshops == 7){
		// get rid of the final entry as an even number always looks tidier
		array_pop($workshops);
	}
	*/

	$align = 'left';
	$html = '<table cellpadding="0" cellspacing="0">';

	foreach ($workshops as $workshop) {
		// we now use the new images
		$file_name = get_post_meta($workshop->ID, '_news_card_img', true);
		if($file_name == ''){
			continue;
		}

		if($align == 'left'){
			$html .= '<tr>';
		}
		
		$html .= '<td valign="top"><table cellpadding="0" cellspacing="0" align="'.$align.'" role="presentation"><tbody><tr><td width="264" valign="top" align="left">';

		$html .= '<a href="'.get_permalink($workshop->ID).'">';
		$html .= '<img src="'.get_stylesheet_directory_uri().'/news-cards/'.$file_name.'" width="274" height="176">';
		$html .= '</a>';

		$html .= '</td></tr></tbody></table></td>';
		
		if($align == 'left'){
			$html .= '<td width="24">&nbsp;</td>';
			$align = 'right';
		}else{
			$html .= '</tr><tr><td colspan="3" height="20">&nbsp;</td></tr>';
			$align = 'left';
		}
	}
	if($align == 'right'){
		$html .= '<td>&nbsp;</td></tr><tr><td colspan="3" height="20">&nbsp;</td></tr>';
	}
	$html .= '</table>';
	return $html;
}

// training card
// added as a full-size image to the newsletter
add_action( 'mailster_add_tag', function(){
	mailster_add_tag( 'training_card', 'mailster_training_card' );
});
function mailster_training_card( $option, $fallback, $campaignID = NULL, $subscriberID = NULL ){
	$html = '';

	// $option should be a training ID
	$option = absint( $option );
	if($option == 0){
		return '';
	}

	$file_name = get_post_meta( $option, '_news_card_v2_img', true );
	if( $file_name == '' ){
		return '';
	}

	$html .= '<a href="'.get_permalink($option).'">';
	$html .= '<img src="'.get_stylesheet_directory_uri().'/news-cards/'.$file_name.'" width="600" height="375">';
	$html .= '</a>';

	return $html;
}



/**
 * intercept link clicks
 * Fires if user clicks on a link and tracking is enabled
 *
 * @param int $subscriber_id The ID of the subscriber
 * @param int $campaign_id Form The ID of the campaign
 * @param string $target The target link
 * @param int $index The index of the link
 */
add_action('mailster_click', 'cc_mailster_mailster_click', 10, 5);
function cc_mailster_mailster_click($subscriber_id, $campaign_id, $target, $index, $campaign_index){
	// ccpa_write_log('cc_mailster_mailster_click');
	// ccpa_write_log('subs_id:'.$subscriber_id.' campaign:'.$campaign_id.' target:'.$target);
	// if it's a Zoom link then track it ... used for CNWL people ... but tracked for everybody
	if(strpos($target, 'zoom.us')){
		// we need the workshop id
		if($link_found = cc_mailster_workshop_id_from_zoom_link($target)){
			// something to record ... so we need the user id
			if($user_id = cc_mailster_get_userid($subscriber_id)){
				// track it
				if($link_found['event_id'] === NULL){
					update_user_meta($user_id, 'zoomed w:'.$link_found['workshop_id'].' e:null', date('Y-m-d'));
				}else{
					update_user_meta($user_id, 'zoomed w:'.$link_found['workshop_id'].' e:'.$link_found['event_id'], date('Y-m-d'));
				}
			}
		}
	}
	// ccpa_write_log('link found:');
	// ccpa_write_log($link_found);
	// ccpa_write_log('user_id:'.$user_id);
}

// return the workshop id (and event id if appropriate)
function cc_mailster_workshop_id_from_zoom_link($zoom_link){
	// ccpa_write_log('cc_mailster_workshop_id_from_zoom_link');
	// ccpa_write_log('link:'.$zoom_link);
	$args = array(
    	'post_type' => 'workshop',
    	'posts_per_page' => -1,
    	'fields' => 'ids',
	);
	$workshop_ids = get_posts($args); // latest date first
	// ccpa_write_log('num workshops:'.count($workshop_ids));
	// ccpa_write_log($workshop_ids);
	$thirty_mins_ahead = strtotime('+ 30 mins');
	foreach ($workshop_ids as $workshop_id) {
		$workshop_zoom = get_post_meta($workshop_id, 'workshop_zoom', true);
		if($workshop_zoom == $zoom_link){
			// We only want to count this click if it occurs from 30 mins before the start of the workshop
			$workshop_start_timestamp = (int) get_post_meta($workshop_id, 'workshop_start_timestamp', true);
			if($thirty_mins_ahead > $workshop_start_timestamp){
				return array(
					'workshop_id' => $workshop_id,
					'event_id' => NULL,
				);
			}
			// clicked too early ... we'll ignore it
			return false;
		}
	}
	// not a workshop zoom link ... maybe an individual event?
	foreach ($workshop_ids as $workshop_id) {
		if(workshop_is_multi_event($workshop_id)){
			for($i = 1; $i<16; $i++) { 
				$event_zoom = get_post_meta($workshop_id, 'event_'.$i.'_zoom', true);
				if($event_zoom == $zoom_link){
					$event_date_time = get_post_meta($workshop_id, 'event_'.$i.'_date', true).' '.get_post_meta($workshop_id, 'event_'.$i.'_time', true); // dd/mm/yyyy hh:ii
					if($event_date_time <> ''){
						$date = DateTime::createFromFormat("d/m/Y H:i", $event_date_time, new DateTimeZone('UTC'));
						if($date){
							$event_start_timestamp = $date->getTimestamp();
							if($thirty_mins_ahead > $event_start_timestamp){
								return array(
									'workshop_id' => $workshop_id,
									'event_id' => $i,
								);
							}
						}
					}
					return false;
				}
			}
		}
	}
	return false;
}

// looks up the user from the subscriber
function cc_mailster_get_userid($subscriber_id){
	$subscriber = mailster('subscribers')->get($subscriber_id);
	if($subscriber){
		$user = get_user_by('email', $subscriber->email);
		if($user){
			return $user->ID;
		}
	}
	return false;
}

// gets activity for a specific Mailster list
// replacement for mailster/classes/actions.class.php/get_list_activity as that is VERY VERY slow!
// we're also limiting the function to only return the last month of activities
function cc_mailster_get_list_activity($list_id){
	global $wpdb;
	/*
	we're replacing ...

	SELECT 
		p.post_title AS campaign_title, a.*, l.link 
	FROM (
		SELECT 'sent' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, NULL AS text FROM wp_mailster_action_sent 
		UNION SELECT 'open' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, NULL AS text FROM wp_mailster_action_opens 
		UNION SELECT 'click' AS type, subscriber_id, campaign_id, timestamp, count, link_id, NULL AS text FROM wp_mailster_action_clicks 
		UNION SELECT 'unsub' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM wp_mailster_action_unsubs 
		UNION SELECT 'softbounce' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM wp_mailster_action_bounces WHERE hard = 0 
		UNION SELECT 'bounce' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM wp_mailster_action_bounces WHERE hard = 1 
		UNION SELECT 'error' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM wp_mailster_action_errors) 
		AS a 
	LEFT JOIN `wp_posts` as p ON p.ID = a.campaign_id 
	LEFT JOIN `wp_mailster_links` AS l ON l.ID = a.link_id 
	LEFT JOIN wp_mailster_lists_subscribers AS ab ON a.subscriber_id = ab.subscriber_id 
	WHERE 1 AND ab.list_id = 18  
	GROUP BY a.type, a.link_id 
	ORDER BY a.timestamp DESC, a.type DESC
	
	which must give ...

	Array (
	    [0] => stdClass Object (
	            [campaign_title] => Registration Workshop/Webinar Paid
	            [type] => click
	            [subscriber_id] => 1308
	            [campaign_id] => 439
	            [timestamp] => 1633535616
	            [count] => 1
	            [link_id] => 40
	            [text] => 
	            [link] => https://contextual.roderickpughmarketing.com/my-account/?login=joeoliver90%40gmail.com
	        )
	    [1] => stdClass Object (
	            [campaign_title] => Registration Workshop/Webinar Paid
	            [type] => open
	            [subscriber_id] => 1308
	            [campaign_id] => 439
	            [timestamp] => 1633535614
	            [count] => 8
	            [link_id] => 
	            [text] => 
	            [link] => 
	        )
	    [2] => stdClass Object (
	            [campaign_title] => Recording Payment
	            [type] => sent
	            [subscriber_id] => 1
	            [campaign_id] => 366
	            [timestamp] => 1587329311
	            [count] => 8
	            [link_id] => 
	            [text] => 
	            [link] => 
	        )
	)
	*/
	// let's start by getting the subscribers
	$sql = "SELECT subscriber_id FROM {$wpdb->prefix}mailster_lists_subscribers WHERE list_id = ".(int) $list_id;
	$subscriber_ids = $wpdb->get_col($sql);
	if(empty($subscriber_ids)) return array();
	// we need the subscriber ids as a list for mysql
	$subscriber_ids_list = "('".implode("','", $subscriber_ids)."')";
	// now get the activities
	$one_month_ago = strtotime("-1 month");
	$sql = "SELECT 'sent' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, NULL AS text FROM {$wpdb->prefix}mailster_action_sent WHERE timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$sent_activities = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT 'open' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, NULL AS text FROM {$wpdb->prefix}mailster_action_opens WHERE timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$open_activities = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT 'click' AS type, subscriber_id, campaign_id, timestamp, count, link_id, NULL AS text FROM {$wpdb->prefix}mailster_action_clicks WHERE timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$click_activities = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT 'unsub' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM {$wpdb->prefix}mailster_action_unsubs WHERE timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$unsub_activities = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT 'softbounce' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM {$wpdb->prefix}mailster_action_bounces WHERE hard = 0 AND timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$softbounce_activities = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT 'bounce' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM {$wpdb->prefix}mailster_action_bounces WHERE hard = 1 AND timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$bounce_activities = $wpdb->get_results($sql, ARRAY_A);
	$sql = "SELECT 'error' AS type, subscriber_id, campaign_id, timestamp, count, NULL AS link_id, text FROM {$wpdb->prefix}mailster_action_errors WHERE timestamp > $one_month_ago AND subscriber_id IN ".$subscriber_ids_list;
	$error_activities = $wpdb->get_results($sql, ARRAY_A);
	$activities = array_merge($sent_activities, $open_activities, $click_activities, $unsub_activities, $softbounce_activities, $bounce_activities, $error_activities);
	// let's put them into descending timestamp order
	usort($activities, function($a, $b){
		return $b['timestamp'] <=> $a['timestamp'];
	});
	// now we need to add some extra info to the activities
	$new_activities = array();
	foreach ($activities as $activity) {
		$new_activity = new stdClass();
		if($activity['campaign_id'] > 0){
			$sql = "SELECT post_title FROM {$wpdb->posts} WHERE ID = ".$activity['campaign_id'];
			$new_activity->campaign_title = $wpdb->get_var($sql);
		}else{
			$new_activity->campaign_title = '';
		}
		$new_activity->type = $activity['type'];
		$new_activity->subscriber_id = $activity['subscriber_id'];
		$new_activity->campaign_id = $activity['campaign_id'];
		$new_activity->timestamp = $activity['timestamp'];
		$new_activity->count = $activity['count'];
		$new_activity->link_id = $activity['link_id'];
		$new_activity->text = $activity['text'];
		if($activity['link_id'] > 0){
			$sql = "SELECT link FROM {$wpdb->prefix}mailster_links WHERE ID = ".$activity['link_id'];
			$new_activity->link = $wpdb->get_var($sql);
		}else{
			$new_activity->link = '';
		}
		$new_activities[] = $new_activity;
	}
	return $new_activities;
}


// add a metabox for the stats to signify that a newsletter is promoting specific training
add_action('add_meta_boxes_newsletter', 'cc_mailster_add_stats_metabox');
function cc_mailster_add_stats_metabox(){
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	add_meta_box( 'cc_mailster_stats', 'For Stats', 'cc_mailster_render_stats_metabox', 'newsletter', 'side', 'low' );
}
function cc_mailster_render_stats_metabox($post){
	$cc_mailster_stats_training_id = get_post_meta($post->ID, 'cc_mailster_stats_training_id', true);
	// we need to find all upcoming workshops as well as all recordings
	$workshops = workshop_archive_get_posts();
	$recordings = recording_get_all_available('title');
	?>
	<style>#cc_mailster_stats {display: inherit;}</style>
	<label for="cc_mailster_stats_training_id">Promoted training:</label>
	<select name="cc_mailster_stats_training_id" id="cc_mailster_stats_training_id">
		<option value="">No specific training being promoted</option>
		<?php foreach ($workshops as $workshop) { ?>
			<option value="<?php echo $workshop->ID; ?>" <?php selected($cc_mailster_stats_training_id, $workshop->ID); ?>>W: <?php echo $workshop->post_title; ?></option>
		<?php }
		foreach ($recordings as $recording) { ?>
			<option value="<?php echo $recording->ID; ?>" <?php selected($cc_mailster_stats_training_id, $recording->ID); ?>>R: <?php echo $recording->post_title; ?></option>
		<?php } ?>
	</select>
	<?php
}

add_action('save_post', 'cc_mailster_stats_metabox_save', 10, 2);
function cc_mailster_stats_metabox_save($post_id, $post){
	if( isset($_POST['cc_mailster_stats_training_id']) && $post->post_type == 'newsletter' ){
		update_post_meta($post_id, 'cc_mailster_stats_training_id', $_POST['cc_mailster_stats_training_id']);
	}
}

// find all newsletters that promoted a given training id between a specific time range
// returns an array of promoted training ids with their newsletter post id
function cc_mailster_promoted_training($starttime, $endtime){
	global $wpdb;
	// what newsletters went out during the week?
	$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_mailster_timestamp' AND meta_value >= $starttime AND meta_value < $endtime";
	$sent_news = $wpdb->get_col($sql);
	$promoted = array();
	foreach ($sent_news as $post_id) {
		// has the newsletter actually been sent?
		if(get_post_status($post_id) <> 'finished') continue;
		// was this newsletter promoting specific training?
		$training_id = get_post_meta($post_id, 'cc_mailster_stats_training_id', true);
		if($training_id <> ''){
			$promoted[$training_id] = $post_id;
		}
	}
	return $promoted;
}

// send the refer a friend credit issue email
// sent to the raf code given when a friend uses it
function cc_mailster_raf_credit_issue( $user_id ){
	$user = get_user_by( 'ID', $user_id );
	if( $user ){

		// we need the subscriber id
    	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
    	if($subscriber){
    		$subscriber_id = $subscriber->ID;
    	}else{
			// add subscriber
			$userdata = array(
	            'email' => $user->user_email,
	            'firstname' => $user->user_firstname,
	            'lastname' => $user->user_lastname,
	        );
	        $overwrite = true;
	        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
    	}

    	// send the email
    	$mailster_tags = array();
		sysctrl_mailster_ar_hook( 'raf_credit_issued', $subscriber_id, $mailster_tags);

	}
}

// gets or creates a subscriber_id
// note: does not update region
function cc_mailster_get_subscriber_id( $email, $firstname, $lastname, $update_subs=true ){
	$userdata = array(
        'email' => $email,
        'firstname' => $firstname,
        'lastname' => $lastname,
    );
	$subscriber = mailster( 'subscribers' )->get_by_mail( $email );
	if($subscriber){
		$subscriber_id = $subscriber->ID;
		if( $update_subs ){
			$result = mailster( 'subscribers' )->update( $userdata );
		}
	}else{
		// add subscriber
        $overwrite = true;
        $subscriber_id = mailster('subscribers')->add($userdata, $overwrite);
	}
	return $subscriber_id;
}

// gets a subscriber's region
// only need to supply subs_id or email, not both
// returns '' if not set
function cc_mailster_get_region( $subscriber_id=0, $email='' ){
	// ccpa_write_log('functiopn cc_mailster_get_region');
	if( $subscriber_id > 0 ){
		$subscriber = mailster('subscribers')->get( $subscriber_id );
	}else{
		$subscriber = mailster('subscribers')->get_by_mail( $email );
	}
	if( $subscriber ){
		// ccpa_write_log( mailster('subscribers')->get_custom_fields( $subscriber->ID ) );
		$region = mailster('subscribers')->get_custom_fields( $subscriber->ID, 'region' );
		if( $region == null ){
			return '';
		}else{
			return $region;
		}
	}
	return '';
}

// update region (if subscriber exists)
// returns true if updated, false if not
function cc_mailsterint_update_region( $user ){
	$user_country = get_user_meta( $user->ID, 'address_country', true );
	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	if( $subscriber && $user_country <> ''){
		$curr_region = mailster('subscribers')->get_custom_fields( $subscriber->ID, 'region' );
		$new_region = cc_country_region( $user_country );
		if( $new_region <> $curr_region ){
			mailster('subscribers')->add_custom_value( $subscriber->ID, 'region', $new_region );
			return true;
		}
	}
	return false;
}

// bulk update of region
add_shortcode( 'bulk_region_update', 'cc_mailsterint_bulk_region_update' );
function cc_mailsterint_bulk_region_update(){
	global $wpdb;
	// we can only do this for users that have a country so let's start by getting all of them
	$sql = "SELECT * FROM $wpdb->usermeta WHERE meta_key = 'address_country' AND meta_value != ''";
	$metas = $wpdb->get_results( $sql, ARRAY_A );
	$html = '<br>bulk_region_update '.count( $metas ).' users found with countries, ';
	$count_updates = 0;
	foreach ($metas as $meta) {
		$user = get_user_by( 'ID', $meta['user_id'] );
		if( cc_mailsterint_update_region( $user ) ){
			$count_updates ++;
		}
	}
	$html .= $count_updates.' were updated.';
	return $html;
}

// gets the subscriber_id for the user by user_id
function cc_mailster_subs_id_of_user( $user_id ){
	$user = get_user_by( 'ID', $user_id );
	if( $user ){
    	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
    	if($subscriber){
    		return $subscriber->ID;
    	}
		// add subscriber
		$userdata = array(
            'email' => $user->user_email,
            'firstname' => $user->user_firstname,
            'lastname' => $user->user_lastname,
        );
        $overwrite = true;
        return mailster('subscribers')->add($userdata, $overwrite);
    }
    return false;
}

// workaround for out of memory issue with Mailster subscribers get_by_mail method
function cc_mailster_get_subscriber_by_mail( $email, $custom_fields = false, $include_deleted = false ) {
    global $wpdb;
    
    // Check cache first if looking for a subscriber with custom fields
    if ( $custom_fields ) {
        // Try to find by cached email lookup
        $cache_key = 'subscriber_email_' . md5( $email );
        $cached_id = mailster_cache_get( $cache_key );
        
        if ( $cached_id && $cached_id !== 'not_found' ) {
            $cached_subscriber = mailster_cache_get( 'subscriber_' . $cached_id );
            if ( $cached_subscriber ) {
                // Verify it's still the correct email and status
                if ( $cached_subscriber->email === $email ) {
                    if ( $include_deleted || $cached_subscriber->status != 5 ) {
                        return $cached_subscriber;
                    }
                }
            }
        } elseif ( $cached_id === 'not_found' ) {
            return false;
        }
    }
    
    // Build the query
    $sql = $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mailster_subscribers 
         WHERE email = %s",
        $email
    );
    
    if ( ! $include_deleted ) {
        $sql .= " AND status != 5";
    }
    
    $sql .= " LIMIT 1";
    
    $subscriber = $wpdb->get_row( $sql );
    
    if ( is_wp_error( $subscriber ) ) {
        cc_debug_log_anything([
            'function' => 'cc_mailster_get_subscriber_by_mail',
            'email' => $email,
            'error' => $subscriber->get_error_message(),
        ]);
        return false;
    }
    
    if ( $subscriber === NULL ) {
        // Cache the "not found" result for 5 minutes to prevent repeated lookups
        if ( $custom_fields ) {
            mailster_cache_set( 'subscriber_email_' . md5( $email ), 'not_found', 300 );
        }
        return false;
    }
    
    // Type cast the numeric fields
    $subscriber->ID      = (int) $subscriber->ID;
    $subscriber->wp_id   = (int) $subscriber->wp_id;
    $subscriber->status  = (int) $subscriber->status;
    $subscriber->added   = (int) $subscriber->added;
    $subscriber->updated = (int) $subscriber->updated;
    $subscriber->signup  = (int) $subscriber->signup;
    $subscriber->confirm = (int) $subscriber->confirm;
    $subscriber->rating  = (float) $subscriber->rating;
    
    // Only fetch custom fields if requested
    if ( $custom_fields && $subscriber->ID ) {
        // Use the optimized version that matches the original behavior
        $custom_fields_data = cc_mailster_get_custom_fields_optimized( $subscriber->ID );
        
        if ( $custom_fields_data ) {
            $subscriber = (object) wp_parse_args( $custom_fields_data, (array) $subscriber );
        }
        
        // Cache the complete subscriber object
        mailster_cache_set( 'subscriber_' . $subscriber->ID, $subscriber );
        
        // Also cache the email -> ID mapping
        mailster_cache_set( 'subscriber_email_' . md5( $email ), $subscriber->ID );
    }
    
    return $subscriber;
}

/**
 * Optimized custom fields retrieval
 */
function cc_mailster_get_custom_fields_optimized( $subscriber_id, $field = null ) {
    global $wpdb;
    
    // Try to get from cache first
    $cache_key = 'get_custom_fields_' . $subscriber_id;
    $custom_fields = mailster_cache_get( $cache_key );
    
    if ( false === $custom_fields ) {
        // Get all registered custom field names
        $custom_field_names = mailster()->get_custom_fields( true );
        
        // Initialize array with all fields set to null
        $custom_fields = array_fill_keys( $custom_field_names, null );
        
        // Add the standard name fields
        $custom_fields['firstname'] = '';
        $custom_fields['lastname']  = '';
        $custom_fields['fullname']  = '';
        
        // Get the actual values from database
        // Note: Using the correct table name
        $sql = $wpdb->prepare( 
            "SELECT meta_key, meta_value 
             FROM {$wpdb->prefix}mailster_subscriber_fields 
             WHERE subscriber_id = %d", 
            $subscriber_id 
        );
        
        $meta_data = $wpdb->get_results( $sql );
        
        // Populate the values
        foreach ( $meta_data as $data ) {
            $custom_fields[ $data->meta_key ] = $data->meta_value;
        }
        
        // Build the fullname field
        $custom_fields['fullname'] = trim(
            mailster_option( 'name_order' )
            ? $custom_fields['lastname'] . ' ' . $custom_fields['firstname']
            : $custom_fields['firstname'] . ' ' . $custom_fields['lastname']
        );
        
        // Only cache if we're getting all fields
        if ( is_null( $field ) ) {
            mailster_cache_set( $cache_key, $custom_fields );
        }
    }
    
    // Return specific field or all fields
    if ( is_null( $field ) ) {
        return $custom_fields;
    }
    
    return isset( $custom_fields[ $field ] ) ? $custom_fields[ $field ] : null;
}

