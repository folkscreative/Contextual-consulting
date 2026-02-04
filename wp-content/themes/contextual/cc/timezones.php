<?php
/**
 * Timezones
 */

// get user's timezone
// uses the saved option where it can
// failing that it looks for a timezone cookie
// failing that it uses the user's currency
// returns the timezone name (eg Europe/London)
function cc_timezone_get_user_timezone($user_id=0){
	if($user_id == 0){
		$user_id = get_current_user_id(); // 0 if no user logged in
	}
	if($user_id > 0){
		// try the option
		$timezone = get_user_meta($user_id, 'workshop_timezone', true);
		if($timezone <> ''){
			return $timezone;
		}
	}
	// try the timezone cookie
	if(isset($_COOKIE['ctimezone'])){
		if(in_array($_COOKIE['ctimezone'], timezone_identifiers_list())){
			return $_COOKIE['ctimezone'];
		}
	}
	// try the currency cookie
	if(isset($_COOKIE['ccurrency'])){
		$currency = $_COOKIE['ccurrency'];
		if($currency == 'GBP'){
			return 'Europe/London';
		}
		if($currency == 'AUD'){
			return 'Australia/Melbourne';
		}
		if($currency == 'USD'){
			return 'America/New_York';
		}
	}
	// ok now try using the IP address
	// we could try to get the timezone from the IP address but that requires a second lookup database and is not 100% reliable and therefore we'll just use the country code from the current db
	$user_country = cc_currencies_get_ip_country();
	if($user_country !== NULL){
		if($user_country == 'AU' || $user_country == 'NZ'){
			return 'Australia/Melbourne';
		}elseif($user_country == 'US' || $user_country == 'CA'){
			return 'America/New_York';
		}else{
			return 'Europe/London';
		}
	}
	// no luck, we'll use London
	return 'Europe/London';
}

// returns a pretty version of the user's timezone
// looks for a saved one for the user first, if not, uses the applied timezone to work one out
function cc_timezone_get_user_timezone_pretty($user_id=0, $timezone=''){
	if($user_id == 0){
		$user_id = get_current_user_id(); // 0 if no user logged in
	}
	if($user_id > 0){
		// try the option
		$p_timezone = get_user_meta($user_id, 'pretty_timezone', true);
		if($p_timezone <> ''){
			return $p_timezone;
		}
	}
	// try the timezone cookie
	if(isset($_COOKIE['ptimezone'])){
		$p_timezone = stripslashes( sanitize_text_field( $_COOKIE['ptimezone'] ) );
		if($p_timezone <> ''){
			return $p_timezone;
		}
	}
	// nothing saved ... let's use the timezone
	if($timezone == ''){
		$timezone = cc_timezone_get_user_timezone($user_id);
	}
	return cc_timezones_pretty_timezone($timezone);
}

// returns the timezone name (eg Europe/London) when given a timezone id (eg 341)
// no longer needed!
/*
function cc_timezone_name($timezone_id){
	$all_timezones = timezone_identifiers_list();
	// var_dump($all_timezones);
	if(isset($all_timezones[$timezone_id])){
		return $all_timezones[$timezone_id];
	}
	return NULL;
}
*/

// returns the select options for changing timezone
function cc_timezone_select_options($selected=''){
	$timezone_names = timezone_identifiers_list();
	$html = '';
	foreach ($timezone_names as $timezone_name) {
        $html .='<option value="'.$timezone_name.'"';
        $html .= ($timezone_name == $selected ? ' selected' : '');
        $html .= '>'.$timezone_name.'</option>';
    }
    return $html;
}

