<?php
/**
 * Presenters
 */


// Register Custom Post Type
function cc_presenters_register_post_type() {

	$labels = array(
		'name'                  => 'Presenters',
		'singular_name'         => 'Presenter',
		'menu_name'             => 'Presenters',
		'name_admin_bar'        => 'Presenters',
		'archives'              => 'Presenter Archives',
		'attributes'            => 'Presenter Attributes',
		'parent_item_colon'     => 'Parent Item:',
		'all_items'             => 'All Presenters',
		'add_new_item'          => 'Add New Presenter',
		'add_new'               => 'Add New',
		'new_item'              => 'New Presenter',
		'edit_item'             => 'Edit Presenter',
		'update_item'           => 'Update Presenter',
		'view_item'             => 'View Presenter',
		'view_items'            => 'View Presenters',
		'search_items'          => 'Search Presenter',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Profile Image',
		'set_featured_image'    => 'Set profile image',
		'remove_featured_image' => 'Remove profile image',
		'use_featured_image'    => 'Use as profile image',
		'insert_into_item'      => 'Insert into Presenter',
		'uploaded_to_this_item' => 'Uploaded to this Presenter',
		'items_list'            => 'Presenters list',
		'items_list_navigation' => 'Presenters list navigation',
		'filter_items_list'     => 'Filter Presenters list',
	);
	$args = array(
		'label'                 => 'Presenter',
		'description'           => 'Training Presenter',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-admin-users',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'presenter', $args );

}
add_action( 'init', 'cc_presenters_register_post_type', 0 );

// add a column to the post type's admin
// basically registers the column and sets it's title
// but as we want it in the middle, it takes a little bit of faffing about
add_filter( 'manage_presenter_posts_columns', function ( $columns ) {
	$new_cols = array();
    foreach( $columns as $key => $value ) {
        if($key=='date') {
        	// insert the order column now
			$new_cols['menu_order'] = 'Order';
        }    
        $new_cols[$key] = $value;
    }
    return $new_cols;  
});

// display the value of the presenter order in the menu_order column
add_action( 'manage_presenter_posts_custom_column', function ( $column_name, $post_id ){
	if ($column_name == 'menu_order') {
		echo get_post( $post_id )->menu_order;
	}
}, 10, 2);

// make the menu_order sortable
add_filter( 'manage_edit-presenter_sortable_columns', function ( $columns ){
	// column key => Query variable
	// menu_order is in Query by default so we can just set it
	$columns['menu_order'] = 'menu_order';
	return $columns;
});

// set the profile image size
add_action('after_setup_theme', 'cc_presenters_image_size');
function cc_presenters_image_size(){
	add_image_size('presenter-profile', 720, 720, true ); // cropped
}

// Add msg to featured image meta box
function cc_presenters_featured_image_meta($content, $post_id, $thumbnail_id){
    $post = get_post($post_id);
    if ($post->post_type == 'presenter') {
        $content .= '<small><i>A square (ish) image with the presenter\'s face in the centre works best ... It\'s good if it is 720px or larger</i></small>';
    }
    return $content;
}
add_filter('admin_post_thumbnail_html', 'cc_presenters_featured_image_meta', 10, 3);


// add another meta box for the second profile image
// https://stackoverflow.com/questions/62860289/how-to-create-a-second-featured-image-box-for-posts-in-wordpress
add_action( 'add_meta_boxes', 'cc_presenter_second_presenter_metaboxes' );
function cc_presenter_second_presenter_metaboxes () {
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
    add_meta_box( 'second_profile_image', '2nd Profile Image', 'cc_presenter_second_presenter_metabox', 'presenter', 'side', 'low');
}
function cc_presenter_second_presenter_metabox ( $post ) {
    global $content_width, $_wp_additional_image_sizes;
    $image_id = get_post_meta( $post->ID, '_second_profile_imgid', true );
    $old_content_width = $content_width;
    $content_width = 254;
    if ( $image_id && get_post( $image_id ) ) {
        if ( ! isset( $_wp_additional_image_sizes['post-thumbnail'] ) ) {
            $thumbnail_html = wp_get_attachment_image( $image_id, array( $content_width, $content_width ) );
        } else {
            $thumbnail_html = wp_get_attachment_image( $image_id, 'post-thumbnail' );
        }
        if ( ! empty( $thumbnail_html ) ) {
            $content = $thumbnail_html;
            $content .= '<p class="hide-if-no-js"><a href="javascript:;" id="remove_2nd_profile_image_button">Remove image</a></p>';
            $content .= '<input type="hidden" id="upload_2nd_profile_image" name="_2nd_profile_image" value="' . esc_attr( $image_id ) . '" />';
        }
        $content_width = $old_content_width;
    } else {
        $content = '<img src="" style="width:' . esc_attr( $content_width ) . 'px;height:auto;border:0;display:none;" />';
        $content .= '<p class="hide-if-no-js"><a title="Set profile image" href="javascript:;" id="upload_2nd_profile_image_button" id="set-2nd-profile-image" data-uploader_title="Choose an image" data-uploader_button_text="Set 2nd Profile image">Set profile image</a></p>';
        $content .= '<input type="hidden" id="upload_2nd_profile_image" name="_2nd_profile_image" value="" />';
    }
    echo $content;
}
add_action( 'save_post', 'cc_presenters_2nd_profile_image_save', 10, 1 );
function cc_presenters_2nd_profile_image_save ( $post_id ) {
    if( isset( $_POST['_2nd_profile_image'] ) ) {
        $image_id = (int) $_POST['_2nd_profile_image'];
        update_post_meta( $post_id, '_second_profile_imgid', $image_id );
    }
}

function cc_presenters_enqueue_admin_script( $hook ) {
	global $post;
    if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
        if ( 'presenter' === $post->post_type ) {
		    $contextual_theme = wp_get_theme();
		    $theme_ver = $contextual_theme->get('Version');
		    wp_enqueue_script( 'presenter-admin', get_template_directory_uri() . '/js/presenter-admin.js', array('jquery'), $theme_ver );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'cc_presenters_enqueue_admin_script' );


// the presenter metabox on workshop and recording edit pages
add_action( 'add_meta_boxes', 'cc_presenters_register_training_metabox' );
function cc_presenters_register_training_metabox(){
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	$post_types = array( 'workshop', 'recording', 'course' );
	add_meta_box( 'presenter_training_metabox', 'Presenters', 'cc_presenters_render_training_metabox', $post_types, 'side', 'default' );
}
function cc_presenters_render_training_metabox($post){
	wp_nonce_field( 'cc_presenters_training_metabox_nonce_action', 'cc_presenters_training_metabox_nonce' );
	?>
	<p>Which presenters would you like shown for this training?</p>
	<?php
	$training_presenters = cc_presenters_training_get_ids($post->ID);
	$included_presenters = array();
	foreach ($training_presenters as $training_presenter) {
		cc_presenters_render_training_presenter($training_presenter['id'], $training_presenter['order'], get_the_title( $training_presenter['id'] ), true, $training_presenter['image']);
		$included_presenters[] = $training_presenter['id'];
	}
	foreach (cc_presenters_get_all() as $presenter) {
		if( ! in_array( $presenter->ID, $included_presenters ) ){
			cc_presenters_render_training_presenter($presenter->ID, 0, $presenter->post_title);
		}
	}
}

// render a presenter within the metabox on the training page
function cc_presenters_render_training_presenter($presenter_id, $order, $presenter_name, $checked=false, $image=''){
	echo '<p><input type="number" class="presenter-order" name="presenter-order-'.$presenter_id.'" value="'.$order.'"><input type="checkbox" id="presenter-'.$presenter_id.'" name="presenter-'.$presenter_id.'" value="yes"';
	if( $checked ){
		echo ' checked';
	}
	echo '> <label for="presenter-'.$presenter_id.'">'.$presenter_name.'</label>';

	// image selection
	$image_1 = get_post_thumbnail_id( $presenter_id );
	$image_2 = get_post_meta( $presenter_id, '_second_profile_imgid', true );
	if(!empty($image_1) && !empty($image_2)){
		echo '<div class="training-presenter-image-chooser">';
		echo '<input type="radio" id="presenter-'.$presenter_id.'-img-random" name="presenter-'.$presenter_id.'-img" value="" '.checked($image, '', false).'><label for="presenter-'.$presenter_id.'-img-random">Random</label> ';
		echo '<input type="radio" id="presenter-'.$presenter_id.'-img-1" name="presenter-'.$presenter_id.'-img" value="1" '.checked($image, '1', false).'><label for="presenter-'.$presenter_id.'-img-1">'.wms_media_image_html($image_1, 'thumbnail', false, '1:1', '', '').'</label> ';
		echo '<input type="radio" id="presenter-'.$presenter_id.'-img-2" name="presenter-'.$presenter_id.'-img" value="2" '.checked($image, '2', false).'><label for="presenter-'.$presenter_id.'-img-2">'.wms_media_image_html($image_2, 'thumbnail', false, '1:1', '', '').'</label> ';
		echo '</div>';
	}

	echo '</p>';
}

// save the presenter training metabox
add_action('save_post', 'cc_presenters_save_training_metabox', 10, 2);
function cc_presenters_save_training_metabox($post_id, $post){
	if ( !isset( $_POST['cc_presenters_training_metabox_nonce'] ) || !wp_verify_nonce( $_POST['cc_presenters_training_metabox_nonce'], 'cc_presenters_training_metabox_nonce_action' ) ){
		return $post_id;
	}
	$post_type = get_post_type_object( $post->post_type );
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ){
		return $post_id;
	}
	$training_presenters = array();
	foreach (cc_presenters_get_all() as $presenter) {
		if(isset($_POST['presenter-'.$presenter->ID])){
			$order = 0;
			if(isset( $_POST['presenter-order-'.$presenter->ID] )){
				$order = (int) $_POST['presenter-order-'.$presenter->ID];
			}
			$image = '';
			if( isset( $_POST['presenter-'.$presenter->ID.'-img'] ) && ( $_POST['presenter-'.$presenter->ID.'-img'] == '1' || $_POST['presenter-'.$presenter->ID.'-img'] == '2' ) ){
				$image = $_POST['presenter-'.$presenter->ID.'-img'];
			}
			$training_presenters[] = array(
				'id' => $presenter->ID,
				'order' => $order,
				'image' => $image,
			);
		}
	}
	update_post_meta($post_id, '_training_presenters', $training_presenters);
}

// get all presenters
function cc_presenters_get_all( $orderby='', $order='' ){
	$args = array(
		'numberposts' => -1,
		'post_type' => 'presenter',
	);
	if( $orderby <> '' ){
		$args['orderby'] = $orderby;
	}
	if( $order <> '' ){
		$args['order'] = $order;
	}
	return get_posts($args);
}

// get the presenter IDs for a specific training
// always returns an array
function cc_presenters_training_get_ids( $training_id, $first_only=false ){
	$training_presenters = get_post_meta($training_id, '_training_presenters', true);
	if($training_presenters == ''){
		return array();
	}
	$last_element = $training_presenters;
	$last_element = array_pop($last_element);
	if(is_array($last_element)){
		// new format :-)
		if(count($training_presenters) > 1){
			// let's put them in order
			usort($training_presenters, fn($a, $b) => $a['order'] <=> $b['order']); // php 7.4+
			if( $first_only ){
				return array( $training_presenters[0] );
			}
		}
		return $training_presenters;
	}
	// reformat to add order and image
	$new_format = array();
	foreach ($training_presenters as $presenter_id) {
		$new_format[] = array(
			'id' => $presenter_id,
			'order' => 0,
			'image' => '',
		);
		if( $first_only ){
			return $new_format; // before a second element gets added
		}
	}
	return $new_format;
}

// the presenter info metabox
add_action('add_meta_boxes', 'cc_presenters_register_metaboxes');
function cc_presenters_register_metaboxes(){
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	add_meta_box( 'presenter_metabox', 'Presenter Info', 'cc_presenters_render_metabox', 'presenter', 'normal', 'default' );
}
function cc_presenters_render_metabox($post){
	wp_nonce_field( 'cc_presenters_metabox_nonce_action', 'cc_presenters_metabox_nonce' );
	?>
	<p>
		<label for="qualifications">Qualifications?</label><br>
		<input type="text" class="widefat" name="qualifications" id="qualifications" value="<?php echo esc_attr( get_post_meta($post->ID, 'qualifications', true) ); ?>">
	</p>
	<?php
}

// save the presenter metabox
add_action('save_post', 'cc_presenters_save_metabox', 10, 2);
function cc_presenters_save_metabox($post_id, $post){
	if ( !isset( $_POST['cc_presenters_metabox_nonce'] ) || !wp_verify_nonce( $_POST['cc_presenters_metabox_nonce'], 'cc_presenters_metabox_nonce_action' ) ){
		return $post_id;
	}
	$post_type = get_post_type_object( $post->post_type );
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ){
		return $post_id;
	}
	$qualifications = '';
	if(isset($_POST['qualifications']) && $_POST['qualifications'] <> ''){
		$qualifications = stripslashes( sanitize_text_field($_POST['qualifications']) );
	}
	update_post_meta($post_id, 'qualifications', $qualifications);
}

