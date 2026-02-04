<?php
/**
 * My Account Workshops panel
 */

// now (Jan 25) always shows upcoming and past training ... but only if the user is an attendee
function cc_myacct_workshops($future='not used'){
    global $wpdb;
    wp_enqueue_script( 'myacct-workshops-scripts' );
    $user_info = wp_get_current_user();
    $date_timezone = cc_timezone_get_user_timezone($user_info->ID);  // now returns the name (eg Europe/London)
    $user_timezone = cc_timezone_get_user_timezone_pretty($user_info->ID, $date_timezone); // local name (eg Llanelli)
    $html = '<h3 class="d-md-none">Live training</h3>';
    // now we only want attendees
    // $wkshop_users = cc_myacct_get_workshops( $user_info->ID );
    $wkshop_users = cc_myacct_get_workshops( $user_info->ID, '', false, 'a');
    $featured_exclude = array();
    if( ( count( $wkshop_users ) ) == 0 ){
        $html .= '<p class="no-results">It looks like you don\'t have any trainings booked! Click here to see all our <a href="/live-training">upcoming trainings</a>.</p>';
        // $html .= '<p class="no-results">No past training found since January 2021. If you are looking for details of training before January 2021, please <a href="/contact-us">contact us</a></p>';

        // now show a featured workshop if there is one ... max of one returned
        foreach ( workshop_featured_post() as $workshop ) {
            $html .= '<hr>';
            $html .= '<div class="myacct-panel myacct-workshops-panel featured-training-panel"><h4>Don\'t Miss It!</h4>';
            $html .= cc_wksp_myacct_featured($workshop);
            $html .= '</div>';
        }
    }else{
        $now = time();

        // we need to know what's coming up ... just upcoming, just past or both
        $includes_past = $includes_future = false;
        foreach ($wkshop_users as $wkshop_user) {
            $end_timestamp = workshop_calculate_end_timestamp( $wkshop_user['workshop_id'] );
            if( $end_timestamp ){
                if( $end_timestamp < $now ){
                    $includes_past = true;
                }else{
                    $includes_future = true;
                }
                if( $includes_past && $includes_future ) break;
            }
        }

        if( count( $wkshop_users ) > 1 ){
            $workshop_class = 'closed';
        }else{
            $workshop_class = 'opened';
        }
        if( $includes_past && $includes_future ){
            $html .= '<h4>Upcoming training</h4>';
        }
        $html .= '<div class="myacct-panel myacct-workshops-panel future-workshops-panel">';
        $html .= '<div class="accordion accordion-flush" id="upcoming_workshops">';
        foreach ($wkshop_users as $wkshop_user) {
            $html .= cc_myacct_workshop_html($wkshop_user['workshop_id'], $workshop_class, $date_timezone, $user_timezone, $wkshop_user['payment_id'], $future);
            $featured_exclude[] = $wkshop_user['workshop_id'];
            $workshop_class = 'closed';
        }
        $html .= '</div><!-- #upcoming_workshops -->';
        $html .= '</div><!-- .myacct-workshops-panel -->';
    }
    return $html;
}