// migrate from integers to strings
/*
add_shortcode('update_all_timezones', 'cc_timezone_update_all_timezones');
function cc_timezone_update_all_timezones(){
	$users = get_users();
	$html = '';
	$counts = array(
		'uk' => 0,
		'au' => 0,
		'us' => 0,
	);
	foreach ($users as $user) {
		$workshop_timezone = get_user_meta($user->ID, 'workshop_timezone', true);
		if($workshop_timezone <> ''){
			$do_update = true;
			switch ($workshop_timezone) {
				case '340':
				case '341':
					$new_timezone = 'Europe/London';
					$counts['uk'] ++;
					break;
				case '312':
					$new_timezone = 'Australia/Melbourne';
					$counts['au'] ++;
					break;
				case '151':
					$new_timezone = 'America/New_York';
					$counts['us'] ++;
					break;
				default:
					$html .= '<br>'.$user->ID.' '.$workshop_timezone;
					$do_update = false;
					break;
			}
			if($do_update){
				update_user_meta($user->ID, 'workshop_timezone', $new_timezone);
			}
		}
	}
	$html .= '<br>Counts: UK:'.$counts['uk'].' US:'.$counts['us'].' AU:'.$counts['au'];
	return $html;
}
*/

// the change timezone modal html, added in to the footer
add_action( 'wp_footer', 'cc_timezones_modal_html' );
function cc_timezones_modal_html(){
	?>
	<div id="chg-time-modal" class="modal timezone-modal cc-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Change timezone</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<p>Currently showing times in <span id="chg-time-pretty-now"></span> time.</p>
					<label for="chg-time" class="form-label">Choose desired timezone</label>
					<input type="text" class="form-control" id="chg-time" placeholder="start typing a city or timezone here">
					<input type="hidden" id="sel-prettyname">
					<input type="hidden" id="sel-timezone">
					<div id="chg-time-results" class="chg-time-results"></div>
					<div id="chg-time-msg"></div>
				</div>
				<div class="modal-footer">
					<div class="row align-items-end">
						<div class="col text-start">
							<a href="javascript:void(0)" class="" data-bs-dismiss="modal">Cancel</a>
						</div>
						<div class="col text-end">
							<a href="javascript:void(0)" id="chg-time-save" class="btn btn-primary" data-type="" data-id="">Save</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}


// ajax timezone search
add_action('wp_ajax_timezone_lookup', 'cc_timezones_timezone_lookup');
add_action('wp_ajax_nopriv_timezone_lookup', 'cc_timezones_timezone_lookup');
function cc_timezones_timezone_lookup(){
	global $wpdb;
	$response = array(
		'status' => 'error',
		'html' => '<div class="chg-time-result-msg">Nothing found</div>',
	);
	$query = '';
	if(isset($_POST['query']) ){
		$query = strtolower( stripslashes( sanitize_text_field($_POST['query']) ) );
	}
	if($query <> ''){
		$table_name = $wpdb->prefix.'timezones';
		$sql = "SELECT * FROM $table_name WHERE placename_country LIKE '%".$query."%' ORDER BY population DESC LIMIT 10";
		$some_timezones = $wpdb->get_results($sql, ARRAY_A);
		if($some_timezones){
			$response['html'] = '<div class="small">Select from the following (or keep typing) ...</div>';
			$response['status'] = 'ok';
			foreach ($some_timezones as $timezone) {
				$response['html'] .= '<div class="chg-time-result" data-prettyname="'.$timezone['placename'].'" data-timezone="'.$timezone['timezone'].'">'.$timezone['placename_country'].'</div>';
			}
		}
		/*
		$query = str_replace(' ', '_', $query);
		$all_timezones = timezone_identifiers_list();
		$count_found = 0;
		foreach ($all_timezones as $timezone_name){
			$tz = strtolower($timezone_name);
			if(strpos($tz, $query) !== false){
				if($count_found == 0){
					$response['html'] = '<div class="small">Select from the following ...</div>';
					$response['status'] = 'ok';
				}
				$response['html'] .= '<div class="chg-time-result">'.$timezone_name.'</div>';
				if($count_found > 9){
					break;
				}
				$count_found ++;
			}
		}
		*/
	}
    echo json_encode($response);
    die();
}

