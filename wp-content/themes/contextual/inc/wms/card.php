<?php
/**
 * 
 * Card
 * also includes the much simplified wrap - which does not use flex so works better with images on IE
 *
 */

/**
 * v6.1 Dec 2021
 * - added image to card
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_shortcode('card', 'wms_card');
add_shortcode('wrap', 'wms_card_wrap');

// the main card function
// [card image="" title="" subtitle="" bg_colour="" text_colour="" ratio="3:2" xclass=""]... body ...[/card]
// Feb 2022
// - added image_link
function wms_card($atts, $content){
    $a = shortcode_atts( array(
        'title' => '',
        'subtitle' => '',
        'bg_colour' => '',
        'text_colour' => '',
        'xclass' => '',
        'image' => '',
        'btn_text' => '',
        'btn_class' => '',
        'btn_link' => '',
        'image_link' => '',
        'ratio' => '3:2',
        'size' => 'post-thumb',
    ), $atts );
    $title = "{$a['title']}";
    $subtitle = "{$a['subtitle']}";
    $bg_colour = "{$a['bg_colour']}";
    $text_colour = "{$a['text_colour']}";
    $xclass = "{$a['xclass']}";
    $image = "{$a['image']}";
    $btn_text = "{$a['btn_text']}";
    $btn_class = "{$a['btn_class']}";
    $btn_link = "{$a['btn_link']}";
    $image_link = "{$a['image_link']}";
    $ratio = "{$a['ratio']}";
    $size = "{$a['size']}";
    if(in_array($image_link, array('yes', 'true')) && $btn_text <> '' && $btn_link <> ''){
        $image_link = $btn_link;
    }else{
        $image_link = '';
    }
    $id = 'wms-card-'.mt_rand();
    $bg_style = $text_style = '';
    if($bg_colour <> ''){
    	// $bg_style = 'style="background-color:'.$bg_colour.';"';
    }
    if($text_colour <> ''){
    	$text_style = 'style="color:'.$text_colour.';"';
    }
    $html = '<div id="'.$id.'" class="card mb-3 '.$xclass.'" '.$bg_style.'>';
    if($image <> ''){
        $html .= wms_media_image_html($image, $size, true, $ratio, $image_link, 'card-img-top');
    }
    $html .= '<div class="card-body">';
    if($title <> ''){
    	$html .= '<h5 class="card-title" '.$text_colour.'>'.$title.'</h5>';
    }
    if($subtitle <> ''){
    	$html .= '<h6 class="card-subtitle mb-2 text-muted" '.$text_colour.'>'.$subtitle.'</h6>';
    }
    $html .= '<div class="card-text mb-3">'.do_shortcode($content).'</div>';
    if($btn_text <> '' && $btn_link <> ''){
        $html .= '<a href="'.$btn_link.'" class="btn btn-'.$btn_class.'">'.$btn_text.'</a>';
    }
	$html .= '</div></div>';
    if($bg_colour <> '' || $text_colour <> ''){
        $html .= '<style>';
        if($bg_colour <> ''){
            $html .= '#'.$id.' {background-color:'.$bg_colour.';}';
        }
        if($text_colour <> ''){
            $html .= '#'.$id.' .card-body, #'.$id.' h1, #'.$id.' h2, #'.$id.' h3, #'.$id.' h4, #'.$id.' h5, #'.$id.' h6, #'.$id.' .card-title, #'.$id.' .card-subtitle, #'.$id.' .card-text {color:'.$text_colour.';}';
            $html .= '#'.$id.' a:not(.btn), #'.$id.' a:not(.btn):visited, #'.$id.' a:not(.btn):hover, #'.$id.' a:not(.btn):active, #'.$id.' a:not(.btn):focus {color:'.$text_colour.';}';
        }
        $html .= '</style>';
    }
	return $html;
}

// a simplified card
// enhanced for Contextual Consulting
// [wrap bg_colour="#ffffff" xclass=""]...[/wrap]
function wms_card_wrap($atts, $content){
    $a = shortcode_atts( array(
        'bg_colour' => '',
        'text_colour' => '',
        'border_colour' => '',
        'icon' => 'circle-check',
        'icon_style' => 'regular',
        'xclass' => '',
    ), $atts );
    $bg_colour = "{$a['bg_colour']}";
    $text_colour = "{$a['text_colour']}";
    $border_colour = "{$a['border_colour']}";
    $icon = "{$a['icon']}";
    $icon_style = "{$a['icon_style']}";
    $xclass = "{$a['xclass']}";
    $id = 'wms-card-wrap'.mt_rand();
    $html = '<div id="'.$id.'" class="wms-card-wrap '.$xclass.'">';
    if($icon <> 'none'){
        $html .= '<i class="fa-'.$icon_style.' fa-'.$icon.' fa-fw wms-card-wrap-icon"></i>';
    }
    $html .= do_shortcode($content);
    $html .= '</div>';
    if($bg_colour <> '' || $text_colour <> '' || $border_colour <> ''){
        $html .= '<style>';
        if($bg_colour <> ''){
            $html .= '#'.$id.' {background:'.$bg_colour.';}';
        }
        if($text_colour <> ''){
            $html .= '#'.$id.', #'.$id.' h1, #'.$id.' h2, #'.$id.' h3, #'.$id.' h4, #'.$id.' h5, #'.$id.' h6 {color:'.$text_colour.';}';
            $html .= '#'.$id.' a:not(.btn), #'.$id.' a:not(.btn):visited, #'.$id.' a:not(.btn):hover, #'.$id.' a:not(.btn):active, #'.$id.' a:not(.btn):focus {color:'.$text_colour.';}';
        }
        if($border_colour <> ''){
            $html .= '#'.$id.' {border-color:'.$border_colour.';}';
        }
        $html .= '</style>';
    }
    return $html;
}