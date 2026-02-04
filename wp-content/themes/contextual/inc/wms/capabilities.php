<?php
/**
 * WMS
 * Capabilities
 *
 * Adjusts roles and capabilities as needed
 */

/**
 * v6.0 Feb 2021
 * - new
 */

if(!defined('ABSPATH')) exit;

// Add capabilities, priority must be after the initial role definition.
add_action( 'admin_init', 'wms_capabilities_setup_rpm_admin' );

function wms_capabilities_setup_rpm_admin() {
	$user = new WP_User( 'RPM Admin' );
	if(!$user->has_cap('rpm_admin')){
		$user->add_cap('rpm_admin');
	}
}
 
