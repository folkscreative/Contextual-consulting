<?php
/**
* Template Name: Zoom
*/

if(isset($_GET['code'])){
	$code = sanitize_text_field($_GET['code']);
	$string = base64_decode($code);
	list($workshop_id, $event_id, $user_id, $daft_number) = explode("|", $string);
	if($daft_number == $workshop_id * $workshop_id + $event_id * $event_id + 67345){
		// code ok

		// we should make sure that the user should have access at this stage ...

		
		if( $user_id <> 36474 ){ // Joy Ahearn

			if($event_id == 0){
			    $workshop_zoom = get_post_meta($workshop_id, 'workshop_zoom', true);
			    if($workshop_zoom <> ''){
			    	update_user_meta($user_id, 'zoomed w:'.$workshop_id.' e:null', date('Y-m-d'));
				   	wp_redirect( $workshop_zoom );
					exit;
			    }
			}else{
		        $event_zoom = get_post_meta($workshop_id, 'event_'.$event_id.'_zoom', true);
			    if($event_zoom <> ''){
			    	update_user_meta($user_id, 'zoomed w:'.$workshop_id.' e:'.$event_id, date('Y-m-d'));
				   	wp_redirect( $event_zoom );
					exit;
			    }
			}

		}
	}
}
wp_redirect( site_url() );
exit;