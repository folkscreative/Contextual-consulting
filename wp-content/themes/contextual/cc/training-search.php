<?php
/**
 * Training Search Functions
 */

// the training search form to be included on the ACT Therapy Training page
// this will sit within a section shortcode
add_shortcode( 'training_search_form', 'cc_training_search_form' );
function cc_training_search_form(){
	$html = '<a href="javascript:void(0);" class="cc-training-search-btn btn btn-training btn-lg">Filter &amp; search training</a>';
	// form moved to the footer (see the next function)
	// $html = '<div class="training-search-form">';
	// $html .= '<h5 class="mb-0"><a href="javascript:void(0);" id="tsf-form-get">Filter and search all training <i id="tsf-get-icon" class="tsf-get-icon fa-solid fa-angle-down"></i><i id="tsf-got-icon" class="tsf-got-icon fa-solid fa-angle-up"></i></a></h5>';
	// $html .= '<div id="tsf-wrap" class="tsf-wrap empty mt-4"><p class="mt-5 text-center"><i class="fa-solid fa-spinner fa-spin-pulse fa-4x"></i></p></div>';
	// $html .= '</div>';
	return $html;
}

// Hook into WordPress to run the function after the page content is loaded
add_action( 'wp_footer', 'cc_training_form_footer' );
// adds the form to the page if the shortcodes are used
function cc_training_form_footer() {
    global $post;
    // Check if is a trainig archive page or the current post content contains the shortcodes
    if ( is_post_type_archive( array( 'recording', 'workshop' ) ) 
    	|| isset( $post->post_content ) && (
	    	has_shortcode( $post->post_content, 'training_search_form' ) 
	    	|| has_shortcode( $post->post_content, 'training_buttons' )
	    ) ) {
        // The HTML content to add to the bottom of the page
        $training_search_panel = '
		    <div id="training-search-panel" class="training-search-panel slider bg-light mb-3">
		    	<div class="p-5">
			        <!-- <span id="cc-training-search-close" class="training-search-close">&times;</span> -->
			        <div id="cc-training-search-content"><p class="mt-5 text-center"><i class="fa-solid fa-spinner fa-spin-pulse fa-4x"></i></p></div>
			    </div>
		    </div>
        ';
        // output the HTML at the bottom of the page
        echo $training_search_panel;
    }
}

// get the content of the training search form when a user requests it
add_action( 'wp_ajax_cc_tsf_get', 'cc_training_search_form_get' );
add_action( 'wp_ajax_nopriv_cc_tsf_get', 'cc_training_search_form_get' );
function cc_training_search_form_get(){
	$response = array(
		'status' => 'ok',
		'html' => cc_training_search_sidebar( cc_training_search_params(), 'yes' ),
	);
	/*
	$response['html'] = '<form id="tsf-form" method="get" action="'.wms_find_page( 'page-training-search.php' ).'" onsubmit="return submitTSF()">';
	$response['html'] .= '<div class="row">';

	$taxes = array(
		array(
			'initial' => 'i',
			'name' => 'Issues',
			'taxonomy' => 'tax_issues',
		),
		array(
			'initial' => 'a',
			'name' => 'Approaches',
			'taxonomy' => 'tax_approaches',
		),
		array(
			'initial' => 'r',
			'name' => 'Resource types',
			'taxonomy' => 'tax_rtypes',
		),
		array(
			'initial' => 'o',
			'name' => 'Others',
			'taxonomy' => 'tax_others',
		),
	);

	foreach ($taxes as $tax) {
		$response['html'] .= '<div class="tsf-col col-md-4 col-lg-2">';
		$response['html'] .= '<h6 class="mb-1">'.$tax['name'].'</h6>';
		$terms = get_terms( array( 'taxonomy' => $tax['taxonomy'] ) );
		$prefix = $tax['initial'].'_';
		$count_terms = 0;
		foreach ($terms as $term) {
			$count_terms ++;
			if( $count_terms == 11 ){
				$response['html'] .= '<div id="" class="tsf-more">';
			}
			$response['html'] .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$term->slug.'" id="'.$prefix.$term->slug.'" name="'.$tax['initial'].'[]"><label class="form-check-label" for="'.$prefix.$term->slug.'">'.$term->name.'</label></div>';
		}
		if( $count_terms > 10 ){
			$response['html'] .= '</div><p class="small m-0"><a href="javascript:void(0);" id="" class="tsf-more-trigger">Show all <i class="fa-solid fa-angle-down"></i></a><a href="javascript:void(0);" id="" class="tsf-less-trigger">Show fewer <i class="fa-solid fa-angle-up"></i></a></p>';
		}
		$response['html'] .= '</div>';
	}

	$response['html'] .= '<div class="col-md-8 col-lg-4">';
	$response['html'] .= '<h6 class="mb-1">Training type</h6>';
	$response['html'] .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="live" id="t_live" name="t[]"><label class="form-check-label" for="t_live">Live training</label></div>';
	$response['html'] .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="on-demand" id="t_on_demand" name="t[]"><label class="form-check-label" for="t_on_demand">On-demand training</label></div>';
	$response['html'] .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="free" id="t_free" name="t[]"><label class="form-check-label" for="t_free">Free training</label></div>';

	$response['html'] .= '<h6 class="mb-1 mt-3">Search for</h6>';
	$response['html'] .= '<input id="tsf-keyword" class="form-control form-control-lg mb-5" type="text" name="k">';

	$response['html'] .= '<p class="text-end"><button type="submit" class="btn btn-primary mb-3">Go</button></p>';
	$response['html'] .= '</div>';

	$response['html'] .= '</div>';
	$response['html'] .= '</form>';
	$response['html'] .= '</div>';
	*/
    echo json_encode($response);
    die();
}

