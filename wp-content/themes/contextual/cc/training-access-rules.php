<?php
/**
 * Training Access Rules
 * Manages which training courses grant access to other training courses
 * Many-to-many relationship
 */

add_action('init', 'create_training_access_rules_table');
function create_training_access_rules_table(){
    global $wpdb;
    $table_ver = 1;
    $installed_ver = get_option('cc_training_access_rules_ver');
    
    if($installed_ver != $table_ver){
        $table_name = $wpdb->prefix.'cc_training_access_rules';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            primary_training_id mediumint(9) NOT NULL COMMENT 'Training that grants access',
            supported_training_id mediumint(9) NOT NULL COMMENT 'Training that becomes accessible',
            rule_type varchar(20) NOT NULL DEFAULT 'standard' COMMENT 'standard, reciprocal, etc',
            status varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, inactive',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_primary_training (primary_training_id, status),
            KEY idx_supported_training (supported_training_id, status),
            KEY idx_combined_lookup (primary_training_id, supported_training_id, status),
            UNIQUE KEY unique_access_rule (primary_training_id, supported_training_id)
        ) $charset_collate;";
                
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('cc_training_access_rules_ver', $table_ver);
    }
}


/**
 * Check if user has "Add to Account" access to a specific training
 * 
 * @param int $user_id User ID
 * @param int $training_id Training to check access for
 * @return array|false Array with access details or false if no access
 */
function cc_training_access_user_has_access($user_id, $training_id) {
    global $wpdb;
    
    $rules_table = $wpdb->prefix.'cc_training_access_rules';
    $has_rules = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $rules_table 
         WHERE supported_training_id = %d AND status = 'active'",
        $training_id
    ));
    
    if(!$has_rules) {
        return false;
    }
    
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $groups_table = $wpdb->prefix.'cc_training_groups';
    
    // Query checks:
    // 1. Direct workshop_id match with rules
    // 2. Upsell workshop_id match with rules
    // 3. Group courses match with rules
    $sql = "
        SELECT 
            p.id as payment_id,
            p.workshop_id,
            p.upsell_workshop_id,
            p.type as payment_type,
            r.primary_training_id,
            'direct' as access_type
        FROM $payments_table p
        INNER JOIN $rules_table r ON (
            r.primary_training_id = p.workshop_id 
            OR r.primary_training_id = p.upsell_workshop_id
        )
        WHERE (p.reg_userid = %d OR p.att_userid = %d)
        AND p.status != 'Cancelled'
        AND r.supported_training_id = %d
        AND r.status = 'active'
        
        UNION
        
        SELECT 
            p.id as payment_id,
            p.workshop_id,
            p.upsell_workshop_id,
            p.type as payment_type,
            r.primary_training_id,
            'group' as access_type
        FROM $payments_table p
        INNER JOIN $groups_table g ON g.payment_id = p.id
        INNER JOIN $rules_table r ON r.primary_training_id = g.course_id
        WHERE (p.reg_userid = %d OR p.att_userid = %d)
        AND p.status != 'Cancelled'
        AND p.type = 'group'
        AND r.supported_training_id = %d
        AND r.status = 'active'
        
        LIMIT 1
    ";
    
    $result = $wpdb->get_row($wpdb->prepare(
        $sql,
        $user_id, $user_id, $training_id,
        $user_id, $user_id, $training_id
    ));
    
    if(!$result) {
        return cc_training_access_check_series_access($user_id, $training_id);
    }
    
    return array(
        'has_access' => true,
        'via_training_id' => $result->primary_training_id,
        'via_training_title' => get_the_title($result->primary_training_id),
        'via_payment_id' => $result->payment_id,
        'access_type' => $result->access_type
    );
}

/**
 * Check series access
 * 
 * @param int $user_id User ID
 * @param int $training_id Training to check
 * @return array|false
 */
