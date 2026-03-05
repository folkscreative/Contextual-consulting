<?php
/**
 * Organisational dashboard functions
 */

// get all the top level stats for the dashboard
// registrations, this month, last month and all time
function dashboard_get_reg_stats_top( $org ){
	global $wpdb;
	$payments_table = $wpdb->prefix.'ccpa_payments';
	$start_date = dashboard_org_start_date( $org );
	$org_uc = strtoupper( $org );

	// Get current time (WordPress-aware)
	$now = current_time('mysql');

	// Modified query to:
	// 1. Include cancelled registrations
	// 2. Only include recording (on-demand) and '' (live) types
	// 3. Include linked registrations from series/groups
	$query = "
	    SELECT
	        COUNT(*) AS total_count,
	        SUM(disc_amount) AS total_amount,

	        SUM(MONTH(last_update) = MONTH(%s) AND YEAR(last_update) = YEAR(%s)) AS this_month_count,
	        SUM(CASE WHEN MONTH(last_update) = MONTH(%s) AND YEAR(last_update) = YEAR(%s) THEN disc_amount ELSE 0 END) AS this_month_amount,

	        SUM(MONTH(last_update) = MONTH(DATE_SUB(%s, INTERVAL 1 MONTH)) AND YEAR(last_update) = YEAR(DATE_SUB(%s, INTERVAL 1 MONTH))) AS last_month_count,
	        SUM(CASE WHEN MONTH(last_update) = MONTH(DATE_SUB(%s, INTERVAL 1 MONTH)) AND YEAR(last_update) = YEAR(DATE_SUB(%s, INTERVAL 1 MONTH)) THEN disc_amount ELSE 0 END) AS last_month_amount
	    FROM $payments_table
	    WHERE last_update > '$start_date' 
	    	AND DISC_CODE = '$org_uc'
	    	AND (status = 'Payment not needed' OR status = 'Cancelled' OR status LIKE 'Linked to #%%')
	    	AND (type = 'recording' OR type = '' OR type IS NULL)";

	return $wpdb->get_row(
	    $wpdb->prepare($query, $now, $now, $now, $now, $now, $now, $now, $now),
	    ARRAY_A
	);

	// Output
	// echo 'All Time: ' . $stats['total_count'] . ' payments totaling $' . number_format($stats['total_amount'], 2) . '<br>';
	// echo 'This Month: ' . $stats['this_month_count'] . ' payments totaling $' . number_format($stats['this_month_amount'], 2) . '<br>';
	// echo 'Last Month: ' . $stats['last_month_count'] . ' payments totaling $' . number_format($stats['last_month_amount'], 2) . '<br>';
}

// start date for this org
function dashboard_org_start_date( $org ){
	$org = strtolower($org);
	switch ($org) {
		case 'nlft':
			// earliest possible payment record
			return '2025-04-01 00:00:00';
			break;
		case 'cnwl':
			// when they switched
			return '2025-03-25 00:00:00';
			break;
	}
	return '2025-01-01 00:00:00';
}

// get the user stats at the top level = top 10 users
function dashboard_get_user_stats_top( $org ){
	global $wpdb;
	$payments_table = $wpdb->prefix.'ccpa_payments';
	$start_date = dashboard_org_start_date( $org );
	$org_uc = strtoupper( $org );

	// Modified to include cancelled and linked registrations, only recording and '' types
	$query = "
	    SELECT reg_userid, COUNT(*) AS payment_count
	    FROM $payments_table
	    WHERE last_update >= %s
	      AND disc_code = %s
	      AND (status = 'Payment not needed' OR status = 'Cancelled' OR status LIKE 'Linked to #%%')
	      AND (type = 'recording' OR type = '' OR type IS NULL)
	    GROUP BY reg_userid
	    ORDER BY payment_count DESC
	    LIMIT 10
	";

	return $wpdb->get_results(
	    $wpdb->prepare($query, $start_date, $org_uc),
	    ARRAY_A
	);

	// Output the results
	/*
	if (!empty($top_users)) {
	    echo '<ol>';
	    foreach ($top_users as $user) {
	        echo '<li>User ID: ' . esc_html($user['reg_user_id']) . ' — ' . esc_html($user['payment_count']) . ' payments</li>';
	    }
	    echo '</ol>';
	} else {
	    echo 'No payments found for that period and discount code.';
	}
	*/
}

