<?php
/**
 * Viewing Stats
 * - from the training delivery page
 */

// Register the video stats endpoint
add_action('rest_api_init', function () {
    register_rest_route('cc/v1', '/video-stats', array(
        'methods' => 'POST',
        'callback' => 'handle_video_stats',
        'permission_callback' => function() {
            return current_user_can('read');
        }
    ));
    register_rest_route('cc/v1', '/video-progress', array(
        'methods' => 'POST',
        'callback' => 'save_video_progress',
        'permission_callback' => function() {
            return current_user_can('read');
        }
    ));
});

function handle_video_stats($request) {
    if (!function_exists('cc_video_tracking_log_success')) {
        function cc_video_tracking_log_success($a, $b = null) {}
        function cc_video_tracking_log_failure($a) {}
    }

    $data = $request->get_json_params();

    // Validate required fields
    $required = ['user_id', 'training_id', 'section_id', 'activity', 'playhead_time', 'viewing_time'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            cc_video_tracking_log_failure('missing_field');
            return new WP_Error('missing_field', "Missing required field: $field", array('status' => 400));
        }
    }
    
	if( cc_users_is_valid_user_logged_in() ){
		$curr_user_id = get_current_user_id();
		if( $curr_user_id <> $data['user_id'] ){
            cc_video_tracking_log_failure('user_invalid');
            return new WP_Error('user_invalid', "User id mismatch: $curr_user_id <> {$data['user_id']}", array('status' => 400));
		}
	}else{
        cc_video_tracking_log_failure('user_logged_out');
        return new WP_Error('user_logged_out', "User not logged in", array('status' => 400));
	}

	if( $data['training_id'] < 1 || get_post_type( $data['training_id'] ) <> 'course' ){
        cc_video_tracking_log_failure('invalid_training');
        return new WP_Error('invalid_training', "Invalid training ID: {$data['training_id']}", array('status' => 400));
	}

	$apply_update = false;

	$recording_meta = get_recording_meta( $data['user_id'], $data['training_id'], $data['section_id'] );

	if( $recording_meta ){

		// activity can be start, pause, reached_end, switch
		switch ( $data['activity'] ) {
			case 'start':
				// new video just started playing
				// add one to the number of views
				$recording_meta['num_views'] = $recording_meta['num_views'] + 1;
				$recording_meta['last_viewed'] = date('d/m/Y H:i:s');
				if( $recording_meta['first_viewed'] == '' ){
					$recording_meta['first_viewed'] = date('d/m/Y H:i:s');
				}
                $recording_meta['last_playhead'] = $data['playhead_time'];
				$apply_update = true;
				break;

			case 'pause':
				$recording_meta['last_viewed'] = date('d/m/Y H:i:s');
				if( $recording_meta['first_viewed'] == '' ){
					$recording_meta['first_viewed'] = date('d/m/Y H:i:s');
				}
				$recording_meta['last_playhead'] = $data['playhead_time'];
				$recording_meta['last_viewed_time'] = $data['viewing_time'];
				$recording_meta['viewing_time'] = $recording_meta['viewing_time'] + $data['viewing_time'];
				$apply_update = true;
				break;

			case 'reached_end':
				// a pause will have come through immediately before this
				if( $recording_meta['viewed_end'] == 'no' ){
					$recording_meta['viewed_end'] = 'yes';
					if( $recording_meta['last_playhead'] <> $data['playhead_time'] ){
						// pause hasn't been processed so we'll do it now
						$recording_meta['last_viewed'] = date('d/m/Y H:i:s');
						if( $recording_meta['first_viewed'] == '' ){
							$recording_meta['first_viewed'] = date('d/m/Y H:i:s');
						}
						$recording_meta['last_playhead'] = $data['playhead_time'];
						$recording_meta['last_viewed_time'] = $data['viewing_time'];
						$recording_meta['viewing_time'] = $recording_meta['viewing_time'] + $data['viewing_time'];
					}
					$apply_update = true;
				}
				break;

            case 'progress':
                // Periodic progress update while video is playing
                $recording_meta['last_viewed'] = date('d/m/Y H:i:s');
                if( $recording_meta['first_viewed'] == '' ){
                    $recording_meta['first_viewed'] = date('d/m/Y H:i:s');
                }
                $recording_meta['last_playhead'] = $data['playhead_time'];
                $recording_meta['last_viewed_time'] = $data['viewing_time'];
                $recording_meta['viewing_time'] = $data['viewing_time'];
                $apply_update = true;
                break;

			case 'switch':
				// not used
				break;
		}

        if( $recording_meta['num_views'] == 0 ){
            $recording_meta['num_views'] = 1;
            $apply_update = true;
        }

		if( $apply_update ){
			$result = update_user_meta( $data['user_id'], 'cc_rec_wshop_'.$data['training_id'].'_'.$data['section_id'], $recording_meta );
            if( $result === false ){
                cc_video_tracking_log_failure('meta_error');
            }

			// also maybe update the main (non-section) meta
            $result = update_training_recording_meta( $data['training_id'], $data['user_id'], $data['section_id'] );
            if( $result === false ){
                cc_video_tracking_log_failure('update_failed');
            }
		}

        courses_maybe_newsletter_sub( $data['user_id'], $data['training_id'], $data['section_id'] );

	}

    cc_video_tracking_log_success($activity, $request_id);

    return new WP_REST_Response(array('success' => true), 200);
}


