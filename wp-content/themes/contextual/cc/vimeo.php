<?php
/**
 * Vimeostuff
 */

define('VIMEO_ACCESS_TOKEN', '7a7833a2415d61affd13913e71da2216');

add_action('init', 'cc_vimeo_video_data_table_update');
function cc_vimeo_video_data_table_update(){
	global $wpdb;
	// v1
	$cc_vimeo_video_data_db_ver = 1;
	$installed_table_ver = get_option('cc_vimeo_video_data_db_ver');
	if($installed_table_ver <> $cc_vimeo_video_data_db_ver){
		$vimeo_video_data_table = $wpdb->prefix.'vimeo_video_data';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $vimeo_video_data_table (
	        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	        video_id VARCHAR(50) NOT NULL UNIQUE,
	        duration FLOAT NOT NULL,
	        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	        PRIMARY KEY  (id)
	    ) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_vimeo_video_data_db_ver', $cc_vimeo_video_data_db_ver);
	}
}

// save the video duration
// duration is specified in seconds and can include decimals
add_action('wp_ajax_save_vimeo_duration', 'save_vimeo_duration_to_db');
function save_vimeo_duration_to_db() {
    global $wpdb;

    $video_id = sanitize_text_field($_POST['video_id']);
    if( stripos( $video_id, '?h=' ) > 0 ){
    	$video_id = substr( $video_id, 0, stripos( $video_id, '?h=' ) );
    }
    $duration = floatval($_POST['duration']);
    $table_name = $wpdb->prefix . 'vimeo_video_data';

    // Insert or update the record
    $wpdb->query(
        $wpdb->prepare("
            INSERT INTO $table_name (video_id, duration)
            VALUES (%s, %f)
            ON DUPLICATE KEY UPDATE duration = VALUES(duration), last_updated = NOW()
        ", $video_id, $duration)
    );

    echo "Duration for video $video_id saved/updated.";
    wp_die();
}

// get the duration
// returns the duration in seconds or NULL if not found
function get_vimeo_duration_by_id( $video_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vimeo_video_data';

    if( stripos( $video_id, '?h=' ) > 0 ){
    	$video_id = substr( $video_id, 0, stripos( $video_id, '?h=' ) );
    }

    return $wpdb->get_var($wpdb->prepare(
        "SELECT duration FROM $table_name WHERE video_id = %s",
        $video_id
    ));
}

// get the duration from Vimeo
function get_duration_from_vimeo( $video_id ){
    if( stripos( $video_id, '?h=' ) > 0 ){
        $video_id = substr( $video_id, 0, stripos( $video_id, '?h=' ) );
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.vimeo.com/videos/$video_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer ".VIMEO_ACCESS_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Debug output
    error_log("HTTP Code: " . $http_code);
    error_log("Response: " . $response);
    
    if ($response === false || $http_code !== 200) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['duration'])) {
        return $data['duration'];
    } else {
        error_log("Duration not found in response: " . print_r($data, true));
        return false;
    }
}

// universal get duration - looks in DB first, then Vimeo
// saves to DB if found in Vimeo
// returns number of seconds or false if it can't get it
function get_video_duration( $video_id ){
    global $wpdb;
    if( stripos( $video_id, '?h=' ) > 0 ){
        $video_id = substr( $video_id, 0, stripos( $video_id, '?h=' ) );
    }
    // try db first
    $duration = get_vimeo_duration_by_id( $video_id );
    if( $duration === NULL ){
        $duration = get_duration_from_vimeo( $video_id );
        if( $duration !== false ){
            $table_name = $wpdb->prefix . 'vimeo_video_data';
            // Insert or update the record
            $wpdb->query(
                $wpdb->prepare("
                    INSERT INTO $table_name (video_id, duration)
                    VALUES (%s, %f)
                    ON DUPLICATE KEY UPDATE duration = VALUES(duration), last_updated = NOW()
                ", $video_id, $duration)
            );
        }
    }
    return $duration;
}

function format_seconds_to_time($seconds) {
    $seconds = (int) round($seconds);

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs); // hh:mm:ss
    } else {
        return sprintf("%02d:%02d", $minutes, $secs); // mm:ss
    }
}

