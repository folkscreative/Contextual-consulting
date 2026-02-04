<?php
/**
 * Resources
 * used by workshops and recordings
 * big upgrade Mar 2025:
 * - new resources table created
 * - used for the new course post_type
 * - workshops still using the post meta
 */

add_action('init', 'resources_table_db_update');
function resources_table_db_update(){
	global $wpdb;
	$resources_table_ver = 1;
	$installed_table_ver = get_option('resources_table_ver');
	if($installed_table_ver <> $resources_table_ver){
		$table_name = $wpdb->prefix.'course_resources';
		$modules_table = $wpdb->prefix.'course_modules';
		$sections_table = $wpdb->prefix.'course_sections';
		// only one of course_id, module_id or section_id will be used
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS $table_name (
			    id 				BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			    course_id 		BIGINT UNSIGNED NULL,
			    module_id 		BIGINT UNSIGNED NULL,
			    section_id 		BIGINT UNSIGNED NULL,
			    resource_name 	VARCHAR(255) NOT NULL,
			    resource_url 	VARCHAR(500) NOT NULL,
			    created_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			    FOREIGN KEY (course_id) REFERENCES $wpdb->posts(ID) ON DELETE CASCADE,
			    FOREIGN KEY (module_id) REFERENCES $modules_table(id) ON DELETE CASCADE,
			    FOREIGN KEY (section_id) REFERENCES $sections_table(id) ON DELETE CASCADE
			) ENGINE = InnoDB 
			DEFAULT CHARSET = utf8mb4 
			COLLATE = utf8mb4_unicode_520_ci"
		);
		update_option('resources_table_ver', $resources_table_ver);
	}
}

// expects an array of id (update only), course_id, module_id, section_id, resource_name, resource_url
function resources_db_update( $resource ){
	global $wpdb;
	$table_name = $wpdb->prefix.'course_resources';
	$format = array( '%d', '%d', '%d', '%s', '%s' );
	if( isset( $resource['id'] ) && $resource['id'] > 0 ){
		$resource_id = $resource['id'];
		unset( $resource['id'] );
		$where = array(
			'id' => $resource_id,
		);
		$result = $wpdb->update( $table_name, $resource, $where, $format );
		return $resource_id;
	}else{
		unset( $resource['id'] );
		$result = $wpdb->insert( $table_name, $resource, $format );
		return $wpdb->insert_id;
	}
}



// returns the html for the workshop and recording metaboxes
// including workshop events (not used) or recording modules (called events here!)
function resources_metabox_fields($post_id, $event_id=NULL, $table=true){
	// ccpa_write_log('resources_metabox_fields');
	if($event_id === NULL){
		// ccpa_write_log('event is null');
		$files = get_post_meta($post_id, '_resource_files', true);
		$wrap_id = 'resource-files-wrap-'.$post_id;
		$data_event = '';
		$empty_key = '##key##';
	}else{
		// ccpa_write_log('event is:'.$event_id);
		$files = get_post_meta($post_id, '_event_'.$event_id.'_resource_files', true);
		$wrap_id = 'resource-files-wrap-'.$post_id.'-'.$event_id;
		$data_event = $event_id;
		$empty_key = 'event-'.$event_id.'-##key##';
	}
	if($files == '') $files = array();
	// ccpa_write_log($files);
	if($table){
		$html = '
	        <tr valign="top">
	            <th class="metabox_label_column">
	            	<strong>Files folder</strong>
	        	</th>
	            <td>
	            	<div id="'.$wrap_id.'" class="resource-files">
	            		<div class="resource-files-row" data-row="0">
			            	<span class="resource-files-label resource-file-name">File name</span>
			            	<span class="resource-files-label resource-file-url">File URL</span>
		            	</div>';
    	$data_type = 'table';
    }else{
    	$html = '
	        	<div id="'.$wrap_id.'" class="resource-files">
	        		<div class="row" data-row="0">
	        			<div class="col-6">
	        				<label class="form-label">File name</label>
	        			</div>
		            	<div class="col-6">
		            		<label class="form-label">File URL</label>
		            	</div>
	            	</div>';
    	$data_type = 'div';
    }
	$high_key = 0;
    if(count($files) == 0){
		if($event_id === NULL){
			$html .= resources_metabox_field(0, array(), $table);
		}else{
			$html .= resources_metabox_field('event-'.$event_id.'-0', array(), $table);
		}
    }else{
		foreach ($files as $key => $values) {
			if($event_id === NULL){
				$input_key = $key;
			}else{
				$input_key = 'event-'.$event_id.'-'.$key;
			}
			$html .= resources_metabox_field($input_key, $values, $table);
			if($key > $high_key){
				$high_key = $key;
			}
		}
    }
    $html .= '
		    	</div><!-- .resource-files -->
		        <div class="resource-files-add-wrap">
	            	<a href="javascript:void(0)" class="resource-files-add button button-primary" data-type="'.$data_type.'" data-wrap="'.$wrap_id.'" data-event="'.$data_event.'" data-highkey="'.$high_key.'">Add row</a>
		        </div>';
    // add a hidden empty row
    if($table){
	    $html .= '
				<div class="resource-files-empty">
			        <div class="resource-file-row" data-row="'.$empty_key.'">
		            	<input type="text" id="resource-file-name-'.$empty_key.'" name="resource-file-name-'.$empty_key.'" class="resource-file-name" value="">
		            	<input type="text" id="resource-file-url-'.$empty_key.'" name="resource-file-url-'.$empty_key.'" class="resource-file-url" value="">
			        </div>
		        </div><!-- .resource-files-empty -->';
	    $html .= '
            </td>
        </tr>';
    }else{
    	$html .= '
    			<div class="resource-files-empty">
    				<div class="row" data-row="'.$empty_key.'">
    					<div class="col-6">
			            	<input type="text" id="resource-file-name-'.$empty_key.'" name="resource-file-name-'.$empty_key.'" class="form-control" value="">
    					</div>
    					<div class="col-6">
			            	<input type="text" id="resource-file-url-'.$empty_key.'" name="resource-file-url-'.$empty_key.'" class="form-control" value="">
    					</div>
    				</div>
    			</div><!-- .resource-files-empty -->';
    }
	return $html;
}

