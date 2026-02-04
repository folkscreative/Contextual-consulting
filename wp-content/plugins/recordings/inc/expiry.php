<?php
/**
 * Recordings expiry functions
 */

// gets expiry details for a recording
// always returns an array (with defaults if no data held)
// expanded also returns useful text fields based on the meta data found
function rpm_cc_recordings_get_expiry($post_id, $expanded=false){
    $recording_expiry = get_post_meta($post_id, 'recording_expiry', true);
    if(!is_array($recording_expiry)){
        $recording_expiry = array(
            'num' => 0,
            'unit' => '',
            'when_set' => 0,
            'reset_status' => '',
            'last_reset' => 0,
        );
    }
    if($expanded){
        if($recording_expiry['num'] == 0 || $recording_expiry['unit'] == ''){
            $recording_expiry['expiry_text'] = 'unlimited';
            $recording_expiry['string_to_time'] = '';
        }else{
            $recording_expiry['expiry_text'] = $recording_expiry['num'].' '.$recording_expiry['unit'];
            $recording_expiry['string_to_time'] = '+'.$recording_expiry['num'].' '.$recording_expiry['unit'];
        }
        if($recording_expiry['when_set'] == 0){
            $recording_expiry['when_set_text'] = 'never';
        }else{
            $recording_expiry['when_set_text'] = date('d/m/Y H:i:s', $recording_expiry['when_set']);
        }
        if($recording_expiry['last_reset'] == 0){
            $recording_expiry['last_reset_text'] = 'never';
        }else{
            $recording_expiry['last_reset_text'] = date('d/m/Y H:i:s', $recording_expiry['last_reset']);
        }
    }
    return $recording_expiry;
}


// start the recording expiry reset 
add_action('wp_ajax_rpm_cc_recordings_expiry_reset_start', 'rpm_cc_recordings_expiry_reset_start');
function rpm_cc_recordings_expiry_reset_start(){
	$response = array(
		'status' => 'error',
		'msg' => '',
	);
	$recording_id = 0;
	if(isset($_POST['recording'])){
		$recording_id = absint($_POST['recording']);
	}
	if($recording_id == 0){
		$response['msg'] = 'Unable to find that recording';
	}else{
		$date_fields = array('startdate', 'enddate', 'expirydate');
		$dates_ok = true;
		$dates = array(
			'startdate' => '0000-00-00 00:00:00',
			'enddate' => '9999-12-31 23:59:59',
			'expirydate' => '',
		);
		foreach ($date_fields as $date_field) {
			if(isset($_POST[$date_field]) && $_POST[$date_field] <> ''){
				if($date_field == 'startdate'){
					$mins = ' 00:00:00';
				}else{
					$mins = ' 23:59:59';
				}
				$date = DateTime::createFromFormat('d/m/Y H:i:s', $_POST[$date_field].$mins);
				if($date){
					$dates[$date_field] = $date->format('Y-m-d H:i:s');
				}else{
					$response['msg'] .= 'Invalid date entered. Please use d/m/YYYY (or leave blank). '.$_POST[$date_field].' found. ';
					$dates_ok = false;
				}
			}
		}
		if($dates['startdate'] <> '' && $dates['enddate'] <> ''){
			if($dates['enddate'] < $dates['startdate']){
				$response['msg'] .= 'End date must be the same or later than start date.';
			}
		}
		if($response['msg'] == ''){
			// input data apparently ok
			// let's log the fact that we're starting the queueing process
			$recording_expiry = rpm_cc_recordings_get_expiry($recording_id);
			$recording_expiry['reset_status'] = 'queueing';
			$recording_expiry['count'] = 999; // just a random number until we know the real number
			$recording_expiry['completed'] = 0;
			$reset_group = uniqid();
			$recording_expiry['reset_group'] = $reset_group;
			update_post_meta($recording_id, 'recording_expiry', $recording_expiry);
			// Now let's get the queue building going ...
			$emails = 'no';
			if(isset($_POST['emails']) && $_POST['emails'] == 'yes'){
				$emails = 'yes';
			}
			/*
			as_enqueue_async_action = Enqueue an action to run one time, as soon as possible.
			Parameters
				$hook (string)(required) Name of the action hook.
				$args (array) Arguments to pass to callbacks when the hook triggers. Default: array().
				$group (string) The group to assign this job to. Default: '’.
			*/
			$queue_info = array(
				'recording' => $recording_id,
				'emails' => $emails,
				'startdate' => $dates['startdate'],
				'enddate' => $dates['enddate'],
				'expirydate' => $dates['expirydate'],
				'reset_group' => $reset_group,
			);
			as_enqueue_async_action( 'rpm_cc_recordings_build_queue', array('queue_info' => $queue_info), $reset_group );
			$response['status'] = 'ok';
			$response['msg'] = 'Expiry reset starting';
		}
	}
   	echo json_encode($response);
	die();
}

