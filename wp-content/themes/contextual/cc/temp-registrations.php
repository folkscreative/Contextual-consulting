<?php
/**
 * Temp registrations
 * - used thru the registration process
 * - and for abandoned cart stuff
 */


function create_temp_registrations_table() {
    global $wpdb;
    $cc_temp_registrations_db_ver = 2;
    // v2 added payment_id, set after the payment concludes
    $installed_table_ver = get_option('cc_temp_registrations_db_ver');
    
    if($installed_table_ver != $cc_temp_registrations_db_ver) {
        $table_name = $wpdb->prefix . 'temp_registrations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token VARCHAR(64) NOT NULL UNIQUE, -- automatically creates A KEY FOR THIS
            user_id BIGINT(20) UNSIGNED NULL,
            email VARCHAR(100) NULL,
            course_id BIGINT(20) UNSIGNED NULL,
            current_step TINYINT(1) DEFAULT 1,
            max_step_reached TINYINT(1) DEFAULT 1,
            form_data LONGTEXT NOT NULL,
            status ENUM('active', 'completed', 'abandoned', 'expired') DEFAULT 'active',
            payment_id BIGINT(20) UNSIGNED,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            
            -- Abandoned cart specific fields
            abandon_detected_at DATETIME NULL,
            email_sequence_stage TINYINT(1) DEFAULT 0,
            recovery_emails_sent TINYINT(1) DEFAULT 0,
            last_email_sent_at DATETIME NULL,
            
            -- Tracking fields
            source_page VARCHAR(255) NULL, -- Which page they clicked from
            referrer_url VARCHAR(500) NULL, -- Full referrer URL
            utm_source VARCHAR(100) NULL,
            utm_medium VARCHAR(100) NULL,
            utm_campaign VARCHAR(100) NULL,
            utm_term VARCHAR(100) NULL,
            utm_content VARCHAR(100) NULL,
            device_type VARCHAR(20) NULL,
            user_agent TEXT NULL,
            ip_address VARCHAR(45) NULL,
            browser_language VARCHAR(10) NULL,
            screen_resolution VARCHAR(20) NULL,
            
            -- Session tracking
            session_duration INT NULL, -- Total time spent in seconds
            pages_visited INT DEFAULT 1,
            interactions_count INT DEFAULT 0, -- Clicks, form fills, etc.
            
            PRIMARY KEY (id),
            INDEX email (email),
            INDEX status (status),
            INDEX created_at (created_at),
            INDEX expires_at (expires_at),
            INDEX course_id (course_id),
            INDEX user_id (user_id),
            INDEX abandon_detected_at (abandon_detected_at),
            INDEX utm_source (utm_source),
            INDEX utm_campaign (utm_campaign),
            INDEX source_page (source_page)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option('cc_temp_registrations_db_ver', $cc_temp_registrations_db_ver);
    }
}
add_action('init', 'create_temp_registrations_table');


class TempRegistration {
    
