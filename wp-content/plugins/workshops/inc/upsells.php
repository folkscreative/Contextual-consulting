<?php
/**
 * Upsells
 */

add_action('plugins_loaded', 'cc_upsells_database_updates');
function cc_upsells_database_updates(){
	$prev_plugin_ver = get_option('cc_workshops_prev_ver');
	if(CC_WORKSHOPS_VER == $prev_plugin_ver) return;
	// ok we have a new plugin version, therefore we may have table updates to perform
	global $wpdb;
	$upsells_db_ver = 1;
	$installed_table_ver = get_option('upsells_db_ver');
	if($installed_table_ver <> $upsells_db_ver){
		$table_name = $wpdb->prefix.'upsells';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			workshop_1_id mediumint(9) NOT NULL,
			workshop_2_id mediumint(9) NOT NULL,
			discount decimal(15,4) NOT NULL,
			expiry datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('upsells_db_ver', $upsells_db_ver);
	}
}

// removes an upsell
function cc_upsells_remove_upsell($workshop_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'upsells';
	$upsell = cc_upsells_get_upsell($workshop_id);
	if($upsell !== NULL){
		$where = array(
			'id' => $upsell['id'],
		);
		return $wpdb->delete( $table_name, $where);
	}	
}

// gets the workshop from the title
// if there are multiple workshops with the same title, it gets the one with the latest date
// based on WP's get_page_by_title but also checks for the latest workshop date
function cc_upsell_get_page_by_title($page_title, $output = OBJECT, $post_type = 'page'){
    global $wpdb;
    if ( is_array( $post_type ) ) {
        $post_type           = esc_sql( $post_type );
        $post_type_in_string = "'" . implode( "','", $post_type ) . "'";
        $sql                 = $wpdb->prepare(
            "
            SELECT ID
            FROM $wpdb->posts
            WHERE post_title = %s
            AND post_type IN ($post_type_in_string)
        ",
            $page_title
        );
    } else {
        $sql = $wpdb->prepare(
            "
            SELECT ID
            FROM $wpdb->posts
            WHERE post_title = %s
            AND post_type = %s
        ",
            $page_title,
            $post_type
        );
    }
    $pages = $wpdb->get_col( $sql );
    if ( $pages ) {
    	$wanted_id = $latest_timestamp = 0;
    	foreach ($pages as $page_id) {
    		$this_timestamp = get_post_meta($page_id, 'workshop_timestamp', true);
    		if($this_timestamp == ''){
    			// try the start timestamp instead
    			$this_timestamp = get_post_meta($page_id, 'workshop_start_timestamp', true);
    		}
    		if($this_timestamp > $latest_timestamp){
    			$latest_timestamp = $this_timestamp;
    			$wanted_id = $page_id;
    		}
    	}
    	if($wanted_id > 0){
    		return get_post( $wanted_id, $output );
    	}
    }
}
