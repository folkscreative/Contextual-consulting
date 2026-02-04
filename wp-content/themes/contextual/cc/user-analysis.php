<?php
/**
 * User analysis
 */

add_action('admin_menu', 'cc_user_anal_admin_pages');
function cc_user_anal_admin_pages(){
	// add_users_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int $position = null )
	add_users_page( 'User Analysis', 'User Analysis', 'manage_options', 'user_analysis', 'cc_user_anal_page' );
}

function cc_user_anal_page(){
	if(!current_user_can('edit_posts')) wp_die('Go away!');

	$pmts_from = '';
	$pmts_from_std = '';
	$pmts_to = '';
	$pmts_to_std = '';
	$train_type = 'all';
	$pmt_amt_from = $pmt_amt_to = 0;
	$user_tot_from = $user_tot_to = 0;
	$user_regs_from = $user_regs_to = 0;
	$raf_flag_setting = 'any';
	$msg = '';
	$stats = '';

	if( isset( $_POST['user-anal-submit'] ) ){
		if( isset( $_POST['pmts_from'] ) && $_POST['pmts_from'] <> '' ){
			$date = DateTime::createFromFormat("d/m/Y", $_POST['pmts_from']);
			if($date){
				$pmts_from = $date->format('d/m/Y');
				$pmts_from_std = $date->format('Y-m-d').' 00:00:00';
			}
		}
		if( isset( $_POST['pmts_to'] ) && $_POST['pmts_to'] <> '' ){
			$date = DateTime::createFromFormat("d/m/Y", $_POST['pmts_to']);
			if($date){
				$pmts_to = $date->format('d/m/Y');
				$pmts_to_std = $date->format('Y-m-d').' 23:59:59';
			}
		}
		if( isset( $_POST['train_type'] ) && ( $_POST['train_type'] == 'workshops' || $_POST['train_type'] == 'recordings' ) ){
			$train_type = $_POST['train_type'];
		}
		if( isset( $_POST['pmt_amt_from'] ) && $_POST['pmt_amt_from'] > 0 ){
			$pmt_amt_from = $_POST['pmt_amt_from'];
		}
		if( isset( $_POST['pmt_amt_to'] ) && $_POST['pmt_amt_to'] > 0 ){
			$pmt_amt_to = $_POST['pmt_amt_to'];
		}
		if( isset( $_POST['user_tot_from'] ) && $_POST['user_tot_from'] > 0 ){
			$user_tot_from = $_POST['user_tot_from'];
		}
		if( isset( $_POST['user_tot_to'] ) && $_POST['user_tot_to'] > 0 ){
			$user_tot_to = $_POST['user_tot_to'];
		}
		if( isset( $_POST['user_regs_from'] ) && $_POST['user_regs_from'] > 0 ){
			$user_regs_from = $_POST['user_regs_from'];
		}
		if( isset( $_POST['user_regs_to'] ) && $_POST['user_regs_to'] > 0 ){
			$user_regs_to = $_POST['user_regs_to'];
		}
		if( isset( $_POST['raf_flag_setting'] ) && ( $_POST['raf_flag_setting'] == 'enabled' || $_POST['raf_flag_setting'] == 'disabled' ) ){
			$raf_flag_setting = $_POST['raf_flag_setting'];
		}

		$stats = cc_user_anal_stats($pmts_from_std, $pmts_to_std, $train_type, $pmt_amt_from, $pmt_amt_to, $user_tot_from, $user_tot_to, $user_regs_from, $user_regs_to, $raf_flag_setting);
	}
	?>

	<div class="wrap">
		<h2>User Analysis</h2>
		<div id="poststuff">
			<div id="post-body">
				<div class="postbox">
					<div class="inside">
						<h3>Find Users</h3>
						<?php if($msg <> ''){ ?>
							<p class="voucher-gen-msg <?php echo $msg_type; ?>"><?php echo $msg; ?></p>
						<?php } ?>
						<form action="" method="post">
							<table class="form-table">
								<tr>
									<th>
										<label>Payment dates</label>
									</th>
									<td>
										<label for="pmts_from">From (inclusive)</label><br>
										<input type="text" id="pmts_from" name="pmts_from" class="regular-text" placeholder="dd/mm/yyyy" value="<?php echo $pmts_from; ?>">
									</td>
									<td>
										<label for="pmts_to">To (inclusive)</label><br>
										<input type="text" id="pmts_to" name="pmts_to" class="regular-text" placeholder="dd/mm/yyyy" value="<?php echo $pmts_to; ?>">
									</td>
								</tr>
								<tr>
									<th>
										<label for="train_type">Training type</label>
									</th>
									<td>
										<select name="train_type" id="train_type" class="regular-text">
											<option value="all" <?php selected($train_type, 'all'); ?>>All</option>
											<option value="workshops" <?php selected($train_type, 'workshops'); ?>>Workshops</option>
											<option value="recordings" <?php selected($train_type, 'recordings'); ?>>Recordings</option>
										</select>
									</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<th>
										<label>Payment amount (in £)</label>
									</th>
									<td>
										<label for="pmt_amt_from">From</label><br>
										<input type="number" min="0" step="0.01" id="pmt_amt_from" name="pmt_amt_from" class="regular-text" value="<?php echo $pmt_amt_from; ?>">
									</td>
									<td>
										<label for="pmt_amt_to">To</label><br>
										<input type="number" min="0" step="0.01" id="pmt_amt_to" name="pmt_amt_to" class="regular-text" value="<?php echo $pmt_amt_to; ?>">
									</td>
								</tr>
								<tr>
									<th>
										<label>Registrant total spend (in £)</label>
									</th>
									<td>
										<label for="user_tot_from">From</label><br>
										<input type="number" min="0" step="0.01" id="user_tot_from" name="user_tot_from" class="regular-text" value="<?php echo $user_tot_from; ?>">
									</td>
									<td>
										<label for="user_tot_to">To</label><br>
										<input type="number" min="0" step="0.01" id="user_tot_to" name="user_tot_to" class="regular-text" value="<?php echo $user_tot_to; ?>">
									</td>
								</tr>
								<tr>
									<th>
										<label>Number of user registrations</label>
									</th>
									<td>
										<label for="user_regs_from">From</label><br>
										<input type="number" min="0" step="1" id="user_regs_from" name="user_regs_from" class="regular-text" value="<?php echo $user_regs_from; ?>">
									</td>
									<td>
										<label for="user_regs_to">To</label><br>
										<input type="number" min="0" step="1" id="user_regs_to" name="user_regs_to" class="regular-text" value="<?php echo $user_regs_to; ?>">
									</td>
								</tr>
								<tr>
									<th>
										<label for="raf_flag_setting">Refer a Friend flag</label>
									</th>
									<td>
										<select name="raf_flag_setting" id="raf_flag_setting" class="regular-text">
											<option value="any" <?php selected($raf_flag_setting, 'any'); ?>>Any</option>
											<option value="enabled" <?php selected($raf_flag_setting, 'enabled'); ?>>Enabled</option>
											<option value="disabled" <?php selected($raf_flag_setting, 'disabled'); ?>>Disabled</option>
										</select>
									</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<th></th>
									<td><input type="submit" id="" name="user-anal-submit" value="Go" class="button-primary" /></td>
								</tr>
							</table>
						</form>
					</div>
				</div>

				<?php if(is_array($stats)){ ?>
					<div class="postbox">
						<div class="inside">
							<h3>Results</h3>
							<table>
								<tr>
									<th class="text-center">Number of Payments</th>
									<th class="text-center">Number of users</th>
									<th class="text-center">Top users by spend</th>
									<th class="text-center">Top users by reg'ns</th>
								</tr>
								<tr>
									<td class="big-text text-center"><?php echo $stats['pmts']; ?></td>
									<td class="big-text text-center"><?php echo $stats['users']; ?></td>
									<td class="">
										<table>
											<?php foreach ($stats['amounts'] as $user_id => $values) { ?>
												<tr>
													<td><?php
														$user = get_user_by('ID', $user_id);
														if($user){
															echo $user->first_name.' '.$user->last_name.' '.$user->user_email.' '; ?>
															<a href="/wp-admin/user-edit.php?user_id=<?php echo $user_id; ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
														<?php }else{
															echo 'User ID '.$user_id.' not found';
														} ?>
													</td>
													<td>
														<?php echo $values['regs']; ?>
													</td>
													<td>
														<?php echo cc_money_format($values['amount'], 'GBP'); ?>
													</td>
												</tr>
											<?php } ?>
										</table>
									</td>
									<td class="">
										<table>
											<?php foreach ($stats['regs'] as $user_id => $values) { ?>
												<tr>
													<td><?php
														$user = get_user_by('ID', $user_id);
														if($user){
															echo $user->first_name.' '.$user->last_name.' '.$user->user_email.' '; ?>
															<a href="/wp-admin/user-edit.php?user_id=<?php echo $user_id; ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
														<?php }else{
															echo 'User ID '.$user_id.' not found';
														} ?>
													</td>
													<td>
														<?php echo $values['regs']; ?>
													</td>
													<td>
														<?php echo cc_money_format($values['amount'], 'GBP'); ?>
													</td>
												</tr>
											<?php } ?>
										</table>
									</td>
								</tr>
							</table>

							<hr>
							<h3>Refer a Friend</h3>
							<p>Flag or unflag these <?php echo $stats['users']; ?> users for Refer a Friend</p>
							<button class="button-primary raf-tag-enable" data-transient="<?php echo $stats['transient']; ?>" data-enable="enable">Enable RaF</button>
							<button class="button-secondary raf-tag-enable" data-transient="<?php echo $stats['transient']; ?>" data-enable="disable">Disable RaF</button>
							<p id="raf-tag-enable-msg"></p>

							<hr>
							<h3>Newsletter List</h3>
							<p>Create a newsletter list of these users</p>
							<label for="user_news_list">Name of list to be created</label>
							<input type="text" class="regular-text" id="user_news_list" value="Users selected <?php echo date('d/m/Y H:i'); ?>">
							<button id="user_news_list_create" class="button-primary" data-transient="<?php echo $stats['transient']; ?>">Create list</button>
							<p id="news_list_msg"></p>

						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>

	<?php
}

// get the stats
function cc_user_anal_stats($pmts_from_std, $pmts_to_std, $train_type, $pmt_amt_from, $pmt_amt_to, $user_tot_from, $user_tot_to, $user_regs_from, $user_regs_to, $raf_flag_setting){
	global $wpdb;
	$payments_table = $wpdb->prefix.'ccpa_payments';

	$stats = array(
		'transient' => uniqid(),
		'pmts' => 0,
	);

	$sql = "SELECT * FROM $payments_table";
	$where = '';
	if($pmts_from_std <> ''){
		$where .= "last_update >= '$pmts_from_std'";
	}
	if($pmts_to_std <> ''){
		if( $where <> '' ){
			$where .= ' AND ';
		}
		$where .= "last_update <= '$pmts_to_std'";
	}
	if($train_type <> 'all'){
		if( $where <> '' ){
			$where .= ' AND ';
		}
		if( $train_type == 'recordings' ){
			$where .= "type = 'recording'";
		}else{
			$where .= "type = ''";
		}
	}
	if( $where <> '' ){
		$sql .= ' WHERE '.$where;
	}

	$payments = $wpdb->get_results($sql, ARRAY_A);

	$user_stats = array();

	if($pmt_amt_to == 0){
		$pmt_amt_to = 999999;
	}
	if($user_tot_to == 0){
		$user_tot_to = 999999;
	}
	if($user_regs_to == 0){
		$user_regs_to = 999999;
	}

	foreach ($payments as $payment) {
		$payment_currency = $payment['currency'];
		if( ! in_array( $payment_currency, array('GBP', 'AUD', 'USD', 'EUR' ) ) ){
			$payment_currency = 'GBP';
		}
		$pmt_gbp = cc_voucher_core_curr_convert($payment_currency, 'GBP', $payment['payment_amount']);
		if( $pmt_gbp < $pmt_amt_from || $pmt_gbp > $pmt_amt_to ){
			continue;
		}

		$user_id = $payment['reg_userid'];
		if( $payment['reg_userid'] == 0 && $payment['email'] <> '' ){
			$user = cc_users_get_user($payment['email']);
			if( $user ){
				$user_id = $user->ID;
			}
		}
		if($user_id == 0) continue;

		if( isset( $user_stats[$user_id] ) ){
			$user_stats[$user_id]['regs'] ++;
			$user_stats[$user_id]['amount'] += $pmt_gbp;
		}else{
			$user_stats[$user_id] = array(
				'regs' => 1,
				'amount' => $pmt_gbp,
			);
		}
	}

	if($user_tot_from > 0 || $user_tot_to < 999999){
		$new_user_stats = array();
		foreach ($user_stats as $user_id => $values) {
			// echo '<br>'.$user_id.' '.$values['regs'].' '.$values['amount'];
			if( $values['amount'] >= $user_tot_from && $values['amount'] <= $user_tot_to ){
				$new_user_stats[$user_id] = $values;
			}
		}
		$user_stats = $new_user_stats;
	}

	if($raf_flag_setting <> 'any'){
		$new_user_stats = array();
		foreach ($user_stats as $user_id => $values) {
			$raf_flag = get_user_meta($user_id, 'refer_a_friend', true);
			if($raf_flag_setting == 'enabled' && $raf_flag == 'yes' || $raf_flag_setting == 'disabled' && $raf_flag == ''){
				$new_user_stats[$user_id] = $values;
			}
		}		
		$user_stats = $new_user_stats;
	}

	if($user_regs_from > 0 || $user_regs_to < 999999){
		$new_user_stats = array();
		foreach ($user_stats as $user_id => $values) {
			if( $values['regs'] >= $user_regs_from && $values['regs'] <= $user_regs_to ){
				$new_user_stats[$user_id] = $values;
				$stats['pmts'] += $values['regs'];
			}
		}
		$user_stats = $new_user_stats;
	}else{
		foreach ($user_stats as $user_id => $values) {
			$stats['pmts'] += $values['regs'];
		}
	}

	$stats['users'] = count($user_stats);

	// sort into descending amount order
	uasort($user_stats, function ($item1, $item2) {
		if( $item1['amount'] == $item2['amount'] ){
			// sort by registrations
			return $item2['regs'] <=> $item1['regs'];
		}
		return $item2['amount'] <=> $item1['amount'];
	    
	});
	$stats['amounts'] = array_slice($user_stats, 0, 10, true);

	// sort into descending registrations order
	uasort($user_stats, function ($item1, $item2) {
		if( $item1['regs'] == $item2['regs'] ){
			// sort by amount
			return $item2['amount'] <=> $item1['amount'];
		}
	    return $item2['regs'] <=> $item1['regs'];
	});
	$stats['regs'] = array_slice($user_stats, 0, 10, true);

	// save the user_ids as a transient
	set_transient( 'cc_user_analysis_'.$stats['transient'], array_keys($user_stats), DAY_IN_SECONDS );

	return $stats;
}

// enable or disable the refer a friend flag for the chosen users
add_action('wp_ajax_cc_user_anal_raf', 'cc_user_anal_raf');
function cc_user_anal_raf(){
	$response = array(
		'class' => 'bg-danger',
		'msg' => 'Saved list of users expired. No users updated. Please re-select and try again.',
	);
	$enable = '';
	$transient = 'cc_user_analysis_';
	$user_ids = false;
	$update_count = 0;
	if( isset( $_POST['transient'] ) ){
		$transient .= stripslashes( sanitize_text_field( $_POST['transient'] ) );
		$user_ids = get_transient( $transient );
	}
	if( isset( $_POST['enable'] ) ){
		$enable = stripslashes( sanitize_text_field( $_POST['enable'] ) );
	}

	if( $user_ids !== false && ( $enable == 'enable' || $enable == 'disable' ) ){
		if($enable == 'enable'){
			foreach ($user_ids as $user_id) {
				if( update_user_meta( $user_id, 'refer_a_friend', 'yes') ){
					// true = successful update
					// false = failure or if the value passed to the function is the same as the one that is already in the database
					$update_count ++;
				}
			}
		}else{
			foreach ($user_ids as $user_id) {
				if( delete_user_meta( $user_id, 'refer_a_friend') ){
					// True on success, false on failure.
					$update_count ++;
				}
			}
		}
		$response['class'] = 'bg-success';
		$response['msg'] = 'Refer a Friend flag '.$enable.'d for '.$update_count.' users';
	}
    echo json_encode($response);
    die();
}

// allocate the users to a newsletter list
add_action('wp_ajax_cc_user_news_list', 'cc_user_news_list');
function cc_user_news_list(){
	$response = array(
		'class' => 'bg-danger',
		'msg' => 'Saved list of users expired. No users updated. Please re-select and try again.',
	);
	$transient = 'cc_user_analysis_';
	$list_name = '';
	$user_ids = false;
	$update_count = 0;
	$error_count = 0;
	if( isset( $_POST['transient'] ) ){
		$transient .= stripslashes( sanitize_text_field( $_POST['transient'] ) );
		$user_ids = get_transient( $transient );
	}
	if( isset( $_POST['listName'] ) ){
		$list_name = stripslashes( sanitize_text_field( $_POST['listName'] ) );
	}

	if($list_name == ''){
		$response['msg'] = 'List name cannot be blank';
	}else{
		if( $user_ids !== false ){
			$list_id = mailster( 'lists' )->get_by_name( $list_name, 'ID' );
			if( $list_id ){
				$response['msg'] = 'List name has already been used';
			}else{
				$list_id = mailster( 'lists' )->add( $list_name );
				foreach ($user_ids as $user_id) {
					$subscriber_id = mailster( 'subscribers' )->add_from_wp_user( $user_id, array('_lists' => $list_id), true, false );
					if( is_wp_error( $subscriber_id ) ){
						$error_count ++;
						cc_debug_log_anything( array(
							'function' => 'cc_user_news_list',
							'user_id' => $user_id,
							'msg' => $subscriber_id->get_error_message()
						) );
					}else{
						$update_count ++;
					}
				}
				$response['class'] = 'bg-success';
				$response['msg'] = 'List '.$list_name.' created and '.$update_count.' users added to the list. There were '.$error_count.' errors';
			}
		}
	}
    echo json_encode($response);
    die();
}

