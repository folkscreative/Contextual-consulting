<?php
/**
 * Topics search and filter functions
 */

// the trigger button
function cc_topics_sf_trigger(){
	$html = '<a href="javascript:void(0);" class="cc-topicsf-btn btn btn-training btn-lg">Filter &amp; search</a>';
	return $html;
}

// Hook into WordPress to run the function after the page content is loaded
add_action( 'wp_footer', 'cc_topics_sf_form_footer' );
// adds the form to the page if the shortcodes are used
function cc_topics_sf_form_footer() {
    global $post;
    // Check if is a resource hub archive page ... could add "or has shortcode..."
    if ( is_post_type_archive( 'resource_hub' ) ) {
        // The HTML content to add to the bottom of the page
        $topics_sf_panel = '
		    <div id="topicsf-panel" class="topicsf-panel slider bg-light mb-3">
		    	<div class="p-5">
			        <!-- <span id="cc-topicsf-close" class="topicsf-close">&times;</span> -->
			        <div id="cc-topicsf-content"><p class="mt-5 text-center"><i class="fa-solid fa-spinner fa-spin-pulse fa-4x"></i></p></div>
			    </div>
		    </div>
        ';
        // output the HTML at the bottom of the page
        echo $topics_sf_panel;
    }
}

// get the content of the topics search form when a user requests it
add_action( 'wp_ajax_cc_topicsf_get', 'cc_topics_sf_get' );
add_action( 'wp_ajax_nopriv_cc_topicsf_get', 'cc_topics_sf_get' );
function cc_topics_sf_get(){
	$response = array(
		'status' => 'ok',
		'html' => cc_topics_sf_sidebar( cc_training_search_params(), 'yes' ),
	);
    echo json_encode($response);
    die();
}

// set up the topics search/filter sidebar
// $btn='yes' means the form was triggered from a btn somewhere on the site
function cc_topics_sf_sidebar( $params, $btn='yes' ){
	$html = '<div class="topics-sf-params">';
	$html .= '<div id="cc-topics-sf-close" class="topics-sf-close">&times;</div>';
	$html .= '<h5>Topics filter and search</h5>';
	$html .= '<form id="topicsf-form" class="topicsf-form" method="get" action="'.get_post_type_archive_link( 'resource_hub' ).'" onsubmit="return submitTopicsSF()" autocomplete="off" data-btn="'.$btn.'">';
	
	/**
	 * NOTE: If you change any of these, the JS will need to change too (function submitTopicsSF in contextual.js)
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
		array(
			'initial' => 'l',
			'name' => 'Level',
			'taxonomy' => 'tax_trainlevels',
		),
	);

	// all potential resource hub ids
	$rhub_ids = cc_resourcehub_get_ids();
	// all terms used by any potential resource hub items ... returns an array of term objects
	$pos_terms_a = wp_get_object_terms( $rhub_ids,  'tax_approaches' );
	$pos_terms_i = wp_get_object_terms( $rhub_ids,  'tax_issues' );
	$pos_terms_r = wp_get_object_terms( $rhub_ids,  'tax_rtypes' );
	$pos_terms_o = wp_get_object_terms( $rhub_ids,  'tax_others' );
	$pos_terms_l = wp_get_object_terms( $rhub_ids,  'tax_trainlevels' );

	$html .= '<div class="topicsf-topic">';
	$html .= '<h6 class="mb-1">Search for</h6>';
	$html .= '<input id="topicsf-keyword" class="form-control form-control-lg" type="text" name="k" value="'.$params['search'].'">';
	$html .= '</div>';

	foreach ($taxes as $tax) {
		// is this taxonomy in use?
		if( ! empty( ${'pos_terms_'.$tax['initial']} ) ){

			$html .= '<div class="topicsf-topic">';
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
						$html .= '<div id="" class="topicsf-more">';
					}
					$html .= '<div class="form-check"><input class="form-check-input" type="checkbox" value="'.$term->slug.'" id="'.$prefix.$term->slug.'" name="'.$tax['initial'].'[]"><label class="form-check-label" for="'.$prefix.$term->slug.'">'.$term->name.'</label></div>';
				}
			}
			if( $count_terms > 10 ){
				$html .= '</div><p class="small m-0"><a href="javascript:void(0);" id="" class="topicsf-more-trigger">Show all <i class="fa-solid fa-angle-down"></i></a><a href="javascript:void(0);" id="" class="topicsf-less-trigger">Show fewer <i class="fa-solid fa-angle-up"></i></a></p>';
			}
			$html .= '</div>';

		}
	}

	$html .= '<div class="topicsf-topic pt-3 mb-0">';
	$html .= '<div class="row">';
	$html .= '<div class="col"><a href="javascript:void(0);" id="topicsf-clear" class="small">Clear filters</a></div>';

	$html .= '</div>';
	$html .= '</div>';

	$html .= '</div>';
	$html .= '</form>';
	return $html;
}

// the ajax triggered search/filter when the page loads or on page change
add_action( 'wp_ajax_cc_topics_search_filter', 'cc_topics_search_filter_ajax' );
add_action( 'wp_ajax_nopriv_cc_topics_search_filter', 'cc_topics_search_filter_ajax' );
function cc_topics_search_filter_ajax(){
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
	$response['html'] = cc_topics_sf_get_results( $params );
	$response['url'] = cc_topics_search_url( $params );
   	echo json_encode($response);
	die();
}

// acquire all the relevant resource hub items for this search/filter
function cc_topics_sf_get_results( $params ){
	$tax_query = cc_training_search_tax_query( $params );
	$args = array(
		'post_type' => 'resource_hub',
		'numberposts' => -1,
		'fields' => 'ids',
	);
	if( ! empty( $tax_query ) ){
		$args['tax_query'] = $tax_query;
	}
	if( $params['search'] <> '' ){
		$args['s'] = $params['search'];
	}
	$cpt_ids = get_posts($args);

	if( count( $cpt_ids ) == 0 ){
		return '<h4>No items found. Please broaden your search.</h4>';
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

	$html .= cc_navigation_tsf( $curr_page, $tot_pages, $params, 'topicsf-page-link' );
	
	return $html;
}

// the ajax triggered topics search ... UPDATE (ie after they change something in the form)
add_action( 'wp_ajax_cc_topics_search_update', 'cc_topics_search_update_ajax' );
add_action( 'wp_ajax_nopriv_cc_topics_search_update', 'cc_topics_search_update_ajax' );
function cc_topics_search_update_ajax(){
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
	$response['html'] = cc_topics_sf_get_results( $params );
	$response['url'] = cc_topics_search_url( $params );
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
function cc_topics_search_url( $params ){
	$base_url = get_post_type_archive_link( 'resource_hub' );
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
