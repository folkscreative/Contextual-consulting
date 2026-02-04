<?php
/**
 * Refer a Friend functions
 */

// adds or updates the RAF tables
// datetime fields all held as UTC
add_action('init', 'cc_friend_tables_init');
function cc_friend_tables_init(){
	global $wpdb;
	// one row for each raf_code once the code has been issued (even if not used)
	// currency will be set to the default currency for that user
	// v2 added expired
	$cc_friend_user_codes_table_ver = 2;
	$installed_table_ver = get_option('cc_friend_user_codes_table_ver');
	if($installed_table_ver <> $cc_friend_user_codes_table_ver){
		$table_name = $wpdb->prefix.'cc_friend_user_codes';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			raf_id mediumint(9) NOT NULL AUTO_INCREMENT,
			raf_code varchar(12) NOT NULL,
			user_id mediumint(9) NOT NULL,
			currency varchar(3) NOT NULL,
			credited decimal(7,2) NOT NULL,
			redeemed decimal(7,2) NOT NULL,
			expired decimal(7,2) NOT NULL,
			balance decimal(7,2) NOT NULL,
			PRIMARY KEY  (raf_id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_friend_user_codes_table_ver', $cc_friend_user_codes_table_ver);
	}
	// one row for each credit issued when a friend registers and uses the code
	// v2 added expired
	$cc_friend_credits_table_ver = 2;
	$installed_table_ver = get_option('cc_friend_credits_table_ver');
	if($installed_table_ver <> $cc_friend_credits_table_ver){
		$table_name = $wpdb->prefix.'cc_friend_credits';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			credit_id mediumint(9) NOT NULL AUTO_INCREMENT,
			raf_id mediumint(9) NOT NULL,
			friend_id mediumint(9) NOT NULL,
			issue_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			expiry_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			value decimal(7,2) NOT NULL,
			redeemed decimal(7,2) NOT NULL,
			expired decimal(7,2) NOT NULL,
			balance decimal(7,2) NOT NULL,
			PRIMARY KEY  (credit_id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_friend_credits_table_ver', $cc_friend_credits_table_ver);
	}
	// type = 'redeemed', 'expired'
	// amount is always positive!
	$cc_friend_usage_table_ver = 1;
	$installed_table_ver = get_option('cc_friend_usage_table_ver');
	if($installed_table_ver <> $cc_friend_usage_table_ver){
		$table_name = $wpdb->prefix.'cc_friend_usage';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			credit_id mediumint(9) NOT NULL,
			txn_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			type varchar(15) NOT NULL,
			amount decimal(7,2) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_friend_usage_table_ver', $cc_friend_usage_table_ver);
	}
}

// gets the usage
function cc_friend_get_usage( $by, $find ){
	global $wpdb;
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$sql = "SELECT * FROM $friend_user_codes_table WHERE $by = '$find' LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// does a RAF code exist?
// Returns bool
function cc_friend_code_exists($raf_code){
	global $wpdb;
	$refer_friend_active = get_option('refer_friend_active', '');
	if($refer_friend_active <> 'active'){
		return false;
	}
	$table_name = $wpdb->prefix.'cc_friend_user_codes';
	$sql = "SELECT raf_id FROM $table_name WHERE raf_code = '$raf_code' LIMIT 1";
	$raf_id = $wpdb->get_var($sql);
	if($raf_id === NULL){
		return false;
	}
	return true;
}

// Can a friend code be used?
// RAF must be active, code must exist, referrer must be flagged for raf
function cc_friend_code_can_i_use($raf_code){
	global $wpdb;
	$refer_friend_active = get_option('refer_friend_active', '');
	if($refer_friend_active <> 'active'){
		return false;
	}
	$table_name = $wpdb->prefix.'cc_friend_user_codes';
	$sql = "SELECT * FROM $table_name WHERE raf_code = '$raf_code' LIMIT 1";
	$raf_row = $wpdb->get_row($sql, ARRAY_A);
	if($raf_row === NULL){
		return false;
	}
	$raf_flag = get_user_meta( $raf_row['user_id'], 'refer_a_friend', true );
	if( $raf_flag == 'yes' ){
		return true;
	}
	return false;
}

// lookup the friend code for a user
// returns NULL if not found
function cc_friend_user_code_lookup($user_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'cc_friend_user_codes';
	$sql = "SELECT raf_code FROM $table_name WHERE user_id = '$user_id' LIMIT 1";
	return $wpdb->get_var($sql);
}

// save a new RAF code 
function cc_friend_insert_code($user_id, $raf_code){
	global $wpdb;
	$table_name = $wpdb->prefix.'cc_friend_user_codes';
	$data = array(
		'raf_code' => $raf_code,
		'user_id' => $user_id,
		'currency' => cc_currency_get_user_currency($user_id),
		'credited' => 0,
		'redeemed' => 0,
		'expired' => 0,
		'balance' => 0,
	);
	$format = array( '%s', '%d', '%s', '%f', '%f', '%f', '%f' );
	$wpdb->insert( $table_name, $data, $format );
	return $wpdb->insert_id;
}

// should we show the My Account Refer a Friend page?
// either the option to refer a friend must be on, globally and for this user
// or their code must have been used
function cc_friend_show_my_acct(){
	$user_id = get_current_user_id();
	$raf_code = cc_friend_user_code_lookup( $user_id );
	if( cc_friend_been_used( $raf_code ) ){
		return true;
	}
	$refer_friend_active = get_option('refer_friend_active', '');
	$raf_flag = get_user_meta( $user_id, 'refer_a_friend', true );
	if( $refer_friend_active == 'active' && $raf_flag == 'yes' ){
		return true;
	}
	return false;
}

// returns a RAF code for a user
// it will lookup theirs if they have one or create (and save) one if not
function cc_friend_user_code($user_id){
	$raf_code = cc_friend_user_code_lookup($user_id);
	if( $raf_code === NULL ){
		$raf_code = cc_friend_code_generate();
		$raf_id = cc_friend_insert_code($user_id, $raf_code);
	}
	return $raf_code;
}

// generate a valid, unique RAF code
function cc_friend_code_generate(){
	$characters = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
	do {
		$raf_code = 'CC-';
		for ($i=0; $i < 9; $i++) { 
			$raf_code .= $characters[mt_rand(0, 61)];
		}
	} while ( cc_friend_code_exists($raf_code) );
	return $raf_code;
}

// lookup a code to see if a friend is ok to use it
// the code must be valid and this friend must not have already used it
// the giver is not allowed to use their own code
// returns bool
// $user_id is the friend, not the original user
function cc_friend_can_i_use_it($raf_code, $user_id){
	global $wpdb;
	if ( ! cc_friend_code_can_i_use($raf_code) ){
		return false;
	}
	// if the friend is not already a user then we'll allow them to use it
	if( $user_id == 0 ){
		return true;
	}
	// is this the user's own code
	if( $raf_code == cc_friend_user_code_lookup($user_id) ){
		return false;
	}
	// has this friend already used it?
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$friend_credits_table = $wpdb->prefix.'cc_friend_credits';
	$sql = "SELECT * FROM $friend_user_codes_table AS u LEFT JOIN $friend_credits_table AS c ON u.raf_id = c.raf_id WHERE u.raf_code = '$raf_code' AND c.friend_id = '$user_id'";
	$friend_usage = $wpdb->get_results($sql, ARRAY_A);
	if( ! empty( $friend_usage ) ){
		return false;
	}
	return true;
}

// a friend code has been used
// record that fact and allocate a credit to the code giver
// $user_id is the recipient, not the giver
function cc_friend_code_used( $raf_code, $user_id, $currency, $amount ){
	global $wpdb;
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$friend_credits_table = $wpdb->prefix.'cc_friend_credits';

	if( ! cc_friend_can_i_use_it($raf_code, $user_id) ) return false;

	$sql = "SELECT * FROM $friend_user_codes_table WHERE raf_code = '$raf_code' LIMIT 1";
	$user_codes = $wpdb->get_row($sql, ARRAY_A);
	if( empty( $user_codes ) ) return false;
	
	// insert the new credit amount
	$date = new DateTime('now');
	$issue_time = $date->format('Y-m-d H:i:s');
	$date->modify('+1 year');
	$expiry_time = $date->format('Y-m-d') . ' 23:59:59';
	$converted_amount = cc_voucher_core_curr_convert($currency, $user_codes['currency'], $amount);
	$data = array(
		'raf_id' => $user_codes['raf_id'],
		'friend_id' => $user_id,
		'issue_time' => $issue_time,
		'expiry_time' => $expiry_time,
		'value' => $converted_amount,
		'redeemed' => 0,
		'balance' => $converted_amount,
	);
	$format = array( '%d', '%d', '%s', '%s', '%f', '%f', '%f');
	$wpdb->insert( $friend_credits_table, $data, $format );

	// update the giver's balance
	$new_credited = $user_codes['credited'] + $converted_amount;
	$new_balance = $user_codes['balance'] + $converted_amount;
	$data = array(
		'credited' => $new_credited,
		'balance' => $new_balance,
	);
	$where = array(
		'raf_id' => $user_codes['raf_id'],
	);
	$format = array('%f', '%f');
	$wpdb->update( $friend_user_codes_table, $data, $where, $format );

	// trigger an email to go out to the giver to tell them thay have just received a credit
	cc_mailster_raf_credit_issue( $user_codes['user_id'] );
}

// has the code ever been used?
function cc_friend_been_used( $raf_code ){
	global $wpdb;
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$sql = "SELECT credited FROM $friend_user_codes_table WHERE raf_code = '$raf_code' LIMIT 1";
	$credited = $wpdb->get_var($sql);
	if( $credited === NULL || $credited == 0 ) return false;
	return true;
}

// redeem a raf credit
function cc_friend_redeem( $raf_code, $user_id, $currency, $amount ){
	// ccpa_write_log('function cc_friend_redeem');
	// ccpa_write_log('raf_code: '.$raf_code);
	// ccpa_write_log('user_id: '.$user_id);
	// ccpa_write_log('currency: '.$currency);
	// ccpa_write_log('amount: '.$amount);
	global $wpdb;
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$friend_credits_table = $wpdb->prefix.'cc_friend_credits';
	$friend_usage_table = $wpdb->prefix.'cc_friend_usage';

	$usage = cc_friend_get_usage( 'raf_code', $raf_code );
	// ccpa_write_log($usage);

	$converted_amount = cc_voucher_core_curr_convert($currency, $usage['currency'], $amount);
	// ccpa_write_log($converted_amount);

	// get the credits that still have a balance
	// $sql = "SELECT * FROM $friend_credits_table WHERE raf_id = ".$usage['raf_id']." AND balance > 0 AND expiry_time < '".date('Y-m-d H:i:s')."' ORDER BY issue_time ASC";
	$sql = $wpdb->prepare(
	    "SELECT * FROM $friend_credits_table WHERE raf_id = %d AND balance > 0 AND expiry_time > %s ORDER BY issue_time ASC",
	    $usage['raf_id'],
	    current_time('mysql')
	);
	$credit_rows = $wpdb->get_results($sql, ARRAY_A);
	// ccpa_write_log($credit_rows);
	$to_allocate = $converted_amount;
	foreach ($credit_rows as $credit) {
		if( $to_allocate > 0 ){
			if( $to_allocate > $credit['balance'] ){
				$this_amount = $credit['balance'];
			}else{
				$this_amount = $to_allocate;
			}

			// insert a new usage redemption row
			$data = array(
				'credit_id' => $credit['credit_id'],
				'txn_time' => date('Y-m-d H:i:s'),
				'type' => 'redeemed',
				'amount' => $this_amount,
			);
			$format = array('%d', '%s', '%s', '%f');
			$wpdb->insert( $friend_usage_table, $data, $format );

			// update the credit row
			$new_balance = $credit['balance'] - $this_amount;
			$data = array(
				'redeemed' => $this_amount,
				'balance' => $new_balance,
			);
			$where = array(
				'credit_id' => $credit['credit_id']
			);
			$format = array('%f', '%f');
			$wpdb->update( $friend_credits_table, $data, $where, $format );

			$to_allocate = $to_allocate - $this_amount;
		}
	}

	// update the usage row
	$new_redeemed = $usage['redeemed'] + $converted_amount - $to_allocate; // $to_allocate should be zero ...
	$new_balance = $usage['balance'] - $converted_amount + $to_allocate;
	$data = array(
		'redeemed' => $new_redeemed,
		'balance' => $new_balance,
	);
	$where = array(
		'raf_id' => $usage['raf_id'],
	);
	$format = array('%f', '%f');
	$wpdb->update( $friend_user_codes_table, $data, $where, $format );
}

// finds the next expiry date and amount for a referrer
function cc_friend_next_expiry( $user_id ){
	global $wpdb;

	$response = array(
		'expiry_date' => '',
		'currency' => '',
		'amount' => 0,
	);

	$friend_credits_table = $wpdb->prefix.'cc_friend_credits';

	$usage = cc_friend_get_usage( 'user_id', $user_id );

	if( $usage['balance'] > 0 ){
		// get the credits with a balance in date order
		$sql = "SELECT * FROM $friend_credits_table WHERE raf_id = ".$usage['raf_id']." AND balance > 0 ORDER BY expiry_time ASC";
		$credits = $wpdb->get_results($sql, ARRAY_A);
		$first_date = '';
		$amount = 0;
		foreach ($credits as $credit) {
			$expiry_date = substr( $credit['expiry_time'], 0, 10 );
			if( $first_date == '' || $expiry_date == $first_date ){
				$amount = $amount + $credit['balance'];
				$first_date = $expiry_date;
			}else{
				break;
			}
		}

		if( $amount > 0 ){
			$date = date_create_from_format( 'Y-m-d', $expiry_date );
			$response['expiry_date'] = $date->format('j M Y');
			$response['currency'] = $usage['currency'];
			$response['amount'] = $amount;
		}
	}
	return $response;
}

// get the stats for the backend
function cc_friend_stats(){
	global $wpdb;
	
	$response = array(
		'givers' => 0,
		'credits' => 0,
		'credit_value' => 0,
		'redeemed' => 0,
		'expired' => 0,
		'balance' => 0,
	);

	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$friend_credits_table = $wpdb->prefix.'cc_friend_credits';
	$friend_usage_table = $wpdb->prefix.'cc_friend_usage';

	$sql = "SELECT * FROM $friend_user_codes_table";
	$usages = $wpdb->get_results($sql, ARRAY_A);
	foreach ($usages as $usage) {
		if( $usage['credited'] > 0 ){
			$response['givers'] ++;
		}
		$response['credit_value'] = $response['credit_value'] + cc_voucher_core_curr_convert( $usage['currency'], 'GBP', $usage['credited'] );
		$response['redeemed'] = $response['redeemed'] + cc_voucher_core_curr_convert( $usage['currency'], 'GBP', $usage['redeemed'] );
		$response['expired'] = $response['expired'] + cc_voucher_core_curr_convert( $usage['currency'], 'GBP', $usage['expired'] );
		$response['balance'] = $response['balance'] + cc_voucher_core_curr_convert( $usage['currency'], 'GBP', $usage['balance'] );
	}

	$sql = "SELECT COUNT(*) FROM $friend_credits_table";
	$response['credits'] = $wpdb->get_var($sql);

	return $response;
}

// get the top referrers for the stats
function cc_friend_top_referrers(){
	global $wpdb;
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$sql = "SELECT * FROM $friend_user_codes_table ORDER BY credited DESC LIMIT 10";
	return $wpdb->get_results($sql, ARRAY_A);
}

// number of users with the refer a friend flag set
function cc_friend_raf_flags_set(){
	global $wpdb;
	$sql = "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'refer_a_friend'";
	return $wpdb->get_var($sql);
}

// schedules the daily cron job to expire unused credits
add_action('init', function(){
	if(!wp_next_scheduled('cc_friend_expiry_cron')){
		wp_schedule_event( strtotime('tomorrow'), 'daily', 'cc_friend_expiry_cron');
	}
});

// for testing only .....
add_shortcode('force_friend_expiry_cron', 'cc_friend_expiry_cronjob');

// run the daily check
add_action('cc_friend_expiry_cron', 'cc_friend_expiry_cronjob');
function cc_friend_expiry_cronjob(){
	global $wpdb;
	$friend_user_codes_table = $wpdb->prefix.'cc_friend_user_codes';
	$friend_credits_table = $wpdb->prefix.'cc_friend_credits';
	$friend_usage_table = $wpdb->prefix.'cc_friend_usage';
	// find expired credits
	$now = date('Y-m-d H:i:s');
	$sql = "SELECT * FROM $friend_credits_table WHERE balance > 0 and expiry_time < '$now'";
	$credits = $wpdb->get_results($sql, ARRAY_A);
	foreach ($credits as $credit) {
		// insert a new usage expiry row
		$data = array(
			'credit_id' => $credit['credit_id'],
			'txn_time' => $now,
			'type' => 'expired',
			'amount' => $credit['balance'],
		);
		$format = array('%d', '%s', '%s', '%f');
		$wpdb->insert( $friend_usage_table, $data, $format );

		// update the credit row
		$data = array(
			'expired' => $credit['balance'],
			'balance' => 0,
		);
		$where = array(
			'credit_id' => $credit['credit_id']
		);
		$format = array('%f', '%f');
		$wpdb->update( $friend_credits_table, $data, $where, $format );

		// get the user codes row
		$usage = cc_friend_get_usage( 'raf_id', $credit['raf_id'] );
		$data = array(
			'expired' => $usage['expired'] + $credit['balance'],
			'balance' => $usage['balance'] - $credit['balance'],
		);
		$where = array(
			'raf_id' => $usage['raf_id'],
		);
		$format = array('%f', '%f');
		$wpdb->update( $friend_user_codes_table, $data, $where, $format );
	}
}

// use a mailster list to set the RAF flag
// add_shortcode( 'subscribe_list_to_raf', 'cc_friend_subscribe_list_to_raf' );
function cc_friend_subscribe_list_to_raf(){
	$list_id = 10000168;
	// $list_id = 10000081;
	$html = 'cc_friend_subscribe_list_to_raf list: '.$list_id;
	// get the subscribers
	// acquired and modified from subscribers table class prepare_items function
	$args = array(
		'status'     => false,
		's'          => null,
		'strict'     => null,
		'lists'      => array($list_id),
		'conditions' => null,
	);
	$subscribers = mailster( 'subscribers' )->query(
		wp_parse_args(
			$args,
			array(
				'calc_found_rows' => true,
				'orderby'         => 'id',
				'order'           => 'DESC',
				'fields'          => array( 'ID', 'email', 'rating', 'wp_id', 'status', 'signup' ),
				'limit'           => 999999,
				'offset'          => 0,
			)
		)
	);
	$html .= '<br>'.count($subscribers).' found';
	// now try to set the RAF flag for them
	$count_users = $update_count = 0;
	foreach ($subscribers as $subscriber) {
		// is this a user?
		$user = get_user_by( 'email', $subscriber->email );
		if($user){
			$count_users ++;
			if( update_user_meta( $user->ID, 'refer_a_friend', 'yes') ){
				// true = successful update
				// false = failure or if the value passed to the function is the same as the one that is already in the database
				$update_count ++;
			}
		}
	}
	$html .= '<br>'.$count_users.' users found. '.$update_count.' updated';
	return $html;
}