<?php
/**
 * Site Search functions
 */

// block the normal WP search function from doing it's stuff
add_action( 'pre_get_posts', 'cc_search_pre_get_posts' );
function cc_search_pre_get_posts( $query ){
	if ( ! is_admin() && $query->is_main_query() ){
		if ( $query->is_search ){
			// we'll lose the search query and, instead, ask it to find the home page
			$query->set( 's', '' );
			$query->set( 'page_id', get_option( 'page_on_front' ) );
			$query->set( 'posts_per_page', 1 );
		}
	}
}

// get upcoming live training for the results page
add_action( 'wp_ajax_cc_search_ult', 'cc_search_cc_search_ult' );
add_action( 'wp_ajax_nopriv_cc_search_ult', 'cc_search_cc_search_ult' );
function cc_search_cc_search_ult(){
	$response = array(
		'html' => cc_search_trainings( 'ult', cc_search_get_search_term() )
	);
    echo json_encode($response);
    die();
}

// get on-demand training for the results page
add_action( 'wp_ajax_cc_search_odt', 'cc_search_cc_search_odt' );
add_action( 'wp_ajax_nopriv_cc_search_odt', 'cc_search_cc_search_odt' );
function cc_search_cc_search_odt(){
	$response = array(
		'html' => cc_search_trainings( 'odt', cc_search_get_search_term() )
	);
    echo json_encode($response);
    die();
}

function cc_search_get_search_term(){
	$search_term = '';
	if( isset( $_POST['s'] ) && $_POST['s'] <> '' ){
		$search_term = stripslashes( sanitize_text_field( $_POST['s'] ) );
	}
	return $search_term;
}