// monthly stats by type
add_action('wp_ajax_get_monthly_breakdown', 'ajax_get_monthly_breakdown');
function ajax_get_monthly_breakdown() {

	global $wpdb;

	$org         = sanitize_text_field($_POST['org']);
	$period      = sanitize_text_field($_POST['period']);
	$serviceType = sanitize_text_field($_POST['serviceType'] ?? '');
	$borough     = sanitize_text_field($_POST['borough'] ?? '');
	$role        = sanitize_text_field($_POST['role'] ?? '');

	$org_uc = strtoupper($org);
	$payments_table = $wpdb->prefix . 'ccpa_payments';

	// =========================
	// Date range
	// =========================

	if ($period == 'this_month') {
		$start = date('Y-m-01 00:00:00');
		$end   = date('Y-m-t 23:59:59');

	} elseif ($period == 'last_month') {
		$start = date('Y-m-01 00:00:00', strtotime('-1 month'));
		$end   = date('Y-m-t 23:59:59', strtotime('-1 month'));

	} elseif (preg_match('/^\d{4}-\d{2}$/', $period)) {
		$start = $period . '-01 00:00:00';
		$end   = date('Y-m-t 23:59:59', strtotime($start));

	} else {
		wp_send_json_error('Invalid period');
	}

	// =========================
	// Dynamic Joins
	// =========================

	$join_service = '';
	$join_borough = '';
	$join_role    = '';

	if (!empty($serviceType)) {
		$join_service = "
			INNER JOIN {$wpdb->usermeta} um_service
				ON um_service.user_id = u.ID
				AND um_service.meta_key = 'nlft_service_type'
				AND um_service.meta_value = %s
		";
	}

	if (!empty($borough)) {
		$join_borough = "
			INNER JOIN {$wpdb->usermeta} um_borough
				ON um_borough.user_id = u.ID
				AND um_borough.meta_key = 'nlft_borough'
				AND um_borough.meta_value = %s
		";
	}

	if (!empty($role)) {
		$join_role = "
			INNER JOIN {$wpdb->usermeta} um_role
				ON um_role.user_id = u.ID
				AND um_role.meta_key = 'job'
				AND um_role.meta_value = %s
		";
	}

	// =========================
	// Base Query
	// =========================

	$query = "
		SELECT 
			p.type,
			COUNT(DISTINCT p.ID) as cnt,
			SUM(p.disc_amount) as amt

		FROM $payments_table p

		JOIN {$wpdb->users} u 
			ON u.ID = p.reg_userid

		$join_service
		$join_borough
		$join_role

		WHERE p.last_update BETWEEN %s AND %s
		  AND p.disc_code = %s
		  AND (
		        p.status = 'Payment not needed'
		     OR p.status = 'Cancelled'
		     OR p.status LIKE 'Linked to #%%'
		  )
		  AND (p.type = 'recording' OR p.type = '' OR p.type IS NULL)

		GROUP BY p.type
	";
//echo $query;
	// =========================
	// Params (ORDER MATTERS)
	// =========================

	$params = [];

	if (!empty($serviceType)) {
		$params[] = $serviceType;
	}

	if (!empty($borough)) {
		$params[] = $borough;
	}

	if (!empty($role)) {
		$params[] = $role;
	}

	$params[] = $start;
	$params[] = $end;
	$params[] = $org_uc;

	// =========================
	// Execute
	// =========================

	$results = $wpdb->get_results(
		$wpdb->prepare($query, $params),
		ARRAY_A
	);

	wp_send_json_success($results);
}
/* function ajax_get_monthly_breakdown() {
	global $wpdb;

	$org = sanitize_text_field($_POST['org']);
	$period = sanitize_text_field($_POST['period']);
    $serviceType = sanitize_text_field($_POST['serviceType']);
    $borough = sanitize_text_field($_POST['borough']);
        
	$org_uc = strtoupper($org);
	$payments_table = $wpdb->prefix . 'ccpa_payments';

	// Set date ranges
	if ($period == 'this_month') {
		$start = date('Y-m-01 00:00:00');
		$end = date('Y-m-t 23:59:59');
	} elseif ($period == 'last_month') {
		$start = date('Y-m-01 00:00:00', strtotime('-1 month'));
		$end = date('Y-m-t 23:59:59', strtotime('-1 month'));
    } elseif (preg_match('/^\d{4}-\d{2}$/', $period)) {
        // Handle YYYY-MM format for other months
        $start = $period . '-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($start));
	} else {
		wp_send_json_error('Invalid period');
	}

	// Modified query to only include recording and '' types with cancelled/linked statuses
	// Type is already clean: '' for Live, 'recording' for On-demand
	$query = "
		SELECT 
			type,
			COUNT(*) as cnt, 
			SUM(disc_amount) as amt
		FROM $payments_table
		WHERE last_update BETWEEN %s AND %s
		  AND disc_code = %s
		  AND (status = 'Payment not needed' OR status = 'Cancelled' OR status LIKE 'Linked to #%%')
		  AND (type = 'recording' OR type = '' OR type IS NULL)
		GROUP BY type
	";

	

	$results = $wpdb->get_results($wpdb->prepare($query, $start, $end, $org_uc), ARRAY_A);

	wp_send_json_success($results);
}
 */