// the job to queue all the required users
// runs in the background cos it could take some time
add_action('rpm_cc_recordings_build_queue', 'rpm_cc_recordings_build_queue');
function rpm_cc_recordings_build_queue($queue_info){
	$recording_id = $queue_info['recording'];
	$emails = $queue_info['emails'];
	$startdate = $queue_info['startdate'];
	$enddate = $queue_info['enddate'];
	$expirydate = $queue_info['expirydate'];
	$reset_group = $queue_info['reset_group'];
	// get all users
	$args = array(
		'orderby' => 'ID',
		'fields' => 'ID',
	);
	$user_ids = get_users($args);
	$count_users = count($user_ids);
	// update the status
	$recording_expiry = rpm_cc_recordings_get_expiry($recording_id);
	$recording_expiry['reset_status'] = 'running';
	$recording_expiry['count'] = $count_users + 1; // plus one to allow for this queueing job too
	update_post_meta($recording_id, 'recording_expiry', $recording_expiry);
	// now queue all users
	/*
	as_enqueue_async_action = Enqueue an action to run one time, as soon as possible.
	Parameters
		$hook (string)(required) Name of the action hook.
		$args (array) Arguments to pass to callbacks when the hook triggers. Default: array().
		$group (string) The group to assign this job to. Default: '’.
	*/
	foreach ($user_ids as $user_id) {
		$queue_item = array(
			'recording' => $recording_id,
			'user' => $user_id,
			'emails' => $emails,
			'startdate' => $startdate,
			'enddate' => $enddate,
			'expirydate' => $expirydate,
		);
		as_enqueue_async_action( 'rpm_cc_recordings_expiry_task', array('queue_item' => $queue_item), $reset_group );
	}
}

// the expiry reset task that updates 1 user at a time (if required)
// Action Scheduler (https://actionscheduler.org/) should limit this to running about 25 of these asynchronously
add_action('rpm_cc_recordings_expiry_task', 'rpm_cc_recordings_expiry_task');
function rpm_cc_recordings_expiry_task($queue_item){
	ccpa_write_log('start rpm_cc_recordings_expiry_task');
	// ccpa_write_log($queue_item);
	$recording_id = $queue_item['recording'];
	$user_id = $queue_item['user'];
	$emails = $queue_item['emails'];
	$startdate = $queue_item['startdate'];
	$enddate = $queue_item['enddate'];
	$expirydate = $queue_item['expirydate'];
	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
	$recording_meta = get_recording_meta( $user_id, $recording_id );
	if($recording_meta){
		ccpa_write_log($recording_meta);
		if(isset($recording_meta['closed_type']) && $recording_meta['closed_type'] == 'manual'){
			// don't mess with this one
		}else{
			// was this user given access during the correct date range?
			if($recording_meta['access_time'] >= $startdate && $recording_meta['access_time'] <= $enddate){
				// set closing time
				if($expirydate == ''){
					// calculate expiry
					$recording_expiry = rpm_cc_recordings_get_expiry($recording_id, true);
					ccpa_write_log($recording_expiry);
					if($recording_expiry['string_to_time'] == ''){
						// no end date
						$recording_meta['closed_time'] = '';
					}else{
						if($recording_meta['closed_time'] == '' || $recording_meta['access_time'] == ''){
							// never set before (or access_time missing for some reason), we'll set closing time based on now
							$recording_meta['closed_time'] = date('Y-m-d', strtotime($recording_expiry['string_to_time'])).' 23:59:59';
						}else{
							// set before so we base closing time on the access time
							$date = new DateTime($recording_meta['access_time']);
							$date->modify($recording_expiry['string_to_time']);
							$recording_meta['closed_time'] = $date->format('Y-m-d H:i:s');
						}
					}
				}else{
					// Joe's specified closing time to be used ...
					$recording_meta['closed_time'] = $expirydate;
				}
				$recording_meta['closed_type'] = 'auto';
				ccpa_write_log('and updating with ...');
				ccpa_write_log($recording_meta);
				update_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, $recording_meta);
				if($emails == 'yes'){
					rpm_cc_recordings_send_new_expiry_email($user_id, $recording_id);
				}
			}else{
				// outside the access date range - ignore
			}
		}
	}else{
		// user does not have access to this recording - ignore
	}
	// ccpa_write_log('end rpm_cc_recordings_expiry_task');
}

