<?php
/**
 * Course migration
 * one-off migration from recordings to Courses
 */

add_shortcode('recording_to_course_migration', function(){
	$html = 'recording_to_course_migration';
	// get all recordings
	$args = array(
		'post_type' => 'recording',
		'numberposts' => 5,
		'post_status' => 'any', // retrieves any status except for ‘inherit’, ‘trash’ and ‘auto-draft’.
	);
	$recordings = get_posts($args);

	foreach ($recordings as $recording) {
		/* 
		the following data does not need to change
			title (post_title)
			subtitle (meta)
			description (post_content)
			excerpt (post_excerpt)
			category (meta)
			recording_expiry_num (meta)
			recording_expiry_unit (meta)
			who_for aka audience (meta)
			_xero_tracking_code (meta)
			mailster_list aka newsletter list (meta)
			registration_message (meta)
			feedback_questions (meta)
			ce_credits (meta)
			cert_cc (meta)
            cert_apa (meta)
            cert_bacb (meta)
            cert_nbcc (meta)
            cert_icf (meta)
            workshop_joining (meta)
            workshop_recording (meta tag on the workshop that links to the recording)
            workshop_dates (meta)
            block_nlft (meta)
            block_cnwl (meta)

		the following data is dropped:
			availability (we'll use recording_exipy_num and recording_exipry_units instead)
			viewer_content (not used)
			workshop_feedback (not been used for a year)
			workshop_linkedin (never used!)
		*/

		$html .= '<br>Recording '.$recording->ID.' '.$recording->post_title;

		// course_type (new)
		update_post_meta( $recording->ID, '_course_type', 'on-demand' );

		// status (was recording_for_sale) aka available for purchase
		// can be '', 'closed', 'public', 'unlisted'
		update_post_meta( $recording->ID, '_course_status', get_post_meta( $recording->ID, 'recording_for_sale', true ) );

		// featured is a new field we do not need to set up

		// timing (was duration for recordings and prettydates for workshops)
		update_post_meta( $recording->ID, '_course_timing', get_post_meta( $recording->ID, 'duration', true ) );

		// and we need to change the post type
		set_post_type( $recording->ID, 'course' );

		/* links to categories and tags will remain unchanged
			Issues, Approaches, Resource types, Others, Training levels, FAQs, Presenters, Accordions, upsells, feedback questions, quiz
		*/

		// newsletter card is a new field that we should set up
		$new_card_html = cc_tnc_create_card_html($recording->ID);
		$current_card_html = get_post_meta($recording->ID, '_news_card_html', true);
		if($new_card_html <> $current_card_html){
			$file_name = cc_tnc_convert_card_to_png($new_card_html);
			if($file_name !== false){
				update_post_meta($recording->ID, '_news_card_img', $file_name);
				update_post_meta($recording->ID, '_news_card_html', $new_card_html);
			}
		}

		// copy resources to the new table
		$files = get_post_meta($recording->ID, '_resource_files', true);
		if($files == '') $files = array();
		// now add any of the old format
	    $slides = get_post_meta( $recording->ID, 'workshop_slides', true ); // url
	    $resources = get_post_meta( $recording->ID, 'workshop_resources', true ); // url
	    if( $slides <> '' ){
	    	$files[] = array(
	    		'file_name' => 'Slides',
	    		'file_url' => $slides,
	    	);
	    }
	    if( $resources <> '' ){
	    	$files[] = array(
	    		'file_name' => 'Resources',
	    		'file_url' => $resources,
	    	);
	    }
	    foreach ($files as $file) {
	    	$resource = array(
	    		'course_id' => $recording->ID,
	    		'module_id' => NULL,
	    		'section_id' => NULL,
	    		'resource_name' => $file['file_name'],
	    		'resource_url' => $file['file_url'],
	    	);
	    	resources_db_update( $resource );
	    }

	    // new pricing
	    $pricing = array(
		    'course_id' => $recording->ID,
		    'pricing_type' => '',
		    'price_gbp' => (float) get_post_meta($recording->ID, 'recording_price', true),
		    'price_usd' => (float) get_post_meta($recording->ID, 'recording_price_usd', true),
		    'price_eur' => (float) get_post_meta($recording->ID, 'recording_price_eur', true),
		    'price_aud' => (float) get_post_meta($recording->ID, 'recording_price_aud', true),
		    'student_discount' => (float) get_post_meta($recording->ID, 'student_discount', true),
		    'early_bird_discount' => (float) get_post_meta($recording->ID, 'earlybird_discount', true),
		    'early_bird_expiry' => null,
		    'early_bird_name' => get_post_meta($recording->ID, 'earlybird_name', true),
	    );
	    $earlybird_ddmmyy = get_post_meta($recording->ID, 'earlybird_expiry_date', true); // dd/mm/yyyy
	    if( $earlybird_ddmmyy <> '' ){
	    	$datetime = DateTime::createFromFormat("d/m/Y H:i:s", $earlybird_ddmmyy.' 23:59:59', new DateTimeZone('UTC'));
	    	if($datetime){
	    		$pricing['early_bird_expiry'] = $datetime->format('Y-m-d H:i:s');
	    	}
	    }
	    $pricing_id = course_pricing_db_update( $pricing );

	    // we may have stuff at the recording level that needs to become the first module/section
		$module_sequence = 0;

		$reg_link_id = get_post_meta($recording->ID, 'registration_link_id', true); // OLD: Recording ID (Oli's Recordings)
		$reg_link_url = get_post_meta($recording->ID, 'registration_link', true); // OLD: Recording/registration URL
		$rec_url = get_post_meta($recording->ID, 'recording_url', true); // OLD: The URL of the recording (the mp4 file)
		$vimeo_id = get_post_meta($recording->ID, 'vimeo_id', true);
		$zoom_chat = cc_zoom_chat_get( $recording->ID, 9999 );
		if( $reg_link_id <> '' || $reg_link_url <> '' || $rec_url <> '' || $vimeo_id <> '' || $zoom_chat !== NULL ){
			// set up a module
			$module = array(
				'course_id' => $recording->ID,
				'position' => $module_sequence,
				'title' => 'Introduction',
				'description' => '',
			);
			$module_id = course_module_db_update( $module );

			// then set up the section
			if( $vimeo_id <> '' ){
				$recording_type = 'vimeo';
				$recording_id = $vimeo_id;
			}elseif( $rec_url <> '' ){
				$recording_type = 'rec_url';
				$recording_id = $rec_url;
			}elseif( $reg_link_url <> '' ){
				$recording_type = 'reg_url';
				$recording_id = $reg_link_url;
			}elseif( $reg_link_id <> '' ){
				$recording_type = 'reg_id';
				$recording_id = $reg_link_id;
			}

			$section = array(
				'module_id' => $module_id,
				'position' => 0,
				'title' => 'Introduction',
				'description' => '',
				'start_time' => NULL,
				'end_time' => NULL,
				'recording_type' => $recording_type,
				'recording_id' => $recording_id,
			);
			$section_id = course_section_db_update( $section );

			if( $zoom_chat !== NULL ){
				// migrate to the new table
				$zoom_chat = array(
				    'section_id' => $section_id,
				    'chat' => $zoom_chat['chat'],
				    'gaps' => $zoom_chat['gaps'],
				    'raw_gaps' => $zoom_chat['raw_gaps'],
				);
				$zoom_chat_id = courses_zoom_chat_update( $zoom_chat );
			}

			$module_sequence ++;
		}

		// now let's set up the modules
		for ($i=0; $i < 10; $i++) { 
			$module_name = get_post_meta($recording->ID, 'module_name_'.$i, true);
			if( $module_name <> '' ){
				$module = array(
					'course_id' => $recording->ID,
					'position' => $module_sequence,
					'title' => $module_name,
					'description' => '',
				);
				$module_id = course_module_db_update( $module );

				// now a section for this module (we only need one)
				$vimeo_id = get_post_meta( $recording->ID, 'module_vimeo_'.$i, true );
				if( $vimeo_id <> '' ){
					$recording_type = 'vimeo';
					$recording_id = $vimeo_id;
				}else{
					$recording_type = '';
					$recording_id = '';
				}
				$section = array(
					'module_id' => $module_id,
					'position' => 0,
					'title' => $module_name,
					'description' => '',
					'start_time' => NULL,
					'end_time' => NULL,
					'recording_type' => $recording_type,
					'recording_id' => $recording_id,
				);
				$section_id = course_section_db_update( $section );

				// and copy the zoom chat
				$zoom_chat = cc_zoom_chat_get( $recording->ID, $i );
				if( $zoom_chat !== NULL ){
					// copy to the new table
					$zoom_chat = array(
					    'section_id' => $section_id,
					    'chat' => $zoom_chat['chat'],
					    'gaps' => $zoom_chat['gaps'],
					    'raw_gaps' => $zoom_chat['raw_gaps'],
					);
					$zoom_chat_id = courses_zoom_chat_update( $zoom_chat );
				}

				// copy resources to the new table
				$files = get_post_meta($recording->ID, '_event_'.$i.'_resource_files', true);
				if($files == '') $files = array();
			    foreach ($files as $file) {
			    	$resource = array(
			    		'course_id' => NULL,
			    		'module_id' => NULL,
			    		'section_id' => $section_id,
			    		'resource_name' => $file['file_name'],
			    		'resource_url' => $file['file_url'],
			    	);
			    	resources_db_update( $resource );
			    }

				$module_sequence ++;
			}
		}
	}
	return $html;
});


