<?php
/**
 * CE Credits
 */

// create the table that holds the attendance records
add_action('init', 'cc_attendance_update_table_def');
function cc_attendance_update_table_def(){
	global $wpdb;
	$cc_attendance_table_ver = 1;
	$installed_table_ver = get_option('cc_attendance_table_ver');
	if($installed_table_ver <> $cc_attendance_table_ver){
		$table_name = $wpdb->prefix.'attendance';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			training_id mediumint(9) NOT NULL,
			event_id mediumint(9) NOT NULL,
			session_id mediumint(9) NOT NULL,
			join_time datetime NOT NULL,
			leave_time datetime NOT NULL,
			attend_mins mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		if( !function_exists( 'dbDelta' ) ){
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}
		$response = dbDelta($sql);
		update_option('cc_attendance_table_ver', $cc_attendance_table_ver);
	}
}

add_action('admin_menu', 'cc_ce_credits_admin_pages');
function cc_ce_credits_admin_pages(){
	// add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', string $icon_url = '', int|float $position = null ): string
	add_menu_page('CE Credits', 'CE Credits', 'manage_options', 'cc_ce_credits', 'cc_ce_credits_attendance', 'dashicons-welcome-learn-more');
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int|float $position = null ): string|false	
	add_submenu_page( 'cc_ce_credits', 'CE Credits Attendance', 'Attendance', 'manage_options', 'cc_ce_credits', 'cc_ce_credits_attendance');
	// add_submenu_page( 'cc_ce_credits', 'CE Credits Daily', 'Daily', 'manage_options', 'cc_sales_stats_daily', 'cc_sales_stats_daily');
	// add_submenu_page( 'cc_ce_credits', 'CE Credits Categories', 'Categories', 'manage_options', 'cc_sales_stats_cats', 'cc_sales_stats_cats');
}

// Record attendance
function cc_ce_credits_attendance(){
	$workshop_id = 0;
	?>
	<h1>Record Workshop Attendance</h1>

	<h4>Attendance being recorded for:</h4>
	<div class="cc-ce-credits-attend-import-wrap">
		<form id="cc-ce-credits-attend-form" method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="cc_cecasub">
			<table>
				<tr>
					<td>
						<label for="cc-ce-credit-attend-workshop-select">Workshop:</label>
						<select id="cc-ce-credit-attend-workshop-select" name="workshopID">
							<option value="">Please select ...</option>
							<?php echo workshops_started_options($workshop_id); ?>
						</select>
						<span id="caws-wait"><i class="fa-solid fa-spinner fa-spin-pulse"></i></span>
					</td>
					<td id="cc-ce-credits-import-events-wrap">
						<label for="cc-ce-credit-attend-event-select">Event:</label>
						<select id="cc-ce-credit-attend-event-select" name="eventID">
							<option value="">Select workshop first!</option>
						</select>
						<span id="cawe-wait"><i class="fa-solid fa-spinner fa-spin-pulse"></i></span>
					</td>
					<td id="cc-ce-credits-import-sessions-wrap">
						<label for="cc-ce-credit-attend-session-select">Session:</label>
						<select id="cc-ce-credit-attend-session-select" name="sessionID">
							<option value="">Select workshop first!</option>
						</select>
						<span id="cawn-wait"><i class="fa-solid fa-spinner fa-spin-pulse"></i></span>
					</td>
					<td id="cc-ce-credits-import-msg" class="cc-ce-credits-import-msg warning">Select workshop/event/session</td>
				</tr>
				<tr>
					<td colspan="4">
						<label for="cc-ce-credit-attend-file">Attendance (output from Zoom):</label>
						<input type="file" id="cc-ce-credit-attend-file" name="attends">
					</td>
				</tr>
				<tr>
					<td colspan="4">
						<input type="submit" class="button-primary" value="Upload">
					</td>
				</tr>
			</table>
		</form>
		<div id="cc-ce-credit-attend-report" class="cc-ce-credit-attend-report"></div>
	</div>
	<?php
}

// looks up possible events for a workshop
// returns select clauses
add_action('wp_ajax_cc_cecaws', 'cc_cecaws');
function cc_cecaws(){
	$response = array(
		'status' => 'error',
		'found' => '',
		'events' => '',
		'sessions' => '',
	);
	$workshop_id = absint( $_POST['workshopID'] );
	if($workshop_id > 0 && get_post_type($workshop_id) == 'workshop'){
		$response['status'] = 'ok';
		$num_events = 0;
		$options = '';
		for($i = 1; $i<16; $i++){
			$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
			if($event_name <> ''){
				$options .= '<option value="'.$i.'">'.$event_name.'</option>';
				$num_events ++;
			}
		}
		if($num_events > 0){
			$response['found'] = 'events';
			$response['events'] = '<option value="">Please select ...</option>'.$options;
			$response['sessions'] = '<option value="">Select event first!</option>';
		}else{
			// single event ... move on to sessions
			$response['events'] = '<option value="1">Single event workshop</option>';
			$response['found'] = 'sessions';
			$sessions = workshop_event_num_sessions($workshop_id, 1);
			$options = '<option value="">Please select ...</option>';
			for ($i=1; $i <= $sessions ; $i++) { 
				$options .= '<option value="'.$i.'">Session '.$i.'</option>';
			}
			$response['sessions'] = $options;
		}
	}
    echo json_encode($response);
    die();
}