// gets the presenters' names and links to their pages
function cc_presenters_names($training_id, $links='modal'){
	$presenters = cc_presenters_training_get_ids($training_id);
	if(!is_array( $presenters ) ){
		return '';
	}
	$names = '';
	foreach ($presenters as $presenter) {
		if($names <> ''){
			$names .= ', ';
		}
		if($links == 'modal'){
			$names .= '<a href="#" data-bs-toggle="modal" data-bs-target="#presenter-modal" data-presenter="'.$presenter['id'].'">'.get_the_title($presenter['id']).'</a>';
		}elseif($links == 'none'){
			$names .= get_the_title($presenter['id']);
		}else{
			$names .= '<a href="'.get_permalink($presenter['id']).'">'.get_the_title($presenter['id']).'</a>';
		}
		
	}
	return $names;
}

// gets the presenters images carousel/single image
function cc_presenters_images( $training_id, $lazyload=false, $first_only=false, $bgimg_xclass='', $flag='' ){
	$presenters = cc_presenters_training_get_ids( $training_id, $first_only );
	if(!is_array( $presenters ) || count($presenters) == 0 || empty( $presenters ) ){
		return '';
	}
	if(count($presenters) == 1){
		// $post_thumbnail_id = get_post_thumbnail_id( $presenters[0]['id'] );
		$presenter_image_id = cc_presenters_image_id( $presenters[0]['id'], $presenters[0]['image'] );
		if ( empty( $presenter_image_id ) ){
			return '';
		}else{
			return wms_media_image_html($presenter_image_id, 'presenter-profile', true, 'unset', '', 'presenter-profile', false, $lazyload, $bgimg_xclass, $flag );
		}
	}
	// carousel
	$carousel_num = rand(0, 1000000000);
	$carousel_id = 'presenter-carousel-'.$carousel_num;
	$html = '<div id="'.$carousel_id.'" class="carousel slide presenter-carousel" data-bs-ride="carousel"><div class="carousel-inner">';
	// $css = '<style>';
	$item_class = ' active';
	foreach ($presenters as $presenter) {
		// $post_thumbnail_id = get_post_thumbnail_id( $presenter['id'] );
		$presenter_image_id = cc_presenters_image_id( $presenter['id'], $presenter['image'] );
		if (! empty( $presenter_image_id ) ){
			$image_data = wp_get_attachment_image_src($presenter_image_id, 'presenter-profile');
			if($image_data){
				$slider_id = $carousel_id.'-'.$presenter['id'];
				$image_url = $image_data[0];
				if( $lazyload ){
					$html .= '<div id="'.$slider_id.'" class="wms-bg-img lazy presenter-image carousel-item'.$item_class.'" data-src="'.$image_url.'">';
				}else{
					$html .= '<div id="'.$slider_id.'" class="wms-bg-img presenter-image carousel-item'.$item_class.'" style="background-image:url('.$image_url.');">';
				}
				$html .= '<div class="item-overlay">';
				$html .= '<div class="carousel-caption"><h5 class="mb-1">'.get_the_title($presenter['id']).'</h5></div>';
				$html .= '</div>';
				if( $flag <> '' ){
	                $html .= '<div class="train-card-flag">'.$flag.'</div>';
				}
				$html .= '</div>';
				// $css .= '#'.$slider_id.' {background-image:url('.$image_url.');}';
				$item_class = '';
			}
		}
	}
	$html .= '</div></div>';
	// $css .= '</style>';
	// return $html . $css;
	return $html;
}