// acquire all the parameters for a search from $_GET
// also used by the resource hub
function cc_training_search_params(){
	$params = cc_training_search_empty_params();

	$taxes = array(
		'i' => 'tax_issues',
		'a' => 'tax_approaches',
		'r' => 'tax_rtypes',
		'o' => 'tax_others',
		'l' => 'tax_trainlevels',
		't' => 'train_types',
	);
	foreach ( $taxes as $tax_initial => $tax_name ) {
		if( isset( $_GET[$tax_initial] ) && $_GET[$tax_initial] <> '' ){
			$safe_terms_string = stripslashes( sanitize_text_field( $_GET[$tax_initial] ) );
			$terms = explode( ',', $safe_terms_string );
			$params[$tax_name] = array_map( 'trim', $terms );
		}
	}

	if( isset( $_GET['e'] ) && $_GET['e'] <> '' ){ // 'e' for educator? :-)
		$safe_presenters_string = stripslashes( sanitize_text_field( $_GET['e'] ) );
		$presenter_ids = explode( ',', $safe_presenters_string );
		$params['presenters'] = array_map( 'trim', $presenter_ids );
	}

	if( isset( $_GET['c'] ) && $_GET['c'] <> '' ){ // certification
		$safe_certs_string = stripslashes( sanitize_text_field( $_GET['c'] ) );
		$certs = explode( ',', $safe_certs_string );
		$params['certificates'] = array_map( 'trim', $certs );
	}

	if( isset( $_GET['k'] ) && $_GET['k'] <> '' ){
		$params['search'] = trim( stripslashes( sanitize_text_field( $_GET['k'] ) ) );
	}

	if( isset( $_GET['pg'] ) && $_GET['pg'] <> '' ){
		$page_num = (int) $_GET['pg'];
		if( $page_num > 0 ){
			$params['page'] = $page_num;
		}
	}

	return $params;
}

function cc_training_search_empty_params(){
	return array(
		'tax_issues' => array(),
		'tax_approaches' => array(),
		'tax_rtypes' => array(),
		'tax_others' => array(),
		'tax_trainlevels' => array(),
		'presenters' => array(),
		'train_types' => array(),
		'certificates' => array(),
		'search' => '',
		'page' => 1,
	);
}

