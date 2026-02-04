<?php
/**
 * IP Geolocation
 */

// WordPress-optimized caching function using transients and custom table
function ccpa_ip_lookup($ip = '') {
    // Get IP if not provided
    if ($ip == '') {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Skip lookup for local/known server IPs - do this BEFORE any caching operations
    $skip_ips = ['77.72.2.12', '185.199.220.37', '127.0.0.1', '::1'];
    if (empty($ip) || in_array($ip, $skip_ips)) {
        return null;
    }
    
    // Validate IP address - avoid cache operations for invalid IPs
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        cc_debug_log_anything('ccpa_ip_lookup: Invalid or private IP: ' . $ip);
        return null;
    }
    
    // Use WordPress transients for short-term caching (1 hour)
    $transient_key = 'ip_geo_' . md5($ip);
    $cached_result = get_transient($transient_key);
    
    if ($cached_result !== false) {
        return $cached_result === 'NULL' ? null : $cached_result;
    }
    
    // Check long-term database cache (24 hours)
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_geolocation_cache';
    
    $cached_row = $wpdb->get_row($wpdb->prepare(
        "SELECT country_code, created_at FROM {$table_name} 
         WHERE ip_address = %s AND created_at > %s",
        $ip,
        date('Y-m-d H:i:s', time() - (24 * 60 * 60)) // 24 hours ago
    ));
    
    if ($cached_row) {
        // Store in transient for faster subsequent access
        set_transient($transient_key, $cached_row->country_code ?: 'NULL', HOUR_IN_SECONDS);
        return $cached_row->country_code ?: null;
    }
    
    // Get fresh data - now the uncached function only handles the API calls
    $result = ccpa_ip_lookup_fresh($ip);
    
    // Cache in both transient (1 hour) and database (permanent with date check)
    set_transient($transient_key, $result === null ? 'NULL' : $result, HOUR_IN_SECONDS);
    
    // Insert/update database cache
    $wpdb->replace(
        $table_name,
        [
            'ip_address' => $ip,
            'country_code' => $result,
            'created_at' => current_time('mysql')
        ],
        ['%s', '%s', '%s']
    );
    
    return $result;
}

function ccpa_ip_lookup_fresh($ip = '') {
    // Get IP if not provided
    if ($ip == '') {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Skip lookup for local/known server IPs
    $skip_ips = ['77.72.2.12', '185.199.220.37', '127.0.0.1', '::1'];
    if (empty($ip) || in_array($ip, $skip_ips)) {
        return null;
    }
    
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        cc_debug_log_anything('ccpa_ip_lookup: Invalid or private IP: ' . $ip);
        return null;
    }
    
    // Create context with longer timeout and user agent
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'user_agent' => 'Mozilla/5.0 (compatible; YourSite/1.0)',
            'ignore_errors' => true
        ]
    ]);
    
    // Define services in order of preference
    $services = [
        [
            'name' => 'ip-api',
            'url' => 'http://ip-api.com/json/' . $ip . '?fields=status,countryCode',
            'parser' => function($response) {
                $data = json_decode($response, true);
                return ($data && $data['status'] === 'success') ? $data['countryCode'] : null;
            }
        ],
        [
            'name' => 'ipwhois',
            'url' => 'https://ipwhois.app/json/' . $ip . '?objects=country_code',
            'parser' => function($response) {
                $data = json_decode($response, true);
                return ($data && isset($data['country_code'])) ? $data['country_code'] : null;
            }
        ],
        [
            'name' => 'ip2c',
            'url' => 'http://ip2c.org/' . $ip,
            'parser' => function($response) {
                if (strlen($response) > 0 && $response[0] === '1') {
                    $reply = explode(';', $response);
                    return isset($reply[1]) ? $reply[1] : null;
                }
                return null;
            }
        ],
        [
            'name' => 'iplocate',
            'url' => 'https://www.iplocate.io/api/lookup/' . $ip,
            'parser' => function($response) {
                $data = json_decode($response, true);
                return ($data && isset($data['country_code'])) ? $data['country_code'] : null;
            }
        ]
    ];
    
    // Try each service
    foreach ($services as $service) {
        try {
            $response = @file_get_contents($service['url'], false, $context);
            
            if ($response !== false && !empty($response)) {
                $country_code = $service['parser']($response);
                
                if ($country_code && strlen($country_code) === 2) {
                    cc_debug_log_anything('ccpa_ip_lookup: Success with ' . $service['name'] . ' for IP: ' . $ip . ' -> ' . $country_code);
                    return strtoupper($country_code);
                }
            }
        } catch (Exception $e) {
            cc_debug_log_anything('ccpa_ip_lookup: Exception with ' . $service['name'] . ': ' . $e->getMessage());
        }
        
        // Small delay between services to avoid rate limiting
        usleep(100000); // 0.1 seconds
    }
    
    // All services failed
    cc_debug_log_anything('ccpa_ip_lookup: All services failed for IP: ' . $ip);
    return null;
}

// Create the cache table
add_action( 'init', 'ccpa_create_ip_cache_table' );
function ccpa_create_ip_cache_table() {
    global $wpdb;
	$ip_geoloocation_cache_table_ver = 1;
	$installed_table_ver = (int) get_option('ip_geoloocation_cache_table_ver', 0);
	if( $installed_table_ver <> $ip_geoloocation_cache_table_ver ){
    
	    $table_name = $wpdb->prefix . 'ip_geolocation_cache';
	    
	    $charset_collate = $wpdb->get_charset_collate();
	    
	    $sql = "CREATE TABLE {$table_name} (
	        id bigint(20) NOT NULL AUTO_INCREMENT,
	        ip_address varchar(45) NOT NULL,
	        country_code varchar(2) NULL,
	        created_at datetime NOT NULL,
	        PRIMARY KEY (id),
	        UNIQUE KEY ip_address (ip_address),
	        KEY created_at (created_at)
	    ) {$charset_collate};";
	    
	    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);

	    update_option( 'ip_geoloocation_cache_table_ver', $installed_table_ver );
	}
}

// Clean old cache entries (run this via WP-Cron daily)
function ccpa_cleanup_ip_cache() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ip_geolocation_cache';
    
    // Delete entries older than 30 days
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} WHERE created_at < %s",
        date('Y-m-d H:i:s', time() - (30 * 24 * 60 * 60))
    ));
}

// Schedule daily cleanup if not already scheduled
if (!wp_next_scheduled('ccpa_cleanup_cache')) {
    wp_schedule_event(time(), 'daily', 'ccpa_cleanup_cache');
}