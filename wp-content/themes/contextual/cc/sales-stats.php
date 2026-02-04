<?php
/**
 * Training sales stats
 */

/**
 * Capture ...
 * We need a table to capture the stats in and a cron job to populate it
 */

// create the stats table
// v2 change promoted to the id of the newsletter
// v3 added category
// v4 added upsells and attendees
add_action('init', 'cc_stats_update_table_def');
function cc_stats_update_table_def(){
	global $wpdb;
	$cc_stats_table_ver = 4;
	$installed_table_ver = get_option('cc_stats_table_ver');
	if($installed_table_ver <> $cc_stats_table_ver){
		$table_name = $wpdb->prefix.'sales_stats';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			week_start date NOT NULL,
			training_id mediumint(9) NOT NULL,
			promoted mediumint(9) NOT NULL,
			registrations mediumint(9) NOT NULL,
			amount decimal(9,2) NOT NULL,
			notes text NOT NULL,
			category varchar(4) NOT NULL,
			upsells mediumint(9) NOT NULL,
			attendees mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		if( !function_exists( 'dbDelta' ) ){
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}
		$response = dbDelta($sql);
		update_option('cc_stats_table_ver', $cc_stats_table_ver);
	}
}

// returns an empty stats array
function cc_stats_empty(){
	return array(
		'id' => 0,
		'week_start' => '0000-00-00',
		'training_id' => 0,
		'promoted' => 0,
		'registrations' => 0,
		'amount' => 0,
		'notes' => '',
		'category' => '',
		'upsells' => 0,
		'attendees' => 0,
	);
}

// shortcode to start a full recaclc of the sales stats
add_shortcode( 'restart_sales_stats_refresh', function(){
	if ( wp_next_scheduled( 'cc_stats_populate_hook' ) ){

		// so that, when it restarts, it goes back to the beginning ...
		delete_option( 'cc_last_stats_populated' );

		// kill the current cycle so that a new cycle starts ...
		$timestamp = wp_next_scheduled( 'cc_stats_populate_hook' );
		wp_unschedule_event( $timestamp, 'cc_stats_populate_hook' );

		// Set frequency to two_mins to start catch-up process
		update_option('cc_stats_populate_hook_frequency', 'two_mins');

		return 'Sales stats rebuild started';
	}
	return 'nothing to restart';
});

// cron job to populate the stats table
if ( wp_next_scheduled( 'cc_stats_populate_hook' ) ) {
	// ccpa_write_log('checking sales stats population state');
	$frequency = get_option('cc_stats_populate_hook_frequency', '');
	// ccpa_write_log('frequency: '.$frequency);
	if( $frequency == 'two_hours' ){
		// all good, caught up and on the regular cycle, do nothing
	}elseif( $frequency == 'two_mins' ){
		// catching up
		// have we caught up yet?
		$last_stats_populated = get_option('cc_last_stats_populated', '2020-12-28');
		if( ! $last_stats_populated || $last_stats_populated == '' ){
			$last_stats_populated = '2020-12-28';
		}
		// ccpa_write_log('last populated: '.$last_stats_populated);
		if(date('D') == 'Mon'){
			$last_monday = date('Y-m-d');
		}else{
			$last_monday = date('Y-m-d', strtotime('last Monday'));
		}
		// ccpa_write_log('last monday: '.$last_monday);
		// add a week to the last populated date
		$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $last_stats_populated.' 00:00:00', new DateTimeZone('UTC'));
		$datetime->modify('+7 days');
		$week_todo = $datetime->format('Y-m-d');
		// ccpa_write_log('week to do: '.$week_todo);
		if( $week_todo < $last_monday ){
			// no, not caught up yet, keep going every 2 mins
		}else{
			// yes, caught up
			// unschedule so that we can change the frequency
			// ccpa_write_log('unscheduling ...');
			$timestamp = wp_next_scheduled( 'cc_stats_populate_hook' );
			wp_unschedule_event( $timestamp, 'cc_stats_populate_hook' );
		}
	}else{
		// shouldn't be here
		// unschedule to restart things
		$timestamp = wp_next_scheduled( 'cc_stats_populate_hook' );
		wp_unschedule_event( $timestamp, 'cc_stats_populate_hook' );
	}
}else{
	// not scheduled, are we catching up or caught up?
	// ccpa_write_log('scheduling sales stats population timing');	
	$last_stats_populated = get_option('cc_last_stats_populated', '2020-12-28');
	if( ! $last_stats_populated || $last_stats_populated == '' ){
		$last_stats_populated = '2020-12-28';
	}
	// ccpa_write_log('last populated: '.$last_stats_populated);
	if(date('D') == 'Mon'){
		$last_monday = date('Y-m-d');
	}else{
		$last_monday = date('Y-m-d', strtotime('last Monday'));
	}
	// ccpa_write_log('last monday: '.$last_monday);
	// add a week to the last populated date
	$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $last_stats_populated.' 00:00:00', new DateTimeZone('UTC'));
	$datetime->modify('+7 days');
	$week_todo = $datetime->format('Y-m-d');
	// ccpa_write_log('week to do: '.$week_todo);
	if( $week_todo < $last_monday ){
		// catching up
		// ccpa_write_log('catching up ...');
	    wp_schedule_event( time(), 'two_mins', 'cc_stats_populate_hook' );
	    update_option('cc_stats_populate_hook_frequency', 'two_mins');
	}else{
		// caught up
		// ccpa_write_log('caught up ...');
	    wp_schedule_event( time(), 'two_hours', 'cc_stats_populate_hook' );
	    update_option('cc_stats_populate_hook_frequency', 'two_hours');
	}
}
add_action('cc_stats_populate_hook', 'cc_stats_maybe_populate');

