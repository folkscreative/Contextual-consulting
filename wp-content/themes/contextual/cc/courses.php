<?php
/**
 * Course related functions
 */

// get everything for a training course
// returns false if not found
function course_get_all( $id ){
	global $wpdb;

    $table_modules = $wpdb->prefix . 'course_modules';
    $table_sections = $wpdb->prefix . 'course_sections';

	$course = get_post( $id, ARRAY_A );
    if( ! $course || $course === NULL ) return false;

	// will include _training_faqs _training_presenters and _thumbnail_id
	$course['meta'] = get_post_meta( $id );
	
	$course['modules'] = array();

    // Fetch modules linked to this course
    $modules = $wpdb->get_results("SELECT * FROM $table_modules WHERE course_id = $id ORDER BY position ASC", ARRAY_A);
    $count_modules = 0;
    $count_all_sections = 0;
	foreach ($modules as $module){
		$module_array = array(
			'id' => $module['id'],
			'title' => $module['title'],
            'timing' => $module['timing'],
			'description' => $module['description'],
			'resources' => resources_get( 'module', $module['id'] ),
			'sections' => array(),
		);

        $sections = $wpdb->get_results("SELECT * FROM $table_sections WHERE module_id = ".$module['id']." ORDER BY position ASC", ARRAY_A);
        $count_sections = 0;
        foreach ($sections as $section) {
        	$module_array['sections'][] = array(
        		'id' => $section['id'],
        		'title' => $section['title'],
        		'description' => $section['description'],
        		'start_time' => $section['start_time'], // Y-m-d H:i:s
        		'end_time' => $section['end_time'], // Y-m-d H:i:s
        		'recording_type' => $section['recording_type'],
        		'recording_id' => $section['recording_id'],
				'resources' => resources_get( 'section', $section['id'] ),
                'zoom_chat' => courses_zoom_chat_get( $section['id'] ),
        	);
        	$count_sections ++;
        	$count_all_sections ++;
        }
        $module_array['sections_count'] = $count_sections;
        $course['modules'][] = $module_array;
        $count_modules ++;
	}
	$course['module_counts']['module_count'] = $count_modules;
	$course['module_counts']['all_sections_count'] = $count_all_sections;

	$course['accordions'] = cc_train_acc_tta_get_all( $id );

	$course['pricing'] = course_pricing_get( $id );

	$course['resources'] = resources_get( 'course', $id );

    $course['recording_expiry'] = rpm_cc_recordings_get_expiry( $id, true );

	$taxonomies = array(
		'tax_issues' => 'issue',
		'tax_approaches' => 'approach',
		'tax_rtypes' => 'resource-type',
		'tax_others' => 'other',
		'tax_trainlevels' => 'training-level',
	);
	foreach ($taxonomies as $taxonomy => $class) {
		$course[$taxonomy] = get_the_terms( $id, $taxonomy );
	}

	// $course['presenters'] = cc_presenters_training_get_ids( $id );

	return $course;
}

// returns single metas from the complete course array
function course_single_meta( $course, $meta_key ){
	if( ! isset( $course['meta'][$meta_key] ) ){
		return '';
	}
	if( is_array( $course['meta'][$meta_key] ) ){
		return $course['meta'][$meta_key][0];
	}
	return $course['meta'][$meta_key];
}

// add a column to the post type's admin
// basically registers the column and sets it's title
// but as we want it in the middle, it takes a little bit of faffing about
add_filter( 'manage_course_posts_columns', function ( $columns ) {
	$new_cols = array();
    foreach( $columns as $key => $value ) {
        if($key=='taxonomy-tax_issues') {
        	// insert the order column now
			$new_cols['course_type'] = 'Type';
        }    
        $new_cols[$key] = $value;
    }
    return $new_cols;  
});

// display the value in the menu_order column
add_action( 'manage_course_posts_custom_column', function ( $column_name, $post_id ){
	if ($column_name == 'course_type') {
		echo get_post_meta( $post_id, '_course_type', true );
	}
}, 10, 2);

