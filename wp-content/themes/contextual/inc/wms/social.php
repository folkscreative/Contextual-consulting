<?php
/**
 * WMS
 * Social - social media buttons and links etc
 *
 * Includes:
 * - 
 */

/**
 * V1.8 Mar 2023
 * - updated to FA v6
 * V6.0 Mar 2021
 * - Google+ is no longer used (removed from config but not removed here ... but could be removed)
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

// returns the social media icons as links
// also used as a shortcode
add_shortcode('social_media_icons', 'wms_social_icons');
function wms_social_icons(){
	global $rpm_theme_options;
	$icons = wms_social_media_icons();
	$html = '';
	foreach ($icons as $icon_name => $fa_icon) {
		if($rpm_theme_options['social-'.$icon_name] <> ''){
			if(isset($rpm_theme_options['social-styling-type']) && $rpm_theme_options['social-styling-type'] == 'circles'){
				$html .= '<a href="'.wms_social_full_url($icon_name).'" target="_blank" rel="nofollow" class="social-link social-circles"><span class="fa-stack fa-2x"><i class="fas fa-circle fa-stack-2x"></i><i class="'.$fa_icon.' fa-stack-1x fa-inverse"></i></span></a>';
			}else{
				$html .= '<a href="'.wms_social_full_url($icon_name).'" target="_blank" rel="nofollow" class="social-link"><i class="'.$fa_icon.'"></i></a>';
			}
		}
	}
	if($html <> ''){
		$html = '<span class="social-icons">'.$html.'</span>';
	}
	return $html;
}

function wms_social_media_icons(){
	return array(
		'facebook' => 'fa-brands fa-facebook-f',
		'twitter' => 'fa-brands fa-x-twitter',
		'youtube' => 'fa-brands fa-youtube',
		'linkedin' => 'fa-brands fa-linkedin-in',
		'pinterest' => 'fa-brands fa-pinterest',
		'instagram' => 'fa-brands fa-instagram',
	);
}

function wms_social_required(){
	global $rpm_theme_options;
	if(    $rpm_theme_options['social-facebook'] <> ''
		|| $rpm_theme_options['social-twitter'] <> ''
		|| $rpm_theme_options['social-youtube'] <> ''
		|| $rpm_theme_options['social-linkedin'] <> ''
		|| $rpm_theme_options['social-pinterest'] <> ''
		|| $rpm_theme_options['social-instagram'] <> '' ){
		return true;
	}else{
		return false;
	}
}

// returns full social media URL
function wms_social_full_url($site){
	global $rpm_theme_options;
	$page = $rpm_theme_options['social-'.$site];
	if(substr($page, 0, 8) == 'https://' || substr($page, 0, 7) == 'http://'){
		return $page;
	}
	switch ($site) {
		case 'facebook':
			return 'https://www.facebook.com/'.$page;
			break;
		case 'twitter':
			return 'https://twitter.com/'.$page;
			break;
		case 'youtube':
			return 'https://youtube.com/@'.$page;
			break;
		case 'linkedin':
			return 'https://linkedin.com/company/'.$page;
			break;
		case 'pinterest':
			return 'https://pinterest.com/'.$page;
			break;
		case 'instagram':
			return 'https://instagram.com/'.$page;
			break;
	}
	return $page;
}

// returns a list of social media pages with their links
function wms_social_list(){
	global $rpm_theme_options;
	$icons = wms_social_media_icons();
	$html = '';
	foreach ($icons as $icon_name => $fa_icon) {
		if($rpm_theme_options['social-'.$icon_name] <> ''){
			if($html <> ''){
				$html .= '<br>';
			}
			$html .= '<i class="'.$fa_icon.' fa-fw"></i> <a href="'.wms_social_full_url($icon_name).'" target="_blank" rel="nofollow" class="social-link">@'.$rpm_theme_options['social-'.$icon_name].'</a>';
		}
	}
	return $html;
}