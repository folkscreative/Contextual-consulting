<?php
/**
 * Prize Draw Processing
 */

add_shortcode( 'prize_draw_form', 'cc_prize_draw_form' );
function cc_prize_draw_form( $atts ){
	$atts = shortcode_atts( array(
		'terms' => '',
	), $atts );

	$firstname = $lastname = $email = $region = '';

	$show_nletter_field = true;
	if( is_user_logged_in() ){
		$user = wp_get_current_user();
		if( $user ){
			$firstname = $user->user_firstname;
			$lastname = $user->user_lastname;
			$email = $user->user_email;
			$region = cc_mailster_get_region( 0, $email );
			if( cc_mailsterint_on_newsletter( $user->user_email ) ){
				$show_nletter_field = false;
			}
		}
	}

	if( $atts['terms'] == '' ){
		$terms_link = 'terms and conditions';
	}else{
		$terms_link = '<a href="'.esc_url( $atts['terms'] ).'" target="_blank">terms and conditions</a>';
	}

	$html = '
		<form id="cc-prize-draw-form" action="" method="post" class="needs-validation row" novalidate>
			<input type="hidden" name="action" value="prize_draw_entry">
			<div class="col-md-6">
				<label for="ccpd-first" class="form-label">First name: *</label>
				<input type="text" class="form-control form-control-lg cc-prize-draw-field" id="ccpd-first" name="firstname" value="'.$firstname.'" required>
				<div class="invalid-feedback">Your first name is required</div>
			</div>
			<div class="col-md-6">
				<label for="ccpd-last" class="form-label">Last name: *</label>
				<input type="text" class="form-control form-control-lg cc-prize-draw-field" id="ccpd-last" name="lastname" value="'.$lastname.'" required>
				<div class="invalid-feedback">Your last name is required</div>
			</div>
			<div class="col-md-12">
				<label for="ccpd-email" class="form-label">Email: *</label>
				<input type="email" class="form-control form-control-lg cc-prize-draw-field" id="ccpd-email" name="email" value="'.$email.'" required>
				<div class="invalid-feedback">Your email is required</div>
			</div>
			<div class="col-md-6">
				<label for="ccpd-region" class="form-label">Your region: *</label>
				<select name="region" id="ccpd-region" class="form-select form-select-lg cc-prize-draw-field" required>
					<option value="">Please select</option>
					<option value="UK" '.selected( $region, "UK", false ).'>UK</option>
					<option value="Aus & NZ" '.selected( $region, "Aus & NZ", false ).'>Aus. / N.Z.</option>
					<option value="USA & Can" '.selected( $region, "USA & Can", false ).'>USA / Can.</option>
					<option value="Rest of World" '.selected( $region, "Rest of World", false ).'>Rest of World</option>
				</select>
				<div class="invalid-feedback">Your region is required</div>
			</div>
			<div class="col-md-6"></div>';
	if( $show_nletter_field ){
		$html .= '
			<div class="col-md-12">
				<div class="row cc-prize-draw-nl-wrap mt-4">
					<div class="col-lg-6">
						<label for="ccpd-newsletter" class="form-label">Join our newsletter:</label>
						<select name="newsletter" id="ccpd-newsletter" class="form-select form-select-lg" aria-describedby="nletter-help" required>
							<option value="">Please select</option>
							<option value="yes">Yes</option>
							<option value="no">No</option>
						</select>
						<div class="invalid-feedback">Please answer Yes or No</div>
						<div id="nletter-help" class="form-text">This will not affect the prize draw. You can unsubscribe from any email we send you.</div>
					</div>
					<div class="col-lg-6 pt-4">
						<span class="form-text">Be the first to receive updates on our upcoming events, exclusive free resources and other valuable goodies. Sign up now and embark on your ACT journey with us!</span>
					</div>
				</div>
			</div>';
	}
	$html .= '
			<div class="col-md-12">
				<label class="form-label">Terms and conditions</label>
				<p class="form-text">By submitting your entry into this prize draw you agree to the '.$terms_link.' of this prize draw</p>
			</div>';

	$already_entered = false;
	if( $firstname <> '' && $lastname <> '' && $email <> '' && $region <> '' ){
		// we have all their data ... are they already in the prize draw?
		$prize_draw_list_id = cc_mailster_list_id_by_name( 'Prize draw Jun 2024' );
		$subscriber = mailster('subscribers')->get_by_mail( $user->user_email );
		if( $subscriber ){
			// already entered?
			if( cc_mailsterint_subs_on_list( $subscriber->ID, $prize_draw_list_id ) ){
				$already_entered = true;
			}
		}
	}

	if( $already_entered ){
		$html .= '
			<div id="cc-prize-draw-form-msg" class="col-md-12"><p class="m-5 text-center bg-success p-3 text-white">You have already been entered into the prize draw, '.$firstname.'</p></div>
		';
	}else{
		$html .= '
			<div class="col-md-12 text-end">
				<button type="submit" class="btn btn-primary btn-lg">Enter the draw</button>
			</div>
			<div id="cc-prize-draw-form-msg" class="col-md-12"></div>
		';
	}

	$html .= '</form>';
	return $html;
}

