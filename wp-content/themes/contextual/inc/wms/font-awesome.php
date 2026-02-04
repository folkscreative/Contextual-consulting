<?php
/**
 * WMS
 * Font Awesome - Adds simple Font Awesome functionality
 * Based on Font Awesome v5.13.0
 *
 * Includes:
 * - 
 */

/**
 * Apr 2021
 * - now does not bother to remove the icon shortcode set by the Bootstrap Shortcodes plugin as it will be overriden if it is set
 * Jun 2020
 * - Bootstrap shortcodes also includes an icon shortcode so we're now removing that first
 * 12/1/19
 * - added in icon left, icon list and circle plus icon size
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_action('init', 'wms_font_awesome_shortcodes', 20);
function wms_font_awesome_shortcodes(){
    // The BS 3 shortcodes icon shortcode (if set) will be overriden here as we have a priority of 20
    add_shortcode('icon', 'wms_font_awesome');
    add_shortcode('icon_list', 'wms_font_awesome_list');
    add_shortcode('icon_circle', 'wms_font_awesome_circle');
    add_shortcode('icon_left', 'wms_font_awesome_icon_left');
}

// eg [icon name="heart" type="solid" list="yes" size="2x"]
function wms_font_awesome($atts, $content){
    $a = shortcode_atts( array(
        'name' => '',
        'type' => 'solid',
        'xclass' => '',
        'list' => '',
        'size' => '',
    ), $atts );
    $name = "{$a['name']}";
    $type = "{$a['type']}";
    $xclass = "{$a['xclass']}";
    $list = "{$a['list']}";
    $size = "{$a['size']}";
    return wms_font_awesome_core( $name, $type, $xclass, $list, $size );
}

// the core FA function
function wms_font_awesome_core( $name, $type, $xclass, $list, $size ){
    $icon_class = '';
    if($type == 'brand'){
        $icon_class .= 'fab';
    }elseif($type == 'regular'){
        $icon_class .= 'far';
    }else{
        $icon_class .= 'fas';
    }
    if($size <> ''){
        $size = ' fa-'.$size;
    }
    $icon_class .= ' fa-'.$name.$size.' '.$xclass;
    $html = '<i class="'.$icon_class.'"></i>';
    if($list == 'yes'){
        $html = '<span class="fa-li" >'.$html.'</span>';
    }
    return $html;
}

// [icon_list] ... lots of <li>s and [icon list="yes"] ... [/icon_list]
function wms_font_awesome_list($atts, $content){
    $a = shortcode_atts( array(
        'xclass' => '',
    ), $atts );
    $xclass = "{$a['xclass']}";
    $html = '<ul class="fa-ul">';
    // get rid of the <ul>
    $content = str_replace('<ul>', '', $content);
    $html .= do_shortcode($content);
    return $html;
}

// [icon_circle icon="skull-crossbones" text="A" xclass=""]
// use text OR icon, not both
function wms_font_awesome_circle($atts){
    $a = shortcode_atts( array(
        'text' => '',
        'icon' => '',
        'xclass' => '',
    ), $atts );
    $text = "{$a['text']}";
    $icon = "{$a['icon']}";
    $xclass = "{$a['xclass']}";
    return wms_font_awesome_circle_html($icon, $text, $xclass);
}
// and so we can use it without a shortcode ....
function wms_font_awesome_circle_html($icon='', $text='', $xclass=''){
    if($icon <> ''){
        $stack = '<i class="fas fa-'.$icon.' fa-stack-1x fa-inverse"></i>';
    }else{
        $stack = '<strong class="fa-stack-1x fa-inverse">'.$text.'</strong>';
    }
    return '<span class="wms-icon-circle fa-stack fa-2x '.$xclass.'"><i class="fas fa-circle fa-stack-2x"></i>'.$stack.'</span>';
}

// splits a column to add an icon to the left of it
// usage = [icon_left icon="" size="3x" xclass=""] ... [/icon_left]
// icon can be a FA icon or an attachment id
function wms_font_awesome_icon_left($atts, $content){
    $a = shortcode_atts( array(
        'icon' => '',
        'size' => '3x',
        'xclass' => '',
        'icon_class' => '',
    ), $atts );
    $icon = "{$a['icon']}";
    if(absint($icon) == $icon){
        $image_data = wp_get_attachment_image_src($icon, 'full');
    }else{
        $image_data = false;
    }
    $size = "{$a['size']}";
    $xclass = "{$a['xclass']}";
    $icon_class = "{$a['icon_class']}";
    $html = '<!-- icon_left -->';
    $html .= '<div class="row wms-icon_left '.$xclass.'"><div class="col-2 icon-col">';
    if($image_data){
        $html .= '<img src="'.esc_url($image_data[0]).'" alt="" class="'.$icon_class.'">';
    }else{
        $html .= '<i class="fas fa-'.$icon.' fa-'.$size.' '.$icon_class.'"></i>';
    }
    $html .= '</div><div class="col-10">';
    $html .= do_shortcode($content);
    $html .= '</div></div>';
    return $html;
}