// change timezone
add_action('wp_ajax_change_timezone', 'cc_currencies_change_timezone');
add_action('wp_ajax_nopriv_change_timezone', 'cc_currencies_change_timezone');
function cc_currencies_change_timezone(){
	$response = array(
		'status' => 'error',
		'msg' => 'Something went wrong. <i class="fa-solid fa-face-frown"></i> Unable to change timezone.',
		'start_time' => '',
		'tz_pretty' => '',
		'event' => array(),
		'current_time' => '',
		'locale_times' => '',
		'locale_date' => '',
	);
	$train_type = '';
	if(isset($_POST['trainType']) && $_POST['trainType'] == 'w' || $_POST['trainType'] == 'r'){
		$train_type = $_POST['trainType'];
	}
	$train_id = 0;
	if(isset($_POST['trainID']) ){
		$train_id = (int) $_POST['trainID'];
	}
	$timezone = '';
	if(isset($_POST['timezone']) && in_array($_POST['timezone'], timezone_identifiers_list()) ){
		$timezone = $_POST['timezone'];
	}
	$pretty_time = '';
	if(isset($_POST['prettyTime'])){
		$pretty_time = stripslashes( sanitize_text_field( $_POST['prettyTime'] ) );
	}
	if($pretty_time == ''){
		$pretty_time = cc_timezones_pretty_timezone($timezone);
	}
	if($timezone <> ''){
		$response['status'] = 'ok';
		$response['msg'] = '';
		$response['tz_pretty'] = $pretty_time;
		$user_id = get_current_user_id();
		if($user_id > 0){
			update_user_meta($user_id, 'workshop_timezone', $timezone);
			update_user_meta($user_id, 'pretty_timezone', $pretty_time);
		}
		$date = new DateTime("now", new DateTimeZone($timezone) );
		$response['current_time'] = $date->format('jS M Y H:i');
		if($train_type <> '' && $train_id > 0 ){
			if($train_type == 'w'){
				$pretty_dates = workshop_calculated_prettydates($train_id, $timezone);
				if($pretty_dates['locale_start_time'] <> ''){
					$response['start_time'] = $pretty_dates['locale_start_time'];
					$response['locale_times'] = $pretty_dates['locale_times'];
					$response['locale_date'] = $pretty_dates['locale_date'];
					for ($i = 1; $i<16; $i++) {
						$event_dt = workshop_event_display_date_time($train_id, $i, $timezone);
						$response['event'][$i] = $event_dt['date'].' '.$event_dt['time'];
					}
				}
			}else{
				// recording






			}
		}else{
			// no training selected
		}
	}else{
		// error
	}
    echo json_encode($response);
    die();
}

// returns all timezones as options for a select clause
// heavily inspired by wp_timezone_choice
function cc_timezones_timezone_choice($selected_zone){
	$continents = array( 'Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific' );
	$zonen = array();
	foreach ( timezone_identifiers_list() as $zone ) {
		$zone = explode( '/', $zone );
		if ( ! in_array( $zone[0], $continents, true ) ) {
			continue;
		}
		// This determines what gets set and translated - we don't translate Etc/* strings here, they are done later.
		$exists    = array(
			0 => ( isset( $zone[0] ) && $zone[0] ),
			1 => ( isset( $zone[1] ) && $zone[1] ),
			2 => ( isset( $zone[2] ) && $zone[2] ),
		);
		$exists[3] = ( $exists[0] && 'Etc' !== $zone[0] );
		$exists[4] = ( $exists[1] && $exists[3] );
		$exists[5] = ( $exists[2] && $exists[3] );
		$zonen[] = array(
			'continent'   => ( $exists[0] ? $zone[0] : '' ),
			'city'        => ( $exists[1] ? $zone[1] : '' ),
			'subcity'     => ( $exists[2] ? $zone[2] : '' ),
			't_continent' => ( $exists[3] ? str_replace( '_', ' ', $zone[0] ) : '' ),
			't_city'      => ( $exists[4] ? str_replace( '_', ' ', $zone[1] ) : '' ),
			't_subcity'   => ( $exists[5] ? str_replace( '_', ' ', $zone[2] ) : '' ),
		);
	}
	usort( $zonen, '_wp_timezone_choice_usort_callback' );
	$structure = array();
	if ( empty( $selected_zone ) ) {
		$structure[] = '<option selected="selected" value="">' . __( 'Select a city' ) . '</option>';
	}
	foreach ( $zonen as $key => $zone ) {
		// Build value in an array to join later.
		$value = array( $zone['continent'] );
		if ( empty( $zone['city'] ) ) {
			// It's at the continent level (generally won't happen).
			$display = $zone['t_continent'];
		} else {
			// It's inside a continent group.
			// Continent optgroup.
			if ( ! isset( $zonen[ $key - 1 ] ) || $zonen[ $key - 1 ]['continent'] !== $zone['continent'] ) {
				$label       = $zone['t_continent'];
				$structure[] = '<optgroup label="' . esc_attr( $label ) . '">';
			}
			// Add the city to the value.
			$value[] = $zone['city'];
			$display = $zone['t_city'];
			if ( ! empty( $zone['subcity'] ) ) {
				// Add the subcity to the value.
				$value[]  = $zone['subcity'];
				$display .= ' - ' . $zone['t_subcity'];
			}
		}
		// Build the value.
		$value    = implode( '/', $value );
		$selected = '';
		if ( $value === $selected_zone ) {
			$selected = 'selected="selected" ';
		}
		$structure[] = '<option ' . $selected . 'value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';
		// Close continent optgroup.
		if ( ! empty( $zone['city'] ) && ( ! isset( $zonen[ $key + 1 ] ) || ( isset( $zonen[ $key + 1 ] ) && $zonen[ $key + 1 ]['continent'] !== $zone['continent'] ) ) ) {
			$structure[] = '</optgroup>';
		}
	}
	return implode( "\n", $structure );
}