// check to see if we want to populate the stats now
function cc_stats_maybe_populate(){
	// ccpa_write_log('function cc_stats_maybe_populate');
	$last_stats_populated = get_option('cc_last_stats_populated', '2020-12-28');
	// ccpa_write_log('last populated: '.$last_stats_populated);
	if( ! $last_stats_populated || $last_stats_populated == '' ){
		$last_stats_populated = '2020-12-28';
	}
	// if today = Monday, last Monday = a week ago
	// but if today is Monday, we want to use today as "last monday"
	if(date('D') == 'Mon'){
		$last_monday = date('Y-m-d');
	}else{
		$last_monday = date('Y-m-d', strtotime('last Monday'));
	}
	// ccpa_write_log('last monday: '.$last_monday);
	if($last_stats_populated < $last_monday){
		// add a week to when we last did it
		$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $last_stats_populated.' 00:00:00', new DateTimeZone('UTC'));
		$datetime->modify('+7 days');
		$week_todo = $datetime->format('Y-m-d');
		// ccpa_write_log('week to do: '.$week_todo);
		// we do not want to do this week yet
		if($week_todo < $last_monday){
			cc_stats_populate($week_todo);
			update_option('cc_last_stats_populated', $week_todo);
		}
	}
}

// for testing
// ######### OUT OF DATE ... DOESN'T CONSIDER COURSES OR SERIES ###########
add_shortcode('cc_stats_populate', 'test_cc_stats_populate');
function test_cc_stats_populate(){
	global $wpdb;
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$htm = 'test_cc_stats_populate';
	$html .= '<br>Last Monday: '.date('Y-m-d H:i:s', strtotime('last Monday'));
	$html .= '<br>Last Tuesday: '.date('Y-m-d H:i:s', strtotime('last Tuesday'));
	$html .= '<br>Last Wednesday: '.date('Y-m-d H:i:s', strtotime('last Wednesday'));
	$html .= '<br>Last Thursday: '.date('Y-m-d H:i:s', strtotime('last Thursday'));
	$html .= '<br>Last Friday: '.date('Y-m-d H:i:s', strtotime('last Friday'));
	$html .= '<br>Last Saturday: '.date('Y-m-d H:i:s', strtotime('last Saturday'));
	$html .= '<br>Last Sunday: '.date('Y-m-d H:i:s', strtotime('last Sunday'));
	// cc_stats_populate('2021-01-11');
	$test = get_post_meta(12345, 'cumulative_registrations', true);
	$html .= '<br>meta field is';
	if($test === false){
		$html .= ' false';
	}
	if($test == 0){
		$html .= ' zero';
	}
	if($test == ''){
		$html .= ' empty';
	}
	$html .= ' and typecast as an int: '.(int)$test;
	$html .= '<br>recalculating cumulative stats';
	// get all trainings
	$args = array(
		'numberposts' => -1,
		'post_type' => array('workshop', 'recording'),
		'fields' => 'ids',
	);
	$trainings = get_posts($args);
	foreach ($trainings as $training_id) {
		$html .= '<br>'.$training_id;
		$cum_regs = (int) get_post_meta($training_id, 'cumulative_registrations', true);
		$cum_amount = round( (float) get_post_meta($training_id, 'cumulative_reg_amount', true), 2 );
		$html .= ' '.$cum_regs.' &pound;'.$cum_amount;
		$sql = "SELECT * FROM $sales_stats_table WHERE training_id = $training_id";
		$stats = $wpdb->get_results($sql, ARRAY_A);
		$count_regs = $sum_regs = 0;
		foreach ($stats as $stat) {
			$count_regs += $stat['registrations'];
			$sum_regs += round( $stat['amount'], 2 );
		}
		$html .= ' '.$count_regs.' &pound;'.$sum_regs;
		if($cum_regs <> $count_regs){
			$html .= ' COUNT MISMATCH!';
		}
		if($cum_amount <> round( $sum_regs, 2 ) ){
			$html .= ' SUM MISMATCH!';
		}
	}
	return $html;
}