// the training stuff for the search results page
function cc_search_trainings( $training_type, $search_term ){
	$response = array(
		'html' => ''
	);
	if( $training_type == 'ult' ){
		$trainings = workshop_archive_get_posts( '', '', $search_term );
		$sect_title = 'Upcoming live training';
	}else{
		$trainings = recording_get_all_available( '', '', '', $search_term, false );
		$sect_title = 'On-demand training';
	}

	$html = '';

	if( count( $trainings ) > 0 ){

		$html .= '
		<div class="wms-section wms-section-std sml-padd-top no-padd-bot text-start">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h2>'.$sect_title.'</h2>';

		$html .= '<p class="search-count"><strong>'.count( $trainings ).' results found for "'.$search_term.'"</strong></p>';

		$doing_carousel = false;
		if( count( $trainings ) > 4 ){
			$doing_carousel = true;
		}

		$html .= '<div class="cc-train-panel cc-train-panel-'.$training_type.'">';
		$html .= '<div class="row mx-auto my-auto';
		if( $doing_carousel ){
			$html .= ' justify-content-center';
		}
		$html .= '">';

		if( $doing_carousel ){
			$html .= '<div id="cc-train-panel-carousel-'.$training_type.'" class="carousel carousel-dark slide">';
			$html .= '<div class="carousel-inner cc-sr-panel-carousel-inner cc-sr-'.$training_type.'-carousel" role="listbox">';
		}else{
			// $html .= '<p>&nbsp;</p>';
		}

		$item_class = 'active';
		$item_count = 0;
		$user_timezone = cc_timezone_get_user_timezone();
		foreach ($trainings as $training) {
			if( $doing_carousel ){
				$html .= '<div class="carousel-item train-carousel-item '.$item_class.'">';
			}
			$item_class = '';
			$html .= '<div class="col-12 col-md-6 col-xl-3 cc-train-panel-col d-flex align-items-stretch">';
			$html .= '<div class="card mb-2 flex-fill training-'.$training->ID.'">';

			// the image ...
			$html .= '<div class="wms-image-wrapper"><a href="'.get_permalink($training->ID).'">';
			// nested carousels are not supported (Bootstrap v5.3) hence we do not attempt to show multiple presenter images
			$item_count ++;
			$presenters_image_ids = cc_presenters_training_get_ids( $training->ID );
			$venue = get_post_meta( $training->ID, 'event_1_venue_name', true );
			if( $venue == '' ){
				$flag = '';
			}else{
				$flag = 'In person';
			}
			if( count( $presenters_image_ids ) <> 1 ){
				// use featured image
				// we're using presenter-profile instead of post-image to match the presenter images
				$feat_img_url = get_the_post_thumbnail_url( $training->ID, 'presenter-profile' );
				if($feat_img_url == ''){
					$feat_img_url = get_the_post_thumbnail_url( get_option('page_on_front'), 'presenter-profile' );
				}
				// lazy load images 5 onwards
				if( $item_count > 5 ){
					$html .= '<div data-src="'.$feat_img_url.'" class="card-img-top lazy cc-train-panel-img wms-bg-img image-zoom">';
				}else{
					$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img image-zoom" style="background-image:url('.$feat_img_url.');">';
				}
				// add a flag if needed
				if( $flag <> '' ){
					$html .= '<div class="train-card-flag">'.$flag.'</div>';
				}
				$html .= '</div>';
			}else{
				// use presenter's image
				if( $item_count > 5 ){
					$html .= cc_presenters_images( $training->ID, true, true, 'card-img-top image-zoom', $flag ); // first only
				}else{
					$html .= cc_presenters_images( $training->ID, false, true, 'card-img-top image-zoom', $flag ); // first only
				}
			}
			$html .= '</a></div>';

			$html .= '<div class="card-body grad-bg">';

			$html .= '<div class="row cc-train-panel-preheader"><div class="col-6">';
			if( $training->post_type == 'workshop' ){
				$html .= 'LIVE';
			}else{
				$html .= 'ON-DEMAND';
			}
			$html .= '</div><div class="col-6 text-end">';
			if( $training->post_type == 'workshop' ){
				$html .= get_post_meta( $training->ID, 'prettydates', true );
			}else{
				$html .= get_post_meta( $training->ID, 'duration', true );
			}
			$html .= '</div></div>';

			// hack to show training level
			$html .= cc_topics_show( $training->ID, 'xs', false, '', cc_training_search_empty_params(), true );

			$training_title = get_the_title( $training->ID );
			$title_font = cc_tnc_text_size( 262, 90, $training_title, 24);
			$html .= '<h5 class="card-title cc-train-panel-card-title" style="font-size:'.$title_font['font_size'].'px; line-height:'.$title_font['line_height'].';">'.cc_search_highlight( $training_title, $search_term ).'</h5>';

			$card_text = cc_presenters_names( $training->ID, 'none' );
			if( $card_text == '' ){
				$card_text = wms_get_excerpt_by_id( $training, '70', 'characters' );
				if( strlen( $card_text ) > 70 ){
					$card_text = substr( $card_text, 0, 70).' ...';
				}
			}
			$html .= '<p class="card-text">'.cc_search_highlight( $card_text, $search_term ).'</p>';

			if( $training->post_type == 'workshop' ){
				$pretty_dates = workshop_calculated_prettydates($training, $user_timezone);
				if($pretty_dates['locale_date'] <> ''){
					$html .= '<p class="card-text">'.$pretty_dates['locale_date'].'</p>';
				}
			}

			$html .= '<p class="highlighted-excerpt">'.cc_search_highlight( cc_search_get_excerpt( $training, $search_term ), $search_term ).'</p>';

			$html .= '<div class="text-end"><a href="'.get_permalink($training).'" class="btn btn-primary btn-sm">Read More</a></div>';
			
			$html .= '</div><!-- .card-body -->';
			$html .= '</div><!-- .card -->';
			$html .= '</div><!-- .col-md-6.col-xl-3 -->';
			if( $doing_carousel ){
				$html .= '</div><!-- .carousel-item -->';
			}
		}
		
		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-inner -->';
			// controls
			$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-train-panel-carousel-'.$training_type.'" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-train-panel-carousel-'.$training_type.'" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
			$html .= '</div><!-- .carousel -->';
		}
		$html .= '</div><!-- .row -->';

		$html .= '</div><!-- .cc-train-panel -->';

		$html .= '		</div><!-- .col outer -->
					</div><!-- .row outer -->
				</div><!-- .container -->
			</div><!-- .wms-sect-bg -->
		</div>';

	}
	return $html;
}