// monthly type user reg details
add_action('wp_ajax_get_monthly_type_breakdown', 'ajax_get_monthly_type_breakdown');
function ajax_get_monthly_type_breakdown() {

    global $wpdb;

    // -----------------------------------
    // Sanitize inputs
    // -----------------------------------
    $org           = isset($_POST['org']) ? sanitize_text_field($_POST['org']) : '';
    $period        = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '';
    $training_type = isset($_POST['training_type']) ? sanitize_text_field($_POST['training_type']) : '';
    $borough       = isset($_POST['borough']) ? sanitize_text_field($_POST['borough']) : '';
    $serviceType   = isset($_POST['serviceType']) ? sanitize_text_field($_POST['serviceType']) : '';
    $role          = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';

    if (empty($org) || empty($period) || empty($training_type)) {
        wp_send_json_error('Missing required parameters.');
        return;
    }

    $org_uc = strtoupper($org);
    $payments_table = $wpdb->prefix . 'ccpa_payments';

    // -----------------------------------
    // Resolve training type
    // -----------------------------------
    if ($training_type === 'Live') {
        $type_condition = " AND (p.type = '' OR p.type IS NULL) ";
    } elseif ($training_type === 'On-demand' || $training_type === 'recording') {
        $type_condition = " AND p.type = 'recording' ";
    } else {
        wp_send_json_error('Invalid training type.');
        return;
    }

    // -----------------------------------
    // Resolve period
    // -----------------------------------
    switch ($period) {

        case 'this_month':
            $month_start = date('Y-m-01 00:00:00');
            $month_end   = date('Y-m-t 23:59:59');
            break;

        case 'last_month':
            $month_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $month_end   = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;

        default:
            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                $month_start = $period . '-01 00:00:00';
                $month_end   = date('Y-m-t 23:59:59', strtotime($month_start));
            } else {
                wp_send_json_error('Invalid period format.');
                return;
            }
    }

    // -----------------------------------
    // Base Query (LEFT JOIN for display)
    // -----------------------------------
    $query = "
        SELECT 
            u.ID as user_id,
            u.user_email,
            um_service.meta_value as nlft_service_type,
            um_borough.meta_value as nlft_borough,
            p_posts.ID as training_id,
            p_posts.post_title AS training_title,
            DATE_FORMAT(p.last_update, '%%d/%%m/%%Y') as reg_date,
            p.disc_amount as value,
            p.status as payment_status

        FROM $payments_table p

        JOIN {$wpdb->users} u 
            ON u.ID = p.reg_userid

        LEFT JOIN {$wpdb->usermeta} um_service
            ON um_service.user_id = u.ID
            AND um_service.meta_key = 'nlft_service_type'

        LEFT JOIN {$wpdb->usermeta} um_borough
            ON um_borough.user_id = u.ID
            AND um_borough.meta_key = 'nlft_borough'

        JOIN {$wpdb->posts} p_posts 
            ON p_posts.ID = p.workshop_id

        WHERE p.disc_code = %s
          AND p.last_update BETWEEN %s AND %s
          AND p_posts.post_status = 'publish'
          AND (
                p.status = 'Payment not needed'
             OR p.status = 'Cancelled'
             OR p.status LIKE 'Linked to #%%'
          )
          $type_condition
    ";

    // -----------------------------------
    // Params (ORDER MATTERS)
    // -----------------------------------
    $params = [
        $org_uc,
        $month_start,
        $month_end
    ];

    // -----------------------------------
    // Optional Filters (SAFE EXISTS)
    // -----------------------------------

    if (!empty($serviceType)) {
        $query .= "
            AND EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um_service_f
                WHERE um_service_f.user_id = u.ID
                  AND um_service_f.meta_key = 'nlft_service_type'
                  AND um_service_f.meta_value = %s
            )
        ";
        $params[] = $serviceType;
    }

    if (!empty($borough)) {
        $query .= "
            AND EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um_borough_f
                WHERE um_borough_f.user_id = u.ID
                  AND um_borough_f.meta_key = 'nlft_borough'
                  AND um_borough_f.meta_value = %s
            )
        ";
        $params[] = $borough;
    }

    if (!empty($role)) {
        $query .= "
            AND EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um_role
                WHERE um_role.user_id = u.ID
                  AND um_role.meta_key = 'job'
                  AND um_role.meta_value = %s
            )
        ";
        $params[] = $role;
    }

    $query .= " ORDER BY u.user_email ASC";

    // -----------------------------------
    // Execute
    // -----------------------------------
    $prepared_query = $wpdb->prepare($query, $params);
    $results = $wpdb->get_results($prepared_query);

    if (empty($results)) {
        wp_send_json_success([]);
        return;
    }

    // -----------------------------------
    // Format Results
    // -----------------------------------
    foreach ($results as $row) {

        $icon = function_exists('dashboard_attendance_icon')
            ? dashboard_attendance_icon($row->user_id, $row->training_id)
            : '';

        $service_type = esc_html($row->nlft_service_type ?? '');
        $borough_val  = esc_html($row->nlft_borough ?? '');
        $title        = esc_html($row->training_title);
        $value        = number_format((float)$row->value, 2);

        if ($row->payment_status === 'Cancelled') {

            $row->training_title = '<span style="text-decoration: line-through;">' .
                $icon . ' ' . $title . '</span>';

            $row->nlft_service_type = '<span style="text-decoration: line-through;">' .
                $service_type . '</span>';

            $row->nlft_borough = '<span style="text-decoration: line-through;">' .
                $borough_val . '</span>';

            $row->reg_date = '<span style="text-decoration: line-through;">' .
                $row->reg_date . '</span>';

            $row->value = '<span style="text-decoration: line-through;">£' .
                $value . '</span>';

        } else {

            $row->training_title = $icon . ' ' . $title;
            $row->nlft_service_type = $service_type;
            $row->nlft_borough = $borough_val;
            $row->value = '£' . $value;
        }

        if (strpos($row->payment_status, 'Linked to #') === 0) {
            $row->training_title .= ' <small class="text-muted">(Series/Group)</small>';
        }
    }

    wp_send_json_success($results);
}
function ajax_get_monthly_type_breakdown_bkpold() {

    global $wpdb;

    // -----------------------------------
    // Sanitize inputs
    // -----------------------------------
    $org           = isset($_POST['org']) ? sanitize_text_field($_POST['org']) : '';
    $period        = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '';
    $training_type = isset($_POST['training_type']) ? sanitize_text_field($_POST['training_type']) : '';
    $borough = isset($_POST['borough']) ? sanitize_text_field($_POST['borough']) : '';
    $serviceType = isset($_POST['serviceType']) ? sanitize_text_field($_POST['serviceType']) : '';


    if (empty($org) || empty($period) || empty($training_type)) {
        wp_send_json_error('Missing required parameters.');
        return;
    }

    $org_uc = strtoupper($org);
    $payments_table = $wpdb->prefix . 'ccpa_payments';

    // -----------------------------------
    // Resolve training type
    // -----------------------------------
    if ($training_type === 'Live') {
        $type_condition = " AND (p.type = '' OR p.type IS NULL) ";
    } elseif ($training_type === 'On-demand' || $training_type === 'recording') {
        $type_condition = " AND p.type = 'recording' ";
    } else {
        wp_send_json_error('Invalid training type.');
        return;
    }

    // -----------------------------------
    // Resolve period into date range
    // -----------------------------------
    switch ($period) {

        case 'this_month':
            $month_start = date('Y-m-01 00:00:00');
            $month_end   = date('Y-m-t 23:59:59');
            break;

        case 'last_month':
            $month_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $month_end   = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;

        default:
            // Format expected: YYYY-MM
            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                $month_start = $period . '-01 00:00:00';
                $month_end   = date('Y-m-t 23:59:59', strtotime($month_start));
            } else {
                wp_send_json_error('Invalid period format.');
                return;
            }
    }

    // -----------------------------------
    // Main Query
    // -----------------------------------
    $query = "
        SELECT 
            u.ID as user_id,
            u.user_email,
            um_service.meta_value as nlft_service_type,
            um_borough.meta_value as nlft_borough,
            p_posts.ID as training_id,
            p_posts.post_title AS training_title,
            DATE_FORMAT(p.last_update, '%%d/%%m/%%Y') as reg_date,
            p.disc_amount as value,
            p.status as payment_status

        FROM $payments_table p

        JOIN {$wpdb->users} u 
            ON u.ID = p.reg_userid

        LEFT JOIN {$wpdb->usermeta} um_service 
            ON um_service.user_id = u.ID 
            AND um_service.meta_key = 'nlft_service_type'

        LEFT JOIN {$wpdb->usermeta} um_borough 
            ON um_borough.user_id = u.ID 
            AND um_borough.meta_key = 'nlft_borough'

        JOIN {$wpdb->posts} p_posts 
            ON p_posts.ID = p.workshop_id

        WHERE p.disc_code = %s
          AND p.last_update BETWEEN %s AND %s
          AND p_posts.post_status = 'publish'
          AND (
                p.status = 'Payment not needed'
                OR p.status = 'Cancelled'
                OR p.status LIKE 'Linked to #%%'
              )
          $type_condition

        ORDER BY u.user_email ASC
    ";

    $prepared_query = $wpdb->prepare(
        $query,
        $org_uc,
        $month_start,
        $month_end
    );

    $results = $wpdb->get_results($prepared_query);

    if (empty($results)) {
        wp_send_json_success([]);
        return;
    }

    // -----------------------------------
    // Format Results
    // -----------------------------------
    foreach ($results as $row) {

        $icon = function_exists('dashboard_attendance_icon')
            ? dashboard_attendance_icon($row->user_id, $row->training_id)
            : '';

        $service_type = esc_html($row->nlft_service_type ?? '');
        $borough      = esc_html($row->nlft_borough ?? '');
        $title        = esc_html($row->training_title);
        $value        = number_format((float)$row->value, 2);

        if ($row->payment_status === 'Cancelled') {

            $row->training_title = '<span style="text-decoration: line-through;">' . 
                $icon . ' ' . $title . '</span>';

            $row->nlft_service_type = '<span style="text-decoration: line-through;">' . 
                $service_type . '</span>';

            $row->nlft_borough = '<span style="text-decoration: line-through;">' . 
                $borough . '</span>';

            $row->reg_date = '<span style="text-decoration: line-through;">' . 
                $row->reg_date . '</span>';

            $row->value = '<span style="text-decoration: line-through;">£' . 
                $value . '</span>';

        } else {

            $row->training_title = $icon . ' ' . $title;
            $row->nlft_service_type = $service_type;
            $row->nlft_borough = $borough;
            $row->value = '£' . $value;
        }

        // Add series indicator
        if (strpos($row->payment_status, 'Linked to #') === 0) {
            $row->training_title .= ' <small class="text-muted">(Series/Group)</small>';
        }
    }

    wp_send_json_success($results);
}
// get user stats
add_action('wp_ajax_get_user_stats', 'ajax_get_user_stats');
function ajax_get_user_stats() {
    global $wpdb;
    $user_id = intval($_POST['user_id']);
    $org = sanitize_text_field($_POST['org']);
    $org_uc = strtoupper($org);
    $payments_table = $wpdb->prefix . 'ccpa_payments';

	$start_date = dashboard_org_start_date( $org );

	// Modified to include cancelled and linked registrations, only recording and '' types
    $query = "
        SELECT 
            p.workshop_id AS training_id,
            p_posts.post_title AS training_title,
            DATE_FORMAT(p.last_update, '%%d/%%m/%%Y') AS reg_date,
            p.disc_amount,
            p.type,
            p.status
        FROM $payments_table p
        JOIN {$wpdb->posts} p_posts ON p_posts.ID = p.workshop_id
        WHERE p.reg_userid = %d
          AND p.disc_code = %s
          AND p.last_update >= %s
          AND (p.status = 'Payment not needed' OR p.status = 'Cancelled' OR p.status LIKE 'Linked to #%%')
          AND (p.type = 'recording' OR p.type = '' OR p.type IS NULL)
        ORDER BY p.last_update DESC
    ";

    /*
    $query = "
        SELECT
            p.workshop_id AS training_id,
            p_posts.post_title AS training_title,
            DATE_FORMAT(p.last_update, '%%d/%%m/%%Y') AS reg_date,
            p.disc_amount,
            p.type
        FROM $payments_table p
        JOIN {$wpdb->posts} p_posts ON p_posts.ID = p.workshop_id
        WHERE p.reg_userid = %d
          AND p.disc_code = %s
          AND p.status = 'Payment not needed'
          AND (p.type IN ('series', 'recording', 'group', '') OR p.type IS NULL)
        ORDER BY p.last_update DESC
    ";
    */

    $results = $wpdb->get_results($wpdb->prepare($query, $user_id, $org_uc, $start_date), ARRAY_A);

    foreach ($results as &$row) { // That ampersand means we are looping "by reference."
        // Human-friendly type label
        switch ($row['type']) {
            case 'recording':
                $row['training_type'] = 'On-demand';
                break;
            case '':
            case null:
                $row['training_type'] = 'Live';
                break;
            default:
                $row['training_type'] = 'Live';
        }

        $row['disc_amount'] = number_format((float)$row['disc_amount'], 2);

	    $icon = dashboard_attendance_icon( $user_id, $row['training_id'] );
	    
	    // Apply strikethrough for cancelled registrations
	    if ($row['status'] == 'Cancelled') {
	        $row['training_title'] = '<span style="text-decoration: line-through;">' . $icon . ' ' . $row['training_title'] . '</span>';
	        $row['training_type'] = '<span style="text-decoration: line-through;">' . $row['training_type'] . '</span>';
	        $row['reg_date'] = '<span style="text-decoration: line-through;">' . $row['reg_date'] . '</span>';
	        $row['disc_amount'] = '<span style="text-decoration: line-through;">' . $row['disc_amount'] . '</span>';
	    } else {
	        $row['training_title'] = $icon . ' ' . $row['training_title'];
	        
	        // Add indicator if it's linked to a series/group
	        if (strpos($row['status'], 'Linked to #') === 0) {
	            $row['training_title'] .= ' <small class="text-muted">(S/G)</small>';
	        }
	    }

    }

    wp_send_json_success($results);
}

