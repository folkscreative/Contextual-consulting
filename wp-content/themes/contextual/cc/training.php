<?php
/**
 * Training stuff for the training landing page ... and other common training stuff
 */

// the main shortcode
// [training type="new" title="New Releases"]
add_shortcode( 'training', 'cc_training_shortcode' );
function cc_training_shortcode( $atts, $content ){
	$atts = shortcode_atts( array(
		'type' => 'new',
		'title' => '',
		'icon' => '',
	), $atts );

	$trainings = cc_training_get( $atts['type'] );
	if( ! $trainings ){
		return '';
	}

	if( is_numeric( $atts['type'] ) ){
		$training_id = $atts['type'];
		// training card for a specific training id
		$html = '<div class="cc-train-panel cc-train-panel-card">';
		if( $atts['title'] <> '' ){
			$icon = '';
			if( $atts['icon'] <> '' ){
				if( is_numeric( $atts['icon'] ) ){
					// 43px square icon works best
					$icon_html = '<img src="'.wms_section_image_url( $atts['icon'] ).'" alt="'.$atts['title'].' training panel icon" class="cc-train-panel-icon">';
				}else{
					$icon_html = wms_font_awesome_core( $atts['icon'], 'solid', 'text-orange', '', 'lg' );
				}
			}
			$html .= '<h3 class="cc-train-panel-title">'.$icon_html.' '.$atts['title'].'</h3>';
		}
		$html .= '<div class="cc-train-panel-text mb-4">'.do_shortcode( $content ).'</div>';
		$html .= '<div class="row training-list">';
		// $html .= '<div class="col-12 col-md-10 offset-md-1">';
		$html .= cc_training_card_flexible( $training_id, array(), true );
		/*
		if( $trainings->post_type == 'workshop' ){
			$html .= cc_wksp_archive_card( $trainings, false );
		}elseif( $trainings->post_type == 'recording' ){
			$html .= recording_card( $trainings->ID, false );
		}
		*/
		// $html .= '</div><!-- .col -->';
		$html .= '</div><!-- .row -->';
		$html .= '</div><!-- .cc-train-panel -->';
		return $html;
	}

	// normal training panel

	if( count( $trainings ) == 0 ){
		return '';
	}

	$doing_carousel = false;
	if( count( $trainings ) > 4 ){
		$doing_carousel = true;
	}

	$html = '<div class="cc-train-panel cc-train-panel-'. $atts['type'].'">';
	if( $atts['title'] <> '' ){
		$icon = '';
		if( $atts['icon'] <> '' ){
			if( is_numeric( $atts['icon'] ) ){
				// 43px square icon works best
				$icon_html = '<img src="'.wms_section_image_url( $atts['icon'] ).'" alt="'.$atts['title'].' training panel icon" class="cc-train-panel-icon">';
			}else{
				$icon_html = wms_font_awesome_core( $atts['icon'], 'solid', 'text-orange', '', 'lg' );
			}
		}
		$html .= '<h3 class="cc-train-panel-title">'.$icon_html.' '.$atts['title'].'</h3>';
	}
	$html .= '<div class="cc-train-panel-text mb-4">'.do_shortcode( $content ).'</div>';
	$html .= '<div class="row mx-auto my-auto training-list';
	if( $doing_carousel ){
		$html .= ' justify-content-center';
	}
	$html .= '">';
	
	static $carousel_id = 0;
	if( $doing_carousel ){
		$html .= '<div id="cc-train-panel-carousel-'.$carousel_id.'" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="7000">';
		// controls ... moved to end of carousel code
		// $html .= '<div class="float-end pe-md-4"><a class="indicator" href="#cc-train-panel-carousel-'.$carousel_id.'" role="button" data-bs-slide="prev"><i class="fa-solid fa-chevron-left"></i></a> &nbsp;&nbsp; <a class="w-aut indicator" href="#cc-train-panel-carousel-'.$carousel_id.'" role="button" data-bs-slide="next"><i class="fa-solid fa-chevron-right"></i></a></div>';
		// the carousel
		$html .= '<div class="carousel-inner cc-train-panel-carousel-inner" role="listbox">';
	}else{
		$html .= '<p>&nbsp;</p>';
	}

	$item_class = 'active';
	$item_count = 0;
	$user_timezone = cc_timezone_get_user_timezone();
	foreach ($trainings as $training) {
		if( $doing_carousel ){
			$html .= '<div class="carousel-item train-carousel-item '.$item_class.'">';
		}
		$item_class = '';
		$html .= '<div class="col-12 col-md-6 col-xl-3 cc-train-panel-col d-flex align-items-stretch">';
		$html .= '<div class="card mb-2 flex-fill training-'.$training->ID.'">';

		// the image ...
		// nested carousels are not supported (Bootstrap v5.3) hence we do not attempt to show multiple presenter images
		$item_count ++;
		$presenters_image_ids = cc_presenters_training_get_ids( $training->ID );

		$flag = '';
		$venue = get_post_meta( $training->ID, 'event_1_venue_name', true );
		if( $venue <> '' ){
			$flag = 'In person';
		}

		if( $atts['type'] == 'new' || count( $presenters_image_ids ) <> 1 ){
			// use featured image
			// we're using presenter-profile instead of post-image to match the presenter images
			$feat_img_url = get_the_post_thumbnail_url( $training->ID, 'presenter-profile' );
			if($feat_img_url == ''){
				$feat_img_url = get_the_post_thumbnail_url( get_option('page_on_front'), 'presenter-profile' );
			}
			$html .= '<div class="wms-image-wrapper">';
			// lazy load images 5 onwards
			if( $item_count > 5 ){
				$html .= '<div data-src="'.$feat_img_url.'" class="card-img-top lazy cc-train-panel-img wms-bg-img">';
			}else{
				$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img" style="background-image:url('.$feat_img_url.');">';
			}
			// add a flag if needed
			if( $flag <> '' ){
				$html .= '<div class="train-card-flag">'.$flag.'</div>';
			}
			$html .= '</div><!-- .cc-train-panel-img -->';
			$html .= '</div><!-- .wms-image-wrapper -->';
		}else{
			// use presenters images
			if( $item_count > 5 ){
				// cc_presenters_images( $training_id, $lazyload=false, $first_only=false, $bgimg_xclass='', $flag='' )
				$html .= cc_presenters_images( $training->ID, true, true, 'card-img-top', $flag ); // first only
			}else{
				$html .= cc_presenters_images( $training->ID, false, true, 'card-img-top', $flag ); // first only
			}
		}

		$html .= '<div class="card-body grad-bg">';

		$html .= '<div class="row cc-train-panel-preheader"><div class="col-6">';
		if( $training->post_type == 'workshop' ){
			$html .= 'LIVE';
		}else{
			$html .= 'ON-DEMAND';
		}
		$html .= '</div><div class="col-6 text-end">';
		if( $training->post_type == 'workshop' ){
			$html .= get_post_meta( $training->ID, 'prettydates', true );
		}else{
			$html .= get_post_meta( $training->ID, 'duration', true );
		}
		$html .= '</div></div>';

		$title_font = cc_tnc_text_size( 262, 90, $training->post_title, 24);
		$html .= '<h5 class="card-title cc-train-panel-card-title" style="font-size:'.$title_font['font_size'].'px; line-height:'.$title_font['line_height'].';">'.$training->post_title.'</h5>';
		/*
		if( strlen( $training->post_title ) > 35 ){
			$html .= '<h5 class="card-title" title="'.$training->post_title.'">'.substr( $training->post_title, 0, 35).'...</h5>';
		}else{
			$html .= '<h5 class="card-title">'.$training->post_title.'</h5>';
		}
		*/

		$card_text = cc_presenters_names( $training->ID, 'none' );
		if( $card_text == '' ){
			$card_text = wms_get_excerpt_by_id( $training->ID, '70', 'characters' );
			if( strlen( $card_text ) > 70 ){
				$card_text = substr( $card_text, 0, 70).' ...';
			}
		}
		$html .= '<p class="card-text">'.$card_text.'</p>';

		if( $atts['type'] == 'upcoming' && $training->post_type == 'workshop' ){
			$pretty_dates = workshop_calculated_prettydates($training->ID, $user_timezone);
			if($pretty_dates['locale_date'] <> ''){
				$html .= '<p class="card-text">'.$pretty_dates['locale_date'].'</p>';
			}
		}

		// $html .= '<p class="cc-train-panel-presenters">'.cc_presenters_names( $training->ID, 'none' ).'</p>';
		$html .= '<div class="text-end"><a href="'.get_permalink($training->ID).'" class="btn btn-primary btn-sm">Read More</a></div>';
		
		$html .= '</div><!-- .card-body -->';
		$html .= '</div><!-- .card -->';
		$html .= '</div><!-- .col-md-6.col-xl-3 -->';
		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-item -->';
		}
	}
	
	if( $doing_carousel ){
		$html .= '</div><!-- .carousel-inner -->';
		// controls
		$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-train-panel-carousel-'.$carousel_id.'" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-train-panel-carousel-'.$carousel_id.'" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
		$html .= '</div><!-- .carousel -->';
	}
	$html .= '</div><!-- .row -->';

	// show some btns?
	/* Nope!
	switch ($atts['type']) {
		case 'new':
			// no buttons
			break;
		case 'upcoming':
			$html .= '<div class="training-buttons-wrap text-end"><a class="btn btn-training" href="/workshop">All live training</a></div>';
			break;
		case 'pop-recordings':
			$html .= '<div class="training-buttons-wrap text-end"><a class="btn btn-training" href="/recording">On-demand training</a></div>';
			break;
	}
	*/

	$html .= '</div><!-- .cc-train-panel -->';

	$carousel_id ++;
	return $html;
}