// populate the stats for a particular week
function cc_stats_populate($week_start){
	ccpa_write_log('cc_stats_populate for '.$week_start);
	global $wpdb;
	$sales_stats_table = $wpdb->prefix.'sales_stats';

	// find the relevant payment records
	$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $week_start.' 00:00:00', new DateTimeZone('UTC'));
	$start_timestamp = $datetime->getTimestamp();
	$datetime->modify('+7 days');
	$week_end = $datetime->format('Y-m-d H:i:s');
	$end_timestamp = $datetime->getTimestamp();
	$payments = cc_paymentdb_get_date_range($week_start, $week_end);
	ccpa_write_log(count($payments).' payment records found');

	// collate the stats
	$stats = array();
	foreach ($payments as $payment) {
		$training_id = $payment['workshop_id']; // just simpler to write
		$upsell_id = $payment['upsell_workshop_id'];
		$num_attendees = count( cc_attendees_for_payment( $payment['id'] ) );
		if( $upsell_id > 0 ){
			if( isset( $stats[$training_id] ) ){
				$stats[$training_id]['registrations'] ++;
				$stats[$training_id]['attendees'] = $stats[$training_id]['attendees'] + $num_attendees;
				$stats[$training_id]['amount'] = $stats[$training_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] );
			}else{
				$stats[$training_id]['registrations'] = 1;
				$stats[$training_id]['upsells'] = 0;
				$stats[$training_id]['attendees'] = $num_attendees;
				$stats[$training_id]['amount'] = cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] );
			}
			if( isset( $stats[$upsell_id] ) ){
				$stats[$upsell_id]['upsells'] ++;
				$stats[$upsell_id]['attendees'] = $stats[$upsell_id]['attendees'] + $num_attendees;
				$stats[$upsell_id]['amount'] = $stats[$upsell_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] );
			}else{
				$stats[$upsell_id]['registrations'] = 0;
				$stats[$upsell_id]['upsells'] = 1;
				$stats[$upsell_id]['attendees'] = $num_attendees;
				$stats[$upsell_id]['amount'] = cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] );
			}
		}else{
			if( isset( $stats[$training_id] ) ){
				$stats[$training_id]['registrations'] ++;
				$stats[$training_id]['attendees'] = $stats[$training_id]['attendees'] + $num_attendees;
				$stats[$training_id]['amount'] = $stats[$training_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] );
			}else{
				$stats[$training_id]['registrations'] = 1;
				$stats[$training_id]['upsells'] = 0;
				$stats[$training_id]['attendees'] = $num_attendees;
				$stats[$training_id]['amount'] = cc_voucher_core_curr_convert($payment['currency'], 'GBP', $payment['payment_amount']);
			}
		}
	}
	
	$promoted = cc_mailster_promoted_training($start_timestamp, $end_timestamp);

	// now clear any stats that might have been held in the table for this week
	$where = array(
		'week_start' => $week_start,
	);
	$wpdb->delete($sales_stats_table, $where);

	// and populate the table with the new stats
	$row_format = array( '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%d', '%d' );
	foreach ($stats as $training_id => $values) {
		$row = cc_stats_empty();
		unset($row['id']);
		$row['week_start'] = $week_start;
		$row['training_id'] = $training_id;
		if( isset($promoted[$training_id] ) ){
			$row['promoted'] = $promoted[$training_id];
		}
		$row['registrations'] = $values['registrations'];
		$row['amount'] = $values['amount'];
		$row['upsells'] = $values['upsells'];
		$row['attendees'] = $values['attendees'];
		$wpdb->insert($sales_stats_table, $row, $row_format);

		// and add in to the training cummulative total field
		$cum_regs = (int) get_post_meta($training_id, 'cumulative_registrations', true);
		$cum_amount = (float) get_post_meta($training_id, 'cumulative_reg_amount', true);
		$cum_upsells = (int) get_post_meta($training_id, 'cumulative_upsells', true);
		$cum_attendees = (int) get_post_meta($training_id, 'cumulative_attendees', true);
		$cum_regs = $cum_regs + $values['registrations'];
		$cum_amount = $cum_amount + $values['amount'];
		$cum_upsells = $cum_upsells + $values['upsells'];
		$cum_attendees = $cum_attendees + $values['attendees'];
		update_post_meta($training_id, 'cumulative_registrations', $cum_regs);
		update_post_meta($training_id, 'cumulative_reg_amount', $cum_amount);
		update_post_meta($training_id, 'cumulative_upsells', $cum_upsells);
		update_post_meta($training_id, 'cumulative_attendees', $cum_attendees);
	}

	ccpa_write_log('cc_stats_populate completed');
}