// looks up an event within a workshop
add_action('wp_ajax_cc_cecawe', 'cc_cecawe');
function cc_cecawe(){
	$response = array(
		'status' => 'error',
		'found' => '',
		'events' => '',
		'sessions' => '',
	);
	$workshop_id = absint( $_POST['workshopID'] );
	$event_id = absint( $_POST['eventID'] );
	if($workshop_id > 0 && get_post_type($workshop_id) == 'workshop'){
		$event_name = get_post_meta( $workshop_id, 'event_'.$event_id.'_name', true );
		if($event_name <> ''){
			$response['status'] = 'ok';
			$response['found'] = 'sessions';
			$sessions = workshop_event_num_sessions($workshop_id, $event_id);
			$options = '<option value="">Please select ...</option>';
			for ($i=1; $i <= $sessions ; $i++) { 
				$options .= '<option value="'.$i.'">Session '.$i.'</option>';
			}
			$response['sessions'] = $options;
		}
	}
    echo json_encode($response);
    die();
}

// looks up a session
add_action('wp_ajax_cc_cecawn', 'cc_cecawn');
function cc_cecawn(){
	global $wpdb;
	$attendance_table = $wpdb->prefix.'attendance';
	$response = array(
		'status' => 'error',
		'found' => '',
		'events' => '',
		'sessions' => '',
		'msg' => '',
	);
	$workshop_id = absint( $_POST['workshopID'] );
	$event_id = absint( $_POST['eventID'] );
	$session_id = absint( $_POST['sessionID'] );
	if($workshop_id > 0 && get_post_type($workshop_id) == 'workshop'){
		$event_name = get_post_meta( $workshop_id, 'event_'.$event_id.'_name', true );
		if($event_id == 1 || $event_name <> ''){
			$sessions = workshop_event_num_sessions($workshop_id, $event_id);
			if($session_id <= $sessions){
				// has this session already had attendance recorded for it???
				$sql = "SELECT * FROM $attendance_table WHERE training_id = $workshop_id AND event_id = $event_id AND session_id = $session_id LIMIT 1";
				$rows = $wpdb->get_results($sql, ARRAY_A);
				if(!empty($rows)){
					$response['msg'] = '<i class="fa-solid fa-triangle-exclamation"></i> Previously uploaded ... will replace!';
				}else{
					$response['status'] = 'ok';
					$response['msg'] = '<i class="fa-solid fa-check"></i> OK';
				}
			}
		}
	}
    echo json_encode($response);
    die();
}

