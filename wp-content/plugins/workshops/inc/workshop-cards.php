<?php
/**
 * Workshop cards
 * used on the home page for featured and upcoming workshops
 */

// return all the cards needed for the home page
function cc_wksp_cards_featured($exclude_wksp_id=0){
	$html = '';
	// look to see if there are any featured events in the future
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => 3,
		'orderby'   => 'meta_value_num',
		'order' => 'ASC',
		'meta_key'  => 'workshop_start_timestamp',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'workshop_start_timestamp',
				'value' => time(),
				'compare' => '>',
			),
			array(
				'key' => 'workshop_featured',
				'value' => 'yes',
				'compare' => '=',
			),
		),
	);
	if($exclude_wksp_id > 0){
		$args['post__not_in'] = array($exclude_wksp_id);
	}
	$featured_workshops = get_posts($args);
	/*
	if(empty($featured_workshops)){
		$sect_title = 'Upcoming training events';
	}else{
		$sect_title = 'Featured training events';
	}
	*/
	// do we also need some upcoming events (we always show 3 events if we can)?
	if(count($featured_workshops) < 3){
		$exclude_ids = array();
		if($exclude_wksp_id > 0){
			$exclude_ids[] = $exclude_wksp_id;
		}
		foreach ($featured_workshops as $workshop) {
			$exclude_ids[] = $workshop->ID;
		}
		$numberposts = 3 - count($featured_workshops);
		$args = array(
			'post_type' => 'workshop',
			'numberposts' => $numberposts,
			'orderby'   => 'meta_value_num',
			'order' => 'ASC',
			'meta_key'  => 'workshop_start_timestamp',
			'meta_query' => array(
				array(
					'key' => 'workshop_start_timestamp',
					'value' => time(),
					'compare' => '>',
				),
			),
		);
		if(!empty($exclude_ids)){
			$args['post__not_in'] = $exclude_ids;
		}
		$upcoming_workshops = get_posts($args);
	}else{
		$upcoming_workshops = array();
	}
	$workshops = array_merge($featured_workshops, $upcoming_workshops);

	$html = '<div class="upcoming-workshops">';

	if(empty($workshops)){
		$html .= '<p class="mx-3">Nothing scheduled. Please check again soon.</p>';
	}else{
		$html .= '<div class="row">';
		foreach ($workshops as $workshop) {
			$html .= cc_wksp_card($workshop);
		}
		$html .= '</div>';
	}

	/*
	$html .= '<div class="row text-center ">';
	$html .= '<div class="col-md-6 text-md-end">';
	$html .= '<a class="btn btn-training mt-3 mt-md-5" href="/workshop">All Live Training</a>';
	$html .= '</div><!-- .col -->';
	$html .= '<div class="col-md-6 text-md-start">';
	$html .= '<a class="btn btn-training mt-3 mt-md-5" href="/recording">On Demand Training</a>';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';

	$html .= '
	<div class="text-center">
		<a class="btn btn-training m-3 mt-md-5" href="/workshop">All Live Training</a>
		<a class="btn btn-training m-3 mt-md-5" href="/recording">On Demand Training</a>
		<a class="btn btn-training m-3 mt-md-5" href="/recording/an-introduction-to-the-act-model">FREE ACT Training</a>
	</div>
	';
	*/

	$html .= cc_custom_training_buttons();

	$html .= '</div><!-- .upcoming-workshops -->';
	return $html;
}

