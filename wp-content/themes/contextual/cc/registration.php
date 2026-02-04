<?php
/**
 * Functions for the registration page
 */

// assemble the training panel content
// $training_type = w, r, s or g
// $student should be 'yes' or 'no'
function cc_registration_training_panel($training_type, $training_id, $eventID, $timezone, $raw_price, $currency, $student, $earlybird, $discount, $voucher, $vat_exempt, $upsell_workshop_id, $page, $num_attends, $group_training=array()){
	
	/*
	ccpa_write_log('cc_registration_training_panel');
	ccpa_write_log(array(
		'training_type' => $training_type,
		'training_id' => $training_id,
		'eventID' => $eventID,
		'timezone' => $timezone,
		'raw_price' => $raw_price,
		'currency' => $currency,
		'student' => $student,
		'earlybird' => $earlybird,
		'discount' => $discount,
		'voucher' => $voucher,
		'vat_exempt' => $vat_exempt,
		'upsell_workshop_id' => $upsell_workshop_id,
		'page' => $page,
		'num_attends' => $num_attends,
		'group_training' => $group_training,
	));
	*/

	$html = '';
	
	$portal_user = '';
	if(cc_users_is_valid_user_logged_in()){
		$portal_user = get_user_meta(get_current_user_id(), 'portal_user', true);
	}

	if( $training_type <> 'g' ){
		$html .= cc_registration_training_detail($training_type, $training_id, $eventID, $timezone);
	}

	// include an upsell panel if it does (or might) apply
	if($portal_user == '' && ( $training_type == 'w' || $training_type == 'r' ) ){
		if($upsell_workshop_id > 0){
			// it has been selected already
			$html .= '<div id="training-panel-upsell-wrap" class="training-panel-upsell-wrap">';
			$html .= '<h4 class="mt-3">Plus</h4>';
			$html .= cc_registration_training_detail( substr( course_training_type( $upsell_workshop_id ), 0, 1 ), $upsell_workshop_id, '', $timezone);
			$html .= '</div>';
			// and we need to add the price in ... NO WE DON'T!
			/*
			$upsell = cc_upsells_get_upsell($training_id);
			$other_workshop_pricing = cc_workshop_price_exact($upsell_workshop_id, $currency, '', $student);
			$raw_price = $raw_price + $other_workshop_pricing['raw_price'];
			$raw_price = round( $raw_price - ($raw_price * $upsell['discount'] / 100), 2) ;
			*/
		}else{
			$upsell = cc_upsells_get_upsell($training_id);
			if($upsell !== NULL){
				// it will be offered ...
				if($training_id == $upsell['workshop_1_id']){
					$other_workshop_id = $upsell['workshop_2_id'];
				}else{
					$other_workshop_id = $upsell['workshop_1_id'];
				}
				$html .= '<div id="training-panel-upsell-wrap" class="training-panel-upsell-wrap d-none">';
				$html .= '<h4 class="mt-3">Plus</h4>';
				$html .= cc_registration_training_detail( substr( course_training_type( $other_workshop_id ), 0, 1 ), $other_workshop_id, '', $timezone );
				$html .= '</div>';
			}
		}
	}

	if( $training_type == 'g' ){
		$first = true;
		foreach ( $group_training as $grp_train_id ) {
			$html .= '<h5 class="training-title mb-1">'.get_the_title( $grp_train_id ).'</h5>';
		}
	}

	$html .= '<div id="train-price-wrap">';

	if( $training_type == 'g' ){
		$currency_lc = strtolower( $currency );
		$now = time();
		// add it up
		$vat = 0;
		$training_price = 0;
		$discounts = array();
		$total_discount = 0;
		$payable = 0;
		foreach ( $group_training as $grp_train_id ) {
			$pricing = get_training_prices( $grp_train_id );
			// ccpa_write_log( $pricing );
			$training_price += $pricing['price_'.$currency_lc];
			// student discounts are ignored
			if( $pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] <> '' ){
				$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'] );
				if( $expiry_date ){
					if( $expiry_date->getTimestamp() > $now ){
						$early_bird_name = ( $pricing['early_bird_name'] == '' ) ? 'Early bird' : $pricing['early_bird_name'];
						$discount_amount = round( $pricing['price_'.$currency_lc] * $pricing['early_bird_discount'] / 100, 2 );
						$total_discount += $discount_amount;
						if( isset( $discounts[$early_bird_name] ) ){
							$discounts[$early_bird_name] += $discount_amount;
						}else{
							$discounts[$early_bird_name] = $discount_amount;
						}
					}
				}
			}
		}
		$discounted_price = $training_price - $total_discount;
		$vat = ( $vat_exempt == 'y' ) ? 0 : ccpa_vat_amount( $discounted_price );

		$html .= cc_registration_training_price( $training_price, $currency, $discounts, $vat, $voucher, 'no', '', $portal_user, $num_attends, $training_type );

		$html .= '</div>';
	}else{
		if( $training_type == 's' ){
			$raw_price = $raw_price + $discount;
		}

		if($vat_exempt == 'y'){
			$vat = 0;
		}else{
			$vat = ccpa_vat_amount($raw_price - $discount);
		}
		$earlybird_name = false;
		if($earlybird){
			$earlybird_name = get_post_meta($training_id, 'earlybird_name', true);
			if($earlybird_name == ''){
				$earlybird_name = 'Early-bird';
			}
		}
		$html .= cc_registration_training_price($raw_price, $currency, $discount, $vat, $voucher, $student, $earlybird_name, $portal_user, $num_attends, $training_type);
		$html .= '</div>';
	}

	if($portal_user == ''){
		$html .= cc_voucher_offer_reg_page($training_type, $training_id, $currency, $raw_price);
	}

	if( $portal_user == '' && $page == 'registration' && $raw_price > 0 ){
		$html .= '<p class="small">NOTE: You can change currency and whether you need to pay VAT before completing your registration</p>';
	}
	return $html;
}

// details of a specific training being registered for
function cc_registration_training_detail($training_type, $training_id, $eventID, $timezone){
	$html = '<div class="train-det">';
	// $subtitle = get_post_meta($training_id, 'subtitle', true);
	$subtitle = ''; // removed to save space
	if($subtitle <> ''){
		$html .= '<h5 class="training-title mb-0">'.get_the_title($training_id).'</h5>';
		$html .= '<h6 class="training-subtitle mb-3">'.$subtitle.'</h6>';
	}else{
		$html .= '<h5 class="training-title mb-3">'.get_the_title($training_id).'</h5>';
	}
	if($training_type == 'w'){
		$venue = get_post_meta($training_id, 'event_1_venue_name', true);
		$multi_event = workshop_is_multi_event($training_id);
		if($multi_event){
			$html .= '<div class="events-wrap mb-3"><h6 class="your-events mb-0">Your Events:</h6>';
			$event_ids = explode(',', $eventID);
			foreach ($event_ids as $event_id) {
				$event_id = absint($event_id);
				if($event_id > 0){
					$html .= '<div class="bullet mb-1"><i class="fa-solid fa-calendar-days fa-fw"></i>';
					$event_dt = workshop_event_display_date_time($training_id, $event_id, $timezone);
					$html .= $event_dt['date'].': ';
					$html .= get_post_meta( $training_id, 'event_'.$event_id.'_name', true );
					$html .= '</div>';
				}
			}
			$html .= '</div>';
			$html .= '<div class="bullets-wrap">';
		}else{
			$html .= '<div class="bullets-wrap">';
			$user_id = get_current_user_id(); // 0 if not logged in
			$user_timezone = cc_timezone_get_user_timezone($user_id); // eg Europe/London
			if( $venue <> '' ){
				$timezone = 'Europe/London';
				$user_timezone = 'Europe/London';
				$pretty_timezone = 'London';
			}else{
				$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $user_timezone);
			}
			$pretty_dates = workshop_calculated_prettydates($training_id, $timezone);
			$num_sess = workshop_number_sessions( $training_id );
			$sessions = '';
			if($num_sess > 1){
				$sessions = $num_sess.' sessions: ';
			}
			if( $num_sess > 1){
				if( $venue <> '' ){
		            // face to face workshop
		            $html .= '<div class="bullet face-to-face venue mb-1"><i class="fa-solid fa-location-dot fa-fw"></i>In person: ';
		            $venue_link = get_post_meta( $training_id, 'meta_c', true );
		            if( $venue_link <> '' ){
		                $html .= '<a href="'.esc_url( $venue_link ).'" target="_blank">';
		            }
		            $html .= $venue;
		            if( $venue_link <> '' ){
		                $html .= '</a>';
		            }
		            $html .= '</div><div class="bullet"><i class="fa-solid fa-clock fa-fw"></i>Starts: <span class="start-time">'.$pretty_dates['london_start_time'].'</span> <small>(London time)</small><br><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.$training_id.'">Course schedule</a></div>';
				}else{
					$html .= '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i>Starts: <span class="start-time">'.$pretty_dates['locale_start_time'].'</span> <small>(<span class="user-timezone">'.$pretty_timezone.'</span> time)</small><br><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.$training_id.'">Course schedule</a><br><small><a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="'.$training_id.'">change timezone</a></small></div>';
				}
			}else{
		        if( $venue <> '' ){
		            $html .= '<div class="bullet face-to-face venue"><i class="fa-solid fa-location-dot fa-fw"></i>In person: ';
		            $venue_link = get_post_meta( $training_id, 'meta_c', true );
		            if( $venue_link <> '' ){
		                $html .= '<a href="'.esc_url( $venue_link ).'" target="_blank">';
		            }
		            $html .= $venue;
		            if( $venue_link <> '' ){
		                $html .= '</a>';
		            }
		            $html .= '</div><div class="bullet"><i class="fa-solid fa-clock fa-fw"></i> London: '.$pretty_dates['london_datetime'].'</div>';
		        }else{
					$html .= '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i><span class="locale-times">'.$pretty_dates['locale_times'].'</span> <small>(<span class="user-timezone">'.$pretty_timezone.'</span> time) <a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="'.$training_id.'">change timezone</a></small></div>';
				}
			}
			/*
			if($pretty_dates['locale_date'] <> ''){
				$html .= '<div class="bullet mb-1"><i class="fa-solid fa-calendar-days fa-fw"></i>'.$sessions.$pretty_dates['locale_date'];
				if($num_sess > 1){
					$html .= ' <small><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.$training_id.'">Show full schedule</a></small>';
				}
				$html .= '</div>';
			}
			*/
		}
		$who = get_post_meta($training_id, 'presenter', true);
		if($who <> ''){
			$html .= '<div class="bullet mb-1"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
		}
		/*
		if($where <> ''){
			$html .= '<div class="bullet mb-1"><i class="fa-solid fa-location-dot fa-fw"></i>'.$where;
			$where_link = get_post_meta( $training_id, 'meta_c', true );
			if( $where_link <> '' ){
				$html .= ' <small>'.wms_text_tidy_url( $where_link, '', '_blank' ).'</small>';
			}
			$html .= '</div>';
		}
		*/
		$html .= '</div>';
	}elseif( $training_type == 's' ){
		// series

		// get the courses in the series
        $series_courses = get_post_meta($training_id, '_series_courses', true);
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];

        $html .= '<p>Includes:</p><ul>';
        foreach ($series_courses as $course_id) {
        	$html .= '<li><a href="'.get_permalink( $course_id ).'" target="_blank">'.get_the_title( $course_id ).'</a></li>';
        }
        $html .= '</ul>';
	}
	$html .= '</div>';
	return $html;
}

// returns the price html
// $student should be yes/no
// $earlybird_name is false or text and ignored if $student = 'yes'
function cc_registration_training_price($raw_price, $currency, $discount, $vat, $voucher, $student, $earlybird_name, $portal_user='', $num_attends=1, $training_type=''){
	$show_total = false;
	$html = '
		<div class="mt-3">
			<div class="row">
				<div class="col-8">
					<div class="h6">Training';
					if($student == 'yes'){
						$html .= ' <span class="v-small">(Student)</span>';
					}elseif($earlybird_name){
						$html .= ' <span class="v-small">('.$earlybird_name.')</span>';
					}
					$html .= '
					</div>
				</div>
				<div class="col-4">
					<div class="h6 text-end">';
						if( $raw_price == 0 ){
							$html .= 'Free';
						}else{
							$html .= workshops_price_prefix($currency).number_format($raw_price,2);
						}
						$html .= '
					</div>
				</div>';
	$discount_text = 'Promotional code';
	if($portal_user <> ''){
		switch ($portal_user) {
			case 'cnwl':
				$discount_text = 'CNWL';
				break;
			case 'nlft':
				$discount_text = 'N. London NHS Found. Trust';
				break;
		}
		// $discount_text = strtoupper( $portal_user );
		$discount = $raw_price;
		$vat = 0;
	}elseif( $training_type == 's' ){
		$discount_text = 'Series discount';
	}
	if( $training_type == 'g' ){
		$total_discount = 0;
		// $discount will be an array of discounts
		foreach ( $discount as $discount_name => $discount_amount ) {
			$total_discount += $discount_amount;
			$html .= '
				<div class="col-8">
					<div class="">'.$discount_name.'</div>
				</div>
				<div class="col-4">
					<div class=" text-end">';
						$html .= '-'.workshops_price_prefix( $currency ).number_format( $discount_amount, 2 );
						$html .= '
					</div>
				</div>';
		}
	}else{
		if($discount > 0){
			$show_total = true;
			$html .= '
				<div class="col-8">
					<div class="">'.$discount_text.'</div>
				</div>
				<div class="col-4">
					<div class=" text-end">';
						$html .= '-'.workshops_price_prefix($currency).number_format($discount, 2);
						$html .= '
					</div>
				</div>';
		}
		$total_discount = $discount;
	}
	if( $vat > 0 ){
		$show_total = true;
		$html .= '
				<div class="col-8">
					<div class="">VAT</div>
				</div>
				<div class="col-4">
					<div class=" text-end">';
						$html .= workshops_price_prefix($currency).number_format($vat, 2);
						$html .= '
					</div>
				</div>';
	}
	$total = $raw_price - $total_discount + $vat;
	if($num_attends > 1){
		$show_total = true;
		$html .= '
				<div class="col-8">
					<div class="h6">Per attendee</div>
				</div>
				<div class="col-4">
					<div class="h6 text-end">';
						$html .= workshops_price_prefix($currency).number_format($total, 2);
						$html .= '
					</div>
				</div>';
		$total = $total * $num_attends;
		$html .= '
				<div class="col-8 mb-3">
					<div class="h6">Total for '.$num_attends.' attendees</div>
				</div>
				<div class="col-4 mb-3">
					<div class="h6 text-end">';
						$html .= workshops_price_prefix($currency).number_format($total, 2);
						$html .= '
					</div>
				</div>';
	}else{
		if( $show_total ){
			$html .= '
				<div class="col-8 mb-3">
					<div class="h6">Total</div>
				</div>
				<div class="col-4 mb-3">
					<div class="h6 text-end">';
						$html .= workshops_price_prefix($currency).number_format($total, 2);
						$html .= '
					</div>
				</div>';
		}
	}
	if($voucher > 0){
		$html .= '
				<div class="col-8 mt-3">
					<div class="">Paid by Gift Voucher</div>
				</div>
				<div class="col-4 mt-3">
					<div class="text-end">';
						$html .= '-'.workshops_price_prefix($currency).number_format($voucher, 2);
						$html .= '
					</div>
				</div>';
		$to_pay = $total - $voucher;
		$html .= '
				<div class="col-8 mb-3">
					<div class="h6">Balance to pay</div>
				</div>
				<div class="col-4 mb-3">
					<div class="h6 text-end">';
						$html .= workshops_price_prefix($currency).number_format($to_pay, 2);
						$html .= '
					</div>
				</div>';
	}
	/*
	$html .= '  <div class="col-12 text-end"><small>';
	if($vat > 0){
		$html .= 'incl. VAT';
	}else{
		$html .= 'no VAT';
	}
	$html .= '	</small></div>';
	*/
	$html .= '
			</div>
		</div>
		<div id="training-price-msg"></div>';
	return $html;
}


/**
 * the "who" panel
 * state:
 *  1 = user not logged in, asking for email, 
 *  2 = email entered, now asking for password, 
 *  3 = email not in system, now asking for your details, 
 *  4 = user logged in, show summary (also allow to logout), 
 *  5 = user logged in and editing their details, 
 *  6 = new user, details now entered and user logged in, showing summary
 * 
 * NOTE: Oct 2025 now passing $form_data to this function as $values
 */