// highlight the search term if found in the text
function cc_search_highlight( $text, $search_term ){
    // Escape special characters in the search term for use in a regular expression
    $escapedSearchTerm = preg_quote($search_term, '/');
    
    // Use a callback function to replace each match with the highlighted version
    $highlightedText = preg_replace_callback(
        "/($escapedSearchTerm)/i", // The 'i' flag makes the search case-insensitive
        function ($matches) {
            // Wrap the matched term in a <span> with class "highlight"
            return '<span class="highlight">' . $matches[0] . '</span>';
        },
        $text
    );
    
    return $highlightedText;
}


function cc_search_get_excerpt($post_id, $search_term) {
    // Get the post content
    $post_content = get_post_field('post_content', $post_id);

    // Remove shortcodes and HTML tags
    $clean_content = wp_strip_all_tags(strip_shortcodes($post_content));

    // Split the content into words
    $words = preg_split('/\s+/', $clean_content);

    // Prepare the regular expression to find the search term
    $pattern = '/\b' . preg_quote($search_term, '/') . '\b/i';

    // Initialize an array to hold the excerpts
    $excerpts = [];

    // Loop through the words and find the search term
    $found_count = 0;
    for ($i = 0; $i < count($words); $i++) {
        if (preg_match($pattern, $words[$i])) {
            $found_count++;
            if ($found_count <= 3) {
                // Get up to 5 words before and after the search term
                $start = max(0, $i - 5);
                $end = min(count($words) - 1, $i + 5);
                
                // Extract the excerpt
                $excerpt = array_slice($words, $start, ($end - $start + 1));
                
                // Add ellipses if there's more content before or after
                if ($start > 0) {
                    array_unshift($excerpt, '...');
                }
                if ($end < count($words) - 1) {
                    array_push($excerpt, '...');
                }

                // Add the excerpt to the list
                $excerpts[] = implode(' ', $excerpt);
            }
        }
    }

    // If no excerpts were found, return the first 10 words
    if (empty($excerpts)) {
        $first_words = array_slice($words, 0, 10);
        return implode(' ', $first_words) . (count($words) > 10 ? '...' : '');
    }

    // Return the first three excerpts (or fewer if less found) joined by "<br>"
    return implode('<br>', $excerpts);
}

// knowledge hub search
add_action( 'wp_ajax_cc_search_khub', 'cc_search_cc_search_khub' );
add_action( 'wp_ajax_nopriv_cc_search_khub', 'cc_search_cc_search_khub' );
function cc_search_cc_search_khub(){
	$response = array(
		'html' => cc_search_hubs( 'khub', cc_search_get_search_term() )
	);
    echo json_encode($response);
    die();	
}

// resource hub search
add_action( 'wp_ajax_cc_search_rhub', 'cc_search_cc_search_rhub' );
add_action( 'wp_ajax_nopriv_cc_search_rhub', 'cc_search_cc_search_rhub' );
function cc_search_cc_search_rhub(){
	$response = array(
		'html' => cc_search_hubs( 'rhub', cc_search_get_search_term() )
	);
    echo json_encode($response);
    die();	
}