// handle a prize draw entry
add_action( 'wp_ajax_prize_draw_entry', 'cc_prize_draw_entry' );
add_action( 'wp_ajax_nopriv_prize_draw_entry', 'cc_prize_draw_entry' );
function cc_prize_draw_entry(){
	$response = array(
		'status' => 'error',
		'msg' => 'Incomplete details - please try again',
	);

	$userdata = array(
		'email' => '',
		'firstname' => '',
		'lastname' => '',
		'region' => '',
	);

	if( isset( $_POST['firstname'] ) ){
		$userdata['firstname'] = stripslashes( sanitize_text_field( $_POST['firstname'] ) );
	}
	if( isset( $_POST['lastname'] ) ){
		$userdata['lastname'] = stripslashes( sanitize_text_field( $_POST['lastname'] ) );
	}
	if( isset( $_POST['email'] ) ){
		$userdata['email'] = stripslashes( sanitize_email( $_POST['email'] ) );
	}
	if( isset( $_POST['region'] ) ){
		$userdata['region'] = stripslashes( sanitize_text_field( $_POST['region'] ) );
	}

	$newsletter = false;
	if( isset( $_POST['newsletter'] ) && $_POST['newsletter'] == 'yes' ){
		$newsletter = true;
	}

	if( $userdata['email'] <> '' && $userdata['firstname'] <> '' && $userdata['lastname'] <> '' && $userdata['region'] <> '' ){
		// valid data
		$prize_draw_list_id = cc_mailster_list_id_by_name( 'Prize draw Jun 2024' );
		$subscriber = mailster('subscribers')->get_by_mail( $userdata['email'] );
		if( $subscriber ){
			$subscriber_id = $subscriber->ID;
			// already entered?
			if( cc_mailsterint_subs_on_list( $subscriber_id, $prize_draw_list_id ) ){
				$response['msg'] = 'You have already been entered into the prize draw, '.$userdata['firstname'].'';
			}else{
				mailster('subscribers')->assign_lists( $subscriber_id, $prize_draw_list_id );
				$response['msg'] = 'You have been successfully entered into the prize draw, '.$userdata['firstname'].' - Good luck!';
			}
			$result = mailster('subscribers')->update( $userdata );
		}else{
	        $overwrite = true;
	        $subscriber_id = mailster('subscribers')->add( $userdata, $overwrite );
			mailster('subscribers')->assign_lists( $subscriber_id, $prize_draw_list_id );
			$response['msg'] = 'You have been successfully entered into the prize draw, '.$userdata['firstname'].' - Good luck!';
		}
		$response['status'] = 'ok';
	}

   	echo json_encode($response);
	die();
}

