<?php
/**
 * WMS
 * Page slider - the slideshow on pages
 *
 * Includes:
 * - wms_page_slider_admin_enqueue
 * - wms_page_slider_add_metaboxes
 * - wms_page_slider_control_callback
 * - wms_page_slider_control_save
 * - wms_page_slider_slides_callback
 * - wms_page_slider_save
 * - wms_page_slider
 */

/**
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_action( 'admin_enqueue_scripts', 'wms_page_slider_admin_enqueue' );
add_action('add_meta_boxes', 'wms_page_slider_add_metaboxes');
add_action('save_post', 'wms_page_slider_control_save');
add_action('save_post', 'wms_page_slider_save');


// add the metaboxes for the slides and the controller to pages
function wms_page_slider_add_metaboxes(){
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	add_meta_box('wms_page_slider_control_metabox', 'What To Show', 'wms_page_slider_control_callback', array('page', 'post', 'workshop', 'recording', 'course'), 'side');
	add_meta_box('wms_page_slider_slides_metabox', 'Slides', 'wms_page_slider_slides_callback', 'page', 'side');
	add_meta_box('wms_page_content_control_metabox', 'Page content', 'wms_page_content_control_callback', 'page', 'side');
}

// enqueue the page slider admin js
function wms_page_slider_admin_enqueue($hook) {
	global $post;
    if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
        if ( 'page' === $post->post_type ) {
			if ( ! did_action( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
		  	wp_enqueue_script( 'page_slider_admin_script', get_stylesheet_directory_uri() . '/inc/wms/page-slider-admin.js', array('jquery'), null, false );
		}
	}
}

// the control metabox - what to show?
// used on pages, posts and training ... with some minor differences
function wms_page_slider_control_callback($post){
	global $rpm_theme_options;
	wp_nonce_field( 'page_slider_control_metabox', 'page_slider_control_nonce' );
	$page_slider_control = get_post_meta($post->ID, '_page_slider_control', true);
	$page_title_locn = get_post_meta($post->ID, '_page_title_locn', true);
	$featured_headline_override = get_post_meta($post->ID, '_featured_headline_override', true);
	$featured_text_override = get_post_meta($post->ID, '_featured_text_override', true);
	$featured_bg_position = get_post_meta($post->ID, '_featured_bg_position', true);
	$featured_height = get_post_meta($post->ID, '_featured_height', true);
	$featured_wash_opacity = get_post_meta($post->ID, '_featured_wash_opacity', true);
	echo '<p>This panel controls what image/s is/are shown at the top of the page.</p>';

	if($post->post_type == 'page'){
		echo '<h4>What to show</h4><p><label for="wms_page_slider_control_show">What to show:';
		echo '<select class="widefat" id="wms_page_slider_control_show" name= "wms_page_slider_control_show">';
		echo '<option value="">Featured Image (see Featured Image panel)</option>';
		$selected = ($page_slider_control == 'map1' || $page_slider_control == 'map') ? ' selected="selected"' : '';
		echo '<option value="map1"'.$selected.'>Location map</option>';
		/*
		if($rpm_theme_options['second-locn']){
			$selected = ($page_slider_control == 'map2') ? ' selected="selected"' : '';
			echo '<option value="map2"'.$selected.'>2nd location map</option>';
		}
		*/
		$selected = ($page_slider_control == 'slider') ? ' selected="selected"' : '';
		echo '<option value="slider"'.$selected.'>Slideshow + slide text (see Slideshow panel)</option>';
		$selected = ($page_slider_control == 'slider-common') ? ' selected="selected"' : '';
		echo '<option value="slider-common"'.$selected.'>Slideshow + common text (see Slideshow panel)</option>';
		$selected = ($page_slider_control == 'video') ? ' selected="selected"' : '';
		echo '<option value="video"'.$selected.'>Video</option>';
		$selected = ($page_slider_control == 'nothing') ? ' selected="selected"' : '';
		echo '<option value="nothing"'.$selected.'>Show nothing</option>';
		echo '</select></label></p>';
	}else{
		echo '<input type="hidden" name="wms_page_slider_control_show" value="">';
	}

	echo '<h4>Background position</h4><p>Applies to the featured image (if set). Which part of the image do you want to be the focus point? The site will try to keep the focus point in the middle of the visible area on all screen sizes. The default is the centre (center). Other values you can use are keywords (eg right top) or percentages (eg 33% 60%).</p>';
	echo '<p><label for="wms_featslide_bg_position">Background position:';
	echo '<input type="text" id="wms_featslide_bg_position" name="wms_featslide_bg_position" class="widefat" value="'.$featured_bg_position.'" placeholder="center">';
	echo '</label></p>';

	if( $post->post_type == 'workshop' || $post->post_type == 'recording' || $post->post_type == 'course' ){
		echo '<h4>Image height (desktop)</h4><p>How tall would you like the image to be (in pixels)? If not set, it will be the smallest size it needs to be to accommodate the text: usually at least 200px. Smaller heights will automatically be used on smaller screens.</p>';
		echo '<p><label for="wms_featslide_height">Image height (desktop):';
		echo '<input type="text" id="wms_featslide_height" name="wms_featslide_height" value="'.$featured_height.'">';
		echo '</label></p>';
	}else{
		echo '<input type="hidden" name="wms_featslide_height" value="">';
	}

	echo '<h4>Image wash</h4><p>This sets the opacity of the colour wash on top of the image. Set a number between 0 and 1. 0 = completely transparent (no wash), 1 = completely opaque (image behind not visible). The default is 0.65</p>';
	echo '<p><label for="wms_featslide_wash_opacity">Image wash:';
	echo '<input type="text" id="wms_featslide_wash_opacity" name="wms_featslide_wash_opacity" value="'.$featured_wash_opacity.'" placeholder="0.65">';
	echo '</label></p>';

	echo '<h4>Page Title</h4><p>By default this is shown below the main image. However, if you want and if no Image Headline is set, it can be displayed on top of the image instead.</p>';
	echo '<p><label for="wms_page_title_locn">Page Title:';
	echo '<select class="widefat" id="wms_page_title_locn" name= "wms_page_title_locn">';
	echo '<option value="">Show below page image</option>';
	echo '<option value="hero" '.selected($page_title_locn, 'hero', false).'>Show on top of page image</option>';
	echo '</select></label></p>';

	if($post->post_type == 'page'){
		echo '<h4>Common text</h4><p>Shown on top of featured image, slideshow + common text and video, otherwise ignored</p>';
		echo '<p><label for="wms_featslide_headline_override">Image Headline:';
		echo '<textarea id="wms_featslide_headline_override" class="widefat" name="wms_featslide_headline_override">'.$featured_headline_override.'</textarea>';
		echo '</label></p>';

		echo '<p><label for="wms_featslide_text_override">Image Text:';
		echo '<textarea id="wms_featslide_text_override" class="widefat" name="wms_featslide_text_override">'.$featured_text_override.'</textarea>';
		echo '</label></p>';
		
		// we'll allow for three buttons on the home page and one on other pages
		if( $post->ID == get_option( 'page_on_front' )){
			$num_btns = 3;
		}else{
			$num_btns = 1;
		}
		for ($i=0; $i < $num_btns; $i++) { 
			$btn_text = get_post_meta($post->ID, '_btn_text_'.$i, true);
			$btn_link = get_post_meta($post->ID, '_btn_link_'.$i, true);
			$btn_tgt = get_post_meta($post->ID, '_btn_tgt_'.$i, true);
			$btn_class = get_post_meta($post->ID, '_btn_class_'.$i, true);
			echo '<h4>Button '.($i + 1).'</h4>';
			echo '<p><label for="btn_text_'.$i.'">Button text:';
			echo '<input type="text" id="btn_text_'.$i.'" name="btn_text_'.$i.'" class="widefat" value="'.$btn_text.'">';
			echo '</label></p>';
			echo '<p><label for="btn_link_'.$i.'">Link to:';
			echo '<input type="text" id="btn_link_'.$i.'" name="btn_link_'.$i.'" class="widefat" value="'.$btn_link.'" placeholder="http://...">';
			echo '</label></p>';
			echo '<p><label for="btn_tgt_'.$i.'">Open in new window?';
			echo '<select class="widefat" id="btn_tgt_'.$i.'" name="btn_tgt_'.$i.'">';
			echo '<option value="" '.selected('', $btn_tgt, false).'>No</option>';
			echo '<option value="yes" '.selected('yes', $btn_tgt, false).'>Yes</option>';
			echo '</select></label></p>';
			echo '<p><label for="btn_class_'.$i.'">Button class:';
			echo '<input type="text" id="btn_class_'.$i.'" name="btn_class_'.$i.'" class="widefat" value="'.$btn_class.'">';
			echo '</label></p>';
		}
	}else{
		// old post stuff ....
		/*echo '<h4>Page Title</h4><p>By default this is shown below the main image. However, if you want and if no Image Headline is set, it can be displayed on top of the image instead.</p>';
		echo '<p><label for="wms_page_title_locn">Page Title:';
		echo '<select class="widefat" id="wms_page_title_locn" name= "wms_page_title_locn">';
		echo '<option value="">Show below page image</option>';
		echo '<option value="hero" '.selected($page_title_locn, 'hero', false).'>Show on top of page image</option>';
		echo '</select></label></p>';

		echo '<h4>Background position</h4><p>Applies to the featured image (if set). Which part of the image do you want to be the focus point? The site will try to keep the focus point in the middle of the visible area on all screen sizes. The default is the centre (center). Other values you can use are keywords (eg right top) or percentages (eg 33% 60%).</p>';
		echo '<p><label for="wms_featslide_bg_position">Background position:';
		echo '<input type="text" id="wms_featslide_bg_position" name="wms_featslide_bg_position" class="widefat" value="'.$featured_bg_position.'" placeholder="center">';
		echo '</label></p>';*/

		echo '<input type="hidden" name="wms_featslide_headline_override" value="">';
		echo '<input type="hidden" name="wms_featslide_text_override" value="">';
	}
}

