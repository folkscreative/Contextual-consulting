<?php
/**
 * Useful recordings related functions
 */

// returns all recordings as a set of options for a select
function cc_recordings_options($recording_id){
	// defaults to orderby descending date, ie newest at the top of the list
	$args = array(
        'post_type' => 'course',
        'numberposts' => -1,
        'meta_key' => '_course_type',
        'meta_value' => 'on-demand'
	);
	$recordings = get_posts($args);
	$html = '';
	foreach ($recordings as $recording) {
		$html .= '<option value="'.$recording->ID.'" '.selected($recording->ID, $recording_id, false).'>'.$recording->ID.': '.$recording->post_title.'</option>';
	}
	return $html;
}

// returns the html for a recording module for the view recordings page
// $recording id will have '-' and the module number appended for a module
function cc_recordings_module_html( $accordions, $collapsed, $mod_num, $mod_title, $recording_id, $num_views, $viewed_end, $viewing_time, $vimeo_id, $chat_module ){
	$html = '';
	// get the recording_id excluding any appended module number
	$training_id = strstr( $recording_id, '-', true);
	if( ! $training_id ){
		$training_id = $recording_id;
	}

	$show_chat = false;
    $zoom_chat = cc_zoom_chat_get( $training_id, $chat_module );
    if( $zoom_chat === NULL ){
        $zoom_chat = cc_zoom_chat_empty();
    }

	$accordion_body_class = '';
    if( $zoom_chat['chat'] <> '' ){
    	$show_chat = true;
    	$accordion_body_class = 'pt-0';
    }

	if($accordions){
		$html .= '<div class="accordion-item training-wrap dark-bg"><h2 class="accordion-header" id="module-'.$mod_num.'"><button class="accordion-button h4';
		if($collapsed){
			$html .= ' collapsed';
		}
		$html .= '" type="button" data-bs-toggle="collapse" data-bs-target="#module-body-'.$mod_num.'" aria-expanded="true" aria-controls="module-body-'.$mod_num.'">'.$mod_title.'</button></h2><div id="module-body-'.$mod_num.'" class="accordion-collapse collapse';
		if(!$collapsed){
			$html .= ' show';
		}
		$html .= '" aria-labelledby="module-'.$mod_num.'" data-bs-parent="#training-modules"><div class="accordion-body '.$accordion_body_class.'">';
	}else{
		$html .= '<div class="training-wrap dark-bg">';
		$html .= '<div class="training-wrap-inner">';
	}

    if( $show_chat ){
		$html .= '<div class="row zoom-chat-row"><div class="col-xl-8"><h6 class="d-none d-xl-block mb-0">&nbsp;</h6>';
    }else{
		$html .= '<div class="row"><div class="col-xl-10 offset-xl-1">';
    }

	$html .= '<div id="rec-video" class="hd-video-container HD1080 rec-video" data-chat="'.$chat_module.'"><iframe class="rec-iframe" width="1920" height="1080" src="https://player.vimeo.com/video/'.$vimeo_id.'" frameborder="0" allowfullscreen data-module="'.$mod_num.'" data-source="vimeo" data-recid="'.$recording_id.'" data-lastviewed="'.date('d/m/Y H:i:s').'" data-numviews="'.$num_views.'" data-viewedend="'.$viewed_end.'" data-viewingtime="'.$viewing_time.'" data-stats="'.$chat_module.'" ></iframe></div>';

	if( $show_chat ){
		$html .= '</div><div class="col-xl-4 zoom-chat-col">';
		$html .= '<h6 class="mb-0">Chat messages</h6>';
    	// $html .= '<div id="zoom-chat-'.$mod_num.'" class="zoom-chat-wrap"><div id="zoom-chats-'.$mod_num.'" class="zoom-chats">';
    	$html .= '<div id="zoom-chat-'.$mod_num.'" class="zoom-chat-wrap">';
        $chat_num = 0;
    	// $html .= '<p id="zc-'.$mod_num.'-'.$chat_num.'" data-time="0">00:00:00 Chat messages will be shown here</p>';
        $chats = maybe_unserialize( $zoom_chat['chat'] );
        foreach ($chats as $chat) {
        	$html .= '<p id="zc-'.$mod_num.'-'.$chat_num.'" data-time="'.$chat['secs'].'">'.$chat['time'].' '.$chat['who'].' '.$chat['msg'].'</p>';
        	$chat_num ++;
        }
	    // $html .= '</div></div>';
	    $html .= '</div>'; // zoom chat wrap
	    $html .= '</div></div>'; // col row
    }else{
	    $html .= '</div></div>'; // col row
    }

	return $html;
}

// are any vimeo IDs set for this recording?
// returns bool
function cc_recordings_vimeo_used($recording_id){
	// the original vimeo ID
	if( get_post_meta( $recording_id, 'vimeo_id', true ) <> '' ) return true;
	// the modules
	for ($i=0; $i < 10; $i++){
		if( get_post_meta($recording_id, 'module_vimeo_'.$i, true) <> '' ) return true;
	}
	return false;
}