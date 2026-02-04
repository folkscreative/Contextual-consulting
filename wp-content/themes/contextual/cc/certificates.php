<?php
/**
 * Certificates
 */

// are certificates available for this training?
// returns bool
// training course certs are just at course level
function cc_certs_training( $training_id, $event_id=0 ){
	if( $event_id > 0 ){
		$meta_prefix = 'event_'.$event_id.'_';
	}else{
		$meta_prefix = '';
	}
	$cert_cc = cc_certs_setting( $training_id, $event_id, 'cc' );
	if( $cert_cc == 'yes' ) return true;
	$ce_credits = get_post_meta( $training_id, $meta_prefix.'ce_credits', true );
	if( is_numeric( $ce_credits ) && $ce_credits > 0 ){
		$other_certs = array( 'apa', 'bacb', 'nbcc', 'icf' );
		foreach ($other_certs as $other_cert) {
			$this_cert = cc_certs_setting( $training_id, $event_id, $other_cert );
			if( $this_cert == 'yes' ){
				return true;
			}
		}
	}
	return false;
}

// any extra requirements for this training/user
// will be shown on the my account panels
function cc_certs_extra_requirements( $training_id, $event_id, $user_id ){
	$response = array(
		'html' => '',
		'bacb' => '',
	);
	if( $event_id > 0 ){
		$meta_prefix = 'event_'.$event_id.'_';
	}else{
		$meta_prefix = '';
	}
	// BACB requires a Participant BACB Certification Number
	$ce_credits = get_post_meta( $training_id, $meta_prefix.'ce_credits', true );
	if( is_numeric( $ce_credits ) && $ce_credits > 0 && $ce_credits == round( $ce_credits, 1 ) ){
		$cert_bacb = cc_certs_setting( $training_id, $event_id, 'bacb' );
		if( $cert_bacb == 'yes' ){
			$user_bacb_cert_num = get_user_meta( $user_id, 'bacb_num', true );
			if( $user_bacb_cert_num == '' ){
				$response['html'] .= ' If you require a BACB Certificate, please enter your Participant BACB Certification Number into <a href="/my-account?my=details">your profile details</a> and then return here.';
				$response['bacb'] = 'no';
			}else{
				$response['bacb'] = 'yes';
			}
		}
	}
	return $response;
}