// get the results for the stats page
function cc_stats_get_results($start_date, $end_date){
	// ccpa_write_log('cc_stats_get_results');
	global $wpdb;
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$start_date .= ' 00:00:00';
	$end_date .= ' 23:59:59';
	$sql = "SELECT * FROM $sales_stats_table WHERE training_id > 0 AND week_start >= '$start_date' AND week_start < '$end_date'";
	$rows = $wpdb->get_results($sql, ARRAY_A);
	// ccpa_write_log('table rows ...');
	// ccpa_write_log($rows);

	// collate lists of the required training ids and put the stats into a multi-dimensional array
	$training_ids = array();
	$workshops = array();
	$recordings = array();
	$series = array();
	$stats = array();
	foreach ($rows as $values) {
		$week_start = $values['week_start'];
		$training_id = $values['training_id'];
		$stats[$week_start][$training_id]['registrations'] = $values['registrations'];
		$stats[$week_start][$training_id]['amount'] = $values['amount'];
		$stats[$week_start][$training_id]['promoted'] = $values['promoted'];
		$stats[$week_start][$training_id]['notes'] = $values['notes'];
		$stats[$week_start][$training_id]['category'] = $values['category'];
		$stats[$week_start][$training_id]['upsells'] = $values['upsells'];
		$stats[$week_start][$training_id]['attendees'] = $values['attendees'];
		if(!in_array($training_id, $training_ids) ){
			$post_type = get_post_type($training_id);
			if( $post_type == 'workshop' ){
				$workshops[] = $training_id;
				$training_ids[] = $training_id;
			}elseif( $post_type == 'series' ){
				$series[] = $training_id;
				$training_ids[] = $training_id;
			}elseif( get_post_type($training_id) == 'course' && get_post_meta( $training_id, '_course_type', true ) == 'on-demand' ){
				$recordings[] = $training_id;
				$training_ids[] = $training_id;
			}			
		}
	}

	// if the date range includes this week then we also need to calculate this week
	if(date('D') == 'Mon'){
		$last_monday = date('Y-m-d');
	}else{
		$last_monday = date('Y-m-d', strtotime('last Monday'));
	}
	$last_monday_hours = $last_monday.' 00:00:00';
	if($end_date > $last_monday_hours){
		// we need some calculations for this week
		$payments = cc_paymentdb_get_date_range($last_monday_hours, $end_date);
		// collate the stats
		foreach ($payments as $payment) {
			$training_id = $payment['workshop_id']; // just simpler to write
			$upsell_id = $payment['upsell_workshop_id'];
			$num_attendees = count( cc_attendees_for_payment( $payment['id'] ) );
			if( $training_id > 0 ){
				$post_type = get_post_type( $training_id );
				if( in_array( $post_type, array( 'workshop', 'recording', 'course', 'series' ) ) ){
					if( $upsell_id > 0 ){
						if( isset( $stats[$last_monday][$training_id] ) ){
							$stats[$last_monday][$training_id]['registrations'] ++;
							$stats[$last_monday][$training_id]['attendees'] = $stats[$last_monday][$training_id]['attendees'] + $num_attendees;
							$stats[$last_monday][$training_id]['amount'] = $stats[$last_monday][$training_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] );
						}else{
							$stats[$last_monday][$training_id]['registrations'] = 1;
							$stats[$last_monday][$training_id]['upsells'] = 0;
							$stats[$last_monday][$training_id]['attendees'] = $num_attendees;
							$stats[$last_monday][$training_id]['amount'] = cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] );
						}
						if( isset( $stats[$last_monday][$upsell_id] ) ){
							$stats[$last_monday][$upsell_id]['upsells'] ++;
							$stats[$last_monday][$upsell_id]['attendees'] = $stats[$last_monday][$upsell_id]['attendees'] + $num_attendees;
							$stats[$last_monday][$upsell_id]['amount'] = $stats[$last_monday][$upsell_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] );
						}else{
							$stats[$last_monday][$upsell_id]['registrations'] = 0;
							$stats[$last_monday][$upsell_id]['upsells'] = 1;
							$stats[$last_monday][$upsell_id]['attendees'] = $num_attendees;
							$stats[$last_monday][$upsell_id]['amount'] = cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] );
						}
						if( ! in_array( $training_id, $training_ids ) ){
							if( $post_type == 'workshop' ){
								$workshops[] = $training_id;
							}elseif( $post_type == 'series' ){
								$series[] = $training_id;
							}else{
								$recordings[] = $training_id;
							}
							$training_ids[] = $training_id;
						}
						if( ! in_array( $upsell_id, $training_ids ) ){
							$upsell_post_type = get_post_type( $upsell_id );
							if( $upsell_post_type == 'workshop' ){
								$workshops[] = $upsell_id;
							}elseif( $upsell_post_type == 'series' ){
								$series[] = $upsell_id;
							}else{
								$recordings[] = $upsell_id;
							}
							$training_ids[] = $upsell_id;
						}
					}else{
						if( isset( $stats[$last_monday][$training_id] ) ){
							$stats[$last_monday][$training_id]['registrations'] ++;
							$stats[$last_monday][$training_id]['attendees'] = $stats[$last_monday][$training_id]['attendees'] + $num_attendees;
							$stats[$last_monday][$training_id]['amount'] = $stats[$last_monday][$training_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] );
						}else{
							$stats[$last_monday][$training_id]['registrations'] = 1;
							$stats[$last_monday][$training_id]['upsells'] = 0;
							$stats[$last_monday][$training_id]['attendees'] = $num_attendees;
							$stats[$last_monday][$training_id]['amount'] = cc_voucher_core_curr_convert($payment['currency'], 'GBP', $payment['payment_amount']);
						}
						if( ! in_array( $training_id, $training_ids ) ){
							if( $post_type == 'workshop' ){
								$workshops[] = $training_id;
							}elseif( $post_type == 'series' ){
								$series[] = $training_id;
							}else{
								$recordings[] = $training_id;
							}
							$training_ids[] = $training_id;
						}
					}
				}
			}

			/* was ...
			if( $training_id > 0 ){
				$post_type = get_post_type( $training_id );
				if( in_array( $post_type, array( 'workshop', 'recording' ) ) ){
					if(isset($stats[$last_monday][$training_id])){
						$stats[$last_monday][$training_id]['registrations'] ++;
						$stats[$last_monday][$training_id]['amount'] = $stats[$last_monday][$training_id]['amount'] + cc_voucher_core_curr_convert($payment['currency'], 'GBP', $payment['payment_amount']);
					}else{
						$stats[$last_monday][$training_id]['registrations'] = 1;
						$stats[$last_monday][$training_id]['amount'] = cc_voucher_core_curr_convert($payment['currency'], 'GBP', $payment['payment_amount']);
					}
					if(!in_array($training_id, $training_ids) ){
						if(get_post_type($training_id) == 'workshop'){
							$workshops[] = $training_id;
						}else{
							$recordings[] = $training_id;
						}
						$training_ids[] = $training_id;
					}
				}
			}
			*/
		}
	}


	// ccpa_write_log('stats array ...');
	// ccpa_write_log($stats);
	// ccpa_write_log('workshops array ...');
	// ccpa_write_log($workshops);
	// ccpa_write_log('recordings array ...');
	// ccpa_write_log($recordings);

	rsort($workshops);
	rsort($recordings);
	rsort($series);

	return array(
		'stats' => $stats,
		'workshops' => $workshops,
		'recordings' => $recordings,
		'series' => $series,
	);
}

