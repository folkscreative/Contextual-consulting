<?php
/**
 * Courses - database stuff
 */

// register the courses CPT
add_action('init', 'courses_db_register_training_courses_cpt');
function courses_db_register_training_courses_cpt() {
    // Register the %course_type% rewrite tag
    add_rewrite_tag('%course_type%', '([^/]+)');
	
    $labels = array(
        'name'               => 'Training Courses',
        'singular_name'      => 'Training Course',
        'menu_name'          => 'Training Courses',
        'name_admin_bar'     => 'Training Course',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Training Course',
        'new_item'           => 'New Training Course',
        'edit_item'          => 'Edit Training Course',
        'view_item'          => 'View Training Course',
        'all_items'          => 'All Training Courses',
        'search_items'       => 'Search Training Courses',
        'not_found'          => 'No training courses found.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'supports'           => array('title', 'editor', 'excerpt', 'thumbnail'),
        'menu_position'      => 21,
        'menu_icon'          => 'dashicons-welcome-learn-more',
        'rewrite'            => array(
        	'slug' => 'training-course/%course_type%', // Placeholder to be replaced later
        	'with_front' => false,
        ),
        'query_var' => true,
    );

    register_post_type('course', $args);
}

// create/update the tables
// not using dbDelta as that does not accommodate foreign keys

// modules
add_action('init', 'courses_db_update_modules_table');
function courses_db_update_modules_table(){
	global $wpdb;
	$cc_modules_table_ver = 2;
	$installed_table_ver = (int) get_option('cc_modules_table_ver', 0);
	if( $installed_table_ver <> $cc_modules_table_ver ){
		$table_name = $wpdb->prefix.'course_modules';

		if( $installed_table_ver < 1 ){
			$wpdb->query(
				"CREATE TABLE IF NOT EXISTS $table_name (
				    id 				BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				    course_id 		BIGINT UNSIGNED NOT NULL,
				    position		INT NOT NULL DEFAULT 0,
				    title 			VARCHAR(255) NOT NULL,
				    description 	TEXT,
				    created_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				    updated_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				    FOREIGN KEY (course_id) REFERENCES $wpdb->posts(ID) ON DELETE CASCADE
				) ENGINE = InnoDB 
				DEFAULT CHARSET = utf8mb4 
				COLLATE = utf8mb4_unicode_520_ci"
			);
			update_option( 'cc_modules_table_ver', 1 );
		}

		if ( $installed_table_ver < 2 ) {
			// Schema upgrade for version 2: Add a new column, change field length, etc.
			// Example: add a new column `duration`
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN timing VARCHAR(100) DEFAULT '' AFTER title");

			// Example: change column length
			// $wpdb->query("ALTER TABLE $table_name MODIFY title VARCHAR(300)");

			update_option( 'cc_modules_table_ver', 2 );
		}
	}
}

// sections
add_action('init', 'courses_db_update_sections_table');
function courses_db_update_sections_table(){
	global $wpdb;
	$cc_sections_table_ver = 1;
	$installed_table_ver = get_option('cc_sections_table_ver');
	if($installed_table_ver <> $cc_sections_table_ver){
		$table_name = $wpdb->prefix.'course_sections';
		$modules_table = $wpdb->prefix.'course_modules';
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS $table_name (
			    id 				BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			    module_id 		BIGINT UNSIGNED NOT NULL,
			    position		INT NOT NULL DEFAULT 0,
			    title 			VARCHAR(255) NOT NULL,
			    description 	TEXT,
			    start_time 		DATETIME NULL,
			    end_time 		DATETIME NULL,
			    recording_type	VARCHAR(255),
			    recording_id 	VARCHAR(255),
			    created_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			    updated_at 		TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    FOREIGN KEY (module_id) REFERENCES $modules_table(ID) ON DELETE CASCADE
			) ENGINE = InnoDB 
			DEFAULT CHARSET = utf8mb4 
			COLLATE = utf8mb4_unicode_520_ci"
		);
		update_option('cc_sections_table_ver', $cc_sections_table_ver);
	}
}

// pricing
add_action('init', 'courses_db_update_pricing_table');
function courses_db_update_pricing_table(){
	global $wpdb;
	$cc_pricing_table_ver = 1;
	$installed_table_ver = get_option('cc_pricing_table_ver');
	if($installed_table_ver <> $cc_pricing_table_ver){
		$table_name = $wpdb->prefix.'course_pricing';
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS $table_name (
			    id 					BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			    course_id 			BIGINT UNSIGNED NOT NULL,
			    pricing_type		VARCHAR(255),
			    price_gbp 			DECIMAL(10,2) NOT NULL,
			    price_usd 			DECIMAL(10,2) NOT NULL,
			    price_eur 			DECIMAL(10,2) NOT NULL,
			    price_aud 			DECIMAL(10,2) NOT NULL,
			    student_discount 	DECIMAL(5,2) DEFAULT 0,
			    early_bird_discount DECIMAL(5,2) DEFAULT 0,
			    early_bird_expiry 	DATETIME DEFAULT NULL,
			    early_bird_name		VARCHAR(255),
			    created_at 			TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			    updated_at 			TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			    FOREIGN KEY (course_id) REFERENCES $wpdb->posts(ID) ON DELETE CASCADE
			) ENGINE = InnoDB 
			DEFAULT CHARSET = utf8mb4 
			COLLATE = utf8mb4_unicode_520_ci"
		);
		update_option('cc_pricing_table_ver', $cc_pricing_table_ver);
	}
}

