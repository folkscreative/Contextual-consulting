<?php
/**
 * ACT Value Cards
 */

// the table for orders
// add or update the log table
add_action('init', 'cc_avc_orders_table_update');
function cc_avc_orders_table_update(){
	global $wpdb;
	// v1
	$cc_avc_orders_db_ver = 1;
	$installed_table_ver = get_option('cc_avc_orders_db_ver');
	if($installed_table_ver <> $cc_avc_orders_db_ver){
		$avc_orders_table = $wpdb->prefix.'avc_orders';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $avc_orders_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			status varchar(15) NOT NULL,
			last_update timestamp DEFAULT CURRENT_TIMESTAMP,
			ip_address varchar(45) NOT NULL,
			user_id mediumint(9) NOT NULL,
			currency varchar(3) NOT NULL,
			pack_price decimal(9,2) NOT NULL,
			packs smallint(4) NOT NULL,
			pack_total decimal(9,2) NOT NULL,
			pnp decimal(9,2) NOT NULL,
			vat decimal(9,2) NOT NULL,
			total decimal(9,2) NOT NULL,
			firstname varchar(50) NOT NULL,
			lastname varchar(50) NOT NULL,
			email varchar(255) NOT NULL,
			phone varchar(255) NOT NULL,
			ship_name varchar(255) NOT NULL,
			ship_addr_1 varchar(255) NOT NULL,
			ship_addr_2 varchar(255) NOT NULL,
			ship_town varchar(255) NOT NULL,
			ship_county varchar(255) NOT NULL,
			ship_postcode varchar(255) NOT NULL,
			ship_country varchar(255) NOT NULL,
			bill_name varchar(255) NOT NULL,
			bill_addr_1 varchar(255) NOT NULL,
			bill_addr_2 varchar(255) NOT NULL,
			bill_town varchar(255) NOT NULL,
			bill_county varchar(255) NOT NULL,
			bill_postcode varchar(255) NOT NULL,
			bill_country varchar(255) NOT NULL,
			pay_method varchar(15) NOT NULL,
			client_secret varchar(255) NOT NULL,
			payment_intent_id varchar(255) NOT NULL,
			stripe_fee decimal(7,2) NOT NULL,
			inv_address text NOT NULL,
			inv_email varchar(255) NOT NULL,
			inv_phone varchar(255) NOT NULL,
			inv_ref varchar(255) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_avc_orders_db_ver', $cc_avc_orders_db_ver);
	}
}

// empty order
function cc_avc_empty_order(){
	return array(
		'id' => 0,
		'status' => 'started',
		'last_update' => '',
		'ip_address' => '',
		'user_id' => 0,
		'currency' => '',
		'pack_price' => 0,
		'packs' => 1,
		'pack_total' => 0,
		'pnp' => 0,
		'vat' => 0,
		'total' => 0,
		'firstname' => '',
		'lastname' => '',
		'email' => '',
		'phone' => '',
		'ship_name' => '',
		'ship_addr_1' => '',
		'ship_addr_2' => '',
		'ship_town' => '',
		'ship_county' => '',
		'ship_postcode' => '',
		'ship_country' => '',
		'bill_name' => '',
		'bill_addr_1' => '',
		'bill_addr_2' => '',
		'bill_town' => '',
		'bill_county' => '',
		'bill_postcode' => '',
		'bill_country' => '',
		'pay_method' => 'online',
		'client_secret' => '',
		'payment_intent_id' => '',
		'stripe_fee' => 0,
		'inv_address' => '',
		'inv_email' => '',
		'inv_phone' => '',
		'inv_ref' => '',
	);
}