// get notes
add_action('wp_ajax_cc_stats_get_notes', 'cc_stats_get_notes');
function cc_stats_get_notes(){
	// ccpa_write_log('cc_stats_get_notes');
	// ccpa_write_log($_REQUEST);
	global $wpdb;
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$training_id = 0;
	$training_title = 'unknown';
	$week_comm = 'unknown';
	$notes = 'unknown';
	$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $_GET['wk'].' 00:00:00', new DateTimeZone('UTC'));
	if($datetime){
		$training_id = absint($_GET['t']);
		$week_start = $datetime->format('Y-m-d');
		$sql = "SELECT * FROM $sales_stats_table WHERE week_start = '$week_start' AND training_id = $training_id";
		$results = $wpdb->get_row( $sql, ARRAY_A );
		$training_title = get_the_title($training_id);
		$week_comm = $datetime->format('jS M Y');
	}
	$html = '<h2>Notes</h2>';
	$html .= '<p>Training: '.$training_id.': '.$training_title.'</p>';
	$html .= '<p>Week commencing: '.$week_comm.'</p>';
	$html .= '<p><label for="cc-stats-notes">Notes:</label><br><textarea id="cc-stats-notes" class="widefat" rows="8">'.$results['notes'].'</textarea></p>';
	$html .= '<p style="text-align:right;"><a href="javascript:void(0)" id="ss-notes-upd" class="button button-primary" data-ssid="'.$results['id'].'">Update</a></p>';
	echo $html;
	die();
}