// set up the training search sidebar filter/search
// $btn='yes' means the form was triggered from a btn somewhere on the site (ie not the main training search page)
function cc_training_search_sidebar( $params, $btn='yes' ){
	$html = '<div class="training-search-params">';
	$html .= '<div id="cc-training-search-close" class="training-search-close">&times;</div>';
	$html .= '<h5>Training filter and search</h5>';
	$html .= '<form id="tsf-form" class="tsf-form" method="get" action="'.wms_find_page( 'page-training-search.php' ).'" onsubmit="return submitTSF()" autocomplete="off" data-btn="'.$btn.'">';
	// $html .= '<hr>';
	
	/**
	 * NOTE: If you change any of these, the JS will need to change too (function submitTSF in contextual.js)
	 */
	$taxes = array(
		array(
			'initial' => 'a',
			'name' => 'Approaches',
			'taxonomy' => 'tax_approaches',
		),
		array(
			'initial' => 'i',
			'name' => 'Issues',
			'taxonomy' => 'tax_issues',
		),
		/*
		array(
			'initial' => 'r',
			'name' => 'Resource types',
			'taxonomy' => 'tax_rtypes',
		),
		array(
			'initial' => 'o',
			'name' => 'Others',
			'taxonomy' => 'tax_others',
		),
		*/
		array(
			'initial' => 'l',
			'name' => 'Level',
			'taxonomy' => 'tax_trainlevels',
		),
	);

	// all potential training ids
	$training_ids = cc_training_search_all_training_ids();
	// all terms used by any potential trainings ... returns an array of term objects
	$pos_terms_i = wp_get_object_terms( $training_ids,  'tax_issues' );
	$pos_terms_a = wp_get_object_terms( $training_ids,  'tax_approaches' );
	// $pos_terms_o = wp_get_object_terms( $training_ids,  'tax_others' );
	$pos_terms_l = wp_get_object_terms( $training_ids,  'tax_trainlevels' );
	// all presenters used by these trainings
	$presenter_ids = cc_presenters_group( $training_ids );
	// all cert types used by these trainings
	$certificates = cc_certs_available_for( $training_ids );

	$html .= '<div class="tsf-topic">';
	$html .= '<h6 class="mb-1">Search for</h6>';
	$html .= '<input id="tsf-keyword" class="form-control form-control-lg" type="text" name="k" value="'.$params['search'].'">';
	$html .= '</div>';

	foreach ($taxes as $tax) {
		// is this taxonomy in use?
		if( ! empty( ${'pos_terms_'.$tax['initial']} ) ){

			$html .= '<div class="tsf-topic">';
			$html .= '<h6 class="mb-1">'.$tax['name'].'</h6>';
			// $terms = get_terms( array( 'taxonomy' => $tax['taxonomy'] ) );
			$prefix = $tax['initial'].'_';
			$count_terms = 0;
			foreach ( $params[$tax['taxonomy']] as $tax_slug) {
				foreach ( ${'pos_terms_'.$tax['initial']} as $term ) {
					if( $term->slug == $tax_slug ){
						$count_terms ++;
						$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$term->slug.'" id="'.$prefix.$term->slug.'" name="'.$tax['initial'].'[]" checked><label class="form-check-label" for="'.$prefix.$term->slug.'">'.$term->name.'</label></div>';
						break;
					}
				}
			}
			// force a belated show more at this point?
			if( $count_terms > 10 ) $count_terms = 10;
			foreach ( ${'pos_terms_'.$tax['initial']} as $term ) {
				if( ! in_array( $term->slug, $params[$tax['taxonomy']] ) ){
					$count_terms ++;
					if( $count_terms == 11 ){
						$html .= '<div id="" class="tsf-more">';
					}
					$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$term->slug.'" id="'.$prefix.$term->slug.'" name="'.$tax['initial'].'[]"><label class="form-check-label" for="'.$prefix.$term->slug.'">'.$term->name.'</label></div>';
				}
			}
			if( $count_terms > 10 ){
				$html .= '</div><p class="small m-0"><a href="javascript:void(0);" id="" class="tsf-more-trigger">Show all <i class="fa-solid fa-angle-down"></i></a><a href="javascript:void(0);" id="" class="tsf-less-trigger">Show fewer <i class="fa-solid fa-angle-up"></i></a></p>';
			}
			$html .= '</div>';

		}
	}

	if( count( $presenter_ids ) > 0 ){
		$html .= '<div class="tsf-topic">';
		$html .= '<h6 class="mb-1">Trainers</h6>';
		$count_presenters = 0;
		foreach ($params['presenters'] as $presenter_id) {
			$count_presenters ++;
			$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$presenter_id.'" id="presenter-'.$presenter_id.'" name="e[]" checked><label class="form-check-label" for="presenter-'.$presenter_id.'">'.get_the_title( $presenter_id ).'</label></div>';
			// remove this presenter from the list
			if ( ( $key = array_search( $presenter_id, $presenter_ids ) ) !== false ) {
			    unset( $presenter_ids[$key] );
			}
		}
		// force a belated show more at this point?
		if( $count_presenters > 10 ) $count_presenters = 10;
		// now do the un-chosen presenters
		foreach ($presenter_ids as $presenter_id) {
			$count_presenters ++;
			if( $count_presenters == 11 ){
				$html .= '<div id="" class="tsf-more">';
			}
			$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$presenter_id.'" id="presenter-'.$presenter_id.'" name="e[]"><label class="form-check-label" for="presenter-'.$presenter_id.'">'.get_the_title( $presenter_id ).'</label></div>';
		}
		if( $count_presenters > 10 ){
			$html .= '</div><p class="small m-0"><a href="javascript:void(0);" id="" class="tsf-more-trigger">Show all <i class="fa-solid fa-angle-down"></i></a><a href="javascript:void(0);" id="" class="tsf-less-trigger">Show fewer <i class="fa-solid fa-angle-up"></i></a></p>';
		}
		$html .= '</div>';
	}

	if( count( $certificates ) > 0 ){
		$html .= '<div class="tsf-topic">';
		$html .= '<h6 class="mb-1">Certificates</h6>';
		$count_certs = 0;
		foreach ($params['certificates'] as $certificate) {
			$count_certs ++;
			$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$certificate.'" id="cert-'.$certificate.'" name="c[]" checked><label class="form-check-label" for="cert-'.$certificate.'">'.strtoupper( $certificate ).'</label></div>';
			// remove this cert from the list
			if ( ( $key = array_search( $certificate, $certificates ) ) !== false ) {
			    unset( $certificates[$key] );
			}
		}
		// force a belated show more at this point?
		if( $count_certs > 10 ) $count_certs = 10;
		// now do the un-chosen certificates
		foreach ($certificates as $certificate) {
			$count_certs ++;
			if( $count_certs == 11 ){
				$html .= '<div id="" class="tsf-more">';
			}
			$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$certificate.'" id="cert-'.$certificate.'" name="c[]"><label class="form-check-label" for="cert-'.$certificate.'">'.strtoupper( $certificate ).'</label></div>';
		}
		if( $count_certs > 10 ){
			$html .= '</div><p class="small m-0"><a href="javascript:void(0);" id="" class="tsf-more-trigger">Show all <i class="fa-solid fa-angle-down"></i></a><a href="javascript:void(0);" id="" class="tsf-less-trigger">Show fewer <i class="fa-solid fa-angle-up"></i></a></p>';
		}
		$html .= '</div>';
	}

	$html .= '<div class="tsf-topic">';
	$html .= '<h6 class="mb-1">Training type</h6>';
	$checked = in_array( 'live', $params['train_types'] ) ? 'checked' : '';
	$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="live" id="t_live" name="t[]" '.$checked.'><label class="form-check-label" for="t_live">Live training</label></div>';
	$checked = in_array( 'on-demand', $params['train_types'] ) ? 'checked' : '';
	$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="on-demand" id="t_on_demand" name="t[]" '.$checked.'><label class="form-check-label" for="t_on_demand">On-demand training</label></div>';
	$checked = in_array( 'free', $params['train_types'] ) ? 'checked' : '';
	$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="free" id="t_free" name="t[]" '.$checked.'><label class="form-check-label" for="t_free">Free training</label></div>';
	$html .= '</div>';

	$html .= '<div class="tsf-topic pt-3 mb-0">';
	$html .= '<div class="row">';
	$html .= '<div class="col"><a href="javascript:void(0);" id="tsf-clear" class="small">Clear filters</a></div>';
	/* we no longer use a button
	if( $btn == 'yes' ){
		$html .= '<div class="col text-end"><button type="submit" id="tsf-go-btn" class="btn btn-sm btn-primary">Go</button></div>';
	}
	*/
	$html .= '</div>';
	$html .= '</div>';

	$html .= '</div>';
	$html .= '</form>';
	return $html;
}