// get the knowledge/resource hub search results
function cc_search_hubs( $hub_type, $search_term ){
	if( $hub_type == 'khub' ){
		$post_type = 'knowledge_hub';
		$sect_title = 'Knowledge hub';
	}else{
		$post_type = 'resource_hub';
		$sect_title = 'Resource hub';
	}
	$args = array(
		'post_type' => $post_type,
		'numberposts' => -1,
		// 'fields' => 'ids',
		// 's' => $search_term,
	);
	$posts = cc_search_match( get_posts( $args ), $search_term );

	$html = '';

	if( ! empty( $posts )){

		$html .= '
		<div class="wms-section wms-section-std sml-padd-top no-padd-bot text-start">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h2>'.$sect_title.'</h2>';

		$html .= '<p class="search-count"><strong>'.count( $posts ).' results found for "'.$search_term.'"</strong></p>';

		$doing_carousel = false;
		if( count( $posts ) > 4 ){
			$doing_carousel = true;
		}

		$html .= '<div class="cc-hub-panel-'.$hub_type.'">';
		$html .= '<div class="cc-train-panel">';
		$html .= '<div class="row mx-auto my-auto';
		if( $doing_carousel ){
			$html .= ' justify-content-center';
		}
		$html .= '">';

		if( $doing_carousel ){
			$html .= '<div id="cc-train-panel-carousel-'.$hub_type.'" class="carousel carousel-dark slide">';
			$html .= '<div class="carousel-inner cc-sr-panel-carousel-inner cc-sr-'.$hub_type.'-carousel" role="listbox">';
		}else{
			// $html .= '<p>&nbsp;</p>';
		}

		$item_class = 'active';
		$item_count = 0;
		foreach ($posts as $post) {
			$item_count ++;
			// $post = get_post($cpt_id);
			if( $doing_carousel ){
				$html .= '<div class="carousel-item train-carousel-item '.$item_class.'">';
				$html .= '<div class="col-12 col-md-6 col-xl-3 cc-train-panel-col d-flex align-items-stretch">';
			}else{
				$html .= '<div class="col-md-6 col-xl-3 cc-train-panel-colxx d-flex align-items-stretch">';
			}
			$html .= '<div class="card mb-3 flex-fill bg-white">';
			$feat_img_url = get_the_post_thumbnail_url( $post->ID, 'post-thumb' );
			if($feat_img_url == ''){
				$feat_img_url = get_the_post_thumbnail_url( get_option('page_on_front'), 'post-thumb' );
			}
			if( $item_count > 5 ){
				$html .= '<a href="'.get_permalink($post->ID).'" class="image-zoom"><img data-src="'.$feat_img_url.'" class="card-img-top lazy" alt="'.$post->post_title.' featured image"></a>';
			}else{
				$html .= '<a href="'.get_permalink($post->ID).'" class="image-zoom"><img src="'.$feat_img_url.'" class="card-img-top" alt="'.$post->post_title.' featured image"></a>';
			}
			$html .= '<div class="card-body">';
			$html .= '<h5 class="post-title card-title">'.cc_search_highlight( $post->post_title, $search_term ).'</h5>';

			$html .= '<p class="card-text highlighted-excerpt">'.cc_search_highlight( cc_search_get_excerpt( $post->ID, $search_term ), $search_term ).'</p>';

			$html .= '<a href="'.get_permalink($post->ID).'" class="btn btn-primary btn-sm">Read more</a>';
			$html .= '</div><!-- .card-body -->';
			$html .= '</div><!-- .card -->';
			$html .= '</div><!-- .col -->';
			if( $doing_carousel ){
				$html .= '</div><!-- .carousel-item -->';
			}
			$item_class = '';
		}

		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-inner -->';
			// controls
			$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-train-panel-carousel-'.$hub_type.'" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-train-panel-carousel-'.$hub_type.'" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
			$html .= '</div><!-- .carousel -->';
		}

		$html .= '</div><!-- .row -->';
		$html .= '</div><!-- .cc-train-panel -->';
		$html .= '</div><!-- .cc-hub-panel -->';

		$html .= '		</div><!-- .col outer -->
					</div><!-- .row outer -->
				</div><!-- .container -->
			</div><!-- .wms-sect-bg -->
		</div>';

	}

	return $html;
}