// acquire the orignal date of a workshop given the ID of a course/recording
function course_migration_get_original_dates( $course_id ){
	$response = array(
		'start_time' => NULL,
		'end_time' => NULL,
		'pretty_dates' => '',
	);
	$workshop_dates = get_post_meta( $course_id, 'workshop_dates', true );
	if($workshop_dates <> ''){
		$response['pretty_dates'] = $workshop_dates;
	}

	// we need the workshop_id
	$workshop_id = recording_get_matching_workshop_id( $course_id );
	if( $workshop_id !== NULL ){
		$workshop_start_timestamp = get_post_meta($workshop_id, 'workshop_start_timestamp', true ); // time is rubbish



	}
	
    if($workshop_start_timestamp <> ''){
    	$workshop_start_date = date('Y-m-d', $workshop_start_timestamp);
    	if($workshop_start_date > date('Y-m-d')){
    		// workshop starting tomorrow or later
    		return true;
    	}
    }
    // workshop is "on" ... or at least some of the events are
    // are any of the events yet to start?
    for ($i=1; $i < 16; $i++){
    	$event_start_time = get_post_meta( $workshop_id, 'event_'.$i.'_time', true ); // H:i (UTC)
        if($event_start_time == ''){
            $event_start_time = '08:00';
        }
    	if($i == 1){
    		$event_start_date = get_post_meta($workshop_id, 'meta_a', true); // d/m/y
    	}else{
    		$event_start_date = get_post_meta($workshop_id, 'event_'.$i.'_date', true); // d/m/y
    	}
    	if($event_start_date <> ''){
	    	$datetime = DateTime::createFromFormat("d/m/Y H:i", $event_start_date.' '.$event_start_time, new DateTimeZone('UTC'));
	    	if($datetime){
	    		if($datetime->getTimestamp() > $now){
	    			// this event is yet to start
	    			return true;
	    		}
	    	}
	    }
    }

	$workshop_end = workshop_calculate_end_timestamp( $workshop_id );
}

