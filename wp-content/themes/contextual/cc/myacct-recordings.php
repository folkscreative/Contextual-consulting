<?php
/**
 * My Account Recordings panel
 */

// May 2025 updated for courses

function cc_myacct_recordings(){
    global $wpdb;
    wp_enqueue_script( 'myacct-recordings-scripts' );
    $user_info = wp_get_current_user();
    // $date_timezone = cc_timezone_name(cc_timezone_get_user_timezone($user_info->ID));
    $date_timezone = cc_timezone_get_user_timezone($user_info->ID);
    $html = '<h3 class="d-md-none">On-demand training</h3>';
    $html .= '<div class="myacct-panel myacct-recordings-panel">';

    // we need to show publicly available recordings
    // plus recordings that the useris an attendee for (NOTE: Jan 2025, no longer showing when registered for somebody else)
    // and we'd like to show them all in descending "age" (ie ID)

    // let's get the publicly available recordings first
    /*
    $args = array(
        'post_type' => 'recording',
        'posts_per_page' => -1,
        'meta_key' => 'recording_for_sale',
        'meta_value' => 'public',
        'fields' => 'ids',
    );
    */
    // defaults added for clarity
    $args = array(
        'post_type' => 'course',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND', // default
            array(
                'key' => '_course_type',
                'value' => 'on-demand',
                'compare' => '=', // default
            ),
            array(
                'key' => '_course_status',
                'value' => 'public',
                'compare' => '=', // default
            ),
        ),
        'fields' => 'ids',
    );
    $public_recordings_ids = get_posts($args);

    // now get all the ids of the recordings that the user is an attendee for
    // $user_recordings_ids = cc_myacct_get_recordings_ids_by_user($user_info->ID);
    $user_recordings_ids = cc_trainings_recordings_for_attendee( $user_info->ID );

    // combine and sort them
    $poss_recording_ids = array_merge($public_recordings_ids, $user_recordings_ids);
    array_multisort($poss_recording_ids, SORT_DESC, SORT_NUMERIC);
    // and deduplicate it for recording ids in there twice ... mostly my testing!
    $poss_recording_ids = array_unique($poss_recording_ids);

    // now we need to remove recordings if they had access to it because they registered for the matching workshop ... in which case we only show it in the workshop panel
    $recording_ids = array();
    foreach ($poss_recording_ids as $recording_id) {
        $matching_workshop_id = recording_get_matching_workshop_id($recording_id);
        if($matching_workshop_id === NULL){
            // no match, show the recording here
            $recording_ids[] = $recording_id;
        }else{
            if( ! is_user_attendee_for_workshop( $user_info->ID, $matching_workshop_id ) ){
            // $workshops_user = cc_myacct_get_workshops_users_by_user_workshop($user_info->ID, $matching_workshop_id);
            // if($workshops_user === NULL){
                // they did not register for the workshop, therefore let's show them the recording here ... and are an attendee
                $recording_ids[] = $recording_id;
            }else{
                // they are not an attendee
            }
        }
    }

    if(count($recording_ids) == 0){
        $html .= '<p class="no-results">It looks like you don\'t have any on-demand trainings booked! Click here to see all our <a href="/online-training">on demand trainings</a>.</p>';
    }else{
        $now = time();
        if(count($recording_ids) > 1){
            $recording_class = 'closed';
        }else{
            $recording_class = 'opened';
        }
        $html .= '<div class="accordion accordion-flush" id="recordings_accordions">';
        foreach ($recording_ids as $recording_id){
            $who = cc_presenters_names($recording_id, 'none');

            if($recording_class == 'opened'){
                $button_class = 'accordion-button';
                $body_class = 'accordion-collapse collapse show';
                $recording_class = 'closed';
            }else{
                $button_class = 'accordion-button collapsed';
                $body_class = 'accordion-collapse collapse';
            }

            $html .= '<div class="recording '.$recording_class.'">';

            $html .= '<div id="training-'.$recording_id.'" class="accordion-item dark-bg">';
            $html .= '<h4 class="accordion-header" id="recordings_accordion_heading_'.$recording_id.'">';
            $html .= '<button class="'.$button_class.'" type="button" data-bs-toggle="collapse" data-bs-target="#recordings_accordion_body_'.$recording_id.'" aria-expanded="false" aria-controls="recordings_accordion_body_'.$recording_id.'">'.get_the_title($recording_id);
            if($who <> ''){
                $html .= ': '.$who;
            }
            $html .= '</button>';
            $html .= '</h4>';

            $html .= '<div id="recordings_accordion_body_'.$recording_id.'" class="'.$body_class.'" aria-labelledby="recordings_accordion_heading_'.$recording_id.'" data-bs-parent="#recordings_accordions">';
            $html .= '<div class="accordion-body">';

            $recording_access = ccrecw_user_can_view($recording_id);

            $html .= '<div class="row">';
            
            $html .= '<div class="col-12 col-sm-6 col-md-8">';
            $html .= cc_myacct_recording_view_btn( 'myacct', $recording_id );
            $html .= '</div>';

            // training details
            $html .= '<div class="col-12 col-sm-6 col-md-4 text-sm-end">';
            if( cc_myacct_show_training_dets( $recording_id ) ){
                $html .= '<a class="btn btn-to-train btn-myacct btn-sm w-100-xso" href="#" data-bs-toggle="modal" data-bs-target="#myacct-training-dets-modal" data-trainingid="'.$recording_id.'"><i class="fa-solid fa-circle-info"></i> Course info</a>';
            }
            $html .= '</div>';

            $html .= '</div>';

            $html .= '<div class="row">';
            // wrap the buttons inside a flex container and use Bootstrap’s gap utility to control spacing so that when they overflow only a second line they still look ok
            $html .= '<div class="col">';
            $html .= '<div class="d-flex flex-wrap gap-2">';

            // resources
            // if( resources_exist( $recording_id, 'any' ) ){
            if( resources_exist_training_course( $recording_id ) ){
                $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#training-resources-modal" data-trainingid="'.$recording_id.'" data-eventid="">Training resources</button>';
            }

            // new certificate button
            if( cc_certs_training( $recording_id ) ){
                $html .= '<a class="btn btn-cecredits btn-myacct btn-sm mb-1 w-100-xso w-49-smo cecredits-btn" href="#" data-bs-toggle="modal" data-bs-target="#cecredits-modal" data-trainingid="'.$recording_id.'" data-eventid=""><i class="fa-solid fa-award fa-fw"></i> Certificate</a>';
            }

            // participation
            $feedback_questions = get_post_meta($recording_id, '_feedback_questions', true);
            $old_fb_url = get_post_meta($recording_id, 'workshop_feedback', true);
            $recording_linkedin = get_post_meta($recording_id, 'workshop_linkedin', true);
            if($feedback_questions <> '' || $old_fb_url <> '' || $recording_linkedin <> ''){
                if(cc_feedback_submitted($recording_id)){
                    $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo disabled">Give Feedback <i class="fa-solid fa-check"></i></button>';
                }else{
                    if($feedback_questions <> ''){
                        $fb_url = add_query_arg(
                            array(
                                'f' => cc_feedback_link_code($recording_id, 0, $user_info->ID),
                            ),
                            '/feedback',
                        );
                        $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#myacct-feedback-modal" data-trainingid="'.$recording_id.'" data-eventid="0">Give feedback</button>';
                    }elseif($old_fb_url <> ''){
                        $html .= '<a class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" href="'.$old_fb_url.'" target="_blank">Give feedback</a>';
                    }
                }
                if($recording_linkedin <> ''){
                    $html .= '<a class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" href="'.$recording_linkedin.'" target="_blank"><i class="fa-brands fa-linkedin fa-fw"></i></a>';
                }
            }

            $html .= '</div>'; // flex
            $html .= '</div>'; // col
            $html .= '</div>'; // row

            // joining info
            $recording_joining = get_post_meta($recording_id, 'workshop_joining', true);
            if($recording_joining <> ''){
                $html .= '<p class="event-joining">'.$recording_joining.'</p>';
            }

            $html .= '</div><!-- .accordion-body -->';
            $html .= '</div><!-- #recordings_accordion_body_ -->';
            $html .= '</div><!-- .accordion-item -->';
            $html .= '</div><!-- .recording -->';

        }
        $html .= '</div><!-- .accordion -->';
    }

    $html .= '</div><!-- .myacct-recordings-panel -->';

    return $html;

}