// get the appropriate trainings
function cc_training_get( $type ){
	// ccpa_write_log('function cc_training_get');
	// ccpa_write_log('type = '.$type);
	if( $type == 'new' ){
		// new releases
		// get all (wanted) trainings
		// recording_get_all_available( $order_by='', $pub_since='', $criteria='', $search_term='', $inc_unlisted=true )
		$recordings = recording_get_all_available( '', '-3 months', '', '', false );
		$workshops = workshop_archive_get_posts( '-3 months' );
		// $recordings = recording_get_all_available();
		// $workshops = workshop_archive_get_posts();
		// put them into publish date order
		$trainings = array();
		foreach ($recordings as $recording) {
			$key = strtotime( $recording->post_date );
			$trainings[$key] = $recording;
		}
		foreach ($workshops as $workshop) {
			$key = strtotime( $workshop->post_date );
			$trainings[$key] = $workshop;
		}
		// we want the latest first
		krsort( $trainings );
		return $trainings;
	}elseif( $type == 'upcoming' ){
		// upcoming workshops in date order
		return workshop_archive_get_posts();
	}elseif( $type == 'pop-recordings' ){
		// on-demand in popularity order
		$recordings = recording_get_all_available( '', '', '', '', false );
		// ccpa_write_log( count($recordings).' recordings found' );
		$trainings = array();
		$recording_ids = array();
		foreach ($recordings as $recording) {
			$recording_ids[] = $recording->ID;
			/*
			if( $recording->post_type <> 'recording' ){
				// ccpa_write_log( 'error with '.$recording->ID.' post_type is '.$recording->post_type );
			}
			*/
			$popularity = cc_training_get_recent_attendees( $recording->ID );
			if( $popularity > 0 ){
				// doing it this way as recording is an object, not array
				// if it was an array we could just add another field to it and use that ...
				$trainings[] = array(
					'popularity' => $popularity,
					'recording' => $recording,
				);
			}
		}
		// ccpa_write_log($recording_ids);
		// ccpa_write_log( count($trainings).' with some attendees' );
		// now sort into descending popularity order
		usort( $trainings, fn($a, $b) => $b['popularity'] <=> $a['popularity'] );
		// but we only want to return the recordings
		$result = array();
		$recording_ids = array();
		foreach ($trainings as $training) {
			$result[] = $training['recording'];
			$recording_ids[] = $training['recording']->ID;
		}
		// ccpa_write_log( count($result).' sorted recordings being returned' );
		// ccpa_write_log($recording_ids);
		return $result;
	}elseif( $type == 'free-recordings' ){
		return recording_get_all_available( '', '', 'free', '', false );
	}elseif( $type == 'free-workshops' ){
		return workshop_archive_get_posts( '', 'free' );
	}elseif( is_numeric( $type ) ){
		// single card
		$post = get_post( $type );
		if( $post === null ) return false;
		if( $post->post_type == 'workshop' ){
			if( $post->post_status == 'publish' && workshop_incomplete( $post->ID ) ){
				// allows unlisted workshops to be intentionally shown here
				return $post;
			}
		}elseif( $post->post_type == 'course' ){
			if( $post->post_status == 'publish' ){
		        $course_status = get_post_meta( $post->ID, '_course_status', true );
		        if( $course_status <> 'closed' ){
		        	return $post;
		        }
			}
		}
		return false;
	}
}