// the button to launch the certificate chosen for this training
function cc_certs_button( $training_id, $event_id, $user_id, $bacb_ok ){
	// first lets find out which certs are to be offered
	$certs_w = array(
		'w' => false,
		'a' => false,
		'b' => false,
		'n' => false,
		'i' => false,
	);
	$certs_r = array(
		'r' => false,
		'a' => false,
		'b' => false,
		'n' => false,
		'i' => false,
	);

	$training_type = get_post_type( $training_id );

	if( $training_type == 'workshop' && $event_id > 0 ){
		$meta_prefix = 'event_'.$event_id.'_';
	}else{
		$meta_prefix = '';
	}
	$count_certs_w = $count_certs_r = 0;

	// cc certs
	if( $training_type == 'workshop' ){
		$cert_cc = cc_certs_setting( $training_id, $event_id, 'cc' );
		if( $cert_cc == 'yes' ){
			$certs_w['w'] = true;
			$count_certs_w ++;
		}
	}else{
		$cert_cc = cc_certs_setting( $training_id, 0, 'cc' );
		if( $cert_cc == 'yes' ){
			$certs_r['r'] = true;
			$count_certs_r ++;
		}
	}

	// workshop with linked recording that the user has had access to?
	$wshop_rec_id = 0;
	if( $training_type == 'workshop' && $event_id == 0 ){
		$wshop_rec_id = (int) get_post_meta( $training_id, 'workshop_recording', true );
	    if( $wshop_rec_id > 0 ){
	    	$recording_cert_cc = cc_certs_setting( $wshop_rec_id, 0, 'cc' );
	    	$recording_access = ccrecw_user_can_view( $wshop_rec_id );
		    if( ! $recording_access['access'] && $recording_access['expiry_date'] == '' ){
		        // has not had access to the recording ... don't offer a recording cert
		        $recording_cert_cc = 'no';
		    }
	        if( $recording_cert_cc <> 'no' ){
	            if( substr( $recording_cert_cc, 0, 8 ) == 'https://' || substr( $recording_cert_cc, 0, 7 ) == 'http://' ){
	                $rec_cert_url = $recording_cert_cc;
	            }else{
	                $cert_parms = ccpdf_recording_cert_parms_encode( $wshop_rec_id, $user_id  );
	                $rec_cert_url = add_query_arg( array( 'c' => $cert_parms ), site_url( '/certificate/' ) );
	            }
	            $certs_r['r'] = true;
	            $count_certs_r ++;
	        }else{
		        $wshop_rec_id = 0; // so we don't offer it later
	        }
	    }
	}

	//  the other certs all need credits, feedback and, for a recording, quiz completion (or override)
	$ce_credits = get_post_meta( $training_id, $meta_prefix.'ce_credits', true );
	if( is_numeric( $ce_credits ) && $ce_credits > 0 ){
		$cert_types = array( 'apa', 'bacb', 'nbcc', 'icf' );
		foreach ($cert_types as $cert_type) {
			// $cert_switch = cc_certs_setting( $training_id, $event_id, $cert_type );
			// if( $cert_switch == 'yes' ){
			if( cc_certs_offer_this_cert( $cert_type, $training_id, $event_id, $user_id ) ){
				$cert_letter = substr( $cert_type, 0, 1 );
				if( $training_type == 'workshop' ){
					$certs_w[$cert_letter] = true;
					$count_certs_w ++;
					if( $wshop_rec_id > 0 ){
						$certs_r[$cert_letter] = true;
						$count_certs_r ++;
					}
				}else{
					$certs_r[$cert_letter] = true;
					$count_certs_r ++;
				}
			}			
		}
	}

	if( $count_certs_w == 0 && $count_certs_r == 0 ) return '';

	// now build the button(s)
	$html = $btn_r = $btn_w = '';

	if( $count_certs_w > 0 ){
		$btn_w .= '<div class="dropdown">';
		$btn_w .= '<button type="button" class="btn btn-training btn-sm mb-3 w-100 dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-award fa-fw"></i> Live training certificate</button>';
		$btn_w .= '<ul class="dropdown-menu">';
		foreach ( $certs_w as $cert => $value) {
			if( $value ){
				$cert_parms = cc_certs_encode_parms( $cert, $training_id, $event_id, $user_id );
				$btn_text = cc_certs_btn_text( $cert );
				$url = add_query_arg( array( 'c' => $cert_parms ), site_url( '/training-certificate/' ) ); // note new URL
				$btn_w .= '<li><a class="dropdown-item" href="'.esc_url( $url ).'" target="_blank">'.$btn_text.'</a></li>';
			}
		}
		$btn_w .= '</ul>';
		$btn_w .= '</div><!-- .dropdown -->';
	}

	if( $count_certs_r > 0 ){
		if( $training_type == 'workshop' ){
			$recording_id = $wshop_rec_id;
		}else{
			$recording_id = $training_id;
		}
		$btn_r .= '<div class="dropdown">';
		$btn_r .= '<button type="button" class="btn btn-training btn-sm mb-3 w-100 dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-award fa-fw"></i> On-demand certificate</button>';
		$btn_r .= '<ul class="dropdown-menu">';
		foreach ( $certs_r as $cert => $value) {
			if( $value ){
				$cert_parms = cc_certs_encode_parms( $cert, $recording_id, 0, $user_id );
				$btn_text = cc_certs_btn_text( $cert );
				$url = add_query_arg( array( 'c' => $cert_parms ), site_url( '/training-certificate/' ) ); // note new URL
				$btn_r .= '<li><a class="dropdown-item" href="'.esc_url( $url ).'" target="_blank">'.$btn_text.'</a></li>';
			}
		}
		$btn_r .= '</ul>';
		$btn_r .= '</div><!-- .dropdown -->';
	}

	if( $btn_w <> '' && $btn_r <> '' ){
		$html .= '<div class="col-sm-6 text-center">';
		$html .= $btn_w;
		$html .= '</div><div class="col-sm-6 text-center">';
		$html .= $btn_r;
		$html .= '</div>';
	}else{
		$html .= '<div class="col-sm-6 offset-sm-3 text-center">';
		$html .= $btn_w;
		$html .= $btn_r;
		$html .= '</div>';
	}

	/*
	if( $count_certs_r == 0 ){
		// just a live training button
	}elseif( $count_certs_w == 0 ){
		// just an on-demand btn
	}else{
		// a btn-group for each
		$html .= '<div class="btn-group">';
		$html .= '<div class="btn-group">';
		$html .= '<button type="button" class="btn btn-training btn-sm mb-3 w-100 dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-award fa-fw"></i> Live training certificate</button>';
		$html .= '<ul class="dropdown-menu">';
		foreach ( $certs_w as $cert => $value) {
			if( $value ){
				$cert_parms = cc_certs_encode_parms( $cert, $training_id, $event_id, $user_id );
				$btn_text = cc_certs_btn_text( $cert );
				$url = add_query_arg( array( 'c' => $cert_parms ), site_url( '/training-certificate/' ) ); // note new URL
				$html .= '<li><a class="dropdown-item" href="'.esc_url( $url ).'" target="_blank">'.$btn_text.'</a></li>';
			}
		}
		$html .= '</ul>';
		$html .= '</div><!-- .btn-group -->';
		$html .= '<div class="btn-group">';
		$html .= '<button type="button" class="btn btn-training btn-sm mb-3 w-100 dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-award fa-fw"></i> On-demand certificate</button>';
		$html .= '<ul class="dropdown-menu">';
		foreach ( $certs_r as $cert => $value) {
			if( $value ){
				$cert_parms = cc_certs_encode_parms( $cert, $wshop_rec_id, 0, $user_id );
				$btn_text = cc_certs_btn_text( $cert );
				$url = add_query_arg( array( 'c' => $cert_parms ), site_url( '/training-certificate/' ) ); // note new URL
				$html .= '<li><a class="dropdown-item" href="'.esc_url( $url ).'" target="_blank">'.$btn_text.'</a></li>';
			}
		}
		$html .= '</ul>';
		$html .= '</div><!-- .btn-group -->';
		$html .= '</div><!-- .btn-group -->';
	}

	/*
	if( $count_certs == 1 ){
		// just one cert to be offered, only need a simple button
        $html = '<a class="btn btn-training btn-sm mb-3 w-100 cecert-btn" href="';
        $cert_parms = '';
        $btn_text = 'Your Certificate';
        foreach ($certs as $cert => $value) {
        	if( $value ){
        		$cert_parms = cc_certs_encode_parms( $cert, $training_id, $event_id, $user_id );
        		$btn_text = cc_certs_btn_text( $cert );
        		break;
        	}
        }
        $html .= add_query_arg( array( 'c' => $cert_parms ), site_url( '/training-certificate/' ) ); // note new URL
        $html .= '" target="_blank"><i class="fa-solid fa-award fa-fw"></i> '.$btn_text.'</a>';
	}else{
		// many certs to be offered
		$html = '<div class="dropdown"><button class="btn btn-training btn-sm mb-3 w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-award fa-fw"></i> Your certificate</button><ul class="dropdown-menu">';
        foreach ($certs as $cert => $value) {
        	if( $value ){
        		$html .= '<li><a class="dropdown-item" href="';
        		$cert_parms = cc_certs_encode_parms( $cert, $training_id, $event_id, $user_id );
		        $html .= add_query_arg( array( 'c' => $cert_parms ), site_url( '/training-certificate/' ) ); // note new URL
		        $html .= '" target="_blank">'.cc_certs_btn_text( $cert ).'</a></li>';
        	}
        }
        $html .= '</ul></div>';
	}
	*/
	return $html;
}