// submission of Zoom attendance file
add_action('wp_ajax_cc_cecasub', 'cc_cecasub');
function cc_cecasub(){
	global $wpdb;
	$attendance_table = $wpdb->prefix.'attendance';
	ccpa_write_log('cc_cecasub');
	$response = array(
		'status' => 'error',
		'msg' => '',
	);
	ccpa_write_log($_POST);
	ccpa_write_log($_FILES);
	$workshop_id = absint( $_POST['workshopID'] );
	$event_id = absint( $_POST['eventID'] );
	$session_id = absint( $_POST['sessionID'] );
	if(isset($_FILES['attends']['name'])){
		$filename = $_FILES['attends']['name'];
		$filepath = $_FILES['attends']['tmp_name'];
		ccpa_write_log('filename: '.$filename);
		$response['size'] = filesize( $filepath );
		ccpa_write_log('size:'.$response['size']);
		$extension = strtolower( pathinfo($filename,PATHINFO_EXTENSION) );
		ccpa_write_log('extension: '.$extension);
		if($extension == 'csv'){
			$upload_dir = wp_upload_dir();
			ccpa_write_log('upload_dir:');
			ccpa_write_log($upload_dir);
			$attends_folder = trailingslashit( $upload_dir['basedir'] ).'attendance';
			ccpa_write_log('attends_folder: '.$attends_folder);
			if(!file_exists($attends_folder)){
				ccpa_write_log('creating attends folder');
				mkdir($attends_folder, 0755, true);
			}
			$destination = $attends_folder.'/'.$filename;
			ccpa_write_log('destination: '.$destination);
			$response['destination'] = $destination;
			if(move_uploaded_file($_FILES['attends']['tmp_name'], $destination)){
				ccpa_write_log('move successful');
				// let's take a look at what's in the file
				$zoom_file = fopen($destination, "r"); // seems to need the path and double-quotes around "r"
				if($zoom_file !== false){
					// $response['opened'] = 'yes';
					$first_row = array_map('cc_ce_credits_sanitise_field', fgetcsv($zoom_file) );
					/* old layout
					$should_be = array('User Name', 'User Email', 'Meeting ID', 'Topic', 'Host', 'Host Account Name', 'Participants', 'Start Time', 'End Time', 'User Join Status', 'Join Time', 'Leave Time', 'Screen Share Used', 'File Transfer Used', 'Recording Used', 'Video Used', 'Phone Used', 'VOIP Used', 'Chat Used', 'Meeting Encryption Status');
					*/
					$should_be = array('User Name', 'User Email', 'Meeting ID', 'Topic', 'Host', 'Host Account Name', 'Participants', 'Start Time', 'End Time', 'User Join Status', 'Join Time', 'Leave Time', 'Meeting Encryption Status', 'Screen sharing', 'Video on (once in meeting)', 'Remote control', 'Closed caption', 'Telephone usage with participant ID', 'In-meeting Chat', 'Join by room', 'Waiting room', 'Reaction', 'Mute/unmute', 'Zoom App', 'Annotation', 'Raise hand', 'Virtual background', 'Whiteboard', 'Immersive scene', 'Avatar', 'Switch to mobile', 'File transfer', 'Record to computer', 'Record to cloud', 'Internal');
					ccpa_write_log('first row ...');
					ccpa_write_log($first_row);
					$difference = array_diff($should_be, $first_row);
					if(!empty($difference)){
						$response['msg'] = 'Columns missing from uploaded file: '.implode(', ', $difference);
					}
					if(count($difference) > 1 || in_array('Join Time', $difference) || in_array('Leave Time', $difference) || in_array('User Join Status', $difference) || in_array('User Email', $difference) ){
						$response['msg'] .= '. Upload aborted!';
					}else{
						$join_time_key = array_search('Join Time', $first_row);
						$leave_time_key = array_search('Leave Time', $first_row);
						$join_status_key = array_search('User Join Status', $first_row);
						$user_email_key = array_search('User Email', $first_row);
						// clear out any old entries for this session if there are any
						$where = array(
							'training_id' => $workshop_id,
							'event_id' => $event_id,
							'session_id' => $session_id,
						);
						$wpdb->delete( $attendance_table, $where );
						// now insert the entries from the csv file
						$attendance_format = array('%d', '%d', '%d', '%d', '%s', '%s', '%d');
						// date times in the Zoom file are in a variety of formats!
						// Possible date formats:
						$date_formats = [
						    'd.m.Y h:i:s A',  // New format: 19.11.2025 09:36:00 AM
						    'm/d/Y H:i:s',     // Old format: 11/19/2025 09:36:00 (24-hour) (but Zoom did not include seconds)
						    'm/d/Y h:i:s A',   // Old format with AM/PM: 11/19/2025 09:36:00 AM
						    'd.m.Y H:i:s'      // New format without AM/PM: 19.11.2025 09:36:00
						];
						$errors = array();
						$inserts = 0;
						while ( ($row = fgetcsv($zoom_file) ) !== FALSE){
							if( cc_ce_credits_sanitise_field( $row[$join_status_key] ) == 'In Meeting' ){
								$user = get_user_by('email', cc_ce_credits_sanitise_field( $row[$user_email_key] ) );
								if($user){
									$join_time_raw = cc_ce_credits_sanitise_field($row[$join_time_key]);
									$leave_time_raw = cc_ce_credits_sanitise_field($row[$leave_time_key]);
									// Add seconds if missing
									if (substr_count($join_time_raw, ':') == 1) $join_time_raw .= ':00';
									if (substr_count($leave_time_raw, ':') == 1) $leave_time_raw .= ':00';
									// Try multiple formats for join_time
									$join_time = false;
									foreach ( $date_formats as $format) {
									    $datetime = DateTime::createFromFormat($format, $join_time_raw, new DateTimeZone('UTC'));
									    if ($datetime !== false) {
									        $dt_errors = DateTime::getLastErrors();
									        // Only accept if no warnings (overflow) or errors occurred
									        if ($dt_errors['warning_count'] == 0 && $dt_errors['error_count'] == 0) {
									            $join_time = $datetime;
									            break;
									        }
									    }
									}
									// Try multiple formats for leave_time
									$leave_time = false;
									foreach ( $date_formats as $format) {
									    $datetime = DateTime::createFromFormat($format, $leave_time_raw, new DateTimeZone('UTC'));
									    if ($datetime !== false) {
									        $dt_errors = DateTime::getLastErrors();
									        // Only accept if no warnings (overflow) or errors occurred
									        if ($dt_errors['warning_count'] == 0 && $dt_errors['error_count'] == 0) {
									            $leave_time = $datetime;
									            break;
									        }
									    }
									}
									// Error checking
									if ($join_time === false || $leave_time === false) {
										$errors[] = cc_ce_credits_sanitise_field( $row[$user_email_key] ).' Invalid dates - Join: ' . $join_time_raw . ', Leave: ' . $leave_time_raw.' - row ignored';
									    continue;
									}
									// $join_time = DateTime::createFromFormat( "m/d/Y H:i:s", cc_ce_credits_sanitise_field( $row[$join_time_key] ).':00', new DateTimeZone('UTC') );
									// $leave_time = DateTime::createFromFormat( "m/d/Y H:i:s", cc_ce_credits_sanitise_field( $row[$leave_time_key] ).':00', new DateTimeZone('UTC') );
									$attendance = $join_time->diff($leave_time);
									$minutes = $attendance->days * 24 * 60;
									$minutes += $attendance->h * 60;
									$minutes += $attendance->i;
									if($join_time && $leave_time){
										$data = array(
											'user_id' => $user->ID,
											'training_id' => $workshop_id,
											'event_id' => $event_id,
											'session_id' => $session_id,
											'join_time' => $join_time->format('Y-m-d H:i:s'),
											'leave_time' => $leave_time->format('Y-m-d H:i:s'),
											'attend_mins' => $minutes,
										);
										$wpdb->insert( $attendance_table, $data, $attendance_format );
										$inserts ++;
									}else{
										$errors[] = cc_ce_credits_sanitise_field( $row[$user_email_key] ).' Join and/or leave time are invalid - row ignored';
									}
								}else{
									$errors[] = cc_ce_credits_sanitise_field( $row[$user_email_key] ).' User not found - row ignored';
								}
							}
						}
						$response['msg'] .= '<br>'.$inserts.' attendance records saved';
						foreach ($errors as $error) {
							$response['msg'] .= '<br>'.$error;
						}
						$response['status'] = 'ok';
					}
				}
			}
		}
	}
    echo json_encode($response);
    die();
}