// retrieve paginated course ids for the recording archive page
function courses_for_recording_archive( $paginated = true, $page_num = 1 ) {
    global $wpdb;
    // ccpa_write_log('function courses_for_recording_archive');
    $posts_per_page = 10;

    if( isset( $_GET['page'] ) ){
        $page_num = (int) $_GET['page'];
    }

    // Meta query for course_type and course_status
    $meta_query = [
        'relation' => 'AND',
        [
            'key' => '_course_type',
            'value' => 'on-demand',
            'compare' => '='
        ],
        [
            'relation' => 'OR',
            [
                'key' => '_course_status',
                'value' => 'public',
                'compare' => '='
            ],
            [
                'key' => '_course_status',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key' => '_course_status',
                'value' => '',
                'compare' => '='
            ]
        ]
    ];

    $args = [
        'post_type'      => 'course',
        'post_status'    => 'publish',
        'posts_per_page' => $paginated ? $posts_per_page : -1,
        'paged'          => $paginated ? max(1, (int)$page_num) : 1,
        'fields'         => 'ids',
        'meta_query'     => $meta_query,
        'orderby'        => 'none',
    ];

    // Add a LEFT JOIN and custom ORDERBY to simulate "featured first"
    add_filter('posts_clauses', function ($clauses) use ($wpdb) {
        // Add LEFT JOIN for workshop_featured
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS wsf ON {$wpdb->posts}.ID = wsf.post_id AND wsf.meta_key = 'workshop_featured'";

        // Order: featured = 'yes' first, then post_date desc
        $clauses['orderby'] = "CASE WHEN wsf.meta_value = 'yes' THEN 1 ELSE 0 END DESC, {$wpdb->posts}.post_date DESC";

        return $clauses;
    });

    // ccpa_write_log($args);

    $query = new WP_Query($args);

    // ccpa_write_log($query->posts);

    // Clean up filter to avoid affecting other queries
    remove_all_filters('posts_clauses');

    return [
        'recordings'  => $query->have_posts() ? $query->posts : [],
        'pages'       => $paginated ? intval($query->max_num_pages) : 1,
        'page_num'    => $paginated ? max(1, intval($page_num)) : 1
    ];
}

// constructs the course early bird message
// course must including the pricing stuff
/*  not working and not really needed!
function course_early_bird_msg( $course, $currency, $normal_price ){
    ccpa_write_log('function course_early_bird_msg');
    ccpa_write_log($course['pricing']);
    ccpa_write_log($currency);
    ccpa_write_log($normal_price);
    if( $course['pricing']['early_bird_discount'] > 0 && $course['pricing']['early_bird_expiry'] <> NULL && $course['pricing']['early_bird_expiry'] > date( 'Y-m-d H:i:s' ) ){
        $earlybird_name = $course['pricing']['early_bird_name'] == '' ? 'Early-bird' : $course['pricing']['early_bird_name'];
        $expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $course['pricing']['early_bird_expiry'] );
        if( $expiry_date ){
            return $earlybird_name.' rate valid until '.$expiry_date->format('jS M Y').' and then the normal price of <span class="non-early-price">'.workshops_price_prefix($currency).number_format($normal_price,2).'</span> + VAT will apply.';
        }else{
            return $earlybird_name.' rate currently valid. The normal price is <span class="non-early-price">'.workshops_price_prefix($currency).number_format($normal_price,2).'</span> + VAT.';
        }
    }
    return '';
}
*/

// student price
function course_student_pricing( $course, $currency ){
    $response = array(
        'student_price' => NULL,
        'student_price_formatted' => '',
    );

    if( $course['pricing']['student_discount'] == 0 ){
        return $response;
    }

    $key = 'price_'.strtolower( $currency );
    if( $course['pricing'][$key] > 0 ){
        $discount_price = $course['pricing'][$key] - ( $course['pricing'][$key] * $course['pricing']['student_discount'] / 100 );
        // however, we want this rounded up to the next multiple of 5
        $response['student_price'] = ceil( $discount_price / 5 ) * 5;
        $response['student_price_formatted'] = workshops_price_prefix( $currency ).number_format( $response['student_price'],2 );
    }

    return $response;
}

// what type of training is this?
// returns 'recording', 'workshop', 'series' or '' = unknown!
function course_training_type( $training_id ){
    $post_type = get_post_type( $training_id );
    switch ($post_type) {
        case 'workshop':
            return 'workshop';
            break;
        case 'recording':
            return 'recording';
            break;
        case 'series':
            return 'series';
            break;
        case 'course':
            $course_type = get_post_meta( $training_id, '_course_type', true );
            if( $course_type == 'on-demand' ){
                return 'recording';
            }
            break;
        case 'series':
            return 'series';
            break;
    }
    return '';
}

// when a user watches a video that may be the trigger to add them to a newsletter list
// returns true if assigned this time, otherwise returns false
function courses_maybe_newsletter_sub( $user_id, $course_id, $section_id ){
    // this function will be called many times so we need to quickly exit when we can
    $newsletter_trigger_section = (int) get_post_meta( $course_id, 'newsletter_trigger_section', true );
    if( $newsletter_trigger_section <> $section_id ) return false;
    $newsletter_trigger_list = (int) get_post_meta( $course_id, 'newsletter_trigger_list', true );
    if( $newsletter_trigger_list == 0 ) return false;
    // this should trigger a subscription ... unless the user is already subscribed
    $subscriber_id = cc_mailster_subs_id_of_user( $user_id );
    if( $subscriber_id ){
        if( cc_mailsterint_subs_on_list( $subscriber_id, $newsletter_trigger_list ) ){
            // already subscribed
            return false;
        }
        // add them to the list
        mailster('subscribers')->assign_lists( $subscriber_id, $newsletter_trigger_list );
        return true;
    }
    return false;
}