// add the number of attendees to the popularity stats for a specific payment
function cc_training_popularity_update( $training_id, $count_attendees, $year='', $month='' ){
	if( $year == '' ){
		$year = date('Y');
	}
	if( $month == '' ){
		$month = date('n');
	}
	$yr_pop_stats = cc_training_get_pop_stats( $training_id, $year );
	$yr_pop_stats[$month] = $yr_pop_stats[$month] + $count_attendees;
	update_post_meta( $training_id, 'pop-stats-'.$year, $yr_pop_stats );
}

// gets the stats for a year
function cc_training_get_pop_stats( $training_id, $year ){
	$yr_pop_stats = get_post_meta( $training_id, 'pop-stats-'.$year, true );
	if( is_array( $yr_pop_stats ) ){
		return $yr_pop_stats;
	}
	$yr_pop_stats = array();
	for ($i=1; $i < 13; $i++) { 
		$yr_pop_stats[$i] = 0;
	}
	return $yr_pop_stats;
}

// gets the number of attendees for a training for the past 3 months
function cc_training_get_recent_attendees( $training_id ){
	$month = date('n');
	$year = date('Y');
	$day = date('j');
	$attendees = 0;
	// this month
	$yr_pop_stats = cc_training_get_pop_stats( $training_id, $year );
	$attendees = $attendees + $yr_pop_stats[$month];
	// month -1
	$month --;
	if( $month == 0 ){
		$month = 12;
		$year --;
	}
	$yr_pop_stats = cc_training_get_pop_stats( $training_id, $year );
	$attendees = $attendees + $yr_pop_stats[$month];
	// month -2
	$month --;
	if( $month == 0 ){
		$month = 12;
		$year --;
	}
	$yr_pop_stats = cc_training_get_pop_stats( $training_id, $year );
	$attendees = $attendees + $yr_pop_stats[$month];
	// month -3
	$month --;
	if( $month == 0 ){
		$month = 12;
		$year --;
	}
	$yr_pop_stats = cc_training_get_pop_stats( $training_id, $year );
	// we only want to take part of this month
	$attendees = $attendees + round( $yr_pop_stats[$month] / 30 * $day, 0 );
	return $attendees;
}

