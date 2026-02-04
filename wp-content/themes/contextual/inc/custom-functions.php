<?php
/**
 * Custom functions
 * - ie one-off bits n bobs
 */

// build user address meta data
// add a two minutes cron schedule ... also used for the Xero interface
add_filter( 'cron_schedules', 'cc_custom_cron_schedules' );
function cc_custom_cron_schedules( $schedules ) { 
    $schedules['two_mins'] = array(
        'interval' => 120, // seconds
        'display'  => esc_html__( 'Every Two Minutes' ), );
    $schedules['two_hours'] = array(
        'interval' => 7200, // seconds
        'display'  => esc_html__( 'Every Two Hours' ), );
    $schedules['five_mins'] = array(
        'interval' => 300, // seconds
        'display'  => esc_html__( 'Every Five Minutes' ), );
    return $schedules;
}
// hook the function
add_action( 'cc_custom_two_min_hook', 'cc_custom_build_user_address' );
if ( ! wp_next_scheduled( 'cc_custom_two_min_hook' ) ) {
    wp_schedule_event( time(), 'two_mins', 'cc_custom_two_min_hook' );
}

function cc_custom_build_user_address(){
	global $wpdb;
	$last_pmt_id = get_option('cc_build_user_address_last_pmt_id', 0);
	$countries = ccpa_countries_all();
	$sql = "SELECT * FROM {$wpdb->prefix}ccpa_payments WHERE id > $last_pmt_id ORDER BY id ASC LIMIT 500";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	foreach ($pmts as $pmt) {
		if($pmt['address'] <> ''){
			// only interested in registered people, not attendees
			$user_id = cc_myacct_get_user($pmt); // will create new user if needed
			$addr_bits = explode(',', $pmt['address']);
			// fields: a1* a2 town* county postcode* country*
			switch (count($addr_bits)) {
				case '4':
					$country_code = array_search(trim($addr_bits[3]), $countries);
					if($country_code){
						$addr1 = trim($addr_bits[0]);
						$addr2 = '';
						$town = trim($addr_bits[1]);
						$county = '';
						$pcode = trim($addr_bits[2]);
						$country = $country_code;
					}else{
						$addr1 = trim($addr_bits[0]);
						$addr2 = '';
						$town = trim($addr_bits[1]);
						$county = trim($addr_bits[2]);
						$pcode = trim($addr_bits[3]);
						$country = '';
					}
					break;
				case '5':
					$country_code = array_search(trim($addr_bits[4]), $countries);
					if($country_code){
						$addr1 = trim($addr_bits[0]);
						$addr2 = '';
						$town = trim($addr_bits[1]);
						$county = trim($addr_bits[2]);
						$pcode = trim($addr_bits[3]);
						$country = $country_code;
					}else{
						$addr1 = trim($addr_bits[0]);
						$addr2 = trim($addr_bits[1]);
						$town = trim($addr_bits[2]);
						$county = trim($addr_bits[3]);
						$pcode = trim($addr_bits[4]);
						$country = '';
					}
					break;
				case '6':
					$country_code = array_search(trim($addr_bits[5]), $countries);
					if($country_code){
						$addr1 = trim($addr_bits[0]);
						$addr2 = trim($addr_bits[1]);
						$town = trim($addr_bits[2]);
						$county = trim($addr_bits[3]);
						$pcode = trim($addr_bits[4]);
						$country = $country_code;
					}else{
						$addr1 = $pmt['address'];
						$addr2 = '';
						$town = '';
						$county = '';
						$pcode = '';
						$country = '';
					}
					break;
				default:
					$addr1 = $pmt['address'];
					$addr2 = '';
					$town = '';
					$county = '';
					$pcode = '';
					$country = '';
					break;
			}
			update_user_meta($user_id, 'address_line_1', $addr1);
			update_user_meta($user_id, 'address_line_2', $addr2);
			update_user_meta($user_id, 'address_town', $town);
			update_user_meta($user_id, 'address_county', $county);
			update_user_meta($user_id, 'address_postcode', $pcode);
			update_user_meta($user_id, 'address_country', $country);
		}
		update_option('cc_build_user_address_last_pmt_id', $pmt['id']);
	}
}