function cc_registration_who_panel( $state='1', $error='', $field_classes=array(), $values=array(), $user_id=0, $reg_type='', $reg_token='' ){
	$html = '';
	if($error == ''){
		$form_class = 'needs-validation';
	}else{
		$form_class = 'was-validated';
	}

	// the user could be returning here even if not logged in (eg page reload or going backwards or abandoned cart)
	$user_dets_complete = cc_registration_user_dets_complete_form_data( $values );

	if($user_id > 0){
		$user = get_user_by('ID', $user_id);
		if(!$user_dets_complete){
			$state = '5';
		}
		$portal_user = $values['portal_user'];
		$jobs_type = $portal_user;
	}else{
		$portal_user = '';
		$jobs_type = 'std';
		if( $user_dets_complete ){
			$state = '6';
		}
	}

	if($user_id == 0 && ($state == '1' || $state == '2')){
		$html .= '
			<div class="reg-who">
				<h2>Email:</h2>
				<form action="" class="reg-who-form '.$form_class.'" novalidate>
					<input type="hidden" name="action" value="reg_who_submit">
					<input type="hidden" id="reg-who-state" name="state" value="'.$state.'">
					<input type="hidden" name="reg_type" value="'.$reg_type.'">
					<input type="hidden" name="reg_token" value="'.$reg_token.'">
					<div class="mb-3">
						<label for="email" class="form-label">Enter your email address to start your registration. If you\'ve booked with us before, make sure you use the same email address to keep all your bookings in one place: *</label>
						<input type="email" name="email" id="email" class="form-control form-control-lg ';
						if(isset($field_classes['email'])){
							$html .= $field_classes['email'];
						}
						$html .= '" value="';
						if(isset($values['email'])){
							$html .= $values['email'];
						}
						$html .= '" required>
						<div class="invalid-feedback">Please enter your email address</div>
					</div>';
					if($state == '2'){
						$html .= '
							<div class="mb-3">
								<label for="password" class="form-label">Welcome back! Please enter your password to login:</label>
								<input type="password" name="password" id="password" class="form-control form-control-lg ';
								if(isset($field_classes['password'])){
									$html .= $field_classes['password'];
								}
								$html .= '" value="" required>
								<div class="invalid-feedback">Please enter your password</div>
							</div>';
					}
					$html .= '
					<div class="row mb-3">
						<div id="reg-who-msg" class="col-12 col-md-8"><p class="reg-who-msg">'.$error.'</p>';
						if( $state == '2' && $portal_user == '' ){
							$html .= '<p><a href="';
							$html .= esc_url(add_query_arg(
								array(
									'login' => $values['email'],
									'action' => 'rp',
								),
								'/member-login'
							));
							$html .= '" target="_blank">Forgot password</a></p>';
						}
						$html .= '</div>
						<div class="col-12 col-md-4 text-end">
							<button type="submit" id="reg-who-submit" class="btn btn-primary reg-who-form-submit">';
							switch ($state) {
								case '1':		$html .= 'Continue';		break;
								case '2':		$html .= 'Login';			break;
								case '3':		$html .= 'Save';			break;
							}
							$html .= '</button>
						</div>
					</div>
				</form>
			</div>';

	}elseif(($state == '6') && $user_id == 0){
	    // NEW USER - Display summary from temp registration data
	    // User details are in $values array passed from case 3
	    $html .= '
	    <div class="reg-who">
	        <h2>Register:</h2>
	        <div class="row">
	            <div class="col-6">
	                <p class="lead">Thanks for registering ' . esc_html($values['firstname'] ?? '') . '</p>
	            </div>
	            <div class="col-6 text-end">
	                <!-- No logout button for new users -->
	            </div>
	        </div>
	        <div class="bullet-wrap">
	            <div class="bullet"><i class="fa-solid fa-user fa-fw"></i> ' . 
	                esc_html(($values['firstname'] ?? '') . ' ' . ($values['lastname'] ?? '')) . '</div>';
	            
	            if(!empty($values['job'])){
	                $html .= '<div class="bullet"><i class="fa-solid fa-briefcase fa-fw"></i> '.professions_pretty($values['job']).'</div>';
	            }
	            
	            // Build address string
	            $user_address = cc_users_user_address_formdata( $values );
	            
	            if($user_address <> ''){
	                $html .= '<div class="bullet"><i class="fa-solid fa-location-dot fa-fw"></i> ' . $user_address . '</div>';
	            }
	            
	            if(!empty($values['phone'])){
	                $html .= '<div class="bullet"><i class="fa-solid fa-phone fa-fw"></i> ' . $values['phone'] . '</div>';
	            }
	            $html .= '
	        </div>
	        <div class="row mb-3">
	            <div id="reg-who-msg" class="col-12 col-md-8 reg-who-msg">
	                <p>' . $error . '</p>
	            </div>
	            <div class="col-12 col-md-4 text-end">
	                <form action="" class="reg-who-form" novalidate>
	                    <input type="hidden" name="action" value="reg_who_submit">
	                    <input type="hidden" id="reg-who-state" name="state" value="4">
	                    <input type="hidden" name="reg_type" value="' . $reg_type . '">
	                    <input type="hidden" name="reg_token" value="' . $reg_token . '">
	                    <button type="submit" id="reg-who-submit" class="btn btn-primary reg-who-form-submit">Edit</button>
	                </form>
	            </div>
	        </div>
	    </div>';

	}elseif($user_id > 0 && ($state == '1' || $state == '4' || $state == '6') && $user_dets_complete){
		// display logged in user summary
		$html .= '
		<div class="reg-who">
			<h2>Register:</h2>
			<div class="row">
				<div class="col-6">
					<p class="lead">';
					if($state == '6'){
						$html .= 'Thanks for registering '.$values['firstname'];
					}else{
						$html .= 'Welcome back '.$values['firstname'].'<br>Please check your contact details:';
					}
					$html .= '</p>
				</div>
				<div class="col-6 text-end">';
					if($state == '4'){
						$html .= '<a href="javascript:void(0);" id="reg-logout" class="btn btn-sm btn-default reg-logout-btn" data-token="'.$reg_token.'">Register as somebody else</a>';
					}
					$html .= '
				</div>
			</div>
			<div class="bullet-wrap">
				<div class="bullet"><i class="fa-solid fa-user fa-fw"></i> '.$values['firstname'].' '.$values['lastname'].'</div>';
				// $user_job = get_user_meta( $user_id, 'job', true);
				if($values['job'] <> ''){
					$html .= '<div class="bullet"><i class="fa-solid fa-briefcase fa-fw"></i> '.professions_pretty($values['job']).'</div>';
				}
				$user_address = cc_users_user_address_formdata( $values );
				if($user_address <> ''){
					$html .= '<div class="bullet"><i class="fa-solid fa-location-dot fa-fw"></i> '.$user_address.'</div>';
				}
				// $user_phone = cc_users_user_phone($user_id);
				if($values['phone'] <> ''){
					$html .= '<div class="bullet"><i class="fa-solid fa-phone fa-fw"></i> '.$values['phone'].'</div>';
				}
				$html .= '
			</div>
			<div class="row mb-3">
				<div id="reg-who-msg" class="col-12 col-md-8 reg-who-msg">
					<p>'.$error.'</p>
				</div>
				<div class="col-12 col-md-4 text-end">
					<form action="" class="reg-who-form" novalidate>
						<input type="hidden" name="action" value="reg_who_submit">
						<input type="hidden" id="reg-who-state" name="state" value="4">
						<input type="hidden" name="reg_type" value="'.$reg_type.'">
						<input type="hidden" name="reg_token" value="'.$reg_token.'">
						<button type="submit" id="reg-who-submit" class="btn btn-primary reg-who-form-submit">Edit</button>
					</form>
				</div>
			</div>
		</div>';
	}elseif($state == '3' || $state == '5' || !$user_dets_complete){
		// send the add/edit user details form
		$html .= '
		<div class="reg-who">
			<h2>Your details:</h2>
			<form action="" class="reg-who-form '.$form_class.'" novalidate>
				<input type="hidden" name="action" value="reg_who_submit">
				<input type="hidden" id="reg-who-state" name="state" value="'.$state.'">
				<input type="hidden" name="reg_type" value="'.$reg_type.'">
				<input type="hidden" name="reg_token" value="'.$reg_token.'">
				<input type="hidden" name="user_id" value="'.$user_id.'">
				<div class="mb-3">
					<label for="email" class="form-label">Enter your email address to start your registration. If you\'ve booked with us before, make sure you use the same email address to keep all your bookings in one place: *</label>
					<input type="email" name="" id="email" class="form-control form-control-lg is-valid" value="'.cc_registration_safe_value($values, 'email').'" disabled>
					<input type="hidden" name="email" value="'.cc_registration_safe_value($values, 'email').'">
				</div>';
				if($state == '5'){
					if(!$user_dets_complete){
						$html .= '<h5>Welcome back! Please complete your details</h5>';
					}else{
						$html .= '<h5>Please update your details</h5>';
					}
				}else{
					$html .= '<h5>Welcome! Please tell us a little about yourself</h5>';
				}
				$user_job = cc_registration_safe_value($values, 'job');
				$html .= '
				<div class="row mb-3">
					<div class="col-md-6">
						<label for="firstname" class="form-label">First name *</label>
						<input type="text" class="form-control form-control-lg" id="firstname" name="firstname" value="'.cc_registration_safe_value($values, 'firstname').'" required>
					</div>
					<div class="col-md-6">
						<label for="lastname" class="form-label">Last name *</label>
						<input type="text" class="form-control form-control-lg" id="lastname" name="lastname" value="'.cc_registration_safe_value($values, 'lastname').'" required>
					</div>
				</div>';
				if( $portal_user == 'nlft' ){
					$html .= '
				<div class="mb-3">
					<label for="org_name" class="form-label">Organisation *</label>
					<input type="text" class="form-control form-control-lg" id="org_name" name="org_name" value="'.cc_registration_safe_value($values, 'org_name').'" required>
				</div>
					';
				}else{
					$html .= '<input type="hidden" name="org_name" value="">';
				}
				$html .= '
				<div class="mb-3">
					<label for="address_line_1" class="form-label">Address: line 1 *</label>
					<input type="text" class="form-control form-control-lg" id="address_line_1" name="address_line_1" value="'.cc_registration_safe_value($values, 'address_line_1').'" required>
				</div>
				<div class="mb-3">
					<label for="address_line_2" class="form-label">Line 2</label>
					<input type="text" class="form-control form-control-lg" id="address_line_2" name="address_line_2" value="'.cc_registration_safe_value($values, 'address_line_2').'">
				</div>
				<div class="row mb-3">
					<div class="col-md-6">
						<label for="address_town" class="form-label">City/town *</label>
						<input type="text" class="form-control form-control-lg" id="address_town" name="address_town" value="'.cc_registration_safe_value($values, 'address_town').'" required>
					</div>
					<div class="col-md-6">
						<label for="address_county" class="form-label">County/state</label>
						<input type="text" class="form-control form-control-lg" id="address_county" name="address_county" value="'.cc_registration_safe_value($values, 'address_county').'">
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-6">
						<label for="address_postcode" class="form-label">Postcode/zip *</label>
						<input type="text" class="form-control form-control-lg" id="address_postcode" name="address_postcode" value="'.cc_registration_safe_value($values, 'address_postcode').'" required>
					</div>
					<div class="col-md-6">
						<label for="address_country" class="form-label">Country *</label>
						<select name="address_country" id="address_country" class="form-select form-select-lg" required>
							<option value="">Please select ...</option>
							'.ccpa_countries_options(cc_registration_safe_value($values, 'address_country')).'
						</select>
					</div>
				</div>';
				if( $portal_user <> 'nlft' ){
					$html .= '
				<div class="mb-3">
					<label for="phone" class="form-label">Phone *</label>
					<input type="text" class="form-control form-control-lg" id="phone" name="phone" value="'.cc_registration_safe_value($values, 'phone').'" required>
				</div>';
				}
				$html .= '
				<div class="mb-3">
					<label for="job" class="form-label">Profession *</label>
					<div id="job-message" style="display: none; font-size: 0.9rem; color: #6c757d;">
						Tip: If your job title appears in the list, please select it. If not, just keep typing.
					</div>
					<input type="text" id="job" name="job" class="form-control form-control-lg" autocomplete="off" value="'.professions_pretty( cc_registration_safe_value( $values, 'job' ) ).'" required placeholder="Start typing your job title - select from the list if possible">
					<input type="hidden" id="job_id" name="job_id" value="'.cc_registration_safe_value( $values, 'job' ).'">
				</div>';
				if( $portal_user == 'nlft' ){
					// collect extra stuff for NLFT
					$html .= '
					<h5>North London NHS Foundation Trust Information</h5>
					<div class="row mb-3">
						<div class="col-md-4">
							<label for="nlft_service_type" class="form-label">Service type *</label>
							<select name="nlft_service_type" id="nlft_service_type" class="form-select form-select-lg" required>
								<option value="">Please select</option>';
								$curr_value = cc_registration_safe_value($values, 'nlft_service_type');
								foreach ( nlft_service_types() as $value) {
									$html .= '<option value="'.$value.'" '.selected( $value, $curr_value, false ).'>'.$value.'</option>';
								}
								$html .= '
							</select>
						</div>
						<div class="col-md-4">
							<label for="nlft_borough" class="form-label">Care Group *</label>
							<select name="nlft_borough" id="nlft_borough" class="form-select form-select-lg" required>
								<option value="">Please select</option>';
								$curr_value = cc_registration_safe_value($values, 'nlft_borough');
								$nlft_boroughs = nlft_boroughs();
								$other_selected = '';
								$show_hidden = false;
								if( $curr_value <> '' && ! in_array( $curr_value, $nlft_boroughs ) ){
									$other_selected = ' selected="selected"';
									$show_hidden = true;
								}
								foreach ( $nlft_boroughs as $value) {
									$html .= '<option value="'.$value.'" '.selected( $value, $curr_value, false ).'>'.$value.'</option>';
								}
								$html .= '<option value="other"'.$other_selected.'>Other</option>
							</select>
							<!-- Hidden text field for "Other" -->';
							if( $show_hidden ){
								$html .= '
						    <div id="other_borough_wrap" class="mb-3">
						        <label for="other_borough" class="form-label">Please specify your borough *</label>
						        <input type="text" id="other_borough" name="other_borough" class="form-control form-control-lg" value="'.$curr_value.'" required>
								<div id="other_borough_msg" class="form-text invalid-feedback">Please tell us your borough</div>
						    </div>
								';
							}else{
								$html .= '
						    <div id="other_borough_wrap" class="mb-3" style="display: none;">
						        <label for="other_borough" class="form-label">Please specify your borough *</label>
						        <input type="text" id="other_borough" name="other_borough" class="form-control form-control-lg">
								<div id="other_borough_msg" class="form-text invalid-feedback">Please tell us your borough</div>
						    </div>
								';
							}
							$html .= '
						</div>
						<div class="col-md-4">
							<label for="nlft_team" class="form-label">Team *</label>
							<input type="text" class="form-control form-control-lg" id="nlft_team" name="nlft_team" value="'.cc_registration_safe_value($values, 'nlft_team').'" required>
						</div>
					</div>
					';
				}
				$html .= '
				<div class="row mb-3">
					<div class="col-6">
						<p class="reg-who-msg"></p>';
						if($state == '5' && $user_dets_complete){
							$html .= '<p><a href="javascript:void(0)" id="reg-who-edit-cancel" data-token="'.$reg_token.'">Cancel</a></p>';
						}
						$html .= '
					</div>
					<div class="col-6 text-end">
						<button type="submit" id="reg-who-submit" class="btn btn-primary reg-who-form-submit">Save</button>
					</div>
				</div>
			</form>
		</div>';
	}
	return wms_tidy_html( $html );
}

// returns safe values
function cc_registration_safe_value($values, $field){
	if(isset($values[$field])){
		return $values[$field];
	}
	return '';
}

// are the user details sufficently complete to allow registration to continue?
function cc_registration_user_dets_complete($user_id){
    if( get_user_meta($user_id, 'first_name', true) == ''
    	|| get_user_meta($user_id, 'last_name', true) == ''
    	|| get_user_meta($user_id, 'address_line_1', true) == ''
    	|| get_user_meta($user_id, 'address_town', true) == ''
    	|| get_user_meta($user_id, 'address_postcode', true) == ''
    	|| get_user_meta($user_id, 'address_country', true) == '' ){
    	return false;
    }
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    if ($portal_user !== 'nlft') {
    	if( get_user_meta($user_id, 'phone', true) == '' ){
    		return false;
    	}
    }
	return true;
}

function cc_registration_user_dets_complete_form_data( $form_data ){
    // Check required fields (always required)
    $required_fields = [
        'firstname',
        'lastname', 
        'address_line_1',
        'address_town',
        'address_postcode',
        'address_country'
    ];
    
    // Add phone to required fields unless portal_user is 'nlft'
    if( !isset($form_data['portal_user']) || $form_data['portal_user'] !== 'nlft' ){
        $required_fields[] = 'phone';
    }
    
    // Check all required fields are present and not empty
    foreach( $required_fields as $field ){
        if( !isset($form_data[$field]) || $form_data[$field] === '' ){
            return false;
        }
    }
    
    return true;
}

// change of user
add_action('wp_ajax_reg_logout', 'cc_registration_reg_logout');
add_action('wp_ajax_nopriv_reg_logout', 'cc_registration_reg_logout');
function cc_registration_reg_logout(){
	$response = array(
		'status' => 'error',
		'html' => '',
		'append' => '',
		'actbtn' => '',
	);
	$reg_token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
    wp_logout();
	$next_state = '1';
	// cc_registration_who_panel( $state='1', $error='', $field_classes=array(), $values=array(), $user_id=0, $reg_type='', $reg_token='' )
	$response['html'] = cc_registration_who_panel( $next_state, '', array(), array(), 0, '', $reg_token );
	$response['status'] = 'ok';
	$response['actbtn'] = contextual_header_action_btn_logged_out();
	echo json_encode($response);
	die;
}

// handles the submission of the registration who panel
add_action('wp_ajax_reg_who_submit', 'cc_registration_reg_who_submit');
add_action('wp_ajax_nopriv_reg_who_submit', 'cc_registration_reg_who_submit');
function cc_registration_reg_who_submit(){
    global $wpdb;

	$response = array(
		'status' => 'error',
		'html' => '',
		'append' => '',
		'actbtn' => '',
	);

	$user_id = get_current_user_id();

	cc_debug_log_anything( array_merge( array( 'function' => 'cc_registration_reg_who_submit', 'loggedin_userid' => $user_id ), $_POST) );

	$text_fields = array('state', 'firstname', 'lastname', 'address_line_1', 'address_line_2', 'address_town', 'address_county', 'address_postcode', 'address_country', 'phone', 'mailing_list', 'source', 'job', 'reg_type', 'job_id', 'org_name', 'nlft_service_type', 'nlft_borough', 'other_borough', 'nlft_team', 'reg_token');
	foreach ($text_fields as $text_field) {
		$$text_field = '';
		if(isset($_POST[$text_field])){
			$$text_field = stripslashes( sanitize_text_field( $_POST[$text_field] ) );
		}
	}
	$email = '';
	if(isset($_POST['email'])){
		$posted_email = strtolower( stripslashes( sanitize_text_field( $_POST['email'] ) ) );
		$email = sanitize_email( $posted_email );
	}
	// $response['debug'] = 'sanitised_email: '.$email;
	$password = '';
	if(isset($_POST['password'])){
		$password = $_POST['password']; // we can safely rely on WP for sanitisation ... and have to because odd characters are allowed
	}
	/*
	$user_id = 0;
	$portal_user = '';
	if(isset($_POST['user_id'])){
		$user_id = absint( $_POST['user_id'] );
	    $portal_user = get_user_meta( $user_id, 'portal_user', true );
	}
	*/

	$registration_data = TempRegistration::get($reg_token);
	$form_data = json_decode($registration_data->form_data, true);

	$next_state = '';
	switch ($state) {
		case '1':
			// 1 = user not logged in, asking for email, 
			// not logged in, email only being submitted
			if($email <> ''){
				$user = cc_users_get_user($email);

		        if($user){
		            // EXISTING USER - store their user details
		            TempRegistration::update_form_data($reg_token, 1, [
	                    'user_id' => $user->ID,
	                    'email' => $email,
		            ]);
		            
		            // prompt for password	
		            $next_state = '2';
		            $response['html'] = cc_registration_who_panel( $next_state, '', array('email' => 'is-valid'), array('email' => $email), 0, $reg_type, $reg_token );
		        }else{
		            // NEW USER - store their email
		            TempRegistration::update_form_data($reg_token, 1, [
	                    'email' => $email,
		            ]);
		            
		            // ask for details
		            $next_state = '3';
		            $response['html'] = cc_registration_who_panel( $next_state, '', array('email' => 'is-valid'), array('email' => $email), 0, $reg_type, $reg_token );
		        }

			}else{
				// bad data, try again
				$next_state = '1';
				$response['html'] = cc_registration_who_panel( $next_state, 'Email address invalid or missing', array('email' => 'is-invalid'), array('email' => $posted_email), $user_id, $reg_type, $reg_token );
			}
			break;
		case '2':
			// 2 = email entered, now asking for password, 
			// email and password supplied for login
			if($email == ''){
				// bad data, go back to start
				$next_state = '1';
				$response['html'] = cc_registration_who_panel( $next_state, 'Email address missing', array('email' => 'is-invalid'), array('email' => $email), $user_id, $reg_type, $reg_token );
			}else{
				if($password == ''){
					// bad
					$next_state = '2';
					$response['html'] = cc_registration_who_panel( $next_state, 'Password invalid or missing', array('email' => 'is-valid', 'password' => 'is-invalid'), array('email' => $email, 'password' => ''), $user_id, $reg_type, $reg_token );
				}else{
					// let's see if we can login with these creds
				    $creds = array(
				        'user_login'    => $email,
				        'user_password' => $password,
				        'remember'      => true
				    );
				    $user = wp_signon( $creds, is_ssl() );
				    if(is_wp_error($user)){
				        $user = cc_users_get_user($email);
				        if($user){
					    	$next_state = '2';
				            $response['html'] = cc_registration_who_panel( $next_state, 'Password incorrect', array('email' => 'is-valid', 'password' => 'is-invalid'), array('email' => $email, 'password' => ''), $user_id, $reg_type, $reg_token );
				        }else{
							$next_state = '1';
							$response['html'] = cc_registration_who_panel( $next_state, 'Email and/or password incorrect', array('email' => 'is-invalid'), array('email' => $email), $user_id, $reg_type, $reg_token );
				        }
				    }elseif(!$user->has_cap('read')){
				        // no role
				        wp_logout();
						$next_state = '1';
						$response['html'] = cc_registration_who_panel( $next_state, 'Invalid email or password. Please try again', array('email' => 'is-invalid'), array('email' => $email), $user_id, $reg_type, $reg_token );
				    }else{
				        wp_set_current_user( $user->ID );
				        update_user_meta($user->ID, 'last_login', time());
				        update_user_meta($user->ID, 'new_password', '');
				        update_user_meta($user_id, 'force_new_password', '');

				        // save what we know about the user so far into the temp registration
				        $user_data = cc_users_user_details( $user );
				        $success = TempRegistration::update_form_data($reg_token, '1', $user_data);

				        // get a freshly updated copy of the temp reg data ...
						$registration_data = TempRegistration::get($reg_token);
						$form_data = json_decode($registration_data->form_data, true);

						// now we're logged in, we'll also sent user data back to the JS:
				        $response['user_data'] = array(
			                'current_user_id' => $user->ID,
			                'current_user_firstname' => get_user_meta($user->ID, 'first_name', true),
			                'current_user_lastname' => get_user_meta($user->ID, 'last_name', true),
			                'current_user_email' => $user->user_email
			            );

				        $next_state = '4';
				        $response['html'] = cc_registration_who_panel( $next_state, '', array(), $form_data, $user->ID, $reg_type );
				        if( cc_registration_user_dets_complete_form_data( $form_data ) ){
				        	// the registration panel will just be shown for info
                            $response['append'] = cc_registration_more_info_panel( $user->ID, $form_data )
					                            . cc_registration_attend_next_panels( '', array(), $form_data, '3', $reg_type );
					        $response['actbtn'] = contextual_header_action_btn_logged_in();
				        }else{
				        	// the registration panel will be asking for details to be completed
				        	// let them save this first before they move on
				        }
				        /*
                        $need_to_capture = professions_reg_capture( $user->ID );
                        if($need_to_capture == 'job'){
                            $response['append'] = cc_registration_more_info_panel( $user->ID );
                        }else{
                        }
				        $response['append'] = cc_registration_attend_next_panels();
                    	*/
				    }
				}
			}
			break;

		case '3':
		    // 3 = email not in system, now asking for your details (NEW USER)
		    
		    $classes = array();
		    $user_data = array();
		    $data_ok = true;
		    
		    // Email is already validated and stored from case 1
		    $user_data['email'] = $email;
		    
		    $opt_fields = array('address_line_2', 'address_county', 'source', 'org_name', 'nlft_service_type', 'nlft_borough', 'nlft_team');
		    $req_fields = array('firstname', 'lastname', 'address_line_1', 'address_town', 'address_postcode', 'address_country', 'job');
		    if( $form_data['portal_user'] == 'nlft' ){
		    	$opt_fields[] = 'phone';
		    }else{
		    	$req_fields[] = 'phone';
		    }

		    foreach ($opt_fields as $field) {
		        $user_data[$field] = $$field;
		        $classes[$field] = 'is-valid';
		    }
		    
		    foreach ($req_fields as $field) {
		        $user_data[$field] = $$field;
		        if($$field == ''){
		            $classes[$field] = 'is-invalid';
		            $data_ok = false;
		        }else{
		            $classes[$field] = 'is-valid';
		        }
		    }
		    
		    if($data_ok){
		        if( $job_id <> '' ){
		            $user_data['job'] = $job_id;
		        }
		        if( $nlft_borough == 'other' && $other_borough <> '' ){
		            $user_data['nlft_borough'] = $other_borough;
		        }
		        
		        // Store the updated details in temp registration for this session
		        $new_user_details = [
	                'email' => $email,
	                'firstname' => $firstname,
	                'lastname' => $lastname,
	                'address_line_1' => $address_line_1,
	                'address_line_2' => $address_line_2,
	                'address_town' => $address_town,
	                'address_county' => $address_county,
	                'address_postcode' => $address_postcode,
	                'address_country' => $address_country,
	                'phone' => $phone,
	                'job' => $user_data['job'],
		            'org_name' => $org_name,
		            'nlft_service_type' => $nlft_service_type,
		            'nlft_borough' => $user_data['nlft_borough'],
		            'nlft_team' => $nlft_team,
	                // 'updated_at' => current_time('mysql')
		        ];
		        		        
		        // Update temp registration with the changes
		        TempRegistration::update_form_data($reg_token, 3, $new_user_details);

		        // get a freshly updated copy of the temp reg data ...
				$registration_data = TempRegistration::get($reg_token);
				$form_data = json_decode($registration_data->form_data, true);
		        
		        $next_state = '6';
		        
		        $response['append'] = cc_registration_more_info_panel( 0, $form_data )
		                            . cc_registration_attend_next_panels( '', array(), array(), '3', $reg_type, $user_data );
		        $response['actbtn'] = ''; // No action button since not logged in
		        
		        // Show the summary panel
		        $response['html'] = cc_registration_who_panel( $next_state, '', array(), $user_data, 0, $reg_type, $reg_token );
		        
		    }else{
		        // Data missing, stay on form
		        $next_state = '3';
		        $response['html'] = cc_registration_who_panel( $next_state, 'Please complete all required fields', $classes, $user_data, 0, $reg_type, $reg_token );
		    }
		    break;

		case '4':
		    // 4 = user logged in, wants to edit their details
		    // This just switches to edit mode
			// $user = wp_get_current_user();
			$next_state = '5';
			// $response['html'] = cc_registration_who_panel( $next_state, '', array(), cc_users_user_details($user), $user->ID, $reg_type, $reg_token );
			$response['html'] = cc_registration_who_panel( $next_state, '', array(), $form_data, $registration_data->user_id, $reg_type, $reg_token );
			break;

		case '5':
		    // 5 = user logged in and editing their details (EXISTING USER)
		    
		    $classes = array();
		    $user_data = array();
		    $data_ok = true;
		    
		    /*
		    // For existing users, user_id should be passed
		    if(!$user_id){
		        $user_id = get_current_user_id();
		    }
		    
		    // Get current portal user status
		    $portal_user = get_user_meta( $user_id, 'portal_user', true );
		    */
		    
		    // Email shouldn't change for existing users but include for consistency
		    $user_data['email'] = $email;
		    $user_data['user_id'] = $user_id;
		    
		    $opt_fields = array('address_line_2', 'address_county', 'source', 'org_name', 'nlft_service_type', 'nlft_borough', 'nlft_team');
		    $req_fields = array('firstname', 'lastname', 'address_line_1', 'address_town', 'address_postcode', 'address_country', 'job');
		    if( $form_data['portal_user'] == 'nlft' ){
		    	$opt_fields[] = 'phone';
		    }else{
		    	$req_fields[] = 'phone';
		    }

		    foreach ($opt_fields as $field) {
		        $user_data[$field] = $$field;
		        $classes[$field] = 'is-valid';
		    }
		    
		    foreach ($req_fields as $field) {
		        $user_data[$field] = $$field;
		        if($$field == ''){
		            $classes[$field] = 'is-invalid';
		            $data_ok = false;
		        }else{
		            $classes[$field] = 'is-valid';
		        }
		    }
		    
		    if($data_ok){
		        if( $job_id <> '' ){
		            $user_data['job'] = $job_id;
		        }
		        if( $nlft_borough == 'other' && $other_borough <> '' ){
		            $user_data['nlft_borough'] = $other_borough;
		        }
		        
		        // UPDATE THE EXISTING USER in WordPress immediately
		        // (they're already logged in so we can update their details now)
		        $user_data['password'] = ''; // Not needed for update
		        $updated_user_id = cc_users_update_details($user_data);
		        
		        // Store the updated details in temp registration for this session
		        $updated_user_details = [
	                'email' => $email,
	                'firstname' => $firstname,
	                'lastname' => $lastname,
	                'address_line_1' => $address_line_1,
	                'address_line_2' => $address_line_2,
	                'address_town' => $address_town,
	                'address_county' => $address_county,
	                'address_postcode' => $address_postcode,
	                'address_country' => $address_country,
	                'phone' => $phone,
	                'job' => $user_data['job'],
		            'org_name' => $org_name,
		            'nlft_service_type' => $nlft_service_type,
		            'nlft_borough' => $user_data['nlft_borough'],
		            'nlft_team' => $nlft_team,
	                // 'updated_at' => current_time('mysql')
		        ];
		        		        
		        // Update temp registration with the changes
		        TempRegistration::update_form_data($reg_token, 5, $updated_user_details);
		        
		        // Also update the main temp registration record
		        /* unnecessary .....
		        global $wpdb;
		        $wpdb->update(
		            $wpdb->prefix . 'temp_registrations',
		            [
		                'user_id' => $user_id,
		                'email' => $email
		            ],
		            ['token' => $reg_token]
		        );
		        */

		        // get a freshly updated copy of the temp reg data ...
				$registration_data = TempRegistration::get($reg_token);
				$form_data = json_decode($registration_data->form_data, true);
		        
		        $next_state = '4'; // Go back to summary view
		        
		        // Generate panels with updated info
		        $response['append'] = cc_registration_more_info_panel( $user_id, $form_data )
		                            . cc_registration_attend_next_panels( '', array(), array(), '3', $reg_type, $user_data );
		        $response['actbtn'] = contextual_header_action_btn_logged_in();
		        $response['html'] = cc_registration_who_panel( $next_state, '', array(), $user_data, $user_id, $reg_type, $reg_token );
		        
		    }else{
		        // Data missing, stay on edit form
		        $next_state = '5';
		        $response['html'] = cc_registration_who_panel( $next_state, 'Please complete all required fields', $classes, $user_data, $user_id, $reg_type, $reg_token );
		    }
		    break;

		case '7':
			// cancel the user edit
			if($registration_data->user_id > 0){
		        $next_state = '4';
		        $response['html'] = cc_registration_who_panel( $next_state, '', array(), $form_data, $registration_data->user_id, $reg_type, $reg_token );
			}else{
				$next_state = '1';
				$response['html'] = cc_registration_who_panel( $next_state, '', array(), array('email' => $email), $user_id, $reg_type, $reg_token );
			}
	        break;
	
		default:
			// code...
			break;
	}

	if($next_state <> ''){
		$response['status'] = 'ok';
	}
	echo json_encode($response);
	die;
}

