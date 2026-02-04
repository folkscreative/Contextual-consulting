<?php
/**
 * Zoom chat integration
 */

// The Zoom Chat table
// Mar 2025 ... table being replaced by courses_zoom_chat table ... see below
add_action('init', 'cc_zoom_chat_table');
function cc_zoom_chat_table(){
	global $wpdb;
	$cc_zoom_chat_table_ver = 1;
	// v1 Jul 2024 new table (theme v1.64)
	$installed_table_ver = get_option('cc_zoom_chat_table_ver');
	if($installed_table_ver <> $cc_zoom_chat_table_ver){
		$table_name = $wpdb->prefix.'zoom_chat';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			last_update timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			training_id mediumint(9) NOT NULL,
			module smallint(4) NOT NULL,
			chat mediumtext NOT NULL,
			gaps text NOT NULL,
			raw_gaps text NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);

		// update
		if( $cc_zoom_chat_table_ver == 1 ){
			maybe_convert_table_to_utf8mb4( $table_name );
		}

		update_option('cc_zoom_chat_table_ver', $cc_zoom_chat_table_ver);
	}
}

add_action('init', 'courses_zoom_chat_table');
function courses_zoom_chat_table(){
	global $wpdb;
	$courses_zoom_chat_table_ver = 1;
	// v1 Mar 2025 new table replaces the above for the new courses structure
	$installed_table_ver = get_option('courses_zoom_chat_table_ver');
	if($installed_table_ver <> $courses_zoom_chat_table_ver){
		$table_name = $wpdb->prefix.'courses_zoom_chat';
		$sections_table = $wpdb->prefix.'course_sections';
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS $table_name (
			    id 				BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			    section_id 		BIGINT UNSIGNED NOT NULL,
			    chat 			MEDIUMTEXT,
			    gaps 			TEXT,
			    raw_gaps 		TEXT,
			    created_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			    updated_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    FOREIGN KEY (section_id) REFERENCES $sections_table(id) ON DELETE CASCADE
			) ENGINE = InnoDB 
			DEFAULT CHARSET = utf8mb4 
			COLLATE = utf8mb4_unicode_520_ci"
		);
		update_option('courses_zoom_chat_table_ver', $courses_zoom_chat_table_ver);
	}
}



// get
// returns a row or NULL if not found
function cc_zoom_chat_get( $training_id, $module ){
	global $wpdb;
	$zoom_chat_table = $wpdb->prefix.'zoom_chat';
	$sql = "SELECT * FROM $zoom_chat_table WHERE training_id = $training_id AND module = $module LIMIT 1";
	return $wpdb->get_row( $sql, ARRAY_A );
}

// empty zoom_chat
function cc_zoom_chat_empty(){
	return array(
		'id' => 0,
		'last_update' => '',
		'training_id' => 0,
		'module' => 0,
		'chat' => '',
		'gaps' => '',
		'raw_gaps' => '',
	);
}

// add or update a zoom chat
// $zoom_chat must be an array only containing training_id, module, chat, gaps and raw_gaps
// returns false if it fails or the number of rows added/updated (which might be 0 if the data is unchanged)
function cc_zoom_chat_update( $zoom_chat ){
	global $wpdb;
	$zoom_chat_table = $wpdb->prefix.'zoom_chat';
	$zoom_chat_format = array( '%d', '%d', '%s', '%s', '%s' );
	if( cc_zoom_chat_get( $zoom_chat['training_id'], $zoom_chat['module'] ) === NULL ){
		// add
		return $wpdb->insert( $zoom_chat_table, $zoom_chat, $zoom_chat_format );
	}else{
		// update
		$where = array(
			'training_id' => $zoom_chat['training_id'],
			'module' => $zoom_chat['module'],
		);
		return $wpdb->update( $zoom_chat_table, $zoom_chat, $where, $zoom_chat_format );
	}
}


/**
 * Courses ....
 */

// gets the zoom chat or an array with empty fields if not found
function courses_zoom_chat_get( $section_id ){
	global $wpdb;
	$zoom_chat_table = $wpdb->prefix.'courses_zoom_chat';
	$sql = "SELECT * FROM $zoom_chat_table WHERE section_id = $section_id LIMIT 1";
	$zoom_chat = $wpdb->get_row( $sql, ARRAY_A );
	if( $zoom_chat === NULL ){
		$zoom_chat = array(
		    'id' => 0,
		    'section_id' => $section_id,
		    'chat' => '',
		    'gaps' => '',
		    'raw_gaps' => '',
		    'created_at' => NULL,
		    'updated_at' => NULL,
		);
	}
	return $zoom_chat;
}