// offer this cert for this training for this user right now?
// cc certs offered for workshop is attended and for recording if viewed
// other certs offered if they have also submitted feedback and, for a recording, completed the quiz (or override in place)
// returns bool
function cc_certs_offer_this_cert( $cert, $training_id, $event_id, $user_id ){
	// is this cert offered for this training?
	if( cc_certs_setting( $training_id, $event_id, $cert ) <> 'yes' ){
		return false;
	}
	// has the user got sufficient attendance to obtain the cert? This also checks the quiz for recordings
	if( ! cc_ce_credits_sufficient_attendance( $training_id, $event_id, $user_id ) ){
		return false;
	}
	// that's enough for a CC Cert
	if( $cert == 'cc' ){
		return true;
	}
	// others also need feedback
	if( ! cc_feedback_submitted( $training_id, $event_id, $user_id ) ){
		return false;
	}
	// and if it's BACB then they also need their cert num to be set
	if( $cert == 'bacb' && get_user_meta( $user_id, 'bacb_num', true ) == '' ){
		return false;
	}
	return true;
}

// encode parms for certs
/**
 * Certificates have encoded parms for them as follows:
 * CC workshop = 'w'.base64($workshop_id.'|'.$user_id.'|'.$daft_number)
 * CC recording = 'r'.base64($recording_id.'|'.$user_id.'|'.$daft_number)
 * CC workshop event = 'e'.base64($workshop_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number)
 * APA (w or r) = 'a'.base64($training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number)
 * BACB (w or r) = 'b'.base64($training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number)
 * NBCC (w or r) = 'n'.base64($training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number)
 * ICF (w or r) = 'i'.base64($training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number)
 **/
