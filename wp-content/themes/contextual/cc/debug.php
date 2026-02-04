<?php
/**
 * Error logging and similar
 */

if ( ! function_exists('ccpa_write_log')) {
	function ccpa_write_log ( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			$log = print_r( $log, true );
		}
		if(WP_DEBUG){
			error_log( $log );
		}else{
			cc_debug_logit($log);
		}
	}
}

// add or update the log table
add_action('init', 'cc_debug_log_table');
function cc_debug_log_table(){
	global $wpdb;
	// v1
	$cc_debug_log_db_ver = 1;
	$installed_table_ver = get_option('cc_debug_log_db_ver');
	if($installed_table_ver <> $cc_debug_log_db_ver){
		$debug_log_table = $wpdb->prefix.'debug_log';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $debug_log_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			ts timestamp DEFAULT CURRENT_TIMESTAMP,
			log longtext NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_debug_log_db_ver', $cc_debug_log_db_ver);
	}
}

// log it
function cc_debug_logit($log){
	global $wpdb;
	$debug_log_table = $wpdb->prefix.'debug_log';
	$wpdb->insert($debug_log_table, array('log' => $log));
}

// log anything
function cc_debug_log_anything( $log ){
    $output = array();
    
    // Add metadata
    $output['_meta'] = array(
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A',
        'user_id' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
        'timestamp' => current_time('mysql'),
        'type' => gettype($log)
    );
    
    // Add the actual data
    $output['_data'] = $log;
    
    // Convert everything to JSON for storage
    $json_output = wp_json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Fallback to serialization if JSON fails
    if ($json_output === false) {
        $json_output = maybe_serialize($output);
    }
    
    // Use your existing cc_debug_logit function
    cc_debug_logit($json_output);

    /*
	if ( is_array( $log ) || is_object( $log ) ) {
		$stuff = 'IP: '.$_SERVER['REMOTE_ADDR'].' | ';
		$stuff .= 'User: '.get_current_user_id().' | ';
		foreach ($log as $key => $value) {
			$stuff .= $key.': '.sanitize_text_field($value).' | ';
		}
		$log = $stuff;
	}else{
		$log = maybe_serialize($log);
	}
	cc_debug_logit($log);
	*/
}

// record all ajax calls
add_action('admin_init', 'cc_debug_admin_init');
function cc_debug_admin_init(){
	if( defined('DOING_AJAX') && DOING_AJAX ){
		$ignores = array( 'heartbeat', 'wp-remove-post-lock', 'save_video_stats', 'save_video_stats_update', 'cmp_save_cookie', 'blc_work' );
		if( ! isset( $_POST['action'] ) || in_array( $_POST['action'], $ignores ) ) return;
		$stuff = 'Ajax: ';
		$stuff .= 'IP: '.$_SERVER['REMOTE_ADDR'].' | ';
		$stuff .= 'User: '.get_current_user_id().' | ';
		$stuff .= 'Post data: ';
		foreach ($_POST as $key => $value) {
			$stuff .= $key.': '.sanitize_text_field($value).' | ';
		}
		cc_debug_logit($stuff);
	}
}

// ajax error log
// this will actually result in a second log entry because we're already logging all ajax calls above
add_action('wp_ajax_ajax_error_log', 'cc_debug_ajax_error_log');
add_action('wp_ajax_nopriv_ajax_error_log', 'cc_debug_ajax_error_log');
function cc_debug_ajax_error_log(){
	$stuff = 'Ajax ERROR: ';
	$stuff .= 'IP: '.$_SERVER['REMOTE_ADDR'].' | ';
	$stuff .= 'User: '.get_current_user_id().' | ';
	$stuff .= 'Post data: ';
	foreach ($_POST as $key => $value) {
		$stuff .= $key.': '.sanitize_text_field($value).' | ';
	}
	cc_debug_logit($stuff);
}