// sanitise a csv field
function cc_ce_credits_sanitise_field($field){
	$field = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $field);
	$field = trim( $field );
	return $field;
}

/*
add_action('init', 'my_catchall');
function my_catchall(){
	ccpa_write_log('my_catchall');
	ccpa_write_log($_REQUEST);
	ccpa_write_log($_FILES);
}
*/

// load the CE Credits modal on the My Account pages - now used for all certificates
add_action('wp_ajax_cecredits_modal_get', 'cecredits_modal_get');
function cecredits_modal_get(){
	global $rpm_theme_options;
	$response = array(
		'status' => 'error',
		'title' => '',
		'body' => '',
	);
	$body = '';
	$training_id = absint( $_POST['trainingID'] );
	$event_id = absint( $_POST['eventID'] ); // 0 for the workshop
	if($training_id > 0){
		$training_type = get_post_type($training_id);
		if( $training_type == 'workshop' || $training_type == 'recording' || $training_type == 'course' ){
			$response['status'] = 'ok';
			$response['title'] = 'Certificate for '.get_the_title($training_id);
			if($event_id > 0){
				$event_name = get_post_meta( $training_id, 'event_'.$event_id.'_name', true );
				if($event_name <> ''){
					$response['title'] .= ' - '.$event_name;
				}
			}
			if($training_type == 'workshop'){
				$body = '<p>'.$rpm_theme_options['ce-credits-modal-intro'].'</p>';
			}else{
				// recording or course
				$body = '<p>'.$rpm_theme_options['ce-credits-modal-intro-rec'].'</p>';
			}

			$body .= cc_ce_credits_training_user_attendance_html($training_id, $event_id);

			$body .= cc_ce_credits_training_user_feedback_html($training_id, $event_id);

			$body .= cc_ce_credits_training_cert_html($training_id, $event_id);

		}
	}
	$response['body'] = wms_tidy_html($body);
    echo json_encode($response);
    die();
}