function cc_certs_encode_parms( $cert, $training_id, $event_id, $user_id ){
	switch ($cert) {
		case 'w':
			$daft_number = $training_id * $user_id + 10408;
			$string = $training_id.'|'.$user_id.'|'.$daft_number;
			return 'w'.base64_encode($string);
			break;

		case 'r':
			$daft_number = $training_id * $user_id + 35486;
			$string = $training_id.'|'.$user_id.'|'.$daft_number;
			return 'r'.base64_encode($string);
			break;

		case 'e':
			$daft_number = $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 34179;
			$string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
			return 'e'.base64_encode($string);
			break;

		case 'a':
			$daft_number = $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 59563;
			$string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
			return 'a'.base64_encode($string);
			break;

		case 'b':
			$daft_number = $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 78485;
			$string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
			return 'b'.base64_encode($string);
			break;

		case 'n':
			$daft_number = $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 53071;
			$string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
			return 'n'.base64_encode($string);
			break;

		case 'i':
			$daft_number = $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 81170;
			$string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
			return 'i'.base64_encode($string);
			break;

		default:
			return '';
			break;
	}
}

// decode the parms
function cc_certs_decode_parms( $string ){
	$response = array(
		'cert' => '',
		'training_id' => 0,
		'event_id' => 0,
		'user_id' => 0,
	);
	$cert = substr( $string, 0, 1 );
	$parms = base64_decode( substr( $string, 1 ) );
	switch ($cert) {
		case 'w':
			list( $workshop_id, $user_id, $daft_number ) = explode( "|", $parms );
			if( $daft_number == $workshop_id * $user_id + 10408 ){
				$response['cert'] = 'w';
				$response['training_id'] = $workshop_id;
				$response['user_id'] = $user_id;
			}
			break;

		case 'r':
			list( $recording_id, $user_id, $daft_number ) = explode( "|", $parms );
			if($daft_number == $recording_id * $user_id + 35486){
				$response['cert'] = 'r';
				$response['training_id'] = $recording_id;
				$response['user_id'] = $user_id;
			}
			break;

		case 'e':
			list( $training_id, $event_id, $user_id, $daft_number ) = explode( "|", $parms );
			if( $daft_number == $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 34179 ){
				$response['cert'] = 'e';
				$response['training_id'] = $training_id;
				$response['event_id'] = $event_id;
				$response['user_id'] = $user_id;
			}
			break;
		
		case 'a':
			list( $training_id, $event_id, $user_id, $daft_number ) = explode( "|", $parms );
			if( $daft_number == $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 59563 ){
				$response['cert'] = 'a';
				$response['training_id'] = $training_id;
				$response['event_id'] = $event_id;
				$response['user_id'] = $user_id;
			}
			break;

		case 'b':
			list( $training_id, $event_id, $user_id, $daft_number ) = explode( "|", $parms );
			if( $daft_number == $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 78485 ){
				$response['cert'] = 'b';
				$response['training_id'] = $training_id;
				$response['event_id'] = $event_id;
				$response['user_id'] = $user_id;
			}
			break;

		case 'n':
			list( $training_id, $event_id, $user_id, $daft_number ) = explode( "|", $parms );
			if( $daft_number == $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 53071 ){
				$response['cert'] = 'n';
				$response['training_id'] = $training_id;
				$response['event_id'] = $event_id;
				$response['user_id'] = $user_id;
			}
			break;

		case 'i':
			list( $training_id, $event_id, $user_id, $daft_number ) = explode( "|", $parms );
			if( $daft_number == $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 81170 ){
				$response['cert'] = 'i';
				$response['training_id'] = $training_id;
				$response['event_id'] = $event_id;
				$response['user_id'] = $user_id;
			}
			break;

		default:
			// code...
			break;
	}
	return $response;
}