// should we allow this person to cancel their recording registration?
// returns bool
function cc_myacct_recording_offer_cancellation($recording_id, $user_id=NULL){
    // to be able to cancel they must be a CNWL user
    if($user_id === NULL){
        $user_id = get_current_user_id();
    }
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    if($portal_user <> 'cnwl') return false;
    // and the recording must not have been viewed yet
    // $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording_id, true);
    $recording_meta = get_recording_meta( $user_id, $recording_id );
    if( isset( $recording_meta['num_views'] ) && $recording_meta['num_views'] > 0 ){
        return false;
    }
    return true;
}

// the view recording button
// $event_id not used!!!!!
function cc_myacct_recording_view_btn( $btn_type, $training_id, $event_id=0 ){
    $html = '';
    $user_id = get_current_user_id();
    
    // Check contract status FIRST - this takes priority over individual recording access
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    $contract_expired = false;
    $contract_type = '';
    $show_expired_message = false;
    
    if (!empty($portal_user) && function_exists('org_register_status') && function_exists('get_organisation_contract')) {
        $org_status = org_register_status($portal_user, $training_id);
        if ($org_status['status'] == 'expired') {
            $contract_expired = true;
            $contract = get_organisation_contract($portal_user);
            $contract_type = !empty($contract->contract_type) ? $contract->contract_type : 'unlimited';
            
            // For unlimited contracts, show expired message regardless of individual access times
            if ($contract_type == 'unlimited') {
                $show_expired_message = true;
            }
            // For fixed_number contracts, individual access times still matter
            // (they retain access to content registered before expiry)
        }
    }
    
    // If unlimited contract is expired, show message and return early
    if ($show_expired_message) {
        $html .= '<div class="alert alert-warning mb-3 py-2 px-3">';
        $html .= '<p class="mb-0 small"><i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Access unavailable</strong><br>';
        $html .= 'Your organisation\'s contract has expired. Please contact your administrator about renewal.</p>';
        $html .= '</div>';
        return $html;
    }
    
    // Now check individual recording access
    $recording_access = ccrecw_user_can_view($training_id);
    
    // If no access and it's due to expired fixed_number contract, show appropriate message
    if (!$recording_access['access'] && $contract_expired && $contract_type == 'fixed_number') {
        $html .= '<div class="alert alert-info mb-3 py-2 px-3">';
        $html .= '<p class="mb-0 small"><i class="fa-solid fa-info-circle me-2"></i><strong>Contract expired</strong><br>';
        $html .= 'Your organisation\'s contract has expired. Contact your administrator about renewal.</p>';
        $html .= '</div>';
        return $html;
    }
    
    // Original logic for when they have access or there's an expiry date
    if($recording_access['access'] || $recording_access['expiry_date'] <> ''){
        $expiry_msg = '';
        if($recording_access['expiry_date'] <> ''){
            if($recording_access['access']){
                $expiry_msg = 'Available until '.$recording_access['expiry_date'];
            }else{
                $expiry_msg = 'Access expired '.$recording_access['expiry_date'];
            }
        }
        $url = '';
        if( $btn_type == 'modal' ){
            $btn_class = 'btn-orange btn-sm w-100';
        }else{
            $btn_class = 'btn-orange btn-sm w-100-xso';
        }            
        if($recording_access['access']){
            // $url = add_query_arg( 'id', $recording, '/watch-recording' );
        }else{
            $btn_class .= ' disabled';
        }

        // new training delivery page

        if($expiry_msg == ''){
            $margin = 'mb-3';
        }else{
            $margin = 'mb-1';
        }

        if( $btn_type == 'modal' ){
            $html .= '<a class="btn '.$margin.' recording-btn '.$btn_class.'" data-bs-toggle="modal" data-bs-target="#myacct-training-modal" data-trainingid="'.$training_id.'" data-eventid="'.$event_id.'">View course</a>';
        }else{
            $nonce = wp_create_nonce( 'course_id' . $training_id );
            $html .= '
                <form action="'.esc_url( site_url( '/training-delivery/' ) ).'" method="post" class="'.$margin.'">
                    <input type="hidden" name="id" value="'.esc_attr( $training_id ).'">
                    <input type="hidden" name="training_delivery_nonce" value="'.esc_attr( $nonce ).'">
                    <button type="submit" class="btn recording-btn '.$btn_class.'">View course</button>
                </form>';
        }

        if( $expiry_msg <> '' ){
            $html .= '<p class="small recording-available mb-3">'.$expiry_msg.'</p>';
        }
    }
    return $html;
}