// blog posts
// get blog posts for the results page
add_action( 'wp_ajax_cc_search_blog', 'cc_search_cc_search_blog' );
add_action( 'wp_ajax_nopriv_cc_search_blog', 'cc_search_cc_search_blog' );
function cc_search_cc_search_blog(){
	global $rpm_theme_options;
	$response = array(
		'html' => ''
	);
	$search_term = cc_search_get_search_term();
	$args = array(
		'post_type' => 'post',
		'numberposts' => -1,
		// 'fields' => 'ids',
		// 's' => $search_term,
	);
	$posts = cc_search_match( get_posts( $args ), $search_term );

	$html = '';

	if( ! empty( $posts )){

		$html .= '
		<div class="wms-section wms-section-std sml-padd-top no-padd-bot text-start">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h2>Blog: Latest insights into ACT</h2>';

		$html .= '<p class="search-count"><strong>'.count( $posts ).' results found for "'.$search_term.'"</strong></p>';

		$doing_carousel = false;
		if( count( $posts ) > 4 ){
			$doing_carousel = true;
		}

		$html .= '<div class="cc-search-panel-blog cc-train-panel">';
		$html .= '<div class="row mx-auto my-auto';
		if( $doing_carousel ){
			$html .= ' justify-content-center';
		}
		$html .= '">';

		if( $doing_carousel ){
			$html .= '<div id="cc-search-blog-carousel" class="carousel carousel-dark slide">';
			$html .= '<div class="carousel-inner cc-sr-panel-carousel-inner cc-sr-blog-carousel" role="listbox">';
		}else{
			// $html .= '<p>&nbsp;</p>';
		}

		$item_class = 'active';
		$item_count = 0;
		foreach ($posts as $post) {
			$item_count ++;
			// $post = get_post($post->ID);
			if( $doing_carousel ){
				$html .= '<div class="carousel-item train-carousel-item '.$item_class.'">';
				$html .= '<div class="col-12 col-md-6 col-xl-3 cc-train-panel-col d-flex align-items-stretch">';
			}else{
				$html .= '<div class="col-md-6 col-xl-3 cc-train-panel-colxx d-flex align-items-stretch">';
			}
			$html .= '<div class="card mb-3 flex-fill bg-white">';

			if(isset($rpm_theme_options['blog-fallback-img']['url']) && $rpm_theme_options['blog-fallback-img']['url'] <> ''){
				$default_image = $rpm_theme_options['blog-fallback-img']['url'];
			}else{
				$default_image = false;
			}
			$args = array(
				'post_id' => $post->ID,
				'scan' => true,
				'size' => 'post-thumb',
				'image_class' => 'card-img-top',
				'default' => $default_image,
				'format' => 'array',
				'echo' => false,
			);
			$permalink = esc_url ( get_permalink( $post->ID ) );
			$html .= '<a href="'.$permalink.'" class="image-zoom"><div class="latest-post-img wms-bg-img"';
			if ( function_exists( 'get_the_image' ) ) {
				$image_arr = get_the_image( $args );
				if( isset( $image_arr['src'] ) && $image_arr['src'] <> '' ) {
					$html .= ' style="background-image:url('.$image_arr['src'].');"';
				}
			}
			$html .= '></div></a>';

			$html .= '<div class="card-body d-flex flex-column">';

			$html .= '<h5 class="card-title">'.cc_search_highlight( get_the_title( $post->ID ), $search_term ).'</h5>';

			$html .= '<div class="row card-cite mb-2"><div class="col-5 col-md-12 col-xxl-5">';
			$html .= contextual_posted_on( $post->ID, true );
			$html .= '</div><div class="col-7 col-md-12 col-xxl-7 text-end text-md-start text-xxl-end">';
			$html .= contextual_post_cats( 'cats', false, $post->ID );
			$html .= '</div></div>';
										
			$html .= '<p class="highlighted-excerpt">'.cc_search_highlight( cc_search_get_excerpt( $post->ID, $search_term ), $search_term ).'</p>';

			$html .= '<p class="card-btn-wrap text-right mt-auto"><a href="'.$permalink.'" class="btn btn-primary btn-sm">Read more</a></p>';

			$html .= '</div><!-- .card-body -->';
			$html .= '</div><!-- .card -->';
			$html .= '</div><!-- .col -->';

			if( $doing_carousel ){
				$html .= '</div><!-- .carousel-item -->';
			}
			$item_class = '';
		}

		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-inner -->';
			// controls
			$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-search-blog-carousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-search-blog-carousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
			$html .= '</div><!-- .carousel -->';
		}

		$html .= '</div><!-- .row -->';
		$html .= '</div><!-- .cc-search-blog-panel -->';

		$html .= '		</div><!-- .col outer -->
					</div><!-- .row outer -->
				</div><!-- .container -->
			</div><!-- .wms-sect-bg -->
		</div>';

	}

	$response['html'] = $html;

    echo json_encode($response);
    die();
}

