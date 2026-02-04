<?php
/**
 * Cancellation
 * - cancel workshop or recording registrations
 */

// the cancallation confirmation modal, added in to the footer
add_action( 'wp_footer', 'cc_cancellation_modal_html' );
function cc_cancellation_modal_html(){
	?>
	<div id="cancellation-modal" class="modal cancellation-modal cc-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Cancel your Registration?</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<p>Are you sure you really want to cancel your registration for <i><span id="cancellation-training-title"></span></i>?</p>
					<p class="my-3 text-center">
						<a href="javascript:void(0);" id="cancel-yes" data-training="" data-type="" class="btn btn-danger btn-lg">Yes: Cancel the registration</a>
					</p>
					<p id="cancel-msg"></p>
				</div>
				<div class="modal-footer">
					<div class="row align-items-end">
						<div class="col text-start">
							<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No: Keep the registration</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

// cancel training
add_action('wp_ajax_cancel_training', 'cc_cancellation_cancel_training');
add_action('wp_ajax_nopriv_cancel_training', 'cc_cancellation_cancel_training');
function cc_cancellation_cancel_training(){
	$response = array(
		'status' => 'error',
		'msg' => '',
	);
	$training_id = 0;
	if(isset($_POST['trainingID'])){
		$training_id = (int) $_POST['trainingID'];
	}
	$training_type = '';
	if(isset($_POST['trainingType']) && ($_POST['trainingType'] == 'w' || $_POST['trainingType'] == 'r')){
		$training_type = $_POST['trainingType'];
	}
	if($training_type == '' || $training_id == 0){
		$response['msg'] = 'Invalid request - please try again';
	}else{
		if($training_type == 'w'){
			if(cc_myacct_workshop_offer_cancellation($training_id)){
				cc_cancellation_cancel_workshop($training_id);
				$response['msg'] = 'Registration cancelled';
				$response['status'] = 'ok';
			}else{
				$response['msg'] = 'Cancellation not possible for this training';
			}
		}else{
			// recording
			if(cc_myacct_recording_offer_cancellation($training_id)){
				cc_cancellation_cancel_recording($training_id);
				$response['msg'] = 'Registration cancelled';
				$response['status'] = 'ok';
			}else{
				$response['msg'] = 'Cancellation not possible for this training';
			}
		}
	}
    echo json_encode($response);
    die();
}

// cancel a workshop
function cc_cancellation_cancel_workshop($workshop_id, $user_id=NULL){
	// ccpa_write_log('cc_cancellation_cancel_workshop');
	if($user_id == NULL){
		$user_id = get_current_user_id();
	}
	// get the workshop users row
	$wkshop_users = cc_myacct_get_workshops_users_by_user_workshop($user_id, $workshop_id);
	// ccpa_write_log($wkshop_users);
	// flag it as cancelled ... don't bother! The update payment will achieve that
	// $result = my_acct_cancel_workshop_users_row($wkshop_users['id']);
	// ccpa_write_log($result);
	// get the payment
	$payment_data = cc_paymentdb_get_payment($wkshop_users['payment_id']);
	// ccpa_write_log($payment_data);
	// flag it as cancelled
	$payment_data['status'] = 'Cancelled';
	if($payment_data['notes'] <> ''){
		$payment_data['notes'] .= ' ';
	}
	$payment_data['notes'] .= 'Registration cancelled by user '.date('d/m/Y H:i:s');
	$payment_id = cc_paymentdb_update_payment($payment_data);

	// we also need to remove the user from the Mailster list for this workshop
	$list_id = cc_mailster_training_list_id($workshop_id);
	$user = get_user_by('ID', $user_id);
	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	if($subscriber){
		$result = mailster('subscribers')->unassign_lists($subscriber->ID, $list_id);
	}

	// do we need to recalculate the last_registration date?
	$last_registration = get_user_meta( $user_id, 'last_registration', true );
	if($last_registration <> ''){
		// if last_registration is over 3 months ago then we can leave it as it is
		$three_months_ago = date('Y-m-d H:i:s', strtotime('-3 months'));
	    if($last_registration > $three_months_ago){
		    // if last_registration is not for this payment then we can also leave it as it is
		    $last_reg_id = get_user_meta( $user_id, 'last_reg_id', true );
		    if($last_reg_id <> $payment_id){
		    	// if neither of these apply then we want to recalculate the last_registration from the payment records
		    	// find previous payment ... if there is one
		    	$prev_payment_data = cc_paymentdb_get_previous_payment($payment_data['email'], $payment_id);
		    	if($prev_payment_data === NULL){
		    		// clear them out
					update_user_meta($user_id, 'last_registration', '');
					update_user_meta($user_id, 'last_reg_id', '');
		    	}else{
					update_user_meta($user_id, 'last_registration', $prev_payment_data['last_update']);
					update_user_meta($user_id, 'last_reg_id', $prev_payment_data['id']);
		    	}
		    }
	    }
	}
}

// cancel a recording
function cc_cancellation_cancel_recording($recording_id, $user_id=NULL){
	if($user_id == NULL){
		$user_id = get_current_user_id();
	}
	// get the user recording data
	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
	$recording_meta = get_recording_meta( $user_id, $recording_id );
	// close access to the recording
	$result = ccrecw_withdraw_access($user_id, $recording_id);
	// we also need to remove the user from the Mailster list for this recording
	$list_id = cc_mailster_training_list_id($recording_id);
	$user = get_user_by('ID', $user_id);
	$subscriber = mailster('subscribers')->get_by_mail($user->user_email);
	if($subscriber){
		$result = mailster('subscribers')->unassign_lists($subscriber->ID, $list_id);
	}
	// get the payment
	if($recording_meta['payment_id'] > 0){
		$payment_data = cc_paymentdb_get_payment($recording_meta['payment_id']);
		// flag it as cancelled
		$payment_data['status'] = 'Cancelled';
		if($payment_data['notes'] <> ''){
			$payment_data['notes'] .= ' ';
		}
		$payment_data['notes'] .= 'Registration cancelled by user '.date('d/m/Y H:i:s');
		$payment_id = cc_paymentdb_update_payment($payment_data);
		// do we need to recalculate the last_registration date?
		$last_registration = get_user_meta( $user_id, 'last_registration', true );
		if($last_registration <> ''){
			// if last_registration is over 3 months ago then we can leave it as it is
			$three_months_ago = date('Y-m-d H:i:s', strtotime('-3 months'));
		    if($last_registration > $three_months_ago){
			    // if last_registration is not for this payment then we can also leave it as it is
			    $last_reg_id = get_user_meta( $user_id, 'last_reg_id', true );
			    if($last_reg_id <> $payment_id){
			    	// if neither of these apply then we want to recalculate the last_registration from the payment records
			    	// find previous payment ... if there is one
			    	$prev_payment_data = cc_paymentdb_get_previous_payment($payment_data['email'], $payment_id);
			    	if($prev_payment_data === NULL){
			    		// clear them out
						update_user_meta($user_id, 'last_registration', '');
						update_user_meta($user_id, 'last_reg_id', '');
			    	}else{
						update_user_meta($user_id, 'last_registration', $prev_payment_data['last_update']);
						update_user_meta($user_id, 'last_reg_id', $prev_payment_data['id']);
			    	}
			    }
		    }
		}
	}
}