function cc_training_access_check_series_access($user_id, $training_id) {
    global $wpdb;
    
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $rules_table = $wpdb->prefix.'cc_training_access_rules';
    
    $primary_trainings = $wpdb->get_col($wpdb->prepare(
        "SELECT primary_training_id FROM $rules_table 
         WHERE supported_training_id = %d AND status = 'active'",
        $training_id
    ));
    
    if(empty($primary_trainings)) {
        return false;
    }
    
    $series_payments = $wpdb->get_results($wpdb->prepare(
        "SELECT p.id, p.workshop_id 
         FROM $payments_table p
         INNER JOIN {$wpdb->prefix}posts posts ON p.workshop_id = posts.ID
         WHERE (p.reg_userid = %d OR p.att_userid = %d)
         AND p.status != 'Cancelled'
         AND posts.post_type = 'series'",
        $user_id, $user_id
    ));
    
    foreach($series_payments as $payment) {
        $series_courses = get_post_meta($payment->workshop_id, '_series_courses', true);
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        
        $match = array_intersect($series_courses, $primary_trainings);
        if(!empty($match)) {
            $matching_training_id = reset($match);
            return array(
                'has_access' => true,
                'via_training_id' => $matching_training_id,
                'via_training_title' => get_the_title($matching_training_id),
                'via_payment_id' => $payment->id,
                'access_type' => 'series',
                'series_id' => $payment->workshop_id
            );
        }
    }
    
    return false;
}

/**
 * Does user already have this training?
 * 
 * @param int $user_id User ID
 * @param int $training_id Training ID
 * @return bool
 */
