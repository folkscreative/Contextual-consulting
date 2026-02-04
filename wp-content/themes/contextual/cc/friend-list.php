<?php
/**
 * Refer a Friend settings and list
 */

// The submenu is added in the discounts file
// this is its html ...
function cc_friends_list_page(){
	if(!current_user_can('edit_posts')) wp_die('Go away!');

	$refer_friend_active = get_option('refer_friend_active', '');
	$refer_friend_msg = get_option('refer_friend_msg', '');
	$refer_friend_percent = get_option('refer_friend_percent', 0);

	// new voucher to be generated?
	$msg = '';
	if(isset($_POST['raf-submit'])){

		if( isset( $_POST['raf_active'] ) ){
			$refer_friend_active = 'active';
		}else{
			$refer_friend_active = '';
		}
		if( isset( $_POST['rafmsg'] ) && $_POST['rafmsg'] <> '' ){
			// $refer_friend_msg = stripslashes( sanitize_textarea_field( $_POST['rafmsg'] ) );
			$refer_friend_msg = wp_kses_post ( $_POST['rafmsg'] );
		}
		if( isset( $_POST['raf_percent'] ) ){
			$refer_friend_percent = (float) $_POST['raf_percent'];
		}

		update_option('refer_friend_active', $refer_friend_active);
		update_option('refer_friend_msg', $refer_friend_msg);
		update_option('refer_friend_percent', $refer_friend_percent);

		$msg = 'Updated';
		$msg_type = 'success';

	}

	$stats = cc_friend_stats();

	$top_referrers = cc_friend_top_referrers();
	
	?>

	<div class="wrap">
		<h2>Refer a Friend</h2>
		<div id="poststuff">
			<div id="post-body">

				<div class="postbox">
					<div class="inside">
						<h3>Settings</h3>
						<?php if($msg <> ''){ ?>
							<p class="voucher-gen-msg <?php echo $msg_type; ?>"><?php echo $msg; ?></p>
						<?php } ?>
						<form action="" method="post">
							<table class="form-table">
								<tbody>
									<tr>
										<th>Active?</th>
										<td>
											<label for="raf_active" class="toggle-switch">
												<input type="checkbox" id="raf_active" name="raf_active" value="active" <?php checked($refer_friend_active, 'active'); ?>>
												<span class="toggle-slider round"></span>
											</label>
										</td>
										<td>
											Number of users with Refer a Friend flag set
											<div class="big-text"><?php echo cc_friend_raf_flags_set(); ?></div>
										</td>
									</tr>
									<tr>
										<th>
											<label>Refer a Friend Message (shown in the My Account section)</label>
										</th>
										<td colspan="2">
											<?php
											$settings = array(
												'media_buttons' => false,
												'textarea_rows' => 4,
												'teeny' => true,
											);
											wp_editor( $refer_friend_msg, 'rafmsg', $settings );
											?>
										</td>
									</tr>
									<tr>
										<th>
											<label>Discount %</label>
										</th>
										<td>
											<input type="number" min="0" max="100" name="raf_percent" value="<?php echo $refer_friend_percent; ?>"> %
										</td>
										<td></td>
									</tr>
									<tr>
										<th></th>
										<td><input type="submit" name="raf-submit" value="Update" class="button-primary" /></td>
										<td></td>
									</tr>
								</tbody>
							</table>
						</form>
					</div>
				</div>

				<div class="postbox">
					<div class="inside">
						<h3>Stats</h3>
						<table class="w-100">
							<tr>
								<th class="text-center">No. Discounts</th>
								<th class="text-center">Codes Used</th>
								<th class="text-center">Tot. Discounts/Credits</th>
								<th class="text-center">Tot. Redeemed</th>
								<th class="text-center">Tot. Expired</th>
								<th class="text-center">Balance</th>
							</tr>
							<tr>
								<td class="big-text text-center"><?php echo $stats['credits']; ?></td>
								<td class="big-text text-center"><?php echo $stats['givers']; ?></td>
								<td class="big-text text-center"><?php echo cc_money_format($stats['credit_value'], 'GBP' ); ?></td>
								<td class="big-text text-center"><?php echo cc_money_format($stats['redeemed'], 'GBP' ); ?></td>
								<td class="big-text text-center"><?php echo cc_money_format($stats['expired'], 'GBP' ); ?></td>
								<td class="big-text text-center"><?php echo cc_money_format($stats['balance'], 'GBP' ); ?></td>
							</tr>
						</table>

						<p>&nbsp;</p>
						<h3>Top Referrers</h3>
						<table>
							<tr>
								<th align="left">User</th>
								<th align="left">Code</th>
								<th align="right">Credited</th>
								<th align="right">Redeemed</th>
								<th align="right">Expired</th>
								<th align="right">Balance</th>
							</tr>
							<?php foreach ($top_referrers as $referrer) { ?>
								<tr>
									<td>
										<?php
										$user = get_user_by('ID', $referrer['user_id']);
										if($user){
											echo $user->first_name.' '.$user->last_name.' '.$user->user_email.' '; ?>
											<a href="/wp-admin/user-edit.php?user_id=<?php echo $referrer['user_id']; ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
										<?php }else{
											echo 'User ID '.$user_id.' not found';
										} ?>
									</td>
									<td>
										<?php echo $referrer['raf_code']; ?>
									</td>
									<td align="right">
										<?php echo cc_money_format( $referrer['credited'], $referrer['currency'] ); ?>
									</td>
									<td align="right">
										<?php echo cc_money_format( $referrer['redeemed'], $referrer['currency'] ); ?>
									</td>
									<td align="right">
										<?php echo cc_money_format( $referrer['expired'], $referrer['currency'] ); ?>
									</td>
									<td align="right">
										<?php echo cc_money_format( $referrer['balance'], $referrer['currency'] ); ?>
									</td>
								</tr>
							<?php } ?>
						</table>

					</div>
				</div>

			</div>
		</div>
	</div>
	
	<?php
}