// returns one card for one workshop
// needs to be given a workshop post to play with
function cc_wksp_card($workshop){
	$image_html = cc_presenters_images($workshop->ID);

	$html = '<div class="col-12 col-lg-4 mb-5">';
	
	// is this a featured workshop?
	$workshop_featured = get_post_meta($workshop->ID, 'workshop_featured', true);
	$wrap_class = '';
	if($workshop_featured == 'yes'){
		$wrap_class = 'featured';
	}

	$html .= '<div class="training-wrap card text-start grad-bg h-100 workshop-'.$workshop->ID.' '.$wrap_class.'">';

	/*
	if($workshop_featured == 'yes'){
		$html .= '<div class="wkshp-triangle"><div class="wkshp-featured">Don\'t Miss It!</div></div>';
	}
	*/

	if($image_html <> ''){
		$html .= $image_html;
	}

	$html .= '<div class="wksp-text-wrap card-body d-flex flex-column">';
	$html .= '<div class="wksp-top-text">';

	$html .= '<div class="mb-2">';
	$html .= '<h4 class="workshop-title mb-2">'.$workshop->post_title.'</h4>';
	$workshop_subtitle = get_post_meta($workshop->ID, 'subtitle', true);
	/*
	if($workshop_subtitle <> ''){
		$html .= '<h6 class="workshop-subtitle mb-2">'.$workshop_subtitle.'</h6>';
	}
	*/
	// $html .= cc_topics_show($workshop->ID, 'xs');
	$html .= '</div>';

	$who = cc_presenters_names($workshop->ID, 'none');
	if($who <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
	}
	$where = get_post_meta($workshop->ID, 'event_1_venue_name', true);
	if($where <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-location-dot fa-fw"></i>In person: '.$where.'</div>';
	}
	$text_dates = get_post_meta( $workshop->ID, 'prettydates', true );
	if($text_dates <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$text_dates.'</div>';
	}
	$user_timezone = cc_timezone_get_user_timezone();
	$pretty_dates = workshop_calculated_prettydates($workshop->ID, $user_timezone);
	$sessions = '';
	$num_sess = workshop_number_sessions($workshop->ID);
	if($num_sess > 1){
		$sessions = $num_sess.' sessions: ';
	}
	if($pretty_dates['locale_date'] <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i>'.$sessions.$pretty_dates['locale_date'].'</div>';
	}
	/*
	$cat = get_post_meta($workshop->ID, 'category', true);
	if($cat <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-folder fa-fw"></i>'.$cat.'</div>';
	}
	*/

	$html .= '<div class="excerpt mt-3">';
	if($workshop_subtitle <> ''){
		$html .= '<p>'.$workshop_subtitle.' ... </p>';
	}
	$html .= '</div><!-- .excerpt -->';

	$html .= '</div><!-- .wksp-top-text -->';

	$html .= '<div class="workshop-btn-wrap mt-auto row">';
	/*
	if($excerpt == ''){
		$html .= '<a href="'.get_permalink($workshop->ID).'" class="btn btn-primary workshop-btn btn-details">Full details</a>';
	}
	*/
	// $html .= '<div class="col-6"><p><a class="training-link" href="'.get_permalink($workshop->ID).'">Read more &#187;</a></p></div><!-- col -->';
	// uses earlybird when it applies but we're not offering student prices ...
	// $pricing = cc_workshop_price($workshop->ID, cc_currency_get_user_currency());
	$html .= '<div class="col-12 text-end">';
	$html .= '<a class="btn btn-training btn-lge workshop-btn" href="'.get_permalink($workshop->ID).'">Full details</a>';
	// $html .= cc_registration_btn_html('w', $workshop->ID, $pricing['curr_found'], $pricing['raw_price'], $user_timezone, cc_timezone_get_user_timezone_pretty(0, $user_timezone), 'workshop-btn');
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .workshop-btn-wrap .row -->';

	$html .= '</div><!-- .wksp-text-wrap .card-body -->';

	$html .= '</div><!-- .training-wrap .card -->';
	$html .= '</div><!-- .col -->';
	return $html;
}