add_shortcode('course_resources_migration', function(){
	$html = 'course_resources_migration';
	// get all recordings
	$args = array(
		'post_type' => 'course',
		'numberposts' => -1,
		'post_status' => 'any', // retrieves any status except for ‘inherit’, ‘trash’ and ‘auto-draft’.
	);
	$recordings = get_posts($args);

	$count_processed = 0;
	foreach ($recordings as $recording) {

		if( $count_processed > 0 ){
			break;
		}

		$processed = false;

		$html .= '<br>Recording '.$recording->ID.' '.$recording->post_title;

		if( resources_exist_training_course( $recording->ID ) ){
			$html .= ' <<< already has resources, skipping >>>';
			continue;
		}

		// copy resources to the new table
		$files = get_post_meta($recording->ID, '_resource_files', true);
		if($files == '') $files = array();
		// now add any of the old format
	    $slides = get_post_meta( $recording->ID, 'workshop_slides', true ); // url
	    $resources = get_post_meta( $recording->ID, 'workshop_resources', true ); // url
	    if( $slides <> '' ){
	    	$files[] = array(
	    		'file_name' => 'Slides',
	    		'file_url' => $slides,
	    	);
	    }
	    if( $resources <> '' ){
	    	$files[] = array(
	    		'file_name' => 'Resources',
	    		'file_url' => $resources,
	    	);
	    }
	    foreach ($files as $file) {
	    	$resource = array(
	    		'course_id' => $recording->ID,
	    		'module_id' => NULL,
	    		'section_id' => NULL,
	    		'resource_name' => $file['file_name'],
	    		'resource_url' => $file['file_url'],
	    	);
	    	resources_db_update( $resource );
	    	$html .= ' *';
	    	$processed = true;
	    }

	    // we need the new section_ids so we'll get the new course
	    $course = course_get_all( $recording->ID );

		// now let's set up the modules
		$module_count = $section_count = 0;
		foreach ($course['modules'] as $module) {
			// let's find the matching old module
			$found = false;
			for ($i=0; $i < 10; $i++){
				$module_name = get_post_meta($recording->ID, 'module_name_'.$i, true);
				if( $module['title'] == $module_name ){
					$found = $i;
					break;
				}
			}
			if( $found === false ){
				// are there likely to be any resources?
				for ($j=0; $j < 10; $j++){
					$files = get_post_meta($recording->ID, '_event_'.$j.'_resource_files', true);
					if($files == '') $files = array();
					if( count( $files ) > 0 ){
						$html .= ' <strong>###### looks like there are resource files here #####</strong>';
					}
				}
				$html .= ' <strong>!!!!!!!!!! module '.$module['title'].' not found !!!!!!!!!!</strong>';
				break;
			}
			// copy resources to the new table
			$files = get_post_meta($recording->ID, '_event_'.$found.'_resource_files', true);
			$section_id = $module['sections'][0]['id'];
			if($files == '') $files = array();
		    foreach ($files as $file) {
		    	$resource = array(
		    		'course_id' => NULL,
		    		'module_id' => NULL,
		    		'section_id' => $section_id,
		    		'resource_name' => $file['file_name'],
		    		'resource_url' => $file['file_url'],
		    	);
		    	resources_db_update( $resource );
		    	$html .= ' #';
		    	$processed = true;
		    }

		}

		if( $processed ){
			$count_processed ++;
			$html .= ' resources migrated!';
		}else{
			$html .= ' no resources found';
		}
	}
	return $html;

});