// convert taxonomy params into a tax query for get_posts
// also used for the topics search/filter
function cc_training_search_tax_query( $params ){
	if( empty( $params ) ){
		$tax_query = array();
	}else{
		$tax_terms = array();
		$taxes = array(
			'i' => 'tax_issues',
			'a' => 'tax_approaches',
			'r' => 'tax_rtypes', // topics filter only
			'o' => 'tax_others',
			'l' => 'tax_trainlevels',
		);
		foreach ( $taxes as $tax_initial => $tax_name) {
			if( ! empty( $params[$tax_name] ) ){
				$tax_terms[] = array(
					'taxonomy' => $tax_name,
					'field' => 'slug',
					'terms' => $params[$tax_name],
				);
			}
		}
		if( count( $tax_terms ) == 1 ){
			$tax_query = array(
				$tax_terms
			);
		}elseif( count( $tax_terms ) > 1 ){
			$tax_query = array_merge(
				array(
					'relation' => 'AND',
				),
				$tax_terms
			);
		}else{
			$tax_query = array();
		}
	}
	return $tax_query;
}


// acquire all the relevant trainings for this search
function cc_training_search_get( $params ){
	$tax_query = cc_training_search_tax_query( $params );
	$post_types = array( 'workshop', 'course' );
	if( in_array( 'live', $params['train_types'] ) && ! in_array( 'on-demand', $params['train_types'] ) && ! in_array( 'free', $params['train_types'] ) ){
		$post_types = array( 'workshop' );
	}
	if( in_array( 'on-demand', $params['train_types'] ) && ! in_array( 'live', $params['train_types'] ) && ! in_array( 'free', $params['train_types'] ) ){
		$post_types = array( 'course' );
	}
	$args = array(
		'post_type' => $post_types,
		'numberposts' => -1,
		'fields' => 'ids',
		'meta_key' => '_links_to',
		'meta_compare' => 'NOT EXISTS',
	);
	if( ! empty( $tax_query ) ){
		$args['tax_query'] = $tax_query;
	}
	if( $params['search'] <> '' ){
		$args['s'] = $params['search'];
	}
	$cpt_ids = get_posts($args);

	// if we're dealing with recordings then we only want to include ones that are available for sale
	$wanted_ids = array();
	foreach ($cpt_ids as $training_id) {
		if( get_post_type( $training_id ) == 'course' ){
	        $course_status = get_post_meta( $training_id, '_course_status', true );
	        if($course_status == 'closed' || $course_status == 'unlisted'){
	            continue;
	        }
	        $wanted_ids[] = $training_id;
	    }else{
	    	// include workshops that are not unlisted
	        $course_status = get_post_meta( $training_id, 'course_status', true );
	        if($course_status <> 'unlisted'){
		        $wanted_ids[] = $training_id;
		    }
	    }
    }
    $cpt_ids = $wanted_ids;

	// for workshops, we only want to include incomplete ones
    $wanted_ids = [];
    foreach ( $cpt_ids as $training_id ) {
		if( get_post_type( $training_id ) == 'workshop' ){
			if( workshop_incomplete( $training_id ) ){
				$wanted_ids[] = $training_id;
			}
		}else{
			$wanted_ids[] = $training_id;
		}
    }
    $cpt_ids = $wanted_ids;

	// if free training has been selected and workshops/recordings have not been selected then we need to remove the non-free things that have not been selected
	if( in_array( 'free', $params['train_types'] ) && ( ! in_array( 'on-demand', $params['train_types'] ) || ! in_array( 'live', $params['train_types'] ) ) ){
		// something to remove (probably)
		$wanted_ids = array();
		foreach ($cpt_ids as $training_id) {
			if( get_post_type( $training_id ) == 'course' ){
				if( ! in_array( 'on-demand', $params['train_types'] ) ){
					$pricing = course_pricing_get( $training_id );
					if( $pricing['price_gbp'] == 0 ){
						$wanted_ids[] = $training_id;
					}
				}else{
					$wanted_ids[] = $training_id;
				}
			}else{
				if( ! in_array( 'live', $params['train_types'] ) ){
					if( (float) get_post_meta( $training_id, 'online_pmt_amt', true ) == 0 ){
						$wanted_ids[] = $training_id;
					}
				}else{
					$wanted_ids[] = $training_id;
				}
			}
		}
	    $cpt_ids = $wanted_ids;
	}

	// if filtering presenters then exclude unwanted presnters...
	if( $params['presenters'] !== [] ){
		$wanted_ids = array();
		foreach ($cpt_ids as $training_id) {
			$training_presenters = cc_presenters_training_get_ids( $training_id );
			foreach ($training_presenters as $presenter) {
				if( in_array( $presenter['id'], $params['presenters'] ) ){
					$wanted_ids[] = $training_id;
					break;
				}
			}
		}
	    $cpt_ids = $wanted_ids;
	}

	// if filtering certificates, exclude unwanted ones
	if( $params['certificates'] !== [] ){
		$wanted_ids = array();
		foreach ($cpt_ids as $training_id) {
			foreach ($params['certificates'] as $cert) {
				if( cc_certs_setting( $training_id, 0, $cert ) == 'yes' ){
					$wanted_ids[] = $training_id;
					break;
				}
			}
		}
	    $cpt_ids = $wanted_ids;
	}

	// finally we need to re-order these trainings
	// now showing all live training first in date order followed by on-demand training in post->ID (DESC) order
	// prep for sorting ...
	$live_ids = array();
	$on_demand_ids = array();
	$missing_dates = 0;
	foreach ($cpt_ids as $training_id) {
		if( get_post_type( $training_id ) == 'course' ){
			$on_demand_ids[] = $training_id;
		}else{
		    $workshop_start_timestamp = get_post_meta( $training_id, 'workshop_start_timestamp', true ); // time is rubbish
		    if( $workshop_start_timestamp == '' ){
		    	$missing_dates ++;
		    	$live_ids[$missing_dates] = $training_id;
		    }else{
		    	$live_ids[$workshop_start_timestamp] = $training_id;
		    }
		}
	}
	// now sort ...
	arsort( $on_demand_ids );
	ksort( $live_ids );
	// and combine
	$cpt_ids = array_merge( array_values( $live_ids ), array_values( $on_demand_ids ) );

	/*
	// matches on approach should come first, then Issues, Speakers, and finally Other
	$param_ids = cc_training_search_params_ids( $params );
	usort( $cpt_ids, function( $a, $b ) use ( $param_ids ) {
	    // Compare priorities of two posts
	    $priority_a = cc_training_search_get_post_priority( $a, $param_ids );
	    $priority_b = cc_training_search_get_post_priority( $b, $param_ids );
	    // Sort posts by priority, ascending (1 = highest priority)
	    if ($priority_a == $priority_b) {
	        return 0;
	    }
	    return ( $priority_a < $priority_b ) ? -1 : 1;
	});
	*/

	if( count( $cpt_ids ) == 0 ){
		return '<h4>No training found. Please broaden your search.</h4>';
	}

	$html = '<p>'.count( $cpt_ids ).' results found';
	$param_text = cc_trainng_search_pretty_params( $params );
	if( $param_text <> '' ){
		$html .= ' for '.$param_text;
	}
	$html .= '</p>';

	$tot_pages = ceil( count( $cpt_ids ) / 12);
	$curr_page = 1;
	if( isset( $params['page'] ) && $params['page'] > 1 ){
		$curr_page = (int) $params['page'];
		if( $curr_page > $tot_pages ){
			$curr_page = $tot_pages;
		}
	}

	// now select the ones we will show on this page
	$num_per_page = 12;
	$start = $num_per_page * ( $curr_page - 1 );
	$cpt_ids = array_slice( $cpt_ids, $start, $num_per_page );

	$html .= '<div class="row mx-auto my-auto">';
	foreach ($cpt_ids as $cpt_id) {
		$html .= cc_topics_cpt_card_col( $cpt_id, false, false, '', $params );
	}
	$html .= '</div>';

	$html .= cc_navigation_tsf( $curr_page, $tot_pages, $params, 'tsf-page-link' );
	
	return $html;
}

