<?php
/**
 * Client Secret handling
 */

add_action( 'init', 'ccpa_payint_client_secret_table_update' );
function ccpa_payint_client_secret_table_update(){
	global $wpdb;
	$client_secrets_db_ver = 3;
	// v3 added counter so we always use the latest ajax data
	// v2 added payment intent id
	$installed_table_ver = get_option('client_secrets_db_ver');
	if($installed_table_ver <> $client_secrets_db_ver){
		$table_name = $wpdb->prefix.'client_secrets';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			client_secret varchar(255) NOT NULL,
			payment_intent_id varchar(255) NOT NULL,
			status varchar(25) NOT NULL,
			type varchar(20) NOT NULL,
			type_id mediumint(9) NOT NULL,
			counter mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('client_secrets_db_ver', $client_secrets_db_ver);
	}
}

function ccpa_payint_client_secret_add($client_secret_row){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$result = $wpdb->insert( $table_name, $client_secret_row, ccpa_payint_client_secret_insert_format() );
	if($result === false){
		return false;
	}
	return $wpdb->insert_id;
}

function ccpa_payint_client_secret_insert_format(){
	return array(
		'%s', // client_secret varchar(255) NOT NULL,
		'%s', // status varchar(25) NOT NULL,
		'%s', // payment_intent_id varchar(255) NOT NULL,
		'%s', // type varchar(20) NOT NULL,
		'%d', // type_id mediumint(9) NOT NULL,
		'%d', // counter mediumint(9) NOT NULL,
	);
}

// gets a row. Returns null if no result is found
function ccpa_payint_client_secret_get($client_secret){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$sql = "SELECT * FROM $table_name WHERE client_secret = '$client_secret' LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// Returns NULL if no result is found.
function ccpa_payint_client_secret_get_pi_id($client_secret){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$sql = "SELECT payment_intent_id FROM $table_name WHERE client_secret = '$client_secret' LIMIT 1";
	return $wpdb->get_var($sql);
}

// update the counter
// This method returns the number of rows updated, or false if there is an error. Keep in mind that if the $data matches what is already in the database, no rows will be updated, so 0 will be returned.
function ccpa_payint_client_secret_update_counter($id, $counter){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$data = array(
		'counter' => $counter
	);
	$where = array(
		'id' => $id
	);
	return $wpdb->update($table_name, $data, $where);
}

// set a client secret status by id
function ccpa_payint_client_secret_update_status($id, $new_status){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$data = array(
		'status' => $new_status
	);
	$where = array(
		'id' => $id
	);
	return $wpdb->update($table_name, $data, $where);
}

// update status selectively with lock
function ccpa_payint_client_secret_status_update($client_secret, $from, $to){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$wpdb->query("LOCK TABLES $table_name WRITE");
	$sql = "SELECT id FROM $table_name WHERE client_secret = '$client_secret' AND status = '$from' LIMIT 1";
	$cs_id = $wpdb->get_var($sql);
	if($cs_id === NULL){
		$wpdb->query("UNLOCK TABLES");
		return false;
	}
	$where = array(
		'id' => $cs_id
	);
	$data = array(
		'status' => $to
	);
	$result = $wpdb->update($table_name, $data, $where);
	$wpdb->query("UNLOCK TABLES");
	if($result == 1){
		return true;
	}
	return false;
}

// set a client secret status by client_secret
function ccpa_payint_client_secret_update_status_by_cs($client_secret, $new_status){
	global $wpdb;
	$table_name = $wpdb->prefix.'client_secrets';
	$data = array(
		'status' => $new_status
	);
	$where = array(
		'client_secret' => $client_secret
	);
	return $wpdb->update($table_name, $data, $where);
}
