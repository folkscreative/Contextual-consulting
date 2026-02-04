<?php
/*
Plugin Name: Mailster - Email Newsletter Plugin for WordPress
Plugin URI: https://mailster.co
Description: #### Modified for Contextual Consulting - DO NOT UPDATE ##### Send Beautiful Email Newsletters in WordPress.
Version: 3.3.2x5
Author: EverPress
Author URI: https://everpress.co
Text Domain: mailster
*/

/**
 * NOTE: version number included with comments where all changes made
 * 
 * v3.3.2x5 Contextual Consulting Sep 2025
 * - workaround for the subscriber get_by_mail out of memory problem
 * v3.3.2x4 Contextual Consulting Jun 2025
 * - added properties to the subscriber.query.class to prevent errors from PHP v8.3
 * v3.3.2x3 Contextual Consulting Apr 2025
 * - added a few extra font in to the campaigns.class so that the default fonts show correctly in the Tinymce options
 * v3.3.2x2 Contextual Consulting Mar 2025
 * - subscriber search is running out of memory if the email is not found. Tried fixing the code in subscriber.query.class but that did not do it .... have to return to this later :-(
 * v3.3.2x1 Contextual Consulting May 2023
 * - fixed bug where autoresponder hook being saved in array when it should not be (two changes to campaigns.class.php)
 * v3.3.2x0 Contextual Consulting Mar 2023
 * - based on Mailster v3.3.2
 * - incorporated all the below mods
 * - also incorporated the newsletter list performance upgrade views > lists > detail.php around line 150
 * - fixed the bug that stopped the fontsizeselect not being shown in classes > campaigns.class.php > iframe_script_styles
 * - plus, of course, the licence mods:
 * -- classes > mailster.class.php > is_verified returns true
 * -- classes > UpdateCenterPlugin.php > verify returns true
 * -- classes > convert.class.php > notice returns
 * -- classes > forms.class.php > block_forms_message returns
 * v3.0.4x0 Contextual Consulting Dec 2021
 * - based on Mailster v3.0.4
 * - added changes made in the v2.4.3 versions up to x5 as listed here ...
 * v2.4.3.x5 Jan 2021
 * - php 7.4: Deprecated: Nested ternary operators without explicit parentheses - see classes/ajax-class.php around line 245 - now fixed in base code
 * v2.4.3.x4 Oct 2020
 * - adds extra folders to the lists in the same manner as the archived lists
 * v2.4.3.x3 Sep 2020
 * - allows for mailster lists to be archived and then not shown on the list of lists
 * v2.4.3.x2 Jul 2020
 * - now accommodates an extra activity when a registration email is re-sent - no longer needed after Mailster v3
 * v2.4.3.x1 May 2020
 * - Unsubscribes now only removes people from the maim newsletter list - see Subscribers Class unsubscribe_by_type
 */


if ( defined( 'MAILSTER_VERSION' ) || ! defined( 'ABSPATH' ) ) {
	return;
}

define( 'MAILSTER_VERSION', '3.3.2' );
define( 'MAILSTER_BUILT', 1679570226 );
define( 'MAILSTER_ENVATO', true );
define( 'MAILSTER_DBVERSION', 20220727 );
define( 'MAILSTER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAILSTER_URI', plugin_dir_url( __FILE__ ) );
define( 'MAILSTER_FILE', __FILE__ );
define( 'MAILSTER_SLUG', basename( MAILSTER_DIR ) . '/' . basename( __FILE__ ) );

$upload_folder = wp_upload_dir();

if ( ! defined( 'MAILSTER_UPLOAD_DIR' ) ) {
	define( 'MAILSTER_UPLOAD_DIR', trailingslashit( $upload_folder['basedir'] ) . 'mailster' );
}
if ( ! defined( 'MAILSTER_UPLOAD_URI' ) ) {
	define( 'MAILSTER_UPLOAD_URI', trailingslashit( $upload_folder['baseurl'] ) . 'mailster' );
}

require_once MAILSTER_DIR . 'vendor/autoload.php';
require_once MAILSTER_DIR . 'includes/check.php';
require_once MAILSTER_DIR . 'includes/functions.php';
require_once MAILSTER_DIR . 'includes/freemius.php';
require_once MAILSTER_DIR . 'includes/deprecated.php';
require_once MAILSTER_DIR . 'includes/3rdparty.php';
require_once MAILSTER_DIR . 'classes/mailster.class.php';

global $mailster;

$mailster = new Mailster();

if ( ! $mailster->wp_mail && mailster_option( 'system_mail' ) == 1 ) {

	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array(), $file = null, $template = null ) {
		return mailster()->wp_mail( $to, $subject, $message, $headers, $attachments, $file, $template );
	}
}