/* no longer needed
// Helper function to determine the priority of a training based on taxonomies
function cc_training_search_get_post_priority( $training_id, $param_ids ) {
    // Check if the training has terms that match the required approaches
    $approach_terms = wp_get_post_terms( $training_id, 'tax_approaches', ['fields' => 'ids'] );
    if ( ! empty( array_intersect( $approach_terms, $param_ids['tax_approaches'] ) ) ) {
        return 1; // Highest priority
    } 
    // Check if the training has terms that match the required issues
    $issue_terms = wp_get_post_terms( $training_id, 'tax_issues', ['fields' => 'ids'] );
    if ( ! empty( array_intersect( $issue_terms, $param_ids['tax_issues'] ) ) ) {
        return 2;
    }
    // Check if the training has terms that match the required presenters
    $presenters = cc_presenters_training_get_ids( $training_id );
    foreach ($presenters as $presenter) {
    	if( in_array( $presenter['id'], $param_ids['presenters']) ){
    		return 3;
    	}
    }
    // Check if the training has terms that match the required others
    $other_terms = wp_get_post_terms( $training_id, 'tax_others', ['fields' => 'ids'] );
    if ( ! empty( array_intersect( $other_terms, $param_ids['tax_others'] ) ) ) {
        return 4; // Lowest priority for matching 'others'
    }
    // lowest priority ...
    return 5;
}
*/

