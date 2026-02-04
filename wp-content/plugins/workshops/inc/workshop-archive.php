<?php
/**
 * Workshop Archive page (aka the upcoming workshops page)
 */

// replaces the query on the archiove page to select upcoming workshops ...
// rebuilt June 2023 
// new approach is to get all workshops and then choose which to show
// $pub_since is a compatible strtotime string or array of year, month, day (see wp_query)
function workshop_archive_get_posts( $pub_since='', $criteria='', $search_term='' ){
	global $wpdb;
	// we'll get them in start data order (and exclude unlisted ones)
	$args = array(
	    'post_type' => 'workshop',
	    'numberposts' => -1,
	    'orderby'   => 'meta_value_num',
	    'order' => 'ASC',
	    'meta_key'  => 'workshop_start_timestamp',
	    'meta_query' => array(
	        'relation' => 'OR',
	        array(
	            'key'     => 'course_status',
	            'value'   => 'unlisted',
	            'compare' => '!='
	        ),
	        array(
	            'key'     => 'course_status',
	            'compare' => 'NOT EXISTS'
	        )
	    )
	);

	if( $pub_since <> '' ){
	    $args['date_query'] = array(
	        array(
	            'after' => $pub_since
	        )
	    );
	}

	$all_workshops = get_posts($args);

	// now, if we have a search term, we need to select the workshops that match
	if( $search_term <> '' ){
		$all_workshops = cc_search_match( $all_workshops, $search_term );
	}

	// we want featured workshops to come up front
	$featured_workshops = array();
	$normal_workshops = array();
	// now let's choose the ones we actually want
	foreach ($all_workshops as $workshop) {
		/**
		 * We cannot use workshops_show_this_workshop($workshop->ID) to check to see if it is a workshop to be shown as this will hide all workshops that are closed for sale, even if they are in the future
		 * So, instead, we'll use the start time of the workshop (or its last event)
		 * In this way, workshops with at least one event will be listed even if they are closed for sale
		 */
		if(workshop_incomplete($workshop->ID)){
			if( $criteria <> '' ){
				if( $criteria == 'free' ){
					$gbp_price = get_post_meta( $workshop->ID, 'online_pmt_amt', true );
					if( $gbp_price <> 0 ){
						continue;
					}
				}
			}
			$workshop_featured = get_post_meta($workshop->ID, 'workshop_featured', true);
			if($workshop_featured == 'yes'){
				$featured_workshops[] = $workshop;
			}else{
				$normal_workshops[] = $workshop;
			}
		}
	}

	/**
	 * I started changing the following to better select the workshops and then realised the above would probably be better and simpler
	 * the code below is partly modified and probably should be deleted but I left it here because it does some nice things with get_posts args
	 * 
	// let's start with all the featured workshops
	// however, there's a problem ... workshop_start_timestamp contains the correct date but not the correct time
	// therefore let's get the workshops that start after midnight last night and then work out whether to show them or not
	$today = strtotime('today UTC');
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => -1,
		'orderby'   => 'meta_value_num',
		'order' => 'ASC',
		'meta_key'  => 'workshop_start_timestamp',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'workshop_start_timestamp',
				'value' => $today,
				'compare' => '>',
			),
			array(
				'key' => 'workshop_featured',
				'value' => 'yes',
				'compare' => '=',
			),
		),
	);
	$featured_posts = get_posts($args);
	// but some of these may need to be ignored
	$featured_workshops = array();
	foreach ($featured_posts as $workshop) {
		if(workshops_show_this_workshop($workshop->ID)){
			$featured_workshops[] = $workshop;
		}
	}

	// now we need the rest of them
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => -1,
		'orderby'   => 'meta_value_num',
		'order' => 'ASC',
		'meta_key'  => 'workshop_start_timestamp',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'workshop_start_timestamp',
				'value' => $today,
				'compare' => '>',
			),
			array(
				'relation' => 'OR',
				array(
					'key' => 'workshop_featured',
					'value' => 'yes',
					'compare' => '!=',
				),
				array(
					'key' => 'workshop_featured',
					'compare' => 'NOT EXISTS',
				),
			),
		),
	);
	$normal_workshops = get_posts($args);
	// but again, we need to check which ones to actually include ....
	// gave up at this point!

	*/

	return array_merge($featured_workshops, $normal_workshops);
}

/*
add_filter('query', 'get_sql');
function get_sql($query){
  //check if this is your query,
  if(strpos($query, "'workshop'")>0){
    //this is your query....print it to your log file to debug it.
    ccpa_write_log('logging the query from workshop-archive.php');
    ccpa_write_log($query);
  }
  return $query;
}
*/