// the html for a single field
function resources_metabox_field($key, $values, $table){
	// ccpa_write_log('resources_metabox_field');
	// ccpa_write_log('key:'.$key);
	// ccpa_write_log('values:');
	// ccpa_write_log($values);
	if(isset($values['file_name'])){
		$file_name = $values['file_name'];
	}else{
		$file_name = '';
	}
	if(isset($values['file_url'])){
		$file_url = $values['file_url'];
	}else{
		$file_url = '';
	}
	if($table){
		return '
	        <div class="resource-files-row" data-row="'.$key.'">
	        	<input type="text" id="resource-file-name-'.$key.'" name="resource-file-name-'.$key.'" class="resource-file-name" value="'.$file_name.'">
	        	<input type="text" id="resource-file-url-'.$key.'" name="resource-file-url-'.$key.'" class="resource-file-url" value="'.$file_url.'">
	        </div>';
    }else{
    	return '
	        <div class="row" data-row="'.$key.'">
	        	<div class="col-6">
	        		<input type="text" id="resource-file-name-'.$key.'" name="resource-file-name-'.$key.'" class="form-control" value="'.$file_name.'">
	        	</div>
	        	<div class="col-6">
	        		<input type="text" id="resource-file-url-'.$key.'" name="resource-file-url-'.$key.'" class="form-control" value="'.$file_url.'">
	        	</div>
	        </div>';
    }
}

// saves the updated resource files folder content
function resources_save_folder($post_id){
	// ccpa_write_log('resources_save_folder: '.$post_id);
	$resource_files = array();
	$event_resource_files = array();
	$do_update = false;
	// ccpa_write_log($_POST);
	foreach ($_POST as $key => $value) {
		if(substr($key, 0, 19) == 'resource-file-name-'){
			$do_update = true;
			if( substr($key, 19, 6) == 'event-' ){
				list($event_id, $file_key) = explode('-', substr($key, 25), 2);
				$event_resource_files[$event_id][$file_key]['file_name'] = stripslashes( sanitize_text_field( $value ) );
			}else{
				$file_key = substr($key, 19);
				$resource_files[$file_key]['file_name'] = stripslashes( sanitize_text_field( $value ) );
			}
		}elseif(substr($key, 0, 18) == 'resource-file-url-'){
			$do_update = true;
			if( substr($key, 18, 6) == 'event-' ){
				list($event_id, $file_key) = explode('-', substr($key, 24), 2);
				$event_resource_files[$event_id][$file_key]['file_url'] = stripslashes( sanitize_text_field( $value ) );
			}else{
				$file_key = substr($key, 18);
				$resource_files[$file_key]['file_url'] = esc_url( $value );
			}
		}
	}

	if( $do_update ){
		// ccpa_write_log($resource_files);
		// ccpa_write_log($event_resource_files);
		$resource_folder = array();
		foreach ($resource_files as $key => $value) {
			if(isset($value['file_url']) && $value['file_url'] <> ''){
				$resource_folder[] = array(
					'file_name' => isset($value['file_name']) ? $value['file_name'] : '',
					'file_url' => $value['file_url'],
				);
			}
		}
		// ccpa_write_log($resource_folder);
		update_post_meta($post_id, '_resource_files', $resource_folder);

		$resource_folder = array();
		foreach ($event_resource_files as $event_id => $values) {
			foreach ($values as $value) {
				if(isset($value['file_url']) && $value['file_url'] <> ''){
					$resource_folder[$event_id][] = array(
						'file_name' => isset($value['file_name']) ? $value['file_name'] : '',
						'file_url' => $value['file_url'],
					);
				}
			}
		}
		$events_set = array();
		// ccpa_write_log($resource_folder);
		foreach ($resource_folder as $event_id => $values) {
			update_post_meta($post_id, '_event_'.$event_id.'_resource_files', $values);
			$events_set[] = $event_id;
		}
		// clean up the empties
		// ccpa_write_log($events_set);
		for ($i=0; $i < 10; $i++) { 
			if(!in_array($i, $events_set)){
				delete_post_meta($post_id, '_event_'.$i.'_resource_files');
				// ccpa_write_log('_event_'.$i.'_resource_files deleted');
			}
		}
	}
}