// builds the user phone number
add_action( 'cc_custom_two_min_hook', 'cc_custom_build_user_phone' );
function cc_custom_build_user_phone(){
	global $wpdb;
	$last_pmt_id = get_option('cc_build_user_phone_last_pmt_id', 0);
	$sql = "SELECT * FROM {$wpdb->prefix}ccpa_payments WHERE id > $last_pmt_id AND phone <> '' ORDER BY id ASC LIMIT 1";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	foreach ($pmts as $pmt) {
		$user_id = cc_myacct_get_user($pmt);
		update_user_meta($user_id, 'user_phone', $pmt['phone']);
		update_option('cc_build_user_phone_last_pmt_id', $pmt['id']);
	}
}

// remove functionality of old shortcodes
// [fa ...]
add_shortcode('fa', 'cc_remove_fa_shortcode');
function cc_remove_fa_shortcode(){
	return '';
}
// [accordions ...] and [accordion ...]
add_shortcode('accordions', 'cc_remove_accordion_shortcodes');
add_shortcode('accordion', 'cc_remove_accordion_shortcodes');
function cc_remove_accordion_shortcodes($atts, $content){
	return do_shortcode($content);
}

// sets up the mailster_list id for workshops and recordings (inc workshop events)
// modified Feb 2023 to fix the recording lists
add_shortcode('attach_mailster_list_ids', 'cc_custom_attach_mailster_list_ids');
function cc_custom_attach_mailster_list_ids(){
	$html = 'cc_custom_attach_mailster_list_ids';
	$lists = mailster( 'lists' )->get();
	// $html .= print_r($lists[0], true);
	$args = array(
		// 'post_type' => array('workshop', 'recording'),
		'post_type' => array('recording'),
		'numberposts' => -1,
	);
	$posts = get_posts( $args );
	$count_posts = $count_nolist = $count_upds = 0;
	$event_posts = $event_nolist = $event_upds = 0;
	foreach ($posts as $post) {
		$count_posts ++;
		$sanitised_post_title = sanitize_title($post->post_title);
		if($post->post_type == 'workshop' && workshop_is_multi_event($post->ID)){
			for($i = 1; $i<16; $i++){
				$event_name = get_post_meta( $post->ID, 'event_'.$i.'_name', true );
				if($event_name <> ''){
					$event_posts ++;
					$mailster_event_list = get_post_meta($post->ID, 'mailster_event_list_'.$i, true);
					if($mailster_event_list == ''){
						$event_nolist ++;
						$sanitized_list_name = sanitize_title($sanitised_post_title.': '.$event_name);
						$mailster_event_list = 0;
						foreach ($lists as $list) {
					    	if($list->slug == $sanitized_list_name){
					    		$mailster_event_list = $list->ID;
					    		break;
							}
				    	}
				    	if($mailster_event_list > 0){
				    		// update_post_meta($post->ID, 'mailster_event_list_'.$i, $mailster_event_list);
				    		$event_upds ++;
				    	}
					}
				}
			}
		}
		$mailster_list = get_post_meta($post->ID, 'mailster_list', true);
    	$html .= '<br>Recording: '.$post->ID.': <em>'.$post->post_title.'</em> <strong>List:</strong> ';
	    $correct_list_slug = sanitize_title( 'Recording: '.$post->post_title );
		if($mailster_list == ''){
			$html .= '<span style="color:#ff0000;">not found</span>';
			$count_nolist ++;
			$mailster_list = 0;
			$list_name = '';
			foreach ($lists as $list) {
		    	if($list->slug == $correct_list_slug){
		    		$mailster_list = $list->ID;
		    		$list_name = $list->name;
		    		break;
				}
	    	}
	    	if($mailster_list > 0){
	    		$html .= ' <span style="color:#99ff00;">being set to:</span> '.$mailster_list.' <span style="color:#0000ff;">'.$list_name.'</span>';
	    		update_post_meta($post->ID, 'mailster_list', $mailster_list);
	    		$count_upds ++;
	    	}
	    }else{
	    	$html .= $mailster_list.': <em>';
			foreach ($lists as $list) {
		    	if($list->ID == $mailster_list){
		    		$html .= ' <span style="color:#00ff00;">'.$list->name.'</span>';
		    		break;
				}
	    	}
	    	$html .= '</em>';
	    	$correct_listid = 0;
	    	$correct_list_name = '';
			foreach ($lists as $list) {
		    	if($list->slug == $correct_list_slug){
		    		$html .= ' should be: '.$list->ID.' <span style="color:#0000ff;">'.$list->name.'</span>';
		    		$correct_listid = $list->ID;
		    		$correct_list_name = $list->name;
		    		break;
				}
	    	}
	    	if($correct_listid <> $mailster_list){
	    		if($correct_listid == 0){
	    			$html .= ' <span style="color:#ffff00;">creating list</span>';
	    			$correct_listid = mailster('lists')->add('Recording: '.$post->post_title);
	    		}
	    		$html .= ' <span style="color:#00ffff;">updating recording</span>';
	    		update_post_meta($post->ID, 'mailster_list', $correct_listid);
	    		$count_upds ++;
	    	}else{
	    		$html .= ' <span style="color:#ff00ff;">which is not being updated</span>';
	    	}
	    }
	}
	$html .= '<br><br>Posts: '.$count_posts;
	$html .= ' No list: '.$count_nolist;
	$html .= ' Updates: '.$count_upds;
	$html .= '<br>Events: '.$event_posts;
	$html .= ' No list: '.$event_nolist;
	$html .= ' Updates: '.$event_upds;
	return $html;
}