// returns a suitable attendance message to be shown in the modal for this training/user
function cc_ce_credits_training_user_attendance_html($training_id, $event_id=0){
	global $wpdb;

	$training_type = course_training_type( $training_id );

	$quiz_override = get_post_meta( $training_id, 'quiz_override', true );
	if( $training_type == 'recording' && $quiz_override == 'yes' ){
		return '';
	}

	$html = '<h6 class="mb-0">Attendance</h6><div class="row">';
	$attendance_table = $wpdb->prefix.'attendance';
	if( $training_type == 'workshop' ){
		$html .= '<div class="col-sm-8"><p>Certificates require your attendance at the training. ';
		if($event_id == 0){
			$btn_type = 'none';
			$workshop_timestamp = get_post_meta($training_id, 'workshop_timestamp', true); // time and date is good
			if($workshop_timestamp <> '' && $workshop_timestamp > time()){
		    	// training not finished yet
				$html .= 'Please remember to attend all training sessions.';
				// is it a face to face workshop?
				$venue = get_post_meta( $training_id, 'event_1_venue_name', true );
				if( $venue <> '' ){
					// yes, it is ...
					$btn_type = 'face';
				}else{
					$btn_type = 'zoom';
				}
			}else{
				// has this training already had attendance recorded for it? (checking final session)
				$num_sessions = workshop_event_num_sessions($training_id, 1);
				$sql = "SELECT * FROM $attendance_table WHERE training_id = $training_id AND event_id = 1 AND session_id = $num_sessions LIMIT 1";
				$rows = $wpdb->get_results($sql, ARRAY_A);
				if(empty($rows)){
					// attendance not recorded yet
					$html .= 'Your attendance has not been verified. Verification will be completed within 24 hours of the training finishing.';
				}else{
					if(cc_ce_credits_sufficient_attendance($training_id, 1)){
						$html .= 'Your attendance has been verified.';
						$btn_type = 'done';
					}else{
						// is there a recording for this workshop that the user could view?
					    $recording = get_post_meta($training_id, 'workshop_recording', true);
					    $recording_access = ccrecw_user_can_view($recording);
					    if($recording_access['access']){
					    	// and have they already taken the quiz?
					    	$results = cc_quizzes_previous_results( get_current_user_id(), $recording);
					    	if($results['pass_fail'] == 'pass'){
					    		$html .= 'You have successfully passed the post-test.';
					    		$btn_type = 'quiz pass';
					    	}else{
					    		if($results['attempts'] > 2){
					    			$html .= 'Your post-test results were '.$results['text'].'. Therefore, we are unable to issue a Certificate.';
					    			$btn_type = 'quiz fail';
					    		}else{
					    			if($results['attempts'] == 0){
					    				if( recording_watched( get_current_user_id(), $recording ) ){
					    					$html .= 'It looks like you didn\'t attend this training live. You need to complete the post-test to verify your attendance. You can view the recording to help prepare for this.';
					    				}else{
					    					$html .= 'It looks like you didn\'t attend this training live. You may wish to view the recording and complete the post-test to verify your attendance.';
					    				}
								    	$quiz_id = cc_quizzes_quiz_id($recording);
								    	if($quiz_id === NULL){
								    		$btn_type = 'recording';
								    	}else{
								    		$btn_type = 'rec + quiz';
								    	}
						    		}else{
						    			$html .= 'Your post-test results so far were '.$results['text'].'. You may wish to re-watch the recording before attempting the post-test again.';
							    		$btn_type = 'rec + quiz';
						    		}
					    		}
					    	}
					    }else{
					    	$html .= 'Your attendance has not been verified.';
					    }
					}
				}
		    }
		    $html .= '</p></div><div class="col-sm-4 text-right">';
		    switch ($btn_type) {

		    	case 'zoom':
					$zoom = '';
				    $workshop_zoom = get_post_meta($training_id, 'workshop_zoom', true);
				    $html .= 'Zoom link:<br>';
	                if($workshop_zoom <> ''){
	                    $zoom_link = esc_url( add_query_arg( array('code' => cc_myacct_workshop_zoom_link_code($training_id, 0)), site_url('/zoom/') ) );
	                    $html .= '<a href="'.$zoom_link.'" class="btn btn-training btn-sm mb-3 w-100" target="_blank">Join training</a>';
	                }else{
	                    $html .= '<span class="attendance-bad mb-3 w-100">To be supplied</span>';
	                }
		    		break;

		    	case 'face':
		    		$html .= '<span class="attendance-bad mb-3 w-100">Return when training completed</span>';
		    		break;

		    	case 'done':
				    // $html .= 'Zoom link:<br>';
                    $html .= '<span class="attendance-ok mb-3 w-100"><i class="fa-solid fa-check"></i> Attendance verified!</span>';
                    // $html .= '<a href="javascript:void(0)" class="btn btn-training btn-sm mb-3 w-100" disabled>Join training <i class="fa-solid fa-check"></i></a>';
		    		break;

		    	case 'recording':
				    $html .= 'View recording:<br>';
		            $html .= cc_myacct_workshops_recording_btn( $training_id, $event_id, 'modal' );
		            // $url = add_query_arg( 'id', $recording, '/watch-recording' );
		            // $html .= '<a class="btn btn-training btn-sm mb-3 w-100 recording-btn" href="'.esc_url($url).'"><i class="fa-solid fa-video fa-fw"></i> Recording</a>';
		    		break;

		    	case 'rec + quiz':
		            $html .= cc_myacct_workshops_recording_btn( $training_id, $event_id, 'modal' );
		            // $url = add_query_arg( 'id', $recording, '/watch-recording' );
		            // $html .= '<a class="btn btn-training btn-sm mb-3 w-100 recording-btn" href="'.esc_url($url).'"><i class="fa-solid fa-video fa-fw"></i> View Recording</a>';
	                $html .= '<button class="btn mb-3 btn-training btn-sm w-100 quiz-btn" data-bs-toggle="modal" data-bs-target="#myacct-quiz-modal" data-trainingid="'.$recording.'" data-eventid="'.$event_id.'">Post-test</button>';
					// $url = add_query_arg( 't', $recording, '/quiz' );
					// $html .= '<a class="btn btn-training btn-sm mb-3 w-100 quiz-btn" href="'.esc_url($url).'"><i class="fa-solid fa-star fa-fw"></i> Post-test</a>';
		    		break;

		    	case 'quiz pass':
		    		$html .= '<span class="feedback-ok mb-3 w-100"><i class="fa-solid fa-check"></i> Post-test</span>';
		    		break;

		    	case 'quiz fail':
			    	$html .= '<span class="feedback-bad mb-3 w-100"><i class="fa-solid fa-times"></i> Post-test</span>';
			    	break;

		    	default:
                    $html .= '<span class="attendance-bad mb-3 w-100">Attendance not verified</span>';
		    		break;
		    }
		    $html .= '</div>';
		}else{
			// multi event


		}
	}else{
		// recording
		/*
		if( get_post_meta( $training_id, 'quiz_override', true ) == 'yes' ){
			$html .= '<div class="col-sm-8"><p>Proof of attandance is not needed to obtain a certificate for this training.</p></div>';
		}else{
		*/

		// have they already tried the quiz?
		$results = cc_quizzes_previous_results( get_current_user_id(), $training_id);
		if($results['pass_fail'] == 'pass'){
			$html .= '<div class="col-sm-8"><p>CE Credits require that you demonstrate that you completed the training. You have successfully passed the post-test.</p></div><div class="col-sm-4 text-right"><p><span class="feedback-ok mb-3 w-100"><i class="fa-solid fa-check"></i> Passed</span></p></div>';
		}elseif($results['attempts'] > 2){
			$html .= '<div class="col-sm-8"><p>CE Credits require that you demonstrate that you completed the training. Your post-test results were '.$results['text'].'. Therefore, we are unable to issue a CE Certificate.</p></div><div class="col-sm-4 text-right"><p><span class="feedback-bad mb-3 w-100"><i class="fa-solid fa-star"></i> Test not passed</span></p></div>';
		}elseif($results['attempts'] > 0){
			$url = add_query_arg( 't', $training_id, '/quiz' );
			$html .= '<div class="col-sm-8"><p>CE Credits require that you demonstrate that you completed the training. Your post-test results so far were '.$results['text'].'.';
            $recording_access = ccrecw_user_can_view($training_id);
            if($recording_access['access']){
            	$html .= ' You may wish to re-watch the recording before attempting the post-test again.';
            }
            $html .= '</p></div><div class="col-sm-4 text-right"><p><a class="btn btn-training btn-sm mb-3 w-100 quiz-btn" href="'.esc_url($url).'"><i class="fa-solid fa-star fa-fw"></i> Post-test</a></p></div>';
		}else{
	    	$quiz_id = cc_quizzes_quiz_id($training_id);
	    	if($quiz_id === NULL){
				$html .= '<div class="col-sm-8"><p>CE Credits require that you demonstrate that you completed the training. Currently there is no post-test to confirm attendance.</p></div><div class="col-sm-4 text-right"></div>';
			}else{
				$url = add_query_arg( 't', $training_id, '/quiz' );
				$html .= '<div class="col-sm-8"><p>CE Credits require that you demonstrate that you completed the training. After you have watched the recording, please complete the post-test to confirm attendance.</p></div><div class="col-sm-4 text-right"><p><a class="btn btn-training btn-sm mb-3 w-100 quiz-btn" href="'.esc_url($url).'"><i class="fa-solid fa-star fa-fw"></i> Post-test</a></p></div>';
			}
		}

		// }
	}
    $html .= '</div>';
    return $html;
}