// sends an email to tell the viewer what the new recording access expiry is
function rpm_cc_recordings_send_new_expiry_email($user_id, $recording_id){
	$user_info = get_userdata($user_id);
	$mailster_tags = array(
		'firstname' => $user_info->first_name,
		'recording' => get_the_title($recording_id),
		'recording_access_expiry' => rpm_cc_recordings_expiry_pretty_expiry_datetime($user_id, $recording_id),
	);
	$mailster_hook = 'send_recording_expiry_update';
	$subscriber = mailster('subscribers')->get_by_mail($user_info->user_email);
	if($subscriber){
		sysctrl_mailster_ar_hook($mailster_hook, $subscriber->ID, $mailster_tags);
	}
}

// returns pretty recording access expiry date/time
function rpm_cc_recordings_expiry_pretty_expiry_datetime($user_id, $recording_id){
	// ccpa_write_log('rpm_cc_recordings_expiry_pretty_expiry_datetime userid:'.$user_id.' recording_id:'.$recording_id);
	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
	$recording_meta = get_recording_meta( $user_id, $recording_id );
	// ccpa_write_log($recording_meta);
	if($recording_meta){
		if($recording_meta['closed_time'] == ''){
			return 'further notice';
		}else{
			$date = new DateTime($recording_meta['closed_time']);
			if($date){
				return $date->format('jS M Y');
			}
		}
	}
	return '';
}

// gets the reset progress
add_action('wp_ajax_rpm_cc_recordings_expiry_progress', 'rpm_cc_recordings_expiry_progress');
function rpm_cc_recordings_expiry_progress(){
	// ccpa_write_log('rpm_cc_recordings_expiry_progress');
	$response = array(
		'status' => 'error',
		'msg' => '',
		'count' => 0,
		'completed' => 0,
		'percent' => 0,
	);
	$recording_id = 0;
	if(isset($_POST['recording'])){
		$recording_id = absint($_POST['recording']);
	}
	if($recording_id == 0){
		$response['msg'] = 'Unable to find that recording';
	}else{
		$recording_expiry = rpm_cc_recordings_get_expiry($recording_id);
		// ccpa_write_log('recording_expiry');
		// ccpa_write_log($recording_expiry);
		if($recording_expiry['reset_status'] <> 'running' && $recording_expiry['reset_status'] <> 'queueing' && $recording_expiry['reset_status'] <> 'complete'){
			$response['msg'] = 'Reset not running';
		}else{
			$response['status'] = 'ok';
			$response['count'] = $recording_expiry['count'];
			$args = array(
				'group' => $recording_expiry['reset_group'],
				'status' => 'complete',
				'per_page' => -1,
			);
			// ccpa_write_log('args');
			// ccpa_write_log($args);
			$status = as_get_scheduled_actions( $args, 'ids' ); // Note: ARRAY_A does not seem to work, but ids is probably better anyway
			// ccpa_write_log('status');
			// ccpa_write_log($status);
			$completed = count($status);
			$response['completed'] = $completed;
			if($completed == 0){
				$response['percent'] = 0;
			}else{
				$response['percent'] = floor($completed / $recording_expiry['count'] * 100);
			}
			if($completed == $recording_expiry['count']){
				$recording_expiry['completed'] = $completed;
				$recording_expiry['last_reset'] = time();
				$recording_expiry['reset_status'] = 'complete';
				update_post_meta($recording_id, 'recording_expiry', $recording_expiry);
			}
		}
	}
   	echo json_encode($response);
	die();
}