// any training resources?
// event_id can be null (only interested in the main training), numeric (interested in resources for that event) or "any" (interested in main training or any event)
function resources_exist( $training_id, $event_id=NULL ){
	// try the new ones first
	if( $event_id === NULL || $event_id == 'any' ){
		$files = get_post_meta($training_id, '_resource_files', true);
	}else{
		$files = get_post_meta($training_id, '_event_'.$event_id.'_resource_files', true);
	}
	if($files == '') $files = array();
	if( ! empty( $files ) ) return true;
	if( $event_id == 'any' ){
		for ($i=0; $i < 10; $i++) { 
			$files = get_post_meta($training_id, '_event_'.$i.'_resource_files', true);
			if($files == '') $files = array();
			if( ! empty( $files ) ) return true;
		}
	}
	// now try the older fields ...
    if($event_id === NULL || $event_id == 'any'){
        $meta_key = 'workshop'; // yes, even for recordings!
    }else{
        $meta_key = 'event_'.$event_id;
    }
    $slides = get_post_meta($training_id, $meta_key.'_slides', true);
    $resources = get_post_meta($training_id, $meta_key.'_resources', true);
    if( $slides <> '' || $resources <> '' ){
        return true;
    }
    if( $event_id == 'any' ){
    	for ($i=0; $i < 10; $i++) {
		    $slides = get_post_meta($training_id, 'event_'.$i.'_slides', true);
		    $resources = get_post_meta($training_id, 'event_'.$i.'_resources', true);
		    if( $slides <> '' || $resources <> '' ){
		        return true;
		    }
    	}
    }
    return false;
}


// returns the html to display resources on the My Accounts pages
// don't use for post type of 'course'!
function resources_show_list( $training_id, $event_id=NULL, $show_click_msg=true, $columns=false){
	// start with the new format stuff
	if( $event_id === NULL ){
		$files = get_post_meta($training_id, '_resource_files', true);
	}else{
		$files = get_post_meta($training_id, '_event_'.$event_id.'_resource_files', true);
	}
	if($files == '') $files = array();

	// now add any of the old format
    if($event_id === NULL){
        $meta_key = 'workshop';
    }else{
        $meta_key = 'event_'.$event_id;
    }

    $slides = get_post_meta( $training_id, $meta_key.'_slides', true ); // url
    $resources = get_post_meta( $training_id, $meta_key.'_resources', true ); // url

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

	if(empty($files)) return '';

	return resources_modal_html( $files, $columns, false );
}