// gets an order by ...
// returns an array or NULL if not found
function cc_avc_order_get_by( $by, $what ){
	global $wpdb;
	$avc_orders_table = $wpdb->prefix.'avc_orders';
	$sql = "SELECT * FROM $avc_orders_table WHERE $by = '$what' LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// adds or updates an order
// return false on failure or the ID on success
function cc_avc_order_update( $order ){
	global $wpdb;
	$avc_orders_table = $wpdb->prefix.'avc_orders';
	if( ! is_array( $order ) ) return false;

	if($order['stripe_fee'] == 0 && $order['payment_intent_id'] <> ''){
		$stripe_fee = cc_stripe_fee_avc_order( $order['payment_intent_id'] );
		if( $stripe_fee <> '?' && $stripe_fee > 0 ){
			$order['stripe_fee'] = $stripe_fee;
		}
	}
	if( ! isset( $order['ip_address']) || $order['ip_address'] == '' ){
		if( isset( $_SERVER['REMOTE_ADDR'] ) ){
			$order['ip_address'] = $_SERVER['REMOTE_ADDR'];
		}else{
			$order['ip_address'] = '';
		}
	}
	$order['last_update'] = date( 'Y-m-d H:i:s' );

	if( isset($order['id'] ) && $order['id'] > 0 ){
		// update needed
		$where = array(
			'id' => $order['id'],
		);
		$result = $wpdb->update($avc_orders_table, $order, $where);
		if( $result ){
			return $order['id'];
		}else{
			return false;
		}
	}else{
		// insert
		$result = $wpdb->insert($avc_orders_table, $order);
		if( $result ){
			return $wpdb->insert_id;
		}else{
			return false;
		}
	}
}

// the currency switcher html for the top of the first panel
function cc_avc_currency_switcher( $currency ){
	global $rpm_theme_options;
	$html = '<div class="avc-currency-switchers">Show price in ';
	$divider = ' ';
	foreach ( cc_valid_currencies() as $offered_currency) {
		if( $currency == $offered_currency ){
			$span_class = 'currency-in-use';
		}else{
			$span_class = '';
		}
		$html .= '<span id="avc-currency-switch-wrap-'.$offered_currency.'" class="avc-currency-switch-wrap '.$span_class.'"">'.$divider.'<a href="javascript:void(0);" id="avc-currency-switch-'.$offered_currency.'" class="avc-currency-switch" data-currency="'.$offered_currency.'" data-locale="'.cc_currency_locale( $offered_currency ).'" data-price="'.$rpm_theme_options['avc_price_'.$offered_currency].'">'.$offered_currency.'</a></span>';
		$divider = ', ';
	}
	$html .= '</div>';
	return $html;
}

// get the pack price
function cc_avc_pack_price( $currency ){
	global $rpm_theme_options;
	return $rpm_theme_options['avc_price_'.$currency];
}

// AVC Order form submit (next btn clicked)
add_action( 'wp_ajax_avc_order_form_submit', 'cc_avc_order_form_submit' );
add_action( 'wp_ajax_nopriv_avc_order_form_submit', 'cc_avc_order_form_submit' );
function cc_avc_order_form_submit(){
	$response = array(
		'status' => 'error',
		'panel' => '',
		'msg' => '',
	);

	$order = cc_avc_populate_order();

	// the submitted panel ...
	$panel = 1;
	if( isset( $_POST['panel'] ) ){
		$panel = (int) $_POST['panel'];
		if( $panel == 0 ){
			$panel = 1;
		}
	}
	// the submitted btn ....
	$direction = 'next';
	if( isset( $_POST['avc_action'] ) && $_POST['avc_action'] == 'back' ){
		$direction = 'back';
	}

	if( $direction == 'next' ){
		$panel ++;
	}else{
		$panel --;
	}

	if( $panel == 4 ){
		$order = cc_avc_set_default_bill_addr( $order );
	}

	if( $panel == 5 ){
		$order = cc_avc_add_vat_pnp( $order );
	}

	if( $panel == 6 ){
		$order = cc_avc_setup_stripe( $order );
	}

	if( $panel == 7 ){
		$order = cc_avc_finalise_order( $order );
		$response['msg'] = 'Thanks for your order. A confirmation email is on its way to you.';
	}else{
		$response['panel'] = cc_avcp_panel_content( $panel, $order );
	}

	$response['status'] = 'ok';

    echo json_encode($response);
    die();
}

// populate order from $_POST values and save it
// returns the saved order
function cc_avc_populate_order(){
	global $rpm_theme_options;
	$order_id = 0;
	if( isset( $_POST['order_id'] ) ){
		$order_id = (int) $_POST['order_id'];
	}
	if( $order_id == 0 ){
		$order = cc_avc_empty_order();
	}else{
		$order = cc_avc_order_get_by( 'id', $order_id );
		if( $order === NULL ){
			$order = cc_avc_empty_order();
		}
	}

	if( isset( $_POST['user_id'] ) ){
		$new_value = (int) $_POST['user_id'];
		if( $order['user_id'] == 0 && $new_value > 0 ){
			$order['user_id'] = $new_value;
			$order = cc_avc_default_user_values( $order );
		}
	}

	$integers = array( 'packs' );
	foreach ($integers as $field_name) {
		if( isset( $_POST[$field_name] ) ){
			$new_value = (int) $_POST[$field_name];
			if( $new_value > 0 ){
				$order[$field_name] = $new_value;
			}
		}
	}

	$financials = array( 'pack_price' );
	foreach ($financials as $field_name) {
		if( isset( $_POST[$field_name] ) ){
			$new_value = (float) $_POST[$field_name];
			if( $new_value > 0 ){
				$order[$field_name] = $new_value;
			}
		}
	}

	$texts = array( 'firstname', 'lastname', 'phone', 'ship_name', 'ship_addr_1', 'ship_addr_2', 'ship_town', 'ship_county', 'ship_postcode', 'ship_country', 'bill_name', 'bill_addr_1', 'bill_addr_2', 'bill_town', 'bill_county', 'bill_postcode', 'bill_country', 'inv_address', 'inv_phone', 'inv_ref' );
	foreach ($texts as $field_name) {
		if( isset( $_POST[$field_name] ) ){
			$new_value = stripslashes( sanitize_text_field( $_POST[$field_name] ) );
			$order[$field_name] = $new_value;
		}
	}

	$emails = array( 'email', 'inv_email' );
	foreach ($emails as $field_name) {
		if( isset( $_POST[$field_name] ) ){
			$new_value = stripslashes( sanitize_email( $_POST[$field_name] ) );
			$order[$field_name] = $new_value;
		}
	}

	if( isset( $_POST['currency'] ) ){
		if( in_array( $_POST['currency'], cc_valid_currencies() ) ){
			$order['currency'] = $_POST['currency'];
			$order['pack_price'] = $rpm_theme_options['avc_price_'.$order['currency']];
			$order['pack_total'] = $order['pack_price'] * $order['packs'];
		}
	}

	if( isset( $_POST['pay_method'] ) ){
		if( in_array( $_POST['pay_method'], array( 'online', 'invoice' ) ) ){
			$order['pay_method'] = $_POST['pay_method'];
		}
	}

	$order_id = cc_avc_order_update( $order );
	$order['id'] = $order_id;

	return $order;
}

// fill the order with default values for this user
function cc_avc_default_user_values( $order ){
	$user = get_user_by( 'id', $order['user_id'] );
	if( $user ){
		$details = cc_users_user_details( $user );
		$order['firstname'] = $details['firstname'];
		$order['lastname'] = $details['lastname'];
		$order['email'] = $details['email'];
		$order['phone'] = $details['phone'];
		$order['ship_name'] = $order['bill_name'] = $details['firstname'].' '.$details['lastname'];
		$order['ship_addr_1'] = $order['bill_addr_1'] = $details['address_line_1'];
		$order['ship_addr_2'] = $order['bill_addr_2'] = $details['address_line_2'];
		$order['ship_town'] = $order['bill_town'] = $details['address_town'];
		$order['ship_county'] = $order['bill_county'] = $details['address_county'];
		$order['ship_postcode'] = $order['bill_postcode'] = $details['address_postcode'];
		$order['ship_country'] = $order['bill_country'] = $details['address_country'];
	}
	return $order;
}

// if blank, set the billing address to be the same as the shipping address
function cc_avc_set_default_bill_addr( $order ){
	if( $order['bill_name'] == '' && $order['bill_addr_1'] == '' && $order['bill_addr_2'] == '' && $order['bill_town'] == '' && $order['bill_county'] == '' && $order['bill_postcode'] == '' && $order['bill_country'] == '' ){
		$order['bill_name'] = $order['ship_name'];
		$order['bill_addr_1'] = $order['ship_addr_1'];
		$order['bill_addr_2'] = $order['ship_addr_2'];
		$order['bill_town'] = $order['ship_town'];
		$order['bill_county'] = $order['ship_county'];
		$order['bill_postcode'] = $order['ship_postcode'];
		$order['bill_country'] = $order['ship_country'];
		$order['bill_name'] = $order['ship_name'];
		cc_avc_order_update( $order );
	}
	return $order;
}

// add VAT and PnP to an order
function cc_avc_add_vat_pnp( $order ){
	global $rpm_theme_options;
	if( $order['ship_country'] == 'GB' ){
		if( $order['packs'] == 1 ){
			$pnp = $rpm_theme_options['avc_ship_gb_one'];
		}else{
			$pnp = $rpm_theme_options['avc_ship_gb_more'];
		}
	}else{
		$extras = $order['packs'] - 1;
		$pnp = round( $rpm_theme_options['avc_ship_world_one'] + $extras * $rpm_theme_options['avc_ship_world_extra'], 2 );
	}
	$order['pnp'] = cc_voucher_core_curr_convert( 'GBP', $order['currency'], $pnp);

	if( $order['ship_country'] == 'GB' ){
		$order['vat'] = ccpa_vat_amount( $order['pack_total'] + $order['pnp'] );
	}else{
		$order['vat'] = 0; // set it to zero in case they have changed country while purchasing!
	}

	$order['total'] = $order['pack_total'] + $order['pnp'] + $order['vat'];

	cc_avc_order_update( $order );
	return $order;
}

// returns an address
// $address should be 'ship' or 'bill'
function cc_avc_format_address( $order, $address, $separator=',<br>'){
    $fields = array('addr_1', 'addr_2', 'town', 'county', 'postcode', 'country');
    $result = '';
    foreach ($fields as $field) {
    	$value = $order[$address.'_'.$field];
        if( $field == 'country' && $value <> '' ){
            $value = ccpa_countries_name( $value );
        }

        if( $value <> '' ){
        	if( $result <> '' ){
        		$result .= $separator;
        	}
        	$result .= $value;
        }
    }
    return $result;
}

// set up Stripe payment intent
function cc_avc_setup_stripe( $order ){
    $charge_amount = round( ($order['total'] * 100 ) , 0);
    $args = array(
        'amount' => $charge_amount,
        'currency' => strtolower( $order['currency'] ),
        'metadata' => array(
            'ACT Value Cards' => $order['id'],
        ),
    );
    $intent = cc_stripe_pmt_intent_create_pi($args);
    // and save info about it
    $order['client_secret'] = $intent->client_secret;
    $order['payment_intent_id'] = $intent->id;
	cc_avc_order_update( $order );
	return $order;
}

// payment (or invoice request) complete
// finalise the order - including triggering appropriate emails
function cc_avc_finalise_order( $order ){
	$order['status'] = 'complete';
	cc_avc_send_conf_email( $order );
	cc_avc_send_thanks_email( $order );
	cc_avc_order_update( $order );
	return $order;
}

// send the confirmation email to Joe
function cc_avc_send_conf_email( $order ){
	global $rpm_theme_options;
	$subject = 'ACT Values Cards Order';
	$message = '<p>New ACT Values Cards Order:</p>';
	$fields = array(
		'id' => 'Order Num.',
		'last_update' => 'Datetime',
		'currency' => 'Currency',
		'pack_price' => 'Pack price:',
		'packs' => 'Packs',
		'pack_total' => 'Pack total',
		'pnp' => 'PnP',
		'vat' => 'VAT',
		'total' => 'Total',
		'firstname' => 'First name',
		'lastname' => 'Last name',
		'email' => 'Email',
		'phone' => 'Phone',
		'ship_name' => 'Ship name',
		'ship_addr_1' => 'Ship address 1',
		'ship_addr_2' => 'Ship address 2',
		'ship_town' => 'Ship town',
		'ship_county' => 'Ship county',
		'ship_postcode' => 'Ship postcode',
		'ship_country' => 'Ship country',
		'bill_name' => 'bill name',
		'bill_addr_1' => 'bill address 1',
		'bill_addr_2' => 'bill address 2',
		'bill_town' => 'bill town',
		'bill_county' => 'bill county',
		'bill_postcode' => 'bill postcode',
		'bill_country' => 'bill country',
		'pay_method' => 'Payment Method',
		'inv_address' => 'Invoice address',
		'inv_email' => 'Invoice email',
		'inv_phone' => 'Invoice phone',
		'inv_ref' => 'Invoice ref.',
	);
	$message .= '<table>';
	foreach ($fields as $key => $label) {
		$message .= '<tr><th>'.$label.'</th><td>';
		switch ($key) {
			case 'last_update':
				$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $order[$key] );
				$message .= $date->format( 'd/m/Y H:i:s' );
				break;
			case 'pack_price':
			case 'pack_total':
			case 'pnp':
			case 'vat':
			case 'total':
				$message .= cc_money_format($order[$key], $order['currency']);
				break;
			case 'ship_country':
			case 'bill_country':
				$message .= ccpa_countries_name( $order[$key] );
				break;
			default:
				$message .= $order[$key];
				break;
		}
	}
	$message .= '</table>';
	if(site_url('', 'https') == 'https://contextualconsulting.co.uk'){
		$to = 'admin@contextualconsulting.co.uk';
	}else{
		$to = get_bloginfo('admin_email');
	}
	$headers = array(
		'From: '.$rpm_theme_options['email-from-name'].' <'.$rpm_theme_options['email-from'].'>',
		'Content-Type: text/html; charset=UTF-8',
	);
	return wp_mail($to, $subject, $message, $headers);
}