// save the control metabox data when the page is saved
function wms_page_slider_control_save($post_id){
	if ( ! isset( $_POST['page_slider_control_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['page_slider_control_nonce'], 'page_slider_control_metabox' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}
	if(!isset($_POST['wms_page_slider_control_show'])){
		return;
	}
	update_post_meta( $post_id, '_page_slider_control', $_POST['wms_page_slider_control_show'] );
	update_post_meta( $post_id, '_page_title_locn', $_POST['wms_page_title_locn'] );
	update_post_meta( $post_id, '_featured_headline_override', $_POST['wms_featslide_headline_override'] );
	update_post_meta( $post_id, '_featured_text_override', $_POST['wms_featslide_text_override'] );
	update_post_meta( $post_id, '_featured_bg_position', $_POST['wms_featslide_bg_position'] );
	update_post_meta( $post_id, '_featured_height', $_POST['wms_featslide_height'] );
	update_post_meta( $post_id, '_featured_wash_opacity', $_POST['wms_featslide_wash_opacity'] );
	for ($i=0; $i < 3; $i++) { 
		if(isset($_POST['btn_text_'.$i])){
			$btn_text = sanitize_text_field($_POST['btn_text_'.$i]);
		}else{
			$btn_text = '';
		}
		update_post_meta( $post_id, '_btn_text_'.$i, $btn_text);
		if(isset($_POST['btn_link_'.$i])){
			$btn_link = esc_url($_POST['btn_link_'.$i]);
		}else{
			$btn_link = '';
		}
		update_post_meta( $post_id, '_btn_link_'.$i, $btn_link);
		if(isset($_POST['btn_tgt_'.$i]) && $_POST['btn_tgt_'.$i] == 'yes'){
			$btn_tgt = 'yes';
		}else{
			$btn_tgt = '';
		}
		update_post_meta( $post_id, '_btn_tgt_'.$i, $btn_tgt);
		if(isset($_POST['btn_class_'.$i])){
			$btn_class = sanitize_text_field($_POST['btn_class_'.$i]);
		}else{
			$btn_class = '';
		}
		update_post_meta( $post_id, '_btn_class_'.$i, $btn_class);
	}
}

// the metabox for the slides on pages
function wms_page_slider_slides_callback($post){
	wp_nonce_field( 'page_slider_metabox', 'page_slider_nonce' );
	// Get WordPress media upload URL
	$upload_link = esc_url( get_upload_iframe_src( 'image', $post->ID ) );
	// retrieve the slider data
	$slider_data = get_post_meta($post->ID, '_slider_data', true);
	if(!is_array($slider_data)){
		$slider_data = array();
	}
	echo '<p>To display a slideshow on the page, you must <strong>select <em>Page Slideshow</em> in the Featured Slide panel</strong> above.</p>';
	echo '<input type="hidden" id="wms_page_slider_current_upload">';
	// up to five slides
	for ($i=0; $i < 5; $i++) {
		echo '<hr><h4>Slide '.($i+1).'</h4>';
		// slider image
		$have_image = false;
		if(isset($slider_data[$i]['id'])){
			$image_src = wp_get_attachment_image_src($slider_data[$i]['id'], 'full');
			$have_image = is_array($image_src);
		}
		echo '<div id="wms-page-slider-img-container-'.$i.'" class="wms-page-slider-img-container">';
		if($have_image){
			echo '<img src="'.$image_src[0].'" alt="" style="max-width:100%;">';
		}
		echo '</div>';
		// image ID
		echo '<input id="wms_page_slider_img_id_'.$i.'" name="wms_page_slider_img_id_'.$i.'" type="hidden" value="';
		if($have_image) echo $slider_data[$i]['id'];
		echo '">';
		// add and remove image links
		echo '<p class="hide-if-no-js"><a data-slidenum="'.$i.'" id="wms-page-slider-img-upload-'.$i.'" class="wms-page-slider-img-upload';
		if($have_image) echo ' hidden';
		echo '" href="'.$upload_link.'">Set slider image</a><a data-slidenum="'.$i.'" id="wms-page-slider-img-delete-'.$i.'" class="wms-page-slider-img-delete';
		if(!$have_image) echo ' hidden';
		echo '" href="#">Remove this image</a></p>';
		// text fields
		echo '<p><label for="wms_page_slider_headline_'.$i.'">Headline:<br>';
		echo '<input type="text" id="wms_page_slider_headline_'.$i.'" name="wms_page_slider_headline_'.$i.'" class="widefat" value="';
		if(isset($slider_data[$i]['headline'])) echo $slider_data[$i]['headline'];
		echo '"></label></p>';
		echo '<p><label for="wms_page_slider_text_'.$i.'">Text:<br>';
		echo '<textarea id="wms_page_slider_text_'.$i.'" name="wms_page_slider_text_'.$i.'" class="widefat">';
		if(isset($slider_data[$i]['text'])) echo $slider_data[$i]['text'];
		echo '</textarea></label></p>';
		// button fields
		echo '<p><label for="wms_page_slider_btn_text_'.$i.'">Button text:<br>';
		echo '<input type="text" id="wms_page_slider_btn_text_'.$i.'" name="wms_page_slider_btn_text_'.$i.'" class="widefat" value="';
		if(isset($slider_data[$i]['btn_text'])) echo $slider_data[$i]['btn_text'];
		echo '"></label></p>';
		echo '<p><label for="wms_page_slider_btn_link_'.$i.'">Button link:<br>';
		echo '<input type="text" id="wms_page_slider_btn_link_'.$i.'" name="wms_page_slider_btn_link_'.$i.'" class="widefat" value="';
		if(isset($slider_data[$i]['btn_link'])) echo $slider_data[$i]['btn_link'];
		echo '"></label></p>';
		echo '<p><label for="wms_page_slider_btn_color_'.$i.'">Button colour:<br>';
		echo '<input type="text" id="wms_page_slider_btn_color_'.$i.'" name="wms_page_slider_btn_color_'.$i.'" class="widefat" value="';
		if(isset($slider_data[$i]['btn_color'])) echo $slider_data[$i]['btn_color'];
		echo '"></label></p>';
	}
}
function wms_page_slider_save($post_id){
	if(!isset($_POST['page_slider_nonce'])) return;
	if(!wp_verify_nonce( $_POST['page_slider_nonce'], 'page_slider_metabox')) return;
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if(!isset($_POST['post_type']) || $_POST['post_type'] <> 'page') return;
	if(!current_user_can('edit_page', $post_id)) return;
	$slider_data = array();
	for ($i=0; $i < 5; $i++) { 
		if(isset($_POST['wms_page_slider_img_id_'.$i]) && $_POST['wms_page_slider_img_id_'.$i] <> ''){
			$slider_data[$i]['id'] = (int) $_POST['wms_page_slider_img_id_'.$i];
		}
		if(isset($_POST['wms_page_slider_headline_'.$i])){
			$slider_data[$i]['headline'] = sanitize_text_field($_POST['wms_page_slider_headline_'.$i]);
		}
		if(isset($_POST['wms_page_slider_text_'.$i])){
			$slider_data[$i]['text'] = sanitize_text_field($_POST['wms_page_slider_text_'.$i]);
		}
		if(isset($_POST['wms_page_slider_btn_text_'.$i])){
			$slider_data[$i]['btn_text'] = sanitize_text_field($_POST['wms_page_slider_btn_text_'.$i]);
		}
		if(isset($_POST['wms_page_slider_btn_link_'.$i])){
			$slider_data[$i]['btn_link'] = sanitize_text_field($_POST['wms_page_slider_btn_link_'.$i]);
		}
		if(isset($_POST['wms_page_slider_btn_color_'.$i])){
			$slider_data[$i]['btn_color'] = sanitize_text_field($_POST['wms_page_slider_btn_color_'.$i]);
		}
	}
	update_post_meta($post_id, '_slider_data', $slider_data);
}

/*
 * @param string $name Name of option or name of post custom field.
 * @param string $value Optional Attachment ID
 * @return string HTML of the Upload Button
 */
function misha_image_uploader_field( $name, $value = '') {
	$image = ' button">Upload image';
	$image_size = 'full'; // it would be better to use thumbnail size here (150x150 or so)
	$display = 'none'; // display state ot the "Remove image" button
	if( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {
		// $image_attributes[0] - image URL
		// $image_attributes[1] - image width
		// $image_attributes[2] - image height
		$image = '"><img src="' . $image_attributes[0] . '" style="max-width:95%;display:block;" />';
		$display = 'inline-block';
	} 
	return '
	<div>
		<a href="#" class="misha_upload_image_button' . $image . '</a>
		<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $value . '" />
		<a href="#" class="misha_remove_image_button" style="display:inline-block;display:' . $display . '">Remove image</a>
	</div>';
}

// featured slide function will display the page's featured image or a map or a page's individual slideshow .... neat huh?
// looks for custom field "_page_slider_control" to find out what is required
function wms_page_slider($includelink = ''){
	global $wpdb;
	global $wp_query;
	global $rpm_theme_options;
	$feat_slide_width = 'full-screen';
	if(isset($rpm_theme_options['feat-slide-width'])){
		$feat_slide_width = $rpm_theme_options['feat-slide-width'];
	}
	$slidehtml = '';
	// find out what we need to display
	$postid = get_the_ID();
	$whats_wanted = '';
	if(is_home()){
		// posts/news page
		$postid = get_option('page_for_posts');
	}else{
	// if(!is_home() || $whats_wanted == ''){
		if(isset($wp_query->post->ID)){
		    $postid = $wp_query->post->ID;
		}
	}
	$whats_wanted = get_post_meta($postid, '_page_slider_control', true);
	// whats_wanted can be:
	// - 'nothing' = show nothing
	// - '' = featured image (if exists)
	// - 'map' or 'map1' = map for location 1
	// - 'map2' = map for location 2
	// - 'slider' = page slider
	// - 'slider-common' = page slider but with common text and btns on top of the slider
	if($whats_wanted == 'nothing'){
		// show nothing
		return apply_filters('wms_page_slider_filter', $slidehtml);
	}
	if($whats_wanted == ''){
		// $whats_wanted of '' means use featured image if it exists (on every type of page)
		if (has_post_thumbnail($postid)) {
			// the featured image
			$slidehtml .= '<div id="home-slideshow" class="slide-container '.$feat_slide_width.'">';
			$slidehtml .= '<div class="slide">';
			$slidehtml .= '<div class="wp-post-image">';
			// $img_id, $selector, $bg_pos='', $bg_height='', $bg_wash_opacity=''
			$slidehtml .= '<style>'.wms_page_slider_slide_css(
				get_post_thumbnail_id($postid),
				'.feat-img-resp.visible',
				get_post_meta($postid, '_featured_bg_position', true),
				get_post_meta( $postid, '_featured_height', true), 
				get_post_meta( $postid, '_featured_wash_opacity', true)
			).'</style>';
			// $slidehtml .= '<div class="item feat-img-resp parallax">';
			$slidehtml .= '<div class="carousel-item active feat-img-resp wms-bg-img parallax">';
	        $headline_toshow = '';
        	$headline_toshow = get_post_meta($postid, '_featured_headline_override', true);
        	if($headline_toshow == ''){
				$page_title_locn = get_post_meta($postid, '_page_title_locn', true);
				if($page_title_locn == 'hero'){
					$headline_toshow = get_the_title();
				}
        	}
	    	$text_toshow = '';
	        $text_toshow = get_post_meta($postid, '_featured_text_override', true);
			if($headline_toshow <> '' || $text_toshow <> ''){
				$slidehtml .= '<div class="item-overlay">';
				$slidehtml .= '<div class="caption-container">';
				$slidehtml .= '<div class="carousel-caption">';
				$slidehtml .= '<h2 class="headline">'.do_shortcode($headline_toshow).'</h2>';
				$slidehtml .= '<p class="subhead">'.do_shortcode($text_toshow).'</p>';
				$slidehtml .= wms_featured_slide_btns();
				$slidehtml .= '</div>';
				$slidehtml .= '</div>';
				$slidehtml .= '</div>';
			/*
			}elseif($rpm_theme_options['slideshow-text-bg-always']){
				$slidehtml .= '<div class="item-overlay">';
				$slidehtml .= '</div>';
			*/
			}
			$slidehtml .= '</div>';
			$slidehtml .= '</div>';
			$slidehtml .= '</div>';
			$slidehtml .= '</div>';
		}
	}
	if($slidehtml == '' && ($whats_wanted == 'slider' || $whats_wanted == 'slider-common')){
		// We're after page slides
		// we use this for slides on the home page too now ...
		$slider_count = 0;
		$indicators = '';
		$slides = '';
		$slide_css = '';
		$slider_data = get_post_meta($postid, '_slider_data', true);
		// default headline ...
        $headline_toshow = '';
    	$headline_toshow = get_post_meta($postid, '_featured_headline_override', true);
    	if($headline_toshow == ''){
			$page_title_locn = get_post_meta($postid, '_page_title_locn', true);
			if($page_title_locn == 'hero'){
				$headline_toshow = get_the_title();
			}
    	}
		if(is_array($slider_data)){
			for ($i=0; $i < 5; $i++) {
				if(isset($slider_data[$i]['id'])){
					$image_id = (int) $slider_data[$i]['id'];
					$image_src = wp_get_attachment_image_src($slider_data[$i]['id'], 'full');
					if(is_array($image_src)){
						// ok we really do have an image here
						if($slider_count == 0){
							// first image
							$indicators .= '<li data-target="#home-slideshow" data-slide-to="0" class="active"></li>';
							$slides .= '<div class="carousel-item active slide-0">';
						}else{
							$indicators .= '<li data-target="#home-slideshow" data-slide-to="'.$slider_count.'"></li>';
							$slides .= '<div class="carousel-item slide-'.$slider_count.'">';
						}
						$slide_css .= wms_page_slider_slide_css(
							$slider_data[$i]['id'],
							'.slide-'.$slider_count,
							get_post_meta( $postid, '_featured_bg_position', true),
							get_post_meta( $postid, '_featured_height', true), 
							get_post_meta( $postid, '_featured_wash_opacity', true)
						);
						$slides .= '<div class="item-overlay">';
						if($whats_wanted == 'slider' && ($slider_data[$i]['headline'] <> '' || $slider_data[$i]['text'] <> '' || $headline_toshow <> '')){
							$slides .= '<div class="caption-container caption-'.$rpm_theme_options['slideshow-text-pos'].'">';
							$slides .= '<div class="carousel-caption">';
							$slides .= '<h2 class="headline">';
							if($slider_data[$i]['headline'] == ''){
								$slides .= $headline_toshow;
							}else{
								$slides .= $slider_data[$i]['headline'];
							}
							$slides .= '</h2>';
							$slides .= '<p class="subhead">'.$slider_data[$i]['text'].'</p>';
							if($slider_data[$i]['btn_text'] <> '' && $slider_data[$i]['btn_link'] <> ''){
								$slides .= '<p class="featured-btns">';
								$slides .= '<a class="btn btn-feat btn-lg';
								// if a real colour then we'll use that, if not we'll set it as a class
								if(wms_validate_html_color($slider_data[$i]['btn_color']) == ''){
									$slides .= ' btn-'.$slider_data[$i]['btn_color'].'"';
								}else{
									$slides .= '" style="background:'.$slider_data[$i]['btn_color'].';"';
								}
								$slides .= ' href="'.$slider_data[$i]['btn_link'].'" role="button">'.$slider_data[$i]['btn_text'].'</a>';
								$slides .= '</p>';
							}
							$slides .= '</div><!-- .carousel-caption -->';
							$slides .= '</div><!-- .container -->';
						}
						$slides .= '</div><!-- .item-overlay -->';
						$slides .= '</div><!-- .carousel-item -->';
						$slider_count ++;
					}
				}
			}
		}
		if($slider_count > 1){
			$hider = '';
		}else{
			$hider = ' hide';
		}
		if($slider_count > 0){
			$slidehtml .= '<style>'.$slide_css.'</style>';
			$slidehtml .= '<div class="slide-container '.$feat_slide_width;
			if(is_front_page() && isset($rpm_theme_options['home-slider-bg']['url']) && $rpm_theme_options['home-slider-bg']['url'] <> ''){
				$slidehtml .= ' wms-bg-img" style="background-image:url('.$rpm_theme_options['home-slider-bg']['url'].');';
			}
			$slidehtml .= '">';
			$slide_interval_att = '';
			if(isset($rpm_theme_options['feat-slide-interval'])){
				$slide_interval = intval($rpm_theme_options['feat-slide-interval']);
				if($slide_interval > 0){
					$slide_interval_att = ' data-interval="'.$slide_interval.'"';
				}
			}
			$slidehtml .= '<div id="home-slideshow" class="carousel slide" data-ride="carousel"'.$slide_interval_att.'>';
			$slidehtml .= '<!-- Indicators -->';
			$slidehtml .= '<ol class="carousel-indicators'.$hider.'">'.$indicators.'</ol>';
			$slidehtml .= '<!-- Wrapper for slides -->';
			$slidehtml .= '<div class="carousel-inner" role="listbox">'.$slides.'</div>';
			$slidehtml .= '<!-- Left and right controls -->';
			$slidehtml .= '<a class="carousel-control-prev'.$hider.'" href="#home-slideshow" role="button" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a>';
			$slidehtml .= '<a class="carousel-control-next'.$hider.'" href="#home-slideshow" role="button" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a>';
			$slidehtml .= '</div><!-- #home-slideshow -->';
			if($whats_wanted == 'slider-common'){
				$headline_toshow = get_post_meta($postid, '_featured_headline_override', true);
				$text_toshow = get_post_meta($postid, '_featured_text_override', true);
				if($headline_toshow <> '' || $text_toshow <> ''){
					$slidehtml .= '<div id="common-overlay" class="item-overlay">';
					$slidehtml .= '<div class="caption-container caption-'.$rpm_theme_options['slideshow-text-pos'].'">';
					$slidehtml .= '<div class="carousel-caption">';
					$slidehtml .= '<h2 class="headline">'.do_shortcode($headline_toshow).'</h2>';
					$slidehtml .= '<p class="subhead">'.do_shortcode($text_toshow).'</p>';
					$slidehtml .= wms_featured_slide_btns();
					$slidehtml .= '</div>';
					$slidehtml .= '</div>';
					$slidehtml .= '</div>';
				}
			}
			$slidehtml .= '</div><!-- .slide-container -->';
		}
	}
	if($slidehtml == '' && substr($whats_wanted,0,3) == 'map'){
		if($whats_wanted == 'map' || $whats_wanted == 'map1'){
			$business_name = $rpm_theme_options['business-name'];
			$address_line = wms_address_formatted(1, ', ');
			$latitude = $rpm_theme_options['latitude'];
			$longitude = $rpm_theme_options['longitude'];
			$map_zoom = $rpm_theme_options['map-zoom'];
		}else{
			// must be map2
			$business_name = $rpm_theme_options['business-name-2'];
			$address_line = wms_address_formatted(2, ', ');
			$latitude = $rpm_theme_options['latitude-2'];
			$longitude = $rpm_theme_options['longitude-2'];
			$map_zoom = $rpm_theme_options['map-zoom-2'];
		}
		$map_height = $rpm_theme_options['slideshow-ht-lg']['height'];
		$map_num = 'map_canvas_'.rand(1000,9999);
		$contentString = '<div id="content-'.$map_num.'"><h4 id="gMapHeading-'.$map_num.'" class="gMapHeading">'.esc_attr($business_name).'</h4><div><p>'.esc_attr($address_line).'</p></div></div>';
		$slidehtml .= '<div class="slide-container '.$feat_slide_width.'">';
		$slidehtml .= '<div class="wp-post-map">';
		$slidehtml .= '<div class="">';
		$slidehtml .= '<div class="google-map">';
		$slidehtml .= '<div id="map_canvas_'.$map_num.'" class="canvas" style="height:'.$map_height.'px;"></div>';
		$slidehtml .= '<script>';
		$slidehtml .= 'var myLatlng = new google.maps.LatLng('.$latitude.','.$longitude.');';
		$map_style = $rpm_theme_options['map-style'];
		if($map_style == 'desat'){
			$map_styling = ', styles: [{stylers: [{saturation: -100}] }]';
		}else{
			$map_styling = '';
		}
		$slidehtml .= 'var mapOptions = {zoom: '.$map_zoom.', center: myLatlng, mapTypeId: google.maps.MapTypeId.ROADMAP, scrollwheel: false'.$map_styling.'};';
		$slidehtml .= 'var map'.$map_num.' = new google.maps.Map(document.getElementById("map_canvas_'.$map_num.'"), mapOptions);';
		$slidehtml .= 'var contentString'.$map_num.' = \''.$contentString.'\';';
		$slidehtml .= 'var infowindow'.$map_num.' = new google.maps.InfoWindow({content: contentString'.$map_num.', maxWidth: 200 });';
		$slidehtml .= 'var marker'.$map_num.' = new google.maps.Marker({position: myLatlng, map: map'.$map_num.', title: \''.esc_attr($business_name).'\'});';
		$slidehtml .= 'google.maps.event.addListener(marker'.$map_num.', "click", function() {infowindow'.$map_num.'.open(map'.$map_num.',marker'.$map_num.');});';
		$slidehtml .= '</script>';
		$slidehtml .= '</div>';
		$slidehtml .= '</div>';
		$slidehtml .= '</div>';
		$slidehtml .= '</div>';
	}
	return apply_filters('wms_page_slider_filter', $slidehtml);
}

// assembles the css needed for a slide/featured image
// allows for responsive images if set
function wms_page_slider_slide_css($img_id, $selector, $bg_pos='', $bg_height='', $bg_wash_opacity=''){
	global $rpm_theme_options;
	if(is_front_page()){
		$looking_for = 'featured-image-home';
	}else{
		$looking_for = 'featured-image';
	}

	if($bg_wash_opacity == ''){
		$gradient_opacity = '0.65';
	}elseif($bg_wash_opacity >= 0 && $bg_wash_opacity <= 1){
		$gradient_opacity = $bg_wash_opacity;
	}else{
		$gradient_opacity = '0.65';
	}
	$gradient_overlay = 'linear-gradient(to right, rgba(35,31,32,'.$gradient_opacity.'), rgba(71,108,141,'.$gradient_opacity.')), ';

	if($bg_pos == ''){
		$bg_pos_style = '';
	}else{
		$bg_pos_style = ' background-position:'.$bg_pos.';';
	}

	$media_lib_meta = wp_get_upload_dir();
	$slide_css = '';
	// used to check $rpm_theme_options['responsive-slideshow'] but now we'll always go responsive
	// set up multiple images in css for different screen sizes
	$image_meta = wp_get_attachment_metadata($img_id);
	$media_folder = substr($image_meta['file'], 0, strrpos($image_meta['file'], '/')); // eg 2016/07
	// xl
	if(isset($image_meta['sizes']['xl'])){
		$image_url = $media_lib_meta['baseurl'].'/'.$media_folder.'/'.$image_meta['sizes']['xl']['file'];
	}elseif(isset($image_meta['sizes'][$looking_for])){
		// fallback
		$image_url = $media_lib_meta['baseurl'].'/'.$media_folder.'/'.$image_meta['sizes'][$looking_for]['file'];
	}else{
		// falling further back ... the base image
		$image_url = $media_lib_meta['baseurl'].'/'.$image_meta['file'];
	}
	$slide_css .= $selector.' {background-image:'.$gradient_overlay.'url('.$image_url.');'.$bg_pos_style.'}';
	if($bg_height > 200){
		$slide_css .= $selector.' {min-height:'.$bg_height.'px;}';
	}
	// lg
	if(isset($image_meta['sizes']['lg']) || $bg_height > 200){
		$slide_css .= '@media only screen and (max-width:1366px){';
		if(isset($image_meta['sizes']['lg'])){
			$image_url = $media_lib_meta['baseurl'].'/'.$media_folder.'/'.$image_meta['sizes']['lg']['file'];
			$slide_css .= $selector.' {background-image:'.$gradient_overlay.'url('.$image_url.');'.$bg_pos_style.'}';
		}
		if($bg_height > 200){
			$slide_css .= $selector.' {min-height:'.($bg_height * 0.9).'px;}';
		}
		$slide_css .= '}';
	}
	// sm
	if(isset($image_meta['sizes']['sm']) || $bg_height > 200){
		$slide_css .= '@media only screen and (max-width:640px){';
		if(isset($image_meta['sizes']['sm'])){
			$image_url = $media_lib_meta['baseurl'].'/'.$media_folder.'/'.$image_meta['sizes']['sm']['file'];
			$slide_css .= $selector.' {background-image:'.$gradient_overlay.'url('.$image_url.');'.$bg_pos_style.'}';
		}
		if($bg_height > 200){
			$slide_css .= $selector.' {min-height:'.($bg_height * 0.8).'px;}';
		}
		$slide_css .= '}';
	}
	// xs
	if(isset($image_meta['sizes']['xs']) || $bg_height > 200){
		$slide_css .= '@media only screen and (max-width:320px){';
		if(isset($image_meta['sizes']['xs'])){
			$image_url = $media_lib_meta['baseurl'].'/'.$media_folder.'/'.$image_meta['sizes']['xs']['file'];
			$slide_css .= $selector.' {background-image:'.$gradient_overlay.'url('.$image_url.');'.$bg_pos_style.'}';
		}
		if($bg_height > 200){
			$slide_css .= $selector.' {min-height:'.($bg_height * 0.7).'px;}';
		}
		$slide_css .= '}';
	}
	return $slide_css;
}

// returns the buttons (if any) for this page to be displayed on the featured slide
// returns '' if no buttons
// can be up to 3 buttons on the home page and 0 or 1 on other pages
function wms_featured_slide_btns(){
	global $post;
	if(is_front_page()){
		$num_btns = 3;
	}else{
		$num_btns = 1;
	}
	$html = '';
	for ($i=0; $i < $num_btns; $i++) { 
		$btn_text = get_post_meta($post->ID, '_btn_text_'.$i, true);
		$btn_link = get_post_meta($post->ID, '_btn_link_'.$i, true);
		$btn_tgt = get_post_meta($post->ID, '_btn_tgt_'.$i, true);
		$btn_class = get_post_meta($post->ID, '_btn_class_'.$i, true);
		if($btn_text <> '' && $btn_link <> ''){
			if($btn_tgt == 'yes'){
				$target = ' target="_blank"';
			}else{
				$target = '';
			}
			if($btn_class == ''){
				$class = '';
			}else{
				$class = ' btn-'.$btn_class;
			}
			$html .= '<a class="btn btn-feat btn-'.$i.$class.'" href="'.$btn_link.'" role="button"'.$target.'>'.$btn_text.'</a>';
		}
	}
	if($html <> ''){
		$html = '<p class="featured-btns">'.$html.'</p>';
	}
	return $html;
}

// the metabox that controls the page content
function wms_page_content_control_callback($post){
	global $rpm_theme_options;
	wp_nonce_field( 'page_content_control_metabox', 'page_content_control_nonce' );
	$page_content_control = get_post_meta($post->ID, '_page_content_control', true);
	if(!is_array($page_content_control)){
		$page_content_control = array(
			'sections' => 1,
		);
	}
	echo '<p><label for="wms_page_content_control_sections">How many sections?';
	echo '<select class="widefat" id="wms_page_content_control_sections" name= "wms_page_content_control_sections">';
	for ($i=1; $i < 15; $i++) { 
		echo '<option value="'.$i.'" '.selected($i, $page_content_control['sections']).'>'.$i.'</option>';
	}
	echo '</select></p>';
	echo '<p><a id="page-content-export" class="button button-secondary" href="javascript:void(0);" data-pageid="'.$post->ID.'">Export page content</a> <span id="page-content-export-msg"></span></p>';
	echo '<p>Content import:<br>Selecting a file here (and then clicking update above) will replace ALL content on this page with the content from the file. The file must be a json file, exported from another page.<br><input type="file" name="content-import"></p>';
}