// returns a tidy version of the last part of the timezone
// eg "America/New_York" returns "New York" and "America/Kentucky/Louisville" returns "Kentucky - Louisville"
function cc_timezones_pretty_timezone($zone){
	$zone = explode( '/', $zone );
	if(isset($zone[2]) && $zone[2] <> ''){
		return str_replace('_', ' ', $zone[1].' - '.$zone[2]);
	}
	return str_replace('_', ' ', $zone[1]);
}

// converts date/time to different timezone
// date must be d/m/y time must be h:i from & to must be timezones eg 'UTC' or 'Europe/London'
function cc_timezones_convert($date, $time, $from, $to, $format='d/m/Y H:i', $icon=false){
	$response = array(
		'status' => 'error',
		'date' => '',
		'time' => '',
		'datetime' => '',
	);
    $datetime = DateTime::createFromFormat("d/m/Y H:i", $date.' '.$time, new DateTimeZone($from));
    if($datetime){
        $new_datetime = new DateTime('now', new DateTimeZone($to));
        $new_datetime->setTimestamp($datetime->getTimestamp());
        $response['date'] = $new_datetime->format('d/m/Y');
        $response['time'] = $new_datetime->format('H:i');
        $response['datetime'] = $new_datetime->format($format);
        $response['status'] = 'ok';
        if($icon){
        	if( $new_datetime->format('H') < 6 || $new_datetime->format('H') > 17 ){
        		$response['time'] .= ' <i class="fa-solid fa-moon"></i>';
        		$response['datetime'] .= ' <i class="fa-solid fa-moon"></i>';
        	}else{
        		$response['time'] .= ' <i class="fa-solid fa-sun"></i>';
        		$response['datetime'] .= ' <i class="fa-solid fa-sun"></i>';
        	}
        }
    }
    return $response;
}

// convert datetime to different timezone
// useful for payment last_update
function cc_timezone_datetime_convert( $datetime, $from, $to ){
    $datefrom = DateTime::createFromFormat("Y-m-d H:i:s", $datetime, new DateTimeZone($from));
    if($datefrom){
        $dateto = new DateTime('now', new DateTimeZone($to));
        $dateto->setTimestamp($datefrom->getTimestamp());
        return $dateto->format( 'jS M Y H:i' );
    }else{
    	return '';
    }
}

// not quite a timezone but this function converts seconds to a string of mins and secs (or maybe hours too)
function cc_timezones_format_duration( $seconds ) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }

    if ($minutes > 0 || $hours > 0) {
        $parts[] = $minutes . 'm';
    }

    $parts[] = $remainingSeconds . 's';

    return implode(' ', $parts);
}