// send the thanks email to the customer
function cc_avc_send_thanks_email( $order ){
	$subscriber_id = cc_mailster_get_subscriber_id( $order['email'], $order['firstname'], $order['lastname'] );
	if( $order['pay_method'] == 'online' ){
		$thanks_msg = 'Thank you for your payment.';
	}else{
		$thanks_msg = 'Thank you for your order. We will invoice you as follows:<br><table>';
		if( $order['inv_address'] <> '' ){
			$thanks_msg .= '<tr><th>Address:</th><td>'.$order['inv_address'].'</td></tr>';
		}
		if( $order['inv_email'] <> '' ){
			$thanks_msg .= '<tr><th>Email:</th><td>'.$order['inv_email'].'</td></tr>';
		}
		if( $order['inv_phone'] <> '' ){
			$thanks_msg .= '<tr><th>Phone:</th><td>'.$order['inv_phone'].'</td></tr>';
		}
		if( $order['inv_ref'] <> '' ){
			$thanks_msg .= '<tr><th>Reference:</th><td>'.$order['inv_ref'].'</td></tr>';
		}
		$thanks_msg .= '</table>';
	}
	$mailster_tags = array(
		'firstname' => $order["firstname"],
		'order_details' => cc_avc_order_details( $order ),
		'thanks_msg' => $thanks_msg,
	);
	sysctrl_mailster_ar_hook( 'avc_thanks_email', $subscriber_id, $mailster_tags);
}

