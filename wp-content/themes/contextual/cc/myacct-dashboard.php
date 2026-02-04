<?php
/**
 * My account dashboard for org. admins
 */

function cc_myacct_dashboard( $org='' ){
	$user_id = get_current_user_id();
	$portal_user = get_user_meta( $user_id, 'portal_user', true );
	$portal_admin = get_user_meta( $user_id, 'portal_admin', true);

	if( ( $portal_user <> '' && $portal_admin == 'yes' ) || ( current_user_can( 'manage_options' ) && $org <> '' ) ){

		if( current_user_can( 'manage_options' ) && $org <> '' ){
			$portal_user = $org;
		}

		$html = '<h3 class="d-md-none">My profile</h3>';
		
        // Get the organization start date
        $start_date_str = dashboard_org_start_date($portal_user);
        $start_date = new DateTime($start_date_str);
        
        // Get total stats (keep existing function for totals)
        $regs = dashboard_get_reg_stats_top( $portal_user );

		$html .= '<h3>';
		switch ( $portal_user ) {
			case 'nlft':	$html .= 'North London NHS Foundation Trust'; 						break;
			case 'cnwl':	$html .= 'Central and North West London NHS Foundation Trust'; 		break;
		}
		$html .= '</h3>';

		$html .= '
			<h4 class="mb-0">Registrations</h4>
		    <div class="row align-items-center border-bottom py-2">
		        <div class="col-4"><strong>&nbsp;</strong></div>
		        <div class="col-4 text-end"><strong>Count</strong></div>
		        <div class="col-3 text-end"><strong>Value</strong></div>
		    </div>';

        // Generate array of all months from start date to current
        $month_rows = [];
        $current = new DateTime('first day of this month');
        $start = clone $start_date;
        $start->modify('first day of this month');

        // Build the months array
        while ($start <= $current) {
            $year = $start->format('Y');
            $month = $start->format('n'); // numeric month without leading zeros
            $month_key = $start->format('Y_m');
            $month_label = $start->format('M Y');
            
            // Get stats for this month
            if ($start->format('Y-m') === date('Y-m')) {
                // Current month - use existing data
                $month_rows[$month_key] = [
                    'label' => $month_label,
                    'count' => $regs['this_month_count'],
                    'amount' => $regs['this_month_amount'],
                    'key' => 'this_month'
                ];
            } elseif ($start->format('Y-m') === date('Y-m', strtotime('-1 month'))) {
                // Last month - use existing data
                $month_rows[$month_key] = [
                    'label' => $month_label,
                    'count' => $regs['last_month_count'],
                    'amount' => $regs['last_month_amount'],
                    'key' => 'last_month'
                ];
            } else {
                // Other months - fetch data
                $month_stats = dashboard_get_month_stats($portal_user, $year, $month);
                $month_rows[$month_key] = [
                    'label' => $month_label,
                    'count' => $month_stats['count'],
                    'amount' => $month_stats['amount'],
                    'key' => $start->format('Y-m') // Use YYYY-MM format for AJAX
                ];
            }
            
            $start->modify('+1 month');
        }
        
        // Reverse to show most recent first
        $month_rows = array_reverse($month_rows, true);

        // Display the months
        foreach ($month_rows as $month_key => $month_data) {
            $count = $month_data['count'];
            $amount = number_format($month_data['amount'], 2);
            $period_key = $month_data['key'];

            $html .= '
            <div class="row align-items-center border-bottom py-2">
                <div class="col-4"><strong>' . $month_data['label'] . '</strong></div>
                <div class="col-4 text-end">' . $count . '</div>
                <div class="col-3 text-end">&pound;' . $amount . '</div>
                <div class="col-1 text-end">
                    <a href="#" class="dashboard-toggle-details" data-period="' . esc_attr($period_key) . '" data-org="' . esc_attr($portal_user) . '">
                        <i class="fa-regular fa-square-plus"></i>
                    </a>
                </div>
            </div>
            <div class="row details-row" id="details-' . $period_key . '" style="display:none;">
                <div class="col-12">
                    <div class="py-2 px-3 border-start border-2 border-primary" id="details-content-' . $period_key . '">
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