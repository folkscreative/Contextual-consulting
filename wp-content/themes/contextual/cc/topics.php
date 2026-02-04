<?php
/***
 * Topics
 */

// Register a custom menu page.
function cc_topics_custom_menu() {
	// add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', string $icon_url = '', int|float $position = null )
	add_menu_page( 'Topics', 'Topics', 'manage_options', 'topics', 'cc_topics_topics_page', 'dashicons-category', 34 );
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int|float $position = null )
	add_submenu_page('topics', 'Topics', 'Topics', 'manage_options', 'topics', 'cc_topics_topics_page' );
	add_submenu_page('topics', 'Issues', 'Issues', 'manage_options', 'edit-tags.php?taxonomy=tax_issues', '' );
	add_submenu_page('topics', 'Approaches', 'Approaches', 'manage_options', 'edit-tags.php?taxonomy=tax_approaches', '' );
	add_submenu_page('topics', 'Resource Types', 'Resource Types', 'manage_options', 'edit-tags.php?taxonomy=tax_rtypes', '' );
	add_submenu_page('topics', 'Others', 'Others', 'manage_options', 'edit-tags.php?taxonomy=tax_others', '' );
	add_submenu_page('topics', 'Training levels', 'Training levels', 'manage_options', 'edit-tags.php?taxonomy=tax_trainlevels', '' );
}
add_action( 'admin_menu', 'cc_topics_custom_menu' );

// Hook into parent_file to correctly highlight your Custom Post Type and Custom Taxonomy submenu items with your custom parent menu/page.
function cc_topics_set_current_menu( $parent_file ) {
    global $submenu_file, $current_screen, $pagenow;
    // Set the submenu as active/current while on the topics taxonomy pages

    // echo 'submenu_file='.$submenu_file.'<br>'; // eg edit-tags.php?taxonomy=tax_issues
    // echo 'current_screen=<br>';
    // var_dump($current_screen);
    /*
    object(WP_Screen)#10613 (18) {
		["action"] => ""
		["base"] => "edit-tags"
		["columns":"WP_Screen":private] => int(0)
		["id"] => "edit-tax_issues"
		["in_admin":protected] => "site"
		["is_network"] => false
		["is_user"] => false
		["parent_base"] => NULL
		["parent_file"] => NULL
		["post_type"] => "post"
		["taxonomy"] => "tax_issues"
		["_help_tabs":"WP_Screen":private] => array {}
		["_help_sidebar":"WP_Screen":private] => ""
		["_screen_reader_content":"WP_Screen":private] => array {
			["heading_views"] => "Filter items list"
			["heading_pagination"] => "Issues list navigation"
			["heading_list"] => "Issues list"
		}
		["_options":"WP_Screen":private] =>	array {
			["per_page"] =>	array {
				["default"] => int(20)
				 ["option"] => "edit_tax_issues_per_page"
			}
		}
		["_show_screen_options":"WP_Screen":private] =>	NULL
		["_screen_settings":"WP_Screen":private] =>	NULL
		["is_block_editor"] => false
	}
	*/
    // echo '<br>';
    // echo 'pagenow='.$pagenow.'<br>'; // eg edit-tags.php

    if( $submenu_file == 'edit-tags.php?taxonomy=tax_issues' 
    	|| $submenu_file == 'edit-tags.php?taxonomy=tax_approaches' 
    	|| $submenu_file == 'edit-tags.php?taxonomy=tax_rtypes'
    	|| $submenu_file == 'edit-tags.php?taxonomy=tax_others'
    	|| $submenu_file == 'edit-tags.php?taxonomy=tax_trainlevels' ){
    	$parent_file = 'topics';
    }
    return $parent_file;
}

add_filter( 'parent_file', 'cc_topics_set_current_menu' );


