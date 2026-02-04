<?php
/**
 * Emails (non-Mailster)
 */

// sends an email when the charge fails
function ccpa_send_failure($id, $payment_data=array()){
	global $rpm_theme_options;
	$email_to = $rpm_theme_options['pay-error-to'];
	if($email_to <> ''){
		if(empty($payment_data)){
			$payment_data = cc_paymentdb_get_payment($id);
		}
		$subject = 'Payment Processing Error';
		$message = 'A payment failed:<br><br>';
		$message .= 'ID: ';
		if(isset($payment_data['id'])){
			$message .= $payment_data['id'];
		}else{
			$message .= $id;
		}
		$message .= '<br>';
		foreach ($payment_data as $key => $value) {
			$message .= $key.': '.$value.'<br>';
		}
		$headers = array(
			'From: '.$rpm_theme_options['email-from-name'].' <'.$rpm_theme_options['email-from'].'>',
			'Content-Type: text/html; charset=UTF-8',
		);
		return wp_mail($email_to, $subject, $message, $headers);
	}
}

// as it says on the tin ...
function ccpa_send_post_data_to_me(){
	global $rpm_theme_options;
	$subject = 'Contextual Consulting Post Data';
	$message = 'Contextual Consulting Post Data<br><br>';
	$message .= 'IP Address: '.$_SERVER['REMOTE_ADDR'].'<br>';
	$message .= 'User id: '.get_current_user_id().'<br>';
	$message .= 'Post data:<br><pre>';
	foreach ($_POST as $key => $value) {
		$message .= $key.': '.sanitize_text_field($value).'<br>';
	}
	$message .= '</pre>';
	$to = get_bloginfo('admin_email');
	$headers = array(
		'From: '.$rpm_theme_options['email-from-name'].' <'.$rpm_theme_options['email-from'].'>',
		'Content-Type: text/html; charset=UTF-8',
	);
	return wp_mail($to, $subject, $message, $headers);
}

// if a duplicate payment is intercepted
function cc_emails_duplicate_notification($duplicate_id){
	global $rpm_theme_options;
	$subject = 'Contextual Consulting: ERROR: Duplicate Intercepted!';
	$message = 'Contextual Consulting Post Data<br><br>';
	$message .= 'IP Address: '.$_SERVER['REMOTE_ADDR'].'<br>';
	$message .= 'User id: '.get_current_user_id().'<br>';
	$message .= 'Post data:<br><pre>';
	foreach ($_POST as $key => $value) {
		$message .= $key.': '.sanitize_text_field($value).'<br>';
	}
	$message .= '</pre>';
	$message .= '<br>This is a believed duplicate of Payment ID: '.$duplicate_id;
	$to = get_bloginfo('admin_email');
	$headers = array(
		'From: '.$rpm_theme_options['email-from-name'].' <'.$rpm_theme_options['email-from'].'>',
		'Content-Type: text/html; charset=UTF-8',
	);
	return wp_mail($to, $subject, $message, $headers);
}

// sends feedback to Joe
function cc_emails_feedback($id){
	global $rpm_theme_options;
	$feedback = cc_feedback_get_by_id($id);
	if($feedback !== NULL){
		$subject = 'Contextual Consulting: Feedback received';
		$message = '<p>Contextual Consulting: Feedback received for '.get_the_title($feedback['training_id']);
		if($feedback['event_id'] > 0){
			$event_name = get_post_meta( $training_id, 'event_'.$feedback['event_id'].'_name', true );
			if($event_name <> ''){
				$message .= ' - '.$event_name;
			}
		}
		$message .= '</p>';
		$answers = maybe_unserialize($feedback['feedback']);
		$message .= '<br>';
		foreach ($answers as $key => $value) {
			$message .= '<p><strong>'.$key.'</strong><br>'.$value.'</p>';
		}
		if(site_url('', 'https') == 'https://contextualconsulting.co.uk'){
			$to = 'joe.oliver@contextualconsulting.co.uk';
		}else{
			$to = get_bloginfo('admin_email');
		}
		$headers = array(
			'From: '.$rpm_theme_options['email-from-name'].' <'.$rpm_theme_options['email-from'].'>',
			'Content-Type: text/html; charset=UTF-8',
		);
		return wp_mail($to, $subject, $message, $headers);
	}
}

