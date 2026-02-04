<?php
/**
 * System control functions
 */

// add a fifteen minutes cron schedule
add_filter( 'cron_schedules', 'sysctrl_add_cron_interval' );
function sysctrl_add_cron_interval( $schedules ) { 
    $schedules['fifteen_mins'] = array(
        'interval' => 900, // seconds
        'display'  => esc_html__( 'Every Fifteen Minutes' ), );
    return $schedules;
}

// hook the function
add_action( 'sysctrl_cron_hook', 'sysctrl_mailster_cron_check' );
if ( ! wp_next_scheduled( 'sysctrl_cron_hook' ) ) {
    wp_schedule_event( time(), 'fifteen_mins', 'sysctrl_cron_hook' );
}

// send warning if Mailstor cron freezes or welcome autoresponder deactivates
function sysctrl_mailster_cron_check(){
	if(!function_exists('mailster')) return;
	global $wpdb;
	$now = time();
	// mailster_option( 'interval' ) = number of minutes between send - irrelevant in our case as a real cron is used (run every minute)
	$interval = mailster_option( 'interval' ) * 60;
	$last_hit = get_option( 'mailster_cron_lasthit' );
	if($last_hit){
		// $last_hit is an array containing:
		// - ip => ip
		// - time => how long the last run took
		// - timemax => how long the longest run has ever taken
		// - mail => average send time per email this run
		// - timestamp => start time of last run
		// - oldtimestamp => start time of previous run
		// - user => $_SERVER['HTTP_USER_AGENT']
		// get real delay...
		$real_delay = max( $interval, $last_hit['timestamp'] - $last_hit['oldtimestamp'] );
		$current_delay = $now - $last_hit['timestamp'];
		// ..and compare it with the interval (3 times) - also something in the queue
		if ( ( $current_delay > $real_delay * 3 || ! $real_delay && ! $current_delay ) ) {
			$message = "Mailster cron on ".site_url()." maybe frozen.\n";
			$message .= "Interval: ".$interval." secs (how long the gap should be between runs).\n";
			$message .= "Server time now: ".$now." = ".date('d/m/Y H:i:s', $now).".\n";
			$message .= "Start time of last run: ".$last_hit['timestamp']." = ".date('d/m/Y H:i:s', $last_hit['timestamp']).".\n";
			$message .= "Start time of previous run: ".$last_hit['oldtimestamp']." = ".date('d/m/Y H:i:s', $last_hit['oldtimestamp']).".\n";
			$message .= "Current delay: ".$current_delay." secs (now less last run start time).\n";
			$message .= "Real delay: ".$real_delay." secs (diff between last and previous timestamps).\n";
			wp_mail( get_bloginfo('admin_email'), 'Mailster Cron Frozen', $message );
		}
	}

	if( LIVE_SITE ){
		// ===== NEW AUTORESPONDER CHECK =====
		// Check if welcome autoresponder (post ID 11931) is active
		$welcome_id = 11931;
		$is_active = get_post_meta($welcome_id, '_mailster_active', true);
		
		$alert_sent = get_transient('mailster_welcome_inactive_alert');
		
		// If _mailster_active doesn't exist or is not 1, it's inactive
		if($is_active != 1 && !$alert_sent){
			$message = "Mailster welcome autoresponder on ".site_url()." has become INACTIVE.\n";
			$message .= "Campaign ID: ".$welcome_id."\n";
			$message .= "Campaign Name: welcome\n";
			$message .= "_mailster_active value: ".($is_active ? $is_active : 'NOT SET')."\n";
			$message .= "Time detected: ".date('d/m/Y H:i:s', $now)."\n";
			wp_mail( get_bloginfo('admin_email'), 'Mailster Welcome Autoresponder INACTIVE', $message );
			
			// Set a transient to avoid repeated alerts - expires after 24 hours
			set_transient('mailster_welcome_inactive_alert', true, DAY_IN_SECONDS);
		}
		
		// If it's active again, clear the alert flag
		if($is_active == 1){
			delete_transient('mailster_welcome_inactive_alert');
		}
	}
}

// automatically unlock the Mailster cron if it is frozen
add_action('mailster_unlock_cron', 'sysctrl_mailster_unlock_cron');
function sysctrl_mailster_unlock_cron(){
	// returns the minimum wait time to unfreeze the cron: 600 secs = 10 mins (the minimum that Mailster allows)
	return 600;
}