add_action('wp_ajax_ajax_get_all_user_stats', 'ajax_get_all_user_stats');
function ajax_get_all_user_stats() {
    global $wpdb;
    $org = sanitize_text_field($_POST['org']);
    $org_uc = strtoupper($org);
    $payments_table = $wpdb->prefix . 'ccpa_payments';

	$start_date = dashboard_org_start_date( $org );

	// Modified to include cancelled and linked registrations, only recording and '' types
    $query = "
        SELECT reg_userid, COUNT(*) AS payment_count
        FROM $payments_table
        WHERE last_update >= %s
          AND disc_code = %s
          AND (status = 'Payment not needed' OR status = 'Cancelled' OR status LIKE 'Linked to #%%')
          AND (type = 'recording' OR type = '' OR type IS NULL)          
        GROUP BY reg_userid
        ORDER BY payment_count DESC
    ";

    $users = $wpdb->get_results($wpdb->prepare($query, $start_date, $org_uc), ARRAY_A);

    usort($users, function ($a, $b) {
        // Sort by count descending, then by user name ascending
        $a_user = get_user_by('id', $a['reg_userid']);
        $b_user = get_user_by('id', $b['reg_userid']);
        $a_name = strtolower($a_user->first_name . ' ' . $a_user->last_name);
        $b_name = strtolower($b_user->first_name . ' ' . $b_user->last_name);
        return ($b['payment_count'] - $a['payment_count']) ?: strcmp($a_name, $b_name);
    });

    ob_start();
    foreach ($users as $user) {
        $user_data = get_user_by('id', $user['reg_userid']);
        $user_name = $user_data->first_name . ' ' . $user_data->last_name;
        $user_email = $user_data->user_email;
        $user_id = $user['reg_userid'];
        ?>
        <div class="row user-row align-items-center border-bottom py-2" data-user-id="<?php echo $user_id; ?>">
            <div class="col-8"><strong><?php echo esc_html( $user_name ); ?></strong> (<?php echo $user_email; ?>)</div>
            <div class="col-3 text-end"><?php echo $user['payment_count']; ?></div>
	        <div class="col-1 text-end">
	            <a href="#" class="dashboard-user-details" data-user-id="<?php echo $user_id; ?>" data-org="<?php echo $org; ?>">
	                <i class="fa-regular fa-square-plus"></i>
	            </a>
	        </div>
        </div>
	    <div class="row user-details" id="user-details-<?php echo $user_id; ?>" style="display:none;">
	        <div class="col-12">
	            <div class="py-2 px-3 border-start border-2 border-primary user-detail-list">
	                <em>Loading...</em>
	            </div>
	        </div>
	    </div>
        <?php
    }
    wp_send_json_success(ob_get_clean());
}