// the responsive, flexible training card
// works in grid or list view based on an outer div having the class training-grid or training-list
// these cards must always be shown within a .row
function cc_training_card_flexible( $training_id, $params=array(), $full_version=true ){
	$html = '';

	$post_type = get_post_type( $training_id );

	if( $post_type == 'course' ){
		$venue = '';
		$course_type = get_post_meta( $training_id, '_course_type', true );
	}else{
		$venue = get_post_meta( $training_id, 'event_1_venue_name', true );
		$course_type = $post_type;
	}

	$workshop_featured = get_post_meta($training_id, 'workshop_featured', true);
	$presenters_image_ids = cc_presenters_training_get_ids( $training_id );
	// we're using presenter-profile instead of post-image to match the presenter images
	$feat_img_url = get_the_post_thumbnail_url( $training_id, 'presenter-profile' );
	$venue = get_post_meta( $training_id, 'event_1_venue_name', true );
	$training_title = get_the_title( $training_id );
	$training_subtitle = get_post_meta( $training_id, 'subtitle', true );
	$excerpt = wms_get_excerpt_by_id( $training_id, '15', 'words', '' );

	if( $venue == '' ){
		$flag = '';
	}else{
		$flag = 'In person';
	}

	$wrap_class = '';
	if($workshop_featured == 'yes'){
		$wrap_class = 'featured ';
	}
	if( count( $presenters_image_ids ) > 1 ){
		$wrap_class .= 'pres-carousel';
	}else{
		$wrap_class .= 'single-image';
	}

	if( isset( $params['search'] ) && $params['search'] <> '' ){
		$card_title = cc_search_highlight( $training_title, $params['search'] );
	}else{
		$card_title = $training_title;
	}
	$title_font = cc_tnc_text_size( 262, 90, $card_title, 24);

	// $html .= '<div class="ftc-l col-12 col-md-10 offset-md-1">';
	// $html .= '<div class="ftc-g col-md-6 col-lg-4 d-flex align-items-stretch">';
	$html .= '<div class="ftc-col col-12 d-flex flex-column justify-content-between">';
	$html .= '<div class="training-wrap text-start grad-bg mb-5 h-100 workshop-'.$training_id.' '.$wrap_class.'">';
	$html .= '<div class="row">';

	// $html .= '<div class="col-md-6 col-lg-4 cc-train-panel-colxx d-flex align-items-stretch">';
	// $html .= '<div class="col-12 col-lg-5 order-lg-2">';
	$html .= '<div class="ftc-image-col col-12">';
	$html .= '<div class="wms-image-wrapper">';
	$html .= '<a href="'.get_permalink( $training_id ).'">';
	if( count( $presenters_image_ids ) > 0 ){
		// $training_id, $lazyload=false, $first_only=false, $bgimg_xclass='', $flag=''
		$html .= cc_presenters_images( $training_id, false, false, '', $flag );
	}else{
		if($feat_img_url == ''){
			$feat_img_url = get_the_post_thumbnail_url( get_option( 'page_on_front' ), 'presenter-profile' );
		}
		$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img image-zoom" style="background-image:url('.$feat_img_url.');">';
		if( $flag <> '' ){
			$html .= '<div class="train-card-flag">'.$flag.'</div>';
		}
		$html .= '</div>';
	}
	$html .= '</a>';
	$html .= '</div><!-- .wms-image-wrapper -->';
	$html .= '</div><!-- .ftc-image-col -->';

	// $html .= '<div class="col-12 col-lg-7 order-lg-1">';
	$html .= '<div class="ftc-text-col col-12">';
	$html .= '<div class="ftc-text-wrap h-100 p-4 flex-column">';

	$html .= '<div class="row cc-train-panel-preheader"><div class="col-6">';
	if( $post_type == 'workshop' ){
		$html .= 'LIVE';
	}else{
		$html .= 'ON-DEMAND';
	}
	$html .= '</div><div class="col-6 text-end">';
	if( $course_type == 'workshop' ){
		$html .= get_post_meta( $training_id, 'prettydates', true );
	}elseif( $course_type == 'on-demand' ){
		$html .= get_post_meta( $training_id, '_course_timing', true );
	}else{
		$html .= get_post_meta( $training_id, 'duration', true );
	}
	$html .= '</div></div>';

	if( $params <> [] ){
		$html .= cc_topics_show( $training_id, 'xs', false, '', $params );
	}

	$html .= '<div class="mb-2"><div class="shrink-font-container mb-2">';
	// $html .= '<h4 class="training-title" style="font-size:'.$title_font['font_size'].'px; line-height:'.$title_font['line_height'].';">'.$card_title.'</4>';
	$html .= '<h4 class="training-title shrink-font mb-0">'.$card_title.'</4>';
	$html .= '</div>';
	if( $full_version && $training_subtitle <> '' ){
		$html .= '<h6 class="training-subtitle mb-2">'.$training_subtitle.'</h6>';
	}
	$html .= '</div>';

	$presenters = cc_presenters_names( $training_id, 'none' );
	if( $presenters <> '' ){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i><span>'.$presenters.'</span></div>';
	}

	if($venue <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-location-dot fa-fw"></i>In person: '.$venue.'</div>';
	}

	if( $post_type == 'workshop' ){
		$pretty_dates = workshop_calculated_prettydates( $training_id, cc_timezone_get_user_timezone() );
		if($pretty_dates['locale_date'] <> ''){
			$html .= '<div class="bullet mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i>'.$pretty_dates['locale_date'].'</div>';
		}
	}

	if( $full_version && $excerpt <> '' ){
		$html .= '<div class="excerpt mt-3"><p>'.$excerpt.' ... </p></div>';
	}

	$html .= '<a class="btn btn-training workshop-btn" href="'.get_permalink( $training_id ).'">Full details</a>';

	$html .= '</div><!-- .ftc-text-wrap -->';
	$html .= '</div><!-- .ftc-text-col -->';

	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .training-wrap -->';
	$html .= '</div><!-- .ftc-col -->';

	return $html;
}

