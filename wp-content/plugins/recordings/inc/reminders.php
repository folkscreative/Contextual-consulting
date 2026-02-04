<?php
/**
 * Recordings reminder functions
 */

// schedule the daily hook
add_action('init', function(){
	if(!wp_next_scheduled('cc_reminders_cron')){
		wp_schedule_event( strtotime('tomorrow'), 'daily', 'cc_reminders_cron');
	}
});

// for testing only .....
add_shortcode('force_send_recording_reminders', 'cc_reminders_cronjob');

// run the daily check
add_action('cc_reminders_cron', 'cc_reminders_cronjob');
function cc_reminders_cronjob(){
	global $rpm_theme_options;

	$html = 'Sending recording reminders';

	$html .= print_r($rpm_theme_options['od-reminders'], true);

	$start_times = $end_times = array();
	foreach ($rpm_theme_options['od-reminders'] as $days) {
		$days = absint($days);
		if( $days > 0 ){
			$ymd = date( 'Y-m-d', strtotime( '+'.$days.' days' ) );
			$start_times[] = $ymd.' 00:00:00';
			$end_times[] = $ymd.' 23:59:59';
		}
	}

	$times = count( $start_times );

	$html .= '<br>'.$times.' time steps identified';
	for ($i=0; $i < $times; $i++) { 
		$html .= '<br>'.$start_times[$i].' - '.$end_times[$i];
	}

	/* was ...
	$first_dtime = date('Y-m-d', strtotime('+3 days')).' 00:00:00';
	$last_dtime = date('Y-m-d', strtotime('+4 days')).' 00:00:00';
	*/

	// go through all users
	$args = array(
		'orderby' => 'ID',
		'fields' => 'ID',
	);
	$user_ids = get_users($args);

	$html .= '<br>'.count($user_ids).' users';
	$count_reminders = 0;

	foreach ($user_ids as $user_id) {
		// look for one or more recordings that are about to expire
		$metas = get_user_meta($user_id);
		foreach ($metas as $key => $value) {
			// we only want training level metas
			// if(substr($key, 0, 13) == 'cc_rec_wshop_'){
			if( preg_match( '/^cc_rec_wshop_\d+$/', $key ) ){
				$recording_meta = maybe_unserialize($value[0]);
				$recording_id = substr($key, 13);
				$recording_meta = sanitise_recording_meta( $recording_meta, $user_id, $recording_id );
				// if(isset($recording_meta['closed_time']) && $recording_meta['closed_time'] > $first_dtime && $recording_meta['closed_time'] <= $last_dtime){
				if(isset($recording_meta['closed_time']) && $recording_meta['closed_time'] <> ''){
					for ($i=0; $i < $times; $i++) { 
						if( $recording_meta['closed_time'] >= $start_times[$i] && $recording_meta['closed_time'] <= $end_times[$i] ){
							// trigger emails warning them of the upcoming expiry
							cc_reminders_send_reminder($user_id, $recording_id);
							$html .= '<br>reminder on its way to user '.$user_id.' for recording '.$recording_id;
							$count_reminders ++;
							break;
						}
					}
				}
			}
		}
	}
	$html .= '<br>'.$count_reminders.' reminders sent';
	$html .= '<br>done';
	return $html;
}

// send the reminder email
function cc_reminders_send_reminder($user_id, $recording_id){
	$user_info = get_userdata($user_id);
	$mailster_tags = array(
		'firstname' => $user_info->first_name,
		'recording' => get_the_title($recording_id),
		'recording_access_expiry' => rpm_cc_recordings_expiry_pretty_expiry_datetime($user_id, $recording_id),
	);
	$mailster_hook = 'send_recording_expiry_reminder';
	$subscriber = mailster('subscribers')->get_by_mail($user_info->user_email);
	if($subscriber){
		sysctrl_mailster_ar_hook($mailster_hook, $subscriber->ID, $mailster_tags);
	}
}