// returns an icon to indicate attendance
function dashboard_attendance_icon( $user_id, $training_id ){
	global $wpdb;
	$attendance_table = $wpdb->prefix.'attendance';

	$training_type = course_training_type( $training_id );

	// fallback
	$icon = '<i class="fa-solid fa-fw fa-circle-question"></i>'; 

	if( $training_type == 'workshop' ){

		// has the training started yet?
		$started = false;
        $workshop_start_timestamp = get_post_meta( $training_id, 'workshop_start_timestamp', true );
        if( $workshop_start_timestamp <> '' ){
        	$workshop_start_date = date( 'Y-m-d', $workshop_start_timestamp );
    		$start_time = get_post_meta( $training_id, 'event_1_time', true); // H:i (UTC)
    		if( $start_time == '' ){
				$dt = datetime::createFromFormat( "Y-m-d H:i", $workshop_start_date.' 00:00' );
    		}else{
				$dt = datetime::createFromFormat( "Y-m-d H:i", $workshop_start_date.' '.$start_time );
    		}
    		if( $dt ){
    			if( $dt->getTimestamp() < time() ){
    				$started = true;
    			}
    		}
        }

        // has the training ended yet?
        $ended = false;
        if( $started ){
			$workshop_end_timestamp = get_post_meta( $training_id, 'workshop_timestamp', true ); // time and date is good
			if($workshop_end_timestamp <> ''){
				if( $workshop_end_timestamp < time() ){
					$ended = true;
				}
			}
        }

        // has this user attended this training (any event/session)?
		$sql = $wpdb->prepare(
		    "SELECT 1 FROM $attendance_table WHERE user_id = %d AND training_id = %d LIMIT 1",
		    $user_id,
		    $training_id
		);
		$result = $wpdb->get_var($sql);
		if ($result !== null) {
		    // At least one row exists
		    $attended_live = true;
		} else {
		    // No matching row found
		    $attended_live = false;
		}

		// is there a recording?
		$recording_id = (int) get_post_meta( $training_id, 'workshop_recording', true );
		if( $recording_id > 0 ){
			$recording_exists = true;
		}else{
			$recording_exists = false;
		}

		// have they viewed the recording?
		$viewed_recording = false;
		// $recording_meta = get_user_meta( $user_id, 'cc_rec_wshop_'.$recording_id, true );
		$recording_meta = get_recording_meta( $user_id, $recording_id );
		if( isset( $recording_meta['num_views'] ) && $recording_meta['num_views'] > 0
			|| isset( $recording_meta['first_viewed'] ) && $recording_meta['first_viewed'] <> '' ){ // num_views not always being updated!!! :-(
			$viewed_recording = true;
		}

		// has their access to the recording expired?
		$recording_expired = false;
		if( isset( $recording_meta['closed_time'] ) && $recording_meta['closed_time'] <> '' ){
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $recording_meta['closed_time'] );
			if( $dt ){
				if( $dt->getTimestamp() < time() ){
					$recording_expired = true;
				}
			}
		}

		if( $attended_live ){
			$icon = '<i class="fa-solid fa-fw fa-circle-check text-success" title="Attended live"></i>';
		}elseif( $viewed_recording ){
			$icon = '<i class="fa-solid fa-fw fa-video text-primary" title="Viewed recording"></i>';
		}elseif( $recording_expired ){
			$icon = '<i class="fa-solid fa-fw fa-video-slash text-danger" title="Recording not watched before expiry"></i>';
		}elseif( ! $started ){
			$icon = '<i class="fa-solid fa-fw fa-hourglass-half" title="Training not started"></i>';
		}elseif( $recording_exists ){
			$icon = '<i class="fa-solid fa-fw fa-video text-warning" title="Recording available to watch"></i>';
		}elseif( $ended ){
			$icon = '<i class="fa-solid fa-fw fa-circle-xmark text-danger" title="Training not attended"></i>';
		}else{
			$icon = '<i class="fa-solid fa-fw fa-person-chalkboard" title="Training in progress"></i>';
		}

	}elseif( $training_type == 'recording' ){

		// have they viewed the training?
		$viewed_training = false;
		// $recording_meta = get_user_meta( $user_id, 'cc_rec_wshop_'.$training_id, true );
		$recording_meta = get_recording_meta( $user_id, $training_id );
		if( isset( $recording_meta['num_views'] ) && $recording_meta['num_views'] > 0
			|| isset( $recording_meta['first_viewed'] ) && $recording_meta['first_viewed'] <> '' ){ // num_views not always being updated!!! :-(
			$viewed_training = true;
		}

		// has their access to the training expired?
		$training_expired = false;
		if( isset( $recording_meta['closed_time'] ) && $recording_meta['closed_time'] <> '' ){
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $recording_meta['closed_time'] );
			if( $dt ){
				if( $dt->getTimestamp() < time() ){
					$training_expired = true;
				}
			}
		}

		if( $viewed_training ){
			$icon = '<i class="fa-solid fa-fw fa-circle-check text-success" title="Viewed training"></i>';			
		}elseif( $training_expired ){
			$icon = '<i class="fa-solid fa-fw fa-circle-xmark text-danger" title="Training not watched before expiry"></i>';			
		}else{
			$icon = '<i class="fa-solid fa-fw fa-hourglass-half" title="Training not watched yet"></i>';
		}

	}

	return $icon;
}