// Register Custom Taxonomies
function cc_topics_issues_tax() {

	$labels = array(
		'name'                       => 'Issues',
		'singular_name'              => 'Issue',
		'menu_name'                  => 'Issues',
		'all_items'                  => 'All Issues',
		'parent_item'                => 'Parent Issue',
		'parent_item_colon'          => 'Parent Issue:',
		'new_item_name'              => 'New Issue Name',
		'add_new_item'               => 'Add New Issue',
		'edit_item'                  => 'Edit Issue',
		'update_item'                => 'Update Issue',
		'view_item'                  => 'View Issue',
		'separate_items_with_commas' => 'Separate Issues with commas',
		'add_or_remove_items'        => 'Add or remove Issues',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Issues',
		'search_items'               => 'Search Issues',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No Issues',
		'items_list'                 => 'Issues list',
		'items_list_navigation'      => 'Issues list navigation',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_in_menu'				 => false,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => true,
		'rewrite'					 => array( 'slug' => 'issues' ),
	);
	register_taxonomy( 'tax_issues', array( 'post', 'recording', 'workshop', 'resource_hub', 'knowledge_hub', 'course' ), $args );

}
add_action( 'init', 'cc_topics_issues_tax', 0 );

function cc_topics_approaches_tax() {

	$labels = array(
		'name'                       => 'Approaches',
		'singular_name'              => 'Approach',
		'menu_name'                  => 'Approaches',
		'all_items'                  => 'All Approaches',
		'parent_item'                => 'Parent Approach',
		'parent_item_colon'          => 'Parent Approach:',
		'new_item_name'              => 'New Approache Name',
		'add_new_item'               => 'Add New Approach',
		'edit_item'                  => 'Edit Approach',
		'update_item'                => 'Update Approach',
		'view_item'                  => 'View Approach',
		'separate_items_with_commas' => 'Separate Approaches with commas',
		'add_or_remove_items'        => 'Add or remove Approaches',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Approaches',
		'search_items'               => 'Search Approaches',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No Approaches',
		'items_list'                 => 'Approaches list',
		'items_list_navigation'      => 'Approaches list navigation',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_in_menu'				 => false,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => true,
		'rewrite'					 => array( 'slug' => 'approaches' ),
	);
	register_taxonomy( 'tax_approaches', array( 'post', 'recording', 'workshop', 'resource_hub', 'knowledge_hub', 'course' ), $args );

}
add_action( 'init', 'cc_topics_approaches_tax', 0 );

function cc_topics_rtypes_tax() {

	$labels = array(
		'name'                       => 'Resource Types',
		'singular_name'              => 'Resource Type',
		'menu_name'                  => 'Resource Types',
		'all_items'                  => 'All Resource Types',
		'parent_item'                => 'Parent Resource Type',
		'parent_item_colon'          => 'Parent Resource Type:',
		'new_item_name'              => 'New Resource Type Name',
		'add_new_item'               => 'Add New Resource Type',
		'edit_item'                  => 'Edit Resource Type',
		'update_item'                => 'Update Resource Type',
		'view_item'                  => 'View Resource Type',
		'separate_items_with_commas' => 'Separate Resource Types with commas',
		'add_or_remove_items'        => 'Add or remove Resource Types',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Resource Types',
		'search_items'               => 'Search Resource Types',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No Resource Types',
		'items_list'                 => 'Resource Types list',
		'items_list_navigation'      => 'Resource Types list navigation',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_in_menu'				 => false,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => true,
		'rewrite'					 => array( 'slug' => 'resource-types' ),
	);
	register_taxonomy( 'tax_rtypes', array( 'post', 'recording', 'workshop', 'resource_hub', 'knowledge_hub', 'course' ), $args );

}
add_action( 'init', 'cc_topics_rtypes_tax', 0 );

function cc_topics_others_tax() {

	$labels = array(
		'name'                       => 'Others',
		'singular_name'              => 'Other',
		'menu_name'                  => 'Others',
		'all_items'                  => 'All Others',
		'parent_item'                => 'Parent Other',
		'parent_item_colon'          => 'Parent Other:',
		'new_item_name'              => 'New Other Name',
		'add_new_item'               => 'Add New Other',
		'edit_item'                  => 'Edit Other',
		'update_item'                => 'Update Other',
		'view_item'                  => 'View Other',
		'separate_items_with_commas' => 'Separate Others with commas',
		'add_or_remove_items'        => 'Add or remove Others',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Others',
		'search_items'               => 'Search Others',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No Others',
		'items_list'                 => 'Others list',
		'items_list_navigation'      => 'Others list navigation',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_in_menu'				 => false,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => true,
		'rewrite'					 => array( 'slug' => 'other' ),
	);
	register_taxonomy( 'tax_others', array( 'post', 'recording', 'workshop', 'resource_hub', 'knowledge_hub', 'course' ), $args );

}
add_action( 'init', 'cc_topics_others_tax', 0 );