// the resources html
function resources_modal_html( $files, $columns = false, $course=true ){
	static $show_click_msg = true;
	
	if( $course ){
		$resource_name_key = 'resource_name';
		$resource_url_key = 'resource_url';
	}else{
		$resource_name_key = 'file_name';
		$resource_url_key = 'file_url';
	}
	
	$html = '';
	if($show_click_msg){
		$html .= '<p>Click the resource name to open/download it.</p>';
	}
	if( $columns ){
		$html .= '<div class="resource-files-wrap row">';
	}else{
		$html .= '<div class="resource-files-wrap">';
	}	

	// Get the uploads directory info
    $upload_dir = wp_get_upload_dir();

	foreach ($files as $key => $file) {
		if( $columns ){
			$html .= '<div class="resource-file-row col-lg-4">';
		}else{
			$html .= '<div class="resource-file-row">';
		}

		// Check if the URL starts with the base uploads URL
	    if ( strpos( $file[$resource_url_key], $upload_dir['baseurl'] ) === 0 ) {
	        // It's in the media library
	        $file_icon = resource_file_icon( $file[$resource_url_key] );
	    }else{
	    	$file_icon = resource_non_media_icon( $file[$resource_url_key] );
	    }

		$html .= '<div class="resource-file-name-wrap">'.$file_icon;
		if(!isset($file[$resource_name_key]) || $file[$resource_name_key] == ''){
			$file_name = $file[$resource_url_key];
		}else{
			$file_name = $file[$resource_name_key];
		}
		if( $file[$resource_url_key] == '' ){
			$html .= '<span class="training-link">'.$file_name.'</span>';
		}else{
			$html .= '<a href="'.esc_url( $file[$resource_url_key] ).'" target="_blank" class="training-link">'.$file_name.'</a>';
		}
		$html .= '</div>';
		$html .= '</div>';
	}
	$html .= '</div>';
	$show_click_msg = false;
	return $html;
}


// returns a resource file icon
function resource_file_icon( $file_url, $xclass='' ){
	$file_type = strtolower( substr($file_url, strrpos($file_url, '.') + 1 ) );
	switch ($file_type) {
		case 'pdf':
			$icon = '<i class="fa-solid fa-file-pdf fa-fw '.$xclass.'"></i>';
			break;
		case 'doc':
		case 'docx':
			$icon = '<i class="fa-solid fa-file-word fa-fw '.$xclass.'"></i>';
			break;
		case 'xlsx':
		case 'xls':
		case 'csv':
			$icon = '<i class="fa-solid fa-file-excel fa-fw '.$xclass.'"></i>';
			break;
		case 'ppt':
		case 'pptx':
			$icon = '<i class="fa-solid fa-file-powerpoint fa-fw '.$xclass.'"></i>';
			break;
		case 'jpg':
		case 'jpeg':
		case 'png':
		case 'gif':
		case 'ico':
			$icon = '<i class="fa-solid fa-file-image fa-fw '.$xclass.'"></i>';
			break;
		case 'mp4':
		case 'm4a':
		case 'mov':
		case 'wmv':
		case 'avi':
			$icon = '<i class="fa-solid fa-file-video fa-fw '.$xclass.'"></i>';
			break;		
		case 'mp3':
		case 'wav':
			$icon = '<i class="fa-solid fa-file-audio fa-fw '.$xclass.'"></i>';
			break;		
		default:
			$icon = '<i class="fa-solid fa-file fa-fw '.$xclass.'"></i>';
			break;
	}
	return $icon;
}

// returns an icon for resources that are not in the media library
function resource_non_media_icon( $file_url, $xclass='' ){
	if( substr( $file_url, 0, 17 ) == 'https://youtu.be/' || 
		substr( $file_url, 0, 24 ) == 'https://www.youtube.com/'
		){
		return '<i class="fa-brands fa-youtube fa-fw '.$xclass.'"></i>';
	}
	if( substr( $file_url, 0, 18 ) == 'https://vimeo.com/' ){
		return '<i class="fa-brands fa-vimeo-v fa-fw '.$xclass.'"></i>';
	}
	if( substr( $file_url, 0, 26 ) == 'https://www.instagram.com/' ){
		return '<i class="fa-brands fa-instagram fa-fw '.$xclass.'"></i>';
	}

	$site_url = site_url(); // does not include the trailing slash
	$site_url_length = strlen( $site_url );
	if( substr( $file_url, 0, $site_url_length ) == $site_url ){
		// $site_page = substr( $file_url, $site_url_length ); // starts with a slash (usually!)
		// but, for now, we'll just use one icon for all CC files ...
		return '<img src="'.get_stylesheet_directory_uri().'/images/cc_logo.svg" alt="cc logo" class="cc-logo '.$xclass.'">';
	}

	return '<i class="fa-solid fa-globe fa-fw '.$xclass.'"></i>';
}

/**
 * resources modal stuff for the training course edit pages
 */

// the resources button for the course edit page
// $type must be 'course', 'module' or 'section'
function resources_edit_button( $type, $id ){
	$results = resources_get( $type, $id );
	if( empty( $results ) ){
		$xclass = 'empty';
	}else{
		$xclass = 'full';
		foreach ($results as $row) {
			if( $row['resource_url'] == '' ){
				$xclass = 'error';
				break;
			}
		}
	}
	return '<button class="edit-resources button button-sml '.$xclass.'" data-type="'.$type.'" data-id="'.$id.'" title="Resources"><i class="fa-solid fa-list-ul"></i></button>';          
}