    public static function create_from_post($post_data) {
        global $wpdb;

        $token = wp_generate_password(32, false);
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $user_id = cc_users_is_valid_user_logged_in() ? get_current_user_id() : null;

	    // sanitize the group training ids
	    $group_training = array_map( 'absint', (array) ( $post_data['training_id'] ?? [] ) );
	    $group_training = array_filter($group_training); // Remove zeros
	    $group_training = array_unique($group_training); // Remove duplicates
        
        // Core registration data
        $form_data = [
            'training_type' => sanitize_text_field($post_data['training-type'] ?? ''),
            'training_id' => intval($post_data['workshop-id'] ?? 0),
            'event_id' => sanitize_text_field($post_data['eventID'] ?? ''),
            'num_events' => intval($post_data['num-events'] ?? 1),
            'num_free' => intval($post_data['num-free'] ?? 0),
            'currency' => sanitize_text_field($post_data['currency'] ?? 'GBP'),
            'raw_price' => floatval($post_data['raw-price'] ?? 0),
            'user_timezone' => sanitize_text_field($post_data['user-timezone'] ?? ''),
            'user_prettytime' => sanitize_text_field($post_data['user-prettytime'] ?? ''),
            'student' => sanitize_text_field($post_data['student'] ?? 'no'),
            'student_price' => floatval($post_data['student-price'] ?? 0),
            'series_discount' => floatval($post_data['series_discount'] ?? 0),
            'group_training' => $group_training,
        ];

        if( $user_id > 0 ){
            $user = get_user_by( 'ID', $user_id );
            $form_data['portal_user'] = get_user_meta( $user_id, 'portal_user', true);
            $form_data = array_merge( $form_data, cc_users_user_details( $user ) );
            if( cc_mailsterint_on_newsletter( $user->user_email ) ){
                $form_data['mailing_list'] = 'p';
            }else{
                $form_data['mailing_list'] = '';
            }
        }
        
        // Tracking data (stored in separate columns)
        $tracking_data = [
            'source_page' => substr(sanitize_text_field($post_data['source_page'] ?? ''), 0, 255),
            'referrer_url' => wp_get_referer(),
            'utm_source' => sanitize_text_field($_GET['utm_source'] ?? $post_data['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($_GET['utm_medium'] ?? $post_data['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($_GET['utm_campaign'] ?? $post_data['utm_campaign'] ?? ''),
            'utm_term' => sanitize_text_field($_GET['utm_term'] ?? $post_data['utm_term'] ?? ''),
            'utm_content' => sanitize_text_field($_GET['utm_content'] ?? $post_data['utm_content'] ?? ''),
            'device_type' => wp_is_mobile() ? 'mobile' : 'desktop',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => self::get_client_ip(),
            'browser_language' => self::get_browser_language(),
            'screen_resolution' => '', // Will be filled by JavaScript
        ];

        $insert_data = array_merge([
            'token' => $token,
            'user_id' => $user_id,
            'email' => $user_id ? get_userdata($user_id)->user_email : null,
            'course_id' => $form_data['training_id'],
            'form_data' => json_encode($form_data),
            'expires_at' => $expires,
        ], $tracking_data);

        $result = $wpdb->insert($wpdb->prefix . 'temp_registrations', $insert_data);

        if ($result === false) {
            // ccpa_write_log('class TempRegistration method create_from_post');
            // ccpa_write_log('Database insert failed: ' . $wpdb->last_error);
            // ccpa_write_log('Last query: ' . $wpdb->last_query);
        } else {
            // ccpa_write_log('Insert successful. Rows affected: ' . $result);
        }

        return $token;
    }
    
    private static function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    public static function update_tracking($token, $tracking_updates) {
        global $wpdb;
        
        $allowed_fields = [
            'screen_resolution', 'session_duration', 'pages_visited', 
            'interactions_count', 'device_type', 'browser_language'
        ];
        
        $update_data = [];
        foreach ($tracking_updates as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $update_data[$key] = sanitize_text_field($value);
            }
        }
        
        if (!empty($update_data)) {
            $wpdb->update(
                $wpdb->prefix . 'temp_registrations',
                $update_data,
                ['token' => $token]
            );
        }
    }
    
    public static function increment_interactions($token) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}temp_registrations 
             SET interactions_count = interactions_count + 1 
             WHERE token = %s",
            $token
        ));
    }
    
    private static function get_browser_language() {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return substr($lang, 0, 2);
    }

    /**
     * Update registration with additional form data from AJAX
     */
    public static function update_form_data($token, $step, $new_data, $merge_arrays = true) {
        global $wpdb;

        // ccpa_write_log('class TempRegistration method update_form_data');
        // ccpa_write_log($token);
        // ccpa_write_log($step);
        // ccpa_write_log($new_data);
        // ccpa_write_log($merge_arrays);
        
        $existing = self::get($token);
        if (!$existing) return false;
        
        $form_data = json_decode($existing->form_data, true) ?: [];

        // ccpa_write_log($form_data);
        
        // Handle different types of data updates
        if ($merge_arrays) {
            $form_data = self::merge_form_data($form_data, $new_data);
        } else {
            $form_data = array_merge($form_data, $new_data);
        }
        
        // Update specific fields if provided
        $update_data = [
            'form_data' => json_encode($form_data),
            'current_step' => $step,
            'max_step_reached' => max($step, $existing->max_step_reached)
        ];
        
        // Update user_id if not already set
        if ( empty( $existing->user_id ) ) {
            $update_data['user_id'] = is_user_logged_in() ? get_current_user_id() : null;
        }

        // Update email if captured and not already set
        if (!empty($new_data['email']) && empty($existing->email)) {
            $update_data['email'] = sanitize_email($new_data['email']);
        }
        
        $wpdb->update(
            $wpdb->prefix . 'temp_registrations',
            $update_data,
            ['token' => $token]
        );
        
        return true;
    }
    
    /**
     * Smart merge of form data - handles arrays and special cases
     */
    private static function merge_form_data($existing_data, $new_data) {

        // ccpa_write_log('merge_form_data called with:');
        // ccpa_write_log('Existing: ' . print_r($existing_data, true));
        // ccpa_write_log('New: ' . print_r($new_data, true));

        foreach ($new_data as $key => $value) {
            if (is_array($value) && isset($existing_data[$key]) && is_array($existing_data[$key])) {
                // Handle special array merging cases
                switch ($key) {
                    case 'attendees':
                        $existing_data[$key] = self::merge_attendees($existing_data[$key], $value);
                        break;
                    case 'vouchers':
                    case 'promotional_codes':
                        $existing_data[$key] = self::merge_codes($existing_data[$key], $value);
                        break;
                    case 'more':
                        $existing_data[$key] = self::merge_more($existing_data[$key], $value);
                        break;
                    default:
                        $existing_data[$key] = array_merge($existing_data[$key], $value);
                }
            } else {
                $existing_data[$key] = $value;
            }
        }
        
        return $existing_data;
    }

    /**
     * Merge attendees data into standardized format with enhanced debugging and fixing
     */
    private static function merge_attendees($existing_attendees, $new_attendees) {
        // Ensure existing attendees is an array 
        if (!is_array($existing_attendees)) {
            $existing_attendees = [];
        }
        
        // ccpa_write_log('=== merge_attendees START ===');
        // ccpa_write_log('Existing attendees: ' . count($existing_attendees));
        // ccpa_write_log('New attendees type: ' . gettype($new_attendees));
        // ccpa_write_log('New attendees data: ' . json_encode($new_attendees));
        
        // New attendees should already be in standard format from JS/PHP processing
        if (is_array($new_attendees) && !empty($new_attendees)) {
            
            // Clear existing attendees if we have new ones (replace, don't merge)
            $existing_attendees = [];
            
            // Validate and process attendee data
            foreach ($new_attendees as $index => $attendee) {
                if (!is_array($attendee)) {
                    // ccpa_write_log('ERROR: Attendee ' . $index . ' is not an array: ' . json_encode($attendee));
                    continue;
                }
                
                // Basic validation for required fields
                if (!isset($attendee['email']) || !isset($attendee['firstname']) || !isset($attendee['registrant'])) {
                    // ccpa_write_log('ERROR: Attendee ' . $index . ' missing required fields: ' . json_encode($attendee));
                    continue;
                }
                
                // Enhanced fixing for registrant with empty data
                if (($attendee['registrant'] === 'r') && 
                    (empty($attendee['firstname']) || empty($attendee['email']))) {
                    
                    // ccpa_write_log('WARNING: Registrant has empty data, attempting to fix...');
                    // ccpa_write_log('Before fix: ' . json_encode($attendee));
                    
                    $current_user = wp_get_current_user();
                    if ($current_user->ID > 0) {
                        // Fix empty fields
                        if (empty($attendee['firstname'])) {
                            $attendee['firstname'] = get_user_meta($current_user->ID, 'first_name', true);
                        }
                        if (empty($attendee['lastname'])) {
                            $attendee['lastname'] = get_user_meta($current_user->ID, 'last_name', true);
                        }
                        if (empty($attendee['email'])) {
                            $attendee['email'] = $current_user->user_email;
                        }
                        if (empty($attendee['user_id']) || $attendee['user_id'] == 0) {
                            $attendee['user_id'] = $current_user->ID;
                        }
                        
                        // ccpa_write_log('After fix: ' . json_encode($attendee));
                    }
                }
                
                // Sanitize all fields
                $clean_attendee = [
                    'user_id' => intval($attendee['user_id'] ?? 0),
                    'registrant' => sanitize_text_field($attendee['registrant'] ?? ''),
                    'firstname' => sanitize_text_field($attendee['firstname'] ?? ''),
                    'lastname' => sanitize_text_field($attendee['lastname'] ?? ''),
                    'email' => sanitize_email($attendee['email'] ?? ''),
                ];
                
                // Final validation - only add if we have essential data
                if (!empty($clean_attendee['email']) && !empty($clean_attendee['firstname'])) {
                    $existing_attendees[] = $clean_attendee;
                    // ccpa_write_log('Added attendee: ' . $clean_attendee['email'] . ' (' . $clean_attendee['firstname'] . ' ' . $clean_attendee['lastname'] . ')');
                } else {
                    // ccpa_write_log('SKIPPED attendee due to missing essential data: ' . json_encode($clean_attendee));
                }
            }
        }
        
        // Ensure we have at least one attendee (the registrant)
        if (empty($existing_attendees)) {
            // ccpa_write_log('WARNING: No valid attendees found, creating default registrant');
            $current_user = wp_get_current_user();
            if ($current_user->ID > 0) {
                $existing_attendees[] = [
                    'user_id' => $current_user->ID,
                    'registrant' => 'r',
                    'firstname' => get_user_meta($current_user->ID, 'first_name', true),
                    'lastname' => get_user_meta($current_user->ID, 'last_name', true),
                    'email' => $current_user->user_email,
                ];
                // ccpa_write_log('Added default registrant attendee: ' . $current_user->user_email);
            }
        }
        
        // ccpa_write_log('Final attendees count: ' . count($existing_attendees));
        // ccpa_write_log('Final attendees: ' . json_encode($existing_attendees));
        // ccpa_write_log('=== merge_attendees END ===');
        
        return $existing_attendees;
    }



    /**
     * Merge promotional codes - keep unique codes
     */
    private static function merge_codes($existing_codes, $new_codes) {
        if (!is_array($existing_codes)) $existing_codes = [];
        if (!is_array($new_codes)) $new_codes = [$new_codes];

        // if job_id is supplied, it should be used as the job field
        if( isset( $new_codes['job_id'] ) && $new_codes['job_id'] <> '' ){
            $new_codes['job'] = $new_codes['job_id'];
            unset( $new_codes['job_id'] );
        }
        
        return array_unique(array_merge($existing_codes, $new_codes));
    }

    /**
     * Merge more fields
     */
    private static function merge_more( $existing_more, $new_more ){
        if (!is_array($existing_more)) $existing_more = [];
        if (!is_array($new_more)) $new_more = [$new_more];
        
        return array_unique(array_merge($existing_more, $new_more));
    }



    /**
     * Extract primary email from attendees
     */
    private static function extract_primary_email($attendees) {
        if (!is_array($attendees) || empty($attendees)) return null;
        
        // Look for primary attendee first
        foreach ($attendees as $attendee) {
            if (!empty($attendee['is_primary']) && !empty($attendee['email'])) {
                return sanitize_email($attendee['email']);
            }
        }
        
        // Fallback to first attendee with email
        foreach ($attendees as $attendee) {
            if (!empty($attendee['email'])) {
                return sanitize_email($attendee['email']);
            }
        }
        
        return null;
    }

    /**
     * Add a single attendee
     */
    public static function add_attendee($token, $attendee_data) {
        $form_data = self::get_form_data($token);
        
        if (!isset($form_data['attendees'])) {
            $form_data['attendees'] = [];
        }
        
        // Sanitize attendee data
        $clean_attendee = [
            'first_name' => sanitize_text_field($attendee_data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($attendee_data['last_name'] ?? ''),
            'email' => sanitize_email($attendee_data['email'] ?? ''),
            'phone' => sanitize_text_field($attendee_data['phone'] ?? ''),
            'company' => sanitize_text_field($attendee_data['company'] ?? ''),
            'dietary_requirements' => sanitize_textarea_field($attendee_data['dietary_requirements'] ?? ''),
            'is_primary' => !empty($attendee_data['is_primary']),
            'added_at' => current_time('mysql')
        ];
        
        return self::update_form_data($token, null, ['attendees' => [$clean_attendee]]);
    }

    /**
     * Remove an attendee by email or index
     */
    public static function remove_attendee($token, $identifier) {
        $form_data = self::get_form_data($token);
        
        if (!isset($form_data['attendees']) || !is_array($form_data['attendees'])) {
            return false;
        }
        
        $attendees = $form_data['attendees'];
        
        // Remove by email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $attendees = array_filter($attendees, function($attendee) use ($identifier) {
                return ($attendee['email'] ?? '') !== $identifier;
            });
        } 
        // Remove by index
        elseif (is_numeric($identifier) && isset($attendees[$identifier])) {
            unset($attendees[$identifier]);
        }
        
        $form_data['attendees'] = array_values($attendees); // Re-index array
        
        return self::update_form_data($token, null, $form_data, false);
    }

    /**
     * Apply promotional code/voucher
     */
    public static function apply_promotional_code($token, $code, $discount_info = []) {
        $form_data = self::get_form_data($token);
        
        if (!isset($form_data['applied_codes'])) {
            $form_data['applied_codes'] = [];
        }
        
        $code_data = [
            'code' => sanitize_text_field($code),
            'discount_type' => sanitize_text_field($discount_info['type'] ?? ''), // percentage, fixed, etc.
            'discount_value' => floatval($discount_info['value'] ?? 0),
            'applied_at' => current_time('mysql'),
            'valid' => !empty($discount_info['valid'])
        ];
        
        // Remove existing instance of this code
        $form_data['applied_codes'] = array_filter($form_data['applied_codes'], function($existing_code) use ($code) {
            return ($existing_code['code'] ?? '') !== $code;
        });
        
        // Add the new code
        $form_data['applied_codes'][] = $code_data;
        
        return self::update_form_data($token, null, $form_data, false);
    }

    /**
     * Remove promotional code
     */
    public static function remove_promotional_code($token, $code) {
        $form_data = self::get_form_data($token);
        
        if (!isset($form_data['applied_codes'])) {
            return false;
        }
        
        $form_data['applied_codes'] = array_filter($form_data['applied_codes'], function($existing_code) use ($code) {
            return ($existing_code['code'] ?? '') !== $code;
        });
        
        $form_data['applied_codes'] = array_values($form_data['applied_codes']); // Re-index
        
        return self::update_form_data($token, null, $form_data, false);
    }

    /**
     * Update how they heard about the course
     */
    public static function update_referral_source($token, $source, $details = '') {
        $referral_data = [
            'heard_about_source' => sanitize_text_field($source),
            'heard_about_details' => sanitize_textarea_field($details),
            'heard_about_updated_at' => current_time('mysql')
        ];
        
        return self::update_form_data($token, null, $referral_data, false);
    }

    // Update the existing get method
    public static function get($token) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}temp_registrations WHERE token = %s",
            $token
        ));
    }

    // Add method to get form data easily
    public static function get_form_data($token) {
        $registration = self::get($token);
        return $registration ? json_decode($registration->form_data, true) : [];
    }

    /**
     * Get specific data subset
     */
    public static function get_attendees($token) {
        $form_data = self::get_form_data($token);
        return $form_data['attendees'] ?? [];
    }
    
    public static function get_applied_codes($token) {
        $form_data = self::get_form_data($token);
        return $form_data['applied_codes'] ?? [];
    }
    
    public static function get_total_attendees($token) {
        $attendees = self::get_attendees($token);
        return count($attendees);
    }

    /**
     * Delete temporary registration data by token
     * 
     * @param string $token The unique token identifying the temporary registration
     * @return bool|int Returns number of rows deleted (1 on success, 0 if not found) or false on error
     */
    public static function delete($token) {
        global $wpdb;
        
        // Validate token parameter
        if (empty($token) || !is_string($token)) {
            return false;
        }
        
        // Optional: Log the deletion for debugging/audit purposes
        // ccpa_write_log('TempRegistration::delete() called for token: ' . substr($token, 0, 8) . '...');
        cc_debug_log_anything([
            'function' => 'TempRegistration::delete',
            'token' => $token,
            'row' => (array) self::get( $token ),
        ]);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'temp_registrations',
            ['token' => $token],
            ['%s'] // Format for token parameter
        );
        
        // Log any database errors
        if ($result === false) {
            ccpa_write_log('TempRegistration::delete() failed with database error: ' . $wpdb->last_error);
            return false;
        }
        
        // Log successful deletion
        // ccpa_write_log('TempRegistration::delete() completed. Rows affected: ' . $result);
        
        return $result;
    }


    /**
     * Update user info in the main temp registration record
     * This is separate from form_data to ensure email and user_id are always accessible
     */
    public static function update_user_info($token, $user_id, $email) {
        global $wpdb;
        
        $update_data = [
            'user_id' => $user_id,
            'email' => sanitize_email($email)
        ];
        
        $result = $wpdb->update(
            $wpdb->prefix . 'temp_registrations',
            $update_data,
            ['token' => $token]
        );
        
        if ($result === false) {
            cc_debug_log_anything([
                'function' => 'TempRegistration::update_user_info',
                'error' => 'Failed to update user info',
                'token' => $token,
                'user_id' => $user_id,
                'email' => $email,
                'wpdb_error' => $wpdb->last_error
            ]);
        }
        
        return $result;
    }

    /**
     * Get complete user details from temp registration
     * This retrieves both existing and new user details stored during registration
     */
    public static function get_user_details($token) {
        $registration = self::get($token);
        if (!$registration) {
            return null;
        }
        
        $form_data = json_decode($registration->form_data, true);
        
        // Check for new user details first (state 3)
        if (isset($form_data['new_user'])) {
            return $form_data['new_user'];
        }
        
        // Check for updated existing user details (state 5)
        if (isset($form_data['updated_user'])) {
            return $form_data['updated_user'];
        }
        
        // Check for existing user found (state 1/2)
        if (isset($form_data['existing_user'])) {
            // For existing users, we may need to fetch details from WordPress
            $user_id = $form_data['existing_user']['user_id'];
            if ($user_id) {
                $user = get_user_by('ID', $user_id);
                if ($user) {
                    return [
                        'user_id' => $user_id,
                        'email' => $user->user_email,
                        'firstname' => get_user_meta($user_id, 'first_name', true),
                        'lastname' => get_user_meta($user_id, 'last_name', true),
                        'address_line_1' => get_user_meta($user_id, 'address_line_1', true),
                        'address_line_2' => get_user_meta($user_id, 'address_line_2', true),
                        'address_town' => get_user_meta($user_id, 'address_town', true),
                        'address_county' => get_user_meta($user_id, 'address_county', true),
                        'address_postcode' => get_user_meta($user_id, 'address_postcode', true),
                        'address_country' => get_user_meta($user_id, 'address_country', true),
                        'phone' => get_user_meta($user_id, 'phone', true),
                        'job' => get_user_meta($user_id, 'job', true),
                    ];
                }
            }
        }
        
        // Fallback to basic info from main record
        if ($registration->user_id) {
            $user = get_user_by('ID', $registration->user_id);
            if ($user) {
                return [
                    'user_id' => $registration->user_id,
                    'email' => $registration->email ?: $user->user_email,
                    'firstname' => get_user_meta($registration->user_id, 'first_name', true),
                    'lastname' => get_user_meta($registration->user_id, 'last_name', true),
                ];
            }
        }
        
        return null;
    }

    /**
     * Restore a registration from an abandoned cart link
     * This method handles both logged-in and non-logged-in scenarios
     */
    public static function restore_from_token($token) {
        $registration = self::get($token);
        if (!$registration) {
            return false;
        }
        
        // Check if registration has expired
        if (strtotime($registration->expires_at) < time()) {
            return false;
        }
        
        // Get user details from the registration
        $user_details = self::get_user_details($token);
        
        if ($user_details && isset($user_details['user_id'])) {
            // Check if user is logged in
            $current_user_id = get_current_user_id();
            
            if ($current_user_id == 0) {
                // User not logged in - we can't auto-login for security
                // Return the registration data for the system to handle
                return [
                    'status' => 'needs_login',
                    'registration' => $registration,
                    'user_details' => $user_details,
                    'message' => 'Please log in to continue with your registration'
                ];
            } elseif ($current_user_id != $user_details['user_id']) {
                // Wrong user is logged in
                return [
                    'status' => 'wrong_user',
                    'registration' => $registration,
                    'user_details' => $user_details,
                    'message' => 'This registration belongs to a different account'
                ];
            }
            
            // Correct user is logged in - restore the session
            return [
                'status' => 'ready',
                'registration' => $registration,
                'user_details' => $user_details,
                'form_data' => json_decode($registration->form_data, true)
            ];
        }
        
        // No user details found - this shouldn't happen but handle gracefully
        return [
            'status' => 'incomplete',
            'registration' => $registration,
            'message' => 'Registration data is incomplete'
        ];
    }

    /**
     * Mark a registration as abandoned
     */
    public static function mark_as_abandoned($token) {
        global $wpdb;
        
        $update_data = [
            'status' => 'abandoned',
            'abandon_detected_at' => current_time('mysql')
        ];
        
        return $wpdb->update(
            $wpdb->prefix . 'temp_registrations',
            $update_data,
            ['token' => $token]
        );
    }

    /**
     * Get registrations that should be marked as abandoned
     * (e.g., started but not completed within X hours)
     */
    public static function get_potential_abandonments($hours_old = 2) {
        global $wpdb;
        
        $cutoff_time = date('Y-m-d H:i:s', strtotime("-{$hours_old} hours"));
        
        $query = "
            SELECT * FROM {$wpdb->prefix}temp_registrations 
            WHERE status = 'in_progress' 
            AND created_at < %s 
            AND abandon_detected_at IS NULL
            AND expires_at > NOW()
            ORDER BY created_at DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $cutoff_time));
    }

    /**
     * Update the status of a temporary registration
     * @param string $token The registration token
     * @param string $status The new status
     * @return bool Success/failure
     */
    public static function update_status($token, $status) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'temp_registrations',
            ['status' => sanitize_text_field($status)],
            ['token' => $token]
        );
        
        if ($result === false) {
            cc_debug_log_anything([
                'function' => 'TempRegistration::update_status',
                'error' => 'Failed to update status',
                'token' => $token,
                'status' => $status,
                'wpdb_error' => $wpdb->last_error
            ]);
        }
        
        return $result !== false;
    }

    public static function update_step( $token, $step ){
        global $wpdb;

        $existing = self::get($token);
        if (!$existing) return false;

        $update_data = [
            'current_step' => $step,
            'max_step_reached' => max($step, $existing->max_step_reached)
        ];
                
        $wpdb->update(
            $wpdb->prefix . 'temp_registrations',
            $update_data,
            ['token' => $token]
        );

        return true;

    }

}
