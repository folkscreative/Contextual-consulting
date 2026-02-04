<?php
/**
 * Next workshop
 * the home page next workshop display
 * added in v2
 */

// returns the id of the next workshop
function cc_wksp_next_workshop_id(){
	// find the next workshop
	// workshop_start_timestamp contains the correct day but an incorrect time
	// therefore we'll look for 5 workshops that start after 00:00 today and then work out which to actually show
	// that's not right!
	// a workshop may be running with events still open for registration and that needs to be shown even if the start was a while back
	// so let's get all workshops that have started since a year ago and then pick off the first of these to show
	$year_ago = strtotime('-1 year');
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => -1,
		'orderby'   => 'meta_value_num',
		'order' => 'ASC',
		'meta_key'  => 'workshop_start_timestamp',
		'meta_value' => $year_ago,
		'meta_compare' => '>',
		'fields' => 'ids',
	);
	$next_workshops = get_posts($args);
	if(empty($next_workshops)){
		return false;
	}else{
		foreach ($next_workshops as $workshop_id) {
			if(workshops_show_this_workshop($workshop_id)){
				return $workshop_id;
			}
		}
	}
	return false;
}

// returns the html
function cc_wksp_next_workshop($workshop_id){
	$html = '<div class="next-workshop">';
	// $html .= '<h3 class="mb-5">Next Live Training</h3>';
	if($workshop_id == false){
		$html .= '<p class="mx-3">Nothing scheduled. Please check again soon.</p>';
	}else{		
		$image_html = cc_presenters_images($workshop_id);

		$html .= '<div class="training-wrap text-start grad-bg">';

		$html .= '<div class="row">';
		if($image_html == ''){
			$html .= '<div class="col-12">';
		}else{
			$html .= '<div class="col-12 col-lg-5 order-lg-2">';
			$html .= $image_html;
			$html .= '</div><!-- .col -->';
			$html .= '<div class="col-12 col-lg-7 order-lg-1">';
		}

		$html .= '<div class="wksp-text-wrap h-100 p-4 d-flex flex-column">';
		$html .= '<div class="wksp-top-text">';

		$html .= '<div class="mb-2">';
		$html .= '<h2 class="workshop-title mb-2">'.get_the_title($workshop_id).'</h2>';
		$workshop_subtitle = get_post_meta($workshop_id, 'subtitle', true);
		if($workshop_subtitle <> ''){
			$html .= '<h4 class="workshop-subtitle mb-2">'.$workshop_subtitle.'</h4>';
		}
		// $html .= cc_topics_show($workshop_id, 'xs');
		$html .= '</div>';

		/*
		if($image_html <> ''){
			$html .= '<div class="d-lg-none">';
			$html .= $image_html;
			$html .= '</div>';
		}
		*/

		$who = cc_presenters_names($workshop_id, 'none');
		if($who <> ''){
			$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i><span>'.$who.'</span></div>';
		}
		$where = get_post_meta($workshop_id, 'event_1_venue_name', true);
		if($where <> ''){
			$html .= '<div class="bullet mb-2"><i class="fa-solid fa-location-dot fa-fw"></i><span>In person: '.$where.'</span></div>';
		}
		$text_dates = get_post_meta( $workshop_id, 'prettydates', true );
		if($text_dates <> ''){
			$html .= '<div class="bullet mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i><span>'.$text_dates.'</span></div>';
		}
		$user_timezone = cc_timezone_get_user_timezone();
		$pretty_dates = workshop_calculated_prettydates($workshop_id, $user_timezone);
		if($pretty_dates['locale_date'] <> ''){
			$html .= '<div class="bullet mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i><span>'.$pretty_dates['locale_date'].'</span></div>';
			/*
			if($pretty_dates['locale_start_time'] <> ''){
				$html .= '<div class="bullet mb-2"><i class="fa-solid fa-clock fa-fw"></i>Starts: '.$pretty_dates['locale_start_time'].' <small>('.$user_timezone.' <a class="cc-timezone-changer" href="javascript:void(0);">change timezone</a>)</small></div>';
			}
			*/
		}
		/*
		$cat = get_post_meta($workshop_id, 'category', true);
		if($cat <> ''){
			$html .= '<div class="bullet mb-2"><i class="fa-solid fa-folder fa-fw"></i>'.$cat.'</div>';
		}
		*/

		$excerpt = wms_get_excerpt_by_id($workshop_id, '15', 'words', '');
		// $subtitle = get_post_meta($workshop_id, 'subtitle', true);
		if($excerpt <> ''){
			// $html .= '<div class="excerpt mt-3">'.$excerpt.' ... <a href="'.get_permalink($workshop_id).'">Read More <i class="fa-solid fa-angles-right"></i></a></div>';
			$html .= '<div class="excerpt mt-3"><p>'.$excerpt.' ...</p></div>';
		}

		$html .= '</div><!-- .wksp-top-text -->';

		$html .= '<div class="workshop-btn-wrap mt-auto row">';
		/*
		if($excerpt == ''){
			$html .= '<a href="'.get_permalink($workshop_id).'" class="btn btn-primary workshop-btn btn-details">Full details</a>';
		}
		*/
		// $html .= '<div class="col-6"><p><a class="training-link" href="'.get_permalink($workshop_id).'">Read more &#187;</a></p></div>';
		// we're using the earlybird price if it applies but not offering the student price!
		// $pricing = cc_workshop_price($workshop_id, cc_currency_get_user_currency());
		$html .= '<div class="col-12 text-end">';
		$html .= '<a class="btn btn-training btn-lge workshop-btn" href="'.get_permalink($workshop_id).'">Full details</a>';
		// $html .= cc_registration_btn_html('w', $workshop_id, $pricing['curr_found'], $pricing['raw_price'], $user_timezone, cc_timezone_get_user_timezone_pretty(0, $user_timezone), 'workshop-btn');
		$html .= '</div>';
		$html .= '</div><!-- .workshop-btn-wrap -->';

		$html .= '</div><!-- .wksp-text-wrap -->';

		$html .= '</div><!-- .col -->';

		$html .= '</div><!-- .row -->';

		$html .= '</div><!-- .training-wrap -->';
	}
	$html .= '</div><!-- .next-workshop -->';
	return $html;
}
	