/**
 * Update or create training meta for a user based on training and module data
 *
 * @param int $training_id The training course ID
 * @param int $user_id The user ID
 * @return bool True on success, false on failure
 */
function update_training_recording_meta($training_id, $user_id, $current_section_id = null) {
    // Validate inputs
    if (!is_numeric($training_id) || !is_numeric($user_id) || $training_id <= 0 || $user_id <= 0) {
        return false;
    }
    
    // Get all user meta keys that match our pattern
    $meta_pattern = 'cc_rec_wshop_' . $training_id;
    $all_user_meta = get_user_meta($user_id);
    
    if (!$all_user_meta) {
        return false;
    }
    
    // Find matching meta keys
    $matching_rows = array();
    foreach ($all_user_meta as $meta_key => $meta_values) {
        if (strpos($meta_key, $meta_pattern) === 0) {
            // Get the first value (WordPress stores meta as arrays)
            $meta_value = maybe_unserialize($meta_values[0]);
            $meta_value = sanitise_recording_meta( $meta_value, $user_id, $training_id );
            if (is_array($meta_value)) {
                $matching_rows[$meta_key] = $meta_value;
            }
        }
    }
    
    // If no matching rows found, create a new training row with default values
    if (empty($matching_rows)) {
        $current_time = current_time('mysql'); // WordPress current time
        $current_time_obj = DateTime::createFromFormat('Y-m-d H:i:s', $current_time);
        
        // Create closed_time as 6 months from now
        $closed_time_obj = clone $current_time_obj;
        $closed_time_obj->add(new DateInterval('P6M'));
        
        $new_meta_value = array(
            'access_time' => $current_time_obj->format('Y-m-d H:i:s'),
            'first_viewed' => $current_time_obj->format('d/m/Y H:i:s'),
            'last_viewed' => $current_time_obj->format('d/m/Y H:i:s'),
            'closed_time' => $closed_time_obj->format('Y-m-d H:i:s')
        );
        
        $training_meta_key = $meta_pattern;
        $result = update_user_meta($user_id, $training_meta_key, $new_meta_value);
        
        return $result !== false;
    }
    
    // Initialize tracking variables
    $earliest_access_time = null;
    $earliest_first_viewed = null;
    $latest_last_viewed = null;
    $latest_closed_time = null;
    
    // Process each row to find min/max values
    foreach ($matching_rows as $meta_key => $data) {
        // Process access_time (Y-m-d H:i:s format)
        if (isset($data['access_time']) && !empty($data['access_time'])) {
            $access_time = validate_datetime($data['access_time'], 'Y-m-d H:i:s');
            if ($access_time && (is_null($earliest_access_time) || $access_time < $earliest_access_time)) {
                $earliest_access_time = $access_time;
            }
        }
        
        // Process first_viewed (d/m/Y H:i:s format)
        if (isset($data['first_viewed']) && !empty($data['first_viewed'])) {
            $first_viewed = validate_datetime($data['first_viewed'], 'd/m/Y H:i:s');
            if ($first_viewed && (is_null($earliest_first_viewed) || $first_viewed < $earliest_first_viewed)) {
                $earliest_first_viewed = $first_viewed;
            }
        }
        
        // Process last_viewed (d/m/Y H:i:s format)
        if (isset($data['last_viewed']) && !empty($data['last_viewed'])) {
            $last_viewed = validate_datetime($data['last_viewed'], 'd/m/Y H:i:s');
            if ($last_viewed && (is_null($latest_last_viewed) || $last_viewed > $latest_last_viewed)) {
                $latest_last_viewed = $last_viewed;
            }
        }
        
        // Process closed_time (Y-m-d H:i:s format)
        if (isset($data['closed_time']) && !empty($data['closed_time'])) {
            $closed_time = validate_datetime($data['closed_time'], 'Y-m-d H:i:s');
            if ($closed_time && (is_null($latest_closed_time) || $closed_time > $latest_closed_time)) {
                $latest_closed_time = $closed_time;
            }
        }
    }
    
    // Apply fallback logic for missing values
    $current_time = current_time('mysql'); // WordPress current time
    $current_time_obj = DateTime::createFromFormat('Y-m-d H:i:s', $current_time);
    
    // Handle access_time fallbacks
    if (is_null($earliest_access_time)) {
        if (!is_null($earliest_first_viewed)) {
            $earliest_access_time = DateTime::createFromFormat('d/m/Y H:i:s', $earliest_first_viewed->format('d/m/Y H:i:s'));
        } elseif (!is_null($latest_last_viewed)) {
            $earliest_access_time = DateTime::createFromFormat('d/m/Y H:i:s', $latest_last_viewed->format('d/m/Y H:i:s'));
        } else {
            $earliest_access_time = $current_time_obj;
        }
    }
    
    // Handle first_viewed fallbacks
    if (is_null($earliest_first_viewed)) {
        if (!is_null($latest_last_viewed)) {
            $earliest_first_viewed = $latest_last_viewed;
        } else {
            $earliest_first_viewed = $current_time_obj;
        }
    }
    
    // Handle last_viewed fallbacks
    if (is_null($latest_last_viewed)) {
        if (!is_null($earliest_first_viewed)) {
            $latest_last_viewed = $earliest_first_viewed;
        } else {
            $latest_last_viewed = $current_time_obj;
        }
    }
    
    // Handle closed_time fallbacks (6 months after access_time)
    if (is_null($latest_closed_time)) {
        $latest_closed_time = clone $earliest_access_time;
        $latest_closed_time->add(new DateInterval('P6M')); // Add 6 months
    }
    
    // Prepare the meta_value array
    $updated_meta_value = array(
        'access_time' => $earliest_access_time->format('Y-m-d H:i:s'),
        'first_viewed' => $earliest_first_viewed->format('d/m/Y H:i:s'),
        'last_viewed' => $latest_last_viewed->format('d/m/Y H:i:s'),
        'closed_time' => $latest_closed_time->format('Y-m-d H:i:s')
    );

    // Track the last viewed section if provided
    if ($current_section_id !== null) {
        $updated_meta_value['last_viewed_section'] = $current_section_id;
    }

    // Check if training level row exists
    $training_meta_key = $meta_pattern;
    $existing_training_meta = get_recording_meta( $user_id, $training_id, true );
    
    if ($existing_training_meta) {
        // Update existing training row
        $existing_data = maybe_unserialize($existing_training_meta);
        if (is_array($existing_data)) {
            // Merge with existing data, keeping our calculated values
            $updated_meta_value = array_merge($existing_data, $updated_meta_value);
        }
    }
    
    // Update or create the training meta
    $result = update_user_meta($user_id, $training_meta_key, $updated_meta_value);
    
    return $result !== false;
}