// details table used for emails
function cc_avc_order_details( $order ){
	$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $order['last_update'] );
	$details = '<p>Order number '.$order['id'].' dated '.$date->format( 'jS M Y' ).'</p>';

	$details .= '<table style="width:100%;"><tr><th>&nbsp;</th><th style="text-align:right;">Price</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Total</th></tr>';
	$details .= '<tr><td>ACT Values Cards</td><td style="text-align:right;">'.cc_money_format($order['pack_price'], $order['currency']).'</td><td style="text-align:right;">'.$order['packs'].'</td><td style="text-align:right;">'.cc_money_format($order['pack_total'], $order['currency']).'</td></tr>';	
	$details .= '<tr><td>Postage &amp; Packing</td><td style="text-align:right;">&nbsp;</td><td style="text-align:right;">&nbsp;</td><td style="text-align:right;">'.cc_money_format($order['pnp'], $order['currency']).'</td></tr>';
	if( $order['vat'] > 0 ){
		$details .= '<tr><td>VAT</td><td style="text-align:right;">&nbsp;</td><td style="text-align:right;">&nbsp;</td><td style="text-align:right;">'.cc_money_format($order['vat'], $order['currency']).'</td></tr>';
	}
	$details .= '<tr><td>Total</td><td style="text-align:right;">&nbsp;</td><td style="text-align:right;">&nbsp;</td><td style="text-align:right;">'.cc_money_format($order['total'], $order['currency']).'</td></tr>';
	$details .= '</table><br>';

	$details .= '<table style="width:100%;"><tr><th style="text-align:left;">Shipping address:</th><th style="text-align:left;">Billing address:</th></tr>';
	$details .= '<tr style="width:100%;"><td>'.cc_avc_order_address( $order, 'ship' ).'</td><td>'.cc_avc_order_address( $order, 'bill' ).'</td></tr>';
	$details .= '</table>';
	return $details;
}