// returns the html with suitable msgs and btns for feedback
function cc_ce_credits_training_user_feedback_html($training_id, $event_id){
	$html = '<h6 class="mb-0">Feedback</h6><div class="row">';
	$html .= '<div class="col-sm-8"><p>Certificates will be issued once you have submitted feedback. ';
	$btn_type = 'ask';
	if(cc_feedback_submitted($training_id, $event_id)){
		$html .= 'Thanks, your feedback has been received.';
		$btn_type = 'done';
	}else{
		if(get_post_type($training_id) == 'workshop'){
			if($event_id == 0){
				$workshop_timestamp = get_post_meta($training_id, 'workshop_timestamp', true); // time and date is good
				if($workshop_timestamp <> '' && $workshop_timestamp > time()){
			    	// training not finished yet
			    	$html .= 'Please submit feedback after the training completes.';
			    	$btn_type = 'wait';
				}else{
					$feedback_questions = get_post_meta($training_id, '_feedback_questions', true);
				    $old_fb_url = get_post_meta($training_id, 'workshop_feedback', true);
				    if($feedback_questions <> ''){
						$html .= 'Please submit your feedback.';
				    }elseif($old_fb_url <> ''){
						$html .= 'Please submit your feedback.';
						$btn_type = 'old';
				    }else{
						$html .= 'Feedback submission not available for this training yet - please try again shortly.';
				    	$btn_type = 'wait';
					}
				}
			}else{
				// multi-event

			    if($event_id > 0){
			        $meta_key = 'event_'.$event_id;
			    }else{
			        $meta_key = 'workshop';
			    }
			    $old_fb_url = get_post_meta($training_id, $meta_key.'_feedback', true);


			}
		}else{
			// recording



		}
	}
	$html .= '</p></div><div class="col-sm-4 text-right"><p>';
	switch ($btn_type) {
		case 'ask':
            $fb_url = add_query_arg(
                array(
                    'f' => cc_feedback_link_code($training_id, $event_id, get_current_user_id()),
                ),
                '/feedback',
            );
            // $html .= '<a class="btn btn-training btn-sm mb-3 w-100 feedback-btn" href="'.$fb_url.'" target="_blank"><i class="fa-solid fa-comments fa-fw"></i> Give feedback</a>';
            $html .= '<button type="button" class="btn btn-training btn-sm mb-3 w-100" data-bs-toggle="modal" data-bs-target="#myacct-feedback-modal" data-trainingid="'.$training_id.'" data-eventid="'.$event_id.'">Give feedback</button>';
			break;

		case 'done':
            $html .= '<span class="feedback-ok mb-3 w-100"><i class="fa-solid fa-check"></i> Feedback received!</span>';
            // $html .= '<a class="btn btn-training btn-sm mb-3 w-100 feedback-btn" href="javascript:void(0)" disabled><i class="fa-solid fa-comments fa-fw"></i> Give feedback <i class="fa-solid fa-check"></i></a>';
			break;

		case 'wait':
            $html .= '<span class="feedback-bad mb-3 w-100"><i class="fa-regular fa-hourglass-half fa-fw"></i> Give feedback</span>';
            // $html .= '<a class="btn btn-training btn-sm mb-3 w-100 feedback-btn" href="javascript:void(0)" disabled><i class="fa-solid fa-comments fa-fw"></i> Give feedback</a>';
			break;

		case 'old':
            $html .= '<a class="btn btn-training btn-sm mb-3 w-100 feedback-btn" href="'.$old_fb_url.'" target="_blank"><i class="fa-solid fa-comments fa-fw"></i> Give feedback</a>';
            break;
		
		default:
			// nothing
			break;
	}
	$html .= '</p></div></div>';
	return $html;
}