// return a pretty string setting out what aparams have been selected
function cc_trainng_search_pretty_params( $params ){
	$html = '';
	$wanted_params = array(
		'tax_issues' => 'Issues',
		'tax_approaches' => 'Approaches',
		'tax_rtypes' => 'Resource types',
		'tax_trainlevels' => 'Training levels',
		'tax_others' => 'Others',
		'presenters' => 'Trainers',
		'train_types' => 'Training types',
		'certificates' => 'Certificates',
		'search' => 'Search term',
	);
	foreach ($wanted_params as $param_type => $pretty_name) {
		if( ! empty( $params[$param_type] ) ){
			if( $html <> '' ){
				$html .= '; ';
			}
			$html .= '<strong>'.$pretty_name.'</strong>: ';
			$first = true;
			if( $param_type == 'search' ){
				$html .= $params[$param_type];
			}else{
				foreach ($params[$param_type] as $selection) {
					if( ! $first ){
						$html .= ', ';
					}
					if( substr( $param_type, 0, 4 ) == 'tax_' ){
						$term = get_term_by( 'slug', $selection, $param_type);
						$html .= $term->name;
					}elseif( $param_type == 'presenters' ){
						$html .= get_the_title( $selection );
					}elseif( $param_type == 'train_types' ){
						$html .= ucfirst( $selection );
					}elseif( $param_type == 'certificates' ){
						$html .= strtoupper( $selection );
					}
					$first = false;
				}
			}
		}
	}
	return $html;
}

