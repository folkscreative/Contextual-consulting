<?php
/**
 * NLFT Stats (clone of CNWL stats, scoped to NLFT + YTD window)
 *
 * YTD window:
 *   1st Jan – 4th Feb (inclusive)
 */

// -----------------------------------------------------------------------------
// CONFIG
// -----------------------------------------------------------------------------

function cc_nlft_stats_start_date(){
    return '2026-01-01 00:00:00';
}

function cc_nlft_stats_end_date(){
    return '2026-02-04 23:59:59';
}

// -----------------------------------------------------------------------------
// ADMIN MENU
// -----------------------------------------------------------------------------

add_action( 'admin_menu', 'nlft_stats_menu' );
function nlft_stats_menu() {

    add_menu_page(
        'NLFT Stats',
        'NLFT Stats',
        'edit_posts',
        'nlft-stats',
        'nlft_stats_report',
        'dashicons-editor-table',
        37
    );
}

// -----------------------------------------------------------------------------
// MAIN REPORT
// -----------------------------------------------------------------------------

function nlft_stats_report(){

    $users = cc_nlft_get_users();

/*     $start = cc_nlft_stats_start_date();
    $end   = cc_nlft_stats_end_date();
 */
    $now = time();
    $today = date('Y-m-d H:i:s');

    $count_users = count($users);
    $count_w_reg = 0;
    $count_w_canc = 0;
    $count_w_future = 0;
    $count_w_view = 0;
    $count_w_dna = 0;
    $count_w_tot_dna = 0;
    $count_wr_view = 0;
    $count_wr_avail = 0;
    $count_r_reg = 0;
    $count_r_canc = 0;
    $count_r_view = 0;
    $count_r_dna = 0;
    $count_r_avail = 0;
    $count_w_reg_tots = array();
    $count_r_reg_tots = array();
    $sum_w_value = 0.0;
    $sum_r_value = 0.0;
    ?>
    <div class="wrap">
        <h2>NLFT Stats</h2>
         
		<div class="text-end">
			<a href="javascript:void(0);" id="nlft-export-req" class="button button-secondary">Generate CSV export</a><br>
			<span id="nlft-export-msg"></span>
		</div>
        <div id="poststuff">
            <div id="post-body">
                <table class="widefat striped cnwl-stats">
                    <thead>
                        <tr>
                            <th>Registered</th>
                            <th>Email</th>
							 <th>Borough</th>
                        <th>Service Type</th>
                        <th>Team</th>
                        <th>Profession</th>

                            <th class="cnwl-pmt-col" colspan="2">Pmt ID</th>
                            <th>Workshops</th>
                            <th>Reg. Price</th>
                            <th>Attend</th>
                            <th>Rec</th>
                            <th class="cnwl-pmt-col" colspan="2">Pmt ID</th>
                            <th>Recordings</th>
                            <th>Reg. Price</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php

                    foreach ($users as $user) {

                        $metas = get_user_meta($user->ID);
						     $borough      = get_user_meta($user->ID, 'nlft_borough', true);
        $service_type = get_user_meta($user->ID, 'nlft_service_type', true);
        $team         = get_user_meta($user->ID, 'nlft_team', true);
        $profession   = get_user_meta($user->ID, 'job', true);

                        $reg_recs = array();
                        $viewed_recs = array();
                        $closed_recs = array();

                        foreach ($metas as $key => $value) {

                            if(substr($key, 0, 13) == 'cc_rec_wshop_'){

                                $rec_id = substr($key, 13);

                                if( strpos( $rec_id, '_') > 0 ){
                                    continue;
                                }

                                $rec_data = maybe_unserialize($value[0]);
                                $rec_data = sanitise_recording_meta( $rec_data, $user->ID, $rec_id );


                                $payment_id = 'unk';
                                if(isset($rec_data['payment_id'])){
                                    $payment_id = $rec_data['payment_id'];
                                }

                                $reg_recs[] = array(
                                    'rec_id' => $rec_id,
                                    'pmt_id' => $payment_id,
                                );

                                if(isset($rec_data['num_views']) && $rec_data['num_views'] > 0){
                                    $viewed_recs[] = $rec_id;
                                }

                                if( isset($rec_data['closed_time']) && $rec_data['closed_time'] <> '' ){
                                    if( $rec_data['closed_time'] < $today ){
                                        $closed_recs[] = $rec_id;
                                    }
                                }
                            }
                        }

                        $viewed_wkshops = cc_ce_credits_user_attended( $user->ID );

                        $workshop_rows = array();

                        $wkshop_users = cc_myacct_get_workshops($user->ID, '', true);

                        $linked_recordings = array();

                        foreach ($wkshop_users as $wkshop_user) {

                            $payment_data = cc_paymentdb_get_payment($wkshop_user['payment_id']);


                            $pmt_html = $wkshop_user['payment_id'];
                            $reg_html = date('j/m/Y', strtotime($payment_data['last_update']));
                            $wkshop_html = $wkshop_user['workshop_id'].': '.get_the_title($wkshop_user['workshop_id']);

                            if($payment_data['status'] == 'Cancelled'){
                                $count_w_canc ++;
                                $cancelled = true;
                            }else{
                                $count_w_reg ++;
                                $cancelled = false;
                                if(isset($count_w_reg_tots[$wkshop_user['workshop_id']])){
                                    $count_w_reg_tots[$wkshop_user['workshop_id']] ++;
                                }else{
                                    $count_w_reg_tots[$wkshop_user['workshop_id']] = 1;
                                }
                            }

                            if($cancelled){

                                $viewed_html = '';
                                $viewrec_html = '';

                            }else{

                                $workshop_dna = false;

                                $workshop_timestamp = get_post_meta($wkshop_user['workshop_id'], 'workshop_timestamp', true);
                                if($workshop_timestamp == ''){
                                    $workshop_timestamp = get_post_meta($wkshop_user['workshop_id'], 'workshop_start_timestamp', true);
                                }

                                if($workshop_timestamp > $now){

                                    $count_w_future ++;
                                    $viewed_html = '##';

                                }else{

                                    if( in_array($wkshop_user['workshop_id'], $viewed_wkshops) ){
                                        $count_w_view ++;
                                        $viewed_html = '**';
                                    }else{
                                        $count_w_dna ++;
                                        $viewed_html = 'X';
                                        $workshop_dna = true;
                                    }
                                }

                                $linked_rec_dna = false;

                                $linked_rec_id = get_post_meta($wkshop_user['workshop_id'], 'workshop_recording', true);

                                if($linked_rec_id <> ''){

                                    $linked_recordings[] = $linked_rec_id;

                                    if(in_array($linked_rec_id, $viewed_recs)){

                                        if(!in_array($wkshop_user['workshop_id'], $viewed_wkshops)){
                                            $count_wr_view ++;
                                        }

                                        $viewrec_html = '$$';

                                    }else{

                                        if( in_array($linked_rec_id, $closed_recs) ){
                                            $viewrec_html = 'X';
                                            $linked_rec_dna = true;
                                        }else{
                                            $viewrec_html = '##';
                                            if(!in_array($wkshop_user['workshop_id'], $viewed_wkshops)){
                                                $count_wr_avail ++;
                                            }
                                        }
                                    }

                                }else{
                                    $viewrec_html = '-';
                                    $linked_rec_dna = true;
                                }

                                if($workshop_dna && $linked_rec_dna){
                                    $count_w_tot_dna ++;
                                }
                            }

                            if( $cancelled ){
                                $pricing_html = '';
                            }else{
                                $pricing = cc_payment_non_disc_amount( $payment_data, 'GBP' );
                                $pricing_html = cc_money_format( $pricing['amount'], $pricing['currency'] );
                                $sum_w_value += (float)$pricing['amount'];
                            }

                            $workshop_rows[] = array(
                                $cancelled,
                                $pmt_html,
                                $reg_html,
                                $wkshop_html,
                                $viewed_html,
                                $viewrec_html,
                                $pricing_html
                            );
                        }

                        $recording_rows = array();

                        foreach ($reg_recs as $rec_ids) {

                            if(in_array($rec_ids['rec_id'], $linked_recordings)) continue;
                            if($rec_ids['pmt_id'] == 0) continue;

                            $pmt_html = $rec_ids['pmt_id'];
                            $rec_html = $rec_ids['rec_id'].': '.get_the_title( $rec_ids['rec_id'] );

                            $cancelled = false;

                            if($rec_ids['pmt_id'] > 0){

                                $payment_data = cc_paymentdb_get_payment($rec_ids['pmt_id']);


                                $reg_html = date('j/m/Y', strtotime($payment_data['last_update']));

                                if($payment_data['status'] == 'Cancelled'){
                                    $cancelled = true;
                                    $count_r_canc ++;
                                }else{
                                    $count_r_reg ++;
                                    if(isset($count_r_reg_tots[$rec_ids['rec_id']])){
                                        $count_r_reg_tots[$rec_ids['rec_id']] ++;
                                    }else{
                                        $count_r_reg_tots[$rec_ids['rec_id']] = 1;
                                    }
                                }
                            }

                            if($cancelled){
                                $viewed_html = '';
                            }else{

                                if( in_array($rec_ids['rec_id'], $viewed_recs) ){
                                    $count_r_view ++;
                                    $viewed_html = '**';
                                }else{
                                    if( in_array($rec_ids['rec_id'], $closed_recs) ){
                                        $viewed_html = 'X';
                                        $count_r_dna ++;
                                    }else{
                                        $viewed_html = '##';
                                        $count_r_avail ++;
                                    }
                                }
                            }

                            if( $cancelled ){
                                $pricing_html = '';
                            }else{
                                $pricing = cc_payment_non_disc_amount( $payment_data, 'GBP' );
                                $pricing_html = cc_money_format( $pricing['amount'], $pricing['currency'] );
                                $sum_r_value += (float)$pricing['amount'];
                            }

                            $recording_rows[] = array(
                                $cancelled,
                                $pmt_html,
                                $reg_html,
                                $rec_html,
                                $viewed_html,
                                $pricing_html
                            );
                        }

                        $user_rows = max(count($workshop_rows), count($recording_rows));

                        if($user_rows > 0){

                            for ($i=0; $i < $user_rows; $i++) {

                                echo '<tr>';

                                if($i == 0){
									echo '<td valign="top">'.date('d/m/Y', strtotime($user->user_registered)).'</td>';
									echo '<td valign="top">'.$user->user_email.'</td>';
									echo '<td>'.esc_html($borough).'</td>';
									echo '<td>'.esc_html($service_type).'</td>';
									echo '<td>'.esc_html($team).'</td>';
									echo '<td>'.esc_html($profession).'</td>';

                                }else{
                                    echo '<td colspan="6">&nbsp;</td>';
                                }

                                if( isset( $workshop_rows[$i] ) ){

                                    $td_class = ($workshop_rows[$i][0] === true) ? 'cancelled' : '';

                                    echo '<td class="cnwl-pmt-col '.$td_class.'" valign="top">'.$workshop_rows[$i][1].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$workshop_rows[$i][2].'</td>';
                                    echo '<td class="truncate '.$td_class.'" valign="top">'.$workshop_rows[$i][3].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$workshop_rows[$i][6].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$workshop_rows[$i][4].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$workshop_rows[$i][5].'</td>';

                                }else{
                                    echo '<td colspan="6" class="cnwl-pmt-col">&nbsp;</td>';
                                }

                                if( isset( $recording_rows[$i] ) ){

                                    $td_class = ($recording_rows[$i][0] === true) ? 'cancelled' : '';

                                    echo '<td class="cnwl-pmt-col '.$td_class.'" valign="top">'.$recording_rows[$i][1].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$recording_rows[$i][2].'</td>';
                                    echo '<td class="truncate '.$td_class.'" valign="top">'.$recording_rows[$i][3].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$recording_rows[$i][5].'</td>';
                                    echo '<td class="'.$td_class.'" valign="top">'.$recording_rows[$i][4].'</td>';

                                }else{
                                    echo '<td colspan="5" class="cnwl-pmt-col">&nbsp;</td>';
                                }

                                echo '</tr>';
                            }
                        }
                    }
                    ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="2">Registered users: <?php echo $count_users; ?></td>
                            <td class="cnwl-pmt-col" colspan="3">Workshop registrations (excl canc.)</td>
                            <td><?php echo cc_money_format( $sum_w_value, 'GBP' ); ?></td>
                            <td colspan="2"><?php echo $count_w_reg; ?></td>
                            <td class="cnwl-pmt-col" colspan="3">Recording registrations (excl canc.)</td>
                            <td><?php echo cc_money_format( $sum_r_value, 'GBP' ); ?></td>
                            <td><?php echo $count_r_reg; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td class="cnwl-pmt-col" colspan="4">Workshop cancellations</td>
                            <td colspan="2"><?php echo $count_w_canc; ?></td>
                            <td class="cnwl-pmt-col" colspan="4">Recording cancellations</td>
                            <td><?php echo $count_r_canc; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td class="cnwl-pmt-col" colspan="4">## = Workshop future / recording viewable</td>
                            <td><?php echo $count_w_future; ?></td>
                            <td><?php echo $count_wr_avail; ?></td>
                            <td class="cnwl-pmt-col" colspan="4">## = Recording viewable</td>
                            <td><?php echo $count_r_avail; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td class="cnwl-pmt-col" colspan="4">** = Workshop attended live</td>
                            <td><?php echo $count_w_view; ?></td>
                            <td></td>
                            <td class="cnwl-pmt-col" colspan="4">** = Recording viewed</td>
                            <td><?php echo $count_r_view; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td class="cnwl-pmt-col" colspan="4">$$ = Recording watched (workshop)</td>
                            <td></td>
                            <td><?php echo $count_wr_view; ?></td>
                            <td class="cnwl-pmt-col" colspan="4">X = Recording DNA</td>
                            <td><?php echo $count_r_dna; ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td class="cnwl-pmt-col" colspan="4">X = Did not attend workshop or view recording</td>
                            <td></td>
                            <td><?php echo $count_w_tot_dna; ?></td>
                            <td class="cnwl-pmt-col" colspan="5"></td>
                        </tr>
                    </tfoot>

                </table>
            </div>
        </div>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// NLFT USERS
// -----------------------------------------------------------------------------

function cc_nlft_get_users(){

    return get_users(array(
        'meta_key'   => 'portal_user',
        'meta_value' => 'nlft',
        'orderby'    => 'registered',
    ));
}



// ajax call to generate the CNWL stats as a CSV file
add_action( 'wp_ajax_nlft_generate_csv', 'cc_nlft_generate_csv' );
function cc_nlft_generate_csv(){
	$response = array(
		'status' => 'error',
		'msg' => '',
	);
	$filename = 'NLFT-stats-'.date('Y-m-d-H-i-s').'.csv';

	ob_start();
	header('Content-Encoding: UTF-8');
	header('Content-type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $file_url = ABSPATH.'/'.$filename;
    $file = fopen($file_url, 'w');
    //add BOM to fix UTF-8 in Excel
	fputs( $file, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    $fields = array( 'Registered', 'Email', 'Borough','Service Type','Team','Profession','Pmt ID', '', 'Workshops', 'Reg. Price', 'Attend', 'Rec', 'Pmt ID', '', 'Recordings', 'Reg. Price', 'View' );
    fputcsv($file, $fields);

    $users = cc_nlft_get_users();

	$now = time();
	$today = date('Y-m-d H:i:s');
	$count_users = count($users);
	$count_w_reg = 0;
	$count_w_canc = 0;
	$count_w_future = 0;
	$count_w_view = 0;
	$count_w_dna = 0;
	$count_w_tot_dna = 0;
	$count_wr_view = 0;
	$count_wr_avail = 0; // workshop recording viewable unless workshop was attended
	$count_r_reg = 0;
	$count_r_canc = 0;
	$count_r_view = 0;
	$count_r_dna = 0;
	$count_r_avail = 0;
	$count_w_reg_tots = array();
	$count_r_reg_tots = array();
	$sum_w_value = 0;
	$sum_r_value = 0;
    $row_count = 0;

	foreach ($users as $user){
		$metas = get_user_meta($user->ID); // gets everything!
		// $viewed_wkshops = array();

		
        $borough      = get_user_meta($user->ID, 'nlft_borough', true);
        $service_type = get_user_meta($user->ID, 'nlft_service_type', true);
        $team         = get_user_meta($user->ID, 'nlft_team', true);
        $profession   = get_user_meta($user->ID, 'job', true);
		$reg_recs = array();
		$viewed_recs = array();
		$closed_recs = array();
		foreach ($metas as $key => $value) {
			if(substr($key, 0, 13) == 'cc_rec_wshop_'){
				$rec_id = substr($key, 13);
				if( strpos( $rec_id, '_') > 0 ){
					// this is for a section, not the main vid, so ignore it
					continue;
				}
				$rec_data = maybe_unserialize($value[0]);
				$rec_data = sanitise_recording_meta( $rec_data, $user->ID, $rec_id );
				if($rec_data['access_time'] < '2022-06-15 00:00:00') continue;
				$payment_id = 'unk';
				if(isset($rec_data['payment_id'])){
					$payment_id = $rec_data['payment_id'];
				}
				$reg_recs[] = array(
					'rec_id' => $rec_id,
					'pmt_id' => $payment_id,
				);
				if(isset($rec_data['num_views']) && $rec_data['num_views'] > 0){
					$viewed_recs[] = $rec_id;
				}
				if( isset($rec_data['closed_time']) && $rec_data['closed_time'] <> '' ){
					if( $rec_data['closed_time'] < $today ){
						$closed_recs[] = $rec_id;
					}
				}
			/* in-person training does not use zoom links so we'll drop this next bit and check the attendance file instead
			}elseif(substr($key, 0, 9) == 'zoomed w:'){
				// something like 'zoomed w:3775 e:null'
				$key_bits = explode(' ', $key);
				$viewed_wkshops[] = substr($key_bits[1], 2);
			*/
			}
		}

		$viewed_wkshops = cc_ce_credits_user_attended( $user->ID );

		$workshop_rows = array();

		$wkshop_users = cc_myacct_get_workshops($user->ID, '', true);
		$pmt_html = $wkshop_html = $viewed_html = $viewrec_html = $canc_html = $future_html = $reg_html = '';
		$linked_recordings = array();

		foreach ($wkshop_users as $wkshop_user) {
			$payment_data = cc_paymentdb_get_payment($wkshop_user['payment_id']);
			// CNWL started on 15/6/2022
			if($payment_data['last_update'] < '2022-06-15 00:00:00') continue;

			$pmt_html = $wkshop_user['payment_id'];

			$reg_html = date('j/m/Y', strtotime($payment_data['last_update']));

			$wkshop_html = $wkshop_user['workshop_id'].': ';
			$wkshop_html .= html_entity_decode( get_the_title( $wkshop_user['workshop_id'] ) );

			if($payment_data['status'] == 'Cancelled'){
				$count_w_canc ++;
				$cancelled = true;
			}else{
				$count_w_reg ++;
				$cancelled = false;

				// unlike the on-screen function above, the csv function needs to use this array in a way that has sequential keys not workshop_id keys
				// therefore looking for the workshop_id in the array is a bit different ...
				$key = array_search( $wkshop_user['workshop_id'], array_column( $count_w_reg_tots, 'workshop_id') );
				if( $key === false ){
					// new workshop
					$count_w_reg_tots[] = array(
						'workshop_id' => $wkshop_user['workshop_id'],
						'count' => 1,
					);
				}else{
					// workshop in array already
					$count_w_reg_tots[$key]['count'] ++;
				}
			}

			if($cancelled){
				$viewed_html = '';
				$viewrec_html = '';
			}else{
				// attended?
				$workshop_dna = false;
		        $workshop_timestamp = get_post_meta($wkshop_user['workshop_id'], 'workshop_timestamp', true);
		        if($workshop_timestamp == ''){
		            $workshop_timestamp = get_post_meta($wkshop_user['workshop_id'], 'workshop_start_timestamp', true);
		        }
				if($workshop_timestamp > $now){
					$count_w_future ++;
					$viewed_html = '##';
				}else{
					if( in_array($wkshop_user['workshop_id'], $viewed_wkshops) ){
						$count_w_view ++;
						$viewed_html = '**';
					}else{
						$count_w_dna ++;
						$viewed_html = 'X';
						$workshop_dna = true;
					}
				}

				// viewed the recording?
				$linked_rec_dna = false;
				$linked_rec_id = get_post_meta($wkshop_user['workshop_id'], 'workshop_recording', true);
				if($linked_rec_id <> ''){
					$linked_recordings[] = $linked_rec_id;
					if(in_array($linked_rec_id, $viewed_recs)){
						if(in_array($wkshop_user['workshop_id'], $viewed_wkshops)){
							// watched live and the recording
						}else{
							// watched recording only
							$count_wr_view ++;
						}
						$viewrec_html = '$$'; // watched the recording
					}else{
						// not watched the recording (yet)
						if( in_array($linked_rec_id, $closed_recs) ){
							// too late to watch it now
							$viewrec_html = 'X';
							$linked_rec_dna = true;
						}else{
							$viewrec_html = '##';
							if(!in_array($wkshop_user['workshop_id'], $viewed_wkshops)){
								// workshop not attended and recording available to view
								$count_wr_avail ++;
							}
						}
					}
				}else{
					$viewrec_html = '-';
					$linked_rec_dna = true;
				}
				if($workshop_dna && $linked_rec_dna){
					$count_w_tot_dna ++;
				}
			}

			// original pricing
			if( $cancelled ){
				$pricing_html = '';
			}else{
				$pricing = cc_payment_non_disc_amount( $payment_data, 'GBP' );
				$pricing_html = cc_money_format( $pricing['amount'], $pricing['currency'] );
				$sum_w_value = $sum_w_value + $pricing['amount'];
			}

			$workshop_rows[] = array( $cancelled, $pmt_html, $reg_html, $wkshop_html, $viewed_html, $viewrec_html, $pricing_html );
		}

		$recording_rows = array();

		$pmt_html = $rec_html = $viewed_html = $canc_html = $reg_html = '';
		
		foreach ($reg_recs as $rec_ids) {

			if(in_array($rec_ids['rec_id'], $linked_recordings)) continue;

			if($rec_ids['pmt_id'] == 0) continue;

			$pmt_html = $rec_ids['pmt_id'];

			$reg_html = date('j/m/Y', strtotime($payment_data['last_update']));

			$rec_html = $rec_ids['rec_id'].': ';
			$rec_html .= html_entity_decode( get_the_title( $rec_ids['rec_id'] ) );

			$cancelled = false;
			if($rec_ids['pmt_id'] > 0){
				$payment_data = cc_paymentdb_get_payment($rec_ids['pmt_id']);
				if($payment_data['status'] == 'Cancelled'){
					$cancelled = true;
					$count_r_canc ++;
				}else{
					$count_r_reg ++;
					$key = array_search( $rec_ids['rec_id'], array_column( $count_r_reg_tots, 'recording_id') );
					if( $key === false ){
						// new recording
						$count_r_reg_tots[] = array(
							'recording_id' => $rec_ids['rec_id'],
							'count' => 1,
						);
					}else{
						// recording in array already
						$count_r_reg_tots[$key]['count'] ++;
					}
				}
			}

			// viewed?
			if($cancelled){
				$viewed_html = '';
			}else{
				if( in_array($rec_ids['rec_id'], $viewed_recs) ){
					$count_r_view ++;
					$viewed_html = '**';
				}else{
					if( in_array($rec_ids['rec_id'], $closed_recs) ){
						$viewed_html = 'X';
						$count_r_dna ++;
					}else{
						$viewed_html = '##';
						$count_r_avail ++;
					}
				}
			}

			// original pricing
			if( $cancelled ){
				$pricing_html = '';
			}else{
				$pricing = cc_payment_non_disc_amount( $payment_data, 'GBP' );
				$pricing_html = cc_money_format( $pricing['amount'], $pricing['currency'] );
				$sum_r_value = $sum_r_value + (float)$pricing['amount'];
			}

			$recording_rows[] = array( $cancelled, $pmt_html, $reg_html, $rec_html, $viewed_html, $pricing_html );
			
		}

		$user_rows = count($workshop_rows);
		if(count($recording_rows) > $user_rows){
			$user_rows = count($recording_rows);
		}

		if($user_rows > 0){
			for ($i=0; $i < $user_rows; $i++) {
				$row = array();
				
				if($i == 0){
    $row = array(
        date('d/m/Y', strtotime($user->user_registered)),
        $user->user_email,
        $borough ?: '',
        $service_type ?: '',
        $team ?: '',
        $profession ?: '',
    );
}else{
    $row = array_fill(0, 6, '');
}
				if( isset( $workshop_rows[$i] ) ){
					if( $workshop_rows[$i][0] === true ){
						$td_class = 'cancelled';
					}else{
						$td_class = '';
					}
					$row[] = $workshop_rows[$i][1];
					$row[] = $workshop_rows[$i][2];
					$row[] = $workshop_rows[$i][3];
					$row[] = $workshop_rows[$i][6];
					if( $td_class == 'cancelled' ){
						$row[] = 'cancelled';
						$row[] = '';
					}else{
						$row[] = $workshop_rows[$i][4];
						$row[] = $workshop_rows[$i][5];
					}
				}else{
					$row[] = '';
					$row[] = '';
					$row[] = '';
					$row[] = '';
					$row[] = '';
					$row[] = '';
				}
				if( isset( $recording_rows[$i] ) ){
					if( $recording_rows[$i][0] === true ){
						$td_class = 'cancelled';
					}else{
						$td_class = '';
					}
					$row[] = $recording_rows[$i][1];
					$row[] = $recording_rows[$i][2];
					$row[] = $recording_rows[$i][3];
					$row[] = $recording_rows[$i][5];
					if( $td_class == 'cancelled' ){
						$row[] = 'cancelled';
					}else{
						$row[] = $recording_rows[$i][4];
					}
				}else{
					$row[] = '';
					$row[] = '';
					$row[] = '';
					$row[] = '';
					$row[] = '';
				}
		        fputcsv($file, $row);
		        $row_count ++;
			}
		}
	}

	$row = array();
	$row[] = 'Registered users: '.$count_users;
	$row[] = '';
	$row[] = 'Workshop registrations (excl canc.):';
	$row[] = '';
	$row[] = '';
	$row[] = cc_money_format( $sum_w_value, 'GBP' );
	$row[] = $count_w_reg;
	$row[] = '';
	$row[] = 'Recording registrations (excl canc.):';
	$row[] = '';
	$row[] = '';
	$row[] = cc_money_format( $sum_r_value, 'GBP' );
	$row[] = $count_r_reg;
	fputcsv($file, $row);

	$row = array();
	$row[] = '';
	$row[] = '';
	$row[] = 'Workshop cancellations';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_w_canc;
	$row[] = '';
	$row[] = 'Recording cancellations';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_r_canc;
	fputcsv($file, $row);

	$row = array();
	$row[] = '';
	$row[] = '';
	$row[] = '## = Workshop in future/recording viewable:';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_w_future;
	$row[] = $count_wr_avail;
	$row[] = '## = Recording viewable:';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_r_avail;
	fputcsv($file, $row);

	$row = array();
	$row[] = '';
	$row[] = '';
	$row[] = '** = Workshop attended live:';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_w_view;
	$row[] = '';
	$row[] = '** = Recording viewed:';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_r_view;
	fputcsv($file, $row);

	$row = array();
	$row[] = '';
	$row[] = '';
	$row[] = '$$ = Workshop recording viewed. Count = watched recording only:';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_wr_view;
	$row[] = 'X = Did not view in time';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_r_dna;
	fputcsv($file, $row);

	$row = array();
	$row[] = '';
	$row[] = '';
	$row[] = 'X = Did not attend workshop or view recording';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = $count_w_tot_dna;
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	$row[] = '';
	fputcsv($file, $row);

	$training_rows = count( $count_w_reg_tots );
	if( count( $count_r_reg_tots ) > $training_rows ){
		$training_rows = count( $count_r_reg_tots );
	}
	for ( $i=0; $i < $training_rows; $i++ ){
		$row = array();
		if( $i == 0 ){
			$row[] = 'Registrations by training';
		}else{
			$row[] = '';
		}
		$row[] = '';
		$row[] = '';
		$row[] = '';
		if( isset( $count_w_reg_tots[$i] ) ){
			$row[] = $count_w_reg_tots[$i]['workshop_id'].': '.html_entity_decode( get_the_title( $count_w_reg_tots[$i]['workshop_id'] ) );
			$row[] = $count_w_reg_tots[$i]['count'];
		}else{
			$row[] = '';
			$row[] = '';
		}
		$row[] = '';
		$row[] = '';
		$row[] = '';
		$row[] = '';
		if( isset( $count_r_reg_tots[$i] ) ){
			$row[] = $count_r_reg_tots[$i]['recording_id'].': '.html_entity_decode( get_the_title( $count_r_reg_tots[$i]['recording_id'] ) );
			$row[] = $count_r_reg_tots[$i]['count'];
		}else{
			$row[] = '';
			$row[] = '';
		}
		$row[] = '';
		fputcsv($file, $row);
	}

    fclose($file);
    ob_end_flush();

    $response['status'] = 'ok';
    $response['msg'] = '<a href="/'.$filename.'" target="_blank">Download Export File</a>';

   	echo json_encode($response);
	die();
}




add_shortcode('feedback_analysis', function() {
    $download_url = add_query_arg('download_feedback_csv', '1', site_url());
    return '<a href="' . esc_url($download_url) . '" class="button">Download Feedback CSV</a>';
});

add_action('init', 'nlft_download_feedback_csv');

function nlft_download_feedback_csv() {
    if (!isset($_GET['download_feedback_csv'])) return;

    global $wpdb;

    $table_name = $wpdb->prefix . 'feedback'; // 🔁 UPDATE THIS

    $start_date = '2024-04-01 00:00:00';
    $end_date   = '2025-03-31 23:59:59';

    // Get feedback rows for users with portal_user = cnwl
    $feedback_rows = $wpdb->get_results($wpdb->prepare("
        SELECT f.training_id, f.user_id, f.feedback 
        FROM $table_name f
        INNER JOIN {$wpdb->usermeta} um ON f.user_id = um.user_id
        WHERE um.meta_key = 'portal_user' AND um.meta_value = 'nlft'
        AND f.updated BETWEEN %s AND %s
    ", $start_date, $end_date));

    if (empty($feedback_rows)) {
        wp_die('No CNWL feedback found.');
    }

    // Group feedback by training ID
    $grouped_feedback = [];
    foreach ($feedback_rows as $row) {
        $grouped_feedback[$row->training_id][] = $row;
    }

    // Start CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_export.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    foreach ($grouped_feedback as $training_id => $rows) {
        $title = get_the_title($training_id);
        $raw_questions = get_post_meta($training_id, '_feedback_questions', true);
        if (!$raw_questions) continue;

        preg_match_all('/\[fb_form\s+([^\]]+)\]/', $raw_questions, $matches);
        $question_map = [];

        foreach ($matches[1] as $attr_string) {
            preg_match_all('/(\w+)="([^"]*)"/', $attr_string, $attrs, PREG_SET_ORDER);
            $attr_array = [];
            foreach ($attrs as $attr) {
                $attr_array[strtolower($attr[1])] = $attr[2];
            }
            if (isset($attr_array['name'], $attr_array['question'])) {
                $question_map[$attr_array['name']] = $attr_array['question'];
            }
        }

        // Header row
        $header = ['Training ID', 'Training Title'];
        foreach ($question_map as $question_text) {
            $header[] = $question_text;
        }
        fputcsv($output, $header);

        // Feedback data rows
        foreach ($rows as $row) {
            $feedback = maybe_unserialize($row->feedback);
            if (!is_array($feedback)) continue;

            $row_data = [$training_id, $title];
            foreach ($question_map as $key => $_) {
                $matched_value = '';
                foreach ($feedback as $fb_key => $fb_value) {
                    if (strtolower($fb_key) === strtolower($key)) {
                        $matched_value = $fb_value;
                        break;
                    }
                }
                $row_data[] = $matched_value;
            }

            fputcsv($output, $row_data);
        }
    }

    fclose($output);
    exit;
}