function cc_topics_training_levels_tax() {

	$labels = array(
		'name'                       => 'Training levels',
		'singular_name'              => 'Training level',
		'menu_name'                  => 'Training levels',
		'all_items'                  => 'All Training levels',
		'parent_item'                => 'Parent Training level',
		'parent_item_colon'          => 'Parent Training level:',
		'new_item_name'              => 'New Training level Name',
		'add_new_item'               => 'Add New Training level',
		'edit_item'                  => 'Edit Training level',
		'update_item'                => 'Update Training level',
		'view_item'                  => 'View Training level',
		'separate_items_with_commas' => 'Separate Training levels with commas',
		'add_or_remove_items'        => 'Add or remove Training levels',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Training levels',
		'search_items'               => 'Search Training levels',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No Training levels',
		'items_list'                 => 'Training levels list',
		'items_list_navigation'      => 'Training levels list navigation',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_in_menu'				 => false,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => true,
		'rewrite'					 => array( 'slug' => 'training-levels' ),
	);
	register_taxonomy( 'tax_trainlevels', array( 'post', 'recording', 'workshop', 'resource_hub', 'knowledge_hub', 'course' ), $args );

}
add_action( 'init', 'cc_topics_training_levels_tax', 0 );

// topics page
function cc_topics_topics_page(){
	?>
	<div class="wrap">
		<h2>Topics</h2>
		<div id="poststuff">
			<div id="post-body row">
				<div class="postbox col-4" style="padding:30px;">
					<h3><a href="/wp-admin/edit-tags.php?taxonomy=tax_issues">Issues</a></h3>
					<h3><a href="/wp-admin/edit-tags.php?taxonomy=tax_approaches">Approaches</a></h3>
					<h3><a href="/wp-admin/edit-tags.php?taxonomy=tax_rtypes">Resource types</a></h3>
					<h3><a href="/wp-admin/edit-tags.php?taxonomy=tax_others">Others</a></h3>
					<h3><a href="/wp-admin/edit-tags.php?taxonomy=tax_trainlevels">Training levels</a></h3>
				</div>
			</div>
		</div>
	</div>
	<?php
}

// show the topics for a thing
// $post_type is the cpt post_type archive required for the link
// if $params are not empty then it will only show things that match these params
// but training levels are always shown
function cc_topics_show( $post_id, $size='', $link=false, $post_type='', $params=array(), $training_levels_only=false ){
	$html = '<div class="topics clearfix mb-2">';

	if( ( empty( $params ) || $params == cc_training_search_empty_params() ) && !$training_levels_only ){
		$always_show = true;
	}else{
		$always_show = false;
	}

	$taxonomies = array(
		'tax_issues' => 'issue',
		'tax_approaches' => 'approach',
		'tax_rtypes' => 'resource-type',
		'tax_others' => 'other',
		'tax_trainlevels' => 'training-level',
	);

	foreach ($taxonomies as $taxonomy => $class) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ){
			foreach ($terms as $term) {
				if( $always_show || $taxonomy == 'tax_trainlevels' || in_array( $term->slug, $params[$taxonomy] ) ){
					$html .= cc_topics_btn( $term, $class, $size, $link, $post_type );					
				}
			}
		}
	}

	if( get_post_type( $post_id ) == 'workshop' || get_post_type( $post_id ) == 'recording' || get_post_type( $post_id ) == 'course' ){
		// presenters not given tags as they are already in the card
		// train_types also not tagged as they are shown earlier

		// certificates
		$certs = cc_certs_for_training( $post_id );
		foreach ($certs as $cert) {
			if( $always_show || in_array( $cert, $params['certificates'] ) ){
				$html .= cc_topics_tag( $cert, 'certificate', $size );					
			}
		}
	}

	$html .= '</div>';
	return $html;
}

