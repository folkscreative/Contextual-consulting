<?php
/**
 * Break
 * forces a line break depending on screen size
 * based on Bootstrap's w-100 class
 */

// [break size="xs, sm, md, lg, xl" xclass=""]
add_shortcode('break', 'wms_break');
function wms_break($atts){
	$a = shortcode_atts( array(
		'size' => 'xs, sm, md, lg, xl',
		'xclass' => '',
	), $atts);
	$size = "{$a['size']}";
	$xclass = "{$a['xclass']}";
	$classes = 'w-100 '.$xclass.' ';
	$prev_class = '';
	if(strpos($size, 'xs') !== false){ // 0 can be true :-)
		// default
		$prev_class = 'break';
	}else{
		$classes .= 'd-none ';
		$prev_class = 'none';
	}
	$xsizes = array('sm', 'md', 'lg', 'xl');
	foreach ($xsizes as $xsize) {
		if(strpos($size, $xsize) !== false){
			if($prev_class == 'none'){
				$classes .= 'd-'.$xsize.'-block ';
				$prev_class = 'block';
			}
		}else{
			if($prev_class == 'block'){
				$classes .= 'd-'.$xsize.'-none ';
				$prev_class = 'none';
			}
		}
	}
	return '<div class="'.$classes.'"></div>';
}