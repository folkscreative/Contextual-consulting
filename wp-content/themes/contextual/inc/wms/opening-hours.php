<?php
/***
 * Opening Hours 
 */

add_shortcode('opening_hours', 'wms_opening_hours_shortcode');

// displays the opening hours (inside a background if wanted)
// [opening_hours bg_colour="#ffffff" text_colour="#000" title="" link="" link_text="" btn_type="primary" xclass="" cols_xs="" cols_sm="" cols_md="" cols_lg="" cols_xl="" cols_xxl=""]
function wms_opening_hours_shortcode($atts){
	global $rpm_theme_options;
    $a = shortcode_atts( array(
        'bg_colour' => '',
        'text_colour' => '',
        'title' => '',
        'link' => '',
        'link_text' => '',
        'btn_type' => 'primary',
        'xclass' => '',
        'cols_xs' => '',
        'cols_sm' => '',
        'cols_md' => '',
        'cols_lg' => '',
        'cols_xl' => '',
        'cols_xxl' => '',
    ), $atts );
    $bg_colour = "{$a['bg_colour']}";
    $text_colour = "{$a['text_colour']}";
    $title = "{$a['title']}";
    $link = "{$a['link']}";
    $link_text = "{$a['link_text']}";
    $btn_type = "{$a['btn_type']}";
    $xclass = "{$a['xclass']}";
    $columns['xs'] = "{$a['cols_xs']}";
    $columns['sm'] = "{$a['cols_sm']}";
    $columns['md'] = "{$a['cols_md']}";
    $columns['lg'] = "{$a['cols_lg']}";
    $columns['xl'] = "{$a['cols_xl']}";
    $columns['xxl'] = "{$a['cols_xxl']}";

    $content = '';
    if($title <> ''){
    	$content .= '<h3 class="opening-hours-title text-center">'.$title.'</h3>';
    }
    // acquire the opening hours
    $content .= '<div class="hours-div row">';
    $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
    foreach($days as $dayname){
    	$content .= '<div class="day-name col-5">'.$dayname.'</div>';
    	$content .= '<div class="day-hours col-7">'.$rpm_theme_options['opening-hours-'.strtolower($dayname)].'</div>';
    }
    $content .= '</div>';

    if($link <> '' && $link_text <> ''){
    	$content .= '<p class="text-center opening-hours-btn-wrap">';
    	$content .= '<a class="btn btn-'.$btn_type.'" href="'.esc_url($link).'">'.$link_text.'</a>';
    	$content .= '</p>';
    }

    $html = '<div class="row opening-hours-wrap">';
    if($columns['xs'] == ''){
    	$cols = 'col';
    }else{
    	$cols = 'col-'.$columns['xs'];
    }
    $sizes = array('sm', 'md', 'lg', 'xl', 'xxl');
    foreach ($sizes as $size) {
    	if($columns[$size] <> ''){
    		$cols .= ' col-'.$size.'-'.$columns[$size];
    		if($columns[$size] < 11){
    			$offset = floor((12 - $columns[$size]) / 2 );
    			$cols .= ' offset-'.$size.'-'.$offset;
    		}
    	}
    }
    $html .= '<div class="'.$cols.'">';

    $html .= do_shortcode('[background bg_colour="'.$bg_colour.'" opacity="" text_colour="'.$text_colour.'" xclass="'.$xclass.'"]'.$content.'[/background]');

    $html .= '</div></div>';
    return $html;
}