// get an image id for a presenter
function cc_presenters_image_id($presenter_id, $image=''){
	$image_1 = get_post_thumbnail_id( $presenter_id );
	$image_2 = get_post_meta( $presenter_id, '_second_profile_imgid', true );
	if( empty($image_1) && empty( $image_2 ) ){
		return false;
	}
	if( empty( $image_2 ) ){
		return $image_1;
	}
	if( empty( $image_1 ) ){
		return $image_2;
	}
	switch ($image) {
		case '1':		return $image_1;						break;
		case '2':		return $image_2;						break;
		default:		return ${'image_'.mt_rand(1,2)};		break;
	}
}

// populate presenter modal
add_action('wp_ajax_presenter_modal_get', 'cc_presenters_presenter_modal_get');
add_action('wp_ajax_nopriv_presenter_modal_get', 'cc_presenters_presenter_modal_get');
function cc_presenters_presenter_modal_get(){
	$response = array(
		'status' => 'error',
		'body' => '',
		'title' => '',
	);
	$presenter_id = 0;
	if(isset($_POST['presenterID'])){
		$presenter_id = (int) $_POST['presenterID'];
	}
	if($presenter_id > 0){
		$presenter = get_post($presenter_id);
		if($presenter){
			$response['title'] = $presenter->post_title;

			$image_url = '';
			// $post_thumbnail_id = get_post_thumbnail_id( $presenter_id );
			$presenter_image_id = cc_presenters_image_id( $presenter_id );
			if (! empty( $presenter_image_id ) ){
				$image_data = wp_get_attachment_image_src($presenter_image_id, 'presenter-profile');
				if($image_data){
					$image_url = $image_data[0];
				}
			}

			$response['body'] = '<div class="clearfix">';
			if($image_url <> ''){
				$response['body'] .= '<img src="'.$image_url.'" class="presenter-profile col-md-6 float-md-end mb-3 ms-md-3" alt="'.$presenter->post_title.'">';
			}
			$qualifications = get_post_meta($presenter_id, 'qualifications', true);
			if($qualifications <> ''){
				$response['body'] .= '<h5>'.$qualifications.'</h5>';
			}

			// some content or part of some content to show ...
			if( strlen( $presenter->post_content ) < 400 ){
				$response['body'] .= apply_filters( 'the_content', $presenter->post_content );
			}else{
				$text = wp_trim_words( $presenter->post_content, 55, ' [&hellip;]' ); // also strips tags
				$response['body'] .= '<p>'.apply_filters( 'get_the_excerpt', $text ).'</p>';
			}
			$response['body'] .= '<p><a href="'.get_permalink($presenter_id).'" target="_blank">Read more and see training presented by '.$presenter->post_title.'</a></p>';
			
			$response['body'] .= '</div>';

			$response['status'] = 'ok';
		}
	}
    echo json_encode($response);
    die();
}