/**
 * To customize the permalink structure of a custom post type (training) in WordPress based on a post meta field (course_type), you’ll need to:
 * Register the custom post type without a fixed slug.
 * Hook into WordPress' post_type_link to modify the permalink based on post meta.
 * Use rewrite rules to support custom URLs like /live-training/training-title/.
 */
function training_custom_permalink($post_link, $post) {
    if ($post->post_type === 'course') {
        $course_type = get_post_meta($post->ID, '_course_type', true);
        
        if ($course_type === 'live') {
            $slug = 'live-training';
        } elseif ($course_type === 'on-demand') {
            $slug = 'on-demand';
        } else {
            $slug = 'training'; // fallback
        }

        return str_replace('%course_type%', $slug, $post_link);
    }

    return $post_link;
}
add_filter('post_type_link', 'training_custom_permalink', 10, 2);

add_action('init', 'add_course_type_permalink_rewrites');
function add_course_type_permalink_rewrites() {
    add_rewrite_rule(
        '^training-course/on-demand/([^/]+)/?$',
        'index.php?post_type=course&name=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^training-course/live-training/([^/]+)/?$',
        'index.php?post_type=course&name=$matches[1]',
        'top'
    );
}


// expects an array of id (update only), course_id, position, title, description
function course_module_db_update( $module ){
	global $wpdb;
	$table_name = $wpdb->prefix.'course_modules';
	$format = array( '%d', '%d', '%s', '%s' );
	if( isset( $module['id'] ) && $module['id'] > 0 ){
		$module_id = $module['id'];
		unset($module['id']);
		$where = array(
			'id' => $module_id,
		);
		$result = $wpdb->update( $table_name, $module, $where, $format );
		return $module_id;
	}else{
		unset($module['id']); // just in case it was 0
		$result = $wpdb->insert( $table_name, $module, $format );
		return $wpdb->insert_id;
	}
}

// expects an array containing id (update only), module_id, position, title, description, start_time, end_time, recording_type, recording_id
function course_section_db_update( $section ){
	global $wpdb;
	$table_name = $wpdb->prefix.'course_sections';
	$format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );
	if( isset( $section['id'] ) && $section['id'] > 0 ){
		$section_id = $section['id'];
		unset( $section['id'] );
		$where = array(
			'id' => $section_id,
		);
		$result = $wpdb->update( $table_name, $section, $where, $format );
		return $section_id;
	}else{
		unset( $section['id'] );
		$result = $wpdb->insert( $table_name, $section, $format );
		return $wpdb->insert_id;
	}
}

// expects an array containing id (update only) and all other fields except created_at and updated_at
function course_pricing_db_update( $pricing ){
	global $wpdb;
	$table_name = $wpdb->prefix.'course_pricing';
	$format = array( '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s' );
	if( isset( $pricing['id'] ) && $pricing['id'] > 0 ){
		$pricing_id = $pricing['id'];
		unset( $pricing['id'] );
		$where = array(
			'id' => $pricing_id,
		);
		$result = $wpdb->update( $table_name, $pricing, $where, $format );
		return $pricing_id;
	}else{
		unset( $pricing['id'] );
		$result = $wpdb->insert( $table_name, $pricing, $format );
		return $wpdb->insert_id;
	}
}

// get course pricing
// always returns an array
function course_pricing_get( $course_id ){
	global $wpdb;
	$table_name = $wpdb->prefix.'course_pricing';
	$sql = "SELECT * FROM $table_name WHERE course_id = $course_id";
	$row = $wpdb->get_row( $sql, ARRAY_A );
	if( $row === NULL ){
		$row = array(
		    'id' => 0,
		    'course_id' => $course_id,
		    'pricing_type' => '',
		    'price_gbp' => 0,
		    'price_usd' => 0,
		    'price_eur' => 0,
		    'price_aud' => 0,
		    'student_discount' => 0,
		    'early_bird_discount' => 0,
		    'early_bird_expiry' => NULL, // Y-m-d H:i:s
		    'early_bird_name' => '',
		    'created_at' => NULL,
		    'updated_at' => NULL,
		);
	}
	return $row;
}

/**
 * Get all section IDs that contain Vimeo videos for a given course
 * 
 * @param int $course_id The ID of the course
 * @return array Array of section IDs that have Vimeo videos
 */
function get_course_vimeo_section_ids($course_id) {
    global $wpdb;
    
    // Sanitize the course ID
    $course_id = absint($course_id);
    
    if (!$course_id) {
        return array();
    }
    
    // Define table names
    $modules_table = $wpdb->prefix . 'course_modules';
    $sections_table = $wpdb->prefix . 'course_sections';
    
    // Query to get all section IDs with Vimeo videos for the given course
    $query = $wpdb->prepare(
        "SELECT s.id 
        FROM $sections_table s
        INNER JOIN $modules_table m ON s.module_id = m.id
        WHERE m.course_id = %d 
        AND s.recording_type = 'vimeo' 
        AND s.recording_id IS NOT NULL 
        AND s.recording_id != ''
        ORDER BY m.position ASC, s.position ASC",
        $course_id
    );
    
    // Execute the query and get results
    $section_ids = $wpdb->get_col($query);
    
    // Return array of section IDs (empty array if none found)
    return !empty($section_ids) ? array_map('intval', $section_ids) : array();
}