function cc_training_access_user_already_registered($user_id, $training_id) {
    global $wpdb;
    
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $groups_table = $wpdb->prefix.'cc_training_groups';
    
    $sql = "
        SELECT 1
        FROM $payments_table p
        WHERE (p.reg_userid = %d OR p.att_userid = %d)
        AND p.status != 'Cancelled'
        AND (
            p.workshop_id = %d 
            OR p.upsell_workshop_id = %d
            OR EXISTS (
                SELECT 1 FROM $groups_table g 
                WHERE g.payment_id = p.id 
                AND g.course_id = %d
            )
        )
        LIMIT 1
    ";
    
    $result = $wpdb->get_var($wpdb->prepare(
        $sql,
        $user_id, $user_id, 
        $training_id, $training_id, $training_id
    ));
    
    if($result) {
        return true;
    }
    
    $training_type = course_training_type($training_id);
    
    if($training_type == 'series') {
        return false;
    }
    
    $series_payments = $wpdb->get_results($wpdb->prepare(
        "SELECT p.workshop_id 
         FROM $payments_table p
         INNER JOIN {$wpdb->prefix}posts posts ON p.workshop_id = posts.ID
         WHERE (p.reg_userid = %d OR p.att_userid = %d)
         AND p.status != 'Cancelled'
         AND posts.post_type = 'series'",
        $user_id, $user_id
    ));
    
    foreach($series_payments as $payment) {
        $series_courses = get_post_meta($payment->workshop_id, '_series_courses', true);
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        
        if(in_array($training_id, $series_courses)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get all trainings user has "Add to Account" access to
 * This is more expensive, only use for admin/account pages, not individual checks
 * Uses caching to minimize impact
 * 
 * @param int $user_id User ID
 * @param bool $use_cache Whether to use transient caching
 * @return array
 */
function cc_training_access_get_user_eligible_trainings($user_id, $use_cache = true) {
    // Check cache first
    $cache_key = 'user_eligible_trainings_' . $user_id;
    
    if($use_cache) {
        $cached = get_transient($cache_key);
        if($cached !== false) {
            return $cached;
        }
    }
    
    global $wpdb;
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $groups_table = $wpdb->prefix.'cc_training_groups';
    $rules_table = $wpdb->prefix.'cc_training_access_rules';
    
    // Get eligible trainings from direct registrations and upsells
    $direct_eligible = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT
            r.supported_training_id,
            r.primary_training_id,
            'direct' as access_type
        FROM $payments_table p
        INNER JOIN $rules_table r ON (
            r.primary_training_id = p.workshop_id 
            OR r.primary_training_id = p.upsell_workshop_id
        )
        WHERE (p.reg_userid = %d OR p.att_userid = %d)
        AND p.status != 'Cancelled'
        AND r.status = 'active'
    ", $user_id, $user_id));
    
    // Get eligible trainings from group registrations
    $group_eligible = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT
            r.supported_training_id,
            r.primary_training_id,
            'group' as access_type
        FROM $payments_table p
        INNER JOIN $groups_table g ON g.payment_id = p.id
        INNER JOIN $rules_table r ON r.primary_training_id = g.course_id
        WHERE (p.reg_userid = %d OR p.att_userid = %d)
        AND p.status != 'Cancelled'
        AND p.type = 'group'
        AND r.status = 'active'
    ", $user_id, $user_id));
    
    // Combine results
    $eligible = array_merge($direct_eligible, $group_eligible);
    
    // Add series-based eligible trainings
    $series_eligible = cc_training_access_get_series_eligible($user_id);
    $eligible = array_merge($eligible, $series_eligible);
    
    // Remove duplicates and filter out already registered
    $filtered = array();
    $seen = array();
    
    foreach($eligible as $item) {
        $key = $item->supported_training_id . '_' . $item->primary_training_id;
        
        if(!isset($seen[$key])) {
            // Check if they already have this training
            if(!cc_training_access_user_already_registered($user_id, $item->supported_training_id)) {
                $filtered[] = $item;
                $seen[$key] = true;
            }
        }
    }
    
    // Cache for 5 minutes
    if($use_cache) {
        set_transient($cache_key, $filtered, 300);
    }
    
    return $filtered;
}

/**
 * Helper: Get eligible trainings from series registrations
 * 
 * @param int $user_id User ID
 * @return array
 */
function cc_training_access_get_series_eligible($user_id) {
    global $wpdb;
    
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $rules_table = $wpdb->prefix.'cc_training_access_rules';
    
    $series_payments = $wpdb->get_results($wpdb->prepare(
        "SELECT p.workshop_id 
         FROM $payments_table p
         INNER JOIN {$wpdb->prefix}posts posts ON p.workshop_id = posts.ID
         WHERE (p.reg_userid = %d OR p.att_userid = %d)
         AND p.status != 'Cancelled'
         AND posts.post_type = 'series'",
        $user_id, $user_id
    ));
    
    if(empty($series_payments)) {
        return array();
    }
    
    $series_eligible = array();
    
    foreach($series_payments as $payment) {
        $series_courses = get_post_meta($payment->workshop_id, '_series_courses', true);
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        
        if(empty($series_courses)) {
            continue;
        }
        
        $placeholders = implode(',', array_fill(0, count($series_courses), '%d'));
        $query = $wpdb->prepare(
            "SELECT DISTINCT
                supported_training_id,
                primary_training_id
             FROM $rules_table 
             WHERE primary_training_id IN ($placeholders)
             AND status = 'active'",
            $series_courses
        );
        
        $results = $wpdb->get_results($query);
        
        foreach($results as $result) {
            $result->access_type = 'series';
            $series_eligible[] = $result;
        }
    }
    
    return $series_eligible;
}


/**
 * Clear user's eligible trainings cache when they register for new training
 * Hook this to your registration completion function
 */
function cc_training_access_clear_user_cache($user_id) {
    $cache_key = 'user_eligible_trainings_' . $user_id;
    delete_transient($cache_key);
}

// Hook into registration completion
add_action('cc_registration_completed', 'cc_training_access_clear_user_cache', 10, 1);


/**
 * Add a training access rule
 */
function cc_training_access_add_rule($primary_training_id, $supported_training_id, $rule_type = 'standard') {
    global $wpdb;
    $table = $wpdb->prefix.'cc_training_access_rules';
    
    if($primary_training_id == $supported_training_id) {
        return false;
    }
    
    $result = $wpdb->insert(
        $table,
        array(
            'primary_training_id' => $primary_training_id,
            'supported_training_id' => $supported_training_id,
            'rule_type' => $rule_type,
            'status' => 'active'
        ),
        array('%d', '%d', '%s', '%s')
    );
    
    return $result ? $wpdb->insert_id : false;
}

/**
 * Get all supported trainings for a primary training
 */
function cc_training_access_get_supported_trainings($primary_training_id) {
    global $wpdb;
    $table = $wpdb->prefix.'cc_training_access_rules';
    
    return $wpdb->get_col($wpdb->prepare(
        "SELECT supported_training_id 
         FROM $table 
         WHERE primary_training_id = %d 
         AND status = 'active'",
        $primary_training_id
    ));
}

/**
 * Get all primary trainings that grant access to a supported training
 */
function cc_training_access_get_primary_trainings($supported_training_id) {
    global $wpdb;
    $table = $wpdb->prefix.'cc_training_access_rules';
    
    return $wpdb->get_col($wpdb->prepare(
        "SELECT primary_training_id 
         FROM $table 
         WHERE supported_training_id = %d 
         AND status = 'active'",
        $supported_training_id
    ));
}