// looking for trainers
add_action( 'wp_ajax_cc_search_trn', 'cc_search_cc_search_trn' );
add_action( 'wp_ajax_nopriv_cc_search_trn', 'cc_search_cc_search_trn' );
function cc_search_cc_search_trn(){
	$response = array(
		'html' => ''
	);
	$search_term = cc_search_get_search_term();

	$args = array(
		'post_type' => 'presenter',
		'numberposts' => -1,
		// 'fields' => 'ids',
		// 's' => $search_term,
	);
	$presenters = cc_search_match( get_posts( $args ), $search_term );

	$html = '';

	if( ! empty( $presenters )){

		$html .= '
		<div class="wms-section wms-section-std sml-padd-top no-padd-bot text-start">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h2>Trainers</h2>';

		$html .= '<p class="search-count"><strong>'.count( $presenters ).' results found for "'.$search_term.'"</strong></p>';

		$doing_carousel = false;
		if( count( $presenters ) > 4 ){
			$doing_carousel = true;
		}

		$html .= '<div class="cc-search-panel-trn cc-train-panel">';
		$html .= '<div class="row mx-auto my-auto';
		if( $doing_carousel ){
			$html .= ' justify-content-center';
		}
		$html .= '">';

		if( $doing_carousel ){
			$html .= '<div id="cc-search-trn-carousel" class="carousel carousel-dark slide">';
			$html .= '<div class="carousel-inner cc-sr-panel-carousel-inner cc-sr-trn-carousel" role="listbox">';
		}else{
			// $html .= '<p>&nbsp;</p>';
		}

		$item_class = 'active';
		$item_count = 0;
		foreach ($presenters as $presenter) {
			$item_count ++;
			if( $doing_carousel ){
				$html .= '<div class="carousel-item train-carousel-item '.$item_class.'">';
				$html .= '<div class="col-12 col-md-6 col-xl-3 cc-train-panel-col d-flex align-items-stretch">';
			}else{
				$html .= '<div class="col-md-6 col-xl-3 cc-train-panel-colxx d-flex align-items-stretch">';
			}
			$html .= '<div class="card mb-3 flex-fill bg-white">';

			$html .= '<a href="'.get_permalink( $presenter->ID ).'">';
        	$image_id = cc_presenters_image_id( $presenter->ID, '1' );
        	if( $image_id ){
        		$image_url = esc_url( wms_section_image_url( $image_id, 'presenter-profile' ) );
        	}else{
        		$image_url = '';
        	}
			if( $item_count > 5 ){
				$html .= '<div data-src="'.$image_url.'" class="card-img-top lazy cc-train-panel-img wms-bg-img image-zoom">';
			}else{
				$html .= '<div class="card-img-top cc-train-panel-img wms-bg-img image-zoom" style="background-image:url('.$image_url.');">';
			}
			$html .= '</div>';
			$html .= '</a>';

            $html .= '<div class="card-body">';

            $html .= '<h5 class="card-title">'.cc_search_highlight( get_the_title( $presenter->ID ), $search_term ).'</h5>';

            $html .= '<p class="card-text presenter-card-text highlighted-excerpt">'.cc_search_highlight( cc_search_get_excerpt( $presenter->ID, $search_term ), $search_term ).'</p>';

            $html .= '<a href="'.get_permalink( $presenter->ID ).'" class="btn btn-primary btn-sm">Read more</a>';

			$html .= '</div><!-- .card-body -->';
			$html .= '</div><!-- .card -->';
			$html .= '</div><!-- .col -->';

			if( $doing_carousel ){
				$html .= '</div><!-- .carousel-item -->';
			}
			$item_class = '';
		}

		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-inner -->';
			// controls
			$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-search-trn-carousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-search-trn-carousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
			$html .= '</div><!-- .carousel -->';
		}

		$html .= '</div><!-- .row -->';
		$html .= '</div><!-- .cc-search-trn-panel -->';

		$html .= '		</div><!-- .col outer -->
					</div><!-- .row outer -->
				</div><!-- .container -->
			</div><!-- .wms-sect-bg -->
		</div>';

	}

	$response['html'] = $html;

    echo json_encode($response);
    die();
}