// fix the zoom chat migration
add_shortcode('courses_zoom_chat_migration', function(){
	global $wpdb;
	$html = 'courses_zoom_chat_migration';
	// get all old zoom chats
	// note there are no 9999 chats in the table
	$zoom_chat_table = $wpdb->prefix.'zoom_chat';
	$sql = "SELECT * FROM $zoom_chat_table ORDER BY training_id ASC, module ASC";
	$old_zooms = $wpdb->get_results( $sql, ARRAY_A );

	$count_modules = 0;
	$last_course_id = 0;
	$old_modules_list = '';
	foreach ($old_zooms as $old_zoom) {
		if( $old_zoom['training_id'] <> $last_course_id ){
			if( $last_course_id > 0 ){
			    if( $course['module_counts']['all_sections_count'] == $count_modules){
			    	// $html .= course_zoom_chat_migration_copy( $last_course_id );
			    }else{
				    $html .= '<br>'.$last_course_id.' modules: '.$course['module_counts']['module_count'].' sections: '.$course['module_counts']['all_sections_count'].' old zoom chats: '.$count_modules.' old modules list: '.$old_modules_list;
			    	$html .= course_zoom_chat_migration_copy( $last_course_id );
			    }
			    $count_modules = 0;
			    $old_modules_list = '';
			}
			$last_course_id = $old_zoom['training_id'];
		    $course = course_get_all( $old_zoom['training_id'] );
		}
		$old_modules_list .= $old_zoom['module'].' ';
		$count_modules ++;
	}
    if( $course['module_counts']['all_sections_count'] == $count_modules){
    	// $html .= course_zoom_chat_migration_copy( $last_course_id );
    }else{
	    $html .= '<br>'.$last_course_id.' modules: '.$course['module_counts']['module_count'].' sections: '.$course['module_counts']['all_sections_count'].' old zoom chats: '.$count_modules.' old modules list: '.$old_modules_list;
    	$html .= course_zoom_chat_migration_copy( $last_course_id );
    }
	$html .= '<br>done'	;
	return $html;
});

