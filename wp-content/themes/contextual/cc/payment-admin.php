<?php
/**
 * Payments Admin page
 */

add_action('admin_menu', 'cc_payment_admin_pages');
function cc_payment_admin_pages(){
	add_menu_page('Payments', 'Payments', 'manage_options', 'ccpa_payments', 'ccpa_payments_page', 'dashicons-admin-settings');
	add_submenu_page( 'ccpa_payments', 'Edit Payment', 'Edit Payment', 'manage_options', 'edit_payment', 'ccpa_edit_payment' );
}

function ccpa_payments_page(){
	// ccpa_write_log('ccpa_payments_page');
	global $wpdb;
	$table_name = $wpdb->prefix.'ccpa_payments';
	?>
	<style>
		.ccpa-export-btn-wrap {float: right;}
		.ccpa-edit-table-wrap {background: #fff; border: 1px solid #ccc; margin-bottom: 20px; border-radius: 10px; padding: 15px;}
		.ccpa-edit-table-head {font-size:20px;}
		.ccpa-edit-table td {width: 500px;}
		.ccpa-edit-table select.wide, .ccpa-edit-table input[type='text'] {width: 100%;}
		.ccpa-top-msg {padding: 15px;}
		.ccpa-top-msg.good {background:#28a745; color:#fff; }
		.ccpa-top-msg.bad {background:#dc3545; color:#fff; }
		.ccpa-pmts-table th {text-align: left;}
		.ccpa-pmts-table thead {background: #ccc;}
		.ccpa-pmts-table td {vertical-align: top;}
		.ccpa-pmts-table .even {background: #ddd;}
		.ccpa-page-links td {width: 1000px;}
		.ccpa-search-wrap {margin-bottom: 10px;}
	</style>
	<div class="ccpa-heading-btn-wrap">
		<div class="ccpa-export-btn-wrap">
			<?php
			$form_link = add_query_arg( array(
			    'page' => 'ccpa_payments',
			), admin_url() );
			?>
			<form action="<?php echo $form_link; ?>" method="post">
				<select name="workshops" id="workshops">
					<option value="">All</option>
					<?php echo ccpa_workshop_options(); ?>
				</select>
				<input id="ccpa-export-go" name="ccpa-export-go" class="button button-primary" type="submit" value="Export Payments">
			</form>
		</div>
		<h1>Payments</h1>
	</div>
	<?php
	$top_msg = '';
	$msg_class = '';
	if(isset($_POST['ccpa-export-go'])){
		$filename = 'payments-'.date('Y-m-d-H-i-s').'.csv';
		$where = '';
		if(isset($_POST['workshops']) && $_POST['workshops'] <> ''){
			$workshop_id = (int) $_POST['workshops'];
			$where = "WHERE workshop_id = $workshop_id OR upsell_workshop_id = $workshop_id";
		}
		$sql = "SELECT * FROM $table_name $where ORDER BY last_update DESC";
		$pmts = $wpdb->get_results($sql, ARRAY_A);
		ob_start();
		header('Content-Encoding: UTF-8');
		header('Content-type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $file_url = ABSPATH.'/'.$filename;
        $file = fopen($file_url, 'w');
        $fields = array_keys(cc_paymentdb_empty_payment());
        fputcsv($file, $fields);
        $row_count = 0;
        foreach ($pmts as $pmt) {
        	$row = array();
        	foreach ($fields as $field) {
        		if($field == 'stripe_fee' && $pmt['stripe_fee'] == 0){
        			$row[] = ccpa_stripe_fee($pmt, true);
        		}else{
        			$row[] = $pmt[$field];
        		}
        	}
            fputcsv($file, $row);
            $row_count ++;
        }
        fclose($file);
        ob_end_flush();
        ?>
        <a href="/<?php echo $filename; ?>" target="_blank">Download Export File</a>
        <?php
        exit;
	}
	if(isset($_GET['dpmt']) && absint($_GET['dpmt']) > 0){
		$payment_id = (int) $_GET['dpmt'];
		if($wpdb->delete( $table_name, array('id' => absint($payment_id)), array('%d')) === false){
			$top_msg = 'Error attempting to delete row. Please try again';
			$msg_class = 'bad';
		}else{
			// also delete the attendees and the workshops and recordings users tables
			$result = cc_attendees_delete_for_payment($payment_id);
			$result = cc_myacct_workshops_users_delete_payment($payment_id);
			$result = cc_myacct_recordings_users_delete_payment($payment_id);
			$top_msg = 'Payment '.absint($payment_id).' successfully deleted.';
			$msg_class = 'good';
		}
	}
	$this_page = 1;
	$showpp = 300; // number to show per page
	if(isset($_GET['show'])){
		$showpp = absint($_GET['show']);
		if($showpp == 0) $showpp = 300;
	}
	if(isset($_GET['ppmt'])){
		$this_page = absint($_GET['ppmt']);
		if($this_page == 0) $this_page = 1;
	}
	$start = 0;
	if($this_page > 1){
		$start = ($this_page - 1) * $showpp;
	}
	$where = '';
	if(isset($_GET['srch']) && $_GET['srch'] <> ''){
		$safe_search = sanitize_text_field($_GET['srch']);
		$where = "WHERE CONCAT_WS(' ',firstname,lastname,email) LIKE '%$safe_search%'";
	}elseif( isset($_GET['wk']) ){
		// ccpa_write_log('wk set to '.$_GET['wk'].'.');
		$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $_GET['wk'].' 00:00:00', new DateTimeZone('UTC'));
		if($datetime){
			$start_date = $datetime->format('Y-m-d H:i:s');
			$datetime->modify('+7 days');
			$end_date = $datetime->format('Y-m-d H:i:s');
			$where = "WHERE last_update >= '$start_date' AND last_update < '$end_date'";
		}
		if(isset($_GET['t'])){
			$where .= " AND ( workshop_id = ".absint($_GET['t'])." OR upsell_workshop_id = ".absint($_GET['t'])." )";
		}
	}elseif( isset($_GET['day']) ){
		$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $_GET['day'].' 00:00:00', new DateTimeZone('UTC'));
		if($datetime){
			$start_date = $datetime->format('Y-m-d H:i:s');
			$datetime->modify('+1 day');
			$end_date = $datetime->format('Y-m-d H:i:s');
			$where = "WHERE last_update >= '$start_date' AND last_update < '$end_date'";
		}
		if(isset($_GET['t'])){
			$where .= " AND ( workshop_id = ".absint($_GET['t'])." OR upsell_workshop_id = ".absint($_GET['t'])." )";
		}
	}
	$stop = $showpp + 1;
	$sql = "SELECT * FROM $table_name $where ORDER BY last_update DESC LIMIT $start, $stop";
	$pmts = $wpdb->get_results($sql, ARRAY_A);
	$more = false;
	if(count($pmts) > $showpp){
		array_pop($pmts);
		$more = true;
	}
	if($top_msg <> ''){ ?>
		<div class="ccpa-top-msg <?php echo $msg_class; ?>"><?php echo $top_msg; ?></div>
	<?php } ?>
	<div class="ccpa-search-wrap">
		<form action="<?php echo admin_url(); ?>" method="get">
			<input type="hidden" name="page" value="ccpa_payments">
			<table>
				<tr>
					<td><strong><label for="srch">Search:</label></strong></td>
					<td width="400px"><input type="text" id="srch" name="srch" class="widefat" placeholder="Enter part of name or email (leave blank for all)"></td>
					<td><strong><label for="srch">Results per page:</label></strong></td>
					<td width="150px"><input type="number" id="show" name="show" class="" value="<?php echo $showpp; ?>"></td>
					<td><input type="submit" id="ccpa-search-submit" name="" class="button button-primary" value="Search/refresh"></td>
				</tr>
			</table>
		</form>
	</div>
	<table class="table table-condensed ccpa-pmts-table">
		<thead>
			<tr>
				<th>Type</th>
				<th>Updated</th>
				<th>Status</th>
				<th>Email</th>
				<th>Amount</th>
				<th>Title</th>
				<th>Name</th>
				<th>Attendee</th>
				<th>Address</th>
				<th>Stripe Payment</th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<th>ID</th>
				<th>Phone</th>
				<th>Pmt Method</th>
				<th>Attendee email</th>
				<th>Discount</th>
				<th>Promo code</th>
				<th>Source</th>
				<th>Webcast</th>
				<th>Inv Address</th>
				<th>Notes</th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<th>Inv Phone</th>
				<th>VAT Exempt</th>
				<th>Inv Email</th>
				<th>VAT Incl.</th>
				<th>Mailing List</th>
				<th>Inv Ref</th>
				<th>T&amp;Cs</th>
				<th colspan="3">Workshop</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$row_class = 'even';
			foreach ($pmts as $pmt) {
				if($row_class == 'even'){
					$row_class = 'odd';
				}else{
					$row_class = 'even';
				}
				$curr_sign = workshops_price_prefix($pmt['currency']);

				$attendees = cc_attendees_for_payment($pmt['id']);
				$attendee_names = $attendee_emails = '';
				$break = false;
				foreach ($attendees as $attendee) {
					if($break){
						$attendee_names .= '<br>';
						$attendee_emails .= '<br>';
					}
					$break = true;
					if($attendee['registrant'] == 'r'){
						$attendee_names .= 'registrant';
						$attendee_emails .= 'registrant';
					}else{
						$user = get_user_by('ID', $attendee['user_id']);
						if($user){
							$attendee_names .= $user->first_name . ' ' . $user->last_name;
							$attendee_emails .= $user->user_email;
						}else{
							$attendee_names .= 'Unknown';
							$attendee_emails .= 'Unknown user ID '.$attendee['user_id'];
						}
					}
				}

				echo '<tr class="'.$row_class.'"><td>';
				if($pmt['type'] == 'recording'){
					echo 'Rec';
				}elseif( $pmt['type'] == 'series' ){
					echo 'Ser';
				}elseif( $pmt['type'] == 'auto' ){
					echo 'Auto';
				}elseif( $pmt['type'] == 'group' ){
					echo 'Group';
				}else{
					echo 'Wksp';
				}
				echo '</td><td>';
				echo date('d/m/Y H:i:s', strtotime($pmt['last_update']));
				echo '</td><td>';
				echo $pmt['status'];
				echo '</td><td>';
				echo $pmt['email'];
				echo '</td><td>';
				echo $curr_sign.sprintf("%0.2f", $pmt["payment_amount"]);
				if($pmt['refund_amount'] <> 0){
					echo '<br><span style="color:#ff0000;">-'.$curr_sign.sprintf("%0.2f", $pmt["refund_amount"]).'</span>';
				}
				echo '</td><td>';
				echo $pmt['title'];
				echo '</td><td>';
				echo $pmt['firstname'].' '.$pmt['lastname'];
				echo '</td><td>';
				// echo $pmt['attendee_firstname'].' '.$pmt['attendee_lastname'];
				echo $attendee_names;
				echo '</td><td>';
				echo $pmt['address'];
				echo '</td><td>';
				echo $pmt['payment_intent_id'];
				echo '</td><td>';
				$edit_link = admin_url('admin.php?page=edit_payment&epmt='.$pmt['id']);
				echo '<a href="'.$edit_link.'">edit</a>';
				echo '</td></tr><tr class="'.$row_class.'"><td>';
				echo $pmt['id'];
				echo '</td><td>';
				echo $pmt['phone'];
				echo '</td><td>';
				echo $pmt['pmt_method'];
				echo '</td><td>';
				// echo $pmt['attendee_email'];
				echo $attendee_emails;
				echo '</td><td>';
				if( $pmt["disc_amount"] > 0 ){
					echo $curr_sign.sprintf("%0.2f", $pmt["disc_amount"]);
				}
				if( $pmt["disc_amount"] > 0 && $pmt['voucher_amount'] > 0 ){
					echo '<br>';
				}
				if( $pmt['voucher_amount'] > 0 ){
					echo $curr_sign.sprintf("%0.2f", $pmt["voucher_amount"]);
				}
				echo '</td><td>';
				if( $pmt['disc_code'] <> '' ){
					echo $pmt['disc_code'];
				}
				if( $pmt['disc_code'] <> '' && $pmt['voucher_code'] <> '' ){
					echo '<br>';
				}
				if( $pmt['voucher_code'] <> '' ){
					echo $pmt['voucher_code'];
				}
				echo '</td><td>';
				echo $pmt['source'];
				echo '</td><td>';
				echo $pmt['webcast_attend'];
				echo '</td><td>';
				echo cc_payment_invoice_address( $pmt, 'string' );
				// echo $pmt['inv_address'];
				echo '</td><td>';
				echo $pmt['notes'];
				echo '</td><td>';
				$del_link = add_query_arg( array(
				    'page' => 'ccpa_payments',
				    'dpmt' => $pmt['id'],
				), admin_url() );
				echo '<a href="'.$del_link.'" onclick="return confirm(\'Are you sure?\')">del</a>';
				echo '</td></tr><tr class="'.$row_class.'"><td>&nbsp;</td><td>';
				echo $pmt['inv_phone'];
				echo '</td><td>';
				echo $pmt['vat_exempt'];
				echo '</td><td>';
				echo $pmt['inv_email'];
				echo '</td><td>';
				echo $curr_sign.sprintf("%0.2f", $pmt["vat_included"]);
				echo '</td><td>';
				echo $pmt['mailing_list'];
				echo '</td><td>';
				echo $pmt['inv_ref'];
				echo '</td><td>';
				echo $pmt['tandcs'];
				echo '</td><td colspan="3">';
				if($pmt['workshop_id'] > 0){
					echo $pmt['workshop_id'].': '.get_the_title($pmt['workshop_id']);
					if($pmt['event_ids'] <> ''){
						$event_ids = explode(',', $pmt['event_ids']);
						foreach ($event_ids as $event_id) {
							if($event_id <> ''){
								$event_name = get_post_meta($pmt['workshop_id'], 'event_'.$event_id.'_name', true);
								if($event_name <> ''){
									echo '<br>EVENT: '.$event_name;
								}
							}
						}
					}
				}else{
					echo $pmt['payment_ref'];
				}
				if($pmt['upsell_workshop_id'] > 0){
					echo '<br>UPSELL: '.$pmt['upsell_workshop_id'].': '.get_the_title($pmt['upsell_workshop_id']);
				}
				echo '</td></tr>';
			} ?>
		</tbody>
	</table>
	<table class="ccpa-page-links">
		<tr>
			<td>
				<?php
				if($this_page > 1){
					$prev_link = add_query_arg( array(
					    'page' => 'ccpa_payments',
					    'ppmt' => $this_page - 1,
					    'show' => $showpp,
					), admin_url() );
					echo '<a href="'.$prev_link.'">Prev</a>';
				}
				?>
			</td>
			<td style="text-align:right;">
				<?php
				if($more){
					$next_link = add_query_arg( array(
					    'page' => 'ccpa_payments',
					    'ppmt' => $this_page + 1,
					    'show' => $showpp,
					), admin_url() );
					echo '<a href="'.$next_link.'">Next</a>';
				}
				?>
			</td>
		</tr>
	</table>
	<?php
}
