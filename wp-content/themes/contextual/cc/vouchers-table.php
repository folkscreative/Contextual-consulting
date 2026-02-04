<?php
/**
 * Voucher table stuff
 */

// adds or updates the vouchers and vouchers/payment tables
// datetime fields all held as UTC
add_action('init', 'ccpa_voucher_tables_init');
function ccpa_voucher_tables_init(){
	global $wpdb;
	$ccpa_vouchers_table_ver = 1;
	$installed_table_ver = get_option('ccpa_vouchers_table_ver');
	if($installed_table_ver <> $ccpa_vouchers_table_ver){
		$table_name = $wpdb->prefix.'ccpa_vouchers';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			voucher_code varchar(12) NOT NULL,
			issue_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			expiry_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			currency varchar(3) NOT NULL,
			amount decimal(7,2) NOT NULL,
			redeemed decimal(7,2) NOT NULL,
			balance decimal(7,2) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccpa_vouchers_table_ver', $ccpa_vouchers_table_ver);
	}
	// type is the type of connection. This can be "issue", "pay" or "expiry"
	// amount is positive for issue and negative for pay and expiry
	// for expiry, the payment_id = 0
	$ccpa_vouchers_payments_table_ver = 2;
	$installed_table_ver = get_option('ccpa_vouchers_payments_table_ver');
	if($installed_table_ver <> $ccpa_vouchers_payments_table_ver){
		$table_name = $wpdb->prefix.'ccpa_vouchers_pmts';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			voucher_code varchar(12) NOT NULL,
			payment_id mediumint(9) NOT NULL,
			type varchar(15) NOT NULL,
			amount decimal(7,2) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('ccpa_vouchers_payments_table_ver', $ccpa_vouchers_payments_table_ver);
	}
}

// voucher row format
function ccpa_voucher_row_format(){
	return array(
		'%d', // id mediumint(9) NOT NULL AUTO_INCREMENT,
		'%s', // voucher_code varchar(12) NOT NULL,
		'%s', // issue_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		'%s', // expiry_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		'%s', // currency varchar(3) NOT NULL,
		'%f', // amount decimal(7,2) NOT NULL,
		'%f', // redeemed decimal(7,2) NOT NULL,
		'%f', // balance decimal(7,2) NOT NULL,
	);
}

// gets a voucher row
// Returns null if no result is found
function ccpa_voucher_table_get($voucher_code){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers';
	$sql = "SELECT * FROM $table_name WHERE voucher_code = '$voucher_code' LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// gets all vouchers
function ccpa_voucher_table_get_all(){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers';
	$sql = "SELECT * FROM $table_name ORDER BY id";
	return $wpdb->get_results($sql, ARRAY_A);
}

// gets all expired vouchers (with a balance)
function ccpa_voucher_table_get_expired(){
	global $wpdb;
	$now = date('Y-m-d H:i:s');
	$table_name = $wpdb->prefix.'ccpa_vouchers';
	$sql = "SELECT * FROM $table_name WHERE expiry_time < '$now' AND balance != 0";
	return $wpdb->get_results($sql, ARRAY_A);
}

// inserts or updates a voucher row
// returns $id of inserted row or number of rows updated
function ccpa_voucher_table_update($voucher){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers';
	if($voucher['id'] == 0){
		// insert
        // Remove id from array for insert
        unset($voucher['id']);
        // Format array without id field
        $format = array(
            '%s', // voucher_code
            '%s', // issue_time
            '%s', // expiry_time
            '%s', // currency
            '%f', // amount
            '%f', // redeemed
            '%f', // balance
        );
		$wpdb->insert( $table_name, $voucher, $format );
		return $wpdb->insert_id;
	}else{
		// update
		$where = array(
			'id' => $voucher['id'],
		);
		return $wpdb->update( $table_name, $voucher, $where, ccpa_voucher_row_format() );
	}
}

// inserts or updates a voucher payments row
// returns $id of inserted row or number of rows updated
function ccpa_voucher_payments_table_update($voucher_pmts){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers_pmts';
	if($voucher_pmts['id'] == 0){
		// insert
		$wpdb->insert( $table_name, $voucher_pmts, array('%d', '%s', '%d', '%s', '%f') );
		return $wpdb->insert_id;
	}else{
		// update
		$where = array(
			'id' => $voucher_pmts['id'],
		);
		return $wpdb->update( $table_name, $voucher_pmts, $where, array('%d', '%s', '%d', '%s', '%f') );
	}
}

// gets the payment id that resulted in a voucher being issued
// Returns NULL if no result is found
function ccpa_voucher_issue_pmt_id($voucher_code){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers_pmts';
	$sql = "SELECT payment_id FROM $table_name WHERE voucher_code = '$voucher_code' AND type = 'issue' LIMIT 1";
	return $wpdb->get_var( $sql );
}

// returns an array of all payment ids that have been part paid using a voucher
function ccpa_voucher_pay_pmts($voucher_code){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers_pmts';
	$sql = "SELECT payment_id FROM $table_name WHERE voucher_code = '$voucher_code' AND type = 'pay' ORDER BY payment_id";
	return $wpdb->get_col( $sql );
}

// gets all voucher payment rows
function ccpa_voucher_pay_rows($voucher_code){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers_pmts';
	$sql = "SELECT * FROM $table_name WHERE voucher_code = '$voucher_code' AND type = 'pay' ORDER BY payment_id";
	return $wpdb->get_results( $sql, ARRAY_A );
}

// gets a voucher expiry trans
// If no matching rows are found, the return value will be an empty array
function ccpa_voucher_expiry($voucher_code){
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_vouchers_pmts';
	$sql = "SELECT * FROM $table_name WHERE voucher_code = '$voucher_code' AND type = 'expiry'";
	return $wpdb->get_results( $sql, ARRAY_A );
}