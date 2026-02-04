<?php
/**
 * My Account Order History
 */

function cc_myacct_orders(){
	$user_info = wp_get_current_user();
    $portal_user = get_user_meta( $user_info->ID, 'portal_user', true);
    $html = '<h3 class="d-md-none">Order history</h3><div class="myacct-panel myacct-orders-panel">';

    $trainings = myacct_orders_get_all( $user_info->ID );

    if( count( $trainings ) == 0 ){
        $html .= '<p class="no-results">It looks like you haven\'t registered for any trainings yet.</p>';
    }else{
    	$accordion_state = ( count( $trainings ) > 1 ) ? 'closed' : 'opened';
        $html .= '<div class="accordion accordion-flush" id="orders_accordions">';

        $accordion_id = 0;
        foreach ( $trainings as $payment_ids ) {

        	// open or closed ...
            if($accordion_state == 'opened'){
                $button_class = 'accordion-button';
                $body_class = 'accordion-collapse collapse show';
                $accordion_state = 'closed';
            }else{
                $button_class = 'accordion-button collapsed';
                $body_class = 'accordion-collapse collapse';
            }

            $html .= '<div class="order">';

            // we need a title for the accordion. We'll get that from the first array entry
            $first_payment_id = $payment_ids[0];
            $first_payment = cc_paymentdb_get_payment( $first_payment_id );
            $training_id = $first_payment['workshop_id'];
            $accordion_title = get_the_title( $training_id );

            // header
            $html .= '<div id="training-'.$accordion_id.'" class="accordion-item dark-bg">';
            $html .= '<h4 class="accordion-header" id="orders_accordion_heading_'.$accordion_id.'">';
            $html .= '<button class="'.$button_class.'" type="button" data-bs-toggle="collapse" data-bs-target="#orders_accordion_body_'.$accordion_id.'" aria-expanded="false" aria-controls="orders_accordion_body_'.$accordion_id.'">'.$accordion_title.'</button>';
            $html .= '</h4>';

            // body
            $html .= '<div id="orders_accordion_body_'.$accordion_id.'" class="'.$body_class.'" aria-labelledby="orders_accordion_heading_'.$accordion_id.'" data-bs-parent="#orders_accordions">';
            $html .= '<div class="accordion-body">';

            // most people will only have registered once ... and for themselves
            if( count( $payment_ids ) == 1 ){
            	$html .= '<div class="row">';

            	$html .= '<div class="col-md-8">';
            	$html .= myacct_orders_training_info( $payment_ids[0], $user_info->ID );
            	$html .= '</div>';

            	$html .= '<div class="col-md-4 lh-1">';
            	$html .= myacct_orders_payment_details( $payment_ids[0] );
            	$html .= '</div>';

            	$html .= '</div>';
            }else{
            	foreach ($payment_ids as $payment_id) {
            		$html .= '<div class="order-wrap mb-3 p-3">';

	            	$html .= '<div class="row">';

	            	$html .= '<div class="col-md-8">';
	            	$html .= myacct_orders_training_info( $payment_id, $user_info->ID );
	            	$html .= '</div>';

	            	$html .= '<div class="col-md-4 lh-1">';
	            	$html .= myacct_orders_payment_details( $payment_id );
	            	$html .= '</div>';
	            	
	            	$html .= '</div>';

	            	$html .= '</div>';
            	}
            }

            $html .= '</div><!-- .accordion-body -->';
            $html .= '</div><!-- #orders_accordion_body_ -->';
            $html .= '</div><!-- .accordion-item -->';
            $html .= '</div><!-- .order -->';

            $accordion_id ++;

        }

        $html .= '</div><!-- .accordion -->';
    }

    $html .= '</div><!-- .myacct-orders-panel -->';

    return $html;
}

// get all trainings (orders) for a user
// each "order" can include multiple payment records
// trainings are ordered by date of most recent payment record, newest to oldest
/**
 * retrieves the list of trainings a user has registered forThis function:
 * - Joins the Payments table with Recordings_users and Workshops_users
 * - Filters only registrants (users where Payments.reg_user_id = Recordings_users.user_id or Payments.reg_user_id = Workshops_users.user_id)
 * - Groups training records by training_id, collecting associated payment_id values
 * - Sorts payment_ids in descending order and the overall trainings array by the latest payment_id
 * returns an associative array where each training_id maps to an array of payment_ids
 **/