// sets up pages to have the title on top of the hero
add_shortcode('move_page_titles', 'cc_custom_move_page_titles');
function cc_custom_move_page_titles(){
	$html = 'Moving post titles ... ';
	$args = array(
		'post_type' => array('page'),
		'numberposts' => -1,
		'fields' => 'ids',
	);
	$page_ids = get_posts( $args );
	$count_pages = 0;
	foreach ($page_ids as $page_id) {
		update_post_meta( $page_id, '_page_title_locn', 'hero' );
		$count_pages ++;
	}
	$html .= $count_pages.' titles moved';
	return $html;
}

// check out the recordings_users tabel for completeness
add_shortcode('recordings_users_tidyup', 'recordings_users_tidyup');
function recordings_users_tidyup(){
	global $wpdb;
	$payments_table = $wpdb->prefix.'ccpa_payments';
	// we'll check all payments since 1/1/2021
	$sql = "SELECT * FROM $payments_table WHERE last_update > '2021-01-01 00:00:00' AND type = 'recording'";
	$payments = $wpdb->get_results($sql, ARRAY_A);
	$html = '<br>recordings_users_tidyup';
	foreach ($payments as $payment) {
		$html .= '<br>'.$payment['id'];
		$rec_user = cc_myacct_get_recordings_users_by_user_recording($payment['reg_userid'], $payment['workshop_id']);
		if($rec_user === NULL){
			$html .= ' ERROR registrant not found';
		}else{
			if($payment['id'] <> $rec_user['payment_id']){
				$html .= ' ERROR payment ID is: '.$rec_user['payment_id'];
			}
		}
	}
	return $html;
}