// get all resources
// always returns an array
function resources_get( $type, $id ){
	global $wpdb;
    $where = '';
    if ($type === 'course') $where = "course_id = $id";
    elseif ($type === 'module') $where = "module_id = $id";
    elseif ($type === 'section') $where = "section_id = $id";
    return $wpdb->get_results("SELECT id, resource_name, resource_url FROM {$wpdb->prefix}course_resources WHERE $where", ARRAY_A);
}

// do resources exist for this training course
// returns bool
function resources_exist_training_course( $training_id ){
	$course = course_get_all( $training_id );
	if( count( $course['resources'] ) > 0 ) return true;
	foreach ($course['modules'] as $module) {
		if( count( $module['resources'] ) > 0 ) return true;
		foreach ($module['sections'] as $section) {
			if( count( $section['resources'] ) > 0 ) return true;
		}
	}
	return false;
}




add_action('wp_ajax_get_resources', 'rpm_get_resources');
function rpm_get_resources() {
    // check_ajax_referer('rpm_resource_nonce');

    $type = sanitize_text_field($_POST['type']);
    $id = intval($_POST['id']);

	$results = resources_get( $type, $id );

    wp_send_json_success($results);
}

add_action('wp_ajax_save_resources', 'rpm_save_resources');
function rpm_save_resources() {
    // check_ajax_referer('rpm_resource_nonce');
    global $wpdb;

    $type = sanitize_text_field($_POST['type']);
    $id = intval($_POST['id']);
    $resources = $_POST['resources'];

    $table = $wpdb->prefix . 'course_resources';

    // Delete all existing
    if ($type === 'course') {
        $wpdb->delete($table, ['course_id' => $id]);
    } elseif ($type === 'module') {
        $wpdb->delete($table, ['module_id' => $id]);
    } elseif ($type === 'section') {
        $wpdb->delete($table, ['section_id' => $id]);
    }

    // Insert new
    foreach ($resources as $res) {
        $wpdb->insert($table, [
            'course_id' => $type === 'course' ? $id : null,
            'module_id' => $type === 'module' ? $id : null,
            'section_id' => $type === 'section' ? $id : null,
            'resource_name' => sanitize_text_field($res['name']),
            'resource_url' => esc_url_raw($res['url']),
        ]);
    }

    wp_send_json_success();
}

// converts a resource url into a link and an icon, either to download or open in a new window
function resource_icon_link( $url, $text, $link_classes="", $icon_classes="" ){
	$url = esc_url( $url );
	$url_type = classify_url( $url );
	switch ($url_type) {
		case 'invalid':
			return '';
			break;
		case 'media_library':
			$icon = resource_file_icon( $url, $icon_classes );
			return '<a href="'.$url.'" class="'.$link_classes.'" download>'.$icon.$text.'</a>';
			break;
		case 'internal':
			$icon = '<img src="'.get_stylesheet_directory_uri().'/images/cc_logo.svg" alt="cc logo" class="cc-logo">';
			return '<a href="'.$url.'" class="'.$link_classes.'" target="_blank">'.$icon.$text.'</a>';
			break;
		case 'external':
			$icon = resource_non_media_icon( $url, $icon_classes );
			return '<a href="'.$url.'" class="'.$link_classes.'" target="_blank">'.$icon.$text.'</a>';
			break;
	}
	return '';
}

function classify_url($url) {
    // Parse the URL safely
    $parsed_url = wp_parse_url($url);
    if (!$parsed_url || empty($parsed_url['host']) && empty($parsed_url['path'])) {
        return 'invalid';
    }

    // Get site base URL
    $site_url  = wp_parse_url(home_url());
    $upload_dir = wp_upload_dir();

    // Normalize and make lowercase
    $url_host = isset($parsed_url['host']) ? strtolower($parsed_url['host']) : '';
    $site_host = isset($site_url['host']) ? strtolower($site_url['host']) : '';

    $url_scheme = isset($parsed_url['scheme']) ? strtolower($parsed_url['scheme']) : '';
    $site_scheme = isset($site_url['scheme']) ? strtolower($site_url['scheme']) : '';

    // Construct full URL from relative if needed
    if (empty($url_host) && !empty($parsed_url['path'])) {
        // Treat as internal
        $absolute_url = home_url($parsed_url['path']);
    } else {
        $absolute_url = $url;
    }

    // Check if it's in the uploads directory (media library)
    if (strpos($absolute_url, $upload_dir['baseurl']) === 0) {
        return 'media_library';
    }

    // Check if it's internal (same host)
    if ($url_host === $site_host || empty($url_host)) {
        return 'internal';
    }

    // If not internal, and not media, it's external
    return 'external';
}