// the more info panel
// asks for profession if not known
// if req for CNWL also ask's for manager's email
// asks how they heard about this training
// and encourages a newsletter signup (if not already signed-up)
// form_data sent across to accommodate going backwards thru the process or an abandonded resumption
function cc_registration_more_info_panel( $user_id, $form_data = array() ){
	$html = '
	<form id="reg-more-form" class="needs-validation" novalidate>
		<div class="animated-card">
			<div class="reg-more-panel reg-panel wms-background animated-card-inner pale-bg">
				<div class="job-conf">
					<h2>Additional info:</h2>';

	if( ! isset( $form_data['job'] ) || empty( $form_data['job'] ) ){
		$html .= '	<div class="mb-3">
						<label for="job" class="form-label">Job title</label>
						<div id="job-message" style="display: none; font-size: 0.9rem; color: #6c757d;">
							Tip: If your job title appears in the list, please select it. If not, just keep typing.
						</div>
						<input type="text" id="job" name="job" class="form-control form-control-lg" autocomplete="off" value="" required placeholder="Start typing your job title - select from the list if possible">
						<input type="hidden" id="job_id" name="job_id" value="">
					</div>';

		if( isset( $form_data['portal_user'] ) && $form_data['portal_user'] == 'cnwl' ){
			// we may need to capture the manager's email, hide it for now
			$html .= '
					<div id="conf-email-wrap" class="conf-email-wrap hide">
						<div class="mb-3">
							<label for="conf_email" class="form-label">Please confirm your manager\'s approval to register for this training by entering their email address:</label>
							<input type="email" id="conf_email" class="form-control form-control-lg" name="conf_email" value="" required>
							<div id="conf-email-msg" class="form-text invalid-feedback">Your manager\'s email address is required</div>
						</div>
					</div>';
		}

	}else{

		if( isset( $form_data['portal_user'] ) && $form_data['portal_user'] == 'cnwl' ){

			if( ! isset( $form_data['conf_email'] ) || $form_data['conf_email'] == '' ){

				$need_email_conf = false;
				foreach ( professions_get_selected('cnwl') as $job ){
					if( $job['slug'] == $form_data['job'] ){
						if( isset( $job['cnwl_conf'] ) && $job['cnwl_conf'] == 'y'){
							$need_email_conf = true;
						}
						break;
					}
				}

				if( $need_email_conf ){
					$html .= '
					<div id="conf-email-wrap" class="conf-email-wrap">
						<div class="mb-3">
							<label for="conf_email" class="form-label">Please confirm your manager\'s approval to register for this training by entering their email address:</label>
							<input type="email" id="conf_email" class="form-control form-control-lg" name="conf_email" value="" required>
							<div id="conf-email-msg" class="form-text invalid-feedback">Your manager\'s email address is required</div>
						</div>
					</div>';
				}

			}

		}

	}

	// technically we do not need to always ask for source but we're doing it so that there is always something on this panel
	if( isset( $form_data['source'] ) && $form_data['source'] <> '' ){
		$source = $form_data['source'];
	}else{
		$source = '';
	}
	$html .= '		<div id="source-wrap" class="source-wrap">
						<div class="mb-3">
							<label for="source" class="form-label">How did you hear about this training? *</label>
							<input type="text" id="source" class="form-control form-control-lg" name="source" value="'.$source.'" required>
							<div id="source-msg" class="form-text invalid-feedback">Please tell us how you heard about the training</div>
						</div>
					</div>';

	if (isset($form_data['mailing_list']) && 
	    in_array($form_data['mailing_list'], ['y', 'p'])) {
	    // Form data says they're already on the list - don't ask
	    $ask_newsletter = false;
	} elseif ($user_id > 0) {
	    // No form data override, but user is logged in - check their status
	    $user = get_user_by('ID', $user_id);
	    
	    if ($user && cc_mailsterint_on_newsletter($user->user_email)) {
	        // User is already on newsletter
	        $ask_newsletter = false;
	    } else {
	        // User exists but not on newsletter
	        $ask_newsletter = true;
	    }
	} else {
	    // No form data saying they're on list, and not logged in - ask them
	    $ask_newsletter = true;
	}

	if( $ask_newsletter ){
		$html .= '	<div class="mailing-list-wrap">
						<div class="mb-3">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" role="switch" id="mailing_list" name="mailing_list" value="yes" checked>
								<label class="form-check-label" for="mailing_list">Yes, I want to receive the latest updates of training events, ACT resources and information in my inbox?</label>
							</div>
						</div>
					</div>';
	}

	/*
	$portal_user = get_user_meta($user_id, 'portal_user', true);

	// For new users (user_id = 0), check if job was already provided in form_data
	if ($user_id == 0) {
		// Check if job was already collected in the new_user data
		if (isset($form_data['job']) && !empty($form_data['job'])) {
			// Job already provided, don't ask again
			$need_to_capture = '';
		} else {
			// Job not provided yet (shouldn't happen if case 3 is working correctly)
			$need_to_capture = 'job';
		}
	} else {
		// Existing user - check normally
		$need_to_capture = professions_reg_capture( $user_id );
	}
	*/



		/*
	$email_wrap_class = '';
	if($need_to_capture == 'job'){
		$email_wrap_class = 'hide';

		if( isset( $form_data['more']['job'] ) ){
			$job = $form_data['more']['job'];
		}else{
			$job = '';
		}
		if( isset( $form_data['more']['job_id'] ) ){
			$job_id = $form_data['more']['job_id'];
		}else{
			$job_id = '';
		}
		*/

		/*
	}
	if($portal_user == 'cnwl' && ( $need_to_capture == 'job' || $need_to_capture == 'email' ) ){

		if( isset( $form_data['more']['conf_email'] ) ){
			$conf_email = $form_data['more']['conf_email'];
		}else{
			$conf_email = '';
		}

		$html .= '	<div id="conf-email-wrap" class="conf-email-wrap '.$email_wrap_class.'">
						<div class="mb-3">
							<label for="conf_email" class="form-label">Please confirm your manager\'s approval to register for this training by entering their email address:</label>
							<input type="email" id="conf_email" class="form-control form-control-lg" name="conf_email" value="'.$conf_email.'" required>
							<div id="conf-email-msg" class="form-text invalid-feedback">Your manager\'s email address is required</div>
						</div>
					</div>';
	}

	// Source field - check both in 'more' and 'new_user' arrays
	if( isset( $form_data['more']['source'] ) ){
		$source = $form_data['more']['source'];
	} elseif( isset( $form_data['new_user']['source'] ) ){
		$source = $form_data['new_user']['source'];
	} else {
		$source = '';
	}

	$html .= '		<div id="source-wrap" class="source-wrap">
						<div class="mb-3">
							<label for="source" class="form-label">How did you hear about this training? *</label>
							<input type="text" id="source" class="form-control form-control-lg" name="source" value="'.$source.'" required>
							<div id="source-msg" class="form-text invalid-feedback">Please tell us how you heard about the training</div>
						</div>
					</div>';
	$user = get_user_by('ID', $user_id);

	// Only show newsletter signup for existing users (who have an email address)
	// For new users, this might have already been handled during initial registration
	if ($user_id > 0) {
		$user = get_user_by('ID', $user_id);
		if($user && !cc_mailsterint_on_newsletter( $user->user_email )){
			$html .= '	<div class="mailing-list-wrap">
							<div class="mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" id="mailing_list" name="mailing_list" value="yes" checked>
									<label class="form-check-label" for="mailing_list">Yes, I want to receive the latest updates of training events, ACT resources and information in my inbox?</label>
								</div>
							</div>
						</div>';
		}
	} elseif (isset($form_data['new_user']['email'])) {
		// For new users, check if we should show newsletter signup
		$email = $form_data['new_user']['email'];
		if(!cc_mailsterint_on_newsletter( $email )){
			$html .= '	<div class="mailing-list-wrap">
							<div class="mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" id="mailing_list" name="mailing_list" value="yes" checked>
									<label class="form-check-label" for="mailing_list">Yes, I want to receive the latest updates of training events, ACT resources and information in my inbox?</label>
								</div>
							</div>
						</div>';
		}
	}
	*/

	$html .= '	</div>
			</div>
		</div>
	</form>';
	return wms_tidy_html( $html );
}

// CNWL blocked registration panel
// returns '' if not needed
// v1.20 changed from 6 mths to 3 mths
function cc_registration_blocked_panel($user_id){
	$html = '';
	$last_registration = get_user_meta( $user_id, 'last_registration', true );
	$three_months_ago = date('Y-m-d H:i:s', strtotime('-3 months'));
	if($last_registration <> '' && $last_registration > $three_months_ago){
		$dt = DateTime::createFromFormat("Y-m-d H:i:s", $last_registration);
		$dt = $dt->modify("+3 months");
		$html = '
			<div class="animated-card">
				<div class="reg-blocked-panel reg-panel wms-background animated-card-inner pale-bg">
					<h2>2. Registration blocked</h2>
					<p>Sorry but you are only allowed to register for one training every three months. You last registered on '.date('jS M Y', strtotime($last_registration)).'. You will be able to register for more training after '.$dt->format('jS M Y').'.</p>
				</div>
			</div>';
	}
	return wms_tidy_html( $html );
}

// attendee stuff and next btn
// function cc_registration_attend_next_panels( $error='', $field_classes=array(), $values=array(), $panel_num='2', $reg_type='' ){
function cc_registration_attend_next_panels( $error='', $field_classes=array(), $values=array(), $panel_num='2', $reg_type='', $user_override=null ){
	$html = '<form id="reg-attend-form" class="needs-validation" novalidate>';
	$portal_user = '';

    // Use override user or current logged-in user
    if($user_override && isset($user_override['email']) ) {
        // New user scenario - use passed user data
        // $user_id = $user_override['user_id'];
        // $user = get_user_by('ID', $user_id);
        $user_firstname = $user_override['firstname'];
        $user_lastname = $user_override['lastname']; 
        $user_email = $user_override['email'];
        $portal_user = $user_override['portal_user'] ?? '';
    } else if(cc_users_is_valid_user_logged_in()) {
        // Logged in user scenario
        $user_id = get_current_user_id();
        $portal_user = get_user_meta($user_id, 'portal_user', true);
        $user = get_user_by('ID', $user_id);
        $user_firstname = get_user_meta($user_id, 'first_name', true);
        $user_lastname = get_user_meta($user_id, 'last_name', true);
        $user_email = $user->user_email;
    } else {
        // No user - shouldn't happen in normal flow
        $user = false;
        $user_firstname = '';
        $user_lastname = '';
        $user_email = '';
    }

    /*
	if(cc_users_is_valid_user_logged_in()){
		$portal_user = get_user_meta(get_current_user_id(), 'portal_user', true);
	}
	*/

	$token = $_GET['token'] ?? ''; // js will catch if missing
	$html .= '<input type="hidden" name="token" value="'.$token.'">';

	if($portal_user <> '' || $reg_type == 'free'){
		$html .= '<input type="hidden" name= "attend_type" value="me">';
		if( $portal_user <> '' ){
			$html .= '<input type="hidden" name="reg-org-submit" value="go"><button type="submit" id="reg-org-submit" class="btn btn-primary reg-next-submit">Confirm Registration</button>';
		}else{
			$html .= '<input type="hidden" name="reg-conf-submit" value="go"><button type="submit" id="reg-conf-submit" class="btn btn-primary reg-next-submit">Confirm Registration</button>';
		}
	}else{

        $html .= '
        <div class="animated-card">
            <div class="reg-attend-panel reg-panel wms-background animated-card-inner pale-bg">
                <div class="reg-attend">
                    <h2>Attendee:</h2>
                    <div class="mb-3">
                        <label for="switch" class="form-label">Who will be receiving the training?</label>
                        <div class="text-center">
                            <input type="radio" class="btn-check attend-switch" name="attend_type" id="attend-me" autocomplete="off" value="me" checked>
                            <label class="btn btn-secondary mb-1" for="attend-me">Me</label>&nbsp;
                            <input type="radio" class="btn-check attend-switch" name="attend_type" id="attend-notme" autocomplete="off" value="notme">
                            <label class="btn btn-secondary mb-1" for="attend-notme">Somebody else</label>&nbsp;
                            <input type="radio" class="btn-check attend-switch" name="attend_type" id="attend-group" autocomplete="off" value="group">
                            <label class="btn btn-secondary mb-1" for="attend-group">Group booking</label>
                        </div>
                    </div>
                    <!-- Single attendee section -->
                    <div id="reg-attend-dets" class="reg-attend-dets">
                        <p>If you are registering on behalf of somebody else, please enter their details here so that we can also send the training information to them.</p>
                        <div class="row mb-md-3">
                            <div class="col-md-6">
                                <label for="attend_single_first" class="form-label">First name *</label>
                                <input type="text" class="attend-field form-control form-control-lg" id="attend_single_first" name="attend_single_first" value="'.cc_registration_safe_value($values, 'attend_first').'">
                            </div>
                            <div class="col-md-6">
                                <label for="attend_single_last" class="form-label">Last name *</label>
                                <input type="text" class="attend-field form-control form-control-lg" id="attend_single_last" name="attend_single_last" value="'.cc_registration_safe_value($values, 'attend_last').'">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="attend_single_email" class="form-label">Email *</label>
                            <input type="email" class="attend-field form-control form-control-lg" id="attend_single_email" name="attend_single_email" value="'.cc_registration_safe_value($values, 'attend_email').'">
                            <div class="invalid-feedback">Please enter a valid email address that is not the same as yours</div>
                        </div>
                    </div>
                    <!-- Group attendee section -->
                    <div id="reg-attend-group" class="reg-attend-group">
                        <p>Please list all attendees here, including yourself if you will be attending. Training information will be sent to all attendees.</p>
                        <div class="row mb-3">
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label">First name *</label>
                                <input type="text" class="form-control" name="attend_first_reg" value="'.$user_firstname.'" readonly>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label">Last name *</label>
                                <input type="text" class="form-control" name="attend_last_reg" value="'.$user_lastname.'" readonly>
                            </div>
                            <div class="col-md-8 col-lg-4">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" id="attend_email_reg" name="attend_email_reg" value="'.$user_email.'" readonly>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label d-none d-md-block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="attend_check_reg" value="yes" id="" checked>
                                    <label class="form-check-label small" for="">attending</label>
                                </div>
                            </div>
                        </div>
                        <div id="reg-attends-wrap">
                            <div class="row mb-3">
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label d-lg-none">First name *</label>
                                    <input type="text" class="attend-grp-field form-control" name="attend_first[]">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label d-lg-none">Last name *</label>
                                    <input type="text" class="attend-grp-field form-control" name="attend_last[]">
                                </div>
                                <div class="col-md-12 col-lg-6">
                                    <label class="form-label d-lg-none">Email *</label>
                                    <input type="email" class="attend-grp-field form-control attend-email" name="attend_email[]">
                                </div>
                            </div>
                        </div>
                        <a href="javascript:void(0);" id="reg-attend-grp-add" class="btn btn-primary btn-sm reg-attend-grp-add">Add another attendee</a>
                        <p id="attend-email-error" class="attend-email-error text-bg-danger p-2 mt-3">Attendee emails must be unique. Please change the above before continuing.</p>
                        <div id="reg-attend-more" class="d-none">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label d-lg-none">First name *</label>
                                    <input type="text" class="attend-grp-field form-control" name="attend_first[]">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-lg-none">Last name *</label>
                                    <input type="text" class="attend-grp-field form-control" name="attend_last[]">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-lg-none">Email *</label>
                                    <input type="email" class="attend-grp-field form-control attend-email" name="attend_email[]">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="reg-next-btn-wrap text-end mb-5">'.cc_registration_next_btn( $portal_user, $reg_type ).'
        <div id="reg-next-btn-msg" class="d-none text-bg-danger p-3 mt-3 text-start"></div>
        </div>';
    }
    $html .= '</form>';

/*

		$user = get_user_by('ID', get_current_user_id());
		$user_first_name = get_user_meta($user->ID, 'first_name', true);
		$user_last_name = get_user_meta($user->ID, 'last_name', true);
		$html .= '
		<div class="animated-card">
			<div class="reg-attend-panel reg-panel wms-background animated-card-inner pale-bg">
				<div class="reg-attend">
					<h2>Attendee:</h2>
					<div class="mb-3">
						<label for="switch" class="form-label">Who will be receiving the training?</label>
						<div class="text-center">
							<input type="radio" class="btn-check attend-switch" name="attend_type" id="attend-me" autocomplete="off" value="me" checked>
							<label class="btn btn-secondary mb-1" for="attend-me">Me</label>&nbsp;
							<input type="radio" class="btn-check attend-switch" name="attend_type" id="attend-notme" autocomplete="off" value="notme">
							<label class="btn btn-secondary mb-1" for="attend-notme">Somebody else</label>&nbsp;
							<input type="radio" class="btn-check attend-switch" name="attend_type" id="attend-group" autocomplete="off" value="group">
							<label class="btn btn-secondary mb-1" for="attend-group">Group booking</label>
						</div>
					</div>
					<div id="reg-attend-dets" class="reg-attend-dets">
						<p>If you are registering on behalf of somebody else, please enter their details here so that we can also send the training information to them.</p>
						<div class="row mb-md-3">
							<div class="col-md-6">
								<label for="attend_single_first" class="form-label">First name *</label>
								<input type="text" class="attend-field form-control form-control-lg" id="attend_single_first" name="attend_single_first" value="'.cc_registration_safe_value($values, 'attend_first').'">
							</div>
							<div class="col-md-6">
								<label for="attend_single_last" class="form-label">Last name *</label>
								<input type="text" class="attend-field form-control form-control-lg" id="attend_single_last" name="attend_single_last" value="'.cc_registration_safe_value($values, 'attend_last').'">
							</div>
						</div>
						<div class="mb-3">
							<label for="attend_single_email" class="form-label">Email *</label>
							<input type="email" class="attend-field form-control form-control-lg" id="attend_single_email" name="attend_single_email" value="'.cc_registration_safe_value($values, 'attend_email').'">
							<div class="invalid-feedback">Please enter a valid email address that is not the same as yours</div>
						</div>
					</div>
					<div id="reg-attend-group" class="reg-attend-group">
						<p>Please list all attendees here, including yourself if you will be attending. Training information will be sent to all attendees.</p>
						<div class="row mb-3">
							<div class="col-md-6 col-lg-3">
								<label class="form-label">First name *</label>
								<input type="text" class="form-control" name="attend_first_reg" value="'.$user_first_name.'" readonly>
							</div>
							<div class="col-md-6 col-lg-3">
								<label class="form-label">Last name *</label>
								<input type="text" class="form-control" name="attend_last_reg" value="'.$user_last_name.'" readonly>
							</div>
							<div class="col-md-8 col-lg-4">
								<label class="form-label">Email *</label>
								<input type="email" class="form-control" id="attend_email_reg" name="attend_email_reg" value="'.$user->user_email.'" readonly>
							</div>
							<div class="col-md-4 col-lg-2">
								<label class="form-label d-none d-md-block">&nbsp;</label>
								<div class="form-check">
									<input class="form-check-input" type="checkbox" name="attend_check_reg" value="yes" id="" checked>
									<label class="form-check-label small" for="">attending</label>
								</div>
							</div>
						</div>
						<div id="reg-attends-wrap">
							<div class="row mb-3">
								<div class="col-md-6 col-lg-3">
									<label class="form-label d-lg-none">First name *</label>
									<input type="text" class="attend-grp-field form-control" name="attend_first[]">
								</div>
								<div class="col-md-6 col-lg-3">
									<label class="form-label d-lg-none">Last name *</label>
									<input type="text" class="attend-grp-field form-control" name="attend_last[]">
								</div>
								<div class="col-md-12 col-lg-6">
									<label class="form-label d-lg-none">Email *</label>
									<input type="email" class="attend-grp-field form-control attend-email" name="attend_email[]">
								</div>
							</div>
						</div>
						<a href="javascript:void(0);" id="reg-attend-grp-add" class="btn btn-primary btn-sm reg-attend-grp-add">Add another attendee</a>
						<p id="attend-email-error" class="attend-email-error text-bg-danger p-2 mt-3">Attendee emails must be unique. Please change the above before continuing.</p>
						<div id="reg-attend-more" class="d-none">
							<div class="row mb-3">
								<div class="col-md-3">
									<label class="form-label d-lg-none">First name *</label>
									<input type="text" class="attend-grp-field form-control" name="attend_first[]">
								</div>
								<div class="col-md-3">
									<label class="form-label d-lg-none">Last name *</label>
									<input type="text" class="attend-grp-field form-control" name="attend_last[]">
								</div>
								<div class="col-md-6">
									<label class="form-label d-lg-none">Email *</label>
									<input type="email" class="attend-grp-field form-control attend-email" name="attend_email[]">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="reg-next-btn-wrap text-end mb-5">'.cc_registration_next_btn( $portal_user, $reg_type ).'</div>
	</form>';
	}

*/

	return wms_tidy_html( $html );
}

/*
// submission of attendee details
add_action('wp_ajax_reg_attend_submit', 'cc_registration_reg_attend_submit');
add_action('wp_ajax_nopriv_reg_attend_submit', 'cc_registration_reg_attend_submit');
function cc_registration_reg_attend_submit(){
	$response = array(
		'status' => 'error',
		'html' => '',
		'append' => '',
	);
	$attend_first = '';
	if(isset($_POST['attend_first'])){
		$attend_first = stripslashes( sanitize_text_field( $_POST['attend_first'] ) );
	}
	$attend_last = '';
	if(isset($_POST['attend_last'])){
		$attend_last = stripslashes( sanitize_text_field( $_POST['attend_last'] ) );
	}
	$attend_email = '';
	if(isset($_POST['attend_email'])){
		$attend_email = sanitize_email( $_POST['attend_email'] );
	}
	if($attend_first <> '' && $attend_last <> '' && $attend_email <> ''){

	}
	echo json_encode($response);
	die;
}
*/