// look for duplicate workshops
add_shortcode('look_for_duplicates', 'look_for_duplicates');
function look_for_duplicates(){
	$html = '<br>look_for_duplicates';
	global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $attendees_table = $wpdb->prefix.'cc_attendees';
    $sql = "SELECT * FROM $wkshop_users_table WHERE workshop_id <> 0 ORDER BY user_id, workshop_id";
    $workshops = $wpdb->get_results($sql, ARRAY_A);
    $html .= '<br>'.count($workshops).' workshops found';
    $last_user_id = $last_workshop_id = $last_payment_id = 0;
    $last_reg_attend = '';
    $empty_event_ids = array('', '0', '1', '1,');
    
    foreach ($workshops as $workshop) {
    	if($workshop['user_id'] == $last_user_id && $workshop['workshop_id'] == $last_workshop_id){
    		$html .= '<br>user: '.$workshop['user_id'].' workshop: '.$workshop['workshop_id'];

    		// $last_attendees = cc_attendees_for_payment($last_payment_id);
    		$sql = "SELECT * FROM $attendees_table WHERE payment_id = $last_payment_id ORDER BY registrant DESC user_id ASC";
    		$last_attendees = $wpdb->get_results($sql, ARRAY_A);

    		// $attendees = cc_attendees_for_payment($workshop['payment_id']);
    		$sql = "SELECT * FROM $attendees_table WHERE payment_id = ".$workshop['payment_id']." ORDER BY registrant DESC user_id ASC";
    		$attendees = $wpdb->get_results($sql, ARRAY_A);

    		if($attendees == $last_attendees){
    			$html .= ' DUPLICATE';
    		}else{
    			$html .= ' not duplicate';
    		}

    		if($last_payment_id == $workshop['payment_id']){
    			$html .= ' SAME PAYMENT: '.$last_payment_id;
    		}
    		$last_payment = cc_paymentdb_get_payment($last_payment_id);
    		$this_payment = cc_paymentdb_get_payment($workshop['payment_id']);
    		if( in_array($last_payment['event_ids'], $empty_event_ids) && in_array($this_payment['event_ids'], $empty_event_ids) ){
    			$html .= ' single event';
    		}else{
    			$html .= ' '.$last_payment_id.' events: '.$last_payment['event_ids'].' <> '.$workshop['payment_id'].' events: '.$this_payment['event_ids'];
    		}
    	}
    	$last_user_id = $workshop['user_id'];
    	$last_workshop_id = $workshop['workshop_id'];
    	$last_payment_id = $workshop['payment_id'];
    	$last_reg_attend = $workshop['reg_attend'];
    }
    $html .= '<br>done';
	return $html;
}