// button text
function cc_certs_btn_text( $cert ){
	switch ($cert) {
		case 'w':		return 'Live training certificate';			break;
		case 'r':		return 'On-demand certificate';				break;
		case 'e':		return 'Live event certificate';			break;
		case 'a':		return 'APA certificate';					break;
		case 'b':		return 'BACB certificate';					break;
		case 'n':		return 'NBCC certificate';					break;
		case 'i':		return 'ICF certificate';					break;
		default:		return 'Your certificate';					break;
	}
}

// migrate the data from the workshop_certificate field to the new fields
add_shortcode('populate_new_cert_fields', 'cc_certs_populate_new_cert_fields');
function cc_certs_populate_new_cert_fields(){
	global $wpdb;
	$html = '<br>cc_certs_populate_new_cert_fields';
	// we need to get all the workshop_certificate fields for workshops and recordings
	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'workshop_certificate'";
	$metas = $wpdb->get_results( $sql, ARRAY_A );
	$count_metas = $count_updates = 0;
	foreach ($metas as $meta) {
		$count_metas ++;
		if( $meta['meta_value'] <> '' ){
			$html .= '<br>'.$meta['post_id'].' '.$meta['meta_value'];
			// valid values can be text, numeric or a URL
			// if numeric, it's a number of CE hours otherwise it's simply treated as a signal to allow a CC cert to be shown
			if( is_numeric( $meta['meta_value'] ) && $meta['meta_value'] > 0 && $meta['meta_value'] == round( $meta['meta_value'], 1 ) ){
				// ce credits
				// are they already set for this training?
				$ce_credits = get_post_meta( $meta['post_id'], 'ce_credits', true );
				if( $ce_credits == '' ){
					// not set so we'll set them
					update_post_meta( $meta['post_id'], 'ce_credits', $meta['meta_value'] );
					$html .= ' ce_credits set';
					// we'll also activate the APA certs (if not already specifically turned off)
					$cert_apa = get_post_meta( $meta['post_id'], 'cert_apa', true );
					if( $cert_apa <> 'no' ){
						update_post_meta( $meta['post_id'], 'cert_apa', 'yes' );
						$html .= ' cert_apa set';
					}
				}
			}
			// numeric or not, we'll activate the cc certs ... unless already turned off
			$cert_cc = get_post_meta( $meta['post_id'], 'cert_cc', true );
			if( $cert_cc <> 'no' ){
				update_post_meta( $meta['post_id'], 'cert_cc', 'yes' );
				$html .= ' cert_cc set';
			}
			$count_updates ++;
		}
	}
	$html .= '<br>metas: '.$count_metas.' updates: '.$count_updates;
	return $html;
}

// get the certificate setting and use its default if not set
// $cert should be 'cc', 'apa', 'bacb', 'nbcc' or 'icf'
function cc_certs_setting( $training_id, $event_id, $cert ){
	if( $event_id == 0 ){
		$meta_value = get_post_meta( $training_id, 'cert_'.$cert, true );
	}else{
		$meta_value = get_post_meta( $training_id, 'event_'.$event_id.'_cert_'.$cert, true );
	}	
	if( $meta_value == '' ){
		// defaults
		switch ($cert) {
			case 'cc':			$meta_value = 'yes';		break;
			case 'apa':			$meta_value = 'no';			break;
			case 'bacb':		$meta_value = 'no';			break;
			case 'nbcc':		$meta_value = 'no';			break;
			case 'icf':			$meta_value = 'no';			break;
		}
	}
	return $meta_value;
}

// which certs are availabkle for a given training/event
function cc_certs_for_training( $training_id, $event_id=0 ){
	$certs = [];
	$all_certs = array( 'apa', 'bacb', 'nbcc', 'icf' );
	foreach ($all_certs as $cert) {
		if( cc_certs_setting( $training_id, $event_id, $cert ) == 'yes' ){
			$certs[] = $cert;
		}
	}
	return $certs;
}

// all certificates available for a bunch of different trainings
function cc_certs_available_for( $training_ids ){
	global $wpdb;
	$all_certs = array( 'apa', 'bacb', 'nbcc', 'icf' );
	$found_certs = array();
    $training_id_string = implode( ',', $training_ids );
	foreach ($all_certs as $cert) {
		// any of our training use this?
		$meta_key = 'cert_'.$cert;
		$sql = "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '$meta_key' AND meta_value = 'yes' AND post_id IN ($training_id_string)";
		$result = $wpdb->get_var( $sql );
		if( $result > 0 ){
			$found_certs[] = $cert;
		}else{
			// if we wanted to be really clever, we could look to see if any events had the cert set ...
		}
	}
	return $found_certs;
}
