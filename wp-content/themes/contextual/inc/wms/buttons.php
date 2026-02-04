<?php
/**
 * WMS
 * Buttons - Adds bootstrap buttons
 *
 * Includes:
 * - 
 */

/**
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_shortcode('button', 'wms_button');

// eg [button type="primary" size="lg" link="http://..."]Read more[/button]
// output defaults to '<a href="#" class="btn btn-primary">...</a>'
function wms_button( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		"type"     => false,
		"size"     => false,
		"block"    => false,
		"dropdown" => false,
		"link"     => '',
		"target"   => false,
		"disabled" => false,
		"active"   => false,
		"xclass"   => false,
		"title"    => false,
		"data"     => false,
		"text"     => '',
	), $atts );
	// to allow for the old CC buttons, i've added an att of text and this ....
	if($content === null || $content == ''){
		$content = $atts['text'];
	}
	$class  = 'btn';
	$class .= ( $atts['type'] )     			? ' btn-' . $atts['type'] 	: ' btn-primary';
	$class .= ( $atts['size'] )     			? ' btn-' . $atts['size'] 	: '';
	$class .= ( $atts['block'] == 'true' )    	? ' btn-block' 				: '';
	$class .= ( $atts['dropdown'] == 'true' ) 	? ' dropdown-toggle' 		: '';
	$class .= ( $atts['disabled'] == 'true' ) 	? ' disabled' 				: '';
	$class .= ( $atts['active'] == 'true' )   	? ' active' 				: '';
	$class .= ( $atts['xclass'] )   			? ' ' . $atts['xclass'] 	: '';
	$data_props = wms_parse_data_attributes( $atts['data'] );
	return sprintf(
		'<a href="%s" class="%s"%s%s%s>%s</a>',
		esc_url( $atts['link'] ),
		esc_attr( trim($class) ),
		( $atts['target'] )     ? sprintf( ' target="%s"', esc_attr( $atts['target'] ) ) : '',
		( $atts['title'] )      ? sprintf( ' title="%s"',  esc_attr( $atts['title'] ) )  : '',
		( $data_props ) ? ' ' . $data_props : '',
		do_shortcode( $content )
	);
}