// tidy up payment records
// remove attendees who shouldn't be there
add_shortcode('tidyup_payment_records', 'tidyup_payment_records');
function tidyup_payment_records(){
	global $wpdb;
	$html = 'tidyup_payment_records';
	$payments_table = $wpdb->prefix.'ccpa_payments';
	$wkshop_users_table = $wpdb->prefix.'wshops_users';
	$sql = "SELECT * FROM $payments_table WHERE last_update > '2023-01-01 00:00:00' AND last_update < '2023-08-01 00:00:00'";
	$payments = $wpdb->get_results($sql, ARRAY_A);
	$fixed = 0;
	foreach ($payments as $payment) {
		$report_it = false;
		if($payment['att_userid'] > 0 && $payment['att_userid'] == $payment['reg_userid']){
			$report_it = true;
		}
		if($payment['attendee_email'] <> '' && strtolower($payment['email']) == strtolower($payment['attendee_email'])){
			$report_it = true;
		}
		/* too hard to fix! :-(
		if($payment['reg_userid'] == 0){
			// $report_it = true;
		}
		*/
		if($report_it){
			$html .= '<br>'.$payment['id'].' reg_userid: '.$payment['reg_userid'].' att_userid: '.$payment['att_userid'].' email: '.$payment['email'].' attendee_email: '.$payment['attendee_email'];
			/*
			if($payment['reg_userid'] == 0){
				if($payment['email'] == ''){
					$poss_user_ids = cc_users_get_by_name($payment['firstname'], $payment['lastname']);
					$html .= ' poss_user_ids: '.implode(', ', $poss_user_ids);
				}else{
					$poss_user = get_user_by('email', $payment['email']);
					if($poss_user){
						$html .= ' poss_user_id: '.$poss_user->ID;
					}else{
						$html .= ' email not found in users';
					}
				}
			}
			*/

			$attendees = cc_attendees_for_payment($payment['id']);
			$html .= ' '.count($attendees).' attendees';

			$sql = "SELECT * FROM $wkshop_users_table WHERE payment_id = ".$payment['id'];
			$wshop_users = $wpdb->get_results($sql, ARRAY_A);
			$html .= ' workshop_users: ';
			$found = array();
			$error = false;
			foreach ($wshop_users as $wshop_user) {
				$html .= $wshop_user['user_id'].', ';
				if(in_array($wshop_user['user_id'], $found)){
					$error = true;
				}
				$found[] = $wshop_user['user_id'];
			}
			if($error){
				$html .= ' Duplicated';
				if(count($wshop_users) == 2){
					$html .= ' fixing it';
					$where = array(
						'user_id' => $found[0],
						'workshop_id' => $payment['workshop_id'],
						'payment_id' => $payment['id'],
						'reg_attend' => 'a',
					);
					// $result = 1;
					// $html .= ' FAKE ';
					$result = $wpdb->delete( $wkshop_users_table, $where );
					if($result){
						$html .= ' '.$result.' rows deleted';
						if($result <> 1){
							break;
						}
					}else{
						$html .= ' result: false';
						break;
					}
					$fixed ++;
					if($fixed > 1000){
						break;
					}
				}
			}

			/* why bother ????
			// clear out the attendee data
			$payment['att_userid'] = 0;
			$payment['attendee_email'] = $payment['attendee_firstname'] = $payment['attendee_lastname'] = '';
			$where = array(
				'id' => $payment['id'],
			);
			// $result = $wpdb->update($payments_table, $payment, $where);
			*/

		}
	}
	$html .= '<br>'.$fixed.' done';
	return $html;
}

// The training buttons
add_shortcode( 'training_buttons', 'cc_custom_training_buttons');
function cc_custom_training_buttons( $atts=array() ){
    $atts = shortcode_atts( array(
            "xclass" => '',
            "filter" => 'no',
    ), $atts );
	return cc_custom_training_buttons_core( $atts['xclass'], 'center', $atts['filter']);
}
function cc_custom_training_buttons_core( $xclass='', $align='center', $filter='no', $myacct='no' ){
    global $rpm_theme_options;
	$html = '<div class="training-buttons-wrap text-'.$align.' '.$xclass.'"><a class="btn btn-training m-3 mt-md-5" href="/live-training">All live training</a><a class="btn btn-training m-3 mt-md-5" href="/online-training">On-demand training</a>';
	if( isset( $rpm_theme_options['free_training_btn_text'] ) && $rpm_theme_options['free_training_btn_text'] <> '' && isset( $rpm_theme_options['free_training_btn_link'] ) && $rpm_theme_options['free_training_btn_link'] <> '' ){
		$html .= '<a class="btn btn-training m-3 mt-md-5" href="'.$rpm_theme_options['free_training_btn_link'].'">'.$rpm_theme_options['free_training_btn_text'].'</a>';
	}
	if( $filter == 'yes' ){
		$html .= '<a href="javascript:void(0);" class="cc-training-search-btn btn btn-training btn-lg m-3 mt-md-5">Filter &amp; search training</a>';
	}
	if( $myacct == 'yes' && is_user_logged_in() ){
		$html .= '<a href="/my-account" class="cc-training-search-btn btn btn-training btn-lg m-3 mt-md-5">My account</a>';
	}
	$html .= '</div>';
	return $html;

}