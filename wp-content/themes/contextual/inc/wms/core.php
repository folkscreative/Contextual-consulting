<?php
/**
 * WMS
 * Core functionality - general and common functions
 *
 * Includes:
 * - wms_scripts
 * - wms_theme
 * - wms_validate_html_color
 * - wms_hex2rgba
 * - wms_get_the_signature
 * - wms_setup_image_sizes
 * - wms_options_css
 * - wms_shortcode_empty_paragraph_fix
 * - wms_parse_data_attributes
 * - wms_blank
 * - wms_email
 */

if(!defined('ABSPATH')) exit;

// add in the WMS Scripts
add_action( 'wp_enqueue_scripts', 'wms_scripts' );
function wms_scripts() {
    global $rpm_theme_options;
	$theme_ver = wms_theme('Version');
    // wp_enqueue_script( 'hover-intent', get_template_directory_uri().'/inc/wms/js/jquery.hoverIntent.min.js', array('jquery'), '1.9.0', true);
    // wp_enqueue_script( 'jquery-actual', get_template_directory_uri().'/inc/wms/js/jquery.actual.min.js', array('jquery'), '1.0.19', true);
	// wp_register_script( 'wms-script', get_template_directory_uri() . '/inc/wms/js/wms.js', array('jquery', 'hover-intent', 'jquery-actual'), $theme_ver, true);
	wp_register_script( 'wms-script', get_template_directory_uri() . '/inc/wms/js/wms.js', array('jquery'), $theme_ver, true);
	wp_localize_script( 'wms-script', 'wmsAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
    wp_enqueue_script( 'wms-script' );
}

// add in the admin styles and scripts
add_action('admin_head', 'wms_core_admin_enqueue');
function wms_core_admin_enqueue(){
	$theme_ver = wms_theme('Version');
    wp_enqueue_style( 'wp-color-picker');
	wp_enqueue_script( 'wp-color-picker');
	wp_enqueue_style('wms-admin-styles', get_template_directory_uri().'/inc/wms/css/admin.css', array(), $theme_ver);
	wp_register_script( 'wms-admin-script', get_template_directory_uri() . '/inc/wms/js/admin.js', array('jquery', 'wp-color-picker'), $theme_ver, true);
	wp_localize_script( 'wms-admin-script', 'wmsAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
    wp_enqueue_script( 'wms-admin-script' );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css', array(), '6.1.1' );
}

// admin favicon
add_action('login_head', 'add_admin_favicon');
add_action('admin_head', 'add_admin_favicon');
function add_admin_favicon() {
	if(file_exists(get_stylesheet_directory().'/images/admin-favicon.ico')){
	  	$favicon_url = get_stylesheet_directory_uri() . '/images/admin-favicon.ico';
		echo '<link rel="shortcut icon" href="' . $favicon_url . '" />';
	}
}

// returns info about the theme
// property can be Name, ThemeURI, Description, Author, AuthorURI, Version, Template, Status, Tags, TextDomain, DomainPath
function wms_theme($property){
	$theme = wp_get_theme();
	if($theme->get($property) !== null ){
		return $theme->get($property);
	}else{
		return false;
	}
}

/* Validates hex color, adding #-sign if not found. Checks for a Color Name first to prevent error if a name was entered (optional).
*   $color: the color hex value stirng to Validates
*   $named: (optional), set to 1 or TRUE to first test if a Named color was passed instead of a Hex value
*	returns '' if supplied with an invalid colour
*/
function wms_validate_html_color($color, $named = false) {
	if ($named) {
	    $named = array('aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure', 'beige', 'bisque', 'black', 'blanchedalmond', 'blue', 'blueviolet', 'brown', 'burlywood', 'cadetblue', 'chartreuse', 'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan', 'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen', 'darkkhaki', 'darkmagenta', 'darkolivegreen', 'darkorange', 'darkorchid', 'darkred', 'darksalmon', 'darkseagreen', 'darkslateblue', 'darkslategray', 'darkturquoise', 'darkviolet', 'deeppink', 'deepskyblue', 'dimgray', 'dodgerblue', 'firebrick', 'floralwhite', 'forestgreen', 'fuchsia', 'gainsboro', 'ghostwhite', 'gold', 'goldenrod', 'gray', 'green', 'greenyellow', 'honeydew', 'hotpink', 'indianred', 'indigo', 'ivory', 'khaki', 'lavender', 'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue', 'lightcoral', 'lightcyan', 'lightgoldenrodyellow', 'lightgreen', 'lightgrey', 'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue', 'lightslategray', 'lightsteelblue', 'lightyellow', 'lime', 'limegreen', 'linen', 'magenta', 'maroon', 'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple', 'mediumseagreen', 'mediumslateblue', 'mediumspringgreen', 'mediumturquoise', 'mediumvioletred', 'midnightblue', 'mintcream', 'mistyrose', 'moccasin', 'navajowhite', 'navy', 'oldlace', 'olive', 'olivedrab', 'orange', 'orangered', 'orchid', 'palegoldenrod', 'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff', 'peru', 'pink', 'plum', 'powderblue', 'purple', 'red', 'rosybrown', 'royalblue', 'saddlebrown', 'salmon', 'sandybrown', 'seagreen', 'seashell', 'sienna', 'silver', 'skyblue', 'slateblue', 'slategray', 'snow', 'springgreen', 'steelblue', 'tan', 'teal', 'thistle', 'tomato', 'turquoise', 'violet', 'wheat', 'white', 'whitesmoke', 'yellow', 'yellowgreen');
	    if (in_array(strtolower($color), $named)) {
			/* A color name was entered instead of a Hex Value, so just exit function */
			return $color;
	    }
	}
	if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
		// Verified OK
	}else if (preg_match('/^[a-f0-9]{6}$/i', $color)) {
		$color = '#' . $color;
	}else{
		$color = '';
	}
	return $color;
}

// convert hex colour to rgb or rgba
// from http://mekshq.com/how-to-convert-hexadecimal-color-code-to-rgb-or-rgba-using-php/
function wms_hex2rgba($color, $opacity = false) {
	$default = 'rgb(0,0,0)';
	//Return default if no color provided
	if(empty($color)) return $default; 
	//Sanitize $color if "#" is provided 
    if ($color[0] == '#' ) {
    	$color = substr( $color, 1 );
    }
    //Check if color has 6 or 3 characters and get values
    if (strlen($color) == 6) {
        $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
    } elseif ( strlen( $color ) == 3 ) {
        $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
    } else {
        return $default;
    }
    //Convert hexadec to rgb
    $rgb =  array_map('hexdec', $hex);
    //Check if opacity is set(rgba or rgb)
    if($opacity){
    	if(abs($opacity) > 1)
    		$opacity = 1.0;
    	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
    } else {
    	$output = 'rgb('.implode(",",$rgb).')';
    }
    //Return rgb(a) color string
    return $output;
}


// returns the pages signature where relevant
function wms_get_the_signature(){
	// could add if(is_single()){...} etc to do different things if needed
	/*
	global $rpm_theme_options;
	global $post;
	if(is_object($post) && ($post->post_type == 'post' || $post->post_type == 'page')){
		$page_signature = $rpm_theme_options['page-signature'];
		if($page_signature <> ''){
			$page_signature_wpautop = $rpm_theme_options['page-signature-wpautop'];
			$page_signature_show = get_post_meta($post->ID, '_signature', true);
			if($page_signature_show <> 'hide'){
				$page_signature = do_shortcode($page_signature);
				if($page_signature_wpautop == '1'){
					$page_signature = wpautop($page_signature);
				}
				$page_signature = '<div class="page-signature">'.$page_signature.'</div>';
				return $page_signature;
			}
		}
	}
	*/
	return '';
}

// set the WMS image sizes
add_action('after_setup_theme', 'wms_setup_image_sizes');
function wms_setup_image_sizes(){
	global $rpm_theme_options;
	// set the featured image size - used for the slideshow and on pages/posts
	$image_width = 1920;
	add_image_size('featured-image-home', $image_width, 1200, true );
	if(isset($rpm_theme_options['slideshow-ht-lg']['height']) && $rpm_theme_options['slideshow-ht-lg']['height'] <> '' && is_numeric($rpm_theme_options['slideshow-ht-lg']['height'])){
		if(isset($rpm_theme_options['feat-slide-width']) && $rpm_theme_options['feat-slide-width'] == 'container'){
			$image_width = 1170;
		}else{
			$image_width = 1920;
		}
		add_image_size('featured-image', $image_width, $rpm_theme_options['slideshow-ht-lg']['height'], true );
	}
	// set the post thumbnail image size used on archive pages
	add_image_size('post-thumb', 720, 480, true);
	// image sizes for the slideshow images. Sizes are based on Bootstrap's container widths (for no particularly good reason) plus a couple of other sizes (cos I can)
	// however, if the image is a letterbox image then i need to consider the slideshow height too as, otherwise, the smaller images could be too short ... previously this was 9999
	/* revised Jan 2023 to latest BS breakpoints and all now 3:2 ratio
	add_image_size('xl', 1920, 1200, true);
	add_image_size('lg', 1366, 854, true);
	add_image_size('sm', 640, 400, true);
	add_image_size('xs', 320, 200, true);
	*/
	add_image_size('xxl', 1920, 1280, true);
	add_image_size('xl', 1400, 933, true);
	add_image_size('lg', 1200, 800, true);
	add_image_size('md', 992, 661, true);
	add_image_size('sm', 768, 512, true);
	add_image_size('xs', 576, 384, true);
	// used for logos:
	add_image_size('xxs', 240, 150, true);
	add_image_size('xxxs', 160, 100, true);
}

// used to add in the dynamic css
function wms_options_css(){
	global $rpm_theme_options;
	$CSS = '';
	// $CSS .= options_typography_styles();
	if(file_exists(get_stylesheet_directory().'/style.php')){
		include(get_stylesheet_directory().'/style.php');
		if(function_exists('wms_dynamic_css')){
			$CSS .= wms_dynamic_css();
		}
	}
	if($rpm_theme_options['custom-css'] <> ''){
		$CSS .= "\n/* *** Custom css: *** */\n";
		$CSS .= $rpm_theme_options['custom-css'];
	}
	return $CSS;
}

// remove the empty paragraphs that WP may add
function wms_shortcode_empty_paragraph_fix( $content ) {
	// echo ' wms_shortcode_empty_paragraph_fix ';
    // define your shortcodes to filter, '' filters all shortcodes
    $shortcodes = array('');
    foreach ( $shortcodes as $shortcode ) {
        $array = array (
            '<p>[' . $shortcode => '[' .$shortcode,
            '<p>[/' . $shortcode => '[/' .$shortcode,
            $shortcode . ']</p>' => $shortcode . ']',
            $shortcode . ']<br />' => $shortcode . ']',
            $shortcode . ']<br>' => $shortcode . ']',
        );
        $content = strtr( $content, $array );
    }
    return $content;
}
add_filter( 'the_content', 'wms_shortcode_empty_paragraph_fix' );

// allows data attributes (eg data="att1,sausage|att2,bacon")
// from Bootstrap 3 shortcodes
function wms_parse_data_attributes( $data ) {
	$data_props = '';
	if( $data ) {
		$data = explode( '|', $data );
		foreach( $data as $d ) {
			$d = explode( ',', $d );
			$data_props .= sprintf( 'data-%s="%s" ', esc_html( $d[0] ), esc_attr( trim( $d[1] ) ) );
		}
	}else {
		$data_props = false;
	}
	return $data_props;
}

// redirect all attachment image pages to the page itself (or the home page)
add_action( 'template_redirect', 'wms_redirect_attachment_page' );
function wms_redirect_attachment_page() {
	if ( is_attachment() ) {
		global $post;
		if ( $post && $post->post_parent ) {
			wp_redirect( esc_url( get_permalink( $post->post_parent ) ), 301 );
			exit;
		} else {
			wp_redirect( esc_url( home_url( '/' ) ), 301 );
			exit;
		}
	}
}

// as it says, function to get the excerpt by ID - for use outside of the loop
// creates one from content if a "real" excerpt is not found
// $limit_type = "words" or "characters"
// $extra only added if it needs shortening
function wms_get_excerpt_by_id($post_id, $excerpt_length='55', $limit_type='words', $extra = ' ...'){
	$the_post = get_post($post_id);
	if( $the_post === NULL ) return '';
	$the_excerpt = $the_post->post_excerpt;
	if ($the_excerpt == '') {
		$the_excerpt = $the_post->post_content;
	}else{
		$limit_type = 'unlimited';
	}
	$the_excerpt = strip_shortcodes($the_excerpt);
	$the_excerpt = apply_filters('the_excerpt', $the_excerpt);
    $the_excerpt = str_replace(']]>', ']]&gt;', $the_excerpt);
    $the_excerpt = strip_tags($the_excerpt);
	if ($limit_type == 'words') {
		$excerptwords = explode(' ', $the_excerpt, $excerpt_length + 1);
		if(count($excerptwords) > $excerpt_length){
			array_pop($excerptwords);
			$the_excerpt = implode(' ', $excerptwords).$extra;
		}
	}elseif($limit_type == 'characters'){
		if(strlen($the_excerpt) > $excerpt_length){
			$the_excerpt = substr($the_excerpt, 0, $excerpt_length).$extra;
		}
	}
	return $the_excerpt;
}

// lightens or darkens colours
// Steps should be between -255 and 255. Negative = darker, positive = lighter
function wms_adjust_brightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    // Format the hex color string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }
    // Get decimal values
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    // Adjust number of steps and keep it inside 0 to 255
    $r = max(0,min(255,$r + $steps));
    $g = max(0,min(255,$g + $steps));  
    $b = max(0,min(255,$b + $steps));
    $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
    $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
    $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    return '#'.$r_hex.$g_hex.$b_hex;
}