// the workshop card for the archive page
function cc_wksp_archive_card( $workshop, $section_wrap=true ){
    $id = 'wms-sect-'.mt_rand();
	$image_html = cc_presenters_images($workshop->ID);
	$workshop_featured = get_post_meta($workshop->ID, 'workshop_featured', true);
	$html = '';

	if( $section_wrap ){
		$html .= '<div id="'.$id.'" class="wms-section wms-section-std sml-padd white-bg">';
		$html .= '<div class="wms-sect-bg">';
		$html .= '<div class="container">';
		$html .= '<div class="row">';
		$html .= '<div class="col-12 col-md-10 offset-md-1">';
	}

	$wrap_class = '';
	if($workshop_featured == 'yes'){
		$wrap_class = 'featured';
	}
	$html .= '<div class="training-wrap text-start grad-bg workshop-'.$workshop->ID.' '.$wrap_class.'">';

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
	$html .= '<h4 class="workshop-title mb-2">'.$workshop->post_title.'</h4>';
	$workshop_subtitle = get_post_meta($workshop->ID, 'subtitle', true);
	if($workshop_subtitle <> ''){
		$html .= '<h6 class="workshop-subtitle mb-2">'.$workshop_subtitle.'</h6>';
	}
	// $html .= cc_topics_show($workshop->ID, 'xs');
	$html .= '</div>';

	$who = cc_presenters_names($workshop->ID, 'none');
	if($who <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i><span>'.$who.'</span></div>';
	}

	$where = get_post_meta($workshop->ID, 'event_1_venue_name', true);
	if($where <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-location-dot fa-fw"></i>In person: '.$where.'</div>';
	}

	$text_dates = get_post_meta( $workshop->ID, 'prettydates', true );
	if($text_dates <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$text_dates.'</div>';
	}
	$user_timezone = cc_timezone_get_user_timezone();
	$pretty_dates = workshop_calculated_prettydates($workshop->ID, $user_timezone);
	if($pretty_dates['locale_date'] <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i>'.$pretty_dates['locale_date'].'</div>';
	}

	$html .= '<div class="excerpt mt-3">';
	$excerpt = wms_get_excerpt_by_id($workshop->ID, '15', 'words', '');
	if($excerpt <> ''){
		$html .= '<p>'.$excerpt.' ... </p>';
	}
	$html .= '</div>';

	$html .= '</div><!-- .wksp-top-text -->';

	$html .= '<div class="workshop-btn-wrap mt-auto row">';
	// $html .= '<div class="col-6"><p><a class="training-link" href="'.get_permalink($workshop->ID).'">Read more &#187;</a></p></div><!-- col -->';
	// uses earlybird when it applies but we're not offering student prices ...
	// $pricing = cc_workshop_price($workshop->ID, cc_currency_get_user_currency());
	$html .= '<div class="col-12 text-end">';
	$html .= '<a class="btn btn-training btn-lge workshop-btn" href="'.get_permalink($workshop->ID).'">Full details</a>';
	// $html .= cc_registration_btn_html('w', $workshop->ID, $pricing['curr_found'], $pricing['raw_price'], $user_timezone, cc_timezone_get_user_timezone_pretty(0, $user_timezone), 'workshop-btn');
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .workshop-btn-wrap .row -->';

	$html .= '</div><!-- .wksp-text-wrap .card-body -->';
	
	$html .= '</div><!-- .col -->';

	$html .= '</div><!-- .row -->';

	$html .= '</div><!-- .training-wrap .card -->';

	if( $section_wrap ){
		$html .= '</div><!-- .col outer -->';
		$html .= '</div><!-- .row outer -->';
		$html .= '</div><!-- .container -->';
		$html .= '</div><!-- .wms-sect-bg -->';
		$html .= '</div><!-- .wms-section -->';
	}

	return $html;
}

// Featured workshop card for the my acct page
function cc_wksp_myacct_featured($workshop){
	$image_html = cc_presenters_images($workshop->ID);
	$html = '<div class="training-wrap text-start grad-bg workshop-'.$workshop->ID.'">';
	$html .= '<div class="row">';
	if($image_html == ''){
		$html .= '<div class="col-12">';
	}else{
		$html .= '<div class="col-12 col-lg-7">';
	}

	$html .= '<div class="wksp-text-wrap p-4">';
	$workshop_subtitle = get_post_meta($workshop->ID, 'subtitle', true);
	if($workshop_subtitle <> ''){
		$html .= '<h4 class="workshop-title mb-2">'.$workshop->post_title.'</h4>';
		$html .= '<h6 class="workshop-subtitle">'.$workshop_subtitle.'</h6>';
	}else{
		$html .= '<h4 class="workshop-title">'.$workshop->post_title.'</h4>';
	}

	if($image_html <> ''){
		$html .= '<div class="d-lg-none">'.$image_html.'</div>';
	}

	$who = cc_presenters_names($workshop->ID, 'none');
	if($who <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
	}
	$text_dates = get_post_meta( $workshop->ID, 'prettydates', true );
	if($text_dates <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$text_dates.'</div>';
	}
	$user_timezone = cc_timezone_get_user_timezone();
	$pretty_dates = workshop_calculated_prettydates($workshop->ID, $user_timezone);
	if($pretty_dates['locale_date'] <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i>'.$pretty_dates['locale_date'].'</div>';
	}

	$excerpt = wms_get_excerpt_by_id($workshop->ID, '15', 'words', '');
	if($excerpt <> ''){
		$html .= '<div class="excerpt mt-3"><p>'.$excerpt.' ... </p></div>';
	}
	// $html .= '<p><a href="'.get_permalink($workshop->ID).'">Read more &#187;</a></p></div>';

	$html .= '<div class="workshop-btn-wrap mt-3 text-end">';
	// uses earlybird when it applies but we're not offering student prices ...
	// $pricing = cc_workshop_price($workshop->ID, cc_currency_get_user_currency());
	// $html .= cc_registration_btn_html('w', $workshop->ID, $pricing['curr_found'], $pricing['raw_price'], $user_timezone, cc_timezone_get_user_timezone_pretty(0, $user_timezone), 'workshop-btn');
	$html .= '<a class="btn btn-training btn-lge workshop-btn" href="'.get_permalink($workshop->ID).'">Full details</a>';
	$html .= '</div><!-- .workshop-btn-wrap -->';

	$html .= '</div><!-- .wksp-text-wrap -->';
	
	$html .= '</div><!-- .col -->';

	if($image_html <> ''){
		$html .= '<div class="d-none d-lg-block col-lg-5">';
		$html .= $image_html;
		$html .= '</div>';
	}

	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .training-wrap -->';
	$html .= '</div><!-- .row outer -->';
	return $html;
}