// the currency changer panel
function cc_registration_curr_panel($currency, $user_country){
	$html = '<div class="animated-card">
				<div class="reg-curr-panel reg-panel wms-background animated-card-inner pale-bg">
					<h2>Currency:</h2>';
	// is the selected currency the same as the expected currency for this IP?
	$expected_currency = cc_currencies_get_country_currency($user_country);
	if($currency <> $expected_currency){
		$html .= '<p class="pmt-curr-switch-msg bg-warning"><i class="fa-solid fa-circle-info"></i> You appear to be in '.ccpa_countries_name($user_country).' but have selected '.ccpa_pretty_currency($currency).' as your payment currency. You can change this here:</p>';
	}
	$html .= '<label for="reg-chg-curr" class="form-label">Select desired currency</label><select id="reg-chg-curr" class="form-select form-select-lg">';
	foreach (cc_valid_currencies() as $curr) {
		$html .= '<option value="'.$curr.'" '.selected($curr, $currency, false).'>'.ccpa_pretty_currency($curr).'</option>';
	}
	$html .= '</select><p class="small mt-3">Note: Prices will be shown in GB Pounds if training is not available in your selected currency.</p></div></div>';
	return $html;
}

// the vat exemption panel
function cc_registration_vat_panel($user_country){
	if($user_country == 'GB'){
		$vat_uk_selected = ' selected="selected"';
		$vat_uk_not_selected = '';
		$vat_exempt = 'n';
		$vat_msg_text = 'VAT is applied at the standard rate';
		$vat_msg_class = '';
	}else{
		$vat_uk_selected = '';
		$vat_uk_not_selected = ' selected="selected"';
		$vat_exempt = 'n';
		$vat_msg_text = 'Incomplete details: VAT is applied at the standard rate';
		$vat_msg_class = ' text-danger';
	}
	$html = '
		<div class="animated-card">
			<div id="reg-vat-panel" class="reg-vat-panel reg-panel wms-background animated-card-inner pale-bg">
				<h2>VAT exemption:</h2>
				<p>Please answer the following questions to determine if you need to pay UK Value Added Tax (VAT)</p>
				<div id="vat-uk-wrap" class="mb-3">
					<label for="vat-uk" class="form-label">Are you based in the UK?</label>
					<select name="vat-uk" id="vat-uk" class="form-select form-select-lg vat-field">
						<option value="y"'.$vat_uk_selected.'>Yes</option>
						<option value="n"'.$vat_uk_not_selected.'>No</option>
					</select>
				</div>
				<div id="vat-not-uk-wrap" class="vat-not-uk-wrap">
					<h4>Not based in the UK:</h4>
					<p>To be exempt from UK VAT, please confirm the following:</p>
					<div class="mb-3">
						<label for="vat-employ" class="form-label">I am a self-employed professional or am undertaking this course for the benefit of my employment:</label>
						<select name="vat-employ" id="vat-employ" class="form-select form-select-lg vat-field">
							<option value="employ">Yes</option>
							<option value="not">No</option>
						</select>
					</div>
					<div id="vat-employer-wrap" class="vat-employer-wrap">
						<div class="mb-3">
							<label for="vat-employer" class="form-label">Name of your self-employed business or employer:</label>
							<input type="text" class="form-control form-control-lg vat-employ-dets vat-field" name="vat-employer" id="vat-employer">
						</div>
					</div>
				</div>
				<p id="vat-msg" class="vat-msg'.$vat_msg_class.'">'.$vat_msg_text.'</p>
			</div>
		</div>';
	return $html;
}

// update pricing from the payment details page
add_action('wp_ajax_reg_update_pricing', 'cc_registration_reg_update_pricing');
add_action('wp_ajax_nopriv_reg_update_pricing', 'cc_registration_reg_update_pricing');
function cc_registration_reg_update_pricing(){
    $response = array(
        'status' => 'error',
        'currency' => '',
        'icon' => '',
        'price_html' => '',
        'raw_price' => 0,
        'disc_amount' => 0,
        'voucher_amount' => 0,
        'vat' => 0,
        'tot_pay' => 0,
        'upsell_html' => '',
        'total_payable' => 0,
    );

    // ccpa_write_log( array_merge( [ 'function' => 'cc_registration_reg_update_pricing' ],  $_POST ) );

    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
	$registration_data = TempRegistration::get($token);
	$form_data = json_decode($registration_data->form_data, true);

	// ccpa_write_log( array_merge( ['what' => 'form_data'], $form_data ) );

    if( $registration_data === NULL || $form_data['training_type'] == '' || $form_data['training_id'] == 0 ){
    	$response['msg'] = 'E9762 '.$form_data['training_type'].'-'.$form_data['training_id'];
        wp_send_json($response);
        return;
    }

    // get the updated data:
    $currency = isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies()) ? $_POST['currency'] : '';
    $vatExempt = isset($_POST['vatExempt']) && in_array($_POST['vatExempt'], ['y', 'n']) ? $_POST['vatExempt'] : '';
    $promo = isset($_POST['promo']) ? stripslashes(sanitize_text_field($_POST['promo'])) : '';
    $voucher = isset($_POST['voucher']) ? stripslashes(sanitize_text_field($_POST['voucher'])) : '';
    $upsell_workshop_id = isset($_POST['upsell']) ? absint($_POST['upsell']) : 0;
    $vat_uk = isset( $_POST['vatUK'] ) ? stripslashes( sanitize_text_field( $_POST['vatUK'] ) ) : 'y';
    $vat_employ = isset( $_POST['vatEmploy'] ) ? stripslashes( sanitize_text_field( $_POST['vatEmploy'] ) ) : '';
    $vat_employer = isset( $_POST['vatEmployer'] ) ? stripslashes( sanitize_text_field( $_POST['vatEmployer'] ) ) : '';

    /*
    // Get existing variables (keeping your current code)
    // $ccreg = isset($_POST['ccreg']) ? stripslashes(sanitize_text_field($_POST['ccreg'])) : '';
    $trainingType = '';
    if(isset($_POST['trainingType']) && in_array($_POST['trainingType'], ['w', 'r', 's', 'g'])){
        $trainingType = $_POST['trainingType'];
    }
    $training_id = isset($_POST['trainingID']) ? absint($_POST['trainingID']) : 0;
    $eventID = isset($_POST['eventID']) ? stripslashes(sanitize_text_field($_POST['eventID'])) : '';
    $student = isset($_POST['student']) && $_POST['student'] == 'yes' ? 'yes' : 'no';

    // NEW: Handle group training
    $group_training = array();
    if($trainingType == 'g' && isset($_POST['group_training'])){
        $group_training = array_map('absint', (array) $_POST['group_training']);
        $group_training = array_filter($group_training);
        $group_training = array_unique($group_training);
    }


    // Validate required fields  
    if($trainingType == '' || ($trainingType != 'g' && $training_id == 0) || $currency == '' || $vatExempt == '') {
        wp_send_json($response);
        return;
    }
    
    // For group training, ensure we have courses selected
    if($trainingType == 'g' && empty($group_training)) {
        wp_send_json($response);
        return;
    }

    // Get attendees from temp registration if we have a token
    $attendees = array();
    if (!empty($token)) {
        $temp_reg = TempRegistration::get($token);
        if ($temp_reg) {
            $form_data = json_decode($temp_reg->form_data, true);
            if (isset($form_data['attendees']) && is_array($form_data['attendees'])) {
                $attendees = $form_data['attendees'];
            }
        }
    }
    
    // Default to 1 attendee if none found
    if (empty($attendees)) {
        $attendees = array(array('registrant' => 'r'));
    }


    $response = cc_registration_reg_update_pricing_core( $trainingType, $training_id, $eventID, $currency, $vatExempt, $promo, $voucher, $student, $upsell_workshop_id, $group_training, $token, $attendees );

    wp_send_json($response);
}


function cc_registration_reg_update_pricing_core( $trainingType, $training_id, $eventID, $currency, $vatExempt, $promo, $voucher, $student, $upsell_workshop_id, $group_training, $token, $attendees ){
    $response = array(
        'status' => 'ok',
        'currency' => '',
        'icon' => '',
        'price_html' => '',
        'raw_price' => 0,
        'disc_amount' => 0,
        'voucher_amount' => 0,
        'vat' => 0,
        'tot_pay' => 0,
        'upsell_html' => '',
        'total_payable' => 0,
    );

    */

    $response['status'] = 'ok';

    // to save me having to change the following code .....
    $trainingType = $form_data['training_type'];
    $training_id = $form_data['training_id'];
    $eventID = $form_data['event_id'];
    $student = $form_data['student'];
    $group_training = $form_data['group_training'];
    $attendees = $form_data['attendees'];

    $discount = false;
    $earlybird_name = false;
    $raw_price = 0;
    $disc_amount = 0;
    $voucher_amount = 0;
    $vat = 0;
    $currency_lc = strtolower($currency);

    // Handle promo/discount codes (not applicable to group training)
    if($trainingType != 'g' && $promo != ''){
        if(substr($promo, 0, 3) == 'CC-'){
            // refer a friend code
            if(cc_friend_can_i_use_it($promo, get_current_user_id())){
                $discount = array(
                    'disc_type' => 'p',
                    'disc_amount' => get_option('refer_friend_percent', 0),
                );
            }
        } else {
            $discount = cc_discount_lookup($promo, $trainingType, $training_id);
        }
    }
    // ccpa_write_log( array_merge( [ 'step' => 'discount found' ], (array) $discount ) );

    // Calculate pricing based on training type
    if($trainingType == 'g' && !empty($form_data['group_training'])){
		$now = time();
		// add it up
		$vat = 0;
		$training_price = 0;
		$discounts = array();
		$total_discount = 0;
		$payable = 0;
        
        foreach($form_data['group_training'] as $grp_train_id){
			$pricing = get_training_prices( $grp_train_id );
			// ccpa_write_log( $pricing );
			$training_price += $pricing['price_'.$currency_lc];
			// student discounts are ignored
			if( $pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] <> '' ){
				$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'] );
				if( $expiry_date ){
					if( $expiry_date->getTimestamp() > $now ){
						$early_bird_name = ( $pricing['early_bird_name'] == '' ) ? 'Early bird' : $pricing['early_bird_name'];
						$discount_amount = round( $pricing['price_'.$currency_lc] * $pricing['early_bird_discount'] / 100, 2 );
						$total_discount += $discount_amount;
						if( isset( $discounts[$early_bird_name] ) ){
							$discounts[$early_bird_name] += $discount_amount;
						}else{
							$discounts[$early_bird_name] = $discount_amount;
						}
					}
				}
			}
        }

        // Set values for group training
        $raw_price = $training_price;
        $disc_amount = $total_discount;
        
        // Calculate VAT on discounted price
        $discounted_price = $training_price - $total_discount;
        $vat = ($vatExempt == 'y') ? 0 : ccpa_vat_amount($discounted_price);

    } else {
        // SINGLE TRAINING PRICING (workshop, recording, series)
        $pricing = false;
        
        if($trainingType == 'w'){
            // Workshop - returns the raw_price as the student price where appropriate
            // $pricing = cc_workshop_price_exact($training_id, $currency, $eventID, $student);
            // changed to get all the price details ...
            $pricing = cc_workshop_price( $training_id, $currency );
            /*
			$response = array(
				'price_text' => '',
				'curr_found' => '',
				'raw_price' => 0,
				'student_price' => null,
				'student_price_formatted' => '',
				'earlybird_msg' => '',
				'non_early_price' => null,
			);
			*/
        } elseif($trainingType == 's'){
            // Series
            $series_pricing = series_pricing($training_id, $currency);
            if($series_pricing){
                // NOTE: $series_pricing['price'] is before the discount is applied!
                $pricing = array(
                    'raw_price' => $series_pricing['price'],
                    'curr_found' => $currency,
                    'student_price' => NULL,
                );
                // Series has its own discount/saving
                $disc_amount = $series_pricing['saving'];
            }
        } else {
            // Recording - returns the student price separately to the raw_price
            $pricing = cc_recording_price($training_id, $currency);
        }
        
        if(!$pricing){
            return $response;
        }

        $raw_price = $pricing['raw_price'];
        $currency = $pricing['curr_found'];

        // Apply student pricing
        if($student == 'yes' && isset($pricing['student_price']) && $pricing['student_price'] !== NULL){
            $raw_price = $pricing['student_price'];
        }

        // Add upsell pricing calculation (not applicable to series or group training)
        if($trainingType != 's' && $trainingType != 'g' && $upsell_workshop_id > 0){
            $upsell = cc_upsells_get_upsell($training_id);
            if($upsell){
            	$other_training_type = course_training_type($upsell_workshop_id);
                // Get pricing for the upsell workshop
                if( $other_training_type == 'workshop'){
                    $other_training_pricing = cc_workshop_price_exact($upsell_workshop_id, $currency, '', $student);
                } else {
                    $other_training_pricing = cc_recording_price($upsell_workshop_id, $currency);
                }
                
                if($other_training_pricing){
                	$upsell_raw_price = $other_training_pricing['raw_price'];

			        // Apply student pricing for recordings
			        if($student == 'yes' && $other_training_type == 'recording' && isset($other_training_pricing['student_price']) && $other_training_pricing['student_price'] !== NULL){
			            $upsell_raw_price = $other_training_pricing['student_price'];
			        }

                    // Add upsell price and apply discount
                    $combined_price = $raw_price + $upsell_raw_price;
                    $raw_price = round($combined_price - ($combined_price * $upsell['discount'] / 100), 2);
                }
            }
        }

        // Check for early bird discount (for single trainings)
        $earlybird = cc_workshop_price_earlybird($training_id);
        if($earlybird){
            $earlybird_name = get_post_meta($training_id, 'earlybird_name', true);
            if($earlybird_name == ''){
                $earlybird_name = 'Early-bird';
            }
        }

        // Apply discount if present (not for series which has its own discount)
        if($trainingType != 's' && $discount){
            if($discount['disc_type'] == 'a'){
                // Amount discount
                $disc_amount = min($discount['disc_amount'], $raw_price);
            } elseif($discount['disc_type'] == 'p'){
                // Percentage discount
                $disc_amount = round($raw_price * $discount['disc_amount'] / 100, 2);
            }
        }

        // series discount
        if( $trainingType == 's' ){
            $disc_amount = $series_pricing['saving'];
        }

        // Calculate VAT
        if($vatExempt == 'n'){
            $vat = ccpa_vat_amount($raw_price - $disc_amount);
        }

    }

    // ccpa_write_log([ 'step' => 'after pricing', 'raw_price' => $raw_price, 'disc_amount' => $disc_amount, 'vat' => $vat ]);

    // Calculate per attendee total
    $per_attendee = $raw_price - $disc_amount + $vat;
    
    // Calculate total for all attendees
    $tot_payable = $per_attendee * count($attendees);

	// payment by voucher?
	if($voucher <> ''){
		$voucher_bal = 0;
		// is it a refer a friend code
		if( substr( $voucher, 0, 3 ) == 'CC-' ){
			// is it their code
			if( $voucher == cc_friend_user_code( get_current_user_id() ) ){
				$usage = cc_friend_get_usage( 'raf_code', $voucher );
				// any balance on it?
				if( $usage['balance'] > 0 ){
					// convert to required currency
					// returns in number format eg 1,234.56 ... worked so far for numbers under 1,000
					$voucher_bal = cc_voucher_core_curr_convert($usage['currency'], $currency, $usage['balance']);
					// convert it back to a normal number ...
					$voucher_bal = (float) str_replace(',', '', $voucher_bal);
				}
			}
		}else{
			// voucher
			$raw_voucher_code = cc_voucher_code_raw( $voucher );
			$voucher_dets = ccpa_voucher_table_get( $raw_voucher_code );
			if($voucher_dets){
				$voucher_bal = ccpa_voucher_usage_balance($voucher_dets, $currency);
				$voucher_bal = (float) str_replace(',', '', $voucher_bal);
			}
		}
		if( $voucher_bal > 0 ){
			if($voucher_bal > $tot_payable){
				$voucher_amount = $tot_payable;
			}else{
				$voucher_amount = $voucher_bal;
			}
		}
	}

	/*
	ccpa_write_log([
		'function' => 'cc_registration_reg_update_pricing_core',
		'step' => 'after voucher calcs',
		'voucher' => $voucher,
		'voucher_amount' => $voucher_amount,
		'voucher_bal' => $voucher_bal,
		'currency' => $currency,
		'tot_payable' => $tot_payable,
		'voucher_dets_voucher_code' => $voucher_dets['voucher_code'],
		'voucher_dets_issue_time' => $voucher_dets['issue_time'],
		'voucher_dets_expiry_time' => $voucher_dets['expiry_time'],
		'voucher_dets_currency' => $voucher_dets['currency'],
		'voucher_dets_amount' => $voucher_dets['amount'],
		'voucher_dets_redeemed' => $voucher_dets['redeemed'],
		'voucher_dets_balance' => $voucher_dets['balance'],
	]);
	*/

    $tot_payable = $tot_payable - $voucher_amount;

    // Prepare response
    $response['currency'] = $currency;
    $response['icon'] = cc_currencies_icon($currency);
    $response['raw_price'] = $raw_price;
    $response['disc_amount'] = $disc_amount;
    $response['voucher_amount'] = $voucher_amount;
    $response['vat'] = $vat;
    $response['tot_pay'] = $per_attendee;
    $response['total_payable'] = round($tot_payable, 2);

    // update the temp registration
    // NOTE we ARE updating VAT fields at this stage ... also in reg_step_two (in case the values change)
    $update_data = array();
    $update_data['currency'] = $currency;
    if( $trainingType == 's' ){
    	$update_data['raw_price'] = $raw_price - $series_pricing['saving'];
    	$update_data['series_discount'] = $series_pricing['saving'];
    }else{
    	$update_data['raw_price'] = $pricing['raw_price'];
    	$update_data['series_discount'] = 0;
    }
    if( $trainingType == 'g' || ! isset( $pricing['student_price'] ) ){
    	$update_data['student_price'] = 0;
    }else{
    	$update_data['student_price'] = $pricing['student_price'];
    }
    $update_data['vat_uk'] = $vat_uk;
    $update_data['vat_employ'] = $vat_employ;
    $update_data['vat_employer'] = $vat_employer;
    $update_data['vat_exempt'] = $vatExempt;

    $update_data['disc_code'] = $promo;
    $update_data['disc_amount'] = $disc_amount;
    $update_data['voucher_code'] = $voucher;
    $update_data['voucher_amount'] = $voucher_amount;

    if( $upsell_workshop_id > 0 ){
    	$update_data['upsell_workshop_id'] = $upsell_workshop_id;
    }

    $success = TempRegistration::update_form_data($token, 2, $update_data);

    /*
    ccpa_write_log([
    	'currency' => $currency,
    	'raw_price' => $raw_price,
    	'disc_amount' => $disc_amount,
    	'voucher_amount' => $voucher_amount,
    	'vat' => $vat,
    	'per_attendee' => $per_attendee,
    	'tot_payable' => $tot_payable,
    	'training_price' => ( ( $trainingType == 'g' ) ? $training_price : 'not a group' ),
    	'discounts' => ( ( $trainingType == 'g' ) ? $discounts : 'not a group' ),
    	'attendees' => $attendees,
    	'trainingType' => $trainingType,
    	'earlybird_name' => $earlybird_name,
    ]);
    */

    // Generate price HTML
    if($trainingType == 'g'){
        // For group training, pass the discounts array instead of a single discount amount
        $response['price_html'] = wms_tidy_html( cc_registration_training_price(
            $training_price,  // Use training_price (before discount) for group
            $currency,
            $discounts,       // Pass array of discounts for group training
            $vat,
            $voucher_amount,
            'no',            // No student discount for groups
            '',              // No single earlybird name for groups
            '',              // portal user
            count($attendees),
            $trainingType
        ) );
    } else {
        // For other training types, use standard parameters
        $response['price_html'] = wms_tidy_html( cc_registration_training_price(
            $raw_price,			// raw_price
            $currency,			// currency
            $disc_amount,		// discount
            $vat,				// vat
            $voucher_amount,	// voucher
            $student,			// student
            $earlybird_name,	// earybird_name
            '', 				// portal user
            count($attendees),	// num_attendees
            $trainingType 		// training_type
        ) );
    }

    // Generate upsell HTML if applicable (not for groups or series)
    if($trainingType != 'g' && $trainingType != 's' && $student != 'yes' && ($trainingType == 'w' || $trainingType == 'r')){
        $upsell_sel = ($upsell_workshop_id > 0);
        $response['upsell_html'] = wms_tidy_html( cc_registration_upsell_panel(
        	'1', 
        	$training_id, 
        	$currency, 
        	cc_timezone_get_user_timezone(), 
        	true, 
        	$upsell_sel
        ) );
    }
    
    wp_send_json($response);
}