function myacct_orders_get_all( $user_id ){
    global $wpdb;
    
    // selects training_id and payment_id from the Payments table, joining with Recordings_users and Workshops_users to ensure only registrants are included.
    // filters by reg_user_id = $user_id
    // Sorting by payment_id: Results are ordered in descending order
    // ignoring child series payments
    $query = "
        SELECT p.workshop_id, p.id
        FROM {$wpdb->prefix}ccpa_payments p
        LEFT JOIN {$wpdb->prefix}recordings_users r ON p.id = r.payment_id
        LEFT JOIN {$wpdb->prefix}wshops_users w ON p.id = w.payment_id
        WHERE p.reg_userid = %d
        AND p.status IN ('Payment successful: ', 'Invoice sent', 'Invoice requested', 'Payment not needed')
        AND NOT (p.disc_code = 'series' AND p.type != 'series')
        GROUP BY p.id
        ORDER BY p.id DESC
    ";
    
    $results = $wpdb->get_results($wpdb->prepare($query, $user_id), ARRAY_A);
    
    // Grouping by training_id: The function organizes payment_ids under their respective training_id.
    $trainings = [];
    
    foreach ($results as $row) {
        $training_id = $row['workshop_id'];
        $payment_id = $row['id'];
        
        if (!isset($trainings[$training_id])) {
            $trainings[$training_id] = [];
        }
        
        $trainings[$training_id][] = $payment_id;
    }
    
    // Sort the trainings array by the first (latest) payment_id in each list
    uasort($trainings, function($a, $b) {
        return max($b) - max($a);
    });
    
    return $trainings;
}

// the info section about this payment record
function myacct_orders_training_info( $payment_id, $user_id ){
	$payment = cc_paymentdb_get_payment( $payment_id );
    $date_timezone = cc_timezone_get_user_timezone($user_id);  // returns the name (eg Europe/London)

    $html = '<div class="mb-3">';

	// registration
	$html .= '<div><i class="fa-solid fa-clock fa-fw"></i> Registration: '.date('jS M Y', strtotime( $payment['last_update'] ) ).'</div>';

	// training
	$html .= myacct_orders_training_line( $payment['workshop_id'], $date_timezone );
    if( $payment['upsell_workshop_id'] > 0 ){
		$html .= myacct_orders_training_line( $payment['upsell_workshop_id'], $date_timezone );
    }

    $html .= '</div>';

    // attendees
    $attendees = cc_attendees_for_payment( $payment_id );
    if( count( $attendees ) > 1 || $attendees[0]['user_id'] <> $user_id ){
        foreach ($attendees as $attendee) {
            $html .= '<div><i class="fa-regular fa-user fa-fw"></i> Attendee: ';
            if($attendee['registrant'] == 'r'){
                $html .= 'Yourself';
            }else{
                $user = get_user_by('ID', $attendee['user_id']);
                $html .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';
            }
            $html .= '</div>';
        }
    }

    if( $payment['pmt_method'] == 'online' ){
        $html .= '<div class="mt-3"><a class="btn btn-sm btn-myacct resend-reg-btn mb-1" data-paymentid="'.$payment_id.'">Resend Reg. Email</a></div>';
        $html .= '<div id="resend-reg-msg-'.$payment_id.'" class="resend-reg-msg"></div>';
    }

    return $html;
}

// the training lines
function myacct_orders_training_line( $training_id, $date_timezone ){
	$html = '<div><i class="fa-solid fa-person-chalkboard fa-fw"></i> '.get_the_title( $training_id );

    $post_type = get_post_type( $training_id );

    if( $post_type == 'series' ){
        $html .= ' training series including:';
    }else{
        $who = cc_presenters_names( $training_id, 'none' );
        if($who <> ''){
            $html .= ': '.$who;
        }
    }
    $html .= '</div>';

    if( $post_type == 'series' ){
        // get all the training ids for the series
        $series_courses = get_post_meta( $training_id, '_series_courses', true );
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        $html .= '<div class="series-wrap">';
        foreach ($series_courses as $course_id) {
            $html .= '<div><i class="fa-solid fa-display fa-fw"></i> '.get_the_title( $course_id ).'</div>';
        }
        $html .= '</div>';
    }elseif( $post_type == 'workshop' ){
    	$html .= '<div><i class="fa-solid fa-display fa-fw"></i> Live training ';
    	$pretty_dates = workshop_calculated_prettydates( $training_id, $date_timezone );
    	$html .= $pretty_dates['locale_date'].'</div>';
    }else{
    	$html .= '<div><i class="fa-solid fa-display fa-fw"></i> On-demand training</div>';
    }
    return $html;
}