// returns the html for a workshop 
// now (jun 2024) accommodates multiple registrations by the same user// simpler now as is just shown to attendees and ignore registration info
function cc_myacct_workshop_html( $workshop_id, $workshop_class, $date_timezone, $user_timezone, $payment_id, $future ){
    static $last_training = 'none';
    $now = time();
    $user_info = wp_get_current_user();
    $multi_event = workshop_is_multi_event($workshop_id);
    $pretty_dates = workshop_calculated_prettydates($workshop_id, $date_timezone);
    $workshop_zoom = get_post_meta($workshop_id, 'workshop_zoom', true);

    if($workshop_class == 'opened'){
        $button_class = 'accordion-button';
        $body_class = 'accordion-collapse collapse show';
    }else{
        $button_class = 'accordion-button collapsed';
        $body_class = 'accordion-collapse collapse';
    }

    $html = '<div id="training-'.$workshop_id.'" class="workshop '.$workshop_class.'">';

    $html .= '<div class="accordion-item dark-bg">';
    
    $html .= '<h4 class="accordion-header" id="upcoming_workshop_heading_'.$workshop_id.'">';
    $html .= '<button class="'.$button_class.'" type="button" data-bs-toggle="collapse" data-bs-target="#upcoming_workshop_body_'.$workshop_id.'" aria-expanded="false" aria-controls="upcoming_workshop_body_'.$workshop_id.'"><span class="flex-column my-2"><span class="dates">'.$pretty_dates['locale_start_date'].'</span><span class="title">'.get_the_title($workshop_id).'</span></span></button>';
    $html .= '</h4>';

    $html .= '<div id="upcoming_workshop_body_'.$workshop_id.'" class="'.$body_class.'" aria-labelledby="upcoming_workshop_heading_'.$workshop_id.'" data-bs-parent="#upcoming_workshops">';
    $html .= '<div class="accordion-body">';

    $html .= '<div class="row mb-3">';

    // dates
    $html .= '<div class="col-12 col-sm-6 col-md-8">';
    $venue = get_post_meta( $workshop_id, 'event_1_venue_name', true );
    if( $venue <> '' ){
        // face to face workshop
        $html .= '<div class="face-to-face venue mb-1"><i class="fa-solid fa-location-dot fa-fw"></i> In person: ';
        $venue_link = get_post_meta( $workshop_id, 'meta_c', true );
        if( $venue_link <> '' ){
            $html .= '<a href="'.esc_url( $venue_link ).'" target="_blank">';
        }
        $html .= $venue;
        if( $venue_link <> '' ){
            $html .= '</a>';
        }
        $html .= '</div><div class="local-time workshop-time mb-1"><i class="fa-regular fa-clock fa-fw"></i> London: '.$pretty_dates['london_date'].' ';
    }else{
        $html .= '<div class="local-time workshop-time mb-1"><i class="fa-regular fa-clock fa-fw"></i> '.$pretty_dates['locale_date'].' ';
    }
    $html .= '<a class="btn btn-dates btn-myacct btn-sm mb-1 w-100-xso" href="#" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.$workshop_id.'">Course schedule</a>';
    $html .= '</div></div>';

    // training details
    $html .= '<div class="col-12 col-sm-6 col-md-4 text-sm-end">';
    if( cc_myacct_show_training_dets( $workshop_id ) ){
        $html .= '<a class="btn btn-to-train btn-myacct btn-sm w-100-xso" href="#" data-bs-toggle="modal" data-bs-target="#myacct-training-dets-modal" data-trainingid="'.$workshop_id.'"><i class="fa-solid fa-circle-info"></i> Course info</a>';
    }
    $html .= '</div>';

    $html .= '</div><!-- .row -->';

    // where are we in relation to the workshop (before it starts, while it's ongoing, after it's done)?
    $workshop_start = workshop_event_calc_start_timestamp( $workshop_id, 1 );
    $workshop_end = workshop_calculate_end_timestamp( $workshop_id );
    if( ! $workshop_start ){
        $workshop_start = $workshop_end;
    }
    if( ! $workshop_end ){
        $workshop_end = $workshop_start;
    }
    $workshop_timing = 'not-started';
    if( $workshop_start && $workshop_end && $workshop_end >= $workshop_start ){
        if( $now > $workshop_end ){
            $workshop_timing = 'ended';
        }elseif( $now > $workshop_start ){
            $workshop_timing = 'on-now';
        }
    }

    if( $multi_event ){
        // we need to know which events this user is an attendee for
        // currently that comes from the payment records
        $attending_events = workshop_user_events_attendee( $workshop_id, $user_info->ID );
        foreach ($attending_events as $event_id) {
            // where are we in relation to this event?
            if( $workshop_timing == 'not-started' || $workshop_timing == 'ended' ){
                $event_timing = $workshop_timing;
            }else{
                $event_start = workshop_event_calc_start_timestamp( $workshop_id, $event_id );
                $event_end = workshop_event_calc_end_timestamp( $workshop_id, $event_id );
                if( ! $event_start ){
                    $event_start = $event_end;
                }
                if( ! $event_end ){
                    $event_end = $event_start;
                }
                $event_timing = 'not-started';
                if( $event_start && $event_end && $event_end >= $event_start ){
                    if( $now > $event_end ){
                        $event_timing = 'ended';
                    }elseif( $now > $event_start ){
                        $event_timing = 'on-now';
                    }
                }
            }

            $html .= '<div class="myacct-training-event-wrap mb-3 p-3">';
            $html .= '<h5 class="mb-2">'.get_post_meta( $workshop_id, 'event_'.$event_id.'_name', true ).'</h5>';

            if( $event_timing <> 'ended' ){
                // on-now or not-started
                // show joining info inc zoom link stuff
                $html .= cc_myacct_workshops_attendance_html( $workshop_id, $event_id, $multi_event );
                // joining info
                $event_joining = get_post_meta( $workshop_id, 'event_'.$event_id.'_joining', true);
                if($event_joining <> ''){
                    $html .= '<div class="mb-3">';
                    $html .= '<label class="form-label"><i class="fa-solid fa-circle-info"></i> Joining information:</label>';
                    $html .= '<textarea class="form-control" rows="3" disabled>'.$event_joining.'</textarea>';
                    $html .= '</div>';
                }
            }else{
                // maybe show link to recording
                $html .= '<div class="row">';
                $html .= '<div class="col">';
                $html .= cc_myacct_workshops_recording_btn( $workshop_id, $event_id );
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '<div class="row">';
            $html .= '<div class="col">';
            $html .= '<div class="d-flex flex-wrap gap-2">';

            // resources
            if( resources_exist( $workshop_id, $event_id ) ){
                $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#training-resources-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'">Training resources</button>';
            }

            if( $event_timing == 'ended' ){
                // the feedback btn
                $html .= cc_myacct_workshops_participation_html( $workshop_id, $event_id );

                // certificate button
                if( cc_certs_training( $workshop_id, $event_id ) ){
                    $html .= '<button type="button" class="btn btn-myacct btn-cecredits btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#cecredits-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'">Certificate</button>';
                }
            }

            $html .= '</div><!-- .d-flex -->';
            $html .= '</div><!-- .col -->';
            $html .= '</div><!-- .row -->';

            $html .= '</div>';

        }

        // now look for training level stuff to show

        $html .= '<div class="row">';
        $html .= '<div class="col">';
        $html .= '<div class="d-flex flex-wrap gap-2">';

        // resources
        if( resources_exist( $workshop_id ) ){
            $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#training-resources-modal" data-trainingid="'.$workshop_id.'" data-eventid="">Training resources</button>';
        }


        if( $workshop_timing == 'ended' ){

            // the feedback btn
            $html .= cc_myacct_workshops_participation_html( $workshop_id );

            // certificate button
            if( cc_certs_training( $workshop_id, 0 ) ){
                $html .= '<button type="button" class="btn btn-myacct btn-cecredits btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#cecredits-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'">Certificate</button>';
            }
        }

        $html .= '</div><!-- .d-flex -->';
        $html .= '</div><!-- .col -->';
        $html .= '</div><!-- .row -->';


    }else{
        // single event
        $event_id = 0;

        if( $workshop_timing <> 'ended' ){
            // on-now or not-started
            // show joining info inc zoom link stuff
            $html .= cc_myacct_workshops_attendance_html( $workshop_id, 0, $multi_event );
            // joining info
            $workshop_joining = get_post_meta($workshop_id, 'workshop_joining', true);
            if($workshop_joining <> ''){
                $html .= '<div class="mb-3">';
                $html .= '<label class="form-label"><i class="fa-solid fa-circle-info"></i> Joining information:</label>';
                $html .= '<textarea class="form-control" rows="3" disabled>'.$workshop_joining.'</textarea>';
                $html .= '</div>';
            }
        }
        if( $workshop_timing <> 'not-started' ){
            // maybe show link to recording
            $html .= '<div class="row">';
            $html .= '<div class="col">';
            $html .= cc_myacct_workshops_recording_btn( $workshop_id );
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '<div class="row">';
        $html .= '<div class="col">';
        $html .= '<div class="d-flex flex-wrap gap-2">';

        // resources
        if( resources_exist( $workshop_id ) ){
            $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#training-resources-modal" data-trainingid="'.$workshop_id.'" data-eventid="">Training resources</button>';
        }


        if( $workshop_timing == 'ended' ){

            // the feedback btn
            $html .= cc_myacct_workshops_participation_html( $workshop_id );

            // certificate button
            if( cc_certs_training( $workshop_id, $event_id ) ){
                $html .= '<button type="button" class="btn btn-myacct btn-cecredits btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#cecredits-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'">Certificate</button>';
            }
        }

        $html .= '</div><!-- .d-flex -->';
        $html .= '</div><!-- .col -->';
        $html .= '</div><!-- .row -->';

    }

    $html .= '</div><!-- .accordion-body -->';
    $html .= '</div><!-- . -->';
    $html .= '</div><!-- .accordion-item -->';
    $html .= '</div><!-- .workshop -->';






    /*


    // $who = get_post_meta($workshop_id, 'presenter', true);

    // get all payments for this user where they registered for it (could be many) or are attending it (should be no more than one)
    $payments = cc_myacct_all_payments_for_user( $user_info->ID, $workshop_id );

    if( empty( $payments ) ){
        // shouldn't find ourselves in this situation!
        return '';
    }

    // start assembling the workshop html, regardless of whether they are a registrant or attendee




    $user_is_registrant = $user_is_attendee = false;
    $payment_dates = array();
    $receipt_btns = $resend_btns = array();

    // has this user registered for anybody other than themselves to attend?
    $count_regs = $count_atts = 0;
    $attendee_html = '';
    if( isset( $payments['r'] ) ){
        foreach ($payments['r'] as $payment_id => $attendees) {
            $user_is_registrant = true;
            $count_regs ++;
            $payment = cc_paymentdb_get_payment( $payment_id );
            $payment_dates[$payment_id] = cc_timezone_datetime_convert( $payment['last_update'], 'UTC', $date_timezone );
            // collect data for the reg buttons later
            if( $payment_dates[$payment_id] <> '' ){
                if( $payment['status'] == 'Payment successful: ' ){
                    $receipt_btns[$payment_id] = $payment_dates[$payment_id];
                }
                if( $payment['pmt_method'] == 'online' ){
                    $resend_btns[$payment_id] = $payment_dates[$payment_id];
                }
            }
            // attendees are rows from the attendees table
            foreach ($attendees as $attendee) {
                $count_atts ++;
                $attendee_html .= '<div class="attendee mb-1"><i class="fa-regular fa-user fa-fw"></i> Attendee: ';
                if( $attendee['user_id'] == $user_info->ID ){
                    $user_is_attendee = true;
                    $attendee_html .= 'Yourself';
                }else{
                    $user = get_user_by('ID', $attendee['user_id']);
                    $attendee_html .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';
                }
                if( $payment_dates[$payment_id] <> '' ){
                    $attendee_html .= ' <span class="reg-date small">Registered: '.$payment_dates[$payment_id].'</span>';
                }
                $attendee_html .= '</div>';
            }
        }
    }

    // has this attendee been registered by anybody else?
    $registrant_html = '';
    if( isset( $payments['a'] ) ){
        foreach ($payments['a'] as $payment_id => $wkshop_users){
            // this gives us rows from the workshops users table where the user is marked as an attendee (not registrant)
            $user_is_attendee = true;
            $payment = cc_paymentdb_get_payment( $payment_id );
            $reg_userid = cc_paymentdb_get_userid( $payment );
            $user = get_user_by('ID', $reg_userid);
            if( ! isset( $payment_dates[$payment_id] ) ){
                $payment_dates[$payment_id] = cc_timezone_datetime_convert( $payment['last_update'], 'UTC', $date_timezone );
            }
            foreach ($wkshop_users as $wkshop_user) {
                $registrant_html .= '<div class="attendee mb-1"><i class="fa-solid fa-file-signature"></i> Training registered by ';
                $registrant_html .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';
                if( $payment_dates[$payment_id] <> '' ){
                    $registrant_html .= ' <span class="reg-date small">Registered: '.$payment_dates[$payment_id].'</span>';
                }
                $registrant_html .= '</div>';
            }
        }
    }

    if( ! isset( $payments['a'] ) && $count_regs == 1 && $count_atts == 1 && $user_is_registrant && $user_is_attendee ){
        // simple registration, self registered, don't show the attendee/registrant stuff
    }else{
        if( $attendee_html <> '' ){
            $html .= '<div class="attendee-wrap mb-3">'.$attendee_html.'</div>';
        }
        if( $registrant_html <> '' ){
            $html .= '<div class="attendee-wrap mb-3">'.$registrant_html.'</div>';
        }
    }





    /*



    if( $user_is_attendee ){
        if( $workshop_timing == 'ended' ){
            // maybe show a link to the recourding            
            $recording = get_post_meta($workshop_id, $meta_key.'_recording', true);

        }

    }










        // any training resources?
        if( cc_myacct_workshops_any_resources( $workshop_id ) ){
            $html .= '<div class="mb-3">';
            $html .= '<button type="button" class="btn btn-myacct btn-sm" data-bs-toggle="modal" data-bs-target="#myacct-resource-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'">Training resources</button>';
            $html .= '</div>';
        }
    }




    */



    /*
    $payment_data = cc_paymentdb_get_payment($payment_id);
    if($payment_data === NULL){
        $payment_data = cc_paymentdb_empty_payment();
    }
    // $workshop_subtitle = get_post_meta($workshop_id, 'subtitle', true);
    $attendees = cc_attendees_for_payment($payment_data['id']);

    // old payment records do not have reg_userid set
    if( isset($payment_data['reg_userid']) && $payment_data['reg_userid'] > 0 ){
        // goodo
    }else{
        $payment_data['reg_userid'] = cc_myacct_get_user($payment_data, 'r');
    }

    // is this person the registrant or an attendee or both?
    $registrant_user = false;
    $attendee_user = false;
    if($user_info->ID == $payment_data['reg_userid']){
        $registrant_user = true;
    }
    foreach ($attendees as $attendee) {
        if($attendee['user_id'] == $user_info->ID){
            $attendee_user = true;
            break;
        }
    }
    $show_attendees = false;
    if(count($attendees) > 1 || $registrant_user <> $attendee_user){
        $show_attendees = true;
    }
    */

    // $html .= '<h6 class="mb-1">'.get_the_title($workshop_id).'</h6>';
    /*
    if($workshop_subtitle <> ''){
        $html .= '<div class="mb-1">'.$workshop_subtitle.'</div>';
    }
    */
    /*
    if(!$multi_event){
        if($pretty_dates['locale_datetime'] <> $pretty_dates['london_date']){
            $html .= '<div class="local-time workshop-time';
            if($show_attendees){
                // about to show attendee
                $html .= ' mb-1';
            }else{
                $html .= ' mb-3';
            }
            $html .= '"><i class="fa-regular fa-clock fa-fw"></i> '.$user_timezone.' time: '.$pretty_dates['locale_datetime'].'</div>';
        }
    }
    */
    /*
    if($payment_data['attendee_email'] <> '' && $payment_data['attendee_email'] <> $payment_data['email']){
        $html .= '<div class="attendee mb-3"><i class="fa-regular fa-user fa-fw"></i> Attendee: ';
        if($payment_data['attendee_firstname'] <> '' || $payment_data['attendee_lastname'] <> ''){
            $html .= $payment_data['attendee_firstname'].' '.$payment_data['attendee_lastname'].' ('.$payment_data['attendee_email'].')';
        }else{
            $html .= $payment_data['attendee_email'];
        }
        $html .= '</div>';
    }
    */
    /*
    if($show_attendees){
        $num_attendees = count($attendees);
        $count_attendees = 0;
        if($registrant_user){
            // registrant
            foreach ($attendees as $attendee) {
                $count_attendees ++;
                if($count_attendees == $num_attendees){
                    $class = 'mb-3';
                }else{
                    $class = 'mb-1';
                }
                $html .= '<div class="attendee '.$class.'"><i class="fa-regular fa-user fa-fw"></i> Attendee: ';
                if($attendee['registrant'] == 'r'){
                    $html .= 'Yourself';
                }else{
                    $user = get_user_by('ID', $attendee['user_id']);
                    $html .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';
                }
                $html .= '</div>';
            }
        }else{
            // attendee
            $html .= '<div class="attendee mb-3"><i class="fa-solid fa-file-signature"></i> Training registered by ';
            $user = get_user_by('ID', $payment_data['reg_userid']);
            $html .= $user->user_firstname.' '.$user->user_lastname.' ('.$user->user_email.')';
            $html .= '</div>';
        }
    }
    */

    /*
    if($multi_event){
        $event_ids = explode(',', $payment_data['event_ids']);
        foreach ($event_ids as $event_id){
            $event_id = trim($event_id);
            if($event_id <> ''){
                $html .= '<div class="event-wrap">';
                $html .= '<h5 class="event-title" data-event_id="'.$event_id.'">'.get_post_meta($workshop_id, 'event_'.$event_id.'_name', true).'</h5>';
                $event_end_timestamp = workshop_event_calc_end_timestamp($workshop_id, $event_id);
                $event_start_timestamp = workshop_event_calc_start_timestamp($workshop_id, $event_id);
                $show_attendance = false;
                if($event_start_timestamp){
                    $date_times = workshop_event_pretty_date_range($event_start_timestamp, $event_end_timestamp, $date_timezone);
                    $html .= '<p class="local-time mb-0 event-time"><i class="fa-regular fa-clock fa-fw"></i> '.$date_timezone.': ';
                    $show_mins = false;
                    if($event_end_timestamp && $event_end_timestamp > $now){
                        $show_mins = true;
                        $show_attendance = true;
                    }elseif($event_start_timestamp > $now){
                        $show_mins = true;
                        $show_attendance = true;
                    }
                    if($show_mins){
                        $html .= $date_times['locale_datetime'];
                    }else{
                        $html .= $date_times['locale_date'];
                    }
                    $html .= '</p>';
                }
                if($user_is_attendee){
                    $html .= cc_myacct_workshops_resources_html($workshop_id, $event_id);
                    if($show_attendance){
                        $html .= cc_myacct_workshops_attendance_html($workshop_id, $event_id);
                    }

                    if($future){
                        $event_joining = get_post_meta($workshop_id, 'event_'.$event_id.'_joining', true);
                        if($event_joining <> ''){
                            $html .= '<p class="event-joining">'.$event_joining.'</p>';
                        }
                    }

                    if(!$future){
                        $html .= cc_myacct_workshops_participation_html($workshop_id, $event_id);
                    }
                }

                $html .= '</div><!-- .event-wrap -->';
            }
        }
    }

    if($user_is_attendee){

        // workshop wide stuff
        $html .= cc_myacct_workshops_resources_html($workshop_id);
        $workshop_end_timestamp = workshop_calculate_end_timestamp($workshop_id);
        if($workshop_end_timestamp && $workshop_end_timestamp > $now){
            $html .= cc_myacct_workshops_attendance_html($workshop_id, 0, $multi_event);
        }

        // joining info
        if($future){
            $workshop_joining = get_post_meta($workshop_id, 'workshop_joining', true);
            if($workshop_joining <> ''){
                $html .= '<div class="event-joining mb-2">'.$workshop_joining.'</div>';
            }
        }

    }

    // show registration stuff for online payments and completed payments
    if ( $user_is_registrant ){
        // $html .= '<h6 class="mb-0">Registration:</h6>';
        $html .= '<div class="row mb-3">';
        if( count( $receipt_btns ) > 0 ){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            if( count( $receipt_btns ) == 1 ){
                foreach ($receipt_btns as $payment_id => $datetime) {
                    $receipt_parms = ccpdf_receipt_parms_encode($payment_id);
                    $receipt_url = add_query_arg(array('r' => $receipt_parms), site_url('/receipt/'));
                    $html .= '<a class="btn btn-training btn-sm mb-3 w-100 pdf-receipt" href="'.$receipt_url.'" target="_blank"><i class="fa-solid fa-file-lines"></i> Your Receipt</a>';
                }
            }else{
                $html .= '<div class="dropup mb-3"><button class="btn btn-training dropdown-toggle btn-sm w-100 pdf-receipt" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-file-lines"></i> Your Receipt</button><ul class="dropdown-menu">';
                foreach ($receipt_btns as $payment_id => $datetime) {
                    $receipt_parms = ccpdf_receipt_parms_encode($payment_id);
                    $receipt_url = add_query_arg(array('r' => $receipt_parms), site_url('/receipt/'));
                    $html .= '<li><a class="dropdown-item" href="'.$receipt_url.'" target="_blank">'.$datetime.'</a></li>';
                }
                $html .= '</ul></div>';
            }
            $html .= '</div>';
        }
        if( count( $resend_btns ) > 0 ){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            if( count( $resend_btns ) == 1 ){
                foreach ($resend_btns as $payment_id => $datetime) {
                    $html .= '<a class="btn btn-training btn-sm mb-1 w-100 resend-reg-btn" data-paymentid="'.$payment_id.'" href="#0"><i class="fa-solid fa-envelope"></i> Resend Reg. Email</a>';
                    $html .= '<div id="resend-reg-msg-'.$payment_id.'" class="resend-reg-msg"></div>';
                }
            }else{
                $html .= '<div class="dropup mb-1"><button class="btn btn-training dropdown-toggle btn-sm w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-envelope"></i> Resend Reg. Email</button><ul class="dropdown-menu">';
                foreach ($resend_btns as $payment_id => $datetime) {
                    $html .= '<li><a class="dropdown-item resend-reg-btn" data-paymentid="'.$payment_id.'" href="#0">'.$datetime.'</a></li>';
                }
                $html .= '</ul></div>';
                foreach ($resend_btns as $payment_id => $datetime) {
                    $html .= '<div id="resend-reg-msg-'.$payment_id.'" class="resend-reg-msg"></div>';
                }
            }
            $html .= '</div>';
        }
        if( $count_regs == 1 && cc_myacct_workshop_offer_cancellation($workshop_id) ){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            $html .= '<a class="btn btn-training btn-sm mb-3 w-100 cancel-btn" data-trainingid="'.$workshop_id.'" data-type="w" data-title="'.get_the_title($workshop_id).'" data-bs-toggle="modal" data-bs-target="#cancellation-modal"><i class="fa-solid fa-ban"></i> Cancel registration</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    /*
    if( $registrant_user && ( $payment_data['pmt_method'] == 'online' || $payment_data['status'] == 'Payment successful: ' ) ){
        if($reg_btn['status'] == 'Payment successful: '){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            $receipt_parms = ccpdf_receipt_parms_encode($payment_id);
            $receipt_url = add_query_arg(array('r' => $receipt_parms), site_url('/receipt/'));
            $html .= '<a class="btn btn-training btn-sm mb-3 w-100 pdf-receipt" href="'.$receipt_url.'" target="_blank"><i class="fa-solid fa-file-lines"></i> Your Receipt</a>';
            $html .= '</div>';
        }
        if($reg_btn['pmt_method'] == 'online'){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            $html .= '<a class="btn btn-training btn-sm mb-1 w-100 resend-reg-btn" data-paymentid="'.$payment_id.'"><i class="fa-solid fa-envelope"></i> Resend Reg. Email</a>';
            $html .= '<div id="resend-reg-msg-'.$payment_id.'" class="resend-reg-msg"></div>';
            $html .= '</div>';
        }
        if(cc_myacct_workshop_offer_cancellation($workshop_id)){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            $html .= '<a class="btn btn-training btn-sm mb-3 w-100 cancel-btn" data-trainingid="'.$workshop_id.'" data-type="w" data-title="'.get_the_title($workshop_id).'" data-bs-toggle="modal" data-bs-target="#cancellation-modal"><i class="fa-solid fa-ban"></i> Cancel registration</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    */
    /*
    if( $user_is_attendee && !$future ){
        $html .= cc_myacct_workshops_participation_html($workshop_id);
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';
    */

    // put a line between the future and past training
    if( ( $last_training == 'not-started' || $last_training == 'on-now' ) && $workshop_timing == 'ended' ){
        // showing first ended training where we have previously shown future training
        $html = '<h4>Past training</h4>'.$html;
    }
    $last_training = $workshop_timing;


    return $html;
}

// returns the recording btn (if set up)
function cc_myacct_workshops_recording_btn( $workshop_id, $event_id=0, $btn_type='myacct' ){
    $html = '';
    if($event_id > 0){
        $meta_key = 'event_'.$event_id;
    }else{
        $meta_key = 'workshop';
    }
    // get the id of the recording (if there is one)
    $recording = (int) get_post_meta($workshop_id, $meta_key.'_recording', true);
    if( $recording > 0 ){
        $html = cc_myacct_recording_view_btn( $btn_type, $recording, $event_id );
        /*
        $recording_access = ccrecw_user_can_view($recording);
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
                $btn_class = 'btn-training btn-sm w-100';
            }else{
                $btn_class = 'btn-myacct';
            }            
            if($recording_access['access']){
                // $url = add_query_arg( 'id', $recording, '/watch-recording' );
            }else{
                $btn_class .= ' disabled';
            }
            if($expiry_msg == ''){
                $html .= '<a class="btn mb-3 recording-btn '.$btn_class.'" data-bs-toggle="modal" data-bs-target="#myacct-training-modal" data-trainingid="'.$recording.'" data-eventid="'.$event_id.'">View course</a>';
            }else{
                $html .= '<a class="btn mb-1 recording-btn '.$btn_class.'" data-bs-toggle="modal" data-bs-target="#myacct-training-modal" data-trainingid="'.$recording.'" data-eventid="'.$event_id.'">View course</a>';
                $html .= '<p class="small recording-available mb-3">'.$expiry_msg.'</p>';
            }
        }
        */
    }
    return $html;
}

// returns the resources html for a workshop or event
// ############### I don't think this function is used any more ####################
function cc_myacct_workshops_resources_html($workshop_id, $event_id=0){
    ccpa_write_log( 'function cc_myacct_workshops_resources_html is still used!' );
    if($event_id > 0){
        $meta_key = 'event_'.$event_id;
    }else{
        $meta_key = 'workshop';
    }
    $recording = get_post_meta($workshop_id, $meta_key.'_recording', true);
    $slides = get_post_meta($workshop_id, $meta_key.'_slides', true);
    $resources = get_post_meta($workshop_id, $meta_key.'_resources', true);
    // $certificate = get_post_meta($workshop_id, $meta_key.'_certificate', true);
    $recording_access = ccrecw_user_can_view($recording);
    /*
    $recording_certificate = get_post_meta($recording, 'workshop_certificate', true);
    if(!$recording_access['access'] && $recording_access['expiry_date'] == ''){
        // has not had access to the recording ... don't offer a recording cert
        $recording_certificate = '';
    }
    */
    $html = '';
    if($event_id == 0){
        $resource_files_html = resources_show_list($workshop_id);
    }else{
        $resource_files_html = '';
    }

    // if($recording_access['access'] || $recording_access['expiry_date'] <> '' || $slides <> '' || $resources <> '' || $certificate <> '' || $resource_files_html <> ''){
    $html .= '<div class="row">';
    $user_info = wp_get_current_user();

    // recording btn
    if($recording_access['access'] || $recording_access['expiry_date'] <> ''){
        $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
        $expiry_msg = '';
        if($recording_access['expiry_date'] <> ''){
            if($recording_access['access']){
                $expiry_msg = 'Available until '.$recording_access['expiry_date'];
            }else{
                $expiry_msg = 'Access expired '.$recording_access['expiry_date'];
            }
        }
        $url = '';
        $btn_class = '';
        if($recording_access['access']){
            $url = add_query_arg( 'id', $recording, '/watch-recording' );
        }else{
            $btn_class = 'disabled';
        }
        if($expiry_msg == ''){
            $html .= '<a class="btn btn-training btn-sm mb-3 w-100 recording-btn '.$btn_class.'" href="'.$url.'"><i class="fa-solid fa-video fa-fw"></i> Recording</a>';
        }else{
            $html .= '<a class="btn btn-training btn-sm mb-0 w-100 recording-btn mb-0 '.$btn_class.'" href="'.$url.'"><i class="fa-solid fa-video fa-fw"></i> Recording</a>';
            $html .= '<p class="small recording-available mb-3">'.$expiry_msg.'</p>';
        }
        $html .= '</div>';
    }

    if($slides <> ''){
        $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
        $html .= '<a class="btn btn-training btn-sm mb-3 w-100 slides-btn" href="'.$slides.'" target="_blank"><i class="fa-regular fa-image fa-fw"></i> Slides</a>';
        $html .= '</div>';
    }
    if($resources <> ''){
        $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
        $html .= '<a class="btn btn-training btn-sm mb-3 w-100 resources-btn" href="'.$resources.'" target="_blank"><i class="fa-solid fa-folder-open fa-fw"></i> Resources</a>';
        $html .= '</div>';
    }

    /*
    if($certificate <> ''){
        $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
        if(substr($certificate, 0, 8) == 'https://' || substr($certificate, 0, 7) == 'http://'){
            $cert_url = $certificate;
        }else{
            $cert_parms = ccpdf_workshop_cert_parms_encode($workshop_id, $user_info->ID);
            $cert_url = add_query_arg(array('c' => $cert_parms), site_url('/certificate/'));
        }
        $rec_cert_url = '';
        if($recording_certificate <> ''){
            if(substr($recording_certificate, 0, 8) == 'https://' || substr($recording_certificate, 0, 7) == 'http://'){
                $rec_cert_url = $recording_certificate;
            }else{
                $cert_parms = ccpdf_recording_cert_parms_encode($recording, $user_info->ID);
                $rec_cert_url = add_query_arg(array('c' => $cert_parms), site_url('/certificate/'));
            }
        }
        if($cert_url <> ''){
            if($rec_cert_url <> ''){
                $html .= '<div class="dropdown">';
                $html .= '<a class="btn btn-training btn-sm mb-3 w-100 certificate-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-certificate fa-fw"></i> Certificate</a>';
                $html .= '<ul class="dropdown-menu">';
                $html .= '<li><a class="dropdown-item" href="'.$cert_url.'">Live training cert.</a></li>';
                $html .= '<li><a class="dropdown-item" href="'.$rec_cert_url.'">Recording cert.</a></li>';
                $html .= '</ul>';
                $html .= '</div>';
            }else{
                $html .= '<a class="btn btn-training btn-sm mb-3 w-100 certificate-btn" href="'.$cert_url.'" target="_blank"><i class="fa-solid fa-certificate fa-fw"></i> Certificate</a>';
            }
        }
        $html .= '</div>';
    }

    // CE Credits
    $workshop_start_date = get_post_meta($workshop_id, 'meta_a', true); // d/m/y
    if($workshop_start_date <> ''){
        list($start_dd, $start_mm, $start_yyyy) = explode('/', $workshop_start_date);
        if($start_yyyy > 2023 || ($start_yyyy == 2023 && $start_mm >= 5)){
            $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
            $html .= '<a class="btn btn-cecredits btn-training btn-sm mb-3 w-100 cecredits-btn" href="#" data-bs-toggle="modal" data-bs-target="#cecredits-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'"><i class="fa-solid fa-award fa-fw"></i> CE credits</a>';
            $html .= '</div>';
        }
    }
    */

    // new certificate button ......
    if( cc_certs_training( $workshop_id, $event_id ) ){
        $html .= '<div class="col-12 col-md-6 col-xl-5 col-xxl-4">';
        $html .= '<a class="btn btn-cecredits btn-training btn-sm mb-3 w-100" href="#" data-bs-toggle="modal" data-bs-target="#cecredits-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'"><i class="fa-solid fa-award fa-fw"></i> Certificate</a>';
        $html .= '</div>';
    }

    $html .= '</div>';
    if($resource_files_html <> ''){
        $html .= '<h6 class="mb-0">Resources:</h6>';
        $html .= $resource_files_html;
    }
    // }
    return $html;
}

// returns the participation html for a workshop event
function cc_myacct_workshops_participation_html($workshop_id, $event_id=0){
    if($event_id > 0){
        $meta_key = 'event_'.$event_id;
    }else{
        $meta_key = 'workshop';
    }
    $html = '';

    $feedback_questions = get_post_meta($workshop_id, '_feedback_questions', true);
    if($feedback_questions <> ''){
        $user_id = get_current_user_id();
        if( cc_feedback_submitted( $workshop_id, $event_id, $user_id ) ){
            $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo disabled">Give Feedback <i class="fa-solid fa-check"></i></button>';
            // $html .= '<span class="feedback-ok mb-3 w-100"><i class="fa-solid fa-check"></i> Feedback received!</span>';
        }else{
            $fb_url = add_query_arg(
                array(
                    'f' => cc_feedback_link_code($workshop_id, $event_id, $user_id),
                ),
                '/feedback',
            );
            // $html .= '<a class="btn btn-training btn-sm mb-3 w-100 feedback-btn" href="'.$fb_url.'" target="_blank"><i class="fa-solid fa-comments fa-fw"></i> Give feedback</a>';
            $html .= '<button type="button" class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" data-bs-toggle="modal" data-bs-target="#myacct-feedback-modal" data-trainingid="'.$workshop_id.'" data-eventid="'.$event_id.'">Give feedback</button>';
        }
    }else{
        $old_fb_url = get_post_meta($workshop_id, $meta_key.'_feedback', true);
        if( $old_fb_url <> '' ){
            $html .= '<a class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" href="'.$old_fb_url.'" target="_blank">Give feedback</a>';
        }
    }

    $linkedin = get_post_meta($workshop_id, $meta_key.'_linkedin', true);
    if($linkedin <> ''){
        $html .= '<a class="btn btn-myacct btn-sm mb-1 w-100-xso w-49-smo" href="'.$linkedin.'" target="_blank"><i class="fa-brands fa-linkedin fa-fw"></i></a>';
    }

    return $html;
}

// returns the attendance html for a workshop (or event)
function cc_myacct_workshops_attendance_html($workshop_id, $event_id=0, $multi_event=false){
    // Check contract status FIRST for portal users
    $user_id = get_current_user_id();
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    $show_expired_message = false;
    $contract_type = '';
    
    if (!empty($portal_user) && function_exists('org_register_status') && function_exists('get_organisation_contract')) {
        $org_status = org_register_status($portal_user, $workshop_id);
        if ($org_status['status'] == 'expired') {
            $contract = get_organisation_contract($portal_user);
            $contract_type = !empty($contract->contract_type) ? $contract->contract_type : 'unlimited';
            
            // For unlimited contracts, block access to live sessions when contract expired
            if ($contract_type == 'unlimited') {
                $show_expired_message = true;
            }
            // For fixed_number contracts, they can still attend sessions they registered for
        }
    }
    
    // If unlimited contract is expired, show message and return early
    if ($show_expired_message) {
        $html = '<div class="alert alert-warning mb-3 py-2 px-3">';
        $html .= '<p class="mb-0 small"><i class="fa-solid fa-exclamation-triangle me-2"></i><strong>Access unavailable</strong><br>';
        $html .= 'Your organisation\'s contract has expired. You cannot access this training. Please contact your administrator about renewal.</p>';
        $html .= '</div>';
        return $html;
    }
    
    if($event_id > 0){
        $zoom = get_post_meta($workshop_id, 'event_'.$event_id.'_zoom', true);
        $venue = get_post_meta($workshop_id, 'event_'.$event_id.'_venue', true);
        $venue_name = get_post_meta($workshop_id, 'event_'.$event_id.'_venue_name', true);
    }else{
        $zoom = '';
        if($multi_event){
            $venue = $venue_name = '';
        }else{
            $venue = get_post_meta($workshop_id, 'event_1_venue', true);
            $venue_name = get_post_meta($workshop_id, 'event_1_venue_name', true);
        }
    }
    $workshop_zoom = get_post_meta($workshop_id, 'workshop_zoom', true);
    $html = '';
    $showit = false;
    if($event_id > 0){
        if($workshop_zoom == '' || $zoom <> '' || $venue <> '' || $venue_name <> ''){
            $showit = true;
        }
    }else{
        if($workshop_zoom <> ''){
            $showit = true;
        }
    }
    if($showit){
        $html .= '<div class="row">';
        if($venue_name <> '' || $venue <> ''){
            $html .= '<div class="col-3 col-md-2">Venue:</div>';
            $html .= '<div class="col-9 col-md-10">';
            if($venue_name <> ''){
                $html .= '<div>'.$venue_name.'</div>';
            }
            if($venue <> ''){
                $html .= '<div><a href="'.$venue.'" target="_blank">'.$venue.'</a></div>';
            }
            $html .= '</div>';
        }else{
            if($event_id > 0 && ($zoom <> '' || $workshop_zoom == '')){
                $html .= '<div class="col-12 mb-3">';
                $html .= '<label class="form-label"><i class="fa-solid fa-video fa-fw"></i> Zoom link:</label>';
                if($zoom <> ''){
                    // we need trackable links ...
                    $zoom_link = esc_url( add_query_arg( array('code' => cc_myacct_workshop_zoom_link_code($workshop_id, $event_id)), site_url('/zoom/') ) );
                    $html .= '<div class="row g-2 align-items-center">';
                    // Zoom Link (Disabled Input)
                    $html .= '<div class="col-12 col-md">';
                    $html .= '<input type="text" id="event-'.$workshop_id.'-'.$event_id.'-zoom" class="form-control" value="'.$zoom_link.'" disabled>';
                    $html .= '</div>';
                    // Buttons Wrapper
                    $html .= '<div class="col-12 col-md-auto">';
                    $html .= '<div class="d-flex gap-2 flex-wrap">';
                    // Copy Button
                    $html .= '<button type="button" class="btn btn-myacct btn-sm flex-grow-0 copy-link" data-link="event-'.$workshop_id.'-'.$event_id.'-zoom">Copy link</button>';
                    // Join Button (Wider)
                    $html .= '<a href="'.$zoom_link.'" class="btn btn-myacct btn-sm flex-grow-1" target="_blank">Join training now</a>';
                    $html .= '</div></div></div>';
                }else{
                    $html .= '<div>To be supplied closer to the event</div>';
                }
                $html .= '</div>';
            }elseif($event_id == 0 && $workshop_zoom <> ''){
                $html .= '<div class="col-12 mb-3">';
                $html .= '<label class="form-label"><i class="fa-solid fa-video fa-fw"></i> Zoom link:</label>';
                if($workshop_zoom <> ''){
                    $zoom_link = esc_url( add_query_arg( array('code' => cc_myacct_workshop_zoom_link_code($workshop_id, 0)), site_url('/zoom/') ) );
                    $html .= '<div class="row g-2 align-items-center">';
                    // Zoom Link (Disabled Input)
                    $html .= '<div class="col-12 col-md">';
                    $html .= '<input type="text" id="workshop-'.$workshop_id.'-zoom" class="form-control" value="'.$zoom_link.'" disabled>';
                    $html .= '</div>';
                    // Buttons Wrapper
                    $html .= '<div class="col-12 col-md-auto">';
                    $html .= '<div class="d-flex gap-2 flex-wrap">';
                    // Copy Button
                    $html .= '<button type="button" class="btn btn-myacct btn-sm flex-grow-0 copy-link" data-link="workshop-'.$workshop_id.'-zoom">Copy link</button>';
                    // Join Button (Wider)
                    $html .= '<a href="'.$zoom_link.'" class="btn btn-myacct btn-sm flex-grow-1" target="_blank">Join training now</a>';
                    $html .= '</div></div></div>';
                }else{
                    $html .= '<div>To be supplied closer to the workshop</div>';
                }
                $html .= '</div>';
            }
        }
        $html .= '</div>';
    }
    return $html;
}

// creates an obfuscated code for the zoom link
function cc_myacct_workshop_zoom_link_code($workshop_id, $event_id=0){
    $daft_number = $workshop_id * $workshop_id + $event_id * $event_id + 67345;
    $string = $workshop_id.'|'.$event_id.'|'.get_current_user_id().'|'.$daft_number;
    return base64_encode($string);
}

// the new user workshop table
// note: reg_attend = 'r' or 'a', 'r' = registrant, 'a' = attendee. Note a registrant may also be (and usually is) an attendee too
// v2 added status
add_action('after_setup_theme', 'cc_myacct_worshops_users_table_update');
function cc_myacct_worshops_users_table_update(){
    global $wpdb;
    $wkshops_users_db_ver = 2;
    $installed_table_ver = get_option('wkshops_users_db_ver');
    if($installed_table_ver <> $wkshops_users_db_ver){
        $table_name = $wpdb->prefix.'wshops_users';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            workshop_id mediumint(9) NOT NULL,
            payment_id mediumint(9) NOT NULL,
            reg_attend char(1) NOT NULL,
            workshop_start date NOT NULL,
            status varchar(10) NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $response = dbDelta($sql);
        update_option('wkshops_users_db_ver', $wkshops_users_db_ver);
    }
}

// the new user recording table
add_action('after_setup_theme', 'cc_myacct_recordings_users_table_update');
function cc_myacct_recordings_users_table_update(){
    global $wpdb;
    $recordings_users_db_ver = 1;
    $installed_table_ver = get_option('recordings_users_db_ver');
    if($installed_table_ver <> $recordings_users_db_ver){
        $table_name = $wpdb->prefix.'recordings_users';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            recording_id mediumint(9) NOT NULL,
            payment_id mediumint(9) NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $response = dbDelta($sql);
        update_option('recordings_users_db_ver', $recordings_users_db_ver);
    }
}

// sets up users as well as rows in the workshop users table and the recordings users table
// add_shortcode('populate_workshop_users_table', 'populate_workshop_users_table');
function populate_workshop_users_table(){
    $html = '<br>'.date('d/m/Y H:i:s').' populate_workshop_users_table users';
    ccpa_write_log('populate_workshop_users_table users');
    global $wpdb;
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    /*
    $sql = "SELECT * FROM $payments_table WHERE last_update > '2023-01-01 00:00:00' AND last_update < '2024-01-01 00:00:00' ORDER BY id";
    $html .= '<br>'.$sql;
    ccpa_write_log($sql);
    $payments = $wpdb->get_results($sql, ARRAY_A);
    foreach ($payments as $payment) {
        // $html .= '<br>Payment_id: '.$payment['id'].' reg_userid: '.$payment['reg_userid'].' workshop_id: '.$payment['workshop_id'];
        // ccpa_write_log('Payment_id: '.$payment['id'].' reg_userid: '.$payment['reg_userid'].' workshop_id: '.$payment['workshop_id']);
        if($payment['status'] <> 'Cancelled'){
            cc_myacct_insert_workshops_recordings_users($payment);
        }
    }
    ccpa_write_log('users Payments processed');
    $html .= '<br>users payments processed';
    */
    // we also want to add in all the recordings that users have access to, maybe for free
    $now = date('Y-m-d H:i:s');
    $users = get_users();
    foreach ($users as $user){
        if( $user->ID >= 30000 ){
            $user_metas = get_user_meta($user->ID);
            foreach ($user_metas as $key => $value){
                // if(substr($key, 0, 13) == 'cc_rec_wshop_'){
                if( preg_match( '/^cc_rec_wshop_\d+$/', $key ) ){
                    $recording_id = substr($key, 13);
                    $recording_meta = maybe_unserialize($value[0]);
                    $recording_meta = sanitise_recording_meta( $recording_meta );
                    // does the user have viewing access ... changed as we no longer care. If they did have access then they can still view the resources etc
                    // if(isset($recording_meta['access_time']) && $recording_meta['access_time'] < $now){
                    //     if(!isset($recording_meta['closed_time']) || $recording_meta['closed_time'] == '' || $recording_meta['closed_time'] > $now){
                            // is it already in the table?
                            $sql = "SELECT * FROM $recordings_users_table WHERE user_id = ".$user->ID." AND recording_id = $recording_id LIMIT 1";
                            $result = $wpdb->get_row($sql, ARRAY_A); // Returns null if no result is found
                            if($result === NULL){
                                // not found, add it ....
                                $data = array(
                                    'user_id' => $user->ID,
                                    'recording_id' => $recording_id,
                                    'payment_id' => 0,
                                );
                                $wpdb->insert($recordings_users_table, $data, array('%d', '%d', '%d'));
                            }
                    //     }
                    // }
                }
            }
        }
    }
    $html .= '<br>'.date('d/m/Y H:i:s').' 30000+ users done';
    ccpa_write_log('users done');
    return $html;
}

// retrieve or create a user
// returns user_id
// ~~NOTE~~ now only of use for the registrant!
function cc_myacct_get_user($payment_data, $reg_attend='r'){
    if($reg_attend <> 'r') return 0;
    // ccpa_write_log('cc_myacct_get_user '.$payment_data['id'].' '.$reg_attend);
    // ccpa_write_log(wp_debug_backtrace_summary());
    $user_id = 0;
    if($payment_data['type'] == 'recording'){
        $recordings_user = cc_myacct_get_recordings_users_row($payment_data['id']);
        if($recordings_user !== NULL){
            $user_id = $recordings_user['user_id'];
        }
    }else{
        $workshops_user = cc_myacct_get_workshops_users_row($payment_data['id'], $reg_attend);
        if($workshops_user !== NULL){
            $user_id = $workshops_user['user_id'];
        }
    }
    if($user_id == 0){
        // ccpa_write_log('user not found in wkshp/rec users tables');
        if($reg_attend == 'r'){
            $user_data = get_user_by( 'email', $payment_data['email'] );
        }else{
            $user_data = get_user_by( 'email', $payment_data['attendee_email'] );
        }
        if(!$user_data){
            // need to create user
            // ccpa_write_log('creating user');
            if(isset($payment_data['id']) && $payment_data['id'] > 0){
                $differentiator = $payment_data['id'];
            }else{
                $differentiator = uniqid();
            }
            $user_login = $payment_data['firstname'].' '.$payment_data['lastname'].' '.$differentiator;
            // just check that the username is not already set up
            $user_data = get_user_by( 'login', $user_login );
            if(!$user_data){
                if($reg_attend == 'r'){
                    $args = array(
                        'user_login' => $payment_data['firstname'].' '.$payment_data['lastname'].' '.$differentiator,
                        'user_pass' => wp_generate_password(),
                        'user_email' => $payment_data['email'],
                        'first_name' => $payment_data['firstname'],
                        'last_name' => $payment_data['lastname'],
                        'role' => 'subscriber',
                    );
                }else{
                    if($payment_data['attendee_firstname'] <> '' || $payment_data['attendee_lastname'] <> ''){
                        $args = array(
                            'user_login' => $payment_data['attendee_firstname'].' '.$payment_data['attendee_lastname'].' '.$differentiator,
                            'user_pass' => wp_generate_password(),
                            'user_email' => $payment_data['attendee_email'],
                            'first_name' => $payment_data['attendee_firstname'],
                            'last_name' => $payment_data['attendee_lastname'],
                            'role' => 'subscriber',
                        );
                    }else{
                        $args = array(
                            'user_login' => $payment_data['attendee_email'].' '.$differentiator,
                            'user_pass' => wp_generate_password(),
                            'user_email' => $payment_data['attendee_email'],
                            'role' => 'subscriber',
                        );
                    }
                }
                $user_id = wp_insert_user( $args );
                if($payment_data['currency'] == 'GBP'){
                    $timezone = 'Europe/London'; // London
                }elseif($payment_data['currency'] == 'AUD'){
                    $timezone = 'Australia/Melbourne'; // Melbourne
                }elseif($payment_data['currency'] == 'USD'){
                    $timezone = 'America/New_York'; // New York
                }else{
                    $timezone = 'Europe/London';
                }
                update_user_meta($user_id, 'workshop_timezone', $timezone);
                update_user_meta($user_id, 'last_login', 'never');
            }
        }else{
            // user exists
            // ccpa_write_log('user exists');
            $user_id = $user_data->ID;
            $last_login = get_user_meta($user_id, 'last_login', true);
            // ccpa_write_log('User:'.$user_id.' last_login:'.print_r($last_login, true).'#');
            /*
            if($last_login == ''){
                ccpa_write_log('last login empty string');
            }
            if($last_login === false){
                ccpa_write_log('last login false');
            }
            */
            if($last_login == ''){
                // pre this update
                $first_jan = strtotime('2021-01-01 00:00:00');
                update_user_meta($user_id, 'last_login', $first_jan);
            }
        }
    }
    return $user_id;
}

// fix timezone
// this was shortlived as we cannot use these codes! :-(
add_shortcode('fix_london_timezone', 'rpm_fix_london_timezone');
function rpm_fix_london_timezone(){
    $users = get_users();
    foreach ($users as $user){
        $timezone = get_user_meta($user->ID, 'workshop_timezone', true);
        if($timezone == '340'){
            update_user_meta($user->ID, 'workshop_timezone', '341');
        }
    }
}

// inserts workshops users or recordings users table rows for a new payment
function cc_myacct_insert_workshops_recordings_users($payment_data){
    if( isset($payment_data['reg_userid']) && $payment_data['reg_userid'] > 0 ){
        $user_id = $payment_data['reg_userid'];
    }else{
        // can't think why we might be here ... surely the reg_userid will have always been set ...?
        // it is because it's missing on old payment rows! ... yes, pre about Jan 2023
        $user_id = cc_myacct_get_user($payment_data, 'r');
        $payment_data['reg_userid'] = $user_id;
    }

    // the primary training_id
    $course_type = course_training_type( $payment_data['workshop_id'] );
    if( $course_type == 'recording' ){
        cc_myacct_insert_recordings_users( $payment_data['id'], $user_id, $payment_data['workshop_id'] );
    }elseif( $course_type == 'workshop' ){
        cc_myacct_insert_workshops_users( $payment_data['id'], $user_id, $payment_data['workshop_id'], $payment_data['status'] );
    }

    // upsell?
    if( $payment_data['upsell_workshop_id'] <> '' && $payment_data['upsell_workshop_id'] <> 0 ){
        $course_type = course_training_type( $payment_data['upsell_workshop_id'] );
        if( $course_type == 'recording' ){
            cc_myacct_insert_recordings_users( $payment_data['id'], $user_id, $payment_data['upsell_workshop_id'] );
        }elseif( $course_type == 'workshop' ){
            cc_myacct_insert_workshops_users( $payment_data['id'], $user_id, $payment_data['upsell_workshop_id'], $payment_data['status'] );
        }
    }
}

// insert the recordings users rows for a payment
function cc_myacct_insert_recordings_users( $payment_id, $user_id, $training_id ){
    global $wpdb;
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    // collect all user ids ... the recordings users table does not care if people are registrants or attendees
    $rec_user_ids = array( $user_id );
    $attendees = cc_attendees_for_payment( $payment_id );
    foreach ($attendees as $attendee) {
        if($attendee['registrant'] <> 'r'){
            $rec_user_ids[] = $attendee['user_id'];
        }
    }
    $count_attendees = 0;
    foreach ($rec_user_ids as $user_id) {
        // create the recording user row
        // is it already in the table?
        $sql = "SELECT * FROM $recordings_users_table WHERE user_id = $user_id AND recording_id = $training_id AND payment_id = $payment_id LIMIT 1";
        $result = $wpdb->get_row($sql, ARRAY_A);
        if(!$result){
            // not found, add it ....
            $data = array(
                'user_id' => $user_id,
                'recording_id' => $training_id,
                'payment_id' => $payment_id,
            );
            $wpdb->insert($recordings_users_table, $data, array('%d', '%d', '%d'));
            $count_attendees ++;
        }
    }
    return $count_attendees;
}

// inserts all the rows needed for a payment: payee, attendee, main workshop, upsell, ...
// this assumes that payment data is not already in the table ... you might want to check that before using this function
function cc_myacct_insert_workshops_users( $payment_id, $user_id, $training_id, $status ){
    $workshop_start = '0000-00-00';
    if( $training_id > 0){
        $workshop_start_timestamp = (int) get_post_meta( $training_id, 'workshop_start_timestamp', true );
        if( $workshop_start_timestamp > 0 ){
            $workshop_start = date( 'Y-m-d', $workshop_start_timestamp );
        }
    }
    $status = ( $status == 'Cancelled' ) ? 'cancelled' : '';
    $data = array(
        'user_id' => $user_id,
        'workshop_id' => $training_id,
        'payment_id' => $payment_id,
        'reg_attend' => 'r',
        'workshop_start' => $workshop_start,
        'status' => $status,
    );
    my_acct_workshop_users_insert( $data );
    $count_rows = 1;
    // attendees
    $attendees = cc_attendees_for_payment( $payment_id );
    foreach ($attendees as $attendee) {
        if($attendee['registrant'] <> 'r'){
            // the registrant may also be an attendee but we will not add them to this table twice as the workshop users table just tells us which trainings to show for a user.
            // The attendee table tells us what details to show for that training
            $data = array(
                'user_id' => $attendee['user_id'],
                'workshop_id' => $training_id,
                'payment_id' => $payment_id,
                'reg_attend' => 'a',
                'workshop_start' => $workshop_start,
                'status' => $status,
            );
            my_acct_workshop_users_insert( $data );
            $count_rows ++;
        }
    }
    return $count_rows;
}

// insert into the workshop_users table
// $row should be an array('user_id', 'workshop_id', 'payment_id', 'reg_attend', 'workshop_start', 'status')
function my_acct_workshop_users_insert( $row ){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $wpdb->insert($wkshop_users_table, $row, array('%d', '%d', '%d', '%s', '%s', '%s'));
}

// update the start date in the workshops users table
function my_acct_workshop_update_start($workshop_id, $workshop_start_timestamp){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $workshop_start = date('Y-m-d', $workshop_start_timestamp );
    $data = array(
        'workshop_start' => $workshop_start
    );
    $where = array(
        'workshop_id' => $workshop_id
    );
    return $wpdb->update( $wkshop_users_table, $data, $where );
}

// cancel a workshop users row
function my_acct_cancel_workshop_users_row($id){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $data = array(
        'status' => 'cancelled'
    );
    $where = array(
        'id' => $id
    );
    return $wpdb->update( $wkshop_users_table, $data, $where );
}

// resend the registration email
// NOTE: also used for recordings
add_action('wp_ajax_resend_reg_email', 'resend_reg_email');
add_action('wp_ajax_nopriv_resend_reg_email', 'resend_reg_email');
function resend_reg_email(){
    $response = array(
        'class' => 'error',
        'msg' => '',
    );
    $payment_id = 0;
    if(isset($_POST['paymentID'])){
        $payment_id = absint($_POST['paymentID']);
    }
    if($payment_id == 0){
        $response['msg'] = '<i class="fa fa-times"></i> Payment not found - please contact us for assistance';
    }else{
        if( cc_mailsterint_send_reg_emails( $payment_id, true ) ){
            $response['msg'] = '<i class="fa fa-check"></i> Registration email successfully sent. You should receive it shortly.';
            $response['class'] = 'success';
        }else{
            $response['msg'] = '<i class="fa fa-times"></i> Error sending email. Please try again or contact us for assistance.';
        }
    }
    echo json_encode($response);
    die();
}

// retrieve all workshop user rows for a given user
// $what can be 'past', 'future' or '' (all)
// workshops will be ordered: future workshops date ascending and past workshops date descending
// note that the datetime for whether it is past or future is the workshop end time (where we know it)
// excludes non-blank status rows if $inc_cancelled = false
// $user_type can be '' = all, 'r' = registrants only 'a' = attendees only
// The workshops users table reg_attend field is set to 'r' (registrant and maybe also attendee) or 'a' (attendee only)
function cc_myacct_get_workshops($user_id, $what='', $inc_cancelled=false, $user_type=''){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $attendees_table = $wpdb->prefix.'cc_attendees';
    if( $user_type == '' ){
        // include registrants and attendees
        if($inc_cancelled){
            $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id <> 0";
        }else{
            $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id <> 0 AND status = ''";
        }
    }elseif( $user_type == 'r' ){
        // registrants only
        if($inc_cancelled){
            $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id <> 0 AND reg_attend = 'r'";
        }else{
            $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id <> 0 AND reg_attend = 'r' AND status = ''";
        }        
    }else{
        // must be attendees only
        // have to also use the attendees table for this
        if( $inc_cancelled ){
            $sql = "
                SELECT *
                FROM $wkshop_users_table wu
                LEFT JOIN $attendees_table a 
                    ON wu.user_id = a.user_id 
                    AND wu.payment_id = a.payment_id
                WHERE 
                    wu.user_id = $user_id
                    AND wu.workshop_id <> 0
                    AND ( wu.reg_attend = 'a' OR a.id IS NOT NULL )
                GROUP BY wu.workshop_id";
        }else{
            $sql = "
                SELECT *
                FROM $wkshop_users_table wu
                LEFT JOIN $attendees_table a 
                    ON wu.user_id = a.user_id 
                    AND wu.payment_id = a.payment_id
                WHERE 
                    wu.user_id = $user_id
                    AND wu.workshop_id <> 0
                    AND wu.status = ''
                    AND ( wu.reg_attend = 'a' OR a.id IS NOT NULL )
                GROUP BY wu.workshop_id";
        }

    }
    $workshop_users = $wpdb->get_results($sql, ARRAY_A);

    $upcoming_workshops = $past_workshops = array();
    $now = time();
    foreach ($workshop_users as $workshop_user) {
        $workshop_timestamp = get_post_meta($workshop_user['workshop_id'], 'workshop_timestamp', true);
        if($workshop_timestamp == ''){
            $workshop_timestamp = get_post_meta($workshop_user['workshop_id'], 'workshop_start_timestamp', true);
        }
        if( $workshop_timestamp > $now ){
            $upcoming_workshops[$workshop_timestamp] = $workshop_user;
        }else{
            $past_workshops[$workshop_timestamp] = $workshop_user;
        }
    }

    ksort( $upcoming_workshops );
    krsort( $past_workshops );

    if( $what == '' ){
        return array_merge( $upcoming_workshops, $past_workshops );
    }elseif( $what == 'future' ){
        return $upcoming_workshops;
    }else{
        return $past_workshops;
    }
}

// retrive a workshops users row for a given payment id
// Returns null if no result is found
function cc_myacct_get_workshops_users_row($payment_id, $reg_attend='r'){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $sql = "SELECT * FROM $wkshop_users_table WHERE payment_id = $payment_id AND reg_attend = '$reg_attend' LIMIT 1";
    return $wpdb->get_row($sql, ARRAY_A);
}

// retrieves a workshops users row for a given user/workshop
// returns NULL if not found
function cc_myacct_get_workshops_users_by_user_workshop($user_id, $workshop_id){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id = $workshop_id LIMIT 1";
    return $wpdb->get_row($sql, ARRAY_A);
}

// retrive a recordings users row for a given payment id
// Returns null if no result is found
function cc_myacct_get_recordings_users_row($payment_id){
    global $wpdb;
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    $sql = "SELECT * FROM $recordings_users_table WHERE payment_id = $payment_id LIMIT 1";
    return $wpdb->get_row($sql, ARRAY_A);
}

// retrieves a recordings users row for a given user/recording
// returns NULL if not found
function cc_myacct_get_recordings_users_by_user_recording($user_id, $recording_id){
    global $wpdb;
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    $sql = "SELECT * FROM $recordings_users_table WHERE user_id = $user_id AND recording_id = $recording_id LIMIT 1";
    return $wpdb->get_row($sql, ARRAY_A);
}

// get all recordings users row for a user
// returns empty arry if nothing found
function cc_myacct_get_recordings_ids_by_user($user_id){
    global $wpdb;
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    $sql = "SELECT recording_id FROM $recordings_users_table WHERE user_id = $user_id";
    return $wpdb->get_col($sql);
}


// update the workshop_start on all workshops users table rows
/* duplicate of my_acct_workshop_update_start!
function cc_myacct_update_workshop_start($workshop_id, $workshop_start_timestamp){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $data = array(
        'workshop_start' => date('Y-m-d', $workshop_start_timestamp)
    );
    $where = array(
        'workshop_id' => $workshop_id
    );
    return $wpdb->update($wkshop_users_table, $data, $where);
}
*/

// delete all rows from the recordings users table for a payment id
function cc_myacct_recordings_users_delete_payment($payment_id){
    global $wpdb;
    $recordings_users_table = $wpdb->prefix.'recordings_users';
    $where = array(
        'payment_id' => $payment_id
    );
    return $wpdb->delete( $recordings_users_table, $where );
}

// delete from the workshops users table for a given payment id
// It returns the number of rows updated, or false on error.
function cc_myacct_workshops_users_delete_payment($payment_id){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $where = array(
        'payment_id' => $payment_id
    );
    return $wpdb->delete( $wkshop_users_table, $where );
}

// remove the invalid entries from the workshops users table
add_shortcode('fix_workshops_users_nil_payments', 'cc_myacct_fix_workshops_users_nil_payments');
function cc_myacct_fix_workshops_users_nil_payments(){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $where = array(
        'workshop_id' => 0
    );
    $result = $wpdb->delete( $wkshop_users_table, $where );
    return 'result = '.print_r($result, true);
}

// should we allow this person to cancel their workshop registration?
// returns bool
function cc_myacct_workshop_offer_cancellation($workshop_id, $user_id=NULL){
    // to be able to cancel they must be a CNWL user
    if($user_id === NULL){
        $user_id = get_current_user_id();
    }
    $portal_user = get_user_meta( $user_id, 'portal_user', true);
    if($portal_user <> 'cnwl') return false;
    // and the workshop must not have started yet
    $workshop_start_timestamp = get_post_meta($workshop_id, 'workshop_start_timestamp', true);
    if($workshop_start_timestamp < time()) return false;
    return true;
}

// get registration and attendance info for this user/workshop
// might have registered (multiple times) might be an attendee (multiple times)
function cc_myacct_all_payments_for_user( $user_id, $workshop_id ){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $attendees_table = $wpdb->prefix.'cc_attendees';

    // has this user registered for this workshop
    // and, if so, who are the attendees that the user has registered
    $results = array();

    // let's get all the payments that the user has registered with first
    // we get these from the workshops users table
    $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id = $workshop_id AND reg_attend = 'r' AND payment_id <> 0 AND status = '' ORDER BY payment_id";
    $regs = $wpdb->get_results($sql, ARRAY_A);

    // for each of these registrations, let's get the attendees
    foreach ($regs as $reg) {
        $payment_id = $reg['payment_id'];
        $sql = "SELECT * FROM $attendees_table WHERE payment_id = $payment_id";
        $results['r'][$payment_id] = $wpdb->get_results( $sql, ARRAY_A ); // always returns an array
    }
    
    // now let's look to see if the user is an attendee for the workshop
    // of course we might have found that already if they registered for themselves to attend
    // but anyway, ...
    // this sql will only pick up the user if somebody else registered them
    $sql = "SELECT * FROM $wkshop_users_table WHERE user_id = $user_id AND workshop_id = $workshop_id AND reg_attend = 'a' AND payment_id <> 0 AND status = '' ORDER BY payment_id";
    $attendees = $wpdb->get_results($sql, ARRAY_A);

    // let's find out who registered this user as an attendee
    // in an ideal world, somebody would only attend a workshop once but, just in case ...
    foreach ($attendees as $attendee) {
        $payment_id = $attendee['payment_id'];
        $sql = "SELECT * FROM $wkshop_users_table WHERE payment_id = $payment_id AND reg_attend = 'r' AND status = ''";
        $results['a'][$payment_id] = $wpdb->get_results( $sql, ARRAY_A ); // always returns an array
    }

    return $results;
}

// should we show the training details for this workshop?
function cc_myacct_show_training_dets( $workshop_id ){
    if( get_post_status( $workshop_id ) == 'publish' && get_post_meta( $workshop_id, '_links_to', true ) == '' ){
        return true;
    }
    return false;
}

// load the training details modal on the My Account pages
add_action('wp_ajax_myacct_training_dets_modal_get', 'myacct_training_dets_modal_get');
function myacct_training_dets_modal_get(){
    global $rpm_theme_options;
    $response = array(
        'status' => 'error',
        'title' => '',
        'body' => '',
    );
    $body = '';
    $training_id = absint( $_POST['trainingID'] );
    if($training_id > 0){
        $training_type = course_training_type($training_id);
        if($training_type == 'workshop' || $training_type == 'recording'){
            $response['status'] = 'ok';
            $response['title'] = get_the_title( $training_id );

            $response['body'] = '<div class="container-fluid"><div class="row bullet-row"><div class="col-8 offset-2 offset-lg-0 col-lg-4 image-col">'.cc_presenters_images( $training_id ).'</div><div class="col-12 col-md-8 offset-md-2 col-lg-8 offset-lg-0 bullet-col"><div class="bullet-wrapper">';
            $who = cc_presenters_names( $training_id, 'none' );
            if($who <> ''){
                $response['body'] .= '<div class="bullet"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
            }

            if( $training_type == 'recording' ){ // course

                $course = course_get_all( $training_id );
                if( $course['recording_expiry']['num'] > 0 && $course['recording_expiry']['unit'] <> '' ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-video fa-fw"></i>Access is for '.$course['recording_expiry']['num'].' '.$course['recording_expiry']['unit'].' after purchase</div>';
                }

                if( ! empty( course_single_meta( $course, '_course_timing' ) ) ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.course_single_meta( $course, '_course_timing' ).'</div>';
                }

                $ce_credits = (float) course_single_meta( $course, 'ce_credits' );
                if( $ce_credits > 0 ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-graduation-cap"></i>'.$ce_credits.' CE credits</div>';
                }

                $training_levels = cc_topics_training_levels( $training_id );
                if( $training_levels <> '' ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-star fa-fw"></i>'.$training_levels.'</div>';
                }
                if( ! empty( course_single_meta( $course, 'who_for' ) ) ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-award fa-fw"></i>'.do_shortcode( course_single_meta( $course, 'who_for' ) ).'</div>';
                }

            }else{

                $where = get_post_meta( $training_id , 'event_1_venue_name', true);
                if($where <> ''){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-location-dot fa-fw"></i>In person: ';
                    $where_link = get_post_meta(  $training_id , 'meta_c', true );
                    if( $where_link <> '' ){
                        $response['body'] .= '<a href="'.esc_url($where_link).'" target="_blank">';
                    }
                    $response['body'] .= $where;
                    if( $where_link <> '' ){
                        $response['body'] .= '</a>';
                    }
                    $response['body'] .= '</div>';
                }
                $text_dates = get_post_meta(  $training_id , 'prettydates', true );
                if($text_dates <> ''){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$text_dates.'</div>';
                }
                $ce_credits = (float) get_post_meta(  $training_id , 'ce_credits', true );
                if( $ce_credits > 0 ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-graduation-cap"></i>'.$ce_credits.' CE credits</div>';
                }
                $training_levels = cc_topics_training_levels(  $training_id  );
                if( $training_levels <> '' ){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-star fa-fw"></i>'.$training_levels.'</div>';
                    // $response['body'] .= '<div class="bullet"><i class="fa-solid fa-star fa-fw"></i><a href="#" data-bs-toggle="tooltip" data-bs-title="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque sodales suscipit ligula eu laoreet. Donec tristique, erat sed accumsan pulvinar">'.$training_levels.'</a></div>';
                }
                $rec_avail = get_post_meta( $training_id , 'rec_avail', true);
                if($rec_avail <> ''){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-video fa-fw"></i>'.$rec_avail.'</div>';
                }
                $who_for = get_post_meta( $training_id , 'who_for', true);
                if($who_for <> ''){
                    $response['body'] .= '<div class="bullet"><i class="fa-solid fa-award fa-fw"></i>'.do_shortcode($who_for).'</div>';
                }

            }

            $response['body'] .= '</div></div>';

            $content = apply_filters( 'the_content', get_the_content( null, false, $training_id ) );
            $response['body'] .= '<div class="row description-row mt-3"><div class="col-12 content-wrapper">'.$content.'</div></div></div>';
        }
    }
    echo json_encode($response);
    die();
}

// load the training modal on the My Account pages
add_action('wp_ajax_myacct_training_modal_get', 'myacct_training_modal_get');
function myacct_training_modal_get(){
    global $rpm_theme_options;
    $response = array(
        'status' => 'error',
        'title' => '',
        'body' => '',
    );
    $body = '';
    $recording_id = absint( $_POST['trainingID'] );
    if($recording_id > 0){
        $training_type = get_post_type($recording_id);
        if($training_type == 'workshop' || $training_type == 'recording'){
            $response['status'] = 'ok';
            $response['title'] = get_the_title( $recording_id );

            $current_user = wp_get_current_user();

            $vimeo_id = get_post_meta( $recording_id, 'vimeo_id', true );
            // $recording_meta = get_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, true);
            $recording_meta = get_recording_meta( $current_user->ID, $recording_id );
            $recording_url = get_post_meta($recording_id, 'recording_url', true);
            $accordions = false;
            for ($i=0; $i < 10; $i++) { 
                $module_name = get_post_meta($recording_id, 'module_name_'.$i, true);
                if($module_name <> ''){
                    $accordions = true;
                    $response['body'] .= '<div class="accordion accordion-flush training-accordion" id="training-modules">';
                    break;
                }
            }

            $collapsed = false;
            $mod_num = 0;

            $num_views = 0;
            if(isset($recording_meta['num_views'])){
                $num_views = $recording_meta['num_views'];
            }
            $viewed_end = 'no';
            if(isset($recording_meta['viewed_end'])){
                $viewed_end = $recording_meta['viewed_end'];
            }
            $viewing_time = 0;
            if(isset($recording_meta['viewing_time'])){
                $viewing_time = $recording_meta['viewing_time'];
            }

            // start with the main video if there is one
            if($vimeo_id <> ''){
                $mod_title = get_the_title($recording_id);
                $chat_module = 9999;

                $response['body'] .= cc_recordings_module_html($accordions, $collapsed, $mod_num, $mod_title, $recording_id, $num_views, $viewed_end, $viewing_time, $vimeo_id, $chat_module);

                /* no longer showing resources here
                // $recording_files = resources_show_list($recording_id);
                if( resources_exist( $recording_id ) ){
                    // $response['body'] .= '<button type="button" class="btn btn-myacct btn-sm my-3" data-bs-toggle="modal" data-bs-target="#training-resources-modal" data-trainingid="'.$recording_id.'" data-eventid="'.$mod_num.'">Training resources</button>';
                    $response['body'] .= '<button type="button" class="btn btn-myacct btn-sm my-3 toggle-resources">Training resources</button>';
                    $response['body'] .= '<div class="mt-3 p-3 border rounded bg-light text-body training-resources-panel" style="display: none;">';
                    $response['body'] .= resources_show_list( $recording_id, $mod_num, true, true );
                    $response['body'] .= '</div>';
                }
                */

                if($accordions){
                    // done this way to include the files in the accordion
                    $response['body'] .= '</div></div></div>';
                }else{
                    $response['body'] .= '</div></div>';
                }

                $collapsed = true;
                $mod_num ++;
            }

            // now do the modules (if there are any)
            for ($i=0; $i < 10; $i++) { 
                $module_name = get_post_meta($recording_id, 'module_name_'.$i, true);
                $module_vimeo = get_post_meta($recording_id, 'module_vimeo_'.$i, true);
                if($module_name <> '' && $module_vimeo <> ''){
                    $num_views = 0;
                    if(isset($recording_meta['modules'][$i]['num_views'])){
                        $num_views = $recording_meta['modules'][$i]['num_views'];
                    }
                    $viewed_end = 'no';
                    if(isset($recording_meta['modules'][$i]['viewed_end'])){
                        $viewed_end = $recording_meta['modules'][$i]['viewed_end'];
                    }
                    $viewing_time = 0;
                    if(isset($recording_meta['modules'][$i]['viewing_time'])){
                        $viewing_time = $recording_meta['modules'][$i]['viewing_time'];
                    }

                    // if there is a main vid, mod_num will start at 1, not 0
                    $response['body'] .= cc_recordings_module_html($accordions, $collapsed, $mod_num, $module_name, $recording_id.'-'.$i, $num_views, $viewed_end, $viewing_time, $module_vimeo, $i);

                    /* no longer showing resources here
                    // $recording_files = resources_show_list($recording_id, $i);
                    if( resources_exist( $recording_id, $i ) ){
                        // $response['body'] .= '<button type="button" class="btn btn-myacct btn-sm my-3" data-bs-toggle="modal" data-bs-target="#training-resources-modal" data-trainingid="'.$recording_id.'" data-eventid="'.$i.'">Training resources</button>';
                        $response['body'] .= '<button type="button" class="btn btn-myacct btn-sm my-3 toggle-resources">Training resources</button>';
                        $response['body'] .= '<div class="mt-3 p-3 border rounded bg-light text-body training-resources-panel" style="display: none;">';
                        $response['body'] .= resources_show_list( $recording_id, $i, true, true );
                        $response['body'] .= '</div>';
                    }
                    */

                    if($accordions){
                        // done this way to include the files in the accordion
                        $response['body'] .= '</div></div></div>';
                    }

                    $collapsed = true;
                    $mod_num ++;
                }
            }

            if($accordions){
                $response['body'] .= '</div>';
            }

            // legacy stuff??????
            if($mod_num == 0 && $recording_url <> ''){
                $response['body'] .= '<div class="flex-video" style="padding-bottom:56.25%"><!--[if lt IE 9]><script>document.createElement("video");</script><![endif]--><video id="rec-video" playsinline controls class="wp-video-shortcode rec-video" data-module="0" width="1920" height="1080" preload="metadata" data-controls="controls" data-source="cc" data-recid="'.$recording_id.'" data-lastviewed="'.date('d/m/Y H:i:s').'" data-numviews="'.$num_views.'" data-viewedend="'.$viewed_end.'" data-viewingtime="'.$viewing_time.'"><source type="video/mp4" src="'.$recording_url.'" /><a href="'.$recording_url.'">'.$recording_url.'</a></video></div>';
            }
        }elseif( $training_type == 'course' ){
            $response['status'] = 'ok';
            $response['title'] = get_the_title( $recording_id );

            $current_user = wp_get_current_user();

            $course = course_get_all( $recording_id );

            $accordions = false;
            $collapsed = false;
            if( $course['module_counts']['module_count'] > 1 ){
                $accordions = true;
                $response['body'] .= '<div class="accordion accordion-flush training-accordion" id="training-modules">';
            }

            // $recording_meta = get_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, true);
            $recording_meta = get_recording_meta( $current_user->ID, $recording_id );
            $num_views = isset( $recording_meta['num_views'] ) ? $recording_meta['num_views'] : 0;
            $viewed_end = isset( $recording_meta['viewed_end'] ) ? $recording_meta['viewed_end'] : 'no';
            $viewing_time = isset( $recording_meta['viewing_time'] ) ? $recording_meta['viewing_time'] : 0;

            foreach ( $course['modules'] as $index => $module ){

                if($accordions){
                    $response['body'] .= '<div class="accordion-item training-wrap dark-bg"><h2 class="accordion-header" id="module-'.$index.'"><button class="accordion-button h4';
                    if($collapsed){
                        $response['body'] .= ' collapsed';
                    }
                    $response['body'] .= '" type="button" data-bs-toggle="collapse" data-bs-target="#module-body-'.$index.'" aria-expanded="true" aria-controls="module-body-'.$index.'">'.esc_html($module['title']).'</button></h2><div id="module-body-'.$index.'" class="accordion-collapse collapse';
                    if(!$collapsed){
                        $response['body'] .= ' show';
                    }
                    $response['body'] .= '" aria-labelledby="module-'.$index.'" data-bs-parent="#training-modules"><div class="accordion-body">';
                }else{
                    $response['body'] .= '<div class="training-wrap dark-bg">';
                    $response['body'] .= '<div class="training-wrap-inner">';
                }

                foreach ( $module['sections'] as $section ){

                    $response['body'] .= '<div class="training-section-wrap">';

                    $response['body'] .= '<h5>'.esc_html($section['title']).'</h5>';

                    if( $section['recording_type'] == 'vimeo' && $section['recording_id'] <> '' ){

                        $show_chat = false;
                        $zoom_chat = courses_zoom_chat_get( $section['id'] );
                        if( $zoom_chat['chat'] <> '' ){
                            $show_chat = true;
                        }

                        if( $show_chat ){
                            $response['body'] .= '<div class="row zoom-chat-row"><div class="col-xl-8"><h6 class="d-none d-xl-block mb-0">&nbsp;</h6>';
                        }else{
                            $response['body'] .= '<div class="row"><div class="col-xl-10 offset-xl-1">';
                        }

                        $response['body'] .= '<div id="rec-video" class="hd-video-container HD1080 rec-video" data-chat="'.$section['id'].'"><iframe class="rec-iframe" width="1920" height="1080" src="https://player.vimeo.com/video/'.$section['recording_id'].'" frameborder="0" allowfullscreen data-module="'.$section['id'].'" data-source="vimeo" data-recid="'.$recording_id.'" data-lastviewed="'.date('d/m/Y H:i:s').'" data-numviews="'.$num_views.'" data-viewedend="'.$viewed_end.'" data-viewingtime="'.$viewing_time.'" data-stats="'.$section['id'].'" ></iframe></div><!-- .hd-video-container -->';

                        if( $show_chat ){
                            $response['body'] .= '</div><!-- .col-xl-8 --><div class="col-xl-4 zoom-chat-col">';
                            $response['body'] .= '<h6 class="mb-0">Chat messages</h6>';
                            $response['body'] .= '<div id="zoom-chat-'.$section['id'].'" class="zoom-chat-wrap">';
                            $chat_num = 0;
                            $chats = maybe_unserialize( $zoom_chat['chat'] );
                            foreach ($chats as $chat) {
                                $response['body'] .= '<p id="zc-'.$section['id'].'-'.$chat_num.'" data-time="'.$chat['secs'].'">'.$chat['time'].' '.$chat['who'].' '.$chat['msg'].'</p>';
                                $chat_num ++;
                            }
                            $response['body'] .= '</div>'; // zoom chat wrap
                        }

                        $response['body'] .= '</div><!-- .col -->';
                        $response['body'] .= '</div><!-- .row -->';

                    }else{
                        $response['body'] .= '<p>Training currently unavailable. Please try again later.</p>';
                        // $response['body'] .= print_r($section, true);
                    }

                    $response['body'] .= '</div><!-- .training-section-wrap -->';

                }

                if( $accordions ){
                    $response['body'] .= '</div><!-- .accordion-body -->';
                    $response['body'] .= '</div><!-- .collapse -->';
                    $response['body'] .= '</div><!-- .accordion-item -->';
                }else{
                    $response['body'] .= '</div><!-- .training-wrap-inner -->';
                    $response['body'] .= '</div><!-- .training-wrap -->';
                }

                $collapsed = true;

            }

            if($accordions){
                $response['body'] .= '</div><!-- .accordion -->';
            }

        }
    }
    echo json_encode($response);
    die();
}

// load the resources modal on the My Account pages
// event_id is not used
// this will always show resources for the main training and all modules/events ... unless it is a multi-event workshop, in which case it will exclude events that the user is not an attendee for
add_action('wp_ajax_training_resources_modal_get', 'training_resources_modal_get');
function training_resources_modal_get(){
    global $rpm_theme_options;
    $response = array(
        'status' => 'error',
        'title' => '',
        'body' => '',
    );
    $body = '';

    $training_id = absint( $_POST['trainingID'] );
    $event_id = 'any';
    if( isset( $_POST['eventID'] ) && $_POST['eventID'] <> '' ){
        $event_id = absint( $_POST['eventID'] );
    }

    if($training_id > 0){

        $training_type = get_post_type( $training_id );
        $multi_event = false;
        $max = 10;
        if( $training_type == 'workshop' ){
            $multi_event = workshop_is_multi_event( $training_id );
            $max = 15;
        }
        $attending_events = array();
        if( $multi_event ){
            $attending_events = workshop_user_events_attendee( $training_id, get_current_user_id() );
        }

        $response['title'] = 'Resources for '.get_the_title( $training_id );

        if( $training_type == 'course' ){
            $course = course_get_all( $training_id );

            // if we are going to be showing resources for multiple modules or for a module and the course then we want to group them into accordions
            $counter = 0;
            if( count( $course['resources'] ) > 0 ){
                $counter ++;
            }
            foreach ($course['modules'] as $module){
                if( $counter > 1 ) break; // don't bother to check further
                if( count( $module['resources'] ) > 0 ){
                    $counter ++;
                }
                foreach ($module['sections'] as $section){
                    if( count( $section['resources'] ) > 0 ){
                        $counter ++;
                    }
                }
            }
            if( $counter > 1 ){
                $use_accordions = true;
            }else{
                $use_accordions = false;
            }

            $accordion_item_class = 'show';

            if( count( $course['resources'] ) > 0 ){
                // we'll not put these into the accordion
                $response['body'] = '<div class="mb-2">'.resources_modal_html( $course['resources'] ).'</div>';
                $accordion_item_class = '';
            }

            if( $use_accordions ){
                $response['body'] .= '<div class="accordion res-mod-acc" id="resources-modal-accordion">';
            }

            foreach ($course['modules'] as $module) {
                $accordion_item_id = 'resources-modal-accordion-item-'.$module['id'];
                $module_resources = '';
                if( count( $module['resources'] ) > 0 ){
                    $module_resources = resources_modal_html( $module['resources'] );
                }
                $sections_resources = '';
                foreach ($module['sections'] as $section) {
                    if( count( $section['resources'] ) > 0 ){
                        $sections_resources .= '<p class="mb-0"><strong>'.$section['title'].'</strong></p>';
                        $sections_resources .= resources_modal_html( $section['resources'] );
                    }
                }
                if( count( $module['resources'] ) > 0 || $sections_resources <> '' ){
                    if( $use_accordions ){
                        $response['body'] .= '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#'.$accordion_item_id.'" aria-expanded="true" aria-controls="'.$accordion_item_id.'">'.$module['title'].'</button></h2><div id="'.$accordion_item_id.'" class="accordion-collapse collapse '.$accordion_item_class.'" data-bs-parent="#resources-modal-accordion"><div class="accordion-body">';
                    }else{
                        $response['body'] .= '<h5 class="mb-0">'.$module['title'].'</h5>';
                    }
                    $response['body'] .= $module_resources;
                    $response['body'] .= $sections_resources;
                    if( $use_accordions ){
                        $response['body'] .= '</div></div></div>';
                        $accordion_item_class = '';
                    }
                }
            }

            if( $use_accordions ){
                $response['body'] .= '</div>';
            }

        }else{
            // main training
            $response['body'] = resources_show_list( $training_id );
            // events/modules ...
            for ($i=0; $i < $max; $i++) {
                if( ! $multi_event || in_array( $i, $attending_events ) ){
                    if( resources_exist( $training_id, $i ) ){
                        if( $training_type == 'workshop' ){
                            $event_name = get_post_meta( $training_id, 'event_'.$i.'_name', true );
                        }else{
                            $event_name = get_post_meta( $training_id, 'module_name_'.$i, true);
                        }
                        if( $event_name <> '' ){
                            $response['body'] .= '<h6 class="mb-0">'.$event_name.'</h6>';
                        }
                        $response['body'] .= resources_show_list( $training_id, $i, false );
                    }
                }
            }
        }
        $response['status'] = 'ok';
    }
    echo json_encode($response);
    die();
}

// find which events of the workshop this user is an attendee for
// check that it's a multi-event before calling this function!
// returns an array
function workshop_user_events_attendee( $workshop_id, $user_id ){
    global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $attendees_table = $wpdb->prefix.'cc_attendees';
    // payment records show which events the user has access to
    // therefore we need to know which payment record(s) this user is an attendee for
    // we can get that from the attendees and workshops users table
    $sql = "SELECT a.payment_id
        FROM $attendees_table a
        INNER JOIN $wkshop_users_table w 
            ON a.user_id = w.user_id 
            AND a.payment_id = w.payment_id
        WHERE w.user_id = $user_id AND w.workshop_id = $workshop_id";
    $payment_ids = $wpdb->get_col( $sql );
    // now let's check each of these payment records out for events
    $attending_events = array();
    foreach ($payment_ids as $payment_id) {
        $payment = cc_paymentdb_get_payment( $payment_id );
        if( $payment !== NULL && $payment['event_ids'] <> '' ){
            $event_ids = explode( ',' , $payment['event_ids'] );
            foreach ($event_ids as $event_id) {
                $event_id = (int) $event_id;
                if( $event_id > 0 ){
                    if( ! isset( $attending_events[$event_id] ) ){
                        $attending_events[] = $event_id;
                    }
                }
            }
        }
    }
    return $attending_events;
}

// load the feedback modal on the My Account pages
add_action('wp_ajax_training_feedback_modal_get', 'training_feedback_modal_get');
function training_feedback_modal_get(){
    global $rpm_theme_options;
    $response = array(
        'status' => 'error',
        'title' => '',
        'body' => '',
    );
    $body = '';
    $training_id = absint( $_POST['trainingID'] );
    $event_id = absint( $_POST['eventID'] ); // we use event_id for recordings and workshops

    if($training_id > 0){
        $training_type = get_post_type( $training_id );
        $response['title'] = get_the_title( $training_id );
        $feedback_questions = get_post_meta( $training_id, '_feedback_questions', true);
        if( $feedback_questions == '' ){
            $response['body'] = '<p>Feedback questions currently unavailable. Please check back later.</p>';
        }else{
            $response['body'] = '<form action="" method="POST" id="cc-feedback-form" class="needs-validation" novalidate data-source="modal">';
            $response['body'] .= do_shortcode( $feedback_questions );
            $response['body'] .= '<button type="submit" id="feedback-submit-btn" class="btn btn-primary" data-trainingid="'.$training_id.'" data-eventid="'.$event_id.'">Submit</button></form><div id="cc-feedback-msg"></div>';
        }
        $response['status'] = 'ok';
    }
    echo json_encode($response);
    die();
}

// load the quiz modal on the My Account pages
add_action('wp_ajax_quiz_modal_get', 'quiz_modal_get');
function quiz_modal_get(){
    global $rpm_theme_options;
    $response = array(
        'status' => 'error',
        'title' => '',
        'body' => '',
    );
    $body = '';
    $training_id = absint( $_POST['trainingID'] );
    $event_id = absint( $_POST['eventID'] );
    $user_id = get_current_user_id();

    $response['title'] = get_the_title( $training_id );

    $user_quiz_id = uniqid();
    $question = array(
        'html' => apply_filters( 'the_content', $rpm_theme_options['quiz-intro-text'] ),
        'prev' => false,
        'next' => 1,
    );
    $results = cc_quizzes_previous_results( $user_id, $training_id );
    switch ($results['attempts']) {
        case 0:
            $question['html'] .= '<p>Click below to start.</p>';
            break;
        case 1:
        case 2:
            $question['html'] .= '<p>Your previous result';
            if($results['attempts'] == 1){
                $question['html'] .= ' was ';
            }else{
                $question['html'] .= 's were ';
            }
            $question['html'] .= $results['text'].'.</p>';
            if($results['pass_fail'] == 'pass'){
                $question['next'] = false;
            }else{
                $question['html'] .= '<p>Click below to start.</p>';
            }
            break;
        case 3:
            $question['html'] .= '<p>Your results were '.$results['text'].'.</p>';
            $question['next'] = false;
            break;
    }
    if($results['pass_fail'] == 'pass'){
        $question['html'] .= '<p>You have successfully passed the quiz. Your certificate is now available to download from your account.</p>';
    }

    $response['body'] = '<form method="POST" id="cc-quiz-form" class="needs-validation"><input type="hidden" name="trainingID" value="'.$training_id.'"><input type="hidden" name="eventID" value="'.$event_id.'"><input type="hidden" name="userQuizId" value="'.$user_quiz_id.'"><input type="hidden" name="qnum" value="0"><div class="qa-wrap dark-bg p-3 mb-5">';
    $response['body'] .= $question['html'];
    $response['body'] .= '</div></form>';

    $response['body'] .= '<div class="text-end"><button type="submit" form="cc-quiz-form" id="cc-quiz-form-next" class="btn btn-primary" data-nextq="'.$question['next'].'">Get started</button></div>';

    $response['body'] .= '<div id="quiz-msg" class="quiz-msg"></div>';

    $response['status'] = 'ok';

    echo json_encode($response);
    die();
}