/* was .......... (delete this)
function cc_registration_reg_update_pricing(){
	// ccpa_write_log('function cc_registration_reg_update_pricing');
	$response = array(
		'status' => 'error',
		'currency' => '',
		'icon' => '',
		'price_html' => '',
		'raw_price' => 0,
		'disc_amount' => 0,
		'voucher_amount' => 0,
		'vat' => 0,
		'tot_pay' => 0, // per attendee
		'upsell_html' => '',
		'total_payable' => 0, // all attendees
	);
	$raw_price = $disc_amount = $voucher_amount = $vat = $tot_payable = $tot_pay = $upsell_workshop_id = $tot_payable = 0;
	$student = 'no';
	$earlybird = false;
	$ccreg = '';
	if(isset($_POST['ccreg'])){
		$ccreg = stripslashes( sanitize_text_field( $_POST['ccreg'] ) );
	}
	$trainingType = '';
	if(isset($_POST['trainingType']) && ( $_POST['trainingType'] == 'w' || $_POST['trainingType'] == 'r' || $_POST['trainingType'] == 's' )){
		$trainingType = $_POST['trainingType'];
	}
	$training_id = '';
	if(isset($_POST['trainingID'])){
		$training_id = absint( $_POST['trainingID'] );
	}
	$eventID = '';
	if(isset($_POST['eventID'])){
		$eventID = stripslashes( sanitize_text_field( $_POST['eventID'] ) );
	}
	$currency = '';
	if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies())){
		$currency = $_POST['currency'];
	}
	$vatExempt = '';
	if(isset($_POST['vatExempt']) && ( $_POST['vatExempt'] == 'y' || $_POST['vatExempt'] == 'n' )){
		$vatExempt = $_POST['vatExempt'];
	}
	$promo = '';
	if(isset($_POST['promo'])){
		$promo = stripslashes( sanitize_text_field( $_POST['promo'] ) );
	}
	$voucher = '';
	if(isset($_POST['voucher'])){
		$voucher = stripslashes( sanitize_text_field( $_POST['voucher'] ) );
	}
	if(isset($_POST['student']) && $_POST['student'] == 'yes'){
	    $student = 'yes';
	}
	if(isset($_POST['upsell'])){
		$upsell_workshop_id = absint( $_POST['upsell'] );
	}
	if($trainingType <> '' && $training_id > 0 && $currency <> '' && $vatExempt <> '' && $ccreg <> ''){
		$response['status'] = 'ok';
		$discount = false;
		$earlybird_name = false;
		if($promo <> ''){
			if( substr( $promo, 0, 3) == 'CC-' ){
				// refer a friend code
				if( cc_friend_can_i_use_it( $promo, get_current_user_id() ) ){
					$discount = array(
						'disc_type' => 'p',
						'disc_amount' => get_option('refer_friend_percent', 0),
					);
				}
			}else{
				$discount = cc_discount_lookup($promo, $trainingType, $training_id);
			}
		}
		if($trainingType == 'w'){
			// this will return the raw_price as the student price where appropriate
			$pricing = cc_workshop_price_exact($training_id, $currency, $eventID, $student);
		}elseif( $trainingType == 's' ){
			$pricing = false;
			$series_pricing = series_pricing( $training_id, $currency );
			if( $series_pricing ){
				// NOTE: $series_pricing['price'] is before the discount is applied!
				$pricing = array(
					'raw_price' => $series_pricing['price'],
					'curr_found' => $currency,
					'student_price' => NULL,
				);
			}
		}else{
			// this will return the student price separately to the raw_price to be used if appropriate
			$pricing = cc_recording_price($training_id, $currency);
		}
		// $response['pricing'] = print_r($pricing, true);
		if($pricing){
			$raw_price = $pricing['raw_price'];
			// $response['debug'] = 'raw:'.$raw_price;
			$currency = $pricing['curr_found'];
			// if there's an upsell, we need to add that in now
			if($upsell_workshop_id > 0){
				$upsell = cc_upsells_get_upsell($training_id);
				if( course_training_type( $upsell_workshop_id ) == 'workshop' ){
					$other_training_pricing = cc_workshop_price_exact($upsell_workshop_id, $currency, '', $student);
				}else{
					$other_training_pricing = cc_recording_price( $upsell_workshop_id, $currency, '' );
				}
				$raw_price = $raw_price + $other_training_pricing['raw_price'];
				$raw_price = round( $raw_price - ( $raw_price * $upsell['discount'] / 100 ), 2 );
			}
			if( $student == 'yes' && $trainingType == 'r' && $pricing['student_price'] !== NULL ){
				// student price request
				// for recordings, we need to set that now. Workshops are set earlier by cc_workshop_price_exact
				$raw_price = $pricing['student_price'];
			}
			$earlybird = cc_workshop_price_earlybird($training_id);
			if($earlybird){
				$earlybird_name = get_post_meta($training_id, 'earlybird_name', true);
				if($earlybird_name == ''){
					$earlybird_name = 'Early-bird';
				}
				// for a recording, do we need to set the raw_price to be the earlybird price now?????????
			}
			// if there's a discount (promo code) then now's the time to apply it
			if($discount){
				if($discount['disc_type'] == 'a'){
					// amount
					if($discount['disc_amount'] > $raw_price){
						$disc_amount = $raw_price;
					}else{
						$disc_amount = $discount['disc_amount'];
					}
				}elseif($discount['disc_type'] == 'p'){
					$disc_amount = round($raw_price * $discount['disc_amount'] * 0.01, 2);
				}
			}elseif( $trainingType == 's' ){
				$disc_amount = $series_pricing['saving'];
			}
			// now VAT
			$vat = 0;
			if($vatExempt == 'n'){
				$vat = ccpa_vat_amount($raw_price - $disc_amount);
			}
			// $response['debug'] .= ' vat:'.$vat;
			// $response['debug'] .= ' disc:'.$disc_amount;
			$per_attendee = $raw_price - $disc_amount + $vat;
			// $response['debug'] .= ' per:'.$per_attendee;
			$attendees = cc_attendees_reg_get($ccreg);
			// ccpa_write_log($attendees);
			if(count($attendees) > 1){
				$tot_payable = $per_attendee * count($attendees);
			}else{
				$tot_payable = $per_attendee;
			}
			// $response['debug'] .= ' tot:'.$tot_payable;
			// payment by voucher?
			if($voucher <> ''){
				$voucher_bal = 0;
				// is it a refer a friend code
				if( substr( $voucher, 0, 3 ) == 'CC-' ){
					// is it their code
					if( $voucher == cc_friend_user_code( get_current_user_id() ) ){
						$usage = cc_friend_get_usage( 'raf_code', $voucher );
						// any balance on it?
						if( $usage['balance'] > 0 ){
							// convert to required currency
							$voucher_bal = cc_voucher_core_curr_convert($usage['currency'], $currency, $usage['balance']);
						}
					}
				}else{
					// voucher
					$voucher_dets = ccpa_voucher_table_get( $voucher );
					if($voucher_dets){
						$voucher_bal = ccpa_voucher_usage_balance($voucher_dets, $currency);
					}
				}
				if( $voucher_bal > 0 ){
					if($voucher_bal > $tot_payable){
						$voucher_amount = $tot_payable;
					}else{
						$voucher_amount = $voucher_bal;
					}
				}
			}
			// $response['debug'] .= ' voucher:'.$voucher_amount;
			$tot_payable = $tot_payable - $voucher_amount;
			// $response['debug'] .= ' tot2:'.$tot_payable;
			if($upsell_workshop_id > 0){
				$upsell_sel = true;
			}else{
				$upsell_sel = false;
			}
			if($student <> 'yes'){
				$response['upsell_html'] = wms_tidy_html( cc_registration_upsell_panel('1', $training_id, $currency, cc_timezone_get_user_timezone(), true, $upsell_sel) );
			}
		}
		$response['currency'] = $currency;
		$response['icon'] = cc_currencies_icon($currency);
		$response['raw_price'] = $raw_price;
		$response['disc_amount'] = $disc_amount;
		$response['voucher_amount'] = $voucher_amount;
		$response['vat'] = $vat;
		$response['tot_pay'] = $per_attendee;
		$response['total_payable'] = round($tot_payable,2);
		// cc_registration_training_price($raw_price, $currency, $discount, $vat, $voucher, $student, $earlybird_name, $portal_user='', $num_attends=1, $training_type='')
		$response['price_html'] = wms_tidy_html( cc_registration_training_price($raw_price, $currency, $disc_amount, $vat, $voucher_amount, $student, $earlybird_name, '', count($attendees), $trainingType ) );
	}

	echo json_encode($response);
	die;
}
*/

// returns the upsell panel
// upsells not offered if multi-event or if student discount or earlybird applies
// $price_upd just returns the prices for an ajax update (eg with a currency change on registration)
function cc_registration_upsell_panel($panel_num, $training_1_id, $currency, $user_timezone, $price_upd=false, $upsell_sel=false){
	// ccpa_write_log('function cc_registration_upsell_panel');
	/*
	if(workshop_is_multi_event($training_1_id)){
		return '';
	}
	*/
	/*
	$student_discount = (float) get_post_meta($training_1_id, 'student_discount', true);
	if($student_discount > 0){
		return '';
	}
	if(cc_workshop_price_earlybird($training_1_id)){
		// return '';
	}
	*/
	$upsell = cc_upsells_get_upsell($training_1_id);
	if($upsell === NULL) return '';
	if($training_1_id == $upsell['workshop_1_id']){
		$training_2_id = $upsell['workshop_2_id'];
	}else{
		$training_2_id = $upsell['workshop_1_id'];
	}

	if( workshop_is_multi_event( $training_1_id ) || workshop_is_multi_event( $training_2_id ) ){
		return '';
	}
	/*
	$student_discount = (float) get_post_meta($training_2_id, 'student_discount', true);
	if($student_discount > 0){
		return '';
	}
	if(cc_workshop_price_earlybird($training_2_id)){
		// return '';
	}
	*/

	if($price_upd){
		$html = '';
	}else{
		$html = '<div class="animated-card upsell-panel-wrap"><div id="reg-upsell-panel" class="reg-upsell-panel reg-panel wms-background animated-card-inner pale-bg">';
	}
	$discount =  (float) $upsell['discount'];
	if(!$price_upd){
		$html .= '<h2>Want to save '.$discount.'%?</h2>';
	}
	$workshop_1_title = get_the_title($training_1_id);
	$workshop_2_title = get_the_title($training_2_id);

	// dates
	if( course_training_type( $training_2_id ) == 'workshop' ){
		$pretty_dates = workshop_calculated_prettydates($training_2_id, $user_timezone);
		$training_2_date = '';
		if($pretty_dates['locale_date'] <> ''){
			$training_2_date = ': '.$pretty_dates['locale_date'];
		}
	}else{
		$training_2_date = '';
	}

	// prices
	if( course_training_type( $training_1_id ) == 'workshop' ){
		$workshop_prices = cc_workshop_price_exact( $training_1_id, $currency, '', '' );
		$training_1_raw_price = $workshop_prices['raw_price'];
		$training_1_price = cc_money_format( $workshop_prices['raw_price'], $workshop_prices['curr_found'] );
		$currency = $workshop_prices['curr_found'];
	}else{
		$recording_prices = cc_recording_price( $training_1_id, $currency, '' );
		// ccpa_write_log('recording prices for training 1 ('.$training_1_id.')');
		// ccpa_write_log($recording_prices);
		$training_1_raw_price = $recording_prices['raw_price'];
		$training_1_price = cc_money_format( $recording_prices['raw_price'], $recording_prices['curr_found'] );
		$currency = $recording_prices['curr_found'];
	}

	if( course_training_type( $training_2_id ) == 'workshop' ){
		$workshop_prices = cc_workshop_price_exact( $training_2_id, $currency, '', '' );
		if( $workshop_prices['curr_found'] <> $currency ) return '';
		$training_2_raw_price = $workshop_prices['raw_price'];
		$training_2_price = cc_money_format( $workshop_prices['raw_price'], $workshop_prices['curr_found'] );
	}else{
		$recording_prices = cc_recording_price( $training_2_id, $currency, '' );
		// ccpa_write_log('recording prices for training 1 ('.$training_2_id.')');
		// ccpa_write_log($recording_prices);
		if( $recording_prices['curr_found'] <> $currency ) return '';
		$training_2_raw_price = $recording_prices['raw_price'];
		$training_2_price = cc_money_format( $recording_prices['raw_price'], $recording_prices['curr_found'] );
	}

	$discounted_price = cc_money_format( $training_1_raw_price + $training_2_raw_price - ( ( $training_1_raw_price + $training_2_raw_price ) * $discount / 100), $currency );		

	if($upsell_sel){
		$check_1 = '';
		$check_2 = 'checked';
	}else{
		$check_1 = 'checked';
		$check_2 = '';
	}
	if(!$price_upd){
		$html .= '<p>Also register for <strong>'.$workshop_2_title.'</strong> and save '.$discount.'%.</p><div id="reg-upsell-panel-price-wrap">';
	}
	$html .= '
		<div class="form-check">
			<input type="radio" class="radio-awesome upsell-switch" name="upsell-chooser" id="upsell-no" autocomplete="off" value="no" '.$check_1.'>
			<label class="form-check-label" for="upsell-no">Register for <strong>'.$workshop_1_title.'</strong> for <span class="from-price">'.$training_1_price.'</span> + VAT</label>
		</div>
		<div class="form-check">
			<input type="radio" class="radio-awesome upsell-switch" name="upsell-chooser" id="upsell-yes" autocomplete="off" value="yes" '.$check_2.' data-workshopID="'.$training_2_id.'">
			<label class="form-check-label" for="upsell-yes">Also register for <strong>'.$workshop_2_title.'</strong>'.$training_2_date.' (normally <span class="upsell-std">'.$training_2_price.'</span> excl. VAT). Get '.$discount.'% discount for booking both training events. Total price only <span class="upsell-tot">'.$discounted_price.'</span> + VAT.</label>
		</div>';
	if(!$price_upd){
		$html .= '</div>';
		$html .= cc_wksp_upsell_card($training_2_id);
		$html .= '</div></div>';
	}
	return $html;
}


// the gift voucher and promotional code panel
function cc_registration_voucher_panel($panel_num, $training_type, $training_id){
	$show_voucher = cc_discounts_possible_discount($training_type, $training_id);
	$html = '<div id="voucher-panel-wrap" class="animated-card voucher-panel-wrap">
				<div id="reg-voucher-panel" class="reg-voucher-panel reg-panel wms-background animated-card-inner pale-bg">';
	if($show_voucher){
		$html .= 	'<h2>Promotional code/gift voucher:</h2>
					<div class="reg-promo-wrap">
						<div class="form-check form-switch mb-3">
							<input id="promo-switch" class="form-check-input" type="checkbox" role="switch" value="yes" id="reg-promo-check">
							<label class="form-check-label" for="reg-promo-check">I want to use a promotional code</label>
						</div>
						<div id="reg-promo-code-wrap" class="reg-promo-code-wrap">
							<div class="row mb-3">
								<div class="col-md-8 col-lg-9 col-xl-10">
									<label for="promo-code" class="form-label">Enter your promotional code:</label>
									<input type="text" class="form-control form-control-lg" id="promo-code">
									<div id="promo-help" class="form-text"></div>
								</div>
								<div class="col-md-4 col-lg-3 col-xl-2 text-end">
									<a href="javascript:void(0)" id="promo-code-apply" class="btn btn-primary btn-field">Apply</a>
								</div>
							</div>
						</div>
					</div>';
	}else{
		$html .= 	'<h2>3. Gift voucher:</h2>';
	}
	$html .= '		<div class="reg-voucher-wrap">
						<div class="form-check form-switch mb-3">
							<input id="voucher-switch" class="form-check-input" type="checkbox" role="switch" value="yes" id="reg-voucher-check">
							<label class="form-check-label" for="reg-voucher-check">I want to pay by gift voucher</label>
						</div>
						<div id="reg-voucher-code-wrap" class="reg-voucher-code-wrap">
							<div class="row mb-3">
								<div class="col-md-8 col-lg-9 col-xl-10">
									<label for="voucher-code" class="form-label">Enter your gift voucher code:</label>
									<input type="text" class="form-control form-control-lg" id="voucher-code">
									<div id="voucher-help" class="form-text"></div>
								</div>
								<div class="col-md-4 col-lg-3 col-xl-2 text-end">
									<a href="javascript:void(0)" id="voucher-code-apply" class="btn btn-primary btn-field">Apply</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
	return $html;
}

// the registration "btn" html
function cc_registration_btn_html($training_type, $training_id, $currency, $raw_price, $user_timezone, $user_prettytime, $btn_class=''){
	// is it past the last date/time for sale?
    $now = time();
    $last_datetime_for_sale = get_post_meta($training_id, 'last_datetime_for_sale', true); // Y-m-d H:i (UTC!)
    if($last_datetime_for_sale <> ''){
    	$datetime = DateTime::createFromFormat("Y-m-d H:i", $last_datetime_for_sale, new DateTimeZone('UTC'));
    	if($datetime){
    		if($datetime->getTimestamp() < $now){
				return '';
    		}
    	}
    }

	if($training_type == 'w'){
		if(workshop_is_multi_event($training_id)){
			// we need to send them to the anchor on the workshop page so they can select the events they want
			$html = '<a href="'.get_permalink($training_id).'#register" class="btn btn-training btn-lge">Register now</a>';
		}else{
			// go straight to the registration page
			$html = '
				<form action="/registration" method="post" class="reg-btn-form">
					<input type="hidden" name="training-type" value="w">
					<input type="hidden" name="workshop-id" value="'.$training_id.'">
					<input type="hidden" class="eventID" name="eventID" value="1">
					<input type="hidden" class="num-events" name="num-events" value="1">
					<input type="hidden" class="num-free" name="num-free" value="0">
					<input type="hidden" class="currency" name="currency" value="'.$currency.'">
					<input type="hidden" class="raw-price" name="raw-price" value="'.$raw_price.'">
					<input type="hidden" class="user-timezone" name="user-timezone" value="'.$user_timezone.'">
					<input type="hidden" class="user-prettytime" name="user-prettytime" value="'.$user_prettytime.'">
					<button class="btn btn-training btn-lge '.$btn_class.'">Register now</button>
				</form>';
		}
	}else{





	}
	return $html;
}

