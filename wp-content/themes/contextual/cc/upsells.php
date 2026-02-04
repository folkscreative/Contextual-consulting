<?php
/**
 * Upsells
 * stuff put in here when upsells were expanded from workshops to all training (Nov 2024)
 * ##### NOTE: we still refer to upsells as "workshops" but they can now also be recordings!! #####
 */

// get upsell by training_id
// NULL if not there
function cc_upsells_get_upsell( $training_id ){
	global $wpdb;
	$training_id = absint($training_id);
	if($training_id == 0) return NULL;
	$table_name = $wpdb->prefix.'upsells';
	$sql = "SELECT * FROM $table_name WHERE (expiry = '0000-00-00 00:00:00' OR expiry > NOW()) AND (workshop_1_id = $training_id OR workshop_2_id = $training_id)";
	$poss_upsells = $wpdb->get_results($sql, ARRAY_A);
	foreach ($poss_upsells as $poss_upsell) {
		if($training_id == $poss_upsell['workshop_1_id']){
			$other_training_id = $poss_upsell['workshop_2_id'];
		}else{
			$other_training_id = $poss_upsell['workshop_1_id'];
		}
		if( course_training_type( $other_training_id ) == 'workshop' ){
			if( workshops_show_this_workshop( $other_training_id ) ){
				// note that this could now be a workshop that redirects to a recording on registration ... do we care?
				return $poss_upsell;
			}
		}else{
			// is recording available for registration?
			if( get_post_status( $other_training_id ) == 'publish' && get_post_meta( $other_training_id, '_course_status', true) <> 'closed' ){
				return $poss_upsell;
			}
		}
	}
	return NULL;
}

// returns the other training and discount for a training_id
// returns NULL if nothing found
function cc_upsells_offer( $training_id ){
	$upsell = cc_upsells_get_upsell( $training_id );
	if( $upsell === NULL ) return NULL;
	$offer = array();
	$offer['workshop_1_id'] = $training_id;
	if($upsell['workshop_1_id'] == $training_id){
		$offer['other_workshop_id'] = $upsell['workshop_2_id'];
	}else{
		$offer['other_workshop_id'] = $upsell['workshop_1_id'];
	}
	$offer['discount'] = $upsell['discount'];
	$offer['expiry'] = $upsell['expiry'];
	return $offer;
}