// update the notes
add_action('wp_ajax_cc_stats_notes_update', 'cc_stats_notes_update');
function cc_stats_notes_update(){
	global $wpdb;
	$response = array(
		'status' => 'error',
	);
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$data = array(
		'notes' => sanitize_textarea_field($_POST['notes']),
	);
	$where = array(
		'id' => absint($_POST['ssid']),
	);
	if($wpdb->update( $sales_stats_table, $data, $where ) !== false){
		$response['status'] = 'ok';
	}
	echo json_encode($response);
	die();
}

function cc_stats_get_daily_results($start_date, $end_date){
	// ccpa_write_log('cc_stats_get_daily_results');
	global $wpdb;
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$start_date .= ' 00:00:00';
	$end_date .= ' 23:59:59';

	$payments = cc_paymentdb_get_date_range($start_date, $end_date);

	// collate the stats
	$stats = array();
	foreach ($payments as $payment) {
		$training_id = $payment['workshop_id']; // just simpler to write
		$upsell_id = $payment['upsell_workshop_id'];
		$num_attendees = count( cc_attendees_for_payment( $payment['id'] ) );
		$reg_date = substr($payment['last_update'], 0, 10);
		if( isset( $stats[$reg_date][$training_id] ) ){
			$stats[$reg_date][$training_id]['registrations'] ++;
			$stats[$reg_date][$training_id]['attendees'] = $stats[$reg_date][$training_id]['attendees'] + $num_attendees;
			$stats[$reg_date][$training_id]['amount'] = $stats[$reg_date][$training_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] );
		}else{
			$stats[$reg_date][$training_id]['registrations'] = 1;
			$stats[$reg_date][$training_id]['upsells'] = 0;
			$stats[$reg_date][$training_id]['attendees'] = $num_attendees;
			$stats[$reg_date][$training_id]['amount'] = cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] );
		}
		if( $upsell_id > 0 ){
			if( isset( $stats[$reg_date][$upsell_id] ) ){
				$stats[$reg_date][$upsell_id]['upsells'] ++;
				$stats[$reg_date][$upsell_id]['attendees'] = $stats[$reg_date][$upsell_id]['attendees'] + $num_attendees;
				$stats[$reg_date][$upsell_id]['amount'] = $stats[$reg_date][$upsell_id]['amount'] + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] );
			}else{
				$stats[$reg_date][$upsell_id]['registrations'] = 0;
				$stats[$reg_date][$upsell_id]['upsells'] = 1;
				$stats[$reg_date][$upsell_id]['attendees'] = $num_attendees;
				$stats[$reg_date][$upsell_id]['amount'] = cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] );
			}
		}
		/* was ...
		if( isset( $stats[$reg_date][$training_id] ) ){
			$stats[$reg_date][$training_id]['registrations'] ++;
			$stats[$reg_date][$training_id]['amount'] = $stats[$reg_date][$training_id]['amount'] + cc_voucher_core_curr_convert($payment['currency'], 'GBP', $payment['payment_amount']);
		}else{
			$stats[$reg_date][$training_id]['registrations'] = 1;
			$stats[$reg_date][$training_id]['amount'] = cc_voucher_core_curr_convert($payment['currency'], 'GBP', $payment['payment_amount']);
		}
		*/
	}

	// collate lists of the required training ids
	$training_ids = array();
	$workshops = array();
	$recordings = array();
	$series = array();
	foreach ($stats as $reg_date => $values) {
		foreach ($values as $training_id => $data) {
			if(!in_array($training_id, $training_ids) ){
				$post_type = get_post_type( $training_id );
				if( $post_type == 'workshop' ){
					$workshops[] = $training_id;
				}elseif( $post_type == 'series' ){
					$series[] = $training_id;
				}else{
					$recordings[] = $training_id;
				}
				$training_ids[] = $training_id;
			}
		}
	}

	// ccpa_write_log('stats array ...');
	// ccpa_write_log($stats);
	// ccpa_write_log('workshops array ...');
	// ccpa_write_log($workshops);
	// ccpa_write_log('recordings array ...');
	// ccpa_write_log($recordings);

	rsort($workshops);
	rsort($recordings);
	rsort($series);

	return array(
		'stats' => $stats,
		'workshops' => $workshops,
		'recordings' => $recordings,
		'series' => $series,
	);
}