// excpects an array of id (update only), section_id, chat, gaps, raw_gaps
function courses_zoom_chat_update( $zoom_chat ){
	global $wpdb;
	$zoom_chat_table = $wpdb->prefix.'courses_zoom_chat';
	$zoom_chat_format = array( '%d', '%s', '%s', '%s' );
	if( isset( $zoom_chat['id'] ) && $zoom_chat['id'] > 0 ){
		// update
		$zoom_chat_id = $zoom_chat['id'];
		unset( $zoom_chat['id'] );
		$where = array(
			'id' => $zoom_chat_id,
		);
		$result = $wpdb->update( $zoom_chat_table, $zoom_chat, $where, $zoom_chat_format );
		return $zoom_chat_id;
	}else{
		unset( $zoom_chat['id'] );
		$result = $wpdb->insert( $zoom_chat_table, $zoom_chat, $zoom_chat_format );
		return $wpdb->insert_id;
	}
}


// upload a zoom chat from the recording edit page
// upgraded for courses
add_action( 'wp_ajax_zoom_chat_upload', 'cc_zoom_chat_upload' );
function cc_zoom_chat_upload(){
	$response = array(
		'status' => 'error',
		'msg' => '',
	);

	$course_id = $section_id = $uncut_vid1 = 0;
	$chat = $gaps = '';
	
	if( isset( $_POST['course'] ) ){
		$course_id = (int) $_POST['course'];
	}
	if( isset( $_POST['section'] ) ){
		$section_id = (int) $_POST['section'];
	}
	if( isset( $_POST['gaps'] ) ){
		$gaps = stripslashes( sanitize_textarea_field( $_POST['gaps'] ) );
	}
	if( isset( $_FILES['chat'] ) 
		&& $_FILES['chat']['error'] == 0 
		&& is_uploaded_file( $_FILES['chat']['tmp_name'] )
		&& $course_id > 0
		&& $section_id > 0 ){

		$abort = false;

		// there may be a second file
		$second_file = false;
		if( isset( $_FILES['chat2'] ) && $_FILES['chat2']['error'] == 0 && is_uploaded_file( $_FILES['chat2']['tmp_name'] ) ){
			$second_file = true;
			if( isset( $_POST['uncutV1'] ) && $_POST['uncutV1'] <> '' ){
				$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', '1970-01-01 '.trim( $_POST['uncutV1'] ), new DateTimeZone( 'UTC' ) );
				if( $dt ){
					$uncut_vid1 = $dt->getTimestamp();
				}else{
					$abort = true;
				}
			}else{
				$abort = true;
			}
		}
		if( $abort ){
			$response['msg'] = 'Second chat file supplied but end time of first video invalid or missing. Nothing updated.';
		}

		/* the gaps will look something like this:
		00:00:00 add 00:00:13\r\n00:00:00 cut 00:49:07\r\n00:56:38 cut 01:06:26
		Oct 2024: now the data looks the same but the meaning is different!
		00:56:38 cut 01:06:26 now means cut from 56:38 TO 1:06:26 (previously it was the amount of time cut out)
		ALSO, the times referred to are the times of the original video, not the edited video!
		Nov 2024 ...
		all vids get a logo section added to the front (about 14 secs)
		cuts are then worked out based on the video timings after that has been added and before anything has actually been cut
		eg 00:00:00 add 00:00:14, 00:20:00 cut 00:22:00, 00:40:00 cut 00:45:00 means that 2 mins was cut at the 19 mins 46 sec point of the original video and 5 mins was cut athe 39 mins 46 secs point of the original video
		*/

		// let's find out what newlines look like with this user's device/browser
		if( strpos( $gaps, "\r\n" ) ){
			$newline = "\r\n";
		}elseif( strpos( $gaps, "\n" ) ){
			$newline = "\n";
		}elseif( strpos( $gaps, "\r" ) ){
			$newline = "\r";
		}else{
			// no newlines in it
			$newline = false;
		}

		if( $newline === false ){
			$split_gaps = array( $gaps );
		}else{
			$split_gaps = explode( $newline, $gaps );
		}

		// convert the gaps into seconds
		$gaps_secs = array();
		$line_count = 0;
		$edited_start = 0;
		// $cum_duration = 0;
		$secs_added = 0;
		foreach ($split_gaps as $split_gap) {
			$line_count ++;
			if( $split_gap <> '' ){
				$split_gap = trim( $split_gap );
				$gap = explode( ' ', $split_gap );

				if( count( $gap ) <> 3 ){
					$abort = true;
					$response['msg'] = 'Gaps invalid on line '.$line_count.': must be time add/cut time on each line with a space between. Nothing updated.';
					break;
				}
				// $dt = new DateTime("1970-01-01 $gap[0]", new DateTimeZone('UTC')); // crashes if it's an invalid format!
				$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', "1970-01-01 $gap[0]", new DateTimeZone( 'UTC' ) );
				if( $dt ){
					$start = $dt->getTimestamp();
				}else{
					$abort = true;
					$response['msg'] = 'Gaps invalid on line '.$line_count.': invalid start time found. Nothing updated.';
					break;
				}
				if( $gap[1] <> 'cut' && $gap[1] <> 'add' ){
					$abort = true;
					$response['msg'] = 'Gaps invalid on line '.$line_count.': must specify cut or add. Nothing updated.';
					break;
				}
				// $dt = new DateTime("1970-01-01 $gap[2]", new DateTimeZone('UTC'));
				$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', "1970-01-01 $gap[2]", new DateTimeZone( 'UTC' ) );
				if( $dt ){
					if( $gap[1] == 'add' ){
						$end = $start;
						$duration = $dt->getTimestamp();
						$secs_added = $secs_added + $duration;
					}else{
						$end = $dt->getTimestamp();
						if( $end > $start ){
							$duration = $end - $start;
						}else{
							$abort = true;
							$response['msg'] = 'End time invalid on line '.$line_count.': must be greater than start time. Nothing updated.';
							break;
						}
					}
				}else{
					$abort = true;
					$response['msg'] = 'Gaps invalid on line '.$line_count.': invalid duration found. Nothing updated.';
					break;
				}

				// $edited_start = $start - $cum_duration;

				$gaps_secs[] = array(
					'start' => $start,
					'add_cut' => $gap[1],
					'duration' => $duration,
					'end' => $end,
				);

				/*
				if( $gap[1] == 'cut' ){
					$cum_duration = $cum_duration + $duration;
				}else{
					$cum_duration = $cum_duration - $duration;
				}
				*/

			}
		}

		if( ! $abort ){

			$chat = file_get_contents( $_FILES['chat']['tmp_name'] );

			if( trim( $chat ) == '' ){
				$response['msg'] = 'Chat file apparently empty. Nothing updated.';
			}else{

				/* the file could look something like this:
				"00:28:56\tRomina Vella:\tHello\r\n\r\n00:29:14\tRomina Vella:\tYes\r\n\r\n00:29:20\tAngela King:\tloving it\r\n\r\nRelatable!\r\n\r\n01:00:56\tKiran Nazir:\tYes mine was a cheerleader when i set the goal for the weekend! So then i changed it to this afternoon 😄\r\n\r\n01:03:01\tVanessa Godden:\tOther people won't like it\r\n\r\nThanks\r\n\r\n"
				*/

				// we may not have a newline character yet
				if( $newline === false ){
					if( strpos( $chat, "\r\n" ) ){
						$newline = "\r\n";
					}elseif( strpos( $chat, "\n" ) ){
						$newline = "\n";
					}elseif( strpos( $chat, "\r" ) ){
						$newline = "\r";
					}else{
						// no newlines in it
					}
				}

				// let's split the file into lines based on the newline codes
				if( $newline === false ){
					$lines = array( $chat );
				}else{
					$lines = explode( $newline, $chat );
				}

				// now we tidy up each line
				$tidy_lines = array();
				$seconds = 0;
				$count_msgs = 0;
				$adjusted_time = '00:00:00';
				foreach ($lines as $line) {
					// ignore empty lines
					if( $line <> '' ){
						// split the line into segments
						// usually there are 3 segments but occasionally there is just one ... we'll limit it to 3
						$segments = explode( "\t", $line, 3 );
						if( isset( $segments[2] ) ){
							// is the first segment a time?
							$dt = new DateTime("1970-01-01 $segments[0]", new DateTimeZone('UTC'));
							if( $dt ){
								$seconds = $dt->getTimestamp();
								// if not it will inherit the time from the previous line
								// now we need to adjust seconds to remove gap time
								// what's the earliest possible time for this msg?
								$earliest = 0;
								$cum_adds = 0;
								$cum_gaps = 0;
								$new_vid_secs = $seconds;
								foreach ($gaps_secs as $gap_secs) {
									if( $new_vid_secs > $gap_secs['start'] ){
										$earliest = $gap_secs['start'] - $cum_gaps;
										if( $gap_secs['add_cut'] == 'add' ){
											$seconds = $seconds + $gap_secs['duration'];
											$new_vid_secs = $new_vid_secs + $gap_secs['duration'];
											$cum_adds = $cum_adds + $gap_secs['duration'];
										}else{
											$seconds = $seconds - $gap_secs['duration'];
											$cum_gaps = $cum_gaps + $gap_secs['duration'];
										}
									}
								}
								if( $seconds < $earliest ){
									$seconds = $earliest;
								}
								$dt->setTimestamp($seconds);
								$adjusted_time = $dt->format('H:i:s');
							}
							$tidy_lines[] = array(
								'secs' => $seconds,
								'time' => $adjusted_time,
								'who' => $segments[1],
								'msg' => $segments[2],
							);
						}else{
							$tidy_lines[] = array(
								'secs' => $seconds,
								'time' => '',
								'who' => '',
								'msg' => $line,
							);
						}
						$count_msgs ++;
					}
				}

				// and if there's a second chat file, we repeat most of the above ... but adjusting the times
				if( $second_file ){
					$chat = file_get_contents( $_FILES['chat2']['tmp_name'] );
					if( $newline === false ){
						$lines = array( $chat );
					}else{
						$lines = explode( $newline, $chat );
					}
					foreach ($lines as $line) {
						if( $line <> '' ){
							$segments = explode( "\t", $line, 3 );
							if( isset( $segments[2] ) ){
								$displayed_time = $segments[0];
								$dt = new DateTime("1970-01-01 $segments[0]", new DateTimeZone('UTC'));
								if( $dt ){
									// for the second chat file we add on the length of the first video
									$seconds = $dt->getTimestamp() + $uncut_vid1;
									$earliest = 0;
									foreach ($gaps_secs as $gap_secs) {
										if( $seconds > $gap_secs['start'] ){
											$earliest = $gap_secs['start'];
											if( $gap_secs['add_cut'] == 'cut' ){
												$seconds = $seconds - $gap_secs['duration'];
											}else{
												$seconds = $seconds + $gap_secs['duration'];
											}
										}
									}
									if( $seconds < $earliest ){
										$seconds = $earliest;
									}
									// we're also going to adjust the displayed time to add on the length of the first video
									$dt->modify('+'.$uncut_vid1.' seconds');
									$displayed_time = $dt->format('H:i:s');
								}
								$tidy_lines[] = array(
									'secs' => $seconds,
									'time' => $displayed_time,
									'who' => $segments[1],
									'msg' => $segments[2],
								);
							}else{
								$tidy_lines[] = array(
									'secs' => $seconds,
									'time' => '',
									'who' => '',
									'msg' => $line,
								);
							}
							$count_msgs ++;
						}
					}

				}

				// and we store it
				$zoom_chat = array(
					'section_id' => $section_id,
					'chat' => maybe_serialize( $tidy_lines ),
					'gaps' => maybe_serialize( $gaps_secs ),
					'raw_gaps' => $gaps,
				);
				$zoom_chat_id = courses_zoom_chat_update( $zoom_chat );

				// $response['saved_chat'] = print_r( cc_zoom_chat_get( $course_id, $section_id ), true );

				$response['status'] = 'ok';
				$response['msg'] = $count_msgs.' chat messages uploaded';

			}

		}

	}else{
		$response['msg'] = 'Upload failed!';
	}

	echo json_encode($response);
	die();
}

add_action('rest_api_init', function () {
    register_rest_route('cc/v1', '/chat-messages/(?P<section_id>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'ptd_get_chat_messages',
        'permission_callback' => '__return_true', // publicly accessible
    ]);
});

function ptd_get_chat_messages($request) {
    $section_id = sanitize_text_field($request['section_id']);

    $zoom_chat = courses_zoom_chat_get( $section_id );
    $chats = maybe_unserialize( $zoom_chat['chat'] );

    $messages = array();

    $chat_num = 0;
    foreach ($chats as $chat) {
    	$messages[] = array(
    		'chat_num' => $chat_num,
    		'secs' => $chat['secs'],
    		'time' => $chat['time'],
    		'who' => $chat['who'],
    		'msg' => $chat['msg'],
    	);
    	$chat_num ++;
    }

    return rest_ensure_response($messages);
}
