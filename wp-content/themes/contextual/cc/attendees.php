<?php
/**
 * Attendees
 * added in v1.25 Jul 2023
 */

// create/update the tables
add_action('init', 'cc_attendees_update_tables');
function cc_attendees_update_tables(){
	global $wpdb;
	
	// attendees table
	$cc_attendees_table_ver = 1;
	// v1 new table Jul 2023
	$installed_table_ver = get_option('cc_attendees_table_ver');
	if($installed_table_ver <> $cc_attendees_table_ver){
		$table_name = $wpdb->prefix.'cc_attendees';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			payment_id mediumint(9) NOT NULL,
			user_id mediumint(9) NOT NULL,
			registrant char(1) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_attendees_table_ver', $cc_attendees_table_ver);

		if($cc_attendees_table_ver == 1){
			cc_attendees_populate_table();
		}
	}

	// registration attendees table - for holding attendee data while the registration process goes thru
	$cc_reg_attendees_table_ver = 2;
	// v1 new table Feb 2024
	// v2 changed expiry to be datetime, not timestamp
	$installed_table_ver = get_option('cc_reg_attendees_table_ver');
	if($installed_table_ver <> $cc_reg_attendees_table_ver){
		$table_name = $wpdb->prefix.'cc_reg_attendees';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			ccreg varchar(23) NOT NULL,
			expiry datetime NOT NULL,
			registrant char(1) NOT NULL,
			user_id mediumint(9) NOT NULL,
			firstname varchar(255) NOT NULL,
			lastname varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_reg_attendees_table_ver', $cc_reg_attendees_table_ver);

		if( $cc_reg_attendees_table_ver == 2 ){
			cc_reg_attendees_populate_table();
		}
	}

}

// for dev .......
add_shortcode('attendees_populate_table', 'cc_attendees_populate_table');