// the upsell field used on the workshop and recording post edit page
function cc_upsells_backend_field( $training_id ){
	$html = '';
	$post_type = get_post_type( $training_id );
	
	// label column on recordings post type (no longer used!)
	if( $post_type == 'recording' ){
		$html .= '<th class="metabox_label_column"><label>Upsell</label></th>';
		$html .= '<td><table><tr>';
		$input_class = "regular-text";
	}else{
		$input_class = "form-control";
	}

	$curr_upsell = cc_upsells_offer( $training_id );
	if( $curr_upsell == null ){
		$upsell_workshop_disp = '';
		$upsell_workshop_id = '';
		$upsell_discount = 0;
		$upsell_expiry_date = '';
		$upsell_expiry_time = '';
	}else{
		$upsell_workshop_disp = $curr_upsell['other_workshop_id'].': ('.course_training_type( $curr_upsell['other_workshop_id'] ).') '.get_the_title( $curr_upsell['other_workshop_id'] );
		$upsell_workshop_id = $curr_upsell['other_workshop_id'];
		$upsell_discount = $curr_upsell['discount'];
		if( $curr_upsell['expiry'] == '0000-00-00 00:00:00' ){
			$upsell_expiry_date = '';
			$upsell_expiry_time = '';
		}else{
			$upsell_expiry_date = date( 'd/m/Y', strtotime( $curr_upsell['expiry'] ) );
			$upsell_expiry_time = date( 'H:i', strtotime( $curr_upsell['expiry'] ) );
		}
	}

	// the upsell training id (inc search)
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '<div class="col-4"><label for="upsell_workshop" class="form-label">Upsell training</label>';
	}else{
		$html .= '<td><label for="upsell_workshop">Upsell training</label><br>';
	}
	if( $post_type == 'course' ){
		$html .= '<div class="upsell-workshop-wrap"><input type="text" id="upsell-workshop-search" class="'.$input_class.'" placeholder="Search for training ..." value="'.$upsell_workshop_disp.'" data-training_id="'.$training_id.'" data-was="'.$upsell_workshop_disp.'"></div></div><div class="col-1"><br><a href="javascript:void(0);" id="upsell-workshop-del" class="text-end text-danger"><i class="fa-solid fa-trash-can"></i></a><input type="hidden" id="upsell-workshop-id" name="upsell_workshop" value="'.$upsell_workshop_id.'">';	
	}else{
		$html .= '<div class="upsell-workshop-wrap"><input type="text" id="upsell-workshop-search" class="'.$input_class.'" placeholder="Search for training ..." value="'.$upsell_workshop_disp.'" data-training_id="'.$training_id.'" data-was="'.$upsell_workshop_disp.'"> <a href="javascript:void(0);" id="upsell-workshop-del" class="text-end text-danger"><i class="fa-solid fa-trash-can"></i></a><input type="hidden" id="upsell-workshop-id" name="upsell_workshop" value="'.$upsell_workshop_id.'"></div>';
	}
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '</div>';
	}else{
		$html .= '</td>';
	}

	if( $post_type == 'recording' ){
		$input_class = '';
	}

	// the upsell discount 
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '<div class="col-3"><label for="upsell_discount" class="form-label">Upsell Discount %</label>';
	}else{
		$html .= '<td><label for="upsell_discount">Upsell Discount %</label><br>';
	}
	$html .= '<input type="number" id="upsell_discount" name="upsell_discount" class="'.$input_class.'" min="0" step="0.0001" max="100" value="'.$upsell_discount.'">';
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '</div>';
	}else{
		$html .= '</td>';
	}

	// expiry date
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '<div class="col-2"><label for="upsell_expiry_date" class="form-label">Expiry date if req.</label>';
	}else{
		$html .= '<td><label for="upsell_expiry_date">Expiry date if req.</label><br>';
	}
	$html .= '<input type="text" id="upsell_expiry_date" name="upsell_expiry_date" class="'.$input_class.'" value="'.$upsell_expiry_date.'" placeholder="DD/MM/YYYY" />';
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '</div>';
	}else{
		$html .= '</td>';
	}

	// expiry time
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '<div class="col-2"><label for="upsell_expiry_time" class="form-label">Expiry time if req</label>';
	}else{
		$html .= '<td><label for="upsell_expiry_time">Expiry time if req</label><br>';
	}
	$html .= '<input type="text" id="upsell_expiry_time" name="upsell_expiry_time" class="'.$input_class.'" value="'.$upsell_expiry_time.'" placeholder="HH:MM" />';
	if( $post_type == 'workshop' || $post_type == 'course' ){
		$html .= '</div>';
	}else{
		$html .= '</td>';
	}

	if( $post_type == 'recording' ){
		$html .= '</tr></table></td>';
	}
	                        
	return $html;
}

