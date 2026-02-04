<?php
/**
 * Call to Action (CTA) shortcode
 */

add_shortcode('cta', 'wms_cta_shortcode');
function wms_cta_shortcode(){
	global $rpm_theme_options;
	return do_shortcode($rpm_theme_options['cta']);
}