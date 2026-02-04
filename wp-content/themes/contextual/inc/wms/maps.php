<?php
/**
 * Maps shortcodes
 */

/**
 * v1.0.3 Dec 2020
 * - added
 */

add_shortcode('map', 'wms_map');
add_shortcode('placemark', 'wms_placemark');

// shortcode to add a Google Map to a page [map] 
// show_marker added for Contextual Consulting
// or with parameters ... [map location="1" name="Business Name" address="23 High Street, East Cheam" lat="51.234567" lon="0.123456" zoom="15" class="third" directions="yes"]
// optional parameters override the theme options settings
// location can be 1 or 2 and if present, it will use as a "base" which can then be overriden by the "manual" settings
// now with added placemark sub-shortcodes too ...
// [map ...][placemark name="Farmers Market" latlng="50.852572,-1.177751" info="West Street 9am-2pm first Saturday of the month. Call 01329 824474 for more details."][placemark ...][/map]
function wms_map($atts, $placemarks = null){
	global $rpm_theme_options;
	$GLOBALS['placemark_count'] = 0;
	do_shortcode($placemarks);
	extract( shortcode_atts( array(
		'location' => '0',
		'name' => '',
		'address' => '',
		'lat' => '',
		'lon' => '',
		'zoom' => '',
		'class' => '',
		'directions' => 'yes',
		'show_marker' => 'yes',
	), $atts ) );
	if($location == '0' || $location == '1'){
		$business_name = $rpm_theme_options['location-name'];
		$address_line = wms_address_formatted(1, ', ');
		$latitude = $rpm_theme_options['latitude'];
		$longitude = $rpm_theme_options['longitude'];
		$map_zoom = $rpm_theme_options['map-zoom'];
	}elseif ($location == '2') {
		$business_name = $rpm_theme_options['location-name-2'];
		$address_line = wms_address_formatted(2, ', ');
		$latitude = $rpm_theme_options['latitude-2'];
		$longitude = $rpm_theme_options['longitude-2'];
		$map_zoom = $rpm_theme_options['map-zoom-2'];
	}
	if($name <> '') $business_name = $name;
	if($address <> '') $address_line = $address;
	if($lat <> '') $latitude = $lat;
	if($lon <> '') $longitude = $lon;
	if($zoom <> '') $map_zoom = $zoom;
	if($business_name == '') $business_name = $rpm_theme_options['business-name'];
	$map_num = 'map_canvas_'.rand(1000,9999);
	$contentString = '<div id="content"><h6 id="gMapHeading" class="gMapHeading mb-1">'.$business_name.'</h6><div id="gMapContent"><p>'.$address_line.'</p></div></div>';
	$HTML = '<div class="google-map '.$class.'">';
	$HTML .= '<div id="map_canvas_'.$map_num.'" class="canvas"></div>';
	$HTML .= '<script>';
	$HTML .= 'var myLatlng = new google.maps.LatLng('.$latitude.','.$longitude.');';
	$map_style = $rpm_theme_options['map-style'];
	if($map_style == 'desat'){
		$map_styling = ', styles: [{stylers: [{saturation: -100}] }]';
	}else{
		$map_styling = '';
	}
	$HTML .= 'var mapOptions = {zoom: '.$map_zoom.', center: myLatlng, mapTypeId: google.maps.MapTypeId.ROADMAP, scrollwheel: false'.$map_styling.'};';
	$HTML .= 'var map'.$map_num.' = new google.maps.Map(document.getElementById("map_canvas_'.$map_num.'"), mapOptions);';
	if($show_marker == 'yes'){
		$HTML .= 'var contentString'.$map_num.' = \''.$contentString.'\';';
		$HTML .= 'var infowindow'.$map_num.' = new google.maps.InfoWindow({content: contentString'.$map_num.', maxWidth: 200 });';
		$HTML .= 'var marker'.$map_num.' = new google.maps.Marker({position: myLatlng, map: map'.$map_num.', title: \''.$business_name.'\'});';
		$HTML .= 'google.maps.event.addListener(marker'.$map_num.', "click", function() {infowindow'.$map_num.'.open(map'.$map_num.',marker'.$map_num.');});';
	}
	if(isset($GLOBALS['placemarks']) && is_array($GLOBALS['placemarks'])){
	    $gplacemarker_num = 0;
	    foreach ($GLOBALS['placemarks'] as $placemark) {
			$HTML .= 'var contentString'.$map_num.$gplacemarker_num.' = \'<div><h6 class="gMapHeading mb-1">'.$placemark['name'].'</h6><div><p>'.$placemark['info'].'</p></div></div>\';';
			$HTML .= 'var infowindow'.$map_num.$gplacemarker_num.' = new google.maps.InfoWindow({content: contentString'.$map_num.$gplacemarker_num.', maxWidth: 200 });';
			$HTML .= 'var myLatlng'.$gplacemarker_num.' = new google.maps.LatLng('.$placemark['latlng'].');';
			$HTML .= 'var marker'.$map_num.$gplacemarker_num.' = new google.maps.Marker({position: myLatlng'.$gplacemarker_num.', map: map'.$map_num.', title: \''.$placemark['name'].'\'});';
			$HTML .= 'google.maps.event.addListener(marker'.$map_num.$gplacemarker_num.', "click", function() {infowindow'.$map_num.$gplacemarker_num.'.open(map'.$map_num.',marker'.$map_num.$gplacemarker_num.');});';
			$gplacemarker_num++;
		}
	}
	$HTML .= '</script>';
	$HTML .= '</div>';
	if($directions == 'yes'){
		$HTML .= '<div class="google-directions"><a href="https://maps.google.com/maps?daddr='.$latitude.','.$longitude.'&hl=en&mra=ltm&t=m&z=17" target="_blank" title="opens in new window">Get Directions</a></div>';
	}
	return $HTML;
}

// the placemark shortcode used within the map shortcode above
function wms_placemark($atts){
	$a = shortcode_atts( array(
		'name' => '',
		'latlng' => '',
		'info' => ''
	), $atts);
	$i = $GLOBALS['placemark_count'];
	$GLOBALS['placemarks'][$i] = array(
		'name' => "{$a['name']}",
		'latlng' => "{$a['latlng']}",
		'info' => "{$a['info']}"
	);
	$GLOBALS['placemark_count'] ++;
}
