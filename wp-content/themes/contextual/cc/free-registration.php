<?php
/**
 * Free registration page
 */

add_action( 'admin_menu', 'ccfreer_admin_menu' );
function ccfreer_admin_menu(){
	add_submenu_page( 'edit.php?post_type=recording', 'Free Recordings', 'Free Recordings', 'publish_pages', 'free-recordings', 'ccfreer_free_recordings_page' );
}
function ccfreer_free_recordings_page(){
	$top_msg = $ok_msg = '';
	// processing data?
	if(isset( $_POST['free_recordings_nonce'] )){
		if(wp_verify_nonce( $_POST['free_recordings_nonce'], 'free_recordings_action' )){
	        if(current_user_can('publish_pages')){
	        	$errors = false;
	        	$top_msg = '';
	        	// $recording_title = '';
	        	if(!isset($_POST['recording']) || $_POST['recording'] == ''){
	        		$top_msg .= 'Recording not selected. ';
	        		$errors = true;
	        	}else{
	        		$recording_id = absint($_POST['recording']);
	        		if($recording_id == 0){
		        		$top_msg .= 'Recording invalid. ';
		        		$errors = true;
	        		}else{
	        			// $recording_title = get_the_title($recording_id);
	        		}
	        	}
	        	$mailster_list = 0;
	        	if(isset($_POST['mailster-list']) && $_POST['mailster-list'] <> ''){
	        		$mailster_list = absint($_POST['mailster-list']);
	        	}
	        	$emails = '';
	        	if(isset($_POST['emails']) && $_POST['emails'] <> ''){
	        		$emails = $_POST['emails'];
	        	}
	        	if($mailster_list == 0 && $emails == ''){
	        		$top_msg .= 'No emails entered. ';
	        		$errors = true;
	        	}
	        	if($mailster_list > 0 && $emails <> ''){
	        		$top_msg .= 'Subscriber list selected and emails also entered - please do one of these, not both. No emails sent! ';
	        		$errors = true;
	        	}
	        	if(!isset($_POST['email-msg-subject']) || $_POST['email-msg-subject'] == ''){
	        		$top_msg .= 'Email message subject is blank (new reg.). ';
	        		$errors = true;
	        	}else{
	        		update_option('free_recording_email_msg_subject', sanitize_text_field($_POST['email-msg-subject']));
	        	}
	        	if(!isset($_POST['email-msg']) || $_POST['email-msg'] == ''){
	        		$top_msg .= 'Email message is missing (new reg.). ';
	        		$errors = true;
	        	}else{
	        		update_option('free_recording_email_msg', sanitize_textarea_field($_POST['email-msg']));
	        	}
	        	if(!isset($_POST['reg-email-msg-subject']) || $_POST['reg-email-msg-subject'] == ''){
	        		$top_msg .= 'Email message subject is blank (extra recording). ';
	        		$errors = true;
	        	}else{
	        		update_option('free_recording_reg_email_msg_subject', sanitize_text_field($_POST['reg-email-msg-subject']));
	        	}
	        	if(!isset($_POST['reg-email-msg']) || $_POST['reg-email-msg'] == ''){
	        		$top_msg .= 'Email message is missing (extra recording). ';
	        		$errors = true;
	        	}else{
	        		update_option('free_recording_reg_email_msg', sanitize_textarea_field($_POST['reg-email-msg']));
	        	}
	        	if(isset($_POST['email-switch']) && $_POST['email-switch'] == 'no'){
	        		$email_switch = 'no';
	        	}else{
	        		$email_switch = 'yes';
	        	}
	        	if(!$errors){
	        		if($emails <> ''){
		        		$count_queued = ccrecw_add_to_queue_form($emails, $recording_id, $email_switch);
		        	}else{
		        		$count_queued = ccrecw_add_to_queue_mailster($mailster_list, $recording_id, $email_switch);
		        	}
		        	ccrecw_start_queue();
	        		$ok_msg = $count_queued.' emails queued for delivery';
	        	}
	        }else{
	        	$top_msg = 'You don\'t have permission to do that';
	        }
	    }else{
	    	$top_msg = 'Nonce invalid';
	    }
    }

	?>
	<div class="wrap">
		<h1>Add Free Recordings</h1>
		<p>Paste a list of email addresses here and they will be given access to the recording of your choice.<br>
			If they are already registsred then this recording will simply be added to their My Recordings page.<br>
			If they are not registered then an email will be sent to them with the a link for them to complete their registration.</p>
		<?php if($top_msg <> ''){ ?>
			<div id="message" class="error notice">
				<p><?php echo $top_msg; ?></p>
			</div>
		<?php }elseif($ok_msg <> ''){ ?>
			<div id="message" class="updated notice">
				<p><?php echo $ok_msg; ?></p>
			</div>
		<?php } ?>
		<form method="post" action="">
			<table class="form-table">
				<tr>
					<th>
						<label for="recording">Select recording</label>
					</th>
					<td>
						<select name="recording" id="recording" class="postform">
							<option value="">Please select ...</option>
							<?php echo ccrecw_free_rec_options(); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<label for="mailster-list">Subscriber List</label>
					</th>
					<td>
						<select name="mailster-list" id="mailster-list" class="postform">
							<option value="">Select if required ...</option>
							<?php echo ccrecw_free_rec_mailster_lists(); ?>
						</select>
						<p class="description">Either select a list here or enter emails below ... not both</p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="emails">Paste email addresses here (one per line)</label>
					</th>
					<td>
						<textarea name="emails" id="emails" cols="60" rows="30" class="large-text code" placeholder="email.address@gmail.com&#13;&#10;another.email@yahoo.co.uk&#13;&#10;etc..."></textarea>
						<p class="description">Either select a list above or enter emails here ... not both</p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="email-switch">Send emails?</label>
					</th>
					<td>
						<select name="email-switch" id="email-switch" class="postform">
							<option value="yes">Yes</option>
							<option value="no">No</option>
						</select>
						<p class="description">Yes = emails only to people who did not previously have access to this recording, No = no emails to anybody</p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="email-msg">Email Message (to go to new registrations)</label>
					</th>
					<td>
						<input type="text" class="regular-text" id="" name="email-msg-subject" placeholder="Login details" value="<?php echo get_option('free_recording_email_msg_subject'); ?>">
						<p class="description">You can add [recording] to automatically add the recording title to the subject</p>
					</td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>
						<textarea name="email-msg" id="email-msg" cols="60" rows="10" class="large-text code"><?php echo get_option('free_recording_email_msg'); ?></textarea>
						<p class="description">You can add [recording] to automatically add the recording title to the email. Will be followed by "To set your password, visit the following address: ... Kind regards, The Contextual Consulting Team"</p>
					</td>
				</tr>
				<tr>
					<th>
						<label for="reg-email-msg">Email Message (to go to people already registered)</label>
					</th>
					<td>
						<input type="text" class="regular-text" id="" name="reg-email-msg-subject" placeholder="New recording" value="<?php echo get_option('free_recording_reg_email_msg_subject'); ?>">
						<p class="description">You can add [recording] to automatically add the recording title to the subject</p>
					</td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>
						<textarea name="reg-email-msg" id="reg-email-msg" cols="60" rows="10" class="large-text code"><?php echo get_option('free_recording_reg_email_msg'); ?></textarea>
						<p class="description">You can add [recording] to automatically add the recording title to the email. Unlike above, this is the complete message. So, you might want to end it with something like ... Kind regards, The Contextual Consulting Team"</p>
					</td>
				</tr>
			</table>
			<?php
		    wp_nonce_field( 'free_recordings_action', 'free_recordings_nonce' );
		    submit_button();
		    ?>
		</form>
	</div>
<?php }