// get cats - select on the weekly sales stats page
add_action('wp_ajax_cc_stats_get_cats', 'cc_stats_get_cats');
function cc_stats_get_cats(){
	global $wpdb;
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$training_id = 0;
	$training_title = 'unknown';
	$week_comm = 'unknown';
	$cat = '';
	$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $_GET['wk'].' 00:00:00', new DateTimeZone('UTC'));
	if($datetime){
		$training_id = absint($_GET['t']);
		$week_start = $datetime->format('Y-m-d');
		$sql = "SELECT * FROM $sales_stats_table WHERE week_start = '$week_start' AND training_id = $training_id";
		$results = $wpdb->get_row( $sql, ARRAY_A );
		$training_title = get_the_title($training_id);
		$week_comm = $datetime->format('jS M Y');
	}
	$sales_stats_cats = get_option('sales_stats_cats', array());
	$html = '<h2>Categories</h2>';
	$html .= '<p>Training: '.$training_id.': '.$training_title.'</p>';
	$html .= '<p>Week commencing: '.$week_comm.'</p>';
	$html .= '<p><label for="cc-stats-cats">Category:</label><br><select id="cc-stats-cats" class="cc-stats-cats" class="widefat"><option value="">No category</option>';
	$css = '';
	foreach ($sales_stats_cats as $key => $cat) {
		$html .= '<option value="'.$key.'" '.selected($key, $results['category'], false).'>'.$cat['cat_name'].'</option>';
		$css .= '.cc-stats-cats option[value="'.$key.'"] {background:'.$cat['colour'].';} ';
	}
	$html .= '</select></p>';
	$html .= '<p style="text-align:right;"><a href="javascript:void(0)" id="ss-cats-upd" class="button button-primary" data-ssid="'.$results['id'].'">Update</a></p>';
	$html .= '<style>'.$css.'</style>';
	echo $html;
	die();
}

