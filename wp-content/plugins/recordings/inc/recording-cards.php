<?php
/**
 * Recording cards
 */

// used on the archive page
function recording_card( $recording_id, $section_wrap=true ){
    $id = 'wms-sect-'.mt_rand();
	$image_html = cc_presenters_images($recording_id);

	// recording_for_sale can be '' = normal, 'public' = no purchase needed, 'closed' = not avail to purchase, 'unlisted' = available to purchase but not shown in the list (you need to have been given a link to it!)
	// closed and unlisted recordings will (should!) not be sent to this function
	$recording_for_sale = get_post_meta($recording_id, 'recording_for_sale', true);

	// much simplified now (v1.65) as we only show a read more link
	// all the stuff about whether the logged in user can watch it or can it be registered for is no longer needed 
	/*
	$allow_watch_now = false;
	$recording_access = ccrecw_user_can_view( $recording_id );
	if($recording_for_sale == 'public' || ( cc_users_is_valid_user_logged_in() && $recording_access['access'] ) ){
		$allow_watch_now = true;
	}

	$recording_currency = cc_currency_get_user_currency();
	$raw_price = 0;
	if(!$allow_watch_now && $recording_for_sale == ''){
		// work out the pricing
		$recording_pricing = cc_recording_price($recording_id, $recording_currency);
		$raw_price = $recording_pricing['raw_price'];
		$recording_currency = $recording_pricing['curr_found'];
	}

	// registration/watch now button
	$reg_btn = '';
	if(!$allow_watch_now && $recording_for_sale <> 'closed'){
		$registration_link = get_post_meta( $recording_id, 'registration_link', true ); // the online workshop's link ... no longer used
		if($registration_link <> ''){
			$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn watch-now-btn btn-training btn-lge">Access training</a>';
		}
		$registration_link_id = get_post_meta( $recording_id, 'registration_link_id', true ); // Oli's ID
		if($registration_link_id <> ''){
			$registration_link = 'https://app-4.globalpodium.com/watch/'.$registration_link_id;
			$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn watch-now-btn btn-training btn-lge" target="_blank">Access training</a>';
		}
		$recording_url = get_post_meta( $recording_id, 'recording_url', true ); // media library file
		if($recording_url <> ''){
			$reg_btn = '<a href="javascript:void(0)" class="btn archive-reg-btn btn-training btn-lge" data-recid="'.$recording_id.'" data-raw="'.$raw_price.'" data-curr="'.$recording_currency.'">Register now</a>';
		}
		if( cc_recordings_vimeo_used($recording_id) ){
			$reg_btn = '<a href="javascript:void(0)" class="btn archive-reg-btn btn-training btn-lge" data-recid="'.$recording_id.'" data-raw="'.$raw_price.'" data-curr="'.$recording_currency.'">Register now</a>';
		}
	}elseif($allow_watch_now){
		$reg_btn = '<a href="/watch-recording?id='.$recording_id.'" class="btn watch-now-btn btn-training btn-lge">Watch now</a>';
	}

	if ($reg_btn == ''){
		// no point in showing this one ...
		return '';
	}
	*/

	$html = '';

	if( $section_wrap ){
		$html .= '<div id="'.$id.'" class="wms-section wms-section-std sml-padd white-bg">';
		$html .= '<div class="wms-sect-bg">';
		$html .= '<div class="container">';
		$html .= '<div class="row">';
		$html .= '<div class="col-12 col-md-10 offset-md-1">';
	}

	if(is_sticky($recording_id)){
		$wrap_class = 'featured';
	}else{
		$wrap_class = '';
	}

	$html .= '<div class="training-wrap text-start grad-bg workshop-'.$recording_id.' '.$wrap_class.'">';
	/*
	if(is_sticky($recording_id)){
		$html .= '<div class="wkshp-triangle"><div class="wkshp-featured">Don\'t Miss It!</div></div>';
	}
	*/

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
	$html .= '<h4 class="workshop-title mb-2">'.get_the_title($recording_id).'</h4>';
	$workshop_subtitle = get_post_meta($recording_id, 'subtitle', true);
	if($workshop_subtitle <> ''){
		$html .= '<h6 class="workshop-subtitle mb-2">'.$workshop_subtitle.'</h6>';
	}
	// $html .= cc_topics_show($recording_id, 'xs');
	$html .= '</div>';

	$who = cc_presenters_names($recording_id, 'none');
	if($who <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
	}
	$availability = get_post_meta($recording_id, 'availability', true);
	if($availability <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-video fa-fw"></i>'.$availability.'</div>';
	}
	$duration = get_post_meta($recording_id, 'duration', true);
	if($duration <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$duration.'</div>';
	}
	$excerpt = wms_get_excerpt_by_id($recording_id, '15', 'words', '');
	if($excerpt <> ''){
		$html .= '<div class="excerpt mt-3"><p>'.$excerpt.' ... </p></div>';
	}

	$html .= '</div><!-- .wksp-top-text -->';

	$html .= '<div class="recording-btn-wrap mt-auto row">';
	// $html .= '<div class="col-6"><p><a class="training-link" href="'.get_permalink($recording_id).'">Read more &#187;</a></p></div><!-- col -->';
	$html .= '<div class="col-12 text-end">';
	// $html .= $reg_btn;
	$html .= '<a class="btn btn-training btn-lge recording-btn" href="'.get_permalink($recording_id).'">Full details</a>';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .recording-btn-wrap .row -->';

	$html .= '</div><!-- .wksp-text-wrap -->';
	
	$html .= '</div><!-- .col -->';

	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .training-wrap -->';

	if( $section_wrap ){
		$html .= '</div><!-- .col outer -->';
		$html .= '</div><!-- .row outer -->';
		$html .= '</div><!-- .container -->';
		$html .= '</div><!-- .wms-sect-bg -->';
		$html .= '</div><!-- .wms-section -->';
	}
	
	return $html;
}