// returns a tidy order address
// address MUST BE ship or bill
function cc_avc_order_address( $order, $address='ship' ){
	$fields = array( 'name', 'addr_1', 'addr_2', 'town', 'county', 'postcode', 'country' );
	$tidy_addr = '';
	foreach ($fields as $field) {
		$field_name = $address.'_'.$field;
		if( $order[$field_name] <> '' ){
			if( $tidy_addr <> '' ){
				$tidy_addr .= '<br>';
			}
			if( $field == 'country' ){
				$tidy_addr .= ccpa_countries_name( $order[$field_name] );
			}else{
				$tidy_addr .= $order[$field_name];
			}
		}
	}
	return $tidy_addr;
}

// get all completed orders for the orders page
function cc_avc_complated_orders(){
	global $wpdb;
	$avc_orders_table = $wpdb->prefix.'avc_orders';
	$sql = "SELECT * FROM $avc_orders_table WHERE status = 'complete' ORDER BY last_update DESC";
	return $wpdb->get_results( $sql, ARRAY_A );
}

// resend the AVC confirmation email
add_shortcode( 'resend_avc_thanks_email', 'resend_avc_thanks_email' );
function resend_avc_thanks_email( $atts ){
    $atts = shortcode_atts( array(
            "order" => 0,
    ), $atts );
    $html = 'Resend AVC thanks email failed';
    if( $atts['order'] > 0 ){
    	$order = cc_avc_order_get_by( 'id', $atts['order'] );
    	if( $order !== null ){
    		cc_avc_send_thanks_email( $order );
		    $html = 'Resend AVC thanks email succeeded';
    	}
    }
    return $html;
}