// convert params from strings to ids
// used for search prioritisation
function cc_training_search_params_ids( $params ){
	// we only care about approaches, Issues, Speaker, Other, Resources
	$wanted = array( 'tax_approaches', 'tax_issues', 'presenters', 'tax_others' );
	$results = array();
	foreach ($wanted as $type) {
		$ids = array();
		if( isset( $params[$type] ) ){
			foreach ($params[$type] as $value) {
				if( $type == 'presenters' ){
					// already an id
					$ids[] = $value;
				}else{
					$term = get_term_by( 'slug', $value, $type );
					if( $term ){
						$ids[] = $term->ID;
					}
				}
			}
		}
		$results[$type] = $ids;
	}
	return $results;
}


// get ids of all potential trainings that may or may not be shown in the search
// eg excludes recordings unavailable for sale
// used for finding potential taxonomy terms later on so we don't have to repeat this process many times
function cc_training_search_all_training_ids(){
	$args = array(
	    'post_type'    => array('workshop', 'course'), // both types
	    'numberposts'  => -1,                          // all posts
	    'fields'       => 'ids',                       // just the IDs
	    'meta_query'   => array(					   // excluding any with a '_links_to' meta_key NOTE When using compare => NOT EXISTS, you must use a meta query array
	        array(
	            'key'     => '_links_to',
	            'compare' => 'NOT EXISTS',
	        ),
	    ),
	);
	$training_ids = get_posts( $args );
	// now exclude the ones we don't want to show
	$wanted_ids = array();
	foreach ($training_ids as $training_id) {
		if( get_post_type( $training_id ) == 'course' ){
	        $course_status = get_post_meta( $training_id, '_course_status', true );
	        if($course_status == 'closed' || $course_status == 'unlisted'){
	            continue;
	        }
	        $wanted_ids[] = $training_id;
	    }else{
	    	if( workshop_incomplete( $training_id ) ){
		        $course_status = get_post_meta( $training_id, 'course_status', true );
		        if($course_status <> 'unlisted'){
		    		$wanted_ids[] = $training_id;
		    	}
	    	}
	    }
	}
	return $wanted_ids;
}

