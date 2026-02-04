<?php
/**
 * WMS
 * Modals - Adds bootstrap modal windows
 *
 * Includes:
 * - 
 */

/**
 * v5.0 7/2/19
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_shortcode('modal', 'wms_modal');

// [modal text="" title="" size="" xclass="" data="" button="white"]... content ... [/modal]
function wms_modal( $atts, $content = null ) {
	static $modal_count = 0;
	$modal_count ++;
	$atts = shortcode_atts( array(
			"text"    => false,
			"title"   => false,
			"size"    => false,
			"xclass"  => false,
			"data"    => false,
			"button"    => 'white',
	), $atts );
	$a_class  = '';
	if($atts['button'] <> ''){
		$a_class .= 'btn btn-'.$atts['button'].' ';
	}
	$a_class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';
	$div_class  = 'modal fade';
	$div_class .= ( $atts['size'] ) ? ' bs-modal-' . $atts['size'] : '';
	$div_size = ( $atts['size'] ) ? ' modal-' . $atts['size'] : '';
	$id = 'custom-modal-' . $modal_count;
	$data_props = wms_parse_data_attributes( $atts['data'] );
	$modal_output = sprintf(
			'<div class="%1$s" id="%2$s" tabindex="-1" role="dialog" aria-hidden="true">
					<div class="modal-dialog %3$s">
							<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="">%4$s</h5>
										<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
									</div>
									<div class="modal-body">
											%5$s
									</div>
							</div> <!-- /.modal-content -->
					</div> <!-- /.modal-dialog -->
			</div> <!-- /.modal -->
			',
		esc_attr( $div_class ),
		esc_attr( $id ),
		esc_attr( $div_size ),
		( $atts['title'] ) ? '<h4 class="modal-title">' . $atts['title'] . '</h4>' : '',
		do_shortcode( $content )
	);
	add_action('wp_footer', function() use ($modal_output) {
			echo $modal_output;
	}, 100,0);
	return sprintf(
		'<p><a data-toggle="modal" href="#%1$s" class="%2$s"%3$s>%4$s</a></p>',
		esc_attr( $id ),
		esc_attr( $a_class ),
		( $data_props ) ? ' ' . $data_props : '',
		esc_html( $atts['text'] )
	);
}