// get up to 3 trainings for the resource hub, knowledge hub and blog posts
/**
 * This training panel is shown on KH, RH and blog posts. It will show up to 3 trainings. The trainings will be selected as follows:
 * - Top priority will be one live training that matches on approaches
 * - Second priority will be one on-demand training that matches on approaches
 * - Third priority will other live training that matches on approaches
 * - Fourth priority will be other on-demand training matching on approaches
 * - Fifth priority will be other live training that matches on issues
 * - Sixth priority will be other on-demand training that matches on issues
 * - Seventh priority will be other live training that matches on the remaining topic types
 * - Eighth priority will be other on-demand training that matches on the remaining topic types
 * Whether a training is featured or not will not be a factor.
 * This means that sometimes all trainings shown will be of one type.
 * Trainings will only be selected for possible inclusion if they are available for registration.
 **/
// $taxonomies should be an array such as array( 'tax_issues' => array( 'tax_one_id', 'tax_two_id' ), 'next_taxonomy' => array( 'tax_three_id' ) )
function cc_trainings_for_hubs( $taxonomies ){
	// ccpa_write_log('cc_trainings_for_hubs');
	// get all upcoming workshops (note this returns the CPTs, not just the IDs)
	$workshops = workshop_archive_get_posts();
	// and all recording ids (for those that are "listed" and open for registration)
	$recording_ids = recording_archive_get_posts( false );

	// now let's allocate a priority to everything
	$pri_workshop_ids = array();
	foreach ($workshops as $training) {
		$priority = cc_trainings_priority( $training->ID, 'w', $taxonomies );
		$pri_workshop_ids[$priority][] = $training->ID;
	}
	$pri_recording_ids = array();
	foreach ($recording_ids as $training_id) {
		$priority = cc_trainings_priority( $training_id, 'r', $taxonomies );
		$pri_recording_ids[$priority][] = $training_id;
	}
	// ccpa_write_log($pri_workshop_ids);
	// ccpa_write_log($pri_recording_ids);

	// now collate this all together
	$training_ids = array();
	if( isset( $pri_workshop_ids[1] ) ){
		$training_ids[] = $pri_workshop_ids[1][0];
	}
	if( isset( $pri_recording_ids[2] ) ){
		$training_ids[] = $pri_recording_ids[2][0];
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_workshop_ids[3] ) ) {
		$training_ids[] = array_shift( $pri_workshop_ids[3] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_recording_ids[4] ) ) {
		$training_ids[] = array_shift( $pri_recording_ids[4] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_workshop_ids[5] ) ) {
		$training_ids[] = array_shift( $pri_workshop_ids[5] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_recording_ids[6] ) ) {
		$training_ids[] = array_shift( $pri_recording_ids[6] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_workshop_ids[7] ) ) {
		$training_ids[] = array_shift( $pri_workshop_ids[7] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_recording_ids[8] ) ) {
		$training_ids[] = array_shift( $pri_recording_ids[8] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_workshop_ids[9] ) ) {
		$training_ids[] = array_shift( $pri_workshop_ids[9] );
	}
	while ( count( $training_ids ) < 3 && ! empty( $pri_recording_ids[9] ) ) {
		$training_ids[] = array_shift( $pri_recording_ids[9] );
	}
	// ccpa_write_log($training_ids);
	return $training_ids;
}