function cc_attendees_populate_table(){
	ccpa_write_log('function cc_attendees_populate_table');
	global $wpdb;
	$payments_table = $wpdb->prefix.'ccpa_payments';
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$last_payment_id = get_option('attendees_populate_last_id', 0);
	$sql = "SELECT * FROM $payments_table WHERE id > $last_payment_id ORDER BY id ASC";
	$payments = $wpdb->get_results($sql, ARRAY_A);
	ccpa_write_log(count($payments).' payment records found');
	$count_attendees = 0;
	foreach ($payments as $payment) {
		$log_entry = 'payment_id:'.$payment['id'];
		if($payment['att_userid'] > 0){
			$count_attendees ++;
			$log_entry .= ' att_userid:'.$payment['att_userid'];
			$attendee_row = array(
				'payment_id' => $payment['id'],
				'user_id' => $payment['att_userid'],
				'registrant' => '',
			);
			$attendee_row_id = cc_attendee_add($attendee_row);
			if($attendee_row_id){
				$log_entry .= ' inserted:'.$attendee_row_id;
			}else{
				$log_entry .= ' not inserted';
			}
		}elseif($payment['attendee_email'] <> ''){
			$count_attendees ++;
			$log_entry .= ' email:'.$payment['attendee_email'];
			// is this already set up as a user?
			$user = cc_users_get_user($payment['attendee_email']);
			if($user){
				$log_entry .= ' user_id:'.$user->ID;
				$attendee_row = array(
					'payment_id' => $payment['id'],
					'user_id' => $user->ID,
					'registrant' => '',
				);
				$attendee_row_id = cc_attendee_add($attendee_row);
				if($attendee_row_id){
					$log_entry .= ' inserted:'.$attendee_row_id;
				}else{
					$log_entry .= ' not inserted';
				}
			}else{
				// create user
				$log_entry .= ' creating new user:';
				if($payment['attendee_firstname'] == '' && $payment['attendee_lastname'] == ''){
					$username = $payment['attendee_email'].' '.uniqid();
				}else{
					$username = $payment['attendee_firstname'].' '.$payment['attendee_lastname'].' '.uniqid();
				}
				$user_id = wp_create_user( $username, wp_generate_password(), $payment['attendee_email'] );
				update_user_meta($user_id, 'last_login', 'never');
				if($payment['attendee_firstname'] <> '' || $payment['attendee_lastname'] <> ''){
					$args = array(
						'ID' => $user_id,
						'first_name' => $payment['attendee_firstname'],
						'last_name' => $payment['attendee_lastname'],
					);
					wp_update_user($args);
				}
				$log_entry .= $user_id;

				$attendee_row = array(
					'payment_id' => $payment['id'],
					'user_id' => $user_id,
					'registrant' => '',
				);
				$attendee_row_id = cc_attendee_add($attendee_row);
				if($attendee_row_id){
					$log_entry .= ' inserted:'.$attendee_row_id;
				}else{
					$log_entry .= ' not inserted';
				}
			}
		}else{
			// in the past, no attendee meant the registrant was the attendee - only one attendee per registration
			// going forwards, registrants can be attendees or not, as they choose (and pay for)
			if($payment['reg_userid'] > 0){
				$count_attendees ++;
				$log_entry .= ' reg_userid:'.$payment['reg_userid'];
				$attendee_row = array(
					'payment_id' => $payment['id'],
					'user_id' => $payment['reg_userid'],
					'registrant' => 'r',
				);
				$attendee_row_id = cc_attendee_add($attendee_row);
				if($attendee_row_id){
					$log_entry .= ' inserted:'.$attendee_row_id;
				}else{
					$log_entry .= ' not inserted';
				}
			}elseif($payment['email'] <> ''){
				$count_attendees ++;
				$log_entry .= ' email:'.$payment['email'];
				// is this already set up as a user?
				$user = cc_users_get_user($payment['email']);
				if($user){
					$log_entry .= ' user_id:'.$user->ID;
					$attendee_row = array(
						'payment_id' => $payment['id'],
						'user_id' => $user->ID,
						'registrant' => 'r',
					);
					$attendee_row_id = cc_attendee_add($attendee_row);
					if($attendee_row_id){
						$log_entry .= ' inserted:'.$attendee_row_id;
					}else{
						$log_entry .= ' not inserted';
					}
				}else{
					// create user
					$log_entry .= ' creating new user:';
					if($payment['firstname'] == '' && $payment['lastname'] == ''){
						$username = $payment['email'].' '.uniqid();
					}else{
						$username = $payment['firstname'].' '.$payment['lastname'].' '.uniqid();
					}
					$user_id = wp_create_user( $username, wp_generate_password(), $payment['email'] );
					update_user_meta($user_id, 'last_login', 'never');
					if($payment['firstname'] <> '' || $payment['lastname'] <> ''){
						$args = array(
							'ID' => $user_id,
							'first_name' => $payment['firstname'],
							'last_name' => $payment['lastname'],
						);
						wp_update_user($args);
					}
					$log_entry .= $user_id;

					$attendee_row = array(
						'payment_id' => $payment['id'],
						'user_id' => $user_id,
						'registrant' => 'r',
					);
					$attendee_row_id = cc_attendee_add($attendee_row);
					if($attendee_row_id){
						$log_entry .= ' inserted:'.$attendee_row_id;
					}else{
						$log_entry .= ' not inserted';
					}
				}
			}else{
				$log_entry .= ' No user_id or email for this user!';
			}
		}
		ccpa_write_log($log_entry);
		if($count_attendees > 1000){
			update_option('attendees_populate_last_id', $payment['id']);
			break;
		}
	}
	ccpa_write_log($count_attendees.' attendees processed');
	ccpa_write_log('last payment_id:'.$payment['id']);
}

// for dev ...
add_shortcode( 'reg_attendees_populate_table', 'cc_reg_attendees_populate_table' );

