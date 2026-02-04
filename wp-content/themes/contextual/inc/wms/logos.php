<?php
/**
 * Logos
 */

// wraps logos in rows and columns - for best effect, make all the logos the same size!
// [logos ids="1, 2, 3" rows_xs="1" rows_sm="1" rows_md="1" rows_lg="1" rows_xl="1" logo_size="full" xclass=""]
add_shortcode('logos', 'wms_logos');
function wms_logos($atts){
	$a = shortcode_atts( array(
		'ids' => '',
		'rows_xs' => 1,
		'rows_sm' => 1,
		'rows_md' => 1,
		'rows_lg' => 1,
		'rows_xl' => 1,
		'rows_xxl' => 1,
		'logo_size' => 'full',
		'xclass' => ''
	), $atts);
	$ids = "{$a['ids']}";
	$breakpoints = array('xs', 'sm', 'md', 'lg', 'xl', 'xxl');
	foreach ($breakpoints as $breakpoint) {
		${"rows_$breakpoint"} = absint("{$a['rows_'.$breakpoint]}");
		if(${"rows_$breakpoint"} < 1) ${"rows_$breakpoint"} = 1;
	}
	/*
	$rows_xs = absint("{$a['rows_xs']}");
	$rows_sm = absint("{$a['rows_sm']}");
	$rows_md = absint("{$a['rows_md']}");
	$rows_lg = absint("{$a['rows_lg']}");
	$rows_xl = absint("{$a['rows_xl']}");
	if($rows_xs < 1) $rows_xs = 1;
	if($rows_sm < 1) $rows_sm = 1;
	if($rows_md < 1) $rows_md = 1;
	if($rows_lg < 1) $rows_lg = 1;
	if($rows_xl < 1) $rows_xl = 1;
	*/
	$logo_size = "{$a['logo_size']}";
	$xclass = "{$a['xclass']}";
	$id_arr = explode(',', $ids);
	$num_ids = count($id_arr);
	if($num_ids > 0){
		foreach ($breakpoints as $breakpoint) {
			${"colw_$breakpoint"} = 100 / ceil($num_ids / ${"rows_$breakpoint"});
		}
		/*
		$colw_xs = 100 / ceil($num_ids / $rows_xs);
		$colw_sm = 100 / ceil($num_ids / $rows_sm);
		$colw_md = 100 / ceil($num_ids / $rows_md);
		$colw_lg = 100 / ceil($num_ids / $rows_lg);
		$colw_xl = 100 / ceil($num_ids / $rows_xl);
		*/
		$div_id = 'wms-logos-'.rand(1000,9999);	
		$html = '<div id="'.$div_id.'" class="row wms-logos-row '.$xclass.'">';
		foreach ($id_arr as $id) {
			$html .= '<div class="wms-logos-col">';
			$image_url = wms_section_image_url(trim($id), $logo_size);
			// will just have returned the id if it cannot be found
			if($image_url <> $id){
				$html .= '<img class="wms-logo" src="'.$image_url.'" alt="logo">';
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		// more css in the style.css file
		$html .= '<style>';
		$html .= '#'.$div_id.' .wms-logos-col {width:'.$colw_xs.'%;}';
		$html .= '@media (min-width:576px){ #'.$div_id.' .wms-logos-col {width:'.$colw_sm.'%;} }';
		$html .= '@media (min-width:768px){ #'.$div_id.' .wms-logos-col {width:'.$colw_md.'%;} }';
		$html .= '@media (min-width:992px){ #'.$div_id.' .wms-logos-col {width:'.$colw_lg.'%;} }';
		$html .= '@media (min-width:1200px){ #'.$div_id.' .wms-logos-col {width:'.$colw_xl.'%;} }';
		$html .= '@media (min-width:1400px){ #'.$div_id.' .wms-logos-col {width:'.$colw_xxl.'%;} }';
		$html .= '</style>';
		return $html;
	}
}