// button for a topic
function cc_topics_btn( $term, $class, $size, $link, $post_type ){
	if( $link ){
		$class .= ' btn-topics';
	}else{
		$class .= ' tag-topics';
	}
	if( $size <> '' ){
		$class .= '-'.$size;
	}
	switch ($term->taxonomy) {
		case 'tax_issues':
			$title = 'Issues';
			$field_name = 'tax-iss';
			$url_param = 'i';
			break;
		case 'tax_approaches':
			$title = 'Approaches';
			$field_name = 'tax-app';
			$url_param = 'a';
			break;
		case 'tax_rtypes':
			$title = 'Resource Types';
			$field_name = 'tax-rtp';
			$url_param = 'r';
			break;
		case 'tax_others':
			$title = 'Others';
			$field_name = 'tax-oth';
			$url_param = 'o';
			break;
		case 'tax_trainlevels':
			$title = 'Training Levels';
			$field_name = 'tax-lev';
			$url_param = 'l';
			break;
		default:
			$title = '';
			$field_name = '';
			$url_param = 'x';
			break;
	}
	if( $link ){
		if( $post_type == 'post' ){
			$html = '<a href="'.get_term_link( $term ).'" class="me-2 mb-1 btn btn-topics btn-'.$class.'" title="'.$title.'">'.$term->name.'</a>';
		}else{
			$html = '<a href="'.esc_url( add_query_arg( $url_param, $term->slug, get_post_type_archive_link( $post_type ) ) ).'" class="me-2 mb-1 btn btn-topics btn-'.$class.'" title="'.$title.'">'.$term->name.'</a>';
			/*
			$html = '
			<form action="'.get_post_type_archive_link( $post_type ).'" method="post">
				<input type="hidden" name="'.$field_name.'[]" value="'.$term->term_id.'">
				<button type="submit" class="btn me-2 mb-1 btn-'.$class.'" title="'.$title.'">'.$term->name.'</button>
			</form>';
			*/
			// $html = '<a class="btn me-2 mb-1 btn-'.$class.'" href="#" role="button" title="'.$title.'">'.$term->name.'</a>';
		}
	}else{
		$html = '<span class="me-2 mb-1 tag tag-'.$class.'" title="'.$title.'">'.$term->name.'</span>';
	}
	return $html;
}

// tags for certificates
function cc_topics_tag( $item, $class, $size ){
	if( $class == 'certificate' ){
		$title = 'Certificates';
		$item_name = strtoupper( $item );
	}
	$class .= ' tag-topics';
	if( $size <> '' ){
		$class .= '-'.$size;
	}
	return '<span class="me-2 mb-1 tag tag-'.$class.'" title="'.$title.'">'.$item_name.'</span>';
}