// the ajax triggered training search when the page loads or on page change
add_action( 'wp_ajax_cc_training_search', 'cc_training_search_ajax' );
add_action( 'wp_ajax_nopriv_cc_training_search', 'cc_training_search_ajax' );
function cc_training_search_ajax(){
	$response = array(
		'status' => 'ok',
		'html' => '',
		'url' => '',
	);
	$params = array();
	if( isset( $_POST['params'] ) && $_POST['params'] <> '' ){
		$params = maybe_unserialize( base64_decode( stripslashes( sanitize_text_field( $_POST['params'] ) ) ) );
	}
	// $response['debug'] = print_r( $params, true );
	$response['html'] = cc_training_search_get( $params );
	$response['url'] = cc_training_search_url( $params );
   	echo json_encode($response);
	die();
}

// the ajax triggered training search ... UPDATE (ie after they change something in the form)
add_action( 'wp_ajax_cc_training_search_update', 'cc_training_search_update_ajax' );
add_action( 'wp_ajax_nopriv_cc_training_search_update', 'cc_training_search_update_ajax' );
function cc_training_search_update_ajax(){
	$response = array(
		'status' => 'ok',
		'html' => '',
		'url' => '',
	);
	$raw_params = array();
	parse_str( $_POST['formData'], $raw_params );

	$params = cc_training_search_empty_params();

	$taxes = array(
		'i' => 'tax_issues',
		'a' => 'tax_approaches',
		'r' => 'tax_rtypes',
		'o' => 'tax_others',
		'l' => 'tax_trainlevels',
		't' => 'train_types',
	);
	foreach ( $taxes as $tax_initial => $tax_name ) {
		if( isset( $raw_params[$tax_initial] ) && $raw_params[$tax_initial] <> '' ){
			$params[$tax_name] = $raw_params[$tax_initial];
		}
	}

	if( isset( $raw_params['e'] ) && $raw_params['e'] <> '' ){ // 'e' for educator? :-)
		$params['presenters'] = $raw_params['e'];
	}

	if( isset( $raw_params['c'] ) && $raw_params['c'] <> '' ){ // certification
		$params['certificates'] = $raw_params['c'];
	}

	if( isset( $raw_params['k'] ) && $raw_params['k'] <> '' ){
		$params['search'] = trim( stripslashes( sanitize_text_field( $raw_params['k'] ) ) );
	}

	if( isset( $raw_params['pg'] ) && $raw_params['pg'] <> '' ){
		$page_num = (int) $raw_params['pg'];
		if( $page_num > 0 ){
			$params['page'] = $page_num;
		}
	}

	// if( isset( $_POST['params'] ) && $_POST['params'] <> '' ){
	// 	$params = maybe_unserialize( base64_decode( stripslashes( sanitize_text_field( $_POST['params'] ) ) ) );
	// }
	// // $response['debug'] = print_r( $params, true );
	$response['html'] = cc_training_search_get( $params );
	$response['url'] = cc_training_search_url( $params );
   	echo json_encode($response);
	die();
}

// create the URL for this particular search
/* eg
encoded:
https://ccdev.saasora.com/find-a-course-for-you?i=anxiety%2Cissue-2&a=chairwork&o=other-topic&l=advanced%2Cbeginner&t=live%2Con-demand%2Cfree&e=5250%2C5252%2C5231&c=apa%2Cbacb&k=acceptance
unencoded:
https://ccdev.saasora.com/find-a-course-for-you?i=anxiety,issue-2&a=chairwork&o=other-topic&l=advanced,beginner&t=live,on-demand,free&e=5250,5252,5231&c=apa,bacb&k=acceptance
*/
function cc_training_search_url( $params ){
	$base_url = wms_find_page( 'page-training-search.php' );
	$possibilities = array(
		'tax_issues' => 'i',
		'tax_approaches' => 'a',
		'tax_rtypes' => 'r',
		'tax_others' => 'o',
		'tax_trainlevels' => 'l',
		'presenters' => 'e',
		'train_types' => 't',
		'certificates' => 'c',
	);
	$args = array();
	foreach ($possibilities as $param_name => $link_initial) {
		if( isset( $params[$param_name] ) && ! empty( $params[$param_name] ) ){
			$terms = implode( ',', $params[$param_name] );
			$args[$link_initial] = $terms;
		}
	}
	// add search term and page number
	if( isset( $params['search'] ) && $params['search'] <> '' ){
		$args['k'] = $params['search'];
	}
	if( isset( $params['page'] ) && $params['page'] <> '' && $params['page'] <> '1' ){
		$args['pg'] = $params['page'];
	}
	return add_query_arg( $args, $base_url );
}