function course_zoom_chat_migration_copy( $course_id ){
	global $wpdb;
	$old_zoom_chat_table = $wpdb->prefix.'zoom_chat';
	$courses_zoom_chat_table = $wpdb->prefix.'courses_zoom_chat';
	$sql = "SELECT * FROM $old_zoom_chat_table WHERE training_id = $course_id ORDER BY module ASC";
	$old_zooms = $wpdb->get_results( $sql, ARRAY_A );

	$course = course_get_all( $course_id );

	$html = '<br>Section ids found: ';

	// $html .= print_r( $course['modules'], true );

	foreach ($old_zooms as $old_zoom) {
		$index = $old_zoom['module'];
		if( isset( $course['modules'][$index]['sections'][0]['id'] ) ){
			$section_id = $course['modules'][$index]['sections'][0]['id'];
			$html .= $section_id.' ';
			$sql = "SELECT * FROM $courses_zoom_chat_table WHERE section_id = $section_id LIMIT 1";
			$courses_zoom_chat = $wpdb->get_row( $sql, ARRAY_A );
			if( $courses_zoom_chat === NULL ){
				$html .= 'not found ';
				$copied_zoom_chat = array(
				    'section_id' => $section_id,
				    'chat' => $old_zoom['chat'],
				    'gaps' => $old_zoom['gaps'],
				    'raw_gaps' => $old_zoom['raw_gaps'],
				);
				$zoom_chat_id = courses_zoom_chat_update( $copied_zoom_chat );
				$html .= ' zoom chat copied';
			}else{
				$html .= '<strong style="color:red;">WAS found !!!</strong> ';
			}
		}else{
			$html .= '<strong style="color:red;">course[modules]['.$index.'][sections][0][id] not set</strong> ';
		}
	}
	return $html;
}




	/*

	// get all recordings
	$args = array(
		'post_type' => 'course',
		'numberposts' => -1,
		'post_status' => 'any', // retrieves any status except for ‘inherit’, ‘trash’ and ‘auto-draft’.
	);
	$recordings = get_posts($args);

	$count_processed = 0;
	foreach ($recordings as $recording) {

		if( $count_processed > 0 ){
			break;
		}

		$processed = false;

		$html .= '<br>Recording '.$recording->ID.' '.$recording->post_title;

		$zoom_chat = cc_zoom_chat_get( $recording->ID, 9999 );

	    // we need the new section_ids so we'll get the new course
	    $course = course_get_all( $recording->ID );

		if( $zoom_chat !== NULL ){
			// try to find the matching section

			foreach ($course['modules'] as $module) {
				// let's find the matching old module
				$found = false;
				for ($i=0; $i < 10; $i++){
					$module_name = get_post_meta($recording->ID, 'module_name_'.$i, true);
					if( $module['title'] == $module_name ){
						$found = $i;
						break;
					}
				}
				if( $found === false ){
					for ($i=0; $i < 10; $i++){
						$module_name = get_post_meta($recording->ID, 'module_name_'.$i, true);
						if( 'Introduction' == $module_name ){
							$found = $i;
							break;
						}
					}
				}
				if( $found === false){
					$html .= ' cannot find module '.$module['title'];
				}else{
					// section_id will come from the first section in the module
					$section_id = $module['sections'][0]['id'];
					// is it already set up?
					$new_zoom_chat = courses_zoom_chat_get( $section_id );
					if( $new_zoom_chat['chat'] == '' ){
						// copy to the new table
						$copied_zoom_chat = array(
						    'section_id' => $section_id,
						    'chat' => $zoom_chat['chat'],
						    'gaps' => $zoom_chat['gaps'],
						    'raw_gaps' => $zoom_chat['raw_gaps'],
						);
						$zoom_chat_id = courses_zoom_chat_update( $copied_zoom_chat );
						$html .= ' zoom chat copied';
					}else{
						$html .= ' zoom chat already set up';
					}
				}

			
		}
	*/