// the html around the CE Credits certificate button
function cc_ce_credits_training_cert_html($training_id, $event_id=0){
	$html = '<h6 class="mb-0">Certificate</h6><div class="row">';
	$html .= '<div class="col-12"><p>';
	$attendance = cc_ce_credits_sufficient_attendance($training_id, $event_id);
	// CC certs will be available regardless of feedback
	// therefore, if there is a cc cert for this training, then we only care about attendance 
	if( cc_certs_setting( $training_id, $event_id, 'cc' ) == 'yes' ){
		$feedback = true;
	}else{
		$feedback = cc_feedback_submitted($training_id, $event_id);
	}
	if($attendance && $feedback){
		$html .= 'Please click the button to obtain your Certificate.';
	}elseif( $attendance ){
		$html .= 'Your Certificates will be available once your feedback is received.';
	}elseif( $feedback ){
		$html .= 'Your Certificates will be available once your attendance is verified.';
	}else{
		$html .= 'Your Certificates will be available once your attendance is verified and your feedback is received.';
	}
	$user_id = get_current_user_id();
	$reqs = cc_certs_extra_requirements( $training_id, $event_id, $user_id );
	$html .= $reqs['html'];
	$html .= '</p></div><div class="row">';
	if($attendance && $feedback){
		$html .= cc_certs_button( $training_id, $event_id, $user_id, $reqs['bacb'] );
	}else{
        $html .= '<div class="col-sm-6 offset-sm-3"><span class="feedback-bad mb-3 w-100">Certificate unavailable</span></div>';
	}
	$html .= '</div>';
	return $html;
}

// has this user's attendance been enough?
// for now, for a workshop, turning up is enough!
// returns bool
function cc_ce_credits_sufficient_attendance($training_id, $event_id, $user_id=0){
	// ccpa_write_log('cc_ce_credits_sufficient_attendance');
	global $wpdb;
	$attendance_table = $wpdb->prefix.'attendance';
	if($user_id == 0){
		$user_id = get_current_user_id();
	}
	if($event_id == 0){
		$event_id = 1;
	}
	// ccpa_write_log('training_id:'.$training_id.' event_id:'.$event_id.' user_id:'.$user_id);
	if(get_post_type($training_id) == 'workshop'){
		// ccpa_write_log('workshop');
		$workshop_attendance = true;
		for ($i=1; $i <= workshop_event_num_sessions($training_id, $event_id); $i++) { 
			$sql = "SELECT attend_mins FROM $attendance_table WHERE user_id = $user_id AND training_id = $training_id AND event_id = $event_id AND session_id = $i LIMIT 1";
			// ccpa_write_log($sql);
			$attend_mins = $wpdb->get_var( $sql );
			// ccpa_write_log('attend_mins:'.$attend_mins);
			if($attend_mins === NULL){
				// ccpa_write_log('attend_mins is null');
				$workshop_attendance = false;
				break;
			}
		}
		// ccpa_write_log('returning true');
		if($workshop_attendance){
			return true;
		}
		// if there was a linked recording and they passed the quiz for that then this is also accepted attendance
	    $recording = (int) get_post_meta($training_id, 'workshop_recording', true);
	    if($recording > 0){
	    	// is there a quiz_override for this recording?
	    	if( get_post_meta( $training_id, 'quiz_override', true ) == 'yes' ){
	    		return true;
	    	}
			$results = cc_quizzes_previous_results( $user_id, $recording);
			if($results['pass_fail'] == 'pass'){
				return true;
			}
	    }
	    return false;
	}else{
		// ccpa_write_log('it is a recording');
		// Added May 2025, if there is a CC cert then this cert is always available
		if( cc_certs_setting( $training_id, $event_id, 'cc' ) == 'yes' ){
			return true;
		}
    	// is there a quiz_override for this recording?
    	if( get_post_meta( $training_id, 'quiz_override', true ) == 'yes' ){
    		return true;
    	}
		// attendance means passing the quiz
		$results = cc_quizzes_previous_results( $user_id, $training_id);
		if($results['pass_fail'] == 'pass'){
			return true;
		}
		return false;
	}
}