// allocates a priority
function cc_trainings_priority( $training_id, $training_type, $taxonomies ){
	static $pri_1_set = false;
	static $pri_2_set = false;
	// approaches
	if( isset( $taxonomies['tax_approaches'] ) && count( $taxonomies['tax_approaches'] ) > 0 ){
		$terms = wp_get_object_terms( $training_id, 'tax_approaches' );
		$term_ids = wp_list_pluck( $terms, 'term_id' );
		$matches = array_intersect( $taxonomies['tax_approaches'], $term_ids );
		if( ! empty( $matches ) ){
			if( $training_type == 'w' ){
				if( $pri_1_set ){
					return 3;
				}else{
					$pri_1_set = true;
					return 1;
				}
			}else{
				if( $pri_2_set ){
					return 4;
				}else{
					$pri_2_set = true;
					return 2;
				}
			}
		}
	}
	// issues
	if( isset( $taxonomies['tax_issues'] ) && count( $taxonomies['tax_issues'] ) > 0 ){
		$terms = wp_get_object_terms( $training_id, 'tax_issues' );
		$term_ids = wp_list_pluck( $terms, 'term_id' );
		$matches = array_intersect( $taxonomies['tax_issues'], $term_ids );
		if( ! empty( $matches ) ){
			if( $training_type == 'w' ){
				return 5;
			}else{
				return 6;
			}
		}
	}
	// others
	$other_taxs = array( 'tax_rtypes', 'tax_others', 'tax_trainlevels' );
	foreach ($other_taxs as $other_tax) {
		if( isset( $taxonomies[$other_tax] ) && count( $taxonomies[$other_tax] ) > 0 ){
			$terms = wp_get_object_terms( $training_id, $other_tax );
			$term_ids = wp_list_pluck( $terms, 'term_id' );
			$matches = array_intersect( $taxonomies[$other_tax], $term_ids );
			if( ! empty( $matches ) ){
				if( $training_type == 'w' ){
					return 7;
				}else{
					return 8;
				}
			}
		}
	}
	// no match
	return 9;
}

// get all recordings where the user is an attendee
// Returns an empty array if no result is found.
function cc_trainings_recordings_for_attendee( $user_id ){
	global $wpdb;
    $recordings_users_table = $wpdb->prefix.'recordings_users';
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$sql = "SELECT DISTINCT ru.recording_id
		FROM $recordings_users_table ru
		INNER JOIN $attendees_table a ON ru.payment_id = a.payment_id AND ru.user_id = a.user_id
		WHERE ru.user_id = $user_id";
	return $wpdb->get_col( $sql );
}

