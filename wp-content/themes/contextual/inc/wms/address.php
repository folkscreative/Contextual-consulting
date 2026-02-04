<?php
/**
 * Address
 */

// formats an address
// separator of '<br>' creates a multi-line address, separator of ', ' creates a single line one
function wms_address_formatted($addr_num=1, $separator='<br>'){
	global $rpm_theme_options;
	$address = '';
	$addr_fields = array('l1', 'l2', 'town', 'county', 'postcode');
	foreach ($addr_fields as $addr_field) {
		$key = 'addr'.$addr_num.'-'.$addr_field;
		if($rpm_theme_options[$key] <> ''){
			if($address <> ''){
				$address .= $separator;
			}
			$address .= $rpm_theme_options[$key];
		}
	}
	return $address;
}

// [address address="1" multiline="yes"]
add_shortcode('address', 'wms_address_shortcode');
function wms_address_shortcode($atts){
	$atts = shortcode_atts( array(
		'address' => 1,
		'multiline' => 'yes',
	), $atts, 'address' );
	$address = "{$atts['address']}";
	$multiline = "{$atts['multiline']}";
	if($multiline == 'yes'){
		$separator = '<br>';
	}else{
		$separator = ', ';
	}
	return wms_address_formatted($address, $separator);
}