// get a featured recording for the my account page
function recording_cards_get_featured($exclude=array()){
	$sticky_posts = get_option('sticky_posts');
	if($sticky_posts){
		$args = array(
			'numberposts' => 1,
			'post_type' => 'recording',
			'orderby' => 'rand',
			'include' => $sticky_posts,
			'exclude' => $exclude,
		);
		return get_posts($args);
	}else{
		return array();
	}
}

// featured recording card for the my acct page
function recording_card_featured($recording_id){
	$image_html = cc_presenters_images($recording_id);
	$html = '<div class="training-wrap text-start workshop-'.$recording_id.'">';
	if(is_sticky($recording_id)){
		$html .= '<div class="wkshp-triangle"><div class="wkshp-featured">Don\'t Miss It!</div></div>';
	}

	$html .= '<div class="row">';
	if($image_html == ''){
		$html .= '<div class="col-12">';
	}else{
		$html .= '<div class="col-12 col-lg-7">';
	}

	$html .= '<div class="wksp-text-wrap p-4">';
	$workshop_subtitle = get_post_meta($recording_id, 'subtitle', true);
	if($workshop_subtitle <> ''){
		$html .= '<h4 class="workshop-title mb-2">'.get_the_title($recording_id).'</h4>';
		$html .= '<h6 class="workshop-subtitle">'.$workshop_subtitle.'</h6>';
	}else{
		$html .= '<h4 class="workshop-title">'.get_the_title($recording_id).'</h4>';
	}

	if($image_html <> ''){
		$html .= '<div class="d-lg-none">'.$image_html.'</div>';
	}

	$who = cc_presenters_names($recording_id, 'none');
	if($who <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
	}
	$duration = get_post_meta($recording_id, 'duration', true);
	if($duration <> ''){
		$html .= '<div class="bullet mb-2"><i class="fa-solid fa-video fa-fw"></i>'.$duration.'</div>';
	}
	$excerpt = wms_get_excerpt_by_id($recording_id, '15', 'words', '');
	if($excerpt <> ''){
		// $html .= '<div class="excerpt mt-3"><p>'.$excerpt.' ... </p><p><a href="'.get_permalink($recording_id).'">Read more &#187;</a></p></div>';
		$html .= '<div class="excerpt mt-3"><p>'.$excerpt.' ... </p></div>';
	}

	$html .= '<div class="recording-btn-wrap mt-3 text-end">';
	/*
	if($excerpt == ''){
		$html .= '<a href="'.get_permalink($recording_id).'" class="btn btn-primary recording-btn btn-details">Full details</a>';
	}
	$html .= $reg_btn;
	*/
	$html .= '<a class="btn btn-training btn-lge recording-btn" href="'.get_permalink($recording_id).'">Full details</a>';
	$html .= '</div><!-- .recording-btn-wrap -->';

	$html .= '</div><!-- .wksp-text-wrap -->';
	
	$html .= '</div><!-- .col -->';

	if($image_html <> ''){
		$html .= '<div class="d-none d-lg-block col-lg-5">';
		$html .= $image_html;
		$html .= '</div>';
	}

	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .training-wrap -->';
	return $html;
}