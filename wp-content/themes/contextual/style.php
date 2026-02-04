<?php
/**
 * applies the styles set in the options panel
 */

function wms_dynamic_css(){
	global $rpm_theme_options;
	$CSS = '';

	// Fonts
	/*
	$CSS .= "body {font-family:'".$rpm_theme_options['fonts-body']['font-family']."'; font-size:".$rpm_theme_options['fonts-body']['font-size']."; line-height:".$rpm_theme_options['fonts-body']['line-height']."; font-weight:".$rpm_theme_options['fonts-body']['font-weight']."; font-style:".$rpm_theme_options['fonts-body']['font-style']."; color:".$rpm_theme_options['fonts-body']['color'].";}\n";
	$CSS .= ".large {font-size:".wms_bigger_font($rpm_theme_options['fonts-body']['font-size']).";}\n";
	if($rpm_theme_options['fonts-headings']['font-family'] == 'inherit' || $rpm_theme_options['fonts-headings']['font-family'] == ''){
		$headings_font = 'inherit';
	}else{
		$headings_font = "'".$rpm_theme_options['fonts-headings']['font-family']."'";
	}
	$CSS .= ".h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {font-family:".$headings_font."; font-weight:".$rpm_theme_options['fonts-headings']['font-weight']."; font-style:".$rpm_theme_options['fonts-headings']['font-style']."; color:".$rpm_theme_options['fonts-headings']['color']."; text-transform:".$rpm_theme_options['fonts-headings']['text-transform'].";}\n";
	$CSS .= ".h1, h1 {font-size:".$rpm_theme_options['fonts-h1']['font-size']."; line-height:".$rpm_theme_options['fonts-h1']['line-height']."; font-weight:".$rpm_theme_options['fonts-h1']['font-weight']."; font-style:".$rpm_theme_options['fonts-h1']['font-style'].";}\n";
	$CSS .= ".h2, h2 {font-size:".$rpm_theme_options['fonts-h2']['font-size']."; line-height:".$rpm_theme_options['fonts-h2']['line-height']."; font-weight:".$rpm_theme_options['fonts-h2']['font-weight']."; font-style:".$rpm_theme_options['fonts-h2']['font-style'].";}\n";
	$CSS .= ".h3, h3 {font-size:".$rpm_theme_options['fonts-h3']['font-size']."; line-height:".$rpm_theme_options['fonts-h3']['line-height']."; font-weight:".$rpm_theme_options['fonts-h3']['font-weight']."; font-style:".$rpm_theme_options['fonts-h3']['font-style'].";}\n";
	$CSS .= ".h4, h4 {font-size:".$rpm_theme_options['fonts-h4']['font-size']."; line-height:".$rpm_theme_options['fonts-h4']['line-height']."; font-weight:".$rpm_theme_options['fonts-h4']['font-weight']."; font-style:".$rpm_theme_options['fonts-h4']['font-style'].";}\n";
	$CSS .= ".h5, h5 {font-size:".$rpm_theme_options['fonts-h5']['font-size']."; line-height:".$rpm_theme_options['fonts-h5']['line-height']."; font-weight:".$rpm_theme_options['fonts-h5']['font-weight']."; font-style:".$rpm_theme_options['fonts-h5']['font-style'].";}\n";
	$CSS .= ".h6, h6 {font-size:".$rpm_theme_options['fonts-h6']['font-size']."; line-height:".$rpm_theme_options['fonts-h6']['line-height']."; font-weight:".$rpm_theme_options['fonts-h6']['font-weight']."; font-style:".$rpm_theme_options['fonts-h6']['font-style'].";}\n";
	$CSS .= ".large .h1, .large h1 {font-size:".wms_bigger_font($rpm_theme_options['fonts-h1']['font-size'])."; line-height:".wms_bigger_font($rpm_theme_options['fonts-h1']['line-height']).";}\n";
	$CSS .= ".large .h2, .large h2 {font-size:".wms_bigger_font($rpm_theme_options['fonts-h2']['font-size'])."; line-height:".wms_bigger_font($rpm_theme_options['fonts-h2']['line-height']).";}\n";
	$CSS .= ".large .h3, .large h3 {font-size:".wms_bigger_font($rpm_theme_options['fonts-h3']['font-size'])."; line-height:".wms_bigger_font($rpm_theme_options['fonts-h3']['line-height']).";}\n";
	$CSS .= ".large .h4, .large h4 {font-size:".wms_bigger_font($rpm_theme_options['fonts-h4']['font-size'])."; line-height:".wms_bigger_font($rpm_theme_options['fonts-h4']['line-height']).";}\n";
	$CSS .= ".large .h5, .large h5 {font-size:".wms_bigger_font($rpm_theme_options['fonts-h5']['font-size'])."; line-height:".wms_bigger_font($rpm_theme_options['fonts-h5']['line-height']).";}\n";
	$CSS .= ".large .h6, .large h6 {font-size:".wms_bigger_font($rpm_theme_options['fonts-h6']['font-size'])."; line-height:".wms_bigger_font($rpm_theme_options['fonts-h6']['line-height']).";}\n";
	$CSS .= ".lead {font-size:".$rpm_theme_options['fonts-lead']['font-size']."; line-height:".$rpm_theme_options['fonts-lead']['line-height']."; font-weight:".$rpm_theme_options['fonts-lead']['font-weight']."; font-style:".$rpm_theme_options['fonts-lead']['font-style'].";}\n";
	*/

	// Links
	/*
	$CSS .= "a, a:visited {color:".$rpm_theme_options['fonts-links']['regular'].";}\n";
	$CSS .= "a:focus, a:hover {color:".$rpm_theme_options['fonts-links']['hover'].";}\n";
	$CSS .= "a:active {color:".$rpm_theme_options['fonts-links']['active'].";}\n";
	*/

	// Message Bar
	/*
	$CSS .= ".messagebar {background:".$rpm_theme_options['messagebar-bg']."; color:".$rpm_theme_options['messagebar-text'].";}\n";
	$CSS .= ".messagebar a, .messagebar a:visited {color:".$rpm_theme_options['messagebar-link']['regular'].";}\n";
	$CSS .= ".messagebar a:focus, .messagebar a:hover {color:".$rpm_theme_options['messagebar-link']['hover'].";}\n";
	$CSS .= ".messagebar a:active {color:".$rpm_theme_options['messagebar-link']['active'].";}\n";
	*/
	// header & menu
	// $CSS .= ".site-header {background-image: linear-gradient(".wms_hex2rgba($rpm_theme_options['header-bg'], 1).", ".wms_hex2rgba($rpm_theme_options['header-bg'], 0.05)."); color:".$rpm_theme_options['header-text'].";}\n";
	// $CSS .= ".topbar, .topbar .header-item {border-color:".$rpm_theme_options['topbar-border'].";}\n";
	// $CSS .= ".site-header.shrink, .site-menu-col {background-image: linear-gradient(".wms_hex2rgba($rpm_theme_options['header-bg'], 1).", ".wms_hex2rgba($rpm_theme_options['header-bg'], 1).");}\n";
	// if($rpm_theme_options['header-icons-font']['font-size'] <> 'inheritpx' && $rpm_theme_options['header-icons-font']['font-size'] <> ''){
	// 	$CSS .= ".header-icon {font-size:".$rpm_theme_options['header-icons-font']['font-size']."; line-height:1;}\n";
	// }
	// if($rpm_theme_options['header-phone-font']['font-size'] <> 'inheritpx' && $rpm_theme_options['header-phone-font']['font-size'] <> ''){
	// 	$CSS .= ".topbar .phone-number {font-size:".$rpm_theme_options['header-phone-font']['font-size']."; line-height:1;}\n";
	// }
	// $CSS .= ".dropdown-menu {background:".$rpm_theme_options['header-bg']."; color:".$rpm_theme_options['header-text'].";}\n";
	// $CSS .= ".site-header .phone {color:".$rpm_theme_options['header-text'].";}\n";
	// $CSS .= ".site-header a, .site-header a:visited {color:".$rpm_theme_options['header-link-colour']['regular'].";}\n";
	// $CSS .= ".site-header a:hover, .site-header a:focus {color:".$rpm_theme_options['header-link-colour']['hover'].";}\n";
	// $CSS .= ".site-header a:active {color:".$rpm_theme_options['header-link-colour']['active'].";}\n";

	// $CSS .= ".site-header .nav-link, .site-header .nav-link:visited {color:".$rpm_theme_options['menu-text']['regular'].";}\n";
	// $CSS .= ".site-header .nav-link:hover, .site-header .nav-link:focus {color:".$rpm_theme_options['menu-text']['hover'].";}\n";
	// $CSS .= ".site-header .nav-link:active {color:".$rpm_theme_options['menu-text']['active'].";}\n";
	// $CSS .= ".site-menu .navbar-nav .menu-item {border-color:".$rpm_theme_options['topbar-border'].";}\n";

	// Carousel & featured images
	/*
	if(is_front_page()){
	}else{
		$slide_height = $rpm_theme_options['slideshow-ht-lg']['height'];
		$CSS .= "#home-slideshow .carousel-inner .item, .wp-post-image .item, .google-map .canvas.feat-map {height:".$slide_height."px;}\n";
		$slide_height = $rpm_theme_options['slideshow-ht-md']['height'];
		$CSS .= "@media only screen and (max-width: 1200px) {\n";
		$CSS .= "#home-slideshow .carousel-inner .item, .wp-post-image .item, .google-map .canvas.feat-map {height:".$slide_height."px;}\n";
		$CSS .= "}\n";
		$slide_height = $rpm_theme_options['slideshow-ht-sm']['height'];
		$CSS .= "@media only screen and (max-width: 992px) {\n";
		$CSS .= "#home-slideshow .carousel-inner .item, .wp-post-image .item, .google-map .canvas.feat-map {height:".$slide_height."px;}\n";
		$CSS .= "}\n";
		$slide_height = $rpm_theme_options['slideshow-ht-xs']['height'];
		$CSS .= "@media only screen and (max-width: 768px) {\n";
		$CSS .= "#home-slideshow .carousel-inner .item, .wp-post-image .item, .google-map .canvas.feat-map {height:".$slide_height."px;}\n";
		$CSS .= "}\n";
	}
	*/

	// if($rpm_theme_options['slideshow-bg-size'] == 'slide'){
	// 	$CSS .= "#home-slideshow .item-overlay, .wp-post-image .item-overlay {background:".$rpm_theme_options['slideshow-text-bg']['rgba'].";}\n";
	// }else{
	// 	$CSS .= "#home-slideshow .carousel-caption, .wp-post-image .carousel-caption {background:".$rpm_theme_options['slideshow-text-bg']['rgba'].";}\n";
	// }

	// if($rpm_theme_options['slideshow-text-pos'] == 'left'){
	// 	$CSS .= "#home-slideshow .carousel-caption, .wp-post-image .carousel-caption {max-width:550px; text-align:".$rpm_theme_options['slideshow-text-pos'].";}\n";
	// }

	/*
	if($rpm_theme_options['slideshow-heading']['font-family'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {font-family:".$rpm_theme_options['slideshow-heading']['font-family'].";}\n";
	}
	if($rpm_theme_options['slideshow-heading']['font-style'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {font-style:".$rpm_theme_options['slideshow-heading']['font-style'].";}\n";
	}
	if($rpm_theme_options['slideshow-heading']['color'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {color:".$rpm_theme_options['slideshow-heading']['color'].";}\n";
	}
	if($rpm_theme_options['slideshow-heading']['font-size'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {font-size:".$rpm_theme_options['slideshow-heading']['font-size'].";}\n";
	}
	if($rpm_theme_options['slideshow-heading']['line-height'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {line-height:".$rpm_theme_options['slideshow-heading']['line-height'].";}\n";
	}
	if($rpm_theme_options['slideshow-heading']['font-weight'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {font-weight:".$rpm_theme_options['slideshow-heading']['font-weight'].";}\n";
	}

	if($rpm_theme_options['slideshow-text']['font-family'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption p, .wp-post-image .carousel-caption p {font-family:".$rpm_theme_options['slideshow-text']['font-family'].";}\n";
	}
	if($rpm_theme_options['slideshow-text']['font-style'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption p, .wp-post-image .carousel-caption p {font-style:".$rpm_theme_options['slideshow-text']['font-style'].";}\n";
	}
	if($rpm_theme_options['slideshow-text']['color'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption p, .wp-post-image .carousel-caption p {color:".$rpm_theme_options['slideshow-text']['color'].";}\n";
	}
	if($rpm_theme_options['slideshow-text']['font-size'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption p, .wp-post-image .carousel-caption p {font-size:".$rpm_theme_options['slideshow-text']['font-size'].";}\n";
	}
	if($rpm_theme_options['slideshow-text']['line-height'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption p, .wp-post-image .carousel-caption p {line-height:".$rpm_theme_options['slideshow-text']['line-height'].";}\n";
	}
	if($rpm_theme_options['slideshow-text']['font-weight'] <> ''){
		$CSS .= "#home-slideshow-wrap .carousel-caption p, .wp-post-image .carousel-caption p {font-weight:".$rpm_theme_options['slideshow-text']['font-weight'].";}\n";
	}
	*/
	/*
	if($rpm_theme_options['slideshow-mobile'] <> '' && $rpm_theme_options['slideshow-mobile'] <> 'all'){
		$CSS .= "@media only screen and (max-width: 768px) {\n";
		if($rpm_theme_options['slideshow-mobile'] == 'none'){
			$CSS .= "#home-slideshow-wrap {display:none;}\n";
		}elseif($rpm_theme_options['slideshow-mobile'] == 'img') {
			$CSS .= "#home-slideshow-wrap .item-overlay {display:none;}\n";
		}elseif($rpm_theme_options['slideshow-mobile'] == 'imghead'){
			$CSS .= "#home-slideshow-wrap .carousel-caption p {display:none;}\n";
		}
		$CSS .= "}\n";
	}

	if($rpm_theme_options['slideshow-mobile-heading']['font-size'] <> ''){
		$CSS .= "@media only screen and (max-width: 768px) {\n";
		$CSS .= "#home-slideshow-wrap .carousel-caption h1, .wp-post-image .carousel-caption h1 {font-size:".$rpm_theme_options['slideshow-mobile-heading']['font-size']."; line-height:".$rpm_theme_options['slideshow-mobile-heading']['line-height'].";}\n";
		$CSS .= "}\n";
	}
	*/

	// panels and accordions and default alerts
	/*
	$CSS .= ".panel-body, .wms-quote {background:".$rpm_theme_options['panel-bg']."; color:".$rpm_theme_options['panel-text'].";}\n";
	$CSS .= ".panel-body strong, .wms-quote strong {color:".$rpm_theme_options['panel-emphasis'].";}\n";
	$CSS .= ".wms-quote cite {color:".wms_adjust_brightness($rpm_theme_options['panel-text'],50).";}\n";
	$CSS .= ".panel-default > .panel-heading {background:".$rpm_theme_options['accordion-header-bg']['regular']."; color:".$rpm_theme_options['accordion-header-text']['regular'].";}\n";
	$CSS .= ".panel-default.active > .panel-heading {background:".$rpm_theme_options['accordion-header-bg']['active']."; color:".$rpm_theme_options['accordion-header-text']['active'].";}\n";
	$CSS .= ".panel-default > .panel-heading:hover {background:".$rpm_theme_options['accordion-header-bg']['hover']."; color:".$rpm_theme_options['accordion-header-text']['hover'].";}\n";
	$CSS .= ".panel-default > .panel-heading a:hover, .panel-default > .panel-heading a:focus {color:".$rpm_theme_options['accordion-header-text']['hover'].";}\n";
	$CSS .= ".alert-default {background:".$rpm_theme_options['panel-bg']."; border-color:".$rpm_theme_options['panel-emphasis']."; color:".$rpm_theme_options['panel-text'].";}\n";
	*/

	// FAQ background image
	$linear_gradient = '';
	$faq_img = '';
	if($rpm_theme_options['faq-wash']['rgba'] <> ''){
		$linear_gradient = 'linear-gradient('.$rpm_theme_options['faq-wash']['rgba'].', '.$rpm_theme_options['faq-wash']['rgba'].')';
	}
	if($rpm_theme_options['faq-img']['url'] <> ''){
		$faq_img = 'url('.$rpm_theme_options['faq-img']['url'].')';
	}
	if($linear_gradient <> '' || $faq_img <> ''){
		$CSS .= ".workshop-faqs-wrap .wms-sect-bg {background-image:".$linear_gradient;
		if($linear_gradient <> '' && $faq_img <> ''){
			$CSS .= ', ';
		}
		$CSS .= $faq_img."}\n";
	}

	// Footer
	/*
	$CSS .= "#colophon {background:".$rpm_theme_options['footer-bg']."; color:".$rpm_theme_options['footer-text'].";} \n";
	$CSS .= "#colophon h1, #colophon h2, #colophon h3, #colophon h4, #colophon h5, #colophon h6 {color:".$rpm_theme_options['footer-text'].";} \n";
	$CSS .= "#colophon a:not(.btn), #colophon a:not(.btn):visited {color:".$rpm_theme_options['footer-text'].";} \n";
	$CSS .= "#colophon a:not(.btn):hover, #colophon a:not(.btn):active, #colophon a:not(.btn):focus {color:".wms_adjust_brightness($rpm_theme_options['footer-text'], -50).";}\n";
	$CSS .= ".sub-footer {background:".$rpm_theme_options['sub-footer-bg']."; color:".$rpm_theme_options['sub-footer-text'].";} \n";
	$CSS .= ".sub-footer h1, .sub-footer h2, .sub-footer h3, .sub-footer h4, .sub-footer h5, .sub-footer h6 {color:".$rpm_theme_options['sub-footer-text'].";} \n";
	$CSS .= ".sub-footer a, .sub-footer a {color:".$rpm_theme_options['sub-footer-text'].";} \n";
	$CSS .= ".sub-footer a:hover {color:".wms_adjust_brightness($rpm_theme_options['sub-footer-text'], -50).";}\n";
	$CSS .= ".seo-footer-wrapper {background:".$rpm_theme_options['seo-footer-bg']."; color:".$rpm_theme_options['seo-footer-text'].";} \n";
	$CSS .= ".seo-footer-wrapper h1, .seo-footer-wrapper h2, .seo-footer-wrapper h3, .seo-footer-wrapper h4, .seo-footer-wrapper h5, .seo-footer-wrapper h6 {color:".$rpm_theme_options['seo-footer-text'].";} \n";
	$CSS .= ".seo-footer-wrapper a, .seo-footer-wrapper a {color:".$rpm_theme_options['seo-footer-text'].";} \n";
	$CSS .= ".seo-footer-wrapper a:hover {color:".wms_adjust_brightness($rpm_theme_options['seo-footer-text'], -50).";}\n";
	$CSS .= ".footer-keywords li {border-right: 1px solid ".$rpm_theme_options['seo-footer-text'].";}\n";
	*/

	return $CSS;
}