// view training url
// will take somebody to the my account page and open the modal
// returns false if it cannot create a url
function cc_trainings_view_url( $user_id, $training_id ){
	// is this training a recording?
	if( get_post_type( $training_id ) <> 'course' || get_post_meta( $training_id, '_course_type', true ) <> 'on-demand' ){
		return false;
	}

	// does the user have access to view it?
	$recording_access = ccrecw_user_can_view( $training_id, $user_id );
	if( ! $recording_access['access'] ){
		return false;
	}

	// is their access direct to the recording or via having attended the workshop?
	if( get_post_meta( $training_id, '_course_status', true ) == 'public' ){
		// public access is via the on-demand page
        $access_via = 'recordings';		
	}
    $matching_workshop_id = recording_get_matching_workshop_id( $training_id );
    if($matching_workshop_id === NULL){
    	// there is no matching live training
        $access_via = 'recordings';
    }else{
        $workshops_user = cc_myacct_get_workshops_users_by_user_workshop( $user_id, $matching_workshop_id );
        if( $workshops_user === NULL ){
            // they did not register for the workshop
            $access_via = 'recordings';
        }else{
            // they did register for the workshop
            $access_via = 'workshops';
        }
    }

    // the url needs to look something like this: 
    // https://contextualconsulting.co.uk/my-account?my=recordings&view=1234
    // or maybe https://contextualconsulting.co.uk/my-account?my=workshops&view=1234

    return esc_url(
    	add_query_arg(
    		array(
    			'my' => $access_via,
    			'view' => $training_id,
    		),
    		site_url( 'my-account' )
    	)
    );
}

// gets the viewing stats for all videos for the relevant training/user
function get_training_viewing_stats( $course_id, $user_id ){
	// ccpa_write_log('function get_training_viewing_stats');
	global $wpdb;
	$response = array(
		'viewing_stats' => array(),
		'last_viewed_section' => 0, // section_id
		'last_viewed_playhead' => 0, // seconds from the start
	);
	$viewing_stats = array();

	// get all sections for this course (that contain videos)
	$course_sections = get_course_vimeo_section_ids( $course_id );
	// ccpa_write_log('course_sections');
	// ccpa_write_log($course_sections);

	// get all meta
	// $meta_key = 'cc_rec_wshop_'.$course_id.'%';
	// $sql = "SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE '$meta_key' AND user_id = $user_id ORDER BY meta_key ASC";
	// not the modules
	$meta_key = 'cc_rec_wshop_'.$course_id.'%';
	$sql = "SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE '$meta_key' AND user_id = $user_id ORDER BY meta_key ASC";
	$metas = $wpdb->get_results( $sql, ARRAY_A );

	$last_viewed_section_time = 0;
	foreach ( $metas as $meta ) {
		if( $meta['meta_key'] == 'cc_rec_wshop_'.$course_id || $meta['meta_key'] == 'cc_rec_wshop_'.$course_id.'_9999' ){
			$course_meta = true;
		}else{
			$course_meta = false;
		}

		$meta_value = maybe_unserialize( $meta['meta_value'] );

		if( $course_meta ){
			// should not do this for section meta
			$meta_value = sanitise_recording_meta( $meta_value, $user_id, $course_id );
		}

		$viewing_stats_data = array(
			'access_time' => $meta_value['access_time'] ?? '', // Y-m-d H:i:s
			'access_type' => $meta_value['access_type'] ?? 'paid', // 'paid' etc
			'num_views' => $meta_value['num_views'] ?? '0',
			'first_viewed' => $meta_value['first_viewed'] ?? '', // d/m/Y H:i:s
			'last_viewed' => $meta_value['last_viewed'] ?? '', // d/m/Y H:i:s
			'last_viewed_time' => $meta_value['last_viewed_time'] ?? '0', // num of secs they viewed
			'viewed_end' => $meta_value['viewed_end'] ?? 'no', // no or yes
			'viewing_time' => $meta_value['viewing_time'] ?? '0', // total num secs viewing time
			'closed_time' => $meta_value['closed_time'] ?? '', // when their access was revoked/expired Y-m-d H:i:s
			'closed_type' => $meta_value['closed_type'] ?? '', // eg auto
			'currency' => $meta_value['currency'] ?? '',
			'amount' => $meta_value['amount'] ?? '',
			'token' => $meta_value['token'] ?? '',
			'payment_id' => $meta_value['payment_id'] ?? '',
			'last_playhead' => $meta_value['last_playhead'] ?? 0,
		);
		if( $course_meta ){
			$response['viewing_stats'][0] = $viewing_stats_data;
			$section_id = 0;
		}else{
			$section_id = substr( $meta['meta_key'], strrpos( $meta['meta_key'], '_' ) +1 );
			$response['viewing_stats'][$section_id] = $viewing_stats_data;

			$key = array_search( $section_id, $course_sections );
			if ($key !== false) {
			    unset( $course_sections[$key] );
			}

			if( $meta_value['last_viewed'] <> '' ){
				$dt = DateTime::createFromFormat( 'd/m/Y H:i:s', $meta_value['last_viewed'] );
				if( $dt && $dt->getTimestamp() > $last_viewed_section_time ){
					$last_viewed_section_time = $dt->getTimestamp();
					$response['last_viewed_playhead'] = isset( $meta_value['last_playhead'] ) ? $meta_value['last_playhead'] : 0;
					$response['last_viewed_section'] = $section_id;
				}
			}
		}
	}

	// did we miss any sections?
	foreach ($course_sections as $section_id) {
		$response['viewing_stats'][$section_id] = array(
			'access_time' => '',
			'access_type' => '',
			'num_views' => '0',
			'first_viewed' => '',
			'last_viewed' => '',
			'last_viewed_time' => '0',
			'viewed_end' => 'no',
			'viewing_time' => '0',
			'closed_time' => '',
			'closed_type' => '',
			'currency' => '',
			'amount' => '',
			'token' => '',
			'payment_id' => '',
			'last_playhead' => 0,
		);
	}

	return $response;
}


