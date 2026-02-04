<?php
/**
 * WMS - Background shortcode
 */

add_shortcode('background','wms_background');

// Background Shortcode
// e.g [background image="http://xxx.jpg" bg_colour="#ffffff" opacity="0.8" text_colour="#000" xclass="quote" focal_point="center" image_size="post-thumb" link="http://..." link_target="_blank"]...[/background]
// Parameters (all are optional):
//  - image = a URL or an image ID. The image goes behind the colour
//  - bg_colour = 3 or 6 character RGB colour (with or without "#"). The image goes behind the colour
//  - text_colour = 3 or 6 character RGB colour (with or without "#")
//  - opacity = a number between 0 (transparent) and 1 (solid). Default = 0.8
//  - xclass = any extra classes you want added, separated by spaces
//  - focal_point = any background-position property. Defaults to center
//  - image_size = any pre-defined image size. Defaults to post-thumb
//  - link = makes the complete background area and all included content into a link
//  - link_target = any link target
function wms_background($atts, $content){
	$a = shortcode_atts( array(
	    'image' => '',
	    'bg_colour' => '',
	    'text_colour' => '',
	    'opacity' => 0.8,
	    'xclass' =>'',
	    'focal_point' => 'center',
	    'image_size' => 'post-thumb',
	    'link' => '',
	    'link_target' => '',
    ), $atts );
	$image = "{$a['image']}";
	$bg_colour = "{$a['bg_colour']}";
	$text_colour = "{$a['text_colour']}";
	$opacity = (float) "{$a['opacity']}";
	$xclass = "{$a['xclass']}";
	$focal_point = "{$a['focal_point']}";
	$image_size = "{$a['image_size']}";
	$link = "{$a['link']}";
	$link_target = "{$a['link_target']}";
	if($opacity > 1 || $opacity < 0) $opacity = 0.8;
	$colour_rgba = wms_hex2rgba($bg_colour, $opacity);
	if($link <> ''){
		$xclass .= ' bg-link';
	}
	$div_id = 'wms-background-'.rand(1000,9999);
	$HTML = '<div id="'.$div_id.'" class="wms-background-outer">';
	if($text_colour <> ''){
		$HTML .= '<style>';
		$HTML .= '#'.$div_id.' h1, #'.$div_id.' h2, #'.$div_id.' h3, #'.$div_id.' h4, #'.$div_id.' h5, #'.$div_id.' h6, #'.$div_id.' p, #'.$div_id.' a:not(.btn), #'.$div_id.' a:not(.btn):visited, #'.$div_id.' a:not(.btn):hover, #'.$div_id.' a:not(.btn):active, #'.$div_id.' a:not(.btn):focus {color:'.$text_colour.'!important;}';
		$HTML .= '</style>';
	}
	$HTML .= '<div class="wms-background';
	if($xclass <> ''){
		$HTML .= ' '.$xclass;
	}
	$HTML .= '" style="';
	if($bg_colour <> ''){
		if($opacity <> ''){
			$bg_colour_rgba = wms_hex2rgba($bg_colour, $opacity);
			if($bg_colour_rgba <> 'rgb(0,0,0)'){
				$HTML .= 'background-color:'.$bg_colour_rgba.';';
			}else{
				$HTML .= 'background-color:'.$bg_colour.';';
			}
		}else{
			$HTML .= 'background-color:'.$bg_colour.';';
		}
	}
	$HTML .= '">';
	if($link <> ''){
		$HTML .= '<a href="'.$link.'"';
		if($link_target <> ''){
			$HTML .= ' target="'.$link_target.'"';
		}
		$HTML .= '>';
	}
	$HTML .= '<div class="inner"';
	if($text_colour <> ''){
		$HTML .= ' style="color:'.$text_colour.'"';
	}
	$HTML .= '>';
	$HTML .= do_shortcode($content);
	$HTML .= '</div>';
	if($link <> ''){
		$HTML .= '</a>';
	}
	if($image <> ''){
		$HTML .= '<div class="img-bg" style="background-image:';
		if($bg_colour <> ''){
			$HTML .= 'linear-gradient('.$colour_rgba.', '.$colour_rgba.'), ';
		}
		$HTML .= 'url('.wms_section_image_url($image, $image_size).');';
		if($focal_point <> 'center'){
			$HTML .= ' background-position:'.$focal_point.';';
		}
		$HTML .= '"></div>';
	}
	$HTML .= '</div>';
	$HTML .= '</div>';
	return $HTML;
}