// the backend upsell search
// switched from recording to course
add_action('wp_ajax_upsell_training_search', 'cc_upsell_training_search');
function cc_upsell_training_search(){
	global $wpdb;
    check_ajax_referer('upsell_search_nonce', 'security');
    $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
    $training_id = isset($_POST['training_id']) ? absint($_POST['training_id']) : 0;

    // get all other training that matches the search term ... we'll exclude some of them in a mo
	$args = array(
		'post_type' => array( 'workshop', 'course'),
		's' => $term,
		'numberposts' => -1,
		'exclude' => array( $training_id ),
	);
	$trainings = get_posts( $args );

	$wanted = [];
	foreach ($trainings as $training) {
		if( $training->post_type == 'workshop' ){
			if( get_post_meta( $training->ID, 'workshop_timestamp', true ) < time() ) continue;
		}elseif( get_post_meta( $training->ID, '_course_type', true ) == 'on-demand' ){
			if( get_post_meta( $training->ID, '_course_status', true) == 'closed' ) continue;
		}
		if( cc_upsells_get_upsell( $training->ID ) === NULL ){
			$wanted[] = $training;
		}
	}

	// we only want the first 10
	$results = [];
	for ($i=0; $i < 10; $i++) { 
		if( ! isset( $wanted[$i] ) ) break;
		if( $wanted[$i]->post_type == 'workshop' ){
			$course_type = 'live';
		}else{
			$course_type = 'on-demand';
		}
        $results[] = [
            'id' => $wanted[$i]->ID,
            'title' => $wanted[$i]->post_title,
            'post_type' => $course_type,
        ];
	}

    wp_send_json($results);
}

// the upsell banner shown on the single training pages
function cc_upsell_training_banner( $training_id ){
	$html = '';
	$curr_upsell = cc_upsells_offer( $training_id );
	if( $curr_upsell <> null && $curr_upsell['discount'] > 0 ){
		// float used to lose the unwanted zeros off the end
		$html .= '
			<div class="upsell-banner-wrap">
				<div class="upsell-banner text-center">
					<h3 class="upsell-banner-header">Save up to '.(float) $curr_upsell['discount'].'%</h3>
					<p class="upsell-banner-text">Register for this training and, at the same time, also register for <a href="'.get_permalink( $curr_upsell['other_workshop_id'] ).'" target="_blank" title="Opens in new window">'.get_the_title( $curr_upsell['other_workshop_id'] ).'</a> and save '.(float) $curr_upsell['discount'].'%</p>
				</div>
			</div>';
	}
	return $html;
}

// get all past and future workshops as options for a select (on the payment edit backend page)
// looks for selected_id first and failing that looks for selected_name
// $post_type can be an array :-)
function cc_upsell_all_workshop_options( $selected_id=0, $selected_name='', $post_type='workshop' ){
	$response = array(
		'options' => '',
		'selected_id' => 0,
	);
	if($selected_id == 0){
		if($selected_name <> ''){
			$selected_training = cc_upsell_get_page_by_title( $selected_name, OBJECT, $post_type );
			if($selected_training){
				$selected_id = $selected_training->ID;
			}
		}
	}
	$response['selected_id'] = $selected_id;
	$args = array(
		'post_type' => $post_type,
		'numberposts' => -1,
	);
	$trainings = get_posts($args);
	foreach ($trainings as $training) {
		$response['options'] .= '<option value="'.$training->ID.'" '.selected($selected_id, $training->ID, false).'>'.$training->ID.': ';
		if( is_array( $post_type ) ){
			$response['options'] .= '('.$training->post_type.') ';
		}
		$response['options'] .= $training->post_title.'</option>';
	}
	return $response;
}

// updates or inserts an upsell
function cc_upsells_update_upsell( $training_id, $other_training_id, $discount, $expiry ){
	global $wpdb;
	$table_name = $wpdb->prefix.'upsells';
	$upsell = cc_upsells_get_upsell( $training_id );
	if($upsell === NULL){
		// insert
		$data = array(
			'workshop_1_id' => $training_id,
			'workshop_2_id' => $other_training_id,
			'discount' => $discount,
			'expiry' => $expiry,
		);
		$wpdb->insert( $table_name, $data, array('%d', '%d', '%f', '%s') );
		return $wpdb->insert_id;
	}else{
		if($upsell['workshop_1_id'] == $training_id){
			$upsell['workshop_2_id'] = $other_training_id;
		}else{
			$upsell['workshop_1_id'] = $other_training_id;
		}
		$upsell['discount'] = $discount;
		$upsell['expiry'] = $expiry;
		$where = array(
			'id' => $upsell['id'],
		);
		return $wpdb->update( $table_name, $upsell, $where, array('%d', '%d', '%d', '%f', '%s') );
	}
}
