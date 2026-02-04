<?php
/**
 * WMS
 * Sections - The heart of the content structure
 *  - sections add in containers, rows and columns and, often, the content too
 *
 * Includes:
 * - 
 */

/**
 * Jan 2022
 * - added row
 * - changed [column] to [col] and added [column] from Bootstrap 3 shortcodes
 * Feb 2021
 * - added logos section
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_shortcode('section', 'wms_section');
add_shortcode('col', 'wms_section_column');
add_shortcode('grid', 'wms_grid');
add_shortcode('row', 'wms_row');
add_shortcode('column', 'wms_column');


/**
 * note, might be good to add an editor button for these shortcodes .... maybe https://madebydenis.com/adding-shortcode-button-to-tinymce-editor/
 */
// the section shortcode should exist on every page and will be automagically added if not there
// it adds Bootstrap container, row and column divs as necessary around the relevant content
// usage: [section type="left" image="http://...jpg" youtube="tMyFrdf06sk" video_quality="720p" bg_colour="#ff0000" text_colour="#00ff00" xclass="class-one class-two" focal_point="center"] ... [/section]
// type can be:
// "" for a standard text section - the default
// "narrow" = narrow container
// "wide" = full width with large centrered text
// "wide_left" = full width, image on the left
// "right" = narrow, image on the right
// "left" = narrow, image on the left
// "grid" = full width incorporating 7 grid shortcodes ... the first one must be 'tall_text' followed by 6 'hover' grids ... see the grid shortcode
// "posts" = shows the latest three posts
// "menu_bar" = shows a menu across the width of the screen
// "3-cols" = splits the row into three equal columns - must include 3 'column' shortcodes
// "2-cols" = splits the row into two equal columns - must include 2 'column' shortcodes
// "3-cols-std" = splits the row into three equal columns - must include 3 'column' shortcodes - standard container width
// "2-cols-std" = splits the row into two equal columns - must include 2 'column' shortcodes - standard container width
// "4-cols"
// "thumb-left" = thumbnail image on the left, great for a list of people or maybe products in a shop
// "logos" = wraps logos in rows and columns - for best effect, make all the logos the same size!
// 			[logos ids="1, 2, 3" rows_xs="1" rows_sm="1" rows_md="1" rows_lg="1" rows_xl="1" logo_size="full" xclass=""]
//
// params ....
// image, if used, must be an image (eg http://...jpg)
// youtube, if used, must be the video id only such as tMyFrdf06sk
// video_quality, if used, must be hd720 720p hd1080 or 1080i it defaults to 720p quality
// bg_colour, if used, must be something like #000000;
// text_colour, if used, must be something like #ffffff;
// text_size, if used, must be small, normal or large
// text_align, if used must be left, right, center or centre, justify or nowrap
// num_items, only applies to the menu_bar
// title, only used on the menu_bar ... and the 3-cols/2-cols
// heading, h1, h2 etc for the title
// width, can be wide (or fluid), 12 (default), 11, ... 1
// image_size, used for thumb-left - if set to "small", sets smaller col for image
// focal_point, defaults to 'center'. Anything else results in a background-position property being set
// xclass can be anything you like
//
// for CC ...
// subhead
$section_type = ''; // used for other shortcodes used within the section shortcodes
function wms_section($atts, $content){
	global $rpm_theme_options;
	global $menu_bar_num_items;
	global $menu_bar_this_item;
	global $section_type;
	$a = shortcode_atts( array(
	    'type' => '',
	    'image' => '',
	    'parallax' => '',
	    'bg_colour' => '',
	    'opacity' => 0.8,
	    'text_colour' => '',
	    'text_size' => '',
	    'text_align' => WMS_SECTION_ALIGN,
	    'youtube' => '',
	    'video_quality' => '720p',
	    'num_items' => '6',
	    'title' => '',
	    'width' => '',
	    'heading' => 'h2',
	    'image_size' => '',
	    'xclass' => '',
	    'ids' => '',
	    'rows_xs' => '1',
	    'rows_sm' => '1',
	    'rows_md' => '1',
	    'rows_lg' => '1',
	    'rows_xl' => '1',
	    'logo_size' => 'sm',
	    'focal_point' => 'center',
	    'image_xs' => '',
	    'subhead' => '',
    ), $atts );

    $type = "{$a['type']}";
    $section_type = $type;
    $image = "{$a['image']}";
    $image_xs = "{$a['image_xs']}";
    $parallax = "{$a['parallax']}";
    if($parallax == 'yes' || $parallax == 'true'){
    	$parallax = 'yes';
    }else{
    	$parallax = 'no';
    }
    $image_size = "{$a['image_size']}";
    $bg_colour = "{$a['bg_colour']}";
    $text_colour = "{$a['text_colour']}";
    $text_size = "{$a['text_size']}";
    $poss_text_sizes = array('', 'small', 'normal', 'large');
    if(!in_array($text_size, $poss_text_sizes)) $text_size = '';
    $text_align = "{$a['text_align']}";
    $poss_text_aligns = array('', 'left', 'right', 'center', 'centre', 'justify', 'nowrap', 'start', 'end');
    if(!in_array($text_align, $poss_text_aligns)) $text_align = '';
    if($text_align == 'centre') $text_align = "center";
    if($text_align == 'left') $text_align = "start";
    if($text_align == 'right') $text_align = "end";
    $youtube = "{$a['youtube']}";
    $video_quality = "{$a['video_quality']}";
    $num_items = "{$a['num_items']}";
    $title = "{$a['title']}";
    $width = "{$a['width']}";
    $heading = "{$a['heading']}";
    $subhead = "{$a['subhead']}";
    $xclass = "{$a['xclass']}";
    $ids = "{$a['ids']}";
    $rows_xs = "{$a['rows_xs']}";
    $rows_sm = "{$a['rows_sm']}";
    $rows_md = "{$a['rows_md']}";
    $rows_lg = "{$a['rows_lg']}";
    $rows_xl = "{$a['rows_xl']}";
    $logo_size = "{$a['logo_size']}";
    $focal_point = "{$a['focal_point']}";
    $bg_colour = wms_validate_html_color($bg_colour);
    $opacity = (float) "{$a['opacity']}";
    if($opacity > 1 || $opacity < 0) $opacity = 0.8;
    if($image <> '' && $bg_colour <> '' && $opacity <> 1){
    	$bg_colour_rgba = wms_hex2rgba($bg_colour, $opacity);
    }else{
    	$bg_colour_rgba = wms_hex2rgba($bg_colour, 1);
    }
    $text_colour = wms_validate_html_color($text_colour);

    $id = 'wms-sect-'.mt_rand();

	if($text_align <> ''){
		$xclass .= ' text-'.$text_align;
	}

	$text_class = ''; // found this was not set but is used in the code bel;ow so set it empty to stop the debug errors

    switch ($type) {
    	case '':
    		// standard 
	    	// background img/colour applies to whole section
			$cols = 12;
			$offset = 0;
			$section_width = 'container';
			$outer_col = 'col-12';
			if($width <> ''){
				if($width == 'wide' || $width == 'fluid'){
					$section_width = 'container-fluid';
				}else{
					$section_width = 'container';
				}
				$cols = (int) $width;
				if($cols > 0 && $cols < 12){
					$offset = floor((12 - $cols) / 2);
					$outer_col = 'col-12 col-md-'.$cols;
					if($offset > 0){
						$outer_col .= ' offset-md-'.$offset;
					}
				}
			}
			$bg_classes = '';
			if($image <> ''){
				$bg_classes .= 'wms-bg-img';
				if($parallax == 'yes'){
					$bg_classes .= ' parallax';
				}
			}
			$html = '<div id="'.$id.'" class="wms-section wms-section-std '.$xclass.'">';
			$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
			$html .= '<div class="'.$section_width.'">';
			$html .= '<div class="row">';
			$html .= '<div class="'.$outer_col.'">';
			if($title <> ''){
				$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
			}
			$html .= do_shortcode($content);
			$html .= '</div><!-- .col outer -->'; // outer col
			$html .= '</div><!-- .row outer -->'; // outer row
			$html .= '</div><!-- .container -->'; // container
			$html .= '</div><!-- .wms-sect-bg -->'; // container
			$html .= '</div><!-- .wms-section -->'; // section
			$html .= wms_section_css($id, $bg_colour, '', $text_colour, $bg_colour_rgba, $image, $image_size, $parallax, $focal_point, $image_xs, 'sect');
    		break;

    	case 'narrow':
    		// narrow container
			if($text_size == ''){
				$col_class = 'normal';
			}else{
				$col_class = $text_size;
			}
			if($text_align == ''){
				$col_class .= ' text-start';
			}else{
				$col_class .= ' text-'.$text_align;
			}
    		$html = '<div id="'.$id.'" class="wms-section wms-section-narrow '.$xclass.'"><div class="container container-narrow"><div class="row"><div class="col-12 wms-section-content '.$col_class.'">';
			// $html .= apply_filters('the_content', $content);
			$html .= do_shortcode($content);
			$html .= '<div class="clear"></div>';
    		$html .= '</div></div></div></div>';
			if($bg_colour <> '' || $text_colour <> ''){
				$html .= '<style>';
				if($bg_colour <> ''){
					$html .= '#'.$id.' {background:'.$bg_colour.';}';
				}
				if($text_colour <> ''){
					$html .= '#'.$id.' .wms-section-content, #'.$id.' .wms-section-content h1, #'.$id.' .wms-section-content h2, #'.$id.' .wms-section-content h3, #'.$id.' .wms-section-content h4, #'.$id.' .wms-section-content h5, #'.$id.' .wms-section-content h6 {color:'.$text_colour.';}';
				}
				$html .= '</style>';
			}
    		break;

		case 'wide_left':
			// full width, image on the left
			$html = '<div id="'.$id.'" class="container-fluid wms-section wms-section-wide-left wms-sect-eq-ht '.$xclass.'"><div class="row"><div class="col-12 col-sm-6 wms-bg-img wms-sect-inner';
			if($youtube <> '') $html .= ' no-padd';
			$html .= '"';
			if($image <> ''){
				if($image_size == '') $image_size = 'post-thumb';
				$html .= ' style="background-image:url('.wms_section_image_url($image, $image_size).');"';
			}
			$html .= '>';
			if($youtube <> ''){
				$html .= do_shortcode('[hd_video youtube="'.$youtube.'" quality="'.$video_quality.'"]');
			}
			$html .= '</div><div class="col-12 col-sm-6 wms-section-content wms-sect-inner">';
			$html .= do_shortcode($content);
			$html .= '<div class="clear"></div>';
			$html .= '</div></div></div>';
			$html .= wms_section_css($id, '', $bg_colour, $text_colour);
			break;

		case 'wide':
			// full width, centred, defaults to large text, centered
			if($text_size == ''){
				$col_class = 'large';
			}else{
				$col_class = $text_size;
			}
			if($text_align == ''){
				$col_class .= ' text-center';
			}else{
				$col_class .= ' text-'.$text_align;
			}
			$html = '<div id="'.$id.'" class="container-fluid wms-section wms-section-wide '.$xclass.'"><div class="row"><div class="col-12 wms-section-content '.$col_class.'"';
			if($image <> ''){
				$html .= ' style="background-image: url('.esc_url($image).');"';
			}
			$html .= '>';
			// $html .= apply_filters('the_content', $content);
			$html .= do_shortcode($content);
			$html .= '<div class="clear"></div>';
			$html .= '</div></div></div>';
			if($bg_colour <> '' || $text_colour <> ''){
				$html .= '<style>';
				if($bg_colour <> ''){
					$html .= '#'.$id.' {background:'.$bg_colour.';}';
				}
				if($text_colour <> ''){
					$html .= '#'.$id.' .wms-section-content, #'.$id.' .wms-section-content h1, #'.$id.' .wms-section-content h2, #'.$id.' .wms-section-content h3, #'.$id.' .wms-section-content h4, #'.$id.' .wms-section-content h5, #'.$id.' .wms-section-content h6 {color:'.$text_colour.';}';
				}
				$html .= '</style>';
			}
			break;

		case 'right':
			// image on the right (image above on mobile)
			// bg applies to the section, image applies to the column
			$cols = 12;
			$offset = 0;
			$section_width = 'container';
			$outer_col = 'col-12';
			if($width <> ''){
				if($width == 'wide' || $width == 'fluid'){
					$section_width = 'container-fluid';
				}else{
					$section_width = 'container';
				}
				$cols = (int) $width;
				if($cols > 0 && $cols < 12){
					$offset = floor((12 - $cols) / 2);
					$outer_col = 'col-12 col-md-'.$cols;
					if($offset > 0){
						$outer_col .= ' offset-md-'.$offset;
					}
				}
			}
			$bg_classes = '';
			if($image <> '' && $parallax == 'yes'){
				$bg_classes = 'parallax';
			}
			$html = '<div id="'.$id.'" class="wms-section wms-section-right '.$xclass.'">';
			$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
			$html .= '<div class="'.$section_width.'">';
			$html .= '<div class="row">';
			$html .= '<div class="'.$outer_col.'">';
			$html .= '<div class="sect-wrap px-xxl-5">';
			$html .= '<div class="row">';
			// image col first on mobile, last on md+
			$html .= '<div class="col-12 col-md-6 offset-xxl-1 order-md-2 wms-sect-inner wms-bg-img mb-5 mb-md-0 wms-square"></div>';
			$html .= '<div class="col-12 col-md-6 col-xxl-4 offset-xxl-1 order-md-1 wms-section-content wms-sect-inner '.$text_class.'">';
			$html .= '<div class="subhead">'.$subhead.'</div>';
			$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
			$html .= do_shortcode($content);
			$html .= '</div><!-- .col inner -->'; // inner col
			$html .= '</div><!-- .row inner -->'; // row around columns
			$html .= '</div><!-- .sect-wrap -->'; // section wrap
			$html .= '</div><!-- .col outer -->'; // outer col
			$html .= '</div><!-- .row outer -->'; // outer row
			$html .= '</div><!-- .container -->'; // container
			$html .= '</div><!-- .wms-sect-bg -->'; // section bg
			$html .= '</div><!-- .wms-section -->'; // section
			$html .= wms_section_css($id, $bg_colour, '', $text_colour, $bg_colour_rgba, $image, 'post-thumb', $parallax, $focal_point, $image_xs, 'col');
			break;

		case 'left':
			// image on the left
			// bg applies to the section, image applies to the column
			$cols = 12;
			$offset = 0;
			$section_width = 'container';
			$outer_col = 'col-12';
			if($width <> ''){
				if($width == 'wide' || $width == 'fluid'){
					$section_width = 'container-fluid';
				}else{
					$section_width = 'container';
				}
				$cols = (int) $width;
				if($cols > 0 && $cols < 12){
					$offset = floor((12 - $cols) / 2);
					$outer_col = 'col-12 col-md-'.$cols;
					if($offset > 0){
						$outer_col .= ' offset-md-'.$offset;
					}
				}
			}
			$bg_classes = '';
			if($image <> '' && $parallax == 'yes'){
				$bg_classes = 'parallax';
			}
			$html = '<div id="'.$id.'" class="wms-section wms-section-right '.$xclass.'">';
			$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
			$html .= '<div class="'.$section_width.'">';
			$html .= '<div class="row">';
			$html .= '<div class="'.$outer_col.'">';
			$html .= '<div class="sect-wrap px-xxl-5">';
			$html .= '<div class="row">';
			$html .= '<div class="col-12 col-md-6 wms-sect-inner wms-bg-img mb-5 mb-md-0 wms-square"></div>';
			$html .= '<div class="col-12 col-md-6 col-xxl-4 offset-xxl-1 wms-section-content wms-sect-inner '.$text_class.'">';
			$html .= '<div class="subhead">'.$subhead.'</div>';
			$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
			$html .= do_shortcode($content);
			$html .= '</div><!-- .col inner -->'; // inner col
			$html .= '</div><!-- .row inner -->'; // row around columns
			$html .= '</div><!-- .sect-wrap -->'; // section wrap
			$html .= '</div><!-- .col outer -->'; // outer col
			$html .= '</div><!-- .row outer -->'; // outer row
			$html .= '</div><!-- .container -->'; // container
			$html .= '</div><!-- .wms-sect-bg -->'; // section bg
			$html .= '</div><!-- .wms-section -->'; // section
			$html .= wms_section_css($id, $bg_colour, '', $text_colour, $bg_colour_rgba, $image, 'post-thumb', $parallax, $focal_point, $image_xs, 'col');
			break;
    	
    	case 'grid':
    		// 7 section grid - must include 7 grid shortcode elements ... see the grid shortcode
    		$html = '<div id="'.$id.'" class="container-fluid wms-section wms-section-grid '.$xclass.'"><div class="row">';
    		// $html .= apply_filters('the_content', $content);
			$html .= do_shortcode($content);
    		$html .= '</div></div>';
    		$html .= '<style>';
    		if($bg_colour <> ''){
    			$html .= '#'.$id.' > .row {background:'.$bg_colour.';}';
    			$bg_colour_rgba = wms_hex2rgba($bg_colour, 0.7);
    			$html .= '#'.$id.' .wms-grid-hover-show {background:'.$bg_colour_rgba.';}';
    		}
    		if($text_colour <> ''){
    			$html .= '#'.$id.' .wms-section-content, #'.$id.' .wms-section-content h1, #'.$id.' .wms-section-content h2, #'.$id.' .wms-section-content h3, #'.$id.' .wms-section-content h4, #'.$id.' .wms-section-content h5, #'.$id.' .wms-section-content h6, #'.$id.' .wms-grid-bg-heading, #'.$id.' .wms-grid-hover-show {color:'.$text_colour.';}';
    		}
    		$html .= '</style>';
    		break;

    	case 'posts':
    		// shows the blog posts, preceded by any content (eg a title)
    		// these parameters could maybe be added later ... (see Kensington)
    		$cat_list = '';
    		$showdate = 'yes';
    		// $readmore = 'Read more <i class="fa fa-angle-right" aria-hidden="true"></i>';
    		$readmore = 'Read more';
			if($text_size == ''){
				$col_class = 'normal';
			}else{
				$col_class = $text_size;
			}
			if($text_align == ''){
				$col_class .= ' text-start';
			}else{
				$col_class .= ' text-'.$text_align;
			}
    		$html = '<div id="'.$id.'" class="container wms-section wms-section-posts '.$xclass.' '.$col_class.'"><div class="row"><div class="col-12">';
			if($title <> ''){
				$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
				$html .= '<p class="section-title-blank">&nbsp;</p>';
			}
			$html .= do_shortcode($content);
    		$html .= '</div></div><div class="row">';
			$args = array(
				'post_status' => 'publish',
				'numberposts' => 3,
				'category' => $cat_list
			);
			$recent_posts = wp_get_recent_posts($args);
			if(count($recent_posts) > 0){
				$post_num = 1;
				foreach( $recent_posts as $recent ){
					$html .= '<div class="col-12 col-md-4 latest-post-col latest-post-'.$post_num.' ps-2 pe-2 '.$xclass.'">';
					if(isset($rpm_theme_options['blog-fallback-img']['url']) && $rpm_theme_options['blog-fallback-img']['url'] <> ''){
						$default_image = $rpm_theme_options['blog-fallback-img']['url'];
					}else{
						$default_image = false;
					}
					$args = array(
						'post_id' => $recent["ID"],
						'scan' => true,
						'size' => 'post-thumb',
						'image_class' => 'img-responsive',
						'echo' => false,
						'default' => $default_image,
						'format' => 'array'
					);
					$html .= '<div class="latest-post-img wms-bg-img"';
					if ( function_exists( 'get_the_image' ) ) {
						$image_arr = get_the_image($args);
						if(isset($image_arr['src']) && $image_arr['src'] <> '') {
							$html .= ' style="background-image:url('.$image_arr['src'].');"';
						}
					}
					$html .= '></div><div class="latest-post-text">';
					if(strlen(esc_attr($recent["post_title"])) > 70){
						$short_title = substr(esc_attr($recent["post_title"]),0,67).' ...';
					}else{
						$short_title = esc_attr($recent["post_title"]);
					}
					// $html .= '<h3 class="latest-post-title"><a href="'.get_permalink($recent["ID"]).'" title="'.esc_attr($recent["post_title"]).'">'.$short_title.'</a></h3>';
					$html .= '<h3 class="latest-post-title">'.$short_title.'</h3>';
					if($showdate == 'yes'){
						$html .= '<p class="small text-muted latest-post-date">'.date('jS M Y', strtotime($recent['post_date'])).'</p>';
					}
					$html .= '<p class="latest-post-excerpt">';
					$html .= wms_get_excerpt_by_id($recent["ID"]);
					$html .= '</p>';
					if($readmore <> ''){
						$html .= '<p class="latest-post-btn"><a href="'.get_permalink($recent["ID"]).'" class="btn btn-default btn" role="button">'.$readmore.'</a></p>';
					}
					$html .= '</div></div>';
					$post_num ++;
				}
			}
			$html .= '</div></div>';
			break;

		case 'menu_bar':
			// shows a menu type grid across the screen
			$menu_bar_num_items = $num_items;
			$menu_bar_this_item = 0;
			$html = '<div id="'.$id.'" class="container-fluid wms-section wms-section-menu-bar '.$xclass.'"><div class="row"><div class="col-12 wms-section-content text-center">';
			if($title <> '') $html .= '<'.$heading.'>'.$title.'</'.$heading.'>';
			$html .= '<div class="row">';
			$html .= do_shortcode($content); // hopefully contains some [menu_item]
			$html .= '</div>';
			$html .= '<div class="clear"></div>';
			$html .= '</div></div></div>';
			if($bg_colour <> '' || $text_colour <> ''){
				$html .= '<style>';
				if($bg_colour <> ''){
					$html .= '#'.$id.' {background:'.$bg_colour.';}';
				}
				if($text_colour <> ''){
					$html .= '#'.$id.' .wms-section-content, #'.$id.' .wms-section-content h1, #'.$id.' .wms-section-content h2, #'.$id.' .wms-section-content h3, #'.$id.' .wms-section-content h4, #'.$id.' .wms-section-content h5, #'.$id.' .wms-section-content h6 {color:'.$text_colour.';}';
				}
				$html .= '</style>';
			}
			break;

		case '3-cols':
		case '2-cols':
		case '3-cols-std':
		case '2-cols-std':
		case '4-cols':
			// splits the row into two, three or four columns - must include col shortcodes
			// NOTE: this is not the same as '2-columns' or '3-columns'!!!! ... keep scrolling for them!
			if($type == '3-cols' || $type == '2-cols' || $type == '4-cols'){
				$section_width = 'container-fluid';
			}else{
				$section_width = 'container';
			}
			if($text_align == ''){
				$align_class = ' text-start';
			}else{
				$align_class = ' text-'.$text_align;
			}
			$cols = 12;
			$offset = 0;
			$outer_col = 'col-12';
			if($width <> ''){
				if($width == 'wide' || $width == 'fluid'){
					$section_width = 'container-fluid';
				}else{
					$section_width = 'container';
				}
				$cols = (int) $width;
				if($cols > 0 && $cols < 12){
					$offset = floor((12 - $cols) / 2);
					$outer_col = 'col-12 col-md-'.$cols;
					if($offset > 0){
						$outer_col .= ' offset-md-'.$offset;
					}
				}
			}
			$html = '<div id="'.$id.'" class="wms-section wms-section-'.$type.' '.$xclass.'">';
			$html .= '<div class="'.$section_width.' '.$align_class.'">';
			$html .= '<div class="row">';
			$html .= '<div class="'.$outer_col.'">';
			if($title <> ''){
				$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
			}
			$html .= '<div class="row">';
			$html .= do_shortcode($content); // hopefully contains some [col]s
			$html .= '</div><!-- .row inner -->'; // row around columns
			$html .= '</div><!-- .col outer -->'; // outer col
			$html .= '</div><!-- .row outer -->'; // outer row
			$html .= '</div><!-- .container -->'; // container
			$html .= '</div><!-- .wms-section -->'; // section
			if($bg_colour <> '' || $text_colour <> ''){
				$html .= '<style>';
				if($bg_colour <> ''){
					$html .= '#'.$id.' {background:'.$bg_colour.';}';
				}
				if($text_colour <> ''){
					$html .= '#'.$id.' .wms-section-content, #'.$id.' .wms-section-content h1, #'.$id.' .wms-section-content h2, #'.$id.' .wms-section-content h3, #'.$id.' .wms-section-content h4, #'.$id.' .wms-section-content h5, #'.$id.' .wms-section-content h6, #'.$id.' .section-title, #'.$id.' .column-title {color:'.$text_colour.';}';
					$html .= '#'.$id.' a, #'.$id.' a:visited, #'.$id.' a:hover, #'.$id.' a:active, #'.$id.' a:focus {color:'.$text_colour.';}';
				}
				$html .= '</style>';
			}
			break;

		case 'thumb-left':
			// thumbnail image on the left, text on the right
			if($text_align == ''){
				$align_class = ' text-start';
			}else{
				$align_class = ' text-'.$text_align;
			}
			$section_width = 'container';
			if($width == 'wide' || $width == 'fluid'){
				$section_width = 'container-fluid';
			}
			$img_cols = 'col-12 col-sm-5 col-md-3';
			$text_cols = 'col-12 col-sm-7 col-md-9';
			$crop_size = 'post-thumb';
			if($image_size == 'small'){
				$img_cols = 'col-4 col-sm-4 col-md-3 col-lg-2';
				$text_cols = 'col-8 col-sm-8 col-md-9 col-lg-10';
				$crop_size = 'thumbnail';
			}elseif($image_size == 'large'){
				$crop_size = 'large';
			}
			$html = '<div id="'.$id.'" class="wms-section wms-section-'.$type.' '.$xclass.'">';
			$html .= '<div class="'.$section_width.' '.$align_class.'">';
			$html .= '<div class="thumb-left-wrap">';
			if($title <> ''){
				$html .= '<div class="row">';
				$html .= '<div class="col">';
				$html .= '<h3 class="thumb-left-title">'.$title.'</h3>';
				$html .= '</div>';
				$html .= '</div>';
			}
			$html .= '<div class="row">';
			$html .= '<div class="thumb-col '.$img_cols.'">';
			$html .= '<img src="'.wms_section_image_url($image, $crop_size).'" alt="">';
			$html .= '</div>';
			$html .= '<div class="wms-section-content '.$text_cols.'">';
			$html .= do_shortcode($content);
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
			if($bg_colour <> '' || $text_colour <> ''){
				$html .= '<style>';
				if($bg_colour <> ''){
					$html .= '#'.$id.' {background:'.$bg_colour.';}';
				}
				if($text_colour <> ''){
					$html .= '#'.$id.' .wms-section-content, #'.$id.' .wms-section-content h1, #'.$id.' .wms-section-content h2, #'.$id.' .wms-section-content h3, #'.$id.' .wms-section-content h4, #'.$id.' .wms-section-content h5, #'.$id.' .wms-section-content h6 {color:'.$text_colour.';}';
				}
				$html .= '</style>';
			}
			break;

		case 'logos':
			// wraps logos in rows and columns - for best effect, make all the logos the same size (640 x 400 would probably be good)
			// [logos ids="1, 2, 3" rows_xs="1" rows_sm="1" rows_md="1" rows_lg="1" rows_xl="1" logo_size="full" xclass=""]
			// bg applies to whole section
			$id_arr = explode(',', $ids);
			$good_ids = array();
			foreach ($id_arr as $logo_id) {
				$good_id = absint(trim($logo_id));
				if($good_id > 0){
					$good_ids[] = $good_id;
				}
			}
			$num_ids = count($good_ids);
			$colw_xs = $colw_sm = $colw_md = $colw_lg = $colw_xl = 0;
			if($num_ids > 0){
				$sizes = array('xs', 'sm', 'md', 'lg', 'xl');
				foreach ($sizes as $size) {
					$effective_ids = $num_ids;
					// ${$size.'_ids'} = 
					$remainder = $effective_ids % ${'rows_'.$size};
					while ($remainder > 0) {
						$effective_ids ++;
						$remainder = $effective_ids % ${'rows_'.$size};
					}
					${'colw_'.$size} = 100 * ${'rows_'.$size} / $effective_ids;
				}
			}

			$cols = 12;
			$offset = 0;
			$section_width = 'container';
			$outer_col = 'col-12';
			if($width <> ''){
				if($width == 'wide' || $width == 'fluid'){
					$section_width = 'container-fluid';
				}else{
					$section_width = 'container';
				}
				$cols = (int) $width;
				if($cols > 0 && $cols < 12){
					$offset = floor((12 - $cols) / 2);
					$outer_col = 'col-12 col-md-'.$cols;
					if($offset > 0){
						$outer_col .= ' offset-md-'.$offset;
					}
				}
			}

			$bg_classes = '';
			if($image <> ''){
				$bg_classes .= 'wms-bg-img';
				if($parallax == 'yes'){
					$bg_classes .= ' parallax';
				}
			}

			$html = '<div id="'.$id.'" class="wms-section wms-section-'.$type.' '.$xclass.'">';
			$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
			$html .= '<div class="'.$section_width.'">';
			$html .= '<div class="row">';
			$html .= '<div class="'.$outer_col.'">';
			if($title <> ''){
				$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
			}
			$html .= '<div class="row">';
			foreach ($good_ids as $logo_id) {
				$html .= '<div class="wms-logos-col">';
				$html .= '<img src="'.wms_section_image_url(trim($logo_id), $logo_size).'" alt="logo">';
				$html .= '</div><!-- .wms-logos-col -->';
			}
			$html .= '</div><!-- .row inner -->'; // row around columns
			$html .= '</div><!-- .col outer -->'; // outer col
			$html .= '</div><!-- .row outer -->'; // outer row
			$html .= '</div><!-- .container -->'; // container
			$html .= '</div><!-- .wms-sect-bg -->'; // container
			$html .= '</div><!-- .wms-section -->'; // section

			// more css for the logos section in the style.css file
			$html .= '<style>';
			$html .= '#'.$id.' .wms-logos-col {width:'.$colw_xs.'%;}';
			$html .= '@media (min-width:768px){ #'.$id.' .wms-logos-col {width:'.$colw_sm.'%;} }';
			$html .= '@media (min-width:992px){ #'.$id.' .wms-logos-col {width:'.$colw_md.'%;} }';
			$html .= '@media (min-width:1200px){ #'.$id.' .wms-logos-col {width:'.$colw_lg.'%;} }';
			$html .= '</style>';
			$html .= wms_section_css($id, $bg_colour, '', $text_colour, $bg_colour_rgba, $image, $image_size, $parallax);
			break;

		case '2-columns':
		case '3-columns':
			// the new (wms v6) 2-columns and 3-columns shortcodes designed to be used on the home page
			$cols = 12;
			$offset = 0;
			$section_width = 'container';
			$outer_col = 'col-12';
			if($width <> ''){
				if($width == 'wide' || $width == 'fluid'){
					$section_width = 'container-fluid';
				}else{
					$section_width = 'container';
				}
				$cols = (int) $width;
				if($cols > 0 && $cols < 12){
					$offset = floor((12 - $cols) / 2);
					$outer_col = 'col-12 col-md-'.$cols;
					if($offset > 0){
						$outer_col .= ' offset-md-'.$offset;
					}
				}
			}
			$bg_classes = '';
			if($image <> ''){
				$bg_classes .= 'wms-bg-img';
				if($parallax == 'yes'){
					$bg_classes .= ' parallax';
				}
			}

			$html = '<div id="'.$id.'" class="wms-section wms-section-'.$type.' '.$xclass.'">';
			$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
			$html .= '<div class="'.$section_width.'">';
			$html .= '<div class="row">';
			$html .= '<div class="'.$outer_col.'">';
			if($title <> ''){
				$html .= '<'.$heading.' class="section-title">'.$title.'</'.$heading.'>';
			}
			$html .= do_shortcode($content);
			$html .= '</div><!-- .col outer -->'; // outer col
			$html .= '</div><!-- .row outer -->'; // outer row
			$html .= '</div><!-- .container -->'; // container
			$html .= '</div><!-- .wms-sect-bg -->'; // container
			$html .= '</div><!-- .wms-section -->'; // section
			$html .= wms_section_css($id, $bg_colour, '', $text_colour, $bg_colour_rgba, $image, $image_size, $parallax, $focal_point, $image_xs, 'sect');
			break;

    	default:
    		if(function_exists(WMS_CUSTOM_SECT_PREFIX.$type)){
    			$args = array(
    				'id' => $id,
    				'type' => $type,
    				'image' => $image,
    				'parallax' => $parallax,
    				'bg_colour' => $bg_colour,
    				'bg_colour_rgba' => $bg_colour_rgba,
    				'opacity' => $opacity,
    				'text_colour' => $text_colour,
    				'text_size' => $text_size,
    				'text_align' => $text_align,
    				'youtube' => $youtube,
    				'video_quality' => $video_quality,
    				'num_items' => $num_items,
    				'title' => $title,
    				'width' => $width,
    				'heading' => $heading,
    				'image_size' => $image_size,
    				'xclass' => $xclass,
				    'ids' => $ids,
				    'rows_xs' => $rows_xs,
				    'rows_sm' => $rows_sm,
				    'rows_md' => $rows_md,
				    'rows_lg' => $rows_lg,
				    'rows_xl' => $rows_xl,
				    'logo_size' => $logo_size,
				    'focal_point' => $focal_point,
				    'image_xs' => $image_xs,
				    'subhead' => $subhead,
    				'content' => $content,
    			);
    			$function_name = WMS_CUSTOM_SECT_PREFIX.$type;
    			$html = $function_name($args);
    		}else{
    			$html = 'Error: Invalid section type: '.$type;
    		}
    		break;
    }
    return $html;
}