// Get registration stats for a specific month

function dashboard_get_month_stats($org, $year, $month) {

    global $wpdb;

    $payments_table = $wpdb->prefix . 'ccpa_payments';
    $org_uc = strtoupper($org);

    // Month boundaries
    $month_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $month_end   = date('Y-m-t 23:59:59', strtotime($month_start));

    $query = "
        SELECT
            COUNT(DISTINCT p.ID) AS count,
            SUM(p.disc_amount) AS amount
        FROM $payments_table p
        WHERE p.last_update BETWEEN %s AND %s
            AND p.disc_code = %s
            AND (
                p.status = 'Payment not needed'
                OR p.status = 'Cancelled'
                OR p.status LIKE 'Linked to #%%'
            )
            AND (
                p.type = 'recording'
                OR p.type = ''
                OR p.type IS NULL
            )
    ";

    $result = $wpdb->get_row(
        $wpdb->prepare($query, $month_start, $month_end, $org_uc),
        ARRAY_A
    );

    return [
        'count'  => !empty($result['count']) ? (int) $result['count'] : 0,
        'amount' => !empty($result['amount']) ? (float) $result['amount'] : 0
    ];
}
function dashboard_get_month_stats_oldBkp($org, $year, $month) {
    global $wpdb;
    $payments_table = $wpdb->prefix . 'ccpa_payments';
    $org_uc = strtoupper($org);
    
    // Calculate start and end of the month
    $month_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $month_end = date('Y-m-t 23:59:59', strtotime($month_start));
    
    $query = "
        SELECT
            COUNT(*) AS count,
            SUM(disc_amount) AS amount
        FROM $payments_table
        WHERE last_update BETWEEN %s AND %s
            AND DISC_CODE = %s
            AND (status = 'Payment not needed' OR status = 'Cancelled' OR status LIKE 'Linked to #%%')
            AND (type = 'recording' OR type = '' OR type IS NULL)
    ";
    
    $result = $wpdb->get_row(
        $wpdb->prepare($query, $month_start, $month_end, $org_uc),
        ARRAY_A
    );
    
    return [
        'count' => $result['count'] ?: 0,
        'amount' => $result['amount'] ?: 0
    ];
}