// show a linked section (eg show knowledge hub items on a resource hub page)
// $taxonomies should be an array such as array( 'tax_issues' => array( 'tax_one_id', 'tax_two_id' ), 'tax_approaches' => array( 'tax_three_id' ) )
// now v1.62 uses a carousel if there are more than three posts
// now accommodates courses
function cc_topics_linked_section( $post_type, $taxonomies=array(), $exclude=0 ){
	// ccpa_write_log('function cc_topics_linked_section post_type: '.$post_type);
	// ccpa_write_log($taxonomies);
	// ccpa_write_log($exclude);
	static $section_num = 0;
	$tax_query = cc_topics_tax_query( $taxonomies );
	// get the relevant CPTs that use these taxonomies
	if( $post_type == 'recording' ){
		$args = array(
			'post_type' => 'course',
			'numberposts' => -1,
			'fields' => 'ids',
	        'meta_key' => '_course_type',
	        'meta_value' => 'on-demand'
		);
	}else{
		$args = array(
			'post_type' => $post_type,
			'numberposts' => -1,
			'fields' => 'ids',
		);
	}
	if( is_array( $tax_query ) ){
		$args['tax_query'] = $tax_query;
	}
	if( $exclude > 0 ){
		$args['post__not_in'] = $exclude;
	}
	// ccpa_write_log($args);
	$cpt_ids = get_posts($args);
	// ccpa_write_log($cpt_ids);

	// if we're dealing with recordings then we only want to include ones that are available for sale
	if( $post_type == 'recording' ){
		$wanted_ids = array();
		foreach ($cpt_ids as $recording_id) {
	        $course_status = get_post_meta( $recording_id, '_course_status', true );
	        if($course_status == 'closed' || $course_status == 'unlisted'){
	            continue;
	        }
	        $wanted_ids[] = $recording_id;
	    }
	    $cpt_ids = $wanted_ids;
	}

	if( empty( $cpt_ids )) return '';

	$doing_carousel = false;
	if( count( $cpt_ids ) > 3 ){
		$doing_carousel = true;
	}

    $id = 'wms-sect-'.mt_rand();
	$html = '<div id="'.$id.'" class="wms-section wms-section-std sml-padd">';
	$section_num ++;
	if( $section_num % 2 == 0 ){
		// even
		$bg_classes = '';
	}else{
		// odd
		$bg_classes = 'background-subtle';
	}
	$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
	$html .= '<div class="container">';
	$html .= '<div class="row">';
	$html .= '<div class="col-12">';
	$html .= '<div class="cc-train-panel">';
	$html .= '<h2 class="section-title">';
	switch ($post_type) {
		case 'knowledge_hub':			$html .= 'Knowledge hub';						break;
		case 'resource_hub':			$html .= 'Resource hub';						break;
		case 'post':					$html .= 'Blog: Latest insights into ACT';		break;
		case 'recording':				$html .= 'On-demand training';					break;
		default:						$html .= $post_type;							break;
	}
	$html .= '</h2>';
	if( is_array( $tax_query ) ){
		$html .= '<p>Related to your search/filter:</p>';
	}
	$html .= '<div class="row mx-auto my-auto';
	if( $doing_carousel ){
		$html .= ' justify-content-center';
	}
	$html .= '">';

	static $carousel_id = 0;
	if( $doing_carousel ){
		$html .= '<div id="cc-train-panel-carousel-'.$carousel_id.'" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="7000">';
		$html .= '<div class="carousel-inner cc-train-panel-carousel-inner" role="listbox">';
	}else{
		// $html .= '<p>&nbsp;</p>';
	}

	$item_class = 'active';
	foreach ($cpt_ids as $cpt_id) {
		$html .= cc_topics_cpt_card_col( $cpt_id, false, $doing_carousel, $item_class );
		$item_class = '';
	}

	if( $doing_carousel ){
		$html .= '</div><!-- .carousel-inner -->';
		// controls
		$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-train-panel-carousel-'.$carousel_id.'" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-train-panel-carousel-'.$carousel_id.'" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
		$html .= '</div><!-- .carousel -->';
	}

	$html .= '</div><!-- .cc-train-panel -->';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';

	switch ($post_type) {
		case 'knowledge_hub':			$html .= '<p class="text-end"><a href="'.get_post_type_archive_link($post_type).'" class="btn btn-primary">Knowledge hub</a></p>';						break;
		case 'resource_hub':			$html .= '<p class="text-end"><a href="'.get_post_type_archive_link($post_type).'" class="btn btn-primary">Resource hub</a></p>';						break;
		case 'post':					$html .= '<p class="text-end"><a href="'.get_post_type_archive_link($post_type).'" class="btn btn-primary">Blog: Latest insights into ACT</a></p>';		break;
		case 'recording':				$html .= '<p class="text-end"><a href="/online-training" class="btn btn-primary">On-demand training</a></p>';													break;
		default:						$html .= '<p class="text-end"><a href="'.get_post_type_archive_link($post_type).'" class="btn btn-primary">'.$post_type.'</a></p>';						break;
	}	

	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .container -->';
	$html .= '</div><!-- .wms-sect-bg -->';
	$html .= '</div><!-- .wms-section -->';

	$carousel_id ++;
	return $html;
}