// prevent the Site Admin Email Verification Screen nag from showing
add_filter( 'admin_email_check_interval', '__return_false' );

// log it!
function wms_write_log ( $log )  {
  if ( is_array( $log ) || is_object( $log ) ) {
     error_log( print_r( $log, true ) );
  } else {
     error_log( $log );
  }
}

// removes tab and newline/cr characters from html
// useful for ajax responses
function wms_tidy_html($html){
	if( $html == '' ) return '';
	$new_lines = "\n";
	$tabs = "\t";
	$carriage_returns = "\r";
	$find = array($new_lines, $tabs, $carriage_returns);
	$replacement = '';
	return str_replace($find, $replacement, $html);
}

// find a page from its template
// $return can be 'url' or 'id'
// returns a url, id or false on failure
function wms_find_page( $template_name, $return='url' ){
    $pages = get_pages( array(
        'meta_key' => '_wp_page_template',
        'meta_value' => $template_name
    ));
    if( isset( $pages[0] ) ) {
    	$page_id = $pages[0]->ID;
    	if( $return == 'url' ){
    		return get_page_link( $page_id );
    	}
    	return $page_id;
    }else{
    	return false;
    }
    return $url;
}

// add the post_id to the front of the results on a link search (in WP Editor)
add_filter( 'wp_link_query', 'wms_cc_wp_link_query', 10, 2 );
function wms_cc_wp_link_query( $results, $query ){
	$new_results = [];
	foreach ( $results as $result ) {
		$new_results[] = array(
			'ID' => $result['ID'],
			'title' => $result['ID'].': '.$result['title'],
			'permalink' => $result['permalink'],
			'info' => $result['info'],
		);
	}
	return $new_results;
}