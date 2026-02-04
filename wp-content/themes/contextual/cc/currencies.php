<?php
/**
 * Currencies
 */

function cc_valid_currencies(){
	return array('GBP', 'AUD', 'USD', 'EUR');
}

// returns a pretty currency
function ccpa_pretty_currency($currency){
	switch ($currency) {
		case 'GBP':			return 'GB Pounds';				break;
		case 'AUD':			return 'Australian Dollars';	break;
		case 'USD':			return 'US Dollars';			break;
		case 'EUR':			return 'Euros';					break;
		default:			return false;					break;
	}
}

// returns a locale
function cc_currency_locale( $currency ){
	switch ($currency) {
		case 'GBP':			return 'en-GB';			break;
		case 'AUD':			return 'en-AU';			break;
		case 'USD':			return 'en-US';			break;
		case 'EUR':			return 'en-IE';			break;
		default:			return false;			break;
	}
}


/**
 * Get currency symbol with fallback
 */
function contextual_get_currency_symbol($currency) {
    $symbols = [
        'GBP' => '£',
        'USD' => '$',
        'EUR' => '€',
        'AUD' => 'A$'
    ];
    
    return isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
}

// The eurozone consists of the following 19 countries in the EU: Austria, Belgium, Cyprus, Estonia, Finland, France, Germany, Greece, Ireland, Italy, Latvia, Lithuania, Luxembourg, Malta, Netherlands, Portugal, Slovakia, Slovenia, and Spain.
function cc_eurozone_countries(){
	return array('AT','BE','CY','EE','FI','FR','DE','GR','IE','IT','LV','LT','LU','MT','NL','PT','SK','SI','ES');
}

// get's a user's currency
// uses the user's chosen currency if set
function cc_currency_get_user_currency($user_id=0){
	if($user_id == 0){
		$user_id = get_current_user_id(); // 0 if no user logged in
	}
	if($user_id > 0){
		// try the option
		$currency = get_user_meta($user_id, 'currency', true);
		if(in_array($currency, cc_valid_currencies())){
			return $currency;
		}
	}
	// try the currency cookie
	if(isset($_COOKIE['ccurrency'])){
		$currency = $_COOKIE['ccurrency'];
		if(in_array($currency, cc_valid_currencies())){
			return $currency;
		}
	}
	// ok now try using the IP address
	$user_country = cc_currencies_get_ip_country();
	if($user_country !== NULL){
		return cc_currencies_get_country_currency($user_country);
	}
	// no luck, we'll use pounds
	return 'GBP';
}



// checks and sets the currency cookie
// triggered at init
$currency = '';
function check_set_currency_cookie(){
	global $currency;
	if(isset($_POST['currency-switch'])){
		if(in_array($_POST['currency-switch'], cc_valid_currencies())){
			$currency = $_POST['currency-switch'];
			cc_currencies_set_cookie($currency);
		}
	}else{
		if(isset($_COOKIE['ccurrency'])){
			$currency = $_COOKIE['ccurrency'];
		}else{
			$currency = 'GBP';
			$user_country = cc_currencies_get_ip_country();
			if($user_country !== NULL){
				$currency = cc_currencies_get_country_currency($user_country);
			}
			cc_currencies_set_cookie($currency);
		}
	}
}

// get a country from the IP address
// will return something like 'UK', 'AU', 'NZ', 'US', etc. ... or NULL
// can be overridden for testing
function cc_currencies_get_ip_country(){
	global $rpm_theme_options;
	$user_country = NULL;
	if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] <> '' && $_SERVER['REMOTE_ADDR'] == $rpm_theme_options['sys-override-ip'] ){
		if($rpm_theme_options['sys-override-locn'] <> ''){
			$user_country = $rpm_theme_options['sys-override-locn'];
			wms_write_log('Country override: Using '.$user_country.' as location for IP:'.$_SERVER['REMOTE_ADDR']);
			return $user_country;
		}
	}
	return ccpa_ip_lookup();
}

// gets a currency from the country code
function cc_currencies_get_country_currency($country_code){
	if($country_code == 'AU' || $country_code == 'NZ'){
		return 'AUD';
	}elseif($country_code == 'US' || $country_code == 'CA'){
		return 'USD';
	}elseif(in_array($country_code, cc_eurozone_countries())){
		return 'EUR';
	}
	return 'GBP';
}

// returns an icon for the currency
function cc_currencies_icon($currency){
	switch ($currency) {
		case 'GBP':			return '<i class="fa-solid fa-sterling-sign fa-fw"></i>';			break;
		// case 'GBP':			return '<span class="fa-stack"><i class="fa-solid fa-circle fa-stack-2x"></i><i class="fab fa-sterling fa-stack-1x fa-inverse"></i></span>';			break;
		case 'AUD':
		case 'USD':			return '<i class="fa-solid fa-dollar-sign fa-fw"></i>';				break;
		case 'EUR':			return '<i class="fa-solid fa-euro-sign fa-fw"></i>';				break;
	}
}