// returns a card column for a CPT
function cc_topics_cpt_card_col( $cpt_id, $link=false, $doing_carousel=false, $item_class='', $params=array(), $xl_carousel_cols='3' ){
	$post = get_post($cpt_id);
	static $user_timezone = '';
	if( $user_timezone == '' ){
		$user_timezone = cc_timezone_get_user_timezone();
	}
	if( $post->post_type == 'recording' || $post->post_type == 'workshop' || $post->post_type == 'course' ){
		$training = true;
		$card_bg = ' grad-bg';
	}else{
		$training = false;
		$card_bg = '';
	}
	$html = '';
	if( $doing_carousel ){
		$html .= '<div class="carousel-item train-carousel-item '.$item_class.'">';
		$html .= '<div class="col-12 col-md-6 col-xl-'.$xl_carousel_cols.' cc-train-panel-col d-flex align-items-stretch">';
	}else{
		$html .= '<div class="col-md-6 col-lg-4 cc-train-panel-colxx d-flex align-items-stretch">';
	}
	$html .= '<div class="card mb-3 flex-fill bg-white '.$post->post_type.'">';

	// the image is different for trainings
	// if there's one presenter then it will be their mugshot, otherwise the featured image
	// the image size is also different as presenter images work better when square
	if( $training ){
		$html .= '<div class="wms-image-wrapper"><a href="'.get_permalink($cpt_id).'">';
		$presenters_image_ids = cc_presenters_training_get_ids( $cpt_id );
		$venue = get_post_meta( $cpt_id, 'event_1_venue_name', true );
		if( $venue == '' ){
			$flag = '';
		}else{
			$flag = 'In person';
		}
		if( count( $presenters_image_ids ) <> 1 ){
			// use featured image
			// we're using presenter-profile instead of post-image to match the presenter images
			$feat_img_url = get_the_post_thumbnail_url( $cpt_id, 'presenter-profile' );
			if($feat_img_url == ''){
				$feat_img_url = get_the_post_thumbnail_url( get_option('page_on_front'), 'presenter-profile' );
			}
			$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img image-zoom" style="background-image:url('.$feat_img_url.');">';
			// add a flag if needed
			if( $flag <> '' ){
				$html .= '<div class="train-card-flag">'.$flag.'</div>';
			}
			$html .= '</div>';
		}else{
			// use presenter's image
			$html .= cc_presenters_images( $cpt_id, false, true, 'card-img-top image-zoom', $flag );
		}
		$html .= '</a></div>';
	}elseif( $post->post_type == 'presenter' ){
		$html .= '<a href="'.get_permalink( $cpt_id ).'">';
    	$image_id = cc_presenters_image_id( $cpt_id, '1' );
    	if( $image_id ){
    		$image_url = esc_url( wms_section_image_url( $image_id, 'presenter-profile' ) );
    	}else{
    		$image_url = '';
    	}
		$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img image-zoom" style="background-image:url('.$image_url.');"></div>';
		$html .= '</a>';
	}else{
		$feat_img_url = get_the_post_thumbnail_url( $cpt_id, 'post-thumb' );
		if($feat_img_url == ''){
			$feat_img_url = get_the_post_thumbnail_url( get_option('page_on_front'), 'post-thumb' );
		}
		$html .= '<div class="cc-train-panel-img">';
		// $html .= '<a href="'.get_permalink($post->ID).'" class="image-zoom"><img src="'.$feat_img_url.'" class="card-img-top" alt="'.$post->post_title.' featured image"></a>';
		$html .= '<a href="'.get_permalink($cpt_id).'">';
		$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img image-zoom" style="background-image:url('.$feat_img_url.');"></div>';
		$html .= '</a>';
		$html .= '</div><!-- .cc-train-panel-img -->';
	}

	$html .= '<div class="card-body'.$card_bg.'">';

	if( $training ){
		$html .= '<div class="row cc-train-panel-preheader"><div class="col-6">';
		if( $post->post_type == 'workshop' ){
			$html .= 'LIVE';
		}else{
			$html .= 'ON-DEMAND';
		}
		$html .= '</div><div class="col-6 text-end">';
		if( $post->post_type == 'workshop' ){
			$html .= get_post_meta( $post->ID, 'prettydates', true );
		}else{
			$html .= get_post_meta( $post->ID, 'duration', true );
		}
		$html .= '</div></div>';
	}

	// show matching params
	if( $params <> [] ){
		$html .= cc_topics_show( $cpt_id, 'xs', false, '', $params );
	}

	// $html .= '<h5 class="post-title card-title">'.$post->post_title.'</h5>';
	if( isset( $params['search'] ) && $params['search'] <> '' ){
		$card_title = cc_search_highlight( $post->post_title, $params['search'] );
	}else{
		$card_title = $post->post_title;
	}
	$title_font = cc_tnc_text_size( 262, 90, $card_title, 24);
	$html .= '<h5 class="card-title cc-train-panel-card-title" style="font-size:'.$title_font['font_size'].'px; line-height:'.$title_font['line_height'].';">'.$card_title.'</h5>';

	$presenters = '';
	if( $training ){
		$presenters = cc_presenters_names($cpt_id, 'none');
	}
	if( $presenters == '' ){
		$html .= '<p class="card-text">'.wms_get_excerpt_by_id($cpt_id, '70', 'characters', ' ...').'</p>';
	}else{
		$html .= '<p class="card-text">'.$presenters.'</p>';
	}
	if( $post->post_type == 'workshop' ){
		$pretty_dates = workshop_calculated_prettydates( $cpt_id, $user_timezone );
		if($pretty_dates['locale_date'] <> ''){
			$html .= '<p class="card-text">'.$pretty_dates['locale_date'].'</p>';
		}
	}
	$html .= '<a href="'.get_permalink($post->ID).'" class="btn btn-primary btn-sm">Read more</a>';
	$html .= '</div><!-- .card-body -->';
	$html .= '</div><!-- .card -->';
	if( $doing_carousel ){
		$html .= '</div><!-- .col -->';
		$html .= '</div><!-- .carousel-item -->';
	}else{
		$html .= '</div><!-- .col -->';
	}
	return $html;
}