// get upcoming workshop ids for a presenter
function cc_presenters_workshops($presenter_id){
	// start by getting all upcoming workshops
	$upcoming_workshops = workshop_archive_get_posts();
	// and then choose the ones with this presenter
	$presenters_workshops = array();
	foreach ($upcoming_workshops as $workshop) {
		$presenters = cc_presenters_training_get_ids($workshop->ID);
		foreach ($presenters as $presenter) {
			if( $presenter['id'] == $presenter_id ){
				$presenters_workshops[] = $workshop;
				break;
			}
		}
	}
	return $presenters_workshops;
}

// get all recording ids for a presenter
function cc_presenters_recordings($presenter_id){
	// start by getting all recording ids (that could be shown)
	$all_recording_ids = recording_archive_get_posts(false);
	// and then choose the ones with this presenter
	$presenters_recordings = array();
	foreach ($all_recording_ids as $recording_id) {
		$presenters = cc_presenters_training_get_ids($recording_id);
		foreach ($presenters as $presenter) {
			if( $presenter['id'] == $presenter_id ){
				$presenters_recordings[] = $recording_id;
				break;
			}
		}
	}
	return $presenters_recordings;
}

// get all presenter ids for a group of trainings
// $training_ids should be an array
// will always return an array
function cc_presenters_group( $training_ids ){
	global $wpdb;

	// Ensure the input is an array and not empty
    if (!is_array($training_ids) || empty($training_ids)) {
        return []; // Return an empty array if input is invalid
    }

    // Prepare the training_ids for the SQL query
    $training_id_string = implode( ',', $training_ids );

    // Prepare and execute the SQL query to get the presenter data from wp_postmeta
    $sql = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_training_presenters' AND post_id IN ($training_id_string)";
    $results = $wpdb->get_results( $sql, ARRAY_A );
    /* I have no idea why this didn't work ... :-(
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT meta_value 
            FROM $wpdb->postmeta
            WHERE meta_key = '_training_presenters'
            AND post_id IN (%s)
            ",
            $training_id_string
        ),
        ARRAY_A
    );
    */

    // Initialize an empty array to store unique presenter IDs
    $presenter_ids = [];

    // Loop through the results
    foreach ($results as $result) {
        // Unserialize the meta_value to get the array of presenters
        $presenters = maybe_unserialize($result['meta_value']);

        // Ensure it's an array and process each presenter
        if (is_array($presenters)) {
            foreach ($presenters as $presenter) {
                // Check if 'id' exists in the presenter array
                if (isset($presenter['id'])) {
                    $presenter_ids[] = $presenter['id']; // Collect the presenter ID
                }
            }
        }
    }

    // Remove duplicate IDs and return the unique array
    $presenter_ids = array_unique($presenter_ids);

    // sort them into alphabetical order
	usort( $presenter_ids, function( $a, $b ) {
	    return strcmp( get_the_title( $a ), get_the_title( $b ) );
	});

    return $presenter_ids;
}