// belt and braces around mailster autoresponder hooks
// triggers the hook and then checks that it has been triggered ... let's me know if not
function sysctrl_mailster_ar_hook($hook, $subscriber_id, $tags=array()){
	global $wpdb;
	$status = 'ok';
	$my_message = date('d/m/Y H:i:s').': hook: '.$hook.' Subscriber: '.$subscriber_id.' ';
	$my_message .= 'TAGS: ';
	$my_message .= print_r($tags, true);
	$cleaned_tags = array();
	foreach ($tags as $key => $value) {
		$my_message .= $key.': '.$value.' ';
		if(strpos($value, "'")){
			$value = str_replace("'", "&apos;", stripslashes($value));
			$my_message .= '##CLEANED## ';
		}
		$cleaned_tags[$key] = $value;
	}
	$my_message .= 'CLEANED TAGS: ';
	$my_message .= print_r($cleaned_tags, true);
	if($subscriber_id < 1){
		$my_message .= 'Invalid subscriber id of '.$subscriber_id;
		$status = 'error';
	}else{
		// we need to know which campaign this hook is linked to
		// note, the option mailster_hooks can contain duplicates! Use the later entry ... not always true!
		// we need to use the entry for the post type newsletter, not revision, so let's find the later one of those!
		$hooks = get_option('mailster_hooks', array());
		$our_campaign_id = 0;
		foreach ( (array) $hooks as $campaign_id => $hook_name ){
			if($hook_name == $hook){
				if(get_post_type($campaign_id) == 'newsletter'){
					$our_campaign_id = $campaign_id;
				}
			}
		}
		if($our_campaign_id == 0){
			$my_message .= 'Hook '.$hook.' not found in mailster hooks';
			$status = 'error';
		}else{
			$my_message .= 'Campaign ID is '.$our_campaign_id.'. ';
			// trigger the autoresponder ...
			do_action($hook, $subscriber_id, $cleaned_tags);
			// is the request now in the queue?
			$sql = "SELECT * FROM {$wpdb->prefix}mailster_queue WHERE subscriber_id = $subscriber_id AND campaign_id = $our_campaign_id";
			$queued = false;
			$queue = $wpdb->get_results($sql, ARRAY_A);
			$my_message .= 'Queue: ';
			foreach ($queue as $queue_item) {
				$queued = true;
				$my_message .= 'Queue added: '.date('d/m/Y H:i:s', $queue_item['added']).' ';
				$my_message .= 'Queue timestamp: '.date('d/m/Y H:i:s', $queue_item['timestamp']).' ';
				$my_message .= 'Queue sent: ';
				if($queue_item['sent'] == 0){
					$my_message .= 'no ';
				}else{
					$my_message .= date('d/m/Y H:i:s', $queue_item['sent']).' ';
				}
			}
			if(!$queued){
				$my_message .= 'Not queued ';
				// maybe it's been sent already?
				$sql = "SELECT * FROM {$wpdb->prefix}mailster_actions WHERE subscriber_id = $subscriber_id AND campaign_id = $our_campaign_id";
				$actioned = false;
				$actions = $wpdb->get_results($sql, ARRAY_A);
				$my_message .= 'Actions: ';
				foreach ($actions as $action) {
					$actioned = true;
					$my_message .= 'timestamp: '.date('d/m/Y H:i:s', $action['timestamp']).' ';
					$my_message .= 'count: '.$action['count'].' ';
					$my_message .= 'type: '.$action['type'].' ';
					$my_message .= 'link_id: '.$action['link_id'].' ';
				}
				if(!$actioned){
					$my_message .= 'not actioned ';
					$status = 'error';
				}
			}
		}
	}
	if($status == 'error'){
		wp_mail( get_bloginfo('admin_email'), 'Mailster autoresponder: '.$status, $my_message );
	}
}

// extend password rest link duration to 7 days
add_filter( 'password_reset_expiration', function( $expiration ) {
	return 7 * DAY_IN_SECONDS;
});

// make sure that only admins and editors can get to the backend
add_action( 'init', 'rpm_blockusers_init' );
function rpm_blockusers_init() {
	if ( is_admin() && ! current_user_can( 'edit_pages' ) && !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
	wp_redirect( home_url() );
	exit;
	}
}

/*
 * Disable User Notification of Password/email Change
 */
add_filter( 'send_email_change_email', '__return_false' );
add_filter( 'send_password_change_email', '__return_false' );

/**
 * Disable Admin Notification of User Password Change
 *
 * @see pluggable.php
 */
if ( ! function_exists( 'wp_password_change_notification' ) ) {
    function wp_password_change_notification( $user ) {
        return;
    }
}