// convert taxonomies into a tax query for get_posts
function cc_topics_tax_query( $taxonomies, $relation='OR' ){
	if( empty( $taxonomies ) ){
		$tax_query = '';
	}else{
		$tax_terms = array();
		foreach ( $taxonomies as $tax_name => $tax_ids) {
			if( $relation == 'AND' ){
				$tax_terms[] = array(
					'taxonomy' => $tax_name,
					'field' => 'term_id',
					'terms' => $tax_ids,
					'operator' => 'AND',
				);
			}else{
				$tax_terms[] = array(
					'taxonomy' => $tax_name,
					'field' => 'term_id',
					'terms' => $tax_ids,
				);
			}
		}
		if( count( $tax_terms ) > 1 ){
			if( $relation == 'AND' ){
				$tax_query = array_merge(
					array(
						'relation' => 'AND',
					),
					$tax_terms
				);
			}else{
				$tax_query = array_merge(
					array(
						'relation' => 'OR',
					),
					$tax_terms
				);
			}
		}else{
			$tax_query = array(
				$tax_terms
			);
		}
	}
	return $tax_query;
}

// convert $_POST items into a taxonomies array
function cc_topics_sanitize_taxonomies(){
	$taxonomies = array();
	$possible_taxs = array( 
		'iss' => 'tax_issues', 
		'app' => 'tax_approaches', 
		'rtp' => 'tax_rtypes', 
		'oth' => 'tax_others',
		'lev' => 'tax_trainlevels',
	);
	foreach ($possible_taxs as $tax => $tax_name) {
		if( isset( $_POST['tax-'.$tax] ) ){
			$terms = array();
			foreach ($_POST['tax-'.$tax] as $key => $value) {
				$term = (int) $value;
				if( $term > 0){
					$terms[] = $term;
				}
			}
			if( ! empty( $terms ) ){
				$taxonomies[$tax_name] = $terms;
			}
		}
	}
	return $taxonomies;
}

// get term name
function cc_topics_get_term_name( $term_id, $taxonomy ){
	$term = get_term_by( 'id', absint( $term_id ), $taxonomy );
    return $term->name;
}

// get training levels for a training
function cc_topics_training_levels( $training_id ){
	$terms_string = '';
	$term_obj_list = get_the_terms( $training_id, 'tax_trainlevels' );
	if( $term_obj_list ){
		$terms_string = join( ', ', wp_list_pluck( $term_obj_list, 'name' ) );
	}
	return $terms_string;
}