// col is used within the 2-cols and 3-cols sections
// [col xclass="text-center"]...[/col]
function wms_section_column($atts, $content){
	global $section_type;
	$a = shortcode_atts( array(
	    'image' => '',
	    'xclass' => '',
	    'cols' => '',
	    'bg_colour' => '',
    ), $atts );
    $image = "{$a['image']}";
    $xclass = "{$a['xclass']}";
    $cols = "{$a['cols']}";
    $bg_colour = "{$a['bg_colour']}";
    $html = '<!-- '.$section_type.' col -->';
	if($section_type <> '2-cols' && $section_type <> '3-cols' && $section_type <> '2-cols-std' && $section_type <> '3-cols-std'  && $section_type <> '4-cols' && $section_type <> '4-cols-std') return do_shortcode($content);
	static $col_num = 0;
	$col_num ++;
	if($section_type == '2-cols' || $section_type == '2-cols-std'){
		$html = '<div class="col-12 col-md-6 wms-section-content wms-col-'.$col_num.' '.$xclass.'">';
	}elseif($section_type == '4-cols' || $section_type == '4-cols-std'){
		$html = '<div class="col-12 col-md-3 wms-section-content wms-col-'.$col_num.' '.$xclass.'">';
	}else{
		$html = '<div class="col-12 col-md-4 wms-section-content wms-col-'.$col_num.' '.$xclass.'">';
	}
	if($cols <> ''){
		$cols_num = absint($cols);
		if($cols_num > 0 && $cols_num < 13){
			$html = '<div class="col-12 col-md-'.$cols_num.' wms-section-content wms-col-'.$col_num.' '.$xclass.'">';
		}
	}
	if($image <> ''){
		$html .= '<div class="wms-sect-inner wms-bg-img" style="background-image: url('.esc_url($image).');">';
	}elseif($bg_colour <> ''){
		$html .= '<div class="wms-sect-inner" style="background:'.$bg_colour.';">';
	}
	$html .= do_shortcode($content);
	if($image <> '' || $bg_colour <> ''){
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

// Nov 2018 added options heading - displayed on the bg image of hover items
// grid is used within the section shortcode with a type of grid
// the first grid shortcode must have a type of tall_text and the other 6 will have a type of hover
$grid_count = 0;
function wms_grid($atts, $content){
	global $grid_count;
	$a = shortcode_atts( array(
	    'type' => '',
	    'image' => '',
	    'xclass' => '',
	    'heading' => '',
    ), $atts );
    $type = "{$a['type']}";
    $image = "{$a['image']}";
    $xclass = "{$a['xclass']}";
    $heading = "{$a['heading']}";
    if($type == 'tall_text'){
    	if($grid_count <> 0) return 'Error: tall_text must be the first grid element only';
    	$html = '<div class="col-12 col-sm-3 wms-grid-'.$type.' wms-section-content wms-grid-'.$grid_count.' '.$xclass.'">';
    	// $html .= apply_filters('the_content', $content);
		$html .= do_shortcode($content);
    	$html .= '</div>';
    }elseif($type == 'hover'){
    	$html = '';
    	if($grid_count < 1 || $grid_count > 6) return 'Error: You need one tall_text followed by 6 hovers for this to work';
    	if($grid_count == 1){
    		$html .= '<div class="col-12 col-sm-9"><div class="row">';
    	}
    	$html .= '<div class="col-12 col-sm-4 wms-grid-'.$type.' wms-bg-img wms-grid-'.$grid_count.' wms-sect-inner '.$xclass.'"';
		if($image <> ''){
			$html .= ' style="background-image: url('.esc_url($image).');"';
		}
		$html .= '>';
		if($heading <> ''){
			$html .= '<h3 class="wms-grid-bg-heading">'.$heading.'</h3>';
		}
		if($content <> ''){
			$html .= '<div class="wms-grid-hover-show">';
			// $html .= apply_filters('the_content', $content);
			$html .= do_shortcode($content);
	    	$html .= '</div>';
	    }
    	$html .= '</div>';
    	if($grid_count == 6){
    		$html .= '</div></div>';
    	}
    }else{
    	return 'Error: Invalid grid type';
    }
    $grid_count ++;
    if($grid_count == 7) $grid_count = 0;
    return $html;
}

// returns an image url
// $image should be an ID or a URL
function wms_section_image_url($image, $size='full', $url_only=true){
	$result = array(
		'url' => $image,
		'width' => NULL,
		'height' => NULL
	);
    if(($image + 0) == $image){
    	$image_data = wp_get_attachment_image_src($image, $size);
    	if($image_data){
    		$result['url'] = $image_data[0];
    		$result['width'] = $image_data[1];
    		$result['height'] = $image_data[2];
    	}
    }
    if($url_only){
    	return $result['url'];
    }else{
    	return $result;
    }
}

// the CSS for the above sections
// applies background images and colours and text colours
function wms_section_css($id, $bg_colour='', $content_bg_colour='', $text_colour='', $bg_colour_rgba='', $image='', $image_size='', $parallax='', $focal_point='center', $image_xs='', $image_pos='sect'){
	// ccpa_write_log('wms_section_css');
	$style_needed = false;
	$set_bg_img_css = false;
	$bg_col_css = '';
	if($bg_colour <> ''){
		$bg_col_css = 'linear-gradient('.$bg_colour_rgba.', '.$bg_colour_rgba.')';
		$set_bg_img_css = true;
	}
	// collect the images
	$image_sizes = array(
		'0px' => 'xs',
		'576px' => 'sm',
		'768px' => 'md',
		'992px' => 'lg',
		'1200px' => 'xl',
		'1400px' => 'xxl',
	);
	$breakpoint_seq = array(
		'0px' => '576px',
		'576px' => '768px',
		'768px' => '992px',
		'992px' => '1200px',
		'1200px' => '1400px',
		'1400px' => 'done',
	);
	$images = array();
	$breakpoint = '0px';
	$last_img_url = '';
	if($image_xs <> ''){
		// ccpa_write_log('image_xs='.$image_xs);
		$image_url = wms_section_image_url($image_xs, $image_sizes[$breakpoint]);
		// ccpa_write_log($breakpoint.'='.$image_url);
		if($image_url <> ''){
			$images[] = array(
				'px' => $breakpoint,
				'url' => $image_url,
			);
			$breakpoint = $breakpoint_seq[$breakpoint];
			$last_img_url = $image_url;
		}
	}
	if($image <> ''){
		// ccpa_write_log('image='.$image);
		if($image_size == ''){
			while ($breakpoint <> 'done') {
				$image_url = wms_section_image_url($image, $image_sizes[$breakpoint]);
				// ccpa_write_log($breakpoint.'='.$image_url);
				if($image_url <> '' && $image_url <> $last_img_url){
					$images[] = array(
						'px' => $breakpoint,
						'url' => $image_url,
					);
					$last_img_url = $image_url;
				}
				$breakpoint = $breakpoint_seq[$breakpoint];
			}
		}else{
			// ccpa_write_log('image_size='.$image_size);
			$image_url = wms_section_image_url($image, $image_size);
			// ccpa_write_log($breakpoint.'='.$image_url);
			if($image_url <> '' && $image_url <> $last_img_url){
				$images[] = array(
					'px' => $breakpoint,
					'url' => $image_url,
				);
			}
		}
	}
	// ccpa_write_log($images);
	if(!empty($images)){
		$set_bg_img_css = true;
	}
	/*
	$std_img_css = '';
	if($image <> ''){
		$std_img_css = 'url('.wms_section_image_url($image, $image_size).')';
		$set_bg_img_css = true;
	}
	$mob_img_css = '';
	if($image_xs <> ''){
		$mob_img_css = 'url('.wms_section_image_url($image_xs, 'post-thumb').')';
		$set_bg_img_css = true;
	}
	*/

	$css = '<style>';
	if($set_bg_img_css){
		$style_needed = true;
		if($image_pos == 'sect'){
			if(empty($images)){
				// must be just a colour
				$css .= '#'.$id.' .wms-sect-bg {background-image:'.$bg_col_css.';}';
			}else{
				if($bg_col_css <> ''){
					$bg_col_css .= ', ';
				}
				foreach ($images as $image_stuff) {
					if($image_stuff['px'] <> '0px'){
						$css .= '@media (min-width:'.$image_stuff['px'].'){';
					}
					$css .= '#'.$id.' .wms-sect-bg {background-image:'.$bg_col_css.'url('.$image_stuff['url'].');';
					if($focal_point <> 'center' && $focal_point <> ''){
						$css .= 'background-position:'.$focal_point.';';
					}
					$css .= '}';
					if($image_stuff['px'] <> '0px'){
						$css .= '}';
					}

				}
			}
			/*
			if($bg_col_css <> '' && $std_img_css <> ''){
				$css .= ', ';
			}
			if($mob_img_css <> ''){
				$css .= $mob_img_css;
			}else{
				$css .= $std_img_css;
				if($focal_point <> 'center' && $focal_point <> ''){
					$css .= ';background-position:'.$focal_point;
				}
			}
			$css .= ';}';
			if($mob_img_css <> ''){
				$css .= '@media (min-width:576px){#'.$id.' .wms-sect-bg {background-image:'.$bg_col_css;
				if($bg_col_css <> '' && $std_img_css <> ''){
					$css .= ', ';
				}
				$css .= $std_img_css.';';
				if($focal_point <> 'center' && $focal_point <> ''){
					$css .= 'background-position:'.$focal_point.';';
				}
				$css .= '}}';
			}
			*/
		}else{
			// colour on section and image in column
			if($bg_col_css <> ''){
				$css .= '#'.$id.' .wms-sect-bg {background-image:'.$bg_col_css.';}';	
			}
			foreach ($images as $image_stuff) {
				if($image_stuff['px'] <> '0px'){
					$css .= '@media (min-width:'.$image_stuff['px'].'){';
				}
				$css .= '#'.$id.' .wms-bg-img {background-image:url('.$image_stuff['url'].');';
				if($focal_point <> 'center' && $focal_point <> ''){
					$css .= 'background-position:'.$focal_point.';';
				}
				$css .= '}';
				if($image_stuff['px'] <> '0px'){
					$css .= '}';
				}

			}
			/*
			if($bg_col_css <> ''){
				$css .= '#'.$id.' .wms-sect-bg {background-image:'.$bg_col_css.';}';	
			}
			if($std_img_css <> ''){
				$css .= '#'.$id.' .wms-bg-img {background-image:';
				if($mob_img_css <> ''){
					$css .= $mob_img_css;
				}else{
					$css .= $std_img_css;
					if($focal_point <> 'center' && $focal_point <> ''){
						$css .= ';background-position:'.$focal_point;
					}
				}
				$css .= ';}';
				if($mob_img_css <> ''){
					$css .= '@media (min-width:576px){#'.$id.' .wms-bg-img {background-image:'.$bg_col_css;
					if($bg_col_css <> '' && $std_img_css <> ''){
						$css .= ', ';
					}
					$css .= $std_img_css.';';
					if($focal_point <> 'center' && $focal_point <> ''){
						$css .= 'background-position:'.$focal_point.';';
					}
					$css .= '}}';
				}
			}
			*/
		}
	}
	if($content_bg_colour <> ''){
		$style_needed = true;
		$css .= '#'.$id.' .wms-section-content {background:'.$content_bg_colour.';}';
	}
	if($text_colour <> ''){
		$style_needed = true;
		$css .= '#'.$id.', #'.$id.' h1, #'.$id.' h2, #'.$id.' h3, #'.$id.' h4, #'.$id.' h5, #'.$id.' h6, #'.$id.' .section-title, #'.$id.' .column-title {color:'.$text_colour.';}';
		$css .= '#'.$id.' a:not(.btn), #'.$id.' a:not(.btn):visited, #'.$id.' a:not(.btn):hover, #'.$id.' a:not(.btn):active, #'.$id.' a:not(.btn):focus {color:'.$text_colour.';}';
	}
	$css .= '</style>';
	// ccpa_write_log($css);
	if($style_needed){
		return $css;
	}else{
		return '';
	}
	
}

// [row] shortcode
// replaces (and built from) Bootstrap 3 Shortcodes row function
function wms_row( $atts, $content = null ) {
	$atts = shortcode_atts( array(
			"xclass" => false,
			"data"   => false
	), $atts );
	$class  = 'row';
	$class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';
	// $data_props = $this->parse_data_attributes( $atts['data'] );
	$data_props = wms_parse_data_attributes( $atts['data'] );
	return sprintf(
		'<div class="%s"%s>%s</div>',
		esc_attr( trim($class) ),
		( $data_props ) ? ' ' . $data_props : '',
		do_shortcode( $content )
	);
}

// [column] shortcode
// replaces (and built from) Bootstrap 3 shortcodes
// pull and push no longer work in BS 5, replaced by order
function wms_column( $atts, $content = null ) {
	$atts = shortcode_atts( array(
			"xxl"         => false,
			"xl"          => false,
			"lg"          => false,
			"md"          => false,
			"sm"          => false,
			"xs"          => false,
			"offset_xxl"  => false,
			"offset_xl"   => false,
			"offset_lg"   => false,
			"offset_md"   => false,
			"offset_sm"   => false,
			"offset_xs"   => false,
			"order_xxl"   => false,
			"order_xl"    => false,
			"order_lg"    => false,
			"order_md"    => false,
			"order_sm"    => false,
			"order_xs"    => false, // same as order
			"order"       => false,
			"xclass"      => false,
			"data"        => false
	), $atts );
	$class  = '';
	$class .= ( $atts['xxl'] )			                                ? ' col-xxl-' . $atts['xxl'] : '';
	$class .= ( $atts['xl'] )			                                ? ' col-xl-' . $atts['xl'] : '';
	$class .= ( $atts['lg'] )			                                ? ' col-lg-' . $atts['lg'] : '';
	$class .= ( $atts['md'] )                                           ? ' col-md-' . $atts['md'] : '';
	$class .= ( $atts['sm'] )                                           ? ' col-sm-' . $atts['sm'] : '';
	$class .= ( $atts['xs'] )                                           ? ' col-' . $atts['xs'] : '';
	$class .= ( $atts['offset_xxl'] || $atts['offset_xxl'] === "0" )    ? ' offset-xxl-' . $atts['offset_xxl'] : '';
	$class .= ( $atts['offset_xl'] || $atts['offset_xl'] === "0" )      ? ' offset-xl-' . $atts['offset_xl'] : '';
	$class .= ( $atts['offset_lg'] || $atts['offset_lg'] === "0" )      ? ' offset-lg-' . $atts['offset_lg'] : '';
	$class .= ( $atts['offset_md'] || $atts['offset_md'] === "0" )      ? ' offset-md-' . $atts['offset_md'] : '';
	$class .= ( $atts['offset_sm'] || $atts['offset_sm'] === "0" )      ? ' offset-sm-' . $atts['offset_sm'] : '';
	$class .= ( $atts['offset_xs'] || $atts['offset_xs'] === "0" )      ? ' offset-' . $atts['offset_xs'] : '';
	$class .= ( $atts['order_xxl']   || $atts['order_xxl'] === "0" )    ? ' order-xxl' . $atts['order_xxl'] : '';
	$class .= ( $atts['order_xl']   || $atts['order_xl'] === "0" )      ? ' order-xl' . $atts['order_xl'] : '';
	$class .= ( $atts['order_lg']   || $atts['order_lg'] === "0" )      ? ' order-lg' . $atts['order_lg'] : '';
	$class .= ( $atts['order_md']   || $atts['order_md'] === "0" )      ? ' order-md' . $atts['order_md'] : '';
	$class .= ( $atts['order_sm']   || $atts['order_sm'] === "0" )      ? ' order-sm' . $atts['order_sm'] : '';
	$class .= ( $atts['order_xs']   || $atts['order_xs'] === "0" )      ? ' order' . $atts['order_xs'] : '';
	$class .= ( $atts['order']   || $atts['order'] === "0" )            ? ' order' . $atts['order'] : '';
	$class .= ( $atts['xclass'] )                                       ? ' ' . $atts['xclass'] : '';
	$data_props = wms_parse_data_attributes( $atts['data'] );
	return sprintf(
		'<div class="%s"%s>%s</div>',
		esc_attr( trim($class) ),
		( $data_props ) ? ' ' . $data_props : '',
		do_shortcode( $content )
	);
}

