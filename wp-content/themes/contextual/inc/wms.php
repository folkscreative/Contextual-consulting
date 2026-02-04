<?php
/**
 *
 * Websites Made Smarter
 * v6
 *
 */

if(!defined('ABSPATH')) exit;

if(!defined('WMS_VER')){
	define('WMS_VER', '6');
}

// Contextual defaults
if(!defined('WMS_SECTION_ALIGN')){
	define('WMS_SECTION_ALIGN', 'left');
}
if(!defined('WMS_CUSTOM_SECT_PREFIX')){
	define('WMS_CUSTOM_SECT_PREFIX', 'contextual_wms_section_');
}

/**
 * Include the required WMS modules
 */
$poss_modules = array('core', 'plugins', 'page-slider', 'social', 'sections', 'buttons', 'font-awesome', 'typography', 'accordion', 'card', 'media', 'modal', 'widgets', 'background', 'logos', 'maps', 'capabilities', 'home', 'address', 'opening-hours', 'cta', 'team', 'break', 'contact', 'newsletter', 'content-export');
foreach ($poss_modules as $poss_module) {
	if(file_exists(get_stylesheet_directory().'/inc/wms/'.$poss_module.'.php')){
	    require get_stylesheet_directory().'/inc/wms/'.$poss_module.'.php';
	}
}


if(file_exists(get_stylesheet_directory().'/inc/wms/wms-metaboxes.php')){
    require get_stylesheet_directory().'/inc/wms/wms-metaboxes.php';
}
