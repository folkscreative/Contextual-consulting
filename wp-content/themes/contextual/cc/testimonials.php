<?php
/**
 * Testimonials
 * - inspired by the old testimonial rotator plugin
 */

// Register Custom Post Type
function cc_testimonials_cpt_setup() {
	$labels = array(
		'name'                  => 'Testimonials',
		'singular_name'         => 'Testimonial',
		'menu_name'             => 'Testimonials',
		'name_admin_bar'        => 'Testimonial',
		'archives'              => 'Testimonial Archives',
		'attributes'            => 'Testimonial Attributes',
		'parent_item_colon'     => 'Parent Testimonial:',
		'all_items'             => 'All Testimonials',
		'add_new_item'          => 'Add New Testimonial',
		'add_new'               => 'Add New',
		'new_item'              => 'New Testimonial',
		'edit_item'             => 'Edit Testimonial',
		'update_item'           => 'Update Testimonial',
		'view_item'             => 'View Testimonial',
		'view_items'            => 'View Testimonials',
		'search_items'          => 'Search Testimonial',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into Testimonial',
		'uploaded_to_this_item' => 'Uploaded to this Testimonial',
		'items_list'            => 'Testimonials list',
		'items_list_navigation' => 'Testimonials list navigation',
		'filter_items_list'     => 'Filter Testimonials list',
	);
	$args = array(
		'label'                 => 'Testimonial',
		'description'           => 'Testimonial',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'custom-fields' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-format-quote',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
        'register_meta_box_cb'  => 'cc_testimonials_meta_boxes', // Callback function for custom metaboxes
	);
	register_post_type( 'testimonial', $args );
}
add_action( 'init', 'cc_testimonials_cpt_setup', 0 );

// custom meta boxes
function cc_testimonials_meta_boxes() {
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
    add_meta_box( 'cc_testimonials', 'Testimonials', 'cc_testimonials_form', 'testimonial', 'normal', 'default' );
}
function cc_testimonials_form() {
    $post_id = get_the_ID();
    $score = get_post_meta( $post_id, '_score', true );
    $source = get_post_meta( $post_id, '_source', true );
    wp_nonce_field( 'cc_testimonials', 'cc_testimonials_nonce' );
    ?>
    <p>
        <label>Score</label><br />
        <select name="testimonial[score]">
            <option value="">Please select ...</option>
        	<option value="1" <?php if($score == "1") echo ' selected="selected"'; ?>>1-star</option>
            <option value="2" <?php if($score == "2") echo ' selected="selected"'; ?>>2-star</option>
            <option value="3" <?php if($score == "3") echo ' selected="selected"'; ?>>3-star</option>
            <option value="4" <?php if($score == "4") echo ' selected="selected"'; ?>>4-star</option>
            <option value="5" <?php if($score == "5") echo ' selected="selected"'; ?>>5-star</option>
        </select>
    </p>
    <p>
        <label>Source</label><br>
        <select name="testimonial[source]">
            <option value="">Unset</option>
            <option value="google" <?php selected('google', $source); ?>>Google</option>
            <option value="facebook" <?php selected('facebook', $source); ?>>Facebook</option>
        </select>
    </p>
    <?php
}

// save
add_action( 'save_post', 'cc_testimonials_save_post' );
function cc_testimonials_save_post( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! empty( $_POST['testimonial'] ) && ! wp_verify_nonce( $_POST['cc_testimonials_nonce'], 'cc_testimonials' ) ) return;
	if ( ! empty( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) )
			return;
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
    }
    if ( ! empty( $_POST['testimonial'] ) ) {
		$score = ( empty( $_POST['testimonial']['score'] ) ) ? '' : stripslashes( sanitize_text_field( $_POST['testimonial']['score'] ) );
    	update_post_meta( $post_id, '_score', $score );
		$source = ( empty( $_POST['testimonial']['source'] ) ) ? '' : stripslashes( sanitize_text_field( $_POST['testimonial']['source'] ) );
    	update_post_meta( $post_id, '_source', $source );
    }
}