// Portal registration (CNWL/NLFT)
add_action('init', 'cc_registration_org_completed');
function cc_registration_org_completed(){
	if(!isset($_POST['reg-org-submit'])) return;

    // Get token from form instead of ccreg
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    if(empty($token)) {
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_org_completed',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'No token supplied',
    	]);
        wp_redirect(home_url());
        exit;
    }

    // Get or create the user from temp registration
    $user_id = cc_create_user_from_temp_registration($token);

    if( ! $user_id ){
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_org_completed',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'Could not create user',
    	]);
        wp_redirect(home_url());
        exit;
    }

    // check we have a portal user
	$portal_user = get_user_meta( $user_id, 'portal_user', true );
	if( $portal_user == '' ){
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_org_completed',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'Not a portal user',
    	]);
        wp_redirect(home_url());
        exit;
	}

    // reg user info
    $user = get_user_by( 'id', $user_id );
    $email = $user->user_email;

	// capture the new info into the temp registration system
	$new_info = array();
	if( isset( $_POST['source'] ) && $_POST['source'] <> '' ){
		$new_info['source'] = stripslashes( sanitize_text_field( $_POST['source'] ) );
	}
	if( isset( $_POST['conf_email'] ) && $_POST['conf_email'] <> '' ){
		$new_info['conf_email'] = stripslashes( sanitize_email( $_POST['conf_email'] ) );
	}
	if( isset( $_POST['mailing_list'] ) && $_POST['mailing_list'] == 'yes' ){
		$new_info['mailing_list'] = 'yes';
	}
	if( isset( $_POST['job'] ) && $_POST['job'] <> '' ){
		if( isset( $_POST['job_id'] ) && $_POST['job_id'] <> '' ){
			$new_info['job'] = stripslashes( sanitize_text_field( $_POST['job_id'] ) );
		}else{
			$new_info['job'] = stripslashes( sanitize_text_field( $_POST['job'] ) );
		}
	}
	// only the registrant is allowed to attend for portal users
	$new_info['attendees'] = array(
		array(
			'user_id' => $user_id,
			'registrant' => 'r',
			'email' => $email,
			'firstname' => '', // not needed for registrant
			'lastname' => '', // not needed for registrant
		)
	);

    // Update the registration data
    // not sure what to set step to so randomly set it to 3!
    $success = TempRegistration::update_form_data($token, 3, $new_info);

    // and get the updated form data
    if ($success) {
        $form_data = TempRegistration::get_form_data($token);
    }else{
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_org_completed',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'Form data update failed',
    	]);
        wp_redirect(home_url());
        exit;
    }

    cc_debug_log_anything([
    	'function' => 'cc_registration_org_completed',
    	'token' => $token,
    	'post' => $_POST,
    	'form_data' => $form_data,
    ]);

    $is_new_user = isset($form_data['new_user']) && isset($form_data['new_user']['is_new_user']);

    // assemble the payment recordlc
	$payment = cc_paymentdb_empty_payment();

    // Populate payment with training details
    if($form_data['training_type'] == 'r'){
        $payment['type'] = 'recording';
    } elseif($form_data['training_type'] == 's'){
        $payment['type'] = 'series';
    } elseif($form_data['training_type'] == 'g'){
        $payment['type'] = 'group';
    }
    
    // Basic training info
    if($form_data['training_id']){
        $payment['workshop_id'] = absint($form_data['training_id']);
    }
    if($form_data['event_id']){
        $payment['event_ids'] = sanitize_text_field($form_data['event_id']);
    }
    if($form_data['currency'] && in_array($form_data['currency'], cc_valid_currencies()) ){
        $payment['currency'] = $form_data['currency'];
    }
    if($form_data['student'] == 'yes'){
        $payment['student'] = 'y';
    }
    
    // Payment amount
    $payment['payment_amount'] = 0; // Portal users don't pay
    $payment['pmt_method'] = 'portal';
    $payment['status'] = 'Payment not needed';
    if( $form_data['raw_price'] && $form_data['raw_price'] > 0 ){
        $payment['disc_amount'] = (float) $form_data['raw_price'];
        $payment['disc_code'] = strtoupper( $portal_user );
    }
    
    // User details from user
    $payment['reg_userid'] = $user_id;
    $payment['email'] = $email;
    $payment['firstname'] = get_user_meta($user->ID, 'first_name', true);
    $payment['lastname'] = get_user_meta($user->ID, 'last_name', true);
    $payment['phone'] = get_user_meta($user->ID, 'phone', true);
    
    // Address details
    $payment['address'] = cc_users_user_address( $user->ID, 'string' );
    
    // Source and other details
    if( isset( $form_data['source'] ) && $form_data['source'] <> '' ){
        $payment['source'] = substr($form_data['source'], 0, 255);
    }
    if(isset($form_data['mailing_list']) && $form_data['mailing_list'] == 'yes'){
        $payment['mailing_list'] = 'y';
    }
    
    // Group training notes
    if($form_data['training_type'] == 'g' && !empty($form_data['group_training'])){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter( $group_training );
        $group_training = array_unique( $group_training );
        if(!empty($group_training)){
	        // Build course details for notes
	        $course_details = array();
	        foreach($group_training as $training_id){
	            $course_title = get_the_title($training_id);
	            $course_details[] = $training_id . ': ' . $course_title;
	        }
            // Add group information to the main payment notes
            $payment['notes'] = 'Group training registration (' . count($group_training) . ' courses): ' . implode(', ', $course_details);
        }
    }
    
    // Manager email note
    if( isset( $form_data['conf_email'] ) && $form_data['conf_email'] <> '' ){
        $existing_notes = $payment['notes'] ?? '';
        $manager_note = 'Manager email: '. $form_data['conf_email'];
        $payment['notes'] = $existing_notes ? $existing_notes . '. ' . $manager_note : $manager_note;
    }
    
    // Payment method and status
    $payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $payment['tandcs'] = 'y';


	/*
	if(isset($_POST['ccreg'])){
	    $ccreg = stripslashes( sanitize_text_field( $_POST['ccreg'] ) );
	}
	if(isset($_POST['training-type']) && $_POST['training-type'] == 'r'){
		$payment['type'] = 'recording';
	}
	if(isset($_POST['training-id'])){
		$payment['workshop_id'] = absint($_POST['training-id']);
	}
	if(isset($_POST['event-id'])){
		$payment['event_ids'] = stripslashes( sanitize_text_field( $_POST['event-id'] ) );
	}
	if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies()) ){
		$payment['currency'] = $_POST['currency'];
	}
	if(isset($_POST['student']) && $_POST['student'] == 'yes'){
	    $payment['student'] = 'y';
	}
	$payment['payment_amount'] = 0;
	if(isset($_POST['raw-price'])){
		$payment['disc_amount'] = (float) $_POST['raw-price'];
	}
	*/
	/*
	if(isset($_POST['attend_first']) && $_POST['attend_first'] <> ''){
	    $payment['attendee_firstname'] = substr( stripslashes( sanitize_text_field( $_POST['attend_first'] ) ),0 , 50);
	}
	if(isset($_POST['attend_last']) && $_POST['attend_last'] <> ''){
	    $payment['attendee_lastname'] = substr( stripslashes( sanitize_text_field( $_POST['attend_last'] ) ), 0, 50);
	}
	if(isset($_POST['attend_email']) && $_POST['attend_email'] <> ''){
	    $payment['attendee_email'] = substr( stripslashes( sanitize_email( $_POST['attend_email'] ) ), 0, 255);
	}
	*/
	/*
	if(isset($_POST['conf_email']) && $_POST['conf_email'] <> ''){
	    $payment['notes'] = 'Manager email: '.stripslashes( sanitize_text_field( $_POST['conf_email'] ) );
	}
	*/

	/* CNWL does not allow attendees and, even if they did, they would not have been saved yet as the CNWL reg process bypasssess the saving bit!
	$attendees = cc_attendees_reg_get($ccreg);
	$count_attendees = count($attendees);
	if($count_attendees < 1){
		$count_attendees = 1;
	}
	*/

	/*
	$payment['vat_exempt'] = 'n';
	$payment['vat_included'] = 0;
	$payment['disc_code'] = strtoupper( $portal_user );
	$payment['vat_uk'] = 'y';
	$payment['pmt_method'] = 'online';
	$payment['status'] = 'Payment not needed';
	$payment['pmt_method'] = 'free';

	$user = wp_get_current_user();
	$payment['reg_userid'] = $user->ID;
	$payment['firstname'] = $user->user_firstname;
	$payment['lastname'] = $user->user_lastname;
	$payment['email'] = $user->user_email;
	$payment['phone'] = get_user_meta($user->ID, 'phone', true);
	$payment['address'] = cc_users_user_address($user->ID, 'string');

	$job = '';
	if(isset($_POST['job'])){
	    $job = stripslashes( sanitize_text_field( $_POST['job'] ) );
	}
	if( isset( $_POST['job_id'] ) && $_POST['job_id'] <> '' ){
	    $job = stripslashes( sanitize_text_field( $_POST['job_id'] ) );
	}
	if( $job <> '' ){
	    update_user_meta( $user->ID, 'job', $job );
	}

	if(isset($_POST['source'])){
	    $payment['source'] = stripslashes( sanitize_text_field( $_POST['source'] ) );
	}
	*/

	/*
	if($payment['attendee_email'] <> ''){
		if($payment['attendee_email'] == $payment['email']){
			$payment['attendee_email'] = $payment['attendee_firstname'] = $payment['attendee_lastname'] = '';
		}else{
			$attendee = cc_users_get_user($payment['attendee_email']);
			if($attendee){
				$payment['att_userid'] = $attendee->ID;
			}else{
				// we'll add the attendee to the user table
		        // user_login can only be 60 chars. Uniqid is 13 chars.
		        $user_login = substr( $payment['attendee_firstname'].' '.$payment['attendee_lastname'], 0, 46).' '.uniqid();
		        $args = array(
		            'user_login' => $user_login,
		            'user_pass' => wp_generate_password(),
		            'user_email' => $payment['attendee_email'],
		            'first_name' => $payment['attendee_firstname'],
		            'last_name' => $payment['attendee_lastname'],
		        );
		        $payment['att_userid'] = wp_insert_user($args);
		        update_user_meta($payment['att_userid'], 'source', 'attendee');
			}
		}
	}
	*/

	// is this a duplicated payment (eg because the Internet is slow and JS got bored and sent it again)?
	// $duplicate_id = cc_paymentdb_dejavu($payment['reg_userid'], $payment['workshop_id'], $payment['att_userid']);
    $duplicate_id = cc_paymentdb_dejavu( get_current_user_id(), $payment['workshop_id'], $token);

	if($duplicate_id){
		// trigger an update, not insert
		$payment['id'] = $duplicate_id;
	}

	$main_payment_id = cc_paymentdb_update_payment($payment, $token);
	$payment['id'] = $main_payment_id;
	
    $attendees = cc_attendees_for_payment($main_payment_id);
    $count_attendees = count($attendees);

    // what course are included in this registration?
    $child_training_ids = array();
    if( $payment['type'] == 'group' ){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter($group_training);
        $group_training = array_unique($group_training);
        $child_training_ids = $group_training;
        $series_discount = 0;
    }elseif( $payment['type'] == 'series' ){
        $series_id = $payment['workshop_id'];
        $series_courses = get_post_meta( $series_id, '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        $child_training_ids = $series_courses;
		$series_discount = (float) get_post_meta( $form_data['training_id'], '_series_discount', true);
    }

    // create individual payment records for groups and series
    if( ! empty( $child_training_ids ) ){
	    global $wpdb;
	    $table_name = $wpdb->prefix . 'ccpa_payments';

        // Get actual prices for each course (usually going to be zero!)
        $course_prices = [];
        $total_course_prices = 0;
        $now = time();
            
        foreach($child_training_ids as $training_id){
            // Get the individual course price
            $pricing = get_training_prices( $training_id );

		    $currency_lc = strtolower($payment['currency']);
			$course_price = $pricing['price_'.$currency_lc];

			$total_payable = $course_price * $count_attendees;

            // Apply student discount if applicable
            $student_discount = 0;
            if( $payment['student'] == 'y' && $pricing['student_discount'] > 0 ){
				$student_discount = round( $course_price * $pricing['student_discount'] / 100, 2 );
				$student_discount = $student_discount * $count_attendees;
				if( $student_discount > $total_payable ){
					$student_discount = $total_payable;
					$total_payable = 0;
				}else{
					$total_payable = $total_payable - $student_discount;
				}
            }

            // or early bird discount
            $early_bird_name = '';
            $early_bird_discount = 0;
			if( $pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] <> '' ){
				$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'] );
				if( $expiry_date ){
					if( $expiry_date->getTimestamp() > $now ){
						$early_bird_name = ( $pricing['early_bird_name'] == '' ) ? 'Early bird' : $pricing['early_bird_name'];
						$early_bird_discount = round( $course_price * $pricing['early_bird_discount'] / 100, 2 );
						$early_bird_discount = $early_bird_discount * $count_attendees;
						if( $early_bird_discount > $total_payable ){
							$early_bird_discount = $total_payable;
							$total_payable = 0;
						}else{
							$total_payable = $total_payable - $early_bird_discount;
						}
					}
				}
			}

			// if it's a series, apply the series discount
			if( $series_discount > 0 ){
				$series_disc_amt = round( $course_price * $series_discount / 100, 2 );
				$series_disc_amt = $series_disc_amt * $count_attendees;
				if( $series_disc_amt > $total_payable ){
					$series_disc_amt = $total_payable;
					$total_payable = 0;
				}else{
					$total_payable = $total_payable - $series_disc_amt;
				}
			}else{
				$series_disc_amt = 0;
			}

			// if it's a portal user, discount the rest
			$portal_user = get_user_meta( $user_id, 'portal_user', true );
			if( $portal_user <> '' ){
				$portal_discount = $total_payable;
				$total_payable = 0;
				$portal_disc_code = strtoupper( $portal_user );
			}else{
				$portal_discount = 0;
				$portal_disc_code = '';
			}

			// vat
			$vat = ( $payment['vat_exempt'] == 'y' ) ? 0 : ccpa_vat_amount( $total_payable );
			$total_payable = $total_payable + $vat;
            
            // store it
            $course_prices[$training_id] = [
            	'course_price' => $course_price,
            	'total_payable' => $total_payable,
            	'student_discount' => $student_discount,
            	'early_bird_name' => $early_bird_name,
            	'early_bird_discount' => $early_bird_discount,
            	'series_discount' => $series_disc_amt,
            	'portal_discount' => $portal_discount,
            	'portal_disc_code' => $portal_disc_code,
            	'vat_included' => $vat,
            ];
        }

        // Create individual course records (linked to main payment)
        $course_payment_ids = [];
        $running_total = 0;

        foreach( $child_training_ids as $index => $training_id ){
            // Clone the payment array for each course
            $course_payment = $payment;

            // Set key fields for the course record
            $course_payment['id'] = 0; // Force new record
            $course_payment['payment_ref'] = $main_payment_id; // Link to main payment
            
            // Clear fields that should only be on the main payment
            $course_payment['payment_intent_id'] = '';
            $course_payment['charge_id'] = '';
            $course_payment['stripe_fee'] = 0;

            // Set the specific workshop ID for this record
            $course_payment['workshop_id'] = $training_id;

            // Set type based on training type
            if( course_training_type( $training_id ) == 'recording' ){
            	$course_payment['type'] = 'recording';
            }else{
            	$course_payment['type'] = '';
            }

            // financials
            $course_payment['payment_amount'] = $course_prices[$training_id]['total_payable'];
            if( $course_prices[$training_id]['student_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['student_discount'];
            	$course_payment['disc_code'] = 'student';
            }
            if( $course_prices[$training_id]['early_bird_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['early_bird_discount'];
            	$course_payment['disc_code'] = $course_prices[$training_id]['early_bird_name'];
            }
            if( $course_prices[$training_id]['series_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['series_discount'];
            	$course_payment['disc_code'] = 'series';
            }
            if( $course_prices[$training_id]['portal_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['portal_discount'];
            	$course_payment['disc_code'] = $course_prices[$training_id]['portal_disc_code'];
            }
            $course_payment['vat_included'] = $course_prices[$training_id]['vat_included'];

            // Add course-specific note linking to main payment
			$course_payment['notes'] = sprintf(
			    'Course record for %s payment %d - Course %d of %d: %d: %s',
			    $payment['type'],
                $main_payment_id,
			    ($index + 1),
			    count($child_training_ids),
			    $training_id,
			    get_the_title($training_id),
			);
            
            // Set a different status to indicate this is a linked record
            $course_payment['status'] = 'Linked to #' . $main_payment_id;

            $course_payment['token'] = '';

            // Ensure we have required fields
            $course_payment['last_update'] = date('Y-m-d H:i:s');
            $course_payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

            // DIRECT DATABASE INSERT - bypassing cc_paymentdb_update_payment
            $result = $wpdb->insert($table_name, $course_payment);

            if($result !== false){
                $course_payment_id = $wpdb->insert_id;
                $course_payment['id'] = $course_payment_id;
                $course_payment_ids[] = $course_payment_id;
                
                // Copy attendees from main payment to this course payment
                // This is crucial for cc_myacct_insert_workshops_recordings_users to work
                foreach($attendees as $attendee){
                    // Only pass the fields that exist in cc_attendees table
                    $attendee_data = array(
                        'payment_id' => $course_payment_id,
                        'user_id' => $attendee['user_id'] ?? 0,
                        'registrant' => $attendee['registrant'] ?? '',
                    );
                    cc_attendee_add($attendee_data);
                }

                // Now explicitly call cc_myacct_insert_workshops_recordings_users
                // This function will now find the attendees we just added and create
                // the appropriate workshops_users or recordings_users entries
                cc_myacct_insert_workshops_recordings_users($course_payment);
                
                // If it's a recording, also give recording access via user meta
                if($course_payment['type'] == 'recording'){
                    $access_type = 'free';
                    $course_amount = 0;
                    $vat_proportion = 0;
                    foreach($attendees as $attendee){
                        if(isset($attendee['user_id']) && $attendee['user_id'] > 0){
                            ccrecw_add_recording_to_user(
                                $attendee['user_id'], 
                                $training_id, 
                                $access_type, 
                                $course_amount,
                                '', 
                                $payment['currency'], 
                                $vat_proportion,
                                $course_payment_id
                            );
                        }
                    }
                }

				// Update popularity stats for each course
                cc_training_popularity_update($training_id, $count_attendees);
                
                // STEP 5: Record in training groups table for tracking
                if( $payment['type'] == 'group' ){
	                cc_training_groups_record_training($main_payment_id, $training_id);
	            }
            }
    	}

    	// could update the main payment record with the IDs of the children at this point

    }elseif( $payment['type'] <> 'group' ){
    	// workshop, recording or series

        // now process a single training
        if( $payment['type'] == 'recording' || $payment['type'] == '' ){
			// Update popularity stats
	        cc_training_popularity_update($form_data['training_id'], $count_attendees);
	    }

	    // Give recording access to ALL attendees (not just registrant)
	    if($payment['type'] == 'recording'){
	        $access_type = 'free';
	        // Give recording access to each attendee
	        foreach ($attendees as $attendee) {
	            ccrecw_add_recording_to_user(
	                $attendee['user_id'], 
	                $payment['workshop_id'], 
	                $access_type, 
	                0, 
	                '', 
	                $payment['currency'], 
	                0, 
	                $main_payment_id
	            );
	        }
	    }

    }

	// save last registration info (for CNWL people really)
	update_user_meta($user->ID, 'last_registration', date('Y-m-d H:i:s'));
	update_user_meta($user->ID, 'last_reg_id', $main_payment_id);

	// add them to the mailing list if needed
    if($payment['mailing_list'] == 'y'){
        update_user_meta($user->ID, 'mailing_list', 'y');
        cc_mailsterint_newsletter_subscribe($user);
    }

    cc_mailsterint_update_region($user);

	// for Google Ads conversion tracking ...
	// $response_conversion = $payment['payment_amount'].'|'.$payment['currency'].'|'.$client_secret;

	// send out the emails
	// cc_mailsterint_send_email($payment);
	cc_mailsterint_send_reg_emails( $payment );

    // Update temp registration status
    TempRegistration::update_status($token, 'completed');

	// also update the popularity stats
	if( !$duplicate_id ){
		cc_training_popularity_update( $payment['workshop_id'], 1 );
		if( $payment['upsell_workshop_id'] > 0 ){
			cc_training_popularity_update( $payment['upsell_workshop_id'], 1 );
		}

		// does the payment result in a voucher being sent?
		ccpa_vouchers_alloc_maybe($payment);
	}

	// $funny_number = ( $main_payment_id + 987834 ) * 2;
	// $conf_url = add_query_arg(array('c' => $funny_number), site_url('/reg-conf/'));
	$conf_url = add_query_arg( array( 'token' => $token ), site_url( '/reg-conf' ) );
	wp_redirect($conf_url);
	exit;

}

// free registration
add_action('init', 'cc_registration_free_confirmation');
function cc_registration_free_confirmation(){
	if(!isset($_POST['reg-conf-submit'])) return;

    // Get token from form
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    if(empty($token)) {
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_free_confirmation',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'No token supplied',
    	]);
        wp_redirect(home_url());
        exit;
    }

    // Get or create the user from temp registration
    $user_id = cc_create_user_from_temp_registration($token);

    if( ! $user_id ){
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_free_confirmation',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'Could not create user',
    	]);
        wp_redirect(home_url());
        exit;
    }

    // reg user info
    $user = get_user_by( 'id', $user_id );
    $email = $user->user_email;

	// capture the new info into the temp registration system
	$new_info = array();
	if( isset( $_POST['source'] ) && $_POST['source'] <> '' ){
		$new_info['source'] = stripslashes( sanitize_text_field( $_POST['source'] ) );
	}
	if( isset( $_POST['conf_email'] ) && $_POST['conf_email'] <> '' ){
		$new_info['conf_email'] = stripslashes( sanitize_email( $_POST['conf_email'] ) );
	}
	if( isset( $_POST['mailing_list'] ) && $_POST['mailing_list'] == 'yes' ){
		$new_info['mailing_list'] = 'yes';
	}
	if( isset( $_POST['job'] ) && $_POST['job'] <> '' ){
		if( isset( $_POST['job_id'] ) && $_POST['job_id'] <> '' ){
			$new_info['job'] = stripslashes( sanitize_text_field( $_POST['job_id'] ) );
		}else{
			$new_info['job'] = stripslashes( sanitize_text_field( $_POST['job'] ) );
		}
	}
	// only the registrant is allowed to attend for free training
	$new_info['attendees'] = array(
		array(
			'user_id' => $user_id,
			'registrant' => 'r',
			'email' => $email,
			'firstname' => '', // not needed for registrant
			'lastname' => '', // not needed for registrant
		)
	);

    // Update the registration data
    // not sure what to set step to so randomly set it to 3!
    $success = TempRegistration::update_form_data($token, 3, $new_info);

    // and get the updated form data
    if ($success) {
        $form_data = TempRegistration::get_form_data($token);
    }else{
    	cc_debug_log_anything([
	    	'function' => 'cc_registration_free_confirmation',
	    	'token' => $token,
	    	'post' => $_POST,
	    	'error' => 'Form data update failed',
    	]);
        wp_redirect(home_url());
        exit;
    }

    cc_debug_log_anything([
    	'function' => 'cc_registration_free_confirmation',
    	'token' => $token,
    	'post' => $_POST,
    	'form_data' => $form_data,
    ]);

    $is_new_user = isset($form_data['new_user']) && isset($form_data['new_user']['is_new_user']);

    // assemble the payment recordlc
	$payment = cc_paymentdb_empty_payment();

    // Populate payment with training details
    if($form_data['training_type'] == 'r'){
        $payment['type'] = 'recording';
    } elseif($form_data['training_type'] == 's'){
        $payment['type'] = 'series';
    } elseif($form_data['training_type'] == 'g'){
        $payment['type'] = 'group';
    }
    
    // Basic training info
    if($form_data['training_id']){
        $payment['workshop_id'] = absint($form_data['training_id']);
    }
    if($form_data['event_id']){
        $payment['event_ids'] = sanitize_text_field($form_data['event_id']);
    }
    if($form_data['currency'] && in_array($form_data['currency'], cc_valid_currencies()) ){
        $payment['currency'] = $form_data['currency'];
    }
    if($form_data['student'] == 'yes'){
        $payment['student'] = 'y';
    }
    
    // Payment details
    $payment['payment_amount'] = 0; // Free training
    $payment['pmt_method'] = 'free';
    $payment['status'] = 'Payment not needed';
    
    // User details from user
    $payment['reg_userid'] = $user_id;
    $payment['email'] = $email;
    $payment['firstname'] = get_user_meta($user->ID, 'first_name', true);
    $payment['lastname'] = get_user_meta($user->ID, 'last_name', true);
    $payment['phone'] = get_user_meta($user->ID, 'phone', true);
    
    // Address details
    $payment['address'] = cc_users_user_address( $user->ID, 'string' );
    
    // Source and other details
    $more = $form_data['more'] ?? [];
    if( isset( $form_data['source'] ) && $form_data['source'] <> '' ){
        $payment['source'] = substr($form_data['source'], 0, 255);
    }elseif( isset( $more['source'] ) && $more['source'] <> '' ){
        $payment['source'] = substr($more['source'], 0, 255);
    }

    if( isset( $form_data['mailing_list'] ) && $form_data['mailing_list'] == 'yes' 
    	|| isset( $more['mailing_list'] ) && $more['mailing_list'] == 'yes' ){
        $payment['mailing_list'] = 'y';
    }elseif( cc_mailsterint_on_newsletter( $user->user_email ) ){
		$payment['mailing_list'] = 'p';
    }

    // VAT details if available
    if(isset($form_data['vat_exempt'])){
        $payment['vat_exempt'] = $form_data['vat_exempt'];
    }
    if(isset($form_data['vat_uk'])){
        $payment['vat_uk'] = substr($form_data['vat_uk'], 0, 25);
    }
    if(isset($form_data['vat_employ'])){
        $payment['vat_employ'] = substr($form_data['vat_employ'], 0, 25);
    }
    if(isset($form_data['vat_employer'])){
        $payment['vat_employer'] = substr($form_data['vat_employer'], 0, 255);
    }
    
    // Invoice details
    if(isset($form_data['inv_email'])){
        $payment['inv_email'] = substr($form_data['inv_email'], 0, 255);
    }
    if(isset($form_data['inv_phone'])){
        $payment['inv_phone'] = substr($form_data['inv_phone'], 0, 30);
    }
    if(isset($form_data['inv_ref'])){
        $payment['inv_ref'] = substr($form_data['inv_ref'], 0, 255);
    }
    if(isset($form_data['inv_org'])){
        $payment['inv_org'] = substr($form_data['inv_org'], 0, 255);
    }
    if(isset($form_data['inv_name'])){
        $payment['inv_name'] = substr($form_data['inv_name'], 0, 255);
    }
    
    // Invoice address
    if(isset($form_data['inv_addr1'])){
        $payment['inv_addr1'] = substr($form_data['inv_addr1'], 0, 255);
    }
    if(isset($form_data['inv_addr2'])){
        $payment['inv_addr2'] = substr($form_data['inv_addr2'], 0, 255);
    }
    if(isset($form_data['inv_town'])){
        $payment['inv_town'] = substr($form_data['inv_town'], 0, 255);
    }
    if(isset($form_data['inv_county'])){
        $payment['inv_county'] = substr($form_data['inv_county'], 0, 255);
    }
    if(isset($form_data['inv_postcode'])){
        $payment['inv_postcode'] = substr($form_data['inv_postcode'], 0, 255);
    }
    if(isset($form_data['inv_country'])){
        $payment['inv_country'] = substr($form_data['inv_country'], 0, 255);
    }
    
    // Discount/voucher codes
    if(isset($form_data['disc_code']) && $form_data['disc_amount'] > 0){
        $payment['disc_code'] = substr($form_data['disc_code'], 0, 25);
        $payment['disc_amount'] = (float) $form_data['disc_amount'];
    }
    if(isset($form_data['voucher_code']) && $form_data['voucher_amount'] > 0){
		if( substr( $form_data['voucher_code'], 0, 3) == 'CC-' ){
			$payment['voucher_code'] = substr( $form_data['voucher_code'], 0, 12 );
		}else{
			$payment['voucher_code'] = substr( cc_voucher_code_raw( $form_data['voucher_code'] ), 0, 12 );
		}
        $payment['voucher_amount'] = (float) $form_data['voucher_amount'];
    }
    
    // Group training notes
    if($form_data['training_type'] == 'g' && !empty($form_data['group_training'])){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter( $group_training );
        $group_training = array_unique( $group_training );
        if(!empty($group_training)){
	        // Build course details for notes
	        $course_details = array();
	        foreach($group_training as $training_id){
	            $course_title = get_the_title($training_id);
	            $course_details[] = $training_id . ': ' . $course_title;
	        }
            // Add group information to the main payment notes
            $payment['notes'] = 'Group training registration (' . count($group_training) . ' courses): ' . implode(', ', $course_details);
        }
    }
    
    // Manager email note
    if(isset($form_data['conf_email']) && $form_data['conf_email'] != ''){
        $existing_notes = $payment['notes'] ?? '';
        $manager_note = 'Manager email: ' . $form_data['conf_email'];
        $payment['notes'] = $existing_notes ? $existing_notes . '. ' . $manager_note : $manager_note;
    }

    // IP address
    $payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $payment['tandcs'] = 'y';


	/*
	if(isset($_POST['ccreg'])){
	    $ccreg = stripslashes( sanitize_text_field( $_POST['ccreg'] ) );
	}
	if(isset($_POST['training-type']) && $_POST['training-type'] == 'r'){
		$payment['type'] = 'recording';
	}
	if(isset($_POST['training-id'])){
		$payment['workshop_id'] = absint($_POST['training-id']);
	}
	if(isset($_POST['event-id'])){
		$payment['event_ids'] = stripslashes( sanitize_text_field( $_POST['event-id'] ) );
	}
	if(isset($_POST['currency']) && in_array($_POST['currency'], cc_valid_currencies()) ){
		$payment['currency'] = $_POST['currency'];
	}
	if(isset($_POST['student']) && $_POST['student'] == 'yes'){
	    $payment['student'] = 'y';
	}elseif(cc_workshop_price_earlybird($payment['workshop_id'])){
		$payment['earlybird'] = 'y';
	}
	if(isset($_POST['vat_exempt']) && ($_POST['vat_exempt'] == 'y' || $_POST['vat_exempt'] == 'n')){
	    $payment['vat_exempt'] = $_POST['vat_exempt'];
	}
	if(isset($_POST['voucher_code'])){
	    $payment['voucher_code'] = substr( cc_voucher_code_raw( stripslashes( sanitize_text_field( $_POST['voucher_code'] ) ) ), 0, 12);
	}
	if(isset($_POST['voucher_amount'])){
	    $payment['voucher_amount'] = (float) $_POST['voucher_amount'];
	}
	if(isset($_POST['total_payable'])){
	    $payment['payment_amount'] = (float) $_POST['total_payable'];
	}
	if(isset($_POST['vat-uk']) && ($_POST['vat-uk'] == 'y' || $_POST['vat-uk'] == 'n')){
	    $payment['vat_uk'] = $_POST['vat-uk'];
	}
	if(isset($_POST['vat-employ'])){
	    $payment['vat_employ'] = substr( stripslashes( sanitize_text_field( $_POST['vat-employ'] ) ), 0, 25);
	}
	if(isset($_POST['vat-employer'])){
	    $payment['vat_employer'] = substr( stripslashes( sanitize_text_field( $_POST['vat-employer'] ) ),0 , 255);
	}
	if(isset($_POST['upsell_workshop_id'])){
	    $payment['upsell_workshop_id'] = (int) $_POST['upsell_workshop_id'];
	}
	if(isset($_POST['source'])){
	    $payment['source'] = substr( stripslashes( sanitize_text_field( $_POST['source'] ) ), 0, 255);
	}
	if( isset( $_POST['mailing_list'] ) && $_POST['mailing_list'] == 'yes' ){
		$payment['mailing_list'] = 'y';
	}
	if(isset($_POST['conf_email']) && $_POST['conf_email'] <> ''){
	    $payment['notes'] = 'Manager email: '.stripslashes( sanitize_text_field( $_POST['conf_email'] ) );
	}
	if(isset($_POST['client-secret']) && $_POST['client-secret'] <> ''){
		$client_secret = stripslashes( sanitize_text_field( $_POST['client-secret'] ) );
		$payment['token'] = ccpa_payint_client_secret_get_pi_id( $client_secret );
	}

	$user = wp_get_current_user();

	$job = '';
	if(isset($_POST['job'])){
	    $job = stripslashes( sanitize_text_field( $_POST['job'] ) );
		if( isset( $_POST['job_id'] ) && $_POST['job_id'] <> '' ){
		    $job = stripslashes( sanitize_text_field( $_POST['job_id'] ) );
		}
		if( $job <> '' ){
		    update_user_meta( $user->ID, 'job', $job );
		}
	}

	// attendees
	if(isset($_POST['attend_type']) && ( $_POST['attend_type'] == 'me' || $_POST['attend_type'] == 'notme' || $_POST['attend_type'] == 'group' ) ){
	    $attendee_type = $_POST['attend_type'];
		if($attendee_type == 'me'){
		    $attendees[] = array(
		        'registrant' => 'r',
		        'user_id' =>  $user->ID,
		    );
		}elseif($attendee_type == 'notme'){
		    $attendees[] = array(
		        'registrant' => '',
		        'firstname' => stripslashes( sanitize_text_field( $_POST['attend_single_first'] ) ),
		        'lastname' => stripslashes( sanitize_text_field( $_POST['attend_single_last'] ) ),
		        'email' => stripslashes( sanitize_email( $_POST['attend_single_email'] ) ),
		    );
		}else{
		    if(isset($_POST['attend_check_reg']) && $_POST['attend_check_reg'] == 'yes'){
		        $attendees[] = array(
		            'registrant' => 'r',
		            'user_id' =>  $user->ID,
		        );
		    }
		    foreach ($_POST['attend_first'] as $key => $attend_first) {
		        if($attend_first <> ''){
		            $attendees[] = array(
		                'registrant' => '',
		                'firstname' => stripslashes( sanitize_text_field( $attend_first ) ),
		                'lastname' => stripslashes( sanitize_text_field( $_POST['attend_last'][$key] ) ),
		                'email' => stripslashes( sanitize_email( $_POST['attend_email'][$key] ) ),
		            );
		        }
		    }
		}
		cc_attendees_reg_save($ccreg, $attendees);
	}

	// $attendees = cc_attendees_reg_get($ccreg);
	$count_attendees = count($attendees);
	if($count_attendees < 1){
		$count_attendees = 1;
	}

	if(isset($_POST['vat_amount'])){
	    $payment['vat_included'] = round( $count_attendees * (float) $_POST['vat_amount'], 2);
	}
	if(isset($_POST['disc_amount'])){
	    $payment['disc_amount'] = round( $count_attendees * (float) $_POST['disc_amount'], 2);
	}
	if(isset($_POST['disc_code']) && $payment['disc_amount'] > 0){
	    $payment['disc_code'] = substr( stripslashes( sanitize_text_field( $_POST['disc_code'] ) ), 0, 25);
	}

	$payment['status'] = 'Payment not needed';
	$payment['pmt_method'] = 'free';

	if($payment['vat_uk'] == 'y'){
		$payment['vat_employ'] = $payment['vat_employer'] = '';
	}

	$payment['reg_userid'] = $user->ID;
	$payment['firstname'] = $user->user_firstname;
	$payment['lastname'] = $user->user_lastname;
	$payment['email'] = $user->user_email;
	$payment['phone'] = get_user_meta($user->ID, 'phone', true);
	$payment['address'] = cc_users_user_address($user->ID, 'string');

	if( $payment['mailing_list'] == '' && cc_mailsterint_on_newsletter( $user->user_email ) ){
		$payment['mailing_list'] = 'p';
	}
	*/

	// is this a duplicated payment (eg because the Internet is slow and JS got bored and sent it again)?
	// $duplicate_id = cc_paymentdb_dejavu($payment['reg_userid'], $payment['workshop_id'], $ccreg);
    $duplicate_id = cc_paymentdb_dejavu( get_current_user_id(), $payment['workshop_id'], $token);
	if($duplicate_id){
		// trigger an update, not insert
		$payment['id'] = $duplicate_id;
	}

	// updates payment and attendees and also the workshop/recording user tables
	$main_payment_id = cc_paymentdb_update_payment($payment, $token);
	$payment['id'] = $main_payment_id;

    // Get attendee count for stats
    $attendees = cc_attendees_for_payment($main_payment_id);
    $count_attendees = count($attendees);

    // what course are included in this registration?
    $child_training_ids = array();
    if( $payment['type'] == 'group' ){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter($group_training);
        $group_training = array_unique($group_training);
        $child_training_ids = $group_training;
        $series_discount = 0;
    }elseif( $payment['type'] == 'series' ){
        $series_id = $payment['workshop_id'];
        $series_courses = get_post_meta( $series_id, '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        $child_training_ids = $series_courses;
		$series_discount = (float) get_post_meta( $form_data['training_id'], '_series_discount', true);
    }

    // create individual payment records for groups and series
    if( ! empty( $child_training_ids ) ){
	    global $wpdb;
	    $table_name = $wpdb->prefix . 'ccpa_payments';

        // Get actual prices for each course (usually going to be zero!)
        $course_prices = [];
        $total_course_prices = 0;
        $now = time();
            
        foreach($child_training_ids as $training_id){
            // Get the individual course price
            $pricing = get_training_prices( $training_id );

		    $currency_lc = strtolower($payment['currency']);
			$course_price = $pricing['price_'.$currency_lc];

			$total_payable = $course_price * $count_attendees;

            // Apply student discount if applicable
            $student_discount = 0;
            if( $payment['student'] == 'y' && $pricing['student_discount'] > 0 ){
				$student_discount = round( $course_price * $pricing['student_discount'] / 100, 2 );
				$student_discount = $student_discount * $count_attendees;
				if( $student_discount > $total_payable ){
					$student_discount = $total_payable;
					$total_payable = 0;
				}else{
					$total_payable = $total_payable - $student_discount;
				}
            }

            // or early bird discount
            $early_bird_name = '';
            $early_bird_discount = 0;
			if( $pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] <> '' ){
				$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'] );
				if( $expiry_date ){
					if( $expiry_date->getTimestamp() > $now ){
						$early_bird_name = ( $pricing['early_bird_name'] == '' ) ? 'Early bird' : $pricing['early_bird_name'];
						$early_bird_discount = round( $course_price * $pricing['early_bird_discount'] / 100, 2 );
						$early_bird_discount = $early_bird_discount * $count_attendees;
						if( $early_bird_discount > $total_payable ){
							$early_bird_discount = $total_payable;
							$total_payable = 0;
						}else{
							$total_payable = $total_payable - $early_bird_discount;
						}
					}
				}
			}

			// if it's a series, apply the series discount
			if( $series_discount > 0 ){
				$series_disc_amt = round( $course_price * $series_discount / 100, 2 );
				$series_disc_amt = $series_disc_amt * $count_attendees;
				if( $series_disc_amt > $total_payable ){
					$series_disc_amt = $total_payable;
					$total_payable = 0;
				}else{
					$total_payable = $total_payable - $series_disc_amt;
				}
			}else{
				$series_disc_amt = 0;
			}

			// if it's a portal user, discount the rest
			// shouldn't be here really!!
			$portal_user = get_user_meta( $user_id, 'portal_user', true );
			if( $portal_user <> '' ){
				$portal_discount = $total_payable;
				$total_payable = 0;
				$portal_disc_code = strtoupper( $portal_user );
			}else{
				$portal_discount = 0;
				$portal_disc_code = '';
			}

			// vat
			$vat = ( $payment['vat_exempt'] == 'y' ) ? 0 : ccpa_vat_amount( $total_payable );
			$total_payable = $total_payable + $vat;
            
            // store it
            $course_prices[$training_id] = [
            	'course_price' => $course_price,
            	'total_payable' => $total_payable,
            	'student_discount' => $student_discount,
            	'early_bird_name' => $early_bird_name,
            	'early_bird_discount' => $early_bird_discount,
            	'series_discount' => $series_disc_amt,
            	'portal_discount' => $portal_discount,
            	'portal_disc_code' => $portal_disc_code,
            	'vat_included' => $vat,
            ];
        }

        // Create individual course records (linked to main payment)
        $course_payment_ids = [];
        $running_total = 0;

        foreach( $child_training_ids as $index => $training_id ){
            // Clone the payment array for each course
            $course_payment = $payment;

            // Set key fields for the course record
            $course_payment['id'] = 0; // Force new record
            $course_payment['payment_ref'] = $main_payment_id; // Link to main payment
            
            // Clear fields that should only be on the main payment
            $course_payment['payment_intent_id'] = '';
            $course_payment['charge_id'] = '';
            $course_payment['stripe_fee'] = 0;

            // Set the specific workshop ID for this record
            $course_payment['workshop_id'] = $training_id;

            // Set type based on training type
            if( course_training_type( $training_id ) == 'recording' ){
            	$course_payment['type'] = 'recording';
            }else{
            	$course_payment['type'] = '';
            }

            // financials
            $course_payment['payment_amount'] = $course_prices[$training_id]['total_payable'];
            if( $course_prices[$training_id]['student_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['student_discount'];
            	$course_payment['disc_code'] = 'student';
            }
            if( $course_prices[$training_id]['early_bird_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['early_bird_discount'];
            	$course_payment['disc_code'] = $course_prices[$training_id]['early_bird_name'];
            }
            if( $course_prices[$training_id]['series_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['series_discount'];
            	$course_payment['disc_code'] = 'series';
            }
            if( $course_prices[$training_id]['portal_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['portal_discount'];
            	$course_payment['disc_code'] = $course_prices[$training_id]['portal_disc_code'];
            }
            $course_payment['vat_included'] = $course_prices[$training_id]['vat_included'];

            // Add course-specific note linking to main payment
			$course_payment['notes'] = sprintf(
			    'Course record for %s payment %d - Course %d of %d: %d: %s',
			    $payment['type'],
                $main_payment_id,
			    ($index + 1),
			    count($child_training_ids),
			    $training_id,
			    get_the_title($training_id),
			);
            
            // Set a different status to indicate this is a linked record
            $course_payment['status'] = 'Linked to #' . $main_payment_id;

            $course_payment['token'] = '';

            // Ensure we have required fields
            $course_payment['last_update'] = date('Y-m-d H:i:s');
            $course_payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

            // DIRECT DATABASE INSERT - bypassing cc_paymentdb_update_payment
            $result = $wpdb->insert($table_name, $course_payment);

            if($result !== false){
                $course_payment_id = $wpdb->insert_id;
                $course_payment['id'] = $course_payment_id;
                $course_payment_ids[] = $course_payment_id;
                
                // Copy attendees from main payment to this course payment
                // This is crucial for cc_myacct_insert_workshops_recordings_users to work
                foreach($attendees as $attendee){
                    // Only pass the fields that exist in cc_attendees table
                    $attendee_data = array(
                        'payment_id' => $course_payment_id,
                        'user_id' => $attendee['user_id'] ?? 0,
                        'registrant' => $attendee['registrant'] ?? '',
                    );
                    cc_attendee_add($attendee_data);
                }

                // Now explicitly call cc_myacct_insert_workshops_recordings_users
                // This function will now find the attendees we just added and create
                // the appropriate workshops_users or recordings_users entries
                cc_myacct_insert_workshops_recordings_users($course_payment);
                
                // If it's a recording, also give recording access via user meta
                if($course_payment['type'] == 'recording'){
                    $access_type = 'free';
                    $course_amount = 0;
                    $vat_proportion = 0;
                    foreach($attendees as $attendee){
                        if(isset($attendee['user_id']) && $attendee['user_id'] > 0){
                            ccrecw_add_recording_to_user(
                                $attendee['user_id'], 
                                $training_id, 
                                $access_type, 
                                $course_amount,
                                '', 
                                $payment['currency'], 
                                $vat_proportion,
                                $course_payment_id
                            );
                        }
                    }
                }

				// Update popularity stats for each course
                cc_training_popularity_update($training_id, $count_attendees);
                
                // STEP 5: Record in training groups table for tracking
                if( $payment['type'] == 'group' ){
	                cc_training_groups_record_training($main_payment_id, $training_id);
	            }
            }
    	}

    	// could update the main payment record with the IDs of the children at this point

    }elseif( $payment['type'] <> 'group' ){
    	// workshop, recording or series

        // now process a single training
        if( $payment['type'] == 'recording' || $payment['type'] == '' ){
			// Update popularity stats
	        cc_training_popularity_update( $payment['workshop_id'], $count_attendees );
	    }

	    // Give recording access to ALL attendees (not just registrant)
	    if($payment['type'] == 'recording'){
	        $access_type = 'free';
	        // Give recording access to each attendee
	        foreach ($attendees as $attendee) {
	            ccrecw_add_recording_to_user(
	                $attendee['user_id'], 
	                $payment['workshop_id'], 
	                $access_type, 
	                0, 
	                '', 
	                $payment['currency'], 
	                0, 
	                $main_payment_id
	            );
	        }
	    }

    }

    // Handle new user notification
    if($is_new_user){
        // Set new user flags
        update_user_meta($user_id, 'new_password', '');
        update_user_meta($user_id, 'force_new_password', 'yes');
    }

	// if a refer a friend code was used, record the fact and allocate a credit to the code giver ... also blocks the recipient from using it a second time
	if( substr( $payment['disc_code'], 0, 3 ) == 'CC-' && !$duplicate_id ){
		cc_friend_code_used( $payment['disc_code'], $payment['reg_userid'], $payment['currency'], $payment['disc_amount'] );
	}

	if( $payment['voucher_code'] <> '' && $payment['voucher_amount'] > 0 ){
		if( substr($payment['voucher_code'], 0, 3) == 'CC-' ){
			// refer a friend redemption
			cc_friend_redeem( $payment['voucher_code'], $payment['reg_userid'], $payment['currency'], $payment['voucher_amount'] );
		}else{
			ccpa_voucher_usage_record($payment['voucher_code'], $payment['voucher_amount'], $payment['currency'], $main_payment_id);
		}
	}

	// save last registration info (for CNWL people really)
	update_user_meta($user->ID, 'last_registration', date('Y-m-d H:i:s'));
	update_user_meta($user->ID, 'last_reg_id', $main_payment_id);

	// add them to the mailing list if needed
    if($payment['mailing_list'] == 'y'){
        update_user_meta($user->ID, 'mailing_list', 'y');
        cc_mailsterint_newsletter_subscribe($user);
    }

    cc_mailsterint_update_region($user);

    // Update temp registration status
    TempRegistration::update_status($token, 'completed');

	// send out the emails
	// if it's a duplicate they may get the emails twice
	// cc_mailsterint_send_email($payment);
	cc_mailsterint_send_reg_emails( $payment );

	// does the payment result in a voucher being sent?
	if(!$duplicate_id){
		ccpa_vouchers_alloc_maybe($payment);
	}

	// Also update the popularity stats - get actual attendee count from database
	if(!$duplicate_id && $main_payment_id){
	    $attendees = cc_attendees_for_payment($main_payment_id);
	    $count_attendees = count($attendees);
	    cc_training_popularity_update($payment['workshop_id'], $count_attendees);
	    if($payment['upsell_workshop_id'] > 0){
	        cc_training_popularity_update($payment['upsell_workshop_id'], $count_attendees);
	    }
	}

	/*
	if( !$duplicate_id ){
		cc_training_popularity_update( $payment['workshop_id'], $count_attendees );
		if( $payment['upsell_workshop_id'] > 0 ){
			cc_training_popularity_update( $payment['upsell_workshop_id'], $count_attendees );
		}
	}
	*/

	// $funny_number = ( $main_payment_id + 987834 ) * 2;
	// $conf_url = add_query_arg( array( 'c' => $funny_number ), site_url( '/reg-conf/' ));
	$conf_url = add_query_arg( array( 'token' => $token ), site_url( '/reg-conf' ) );
	wp_redirect( $conf_url );
	exit;
}


// Helper function for free registration with token
function cc_registration_free_confirmation_with_token($token) {
    $registration_data = TempRegistration::get($token);
    if (!$registration_data) {
        wp_redirect(home_url());
        exit;
    }

    if( $registration_data->user_id == 0 ){
    	$is_new_user = true;
    }else{
    	$is_new_user = false;
    }

    $form_data = json_decode($registration_data->form_data, true);
    $user_id = cc_create_user_from_temp_registration( $token );
    $user = get_user_by( 'id', $user_id );

    /*
    // Process the free registration using the token data
    $_POST['reg-conf-submit'] = true;
    $_POST['token'] = $token;
    
    // Set up form data for the existing free confirmation function
    foreach ($form_data as $key => $value) {
        if (!isset($_POST[$key])) {
            $_POST[$key] = $value;
        }
    }
    
    // Call the existing free confirmation function
    cc_registration_free_confirmation();
	*/

    cc_debug_log_anything([
    	'function' => 'cc_registration_free_confirmation_with_token',
    	'token' => $token,
    	'post' => $_POST,
    	'form_data' => $form_data,
    ]);


    // assemble the payment recordlc
	$payment = cc_paymentdb_empty_payment();

    // Populate payment with training details
    if($form_data['training_type'] == 'r'){
        $payment['type'] = 'recording';
    } elseif($form_data['training_type'] == 's'){
        $payment['type'] = 'series';
    } elseif($form_data['training_type'] == 'g'){
        $payment['type'] = 'group';
    }
    
    // Basic training info
    if($form_data['training_id']){
        $payment['workshop_id'] = absint($form_data['training_id']);
    }
    if($form_data['event_id']){
        $payment['event_ids'] = sanitize_text_field($form_data['event_id']);
    }
    if($form_data['currency'] && in_array($form_data['currency'], cc_valid_currencies()) ){
        $payment['currency'] = $form_data['currency'];
    }
    if($form_data['student'] == 'yes'){
        $payment['student'] = 'y';
    }
    
    // Payment details
    $payment['payment_amount'] = 0; // Free training
    $payment['pmt_method'] = 'free';
    $payment['status'] = 'Payment not needed';
    
    // registrant details
    $payment['reg_userid'] = $user_id;
    $payment['email'] = $form_data['email'];
    $payment['firstname'] = $form_data['firstname'];
    $payment['lastname'] = $form_data['lastname'];
    $payment['phone'] = $form_data['phone'];
    
    // Address details
    $payment['address'] = cc_users_user_address_formdata( $form_data );
    
    // Source and other details
    if( isset( $form_data['source'] ) && $form_data['source'] <> '' ){
        $payment['source'] = substr($form_data['source'], 0, 255);
    }

    if( isset( $form_data['mailing_list'] ) && $form_data['mailing_list'] == 'yes' ){
        $payment['mailing_list'] = 'y';
    }elseif( cc_mailsterint_on_newsletter( $user->user_email ) ){
		$payment['mailing_list'] = 'p';
    }

    // VAT details if available
    if(isset($form_data['vat_exempt'])){
        $payment['vat_exempt'] = $form_data['vat_exempt'];
    }
    if(isset($form_data['vat_uk'])){
        $payment['vat_uk'] = substr($form_data['vat_uk'], 0, 25);
    }
    if(isset($form_data['vat_employ'])){
        $payment['vat_employ'] = substr($form_data['vat_employ'], 0, 25);
    }
    if(isset($form_data['vat_employer'])){
        $payment['vat_employer'] = substr($form_data['vat_employer'], 0, 255);
    }
    
    // Invoice details
    if(isset($form_data['inv_email'])){
        $payment['inv_email'] = substr($form_data['inv_email'], 0, 255);
    }
    if(isset($form_data['inv_phone'])){
        $payment['inv_phone'] = substr($form_data['inv_phone'], 0, 30);
    }
    if(isset($form_data['inv_ref'])){
        $payment['inv_ref'] = substr($form_data['inv_ref'], 0, 255);
    }
    if(isset($form_data['inv_org'])){
        $payment['inv_org'] = substr($form_data['inv_org'], 0, 255);
    }
    if(isset($form_data['inv_name'])){
        $payment['inv_name'] = substr($form_data['inv_name'], 0, 255);
    }
    
    // Invoice address
    if(isset($form_data['inv_addr1'])){
        $payment['inv_addr1'] = substr($form_data['inv_addr1'], 0, 255);
    }
    if(isset($form_data['inv_addr2'])){
        $payment['inv_addr2'] = substr($form_data['inv_addr2'], 0, 255);
    }
    if(isset($form_data['inv_town'])){
        $payment['inv_town'] = substr($form_data['inv_town'], 0, 255);
    }
    if(isset($form_data['inv_county'])){
        $payment['inv_county'] = substr($form_data['inv_county'], 0, 255);
    }
    if(isset($form_data['inv_postcode'])){
        $payment['inv_postcode'] = substr($form_data['inv_postcode'], 0, 255);
    }
    if(isset($form_data['inv_country'])){
        $payment['inv_country'] = substr($form_data['inv_country'], 0, 255);
    }
    
    // Discount/voucher codes
    if(isset($form_data['disc_code']) && $form_data['disc_amount'] > 0){
        $payment['disc_code'] = substr($form_data['disc_code'], 0, 25);
        $payment['disc_amount'] = (float) $form_data['disc_amount'];
    }
    if(isset($form_data['voucher_code']) && $form_data['voucher_amount'] > 0){
		if( substr( $form_data['voucher_code'], 0, 3) == 'CC-' ){
			$payment['voucher_code'] = substr( $form_data['voucher_code'], 0, 12 );
		}else{
			$payment['voucher_code'] = substr( cc_voucher_code_raw( $form_data['voucher_code'] ), 0, 12 );
		}
        $payment['voucher_amount'] = (float) $form_data['voucher_amount'];
    }
    
    // Group training notes
    if($form_data['training_type'] == 'g' && !empty($form_data['group_training'])){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter( $group_training );
        $group_training = array_unique( $group_training );
        if(!empty($group_training)){
	        // Build course details for notes
	        $course_details = array();
	        foreach($group_training as $training_id){
	            $course_title = get_the_title($training_id);
	            $course_details[] = $training_id . ': ' . $course_title;
	        }
            // Add group information to the main payment notes
            $payment['notes'] = 'Group training registration (' . count($group_training) . ' courses): ' . implode(', ', $course_details);
        }
    }
    
    // Manager email note
    if(isset($form_data['conf_email']) && $form_data['conf_email'] != ''){
        $existing_notes = $payment['notes'] ?? '';
        $manager_note = 'Manager email: ' . $form_data['conf_email'];
        $payment['notes'] = $existing_notes ? $existing_notes . '. ' . $manager_note : $manager_note;
    }

    // IP address
    $payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $payment['tandcs'] = 'y';


	// is this a duplicated payment (eg because the Internet is slow and JS got bored and sent it again)?
	// $duplicate_id = cc_paymentdb_dejavu($payment['reg_userid'], $payment['workshop_id'], $ccreg);
    $duplicate_id = cc_paymentdb_dejavu( get_current_user_id(), $payment['workshop_id'], $token);
	if($duplicate_id){
		// trigger an update, not insert
		$payment['id'] = $duplicate_id;
	}

	// updates payment and attendees and also the workshop/recording user tables
	$main_payment_id = cc_paymentdb_update_payment($payment, $token);
	$payment['id'] = $main_payment_id;

    // Get attendee count for stats
    $attendees = cc_attendees_for_payment($main_payment_id);
    $count_attendees = count($attendees);

    // what course are included in this registration?
    $child_training_ids = array();
    if( $payment['type'] == 'group' ){
        $group_training = array_map('absint', (array) $form_data['group_training']);
        $group_training = array_filter($group_training);
        $group_training = array_unique($group_training);
        $child_training_ids = $group_training;
        $series_discount = 0;
    }elseif( $payment['type'] == 'series' ){
        $series_id = $payment['workshop_id'];
        $series_courses = get_post_meta( $series_id, '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        $child_training_ids = $series_courses;
		$series_discount = (float) get_post_meta( $form_data['training_id'], '_series_discount', true);
    }

    // create individual payment records for groups and series
    if( ! empty( $child_training_ids ) ){
	    global $wpdb;
	    $table_name = $wpdb->prefix . 'ccpa_payments';

        // Get actual prices for each course (usually going to be zero!)
        $course_prices = [];
        $total_course_prices = 0;
        $now = time();
            
        foreach($child_training_ids as $training_id){
            // Get the individual course price
            $pricing = get_training_prices( $training_id );

		    $currency_lc = strtolower($payment['currency']);
			$course_price = $pricing['price_'.$currency_lc];

			$total_payable = $course_price * $count_attendees;

            // Apply student discount if applicable
            $student_discount = 0;
            if( $payment['student'] == 'y' && $pricing['student_discount'] > 0 ){
				$student_discount = round( $course_price * $pricing['student_discount'] / 100, 2 );
				$student_discount = $student_discount * $count_attendees;
				if( $student_discount > $total_payable ){
					$student_discount = $total_payable;
					$total_payable = 0;
				}else{
					$total_payable = $total_payable - $student_discount;
				}
            }

            // or early bird discount
            $early_bird_name = '';
            $early_bird_discount = 0;
			if( $pricing['early_bird_discount'] > 0 && $pricing['early_bird_expiry'] <> '' ){
				$expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'] );
				if( $expiry_date ){
					if( $expiry_date->getTimestamp() > $now ){
						$early_bird_name = ( $pricing['early_bird_name'] == '' ) ? 'Early bird' : $pricing['early_bird_name'];
						$early_bird_discount = round( $course_price * $pricing['early_bird_discount'] / 100, 2 );
						$early_bird_discount = $early_bird_discount * $count_attendees;
						if( $early_bird_discount > $total_payable ){
							$early_bird_discount = $total_payable;
							$total_payable = 0;
						}else{
							$total_payable = $total_payable - $early_bird_discount;
						}
					}
				}
			}

			// if it's a series, apply the series discount
			if( $series_discount > 0 ){
				$series_disc_amt = round( $course_price * $series_discount / 100, 2 );
				$series_disc_amt = $series_disc_amt * $count_attendees;
				if( $series_disc_amt > $total_payable ){
					$series_disc_amt = $total_payable;
					$total_payable = 0;
				}else{
					$total_payable = $total_payable - $series_disc_amt;
				}
			}else{
				$series_disc_amt = 0;
			}

			// if it's a portal user, discount the rest
			// shouldn't be here really!!
			$portal_user = get_user_meta( $user_id, 'portal_user', true );
			if( $portal_user <> '' ){
				$portal_discount = $total_payable;
				$total_payable = 0;
				$portal_disc_code = strtoupper( $portal_user );
			}else{
				$portal_discount = 0;
				$portal_disc_code = '';
			}

			// vat
			$vat = ( $payment['vat_exempt'] == 'y' ) ? 0 : ccpa_vat_amount( $total_payable );
			$total_payable = $total_payable + $vat;
            
            // store it
            $course_prices[$training_id] = [
            	'course_price' => $course_price,
            	'total_payable' => $total_payable,
            	'student_discount' => $student_discount,
            	'early_bird_name' => $early_bird_name,
            	'early_bird_discount' => $early_bird_discount,
            	'series_discount' => $series_disc_amt,
            	'portal_discount' => $portal_discount,
            	'portal_disc_code' => $portal_disc_code,
            	'vat_included' => $vat,
            ];
        }

        // Create individual course records (linked to main payment)
        $course_payment_ids = [];
        $running_total = 0;

        foreach( $child_training_ids as $index => $training_id ){
            // Clone the payment array for each course
            $course_payment = $payment;

            // Set key fields for the course record
            $course_payment['id'] = 0; // Force new record
            $course_payment['payment_ref'] = $main_payment_id; // Link to main payment
            
            // Clear fields that should only be on the main payment
            $course_payment['payment_intent_id'] = '';
            $course_payment['charge_id'] = '';
            $course_payment['stripe_fee'] = 0;

            // Set the specific workshop ID for this record
            $course_payment['workshop_id'] = $training_id;

            // Set type based on training type
            if( course_training_type( $training_id ) == 'recording' ){
            	$course_payment['type'] = 'recording';
            }else{
            	$course_payment['type'] = '';
            }

            // financials
            $course_payment['payment_amount'] = $course_prices[$training_id]['total_payable'];
            if( $course_prices[$training_id]['student_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['student_discount'];
            	$course_payment['disc_code'] = 'student';
            }
            if( $course_prices[$training_id]['early_bird_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['early_bird_discount'];
            	$course_payment['disc_code'] = $course_prices[$training_id]['early_bird_name'];
            }
            if( $course_prices[$training_id]['series_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['series_discount'];
            	$course_payment['disc_code'] = 'series';
            }
            if( $course_prices[$training_id]['portal_discount'] > 0 ){
            	$course_payment['disc_amount'] = $course_prices[$training_id]['portal_discount'];
            	$course_payment['disc_code'] = $course_prices[$training_id]['portal_disc_code'];
            }
            $course_payment['vat_included'] = $course_prices[$training_id]['vat_included'];

            // Add course-specific note linking to main payment
			$course_payment['notes'] = sprintf(
			    'Course record for %s payment %d - Course %d of %d: %d: %s',
			    $payment['type'],
                $main_payment_id,
			    ($index + 1),
			    count($child_training_ids),
			    $training_id,
			    get_the_title($training_id),
			);
            
            // Set a different status to indicate this is a linked record
            $course_payment['status'] = 'Linked to #' . $main_payment_id;

            $course_payment['token'] = '';

            // Ensure we have required fields
            $course_payment['last_update'] = date('Y-m-d H:i:s');
            $course_payment['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

            // DIRECT DATABASE INSERT - bypassing cc_paymentdb_update_payment
            $result = $wpdb->insert($table_name, $course_payment);

            if($result !== false){
                $course_payment_id = $wpdb->insert_id;
                $course_payment['id'] = $course_payment_id;
                $course_payment_ids[] = $course_payment_id;
                
                // Copy attendees from main payment to this course payment
                // This is crucial for cc_myacct_insert_workshops_recordings_users to work
                foreach($attendees as $attendee){
                    // Only pass the fields that exist in cc_attendees table
                    $attendee_data = array(
                        'payment_id' => $course_payment_id,
                        'user_id' => $attendee['user_id'] ?? 0,
                        'registrant' => $attendee['registrant'] ?? '',
                    );
                    cc_attendee_add($attendee_data);
                }

                // Now explicitly call cc_myacct_insert_workshops_recordings_users
                // This function will now find the attendees we just added and create
                // the appropriate workshops_users or recordings_users entries
                cc_myacct_insert_workshops_recordings_users($course_payment);
                
                // If it's a recording, also give recording access via user meta
                if($course_payment['type'] == 'recording'){
                    $access_type = 'free';
                    $course_amount = 0;
                    $vat_proportion = 0;
                    foreach($attendees as $attendee){
                        if(isset($attendee['user_id']) && $attendee['user_id'] > 0){
                            ccrecw_add_recording_to_user(
                                $attendee['user_id'], 
                                $training_id, 
                                $access_type, 
                                $course_amount,
                                '', 
                                $payment['currency'], 
                                $vat_proportion,
                                $course_payment_id
                            );
                        }
                    }
                }

				// Update popularity stats for each course
                cc_training_popularity_update($training_id, $count_attendees);
                
                // STEP 5: Record in training groups table for tracking
                if( $payment['type'] == 'group' ){
	                cc_training_groups_record_training($main_payment_id, $training_id);
	            }
            }
    	}

    	// could update the main payment record with the IDs of the children at this point

    }elseif( $payment['type'] <> 'group' ){
    	// workshop, recording or series

        // now process a single training
        if( $payment['type'] == 'recording' || $payment['type'] == '' ){
			// Update popularity stats
	        cc_training_popularity_update( $payment['workshop_id'], $count_attendees );
	    }

	    // Give recording access to ALL attendees (not just registrant)
	    if($payment['type'] == 'recording'){
	        $access_type = 'free';
	        // Give recording access to each attendee
	        foreach ($attendees as $attendee) {
	            ccrecw_add_recording_to_user(
	                $attendee['user_id'], 
	                $payment['workshop_id'], 
	                $access_type, 
	                0, 
	                '', 
	                $payment['currency'], 
	                0, 
	                $main_payment_id
	            );
	        }
	    }

    }

    // Handle new user notification
    if($is_new_user){
        // Set new user flags
        update_user_meta($user_id, 'new_password', '');
        update_user_meta($user_id, 'force_new_password', 'yes');
    }

	// if a refer a friend code was used, record the fact and allocate a credit to the code giver ... also blocks the recipient from using it a second time
	if( substr( $payment['disc_code'], 0, 3 ) == 'CC-' && !$duplicate_id ){
		cc_friend_code_used( $payment['disc_code'], $payment['reg_userid'], $payment['currency'], $payment['disc_amount'] );
	}

	if( $payment['voucher_code'] <> '' && $payment['voucher_amount'] > 0 ){
		if( substr($payment['voucher_code'], 0, 3) == 'CC-' ){
			// refer a friend redemption
			cc_friend_redeem( $payment['voucher_code'], $payment['reg_userid'], $payment['currency'], $payment['voucher_amount'] );
		}else{
			ccpa_voucher_usage_record($payment['voucher_code'], $payment['voucher_amount'], $payment['currency'], $main_payment_id);
		}
	}

	// save last registration info (for CNWL people really)
	update_user_meta($user->ID, 'last_registration', date('Y-m-d H:i:s'));
	update_user_meta($user->ID, 'last_reg_id', $main_payment_id);

	// add them to the mailing list if needed
    if($payment['mailing_list'] == 'y'){
        update_user_meta($user->ID, 'mailing_list', 'y');
        cc_mailsterint_newsletter_subscribe($user);
    }

    cc_mailsterint_update_region($user);

    // Update temp registration status
    TempRegistration::update_status($token, 'completed');

	// send out the emails
	// if it's a duplicate they may get the emails twice
	// cc_mailsterint_send_email($payment);
	cc_mailsterint_send_reg_emails( $payment );

	// does the payment result in a voucher being sent?
	if(!$duplicate_id){
		ccpa_vouchers_alloc_maybe($payment);
	}

	// Also update the popularity stats - get actual attendee count from database
	if(!$duplicate_id && $main_payment_id){
	    $attendees = cc_attendees_for_payment($main_payment_id);
	    $count_attendees = count($attendees);
	    cc_training_popularity_update($payment['workshop_id'], $count_attendees);
	    if($payment['upsell_workshop_id'] > 0){
	        cc_training_popularity_update($payment['upsell_workshop_id'], $count_attendees);
	    }
	}

	/*
	if( !$duplicate_id ){
		cc_training_popularity_update( $payment['workshop_id'], $count_attendees );
		if( $payment['upsell_workshop_id'] > 0 ){
			cc_training_popularity_update( $payment['upsell_workshop_id'], $count_attendees );
		}
	}
	*/

	// $funny_number = ( $main_payment_id + 987834 ) * 2;
	// $conf_url = add_query_arg( array( 'c' => $funny_number ), site_url( '/reg-conf/' ));
	$conf_url = add_query_arg( array( 'token' => $token ), site_url( '/reg-conf' ) );
	wp_redirect( $conf_url );
	exit;
}




// gets the 'next' btn for the registration page
// could take you on to payment details or to finalise the registration
function cc_registration_next_btn( $portal_user, $reg_type ){
	// the hidden fields are necessary as we are forcing the form submission using JS, which does not send the sibmit button!
	if( $portal_user <> '' ){
		return '<input type="hidden" name="reg-org-submit" value="go"><button type="submit" id="reg-org-submit" class="btn btn-primary reg-next-submit">Confirm registration</button>';
	}
	if( $reg_type == 'free' ){
		return '<input type="hidden" name="reg-conf-submit" value="go"><button type="submit" id="reg-conf-submit" class="btn btn-primary reg-next-submit">Confirm registration</button>';
	}
	return '<button type="submit" id="reg-next-submit" class="btn btn-primary reg-next-submit">Next: Payment details</button>';
}


function handle_registration_with_back_button_check() {
    // Check for back button BEFORE running handle_registration_page_load
    if (is_back_button_navigation()) {
        // Clear any existing token
        if (isset($_GET['token'])) {
            TempRegistration::delete($_GET['token']);
        }
        wp_redirect(home_url('/act-therapy-training'));
        exit;
    }
    
    // Normal flow
    return handle_registration_page_load();
}

function is_back_button_navigation() {
    if (empty($_POST) && isset($_GET['token'])) {
        $registration_data = TempRegistration::get($_GET['token']);
        
        if ($registration_data) {
            $current_step = (int)$registration_data->current_step;
            
            // If we're on step 1 page but current_step > 1, it's back navigation
            if ($current_step > 1) {
                return true;
            }
        }
    }
    
    return false;
}

function handle_registration_page_load() {
    // Check if we have POST data (new registration) or GET token (returning user)
    $token = $_GET['token'] ?? null;
    $returning_user = !empty($token);
    
    if (!$returning_user && !empty($_POST)) {
        // New registration - create temp record
        $token = TempRegistration::create_from_post($_POST);
        
        // Redirect to clean URL with token
        $redirect_url = add_query_arg([
            'token' => $token,
            'step' => 1
        ], get_permalink());
        
        wp_redirect($redirect_url);
        exit;
        
    } elseif ($returning_user) {
        // Load existing registration data
        $registration_data = TempRegistration::get($token);
        if (!$registration_data || $registration_data->status <> 'active') {
            // Handle expired/invalid token
            wp_redirect(home_url('/act-therapy-training'));
            exit;
        }
    } else {
        // No data - redirect back to course selection
        wp_redirect(home_url('/act-therapy-training'));
        exit;
    }
    
    return $token;
}


// save the extra panel data from the registration page
add_action('wp_ajax_reg_step_one', 'registration_step_one_data_update');
add_action('wp_ajax_nopriv_reg_step_one', 'registration_step_one_data_update');
// Fix for registration_step_one_data_update function in registration.php
// Add this after the existing attendee processing
function registration_step_one_data_update() {
    $request_body = file_get_contents('php://input');
    $request_data = json_decode($request_body, true);
    
    $token = isset($request_data['token']) ? sanitize_text_field($request_data['token']) : '';
    $step = intval($request_data['step'] ?? 1);
    $data = $request_data['formsData'] ?? [];

    cc_debug_log_anything([
    	'function' => 'registration_step_one_data_update',
    	'token' => $token,
    	'step' => $step,
    	'request_data' => (array) $request_data,
    ]);

    // ccpa_write_log('Received data for token: ' . $token);
    // ccpa_write_log('Forms data: ' . json_encode($data));
    
    if (!$token) {
        wp_send_json_error('Invalid token');
        return;
    }
    
    // Get existing registration data
    $existing_registration = TempRegistration::get($token);
    if (!$existing_registration) {
        wp_send_json_error('Registration session not found');
        return;
    }
    
    // Prepare data for update - attendees should already be in standard format from JS
    $update_data = array();
    
    // Handle data from form 1
    $more_fields = array( 'source', 'mailing_list', 'conf_email', 'job' );
    foreach ($more_fields as $more_field) {
    	if( isset( $data[$more_field] ) && $data[$more_field] <> '' ){
    		$update_data[$more_field] = stripslashes( sanitize_text_field( $data[$more_field] ) );
    	}
    }
    if( isset( $data['job_id'] ) && $data['job_id'] <> '' ){
    	$update_data['job'] = stripslashes( sanitize_text_field( $data['job_id'] ) );
    }

    // Not sure we need this ...
    if (isset($data['attend_type'])) {
        $update_data['attend_type'] = sanitize_text_field($data['attend_type']);
        // ccpa_write_log('Saving attend_type: ' . $data['attend_type']);
    }
    
    // Handle attendees data - should be in standard format already
    if (isset($data['attendees'])) {
        // Validate that it's in the expected standard format
        if (is_array($data['attendees'])) {
            $valid_attendees = array();
            foreach ($data['attendees'] as $attendee) {
                if (is_array($attendee) && 
                    isset($attendee['email']) && 
                    isset($attendee['firstname']) && 
                    isset($attendee['registrant'])) {
                    
                    $valid_attendees[] = array(
                        'user_id' => intval($attendee['user_id'] ?? 0),
                        'registrant' => sanitize_text_field($attendee['registrant']),
                        'firstname' => sanitize_text_field($attendee['firstname']),
                        'lastname' => sanitize_text_field($attendee['lastname'] ?? ''),
                        'email' => sanitize_email($attendee['email'])
                    );
                }
            }
            
            if (!empty($valid_attendees)) {
                $update_data['attendees'] = $valid_attendees;
                // ccpa_write_log('Valid standardized attendees: ' . count($valid_attendees));
            }
        }
    }
    
    // Enhanced debugging
    // ccpa_write_log('Update data being saved:');
    // ccpa_write_log(json_encode($update_data));
    
    // Update the registration data
    $success = TempRegistration::update_form_data($token, $step, $update_data);
    
    if ($success) {
        // Update the step to 2 since registration is complete and moving to payment
        $step_updated = TempRegistration::update_step($token, 2);

        $updated_data = TempRegistration::get_form_data($token);
        // ccpa_write_log('Update successful. Final data:');
        // ccpa_write_log('Attendees count: ' . count($updated_data['attendees'] ?? []));
        // ccpa_write_log('Attend type: ' . ($updated_data['attend_type'] ?? 'not set'));
        
        wp_send_json_success([
            'message' => 'Registration data updated successfully',
            'current_data' => $updated_data,
            'step' => $step
        ]);
    } else {
        // ccpa_write_log('Update failed');
        wp_send_json_error('Failed to update registration data');
    }
    
    // ccpa_write_log('=== END registration_step_one_data_update ===');
}



// Save payment details data from the payment details page
add_action('wp_ajax_reg_step_two', 'registration_step_two_data_update');
add_action('wp_ajax_nopriv_reg_step_two', 'registration_step_two_data_update');
function registration_step_two_data_update() {
    // ccpa_write_log('=== START registration_step_two_data_update ===');
    
    // Get raw JSON input
    $json_input = file_get_contents('php://input');
    // ccpa_write_log('Raw JSON input: ' . $json_input);
    
    if (empty($json_input)) {
        // ccpa_write_log('ERROR: No JSON data received');
        wp_send_json_error('No data received');
        return;
    }
    
    $request_data = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // ccpa_write_log('JSON decode error: ' . json_last_error_msg());
        wp_send_json_error('Invalid JSON data');
        return;
    }
    
    // ccpa_write_log('Decoded request data:');
    // ccpa_write_log($request_data);
    
    $token = $request_data['token'] ?? '';
    $step = intval($request_data['step'] ?? 2);
    $payment_data = $request_data['paymentData'] ?? [];

    cc_debug_log_anything([
    	'function' => 'registration_step_two_data_update',
    	'token' => $token,
    	'step' => $step,
    	'request_data' => (array) $request_data,
    ]);

    // ccpa_write_log('Extracted - Token: ' . $token . ', Step: ' . $step);
    // ccpa_write_log('Payment data:');
    // ccpa_write_log($payment_data);
    
    if (!$token) {
        // ccpa_write_log('ERROR: No token provided');
        wp_send_json_error('Invalid token');
        return;
    }
    
    // Get existing registration data
    $existing_registration = TempRegistration::get($token);
    if (!$existing_registration || $existing_registration->status === 'expired') {
        // ccpa_write_log('ERROR: Registration session expired or not found');
        wp_send_json_error('Registration session not found');
        return;
    }

    $existing_form_data = json_decode($existing_registration->form_data, true);
    
    // FIX: Create a NEW array with ONLY the payment data that should be updated
    // Don't include the existing_form_data which contains course IDs
    $payment_updates = [
        // Currency and pricing - directly from JavaScript
        'currency' => in_array($payment_data['currency'], cc_valid_currencies()) ? $payment_data['currency'] : 'GBP',
        // 'raw_price' => floatval($payment_data['raw_price'] ?? 0),
        'disc_amount' => floatval($payment_data['disc_amount'] ?? 0),
        'voucher_amount' => floatval($payment_data['voucher_amount'] ?? 0),
        // 'vat_amount' => floatval($payment_data['vat_amount'] ?? 0),
        // 'tot_pay' => floatval($payment_data['tot_pay'] ?? 0),
        // 'total_payable' => floatval($payment_data['total_payable'] ?? 0),
        
        // VAT fields
        'vat_uk' => sanitize_text_field($payment_data['vat_uk'] ?? ''),
        'vat_employ' => sanitize_text_field($payment_data['vat_employ'] ?? ''),
        'vat_employer' => sanitize_text_field($payment_data['vat_employer'] ?? ''),
        'vat_exempt' => sanitize_text_field($payment_data['vat_exempt'] ?? 'n'),
        
        // Upsell
        'upsell_selected' => sanitize_text_field($payment_data['upsell_selected'] ?? 'no'),
        'upsell_workshop_id' => intval($payment_data['upsell_workshop_id'] ?? 0),
        
        // Codes
        'disc_code' => sanitize_text_field($payment_data['disc_code'] ?? ''),
        'voucher_code' => sanitize_text_field($payment_data['voucher_code'] ?? ''),
        
        // Additional fields
        // 'conf_email' => sanitize_email($payment_data['conf_email'] ?? ''),
        // 'mailing_list' => sanitize_text_field($payment_data['mailing_list'] ?? ''),
        // 'source' => sanitize_text_field($payment_data['source'] ?? ''),
    ];
    
    /*
    // Calculate number of attendees for total pricing
    $num_attendees = 1;
    if (isset($existing_form_data['attendees']['attend_type']) && 
        $existing_form_data['attendees']['attend_type'] == 'group' && 
        isset($existing_form_data['attendees']['attend_email'])) {
        $num_attendees = count($existing_form_data['attendees']['attend_email']);
    }
    
    // Adjust totals for multiple attendees if needed
    if ($num_attendees > 1) {
        $existing_form_data['vat_amount'] = $existing_form_data['vat_amount'] * $num_attendees;
        $existing_form_data['disc_amount'] = $existing_form_data['disc_amount'] * $num_attendees;
        // $existing_form_data['voucher_amount'] = $existing_form_data['voucher_amount'] * $num_attendees;
        $existing_form_data['total_payable'] = $existing_form_data['total_payable'] * $num_attendees;
    }
    */
    
    // ccpa_write_log('Updated form data:');
    // ccpa_write_log($existing_form_data);
    
    // Save ONLY the payment data - this won't duplicate course IDs
    $success = TempRegistration::update_form_data($token, $step, $payment_updates);

    // ccpa_write_log('Update result: ' . ($success ? 'SUCCESS' : 'FAILED'));
    
    if ($success) {
        // Update the step to 3 since pmt dets is complete and moving to payment
        $step_updated = TempRegistration::update_step($token, 3);

        // Get the updated data to verify
        // $updated_data = TempRegistration::get_form_data($token);
        // ccpa_write_log('Final updated form data:');
        // ccpa_write_log($updated_data);
        
        wp_send_json_success([
            'message' => 'Payment details saved successfully',
            // 'current_data' => $updated_data,
            'step' => $step
        ]);
    } else {
        wp_send_json_error('Failed to save payment details');
    }
    
    // ccpa_write_log('=== END registration_step_two_data_update ===');
}

// Process final payment - NEW AJAX handler for step 3
add_action('wp_ajax_reg_step_three', 'registration_step_three_payment');
add_action('wp_ajax_nopriv_reg_step_three', 'registration_step_three_payment');
function registration_step_three_payment() {
    $token = $_POST['token'] ?? '';
    
    if (empty($token)) {
        wp_send_json_error('Invalid token');
        return;
    }
    
    // Get registration data
    $registration_data = TempRegistration::get($token);
    if (!$registration_data) {
        wp_send_json_error('Registration session not found');
        return;
    }
    
    $form_data = json_decode($registration_data->form_data, true);
    
    // Process payment using existing logic but with token-based data
    $response = cc_payment_record_payment_core_with_token($token, $form_data);
    
    if ($response['status'] == 'ok') {
        // Clear the temp registration on successful payment
        TempRegistration::delete($token);
    }
    
    wp_send_json($response);
}


// extra T&Cs panel on the registration/payment pages
function cc_registration_tncs_panel(){
	return '
		<div class="animated-card">
			<div class="animated-card-inner reg-panel pale-bg">
				By completing this registration, you are agreeing to the <a href="/training-terms-and-conditions" target="_blank">training terms and conditions</a>.
			</div>
		</div>
	';
}