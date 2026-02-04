<?php
/**
 * Contextual specific page sections
 */

// the Next Live training section on the home page
function contextual_wms_section_training_next($args){
	$cols = 12;
	$offset = 0;
	$section_width = 'container';
	$outer_col = 'col-12';
	if($args['width'] <> ''){
		if($args['width'] == 'wide' || $args['width'] == 'fluid'){
			$section_width = 'container-fluid';
		}else{
			$section_width = 'container';
		}
		$cols = (int) $args['width'];
		if($cols > 0 && $cols < 12){
			$offset = floor((12 - $cols) / 2);
			$outer_col = 'col-12 col-md-'.$cols;
			if($offset > 0){
				$outer_col .= ' offset-md-'.$offset;
			}
		}
	}
	$bg_classes = '';
	if($args['image'] <> ''){
		$bg_classes .= 'wms-bg-img';
		if($parallax == 'yes'){
			$bg_classes .= ' parallax';
		}
	}
	$bg_classes = '';
	$html = '<a id="training"></a>';
	$html .= '<div id="'.$args['id'].'" class="wms-section wms-section-training section-training-next '.$args['xclass'].'">';
	$html .= '<div class="wms-sect-bg '.$bg_classes.'">';

	$html .= '<div class="row">';
	$html .= '<div class="col-12">';
	$html .= '<div class="sect-text my-5">';
	$html .= do_shortcode($args['content']);
	$html .= '</div><!-- .sect-text -->';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';
	
	$html .= '<div class="'.$section_width.'">';
	$html .= '<div class="row">';
	$html .= '<div class="'.$outer_col.'">';
	$html .= '<div class="sect-wrap">';

	$html .= '<'.$args['heading'].' class="section-title">'.$args['title'].'</'.$args['heading'].'>';
	
	$html .= '<div class="row">';
	$html .= '<div class="col-12">';
	$next_workshop_id = cc_wksp_next_workshop_id();
	$html .= cc_wksp_next_workshop($next_workshop_id);
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';

	$html .= '</div><!-- .sect-wrap -->';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .container -->';
	$html .= '</div><!-- .wms-sect-bg -->';
	$html .= '</div><!-- .wms-section -->';
	// function wms_section_css($id, $bg_colour='', $content_bg_colour='', $text_colour='', $bg_colour_rgba='', $image='', $image_size='', $parallax='', $focal_point='center', $image_xs=''){
	$html .= wms_section_css($args['id'], $args['bg_colour'], '', $args['text_colour'], $args['bg_colour_rgba'], $args['image'], 'post-thumb', $args['parallax'], $args['focal_point'], $args['image_xs'], 'col');
	return $html;
}

// the upcoming training section on the home page
function contextual_wms_section_training_upcoming($args){
	$cols = 12;
	$offset = 0;
	$section_width = 'container';
	/*
	$outer_col = 'col-12';
	if($args['width'] <> ''){
		if($args['width'] == 'wide' || $args['width'] == 'fluid'){
			$section_width = 'container-fluid';
		}else{
			$section_width = 'container';
		}
		$cols = (int)$args['width'] + 0;
		if($cols > 0 && $cols < 12){
			$offset = floor((12 - $cols) / 2);
			$outer_col = 'col-12 col-md-'.$cols;
			if($offset > 0){
				$outer_col .= ' offset-md-'.$offset;
			}
		}
	}
	*/
	$outer_col = 'col-12 col-md-10 offset-md-1 col-lg-12 offset-lg-0';
	$bg_classes = '';
	if($args['image'] <> ''){
		$bg_classes .= 'wms-bg-img';
		if($parallax == 'yes'){
			$bg_classes .= ' parallax';
		}
	}
	$bg_classes = '';
	$html = '<div id="'.$args['id'].'" class="wms-section wms-section-training section-training-upcoming '.$args['xclass'].'">';
	$html .= '<div class="wms-sect-bg '.$bg_classes.'">';
	$html .= '<div class="'.$section_width.'">';
	$html .= '<div class="row">';
	$html .= '<div class="'.$outer_col.'">';
	$html .= '<div class="sect-wrap">';

	$html .= '<'.$args['heading'].' class="section-title">'.$args['title'].'</'.$args['heading'].'>';
	if($args['content'] <> ''){
		$html .= '<div class="sect-text mb-5">';
		$html .= do_shortcode($args['content']);
		$html .= '</div><!-- .sect-text -->';
	}
	
	$next_workshop_id = cc_wksp_next_workshop_id();
	$html .= cc_wksp_cards_featured($next_workshop_id);

	$html .= '</div><!-- .sect-wrap -->';
	$html .= '</div><!-- .col -->';
	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .container -->';
	$html .= '</div><!-- .wms-sect-bg -->';
	$html .= '</div><!-- .wms-section -->';
	// function wms_section_css($id, $bg_colour='', $content_bg_colour='', $text_colour='', $bg_colour_rgba='', $image='', $image_size='', $parallax='', $focal_point='center', $image_xs=''){
	$html .= wms_section_css($args['id'], $args['bg_colour'], '', $args['text_colour'], $args['bg_colour_rgba'], $args['image'], 'post-thumb', $args['parallax'], $args['focal_point'], $args['image_xs'], 'col');
	return $html;
}