// update the sales stats cat for a given week/training
add_action('wp_ajax_cc_stats_cat_update', 'cc_stats_cat_update');
function cc_stats_cat_update(){
	global $wpdb;
	$response = array(
		'status' => 'error',
	);
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$data = array(
		'category' => ($_POST['cat']),
	);
	$where = array(
		'id' => absint($_POST['ssid']),
	);
	if($wpdb->update( $sales_stats_table, $data, $where ) !== false){
		$response['status'] = 'ok';
	}
	echo json_encode($response);
	die();
}

// get the cumulative stats for a training
// no longer relying on the meta data as that seems to be randomly wrong!
function cc_stats_cumulative($training_id){
	// ccpa_write_log('function cc_stats_cumulative training_id: '.$training_id);
	global $wpdb;
	// get past weeks from the sales stats table
	$sales_stats_table = $wpdb->prefix.'sales_stats';
	$payments_table = $wpdb->prefix.'ccpa_payments';
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$sql = "SELECT * FROM $sales_stats_table WHERE training_id = $training_id";
	$stats = $wpdb->get_results($sql, ARRAY_A);
	$latest_date = '0000-00-00';
	$cum_regs = $cum_amount = $cum_upsells = $cum_attendees = 0;
	foreach ($stats as $stat) {
		if($stat['week_start'] > $latest_date){
			$latest_date = $stat['week_start'];
		}
		$cum_regs = $cum_regs + $stat['registrations'];
		$cum_amount = $cum_amount + $stat['amount'];
		$cum_upsells = $cum_upsells + $stat['upsells'];
		$cum_attendees = $cum_attendees + $stat['attendees'];
	}
	// ccpa_write_log('past data collected');
	// now also add in any payments since the stats were last calculated
	$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $latest_date.' 00:00:00', new DateTimeZone('UTC'));
	$datetime->modify('+7 days');
	$start = $datetime->format('Y-m-d H:i:s');
	$date_now = new DateTime("now", new DateTimeZone("UTC"));
	$end = $date_now->format('Y-m-d H:i:s');
	$sql = "SELECT * FROM $payments_table WHERE ( workshop_id = $training_id OR upsell_workshop_id = $training_id ) AND last_update >= '$start' AND last_update <= '$end'";
	$payments = $wpdb->get_results($sql, ARRAY_A);
	foreach ($payments as $payment) {
		if( $payment['workshop_id'] == $training_id ){
			$cum_regs ++;
			$cum_amount = round( $cum_amount + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['payment_amount'] - $payment['upsell_payment_amount'] ), 2 ) ;
			$cum_attendees = $cum_attendees + count( cc_attendees_for_payment( $payment['id'] ) );
		}elseif( $payment['upsell_workshop_id'] == $training_id ){
			$cum_upsells ++;
			$cum_amount = round( $cum_amount + cc_voucher_core_curr_convert( $payment['currency'], 'GBP', $payment['upsell_payment_amount'] ), 2 ) ;
			$cum_attendees = $cum_attendees + count( cc_attendees_for_payment( $payment['id'] ) );
		}
	}
	// ccpa_write_log('current data collected');
	// and get the attendees
	/* was ...
	$sql = "SELECT 
				COUNT(*) 
			FROM $payments_table AS p 
			LEFT JOIN $attendees_table AS a 
				ON p.id = a.payment_id
			WHERE
				p.workshop_id = $training_id
				OR p.upsell_workshop_id = $training_id
			";
	$cum_attends = $wpdb->get_var($sql);
	*/
	// $cum_attends = 1;
	// ccpa_write_log('attendees collected');
	return array(
		'registrations' => $cum_regs,
		'upsells' => $cum_upsells,
		'amount' => $cum_amount,
		'attendees' => $cum_attendees,
	);
}