// looking for other pages
add_action( 'wp_ajax_cc_search_pag', 'cc_search_cc_search_pages' );
add_action( 'wp_ajax_nopriv_cc_search_pag', 'cc_search_cc_search_pages' );
function cc_search_cc_search_pages(){
	$response = array(
		'html' => ''
	);
	$search_term = cc_search_get_search_term();

	$args = array(
		'post_type' => 'page',
		'numberposts' => -1,
		'meta_key' => 'search_switch',
		'meta_value' => 'yes',
		// 'fields' => 'ids',
		// 's' => $search_term,
	);
	$pages = cc_search_match( get_posts( $args ), $search_term );

	$html = '';

	if( ! empty( $pages )){

		$html .= '
		<div class="wms-section wms-section-std sml-padd-top no-padd-bot text-start">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h2>Other pages</h2>';

		$html .= '<p class="search-count"><strong>'.count( $pages ).' results found for "'.$search_term.'"</strong></p>';

		$html .= '<div class="cc-search-panel-pag cc-train-panel">';

		foreach ($pages as $page) {
			$html .= '<div class="card mb-3 flex-fill">';
            $html .= '<div class="card-body">';

			$html .= '<div class="row mx-auto my-auto align-items-end">';
			$html .= '<div class="col-md-9 col-lg-10">';

            $html .= '<h4 class="card-title">'.cc_search_highlight( get_the_title( $page->ID ), $search_term ).'</h4>';

            $html .= '<p class="card-text presenter-card-text highlighted-excerpt mb-3">'.cc_search_highlight( cc_search_get_excerpt( $page->ID, $search_term ), $search_term ).'</p>';

			$html .= '</div><div class="col-md-3 col-lg-2 text-end">';

            $html .= '<a href="'.get_permalink( $page->ID ).'" class="btn btn-primary btn-sm">Read more</a>';

            $html .= '</div><!-- .col -->';
            $html .= '</div><!-- .row -->';
            $html .= '</div><!-- .card-body -->';
            $html .= '</div><!-- .card -->';
		}

		$html .= '</div><!-- .cc-search-pag-panel -->';

		$html .= '		</div><!-- .col outer -->
					</div><!-- .row outer -->
				</div><!-- .container -->
			</div><!-- .wms-sect-bg -->
		</div>';

	}

	$response['html'] = $html;

    echo json_encode($response);
    die();
}