// the workshop card for the upsell panel on the payment details page
function cc_wksp_upsell_card($workshop_id){
	$workshop = get_post($workshop_id);
	$image_html = cc_presenters_images($workshop_id);
	$html = '<div class="training-wrap text-start grad-bg workshop-'.$workshop_id.'">';
	$html .= '<div class="row">';
	if($image_html == ''){
		$html .= '<div class="col-12">';
	}else{
		$html .= '<div class="col-12 col-lg-5 order-lg-2">';
		$html .= $image_html;
		$html .= '</div><!-- .col -->';
		$html .= '<div class="col-12 col-lg-7 order-lg-1">';
	}
	$html .= '<div class="wksp-text-wrap p-3">';
	$workshop_subtitle = get_post_meta($workshop->ID, 'subtitle', true);
	if($workshop_subtitle <> ''){
		$html .= '<h5 class="workshop-title mb-2">'.$workshop->post_title.'</h5>';
		$html .= '<p class="workshop-subtitle">'.$workshop_subtitle.'</p>';
	}else{
		$html .= '<h5 class="workshop-title">'.$workshop->post_title.'</h5>';
	}
	$who = cc_presenters_names($workshop_id, 'none');
	if($who <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
	}
	$text_dates = get_post_meta( $workshop->ID, 'prettydates', true );
	if($text_dates <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$text_dates.'</div>';
	}
	$user_timezone = cc_timezone_get_user_timezone();
	$pretty_dates = workshop_calculated_prettydates($workshop->ID, $user_timezone);
	if($pretty_dates['locale_date'] <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-calendar-days fa-fw"></i>'.$pretty_dates['locale_date'].'</div>';
	}
	$excerpt = wms_get_excerpt_by_id($workshop->ID, '15', 'words', '');
	if($excerpt <> ''){
		$html .= '<p>'.$excerpt.' ... </p>';
	}
	$html .= '<p><a href="'.get_permalink($workshop->ID).'" target="_blank" title="Opens in new window">Read more &#187;</a></p>';
	$html .= '</div><!-- .wksp-text-wrap -->';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .training-wrap -->';
	return $html;
}

// the workshop card for the resource hub
function cc_wksp_resknow_hub_card( $workshop_id ){
	$workshop = get_post($workshop_id);

	$html = '<div class="card mb-3">';
	$feat_img_url = get_the_post_thumbnail_url( $workshop_id, 'post-thumb' );
	$html .= '<img src="'.$feat_img_url.'" class="card-img-top" alt="'.$workshop->post_title.' featured image">';
	$html .= '<div class="card-body grad-bg">';

	$html .= '<div class="row">';
	$html .= '<div class="col-6">';
	// $html .= cc_topics_show($workshop_id, 'xs');
	$html .= '</div><!-- .col -->';
	$html .= '<div class="col-6 text-end small">';
	$text_dates = get_post_meta( $workshop->ID, 'prettydates', true );
	if($text_dates <> ''){
		$html .= $text_dates;
	}else{
		$user_timezone = cc_timezone_get_user_timezone();
		$pretty_dates = workshop_calculated_prettydates($workshop->ID, $user_timezone);
		if($pretty_dates['locale_date'] <> ''){
			$html .= $pretty_dates['locale_date'];
		}
	}
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';

	$html .= '<h5 class="workshop-title card-title">'.$workshop->post_title.'</h5>';
	$presenters = cc_presenters_names($workshop->ID, 'none');
	if( $presenters == '' ){
		$workshop_subtitle = get_post_meta($workshop->ID, 'subtitle', true);
		if($workshop_subtitle <> ''){
			$html .= '<p class="card-text">'.$workshop_subtitle.'</p>';
		}
	}else{
		$html .= '<p class="card-text">'.$presenters.'</p>';
	}
	$html .= '<a href="'.get_permalink($workshop->ID).'" class="btn btn-primary btn-sm">Read more</a>';

	$html .= '</div>';
	$html .= '</div>';

	return $html;
}