// the payment amounts
function myacct_orders_payment_details( $payment_id ){
	$payment = cc_paymentdb_get_payment( $payment_id );

	$html = '';

	$training_amount = $payment['payment_amount'] + $payment['disc_amount'] - $payment['vat_included'];
	$html .= '<div class="row mb-2"><div class="col-6">Training</div><div class="col-6 text-end">'.cc_money_format( $training_amount, $payment['currency'] ).'</div></div>';

	$voucher = $discount = false;
	if( $payment['disc_amount'] <> 0 ){
        // check for it being a voucher or a RAF code
		$voucher = ccpa_voucher_table_get( $payment['disc_code'] );
		if( $voucher === null && substr( $payment['disc_code'], 0, 3 ) <> 'CC-' ){
            // $disc_code = $payment['disc_code'] == 'UPSELL' ? 'Training offer' : $payment['disc_code'];
            switch ($payment['disc_code']) {
                case 'UPSELL':
                    $disc_code = 'Training offer';
                    break;
                case 'NLFT':
                    $disc_code = 'N. London NHS Found. Trust';
                    break;
                default:
                    $disc_code = $payment['disc_code'];
                    break;
            }
			$html .= '<div class="row mb-2"><div class="col-6">Less discount<br><small>'.$disc_code.'</small></div><div class="col-6 text-end">'.cc_money_format( $payment['disc_amount'], $payment['currency'] ).'</div></div>';
			$discount = true;
		}else{
			$voucher = true;
		}
	}

	if( $payment['vat_included'] <> 0 ){
		$html .= '<div class="row mb-2"><div class="col-6">VAT</div><div class="col-6 text-end">'.cc_money_format( $payment['vat_included'], $payment['currency'] ).'</div></div>';
	}

	if( $payment['vat_included'] <> 0 || $discount ){
		if( $voucher ){
			$total = $payment['payment_amount'] + $payment['disc_amount'];
		}else{
			$total = $payment['payment_amount'];
		}
		$html .= '<div class="row mb-2"><div class="col-6">Total</div><div class="col-6 text-end">'.cc_money_format( $total, $payment['currency'] ).'</div></div>';
	}

	if( $voucher ){
        // voucher or RAF code
		$html .= '<div class="row mb-2"><div class="col-6">Less voucher<br><small>'.cc_voucher_core_pretty_voucher( $payment['disc_code'] ).'</small></div><div class="col-6 text-end">'.cc_money_format( $payment['disc_amount'], $payment['currency'] ).'</div></div>';
	}

	if( $payment['status'] == 'Payment successful: ' ){
		$html .= '<div class="row mb-2"><div class="col-6">Paid online</div><div class="col-6 text-end">'.cc_money_format( $payment['payment_amount'], $payment['currency'] ).'</div></div>';
	}elseif( $payment['status'] == 'Invoice requested' || $payment['status'] == 'Invoice sent' ){
		$html .= '<div class="row mb-2"><div class="col-6">Invoiced</div><div class="col-6 text-end">'.cc_money_format( $payment['payment_amount'], $payment['currency'] ).'</div></div>';
	}

	if( $payment['status'] == 'Payment successful: ' ){
        $receipt_parms = ccpdf_receipt_parms_encode( $payment_id );
        $receipt_url = add_query_arg( array( 'r' => $receipt_parms ), site_url( '/receipt/' ) );
        $html .= '<div class="mt-3"><a class="btn btn-sm btn-myacct pdf-receipt" href="'.esc_url($receipt_url).'" target="_blank">Your Receipt</a></div>';
	}

	return $html;
}