// lookup of IP address
// uses the http://geolocation-db.com/ service
// returns an object of data or NULL
/* replaced by code in the ip-geolocation.php file
function ccpa_ip_lookup($ip=''){
	if($ip == ''){
		if(isset($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
	}
	// 77.72.2.12 = Krystal Gibson server (live)
	// 185.199.220.37 = Krystal Trinity server (dev)
	if($ip <> '' && $ip <> '77.72.2.12' && $ip <> '185.199.220.37'){
		/**
		 * geolocation-db.com has been down since July 2024
		 */
		// see http://geolocation-db.com/documentation
		// $api_key = '0f761a30-fe14-11e9-b59f-e53803842572';
		// now limited to only attempting the connection for one second!
		/*
		$context = stream_context_create(array(
			'http' => array( // yes, must be http even though we are using https
				'timeout' => 1, // one second
			),
		));
		/*
		$json = file_get_contents('https://geolocation-db.com/json/'.$api_key.'/'.$ip, false, $context);
		if($json){
			$data = json_decode($json);
			if($data !== NULL){
				return  $data->country_code;
			}
		}
		*/
		// timeout or other unpleasant happenning
		// try ip2c.org ... again we will only give it one second
		/*
		$s = file_get_contents('http://ip2c.org/'.$ip, false, $context);
		switch($s[0]){
			case '0':
				// echo 'Something wrong';
				break;
			case '1':
				$reply = explode(';',$s);
				// echo '<br>Two-letter: '.$reply[1];
				// echo '<br>Three-letter: '.$reply[2];
				// echo '<br>Full name: '.$reply[3];
				return $reply[1];
				break;
			case '2':
				// echo 'Not found in database';
				break;
		}
		// if we're still here, something went wrong
		// let's try ipgeolocation.io
		// documentation here: https://ipgeolocation.io/documentation/ip-geolocation-api.html
		// we only want the country_code2 field
		$api_key = 'b9b918a5b1a048688e784ad7a0ea0ef4';
		$json = file_get_contents( 'https://api.ipgeolocation.io/ipgeo?apiKey='.$api_key.'&ip='.$ip.'&fields=country_code2', false, $context );
		if( $json ){
			$data = json_decode( $json );
			if( $data !== NULL ){
				return $data->country_code2;
			}
		}

		cc_debug_log_anything('ccpa_ip_lookup failed. IP:'.$ip.' IP2C response:'.$s[0]);
	}
	return NULL;
}
*/

// sets a cookie
function cc_currencies_set_cookie($currency){
	if (version_compare(phpversion(), '7.3.0', '<')){
		// setcookie(name, value, expire, path, domain, secure, httponly);
		setcookie('ccurrency', $currency, time()+86400*30, '/');
	}else{
		$cookie_args = array(
			'expires' => strtotime('+30 days'),
			'path' => '/',
			'samesite' => 'Strict',
		);
		setcookie('ccurrency', $currency, $cookie_args);
	}
}