// get three testimonials
// Jun 2023 - now getting all of them for a carousel instead of just 3!
add_shortcode('show_testimonials', 'cc_testimonials_get_three');
function cc_testimonials_get_three(){
	$args = array(
		'post_type' => 'testimonial',
		'posts_per_page' => -1,
		'orderby' => 'rand',
	);
	$testimonials = get_posts($args);
    if(count($testimonials) > 3){
        // carousel
        $html = '
            <div class="row eq-height-backgrounds mx-auto my-auto justify-content-center">
                <div id="feat-testimonials-carousel" class="carousel slide feat-testimonials-carousel" data-bs-ride="carousel" data-type="multi">
                    <div class="carousel-inner">';
        $item_class = 'active';
        foreach ($testimonials as $testimonial) {
            $html .= '
                        <div class="carousel-item '.$item_class.'">
                            <div class="col-md-4">';
            $item_class = '';

            $html .= '<div class="testimonial inner">';
		    $score = get_post_meta( $testimonial->ID, '_score', true );
		    $source = get_post_meta( $testimonial->ID, '_source', true );
	        $reviewer = get_the_title($testimonial->ID);
	        $full_text = get_the_content(null, false, $testimonial->ID);
	        if(strlen($full_text) > 200){
	        	$full_text = substr($full_text, 0, 195).'...';
	        }
            $html .= '<div class="testimonial-item" itemprop="review" itemscope itemtype="http://schema.org/Review">';
	        $html .= '<div class="testimonial-source-icon-wrap">';
	        $html .= cc_testimonials_source_icon($source);
	        $html .= '</div><!-- .testimonial-source-icon-wrap -->';
	        $html .= '<div class="row">';
	        $html .= '<div class="col-4 avatar-col">';
	        $html .= '<img src="'.cc_testimonials_get_avatar($reviewer).'" alt="'.$reviewer.' avatar" class="testimonial-avatar">';
	        $html .= '</div><!-- .avatar-col -->';
	        $html .= '<div class="col-8 non-avatar-col">';
	        $html .= '<h6 class="testimonial-author" itemprop="author"><strong>'.$reviewer.'</strong></h6>';
	        $html .= '<p class="testimonial-score" itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating"><meta itemprop="ratingValue" content="'.$score.'">';
	        $html .= cc_testimonials_stars_fa($score);
	        $html .= '</p>';
	        $html .= '</div><!-- .non-avatar-col -->';
	        $html .= '</div><!-- .row -->';
	        $html .= '<p class="testimonial-body" itemprop="reviewBody">'.$full_text.'</p>';
	        $html .= '</div><!-- .testimonial-item -->';
	        $html .= '</div><!-- .testimonial -->';

            $html .= '
                            </div><!-- .col-md-4 -->
                        </div><!-- .carousel-item -->';
        }

        $html .= '
                    </div><!-- .carousel-inner -->

                    <!-- Controls -->
					<button class="carousel-control-prev" type="button" data-bs-target="#feat-testimonials-carousel" data-bs-slide="prev">
						<span class="carousel-control-prev-icon" aria-hidden="true"></span>
						<span class="visually-hidden">Previous</span>
					</button>
					<button class="carousel-control-next" type="button" data-bs-target="#feat-testimonials-carousel" data-bs-slide="next">
						<span class="carousel-control-next-icon" aria-hidden="true"></span>
						<span class="visually-hidden">Next</span>
					</button>

                </div><!-- .carousel -->
            </div><!-- .row -->';
    }else{
	    $html = '<div class="testimonials eq-height-backgrounds row text-start">';
	    foreach ($testimonials as $testimonial) {
		    $score = get_post_meta( $testimonial->ID, '_score', true );
		    $source = get_post_meta( $testimonial->ID, '_source', true );
	        $reviewer = get_the_title($testimonial->ID);
	        $full_text = get_the_content(null, false, $testimonial->ID);
	        if(strlen($full_text) > 200){
	        	$full_text = substr($full_text, 0, 195).'...';
	        }
	        $html .= '<div class="col-md-4">';
	        $html .= '<div class="testimonial">';
	        $html .= '<div class="testimonial-item inner" itemprop="review" itemscope itemtype="http://schema.org/Review">';
	        $html .= '<div class="testimonial-source-icon-wrap">';
	        $html .= cc_testimonials_source_icon($source);
	        $html .= '</div>';
	        $html .= '<div class="row">';
	        $html .= '<div class="col-4 avatar-col">';
	        $html .= '<img src="'.cc_testimonials_get_avatar($reviewer).'" alt="'.$reviewer.' avatar" class="testimonial-avatar">';
	        $html .= '</div>';
	        $html .= '<div class="col-8 non-avatar-col">';
	        $html .= '<h6 class="testimonial-author" itemprop="author"><strong>'.$reviewer.'</strong></h6>';
	        $html .= '<p class="testimonial-score" itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating"><meta itemprop="ratingValue" content="'.$score.'">';
	        $html .= cc_testimonials_stars_fa($score);
	        $html .= '</p>';
	        $html .= '</div>';
	        $html .= '</div>';
	        $html .= '<p class="testimonial-body" itemprop="reviewBody">'.$full_text.'</p>';
	        $html .= '</div>';
	        $html .= '</div>';
	        $html .= '</div>';
	    }
	    $html .= '</div>';
	}
    return $html;
}

function cc_testimonials_source_icon($source){
	switch ($source) {
		case 'google':
			$html = '<img class="testimonial-source-icon google" src="'.get_stylesheet_directory_uri().'/images/Google_G_Logo.svg" alt="Google G icon">';
			break;
		case 'facebook':
			$html = '<img class="testimonial-source-icon facebook" src="'.get_stylesheet_directory_uri().'/images/2021_Facebook_icon.svg" alt="Facebook icon">';
			break;
		default:
			$html = '';
			break;
	}
	return $html;
}

function cc_testimonials_stars_fa($score){
	$html = '';
    switch ($score) {
        case '1':   $html = '<i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i>';     break;
        case '2':   $html = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i>';     break;
        case '3':   $html = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i>';     break;
        case '4':   $html = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i>';     break;
        case '4.5': $html = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>';     break;
        case '5':   $html = '<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>';     break;
    }
    return $html;
}

// get an avatar from https://ui-avatars.com/
function cc_testimonials_get_avatar($name){
    return esc_url( add_query_arg( array(
        'name' => urlencode( $name ),
        'background' => 'random',
        'rounded' => 'true',
    ), 'https://ui-avatars.com/api/' ) );
}