function cc_reg_attendees_populate_table(){
	global $wpdb;
	$table_name = $wpdb->prefix.'cc_reg_attendees';
	$html = 'cc_reg_attendees_populate_table';
	$reg_attendees = cc_attendees_reg_get_all();
	$count_inserts = 0;
	foreach ($reg_attendees as $ccreg => $attendee_data) {
		if( $ccreg <> '' ){
			$html .= '<br>';
			foreach ($attendee_data as $key => $value) {
				switch ($key) {
					case 'expiry':
						$expiry = date( 'Y-m-d H:i:s', $value );
						// $html .= ' expiry: '.$expiry;
						break;
					case 'attendees':
						$reg_attendee = array(
							'ccreg' => $ccreg,
							'expiry' => $expiry,
							'registrant' => '',
							'user_id' => 0,
							'firstname' => '',
							'lastname' => '',
							'email' => '',
						);
						foreach ($value as $attendee) {
							foreach ($attendee as $attendee_key => $attendee_value) {
								$reg_attendee[$attendee_key] = $attendee_value;
							}
						}
						$html .= var_export( $reg_attendee, true);
						$result = $wpdb->insert( $table_name, $reg_attendee, array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' ) );
						$count_inserts ++;
						break;

					default:
						$html .= ' key: '.$key.' value: '.var_export( $value, true);
						break;
				}
			}
		}
	}
	$html .= '<br>Done. '.$count_inserts.' attendees migrated.';
	cc_debug_log_anything($html);
	return $html;
}

// add attendee to table (if not already there)
function cc_attendee_add($attendee){
	global $wpdb;
	$attendees_table = $wpdb->prefix.'cc_attendees';
	// already there?
	$sql = "SELECT * FROM $attendees_table WHERE payment_id = ".$attendee['payment_id']." AND user_id = ".$attendee['user_id']." LIMIT 1";
	$row_exists = $wpdb->get_row($sql);
	if($row_exists === NULL){
		// add new ...
		$wpdb->insert($attendees_table, $attendee, array('%d', '%d', '%s'));
		return $wpdb->insert_id;
	}
	// already there
	return false;
}

// delete all attendees for a given payment (handy if the payment is deleted)
// returns the number of rows updated, or false on error
function cc_attendees_delete_for_payment($payment_id){
	global $wpdb;
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$where = array(
		'payment_id' => $payment_id,
	);
	return $wpdb->delete( $attendees_table, $where, array('%d') );
}

// get all attendees for a payment
// always returns an array
function cc_attendees_for_payment($payment_id){
	global $wpdb;
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$sql = "SELECT * FROM $attendees_table WHERE payment_id = $payment_id ORDER BY registrant DESC, user_id ASC";
	return $wpdb->get_results($sql, ARRAY_A);
}

// get attendee info for this registration
function cc_attendees_reg_get($ccreg){
	global $wpdb;
	$table_name = $wpdb->prefix.'cc_reg_attendees';
	$sql = "SELECT * FROM $table_name WHERE ccreg = '$ccreg' ORDER BY id";
	return $wpdb->get_results( $sql, ARRAY_A );
	/*
	$reg_attendees = cc_attendees_reg_get_all();
	if(isset($reg_attendees[$ccreg])){
		return $reg_attendees[$ccreg]['attendees'];
	}
	return false;
	*/
}

// delete attendees from the registration attendees table
// returns the number of rows deleted, or false on error
function cc_reg_attendees_delete( $ccreg ){
	global $wpdb;
	$table_name = $wpdb->prefix.'cc_reg_attendees';
	$where = array(
		'ccreg' => $ccreg,
	);
	return $wpdb->delete( $table_name, $where );
}

// save attendee info during registration
function cc_attendees_reg_save($ccreg, $attendees){
	global $wpdb;
	$table_name = $wpdb->prefix.'cc_reg_attendees';
	// just in case ...
	cc_reg_attendees_delete( $ccreg );

	$expiry = date( 'Y-m-d H:i:s', strtotime( '+1 day' ) );
	$count_inserts = 0;
	foreach ($attendees as $attendee) {
		$data = array(
			'ccreg' => $ccreg,
			'expiry' => $expiry,
			'registrant' => ( isset( $attendee['registrant'] ) ) ? $attendee['registrant'] : '',
			'user_id' => ( isset( $attendee['user_id'] ) ) ? $attendee['user_id'] : '',
			'firstname' => ( isset( $attendee['firstname'] ) ) ? substr( $attendee['firstname'] , 0, 255 ) : '',
			'lastname' => ( isset( $attendee['lastname'] ) ) ? substr( $attendee['lastname'] , 0, 255 ) : '',
			'email' => ( isset( $attendee['email'] ) ) ? substr( $attendee['email'] , 0, 255 ) : '',
		);
		$result = $wpdb->insert( $table_name, $data, array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' ) );
		$count_inserts = $count_inserts + $result;
	}

	/*
	// ccpa_write_log('function cc_attendees_reg_save');
	$reg_attendees = cc_attendees_reg_get_all();
	$reg_attendees[$ccreg] = array(
		'expiry' => strtotime('+1 day'),
		'attendees' => $attendees,
	);
	// ccpa_write_log($reg_attendees);
	update_option('registration_attendees', $reg_attendees);
	*/

	return $count_inserts;
}

// retrieve all registration attendees that have not expired
// NO LONGER USED!
function cc_attendees_reg_get_all(){
	// ccpa_write_log('function cc_attendees_reg_get_all');
	$reg_attendees = get_option('registration_attendees', array());
	// ccpa_write_log($reg_attendees);
	$unexpired = array();
	$now = time();
	foreach ($reg_attendees as $ccreg => $values) {
		if($values['expiry'] > $now){
			$unexpired[$ccreg] = $values;
		}
	}
	// ccpa_write_log($unexpired);
	return $unexpired;
}

// save attendees as the payment is recorded
// returns count of attendees added or false if no attendees found
function cc_attendees_save_from_reg($ccreg, $payment_id){
	$debug = array(
		'function' => 'cc_attendees_save_from_reg',
		'ccreg' => $ccreg,
		'payment_id' => $payment_id,
	);
	$attendees = cc_attendees_reg_get($ccreg);
	if($attendees){
		$debug['attendees'] = maybe_serialize($attendees);
		$count_attendees = 0;
		foreach ($attendees as $attendee) {
			if($attendee['registrant'] == 'r'){
				$user_id = $attendee['user_id'];
			}else{
				$user = cc_users_get_user($attendee['email']);
				if($user){
					$user_id = $user->ID;
					if($attendee['firstname'] <> $user->user_firstname || $attendee['lastname'] <> $user->user_lastname){
						$args = array(
							'ID' => $user_id,
				            'first_name' => $attendee['firstname'],
				            'last_name' => $attendee['lastname'],
						);
						wp_update_user($args);
					}
				}else{
					// we'll add the attendee to the user table
			        // user_login can only be 60 chars. Uniqid is 13 chars.
			        $user_login = substr( $attendee['firstname'].' '.$attendee['lastname'], 0, 46).' '.uniqid();
			        $args = array(
			            'user_login' => $user_login,
			            'user_pass' => wp_generate_password(),
			            'user_email' => $attendee['email'],
			            'first_name' => $attendee['firstname'],
			            'last_name' => $attendee['lastname'],
			        );
			        $user_id = wp_insert_user($args);
			        update_user_meta($user_id, 'source', 'attendee');
				}
			}
			$attendee_row = array(
				'payment_id' => $payment_id,
				'user_id' => $user_id,
				'registrant' => $attendee['registrant'],
			);
			$attendee_row_id = cc_attendee_add($attendee_row);
			$count_attendees ++;
		}
		cc_debug_log_anything($debug);
		return $count_attendees;
	}else{
		$debug['attendees'] = 'none found';
	}
	cc_debug_log_anything($debug);
	return false;
}

/**
 * Save attendees from standardized token data structure
 * Handles user creation/lookup and saves to cc_attendees table
 */
function cc_attendees_save_from_token($token, $payment_id){
    $debug = array(
        'function' => 'cc_attendees_save_from_token',
        'token' => $token,
        'payment_id' => $payment_id,
    );
    
    // Get the registration data from the token
    $registration_data = TempRegistration::get($token);
    if (!$registration_data) {
        $debug['error'] = 'No registration data found for token';
        cc_debug_log_anything($debug);
        return false;
    }
    
    $form_data = json_decode($registration_data->form_data, true);
    if (!$form_data || !isset($form_data['attendees'])) {
        $debug['error'] = 'No attendees data found in form_data';
        cc_debug_log_anything($debug);
        return false;
    }
    
    $attendees = $form_data['attendees'];
    
    // Validate structure - should be standardized format
    if (!is_array($attendees) || empty($attendees)) {
        $debug['error'] = 'Attendees data is not a valid array';
        cc_debug_log_anything($debug);
        return false;
    }

    // Get payment record to have access to registrant info
    $payment = cc_paymentdb_get_payment($payment_id);
    if (!$payment) {
        $debug['error'] = 'No payment record found';
        cc_debug_log_anything($debug);
        return false;
    }

    $debug['attendees_found'] = count($attendees);
    $count_attendees = 0;
    
    foreach ($attendees as $index => $attendee) {
        $debug["attendee_{$index}"] = array(
            'email' => $attendee['email'] ?? 'not set',
            'firstname' => $attendee['firstname'] ?? 'not set',
            'registrant' => $attendee['registrant'] ?? '',
        );
        
        /*
        // Validate required fields
        if (!isset($attendee['email']) || !isset($attendee['firstname']) || 
            empty($attendee['email']) || empty($attendee['firstname'])) {
            $debug["attendee_{$index}"]['status'] = 'skipped - missing required fields';
            continue;
        }
        */
        
        $user_id = null;

        // Handle registrant vs other attendees
        if (($attendee['registrant'] ?? '') == 'r') {
            // This is the registrant
            // For normal registration, we may only have registrant='r' without email/name
            // Get the user_id from the payment record
            if ($payment['reg_userid'] > 0) {
                $user_id = $payment['reg_userid'];
                $debug["attendee_{$index}"]['status'] = 'registrant - using payment reg_userid: ' . $user_id;
            } else {
                // Fallback: try to get current logged-in user
                $current_user_id = get_current_user_id();
                if ($current_user_id > 0) {
                    $user_id = $current_user_id;
                    $debug["attendee_{$index}"]['status'] = 'registrant - using current user: ' . $user_id;
                } else {
                    // Last resort: try to look up by email if available
                    if (!empty($attendee['email'])) {
                        $user = cc_users_get_user($attendee['email']);
                        if ($user) {
                            $user_id = $user->ID;
                            $debug["attendee_{$index}"]['status'] = 'registrant - found by email: ' . $user_id;
                        }
                    } elseif (!empty($payment['email'])) {
                        // Try payment email
                        $user = cc_users_get_user($payment['email']);
                        if ($user) {
                            $user_id = $user->ID;
                            $debug["attendee_{$index}"]['status'] = 'registrant - found by payment email: ' . $user_id;
                        }
                    }
                }
            }

            // If we still don't have a user_id, we can't proceed with this attendee
            if (!$user_id) {
                $debug["attendee_{$index}"]['status'] = 'skipped - could not determine registrant user_id';
                continue;
            }
            
        } else {
            // This is another attendee - look up or create user
            $user = cc_users_get_user($attendee['email']);
            
            if ($user) {
                // Existing user found
                $user_id = $user->ID;
                $debug["attendee_{$index}"]['status'] = 'existing user found: ' . $user_id;
                
                // Update user info if it has changed
                $needs_update = false;
                $update_args = array('ID' => $user_id);
                
                if ($attendee['firstname'] != $user->user_firstname) {
                    $update_args['first_name'] = $attendee['firstname'];
                    $needs_update = true;
                }
                
                if (($attendee['lastname'] ?? '') != $user->user_lastname) {
                    $update_args['last_name'] = $attendee['lastname'] ?? '';
                    $needs_update = true;
                }
                
                if ($needs_update) {
                    wp_update_user($update_args);
                    $debug["attendee_{$index}"]['user_updated'] = 'yes';
                }
                
            } else {
                // Create new user
                $user_login = substr(
                    $attendee['firstname'] . ' ' . ($attendee['lastname'] ?? ''), 
                    0, 46
                ) . ' ' . uniqid();
                
                $user_args = array(
                    'user_login' => $user_login,
                    'user_pass' => wp_generate_password(),
                    'user_email' => $attendee['email'],
                    'first_name' => $attendee['firstname'],
                    'last_name' => $attendee['lastname'] ?? '',
                );
                
                $user_id = wp_insert_user($user_args);
                
                if (is_wp_error($user_id)) {
                    $debug["attendee_{$index}"]['status'] = 'user creation failed: ' . $user_id->get_error_message();
                    continue;
                } else {
                    update_user_meta($user_id, 'source', 'attendee');
                    $debug["attendee_{$index}"]['status'] = 'new user created: ' . $user_id;
                }
            }
        }
        
        // Add attendee record to cc_attendees table
        $attendee_row = array(
            'payment_id' => $payment_id,
            'user_id' => $user_id,
            'registrant' => $attendee['registrant'] ?? '',
        );
        
        $attendee_row_id = cc_attendee_add($attendee_row);
        
        if ($attendee_row_id) {
            $count_attendees++;
            $debug["attendee_{$index}"]['cc_attendees_id'] = $attendee_row_id;
        } else {
            $debug["attendee_{$index}"]['cc_attendees_error'] = 'failed to add to cc_attendees table (possibly duplicate)';
        }
    }
    
    $debug['count_attendees_saved'] = $count_attendees;
    cc_debug_log_anything($debug);

    // Return count or false if none were saved
    return $count_attendees > 0 ? $count_attendees : false;
}

/**
 * Helper function to get or create a user for an attendee
 * Separated out for clarity and reusability
 */
function cc_get_or_create_attendee_user($attendee_data) {
    // Look for existing user first
    $user = cc_users_get_user($attendee_data['email']);
    
    if ($user) {
        // Update user info if needed
        $needs_update = false;
        $update_args = array('ID' => $user->ID);
        
        if (($attendee_data['firstname'] ?? '') != $user->user_firstname) {
            $update_args['first_name'] = $attendee_data['firstname'] ?? '';
            $needs_update = true;
        }
        
        if (($attendee_data['lastname'] ?? '') != $user->user_lastname) {
            $update_args['last_name'] = $attendee_data['lastname'] ?? '';
            $needs_update = true;
        }
        
        if ($needs_update) {
            wp_update_user($update_args);
        }
        
        return $user->ID;
    }
    
    // Create new user
    $user_login = substr(
        ($attendee_data['firstname'] ?? '') . ' ' . ($attendee_data['lastname'] ?? ''), 
        0, 46
    ) . ' ' . uniqid();
    
    $user_args = array(
        'user_login' => $user_login,
        'user_pass' => wp_generate_password(),
        'user_email' => $attendee_data['email'],
        'first_name' => $attendee_data['firstname'] ?? '',
        'last_name' => $attendee_data['lastname'] ?? '',
    );
    
    $user_id = wp_insert_user($user_args);
    
    if (!is_wp_error($user_id)) {
        update_user_meta($user_id, 'source', 'attendee');
        return $user_id;
    }
    
    return false;
}

// portal reg bypasses the normal ccreg based save
// and, anyway, portal users cannot have attendees!
function cc_attendees_save_org($payment_id, $user_id){
	$attendee_row = array(
		'payment_id' => $payment_id,
		'user_id' => $user_id,
		'registrant' => 'r',
	);
	$attendee_row_id = cc_attendee_add($attendee_row);
}

// switch an attendee from one user_id to another
// used for account merging
function cc_attendees_switch($payment_id, $from_user_id, $to_user_id){
	global $wpdb;
	$attendees_table = $wpdb->prefix.'cc_attendees';
	if($payment_id == 0 || $from_user_id == 0 || $to_user_id == 0) return false;
	// find the registrant
    $sql = "SELECT * FROM $attendees_table WHERE payment_id = $payment_id AND registrant = 'r' LIMIT 1";
    $registrant_row = $wpdb->get_row($sql, ARRAY_A);
	if($registrant_row === NULL) return false;
	if($registrant_row['user_id'] == $from_user_id){
		// change it to the to_user_id
		$data = array(
			'user_id' => $to_user_id,
		);
		$where = array(
			'id' => $registrant_row['id'],
		);
		$wpdb->update( $attendees_table, $data, $where);
		// if the to_user_id was already an attendee (non-registrant) then we need to delete it if it exists
    	$where = array(
    		'payment_id' => $payment_id,
    		'user_id' => $to_user_id,
    		'registrant' => '',
    	);
    	$wpdb->delete( $attendees_table, $where );
	}elseif($registrant_row['user_id'] == $to_user_id){
		// that can stay, so we need to delete the from_user_id if it's there
    	$where = array(
    		'payment_id' => $payment_id,
    		'user_id' => $from_user_id,
    		'registrant' => '',
    	);
    	$wpdb->delete( $attendees_table, $where );
	}else{
		// we'll update the attendee with the from_user_id to be the to_user_id
		$data = array(
			'user_id' => $to_user_id,
		);
		$where = array(
    		'payment_id' => $payment_id,
    		'user_id' => $from_user_id,
		);
		$wpdb->update( $attendees_table, $data, $where );
	}
	return true;
}

// get the registrant id for a payment
function cc_attendees_get_registrant_id($payment_id){
	global $wpdb;
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$wkshop_users_table = $wpdb->prefix.'wshops_users';
	if($payment_id == 0) return false;
	// first check the attendees table
    $sql = "SELECT user_id FROM $attendees_table WHERE payment_id = $payment_id AND registrant = 'r' LIMIT 1";
    $registrant_id = $wpdb->get_var($sql);
    if($registrant_id !== NULL && $registrant_id > 0) return $registrant_id;
    // ok fallback to checking the payment record
    $payment = cc_paymentdb_get_payment($payment_id);
    if($payment !== NULL){
    	if($payment['reg_userid'] > 0) return $payment['reg_userid'];
    	if($payment['email'] <> ''){
    		$user = cc_users_get_user($payment['email']);
    		if($user) return $user->ID;
    	}
	    // bummer!
    	if($payment['type'] == ''){
    		// we'll try the workshops user table
    		$sql = "SELECT user_id FROM $wkshop_users_table WHERE payment_id = $payment_id AND reg_attend = 'r'";
		    $registrant_id = $wpdb->get_var($sql);
		    if($registrant_id !== NULL && $registrant_id > 0) return $registrant_id;
    	}
    }
    // out of options now
    return false;
}

// get all attendees for a training
// always returns an array
function cc_attendees_all_for_training( $training_id ){
    global $wpdb;
    $table_payments = $wpdb->prefix . 'ccpa_payments';
    $table_attendees = $wpdb->prefix . 'cc_attendees';
    $sql = "SELECT DISTINCT a.user_id
        FROM $table_attendees a
        JOIN $table_payments p ON a.payment_id = p.id
        WHERE p.workshop_id = $training_id OR p.upsell_workshop_id = $training_id";
    return $wpdb->get_col($sql);
}

function is_user_attendee_for_workshop($user_id, $training_id) {
    global $wpdb;
    $user_id = absint($user_id);
    $training_id = absint($training_id);
    if (!$user_id || !$training_id) {
        return false;
    }
    $query = $wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$wpdb->prefix}wshops_users wu
         INNER JOIN {$wpdb->prefix}cc_attendees ca ON wu.payment_id = ca.payment_id
         WHERE wu.user_id = %d 
         AND wu.workshop_id = %d
         AND ca.user_id = %d
         LIMIT 1",
        $user_id,
        $training_id,
        $user_id
    );
    $result = $wpdb->get_var($query);
    return (bool) $result;
}