// look for a search term match on a bunch of posts
// return those that match
// now re-orders them too ... title matches first, exact matches second, fuzzy matches third
function cc_search_match( $posts, $search_term ){
	if( $search_term == '' ){
		return $posts;
	}
	$search_term = strtolower( $search_term );
	$first_pri = $second_pri = $third_pri = array();
	foreach ($posts as $post) {
		// collect the text
		$title_text = strtolower( wp_strip_all_tags( strip_shortcodes( $post->post_title ) ) );
		$excert_text = strtolower( wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ) ) );
		$content_text = strtolower( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
		// check for string matches (accommodates multiple words)
		if( strpos( $title_text, $search_term ) !== false ){
			$first_pri[] = $post;
		}elseif( strpos( $excert_text, $search_term ) !== false || strpos( $content_text, $search_term ) !== false ){
			$second_pri[] = $post;
		}else{
		    // Check if any word matches or is "close" to the search term
		    // we'll check for exact matches first and only do the fuzzy check if that fails to try to speed things up
		    /* not needed as the above will catch exact matches
		    $matches = false;
		    foreach ( [$title_words, $excerpt_words, $content_words] as $words ){
		    	foreach ( $words as $word ){
		    		if( $word == $search_term ){
		    			$matched_posts[] = $post;
		    			// No need to check further if a match is found
		    			$matches = true;
		    			break 2;
		    		}
		    	}
		    }
		    if( ! $matches && ! cc_search_term_is_acronym( $search_term ) ){
		    */
		    if( ! cc_search_term_is_acronym( $search_term ) ){
				// Break down the post fields into words
			    $title_words = explode(' ', $title_text );
			    $excerpt_words = explode(' ', $excert_text );
			    $content_words = explode(' ', $content_text );
			    foreach ( [$title_words, $excerpt_words, $content_words] as $words ){
			    	foreach ( $words as $word ){
			    		// now check the levenshtein distance
			    		$distance = levenshtein( $search_term, $word );
			    		// If the distance is within the acceptable range, consider it a match
			            if ($distance <= 2){
			    			$third_pri[] = $post;
			    			break 2;
			            }
			    	}
			    }
			}
		}
	}
	return array_merge( $first_pri, $second_pri, $third_pri );
}

// is the search term in the acronym list?
function cc_search_term_is_acronym( $search_term ){
	global $rpm_theme_options;
	$acronyms = array_map( 'trim', preg_split( '/\r\n|[\r\n]/', strtolower( $rpm_theme_options['acronyms'] ) ) );
	return in_array( $search_term, $acronyms );
}

// Add a search switch to the page attributes meta box
add_action( 'page_attributes_misc_attributes', 'cc_search_page_attributes' );
function cc_search_page_attributes( $post ) {
    wp_nonce_field( 'cc_search_page_attributes_nonce', 'cc_search_page_attributes_nonce' );
    ?>
    <p class="post-attributes-label-wrapper search-label-wrapper">
        <label class="search-label" for="search_switch" style="font-weight: 600;">Include in site search?</label>
		<select class="widefat" id="search_switch" name="search_switch">
			<option value="">No</option>
			<option value="yes" <?php selected( 'yes', get_post_meta( $post->ID, 'search_switch', true ) ); ?>>Yes</option>
		</select>
    </p>
    <?php
}
add_action( 'save_post', 'cc_search_metabox_save_post' );
function cc_search_metabox_save_post( $post_id ) {
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if( ! isset( $_POST['cc_search_page_attributes_nonce'] ) || ! wp_verify_nonce( $_POST['cc_search_page_attributes_nonce'], 'cc_search_page_attributes_nonce' ) ) return;
    if( ! current_user_can( 'edit_post', $post_id ) ) return;
    $search_switch = '';
    if( isset( $_POST['search_switch'] ) && ( $_POST['search_switch'] == '' || $_POST['search_switch'] == 'yes' ) ){
	    update_post_meta( $post_id, 'search_switch', $_POST['search_switch'] );
    }
}