/**
 * Validate and parse datetime string
 *
 * @param string $datetime The datetime string to validate
 * @param string $format The expected format
 * @return DateTime|false DateTime object on success, false on failure
 */
function validate_datetime($datetime, $format) {
    if (empty($datetime) || !is_string($datetime)) {
        return false;
    }
    
    $date = DateTime::createFromFormat($format, $datetime);
    
    // Check if the date was parsed correctly and matches the original string
    if ($date && $date->format($format) === $datetime) {
        return $date;
    }
    
    return false;
}

function get_vimeo_recording_meta( $user_id, $training_id, $section_id=NULL ){
	$meta_key = 'cc_rec_wshop_'.$training_id;
	if( $section_id <> NULL ){
		$meta_key = '_'.$section_id;
	}

	// $recording_meta = get_user_meta( $user_id, $meta_key, true );
	$recording_meta = get_recording_meta( $user_id, $training_id, $section_id );

	if( is_array( $recording_meta ) ){
		return $recording_meta;
	}

	if( $section_id === NULL ){
		// should not happen
		return false;
	}

	// $recording_meta = get_user_meta( $user_id, 'cc_rec_wshop_'.$training_id, true );
	$recording_meta = get_recording_meta( $user_id, $training_id );

	if( ! is_array( $recording_meta ) ){
		// another error
		return false;
	}

	// build the section recording meta
	return array(
		'access_time' => '', // Y-m-d H:i:s
		'access_type' => '',
		'num_views' => 0, // so we can add one
		'first_viewed' => '', // d/m/Y H:i:s
		'last_viewed' => '', // d/m/Y H:i:s
		'last_viewed_time' => 0,
		'viewed_end' => 'no',
		'viewing_time' => 0,
		'closed_time' => '',
		'closed_type' => '',
		'currency' => '',
		'amount' => 0,
		'token' => '',
		'payment_id' => 0,
		'last_playhead' => 0,
	);
}

function save_video_progress($request) {
    $user_id = $request['user_id'];
    $training_id = $request['training_id'];
    $section_id = $request['section_id'];
    $position = $request['position'];
    
    // Update progress
    $recording_meta = get_vimeo_recording_meta( $user_id, $training_id, $section_id );
	if( $recording_meta ){
		$recording_meta['last_playhead'] = $position;
		update_user_meta( $user_id, 'cc_rec_wshop_'.$training_id.'_'.$section_id, $recording_meta );
	}

    return new WP_REST_Response(array('success' => true), 200);
}