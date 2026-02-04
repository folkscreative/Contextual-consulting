<?php
/**
 * WMS
 * Accordion
 *
 * Includes:
 * - 
 */

/**
 * v1.0.2 Dec 2020
 * - adjusted for Bootstrap v4.5
 * v5.0 12/1/19
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_shortcode('accordions', 'wms_accordion_accordions');
add_shortcode('accordion', 'wms_accordion_accordion');

// the outer function
// [accordions xclass=""][accordion]....[/accordions]
$accordion_id = '';
function wms_accordion_accordions($atts, $content){
    global $accordion_id;
    global $accordion_count;
    $a = shortcode_atts( array(
        'xclass' => '',
    ), $atts );
    $xclass = "{$a['xclass']}";
    $accordion_id = 'wms-accorion-'.mt_rand();
    $html = '<div id="'.$accordion_id.'" class="accordion '.$xclass.'">';
    $html .= do_shortcode($content);
    $html .= '</div>';
    $accordion_count = 0;
    return $html;
}

// each accordion section
// [accordion open="yes" heading="xxx" xclass=""]...[/accordion]
$accordion_count = 0;
function wms_accordion_accordion($atts, $content){
    global $accordion_id;
    global $accordion_count;
    $a = shortcode_atts( array(
        'heading' => '',
        'open' => '',
        'xclass' => '',
    ), $atts );
    $heading = "{$a['heading']}";
    $open = "{$a['open']}";
    if($open == 'yes'){
        $btn_class = '';
        $body_class = 'show';
    }else{
        $btn_class = 'collapsed';
        $body_class = '';
    }
    $xclass = "{$a['xclass']}";
    $accordion_count++;
    $this_id = $accordion_id.'-'.$accordion_count;
    $body_id = $this_id.'-body';
    $html = '<div class="card '.$xclass.'"><div class="card-header" id="'.$this_id.'"><h5 class="mb-0"><button class="btn btn-link btn-block text-start btn-accordion '.$btn_class.'" data-toggle="collapse" data-target="#'.$body_id.'">';
    $html .= $heading;
    $html .= '</button></h5></div>';
    $html .= '<div id="'.$body_id.'" class="collapse '.$body_class.'" data-parent="#'.$accordion_id.'"><div class="card-body">';
    $html .= do_shortcode($content);
    $html .= '</div></div></div>';
    return $html;
}