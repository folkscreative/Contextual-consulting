<?php
/**
 * Template Name: Feedback save
 * 
 * FEB 2025 .... THIS IS NO LONGER USED!!
 * 
 * this is not a real page ... it saves the data from the feedback page and redirects to the feedback thank you page
 *
 * @package Contextual
 */

if(!isset($_POST['f'])){
    // get out of here .....
    wp_redirect( home_url() );
    exit;
}

$feedback = cc_feedback_link_elements( sanitize_text_field( $_POST['f'] ) );

// if already saved .... skip
if( cc_feedback_get_by_user_training($feedback['training_id'], $feedback['event_id'], $feedback['user_id']) === NULL){

    $answers = array();
    foreach ($_POST as $key => $value) {
        if($key <> 'f'){
            $answers[ stripslashes( sanitize_title( $key ) ) ] = stripslashes( sanitize_text_field( $value ) );
        }
    }

    $data = array(
        'user_id' => $feedback['user_id'],
        'training_id' => $feedback['training_id'],
        'event_id' => $feedback['event_id'],
        'updated' => date('Y-m-d H:i:s'),
        'feedback' => maybe_serialize($answers),
    );
    $feedback_id = cc_feedback_update($data);

    if($feedback_id !== NULL){
        // no longer sending feedback emails to Joe
        // cc_emails_feedback($feedback_id);
    }

}

wp_redirect( add_query_arg( array( 'f' => $_POST['f'] ), '/feedback-thanks' ) );
exit;