// get all workshops that a user attended
// always returns an array
function cc_ce_credits_user_attended( $user_id ){
	global $wpdb;
	$attendance_table = $wpdb->prefix.'attendance';
	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT training_id FROM $attendance_table WHERE user_id = %d",
			$user_id
		)
	);
}

// convert info about a CE Credit certificate into a base64 string for url inclusion
function cc_ce_credits_cert_parms_encode($training_id, $event_id, $user_id){
	$daft_number = $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 59563;
	$string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
	return base64_encode($string);
}

// decode a recording cert parm string
function cc_ce_credits_cert_parms_decode($string){
	$string = base64_decode($string);
	list($training_id, $event_id, $user_id, $daft_number) = explode("|", $string);
	if($daft_number == $training_id * $training_id + $user_id * $user_id + $event_id * $event_id + 59563){
		return array(
			'training_id' => $training_id,
			'event_id' => $event_id,
			'user_id' => $user_id,
		);
	}
	return false;
}

// how many credits (hours) does this training give ...
// Jul 24: don't think this function is actively used any more ... we just us the ce_credits meta
function cc_ce_credits_number_credits($training_id, $event_id){
	$training_mins = 0;
	if(get_post_type($training_id) == 'workshop'){
		if($event_id < 2){
			for ($i=1; $i <= 6; $i++) {
				if($i == 1){
		            $prefix = 'event_1';
					$start_meta = 'meta_a';
		        }else{
		            $prefix = 'event_1_sess_'.$i;
					$start_meta = $prefix.'_date';
				}
				// start date ...
				$start_date = get_post_meta($training_id, $start_meta, true); // d/m/y
				if($start_date <> ''){
					// start time ...
					$start_time = get_post_meta($training_id, $prefix.'_time', true); // H:i (UTC)
					if($start_time == ''){
						$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' 00:00', new DateTimeZone('UTC'));
					}else{
						$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' '.$start_time, new DateTimeZone('UTC'));
					}
					// end date
					$end_date = get_post_meta($training_id, $prefix.'_date_end', true);
					if($end_date == ''){
						$end_date = $start_date;
					}
					$end_time = get_post_meta($training_id, $prefix.'_time_end', true); // H:i (UTC)
					if($end_time == ''){
						$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' 00:00', new DateTimeZone('UTC'));
					}else{
						$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' '.$end_time, new DateTimeZone('UTC'));
					}
					if($start_datetime && $end_datetime){
						$interval = $start_datetime->diff($end_datetime);
						$minutes = $interval->days * 24 * 60;
						$minutes += $interval->h * 60;
						$minutes += $interval->i;
						$training_mins += $minutes;
					}
				}
			}
		}else{
            $prefix = 'event_'.$event_id.'_sess_1';
			$start_meta = $prefix.'_date';
			// start date ...
			$start_date = get_post_meta($training_id, $start_meta, true); // d/m/y
			if($start_date <> ''){
				// start time ...
				$start_time = get_post_meta($training_id, $prefix.'_time', true); // H:i (UTC)
				if($start_time == ''){
					$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' 00:00', new DateTimeZone('UTC'));
				}else{
					$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' '.$start_time, new DateTimeZone('UTC'));
				}
				// end date
				$end_date = get_post_meta($training_id, $prefix.'_date_end', true);
				if($end_date == ''){
					$end_date = $start_date;
				}
				$end_time = get_post_meta($training_id, $prefix.'_time_end', true); // H:i (UTC)
				if($end_time == ''){
					$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' 00:00', new DateTimeZone('UTC'));
				}else{
					$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' '.$end_time, new DateTimeZone('UTC'));
				}
				if($start_datetime && $end_datetime){
					$interval = $start_datetime->diff($end_datetime);
					$minutes = $interval->days * 24 * 60;
					$minutes += $interval->h * 60;
					$minutes += $interval->i;
					$training_mins += $minutes;
				}
			}
		}

		// now convert the minutes to hours (in half hour increments)
		$hours = floor($training_mins / 60);
		$extra_mins = $training_mins - ($hours * 60);
		if($extra_mins >= 30){
			$hours += 0.5;
		}

	}else{
		// recording
		$hours = (float) get_post_meta($training_id, 'ce_credits', true);
	}
	return $hours;
}

// should the CE Credits button be shown for this recording?
// we'll show the button if there are CE credit hours for this recording, there's a quiz for it and there's a feedback form for it.
// returns bool (true = show the btn)
function cc_ce_credits_offer_for_recording($recording_id){
	$ce_credits = (float) get_post_meta($recording_id, 'ce_credits', true);
	if( $ce_credits <= 0 ) return false;
	$quiz_id = cc_quizzes_quiz_id($recording_id);
	if($quiz_id === NULL) return false;
	$feedback_questions = get_post_meta($recording_id, '_feedback_questions', true);
	if($feedback_questions == '') return false;
	return true;
}