/*
a:14:{s:11:"access_time";s:19:"2025-01-02 11:36:22";s:11:"access_type";s:4:"paid";s:9:"num_views";i:11;s:12:"first_viewed";s:19:"25/06/2025 16:32:52";s:11:"last_viewed";s:19:"25/06/2025 16:32:52";s:16:"last_viewed_time";i:81;s:10:"viewed_end";s:2:"no";s:12:"viewing_time";i:81;s:11:"closed_time";s:19:"2025-07-02 23:59:59";s:11:"closed_type";s:4:"auto";s:8:"currency";s:3:"GBP";s:6:"amount";i:0;s:5:"token";s:0:"";s:10:"payment_id";i:35943;}
*/

// gets all the pricing info for a training course or workshop
function get_training_prices( $training_id ){
	if( get_post_type( $training_id ) == 'course' ){
		return course_pricing_get( $training_id );
	}
	// must be a workshop .....
	// ccpa_write_log('function get_training_prices');
	$early_bird_discount = (float) get_post_meta( $training_id, 'earlybird_discount', true );
	// ccpa_write_log('early_bird_discount:'.$early_bird_discount);
	$early_bird_expiry_new = '';
	$early_bird_expiry_old = get_post_meta( $training_id, 'earlybird_expiry_date', true );
	// ccpa_write_log('early_bird_expiry_old:'.$early_bird_expiry_old);
	if( $early_bird_discount > 0 && $early_bird_expiry_old <> ''){
		$expiry_date = DateTime::createFromFormat( 'd/m/Y H:i:s', $early_bird_expiry_old.' 23:59:59' );
		if( $expiry_date ){
			$early_bird_expiry_new = $expiry_date->format( 'Y-m-d H:i:s' );
			// ccpa_write_log('early_bird_expiry_new:'.$early_bird_expiry_new);
		}
	}
	return array(
	    'id' => 0,						// no pricing record for workshop CPTs
	    'course_id' => $training_id,
	    'pricing_type' => '',			// not used
	    'price_gbp' => (float) get_post_meta( $training_id, 'online_pmt_amt', true ),
	    'price_usd' => (float) get_post_meta( $training_id, 'online_pmt_usd', true ),
	    'price_eur' => (float) get_post_meta( $training_id, 'online_pmt_eur', true ),
	    'price_aud' => (float) get_post_meta( $training_id, 'online_pmt_aud', true ),
	    'student_discount' => (float) get_post_meta( $training_id, 'student_discount', true ),
	    'early_bird_discount' => $early_bird_discount,
	    'early_bird_expiry' => $early_bird_expiry_new,		// Y-m-d H:i:s or empty
	    'early_bird_name' => get_post_meta( $training_id, 'earlybird_name', true ),
	    'created_at' => NULL,			// not used for workshop CPTs
	    'updated_at' => NULL,			// not used for workshop CPTs
	);
}