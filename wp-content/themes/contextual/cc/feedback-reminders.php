<?php
/**
 * Feedback reminder emails
 */


// Hook into WordPress cron system
function schedule_training_feedback_cron() {
    if (!wp_next_scheduled('send_training_feedback_email')) {
        wp_schedule_event(time(), 'hourly', 'send_training_feedback_email');
    }
}
add_action('wp', 'schedule_training_feedback_cron');
add_action('send_training_feedback_email', 'send_training_feedback_email');

// Define the cron job function
function send_training_feedback_email() {
    // Get last run time
    $last_run = get_option('last_training_feedback_check', time() - HOUR_IN_SECONDS);
    $current_time = time();
    
    // Find workshops completed since last check
    $args = array(
    	'post_type' => 'workshop',
    	'numberposts' => -1,
    	'fields' => 'ids',
    );
    $workshop_ids = get_posts($args);

    // any we interested in any of them?
    $trainings_to_process = array();
    foreach ($workshop_ids as $workshop_id) {
    	$workshop_end = workshop_calculate_end_timestamp( $workshop_id );
    	if( $workshop_end > $last_run && $workshop_end <= $current_time ){
    		$trainings_to_process[] = $workshop_id;
    	}
    }

    foreach ($trainings_to_process as $workshop_id) {
        $course_name = get_the_title( $workshop_id );
        $count_emails = 0;
        // find the attendees
        $user_ids = cc_attendees_all_for_training( $workshop_id );

        foreach ($user_ids as $user_id) {
            // has this user already submitted feedback?
            if( ! cc_feedback_submitted( $workshop_id, 0, $user_id ) ){
                // have they already been sent this reminder?
                $reminder_status = get_user_meta( $user_id, '_fb_reminder_'.$workshop_id, true ); // '', 1, 2, 3, 'stop'
                if( $reminder_status == '' ){
                    $user = get_user_by( 'ID', $user_id );
                    $subscriber_id = cc_mailster_get_subscriber_id( $user->user_email, $user->user_firstname, $user->user_lastname, false );
                    $mailster_tags = array(
                        'firstname' => $user->user_firstname,
                        'course_name' => $course_name,
                    );
                    sysctrl_mailster_ar_hook( 'feedback_reminder_one', $subscriber_id, $mailster_tags);
                    update_user_meta( $user_id, '_fb_reminder_'.$workshop_id, '1' ); // email one sent
                    $count_emails ++;
                }
            }
        }
        cc_debug_log_anything( 'Feedback reminder emails for workshop '.$workshop_id.' sent to '.$count_emails.' attendees.' );

        // Schedule reminder emails
        wp_schedule_single_event(time() + 3 * DAY_IN_SECONDS, 'send_training_feedback_reminder', array( 2, $workshop_id ) );
        wp_schedule_single_event(time() + 10 * DAY_IN_SECONDS, 'send_training_feedback_reminder', array( 3, $workshop_id ) );
    }
    
    // Update last run time
    update_option('last_training_feedback_check', $current_time);
}

// send the follow-up-reminders
add_action( 'send_training_feedback_reminder', 'send_training_feedback_reminder', 10, 2 );
function send_training_feedback_reminder( $email_no, $workshop_id ){
    $course_name = get_the_title( $workshop_id );
    $count_emails = 0;

    if( $email_no == 2 ){
        $acceptable_status = '1';
        $mailster_hook = 'feedback_reminder_two';
    }else{
        $acceptable_status = '2';
        $mailster_hook = 'feedback_reminder_three';
    }

    // find the attendees
    $user_ids = cc_attendees_all_for_training( $workshop_id );

    foreach ($user_ids as $user_id) {
        // has this user already submitted feedback?
        if( ! cc_feedback_submitted( $workshop_id, 0, $user_id ) ){
            // have they already been sent this reminder?
            $reminder_status = get_user_meta( $user_id, '_fb_reminder_'.$workshop_id, true ); // '', 1, 2, 3, 'stop'
            if( $reminder_status == $acceptable_status ){
                $user = get_user_by( 'ID', $user_id );
                $subscriber_id = cc_mailster_get_subscriber_id( $user->user_email, $user->user_firstname, $user->user_lastname, false );
                $mailster_tags = array(
                    'firstname' => $user->user_firstname,
                    'course_name' => $course_name,
                );
                sysctrl_mailster_ar_hook( $mailster_hook, $subscriber_id, $mailster_tags);
                update_user_meta( $user_id, '_fb_reminder_'.$workshop_id, $email_no ); // email two/three sent
                $count_emails ++;
            }
        }
    }
    cc_debug_log_anything( 'Feedback reminder email '.$email_no.' for workshop '.$workshop_id.' sent to '.$count_emails.' attendees.' );
}