// the change currency modal html, added in to the footer
add_action( 'wp_footer', 'cc_currencies_modal_html' );
function cc_currencies_modal_html(){
	?>
	<div id="chg-curr-modal" class="modal currency-modal cc-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Change Currency</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<label for="chg-curr" class="form-label">Select desired currency</label>
					<select id="chg-curr" class="form-select">
						<?php foreach (cc_valid_currencies() as $curr) { ?>
							<option value="<?php echo $curr; ?>"><?php echo ccpa_pretty_currency($curr); ?></option>
						<?php } ?>
					</select>
					<p class="small mt-3">Note: Prices will be shown in GB Pounds if training is not available in your selected currency.</p>
					<div id="chg-curr-msg"></div>
				</div>
				<div class="modal-footer">
					<div class="row align-items-end">
						<div class="col text-start">
							<a href="javascript:void(0)" class="" data-bs-dismiss="modal">Cancel</a>
						</div>
						<div class="col text-end">
							<a href="javascript:void(0)" id="chg-curr-save" class="btn btn-primary" data-type="" data-id="">Save</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

// change currency
add_action('wp_ajax_change_currency', 'cc_currencies_change_currency');
add_action('wp_ajax_nopriv_change_currency', 'cc_currencies_change_currency');
function cc_currencies_change_currency(){
	$response = array(
		'status' => 'error',
		'msg' => 'Something went wrong. <i class="fa-solid fa-face-frown"></i> Unable to change currency.',
		'price' => '',
		'raw_price' => 0,
		'non_early' => '',
		'earlybird' => 'n',
		'student_price' => 0,
		'student_price_formatted' => '',
		'gift_voucher_value' => '',
		'currency' => '',
		'icon' => '',
		'event' => array(),
	);
	$train_type = '';
	if(isset($_POST['trainType']) && $_POST['trainType'] == 'w' || $_POST['trainType'] == 'r'){
		$train_type = $_POST['trainType'];
	}
	$train_id = 0;
	if(isset($_POST['trainID']) ){
		$train_id = (int) $_POST['trainID'];
	}
	$currency = '';
	if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies()) ){
		$currency = $_POST['currency'];
		$user_id = get_current_user_id(); // 0 if no user logged in
		if($user_id > 0){
			update_user_meta($user_id, 'currency', $currency);
		}
	}
	if($train_type <> '' && $train_id > 0 && $currency <> ''){
		if($train_type == 'w'){
			$workshop_pricing = cc_workshop_price($train_id, $currency);
			$response['price'] = $workshop_pricing['price_text'];
			$response['raw_price'] = $workshop_pricing['raw_price'];
			$response['student_price'] = $workshop_pricing['student_price'];
			$response['student_price_formatted'] = $workshop_pricing['student_price_formatted'];
			$response['non_early'] = $workshop_pricing['non_early_price'];
			if($workshop_pricing['earlybird_msg'] <> ''){
				$response['earlybird'] = 'y';
			}
			$response['gift_voucher_value'] = cc_voucher_offer_value($workshop_pricing['curr_found']);
			$response['currency'] = $workshop_pricing['curr_found'];
			$response['icon'] = cc_currencies_icon($workshop_pricing['curr_found']);
			$response['status'] = 'ok';
			$response['msg'] = '';
			for ($i = 1; $i<16; $i++) {
				$event_name = get_post_meta( $train_id, 'event_'.$i.'_name', true );
				if($event_name == ''){
					$response['event'][$i] = '';
				}else{
					$event_free = get_post_meta( $train_id, 'event_'.$i.'_free', true );
					if($event_free == 'yes'){
						$response['event'][$i] = 'FREE';
					}else{
						$response['event'][$i] = workshops_event_price($train_id, $i, $currency);
						/* taken out cos missing currencies are too complicated! :-(
						$event_price = workshops_event_price($train_id, $i, $currency);
						if($event_price > 0){
							$response['event'][$i] = workshops_pretty_price($event_price, $currency);
						}else{
							$event_price = workshops_event_price($train_id, $i, 'GBP');
							$response['event'][$i] = workshops_pretty_price($event_price, 'GBP');
						}
						*/
					}
				}
			}
		}else{
			$recording_pricing = cc_recording_price($train_id, $currency);
			$response['raw_price'] = $recording_pricing['raw_price'];
			$response['currency'] = $recording_pricing['curr_found'];
			$response['price'] = workshops_pretty_price($recording_pricing['raw_price'], $recording_pricing['curr_found']);
			$response['student_price'] = $recording_pricing['student_price'];
			$response['student_price_formatted'] = $recording_pricing['student_price_formatted'];
			$response['non_early'] = $recording_pricing['non_early_price'];
			if($recording_pricing['earlybird_msg'] <> ''){
				$response['earlybird'] = 'y';
			}
			$response['icon'] = cc_currencies_icon($recording_pricing['curr_found']);
			$response['gift_voucher_value'] = cc_voucher_offer_value($recording_pricing['curr_found']);
			$response['status'] = 'ok';
			$response['msg'] = '';
		}
	}
    echo json_encode($response);
    die();
}

// money_format is deprecated in phpv7.4
function cc_money_format($amount, $currency){
    if ($amount === null || $amount === '') {
        $amount = 0;
    }	
	if($currency == 'AUD'){
		$locale = 'en_AU';
	}elseif($currency == 'USD'){
		$locale = 'en_US';
	}elseif($currency == 'EUR'){
		// need this to show the € symbol
		$locale = 'en_IE';
	}else{
		$locale = 'en_GB';
	}
	$new_amount = new \NumberFormatter( $locale, \NumberFormatter::CURRENCY );
	return $new_amount->format( $amount );
}

// gets the payment amount with currency symbol and VAT amount
function ccpa_payment_data_amount($payment_data){
	// ccpa_write_log('function ccpa_payment_data_amount currency:'.$payment_data['currency'].' amount:'.$payment_data["payment_amount"].' vat:'.$payment_data["vat_included"]);
	$text = $payment_data['currency'].' '.cc_money_format($payment_data["payment_amount"], $payment_data['currency']);
	if($payment_data['vat_included'] == 0){
		$text .= ' (no VAT)';
	}else{
		$text .= ' (incl. '.cc_money_format($payment_data["vat_included"], $payment_data['currency']).' VAT)';
	}
	return $text;
}
