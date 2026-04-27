<?php
/**
 * My account dashboard for org. admins
 */
function nlft_service_type(){
	return array(
		'Adult community mental health',
		'Children and young people',
		'Complex emotional needs',
		'Eating disorders',
		'Forensic psychology',
		'Health psychology',
		'Inpatient and urgent care',
		'Learning disabilities',
		'Neuropsychology',
		'Perinatal mental health',
		'Older adults service',
		'Substance misuse services',
		'Talking therapies (previously IAPT)',
		'Veterans mental health',
	);
}
function nlft_boroughss(){
	return array(
		'Acute Adult Inpatient and Same Day Emergency Care Group',
		'Children and Young People and Perinatal Care Group',
		'Community Care Group',
		'Forensic and Tertiary Care Group',
		'Older People and Rehabilitation Care Group',
		'Cross – Trust',
	);
}


function nlft_role(){

if($_GET['org'] == "nlft"):
     return array(
   'Assistant psychologist',
'Mental health well-being practitioner',
'Mental health well-being practitioner in training',
'Psychological wellbeing practitioner',
'Psychological wellbeing practitioner in training',
"Children's wellbeing practitioner",
"Children's wellbeing practitioner in training",
'Adult psychotherapist',
'Adult psychotherapist in training',
'Family and systemic psychotherapist',
'Family and systemic psychotherapist in training',
'Child and adolescent psychotherapist',
'Child and adolescent psychotherapist in training',
'Clinical associate in psychology (CAP)',
'Clinical associate in psychology (CAP) in training',
'Arts therapist',
'Arts therapist in training',
'Applied psychologist',
'Applied psychologist in training',
'Mental health practitioner',
'Behaviour analyst',
'Coach',
'CBT therapist',
'CBT therapist in training',
'Counsellor',
'Counsellor in training',
'Doctor',
'Doctor in training',
'Peer support worker',
     );
else:
    return array(
        'Assistant psychologist', 
        'Mental health well-being practitioner', 
        'Psychological wellbeing practitioner', 
        'Applied psychologist',   
        'Psychotherapist', 
        'Mental health practitioner' 
    );
endif;
}
function cc_myacct_dashboard( $org='' ){

    $user_id = get_current_user_id();
    $portal_user  = get_user_meta( $user_id, 'portal_user', true );
    $portal_admin = get_user_meta( $user_id, 'portal_admin', true );

    if( ( $portal_user <> '' && $portal_admin == 'yes' ) || 
        ( current_user_can( 'manage_options' ) && $org <> '' ) ){

        if( current_user_can( 'manage_options' ) && $org <> '' ){
            $portal_user = $org;
        }

        // =========================
        // Capture filters
        // =========================

        $filter_start = null;
        $filter_end   = null;

        if (!empty($_GET['filter_start_date']) && !empty($_GET['filter_end_date'])) {

            $start_ts = strtotime($_GET['filter_start_date']);
            $end_ts   = strtotime($_GET['filter_end_date']);

            if ($start_ts && $end_ts && $start_ts <= $end_ts) {
                $filter_start = date('Y-m-d 00:00:00', $start_ts);
                $filter_end   = date('Y-m-d 23:59:59', $end_ts);
            }
        }

        $filter_service_type = sanitize_text_field($_GET['filter_service_type'] ?? '');
        $filter_borough      = sanitize_text_field($_GET['filter_borough'] ?? '');
        $filter_role         = sanitize_text_field($_GET['filter_role'] ?? '');


        $html = '<h3 class="d-md-none">My profile</h3>';

        $start_date_str = dashboard_org_start_date($portal_user);
        $start_date = new DateTime($start_date_str);
        $regs = dashboard_get_reg_stats_top( $portal_user );
        $html .= '<h3>';
        switch ( $portal_user ) {
            case 'nlft': $html .= 'North London NHS Foundation Trust'; break;
            case 'cnwl': $html .= 'Central and North West London NHS Foundation Trust'; break;
        }
        $html .= '</h3>';

        // =========================
        // FILTER UI
        // =========================

        $html .= '
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">
                    <i class="fa-solid fa-filter"></i> Filter usage
                </h5>

                <form method="get" class="usage-filter">
                    <input type="hidden" name="my" value="dashboard">
                    <input type="hidden" name="org" value="'. esc_attr($_GET['org'] ?? ''). '">

                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">From date</label>
                            <input type="date" class="form-control"
                                   name="filter_start_date"
                                   value="'.esc_attr($_GET['filter_start_date'] ?? '').'">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">To date</label>
                            <input type="date" class="form-control"
                                   name="filter_end_date"
                                   value="'.esc_attr($_GET['filter_end_date'] ?? '').'">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="filter_role">
                                <option value="">All roles</option>';
                              
                                foreach (nlft_role() as $label) {
            $html .= '<option value="' . esc_attr($label) . '" ' .
                     selected($filter_role, $label, false) . '>' .
                     esc_html($label) . '</option>';
        }
       
        $html .= '</select>
                        </div>';
                         if($_GET['org'] == 'nlft'):

                        $html .= '<div class="col-md-2">
                            <label class="form-label">Service type</label>
                            <select class="form-select" name="filter_service_type">
                                <option value="">All services</option>';

        foreach (nlft_service_type() as $label) {
            $html .= '<option value="' . esc_attr($label) . '" ' .
                     selected($filter_service_type, $label, false) . '>' .
                     esc_html($label) . '</option>';
        }

        $html .= '</select></div>

                        <div class="col-md-2">
                            <label class="form-label">Borough</label>
                            <select class="form-select" name="filter_borough">
                                <option value="">All boroughs</option>';

        foreach (nlft_boroughss() as $label) {
            $html .= '<option value="' . esc_attr($label) . '" ' .
                     selected($filter_borough, $label, false) . '>' .
                     esc_html($label) . '</option>';
        }

        $html .= '</select></div>';
                         endif;
        $html .= '<div class="col-12 text-end pt-2">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa-solid fa-filter"></i> Apply filters
                            </button>
                            <a href="?my=dashboard&org='.esc_attr($portal_user).'" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>

                    </div>
                </form>
            </div>
        </div>';

        // =========================
        // REGISTRATIONS TABLE
        // =========================

        $html .= '
            <h4 class="mb-0">Registrations</h4>
            <div class="row align-items-center border-bottom py-2">
                <div class="col-4"><strong>&nbsp;</strong></div>
                <div class="col-4 text-end"><strong>Count</strong></div>
                <div class="col-3 text-end"><strong>Value</strong></div>
            </div>';

     //month loop logic
if ($filter_start && $filter_end) {

    $start = new DateTime($filter_start);
    $start->modify('first day of this month');

    $end = new DateTime($filter_end);
    $end->modify('first day of this month');

} else {

    $start = clone $start_date;
    $start->modify('first day of this month');

    $end = new DateTime('first day of this month');
}

$month_rows = [];

while ($start <= $end) {

    $year  = $start->format('Y');
    $month = $start->format('n');
    $month_key = $start->format('Y_m');
    $month_label = $start->format('M Y');

    $month_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $month_end   = date('Y-m-t 23:59:59', strtotime($month_start));

    $has_filter = (
        !empty($filter_start) ||
        !empty($filter_service_type) ||
        !empty($filter_borough) ||
        !empty($filter_role)      
    );

    if ($has_filter) {

        $effective_start = $filter_start ?: $month_start;
        $effective_end   = $filter_end   ?: $month_end;

        $month_stats = dashboard_get_month_stats_filtered(
            $portal_user,
            $year,
            $month,
            $effective_start,
            $effective_end,
            $filter_service_type,
            $filter_borough,
            $filter_role           
        );

    } else {

        $month_stats = dashboard_get_month_stats(
            $portal_user,
            $year,
            $month
        );
    }

    $month_rows[$month_key] = [
        'label'             => $month_label,
        'count'             => $month_stats['count'],
        'amount'            => $month_stats['amount'],
        'outside_allowance' => $month_stats['outside_allowance'] ?? 0,
        'key'               => $start->format('Y-m')
    ];

    $start->modify('+1 month');
}
        $month_rows = array_reverse($month_rows, true);

        foreach ($month_rows as $month_data) {

            $oa_line = '';
            if ( $portal_user === 'cnwl' && $month_data['outside_allowance'] > 0 ) {
                $oa_line = '
                <div class="row py-1">
                    <div class="col-4 ps-4 text-muted small">
                        <i class="fa-solid fa-circle-info"></i> incl. outside allowance
                    </div>
                    <div class="col-4 text-end text-muted small">' . $month_data['outside_allowance'] . '</div>
                    <div class="col-4"></div>
                </div>';
            }

            $html .= '
            <div class="row align-items-center border-bottom py-2">
                <div class="col-4"><strong>' . $month_data['label'] . '</strong></div>
                <div class="col-4 text-end">' . $month_data['count'] . '</div>
                <div class="col-3 text-end">&pound;' . number_format($month_data['amount'], 2) . '</div>
                <div class="col-1 text-end">
                    <a href="#" class="dashboard-toggle-details"
                       data-period="' . esc_attr($month_data['key']) . '"
                       data-org="' . esc_attr($portal_user) . '"
                       data-service-type="'.esc_attr($filter_service_type).'"
                       data-borough="'.esc_attr($filter_borough).'"
                       data-role="'.esc_attr($filter_role).'">
                        <i class="fa-regular fa-square-plus"></i>
                    </a>
                </div>
            </div>
            ' . $oa_line . '
            <div class="row details-row" id="details-' . $month_data['key'] . '" style="display:none;">
                <div class="col-12">
                    <div class="py-2 px-3 border-start border-2 border-primary" id="details-content-' . $month_data['key'] . '">
                        <em>Loading...</em>
                    </div>
                </div>
            </div>';
        }

        $html .= '
			<div class="row align-items-center border-bottom py-2">
				<div class="col-4"><strong>All</strong></div>
				<div class="col-4 text-end">'.$regs['total_count'].'</div>
				<div class="col-3 text-end">&pound;'.number_format( $regs['total_amount'], 2 ).'</div>
			</div>';

		$users = dashboard_get_user_stats_top( $portal_user );
		$html .= '<h4 class="mb-0 mt-5">Top users</h4>';
		if( empty( $users ) ){
			$html .= '<p>No registrations found</p>';
		}else{
			// header
			$html .= '
				<div class="row align-items-center border-bottom py-2">
					<div class="col-8">&nbsp;</div>
					<div class="col-3 text-end"><strong>Registrations</strong></div>
				</div>';
			// top 10 users
			foreach ($users as $user) {
				$user_data = get_user_by( 'id', $user['reg_userid'] );
				$user_name = $user_data->first_name . ' ' . $user_data->last_name;
			    $user_email = $user_data->user_email;
			    $user_id = $user['reg_userid'];

			    $html .= '
			    <div class="row user-row align-items-center border-bottom py-2" data-user-id="'.$user_id.'">
			        <div class="col-8"><strong>'.$user_name.'</strong> ('.$user_email.')</div>
			        <div class="col-3 text-end">'.$user['payment_count'].'</div>
			        <div class="col-1 text-end">
			            <a href="#" class="dashboard-user-details" data-user-id="'.$user_id.'" data-org="' . esc_attr($portal_user) . '">
			                <i class="fa-regular fa-square-plus"></i>
			            </a>
			        </div>
			    </div>
			    <div class="row user-details" id="user-details-'.$user_id.'" style="display:none;">
			        <div class="col-12">
			            <div class="py-2 px-3 border-start border-2 border-primary user-detail-list">
			                <em>Loading...</em>
			            </div>
			        </div>
			    </div>';
			}
			$html .= '<div class="text-end mt-2"><a href="#"" id="show-all-users" data-org="' . esc_attr($portal_user) . '">Show all users</a></div>';
			$html .= '<div id="user-stats-container"><!-- all user rows inserted here --></div>';
		}

		// icon key
		$html .= '<h4 class="mb-1 mt-5">Key</h4>';
		
		// Attendance icons
		$html .= '<h6 class="mb-0">Live training</h6>';
		$html .= '<p class="small"><i class="fa-solid fa-fw fa-circle-check text-success"></i> = Attended the live training<br>';
		$html .= '<i class="fa-solid fa-fw fa-video text-primary"></i> = Did not attend live but viewed the recording<br>';
		$html .= '<i class="fa-solid fa-fw fa-video-slash text-danger"></i> = Did not attend live training or watch recording before expiry<br>';
		$html .= '<i class="fa-solid fa-fw fa-hourglass-half"></i> = Training not started yet<br>';
		$html .= '<i class="fa-solid fa-fw fa-video text-warning"></i> = Did not attend live but can watch the recording<br>';
		$html .= '<i class="fa-solid fa-fw fa-circle-xmark text-danger"></i> = Did not attend the training (no recording available)<br>';
		$html .= '<i class="fa-solid fa-fw fa-person-chalkboard"></i> = Training in progress</p>';
		
		$html .= '<h6 class="mb-0">On-demand</h6>';
		$html .= '<p class="small"><i class="fa-solid fa-fw fa-circle-check text-success"></i> = Viewed the training<br>';
		$html .= '<i class="fa-solid fa-fw fa-circle-xmark text-danger"></i> = Training not watched before expiry<br>';
		$html .= '<i class="fa-solid fa-fw fa-hourglass-half"></i> = Training available to watch</p>';
		
		// Registration status indicators
		$html .= '<h6 class="mb-0">Registration status</h6>';
		$html .= '<p class="small"><span style="text-decoration: line-through;">Strikethrough text</span> = Registration was cancelled<br>';
		$html .= '<small class="text-muted">(S/G)</small> or <small class="text-muted">(Series/Group)</small> = Part of a series or group registration</p>';

	}else{
			return '<h3>You do not have access to this.</h3>';
	}
        $html .= '<div class="mb-5">&nbsp;</div>';

    

    return $html;
}
function dashboard_get_month_stats_filtered(
    $org,
    $year,
    $month,
    $filter_start,
    $filter_end,
    $service_type = '',
    $borough = '',
    $role = ''              // ✅ NEW
) {

    global $wpdb;

    $payments_table = $wpdb->prefix . 'ccpa_payments';
    $org_uc = strtoupper($org);

    $month_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $month_end   = date('Y-m-t 23:59:59', strtotime($month_start));

    $range_start = max($month_start, $filter_start);
    $range_end   = min($month_end, $filter_end);

    if ($range_start > $range_end) {
        return ['count' => 0, 'amount' => 0];
    }

    // -----------------------------------
    // Dynamic joins
    // -----------------------------------

    $join_service = '';
    $join_borough = '';
    $join_role    = '';

    if (!empty($service_type)) {
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

    $query = "
        SELECT
            COUNT(DISTINCT p.ID) AS count,
            SUM(p.disc_amount) AS amount
        FROM $payments_table p

        JOIN {$wpdb->users} u 
            ON u.ID = p.reg_userid

        $join_service
        $join_borough
        $join_role

        WHERE p.last_update BETWEEN %s AND %s
            AND p.DISC_CODE = %s
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

   // echo $query;
    $params = [];

    if (!empty($service_type)) {
        $params[] = $service_type;
    }

    if (!empty($borough)) {
        $params[] = $borough;
    }

    if (!empty($role)) {
        $params[] = $role;
    }

    $params[] = $range_start;
    $params[] = $range_end;
    $params[] = $org_uc;

    $result = $wpdb->get_row(
        $wpdb->prepare($query, $params),
        ARRAY_A
    );

    // Count outside-allowance (Add to Account) registrations separately
    $oa_query = "
        SELECT COUNT(DISTINCT p.ID) AS oa_count
        FROM $payments_table p
        JOIN {$wpdb->users} u ON u.ID = p.reg_userid
        $join_service
        $join_borough
        $join_role
        WHERE p.last_update BETWEEN %s AND %s
            AND p.disc_code = %s
            AND p.pmt_method = 'add_to_account'
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
    $oa_params = $params;
    $oa_result = $wpdb->get_row(
        $wpdb->prepare($oa_query, $oa_params),
        ARRAY_A
    );

    return [
        'count'             => !empty($result['count']) ? (int) $result['count'] : 0,
        'amount'            => !empty($result['amount']) ? (float) $result['amount'] : 0,
        'outside_allowance' => !empty($oa_result['oa_count']) ? (int) $oa_result['oa_count'] : 0,
    ];

}